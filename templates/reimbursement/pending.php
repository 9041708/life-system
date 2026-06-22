<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">管理待报销的记录。</div>
    <div class="d-flex gap-2">
        <a href="/public/index.php?route=reimbursement" class="btn btn-sm btn-outline-primary">报销总览</a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">新增报销</button>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($overview)): ?>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">待报销总额</div>
                <div class="fs-5 fw-bold text-warning">¥<?= number_format($overview['pending_amount'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">已报销总额</div>
                <div class="fs-5 fw-bold text-success">¥<?= number_format($overview['reimbursed_amount'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">记录总数</div>
                <div class="fs-5 fw-bold"><?= $overview['total_count'] ?? 0 ?> 条</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($items)): ?>
        <div class="text-center py-5">
            <div class="mb-3" style="font-size: 4rem;">🎉</div>
            <h5 class="text-muted">暂无待报销记录</h5>
            <p class="text-muted">点击右上角「新增报销」创建第一条记录</p>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>标题</th>
                            <th class="text-end">金额</th>
                            <th>分类</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th class="text-end">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php
                                $statusLabel = [
                                    'pending'   => ['待报销', 'warning'],
                                    'approved'  => ['已审批', 'info'],
                                    'reimbursed'=> ['已报销', 'success'],
                                    'rejected'  => ['已拒绝', 'danger'],
                                ];
                                $status = $statusLabel[$item['status']] ?? ['未知', 'secondary'];
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($item['title']) ?></div>
                                    <?php if (!empty($item['description'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars(mb_substr($item['description'], 0, 50)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold">¥<?= number_format($item['amount'], 2) ?></td>
                                <td>
                                    <?php if (!empty($item['category_name'])): ?>
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($item['category_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= $status[1] ?>"><?= $status[0] ?></span></td>
                                <td class="small text-muted"><?= $item['created_at'] ?></td>
                                <td class="text-end">
                                    <?php if ($item['status'] === 'pending' || $item['status'] === 'approved'): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确认标记为已报销？');">
                                            <input type="hidden" name="action" value="mark-reimbursed">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success">✓ 报销</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('确认删除此报销记录？');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- 新增报销弹窗 -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">新增报销记录</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">标题 <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="如：出差交通费">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">金额 <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="可选填写报销说明"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
