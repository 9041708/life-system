<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;
use App\Service\Backup;

class BackupController
{
    private function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
            header('Location: /public/index.php?route=landing');
            exit;
        }
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function isAjax(): bool
    {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public function index(): void
    {
        $this->requireLogin();
        $_SESSION['current_page_title'] = '数据备份';

        $config = Config::get('backup', []);
        $backupDir = dirname(__DIR__, 2) . '/backup';

        // 备份配置
        $backupEnabled = (bool)($config['enabled'] ?? false);
        $frequency = $config['frequency'] ?? 'daily';
        $executionDay = (int)($config['execution_day'] ?? 1);
        $executionTime = $config['execution_time'] ?? '02:00';
        $retentionDays = (int)($config['retention_days'] ?? 30);
        $keepVersions = (int)($config['keep_versions'] ?? 30);
        $backupDatabase = (bool)($config['backup_paths']['database'] ?? false);
        $backupUploads = (bool)($config['backup_paths']['uploads'] ?? false);
        $emailNotify = (bool)($config['email_notify'] ?? false);
        $notifyEmail = $config['notify_email'] ?? '';
        $dbConfig = Config::get('db', []);

        // 列出已有备份
        $backups = [];
        if (is_dir($backupDir)) {
            $items = scandir($backupDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $backupDir . '/' . $item;
                if (is_dir($path)) {
                    $manifestFile = $path . '/manifest.json';
                    $manifest = null;
                    if (is_file($manifestFile)) {
                        $manifest = json_decode(file_get_contents($manifestFile), true);
                    }
                    $size = \App\Service\Backup::getDirSize($path);
                    $backups[] = [
                        'name' => $item,
                        'path' => $path,
                        'time' => filemtime($path) ?: 0,
                        'size' => $size,
                        'formatted_size' => Backup::formatBytes($size),
                        'manifest' => $manifest,
                    ];
                }
            }
        }
        usort($backups, fn($a, $b) => $b['time'] - $a['time']);

        // 数据库信息
        $dbInfo = [
            'host' => $dbConfig['host'] ?? 'localhost',
            'port' => $dbConfig['port'] ?? 3306,
            'name' => $dbConfig['database'] ?? '',
            'charset' => $dbConfig['charset'] ?? 'utf8mb4',
        ];

        $this->render('backup/index', [
            'backupEnabled' => $backupEnabled,
            'frequency' => $frequency,
            'executionDay' => $executionDay,
            'executionTime' => $executionTime,
            'retentionDays' => $retentionDays,
            'keepVersions' => $keepVersions,
            'backupDatabase' => $backupDatabase,
            'backupUploads' => $backupUploads,
            'emailNotify' => $emailNotify,
            'notifyEmail' => $notifyEmail,
            'dbInfo' => $dbInfo,
            'backups' => $backups,
        ]);
    }

    public function perform(): void
    {
        if (!$this->isAjax()) {
            http_response_code(400);
            exit;
        }
        $this->requireLogin();

        $result = Backup::performBackup();

        if (!empty($result['success'])) {
            $config = Config::get('backup', []);
            $config['last_run_time'] = time();
            Config::set('backup', $config);
        }

        $this->json($result);
    }

    public function download(): void
    {
        $this->requireLogin();
        $name = trim((string)($_GET['name'] ?? ''));
        if ($name === '') {
            header('Location: /public/index.php?route=backup');
            exit;
        }

        $backupDir = dirname(__DIR__, 2) . '/backup';
        $path = $backupDir . '/' . $name;
        if (!is_dir($path)) {
            http_response_code(404);
            exit;
        }

        // 创建 zip 打包下载
        $zipPath = $backupDir . '/' . $name . '.zip';
        if (!is_file($zipPath)) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $this->zipDir($zip, $path, $name);
                $zip->close();
            }
        }

        if (is_file($zipPath)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $name . '.zip"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            exit;
        }
    }

    public function delete(): void
    {
        if (!$this->isAjax()) {
            http_response_code(400);
            exit;
        }
        $this->requireLogin();

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $this->json(['success' => false, 'error' => '备份名称不能为空']);
        }

        $backupDir = dirname(__DIR__, 2) . '/backup';
        $path = $backupDir . '/' . $name;
        $zipPath = $backupDir . '/' . $name . '.zip';

        $deleted = 0;
        if (is_dir($path)) {
            $this->deleteDir($path);
            $deleted++;
        }
        if (is_file($zipPath)) {
            unlink($zipPath);
            $deleted++;
        }

        $this->json(['success' => true, 'deleted' => $deleted]);
    }

    public function updateConfig(): void
    {
        if (!$this->isAjax()) {
            http_response_code(400);
            exit;
        }
        $this->requireLogin();

        $enabled = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : false;
        $frequency = trim((string)($_POST['frequency'] ?? 'daily'));
        $executionDay = max(1, (int)($_POST['execution_day'] ?? 1));
        $executionTime = trim((string)($_POST['execution_time'] ?? '02:00'));
        $retentionDays = max(1, (int)($_POST['retention_days'] ?? 30));
        $keepVersions = max(1, (int)($_POST['keep_versions'] ?? 30));
        // 备份项目：直接使用复选框值
        $backupDatabase = isset($_POST['backup_database']) ? (bool)$_POST['backup_database'] : false;
        $backupUploads = isset($_POST['backup_uploads']) ? (bool)$_POST['backup_uploads'] : false;
        $emailNotify = isset($_POST['email_notify']) ? (bool)$_POST['email_notify'] : false;
        $notifyEmail = trim((string)($_POST['notify_email'] ?? ''));

        $config = Config::get('backup', []);
        $config['enabled'] = $enabled;
        $config['frequency'] = $frequency;
        $config['execution_day'] = $executionDay;
        $config['execution_time'] = $executionTime;
        $config['retention_days'] = $retentionDays;
        $config['keep_versions'] = $keepVersions;
        $config['backup_paths'] = [
            'database' => $backupDatabase,
            'uploads' => $backupUploads,
        ];
        $config['email_notify'] = $emailNotify;
        $config['notify_email'] = $notifyEmail;

        Config::set('backup', $config);
        $this->json(['success' => true]);
    }

    // getDirSize() moved to Backup class

    private function zipDir(\ZipArchive $zip, string $dir, string $base): void
    {
        $items = @scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            $name = $base . '/' . $item;
            if (is_file($path)) {
                $zip->addFile($path, $name);
            } else {
                $this->zipDir($zip, $path, $name);
            }
        }
    }

    private function deleteDir(string $dir): void
    {
        $files = @scandir($dir) ?: [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . '/' . $file;
            is_file($path) ? @unlink($path) : $this->deleteDir($path);
        }
        @rmdir($dir);
    }
}