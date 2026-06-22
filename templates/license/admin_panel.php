<?php
/** @var string $tab */
/** @var string $msg */
/** @var array $licenses */
/** @var array $applications */
/** @var array $campaigns */
/** @var array $qr */
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="?route=license-admin-panel&tab=pending">🕐 待审批 (<?= count($applications) ?>)</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'list' ? 'active' : '' ?>" href="?route=license-admin-panel&tab=list">📋 已授权</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'campaign' ? 'active' : '' ?>" href="?route=license-admin-panel&tab=campaign">🎉 营销活动</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'qrcode' ? 'active' : '' ?>" href="?route=license-admin-panel&tab=qrcode">📱 收款码</a></li>
</ul>

<?= $msg ?>

<?php if ($tab === 'pending'): ?>
    <?php if (empty($applications)): ?>
    <div class="text-muted text-center py-4">暂无待审批申请</div>
    <?php else: ?>
    <table class="table table-sm"><thead><tr><th>邮箱</th><th>域名</th><th>截图</th><th>时间</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($applications as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['email']) ?></td>
        <td><?= htmlspecialchars($a['domain']) ?></td>
        <td><?php if ($a['payment_screenshot']): ?><a href="/license/uploads/<?= $a['payment_screenshot'] ?>" target="_blank">查看</a><?php else: ?>-<?php endif; ?></td>
        <td><?= $a['created_at'] ?></td>
        <td>
            <form method="post" style="display:inline"><input type="hidden" name="action" value="approve_app"><input type="hidden" name="app_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-success">通过</button></form>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除？')"><input type="hidden" name="action" value="delete_app"><input type="hidden" name="app_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-danger">删除</button></form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>

<?php elseif ($tab === 'campaign'): ?>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
        <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="action" value="add_campaign">
            <div class="col-3"><input name="campaign_name" class="form-control form-control-sm" placeholder="活动名称" required></div>
            <div class="col-2"><input name="campaign_price" type="number" step="0.01" class="form-control form-control-sm" placeholder="活动价(元)" required></div>
            <div class="col-2"><input name="campaign_start" type="date" class="form-control form-control-sm" required></div>
            <div class="col-2"><input name="campaign_end" type="date" class="form-control form-control-sm" required></div>
            <div class="col-1"><button class="btn btn-sm btn-primary w-100">创建</button></div>
        </form>
    </div></div>
    <?php if (empty($campaigns)): ?>
    <div class="text-muted text-center py-4">暂无营销活动</div>
    <?php else: ?>
    <table class="table table-sm"><thead><tr><th>名称</th><th>活动价</th><th>开始</th><th>结束</th><th>状态</th><th>操作</th></tr></thead>
    <tbody>
    <?php $today = date('Y-m-d'); foreach ($campaigns as $c): $active = $c['end_date'] >= $today && $c['start_date'] <= $today; ?>
    <tr>
        <td><?= htmlspecialchars($c['name']) ?></td><td>¥<?= number_format($c['campaign_price'], 2) ?></td>
        <td><?= $c['start_date'] ?></td><td><?= $c['end_date'] ?></td>
        <td><span class="badge bg-<?= $active ? 'success' : 'secondary' ?>"><?= $active ? '进行中' : ($c['end_date'] < $today ? '已结束' : '待开始') ?></span></td>
        <td><form method="post" style="display:inline" onsubmit="return confirm('确定删除？')"><input type="hidden" name="action" value="delete_campaign"><input type="hidden" name="campaign_id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-danger">删除</button></form></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>

<?php elseif ($tab === 'qrcode'): ?>
    <div class="card border-0 shadow-sm"><div class="card-body">
    <form method="post" enctype="multipart/form-data" class="row g-2">
        <input type="hidden" name="action" value="save_qrcode">
        <div class="col-4"><label class="small">微信收款码</label><input type="file" name="qr_wx" class="form-control form-control-sm"><?php if ($qr['wx']): ?><img src="/license/uploads/<?= $qr['wx'] ?>" style="max-width:100px;margin-top:4px"><?php endif; ?></div>
        <div class="col-4"><label class="small">支付宝收款码</label><input type="file" name="qr_ali" class="form-control form-control-sm"><?php if ($qr['ali']): ?><img src="/license/uploads/<?= $qr['ali'] ?>" style="max-width:100px;margin-top:4px"><?php endif; ?></div>
        <div class="col-4"><label class="small">PayPal 付款链接</label><input type="text" name="paypal_link" class="form-control form-control-sm" placeholder="https://paypal.me/xxx" value="<?= htmlspecialchars($qr['paypal_link'] ?? '') ?>"><div class="form-text small">PayPal 仅支持链接</div></div>
        <div class="col-12 mt-2"><button class="btn btn-sm btn-primary">保存</button></div>
    </form>
    </div></div>

<?php else: ?>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
        <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="action" value="add">
            <div class="col-4"><input name="email" class="form-control form-control-sm" placeholder="用户邮箱" required></div>
            <div class="col-4"><input name="domain" class="form-control form-control-sm" placeholder="绑定域名"></div>
            <div class="col-2"><button class="btn btn-sm btn-primary w-100">生成授权码</button></div>
        </form>
    </div></div>

    <div class="table-responsive">
    <table class="table table-sm"><thead><tr><th>授权码</th><th>邮箱</th><th>域名</th><th>创建日期</th><th>状态</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($licenses as $r): ?>
    <tr>
        <td><code><?= $r['license_key'] ?></code></td><td><?= htmlspecialchars($r['email']) ?></td><td><?= htmlspecialchars($r['domain']) ?></td>
        <td><?= substr($r['created_at'], 0, 10) ?></td>
        <td><span class="badge bg-<?= $r['is_active']?'success':'secondary' ?>"><?= $r['is_active']?'启用':'禁用' ?></span></td>
        <td>
            <a href="?route=license-admin-panel&download=1&key=<?= $r['license_key'] ?>" class="btn btn-sm btn-outline-primary">下载</a>
            <form method="post" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="key" value="<?= $r['license_key'] ?>"><button class="btn btn-sm btn-<?= $r['is_active']?'warning':'success' ?>"><?= $r['is_active']?'禁用':'启用' ?></button></form>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除？')"><input type="hidden" name="action" value="delete_lic"><input type="hidden" name="key" value="<?= $r['license_key'] ?>"><button class="btn btn-sm btn-danger">删除</button></form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
    </div>
<?php endif; ?>
