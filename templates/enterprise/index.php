<div class="container-fluid py-3">
<style>
.ent-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px; }
.ent-stat-card { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 10px; padding: 14px 16px; }
.ent-stat-card .stat-val { font-size: 1.4rem; font-weight: 700; }
.ent-stat-card .stat-lbl { font-size: 0.8rem; color: var(--bs-secondary-color); }
.level-badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
.emp-card { border: 1px solid var(--bs-border-color); border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; }
.grade-c { color: #6c757d; } .grade-b { color: #0d6efd; } .grade-a { color: #6f42c1; } .grade-s { color: #fd7e14; }
.speed-btn { padding: 4px 16px; border-radius: 6px; border: 1px solid var(--bs-border-color); cursor: pointer; font-weight: 600; margin: 0 2px; }
.speed-btn.active { background: var(--bs-primary); color: #fff; border-color: var(--bs-primary); }
.crisis-alert { animation: pulse 2s infinite; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.6; } }
.section-tabs { display: flex; gap: 4px; margin-bottom: 16px; flex-wrap: wrap; }
.section-tab { padding: 6px 16px; border-radius: 6px; cursor: pointer; border: 1px solid var(--bs-border-color); font-size: 0.9rem; background: var(--bs-body-bg); }
.section-tab.active { background: var(--bs-primary); color: #fff; border-color: var(--bs-primary); }
</style>

<!-- 顶部标题栏 -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-0"><?= htmlspecialchars($company['name']) ?>
            <span class="level-badge bg-primary text-white ms-2">Lv.<?= $company['level'] ?> <?= $company['level_name'] ?></span>
            <?php if ($company['is_listed']): ?>
                <span class="level-badge bg-warning text-dark ms-1">📈 已上市</span>
            <?php endif; ?>
            <?php if ($upgradeStatus['can_upgrade']): ?>
                <button class="btn btn-warning btn-sm ms-2" onclick="doUpgrade()">⬆ 升级至 <?= $upgradeStatus['next_level'] ?></button>
            <?php endif; ?>
        </h3>
        <small class="text-muted">
            运营时间：<?= $years ?>年 <?= $months ?>月 <?= $days ?>天 &nbsp;|&nbsp;
            倍速：
            <button class="speed-btn <?= $company['speed']==1?'active':'' ?>" onclick="setSpeed(1)">1×</button>
            <button class="speed-btn <?= $company['speed']==2?'active':'' ?>" onclick="setSpeed(2)">2×</button>
            <button class="speed-btn <?= $company['speed']==5?'active':'' ?>" onclick="setSpeed(5)">5×</button>
            <span class="ms-2 text-muted">(1分钟=1天)</span>
            &nbsp;|&nbsp;
            👤 个人资产：<strong class="text-warning">¥<?= number_format($personalBalance) ?></strong>
        </small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <!-- 升级条件 -->
        <button class="btn btn-outline-warning btn-sm" onclick="showUpgradeInfo()" title="查看升级条件">📊 升级条件</button>
        <!-- 注资 -->
        <button class="btn btn-outline-success btn-sm" onclick="invest()">💵 注资</button>
        <!-- 分红 -->
        <button class="btn btn-outline-info btn-sm" onclick="dividend()">💰 分红</button>
        <a href="?route=enterprise-guide" class="btn btn-outline-info btn-sm">📖 游戏说明</a>
    </div>
</div>

<!-- 升级条件弹窗 -->
<div id="upgradeModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;justify-content:center;align-items:center;">
<div class="card shadow" style="max-width:500px;width:90%">
<div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
    <strong>📊 升级条件 — Lv.<?= $company['level'] ?> → <?= $upgradeStatus['next_lv'] ?? '-' ?> <?= $upgradeStatus['next_level'] ?? '已满级' ?></strong>
    <button class="btn-close btn-close-white" onclick="document.getElementById('upgradeModal').style.display='none'"></button>
</div>
<div class="card-body">
    <?php if ($upgradeStatus['reached']): ?>
        <div class="alert alert-success">🎉 已达最高等级！</div>
    <?php else: ?>
        <?php foreach ($upgradeStatus['conditions'] as $cond): ?>
        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded <?= $cond['met'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' ?>">
            <span><?= $cond['label'] ?></span>
            <span>
                <?php if ($cond['met']): ?>
                    <span class="text-success">✅ 已达标 (<?= $cond['current'] ?>)</span>
                <?php else: ?>
                    <span class="text-danger">❌ 未达标 (当前: <?= $cond['current'] ?>)</span>
                <?php endif; ?>
            </span>
        </div>
        <?php endforeach; ?>
        <?php if ($upgradeStatus['can_upgrade']): ?>
            <button class="btn btn-warning w-100 mt-2" onclick="doUpgrade()">⬆ 立即升级</button>
        <?php else: ?>
            <div class="text-muted text-center mt-2">满足所有条件后可升级</div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>
</div>

<!-- 危机提示 -->
<?php foreach ($crisisList as $cr): ?>
<div class="alert alert-danger crisis-alert mb-3 py-2">
    ⚠️ <strong><?= htmlspecialchars($cr['event_name']) ?></strong>：<?= htmlspecialchars($cr['effect_desc']) ?>
    <?php if ($cr['event_name'] === '停业整顿'): ?>
        <button class="btn btn-warning btn-sm ms-3" onclick="recover()">恢复运营</button>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- 统计卡片 -->
<div class="ent-stats">
    <div class="ent-stat-card">
        <div class="stat-lbl">💰 企业资金</div>
        <div class="stat-val text-success">¥<?= number_format($company['balance']) ?></div>
    </div>
    <div class="ent-stat-card">
        <div class="stat-lbl">🏦 注册资本</div>
        <div class="stat-val">¥<?= number_format($company['capital']) ?></div>
    </div>
    <div class="ent-stat-card">
        <div class="stat-lbl">📈 累计营收</div>
        <div class="stat-val text-primary">¥<?= number_format($company['total_revenue']) ?></div>
    </div>
    <div class="ent-stat-card">
        <div class="stat-lbl">👥 员工数</div>
        <div class="stat-val"><?= count($employees) ?> / <?= $company['emp_limit'] ?></div>
    </div>
    <div class="ent-stat-card">
        <div class="stat-lbl">😊 满意度</div>
        <div class="stat-val"><?= number_format($company['happiness'], 1) ?>%</div>
    </div>
    <div class="ent-stat-card">
        <div class="stat-lbl">⚖️ 劳动风险</div>
        <div class="stat-val <?= $company['labor_risk'] >= 40 ? 'text-danger' : '' ?>"><?= $company['labor_risk'] ?> / 100</div>
    </div>
    <div class="ent-stat-card">
        <div class="stat-lbl">📦 完成订单</div>
        <div class="stat-val"><?= $company['total_orders_completed'] ?></div>
    </div>
    <div class="ent-stat-card">
        <div class="stat-lbl">🔬 研发成果</div>
        <div class="stat-val"><?= $company['total_rd_count'] ?></div>
    </div>
</div>

<!-- 月度运营成本概览 -->
<?php
$decoLv = 0; $netLv = 0; $deskLv = 0; $equipLv = 0;
foreach ($assetList as $a) {
    if ($a['category'] === '装修') $decoLv = (int)$a['level'];
    if ($a['category'] === '网络') $netLv = (int)$a['level'];
    if ($a['category'] === '桌椅') $deskLv = (int)$a['level'];
    if ($a['category'] === '设备') $equipLv = (int)$a['level'];
}
$empCount = count($employees);
$rentCost = $decoLv * 25000;
$utilityCost = max(5000, $empCount * 300);
$netCost = $netLv * 10000;
$maintainCost = $equipLv * 50000;
$depreCost = $deskLv * 10000;
$monthlyOps = $rentCost + $utilityCost + $netCost + $maintainCost + $depreCost;
?>
<div class="card border-info mb-3" style="background: var(--bs-info-bg-subtle, rgba(13,202,240,0.05))">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <strong class="text-info">📊 月度运营成本</strong>
            <span><span class="text-muted">场地租金</span> <strong>¥<?= number_format($rentCost) ?></strong></span>
            <span><span class="text-muted">水电杂费</span> <strong>¥<?= number_format($utilityCost) ?></strong></span>
            <span><span class="text-muted">网络费</span> <strong>¥<?= number_format($netCost) ?></strong></span>
            <span><span class="text-muted">设备维护</span> <strong>¥<?= number_format($maintainCost) ?></strong></span>
            <span><span class="text-muted">折旧</span> <strong>¥<?= number_format($depreCost) ?></strong></span>
            <span class="ms-auto fw-bold text-info">合计 ¥<?= number_format($monthlyOps) ?>/月</span>
        </div>
        <small class="text-muted">场地租金随装修等级递增(¥25,000/级)，水电按员工数(¥300/人)，其他按对应资产等级计算。每30天自动扣除。</small>
    </div>
</div>

<!-- 功能标签 -->
<div class="section-tabs">
    <div class="section-tab active" onclick="switchTab('staff')">👥 人力资源</div>
    <div class="section-tab" onclick="switchTab('orders')">📋 订单生产</div>
    <div class="section-tab" onclick="switchTab('rd')">🔬 研发</div>
    <div class="section-tab" onclick="switchTab('assets')">🏗️ 资产</div>
    <div class="section-tab" onclick="switchTab('welfare')">🎁 福利</div>
    <div class="section-tab" onclick="switchTab('finance')">💳 贷款</div>
    <div class="section-tab" onclick="switchTab('stores')">🏪 销售</div>
</div>

<!-- ========= 人力资源 ========= -->
<div id="tab-staff" class="tab-content">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>👥 员工列表</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" onclick="recruit(false)">🔄 免费刷新(5人)</button>
            <button class="btn btn-outline-secondary btn-sm" onclick="recruit(true)">💎 付费刷新(10人 ¥10,000)</button>
        </div>
    </div>

    <div id="candidateList" class="mb-3"></div>

    <div class="row">
        <?php foreach ($employees as $emp): ?>
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="emp-card">
                <div class="d-flex justify-content-between align-items-center">
                    <strong><?= htmlspecialchars($emp['name']) ?></strong>
                    <span class="badge grade-<?= strtolower($emp['grade']) ?> bg-light border"><?= $emp['grade'] ?>级</span>
                </div>
                <small class="text-muted"><?= htmlspecialchars($emp['department']) ?> | ￥<?= number_format($emp['salary']) ?>/月 | ×<?= $emp['output_mult'] ?></small>
                <div class="mt-1 d-flex gap-1">
                    <select class="form-select form-select-sm" style="width:auto" onchange="setDept(<?= $emp['id'] ?>, this.value)">
                        <?php foreach ($departments as $dept): ?>
                            <?php $unlockLv = $deptUnlock[$dept] ?? 1; ?>
                            <option value="<?= $dept ?>" <?= $emp['department'] === $dept ? 'selected' : '' ?>
                                <?= $company['level'] < $unlockLv ? 'disabled' : '' ?>>
                                <?= $dept ?><?= $company['level'] < $unlockLv ? ' 🔒' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-danger btn-sm" onclick="fireEmployee(<?= $emp['id'] ?>, '<?= htmlspecialchars($emp['name']) ?>')">解雇</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($employees)): ?>
        <div class="col-12 text-muted text-center py-4">暂无员工，请点击上方按钮招聘</div>
        <?php endif; ?>
    </div>
</div>

<!-- ========= 订单生产 ========= -->
<div id="tab-orders" class="tab-content" style="display:none">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>📋 待处理订单</h5>
        <a href="?route=enterprise-products" class="btn btn-outline-primary btn-sm">管理产品 →</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead><tr><th>客户</th><th>产品</th><th>数量</th><th>金额</th><th class="text-center">类型</th><th class="text-center">时限</th><th class="text-center">进度</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($orderList as $ord): ?>
            <tr>
                <td><?= htmlspecialchars($ord['client_name']) ?></td>
                <td><?= htmlspecialchars($ord['product_name']) ?></td>
                <td><?= $ord['quantity'] ?></td>
                <td>¥<?= number_format($ord['total_amount']) ?></td>
                <td class="text-center">
                    <?php if ($ord['type'] === 'vip'): ?><span class="badge bg-warning">大客户</span>
                    <?php elseif ($ord['type'] === 'urgent'): ?><span class="badge bg-danger">加急</span>
                    <?php else: ?><span class="badge bg-secondary">普通</span><?php endif; ?>
                </td>
                <td class="text-center"><?= $ord['deadline'] ?>天</td>
                <td class="text-center">
                    <?php if ($ord['status'] === 'in_progress'): ?>
                    <div class="progress" style="height:6px"><div class="progress-bar" style="width:<?= $ord['progress'] ?>%"></div></div>
                    <small><?= $ord['progress'] ?>%</small>
                    <?php else: ?>
                    <span class="text-muted">待接单</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($ord['status'] === 'pending'): ?>
                    <button class="btn btn-success btn-sm" onclick="takeOrder(<?= $ord['id'] ?>)">接单</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orderList)): ?>
            <tr><td colspan="8" class="text-center text-muted py-3">暂无待处理订单，系统每天自动刷新</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h5 class="mt-3">🏭 生产线</h5>
    <div class="row">
        <?php foreach ($prodLines as $line): ?>
        <div class="col-md-4 mb-2">
            <div class="emp-card">
                <strong><?= htmlspecialchars($line['name']) ?></strong>
                <?php if ($line['status'] === 'busy'): ?>
                    <span class="badge bg-warning">生产中 <?= $line['progress'] ?>%</span>
                <?php elseif ($line['status'] === 'broken'): ?>
                    <span class="badge bg-danger">故障</span>
                    <button class="btn btn-warning btn-sm mt-1" onclick="repairLine(<?= $line['id'] ?>)">维修 ¥100,000</button>
                <?php else: ?>
                    <span class="badge bg-success">空闲</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-outline-primary btn-sm mt-2" onclick="buyProdLine()">➕ 购买新产线</button>
</div>

<!-- ========= 研发 ========= -->
<div id="tab-rd" class="tab-content" style="display:none">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>🔬 研发项目</h5>
        <span class="text-muted small">研发部: <?= $rdEmpCount ?>人 | </span>
        <a href="?route=enterprise-rd" class="btn btn-outline-primary btn-sm">研发中心 →</a>
    </div>

    <?php if ((int)$company['level'] < 2): ?>
    <p class="text-muted">企业需达到2级解锁研发部。</p>
    <?php else: ?>
    <div class="row g-2 mb-3">
        <?php
        $poolSlice = array_slice($rdPool, 0, 8, true);
        foreach ($poolSlice as $pid => $proj):
            $canAfford = (float)$company['balance'] >= $proj['cost'];
            $basePerDay = max(1, round(100 / max(1, $proj['days'])));
            $withEmp = max(1, $basePerDay + $rdEmpCount * 3);
            $estDays = ceil(100 / $withEmp);
        ?>
        <div class="col-md-3 col-6">
            <div class="emp-card" style="cursor:pointer" onclick="startRdPool(<?= $pid ?>, '<?= htmlspecialchars(addslashes($proj['name'])) ?>')">
                <strong class="small"><?= htmlspecialchars($proj['name']) ?></strong>
                <div class="small text-muted">¥<?= number_format($proj['cost']) ?> | 约<?= $proj['days'] ?>天
                    <?php if ($rdEmpCount > 0): ?><br><span class="text-success">加速后约<?= $estDays ?>天</span><?php endif; ?>
                </div>
                <span class="badge bg-light border small">品质<?= $proj['min_quality'] ?>-<?= $proj['max_quality'] ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($rdList)): ?>
    <p class="text-muted">暂无进行中的研发。</p>
    <?php endif; ?>
    <?php foreach ($rdList as $rd):
        $rdDays = $rd['research_days'] ?? 30;
        $rdBase = max(1, round(100 / max(1, (int)$rdDays)));
        $rdEmp = max(1, $rdBase + $rdEmpCount * 3);
        $remain = $rd['status'] === 'researching' ? ceil((100 - $rd['progress']) / $rdEmp) : 0;
    ?>
    <div class="emp-card">
        <div class="d-flex justify-content-between">
            <strong><?= htmlspecialchars($rd['name']) ?></strong>
            <span class="badge <?= $rd['status']==='completed'?'bg-success':'bg-info' ?>"><?= $rd['status']==='completed'?'已完成':'研发中' ?></span>
        </div>
        <small>品质: <?= $rd['quality'] ?> | 投入: ¥<?= number_format($rd['cost']) ?> | <?= $rd['status']==='researching' ? "剩余约{$remain}天" : '' ?></small>
        <?php if ($rd['status'] === 'researching'): ?>
        <div class="progress mt-1" style="height:5px"><div class="progress-bar" style="width:<?= $rd['progress'] ?>%"></div></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- ========= 资产 ========= -->
<div id="tab-assets" class="tab-content" style="display:none">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>🏗️ 固定资产</h5>
        <a href="?route=enterprise-mall" class="btn btn-outline-primary btn-sm">商城 →</a>
    </div>
    <div class="row">
    <?php
    $assetMap = [];
    foreach ($assetList as $a) $assetMap[$a['category']] = $a;
    $allCats = ['装修', '网络', '桌椅', '设备'];
    $assetNames = ['装修' => '办公装修', '网络' => '网络设施', '桌椅' => '办公桌椅', '设备' => '生产设备'];
    $assetDescs = ['装修' => '场地面积 +50㎡/级', '网络' => '订单速度 -5%/级', '桌椅' => '满意度 +5%/级', '设备' => '产能 +20%/级'];
    $basePrices = ['装修' => 500000, '网络' => 100000, '桌椅' => 200000, '设备' => 800000];
    $upgradeRates = ['装修' => 0.3, '网络' => 0.2, '桌椅' => 0.25, '设备' => 0.4];
    foreach ($allCats as $cat):
        $a = $assetMap[$cat] ?? null; $lv = $a ? $a['level'] : 0;
        $base = $basePrices[$cat];
        $rate = $upgradeRates[$cat];
        $nextCost = $lv > 0 ? round($base * pow(1 + $rate, $lv)) : $base;
        $canBuy = $lv < 10 && (float)$company['balance'] >= $nextCost;
    ?>
    <div class="col-md-3 mb-2">
        <div class="emp-card text-center">
            <strong><?= $assetNames[$cat] ?></strong>
            <div class="text-muted small"><?= $assetDescs[$cat] ?></div>
            <div>等级: <strong><?= $lv ?></strong> / 10</div>
            <?php if ($lv >= 10): ?>
                <small class="text-success">已满级</small>
            <?php else: ?>
                <div class="small mt-1">升级至 <?= $lv + 1 ?> 级: <strong class="<?= $canBuy ? 'text-success' : 'text-danger' ?>">¥<?= number_format($nextCost) ?></strong></div>
            <?php endif; ?>
            <button class="btn btn-outline-success btn-sm mt-1" onclick="buyAsset('<?= $cat ?>')" <?= $lv >= 10 ? 'disabled' : '' ?>>
                <?= $lv >= 10 ? '已满级' : '升级' ?>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- ========= 福利 ========= -->
<div id="tab-welfare" class="tab-content" style="display:none">
    <h5>🎁 员工福利配置（月成本）</h5>
    <form id="welfareForm" class="row g-2 mb-3">
        <?php
        $welfares = [
            'social_insurance' => ['🧾 社保', '¥2,000/人（强制）'],
            'housing_fund' => ['🏠 公积金', '¥300/人（强制）'],
            'canteen' => ['🍽️ 员工食堂', '¥500/人 满意+5%'],
            'dormitory' => ['🏢 员工宿舍', '¥800/人 满意+8%'],
            'transport' => ['🚌 交通补贴', '¥200/人 满意+3%'],
            'holiday_bonus' => ['🎉 节日福利', '¥300/人/季 满意+10%'],
        ];
        foreach ($welfares as $key => $info):
        ?>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="<?= $key ?>" id="wf_<?= $key ?>" <?= $company[$key] ? 'checked' : '' ?>>
                <label class="form-check-label" for="wf_<?= $key ?>"><?= $info[0] ?> <small class="text-muted"><?= $info[1] ?></small></label>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="col-12 mt-2">
            <button type="button" class="btn btn-primary btn-sm" onclick="saveWelfare()">保存配置</button>
        </div>
    </form>
</div>

<!-- ========= 贷款 ========= -->
<div id="tab-finance" class="tab-content" style="display:none">
    <h5>💳 银行贷款</h5>
    <div class="row">
        <?php
        $plans = [
            ['低息长期', '¥2,000,000', '1%/月', '12个月', '适合稳定发展'],
            ['高息短期', '¥5,000,000', '3%/月', '3个月', '适合急需周转'],
            ['抵押贷款', '¥10,000,000', '1.5%/月', '6个月', '需抵押固定资产'],
        ];
        foreach ($plans as $p):
        ?>
        <div class="col-md-4 mb-3">
            <div class="emp-card text-center">
                <strong><?= $p[0] ?></strong>
                <div>额度: <?= $p[1] ?></div>
                <div class="text-muted">利率: <?= $p[2] ?> | 期限: <?= $p[3] ?></div>
                <small><?= $p[4] ?></small>
                <button class="btn btn-outline-warning btn-sm d-block w-100 mt-2" onclick="applyLoan('<?= $p[0] ?>')">申请贷款</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($loanList)): ?>
    <h6 class="mt-3">当前贷款</h6>
    <?php foreach ($loanList as $l): ?>
    <div class="emp-card d-flex justify-content-between">
        <div><?= $l['plan_type'] ?>：¥<?= number_format($l['amount']) ?></div>
        <div>剩余 <?= $l['remaining'] ?>/<?= $l['months'] ?> 个月 | 月供 ¥<?= number_format($l['monthly_payment']) ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ========= 销售 & 其他 ========= -->
<div id="tab-stores" class="tab-content" style="display:none">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>🏪 店铺 & 其他操作</h5>
        <a href="?route=enterprise-sales" class="btn btn-outline-primary btn-sm">销售管理 →</a>
    </div>
    <?php if (!empty($storeList)): ?>
    <?php foreach ($storeList as $s): ?>
    <div class="emp-card d-flex justify-content-between">
        <div><?= $s['type'] === 'online' ? '🌐 线上' : '🏪 线下' ?> <?= htmlspecialchars($s['name']) ?></div>
        <div>日营收 ¥<?= number_format($s['daily_revenue']) ?></div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <p class="text-muted">暂无店铺，企业2级后可开店</p>
    <?php endif; ?>

    <div class="mt-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-success btn-sm" onclick="openStore('online')">🌐 开线上店 ¥100,000</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="openStore('offline')">🏪 开线下店 ¥300,000</button>
        <button class="btn btn-outline-warning btn-sm" onclick="dividend()">💰 分红（转入股票账户）</button>
        <?php if ((int)$company['level'] >= 5 && !$company['is_listed']): ?>
        <button class="btn btn-outline-info btn-sm" onclick="applyIpo()">📈 申请上市（保证金5000万）</button>
        <?php endif; ?>
    </div>
</div>

<!-- 财务日志 -->
<div class="mt-4">
    <h5>📒 最近财务动态</h5>
    <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
        <table class="table table-sm table-hover">
            <thead><tr><th>类型</th><th>类别</th><th>金额</th><th>余额</th></tr></thead>
            <tbody>
            <?php foreach ($finList as $f): ?>
            <tr>
                <td><span class="badge <?= $f['type']==='income'?'bg-success':'bg-danger' ?>"><?= $f['type']==='income'?'收入':'支出' ?></span></td>
                <td><?= htmlspecialchars($f['category']) ?></td>
                <td class="<?= $f['type']==='income'?'text-success':'text-danger' ?>">¥<?= number_format($f['amount']) ?></td>
                <td>¥<?= number_format($f['balance_after']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<script>
function switchTab(name) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.section-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    event.target.classList.add('active');
}

function api(action, data, cb) {
    let body = 'action=' + action;
    for (let k in data) body += '&' + k + '=' + encodeURIComponent(data[k]);
    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    }).then(r => r.json()).then(d => { if (d.ok) { if (cb) cb(d); location.reload(); } else alert(d.error); });
}

function setSpeed(s) { api('setSpeed', {speed:s}); }
let candidatesData = [];

function recruit(paid) {
    fetch('?route=enterprise-api', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=recruit&paid='+(paid?1:0) })
    .then(r => r.json()).then(d => {
        if (!d.ok) { alert(d.error); return; }
        candidatesData = d.candidates;
        renderCandidates();
    });
}

function renderCandidates() {
    let remaining = candidatesData.filter(c => !c._hired).length;
    let html = '<h6>候选人列表 <small class="text-muted">剩余 ' + remaining + ' 人</small></h6><div class="row">';
    candidatesData.forEach((c, i) => {
        if (c._hired) {
            html += '<div class="col-md-4 col-lg-2 mb-2"><div class="emp-card text-center bg-success bg-opacity-10"><strong>'+c.name+'</strong><br><span class="text-success small">已入职 ✓</span></div></div>';
        } else {
            html += '<div class="col-md-4 col-lg-2 mb-2" id="cand-'+i+'"><div class="emp-card text-center" style="cursor:pointer" onclick="hire('+i+')">';
            html += '<strong>'+c.name+'</strong><br><span class="grade-'+c.grade.toLowerCase()+'">'+c.grade+'级</span><br>';
            html += '<small>￥'+c.salary.toLocaleString()+'/月 ×'+c.output_mult+'</small></div></div>';
        }
    });
    html += '</div>';
    if (remaining === 0 && candidatesData.length > 0) {
        html += '<button class="btn btn-outline-secondary btn-sm mt-1" onclick="location.reload()">刷新页面</button>';
    }
    document.getElementById('candidateList').innerHTML = html;
}

function hire(idx) {
    let c = candidatesData[idx];
    if (!c || c._hired) return;
    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=hire&name='+encodeURIComponent(c.name)+'&grade='+c.grade+'&salary='+c.salary+'&output_mult='+c.output_mult+'&department=订单部'
    }).then(r => r.json()).then(d => {
        if (d.ok) {
            c._hired = true;
            renderCandidates();
        } else {
            alert(d.error);
        }
    });
}
function fireEmployee(id, name) {
    if (confirm('确定解雇 ' + name + ' 吗？')) api('fire', {emp_id:id});
}
function setDept(id, dept) { api('setDept', {emp_id:id, department:dept}); }
function buyAsset(cat) { if (confirm('确定升级' + cat + '吗？')) api('buyAsset', {category:cat}); }
function buyProdLine() { if (confirm('确定购买新产线吗？')) api('buyProdLine', {}); }
function takeOrder(id) { api('takeOrder', {order_id:id}); }
function startRdPool(pid, name) {
    if (confirm('确定启动研发项目「' + name + '」吗？')) {
        api('startRd', {pool_id: pid});
    }
}
function applyLoan(type) { if (confirm('确定申请' + type + '贷款吗？')) api('loan', {plan_type:type}); }
function saveWelfare() {
    let data = {};
    document.querySelectorAll('#welfareForm input[type=checkbox]').forEach(cb => { data[cb.name] = cb.checked ? 1 : 0; });
    api('welfare', data);
}
function openStore(type) {
    let name = prompt('请输入店铺名称：');
    if (!name) return;
    api('openStore', {type:type, name:name});
}
function dividend() {
    let amt = prompt('请输入分红金额（将扣除20%税转入股票账户）：');
    if (!amt) return;
    api('dividend', {amount:parseFloat(amt)});
}
function invest() {
    let amt = prompt('请输入注资金额（从个人股票账户转入企业）：');
    if (!amt || parseFloat(amt) <= 0) return;
    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=invest&amount=' + parseFloat(amt)
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert(d.message); location.reload(); } else alert(d.error);
    });
}
function showUpgradeInfo() {
    document.getElementById('upgradeModal').style.display = 'flex';
}
function doUpgrade() {
    if (!confirm('确定升级企业吗？')) return;
    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=doUpgrade'
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert(d.message); location.reload(); } else alert(d.error);
    });
}
function applyIpo() { if (confirm('确定申请上市吗？需缴纳5000万保证金')) api('ipo', {}); }
function recover() { api('recover', {}); }
function repairLine(id) { api('repairLine', {line_id:id}); }
</script>
