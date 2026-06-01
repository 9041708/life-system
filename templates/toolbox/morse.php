<style>
.morse-card { border-radius: 0.75rem; max-width: 700px; }
.morse-output { font-family: monospace; font-size: 1.05rem; background: rgba(15,23,42,0.03); border-radius: 0.5rem; padding: 12px; min-height: 48px; word-break: break-all; }
body.theme-dark .morse-output { background: rgba(148,163,184,0.06); }
</style>

<h5 class="mb-3">📡 摩斯电码编码解码</h5>
<div class="alert alert-info small py-2 mb-3">拉丁字符直接编码；中文等非拉丁字符使用 Unicode 码点编码（格式 <code style="font-family:monospace;">[20320]</code>），解码时自动还原。需使用本工具或同规则解码。</div>

<div class="card glass-card morse-card">
    <div class="card-body p-3">
        <div class="mb-3">
            <label class="form-label small fw-semibold">输入内容</label>
            <textarea id="morseInput" class="form-control form-control-sm" rows="3" placeholder="输入文字或摩斯电码..."></textarea>
        </div>
        <div class="row g-2 align-items-end mb-3">
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-0">分隔符号</label>
                <input type="text" id="morseSep" class="form-control form-control-sm" value="/" style="width:60px;">
            </div>
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-0">长符号</label>
                <input type="text" id="morseLong" class="form-control form-control-sm" value="-" style="width:60px;">
            </div>
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-0">短符号</label>
                <input type="text" id="morseShort" class="form-control form-control-sm" value="." style="width:60px;">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary" onclick="morseEncode()">🔒 编码</button>
                <button class="btn btn-sm btn-outline-primary ms-1" onclick="morseDecode()">🔓 解码</button>
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyMorseResult()">📋 复制结果</button>
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="document.getElementById('morseInput').value='';document.getElementById('morseOutput').textContent='';">🗑 清空</button>
            </div>
        </div>
        <div>
            <label class="form-label small fw-semibold">结果</label>
            <div class="morse-output" id="morseOutput"></div>
        </div>
    </div>
</div>

<script>
var MORSE_MAP = {
    'A':'.-','B':'-...','C':'-.-.','D':'-..','E':'.','F':'..-.','G':'--.','H':'....',
    'I':'..','J':'.---','K':'-.-','L':'.-..','M':'--','N':'-.','O':'---','P':'.--.',
    'Q':'--.-','R':'.-.','S':'...','T':'-','U':'..-','V':'...-','W':'.--','X':'-..-',
    'Y':'-.--','Z':'--..',
    '0':'-----','1':'.----','2':'..---','3':'...--','4':'....-','5':'.....',
    '6':'-....','7':'--...','8':'---..','9':'----.',
    '.':'.-.-.-',',':'--..--','?':'..--..',"'":'.----.','!':'-.-.--','/':'-..-.',
    '(':'-.--.',')':'-.--.-','&':'.-...',':':'---...',';':'-.-.-.','=':'-...-',
    '+':'.-.-.','-':'-....-','_':'..--.-','"':'.-..-.','$':'...-..-','@':'.--.-.',
    ' ':'/'
};
var REV_MAP = {};
for (var key in MORSE_MAP) { REV_MAP[MORSE_MAP[key]] = key; }

function getLong() { return document.getElementById('morseLong').value || '-'; }
function getShort() { return document.getElementById('morseShort').value || '.'; }
function getSep() { return document.getElementById('morseSep').value || '/'; }

function decodeToken(tok) {
    if (tok[0] === '[' && tok[tok.length - 1] === ']') tok = tok.slice(1, -1);
    var cp = parseInt(tok);
    if (!isNaN(cp) && cp > 127) return String.fromCodePoint(cp);
    return tok;
}

function morseEncode() {
    var text = document.getElementById('morseInput').value.trim();
    if (!text) return;
    var long = getLong(), short = getShort(), sep = getSep();
    var parts = [];
    for (var i = 0; i < text.length; i++) {
        var ch = text[i];
        if (ch === ' ' || ch === '\n') { parts.push(sep); continue; }
        var code = MORSE_MAP[ch.toUpperCase()];
        if (code) {
            parts.push(code.replace(/-/g, long).replace(/\./g, short));
        } else {
            var cp = ch.codePointAt(0);
            if (cp > 0xFFFF) i++;
            var digits = '[' + cp + ']';
            for (var d = 0; d < digits.length; d++) {
                var dch = digits[d];
                var dcode = dch === '[' ? MORSE_MAP['('] : (dch === ']' ? MORSE_MAP[')'] : MORSE_MAP[dch]);
                if (dcode) parts.push(dcode.replace(/-/g, long).replace(/\./g, short));
            }
        }
    }
    document.getElementById('morseOutput').textContent = parts.join(' ');
}

function morseDecode() {
    var text = document.getElementById('morseInput').value.trim();
    if (!text) return;
    var long = getLong(), short = getShort(), sep = getSep();
    var norm = text.replace(new RegExp('\\' + long, 'g'), '-').replace(new RegExp('\\' + short, 'g'), '.');
    var parts = norm.split(/\s+/);
    var result = '';
    var buf = ''; // 正在收集 Unicode 码点
    var inCP = false;

    for (var i = 0; i < parts.length; i++) {
        if (parts[i] === sep || parts[i] === '/') {
            if (inCP) { result += decodeToken(buf); buf = ''; inCP = false; }
            result += ' ';
            continue;
        }
        var ch = REV_MAP[parts[i]];
        if (ch === undefined) { result += '?'; continue; }
        if (ch === '(') { inCP = true; continue; }
        if (inCP) {
            if (ch === ')') {
                result += decodeToken(buf);
                buf = ''; inCP = false;
            } else {
                buf += ch;
            }
            continue;
        }
        result += ch;
    }
    if (inCP && buf) result += decodeToken(buf);
    document.getElementById('morseOutput').textContent = result;
}

function copyMorseResult() {
    var text = document.getElementById('morseOutput').textContent;
    if (!text) return;
    try { navigator.clipboard.writeText(text); } catch(e) {
        var ta = document.createElement('textarea'); ta.value = text;
        ta.style.position = 'fixed'; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
    }
    alert('已复制到剪贴板');
}
</script>
