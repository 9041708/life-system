<div style="padding:24px 16px;max-width:400px;margin:0 auto;">
<h4 class="text-center mb-3">🏢 注册你的企业</h4>
<p class="text-muted small text-center mb-3">注册资本 <strong class="text-primary">¥1,000,000</strong>，从股票账户扣除</p>

<div class="mb-3">
    <label class="form-label fw-bold small">公司名称</label>
    <input type="text" id="companyName" class="form-control" placeholder="2-20字符" maxlength="20">
    <div class="form-text small">中文/英文/数字，不支持 &lt;&gt;/</div>
</div>

<div class="alert alert-info small">
    • 初始资金：¥1,000,000<br>
    • 初始等级：创业公司<br>
    • 注册后赠送8个基础产品
</div>

<button class="btn btn-primary w-100" onclick="registerCompany()">立即注册</button>
<a href="?route=enterprise-guide" class="btn btn-outline-secondary w-100 mt-2">查看游戏说明</a>
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
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert(d.message); location.reload(); } else alert(d.error);
    });
}
</script>
