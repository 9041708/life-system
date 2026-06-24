<?php
namespace App\Service;

use App\Service\Config;

class Upload
{
    public static function saveAttachment(int $userId, array $file): ?string
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            return null;
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $date = new \DateTime();
        $subPath = $userId . '/' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d');
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = uniqid('att_', true) . ($ext ? ('.' . $ext) : '');
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        // 杩斿洖鐩稿璺緞锛屼緵鍓嶇璁块棶鏃舵嫾鎺?/uploads/
        return $subPath . '/' . $safeName;
    }

    /**
     * 淇濆瓨鏂囨湰鍐呭涓?uploads 涓嬬殑鏂囦欢锛堜緥濡傜矘璐寸殑 SVG锛夈€?     *
     * 杩斿洖鐩稿璺緞锛屼緵鍓嶇璁块棶鏃舵嫾鎺?/uploads/
     */
    public static function saveTextFile(int $userId, string $content, string $ext, string $prefix = 'att_'): ?string
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }

        // 绠€鍗曢檺鍒讹細閬垮厤寮傚父瓒呭ぇ鏂囨湰
        if (strlen($content) > 512 * 1024) {
            return null;
        }

        $ext = strtolower(trim($ext));
        if ($ext === '') {
            $ext = 'txt';
        }
        if (!preg_match('/^[a-z0-9]+$/', $ext)) {
            $ext = 'txt';
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $date = new \DateTime();
        $subPath = $userId . '/' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d');
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $safeName = uniqid($prefix, true) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (@file_put_contents($targetPath, $content) === false) {
            return null;
        }

        return $subPath . '/' . $safeName;
    }

    /**
     * 灏?base64 缂栫爜鐨勫浘鐗囦繚瀛樹负闄勪欢鏂囦欢銆?     *
     * 鏀寔鏍煎紡锛歞ata:image/jpeg;base64,... 鎴栫函 base64 瀛楃涓层€?     * 杩斿洖鐩稿璺緞锛屼緵鍓嶇璁块棶鏃舵嫾鎺?/uploads/
     */
    public static function saveBase64Image(int $userId, string $base64Data): ?string
    {
        $base64Data = trim($base64Data);
        if ($base64Data === '') {
            return null;
        }

        $ext = 'jpg';
        // 瑙ｆ瀽 data URI 鏍煎紡锛歞ata:image/png;base64,xxxxx
        if (preg_match('#^data:image/(\w+);base64,#i', $base64Data, $m)) {
            $typeExt = strtolower($m[1]);
            if (in_array($typeExt, ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp'], true)) {
                $ext = $typeExt === 'jpeg' ? 'jpg' : $typeExt;
            }
            $base64Data = substr($base64Data, strlen($m[0]));
        }

        $binary = base64_decode($base64Data, true);
        if ($binary === false || strlen($binary) === 0) {
            return null;
        }

        // 闄愬埗鍗曞紶鍥剧墖 10MB
        if (strlen($binary) > 10 * 1024 * 1024) {
            return null;
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $date = new \DateTime();
        $subPath = $userId . '/' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d');
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $safeName = uniqid('att_', true) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (@file_put_contents($targetPath, $binary) === false) {
            return null;
        }

        return $subPath . '/' . $safeName;
    }

    /**
     * 涓虹敤鎴蜂繚瀛樺ご鍍忔枃浠讹紙鏉ヨ嚜琛ㄥ崟涓婁紶锛夈€?     */
    public static function saveAvatar(int $userId, array $file): ?string
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        // 澶村儚闄愬埗涓?5MB 浠ュ唴
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $date = new \DateTime();
        $subPath = $userId . '/' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d');
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $ext = strtolower((string)pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        $safeName = uniqid('avatar_', true) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        return $subPath . '/' . $safeName;
    }

    /**
     * 浠庤繙绋?URL 涓嬭浇澶村儚骞朵繚瀛樺埌 uploads 鐩綍銆?     */
    public static function saveAvatarFromUrl(int $userId, string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
            'https' => [
                'timeout' => 5,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            return null;
        }

        // 绠€鍗曢檺鍒讹細涓嶈秴杩?5MB
        if (strlen($data) > 5 * 1024 * 1024) {
            return null;
        }

        if (!function_exists('getimagesizefromstring')) {
            return null;
        }
        $info = @getimagesizefromstring($data);
        if ($info === false) {
            return null;
        }

        $mime = (string)($info['mime'] ?? '');
        $ext = 'jpg';
        if ($mime === 'image/png') {
            $ext = 'png';
        } elseif ($mime === 'image/gif') {
            $ext = 'gif';
        } elseif ($mime === 'image/webp') {
            $ext = 'webp';
        }

        $date = new \DateTime();
        $subPath = $userId . '/' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d');
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $safeName = uniqid('avatar_', true) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (@file_put_contents($targetPath, $data) === false) {
            return null;
        }

        return $subPath . '/' . $safeName;
    }

    /**
     * 淇濆瓨 PC 绔儗鏅浘鍒?uploads 鐩綍銆?     * 闄愬埗 5MB锛屾敮鎸?jpg/png/webp锛岃繑鍥炵浉瀵硅矾寰勶紙瀛?DB 鐢級銆?     */
    public static function saveBgImage(array $file): ?string
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return null;
        }

        $subPath = 'bg';
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return null;
            }
        }

        $ext = strtolower((string)pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }
        $safeName = uniqid('bg_', true) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        return $subPath . '/' . $safeName;
    }

    /**
     * 淇濆瓨灏忕▼搴忕爜鍥剧墖鍒?uploads/miniapp 鐩綍銆?     * 闄愬埗 2MB锛屾敮鎸?jpg/png/webp锛岃繑鍥炵浉瀵硅矾寰勩€?     */
    public static function saveMiniappQrcode(array $file): string
    {
        $baseDir = Config::get('app.upload_dir');
        $subPath = 'miniapp';
        $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subPath;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $ext = strtolower((string)pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) $ext = 'png';
        $safeName = uniqid('mp_', true) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;
        move_uploaded_file($file['tmp_name'], $targetPath);
        return $subPath . '/' . $safeName;
    }

    /**
     * 鍒犻櫎 uploads 鐩綍涓嬬殑鐩稿璺緞鏂囦欢锛堝ご鍍忔垨闄勪欢锛夈€?     */
    public static function deleteByRelativePath(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $baseDir = Config::get('app.upload_dir');
        if (!$baseDir) {
            return;
        }
        $relativePath = ltrim($relativePath, '/\\');
        $fullPath = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
