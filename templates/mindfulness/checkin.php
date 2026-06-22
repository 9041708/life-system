<?php
/** @var array $config */
/** @var float $currentScore */
/** @var array $streakStats */
/** @var bool $isCheckedIn */
/** @var array $aiQuota */
/** @var bool $isAdmin */

$scoreColor = '#3b82f6';
if ($currentScore <= 40) $scoreColor = '#ef4444';
elseif ($currentScore <= 60) $scoreColor = '#f97316';
elseif ($currentScore <= 80) $scoreColor = '#3b82f6';
else $scoreColor = '#22c55e';
?>
<style>
.mf-checkin-wrap { max-width: 860px; margin: 0 auto; text-align: center; }
.mf-glass {
    background: rgba(255,255,255,0.45); border: 1px solid rgba(255,255,255,0.35);
    border-radius: 14px; padding: 24px;
    backdrop-filter: blur(18px) saturate(140%); -webkit-backdrop-filter: blur(18px) saturate(140%);
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
}
body.theme-dark .mf-glass {
    background: rgba(30,41,59,0.45); border-color: rgba(148,163,184,0.12);
    box-shadow: 0 4px 24px rgba(0,0,0,0.2);
}
.mf-score-circle {
    width: 150px; height: 150px; border-radius: 50%;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    margin: 0 auto 16px; border: 4px solid <?= $scoreColor ?>;
    background: rgba(255,255,255,0.85);
    position: relative;
}
body.theme-dark .mf-score-circle { background: rgba(30,41,59,0.85); }
.mf-score-num { font-size: 2.2rem; font-weight: 700; color: <?= $scoreColor ?>; line-height: 1; }
.mf-score-label { font-size: 0.75rem; color: #999; margin-top: 2px; }
.mf-checkin-btn {
    width: 120px; height: 120px; border-radius: 50%;
    border: none; font-size: 1.1rem; font-weight: 600; color: #fff;
    background: linear-gradient(135deg, #667eea, #764ba2);
    cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(102,126,234,0.4);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    margin: 20px auto;
}
.mf-checkin-btn:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(102,126,234,0.5); }
.mf-checkin-btn:active { transform: scale(0.95); }
.mf-checkin-btn:disabled { background: #ccc; cursor: default; box-shadow: none; }
.mf-checkin-btn:disabled:hover { transform: none; }
.mf-trophy { font-size: 1.3rem; display: inline-flex; align-items: center; gap: 6px; }
.mf-cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.mf-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
.mf-cal-weekday { font-size: 0.7rem; color: #999; text-align: center; padding: 6px 0; font-weight: 500; }
.mf-cal-day {
    display: flex; flex-direction: column; align-items: stretch; justify-content: flex-start;
    border-radius: 8px; font-size: 0.78rem; cursor: pointer; position: relative;
    border: 1px solid rgba(0,0,0,0.06); background: rgba(255,255,255,0.65);
    transition: all 0.15s; min-height: 72px; padding: 4px 5px;
}
body.theme-dark .mf-cal-day { background: rgba(30,41,59,0.45); border-color: rgba(148,163,184,0.12); }
.mf-cal-day:hover { border-color: #667eea; background: rgba(102,126,234,0.06); }
.mf-cal-day.checked { background: rgba(102,126,234,0.1); }
.mf-cal-day.today { border-color: #667eea; border-width: 2px; }
.mf-cal-day .cal-date { font-weight: 600; font-size: 0.8rem; line-height: 1; margin-bottom: 3px; }
.mf-cal-day .cal-event {
    display: block; font-size: 0.6rem; font-weight: 500; line-height: 1.3;
    padding: 1px 4px; border-radius: 3px; margin-bottom: 1px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.mf-cal-day .cal-event.pos { background: rgba(34,197,94,0.2); color: #15803d; }
.mf-cal-day .cal-event.neg { background: rgba(239,68,68,0.2); color: #b91c1c; }
.mf-cal-day .cal-event.check { background: rgba(102,126,234,0.2); color: #4338ca; }
.mf-cal-weekday { font-size: 0.7rem; color: #999; text-align: center; padding: 4px 0; }
.mf-backfill-modal .modal-body { max-height: 60vh; overflow-y: auto; }
.mf-record-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 12px; border: 1px solid rgba(0,0,0,0.06); border-radius: 6px; margin-bottom: 6px;
    font-size: 0.85rem;
}
body.theme-dark .mf-record-item { border-color: rgba(148,163,184,0.15); }
.mf-record-item .badge { font-size: 0.7rem; }
</style>

<div class="mf-checkin-wrap">
    <h5 class="mb-4">💊 正念签到 
    <span class="ms-2" style="font-size:0.75rem;vertical-align:middle">
        <label class="form-check form-switch d-inline-flex align-items-center gap-1" style="cursor:pointer">
            <input class="form-check-input" type="checkbox" id="autoCheckinToggle" <?=!empty($config['auto_checkin'])?'checked':''?> onchange="toggleAutoCheckin()">
            <span class="form-check-label small">自动签到</span>
        </label>
    </span>
    </h5>

    <div class="mf-score-circle">
        <div class="mf-score-num"><?= number_format($currentScore, 1) ?></div>
        <div class="mf-score-label">当前分数</div>
    </div>

    <button class="mf-checkin-btn" id="btnCheckin" <?= $isCheckedIn ? 'disabled' : '' ?> onclick="doCheckin()">
        <span style="font-size:1.5rem">💊</span>
        <span><?= $isCheckedIn ? '已签到' : '签到' ?></span>
    </button>

    <div class="mt-3">
        <span class="mf-trophy">🏆 最高连续签到：<strong><?= (int)$streakStats['max_streak'] ?></strong> 天</span>
    </div>
    <div class="mt-1 mb-4">
        <span class="mf-trophy" style="font-size:1rem">🔥 当前连续签到：<strong><?= (int)$streakStats['current_streak'] ?></strong> 天</span>
    </div>

    <div class="mf-glass">
        <div class="mf-cal-header">
            <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(-1)">◀</button>
            <span id="calMonthLabel" class="fw-semibold"></span>
            <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(1)">▶</button>
        </div>
        <div class="mf-cal-grid" id="calWeekdays"></div>
        <div class="mf-cal-grid" id="calGrid"></div>
    </div>
</div>

<div class="modal fade mf-backfill-modal" id="backfillModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="backfillTitle">补录记录</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="backfillRecords"></div>
                <hr>
                <h6 class="small fw-semibold mb-2">添加记录</h6>
                <div class="mb-2">
                    <div class="btn-group btn-group-sm w-100">
                        <button class="btn btn-outline-success" onclick="setBackfillType('positive')">正念</button>
                        <button class="btn btn-outline-danger" onclick="setBackfillType('negative')">负念</button>
                    </div>
                </div>
                <div id="backfillItems"></div>
            </div>
        </div>
    </div>
</div>

<script>
var calYear = <?= date('Y') ?>;
var calMonth = <?= date('n') ?>;
var calData = {};
var backfillDate = '';
var backfillType = 'positive';
var positiveItems = <?= json_encode($config['positive_items'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
var negativeItems = <?= json_encode($config['negative_items'] ?? [], JSON_UNESCAPED_UNICODE) ?>;

function renderCalendar() {
    var label = document.getElementById('calMonthLabel');
    label.textContent = calYear + '年' + calMonth + '月';

    var weekdays = ['日','一','二','三','四','五','六'];
    var whtml = '';
    weekdays.forEach(function(w) { whtml += '<div class="mf-cal-weekday">' + w + '</div>'; });
    document.getElementById('calWeekdays').innerHTML = whtml;

    fetch('/public/index.php?route=mindfulness-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_calendar&year=' + calYear + '&month=' + calMonth
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.ok) return;
        calData = d.calendar || {};
        renderGrid();
    });
}

function renderGrid() {
    var firstDay = new Date(calYear, calMonth - 1, 1).getDay();
    var daysInMonth = new Date(calYear, calMonth, 0).getDate();
    var today = '<?= date('Y-m-d') ?>';
    var html = '';
    for (var i = 0; i < firstDay; i++) {
        html += '<div class="mf-cal-day" style="border:none;background:none;"></div>';
    }
    for (var d = 1; d <= daysInMonth; d++) {
        var date = calYear + '-' + String(calMonth).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        var info = calData[date] || {};
        var cls = 'mf-cal-day';
        if (info.checked) cls += ' checked';
        if (date === today) cls += ' today';
        html += '<div class="' + cls + '" onclick="openDay(\'' + date + '\')">';
        html += '<div class="cal-date">' + d + '</div>';
        if (info.checked) html += '<div class="cal-event check">签到</div>';
        if (info.positive !== undefined && info.positive !== 0) html += '<div class="cal-event pos">正念 +' + info.positive + '</div>';
        if (info.negative !== undefined && info.negative !== 0) html += '<div class="cal-event neg">负念 ' + info.negative + '</div>';
        html += '</div>';
    }
    document.getElementById('calGrid').innerHTML = html;
}

function changeMonth(delta) {
    calMonth += delta;
    if (calMonth > 12) { calMonth = 1; calYear++; }
    if (calMonth < 1) { calMonth = 12; calYear--; }
    renderCalendar();
}

function openDay(date) {
    backfillDate = date;
    document.getElementById('backfillTitle').textContent = date + ' 补录';
    fetch('/public/index.php?route=mindfulness-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_day_records&date=' + date
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.ok) return;
        positiveItems = d.positive_items || positiveItems;
        negativeItems = d.negative_items || negativeItems;
        renderRecords(d.records || []);
        setBackfillType('positive');
        new bootstrap.Modal(document.getElementById('backfillModal')).show();
    });
}

function renderRecords(records) {
    var html = '';
    if (records.length === 0) {
        html = '<div class="text-muted small text-center py-2">暂无记录</div>';
    }
    records.forEach(function(r) {
        var typeLabel = r.type === 'positive' ? '正念' : '负念';
        var typeBadge = r.type === 'positive' ? 'bg-success' : 'bg-danger';
        html += '<div class="mf-record-item">';
        html += '<span><span class="badge ' + typeBadge + ' me-1">' + typeLabel + '</span>' + h(r.item_name) + '</span>';
        html += '<span>';
        var sc = parseFloat(r.score_change);
        html += '<span class="' + (sc >= 0 ? 'text-success' : 'text-danger') + '">' + (sc >= 0 ? '+' : '') + sc + '</span>';
        html += ' <button class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:0.7rem" onclick="deleteRecord(' + r.id + ')">✕</button>';
        html += '</span></div>';
    });
    document.getElementById('backfillRecords').innerHTML = html;
}

function setBackfillType(type) {
    backfillType = type;
    var items = type === 'positive' ? positiveItems : negativeItems;
    var excludeList = ['正能量树洞', '负能量树洞'];
    var html = '<div class="d-flex flex-wrap gap-1">';
    Object.keys(items).forEach(function(name) {
        if (excludeList.indexOf(name) !== -1) return;
        var score = items[name];
        var btnClass = type === 'positive' ? 'btn-outline-success' : 'btn-outline-danger';
        html += '<button class="btn btn-sm ' + btnClass + '" onclick="addRecord(\'' + h(name) + '\')">' + h(name) + ' ' + (score >= 0 ? '+' : '') + score + '</button>';
    });
    html += '</div>';
    document.getElementById('backfillItems').innerHTML = html;
}

function addRecord(name) {
    fetch('/public/index.php?route=mindfulness-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=backfill_record&date=' + backfillDate + '&type=' + backfillType + '&item_name=' + encodeURIComponent(name)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            showToast(d.message, 'success');
            openDay(backfillDate);
            renderCalendar();
        } else {
            showToast(d.error, 'error');
        }
    });
}

function deleteRecord(id) {
    if (!confirm('确定删除该记录？')) return;
    fetch('/public/index.php?route=mindfulness-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete_record&record_id=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            showToast(d.message, 'success');
            openDay(backfillDate);
            renderCalendar();
        }
    });
}

function toggleAutoCheckin(){
    var on=document.getElementById('autoCheckinToggle').checked?1:0;
    var fd=new FormData();fd.append('action','save_config');fd.append('auto_checkin',on);fd.append('initial_score','<?=$config['initial_score']?>');fd.append('checkin_score','<?=$config['checkin_score']?>');
    fetch('/public/index.php?route=mindfulness-api',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.ok)showToast(on?'自动签到已开启':'自动签到已关闭','success');});
}
function doCheckin() {
    var btn = document.getElementById('btnCheckin');
    btn.disabled = true;
    fetch('/public/index.php?route=mindfulness-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=do_checkin'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            showToast(d.message, 'success');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            btn.disabled = false;
            showToast(d.error, 'error');
        }
    })
    .catch(function() { btn.disabled = false; });
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

document.addEventListener('DOMContentLoaded', function() {
    renderCalendar();
});
</script>
