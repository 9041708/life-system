<div class="container-fluid py-3">
<h3>📋 产品与订单管理</h3>
<p>
    企业资金：<strong class="text-success">¥<?= number_format($company['balance']) ?></strong>
    &nbsp;|&nbsp;
    <a href="?route=enterprise" class="btn btn-outline-secondary btn-sm">← 返回企业</a>
</p>

<div class="row">
    <!-- 产品目录 -->
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>📦 在售产品目录</strong>
                <small class="text-muted">共 <?= count($prodList) ?> 个产品</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>产品名称</th><th>品质</th><th>单价</th><th class="text-center">来源</th></tr></thead>
                        <tbody>
                        <?php foreach ($prodList as $p):
                            $isBase = in_array($p['name'], ['办公文具套装','定制笔记本','企业名片印刷','节日礼盒','电子配件包','日用清洁套装','宣传画册','会员卡定制']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="progress" style="width:50px;height:6px"><div class="progress-bar <?= $p['quality']>=70?'bg-success':($p['quality']>=40?'bg-primary':'bg-warning') ?>" style="width:<?= $p['quality'] ?>%"></div></div>
                                    <?= $p['quality'] ?>
                                </div>
                            </td>
                            <td>¥<?= number_format($p['base_price']) ?></td>
                            <td class="text-center"><span class="badge <?= $isBase?'bg-secondary':'bg-success' ?>"><?= $isBase?'基础产品':'研发成果' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($prodList)): ?>
                        <tr><td colspan="4" class="text-muted text-center py-3">暂无产品，请通过研发中心研发新产品</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 添加新产品（走研发流程） -->
        <div class="card">
            <div class="card-header"><strong>🔬 提交新产品研发</strong></div>
            <div class="card-body">
                <div class="alert alert-info small mb-2">
                    💡 自定义产品需经过<strong>研发流程</strong>才能投入生产和销售。填写产品名称和预期售价，系统自动计算研发周期和投入。
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">产品名称</label>
                        <input type="text" id="prodName" class="form-control" placeholder="如：智能手表Pro">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">预期售价 ¥</label>
                        <input type="number" id="prodPrice" class="form-control" placeholder="售价" value="50000">
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">建议售价1-20万<br>研发费≈售价×30%</small>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="addProduct()">提交研发</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 订单列表 -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>📋 全部订单</strong>
                <small class="text-muted">订单基于产品自动生成</small>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>客户</th><th>产品</th><th>数量</th><th>金额</th><th>状态</th></tr></thead>
                        <tbody>
                        <?php foreach ($orderList as $o):
                            $statusBadge = match($o['status']) {
                                'pending' => 'bg-secondary',
                                'in_progress' => 'bg-info',
                                'completed' => 'bg-success',
                                'cancelled' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            $statusText = match($o['status']) {
                                'pending' => '待接单',
                                'in_progress' => '生产中',
                                'completed' => '已完成',
                                'cancelled' => '已取消',
                                default => $o['status']
                            };
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($o['client_name']) ?></td>
                            <td><?= htmlspecialchars($o['product_name']) ?></td>
                            <td><?= $o['quantity'] ?></td>
                            <td>¥<?= number_format($o['total_amount']) ?></td>
                            <td><span class="badge <?= $statusBadge ?>"><?= $statusText ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orderList)): ?>
                        <tr><td colspan="5" class="text-muted text-center py-3">暂无订单。系统每天根据产品目录自动生成订单。</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function addProduct() {
    let name = document.getElementById('prodName').value.trim();
    let price = document.getElementById('prodPrice').value;
    if (!name) return alert('请输入产品名称');
    if (!price || parseFloat(price) <= 0) return alert('请输入有效的预期售价');
    if (!confirm('该产品将进入研发流程，研发费用≈¥' + Math.round(parseFloat(price)*0.3).toLocaleString() + '，研发完成后自动上架销售。\n\n确定提交研发吗？')) return;
    fetch('?route=enterprise-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=addProduct&name=' + encodeURIComponent(name) + '&price=' + price
    }).then(r => r.json()).then(d => {
        if (d.ok) { alert(d.message); location.reload(); } else alert(d.error);
    });
}
</script>
