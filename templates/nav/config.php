<?php
/** @var array $groups */
/** @var array $bookmarks */
?>
<style>
.nv-config .card { border-radius: 0.75rem; }
.nv-item-row {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border: 1px solid rgba(15,23,42,0.06);
    border-radius: 0.5rem; margin-bottom: 8px;
    font-size: 0.85rem;
    background: rgba(255,255,255,0.4);
}
body.theme-dark .nv-item-row {
    background: rgba(30,41,59,0.3);
    border-color: rgba(148,163,184,0.1);
}
.nv-item-name { flex: 1; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.nv-item-meta { font-size: 0.75rem; color: #6b7280; }
body.theme-dark .nv-item-meta { color: #9ca3ab; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">⚙️ 导航配置</h5>
    <a href="/public/index.php?route=nav-my" class="btn btn-sm btn-outline-secondary">← 返回导航</a>
</div>

<div class="nv-config">
<!-- ===== 分组管理 ===== -->
<div class="card glass-card mb-3">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">📁 分组管理</h6>
            <button class="btn btn-sm btn-glass" onclick="openGroupModal()">+ 添加分组</button>
        </div>
        <?php if (empty($groups)): ?>
            <div class="text-muted small text-center py-3">暂无分组，请添加</div>
        <?php else: ?>
            <?php foreach ($groups as $g): ?>
            <div class="nv-item-row">
                <?php if (!empty($g['icon_type']) && !empty($g['icon_value'])): ?>
                    <?php if ($g['icon_type'] === 'file'): ?>
                        <img src="/uploads/<?= htmlspecialchars($g['icon_value']) ?>" style="width:22px;height:22px;object-fit:cover;border-radius:4px;">
                    <?php elseif ($g['icon_type'] === 'svg'): ?>
                        <img src="data:image/svg+xml;base64,<?= base64_encode($g['icon_value']) ?>" style="width:22px;height:22px;border-radius:4px;" alt="">
                    <?php elseif ($g['icon_type'] === 'url'): ?>
                        <img src="<?= htmlspecialchars($g['icon_value']) ?>" style="width:22px;height:22px;object-fit:contain;border-radius:4px;" alt="" onerror="this.style.display='none'">
                    <?php endif; ?>
                <?php endif; ?>
                <span class="nv-item-name"><?= htmlspecialchars($g['name']) ?></span>
                <span class="nv-item-meta">排序: <?= (int)$g['sort_order'] ?></span>
                <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openGroupModal(<?= (int)$g['id'] ?>, '<?= htmlspecialchars(addslashes($g['name'])) ?>', <?= (int)$g['sort_order'] ?>, '<?= htmlspecialchars(addslashes((string)($g['icon_type'] ?? ''))) ?>', '<?= htmlspecialchars(addslashes((string)($g['icon_value'] ?? ''))) ?>')">编辑</button>
                <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteGroup(<?= (int)$g['id'] ?>, '<?= htmlspecialchars(addslashes($g['name'])) ?>')">删除</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ===== 标签管理 ===== -->
<div class="card glass-card">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">🏷️ 导航标签</h6>
            <button class="btn btn-sm btn-glass" onclick="openBookmarkModal()">+ 添加标签</button>
        </div>
        <div class="mb-3">
            <select id="filterGroup" class="form-select form-select-sm" style="max-width:220px;" onchange="filterBookmarks()">
                <option value="">全部分组</option>
                <?php foreach ($groups as $g): ?>
                <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="bookmarkList">
        <?php if (empty($bookmarks)): ?>
            <div class="text-muted small text-center py-3">暂无标签</div>
        <?php else: ?>
            <?php foreach ($bookmarks as $b): ?>
            <div class="nv-item-row" data-group="<?= (int)$b['group_id'] ?>">
                <?php if (!empty($b['screenshot'])): ?>
                    <img src="/uploads/<?= htmlspecialchars($b['screenshot']) ?>" style="width:32px;height:24px;object-fit:cover;border-radius:3px;flex-shrink:0;">
                <?php endif; ?>
                <?php if (!empty($b['icon_type']) && !empty($b['icon_value'])): ?>
                    <?php if ($b['icon_type'] === 'file'): ?>
                        <img src="/uploads/<?= htmlspecialchars($b['icon_value']) ?>" style="width:20px;height:20px;object-fit:cover;border-radius:3px;flex-shrink:0;">
                    <?php elseif ($b['icon_type'] === 'svg'): ?>
                        <img src="data:image/svg+xml;base64,<?= base64_encode($b['icon_value']) ?>" style="width:20px;height:20px;border-radius:3px;flex-shrink:0;" alt="">
                    <?php elseif ($b['icon_type'] === 'url'): ?>
                        <img src="<?= htmlspecialchars($b['icon_value']) ?>" style="width:20px;height:20px;object-fit:contain;border-radius:3px;flex-shrink:0;" alt="" onerror="this.style.display='none'">
                    <?php endif; ?>
                <?php endif; ?>
                <span class="nv-item-name"><?= htmlspecialchars($b['name']) ?></span>
                <span class="nv-item-meta text-truncate" style="max-width:180px;"><?= htmlspecialchars($b['url']) ?></span>
                <span class="nv-item-meta"><?= htmlspecialchars($b['group_name'] ?? '') ?></span>
                <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openBookmarkModal(<?= (int)$b['id'] ?>)">编辑</button>
                <?php if ($isAdmin): ?>
                <button class="btn btn-sm btn-outline-info py-0 px-2" onclick="openPushModal(<?= (int)$b['id'] ?>, '<?= htmlspecialchars(addslashes($b['name'])) ?>')">推送</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteBookmark(<?= (int)$b['id'] ?>, '<?= htmlspecialchars(addslashes($b['name'])) ?>')">删除</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</div>

</div>

<!-- ===== 分组弹窗 ===== -->
<div class="modal fade mgmt-modal" id="groupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title" id="groupModalTitle">添加分组</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="gid">
                <div class="mb-3"><label class="form-label small fw-semibold">分组名称</label><input type="text" id="gname" class="form-control form-control-sm" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">排序</label><input type="number" id="gsort" class="form-control form-control-sm" value="0"></div>
                <div class="mb-2"><label class="form-label small fw-semibold">图标（可选）</label>
                    <div class="btn-group btn-group-sm mb-2">
                        <input type="radio" class="btn-check" name="gicon_mode" id="giconNone" value="none" checked><label class="btn btn-outline-secondary" for="giconNone">无图标</label>
                        <input type="radio" class="btn-check" name="gicon_mode" id="giconFile" value="file"><label class="btn btn-outline-secondary" for="giconFile">上传</label>
                        <input type="radio" class="btn-check" name="gicon_mode" id="giconSvg" value="svg"><label class="btn btn-outline-secondary" for="giconSvg">SVG</label>
                        <input type="radio" class="btn-check" name="gicon_mode" id="giconUrl" value="url"><label class="btn btn-outline-secondary" for="giconUrl">链接</label>
                    </div>
                    <div id="giconFileWrap" class="d-none mb-2"><input type="file" accept="image/*" class="form-control form-control-sm" id="giconFileInput"></div>
                    <div id="giconSvgWrap" class="d-none mb-2"><textarea id="giconSvgInput" class="form-control form-control-sm" rows="4" placeholder="粘贴 SVG 代码"></textarea></div>
                    <div id="giconUrlWrap" class="d-none mb-2"><input type="text" id="giconUrlInput" class="form-control form-control-sm" placeholder="https://example.com/icon.png"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-sm btn-primary" onclick="saveGroup()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 标签弹窗 ===== -->
<div class="modal fade mgmt-modal" id="bookmarkModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title" id="bookmarkModalTitle">添加标签</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="bid">
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small fw-semibold">标签名称 <span class="text-danger">*</span></label><input type="text" id="bname" class="form-control form-control-sm" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">所属分组 <span class="text-danger">*</span></label>
                        <select id="bgroup" class="form-select form-select-sm"><option value="">请选择分组</option>
                            <?php foreach ($groups as $g): ?><option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">主网址 <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="burl" class="form-control form-control-sm" placeholder="https://" onblur="suggestFetchInfo()">
                            <button class="btn btn-outline-secondary" type="button" onclick="fetchPageInfo()" id="btnFetchInfo">🔍 获取信息</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold d-flex justify-content-between">
                            <span>备用链接</span>
                            <button type="button" class="btn btn-link btn-sm py-0" onclick="addAltUrl()">+ 添加</button>
                        </label>
                        <div id="altUrlsContainer"></div>
                        <textarea id="altUrlsRaw" class="d-none" name="alt_urls"></textarea>
                        <div class="form-text small">每个备用链接占一行，格式：<code>标签名称|网址</code> 或直接填网址</div>
                    </div>
                    <div class="col-12"><label class="form-label small fw-semibold">介绍</label><textarea id="bdesc" class="form-control form-control-sm" rows="2"></textarea></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">排序</label><input type="number" id="bsort" class="form-control form-control-sm" value="0"></div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">&nbsp;</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bshowHome">
                            <label class="form-check-label small" for="bshowHome">在首页显示</label>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">标签图标（可选）</label>
                        <div class="btn-group btn-group-sm mb-2" id="biconModeGroup">
                            <input type="radio" class="btn-check" name="bicon_mode" id="biconNone" value="none" checked><label class="btn btn-outline-secondary" for="biconNone">无图标</label>
                            <input type="radio" class="btn-check" name="bicon_mode" id="biconFile" value="file"><label class="btn btn-outline-secondary" for="biconFile">上传</label>
                            <input type="radio" class="btn-check" name="bicon_mode" id="biconSvg" value="svg"><label class="btn btn-outline-secondary" for="biconSvg">SVG</label>
                            <input type="radio" class="btn-check" name="bicon_mode" id="biconUrl" value="url"><label class="btn btn-outline-secondary" for="biconUrl">链接</label>
                        </div>
                        <div id="biconFileWrap" class="d-none mb-2"><input type="file" accept="image/*,.svg" class="form-control form-control-sm" id="biconFileInput"></div>
                        <div id="biconSvgWrap" class="d-none mb-2"><textarea id="biconSvgInput" class="form-control form-control-sm" rows="3" placeholder="粘贴 SVG 代码"></textarea></div>
                        <div id="biconUrlWrap" class="d-none mb-2"><input type="text" id="biconUrlInput" class="form-control form-control-sm" placeholder="https://example.com/icon.png"></div>
                        <div id="biconPreview" class="small mt-1"></div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">页面截图</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="bscreenshot" class="form-control form-control-sm" placeholder="自动获取或手动上传">
                            <button class="btn btn-outline-secondary" type="button" onclick="fetchScreenshot()" id="btnFetchSS">自动获取</button>
                        </div>
                        <div class="mt-1"><input type="file" accept="image/*" class="form-control form-control-sm" id="bscreenshotFile"></div>
                        <div class="mt-2 p-3 text-center border rounded-3" style="border-style:dashed!important;border-color:rgba(15,23,42,0.12);cursor:pointer;background:rgba(255,255,255,0.3);transition:border-color 0.2s;"
                             id="pasteZone"
                             onclick="document.getElementById('bscreenshotFile').click()"
                             onmouseenter="this.style.borderColor='#3b82f6'"
                             onmouseleave="this.style.borderColor='rgba(15,23,42,0.12)'">
                            <span class="text-muted small">📋 点击此处或 Ctrl+V 粘贴截图</span>
                        </div>
                        <div id="bssPreview" class="mt-1 small"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-sm btn-primary" onclick="saveBookmark()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 推送弹窗 ===== -->
<?php if ($isAdmin): ?>
<div class="modal fade mgmt-modal" id="pushModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">推送标签</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="pushBookmarkId">
                <div id="pushBookmarkName" class="fw-semibold mb-3"></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">推送目标</label>
                    <select id="pushTarget" class="form-select form-select-sm">
                        <option value="0">全部用户</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username'] . ' (' . ($u['display_name'] ?? '') . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="pushStatus" class="small text-muted mb-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-sm btn-primary" onclick="doPush()">确认推送</button>
                <button class="btn btn-sm btn-outline-danger" onclick="doUnpush()" id="btnUnpush" style="display:none">取消推送</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
var bmData = <?= json_encode($bookmarks, JSON_UNESCAPED_UNICODE) ?>;
var isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
var pastedScreenshotFile = null;

// ===== 粘贴截图 =====
document.addEventListener('paste', function(e) {
    var modal = document.getElementById('bookmarkModal');
    if (!modal || !modal.classList.contains('show')) return;
    var items = e.clipboardData && e.clipboardData.items;
    if (!items) return;
    for (var i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') === 0) {
            e.preventDefault();
            var blob = items[i].getAsFile();
            var ext = items[i].type === 'image/png' ? 'png' : (items[i].type === 'image/jpeg' ? 'jpg' : 'png');
            pastedScreenshotFile = new File([blob], 'pasted_screenshot.' + ext, {type: items[i].type});
            document.getElementById('bscreenshot').value = '';
            var url = URL.createObjectURL(blob);
            document.getElementById('bssPreview').innerHTML = '<img src="' + url + '" style="max-width:200px;max-height:120px;border-radius:6px;"><span class="small text-success ms-2">已粘贴 ✅</span>';
            document.getElementById('bscreenshotFile').value = '';
            break;
        }
    }
});

// ===== 分组 =====
function openGroupModal(id, name, sort, iconType, iconValue) {
    document.getElementById('gid').value = id || '';
    document.getElementById('gname').value = name || '';
    document.getElementById('gsort').value = sort || 0;
    document.getElementById('groupModalTitle').textContent = id ? '编辑分组' : '添加分组';
    document.getElementById('giconFileInput').value = '';
    document.getElementById('giconSvgInput').value = '';
    document.getElementById('giconUrlInput').value = '';
    resetIconMode('gicon');
    if (iconType === 'file' || iconType === 'svg' || iconType === 'url') {
        document.getElementById('gicon' + iconType.charAt(0).toUpperCase() + iconType.slice(1)).checked = true;
        resetIconMode('gicon', true);
        updateIconMode('gicon');
        if (iconType === 'svg') document.getElementById('giconSvgInput').value = iconValue || '';
        if (iconType === 'url') document.getElementById('giconUrlInput').value = iconValue || '';
    }
    new bootstrap.Modal(document.getElementById('groupModal')).show();
}

function saveGroup() {
    var id = document.getElementById('gid').value;
    var name = document.getElementById('gname').value.trim();
    if (!name) { alert('请输入名称'); return; }
    var fd = new FormData();
    fd.append('action', id ? 'update_group' : 'create_group');
    if (id) fd.append('id', id);
    fd.append('name', name);
    fd.append('sort_order', document.getElementById('gsort').value);
    var mode = document.querySelector('input[name="gicon_mode"]:checked').value;
    if (mode === 'file') {
        var f = document.getElementById('giconFileInput').files[0];
        if (f) { fd.append('icon_type', 'file'); fd.append('icon_file', f); }
    } else if (mode === 'svg') {
        var svg = document.getElementById('giconSvgInput').value.trim();
        if (svg) { fd.append('icon_type', 'svg'); fd.append('icon_value', svg); }
    } else if (mode === 'url') {
        var url = document.getElementById('giconUrlInput').value.trim();
        if (url) { fd.append('icon_type', 'url'); fd.append('icon_value', url); }
    }
    fetch('/public/index.php?route=nav-api', {method:'POST', body:fd})
        .then(r => r.json()).then(d => { if (d.ok) location.reload(); else alert(d.error); });
}

function deleteGroup(id, name) {
    if (!confirm('确定删除分组「' + name + '」及其中所有标签？')) return;
    fetch('/public/index.php?route=nav-api', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=delete_group&id=' + id})
        .then(r => r.json()).then(d => { if (d.ok) location.reload(); else alert(d.error); });
}

// ===== 标签 =====
function openBookmarkModal(id) {
    document.getElementById('bid').value = '';
    document.getElementById('bname').value = '';
    document.getElementById('bgroup').value = '';
    document.getElementById('burl').value = '';
    document.getElementById('bdesc').value = '';
    document.getElementById('bsort').value = 0;
    document.getElementById('bshowHome').checked = false;
    document.getElementById('bscreenshot').value = '';
    document.getElementById('bscreenshotFile').value = '';
    document.getElementById('bssPreview').innerHTML = '';
    document.getElementById('biconFileInput').value = '';
    document.getElementById('biconSvgInput').value = '';
    document.getElementById('biconPreview').innerHTML = '';
    document.getElementById('btnFetchSS').disabled = false;
    document.getElementById('altUrlsContainer').innerHTML = '';
    document.getElementById('bookmarkModalTitle').textContent = '添加标签';
    pastedScreenshotFile = null;
    resetIconMode('bicon');

    if (id) {
        var b = bmData.find(function(x){ return x.id == id; });
        if (b) {
            document.getElementById('bid').value = b.id;
            document.getElementById('bname').value = b.name;
            document.getElementById('bgroup').value = b.group_id;
            document.getElementById('burl').value = b.url;
            document.getElementById('bdesc').value = b.description || '';
            document.getElementById('bsort').value = b.sort_order || 0;
            document.getElementById('bshowHome').checked = !!parseInt(b.show_on_home);
            if (b.screenshot) {
                document.getElementById('bscreenshot').value = b.screenshot;
                document.getElementById('bssPreview').innerHTML = '<img src="/uploads/' + b.screenshot + '" style="max-width:120px;max-height:80px;border-radius:6px;">';
            }
            document.getElementById('bookmarkModalTitle').textContent = '编辑标签';
            if (b.icon_type && b.icon_value) {
                if (b.icon_type === 'file') {
                    document.getElementById('biconFile').checked = true;
                    document.getElementById('biconPreview').innerHTML = '<img src="/uploads/' + b.icon_value + '" style="width:32px;height:32px;object-fit:cover;border-radius:4px;">';
                } else if (b.icon_type === 'svg') {
                    document.getElementById('biconSvg').checked = true;
                    document.getElementById('biconSvgInput').value = b.icon_value;
                    document.getElementById('biconPreview').innerHTML = '<span style="display:inline-block;width:32px;height:32px;overflow:hidden;">' + b.icon_value + '</span>';
                } else if (b.icon_type === 'url') {
                    document.getElementById('biconUrl').checked = true;
                    document.getElementById('biconUrlInput').value = b.icon_value;
                    document.getElementById('biconPreview').innerHTML = '<img src="' + b.icon_value + '" style="width:32px;height:32px;object-fit:contain;border-radius:4px;" onerror="this.style.display=\'none\'">';
                }
                updateBiconMode();
            }
            if (b.urls && b.urls.length) {
                b.urls.forEach(function(u){
                    addAltUrlRow(u.label || '', u.url);
                });
            }
        }
    }
    new bootstrap.Modal(document.getElementById('bookmarkModal')).show();
}

function addAltUrl() {
    addAltUrlRow('', '');
}

function addAltUrlRow(label, url) {
    var container = document.getElementById('altUrlsContainer');
    var div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML = '<input type="text" class="form-control form-control-sm" placeholder="标签(可选)" value="' + h(label) + '" style="max-width:120px;">'
        + '<input type="text" class="form-control form-control-sm" placeholder="网址" value="' + h(url) + '">'
        + '<button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">×</button>';
    container.appendChild(div);
}

function h(s) { return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function collectAltUrls() {
    var lines = [];
    document.querySelectorAll('#altUrlsContainer .input-group').forEach(function(row){
        var inputs = row.querySelectorAll('input');
        var label = inputs[0].value.trim();
        var url = inputs[1].value.trim();
        if (!url) return;
        lines.push(label ? (label + '|' + url) : url);
    });
    document.getElementById('altUrlsRaw').value = lines.join('\n');
}

function fetchPageInfo() {
    var url = document.getElementById('burl').value.trim();
    if (!url) { alert('请先填写网址'); return; }
    if (!/^https?:\/\//i.test(url)) url = 'https://' + url;
    document.getElementById('burl').value = url;
    var btn = document.getElementById('btnFetchInfo');
    btn.disabled = true; btn.textContent = '获取中...';

    var infoReq = fetch('/public/index.php?route=nav-api', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=fetch_page_info&url=' + encodeURIComponent(url)
    }).then(r => r.json());

    var ssReq = fetch('/public/index.php?route=nav-api', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=fetch_screenshot&url=' + encodeURIComponent(url)
    }).then(r => r.json());

    Promise.all([infoReq, ssReq]).then(function(results){
        btn.disabled = false; btn.textContent = '🔍 获取信息';
        var infoData = results[0];
        var ssData = results[1];

        if (infoData.ok && infoData.info) {
            if (infoData.info.title && !document.getElementById('bname').value) {
                document.getElementById('bname').value = infoData.info.title;
            }
            if (infoData.info.description && !document.getElementById('bdesc').value) {
                document.getElementById('bdesc').value = infoData.info.description;
            }
            if (infoData.info.favicon_path) {
                document.getElementById('biconFile').checked = true;
                document.getElementById('biconFileWrap').classList.remove('d-none');
                document.getElementById('biconSvgWrap').classList.add('d-none');
                document.getElementById('biconPreview').innerHTML = '<img src="/uploads/' + infoData.info.favicon_path + '" style="width:32px;height:32px;object-fit:contain;border-radius:4px;">';
            }
        }
        if (ssData.ok && ssData.screenshot_url && !document.getElementById('bscreenshot').value) {
            document.getElementById('bscreenshot').value = ssData.screenshot_url;
            document.getElementById('bssPreview').innerHTML = '<img src="/uploads/' + ssData.screenshot_url + '" style="max-width:120px;max-height:80px;border-radius:6px;">';
        }
        if (!infoData.ok && !ssData.ok) {
            alert('无法获取页面信息（请检查网址是否正确）');
        }
    }).catch(function(){
        btn.disabled = false; btn.textContent = '🔍 获取信息';
    });
}

function suggestFetchInfo() {
    var url = document.getElementById('burl').value.trim();
    var name = document.getElementById('bname').value.trim();
    if (url && !name) fetchPageInfo();
}

function fetchScreenshot() {
    var url = document.getElementById('burl').value.trim();
    if (!url) { alert('请先填写网址'); return; }
    var btn = document.getElementById('btnFetchSS');
    btn.disabled = true; btn.textContent = '获取中...';
    fetch('/public/index.php?route=nav-api', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=fetch_screenshot&url=' + encodeURIComponent(url)
    }).then(r => r.json()).then(d => {
        btn.disabled = false; btn.textContent = '自动获取';
        if (d.ok && d.screenshot_url) {
            document.getElementById('bscreenshot').value = d.screenshot_url;
            document.getElementById('bssPreview').innerHTML = '<img src="/uploads/' + d.screenshot_url + '" style="max-width:120px;max-height:80px;border-radius:6px;">';
        } else {
            alert('自动获取失败，请手动上传');
        }
    }).catch(function(){
        btn.disabled = false; btn.textContent = '自动获取';
        alert('获取失败，请手动上传');
    });
}

function saveBookmark() {
    var id = document.getElementById('bid').value;
    var name = document.getElementById('bname').value.trim();
    var url = document.getElementById('burl').value.trim();
    var gid = document.getElementById('bgroup').value;
    if (!name || !url) { alert('请填写名称和网址'); return; }
    if (!gid) { alert('请选择分组'); return; }

    collectAltUrls();

    var fd = new FormData();
    fd.append('action', id ? 'update_bookmark' : 'create_bookmark');
    if (id) fd.append('id', id);
    fd.append('name', name);
    fd.append('url', url);
    fd.append('group_id', gid);
    fd.append('description', document.getElementById('bdesc').value.trim());
    fd.append('sort_order', document.getElementById('bsort').value);
    if (document.getElementById('bshowHome').checked) fd.append('show_on_home', '1');
    fd.append('screenshot_url', document.getElementById('bscreenshot').value.trim());
    fd.append('alt_urls', document.getElementById('altUrlsRaw').value);

    var iconMode = document.querySelector('input[name="bicon_mode"]:checked');
    if (iconMode) {
        if (iconMode.value === 'file') {
            var f = document.getElementById('biconFileInput').files[0];
            if (f) {
                fd.append('icon_type', 'file'); fd.append('icon_file', f);
            } else {
                var prevImg = document.querySelector('#biconPreview img');
                if (prevImg) {
                    var src = prevImg.getAttribute('src') || '';
                    var path = src.replace(/^\/uploads\//, '');
                    if (path && path !== src) { fd.append('icon_type', 'file'); fd.append('icon_value', path); }
                }
            }
        } else if (iconMode.value === 'svg') {
            var svg = document.getElementById('biconSvgInput').value.trim();
            if (svg) { fd.append('icon_type', 'svg'); fd.append('icon_value', svg); }
        } else if (iconMode.value === 'url') {
            var url = document.getElementById('biconUrlInput').value.trim();
            if (url) { fd.append('icon_type', 'url'); fd.append('icon_value', url); }
        }
    }

    var sf = document.getElementById('bscreenshotFile').files[0];
    if (sf) fd.append('screenshot', sf);
    else if (pastedScreenshotFile) fd.append('screenshot', pastedScreenshotFile);

    fetch('/public/index.php?route=nav-api', {method:'POST', body:fd})
        .then(r => r.json()).then(d => {
            if (d.ok) {
                var msg = '保存成功';
                if (sf && d.screenshot_uploaded === false) msg += '，截图上传失败（检查文件格式/大小≤5MB）';
                if (sf && d.screenshot_uploaded === true) msg += '，截图已上传';
                alert(msg);
                location.reload();
            }
            else alert(d.error || '保存失败');
        });
}

function deleteBookmark(id, name) {
    if (!confirm('确定删除标签「' + name + '」？')) return;
    fetch('/public/index.php?route=nav-api', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=delete_bookmark&id=' + id})
        .then(r => r.json()).then(d => { if (d.ok) location.reload(); else alert(d.error); });
}

function filterBookmarks() {
    var gid = document.getElementById('filterGroup').value;
    document.querySelectorAll('#bookmarkList .nv-item-row').forEach(function(el){
        el.style.display = (!gid || el.getAttribute('data-group') === gid) ? '' : 'none';
    });
}

function resetIconMode(prefix, keepEvents) {
    if (!keepEvents) {
        document.getElementById(prefix + 'None').checked = true;
    }
    document.getElementById(prefix + 'FileWrap').classList.add('d-none');
    document.getElementById(prefix + 'SvgWrap').classList.add('d-none');
    document.getElementById(prefix + 'UrlWrap').classList.add('d-none');
    if (!keepEvents) {
        document.querySelectorAll('input[name="' + prefix + '_mode"]').forEach(function(r){
            r.addEventListener('change', function(){
                document.getElementById(prefix + 'FileWrap').classList.toggle('d-none', r.value !== 'file');
                document.getElementById(prefix + 'SvgWrap').classList.toggle('d-none', r.value !== 'svg');
                document.getElementById(prefix + 'UrlWrap').classList.toggle('d-none', r.value !== 'url');
            });
        });
    }
}
function updateIconMode(prefix) {
    var checked = document.querySelector('input[name="' + prefix + '_mode"]:checked');
    if (checked) {
        document.getElementById(prefix + 'FileWrap').classList.toggle('d-none', checked.value !== 'file');
        document.getElementById(prefix + 'SvgWrap').classList.toggle('d-none', checked.value !== 'svg');
        document.getElementById(prefix + 'UrlWrap').classList.toggle('d-none', checked.value !== 'url');
    }
}
resetIconMode('gicon');
resetIconMode('bicon');
function updateBiconMode() { updateIconMode('bicon'); }

// ===== 推送 =====
function openPushModal(id, name) {
    document.getElementById('pushBookmarkId').value = id;
    document.getElementById('pushBookmarkName').textContent = '标签：' + name;
    document.getElementById('pushTarget').value = '0';
    document.getElementById('pushStatus').innerHTML = '';
    document.getElementById('btnUnpush').style.display = 'none';
    var modal = new bootstrap.Modal(document.getElementById('pushModal'));
    modal.show();

    fetch('/public/index.php?route=nav-api', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=get_pushed_targets&bookmark_id=' + id
    }).then(r => r.json()).then(d => {
        if (d.ok && d.targets.length) {
            document.getElementById('pushStatus').innerHTML = '已推送目标: ' + d.targets.map(function(t){return t==0?'全部':t}).join(', ');
            document.getElementById('btnUnpush').style.display = '';
            if (d.targets.length === 1) {
                document.getElementById('pushTarget').value = d.targets[0];
            }
        }
    });
}

function doPush() {
    var id = document.getElementById('pushBookmarkId').value;
    var target = document.getElementById('pushTarget').value;
    fetch('/public/index.php?route=nav-api', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=push&bookmark_id=' + id + '&target_user_id=' + target
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert('推送成功'); location.reload(); }
        else alert(d.error || '推送失败');
    });
}

function doUnpush() {
    if (!confirm('确定取消对该目标的推送？')) return;
    var id = document.getElementById('pushBookmarkId').value;
    var target = document.getElementById('pushTarget').value;
    fetch('/public/index.php?route=nav-api', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=unpush&bookmark_id=' + id + '&target_user_id=' + target
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert('已取消推送'); location.reload(); }
        else alert(d.error || '操作失败');
    });
}
</script>
