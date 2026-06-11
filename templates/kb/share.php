<?php
/** @var array $doc */
/** @var string $appName */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($doc['title']) ?> - <?= htmlspecialchars($appName) ?>知识库</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/marked@12.0.0/marked.min.js"></script>
<style>
* { box-sizing:border-box; }
body { margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#fff; color:#333; }
.share-header { border-bottom:1px solid #eee; padding:12px 24px; display:flex; align-items:center; justify-content:space-between; }
.share-header .logo { font-weight:700; font-size:1rem; color:#333; text-decoration:none; }
.share-header .doc-title { font-size:0.88rem; color:#666; }
.share-wrap { display:flex; max-width:1200px; margin:0 auto; min-height:calc(100vh - 50px); }
.share-content { flex:1; padding:32px 48px; max-width:860px; }
.share-content h1 { font-size:1.8rem; font-weight:700; margin:0 0 24px; padding-bottom:10px; border-bottom:2px solid #eee; }
.share-content h2 { font-size:1.4rem; font-weight:600; margin:24px 0 12px; color:#222; }
.share-content h3 { font-size:1.15rem; font-weight:600; margin:20px 0 8px; }
.share-content h4 { font-size:1rem; font-weight:600; margin:16px 0 6px; }
.share-content p { line-height:1.8; margin-bottom:14px; }
.share-content pre { background:#f6f8fa; padding:16px; border-radius:8px; overflow-x:auto; font-size:0.85rem; }
.share-content code { font-size:0.85em; }
.share-content p code { background:rgba(102,126,234,0.08); padding:2px 6px; border-radius:4px; }
.share-content blockquote { border-left:4px solid #667eea; padding:10px 16px; margin:14px 0; background:rgba(102,126,234,0.04); border-radius:0 8px 8px 0; }
.share-content table { width:100%; border-collapse:collapse; margin:14px 0; }
.share-content th, .share-content td { border:1px solid #e5e7eb; padding:8px 12px; text-align:left; font-size:0.88rem; }
.share-content th { background:#f9fafb; font-weight:600; }
.share-content img { max-width:100%; border-radius:8px; margin:10px 0; }
.share-content hr { border:none; border-top:1px solid #e5e7eb; margin:24px 0; }
.share-content ul, .share-content ol { padding-left:24px; margin-bottom:14px; }
.share-content li { margin-bottom:6px; line-height:1.7; }
.share-toc { width:200px; min-width:200px; padding:24px 12px; border-left:1px solid #eee; position:sticky; top:0; height:100vh; overflow-y:auto; }
.share-toc .toc-label { font-size:0.72rem; color:#999; text-transform:uppercase; font-weight:600; margin-bottom:8px; }
.share-toc a { display:block; font-size:0.78rem; color:#666; text-decoration:none; padding:3px 0 3px 8px; border-left:2px solid transparent; transition:all 0.15s; line-height:1.4; }
.share-toc a:hover, .share-toc a.active { color:#667eea; border-left-color:#667eea; }
.share-toc a.toc-h3 { padding-left:20px; }
.share-toc a.toc-h4 { padding-left:32px; font-size:0.72rem; }
.share-footer { border-top:1px solid #eee; padding:16px 24px; text-align:center; font-size:0.78rem; color:#999; }
@media (max-width:768px) {
    .share-toc { display:none; }
    .share-content { padding:20px 16px; }
}
</style>
</head>
<body>

<div class="share-header">
    <span class="logo">📚 <?= htmlspecialchars($appName) ?> 知识库</span>
    <span class="doc-title"><?= htmlspecialchars($doc['space_name'] ?? '') ?></span>
</div>

<div class="share-wrap">
    <div class="share-content">
        <h1><?= htmlspecialchars($doc['title']) ?></h1>
        <div id="articleBody"></div>
    </div>
    <div class="share-toc">
        <div class="toc-label">目录</div>
        <div id="tocList"></div>
    </div>
</div>

<div class="share-footer">
    由 <?= htmlspecialchars($appName) ?> 知识库生成 · 分享于 <?= date('Y-m-d') ?>
</div>

<script>
var mdContent = <?= json_encode($doc['content'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
var html = marked.parse(mdContent);
document.getElementById('articleBody').innerHTML = html;

var headings = document.querySelectorAll('#articleBody h1, #articleBody h2, #articleBody h3, #articleBody h4');
var tocHtml = '';
headings.forEach(function(el, i) {
    var id = 'h-' + i;
    el.id = id;
    var level = parseInt(el.tagName.charAt(1));
    var cls = level >= 3 ? ' toc-h' + level : '';
    tocHtml += '<a href="#' + id + '" class="' + cls + '" data-target="' + id + '">' + el.textContent + '</a>';
});
document.getElementById('tocList').innerHTML = tocHtml || '<div style="color:#999;font-size:0.78rem">无目录</div>';

var tocLinks = document.querySelectorAll('#tocList a');
window.addEventListener('scroll', function() {
    var scrollPos = window.scrollY + 80;
    var active = null;
    headings.forEach(function(el) { if (el.offsetTop <= scrollPos) active = el.id; });
    tocLinks.forEach(function(a) { a.classList.toggle('active', a.getAttribute('data-target') === active); });
});
</script>
</body>
</html>
