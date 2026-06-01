<?php
namespace App\Service;

use App\Service\Database;

/**
 * 备份服务：自动备份、加密备份、备份管理
 */
class Backup
{
    private static $backupDir;

    public static function init(): void
    {
        self::$backupDir = dirname(__DIR__, 2) . '/backup';
        if (!is_dir(self::$backupDir)) {
            @mkdir(self::$backupDir, 0700, true);
        }
    }

    /**
     * 执行完整备份
     */
    public static function performBackup(): array
    {
        self::init();
        $config = Config::get('backup', []);

        if (!($config['enabled'] ?? false)) {
            return ['success' => false, 'error' => '备份功能未启用'];
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupName = 'backup_' . $timestamp;
        $backupPath = self::$backupDir . '/' . $backupName;

        if (!@mkdir($backupPath, 0700)) {
            return ['success' => false, 'error' => '无法创建备份目录'];
        }

        $results = [];
        $errors = [];

        // 数据库备份
        if ($config['backup_paths']['database'] ?? false) {
            $dbResult = self::backupDatabase($backupPath, $config);
            $results['database'] = $dbResult;
            if (!($dbResult['success'] ?? false)) {
                $errors[] = '数据库备份: ' . ($dbResult['error'] ?? '未知错误');
            }
        }

        // 上传文件备份
        if ($config['backup_paths']['uploads'] ?? false) {
            $uploadResult = self::backupUploads($backupPath);
            $results['uploads'] = $uploadResult;
            if (!($uploadResult['success'] ?? false)) {
                $errors[] = '文件备份: ' . ($uploadResult['error'] ?? '未知错误');
            }
        }

        // 创建备份清单
        self::createManifest($backupPath, $results);

        $totalSize = self::getDirSize($backupPath);

        // 发送邮件通知（如果启用）
        $emailNotify = (bool)($config['email_notify'] ?? false);
        $notifyEmail = trim((string)($config['notify_email'] ?? ''));
        if ($emailNotify && !empty($notifyEmail) && empty($errors)) {
            self::sendBackupNotificationEmail($notifyEmail, $backupName, $totalSize, $results);
        }

        // 清理过期备份
        $keepVersions = (int)($config['keep_versions'] ?? 30);
        self::cleanupOldBackups($config['retention_days'] ?? 30, $keepVersions);

        return [
            'success' => empty($errors),
            'backup_name' => $backupName,
            'backup_path' => $backupPath,
            'timestamp' => $timestamp,
            'total_size' => $totalSize,
            'formatted_size' => self::formatBytes($totalSize),
            'results' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * 发送备份成功通知邮件
     */
    private static function sendBackupNotificationEmail(string $toEmail, string $backupName, int $totalSize, array $results): bool
    {
        $subject = '【' . Config::get('app.name', '记账系统') . '】备份完成通知';

        $body = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>备份通知</title></head>
<body style="font-family:Arial,sans-serif;padding:20px;">
    <div style="max-width:600px;margin:0 auto;background:#f5f5f5;padding:20px;border-radius:8px;">
        <h2 style="color:#333;">📦 数据备份完成</h2>
        <hr style="border:none;border-top:1px solid #ddd;">
        <p><strong>备份名称：</strong>' . htmlspecialchars($backupName) . '</p>
        <p><strong>备份时间：</strong>' . date('Y-m-d H:i:s') . '</p>
        <p><strong>备份大小：</strong>' . self::formatBytes($totalSize) . '</p>
        <p><strong>备份结果：</strong></p>
        <ul style="background:#fff;padding:15px;border-radius:4px;">'
            . (isset($results['database']) ? '<li>✅ 数据库备份：' . ($results['database']['success'] ? '成功 (' . ($results['database']['formatted_size'] ?? '') . ')' : '失败') . '</li>' : '')
            . (isset($results['uploads']) ? '<li>✅ 文件备份：' . ($results['uploads']['success'] ? '成功 (' . ($results['uploads']['formatted_size'] ?? '') . ')' : '失败') . '</li>' : '')
        . '</ul>
        <p style="color:#999;font-size:12px;margin-top:20px;">此邮件由系统自动发送，请勿回复。</p>
    </div>
</body>
</html>';

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n";

        return @mail($toEmail, $subject, $body, $headers);
    }

    /**
     * 数据库备份：优先 mysqldump，找不到自动回退纯 PHP 导出
     */
    private static function backupDatabase(string $backupPath, array $config): array
    {
        try {
            $dumpFile = $backupPath . '/database.sql';

            // 优先尝试 mysqldump
            $mysqldumpPath = self::findMysqldump();
            if (!empty($mysqldumpPath)) {
                $result = self::backupWithMysqldump($dumpFile, $mysqldumpPath);
                if ($result['success']) {
                    return self::finalizeBackup($dumpFile, $config, $result);
                }
                // mysqldump 失败则回退到纯 PHP
            }

            // 纯 PHP 导出
            $phpResult = self::backupWithPhp($dumpFile);
            return self::finalizeBackup($dumpFile, $config, $phpResult);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => '数据库备份异常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 通过 mysqldump 导出
     */
    private static function backupWithMysqldump(string $dumpFile, string $mysqldumpPath): array
    {
        $dbConfig = Config::get('db', []);
        $password = isset($dbConfig['pass']) ? escapeshellarg($dbConfig['pass']) : '';
        $user = escapeshellarg($dbConfig['user'] ?? 'root');
        $host = escapeshellarg($dbConfig['host'] ?? 'localhost');
        $database = escapeshellarg($dbConfig['dbname'] ?? '');
        $port = isset($dbConfig['port']) ? '--port=' . (int)$dbConfig['port'] : '';

        $cmd = $mysqldumpPath . ' --user=' . $user . ' --password=' . $password . ' --host=' . $host . ' ' . $port . ' --single-transaction --quick --lock-tables=false ' . $database . ' > ' . escapeshellarg($dumpFile) . ' 2>&1';

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            return ['success' => false, 'error' => implode("\n", $output)];
        }
        if (!file_exists($dumpFile) || filesize($dumpFile) === 0) {
            return ['success' => false, 'error' => '备份文件为空'];
        }

        return [
            'success' => true,
            'file' => basename($dumpFile),
            'size' => filesize($dumpFile),
            'formatted_size' => self::formatBytes(filesize($dumpFile)),
        ];
    }

    /**
     * 纯 PHP 导出数据库（不依赖任何外部工具，分批读取避免内存溢出）
     */
    private static function backupWithPhp(string $dumpFile): array
    {
        $pdo = Database::getConnection();
        $dbConfig = Config::get('db', []);
        $dbName = $dbConfig['dbname'] ?? '';

        $handle = @fopen($dumpFile, 'w');
        if (!$handle) {
            return ['success' => false, 'error' => '无法创建备份文件'];
        }

        fwrite($handle, "-- 数据库备份 (纯PHP导出)\n");
        fwrite($handle, "-- 数据库: {$dbName}\n");
        fwrite($handle, "-- 时间: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- ========================================\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        // 获取所有表
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        $totalRows = 0;

        foreach ($tables as $table) {
            // 表结构
            $createResult = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            if ($createResult) {
                fwrite($handle, "-- 表结构: {$table}\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createResult[1] . ";\n\n");
            }

            // 获取列信息
            $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            $columnNames = array_map(fn($c) => '`' . $c['Field'] . '`', $columns);
            $columnList = implode(', ', $columnNames);

            // 分批读取表数据（游标方式，避免 fetchAll 内存溢出）
            $rowCount = 0;
            $batchSize = 500;
            $offset = 0;
            $firstBatch = true;

            do {
                $stmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}");
                $rows = $stmt->fetchAll(\PDO::FETCH_NUM);
                $batchCount = count($rows);

                if ($batchCount > 0 && $firstBatch) {
                    fwrite($handle, "-- 数据: {$table}\n");
                    $firstBatch = false;
                }

                if ($batchCount > 0) {
                    $values = [];
                    foreach ($rows as $row) {
                        $escaped = array_map(function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return $pdo->quote((string) $val);
                        }, $row);
                        $values[] = '(' . implode(', ', $escaped) . ')';
                    }
                    fwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES\n");
                    fwrite($handle, implode(",\n", $values) . ";\n\n");
                    $rowCount += $batchCount;
                }

                // 释放本批数据内存
                unset($rows, $values);
                $offset += $batchSize;
            } while ($batchCount >= $batchSize);

            if ($rowCount > 0) {
                fwrite($handle, "-- 行数: {$rowCount}\n\n");
            }
            $totalRows += $rowCount;
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        $fileSize = filesize($dumpFile);

        return [
            'success' => true,
            'file' => basename($dumpFile),
            'size' => $fileSize,
            'formatted_size' => self::formatBytes($fileSize),
            'method' => 'php',
            'table_count' => count($tables),
            'total_rows' => $totalRows,
        ];
    }

    /**
     * 统一处理加密和返回结果
     */
    private static function finalizeBackup(string $dumpFile, array $config, array $result): array
    {
        if (!$result['success']) {
            return $result;
        }

        if ($config['encrypt_backup'] ?? false) {
            $encryptedFile = $dumpFile . '.enc';
            $encryptResult = self::encryptFile($dumpFile, $encryptedFile, $config['encryption_key'] ?? '');
            if ($encryptResult['success']) {
                @unlink($dumpFile);
                return [
                    'success' => true,
                    'file' => basename($encryptedFile),
                    'size' => filesize($encryptedFile),
                    'encrypted' => true,
                ];
            } else {
                return ['success' => false, 'error' => '加密失败: ' . ($encryptResult['error'] ?? '未知错误')];
            }
        }

        return $result;
    }

    /**
     * 查找 mysqldump 可执行文件路径（兼容群晖 NAS、Linux、macOS）
     */
    private static function findMysqldump(): string
    {
        // 1. 直接用 which/where 查找
        $cmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where mysqldump' : 'which mysqldump';
        $result = trim((string) @shell_exec($cmd . ' 2>/dev/null'));
        if (!empty($result) && file_exists($result)) {
            return $result;
        }

        // 2. 群晖 MariaDB 常见路径
        $synologyPaths = [
            '/volume1/@appstore/MariaDB10/usr/bin/mysqldump',
            '/volume1/@appstore/MariaDB105/usr/bin/mysqldump',
            '/volume1/@appstore/MariaDB/usr/bin/mysqldump',
            '/usr/local/mariadb10/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];
        foreach ($synologyPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // 3. 遍历 /volume1/@appstore/ 下所有 MariaDB*/MySQL* 目录
        $appStoreDir = '/volume1/@appstore';
        if (is_dir($appStoreDir)) {
            $dirs = @scandir($appStoreDir) ?: [];
            foreach ($dirs as $dir) {
                if (stripos($dir, 'maria') !== false || stripos($dir, 'mysql') !== false) {
                    $candidate = $appStoreDir . '/' . $dir . '/usr/bin/mysqldump';
                    if (file_exists($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return '';
    }

    /**
     * 上传文件备份
     */
    private static function backupUploads(string $backupPath): array
    {
        try {
            $uploadsDir = dirname(__DIR__, 2) . '/uploads';

            if (!is_dir($uploadsDir)) {
                return [
                    'success' => true,
                    'message' => '未找到上传目录',
                    'skipped' => true,
                ];
            }

            $targetDir = $backupPath . '/uploads';
            self::recursiveCopy($uploadsDir, $targetDir);

            $size = self::getDirSize($targetDir);

            return [
                'success' => true,
                'path' => 'uploads',
                'size' => $size,
                'formatted_size' => self::formatBytes($size),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => '上传文件备份异常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 递归复制目录
     */
    private static function recursiveCopy(string $src, string $dst): void
    {
        if (!@mkdir($dst, 0700, true) && !is_dir($dst)) {
            throw new \RuntimeException("无法创建目录: {$dst}");
        }

        $dir = @opendir($src);
        if (!$dir) {
            throw new \RuntimeException("无法打开源目录: {$src}");
        }

        try {
            while (($file = readdir($dir)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $srcPath = $src . '/' . $file;
                $dstPath = $dst . '/' . $file;

                if (is_dir($srcPath)) {
                    self::recursiveCopy($srcPath, $dstPath);
                } elseif (is_file($srcPath)) {
                    @copy($srcPath, $dstPath);
                }
            }
        } finally {
            closedir($dir);
        }
    }

    /**
     * 递归删除目录
     */
    private static function recursiveDelete(string $path): bool
    {
        if (is_dir($path)) {
            $files = @scandir($path);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        if (!self::recursiveDelete($path . '/' . $file)) {
                            return false;
                        }
                    }
                }
            }
            return @rmdir($path);
        } else {
            return @unlink($path);
        }
    }

    /**
     * 计算目录大小
     */
    public static function getDirSize(string $path): int
    {
        $size = 0;
        $files = @scandir($path);

        if (!is_array($files)) {
            return 0;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $size += self::getDirSize($filePath);
            } elseif (is_file($filePath)) {
                $size += @filesize($filePath) ?: 0;
            }
        }

        return $size;
    }

    /**
     * 加密文件 (使用 AES-256-CBC)
     */
    private static function encryptFile(string $inputFile, string $outputFile, string $key): array
    {
        try {
            if (!extension_loaded('openssl')) {
                return [
                    'success' => false,
                    'error' => 'OpenSSL 扩展未加载',
                ];
            }

            $data = @file_get_contents($inputFile);
            if ($data === false) {
                return [
                    'success' => false,
                    'error' => '无法读取输入文件',
                ];
            }

            $encryptionKey = hash('sha256', $key, true);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

            if ($iv === false) {
                return [
                    'success' => false,
                    'error' => '无法生成 IV',
                ];
            }

            $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);

            // 立即释放明文内存
            unset($data);

            if ($encrypted === false) {
                return [
                    'success' => false,
                    'error' => '加密失败',
                ];
            }

            if (@file_put_contents($outputFile, $iv . $encrypted) === false) {
                return [
                    'success' => false,
                    'error' => '无法写入输出文件',
                ];
            }

            unset($encrypted);
            return ['success' => true];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => '加密异常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 创建备份清单
     */
    private static function createManifest(string $backupPath, array $results): void
    {
        try {
            $pdo = Database::getConnection();
            $versionResult = $pdo->query('SELECT VERSION()')->fetch(\PDO::FETCH_NUM);
            $dbVersion = $versionResult[0] ?? 'Unknown';

            $manifest = [
                'timestamp' => date('c'),
                'php_version' => phpversion(),
                'database_version' => $dbVersion,
                'system' => php_uname(),
                'backup_results' => $results,
            ];

            @file_put_contents(
                $backupPath . '/manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            error_log('创建备份清单失败: ' . $e->getMessage());
        }
    }

    /**
     * 清理过期备份
     */
    public static function cleanupOldBackups(int $retentionDays, int $keepVersions = 0): void
    {
        try {
            self::init();

            // 1. 按天数清理
            $threshold = time() - ($retentionDays * 86400);
            $backups = @scandir(self::$backupDir);

            if (!is_array($backups)) {
                return;
            }

            foreach ($backups as $backup) {
                if ($backup === '.' || $backup === '..') {
                    continue;
                }

                $backupPath = self::$backupDir . '/' . $backup;
                $modTime = @filemtime($backupPath);

                if ($modTime && $modTime < $threshold) {
                    self::recursiveDelete($backupPath);
                }
            }

            // 2. 按版本数清理（如果设置了 keep_versions）
            if ($keepVersions > 0) {
                $allBackups = self::listBackups(); // 已按时间倒序排列（最新的在前）
                if (count($allBackups) > $keepVersions) {
                    // 删除最旧的（数组后面的）
                    $toDelete = array_slice($allBackups, $keepVersions);
                    foreach ($toDelete as $backup) {
                        $backupPath = self::$backupDir . '/' . $backup['name'];
                        self::recursiveDelete($backupPath);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('清理过期备份失败: ' . $e->getMessage());
        }
    }

    /**
     * 列出所有备份
     */
    public static function listBackups(): array
    {
        try {
            self::init();

            $backups = [];
            $files = @scandir(self::$backupDir, SCANDIR_SORT_DESCENDING);

            if (!is_array($files)) {
                return [];
            }

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = self::$backupDir . '/' . $file;
                if (is_dir($path)) {
                    $manifest = $path . '/manifest.json';
                    $manifestData = [];

                    if (file_exists($manifest)) {
                        $content = @file_get_contents($manifest);
                        if ($content) {
                            $manifestData = json_decode($content, true) ?? [];
                        }
                    }

                    $backups[] = [
                        'name' => $file,
                        'size' => self::getDirSize($path),
                        'formatted_size' => self::formatBytes(self::getDirSize($path)),
                        'created_at' => @filemtime($path),
                        'created_at_formatted' => date('Y-m-d H:i:s', @filemtime($path)),
                        'manifest' => $manifestData,
                    ];
                }
            }

            return $backups;
        } catch (\Throwable $e) {
            error_log('列出备份失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 格式化字节数
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 从指定备份恢复数据库
     */
    public static function restore(string $backupName): array
    {
        try {
            self::init();
            $backupPath = self::$backupDir . '/' . $backupName;

            if (!is_dir($backupPath)) {
                return ['success' => false, 'error' => '备份目录不存在: ' . $backupName];
            }

            // 查找 SQL 文件（优先未加密，其次加密）
            $sqlFile = $backupPath . '/database.sql';
            $encFile = $sqlFile . '.enc';

            if (!file_exists($sqlFile)) {
                if (file_exists($encFile)) {
                    // 解密
                    $config = Config::get('backup', []);
                    $decryptResult = self::decryptFile($encFile, $sqlFile, $config['encryption_key'] ?? '');
                    if (!$decryptResult['success']) {
                        return ['success' => false, 'error' => '解密失败: ' . ($decryptResult['error'] ?? '未知错误')];
                    }
                } else {
                    return ['success' => false, 'error' => '备份中未找到数据库文件 (database.sql)'];
                }
            }

            $dbConfig = Config::get('db', []);
            $database = $dbConfig['dbname'] ?? '';

            if (empty($database)) {
                return ['success' => false, 'error' => '未配置数据库名称'];
            }

            // 优先尝试 mysql 命令行导入
            $mysqlPath = self::findMysql();
            if (!empty($mysqlPath)) {
                $password = isset($dbConfig['pass']) ? escapeshellarg($dbConfig['pass']) : '';
                $user = escapeshellarg($dbConfig['user'] ?? 'root');
                $host = escapeshellarg($dbConfig['host'] ?? 'localhost');
                $port = isset($dbConfig['port']) ? '--port=' . (int)$dbConfig['port'] : '';

                $cmd = $mysqlPath . ' --user=' . $user . ' --password=' . $password . ' --host=' . $host . ' ' . $port . ' ' . $database . ' < ' . escapeshellarg($sqlFile) . ' 2>&1';
                $output = [];
                $exitCode = 0;
                exec($cmd, $output, $exitCode);

                if ($exitCode === 0) {
                    @unlink($sqlFile); // 清理解密后的临时文件
                    return ['success' => true, 'message' => '数据库恢复成功（mysql 命令行）'];
                }
                // 命令行失败则回退到 PHP 导入
            }

            // 纯 PHP 导入
            return self::restoreWithPhp($sqlFile);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => '恢复异常: ' . $e->getMessage()];
        }
    }

    /**
     * 查找 mysql 客户端路径
     */
    private static function findMysql(): string
    {
        $cmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where mysql' : 'which mysql';
        $result = trim((string) @shell_exec($cmd . ' 2>/dev/null'));
        if (!empty($result) && file_exists($result)) {
            return $result;
        }

        $paths = [
            '/volume1/@appstore/MariaDB10/usr/bin/mysql',
            '/volume1/@appstore/MariaDB105/usr/bin/mysql',
            '/volume1/@appstore/MariaDB/usr/bin/mysql',
            '/usr/local/mariadb10/bin/mysql',
            '/usr/local/mysql/bin/mysql',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return '';
    }

    /**
     * 纯 PHP 执行 SQL 导入（流式读取，避免大文件撑爆内存）
     */
    private static function restoreWithPhp(string $sqlFile): array
    {
        $pdo = Database::getConnection();

        $handle = @fopen($sqlFile, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => '无法打开 SQL 文件'];
        }

        $successCount = 0;
        $errorMessages = [];
        $currentStmt = '';

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $currentStmt .= $line;

            if (str_ends_with($trimmed, ';')) {
                $stmtTrimmed = trim($currentStmt);
                if ($stmtTrimmed !== '' && $stmtTrimmed !== ';') {
                    try {
                        $pdo->exec($stmtTrimmed);
                        $successCount++;
                    } catch (\Throwable $e) {
                        $errorMessages[] = substr($stmtTrimmed, 0, 80) . '... → ' . $e->getMessage();
                    }
                }
                $currentStmt = '';
            }
        }
        fclose($handle);

        @unlink($sqlFile);

        if (!empty($errorMessages)) {
            return [
                'success' => false,
                'error' => '部分语句执行失败',
                'details' => array_slice($errorMessages, 0, 5),
                'executed' => $successCount,
            ];
        }

        return ['success' => true, 'message' => "数据库恢复成功，执行 {$successCount} 条语句"];
    }

    /**
     * 解密文件
     */
    private static function decryptFile(string $inputFile, string $outputFile, string $key): array
    {
        try {
            if (!extension_loaded('openssl')) {
                return ['success' => false, 'error' => 'OpenSSL 扩展未加载'];
            }

            $encryptedData = @file_get_contents($inputFile);
            if ($encryptedData === false) {
                return ['success' => false, 'error' => '无法读取加密文件'];
            }

            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            if (strlen($encryptedData) < $ivLength) {
                return ['success' => false, 'error' => '文件格式错误'];
            }

            $iv = substr($encryptedData, 0, $ivLength);
            $encrypted = substr($encryptedData, $ivLength);
            $encryptionKey = hash('sha256', $key, true);

            $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
            if ($decrypted === false) {
                return ['success' => false, 'error' => '解密失败，请检查密钥'];
            }

            if (@file_put_contents($outputFile, $decrypted) === false) {
                return ['success' => false, 'error' => '无法写入解密文件'];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => '解密异常: ' . $e->getMessage()];
        }
    }

    /**
     * 获取备份目录路径
     */
    public static function getBackupDir(): string
    {
        self::init();
        return self::$backupDir;
    }
}

