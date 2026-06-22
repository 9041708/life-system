<?php
require_once __DIR__ . '/LicenseManager.php';
$mgr = \License\LicenseManager::getInstance();

$campaign = $mgr->getActiveCampaign();
$price = $campaign ? (float)$campaign['campaign_price'] : 299.00;
$origPrice = $campaign ? 299.00 : null;

$wxQr = $mgr->getConfig('wx_qrcode');
$aliQr = $mgr->getConfig('ali_qrcode');
$ppLink = $mgr->getConfig('paypal_link');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = '<div class="alert alert-danger py-1 small">邮箱格式不正确</div>';
    elseif ($domain === '') $msg = '<div class="alert alert-danger py-1 small">请输入绑定域名</div>';
    else {
        $screenshot = '';
        if (!empty($_FILES['screenshot']['tmp_name'])) {
            if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);
            $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
            $name = uniqid('pay_') . '.' . $ext;
            move_uploaded_file($_FILES['screenshot']['tmp_name'], __DIR__ . '/uploads/' . $name);
            $screenshot = $name;
        }
        // 保存申请到数据库
        $pdo = $mgr->getDb();
        $pdo->prepare('INSERT INTO license_applications (email, domain, payment_screenshot) VALUES (:e, :d, :s)')->execute([':e' => $email, ':d' => $domain, ':s' => $screenshot]);
        $msg = '<div class="alert alert-success py-1 small">申请已提交！管理员将在24小时内回复授权文件到您的邮箱。</div>';
    }
}
?>
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>申请授权 · 三石记账</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height:100vh; font-family:-apple-system,sans-serif; }
.box { max-width:640px; margin:40px auto; background:#fff; border-radius:16px; padding:32px; box-shadow:0 8px 32px rgba(0,0,0,0.12); }
</style></head><body>
<div class="box">
    <h4 class="text-center mb-1">🔐 申请授权</h4>
    <div class="text-center text-muted small mb-3">
        <?php if ($campaign): ?>
        🎉 <strong><?= htmlspecialchars($campaign['name']) ?></strong> 活动价 <span style="color:#ef4444;font-size:1.2rem;font-weight:700">¥<?= number_format($price, 2) ?></span>
        <span style="text-decoration:line-through;color:#999">¥299.00</span>
        截止 <?= $campaign['end_date'] ?>
        <?php else: ?>
        ¥299 永久授权 · 绑定域名 · 24小时内回复
        <?php endif; ?>
    </div>
    <?= $msg ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-2">
            <label class="form-label small fw-semibold">邮箱 <span class="text-danger">*</span></label>
            <input name="email" type="email" class="form-control form-control-sm" placeholder="用于接收授权文件" required>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold">绑定域名 <span class="text-danger">*</span></label>
            <input name="domain" type="text" class="form-control form-control-sm" placeholder="如 example.com（一级域名）" required>
        </div>

        <hr>
        <div class="mb-2 fw-semibold small">📱 扫码付款 ¥<?= number_format($price, 2) ?></div>
        <?php if ($wxQr || $aliQr || $ppLink): ?>
        <div class="d-flex gap-2 justify-content-center mb-3">
            <?php if ($wxQr): ?><button type="button" class="btn btn-success btn-sm" onclick="showQr('wx', '/license/uploads/<?= $wxQr ?>')">💚 微信支付</button><?php endif; ?>
            <?php if ($aliQr): ?><button type="button" class="btn btn-primary btn-sm" onclick="showQr('ali', '/license/uploads/<?= $aliQr ?>')">💙 支付宝</button><?php endif; ?>
            <?php if ($ppLink): ?><a href="<?= htmlspecialchars($ppLink) ?>" target="_blank" class="btn btn-outline-primary btn-sm">💳 PayPal</a><?php endif; ?>
        </div>
        <?php else: ?>
        <div class="text-muted small mb-3">管理员暂未配置收款码，请直接联系</div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label small fw-semibold">付款截图（选填）</label>
            <input type="file" name="screenshot" class="form-control form-control-sm" accept="image/*">
        </div>

        <div class="mb-3 small text-muted">
            📧 9041708@qq.com &nbsp;|&nbsp; 💬 QQ：9041708
        </div>

        <button type="submit" class="btn btn-primary w-100">提交申请</button>
    </form>
</div>

<div id="qrModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center" onclick="this.style.display='none'">
    <div onclick="event.stopPropagation()" style="background:#fff;border-radius:16px;padding:24px;text-align:center;max-width:90vw">
        <div class="fw-bold mb-2" id="qrTitle"></div>
        <img id="qrImg" src="" style="max-width:280px;width:100%;border-radius:8px">
        <div class="small text-muted mt-2">请使用对应App扫码支付 ¥<?= number_format($price, 2) ?></div>
        <button class="btn btn-sm btn-outline-secondary mt-2" onclick="document.getElementById('qrModal').style.display='none'">关闭</button>
    </div>
</div>

<script>
function showQr(type, src) {
    document.getElementById('qrTitle').textContent = type === 'wx' ? '微信扫码支付' : '支付宝扫码支付';
    document.getElementById('qrImg').src = src;
    document.getElementById('qrModal').style.display = 'flex';
}
</script>
</body></html>
