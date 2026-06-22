<?php
/** @var array $settings */
/** @var array $recentSessions */
/** @var int $todayWorkSessions */
/** @var int $todayWorkMinutes */
?>
<div class="row">
    <div class="col-md-4">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header"><strong>番茄钟设置</strong></div>
            <div class="card-body">
                <form id="pomodoroSettings">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="mb-2">
                        <label class="form-label">工作时长（分钟）</label>
                        <input type="number" name="work_duration" class="form-control" value="<?= (int)($settings['work_duration'] ?? 25) ?>" min="1" max="120">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">短休息（分钟）</label>
                        <input type="number" name="short_break" class="form-control" value="<?= (int)($settings['short_break'] ?? 5) ?>" min="1" max="60">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">长休息（分钟）</label>
                        <input type="number" name="long_break" class="form-control" value="<?= (int)($settings['long_break'] ?? 15) ?>" min="1" max="60">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">长休息间隔周期数</label>
                        <input type="number" name="long_break_interval" class="form-control" value="<?= (int)($settings['long_break_interval'] ?? 4) ?>" min="1" max="10">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">保存设置</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header"><strong>今日统计</strong></div>
            <div class="card-body text-center">
                <div class="display-6 fw-bold text-success"><?= $todayWorkSessions ?></div>
                <div class="text-muted small">完成番茄数</div>
                <div class="display-6 fw-bold text-info mt-3"><?= $todayWorkMinutes ?></div>
                <div class="text-muted small">专注分钟数</div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <strong>番茄钟</strong>
                <div>
                    <button class="btn btn-success btn-sm" id="startBtn" onclick="startTimer()">▶ 开始工作</button>
                    <button class="btn btn-warning btn-sm" id="pauseBtn" style="display:none" onclick="pauseTimer()">⏸ 暂停</button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="resetTimer()">重置</button>
                </div>
            </div>
            <div class="card-body text-center">
                <div class="display-1 fw-bold mb-3" id="timerDisplay" style="font-variant-numeric:tabular-nums">25:00</div>
                <div class="mb-3">
                    <span class="badge bg-success me-1" id="statusLabel">工作</span>
                    <span class="badge bg-secondary" id="cycleLabel">第 1 / 4 个周期</span>
                </div>
                <div class="progress" style="height:8px;max-width:400px;margin:0 auto">
                    <div class="progress-bar bg-success" id="timerProgress" style="width:0%"></div>
                </div>
            </div>
        </div>

        <div class="card mt-3 border-0 shadow-sm">
            <div class="card-header"><strong>最近记录</strong></div>
            <ul class="list-group list-group-flush">
                <?php if (empty($recentSessions)): ?>
                <li class="list-group-item text-muted text-center">暂无记录</li>
                <?php else: ?>
                <?php foreach (array_slice($recentSessions, 0, 10) as $s): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span>
                        <span class="badge bg-<?= $s['type'] === 'work' ? 'success' : ($s['type'] === 'short_break' ? 'info' : 'warning') ?>">
                            <?= $s['type'] === 'work' ? '工作' : ($s['type'] === 'short_break' ? '短休息' : '长休息') ?>
                        </span>
                        <?= htmlspecialchars(substr($s['started_at'], 0, 16)) ?>
                    </span>
                    <span class="text-muted small"><?= (int)$s['duration_minutes'] ?> 分钟</span>
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- 提醒弹窗 -->
<div class="modal fade" id="alertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center" style="border:3px solid #0d6efd">
            <div class="modal-body py-4">
                <div style="font-size:64px;margin-bottom:12px" id="alertIcon">🔔</div>
                <h4 id="alertTitle" class="mb-2">工作时间结束！</h4>
                <p class="text-muted mb-3" id="alertSubtitle">休息一下，喝杯水吧</p>
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-primary btn-lg" id="alertNextBtn" onclick="handleAlertNext()">开始休息 ▶</button>
                    <button class="btn btn-outline-secondary" onclick="dismissAlert()">稍后</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var workDur = <?= (int)($settings['work_duration'] ?? 25) ?>;
var shortBreak = <?= (int)($settings['short_break'] ?? 5) ?>;
var longBreak = <?= (int)($settings['long_break'] ?? 15) ?>;
var longBreakInterval = <?= (int)($settings['long_break_interval'] ?? 4) ?>;

var currentType = 'work';
var currentCycle = 1;
var timeLeft = workDur * 60;
var sessionId = null;
var timerInterval = null;
var paused = false;
var startedAt = null;
var pendingNextType = null;
var pendingNextCycle = null;
var pendingNextTime = null;
var pendingIsWorkEnd = false;

var alertModal;
document.addEventListener('DOMContentLoaded', function() {
    alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
});

document.getElementById('pomodoroSettings').onsubmit = function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch('/public/index.php?route=easytodo-api-pomodoro', {method:'POST', body: fd})
        .then(r => r.json())
        .then(d => { if (d.ok) location.reload(); });
};

function formatTime(seconds) {
    var m = Math.floor(seconds / 60).toString().padStart(2, '0');
    var s = (seconds % 60).toString().padStart(2, '0');
    return m + ':' + s;
}

function updateDisplay() {
    document.getElementById('timerDisplay').textContent = formatTime(timeLeft);
    var total = currentType === 'work' ? workDur * 60 : (currentType === 'short_break' ? shortBreak * 60 : longBreak * 60);
    document.getElementById('timerProgress').style.width = ((total - timeLeft) / total * 100) + '%';
    var labels = {work:'工作', short_break:'短休息', long_break:'长休息'};
    var colors = {work:'bg-success', short_break:'bg-info', long_break:'bg-warning'};
    document.getElementById('statusLabel').textContent = labels[currentType];
    document.getElementById('statusLabel').className = 'badge me-1 ' + colors[currentType];
    document.getElementById('cycleLabel').textContent = '第 ' + currentCycle + ' / ' + longBreakInterval + ' 个周期';
    document.title = formatTime(timeLeft) + ' ' + labels[currentType] + ' - 番茄钟';
}

function playBeep() {
    try {
        var ctx = new (window.AudioContext || window.webkitAudioContext)();
        var freqs = [700, 900, 1100];
        freqs.forEach(function(freq, i) {
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.25, ctx.currentTime + i * 0.25);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.25 + 0.45);
            osc.start(ctx.currentTime + i * 0.25);
            osc.stop(ctx.currentTime + i * 0.25 + 0.5);
        });
    } catch(e) {}
}

function showBrowserNotification(title, body) {
    if (Notification.permission === 'granted') {
        try { new Notification(title, {body: body, icon: '🔔', silent: false}); } catch(e) {}
    }
}

function showAlert(nextType, nextCycle, nextTime) {
    var isWorkEnd = currentType === 'work';
    var isBreakEnd = !isWorkEnd;

    // 设置弹窗内容
    if (isWorkEnd) {
        // 工作结束 → 要休息
        document.getElementById('alertIcon').textContent = '🔔';
        document.getElementById('alertTitle').textContent = nextType === 'long_break' ? '长休息时间到！' : '工作结束！';
        document.getElementById('alertSubtitle').textContent = '休息一下，喝杯水吧';
        document.getElementById('alertNextBtn').style.display = '';
        document.getElementById('alertNextBtn').textContent = nextType === 'long_break' ? '开始长休息 ▶' : '开始休息 ▶';
    } else {
        // 休息结束 → 要工作
        document.getElementById('alertIcon').textContent = '✅';
        document.getElementById('alertTitle').textContent = '休息结束！';
        document.getElementById('alertSubtitle').textContent = '准备开始下一个番茄钟吧';
        document.getElementById('alertNextBtn').style.display = '';
        document.getElementById('alertNextBtn').textContent = '开始工作 ▶';
    }

    pendingNextType = nextType;
    pendingNextCycle = nextCycle;
    pendingNextTime = nextTime;
    pendingIsWorkEnd = isWorkEnd;

    alertModal.show();
    playBeep();
    showBrowserNotification(
        isWorkEnd ? '工作时间结束' : '休息结束',
        isWorkEnd ? '休息一下' : '准备开始下一个番茄钟'
    );
}

function handleAlertNext() {
    alertModal.hide();
    currentType = pendingNextType;
    currentCycle = pendingNextCycle;
    timeLeft = pendingNextTime;
    updateDisplay();
    document.getElementById('startBtn').style.display = 'none';
    document.getElementById('pauseBtn').style.display = '';
    timerInterval = setInterval(tick, 1000);
}

function dismissAlert() {
    alertModal.hide();
    // 不自动切换，用户点"开始工作/休息"才切换
    // 计时器停在 00:00，页面提示该阶段已结束
    document.getElementById('startBtn').style.display = '';
    document.getElementById('pauseBtn').style.display = 'none';
    clearInterval(timerInterval);
    timerInterval = null;
}

function startTimer() {
    if (timerInterval) return;
    startedAt = new Date().toISOString();
    var fd = new FormData();
    fd.append('action', 'start_session');
    fd.append('type', currentType);
    fd.append('started_at', startedAt);
    fetch('/public/index.php?route=easytodo-api-pomodoro', {method:'POST', body: fd})
        .then(r => r.json())
        .then(d => { if (d.ok) sessionId = d.id; });
    paused = false;
    document.getElementById('startBtn').style.display = 'none';
    document.getElementById('pauseBtn').style.display = '';
    timerInterval = setInterval(tick, 1000);
}

function tick() {
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        timerInterval = null;

        if (sessionId) {
            var fd = new FormData();
            fd.append('action', 'end_session');
            fd.append('id', sessionId);
            fd.append('started_at', startedAt);
            fd.append('ended_at', new Date().toISOString());
            fetch('/public/index.php?route=easytodo-api-pomodoro', {method:'POST', body: fd});
            sessionId = null;
        }

        // 计算下一个阶段
        var nextType = null, nextCycle = currentCycle, nextTime = 0;
        if (currentType === 'work') {
            if (currentCycle >= longBreakInterval) {
                nextType = 'long_break';
                nextTime = longBreak * 60;
            } else {
                nextType = 'short_break';
                nextTime = shortBreak * 60;
            }
            nextCycle = currentCycle;
        } else {
            if (currentType === 'long_break') {
                nextCycle = 1;
            } else {
                nextCycle = currentCycle + 1;
            }
            nextType = 'work';
            nextTime = workDur * 60;
        }

        showAlert(nextType, nextCycle, nextTime);
        return;
    }
    timeLeft--;
    updateDisplay();
}

function pauseTimer() {
    if (paused) {
        timerInterval = setInterval(tick, 1000);
        document.getElementById('pauseBtn').textContent = '⏸ 暂停';
        document.getElementById('pauseBtn').className = 'btn btn-warning btn-sm';
        paused = false;
    } else {
        clearInterval(timerInterval);
        timerInterval = null;
        document.getElementById('pauseBtn').textContent = '▶ 继续';
        document.getElementById('pauseBtn').className = 'btn btn-success btn-sm';
        paused = true;
    }
}

function resetTimer() {
    clearInterval(timerInterval);
    timerInterval = null;
    paused = false;
    sessionId = null;
    currentType = 'work';
    currentCycle = 1;
    timeLeft = workDur * 60;
    alertModal.hide();
    updateDisplay();
    document.getElementById('startBtn').style.display = '';
    document.getElementById('pauseBtn').style.display = 'none';
    document.getElementById('pauseBtn').textContent = '⏸ 暂停';
    document.getElementById('pauseBtn').className = 'btn btn-warning btn-sm';
    document.title = '番茄钟';
}

updateDisplay();
</script>