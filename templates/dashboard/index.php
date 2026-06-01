<div class="row g-3">
    <div class="col-lg-9">
<div class="row g-3">
    <?php if ($debtCurrentMonthCount > 0): ?>
    <div class="col-12 col-md-4">
        <div class="card glass-card h-100 border-start border-4 border-warning dashboard-balance-card" data-group="debt_monthly" data-title="当月应还">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">💳 当月应还</div>
                        <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($debtCurrentMonthTotal, 2) ?></div>
                        <div class="text-muted" style="font-size:0.75rem;"><?= $debtCurrentMonthCount ?> 笔待还</div>
                    </div>
                    <div class="text-warning fs-3">📋</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($reimbEnabled)): ?>
    <div class="col-12 col-md-4">
        <div class="card glass-card h-100 border-start border-4 border-info dashboard-balance-card" data-group="reimb_pending" data-title="待报销">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">🧾 待报销</div>
                        <div class="fs-4 fw-semibold text-info">¥ <?= number_format($reimbPendingTotal, 2) ?></div>
                        <div class="text-muted" style="font-size:0.75rem;"><?= $reimbPendingCount ?> 笔待报销</div>
                    </div>
                    <div class="text-info fs-3">💰</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-12 col-md-4">
        <a href="/public/report-card.php" target="_blank" class="card glass-card h-100 border-start border-4 border-primary text-decoration-none d-block" style="cursor:pointer">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">📊 财务报告</div>
                        <div class="mt-1" style="font-size:0.8rem;color:#666">月度 · 季度 · 年度</div>
                    </div>
                    <div class="text-primary fs-3">📋</div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mt-3">
    <?php
    $accountGroupCards = [
        'financial' => ['label' => '金融账户', 'icon' => '🏦', 'color' => 'primary'],
        'saving' => ['label' => '储蓄账户', 'icon' => '💰', 'color' => 'success'],
        'receivable' => ['label' => '应收账款', 'icon' => '📩', 'color' => 'info'],
        'debt' => ['label' => '应付账款', 'icon' => '💳', 'color' => 'warning'],
        'other' => ['label' => '其它账户', 'icon' => '🧾', 'color' => 'secondary'],
    ];
    ?>
    <?php foreach ($accountGroupCards as $groupCode => $groupMeta): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
            <div class="card glass-card h-100 border-start border-4 border-<?= htmlspecialchars($groupMeta['color']) ?> dashboard-balance-card" data-group="<?= htmlspecialchars($groupCode) ?>" data-title="<?= htmlspecialchars($groupMeta['label']) ?>详情">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small"><?= htmlspecialchars($groupMeta['icon']) ?> <?= htmlspecialchars($groupMeta['label']) ?></div>
                            <div class="fs-5 fw-semibold">¥ <?= number_format((float)($balances[$groupCode] ?? 0), 2) ?></div>
                        </div>
                        <div class="text-<?= htmlspecialchars($groupMeta['color']) ?> fs-3"><?= htmlspecialchars($groupMeta['icon']) ?></div>
                    </div>
                    <div class="small text-muted mt-2">点击查看该类别账户明细</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- 账户明细弹窗 -->
<style>
#dashboardAccountDetailModal {
    z-index: 1055 !important;
}
#dashboardAccountDetailModal .modal-content {
    position: relative;
    z-index: 1;
    pointer-events: auto;
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(20px) saturate(130%);
    -webkit-backdrop-filter: blur(20px) saturate(130%);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 1.25rem;
    box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.18);
}
body.theme-dark #dashboardAccountDetailModal .modal-content {
    position: relative;
    z-index: 1;
    pointer-events: auto;
    background: rgba(30, 41, 59, 0.72);
    backdrop-filter: blur(20px) saturate(110%);
    -webkit-backdrop-filter: blur(20px) saturate(110%);
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 1.5rem 3rem rgba(0, 0, 0, 0.5);
}
#dashboardAccountDetailModal .modal-header {
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    background: transparent;
}
body.theme-dark #dashboardAccountDetailModal .modal-header {
    border-bottom-color: rgba(148, 163, 184, 0.12);
}
#dashboardAccountDetailModal .modal-body {
    background: transparent;
}
#dashboardAccountDetailModal .modal-dialog {
    position: relative;
    z-index: 2;
    pointer-events: auto;
}
.item-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 0.75rem;
    margin-bottom: 10px;
    background: rgba(255, 255, 255, 0.45);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    transition: background 0.2s, box-shadow 0.2s;
}
.item-card:hover {
    background: rgba(255, 255, 255, 0.7);
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
}
body.theme-dark .item-card {
    background: rgba(30, 41, 59, 0.4);
    border-color: rgba(148, 163, 184, 0.12);
}
body.theme-dark .item-card:hover {
    background: rgba(30, 41, 59, 0.6);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
}
.item-card .item-icon {
    width: 40px; height: 40px;
    border-radius: 0.6rem;
    display: flex; align-items: center; justify-content: center;
    background: rgba(59, 130, 246, 0.1);
    flex-shrink: 0;
    font-size: 1.2rem;
    overflow: hidden;
}
.item-card .item-icon img {
    width: 100%; height: 100%; object-fit: cover;
}
.item-card .item-icon svg {
    width: 22px; height: 22px;
}
.item-card .item-info {
    flex: 1; min-width: 0;
}
.item-card .item-name {
    font-weight: 600; font-size: 0.92rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.item-card .item-meta {
    font-size: 0.78rem; color: #6b7280;
}
body.theme-dark .item-card .item-meta {
    color: #9ca3af;
}
.item-card .item-balance {
    font-weight: 700; font-size: 0.95rem;
    white-space: nowrap;
    flex-shrink: 0;
}
.empty-state {
    text-align: center; padding: 40px 20px; color: #9ca3af;
}
.empty-state .empty-icon { font-size: 3rem; margin-bottom: 12px; }
body.theme-dark .empty-state { color: #64748b; }

/* item-card for debt/reimb rows (simpler) */
.detail-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    font-size: 0.88rem;
}
body.theme-dark .detail-item {
    border-bottom-color: rgba(148, 163, 184, 0.1);
}
.detail-item:last-child { border-bottom: none; }
.detail-item .di-main { flex: 1; min-width: 0; font-weight: 500; }
.detail-item .di-amount { font-weight: 700; white-space: nowrap; }
.detail-total {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 14px;
    background: rgba(15, 23, 42, 0.03);
    border-radius: 0.5rem;
    font-weight: 600; font-size: 0.88rem;
}
body.theme-dark .detail-total {
    background: rgba(148, 163, 184, 0.06);
}
.dashboard-timeline-item {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 6px 0; border-bottom: 1px solid rgba(15,23,42,0.06);
    font-size: 0.78rem;
}
body.theme-dark .dashboard-timeline-item { border-bottom-color: rgba(148,163,184,0.1); }
</style>

<div class="modal fade" id="dashboardAccountDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashboardAccountDetailTitle">账户明细</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                $groupLabels = [
                    'financial'  => '金融账户',
                    'saving'     => '储蓄账户',
                    'receivable' => '应收账款',
                    'debt'       => '应付账款',
                    'other'      => '其它账户',
                ];
                ?>
                <?php foreach ($groupLabels as $code => $label): ?>
                    <div class="dashboard-account-detail d-none" data-group="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
                        <?php $list = $accountsByGroup[$code] ?? []; ?>
                        <?php if (empty($list)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <div>该分类下暂无账户</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($list as $a): ?>
                            <div class="item-card">
                                <div class="item-icon">
                                    <?php if (!empty($a['icon_type']) && !empty($a['icon_value'])): ?>
                                        <?php if ($a['icon_type'] === 'file'): ?>
                                            <img src="/uploads/<?= htmlspecialchars($a['icon_value']) ?>" alt="图标">
                                        <?php elseif ($a['icon_type'] === 'svg'): ?>
                                            <?= $a['icon_value'] ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        💳
                                    <?php endif; ?>
                                </div>
                                <div class="item-info">
                                    <div class="item-name"><?= htmlspecialchars($a['name']) ?></div>
                                    <?php if (!empty($a['account_no'])): ?>
                                    <div class="item-meta"><?= htmlspecialchars($a['account_no']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-balance">
                                    <span class="<?= ($a['current_balance'] ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                                        ¥ <?= number_format((float)($a['current_balance'] ?? 0), 2) ?>
                                    </span>
                                </div>
                                <a href="/public/index.php?route=transactions&amp;account_id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary">明细</a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- 当月应还负债明细 -->
                <div class="dashboard-account-detail d-none" data-group="debt_monthly">
                    <?php if (empty($debtCurrentMonthList)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">🎉</div>
                            <div>本月无应还项目</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($debtCurrentMonthList as $item): ?>
                            <?php $isOverdue = (strtotime($item['due_date']) < strtotime(date('Y-m-d'))); ?>
                            <div class="detail-item">
                                <div class="di-main">
                                    <?= htmlspecialchars($item['debt_name']) ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger ms-1">逾期</span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-secondary">第<?= $item['period_number'] ?>/<?= $item['installment_count'] ?>期</span>
                                <span class="small text-muted"><?= date('m-d', strtotime($item['due_date'])) ?></span>
                                <span class="di-amount text-danger">¥ <?= number_format((float)$item['total_amount'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="detail-total mt-2">
                            <span>合计</span>
                            <span class="text-danger">¥ <?= number_format($debtCurrentMonthTotal, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3 text-end">
                        <a href="/public/index.php?route=debt-current" class="btn btn-sm btn-outline-primary">查看全部</a>
                    </div>
                </div>

                <!-- 待报销明细 -->
                <div class="dashboard-account-detail d-none" data-group="reimb_pending">
                    <?php if (empty($reimbPendingList)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">💰</div>
                            <div>暂无待报销记录</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reimbPendingList as $item): ?>
                            <div class="detail-item">
                                <div class="di-main"><?= htmlspecialchars($item['title'] ?? '报销记录') ?></div>
                                <span class="badge bg-secondary"><?= htmlspecialchars($item['category_name'] ?? '未分类') ?></span>
                                <span class="small text-muted"><?= !empty($item['transaction_date']) ? date('m-d', strtotime($item['transaction_date'])) : '-' ?></span>
                                <span class="di-amount text-info">¥ <?= number_format((float)$item['amount'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="detail-total mt-2">
                            <span>合计</span>
                            <span class="text-info">¥ <?= number_format($reimbPendingTotal, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3 text-end">
                        <a href="/public/index.php?route=reimbursement" class="btn btn-sm btn-outline-info">查看全部</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-12 col-lg-4">
        <div class="card glass-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="text-muted small">当月预算（支出）</div>
                        <div class="fs-4 fw-semibold text-primary">¥ <?= number_format($monthBudget ?? 0, 2) ?></div>
                    </div>
                    <div class="text-primary fs-3">📅</div>
                </div>
                <?php if (!empty($monthBudget)): ?>
                    <div class="small text-muted mb-1">统计范围：仅包含已设置预算的支出分类/项目。</div>
                    <div class="small mb-1">
                        <span class="text-muted">已用预算：</span>
                        <span class="fw-semibold text-danger">¥ <?= number_format($monthBudgetUsed ?? 0, 2) ?></span>
                        <span class="text-muted ms-2">剩余额度：</span>
                        <span class="fw-semibold <?= ($monthBudgetRemain ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                            ¥ <?= number_format(max(0, $monthBudgetRemain ?? 0), 2) ?>
                        </span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <?php
                        $ratePercent = (int)($monthBudgetRatePercent ?? 0);
                        $barClass = 'bg-success';
                        $enableReminder = isset($budgetReminderEnabled) ? (bool)$budgetReminderEnabled : true;
                        if ($enableReminder) {
                            if (!empty($monthBudgetOver)) {
                                $barClass = 'bg-danger';
                            } elseif (!empty($monthBudgetWarn)) {
                                $barClass = 'bg-warning';
                            }
                        }
                        ?>
                        <div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= min(100, max(0, $ratePercent)) ?>%;"></div>
                    </div>
                    <div class="small mt-1">
                        <?php if (!empty($enableReminder) && !empty($monthBudgetOver)): ?>
                            <span class="text-danger">本月预算已超支（约 <?= (int)($monthBudgetRatePercent ?? 0) ?>%）。</span>
                        <?php elseif (!empty($enableReminder) && !empty($monthBudgetWarn)): ?>
                            <span class="text-warning">本月已使用约 <?= (int)($monthBudgetRatePercent ?? 0) ?>% 的预算，接近上限。</span>
                        <?php else: ?>
                            <span class="text-muted">本月已使用约 <?= (int)($monthBudgetRatePercent ?? 0) ?>% 的预算。</span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="small text-muted">当前尚未设置当月预算，建议前往“预算管理”页面配置一个整体或分项目预算。</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card glass-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="text-muted small">当月已支出</div>
                        <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($monthExpense ?? 0, 2) ?></div>
                    </div>
                    <div class="text-danger fs-3">💸</div>
                </div>
                <div class="small text-muted">按“支出”记账合计，方便对比预算执行情况。</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card glass-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="text-muted small">当月收入 & 结余</div>
                        <div class="fs-6 text-success mb-1">收入：¥ <?= number_format($monthIncome ?? 0, 2) ?></div>
                        <?php $net = $monthNet ?? 0; ?>
                        <div class="fs-6 <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">结余：¥ <?= number_format($net, 2) ?></div>
                    </div>
                    <div class="text-success fs-3">📈</div>
                </div>
                <div class="small text-muted">结余 = 当月收入 - 当月支出，帮助快速了解本月收支情况。</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-12 col-lg-4">
        <a href="/public/index.php?route=goals" class="text-decoration-none text-reset">
            <div class="card glass-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="text-muted small">目标进度</div>
                            <?php
                            $goalPercent = isset($goalOverallPercent) ? (int)$goalOverallPercent : 0;
                            ?>
                            <div class="fs-4 fw-semibold"><?= $goalPercent ?>%</div>
                        </div>
                        <div class="fs-3 text-primary">🎯</div>
                    </div>
                    <div class="small text-muted mb-1">
                        目标总额：¥ <?= number_format($goalTotalTarget ?? 0, 2) ?>，已完成：¥ <?= number_format($goalTotalSaved ?? 0, 2) ?>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, max(0, (int)($goalOverallPercent ?? 0))) ?>%;"></div>
                    </div>
                    <div class="small text-muted mt-1">
                        当前有效目标：<?= (int)($goalActiveCount ?? 0) ?> 个，点击查看详情和管理。
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<?php if (!empty($trendLabels7)): ?>
<div class="card glass-card mt-3">
    <div class="card-body">
        <h3 class="h6 mb-3">最近 7 天收支趋势</h3>
        <canvas id="dashboardTrend" style="max-height:320px;"></canvas>
    </div>
</div>

<?php $chartJsLocal = __DIR__ . '/../../assets/vendor/chart/chart.umd.min.js'; if (is_file($chartJsLocal)): ?>
<script src="/assets/vendor/chart/chart.umd.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>
<script>
    (function() {
        const ctx = document.getElementById('dashboardTrend');
        if (!ctx) return;
        const labels = <?= json_encode(array_values($trendLabels7), JSON_UNESCAPED_UNICODE) ?>;
        const incomeData = <?= json_encode(array_map('floatval', $trendIncome7), JSON_UNESCAPED_UNICODE) ?>;
        const expenseData = <?= json_encode(array_map('floatval', $trendExpense7), JSON_UNESCAPED_UNICODE) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '收入',
                        data: incomeData,
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.15)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    },
                    {
                        label: '支出',
                        data: expenseData,
                        borderColor: 'rgba(25, 135, 84, 1)',
                        backgroundColor: 'rgba(25, 135, 84, 0.15)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': ¥ ' + Number(ctx.parsed.y).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: { display: true },
                    y: { beginAtZero: true }
                }
            }
        });
    })();
</script>
<?php endif; ?>

    </div>
    <div class="col-lg-3">
        <?php
        $now = new DateTime();
        $thisYearTL = (int)$now->format('Y');
        $thisMonthTL = (int)$now->format('n');
        $thisDayTL = (int)$now->format('j');
        $tgI2 = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
        $dzI2 = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
        $sxI2 = ['鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'];
        $lunarMN2 = ['正','二','三','四','五','六','七','八','九','十','冬','腊'];
        $lunarDN2 = ['初一','初二','初三','初四','初五','初六','初七','初八','初九','初十','十一','十二','十三','十四','十五','十六','十七','十八','十九','二十','廿一','廿二','廿三','廿四','廿五','廿六','廿七','廿八','廿九','三十'];
        function _dashLunarCalc($y,$m,$d){
            $li=[0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,0x06ca0,0x0b550,0x15355,0x04da0,0x0a5b0,0x14573,0x052b0,0x0a9a8,0x0e950,0x06aa0,0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b6a0,0x195a6,0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,0x05aa0,0x076a3,0x096d0,0x04afb,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0,0x14b63,0x09370,0x049f8,0x04970,0x064b0,0x168a6,0x0ea50,0x06aa0,0x1a6c4,0x0aae0,0x0a2e0,0x0d2e3,0x0c960,0x0d557,0x0d4a0,0x0da50,0x05d55,0x056a0,0x0a6d0,0x055d4,0x052d0,0x0a9b8,0x0a950,0x0b4a0,0x0b6a6,0x0ad50,0x055a0,0x0aba4,0x0a5b0,0x052b0,0x0b273,0x06930,0x07337,0x06aa0,0x0ad50,0x14b55,0x04b60,0x0a570,0x054e4,0x0d160,0x0e968,0x0d520,0x0daa0,0x16aa6,0x056d0,0x04ae0,0x0a9d4,0x0a4d0,0x0d150,0x0f252,0x0d520];
            $base=new DateTime('1900-01-31');$target=new DateTime("{$y}-{$m}-{$d}");
            $diff=(int)$base->diff($target)->format('%r%a');
            if($diff<0)return['m'=>12,'d'=>30+$diff+1];
            $ly=1900;while(true){$idx=$ly-1900;$inf=$li[$idx]??0;$lm=$inf&0xf;$mc=$lm>0?13:12;$yd=0;for($i=1;$i<=$mc;$i++){$il=$lm>0&&$i===$lm+1;$md=$il?(($inf&(1<<16))?30:29):( ($inf&(1<<(16-$i)))?30:29);$yd+=$md;}if($diff<$yd)break;$diff-=$yd;$ly++;}
            $i2=$ly-1900;$inf2=$li[$i2]??0;$lm2=$inf2&0xf;$m2=1;$lp=false;
            for($j=1;$j<=($lm2>0?13:12);$j++){$il2=$lm2>0&&$j===$lm2+1;$md2=$il2?(($inf2&(1<<16))?30:29):( ($inf2&(1<<(16-$j)))?30:29);if($diff<$md2){$m2=$il2?($j-1):$j;$lp=$il2;break;}$diff-=$md2;}
            return['m'=>$m2,'d'=>$diff+1];
        }
        $todayLua = _dashLunarCalc($thisYearTL, $thisMonthTL, $thisDayTL);
        $dayDiff = (int)(new DateTime('2000-01-01'))->diff(new DateTime("{$thisYearTL}-{$thisMonthTL}-{$thisDayTL}"))->format('%r%a');
        $dGzIdx = (54 + $dayDiff) % 60; if ($dGzIdx < 0) $dGzIdx += 60;
        $dTg = $tgI2[$dGzIdx % 10]; $dDz = $dzI2[$dGzIdx % 12];
        $dTgIdx = (($thisYearTL - 4) % 10 + 10) % 10;
        $dDzIdx = (($thisYearTL - 4) % 12 + 12) % 12;
        $yGz = $tgI2[$dTgIdx] . $dzI2[$dDzIdx];
        $mZhiMap = [1=>2,2=>3,3=>4,4=>5,5=>6,6=>7,7=>8,8=>9,9=>10,10=>11,11=>0,12=>1];
        $mTrad = $thisMonthTL;
        $jqApprox2 = [1=>5,2=>4,3=>5,4=>5,5=>5,6=>5,7=>7,8=>7,9=>7,10=>8,11=>7,12=>7];
        if ($thisDayTL < $jqApprox2[$thisMonthTL]) { $mTrad--; if ($mTrad < 1) $mTrad = 12; }
        $mGz = $tgI2[(($thisYearTL - 4) % 10 + 10) % 10 * 2 + $mTrad % 10] . $dzI2[$mZhiMap[$mTrad]];
        $mGzShort = $tgI2[((($thisYearTL - 4) % 10) * 2 + $mTrad) % 10] . $dzI2[$mZhiMap[$mTrad]];
        $jcIdx2 = ($dGzIdx % 12 - $mZhiMap[$mTrad] + 12) % 12;
        $yiAll = [
            ['祭祀','祈福','求嗣','开光','出行','赴任','会亲友','进人口','修造','动土','竖柱上梁','开市','交易','立券','牧养'],
            ['祭祀','解除','沐浴','捕捉','治病','针灸','扫舍','破屋','坏垣'],
            ['祈福','求嗣','进人口','裁衣','纳财','开市','交易','立券','纳畜','牧养','栽种'],
            ['修饰','平治道涂','造畜稠','安葬','纳财','修造','动土','栽种','纳畜'],
            ['祈福','裁衣','嫁娶','纳采','安床','安门','开市','交易','立券','纳财','栽种'],
            ['祭祀','捕捉','纳财','纳畜','牧养','进人口','栽种','修造','动土','安床'],
            ['破屋','坏垣','求医','治病','解除','拆卸'],
            ['祈福','安葬','祭祀','修造','动土','造庙','沐浴','酬神'],
            ['开市','嫁娶','出行','纳采','安床','安门','开光','祈福','求嗣','交易','立券','纳财','入宅'],
            ['祭祀','纳财','捕捉','纳畜','牧养','进人口','栽种','修造','动土'],
            ['开市','出行','嫁娶','入宅','安床','开光','祈福','求嗣','交易','立券','纳财','栽种'],
            ['安葬','修造','纳财','纳畜','牧养','进人口','栽种','动土','破土'],
        ];
        $jiAll = [
            ['开仓','掘井','伐木','畋猎','渡水'],['嫁娶','出行','移徙','入宅','安床','开市'],
            ['动土','安葬','开仓','造庙','入宅'],['出行','嫁娶','移徙','入宅','开市','词讼'],
            ['词讼','出行','修造','动土','安葬'],['开市','出行','嫁娶','移徙','入宅','安葬'],
            ['嫁娶','开市','安葬','入宅','移徙','出行','修造'],['登高','出行','嫁娶','入宅','移徙','安床'],
            ['词讼','安葬','动土','修造','行丧'],['安葬','出行','嫁娶','移徙','入宅','开市'],
            ['动土','安葬','修造','破土','行丧'],['开市','出行','嫁娶','移徙','入宅','安床'],
        ];
        $todayYi = $yiAll[$jcIdx2 % 12]; $todayJi = $jiAll[$jcIdx2 % 12];
        $weekNames2 = ['日','一','二','三','四','五','六'];
        $cDays2 = [20,19,21,20,21,22,23,23,23,24,23,22];
        $cNames2 = ['摩羯','水瓶','双鱼','白羊','金牛','双子','巨蟹','狮子','处女','天秤','天蝎','射手'];
        $constIdx2 = $thisDayTL >= $cDays2[$thisMonthTL-1] ? $thisMonthTL % 12 : ($thisMonthTL + 10) % 12;
        $constellation2 = $cNames2[$constIdx2];
        $futureDates = [];
        for ($fd = 0; $fd < 4; $fd++) {
            $fDate = (new DateTime("{$thisYearTL}-{$thisMonthTL}-{$thisDayTL}"))->modify("+{$fd} day");
            $fDs = $fDate->format('Y-m-d');
            $fTasks = $tasksByDate[$fDs] ?? [];
            $futureDates[] = ['date'=>$fDs, 'weekday'=>'周'.$weekNames2[(int)$fDate->format('w')], 'label'=>$fd===0?'今天':($fd===1?'明天':($fd===2?'后天':$fDate->format('m/d'))), 'tasks'=>$fTasks];
        }
        ?>
        <div class="card glass-card" style="margin-bottom:12px">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span style="font-size:0.92rem;font-weight:700">📅 今日万年历</span>
                    <a href="/public/index.php?route=toolbox-calendar&date=<?= $thisYearTL.'-'.$thisMonthTL.'-'.$thisDayTL ?>" style="font-size:0.78rem" class="text-decoration-none">完整版 &raquo;</a>
                </div>
                <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:10px">
                    <span style="font-size:2rem;font-weight:800;line-height:1"><?= $thisDayTL ?></span>
                    <div style="font-size:0.82rem;line-height:1.5">
                        <div style="color:#9ca3af"><?= $thisYearTL ?>年<?= str_pad($thisMonthTL,2,'0',STR_PAD_LEFT) ?>月 · 周<?= $weekNames2[(int)(new DateTime("{$thisYearTL}-{$thisMonthTL}-{$thisDayTL}"))->format('w')] ?> <span id="live-clock" style="font-family:monospace;font-weight:600"><?= $now->format('H:i:s') ?></span></div>
                        <div>农历 <?= $lunarMN2[$todayLua['m']-1] ?>月<?= $lunarDN2[$todayLua['d']-1] ?></div>
                    </div>
                </div>
                <div style="font-size:0.78rem;color:#9ca3af;line-height:1.8;border-top:1px solid rgba(148,163,184,0.15);padding-top:8px">
                    <?= $yGz ?>年 <?= $mGzShort ?>月 <?= $dTg.$dDz ?>日 · 属<?= $sxI2[$dDzIdx] ?> · <?= $constellation2 ?>座
                </div>
                <div style="display:flex;gap:6px;margin-top:8px">
                    <span class="wl-badge wl-badge-warn" style="font-size:0.72rem;padding:3px 8px"><?= htmlspecialchars($jcIdx2 < 12 ? ['建','除','满','平','定','执','破','危','成','收','开','闭'][$jcIdx2] : '') ?></span>
                </div>
            </div>
        </div>

        <div class="card glass-card" style="margin-bottom:12px">
            <div class="card-body p-3">
                <div style="font-size:0.85rem;font-weight:600;margin-bottom:8px">☯️ 今日宜忌</div>
                <div style="margin-bottom:6px">
                    <span style="color:#16a34a;font-size:0.82rem;font-weight:600">宜</span>
                    <span style="font-size:0.82rem;color:var(--wl-text);margin-left:6px;line-height:1.6"><?= implode(' · ', array_slice($todayYi, 0, 8)) ?></span>
                </div>
                <div>
                    <span style="color:#dc2626;font-size:0.82rem;font-weight:600">忌</span>
                    <span style="font-size:0.82rem;color:var(--wl-text);margin-left:6px;line-height:1.6"><?= implode(' · ', array_slice($todayJi, 0, 6)) ?></span>
                </div>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span style="font-size:0.85rem;font-weight:700">📋 近期日程</span>
                    <a href="/public/index.php?route=easytodo-tasks" style="font-size:0.75rem" class="text-decoration-none">查看全部 &raquo;</a>
                </div>
                <?php foreach ($futureDates as $fdItem): ?>
                <div style="margin-bottom:12px;<?php if ($fdItem['label'] !== '今天') echo 'opacity:0.75;' ?>">
                    <div style="font-size:0.82rem;font-weight:600;margin-bottom:4px">
                        <?= $fdItem['label'] ?> <span style="color:#9ca3af;font-weight:400"><?= $fdItem['date'] ?> <?= $fdItem['weekday'] ?></span>
                        <?php if (count($fdItem['tasks']) > 0): ?>
                            <span style="font-size:0.7rem;background:var(--wl-primary);color:#fff;border-radius:8px;padding:1px 6px;margin-left:4px"><?= count($fdItem['tasks']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($fdItem['tasks'])): ?>
                        <div style="font-size:0.78rem;color:#d1d5db">暂无日程</div>
                    <?php else: ?>
                        <?php foreach (array_slice($fdItem['tasks'], 0, 3) as $tk): ?>
                        <?php $tc = $tk['color'] ?? 'blue'; $tcm = ['red'=>'#dc2626','orange'=>'#ea580c','yellow'=>'#ca8a04','green'=>'#16a34a','blue'=>'#2563eb']; ?>
                        <div style="font-size:0.78rem;border-left:2px solid <?= $tcm[$tc] ?? '#2563eb' ?>;padding-left:8px;margin-bottom:3px;line-height:1.5;<?= !empty($tk['completed']) ? 'text-decoration:line-through;opacity:0.5;' : '' ?>">
                            <?= htmlspecialchars(mb_strimwidth($tk['title'] ?? '', 0, 15, '...')) ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($fdItem['tasks']) > 3): ?>
                            <div style="font-size:0.72rem;color:#9ca3af">还有 <?= count($fdItem['tasks']) - 3 ?> 项...</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($homeBookmarks)): ?>
        <div class="card glass-card" style="margin-top:12px">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span style="font-size:0.85rem;font-weight:700">🌐 常用导航</span>
                    <a href="/public/index.php?route=nav-my" style="font-size:0.75rem" class="text-decoration-none">全部 &raquo;</a>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;max-height:320px;overflow-y:auto;padding-right:4px;">
                    <?php foreach ($homeBookmarks as $bm): ?>
                    <?php $mainUrl = $bm['url']; $hasScreenshot = !empty($bm['screenshot']); ?>
                    <a href="<?= htmlspecialchars($mainUrl) ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:0.65rem;text-decoration:none;color:inherit;background:rgba(255,255,255,0.35);border:1px solid rgba(255,255,255,0.4);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);transition:all 0.2s"
                       onmouseenter="this.style.background='rgba(255,255,255,0.55)';this.style.boxShadow='0 4px 12px rgba(15,23,42,0.08)';this.style.transform='translateY(-1px)'"
                       onmouseleave="this.style.background='rgba(255,255,255,0.35)';this.style.boxShadow='none';this.style.transform='none'">
                        <div style="width:36px;height:36px;flex-shrink:0;display:flex;align-items:center;justify-content:center;border-radius:0.5rem;overflow:hidden;background:rgba(15,23,42,0.04)">
                            <?php if (!empty($bm['icon_type']) && !empty($bm['icon_value'])): ?>
                                <?php if ($bm['icon_type'] === 'file'): ?>
                                    <img src="/uploads/<?= htmlspecialchars($bm['icon_value']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                <?php elseif ($bm['icon_type'] === 'svg'): ?>
                                    <span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;"><?= $bm['icon_value'] ?></span>
                                <?php elseif ($bm['icon_type'] === 'url'): ?>
                                    <img src="<?= htmlspecialchars($bm['icon_value']) ?>" style="width:100%;height:100%;object-fit:contain;" onerror="this.style.display='none'">
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="width:100%;height:100%;background:linear-gradient(135deg,#60a5fa,#818cf8);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.85rem"><?= mb_substr($bm['name'], 0, 1) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="min-width:0;flex:1">
                            <div style="font-weight:600;font-size:0.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--wl-text,#1e293b)"><?= htmlspecialchars($bm['name']) ?></div>
                            <?php if (!empty($bm['description'])): ?>
                                <div style="font-size:0.72rem;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px"><?= htmlspecialchars(mb_strimwidth($bm['description'], 0, 30, '...')) ?></div>
                            <?php else: ?>
                                <div style="font-size:0.72rem;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px"><?= htmlspecialchars($bm['group_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($hasScreenshot): ?>
                            <img src="/uploads/<?= htmlspecialchars($bm['screenshot']) ?>" style="width:56px;height:36px;object-fit:cover;border-radius:4px;flex-shrink:0;opacity:0.85;">
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('dashboardAccountDetailModal');
    if (!modalEl) return;

    if (modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }

    var cards = document.querySelectorAll('.dashboard-balance-card');

    var modalTitleEl = document.getElementById('dashboardAccountDetailTitle');

    function showDetail(group, title) {
        var detailBlocks = modalEl.querySelectorAll('.dashboard-account-detail');
        detailBlocks.forEach(function (el) {
            el.classList.add('d-none');
        });
        var target = modalEl.querySelector('.dashboard-account-detail[data-group="' + group + '"]');
        if (target) {
            target.classList.remove('d-none');
        }
        if (modalTitleEl && title) {
            modalTitleEl.textContent = title;
        }
        if (typeof bootstrap !== 'undefined') {
            var m = bootstrap.Modal.getOrCreateInstance(modalEl);
            m.show();
        }
    }

    cards.forEach(function (card) {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function () {
            var group = card.getAttribute('data-group');
            var title = card.getAttribute('data-title') || '账户明细';
            if (!group) return;
            showDetail(group, title);
        });
    });

    // 实时时钟
    (function updateClock() {
        var el = document.getElementById('live-clock');
        if (!el) return;
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        el.textContent = h + ':' + m + ':' + s;
        setTimeout(updateClock, 1000);
    })();

    // 实时时钟
    (function updateClock() {
        var el = document.getElementById('live-clock');
        if (!el) return;
        function tick() {
            var now = new Date();
            el.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');
            setTimeout(tick, 1000);
        }
        tick();
    })();
});
</script>

<?php if (!empty($pendingReminders)): ?>
<!-- 日程提醒弹窗 -->
<div class="modal fade" id="reminderModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content" style="border-radius:1rem;border-top:3px solid #f59e0b"><div class="modal-header py-2 px-3"><h6 class="modal-title" style="font-size:0.9rem">🔔 日程提醒</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body py-2 px-3" style="font-size:0.85rem;max-height:300px;overflow-y:auto">
<?php foreach ($pendingReminders as $pr): ?>
<?php $rc = $pr['color'] ?? 'blue'; $rcm = ['red'=>'#dc2626','orange'=>'#ea580c','yellow'=>'#ca8a04','green'=>'#16a34a','blue'=>'#2563eb']; ?>
<div class="d-flex align-items-center gap-2 py-1" style="border-left:3px solid <?= $rcm[$rc] ?? '#2563eb' ?>;padding-left:8px;margin-bottom:4px">
    <span style="font-size:0.82rem"><?= htmlspecialchars($pr['title']) ?></span>
    <span class="text-muted" style="font-size:0.7rem"><?= htmlspecialchars($pr['task_date']) ?></span>
</div>
<?php endforeach; ?>
</div><div class="modal-footer py-1 px-3"><a href="/public/index.php?route=easytodo-tasks" class="btn btn-sm btn-outline-primary">查看待办</a></div></div></div></div>
<script>document.addEventListener('DOMContentLoaded',function(){if(typeof bootstrap!=='undefined')bootstrap.Modal.getOrCreateInstance(document.getElementById('reminderModal')).show()})</script>
<?php endif; ?>
