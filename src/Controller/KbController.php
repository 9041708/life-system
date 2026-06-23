<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Upload;
use App\Model\KbSpace;
use App\Model\KbDocument;
use App\Model\KbDocVersion;

class KbController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) { header('Location: /public/index.php?route=login'); exit; }
        return $uid;
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '知识库';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function renderStandalone(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/kb/' . $view . '.php';
    }

    private function json(array $data): void
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function editor(): void
    {
        $userId = $this->requireLogin();
        $space = KbSpace::getOrCreate($userId);
        $docId = (int)($_GET['doc'] ?? 0);
        $tree = KbDocument::getTree((int)$space['id'], $userId);
        $currentDoc = null;
        if ($docId > 0) {
            $currentDoc = KbDocument::findById($docId, $userId);
        }
        $this->render('kb/editor', [
            'pageTitle' => '知识库编辑',
            'space' => $space,
            'tree' => $tree,
            'currentDoc' => $currentDoc,
            'currentDocId' => $docId,
        ]);
    }

    public function read(): void
    {
        $userId = $this->requireLogin();
        $space = KbSpace::getOrCreate($userId);
        $docId = (int)($_GET['doc'] ?? 0);
        $tree = KbDocument::getTree((int)$space['id'], $userId);
        $currentDoc = null;
        if ($docId > 0) {
            $currentDoc = KbDocument::findById($docId, $userId);
        } elseif (!empty($tree)) {
            foreach ($tree as $node) {
                if (empty($node['is_folder'])) { $currentDoc = KbDocument::findById((int)$node['id'], $userId); break; }
            }
        }
        $this->render('kb/read', [
            'pageTitle' => '知识库',
            'space' => $space,
            'tree' => $tree,
            'currentDoc' => $currentDoc,
        ]);
    }

    public function share(): void
    {
        $token = trim($_GET['token'] ?? '');
        if ($token === '') { http_response_code(404); echo '链接无效'; exit; }
        $doc = KbDocument::findByToken($token);
        if (!$doc) { http_response_code(404); echo '文档不存在或已取消分享'; exit; }
        $this->renderStandalone('share', [
            'doc' => $doc,
            'appName' => Config::get('app.name'),
        ]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->json(['ok' => false, 'error' => '无效请求']); }
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        try {
            ob_start();
            switch ($action) {
                case 'create_doc': $this->createDoc($userId); break;
                case 'update_doc': $this->updateDoc($userId); break;
                case 'delete_doc': $this->deleteDoc($userId); break;
                case 'get_tree': $this->getTree($userId); break;
                case 'get_doc': $this->getDoc($userId); break;
                case 'toggle_share': $this->toggleShare($userId); break;
                case 'reorder': $this->reorder($userId); break;
                case 'search': $this->search($userId); break;
                case 'upload_image': $this->uploadImage($userId); break;
                case 'save_space_config': $this->saveSpaceConfig($userId); break;
                case 'get_versions': $this->getVersions($userId); break;
                case 'restore_version': $this->restoreVersion($userId); break;
                default: $this->json(['ok' => false, 'error' => '未知操作']);
            }
            ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level()) ob_end_clean();
            $this->json(['ok' => false, 'error' => '操作异常: ' . $e->getMessage()]);
        }
    }

    private function createDoc(int $userId): void
    {
        $space = KbSpace::getOrCreate($userId);
        $parentId = (int)($_POST['parent_id'] ?? 0);
        $isFolder = (int)($_POST['is_folder'] ?? 0);
        $title = trim($_POST['title'] ?? '') ?: ($isFolder ? '新文件夹' : '无标题');
        $sort = KbDocument::getMaxSortOrder((int)$space['id'], $parentId, $userId) + 1;
        $id = KbDocument::create((int)$space['id'], $userId, [
            'parent_id' => $parentId,
            'title' => $title,
            'is_folder' => $isFolder,
            'sort_order' => $sort,
        ]);
        $this->json(['ok' => true, 'id' => $id, 'title' => $title]);
    }

    private function updateDoc(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $doc = KbDocument::findById($id, $userId);
        if (!$doc) $this->json(['ok' => false, 'error' => '文档不存在']);
        $data = [];
        if (isset($_POST['title'])) $data['title'] = trim($_POST['title']);
        if (isset($_POST['content'])) {
            $oldContent = $doc['content'] ?? '';
            $newContent = $_POST['content'];
            // 清理已删除的图片文件
            $oldImages = Upload::extractKbImagePaths($oldContent);
            $newImages = Upload::extractKbImagePaths($newContent);
            $removed = array_diff($oldImages, $newImages);
            $baseDir = Config::get('app.upload_dir');
            foreach ($removed as $relPath) {
                $fullPath = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . ltrim($relPath, '/\\');
                if (is_file($fullPath)) @unlink($fullPath);
            }
            $data['content'] = $newContent;
            $data['content_html'] = $_POST['content_html'] ?? '';
        }
        if (isset($_POST['parent_id'])) $data['parent_id'] = (int)$_POST['parent_id'];
        if (!empty($data)) KbDocument::update($id, $userId, $data);
        if (isset($_POST['content'])) {
            $space = KbSpace::getOrCreate($userId);
            if (!empty($space['version_enabled'])) {
                $maxVer = max(1, (int)($space['version_max'] ?? 10));
                $verCount = KbDocVersion::countByDoc($id);
                if ($verCount === 0 || $doc['content'] !== $_POST['content']) {
                    KbDocVersion::create($id, $userId, $doc['title'], $_POST['content']);
                    KbDocVersion::cleanOldVersions($id, $maxVer);
                }
            }
        }
        $this->json(['ok' => true]);
    }

    private function deleteDoc(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        // 先删除该文档对应的图片目录
        Upload::deleteKbDocDir($userId, $id);
        KbDocument::deleteRecursive($id, $userId);
        $this->json(['ok' => true]);
    }

    private function getTree(int $userId): void
    {
        $space = KbSpace::getOrCreate($userId);
        $tree = KbDocument::getTree((int)$space['id'], $userId);
        $this->json(['ok' => true, 'tree' => $tree, 'space' => $space]);
    }

    private function getDoc(int $userId): void
    {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $doc = KbDocument::findById($id, $userId);
        if (!$doc) $this->json(['ok' => false, 'error' => '文档不存在']);
        $this->json(['ok' => true, 'doc' => $doc]);
    }

    private function toggleShare(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $doc = KbDocument::findById($id, $userId);
        if (!$doc) $this->json(['ok' => false, 'error' => '文档不存在']);
        if (!empty($doc['is_public'])) {
            KbDocument::update($id, $userId, ['is_public' => 0, 'share_token' => null]);
            $this->json(['ok' => true, 'shared' => false, 'message' => '已取消分享']);
        } else {
            $token = bin2hex(random_bytes(32));
            KbDocument::update($id, $userId, ['is_public' => 1, 'share_token' => $token]);
            $url = Config::get('app.site_url', '') . '/public/index.php?route=kb-share&token=' . $token;
            $this->json(['ok' => true, 'shared' => true, 'token' => $token, 'url' => $url, 'message' => '已开启分享']);
        }
    }

    private function reorder(int $userId): void
    {
        $orders = $_POST['orders'] ?? '';
        if (is_string($orders)) $orders = json_decode($orders, true);
        if (!is_array($orders)) $this->json(['ok' => false, 'error' => '参数错误']);
        foreach ($orders as $item) {
            $id = (int)($item['id'] ?? 0);
            $sort = (int)($item['sort_order'] ?? 0);
            $parentId = (int)($item['parent_id'] ?? -1);
            if ($id <= 0) continue;
            $data = ['sort_order' => $sort];
            if ($parentId >= 0) $data['parent_id'] = $parentId;
            KbDocument::update($id, $userId, $data);
        }
        $this->json(['ok' => true]);
    }

    private function search(int $userId): void
    {
        $space = KbSpace::getOrCreate($userId);
        $kw = trim($_POST['q'] ?? $_GET['q'] ?? '');
        if ($kw === '') $this->json(['ok' => true, 'results' => []]);
        $results = KbDocument::search((int)$space['id'], $userId, $kw);
        $this->json(['ok' => true, 'results' => $results]);
    }

    private function uploadImage(int $userId): void
    {
        // Editor.md 通过 URL query 传参，不从 POST 取 action
        $docId = (int)($_GET['doc_id'] ?? 0);
        if ($docId <= 0) {
            $this->json(['success' => 0, 'message' => '文档ID无效']);
        }
        $doc = KbDocument::findById($docId, $userId);
        if (!$doc) {
            $this->json(['success' => 0, 'message' => '文档不存在']);
        }
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => 0, 'message' => '上传失败，请重试']);
        }
        $path = Upload::saveKbImage($userId, $docId, $_FILES['image']);
        if (!$path) {
            $this->json(['success' => 0, 'message' => '图片保存失败']);
        }
        // Editor.md 要求返回 {success:1, url:"..."}
        $this->json(['success' => 1, 'url' => '/uploads/' . $path]);
    }

    private function saveSpaceConfig(int $userId): void
    {
        $space = KbSpace::getOrCreate($userId);
        $data = [];
        if (isset($_POST['name'])) $data['name'] = trim($_POST['name']);
        if (isset($_POST['description'])) $data['description'] = trim($_POST['description']);
        if (isset($_POST['version_enabled'])) $data['version_enabled'] = (int)$_POST['version_enabled'];
        if (isset($_POST['version_max'])) $data['version_max'] = max(1, min(100, (int)$_POST['version_max']));
        if (!empty($data)) KbSpace::update((int)$space['id'], $userId, $data);
        $this->json(['ok' => true, 'message' => '配置已保存']);
    }

    private function getVersions(int $userId): void
    {
        $docId = (int)($_POST['doc_id'] ?? 0);
        $versions = KbDocVersion::listByDoc($docId, $userId);
        $this->json(['ok' => true, 'versions' => $versions]);
    }

    private function restoreVersion(int $userId): void
    {
        $verId = (int)($_POST['version_id'] ?? 0);
        $version = KbDocVersion::findById($verId, $userId);
        if (!$version) $this->json(['ok' => false, 'error' => '版本不存在']);
        $doc = KbDocument::findById((int)$version['doc_id'], $userId);
        if (!$doc) $this->json(['ok' => false, 'error' => '文档不存在']);
        KbDocument::update((int)$doc['id'], $userId, [
            'title' => $version['title'],
            'content' => $version['content'],
        ]);
        $this->json(['ok' => true, 'content' => $version['content'], 'title' => $version['title'], 'message' => '已恢复到 v' . $version['version_num']]);
    }
}
