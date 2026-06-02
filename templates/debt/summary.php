<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">所有负债项目的汇总统计。</div>
    <a href="/public/index.php?route=debt-config" class="btn btn-sm btn-outline-primary">管理负债配置</a>
</div>

<?php if ($grandTotalRemaining > 0): ?>
<!-- 总计卡片 -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">总本金</div>
                <div class="fs-4 fw-bold">¥<?= number_format($grandTotalPrincipal, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">总利息</div>
                <div class="fs-4 fw-bold text-warning">¥<?= number_format($grandTotalInterest, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">已还总额</div>
                <div class="fs-4 fw-bold text-success">¥<?= number_format($grandTotalPaid, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">剩余总额</div>
                <div class="fs-4 fw-bold text-danger">¥<?= number_format($grandTotalRemaining, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- 总进度 -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold">整体还款进度</span>
            <span class="small text-muted"><?= $grandPaidPeriods ?> / <?= $grandTotalPeriods ?> 期（<?= $grandProgressPercent ?>%）</span>
        </div>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $grandProgressPercent ?>%;"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($summary)): ?>
        <div class="text-center py-5">
            <div class="mb-3" style="font-size: 4rem;">📭</div>
            <h5 class="text-muted">暂无负债配置</h5>
            <p class="text-muted">点击下方按钮添加你的第一个负债项目</p>
            <a href="/public/index.php?route=debt-config-create" class="btn btn-primary mt-3">+ 添加负债配置</a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">负债项目</th>
                                <th>总期数</th>
                                <th>已还期数</th>
                                <th>剩余期数</th>
                                <th>剩余金额</th>
                                <th>进度</th>
                                <th class="text-end pe-3">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary as $item): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold"><?= htmlspecialchars($item['debt_name']) ?></div>
                                        <div class="small text-muted">
                                            本金: ¥<?= number_format($item['total_principal'], 2) ?> 
                                            | 利息: ¥<?= number_format($item['total_interest'], 2) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $item['installment_count'] ?> 期</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?= $item['paid_periods'] ?> 期</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?= $item['remaining_periods'] ?> 期</span>
                                    </td>
                                    <td class="fw-bold text-danger">
                                        ¥<?= number_format($item['remaining_amount'], 2) ?>
                                    </td>
                                    <td style="min-width: 150px;">
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 0.75rem;">
                                                <div class="progress-bar bg-success" 
                                                     role="progressbar" 
                                                     style="width: <?= $item['progress_percent'] ?>%;">
                                                </div>
                                            </div>
                                            <span class="small"><?= $item['progress_percent'] ?>%</span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="/public/index.php?route=debt-config-create&id=<?= $item['debt_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">编辑</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
