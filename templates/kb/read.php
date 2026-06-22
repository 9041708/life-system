<?php
/** @var array $space */
/** @var array $tree */
/** @var array|null $currentDoc */
?>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/lib/marked.min.js"></script>
<style>
.kb-read-wrap { display:flex; height:calc(100vh - 60px); overflow:hidden; background:rgba(255,255,255,0.88); }
body.theme-dark .kb-read-wrap { background:rgba(15,23,42,0.88); }
.kb-read-sidebar { width:240px; min-width:240px; border-right:1px solid rgba(0,0,0,0.06); display:flex; flex-direction:column; background:rgba(255,255,255,0.6); backdrop-filter:blur(10px); }
body.theme-dark .kb-read-sidebar { background:rgba(30,41,59,0.6); border-right-color:rgba(148,163,184,0.12); }
.kb-read-tree { flex:1; overflow-y:auto; padding:6px 0; }
.kb-read-tree .node { display:flex; align-items:center; gap:4px; padding:5px 12px; cursor:pointer; font-size:0.82rem; white-space:nowrap; text-decoration:none; color:inherit; transition:background 0.1s; }
.kb-read-tree .node:hover { background:rgba(102,126,234,0.08); }
.kb-read-tree .node.active { background:rgba(102,126,234,0.15); font-weight:600; }
.kb-read-main { flex:1; display:flex; overflow:hidden; }
.kb-read-content { flex:1; overflow-y:auto; padding:32px 48px; }
.kb-read-toc { width:200px; min-width:200px; border-left:1px solid rgba(0,0,0,0.06); overflow-y:auto; padding:20px 12px; position:sticky; top:0; height:calc(100vh - 60px); }
body.theme-dark .kb-read-toc { border-left-color:rgba(148,163,184,0.12); }
.kb-read-toc .toc-title { font-size:0.75rem; color:#999; margin-bottom:8px; font-weight:600; text-transform:uppercase; }
.kb-read-toc a { display:block; font-size:0.78rem; color:#666; text-decoration:none; padding:3px 0 3px 8px; border-left:2px solid transparent; transition:all 0.15s; line-height:1.4; }
body.theme-dark .kb-read-toc a { color:#94a3b8; }
.kb-read-toc a:hover, .kb-read-toc a.active { color:#667eea; border-left-color:#667eea; }
.kb-read-toc a.toc-h3 { padding-left:20px; }
.kb-read-toc a.toc-h4 { padding-left:32px; font-size:0.72rem; }
.kb-article h1 { font-size:1.8rem; font-weight:700; margin:24px 0 12px; padding-bottom:8px; border-bottom:2px solid rgba(0,0,0,0.06); }
body.theme-dark .kb-article h1 { border-bottom-color:rgba(148,163,184,0.15); }
.kb-article h2 { font-size:1.4rem; font-weight:600; margin:20px 0 10px; }
.kb-article h3 { font-size:1.15rem; font-weight:600; margin:16px 0 8px; }
.kb-article h4 { font-size:1rem; font-weight:600; margin:12px 0 6px; }
.kb-article p { line-height:1.8; margin-bottom:12px; }
.kb-article pre { background:#f6f8fa; padding:16px; border-radius:8px; overflow-x:auto; font-size:0.85rem; }
body.theme-dark .kb-article pre { background:#0f172a; }
.kb-article code { font-size:0.85em; }
.kb-article p code { background:rgba(102,126,126,0.1); padding:2px 6px; border-radius:4px; }
.kb-article blockquote { border-left:4px solid #667eea; padding:8px 16px; margin:12px 0; background:rgba(102,126,234,0.04); border-radius:0 8px 8px 0; }
.kb-article table { width:100%; border-collapse:collapse; margin:12px 0; }
.kb-article th, .kb-article td { border:1px solid rgba(0,0,0,0.1); padding:8px 12px; text-align:left; font-size:0.88rem; }
body.theme-dark .kb-article th, body.theme-dark .kb-article td { border-color:rgba(148,163,184,0.15); }
.kb-article th { background:rgba(0,0,0,0.03); font-weight:600; }
body.theme-dark .kb-article th { background:rgba(148,163,184,0.08); }
.kb-article img { max-width:100%; border-radius:8px; margin:8px 0; }
.kb-article hr { border:none; border-top:1px solid rgba(0,0,0,0.1); margin:20px 0; }
body.theme-dark .kb-article hr { border-top-color:rgba(148,163,184,0.15); }
.kb-breadcrumb { font-size:0.78rem; color:#999; margin-bottom:16px; }
</style>

<div class="kb-read-wrap">
    <div class="kb-read-sidebar">
        <div style="padding:10px 12px; border-bottom:1px solid rgba(0,0,0,0.06); display:flex; align-items:center; justify-content:space-between;">
            <span class="fw-semibold small">📚 <?= htmlspecialchars($space['name']) ?></span>
            <a href="?route=kb-editor" class="btn btn-sm btn-outline-primary py-0" style="font-size:0.7rem">编辑</a>
        </div>
        <div class="kb-read-tree" id="readTree"></div>
    </div>
    <div class="kb-read-main">
        <?php if ($currentDoc): ?>
        <div class="kb-read-content" id="readContent">
            <div class="kb-breadcrumb" id="breadcrumb"></div>
            <h1 style="border:none;margin:0 0 20px;padding:0"><?= htmlspecialchars($currentDoc['title']) ?></h1>
            <div class="kb-article" id="articleBody"></div>
        </div>
        <div class="kb-read-toc" id="tocPanel">
            <div class="toc-title">目录</div>
            <div id="tocList"></div>
        </div>
        <?php else: ?>
        <div style="flex:1;display:flex;align-items:center;justify-content:center;color:#999">
            <div class="text-center">
                <div style="font-size:3rem;margin-bottom:12px">📄</div>
                <div>选择左侧文档开始阅读</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
var treeData = <?= json_encode($tree, JSON_UNESCAPED_UNICODE) ?>;
var currentDocId = <?= (int)($currentDoc['id'] ?? 0) ?>;

function buildReadTree(parentId, depth) {
    var html = '';
    var items = treeData.filter(function(n){ return (parseInt(n.parent_id)||0) === parentId; });
    items.forEach(function(node) {
        var nid = parseInt(node.id);
        var isFolder = parseInt(node.is_folder);
        var pad = depth * 14;
        var cls = 'node' + (nid === currentDocId ? ' active' : '');
        if (isFolder) {
            html += '<div class="' + cls + '" style="padding-left:' + (12+pad) + 'px"><span style="width:14px;text-align:center;font-size:0.7rem">📁</span><span style="flex:1;overflow:hidden;text-overflow:ellipsis">' + h(node.title) + '</span></div>';
            html += buildReadTree(nid, depth + 1);
        } else {
            html += '<a href="?route=kb-read&doc=' + nid + '" class="' + cls + '" style="padding-left:' + (12+pad) + 'px"><span style="width:14px;text-align:center;font-size:0.7rem">📄</span><span style="flex:1;overflow:hidden;text-overflow:ellipsis">' + h(node.title) + '</span></a>';
        }
    });
    return html;
}

function h(s) { return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('readTree').innerHTML = buildReadTree(0, 0);

    <?php if ($currentDoc): ?>
    var mdContent = <?= json_encode($currentDoc['content'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
    var html = marked.parse(mdContent);
    document.getElementById('articleBody').innerHTML = html;

    // Build TOC
    var headings = document.querySelectorAll('.kb-article h1, .kb-article h2, .kb-article h3, .kb-article h4');
    var tocHtml = '';
    headings.forEach(function(hEl, i) {
        var id = 'heading-' + i;
        hEl.id = id;
        var level = parseInt(hEl.tagName.charAt(1));
        var cls = level >= 3 ? ' toc-h' + level : '';
        tocHtml += '<a href="#' + id + '" class="' + cls + '" data-target="' + id + '">' + hEl.textContent + '</a>';
    });
    document.getElementById('tocList').innerHTML = tocHtml || '<div class="text-muted small">无目录</div>';

    // Scrollspy
    var tocLinks = document.querySelectorAll('#tocList a');
    var contentEl = document.getElementById('readContent');
    contentEl.addEventListener('scroll', function() {
        var scrollPos = contentEl.scrollTop + 80;
        var active = null;
        headings.forEach(function(hEl) {
            if (hEl.offsetTop <= scrollPos) active = hEl.id;
        });
        tocLinks.forEach(function(a) {
            a.classList.toggle('active', a.getAttribute('data-target') === active);
        });
    });
    <?php endif; ?>
});
</script>
