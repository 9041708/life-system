<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\LedgerContext;
use App\Service\Logger;
use App\Model\Category;
use App\Model\IconLibrary;
use App\Model\SystemIconSubmission;
use App\Model\User;

class CategoryController
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
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }
        $currentUser = User::findById($userId);
        $transferEnabled = !empty($currentUser['enable_transfer']);
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $type = $_POST['type'] ?? 'expense';
                $name = trim($_POST['name'] ?? '');
                $sort = (int)($_POST['sort_order'] ?? 0);
                $iconType = null;
                $iconValue = null;

                $iconMode = $_POST['icon_mode'] ?? 'none';
                if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                    $savedPath = \App\Service\Upload::saveAttachment($userId, $_FILES['icon_file']);
                    if ($savedPath) {
                        $iconType = 'file';
                        $iconValue = $savedPath;
                        // 新上传的图标自动加入图标库，名称默认使用分类名
                        IconLibrary::ensureExists($userId, $savedPath, $name ?: '分类图标');
                        if (!empty($_POST['submit_to_system'])) {
                            try {
                                SystemIconSubmission::createIfNotOpen($userId, $name ?: '分类图标', $savedPath);
                            } catch (\Throwable $e) {}
                        }
                    }
                } elseif ($iconMode === 'library') {
                    $libId = (int)($_POST['icon_library_id'] ?? 0);
                    if ($libId > 0) {
                        $icon = IconLibrary::findByUser($userId, $libId);
                        if ($icon) {
                            $iconType = 'file';
                            $iconValue = $icon['file_path'] ?? null;
                        }
                    }
                }

                if ($name === '') {
                    $error = '名称不能为空';
                } else {
                    if ($ledgerId > 0) {
                        Category::createForLedger($userId, $ledgerId, $type, $name, $sort, $iconType, $iconValue);
                    } else {
                        Category::create($userId, $type, $name, $sort, $iconType, $iconValue);
                    }
                    $success = '新增分类成功';
                    Logger::log('创建分类', "创建分类：{$name}（{$type}）", $userId, $_SESSION['user_nickname'] ?? null);
                }
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $sort = (int)($_POST['sort_order'] ?? 0);
                $iconType = null;
                $iconValue = null;

                $iconMode = $_POST['icon_mode'] ?? 'none';
                $current = $ledgerId > 0
                    ? Category::findByLedger($ledgerId, $id)
                    : Category::findByUser($userId, $id);

                if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                    $savedPath = \App\Service\Upload::saveAttachment($userId, $_FILES['icon_file']);
                    if ($savedPath) {
                        $iconType = 'file';
                        $iconValue = $savedPath;
                        IconLibrary::ensureExists($userId, $savedPath, $name ?: '分类图标');
                        if (!empty($_POST['submit_to_system'])) {
                            try {
                                SystemIconSubmission::createIfNotOpen($userId, $name ?: '分类图标', $savedPath);
                            } catch (\Throwable $e) {}
                        }
                    } elseif ($current) {
                        $iconType = $current['icon_type'] ?? null;
                        $iconValue = $current['icon_value'] ?? null;
                    }
                } elseif ($iconMode === 'library') {
                    $libId = (int)($_POST['icon_library_id'] ?? 0);
                    if ($libId > 0) {
                        $icon = IconLibrary::findByUser($userId, $libId);
                        if ($icon) {
                            $iconType = 'file';
                            $iconValue = $icon['file_path'] ?? null;
                        }
                    }
                    if (!$iconType && $current) {
                        $iconType = $current['icon_type'] ?? null;
                        $iconValue = $current['icon_value'] ?? null;
                    }
                } elseif ($iconMode === 'clear') {
                    $iconType = null;
                    $iconValue = null;
                } else { // none: 保持不变
                    if ($current) {
                        $iconType = $current['icon_type'] ?? null;
                        $iconValue = $current['icon_value'] ?? null;
                    }
                }

                if ($name === '') {
                    $error = '名称不能为空';
                } else {
                    if ($ledgerId > 0) {
                        Category::updateForLedger($ledgerId, $id, $name, $sort, $iconType, $iconValue);
                    } else {
                        Category::update($userId, $id, $name, $sort, $iconType, $iconValue);
                    }
                    $success = '更新分类成功';
                    Logger::log('更新分类', "更新分类 #{$id}：{$name}", $userId, $_SESSION['user_nickname'] ?? null);
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $ok = $ledgerId > 0
                    ? Category::deleteForLedger($ledgerId, $id)
                    : Category::delete($userId, $id);
                if (!$ok) {
                    $error = '该分类已有记账数据，无法删除';
                } else {
                    $success = '删除分类成功';
                    Logger::log('删除分类', "删除分类 #{$id}", $userId, $_SESSION['user_nickname'] ?? null);
                }
            }
        }

        // 分类列表：共享账本下只管理当前账本内的分类，个人模式下管理用户级分类
        if ($ledgerId > 0) {
            $categories = Category::allByLedger($ledgerId);
        } else {
            $categories = Category::allByUser($userId);
        }
        $iconLibrary = IconLibrary::allByUser($userId);
        $this->render('categories/index', compact('categories', 'iconLibrary', 'error', 'success', 'ledgerId', 'transferEnabled'));
    }
}
