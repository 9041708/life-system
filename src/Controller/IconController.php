<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\IconLibrary;
use App\Model\Category;
use App\Model\Item;
use App\Model\Account;
use App\Model\SystemIconLibrary;
use App\Model\SystemIconSubmission;
use App\Model\SystemIconChange;
use App\Model\SystemIconCleanupLog;
use App\Model\User;
use App\Service\Upload;

class IconController
{
    private static function detectSourceMode(?string $uploadDir, string $relativePath): string
    {
        $relativePath = trim($relativePath);
        if ($relativePath === '') {
            return 'upload';
        }
        if (preg_match('/\.svg$/i', $relativePath)) {
            return 'svg';
        }

        $uploadDir = is_string($uploadDir) ? trim($uploadDir) : '';
        if ($uploadDir === '') {
            return 'upload';
        }

        $safeRel = ltrim($relativePath, '/\\');
        $abs = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $safeRel);
        if (!is_file($abs)) {
            return 'upload';
        }

        try {
            $fp = @fopen($abs, 'rb');
            if (!$fp) {
                return 'upload';
            }
            $buf = (string)@fread($fp, 2048);
            @fclose($fp);
            if ($buf === '' || strpos($buf, "\0") !== false) {
                return 'upload';
            }
            if (preg_match('/<svg\b/i', $buf)) {
                return 'svg';
            }
        } catch (\Throwable $e) {
            return 'upload';
        }
        return 'upload';
    }

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

        $submitStatusFilter = trim((string)($_GET['submit_status'] ?? ''));
        $allowedFilters = ['all', 'unsubmitted', 'pending', 'approved', 'rejected'];
        if ($submitStatusFilter === '') {
            $submitStatusFilter = 'all';
        }
        if (!in_array($submitStatusFilter, $allowedFilters, true)) {
            $submitStatusFilter = 'all';
        }

        $syncChangeList = [];
        $cleanupLogs = [];
        $hasSystemIconUpdates = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    $error = '图标名称不能为空';
                } else {
                    $savedPath = !empty($_FILES['icon_file']) ? Upload::saveAttachment($userId, $_FILES['icon_file']) : null;
                    if (!$savedPath) {
                        $error = '图标上传失败，请选择有效的图片文件（小于 10MB）。';
                    } else {
                        IconLibrary::create($userId, $name, $savedPath);
                        if (!empty($_POST['submit_to_system'])) {
                            try {
                                SystemIconSubmission::createIfNotOpen($userId, $name, $savedPath);
                                $success = '新增图标成功，已提交公共图标库，等待管理员审核。';
                            } catch (\Throwable $e) {
                                $success = '新增图标成功。';
                                $error = '提交公共图标库失败（可能尚未创建 system_icon_submissions 表）。';
                            }
                        } else {
                        $success = '新增图标成功';
                        }
                    }
                }
            } elseif ($action === 'init_from_existing') {
                $processed = 0;

                // 从分类中导入
                $categories = Category::allByUser($userId);
                foreach ($categories as $c) {
                    $iconType = $c['icon_type'] ?? null;
                    $iconValue = trim((string)($c['icon_value'] ?? ''));
                    if ($iconType === 'file' && $iconValue !== '') {
                        IconLibrary::ensureExists($userId, $iconValue, '分类-' . ($c['name'] ?? '未命名'));
                        $processed++;
                    }
                }

                // 从项目中导入
                $items = Item::allByUser($userId);
                foreach ($items as $i) {
                    $iconType = $i['icon_type'] ?? null;
                    $iconValue = trim((string)($i['icon_value'] ?? ''));
                    if ($iconType === 'file' && $iconValue !== '') {
                        IconLibrary::ensureExists($userId, $iconValue, '项目-' . ($i['name'] ?? '未命名'));
                        $processed++;
                    }
                }

                // 从账户中导入
                $accounts = Account::allByUser($userId);
                foreach ($accounts as $a) {
                    $iconType = $a['icon_type'] ?? null;
                    $iconValue = trim((string)($a['icon_value'] ?? ''));
                    if ($iconType === 'file' && $iconValue !== '') {
                        $defaultName = '账户-' . ($a['group_name'] ?? '') . '-' . ($a['name'] ?? '未命名');
                        IconLibrary::ensureExists($userId, $iconValue, $defaultName);
                        $processed++;
                    }
                }

                if ($processed === 0) {
                    $success = '没有在现有分类/项目/账户中找到已上传的文件图标，无需导入。';
                } else {
                    $success = '已扫描现有分类/项目/账户中的文件图标，并尝试写入图标库，共处理 ' . $processed . ' 条记录（已存在路径会自动跳过）。';
                }
            } elseif ($action === 'bulk_submit_to_system') {
                $icons = IconLibrary::allByUser($userId);
                $submitted = 0;
                $skipped = 0;
                try {
                    $openPaths = SystemIconSubmission::listOpenFilePathsByUser($userId);
                } catch (\Throwable $e) {
                    $openPaths = [];
                }

                foreach ($icons as $icon) {
                    $path = (string)($icon['file_path'] ?? '');
                    $isSystem = !empty($icon['system_icon_id']);
                    if ($isSystem || $path === '') {
                        continue;
                    }
                    if (!empty($openPaths[$path])) {
                        $skipped++;
                        continue;
                    }
                    $id = SystemIconSubmission::createIfNotOpen($userId, (string)($icon['name'] ?? '图标'), $path);
                    if ($id) {
                        $submitted++;
                        $openPaths[$path] = true;
                    }
                }

                if ($submitted > 0) {
                    $success = '已一键提交 ' . $submitted . ' 个未提交/已驳回图标到系统图标库审核。';
                } else {
                    $success = '当前没有可提交的未提交/已驳回图标（或已全部提交且待审核/已通过）。';
                }
            } elseif ($action === 'sync_system') {
                $beforeChangeId = 0;
                try {
                    $beforeChangeId = User::getSystemIconLastSyncChangeId($userId);
                } catch (\Throwable $e) {
                    $beforeChangeId = 0;
                }

                $changes = SystemIconChange::listSinceId($beforeChangeId, 200);

                // 从系统统一图标库拉取到当前用户图标库（支持更新与删除）
                $systemIcons = SystemIconLibrary::all();
                $created = 0;
                $updated = 0;
                $touched = 0;

                foreach ($systemIcons as $si) {
                    $sid = (int)($si['id'] ?? 0);
                    $name = (string)($si['name'] ?? '系统图标');
                    $path = trim((string)($si['file_path'] ?? ''));
                    if ($sid <= 0 || $path === '') {
                        continue;
                    }

                    $touched++;
                    $existing = IconLibrary::findByUserAndSystemIconId($userId, $sid);
                    if ($existing) {
                        $needUpdate = ((string)($existing['name'] ?? '') !== $name) || ((string)($existing['file_path'] ?? '') !== $path);
                        if ($needUpdate) {
                            IconLibrary::updateNameAndPathAndSystemId($userId, (int)$existing['id'], $name, $path, $sid);
                            $updated++;
                        }
                        continue;
                    }

                    // 兼容旧版本：曾用 ensureExists 按 file_path 导入过的系统图标（无 system_icon_id）
                    $byPath = IconLibrary::findByUserAndFilePath($userId, $path);
                    if ($byPath && empty($byPath['system_icon_id'])) {
                        IconLibrary::updateNameAndPathAndSystemId($userId, (int)$byPath['id'], $name, $path, $sid);
                        $updated++;
                        continue;
                    }

                    IconLibrary::createSystem($userId, $sid, $name, $path);
                    $created++;
                }

                // 删除：当前用户图标库中已绑定 system_icon_id 但系统库已不存在的记录
                $deleted = 0;
                try {
                    $pdo = \App\Service\Database::getConnection();
                    $stmt = $pdo->prepare('SELECT id, name, file_path FROM icon_library WHERE user_id = :uid AND system_icon_id IS NOT NULL AND system_icon_id NOT IN (SELECT id FROM system_icon_library)');
                    $stmt->execute([':uid' => $userId]);
                    $toDel = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($toDel as $r) {
                        $deleted++;
                        $syncChangeList[] = '删除：' . (string)($r['name'] ?? '系统图标');
                    }
                    $del = $pdo->prepare('DELETE FROM icon_library WHERE user_id = :uid AND system_icon_id IS NOT NULL AND system_icon_id NOT IN (SELECT id FROM system_icon_library)');
                    $del->execute([':uid' => $userId]);
                } catch (\Throwable $e) {
                    // ignore
                }

                $latestChangeId = SystemIconChange::latestId();
                try {
                    User::setSystemIconLastSyncChangeId($userId, $latestChangeId);
                } catch (\Throwable $e) {}

                // 变更清单（以系统变更记录为准，若表不存在则为空）
                if (!empty($changes)) {
                    foreach ($changes as $c) {
                        $act = (string)($c['action'] ?? '');
                        $nm = (string)($c['name'] ?? '');
                        if ($nm === '') {
                            $nm = '系统图标';
                        }
                        if ($act === 'create') {
                            $syncChangeList[] = '新增：' . $nm;
                        } elseif ($act === 'update') {
                            $syncChangeList[] = '更新：' . $nm;
                        } elseif ($act === 'delete') {
                            $syncChangeList[] = '删除：' . $nm;
                        }
                    }
                }

                if ($touched > 0) {
                    $success = '系统图标更新完成：新增 ' . $created . '，更新 ' . $updated . '，删除 ' . $deleted . '。';
                } else {
                    $success = '系统统一图标库当前为空，无需同步。';
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $error = '参数错误，未指定要删除的图标。';
                } else {
                    if (IconLibrary::delete($userId, $id)) {
                        $success = '已从图标库中删除该图标记录（不会删除实际文件）。';
                    } else {
                        $error = '删除失败，该图标可能不存在或不属于当前用户。';
                    }
                }
            }
        }

        $allIcons = IconLibrary::allByUser($userId);

        // 分页设置
        $perPage = 30;
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $totalIcons = count($allIcons);
        $totalPages = max(1, (int)ceil($totalIcons / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $icons = array_slice($allIcons, $offset, $perPage);

        // 系统图标删除联动提示（管理员删除时写入日志；用户进入图标库时提示并标记已读）
        try {
            $cleanupLogs = SystemIconCleanupLog::listUnread($userId, 50);
            if (!empty($cleanupLogs)) {
                SystemIconCleanupLog::markAllRead($userId);
            }
        } catch (\Throwable $e) {
            $cleanupLogs = [];
        }

        // 是否存在系统图标更新
        try {
            $latestChangeId = SystemIconChange::latestId();
            $lastSync = User::getSystemIconLastSyncChangeId($userId);
            $hasSystemIconUpdates = $latestChangeId > $lastSync;
        } catch (\Throwable $e) {
            $hasSystemIconUpdates = false;
        }

        // 已提交状态集合
        try {
            $openSubmissionPaths = SystemIconSubmission::listOpenFilePathsByUser($userId);
        } catch (\Throwable $e) {
            $openSubmissionPaths = [];
        }

        // 每个图标的最新提交状态/备注（pending/approved/rejected）
        try {
            $latestSubmissionMetaByPath = SystemIconSubmission::listLatestMetaByUser($userId);
        } catch (\Throwable $e) {
            $latestSubmissionMetaByPath = [];
        }

        $latestSubmissionStatusByPath = [];
        if (!empty($latestSubmissionMetaByPath)) {
            foreach ($latestSubmissionMetaByPath as $p => $meta) {
                if (!is_string($p) || $p === '') continue;
                $latestSubmissionStatusByPath[$p] = (string)($meta['status'] ?? '');
            }
        }

        // 提交状态筛选：只过滤个人图标（系统图标不参与筛选）
        if ($submitStatusFilter !== 'all' && !empty($icons)) {
            $filtered = [];
            foreach ($icons as $icon) {
                $isSystem = !empty($icon['system_icon_id']);
                if ($isSystem) {
                    continue;
                }
                $path = (string)($icon['file_path'] ?? '');
                $status = ($path !== '' && isset($latestSubmissionStatusByPath[$path])) ? (string)$latestSubmissionStatusByPath[$path] : '';
                if ($submitStatusFilter === 'unsubmitted') {
                    if ($status === '') {
                        $filtered[] = $icon;
                    }
                } else {
                    if ($status === $submitStatusFilter) {
                        $filtered[] = $icon;
                    }
                }
            }
            $icons = $filtered;
        }

        // 来源识别：用于“来源”列展示（svg/upload）
        $uploadDir = null;
        try {
            $uploadDir = Config::get('app.upload_dir');
        } catch (\Throwable $e) {
            $uploadDir = null;
        }
        if (!empty($icons)) {
            foreach ($icons as &$ic) {
                $p = (string)($ic['file_path'] ?? '');
                $ic['_source_mode'] = self::detectSourceMode($uploadDir, $p);
            }
            unset($ic);
        }

        $this->render('icons/index', compact('icons', 'error', 'success', 'openSubmissionPaths', 'latestSubmissionStatusByPath', 'latestSubmissionMetaByPath', 'syncChangeList', 'cleanupLogs', 'hasSystemIconUpdates', 'submitStatusFilter', 'currentPage', 'totalPages', 'totalIcons'));
    }
}
