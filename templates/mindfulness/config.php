<?php
/** @var array $config */
/** @var array $aiQuota */
/** @var array $pricingPlans */
/** @var bool $isAdmin */

$remaining = max(0, (int)$aiQuota['system_quota'] - (int)$aiQuota['system_used']) + max(0, (int)$aiQuota['purchased_quota'] - (int)$aiQuota['purchased_used']);
?>
<style>
.mf-config-wrap { max-width: 800px; margin: 0 auto; }
.mf-config-card {
    border: 1px solid rgba(0,0,0,0.06); border-radius: 12px; padding: 20px;
    margin-bottom: 16px; background: rgba(255,255,255,0.5);
    backdrop-filter: blur(10px);
}
body.theme-dark .mf-config-card { background: rgba(30,41,59,0.4); border-color: rgba(148,163,184,0.15); }
.mf-item-row {
    display: flex; align-items: center; gap: 8px; margin-bottom: 6px;
}
.mf-item-row input[type="text"] { flex: 1; }
.mf-item-row input[type="number"] { width: 80px; }
.mf-plan-card {
    border: 1px solid rgba(102,126,234,0.2); border-radius: 10px; padding: 16px;
    text-align: center; transition: all 0.2s;
}
.mf-plan-card:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102,126,234,0.15); }
.mf-plan-price { font-size: 1.5rem; font-weight: 700; color: #667eea; }
.mf-plan-original { text-decoration: line-through; color: #999; font-size: 0.85rem; }
.mf-plan-quota { font-size: 0.85rem; color: #666; margin-top: 4px; }
</style>

<div class="mf-config-wrap">
    <h5 class="mb-3">⚙️ 正念配置</h5>

    <form id="configForm" onsubmit="saveConfig(); return false;">
        <div class="mf-config-card">
            <h6 class="fw-semibold mb-3">📊 分数设置</h6>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label small">初始分数</label>
                    <input type="number" name="initial_score" class="form-control form-control-sm" value="<?= (float)$config['initial_score'] ?>" min="0" max="100" step="1">
                </div>
                <div class="col-6">
                    <label class="form-label small">签到一次 +</label>
                    <input type="number" name="checkin_score" class="form-control form-control-sm" value="<?= (float)$config['checkin_score'] ?>" min="0" max="10" step="0.1">
                </div>
            </div>
        </div>

        <div class="mf-config-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0">✅ 正念项目</h6>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="addItem('positive')">+ 添加</button>
            </div>
            <div id="positiveItems">
                <?php $pi = $config['positive_items'] ?? []; foreach ($pi as $name => $score): ?>
                <div class="mf-item-row">
                    <input type="text" name="pi_name[]" class="form-control form-control-sm" value="<?= htmlspecialchars($name) ?>" placeholder="项目名称">
                    <input type="number" name="pi_score[]" class="form-control form-control-sm" value="<?= (float)$score ?>" step="0.1">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.parentElement.remove()">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mf-config-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0">❌ 负念项目</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="addItem('negative')">+ 添加</button>
            </div>
            <div id="negativeItems">
                <?php $ni = $config['negative_items'] ?? []; foreach ($ni as $name => $score): ?>
                <div class="mf-item-row">
                    <input type="text" name="ni_name[]" class="form-control form-control-sm" value="<?= htmlspecialchars($name) ?>" placeholder="项目名称">
                    <input type="number" name="ni_score[]" class="form-control form-control-sm" value="<?= (float)$score ?>" step="0.1">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.parentElement.remove()">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mf-config-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0">🏆 连续签到奖励</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBonusRule()">+ 添加</button>
            </div>
            <div id="bonusRules">
                <?php $br = $config['bonus_rules'] ?? []; foreach ($br as $rule): ?>
                <div class="mf-item-row">
                    <span class="small text-nowrap">连续</span>
                    <input type="number" name="br_days[]" class="form-control form-control-sm" value="<?= (int)$rule['days'] ?>" min="1" style="width:60px">
                    <span class="small text-nowrap">日 +</span>
                    <input type="number" name="br_bonus[]" class="form-control form-control-sm" value="<?= (float)$rule['bonus'] ?>" step="0.1" style="width:80px">
                    <span class="small text-nowrap">分</span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.parentElement.remove()">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mf-config-card">
            <h6 class="fw-semibold mb-3">🤖 树洞AI配置</h6>
            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="ai_mode" id="aiModeSystem" value="system" <?= ($config['ai_mode'] ?? 'system') === 'system' ? 'checked' : '' ?> onchange="toggleAiMode()">
                    <label class="form-check-label small" for="aiModeSystem">使用系统配置</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="ai_mode" id="aiModeCustom" value="custom" <?= ($config['ai_mode'] ?? 'system') === 'custom' ? 'checked' : '' ?> onchange="toggleAiMode()">
                    <label class="form-check-label small" for="aiModeCustom">自定义配置</label>
                </div>
            </div>

            <?php if (($config['ai_mode'] ?? 'system') === 'system'): ?>
            <div class="small text-muted mb-2">
                系统配置模式下，每次使用树洞AI消耗1次。当前剩余：<strong><?= $remaining ?></strong> 次
            </div>
            <?php endif; ?>

            <div id="customAiConfig" style="display:<?= ($config['ai_mode'] ?? 'system') === 'custom' ? 'block' : 'none' ?>">
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <label class="form-label small">API地址</label>
                        <input type="text" name="custom_api_url" class="form-control form-control-sm" value="<?= htmlspecialchars($config['custom_api_url'] ?? '') ?>" placeholder="https://api.openai.com/v1/chat/completions">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">API Key</label>
                        <input type="password" name="custom_api_key" class="form-control form-control-sm" value="<?= htmlspecialchars($config['custom_api_key'] ?? '') ?>" placeholder="sk-...">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">模型</label>
                        <input type="text" name="custom_model" class="form-control form-control-sm" value="<?= htmlspecialchars($config['custom_model'] ?? '') ?>" placeholder="gpt-3.5-turbo">
                    </div>
                </div>
                <div class="small text-muted">自定义配置不消耗系统次数，请自行确保API可用性。</div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary" id="btnSaveConfig">保存配置</button>
        </div>
    </form>

    <?php if (!empty($pricingPlans)): ?>
    <div class="mf-config-card mt-4">
        <h6 class="fw-semibold mb-3">💰 AI次数套餐</h6>
        <div class="row g-3">
            <?php foreach ($pricingPlans as $plan): ?>
            <div class="col-md-4">
                <div class="mf-plan-card">
                    <div class="fw-semibold mb-1"><?= htmlspecialchars($plan['name']) ?></div>
                    <div class="mf-plan-quota"><?= (int)$plan['quota'] ?> 次</div>
                    <div class="mt-2">
                        <span class="mf-plan-price">¥<?= number_format((float)$plan['price'], 2) ?></span>
                        <?php if ((float)$plan['original_price'] > (float)$plan['price']): ?>
                        <span class="mf-plan-original ms-1">¥<?= number_format((float)$plan['original_price'], 2) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <div class="small text-muted">购买请联系管理员，或在个人中心购买</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleAiMode() {
    var isCustom = document.getElementById('aiModeCustom').checked;
    document.getElementById('customAiConfig').style.display = isCustom ? 'block' : 'none';
}

function addItem(type) {
    var container = document.getElementById(type + 'Items');
    var prefix = type === 'positive' ? 'pi' : 'ni';
    var div = document.createElement('div');
    div.className = 'mf-item-row';
    div.innerHTML = '<input type="text" name="' + prefix + '_name[]" class="form-control form-control-sm" placeholder="项目名称">' +
        '<input type="number" name="' + prefix + '_score[]" class="form-control form-control-sm" value="0" step="0.1">' +
        '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.parentElement.remove()">✕</button>';
    container.appendChild(div);
}

function addBonusRule() {
    var container = document.getElementById('bonusRules');
    var div = document.createElement('div');
    div.className = 'mf-item-row';
    div.innerHTML = '<span class="small text-nowrap">连续</span>' +
        '<input type="number" name="br_days[]" class="form-control form-control-sm" value="7" min="1" style="width:60px">' +
        '<span class="small text-nowrap">日 +</span>' +
        '<input type="number" name="br_bonus[]" class="form-control form-control-sm" value="0.5" step="0.1" style="width:80px">' +
        '<span class="small text-nowrap">分</span>' +
        '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.parentElement.remove()">✕</button>';
    container.appendChild(div);
}

function saveConfig() {
    var btn = document.getElementById('btnSaveConfig');
    btn.disabled = true;
    btn.textContent = '保存中...';

    var form = document.getElementById('configForm');
    var fd = new FormData(form);
    fd.append('action', 'save_config');

    fetch('/public/index.php?route=mindfulness-api', {
        method: 'POST',
        body: new URLSearchParams(fd)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        btn.disabled = false;
        btn.textContent = '保存配置';
        if (d.ok) {
            showToast(d.message, 'success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showToast(d.error || '保存失败', 'error');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = '保存配置';
        showToast('请求失败', 'error');
    });
}

function showToast(msg, type) {
    var bg = type === 'error' ? '#dc3545' : (type === 'success' ? '#198754' : '#333');
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;z-index:9999;pointer-events:none;';
    var box = document.createElement('div');
    box.style.cssText = 'background:' + bg + ';color:#fff;padding:14px 28px;border-radius:10px;font-size:14px;box-shadow:0 4px 20px rgba(0,0,0,0.3);pointer-events:auto;opacity:0;transform:scale(0.8);transition:all 0.2s;text-align:center;max-width:80vw;';
    box.textContent = msg;
    overlay.appendChild(box);
    document.body.appendChild(overlay);
    requestAnimationFrame(function() { box.style.opacity = '1'; box.style.transform = 'scale(1)'; });
    setTimeout(function() {
        box.style.opacity = '0'; box.style.transform = 'scale(0.8)';
        setTimeout(function() { overlay.remove(); }, 200);
    }, 2000);
}
</script>
