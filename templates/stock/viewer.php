<?php
/** @var array $watchlist */
/** @var array $quoteMap */
?>
<script src="https://cdn.jsdelivr.net/npm/lightweight-charts@4.1.0/dist/lightweight-charts.standalone.production.js"></script>
<style>
.stk-search-wrap { position:relative; }
.stk-search-results { position:absolute; top:100%; left:0; right:0; z-index:100; background:#fff; border:1px solid rgba(0,0,0,0.1); border-radius:0 0 8px 8px; box-shadow:0 4px 16px rgba(0,0,0,0.1); max-height:300px; overflow-y:auto; display:none; }
body.theme-dark .stk-search-results { background:#1e293b; border-color:rgba(148,163,184,0.2); }
.stk-search-item { padding:8px 12px; cursor:pointer; font-size:0.85rem; border-bottom:1px solid rgba(0,0,0,0.04); display:flex; justify-content:space-between; }
.stk-search-item:hover { background:rgba(102,126,234,0.08); }
.stk-up { color:#ef4444; } .stk-down { color:#22c55e; } .stk-flat { color:#999; }
/* 行情区 */
.stk-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px; }
.stk-price-big { font-size:2.6rem; font-weight:800; line-height:1; letter-spacing:-1px; }
.stk-change-row { font-size:1rem; font-weight:600; margin-top:4px; }
.stk-chart-wrap { height:440px; border:1px solid rgba(0,0,0,0.06); border-radius:10px; overflow:hidden; background:#fff; margin-bottom:16px; }
body.theme-dark .stk-chart-wrap { background:#0f172a; border-color:rgba(148,163,184,0.12); }
.stk-tabs { display:flex; gap:2px; margin-bottom:8px; }
.stk-tab { padding:4px 14px; border-radius:6px; font-size:0.8rem; cursor:pointer; border:1px solid rgba(0,0,0,0.08); background:transparent; transition:all 0.15s; }
.stk-tab.active { background:#667eea; color:#fff; border-color:#667eea; }
.stk-tab:hover:not(.active) { background:rgba(102,126,234,0.08); }
.stk-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:6px; margin-bottom:16px; }
.stk-cell { padding:10px 12px; border:1px solid rgba(0,0,0,0.05); border-radius:8px; background:rgba(0,0,0,0.01); }
body.theme-dark .stk-cell { background:rgba(30,41,59,0.3); border-color:rgba(148,163,184,0.1); }
.stk-cell .lbl { font-size:0.7rem; color:#999; margin-bottom:2px; }
.stk-cell .val { font-size:0.92rem; font-weight:600; }
.stk-quote-card { border:1px solid rgba(0,0,0,0.06); border-radius:10px; padding:12px; background:rgba(255,255,255,0.65); backdrop-filter:blur(10px); cursor:pointer; transition:all 0.2s; }
body.theme-dark .stk-quote-card { background:rgba(30,41,59,0.5); border-color:rgba(148,163,184,0.12); }
.stk-quote-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.08); }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">📈 股票行情</h5>
    <a href="?route=stock-simulator" class="btn btn-sm btn-outline-primary">模拟交易 →</a>
</div>

<div class="stk-search-wrap mb-3">
    <div class="input-group">
        <input type="text" id="stkSearch" class="form-control" placeholder="搜索股票名称或代码..." autocomplete="off">
        <button class="btn btn-outline-secondary" onclick="doSearch()">搜索</button>
    </div>
    <div class="stk-search-results" id="stkSearchResults"></div>
</div>

<div id="stkDetail" style="display:none">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <!-- 头部 -->
            <div class="stk-header">
                <div>
                    <div class="fw-bold fs-4" id="dtlName">-</div>
                    <div class="text-muted" style="font-size:0.82rem" id="dtlSymbol">-</div>
                </div>
                <div class="text-end">
                    <div class="stk-price-big stk-flat" id="dtlPrice">-</div>
                    <div class="stk-change-row stk-flat" id="dtlChange">-</div>
                </div>
            </div>

            <!-- K线 -->
            <div class="stk-tabs">
                <?php foreach (['5'=>'分时','15'=>'15分','30'=>'30分','60'=>'60分','101'=>'日K','102'=>'周K','103'=>'月K'] as $k=>$v): ?>
                <button class="stk-tab <?= $k==='101'?'active':'' ?>" onclick="loadKline('<?= $k ?>',this)"><?= $v ?></button>
                <?php endforeach; ?>
                <button class="btn btn-sm btn-outline-primary ms-auto" id="btnWatch" onclick="toggleWatch()">⭐ 加自选</button>
            </div>
            <div class="stk-chart-wrap" id="stkChart"></div>

            <!-- 基础行情 -->
            <div class="mb-2 small fw-semibold">📊 实时行情</div>
            <div class="stk-grid">
                <div class="stk-cell"><div class="lbl">今开</div><div class="value" id="dtlOpen">-</div></div>
                <div class="stk-cell"><div class="lbl">昨收</div><div class="value" id="dtlPrev">-</div></div>
                <div class="stk-cell"><div class="lbl">最高</div><div class="value" id="dtlHigh">-</div></div>
                <div class="stk-cell"><div class="lbl">最低</div><div class="value" id="dtlLow">-</div></div>
                <div class="stk-cell"><div class="lbl">成交量</div><div class="value" id="dtlVol">-</div></div>
                <div class="stk-cell"><div class="lbl">成交额</div><div class="value" id="dtlAmt">-</div></div>
                <div class="stk-cell"><div class="lbl">涨跌幅</div><div class="value" id="dtlPct">-</div></div>
                <div class="stk-cell"><div class="lbl">振幅</div><div class="value" id="dtlAmpl">-</div></div>
            </div>

            <!-- 估值信息 -->
            <div class="mb-2 small fw-semibold">💰 估值分析</div>
            <div class="stk-grid">
                <div class="stk-cell"><div class="lbl">总市值</div><div class="value" id="dtlCap">-</div></div>
                <div class="stk-cell"><div class="lbl">流通市值</div><div class="value" id="dtlCirCap">-</div></div>
                <div class="stk-cell"><div class="lbl">市盈率(动)</div><div class="value" id="dtlPE">-</div></div>
                <div class="stk-cell"><div class="lbl">市盈率(TTM)</div><div class="value" id="dtlPETTM">-</div></div>
                <div class="stk-cell"><div class="lbl">市净率</div><div class="value" id="dtlPB">-</div></div>
                <div class="stk-cell"><div class="lbl">换手率</div><div class="value" id="dtlTurnover">-</div></div>
                <div class="stk-cell"><div class="lbl">52周最高</div><div class="value" id="dtlH52">-</div></div>
                <div class="stk-cell"><div class="lbl">52周最低</div><div class="value" id="dtlL52">-</div></div>
            </div>
        </div>
    </div>
</div>

<h6 class="fw-semibold mb-2">⭐ 自选股 <span class="text-muted small">(30秒自动刷新)</span></h6>
<div class="row g-2" id="watchlistArea">
    <?php if (empty($watchlist)): ?>
    <div class="col-12 text-center text-muted py-4"><div style="font-size:2rem;margin-bottom:4px">📋</div><div>暂无自选股，搜索后添加</div></div>
    <?php else: ?>
    <?php foreach ($watchlist as $w):
        $q = $quoteMap[$w['symbol']] ?? null;
        $name = !empty($w['name']) && $w['name'] !== $w['symbol'] ? $w['name'] : ($q['name'] ?? $w['symbol']);
        $price = $q ? $q['current'] : 0; $change = $q ? $q['change_percent'] : 0;
        $cls = $change > 0 ? 'stk-up' : ($change < 0 ? 'stk-down' : 'stk-flat');
    ?>
    <div class="col-12 col-md-6 col-lg-4" id="watch-<?= htmlspecialchars($w['symbol']) ?>">
        <div class="stk-quote-card" data-symbol="<?= htmlspecialchars($w['symbol']) ?>" onclick="showDetail('<?= htmlspecialchars($w['symbol']) ?>','<?= htmlspecialchars(addslashes($name)) ?>')">
            <div class="d-flex justify-content-between"><div><div class="fw-semibold"><?= htmlspecialchars($name) ?></div><div class="text-muted" style="font-size:0.7rem"><?= htmlspecialchars(strtoupper($w['symbol'])) ?></div></div><div class="text-end"><div style="font-weight:700;font-size:1rem" class="<?= $cls ?>"><?= $price>0?number_format($price,2):'-' ?></div><div class="small <?= $cls ?>"><?= $change>0?'+':'' ?><?= number_format($change,2) ?>%</div></div></div>
            <div class="text-end mt-1"><button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.6rem" onclick="event.stopPropagation();removeWatch('<?= htmlspecialchars($w['symbol']) ?>')">移除</button></div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<script>
var cur = '', cname = '';
function h(s){ return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
var v = function(id){ return document.getElementById(id); };

// 搜索
var st; document.getElementById('stkSearch').addEventListener('input',function(){ clearTimeout(st); var kw=this.value.trim(); if(!kw){ v('stkSearchResults').style.display='none'; return; } st=setTimeout(doSearch,300); });
document.getElementById('stkSearch').addEventListener('keydown',function(e){ if(e.key=='Enter'){ e.preventDefault(); doSearch(); }});
document.addEventListener('click',function(e){ if(!e.target.closest('.stk-search-wrap')) v('stkSearchResults').style.display='none'; });

function doSearch(){ var kw=v('stkSearch').value.trim(); if(!kw)return; fetch('/public/index.php?route=stock-api&action=search&q='+encodeURIComponent(kw)).then(function(r){return r.json()}).then(function(d){ if(!d.ok||!d.results.length){v('stkSearchResults').innerHTML='<div class=stk-search-item style=color:#999>无结果</div>';v('stkSearchResults').style.display='block';return;} var h=''; d.results.forEach(function(r){ h+='<div class=stk-search-item onclick=selectStock(\''+r.symbol+'\',\''+h(r.name)+'\')><span>'+h(r.name)+'</span><span class="text-muted small">'+r.symbol.toUpperCase()+'</span></div>'; }); v('stkSearchResults').innerHTML=h; v('stkSearchResults').style.display='block'; }); }
function selectStock(s,n){ v('stkSearchResults').style.display='none'; v('stkSearch').value=''; showDetail(s,n); }

function showDetail(sym, name) {
    cur = sym; cname = name;
    v('stkDetail').style.display=''; v('dtlName').textContent=name; v('dtlSymbol').textContent=sym.toUpperCase();
    fetch('/public/index.php?route=stock-api&action=quote&symbol='+encodeURIComponent(sym)).then(function(r){return r.json()}).then(function(d){
        if(!d.ok) return;
        var q=d.quote, c=q.change>=0?'stk-up':(q.change<0?'stk-down':'stk-flat');
        v('dtlPrice').className='stk-price-big '+c; v('dtlPrice').textContent=q.current>0?q.current.toFixed(2):'-';
        v('dtlChange').className='stk-change-row '+c; v('dtlChange').textContent=(q.change>=0?'+':'')+q.change.toFixed(2)+'  '+(q.change_percent>=0?'+':'')+q.change_percent.toFixed(2)+'%';
        v('dtlOpen').textContent=q.open>0?formatNum(q.open):'-'; v('dtlPrev').textContent=q.prev_close>0?formatNum(q.prev_close):'-';
        v('dtlHigh').className=c; v('dtlHigh').textContent=q.high>0?formatNum(q.high):'-';
        v('dtlLow').className=c; v('dtlLow').textContent=q.low>0?formatNum(q.low):'-';
        v('dtlVol').textContent=q.volume>0?(q.volume/10000).toFixed(1)+'万手':'-';
        v('dtlAmt').textContent=q.amount>0?(q.amount/100000000).toFixed(2)+'亿':'-';
        v('dtlPct').className=c; v('dtlPct').textContent=(q.change_percent>=0?'+':'')+q.change_percent.toFixed(2)+'%';
        v('dtlAmpl').textContent=q.amplitude>0?q.amplitude.toFixed(2)+'%':'-';
        v('dtlCap').textContent=q.market_cap>0?q.market_cap.toFixed(0)+'亿':'-';
        v('dtlCirCap').textContent=q.circulating_cap>0?q.circulating_cap.toFixed(0)+'亿':'-';
        v('dtlPE').textContent=q.pe>0?q.pe.toFixed(2):'-'; v('dtlPETTM').textContent=q.pe_ttm>0?q.pe_ttm.toFixed(2):'-';
        v('dtlPB').textContent=q.pb>0?q.pb.toFixed(2):'-'; v('dtlTurnover').textContent=q.turnover_rate>0?q.turnover_rate.toFixed(2)+'%':'-';
        v('dtlH52').textContent=q.high_52w>0?formatNum(q.high_52w):'-'; v('dtlL52').textContent=q.low_52w>0?formatNum(q.low_52w):'-';
    });
    loadKline('101');
}
function formatNum(n){ return n>=1000?parseFloat(n).toFixed(0):parseFloat(n).toFixed(2); }

var chart=null, candleSeries=null;
function loadKline(scale, btn){
    document.querySelectorAll('.stk-tab').forEach(function(t){ t.classList.remove('active'); });
    if(btn) btn.classList.add('active');
    fetch('/public/index.php?route=stock-api&action=kline&symbol='+encodeURIComponent(cur)+'&scale='+scale).then(function(r){return r.json()}).then(function(d){ if(d.ok&&d.data.length)renderChart(d.data); });
}

function renderChart(data){
    var el=v('stkChart'); el.innerHTML=''; var dark=document.body.classList.contains('theme-dark');
    chart=LightweightCharts.createChart(el,{width:el.clientWidth,height:420,layout:{background:{type:'solid',color:dark?'#0f172a':'#ffffff'},textColor:dark?'#94a3b8':'#333'},grid:{vertLines:{color:'rgba(0,0,0,0.04)'},horzLines:{color:'rgba(0,0,0,0.04)'}},timeScale:{timeVisible:true}});
    candleSeries=chart.addCandlestickSeries({upColor:'#ef4444',downColor:'#22c55e',borderUpColor:'#ef4444',borderDownColor:'#22c55e',wickUpColor:'#ef4444',wickDownColor:'#22c55e'});
    candleSeries.setData(data); chart.timeScale().fitContent();
}

function toggleWatch(){ var f=new FormData(); f.append('action','add_watch'); f.append('symbol',cur); f.append('name',cname||v('dtlName').textContent); fetch('/public/index.php?route=stock-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message)}); }
function removeWatch(sym){ fetch('/public/index.php?route=stock-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=remove_watch&symbol='+encodeURIComponent(sym)}).then(function(r){return r.json()}).then(function(d){ var e=v('watch-'+sym); if(e) e.remove(); }); }

setInterval(function(){ document.querySelectorAll('.stk-quote-card[data-symbol]').forEach(function(card){ var sym=card.getAttribute('data-symbol'); fetch('/public/index.php?route=stock-api&action=quote&symbol='+encodeURIComponent(sym)).then(function(r){return r.json()}).then(function(d){ if(!d.ok)return; var q=d.quote,c=q.change>=0?'stk-up':(q.change<0?'stk-down':'stk-flat'),pe=card.querySelector('div[style*=700]'),ce=card.querySelector('.small'); if(pe){pe.className=c;pe.style.cssText='font-weight:700;font-size:1rem';pe.textContent=q.current>0?q.current.toFixed(2):'-';} if(ce){ce.className='small '+c;ce.textContent=(q.change_percent>0?'+':'')+q.change_percent.toFixed(2)+'%';} }); }); },30000);
</script>
