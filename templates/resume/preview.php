<?php
/** @var string $template */
/** @var array $resume */
/** @var string $resumeName */
/** @var int $resumeId */
/** @var array $resumes */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">📄 简历预览</h5>
        <select id="resumeSelect" class="form-select form-select-sm" style="width:200px" onchange="switchResume()">
            <?php foreach ($resumes as $r): ?>
            <option value="<?= $r['id'] ?>" <?= $r['id'] === $resumeId ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
            <?php if (empty($resumes)): ?>
            <option value="0">暂无简历，请先创建</option>
            <?php endif; ?>
        </select>
    </div>
    <div class="d-flex gap-2">
        <select id="templateSelect" class="form-select form-select-sm" style="width:140px" onchange="changeTemplate()">
            <option value="simple" <?= $template === 'simple' ? 'selected' : '' ?>>简洁版</option>
            <option value="pro" <?= $template === 'pro' ? 'selected' : '' ?>>专业版</option>
            <option value="creative" <?= $template === 'creative' ? 'selected' : '' ?>>创意版</option>
        </select>
        <a href="/public/index.php?route=resume-builder<?= $resumeId ? '&id='.$resumeId : '' ?>" class="btn btn-sm btn-outline-primary">⚙️ 配置简历</a>
        <button class="btn btn-sm btn-success" onclick="exportPDF()">📄 生成PDF</button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <iframe id="previewFrame" src="/public/index.php?route=resume-preview&standalone=1&template=<?= htmlspecialchars($template) ?>&id=<?= $resumeId ?>" style="width:100%;height:calc(100vh - 160px);border:none"></iframe>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script>
function switchResume() {
    var id = document.getElementById('resumeSelect').value;
    if (id > 0) {
        window.location.href = '/public/index.php?route=resume-preview&id=' + id;
    }
}
function changeTemplate() {
    var tpl = document.getElementById('templateSelect').value;
    var id = document.getElementById('resumeSelect').value;
    document.getElementById('previewFrame').src = '/public/index.php?route=resume-preview&standalone=1&template=' + tpl + '&id=' + id;
}
function exportPDF() {
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:9999;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = '<div style="background:#fff;padding:30px 50px;border-radius:12px;text-align:center"><div style="font-size:36px;margin-bottom:12px">⏳</div><div style="font-size:16px;color:#333">正在生成PDF...</div><div class="progress mt-3" style="width:200px;height:8px"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div></div></div>';
    document.body.appendChild(overlay);

    var tpl = document.getElementById('templateSelect').value;
    var id = document.getElementById('resumeSelect').value;
    var url = '/public/index.php?route=resume-preview&standalone=1&template=' + tpl + '&id=' + id;
    fetch(url)
        .then(function(r){return r.text()})
        .then(function(html){
            var container = document.createElement('div');
            container.style.cssText = 'position:fixed;left:-9999px;top:0;width:794px;z-index:-1';
            container.innerHTML = html;
            document.body.appendChild(container);
            html2canvas(container, {scale:2,useCORS:true,logging:false}).then(function(canvas){
                document.body.removeChild(container);
                var pageW = 210, pageH = 297;
                var pxPerMm = canvas.width / pageW;
                var pagePxH = pageH * pxPerMm;
                var totalPages = Math.ceil(canvas.height / pagePxH);
                var pdf = new jspdf.jsPDF('p', 'mm', 'a4');
                for (var p = 0; p < totalPages; p++) {
                    if (p > 0) pdf.addPage();
                    var sy = p * pagePxH;
                    var sh = Math.min(pagePxH, canvas.height - sy);
                    var pageCanvas = document.createElement('canvas');
                    pageCanvas.width = canvas.width;
                    pageCanvas.height = sh;
                    pageCanvas.getContext('2d').drawImage(canvas, 0, sy, canvas.width, sh, 0, 0, canvas.width, sh);
                    var hMm = sh / pxPerMm;
                    pdf.addImage(pageCanvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, pageW, hMm);
                }
                var blob = pdf.output('blob');
                document.body.removeChild(overlay);
                window.open(URL.createObjectURL(blob), '_blank');
            });
        });
}
</script>
