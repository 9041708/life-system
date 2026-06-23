<?php
/**
 * 知识库 - 编辑器页面
 *
 * @var array  $tree
 * @var array  $breadcrumb
 * @var array  $spaces
 * @var array  $currentSpace
 * @var array  $currentDoc
 * @var int    $docId
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $currentDoc ? htmlspecialchars($currentDoc['title']) : '新文档' ?> - 知识库</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/css/editormd.min.css">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; }
        .kb-header { display: flex; align-items: center; padding: 8px 16px; background: #fff; border-bottom: 1px solid #e8e8e8; gap: 12px; }
        .kb-header .back { color: #1890ff; text-decoration: none; font-size: 14px; }
        .kb-header input.title { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; font-size: 16px; }
        .kb-header .saved { color: #52c41a; font-size: 12px; }
        .kb-layout { display: flex; height: calc(100vh - 49px); }
        .kb-sidebar { width: 260px; background: #fff; border-right: 1px solid #e8e8e8; overflow-y: auto; flex-shrink: 0; }
        .kb-sidebar .tree-item { padding: 8px 12px; cursor: pointer; user-select: none; }
        .kb-sidebar .tree-item:hover { background: #f0f0f0; }
        .kb-sidebar .tree-item.active { background: #e6f7ff; color: #1890ff; }
        .kb-sidebar .tree-item.indent-1 { padding-left: 28px; }
        .kb-sidebar .tree-item.indent-2 { padding-left: 44px; }
        .kb-sidebar .tree-item.indent-3 { padding-left: 60px; }
        .kb-sidebar .add-doc { padding: 8px 12px; color: #1890ff; cursor: pointer; font-size: 13px; }
        .kb-main { flex: 1; overflow: hidden; }
        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #999; }
        .empty-state a { color: #1890ff; }
    </style>
</head>
<body>
<div class="kb-header">
    <a class="back" href="/public/index.php?route=kb">&larr; 返回知识库</a>
    <input class="title" id="docTitle" value="<?= $currentDoc ? htmlspecialchars($currentDoc['title']) : '' ?>" placeholder="文档标题" />
    <span class="saved" id="saveStatus"></span>
</div>
<div class="kb-layout">
    <div class="kb-sidebar">
        <div class="add-doc" onclick="addDoc()">+ 新建文档</div>
        <div id="docTree"></div>
    </div>
    <div class="kb-main">
        <?php if ($currentDoc): ?>
        <div id="kbEditor"></div>
        <?php else: ?>
        <div class="empty-state">
            <p>请从左侧选择一篇文档，或<a href="javascript:addDoc()">新建文档</a></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@1.12.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/editormd.min.js"></script>
<script src="/public/plugins/image-handle-paste/image-handle-paste.js"></script>
<script>
var currentDocId = <?= $docId ?: 0 ?>;
var autoSaveTimer = null;
var lastContent = '';
var lastTitle = '';

// ========== 粘贴上传图片（独立实现，不依赖 Editor.md 内部） ==========
// 插件 image-handle-paste 已在页面中引入，初始化后启用

function autoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(function() {
        var title = $('#docTitle').val();
        var content = editor ? editor.codemirror.getValue() : '';
        if (title === lastTitle && content === lastContent) return;

        $('#saveStatus').text('保存中...');
        $.ajax({
            url: '/public/index.php?route=kb-api&action=save_doc&id=' + currentDocId,
            type: 'POST',
            data: { title: title, content: content },
            dataType: 'json',
            success: function(d) {
                if (d.success) {
                    lastTitle = title;
                    lastContent = content;
                    $('#saveStatus').text('已保存');
                    if (d.doc_id && d.doc_id !== currentDocId) {
                        currentDocId = d.doc_id;
                        history.replaceState(null, '', '/public/index.php?route=kb-editor&id=' + currentDocId);
                    }
                    setTimeout(function() { $('#saveStatus').text(''); }, 2000);
                } else {
                    $('#saveStatus').text('保存失败');
                }
            },
            error: function() { $('#saveStatus').text('保存失败'); }
        });
    }, 1000);
}

// ========== 文档树 ==========
function renderTree() {
    var html = '';
    function walk(nodes, depth) {
        nodes.forEach(function(n) {
            var cls = 'tree-item indent-' + depth + (n.id === currentDocId ? ' active' : '');
            html += '<div class="' + cls + '" onclick="openDoc(' + n.id + ')">' + htmlspecialchars(n.title) + '</div>';
            if (n.children && n.children.length) walk(n.children, depth + 1);
        });
    }
    walk(<?= json_encode($tree) ?>, 0);
    $('#docTree').html(html);
}

function htmlspecialchars(str) {
    if (typeof str !== 'string') return str;
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function openDoc(id) {
    location.href = '/public/index.php?route=kb-editor&id=' + id;
}

function addDoc() {
    var title = prompt('文档标题：');
    if (!title) return;
    $.ajax({
        url: '/public/index.php?route=kb-api&action=create_doc',
        type: 'POST',
        data: { space_id: <?= $currentSpace['id'] ?? 0 ?>, title: title },
        dataType: 'json',
        success: function(d) {
            if (d.success && d.doc_id) {
                location.href = '/public/index.php?route=kb-editor&id=' + d.doc_id;
            } else {
                alert('创建失败：' + (d.message || ''));
            }
        }
    });
}

// ========== 初始化 ==========
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
        imageUploadURL: '/public/index.php?route=kb-api&action=upload_image&doc_id=' + (currentDocId || 0),
        imageUploadField: 'image',
        flowChart: false,
        sequenceDiagram: false,
        onchange: function() { autoSave(); },
        onload: function() {
            // 激活粘贴上传插件
            this.imagePaste();
        }
    });
    lastContent = editor.codemirror.getValue();
    lastTitle = $('#docTitle').val();
    $('#docTitle').on('input', function(){ lastTitle = $(this).val(); autoSave(); });
    <?php endif; ?>
});
</script>
</body>
</html>
