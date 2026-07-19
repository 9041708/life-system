<div class="container py-4">
<div class="row justify-content-center">
<div class="col-lg-6">

<?php if (!empty($bankrupt)): ?>
<!-- 破产恢复 -->
<div class="card shadow border-danger">
<div class="card-body text-center p-5">
    <h2 class="mb-3 text-danger">💔 企业已破产</h2>
    <p class="text-muted">
        你的企业 <strong><?= htmlspecialchars($bankrupt['name']) ?></strong> 已破产清算。<br>
        存活时间：<?= floor($bankrupt['sim_time']/365) ?>年<?= floor(($bankrupt['sim_time']%365)/30) ?>月<?= $bankrupt['sim_time']%30 ?>天 |
        最高等级：Lv.<?= $bankrupt['level'] ?>
    </p>
    <div class="alert alert-warning">重新注册需要更换公司名称</div>

    <div class="mb-3 text-start">
        <label class="form-label fw-bold">新公司名称</label>
        <input type="text" id="companyName" class="form-control form-control-lg" placeholder="请输入新的公司名称（2-20字符）" maxlength="20">
    </div>

    <div class="alert alert-info text-start">
        <strong>注册说明：</strong><br>
        • 注册资本：¥1,000,000（从股票账户扣除）<br>
        • 初始等级：创业公司<br>
        • 赠送8个基础产品
    </div>

    <button class="btn btn-primary btn-lg w-100" onclick="registerCompany()">重新注册</button>
</div>
</div>

<?php else: ?>
<!-- 新注册 -->
<div class="card shadow">
<div class="card-body text-center p-5">
    <h2 class="mb-3">🏢 注册你的企业</h2>
    <p class="text-muted mb-4">注册资本 <strong class="text-primary">¥1,000,000</strong>，将从你的<strong>股票账户</strong>中扣除</p>

    <div class="mb-3 text-start">
        <label class="form-label fw-bold">公司名称</label>
        <input type="text" id="companyName" class="form-control form-control-lg" placeholder="请输入公司名称（2-20字符）" maxlength="20">
        <div class="form-text">支持中文、英文、数字，不支持 &lt;&gt;/ 等特殊字符</div>
    </div>

    <div class="alert alert-info text-start">
        <strong>注册说明：</strong><br>
        • 初始资金：¥1,000,000<br>
        • 初始等级：创业公司<br>
        • 初始员工：0人（注册后需招聘）<br>
        • 注册后不可更改公司名称
    </div>

    <button class="btn btn-primary btn-lg w-100" onclick="registerCompany()">立即注册</button>
    <a href="/public/index.php?route=enterprise-guide" class="btn btn-outline-secondary w-100 mt-2">查看游戏说明</a>
</div>
</div>
<?php endif; ?>

</div>
</div>
</div>

<script>
function registerCompany() {
    const name = document.getElementById('companyName').value.trim();
    if (!name) return alert('请输入公司名称');
    if (name.length < 2 || name.length > 20) return alert('公司名称需2-20个字符');

    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=register&name=' + encodeURIComponent(name)
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            alert(d.message);
            location.reload();
        } else {
            alert(d.error);
        }
    });
}
</script>
