<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Logger;
use App\Model\ResumeData;

class ResumeController
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
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '在线简历';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function renderPlain(string $view, array $params = []): void
    {
        extract($params);
        include __DIR__ . '/../../templates/resume/' . $view . '.php';
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function preview(): void
    {
        $userId = $this->requireLogin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        $resume = null;
        if ($id > 0) {
            $resume = ResumeData::getById($id, $userId) ?? ResumeData::get($userId);
        } else {
            $resume = ResumeData::get($userId);
        }

        if ($resume !== null) {
            $template = $_GET['template'] ?? $resume['template'] ?? 'simple';
            $resumeData = $resume['data'] ?? self::defaultData();
            $resumeName = $resume['name'] ?? '未命名简历';
            $resumeId = $resume['id'] ?? 0;
        } else {
            $template = $_GET['template'] ?? 'simple';
            $resumeData = self::defaultData();
            $resumeName = '未命名简历';
            $resumeId = 0;
        }

        if (isset($_GET['data'])) {
            $decoded = json_decode($_GET['data'], true);
            if ($decoded && is_array($decoded)) {
                $resumeData = $decoded;
            }
        }

        if (isset($_GET['standalone'])) {
            if ($id > 0 && !isset($_GET['data'])) {
                $resume = ResumeData::getById($id, $userId);
                if ($resume) {
                    $resumeData = $resume['data'] ?? $resumeData;
                    if (!isset($_GET['template'])) {
                        $template = $resume['template'] ?? $template;
                    }
                }
            }
            $tpl = in_array($template, ['simple','pro','creative']) ? $template : 'simple';
            $this->renderPlain('templates/template_' . $tpl, [
                'resume' => $resumeData,
            ]);
            return;
        }

        $resumes = ResumeData::getAll($userId);

        $this->render('resume/preview', [
            'pageTitle' => '简历预览',
            'template' => $template,
            'resume' => $resumeData,
            'resumeName' => $resumeName,
            'resumeId' => $resumeId,
            'resumes' => $resumes,
        ]);
    }

    public function builder(): void
    {
        $userId = $this->requireLogin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id > 0) {
            $resume = ResumeData::getById($id, $userId);
        } else {
            $resume = ResumeData::get($userId);
        }

        if ($resume !== null) {
            $template = $resume['template'] ?? 'simple';
            $resumeData = $resume['data'] ?? self::defaultData();
            $resumeName = $resume['name'] ?? '未命名简历';
            $resumeId = $resume['id'] ?? 0;
        } else {
            $template = 'simple';
            $resumeData = self::defaultData();
            $resumeName = '未命名简历';
            $resumeId = 0;
        }

        $resumes = ResumeData::getAll($userId);

        $this->render('resume/builder', [
            'pageTitle' => '简历配置',
            'template' => $template,
            'resume' => $resumeData,
            'resumeName' => $resumeName,
            'resumeId' => $resumeId,
            'resumes' => $resumes,
        ]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'save') {
                $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
                $data = json_decode($_POST['data'] ?? '{}', true);
                if (!$data || !is_array($data)) {
                    $this->json(['ok' => false, 'error' => '数据格式错误']);
                }
                $template = $_POST['template'] ?? 'simple';
                $name = $_POST['name'] ?? '';
                $newId = ResumeData::save($id, $userId, $data, $template, $name);
                $resumeName = $data['basic']['name'] ?? '未命名';
                Logger::log('保存简历', "保存简历：{$resumeName}，模板：{$template}", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => true, 'id' => $newId]);
                return;
            }

            if ($action === 'list') {
                $resumes = ResumeData::getAll($userId);
                $this->json(['ok' => true, 'resumes' => $resumes]);
                return;
            }

            if ($action === 'load') {
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                if ($id > 0) {
                    $resume = ResumeData::getById($id, $userId);
                } else {
                    $resume = ResumeData::get($userId);
                }
                if ($resume !== null) {
                    $this->json([
                        'ok' => true,
                        'id' => $resume['id'] ?? 0,
                        'name' => $resume['name'] ?? '未命名简历',
                        'template' => $resume['template'] ?? 'simple',
                        'data' => $resume['data'] ?? self::defaultData(),
                    ]);
                } else {
                    $this->json([
                        'ok' => true,
                        'id' => 0,
                        'name' => '未命名简历',
                        'template' => 'simple',
                        'data' => self::defaultData(),
                    ]);
                }
                return;
            }

            if ($action === 'copy') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $this->json(['ok' => false, 'error' => '请指定要复制的简历']);
                }
                $newId = ResumeData::copy($id, $userId);
                if ($newId === null) {
                    $this->json(['ok' => false, 'error' => '简历不存在']);
                }
                $resume = ResumeData::getById($newId, $userId);
                Logger::log('复制简历', "复制简历：ID {$id} → {$newId}", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json([
                    'ok' => true,
                    'id' => $newId,
                    'name' => $resume['name'] ?? '',
                    'template' => $resume['template'] ?? 'simple',
                    'data' => $resume['data'] ?? self::defaultData(),
                ]);
                return;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $this->json(['ok' => false, 'error' => '请指定要删除的简历']);
                }
                $deleted = ResumeData::delete($id, $userId);
                if (!$deleted) {
                    $this->json(['ok' => false, 'error' => '简历不存在或无权删除']);
                }
                Logger::log('删除简历', "删除简历：ID {$id}", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => true]);
                return;
            }

            if ($action === 'new') {
                $name = $_POST['name'] ?? '新建简历';
                $newId = ResumeData::save(null, $userId, self::defaultData(), 'simple', $name);
                Logger::log('新建简历', "新建简历：{$name}", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => true, 'id' => $newId, 'name' => $name, 'template' => 'simple', 'data' => self::defaultData()]);
                return;
            }

            if ($action === 'upload_avatar') {
                $file = $_FILES['avatar'] ?? null;
                if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $this->json(['ok' => false, 'error' => '未选择文件或上传失败']);
                }
                $tmp = (string)($file['tmp_name'] ?? '');
                if ($tmp === '' || !is_uploaded_file($tmp)) {
                    $this->json(['ok' => false, 'error' => '无效的上传文件']);
                }
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
                    $this->json(['ok' => false, 'error' => '仅支持 JPG/PNG/GIF/WEBP']);
                }
                $uploadDir = __DIR__ . '/../../uploads/resume';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . '/' . $filename;
                if (!@move_uploaded_file($tmp, $targetPath)) {
                    $this->json(['ok' => false, 'error' => '文件保存失败']);
                }
                $url = '/uploads/resume/' . $filename;
                $this->json(['ok' => true, 'url' => $url]);
                return;
            }
        }

        $this->json(['ok' => false, 'error' => '未知操作']);
    }

    private static function defaultData(): array
    {
        return [
            'basic' => [
                'name' => '',
                'avatar' => '',
                'phone' => '',
                'email' => '',
                'birth' => '',
                'location' => '',
                'website' => '',
                'title' => '',
                'summary' => '',
            ],
            'experience' => [],
            'education' => [],
            'skills' => [],
            'projects' => [],
        ];
    }
}
