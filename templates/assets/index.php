<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">记录和管理您的个人资产。</div>
    <button type="button" class="btn btn-sm btn-glass" data-bs-toggle="modal" data-bs-target="#assetCreateModal">+ 新增资产</button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form class="card glass-card mb-3 p-3" method="get">
    <div class="row g-2 align-items-end">
    <input type="hidden" name="route" value="assets">
    <div class="col-12 col-md-4 col-lg-3">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="搜索资产名称" value="<?= htmlspecialchars($keyword ?? '') ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <select name="sort" class="form-select form-select-sm">
            <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>最新添加</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>最早添加</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>价格从高到低</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>价格从低到高</option>
            <option value="daily_desc" <?= $sort === 'daily_desc' ? 'selected' : '' ?>>日均从高到低</option>
            <option value="daily_asc" <?= $sort === 'daily_asc' ? 'selected' : '' ?>>日均从低到高</option>
        </select>
    </div>
    <div class="col-12 col-md-2 col-lg-2 d-flex justify-content-end">
        <button type="submit" class="btn btn-sm btn-glass">筛选</button>
    </div>
</form>
    </div>
</form>

<div class="mb-3">
    <div class="btn-group" role="group">
        <a href="/public/index.php?route=assets&tab=active&q=<?= urlencode($keyword ?? '') ?>&sort=<?= urlencode($sort ?? 'latest') ?>" class="btn btn-outline-primary <?= ($viewTab ?? 'active') === 'active' ? 'active' : '' ?>">在服役中</a>
        <a href="/public/index.php?route=assets&tab=transferred&q=<?= urlencode($keyword ?? '') ?>&sort=<?= urlencode($sort ?? 'latest') ?>" class="btn btn-outline-primary <?= ($viewTab ?? 'active') === 'transferred' ? 'active' : '' ?>">已转手</a>
    </div>
</div>

<div class="card glass-card mb-3">
    <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div class="mb-2 mb-md-0">
            <div class="small text-muted">📊 我的资产概览（仅统计在服役中的资产）</div>
        </div>
        <div class="d-flex flex-wrap gap-3 small">
            <div>资产总值：<span class="fw-semibold"><?= number_format((float)($totalValue ?? 0), 2) ?></span></div>
            <div>资产数量：<span class="fw-semibold"><?= (int)($assetCount ?? 0) ?></span></div>
            <div>每日成本：<span class="fw-semibold"><?= number_format((float)($totalDailyCost ?? 0), 2) ?></span></div>
        </div>
    </div>
</div>

<?php
$list = ($viewTab ?? 'active') === 'transferred' ? ($transferredAssets ?? []) : ($activeAssets ?? []);
?>

<?php if (empty($list)): ?>
    <div class="text-muted small text-center py-4">暂无资产记录，可点击右上角“新增资产”开始记录。</div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        <?php foreach ($list as $a): ?>
            <div class="col">
                <div class="card glass-card h-100">
                    <div class="card-body d-flex">
                        <div class="me-3">
                            <?php if (!empty($a['icon_type']) && !empty($a['icon_value']) && $a['icon_type'] === 'file'): ?>
                                <img src="/uploads/<?= htmlspecialchars($a['icon_value']) ?>" alt="图标" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                    <span class="text-muted small">图</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($a['name'] ?? '') ?></div>
                                    <div class="small text-muted">到手日期：<?= htmlspecialchars($a['acquired_date'] ?? '') ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">¥ <?= number_format((float)($a['value_amount'] ?? 0), 2) ?></div>
                                    <div class="small text-muted">日均：¥ <?= number_format((float)($a['daily_cost'] ?? 0), 2) ?></div>
                                </div>
                            </div>
                            <?php if (!empty($a['remark'])): ?>
                                <div class="small text-muted mb-2" style="white-space:pre-wrap;">备注：<?= htmlspecialchars($a['remark']) ?></div>
                            <?php endif; ?>
                            <div class="small text-muted mb-2">
                                <?= ($a['status'] ?? 'active') === 'transferred' ? '已转手' : '在服役' ?>
                                <?php if (($a['status'] ?? 'active') === 'transferred'): ?>
                                    · 转手价：¥ <?= number_format((float)($a['transfer_price'] ?? 0), 2) ?>
                                <?php else: ?>
                                    · 使用天数：<?= (int)($a['use_days'] ?? 0) ?> 天
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assetEditModal"
                                        data-id="<?= (int)($a['id'] ?? 0) ?>"
                                        data-name="<?= htmlspecialchars($a['name'] ?? '', ENT_QUOTES) ?>"
                                        data-acquired-date="<?= htmlspecialchars($a['acquired_date'] ?? '') ?>"
                                        data-value-amount="<?= htmlspecialchars($a['value_amount'] ?? '') ?>"
                                        data-remark="<?= htmlspecialchars($a['remark'] ?? '', ENT_QUOTES) ?>"
                                        data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                                        data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>">
                                    编辑
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('确定删除该资产记录吗？');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)($a['id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                </form>
                                <?php if (($a['status'] ?? 'active') === 'active'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#assetTransferModal"
                                            data-id="<?= (int)($a['id'] ?? 0) ?>"
                                            data-name="<?= htmlspecialchars($a['name'] ?? '', ENT_QUOTES) ?>">
                                        转手
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- 新增资产 -->
<div class="modal fade mgmt-modal" id="assetCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增资产</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body row g-2 align-items-end">
                    <input type="hidden" name="action" value="create">
                    <div class="col-12">
                        <label class="form-label small">名称</label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">到手日期</label>
                        <input type="date" name="acquired_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">价值</label>
                        <input type="number" name="value_amount" step="0.01" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">资产图标</label>
                        <div class="form-text small mb-1">可选择上传小图标，或从图标库/系统统一图标库中选择（可选）。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="assetCreateIconNone" value="none" checked>
                            <label class="btn btn-outline-secondary" for="assetCreateIconNone">默认图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="assetCreateIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="assetCreateIconFile">上传图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="assetCreateIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="assetCreateIconLib">从图标库选择</label>
                        </div>
                        <div class="asset-icon-input-file d-none mb-2">
                            <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="submit_to_system" id="assetCreateSubmitToSystem" value="1">
                                <label class="form-check-label small" for="assetCreateSubmitToSystem">提交到公共图标库（管理员审核后可供全体用户同步使用）</label>
                            </div>
                        </div>
                        <div class="asset-icon-input-library d-none mb-2">
                            <?php if (!empty($iconLibrary) || !empty($systemIcons)): ?>
                                <input type="hidden" name="icon_library_id" id="assetCreateIconLibraryId" value="">
                                <input type="hidden" name="system_icon_id" id="assetCreateSystemIconId" value="">
                                <input type="text" class="form-control form-control-sm mb-2" id="assetCreateIconSearch" placeholder="搜索图标名称...">
                                <div id="assetCreateIconList" class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
                                    <?php if (!empty($iconLibrary)): ?>
                                        <div class="small text-muted mb-1">我的图标库</div>
                                        <?php foreach ($iconLibrary as $lib): ?>
                                            <div class="asset-create-icon-item icon-search-item d-flex align-items-center p-1 cursor-pointer" 
                                                 data-id="<?= (int)$lib['id'] ?>" 
                                                 data-system-id="0"
                                                 data-name="<?= htmlspecialchars($lib['name'] ?? '', ENT_QUOTES) ?>"
                                                 data-path="<?= htmlspecialchars($lib['file_path'] ?? '', ENT_QUOTES) ?>"
                                                 style="cursor:pointer;"><img src="/uploads/<?= htmlspecialchars($lib['file_path'] ?? '') ?>" alt="" class="rounded" style="width:24px;height:24px;object-fit:cover;margin-right:8px;"><span class="small"><?= htmlspecialchars($lib['name'] ?? '') ?></span></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($systemIcons)): ?>
                                        <div class="small text-muted mb-1 mt-2">系统统一图标库</div>
                                        <?php foreach ($systemIcons as $si): ?>
                                            <div class="asset-create-icon-item icon-search-item d-flex align-items-center p-1 cursor-pointer" 
                                                 data-id="0" 
                                                 data-system-id="<?= (int)($si['id'] ?? 0) ?>" 
                                                 data-name="<?= htmlspecialchars($si['name'] ?? '', ENT_QUOTES) ?>"
                                                 data-path="<?= htmlspecialchars($si['file_path'] ?? '', ENT_QUOTES) ?>"
                                                 style="cursor:pointer;"><img src="/uploads/<?= htmlspecialchars($si['file_path'] ?? '') ?>" alt="" class="rounded" style="width:24px;height:24px;object-fit:cover;margin-right:8px;"><span class="small"><?= htmlspecialchars($si['name'] ?? '') ?></span></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div id="assetCreateIconPreview" class="mt-2 small"></div>
                                <div class="form-text small">系统图标实际会在“图标库”页面同步后复用，避免重复上传。</div>
                            <?php else: ?>
                                <div class="form-text small text-muted">暂无可用图标，可先在“图标库”或“系统设置-系统统一图标库”中添加。</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">备注</label>
                        <textarea name="remark" class="form-control form-control-sm" rows="2" placeholder="可选，例如购买渠道、序列号等"></textarea>
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

<!-- 编辑资产 -->
<div class="modal fade mgmt-modal" id="assetEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑资产</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body row g-2 align-items-end">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="assetEditId">
                    <div class="col-12">
                        <label class="form-label small">名称</label>
                        <input type="text" name="name" id="assetEditName" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">到手日期</label>
                        <input type="date" name="acquired_date" id="assetEditAcquiredDate" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">价值</label>
                        <input type="number" name="value_amount" id="assetEditValueAmount" step="0.01" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">资产图标</label>
                        <div class="form-text small mb-1">可保留原图标，或重新上传 / 从图标库选择 / 清除图标。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="assetEditIconKeep" value="none" checked>
                            <label class="btn btn-outline-secondary" for="assetEditIconKeep">保持不变</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="assetEditIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="assetEditIconFile">上传新图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="assetEditIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="assetEditIconLib">从图标库选择</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="assetEditIconClear" value="clear">
                            <label class="btn btn-outline-danger" for="assetEditIconClear">清除图标</label>
                        </div>
                        <div class="asset-edit-icon-input-file d-none mb-2">
                            <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="submit_to_system" id="assetEditSubmitToSystem" value="1">
                                <label class="form-check-label small" for="assetEditSubmitToSystem">提交到公共图标库（管理员审核后可供全体用户同步使用）</label>
                            </div>
                        </div>
                        <div class="asset-edit-icon-input-library d-none mb-2">
                            <?php if (!empty($iconLibrary) || !empty($systemIcons)): ?>
                                <input type="hidden" name="icon_library_id" id="assetEditIconLibraryId" value="">
                                <input type="hidden" name="system_icon_id" id="assetEditSystemIconId" value="">
                                <input type="text" class="form-control form-control-sm mb-2" id="assetEditIconSearch" placeholder="搜索图标名称...">
                                <div id="assetEditIconList" class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
                                    <?php if (!empty($iconLibrary)): ?>
                                        <div class="small text-muted mb-1">我的图标库</div>
                                        <?php foreach ($iconLibrary as $lib): ?>
                                            <div class="asset-edit-icon-item icon-search-item d-flex align-items-center p-1 cursor-pointer" 
                                                 data-id="<?= (int)$lib['id'] ?>" 
                                                 data-system-id="0"
                                                 data-name="<?= htmlspecialchars($lib['name'] ?? '', ENT_QUOTES) ?>"
                                                 data-path="<?= htmlspecialchars($lib['file_path'] ?? '', ENT_QUOTES) ?>"
                                                 style="cursor:pointer;"><img src="/uploads/<?= htmlspecialchars($lib['file_path'] ?? '') ?>" alt="" class="rounded" style="width:24px;height:24px;object-fit:cover;margin-right:8px;"><span class="small"><?= htmlspecialchars($lib['name'] ?? '') ?></span></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($systemIcons)): ?>
                                        <div class="small text-muted mb-1 mt-2">系统统一图标库</div>
                                        <?php foreach ($systemIcons as $si): ?>
                                            <div class="asset-edit-icon-item icon-search-item d-flex align-items-center p-1 cursor-pointer" 
                                                 data-id="0" 
                                                 data-system-id="<?= (int)($si['id'] ?? 0) ?>" 
                                                 data-name="<?= htmlspecialchars($si['name'] ?? '', ENT_QUOTES) ?>"
                                                 data-path="<?= htmlspecialchars($si['file_path'] ?? '', ENT_QUOTES) ?>"
                                                 style="cursor:pointer;"><img src="/uploads/<?= htmlspecialchars($si['file_path'] ?? '') ?>" alt="" class="rounded" style="width:24px;height:24px;object-fit:cover;margin-right:8px;"><span class="small"><?= htmlspecialchars($si['name'] ?? '') ?></span></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div id="assetEditIconPreview" class="mt-2 small"></div>
                                <div class="form-text small">系统图标实际会在“图标库”页面同步后复用，避免重复上传。</div>
                            <?php else: ?>
                                <div class="form-text small text-muted">暂无可用图标，可先在“图标库”或“系统设置-系统统一图标库”中添加。</div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 small" id="assetEditIconCurrentPreview"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">备注</label>
                        <textarea name="remark" id="assetEditRemark" class="form-control form-control-sm" rows="2"></textarea>
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

<!-- 转手资产 -->
<div class="modal fade mgmt-modal" id="assetTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">资产转手</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body row g-2 align-items-end">
                    <input type="hidden" name="action" value="transfer">
                    <input type="hidden" name="id" id="assetTransferId">
                    <div class="col-12">
                        <div class="small mb-1">资产：<span id="assetTransferName" class="fw-semibold"></span></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">转手日期</label>
                        <input type="date" name="transfer_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">转手价格</label>
                        <input type="number" name="transfer_price" step="0.01" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 small text-muted">
                        转手后将停止继续累计使用天数和日均成本，并在“已转手”列表中查看历史记录。
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">确认转手</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 图标库搜索和选择功能
    function setupIconSearch(searchId, listId, previewId, inputId, systemInputId) {
        var searchInput = document.getElementById(searchId);
        var listDiv = document.getElementById(listId);
        var previewDiv = document.getElementById(previewId);
        var hiddenInput = document.getElementById(inputId);
        
        if (!searchInput || !listDiv) return;
        
        var allItems = Array.from(listDiv.querySelectorAll('.icon-search-item'));
        var systemInput = systemInputId ? document.getElementById(systemInputId) : null;
        
        searchInput.addEventListener('input', function() {
            var keyword = this.value.toLowerCase().trim();
            allItems.forEach(function(item) {
                var name = (item.getAttribute('data-name') || '').toLowerCase();
                if (keyword === '' || name.includes(keyword)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        allItems.forEach(function(item) {
            item.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var systemId = parseInt(this.getAttribute('data-system-id') || '0', 10) || 0;
                var name = this.getAttribute('data-name');
                var path = this.getAttribute('data-path');
                
                if (hiddenInput) {
                    hiddenInput.value = systemId > 0 ? '' : id;
                }
                if (systemInput) {
                    systemInput.value = systemId > 0 ? systemId : '';
                }
                
                // 取消所有选中状态
                allItems.forEach(function(i) {
                    i.classList.remove('bg-primary', 'text-white');
                });
                // 标记当前选中
                item.classList.add('bg-primary', 'text-white');
                
                // 显示预览
                if (previewDiv) {
                    previewDiv.innerHTML = '<span class="me-2">已选择：</span><img src="/uploads/' + path + '" alt="图标" style="width:32px;height:32px;object-fit:cover;" class="rounded me-1"><span>' + name + '</span>';
                }
            });
        });
    }
    
    setupIconSearch('assetCreateIconSearch', 'assetCreateIconList', 'assetCreateIconPreview', 'assetCreateIconLibraryId', 'assetCreateSystemIconId');
    setupIconSearch('assetEditIconSearch', 'assetEditIconList', 'assetEditIconPreview', 'assetEditIconLibraryId', 'assetEditSystemIconId');

    var assetCreateModal = document.getElementById('assetCreateModal');
    if (assetCreateModal) {
        assetCreateModal.addEventListener('show.bs.modal', function () {
            var createSearchInput = document.getElementById('assetCreateIconSearch');
            if (createSearchInput) createSearchInput.value = '';
            var createPreview = document.getElementById('assetCreateIconPreview');
            if (createPreview) createPreview.innerHTML = '';
            var createIconLib = document.getElementById('assetCreateIconLibraryId');
            if (createIconLib) createIconLib.value = '';
            var createSystemIcon = document.getElementById('assetCreateSystemIconId');
            if (createSystemIcon) createSystemIcon.value = '';
            var createItems = document.querySelectorAll('#assetCreateIconList .icon-search-item');
            createItems.forEach(function(item) {
                item.classList.remove('bg-primary', 'text-white');
            });
        });
    }
    
    // 切换新增资产图标模式
    var iconModeRadios = document.querySelectorAll('#assetCreateModal input[name="icon_mode"]');
    var fileWrap = document.querySelector('#assetCreateModal .asset-icon-input-file');
    var libWrap = document.querySelector('#assetCreateModal .asset-icon-input-library');
    iconModeRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            if (this.value === 'file') {
                fileWrap && fileWrap.classList.remove('d-none');
                libWrap && libWrap.classList.add('d-none');
            } else if (this.value === 'library') {
                libWrap && libWrap.classList.remove('d-none');
                fileWrap && fileWrap.classList.add('d-none');
            } else {
                fileWrap && fileWrap.classList.add('d-none');
                libWrap && libWrap.classList.add('d-none');
            }
        });
    });

    // 编辑资产填充数据
    var editModal = document.getElementById('assetEditModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            document.getElementById('assetEditId').value = btn.getAttribute('data-id') || '';
            document.getElementById('assetEditName').value = btn.getAttribute('data-name') || '';
            document.getElementById('assetEditAcquiredDate').value = btn.getAttribute('data-acquired-date') || '';
            document.getElementById('assetEditValueAmount').value = btn.getAttribute('data-value-amount') || '';
            document.getElementById('assetEditRemark').value = btn.getAttribute('data-remark') || '';

            var iconType = btn.getAttribute('data-icon-type') || '';
            var iconValue = btn.getAttribute('data-icon-value') || '';
            var iconPreview = document.getElementById('assetEditIconCurrentPreview');
            if (iconPreview) {
                if (iconType === 'file' && iconValue) {
                    iconPreview.innerHTML = '<span class="me-2">当前图标：</span><img src="/uploads/' + iconValue + '" alt="资产图标" style="width:40px;height:40px;object-fit:cover;" class="rounded">';
                } else {
                    iconPreview.innerHTML = '<span class="text-muted">当前无图标，将使用占位符。</span>';
                }
            }

            // 重置图标模式为“保持不变”
            var keepRadio = document.getElementById('assetEditIconKeep');
            if (keepRadio) {
                keepRadio.checked = true;
            }
            var fileWrapEdit = document.querySelector('#assetEditModal .asset-edit-icon-input-file');
            var libWrapEdit = document.querySelector('#assetEditModal .asset-edit-icon-input-library');
            if (fileWrapEdit) fileWrapEdit.classList.add('d-none');
            if (libWrapEdit) libWrapEdit.classList.add('d-none');
            
            // 清空搜索框和预览
            var editSearch = document.getElementById('assetEditIconSearch');
            if (editSearch) editSearch.value = '';
            var editPreview = document.getElementById('assetEditIconPreview');
            if (editPreview) editPreview.innerHTML = '';
            var editSystemInput = document.getElementById('assetEditSystemIconId');
            if (editSystemInput) editSystemInput.value = '';
            var editItems = document.querySelectorAll('#assetEditIconList .icon-search-item');
            editItems.forEach(function(item) {
                item.classList.remove('bg-primary', 'text-white');
            });
        });
    }

    // 转手资产填充数据
    var transferModal = document.getElementById('assetTransferModal');
    if (transferModal) {
        transferModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            document.getElementById('assetTransferId').value = btn.getAttribute('data-id') || '';
            document.getElementById('assetTransferName').textContent = btn.getAttribute('data-name') || '';
        });
    }

    // 编辑资产图标模式切换
    var editIconModeRadios = document.querySelectorAll('#assetEditModal input[name="icon_mode"]');
    var editFileWrap = document.querySelector('#assetEditModal .asset-edit-icon-input-file');
    var editLibWrap = document.querySelector('#assetEditModal .asset-edit-icon-input-library');
    editIconModeRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            if (this.value === 'file') {
                editFileWrap && editFileWrap.classList.remove('d-none');
                editLibWrap && editLibWrap.classList.add('d-none');
            } else if (this.value === 'library') {
                editLibWrap && editLibWrap.classList.remove('d-none');
                editFileWrap && editFileWrap.classList.add('d-none');
            } else {
                editFileWrap && editFileWrap.classList.add('d-none');
                editLibWrap && editLibWrap.classList.add('d-none');
            }
        });
        if (r.checked) {
            if (r.value === 'file') {
                editFileWrap && editFileWrap.classList.remove('d-none');
                editLibWrap && editLibWrap.classList.add('d-none');
            } else if (r.value === 'library') {
                editLibWrap && editLibWrap.classList.remove('d-none');
                editFileWrap && editFileWrap.classList.add('d-none');
            } else {
                editFileWrap && editFileWrap.classList.add('d-none');
                editLibWrap && editLibWrap.classList.add('d-none');
            }
        }
    });
});
</script>
