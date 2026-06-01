<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">设定储蓄目标，追踪完成进度。</div>
    <button type="button" class="btn btn-sm btn-glass" data-bs-toggle="modal" data-bs-target="#modalGoalCreate">+ 新增目标</button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card glass-card mb-3">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold">📊 总体进度</span>
            <span class="small text-muted">¥ <?= number_format($totalSaved ?? 0, 2) ?> / ¥ <?= number_format($totalTarget ?? 0, 2) ?></span>
        </div><?php
                $overallPercent = ($totalTarget ?? 0) > 0 ? min(100, round(($totalSaved ?? 0) / ($totalTarget ?? 1) * 100)) : 0;
                ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 8px;">
                        <div class="progress-bar" role="progressbar" style="width: <?= (int)$overallPercent ?>%;" aria-valuenow="<?= (int)$overallPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-muted" style="min-width:3rem;"><?= (int)$overallPercent ?>%</div>
                </div>
            </div>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body p-3">
        <?php if (empty($goals)): ?>
            <div class="text-muted small">当前还没有目标，点击右上角「新增目标」开始吧。</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%;">目标名称</th>
                        <th style="width: 12%;">目标金额</th>
                        <th style="width: 12%;">已完成</th>
                        <th style="width: 24%;">进度</th>
                        <th style="width: 12%;">截止日期</th>
                        <th style="width: 10%;">状态</th>
                        <th style="width: 10%;" class="text-end goals-actions-cell">操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($goals as $g): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($g['title']) ?>
                            <?php if (!empty($g['account_label'])): ?>
                                <div class="small text-muted">关联账户：<?= htmlspecialchars($g['account_label']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>¥ <?= number_format($g['target_amount'], 2) ?></td>
                        <td>¥ <?= number_format($g['saved_amount'], 2) ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= (int)$g['barPercent'] ?>%;" aria-valuenow="<?= (int)$g['barPercent'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="small text-muted" style="min-width:3rem; text-align:right;"><?= (int)$g['percent'] ?>%</span>
                            </div>
                        </td>
                        <td class="small text-muted">
                            <?php if (!empty($g['deadline'])): ?>
                                <?= htmlspecialchars(substr((string)$g['deadline'], 0, 10)) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if ($g['status'] === 'done'): ?>
                                <span class="badge bg-success-subtle text-success">已完成</span>
                            <?php elseif ($g['status'] === 'archived'): ?>
                                <span class="badge bg-secondary-subtle text-secondary">已归档</span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary">进行中</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end goals-actions-cell">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary btn-icon me-1 btn-goal-edit"
                                title="编辑目标"
                                data-id="<?= (int)$g['id'] ?>"
                                data-title="<?= htmlspecialchars($g['title'], ENT_QUOTES) ?>"
                                data-account-id="<?= (int)($g['account_id'] ?? 0) ?>"
                                data-target="<?= htmlspecialchars((string)$g['target_amount'], ENT_QUOTES) ?>"
                                data-saved="<?= htmlspecialchars((string)$g['saved_amount'], ENT_QUOTES) ?>"
                                data-deadline="<?= htmlspecialchars((string)($g['deadline'] ?? ''), ENT_QUOTES) ?>"
                                data-status="<?= htmlspecialchars((string)$g['status'], ENT_QUOTES) ?>"
                            >
                                <span class="visually-hidden">编辑</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true">
                                    <path d="M12.146.854a.5.5 0 0 1 .708 0l2.292 2.292a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-4 1.5a.5.5 0 0 1-.65-.65l1.5-4a.5.5 0 0 1 .11-.168l9.5-9.5zM11.207 3 3 11.207 2.146 13.854 4.793 13 13 4.793 11.207 3z"/>
                                </svg>
                            </button>
                            <form method="post" class="d-inline" onsubmit="return confirm('确定要删除这个目标吗？此操作不可恢复。');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="删除目标">
                                    <span class="visually-hidden">删除</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true">
                                        <path d="M5.5 5.5a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0v-6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0v-6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0v-6z"/>
                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3h11V2h-11v1z"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 新增目标弹窗 -->
<div class="modal fade mgmt-modal" id="modalGoalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增目标</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="create">
                    <div class="col-12">
                        <label class="form-label small">目标名称</label>
                        <input type="text" name="title" class="form-control form-control-sm" maxlength="50" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">目标账户（可选）</label>
                        <select name="account_id" class="form-select form-select-sm">
                            <option value="0">不绑定账户</option>
                            <?php if (!empty($accounts)): ?>
                                <?php foreach ($accounts as $a): ?>
                                    <option value="<?= (int)$a['id'] ?>">
                                        <?= htmlspecialchars('[' . ($a['group_name'] ?? '') . '] ' . ($a['name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text small">绑定账户后，可在「新增/编辑记账」时选择将某笔收入/转账转入同步到该目标；若同步了支出/转账转出则会扣减目标完成金额。不绑定账户则仅手动维护「已完成金额」（不影响记账流水本身）。</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">目标金额（¥）</label>
                        <input type="number" step="0.01" min="0.01" name="target_amount" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">已完成金额（初始值/手动调整，可选）</label>
                        <input type="number" step="0.01" min="0" name="saved_amount" class="form-control form-control-sm" value="0">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">截止日期（可选）</label>
                        <input type="date" name="deadline" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 编辑目标弹窗 -->
<div class="modal fade mgmt-modal" id="modalGoalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑目标</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-3" id="formGoalEdit">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="0">
                    <div class="col-12">
                        <label class="form-label small">目标名称</label>
                        <input type="text" name="title" class="form-control form-control-sm" maxlength="50" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">目标账户（可选）</label>
                        <select name="account_id" class="form-select form-select-sm">
                            <option value="0">不绑定账户</option>
                            <?php if (!empty($accounts)): ?>
                                <?php foreach ($accounts as $a): ?>
                                    <option value="<?= (int)$a['id'] ?>">
                                        <?= htmlspecialchars('[' . ($a['group_name'] ?? '') . '] ' . ($a['name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text small">绑定账户后，可在记账时选择将某笔收入/转账转入同步到该目标；同步支出/转账转出会扣减完成金额。不绑定账户则仅手动维护「已完成金额」。</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">目标金额（¥）</label>
                        <input type="number" step="0.01" min="0.01" name="target_amount" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">已完成金额（初始值/手动调整）</label>
                        <input type="number" step="0.01" min="0" name="saved_amount" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">截止日期（可选）</label>
                        <input type="date" name="deadline" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">状态</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="active">进行中</option>
                            <option value="done">已完成</option>
                            <option value="archived">已归档</option>
                        </select>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editButtons = document.querySelectorAll('.btn-goal-edit');
        var modalEl = document.getElementById('modalGoalEdit');
        var form = document.getElementById('formGoalEdit');
        if (!editButtons || !modalEl || !form || !window.bootstrap) return;

        editButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-id') || '0';
                var title = btn.getAttribute('data-title') || '';
                var target = btn.getAttribute('data-target') || '';
                var saved = btn.getAttribute('data-saved') || '';
                var deadline = btn.getAttribute('data-deadline') || '';
                var status = btn.getAttribute('data-status') || 'active';

                form.querySelector('input[name="id"]').value = id;
                form.querySelector('input[name="title"]').value = title;
                form.querySelector('input[name="target_amount"]').value = target;
                form.querySelector('input[name="saved_amount"]').value = saved;
                form.querySelector('input[name="deadline"]').value = deadline ? deadline.substring(0, 10) : '';
                form.querySelector('select[name="status"]').value = status;

                var accountId = btn.getAttribute('data-account-id') || '0';
                var accountSelect = form.querySelector('select[name="account_id"]');
                if (accountSelect) {
                    accountSelect.value = accountId;
                }

                var modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        });
    });
</script>
