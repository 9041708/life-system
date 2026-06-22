<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">查看指定月份需要还款的负债项目。</div>
    <div class="d-flex gap-2 align-items-center">
        <form id="debtMonthForm" method="get" class="d-flex gap-1 align-items-center">
            <input type="hidden" name="route" value="debt-current">
            <input type="hidden" id="debtMonthHidden" name="month" value="<?= htmlspecialchars($month) ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary" name="month" value="<?= date('Y-m', strtotime('-1 month', strtotime($month . '-01'))) ?>" onclick="document.getElementById('debtMonthHidden').value=this.value;">&laquo; 上月</button>
            <input type="month" id="debtMonthPicker" name="month_picker" class="form-control form-control-sm" style="width:160px;" value="<?= htmlspecialchars($month) ?>" onchange="document.getElementById('debtMonthHidden').value=this.value; this.form.submit()">
            <button type="submit" class="btn btn-sm btn-outline-secondary" name="month" value="<?= date('Y-m', strtotime('+1 month', strtotime($month . '-01'))) ?>" onclick="document.getElementById('debtMonthHidden').value=this.value;">下月 &raquo;</button>
        </form>
        <a href="/public/index.php?route=debt-config" class="btn btn-sm btn-outline-primary">管理负债配置</a>
    </div>
</div>

<!-- 统计卡片 -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                        <span class="fs-4">💰</span>
                    </div>
                    <div>
                        <div class="text-muted small"><?= htmlspecialchars($month) ?> 应还总额</div>
                        <div class="fs-4 fw-bold">¥<?= number_format($totalAmount, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <span class="fs-4">📊</span>
                    </div>
                    <div>
                        <div class="text-muted small">剩余应还金额</div>
                        <div class="fs-4 fw-bold text-danger">¥<?= number_format($totalAmount - $paidAmount, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <span class="fs-4">✅</span>
                    </div>
                    <div>
                        <div class="text-muted small">已还金额</div>
                        <div class="fs-4 fw-bold text-success">¥<?= number_format($paidAmount, 2) ?></div>
<?php if ($paidCount > 0): ?>
                                <div class="text-muted" style="font-size:0.75rem;"><?= $paidCount ?> 笔已还</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <span class="fs-4">📅</span>
                        </div>
                        <div>
                            <div class="text-muted small">当前选择</div>
                            <div class="fs-4 fw-bold"><?= date('Y年m月', strtotime($month . '-01')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 还款列表 -->
    <?php if (empty($payments)): ?>
        <div class="text-center py-5">
            <div class="mb-3" style="font-size: 4rem;">🎉</div>
            <h5 class="text-muted">所选月份无应还项目</h5>
            <p class="text-muted">太棒了！该月份没有需要还款的负债，继续保持！</p>
            <a href="/public/index.php?route=debt-config" class="btn btn-primary mt-3">+ 添加负债配置</a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:50px">#</th>
                                <th>负债项目</th>
                                <th>期数</th>
                                <th>应还日期</th>
                                <th>本金</th>
                                <th>利息</th>
                                <th>总额</th>
                                <th>剩余期数</th>
                                <th>剩余金额</th>
                                <th>状态</th>
                                <th class="text-end pe-3">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $seq = 0; foreach ($payments as $payment): 
                                $seq++;
                                $paidAmt = (float)($payment['paid_amount'] ?? 0);
                                $totalAmt = (float)$payment['total_amount'];
                                $isFullyPaid = ($payment['status'] === 'paid');
                                $isPartialPaid = (!$isFullyPaid && $paidAmt > 0);
                                $isOverdue = (!$isFullyPaid && !$isPartialPaid && strtotime($payment['due_date']) < strtotime(date('Y-m-d')));
                                $isOverduePartial = ($isPartialPaid && strtotime($payment['due_date']) < strtotime(date('Y-m-d')));
                                $rowClass = $isFullyPaid ? 'table-success bg-opacity-10' : ($isOverdue || $isOverduePartial ? 'table-danger bg-opacity-10' : ($isPartialPaid ? 'table-warning bg-opacity-10' : ''));
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="ps-3 text-muted"><?= $seq ?></td>
                                    <td>
                                        <div class="fw-semibold <?= $isFullyPaid ? 'text-muted' : '' ?>"><?= htmlspecialchars($payment['debt_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">第 <?= $payment['period_number'] ?> 期</span>
                                    </td>
                                    <td>
                                        <span class="<?= $isOverdue ? 'text-danger fw-bold' : ($isFullyPaid ? 'text-muted' : '') ?>">
                                            <?= date('Y-m-d', strtotime($payment['due_date'])) ?>
                                        </span>
                                    </td>
                                    <td class="<?= $isFullyPaid ? 'text-muted' : '' ?>">¥<?= number_format($payment['principal_amount'], 2) ?></td>
                                    <td class="<?= $isFullyPaid ? 'text-muted' : '' ?>">¥<?= number_format($payment['interest_amount'], 2) ?></td>
                                    <td class="fw-bold <?= $isFullyPaid ? 'text-muted' : '' ?>">¥<?= number_format($payment['total_amount'], 2) ?></td>
                                    <td>
                                        <?php if ($isFullyPaid): ?>
                                            <span class="badge bg-secondary">第 <?= $payment['period_number'] ?>/<?= $payment['installment_count'] ?>期</span>
                                        <?php else: ?>
                                            <span class="badge bg-info"><?= $payment['remaining_periods'] ?> 期剩余</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isFullyPaid): ?>
                                            <span class="text-success fw-bold">¥<?= number_format($paidAmt, 2) ?></span>
                                            <div class="text-muted small">已还</div>
                                        <?php elseif ($isPartialPaid): ?>
                                            <span class="fw-bold <?= $isOverduePartial ? 'text-danger' : '' ?>">¥<?= number_format($paidAmt, 2) ?></span>
                                            <div class="small <?= $isOverduePartial ? 'text-danger fw-bold' : 'text-muted' ?>">已还</div>
                                        <?php else: ?>
                                            <span class="fw-bold text-warning">¥<?= number_format($payment['remaining_amount'], 2) ?></span>
                                            <div class="text-muted small">剩余</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($isFullyPaid): ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="undoPaid(<?= $payment['id'] ?>, '<?= htmlspecialchars($payment['debt_name']) ?>', '第 <?= $payment['period_number'] ?> 期')">↩ 回退还款</button>
                                        <?php else: ?>
                                            <button class="btn btn-sm <?= $isOverdue || $isOverduePartial ? 'btn-danger' : 'btn-success' ?>" onclick="markPaid(<?= $payment['id'] ?>, <?= $totalAmt ?>,'<?= $isPartialPaid ? number_format($paidAmt, 2) : number_format($totalAmt, 2) ?>')"><?= $isPartialPaid ? '补还款' : '标记还款' ?></button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isFullyPaid): ?>
                                            <span class="badge bg-success">已还款</span>
                                            <div class="text-muted small"><?= htmlspecialchars($payment['paid_date'] ?? '') ?></div>
                                        <?php elseif ($isOverduePartial): ?>
                                            <span class="badge bg-danger"><strong>逾期·已还<?= number_format($paidAmt, 2) ?></strong></span>
                                            <div class="small text-danger fw-bold">还差 ¥<?= number_format($totalAmt - $paidAmt, 2) ?></div>
                                        <?php elseif ($isPartialPaid): ?>
                                            <span class="badge bg-warning text-dark">已还部分</span>
                                            <div class="small text-muted">已还 ¥<?= number_format($paidAmt, 2) ?> / 剩余 ¥<?= number_format($totalAmt - $paidAmt, 2) ?></div>
                                        <?php elseif ($isOverdue): ?>
                                            <span class="badge bg-danger"><strong>已逾期</strong></span>
                                        <?php else: ?>
                                            <span class="badge bg-info">待还款</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($isFullyPaid): ?>
                                            <button 
                                                class="btn btn-sm btn-outline-secondary" 
                                                onclick="undoPaid(<?= $payment['id'] ?>, '<?= htmlspecialchars($payment['debt_name']) ?>', '第 <?= $payment['period_number'] ?> 期')"
                                            >
                                                ↩ 回退还款
                                            </button>
                                        <?php else: ?>
                                            <button 
                                                class="btn btn-sm btn-success" 
                                                onclick="markPaid(<?= $payment['id'] ?>, <?= $payment['total_amount'] ?>)"
                                            >
                                                标记还款
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 还款弹窗 -->
        <div class="modal fade" id="markPaidModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">标记还款</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="/public/index.php?route=debt-mark-paid">
                        <div class="modal-body">
                            <input type="hidden" name="payment_id" id="modalPaymentId">
                            <div class="mb-3">
                                <label class="form-label">本期应还金额</label>
                                <div class="fs-4 fw-bold text-warning" id="modalDueAmount"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">实际还款金额 <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="paid_amount" id="modalPaidAmount" required>
                                <div class="form-text">请输入实际还款金额，可以与应还金额不同</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-success">确认还款</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 回退还款确认弹窗 -->
        <div class="modal fade" id="undoPaidModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">回退还款</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="/public/index.php?route=debt-undo-paid">
                        <div class="modal-body">
                            <input type="hidden" name="payment_id" id="undoPaymentId">
                            <div class="text-center py-3">
                                <div class="mb-3" style="font-size:3rem;">⚠️</div>
                                <h5 class="mb-2">确认回退还款？</h5>
                                <p class="text-muted mb-0">
                                    将 <strong id="undoDebtName"></strong> <strong id="undoPeriod"></strong> 的还款记录回退为待还状态
                                </p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-outline-warning">确认回退</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// 标记还款弹窗
function markPaid(paymentId, dueAmount, defaultAmount) {
    document.getElementById('modalPaymentId').value = paymentId;
    document.getElementById('modalDueAmount').textContent = '¥' + parseFloat(dueAmount).toFixed(2);
    document.getElementById('modalPaidAmount').value = defaultAmount || dueAmount;
    var modal = new bootstrap.Modal(document.getElementById('markPaidModal'));
    modal.show();
}

// 回退还款弹窗
function undoPaid(paymentId, debtName, period) {
    document.getElementById('undoPaymentId').value = paymentId;
    document.getElementById('undoDebtName').textContent = debtName;
    document.getElementById('undoPeriod').textContent = period;
    
    var modal = new bootstrap.Modal(document.getElementById('undoPaidModal'));
    modal.show();
}

// 页面加载时检查是否需要显示恭喜弹窗
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['debt_congratulations']) && $_SESSION['debt_congratulations']): ?>
        // 显示恭喜弹窗
        showCongratulationsModal();
        <?php unset($_SESSION['debt_congratulations']); ?>
    <?php endif; ?>
});

// 显示恭喜弹窗
function showCongratulationsModal() {
    // 创建弹窗HTML
    var modalHtml = `
        <div class="modal fade" id="congratulationsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-5">
                        <div class="mb-4" style="font-size: 5rem;">🎊</div>
                        <h4 class="fw-bold text-success mb-3">恭喜你！上岸成功！</h4>
                        <p class="text-muted mb-4">
                            你已经完成了最后一期还款！<br>
                            从此摆脱债务，重新开始！<br>
                            未来的生活会更美好，加油！💪
                        </p>
                        <button type="button" class="btn btn-success btn-lg" data-bs-dismiss="modal">
                            感谢，继续前行！
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 添加到页面
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // 显示弹窗
    var modal = new bootstrap.Modal(document.getElementById('congratulationsModal'));
    modal.show();
    
    // 弹窗关闭后移除DOM
    document.getElementById('congratulationsModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}
</script>
