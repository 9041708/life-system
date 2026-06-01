<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">已报销完成的记录。</div>
    <div class="d-flex gap-2">
        <a href="/public/index.php?route=reimbursement" class="btn btn-sm btn-outline-primary">报销总览</a>
        <a href="/public/index.php?route=reimbursement-pending" class="btn btn-sm btn-outline-warning">待报销</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (empty($items)): ?>
        <div class="text-center py-5">
            <div class="mb-3" style="font-size: 4rem;">📋</div>
            <h5 class="text-muted">暂无已报销记录</h5>
            <p class="text-muted">在「待报销」中标记报销后，记录将显示在这里</p>
            <a href="/public/index.php?route=reimbursement-pending" class="btn btn-outline-primary mt-2">前往待报销</a>
        </div>
    <?php else: ?>
        <?php
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += (float)$item['amount'];
            }
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <span class="text-muted">共 <?= count($items) ?> 条记录</span>
                <span class="fw-bold">报销总额：<span class="text-success">¥<?= number_format($totalAmount, 2) ?></span></span>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>标题</th>
                            <th class="text-end">金额</th>
                            <th>分类</th>
                            <th>报销时间</th>
                            <th>原始创建时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($item['title']) ?></div>
                                    <?php if (!empty($item['description'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars(mb_substr($item['description'], 0, 50)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-success">¥<?= number_format($item['amount'], 2) ?></td>
                                <td>
                                    <?php if (!empty($item['category_name'])): ?>
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($item['category_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= $item['reimbursed_at'] ?? '-' ?></td>
                                <td class="small text-muted"><?= $item['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
