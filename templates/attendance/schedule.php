<?php $sc=$schedule??[];$sh=$shifts??[];$ym=$ym??date('Y-m');$y=(int)substr($ym,0,4);$m=(int)substr($ym,5,2);$daysInMonth=cal_days_in_month(CAL_GREGORIAN,$m,$y);$firstDay=(int)date('w',strtotime("$y-$m-01"));$wdNames=['日','一','二','三','四','五','六'];?>
<style>.att-bg{background:rgba(255,255,255,0.88);border-radius:12px;padding:16px}body.theme-dark .att-bg{background:rgba(15,23,42,0.88)}.sch-cell{min-height:82px;border:1px solid rgba(0,0,0,0.08);border-radius:6px;padding:5px;cursor:pointer;font-size:0.85rem;background:rgba(255,255,255,0.75);transition:background 0.15s}body.theme-dark .sch-cell{background:rgba(30,41,59,0.55)}.sch-cell:hover{background:rgba(102,126,234,0.1)}.sch-cell .dnum{font-weight:700;font-size:1rem;margin-bottom:2px}.sch-cell .shift{font-size:0.72rem;padding:2px 5px;border-radius:3px;display:inline-block;margin-top:3px;font-weight:600}</style>
<div class="att-bg">
<h5>📅 排班管理</h5>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div><a href="?route=attendance-schedule&ym=<?=date('Y-m',strtotime($ym.' -1 month'))?>" class="btn btn-sm btn-outline-secondary">◀</a> <span class="fw-bold"><?=$y?>年<?=$m?>月</span> <a href="?route=attendance-schedule&ym=<?=date('Y-m',strtotime($ym.' +1 month'))?>" class="btn btn-sm btn-outline-secondary">▶</a></div>
    <a href="?route=attendance-shift" class="btn btn-sm btn-outline-primary">出勤记录 →</a>
</div>
<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px">
    <?php foreach($wdNames as $wd)echo"<div class='text-center text-muted small fw-bold py-1'>$wd</div>";
    for($i=0;$i<$firstDay;$i++)echo'<div></div>';
    for($d=1;$d<=$daysInMonth;$d++):
        $date=sprintf('%04d-%02d-%02d',$y,$m,$d);
        $sd=$sc[$date]??null;$color=$sd?($sd['name']=='休息'?'#9ca3af':'#22c55e'):'#999';?>
    <div class="sch-cell text-center" onclick="setSchedPrompt('<?=$date?>','<?=htmlspecialchars($sd?$sd['name'].' '.$sd['start_time'].'-'.$sd['end_time']:'未排班')?>')">
        <div class="dnum"><?=$d?></div>
        <div class="shift" style="background:<?=$color?>20;color:<?=$color?>"><?=$sd?$sd['name']:'未排'?></div>
    </div>
    <?php endfor;?>
</div>
<div class="mt-3 small text-muted">💡 点击单日修改该天的排班，不同日独立设置</div>
<?php $sCounts=['早班'=>0,'晚班'=>0,'休息'=>0];foreach($sc as $sdd){$n=$sdd['name'];if(isset($sCounts[$n]))$sCounts[$n]++;else $sCounts[$n]=1;}?>
<div class="row g-2 mt-3">
    <?php foreach($sCounts as $k=>$v):$cl=($k=='休息'?'bg-secondary':($k=='早班'?'bg-success':'bg-primary'));?><div class="col"><div class="text-center p-2 rounded <?=$cl?> bg-opacity-10"><div class="fw-bold fs-5"><?=$v?></div><div class="small text-muted"><?=$k?></div></div></div><?php endforeach;?>
    <div class="col"><div class="text-center p-2 rounded bg-warning bg-opacity-10"><div class="fw-bold fs-5"><?=$daysInMonth-array_sum($sCounts)?></div><div class="small text-muted">未排班</div></div></div>
</div>

<script>
var curDate='';
function setSchedPrompt(date,name){
    curDate=date;
    var html='<div class="card border-0 shadow-lg"><div class="card-header d-flex justify-content-between py-1 px-2"><strong>'+date+' 排班</strong><button class="btn-close" onclick="closeSched()"></button></div><div class="card-body py-2">';
    <?php foreach($sh as $s):if($s['is_rest'])continue;?>
    html+='<div class="border rounded p-2 mb-1" style="cursor:pointer" onclick="setSched(<?=$s['id']?>)"><strong><?=$s['name']?></strong> <span class="text-muted small"><?=$s['start_time']?>-<?=$s['end_time']?></span></div>';
    <?php endforeach;?>
    html+='<div class="border rounded p-2 text-center text-muted" style="cursor:pointer" onclick="setSched(0)">标记为休息</div></div></div>';
    document.getElementById('schedPopup').innerHTML=html;document.getElementById('schedPopup').style.display='block';
}
function closeSched(){document.getElementById('schedPopup').style.display='none';}
function setSched(sid){var f=new FormData();f.append('action','set_schedule');f.append('date',curDate);f.append('shift_id',sid);fetch('/public/index.php?route=attendance-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok)location.reload();else alert('失败');});}
</script>
<div id="schedPopup" style="display:none;position:fixed;top:30%;left:50%;transform:translate(-50%,-30%);z-index:9999;min-width:260px" onclick="event.stopPropagation()"></div></div>
