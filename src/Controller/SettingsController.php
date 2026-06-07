<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\LedgerContext;
use App\Model\User;
use App\Model\SystemSetting;
use App\Model\Announcement;
use App\Model\EmailPush;
use App\Model\Ledger;
use App\Model\LedgerMember;
use App\Service\Mailer;
use App\Service\Seeder;
use App\Model\LoginToken;
use App\Model\UserWechatBinding;
use App\Model\ApiToken;

class SettingsController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
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
        $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtolower((string)$xrw) === 'xmlhttprequest';
    }

    private function accountMaintenanceBlockReason(int $userId): ?string
    {
        if (Ledger::hasSharedLedgersForUser($userId)) {
            return '检测到当前账号仍关联共享账本。为避免误删多人数据，请先在「设置 - 账本管理」中删除共享账本后，再重新发起重置或注销。';
        }
        return null;
    }

    private function initAccountMaintenance(int $userId, string $type, string $confirm): array
    {
        $type = $type === 'delete' ? 'delete' : 'reset';
        $confirm = strtoupper(trim($confirm));

        $expected = $type === 'delete' ? 'DELETE' : 'RESET';
        if ($confirm !== $expected) {
            return ['ok' => false, 'error' => '确认文本不正确，请输入 ' . $expected . '。'];
        }

        $block = $this->accountMaintenanceBlockReason($userId);
        if ($block !== null) {
            return ['ok' => false, 'error' => $block, 'block' => 'shared_ledger'];
        }

        $steps = $this->getAccountMaintenanceSteps($type);
        $_SESSION['account_maintenance'] = [
            'type' => $type,
            'status' => 'running',
            'step_index' => 0,
            'total_steps' => count($steps),
            'started_at' => time(),
        ];

        return [
            'ok' => true,
            'type' => $type,
            'totalSteps' => count($steps),
            'nextStep' => 0,
        ];
    }

    private function getAccountMaintenanceSteps(string $type): array
    {
        $type = $type === 'delete' ? 'delete' : 'reset';
        $steps = [
            ['key' => 'transactions', 'name' => '清理流水与附件'],
            ['key' => 'accounts_categories_items_budgets', 'name' => '清理账户 / 分类 / 项目 / 预算'],
            ['key' => 'goals', 'name' => '清理目标与关联'],
            ['key' => 'assets_subscriptions', 'name' => '清理资产与订阅'],
            ['key' => 'icons', 'name' => '清理图标数据'],
            ['key' => 'feedbacks', 'name' => '清理反馈记录'],
            ['key' => 'tokens_reads', 'name' => '清理令牌与阅读记录'],
        ];

        if ($type === 'delete') {
            $steps[] = ['key' => 'personal_ledger', 'name' => '清理个人账本信息'];
            $steps[] = ['key' => 'delete_user', 'name' => '删除账号并退出登录'];
        }
        return $steps;
    }

    private function runAccountMaintenanceStep(int $userId, string $type, int $stepIndex): array
    {
        $type = $type === 'delete' ? 'delete' : 'reset';
        $steps = $this->getAccountMaintenanceSteps($type);
        if (!isset($steps[$stepIndex])) {
            return ['ok' => false, 'error' => '无效的步骤'];
        }

        // 每步执行前再做一次共享账本校验（更安全）
        $block = $this->accountMaintenanceBlockReason($userId);
        if ($block !== null) {
            return ['ok' => false, 'error' => $block, 'block' => 'shared_ledger'];
        }

        $pdo = \App\Service\Database::getConnection();
        $key = (string)$steps[$stepIndex]['key'];
        $name = (string)$steps[$stepIndex]['name'];

        try {
            $pdo->beginTransaction();

            if ($key === 'transactions') {
                // goal links / attachments 先通过 JOIN 方式清理（避免 IN 太长）
                try {
                    $stmt = $pdo->prepare('DELETE a FROM transaction_attachments a INNER JOIN transactions t ON t.id = a.transaction_id WHERE t.user_id = :uid');
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }
                try {
                    $stmt = $pdo->prepare('DELETE l FROM goal_transaction_links l INNER JOIN transactions t ON t.id = l.transaction_id WHERE t.user_id = :uid');
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }
                $stmt = $pdo->prepare('DELETE FROM transactions WHERE user_id = :uid');
                $stmt->execute([':uid' => $userId]);

            } elseif ($key === 'accounts_categories_items_budgets') {
                foreach (['accounts', 'categories', 'items', 'budgets'] as $table) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = :uid");
                        $stmt->execute([':uid' => $userId]);
                    } catch (\Throwable $e) {
                    }
                }

            } elseif ($key === 'goals') {
                try {
                    $stmt = $pdo->prepare('DELETE l FROM goal_transaction_links l INNER JOIN goals g ON g.id = l.goal_id WHERE g.user_id = :uid');
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }
                try {
                    $stmt = $pdo->prepare('DELETE FROM goals WHERE user_id = :uid');
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }

            } elseif ($key === 'assets_subscriptions') {
                foreach (['assets', 'subscriptions'] as $table) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = :uid");
                        $stmt->execute([':uid' => $userId]);
                    } catch (\Throwable $e) {
                    }
                }

            } elseif ($key === 'icons') {
                foreach (['icon_library', 'system_icon_submissions'] as $table) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = :uid");
                        $stmt->execute([':uid' => $userId]);
                    } catch (\Throwable $e) {
                    }
                }

            } elseif ($key === 'feedbacks') {
                try {
                    $stmt = $pdo->prepare('DELETE fm FROM feedback_messages fm INNER JOIN feedbacks f ON f.id = fm.feedback_id WHERE f.user_id = :uid');
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }
                try {
                    $stmt = $pdo->prepare('DELETE FROM feedbacks WHERE user_id = :uid');
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }

            } elseif ($key === 'tokens_reads') {
                foreach (['api_tokens', 'login_tokens', 'email_tokens', 'announcement_reads'] as $table) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = :uid");
                        $stmt->execute([':uid' => $userId]);
                    } catch (\Throwable $e) {
                    }
                }

            } elseif ($key === 'personal_ledger') {
                // 仅在“删除账号”流程里执行；且前置已阻止共享账本
                try {
                    $stmt = $pdo->prepare('DELETE FROM ledger_members WHERE user_id = :uid');
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }
                try {
                    $stmt = $pdo->prepare("DELETE FROM ledgers WHERE type = 'personal' AND owner_user_id = :uid");
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }

            } elseif ($key === 'delete_user') {
                // 删除账号：最后一步删除绑定关系与 users
                try {
                    $stmt = $pdo->prepare('DELETE FROM user_wechat_bindings WHERE user_id = :uid');
                    $stmt->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                }
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = :uid');
                $stmt->execute([':uid' => $userId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            try {
                $pdo->rollBack();
            } catch (\Throwable $ignored) {
            }
            try {
                error_log('[account-maintenance] step failed: ' . $key . ' uid=' . $userId . ' err=' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
            return ['ok' => false, 'error' => '执行失败：' . $name . '，请稍后重试。'];
        }

        // 若删除账号步骤已执行，需要退出登录（会话销毁）
        $redirect = null;
        if ($key === 'delete_user') {
            $_SESSION = [];
            if (session_id() !== '') {
                try {
                    session_destroy();
                } catch (\Throwable $e) {
                }
            }
            $redirect = '/public/index.php?route=login';
        }

        return ['ok' => true, 'stepKey' => $key, 'stepName' => $name, 'redirect' => $redirect];
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        $currentUser = User::findById($userId);
        $isAdmin = ($currentUser['role'] ?? 'user') === 'admin';
        $registerSource = $currentUser['register_source'] ?? null;
        // 兼容旧库：无 register_source 时尝试根据绑定时间推断；若也无法推断，则按 PC 注册处理
        $currentBinding = UserWechatBinding::findByUserId($userId);
        $hasWechatBinding = $currentBinding !== null;
        if ($registerSource === null) {
            $isMiniappUser = false;
            if ($currentBinding && !empty($currentUser['created_at']) && !empty($currentBinding['created_at'])) {
                $uCreated = strtotime($currentUser['created_at']);
                $bCreated = strtotime($currentBinding['created_at']);
                if ($uCreated && $bCreated) {
                    $diffMin = abs(($bCreated - $uCreated) / 60);
                    $isMiniappUser = ($diffMin <= 5);
                }
            }
        } else {
            $isMiniappUser = ($registerSource === 'miniapp');
        }

        $tab = $_GET['tab'] ?? 'profile';
        $error = '';
        $success = '';
        $usernameModalError = '';
        $usernameModalSuccess = '';
        $pendingUsername = '';
        $emailModalError = '';
        $emailModalSuccess = '';
        $pendingEmail = '';
        $openModal = '';
        // 个人绑定二维码（当前用户自己生成并查看）
        $selfBindQrToken = null;
        $selfBindQrPayload = null;
        $selfBindQrExpiresAt = null;
        // 管理端生成的绑定二维码信息（仅本次请求展示）
        $bindQrUserId = null;
        $bindQrToken = null;
        $bindQrPayload = null;
        $bindQrExpiresAt = null;
        // 共享账本邀请二维码（当前会话内临时展示）
        $ledgerInviteQrLedgerId = null;
        $ledgerInviteQrLedgerName = null;
        $ledgerInviteQrPayload = null;
        $ledgerInviteCode = null;

        // 共享账本成员管理弹窗（当前会话内临时展示）
        $ledgerMembersModalLedgerId = null;
        $ledgerMembersModalLedgerName = null;
        $ledgerMembersOwnerUserId = null;
        $ledgerMembers = [];
        $ledgerMembersModalError = '';
        $ledgerMembersModalSuccess = '';
        $pendingLedgerMemberKeyword = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            // 账号维护（重置数据 / 注销账号）：AJAX 分步执行 + 进度条
            if ($this->isAjax() && in_array($action, ['self_reset_init', 'self_reset_step', 'self_delete_init', 'self_delete_step'], true)) {
                $type = str_starts_with($action, 'self_delete') ? 'delete' : 'reset';
                if (str_ends_with($action, '_init')) {
                    $confirm = (string)($_POST['confirm'] ?? '');
                    $result = $this->initAccountMaintenance($userId, $type, $confirm);
                    $this->json($result);
                }

                // step
                $job = $_SESSION['account_maintenance'] ?? null;
                if (!is_array($job) || ($job['status'] ?? '') !== 'running' || ($job['type'] ?? '') !== $type) {
                    $this->json(['ok' => false, 'error' => '任务不存在或已结束，请重新发起。']);
                }

                $expectedIndex = (int)($job['step_index'] ?? 0);
                $stepIndex = (int)($_POST['step'] ?? -1);
                if ($stepIndex !== $expectedIndex) {
                    $this->json(['ok' => false, 'error' => '步骤不同步，请刷新页面后重试。']);
                }

                $steps = $this->getAccountMaintenanceSteps($type);
                $total = count($steps);
                $stepResult = $this->runAccountMaintenanceStep($userId, $type, $stepIndex);
                if (empty($stepResult['ok'])) {
                    $job['status'] = 'error';
                    $_SESSION['account_maintenance'] = $job;
                    $this->json($stepResult);
                }

                $job['step_index'] = $stepIndex + 1;
                if ($job['step_index'] >= $total) {
                    $job['status'] = 'done';
                }
                $_SESSION['account_maintenance'] = $job;

                $percent = $total > 0 ? (int)floor(min(100, ($job['step_index'] / $total) * 100)) : 100;
                $this->json([
                    'ok' => true,
                    'type' => $type,
                    'stepIndex' => $stepIndex,
                    'stepName' => $stepResult['stepName'] ?? '',
                    'nextStep' => $job['status'] === 'done' ? null : $job['step_index'],
                    'done' => $job['status'] === 'done',
                    'totalSteps' => $total,
                    'percent' => $percent,
                    'redirect' => $stepResult['redirect'] ?? null,
        ]);
            }

            if ($action === 'update_profile') {
                // 仅允许在此处修改昵称，用户名通过单独弹窗处理
                $nickname = trim($_POST['nickname'] ?? '');
                if ($nickname === '') {
                    $error = '昵称不能为空';
                } else {
                    User::updateProfile($userId, $currentUser['username'] ?? '', $nickname);
                    $_SESSION['user_nickname'] = $nickname;
                    $success = '昵称已更新';
                    $currentUser = User::findById($userId);
                }

            } elseif ($action === 'update_avatar') {
                // PC 端手动上传头像
                if (empty($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
                    $error = '请选择要上传的头像文件';
                } else {
                    $oldAvatar = $currentUser['avatar_path'] ?? null;
                    $newPath = \App\Service\Upload::saveAvatar($userId, $_FILES['avatar']);
                    if ($newPath === null) {
                        $error = '头像上传失败，请确认文件大小不超过 5MB 且为常见图片格式';
                    } else {
                        User::updateAvatarPath($userId, $newPath);
                        if ($oldAvatar && $oldAvatar !== $newPath) {
                            \App\Service\Upload::deleteByRelativePath($oldAvatar);
                        }
                        $currentUser = User::findById($userId);
                        $_SESSION['user_avatar'] = '/uploads/' . ltrim((string)$currentUser['avatar_path'], '/\\');
                        $success = '头像已更新';
                    }
                }

            } elseif ($action === 'update_budget_reminder') {
                // 更新用户级预算提醒开关（接近上限 / 超支高亮与文案）
                $enabled = !empty($_POST['budget_reminder_enabled']);
                User::updateBudgetReminder($userId, $enabled);
                $success = '预算提醒设置已更新';
                $currentUser = User::findById($userId);

            } elseif ($action === 'update_transfer_feature') {
                // 更新用户级账户间转账功能开关
                $enabled = !empty($_POST['enable_transfer']);
                User::updateTransferFeature($userId, $enabled);
                $success = '转账功能开关已更新';
                $currentUser = User::findById($userId);

            } elseif ($action === 'update_allow_negative_balance') {
                // 更新用户级账户余额是否允许为负数的开关
                $enabled = !empty($_POST['allow_negative_balance']);
                User::updateAllowNegativeBalance($userId, $enabled);
                $success = '账户余额为负数开关已更新';
                $currentUser = User::findById($userId);

            } elseif ($action === 'change_username') {
                $tab = 'profile';
                $openModal = 'username';
                $newUsername = trim($_POST['new_username'] ?? '');
                $pendingUsername = $newUsername;
                $submitType = $_POST['submit_type'] ?? 'save';

                if ($newUsername === '') {
                    $usernameModalError = '新用户名不能为空';
                } else {
                    $uByName = User::findByUsername($newUsername);
                    if ($uByName && (int)$uByName['id'] !== $userId) {
                        $usernameModalError = '该用户名已被占用，请尝试其他名称或使用推荐';
                    } else {
                        if ($submitType === 'check') {
                            $usernameModalSuccess = '该用户名可以使用';
                        } else {
                            User::updateUsername($userId, $newUsername);
                            $success = '用户名已修改，下次登录请使用新用户名';
                            $currentUser = User::findById($userId);
                            $openModal = '';
                            $pendingUsername = '';
                        }
                    }
                }

            } elseif ($action === 'change_email') {
                $tab = 'profile';
                $newEmail = trim($_POST['new_email'] ?? '');
                $pendingEmail = $newEmail;
                if ($newEmail === '') {
                    $emailModalError = '新邮箱不能为空';
                    $openModal = 'email';
                } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $emailModalError = '请输入有效的新邮箱地址';
                    $openModal = 'email';
                } elseif (strcasecmp((string)($currentUser['email'] ?? ''), $newEmail) === 0) {
                    $emailModalError = '新邮箱不能与当前邮箱相同';
                    $openModal = 'email';
                } else {
                    $exist = User::findByEmail($newEmail);
                    if ($exist && (int)$exist['id'] !== $userId) {
                        $emailModalError = '该邮箱已被其他账号使用，请更换一个邮箱。';
                        $openModal = 'email';
                    } else {
                        // 直接更新邮箱并视为已验证
                        User::updateEmail($userId, $newEmail, true);
                        $success = '邮箱已更新，下次登录可使用新邮箱。';
                        $currentUser = User::findById($userId);
                        $pendingEmail = '';
                        $openModal = '';
                        // 清理旧的验证码会话数据（如存在）
                        unset($_SESSION['email_change']);
                    }
                }

            } elseif ($action === 'change_password') {
                $old = $_POST['old_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                if (!password_verify($old, $currentUser['password_hash'] ?? '')) {
                    $error = '旧密码不正确，如忘记可使用“忘记密码”功能';
                } elseif ($new === '' || $new !== $confirm) {
                    $error = '新密码不能为空且两次输入需一致';
                } else {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    User::updatePassword($userId, $hash);
                    $success = '密码已更新';
                }
                $tab = 'security';

            } elseif ($isAdmin && $action === 'update_system') {
                $siteName = trim($_POST['site_name'] ?? '');
                $siteUrl = trim($_POST['site_url'] ?? '') ?: null;
                $allowRegister = isset($_POST['allow_register']);
                $siteIconSvg = trim($_POST['site_icon_svg'] ?? '');
                if ($siteIconSvg === '') {
                    $siteIconSvg = null;
                }

                $timeoutRaw = trim($_POST['session_timeout_hours'] ?? '');
                $sessionTimeoutHours = $timeoutRaw === '' ? 24 : (int)$timeoutRaw;

                if ($sessionTimeoutHours < 1 || $sessionTimeoutHours > 168) {
                    $error = '自动退出时间需在 1~168 小时之间，请重新填写。';
                } else {
                    // 绑定二维码相关参数（如未提供则使用当前或默认值）
                    $bindMinutesRaw = trim($_POST['bind_qr_expires_minutes'] ?? '');
                    $bindMinutes = $bindMinutesRaw === '' ? (int)($system['bind_qr_expires_minutes'] ?? 10) : (int)$bindMinutesRaw;
                    if ($bindMinutes < 1) {
                        $bindMinutes = 1;
                    } elseif ($bindMinutes > 1440) {
                        $bindMinutes = 1440;
                    }
                    $bindText = trim($_POST['bind_qr_text'] ?? (string)($system['bind_qr_text'] ?? ''));

                    // 处理背景图上传
                $bgImagePath = $system['bg_image_path'] ?? null;
                if (!empty($_FILES['bg_image']) && $_FILES['bg_image']['error'] === UPLOAD_ERR_OK) {
                    $newBgPath = \App\Service\Upload::saveBgImage($_FILES['bg_image']);
                    if ($newBgPath !== null) {
                        $bgImagePath = $newBgPath;
                        // 记录到背景图历史表
                        $pdo = \App\Service\Database::getConnection();
                        $stmt = $pdo->prepare("INSERT INTO bg_images (file_path, created_at) VALUES (?, NOW())");
                        $stmt->execute([$newBgPath]);
                    }
                }
                // 处理从历史列表选择背景图
                if (!empty($_POST['bg_image_select'])) {
                    $selectPath = $_POST['bg_image_select'];
                    $pdo = \App\Service\Database::getConnection();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bg_images WHERE file_path = ?");
                    $stmt->execute([$selectPath]);
                    if ($stmt->fetchColumn() > 0) {
                        $bgImagePath = $selectPath;
                    }
                }
                // 处理背景图删除（从历史列表删除单张）
                if (isset($_POST['bg_image_delete']) && !empty($_POST['bg_image_delete'])) {
                    $delPath = $_POST['bg_image_delete'];
                    \App\Service\Upload::deleteByRelativePath($delPath);
                    $pdo = \App\Service\Database::getConnection();
                    $stmt = $pdo->prepare("DELETE FROM bg_images WHERE file_path = ?");
                    $stmt->execute([$delPath]);
                    // 如果删的是当前启用的背景图，清空设置
                    if ($delPath === ($system['bg_image_path'] ?? '')) {
                        $bgImagePath = null;
                    }
                }

                SystemSetting::update(
                    siteName: $siteName,
                    siteUrl: $siteUrl,
                    allowRegister: $allowRegister,
                    siteIconSvg: $siteIconSvg,
                    sessionTimeoutHours: $sessionTimeoutHours,
                    bindQrExpiresMinutes: $bindMinutes,
                    bindQrText: $bindText,
                    bgImagePath: $bgImagePath,
                );
                    $success = '系统参数已保存';
                    // 重新加载系统设置，避免页面仍显示旧值
                    $system = SystemSetting::get();
                }
                $tab = 'system';

            } elseif ($isAdmin && $action === 'run_migration') {
                $results = [];
                try {
                    require_once __DIR__ . '/../bootstrap.php';
                    $results[] = '数据库迁移完成';
                } catch (\Throwable $e) {
                    $results[] = '迁移异常: ' . $e->getMessage();
                }
                try {
                    \App\Service\TodayDoService::initTables();
                    $results[] = '「今天干嘛」数据表已就绪';
                } catch (\Throwable $e) {
                    $results[] = '今天干嘛异常: ' . $e->getMessage();
                }
                $success = implode('；', $results);
                $tab = 'system';

            } elseif ($isAdmin && $action === 'miniapp_add') {
                $pdo = \App\Service\Database::getConnection();
                $name = trim($_POST['miniapp_name'] ?? '');
                if ($name !== '') {
                    $qrcodePath = '';
                    if (!empty($_FILES['miniapp_qrcode']) && $_FILES['miniapp_qrcode']['error'] === UPLOAD_ERR_OK) {
                        $qrcodePath = \App\Service\Upload::saveMiniappQrcode($_FILES['miniapp_qrcode']);
                    }
                    $stmt = $pdo->prepare("INSERT INTO miniapps (name, qrcode_path, sort_order) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $qrcodePath, (int)($_POST['miniapp_sort'] ?? 0)]);
                    $success = '小程序已添加';
                }
                $tab = 'system';

            } elseif ($isAdmin && $action === 'miniapp_update') {
                $pdo = \App\Service\Database::getConnection();
                $id = (int)($_POST['miniapp_id'] ?? 0);
                $name = trim($_POST['miniapp_name'] ?? '');
                if ($id > 0 && $name !== '') {
                    $fields = ['name = ?', 'sort_order = ?'];
                    $vals = [$name, (int)($_POST['miniapp_sort'] ?? 0)];
                    if (!empty($_FILES['miniapp_qrcode']) && $_FILES['miniapp_qrcode']['error'] === UPLOAD_ERR_OK) {
                        // 删除旧图片
                        $oldRow = $pdo->prepare("SELECT qrcode_path FROM miniapps WHERE id = ?");
                        $oldRow->execute([$id]);
                        $oldFile = $oldRow->fetch(\PDO::FETCH_ASSOC);
                        if ($oldFile && !empty($oldFile['qrcode_path'])) {
                            $oldFullPath = rtrim(Config::get('app.upload_dir'), '/') . '/' . $oldFile['qrcode_path'];
                            if (file_exists($oldFullPath)) @unlink($oldFullPath);
                        }
                        $qrcodePath = \App\Service\Upload::saveMiniappQrcode($_FILES['miniapp_qrcode']);
                        $fields[] = 'qrcode_path = ?';
                        $vals[] = $qrcodePath;
                    }
                    $vals[] = $id;
                    $stmt = $pdo->prepare("UPDATE miniapps SET " . implode(', ', $fields) . " WHERE id = ?");
                    $stmt->execute($vals);
                    $success = '小程序已更新';
                }
                $tab = 'system';

            } elseif ($isAdmin && $action === 'miniapp_delete') {
                $pdo = \App\Service\Database::getConnection();
                $id = (int)($_POST['miniapp_id'] ?? 0);
                if ($id > 0) {
                    $row = $pdo->prepare("SELECT qrcode_path FROM miniapps WHERE id = ?");
                    $row->execute([$id]);
                    $old = $row->fetch(\PDO::FETCH_ASSOC);
                    if ($old && !empty($old['qrcode_path'])) {
                        $fullPath = rtrim(Config::get('app.upload_dir'), '/') . '/' . $old['qrcode_path'];
                        if (file_exists($fullPath)) @unlink($fullPath);
                    }
                    $pdo->prepare("DELETE FROM miniapps WHERE id = ?")->execute([$id]);
                    $success = '小程序已删除';
                }
                $tab = 'system';

            } elseif ($isAdmin && $action === 'announcement_create') {
                $tab = 'system';
                $title = trim($_POST['announcement_title'] ?? '');
                $content = trim($_POST['announcement_content'] ?? '');
                $sendType = $_POST['announcement_send_type'] ?? 'now';
                $scheduledRaw = trim($_POST['announcement_scheduled_at'] ?? '');
                if ($title === '' || $content === '') {
                    $error = '公告标题和内容不能为空';
                } else {
                    $scheduledAt = date('Y-m-d H:i:s');
                    if ($sendType === 'schedule' && $scheduledRaw !== '') {
                        $ts = strtotime($scheduledRaw);
                        if ($ts !== false) {
                            $scheduledAt = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    Announcement::create($title, $content, $scheduledAt);
                    $success = '公告已创建';
                }
            } elseif ($isAdmin && $action === 'announcement_update') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                $title = trim($_POST['announcement_title'] ?? '');
                $content = trim($_POST['announcement_content'] ?? '');
                $scheduledRaw = trim($_POST['announcement_scheduled_at'] ?? '');
                if ($id <= 0) {
                    $error = '公告不存在';
                } elseif ($title === '' || $content === '') {
                    $error = '公告标题和内容不能为空';
                } else {
                    $scheduledAt = date('Y-m-d H:i:s');
                    if ($scheduledRaw !== '') {
                        $ts = strtotime($scheduledRaw);
                        if ($ts !== false) {
                            $scheduledAt = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    Announcement::update($id, $title, $content, $scheduledAt);
                    $success = '公告已更新';
                }
            } elseif ($isAdmin && $action === 'announcement_delete') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Announcement::delete($id);
                    $success = '公告已删除';
                } else {
                    $error = '公告不存在';
                }
            } elseif ($isAdmin && $action === 'announcement_repush') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                $row = $id > 0 ? Announcement::findById($id) : null;
                if (!$row) {
                    $error = '公告不存在';
                } else {
                    $scheduledAt = date('Y-m-d H:i:s');
                    Announcement::create((string)$row['title'], (string)$row['content'], $scheduledAt);
                    $success = '公告已重新推送（生成一条新的公告记录）';
                }
            } elseif ($isAdmin && $action === 'email_push_create') {
                $tab = 'system';
                $title = trim($_POST['email_title'] ?? '');
                $content = trim($_POST['email_content'] ?? '');
                $scope = $_POST['email_scope'] ?? 'all';
                $sendType = $_POST['email_send_type'] ?? 'now';
                $scheduledRaw = trim($_POST['email_scheduled_at'] ?? '');
                $selectedIds = isset($_POST['email_selected_users']) && is_array($_POST['email_selected_users']) ? $_POST['email_selected_users'] : [];

                if ($title === '' || $content === '') {
                    $error = '邮件标题和内容不能为空';
                } elseif ($scope === 'selected' && empty($selectedIds)) {
                    $error = '请选择需要推送的用户';
                } else {
                    $scope = $scope === 'selected' ? 'selected' : 'all';
                    $scheduledAt = date('Y-m-d H:i:s');
                    if ($sendType === 'schedule' && $scheduledRaw !== '') {
                        $ts = strtotime($scheduledRaw);
                        if ($ts !== false) {
                            $scheduledAt = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    $pushId = EmailPush::create($title, $content, $scope, $scheduledAt);
                    if ($scope === 'selected') {
                        EmailPush::seedRecipients($pushId, $selectedIds);
                    }
                    if ($sendType === 'now') {
                        $result = EmailPush::sendNow($pushId);
                        $success = '邮件已发送：成功 ' . (int)$result['sent'] . ' 封，失败 ' . (int)$result['failed'] . ' 封。';
                    } else {
                        $success = '邮件推送任务已创建，将在计划时间后自动发送（需有访问触发或定时任务调用）。';
                    }
                }
            } elseif ($isAdmin && $action === 'email_push_delete') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    EmailPush::delete($id);
                    $success = '邮件推送记录已删除';
                } else {
                    $error = '邮件推送记录不存在';
                }
            } elseif ($isAdmin && $action === 'email_push_resend') {
                $tab = 'system';
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $result = EmailPush::sendNow($id);
                    $success = '邮件已重新发送：成功 ' . (int)$result['sent'] . ' 封，失败 ' . (int)$result['failed'] . ' 封。';
                } else {
                    $error = '邮件推送记录不存在';
                }
            } elseif ($isAdmin && $action === 'user_status') {
                $uid = (int)($_POST['id'] ?? 0);
                $status = (int)($_POST['status'] ?? 1);
                if ($uid !== $userId) {
                    User::updateStatus($uid, $status);
                    $success = '用户状态已更新';
                } else {
                    $error = '不能禁用当前登录账号';
                }
                $tab = 'users';
            } elseif ($isAdmin && $action === 'user_role') {
                $uid = (int)($_POST['id'] ?? 0);
                $role = $_POST['role'] ?? 'user';
                User::updateRole($uid, $role);
                $success = '用户角色已更新';
                $tab = 'users';
            } elseif ($isAdmin && $action === 'user_reset_password') {
                $uid = (int)($_POST['id'] ?? 0);
                $user = User::findById($uid);
                if ($user) {
                    $newPass = substr(bin2hex(random_bytes(4)), 0, 8);
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    User::updatePassword($uid, $hash);
                    // 发送邮件通知
                    $subject = 'SanS三石记账系统 - 密码已重置';
                    $html = '<p>您好，' . htmlspecialchars($user['nickname']) . '：</p>' .
                        '<p>管理员已为您重置登录密码，新密码为：<b>' . htmlspecialchars($newPass) . '</b></p>' .
                        '<p>请尽快登录系统并在“安全设置”中修改为您自己的密码。</p>';
                    Mailer::send($user['email'], $user['nickname'], $subject, $html);
                    $success = '已为该用户重置密码并发送邮件通知';
                }
                $tab = 'users';
            } elseif ($isAdmin && $action === 'user_delete') {
                $uid = (int)($_POST['id'] ?? 0);
                if ($uid === $userId) {
                    $error = '不能删除当前登录账号';
                } else {
                    User::deleteForce($uid);
                    $success = '已强制删除该用户及其所有数据';
                }
                $tab = 'users';
            } elseif ($isAdmin && $action === 'user_generate_bind_qr') {
                $tab = 'users';
                $uid = (int)($_POST['id'] ?? 0);
                $target = User::findById($uid);
                if (!$target) {
                    $error = '用户不存在';
                } else {
                    $systemTmp = SystemSetting::get();
                    $minutes = (int)($systemTmp['bind_qr_expires_minutes'] ?? 10);
                    if ($minutes <= 0) { $minutes = 10; }
                    $bindQrToken = bin2hex(random_bytes(16));
                    $bindQrExpiresAt = date('Y-m-d H:i:s', time() + $minutes * 60);
                    LoginToken::createForBind($bindQrToken, $uid, $bindQrExpiresAt);
                    $bindQrPayload = json_encode([
                        'type' => 'bind',
                        'token' => $bindQrToken,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $bindQrUserId = $uid;
                    $success = '已为该用户生成绑定二维码，请在有效期内使用微信小程序扫码绑定。';
                }
            } elseif ($isAdmin && $action === 'user_seed_defaults') {
                $tab = 'users';
                $uid = (int)($_POST['id'] ?? 0);
                $target = User::findById($uid);
                if (!$target) {
                    $error = '用户不存在';
                } else {
                    // 仅在该用户分类/项目/账户均为空时注入默认数据，具体判断由 Seeder 内部完成
                    Seeder::seedIfEmpty($uid);
                    $success = '已尝试为该用户注入默认数据：如其分类/项目/账户均为空，则已创建一套初始化数据；如已有数据则不会做任何更改。';
                }
            } elseif ($isAdmin && $action === 'user_unbind_wechat') {
                // 管理员为指定用户解除微信绑定，便于用户更换微信后重新绑定
                $tab = 'users';
                $uid = (int)($_POST['id'] ?? 0);
                if ($uid <= 0) {
                    $error = '用户不存在';
                } elseif ($uid === $userId) {
                    // 防止管理员通过用户管理误解绑自己，引导去个人信息页操作
                    $error = '不能在用户管理列表中为当前登录账号解绑微信，如需解绑请在“个人信息”页操作。';
                } else {
                    UserWechatBinding::deleteByUserId($uid);
                    // 简单记录一下管理员操作日志
                    try {
                        $msg = sprintf('[admin:%d] unbind wechat for user:%d at %s', $userId, $uid, date('Y-m-d H:i:s'));
                        error_log($msg);
                    } catch (\Throwable $e) {
                        // 忽略日志异常
                    }
                    $success = '已为该用户解除微信绑定，如需继续在小程序使用，请提醒其重新登录或扫码绑定。';
                }
            } elseif ($action === 'self_generate_bind_qr') {
                // 普通用户在个人信息页生成自己的绑定二维码
                $tab = 'profile';
                // 若用户已通过小程序注册或已经有绑定记录，则不重复生成，提示并提供解绑入口
                $currentBinding = UserWechatBinding::findByUserId($userId);
                $hasWechatBinding = $currentBinding !== null;
                $registerSource = $currentUser['register_source'] ?? null;
                $isMiniappUser = $registerSource === 'miniapp';
                if ($registerSource === null && $currentBinding && !empty($currentUser['created_at']) && !empty($currentBinding['created_at'])) {
                    $uCreated = strtotime($currentUser['created_at']);
                    $bCreated = strtotime($currentBinding['created_at']);
                    if ($uCreated && $bCreated && abs(($bCreated - $uCreated) / 60) <= 5) {
                        $isMiniappUser = true;
                    }
                }

                if ($isMiniappUser || $hasWechatBinding) {
                    $success = $isMiniappUser
                        ? '您是通过小程序注册的账号，默认已绑定，无需重复绑定。如需更换微信，可先解绑后再在小程序中重新绑定。'
                        : '当前账号已绑定微信，无需重复绑定。如需更换微信，可先解绑后再在小程序中重新绑定。';
                    $openModal = '';
                } else {
                    $systemTmp = SystemSetting::get();
                    $minutes = (int)($systemTmp['bind_qr_expires_minutes'] ?? 10);
                    if ($minutes <= 0) { $minutes = 10; }
                    $token = bin2hex(random_bytes(16));
                    $expiresAt = date('Y-m-d H:i:s', time() + $minutes * 60);
                    LoginToken::createForBind($token, $userId, $expiresAt);
                    $selfBindQrToken = $token;
                    $selfBindQrExpiresAt = $expiresAt;
                    $selfBindQrPayload = json_encode([
                        'type' => 'bind',
                        'token' => $token,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $openModal = 'bindqr';
                    $success = '已生成绑定二维码，请在有效期内使用微信小程序扫码完成绑定。';
                }
            } elseif ($action === 'unbind_wechat') {
                // 解绑当前账号的微信绑定，便于更换微信账号后重新绑定
                $tab = 'profile';
                UserWechatBinding::deleteByUserId($userId);
                $success = '已解绑微信。如需继续在小程序使用，请在小程序中登录账号或在本页生成绑定二维码后重新扫码绑定。';
            } elseif ($action === 'update_theme') {
                $tab = 'profile';
                $mode = $_POST['theme_mode'] ?? 'light';
                if (!in_array($mode, ['light', 'dark'], true)) {
                    $mode = 'light';
                }
                User::updateThemeMode($userId, $mode);
                $_SESSION['theme_mode'] = $mode;
                $success = '主题模式已更新';
            } elseif ($action === 'ledger_create_shared') {
                // 在设置中心创建新的共享账本
                $tab = 'ledgers';
                $name = trim($_POST['ledger_name'] ?? '');
                $ledgerId = Ledger::createShared($userId, $name);
                if ($ledgerId === null) {
                    $error = '创建共享账本失败，请稍后再试。';
                } else {
                    // 默认切换到新建的共享账本
                    LedgerContext::setActiveLedgerId($userId, $ledgerId);
                    $success = '已创建共享账本，并切换为当前账本。';
                }
            } elseif ($action === 'ledger_set_active') {
                // 在设置中心切换当前账本
                $tab = 'ledgers';
                $ledgerId = (int)($_POST['ledger_id'] ?? 0);
                if ($ledgerId <= 0) {
                    $error = '请选择要切换的账本';
                } else {
                    $ok = LedgerContext::setActiveLedgerId($userId, $ledgerId);
                    if ($ok) {
                        $success = '当前账本已切换。';
                    } else {
                        $error = '无权切换到该账本，或账本不存在。';
                    }
                }
            } elseif ($action === 'ledger_regenerate_invite') {
                // 刷新共享账本的邀请码
                $tab = 'ledgers';
                $ledgerId = (int)($_POST['ledger_id'] ?? 0);
                if ($ledgerId <= 0) {
                    $error = '账本不存在';
                } else {
                    $code = Ledger::regenerateInviteCode($ledgerId, $userId);
                    if ($code === null) {
                        $error = '无权刷新该账本的邀请码，或账本不存在。';
                    } else {
                        $success = '邀请码已刷新，请重新分享新的二维码或邀请码。';
                    }
                }
            } elseif ($action === 'ledger_show_invite_qr') {
                // 为指定共享账本生成一次性的邀请二维码（基于当前邀请码）
                $tab = 'ledgers';
                $ledgerId = (int)($_POST['ledger_id'] ?? 0);
                if ($ledgerId <= 0) {
                    $error = '账本不存在';
                } else {
                    $ledger = Ledger::findById($ledgerId);
                    if (!$ledger || ($ledger['type'] ?? '') !== 'shared') {
                        $error = '仅支持为共享账本生成邀请二维码。';
                    } elseif (!((int)($ledger['owner_user_id'] ?? 0) === $userId || LedgerMember::isAdmin($ledgerId, $userId))) {
                        $error = '仅共享账本管理员可以生成邀请二维码。';
                    } elseif (empty($ledger['invite_code'])) {
                        $error = '当前账本尚未生成邀请码，请先刷新邀请码后再试。';
                    } else {
                        $ledgerInviteQrLedgerId = $ledgerId;
                        $ledgerInviteQrLedgerName = (string)($ledger['name'] ?? '共享账本');
                        $ledgerInviteCode = (string)$ledger['invite_code'];
                        // 与小程序端约定 payload 结构，便于扫码后识别加入账本
                        $ledgerInviteQrPayload = json_encode([
                            'type' => 'ledger_invite',
                            'code' => (string)$ledger['invite_code'],
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $openModal = 'ledger_invite_qr';
                        $success = '已生成账本邀请二维码，请在小程序中使用“扫码加入账本”进行扫描。';
                    }
                }
            } elseif ($action === 'ledger_delete_shared') {
                $tab = 'ledgers';
                $ledgerId = (int)($_POST['ledger_id'] ?? 0);
                if ($ledgerId <= 0) {
                    $error = '账本不存在';
                } else {
                    $ok = Ledger::deleteShared($ledgerId, $userId);
                    if ($ok) {
                        $success = '共享账本及其所有流水已删除。建议定期使用“记账明细”中的导出功能备份重要账本。';
                    } else {
                        $error = '删除失败：仅共享账本管理员可以删除，或账本不存在。';
                    }
                }
            } elseif ($action === 'ledger_show_members') {
                $tab = 'ledgers';
                $ledgerId = (int)($_POST['ledger_id'] ?? 0);
                if ($ledgerId <= 0) {
                    $error = '账本不存在';
                } else {
                    $ledger = Ledger::findById($ledgerId);
                    if (!$ledger || ($ledger['type'] ?? '') !== 'shared') {
                        $error = '仅支持查看共享账本成员。';
                    } else {
                        $ownerId = (int)($ledger['owner_user_id'] ?? 0);
                        $canManage = ($ownerId === $userId) || LedgerMember::isAdmin($ledgerId, $userId);
                        if (!$canManage) {
                            $error = '仅共享账本管理员可以管理成员。';
                        } else {
                            $ledgerMembersModalLedgerId = $ledgerId;
                            $ledgerMembersModalLedgerName = (string)($ledger['name'] ?? '共享账本');
                            $ledgerMembersOwnerUserId = $ownerId;
                            try {
                                $ledgerMembers = LedgerMember::listMembers($ledgerId);
                            } catch (\Throwable $e) {
                                $ledgerMembers = [];
                                $ledgerMembersModalError = '成员列表加载失败，请稍后重试。';
                            }
                            $openModal = 'ledger_members';
                        }
                    }
                }
            } elseif ($action === 'ledger_add_member') {
                $tab = 'ledgers';
                $ledgerId = (int)($_POST['ledger_id'] ?? 0);
                $keyword = trim($_POST['member_keyword'] ?? '');
                $pendingLedgerMemberKeyword = $keyword;
                if ($ledgerId <= 0) {
                    $ledgerMembersModalError = '账本不存在';
                } elseif ($keyword === '') {
                    $ledgerMembersModalError = '请输入成员邮箱或用户名';
                } else {
                    $ledger = Ledger::findById($ledgerId);
                    if (!$ledger || ($ledger['type'] ?? '') !== 'shared') {
                        $ledgerMembersModalError = '仅支持共享账本添加成员。';
                    } else {
                        $ownerId = (int)($ledger['owner_user_id'] ?? 0);
                        $canManage = ($ownerId === $userId) || LedgerMember::isAdmin($ledgerId, $userId);
                        if (!$canManage) {
                            $ledgerMembersModalError = '仅共享账本管理员可以添加成员。';
                        } else {
                            $target = null;
                            if (strpos($keyword, '@') !== false) {
                                $target = User::findByEmail($keyword);
                            } else {
                                $target = User::findByUsername($keyword);
                            }
                            if (!$target) {
                                $ledgerMembersModalError = '未找到该用户，请确认邮箱或用户名是否正确。';
                            } else {
                                $targetId = (int)($target['id'] ?? 0);
                                if ($targetId <= 0) {
                                    $ledgerMembersModalError = '未找到该用户，请确认邮箱或用户名是否正确。';
                                } else {
                                    $ok = false;
                                    try {
                                        $ok = LedgerMember::addMember($ledgerId, $userId, $targetId);
                                    } catch (\Throwable $e) {
                                        $ok = false;
                                    }
                                    if ($ok) {
                                        $ledgerMembersModalSuccess = '成员已添加。';
                                        $pendingLedgerMemberKeyword = '';
                                    } else {
                                        $ledgerMembersModalError = '添加失败：可能无权限、账本不存在，或该用户已在成员列表中。';
                                    }
                                }
                            }

                            $ledgerMembersModalLedgerId = $ledgerId;
                            $ledgerMembersModalLedgerName = (string)($ledger['name'] ?? '共享账本');
                            $ledgerMembersOwnerUserId = $ownerId;
                            try {
                                $ledgerMembers = LedgerMember::listMembers($ledgerId);
                            } catch (\Throwable $e) {
                                $ledgerMembers = [];
                            }
                            $openModal = 'ledger_members';
                        }
                    }
                }
            } elseif ($action === 'ledger_remove_member') {
                $tab = 'ledgers';
                $ledgerId = (int)($_POST['ledger_id'] ?? 0);
                $memberUserId = (int)($_POST['member_user_id'] ?? 0);
                if ($ledgerId <= 0 || $memberUserId <= 0) {
                    $ledgerMembersModalError = '参数错误，请重试。';
                } else {
                    $ledger = Ledger::findById($ledgerId);
                    if (!$ledger || ($ledger['type'] ?? '') !== 'shared') {
                        $ledgerMembersModalError = '仅支持共享账本移除成员。';
                    } else {
                        $ownerId = (int)($ledger['owner_user_id'] ?? 0);
                        $canManage = ($ownerId === $userId) || LedgerMember::isAdmin($ledgerId, $userId);
                        if (!$canManage) {
                            $ledgerMembersModalError = '仅共享账本管理员可以移除成员。';
                        } elseif ($memberUserId === $ownerId) {
                            $ledgerMembersModalError = '不能移除账本创建者。';
                        } elseif ($memberUserId === $userId) {
                            $ledgerMembersModalError = '不能在这里移除自己。';
                        } else {
                            $ok = false;
                            try {
                                $ok = LedgerMember::removeMember($ledgerId, $userId, $memberUserId);
                            } catch (\Throwable $e) {
                                $ok = false;
                            }
                            $ledgerMembersModalSuccess = $ok ? '成员已移除。' : '';
                            $ledgerMembersModalError = $ok ? '' : '移除失败：可能无权限、账本不存在，或该成员已不在列表中。';
                        }

                        $ledgerMembersModalLedgerId = $ledgerId;
                        $ledgerMembersModalLedgerName = (string)($ledger['name'] ?? '共享账本');
                        $ledgerMembersOwnerUserId = $ownerId;
                        try {
                            $ledgerMembers = LedgerMember::listMembers($ledgerId);
                        } catch (\Throwable $e) {
                            $ledgerMembers = [];
                        }
                        $openModal = 'ledger_members';
                    }
                }
            }
        }

        // 重新获取绑定状态以反映本次变更
        $currentBinding = UserWechatBinding::findByUserId($userId);
        $hasWechatBinding = $currentBinding !== null;
        $system = SystemSetting::get();
        $users = $isAdmin ? User::listAllForAdminSelect() : [];

        // 管理端：用户管理分页
        $usersPage = [];
        $usersPageTotal = 0;
        $usersPageSize = 10;
        $usersPageIndex = 1;
        $usersPageTotalPages = 1;
        if ($isAdmin && $tab === 'users') {
            $usersPageIndex = max(1, (int)($_GET['page'] ?? 1));
            $pageSize = (int)($_GET['page_size'] ?? 10);
            $allowedPageSizes = [10, 30, 50, 100];
            $usersPageSize = in_array($pageSize, $allowedPageSizes, true) ? $pageSize : 10;
            try {
                $usersPageTotal = User::countAll();
            } catch (\Throwable $e) {
                $usersPageTotal = 0;
            }
            $usersPageTotalPages = max(1, (int)ceil($usersPageTotal / $usersPageSize));
            if ($usersPageIndex > $usersPageTotalPages) {
                $usersPageIndex = $usersPageTotalPages;
            }
            try {
                $usersPage = User::listPage($usersPageIndex, $usersPageSize);
            } catch (\Throwable $e) {
                $usersPage = [];
            }
        }

        // 管理端：为用户管理表准备每个用户的账本列表（个人账本 + 共享账本）
        $userLedgers = [];
        if ($isAdmin && $tab === 'users' && !empty($usersPage)) {
            foreach ($usersPage as $u) {
                $uid = (int)($u['id'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                try {
                    $userLedgers[$uid] = Ledger::listForUser($uid);
                } catch (\Throwable $e) {
                    $userLedgers[$uid] = [];
                }
            }
        }

        // 账本信息：用于在设置中心展示和操作
        $ledgerMode = false;
        $activeLedgerId = 0;
        $activeLedger = null;
        $ledgers = [];
        try {
            $activeLedgerId = LedgerContext::requireActiveLedgerId($userId);
            if ($activeLedgerId > 0) {
                $ledgerMode = true;
                $ledgers = Ledger::listForUser($userId);
                foreach ($ledgers as $l) {
                    if ((int)($l['id'] ?? 0) === $activeLedgerId) {
                        $activeLedger = $l;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // 若数据库未升级或发生异常，则保持 ledgerMode=false 以兼容旧版本
        }

        // 管理端：处理到期但未发送的邮件推送任务，并准备公告/邮件推送列表
        $announcements = [];
        $emailPushes = [];
        if ($isAdmin) {
            // 轻量处理：每次进入设置页最多处理少量待发送任务
            try {
                EmailPush::processPending(3);
            } catch (\Throwable $e) {
                // 忽略后台定时任务错误，避免影响设置页打开
            }
            $announcements = Announcement::listAllWithViewCount();
            $emailPushes = EmailPush::listAll();
        }


        // API Token 管理
        $apiTokens = ApiToken::listByUser($userId);
        $newApiToken = null;
        $apiTokenError = '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['api_token_error'] ?? '') === 'limit') {
            $apiTokenError = '最多只能创建 3 个 API Token。';
            $openModal = 'api_token';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_api_token') {
            $description = trim((string)($_POST['token_description'] ?? ''));
            $currentCount = ApiToken::countByUser($userId);
            if ($currentCount >= 3) {
                $apiTokenError = '最多只能创建 3 个 API Token。';
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'error' => $apiTokenError]);
                    exit;
                }
                header('Location: /public/index.php?route=settings&tab=profile&openModal=api_token&api_token_error=limit');
                exit;
            }
            if ($description !== '') {
                $newApiToken = ApiToken::createToken($userId, $description);
                $apiTokens = ApiToken::listByUser($userId);
                if ($this->isAjax()) {
                    $this->json(['success' => true, 'token' => $newApiToken]);
                    exit;
                }
            } else {
                $apiTokenError = '请填写用途描述。';
                $openModal = 'api_token';
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'error' => $apiTokenError]);
                    exit;
                }
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_api_token') {
            $tokenId = (int)($_GET['id'] ?? 0);
            if ($tokenId > 0) {
                $token = ApiToken::findRawToken($tokenId, $userId);
                if ($token !== null) {
                    $this->json(['success' => true, 'token' => $token]);
                } else {
                    $this->json(['success' => false, 'error' => 'Token 不存在或已撤销']);
                }
            } else {
                $this->json(['success' => false, 'error' => '无效的 ID']);
            }
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revoke_api_token') {
            $tokenId = (int)($_POST['token_id'] ?? 0);
            if ($tokenId > 0) {
                ApiToken::revokeById($tokenId, $userId);
            }
            if ($this->isAjax()) {
                $this->json(['success' => true]);
                exit;
            }
            header('Location: /public/index.php?route=settings&tab=profile');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'batch_revoke_api_token') {
            $idsRaw = trim((string)($_POST['token_ids'] ?? ''));
            $ids = array_filter(array_map('intval', explode(',', $idsRaw)));
            $count = ApiToken::revokeByIds($ids, $userId);
            $this->json(['success' => true, 'count' => $count]);
            exit;
        }
        // 查询背景图历史记录
        $pdo = \App\Service\Database::getConnection();
        $bgImages = $pdo->query("SELECT id, file_path, created_at FROM bg_images ORDER BY created_at DESC LIMIT 20")->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('settings/index', [
            'tab' => $tab,
            'currentUser' => $currentUser,
            'isAdmin' => $isAdmin,
            'isMiniappUser' => $isMiniappUser,
            'hasWechatBinding' => $hasWechatBinding,
            'wechatBinding' => $currentBinding,
            'system' => $system,
            'users' => $users,
            'usersPage' => $usersPage,
            'usersPageTotal' => $usersPageTotal,
            'usersPageSize' => $usersPageSize,
            'usersPageIndex' => $usersPageIndex,
            'usersPageTotalPages' => $usersPageTotalPages,
            'announcements' => $announcements,
            'emailPushes' => $emailPushes,
            'bindQrUserId' => $bindQrUserId,
            'bindQrToken' => $bindQrToken,
            'bindQrPayload' => $bindQrPayload,
            'bindQrExpiresAt' => $bindQrExpiresAt,
            'selfBindQrToken' => $selfBindQrToken,
            'selfBindQrPayload' => $selfBindQrPayload,
            'selfBindQrExpiresAt' => $selfBindQrExpiresAt,
            'ledgerMode' => $ledgerMode,
            'activeLedgerId' => $activeLedgerId,
            'activeLedger' => $activeLedger,
            'ledgers' => $ledgers,
            'ledgerInviteQrLedgerId' => $ledgerInviteQrLedgerId,
            'ledgerInviteQrLedgerName' => $ledgerInviteQrLedgerName,
            'ledgerInviteQrPayload' => $ledgerInviteQrPayload,
            'ledgerInviteCode' => $ledgerInviteCode,
            'ledgerMembersModalLedgerId' => $ledgerMembersModalLedgerId,
            'ledgerMembersModalLedgerName' => $ledgerMembersModalLedgerName,
            'ledgerMembersOwnerUserId' => $ledgerMembersOwnerUserId,
            'ledgerMembers' => $ledgerMembers,
            'ledgerMembersModalError' => $ledgerMembersModalError,
            'ledgerMembersModalSuccess' => $ledgerMembersModalSuccess,
            'pendingLedgerMemberKeyword' => $pendingLedgerMemberKeyword,
            'userLedgers' => $userLedgers,
            'error' => $error,
            'success' => $success,
            'usernameModalError' => $usernameModalError,
            'usernameModalSuccess' => $usernameModalSuccess,
            'pendingUsername' => $pendingUsername,
            'emailModalError' => $emailModalError,
            'emailModalSuccess' => $emailModalSuccess,
            'pendingEmail' => $pendingEmail,
            'openModal' => $openModal,
            'apiTokens' => $apiTokens,
            'apiTokenError' => $apiTokenError,
            'newApiToken' => $newApiToken,
            'bgImages' => $bgImages,
            'miniapps' => $pdo->query("SELECT * FROM miniapps ORDER BY sort_order, id")->fetchAll(\PDO::FETCH_ASSOC) ?: [],
        ]);
    }

    public function toggleTheme(): void
    {
        $current = $_POST['current'] ?? 'light';
        $mode = ($current === 'dark') ? 'light' : 'dark';
        $_SESSION['theme_mode'] = $mode;

        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid > 0) {
            try {
                User::updateThemeMode($uid, $mode);
            } catch (\Throwable $e) {}
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['mode' => $mode], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
