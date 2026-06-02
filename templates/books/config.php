<?php
/** @var array $books */
function formatSize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">⚙️ 图书配置</h5>
    <button class="btn btn-sm btn-primary" onclick="showUploadModal()">+ 上传图书</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 small">
            <thead class="table-light">
                <tr>
                    <th style="width:60px">封面</th>
                    <th>书名</th>
                    <th>作者</th>
                    <th>类型</th>
                    <th>大小</th>
                    <th>范围</th>
                    <th>上传时间</th>
                    <th style="width:180px">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">暂无图书，点击右上角上传</td></tr>
                <?php else: ?>
                <?php foreach ($books as $b): ?>
                <tr>
                    <td>
                        <img src="<?= htmlspecialchars($b['cover'] ?? '/assets/img/book-placeholder.svg') ?>" style="width:40px;height:50px;object-fit:cover;border-radius:3px" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 50%22><rect fill=%22%23e2e8f0%22 width=%2240%22 height=%2250%22 rx=%223%22/><text fill=%22%2394a3b8%22 x=%2220%22 y=%2230%22 text-anchor=%22middle%22 font-size=%2212%22>📖</text></svg>'">
                    </td>
                    <td class="fw-bold"><?= htmlspecialchars($b['title']) ?></td>
                    <td><?= htmlspecialchars($b['author'] ?: '-') ?></td>
                    <td><span class="badge bg-<?= $b['file_type'] === 'pdf' ? 'danger' : 'secondary' ?>"><?= strtoupper($b['file_type']) ?></span></td>
                    <td><?= formatSize((int)($b['file_size'] ?? 0)) ?></td>
                    <td>
                        <?php $pushInfo = $b['push_info'] ?? ''; $pushList = $b['push_list'] ?? []; ?>
                        <?php if (($b['scope'] ?? 'personal') === 'system'): ?>
                            <span class="badge bg-success">全系统可阅</span>
                        <?php elseif ($pushInfo): ?>
                            <span class="badge bg-info text-dark" style="cursor:pointer" onclick="showPushViewer(<?= (int)($b['id']) ?>, <?= htmlspecialchars(json_encode($pushList, JSON_UNESCAPED_UNICODE)) ?>, '<?= htmlspecialchars(addslashes($b['title'])) ?>')"><?= htmlspecialchars($pushInfo) ?></span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">个人</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($b['created_at'] ?? '') ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="editBook(<?= (int)$b['id'] ?>,'<?= htmlspecialchars(addslashes($b['title'])) ?>','<?= htmlspecialchars(addslashes($b['author'])) ?>','<?= htmlspecialchars(addslashes($b['cover'] ?? '')) ?>','<?= htmlspecialchars(addslashes($b['description'] ?? '')) ?>')">编辑</button>
                        <button class="btn btn-sm btn-outline-warning py-0 px-1" onclick="openBook(<?= (int)$b['id'] ?>)">阅读</button>
                        <?php if (($b['scope'] ?? 'personal') !== 'system'): ?>
                        <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="showPushModal(<?= (int)$b['id'] ?>, '<?= htmlspecialchars(addslashes($b['title'])) ?>')">推送</button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteBook(<?= (int)$b['id'] ?>)">删除</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 上传弹窗 -->
<div class="modal fade" id="modalUpload" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2"><h6 class="modal-title">📤 上传图书</h6><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3 p-3 border rounded bg-light">
                    <label class="form-label small fw-bold mb-1">选择文件</label>
                    <input type="file" id="uploadFile" accept=".pdf,.txt" class="form-control form-control-sm">
                    <div class="small text-muted mt-1">支持 PDF / TXT 格式</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-8"><label class="form-label small fw-bold mb-0">书名</label><input type="text" id="uploadTitle" class="form-control form-control-sm" placeholder="留空则使用文件名"></div>
                    <div class="col-4"><label class="form-label small fw-bold mb-0">作者</label><input type="text" id="uploadAuthor" class="form-control form-control-sm"></div>
                </div>
                <div class="mb-2"><label class="form-label small fw-bold mb-0">封面图片</label><input type="file" id="uploadCover" accept="image/*" class="form-control form-control-sm"><span class="small text-muted">可选</span></div>
                <div class="mb-2"><label class="form-label small fw-bold mb-0">简介</label><textarea id="uploadDesc" class="form-control form-control-sm" rows="3" placeholder="书籍简介..."></textarea></div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-light" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-sm btn-primary" onclick="submitUpload()">📤 确认上传</button>
            </div>
        </div>
    </div>
</div>

<!-- 编辑弹窗 -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white py-2"><h6 class="modal-title">✏️ 编辑图书</h6><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <input type="hidden" id="editOldCover">
                <div class="text-center mb-3">
                    <img id="editCoverPreview" src="" style="max-height:100px;border-radius:4px;display:none" alt="">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-8"><label class="form-label small fw-bold mb-0">书名</label><input type="text" id="editTitle" class="form-control form-control-sm"></div>
                    <div class="col-4"><label class="form-label small fw-bold mb-0">作者</label><input type="text" id="editAuthor" class="form-control form-control-sm"></div>
                </div>
                <div class="mb-2"><label class="form-label small fw-bold mb-0">更换封面 <span class="small text-muted fw-normal">可选</span></label><input type="file" id="editCoverFile" accept="image/*" class="form-control form-control-sm" onchange="previewEditCover(this)"></div>
                <div class="mb-2"><label class="form-label small fw-bold mb-0">简介</label><textarea id="editDesc" class="form-control form-control-sm" rows="3" placeholder="书籍简介..."></textarea></div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-light" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-sm btn-primary" onclick="submitEdit()">💾 保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 已推送查看弹窗 -->
<div class="modal fade" id="modalViewer" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2"><h6 class="modal-title">📖 可查看用户</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted mb-2">"<span id="viewerBookTitle"></span>" 已推送给：</p>
                <div id="viewerList" class="table-responsive"></div>
            </div>
            <div class="modal-footer py-2"><button class="btn btn-sm btn-light" data-bs-dismiss="modal">关闭</button></div>
        </div>
    </div>
</div>

<!-- 推送弹窗 -->
<div class="modal fade" id="modalPush" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">推送图书</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="pushBookId">
                <p class="small text-muted mb-3">推送 "<span id="pushBookTitle" class="fw-bold"></span>" 到：</p>
                <button class="btn btn-success w-100 mb-3" onclick="pushToAll()">📢 推送至全系统</button>
                <hr>
                <label class="form-label small mb-0">推送给指定用户</label>
                <div class="input-group input-group-sm">
                    <input type="number" id="pushUserId" class="form-control" placeholder="输入用户ID">
                    <button class="btn btn-outline-primary" onclick="lookupUser()">查询</button>
                </div>
                <div id="userInfoBox" class="mt-2 small" style="display:none">
                    <div class="border rounded p-2 bg-light">
                        <div><strong id="uiName"></strong> <span id="uiEmail" class="text-muted"></span></div>
                        <div class="mt-1"><button class="btn btn-sm btn-primary" onclick="confirmPushUser()">确认推送给该用户</button></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script>
var uploadModal, editModal, viewerModal;
document.addEventListener('DOMContentLoaded', function(){
    uploadModal = new bootstrap.Modal(document.getElementById('modalUpload'));
    editModal = new bootstrap.Modal(document.getElementById('modalEdit'));
    viewerModal = new bootstrap.Modal(document.getElementById('modalViewer'));
});

function showPushViewer(bookId, list, title) {
    document.getElementById('viewerBookTitle').textContent = title;
    var html = '';
    if (list && list.length) {
        html = '<table class="table table-sm table-hover mb-0"><thead><tr><th>用户</th><th style="width:100px">操作</th></tr></thead><tbody>';
        list.forEach(function(u){
            html += '<tr><td>👤 ' + u.name + ' <span class="text-muted">(ID:' + u.id + ')</span></td>';
            html += '<td><button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="cancelPush(' + bookId + ',' + u.id + ')">取消推送</button></td></tr>';
        });
        html += '</tbody></table>';
    } else {
        html = '<div class="text-muted text-center py-3">暂无推送记录</div>';
    }
    document.getElementById('viewerList').innerHTML = html;
    viewerModal.show();
}

function cancelPush(bookId, targetUid) {
    if (!confirm('确定取消对该用户的推送？对方将无法再看到此书。')) return;
    var fd = new FormData();
    fd.append('action', 'cancel_push');
    fd.append('id', bookId);
    fd.append('target_uid', targetUid);
    fetch('/public/index.php?route=books-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) { viewerModal.hide(); location.reload(); }
            else alert('取消推送失败');
        });
}

function showUploadModal() { uploadModal.show(); }

function submitUpload() {
    var file = document.getElementById('uploadFile').files[0];
    if (!file) { alert('请选择文件'); return; }
    var fd = new FormData();
    fd.append('action', 'upload');
    fd.append('file', file);
    fd.append('title', document.getElementById('uploadTitle').value);
    fd.append('author', document.getElementById('uploadAuthor').value);
    fd.append('description', document.getElementById('uploadDesc').value);
    var coverFile = document.getElementById('uploadCover').files[0];
    if (coverFile) fd.append('cover', coverFile);
    fetch('/public/index.php?route=books-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) { uploadModal.hide(); location.reload(); }
            else alert(d.error || '上传失败');
        });
}

function deleteBook(id) {
    if (!confirm('确定删除？文件也将被删除。')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('/public/index.php?route=books-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){ if (d.ok) location.reload(); else alert('删除失败'); });
}

function editBook(id, title, author, cover, desc) {
    document.getElementById('editId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editAuthor').value = author;
    document.getElementById('editOldCover').value = cover || '';
    document.getElementById('editDesc').value = desc || '';
    document.getElementById('editCoverFile').value = '';
    var preview = document.getElementById('editCoverPreview');
    if (cover) { preview.src = cover; preview.style.display = ''; }
    else { preview.style.display = 'none'; }
    editModal.show();
}

function previewEditCover(input) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var preview = document.getElementById('editCoverPreview');
        preview.src = e.target.result;
        preview.style.display = '';
    };
    reader.readAsDataURL(file);
}

function submitEdit() {
    var fd = new FormData();
    fd.append('action', 'update');
    fd.append('id', document.getElementById('editId').value);
    fd.append('title', document.getElementById('editTitle').value);
    fd.append('author', document.getElementById('editAuthor').value);
    fd.append('old_cover', document.getElementById('editOldCover').value);
    fd.append('description', document.getElementById('editDesc').value);
    var coverFile = document.getElementById('editCoverFile').files[0];
    if (coverFile) fd.append('cover_file', coverFile);
    fetch('/public/index.php?route=books-api-update', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){ if (d.ok) { editModal.hide(); location.reload(); } else alert('保存失败'); });
}

function openBook(id) {
    window.location.href = '/public/index.php?route=books-reader&id=' + id;
}

var pushModal, pushBookId;
document.addEventListener('DOMContentLoaded', function(){
    pushModal = new bootstrap.Modal(document.getElementById('modalPush'));
});

function showPushModal(id, title) {
    pushBookId = id;
    document.getElementById('pushBookId').value = id;
    document.getElementById('pushBookTitle').textContent = title;
    document.getElementById('pushUserId').value = '';
    document.getElementById('userInfoBox').style.display = 'none';
    pushModal.show();
}

function pushToAll() {
    if (!confirm('确定将此书推送给全系统所有用户在线预览？')) return;
    var fd = new FormData();
    fd.append('action', 'push_all');
    fd.append('id', pushBookId);
    fetch('/public/index.php?route=books-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) { pushModal.hide(); location.reload(); }
            else alert(d.error || '推送失败');
        });
}

function lookupUser() {
    var uid = document.getElementById('pushUserId').value;
    if (!uid) return;
    var fd = new FormData();
    fd.append('action', 'user_info');
    fd.append('target_uid', uid);
    fetch('/public/index.php?route=books-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) {
                document.getElementById('uiName').textContent = d.user.username + ' (' + d.user.nickname + ')';
                document.getElementById('uiEmail').textContent = d.user.email;
                document.getElementById('userInfoBox').style.display = '';
            } else {
                alert(d.error || '用户不存在');
            }
        });
}

function confirmPushUser() {
    var uid = document.getElementById('pushUserId').value;
    if (!confirm('确定推送给该用户？')) return;
    var fd = new FormData();
    fd.append('action', 'push_user');
    fd.append('id', pushBookId);
    fd.append('target_uid', uid);
    fetch('/public/index.php?route=books-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) { pushModal.hide(); alert('推送成功'); }
            else alert(d.error || '推送失败');
        });
}
</script>
