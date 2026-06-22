<?php
/** @var array $countdowns */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">⏱️ 倒计时</h5>
    <button class="btn btn-primary btn-sm" onclick="showAddModal()">+ 添加倒计时</button>
</div>

<?php if (empty($countdowns)): ?>
<div class="text-center text-muted py-5">
    <div style="font-size:48px;margin-bottom:12px">⏱️</div>
    <div>暂无倒计时</div>
</div>
<?php else: ?>
<div class="row g-3" id="countdownList">
    <?php foreach ($countdowns as $cd): ?>
    <div class="col-md-4 col-lg-3">
        <div class="card h-100 <?= empty($cd['enabled']) ? 'opacity-50' : '' ?>">
            <div class="card-body text-center d-flex flex-column">
                <h6 class="card-title mb-2"><?= htmlspecialchars($cd['title']) ?></h6>
                <div class="countdown-display display-6 fw-bold text-primary my-2" data-target="<?= htmlspecialchars($cd['target_time']) ?>" data-mode="<?= (int)($cd['display_mode'] ?? 2) ?>">计算中...</div>
                <div class="small text-muted mb-1">目标：<?= htmlspecialchars(substr($cd['target_time'], 0, 16)) ?></div>
                <?php if ($cd['repeat_type'] !== 'none'): ?>
                <span class="badge bg-info mb-2"><?= ['weekly'=>'每周','monthly'=>'每月','yearly'=>'每年'][$cd['repeat_type']] ?? $cd['repeat_type'] ?></span>
                <?php endif; ?>
                <div class="mt-auto d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleEnabled(<?= (int)$cd['id'] ?>)"><?= !empty($cd['enabled']) ? '停用' : '启用' ?></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCountdown(<?= (int)$cd['id'] ?>)">删除</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- 添加弹窗 -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">添加倒计时</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="countdownForm">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">标题</label>
                        <input type="text" name="title" class="form-control" placeholder="例如：春节假期" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">目标时间</label>
                        <input type="datetime-local" name="target_time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">显示粒度</label>
                        <select name="display_mode" class="form-select">
                            <option value="1">只显示天</option>
                            <option value="2" selected>天 + 时分</option>
                            <option value="3">天 + 时分秒</option>
                            <option value="4">完整倒计时</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">重复类型</label>
                        <select name="repeat_type" class="form-select" id="repeatTypeSelect" onchange="toggleRepeatFields()">
                            <option value="none">不重复</option>
                            <option value="weekly">每周</option>
                            <option value="monthly">每月</option>
                            <option value="yearly">每年</option>
                        </select>
                    </div>
                    <div id="weeklyRepeatField" class="mb-3" style="display:none">
                        <label class="form-label">重复星期</label>
                        <select name="repeat_weekday" class="form-select">
                            <option value="1">周一</option>
                            <option value="2">周二</option>
                            <option value="3">周三</option>
                            <option value="4">周四</option>
                            <option value="5">周五</option>
                            <option value="6">周六</option>
                            <option value="0">周日</option>
                        </select>
                    </div>
                    <div id="monthlyRepeatField" class="mb-3" style="display:none">
                        <label class="form-label">重复日期（每月几号）</label>
                        <input type="number" name="repeat_month_day" class="form-control" min="1" max="31" placeholder="1-31">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary btn-sm" form="countdownForm">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
var addModal;
document.addEventListener('DOMContentLoaded', function() {
    addModal = new bootstrap.Modal(document.getElementById('addModal'));
});

function showAddModal() {
    document.getElementById('countdownForm').reset();
    document.getElementById('weeklyRepeatField').style.display = 'none';
    document.getElementById('monthlyRepeatField').style.display = 'none';
    addModal.show();
}

function toggleRepeatFields() {
    var v = document.getElementById('repeatTypeSelect').value;
    document.getElementById('weeklyRepeatField').style.display = v === 'weekly' ? '' : 'none';
    document.getElementById('monthlyRepeatField').style.display = v === 'monthly' ? '' : 'none';
}

document.getElementById('countdownForm').onsubmit = function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch('/public/index.php?route=easytodo-api-countdowns', {method:'POST', body: fd})
        .then(r => r.json())
        .then(function(d) { if (d.ok) location.reload(); });
};

function toggleEnabled(id) {
    var fd = new FormData();
    fd.append('action', 'toggle_enabled');
    fd.append('id', id);
    fetch('/public/index.php?route=easytodo-api-countdowns', {method:'POST', body: fd})
        .then(r => r.json())
        .then(function(d) { if (d.ok) location.reload(); });
}

function deleteCountdown(id) {
    if (!confirm('删除此倒计时？')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('/public/index.php?route=easytodo-api-countdowns', {method:'POST', body: fd})
        .then(r => r.json())
        .then(function(d) { if (d.ok) location.reload(); });
}

function updateCountdowns() {
    document.querySelectorAll('.countdown-display').forEach(function(el) {
        var target = new Date(el.dataset.target);
        var now = new Date();
        var diff = target - now;
        var mode = parseInt(el.dataset.mode);
        if (diff < 0) { el.textContent = '已到期'; return; }
        var d = Math.floor(diff / 86400000);
        var h = Math.floor((diff % 86400000) / 3600000);
        var m = Math.floor((diff % 3600000) / 60000);
        var s = Math.floor((diff % 60000) / 1000);
        var text = '';
        if (mode === 1) text = d + ' 天';
        else if (mode === 2) text = d + ' 天 ' + h + ' 时 ' + m + ' 分';
        else if (mode === 3) text = d + ' 天 ' + h + ' 时 ' + m + ' 分 ' + s + ' 秒';
        else text = d + ' 天 ' + h + ' 时 ' + m + ' 分 ' + s + ' 秒';
        el.textContent = text;
    });
}

updateCountdowns();
setInterval(updateCountdowns, 1000);
</script>