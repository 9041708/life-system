<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">管理您的各类订阅和买断记录。</div>
    <button type="button" class="btn btn-sm btn-glass" data-bs-toggle="modal" data-bs-target="#subscriptionCreateModal">+ 新增记录</button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form class="card glass-card mb-3 p-3" method="get">
    <div class="row g-2 align-items-end">
    <input type="hidden" name="route" value="subscriptions">
    <div class="col-12 col-md-4 col-lg-3">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="搜索平台名称" value="<?= htmlspecialchars($keyword ?? '') ?>">
    </div>
    <div class="col-12 col-md-4 col-lg-3 small text-muted">
        提示:已关闭的订阅记录会在到期 30 天后自动清除;未关闭的记录会一直保留。
    </div>
    <div class="col-12 col-md-4 col-lg-3 d-flex justify-content-end">
        <button type="submit" class="btn btn-sm btn-glass">🔍 搜索</button>
    </div>
</form>
    </div>
</form>

<div class="mb-3 border-bottom">
    <ul class="nav subscriptions-nav">
        <li class="nav-item">
            <a class="nav-link <?= ($tab ?? 'subscription') === 'subscription' ? 'active' : '' ?>" href="/public/index.php?route=subscriptions&amp;tab=subscription&amp;q=<?= urlencode($keyword ?? '') ?>">订阅</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($tab ?? 'subscription') === 'lifetime' ? 'active' : '' ?>" href="/public/index.php?route=subscriptions&amp;tab=lifetime&amp;q=<?= urlencode($keyword ?? '') ?>">买断</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($tab ?? 'subscription') === 'expired' ? 'active' : '' ?>" href="/public/index.php?route=subscriptions&amp;tab=expired&amp;q=<?= urlencode($keyword ?? '') ?>">已到期</a>
        </li>
    </ul>
</div>
<?php
$currentTab = $tab ?? 'subscription';
$visible = [];

foreach ($subscriptions as $s) {
    $type = $s['type'] ?? 'subscription';
    $isSub = $type === 'subscription';
    $daysLeft = $s['days_left'];

    // 按选项卡过滤
    if ($currentTab === 'subscription') {
        if (!$isSub) {
            continue;
        }
        if ($daysLeft !== null && $daysLeft < 0) {
            continue;
        }
    } elseif ($currentTab === 'lifetime') {
        if ($isSub) {
            continue;
        }
    } elseif ($currentTab === 'expired') {
        if (!$isSub) {
            continue;
        }
        if (!($daysLeft !== null && $daysLeft < 0)) {
            continue;
        }
    }

    $visible[] = $s;
}

$hasVisible = !empty($visible);

if (!function_exists('render_subscription_card')) {
    function render_subscription_card(array $s, bool $isSub): void {
        $daysLeft = $s['days_left'];
        $badgeClass = 'bg-secondary';
        $badgeText = '买断';
        if ($isSub) {
            if ($daysLeft !== null && $daysLeft < 0) {
                $badgeClass = 'bg-danger';
                $badgeText = '已到期';
            } elseif ($daysLeft !== null && $daysLeft <= 3) {
                $badgeClass = 'bg-danger';
                $badgeText = '即将到期';
            } elseif ($daysLeft !== null && $daysLeft <= 7) {
                $badgeClass = 'bg-warning text-dark';
                $badgeText = '一周内到期';
            } else {
                $badgeClass = 'bg-primary';
                $badgeText = '订阅中';
            }
        }
        ?>
        <div class="col">
            <div class="card glass-card h-100">
                <div class="card-body d-flex">
                    <div class="me-3">
                        <?php if (!empty($s['icon_type']) && !empty($s['icon_value']) && $s['icon_type'] === 'file'): ?>
                            <img src="/uploads/<?= htmlspecialchars($s['icon_value']) ?>" alt="图标" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                        <?php else: ?>
                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                <span class="text-muted small">图</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($s['platform'] ?? '') ?></div>
                                <div class="small text-muted">
                                    类型:<?= $isSub ? '订阅' : '买断' ?>
                                    <?php if ($isSub): ?>
                                        · <?= !empty($s['auto_renew']) ? '自动续费' : '非自动续费' ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end small">
                                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                            </div>
                        </div>
                        <div class="small text-muted mb-1">
                            金额:<span class="fw-semibold"><?= number_format((float)($s['price'] ?? 0), 2) ?></span>
                            <?php if ($isSub && !empty($s['period'])): ?>
                                (按 <?= $s['period'] === 'day' ? '日' : ($s['period'] === 'week' ? '周' : ($s['period'] === 'month' ? '月' : ($s['period'] === 'quarter' ? '季度' : '年'))) ?> 续费)
                            <?php endif; ?>
                        </div>
                        <?php if ($isSub): ?>
                            <div class="small text-muted mb-1">
                                到期日:<?= htmlspecialchars($s['expire_date'] ?? '') ?>
                                <?php if ($daysLeft !== null): ?>
                                    · 剩余:
                                    <?php if ($daysLeft < 0): ?>
                                        已超期 <?= abs($daysLeft) ?> 天
                                    <?php elseif ($daysLeft === 0): ?>
                                        今天到期
                                    <?php else: ?>
                                        <?= $daysLeft ?> 天
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($s['remark'])): ?>
                            <div class="small text-muted mb-1" style="white-space:pre-wrap;">备注:<?= htmlspecialchars($s['remark']) ?></div>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#subscriptionEditModal"
                                    data-id="<?= (int)($s['id'] ?? 0) ?>"
                                    data-platform="<?= htmlspecialchars($s['platform'] ?? '', ENT_QUOTES) ?>"
                                    data-type="<?= htmlspecialchars($s['type'] ?? '', ENT_QUOTES) ?>"
                                    data-price="<?= htmlspecialchars($s['price'] ?? '', ENT_QUOTES) ?>"
                                    data-expire="<?= htmlspecialchars($s['expire_date'] ?? '') ?>"
                                    data-auto-renew="<?= !empty($s['auto_renew']) ? '1' : '0' ?>"
                                    data-period="<?= htmlspecialchars($s['period'] ?? '', ENT_QUOTES) ?>"
                                    data-icon-type="<?= htmlspecialchars($s['icon_type'] ?? '', ENT_QUOTES) ?>"
                                    data-icon-value="<?= htmlspecialchars($s['icon_value'] ?? '', ENT_QUOTES) ?>"
                                    data-remark="<?= htmlspecialchars($s['remark'] ?? '', ENT_QUOTES) ?>">
                                编辑
                            </button>
                            <form method="post" class="d-inline" onsubmit="return confirm('确定关闭该订阅记录吗?关闭后将从列表中移除,并在到期 30 天后自动清除数据。');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#subscriptionRenewModal"
                                    data-id="<?= (int)($s['id'] ?? 0) ?>"
                                    data-platform="<?= htmlspecialchars($s['platform'] ?? '', ENT_QUOTES) ?>">
                                我已续费
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
?>

<?php if (!$hasVisible): ?>
    <div class="row">
        <div class="col">
            <div class="text-muted small text-center py-4">
                <?php if ($currentTab === 'subscription'): ?>
                    暂无订阅记录,可点击右上角"新增记录"开始记录。
                <?php elseif ($currentTab === 'lifetime'): ?>
                    暂无买断记录,可点击右上角"新增记录"开始记录。
                <?php else: ?>
                    暂无已到期的订阅记录。
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php if ($hasVisible): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-3">
            <?php foreach ($visible as $s): ?>
                <?php $type = $s['type'] ?? 'subscription'; $isSub = $type === 'subscription'; ?>
                <?php render_subscription_card($s, $isSub); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- 新增订阅记录 -->
<div class="modal fade mgmt-modal" id="subscriptionCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增订阅记录</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body row g-2 align-items-end">
                    <input type="hidden" name="action" value="create">
                    <div class="col-12">
                        <label class="form-label small">平台名称</label>
                        <input type="text" name="platform" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">类型</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="type" id="subCreateTypeSub" value="subscription" checked>
                            <label class="btn btn-outline-secondary" for="subCreateTypeSub">订阅</label>
                            <input type="radio" class="btn-check" name="type" id="subCreateTypeLife" value="lifetime">
                            <label class="btn btn-outline-secondary" for="subCreateTypeLife">买断</label>
                        </div>
                    </div>
                    <div class="col-12 sub-create-subscription-fields">
                        <div class="row g-2 align-items-end">
                            <div class="col-6">
                                <label class="form-label small">到期日期</label>
                                <input type="date" name="expire_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">续费方式</label>
                                <select name="period" class="form-select form-select-sm">
                                    <option value="day">按日</option>
                                    <option value="week">按周</option>
                                    <option value="month" selected>按月</option>
                                    <option value="quarter">按季度</option>
                                    <option value="year">按年</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check small mt-1">
                                    <input class="form-check-input" type="checkbox" name="auto_renew" id="subCreateAutoRenew" checked>
                                    <label class="form-check-label" for="subCreateAutoRenew">自动续费</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">金额</label>
                        <input type="number" name="price" step="0.01" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">图标</label>
                        <div class="form-text small mb-1">可上传或从图标库/系统统一图标库中选择(可选)。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="subCreateIconNone" value="none" checked>
                            <label class="btn btn-outline-secondary" for="subCreateIconNone">默认图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="subCreateIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="subCreateIconFile">上传图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="subCreateIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="subCreateIconLib">从图标库选择</label>
                        </div>
                        <div class="sub-icon-input-file d-none mb-2">
                            <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="submit_to_system" id="subCreateSubmitToSystem" value="1">
                                <label class="form-check-label small" for="subCreateSubmitToSystem">提交到公共图标库(管理员审核后可供全体用户同步使用)</label>
                            </div>
                        </div>
                        <div class="sub-icon-input-library d-none mb-2">
                            <?php if (!empty($iconLibrary) || !empty($systemIcons)): ?>
                                <input type="hidden" name="icon_library_id" id="subCreateIconLibraryId" value="">
                                <input type="hidden" name="system_icon_id" id="subCreateSystemIconId" value="">
                                <input type="text" class="form-control form-control-sm mb-2" id="subCreateIconSearch" placeholder="搜索图标名称...">
                                <div id="subCreateIconList" class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
                                    <?php if (!empty($iconLibrary)): ?>
                                        <div class="small text-muted mb-1">我的图标库</div>
                                        <?php foreach ($iconLibrary as $lib): ?>
                                            <div class="icon-search-item d-flex align-items-center p-1" 
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
                                            <div class="icon-search-item d-flex align-items-center p-1" 
                                                 data-id="0"
                                                 data-system-id="<?= (int)($si['id'] ?? 0) ?>"
                                                 data-name="<?= htmlspecialchars($si['name'] ?? '', ENT_QUOTES) ?>"
                                                 data-path="<?= htmlspecialchars($si['file_path'] ?? '', ENT_QUOTES) ?>"
                                                 style="cursor:pointer;"><img src="/uploads/<?= htmlspecialchars($si['file_path'] ?? '') ?>" alt="" class="rounded" style="width:24px;height:24px;object-fit:cover;margin-right:8px;"><span class="small"><?= htmlspecialchars($si['name'] ?? '') ?></span></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div id="subCreateIconPreview" class="mt-2 small"></div>
                                <div class="form-text small">系统图标实际会在"图标库"页面同步后复用,避免重复上传。</div>
                            <?php else: ?>
                                <div class="form-text small text-muted">暂无可用图标,可先在"图标库"或"系统设置-系统统一图标库"中添加。</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">备注</label>
                        <textarea name="remark" class="form-control form-control-sm" rows="2" placeholder="可选,例如账号信息、购买渠道等"></textarea>
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

<!-- 编辑订阅记录 -->
<div class="modal fade mgmt-modal" id="subscriptionEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑订阅记录</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body row g-2 align-items-end">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="subEditId">
                    <div class="col-12">
                        <label class="form-label small">平台名称</label>
                        <input type="text" name="platform" id="subEditPlatform" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">类型</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="type" id="subEditTypeSub" value="subscription">
                            <label class="btn btn-outline-secondary" for="subEditTypeSub">订阅</label>
                            <input type="radio" class="btn-check" name="type" id="subEditTypeLife" value="lifetime">
                            <label class="btn btn-outline-secondary" for="subEditTypeLife">买断</label>
                        </div>
                    </div>
                    <div class="col-12 sub-edit-subscription-fields">
                        <div class="row g-2 align-items-end">
                            <div class="col-6">
                                <label class="form-label small">到期日期</label>
                                <input type="date" name="expire_date" id="subEditExpire" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">续费方式</label>
                                <select name="period" id="subEditPeriod" class="form-select form-select-sm">
                                    <option value="day">按日</option>
                                    <option value="week">按周</option>
                                    <option value="month">按月</option>
                                    <option value="quarter">按季度</option>
                                    <option value="year">按年</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check small mt-1">
                                    <input class="form-check-input" type="checkbox" name="auto_renew" id="subEditAutoRenew">
                                    <label class="form-check-label" for="subEditAutoRenew">自动续费</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">金额</label>
                        <input type="number" name="price" id="subEditPrice" step="0.01" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">图标</label>
                        <div class="form-text small mb-1">可上传或从图标库/系统统一图标库中选择(可选)。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="subEditIconKeep" value="none" checked>
                            <label class="btn btn-outline-secondary" for="subEditIconKeep">保持不变</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="subEditIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="subEditIconFile">上传图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="subEditIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="subEditIconLib">从图标库选择</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="subEditIconClear" value="clear">
                            <label class="btn btn-outline-secondary" for="subEditIconClear">清除图标</label>
                        </div>
                        <div id="subEditIconCurrentPreview" class="mb-2"></div>
                        <div class="sub-edit-icon-input-file d-none mb-2">
                            <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="submit_to_system" id="subEditSubmitToSystem" value="1">
                                <label class="form-check-label small" for="subEditSubmitToSystem">提交到公共图标库(管理员审核后可供全体用户同步使用)</label>
                            </div>
                        </div>
                        <div class="sub-edit-icon-input-library d-none mb-2">
                            <?php if (!empty($iconLibrary) || !empty($systemIcons)): ?>
                                <input type="hidden" name="icon_library_id" id="subEditIconLibraryId" value="">
                                <input type="hidden" name="system_icon_id" id="subEditSystemIconId" value="">
                                <input type="text" class="form-control form-control-sm mb-2" id="subEditIconSearch" placeholder="搜索图标名称...">
                                <div id="subEditIconList" class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
                                    <?php if (!empty($iconLibrary)): ?>
                                        <div class="small text-muted mb-1">我的图标库</div>
                                        <?php foreach ($iconLibrary as $lib): ?>
                                            <div class="icon-search-item d-flex align-items-center p-1" 
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
                                            <div class="icon-search-item d-flex align-items-center p-1" 
                                                 data-id="0"
                                                 data-system-id="<?= (int)($si['id'] ?? 0) ?>"
                                                 data-name="<?= htmlspecialchars($si['name'] ?? '', ENT_QUOTES) ?>"
                                                 data-path="<?= htmlspecialchars($si['file_path'] ?? '', ENT_QUOTES) ?>"
                                                 style="cursor:pointer;"><img src="/uploads/<?= htmlspecialchars($si['file_path'] ?? '') ?>" alt="" class="rounded" style="width:24px;height:24px;object-fit:cover;margin-right:8px;"><span class="small"><?= htmlspecialchars($si['name'] ?? '') ?></span></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div id="subEditIconPreview" class="mt-2 small"></div>
                                <div class="form-text small">系统图标实际会在"图标库"页面同步后复用,避免重复上传。</div>
                            <?php else: ?>
                                <div class="form-text small text-muted">暂无可用图标,可先在"图标库"或"系统设置-系统统一图标库"中添加。</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">备注</label>
                        <textarea name="remark" id="subEditRemark" class="form-control form-control-sm" rows="2"></textarea>
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

<!-- 续费 -->
<div class="modal fade mgmt-modal" id="subscriptionRenewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">我已续费</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body row g-2 align-items-end">
                    <input type="hidden" name="action" value="renew">
                    <input type="hidden" name="id" id="subRenewId">
                    <div class="col-12">
                        <div class="small mb-1">平台:<span id="subRenewPlatform" class="fw-semibold"></span></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">类型</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="type" id="subRenewTypeSub" value="subscription" checked>
                            <label class="btn btn-outline-secondary" for="subRenewTypeSub">订阅</label>
                            <input type="radio" class="btn-check" name="type" id="subRenewTypeLife" value="lifetime">
                            <label class="btn btn-outline-secondary" for="subRenewTypeLife">买断</label>
                        </div>
                    </div>
                    <div class="col-12 sub-renew-subscription-fields">
                        <div class="row g-2 align-items-end">
                            <div class="col-6">
                                <label class="form-label small">新到期日期</label>
                                <input type="date" name="expire_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">续费方式</label>
                                <select name="period" class="form-select form-select-sm">
                                    <option value="day">按日</option>
                                    <option value="week">按周</option>
                                    <option value="month" selected>按月</option>
                                    <option value="quarter">按季度</option>
                                    <option value="year">按年</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check small mt-1">
                                    <input class="form-check-input" type="checkbox" name="auto_renew" id="subRenewAutoRenew" checked>
                                    <label class="form-check-label" for="subRenewAutoRenew">自动续费</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">本次支付金额</label>
                        <input type="number" name="price" step="0.01" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 small text-muted">
                        续费会更新当前记录的类型 / 金额 / 到期日期等信息,便于后续继续提醒。
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">保存续费</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function toggleSubCreateFields() {
        var typeSub = document.getElementById('subCreateTypeSub');
        var fields = document.querySelector('.sub-create-subscription-fields');
        if (!typeSub || !fields) return;
        if (typeSub.checked) {
            fields.classList.remove('d-none');
        } else {
            fields.classList.add('d-none');
        }
    }
    var typeCreateRadios = document.querySelectorAll('#subscriptionCreateModal input[name="type"]');
    typeCreateRadios.forEach(function (r) {
        r.addEventListener('change', toggleSubCreateFields);
    });
    toggleSubCreateFields();

    function setupIconSearch(searchId, listId, previewId, inputId, systemInputId) {
        var searchInput = document.getElementById(searchId);
        var listDiv = document.getElementById(listId);
        var previewDiv = document.getElementById(previewId);
        var hiddenInput = document.getElementById(inputId);
        var systemInput = systemInputId ? document.getElementById(systemInputId) : null;
        if (!searchInput || !listDiv) return;

        var allItems = Array.from(listDiv.querySelectorAll('.icon-search-item'));
        searchInput.addEventListener('input', function () {
            var keyword = this.value.toLowerCase().trim();
            allItems.forEach(function (item) {
                var name = (item.getAttribute('data-name') || '').toLowerCase();
                if (keyword === '' || name.includes(keyword)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        allItems.forEach(function (item) {
            item.addEventListener('click', function () {
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

                allItems.forEach(function (i) {
                    i.classList.remove('bg-primary', 'text-white');
                });
                item.classList.add('bg-primary', 'text-white');

                if (previewDiv) {
                    previewDiv.innerHTML = '<span class="me-2">已选择：</span><img src="/uploads/' + path + '" alt="图标" style="width:32px;height:32px;object-fit:cover;" class="rounded me-1"><span>' + name + '</span>';
                }
            });
        });
    }

    setupIconSearch('subCreateIconSearch', 'subCreateIconList', 'subCreateIconPreview', 'subCreateIconLibraryId', 'subCreateSystemIconId');
    setupIconSearch('subEditIconSearch', 'subEditIconList', 'subEditIconPreview', 'subEditIconLibraryId', 'subEditSystemIconId');

    var createModal = document.getElementById('subscriptionCreateModal');
    if (createModal) {
        createModal.addEventListener('show.bs.modal', function () {
            var createSearchInput = document.getElementById('subCreateIconSearch');
            if (createSearchInput) createSearchInput.value = '';
            var createPreview = document.getElementById('subCreateIconPreview');
            if (createPreview) createPreview.innerHTML = '';
            var createIconLib = document.getElementById('subCreateIconLibraryId');
            if (createIconLib) createIconLib.value = '';
            var createSystemIcon = document.getElementById('subCreateSystemIconId');
            if (createSystemIcon) createSystemIcon.value = '';
            var createItems = document.querySelectorAll('#subCreateIconList .icon-search-item');
            createItems.forEach(function(item) {
                item.classList.remove('bg-primary', 'text-white');
            });
        });
    }

    // 图标模式切换(新增)
    var subIconRadios = document.querySelectorAll('#subscriptionCreateModal input[name="icon_mode"]');
    var subFileWrap = document.querySelector('#subscriptionCreateModal .sub-icon-input-file');
    var subLibWrap = document.querySelector('#subscriptionCreateModal .sub-icon-input-library');
    subIconRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            if (this.value === 'file') {
                subFileWrap && subFileWrap.classList.remove('d-none');
                subLibWrap && subLibWrap.classList.add('d-none');
            } else if (this.value === 'library') {
                subLibWrap && subLibWrap.classList.remove('d-none');
                subFileWrap && subFileWrap.classList.add('d-none');
            } else {
                subFileWrap && subFileWrap.classList.add('d-none');
                subLibWrap && subLibWrap.classList.add('d-none');
            }
        });
    });

    // 编辑订阅填充数据
    var editModal = document.getElementById('subscriptionEditModal');
    if (editModal) {
        // 图标模式切换(编辑)
        var editIconRadios = document.querySelectorAll('#subscriptionEditModal input[name="icon_mode"]');
        var editFileWrap = document.querySelector('#subscriptionEditModal .sub-edit-icon-input-file');
        var editLibWrap = document.querySelector('#subscriptionEditModal .sub-edit-icon-input-library');
        var editSubmitCheck = document.getElementById('subEditSubmitToSystem');
        function toggleSubEditIconWrap(val) {
            if (val === 'file') {
                editFileWrap && editFileWrap.classList.remove('d-none');
                editLibWrap && editLibWrap.classList.add('d-none');
            } else if (val === 'library') {
                editLibWrap && editLibWrap.classList.remove('d-none');
                editFileWrap && editFileWrap.classList.add('d-none');
            } else {
                editFileWrap && editFileWrap.classList.add('d-none');
                editLibWrap && editLibWrap.classList.add('d-none');
            }
        }
        editIconRadios.forEach(function (r) {
            r.addEventListener('change', function () {
                toggleSubEditIconWrap(this.value);
            });
        });

        editModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            document.getElementById('subEditId').value = btn.getAttribute('data-id') || '';
            document.getElementById('subEditPlatform').value = btn.getAttribute('data-platform') || '';
            var type = btn.getAttribute('data-type') || 'subscription';
            document.getElementById('subEditTypeSub').checked = type === 'subscription';
            document.getElementById('subEditTypeLife').checked = type === 'lifetime';
            document.getElementById('subEditExpire').value = btn.getAttribute('data-expire') || '';
            document.getElementById('subEditPrice').value = btn.getAttribute('data-price') || '';
            document.getElementById('subEditRemark').value = btn.getAttribute('data-remark') || '';
            var period = btn.getAttribute('data-period') || '';
            var periodSelect = document.getElementById('subEditPeriod');
            if (periodSelect) {
                periodSelect.value = period || 'month';
            }
            var auto = btn.getAttribute('data-auto-renew') === '1';
            document.getElementById('subEditAutoRenew').checked = auto;

            var fields = document.querySelector('.sub-edit-subscription-fields');
            if (type === 'subscription') {
                fields && fields.classList.remove('d-none');
            } else {
                fields && fields.classList.add('d-none');
            }

            // 图标:默认保持不变,并展示当前图标预览
            var keepRadio = document.getElementById('subEditIconKeep');
            if (keepRadio) keepRadio.checked = true;
            toggleSubEditIconWrap('none');
            if (editSubmitCheck) editSubmitCheck.checked = false;

            var iconType = btn.getAttribute('data-icon-type') || '';
            var iconValue = btn.getAttribute('data-icon-value') || '';
            var preview = document.getElementById('subEditIconCurrentPreview');
            if (preview) {
                preview.textContent = '';
                var label = document.createElement('span');
                label.className = 'text-muted';
                if (iconType === 'file' && iconValue) {
                    label.textContent = '当前图标:';
                    var img = document.createElement('img');
                    img.alt = '图标';
                    img.className = 'rounded ms-1';
                    img.style.width = '24px';
                    img.style.height = '24px';
                    img.style.objectFit = 'cover';
                    img.src = '/uploads/' + iconValue;
                    preview.appendChild(label);
                    preview.appendChild(img);
                } else {
                    label.textContent = '当前图标:默认';
                    preview.appendChild(label);
                }
            }

            var editSearchInput = document.getElementById('subEditIconSearch');
            if (editSearchInput) editSearchInput.value = '';
            var editPreview = document.getElementById('subEditIconPreview');
            if (editPreview) editPreview.innerHTML = '';
            var editIconLib = document.getElementById('subEditIconLibraryId');
            if (editIconLib) editIconLib.value = '';
            var editSystemIcon = document.getElementById('subEditSystemIconId');
            if (editSystemIcon) editSystemIcon.value = '';
            var editItems = document.querySelectorAll('#subEditIconList .icon-search-item');
            editItems.forEach(function(item) {
                item.classList.remove('bg-primary', 'text-white');
            });
        });
    }

    // 续费弹窗填充
    var renewModal = document.getElementById('subscriptionRenewModal');
    if (renewModal) {
        renewModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            document.getElementById('subRenewId').value = btn.getAttribute('data-id') || '';
            document.getElementById('subRenewPlatform').textContent = btn.getAttribute('data-platform') || '';
            document.getElementById('subRenewTypeSub').checked = true;
            document.getElementById('subRenewTypeLife').checked = false;
            var fields = document.querySelector('.sub-renew-subscription-fields');
            fields && fields.classList.remove('d-none');
        });
    }

    // 续费类型切换
    function toggleRenewFields() {
        var typeSub = document.getElementById('subRenewTypeSub');
        var fields = document.querySelector('.sub-renew-subscription-fields');
        if (!typeSub || !fields) return;
        if (typeSub.checked) {
            fields.classList.remove('d-none');
        } else {
            fields.classList.add('d-none');
        }
    }
    var renewTypeRadios = document.querySelectorAll('#subscriptionRenewModal input[name="type"]');
    renewTypeRadios.forEach(function (r) {
        r.addEventListener('change', toggleRenewFields);
    });
    toggleRenewFields();
});
</script>
