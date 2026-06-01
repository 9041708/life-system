<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\SystemSetting;
use App\Model\LicenseUser;
use App\Model\LicensePricing;
use App\Model\LicenseRequest;
use App\Model\LicenseMessage;
use App\Service\Mailer;
use App\Model\Log;

class LicenseAdminController
{
    private function ensureAdmin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        $role = (string)($_SESSION['user_role'] ?? 'user');
        if ($role !== 'admin') {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }

    private function render(string $view, array $params = []): void
    {
        $this->ensureAdmin();
        extract($params);
        $appName = Config::get('app.name');
        $view = 'license_admin/' . $view;
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function savePaymentQrs(): void
    {
        $uploadDir = Config::get('app.upload_dir', __DIR__ . '/../../uploads');
        $systemDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($systemDir)) {
            @mkdir($systemDir, 0777, true);
        }

        $map = [
            'wechat_qr' => 'pay_wechat.png',
            'alipay_qr' => 'pay_alipay.png',
            'qq_qr' => 'pay_qq.png',
        ];

        foreach ($map as $field => $filename) {
            if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
                continue;
            }
            $error = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = (string)($_FILES[$field]['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                continue;
            }
            $targetPath = $systemDir . DIRECTORY_SEPARATOR . $filename;
            @move_uploaded_file($tmp, $targetPath);
        }
    }

    /**
     * 固定配置 PC 客户端部署包下载地址。
     * 
     * 为避免受服务器上传大小限制影响，这里不再实际处理上传文件，
     * 直接将下载地址写死到 system_settings.license_source_path 中。
     * 你只需要把部署包手动上传到对应目录即可。
     */
    private function saveSourcePackage(): void
    {
        // 写死为统一路径：/uploads/system/source/2026/01/ssjizhang.zip
        // 如需调整目录或文件名，直接修改下面这一行即可。
        $publicPath = '/uploads/system/source/2026/01/ssjizhang.zip';
        SystemSetting::updateLicenseSourcePath($publicPath);
    }

    public function index(): void
    {
        $this->ensureAdmin();

        $tab = 'logs';  // 只保留系统日志

        // 系统日志
        $logs = [];
        $logFilters = [];
        $logPage = 1;
        $logTotalPages = 1;
        $logTotal = 0;

        $logFilters = [];
        $logPage = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(200, (int)($_GET['per_page'] ?? 50))); // 允许 10-200 条每页，默认 50
        $offset = ($logPage - 1) * $perPage;

        if (!empty($_GET['user_id'])) {
            $logFilters['user_id'] = (int)$_GET['user_id'];
        }
        if (!empty($_GET['action'])) {
            $logFilters['action'] = trim((string)$_GET['action']);
        }
        if (!empty($_GET['date_from'])) {
            $logFilters['date_from'] = trim((string)$_GET['date_from']);
        }
        if (!empty($_GET['date_to'])) {
            $logFilters['date_to'] = trim((string)$_GET['date_to']);
        }

        $logs = Log::search($logFilters, $perPage, $offset);
        $logTotal = Log::count($logFilters);
        $logTotalPages = ceil($logTotal / $perPage);

        $this->render('index', [
            'logs' => $logs,
            'logFilters' => $logFilters,
            'logPage' => $logPage,
            'logTotalPages' => $logTotalPages,
            'logTotal' => $logTotal,
            'perPage' => $perPage,
            'tab' => $tab,
        ]);
    }
}
