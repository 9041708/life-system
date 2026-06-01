<?php
/** @var array $deposits */
/** @var array $summary */
$totalPrincipal = (float)($summary['total_principal'] ?? 0);
$totalInterest = (float)($summary['total_interest'] ?? 0);
$earnedInterest = (float)($summary['earned_interest'] ?? 0);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">💰 理财管理</h5>
    <button class="btn btn-sm btn-glass" onclick="showAddModal()">+ 添加理财</button>
</div>

<!-- 统计卡片 -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body py-3 text-center">
                <div class="small text-muted mb-1">💰 已存本金</div>
                <div class="fs-4 fw-bold text-primary" id="cardPrincipal"><?= number_format($totalPrincipal, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body py-3 text-center">
                <div class="small text-muted mb-1">📈 可获利息</div>
                <div class="fs-4 fw-bold text-success" id="cardInterest"><?= number_format($totalInterest, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body py-3 text-center">
                <div class="small text-muted mb-1">🏦 连本带利</div>
                <div class="fs-4 fw-bold text-warning" id="cardTotal"><?= number_format($totalPrincipal + $totalInterest, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- 存款列表 -->
<div class="card glass-card">
    <div class="card-body p-3">
        <h5 class="small fw-bold mb-3">📋 存款记录</h5>
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>存款日期</th>
                        <th>金额</th>
                        <th>方式</th>
                        <th>到期时间</th>
                        <th>年化利率</th>
                        <th>预估利息</th>
                        <th>状态</th>
                        <th style="width:100px">操作</th>
                    </tr>
                </thead>
                <tbody id="depositList">
                    <?php if (empty($deposits)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">暂无理财记录</td></tr>
                    <?php else: ?>
                    <?php foreach ($deposits as $d): ?>
                    <?php $isActive = ($d['status'] ?? 'active') === 'active'; ?>
                    <tr class="<?= $isActive ? '' : 'text-muted' ?>">
                        <td><?= htmlspecialchars($d['deposit_date'] ?? '') ?></td>
                        <td class="fw-bold"><?= number_format((float)($d['amount'] ?? 0), 2) ?></td>
                        <td><?= htmlspecialchars($d['method'] ?? '') ?></td>
                        <td><?= htmlspecialchars($d['maturity_date'] ?? '-') ?></td>
                        <td><?= $d['annual_rate'] !== null ? number_format((float)$d['annual_rate'] * 100, 2) . '%' : '-' ?></td>
                        <td><?= $d['estimated_interest'] !== null ? number_format((float)$d['estimated_interest'], 2) : '-' ?></td>
                        <td><?= $isActive ? '<span class="badge bg-success">存续</span>' : '<span class="badge bg-secondary">已取出</span>' ?></td>
                        <td>
                            <?php if ($isActive): ?>
                            <button class="btn btn-sm btn-outline-warning py-0 px-1" onclick="showWithdrawModal(<?= (int)$d['id'] ?>, '<?= htmlspecialchars($d['deposit_date']) ?>', <?= (float)$d['amount'] ?>)">取出</button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteDeposit(<?= (int)$d['id'] ?>)">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 添加理财弹窗 -->
<div class="modal fade mgmt-modal" id="modalAdd" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">添加理财</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="formAdd">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small mb-0">存款日期</label><input type="date" name="deposit_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-6"><label class="form-label small mb-0">存款金额</label><input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" oninput="calcInterest()"></div>
                        <div class="col-6"><label class="form-label small mb-0">存款方式</label>
                            <select name="method" class="form-select form-select-sm">
                                <option value="存单">存单</option><option value="存折">存折</option><option value="硬卡">硬卡</option><option value="其它">其它</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label small mb-0">到期时间</label><input type="date" name="maturity_date" class="form-control form-control-sm" oninput="calcInterest()"></div>
                        <div class="col-6"><label class="form-label small mb-0">年化利率 (%)</label><input type="number" step="0.01" name="annual_rate" class="form-control form-control-sm" placeholder="如 2.5" oninput="calcInterest()"></div>
                        <div class="col-6"><label class="form-label small mb-0">预估利息</label><input type="text" name="estimated_interest_display" class="form-control form-control-sm" readonly placeholder="自动计算"></div>
                        <div class="col-6"><label class="form-label small mb-0">自动续期</label>
                            <select name="auto_renew" class="form-select form-select-sm"><option value="0">否</option><option value="1">是</option></select>
                        </div>
                        <div class="col-12"><label class="form-label small mb-0">备注</label><input type="text" name="notes" class="form-control form-control-sm"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-sm btn-primary" onclick="submitAdd()">确认添加</button>
            </div>
        </div>
    </div>
</div>

<!-- 取出弹窗 -->
<div class="modal fade mgmt-modal" id="modalWithdraw" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">取出理财</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="small text-muted mb-2">存款日期：<span id="wdDate"></span> | 存款金额：<span id="wdAmount"></span></div>
                <input type="hidden" id="withdrawId">
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small mb-0">取出日期</label><input type="date" id="wdDate2" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-6"><label class="form-label small mb-0">取出本金</label><input type="number" step="0.01" id="wdPrincipal" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small mb-0">获得利息</label><input type="number" step="0.01" id="wdInterest" class="form-control form-control-sm"></div>
                    <div class="col-12"><label class="form-label small mb-0">备注</label><input type="text" id="wdNotes" class="form-control form-control-sm"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-sm btn-warning" onclick="submitWithdraw()">确认取出</button>
            </div>
        </div>
    </div>
</div>

<script>
var addModal, withdrawModal;
document.addEventListener('DOMContentLoaded', function(){
    addModal = new bootstrap.Modal(document.getElementById('modalAdd'));
    withdrawModal = new bootstrap.Modal(document.getElementById('modalWithdraw'));
});

function showAddModal() {
    document.getElementById('formAdd').reset();
    document.querySelector('#formAdd [name=deposit_date]').value = '<?= date('Y-m-d') ?>';
    document.querySelector('#formAdd [name=estimated_interest_display]').value = '';
    addModal.show();
}

function calcInterest() {
    var amount = parseFloat(document.querySelector('#formAdd [name=amount]').value) || 0;
    var rate = parseFloat(document.querySelector('#formAdd [name=annual_rate]').value) || 0;
    var depDate = document.querySelector('#formAdd [name=deposit_date]').value;
    var matDate = document.querySelector('#formAdd [name=maturity_date]').value;
    var display = document.querySelector('#formAdd [name=estimated_interest_display]');
    if (amount > 0 && rate > 0 && depDate && matDate) {
        var days = Math.max(0, (new Date(matDate) - new Date(depDate)) / 86400000);
        var interest = amount * (rate / 100) * (days / 365);
        display.value = interest.toFixed(2);
    } else {
        display.value = '';
    }
}

function submitAdd() {
    var fd = new FormData(document.getElementById('formAdd'));
    fd.append('action', 'create');
    var displayVal = document.querySelector('#formAdd [name=estimated_interest_display]').value;
    if (displayVal) fd.set('annual_rate', (parseFloat(fd.get('annual_rate') || '0') / 100).toString());
    fetch('/public/index.php?route=finance-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) { addModal.hide(); updateSummary(d.summary); reloadList(); }
            else alert(d.error || '添加失败');
        });
}

function showWithdrawModal(id, date, amount) {
    document.getElementById('withdrawId').value = id;
    document.getElementById('wdDate').textContent = date;
    document.getElementById('wdAmount').textContent = parseFloat(amount).toFixed(2);
    document.getElementById('wdPrincipal').value = amount;
    document.getElementById('wdInterest').value = '';
    document.getElementById('wdNotes').value = '';
    withdrawModal.show();
}

function submitWithdraw() {
    var fd = new FormData();
    fd.append('action', 'withdraw');
    fd.append('id', document.getElementById('withdrawId').value);
    fd.append('withdraw_date', document.getElementById('wdDate2').value);
    fd.append('withdraw_principal', document.getElementById('wdPrincipal').value);
    fd.append('withdraw_interest', document.getElementById('wdInterest').value);
    fd.append('withdraw_notes', document.getElementById('wdNotes').value);
    fetch('/public/index.php?route=finance-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) { withdrawModal.hide(); updateSummary(d.summary); reloadList(); }
            else alert('取出失败');
        });
}

function deleteDeposit(id) {
    if (!confirm('确定删除该理财记录？')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('/public/index.php?route=finance-api', {method:'POST', body: fd})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok) { updateSummary(d.summary); reloadList(); }
            else alert('删除失败');
        });
}

function updateSummary(s) {
    document.getElementById('cardPrincipal').textContent = parseFloat(s.total_principal || 0).toFixed(2);
    document.getElementById('cardInterest').textContent = parseFloat(s.total_interest || 0).toFixed(2);
    document.getElementById('cardTotal').textContent = (parseFloat(s.total_principal || 0) + parseFloat(s.total_interest || 0)).toFixed(2);
}

function reloadList() {
    fetch('/public/index.php?route=finance-api', {method:'POST', body: new URLSearchParams({action:'load'})})
        .then(function(r){return r.json()})
        .then(function(d){
            if (!d.ok) return;
            var html = '';
            if (!d.deposits || d.deposits.length === 0) {
                html = '<tr><td colspan="8" class="text-center text-muted py-4">暂无理财记录</td></tr>';
            } else {
                d.deposits.forEach(function(item){
                    var active = item.status === 'active';
                    html += '<tr class="' + (active ? '' : 'text-muted') + '">';
                    html += '<td>' + (item.deposit_date || '') + '</td>';
                    html += '<td class="fw-bold">' + parseFloat(item.amount || 0).toFixed(2) + '</td>';
                    html += '<td>' + (item.method || '') + '</td>';
                    html += '<td>' + (item.maturity_date || '-') + '</td>';
                    html += '<td>' + (item.annual_rate != null ? (parseFloat(item.annual_rate) * 100).toFixed(2) + '%' : '-') + '</td>';
                    html += '<td>' + (item.estimated_interest != null ? parseFloat(item.estimated_interest).toFixed(2) : '-') + '</td>';
                    html += '<td>' + (active ? '<span class="badge bg-success">存续</span>' : '<span class="badge bg-secondary">已取出</span>') + '</td>';
                    html += '<td>';
                    if (active) html += '<button class="btn btn-sm btn-outline-warning py-0 px-1" onclick="showWithdrawModal(' + item.id + ',\'' + item.deposit_date + '\',' + item.amount + ')">取出</button> ';
                    html += '<button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteDeposit(' + item.id + ')">删除</button>';
                    html += '</td></tr>';
                });
            }
            document.getElementById('depositList').innerHTML = html;
        });
}
</script>
