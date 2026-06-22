<?php
/** @var array $projects */
/** @var string $currentStatus */

$statusLabels = ['all' => '全部', 'planning' => '规划中', 'active' => '进行中', 'completed' => '已完成', 'archived' => '已归档'];
$statusColors = ['planning' => '#6b7280', 'active' => '#3b82f6', 'completed' => '#22c55e', 'archived' => '#9ca3af'];
?>
<style>
.pj-card {
    border: 1px solid rgba(0,0,0,0.06); border-radius: 12px; padding: 18px;
    background: rgba(255,255,255,0.65); backdrop-filter: blur(12px);
    transition: all 0.2s; cursor: pointer; margin-bottom: 12px;
}
body.theme-dark .pj-card { background: rgba(30,41,59,0.5); border-color: rgba(148,163,184,0.12); }
.pj-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-1px); }
.pj-card .pj-name { font-weight: 600; font-size: 1rem; margin-bottom: 4px; }
.pj-card .pj-desc { font-size: 0.82rem; color: #666; margin-bottom: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
body.theme-dark .pj-card .pj-desc { color: #94a3b8; }
.pj-progress { height: 6px; border-radius: 3px; background: rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 8px; }
body.theme-dark .pj-progress { background: rgba(148,163,184,0.15); }
.pj-progress-bar { height: 100%; border-radius: 3px; transition: width 0.3s; }
.pj-meta { font-size: 0.75rem; color: #999; display: flex; align-items: center; gap: 12px; }
.pj-badge { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 500; }
.pj-empty { text-align: center; padding: 60px 20px; color: #999; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">📂 项目</h5>
    <button class="btn btn-primary btn-sm" onclick="openProjectModal()">+ 新建项目</button>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <?php foreach ($statusLabels as $k => $v): ?>
    <a href="?route=project-list&status=<?= $k ?>" class="btn btn-sm <?= $currentStatus === $k ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $v ?></a>
    <?php endforeach; ?>
</div>

<div class="row" id="projectList">
<?php if (empty($projects)): ?>
    <div class="col-12 pj-empty">
        <div style="font-size:3rem;margin-bottom:8px">📂</div>
        <div>暂无项目</div>
        <div class="small mt-1">点击右上角"新建项目"开始</div>
    </div>
<?php else: ?>
    <?php foreach ($projects as $p): ?>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="pj-card" onclick="location.href='?route=project-detail&id=<?= (int)$p['id'] ?>'">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="pj-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="d-flex gap-1 align-items-center">
                    <?php if (($p['my_role'] ?? '') !== 'owner'): ?>
                    <span class="pj-badge" style="background:#f59e0b20;color:#b45309">协作</span>
                    <?php endif; ?>
                    <span class="pj-badge" style="background:<?= $statusColors[$p['status']] ?? '#6b7280' ?>20;color:<?= $statusColors[$p['status']] ?? '#6b7280' ?>"><?= $statusLabels[$p['status']] ?? $p['status'] ?></span>
                </div>
            </div>
            <?php if (!empty($p['description'])): ?>
            <div class="pj-desc"><?= htmlspecialchars($p['description']) ?></div>
            <?php endif; ?>
            <div class="pj-progress">
                <div class="pj-progress-bar" style="width:<?= (int)$p['progress'] ?>%;background:<?= $statusColors[$p['status']] ?? '#3b82f6' ?>"></div>
            </div>
            <div class="pj-meta">
                <span>进度 <?= (int)$p['progress'] ?>%</span>
                <?php if ($p['tasks_total'] > 0): ?><span>任务 <?= $p['tasks_done'] ?>/<?= $p['tasks_total'] ?></span><?php endif; ?>
                <span>动态 <?= (int)$p['update_count'] ?></span>
                <?php if (!empty($p['start_date'])): ?><span><?= htmlspecialchars($p['start_date']) ?></span><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<div class="modal fade" id="projectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title" id="projectModalTitle">新建项目</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" style="max-height:70vh;overflow-y:auto">
                <input type="hidden" id="pj_id">
                <div class="mb-2"><label class="form-label small">项目名称 <span class="text-danger">*</span></label><input type="text" id="pj_name" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label small">描述</label><textarea id="pj_desc" class="form-control form-control-sm" rows="2"></textarea></div>
                <div class="mb-2">
                    <label class="form-label small d-flex justify-content-between">
                        <span>📝 待办任务（每行一个）</span>
                        <button type="button" class="btn btn-link btn-sm py-0" onclick="addTaskLine()">+ 添加</button>
                    </label>
                    <div id="pj_tasks_container"></div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-4"><label class="form-label small">状态</label>
                        <select id="pj_status" class="form-select form-select-sm">
                            <option value="planning">规划中</option>
                            <option value="active">进行中</option>
                            <option value="completed">已完成</option>
                            <option value="archived">已归档</option>
                        </select>
                    </div>
                    <div class="col-4"><label class="form-label small">开始日期</label><input type="date" id="pj_start" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
                </div>
                <div id="newProjectExtras">
                    <hr class="my-2">
                    <div class="mb-2">
                        <label class="form-label small">📎 初始附件（可选，创建后自动记录为第一条进度）</label>
                        <input type="file" id="pj_files" class="form-control form-control-sm" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">👥 添加协作者（可选）</label>
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" id="pj_member_search" class="form-control" placeholder="搜索用户名/昵称/ID" autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" onclick="searchMembersForNew()">搜索</button>
                        </div>
                        <div id="pj_member_results" style="max-height:150px;overflow-y:auto"></div>
                        <div id="pj_selected_members" class="d-flex flex-wrap gap-1 mt-1"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="saveProject()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
var projectData = <?= json_encode($projects, JSON_UNESCAPED_UNICODE) ?>;
var selectedMembers = [];

function addTaskLine(text, done) {
    var container = document.getElementById('pj_tasks_container');
    var div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML = '<input type="text" class="form-control form-control-sm pj-task-input" placeholder="任务内容" value="' + h(text||'') + '">' +
        '<button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">✕</button>';
    container.appendChild(div);
}

function collectTasks() {
    var inputs = document.querySelectorAll('.pj-task-input');
    var tasks = [];
    inputs.forEach(function(inp) {
        var text = inp.value.trim();
        if (text) tasks.push({ text: text, done: false });
    });
    return JSON.stringify(tasks);
}

function openProjectModal(id) {
    document.getElementById('pj_id').value = '';
    document.getElementById('pj_name').value = '';
    document.getElementById('pj_desc').value = '';
    document.getElementById('pj_status').value = 'planning';
    document.getElementById('pj_start').value = '<?= date('Y-m-d') ?>';
    document.getElementById('pj_files').value = '';
    document.getElementById('pj_member_search').value = '';
    document.getElementById('pj_member_results').innerHTML = '';
    document.getElementById('pj_tasks_container').innerHTML = '';
    selectedMembers = [];
    renderSelectedMembers();
    document.getElementById('projectModalTitle').textContent = '新建项目';
    document.getElementById('newProjectExtras').style.display = '';
    addTaskLine();
    if (id) {
        var p = projectData.find(function(x){ return x.id == id; });
        if (p) {
            document.getElementById('pj_id').value = p.id;
            document.getElementById('pj_name').value = p.name;
            document.getElementById('pj_desc').value = p.description || '';
            document.getElementById('pj_status').value = p.status;
            document.getElementById('pj_start').value = p.start_date || '';
            document.getElementById('projectModalTitle').textContent = '编辑项目';
            document.getElementById('newProjectExtras').style.display = 'none';
            document.getElementById('pj_tasks_container').innerHTML = '';
            if (p.tasks && p.tasks.length) {
                p.tasks.forEach(function(t) { addTaskLine(t.text); });
            }
        }
    }
    new bootstrap.Modal(document.getElementById('projectModal')).show();
}

function searchMembersForNew() {
    var kw = document.getElementById('pj_member_search').value.trim();
    if (!kw) return;
    fetch('/public/index.php?route=project-api&action=search_member&q=' + encodeURIComponent(kw))
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.ok || !d.users.length) { document.getElementById('pj_member_results').innerHTML = '<div class="small text-muted">未找到</div>'; return; }
        var html = '';
        d.users.forEach(function(u) {
            var already = selectedMembers.some(function(m){ return m.id === u.id; });
            html += '<div class="d-flex align-items-center justify-content-between py-1 px-2 rounded" style="cursor:' + (already ? 'default' : 'pointer') + ';border:1px solid rgba(0,0,0,0.05);margin-bottom:2px;opacity:' + (already ? '0.5' : '1') + '" ' + (already ? '' : 'onmouseenter="this.style.background=\'rgba(102,126,234,0.08)\'" onmouseleave="this.style.background=\'\'" onclick="addNewMember(' + u.id + ',\'' + h(u.nickname||u.username) + '\')"') + '>';
            html += '<div><strong>' + h(u.username) + '</strong> <span class="text-muted small">(' + h(u.nickname||'') + ')</span></div>';
            html += '<span class="small text-muted">' + (already ? '已选' : 'ID:' + u.id) + '</span></div>';
        });
        document.getElementById('pj_member_results').innerHTML = html;
    });
}

function addNewMember(uid, name) {
    if (selectedMembers.some(function(m){ return m.id === uid; })) return;
    selectedMembers.push({ id: uid, name: name });
    renderSelectedMembers();
}

function removeNewMember(uid) {
    selectedMembers = selectedMembers.filter(function(m){ return m.id !== uid; });
    renderSelectedMembers();
}

function renderSelectedMembers() {
    var html = '';
    selectedMembers.forEach(function(m) {
        html += '<span class="badge bg-light text-dark border d-flex align-items-center gap-1">' + h(m.name) + ' <button type="button" class="btn-close" style="font-size:0.5rem" onclick="removeNewMember(' + m.id + ')"></button></span>';
    });
    document.getElementById('pj_selected_members').innerHTML = html;
}

function h(s) { return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function saveProject() {
    var id = document.getElementById('pj_id').value;
    var name = document.getElementById('pj_name').value.trim();
    if (!name) { alert('请输入项目名称'); return; }
    var btn = document.querySelector('#projectModal .btn-primary');
    if (btn.disabled) return;
    btn.disabled = true;
    btn.textContent = '保存中...';
    var fd = new FormData();
    fd.append('action', id ? 'update_project' : 'create_project');
    if (id) fd.append('id', id);
    fd.append('name', name);
    fd.append('description', document.getElementById('pj_desc').value.trim());
    fd.append('tasks', collectTasks());
    fd.append('status', document.getElementById('pj_status').value);
    fd.append('start_date', document.getElementById('pj_start').value);
    if (!id && selectedMembers.length) {
        fd.append('member_ids', selectedMembers.map(function(m){ return m.id; }).join(','));
    }
    if (!id) {
        var files = document.getElementById('pj_files').files;
        for (var i = 0; i < files.length; i++) {
            fd.append('attachments[]', files[i]);
        }
    }
    fetch('/public/index.php?route=project-api', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            if (!id && d.id) { location.href = '?route=project-detail&id=' + d.id; }
            else { location.reload(); }
        }
        else { alert(d.error || '保存失败'); btn.disabled = false; btn.textContent = '保存'; }
    })
    .catch(function() { btn.disabled = false; btn.textContent = '保存'; });
}

document.addEventListener('DOMContentLoaded', function() {
    var msi = document.getElementById('pj_member_search');
    if (msi) msi.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); searchMembersForNew(); } });
<?php if (!empty($_GET['edit'])): ?>
    openProjectModal(<?= (int)$_GET['edit'] ?>);
<?php endif; ?>
});

function deleteProject(id, name) {
    if (!confirm('确定删除项目「' + name + '」及其所有进度记录？')) return;
    fetch('/public/index.php?route=project-api', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete_project&id=' + id
    }).then(function(r) { return r.json(); }).then(function(d) { if (d.ok) location.reload(); else alert(d.error); });
}
</script>
