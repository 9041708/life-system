<?php
$tx = $transaction ?? null;
$type = $tx['type'] ?? ($_POST['type'] ?? 'expense');
$categoryId = $tx['category_id'] ?? ($_POST['category_id'] ?? '');
$itemId = $tx['item_id'] ?? ($_POST['item_id'] ?? '');
$fromAccountId = $tx['from_account_id'] ?? ($_POST['from_account_id'] ?? '');
$toAccountId = $tx['to_account_id'] ?? ($_POST['to_account_id'] ?? '');
$amount = $tx['amount'] ?? ($_POST['amount'] ?? '');
$remark = $tx['remark'] ?? ($_POST['remark'] ?? '');
$transTime = $tx['trans_time'] ?? ($_POST['trans_time'] ?? date('Y-m-d H:i'));
$goalsByAccount = $goalsByAccount ?? [];
$initialGoalInId = (int)($goalInId ?? ($_POST['goal_in_id'] ?? 0));
$initialGoalOutId = (int)($goalOutId ?? ($_POST['goal_out_id'] ?? 0));
$initialGoalInSync = (int)($_POST['goal_in_sync'] ?? 0);
$initialGoalOutSync = (int)($_POST['goal_out_sync'] ?? 0);
if (($mode ?? '') === 'edit') {
    if ($initialGoalInId > 0) $initialGoalInSync = 1;
    if ($initialGoalOutId > 0) $initialGoalOutSync = 1;
}
// 转换为 datetime-local 控件可用的值：YYYY-MM-DDTHH:MM（只到分，不显示秒）
if (!empty($transTime)) {
    if (strpos($transTime, 'T') === false) {
        // 兼容旧格式 "Y-m-d H:i:s" 或 "Y-m-d H:i"
        $normalized = substr($transTime, 0, 16);
        $transTimeInput = str_replace(' ', 'T', $normalized);
    } else {
        $transTimeInput = substr($transTime, 0, 16);
    }
} else {
    $transTimeInput = date('Y-m-d\TH:i');
}
?>
<style>
#txEditModal {
    z-index: 1055 !important;
}
#txEditModal .modal-content {
    position: relative;
    z-index: 1;
    pointer-events: auto;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(18px) saturate(130%);
    -webkit-backdrop-filter: blur(18px) saturate(130%);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 1.25rem;
    box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.15);
}
body.theme-dark #txEditModal .modal-content {
    position: relative;
    z-index: 1;
    pointer-events: auto;
    background: rgba(30, 41, 59, 0.75);
    backdrop-filter: blur(18px) saturate(110%);
    -webkit-backdrop-filter: blur(18px) saturate(110%);
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 1.5rem 3rem rgba(0, 0, 0, 0.45);
}
#txEditModal .modal-header { border-bottom-color: rgba(15,23,42,0.05); background: transparent; }
body.theme-dark #txEditModal .modal-header { border-bottom-color: rgba(148,163,184,0.1); }
#txEditModal .modal-footer { border-top-color: rgba(15,23,42,0.05); background: transparent; }
body.theme-dark #txEditModal .modal-footer { border-top-color: rgba(148,163,184,0.1); }
#txEditModal .modal-body { background: transparent; }
#txEditModal .modal-dialog {
    position: relative;
    z-index: 2;
    pointer-events: auto;
}

.tx-form-section {
    margin-bottom: 0.85rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
}
.tx-form-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
body.theme-dark .tx-form-section { border-bottom-color: rgba(148, 163, 184, 0.1); }
.tx-form-section-label {
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 0.75rem;
}
body.theme-dark .tx-form-section-label { color: #64748b; }

.tx-type-pill {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.45rem 1rem; border-radius: 2rem; border: 1.5px solid rgba(15,23,42,0.12);
    cursor: pointer; font-size: 0.85rem; font-weight: 500;
    transition: all 0.2s ease; user-select: none;
    background: rgba(255,255,255,0.6); backdrop-filter: blur(4px);
}
.tx-type-pill:hover { border-color: #3b82f6; background: rgba(59,130,246,0.06); }
.tx-type-pill.active {
    border-color: #3b82f6; background: rgba(59,130,246,0.12);
    color: #2563eb; font-weight: 600; box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
}
body.theme-dark .tx-type-pill {
    background: rgba(30,41,59,0.5); border-color: rgba(148,163,184,0.15); color: #cbd5e1;
}
body.theme-dark .tx-type-pill:hover { border-color: #60a5fa; }
body.theme-dark .tx-type-pill.active {
    border-color: #60a5fa; background: rgba(59,130,246,0.2);
    color: #93c5fd; box-shadow: 0 0 0 3px rgba(96,165,250,0.15);
}

.btn-save-gradient {
    background: linear-gradient(135deg, #2563eb, #4f46e5) !important;
    border: none !important; color: #fff !important;
    padding: 0.55rem 2rem; border-radius: 0.75rem; font-weight: 600;
    transition: all 0.25s ease; box-shadow: 0 4px 14px rgba(37,99,235,0.3);
}
.btn-save-gradient:hover {
    box-shadow: 0 6px 20px rgba(37,99,235,0.4);
    transform: translateY(-1px);
}

/* 表单控件美化：圆润 + 收窄 */
.tx-glass-form .row.g-3 { --bs-gutter-y: 0.5rem; }
.tx-glass-form select.form-select,
.tx-glass-form input.form-control,
.tx-glass-form textarea.form-control {
    border-radius: 0.75rem !important;
    border: 1.5px solid rgba(15, 23, 42, 0.1) !important;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: none !important;
    width: 100%;
    max-width: 100%;
}
.tx-glass-form select.form-select:hover,
.tx-glass-form input.form-control:hover,
.tx-glass-form textarea.form-control:hover {
    border-color: rgba(59, 130, 246, 0.35) !important;
    background: rgba(255, 255, 255, 0.8);
}
.tx-glass-form select.form-select:focus,
.tx-glass-form input.form-control:focus,
.tx-glass-form textarea.form-control:focus {
    border-color: #3b82f6 !important;
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12) !important;
    outline: none;
}
body.theme-dark .tx-glass-form select.form-select,
body.theme-dark .tx-glass-form input.form-control,
body.theme-dark .tx-glass-form textarea.form-control {
    background: rgba(30, 41, 59, 0.5) !important;
    border-color: rgba(148, 163, 184, 0.18) !important;
    color: #e5e7eb !important;
}
body.theme-dark .tx-glass-form select.form-select:hover,
body.theme-dark .tx-glass-form input.form-control:hover,
body.theme-dark .tx-glass-form textarea.form-control:hover {
    border-color: rgba(96, 165, 250, 0.4) !important;
    background: rgba(30, 41, 59, 0.65) !important;
}
body.theme-dark .tx-glass-form select.form-select:focus,
body.theme-dark .tx-glass-form input.form-control:focus,
body.theme-dark .tx-glass-form textarea.form-control:focus {
    border-color: #60a5fa !important;
    background: rgba(30, 41, 59, 0.75) !important;
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15) !important;
}

.tx-glass-form .tx-amount-input {
    font-size: 1.05rem !important;
    font-weight: 600;
}
.tx-amount-input::-webkit-inner-spin-button,
.tx-amount-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.tx-amount-input {
    -moz-appearance: textfield;
}

/* 账户选项表格对齐 */
.opt-g, .opt-n, .opt-b { display: inline-block; vertical-align: middle; white-space: nowrap; }
.opt-g { min-width: 56px; font-size: 0.78rem; color: #6b7280; }
.opt-n { min-width: 80px; overflow: hidden; text-overflow: ellipsis; }
.opt-b { min-width: 80px; text-align: right; font-weight: 600; }
body.theme-dark .opt-g { color: #94a3b8; }

/* 选中值显示区域更宽松 */
.choices__list--single .choices__item { max-width: none !important; }

.tx-glass-form .row.g-3 {
    --bs-gutter-y: 0.6rem;
}

/* Choices.js 下拉美化 */
.tx-glass-form .choices__inner {
    border-radius: 0.75rem !important;
    border: 1.5px solid rgba(15, 23, 42, 0.1) !important;
    background: rgba(255, 255, 255, 0.6) !important;
    padding: 0.35rem 0.65rem !important;
    font-size: 0.875rem !important;
    min-height: auto !important;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}
.tx-glass-form .choices__list--dropdown,
.tx-glass-form .choices__list[aria-expanded] {
    border-radius: 0.5rem !important;
    border: 1.5px solid rgba(15, 23, 42, 0.08) !important;
    background: rgba(255, 255, 255, 0.9) !important;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1) !important;
}
.tx-glass-form .choices__list--dropdown .choices__item--selectable,
.tx-glass-form .choices__list[aria-expanded] .choices__item--selectable {
    font-size: 0.85rem;
    padding: 0.4rem 0.65rem;
}
body.theme-dark .tx-glass-form .choices__inner {
    background: rgba(30, 41, 59, 0.5) !important;
    border-color: rgba(148, 163, 184, 0.18) !important;
    color: #e5e7eb !important;
}
body.theme-dark .tx-glass-form .choices__list--dropdown,
body.theme-dark .tx-glass-form .choices__list[aria-expanded] {
    background: rgba(30, 41, 59, 0.9) !important;
    border-color: rgba(148, 163, 184, 0.15) !important;
}
body.theme-dark .tx-glass-form .choices__list--dropdown .choices__item--selectable,
body.theme-dark .tx-glass-form .choices__list[aria-expanded] .choices__item--selectable {
    color: #e5e7eb !important;
}
body.theme-dark .tx-glass-form .choices__input {
    background: rgba(15, 23, 42, 0.5) !important;
    border-color: rgba(148, 163, 184, 0.2) !important;
    color: #e5e7eb !important;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0"><?= $mode === 'edit' ? '编辑记账' : '新增记账' ?></h2>
    <a href="/public/index.php?route=transactions" class="btn btn-sm btn-outline-secondary">返回明细</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card glass-card mb-4">
    <div class="card-body p-4">
        <div class="tx-glass-form">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="source" value="<?= htmlspecialchars($tx['source'] ?? 'manual', ENT_QUOTES) ?>">

            <!-- 类型选择 -->
            <div class="tx-form-section">
                <div class="tx-form-section-label">📌 记账类型</div>
                <div class="d-flex flex-wrap gap-2" id="txTypePills">
                    <label class="tx-type-pill<?= $type === 'expense' ? ' active' : '' ?>" data-value="expense">
                        📤 支出
                    </label>
                    <label class="tx-type-pill<?= $type === 'income' ? ' active' : '' ?>" data-value="income">
                        📥 收入
                    </label>
                    <?php if (!empty($transferEnabled)): ?>
                    <label class="tx-type-pill<?= $type === 'transfer' ? ' active' : '' ?>" data-value="transfer">
                        🔄 转账
                    </label>
                    <?php endif; ?>
                </div>
                <select name="type" class="d-none" id="txTypeSelect">
                    <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>支出</option>
                    <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>收入</option>
                    <?php if (!empty($transferEnabled)): ?>
                        <option value="transfer" <?= $type === 'transfer' ? 'selected' : '' ?>>转账</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- 分类 / 项目 / 金额 -->
            <div class="tx-form-section">
                <div class="tx-form-section-label">📂 分类 · 项目 · 金额</div>
                <div class="row g-3">
    <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold">分类 <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select form-select-sm js-icon-select" required>
            <option value="">请选择</option>
            <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                            data-type="<?= htmlspecialchars($c['type']) ?>"
                            data-icon-type="<?= htmlspecialchars($c['icon_type'] ?? '', ENT_QUOTES) ?>"
                            data-icon-value="<?= htmlspecialchars($c['icon_value'] ?? '', ENT_QUOTES) ?>"
                            <?= (string)$categoryId === (string)$c['id'] ? 'selected' : '' ?>>
                    <?php
                    $typeLabel = '收入';
                    if ($c['type'] === 'expense') {
                        $typeLabel = '支出';
                    } elseif ($c['type'] === 'transfer') {
                        $typeLabel = '转账';
                    }
                    ?>
                    <?= htmlspecialchars('[' . $typeLabel . '] ' . $c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
            <div class="small mt-1" id="txFormCategoryIconPreview"></div>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold">项目</label>
        <select name="item_id" class="form-select form-select-sm js-icon-select">
            <option value="">不选项目</option>
            <?php foreach ($items as $i): ?>
                    <?php
                    $itemCategoryType = null;
                    foreach ($categories as $cTmp) {
                        if ((int)$cTmp['id'] === (int)$i['category_id']) {
                            $itemCategoryType = $cTmp['type'];
                            break;
                        }
                    }
                    ?>
                    <option value="<?= (int)$i['id'] ?>"
                            data-category="<?= (int)$i['category_id'] ?>"
                            data-type="<?= htmlspecialchars($itemCategoryType ?? '', ENT_QUOTES) ?>"
                            data-icon-type="<?= htmlspecialchars($i['icon_type'] ?? '', ENT_QUOTES) ?>"
                            data-icon-value="<?= htmlspecialchars($i['icon_value'] ?? '', ENT_QUOTES) ?>"
                            <?= (string)$itemId === (string)$i['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($i['name']) ?>
                    </option>
            <?php endforeach; ?>
        </select>
            <div class="small mt-1" id="txFormItemIconPreview"></div>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold">金额 <span class="text-danger">*</span></label>
        <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm tx-amount-input" placeholder="0.00" value="<?= htmlspecialchars((string)$amount) ?>" required>
    </div>
                </div>
            </div>

            <!-- 账户 -->
            <div class="tx-form-section">
                <div class="tx-form-section-label">💰 账户</div>
                <div class="row g-3">
    <div class="col-12 col-md-6" data-role="from-account-group">
        <label class="form-label small fw-semibold">支出账户</label>
        <select name="from_account_id" class="form-select form-select-sm js-icon-select">
            <option value="">不选择</option>
            <?php foreach ($accounts as $a): ?>
                <?php
                $balance = (float)$a['current_balance'];
                $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                ?>
                <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                        data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                        data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>"
                        <?= (string)$fromAccountId === (string)$a['id'] ? 'selected' : '' ?>
                >
                    <span class="opt-g">[<?= htmlspecialchars($a['group_name']) ?>]</span><span class="opt-n"><?= htmlspecialchars($a['name']) ?></span><span class="opt-b">¥ <?= number_format($balance, 2) ?></span>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="small mt-1" id="txFormFromAccountIconPreview"></div>
    </div>
    <div class="col-12 col-md-6" data-role="to-account-group">
        <label class="form-label small fw-semibold">收入账户</label>
        <select name="to_account_id" class="form-select form-select-sm js-icon-select">
            <option value="">不选择</option>
            <?php foreach ($accounts as $a): ?>
                <?php
                $balance = (float)$a['current_balance'];
                $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                ?>
                <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                        data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                        data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>"
                        <?= (string)$toAccountId === (string)$a['id'] ? 'selected' : '' ?>
                >
                    <span class="opt-g">[<?= htmlspecialchars($a['group_name']) ?>]</span><span class="opt-n"><?= htmlspecialchars($a['name']) ?></span><span class="opt-b">¥ <?= number_format($balance, 2) ?></span>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="small mt-1" id="txFormToAccountIconPreview"></div>
    </div>
                </div>
            </div>

    <div class="col-12" id="txGoalSyncWrap" style="display:none;">
        <div class="alert alert-info small mb-0 py-2">
            <div class="mb-1">检测到所选账户已绑定目标，可选择将本次记账同步到目标（入账会增加完成金额，支出会扣减完成金额）。</div>

            <div id="txGoalInBlock" class="d-none">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="goal_in_sync" value="1" id="goalInSync" <?= $initialGoalInSync === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="goalInSync">同步入账到目标</label>
                </div>
                <div class="mt-1">
                    <select name="goal_in_id" class="form-select form-select-sm" id="goalInSelect">
                        <option value="0">请选择目标</option>
                    </select>
                </div>
            </div>

            <div id="txGoalOutBlock" class="d-none mt-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="goal_out_sync" value="1" id="goalOutSync" <?= $initialGoalOutSync === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="goalOutSync">同步支出/转出到目标（扣减）</label>
                </div>
                <div class="mt-1">
                    <select name="goal_out_id" class="form-select form-select-sm" id="goalOutSelect">
                        <option value="0">请选择目标</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

            <!-- 时间 · 备注 · 凭证 -->
            <div class="tx-form-section">
                <div class="tx-form-section-label">📅 时间 · 备注 · 凭证</div>
                <div class="row g-3">
    <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold">记账时间</label>
        <input type="datetime-local" name="trans_time" class="form-control form-control-sm" step="60" value="<?= htmlspecialchars($transTimeInput) ?>">
        <div class="form-text small mt-1">默认当前时间</div>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold">备注</label>
        <textarea name="remark" rows="2" class="form-control form-control-sm" placeholder="可选备注..."><?= htmlspecialchars((string)$remark) ?></textarea>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold">凭证</label>
        <input type="file" name="attachments[]" accept="image/*" multiple class="form-control form-control-sm">
        <div class="form-text small mt-1">最多 5 张，单张≤10MB</div>
        <?php
        $existingAttachments = [];
        if (!empty($tx['attachments']) && is_array($tx['attachments'])) {
            $existingAttachments = $tx['attachments'];
        } elseif (!empty($tx['attachment_path'])) {
            $existingAttachments = [$tx['attachment_path']];
        }
        $existingAttachments = array_values(array_filter(array_map('strval', $existingAttachments), static fn($p) => $p !== ''));
        ?>
        <?php if (!empty($existingAttachments)): ?>
            <div class="d-flex flex-wrap gap-1 mt-1">
                <?php foreach ($existingAttachments as $p): ?>
                    <a href="/uploads/<?= htmlspecialchars($p) ?>" target="_blank">
                        <img src="/uploads/<?= htmlspecialchars($p) ?>" alt="凭证" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid rgba(0,0,0,.12);">
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="removeAttachment">
                <label class="form-check-label small" for="removeAttachment">删除全部图片</label>
            </div>
        <?php endif; ?>
    </div>
                </div>
            </div>

    <?php if (!empty($reimbursementEnabled) && $reimbursementEnabled): ?>
            <div class="tx-form-section">
                <div class="tx-form-section-label">🧾 报销</div>
    <!-- 支出：需要报销 -->
    <div class="col-12" id="reimbExpenseOption" data-reimb-type="expense">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="needs_reimbursement" value="1" id="needsReimbursement"
                   <?= !empty($transaction['needs_reimbursement']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="needsReimbursement">需要报销</label>
            <div class="form-text small text-muted">勾选后，将创建报销记录，可在"报销情况"页面查看</div>
        </div>
    </div>
    <!-- 收入：抵消报销 -->
    <div class="col-12" id="reimbIncomeOption" data-reimb-type="income" style="display:none;">
        <label class="form-label small fw-semibold">抵消报销</label>
        <select name="reimb_id" class="form-select form-select-sm" id="reimbSelect">
            <option value="">不抵消</option>
            <?php foreach ($pendingReimbursements ?? [] as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>">
                    <?= htmlspecialchars($pr['title'] ?? '报销记录') ?>
                    - ¥<?= number_format((float)($pr['amount'] ?? 0), 2) ?>
                    (<?= htmlspecialchars($pr['category_name'] ?? '未分类') ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text small text-muted">选择一条待报销记录，将其标记为已报销</div>
        <?php if (empty($pendingReimbursements ?? [])): ?>
            <div class="form-text small text-muted" id="reimbEmptyHint">当前无待报销记录</div>
        <?php endif; ?>
    </div>
            </div>
    <?php endif; ?>

    <div class="d-flex justify-content-end pt-2">
        <button type="submit" class="btn btn-save-gradient">💾 保存</button>
    </div>
    </form>
        </div>
    </div>
</div>

<script>
// 类型药丸按钮同步
(function() {
    var pills = document.querySelectorAll('#txTypePills .tx-type-pill');
    var typeSelect = document.querySelector('select[name="type"]');
    if (!typeSelect) typeSelect = document.getElementById('txTypeSelect');
    pills.forEach(function(pill) {
        pill.addEventListener('click', function() {
            pills.forEach(function(p) { p.classList.remove('active'); });
            pill.classList.add('active');
            if (typeSelect) {
                typeSelect.value = pill.getAttribute('data-value');
                typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const goalsByAccount = <?= json_encode($goalsByAccount, JSON_UNESCAPED_UNICODE) ?>;
    const typeSelect = document.querySelector('select[name="type"]');
    const categorySelect = document.querySelector('select[name="category_id"]');
    const itemSelect = document.querySelector('select[name="item_id"]');
    const fromGroup = document.querySelector('[data-role="from-account-group"]');
    const toGroup = document.querySelector('[data-role="to-account-group"]');
    const categoryIconPreview = document.getElementById('txFormCategoryIconPreview');
    const itemIconPreview = document.getElementById('txFormItemIconPreview');
    const fromAccountIconPreview = document.getElementById('txFormFromAccountIconPreview');
    const toAccountIconPreview = document.getElementById('txFormToAccountIconPreview');

    const goalWrap = document.getElementById('txGoalSyncWrap');
    const goalInBlock = document.getElementById('txGoalInBlock');
    const goalOutBlock = document.getElementById('txGoalOutBlock');
    const goalInSelect = document.getElementById('goalInSelect');
    const goalOutSelect = document.getElementById('goalOutSelect');
    const goalInSync = document.getElementById('goalInSync');
    const goalOutSync = document.getElementById('goalOutSync');
    const initialGoalInId = <?= (int)$initialGoalInId ?>;
    const initialGoalOutId = <?= (int)$initialGoalOutId ?>;

    const initialType = '<?= htmlspecialchars($type, ENT_QUOTES) ?>';
    const initialCategoryId = '<?= htmlspecialchars((string)$categoryId, ENT_QUOTES) ?>';
    const initialItemId = '<?= htmlspecialchars((string)$itemId, ENT_QUOTES) ?>';

    function filterCategories() {
        const currentType = typeSelect.value;
        let hasSelected = false;
        Array.from(categorySelect.options).forEach(function (opt) {
            if (!opt.value) return;
            const t = opt.getAttribute('data-type');
            const match = !currentType || t === currentType;
            opt.hidden = !match;
            if (!match && opt.selected) {
                opt.selected = false;
            }
            if (match && !hasSelected && (opt.value === initialCategoryId || !initialCategoryId)) {
                hasSelected = true;
            }
        });
        if (categorySelect && categorySelect._decorateChoices) {
            categorySelect._decorateChoices();
        }
    }

    function filterItems() {
        const cid = categorySelect.value;
        Array.from(itemSelect.options).forEach(function (opt) {
            if (!opt.value) return;
            const c = opt.getAttribute('data-category');
            const match = !cid || c === cid;
            opt.hidden = !match;
            opt.disabled = !match;
            if (!match && opt.selected) {
                opt.selected = false;
            }
        });
        if (itemSelect && itemSelect._decorateChoices) {
            itemSelect._decorateChoices();
        }
    }

    function updateIconPreview(selectEl, previewEl) {
        if (!selectEl || !previewEl) return;
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) {
            previewEl.textContent = '';
            return;
        }
        const type = opt.getAttribute('data-icon-type') || '';
        const value = opt.getAttribute('data-icon-value') || '';
        if (!type || !value) {
            previewEl.textContent = '';
            return;
        }
        if (type === 'file') {
            previewEl.innerHTML = '<img src="/uploads/' + value + '" alt="图标" style="width:18px;height:18px;object-fit:cover;" class="rounded">';
        } else if (type === 'svg') {
            previewEl.innerHTML = '<span class="tx-icon d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">' + value + '</span>';
        } else {
            previewEl.textContent = '';
        }
    }

    function updateAccountVisibility() {
        const t = typeSelect.value;
        if (t === 'expense') {
            fromGroup.classList.remove('d-none');
            toGroup.classList.add('d-none');
            const toSelect = toGroup.querySelector('select');
            if (toSelect) toSelect.value = '';
        } else if (t === 'income') {
            fromGroup.classList.add('d-none');
            toGroup.classList.remove('d-none');
            const fromSelect = fromGroup.querySelector('select');
            if (fromSelect) fromSelect.value = '';
        } else {
            fromGroup.classList.remove('d-none');
            toGroup.classList.remove('d-none');
        }
    }

    function fillGoalOptions(selectEl, goals, selectedId) {
        if (!selectEl) return;
        const current = String(selectedId || '0');
        selectEl.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '0';
        opt0.textContent = (goals && goals.length) ? '请选择目标' : '无可用目标';
        selectEl.appendChild(opt0);

        (goals || []).forEach(function (g) {
            const opt = document.createElement('option');
            opt.value = String(g.id);
            opt.textContent = g.title || ('目标 #' + g.id);
            if (String(g.id) === current) {
                opt.selected = true;
            }
            selectEl.appendChild(opt);
        });
    }

    function updateGoalSyncUI() {
        if (!goalWrap || !goalInBlock || !goalOutBlock) return;
        const t = typeSelect ? typeSelect.value : 'expense';
        const fromAcc = fromAccountSelect ? parseInt(fromAccountSelect.value || '0', 10) : 0;
        const toAcc = toAccountSelect ? parseInt(toAccountSelect.value || '0', 10) : 0;
        const inGoals = toAcc > 0 ? (goalsByAccount[toAcc] || []) : [];
        const outGoals = fromAcc > 0 ? (goalsByAccount[fromAcc] || []) : [];

        let showIn = false;
        let showOut = false;
        if (t === 'income') {
            showIn = inGoals.length > 0;
        } else if (t === 'expense') {
            showOut = outGoals.length > 0;
        } else if (t === 'transfer') {
            showIn = inGoals.length > 0;
            showOut = outGoals.length > 0;
        }

        if (!showIn && !showOut) {
            goalWrap.style.display = 'none';
            goalInBlock.classList.add('d-none');
            goalOutBlock.classList.add('d-none');
            return;
        }

        goalWrap.style.display = '';

        if (showIn) {
            goalInBlock.classList.remove('d-none');
            var currentIn = goalInSelect ? parseInt(goalInSelect.value || '0', 10) : 0;
            fillGoalOptions(goalInSelect, inGoals, currentIn > 0 ? currentIn : initialGoalInId);
        } else {
            goalInBlock.classList.add('d-none');
            fillGoalOptions(goalInSelect, [], 0);
        }

        if (showOut) {
            goalOutBlock.classList.remove('d-none');
            var currentOut = goalOutSelect ? parseInt(goalOutSelect.value || '0', 10) : 0;
            fillGoalOptions(goalOutSelect, outGoals, currentOut > 0 ? currentOut : initialGoalOutId);
        } else {
            goalOutBlock.classList.add('d-none');
            fillGoalOptions(goalOutSelect, [], 0);
        }
    }

    if (goalInSelect && goalInSync) {
        goalInSelect.addEventListener('change', function () {
            var v = parseInt(goalInSelect.value || '0', 10);
            if (v > 0) {
                goalInSync.checked = true;
            }
        });
    }
    if (goalOutSelect && goalOutSync) {
        goalOutSelect.addEventListener('change', function () {
            var v = parseInt(goalOutSelect.value || '0', 10);
            if (v > 0) {
                goalOutSync.checked = true;
            }
        });
    }

    typeSelect.addEventListener('change', function () {
        filterCategories();
        categorySelect.value = '';
        filterItems();
        updateAccountVisibility();
        updateGoalSyncUI();
        updateIconPreview(categorySelect, categoryIconPreview);
        updateIconPreview(itemSelect, itemIconPreview);
        updateIconPreview(document.querySelector('select[name="from_account_id"]'), fromAccountIconPreview);
        updateIconPreview(document.querySelector('select[name="to_account_id"]'), toAccountIconPreview);
        
        // 切换报销区域
        var reimbExpense = document.getElementById('reimbExpenseOption');
        var reimbIncome = document.getElementById('reimbIncomeOption');
        if (reimbExpense) reimbExpense.style.display = (typeSelect.value === 'expense') ? '' : 'none';
        if (reimbIncome) reimbIncome.style.display = (typeSelect.value === 'income') ? '' : 'none';
    });

    categorySelect.addEventListener('change', function () {
        filterItems();
        updateIconPreview(categorySelect, categoryIconPreview);
        updateIconPreview(itemSelect, itemIconPreview);
    });

    itemSelect.addEventListener('change', function () {
        updateIconPreview(itemSelect, itemIconPreview);
    });

    const fromAccountSelect = document.querySelector('select[name="from_account_id"]');
    const toAccountSelect = document.querySelector('select[name="to_account_id"]');
    if (fromAccountSelect) {
        fromAccountSelect.addEventListener('change', function () {
            updateIconPreview(fromAccountSelect, fromAccountIconPreview);
            updateGoalSyncUI();
        });
    }
    if (toAccountSelect) {
        toAccountSelect.addEventListener('change', function () {
            updateIconPreview(toAccountSelect, toAccountIconPreview);
            updateGoalSyncUI();
        });
    }

    // 初始化
    filterCategories();
    filterItems();
    updateAccountVisibility();
    updateGoalSyncUI();
    updateIconPreview(categorySelect, categoryIconPreview);
    updateIconPreview(itemSelect, itemIconPreview);
    if (fromAccountSelect) updateIconPreview(fromAccountSelect, fromAccountIconPreview);
    if (toAccountSelect) updateIconPreview(toAccountSelect, toAccountIconPreview);

    // 使用 Choices.js 为带图标的下拉增强 UI，并在下拉项中显示图标
    if (window.Choices) {
        function enhanceIconSelect(select) {
            if (!select) return null;

            var instance = new Choices(select, {
                searchEnabled: true,
                shouldSort: false,
                position: 'bottom',
                itemSelectText: '',
                allowHTML: true,
            });

            select._choicesInstance = instance;

            function decorateChoices() {
                var root = select.closest('.choices');
                if (!root) return;
                var dropdown = root.querySelector('.choices__list--dropdown');
                if (!dropdown) return;
                var backing = instance.passedElement && instance.passedElement.element ? instance.passedElement.element : select;
                dropdown.querySelectorAll('.choices__item--choice[data-value]').forEach(function (choiceEl) {
                    var value = choiceEl.getAttribute('data-value');
                    if (!value) return;
                    var opt = backing.querySelector('option[value="' + value.replace(/"/g, '\"') + '"]');
                    if (!opt) return;
                    if (opt.hidden) {
                        choiceEl.style.display = 'none';
                    } else {
                        choiceEl.style.display = '';
                    }
                    var iconType = opt.getAttribute('data-icon-type') || '';
                    var iconValue = opt.getAttribute('data-icon-value') || '';
                    var label = opt.querySelector('.opt-g,.opt-n,.opt-b') ? opt.innerHTML : (choiceEl.textContent || '');
                    var iconHtml = '';
                    if (iconType && iconValue) {
                        if (iconType === 'file') {
                            iconHtml = '<img src="/uploads/' + iconValue + '" alt="图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">';
                        } else if (iconType === 'svg') {
                            iconHtml = '<span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">' + iconValue + '</span>';
                        }
                    }
                    choiceEl.innerHTML = iconHtml + label;
                });
            }

            select._decorateChoices = decorateChoices;

            decorateChoices();

            select.addEventListener('showDropdown', function () {
                decorateChoices();
            });

            return instance;
        }

        document.querySelectorAll('select.js-icon-select').forEach(function (el) {
            enhanceIconSelect(el);
        });
    }
});
</script>

<?php if ($mode === 'create'): ?>
    <div class="card glass-card">
        <div class="card-body p-4">
            <h3 class="h6 mb-3">📋 今日记账明细</h3>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>类型</th>
                        <th>分类</th>
                        <th>项目</th>
                        <th>账户</th>
                        <th class="text-end">金额</th>
                        <th>时间</th>
                        <th>备注</th>
                        <th>凭证</th>
                        <th class="text-center">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($todayTransactions)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted small">今日暂无记账记录</td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($todayTransactions as $t): ?>
                        <tr>
                            <td>
                                <?php if ($t['type'] === 'income'): ?>
                                    <span class="text-danger">收入</span>
                                <?php elseif ($t['type'] === 'expense'): ?>
                                    <span class="text-success">支出</span>
                                <?php else: ?>
                                    <span class="text-secondary">转账</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($t['category_icon_type']) && !empty($t['category_icon_value'])): ?>
                                    <?php if ($t['category_icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($t['category_icon_value'], ENT_QUOTES) ?>" alt="分类图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                    <?php elseif ($t['category_icon_type'] === 'svg'): ?>
                                        <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                            <?= $t['category_icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($t['category_name'] ?? '') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($t['item_icon_type']) && !empty($t['item_icon_value'])): ?>
                                    <?php if ($t['item_icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($t['item_icon_value'], ENT_QUOTES) ?>" alt="项目图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                    <?php elseif ($t['item_icon_type'] === 'svg'): ?>
                                        <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                            <?= $t['item_icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($t['item_name'] ?? '') ?></span>
                            </td>
                            <td>
                                <?php if ($t['type'] === 'transfer'): ?>
                                    <?php if (!empty($t['from_account_icon_type']) && !empty($t['from_account_icon_value'])): ?>
                                        <?php if ($t['from_account_icon_type'] === 'file'): ?>
                                            <img src="/uploads/<?= htmlspecialchars($t['from_account_icon_value'], ENT_QUOTES) ?>" alt="账户图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                        <?php elseif ($t['from_account_icon_type'] === 'svg'): ?>
                                            <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                                <?= $t['from_account_icon_value'] ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($t['from_account_name'] ?? '') ?> → <?= htmlspecialchars($t['to_account_name'] ?? '') ?></span>
                                <?php elseif ($t['type'] === 'expense'): ?>
                                    <?php if (!empty($t['from_account_icon_type']) && !empty($t['from_account_icon_value'])): ?>
                                        <?php if ($t['from_account_icon_type'] === 'file'): ?>
                                            <img src="/uploads/<?= htmlspecialchars($t['from_account_icon_value'], ENT_QUOTES) ?>" alt="账户图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                        <?php elseif ($t['from_account_icon_type'] === 'svg'): ?>
                                            <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                                <?= $t['from_account_icon_value'] ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($t['from_account_name'] ?? '') ?></span>
                                <?php else: ?>
                                    <?php if (!empty($t['to_account_icon_type']) && !empty($t['to_account_icon_value'])): ?>
                                        <?php if ($t['to_account_icon_type'] === 'file'): ?>
                                            <img src="/uploads/<?= htmlspecialchars($t['to_account_icon_value'], ENT_QUOTES) ?>" alt="账户图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                        <?php elseif ($t['to_account_icon_type'] === 'svg'): ?>
                                            <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                                <?= $t['to_account_icon_value'] ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($t['to_account_name'] ?? '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">¥ <?= number_format($t['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($t['trans_time']) ?></td>
                            <td><?= htmlspecialchars($t['remark'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($t['attachment_path'])): ?>
                                    <img src="/uploads/<?= htmlspecialchars($t['attachment_path'], ENT_QUOTES) ?>"
                                         alt="凭证"
                                         class="attachment-thumb"
                                         style="max-width:60px;max-height:60px;object-fit:cover;border-radius:4px;cursor:zoom-in;"
                                         data-attachment-preview="/uploads/<?= htmlspecialchars($t['attachment_path'], ENT_QUOTES) ?>">
                                <?php else: ?>
                                    <span class="text-muted small">无</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#txEditModal"
                                        data-id="<?= (int)$t['id'] ?>"
                                        data-type="<?= htmlspecialchars($t['type'], ENT_QUOTES) ?>"
                                        data-category-id="<?= (int)($t['category_id'] ?? 0) ?>"
                                        data-item-id="<?= (int)($t['item_id'] ?? 0) ?>"
                                        data-from-account-id="<?= (int)($t['from_account_id'] ?? 0) ?>"
                                        data-to-account-id="<?= (int)($t['to_account_id'] ?? 0) ?>"
                                        data-amount="<?= htmlspecialchars((string)$t['amount'], ENT_QUOTES) ?>"
                                        data-trans-time="<?= htmlspecialchars($t['trans_time'], ENT_QUOTES) ?>"
                                        data-remark="<?= htmlspecialchars($t['remark'] ?? '', ENT_QUOTES) ?>"
                                        data-attachment-path="<?= htmlspecialchars($t['attachment_path'] ?? '', ENT_QUOTES) ?>">
                                    编辑
                                </button>
                                <form method="post" action="/public/index.php?route=transaction-delete" class="d-inline" onsubmit="return confirm('确定删除该记录吗？删除后将同步回滚账户余额。');">
                                    <input type="hidden" name="ids[]" value="<?= (int)$t['id'] ?>">
                                    <input type="hidden" name="from" value="create">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

        <!-- 编辑记账弹窗（用于今日记账明细） -->
        <div class="modal fade" id="txEditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑今日记账</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="txEditForm" method="post" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">类型</label>
                                    <select name="type" class="form-select form-select-sm js-icon-select" id="txEditType">
                                        <option value="expense">支出</option>
                                        <option value="income">收入</option>
                                        <?php if (!empty($transferEnabled)): ?>
                                            <option value="transfer">转账</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">分类</label>
                                    <select name="category_id" class="form-select form-select-sm tx-select tx-category-select js-icon-select" id="txEditCategory">
                                        <option value="">请选择</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= (int)$c['id'] ?>"
                                                    data-type="<?= htmlspecialchars($c['type']) ?>"
                                                    data-icon-type="<?= htmlspecialchars($c['icon_type'] ?? '', ENT_QUOTES) ?>"
                                                    data-icon-value="<?= htmlspecialchars($c['icon_value'] ?? '', ENT_QUOTES) ?>">
                                                <?php
                                                $typeLabel = '收入';
                                                if ($c['type'] === 'expense') {
                                                    $typeLabel = '支出';
                                                } elseif ($c['type'] === 'transfer') {
                                                    $typeLabel = '转账';
                                                }
                                                ?>
                                                <?= htmlspecialchars('[' . $typeLabel . '] ' . $c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small mt-1" id="txEditCategoryIconPreview"></div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">项目</label>
                                    <select name="item_id" class="form-select form-select-sm js-icon-select" id="txEditItem">
                                        <option value="">不选项目</option>
                                        <?php foreach ($items as $i): ?>
                                            <option value="<?= (int)$i['id'] ?>"
                                                    data-category="<?= (int)$i['category_id'] ?>"
                                                    data-icon-type="<?= htmlspecialchars($i['icon_type'] ?? '', ENT_QUOTES) ?>"
                                                    data-icon-value="<?= htmlspecialchars($i['icon_value'] ?? '', ENT_QUOTES) ?>">
                                                <?= htmlspecialchars($i['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small mt-1" id="txEditItemIconPreview"></div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">金额</label>
                                    <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm tx-amount-input" id="txEditAmount">
                                </div>

                                <div class="col-12 col-md-4" data-role="tx-edit-from-account-group">
                                    <label class="form-label small">支出账户（支出/转出）</label>
                                    <select name="from_account_id" class="form-select form-select-sm js-icon-select" id="txEditFromAccount">
                                        <option value="">不选择</option>
                                        <?php foreach ($accounts as $a): ?>
                                            <?php
                                            $balance = (float)$a['current_balance'];
                                            $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                                            ?>
                                            <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                                                    data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                                                    data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>">
                                                <span class="opt-g">[<?= htmlspecialchars($a['group_name']) ?>]</span><span class="opt-n"><?= htmlspecialchars($a['name']) ?></span><span class="opt-b">¥ <?= number_format($balance, 2) ?></span>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small mt-1" id="txEditFromAccountIconPreview"></div>
                                </div>
                                <div class="col-12 col-md-4" data-role="tx-edit-to-account-group">
                                    <label class="form-label small">收入账户（收入/转入）</label>
                                    <select name="to_account_id" class="form-select form-select-sm js-icon-select" id="txEditToAccount">
                                        <option value="">不选择</option>
                                        <?php foreach ($accounts as $a): ?>
                                            <?php
                                            $balance = (float)$a['current_balance'];
                                            $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                                            ?>
                                            <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                                                    data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                                                    data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>">
                                                <span class="opt-g">[<?= htmlspecialchars($a['group_name']) ?>]</span><span class="opt-n"><?= htmlspecialchars($a['name']) ?></span><span class="opt-b">¥ <?= number_format($balance, 2) ?></span>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small mt-1" id="txEditToAccountIconPreview"></div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small">记账时间</label>
                                    <input type="datetime-local" name="trans_time" class="form-control form-control-sm" id="txEditTransTime" step="60">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small">备注</label>
                                    <textarea name="remark" rows="2" class="form-control form-control-sm" id="txEditRemark"></textarea>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small">图片凭证（≤10MB）</label>
                                    <input type="file" name="attachment" accept="image/*" class="form-control form-control-sm" id="txEditAttachmentInput">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="txEditRemoveAttachment">
                                        <label class="form-check-label small" for="txEditRemoveAttachment">删除当前图片</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 d-flex align-items-end" id="txEditAttachmentPreviewWrapper" style="min-height:2.5rem;">
                                    <div class="small text-muted" id="txEditAttachmentPlaceholder">当前无凭证</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-primary">保存修改</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('txEditModal');
            if (!modalEl) return;

            if (modalEl.parentNode !== document.body) {
                document.body.appendChild(modalEl);
            }

            modalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;

                var id = button.getAttribute('data-id');
                var type = button.getAttribute('data-type') || 'expense';
                var categoryId = button.getAttribute('data-category-id') || '';
                var itemId = button.getAttribute('data-item-id') || '';
                var fromAccountId = button.getAttribute('data-from-account-id') || '';
                var toAccountId = button.getAttribute('data-to-account-id') || '';
                var amount = button.getAttribute('data-amount') || '';
                var transTime = button.getAttribute('data-trans-time') || '';
                var remark = button.getAttribute('data-remark') || '';
                var attachmentPath = button.getAttribute('data-attachment-path') || '';

                var form = document.getElementById('txEditForm');
                if (form && id) {
                    form.action = '/public/index.php?route=transaction-edit&id=' + encodeURIComponent(id) + '&from=create';
                }

                var typeSelect = document.getElementById('txEditType');
                var categorySelect = document.getElementById('txEditCategory');
                var itemSelect = document.getElementById('txEditItem');
                var fromSelect = document.getElementById('txEditFromAccount');
                var toSelect = document.getElementById('txEditToAccount');
                var amountInput = document.getElementById('txEditAmount');
                var timeInput = document.getElementById('txEditTransTime');
                var remarkInput = document.getElementById('txEditRemark');
                var fromGroup = modalEl.querySelector('[data-role="tx-edit-from-account-group"]');
                var toGroup = modalEl.querySelector('[data-role="tx-edit-to-account-group"]');
                var attachmentWrapper = document.getElementById('txEditAttachmentPreviewWrapper');
                var attachmentPlaceholder = document.getElementById('txEditAttachmentPlaceholder');
                var removeAttachmentCheckbox = document.getElementById('txEditRemoveAttachment');
                var categoryIconPreview = document.getElementById('txEditCategoryIconPreview');
                var itemIconPreview = document.getElementById('txEditItemIconPreview');
                var fromAccountIconPreview = document.getElementById('txEditFromAccountIconPreview');
                var toAccountIconPreview = document.getElementById('txEditToAccountIconPreview');

                if (typeSelect) {
                    typeSelect.value = type;
                    if (typeSelect._choicesInstance && typeof typeSelect._choicesInstance.setChoiceByValue === 'function') {
                        typeSelect._choicesInstance.setChoiceByValue(type);
                    }
                }
                if (categorySelect) {
                    categorySelect.value = categoryId;
                    if (categorySelect._choicesInstance && typeof categorySelect._choicesInstance.setChoiceByValue === 'function') {
                        categorySelect._choicesInstance.setChoiceByValue(categoryId);
                    }
                }
                if (itemSelect) {
                    itemSelect.value = itemId;
                    if (itemSelect._choicesInstance && typeof itemSelect._choicesInstance.setChoiceByValue === 'function') {
                        itemSelect._choicesInstance.setChoiceByValue(itemId);
                    }
                }
                if (fromSelect) {
                    fromSelect.value = fromAccountId;
                    if (fromSelect._choicesInstance && typeof fromSelect._choicesInstance.setChoiceByValue === 'function') {
                        fromSelect._choicesInstance.setChoiceByValue(fromAccountId);
                    }
                }
                if (toSelect) {
                    toSelect.value = toAccountId;
                    if (toSelect._choicesInstance && typeof toSelect._choicesInstance.setChoiceByValue === 'function') {
                        toSelect._choicesInstance.setChoiceByValue(toAccountId);
                    }
                }
                if (amountInput) amountInput.value = amount;
                if (timeInput) {
                    if (transTime && transTime.indexOf('T') === -1) {
                        timeInput.value = transTime.replace(' ', 'T');
                    } else {
                        timeInput.value = transTime;
                    }
                }
                if (remarkInput) remarkInput.value = remark;

                function updateIconPreview(selectEl, previewEl) {
                    if (!selectEl || !previewEl) return;
                    var opt = selectEl.options[selectEl.selectedIndex];
                    if (!opt || !opt.value) {
                        previewEl.textContent = '';
                        return;
                    }
                    var itype = opt.getAttribute('data-icon-type') || '';
                    var ivalue = opt.getAttribute('data-icon-value') || '';
                    if (!itype || !ivalue) {
                        previewEl.textContent = '';
                        return;
                    }
                    if (itype === 'file') {
                        previewEl.innerHTML = '<img src="/uploads/' + ivalue + '" alt="图标" style="width:18px;height:18px;object-fit:cover;" class="rounded">';
                    } else if (itype === 'svg') {
                        previewEl.innerHTML = '<span class="tx-icon d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">' + ivalue + '</span>';
                    } else {
                        previewEl.textContent = '';
                    }
                }

                // 处理当前凭证预览
                if (attachmentWrapper && attachmentPlaceholder) {
                    attachmentWrapper.innerHTML = '';
                    if (attachmentPath) {
                        var img = document.createElement('img');
                        img.src = '/uploads/' + attachmentPath;
                        img.alt = '凭证';
                        img.className = 'attachment-thumb';
                        img.setAttribute('data-attachment-preview', '/uploads/' + attachmentPath);
                        attachmentWrapper.appendChild(img);
                    } else {
                        attachmentWrapper.appendChild(attachmentPlaceholder);
                        attachmentPlaceholder.textContent = '当前无凭证';
                    }
                }

                if (removeAttachmentCheckbox) {
                    removeAttachmentCheckbox.checked = false;
                    removeAttachmentCheckbox.disabled = !attachmentPath;
                }
                function updateAccountVisibility() {
                    var t = typeSelect ? typeSelect.value : 'expense';
                    if (!fromGroup || !toGroup) return;
                    if (t === 'expense') {
                        fromGroup.classList.remove('d-none');
                        toGroup.classList.add('d-none');
                        if (toSelect) toSelect.value = '';
                    } else if (t === 'income') {
                        fromGroup.classList.add('d-none');
                        toGroup.classList.remove('d-none');
                        if (fromSelect) fromSelect.value = '';
                    } else {
                        fromGroup.classList.remove('d-none');
                        toGroup.classList.remove('d-none');
                    }
                }

                if (typeSelect) {
                    typeSelect.onchange = updateAccountVisibility;
                }
                updateAccountVisibility();

                if (categorySelect && itemSelect) {
                    var allItemOptions = Array.prototype.slice.call(itemSelect.querySelectorAll('option'));
                    function filterItems() {
                        var cid = categorySelect.value;
                        allItemOptions.forEach(function (opt) {
                            if (!opt.value) return;
                            var c = opt.getAttribute('data-category');
                            var match = !cid || c === cid;
                            opt.hidden = !match;
                            opt.disabled = !match;
                        });
                            if (itemSelect && itemSelect._decorateChoices) {
                                itemSelect._decorateChoices();
                            }
                    }
                    categorySelect.onchange = function () {
                        filterItems();
                        updateIconPreview(categorySelect, categoryIconPreview);
                        updateIconPreview(itemSelect, itemIconPreview);
                    };
                    filterItems();
                }

                if (itemSelect) {
                    itemSelect.onchange = function () {
                        updateIconPreview(itemSelect, itemIconPreview);
                    };
                }

                if (fromSelect) {
                    fromSelect.onchange = function () {
                        updateIconPreview(fromSelect, fromAccountIconPreview);
                    };
                }

                if (toSelect) {
                    toSelect.onchange = function () {
                        updateIconPreview(toSelect, toAccountIconPreview);
                    };
                }

                // 初始化图标预览
                updateIconPreview(categorySelect, categoryIconPreview);
                updateIconPreview(itemSelect, itemIconPreview);
                updateIconPreview(fromSelect, fromAccountIconPreview);
                updateIconPreview(toSelect, toAccountIconPreview);
            });
        });
        </script>
