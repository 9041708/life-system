<?php
/** @var array $history */
?>
<style>
.mg-result { border:1px solid rgba(102,126,234,0.2); border-radius:10px; padding:16px; background:rgba(102,126,234,0.04); margin-top:16px; display:none; }
.mg-result .big { font-size:1.8rem; font-weight:700; color:#667eea; }
.mg-result .label { font-size:0.78rem; color:#999; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">🧮 贷款计算器</h5>
    <?php if (!empty($history)): ?>
    <form method="post" class="d-inline" onsubmit="return confirm('确定清空所有计算历史？')">
        <input type="hidden" name="route" value="property-mortgage">
        <input type="hidden" name="action" value="clear_calcs">
        <button type="submit" class="btn btn-sm btn-outline-danger">清空历史</button>
    </form>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">贷款信息</h6>
                <div class="mb-2"><label class="form-label small">贷款总额（万元）</label><input type="number" id="mgPrincipal" class="form-control form-control-sm" step="0.01" value="100" placeholder="如：100"></div>
                <div class="mb-2"><label class="form-label small">年利率（%）</label><input type="number" id="mgRate" class="form-control form-control-sm" step="0.01" value="3.5" placeholder="如：3.5"></div>
                <div class="mb-2"><label class="form-label small">贷款期限（年）</label><input type="number" id="mgYears" class="form-control form-control-sm" value="30" placeholder="如：30"></div>
                <div class="mb-3">
                    <label class="form-label small">还款方式</label>
                    <div class="btn-group btn-group-sm w-100">
                        <input type="radio" class="btn-check" name="mgMethod" id="methodEqual" value="equal" checked><label class="btn btn-outline-primary" for="methodEqual">等额本息</label>
                        <input type="radio" class="btn-check" name="mgMethod" id="methodPrincipal" value="principal"><label class="btn btn-outline-primary" for="methodPrincipal">等额本金</label>
                    </div>
                </div>
                <button class="btn btn-primary w-100" onclick="doCalc()">计算</button>

                <div class="mg-result" id="mgResult">
                    <div class="row text-center g-3">
                        <div class="col-4"><div class="big" id="resMonthly">-</div><div class="label">月供(元)</div></div>
                        <div class="col-4"><div class="big" id="resInterest" style="color:#f97316">-</div><div class="label">总利息(万)</div></div>
                        <div class="col-4"><div class="big" id="resTotal" style="color:#ef4444">-</div><div class="label">还款总额(万)</div></div>
                    </div>
                    <div class="text-muted small text-center mt-2" id="resNote"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <?php if (!empty($history)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">计算历史（最近10次）</h6>
                <?php foreach ($history as $h):
                    $methodText = $h['method'] === 'equal' ? '等额本息' : '等额本金';
                ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="small fw-semibold"><?= (float)$h['principal'] ?>万 · <?= (float)$h['rate'] ?>% · <?= (int)$h['months'] ?>月 · <?= $methodText ?></div>
                        <div class="text-muted" style="font-size:0.72rem">月供 ¥<?= number_format($h['monthly_payment'], 2) ?> · 总利息 ¥<?= number_format($h['total_interest'], 2) ?></div>
                    </div>
                    <form method="post" class="d-inline"><input type="hidden" name="action" value="delete_calc"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.7rem">✕</button></form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function doCalc() {
    var principal = parseFloat(document.getElementById('mgPrincipal').value) || 0;
    var rate = parseFloat(document.getElementById('mgRate').value) || 0;
    var years = parseInt(document.getElementById('mgYears').value) || 0;
    var months = years * 12;
    var method = document.querySelector('input[name="mgMethod"]:checked').value;
    if (principal <= 0 || months <= 0) { alert('请输入有效的贷款信息'); return; }

    var fd = new FormData();
    fd.append('action', 'calc_mortgage');
    fd.append('principal', principal);
    fd.append('rate', rate);
    fd.append('months', months);
    fd.append('method', method);

    fetch('/public/index.php?route=property-api', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.ok) { alert(d.error); return; }
        var r = d.result;
        document.getElementById('resMonthly').textContent = r.monthly.toFixed(2);
        document.getElementById('resInterest').textContent = (r.total_interest / 10000).toFixed(2);
        document.getElementById('resTotal').textContent = (r.total / 10000).toFixed(2);
        document.getElementById('resNote').textContent = method === 'equal'
            ? '等额本息：每月还款金额相同，共' + months + '期'
            : '等额本金：首月还款' + r.monthly.toFixed(2) + '元，逐月递减，共' + months + '期';
        document.getElementById('mgResult').style.display = 'block';
    });
}
</script>
