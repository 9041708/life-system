<?php if ($isEdit && $config): ?>
                            <input type="hidden" name="id" value="<?= $config['id'] ?>">
                        <?php endif; ?>
                        
                        <!-- 负债名称 -->
                        <div class="mb-3">
                            <label class="form-label">负债项目名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?= $config ? htmlspecialchars($config['name']) : '' ?>" 
                                   placeholder="例如：信用卡分期、车贷、房贷等" required>
                        </div>
                        
                        <!-- 总本金 -->
                        <div class="mb-3">
                            <label class="form-label">总本金（元） <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="total_principal" 
                                   value="<?= $config ? $config['total_principal'] : '' ?>" 
                                   placeholder="0.00" required id="totalPrincipal">
                        </div>
                        
                        <!-- 总利息 -->
                        <div class="mb-3">
                            <label class="form-label">总利息（元）</label>
                            <input type="number" step="0.01" class="form-control" name="total_interest" 
                                   value="<?= $config ? $config['total_interest'] : '0.00' ?>" 
                                   placeholder="0.00" id="totalInterest">
                            <div class="form-text">如果不产生利息，请填0</div>
                        </div>
                        
                        <!-- 总期数 -->
                        <div class="mb-3">
                            <label class="form-label">总期数 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="installment_count" 
                                   value="<?= $config ? $config['installment_count'] : '' ?>" 
                                   placeholder="例如：12、24、36" required id="installmentCount">
                        </div>
                        
                        <!-- 每期本金（自动计算） -->
                        <div class="mb-3">
                            <label class="form-label">每期本金（元）</label>
                            <input type="number" step="0.01" class="form-control bg-light" name="per_period_principal" 
                                   value="<?= $config ? $config['per_period_principal'] : '' ?>" 
                                   readonly id="perPeriodPrincipal">
                            <div class="form-text">自动计算：总本金 ÷ 总期数</div>
                        </div>
                        
                        <!-- 每期利息（自动计算） -->
                        <div class="mb-3">
                            <label class="form-label">每期利息（元）</label>
                            <input type="number" step="0.01" class="form-control bg-light" name="per_period_interest" 
                                   value="<?= $config ? $config['per_period_interest'] : '' ?>" 
                                   readonly id="perPeriodInterest">
                            <div class="form-text">自动计算：总利息 ÷ 总期数</div>
                        </div>
                        
                        <!-- 每期总额（自动计算） -->
                        <div class="mb-3">
                            <label class="form-label">每期总额（元）</label>
                            <input type="number" step="0.01" class="form-control bg-warning bg-opacity-10 fw-bold" 
                                   name="per_period_total" 
                                   value="<?= $config ? $config['per_period_total'] : '' ?>" 
                                   readonly id="perPeriodTotal">
                            <div class="form-text">自动计算：每期本金 + 每期利息</div>
                        </div>
                        
                        <!-- 首次还款日期 -->
                        <div class="mb-3">
                            <label class="form-label">首次还款日期 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="first_payment_date" 
                                   value="<?= $config ? date('Y-m-d', strtotime($config['first_payment_date'])) : '' ?>" 
                                   required>
                        </div>
                        
                        <!-- 还款方式 -->
                        <div class="mb-3">
                            <label class="form-label">还款方式</label>
                            <select class="form-select" name="repayment_method">
                                <option value="equal" <?= ($config && $config['repayment_method'] === 'equal') ? 'selected' : '' ?>>
                                    等额本息（每期还款金额相同）
                                </option>
                                <option value="principal" <?= ($config && $config['repayment_method'] === 'principal') ? 'selected' : '' ?>>
                                    等额本金（每期本金相同，利息递减）
                                </option>
                            </select>
                        </div>
                        
                        <!-- 备注 -->
                        <div class="mb-4">
                            <label class="form-label">备注</label>
                            <textarea class="form-control" name="note" rows="3" 
                                      placeholder="可选备注信息"><?= $config ? htmlspecialchars($config['note']) : '' ?></textarea>
                        </div>
                        
                        <!-- 提交按钮 -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <?= $isEdit ? '更新配置' : '创建配置' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 自动计算每期金额
function calculatePeriodAmounts() {
    var totalPrincipal = parseFloat(document.getElementById('totalPrincipal').value) || 0;
    var totalInterest = parseFloat(document.getElementById('totalInterest').value) || 0;
    var installmentCount = parseInt(document.getElementById('installmentCount').value) || 1;
    
    // 防止除以0
    if (installmentCount <= 0) {
        installmentCount = 1;
    }
    
    // 计算每期本金和利息
    var perPeriodPrincipal = totalPrincipal / installmentCount;
    var perPeriodInterest = totalInterest / installmentCount;
    var perPeriodTotal = perPeriodPrincipal + perPeriodInterest;
    
    // 更新输入框（保留2位小数）
    document.getElementById('perPeriodPrincipal').value = perPeriodPrincipal.toFixed(2);
    document.getElementById('perPeriodInterest').value = perPeriodInterest.toFixed(2);
    document.getElementById('perPeriodTotal').value = perPeriodTotal.toFixed(2);
}

// 监听输入框变化
document.getElementById('totalPrincipal').addEventListener('input', calculatePeriodAmounts);
document.getElementById('totalInterest').addEventListener('input', calculatePeriodAmounts);
document.getElementById('installmentCount').addEventListener('input', calculatePeriodAmounts);

// 页面加载时计算一次（编辑模式）
<?php if ($isEdit && $config): ?>
    document.addEventListener('DOMContentLoaded', calculatePeriodAmounts);
<?php endif; ?>
</script>
