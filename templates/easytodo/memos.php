<?php
/** @var array $memos */
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/css/editormd.min.css">
<link rel="stylesheet" href="https://cdn.bootcss.com/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@1.11.3/dist/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/lib/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/google-code-prettify@1.0.5/src/prettify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.3.0/raphael.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery.flowchart@1.1.0/jquery.flowchart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/editormd.min.js"></script>

<style>
#sidebarToggle { cursor: pointer; user-select: none; }
#sidebarToggle:hover { color: #0d6efd; }

#memoListPanel {
    height: calc(100vh - 180px);
    overflow-y: auto;
    padding-right: 4px;
}

.memo-list-item {
    cursor: pointer;
    border-radius: 6px;
    margin-bottom: 4px;
    transition: background 0.15s;
    padding: 8px 12px;
}
.memo-list-item:hover { background: #f0f0f0; }
.memo-list-item.active { background: #e7f5ff; border-left: 3px solid #0d6efd; }
.memo-list-item .memo-title {
    font-size: 13px;
    font-weight: 600;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.memo-list-item .memo-preview {
    font-size: 12px;
    color: #666;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    max-height: 18px;
}
.memo-list-item .memo-date { font-size: 11px; color: #aaa; margin-top: 2px }

/* editormd 工具栏美化 */
.editormd-toolbar { border-radius: 6px 6px 0 0 !important; }
.editormd-toolbar ul { flex-wrap: wrap; }

/* 导出分享面板 */
#exportPanel { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
#exportPanel.active { display: flex; }
#exportPanel > div { background: white; border-radius: 10px; padding: 28px 32px; max-width: 400px; width: 90%; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
.export-item { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.15s; border-radius: 6px; padding: 12px 10px; }
.export-item:hover { background: #f5f5f5; }
.export-item i { font-size: 22px; width: 28px; text-align: center; }
.export-item .export-label { font-size: 14px; font-weight: 600; }
.export-item .export-desc { font-size: 12px; color: #888; }
#shareUrlInput { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; margin-top: 8px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">📝 备忘录</h5>
    <button class="btn btn-primary btn-sm" id="btnNew">+ 新建</button>
</div>

<div style="display:flex;height:calc(100vh - 180px)">
    <!-- 左侧列表 -->
    <div id="sidebarPanel" style="width:220px;flex-shrink:0;padding-right:12px">
        <div class="d-flex align-items-center mb-2" style="font-size:12px;color:#888;cursor:pointer" onclick="toggleSidebar()">
            <span id="sidebarToggle">◀ 收起</span>
        </div>
        <div id="memoListPanel">
            <?php if (empty($memos)): ?>
            <div class="text-center text-muted py-5" style="font-size:13px">暂无备忘录<br><small>点击右上角新建</small></div>
            <?php else: ?>
            <?php foreach ($memos as $m):
                $plain = trim(strip_tags($m['content']));
                $title = trim(substr($plain, 0, 28)) ?: '无标题';
                $preview = trim(substr($plain, 0, 50));
                if (strlen($plain) > 50) $preview .= '...';
                $isActive = !empty($_GET['id']) && $_GET['id'] == $m['id'];
            ?>
            <div class="memo-list-item<?= $isActive ? ' active' : '' ?>" data-id="<?= (int)$m['id'] ?>">
                <div class="memo-title"><?= htmlspecialchars($title) ?></div>
                <div class="memo-preview"><?= htmlspecialchars($preview) ?></div>
                <div class="memo-date"><?= htmlspecialchars(substr($m['updated_at'], 0, 10)) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 展开箭头（侧边栏收起时显示） -->
    <div id="expandBtn" style="display:none;width:28px;flex-shrink:0;padding-top:20px">
        <div style="writing-mode:vertical-rl;font-size:12px;color:#aaa;cursor:pointer;letter-spacing:2px" onclick="toggleSidebar()">
            ▶ 备忘录
        </div>
    </div>

    <!-- 右侧编辑器 -->
    <div style="flex:1;min-width:0">
        <div id="editorEmpty" class="text-center text-muted" style="height:100%;display:flex;align-items:center;justify-content:center;border:2px dashed #ddd;border-radius:8px">
            <div>
                <div style="font-size:48px;margin-bottom:12px">📝</div>
                <div>选择或新建备忘录开始编辑</div>
                <div class="small text-muted mt-1">支持 Markdown 语法，实时预览</div>
            </div>
        </div>
        <div id="editorArea" style="display:none;height:100%">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-secondary" id="editorStatus">未保存</span>
                <div class="d-flex gap-1 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary" onclick="openMarkdown()" title="在新标签页查看 Markdown 源码"><i class="fa fa-external-link"></i> 打开MD</button>
                    <button class="btn btn-sm btn-outline-success" onclick="copyMarkdown()" title="复制 Markdown 源码到剪贴板"><i class="fa fa-copy"></i> 复制MD</button>
                    <button class="btn btn-sm btn-outline-warning" onclick="copyHTML()" title="复制 HTML 预览内容到剪贴板"><i class="fa fa-code"></i> 复制HTML</button>
                    <button class="btn btn-sm btn-outline-info" onclick="exportMarkdown()" title="下载 .md 文件"><i class="fa fa-download"></i> 导出MD</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="exportHTML()" title="下载 .html 文件"><i class="fa fa-file-text-o"></i> 导出HTML</button>
                    <button class="btn btn-sm btn-outline-danger" id="btnDelete">删除</button>
                    <button class="btn btn-sm btn-primary" id="btnSave">💾 保存</button>
                </div>
            </div>
            <div id="editormd" style="height:calc(100vh - 240px);border:1px solid #ddd;border-radius:6px">
                <textarea id="memoContent" style="display:none"></textarea>
            </div>
        </div>
    </div>
</div>

<!-- 导出/分享弹窗 -->
<div id="exportPanel">
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 fw-bold">导出 / 分享</h6>
            <button onclick="closeExportPanel()" style="background:none;border:none;font-size:20px;cursor:pointer">×</button>
        </div>

        <div class="export-item" onclick="openMarkdown()">
            <i class="fa fa-arrow-up-right-from-square text-primary"></i>
            <div>
                <div class="export-label">打开 MD</div>
                <div class="export-desc">在新标签页查看 Markdown 源码</div>
            </div>
        </div>

        <div class="export-item" onclick="copyMarkdown()">
            <i class="fa fa-copy text-info"></i>
            <div>
                <div class="export-label">复制 MD</div>
                <div class="export-desc">复制 Markdown 源码到剪贴板</div>
            </div>
        </div>

        <div class="export-item" onclick="copyHTML()">
            <i class="fa fa-code text-warning"></i>
            <div>
                <div class="export-label">复制 HTML</div>
                <div class="export-desc">复制渲染后的 HTML 到剪贴板</div>
            </div>
        </div>

        <div class="export-item" onclick="exportMarkdown()">
            <i class="fa fa-file-lines text-success"></i>
            <div>
                <div class="export-label">导出 MD</div>
                <div class="export-desc">下载为 .md 文件</div>
            </div>
        </div>

        <div class="export-item" onclick="exportHTML()">
            <i class="fa fa-file-code text-danger"></i>
            <div>
                <div class="export-label">导出 HTML</div>
                <div class="export-desc">下载为完整 HTML 文件</div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var allMemos = <?= json_encode(array_column($memos, 'content', 'id')) ?>;
    var currentMemoId = null;
    var currentTitle = '';
    var editor = null;
    var saveTimer = null;
    var collapsed = false;

    window.toggleSidebar = function() {
        collapsed = !collapsed;
        $('#sidebarPanel').toggle(!collapsed);
        $('#expandBtn').toggle(collapsed);
        if (!collapsed && editor) setTimeout(function() { editor.resize(); }, 10);
    };

    function initEditor(content) {
        $('#editorEmpty').hide();
        $('#editorArea').show();

        if (editor) {
            try { editor.clear(); } catch(e) {}
            $('#editormd').empty().html('<textarea id="memoContent" style="display:none"></textarea>');
            editor = null;
        }
        $('#memoContent').val(content || '');

        editor = editormd("editormd", {
            width: "100%",
            height: "calc(100vh - 240px)",
            path: "https://cdn.jsdelivr.net/npm/editor.md@1.5.0/lib/",
            markdown: content || '',
            theme: "default",
            previewTheme: "default",
            editorTheme: "default",
            codeFold: true,
            syncScrolling: true,
            saveHTMLToTextarea: true,
            searchReplace: true,
            watch: true,
            toolbar: true,
            autoLoadModules: true,
            placeholder: '开始写作...（支持 Markdown）',
            flowChart: false,
            sequenceDiagram: false,
            onchange: function() {
                $('#editorStatus').text('● 未保存').removeClass('bg-success').addClass('bg-warning text-dark');
                clearTimeout(saveTimer);
                saveTimer = setTimeout(autoSave, 2000);
            }
        });
    }

    function autoSave() {
        if (!currentMemoId || !editor) return;
        saveMemo(currentMemoId, editor.getMarkdown(), true);
    }

    function saveMemo(id, content, isAuto) {
        $.post('/public/index.php?route=easytodo-api-memos', {
            action: 'update', id: id, content: content
        }, function(d) {
            if (d.ok) {
                $('#editorStatus').text('✓ 已保存').removeClass('bg-warning text-dark').addClass('bg-success');
                allMemos[id] = content;
                updateListItem(id, content);
            }
        }, 'json');
    }

    function updateListItem(id, content) {
        var plain = content.replace(/[#*`>\-\[\]]/g, '').trim();
        var title = plain.substr(0, 28) || '无标题';
        var preview = plain.substr(0, 50);
        if (plain.length > 50) preview += '...';
        var el = $('.memo-list-item[data-id="' + id + '"]');
        el.find('.memo-title').text(title);
        el.find('.memo-preview').text(preview);
        if (id == currentMemoId) currentTitle = title;
    }

    function selectMemo(id, el) {
        currentMemoId = id;
        $('.memo-list-item').removeClass('active');
        $(el).addClass('active');
        if (editor) {
            editor.setMarkdown(allMemos[id] || '');
        } else {
            initEditor(allMemos[id] || '');
        }
        var plain = (allMemos[id] || '').replace(/[#*`>\-\[\]]/g, '').trim();
        currentTitle = plain.substr(0, 28) || '无标题';
    }

    function showExportPanel() {
        if (!currentMemoId) { alert('请先选择一个备忘录'); return; }
        $('#shareUrlBox').hide();
        $('#exportPanel').addClass('active');
    }

    window.closeExportPanel = function() {
        $('#exportPanel').removeClass('active');
    };

    window.exportHTML = function() {
        if (!editor) return;
        var md = editor.getMarkdown();
        var html = editor.getHTML();
        var title = currentTitle || '备忘录';
        var page = `<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>${title}</title>
<link rel="stylesheet" href="https://9041708.cn:555/assets/css/editormd.min.css">
<style>
body { margin: 0; padding: 40px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fafafa; }
.editor-md-preview { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
pre { background: #f6f8fa; border-radius: 6px; padding: 16px; overflow-x: auto; }
blockquote { border-left: 3px solid #dfe2e5; padding-left: 16px; color: #6a737d; margin: 0; }
table { border-collapse: collapse; width: 100%; }
table th, table td { border: 1px solid #dfe2e5; padding: 8px 12px; }
table th { background: #f6f8fa; }
h1,h2,h3,h4,h5,h6 { border-bottom: 1px solid #eaecef; padding-bottom: 8px; }
</style>
</head>
<body>
<div class="editor-md-preview">
${html}
</div>
</body>
</html>`;
        var blob = new Blob([page], {type: 'text/html'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = (title + '.html'); a.click();
        URL.revokeObjectURL(url);
        closeExportPanel();
    };

    window.openMarkdown = function() {
        // 打开本地MD文件
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = '.md,.markdown,text/markdown';
        input.onchange = function(e) {
            var file = e.target.files[0];
            if (!file) return;
            
            var reader = new FileReader();
            reader.onload = function(event) {
                var content = event.target.result;
                // 加载内容到编辑器
                if (editor) {
                    editor.setMarkdown(content);
                } else {
                    initEditor(content);
                }
                // 更新标题（用文件名）
                currentTitle = file.name.replace(/\.md$/i, '');
                $('#editorStatus').text('● 已加载本地文件').removeClass('bg-success').addClass('bg-warning text-dark');
            };
            reader.readAsText(file, 'UTF-8');
        };
        input.click();
    };

    window.copyMarkdown = function() {
        if (!editor) return;
        var md = editor.getMarkdown();
        try { navigator.clipboard.writeText(md); } catch(e) { prompt('复制MD', md); }
        closeExportPanel();
    };

    window.copyHTML = function() {
        if (!editor) return;
        var html = editor.getHTML();
        // 复制带格式的HTML到剪贴板
        var blob = new Blob([html], {type: 'text/html'});
        var textBlob = new Blob([html], {type: 'text/plain'});
        
        if (navigator.clipboard && navigator.clipboard.write) {
            navigator.clipboard.write([
                new ClipboardItem({
                    'text/html': blob,
                    'text/plain': textBlob
                })
            ]).then(function() {
                $('#editorStatus').text('✓ HTML已复制').removeClass('bg-warning text-dark').addClass('bg-success');
                setTimeout(function() {
                    $('#editorStatus').text('✓ 已保存').removeClass('bg-success').addClass('bg-success');
                }, 2000);
            }).catch(function() {
                fallbackCopyHTML(html);
            });
        } else {
            fallbackCopyHTML(html);
        }
    };
    
    function fallbackCopyHTML(html) {
        var tempDiv = document.createElement('div');
        tempDiv.style.position = 'fixed';
        tempDiv.style.left = '-9999px';
        tempDiv.innerHTML = html;
        document.body.appendChild(tempDiv);
        
        var range = document.createRange();
        range.selectNodeContents(tempDiv);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        
        try {
            document.execCommand('copy');
            $('#editorStatus').text('✓ HTML已复制').removeClass('bg-warning text-dark').addClass('bg-success');
        } catch(e) {
            prompt('复制HTML', html);
        }
        
        selection.removeAllRanges();
        document.body.removeChild(tempDiv);
    }

    window.exportMarkdown = function() {
        if (!editor) return;
        var md = editor.getMarkdown();
        var blob = new Blob([md], {type: 'text/markdown'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = (currentTitle || '备忘录') + '.md'; a.click();
        URL.revokeObjectURL(url);
        closeExportPanel();
    };

    window.exportHTML = function() {
        if (!editor) return;
        var md = editor.getMarkdown();
        var html = editor.getHTML();
        var title = currentTitle || '备忘录';
        var page = `<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>${title}</title>
<link rel="stylesheet" href="https://9041708.cn:555/assets/css/editormd.min.css">
<style>
body { margin: 0; padding: 40px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fafafa; }
.editor-md-preview { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
pre { background: #f6f8fa; border-radius: 6px; padding: 16px; overflow-x: auto; }
blockquote { border-left: 3px solid #dfe2e5; padding-left: 16px; color: #6a737d; margin: 0; }
table { border-collapse: collapse; width: 100%; }
table th, table td { border: 1px solid #dfe2e5; padding: 8px 12px; }
table th { background: #f6f8fa; }
h1,h2,h3,h4,h5,h6 { border-bottom: 1px solid #eaecef; padding-bottom: 8px; }
</style>
</head>
<body>
<div class="editor-md-preview">
${html}
</div>
</body>
</html>`;
        var blob = new Blob([page], {type: 'text/html'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = (title + '.html'); a.click();
        URL.revokeObjectURL(url);
        closeExportPanel();
    };

    window.exportPDF = function() {
        if (!editor) return;
        var printWindow = window.open('', '_blank');
        var html = editor.getHTML();
        printWindow.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>${currentTitle || '备忘录'}</title><link rel="stylesheet" href="https://9041708.cn:555/assets/css/editormd.min.css"><style>body{margin:30px;font-family:-apple-system,sans-serif}.CodeMirror{height:auto!important}pre{background:#f6f8fa;padding:16px;border-radius:6px}.editor-preview{width:800px;margin:0 auto}</style></head><body><div class="editor-preview">${html}</div></body></html>`);
        printWindow.document.close();
        setTimeout(function() { printWindow.print(); }, 500);
        closeExportPanel();
    };

    window.copyShareLink = function() {
        var link = window.location.origin + '/public/index.php?route=easytodo-memos&id=' + currentMemoId;
        $('#shareUrlInput').val(link).show().select();
        try { navigator.clipboard.writeText(link); } catch(e) {}
        closeExportPanel();
    };

    $('#btnNew').click(function() {
        $.post('/public/index.php?route=easytodo-api-memos', {
            action: 'create',
            content: '# 新建备忘录\n\n从这里开始写作...\n'
        }, function(d) {
            if (d.ok) location.href = '/public/index.php?route=easytodo-memos&id=' + d.id;
        }, 'json');
    });

    $('#btnSave').click(function() {
        if (!currentMemoId || !editor) return;
        saveMemo(currentMemoId, editor.getMarkdown(), false);
    });

    $('#btnDelete').click(function() {
        if (!currentMemoId) return;
        if (!confirm('删除此备忘录？')) return;
        $.post('/public/index.php?route=easytodo-api-memos', {
            action: 'delete', id: currentMemoId
        }, function(d) { if (d.ok) location.reload(); }, 'json');
    });

    $('.memo-list-item').click(function() {
        selectMemo($(this).data('id'), this);
    });

    <?php if (!empty($_GET['id'])): ?>
    window.addEventListener('load', function() {
        currentMemoId = <?= (int)$_GET['id'] ?>;
        initEditor(allMemos[currentMemoId] || '');
        $('.memo-list-item[data-id="' + currentMemoId + '"]').addClass('active');
        var plain = ((allMemos[currentMemoId] || '')).replace(/[#*`>\-\[\]]/g, '').trim();
        currentTitle = plain.substr(0, 28) || '无标题';
    });
    <?php endif; ?>
})();
</script>