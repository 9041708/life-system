<?php
/**
 * 安全系统初始化脚本
 * 用于创建必要的数据库表和配置
 * 
 * 使用方法：
 * php tools/security_init.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Service\Security;
use App\Service\Backup;
use App\Service\Config;

echo "========================================\n";
echo "  三石记账系统 - 安全系统初始化\n";
echo "========================================\n\n";

// 1. 初始化安全表
echo "1️⃣  初始化安全追踪表...\n";
$securityInit = Security::initializeTables();

if ($securityInit['success']) {
    echo "   ✅ 安全表初始化成功\n";
} else {
    echo "   ❌ 安全表初始化失败:\n";
    foreach ($securityInit['errors'] as $error) {
        echo "      - {$error}\n";
    }
}

echo "\n";

// 2. 验证备份配置
echo "2️⃣  验证备份配置...\n";
$backupConfig = Config::get('backup', []);

if ($backupConfig['enabled'] ?? false) {
    echo "   ✅ 备份功能已启用\n";
    echo "      - 保留天数: " . ($backupConfig['retention_days'] ?? 30) . " 天\n";
    echo "      - 数据库备份: " . (($backupConfig['backup_paths']['database'] ?? false) ? '是' : '否') . "\n";
    echo "      - 文件备份: " . (($backupConfig['backup_paths']['uploads'] ?? false) ? '是' : '否') . "\n";
    echo "      - 加密备份: " . (($backupConfig['encrypt_backup'] ?? false) ? '是' : '否') . "\n";
} else {
    echo "   ⚠️  备份功能未启用\n";
    echo "      请在 config/config.php 中启用备份功能\n";
}

echo "\n";

// 3. 验证安全配置
echo "3️⃣  验证安全配置...\n";
$securityConfig = Config::get('security', []);

if (!empty($securityConfig)) {
    echo "   ✅ 安全配置已加载\n";
    echo "      - 爆破防护: " . (($securityConfig['login_max_attempts'] ?? 0) > 0 ? '是' : '否') . "\n";
    if (($securityConfig['login_max_attempts'] ?? 0) > 0) {
        echo "        最多失败次数: " . $securityConfig['login_max_attempts'] . "\n";
        echo "        锁定时长: " . ($securityConfig['login_lockout_minutes'] ?? 15) . " 分钟\n";
    }
    echo "      - IP 黑名单: " . (($securityConfig['enable_ip_blocking'] ?? false) ? '是' : '否') . "\n";
} else {
    echo "   ⚠️  安全配置未加载\n";
    echo "      请在 config/config.php 中配置安全选项\n";
}

echo "\n";

// 4. 创建备份目录
echo "4️⃣  检查备份目录...\n";
$backupDir = Backup::getBackupDir();

if (is_dir($backupDir) && is_writable($backupDir)) {
    echo "   ✅ 备份目录已就绪: {$backupDir}\n";
} else {
    echo "   ❌ 备份目录不可用: {$backupDir}\n";
    echo "      请确保目录存在且有写权限\n";
}

echo "\n";

// 5. 显示建议
$scriptPath = realpath(__FILE__);
echo "5️⃣  建议的后续步骤:\n";
echo "   1. 在 config/config.php 中配置安全选项\n";
echo "   2. 设置定时任务自动备份:\n";
echo "      0 2 * * * /usr/bin/php {$scriptPath} >> /var/log/backup.log 2>&1\n";
echo "   3. 定期检查备份日志\n";
echo "   4. 测试数据恢复流程\n";

echo "\n";
echo "========================================\n";
echo "  初始化完成！\n";
echo "========================================\n";
