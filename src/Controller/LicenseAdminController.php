<?php
namespace App\Controller;

use App\Service\Config;

class LicenseAdminController
{
    private function requireAdmin(): void
    {
        if (($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: /public/index.php?route=login'); exit; }
        $pwd = Config::get('license.admin_password', '');
        if ($pwd !== '' && ($_SESSION['license_admin'] ?? false) !== true) {
            if (($_POST['admin_pass'] ?? '') === $pwd) { $_SESSION['license_admin'] = true; }
            else { $this->render('license/admin_login', ['error' => $_SERVER['REQUEST_METHOD'] === 'POST' ? '密码错误' : '']); exit; }
        }
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name', '');
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '授权管理';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function getLicMgr()
    {
        require_once __DIR__ . '/../../license/LicenseManager.php';
        return \License\LicenseManager::getInstance();
    }

    private function ensureKeys(): void
    {
        $privateFile = __DIR__ . '/../../license/keys/private.key';
        if (!file_exists($privateFile)) {
            $keyDir = __DIR__ . '/../../license/keys';
            if (!is_dir($keyDir)) mkdir($keyDir, 0700, true);
            $kp = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($kp, $priv);
            $pub = openssl_pkey_get_details($kp)['key'];
            file_put_contents($privateFile, $priv);
            file_put_contents($keyDir . '/public.key', $pub);
            file_put_contents(__DIR__ . '/../../config/license_public.key', $pub);
        }
    }

    public function index(): void
    {
        $this->requireAdmin();
        $this->ensureKeys();
        $mgr = $this->getLicMgr();
        $tab = $_GET['tab'] ?? 'list';
        $msg = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $a = $_POST['action'] ?? '';
            if ($a === 'add') {
                $r = $mgr->addLicense($_POST['email'] ?? '', $_POST['domain'] ?? '', '2099-12-31');
                $msg = $r['ok'] ? '<div class="alert alert-success py-1">授权码：<strong>' . $r['key'] . '</strong> <a href="/public/index.php?route=license-admin-panel&download=1&key=' . $r['key'] . '" class="btn btn-sm btn-outline-primary ms-2">下载 key.php</a></div>' : '<div class="alert alert-danger py-1">' . $r['error'] . '</div>';
            } elseif ($a === 'toggle') {
                $mgr->toggleActive($_POST['key'] ?? '');
                header('Location: /public/index.php?route=license-admin-panel'); exit;
            } elseif ($a === 'delete_lic') {
                $mgr->deleteLicense($_POST['key'] ?? '');
                header('Location: /public/index.php?route=license-admin-panel'); exit;
            } elseif ($a === 'add_campaign') {
                $mgr->addCampaign($_POST['campaign_name'] ?? '', (float)($_POST['campaign_price'] ?? 0), $_POST['campaign_start'] ?? '', $_POST['campaign_end'] ?? '');
                header('Location: /public/index.php?route=license-admin-panel&tab=campaign'); exit;
            } elseif ($a === 'delete_campaign') {
                $mgr->deleteCampaign((int)($_POST['campaign_id'] ?? 0));
                header('Location: /public/index.php?route=license-admin-panel&tab=campaign'); exit;
            } elseif ($a === 'save_qrcode') {
                foreach (['wx','ali'] as $t) {
                    if (!empty($_FILES['qr_'.$t]['tmp_name'])) {
                        $ext = pathinfo($_FILES['qr_'.$t]['name'], PATHINFO_EXTENSION);
                        $name = 'qr_'.$t.'_'.time().'.'.$ext;
                        $dir = __DIR__ . '/../../license/uploads';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        move_uploaded_file($_FILES['qr_'.$t]['tmp_name'], $dir . '/' . $name);
                        $mgr->saveConfig($t.'_qrcode', $name);
                    }
                }
                $ppLink = trim($_POST['paypal_link'] ?? '');
                if ($ppLink !== '') $mgr->saveConfig('paypal_link', $ppLink);
                header('Location: /public/index.php?route=license-admin-panel&tab=qrcode'); exit;
            } elseif ($a === 'approve_app') {
                $appId = (int)($_POST['app_id'] ?? 0);
                $apps = $mgr->listApplications();
                $app = null;
                foreach ($apps as $ap) { if ((int)$ap['id'] === $appId) { $app = $ap; break; } }
                if ($app) {
                    $r = $mgr->addLicense($app['email'], $app['domain'], '2099-12-31');
                    $mgr->deleteApplication($appId);
                    $msg = '<div class="alert alert-success py-1">已生成 <strong>' . ($r['key'] ?? '') . '</strong> <a href="?route=license-admin-panel&download=1&key=' . ($r['key'] ?? '') . '">下载</a></div>';
                }
            } elseif ($a === 'delete_app') {
                $mgr->deleteApplication((int)($_POST['app_id'] ?? 0));
                header('Location: /public/index.php?route=license-admin-panel&tab=pending'); exit;
            }
        }

        // 下载 key.php
        if (isset($_GET['download']) && !empty($_GET['key'])) {
            $licenses = $mgr->listLicenses();
            $row = null;
            foreach ($licenses as $r) { if ($r['license_key'] === $_GET['key']) { $row = $r; break; } }
            if ($row) {
                $payload = json_encode(['license_key' => $row['license_key'], 'email' => $row['email'], 'domain' => $row['domain'], 'expire_date' => $row['expire_date']]);
                $keyDir = __DIR__ . '/../../license/keys';
                $privateFile = $keyDir . '/private.key';
                if (!file_exists($privateFile)) {
                    if (!is_dir($keyDir)) mkdir($keyDir, 0700, true);
                    $kp = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
                    openssl_pkey_export($kp, $priv);
                    $pub = openssl_pkey_get_details($kp)['key'];
                    file_put_contents($privateFile, $priv);
                    file_put_contents($keyDir . '/public.key', $pub);
                    // 同时写入客户端公钥文件
                    file_put_contents(__DIR__ . '/../../config/license_public.key', $pub);
                } else {
                    $priv = file_get_contents($privateFile);
                }
                openssl_sign($payload, $sig, $priv, OPENSSL_ALGO_SHA256);
                $content = "<?php\nreturn " . var_export(['license_key' => $row['license_key'], 'email' => $row['email'], 'domain' => $row['domain'], 'expire_date' => $row['expire_date'], 'signature' => base64_encode($sig), 'sig_date' => date('Y-m-d')], true) . ";\n";
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="key.php"');
                header('Content-Length: ' . strlen($content));
                echo $content; exit;
            }
        }

        $licenses = $mgr->listLicenses();
        $applications = $mgr->listApplications();
        $campaigns = $mgr->listCampaigns();
        $qr = ['wx' => $mgr->getConfig('wx_qrcode'), 'ali' => $mgr->getConfig('ali_qrcode'), 'pp' => $mgr->getConfig('paypal_qrcode'), 'paypal_link' => $mgr->getConfig('paypal_link')];
        $this->render('license/admin_panel', ['tab' => $tab, 'msg' => $msg, 'licenses' => $licenses, 'applications' => $applications, 'campaigns' => $campaigns, 'qr' => $qr, 'pageTitle' => '授权管理']);
    }
}
