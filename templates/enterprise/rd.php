<div class="container-fluid py-3">
<style>
.rd-pool-card { border: 2px solid var(--bs-border-color); border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; height: 100%; }
.rd-pool-card:hover { border-color: var(--bs-primary); box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
.rd-pool-card.selected { border-color: var(--bs-primary); background: var(--bs-primary-bg-subtle, rgba(13,110,253,0.05)); }
.rd-pool-card .rd-icon { font-size: 1.5rem; margin-bottom: 4px; }
.rd-pool-card .rd-name { font-weight: 600; font-size: 0.95rem; }
.rd-pool-card .rd-desc { font-size: 0.8rem; color: var(--bs-secondary-color); }
.rd-pool-card .rd-meta { font-size: 0.8rem; margin-top: 6px; }
.rd-quality-bar { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
</style>

<h3>🔬 研发中心</h3>
<p>
    企业资金：<strong class="text-success">¥<?= number_format($company['balance']) ?></strong>
    &nbsp;|&nbsp;
    已研发：<strong><?= $company['total_rd_count'] ?></strong> 项
    &nbsp;|&nbsp;
    研发部员工：<strong><?= $rdEmpCount ?></strong> 人
    &nbsp;|&nbsp;
    <a href="?route=enterprise" class="btn btn-outline-secondary btn-sm">← 返回企业</a>
</p>

<?php if ((int)$company['level'] < 2): ?>
<div class="alert alert-warning">企业需达到 <strong>2级（微小企业）</strong> 才能解锁研发部。请先升级企业。</div>
<?php else: ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>📋 可选研发项目（点击选择，再点启动研发）</strong>
        <small class="text-muted">研发部员工 <?= $rdEmpCount ?> 人 | 每人每天+3%研发进度</small>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($rdPool as $pid => $proj):
                $canAfford = (float)$company['balance'] >= $proj['cost'];
                $basePerDay = max(1, round(100 / max(1, $proj['days'])));
                $withEmp = max(1, $basePerDay + $rdEmpCount * 3);
                $estDays = ceil(100 / $withEmp);
            ?>
            <div class="col-md-4 col-lg-3">
                <div class="rd-pool-card" data-pid="<?= $pid ?>" onclick="selectRd(<?= $pid ?>, this)">
                    <div class="rd-icon"><?= match($pid % 5) { 0=>'💡', 1=>'🔧', 2=>'🧪', 3=>'⚡', 4=>'🖥️' } ?></div>
                    <div class="rd-name"><?= htmlspecialchars($proj['name']) ?></div>
                    <div class="rd-desc"><?= htmlspecialchars($proj['desc']) ?></div>
                    <div class="rd-meta">
                        <div>💰 投入：<strong class="<?= $canAfford ? 'text-success' : 'text-danger' ?>">¥<?= number_format($proj['cost']) ?></strong></div>
                        <div>
                            品质范围：
                            <span class="rd-quality-bar bg-light border"><?= $proj['min_quality'] ?>-<?= $proj['max_quality'] ?></span>
                        </div>
                        <div>⏱️ 预计周期：<strong><?= $proj['days'] ?></strong> 天
                            <?php if ($rdEmpCount > 0): ?>
                                <span class="text-success">（研发加速后约 <strong><?= $estDays ?></strong> 天）</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 text-end">
            <span id="selectedHint" class="text-muted me-2">请先选择项目</span>
            <button id="startRdBtn" class="btn btn-primary" disabled onclick="startRdProject()">启动研发</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($rdList)): ?>
<h5 class="mt-4">📊 研发进度</h5>
<div class="row">
    <?php foreach ($rdList as $rd):
        $basePerDay = max(1, round(100 / max(1, (int)($rd['research_days'] ?? 30))));
        $withEmp = max(1, $basePerDay + $rdEmpCount * 3);
        $remainDays = $rd['status'] === 'researching' ? ceil((100 - $rd['progress']) / $withEmp) : 0;
    ?>
    <div class="col-md-4 col-lg-3 mb-3">
        <div class="card h-100 <?= $rd['status']==='completed'?'border-success':'' ?>">
            <div class="card-body">
                <h6><?= htmlspecialchars($rd['name']) ?></h6>
                <span class="badge <?= $rd['status']==='completed'?'bg-success':'bg-info' ?> mb-2">
                    <?= $rd['status']==='completed'?'已完成':'研发中' ?>
                </span>
                <div class="small">
                    品质值: <strong><?= $rd['quality'] ?></strong><br>
                    投入: ¥<?= number_format($rd['cost']) ?>
                    <?php if ($rd['status'] === 'researching'): ?>
                        <br>剩余约: <strong><?= $remainDays ?></strong> 天
                    <?php endif; ?>
                </div>
                <?php if ($rd['status'] === 'researching'): ?>
                <div class="progress mt-2" style="height:10px">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:<?= $rd['progress'] ?>%"><?= $rd['progress'] ?>%</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<script>
let selectedPid = 0;
function selectRd(pid, el) {
    document.querySelectorAll('.rd-pool-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedPid = pid;
    document.getElementById('startRdBtn').disabled = false;
    document.getElementById('selectedHint').textContent = '已选择项目 #' + pid;
}
function startRdProject() {
    if (selectedPid <= 0) return alert('请先选择一个研发项目');
    if (!confirm('确定启动该项目吗？')) return;
    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=startRd&pool_id=' + selectedPid
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert(d.message); location.reload(); } else alert(d.error);
    });
}
</script>
