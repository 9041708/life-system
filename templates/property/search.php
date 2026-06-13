<?php
/** @var string $city */
/** @var string $keyword */
/** @var string $type */
/** @var int $page */
/** @var array $results */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">🔍 房产查询</h5>
    <a href="?route=property-watch" class="btn btn-sm btn-outline-secondary">← 返回关注</a>
</div>

<form method="get" class="card border-0 shadow-sm mb-3">
    <input type="hidden" name="route" value="property-search">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">城市</label>
                <select name="city" class="form-select form-select-sm">
                    <?php foreach (['北京','上海','广州','深圳','杭州','成都','武汉','南京','重庆','西安','苏州','天津','长沙','郑州','东莞','佛山','合肥','昆明','福州','厦门','大连','沈阳','青岛','济南','宁波','无锡','温州'] as $c): ?>
                    <option <?= $city === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small">搜索关键字</label>
                <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($keyword) ?>" placeholder="小区名、地段、地铁站...">
            </div>
            <div class="col-md-3">
                <label class="form-label small">类型</label>
                <div class="btn-group btn-group-sm w-100">
                    <input type="radio" class="btn-check" name="type" id="typeSale" value="sale" <?= $type === 'sale' ? 'checked' : '' ?>><label class="btn btn-outline-primary" for="typeSale">二手房</label>
                    <input type="radio" class="btn-check" name="type" id="typeRent" value="rent" <?= $type === 'rent' ? 'checked' : '' ?>><label class="btn btn-outline-primary" for="typeRent">租房</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">搜索</button>
            </div>
        </div>
    </div>
</form>

<?php if ($keyword !== ''): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (!($results['ok'] ?? false)): ?>
        <div class="text-center text-muted py-4">
            <div style="font-size:2rem;margin-bottom:8px">😅</div>
            <div><?= htmlspecialchars($results['error'] ?? '暂无结果') ?></div>
            <div class="small mt-1">贝壳找房有反爬限制，可能需要稍后重试。你也可以直接访问 <a href="https://<?= ['北京'=>'bj','上海'=>'sh','广州'=>'gz','深圳'=>'sz','杭州'=>'hz','成都'=>'cd','武汉'=>'wh','南京'=>'nj'][$city] ?? 'bj' ?>.ke.com" target="_blank">贝壳找房</a> 查看。</div>
        </div>
        <?php elseif (empty($results['items'])): ?>
        <div class="text-center text-muted py-4">
            <div style="font-size:2rem;margin-bottom:8px">🔍</div>
            <div>未找到「<?= htmlspecialchars($keyword) ?>」相关结果</div>
            <div class="small mt-1">试试换个关键字？</div>
        </div>
        <?php else: ?>
        <div class="small text-muted mb-2">找到 <?= count($results['items']) ?> 条结果（数据来自贝壳找房，仅供参考）</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>房源信息</th>
                    <?php if ($type === 'sale'): ?>
                    <th class="text-end">总价(万)</th>
                    <th class="text-end">单价(元/㎡)</th>
                    <?php else: ?>
                    <th class="text-end">月租(元)</th>
                    <?php endif; ?>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($results['items'] as $i => $item): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td>
                        <div class="fw-semibold small"><?= htmlspecialchars($item['title'] ?? '') ?></div>
                        <?php if (!empty($item['position'])): ?><div class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($item['position']) ?></div><?php endif; ?>
                        <?php if (!empty($item['info'])): ?><div class="text-muted" style="font-size:0.72rem"><?= htmlspecialchars($item['info']) ?></div><?php endif; ?>
                    </td>
                    <?php if ($type === 'sale'): ?>
                    <td class="text-end fw-bold text-danger"><?= number_format($item['total_price'] ?? 0, 2) ?></td>
                    <td class="text-end small text-muted"><?= number_format($item['unit_price'] ?? 0, 0) ?></td>
                    <?php else: ?>
                    <td class="text-end fw-bold text-primary"><?= number_format($item['rent_price'] ?? 0, 0) ?></td>
                    <?php endif; ?>
                    <td><button class="btn btn-sm btn-outline-primary py-0" onclick="watchFromSearch('<?= htmlspecialchars(addslashes($item['title'])) ?>','<?= $city ?>',<?= $item['total_price'] ?? 0 ?>,<?= $item['unit_price'] ?? 0 ?>)">+ 关注</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <?php if ($page > 1): ?><a href="?route=property-search&city=<?= urlencode($city) ?>&q=<?= urlencode($keyword) ?>&type=<?= $type ?>&page=<?= $page-1 ?>" class="btn btn-sm btn-outline-secondary">上一页</a><?php endif; ?>
            <span class="small text-muted align-self-center">第 <?= $page ?> 页</span>
            <a href="?route=property-search&city=<?= urlencode($city) ?>&q=<?= urlencode($keyword) ?>&type=<?= $type ?>&page=<?= $page+1 ?>" class="btn btn-sm btn-outline-secondary">下一页</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="text-center py-5 text-muted">
    <div style="font-size:3rem;margin-bottom:8px">🔍</div>
    <div>输入关键字搜索房源</div>
    <div class="small mt-1">支持二手房和租房搜索，数据来自贝壳找房</div>
</div>
<?php endif; ?>

<script>
function watchFromSearch(name, city, price, unitPrice) {
    var fd = new FormData();
    fd.append('action', 'add_property');
    fd.append('name', name);
    fd.append('city', city);
    fd.append('current_price', price);
    fd.append('unit_price', unitPrice);
    fetch('/public/index.php?route=property-api', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.ok) alert('已添加到关注列表'); else alert(d.error); });
}
</script>
