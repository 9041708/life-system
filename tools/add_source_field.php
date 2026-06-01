<?php
/**
 * 为 transactions 表添加 source 字段
 * 用于标记记账来源：'manual'（手动）或 'ai'（AI）
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Service\Database;

$pdo = Database::getConnection();

try {
    // 检查 source 字段是否存在
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='transactions' AND COLUMN_NAME='source'");
    $exists = $result->rowCount() > 0;
    
    if (!$exists) {
        // 添加 source 字段
        $pdo->exec("ALTER TABLE transactions ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT 'manual' COMMENT '记账来源: manual 手动, ai AI'");
        echo "✓ 已成功添加 source 字段\n";
    } else {
        echo "✓ source 字段已存在\n";
    }
} catch (\Throwable $e) {
    echo "✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
