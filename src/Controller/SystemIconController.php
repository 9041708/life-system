<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\User;
use App\Model\SystemIconLibrary;
use App\Model\SystemIconSubmission;
use App\Model\IconLibrary;
use App\Model\SystemIconChange;
use App\Model\SystemIconCleanupLog;
use App\Service\Upload;

class SystemIconController
{
    private function detectSourceModeFromPath(?string $path): string
    {
        $path = (string)$path;
        if ($path !== '' && preg_match('/\\.svg$/i', $path)) {
            return 'svg';
        }
        return 'upload';
    }

    private function safeDeleteUploadsFileIfUnreferenced(?string $relativePath): void
    {
        $relativePath = trim((string)$relativePath);
        if ($relativePath === '') {
            return;
        }

        try {
            $pdo = \App\Service\Database::getConnection();

            $cnt1 = 0;
            $cnt2 = 0;
            $cnt3 = 0;
            try {
                $st1 = $pdo->prepare('SELECT COUNT(*) FROM system_icon_library WHERE file_path = :p');
                $st1->execute([':p' => $relativePath]);
                $cnt1 = (int)$st1->fetchColumn();
            } catch (\Throwable $e) {}

            try {
                $st2 = $pdo->prepare('SELECT COUNT(*) FROM icon_library WHERE file_path = :p');
                $st2->execute([':p' => $relativePath]);
                $cnt2 = (int)$st2->fetchColumn();
            } catch (\Throwable $e) {}

            try {
                $st3 = $pdo->prepare('SELECT COUNT(*) FROM system_icon_submissions WHERE file_path = :p AND status = \'pending\'');
                $st3->execute([':p' => $relativePath]);
                $cnt3 = (int)$st3->fetchColumn();
            } catch (\Throwable $e) {}

            if (($cnt1 + $cnt2 + $cnt3) <= 0) {
                Upload::deleteByRelativePath($relativePath);
            }
        } catch (\Throwable $e) {
            // ignore
        }
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

    private function requireAdmin(int $userId): void
    {
        $u = User::findById($userId);
        $isAdmin = ($u['role'] ?? 'user') === 'admin';
        if (!$isAdmin) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
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
        $this->requireAdmin($userId);

        $error = '';
        $success = '';
        $openModal = '';

        $perPage = 10;
        $iconPage = max(1, (int)($_GET['icon_page'] ?? 1));
        $pendingPage = max(1, (int)($_GET['pending_page'] ?? 1));
        $iconSearch = trim((string)($_GET['icon_search'] ?? ''));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'system_icon_create') {
                $name = trim($_POST['system_icon_name'] ?? '');
                $svg = trim($_POST['system_icon_svg'] ?? '');

                if ($name === '') {
                    $error = '图标名称不能为空';
                    $openModal = 'create';
                } else {
                    $savedPath = null;
                    if (!empty($_FILES['system_icon_file']) && is_array($_FILES['system_icon_file']) && ($_FILES['system_icon_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $savedPath = Upload::saveAttachment($userId, $_FILES['system_icon_file']);
                    } elseif ($svg !== '') {
                        $savedPath = Upload::saveTextFile($userId, $svg, 'svg', 'sysicon_');
                    }

                    if (!$savedPath) {
                        $error = '图标内容为空或保存失败，请上传文件或粘贴 SVG。';
                        $openModal = 'create';
                    } else {
                        $sourceMode = $svg !== '' ? 'svg' : $this->detectSourceModeFromPath($savedPath);
                        $newId = SystemIconLibrary::create($name, $savedPath, 'admin', $sourceMode);
                        SystemIconChange::record('create', $newId > 0 ? $newId : null, $name, $savedPath);
                        $success = '已新增系统图标。';
                    }
                }

            } elseif ($action === 'system_icon_update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['system_icon_name'] ?? '');
                $svg = trim($_POST['system_icon_svg'] ?? '');

                if ($id <= 0) {
                    $error = '参数错误，未指定要修改的图标。';
                } elseif ($name === '') {
                    $error = '图标名称不能为空';
                } else {
                    $before = null;
                    try {
                        $before = SystemIconLibrary::findById($id);
                    } catch (\Throwable $e) {
                        $before = null;
                    }

                    $savedPath = null;
                    if (!empty($_FILES['system_icon_file']) && is_array($_FILES['system_icon_file']) && ($_FILES['system_icon_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $savedPath = Upload::saveAttachment($userId, $_FILES['system_icon_file']);
                    } elseif ($svg !== '') {
                        $savedPath = Upload::saveTextFile($userId, $svg, 'svg', 'sysicon_');
                    }

                    $ok = false;
                    if ($savedPath) {
                        $ok = SystemIconLibrary::update($id, $name, $savedPath);
                    } else {
                        $ok = SystemIconLibrary::update($id, $name, null);
                    }

                    if ($ok) {
                        $newPath = $savedPath ?: (string)($before['file_path'] ?? null);
                        SystemIconChange::record('update', $id, $name, $newPath);
                        $success = '系统图标已更新。';
                    } else {
                        $error = '更新失败，记录可能不存在。';
                    }
                }

            } elseif ($action === 'system_icon_delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $error = '参数错误，未指定要删除的图标。';
                } else {
                    $row = null;
                    try {
                        $row = SystemIconLibrary::findById($id);
                    } catch (\Throwable $e) {
                        $row = null;
                    }

                    if (SystemIconLibrary::delete($id)) {
                        // 联动删除用户图标库中的系统图标，并提示用户
                        $affected = [];
                        try {
                            $affected = IconLibrary::deleteAllBySystemIconId($id);
                        } catch (\Throwable $e) {
                            $affected = [];
                        }

                        // 兜底：旧版本可能未写 system_icon_id，仅按路径导入过
                        $pathFallback = $row ? (string)($row['file_path'] ?? '') : '';
                        if ($pathFallback !== '') {
                            try {
                                $affected2 = IconLibrary::deleteAllByFilePath($pathFallback);
                                if (!empty($affected2)) {
                                    $affected = array_merge($affected, $affected2);
                                }
                            } catch (\Throwable $e) {}
                        }

                        if (!empty($affected)) {
                            foreach ($affected as $a) {
                                $uid = (int)($a['user_id'] ?? 0);
                                if ($uid > 0) {
                                    SystemIconCleanupLog::add($uid, (string)($a['name'] ?? null), (string)($a['file_path'] ?? null));
                                }
                            }
                        }

                        $name = $row ? (string)($row['name'] ?? '') : null;
                        $path = $row ? (string)($row['file_path'] ?? '') : null;
                        SystemIconChange::record('delete', $id, $name, $path);

                        // 尽量删除无引用的文件
                        $this->safeDeleteUploadsFileIfUnreferenced($path);

                        $success = '已删除系统图标（已联动清理用户图标库引用；若文件无引用将一并删除）。';
                    } else {
                        $error = '删除失败，记录可能不存在。';
                    }
                }
            } elseif ($action === 'system_icon_submission_publish' || $action === 'system_icon_submission_replace' || $action === 'system_icon_submission_reject') {
                $sid = (int)($_POST['submission_id'] ?? 0);
                if ($sid <= 0) {
                    $error = '参数错误，未指定要审核的提交记录。';
                } else {
                    $sub = SystemIconSubmission::findById($sid);
                    if (!$sub || (string)($sub['status'] ?? '') !== 'pending') {
                        $error = '提交记录不存在或已审核。';
                    } else {
                        $name = trim((string)($sub['name'] ?? ''));
                        $path = trim((string)($sub['file_path'] ?? ''));

                        if ($action === 'system_icon_submission_reject') {
                            $note = trim((string)($_POST['note'] ?? ''));
                            $note = $note !== '' ? $note : null;
                            if (SystemIconSubmission::markReviewed($sid, 'rejected', $userId, null, $note)) {
                                $success = '已驳回该提交。';
                            } else {
                                $error = '驳回失败，该记录可能已被处理。';
                            }
                        } elseif ($name === '' || $path === '') {
                            $error = '提交记录数据不完整，无法审核。';
                        } else {
                            $existing = SystemIconLibrary::findByName($name);
                            if ($action === 'system_icon_submission_publish') {
                                if ($existing) {
                                    $error = '系统图标库已存在同名图标，请使用“替换同名”审核操作。';
                                } else {
                                    $sourceMode = $this->detectSourceModeFromPath($path);
                                    $newId = SystemIconLibrary::create($name, $path, 'user', $sourceMode);
                                    if ($newId > 0) {
                                        SystemIconLibrary::setSource($newId, 'user', $sourceMode);
                                    }
                                    SystemIconChange::record('create', $newId > 0 ? $newId : null, $name, $path);
                                    if (SystemIconSubmission::markReviewed($sid, 'approved', $userId, 'publish', null)) {
                                        $success = '已公开入库该图标。';
                                    } else {
                                        $success = '已公开入库该图标（提交记录状态更新失败，请刷新确认）。';
                                    }
                                }
                            } else {
                                // replace
                                if ($existing) {
                                    SystemIconLibrary::update((int)$existing['id'], $name, $path);
                                    $sourceMode = $this->detectSourceModeFromPath($path);
                                    SystemIconLibrary::setSource((int)$existing['id'], 'user', $sourceMode);
                                    SystemIconChange::record('update', (int)$existing['id'], $name, $path);
                                } else {
                                    $sourceMode = $this->detectSourceModeFromPath($path);
                                    $newId = SystemIconLibrary::create($name, $path, 'user', $sourceMode);
                                    if ($newId > 0) {
                                        SystemIconLibrary::setSource($newId, 'user', $sourceMode);
                                    }
                                    SystemIconChange::record('create', $newId > 0 ? $newId : null, $name, $path);
                                }
                                if (SystemIconSubmission::markReviewed($sid, 'approved', $userId, 'replace', null)) {
                                    $success = $existing ? '已替换同名系统图标。' : '系统无同名图标，已按公开入库处理。';
                                } else {
                                    $success = '已处理该提交（提交记录状态更新失败，请刷新确认）。';
                                }
                            }
                        }
                    }
                }
            } elseif ($action === 'system_icon_submission_bulk') {
                $ids = $_POST['submission_ids'] ?? [];
                if (!is_array($ids)) {
                    $ids = [];
                }
                $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
                $bulkAction = (string)($_POST['bulk_action'] ?? '');
                $bulkNote = trim((string)($_POST['bulk_note'] ?? ''));
                $bulkNote = $bulkNote !== '' ? $bulkNote : null;

                if (empty($ids)) {
                    $error = '请先勾选要批量审核的提交记录。';
                } elseif (!in_array($bulkAction, ['publish', 'replace', 'reject'], true)) {
                    $error = '批量操作类型无效。';
                } else {
                    $ok = 0;
                    $fail = 0;
                    foreach ($ids as $sid) {
                        try {
                            $sub = SystemIconSubmission::findById((int)$sid);
                            if (!$sub || (string)($sub['status'] ?? '') !== 'pending') {
                                $fail++;
                                continue;
                            }

                            $name = trim((string)($sub['name'] ?? ''));
                            $path = trim((string)($sub['file_path'] ?? ''));
                            if ($name === '' || $path === '') {
                                $fail++;
                                continue;
                            }

                            if ($bulkAction === 'reject') {
                                if (SystemIconSubmission::markReviewed((int)$sid, 'rejected', $userId, null, $bulkNote)) {
                                    $ok++;
                                } else {
                                    $fail++;
                                }
                                continue;
                            }

                            $existing = SystemIconLibrary::findByName($name);

                            if ($bulkAction === 'publish') {
                                if ($existing) {
                                    $fail++;
                                    continue;
                                }
                                $sourceMode = $this->detectSourceModeFromPath($path);
                                $newId = SystemIconLibrary::create($name, $path, 'user', $sourceMode);
                                if ($newId > 0) {
                                    SystemIconLibrary::setSource($newId, 'user', $sourceMode);
                                }
                                SystemIconChange::record('create', $newId > 0 ? $newId : null, $name, $path);
                                if (SystemIconSubmission::markReviewed((int)$sid, 'approved', $userId, 'publish', null)) {
                                    $ok++;
                                } else {
                                    $fail++;
                                }
                            } else {
                                // replace
                                if ($existing) {
                                    SystemIconLibrary::update((int)$existing['id'], $name, $path);
                                    $sourceMode = $this->detectSourceModeFromPath($path);
                                    SystemIconLibrary::setSource((int)$existing['id'], 'user', $sourceMode);
                                    SystemIconChange::record('update', (int)$existing['id'], $name, $path);
                                } else {
                                    $sourceMode = $this->detectSourceModeFromPath($path);
                                    $newId = SystemIconLibrary::create($name, $path, 'user', $sourceMode);
                                    if ($newId > 0) {
                                        SystemIconLibrary::setSource($newId, 'user', $sourceMode);
                                    }
                                    SystemIconChange::record('create', $newId > 0 ? $newId : null, $name, $path);
                                }

                                if (SystemIconSubmission::markReviewed((int)$sid, 'approved', $userId, 'replace', null)) {
                                    $ok++;
                                } else {
                                    $fail++;
                                }
                            }
                        } catch (\Throwable $e) {
                            $fail++;
                        }
                    }

                    if ($ok > 0) {
                        $success = '批量审核完成：成功 ' . $ok . ' 条，失败 ' . $fail . ' 条。';
                    } else {
                        $error = '批量审核未处理任何记录（请确认选择了待审核记录，且操作类型正确）。';
                    }
                }
            }
        }

        if ($iconSearch !== '') {
            $iconsTotal = SystemIconLibrary::countBySearch($iconSearch);
            $iconsTotalPages = max(1, (int)ceil($iconsTotal / $perPage));
            $iconPage = min($iconPage, $iconsTotalPages);
            $systemIcons = SystemIconLibrary::listPagedBySearch(($iconPage - 1) * $perPage, $perPage, $iconSearch);
        } else {
            $iconsTotal = SystemIconLibrary::countAll();
            $iconsTotalPages = max(1, (int)ceil($iconsTotal / $perPage));
            $iconPage = min($iconPage, $iconsTotalPages);
            $systemIcons = SystemIconLibrary::listPaged(($iconPage - 1) * $perPage, $perPage);
        }

        $pendingTotal = 0;
        $pendingTotalPages = 1;
        $pendingSubmissions = [];
        try {
            $pendingTotal = SystemIconSubmission::countPending();
            $pendingTotalPages = max(1, (int)ceil($pendingTotal / $perPage));
            $pendingPage = min($pendingPage, $pendingTotalPages);
            $pendingSubmissions = SystemIconSubmission::listPendingPaged(($pendingPage - 1) * $perPage, $perPage);
        } catch (\Throwable $e) {
            $pendingSubmissions = [];
            if ($error === '') {
                $error = '待审核列表加载失败（可能尚未创建 system_icon_submissions 表）。';
            }
        }

        $this->render('system_icons/index', [
            'systemIcons' => $systemIcons,
            'iconsTotal' => $iconsTotal,
            'iconPage' => $iconPage,
            'iconsTotalPages' => $iconsTotalPages,
            'perPage' => $perPage,
            'iconSearch' => $iconSearch,
            'pendingSubmissions' => $pendingSubmissions,
            'pendingTotal' => $pendingTotal,
            'pendingPage' => $pendingPage,
            'pendingTotalPages' => $pendingTotalPages,
            'error' => $error,
            'success' => $success,
            'openModal' => $openModal,
        ]);
    }
}
