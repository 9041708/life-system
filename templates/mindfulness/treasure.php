<?php
/** @var array $config */
/** @var array $treasures */
/** @var array $aiQuota */
/** @var float $currentScore */
/** @var bool $isAdmin */
/** @var int $page */
/** @var int $totalPages */
/** @var int $total */

$remaining = max(0, (int)$aiQuota['system_quota'] - (int)$aiQuota['system_used']) + max(0, (int)$aiQuota['purchased_quota'] - (int)$aiQuota['purchased_used']);
$aiMode = $config['ai_mode'] ?? 'system';
?>
<style>
.mf-treasure-wrap { max-width: 700px; margin: 0 auto; }
.mf-treasure-item {
    border: 1px solid rgba(0,0,0,0.06); border-radius: 12px; padding: 16px;
    margin-bottom: 12px; background: rgba(255,255,255,0.5);
    backdrop-filter: blur(10px); transition: all 0.2s;
}
body.theme-dark .mf-treasure-item { background: rgba(30,41,59,0.4); border-color: rgba(148,163,184,0.15); }
.mf-treasure-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.mf-treasure-content { font-size: 0.9rem; line-height: 1.6; margin-bottom: 10px; white-space: pre-wrap; }
.mf-treasure-reply {
    font-size: 0.85rem; color: #555; padding: 10px 14px; border-radius: 8px;
    background: rgba(102,126,234,0.06); border-left: 3px solid #667eea; margin-bottom: 8px;
}
body.theme-dark .mf-treasure-reply { background: rgba(102,126,234,0.1); color: #b8c5d6; }
.mf-treasure-meta { font-size: 0.75rem; color: #999; display: flex; align-items: center; justify-content: space-between; }
.mf-treasure-sentiment { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 0.7rem; }
.mf-treasure-sentiment.positive { background: rgba(34,197,94,0.15); color: #16a34a; }
.mf-treasure-sentiment.negative { background: rgba(239,68,68,0.15); color: #dc2626; }
.mf-treasure-sentiment.neutral { background: rgba(148,163,184,0.15); color: #6b7280; }
.mf-add-wrap {
    border: 2px dashed rgba(102,126,234,0.3); border-radius: 12px; padding: 20px;
    text-align: center; cursor: pointer; transition: all 0.2s; margin-bottom: 16px;
}
.mf-add-wrap:hover { border-color: #667eea; background: rgba(102,126,234,0.04); }
.mf-compose-area { display: none; }
.mf-compose-area.active { display: block; }
.mf-compose-textarea {
    width: 100%; min-height: 120px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;
    padding: 12px; font-size: 0.9rem; resize: vertical; background: rgba(255,255,255,0.8);
}
body.theme-dark .mf-compose-textarea { background: rgba(30,41,59,0.6); border-color: rgba(148,163,184,0.2); color: #e2e8f0; }
.mf-compose-textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
.mf-warning-banner {
    background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); border-radius: 8px;
    padding: 10px 14px; font-size: 0.85rem; color: #b45309; margin-bottom: 12px;
}
body.theme-dark .mf-warning-banner { color: #fbbf24; background: rgba(245,158,11,0.08); }
</style>

<div class="mf-treasure-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">🕳️ 正念树洞</h5>
        <span class="small text-muted">分数：<strong><?= number_format($currentScore, 1) ?></strong></span>
    </div>

    <?php if ($aiMode === 'system'): ?>
    <div class="d-flex justify-content-between align-items-center mb-2 small">
        <span class="text-muted">AI剩余次数：<strong><?= $remaining ?></strong></span>
        <?php if ($remaining <= 0): ?>
            <a href="/public/index.php?route=mindfulness-config" class="btn btn-sm btn-outline-primary py-0">购买套餐</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="mf-add-wrap" id="addWrap" onclick="showCompose()">
        <div style="font-size:2rem;margin-bottom:4px">💭</div>
        <div class="text-muted small">点击这里，说出你的心事...</div>
    </div>

    <div class="mf-compose-area" id="composeArea">
        <textarea class="mf-compose-textarea" id="composeText" placeholder="在这里写下你的心事，AI会温暖回应你..."></textarea>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="hideCompose()">取消</button>
            <button class="btn btn-sm btn-primary" id="btnSubmit" onclick="submitTreasure()">提交心事</button>
        </div>
    </div>

    <div id="warningArea"></div>

    <div class="mt-3" id="treasureList">
        <?php if (empty($treasures)): ?>
            <div class="text-center text-muted py-4">
                <div style="font-size:3rem;margin-bottom:8px">🌙</div>
                <div>还没有心事记录</div>
                <div class="small">点击上方开始倾诉</div>
            </div>
        <?php else: ?>
            <?php foreach ($treasures as $t): ?>
            <div class="mf-treasure-item" id="treasure-<?= (int)$t['id'] ?>">
                <div class="mf-treasure-content"><?= nl2br(htmlspecialchars($t['content'])) ?></div>
                <?php if (!empty($t['ai_reply'])): ?>
                <div class="mf-treasure-reply">🤖 <?= htmlspecialchars($t['ai_reply']) ?></div>
                <?php endif; ?>
                <div class="mf-treasure-meta">
                    <div>
                        <?php if ($t['sentiment'] === 'positive'): ?>
                            <span class="mf-treasure-sentiment positive">正念</span>
                        <?php elseif ($t['sentiment'] === 'negative'): ?>
                            <span class="mf-treasure-sentiment negative">负念</span>
                        <?php else: ?>
                            <span class="mf-treasure-sentiment neutral">中性</span>
                        <?php endif; ?>
                        <?php if ($t['score_change'] != 0): ?>
                        <span class="<?= $t['score_change'] > 0 ? 'text-success' : 'text-danger' ?> ms-1">
                            <?= $t['score_change'] > 0 ? '+' : '' ?><?= (float)$t['score_change'] ?>分
                        </span>
                        <?php endif; ?>
                        <span class="ms-2"><?= htmlspecialchars(substr($t['created_at'], 0, 16)) ?></span>
                    </div>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.7rem" onclick="deleteTreasure(<?= (int)$t['id'] ?>)">删除</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="small text-muted">共 <?= $total ?> 条</div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?route=mindfulness-treasure&page=<?= $page - 1 ?>">上一页</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($totalPages > 7 && $p > 3 && $p < $totalPages - 2 && abs($p - $page) > 1): ?>
                        <?php if ($p === 4): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?route=mindfulness-treasure&page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?route=mindfulness-treasure&page=<?= $page + 1 ?>">下一页</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function showCompose() {
    document.getElementById('addWrap').style.display = 'none';
    document.getElementById('composeArea').classList.add('active');
    document.getElementById('composeText').focus();
}

function hideCompose() {
    document.getElementById('addWrap').style.display = '';
    document.getElementById('composeArea').classList.remove('active');
    document.getElementById('composeText').value = '';
}

function submitTreasure() {
    var content = document.getElementById('composeText').value.trim();
    if (!content) { alert('请输入心事内容'); return; }

    var btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.textContent = 'AI思考中...';

    fetch('/public/index.php?route=mindfulness-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=create_treasure&content=' + encodeURIComponent(content)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        btn.disabled = false;
        btn.textContent = '提交心事';
        if (d.ok) {
            showToast(d.message, 'success');
            if (d.warning) {
                document.getElementById('warningArea').innerHTML = '<div class="mf-warning-banner mt-2">💛 ' + h(d.warning) + '</div>';
            }
            hideCompose();
            setTimeout(function() { location.reload(); }, 800);
        } else {
            if (d.quota_exhausted) {
                if (confirm(d.error + '\n\n是否前往配置页面？')) {
                    location.href = '/public/index.php?route=mindfulness-config';
                }
            } else {
                showToast(d.error, 'error');
            }
        }
    })
    .catch(function(e) {
        btn.disabled = false;
        btn.textContent = '提交心事';
        showToast('请求失败', 'error');
    });
}

function deleteTreasure(id) {
    if (!confirm('确定删除这条心事？')) return;
    fetch('/public/index.php?route=mindfulness-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete_treasure&id=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            var el = document.getElementById('treasure-' + id);
            if (el) { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }
            showToast('已删除', 'success');
        }
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

function h(s) { return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
