<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\Subscription;
use App\Model\IconLibrary;
use App\Model\SystemIconLibrary;
use App\Model\SystemIconSubmission;
use App\Service\Upload;

class SubscriptionController
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

    public function index(): void
    {
        $userId = $this->requireLogin();
        $error = '';
        $success = '';

        // 进入页面时执行到期和 30 天清理逻辑
        Subscription::cleanupExpired($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $platform = trim($_POST['platform'] ?? '');
                $type = $_POST['type'] === 'lifetime' ? 'lifetime' : 'subscription';
                $price = (float)($_POST['price'] ?? 0);
                $expireDate = trim($_POST['expire_date'] ?? '');
                $autoRenew = !empty($_POST['auto_renew']);
                $period = $type === 'subscription' ? ($_POST['period'] ?? null) : null;
                $remark = trim($_POST['remark'] ?? '');

                if ($platform === '' || $price <= 0 || ($type === 'subscription' && $expireDate === '')) {
                    $error = '请填写平台名称、价格，以及订阅类型下的到期日期。';
                } else {
                    $iconType = null;
                    $iconValue = null;
                    $iconMode = $_POST['icon_mode'] ?? 'none';

                    if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                        $savedPath = Upload::saveAttachment($userId, $_FILES['icon_file']);
                        if ($savedPath) {
                            $iconType = 'file';
                            $iconValue = $savedPath;
                            IconLibrary::ensureExists($userId, $savedPath, $platform ?: '订阅图标');
                            if (!empty($_POST['submit_to_system'])) {
                                try {
                                    SystemIconSubmission::createIfNotOpen($userId, $platform ?: '订阅图标', $savedPath);
                                } catch (\Throwable $e) {}
                            }
                        }
                    } elseif ($iconMode === 'library') {
                        $libId = (int)($_POST['icon_library_id'] ?? 0);
                        $systemIconId = (int)($_POST['system_icon_id'] ?? 0);
                        if ($libId > 0) {
                            $icon = IconLibrary::findByUser($userId, $libId);
                            if ($icon) {
                                $iconType = 'file';
                                $iconValue = $icon['file_path'] ?? null;
                            }
                        } elseif ($systemIconId > 0) {
                            $systemIcon = SystemIconLibrary::findById($systemIconId);
                            if ($systemIcon) {
                                $iconType = 'file';
                                $iconValue = $systemIcon['file_path'] ?? null;
                            }
                        }
                    } elseif ($iconMode === 'clear') {
                        $iconType = null;
                        $iconValue = null;
                    } elseif ($action === 'update') {
                        $current = Subscription::findByUser($userId, $id);
                        if ($current) {
                            $iconType = $current['icon_type'] ?? null;
                            $iconValue = $current['icon_value'] ?? null;
                        }
                    }

                    try {
                        $expire = $type === 'subscription' ? $expireDate : null;
                        if ($action === 'create') {
                            Subscription::create($userId, $platform, $type, $price, $expire, $autoRenew, $period, $iconType, $iconValue, $remark !== '' ? $remark : null);
                            $success = '新增订阅记录成功';
                        } else {
                            if (Subscription::update($userId, $id, $platform, $type, $price, $expire, $autoRenew, $period, $iconType, $iconValue, $remark !== '' ? $remark : null)) {
                                $success = '订阅记录已更新';
                            } else {
                                $error = '订阅记录更新失败或不存在。';
                            }
                        }
                    } catch (\Throwable $e) {
                        $error = '保存订阅记录时发生错误，请稍后重试。';
                    }
                }
            } elseif ($action === 'renew') {
                $id = (int)($_POST['id'] ?? 0);
                $type = $_POST['type'] === 'lifetime' ? 'lifetime' : 'subscription';
                $price = (float)($_POST['price'] ?? 0);
                $expireDate = trim($_POST['expire_date'] ?? '');
                $autoRenew = !empty($_POST['auto_renew']);
                $period = $type === 'subscription' ? ($_POST['period'] ?? null) : null;
                if ($id <= 0 || $price <= 0 || ($type === 'subscription' && $expireDate === '')) {
                    $error = '请填写正确的续费金额和到期日期。';
                } else {
                    $expire = $type === 'subscription' ? $expireDate : null;
                    if (Subscription::renew($userId, $id, $type, $price, $expire, $autoRenew, $period)) {
                        $success = '续费信息已更新';
                    } else {
                        $error = '续费更新失败或记录不存在。';
                    }
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    if (Subscription::logicalDelete($userId, $id)) {
                        $success = '订阅记录已关闭。系统会在到期 30 天后自动清理数据。';
                    } else {
                        $error = '关闭失败，记录可能不存在。';
                    }
                }
            }
        }

        $keyword = trim($_GET['q'] ?? '');
        $tab = $_GET['tab'] ?? 'subscription';
        if (!in_array($tab, ['subscription', 'lifetime', 'expired'], true)) {
            $tab = 'subscription';
        }

        $subscriptions = Subscription::allActiveByUser($userId, $keyword);

        $today = new \DateTimeImmutable('today');
        foreach ($subscriptions as &$row) {
            $expireDate = !empty($row['expire_date']) ? new \DateTimeImmutable($row['expire_date']) : null;
            if ($expireDate) {
                // 正数表示还有多少天到期；负数表示已超期多少天
                $diff = $today->diff($expireDate);
                $days = (int)$diff->format('%r%a');
                $row['days_left'] = $days;
            } else {
                $row['days_left'] = null;
            }
        }
        unset($row);

        $iconLibrary = IconLibrary::allByUser($userId);
        $systemIcons = SystemIconLibrary::all();

        $this->render('subscriptions/index', [
            'error' => $error,
            'success' => $success,
            'keyword' => $keyword,
            'tab' => $tab,
            'subscriptions' => $subscriptions,
            'iconLibrary' => $iconLibrary,
            'systemIcons' => $systemIcons,
        ]);
    }
}
