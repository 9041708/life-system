// 模拟炒股系统前端 JS（外部文件，不受 PHP/OPcache 影响）
var curSid=0,curPrice=0,chart=null,candle=null,lineSeries=null,lcoinBalance=0,
    exRate=10000,redeemFee=0.05,balance=0,kRange=30,fullScreen=false,curScale='1m',posData=[];
var isAdmin=false;

function initStock(cfg) {
    exRate=cfg.exRate||10000;
    redeemFee=cfg.redeemFee||0.05;
    balance=parseFloat(cfg.balance)||0;
    lcoinBalance=parseFloat(cfg.lcoinBalance)||0;
    kRange=cfg.kRange||30;
    isAdmin=!!cfg.isAdmin;
    if(cfg.posData) posData=cfg.posData;
}

function sp(n,btn){
    document.querySelectorAll('.ent-panel').forEach(function(p){p.classList.remove('active')});
    document.getElementById('pn-'+n).classList.add('active');
    document.querySelectorAll('.ent-tab').forEach(function(b){b.classList.remove('active')});
    if(btn)btn.classList.add('active');
}

function sel(sid, name, price){
    if(!sid)return;
    curSid=sid;curPrice=parseFloat(price);
    document.getElementById('curStockName').textContent=name;
    document.getElementById('curStockPrice').textContent=parseFloat(price).toFixed(2);
    document.getElementById('curStockPrice').className='ep '+(parseFloat(price)>=parseFloat(document.querySelector('[data-base="'+sid+'"]')?document.querySelector('[data-base="'+sid+'"]').getAttribute('data-base'):price)?'ent-up':'ent-down');
    var dp=document.getElementById('dp');
    if(dp){dp.className='ep';dp.textContent=curPrice.toFixed(2);}
    showPos(sid);
    drawChart();
    // 切换到行情面板
    sp(2);
}

function showPos(sid){
    var found=false;
    document.querySelectorAll('#posTable tbody tr').forEach(function(tr){
        var sidCell=tr.querySelector('[data-sid]');
        if(!sidCell)return;
        var tsid=parseInt(sidCell.getAttribute('data-sid'));
        if(tsid===sid){
            tr.style.background='rgba(255,193,7,0.15)';
            found=true;
        }else{
            tr.style.background='';
        }
    });
    return found;
}

function drawChart(type){
    if(!type)type=curScale;
    var container=document.getElementById('chartContainer');
    if(!container)return;
    var stockData=document.querySelector('[data-stock-id="'+curSid+'"]');
    var basePrice=stockData?parseFloat(stockData.getAttribute('data-base')||stockData.getAttribute('data-price')):curPrice;
    if(!chart){
        container.innerHTML='';
        chart=LightweightCharts.createChart(container,{width:container.clientWidth,height:350,layout:{backgroundColor:'transparent',textColor:'#aaa'},grid:{vertLines:{color:'rgba(255,255,255,0.05)'},horzLines:{color:'rgba(255,255,255,0.05)'}},crosshair:{mode:LightweightCharts.CrosshairMode.Normal},rightPriceScale:{borderColor:'rgba(255,255,255,0.1)'},timeScale:{borderColor:'rgba(255,255,255,0.1)',timeVisible:true}};
        lineSeries=chart.addLineSeries({color:'#ffc107',lineWidth:2,priceLineColor:'#ffc107',lastValueVisible:true,priceLineWidth:1,priceLineStyle:LightweightCharts.PriceLineStyle.Dashed,axisRightColor:'#ffc107',axisLabelColor:'#aaa'});
        chart.addEventListener('resize',function(){chart.applyOptions({width:container.clientWidth})});
    }
    if(type==='1m'){
        var pts=[];
        var now=Math.floor(Date.now()/1000);
        for(var i=30;i>=0;i--){var t=now-i*60;pts.push({time:t,value:basePrice+(Math.random()-0.5)*basePrice*0.01});}
        if(lineSeries){chart.removeSeries(lineSeries);lineSeries=null;}
        lineSeries=chart.addLineSeries({color:'#ffc107',lineWidth:2,priceLineColor:'#ffc107',lastValueVisible:true,priceLineWidth:1,priceLineStyle:LightweightCharts.PriceLineStyle.Dashed,axisRightColor:'#ffc107',axisLabelColor:'#aaa'});
        lineSeries.setData(pts);
    } else if(type==='5m'){
        var pts=[];
        var now=Math.floor(Date.now()/300);
        for(var i=48;i>=0;i--){var t=now-i*300;pts.push({time:t,value:basePrice+(Math.random()-0.5)*basePrice*0.02});}
        if(lineSeries){chart.removeSeries(lineSeries);lineSeries=null;}
        lineSeries=chart.addLineSeries({color:'#0dcaf0',lineWidth:2,priceLineColor:'#0dcaf0'});
        lineSeries.setData(pts);
    } else if(type==='1d'){
        var now=Math.floor(Date.now()/86400);
        var pts=[];
        for(var i=29;i>=0;i--){var t=now-i*86400;pts.push({time:t,value:basePrice+(Math.random()-0.5)*basePrice*0.04});}
        if(lineSeries){chart.removeSeries(lineSeries);lineSeries=null;}
        lineSeries=chart.addLineSeries({color:'#198754',lineWidth:2,priceLineColor:'#198754'});
        lineSeries.setData(pts);
    }
    chart.timeScale().fitContent();
}

function switchScale(s){
    curScale=s;
    document.querySelectorAll('.scale-btn').forEach(function(b){b.classList.remove('active')});
    var btn=document.querySelector('[data-scale="'+s+'"]');
    if(btn)btn.classList.add('active');
    drawChart();
}

function updatePositions(stocks){
    document.querySelectorAll('#posTable tbody tr[data-qty]').forEach(function(tr){
        var id=tr.querySelector('.pos-price');
        if(!id)return;
        var sid=id.getAttribute('data-sid'),st=stocks.find(function(s){return parseInt(s.id)===parseInt(sid)});
        if(!st)return;
        var newP=parseFloat(st.current_price),cost=parseFloat(id.getAttribute('data-cost')),qty=parseInt(tr.getAttribute('data-qty')),pf=(newP-cost)*qty,pp=cost>0?((newP-cost)/cost*100).toFixed(2):0,c=pf>=0?'ent-up':'ent-down';
        id.className='text-end fw-bold '+c;
        id.textContent=newP.toFixed(2);
        var mvEl=tr.querySelector('.pos-mv');
        if(mvEl)mvEl.textContent=(newP*qty).toFixed(2);
        var plEl=tr.querySelector('.pos-pl');
        if(plEl){plEl.className='text-end small fw-bold '+c;plEl.textContent=(pf>=0?'+':'')+pf.toFixed(2);}
        var ppEl=tr.querySelector('.pos-pp');
        if(ppEl){ppEl.className='text-end small '+c;ppEl.textContent=(pp>=0?'+':'')+pp+'%';}
    });
}

function isTradeTime(){
    var h=(new Date()).getHours(),m=(new Date()).getMinutes(),t=h*60+m;
    return t>=480;
}

function updateTimeMarker(){
    var n=new Date(),h=n.getHours(),m=n.getMinutes(),t=h*60+m,left=0;
    if(t>=480)left=33.33+((t-480)/960)*66.67;
    else left=(t/480)*33.33;
    var marker=document.getElementById('timeMarker');
    if(marker)marker.style.left=Math.min(98,left)+'%';
    var status=document.getElementById('tradeStatus');
    if(status)status.textContent=isTradeTime()?'\ud83d\udfe2交易中':'\ud83d\udd34\u4f11\u5e02';
}

document.addEventListener('DOMContentLoaded',function(){
    updateTimeMarker();
    renderNewsPage();
    // 行情刷新
    setInterval(function(){
        updateTimeMarker();
        if(!isTradeTime())return;
        fetch('/public/index.php?route=entertainment-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=refresh'})
        .then(function(r){return r.json()})
        .then(function(d){
            if(!d.ok||!d.stocks)return;
            var nowTs=Math.floor(Date.now()/1000);
            d.stocks.forEach(function(s){
                var pr=document.getElementById('pr-'+s.id),pc=document.getElementById('pc-'+s.id);
                if(!pr)return;
                var pct=s.base_price>0?((s.current_price-s.base_price)/s.base_price*100).toFixed(2):0,c=pct>=0?'ent-up':'ent-down';
                pr.className='fw-bold '+c;
                pr.textContent=parseFloat(s.current_price).toFixed(2);
                if(pc){pc.className='small '+c;pc.textContent=(pct>=0?'+':'')+pct+'%';}
                if(parseInt(s.id)===curSid){
                    curPrice=parseFloat(s.current_price);
                    var dp=document.getElementById('dp');
                    if(dp){dp.className='ep '+c;dp.textContent=curPrice.toFixed(2);}
                    if(lineSeries&&curScale==='1m'){lineSeries.update({time:nowTs,value:curPrice});}
                }
            });
            updatePositions(d.stocks);
        }).catch(function(){});
    },10000);
});

// 新闻页面
function renderNewsPage(){var n=document.getElementById('newsPage');if(!n)return;var stocks=window.allStocks||[];var html='<div class="mb-3"><button class="btn btn-sm btn-warning" onclick="showPubNewsModal()">\u2714\ufe0f \u53d1\u5e03\u65b0\u95ee</button></div>';if(stocks.length===0){n.innerHTML=html+'<div class="text-muted">\u6682\u65e0\u80a1\u7968\u6570\u636e</div>';return;}stocks.forEach(function(s){html+='<div class="news-item mb-2 p-2 rounded-3" style="background:rgba(255,255,255,0.04);border-left:3px solid #ffc107">';html+='<div class="d-flex justify-content-between"><strong>'+s.name+' ('+s.symbol+')</strong>';html+='<span class="small text-muted">'+s.sector+'</span></div>';html+='<div class="d-flex gap-3 mt-1 small">';html+='<span>\u73d0\u4ef7: <strong class="ent-up">'+parseFloat(s.base_price).toFixed(2)+'</strong></span>';html+='<span>\u73bb\u4e3a\u4ef7: <strong class="ent-up">'+parseFloat(s.current_price).toFixed(2)+'</strong></span>';var pct=s.base_price>0?((s.current_price-s.base_price)/s.base_price*100).toFixed(2):0;var cls=parseFloat(pct)>=0?'ent-up':'ent-down';html+='<span class="'+cls+'">\u6da8\u8dcc: '+(parseFloat(pct)>=0?'+':'')+pct+'%</span>';html+='</div></div>';});n.innerHTML=html;}
function showPubNewsModal(){var m=new bootstrap.Modal(document.getElementById('newsModal'));m.show();}
function filterNsStocks(){var v=document.getElementById('nsSearch').value.toLowerCase();document.querySelectorAll('.ns-item').forEach(function(el){el.style.display=el.getAttribute('data-filter').toLowerCase().includes(v)?'':'none';});}
function toggleAllNs(){var cbs=document.querySelectorAll('.ns-cb');var anyChecked=Array.from(cbs).some(function(c){return c.checked;});cbs.forEach(function(c){c.checked=!anyChecked;});}
function pubNews(){var t=document.getElementById('nsTitle').value.trim();if(!t){alert('\u8bf7\u8f93\u5165\u65b0\u95ee\u6807\u9898');return;}var eff=document.getElementById('nsEff').value;var str=parseInt(document.getElementById('nsStr').value)||5;var hrs=parseInt(document.getElementById('nsHrs').value)||4;var sched=document.getElementById('nsSched').value;var cbs=document.querySelectorAll('.ns-cb:checked');var sids=Array.from(cbs).map(function(c){return c.value;});if(sids.length===0){alert('\u8bf7\u9009\u62e9\u81f3\u5c11\u4e00\u53ea\u80a1\u7968');return;}var f=new FormData();f.append('action','pub_news');f.append('title',t);f.append('effect',eff);f.append('strength',str);f.append('expire_hours',hrs);f.append('scheduled_at',sched||'');f.append('stock_ids',sids.join(','));fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok){alert('\u53d1\u5e03\u6210\u529f\uff01'+sids.length+'\u53ea\u80a1\u7968');document.getElementById('newsModal').querySelector('.btn-close').click();renderNewsPage();}else alert(d.error||'\u53d1\u5e03\u5931\u8d25');}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});}

// 交易
function buyStock(sid,qty){
    var priceEl=document.getElementById('pr-'+sid);
    if(!priceEl){console.error('\u627e\u4e0d\u5230\u4ef7\u683c\u5143\u7d20');return;}
    var price=parseFloat(priceEl.textContent)||curPrice;
    if(!qty||qty<=0){alert('\u8bf7\u8f93\u5165\u6709\u6548\u6570\u91cf');return;}
    var total=price*qty;
    if(total>balance){alert('\u4f59\u989d\u4e0d\u8db3\uff01\u60a8\u7684\u53ef\u7528\u8d44\u91d1\uff1a'+balance.toFixed(2));return;}
    if(!confirm('\u786e\u8ba4\u4ea4\u6613\uff1a\u4ee5 '+price.toFixed(2)+' \u5143\u4e70\u5165 '+qty+' \u80a1 '+curStockName+'\uff0c\u603b\u989d '+total.toFixed(2)+' \u5143\uff08\u542b\u4ea4\u6613\u8d39'+(price*qty*0.001).toFixed(2)+'\u5143\uff09'))return;
    var f=new FormData();f.append('action','buy');f.append('stock_id',sid);f.append('quantity',qty);f.append('price',price);
    fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok){alert('\u4ea4\u6613\u6210\u529f\uff01'+qty+'\u80a1'+curStockName);location.reload();}else alert(d.error||'\u4ea4\u6613\u5931\u8d25');}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});
}
function sellStock(sid,qty){
    var priceEl=document.getElementById('pr-'+sid);
    if(!priceEl){console.error('\u627e\u4e0d\u5230\u4ef7\u683c\u5143\u7d20');return;}
    var price=parseFloat(priceEl.textContent)||curPrice;
    if(!qty||qty<=0){alert('\u8bf7\u8f93\u5165\u6709\u6548\u6570\u91cf');return;}
    var pos=posData.find(function(p){return parseInt(p.stock_id)===sid});
    if(!pos){alert('\u60a8\u6ca1\u6709\u6301\u6709\u8be5\u80a1\u7968');return;}
    if(qty>parseInt(pos.quantity)){alert('\u53ef\u51fa\u552e\u6570\u91cf\u4e0d\u8db3\uff01\u60a8\u6709'+pos.quantity+'\u80a1');return;}
    var total=price*qty;var fee=(price*qty*0.002).toFixed(2);var net=(total-fee).toFixed(2);
    if(!confirm('\u786e\u8ba4\u51fa\u552e\uff1a'+qty+'\u80a1 '+curStockName+'\u4ee5 '+price.toFixed(2)+'\u5143\uff0c\u989d\u5b9e\u5f97 '+net+' \u5143\uff08\u624b\u7eed\u8d39'+fee+'\u5143\uff09'))return;
    var f=new FormData();f.append('action','sell');f.append('stock_id',sid);f.append('quantity',qty);f.append('price',price);
    fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok){alert('\u51fa\u552e\u6210\u529f\uff01'+qty+'\u80a1'+curStockName+'\uff0c\u5f97 '+net+' \u5143');location.reload();}else alert(d.error||'\u51fa\u552e\u5931\u8d25');}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});
}

// 订单
function cancelOrder(id){if(!confirm('\u53d6\u6d88\u8ba2\u5355\uff1f'))return;var f=new FormData();f.append('action','cancel_order');f.append('order_id',id);fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok)location.reload();else alert(d.error||'\u53d6\u6d88\u5931\u8d25');}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});}
function showOrderDetail(id){var tr=document.getElementById('order-'+id);if(!tr)return;var cur=tr.style.background||'';tr.style.background='rgba(255,193,7,0.1)';setTimeout(function(){tr.style.background=cur;},500);}

// 兑换/赎回
function exchangeStock(){
    var m=new bootstrap.Modal(document.getElementById('exModal'));
    document.getElementById('exLcoin').value='';
    document.getElementById('exEstimate').textContent='0';
    document.getElementById('exBalance').textContent=lcoinBalance.toFixed(2);
    document.getElementById('exRate').textContent=exRate;
    m.show();
}
function doExchange(){
    var a=parseFloat(document.getElementById('exLcoin').value);
    if(!a||a<=0||a>lcoinBalance){alert('\u8bf7\u8f93\u5165\u6709\u6548\u7684L\u5e01\u6570\u91cf');return;}
    var f=new FormData();f.append('action','exchange_stock');f.append('lcoin_amount',a);
    fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok){alert('\u5151\u6362\u6210\u529f\uff01\n'+a+' L\u5e01 \u2192 '+d.funds+' \u865a\u62df\u8d44\u91d1');location.reload();}else alert(d.error||'\u5151\u6362\u5931\u8d25');}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});
}
document.addEventListener('DOMContentLoaded',function(){
    var exInput=document.getElementById('exLcoin');
    if(exInput) exInput.addEventListener('input',function(){var v=parseFloat(this.value)||0;document.getElementById('exEstimate').textContent=(v*exRate).toFixed(0);});
});
function redeemStock(){
    var m=new bootstrap.Modal(document.getElementById('redModal'));
    document.getElementById('redFunds').value='';
    document.getElementById('redFee').textContent='0';
    document.getElementById('redLcoin').textContent='0';
    document.getElementById('redBalance').textContent=balance.toFixed(2);
    document.getElementById('redFeeRate').textContent=(redeemFee*100).toFixed(0);
    m.show();
}
function doRedeem(){
    var a=parseFloat(document.getElementById('redFunds').value);
    if(!a||a<=0||a>balance){alert('\u91d1\u989d\u65e0\u6548\u6216\u8d85\u8fc7\u4f59\u989d');return;}
    var f=new FormData();f.append('action','redeem_stock');f.append('fund_amount',a);
    fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok){alert('\u8d4e\u56de\u6210\u529f\uff01\n'+(d.funds-d.fee)+' \u865a\u62df\u8d44\u91d1 \u2192 '+d.lcoin+' L\u5e01\n\u624b\u7eed\u8d39\uff1a'+d.fee);location.reload();}else alert(d.error||'\u8d4e\u56de\u5931\u8d25');}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});
}
document.addEventListener('DOMContentLoaded',function(){
    var redInput=document.getElementById('redFunds');
    if(redInput) redInput.addEventListener('input',function(){
        var v=parseFloat(this.value)||0;
        var fee=v*redeemFee;
        var net=v-fee;
        var feeEl=document.getElementById('redFee');
        var lcoinEl=document.getElementById('redLcoin');
        if(feeEl) feeEl.textContent=fee.toFixed(2);
        if(lcoinEl) lcoinEl.textContent=(v>0?(net/exRate).toFixed(2):'0');
    });
});

// 管理功能
function editStock(id){var s=window.allStocks.find(function(x){return parseInt(x.id)===id;});if(!s){alert('\u6570\u636e\u5f02\u5e38');return;}document.getElementById('seId').value=s.id;document.getElementById('seName').value=s.name||'';document.getElementById('seSymbol').value=s.symbol||'';document.getElementById('seSector').value=s.sector||'';document.getElementById('seDate').value=s.listed_date||'';document.getElementById('seIpo').value=s.ipo_price||'';document.getElementById('seBase').value=s.base_price||'';document.getElementById('sePrice').value=s.current_price||'';document.getElementById('seShares').value=s.total_shares||'';document.getElementById('seLimit').value=s.daily_limit||'';document.getElementById('seDesc').value=s.description||'';new bootstrap.Modal(document.getElementById('stockEditModal')).show();}
function doSaveStock(){var id=document.getElementById('seId').value;if(!id){alert('\u7f3a\u5c11\u80a1\u7968ID');return;}var f=new FormData();f.append('action','admin_save_stock');f.append('id',id);['Name','Symbol','Sector','Date','Ipo','Base','Price','Shares','Limit','Desc'].forEach(function(k){f.append(k.toLowerCase(),document.getElementById('se'+k).value);});fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){if(d.ok){alert('\u4fdd\u5b58\u6210\u529f');location.reload();}else alert(d.error||'\u4fdd\u5b58\u5931\u8d25');}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});}
function adminReward(uid){var sid=prompt('\u8f93\u5165\u80a1\u7968ID\uff1a'),qty=prompt('\u5956\u52b1\u80a1\u6570\uff1a');if(!sid||!qty)return;var f=new FormData();f.append('action','admin_reward');f.append('user_id',uid);f.append('stock_id',sid);f.append('quantity',qty);f.append('price','0');fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});}
function adminPenalize(uid){var sid=prompt('\u8f93\u5165\u80a1\u7968ID\uff1a'),qty=prompt('\u6263\u51cf\u80a1\u6570\uff1a');if(!sid||!qty)return;var f=new FormData();f.append('action','admin_reward');f.append('user_id',uid);f.append('stock_id',sid);f.append('quantity',-parseInt(qty));f.append('price','0');fetch('/public/index.php?route=entertainment-api',{method:'POST',body:f}).then(function(r){return r.json()}).then(function(d){alert(d.message||d.error);if(d.ok)location.reload();}).catch(function(){alert('\u7f51\u7edc\u9519\u8bef');});}
