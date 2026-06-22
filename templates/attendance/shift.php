<?php $cal=$records??[];$st=$stats??[];$sh=$shifts??[];$m=$month??date('n');$y=$year??date('Y');$daysInMonth=cal_days_in_month(CAL_GREGORIAN,$m,$y);$firstDay=(int)date('w',strtotime("$y-$m-01"));$cm=$company??null;$cd=$cm?floor((time()-strtotime($cm['join_date']))/86400):0;$companies=$companies??[];?>
<style>.att-wrap{background:rgba(255,255,255,0.88);border-radius:12px;padding:16px;margin-bottom:12px}body.theme-dark .att-wrap{background:rgba(15,23,42,0.88)}.att-cal{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}.att-cell{min-height:74px;border:1px solid rgba(0,0,0,0.08);border-radius:6px;padding:5px;cursor:pointer;font-size:0.85rem;background:rgba(255,255,255,0.75)}body.theme-dark .att-cell{background:rgba(30,41,59,0.55)}.att-cell .dnum{font-weight:700;font-size:1rem;margin-bottom:2px}.att-cell .tag{display:inline-block;padding:2px 6px;border-radius:3px;font-size:0.7rem;font-weight:600}.att-cell.present{border-left:4px solid #22c55e}.att-cell.absent{border-left:4px solid #ef4444}.att-cell.late{border-left:4px solid #f59e0b}.att-cell.leave{border-left:4px solid #3b82f6}.att-cell.rest{border-left:4px solid #9ca3af}.att-stat{text-align:center;padding:14px 10px;border-radius:10px;font-size:0.92rem;background:rgba(255,255,255,0.6)}body.theme-dark .att-stat{background:rgba(30,41,59,0.45)}</style>
<div class="att-wrap">
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">🏢 出勤管理
    <?php if($cm):?><span class="ms-2 badge bg-success" style="font-size:0.7rem"><?=htmlspecialchars($cm['company_name'])?> · <?=$cd?>天</span><?php else:?><button class="btn btn-sm btn-outline-primary ms-2" onclick="openJoinModal()">+ 加入企业</button><?php endif;?></h5>
    <a href="?route=attendance-schedule" class="btn btn-sm btn-outline-secondary">排班管理 →</a>
    <?php if(!empty($companies)):?><button class="btn btn-sm btn-outline-secondary" onclick="openCompanyList()">企业历史</button><?php endif;?>
</div>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div><a href="?route=attendance-shift&ym=<?=date('Y-m',strtotime($ym.' -1 month'))?>" class="btn btn-sm btn-outline-secondary">◀</a> <span class="fw-bold"><?=$y?>年<?=$m?>月</span> <a href="?route=attendance-shift&ym=<?=date('Y-m',strtotime($ym.' +1 month'))?>" class="btn btn-sm btn-outline-secondary">▶</a></div>
    <button class="btn btn-sm btn-outline-danger" onclick="clearMonth()">清空本月</button>
</div>
<div class="att-cal">
    <?php $wdNames=['日','一','二','三','四','五','六'];foreach($wdNames as $wd)echo"<div class='text-center text-muted small fw-bold py-1'>$wd</div>";
    for($i=0;$i<$firstDay;$i++)echo'<div></div>';
    for($d=1;$d<=$daysInMonth;$d++):$date=sprintf('%04d-%02d-%02d',$y,$m,$d);$r=$cal[$date]??null;$st=$r?$r['status']:'';$cls=$st?:'';?>
    <div class="att-cell <?=$cls?>" onclick="openRecord('<?=$date?>','<?=$st?>','<?=htmlspecialchars(addslashes($r['note']??''))?>')"><div class="dnum"><?=$d?></div>
        <?php if($r):$tl=['present'=>'出勤','absent'=>'缺勤','late'=>'迟到','leave'=>'请假','rest'=>'休息'];$tc=['present'=>'bg-success','absent'=>'bg-danger','late'=>'bg-warning text-dark','leave'=>'bg-primary','rest'=>'bg-secondary'];?><span class="tag <?=$tc[$st]??''?>"><?=$tl[$st]??$st?></span><?php endif;?></div>
    <?php endfor;?>
</div>
<?php $total=$daysInMonth;$worked=($st['present']??0)+($st['late']??0);$rested=$st['rest']??0;$absent=$st['absent']??0;$leaveHours=0;foreach($leaves as $lv){$leaveHours+=$lv['hours']??0;}$leaveDays=round($leaveHours/8,1);$should=$total-4*(int)ceil($total/7);?>
<div class="row g-2 mt-3">
    <div class="col"><div class="att-stat bg-success bg-opacity-10"><div class="fw-bold fs-5"><?=$worked?></div><div class="small text-muted">实际出勤</div></div></div>
    <div class="col"><div class="att-stat bg-secondary bg-opacity-10"><div class="fw-bold fs-5"><?=$should?></div><div class="small text-muted">应出勤</div></div></div>
    <div class="col"><div class="att-stat bg-warning bg-opacity-10"><div class="fw-bold fs-5"><?=$rested?></div><div class="small text-muted">休息</div></div></div>
    <div class="col"><div class="att-stat bg-primary bg-opacity-10"><div class="fw-bold fs-5"><?=$leaveDays?><small class="text-muted">天</small></div><div class="small text-muted">请假<?=$leaveHours>0?'（'.$leaveHours.'小时）':''?></div></div></div>
    <div class="col"><div class="att-stat bg-danger bg-opacity-10"><div class="fw-bold fs-5"><?=$absent?></div><div class="small text-muted">缺勤</div></div></div>
</div>
</div>

<div class="modal fade" id="recordModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">出勤记录</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" id="recDate">
    <label class="form-label small">状态</label>
    <select id="recStatus" class="form-select form-select-sm mb-2" onchange="this.value==='leave'?document.getElementById('leaveHoursDiv').style.display='block':document.getElementById('leaveHoursDiv').style.display='none'"><?php foreach(['present'=>'🟢 出勤','absent'=>'🔴 缺勤','late'=>'🟡 迟到','leave'=>'🔵 请假','rest'=>'⚪ 休息'] as $k=>$v)echo"<option value=\"$k\">$v</option>";?></select>
    <label class="form-label small">备注</label><input id="recNote" class="form-control form-control-sm mb-2" placeholder="选填">
    <div id="leaveHoursDiv" style="display:none"><label class="form-label small">请假小时数（4小时=半天）</label><input id="leaveHours" type="number" step="0.5" class="form-control form-control-sm mb-1" placeholder="如：4" oninput="var t=this.value,p=document.getElementById('leaveDaysTip');if(t&&!isNaN(t)){var d=(t/8).toFixed(1);p.textContent=t+'小时（约'+d+'天）';p.style.display='block';}else{p.textContent='';p.style.display='none';}"><div class="small text-muted" id="leaveDaysTip" style="display:none"></div></div>
    <div class="d-flex gap-2"><button class="btn btn-primary btn-sm flex-fill" onclick="saveRecord()">保存</button><button class="btn btn-outline-danger btn-sm flex-fill" onclick="clearRecord()">清空</button></div>
</div></div></div></div>

<div class="modal fade" id="joinModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">加入企业</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input id="joinName" class="form-control form-control-sm mb-2" placeholder="企业名称"><input id="joinDate" type="date" class="form-control form-control-sm mb-2" value="<?=date('Y-m-d')?>"><button class="btn btn-sm btn-primary w-100" onclick="joinCompany()">确定加入</button></div>
</div></div></div></div>

<div class="modal fade" id="leaveModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">离职</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input id="leaveDate" type="date" class="form-control form-control-sm mb-2" value="<?=date('Y-m-d')?>"><button class="btn btn-sm btn-danger w-100" onclick="leaveCompany()">确认离职</button></div>
</div></div></div></div>

<div class="modal fade" id="companyListModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">企业历史</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><?php foreach($companies as $cp):$active=!$cp['left_date'];$days=$cp['left_date']?floor((strtotime($cp['left_date'])-strtotime($cp['join_date']))/86400):$cd;?>
<div class="border rounded p-2 mb-1"><?=htmlspecialchars($cp['company_name'])?> · <?=$cp['join_date']?>~<?=$cp['left_date']?:'至今'?> · <?=$days?>天 <span class="badge bg-<?=$active?'success':'secondary'?>"><?=$active?'在职':'已离职'?></span><?php if($active):?> <button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.6rem" onclick="openLeaveModal()">离职</button><?php endif;?></div><?php endforeach;?></div></div></div></div>

<script>
function openRecord(d,s,n){document.getElementById('recDate').value=d;document.getElementById('recStatus').value=s||'present';document.getElementById('recNote').value=n||'';document.getElementById('leaveHoursDiv').style.display=(s==='leave')?'block':'none';new bootstrap.Modal(document.getElementById('recordModal')).show();}
function saveRecord(){var f=new FormData();f.append('action','set_record');f.append('date',document.getElementById('recDate').value);f.append('status',document.getElementById('recStatus').value);f.append('note',document.getElementById('recNote').value);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok){if(document.getElementById('recStatus').value==='leave'){saveLeave();}else{location.reload();}}});}function saveLeave(){var f=new FormData();f.append('action','save_leave');f.append('date',document.getElementById('recDate').value);f.append('hours',document.getElementById('leaveHours').value);f.append('note',document.getElementById('recNote').value);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function clearRecord(){if(!confirm('清空该日出勤记录？'))return;var f=new FormData();f.append('action','clear_record');f.append('date',document.getElementById('recDate').value);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function clearMonth(){if(!confirm('确定清空本月全部出勤记录？'))return;var f=new FormData();f.append('action','clear_records');f.append('ym','<?=$ym?>');fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function openJoinModal(){new bootstrap.Modal(document.getElementById('joinModal')).show();}
function openLeaveModal(){new bootstrap.Modal(document.getElementById('leaveModal')).show();}
function openCompanyList(){new bootstrap.Modal(document.getElementById('companyListModal')).show();}
function joinCompany(){var n=document.getElementById('joinName').value,d=document.getElementById('joinDate').value;if(!n||!d){alert('请填写完整');return;}var f=new FormData();f.append('action','join_company');f.append('company_name',n);f.append('join_date',d);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function leaveCompany(){var d=document.getElementById('leaveDate').value;if(!d){alert('请选择离职日期');return;}var f=new FormData();f.append('action','leave_company');f.append('leave_date',d);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
</script>
