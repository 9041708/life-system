<?php
/** @var array $account */
/** @var array $positions */
/** @var array $quoteMap */
/** @var array $trades */
$balance = (float)$account['balance'];
$totalMV = 0; $totalCost = 0;
foreach ($positions as $p) {
    $q = $quoteMap[$p['symbol']] ?? null;
    $cp = $q ? (float)$q['current'] : (float)$p['avg_cost'];
    $totalMV += $cp * (int)$p['quantity'];
    $totalCost += (float)$p['avg_cost'] * (int)$p['quantity'];
}
$totalAssets = $balance + $totalMV;
$totalProfit = $totalAssets - (float)$account['initial_balance'];
$profitPct = (float)$account['initial_balance'] > 0 ? ($totalProfit / (float)$account['initial_balance']) * 100 : 0;
?>
<style>
.stk-up { color:#ef4444; }
.stk-down { color:#22c55e; }
.stk-summary-row { display:flex; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.stk-summary-item { flex:1; min-width:140px; border:1px solid rgba(0,0,0,0.06); border-radius:10px; padding:14px; text-align:center; background:rgba(255,255,255,0.5); }
body.theme-dark .stk-summary-item { background:rgba(30,41,59,0.4); border-color:rgba(148,163,184,0.12); }
.stk-summary-item .val { font-size:1.4rem; font-weight:700; }
.stk-summary-item .lbl { font-size:0.72rem; color:#999; margin-top:2px; }
.stk-trade-panel { display:flex; gap:16px; }
.stk-trade-side { flex:1; border:1px solid rgba(0,0,0,0.06); border-radius:10px; padding:16px; }
body.theme-dark .stk-trade-side { border-color:rgba(148,163,184,0.12); }
.stk-search-results { z-index:100; background:#fff; border:1px solid rgba(0,0,0,0.12); border-radius:0 0 8px 8px; box-shadow:0 4px 16px rgba(0,0,0,0.12); max-height:260px; overflow-y:auto; display:none; }
body.theme-dark .stk-search-results { background:#1e293b; border-color:rgba(148,163,184,0.2); }
.stk-search-item { padding:8px 12px; cursor:pointer; font-size:0.85rem; border-bottom:1px solid rgba(0,0,0,0.04); display:flex; justify-content:space-between; align-items:center; }
body.theme-dark .stk-search-item { border-bottom-color:rgba(148,163,184,0.08); }
.stk-search-item:hover { background:rgba(102,126,234,0.08); }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">💹 模拟交易</h5>
    <div class="d-flex gap-2">
        <a href="?route=stock-viewer" class="btn btn-sm btn-outline-secondary">← 股票行情</a>
        <button class="btn btn-sm btn-outline-danger" onclick="resetAccount()">重置账户</button>
    </div>
</div>

<div class="stk-summary-row">
    <div class="stk-summary-item">
        <div class="val"><?= number_format($totalAssets, 2) ?></div><div class="lbl">总资产(元)</div>
    </div>
    <div class="stk-summary-item">
        <div class="val" style="color:#3b82f6"><?= number_format($balance, 2) ?></div><div class="lbl">可用资金</div>
    </div>
    <div class="stk-summary-item">
        <div class="val"><?= number_format($totalMV, 2) ?></div><div class="lbl">持仓市值</div>
    </div>
    <div class="stk-summary-item">
        <div class="val <?= $totalProfit >= 0 ? 'stk-up' : 'stk-down' ?>"><?= $totalProfit >= 0 ? '+' : '' ?><?= number_format($totalProfit, 2) ?></div>
        <div class="lbl">总盈亏 (<?= $profitPct >= 0 ? '+' : '' ?><?= number_format($profitPct, 2) ?>%)</div>
    </div>
    <div class="stk-summary-item">
        <div class="val <?= $balance > 0 ? 'stk-up' : '' ?>" style="font-size:1rem"><?= number_format((float)$account['initial_balance'], 0) ?></div>
        <div class="lbl">初始资金 | 佣金 <?= number_format((float)$account['commission_rate'] * 10000, 1) ?>‱ | 印花税 <?= number_format((float)$account['stamp_tax_rate'] * 1000, 1) ?>‰(卖)</div>
    </div>
</div>

<!-- 交易面板 -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">🔍 交易操作</h6>
        <div class="mb-2" style="position:relative">
            <label class="form-label small">搜索股票</label>
            <div class="input-group input-group-sm">
                <input type="text" id="tradeSearch" class="form-control" placeholder="输入名称或代码搜索..." autocomplete="off">
                <button class="btn btn-outline-secondary" onclick="searchTradeStock()">搜索</button>
            </div>
            <div id="tradeSearchResults" class="stk-search-results" style="position:absolute;width:100%"></div>
        </div>
        <div id="tradeQuoteInfo" class="small p-2 rounded mb-2" style="display:none;background:rgba(102,126,234,0.05)">
            <div class="d-flex justify-content-between">
                <span class="fw-semibold" id="tradeQuoteName">-</span>
                <span id="tradeQuotePrice" class="fw-bold">-</span>
            </div>
            <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem">
                <span>涨跌幅：<span id="tradeQuoteChange">-</span></span>
                <span>可买：<span id="tradeCanBuy">-</span>手</span>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-4"><label class="form-label small">价格(元)</label><input type="number" id="tradePrice" class="form-control form-control-sm" step="0.01"></div>
            <div class="col-4"><label class="form-label small">数量(手)</label><input type="number" id="tradeLots" class="form-control form-control-sm" value="1" min="1"></div>
            <div class="col-4" style="display:flex;flex-direction:column;justify-content:flex-end">
                <input type="hidden" id="tradeSymbol"><input type="hidden" id="tradeName">
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm flex-fill" onclick="doTrade('buy')">买入</button>
                    <button class="btn btn-danger btn-sm flex-fill" onclick="doTrade('sell')">卖出</button>
                </div>
            </div>
        </div>
        <div class="small text-muted mt-1" id="tradeCost">预估金额：-</div>
    </div>
</div>

<!-- 持仓 -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-semibold mb-2">📊 当前持仓</h6>
        <?php if (empty($positions)): ?>
        <div class="text-muted small text-center py-3">暂无持仓</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>股票</th><th class="text-end">持仓/成本</th><th class="text-end">现价</th><th class="text-end">市值</th><th class="text-end">盈亏</th></tr></thead>
                <tbody>
                <?php foreach ($positions as $p):
                    $q = $quoteMap[$p['symbol']] ?? null;
                    $cp = $q ? (float)$q['current'] : (float)$p['avg_cost'];
                    $cost = (float)$p['avg_cost']; $qty = (int)$p['quantity'];
                    $profit = ($cp - $cost) * $qty;
                    $profitP = $cost > 0 ? (($cp - $cost) / $cost) * 100 : 0;
                    $cls = $profit >= 0 ? 'stk-up' : 'stk-down';
                ?>
                <tr>
                    <td><div class="fw-semibold small"><?= htmlspecialchars($p['name']) ?></div><div class="text-muted" style="font-size:0.7rem"><?= htmlspecialchars(strtoupper($p['symbol'])) ?></div></td>
                    <td class="text-end small"><?= $qty ?>股<br><span class="text-muted"><?= number_format($cost, 2) ?></span></td>
                    <td class="text-end fw-bold small <?= $cls ?>"><?= number_format($cp, 2) ?></td>
                    <td class="text-end small"><?= number_format($cp * $qty, 2) ?><br><span class="text-muted" style="font-size:0.7rem"><?= $totalAssets > 0 ? number_format(($cp * $qty) / $totalAssets * 100, 1) . '%' : '-' ?></span></td>
                    <td class="text-end small <?= $cls ?>"><?= $profit >= 0 ? '+' : '' ?><?= number_format($profit, 2) ?><br><span style="font-size:0.7rem"><?= $profitP >= 0 ? '+' : '' ?><?= number_format($profitP, 2) ?>%</span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 交易记录 -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-semibold mb-2">📝 交易记录</h6>
        <div style="max-height:300px;overflow-y:auto">
        <?php if (empty($trades)): ?>
        <div class="text-muted small text-center py-2">暂无交易</div>
        <?php else: ?>
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>时间</th><th>类型</th><th>股票</th><th class="text-end">价格</th><th class="text-end">数量</th><th class="text-end">手续费</th><th class="text-end">印花税</th><th class="text-end">金额</th></tr></thead>
            <tbody>
            <?php foreach ($trades as $t): ?>
            <tr>
                <td class="small text-muted"style="white-space:nowrap"><?= substr($t['created_at'],5,11) ?></td>
                <td><span class="badge <?= $t['type']==='buy'?'bg-success':'bg-danger' ?> py-0" style="font-size:0.65rem"><?= $t['type']==='buy'?'买入':'卖出' ?></span></td>
                <td class="small"><?= htmlspecialchars($t['name']) ?></td>
                <td class="text-end small"><?= number_format($t['price'], 2) ?></td>
                <td class="text-end small"><?= (int)$t['quantity'] ?></td>
                <td class="text-end small text-muted"><?= number_format($t['commission'], 2) ?></td>
                <td class="text-end small text-muted"><?= number_format($t['stamp_tax'], 2) ?></td>
                <td class="text-end small fw-semibold <?= $t['type']==='buy'?'stk-up':'stk-down' ?>"><?= $t['type']==='buy'?'-':'' ?><?= number_format($t['total_amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>
    </div>
</div>

<script>
function searchTradeStock() {
    var kw = document.getElementById('tradeSearch').value.trim();
    if (!kw) { alert('请输入股票名称或代码'); return; }
    fetch('/public/index.php?route=stock-api&action=search&q=' + encodeURIComponent(kw))
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.ok || !d.results.length) { document.getElementById('tradeSearchResults').innerHTML='<div class="stk-search-item text-muted">无结果</div>'; document.getElementById('tradeSearchResults').style.display='block'; return; }
        var html = '';
        d.results.forEach(function(r){ html += '<div class="stk-search-item" onclick="selectTrade(\''+r.symbol+'\',\''+h(r.name)+'\')"><span>'+h(r.name)+'</span><span class="text-muted small">'+r.symbol.toUpperCase()+'</span></div>'; });
        document.getElementById('tradeSearchResults').innerHTML = html;
        document.getElementById('tradeSearchResults').style.display = 'block';
    });
}
function selectTrade(sym, name) {
    document.getElementById('tradeSymbol').value = sym; document.getElementById('tradeName').value = name;
    document.getElementById('tradeSearch').value = ''; document.getElementById('tradeSearchResults').style.display = 'none';
    document.getElementById('tradeQuoteInfo').style.display = '';
    document.getElementById('tradeQuoteName').textContent = name + ' (' + sym.toUpperCase() + ')';
    fetch('/public/index.php?route=stock-api&action=quote&symbol=' + encodeURIComponent(sym))
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.ok) return;
        var q = d.quote, cls = q.change >= 0 ? 'stk-up' : 'stk-down';
        document.getElementById('tradePrice').value = q.current.toFixed(2);
        document.getElementById('tradeQuotePrice').textContent = '¥' + q.current.toFixed(2);
        document.getElementById('tradeQuotePrice').className = 'fw-bold ' + cls;
        document.getElementById('tradeQuoteChange').textContent = (q.change>=0?'+':'') + q.change_percent.toFixed(2) + '%';
        document.getElementById('tradeQuoteChange').className = cls;
        var balance = <?= $balance ?>;
        document.getElementById('tradeCanBuy').textContent = Math.floor(balance / (q.current * 100));
        updateCost();
    });
}
function h(s){ return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function updateCost() {
    var price = parseFloat(document.getElementById('tradePrice').value) || 0;
    var lots = parseInt(document.getElementById('tradeLots').value) || 0;
    document.getElementById('tradeCost').textContent = '预估金额：¥' + (price * lots * 100).toFixed(2) + '（另含佣金万三）';
}
document.getElementById('tradePrice').addEventListener('input', updateCost);
document.getElementById('tradeLots').addEventListener('input', updateCost);
document.getElementById('tradeSearch').addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); searchTradeStock(); }});
document.addEventListener('click', function(e){ if (!e.target.closest('#tradeSearchResults') && !e.target.closest('.input-group')) document.getElementById('tradeSearchResults').style.display='none'; });

function doTrade(type) {
    var sym = document.getElementById('tradeSymbol').value.trim(), name = document.getElementById('tradeName').value.trim();
    if (!sym || !name) { alert('请先搜索并选择股票'); return; }
    var price = parseFloat(document.getElementById('tradePrice').value);
    var lots = parseInt(document.getElementById('tradeLots').value);
    if (!price || !lots) { alert('请填写价格和数量'); return; }
    var cost = price * lots * 100;
    if (type === 'buy' && cost > <?= $balance ?>) { alert('可用资金不足！需 ¥' + cost.toFixed(2)); return; }
    var fd = new FormData();
    fd.append('action', type); fd.append('symbol', sym); fd.append('name', name);
    fd.append('price', price); fd.append('quantity', lots * 100);
    fetch('/public/index.php?route=stock-api', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.ok){ alert(d.message); location.reload(); } else alert(d.error); });
}

function resetAccount() {
    var bal = prompt('初始资金（元）', '1000000');
    if (!bal || bal <= 0) return;
    if (!confirm('确定重置？所有持仓和交易记录将清空。')) return;
    fetch('/public/index.php?route=stock-api', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=reset_account&balance='+bal })
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.ok){ alert(d.message); location.reload(); } else alert(d.error); });
}
</script>
