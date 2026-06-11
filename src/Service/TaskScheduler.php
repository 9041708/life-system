<?php
namespace App\Service;

/**
 * 任务接口 - 所有定时任务必须实现此接口
 */
interface TaskInterface
{
    /**
     * 获取任务名称
     */
    public function getName(): string;

    /**
     * 获取任务描述
     */
    public function getDescription(): string;

    /**
     * 获取任务执行间隔（秒）
     */
    public function getInterval(): int;

    /**
     * 执行任务
     */
    public function execute(): array;

    /**
     * 获取任务类型（backup, cleanup, maintenance, etc）
     */
    public function getType(): string;
}

/**
 * 任务执行记录
 */
class TaskExecution
{
    public $taskName;
    public $startTime;
    public $endTime;
    public $success;
    public $result;
    public $error;

    public function __construct(string $taskName, int $startTime, int $endTime, bool $success, string $result, ?string $error = null)
    {
        $this->taskName = $taskName;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->success = $success;
        $this->result = $result;
        $this->error = $error;
    }

    public function toArray(): array
    {
        return [
            'task_name' => $this->taskName,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'duration' => $this->endTime - $this->startTime,
            'success' => $this->success,
            'result' => $this->result,
            'error' => $this->error,
            'formatted_start_time' => date('Y-m-d H:i:s', $this->startTime),
            'formatted_end_time' => date('Y-m-d H:i:s', $this->endTime),
        ];
    }
}

/**
 * 内置定时任务调度器
 * 不依赖外部 cron，直接基于系统时间调度任务
 */
class TaskScheduler
{
    private static $instance = null;
    private $tasks = [];
    private $lastExecutions = [];
    private $logFile;
    private $statusFile;
    private $isRunning = false;
    private $checkInterval = 60; // 每60秒检查一次任务

    private function __construct()
    {
        $this->logFile = dirname(__DIR__, 2) . '/logs/task_scheduler.log';
        $this->statusFile = dirname(__DIR__, 2) . '/logs/task_scheduler.status';
        $this->ensureLogDirectory();
        $this->loadExecutionHistory();
        $this->registerDefaultTasks();
    }

    public static function getInstance(): TaskScheduler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 注册任务
     */
    public function registerTask(TaskInterface $task, bool $log = true): void
    {
        $this->tasks[$task->getName()] = $task;
        if ($log) {
            $this->log("注册任务: {$task->getName()} - {$task->getDescription()}");
        }
    }

    /**
     * 启动调度器
     */
    public function start(): void
    {
        if ($this->isRunning) {
            $this->log("调度器已在运行中");
            return;
        }

        $this->isRunning = true;
        $this->log("定时任务调度器启动");
        $this->saveStatusFile($this->buildStatusData(true));

        // 启动调度循环
        $this->scheduleLoop();
    }

    /**
     * 停止调度器
     */
    public function stop(): void
    {
        $this->isRunning = false;
        $this->log("定时任务调度器停止");
        $this->saveStatusFile($this->buildStatusData(false));
    }

    /**
     * 手动执行任务
     */
    public function executeTask(string $taskName): array
    {
        if (!isset($this->tasks[$taskName])) {
            return [
                'success' => false,
                'error' => "任务 '{$taskName}' 未找到",
            ];
        }

        $task = $this->tasks[$taskName];
        $startTime = time();

        try {
            $this->log("手动执行任务: {$taskName}");
            $result = $task->execute();

            $endTime = time();
            $execution = new TaskExecution($taskName, $startTime, $endTime, true, json_encode($result, JSON_UNESCAPED_UNICODE));
            $this->recordExecution($execution);

            return [
                'success' => true,
                'task' => $taskName,
                'result' => $result,
                'duration' => $endTime - $startTime,
            ];
        } catch (\Throwable $e) {
            $endTime = time();
            $execution = new TaskExecution($taskName, $startTime, $endTime, false, '', $e->getMessage());
            $this->recordExecution($execution);

            $this->log("任务执行失败: {$taskName} - " . $e->getMessage());
            return [
                'success' => false,
                'task' => $taskName,
                'error' => $e->getMessage(),
                'duration' => $endTime - $startTime,
            ];
        }
    }

    /**
     * 获取所有任务状态
     */
    public function getTaskStatus(): array
    {
        $status = [];
        foreach ($this->tasks as $taskName => $task) {
            $lastExecution = $this->lastExecutions[$taskName] ?? null;
            $nextExecution = $this->getNextExecutionTime($taskName);

            $status[$taskName] = [
                'name' => $task->getName(),
                'description' => $task->getDescription(),
                'type' => $task->getType(),
                'interval' => $task->getInterval(),
                'last_execution' => $lastExecution ? $lastExecution->toArray() : null,
                'next_execution' => $nextExecution,
                'formatted_next_execution' => date('Y-m-d H:i:s', $nextExecution),
                'is_due' => time() >= $nextExecution,
            ];
        }
        return $status;
    }

    /**
     * 获取任务执行历史
     */
    public function getExecutionHistory(int $limit = 50): array
    {
        $history = [];
        if (file_exists($this->logFile)) {
            $lines = $this->tailFile($this->logFile, $limit);
            foreach ($lines as $line) {
                $line = trim($line);
                // 去掉 writeLog 添加的时间戳前缀: [2026-05-30 03:07:47]
                if (preg_match('/^\[.+?\]\s*(.*)$/s', $line, $m)) {
                    $line = $m[1];
                }
                $data = json_decode($line, true);
                if ($data && isset($data['type']) && $data['type'] === 'execution') {
                    $history[] = $data;
                }
            }
        }
        return array_reverse($history);
    }

    /**
     * 注册默认任务
     */
    private function registerDefaultTasks(): void
    {
        // 论坛助手定时任务 - 每分钟检查
        $this->registerTask(new class implements TaskInterface {
            public function getName(): string { return 'forum_cron'; }
            public function getDescription(): string { return '论坛助手自动任务（签到/回帖/通知检查）'; }
            public function getInterval(): int { return 60; } // 1分钟
            public function getType(): string { return 'forum'; }

            public function execute(): array {
                return TaskScheduler::runForumCron();
            }
        }, false);
    }

    /**
     * 调度循环
     */
    private function scheduleLoop(): void
    {
        while ($this->isRunning) {
            try {
                $this->updateHeartbeat();
                $this->checkAndExecuteTasks();
            } catch (\Throwable $e) {
                $this->log("调度循环异常: " . $e->getMessage());
            }

            // 主动回收内存，防止长时间运行导致内存泄漏
            gc_collect_cycles();

            // 等待下次检查
            sleep($this->checkInterval);
        }
    }

    /**
     * 检查并执行到期的任务
     */
    private function checkAndExecuteTasks(): void
    {
        $now = time();

        foreach ($this->tasks as $taskName => $task) {
            $nextExecution = $this->getNextExecutionTime($taskName);

            if ($now >= $nextExecution) {
                $this->executeTaskInternal($taskName, $task);
                // 更新下次执行时间
                $this->lastExecutions[$taskName] = new TaskExecution($taskName, $now, $now, true, 'scheduled', null);
            }
        }
    }

    /**
     * 内部执行任务
     */
    private function executeTaskInternal(string $taskName, TaskInterface $task): void
    {
        $startTime = time();

        try {
            $this->log("执行定时任务: {$taskName}");
            $result = $task->execute();

            $endTime = time();
            $execution = new TaskExecution($taskName, $startTime, $endTime, true, json_encode($result, JSON_UNESCAPED_UNICODE));
            $this->recordExecution($execution);

        } catch (\Throwable $e) {
            $endTime = time();
            $execution = new TaskExecution($taskName, $startTime, $endTime, false, '', $e->getMessage());
            $this->recordExecution($execution);

            $this->log("定时任务执行失败: {$taskName} - " . $e->getMessage());
        }
    }

    /**
     * 获取下次执行时间
     */
    private function getNextExecutionTime(string $taskName): int
    {
        $task = $this->tasks[$taskName] ?? null;
        if (!$task) {
            return PHP_INT_MAX;
        }

        $lastExecution = $this->lastExecutions[$taskName] ?? null;
        if ($lastExecution) {
            return $lastExecution->endTime + $task->getInterval();
        }

        // 如果任务尚未执行过，则从当前时间开始计算下次执行时间。
        return time() + $task->getInterval();
    }

    /**
     * 记录执行结果
     */
    private function recordExecution(TaskExecution $execution): void
    {
        $this->lastExecutions[$execution->taskName] = $execution;

        $logData = array_merge($execution->toArray(), ['type' => 'execution']);
        $this->writeLog(json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n");
    }

    /**
     * 加载执行历史（仅读取最后200行，避免大文件撑爆内存）
     */
    private function loadExecutionHistory(): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }

        $lines = $this->tailFile($this->logFile, 200);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\[.+?\]\s*(.*)$/s', $line, $m)) {
                $line = $m[1];
            }
            $data = json_decode($line, true);
            if ($data && isset($data['type']) && $data['type'] === 'execution' && isset($data['task_name'])) {
                $execution = new TaskExecution(
                    $data['task_name'],
                    $data['start_time'],
                    $data['end_time'],
                    $data['success'],
                    $data['result'],
                    $data['error'] ?? null
                );
                $this->lastExecutions[$data['task_name']] = $execution;
            }
        }
    }

    /**
     * 高效读取文件末尾N行（不加载整个文件到内存）
     */
    private function tailFile(string $filePath, int $lines = 200): array
    {
        $result = [];
        $handle = @fopen($filePath, 'r');
        if (!$handle) {
            return $result;
        }

        $buffer = [];
        while (($line = fgets($handle)) !== false) {
            $buffer[] = $line;
            if (count($buffer) > $lines) {
                array_shift($buffer);
            }
        }
        fclose($handle);
        return $buffer;
    }

    /**
     * 确保日志目录存在
     */
    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    /**
     * 写入日志
     */
    private function writeLog(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";

        if (!file_exists($this->logFile)) {
            @touch($this->logFile);
        }

        @file_put_contents($this->logFile, $logEntry . "\n", FILE_APPEND);
    }

    private function saveStatusFile(array $status): void
    {
        if (!file_exists(dirname($this->statusFile))) {
            @mkdir(dirname($this->statusFile), 0755, true);
        }
        @file_put_contents($this->statusFile, json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private static function performWeeklyMaintenance(): array
    {
        $results = [];
        $errors = [];

        try {
            $logResult = self::cleanupOldLogs(30);
            $results['logs'] = $logResult;
            if (!empty($logResult['errors'])) {
                $errors = array_merge($errors, $logResult['errors']);
            }
        } catch (\Throwable $e) {
            $errors[] = '日志清理失败：' . $e->getMessage();
        }

        try {
            Backup::cleanupOldBackups(30);
            $results['backups'] = ['success' => true, 'message' => '过期备份已清理'];
        } catch (\Throwable $e) {
            $errors[] = '备份清理失败：' . $e->getMessage();
            $results['backups'] = ['success' => false, 'error' => $e->getMessage()];
        }

        return [
            'message' => '系统维护完成',
            'results' => $results,
            'errors' => $errors,
            'success' => empty($errors),
        ];
    }

    private static function cleanupOldLogs(int $days): array
    {
        $logsDir = dirname(__DIR__, 2) . '/logs';
        $threshold = time() - ($days * 86400);
        $deleted = 0;
        $errors = [];

        if (!is_dir($logsDir)) {
            return ['success' => true, 'message' => '日志目录不存在，无需清理', 'deleted' => 0, 'errors' => []];
        }

        $items = @scandir($logsDir);
        if (!is_array($items)) {
            return ['success' => false, 'message' => '无法读取日志目录', 'deleted' => 0, 'errors' => ['无法读取日志目录']];
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $logsDir . '/' . $item;
            if (!is_file($path)) {
                continue;
            }
            if (@filemtime($path) === false) {
                continue;
            }
            if (filemtime($path) < $threshold) {
                if (@unlink($path)) {
                    $deleted++;
                } else {
                    $errors[] = '无法删除日志文件：' . $path;
                }
            }
        }

        return ['success' => empty($errors), 'message' => '旧日志清理完成', 'deleted' => $deleted, 'errors' => $errors];
    }

    private function loadStatusFile(): array
    {
        if (!file_exists($this->statusFile)) {
            return [];
        }
        $data = json_decode(@file_get_contents($this->statusFile) ?: '', true);
        return is_array($data) ? $data : [];
    }

    private function buildStatusData(bool $running): array
    {
        $persisted = $this->loadStatusFile();
        $pid = $persisted['pid'] ?? null;
        if ($running && $pid === null && function_exists('getmypid')) {
            $pid = getmypid();
        }

        return [
            'is_running' => $running,
            'pid' => $pid,
            'start_time' => $running ? time() : ($persisted['start_time'] ?? null),
            'last_heartbeat' => $running ? time() : ($persisted['last_heartbeat'] ?? null),
            'check_interval' => $this->checkInterval,
            'max_concurrent' => $persisted['max_concurrent'] ?? 3,
            'log_level' => $persisted['log_level'] ?? 'info',
        ];
    }

    private function updateHeartbeat(): void
    {
        $status = $this->loadStatusFile();
        $status['last_heartbeat'] = time();
        $status['pid'] = $status['pid'] ?? (function_exists('getmypid') ? getmypid() : null);
        $status['is_running'] = true;
        $status['start_time'] = $status['start_time'] ?? time();
        $status['check_interval'] = $this->checkInterval;
        $this->saveStatusFile($status);
    }

    private function isSchedulerProcessAlive(?int $pid, ?int $lastHeartbeat, int $checkInterval): bool
    {
        if ($pid !== null && function_exists('posix_kill')) {
            $alive = @posix_kill($pid, 0);
            if ($alive) {
                return true;
            }
        }

        if ($lastHeartbeat !== null) {
            return time() - $lastHeartbeat <= max(120, $checkInterval * 2);
        }

        return false;
    }

    /**
     * 记录日志
     */
    private function log(string $message): void
    {
        $this->writeLog($message);
    }

    /**
     * 计算任务的下次运行时间
     */
    private function calculateNextRunTime(string $taskName): ?int
    {
        return $this->getNextExecutionTime($taskName);
    }

    /**
     * 设置任务启用状态
     */
    public function setTaskEnabled(string $taskName, bool $enabled): void
    {
        // 暂时不支持禁用任务，后续可以扩展
        $this->log("任务 {$taskName} " . ($enabled ? '启用' : '禁用') . "请求已记录，但当前版本不支持此功能");
    }

    /**
     * 立即运行任务
     */
    public function runTaskNow(string $taskName): array
    {
        $task = $this->tasks[$taskName] ?? null;
        if (!$task) {
            return ['success' => false, 'error' => '任务不存在'];
        }

        $startTime = time();
        try {
            $result = $task->execute();
            $endTime = time();
            $execution = new TaskExecution($taskName, $startTime, $endTime, true, json_encode($result, JSON_UNESCAPED_UNICODE));
            $this->recordExecution($execution);
            return ['success' => true, 'result' => $result];
        } catch (\Throwable $e) {
            $endTime = time();
            $execution = new TaskExecution($taskName, $startTime, $endTime, false, '', $e->getMessage());
            $this->recordExecution($execution);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 执行一次到期任务检查（页面访问触发，不阻塞）
     * 适用于无 pcntl_fork 的环境（群晖 NAS、Windows 等）
     */
    public function runOnce(): void
    {
        $lockFile = dirname(__DIR__, 2) . '/runtime/scheduler_run.lock';
        $lockDir = dirname($lockFile);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0755, true);
        }

        $fp = @fopen($lockFile, 'w');
        if (!$fp) {
            return;
        }

        // 非阻塞锁，如果另一个请求正在执行则跳过
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return;
        }

        try {
            $this->checkAndExecuteTasks();
            $this->updateHeartbeat();
        } catch (\Throwable $e) {
            error_log('[TaskScheduler::runOnce] ' . $e->getMessage());
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * 更新配置
     */
    public function updateConfig(array $config): void
    {
        if (isset($config['check_interval'])) {
            $this->checkInterval = max(10, min(3600, (int)$config['check_interval']));
        }
        if (isset($config['max_concurrent'])) {
            $config['max_concurrent'] = max(1, min(10, (int)$config['max_concurrent']));
        }
        if (isset($config['log_level'])) {
            $config['log_level'] = in_array($config['log_level'], ['debug', 'info', 'warning', 'error'], true)
                ? $config['log_level']
                : 'info';
        }

        // 持久化调度器配置到状态文件，保证刷新后仍能读取到最新值
        $persisted = $this->loadStatusFile();
        $persisted['check_interval'] = $this->checkInterval;
        if (isset($config['max_concurrent'])) {
            $persisted['max_concurrent'] = (int)$config['max_concurrent'];
        }
        if (isset($config['log_level'])) {
            $persisted['log_level'] = $config['log_level'];
        }
        $persisted['is_running'] = $persisted['is_running'] ?? false;
        $persisted['start_time'] = $persisted['start_time'] ?? null;
        $persisted['last_heartbeat'] = $persisted['last_heartbeat'] ?? null;
        $persisted['pid'] = $persisted['pid'] ?? null;
        $this->saveStatusFile($persisted);

        $this->log("调度器配置已更新: " . json_encode($persisted, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 执行论坛助手定时任务（签到/回帖/通知检查/日志清理）
     */
    public static function runForumCron(): array
    {
        if (function_exists('opcache_reset')) { opcache_reset(); }
        $results = ['signin' => 0, 'reply' => 0, 'notice' => 0, 'mention' => 0, 'cleaned' => 0, 'errors' => []];

        try {
            $cleaned = \App\Model\ForumActionLog::cleanOldLogs(1);
            $results['cleaned'] = $cleaned;
        } catch (\Throwable $e) {
            $results['errors'][] = '日志清理失败: ' . $e->getMessage();
        }

        try {
            $signinAccounts = \App\Model\ForumAccount::getNeedExecute('signin');
            foreach ($signinAccounts as $account) {
                try {
                    $service = new \App\Service\DiscuzService((int)$account['user_id'], $account);
                    $loginResult = $service->login($account['username'], $account['password']);
                    if (!$loginResult['ok']) {
                        \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $loginResult['error']);
                        $results['errors'][] = "签到登录失败[{$account['forum_name']}]: {$loginResult['error']}";
                        continue;
                    }
                    $result = $service->signin($account);
                    $logType = $result['ok'] ? 'signin' : 'error';
                    \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], $logType, $result['message'] ?? $result['error']);
                    if ($result['ok']) {
                        \App\Model\ForumAccount::updateLastSignin((int)$account['id']);
                    }
                    $results['signin']++;
                } catch (\Throwable $e) {
                    \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $e->getMessage());
                    $results['errors'][] = "签到异常[{$account['forum_name']}]: " . $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $results['errors'][] = '签到任务异常: ' . $e->getMessage();
        }

        try {
            $replyAccounts = \App\Model\ForumAccount::getNeedExecute('autoreply');
            foreach ($replyAccounts as $account) {
                try {
                    $uid = (int)$account['user_id'];
                    if (!\App\Model\AiQuota::hasQuota($uid)) {
                        $results['errors'][] = "AI次数不足[{$account['forum_name']}],跳过回帖";
                        continue;
                    }
                    $service = new \App\Service\DiscuzService($uid, $account);
                    $loginResult = $service->login($account['username'], $account['password']);
                    if (!$loginResult['ok']) {
                        \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $loginResult['error']);
                        $results['errors'][] = "回帖登录失败[{$account['forum_name']}]: {$loginResult['error']}";
                        continue;
                    }
                    $thread = $service->getUnrepliedThread();
                    if (!$thread) {
                        continue;
                    }
                    $tid = (int)$thread['tid'];
                    $message = '';
                    $threadContent = $service->getThreadContent($tid);
                    if ($threadContent['ok']) {
                        $message = \App\Service\DiscuzService::generateAiReply($threadContent['title'], $threadContent['content']);
                        if (empty($message)) {
                            $message = \App\Service\DiscuzService::generateAiReply($threadContent['title'], $threadContent['content'], true);
                        }
                    }
                    if (empty($message)) continue;
                    $message .= "\n" . ($account['ai_reply_flag'] ?? '[AI回帖]');
                    $result = $service->reply($tid, $message);
                    $logType = $result['ok'] ? 'reply' : 'error';
                    $target = "帖子#{$tid} " . ($result['title'] ?? '');
                    \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], $logType, $result['message'] ?? $result['error'], $target);
                    if ($result['ok']) {
                        \App\Model\AiQuota::consume($uid);
                        \App\Model\ForumAccount::updateLastReply((int)$account['id']);
                        \App\Model\ForumRepliedThread::markReplied((int)$account['id'], $tid, $result['title'] ?? '');
                    }
                    $results['reply']++;
                } catch (\Throwable $e) {
                    \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $e->getMessage());
                    $results['errors'][] = "回帖异常[{$account['forum_name']}]: " . $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $results['errors'][] = '回帖任务异常: ' . $e->getMessage();
        }

        try {
            $noticeAccounts = \App\Model\ForumAccount::getNeedCheckNotice();
            foreach ($noticeAccounts as $account) {
                try {
                    $service = new \App\Service\DiscuzService((int)$account['user_id'], $account);
                    $loginResult = $service->login($account['username'], $account['password']);
                    if (!$loginResult['ok']) {
                        \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $loginResult['error']);
                        continue;
                    }
                    $result = $service->getNotices();
                    \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'notice', $result['message'] ?? $result['error'] ?? '检查通知');
                    $results['notice']++;
                } catch (\Throwable $e) {
                    \App\Model\ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $e->getMessage());
                    $results['errors'][] = "通知检查异常[{$account['forum_name']}]: " . $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $results['errors'][] = '通知检查异常: ' . $e->getMessage();
        }

        try {
            $mentionAccounts = \App\Model\ForumAccount::getNeedMentionReply();
            foreach ($mentionAccounts as $account) {
                try {
                    $uid = (int)$account['user_id'];
                    if (!\App\Model\AiQuota::hasQuota($uid)) {
                        $results['errors'][] = "AI次数不足[{$account['forum_name']}],跳过@回复";
                        continue;
                    }
                    $service = new \App\Service\DiscuzService($uid, $account);
                    $loginResult = $service->login($account['username'], $account['password']);
                    if (!$loginResult['ok']) {
                        $results['errors'][] = "互动回复登录失败[{$account['forum_name']}]: " . ($loginResult['error'] ?? '未知');
                        continue;
                    }
                    $r = $service->handleMentionReplies();
                    $results['mention'] += ($r['replied'] ?? 0);
                    if (($r['replied'] ?? 0) > 0) {
                        \App\Model\AiQuota::consume($uid);
                        \App\Model\ForumActionLog::create($uid, (int)$account['id'], 'reply', $r['message']);
                    }
                } catch (\Throwable $e) {
                    $results['errors'][] = "互动回复异常[{$account['forum_name']}]: " . $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $results['errors'][] = '互动回复任务异常: ' . $e->getMessage();
        }

        try {
            require_once dirname(__DIR__, 2) . '/process_system_tasks.php';
            processBackupTask();
        } catch (\Throwable $e) {}

        return [
            'message' => "签到{$results['signin']}个，回帖{$results['reply']}个，通知{$results['notice']}个，互动{$results['mention']}个，清理{$results['cleaned']}条日志",
            'details' => $results,
            'success' => empty($results['errors']),
        ];
    }

    /**
     * 调度器日志轮转（超过5MB时归档）
     */
    private static function rotateSchedulerLog(): array
    {
        $logFile = dirname(__DIR__, 2) . '/logs/task_scheduler.log';
        if (!file_exists($logFile)) {
            return ['message' => '日志文件不存在，无需轮转'];
        }

        $size = filesize($logFile);
        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($size <= $maxSize) {
            return ['message' => '日志文件大小 ' . round($size / 1024) . 'KB，未达到轮转阈值'];
        }

        $archiveFile = $logFile . '.' . date('Y-m-d_H-i-s') . '.bak';
        if (@rename($logFile, $archiveFile)) {
            @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] 日志已轮转，归档: ' . basename($archiveFile) . "\n");
            return ['message' => '日志已轮转', 'archived' => basename($archiveFile), 'size' => $size];
        }

        return ['message' => '日志轮转失败'];
    }
    public function getStatus(): array
    {
        $persisted = $this->loadStatusFile();
        $isRunning = !empty($persisted['is_running']) && $this->isSchedulerProcessAlive(
            isset($persisted['pid']) ? (int)$persisted['pid'] : null,
            isset($persisted['last_heartbeat']) ? (int)$persisted['last_heartbeat'] : null,
            isset($persisted['check_interval']) ? (int)$persisted['check_interval'] : $this->checkInterval
        );

        $tasks = [];
        foreach ($this->tasks as $task) {
            $lastExecution = $this->lastExecutions[$task->getName()] ?? null;
            $nextRun = $this->calculateNextRunTime($task->getName());

            $tasks[] = [
                'name' => $task->getName(),
                'description' => $task->getDescription(),
                'enabled' => true,
                'next_run' => $nextRun ? date('Y-m-d H:i:s', $nextRun) : null,
                'last_run' => $lastExecution ? date('Y-m-d H:i:s', $lastExecution->startTime) : null,
                'last_result' => $lastExecution ? ($lastExecution->success ? 'success' : 'error') : null,
            ];
        }

        return [
            'is_running' => $isRunning,
            'start_time' => !empty($persisted['start_time']) ? date('Y-m-d H:i:s', (int)$persisted['start_time']) : null,
            'config' => [
                'check_interval' => isset($persisted['check_interval']) ? (int)$persisted['check_interval'] : $this->checkInterval,
                'max_concurrent' => isset($persisted['max_concurrent']) ? (int)$persisted['max_concurrent'] : 3,
                'log_level' => $persisted['log_level'] ?? 'info',
            ],
            'tasks' => $tasks,
        ];
    }
}
