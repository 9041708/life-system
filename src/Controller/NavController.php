<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\NavBookmark;

class NavController
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
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '导航';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function handleScreenshotUpload(int $userId): ?string
    {
        if (empty($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        return $this->saveUpload('screenshot', $userId);
    }

    private function handleIconUpload(int $userId): ?string
    {
        if (empty($_FILES['icon_file']) || $_FILES['icon_file']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        return $this->saveUpload('icon_file', $userId);
    }

    private function saveUpload(string $field, int $userId): ?string
    {
        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) return null;
        if ($_FILES[$field]['size'] > 5 * 1024 * 1024) return null;
        $dir = __DIR__ . '/../../uploads/nav/' . $userId . '/';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return null;
        $filename = 'nav_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . $filename;
        if (!@move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) return null;
        @chmod($dest, 0644);
        return 'nav/' . $userId . '/' . $filename;
    }

    private function deleteNavFile(?string $path): void
    {
        if (empty($path)) return;
        $full = __DIR__ . '/../../uploads/' . ltrim($path, '/');
        if (is_file($full)) @unlink($full);
    }

    public function my(): void
    {
        $userId = $this->requireLogin();
        $tab = $_GET['tab'] ?? 'own';
        $groups = NavBookmark::listGroups($userId);
        $bookmarks = $tab === 'pushed' ? NavBookmark::listPushedBookmarks($userId) : NavBookmark::listBookmarks($userId);
        $this->render('nav/my', [
            'pageTitle' => '我的导航',
            'groups' => $groups,
            'bookmarks' => $bookmarks,
            'tab' => $tab,
        ]);
    }

    public function detail(): void
    {
        $userId = $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $bookmark = NavBookmark::getBookmark($id, $userId);
        if (!$bookmark) {
            header('Location: /public/index.php?route=nav-my');
            exit;
        }
        $this->render('nav/detail', [
            'pageTitle' => $bookmark['name'] . ' — 导航详情',
            'bookmark' => $bookmark,
        ]);
    }

    public function config(): void
    {
        $userId = $this->requireLogin();
        $groups = NavBookmark::listGroups($userId);
        $bookmarks = NavBookmark::listBookmarks($userId);
        $isAdmin = ($_SESSION['user_role'] ?? 'user') === 'admin';
        $allUsers = $isAdmin ? \App\Model\User::listAllForAdminSelect() : [];
        $this->render('nav/config', [
            'pageTitle' => '导航配置',
            'groups' => $groups,
            'bookmarks' => $bookmarks,
            'isAdmin' => $isAdmin,
            'allUsers' => $allUsers,
        ]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        // 分组操作
        if ($action === 'create_group') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') $this->json(['ok' => false, 'error' => '名称不能为空']);
            $iconType = $_POST['icon_type'] ?? null;
            $iconValue = $_POST['icon_value'] ?? null;
            if ($iconType === 'file') {
                $path = $this->handleIconUpload($userId);
                if ($path) $iconValue = $path;
            }
            $id = NavBookmark::createGroup($userId, [
                'name' => $name,
                'icon_type' => $iconType,
                'icon_value' => $iconValue,
                'sort_order' => $_POST['sort_order'] ?? 0,
            ]);
            $this->json(['ok' => true, 'id' => $id]);
        }

        if ($action === 'update_group') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($name === '') $this->json(['ok' => false, 'error' => '名称不能为空']);
            $data = [
                'name' => $name,
                'sort_order' => $_POST['sort_order'] ?? 0,
            ];
            $iconMode = $_POST['icon_type'] ?? '';
            if ($iconMode !== '') {
                if ($iconMode === 'file') {
                    $path = $this->handleIconUpload($userId);
                    if ($path) {
                        $stmt = $pdo = \App\Service\Database::getConnection();
                        $old = $pdo->prepare("SELECT icon_value FROM nav_groups WHERE id = ? AND user_id = ?");
                        $old->execute([$id, $userId]);
                        $oldVal = $old->fetchColumn();
                        $this->deleteNavFile($oldVal ?: null);
                        $data['icon_value'] = $path;
                    }
                } else {
                    $data['icon_value'] = $_POST['icon_value'] ?? null;
                }
                $data['icon_type'] = $iconMode;
            }
            NavBookmark::updateGroup($id, $userId, $data);
            $this->json(['ok' => true]);
        }

        if ($action === 'delete_group') {
            NavBookmark::deleteGroup((int)($_POST['id'] ?? 0), $userId);
            $this->json(['ok' => true]);
        }

        // 标签操作
        if ($action === 'create_bookmark') {
            $name = trim($_POST['name'] ?? '');
            $url = trim($_POST['url'] ?? '');
            if ($name === '' || $url === '') $this->json(['ok' => false, 'error' => '名称和网址不能为空']);
            $group_id = (int)($_POST['group_id'] ?? 0);
            if ($group_id <= 0) $this->json(['ok' => false, 'error' => '请选择分组']);
            $screenshot = $this->handleScreenshotUpload($userId);
            $ssUploaded = ($screenshot !== null);
            $iconType = $_POST['icon_type'] ?? null;
            $iconValue = $_POST['icon_value'] ?? null;
            if ($iconType === 'file') {
                $path = $this->handleIconUpload($userId);
                if ($path) $iconValue = $path;
            }
            $id = NavBookmark::createBookmark($userId, [
                'group_id' => $group_id,
                'name' => $name,
                'url' => $url,
                'description' => $_POST['description'] ?? null,
                'icon_type' => $iconType,
                'icon_value' => $iconValue,
                'screenshot' => $screenshot ?: null,
                'sort_order' => $_POST['sort_order'] ?? 0,
                'show_on_home' => isset($_POST['show_on_home']) ? 1 : 0,
            ]);
            $this->saveBookmarkUrls($id, $_POST['alt_urls'] ?? '');
            $this->json(['ok' => true, 'id' => $id, 'screenshot_uploaded' => $ssUploaded]);
        }

        if ($action === 'update_bookmark') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($name === '') $this->json(['ok' => false, 'error' => '名称不能为空']);
            $data = [
                'group_id' => (int)($_POST['group_id'] ?? 0),
                'name' => $name,
                'url' => trim($_POST['url'] ?? ''),
                'description' => $_POST['description'] ?? null,
                'sort_order' => $_POST['sort_order'] ?? 0,
                'show_on_home' => isset($_POST['show_on_home']) ? 1 : 0,
            ];
            // 仅在主动选择图标模式时更新图标字段
            $iconMode = $_POST['icon_type'] ?? '';
            if ($iconMode !== '') {
                if ($iconMode === 'file') {
                    $path = $this->handleIconUpload($userId);
                    if ($path) {
                        if (empty($old)) $old = NavBookmark::getBookmark($id, $userId);
                        $this->deleteNavFile($old['icon_value'] ?? null);
                        $data['icon_value'] = $path;
                    }
                } else {
                    $data['icon_value'] = $_POST['icon_value'] ?? null;
                }
                $data['icon_type'] = $iconMode;
            }
            $screenshot = $this->handleScreenshotUpload($userId);
            $ssUploaded = ($screenshot !== null);
            if ($screenshot) {
                $old = NavBookmark::getBookmark($id, $userId);
                $this->deleteNavFile($old['screenshot'] ?? null);
                $this->deleteNavFile($old['icon_value'] ?? null);
                $data['screenshot'] = $screenshot;
            }
            NavBookmark::updateBookmark($id, $userId, $data);
            $this->saveBookmarkUrls($id, $_POST['alt_urls'] ?? '');
            $this->json(['ok' => true, 'screenshot_uploaded' => $ssUploaded]);
        }

        if ($action === 'delete_bookmark') {
            $id = (int)($_POST['id'] ?? 0);
            $old = NavBookmark::getBookmark($id, $userId);
            if ($old) {
                $this->deleteNavFile($old['screenshot'] ?? null);
                $this->deleteNavFile($old['icon_value'] ?? null);
            }
            NavBookmark::deleteBookmark($id, $userId);
            $this->json(['ok' => true]);
        }

        if ($action === 'fetch_screenshot') {
            $url = trim($_POST['url'] ?? '');
            if ($url === '') $this->json(['ok' => false, 'error' => '网址不能为空']);
            if (empty(Config::get('screenshotmachine_api_key', ''))) {
                $this->json(['ok' => false, 'error' => '未配置截图 API Key，请在 config.php 中设置 screenshotmachine_api_key']);
            }
            $screenshotUrl = $this->tryFetchScreenshot($url, $userId);
            if (empty($screenshotUrl)) $this->json(['ok' => false, 'error' => '截图获取失败']);
            $this->json(['ok' => true, 'screenshot_url' => $screenshotUrl]);
        }

        if ($action === 'fetch_page_info') {
            $url = trim($_POST['url'] ?? '');
            if ($url === '') $this->json(['ok' => false, 'error' => '网址不能为空']);
            $info = $this->extractPageInfo($url, $userId);
            $this->json(['ok' => true, 'info' => $info]);
        }

        if ($action === 'push') {
            if (($_SESSION['user_role'] ?? 'user') !== 'admin') $this->json(['ok' => false, 'error' => '仅管理员可推送']);
            NavBookmark::push((int)($_POST['bookmark_id'] ?? 0), $userId, (int)($_POST['target_user_id'] ?? 0));
            $this->json(['ok' => true]);
        }
        if ($action === 'unpush') {
            if (($_SESSION['user_role'] ?? 'user') !== 'admin') $this->json(['ok' => false, 'error' => '仅管理员可操作']);
            NavBookmark::unpush((int)($_POST['bookmark_id'] ?? 0), (int)($_POST['target_user_id'] ?? 0));
            $this->json(['ok' => true]);
        }
        if ($action === 'get_pushed_targets') {
            $targets = NavBookmark::getPushedTargets((int)($_POST['bookmark_id'] ?? 0));
            $this->json(['ok' => true, 'targets' => $targets]);
        }

        $this->json(['ok' => false, 'error' => '未知操作']);
    }

    private function saveBookmarkUrls(int $bookmarkId, string $raw): void
    {
        $pairs = array_filter(explode("\n", $raw), fn($l) => trim($l) !== '');
        $urls = [];
        foreach ($pairs as $line) {
            $line = trim($line);
            if (str_contains($line, '|')) {
                [$lbl, $u] = explode('|', $line, 2);
                $urls[] = ['label' => trim($lbl), 'url' => trim($u)];
            } else {
                $urls[] = ['label' => '', 'url' => $line];
            }
        }
        NavBookmark::saveUrls($bookmarkId, $urls);
    }

    private function extractPageInfo(string $url, int $userId): array
    {
        $info = ['title' => '', 'description' => '', 'favicon_path' => ''];
        $ctx = stream_context_create([
            'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0', 'ignore_errors' => true],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $html = @file_get_contents($url, false, $ctx);
        if (empty($html)) return $info;

        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
            $info['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }
        if (preg_match('/<meta\s+[^>]*name\s*=\s*["\']description["\'][^>]*content\s*=\s*["\']([^"\']+)["\']/si', $html, $m)
            || preg_match('/<meta\s+[^>]*content\s*=\s*["\']([^"\']+)["\'][^>]*name\s*=\s*["\']description["\']/si', $html, $m)) {
            $info['description'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }
        // 提取 favicon URL
        $favUrl = '';
        if (preg_match('/<link[^>]*rel\s*=\s*["\'](?:shortcut\s+)?icon["\'][^>]*href\s*=\s*["\']([^"\']+)["\']/si', $html, $m)
            || preg_match('/<link[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*rel\s*=\s*["\'](?:shortcut\s+)?icon["\']/si', $html, $m)) {
            $favUrl = trim($m[1]);
            if (!preg_match('#^https?://#i', $favUrl)) {
                $base = parse_url($url);
                $favUrl = ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? '') . '/' . ltrim($favUrl, '/');
            }
        }
        if (empty($favUrl)) {
            $base = parse_url($url);
            $favUrl = ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? '') . '/favicon.ico';
        }

        // 下载 favicon 到本地
        $favRaw = $this->httpGet($favUrl);
        if (!empty($favRaw) && strlen($favRaw) < 500 * 1024) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($favRaw);
            $extMap = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif',
                        'image/svg+xml' => 'svg', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico',
                        'image/webp' => 'webp', 'image/ico' => 'ico'];
            $ext = $extMap[$mime] ?? null;
            // 如果 MIME 检测失败但确是有效图片，尝试根据文件头判断
            if (!$ext) {
                $head = substr($favRaw, 0, 8);
                if (str_starts_with($head, "\x89PNG")) $ext = 'png';
                elseif (str_starts_with($head, "\xFF\xD8\xFF")) $ext = 'jpg';
                elseif (str_starts_with($head, "GIF8")) $ext = 'gif';
                elseif (str_starts_with($head, "\x00\x00\x01\x00")) $ext = 'ico';
                elseif (str_starts_with($head, '<svg')) $ext = 'svg';
            }
            if ($ext) {
                $dir = __DIR__ . '/../../uploads/nav/' . $userId . '/';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $filename = 'fav_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (@file_put_contents($dir . $filename, $favRaw) !== false) {
                    @chmod($dir . $filename, 0644);
                    $info['favicon_path'] = 'nav/' . $userId . '/' . $filename;
                }
            }
        }
        return $info;
    }

    private function httpGet(string $url): string|false
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'Mozilla/5.0',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
            return $raw;
        }
        $ctx = stream_context_create([
            'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0', 'ignore_errors' => true],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        return @file_get_contents($url, false, $ctx);
    }

    private function tryFetchScreenshot(string $url, int $userId): string
    {
        $apiKey = Config::get('screenshotmachine_api_key', '');
        if (empty($apiKey)) return '';
        $params = http_build_query([
            'key' => $apiKey,
            'url' => $url,
            'dimension' => '1024x768',
        ]);
        $apiUrl = 'https://api.screenshotmachine.com?' . $params;

        $raw = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            if ($httpCode !== 200 || empty($raw) || !str_starts_with((string)$contentType, 'image/')) {
                $raw = false;
            }
        }
        if ($raw === false) {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0',
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $raw = @file_get_contents($apiUrl, false, $ctx);
        }
        if (empty($raw) || strlen($raw) < 100) return '';
        $dir = __DIR__ . '/../../uploads/nav/' . $userId . '/';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return '';
        $filename = 'nav_ss_' . time() . '.png';
        if (@file_put_contents($dir . $filename, $raw) === false) return '';
        @chmod($dir . $filename, 0644);
        return 'nav/' . $userId . '/' . $filename;
    }
}
