<style>
.shelf-card { border-radius: 0.75rem; }
.shelf-result { font-size: 1.15rem; font-weight: 700; color: #059669; }
body.theme-dark .shelf-result { color: #34d399; }
</style>

<h5 class="mb-3">📆 保质期计算器</h5>

<div class="card glass-card shelf-card" style="max-width:600px;">
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label small fw-semibold">生产日期</label>
                <input type="datetime-local" id="prodDate" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i:00') ?>">
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">保质期限</label>
                <div class="input-group input-group-sm">
                    <input type="number" id="shelfValue" class="form-control form-control-sm" value="0" min="0" step="1" style="max-width:120px;">
                    <select id="shelfUnit" class="form-select form-select-sm" style="max-width:100px;">
                        <option value="day">天</option>
                        <option value="month">月</option>
                        <option value="year">年</option>
                    </select>
                    <button class="btn btn-primary" onclick="calcShelf()">计算到期日期</button>
                </div>
            </div>
            <div class="col-12">
                <div class="row g-2">
                    <div class="col-auto">
                        <div class="p-3 border rounded-3 text-center" style="background:rgba(5,150,105,0.06);min-width:180px;">
                            <div class="small text-muted mb-1">📦 生产日期</div>
                            <div class="fw-bold" id="prodDisplay">—</div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="p-3 border rounded-3 text-center" style="background:rgba(217,119,6,0.06);min-width:180px;">
                            <div class="small text-muted mb-1">⏰ 到期日期</div>
                            <div class="shelf-result" id="expDisplay">—</div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="p-3 border rounded-3 text-center" style="background:rgba(37,99,235,0.06);min-width:180px;">
                            <div class="small text-muted mb-1">📊 剩余天数</div>
                            <div class="fw-bold" id="remainDisplay">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatDT(d) {
    var y = d.getFullYear();
    var M = ('0' + (d.getMonth()+1)).slice(-2);
    var day = ('0' + d.getDate()).slice(-2);
    var h = ('0' + d.getHours()).slice(-2);
    var m = ('0' + d.getMinutes()).slice(-2);
    return y + '-' + M + '-' + day + ' ' + h + ':' + m;
}

function calcShelf() {
    var prodVal = document.getElementById('prodDate').value;
    if (!prodVal) { alert('请选择生产日期'); return; }
    var prod = new Date(prodVal);
    document.getElementById('prodDisplay').textContent = formatDT(prod);

    var value = parseInt(document.getElementById('shelfValue').value) || 0;
    var unit = document.getElementById('shelfUnit').value;

    var exp = new Date(prod);
    if (unit === 'day') exp.setDate(exp.getDate() + value);
    else if (unit === 'month') exp.setMonth(exp.getMonth() + value);
    else if (unit === 'year') exp.setFullYear(exp.getFullYear() + value);

    document.getElementById('expDisplay').textContent = formatDT(exp);

    var now = new Date();
    var remainDays = Math.ceil((exp - now) / (1000 * 60 * 60 * 24));
    document.getElementById('remainDisplay').textContent = remainDays > 0 ? '剩余 ' + remainDays + ' 天' : (remainDays === 0 ? '今天到期' : '已过期 ' + Math.abs(remainDays) + ' 天');
}

calcShelf();
</script>
