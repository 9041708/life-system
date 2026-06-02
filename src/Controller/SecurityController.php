<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;
use App\Service\Security;

class SecurityController
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

    /**
     * 安全监控主页
     */
    public function index(): void
    {
        $this->requireLogin();
        $_SESSION['current_page_title'] = '安全监控';

        $pdo = Database::getConnection();

        // ========== 顶部统计卡片数据 ==========
        $hours24 = time() - 86400;

        // 1. 24小时登录总量
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE attempt_time >= ?");
        $stmt->execute([$hours24]);
        $todayTotal = (int)$stmt->fetchColumn();

        // 2. 24小时独立IP数
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) FROM login_attempts WHERE attempt_time >= ?");
        $stmt->execute([$hours24]);
        $uniqueIps = (int)$stmt->fetchColumn();

        // 3. 24小时失败尝试次数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE attempt_time >= ? AND success = 0");
        $stmt->execute([$hours24]);
        $todayFail = (int)$stmt->fetchColumn();

        // 4. 24小时成功登录次数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE attempt_time >= ? AND success = 1");
        $stmt->execute([$hours24]);
        $todaySuccess = (int)$stmt->fetchColumn();

        // 5. 当前被锁定账户数
        $lockedAccounts = Security::getLockedAccounts();
        $lockedCount = count($lockedAccounts);

        // 6. 总日志记录数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts");
        $stmt->execute();
        $totalLogs = (int)$stmt->fetchColumn();

        // 黑白名单计数
        $blacklistCount = Security::getBlacklistCount();
        $whitelistCount = Security::getWhitelistCount();

        // ========== 近7天趋势数据 ==========
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = strtotime("-$i days midnight");
            $dayEnd = strtotime("-$i days tomorrow") - 1;

            $stmt = $pdo->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT ip_address) as unique_ips FROM login_attempts WHERE attempt_time BETWEEN ? AND ?");
            $stmt->execute([$dayStart, $dayEnd]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $trendData[] = [
                'date' => date('m-d', $dayStart),
                'total' => (int)($row['total'] ?? 0),
                'unique_ips' => (int)($row['unique_ips'] ?? 0),
            ];
        }

        // ========== 活跃IP列表（24小时内）==========
        $stmt = $pdo->prepare("
            SELECT 
                ip_address,
                COUNT(*) as request_count,
                MAX(attempt_time) as last_attempt,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as fail_count,
                GROUP_CONCAT(DISTINCT account SEPARATOR ', ') as accounts
            FROM login_attempts 
            WHERE attempt_time >= ?
            GROUP BY ip_address
            ORDER BY request_count DESC
            LIMIT 20
        ");
        $stmt->execute([$hours24]);
        $activeIps = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 高风险IP（失败>=3）
        $riskyIps = array_values(array_filter($activeIps, fn($ip) => ($ip['fail_count'] ?? 0) >= 3));

        // ========== 最近登录记录 ==========
        $stmt = $pdo->prepare("SELECT * FROM login_attempts ORDER BY attempt_time DESC LIMIT 50");
        $stmt->execute();
        $recentLogs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // ========== 黑白名单数据 ==========
        $blacklist = Security::getBlacklist(50);
        $whitelist = Security::getWhitelist(50);

        // ========== 策略配置 ==========
        $policies = Security::getAllPolicies();

        // 地区代码 → 中文名称映射（传给模板做多选）
        $allRegions = [
            'CN' => '中国', 'HK' => '香港', 'MO' => '澳门', 'TW' => '台湾',
            'US' => '美国', 'JP' => '日本', 'KR' => '韩国', 'RU' => '俄罗斯',
            'GB' => '英国', 'DE' => '德国', 'FR' => '法国', 'SG' => '新加坡',
            'AU' => '澳大利亚', 'CA' => '加拿大', 'IN' => '印度', 'BR' => '巴西',
            'NZ' => '新西兰', 'IE' => '爱尔兰', 'NL' => '荷兰', 'IT' => '意大利',
            'ES' => '西班牙', 'PT' => '葡萄牙', 'AT' => '奥地利', 'CH' => '瑞士',
            'SE' => '瑞典', 'NO' => '挪威', 'DK' => '丹麦', 'FI' => '芬兰',
            'PL' => '波兰', 'CZ' => '捷克', 'TH' => '泰国', 'VN' => '越南',
            'MY' => '马来西亚', 'ID' => '印度尼西亚', 'PH' => '菲律宾',
            'AE' => '阿联酋', 'SA' => '沙特阿拉伯', 'KR' => '韩国',
        ];
        $allowedCodes = explode(',', $policies['geo_allowed_regions'] ?? 'CN,HK,MO,TW');
        $allowedNames = [];
        foreach ($allowedCodes as $code) {
            $code = trim($code);
            if ($code !== '') {
                $allowedNames[] = $allRegions[$code] ?? $code;
            }
        }
        $geoAllowedRegionsDisplay = implode('、', $allowedNames);

        $this->render('security/index', [
            // 统计卡片
            'todayTotal'     => $todayTotal,
            'uniqueIps'      => $uniqueIps,
            'todayFail'      => $todayFail,
            'todaySuccess'   => $todaySuccess,
            'lockedCount'    => $lockedCount,
            'totalLogs'      => $totalLogs,
            'blacklistCount' => $blacklistCount,
            'whitelistCount' => $whitelistCount,

            // 趋势图
            'trendData'      => $trendData,

            // IP列表
            'activeIps'      => $activeIps,
            'riskyIps'       => $riskyIps,

            // 黑白名单
            'blacklist'      => $blacklist,
            'whitelist'      => $whitelist,

            // 策略
            'policies'                 => $policies,
            'geo_allowed_regions_display' => $geoAllowedRegionsDisplay,
            'all_regions'               => $allRegions,

            // 其他
            'recentLogs'     => $recentLogs,
            'lockedAccounts' => $lockedAccounts,
        ]);
    }

    // ==================== 账户操作 ====================

    public function unlock(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $account = trim((string)($_POST['account'] ?? ''));
        if ($account === '') {
            $this->json(['success' => false, 'error' => '账户不能为空']);
        }
        $this->json(Security::unlockAccount($account));
    }

    // ==================== 黑名单操作 ====================

    /**
     * 添加黑名单
     */
    public function addBlacklist(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $ip = trim((string)($_POST['ip'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        $duration = (int)($_POST['duration_minutes'] ?? 0);

        if ($ip === '') {
            $this->json(['success' => false, 'error' => 'IP 地址不能为空']);
        }

        $durationSeconds = $duration > 0 ? $duration * 60 : 0;
        $this->json(Security::addToBlacklist($ip, $reason, $durationSeconds, 'admin'));
    }

    /**
     * 移除黑名单
     */
    public function removeBlacklist(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $ip = trim((string)($_POST['ip'] ?? ''));
        if ($ip === '') {
            $this->json(['success' => false, 'error' => 'IP 地址不能为空']);
        }
        $this->json(Security::removeFromBlacklist($ip));
    }

    /**
     * 批量移除黑名单
     */
    public function batchRemoveBlacklist(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $ips = $_POST['ips'] ?? [];
        if (!is_array($ips) || empty($ips)) {
            $this->json(['success' => false, 'error' => '请选择要移除的 IP']);
        }
        $this->json(Security::batchRemoveFromBlacklist($ips));
    }

    // ==================== 白名单操作 ====================

    /**
     * 添加白名单
     */
    public function addWhitelist(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $ip = trim((string)($_POST['ip'] ?? ''));
        $remark = trim((string)($_POST['remark'] ?? ''));

        if ($ip === '') {
            $this->json(['success' => false, 'error' => 'IP 地址不能为空']);
        }
        $this->json(Security::addToWhitelist($ip, $remark, 'admin'));
    }

    /**
     * 移除白名单
     */
    public function removeWhitelist(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $ip = trim((string)($_POST['ip'] ?? ''));
        if ($ip === '') {
            $this->json(['success' => false, 'error' => 'IP 地址不能为空']);
        }
        $this->json(Security::removeFromWhitelist($ip));
    }

    /**
     * 批量移除白名单
     */
    public function batchRemoveWhitelist(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $ips = $_POST['ips'] ?? [];
        if (!is_array($ips) || empty($ips)) {
            $this->json(['success' => false, 'error' => '请选择要移除的 IP']);
        }
        $this->json(Security::batchRemoveFromWhitelist($ips));
    }

    // ==================== 策略配置 ====================

    /**
     * 保存登录安全策略
     */
    public function saveLoginPolicy(): void
    {
        ob_start();
        set_exception_handler(function ($e) {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        });
        register_shutdown_function(function () {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success'=>false, 'error'=>$err['message']], JSON_UNESCAPED_UNICODE);
            }
        });

        if (!$this->isAjax()) { http_response_code(400); ob_clean(); echo json_encode(['success'=>false,'error'=>'not ajax']); exit; }
        $this->requireLogin();

        $lockThreshold = max(1, (int)($_POST['login_lock_threshold'] ?? 5));
        $lockDuration  = max(1, (int)($_POST['login_lock_duration'] ?? 3));
        $banThreshold  = max(1, (int)($_POST['ip_ban_threshold'] ?? 10));
        $banDuration   = max(1, (int)($_POST['ip_ban_duration'] ?? 60));

        $policies = [
            'login_lock_threshold' => (string)$lockThreshold,
            'login_lock_duration'  => (string)$lockDuration,
            'ip_ban_threshold'     => (string)$banThreshold,
            'ip_ban_duration'      => (string)$banDuration,
        ];

        $result = Security::setPolicies($policies, 'admin');
        ob_clean();
        $this->json($result);
    }

    /**
     * 保存地区访问策略
     */
    public function saveGeoPolicy(): void
    {
        // 捕获所有错误，确保返回 JSON 而非 HTML
        ob_start();
        set_exception_handler(function ($e) {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success'=>false, 'error'=>$e->getMessage(), 'file'=>basename($e->getFile()), 'line'=>$e->getLine()], JSON_UNESCAPED_UNICODE);
            exit;
        });
        register_shutdown_function(function () {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success'=>false, 'error'=>$err['message'], 'file'=>basename($err['file']), 'line'=>$err['line']], JSON_UNESCAPED_UNICODE);
            }
        });

        if (!$this->isAjax()) { http_response_code(400); ob_clean(); echo json_encode(['success'=>false,'error'=>'not ajax']); exit; }
        $this->requireLogin();

        $geoMode           = trim((string)($_POST['geo_mode'] ?? 'off'));
        $geoAllowedRegions = trim((string)($_POST['geo_allowed_regions'] ?? ''));
        $autoBlockDuration = trim((string)($_POST['auto_block_duration'] ?? 'permanent'));
        $autoBlockMinutes  = (int)($_POST['auto_block_minutes'] ?? 1440);

        // 验证 geo_mode
        if (!in_array($geoMode, ['off', 'monitor', 'auto_block'])) {
            $geoMode = 'off';
        }

        $policies = [
            'geo_mode'            => $geoMode,
            'geo_allowed_regions' => $geoAllowedRegions,
            'auto_block_duration' => $autoBlockDuration,
            'auto_block_minutes'  => (string)$autoBlockMinutes,
        ];

        $result = Security::setPolicies($policies, 'admin');
        ob_clean();
        $this->json($result);
    }

    // ==================== 日志清理 ====================

    public function clearLogs(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $before = (int)($_POST['before'] ?? 0);
        $pdo = Database::getConnection();

        if ($before > 0) {
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempt_time < ?");
            $stmt->execute([$before]);
            $deleted = $stmt->rowCount();
        } else {
            $stmt = $pdo->prepare("TRUNCATE TABLE login_attempts");
            $stmt->execute();
            $deleted = 'all';
        }

        $this->json(['success' => true, 'deleted' => $deleted]);
    }

    public function updateConfig(): void
    {
        if (!$this->isAjax()) { http_response_code(400); exit; }
        $this->requireLogin();

        $maxAttempts = max(1, (int)($_POST['login_max_attempts'] ?? 5));
        $lockoutMinutes = max(1, (int)($_POST['login_lockout_minutes'] ?? 15));
        $attemptWindow = max(60, (int)($_POST['login_attempt_window'] ?? 300));

        $config = Config::get('security', []);
        $config['login_max_attempts'] = $maxAttempts;
        $config['login_lockout_minutes'] = $lockoutMinutes;
        $config['login_attempt_window'] = $attemptWindow;

        Config::set('security', $config);
        $this->json(['success' => true]);
    }
}
