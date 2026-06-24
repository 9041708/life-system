<?php
/** @var array $space */
/** @var array $tree */
/** @var array|null $currentDoc */
/** @var int $currentDocId */
$siteUrl = \App\Service\Config::get('app.site_url', '');
?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/css/editormd.min.css">
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/lib/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/editormd.min.js"></script>
<style>
.kb-wrap { display:flex; height:calc(100vh - 60px); overflow:hidden; background:rgba(255,255,255,0.88); }
body.theme-dark .kb-wrap { background:rgba(15,23,42,0.88); }
.kb-sidebar { width:260px; min-width:260px; border-right:1px solid rgba(0,0,0,0.06); display:flex; flex-direction:column; background:rgba(255,255,255,0.6); backdrop-filter:blur(10px); }
body.theme-dark .kb-sidebar { background:rgba(30,41,59,0.6); border-right-color:rgba(148,163,184,0.12); }
.kb-sidebar-header { padding:10px 12px; border-bottom:1px solid rgba(0,0,0,0.06); display:flex; gap:4px; }
body.theme-dark .kb-sidebar-header { border-bottom-color:rgba(148,163,184,0.12); }
.kb-tree { flex:1; overflow-y:auto; padding:6px 0; }
.kb-tree-item { display:flex; align-items:center; gap:4px; padding:5px 12px; cursor:pointer; font-size:0.82rem; user-select:none; transition:background 0.1s; white-space:nowrap; }
.kb-tree-item:hover { background:rgba(102,126,234,0.08); }
.kb-tree-item.active { background:rgba(102,126,234,0.15); font-weight:600; }
.kb-tree-item .indent { display:inline-block; }
.kb-tree-item .icon { width:16px; text-align:center; font-size:0.75rem; flex-shrink:0; }
.kb-tree-item .label { flex:1; overflow:hidden; text-overflow:ellipsis; }
.kb-tree-item .actions { display:none; gap:2px; }
.kb-tree-item:hover .actions { display:flex; }
.kb-tree-item .actions button { border:none; background:none; padding:0 3px; cursor:pointer; font-size:0.7rem; color:#999; }
.kb-tree-item .actions button:hover { color:#667eea; }
.kb-editor-area { flex:1; display:flex; flex-direction:column; overflow:hidden; }
.kb-editor-toolbar { padding:6px 12px; border-bottom:1px solid rgba(0,0,0,0.06); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
body.theme-dark .kb-editor-toolbar { border-bottom-color:rgba(148,163,184,0.12); }
.kb-editor-toolbar .title-input { border:none; outline:none; font-size:1.1rem; font-weight:600; flex:1; min-width:200px; background:transparent; }
body.theme-dark .kb-editor-toolbar .title-input { color:#e2e8f0; }
.kb-editor-content { flex:1; overflow:hidden; }
.kb-status { font-size:0.72rem; padding:2px 8px; border-radius:10px; }
.kb-empty { display:flex; align-items:center; justify-content:center; height:100%; color:#999; font-size:0.9rem; }
.kb-context-menu { position:fixed; z-index:9999; background:#fff; border:1px solid rgba(0,0,0,0.1); border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,0.12); padding:4px 0; min-width:140px; }
body.theme-dark .kb-context-menu { background:#1e293b; border-color:rgba(148,163,184,0.2); }
.kb-context-menu button { display:block; width:100%; text-align:left; border:none; background:none; padding:6px 14px; font-size:0.82rem; cursor:pointer; }
.kb-context-menu button:hover { background:rgba(102,126,234,0.1); }
</style>

<div class="kb-wrap">
    <div class="kb-sidebar">
        <div class="kb-sidebar-header">
            <input type="text" id="kbSearch" class="form-control form-control-sm" placeholder="🔍 搜索文档..." style="font-size:0.78rem">
        </div>
        <div class="kb-tree" id="kbTree"></div>
        <div style="padding:6px 12px; border-top:1px solid rgba(0,0,0,0.06); display:flex; gap:4px;">
            <button class="btn btn-sm btn-outline-primary w-100" style="font-size:0.75rem" onclick="createDoc(0,0)">+ 新文档</button>
            <button class="btn btn-sm btn-outline-secondary w-100" style="font-size:0.75rem" onclick="createDoc(0,1)">+ 文件夹</button>
        </div>
    </div>
    <div class="kb-editor-area">
        <?php if ($currentDoc): ?>
        <div class="kb-editor-toolbar">
            <input type="text" class="title-input" id="docTitle" value="<?= htmlspecialchars($currentDoc['title']) ?>" placeholder="文档标题">
            <span class="kb-status bg-success text-white" id="saveStatus">✓ 已保存</span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">更多</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item" onclick="openMoveModal()">📁 移动到...</button></li>
                    <li><button class="dropdown-item" onclick="openVersionModal()">🕐 版本历史</button></li>
                    <li><button class="dropdown-item" onclick="toggleShare()">🔗 分享设置</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item text-danger" onclick="deleteCurrentDoc()">🗑 删除</button></li>
                </ul>
            </div>
            <a href="?route=kb-read&doc=<?= (int)$currentDoc['id'] ?>" class="btn btn-sm btn-outline-primary">👁 阅读</a>
            <button class="btn btn-sm btn-outline-secondary" onclick="openSpaceConfig()">⚙</button>
        </div>
        <div class="kb-editor-content">
            <div id="kbEditor"><textarea id="kbContent" style="display:none"><?= htmlspecialchars($currentDoc['content'] ?? '') ?></textarea></div>
        </div>
        <?php else: ?>
        <div class="kb-empty">
            <div class="text-center">
                <div style="font-size:3rem;margin-bottom:12px">📚</div>
                <div>选择左侧文档开始编辑，或创建新文档</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 移动弹窗 -->
<div class="modal fade" id="moveModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title">移动到</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="moveTreeBody" style="max-height:300px;overflow-y:auto"></div>
</div></div></div>

<!-- 版本历史弹窗 -->
<div class="modal fade" id="versionModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title">版本历史</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="versionList" style="max-height:400px;overflow-y:auto"><div class="text-muted small">加载中...</div></div>
</div></div></div>

<!-- 分享弹窗 -->
<div class="modal fade" id="shareModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title">分享文档</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="shareContent">
            <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control" id="shareUrl" readonly>
                <button class="btn btn-outline-primary" onclick="copyShareUrl()">复制</button>
            </div>
            <div class="small text-muted mb-2">分享此链接给他人，无需登录即可阅读。</div>
            <button class="btn btn-sm btn-outline-danger w-100" onclick="toggleShare()">取消分享</button>
        </div>
        <div id="shareOff" style="display:none">
            <div class="text-center text-muted py-3">当前未开启分享</div>
            <button class="btn btn-sm btn-primary w-100" onclick="toggleShare()">开启分享</button>
        </div>
    </div>
</div></div></div>

<!-- 空间配置弹窗 -->
<div class="modal fade" id="spaceConfigModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title">知识库设置</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label small">知识库名称</label><input type="text" id="cfgName" class="form-control form-control-sm" value="<?= htmlspecialchars($space['name']) ?>"></div>
        <div class="mb-2"><label class="form-label small">描述</label><textarea id="cfgDesc" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($space['description'] ?? '') ?></textarea></div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="cfgVerEnabled" <?= !empty($space['version_enabled']) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="cfgVerEnabled">开启版本历史</label>
        </div>
        <div class="mb-2"><label class="form-label small">最大版本数</label><input type="number" id="cfgVerMax" class="form-control form-control-sm" value="<?= (int)($space['version_max'] ?? 10) ?>" min="1" max="100"></div>
        <button class="btn btn-sm btn-primary w-100" onclick="saveSpaceConfig()">保存设置</button>
    </div>
</div></div></div>

<div id="kbContextMenu" class="kb-context-menu" style="display:none"></div>

<script>
var currentDocId = <?= (int)$currentDocId ?>;
var treeData = <?= json_encode($tree, JSON_UNESCAPED_UNICODE) ?>;
var editor = null;
var saveTimer = null;
var siteUrl = '<?= $siteUrl ?>';

function buildTree(parentId, depth) {
    var html = '';
    var items = treeData.filter(function(n){ return (parseInt(n.parent_id)||0) === parentId; });
    items.forEach(function(node) {
        var nid = parseInt(node.id);
        var isFolder = parseInt(node.is_folder);
        var pad = depth * 16;
        html += '<div class="kb-tree-item' + (nid === currentDocId ? ' active' : '') + '" data-id="' + nid + '" data-folder="' + isFolder + '" style="padding-left:' + (12+pad) + 'px" onclick="clickNode(' + nid + ',' + isFolder + ')" oncontextmenu="showCtx(event,' + nid + ',' + isFolder + ')">';
        html += '<span class="icon">' + (isFolder ? '📁' : '📄') + '</span>';
        html += '<span class="label">' + h(node.title) + '</span>';
        html += '<span class="actions">';
        if (isFolder) html += '<button onclick="event.stopPropagation();createDoc(' + nid + ',0)" title="新建文档">+</button>';
        html += '<button onclick="event.stopPropagation();showCtx(event,' + nid + ',' + isFolder + ')" title="更多">⋯</button>';
        html += '</span></div>';
        if (isFolder) html += buildTree(nid, depth + 1);
    });
    return html;
}

function renderTree() { document.getElementById('kbTree').innerHTML = buildTree(0, 0); }

function clickNode(id, isFolder) {
    if (isFolder) return;
    window.location.href = '?route=kb-editor&doc=' + id;
}

function h(s) { return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Context menu
var ctxMenu = document.getElementById('kbContextMenu');
document.addEventListener('click', function(){ ctxMenu.style.display='none'; });

function showCtx(e, id, isFolder) {
    e.preventDefault(); e.stopPropagation();
    var html = '';
    if (isFolder) {
        html += '<button onclick="createDoc('+id+',0)">📄 新建文档</button>';
        html += '<button onclick="createDoc('+id+',1)">📁 新建子文件夹</button>';
    }
    html += '<button onclick="renameDoc('+id+')">✏️ 重命名</button>';
    html += '<button onclick="moveDoc('+id+')">📁 移动到...</button>';
    html += '<button onclick="deleteDoc('+id+')" style="color:#ef4444">🗑 删除</button>';
    ctxMenu.innerHTML = html;
    ctxMenu.style.display = 'block';
    ctxMenu.style.left = Math.min(e.clientX, window.innerWidth - 160) + 'px';
    ctxMenu.style.top = Math.min(e.clientY, window.innerHeight - 200) + 'px';
}

function createDoc(parentId, isFolder) {
    ctxMenu.style.display = 'none';
    var title = isFolder ? '新文件夹' : '无标题';
    $.post('/public/index.php?route=kb-api', {action:'create_doc', parent_id:parentId, is_folder:isFolder, title:title}, function(d){
        if (d.ok) {
            if (!isFolder) window.location.href = '?route=kb-editor&doc=' + d.id;
            else { refreshTree(); }
        } else alert(d.error);
    }, 'json');
}

function renameDoc(id) {
    ctxMenu.style.display = 'none';
    var node = treeData.find(function(n){ return parseInt(n.id)===id; });
    if (!node) return;
    var name = prompt('重命名', node.title);
    if (name === null || name.trim() === '') return;
    $.post('/public/index.php?route=kb-api', {action:'update_doc', id:id, title:name.trim()}, function(d){
        if (d.ok) refreshTree(); else alert(d.error);
    }, 'json');
}

function deleteDoc(id) {
    ctxMenu.style.display = 'none';
    if (!confirm('确定删除？子文档也会一并删除。')) return;
    $.post('/public/index.php?route=kb-api', {action:'delete_doc', id:id}, function(d){
        if (d.ok) {
            if (id === currentDocId) window.location.href = '?route=kb-editor';
            else refreshTree();
        } else alert(d.error);
    }, 'json');
}

function deleteCurrentDoc() {
    if (!currentDocId) return;
    if (!confirm('确定删除当前文档？')) return;
    $.post('/public/index.php?route=kb-api', {action:'delete_doc', id:currentDocId}, function(d){
        if (d.ok) window.location.href = '?route=kb-editor';
        else alert(d.error);
    }, 'json');
}

function refreshTree() {
    $.post('/public/index.php?route=kb-api', {action:'get_tree'}, function(d){
        if (d.ok) { treeData = d.tree; renderTree(); }
    }, 'json');
}

// Auto save
function autoSave() {
    if (!currentDocId || !editor) return;
    $('#saveStatus').text('● 未保存').removeClass('bg-success').addClass('bg-warning text-dark');
    clearTimeout(saveTimer);
    saveTimer = setTimeout(doSave, 2000);
}

function doSave() {
    if (!currentDocId || !editor) return;
    var md = editor.getMarkdown();
    var html = editor.getHTML();
    var title = $('#docTitle').val() || '无标题';
    $.post('/public/index.php?route=kb-api', {action:'update_doc', id:currentDocId, title:title, content:md, content_html:html}, function(d){
        if (d.ok) $('#saveStatus').text('✓ 已保存').removeClass('bg-warning text-dark').addClass('bg-success');
    }, 'json');
}

// Move
var moveTargetId = 0;
function moveDoc(id) {
    ctxMenu.style.display = 'none';
    moveTargetId = id;
    var html = '<div class="kb-tree-item" onclick="doMove(0)" style="cursor:pointer"><span class="icon">📂</span><span class="label">根目录</span></div>';
    treeData.forEach(function(n){
        if (parseInt(n.is_folder) && parseInt(n.id) !== id) {
            html += '<div class="kb-tree-item" onclick="doMove('+n.id+')" style="cursor:pointer"><span class="icon">📁</span><span class="label">'+h(n.title)+'</span></div>';
        }
    });
    document.getElementById('moveTreeBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('moveModal')).show();
}

function openMoveModal() { if (currentDocId) moveDoc(currentDocId); }

function doMove(parentId) {
    $.post('/public/index.php?route=kb-api', {action:'update_doc', id:moveTargetId, parent_id:parentId}, function(d){
        if (d.ok) { bootstrap.Modal.getInstance(document.getElementById('moveModal')).hide(); refreshTree(); }
        else alert(d.error);
    }, 'json');
}

// Share
function toggleShare() {
    var id = currentDocId || moveTargetId;
    if (!id) return;
    $.post('/public/index.php?route=kb-api', {action:'toggle_share', id:id}, function(d){
        if (d.ok) {
            if (d.shared) {
                document.getElementById('shareUrl').value = d.url;
                document.getElementById('shareContent').style.display = '';
                document.getElementById('shareOff').style.display = 'none';
            } else {
                document.getElementById('shareContent').style.display = 'none';
                document.getElementById('shareOff').style.display = '';
            }
            if (document.getElementById('shareModal').classList.contains('show')) return;
            new bootstrap.Modal(document.getElementById('shareModal')).show();
        }
    }, 'json');
}

function copyShareUrl() {
    var inp = document.getElementById('shareUrl');
    inp.select(); document.execCommand('copy');
    alert('已复制');
}

// Version history
function openVersionModal() {
    if (!currentDocId) return;
    new bootstrap.Modal(document.getElementById('versionModal')).show();
    $.post('/public/index.php?route=kb-api', {action:'get_versions', doc_id:currentDocId}, function(d){
        if (!d.ok || !d.versions.length) {
            document.getElementById('versionList').innerHTML = '<div class="text-muted small text-center py-3">暂无版本记录</div>';
            return;
        }
        var html = '';
        d.versions.forEach(function(v){
            html += '<div class="d-flex justify-content-between align-items-center py-2 border-bottom">';
            html += '<div><strong>v'+v.version_num+'</strong> <span class="text-muted small ms-2">'+v.created_at+'</span></div>';
            html += '<button class="btn btn-sm btn-outline-primary py-0" onclick="restoreVersion('+v.id+')">恢复</button>';
            html += '</div>';
        });
        document.getElementById('versionList').innerHTML = html;
    }, 'json');
}

function restoreVersion(verId) {
    if (!confirm('确定恢复到此版本？当前内容将被覆盖。')) return;
    $.post('/public/index.php?route=kb-api', {action:'restore_version', version_id:verId}, function(d){
        if (d.ok) {
            if (editor) editor.setMarkdown(d.content || '');
            if (d.title) $('#docTitle').val(d.title);
            bootstrap.Modal.getInstance(document.getElementById('versionModal')).hide();
            doSave();
        } else alert(d.error);
    }, 'json');
}

// Space config
function openSpaceConfig() { new bootstrap.Modal(document.getElementById('spaceConfigModal')).show(); }

function saveSpaceConfig() {
    $.post('/public/index.php?route=kb-api', {
        action:'save_space_config',
        name: $('#cfgName').val(),
        description: $('#cfgDesc').val(),
        version_enabled: $('#cfgVerEnabled').is(':checked') ? 1 : 0,
        version_max: $('#cfgVerMax').val()
    }, function(d){
        if (d.ok) { bootstrap.Modal.getInstance(document.getElementById('spaceConfigModal')).hide(); }
        else alert(d.error);
    }, 'json');
}

// Search
var searchTimer = null;
document.getElementById('kbSearch').addEventListener('input', function(){
    clearTimeout(searchTimer);
    var kw = this.value.trim();
    if (!kw) { renderTree(); return; }
    searchTimer = setTimeout(function(){
        $.post('/public/index.php?route=kb-api', {action:'search', q:kw}, function(d){
            if (!d.ok) return;
            var html = '';
            d.results.forEach(function(r){
                var isF = parseInt(r.is_folder);
                html += '<div class="kb-tree-item" onclick="clickNode('+r.id+','+isF+')">';
                html += '<span class="icon">'+(isF?'📁':'📄')+'</span>';
                html += '<span class="label">'+h(r.title)+'</span></div>';
            });
            if (!html) html = '<div class="text-muted small text-center py-3">无结果</div>';
            document.getElementById('kbTree').innerHTML = html;
        }, 'json');
    }, 300);
});

// Init editor
$(function(){
    renderTree();
    <?php if ($currentDoc): ?>
    editor = editormd('kbEditor', {
        width: '100%',
        height: 'calc(100vh - 120px)',
        path: 'https://cdn.jsdelivr.net/npm/editor.md@1.5.0/lib/',
        markdown: <?= json_encode($currentDoc['content'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        theme: 'default',
        previewTheme: 'default',
        editorTheme: 'default',
        codeFold: true,
        syncScrolling: true,
        saveHTMLToTextarea: true,
        searchReplace: true,
        watch: true,
        toolbar: true,
        placeholder: '开始写文档...',
        imageUpload: true,
        imageUploadURL: '/public/index.php?route=kb-api',
        imageUploadField: 'image',
        flowChart: false,
        sequenceDiagram: false,
        onchange: function() { autoSave(); }
    });
    $('#docTitle').on('input', function(){ autoSave(); });
    <?php endif; ?>
});
</script>
