<?php
/** @var array $project */
/** @var array $updates */
/** @var array $members */
/** @var bool $isOwner */

$statusLabels = ['planning' => '规划中', 'active' => '进行中', 'completed' => '已完成', 'archived' => '已归档'];
$statusColors = ['planning' => '#6b7280', 'active' => '#3b82f6', 'completed' => '#22c55e', 'archived' => '#9ca3af'];
$sColor = $statusColors[$project['status']] ?? '#3b82f6';
?>
<style>
.pj-header { margin-bottom: 24px; }
.pj-header .pj-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 4px; }
.pj-header .pj-meta { font-size: 0.82rem; color: #666; }
body.theme-dark .pj-header .pj-meta { color: #94a3b8; }
.pj-progress-lg { height: 10px; border-radius: 5px; background: rgba(0,0,0,0.06); overflow: hidden; margin: 8px 0; }
body.theme-dark .pj-progress-lg { background: rgba(148,163,184,0.15); }
.pj-progress-lg .bar { height: 100%; border-radius: 5px; transition: width 0.3s; }

.tl { position: relative; padding-left: 32px; }
.tl::before { content: ''; position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: rgba(0,0,0,0.1); }
body.theme-dark .tl::before { background: rgba(148,163,184,0.2); }
.tl-item { position: relative; margin-bottom: 24px; }
.tl-dot { position: absolute; left: -27px; top: 6px; width: 14px; height: 14px; border-radius: 50%; border: 3px solid #fff; z-index: 1; }
body.theme-dark .tl-dot { border-color: #1e293b; }
.tl-card {
    background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.06); border-radius: 10px;
    padding: 14px 16px; backdrop-filter: blur(10px);
}
body.theme-dark .tl-card { background: rgba(30,41,59,0.5); border-color: rgba(148,163,184,0.12); }
.tl-card .tl-date { font-size: 0.75rem; color: #999; margin-bottom: 4px; }
.tl-card .tl-title { font-weight: 600; font-size: 0.92rem; margin-bottom: 4px; }
.tl-card .tl-content { font-size: 0.85rem; color: #555; white-space: pre-wrap; line-height: 1.6; }
body.theme-dark .tl-card .tl-content { color: #b8c5d6; }
.tl-card .tl-progress { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 500; }
.tl-card .tl-actions { margin-top: 8px; display: flex; gap: 6px; }
.tl-card .tl-attach { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px; }
.tl-card .tl-attach a { display: inline-flex; align-items: center; gap: 4px; font-size: 0.78rem; color: #3b82f6; text-decoration: none; }
.tl-card .tl-attach a:hover { text-decoration: underline; }
.tl-card .tl-attach img { max-width: 200px; max-height: 150px; border-radius: 6px; cursor: pointer; }
.tl-empty { text-align: center; padding: 40px; color: #999; }
.task-list { list-style: none; padding: 0; margin: 0; }
.task-item { display: flex; align-items: flex-start; gap: 8px; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.04); font-size: 0.88rem; }
body.theme-dark .task-item { border-bottom-color: rgba(148,163,184,0.1); }
.task-item:last-child { border-bottom: none; }
.task-item input[type="checkbox"] { margin-top: 3px; cursor: pointer; accent-color: #667eea; }
.task-item.done label { text-decoration: line-through; color: #999; }
.task-item label { cursor: pointer; margin: 0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-2">
    <a href="?route=project-list" class="btn btn-sm btn-outline-secondary">← 返回列表</a>
    <div class="d-flex gap-2">
        <?php if ($isOwner): ?>
        <button class="btn btn-sm btn-outline-primary" onclick="openProjectEdit()">编辑项目</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteProject(<?= (int)$project['id'] ?>, '<?= htmlspecialchars(addslashes($project['name'])) ?>')">删除</button>
        <?php endif; ?>
    </div>
</div>

<div class="pj-header">
    <div class="d-flex align-items-center gap-2 mb-1">
        <span class="pj-title"><?= htmlspecialchars($project['name']) ?></span>
        <span class="pj-badge" style="background:<?= $sColor ?>20;color:<?= $sColor ?>;padding:2px 10px;border-radius:10px;font-size:0.75rem"><?= $statusLabels[$project['status']] ?? '' ?></span>
    </div>
    <?php if (!empty($project['description'])): ?>
    <div class="pj-meta mb-2" style="font-size:0.9rem;color:#555"><?= nl2br(htmlspecialchars($project['description'])) ?></div>
    <?php endif; ?>
    <div class="pj-meta">
        <?php if (!empty($project['start_date'])): ?><span>开始：<?= htmlspecialchars($project['start_date']) ?></span><?php endif; ?>
        <span>进度 <?= (int)$project['progress'] ?>%</span>
    </div>
    <div class="pj-progress-lg">
        <div class="bar" style="width:<?= (int)$project['progress'] ?>%;background:<?= $sColor ?>"></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-semibold">👥 成员 (<?= count($members) ?>)</span>
            <?php if ($isOwner): ?>
            <button class="btn btn-sm btn-outline-primary py-0" style="font-size:0.72rem" onclick="openMemberModal()">+ 添加</button>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($members as $m): ?>
            <span class="d-inline-flex align-items-center gap-1 small border rounded-pill px-2 py-1" style="font-size:0.78rem">
                <?= htmlspecialchars($m['nickname'] ?: $m['username']) ?>
                <?php if ($m['role'] === 'owner'): ?>
                <span class="badge bg-dark py-0" style="font-size:0.6rem">创建者</span>
                <?php endif; ?>
                <?php if ($isOwner && $m['role'] !== 'owner'): ?>
                <button class="btn btn-sm btn-link text-danger py-0 px-1" style="font-size:0.65rem;line-height:1" onclick="removeMember(<?= (int)$m['user_id'] ?>, '<?= htmlspecialchars(addslashes($m['nickname'] ?: $m['username'])) ?>')">✕</button>
                <?php endif; ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php $tasks = $project['tasks'] ?? []; if (!empty($tasks)): ?>
<?php $tasksDone = count(array_filter($tasks, fn($t) => !empty($t['done']))); ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-semibold">📝 待办任务 (<?= $tasksDone ?>/<?= count($tasks) ?>)</span>
            <?php if ($isOwner): ?>
            <a href="?route=project-list&edit=<?= (int)$project['id'] ?>" class="btn btn-link btn-sm py-0" style="font-size:0.72rem">编辑任务</a>
            <?php endif; ?>
        </div>
        <ul class="task-list">
            <?php foreach ($tasks as $i => $t): ?>
            <li class="task-item <?= !empty($t['done']) ? 'done' : '' ?>">
                <input type="checkbox" <?= !empty($t['done']) ? 'checked' : '' ?> onchange="toggleTask(<?= (int)$project['id'] ?>, <?= $i ?>)">
                <label><?= htmlspecialchars($t['text']) ?></label>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">📅 进度时间轴</h6>
    <button class="btn btn-sm btn-primary" onclick="openUpdateModal()">+ 添加进度</button>
</div>

<?php if (empty($updates)): ?>
<div class="tl-empty">
    <div style="font-size:2.5rem;margin-bottom:8px">📝</div>
    <div>暂无进度记录</div>
    <div class="small mt-1">点击"添加进度"记录你的项目进展</div>
</div>
<?php else: ?>
<div class="tl">
    <?php foreach ($updates as $u): ?>
    <div class="tl-item" id="update-<?= (int)$u['id'] ?>">
        <div class="tl-dot" style="background:<?= $sColor ?>"></div>
        <div class="tl-card">
            <div class="tl-date"><?= htmlspecialchars($u['update_date']) ?></div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="tl-title"><?= htmlspecialchars($u['title']) ?></span>
                <span class="tl-progress" style="background:<?= $sColor ?>20;color:<?= $sColor ?>">进度 <?= (int)$u['progress'] ?>%</span>
            </div>
            <?php if (!empty($u['content'])): ?>
            <div class="tl-content"><?= nl2br(htmlspecialchars($u['content'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($u['attachments'])): ?>
            <div class="tl-attach">
                <?php foreach ($u['attachments'] as $att): ?>
                <?php if (in_array($att['ext'] ?? '', ['jpg','jpeg','png','gif','webp'])): ?>
                <a href="/uploads/<?= htmlspecialchars($att['path']) ?>" target="_blank"><img src="/uploads/<?= htmlspecialchars($att['path']) ?>" alt="<?= htmlspecialchars($att['name']) ?>"></a>
                <?php else: ?>
                <a href="/uploads/<?= htmlspecialchars($att['path']) ?>" target="_blank">📎 <?= htmlspecialchars($att['name']) ?></a>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="tl-actions">
                <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.7rem" onclick='editUpdateBtn(<?= json_encode($u, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)'>编辑</button>
                <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.7rem" onclick="deleteUpdate(<?= (int)$u['id'] ?>)">删除</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title" id="updateModalTitle">添加进度</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="updateForm" enctype="multipart/form-data" onsubmit="return false;">
                <div class="modal-body">
                    <input type="hidden" name="action" id="upd_action" value="add_update">
                    <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                    <input type="hidden" name="update_id" id="upd_id">
                    <input type="hidden" name="keep_attachments" id="upd_keep_attach">
                    <div class="mb-2"><label class="form-label small">日期 <span class="text-danger">*</span></label><input type="date" name="update_date" id="upd_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-2"><label class="form-label small">标题 <span class="text-danger">*</span></label><input type="text" name="title" id="upd_title" class="form-control form-control-sm" required></div>
                    <div class="mb-2"><label class="form-label small">内容</label><textarea name="content" id="upd_content" class="form-control form-control-sm" rows="3"></textarea></div>
                    <div class="mb-2"><label class="form-label small">进度 (0-100%)</label><input type="number" name="progress" id="upd_progress" class="form-control form-control-sm" min="0" max="100" value="0"></div>
                    <div class="mb-2">
                        <label class="form-label small">项目状态</label>
                        <select name="project_status" id="upd_project_status" class="form-select form-select-sm">
                            <option value="">不变更</option>
                            <option value="planning">规划中</option>
                            <option value="active">进行中</option>
                            <option value="completed">已完成</option>
                            <option value="archived">已归档</option>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label small">附件（图片/文件，支持多选）</label><input type="file" name="attachments[]" id="upd_files" class="form-control form-control-sm" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"></div>
                    <div id="upd_existing_attach" class="small text-muted mt-1"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="submitUpdate()">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2"><h6 class="modal-title">添加成员</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="input-group input-group-sm mb-2">
                    <input type="text" id="memberSearchInput" class="form-control" placeholder="搜索用户名/昵称/邮箱/ID" autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" onclick="searchMembers()">搜索</button>
                </div>
                <div id="memberSearchResults" style="max-height:200px;overflow-y:auto"></div>
            </div>
        </div>
    </div>
</div>

<script>
var projectId = <?= (int)$project['id'] ?>;
function h(s) { return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function toast(msg, type) {
    var bg = type === 'error' ? '#dc3545' : '#198754';
    var el = document.createElement('div');
    el.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;background:'+bg+';color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,0.2);opacity:0;transition:opacity 0.2s';
    el.textContent = msg;
    document.body.appendChild(el);
    requestAnimationFrame(function(){ el.style.opacity = '1'; });
    setTimeout(function(){ el.style.opacity = '0'; setTimeout(function(){ el.remove(); }, 200); }, 2000);
}

function withLoading(btn, fn) {
    if (btn.disabled) return;
    btn.disabled = true;
    var orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>处理中';
    fn().finally(function() { btn.disabled = false; btn.innerHTML = orig; });
}

function toggleTask(pid, index) {
    fetch('/public/index.php?route=project-api', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=toggle_task&project_id=' + pid + '&task_index=' + index
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.ok) {
            var items = document.querySelectorAll('.task-item');
            if (items[index]) items[index].classList.toggle('done', d.done);
            var label = document.querySelector('.task-list')?.parentElement?.querySelector('.fw-semibold');
            if (label && d.tasks_total) label.textContent = '待办任务 (' + d.tasks_done + '/' + d.tasks_total + ')';
        }
    });
}

function openMemberModal() {
    document.getElementById('memberSearchInput').value = '';
    document.getElementById('memberSearchResults').innerHTML = '';
    new bootstrap.Modal(document.getElementById('memberModal')).show();
    setTimeout(function(){ document.getElementById('memberSearchInput').focus(); }, 300);
}

function searchMembers() {
    var kw = document.getElementById('memberSearchInput').value.trim();
    if (!kw) return;
    fetch('/public/index.php?route=project-api&action=search_member&q=' + encodeURIComponent(kw))
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.ok || !d.users.length) { document.getElementById('memberSearchResults').innerHTML = '<div class="small text-muted">未找到</div>'; return; }
        var html = '';
        d.users.forEach(function(u) {
            html += '<div class="d-flex align-items-center justify-content-between py-1 px-2 rounded" style="cursor:pointer;border:1px solid rgba(0,0,0,0.05);margin-bottom:2px" onmouseenter="this.style.background=\'rgba(102,126,234,0.08)\'" onmouseleave="this.style.background=\'\'" onclick="addMember(' + u.id + ',\'' + h(u.nickname||u.username) + '\')">';
            html += '<div><strong>' + h(u.username) + '</strong> <span class="text-muted small">(' + h(u.nickname||'') + ')</span></div>';
            html += '<span class="small text-muted">ID:' + u.id + '</span></div>';
        });
        document.getElementById('memberSearchResults').innerHTML = html;
    });
}

function addMember(uid, name) {
    if (!confirm('确定添加「' + name + '」为项目成员？')) return;
    var fd = new FormData();
    fd.append('action', 'add_member');
    fd.append('project_id', projectId);
    fd.append('user_id', uid);
    fetch('/public/index.php?route=project-api', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) { toast('已添加'); setTimeout(function(){ location.reload(); }, 500); }
        else alert(d.error || '添加失败');
    });
}

function removeMember(uid, name) {
    if (!confirm('确定移除成员「' + name + '」？')) return;
    fetch('/public/index.php?route=project-api', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=remove_member&project_id=' + projectId + '&user_id=' + uid
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.ok) { toast('已移除'); setTimeout(function(){ location.reload(); }, 500); }
        else alert(d.error);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var msi = document.getElementById('memberSearchInput');
    if (msi) msi.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); searchMembers(); } });
    document.querySelectorAll('.modal').forEach(function(m) {
        m.addEventListener('hide.bs.modal', function() { if (document.activeElement) document.activeElement.blur(); });
    });
});

function openUpdateModal() {
    document.getElementById('upd_action').value = 'add_update';
    document.getElementById('upd_id').value = '';
    document.getElementById('upd_date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('upd_title').value = '';
    document.getElementById('upd_content').value = '';
    document.getElementById('upd_progress').value = '0';
    document.getElementById('upd_files').value = '';
    document.getElementById('upd_keep_attach').value = '';
    document.getElementById('upd_existing_attach').innerHTML = '';
    document.getElementById('updateModalTitle').textContent = '添加进度';
    var statusSel = document.getElementById('upd_project_status');
    var currentStatus = '<?= $project['status'] ?>';
    statusSel.value = (currentStatus === 'planning') ? 'active' : '';
    new bootstrap.Modal(document.getElementById('updateModal')).show();
}

function editUpdateBtn(u) {
    document.getElementById('upd_action').value = 'edit_update';
    document.getElementById('upd_id').value = u.id;
    document.getElementById('upd_date').value = u.update_date;
    document.getElementById('upd_title').value = u.title;
    document.getElementById('upd_content').value = u.content || '';
    document.getElementById('upd_progress').value = u.progress;
    document.getElementById('upd_files').value = '';
    document.getElementById('updateModalTitle').textContent = '编辑进度';
    var keepAttach = (u.attachments && u.attachments.length) ? JSON.stringify(u.attachments) : '';
    document.getElementById('upd_keep_attach').value = keepAttach;
    var ehtml = '';
    if (u.attachments && u.attachments.length) {
        ehtml = '已有附件：' + u.attachments.map(function(a){ return a.name; }).join('、') + '（重新上传将追加）';
    }
    document.getElementById('upd_existing_attach').innerHTML = ehtml;
    new bootstrap.Modal(document.getElementById('updateModal')).show();
}

function submitUpdate() {
    var form = document.getElementById('updateForm');
    var btn = form.querySelector('.btn-primary');
    if (btn.disabled) return;
    btn.disabled = true;
    btn.textContent = '保存中...';
    var fd = new FormData(form);
    fetch('/public/index.php?route=project-api', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) { toast('已保存'); setTimeout(function(){ location.reload(); }, 500); }
        else { alert(d.error || '保存失败'); btn.disabled = false; btn.textContent = '保存'; }
    })
    .catch(function() { btn.disabled = false; btn.textContent = '保存'; });
}

function deleteUpdate(id) {
    if (!confirm('确定删除该进度记录？此操作不可恢复。')) return;
    var btn = event.target;
    btn.disabled = true;
    fetch('/public/index.php?route=project-api', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete_update&update_id=' + id
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.ok) { toast('已删除'); setTimeout(function(){ location.reload(); }, 500); }
        else { alert(d.error); btn.disabled = false; }
    });
}

function openProjectEdit() {
    location.href = '?route=project-list&edit=<?= (int)$project['id'] ?>';
}

function deleteProject(id, name) {
    if (!confirm('确定删除项目「' + name + '」？\n\n此操作将删除项目及所有进度记录，不可恢复！')) return;
    var btn = event.target;
    btn.disabled = true;
    fetch('/public/index.php?route=project-api', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete_project&id=' + id
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.ok) { toast('已删除'); setTimeout(function(){ location.href = '?route=project-list'; }, 500); }
        else { alert(d.error); btn.disabled = false; }
    });
}
</script>
