<?php
/** @var array $book */
/** @var array|null $progress */
$isPdf = ($book['file_type'] ?? 'pdf') === 'pdf';
$startPage = (int)($progress['page_num'] ?? 1);
$startOffset = (int)($progress['scroll_offset'] ?? 0);
$fileUrl = '/public/index.php?route=books-serve&id=' . (int)$book['id'];
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <a href="/public/index.php?route=books" class="btn btn-sm btn-outline-secondary">← 返回书架</a>
        <span class="ms-2 fw-bold"><?= htmlspecialchars($book['title']) ?></span>
        <?php if (!empty($book['author'])): ?><span class="text-muted small ms-1">— <?= htmlspecialchars($book['author']) ?></span><?php endif; ?>
    </div>
    <div id="navBar" class="d-flex align-items-center gap-1">
        <button class="btn btn-sm btn-outline-primary" onclick="prevPage()">◀ 上一页</button>
        <span class="small mx-1"><input type="number" id="pageInput" style="width:50px" class="form-control form-control-sm d-inline" value="<?= $startPage ?>"> / <span id="totalPages">-</span></span>
        <button class="btn btn-sm btn-outline-primary" onclick="nextPage()">下一页 ▶</button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0" style="background:#525659">
        <?php if ($isPdf): ?>
        <div id="pdfContainer" style="text-align:center;min-height:calc(100vh - 140px);overflow-y:auto;padding:10px">
            <canvas id="pdfCanvas" style="max-width:100%;margin:0 auto;box-shadow:0 4px 20px rgba(0,0,0,0.3)"></canvas>
        </div>
        <?php else: ?>
        <div id="txtContainer" style="min-height:calc(100vh - 140px);overflow-y:auto;background:#fff;padding:20px 30px;font-size:15px;line-height:1.8;white-space:pre-wrap;word-break:break-word;font-family:'PingFang SC','Microsoft YaHei',monospace"></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isPdf): ?>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
var pdfDoc = null, currentPage = <?= $startPage ?>, totalPages = 0;
var canvas = document.getElementById('pdfCanvas');
var ctx = canvas.getContext('2d');

function loadPdf() {
    pdfjsLib.getDocument('<?= $fileUrl ?>').promise.then(function(pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        document.getElementById('totalPages').textContent = totalPages;
        document.getElementById('pageInput').max = totalPages;
        currentPage = Math.min(currentPage, totalPages);
        renderPage(currentPage);
    });
}

function renderPage(num) {
    pdfDoc.getPage(num).then(function(page) {
        var viewport = page.getViewport({scale: 1.5});
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        page.render({canvasContext: ctx, viewport: viewport});
        currentPage = num;
        document.getElementById('pageInput').value = num;
        saveProgress();
    });
}

function prevPage() { if (currentPage > 1) renderPage(currentPage - 1); }
function nextPage() { if (currentPage < totalPages) renderPage(currentPage + 1); }

document.getElementById('pageInput').onchange = function() {
    var p = parseInt(this.value);
    if (p >= 1 && p <= totalPages) renderPage(p);
};

function saveProgress() {
    var fd = new FormData();
    fd.append('action', 'progress');
    fd.append('book_id', <?= (int)$book['id'] ?>);
    fd.append('page_num', currentPage);
    fd.append('scroll_offset', 0);
    fetch('/public/index.php?route=books-api', {method:'POST', body: fd});
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') prevPage();
    if (e.key === 'ArrowRight') nextPage();
});

loadPdf();
</script>
<?php else: ?>
<script>
var scrollOffset = <?= $startOffset ?>;
var txtContainer = document.getElementById('txtContainer');
var saveTimer;

fetch('<?= $fileUrl ?>')
    .then(function(r){return r.text()})
    .then(function(text){
        txtContainer.textContent = text;
        if (scrollOffset > 0) txtContainer.scrollTop = scrollOffset;
    });

txtContainer.addEventListener('scroll', function() {
    clearTimeout(saveTimer);
    scrollOffset = txtContainer.scrollTop;
    saveTimer = setTimeout(function() {
        var fd = new FormData();
        fd.append('action', 'progress');
        fd.append('book_id', <?= (int)$book['id'] ?>);
        fd.append('page_num', 0);
        fd.append('scroll_offset', scrollOffset);
        fetch('/public/index.php?route=books-api', {method:'POST', body: fd});
    }, 1000);
});
</script>
<?php endif; ?>
