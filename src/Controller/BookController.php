<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Logger;
use App\Model\Book;
use App\Model\BookProgress;
use App\Model\User;

class BookController
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
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '图书管理';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        $tab = $_GET['tab'] ?? 'personal';
        if ($tab === 'system') {
            $books = Book::listSystem();
        } else {
            $books = Book::listByUser($userId);
        }
        if (empty($books)) {
            $synced = Book::syncFromDisk($userId);
            if ($synced > 0) {
                if ($tab === 'system') {
                    $books = Book::listSystem();
                } else {
                    $books = Book::listByUser($userId);
                }
            }
        }
        $this->render('books/index', [
            'pageTitle' => '在线阅览',
            'books' => $books,
            'tab' => $tab,
        ]);
    }

    public function config(): void
    {
        $userId = $this->requireLogin();
        $books = Book::listByUser($userId);
        if (empty($books)) {
            $synced = Book::syncFromDisk($userId);
            if ($synced > 0) {
                $books = Book::listByUser($userId);
            }
        }
        foreach ($books as &$book) {
            if (($book['scope'] ?? 'personal') === 'system') {
                $book['push_info'] = '全系统可阅';
                $book['push_list'] = [];
            } elseif (!empty($book['push_data'])) {
                $pd = json_decode($book['push_data'], true);
                if (is_array($pd) && $pd) {
                    $ids = array_column($pd, 'uid');
                    $users = [];
                    foreach ($ids as $uid) {
                        $u = User::findById((int)$uid);
                        $users[] = ['id' => $uid, 'name' => ($u['username'] ?? 'ID:' . $uid) . ($u['nickname'] ? ' (' . $u['nickname'] . ')' : '')];
                    }
                    $book['push_info'] = '已推 ' . count($ids) . ' 人';
                    $book['push_list'] = $users;
                }
            }
        }
        unset($book);
        $this->render('books/config', [
            'pageTitle' => '图书配置',
            'books' => $books,
        ]);
    }

    public function reader(): void
    {
        $userId = $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $book = Book::findById($id, $userId);
        if (!$book) {
            header('Location: /public/index.php?route=books');
            exit;
        }
        $progress = BookProgress::get($userId, $id);
        $this->render('books/reader', [
            'pageTitle' => '阅读: ' . $book['title'],
            'book' => $book,
            'progress' => $progress,
        ]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'upload') {
                $file = $_FILES['file'] ?? null;
                if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $this->json(['ok' => false, 'error' => '未选择文件或上传失败']);
                }
                $tmp = (string)($file['tmp_name'] ?? '');
                if ($tmp === '' || !is_uploaded_file($tmp)) {
                    $this->json(['ok' => false, 'error' => '无效的上传文件']);
                }
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['pdf','txt'], true)) {
                    $this->json(['ok' => false, 'error' => '仅支持 PDF/TXT 格式']);
                }
                $uploadDir = __DIR__ . '/../../uploads/books';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'book_' . $userId . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . '/' . $filename;
                if (!@move_uploaded_file($tmp, $targetPath)) {
                    $this->json(['ok' => false, 'error' => '文件保存失败']);
                }
                $coverPath = '';
                $coverFile = $_FILES['cover'] ?? null;
                if ($coverFile && (int)($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $coverTmp = (string)($coverFile['tmp_name'] ?? '');
                    if ($coverTmp !== '' && is_uploaded_file($coverTmp)) {
                        $coverExt = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
                        $coverFilename = 'cover_' . $userId . '_' . time() . '.' . $coverExt;
                        $coverTarget = $uploadDir . '/' . $coverFilename;
                        if (@move_uploaded_file($coverTmp, $coverTarget)) {
                            $coverPath = '/uploads/books/' . $coverFilename;
                        }
                    }
                }
                $id = Book::create($userId, [
                    'title' => $_POST['title'] ?: pathinfo($file['name'], PATHINFO_FILENAME),
                    'author' => $_POST['author'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'file_path' => '/uploads/books/' . $filename,
                    'file_type' => $ext,
                    'file_size' => $file['size'] ?? 0,
                    'cover' => $coverPath,
                ]);
                $title = $_POST['title'] ?: pathinfo($file['name'], PATHINFO_FILENAME);
                Logger::log('上传图书', "上传图书：{$title}，类型：{$ext}", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => true, 'id' => $id]);
                return;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $book = Book::findById($id, $userId);
                if ($book) {
                    // only delete file if user is the actual owner
                    if ((int)($book['user_id'] ?? 0) === $userId) {
                        $absPath = __DIR__ . '/../../' . ltrim($book['file_path'], '/');
                        if (is_file($absPath)) @unlink($absPath);
                    }
                }
                $ok = Book::delete($id, $userId);
                Logger::log('删除图书', "删除图书 #{$id}：{$book['title']}", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => $ok]);
                return;
            }

            if ($action === 'progress') {
                $bookId = (int)($_POST['book_id'] ?? 0);
                $pageNum = (int)($_POST['page_num'] ?? 1);
                $scrollOffset = (int)($_POST['scroll_offset'] ?? 0);
                BookProgress::save($userId, $bookId, $pageNum, $scrollOffset);
                $this->json(['ok' => true]);
                return;
            }

            if ($action === 'push_all') {
                $id = (int)($_POST['id'] ?? 0);
                $ok = Book::pushToAll($id, $userId);
                Logger::log('推送图书-全系统', "推送图书 #{$id} 至全系统", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => $ok, 'error' => $ok ? '' : '推送失败']);
                return;
            }

            if ($action === 'push_user') {
                $id = (int)($_POST['id'] ?? 0);
                $targetUid = (int)($_POST['target_uid'] ?? 0);
                if ($targetUid <= 0) {
                    $this->json(['ok' => false, 'error' => '请输入有效的用户ID']);
                }
                $targetUser = User::findById($targetUid);
                if (!$targetUser) {
                    $this->json(['ok' => false, 'error' => '用户ID不存在']);
                }
                $newId = Book::pushToUser($id, $userId, $targetUid);
                Logger::log('推送图书-指定用户', "推送图书 #{$id} 至用户 {$targetUid} ({$targetUser['username']})", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => $newId !== null, 'error' => $newId !== null ? '' : '推送失败']);
                return;
            }

            if ($action === 'cancel_push') {
                $id = (int)($_POST['id'] ?? 0);
                $targetUid = (int)($_POST['target_uid'] ?? 0);
                $ok = Book::removePushTarget($id, $userId, $targetUid);
                Logger::log('取消推送', "取消推送图书 #{$id} 至用户 {$targetUid}", $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => $ok]);
                return;
            }

            if ($action === 'user_info') {
                $targetUid = (int)($_POST['target_uid'] ?? 0);
                $targetUser = User::findById($targetUid);
                if (!$targetUser) {
                    $this->json(['ok' => false, 'error' => '用户ID不存在']);
                }
                $this->json(['ok' => true, 'user' => [
                    'id' => $targetUser['id'],
                    'username' => $targetUser['username'] ?? '',
                    'email' => $targetUser['email'] ?? '',
                    'nickname' => $targetUser['nickname'] ?? '',
                ]]);
                return;
            }
        }

        $this->json(['ok' => false, 'error' => '未知操作']);
    }

    public function serve(): void
    {
        $userId = $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $book = Book::findById($id, $userId);
        if (!$book) {
            http_response_code(404);
            exit;
        }
        $absPath = __DIR__ . '/../../' . ltrim($book['file_path'], '/');
        if (!is_file($absPath)) {
            http_response_code(404);
            exit;
        }
        $mime = $book['file_type'] === 'pdf' ? 'application/pdf' : 'text/plain; charset=utf-8';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($absPath));
        header('Accept-Ranges: bytes');
        readfile($absPath);
    }

    public function apiUpdate(): void
    {
        $userId = $this->requireLogin();
        $id = (int)($_POST['id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $author = $_POST['author'] ?? '';
        $description = $_POST['description'] ?? '';
        if ($id <= 0 || $title === '') {
            $this->json(['ok' => false, 'error' => '参数错误']);
        }

        $coverPath = null;
        $oldCover = $_POST['old_cover'] ?? '';

        // handle new cover upload
        $coverFile = $_FILES['cover_file'] ?? null;
        if ($coverFile && (int)($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $coverTmp = (string)($coverFile['tmp_name'] ?? '');
            if ($coverTmp !== '' && is_uploaded_file($coverTmp)) {
                $ext = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
                    $this->json(['ok' => false, 'error' => '封面仅支持 JPG/PNG/GIF/WEBP']);
                }
                $uploadDir = __DIR__ . '/../../uploads/books';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'cover_' . $userId . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . '/' . $filename;
                if (@move_uploaded_file($coverTmp, $targetPath)) {
                    $coverPath = '/uploads/books/' . $filename;
                    // delete old cover file
                    if ($oldCover !== '') {
                        $oldPath = __DIR__ . '/../../' . ltrim($oldCover, '/');
                        if (is_file($oldPath)) @unlink($oldPath);
                    }
                }
            }
        }

        $pdo = \App\Service\Database::getConnection();
        if ($coverPath !== null) {
            $stmt = $pdo->prepare('UPDATE books SET title = ?, author = ?, cover = ?, description = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$title, $author, $coverPath, $description, $id, $userId]);
        } else {
            $stmt = $pdo->prepare('UPDATE books SET title = ?, author = ?, description = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$title, $author, $description, $id, $userId]);
        }
        $this->json(['ok' => $stmt->rowCount() > 0]);
    }
}
