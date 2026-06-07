<style>
.td-mod { margin-bottom: 1.5rem; border: 1px solid var(--bs-border-color); border-radius: 14px; overflow: hidden; background: var(--bs-body-bg); }
.td-mod-head { padding: 14px 18px; font-weight: 700; font-size: 1.05rem; color: #fff; display: flex; align-items: center; gap: 10px; }
.td-mod-body { padding: 16px 18px 20px; }
.td-filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
.td-filters select { width: auto; min-width: 110px; font-size: 0.85rem; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--bs-border-color); }
.td-display { text-align: center; padding: 24px 16px; border-radius: 12px; margin-bottom: 14px; min-height: 72px; display: flex; align-items: center; justify-content: center; background: var(--bs-tertiary-bg); border: 2px solid var(--bs-border-color); transition: all 0.3s; }
.td-display.idle { border-style: dashed; color: var(--bs-secondary-color); font-size: 0.95rem; }
.td-display.spin { border-color: var(--bs-primary); border-style: solid; background: rgba(var(--bs-primary-rgb),0.05); }
.td-display.spin .td-name { animation: tdPulse 0.12s steps(2) infinite; }
.td-display.ok { border-color: var(--bs-success); border-style: solid; background: rgba(var(--bs-success-rgb),0.05); }
.td-name { font-size: 1.6rem; font-weight: 800; }
@keyframes tdPulse { 0% { opacity: 0.6; transform: translateY(-2px); } 100% { opacity: 1; transform: translateY(2px); } }
.td-go { display: block; width: 100%; padding: 11px; border-radius: 10px; border: none; font-size: 0.95rem; font-weight: 600; cursor: pointer; color: #fff; transition: opacity 0.2s; letter-spacing: 1px; }
.td-go:hover { opacity: 0.85; }
.td-detail { margin-top: 16px; font-size: 0.92rem; line-height: 1.8; display: none; padding: 16px; background: var(--bs-tertiary-bg); border-radius: 10px; }
.td-detail.show { display: block; animation: tdUp 0.3s ease; }
@keyframes tdUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.td-detail h6 { font-size: 1rem; margin-bottom: 6px; font-weight: 700; }
.td-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 0.78rem; margin: 3px 2px; font-weight: 500; }
.td-detail .links { margin-top: 8px; }
.td-detail .links a { font-size: 0.85rem; text-decoration: none; margin-right: 8px; }
.td-detail .links a:hover { text-decoration: underline; }
</style>

<div class="row g-4">
    <div class="col-md-4">
        <div class="td-mod">
            <div class="td-mod-head" style="background:linear-gradient(135deg,#ff6b6b,#ee5a24)">🍽️ 今天吃什么</div>
            <div class="td-mod-body">
                <div class="td-filters"><select class="form-select form-select-sm" id="foodCat"><option value="">全部分类</option></select></div>
                <div class="td-display idle" id="foodBox"><span>点击按钮随机选一道菜</span></div>
                <button class="td-go" id="foodBtn" style="background:linear-gradient(135deg,#ff6b6b,#ee5a24)" onclick="go('food')">🎲 随机一下</button>
                <div class="td-detail" id="foodInfo"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="td-mod">
            <div class="td-mod-head" style="background:linear-gradient(135deg,#6C5CE7,#0984e3)">🗺️ 今天去哪里</div>
            <div class="td-mod-body">
                <div class="td-filters">
                    <select class="form-select form-select-sm" id="placeCity"><option value="">选择城市</option></select>
                    <select class="form-select form-select-sm" id="placeFree"><option value="1">免费</option><option value="0">收费</option></select>
                </div>
                <div class="td-display idle" id="placeBox"><span>点击按钮随机选一个去处</span></div>
                <button class="td-go" id="placeBtn" style="background:linear-gradient(135deg,#6C5CE7,#0984e3)" onclick="go('place')">🎲 随机一下</button>
                <div class="td-detail" id="placeInfo"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="td-mod">
            <div class="td-mod-head" style="background:linear-gradient(135deg,#00b894,#00cec9)">🎬 今天看什么</div>
            <div class="td-mod-body">
                <div class="td-filters">
                    <select class="form-select form-select-sm" id="showType"><option value="tv">电视剧</option><option value="movie">电影</option><option value="variety">综艺</option><option value="anime">动漫</option></select>
                </div>
                <div class="td-display idle" id="showBox"><span>点击按钮随机选一部剧</span></div>
                <button class="td-go" id="showBtn" style="background:linear-gradient(135deg,#00b894,#00cec9)" onclick="go('show')">🎲 随机一下</button>
                <div class="td-detail" id="showInfo"></div>
            </div>
        </div>
    </div>
</div>

<script>
var A='/public/index.php?route=toolbox-today-do-api';
var lock={},tm={};

function init(){
    api('food_categories',function(d){fill('foodCat',d)});
    api('places_cities',function(d){fill('placeCity',d)});
}
function fill(id,a){var s=document.getElementById(id);a.forEach(function(v){var o=document.createElement('option');o.value=v;o.textContent=v;s.appendChild(o);});}
function api(act,cb,extra){
    var fd=new FormData();fd.append('action',act);
    if(extra)Object.keys(extra).forEach(function(k){fd.append(k,extra[k]);});
    fetch(A,{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.ok&&d.data)cb(d.data);}).catch(function(){});
}

function go(t){if(lock[t])return stop(t);start(t);}

function start(t){
    if(t==='place'&&!document.getElementById('placeCity').value){alert('请先选择城市');return;}
    var p={};
    if(t==='food'){var c=document.getElementById('foodCat').value;if(c)p.category=c;}
    else if(t==='place'){p.city=document.getElementById('placeCity').value;p.is_free=document.getElementById('placeFree').value;}
    else{p.type=document.getElementById('showType').value;}

    var acts={food:'get_food_list',place:'get_place_list',show:'get_show_list'};
    api(acts[t],function(list){
        if(!list||!list.length){alert('暂无匹配数据');return;}
        lock[t]={list:list,idx:Math.floor(Math.random()*list.length)};
        var box=document.getElementById(t+'Box'),btn=document.getElementById(t+'Btn');
        document.getElementById(t+'Info').className='td-detail';
        box.className='td-display spin';
        btn.textContent='停 止';
        var n=lock[t].idx;
        tm[t]=setInterval(function(){n=(n+1)%list.length;box.innerHTML='<span class="td-name">'+esc(list[n].name)+'</span>';},80);
    },p);
}

function stop(t){
    var m=lock[t];if(!m)return;clearInterval(tm[t]);
    var box=document.getElementById(t+'Box'),btn=document.getElementById(t+'Btn');
    var item=m.list[m.idx];
    box.className='td-display ok';
    box.innerHTML='<span class="td-name">'+esc(item.name)+'</span>';
    btn.textContent='🎲 再来一次';
    lock[t]=null;
    var info=document.getElementById(t+'Info');
    if(t==='food')foodHTML(info,item);else if(t==='place')placeHTML(info,item);else showHTML(info,item);
    info.className='td-detail show';
}

function foodHTML(el,d){
    var df={1:'简单',2:'中等',3:'困难'};
    var h='<div class="mb-2"><span class="td-chip" style="background:#fde2e2;color:#c0392b">'+esc(d.category)+'</span>';
    h+='<span class="td-chip" style="background:#e8f8f5;color:#1abc9c">'+(df[d.difficulty]||'')+'</span>';
    h+='<span class="td-chip" style="background:#fef9e7;color:#f39c12">⏱ '+d.time_min+'分钟</span>';
    if(d.is_takeout==1) h+='<span class="td-chip" style="background:#d5f5e3;color:#27ae60">🛵 可外卖</span></div>';
    if(d.ingredients) h+='<div class="mb-2"><strong>食材：</strong>'+esc(d.ingredients)+'</div>';
    h+='<div class="links">';
    if(d.recipe_url) h+='<a href="'+esc(d.recipe_url)+'" target="_blank">📖 查看菜谱</a>';
    h+='<a href="https://www.baidu.com/s?wd='+encodeURIComponent(d.name)+'+怎么做" target="_blank">🔍 搜索做法</a></div>';
    el.innerHTML=h;
}

function placeHTML(el,d){
    var h='<div class="mb-2"><span class="td-chip" style="background:#d6eaf8;color:#2e86c1">📍 '+esc(d.city)+'</span>';
    h+=d.is_free==1?'<span class="td-chip" style="background:#d4edda;color:#155724">🆓 免费</span>':'<span class="td-chip" style="background:#fff3cd;color:#856404">💰 ¥'+d.ticket_price+'</span>';
    if(d.category) h+='<span class="td-chip" style="background:#f4ecf7;color:#8e44ad">'+esc(d.category)+'</span></div>';
    if(d.description) h+='<div class="mb-2">'+esc(d.description)+'</div>';
    if(d.tips) h+='<div class="mb-1 text-muted">💡 '+esc(d.tips)+'</div>';
    h+='<div class="links"><a href="https://www.baidu.com/s?wd='+encodeURIComponent(d.name)+'+'+encodeURIComponent(d.city)+'" target="_blank">🔍 查看攻略</a></div>';
    el.innerHTML=h;
}

function showHTML(el,d){
    var tm2={tv:'📺 电视剧',movie:'🎬 电影',variety:'🎭 综艺',anime:'🎌 动漫'};
    var h='<div class="mb-2"><span class="td-chip" style="background:#d1ecf1;color:#0c5460">📡 '+esc(d.platform)+'</span>';
    h+='<span class="td-chip" style="background:#e8daef;color:#7d3c98">'+(tm2[d.type]||d.type)+'</span>';
    if(d.rating>0) h+='<span class="td-chip" style="background:#fef9e7;color:#f39c12">⭐ '+d.rating+'</span>';
    if(d.air_date) h+='<span class="td-chip" style="background:#d5f5e3;color:#27ae60">📅 '+esc(d.air_date)+'</span>';
    if(d.year>0&&!d.air_date) h+='<span class="td-chip" style="background:#fdebd0;color:#e67e22">'+d.year+'</span>';
    h+='</div>';
    if(d.status) h+='<div class="mb-1"><strong>状态：</strong>'+esc(d.status)+'</div>';
    if(d.cast) h+='<div class="mb-1"><strong>主演：</strong>'+esc(d.cast)+'</div>';
    if(d.description) h+='<div class="text-muted mb-2">'+esc(d.description)+'</div>';
    h+='<div class="links"><a href="https://www.baidu.com/s?wd='+encodeURIComponent(d.name)+'+'+encodeURIComponent(d.platform)+'" target="_blank">▶️ 去观看</a></div>';
    el.innerHTML=h;
}

function esc(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
init();
</script>
