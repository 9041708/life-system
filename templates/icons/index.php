<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($cleanupLogs) && is_array($cleanupLogs)): ?>
    <div class="alert alert-warning py-2 small">
        <div class="fw-semibold mb-1">系统图标已清理</div>
        <div class="text-muted">管理员删除了部分系统图标，已从你的图标库移除（最多显示 50 条）：</div>
        <ul class="mb-0 mt-1">
            <?php foreach ($cleanupLogs as $log): ?>
                <li><?= htmlspecialchars((string)($log['name'] ?? ($log['file_path'] ?? '系统图标'))) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($syncChangeList) && is_array($syncChangeList)): ?>
    <div class="alert alert-info py-2 small">
        <div class="fw-semibold mb-1">本次系统图标同步清单</div>
        <ul class="mb-0">
            <?php foreach ($syncChangeList as $line): ?>
                <li><?= htmlspecialchars((string)$line) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card glass-card mb-3">
    <div class="card-body p-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div class="mb-2 mb-md-0">
            <h3 class="h6 mb-1">📦 从现有数据初始化图标库</h3>
            <div class="small text-muted">扫描当前用户的分类 / 项目 / 账户中已上传的文件图标，一键写入图标库（已存在路径会自动跳过）。</div>
        </div>
        <div>
            <form method="post" onsubmit="return confirm('确定要扫描并导入现有分类/项目/账户中的文件图标吗？已存在的会自动跳过。');">
                <input type="hidden" name="action" value="init_from_existing">
                <button type="submit" class="btn btn-sm btn-glass">一键导入历史图标</button>
            </form>
        </div>
    </div>
</div>
<div class="card glass-card">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3 gap-2">
            <div>
                <h3 class="h6 mb-1">🎨 图标库列表</h3>
                <div class="small text-muted">可从系统统一图标库一键同步常用图标，减少重复上传。</div>
                <?php if (!empty($hasSystemIconUpdates)): ?>
                    <div class="small text-warning mt-1">系统图标库有更新，请点击右侧"获取系统图标更新"。</div>
                <?php endif; ?>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="get" action="/public/index.php" class="d-inline">
                    <input type="hidden" name="route" value="icons">
                    <select name="submit_status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 160px;">
                        <option value="all" <?= (empty($submitStatusFilter) || $submitStatusFilter === 'all') ? 'selected' : '' ?>>提交状态：全部</option>
                        <option value="unsubmitted" <?= (!empty($submitStatusFilter) && $submitStatusFilter === 'unsubmitted') ? 'selected' : '' ?>>未提交</option>
                        <option value="pending" <?= (!empty($submitStatusFilter) && $submitStatusFilter === 'pending') ? 'selected' : '' ?>>待审核</option>
                        <option value="approved" <?= (!empty($submitStatusFilter) && $submitStatusFilter === 'approved') ? 'selected' : '' ?>>已通过</option>
                        <option value="rejected" <?= (!empty($submitStatusFilter) && $submitStatusFilter === 'rejected') ? 'selected' : '' ?>>已驳回</option>
                    </select>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('确定要获取系统图标更新吗？将同步新增/更新/删除的系统图标。');">
                    <input type="hidden" name="action" value="sync_system">
                    <button type="submit" class="btn btn-sm btn-glass">获取系统图标更新</button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('确定要将图标库中"未提交/已驳回"的个人图标一键提交到系统图标库审核吗？待审核/已通过的会自动跳过。');">
                    <input type="hidden" name="action" value="bulk_submit_to_system">
                    <button type="submit" class="btn btn-sm btn-glass">一键提交未提交/已驳回</button>
                </form>
                <button type="button" class="btn btn-sm btn-glass" data-bs-toggle="modal" data-bs-target="#iconCreateModal">+ 新增图标</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:55px;">预览</th>
                    <th style="width:25%;">名称</th>
                    <th style="width:85px;">标识</th>
                    <th style="width:75px;">来源</th>
                    <th style="width:105px;">提交状态</th>
                    <th style="width:25%;">存储路径</th>
                    <th class="text-center" style="width:110px;">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($icons)): ?>
                    <tr><td colspan="7" class="text-center text-muted small">图标库中暂无图标，可在上方表单中添加。</td></tr>
                <?php else: ?>
                    <?php foreach ($icons as $icon): ?>
                        <?php
                        $path = (string)($icon['file_path'] ?? '');
                        $isSystem = !empty($icon['system_icon_id']);
                        $sourceMode = (string)($icon['_source_mode'] ?? '');
                        $isSvg = ($sourceMode === 'svg');
                        $latestStatus = (!$isSystem && $path !== '' && !empty($latestSubmissionStatusByPath) && isset($latestSubmissionStatusByPath[$path]))
                            ? (string)$latestSubmissionStatusByPath[$path]
                            : '';
                        $latestMeta = (!$isSystem && $path !== '' && !empty($latestSubmissionMetaByPath) && isset($latestSubmissionMetaByPath[$path]) && is_array($latestSubmissionMetaByPath[$path]))
                            ? $latestSubmissionMetaByPath[$path]
                            : [];
                        $reviewNote = trim((string)($latestMeta['review_note'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <img src="/uploads/<?= htmlspecialchars($path) ?>" alt="图标预览" class="rounded" style="width:24px;height:24px;object-fit:cover;">
                            </td>
                            <td><?= htmlspecialchars($icon['name'] ?? '') ?></td>
                            <td>
                                <?php if ($isSystem): ?>
                                    <span class="badge bg-secondary-subtle text-secondary">系统图标</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">个人图标</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isSvg): ?>
                                    <span class="badge bg-info-subtle text-info">SVG</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">上传</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isSystem): ?>
                                    <span class="text-muted small">-</span>
                                <?php else: ?>
                                    <?php if ($latestStatus === 'pending'): ?>
                                        <span class="badge bg-warning-subtle text-warning">待审核</span>
                                    <?php elseif ($latestStatus === 'approved'): ?>
                                        <span class="badge bg-primary-subtle text-primary">已通过</span>
                                    <?php elseif ($latestStatus === 'rejected'): ?>
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-secondary-subtle text-secondary align-self-start">已驳回</span>
                                            <?php if ($reviewNote !== ''): ?>
                                                <span class="text-muted small text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($reviewNote) ?>">原因：<?= htmlspecialchars($reviewNote) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger">未提交</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($path) ?></td>
                            <td class="text-center">
                                <form method="post" class="d-inline" onsubmit="return confirm('确定从图标库中删除该记录吗？不会删除实际图片文件。');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$icon['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除记录</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3" aria-label="图标库分页">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    共 <?= $totalIcons ?> 个图标，第 <?= $currentPage ?>/<?= $totalPages ?> 页
                </div>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?route=icons&page=<?= max(1, $currentPage - 1) ?>&submit_status=<?= htmlspecialchars($submitStatusFilter) ?>">上一页</a>
                    </li>
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    if ($startPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="?route=icons&page=1&submit_status=<?= htmlspecialchars($submitStatusFilter) ?>">1</a></li>
                        <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?route=icons&page=<?= $i ?>&submit_status=<?= htmlspecialchars($submitStatusFilter) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?route=icons&page=<?= $totalPages ?>&submit_status=<?= htmlspecialchars($submitStatusFilter) ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?route=icons&page=<?= min($totalPages, $currentPage + 1) ?>&submit_status=<?= htmlspecialchars($submitStatusFilter) ?>">下一页</a>
                    </li>
                </ul>
            </div>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- 新增图标弹窗 -->
<div class="modal fade mgmt-modal" id="iconCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增图标到图标库</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label small">图标名称</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="例如：微信、支付宝、工资" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small d-block">上传图标文件</label>
                        <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm" required>
                        <div class="form-text small">建议使用正方形 PNG/JPG，小于 512KB。</div>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="submit_to_system" id="submit_to_system" value="1">
                        <label class="form-check-label small" for="submit_to_system">提交到公共图标库（管理员审核后可供全体用户同步使用）</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">添加到图标库</button>
                </div>
            </form>
        </div>
    </div>
    </div>