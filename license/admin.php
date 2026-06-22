<?php
session_start();
$config = include __DIR__ . '/../config/config.php';

$mainAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$licPwd = $config['license']['admin_password'] ?? ($config['license']['admin_password'] ?? '');
$isAdmin = ($_SESSION['license_admin'] ?? false);

if (!$mainAdmin) { die('<h3>请先登录主站管理员账号</h3><a href="/public/index.php?route=login">前往登录</a>'); }
if ($licPwd === '') { die('<h3>独立密码未配置</h3>'); }

if (!$isAdmin && ($_POST['admin_pass'] ?? '') === $licPwd) { $_SESSION['license_admin'] = true; $isAdmin = true; }

if (!$isAdmin) {
    $error = ($_SERVER['REQUEST_METHOD'] === 'POST') ? '密码错误' : '';
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>验证</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh"><div class="card" style="width:360px"><div class="card-body p-4 text-center"><h5>🔐 验证</h5>' . ($error ? '<div class="alert alert-danger py-1 small">' . $error . '</div>' : '') . '<form method="post"><input name="admin_pass" type="password" class="form-control mb-2" placeholder="独立密码"><button class="btn btn-primary w-100">验证</button></form></div></div></body></html>';
    exit;
}

require_once __DIR__ . '/LicenseManager.php';
$mgr = \License\LicenseManager::getInstance();

// 下载 key.php
if (($_GET['action'] ?? '') === 'download' && !empty($_GET['key'])) {
    $licenseKey = $_GET['key'];
    $stmt = (new PDO('mysql:host=' . ($config['db']['host'] ?? 'localhost') . ';dbname=' . ($config['db']['dbname'] ?? ''), $config['db']['user'] ?? '', $config['db']['pass'] ?? ''))
        ->prepare('SELECT * FROM licenses WHERE license_key = :k');
    $stmt->execute([':k' => $licenseKey]);
    $row = $stmt->fetch();
    if (!$row) die('授权码不存在');

    $payload = json_encode(['license_key' => $row['license_key'], 'email' => $row['email'], 'domain' => $row['domain'], 'expire_date' => $row['expire_date']]);

    $privateKeyFile = __DIR__ . '/keys/private.key';
    if (!file_exists($privateKeyFile)) {
        // 自动生成RSA密钥对
        if (!is_dir(__DIR__ . '/keys')) mkdir(__DIR__ . '/keys', 0700, true);
        $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keyPair, $privateKey);
        $publicKey = openssl_pkey_get_details($keyPair)['key'];
        file_put_contents($privateKeyFile, $privateKey);
        file_put_contents(__DIR__ . '/keys/public.key', $publicKey);
    } else {
        $privateKey = file_get_contents($privateKeyFile);
    }

    openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $signatureB64 = base64_encode($signature);

    $keyContent = "<?php\n// 三石记账授权文件，请勿修改\nreturn " . var_export([
        'license_key' => $row['license_key'],
        'email' => $row['email'],
        'domain' => $row['domain'],
        'expire_date' => $row['expire_date'],
        'signature' => $signatureB64,
        'sig_date' => date('Y-m-d'),
    ], true) . ";\n";

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="key.php"');
    header('Content-Length: ' . strlen($keyContent));
    echo $keyContent;
    exit;
}

// 生成授权码
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $r = $mgr->addLicense($_POST['email'] ?? '', $_POST['domain'] ?? '', $_POST['expire_date'] ?? '');
    $msg = $r['ok'] ? '<div class="alert alert-success py-1">授权码：<strong>' . $r['key'] . '</strong> <a href="?action=download&key=' . $r['key'] . '" class="btn btn-sm btn-outline-primary ms-2">下载 key.php</a></div>' : '<div class="alert alert-danger py-1">' . $r['error'] . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $mgr->toggleActive($_POST['key'] ?? '');
    header('Location: /license/admin.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_qrcode') {
    foreach (['wx','ali','paypal'] as $t) {
        if (!empty($_FILES['qr_'.$t]['tmp_name'])) {
            $ext = pathinfo($_FILES['qr_'.$t]['name'], PATHINFO_EXTENSION);
            $name = 'qr_'.$t.'_'.time().'.'.$ext;
            move_uploaded_file($_FILES['qr_'.$t]['tmp_name'], __DIR__ . '/uploads/' . $name);
            $mgr->saveConfig($t.'_qrcode', $name);
        }
    }
    header('Location: /license/admin.php?tab=qrcode'); exit;
}

$licenses = $mgr->listLicenses();
?>
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>授权管理</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body><div class="container py-4">
<h3>🔐 授权管理</h3>
<?= $msg ?? '' ?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= ($tab ?? 'list') === 'list' ? 'active' : '' ?>" href="?tab=list">📋 授权列表</a></li>
    <li class="nav-item"><a class="nav-link <?= ($tab ?? '') === 'qrcode' ? 'active' : '' ?>" href="?tab=qrcode">📱 收款码配置</a></li>
</ul>

<?php $tab = $_GET['tab'] ?? 'list'; if ($tab === 'qrcode'): ?>
    <form method="post" enctype="multipart/form-data" class="row g-2 mb-3">
        <input type="hidden" name="action" value="save_qrcode">
        <div class="col-4"><label class="small">微信收款码</label><input type="file" name="qr_wx" class="form-control form-control-sm"><?php if ($mgr->getConfig('wx_qrcode')): ?><img src="/license/uploads/<?= $mgr->getConfig('wx_qrcode') ?>" style="max-width:100px;margin-top:4px" alt="微信"><?php endif; ?></div>
        <div class="col-4"><label class="small">支付宝收款码</label><input type="file" name="qr_ali" class="form-control form-control-sm"><?php if ($mgr->getConfig('ali_qrcode')): ?><img src="/license/uploads/<?= $mgr->getConfig('ali_qrcode') ?>" style="max-width:100px;margin-top:4px" alt="支付宝"><?php endif; ?></div>
        <div class="col-4"><label class="small">PayPal</label><input type="file" name="qr_paypal" class="form-control form-control-sm"><?php if ($mgr->getConfig('paypal_qrcode')): ?><img src="/license/uploads/<?= $mgr->getConfig('paypal_qrcode') ?>" style="max-width:100px;margin-top:4px" alt="PayPal"><?php endif; ?></div>
        <div class="col-12 mt-2"><button class="btn btn-sm btn-primary">保存收款码</button></div>
    </form>
<?php else: ?>

<div class="card mb-3"><div class="card-body py-2">
    <form method="post" class="row g-2 align-items-end">
        <input type="hidden" name="action" value="add">
        <div class="col-3"><input name="email" class="form-control form-control-sm" placeholder="用户邮箱" required></div>
        <div class="col-3"><input name="domain" class="form-control form-control-sm" placeholder="绑定域名（一级域名）"></div>
        <div class="col-2"><input name="expire_date" type="date" class="form-control form-control-sm" required></div>
        <div class="col-2"><button class="btn btn-sm btn-primary w-100">生成授权码</button></div>
    </form>
</div></div>

<table class="table table-sm"><thead><tr><th>授权码</th><th>邮箱</th><th>域名</th><th>到期日</th><th>心跳</th><th>状态</th><th>操作</th></tr></thead>
<tbody>
<?php foreach ($licenses as $r): ?>
<tr>
    <td><code><?= $r['license_key'] ?></code></td><td><?= htmlspecialchars($r['email']) ?></td><td><?= htmlspecialchars($r['domain']) ?></td>
    <td><?= $r['expire_date'] ?></td><td><?= $r['last_checkin'] ?: '-' ?></td>
    <td><span class="badge bg-<?= $r['is_active']?'success':'secondary' ?>"><?= $r['is_active']?'启用':'禁用' ?></span></td>
    <td>
        <a href="?action=download&key=<?= $r['license_key'] ?>" class="btn btn-sm btn-outline-primary">下载 key.php</a>
        <form method="post" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="key" value="<?= $r['license_key'] ?>"><button class="btn btn-sm btn-<?= $r['is_active']?'warning':'success' ?>"><?= $r['is_active']?'禁用':'启用' ?></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div></body></html>
