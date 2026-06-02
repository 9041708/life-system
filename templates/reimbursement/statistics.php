<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">报销数据统计与分析。</div>
    <div class="d-flex gap-2">
        <a href="/public/index.php?route=reimbursement" class="btn btn-sm btn-outline-primary">报销总览</a>
        <a href="/public/index.php?route=reimbursement-pending" class="btn btn-sm btn-outline-warning">待报销</a>
    </div>
</div>

<?php
    // 计算汇总数据
    $totalReimbursed = 0;
    $totalCount = 0;
    $maxMonthly = 0;
    foreach ($monthly as $m) {
        $totalReimbursed += (float)$m['total_amount'];
        $totalCount += (int)$m['count'];
        if ((float)$m['total_amount'] > $maxMonthly) {
            $maxMonthly = (float)$m['total_amount'];
        }
    }
    $avgPerRecord = $totalCount > 0 ? $totalReimbursed / $totalCount : 0;
    $catTotal = 0;
    $catCount = 0;
    $maxCat = 0;
    foreach ($category as $c) {
        $catTotal += (float)$c['total_amount'];
        $catCount += (int)$c['count'];
        if ((float)$c['total_amount'] > $maxCat) {
            $maxCat = (float)$c['total_amount'];
        }
    }
?>

<!-- 概览卡片 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">📋 待报销</div>
                <div class="fs-4 fw-bold text-warning"><?= $overview['pending_count'] ?? 0 ?></div>
                <div class="small text-muted">¥<?= number_format($overview['pending_amount'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">✅ 已报销</div>
                <div class="fs-4 fw-bold text-success"><?= $overview['reimbursed_count'] ?? 0 ?></div>
                <div class="small text-muted">¥<?= number_format($overview['reimbursed_amount'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">📅 本月已报销</div>
                <div class="fs-4 fw-bold text-primary"><?= $overview['this_month_count'] ?? 0 ?></div>
                <div class="small text-muted">¥<?= number_format($overview['this_month_amount'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">💰 累计报销</div>
                <div class="fs-4 fw-bold">¥<?= number_format($totalReimbursed, 2) ?></div>
                <div class="small text-muted">共 <?= $totalCount ?> 笔 · 均 ¥<?= number_format($avgPerRecord, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 月度趋势 -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0">📊 月度报销趋势</h6>
                <span class="small text-muted">最近 <?= count($monthly) ?> 个月</span>
            </div>
            <div class="card-body">
                <?php if (empty($monthly)): ?>
                    <p class="text-muted text-center py-4">暂无月度数据</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:20%;">月份</th>
                                    <th class="text-center" style="width:10%;">笔数</th>
                                    <th class="text-end" style="width:25%;">金额</th>
                                    <th style="width:35%;">趋势</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly as $i => $m): ?>
                                    <?php
                                        $pct = $maxMonthly > 0 ? round(((float)$m['total_amount'] / $maxMonthly) * 100) : 0;
                                        $isLatest = ($i === 0);
                                    ?>
                                    <tr class="<?= $isLatest ? 'table-primary bg-opacity-10' : '' ?>">
                                        <td class="fw-semibold">
                                            <?= htmlspecialchars($m['month']) ?>
                                            <?php if ($isLatest): ?>
                                                <span class="badge bg-primary ms-1" style="font-size:0.6rem;">最新</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><span class="badge bg-light text-dark"><?= (int)$m['count'] ?></span></td>
                                        <td class="text-end fw-bold">¥<?= number_format((float)$m['total_amount'], 2) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 10px; border-radius: 5px;">
                                                    <div class="progress-bar <?= $isLatest ? 'bg-primary' : 'bg-info' ?>" role="progressbar" style="width: <?= $pct ?>%; border-radius: 5px;"></div>
                                                </div>
                                                <span class="small text-muted" style="min-width:35px;"><?= $pct ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 简易柱状图 -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="d-flex align-items-end justify-content-between" style="height: 100px;">
                            <?php
                                $chartMonthly = array_reverse($monthly);
                            ?>
                            <?php foreach ($chartMonthly as $cm): ?>
                                <?php
                                    $barH = $maxMonthly > 0 ? max(4, round(((float)$cm['total_amount'] / $maxMonthly) * 100)) : 4;
                                ?>
                                <div class="text-center flex-fill" title="<?= htmlspecialchars($cm['month']) ?>: ¥<?= number_format((float)$cm['total_amount'], 2) ?>">
                                    <div class="mx-auto" style="width: 60%; max-width: 40px; height: <?= $barH ?>px; background: linear-gradient(180deg, #0d6efd 0%, #6ea8fe 100%); border-radius: 3px 3px 0 0;"></div>
                                    <div class="text-muted mt-1" style="font-size: 0.6rem; writing-mode: vertical-rl; text-orientation: mixed; display: inline-block; height: 50px;"><?= substr($cm['month'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 分类统计 -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0">🏷️ 按分类统计</h6>
                <span class="small text-muted"><?= count($category) ?> 个分类</span>
            </div>
            <div class="card-body">
                <?php if (empty($category)): ?>
                    <p class="text-muted text-center py-4">暂无分类数据</p>
                <?php else: ?>
                    <!-- 分类占比条形图 -->
                    <div class="mb-3">
                        <?php
                            $colors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-danger', 'bg-info', 'bg-secondary', 'bg-dark'];
                            $colorIdx = 0;
                        ?>
                        <?php foreach ($category as $c): ?>
                            <?php
                                $pct = $catTotal > 0 ? round(((float)$c['total_amount'] / $catTotal) * 100, 1) : 0;
                                $color = $colors[$colorIdx % count($colors)];
                                $colorIdx++;
                            ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small"><?= htmlspecialchars($c['category_name']) ?></span>
                                    <span class="small text-muted">¥<?= number_format((float)$c['total_amount'], 2) ?> (<?= $pct ?>%)</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?= $color ?>" role="progressbar" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 分类详情表 -->
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>分类</th>
                                    <th class="text-center">笔数</th>
                                    <th class="text-end">金额</th>
                                    <th class="text-end">占比</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category as $c): ?>
                                    <?php $pct = $catTotal > 0 ? round(((float)$c['total_amount'] / $catTotal) * 100, 1) : 0; ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($c['category_name']) ?></td>
                                        <td class="text-center"><span class="badge bg-light text-dark"><?= (int)$c['count'] ?></span></td>
                                        <td class="text-end fw-bold">¥<?= number_format((float)$c['total_amount'], 2) ?></td>
                                        <td class="text-end text-muted"><?= $pct ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>合计</th>
                                    <th class="text-center"><?= $catCount ?></th>
                                    <th class="text-end fw-bold">¥<?= number_format($catTotal, 2) ?></th>
                                    <th class="text-end">100%</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
