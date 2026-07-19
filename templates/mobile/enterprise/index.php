<style>
.ent-mobile { padding: 8px; padding-bottom: 70px; }
.ent-m-card { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; }
.ent-m-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 10px; }
.ent-m-stat { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 8px; padding: 8px 10px; text-align: center; }
.ent-m-stat .val { font-size: 1rem; font-weight: 700; }
.ent-m-stat .lbl { font-size: 0.7rem; color: var(--bs-secondary-color); }
.ent-m-tabs { display: flex; gap: 2px; margin-bottom: 8px; overflow-x: auto; padding-bottom: 4px; }
.ent-m-tab { flex-shrink: 0; padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; cursor: pointer; border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); white-space: nowrap; }
.ent-m-tab.active { background: var(--bs-primary); color: #fff; border-color: var(--bs-primary); }
.ent-m-content { display: none; }
.ent-m-content.active { display: block; }
.emp-m-card { border: 1px solid var(--bs-border-color); border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; font-size: 0.85rem; }
.emp-m-card .grade { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
.speed-row { display: flex; gap: 6px; align-items: center; margin-bottom: 8px; }
.speed-btn { flex: 1; padding: 6px; border-radius: 6px; border: 1px solid var(--bs-border-color); text-align: center; font-weight: 600; font-size: 0.8rem; cursor: pointer; }
.speed-btn.on { background: var(--bs-primary); color: #fff; border-color: var(--bs-primary); }
.btn-sm-m { padding: 5px 10px; font-size: 0.8rem; border-radius: 6px; }
.overlay-m { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: flex-end; }
.overlay-m .sheet { background: var(--bs-body-bg); width: 100%; max-height: 80vh; border-radius: 16px 16px 0 0; padding: 16px; overflow-y: auto; }
</style>

<div class="ent-mobile">

<!-- 标题 -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <strong style="font-size:1.1rem"><?= htmlspecialchars($company['name']) ?></strong>
        <span class="badge bg-primary ms-1">Lv.<?= $company['level'] ?></span>
    </div>
    <div class="d-flex gap-1">
        <?php if ($upgradeStatus['can_upgrade']): ?>
        <button class="btn btn-warning btn-sm-m" onclick="doUpgrade()">⬆升级</button>
        <?php endif; ?>
        <button class="btn btn-outline-info btn-sm-m" onclick="showUpgradeInfo()">📊</button>
        <a href="?route=enterprise-guide" class="btn btn-outline-info btn-sm-m">📖</a>
    </div>
</div>

<!-- 时间 + 倍速 -->
<div class="small text-muted mb-2">
    <?= $years ?>年<?= $months ?>月<?= $days ?>天 | 个人: <strong class="text-warning">¥<?= number_format($personalBalance) ?></strong>
</div>
<div class="speed-row">
    <div class="speed-btn <?= $company['speed']==1?'on':'' ?>" onclick="setSpeed(1)">1×</div>
    <div class="speed-btn <?= $company['speed']==2?'on':'' ?>" onclick="setSpeed(2)">2×</div>
    <div class="speed-btn <?= $company['speed']==5?'on':'' ?>" onclick="setSpeed(5)">5×</div>
    <button class="btn btn-outline-success btn-sm-m ms-auto" onclick="invest()">💵注资</button>
    <button class="btn btn-outline-info btn-sm-m" onclick="dividend()">💰分红</button>
</div>

<!-- 危机 -->
<?php foreach ($crisisList as $cr): ?>
<div class="alert alert-danger py-1 px-2 mb-2 small">
    ⚠️ <?= htmlspecialchars($cr['event_name']) ?>
    <?php if ($cr['event_name']==='停业整顿'): ?><button class="btn btn-warning btn-sm-m ms-1" onclick="recover()">恢复</button><?php endif; ?>
</div>
<?php endforeach; ?>

<!-- 统计 -->
<div class="ent-m-stats">
    <div class="ent-m-stat"><div class="val text-success">¥<?= number_format($company['balance']/10000,1) ?>万</div><div class="lbl">企业资金</div></div>
    <div class="ent-m-stat"><div class="val"><?= count($employees) ?>/<?= $company['emp_limit'] ?></div><div class="lbl">员工</div></div>
    <div class="ent-m-stat"><div class="val">¥<?= number_format($company['total_revenue']/10000,1) ?>万</div><div class="lbl">累计营收</div></div>
    <div class="ent-m-stat"><div class="val"><?= $company['total_orders_completed'] ?></div><div class="lbl">完成订单</div></div>
    <div class="ent-m-stat"><div class="val"><?= $company['labor_risk'] ?>/100</div><div class="lbl">劳动风险</div></div>
    <div class="ent-m-stat"><div class="val"><?= $company['total_rd_count'] ?></div><div class="lbl">研发成果</div></div>
</div>

<!-- 月度成本 -->
<div class="ent-m-card border-info" style="border-left:3px solid var(--bs-info)">
    <small class="text-info fw-bold">📊 月度运营成本</small>
    <div class="small">场地¥<?= number_format($decoLv*25000) ?> | 水电¥<?= number_format(max(5000,$empCount*300)) ?> | 网络¥<?= number_format($netLv*10000) ?> | 维护¥<?= number_format($equipLv*50000) ?> | 合计 <strong>¥<?= number_format($monthlyOps) ?>/月</strong></div>
</div>

<!-- 标签切换 -->
<div class="ent-m-tabs" id="mTabs">
    <div class="ent-m-tab active" onclick="mSwitch('staff')">👥 招聘</div>
    <div class="ent-m-tab" onclick="mSwitch('orders')">📋 订单</div>
    <div class="ent-m-tab" onclick="mSwitch('rd')">🔬 研发</div>
    <div class="ent-m-tab" onclick="mSwitch('asset')">🏗️ 资产</div>
    <div class="ent-m-tab" onclick="mSwitch('more')">···</div>
</div>

<!-- ====== 员工/招聘 ====== -->
<div id="m-staff" class="ent-m-content active">
    <div class="d-flex gap-2 mb-2">
        <button class="btn btn-outline-primary btn-sm-m flex-fill" onclick="recruit(false)">🔄 免费 5人</button>
        <button class="btn btn-outline-secondary btn-sm-m flex-fill" onclick="recruit(true)">💎 付费10人 ¥1万</button>
    </div>
    <div id="mCandList"></div>
    <?php foreach ($employees as $emp): ?>
    <div class="emp-m-card">
        <div class="d-flex justify-content-between">
            <strong><?= htmlspecialchars($emp['name']) ?></strong>
            <span class="grade bg-light border grade-<?= strtolower($emp['grade']) ?>"><?= $emp['grade'] ?></span>
        </div>
        <div class="small text-muted"><?= htmlspecialchars($emp['department']) ?> | ¥<?= number_format($emp['salary']) ?>/月 | ×<?= $emp['output_mult'] ?></div>
        <select class="form-select form-select-sm mt-1" onchange="setDept(<?= $emp['id'] ?>,this.value)">
            <?php foreach ($departments as $d): $ul=$deptUnlock[$d]??1; ?>
            <option <?= $emp['department']===$d?'selected':'' ?> <?= $company['level']<$ul?'disabled':'' ?>><?= $d ?><?= $company['level']<$ul?'🔒':'' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endforeach; ?>
</div>

<!-- ====== 订单 ====== -->
<div id="m-orders" class="ent-m-content">
    <?php foreach ($orderList as $o): ?>
    <div class="emp-m-card">
        <div class="d-flex justify-content-between">
            <strong><?= htmlspecialchars($o['product_name']) ?></strong>
            <span class="badge <?= $o['type']==='vip'?'bg-warning':($o['type']==='urgent'?'bg-danger':'bg-secondary') ?>"><?= $o['type']==='vip'?'大客户':($o['type']==='urgent'?'加急':'普通') ?></span>
        </div>
        <div class="small">客户: <?= htmlspecialchars($o['client_name']) ?> | 数量: <?= $o['quantity'] ?> | ¥<?= number_format($o['total_amount']) ?></div>
        <?php if ($o['status']==='in_progress'): ?>
        <div class="progress mt-1" style="height:4px"><div class="progress-bar" style="width:<?= $o['progress'] ?>%"></div></div>
        <?php elseif ($o['status']==='pending'): ?>
        <button class="btn btn-success btn-sm-m mt-1" onclick="takeOrder(<?= $o['id'] ?>)">接单</button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if (empty($orderList)): ?><div class="text-muted small text-center py-3">暂无待处理订单</div><?php endif; ?>

    <div class="small text-muted mt-2">🏭 生产线 (<?= count($prodLines) ?>条)</div>
    <?php foreach ($prodLines as $l): ?>
    <div class="emp-m-card d-flex justify-content-between align-items-center">
        <strong><?= htmlspecialchars($l['name']) ?></strong>
        <?php if ($l['status']==='busy'): ?><span class="badge bg-warning">生产中 <?= $l['progress'] ?>%</span>
        <?php elseif ($l['status']==='broken'): ?><span class="badge bg-danger">故障</span><button class="btn btn-warning btn-sm-m ms-1" onclick="repairLine(<?= $l['id'] ?>)">修 ¥10万</button>
        <?php else: ?><span class="badge bg-success">空闲</span><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <button class="btn btn-outline-primary btn-sm-m mt-1 w-100" onclick="buyProdLine()">➕ 买产线</button>
</div>

<!-- ====== 研发 ====== -->
<div id="m-rd" class="ent-m-content">
    <?php if ((int)$company['level']<2): ?>
    <div class="text-muted small p-2">企业2级解锁研发部</div>
    <?php else: ?>
    <div class="small text-muted mb-1">可选项目 (研发部:<?= $rdEmpCount ?>人)</div>
    <?php
    $poolSlice = array_slice($rdPool, 0, 6, true);
    foreach ($poolSlice as $pid=>$proj):
        $bp = max(1, round(100/max(1,$proj['days'])));
        $we = max(1, $bp+$rdEmpCount*3);
        $ed = ceil(100/$we);
    ?>
    <div class="emp-m-card" onclick="startRdPool(<?= $pid ?>,'<?= htmlspecialchars(addslashes($proj['name'])) ?>')">
        <div class="d-flex justify-content-between">
            <strong class="small"><?= htmlspecialchars($proj['name']) ?></strong>
            <span class="small">¥<?= number_format($proj['cost']/10000,1) ?>万</span>
        </div>
        <div class="small text-muted">约<?= $proj['days'] ?>天 品质<?= $proj['min_quality'] ?>-<?= $proj['max_quality'] ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php foreach ($rdList as $rd):
        $rdd = $rd['research_days']??30;
        $rdb = max(1,round(100/max(1,(int)$rdd)));
        $rde = max(1,$rdb+$rdEmpCount*3);
        $rem = $rd['status']==='researching'?ceil((100-$rd['progress'])/$rde):0;
    ?>
    <div class="emp-m-card">
        <div class="d-flex justify-content-between"><strong class="small"><?= htmlspecialchars($rd['name']) ?></strong><span class="badge <?= $rd['status']==='completed'?'bg-success':'bg-info' ?> small"><?= $rd['status']==='completed'?'完成':'研发中' ?></span></div>
        <?php if ($rd['status']==='researching'): ?><div class="small">剩余约<?= $rem ?>天</div><div class="progress mt-1" style="height:4px"><div class="progress-bar" style="width:<?= $rd['progress'] ?>%"></div></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- ====== 资产 ====== -->
<div id="m-asset" class="ent-m-content">
    <?php
    $basePrices = ['装修'=>500000,'网络'=>100000,'桌椅'=>200000,'设备'=>800000];
    $rates = ['装修'=>0.3,'网络'=>0.2,'桌椅'=>0.25,'设备'=>0.4];
    $names = ['装修'=>'办公装修','网络'=>'网络设施','桌椅'=>'办公桌椅','设备'=>'生产设备'];
    $am = []; foreach ($assetList as $a) $am[$a['category']]=$a;
    foreach (['装修','网络','桌椅','设备'] as $cat):
        $a = $am[$cat]??null; $lv = $a?(int)$a['level']:0;
        $nc = $lv>0?round($basePrices[$cat]*pow(1+$rates[$cat],$lv)):$basePrices[$cat];
        $cb = $lv<10 && (float)$company['balance']>=$nc;
    ?>
    <div class="emp-m-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong class="small"><?= $names[$cat] ?></strong>
                <div class="small text-muted">Lv.<?= $lv ?>/10 <?= $lv<10 ? "→ Lv.".($lv+1)." ¥".number_format($nc) : '' ?></div>
            </div>
            <button class="btn btn-outline-success btn-sm-m" onclick="buyAsset('<?= $cat ?>')" <?= $lv>=10?'disabled':'' ?>><?= $lv>=10?'满级':'升级' ?></button>
        </div>
    </div>
    <?php endforeach; ?>
    <a href="?route=enterprise-mall" class="btn btn-outline-primary btn-sm-m w-100 mt-1">🏗️ 商城</a>
</div>

<!-- ====== 更多 ====== -->
<div id="m-more" class="ent-m-content">
    <div class="emp-m-card" onclick="showUpgradeInfo()" style="cursor:pointer">
        <strong>📊 升级条件</strong> — 查看 Lv.<?= $company['level'] ?>→<?= $upgradeStatus['next_lv']??'-' ?> <?= $upgradeStatus['next_level']??'满级' ?>
        <?php if ($upgradeStatus['can_upgrade']): ?><span class="badge bg-warning ms-1">可升级</span><?php endif; ?>
    </div>
    <div class="emp-m-card" onclick="saveWelfare()" style="cursor:pointer">
        <strong>🎁 福利配置</strong>
        <div class="small text-muted">社保<?= $company['social_insurance']?'✅':'❌' ?> 公积金<?= $company['housing_fund']?'✅':'❌' ?> 食堂<?= $company['canteen']?'✅':'❌' ?> 宿舍<?= $company['dormitory']?'✅':'❌' ?></div>
    </div>
    <div class="emp-m-card" onclick="applyLoan('低息长期')" style="cursor:pointer">
        <strong>💳 贷款方案</strong>
        <div class="small text-muted">低息200万 | 高息500万 | 抵押1000万</div>
    </div>
    <div class="emp-m-card">
        <strong>🏪 店铺</strong>
        <?php foreach ($storeList as $s): ?>
        <div class="small"><?= $s['type']==='online'?'🌐':'🏪' ?> <?= htmlspecialchars($s['name']) ?> 日收¥<?= number_format($s['daily_revenue']) ?></div>
        <?php endforeach; ?>
        <div class="mt-1 d-flex gap-1">
            <button class="btn btn-outline-success btn-sm-m flex-fill" onclick="openStore('online')">🌐 线上 ¥10万</button>
            <button class="btn btn-outline-secondary btn-sm-m flex-fill" onclick="openStore('offline')">🏪 线下 ¥30万</button>
        </div>
    </div>
    <a href="?route=enterprise-products" class="btn btn-outline-primary btn-sm-m w-100">📦 产品管理</a>
    <a href="?route=enterprise-rd" class="btn btn-outline-primary btn-sm-m w-100 mt-1">🔬 研发中心</a>
</div>

<!-- 财务日志 -->
<div class="ent-m-card mt-2">
    <strong class="small">📒 最近财务</strong>
    <?php foreach (array_slice($finList,0,10) as $f): ?>
    <div class="d-flex justify-content-between small py-1 border-bottom">
        <span><?= htmlspecialchars($f['category']) ?></span>
        <span class="<?= $f['type']==='income'?'text-success':'text-danger' ?>"><?= $f['type']==='income'?'+':'-' ?>¥<?= number_format($f['amount']) ?></span>
    </div>
    <?php endforeach; ?>
</div>

</div>

<!-- 升级条件弹窗 -->
<div class="overlay-m" id="upModal">
<div class="sheet">
    <div class="d-flex justify-content-between mb-2">
        <strong>📊 升级条件 Lv.<?= $company['level'] ?>→<?= $upgradeStatus['next_lv']??'-' ?></strong>
        <button class="btn-close" onclick="document.getElementById('upModal').style.display='none'"></button>
    </div>
    <?php if ($upgradeStatus['reached']): ?>
    <div class="alert alert-success">已满级</div>
    <?php else: ?>
    <?php foreach ($upgradeStatus['conditions'] as $c): ?>
    <div class="d-flex justify-content-between p-2 rounded mb-1 small <?= $c['met']?'bg-success bg-opacity-10':'bg-danger bg-opacity-10' ?>">
        <span><?= $c['label'] ?></span>
        <span><?= $c['met']?'✅':'❌ 当前:'.$c['current'] ?></span>
    </div>
    <?php endforeach; ?>
    <?php if ($upgradeStatus['can_upgrade']): ?>
    <button class="btn btn-warning w-100 mt-2" onclick="doUpgrade()">⬆ 立即升级</button>
    <?php endif; ?>
    <?php endif; ?>
</div>
</div>

<script>
let mCandData = [];
function mSwitch(name) {
    document.querySelectorAll('.ent-m-content').forEach(e=>e.classList.remove('active'));
    document.querySelectorAll('.ent-m-tab').forEach(e=>e.classList.remove('active'));
    document.getElementById('m-'+name).classList.add('active');
    event.target.classList.add('active');
}
function setSpeed(s) { api('setSpeed',{speed:s}); }
function invest() {
    let a = prompt('注资金额（从个人股票账户转入企业）：');
    if (!a||parseFloat(a)<=0) return;
    fetch('?route=enterprise-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=invest&amount='+parseFloat(a)}).then(r=>r.json()).then(d=>{if(d.ok){alert(d.message);location.reload();}else alert(d.error);});
}
function dividend() {
    let a = prompt('分红金额（扣20%税转入股票账户）：');
    if (!a) return;
    api('dividend',{amount:parseFloat(a)});
}
function showUpgradeInfo() { document.getElementById('upModal').style.display='flex'; }
function doUpgrade() {
    if (!confirm('确定升级？')) return;
    fetch('?route=enterprise-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=doUpgrade'}).then(r=>r.json()).then(d=>{if(d.ok){alert(d.message);location.reload();}else alert(d.error);});
}
function recruit(paid) {
    fetch('?route=enterprise-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=recruit&paid='+(paid?1:0)})
    .then(r=>r.json()).then(d=>{
        if(!d.ok){alert(d.error);return;}
        mCandData = d.candidates;
        let h = '<div class="small fw-bold mb-1">候选人 (点击入职)</div>';
        mCandData.forEach((c,i)=>{if(!c._hired)h+='<div class="emp-m-card" onclick="mHire('+i+')"><strong>'+c.name+'</strong> <span class="grade grade-'+c.grade.toLowerCase()+'">'+c.grade+'</span> <small>¥'+c.salary.toLocaleString()+'/月 ×'+c.output_mult+'</small></div>';});
        document.getElementById('mCandList').innerHTML = h;
    });
}
function mHire(i) {
    let c = mCandData[i]; if(!c||c._hired) return;
    fetch('?route=enterprise-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=hire&name='+encodeURIComponent(c.name)+'&grade='+c.grade+'&salary='+c.salary+'&output_mult='+c.output_mult+'&department=订单部'})
    .then(r=>r.json()).then(d=>{if(d.ok){c._hired=true;mCandData.forEach((x,j)=>{if(x._hired){let el=document.querySelector('#mCandList .emp-m-card:nth-child('+(j+2)+')');if(el)el.innerHTML='<span class="text-success small">✅ '+x.name+' 已入职</span>';}});}else alert(d.error);});
}
function api(action,data,cb) {
    let b='action='+action;
    for(let k in data) b+='&'+k+'='+encodeURIComponent(data[k]);
    fetch('?route=enterprise-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:b})
    .then(r=>r.json()).then(d=>{if(d.ok){if(cb)cb(d);location.reload();}else alert(d.error);});
}
function fireEmployee(id,name){if(confirm('解雇'+name+'?'))api('fire',{emp_id:id});}
function setDept(id,dept){api('setDept',{emp_id:id,department:dept});}
function buyAsset(cat){if(confirm('升级'+cat+'?'))api('buyAsset',{category:cat});}
function buyProdLine(){if(confirm('购买产线？'))api('buyProdLine',{});}
function takeOrder(id){api('takeOrder',{order_id:id});}
function startRdPool(pid,name){if(confirm('启动研发「'+name+'」？'))api('startRd',{pool_id:pid});}
function applyLoan(type){if(confirm('申请'+type+'贷款？'))api('loan',{plan_type:type});}
function openStore(type){let n=prompt('店铺名称：');if(!n)return;api('openStore',{type:type,name:n});}
function recover(){api('recover',{});}
function repairLine(id){api('repairLine',{line_id:id});}
function saveWelfare(){api('welfare',{social_insurance:1,housing_fund:1});}
</script>
