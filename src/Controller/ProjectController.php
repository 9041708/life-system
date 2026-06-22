<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\Project;
use App\Model\ProjectUpdate;
use App\Model\User;

class ProjectController
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
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '项目';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function list(): void
    {
        $userId = $this->requireLogin();
        $status = $_GET['status'] ?? 'all';
        $projects = Project::listByUser($userId, $status);
        $this->render('project/list', [
            'pageTitle' => '项目列表',
            'projects' => $projects,
            'currentStatus' => $status,
        ]);
    }

    public function detail(): void
    {
        $userId = $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $project = Project::findById($id, $userId);
        if (!$project || !Project::isMember($id, $userId)) {
            header('Location: /public/index.php?route=project-list');
            exit;
        }
        $updates = ProjectUpdate::listByProject($id, $userId);
        $members = Project::getMembers($id);
        $isOwner = Project::isOwner($id, $userId);
        $this->render('project/detail', [
            'pageTitle' => $project['name'],
            'project' => $project,
            'updates' => $updates,
            'members' => $members,
            'isOwner' => $isOwner,
        ]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => '无效请求']);
        }
        $action = $_POST['action'] ?? '';
        try {
            ob_start();
            switch ($action) {
                case 'create_project':
                    $this->createProject($userId);
                    break;
                case 'update_project':
                    $this->updateProject($userId);
                    break;
                case 'delete_project':
                    $this->deleteProject($userId);
                    break;
                case 'add_update':
                    $this->addUpdate($userId);
                    break;
                case 'edit_update':
                    $this->editUpdate($userId);
                    break;
                case 'delete_update':
                    $this->deleteUpdate($userId);
                    break;
                case 'add_member':
                    $this->addMember($userId);
                    break;
                case 'remove_member':
                    $this->removeMember($userId);
                    break;
                case 'search_member':
                    $this->searchMember($userId);
                    break;
                case 'toggle_task':
                    $this->toggleTask($userId);
                    break;
                default:
                    $this->json(['ok' => false, 'error' => '未知操作']);
            }
            ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level()) ob_end_clean();
            $this->json(['ok' => false, 'error' => '操作异常: ' . $e->getMessage()]);
        }
    }

    private function createProject(int $userId): void
    {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') $this->json(['ok' => false, 'error' => '项目名称不能为空']);
        $id = Project::create($userId, [
            'name' => $name,
            'description' => trim($_POST['description'] ?? ''),
            'tasks' => trim($_POST['tasks'] ?? '') ?: null,
            'status' => $_POST['status'] ?? 'planning',
            'start_date' => $_POST['start_date'] ?? '',
        ]);
        $memberIds = trim($_POST['member_ids'] ?? '');
        if ($memberIds !== '') {
            foreach (explode(',', $memberIds) as $mid) {
                $mid = (int)trim($mid);
                if ($mid > 0 && $mid !== $userId) {
                    Project::addMember($id, $mid);
                }
            }
        }
        $attachments = $this->handleAttachments($userId, $id);
        if ($attachments) {
            ProjectUpdate::create($id, $userId, [
                'title' => '项目启动',
                'content' => '项目创建，初始附件已上传',
                'progress' => 0,
                'update_date' => date('Y-m-d'),
                'attachments' => json_encode($attachments, JSON_UNESCAPED_UNICODE),
            ]);
        }
        $this->json(['ok' => true, 'id' => $id, 'message' => '项目已创建']);
    }

    private function updateProject(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $project = Project::findById($id, $userId);
        if (!$project) $this->json(['ok' => false, 'error' => '项目不存在']);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') $this->json(['ok' => false, 'error' => '项目名称不能为空']);
        Project::update($id, $userId, [
            'name' => $name,
            'description' => trim($_POST['description'] ?? ''),
            'tasks' => trim($_POST['tasks'] ?? '') ?: null,
            'status' => $_POST['status'] ?? $project['status'],
            'progress' => (int)($project['progress']),
            'start_date' => $_POST['start_date'] ?? '',
        ]);
        $this->json(['ok' => true, 'message' => '项目已更新']);
    }

    private function deleteProject(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        Project::delete($id, $userId);
        $this->json(['ok' => true, 'message' => '项目已删除']);
    }

    private function addUpdate(int $userId): void
    {
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!Project::isMember($projectId, $userId)) $this->json(['ok' => false, 'error' => '无权限操作']);
        $title = trim($_POST['title'] ?? '');
        if ($title === '') $this->json(['ok' => false, 'error' => '标题不能为空']);
        $attachments = $this->handleAttachments($userId, $projectId);
        $updateId = ProjectUpdate::create($projectId, $userId, [
            'title' => $title,
            'content' => trim($_POST['content'] ?? ''),
            'progress' => (int)($_POST['progress'] ?? 0),
            'update_date' => $_POST['update_date'] ?? date('Y-m-d'),
            'attachments' => $attachments ? json_encode($attachments, JSON_UNESCAPED_UNICODE) : '',
        ]);
        $newStatus = trim($_POST['project_status'] ?? '');
        if ($newStatus !== '' && in_array($newStatus, ['planning', 'active', 'completed', 'archived'])) {
            $project = Project::findById($projectId, $userId);
            if ($project && $project['user_id'] == $userId) {
                $pdo = \App\Service\Database::getConnection();
                $pdo->prepare('UPDATE projects SET status = :s WHERE id = :pid')->execute([':s' => $newStatus, ':pid' => $projectId]);
            }
        }
        $this->json(['ok' => true, 'id' => $updateId, 'message' => '进度已添加']);
    }

    private function editUpdate(int $userId): void
    {
        $id = (int)($_POST['update_id'] ?? 0);
        $existing = ProjectUpdate::findById($id, $userId);
        if (!$existing) $this->json(['ok' => false, 'error' => '记录不存在']);
        $title = trim($_POST['title'] ?? '');
        if ($title === '') $this->json(['ok' => false, 'error' => '标题不能为空']);
        $keepAttach = $_POST['keep_attachments'] ?? '';
        $existingAttach = $keepAttach ? json_decode($keepAttach, true) : ($existing['attachments'] ?? []);
        $newAttach = $this->handleAttachments($userId, $existing['project_id']);
        $allAttach = array_merge($existingAttach ?: [], $newAttach ?: []);
        ProjectUpdate::update($id, $userId, [
            'title' => $title,
            'content' => trim($_POST['content'] ?? ''),
            'progress' => (int)($_POST['progress'] ?? 0),
            'update_date' => $_POST['update_date'] ?? $existing['update_date'],
            'attachments' => $allAttach ? json_encode($allAttach, JSON_UNESCAPED_UNICODE) : '',
        ]);
        $this->json(['ok' => true, 'message' => '已更新']);
    }

    private function deleteUpdate(int $userId): void
    {
        $id = (int)($_POST['update_id'] ?? 0);
        $row = ProjectUpdate::findById($id, $userId);
        if ($row && !empty($row['attachments'])) {
            $uploadDir = Config::get('app.upload_dir', '');
            foreach ($row['attachments'] as $att) {
                if (!empty($att['path'])) {
                    $fullPath = rtrim($uploadDir, '/') . '/' . $att['path'];
                    if (file_exists($fullPath)) @unlink($fullPath);
                }
            }
        }
        ProjectUpdate::delete($id, $userId);
        $this->json(['ok' => true, 'message' => '已删除']);
    }

    private function handleAttachments(int $userId, int $projectId): array
    {
        $files = $_FILES['attachments'] ?? null;
        if (!$files || empty($files['name'][0])) return [];
        $uploadDir = rtrim(Config::get('app.upload_dir', ''), '/');
        if (!$uploadDir) return [];
        $targetDir = $uploadDir . '/projects/' . $userId;
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) return [];
        }
        $result = [];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > 10 * 1024 * 1024) continue;
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $safeName = uniqid('pu_', true) . '.' . $ext;
            $targetPath = $targetDir . '/' . $safeName;
            if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                $result[] = [
                    'name' => $files['name'][$i],
                    'path' => 'projects/' . $userId . '/' . $safeName,
                    'size' => $files['size'][$i],
                    'ext' => $ext,
                ];
            }
        }
        return $result;
    }

    private function addMember(int $userId): void
    {
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!Project::isOwner($projectId, $userId)) $this->json(['ok' => false, 'error' => '仅项目创建者可添加成员']);
        $targetUid = (int)($_POST['user_id'] ?? 0);
        if ($targetUid <= 0) $this->json(['ok' => false, 'error' => '无效的用户ID']);
        if ($targetUid === $userId) $this->json(['ok' => false, 'error' => '不能添加自己']);
        $target = User::findById($targetUid);
        if (!$target) $this->json(['ok' => false, 'error' => '用户不存在']);
        Project::addMember($projectId, $targetUid);
        $this->json(['ok' => true, 'message' => '已添加成员：' . ($target['nickname'] ?: $target['username'])]);
    }

    private function removeMember(int $userId): void
    {
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!Project::isOwner($projectId, $userId)) $this->json(['ok' => false, 'error' => '仅项目创建者可移除成员']);
        $targetUid = (int)($_POST['user_id'] ?? 0);
        if ($targetUid === $userId) $this->json(['ok' => false, 'error' => '不能移除自己']);
        Project::removeMember($projectId, $targetUid);
        $this->json(['ok' => true, 'message' => '已移除']);
    }

    private function searchMember(int $userId): void
    {
        $keyword = trim($_GET['q'] ?? $_POST['q'] ?? '');
        if ($keyword === '') $this->json(['ok' => true, 'users' => []]);
        $kw = '%' . $keyword . '%';
        $pdo = \App\Service\Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, username, nickname, email FROM users WHERE CAST(id AS CHAR) LIKE :kw OR username LIKE :kw OR nickname LIKE :kw OR email LIKE :kw ORDER BY id LIMIT 20");
        $stmt->execute([':kw' => $kw]);
        $this->json(['ok' => true, 'users' => $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []]);
    }

    private function toggleTask(int $userId): void
    {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $taskIndex = (int)($_POST['task_index'] ?? -1);
        if (!Project::isMember($projectId, $userId)) $this->json(['ok' => false, 'error' => '无权限']);
        $project = Project::findById($projectId, $userId);
        if (!$project) $this->json(['ok' => false, 'error' => '项目不存在']);
        $tasks = $project['tasks'] ?? [];
        if ($taskIndex < 0 || $taskIndex >= count($tasks)) $this->json(['ok' => false, 'error' => '无效的任务索引']);
        $tasks[$taskIndex]['done'] = empty($tasks[$taskIndex]['done']);
        $tasksJson = json_encode($tasks, JSON_UNESCAPED_UNICODE);
        Project::updateTasks($projectId, $project['user_id'], $tasksJson);
        $doneCount = count(array_filter($tasks, fn($t) => !empty($t['done'])));
        $this->json(['ok' => true, 'done' => $tasks[$taskIndex]['done'], 'tasks_done' => $doneCount, 'tasks_total' => count($tasks)]);
    }
}
