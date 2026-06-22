<?php
/** @var array $properties */
/** @var string $currentStatus */
$statusLabels = ['all' => '全部', 'watching' => '关注中', 'bought' => '已购', 'dropped' => '已放弃'];
?>
<style>
.pr-card { border:1px solid rgba(0,0,0,0.06); border-radius:12px; padding:16px; background:rgba(255,255,255,0.65); backdrop-filter:blur(12px); margin-bottom:12px; transition:all 0.2s; }
body.theme-dark .pr-card { background:rgba(30,41,59,0.5); border-color:rgba(148,163,184,0.12); }
.pr-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); }
.pr-price { font-size:1.3rem; font-weight:700; color:#ef4444; }
.pr-unit { font-size:0.78rem; color:#999; }
.pr-info { font-size:0.82rem; color:#666; display:flex; gap:12px; flex-wrap:wrap; margin:6px 0; }
body.theme-dark .pr-info { color:#94a3b8; }
.pr-chart { height:60px; margin:8px 0; }
.pr-actions { display:flex; gap:4px; flex-wrap:wrap; }
.pr-trend { font-size:0.78rem; font-weight:600; }
.pr-trend.up { color:#ef4444; }
.pr-trend.down { color:#22c55e; }
.pr-trend.flat { color:#999; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">🏠 房产关注</h5>
    <button class="btn btn-primary btn-sm" onclick="openAddModal()">+ 添加关注</button>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <?php foreach ($statusLabels = ['all' => '全部', 'watching' => '关注中', 'bought' => '已购', 'dropped' => '已放弃'] as $k => $v): ?>
    <a href="?route=property-watch&status=<?= $k ?>" class="btn btn-sm <?= $currentStatus === $k ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $v ?></a>
    <?php endforeach; ?>
</div>

<div id="propertyList">
<?php if (empty($properties)): ?>
    <div class="text-center py-5 text-muted">
        <div style="font-size:3rem;margin-bottom:8px">🏠</div>
        <div>暂无关注的房产</div>
        <div class="small mt-1">点击右上角添加你关注的房产</div>
    </div>
<?php else: ?>
    <div class="row">
    <?php foreach ($properties as $p):
        $history = $p['price_history'] ?? [];
        $latestPrice = !empty($history) ? end($history) : null;
        $prevPrice = count($history) >= 2 ? $history[count($history) - 2] : null;
        $trend = 'flat';
        if ($latestPrice && $prevPrice) {
            if ($latestPrice['price'] > $prevPrice['price']) $trend = 'up';
            elseif ($latestPrice['price'] < $prevPrice['price']) $trend = 'down';
        }
    ?>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="pr-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                <span class="badge bg-<?= $p['status']==='watching'?'primary':($p['status']==='bought'?'success':'secondary') ?>"><?= $statusLabels[$p['status']] ?? $p['status'] ?></span>
            </div>
            <?php if (!empty($p['city']) || !empty($p['address'])): ?>
            <div class="pr-info"><span>📍 <?= htmlspecialchars($p['city'] . ' ' . $p['address']) ?></span></div>
            <?php endif; ?>
            <div class="pr-info">
                <?php if ($p['area'] > 0): ?><span><?= (float)$p['area'] ?>㎡</span><?php endif; ?>
                <?php if (!empty($p['layout'])): ?><span><?= htmlspecialchars($p['layout']) ?></span><?php endif; ?>
            </div>
            <div class="d-flex align-items-baseline gap-2 mt-1">
                <span class="pr-price"><?= $p['current_price'] > 0 ? number_format($p['current_price'], 2) . '万' : '未设置' ?></span>
                <?php if ($p['unit_price'] > 0): ?><span class="pr-unit"><?= number_format($p['unit_price'], 0) ?>元/㎡</span><?php endif; ?>
                <?php if ($trend !== 'flat'): ?><span class="pr-trend <?= $trend ?>"><?= $trend === 'up' ? '↑' : '↓' ?></span><?php endif; ?>
            </div>
            <?php if (!empty($history)): ?>
            <div class="pr-chart" id="chart-<?= $p['id'] ?>"></div>
            <script>
            (function(){
                var data = <?= json_encode(array_map(fn($h) => ['date'=>$h['recorded_at'],'price'=>(float)$h['price']], $history)) ?>;
                var el = document.getElementById('chart-<?= $p['id'] ?>');
                if (!data.length || !el) return;
                var min = Math.min.apply(null, data.map(function(d){return d.price}));
                var max = Math.max.apply(null, data.map(function(d){return d.price}));
                var range = max - min || 1;
                var w = el.offsetWidth || 200;
                var h = 50;
                var svg = '<svg width="'+w+'" height="'+h+'" viewBox="0 0 '+w+' '+h+'">';
                var points = data.map(function(d,i){
                    var x = (i / Math.max(data.length-1,1)) * w;
                    var y = h - ((d.price - min) / range) * (h-10) - 5;
                    return x+','+y;
                });
                svg += '<polyline points="'+points.join(' ')+'" fill="none" stroke="#667eea" stroke-width="2"/>';
                svg += '</svg>';
                el.innerHTML = svg;
            })();
            </script>
            <?php endif; ?>
            <div class="pr-actions mt-2">
                <button class="btn btn-sm btn-outline-primary py-0" onclick="openPriceModal(<?= $p['id'] ?>)">更新价格</button>
                <?php if (!empty($p['source_url'])): ?><a href="<?= htmlspecialchars($p['source_url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0">查看原页</a><?php endif; ?>
                <button class="btn btn-sm btn-outline-secondary py-0" onclick="openEditModal(<?= $p['id'] ?>)">编辑</button>
                <button class="btn btn-sm btn-outline-danger py-0" onclick="deleteProperty(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['name'])) ?>')">删除</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<!-- 添加/编辑弹窗 -->
<div class="modal fade" id="propertyModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title" id="propModalTitle">添加关注</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="propId">
        <div class="mb-2"><label class="form-label small">小区/楼盘名 <span class="text-danger">*</span></label><input type="text" id="propName" class="form-control form-control-sm"></div>
        <div class="row g-2 mb-2">
            <div class="col-4"><label class="form-label small">城市</label><input type="text" id="propCity" class="form-control form-control-sm" placeholder="北京"></div>
            <div class="col-8"><label class="form-label small">地址</label><input type="text" id="propAddr" class="form-control form-control-sm"></div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-4"><label class="form-label small">面积(㎡)</label><input type="number" id="propArea" class="form-control form-control-sm" step="0.01"></div>
            <div class="col-4"><label class="form-label small">户型</label><input type="text" id="propLayout" class="form-control form-control-sm" placeholder="3室2厅"></div>
            <div class="col-4"><label class="form-label small">状态</label>
                <select id="propStatus" class="form-select form-select-sm"><option value="watching">关注中</option><option value="bought">已购</option><option value="dropped">已放弃</option></select>
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small">挂牌价(万)</label><input type="number" id="propPrice" class="form-control form-control-sm" step="0.01"></div>
            <div class="col-6"><label class="form-label small">单价(元/㎡)</label><input type="number" id="propUnitPrice" class="form-control form-control-sm" step="1"></div>
        </div>
        <div class="mb-2"><label class="form-label small">贝壳/链家链接（可选）</label><input type="text" id="propUrl" class="form-control form-control-sm" placeholder="https://..."></div>
        <div class="mb-2"><label class="form-label small">贝壳小区ID（用于自动抓取价格）</label><input type="text" id="propSourceId" class="form-control form-control-sm" placeholder="如：1111027382113"></div>
        <div class="mb-2"><label class="form-label small">备注</label><textarea id="propNote" class="form-control form-control-sm" rows="2"></textarea></div>
    </div>
    <div class="modal-footer py-2"><button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button><button class="btn btn-sm btn-primary" onclick="saveProperty()">保存</button></div>
</div></div></div>

<!-- 更新价格弹窗 -->
<div class="modal fade" id="priceModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title">更新价格</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="pricePropId">
        <div class="mb-2"><label class="form-label small">挂牌价(万)</label><input type="number" id="priceValue" class="form-control form-control-sm" step="0.01"></div>
        <div class="mb-2"><label class="form-label small">单价(元/㎡)</label><input type="number" id="priceUnit" class="form-control form-control-sm" step="1"></div>
    </div>
    <div class="modal-footer py-2"><button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button><button class="btn btn-sm btn-primary" onclick="submitPrice()">保存</button></div>
</div></div></div>

<script>
var propData = <?= json_encode($properties, JSON_UNESCAPED_UNICODE) ?>;

function openAddModal() {
    document.getElementById('propId').value = '';
    document.getElementById('propName').value = '';
    document.getElementById('propCity').value = '';
    document.getElementById('propAddr').value = '';
    document.getElementById('propArea').value = '';
    document.getElementById('propLayout').value = '';
    document.getElementById('propPrice').value = '';
    document.getElementById('propUnitPrice').value = '';
    document.getElementById('propUrl').value = '';
    document.getElementById('propSourceId').value = '';
    document.getElementById('propNote').value = '';
    document.getElementById('propStatus').value = 'watching';
    document.getElementById('propModalTitle').textContent = '添加关注';
    new bootstrap.Modal(document.getElementById('propertyModal')).show();
}

function openEditModal(id) {
    var p = propData.find(function(x){ return x.id == id; });
    if (!p) return;
    document.getElementById('propId').value = p.id;
    document.getElementById('propName').value = p.name;
    document.getElementById('propCity').value = p.city || '';
    document.getElementById('propAddr').value = p.address || '';
    document.getElementById('propArea').value = p.area || '';
    document.getElementById('propLayout').value = p.layout || '';
    document.getElementById('propPrice').value = p.current_price || '';
    document.getElementById('propUnitPrice').value = p.unit_price || '';
    document.getElementById('propUrl').value = p.source_url || '';
    document.getElementById('propSourceId').value = p.source_id || '';
    document.getElementById('propNote').value = p.note || '';
    document.getElementById('propStatus').value = p.status;
    document.getElementById('propModalTitle').textContent = '编辑关注';
    new bootstrap.Modal(document.getElementById('propertyModal')).show();
}

function saveProperty() {
    var id = document.getElementById('propId').value;
    var name = document.getElementById('propName').value.trim();
    if (!name) { alert('请输入小区名称'); return; }
    var fd = new FormData();
    fd.append('action', id ? 'update_property' : 'add_property');
    if (id) fd.append('id', id);
    fd.append('name', name);
    fd.append('city', document.getElementById('propCity').value.trim());
    fd.append('address', document.getElementById('propAddr').value.trim());
    fd.append('area', document.getElementById('propArea').value);
    fd.append('layout', document.getElementById('propLayout').value.trim());
    fd.append('current_price', document.getElementById('propPrice').value);
    fd.append('unit_price', document.getElementById('propUnitPrice').value);
    fd.append('source_url', document.getElementById('propUrl').value.trim());
    fd.append('source_id', document.getElementById('propSourceId').value.trim());
    fd.append('note', document.getElementById('propNote').value.trim());
    if (id) fd.append('status', document.getElementById('propStatus').value);
    fetch('/public/index.php?route=property-api', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.ok) location.reload(); else alert(d.error); });
}

function deleteProperty(id, name) {
    if (!confirm('确定删除「'+name+'」及其价格历史？')) return;
    fetch('/public/index.php?route=property-api', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=delete_property&id='+id })
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.ok) location.reload(); else alert(d.error); });
}

function openPriceModal(id) {
    document.getElementById('pricePropId').value = id;
    document.getElementById('priceValue').value = '';
    document.getElementById('priceUnit').value = '';
    new bootstrap.Modal(document.getElementById('priceModal')).show();
}

function submitPrice() {
    var fd = new FormData();
    fd.append('action', 'update_price');
    fd.append('property_id', document.getElementById('pricePropId').value);
    fd.append('price', document.getElementById('priceValue').value);
    fd.append('unit_price', document.getElementById('priceUnit').value);
    fetch('/public/index.php?route=property-api', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.ok) location.reload(); else alert(d.error); });
}
</script>
