<div class="container-fluid py-3">
<style>
.mall-card { border: 1px solid var(--bs-border-color); border-radius: 10px; padding: 16px; margin-bottom: 12px; text-align: center; }
.mall-card .cat-icon { font-size: 2rem; }
</style>

<h3>🏗️ 企业商城</h3>
<p class="text-muted mb-0">
    企业资金：<strong class="text-success">¥<?= number_format($company['balance']) ?></strong>
    &nbsp;|&nbsp;
    <a href="?route=enterprise" class="btn btn-outline-secondary btn-sm">← 返回企业</a>
</p>

<div class="row mt-3">
<?php
$allCats = [
    '装修' => ['icon' => '🏗️', 'name' => '办公装修', 'desc' => '场地面积 +50㎡/级，提升客户好感', 'base' => 500000, 'rate' => 0.3],
    '网络' => ['icon' => '🌐', 'name' => '网络设施', 'desc' => '订单处理速度 -5%/级', 'base' => 100000, 'rate' => 0.2],
    '桌椅' => ['icon' => '🪑', 'name' => '办公桌椅', 'desc' => '员工舒适度 +5%/级', 'base' => 200000, 'rate' => 0.25],
    '设备' => ['icon' => '⚙️', 'name' => '生产设备', 'desc' => '产能 +20%/级', 'base' => 800000, 'rate' => 0.4],
];

$assetMap = [];
foreach ($assetList as $a) $assetMap[$a['category']] = $a;

foreach ($allCats as $key => $info):
    $existing = $assetMap[$key] ?? null;
    $currentLevel = $existing ? (int)$existing['level'] : 0;
    $nextLevel = $currentLevel + 1;
    $maxLevel = 10;
    $cost = $currentLevel > 0
        ? round($info['base'] * pow(1 + $info['rate'], $nextLevel - 2))
        : $info['base'];
    $canBuy = $currentLevel < $maxLevel && (float)$company['balance'] >= $cost;
?>
<div class="col-md-6 col-lg-3">
    <div class="mall-card">
        <div class="cat-icon"><?= $info['icon'] ?></div>
        <h5><?= $info['name'] ?></h5>
        <p class="text-muted small"><?= $info['desc'] ?></p>
        <div>当前等级: <strong><?= $currentLevel ?></strong> / <?= $maxLevel ?></div>
        <?php if ($currentLevel >= $maxLevel): ?>
            <button class="btn btn-secondary btn-sm w-100 mt-2" disabled>已满级</button>
        <?php else: ?>
            <div class="mt-1">升级费用: <strong class="<?= $canBuy ? 'text-success' : 'text-danger' ?>">¥<?= number_format($cost) ?></strong></div>
            <button class="btn <?= $canBuy ? 'btn-primary' : 'btn-secondary' ?> btn-sm w-100 mt-1"
                <?= !$canBuy ? 'disabled' : '' ?>
                onclick="buy('<?= $key ?>')">
                <?= $currentLevel > 0 ? "升级至 {$nextLevel} 级" : "购买" ?>
            </button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>

<script>
function buy(cat) {
    if (!confirm('确定购买/升级 ' + cat + ' 吗？')) return;
    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=buyAsset&category=' + encodeURIComponent(cat)
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert(d.message); location.reload(); } else alert(d.error);
    });
}
</script>
