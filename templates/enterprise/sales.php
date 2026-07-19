<div class="container-fluid py-3">
<h3>🏪 市场销售</h3>
<p>
    企业资金：<strong class="text-success">¥<?= number_format($company['balance']) ?></strong>
    &nbsp;|&nbsp;
    企业等级：<strong>Lv.<?= $company['level'] ?></strong>
    &nbsp;|&nbsp;
    <a href="?route=enterprise" class="btn btn-outline-secondary btn-sm">← 返回企业</a>
</p>

<?php if ((int)$company['level'] < 2): ?>
<div class="alert alert-warning">企业需达到 <strong>2级（微小企业）</strong> 才能开设店铺。</div>
<?php else: ?>
<div class="card mb-3">
    <div class="card-header"><strong>开设新店铺</strong></div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" id="storeName" class="form-control" placeholder="店铺名称">
            </div>
            <div class="col-md-3">
                <select id="storeType" class="form-select">
                    <option value="online">🌐 线上电商 - ¥100,000（日营收 ¥5,000）</option>
                    <option value="offline">🏪 线下门店 - ¥300,000（日营收 ¥15,000）</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" onclick="openStore()">开设店铺</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<h5>已开设店铺</h5>
<div class="row">
    <?php foreach ($storeList as $s): ?>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5><?= $s['type'] === 'online' ? '🌐' : '🏪' ?> <?= htmlspecialchars($s['name']) ?></h5>
                <span class="badge bg-primary"><?= $s['type'] === 'online' ? '线上电商' : '线下门店' ?></span>
                <div class="mt-2">
                    开设成本：¥<?= number_format($s['cost']) ?><br>
                    日营收：<strong class="text-success">¥<?= number_format($s['daily_revenue']) ?></strong><br>
                    <small class="text-muted">每天自动计入企业资金</small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($storeList)): ?>
    <div class="col-12 text-muted text-center py-4">暂无店铺</div>
    <?php endif; ?>
</div>

<?php if ($company['is_listed']): ?>
<div class="card mt-3 border-warning">
    <div class="card-header bg-warning text-dark"><strong>📈 上市信息</strong></div>
    <div class="card-body">
        <p>企业已上市！保证金：<strong>¥<?= number_format($company['listing_deposit']) ?></strong></p>
        <p class="text-muted small">上市后每月自动生成财报，股价受订单完成率和负面事件影响。未来版本将支持增发股票融资。</p>
    </div>
</div>
<?php endif; ?>
</div>

<script>
function openStore() {
    let name = document.getElementById('storeName').value.trim();
    let type = document.getElementById('storeType').value;
    if (!name) return alert('请输入店铺名称');
    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=openStore&name=' + encodeURIComponent(name) + '&type=' + type
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert(d.message); location.reload(); } else alert(d.error);
    });
}
</script>
