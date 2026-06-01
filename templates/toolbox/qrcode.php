<style>
.qr-card { border-radius: 0.75rem; max-width: 700px; }
</style>

<h5 class="mb-3">📱 二维码生成器</h5>

<div class="card glass-card qr-card">
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">内容</label>
                <textarea id="qrText" class="form-control form-control-sm" rows="4" placeholder="输入网址或文本..."></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">尺寸 (px)</label>
                <input type="number" id="qrSize" class="form-control form-control-sm" value="256" min="64" max="1024" step="16">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">容错率</label>
                <select id="qrLevel" class="form-select form-select-sm">
                    <option value="L">7%</option>
                    <option value="M" selected>15%</option>
                    <option value="Q">25%</option>
                    <option value="H">30%</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">密度</label>
                <select id="qrDensity" class="form-select form-select-sm">
                    <?php for ($i=1;$i<=10;$i++): ?><option value="<?=$i?>" <?=$i==4?'selected':''?>><?=$i?></option><?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">外边距</label>
                <select id="qrMargin" class="form-select form-select-sm">
                    <option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4" selected>4</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">圆角</label>
                <select id="qrRadius" class="form-select form-select-sm">
                    <option value="0">0</option><option value="10" selected>10</option><option value="20">20</option><option value="30">30</option><option value="40">40</option><option value="50">50</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">前景色</label>
                <input type="color" id="qrFg" class="form-control form-control-sm" value="#000000" style="height:34px;">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">背景色</label>
                <input type="color" id="qrBg" class="form-control form-control-sm" value="#ffffff" style="height:34px;">
            </div>
            <div class="col-12">
                <button class="btn btn-sm btn-primary" onclick="generateQR()">生成二维码</button>
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="downloadQR()">下载图片</button>
            </div>
            <div class="col-12 text-center" id="qrOutput"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function generateQR() {
    var text = document.getElementById('qrText').value.trim();
    if (!text) { alert('请输入内容'); return; }
    var size = parseInt(document.getElementById('qrSize').value) || 256;
    var level = document.getElementById('qrLevel').value;
    var density = parseInt(document.getElementById('qrDensity').value);
    var margin = parseInt(document.getElementById('qrMargin').value);
    var radius = parseInt(document.getElementById('qrRadius').value);
    var fg = document.getElementById('qrFg').value;
    var bg = document.getElementById('qrBg').value;

    var cellSize = Math.floor(size / (21 + density * 4 + margin * 2));

    var out = document.getElementById('qrOutput');
    out.innerHTML = '<div id="qrCanvas" style="display:inline-block;border-radius:' + radius + 'px;overflow:hidden;background:' + bg + ';"></div>';

    var qr = new QRCode(document.getElementById('qrCanvas'), {
        text: text,
        width: size,
        height: size,
        colorDark: fg,
        colorLight: bg,
        correctLevel: QRCode.CorrectLevel[level]
    });
}

function downloadQR() {
    var canvas = document.querySelector('#qrCanvas canvas');
    if (!canvas) { alert('请先生成二维码'); return; }
    var a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = 'qrcode.png';
    a.click();
}
</script>
