
<style>
.rpt-summary-card {
    border-radius: 0.75rem;
    transition: box-shadow 0.2s, transform 0.15s;
}
.rpt-summary-card:hover {
    box-shadow: 0 0.5rem 1.5rem rgba(15,23,42,0.1);
    transform: translateY(-2px);
}
body.theme-dark .rpt-summary-card:hover {
    box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.3);
}
.rpt-filter-card .form-select,
.rpt-filter-card .form-control {
    border-radius: 0.6rem !important;
    font-size: 0.84rem;
}
.rpt-filter-card .btn-primary {
    border-radius: 0.6rem !important;
}
</style>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card glass-card h-100 rpt-summary-card">
            <div class="card-body">
                <div class="text-muted small mb-1">💸 期间总支出</div>
                <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($totalExpense ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card glass-card h-100 rpt-summary-card">
            <div class="card-body">
                <div class="text-muted small mb-1">💰 期间总收入</div>
                <div class="fs-4 fw-semibold text-success">¥ <?= number_format($totalIncome ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card glass-card h-100 rpt-summary-card">
            <div class="card-body">
                <div class="text-muted small mb-1">📊 收支结余</div>
                <div class="fs-4 fw-semibold <?= (($totalIncome ?? 0) - ($totalExpense ?? 0)) >= 0 ? 'text-primary' : 'text-danger' ?>">¥ <?= number_format(($totalIncome ?? 0) - ($totalExpense ?? 0), 2) ?></div>
            </div>
        </div>
    </div>
</div>

<form method="get" action="/public/index.php" class="card glass-card mb-3 rpt-filter-card">
    <div class="card-body p-3">
        <input type="hidden" name="route" value="reports">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold">统计模式</label>
                <select name="mode" class="form-select form-select-sm">
                    <option value="month" <?= ($mode ?? 'month') === 'month' ? 'selected' : '' ?>>按月</option>
                    <option value="quarter" <?= ($mode ?? '') === 'quarter' ? 'selected' : '' ?>>按季度</option>
                    <option value="year" <?= ($mode ?? '') === 'year' ? 'selected' : '' ?>>按年度</option>
                    <option value="day" <?= ($mode ?? '') === 'day' ? 'selected' : '' ?>>今日</option>
                    <option value="yesterday" <?= ($mode ?? '') === 'yesterday' ? 'selected' : '' ?>>昨日</option>
                    <option value="custom" <?= ($mode ?? '') === 'custom' ? 'selected' : '' ?>>自定义</option>
                </select>
            </div>
            <div class="col-6 col-md-2 report-filter-year">
                <label class="form-label small fw-semibold">年份</label>
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; --$y): ?>
                        <option value="<?= $y ?>" <?= ((int)($year ?? date('Y')) === $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 report-filter-month">
                <label class="form-label small fw-semibold">月份</label>
                <select name="month" class="form-select form-select-sm">
                    <?php for ($m = 1; $m <= 12; ++$m): ?>
                        <option value="<?= $m ?>" <?= ((int)($month ?? date('n')) === $m) ? 'selected' : '' ?>><?= $m ?>月</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 report-filter-quarter">
                <label class="form-label small fw-semibold">季度</label>
                <select name="quarter" class="form-select form-select-sm">
                    <option value="1" <?= ((int)($quarter ?? 1) === 1) ? 'selected' : '' ?>>Q1 (1-3月)</option>
                    <option value="2" <?= ((int)($quarter ?? 1) === 2) ? 'selected' : '' ?>>Q2 (4-6月)</option>
                    <option value="3" <?= ((int)($quarter ?? 1) === 3) ? 'selected' : '' ?>>Q3 (7-9月)</option>
                    <option value="4" <?= ((int)($quarter ?? 1) === 4) ? 'selected' : '' ?>>Q4 (10-12月)</option>
                </select>
            </div>
            <div class="col-6 col-md-2 report-filter-date-from">
                <label class="form-label small fw-semibold">起始日期</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom ?? '') ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2 report-filter-date-to">
                <label class="form-label small fw-semibold">结束日期</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo ?? '') ?>" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-1 d-grid">
                <button type="submit" class="btn btn-sm btn-primary">查询</button>
            </div>
        </div>
        <div class="mt-2">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="compare_last_year" id="compareLastYear" value="1" <?= !empty($compareLastYear) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="compareLastYear">对比去年同期</label>
            </div>
        </div>
    </div>
</form>

<?php if (in_array($mode ?? 'month', ['year', 'quarter', 'month'], true)): ?>
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card glass-card h-100 rpt-summary-card">
            <div class="card-body">
                <div class="text-muted small mb-1">📋 期间总预算（支出）</div>
                <div class="fs-4 fw-semibold text-primary">¥ <?= number_format($totalBudgetExpense ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card glass-card h-100 rpt-summary-card">
            <div class="card-body">
                <div class="text-muted small mb-1">💳 期间已支出（按预算口径）</div>
                <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($totalUsedExpense ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card glass-card h-100 rpt-summary-card">
            <div class="card-body">
                <div class="text-muted small mb-1">✅ 预算剩余</div>
                <div class="fs-4 fw-semibold text-success">¥ <?= number_format(($totalBudgetExpense ?? 0) - ($totalUsedExpense ?? 0), 2) ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modeSelect = document.querySelector('select[name="mode"]');
    if (!modeSelect) return;

    var yearCol = document.querySelector('.report-filter-year');
    var monthCol = document.querySelector('.report-filter-month');
    var quarterCol = document.querySelector('.report-filter-quarter');
    var dateFromCol = document.querySelector('.report-filter-date-from');
    var dateToCol = document.querySelector('.report-filter-date-to');

    var dateFromInput = document.querySelector('input[name="date_from"]');
    var dateToInput = document.querySelector('input[name="date_to"]');

    function toggle(el, show) {
        if (!el) return;
        el.classList.toggle('d-none', !show);
    }

    function formatDate(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function applyQuickDate(mode) {
        if (!dateFromInput || !dateToInput) return;
        var today = new Date();
        if (mode === 'day') {
            var d = formatDate(today);
            dateFromInput.value = d;
            dateToInput.value = d;
        } else if (mode === 'yesterday') {
            var yDay = new Date(today.getTime());
            yDay.setDate(yDay.getDate() - 1);
            var yd = formatDate(yDay);
            dateFromInput.value = yd;
            dateToInput.value = yd;
        }
    }

    function updateVisibility() {
        var mode = modeSelect.value;

        // 年份：按年度 / 按季度 / 按月度
        var showYear = (mode === 'year' || mode === 'quarter' || mode === 'month');
        toggle(yearCol, showYear);

        // 月份：仅按月度
        toggle(monthCol, mode === 'month');

        // 季度：仅按季度
        toggle(quarterCol, mode === 'quarter');

        // 日期范围：今日 / 昨日 / 自定义
        var showDate = (mode === 'day' || mode === 'yesterday' || mode === 'custom');
        toggle(dateFromCol, showDate);
        toggle(dateToCol, showDate);

        // 切换到“今日 / 昨日”时自动设置日期
        if (mode === 'day' || mode === 'yesterday') {
            applyQuickDate(mode);
        }
    }

    modeSelect.addEventListener('change', updateVisibility);
    updateVisibility();
});
</script>

<div class="card glass-card mt-3">
    <div class="card-body p-4">
        <h3 class="h6 mb-3">📊 收支柱状图</h3>
        <?php if (empty($labels)): ?>
            <div class="text-muted small">当前时间范围内暂无记账数据。</div>
        <?php else: ?>
            <canvas id="reportChart" style="max-height:380px;"></canvas>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($labels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const ctx = document.getElementById('reportChart');
        if (!ctx) return;
        const labels = <?= json_encode(array_values($labels), JSON_UNESCAPED_UNICODE) ?>;
        const incomeData = <?= json_encode(array_map('floatval', $incomeData), JSON_UNESCAPED_UNICODE) ?>;
        const expenseData = <?= json_encode(array_map('floatval', $expenseData), JSON_UNESCAPED_UNICODE) ?>;
        const compareLastYear = <?= !empty($compareLastYear) ? 'true' : 'false' ?>;
        const incomeLastData = <?= json_encode(array_map('floatval', $incomeLastData ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const expenseLastData = <?= json_encode(array_map('floatval', $expenseLastData ?? []), JSON_UNESCAPED_UNICODE) ?>;

        const datasets = [
            {
                label: '收入',
                data: incomeData,
                backgroundColor: 'rgba(220, 53, 69, 0.5)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1
            },
            {
                label: '支出',
                data: expenseData,
                backgroundColor: 'rgba(25, 135, 84, 0.5)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1
            }
        ];

        if (compareLastYear && incomeLastData.length === incomeData.length && expenseLastData.length === expenseData.length) {
            datasets.push(
                {
                    label: '收入（去年同期）',
                    data: incomeLastData,
                    backgroundColor: 'rgba(220, 53, 69, 0.15)',
                    borderColor: 'rgba(220, 53, 69, 0.7)',
                    borderWidth: 1
                },
                {
                    label: '支出（去年同期）',
                    data: expenseLastData,
                    backgroundColor: 'rgba(25, 135, 84, 0.15)',
                    borderColor: 'rgba(25, 135, 84, 0.7)',
                    borderWidth: 1
                }
            );
        }

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ¥ ' + Number(ctx.parsed.y).toFixed(2); } } }
                },
                scales: {
                    x: { stacked: false },
                    y: { beginAtZero: true }
                }
            }
        });
    })();
</script>
<?php endif; ?>

<?php if (!empty($categoryLabels ?? [])): ?>
<div class="card glass-card mt-3">
    <div class="card-body p-4">
        <h3 class="h6 mb-3">🏷️ 按分类 / 项目支出</h3>
        <canvas id="categoryChart" style="max-height:380px;"></canvas>
        <div class="small text-muted mt-2">点击柱子可跳转到对应分类 / 项目的流水列表，并自动带上当前时间范围筛选。</div>
    </div>
</div>

<script>
    (function() {
        const ctx = document.getElementById('categoryChart');
        if (!ctx) return;

        const labels = <?= json_encode(array_values($categoryLabels), JSON_UNESCAPED_UNICODE) ?>;
        const data = <?= json_encode(array_map('floatval', $categoryData), JSON_UNESCAPED_UNICODE) ?>;
        const links = <?= json_encode(array_values($categoryLinks), JSON_UNESCAPED_UNICODE) ?>;

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '支出',
                        data: data,
                        backgroundColor: 'rgba(25, 135, 84, 0.5)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return '支出: ¥ ' + Number(ctx.parsed.y).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { maxRotation: 45, minRotation: 0 } },
                    y: { beginAtZero: true }
                },
                onClick: function(evt, elements) {
                    if (!elements || !elements.length) return;
                    const index = elements[0].index;
                    const url = links[index] || null;
                    if (url) {
                        window.location.href = url;
                    }
                }
            }
        });
    })();
</script>
<?php endif; ?>
