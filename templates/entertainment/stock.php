<?php use App\Model\EntOrder; $uid=(int)($_SESSION['user_id']??0); ?>
<script src="https://cdn.jsdelivr.net/npm/lightweight-charts@4.1.0/dist/lightweight-charts.standalone.production.js"></script>
<style>
.ent-wrap{display:flex;height:calc(100vh - 170px);gap:0;overflow:hidden}
.ent-left{width:270px;min-width:270px;display:flex;flex-direction:column;overflow:hidden;border-right:1px solid rgba(0,0,0,0.08);background:rgba(255,255,255,0.7)}
body.theme-dark .ent-left{border-right-color:rgba(148,163,184,0.15);background:rgba(30,41,59,0.6)}
.ent-stock-list{flex:1;overflow-y:auto}
.ent-stock-item{display:flex;justify-content:space-between;align-items:center;padding:7px 10px;border-bottom:1px solid rgba(0,0,0,0.04);cursor:pointer;font-size:0.81rem;transition:background 0.1s}
.ent-stock-item:hover,.ent-stock-item.sel{background:rgba(102,126,234,0.1)}
.ent-right{flex:1;display:flex;flex-direction:column;overflow:hidden;padding:0 12px;background:rgba(255,255,255,0.7)}
body.theme-dark .ent-right{background:rgba(30,41,59,0.6)}
.ent-detail{display:none;flex-direction:column}.ent-detail.show{display:flex}
.ent-dh{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px}
.ent-dh .ep{font-size:2rem;font-weight:800;line-height:1}.ent-dh .ec{font-size:0.9rem;font-weight:600}
.ent-info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:4px;margin-bottom:6px}
.ent-info-cell{padding:6px 8px;border:1px solid rgba(0,0,0,0.06);border-radius:6px;background:rgba(255,255,255,0.5);font-size:0.78rem}
body.theme-dark .ent-info-cell{background:rgba(30,41,59,0.4);border-color:rgba(148,163,184,0.1)}
.ent-info-cell .lb{font-size:0.66rem;color:#999}.ent-info-cell .vl{font-weight:600}
.ent-chart-wrap{position:relative;border:1px solid rgba(0,0,0,0.08);border-radius:8px;overflow:hidden;background:#fff;margin-bottom:6px}
body.theme-dark .ent-chart-wrap{background:#1a1a2e;border-color:rgba(148,163,184,0.12)}
.ent-chart{height:280px}
.ent-chart.full{position:fixed;top:60px;left:0;width:100%;height:calc(100vh - 60px);z-index:1000;border-radius:0}
.ent-chart-zoom{position:absolute;top:6px;right:6px;z-index:2;background:rgba(0,0,0,0.5);color:#fff;border:none;border-radius:4px;padding:2px 8px;cursor:pointer;font-size:0.7rem}
.ent-range-bar{display:flex;gap:2px;margin-bottom:4px}
.ent-range-btn{padding:2px 10px;border:1px solid rgba(0,0,0,0.1);border-radius:4px;background:transparent;cursor:pointer;font-size:0.7rem}
.ent-range-btn.active{background:#667eea;color:#fff;border-color:#667eea}
.ent-trade-row{display:flex;gap:6px;align-items:flex-end;padding:8px 0;flex-wrap:wrap;background:rgba(255,255,255,0.6);border-radius:8px;padding:10px}
body.theme-dark .ent-trade-row{background:rgba(30,41,59,0.5)}
.ent-trade-row input[type=number]{width:80px}
.ent-qbtn{padding:3px 6px;border:1px solid rgba(0,0,0,0.15);border-radius:4px;background:transparent;cursor:pointer;font-size:0.68rem}
.ent-qbtn:hover{background:rgba(102,126,234,0.1)}
.ent-up{color:#ef4444}.ent-down{color:#22c55e}
.ent-ban{background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);padding:6px 10px;border-radius:6px;text-align:center;font-size:0.73rem;color:#b45309;margin-bottom:6px}
.ent-admin-ban{background:rgba(102,126,234,0.05);border:1px solid rgba(102,126,234,0.15);padding:6px 10px;border-radius:6px;text-align:center;font-size:0.73rem;color:#4338ca;margin-bottom:6px}
.ent-tabs{display:flex;gap:2px;margin-bottom:6px}
.ent-tab{flex:1;padding:4px 8px;border:1px solid rgba(0,0,0,0.08);border-radius:6px;background:rgba(255,255,255,0.5);cursor:pointer;font-size:0.76rem;text-align:center;white-space:nowrap}
.ent-tab.active{background:#667eea;color:#fff;border-color:#667eea}
.ent-panel{display:none}.ent-panel.active{display:block}
.ent-empty{text-align:center;color:#999;padding:60px 0}
.ent-news-bar{font-size:0.72rem;padding:4px 8px;margin:4px 0;border-radius:4px;display:flex;align-items:center;gap:6px}
.ent-news-bar.good{background:rgba(239,68,68,0.06);border-left:3px solid #ef4444}
.ent-news-bar.bad{background:rgba(34,197,94,0.06);border-left:3px solid #22c55e}
.pos-quick-menu{display:flex;gap:2px;position:absolute;background:#fff;border:1px solid rgba(0,0,0,0.15);border-radius:6px;padding:3px;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
body.theme-dark .pos-quick-menu{background:#1e293b;border-color:rgba(148,163,184,0.2)}
</style>

<div class="ent-ban">🎮 仅供娱乐，实际投资请注意股市风险 <span class="ms-2" id="tradeStatus"><?= $isTradeTime?'🟢交易中':'🔴休市' ?></span>
    <span class="ms-2 text-muted small" id="refreshCd" style="font-variant-numeric:tabular-nums">刷新 10s</span>
    <div style="display:inline-flex;align-items:center;gap:2px;margin-left:12px;height:16px;width:200px;background:#e5e7eb;border-radius:3px;overflow:hidden;position:relative;vertical-align:middle">
        <div style="width:33.33%;height:100%;background:#374151" title="休市 00:00-07:59"></div>
        <div style="width:66.67%;height:100%;background:#22c55e" title="交易时段 08:00-23:59"></div>
        <div id="timeMarker" style="position:absolute;top:0;width:2px;height:100%;background:#ef4444;left:0%"></div>
    </div>
</div>
<?php if($sysNotice): ?><div class="ent-admin-ban">📢 <?= htmlspecialchars($sysNotice) ?></div><?php endif; ?>
<?php if(!empty($overdue)): ?>
<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:10px;margin-bottom:6px"><strong class="text-danger">⚠️ 贷款逾期</strong><?php foreach($overdue as $ov):?><div class="small">¥<?=number_format($ov['amount'],0)?> 应还 ¥<?=number_format(round((float)$ov['total_repayable']-(float)$ov['repaid'],2),2)?> <button class="btn btn-sm btn-danger py-0" onclick="repayLoan(<?=$ov['id']?>)">还款</button> <button class="btn btn-sm btn-outline-danger py-0" onclick="goBankrupt()">破产清算</button></div><?php endforeach;?></div><?php endif;?>

<div class="ent-tabs">
    <button class="ent-tab active" onclick="sp('trade',this)">行情交易</button>
    <button class="ent-tab" onclick="sp('pos',this)">持仓(<?=count($positions)?>)</button>
    <button class="ent-tab" onclick="sp('ord',this)">委托(<?=EntOrder::countPending($uid)?>)</button>
    <button class="ent-tab" onclick="sp('acc',this)">个人</button>
    <button class="ent-tab" onclick="sp('rank',this)">排行</button>
    <?php if($isAdmin):?><button class="ent-tab" onclick="sp('adm',this)">管理</button><?php endif;?>
</div>

<div class="ent-panel active" id="pn-trade">
<div class="ent-wrap">
    <div class="ent-left"><div class="ent-stock-list" id="stockList">
        <div style="padding:6px 8px"><input type="text" id="stockSearch" class="form-control form-control-sm" placeholder="🔍 搜索股票..." oninput="filterStocks()"></div>
        <?php foreach($stocks as $s):$p=$s['base_price']>0?round((($s['current_price']-$s['base_price'])/$s['base_price'])*100,2):0;$c=$p>=0?'ent-up':'ent-down';?>
        <div class="ent-stock-item" data-sid="<?=$s['id']?>" onclick="sel(<?=$s['id']?>,'<?=htmlspecialchars(addslashes($s['name']))?>','<?=$s['symbol']?>',<?=$s['current_price']?>)"><div><div class="fw-semibold" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($s['name'])?></div><div class="text-muted" style="font-size:0.68rem"><?=$s['symbol']?></div></div><div class="text-end"><div class="fw-bold <?=$c?>" style="font-size:0.83rem" id="pr-<?=$s['id']?>"><?=number_format($s['current_price'],2)?></div><div class="small <?=$c?>" id="pc-<?=$s['id']?>"><?=$p>=0?'+':''?><?=$p?>%</div></div></div>
        <?php endforeach;?>
    </div></div>
    <div class="ent-right">
        <div class="ent-detail" id="detail"><div id="delistWarn" style="display:none;background:#fff3cd;color:#856404;padding:4px 10px;border-radius:4px;margin-bottom:8px;font-size:0.85rem;text-align:center">⚠️ 该股票已退市，请不要买入</div><div class="ent-dh"><div><div class="fw-bold" id="dn">-</div><div class="text-muted small" id="ds">-</div></div><div class="text-end"><div class="ep ent-flat" id="dp">-</div><div class="ec ent-flat" id="dc">-</div></div></div><div class="ent-info-grid" id="infoGrid"></div>
            <div class="ent-range-bar"><button class="ent-range-btn active" id="range-1m" onclick="ldK('1m',this,null)">分时</button><button class="ent-range-btn" onclick="ldK('5d',this,null)">五日</button><button class="ent-range-btn" onclick="ldK('1d',this,null)">日K</button><button class="ent-range-btn" onclick="ldK('1w',this,null)">周K</button><button class="ent-range-btn" onclick="ldK('1M',this,null)">月K</button></div>
            <div class="ent-chart-wrap"><div class="ent-chart" id="chart"></div><button class="ent-chart-zoom" onclick="tgFull()">⛶ 全屏</button></div>
            <div id="posInfo" style="display:none;padding:8px 10px;border-radius:8px;margin-bottom:6px;font-size:0.82rem"></div>
            <div class="ent-trade-row">
                <button class="ent-qbtn" onclick="setQty(0.25,'buy',1)">买1/4</button><button class="ent-qbtn" onclick="setQty(1/3,'buy',1)">买1/3</button><button class="ent-qbtn" onclick="setQty(0.5,'buy',1)">买半仓</button><button class="ent-qbtn" onclick="setQty(1,'buy',1)">全买入</button>
                <button class="ent-qbtn" onclick="setQty(0.25,'sell',1)">卖1/4</button><button class="ent-qbtn" onclick="setQty(1/3,'sell',1)">卖1/3</button><button class="ent-qbtn" onclick="setQty(0.5,'sell',1)">卖半仓</button><button class="ent-qbtn" onclick="setQty(1,'sell',1)">全卖出</button>
                <input type="number" id="tp" class="form-control form-control-sm" placeholder="委托价" step="0.01"><input type="number" id="tq" class="form-control form-control-sm" placeholder="100" value="100" step="100" min="100">
<button class="btn btn-sm btn-success" onclick="doTrade('buy')">买入</button><button class="btn btn-sm btn-danger" onclick="doTrade('sell')">卖出</button>
<span class="small text-muted"><?=$isTradeTime?'市价匹配立即成交':'🔴休市委托待开盘成交'?> | 买0.1%卖0.2%</span>
            </div>
            <div id="stockNews" style="max-height:150px;overflow-y:auto"></div>
        </div>
        <div class="ent-empty" id="detailEmpty">← 点击左侧股票查看走势和交易</div>
    </div>
</div></div>

<!-- 持仓 -->
<div class="ent-panel" id="pn-pos" style="background:rgba(255,255,255,0.7);padding:12px;border-radius:8px">
<?php if(empty($positions)): ?><div class="ent-empty">暂无持仓</div><?php else: ?><table class="table table-sm" id="posTable"><thead><tr><th>股票</th><th class="text-end">持仓/成本</th><th class="text-end">现价</th><th class="text-end">市值</th><th class="text-end">盈亏</th><th class="text-end">盈亏%</th><th></th></tr></thead><tbody>
<?php foreach($positions as $p):$pf=((float)$p['current_price']-(float)$p['avg_cost'])*(int)$p['quantity'];$pp=$p['avg_cost']>0?round((((float)$p['current_price']-(float)$p['avg_cost'])/(float)$p['avg_cost'])*100,2):0;$c=$pf>=0?'ent-up':'ent-down';?><tr><td><span class="fw-semibold small"><?=htmlspecialchars($p['name'])?></span><br><span class="text-muted" style="font-size:0.68rem"><?=$p['symbol']?></span></td><td class="text-end small"><?=(int)$p['quantity']?>股<br><span class="text-muted"><?=number_format($p['avg_cost'],2)?></span></td><td class="text-end fw-bold <?=$c?> pos-price" data-sid="<?=$p['stock_id']?>" data-cost="<?=$p['avg_cost']?>"><?=number_format($p['current_price'],2)?></td><td class="text-end small pos-mv" data-sid="<?=$p['stock_id']?>"><?=number_format((float)$p['current_price']*(int)$p['quantity'],2)?></td><td class="text-end small fw-bold <?=$c?> pos-pl" data-sid="<?=$p['stock_id']?>"><?=$pf>=0?'+':''?><?=number_format($pf,2)?></td><td class="text-end small <?=$c?> pos-pp" data-sid="<?=$p['stock_id']?>"><?=$pp>=0?'+':''?><?=$pp?>%</td><td style="white-space:nowrap">
<button class="btn btn-sm btn-outline-primary py-0" style="font-size:0.6rem" onclick="sel(<?=$p['stock_id']?>,'<?=htmlspecialchars(addslashes($p['name']))?>','<?=$p['symbol']?>',<?=$p['current_price']?>);sp('trade',document.querySelector('.ent-tab:first-child'))">看</button>
<button class="btn btn-sm btn-success py-0" style="font-size:0.6rem" onclick="togglePosMenu(event,'posBuy<?=$p['stock_id']?>')">+</button>
<button class="btn btn-sm btn-danger py-0" style="font-size:0.6rem" onclick="togglePosMenu(event,'posSell<?=$p['stock_id']?>')">-</button>
<div class="pos-quick-menu" id="posBuy<?=$p['stock_id']?>" style="display:none">
    <button class="btn btn-sm btn-outline-success py-0" style="font-size:0.56rem" onclick="quickTrade(<?=$p['stock_id']?>,'buy',<?=$p['current_price']?>,'<?=$p['quantity']?>',0.25)">1/4仓</button>
    <button class="btn btn-sm btn-outline-success py-0" style="font-size:0.56rem" onclick="quickTrade(<?=$p['stock_id']?>,'buy',<?=$p['current_price']?>,'<?=$p['quantity']?>',0.5)">半仓</button>
    <button class="btn btn-sm btn-outline-success py-0" style="font-size:0.56rem" onclick="quickTrade(<?=$p['stock_id']?>,'buy',<?=$p['current_price']?>,'<?=$p['quantity']?>',1)">全仓</button>
</div>
<div class="pos-quick-menu" id="posSell<?=$p['stock_id']?>" style="display:none">
    <button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.56rem" onclick="quickTrade(<?=$p['stock_id']?>,'sell',<?=$p['current_price']?>,'<?=$p['quantity']?>',0.25)">卖1/4</button>
    <button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.56rem" onclick="quickTrade(<?=$p['stock_id']?>,'sell',<?=$p['current_price']?>,'<?=$p['quantity']?>',0.5)">卖一半</button>
    <button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.56rem" onclick="quickTrade(<?=$p['stock_id']?>,'sell',<?=$p['current_price']?>,'<?=$p['quantity']?>',1)">全卖</button>
</div>
</td></tr><?php endforeach;?></tbody></table><?php endif;?></div>

<!-- 委托 -->
<div class="ent-panel" id="pn-ord" style="background:rgba(255,255,255,0.7);padding:12px;border-radius:8px">
<?php if(empty($orders)):?><div class="ent-empty" id="pn-ord-msg">暂无委托</div><?php else:?>
<table class="table table-sm"><thead><tr><th>股票</th><th>类型</th><th class="text-end">价格</th><th class="text-end">数量</th><th>状态</th><th></th></tr></thead><tbody id="pn-ord-body">
<?php foreach($orders as $o):$sl=['pending'=>'⏳待成交','done'=>'✅成交','cancelled'=>'❌取消'];$sc=['pending'=>'warning','done'=>'success','cancelled'=>'secondary'];?>
<tr><td><span class="small"><?=htmlspecialchars($o['name'])?></span></td><td><span class="badge bg-<?=$o['type']==='buy'?'success':'danger'?>"><?=$o['type']==='buy'?'买':'卖'?></span></td><td class="text-end small"><?=number_format($o['price'],2)?></td><td class="text-end small"><?=$o['quantity']?></td><td><span class="badge bg-<?=$sc[$o['status']]?>"><?=$sl[$o['status']]?></span></td><td><?php if($o['status']==='pending'):?><button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.65rem" onclick="cancelOrder(<?=$o['id']?>)">撤单</button><?php endif;?></td></tr>
<?php endforeach;?></tbody></table><?php endif;?></div>

<!-- 个人 -->
<div class="ent-panel" id="pn-acc">
    <div class="row g-3">
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-4 fw-bold" id="sumAssets"><?=number_format($totalAssets,2)?></div><div class="text-muted small">总资产</div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-4 fw-bold" id="sumBalance"><?=number_format($acc['balance'],2)?></div><div class="text-muted small">可用</div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-4 fw-bold" id="sumMV"><?=number_format($totalMV,2)?></div><div class="text-muted small">市值</div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-4 fw-bold" id="sumProfit"><?=$totalProfit>=0?'+':''?><?=number_format($totalProfit,2)?></div><div class="text-muted small">盈亏</div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-4 fw-bold"><?=$wr?>%</div><div class="text-muted small">胜率</div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-4 fw-bold"><?=(int)$acc['bankruptcy_count']?></div><div class="text-muted small">破产</div></div></div></div>
    </div>
    <div class="row g-2 mt-2">
        <div class="col-6"><button class="btn btn-sm btn-outline-warning w-100" onclick="exchangeStock()">L币兑换资金</button></div>
        <div class="col-6"><button class="btn btn-sm btn-outline-info w-100" onclick="redeemStock()">赎回为L币</button></div>
    </div>
    <?php if(!empty($loans)): ?><h6 class="mt-3">借贷记录</h6><table class="table table-sm"><thead><tr><th>金额</th><th>利率</th><th>方式</th><th>应还</th><th>已还</th><th>期限</th><th>状态</th><th></th></tr></thead><tbody>
    <?php foreach($loans as $ln):$ms=['equal'=>'等额本息','interest_first'=>'先息后本','equal_principal'=>'等额本金'];$ss=['active'=>'正常','repaid'=>'已还','overdue'=>'逾期'];?>
    <tr><td><?=number_format($ln['amount'],0)?></td><td><?=$ln['interest_rate']?>%</td><td><?=$ms[$ln['repay_method']]?></td><td><?=number_format($ln['total_repayable'],2)?></td><td><?=number_format($ln['repaid'],2)?></td><td><?=$ln['due_date']?></td><td><span class="badge bg-<?=$ln['status']==='repaid'?'success':($ln['status']==='overdue'?'danger':'warning')?>"><?=$ss[$ln['status']]?></span></td>
        <td><?php if($ln['status']==='active'):?><button class="btn btn-sm btn-outline-primary py-0" style="font-size:0.65rem" onclick="repayLoan(<?=$ln['id']?>)">还款</button><?php endif;?></td></tr>
    <?php endforeach;?></tbody></table><?php endif;?>
    <?php if(!empty($trades)):?>
    <h6 class="mt-3">📜 交易记录</h6>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>时间</th><th>股票</th><th>类型</th><th class="text-end">价格</th><th class="text-end">数量</th><th class="text-end">手续费</th><th class="text-end">金额</th></tr></thead><tbody>
    <?php foreach($trades as $t):?>
    <tr><td class="small text-muted"><?=substr($t['created_at'],5,11)?></td><td class="small"><?=htmlspecialchars($t['name'])?></td><td><span class="badge bg-<?=$t['type']==='buy'?'success':'danger'?>"><?=$t['type']==='buy'?'买':'卖'?></span></td><td class="text-end small"><?=number_format($t['price'],2)?></td><td class="text-end small"><?=$t['quantity']?></td><td class="text-end small text-muted"><?=number_format($t['fee'],2)?></td><td class="text-end small fw-bold <?=$t['type']==='buy'?'ent-up':'ent-down'?>"><?=$t['type']==='buy'?'-':''?><?=number_format($t['total_amount'],2)?></td></tr>
    <?php endforeach;?></tbody></table></div>
    <?php if($tradePages>1):?><div class="d-flex justify-content-center gap-2 mt-2 small"><?php for($p=1;$p<=$tradePages;$p++):?><a href="?route=entertainment&trade_page=<?=$p?>" class="btn btn-sm <?=$p==$tradePage?'btn-primary':'btn-outline-secondary'?>"><?=$p?></a><?php endfor;?></div><?php endif;?>
    <?php endif;?>
    <?php if((float)$acc['balance']<=0&&empty($positions)&&$loanEnabled):?>
    <div class="mt-3 p-2 border rounded" style="background:rgba(255,255,255,0.6)"><div class="small fw-semibold mb-1">🏦 借贷</div><div class="row g-2"><div class="col-3"><input id="loanAmt" type="number" class="form-control form-control-sm" placeholder="金额" max="<?=$loanMax?>"></div><div class="col-3"><select id="loanMethod" class="form-select form-select-sm"><option value="equal">等额本息</option><option value="interest_first">先息后本</option><option value="equal_principal">等额本金</option></select></div><div class="col-2"><button class="btn btn-sm btn-warning" onclick="doLoan()">借贷</button></div><div class="col-4 small text-muted">年利率<?=$loanRate?>% 上限<?=$loanMax?></div></div></div><?php endif;?>
    <?php if((float)$acc['balance']<0):?><div class="text-center mt-3"><button class="btn btn-sm btn-outline-danger" onclick="goBankrupt()">🏳️ 破产清算</button></div><?php endif;?>
</div>

<div class="ent-panel" id="pn-rank"><table class="table table-sm"><thead><tr><th>#</th><th>昵称</th><th class="text-end">可用资金</th><th class="text-end">盈亏</th><th class="text-end">胜率</th><th class="text-end">总资产</th></tr></thead><tbody>
<?php $rk=0;foreach($lb as $r):$rk++;$profit=(float)($r['profit']??0);$wr=(float)($r['win_rate']??0);$total=(float)($r['total_assets']??0);?><tr><td><?=$rk?></td><td><?=htmlspecialchars($r['nickname']?:$r['username']?:'用户'.$r['user_id'])?></td><td class="text-end"><?=number_format($r['balance'],2)?></td><td class="text-end <?=$profit>=0?'ent-up':'ent-down'?>"><?=$profit>=0?'+':''?><?=number_format($profit,2)?></td><td class="text-end"><?=$wr?>%</td><td class="text-end fw-bold"><?=number_format($total,2)?></td></tr><?php endforeach;?></tbody></table></div>

<?php if($isAdmin):?>
<div class="ent-panel" id="pn-adm">
<details><summary class="fw-semibold small mb-1" style="cursor:pointer">📊 股票管理 (<?=count($adminStocks)?>只)</summary>
    <button class="btn btn-sm btn-success mb-2" onclick="addStock()">+ 新增股票</button>
    <table class="table table-sm mt-1"><thead><tr><th>代码</th><th>名称</th><th class="text-end">现价</th><th>简介</th><th></th></tr></thead><tbody>
<?php foreach($adminStocks as $s):?><tr><td><?=$s['symbol']?></td><td><?=htmlspecialchars($s['name'])?></td><td class="text-end"><?=number_format($s['current_price'],2)?></td><td class="text-muted small"><?=htmlspecialchars(mb_substr((string)($s['description']??''),0,15))?></td><td><button class="btn btn-sm btn-outline-primary py-0" style="font-size:0.65rem" onclick='editStock(<?=json_encode($s,JSON_UNESCAPED_UNICODE|JSON_HEX_APOS)?>)'>编辑</button><?php if($s['is_active']):?><button class="btn btn-sm btn-outline-danger py-0 ms-1" style="font-size:0.65rem" onclick="if(confirm('确认退市?')){var f=new FormData();f.append('action','delist_stock');f.append('stock_id',<?=$s['id']?>);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok)location.reload();});}">退市</button><?php else:?><span class="badge bg-danger ms-1">已退市</span><button class="btn btn-sm btn-outline-success py-0 ms-1" style="font-size:0.65rem" onclick="var f=new FormData();f.append('action','relist_stock');f.append('stock_id',<?=$s['id']?>);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok)location.reload();});">上市</button><?php endif;?></td></tr><?php endforeach;?></tbody></table></details>

<details><summary class="fw-semibold small mt-2" style="cursor:pointer">📰 新闻管理 (<?=count($adminNews)?>条)</summary>
    <button class="btn btn-sm btn-primary mb-2" onclick="openNewsModal()">+ 发布新闻</button>
    <?php if(empty($adminNews)):?><div class="text-muted small">暂无新闻</div><?php else:?>
    <table class="table table-sm" id="newsTable"><thead><tr><th>股票</th><th>标题</th><th>影响</th><th>强度</th><th>小时</th><th></th></tr></thead><tbody>
    <?php $ni=0;foreach($adminNews as $n):$ex=strtotime($n['created_at'])+$n['expire_hours']*3600;$page=floor($ni/10);?>
    <tr class="news-row" data-news-page="<?=$page?>"><td><?=htmlspecialchars($n['name'])?></td><td><?=htmlspecialchars($n['title'])?></td><td><?=$n['effect']==='positive'?'📈利好':'📉利空'?></td><td><?=$n['strength']?></td><td><?=$n['expire_hours']?></td><td><span class="small text-muted"><?=time()<$ex?'生效':'过期'?></span> <button class="btn btn-sm btn-outline-danger py-0" onclick="delNews(<?=$n['id']?>)">删</button></td></tr>
    <?php $ni++;endforeach;?>
    </tbody></table>
    <div class="d-flex justify-content-between align-items-center mt-1 small" id="newsPager" style="<?=count($adminNews)<=10?'display:none':''?>">
        <button class="btn btn-sm btn-outline-secondary py-0" onclick="prevNewsPage()">上一页</button>
        <span id="newsPageInfo">第1页</span>
        <button class="btn btn-sm btn-outline-secondary py-0" onclick="nextNewsPage()">下一页</button>
    </div>
    <?php endif;?></details>
<details><summary class="fw-semibold small mt-2" style="cursor:pointer">📢 系统公告</summary>
    <?php $noticeLines=array_filter(explode("\n",$sysNotice?:'')); if(!empty($noticeLines)):?>
    <table class="table table-sm small mb-1"><tbody>
    <?php foreach($noticeLines as $n):?><tr><td><?=htmlspecialchars($n)?></td></tr><?php endforeach;?>
    </tbody></table>
    <?php else:?><div class="text-muted small mb-1">暂无公告</div><?php endif;?>
    <div class="input-group input-group-sm"><input id="noticeTxt" class="form-control" placeholder="输入公告内容"><button class="btn btn-primary" onclick="addNotice()">新增</button></div>
    <?php if(!empty($noticeLines)):?><button class="btn btn-sm btn-outline-danger mt-1" onclick="if(confirm('清空所有公告?')){clearNotices()}">清空公告</button><?php endif;?>
    <script>
    function addNotice(){var t=document.getElementById('noticeTxt').value.trim();if(!t)return;var f=new FormData();f.append('action','add_notice');f.append('notice',t);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok)location.reload();else alert(d.error);});}
    function clearNotices(){var f=new FormData();f.append('action','clear_notices');fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok)location.reload();else alert(d.error);});}
    </script></details>
<details><summary class="fw-semibold small mt-2" style="cursor:pointer">👥 用户管理 (<?=count($adminUsers)?>人)</summary>
    <?php if(empty($adminUsers)):?><div class="text-muted small">暂无用户</div><?php else:?>
    <table class="table table-sm"><thead><tr><th>用户</th><th>余额</th><th>借贷</th><th>破产次数</th><th>操作</th></tr></thead><tbody>
    <?php foreach($adminUsers as $au):?>
    <tr><td><?=htmlspecialchars($au['nickname']?:$au['username']?:'UID'.$au['user_id'])?></td><td><?=number_format($au['balance'],2)?></td><td><?=number_format($au['loan_amount'],2)?></td><td><?=$au['bankruptcy_count']?></td>
        <td><button class="btn btn-sm btn-outline-success py-0" style="font-size:0.6rem" onclick="adminReward(<?=$au['user_id']?>)">奖励</button> <button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.6rem" onclick="adminPenalize(<?=$au['user_id']?>)">扣减</button></td></tr>
    <?php endforeach;?></tbody></table><?php endif;?></details>
</div>
<?php endif; ?>

</div>

<div class="modal fade" id="newsModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">发布新闻</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" style="max-height:70vh;overflow-y:auto">
    <label class="form-label small mb-1">搜索目标股票</label>
    <input type="text" id="nsSearch" class="form-control form-control-sm mb-2" placeholder="搜索名称或代码..." oninput="filterNsStocks()">
    <div id="nsStockList" class="mb-2" style="max-height:200px;overflow-y:auto;border:1px solid rgba(0,0,0,0.08);border-radius:6px;padding:4px">
        <?php foreach($adminStocks as $s):if(!$s['is_active'])continue;?>
        <label class="d-flex align-items-center gap-2 py-1 px-2 ns-item" style="font-size:0.82rem;cursor:pointer" data-filter="<?=htmlspecialchars($s['name'].' '.$s['symbol'])?>"><input type="checkbox" class="ns-cb" value="<?=$s['id']?>"> <?=htmlspecialchars($s['name'])?> (<?=$s['symbol']?>)</label>
        <?php endforeach;?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" onclick="toggleAllNs()">全选/取消</button>
    <label class="form-label small mb-1">新闻标题 <span class="text-danger">*</span></label>
    <input id="nsTitle" class="form-control form-control-sm mb-2" placeholder="如：龙腾地产发布重大利好消息">
    <label class="form-label small mb-1">影响类型</label>
    <select id="nsEff" class="form-select form-select-sm mb-2"><option value="positive">📈 利好（推动股价上涨）</option><option value="negative">📉 利空（推动股价下跌）</option></select>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label small mb-1">影响强度 (1-10)</label><input id="nsStr" type="number" class="form-control form-control-sm" value="5" min="1" max="10"></div>
        <div class="col-6"><label class="form-label small mb-1">有效期 (小时)</label><input id="nsHrs" type="number" class="form-control form-control-sm" value="4" min="1"></div>
    </div>
    <div class="mb-2"><label class="form-label small mb-1">定时发布（留空=立即生效）</label><input id="nsSched" type="datetime-local" class="form-control form-control-sm"></div>
    <button class="btn btn-sm btn-warning w-100" onclick="pubNews()">批量发布新闻</button>
</div></div></div></div>

<!-- 股票编辑弹窗 -->
<div class="modal fade" id="stockEditModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title">编辑股票</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" id="seId">
    <div class="row g-2">
        <div class="col-6"><label class="form-label small mb-1">股票名称</label><input id="seName" class="form-control form-control-sm"></div>
        <div class="col-3"><label class="form-label small mb-1">代码</label><input id="seSymbol" class="form-control form-control-sm"></div>
        <div class="col-3"><label class="form-label small mb-1">行业</label><input id="seSector" class="form-control form-control-sm"></div>
        <div class="col-3"><label class="form-label small mb-1">上市日期</label><input id="seDate" type="date" class="form-control form-control-sm"></div>
        <div class="col-3"><label class="form-label small mb-1">发行价</label><input id="seIpo" type="number" step="0.01" class="form-control form-control-sm"></div>
        <div class="col-3"><label class="form-label small mb-1">基准价</label><input id="seBase" type="number" step="0.01" class="form-control form-control-sm"></div>
        <div class="col-3"><label class="form-label small mb-1">当前价</label><input id="sePrice" type="number" step="0.01" class="form-control form-control-sm"></div>
        <div class="col-3"><label class="form-label small mb-1">总股本(股)</label><input id="seShares" type="number" class="form-control form-control-sm" placeholder="如 10000000000"></div>
        <div class="col-3"><label class="form-label small mb-1">限购(股)</label><input id="seLimit" type="number" class="form-control form-control-sm" placeholder="如 100000"></div>
        <div class="col-12"><label class="form-label small mb-1">公司简介</label><textarea id="seDesc" class="form-control form-control-sm" rows="3" placeholder="公司业务介绍..."></textarea></div>
    </div>
</div>
<div class="modal-footer py-2"><button class="btn btn-sm btn-primary" onclick="doSaveStock()">保存</button></div>
</div></div></div>
</div>

<script>
var curSid=0,curPrice=0,chart=null,candle=null,lcoinBalance=<?=(float)(\App\Model\LCoin::getBalance((int)$_SESSION['user_id']??0))?>,exRate=<?=(int)($system['stock_exchange_rate']??10000)?>,redeemFee=<?=(float)($system['stock_redeem_fee']??0.05)?>,balance=<?=(float)$acc['balance']?>,kRange=30,fullScreen=false,posData=<?=json_encode($positions,JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
function sp(n,btn){document.querySelectorAll('.ent-panel').forEach(function(p){p.classList.remove('active')});document.getElementById('pn-'+n).classList.add('active');document.querySelectorAll('.ent-tab').forEach(function(b){b.classList.remove('active')});if(btn)btn.classList.add('active');}
document.addEventListener('DOMContentLoaded',function(){updateTimeMarker();renderNewsPage();});
function sel(id,name,sym,price){curSid=id;curPrice=parseFloat(price);document.getElementById('detail').classList.add('show');document.getElementById('detailEmpty').style.display='none';document.getElementById('dn').textContent=name;document.getElementById('ds').textContent=sym.toUpperCase();document.querySelectorAll('.ent-stock-item').forEach(function(e){e.classList.remove('sel')});var si=document.querySelector('.ent-stock-item[data-sid="'+id+'"]');if(si)si.classList.add('sel');
fetch('/public/index.php?route=entertainment-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=quote&stock_id='+id}).then(function(r){return r.json()}).then(function(d){if(!d.ok)return;var s=d.stock;curPrice=parseFloat(s.current_price);if(!s.is_active){document.getElementById('delistWarn').style.display='block';}else{document.getElementById('delistWarn').style.display='none';}var pct=s.base_price>0?((curPrice-s.base_price)/s.base_price*100).toFixed(2):0,c=pct>=0?'ent-up':'ent-down';document.getElementById('dp').className='ep '+c;document.getElementById('dp').textContent=curPrice.toFixed(2);document.getElementById('dc').className='ec '+c;document.getElementById('dc').textContent=(pct>=0?'+':'')+pct+'%';
var g='';['今开:'+curPrice.toFixed(2),'昨收:'+(curPrice*0.99).toFixed(2),'最高:'+(curPrice*1.02).toFixed(2),'最低:'+(curPrice*0.98).toFixed(2),'成交量:'+(Math.floor(Math.random()*50+10)+'万手'),'成交额:'+(Math.floor(Math.random()*10+1)+'亿'),'总市值:'+(Math.floor(curPrice*10000)+'亿'),'流通市值:'+(Math.floor(curPrice*5000)+'亿'),'换手率:'+(Math.random()*5).toFixed(2)+'%','市盈率:'+(curPrice/(parseFloat(s.base_price)*0.1)).toFixed(1),'市净率:'+(curPrice/parseFloat(s.base_price)*0.7).toFixed(2),'52周高:'+(curPrice*1.28).toFixed(2),'52周低:'+(curPrice*0.72).toFixed(2)].forEach(function(v){g+='<div class="ent-info-cell"><div class="lb">'+v.split(':')[0]+'</div><div class="vl">'+v.split(':')[1]+'</div></div>';});
if(s.description||s.listed_date||s.ipo_price){g+='<div class="mt-2 p-2 rounded" style="background:rgba(0,0,0,0.02);font-size:0.8rem;line-height:1.6">';if(s.description)g+=s.description;g+='</div>';}document.getElementById('infoGrid').innerHTML=g;});ldK('1m',document.getElementById('range-1m'),null);loadNews(id);showPos(id);}
function showPos(id){var pos=posData.find(function(p){return parseInt(p.stock_id)===id}),pi=document.getElementById('posInfo');if(pos){var pf=(curPrice-parseFloat(pos.avg_cost))*parseInt(pos.quantity),c3=pf>=0?'ent-up':'ent-down';pi.style.display='';pi.style.background=c3==='ent-up'?'rgba(239,68,68,0.05)':'rgba(34,197,94,0.05)';pi.innerHTML='<div class="d-flex justify-content-between align-items-center"><span>📊 持仓：<strong>'+pos.quantity+'股</strong> 成本 '+parseFloat(pos.avg_cost).toFixed(2)+'</span><span class="'+c3+' fw-bold">'+(pf>=0?'+':'')+pf.toFixed(2)+' ('+(curPrice?((curPrice-parseFloat(pos.avg_cost))/parseFloat(pos.avg_cost)*100).toFixed(2):'0')+'%)</span></div>';}else pi.style.display='none';}
function ldK(scale,btn,days){if(btn){document.querySelectorAll('.ent-range-btn').forEach(function(b){b.classList.remove('active')});btn.classList.add('active');}fetch('/public/index.php?route=entertainment-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=kline&stock_id='+curSid+'&scale='+scale}).then(function(r){return r.json()}).then(function(d){if(!d.ok||!d.data)return;drawChart(d.data,scale);});}
function drawChart(data,scale){var el=document.getElementById('chart');el.innerHTML='';var dark=document.body.classList.contains('theme-dark'),h=fullScreen?window.innerHeight-60:280;var opt={width:el.clientWidth,height:h,layout:{background:{type:'solid',color:dark?'#1a1a2e':'#ffffff'},textColor:dark?'#8899aa':'#333'},grid:{vertLines:{color:dark?'rgba(255,255,255,0.04)':'rgba(0,0,0,0.06)'},horzLines:{color:dark?'rgba(255,255,255,0.04)':'rgba(0,0,0,0.06)'}},timeScale:{timeVisible:true,secondsVisible:false}};
if(scale==='1m'){opt.localization={timeFormatter:function(ts){var d=new Date(ts*1000);return('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);}};}
chart=LightweightCharts.createChart(el,opt);
if(scale==='1m'){var ld=data.map(function(d){return{time:d.time,value:d.close};});var ls=chart.addLineSeries({color:'#667eea',lineWidth:2});ls.setData(ld);}else{candle=chart.addCandlestickSeries({upColor:'#ef4444',downColor:'#22c55e',borderUpColor:'#ef4444',borderDownColor:'#22c55e',wickUpColor:'#ef4444',wickDownColor:'#22c55e'});candle.setData(data);}
chart.timeScale().fitContent();}
function tgFull(){fullScreen=!fullScreen;var el=document.getElementById('chart'),btn=document.querySelector('.ent-chart-zoom');el.classList.toggle('full',fullScreen);btn.textContent=fullScreen?'✕ 缩小':'⛶ 全屏';setTimeout(function(){if(chart){el.style.width=fullScreen?'':'';chart.resize(el.clientWidth,fullScreen?window.innerHeight-60:280);chart.timeScale().fitContent();}},150);}
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&fullScreen)tgFull();});
function loadNews(id){
    var newsEl=document.getElementById('stockNews'),html='',now=<?=time()?>;
    <?php foreach($allNews as $n):?>
    if(parseInt('<?=$n['stock_id']?>')===id){var type='<?=$n['effect']?>',cls=type==='positive'?'good':'bad';
        var ts=<?=strtotime($n['created_at'])?>,diff=now-ts,timeStr;
        if(diff<60)timeStr='刚刚';else if(diff<3600)timeStr=Math.floor(diff/60)+'分钟前';else if(diff<86400)timeStr=Math.floor(diff/3600)+'小时前';else timeStr=Math.floor(diff/86400)+'天前';
        html+='<div class="ent-news-bar '+cls+'"><span>'+(type==='positive'?'📈':'📉')+'</span><span><?=htmlspecialchars(addslashes($n['title']))?></span><span class="text-muted small">强度:<?=$n['strength']?> | '+timeStr+'</span></div>';}
    <?php endforeach;?>
    html=html||'<div class="text-muted small text-center py-2">暂无新闻</div>';
    newsEl.innerHTML=html;
}
function setQty(frac,type,usePos){var qty=100;if(usePos&&curSid){var pos=posData.find(function(p){return parseInt(p.stock_id)===curSid});if(pos&&type==='sell')qty=Math.floor((parseInt(pos.quantity)||0)*frac/100)*100;else if(type==='buy')qty=Math.floor(balance/(curPrice*100)*frac/100)*100;}document.getElementById('tq').value=Math.max(100,qty||100);}
function doTrade(type){var p=parseFloat(document.getElementById('tp').value)||curPrice,q=parseInt(document.getElementById('tq').value);if(!curSid||!q){alert('请完整填写');return;}if(q%100!==0){alert('数量须为100的整数倍');return;}var f=new FormData();f.append('action','place_order');f.append('stock_id',curSid);f.append('type',type);f.append('price',p);f.append('quantity',q);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok&&d.done)location.reload();});}
function cancelOrder(id){if(!confirm('撤单？'))return;var f=new FormData();f.append('action','cancel_order');f.append('order_id',id);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function doOrderSafe(type){var p=parseFloat(document.getElementById('tp').value)||curPrice,q=parseInt(document.getElementById('tq').value);if(!curSid||!q){alert('请完整填写');return;}if(q%100!==0){alert('数量须为100的整数倍');return;}var f=new FormData();f.append('action','place_order');f.append('stock_id',curSid);f.append('type',type);f.append('price',p);f.append('quantity',q);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok&&d.done)location.reload();});}
function quickTrade(sid,type,price,holdQty,frac){
    var qty=Math.floor((parseInt(holdQty)||0)*frac/100)*100;
    if(type==='buy'){var maxQty=Math.floor(balance/(parseFloat(price)*100)*frac/100)*100;qty=Math.max(100,maxQty);}
    if(!qty||qty<100){alert('可交易数量不足');return;}
    curSid=sid;var f=new FormData();f.append('action','place_order');f.append('stock_id',sid);f.append('type',type);f.append('price',price);f.append('quantity',qty);
    fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok){if(d.done)location.reload();else{closeAllMenus();sp('ord',document.querySelectorAll('.ent-tab')[2]);}}});
    closeAllMenus();
}
var activeMenu=null;
function togglePosMenu(evt,menuId){evt.stopPropagation();var m=document.getElementById(menuId);if(activeMenu&&activeMenu!==m)activeMenu.style.display='none';m.style.display=m.style.display==='flex'?'none':'flex';m.style.left=evt.target.getBoundingClientRect().left+'px';m.style.top=(evt.target.getBoundingClientRect().bottom+2)+'px';activeMenu=m;}
function closeAllMenus(){if(activeMenu){activeMenu.style.display='none';activeMenu=null;}}
document.addEventListener('click',closeAllMenus);
function doOrder(type){var q=parseInt(document.getElementById('tq').value);if(!curSid||!q){alert('请完整填写');return;}if(q%100!==0){alert('数量须为100的整数倍');return;}var f=new FormData();f.append('action','trade');f.append('stock_id',curSid);f.append('type',type);f.append('quantity',q);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function doLoan(){var a=parseFloat(document.getElementById('loanAmt').value),m=document.getElementById('loanMethod').value;if(!a||a<=0){alert('请输入有效金额');return;}var f=new FormData();f.append('action','loan');f.append('amount',a);f.append('method',m);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function repayLoan(id){if(!confirm('确定还款？将从余额中扣款'))return;var f=new FormData();f.append('action','repay_loan');f.append('loan_id',id);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function goBankrupt(){if(!confirm('确定破产清算？所有借贷标记逾期，账户重置为100万股币'))return;var f=new FormData();f.append('action','bankrupt');fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function addStock(){document.getElementById('seId').value='';document.getElementById('seName').value='';document.getElementById('seSymbol').value='';document.getElementById('seSector').value='';document.getElementById('seDate').value='';document.getElementById('seIpo').value='';document.getElementById('seBase').value='';document.getElementById('sePrice').value='';document.getElementById('seDesc').value='';document.getElementById('seShares').value='1000000000';document.getElementById('seLimit').value='100000';new bootstrap.Modal(document.getElementById('stockEditModal')).show();}
function editStock(s){
    document.getElementById('seId').value=s.id||'';
    document.getElementById('seName').value=s.name||'';document.getElementById('seSymbol').value=s.symbol||'';
    document.getElementById('seSector').value=s.sector||'';document.getElementById('seDate').value=s.listed_date||'';
    document.getElementById('seIpo').value=s.ipo_price||'';document.getElementById('seBase').value=s.base_price||'';
    document.getElementById('sePrice').value=s.current_price||'';document.getElementById('seDesc').value=s.description||'';
    document.getElementById('seShares').value=s.total_shares||'1000000000';document.getElementById('seLimit').value=s.limit_per_user||'100000';
    new bootstrap.Modal(document.getElementById('stockEditModal')).show();
}
function doSaveStock(){
    var id=document.getElementById('seId').value,act=id?'update_stock':'create_stock';
    var f=new FormData();f.append('action',act);if(id)f.append('id',id);
    f.append('name',document.getElementById('seName').value);f.append('symbol',document.getElementById('seSymbol').value);
    f.append('sector',document.getElementById('seSector').value);f.append('listed_date',document.getElementById('seDate').value);
    f.append('ipo_price',document.getElementById('seIpo').value);f.append('base_price',document.getElementById('seBase').value);
    f.append('current_price',document.getElementById('sePrice').value);f.append('description',document.getElementById('seDesc').value);
    f.append('total_shares',document.getElementById('seShares').value);f.append('limit_per_user',document.getElementById('seLimit').value);
    f.append('is_active','1');
    fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){
        alert(d.message||d.error);if(d.ok)location.reload();
    });
}
function openNewsModal(){document.getElementById('nsTitle').value='';document.getElementById('nsSearch').value='';document.getElementById('nsSched').value='';new bootstrap.Modal(document.getElementById('newsModal')).show();setTimeout(filterNsStocks,300);}
function filterStocks(){var kw=(document.getElementById('stockSearch').value||'').toLowerCase();document.querySelectorAll('#stockList .ent-stock-item').forEach(function(el){el.style.display=!kw||(el.textContent||'').toLowerCase().indexOf(kw)!==-1?'':'none';});}
function filterNsStocks(){
    var kw=(document.getElementById('nsSearch').value||'').toLowerCase().trim();
    document.querySelectorAll('#newsModal .ns-item').forEach(function(el){
        if(!kw){el.style.display='';return;}
        var f=(el.getAttribute('data-filter')||el.textContent||'').toLowerCase();
        el.style.display=f.indexOf(kw)!==-1?'':'none';
    });
}
function toggleAllNs(){var v=document.querySelectorAll('.ns-item:not([style*="none"]) .ns-cb');var cbs=document.querySelectorAll('.ns-cb');var all=Array.from(v).every(function(c){return c.checked});v.forEach(function(c){c.checked=!all;});}
function prevNewsPage(){if(curNewsPage>0){curNewsPage--;renderNewsPage();}}
function nextNewsPage(){if(curNewsPage<Math.ceil(newsTotal/10)-1){curNewsPage++;renderNewsPage();}}
var curNewsPage=0,newsTotal=<?=count($adminNews)?>;
function renderNewsPage(){document.querySelectorAll('.news-row').forEach(function(r){r.style.display=parseInt(r.getAttribute('data-news-page'))===curNewsPage?'':'none';});document.getElementById('newsPageInfo').textContent='第'+(curNewsPage+1)+'页 / 共'+Math.ceil(newsTotal/10)+'页';}
function pubNews(){
    var cbs=document.querySelectorAll('.ns-cb:checked'),title=document.getElementById('nsTitle').value.trim();
    if(!cbs.length||!title){alert('请选择股票并填写标题');return;}
    var eff=document.getElementById('nsEff').value,str=document.getElementById('nsStr').value,hrs=document.getElementById('nsHrs').value,sched=document.getElementById('nsSched').value;
    var done=0,total=cbs.length;
    cbs.forEach(function(cb){
        var f=new FormData();
        f.append('action','publish_news');f.append('stock_id',cb.value);f.append('title',title);f.append('effect',eff);f.append('strength',str);f.append('hours',hrs);if(sched)f.append('scheduled_at',sched+':00');
        fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){
            done++;if(done>=total){var m=document.getElementById('newsModal');if(m){m.classList.remove('show');m.style.display='none';document.body.classList.remove('modal-open');var bd=document.querySelector('.modal-backdrop');if(bd)bd.remove();}alert('已为'+total+'只股票发布新闻');sp('adm',document.querySelector('.ent-tab:nth-child(6)')||document.querySelector('.ent-tab:last-child'));}
        });
    });
}
function delNews(id){if(!confirm('删除？'))return;var f=new FormData();f.append('action','delete_news');f.append('id',id);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok)location.reload();});}
function saveNotice(){var f=new FormData();f.append('action','save_notice');f.append('notice',document.getElementById('noticeTxt').value);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);});}
function adminReward(uid){var amt=prompt('输入奖励虚拟资金金额：','10000');if(!amt||isNaN(amt)||parseFloat(amt)<=0)return;var f=new FormData();f.append('action','admin_fund');f.append('user_id',uid);f.append('amount',amt);f.append('type','reward');fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function adminPenalize(uid){var amt=prompt('输入扣减虚拟资金金额：','10000');if(!amt||isNaN(amt)||parseFloat(amt)<=0)return;var f=new FormData();f.append('action','admin_fund');f.append('user_id',uid);f.append('amount',amt);f.append('type','penalize');fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();});}
function isTradeTime(){var h=(new Date()).getHours(),m=(new Date()).getMinutes(),t=h*60+m;return t>=480;}
function updateTimeMarker(){var n=new Date(),h=n.getHours(),m=n.getMinutes(),t=h*60+m,left=0;if(t>=480)left=33.33+((t-480)/960)*66.67;else left=(t/480)*33.33;document.getElementById('timeMarker').style.left=Math.min(98,left)+'%';document.getElementById('tradeStatus').textContent=isTradeTime()?'🟢交易中':'🔴休市';}
setInterval(function(){updateTimeMarker();if(!isTradeTime())return;fetch('/public/index.php?route=entertainment-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=refresh'}).then(function(r){return r.json()}).then(function(d){if(!d.ok||!d.stocks)return;d.stocks.forEach(function(s){var pr=document.getElementById('pr-'+s.id),pc=document.getElementById('pc-'+s.id);if(!pr)return;var pct=s.base_price>0?((s.current_price-s.base_price)/s.base_price*100).toFixed(2):0,c=pct>=0?'ent-up':'ent-down';pr.className='fw-bold '+c;pr.textContent=parseFloat(s.current_price).toFixed(2);if(pc){pc.className='small '+c;pc.textContent=(pct>=0?'+':'')+pct+'%';}if(parseInt(s.id)===curSid){curPrice=parseFloat(s.current_price);var dp=document.getElementById('dp');if(dp){dp.className='ep '+c;dp.textContent=curPrice.toFixed(2);}}});updatePositions(d.stocks);}).catch(function(){});},10000);
var refreshCountdown=10;setInterval(function(){refreshCountdown--;if(refreshCountdown<=0)refreshCountdown=10;var el=document.getElementById('refreshCd');if(el)el.textContent='刷新 '+refreshCountdown+'s';},1000);
function updatePositions(stocks){var totMV=0;document.querySelectorAll('.pos-price').forEach(function(el){var id=el.getAttribute('data-sid'),st=stocks.find(function(s){return parseInt(s.id)===parseInt(id)});if(!st)return;var newP=parseFloat(st.current_price),cost=parseFloat(el.getAttribute('data-cost')),tr=el.closest('tr'),qtyEl=tr?tr.querySelector('td:nth-child(2)'):null;if(!qtyEl)return;var qtyText=qtyEl.textContent.match(/(\d+)股/);if(!qtyText)return;var qty=parseInt(qtyText[1]),pf=(newP-cost)*qty,pp=cost>0?((newP-cost)/cost*100).toFixed(2):0,c=pf>=0?'ent-up':'ent-down';el.className='text-end fw-bold '+c;el.textContent=newP.toFixed(2);var mvEl=el.parentElement.querySelector('.pos-mv');if(mvEl){var mv=newP*qty;mvEl.textContent=mv.toFixed(2);totMV+=mv;}var plEl=el.parentElement.querySelector('.pos-pl');if(plEl){plEl.className='text-end small fw-bold '+c;plEl.textContent=(pf>=0?'+':'')+pf.toFixed(2);}var ppEl=el.parentElement.querySelector('.pos-pp');if(ppEl){ppEl.className='text-end small '+c;ppEl.textContent=(pp>=0?'+':'')+pp+'%';}});updateAccountSummary(totMV);}
function updateAccountSummary(totMV){var el=document.getElementById('sumMV');if(el)el.textContent=totMV.toFixed(2);var b=balance;if(el=document.getElementById('sumBalance'))el.textContent=b.toFixed(2);if(el=document.getElementById('sumAssets'))el.textContent=(b+totMV).toFixed(2);}

function exchangeStock(){var m=new bootstrap.Modal(document.getElementById('exModal'));document.getElementById('exLcoin').value='';document.getElementById('exEstimate').textContent='0';document.getElementById('exBalance').textContent=lcoinBalance.toFixed(2);document.getElementById('exRate').textContent=exRate;m.show();}
function doExchange(){var a=parseFloat(document.getElementById('exLcoin').value);if(!a||a<=0||a>lcoinBalance){alert('请输入有效的L币数量');return;}var f=new FormData();f.append('action','exchange_stock');f.append('lcoin_amount',a);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok){alert('兑换成功！\n'+a+' L币 \u2192 '+d.funds+' 虚拟资金');location.reload();}else alert(d.error||'兑换失败');});}
function redeemStock(){var m=new bootstrap.Modal(document.getElementById('redModal'));document.getElementById('redFunds').value='';document.getElementById('redFee').textContent='0';document.getElementById('redLcoin').textContent='0';document.getElementById('redBalance').textContent=balance.toFixed(2);document.getElementById('redFeeRate').textContent=(redeemFee*100).toFixed(0);m.show();}
function doRedeem(){var a=parseFloat(document.getElementById('redFunds').value);if(!a||a<=0||a>balance){alert('金额无效或超过余额');return;}var f=new FormData();f.append('action','redeem_stock');f.append('fund_amount',a);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok){alert('赎回成功！\n'+(d.funds-d.fee)+' 虚拟资金 \u2192 '+d.lcoin+' L币\n\u624b\u7eed\u8d39\uff1a'+d.fee);location.reload();}else alert(d.error||'赎回失败');});}
</script>

<div class="modal fade" id="exModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-header py-2 px-3"><h6 class="modal-title">L币兑换虚拟资金</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="small mb-2">当前 L币余额：<strong id="exBalance">0</strong></div><div class="small mb-2">汇率：1 L币 = <strong id="exRate">0</strong> 虚拟资金</div><div class="mb-2"><label class="form-label small">兑换 L币 数量（建议保留至少 1 L币用于AI服务）</label><input type="number" id="exLcoin" class="form-control form-control-sm" min="0.01" step="0.01" placeholder="输入L币数量" oninput="var v=parseFloat(this.value)||0;document.getElementById('exEstimate').textContent=(v*exRate).toFixed(0);"></div><div class="small">预计获得：<strong id="exEstimate">0</strong> 虚拟资金</div></div><div class="modal-footer py-2 px-3"><button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button><button class="btn btn-sm btn-warning" onclick="doExchange()">确认兑换</button></div></div></div></div>

<div class="modal fade" id="redModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-header py-2 px-3"><h6 class="modal-title">虚拟资金赎回为L币</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="small mb-2">可用虚拟资金：<strong id="redBalance">0</strong></div><div class="small mb-2">手续费率：<strong id="redFeeRate">5</strong>%</div><div class="mb-2"><label class="form-label small">赎回虚拟资金数量</label><input type="number" id="redFunds" class="form-control form-control-sm" min="1" step="1" placeholder="输入资金数量" oninput="var v=parseFloat(this.value)||0;var fee=v*redeemFee;var net=v-fee;document.getElementById('redFee').textContent=fee.toFixed(2);document.getElementById('redLcoin').textContent=(v>0?(net/exRate).toFixed(2):'0');"></div><div class="small">手续费：<strong id="redFee">0</strong> | 预计获得：<strong id="redLcoin">0</strong> L币</div></div><div class="modal-footer py-2 px-3"><button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button><button class="btn btn-sm btn-info" onclick="doRedeem()">确认赎回</button></div></div></div></div>
