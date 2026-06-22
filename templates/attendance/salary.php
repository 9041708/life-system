<?php $ym=$ym??date('Y-m');$cf=$cfg??null;$ac=$actual??null;?>
<style>.sal-card{border:1px solid rgba(0,0,0,0.06);border-radius:10px;padding:14px;margin-bottom:8px;background:rgba(255,255,255,0.5)}body.theme-dark .sal-card{background:rgba(30,41,59,0.4)}.sal-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}.sal-item{flex:1;min-width:100px;text-align:center;padding:10px;border-radius:8px}.sal-big{font-size:1.5rem;font-weight:800}</style>
<h5>💰 薪资计算</h5>
<div class="d-flex align-items-center gap-2 mb-2"><a href="?route=attendance-salary&ym=<?=date('Y-m',strtotime($ym.' -1 month'))?>" class="btn btn-sm btn-outline-secondary">◀</a><span class="fw-bold"><?=$ym?></span><a href="?route=attendance-salary&ym=<?=date('Y-m',strtotime($ym.' +1 month'))?>" class="btn btn-sm btn-outline-secondary">▶</a></div>

<div class="sal-row">
    <div class="sal-item" style="background:rgba(34,197,94,0.06)"><div class="text-muted small">底薪</div><div class="sal-big"><?=number_format($base??0,2)?></div></div>
    <div class="sal-item" style="background:rgba(34,197,94,0.06)"><div class="text-muted small">绩效</div><div class="sal-big"><?=number_format($perf??0,2)?></div></div>
    <div class="sal-item" style="background:rgba(34,197,94,0.06)"><div class="text-muted small">补贴</div><div class="sal-big"><?=number_format($sub??0,2)?></div></div>
    <div class="sal-item" style="background:rgba(34,197,94,0.06);border:2px solid #22c55e"><div class="text-muted small">应发合计</div><div class="sal-big" style="color:#16a34a"><?=number_format($income??0,2)?></div></div>
</div><div class="sal-row">
    <div class="sal-item" style="background:rgba(239,68,68,0.06)"><div class="text-muted small">扣款</div><div class="sal-big" style="color:#ef4444"><?=number_format($totalDed??0,2)?></div></div>
    <div class="sal-item" style="background:rgba(239,68,68,0.06)"><div class="text-muted small">社保</div><div class="sal-big" style="color:#ef4444"><?=number_format($totalSocial??0,2)?></div></div>
    <div class="sal-item" style="background:rgba(239,68,68,0.06)"><div class="text-muted small">公积金</div><div class="sal-big" style="color:#ef4444"><?=number_format($totalFund??0,2)?></div></div>
    <div class="sal-item" style="background:rgba(239,68,68,0.06);border:2px solid #ef4444"><div class="text-muted small">应扣合计</div><div class="sal-big" style="color:#ef4444"><?=number_format($deduct??0,2)?></div></div>
</div><div class="sal-row">
    <div class="sal-item" style="background:rgba(102,126,234,0.08);border:2px solid rgba(102,126,234,0.3)"><div class="text-muted small">应实发</div><div class="sal-big" style="color:#667eea"><?=number_format($net??0,2)?></div></div>
    <div class="sal-item" style="background:rgba(245,158,11,0.08)">
         <div class="text-muted small">实际到手 <button class="btn btn-sm btn-outline-warning py-0" style="font-size:0.6rem" onclick="new bootstrap.Modal(document.getElementById('actualModal')).show()">✏️</button></div>
        <div class="sal-big" style="color:#b45309"><?=($ac?number_format($ac['actual_amount'],2):'-')?></div>
    </div>
</div>

<div class="sal-card"><h6 class="small">⚙️ 薪资配置 <button class="btn btn-sm btn-outline-primary py-0" style="font-size:0.6rem" onclick="new bootstrap.Modal(document.getElementById('cfgModal')).show()">+</button></h6>
    <?php if($cf):?><div class="small">底薪<?=number_format($cf['base_salary'],2)?> + 绩效<?=number_format($cf['performance'],2)?> + 补贴<?=number_format($cf['subsidy'],2)?> · 生效:<?=$cf['effective_from']?></div><?php else:?><div class="text-muted small">未配置</div><?php endif;?>
</div>
<div class="text-muted small">💡 <a href="?route=attendance-deduction">扣款管理</a> | <a href="?route=attendance-social">社保公积金</a> | 数据自动从以上页面获取</div>

<!-- 配置弹窗 -->
<div class="modal fade" id="cfgModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">薪资配置</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <label class="form-label small">底薪</label><input id="cfgBase" type="number" step="0.01" class="form-control form-control-sm mb-1" value="<?=$cf['base_salary']??0?>">
    <label class="form-label small">补贴</label><input id="cfgSub" type="number" step="0.01" class="form-control form-control-sm mb-1" value="<?=$cf['subsidy']??0?>">
    <label class="form-label small">生效日期</label><input id="cfgDate" type="date" class="form-control form-control-sm mb-2" value="<?=$cf['effective_from']??date('Y-m-d')?>">
    <button class="btn btn-sm btn-primary w-100" onclick="saveCfg()">保存</button>
</div></div></div></div>

<div class="modal fade" id="actualModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">实际到手</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" id="actMonth" value="<?=$ym?>">
    <label class="form-label small">金额</label><input id="actAmt" type="number" step="0.01" class="form-control form-control-sm mb-1" value="<?=$ac['actual_amount']??0?>">
    <label class="form-label small">备注</label><input id="actNote" class="form-control form-control-sm mb-2" value="<?=htmlspecialchars($ac['note']??'')?>">
    <button class="btn btn-sm btn-primary w-100" onclick="saveActual()">保存</button>
</div></div></div></div>

<script>
function saveCfg(){var f=new FormData();f.append('action','save_salary_cfg');f.append('base',document.getElementById('cfgBase').value);f.append('subsidy',document.getElementById('cfgSub').value);f.append('effective_from',document.getElementById('cfgDate').value);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function saveActual(){var f=new FormData();f.append('action','save_actual');f.append('month',document.getElementById('actMonth').value);f.append('amount',document.getElementById('actAmt').value);f.append('note',document.getElementById('actNote').value);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
</script>

<hr class="my-4">
<h5>📊 收入统计</h5>
<div class="row g-3">
    <div class="col-md-4">
        <div class="sal-card" style="border-color:#22c55e">
            <h6 class="small text-muted">🔸 季度收入</h6>
            <div class="small mb-1">📅 <?=$quarter['start']?> ~ <?=$quarter['end']?></div>
            <div class="d-flex justify-content-between mb-1"><span>基本工资</span><span class="fw-bold"><?=number_format($quarter['base']??0,2)?></span></div>
            <div class="d-flex justify-content-between mb-1"><span>绩效</span><span class="fw-bold"><?=number_format($quarter['perf']??0,2)?></span></div>
            <div class="d-flex justify-content-between mb-1"><span>补贴</span><span class="fw-bold"><?=number_format($quarter['sub']??0,2)?></span></div>
            <div class="d-flex justify-content-between mt-2 pt-2" style="border-top:2px solid #22c55e"><span class="fw-bold">净收入</span><span class="fw-bold" style="color:#16a34a;font-size:1.2rem"><?=number_format(($quarter['base']??0)+($quarter['perf']??0)+($quarter['sub']??0),2)?></span></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sal-card" style="border-color:#3b82f6">
            <h6 class="small text-muted">🔸 半年收入</h6>
            <div class="small mb-1">📅 <?=$half['start']?> ~ <?=$half['end']?></div>
            <div class="d-flex justify-content-between mb-1"><span>基本工资</span><span class="fw-bold"><?=number_format($half['base']??0,2)?></span></div>
            <div class="d-flex justify-content-between mb-1"><span>绩效</span><span class="fw-bold"><?=number_format($half['perf']??0,2)?></span></div>
            <div class="d-flex justify-content-between mb-1"><span>补贴</span><span class="fw-bold"><?=number_format($half['sub']??0,2)?></span></div>
            <div class="d-flex justify-content-between mt-2 pt-2" style="border-top:2px solid #3b82f6"><span class="fw-bold">净收入</span><span class="fw-bold" style="color:#16a34a;font-size:1.2rem"><?=number_format(($half['base']??0)+($half['perf']??0)+($half['sub']??0),2)?></span></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sal-card" style="border-color:#8b5cf6">
            <h6 class="small text-muted">🔸 年度收入</h6>
            <div class="small mb-1">📅 <?=$yearStats['start']?> ~ <?=$yearStats['end']?></div>
            <div class="d-flex justify-content-between mb-1"><span>基本工资</span><span class="fw-bold"><?=number_format($yearStats['base']??0,2)?></span></div>
            <div class="d-flex justify-content-between mb-1"><span>绩效</span><span class="fw-bold"><?=number_format($yearStats['perf']??0,2)?></span></div>
            <div class="d-flex justify-content-between mb-1"><span>补贴</span><span class="fw-bold"><?=number_format($yearStats['sub']??0,2)?></span></div>
            <div class="d-flex justify-content-between mt-2 pt-2" style="border-top:2px solid #8b5cf6"><span class="fw-bold">净收入</span><span class="fw-bold" style="color:#16a34a;font-size:1.2rem"><?=number_format(($yearStats['base']??0)+($yearStats['perf']??0)+($yearStats['sub']??0),2)?></span></div>
        </div>
    </div>
</div>
