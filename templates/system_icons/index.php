<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">管理系统统一图标库，供全体用户同步使用。</div>
    <button type="button" class="btn btn-sm btn-glass" data-bs-toggle="modal" data-bs-target="#systemIconCreateModal">+ 新增图标</button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php
$perPage = (int)($perPage ?? 10);
$iconSearch = (string)($iconSearch ?? '');
?>
<form method="get" class="card glass-card mb-3 p-3">
    <input type="hidden" name="route" value="system-icons">
    <input type="hidden" name="pending_page" value="<?= (int)$pendingPage ?>">
    <div class="row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-0">🔍 搜索</label>
    </div>
    <div class="col-sm-4 col-md-5 col-lg-4">
        <input id="iconSearchInput" type="search" name="icon_search" class="form-control form-control-sm" placeholder="按名称或路径搜索" value="<?= htmlspecialchars($iconSearch) ?>">
    </div>
    <div class="col-auto d-grid">
        <button type="submit" class="btn btn-sm btn-glass">搜索</button>
    </div>
    </div>
</form>
<?php
$iconPage = (int)($iconPage ?? 1);
$iconsTotalPages = (int)($iconsTotalPages ?? 1);
$iconsTotal = (int)($iconsTotal ?? 0);

$pendingPage = (int)($pendingPage ?? 1);
$pendingTotalPages = (int)($pendingTotalPages ?? 1);
$pendingTotal = (int)($pendingTotal ?? 0);

$iconPrev = max(1, $iconPage - 1);
$iconNext = min($iconsTotalPages, $iconPage + 1);
$pendingPrev = max(1, $pendingPage - 1);
$pendingNext = min($pendingTotalPages, $pendingPage + 1);
?>

<div class="card glass-card">
    <div class="card-body p-3">
        <h3 class="h6 mb-3">🖼️ 系统图标</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:55px;">预览</th>
                    <th style="width:28%;">名称</th>
                    <th style="width:110px;">来源标识</th>
                    <th style="width:90px;">来源类型</th>
                    <th style="width:28%;">存储路径</th>
                    <th class="text-center" style="width:210px;">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($systemIcons)): ?>
                    <tr><td colspan="6" class="text-center text-muted small">当前暂无系统图标，可通过右上角按钮新增。</td></tr>
                <?php else: ?>
                    <?php foreach ($systemIcons as $icon): ?>
                        <?php
                        $sid = (int)($icon['id'] ?? 0);
                        $sname = (string)($icon['name'] ?? '');
                        $spath = (string)($icon['file_path'] ?? '');
                        $sourceType = (string)($icon['source_type'] ?? '');
                        $sourceMode = (string)($icon['source_mode'] ?? '');
                        if ($sourceMode === '' && $spath !== '' && preg_match('/\\.svg$/i', $spath)) {
                            $sourceMode = 'svg';
                        }
                        ?>
                        <tr>
                            <td>
                                <?php if ($spath !== ''): ?>
                                    <img src="/uploads/<?= htmlspecialchars($spath) ?>" alt="预览" class="rounded" style="width:24px;height:24px;object-fit:cover;">
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($sname) ?></td>
                            <td>
                                <?php if ($sourceType === 'user'): ?>
                                    <span class="badge bg-primary-subtle text-primary">用户提交</span>
                                <?php elseif ($sourceType === 'admin'): ?>
                                    <span class="badge bg-secondary-subtle text-secondary">管理创建</span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sourceMode === 'svg'): ?>
                                    <span class="badge bg-info-subtle text-info">SVG</span>
                                <?php elseif ($sourceMode === 'upload'): ?>
                                    <span class="badge bg-secondary-subtle text-secondary">上传</span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($spath) ?></td>
                            <td class="text-center">
                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#systemIconEditModal"
                                        data-id="<?= (int)$sid ?>"
                                        data-name="<?= htmlspecialchars($sname) ?>"
                                        data-path="<?= htmlspecialchars($spath) ?>"
                                    >编辑</button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('确定要删除该系统图标吗？将联动清理所有用户图标库中的引用，并在文件无引用时删除实际文件。');">
                                        <input type="hidden" name="action" value="system_icon_delete">
                                        <input type="hidden" name="id" value="<?= $sid ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        $pageLinks = [];
        $startPage = max(1, $iconPage - 3);
        $endPage = min($iconsTotalPages, $iconPage + 3);
        if ($startPage > 1) {
            $pageLinks[] = 1;
            if ($startPage > 2) {
                $pageLinks[] = '...';
            }
        }
        for ($i = $startPage; $i <= $endPage; $i++) {
            $pageLinks[] = $i;
        }
        if ($endPage < $iconsTotalPages) {
            if ($endPage < $iconsTotalPages - 1) {
                $pageLinks[] = '...';
            }
            $pageLinks[] = $iconsTotalPages;
        }
        ?>
        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
            <div class="small text-muted">第 <?= (int)$iconPage ?> / <?= (int)$iconsTotalPages ?> 页（共 <?= (int)$iconsTotal ?> 条）</div>
            <nav aria-label="系统图标分页">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $iconPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?route=system-icons&icon_search=<?= urlencode($iconSearch) ?>&icon_page=<?= (int)$iconPrev ?>&pending_page=<?= (int)$pendingPage ?>">上一页</a>
                    </li>
                    <?php foreach ($pageLinks as $page): ?>
                        <?php if ($page === '...'): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php else: ?>
                            <li class="page-item <?= $page === $iconPage ? 'active' : '' ?>">
                                <a class="page-link" href="?route=system-icons&icon_search=<?= urlencode($iconSearch) ?>&icon_page=<?= (int)$page ?>&pending_page=<?= (int)$pendingPage ?>"><?= (int)$page ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li class="page-item <?= $iconPage >= $iconsTotalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?route=system-icons&icon_search=<?= urlencode($iconSearch) ?>&icon_page=<?= (int)$iconNext ?>&pending_page=<?= (int)$pendingPage ?>">下一页</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php $pendingSubmissions = $pendingSubmissions ?? []; ?>
<div class="card glass-card mt-3">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h6 mb-1">📋 待审核提交</h3>
                <div class="small text-muted">来自用户的公共图标库提交。</div>
            </div>
        </div>

        <?php if (!empty($pendingSubmissions)): ?>
            <form id="systemIconBulkReviewForm" method="post" onsubmit="return confirm('确定要批量执行审核操作吗？');" class="mb-2">
                <input type="hidden" name="action" value="system_icon_submission_bulk">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">批量操作</label>
                        <select name="bulk_action" class="form-select form-select-sm" required>
                            <option value="publish">公开入库</option>
                            <option value="replace">替换同名</option>
                            <option value="reject">驳回</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">驳回备注（可选）</label>
                        <input type="text" name="bulk_note" class="form-control form-control-sm" placeholder="批量驳回时可填写原因（可留空）">
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="submit" class="btn btn-sm btn-glass">批量执行</button>
                    </div>
                </div>
            </form>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:42px;">
                        <input class="form-check-input" type="checkbox" id="pendingCheckAll" onclick="togglePendingChecks(this)">
                    </th>
                    <th style="width:55px;">预览</th>
                    <th style="width:22%;">名称</th>
                    <th style="width:110px;">提交用户ID</th>
                    <th style="width:170px;">提交时间</th>
                    <th class="text-center" style="width:250px;">审核</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($pendingSubmissions)): ?>
                    <tr><td colspan="6" class="text-center text-muted small">暂无待审核提交。</td></tr>
                <?php else: ?>
                    <?php foreach ($pendingSubmissions as $sub): ?>
                        <?php
                        $subId = (int)($sub['id'] ?? 0);
                        $subName = (string)($sub['name'] ?? '');
                        $subPath = (string)($sub['file_path'] ?? '');
                        $subUid = (int)($sub['user_id'] ?? 0);
                        $subCreated = (string)($sub['created_at'] ?? '');
                        ?>
                        <tr>
                            <td>
                                <input class="form-check-input pending-check" type="checkbox" form="systemIconBulkReviewForm" name="submission_ids[]" value="<?= (int)$subId ?>">
                            </td>
                            <td>
                                <?php if ($subPath !== ''): ?>
                                    <img src="/uploads/<?= htmlspecialchars($subPath) ?>" alt="预览" class="rounded" style="width:24px;height:24px;object-fit:cover;">
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($subName) ?></td>
                            <td class="small text-muted"><?= (int)$subUid ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($subCreated) ?></td>
                            <td class="text-center">
                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                    <form method="post" class="d-inline" onsubmit="return confirm('确定公开入库该图标吗？若已存在同名图标，建议使用“替换同名”。');">
                                        <input type="hidden" name="action" value="system_icon_submission_publish">
                                        <input type="hidden" name="submission_id" value="<?= $subId ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">公开入库</button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('确定用该提交替换系统图标库中同名图标吗？若不存在同名，则按公开入库处理。');">
                                        <input type="hidden" name="action" value="system_icon_submission_replace">
                                        <input type="hidden" name="submission_id" value="<?= $subId ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">替换同名</button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return rejectSingleSubmissionWithNote(this);">
                                        <input type="hidden" name="action" value="system_icon_submission_reject">
                                        <input type="hidden" name="submission_id" value="<?= $subId ?>">
                                        <input type="hidden" name="note" value="">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">驳回</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="small text-muted">第 <?= (int)$pendingPage ?> / <?= (int)$pendingTotalPages ?> 页（共 <?= (int)$pendingTotal ?> 条）</div>
                    <nav aria-label="待审核分页">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $pendingPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?route=system-icons&icon_page=<?= (int)$iconPage ?>&pending_page=<?= (int)$pendingPrev ?>">上一页</a>
                            </li>
                            <li class="page-item <?= $pendingPage >= $pendingTotalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?route=system-icons&icon_page=<?= (int)$iconPage ?>&pending_page=<?= (int)$pendingNext ?>">下一页</a>
                            </li>
                        </ul>
                    </nav>
                </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:55px;">预览</th>
                        <th style="width:22%;">名称</th>
                        <th style="width:110px;">提交用户ID</th>
                        <th style="width:170px;">提交时间</th>
                        <th class="text-center" style="width:250px;">审核</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr><td colspan="5" class="text-center text-muted small">暂无待审核提交。</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="small text-muted">第 <?= (int)$pendingPage ?> / <?= (int)$pendingTotalPages ?> 页（共 <?= (int)$pendingTotal ?> 条）</div>
                <nav aria-label="待审核分页">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled"><span class="page-link">上一页</span></li>
                        <li class="page-item disabled"><span class="page-link">下一页</span></li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePendingChecks(master) {
    try {
        var list = document.querySelectorAll('.pending-check');
        for (var i = 0; i < list.length; i++) {
            list[i].checked = !!master.checked;
        }
    } catch (e) {}
}

function rejectSingleSubmissionWithNote(form) {
    try {
        var note = window.prompt('驳回备注（可选，可留空）：', '');
        if (note === null) {
            return false;
        }
        var input = form.querySelector('input[name="note"]');
        if (input) {
            input.value = (note || '').trim();
        }
    } catch (e) {
        // ignore
    }
    return window.confirm('确定要驳回该提交吗？');
}
</script>

<!-- 新增系统图标弹窗 -->
<div class="modal fade mgmt-modal" id="systemIconCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增系统图标</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="system_icon_create">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small">名称</label>
                            <input type="text" name="system_icon_name" class="form-control form-control-sm" placeholder="例如：微信、支付宝" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small">上传图标文件（图片或 SVG 文件）</label>
                            <input type="file" name="system_icon_file" class="form-control form-control-sm" accept="image/*,.svg">
                            <div class="form-text small">二选一：上传文件 或 粘贴 SVG。</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">粘贴 SVG（可选）</label>
                            <textarea name="system_icon_svg" class="form-control form-control-sm" rows="5" placeholder="在此粘贴完整的 <svg>...</svg> 代码"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 编辑系统图标弹窗 -->
<div class="modal fade mgmt-modal" id="systemIconEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑系统图标</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="system_icon_update">
                    <input type="hidden" name="id" id="systemIconEditId" value="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="small text-muted">当前文件：<span id="systemIconEditPath"></span></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small">名称</label>
                            <input type="text" name="system_icon_name" id="systemIconEditName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small">替换文件（可选）</label>
                            <input type="file" name="system_icon_file" class="form-control form-control-sm" accept="image/*,.svg">
                            <div class="form-text small">若不上传/不粘贴，将仅修改名称。</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">或粘贴 SVG 替换（可选）</label>
                            <textarea name="system_icon_svg" class="form-control form-control-sm" rows="5" placeholder="在此粘贴完整的 <svg>...</svg> 代码"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">保存修改</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        var modalEl = document.getElementById('systemIconEditModal');
        if (!modalEl) return;
        modalEl.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            var id = btn.getAttribute('data-id') || '0';
            var name = btn.getAttribute('data-name') || '';
            var path = btn.getAttribute('data-path') || '';
            var idEl = document.getElementById('systemIconEditId');
            var nameEl = document.getElementById('systemIconEditName');
            var pathEl = document.getElementById('systemIconEditPath');
            if (idEl) idEl.value = id;
            if (nameEl) nameEl.value = name;
            if (pathEl) pathEl.textContent = path || '-';
        });
    } catch (e) {
        console.error(e);
    }
});
</script>

<?php if (!empty($openModal) && $openModal === 'create'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('systemIconCreateModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    </script>
<?php endif; ?>
