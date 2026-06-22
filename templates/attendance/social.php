<?php $sc=$socials??[];?>
<h5>🏛️ 社保公积金</h5>
<button class="btn btn-sm btn-primary mb-2" onclick="new bootstrap.Modal(document.getElementById('socModal')).show()">+ 新增配置</button>
<?php if(empty($sc)):?><div class="text-muted small text-center py-3">暂无配置</div><?php else:foreach($sc as $s):$active=$s['start_date']<=date('Y-m-d')&&(!$s['end_date']||$s['end_date']>=date('Y-m-d'));?>
<div class="border rounded p-2 mb-1 <?=$active?'border-success':''?>" style="background:rgba(255,255,255,0.5)"><div class="d-flex justify-content-between"><div><strong>社保：<?=number_format($s['social_amount'],2)?> &nbsp; 公积金：<?=number_format($s['fund_amount'],2)?></strong></div><span class="badge bg-<?=$active?'success':'secondary'?>"><?=$active?'生效':'过期'?></span><form method="post" class="d-inline"><input type="hidden" name="action" value="del_social"><input type="hidden" name="id" value="<?=$s['id']?>"><button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.6rem">✕</button></form></div><div class="small text-muted"><?=$s['start_date']?> ~ <?=$s['end_date']?:'长期'?></div></div>
<?php endforeach;endif;?>

<div class="modal fade" id="socModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">社保公积金配置</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><label class="form-label small">社保金额</label><input id="socSocial" type="number" step="0.01" class="form-control form-control-sm mb-1"><label class="form-label small">公积金金额</label><input id="socFund" type="number" step="0.01" class="form-control form-control-sm mb-1"><label class="form-label small">开始日期</label><input id="socStart" type="date" class="form-control form-control-sm mb-1" value="<?=date('Y-m-d')?>"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="socLong" checked><label class="form-check-label small" for="socLong">长期有效</label></div><div id="socEndWrap"><label class="form-label small">结束日期</label><input id="socEnd" type="date" class="form-control form-control-sm mb-2"></div><button class="btn btn-sm btn-primary w-100" onclick="addSoc()">保存</button></div>
</div></div></div></div>
<script>
document.getElementById('socLong').addEventListener('change',function(){document.getElementById('socEndWrap').style.display=this.checked?'none':'';});
function addSoc(){var f=new FormData();f.append('action','add_social');f.append('social',document.getElementById('socSocial').value);f.append('fund',document.getElementById('socFund').value);f.append('start',document.getElementById('socStart').value);if(!document.getElementById('socLong').checked)f.append('end',document.getElementById('socEnd').value);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
</script>
