<style>
.cny-card { border-radius: 0.75rem; }
.cny-result { font-size: 1.1rem; font-weight: 600; color: #d97706; word-break: break-all; }
body.theme-dark .cny-result { color: #fbbf24; }
.cny-table th, .cny-table td { font-size: 0.82rem; padding: 4px 8px; text-align: center; }
</style>

<h5 class="mb-3">💴 人民币大写转换器</h5>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card glass-card cny-card">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-auto">
                        <label class="form-label small fw-semibold">输入方式</label>
                        <select id="cnyMode" class="form-select form-select-sm" onchange="toggleMode()">
                            <option value="single">单个金额</option>
                            <option value="multi">多个金额（每行一个）</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-semibold">单位</label>
                        <select id="cnyUnit" class="form-select form-select-sm">
                            <option value="元">元</option>
                            <option value="圆">圆</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary" onclick="convert()">🔄 转换</button>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="clearAll()">清空</button>
                    </div>
                </div>
                <div id="cnySingleWrap">
                    <label class="form-label small fw-semibold">输入金额</label>
                    <input type="text" id="cnyInputSingle" class="form-control form-control-sm" placeholder="如：1234.56" onkeydown="if(event.key==='Enter')convert()">
                </div>
                <div id="cnyMultiWrap" class="d-none">
                    <label class="form-label small fw-semibold">输入金额（每行一个）</label>
                    <textarea id="cnyInputMulti" class="form-control form-control-sm" rows="5" placeholder="1234.56&#10;1000.00&#10;0.88"></textarea>
                </div>
                <div class="mt-3" id="cnyOutput"></div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card glass-card cny-card">
            <div class="card-body p-3">
                <h6 class="mb-2">📖 数字大写对照表</h6>
                <table class="table table-sm table-bordered cny-table mb-0">
                    <thead class="table-light"><tr><th>数字</th><th>大写</th></tr></thead>
                    <tbody>
                        <tr><td>0</td><td>零</td></tr>
                        <tr><td>1</td><td>壹</td></tr>
                        <tr><td>2</td><td>贰</td></tr>
                        <tr><td>3</td><td>叁</td></tr>
                        <tr><td>4</td><td>肆</td></tr>
                        <tr><td>5</td><td>伍</td></tr>
                        <tr><td>6</td><td>陆</td></tr>
                        <tr><td>7</td><td>柒</td></tr>
                        <tr><td>8</td><td>捌</td></tr>
                        <tr><td>9</td><td>玖</td></tr>
                        <tr><td>10</td><td>拾</td></tr>
                        <tr><td>100</td><td>佰</td></tr>
                        <tr><td>1000</td><td>仟</td></tr>
                        <tr><td>万</td><td>万</td></tr>
                        <tr><td>亿</td><td>亿</td></tr>
                        <tr><td>角</td><td>角</td></tr>
                        <tr><td>分</td><td>分</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var CN_DIGITS = ['零','壹','贰','叁','肆','伍','陆','柒','捌','玖'];
var CN_RADICES = ['','拾','佰','仟'];
var CN_SUP_RADICES = ['','万','亿','万亿'];
var CN_DEC = ['角','分'];

function toggleMode() {
    var mode = document.getElementById('cnyMode').value;
    document.getElementById('cnySingleWrap').classList.toggle('d-none', mode !== 'single');
    document.getElementById('cnyMultiWrap').classList.toggle('d-none', mode !== 'multi');
    document.getElementById('cnyOutput').innerHTML = '';
}

function clearAll() {
    document.getElementById('cnyInputSingle').value = '';
    document.getElementById('cnyInputMulti').value = '';
    document.getElementById('cnyOutput').innerHTML = '';
}

function toCny(n) {
    var unit = document.getElementById('cnyUnit').value;
    var negative = n < 0;
    n = Math.abs(n);
    var intPart = Math.floor(n);
    var decPart = Math.round((n - intPart) * 100);
    var jiao = Math.floor(decPart / 10);
    var fen = decPart % 10;

    var intStr = '';
    if (intPart === 0) intStr = '零' + unit;
    else {
        var segments = [];
        var unitIndex = 0;
        while (intPart > 0) {
            var seg = intPart % 10000;
            intPart = Math.floor(intPart / 10000);
            var s = '';
            if (seg > 0) {
                var segStr = seg.toString();
                var len = segStr.length;
                var hasZero = false;
                for (var i = 0; i < len; i++) {
                    var d = parseInt(segStr[i]);
                    var pos = len - i - 1;
                    if (d === 0) {
                        hasZero = true;
                    } else {
                        if (hasZero) { s += '零'; hasZero = false; }
                        s += CN_DIGITS[d] + CN_RADICES[pos];
                    }
                }
                s += CN_SUP_RADICES[unitIndex];
            }
            segments.unshift(s);
            unitIndex++;
        }
        intStr = segments.join('').replace(/零+$/, '') + unit;
    }
    intStr = intStr.replace(/零{2,}/g, '零');

    var decStr = '';
    if (jiao === 0 && fen === 0) decStr = '整';
    else {
        if (jiao > 0) decStr += CN_DIGITS[jiao] + '角';
        if (fen > 0) decStr += CN_DIGITS[fen] + '分';
    }

    var result = intStr + decStr;
    if (negative) result = '负' + result;
    return result;
}

function convert() {
    var mode = document.getElementById('cnyMode').value;
    var out = document.getElementById('cnyOutput');
    var lines = [];
    if (mode === 'single') {
        var v = document.getElementById('cnyInputSingle').value.trim();
        if (v) lines = [v];
    } else {
        lines = document.getElementById('cnyInputMulti').value.split(/[\n\r]+/).filter(function(l){return l.trim() !== '';});
    }
    if (lines.length === 0) { out.innerHTML = '<div class="text-muted small">请输入金额</div>'; return; }
    var html = '';
    lines.forEach(function(line){
        var num = parseFloat(line.replace(/,/g, ''));
        if (isNaN(num)) { html += '<div class="text-danger small">' + line + ' — 无效数字</div>'; }
        else html += '<div class="cny-result">' + toCny(num) + '</div>';
    });
    out.innerHTML = html;
}
</script>
