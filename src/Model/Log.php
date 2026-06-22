<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Log
{
    private static function ensureTableExists(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM logs LIMIT 1');
        } catch (\Throwable $e) {
            $sql = 'CREATE TABLE IF NOT EXISTS logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                username VARCHAR(255) NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            $pdo->exec($sql);
        }
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        self::ensureTableExists();
        $stmt = $pdo->prepare('INSERT INTO logs (user_id, username, ip_address, user_agent, action, details, created_at) VALUES (:user_id, :username, :ip_address, :user_agent, :action, :details, :created_at)');
        $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':username' => $data['username'] ?? null,
            ':ip_address' => $data['ip_address'],
            ':user_agent' => $data['user_agent'],
            ':action' => $data['action'],
            ':details' => $data['details'] ?? null,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function search(array $filters = [], ?int $limit = null, ?int $offset = null): array
    {
        $pdo = Database::getConnection();
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = 'SELECT * FROM logs';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC';

        if ($limit !== null) {
            $lim = max(1, (int)$limit);
            $off = max(0, (int)($offset ?? 0));
            $sql .= ' LIMIT ' . $lim . ' OFFSET ' . $off;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function count(array $filters = []): int
    {
        $pdo = Database::getConnection();
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = 'SELECT COUNT(*) FROM logs';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 统一搜索：UNION 系统日志(logs) + 论坛操作日志(forum_action_logs)
     */
    public static function searchUnified(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $pdo = Database::getConnection();

        $systemWhere = [];
        $forumWhere = [];
        $params = [];
        $paramIndex = 0;

        if (!empty($filters['user_id'])) {
            $systemWhere[] = 'user_id = :p' . $paramIndex;
            $forumWhere[] = 'l.user_id = :p' . $paramIndex;
            $params[':p' . $paramIndex] = (int)$filters['user_id'];
            $paramIndex++;
        }

        if (!empty($filters['date_from'])) {
            $systemWhere[] = 'created_at >= :p' . $paramIndex;
            $forumWhere[] = 'l.created_at >= :p' . $paramIndex;
            $params[':p' . $paramIndex] = $filters['date_from'] . ' 00:00:00';
            $paramIndex++;
        }

        if (!empty($filters['date_to'])) {
            $systemWhere[] = 'created_at <= :p' . $paramIndex;
            $forumWhere[] = 'l.created_at <= :p' . $paramIndex;
            $params[':p' . $paramIndex] = $filters['date_to'] . ' 23:59:59';
            $paramIndex++;
        }

        $action = $filters['action'] ?? '';

        $forumActionMap = [
            '论坛签到' => 'signin',
            '论坛回帖' => 'reply',
            '@提及回复' => 'reply',
            '论坛通知检查' => 'notice',
            '论坛登录' => 'login',
            '论坛错误' => 'error',
        ];

        $systemActions = [
            '登录', '用户注册', '退出登录',
            '创建交易', '更新交易', '删除交易',
            '创建账户', '更新账户', '删除账户',
            '创建分类', '更新分类', '删除分类',
            '创建负债配置', '更新负债配置', '取消负债配置',
            '标记还款', '回退还款',
            '创建报销记录', '标记已报销', '删除报销记录', '更新报销配置',
            '保存简历', '复制简历', '新建简历', '删除简历',
            '新增理财', '取出理财', '删除理财',
            '上传图书', '删除图书', '推送图书-全系统', '推送图书-指定用户', '取消推送',
            '其他操作', '论坛助手操作',
        ];

        $includeSystem = true;
        $includeForum = true;

        if ($action !== '') {
            if (isset($forumActionMap[$action])) {
                $includeSystem = false;
                $forumWhere[] = 'l.action_type = :p' . $paramIndex;
                $params[':p' . $paramIndex] = $forumActionMap[$action];
                $paramIndex++;
            } else {
                $includeForum = false;
                $systemWhere[] = 'action = :p' . $paramIndex;
                $params[':p' . $paramIndex] = $action;
                $paramIndex++;
            }
        }

        $unions = [];

        if ($includeSystem || !$action) {
            $systemSql = "SELECT 'system' AS source, id, user_id, username, action, details, created_at, ip_address, user_agent, NULL AS forum_name, NULL AS result FROM logs";
            if ($systemWhere) {
                $systemSql .= ' WHERE ' . implode(' AND ', $systemWhere);
            }
            $unions[] = "($systemSql)";
        }

        if ($includeForum || !$action) {
            $actionCase = "CASE l.action_type WHEN 'signin' THEN '论坛签到' WHEN 'reply' THEN '论坛回帖' WHEN 'notice' THEN '论坛通知检查' WHEN 'login' THEN '论坛登录' WHEN 'error' THEN '论坛错误' ELSE l.action_type END";

            $forumSql = "SELECT 'forum' AS source, l.id, l.user_id, CONCAT(a.forum_name, ' - ', l.action_type) AS username, {$actionCase} AS action, COALESCE(l.target_info, '') AS details, l.created_at, '' AS ip_address, '' AS user_agent, a.forum_name, l.result FROM forum_action_logs l LEFT JOIN forum_accounts a ON l.account_id = a.id";
            if ($forumWhere) {
                $forumSql .= ' WHERE ' . implode(' AND ', $forumWhere);
            }
            $unions[] = "($forumSql)";
        }

        if (empty($unions)) {
            return [];
        }

        $sql = implode(' UNION ALL ', $unions) . ' ORDER BY created_at DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 统一计数
     */
    public static function countUnified(array $filters = []): int
    {
        $pdo = Database::getConnection();

        $systemWhere = [];
        $forumWhere = [];
        $params = [];
        $paramIndex = 0;

        if (!empty($filters['user_id'])) {
            $systemWhere[] = 'user_id = :p' . $paramIndex;
            $forumWhere[] = 'l.user_id = :p' . $paramIndex;
            $params[':p' . $paramIndex] = (int)$filters['user_id'];
            $paramIndex++;
        }

        if (!empty($filters['date_from'])) {
            $systemWhere[] = 'created_at >= :p' . $paramIndex;
            $forumWhere[] = 'l.created_at >= :p' . $paramIndex;
            $params[':p' . $paramIndex] = $filters['date_from'] . ' 00:00:00';
            $paramIndex++;
        }

        if (!empty($filters['date_to'])) {
            $systemWhere[] = 'created_at <= :p' . $paramIndex;
            $forumWhere[] = 'l.created_at <= :p' . $paramIndex;
            $params[':p' . $paramIndex] = $filters['date_to'] . ' 23:59:59';
            $paramIndex++;
        }

        $action = $filters['action'] ?? '';

        $forumActionMap = [
            '论坛签到' => 'signin',
            '论坛回帖' => 'reply',
            '@提及回复' => 'reply',
            '论坛通知检查' => 'notice',
            '论坛登录' => 'login',
            '论坛错误' => 'error',
        ];

        $includeSystem = true;
        $includeForum = true;

        if ($action !== '') {
            if (isset($forumActionMap[$action])) {
                $includeSystem = false;
                $forumWhere[] = 'l.action_type = :p' . $paramIndex;
                $params[':p' . $paramIndex] = $forumActionMap[$action];
                $paramIndex++;
            } else {
                $includeForum = false;
                $systemWhere[] = 'action = :p' . $paramIndex;
                $params[':p' . $paramIndex] = $action;
                $paramIndex++;
            }
        }

        $totals = 0;

        if ($includeSystem || !$action) {
            $systemSql = 'SELECT COUNT(*) FROM logs';
            if ($systemWhere) {
                $systemSql .= ' WHERE ' . implode(' AND ', $systemWhere);
            }
            try {
                $stmt = $pdo->prepare($systemSql);
                $stmt->execute($params);
                $totals += (int)$stmt->fetchColumn();
            } catch (\Throwable $e) {}
        }

        if ($includeForum || !$action) {
            $forumSql = 'SELECT COUNT(*) FROM forum_action_logs l LEFT JOIN forum_accounts a ON l.account_id = a.id';
            if ($forumWhere) {
                $forumSql .= ' WHERE ' . implode(' AND ', $forumWhere);
            }
            try {
                $stmt = $pdo->prepare($forumSql);
                $stmt->execute($params);
                $totals += (int)$stmt->fetchColumn();
            } catch (\Throwable $e) {}
        }

        return $totals;
    }
}
