<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;

class LifeController
{
    private function requireLogin(): int {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            if ($this->isApiRequest()) {
                $this->json(['ok' => false, 'error' => '请先登录']);
            }
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    private function isApiRequest(): bool {
        return ($_POST['action'] ?? '') !== '';
    }

    private function render(string $view, array $p = []): void {
        extract($p);
        $appName = Config::get('app.name');
        $_SESSION['current_page_title'] = $p['pageTitle'] ?? '我的人生';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $d): void {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($d, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function index(): void {
        $uid = $this->requireLogin();
        $pdo = Database::getConnection();

        // 检查是否有进行中的游戏
        $stmt = $pdo->prepare("SELECT * FROM life_records WHERE user_id = ? AND is_completed = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$uid]);
        $activeRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        // 获取历史记录
        $stmt = $pdo->prepare("SELECT * FROM life_records WHERE user_id = ? AND is_completed = 1 ORDER BY end_time DESC LIMIT 10");
        $stmt->execute([$uid]);
        $history = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // 获取成就列表
        $achievements = $pdo->query("SELECT * FROM life_achievements ORDER BY sort_order")->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // 获取用户已解锁的成就
        $userAchs = [];
        if ($uid > 0) {
            $stmt = $pdo->prepare("SELECT achievement_id FROM life_user_achievements WHERE user_id = ?");
            $stmt->execute([$uid]);
            $userAchs = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        }

        $this->render('entertainment/life', [
            'pageTitle' => '我的人生',
            'activeRecord' => $activeRecord,
            'history' => $history,
            'achievements' => $achievements,
            'userAchs' => $userAchs,
            'pdo' => $pdo,
        ]);
    }

    public function api(): void {
        $uid = $this->requireLogin();
        $action = $_POST['action'] ?? '';
        $pdo = Database::getConnection();

        try {
            switch ($action) {
                case 'start':
                    $this->apiStart($uid, $pdo);
                    break;
                case 'get_event':
                    $this->apiGetEvent($uid, $pdo);
                    break;
                case 'choose':
                    $this->apiChoose($uid, $pdo);
                    break;
                case 'get_status':
                    $this->apiGetStatus($uid, $pdo);
                    break;
                case 'get_all_achievements':
                    $this->apiGetAllAchievements($uid, $pdo);
                    break;
                case 'end':
                    $this->apiEnd($uid, $pdo);
                    break;
                case 'get_history':
                    $this->apiGetHistory($uid, $pdo);
                    break;
                case 'admin_save_event':
                    $this->requireAdmin();
                    $this->adminSaveEvent($pdo);
                    break;
                case 'admin_delete_event':
                    $this->requireAdmin();
                    $this->adminDeleteEvent($pdo);
                    break;
                case 'admin_save_achievement':
                    $this->requireAdmin();
                    $this->adminSaveAchievement($pdo);
                    break;
                case 'admin_delete_achievement':
                    $this->requireAdmin();
                    $this->adminDeleteAchievement($pdo);
                    break;
                case 'admin_fill_events':
                    $this->requireAdmin();
                    $this->adminFillEvents($pdo);
                    break;
                case 'admin_save_config':
                    $this->requireAdmin();
                    $this->adminSaveConfig($pdo);
                    break;
                default:
                    $this->json(['ok' => false, 'error' => '未知操作']);
            }
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage() . ' [file:' . $e->getFile() . ':' . $e->getLine() . ']']);
        }
    }

    public function admin(): void {
        $this->requireLogin();
        $this->requireAdmin();

        $pdo = Database::getConnection();
        $events = $pdo->query("SELECT * FROM life_events ORDER BY age_min, sort_order")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $achievements = $pdo->query("SELECT * FROM life_achievements ORDER BY sort_order")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        
        // 修复：config 查询使用 FETCH_KEY_PAIR 的正确方式
        $configStmt = $pdo->query("SELECT config_key, config_value FROM life_config");
        $configs = $configStmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

        $this->render('entertainment/life_admin', [
            'pageTitle' => '我的人生 - 管理',
            'events' => $events,
            'achievements' => $achievements,
            'configs' => $configs,
        ]);
    }

    // ==================== API 方法 ====================

    private function apiStart(int $uid, \PDO $pdo): void {
        $family = $_POST['family'] ?? '随机生成';
        $gender = $_POST['gender'] ?? 'male';
        if (!in_array($gender, ['male', 'female'])) $gender = 'male';

        // 检查是否已有进行中的游戏
        $stmt = $pdo->prepare("SELECT id FROM life_records WHERE user_id = ? AND is_completed = 0");
        $stmt->execute([$uid]);
        if ($stmt->fetch()) {
            $this->json(['ok' => false, 'error' => '你有一个进行中的人生，请先结束或继续']);
        }

        // 根据原生家庭计算初始属性
        $initRange = $this->getInitRanges($pdo);
        $iq = $this->randFromRange($initRange['initial_iq_range']);
        $eq = $this->randFromRange($initRange['initial_eq_range']);
        $health = $this->randFromRange($initRange['initial_health_range']);
        $wealth = $this->randFromRange($initRange['initial_wealth_range']);
        $looks = $this->randFromRange($initRange['initial_looks_range']);
        $luck = $this->randFromRange($initRange['initial_luck_range']);

        // 根据原生家庭调整
        $adjustments = $this->getFamilyAdjustments($family);
        $iq += $adjustments['iq'];
        $eq += $adjustments['eq'];
        $health += $adjustments['health'];
        $wealth += $adjustments['wealth'];
        $looks += $adjustments['looks'];
        $luck += $adjustments['luck'];

        // 确保在 0-100 范围内
        $iq = max(0, min(100, $iq));
        $eq = max(0, min(100, $eq));
        $health = max(0, min(100, $health));
        $wealth = max(0, min(100, $wealth));
        $looks = max(0, min(100, $looks));
        $luck = max(0, min(100, $luck));

        // 创建记录
        $stmt = $pdo->prepare("INSERT INTO life_records (user_id, family_background, gender, start_time, initial_iq, initial_eq, initial_health, initial_wealth, initial_looks, initial_luck) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $family, $gender, $iq, $eq, $health, $wealth, $looks, $luck]);
        $recordId = $pdo->lastInsertId();

        // 获取第一个事件
        $event = $this->getEventForAge($pdo, 0, $iq, $eq, $health, $wealth, $looks, $luck, $family, $gender);

        $this->json([
            'ok' => true,
            'record_id' => $recordId,
            'age' => 0,
            'gender' => $gender,
            'attrs' => [
                'iq' => $iq, 'eq' => $eq, 'health' => $health,
                'wealth' => $wealth, 'looks' => $looks, 'luck' => $luck
            ],
            'event' => $event,
        ]);
    }

    private function apiGetEvent(int $uid, \PDO $pdo): void {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $age = (int)($_POST['age'] ?? 0);

        // 验证记录属于当前用户
        $stmt = $pdo->prepare("SELECT * FROM life_records WHERE id = ? AND user_id = ? AND is_completed = 0");
        $stmt->execute([$recordId, $uid]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) {
            $this->json(['ok' => false, 'error' => '游戏记录不存在或已完成']);
        }

        // 从 life_log 恢复当前属性
        $log = json_decode($record['life_log'] ?: '[]', true) ?: [];
        $attrs = [
            'iq' => $record['initial_iq'], 'eq' => $record['initial_eq'],
            'health' => $record['initial_health'], 'wealth' => $record['initial_wealth'],
            'looks' => $record['initial_looks'], 'luck' => $record['initial_luck']
        ];
        // 应用所有历史选择的效果
        foreach ($log as $entry) {
            if (isset($entry['effects'])) {
                foreach ($entry['effects'] as $k => $v) {
                    if (isset($attrs[$k])) $attrs[$k] += $v;
                }
            }
        }

        $usedIds = array_filter(array_column($log, 'event_id'));
        $event = $this->getEventForAge($pdo, $age, $attrs['iq'], $attrs['eq'], $attrs['health'], $attrs['wealth'], $attrs['looks'], $attrs['luck'], $record['family_background'], $record['gender'] ?? 'male', $usedIds);

        $this->json([
            'ok' => true,
            'age' => $age,
            'attrs' => $attrs,
            'event' => $event,
        ]);
    }

    private function apiChoose(int $uid, \PDO $pdo): void {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $eventId = (int)($_POST['event_id'] ?? 0);
        $choiceIdx = (int)($_POST['choice_idx'] ?? 0);

        // 验证记录
        $stmt = $pdo->prepare("SELECT * FROM life_records WHERE id = ? AND user_id = ? AND is_completed = 0");
        $stmt->execute([$recordId, $uid]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) {
            $this->json(['ok' => false, 'error' => '游戏记录不存在或已完成']);
        }

        // 计算当前属性（从初始值 + 历史日志恢复）
        $log = json_decode($record['life_log'] ?: '[]', true) ?: [];
        $attrs = [
            'iq' => $record['initial_iq'], 'eq' => $record['initial_eq'],
            'health' => $record['initial_health'], 'wealth' => $record['initial_wealth'],
            'looks' => $record['initial_looks'], 'luck' => $record['initial_luck']
        ];
        foreach ($log as $entry) {
            if (isset($entry['effects'])) {
                foreach ($entry['effects'] as $k => $v) {
                    if (isset($attrs[$k])) $attrs[$k] += $v;
                }
            }
        }

        // event_id=0 表示默认/平凡事件，直接处理效果无需查库
        $choice = null;
        $eventTitle = '平凡的一年';
        if ($eventId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM life_events WHERE id = ? AND is_active = 1");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$event) {
                $this->json(['ok' => false, 'error' => '事件不存在']);
            }
            $choices = json_decode($event['choices'], true);
            if (!isset($choices[$choiceIdx])) {
                $this->json(['ok' => false, 'error' => '选项不存在']);
            }
            $choice = $choices[$choiceIdx];
            $eventTitle = $event['title'];
        } else {
            // 默认事件：无效果，继续生活
            $choice = ['text' => '继续生活', 'effects' => []];
        }

        // 计算新属性
        $log = json_decode($record['life_log'] ?: '[]', true) ?: [];
        $attrs = [
            'iq' => $record['initial_iq'], 'eq' => $record['initial_eq'],
            'health' => $record['initial_health'], 'wealth' => $record['initial_wealth'],
            'looks' => $record['initial_looks'], 'luck' => $record['initial_luck']
        ];
        foreach ($log as $entry) {
            if (isset($entry['effects'])) {
                foreach ($entry['effects'] as $k => $v) {
                    if (isset($attrs[$k])) $attrs[$k] += $v;
                }
            }
        }

        // 应用当前选择的效果
        $effects = $choice['effects'] ?: [];
        $isDeath = !empty($choice['die']); // 选项标记死亡
        $effectsApplied = [];
        foreach ($effects as $k => $v) {
            if (isset($attrs[$k])) {
                $attrs[$k] += $v;
                $attrs[$k] = max(0, min(100, $attrs[$k]));
                $effectsApplied[$k] = $v;
            }
        }

        // 记录日志
        $currentAge = count($log);
        $logEntry = [
            'age' => $currentAge,
            'event_id' => $eventId,
            'event_title' => $eventTitle,
            'choice' => $choice['text'],
            'effects' => $effectsApplied,
            'is_death' => $isDeath,
            'snapshot' => $attrs,
        ];
        $log[] = $logEntry;

        // 更新记录
        $nextAge = $currentAge + 1;
        $maxAge = (int)($pdo->query("SELECT config_value FROM life_config WHERE config_key = 'max_age'")->fetchColumn() ?: 100);

        // 检查是否死亡或达到最大年龄
        $isDead = $isDeath || $attrs['health'] <= 0;
        $isMaxAge = $nextAge >= $maxAge;

        if ($isDead || $isMaxAge) {
            // Cap 属性到合法范围再写入数据库
            foreach ($attrs as &$v) { $v = max(-128, min(127, (int)$v)); }
            unset($v);
            // 结束游戏
            $stmt = $pdo->prepare("UPDATE life_records SET end_time = NOW(), is_completed = 1, final_age = ?, final_iq = ?, final_eq = ?, final_health = ?, final_wealth = ?, final_looks = ?, final_luck = ?, life_log = ? WHERE id = ?");
            $stmt->execute([$currentAge, $attrs['iq'], $attrs['eq'], $attrs['health'], $attrs['wealth'], $attrs['looks'], $attrs['luck'], json_encode($log, JSON_UNESCAPED_UNICODE), $recordId]);

            // 计算成就
            $achievements = $this->checkAchievements($pdo, $uid, $recordId, $attrs, $currentAge, $log);

            $this->json([
                'ok' => true,
                'finished' => true,
                'age' => $currentAge,
                'attrs' => $attrs,
                'achievements' => $achievements,
                'summary' => $this->generateSummary($log, $attrs, $currentAge),
            ]);
        } else {
            // 继续游戏
            $stmt = $pdo->prepare("UPDATE life_records SET life_log = ? WHERE id = ?");
            $stmt->execute([json_encode($log, JSON_UNESCAPED_UNICODE), $recordId]);

            // 获取下一个事件（排除已触发的）
            $usedIds = array_filter(array_column($log, 'event_id'));
            $nextEvent = $this->getEventForAge($pdo, $nextAge, $attrs['iq'], $attrs['eq'], $attrs['health'], $attrs['wealth'], $attrs['looks'], $attrs['luck'], $record['family_background'], $record['gender'] ?? 'male', $usedIds);

            $this->json([
                'ok' => true,
                'finished' => false,
                'age' => $nextAge,
                'attrs' => $attrs,
                'event' => $nextEvent,
            ]);
        }
    }

    private function apiGetStatus(int $uid, \PDO $pdo): void {
        $recordId = (int)($_POST['record_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT * FROM life_records WHERE id = ? AND user_id = ? AND is_completed = 0");
        $stmt->execute([$recordId, $uid]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) {
            $this->json(['ok' => false, 'error' => '游戏记录不存在或已完成']);
        }

        $log = json_decode($record['life_log'] ?: '[]', true) ?: [];
        $attrs = [
            'iq' => $record['initial_iq'], 'eq' => $record['initial_eq'],
            'health' => $record['initial_health'], 'wealth' => $record['initial_wealth'],
            'looks' => $record['initial_looks'], 'luck' => $record['initial_luck']
        ];
        foreach ($log as $entry) {
            if (isset($entry['effects'])) {
                foreach ($entry['effects'] as $k => $v) {
                    if (isset($attrs[$k])) $attrs[$k] += $v;
                }
            }
        }

        $currentAge = count($log);
        $usedIds = array_filter(array_column($log, 'event_id'));
        $event = $this->getEventForAge($pdo, $currentAge, $attrs['iq'], $attrs['eq'], $attrs['health'], $attrs['wealth'], $attrs['looks'], $attrs['luck'], $record['family_background'], $record['gender'] ?? 'male', $usedIds);

        $this->json([
            'ok' => true,
            'record_id' => $recordId,
            'age' => $currentAge,
            'attrs' => $attrs,
            'event' => $event,
            'log_count' => count($log),
        ]);
    }

    private function apiGetAllAchievements(int $uid, \PDO $pdo): void {
        $achievements = $pdo->query("SELECT * FROM life_achievements ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC);
        $unlockedIds = $pdo->prepare("SELECT DISTINCT achievement_id FROM life_user_achievements WHERE user_id = ?");
        $unlockedIds->execute([$uid]);
        $unlockedList = $unlockedIds->fetchAll(\PDO::FETCH_COLUMN);
        $counts = $pdo->query("SELECT achievement_id, COUNT(*) as cnt FROM life_user_achievements GROUP BY achievement_id")->fetchAll(\PDO::FETCH_KEY_PAIR);
        foreach ($achievements as &$a) { $a['unlock_count'] = $counts[$a['id']] ?? 0; }
        unset($a);
        $this->json(['ok' => true, 'achievements' => $achievements, 'unlocked_list' => $unlockedList]);
    }

    private function apiEnd(): void {
        $uid = $this->requireLogin();
        $pdo = Database::getConnection();
        $recordId = (int)($_POST['record_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT * FROM life_records WHERE id = ? AND user_id = ? AND is_completed = 0");
        $stmt->execute([$recordId, $uid]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) {
            $this->json(['ok' => false, 'error' => '游戏记录不存在或已完成']);
        }

        $log = json_decode($record['life_log'] ?: '[]', true) ?: [];
        $attrs = [
            'iq' => $record['initial_iq'], 'eq' => $record['initial_eq'],
            'health' => $record['initial_health'], 'wealth' => $record['initial_wealth'],
            'looks' => $record['initial_looks'], 'luck' => $record['initial_luck']
        ];
        foreach ($log as $entry) {
            if (isset($entry['effects'])) {
                foreach ($entry['effects'] as $k => $v) {
                    if (isset($attrs[$k])) $attrs[$k] += $v;
                }
            }
        }

        $currentAge = count($log);
        $stmt = $pdo->prepare("UPDATE life_records SET end_time = NOW(), is_completed = 1, final_age = ?, final_iq = ?, final_eq = ?, final_health = ?, final_wealth = ?, final_looks = ?, final_luck = ? WHERE id = ?");
        $stmt->execute([$currentAge, $attrs['iq'], $attrs['eq'], $attrs['health'], $attrs['wealth'], $attrs['looks'], $attrs['luck'], $recordId]);

        $achievements = $this->checkAchievements($pdo, $uid, $recordId, $attrs, $currentAge, $log);

        $this->json([
            'ok' => true,
            'age' => $currentAge,
            'attrs' => $attrs,
            'achievements' => $achievements,
            'summary' => $this->generateSummary($log, $attrs, $currentAge),
        ]);
    }

    private function apiGetHistory(int $uid, \PDO $pdo): void {
        $stmt = $pdo->prepare("SELECT * FROM life_records WHERE user_id = ? AND is_completed = 1 ORDER BY end_time DESC");
        $stmt->execute([$uid]);
        $history = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $result = [];
        foreach ($history as $h) {
            $result[] = [
                'id' => $h['id'],
                'family_background' => $h['family_background'],
                'start_time' => $h['start_time'],
                'end_time' => $h['end_time'],
                'final_age' => $h['final_age'],
                'final_iq' => $h['final_iq'],
                'final_eq' => $h['final_eq'],
                'final_wealth' => $h['final_wealth'],
                'final_health' => $h['final_health'],
            ];
        }

        $this->json(['ok' => true, 'history' => $result]);
    }

    // ==================== 辅助方法 ====================

    private function getInitRanges(\PDO $pdo): array {
        // 修复：使用正确的 fetchAll() 调用
        $stmt = $pdo->query("SELECT config_key, config_value FROM life_config WHERE config_key LIKE 'initial_%'");
        $configs = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
        $defaults = [
            'initial_iq_range' => '40,60',
            'initial_eq_range' => '40,60',
            'initial_health_range' => '40,100',
            'initial_wealth_range' => '30,70',
            'initial_looks_range' => '30,70',
            'initial_luck_range' => '30,70',
        ];
        return array_merge($defaults, $configs);
    }

    private function randFromRange(string $range): int {
        $parts = explode(',', $range);
        $min = (int)($parts[0] ?? 0);
        $max = (int)($parts[1] ?? 100);
        return rand($min, $max);
    }

    private function getFamilyAdjustments(string $family): array {
        $adjustments = [
            '随机生成' => ['iq' => 0, 'eq' => 0, 'health' => 0, 'wealth' => 0, 'looks' => 0, 'luck' => 0],
            '贫困农村' => ['iq' => -10, 'eq' => 0, 'health' => 10, 'wealth' => -20, 'looks' => -5, 'luck' => 5],
            '普通工薪' => ['iq' => 0, 'eq' => 0, 'health' => 0, 'wealth' => 0, 'looks' => 0, 'luck' => 0],
            '富裕中产' => ['iq' => 5, 'eq' => 5, 'health' => 0, 'wealth' => 20, 'looks' => 5, 'luck' => 0],
            '知识分子' => ['iq' => 15, 'eq' => -5, 'health' => 0, 'wealth' => -10, 'looks' => 0, 'luck' => 0],
            '官宦世家' => ['iq' => 5, 'eq' => 10, 'health' => 0, 'wealth' => 15, 'looks' => 5, 'luck' => 0],
            '富豪家庭' => ['iq' => 5, 'eq' => 5, 'health' => 0, 'wealth' => 30, 'looks' => 10, 'luck' => 5],
            '艺术世家' => ['iq' => 5, 'eq' => 5, 'health' => 0, 'wealth' => 0, 'looks' => 15, 'luck' => 5],
            '单亲家庭' => ['iq' => -5, 'eq' => -5, 'health' => 0, 'wealth' => -10, 'looks' => 0, 'luck' => -5],
            '重组家庭' => ['iq' => -3, 'eq' => -3, 'health' => 0, 'wealth' => -5, 'looks' => 0, 'luck' => 0],
        ];
        return $adjustments[$family] ?? $adjustments['随机生成'];
    }

    private function getEventForAge(\PDO $pdo, int $age, int $iq, int $eq, int $health, int $wealth, int $looks, int $luck, string $family, string $gender = 'male', array $usedEventIds = []): ?array {
        $excludedIds = array_unique($usedEventIds);

        // 构建排除条件
        $excludeSql = '';
        $excludeParams = [];
        if (!empty($excludedIds)) {
            $placeholders = implode(',', array_fill(0, count($excludedIds), '?'));
            $excludeSql = " AND id NOT IN ($placeholders)";
            $excludeParams = array_values($excludedIds);
        }

        // 两轮查询：先找属性完全匹配的，找不到时放宽条件（模拟人生不确定性）
        $sql = "SELECT * FROM life_events 
                WHERE is_active = 1 
                AND age_min <= ? AND age_max >= ?
                AND (gender = 'all' OR gender = ?)
                AND iq_min <= ? AND iq_max >= ?
                AND eq_min <= ? AND eq_max >= ?
                AND health_min <= ? AND health_max >= ?
                AND wealth_min <= ? AND wealth_max >= ?
                AND looks_min <= ? AND looks_max >= ?
                AND luck_min <= ? AND luck_max >= ?
                AND (family_background = '' OR family_background = ? OR family_background IS NULL)
                $excludeSql
                ORDER BY RAND() LIMIT 1";
        $params = [
            $age, $age,
            $gender,
            $iq, $iq, $eq, $eq, $health, $health,
            $wealth, $wealth, $looks, $looks, $luck, $luck,
            $family
        ];
        $params = array_merge($params, $excludeParams);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $event = $stmt->fetch(\PDO::FETCH_ASSOC);

        // 没找到精确匹配时，放宽到年龄+性别（随机波折）
        if (!$event) {
            $sql2 = "SELECT * FROM life_events 
                    WHERE is_active = 1 
                    AND age_min <= ? AND age_max >= ?
                    AND (gender = 'all' OR gender = ?)
                    AND (family_background = '' OR family_background = ? OR family_background IS NULL)
                    $excludeSql
                    ORDER BY RAND() LIMIT 1";
            $params2 = [$age, $age, $gender, $family];
            $params2 = array_merge($params2, $excludeParams);
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute($params2);
            $event = $stmt2->fetch(\PDO::FETCH_ASSOC);
        }

        // 仍然没有，返回通用事件（id=0，可重复）
        if (!$event) {
            $defaultChoices = [['text' => '继续生活', 'effects' => []]];
            return [
                'id' => 0,
                'title' => '平凡的一年',
                'description' => '这一年，你平平淡淡地过着日子，没有什么特别的事情发生。',
                'choices' => json_encode($defaultChoices),
            ];
        }
        return $event;
    }

    private function checkAchievements(\PDO $pdo, int $uid, int $recordId, array $attrs, int $age, array $log): array {
        $achievements = $pdo->query("SELECT * FROM life_achievements")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $unlocked = [];

        foreach ($achievements as $ach) {
            $condition = json_decode($ach['condition_json'], true);
            if ($this->checkCondition($condition, $attrs, $age, $log)) {
                // 检查是否已解锁
                $stmt = $pdo->prepare("SELECT id FROM life_user_achievements WHERE user_id = ? AND record_id = ? AND achievement_id = ?");
                $stmt->execute([$uid, $recordId, $ach['id']]);
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO life_user_achievements (user_id, record_id, achievement_id) VALUES (?, ?, ?)");
                    $stmt->execute([$uid, $recordId, $ach['id']]);
                    $unlocked[] = $ach;
                }
            }
        }

        return $unlocked;
    }

    private function checkCondition(array $condition, array $attrs, int $age, array $log): bool {
        foreach ($condition as $key => $value) {
            if ($key === 'min') {
                // 通用 min 检查（不常用）
                continue;
            }
            if ($key === 'age') {
                if (isset($value['min']) && $age < $value['min']) return false;
                if (isset($value['max']) && $age > $value['max']) return false;
            } elseif ($key === 'balanced') {
                // 检查属性是否均衡（都在40-60之间）
                $balanced = true;
                foreach (['iq', 'eq', 'health', 'wealth', 'looks', 'luck'] as $attr) {
                    if ($attrs[$attr] < 40 || $attrs[$attr] > 60) {
                        $balanced = false;
                        break;
                    }
                }
                if (!$balanced) return false;
            } elseif ($key === 'unlock_count') {
                // 这个需要在外部计算，这里先跳过
                continue;
            } else {
                // 检查属性
                if (isset($attrs[$key])) {
                    if (isset($value['min']) && $attrs[$key] < $value['min']) return false;
                    if (isset($value['max']) && $attrs[$key] > $value['max']) return false;
                }
            }
        }
        return true;
    }

    private function generateSummary(array $log, array $attrs, int $age): string {
        $summary = "你的一生结束了，活到了 {$age} 岁。\n";
        $summary .= "最终属性：\n";
        $summary .= "智商：{$attrs['iq']}，情商：{$attrs['eq']}，体质：{$attrs['health']}\n";
        $summary .= "财富：{$attrs['wealth']}，颜值：{$attrs['looks']}，运气：{$attrs['luck']}\n";
        return $summary;
    }

    private function requireAdmin(): void {
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: /public/index.php?route=dashboard');
            exit;
        }
    }

    // ==================== 管理后台方法 ====================

    private function adminSaveEvent(\PDO $pdo): void {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'age_min' => (int)($_POST['age_min'] ?? 0),
            'age_max' => (int)($_POST['age_max'] ?? 100),
            'gender' => $_POST['gender'] ?? 'all',
            'iq_min' => (int)($_POST['iq_min'] ?? 0),
            'iq_max' => (int)($_POST['iq_max'] ?? 100),
            'eq_min' => (int)($_POST['eq_min'] ?? 0),
            'eq_max' => (int)($_POST['eq_max'] ?? 100),
            'health_min' => (int)($_POST['health_min'] ?? 0),
            'health_max' => (int)($_POST['health_max'] ?? 100),
            'wealth_min' => (int)($_POST['wealth_min'] ?? 0),
            'wealth_max' => (int)($_POST['wealth_max'] ?? 100),
            'looks_min' => (int)($_POST['looks_min'] ?? 0),
            'looks_max' => (int)($_POST['looks_max'] ?? 100),
            'luck_min' => (int)($_POST['luck_min'] ?? 0),
            'luck_max' => (int)($_POST['luck_max'] ?? 100),
            'family_background' => $_POST['family_background'] ?? '',
            'condition_json' => $_POST['condition_json'] ?? '{}',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'choices' => $_POST['choices'] ?? '[]',
            'is_active' => (int)($_POST['is_active'] ?? 1),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];

        if ($id > 0) {
            $sql = "UPDATE life_events SET age_min=?, age_max=?, gender=?, iq_min=?, iq_max=?, eq_min=?, eq_max=?, health_min=?, health_max=?, wealth_min=?, wealth_max=?, looks_min=?, looks_max=?, luck_min=?, luck_max=?, family_background=?, condition_json=?, title=?, description=?, choices=?, is_active=?, sort_order=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($data), $id]);
        } else {
            $sql = "INSERT INTO life_events (age_min, age_max, gender, iq_min, iq_max, eq_min, eq_max, health_min, health_max, wealth_min, wealth_max, looks_min, looks_max, luck_min, luck_max, family_background, condition_json, title, description, choices, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
        }

        $this->json(['ok' => true, 'message' => '保存成功']);
    }

    private function adminDeleteEvent(\PDO $pdo): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) $this->json(['ok' => false, 'error' => 'ID无效']);

        $stmt = $pdo->prepare("DELETE FROM life_events WHERE id = ?");
        $stmt->execute([$id]);

        $this->json(['ok' => true, 'message' => '删除成功']);
    }

    private function adminSaveAchievement(\PDO $pdo): void {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'condition_json' => $_POST['condition_json'] ?? '{}',
            'icon' => $_POST['icon'] ?? '',
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE life_achievements SET name=?, description=?, condition_json=?, icon=?, sort_order=? WHERE id=?");
            $stmt->execute([...array_values($data), $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO life_achievements (name, description, condition_json, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(array_values($data));
        }

        $this->json(['ok' => true, 'message' => '保存成功']);
    }

    private function adminDeleteAchievement(\PDO $pdo): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) $this->json(['ok' => false, 'error' => 'ID无效']);

        $stmt = $pdo->prepare("DELETE FROM life_achievements WHERE id = ?");
        $stmt->execute([$id]);

        $this->json(['ok' => true, 'message' => '删除成功']);
    }

    private function adminSaveConfig(\PDO $pdo): void {
        $key = $_POST['config_key'] ?? '';
        $value = $_POST['config_value'] ?? '';
        if (empty($key)) $this->json(['ok' => false, 'error' => '键名不能为空']);

        $stmt = $pdo->prepare("INSERT INTO life_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?");
        $stmt->execute([$key, $value, $value]);

        $this->json(['ok' => true, 'message' => '保存成功']);
    }

    private function adminFillEvents(\PDO $pdo): void {
        $events = [];

        // 4~6岁
        $events[] = [4,5,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','语言爆发期','你的语言能力突飞猛进，开始问十万个为什么。',
            '[{"text":"好奇多问","effects":{"iq":3,"eq":2}},{"text":"安静观察","effects":{"iq":1}},{"text":"缠着大人讲故事","effects":{"iq":2,"eq":3}}]',1,4];
        $events[] = [5,6,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','幼儿园毕业','你要从幼儿园毕业了，即将成为小学生。',
            '[{"text":"期待上小学","effects":{"eq":3,"iq":1}},{"text":"舍不得幼儿园","effects":{"eq":-1}}]',1,5];

        // 12~14岁
        $events[] = [12,13,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','小升初','小学即将结束，面临小升初的选择。',
            '[{"text":"努力考好初中","effects":{"iq":4,"health":-1}},{"text":"就近入学","effects":{"health":2}},{"text":"特长生招生","effects":{"iq":2,"looks":1}}]',1,16];
        $events[] = [13,14,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','青春期的烦恼','你开始注意到自己的身体变化，心情也变得不稳定。',
            '[{"text":"和父母沟通","effects":{"eq":3}},{"text":"闷在心里","effects":{"eq":-2,"health":-1}},{"text":"和朋友倾诉","effects":{"eq":2}}]',1,17];

        // 15~17岁
        $events[] = [14,15,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','初二分水岭','初二是成绩的分水岭，课程难度明显提升。',
            '[{"text":"迎难而上","effects":{"iq":5,"health":-2}},{"text":"保持现状","effects":{"iq":1}},{"text":"开始厌学","effects":{"iq":-3,"health":1}}]',1,18];
        $events[] = [15,17,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','叛逆期高峰','你和父母的矛盾达到了顶峰，经常吵架。',
            '[{"text":"冷战对抗","effects":{"eq":-3,"health":-1}},{"text":"尝试理解父母","effects":{"eq":5,"iq":2}},{"text":"找朋友发泄","effects":{"eq":2,"luck":1}}]',1,19];

        // 20~24岁
        $events[] = [20,21,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','社团/学生会','你在考虑要不要参加社团或学生会。',
            '[{"text":"加入学生会","effects":{"eq":5,"iq":2}},{"text":"加兴趣社团","effects":{"eq":3,"looks":1}},{"text":"专注学习不参加","effects":{"iq":4}}]',1,25];
        $events[] = [23,24,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','实习机会','你获得了一个实习机会。',
            '[{"text":"去大厂实习","effects":{"wealth":5,"iq":3,"eq":2}},{"text":"去创业公司","effects":{"wealth":2,"iq":4,"luck":-2}},{"text":"不实习继续学习","effects":{"iq":3}}]',1,33];

        $stmt = $pdo->prepare('INSERT IGNORE INTO life_events (age_min,age_max,gender,iq_min,iq_max,eq_min,eq_max,health_min,health_max,wealth_min,wealth_max,looks_min,looks_max,luck_min,luck_max,family_background,condition_json,title,description,choices,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

        $cnt = 0;
        foreach ($events as $e) {
            try {
                $stmt->execute($e);
                $cnt++;
            } catch (\Throwable $ex) {
                // skip duplicates
            }
        }

        $total = $pdo->query("SELECT COUNT(*) FROM life_events WHERE is_active=1")->fetchColumn();
        $this->json(['ok' => true, 'message' => "补充了 {$cnt} 个新事件，当前共 {$total} 个活跃事件"]);
    }
}
