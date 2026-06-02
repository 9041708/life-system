<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">管理所有负债配置，支持等额本息和等额本金两种还款方式。</div>
    <a href="/public/index.php?route=debt-config-create" class="btn btn-sm btn-primary">+ 添加配置</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- 搜索栏 -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="debt-config">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="搜索负债名称..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">🔍 搜索</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($configs)): ?>
    <div class="text-center py-5">
        <div class="mb-3" style="font-size: 4rem;">📝</div>
        <h5 class="text-muted">暂无负债配置</h5>
        <p class="text-muted">点击下方按钮添加你的第一个负债项目</p>
        <a href="/public/index.php?route=debt-config-create" class="btn btn-primary mt-3">+ 添加负债配置</a>
    </div>
<?php else: ?>
    <?php
        // Search filter
        $searchKeyword = trim($_GET['search'] ?? '');
        $filteredConfigs = $configs;
        if ($searchKeyword !== '') {
            $filteredConfigs = array_filter($configs, function($c) use ($searchKeyword) {
                return stripos($c['name'] ?? '', $searchKeyword) !== false;
            });
        }
    ?>
    <div class="row g-2">
        <?php foreach ($filteredConfigs as $config):
            $statusBadge = $config['status'] === 'active' ? 'success' : 'secondary';
            $statusText = $config['status'] === 'active' ? '进行中' : '已完成';
            $methodText = $config['repayment_method'] === 'equal' ? '等额本息' : '等额本金';
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100" style="font-size: 0.85rem;">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-<?= $statusBadge ?>" style="font-size:0.65rem;"><?= $statusText ?></span>
                                <strong style="font-size:0.9rem;"><?= htmlspecialchars($config['name']) ?></strong>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" type="button" data-bs-toggle="dropdown" style="font-size:0.75rem;">⋯</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/public/index.php?route=debt-config-create&id=<?= $config['id'] ?>">编辑</a></li>
                                    <?php if ($config['status'] === 'active'): ?>
                                        <li>
                                            <form method="post" action="/public/index.php?route=debt-config-cancel" onsubmit="return confirm('确定要取消这个负债配置吗？');">
                                                <input type="hidden" name="id" value="<?= $config['id'] ?>">
                                                <button type="submit" class="dropdown-item text-danger">取消配置</button>
                                            </form>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="text-muted mb-1" style="font-size:0.75rem;"><?= $methodText ?> · 每期 ¥<?= number_format($config['per_period_total'], 2) ?></div>
                        <div class="row g-1 mb-1">
                            <div class="col-4"><span class="text-muted">本金</span> <strong>¥<?= number_format($config['total_principal'], 2) ?></strong></div>
                            <div class="col-4"><span class="text-muted">利息</span> <strong class="text-warning">¥<?= number_format($config['total_interest'], 2) ?></strong></div>
                            <div class="col-4"><span class="text-muted">已还</span> <strong class="text-success"><?= $config['paid_periods'] ?>/<?= $config['installment_count'] ?></strong></div>
                        </div>
                        <div class="d-flex align-items-center gap-1 mb-1">
                            <div class="progress flex-grow-1" style="height: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $config['progress_percent'] ?>%;"></div>
                            </div>
                            <span style="font-size:0.7rem;"><?= $config['progress_percent'] ?>%</span>
                        </div>
                        <?php if ($config['remaining_amount'] > 0): ?>
                            <div class="text-danger" style="font-size:0.75rem;">剩余: ¥<?= number_format($config['remaining_amount'], 2) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($config['note'])): ?>
                            <div class="text-muted mt-1" style="font-size:0.7rem;">📝 <?= htmlspecialchars(mb_substr($config['note'], 0, 30)) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
