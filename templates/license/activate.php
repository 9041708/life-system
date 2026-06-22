<?php
use App\License\LicenseClient;
LicenseClient::init();
$info = LicenseClient::getInfo();
$trialDays = LicenseClient::getTrialDays();
$isActivated = LicenseClient::isActivated();
$uploadMsg = '';
if (isset($_GET['uploaded'])) {
    // 重新初始化以读取刚上传的文件
    LicenseClient::init();
    if (LicenseClient::isActivated()) {
        $uploadMsg = '<div class="alert alert-success py-2 small">✅ 激活成功！授权已生效</div>';
        $isActivated = true;
        $info = LicenseClient::getInfo();
    } else {
        $uploadMsg = '<div class="alert alert-danger py-2 small">❌ 授权文件无效或签名验证失败，请确认文件正确</div>';
    }
} elseif (isset($_GET['err'])) {
    $uploadMsg = '<div class="alert alert-danger py-2 small">❌ 上传失败，请重试</div>';
}
?>
<style>
.lic-wrap { max-width:600px; margin:40px auto; }
.lic-bar { padding:12px 20px; border-radius:10px; margin-bottom:20px; font-weight:600; }
.lic-bar.ok { background:rgba(34,197,94,0.08); color:#16a34a; border:1px solid rgba(34,197,94,0.2); }
.lic-bar.trial { background:rgba(245,158,11,0.08); color:#b45309; border:1px solid rgba(245,158,11,0.2); }
.lic-bar.end { background:rgba(239,68,68,0.08); color:#dc2626; border:1px solid rgba(239,68,68,0.2); }
.lic-card { border:1px solid rgba(0,0,0,0.06); border-radius:12px; padding:24px; background:rgba(255,255,255,0.6); margin-bottom:16px; }
body.theme-dark .lic-card { background:rgba(30,41,59,0.5); border-color:rgba(148,163,184,0.12); }
</style>

<div class="lic-wrap">
    <?= $uploadMsg ?>
    <?php if ($isActivated): ?>
    <div class="lic-bar ok">✅ 已激活 · 到期 <?= htmlspecialchars($info['expire_date'] ?? '永久') ?></div>
    <?php elseif ($trialDays > 0): ?>
    <div class="lic-bar trial">⏳ 试用期还剩 <?= $trialDays ?> 天</div>
    <?php else: ?>
    <div class="lic-bar end">🔒 试用已到期 · 核心功能仍可用</div>
    <?php endif; ?>

    <div class="lic-card">
        <h6>📱 微信小程序</h6>
        <div class="small text-muted">自行注册微信小程序并替换配置后部署。源码在 wwechatxiaochengxu/。</div>
    </div>

    <?php if (!$isActivated): ?>
    <div class="lic-card">
        <h6>💰 获取授权 ¥299（永久）</h6>
        <div class="small text-muted mb-2">
            联系管理员获取授权文件 key.php：<br>
            📧 邮箱：9041708@qq.com &nbsp;|&nbsp; 💬 QQ：9041708<br>
            发送邮件注明绑定域名，管理员将回复 key.php 文件。
        </div>
        <a href="https://9041708.cn:555/license/apply.php" target="_blank" class="btn btn-primary btn-sm">📝 前往申请授权</a>
    </div>

    <div class="lic-card">
        <h6 class="mb-3">📂 上传 key.php 激活</h6>
        <div class="small text-muted mb-2">将管理员发给你的 key.php 文件上传到 data/ 目录，或在此上传：</div>
        <form action="/public/index.php?route=license-upload-key" method="post" enctype="multipart/form-data">
            <div class="input-group input-group-sm">
                <input type="file" name="keyfile" class="form-control" accept=".php">
                <button class="btn btn-primary">上传激活</button>
            </div>
            <div class="form-text">上传后自动保存到 data/key.php，验证通过即激活。</div>
        </form>
    </div>
    <?php endif; ?>
</div>
