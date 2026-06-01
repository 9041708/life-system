<?php
/** @var array $entries */
/** @var string $search */
?>
<style>
.pv-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    transition: box-shadow 0.15s;
    background: #fff;
}
.pv-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.pv-card .pv-name { font-weight: 600; font-size: 15px; }
.pv-card .pv-meta { font-size: 13px; color: #666; }
.pv-card .pv-actions { display: flex; gap: 6px; }
.pv-copy-toast {
    position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
    background: #333; color: #fff; padding: 8px 20px; border-radius: 6px;
    font-size: 13px; z-index: 9999; pointer-events: none; opacity: 0; transition: opacity 0.3s;
}
.pv-copy-toast.show { opacity: 1; }
.pwd-toggle { cursor: pointer; user-select: none; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">🔐 密码箱</h5>
    <button class="btn btn-primary btn-sm" onclick="openCreateModal()">+ 新增密码</button>
</div>

<div class="mb-3">
    <div class="input-group">
        <span class="input-group-text">🔍</span>
        <input type="text" id="searchInput" class="form-control" placeholder="搜索名称、网址、用户名..."
               value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter') doSearch()">
        <button class="btn btn-outline-secondary" onclick="doSearch()">搜索</button>
        <?php if ($search !== ''): ?>
            <a href="/public/index.php?route=toolbox-password-vault" class="btn btn-outline-secondary">清除</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($entries)): ?>
    <div class="text-center text-muted py-5">
        <div style="font-size:48px;margin-bottom:12px">🔐</div>
        <div><?= $search !== '' ? '未找到匹配的密码' : '暂无保存的密码' ?></div>
        <div class="small text-muted mt-1">点击右上角"新增密码"添加</div>
    </div>
<?php else: ?>
    <div class="row" id="entryList">
        <?php foreach ($entries as $entry): ?>
        <div class="col-12 col-md-6 col-lg-4" data-id="<?= (int)$entry['id'] ?>">
            <div class="pv-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="pv-name text-truncate" style="flex:1;min-width:0"><?= htmlspecialchars($entry['name']) ?></div>
                    <div class="pv-actions ms-2 flex-shrink-0">
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyPassword(<?= (int)$entry['id'] ?>)" title="复制密码">📋</button>
                        <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?= (int)$entry['id'] ?>)" title="编辑">✏️</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEntry(<?= (int)$entry['id'] ?>, '<?= htmlspecialchars(addslashes($entry['name'])) ?>')" title="删除">🗑</button>
                    </div>
                </div>
                <div class="pv-meta mb-1">
                    <span>👤 <?= htmlspecialchars($entry['username']) ?></span>
                </div>
                <?php if (!empty($entry['url'])): ?>
                <div class="pv-meta mb-1">
                    <span>🔗 <a href="<?= htmlspecialchars($entry['url']) ?>" target="_blank" rel="noopener" class="text-decoration-none"><?= htmlspecialchars($entry['url']) ?></a></span>
                </div>
                <?php endif; ?>
                <div class="pv-meta">
                    <span>🔒 <span class="pwd-masked" data-id="<?= (int)$entry['id'] ?>">••••••••</span>
                    <span class="pwd-toggle small text-primary ms-1" data-id="<?= (int)$entry['id'] ?>" onclick="togglePassword(<?= (int)$entry['id'] ?>)">显示</span></span>
                    <span class="d-none pwd-real" data-id="<?= (int)$entry['id'] ?>"><?= htmlspecialchars($entry['password']) ?></span>
                </div>
                <?php if (!empty($entry['notes'])): ?>
                <div class="pv-meta mt-1 small">
                    <span class="text-muted">📝 <?= nl2br(htmlspecialchars($entry['notes'])) ?></span>
                </div>
                <?php endif; ?>
                <div class="pv-meta mt-2">
                    <small class="text-muted"><?= htmlspecialchars(substr($entry['updated_at'], 0, 16)) ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Toast -->
<div class="pv-copy-toast" id="copyToast">已复制到剪贴板</div>

<script>
var cachedData = {};

function togglePassword(id) {
    var masked = document.querySelector('.pwd-masked[data-id="' + id + '"]');
    var real = document.querySelector('.pwd-real[data-id="' + id + '"]');
    var toggle = document.querySelector('.pwd-toggle[data-id="' + id + '"]');
    if (!masked || !real) return;
    if (masked.classList.contains('d-none')) {
        masked.classList.remove('d-none');
        real.classList.add('d-none');
        if (toggle) toggle.textContent = '显示';
    } else {
        masked.classList.add('d-none');
        real.classList.remove('d-none');
        if (toggle) toggle.textContent = '隐藏';
    }
}

function showToast(msg) {
    var t = document.getElementById('copyToast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 2000);
}

function copyPassword(id) {
    var real = document.querySelector('.pwd-real[data-id="' + id + '"]');
    if (!real) return;
    var pwd = real.textContent;
    try {
        navigator.clipboard.writeText(pwd).then(function() {
            showToast('密码已复制到剪贴板');
        });
    } catch(e) {
        var ta = document.createElement('textarea');
        ta.value = pwd; ta.style.position = 'fixed'; ta.style.left = '-9999px';
        document.body.appendChild(ta); ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('密码已复制到剪贴板');
    }
}

function doSearch() {
    var val = document.getElementById('searchInput').value.trim();
    if (val) {
        window.location.href = '/public/index.php?route=toolbox-password-vault&search=' + encodeURIComponent(val);
    } else {
        window.location.href = '/public/index.php?route=toolbox-password-vault';
    }
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = '新增密码';
    document.getElementById('entryId').value = '';
    document.getElementById('entryName').value = '';
    document.getElementById('entryUrl').value = '';
    document.getElementById('entryUsername').value = '';
    document.getElementById('entryPassword').value = '';
    document.getElementById('entryNotes').value = '';
    document.getElementById('entryPassword').type = 'password';
    document.getElementById('togglePwdBtn').textContent = '显示';
    document.getElementById('deleteBtn').style.display = 'none';
    new bootstrap.Modal(document.getElementById('entryModal')).show();
}

function openEditModal(id) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/public/index.php?route=toolbox-password-vault-api', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onload = function() {
        try {
            var d = JSON.parse(xhr.responseText || '{}');
            if (d.ok && d.entry) {
                document.getElementById('modalTitle').textContent = '编辑密码';
                document.getElementById('entryId').value = d.entry.id;
                document.getElementById('entryName').value = d.entry.name;
                document.getElementById('entryUrl').value = d.entry.url || '';
                document.getElementById('entryUsername').value = d.entry.username;
                document.getElementById('entryPassword').value = d.entry.password;
                document.getElementById('entryNotes').value = d.entry.notes || '';
                document.getElementById('entryPassword').type = 'password';
                document.getElementById('togglePwdBtn').textContent = '显示';
                document.getElementById('deleteBtn').style.display = '';
                cachedData[id] = d.entry;
                new bootstrap.Modal(document.getElementById('entryModal')).show();
            }
        } catch(e) {}
    };
    xhr.send('action=get&id=' + id);
}

function togglePwdVisibility() {
    var input = document.getElementById('entryPassword');
    var btn = document.getElementById('togglePwdBtn');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '隐藏';
    } else {
        input.type = 'password';
        btn.textContent = '显示';
    }
}

function saveEntry() {
    var id = document.getElementById('entryId').value;
    var name = document.getElementById('entryName').value.trim();
    var pwd = document.getElementById('entryPassword').value;
    if (!name) { alert('请输入名称'); return; }
    if (!id && !pwd) { alert('请输入密码'); return; }

    var isUpdate = !!id;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/public/index.php?route=toolbox-password-vault-api', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onload = function() {
        try {
            var d = JSON.parse(xhr.responseText || '{}');
            if (d.ok) {
                location.reload();
            } else {
                alert(d.error || '操作失败');
            }
        } catch(e) { alert('操作失败'); }
    };
    var params = 'action=' + (isUpdate ? 'update' : 'create')
        + '&name=' + encodeURIComponent(name)
        + '&url=' + encodeURIComponent(document.getElementById('entryUrl').value.trim())
        + '&username=' + encodeURIComponent(document.getElementById('entryUsername').value.trim())
        + '&password=' + encodeURIComponent(pwd)
        + '&notes=' + encodeURIComponent(document.getElementById('entryNotes').value.trim());
    if (isUpdate) params += '&id=' + id;
    xhr.send(params);
}

function deleteEntry(id, name) {
    if (!confirm('确定删除「' + name.replace(/\\'/g, '\'') + '」？此操作不可撤销。')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/public/index.php?route=toolbox-password-vault-api', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onload = function() {
        try {
            var d = JSON.parse(xhr.responseText || '{}');
            if (d.ok) {
                location.reload();
            } else {
                alert(d.error || '删除失败');
            }
        } catch(e) { alert('删除失败'); }
    };
    xhr.send('action=delete&id=' + id);
}

function deleteFromModal() {
    var id = document.getElementById('entryId').value;
    var name = document.getElementById('entryName').value;
    if (!id) return;
    if (!confirm('确定删除「' + name + '」？此操作不可撤销。')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/public/index.php?route=toolbox-password-vault-api', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onload = function() {
        try {
            var d = JSON.parse(xhr.responseText || '{}');
            if (d.ok) location.reload();
            else alert(d.error || '删除失败');
        } catch(e) { alert('删除失败'); }
    };
    xhr.send('action=delete&id=' + id);
}
</script>

<!-- 新增/编辑弹窗 -->
<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">新增密码</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="entryId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">名称 <span class="text-danger">*</span></label>
                    <input type="text" id="entryName" class="form-control" placeholder="如：邮箱、微信、银行">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">网址</label>
                    <input type="text" id="entryUrl" class="form-control" placeholder="如：https://example.com（非必填）">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">用户名</label>
                    <input type="text" id="entryUsername" class="form-control" placeholder="用户名/账号">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">密码 <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" id="entryPassword" class="form-control" placeholder="密码">
                        <button class="btn btn-outline-secondary" type="button" id="togglePwdBtn" onclick="togglePwdVisibility()">显示</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">备注</label>
                    <textarea id="entryNotes" class="form-control" rows="3" placeholder="备注信息（非必填）"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="deleteBtn" style="display:none" onclick="deleteFromModal()">删除</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveEntry()">保存</button>
            </div>
        </div>
    </div>
</div>
