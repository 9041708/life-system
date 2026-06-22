<?php
/** @var array $tasks */
/** @var string $date */
/** @var array $stats */
/** @var int $year */
/** @var int $month */
/** @var array $monthStats */
/** @var array $tasksByDate */
/** @var string $selectedDate */
/** @var array $holidays */
/** @var array $lunarInfo */
/** updated 2026-05-22 */
?>
<script>
// 农历转换（1900-2100）
(function(){var e=[0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,0x06ca0,0x0b550,0x15355,0x04da0,0x0a5b0,0x14573,0x052b0,0x0a9a8,0x0e950,0x06aa0,0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b6a0,0x195a6,0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,0x05aa0,0x076a3,0x096d0,0x04afb,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0,0x14b63,0x09370,0x049f8,0x04970,0x064b0,0x168a6,0x0ea50,0x06b20,0x1a6c4,0x0aae0,0x0a2e0,0x0d2e3,0x0c960,0x0d557,0x0d4a0,0x0da50,0x05d55,0x056a0,0x0a6d0,0x055d4,0x052d0,0x0a9b8,0x0a950,0x0b4a0,0x0b6a6,0x0ad50,0x055a0,0x0aba4,0x0a5b0,0x052b0,0x0b273,0x06930,0x07337,0x06aa0,0x0ad50,0x14b55,0x04b60,0x0a570,0x054e4,0x0d160,0x0e968,0x0d520,0x0daa0,0x16aa6,0x056d0,0x04ae0,0x0a9d4,0x0a4d0,0x0d150,0x0f252,0x0d520],n=["正","二","三","四","五","六","七","八","九","十","冬","腊"],o=["初","十","廿","三"],r=["一","二","三","四","五","六","七","八","九","十"];
function t(y){var s=0;for(var i=0x8000;i>8;i>>=1)s+=e[y-1900]&i?1:0;return s+348+u(y)}function u(y){var m=e[y-1900]&0xf;return m?e[y-1900]&0x10000?30:29:0}function a(y,m){return e[y-1900]&(0x10000>>m)?30:29}
function L(Y,M,D){var B=new Date(1900,0,31),C=new Date(Y,M-1,D),ds=Math.round((C-B)/864e5);if(ds<0)return"";var y,m,d,lp=0,ly=1900;for(;ly<=2100;ly++){var yd=t(ly);if(ds<yd)break;ds-=yd}
var LM=e[ly-1900]&0xf,isLeap=false;for(m=1;m<=12;m++){var md=isLeap?u(ly):a(ly,m);if(ds<md)break;ds-=md;if(!isLeap&&LM==m){isLeap=true;m--}}d=ds+1;var mn=n[m-1];var dn;if(d==10)dn="初十";else if(d==20)dn="二十";else if(d==30)dn="三十";else{var t10=Math.floor(d/10);dn=(t10==1?"十":t10==2?"廿":"")+r[(d-1)%10]}return mn+"月"+dn}
window.getLunar=function(y,m,d){try{return L(y,m,d)}catch(e){return""}};})();
</script>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">📋 待办管理 <small style="color:#0d6efd;font-size:12px">v2</small></h5>
</div>

<div class="row g-3">
    <!-- ===== 左侧：添加 + 列表 ===== -->
    <div class="col-md-5">

        <!-- 任务输入 -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form id="taskForm">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="task_date" value="<?= htmlspecialchars($date) ?>">
                    <input type="hidden" name="color" id="taskColor" value="blue">
                    <input type="hidden" name="recurrence" id="taskRecurrence" value="none">
                    <input type="hidden" name="reminder_at" id="taskReminderAt" value="">
                    <input type="hidden" name="reminder_advance" id="taskReminderAdvance" value="0">
                    <div class="d-flex gap-2">
                        <input type="text" name="title" id="taskTitle" class="form-control form-control-sm" placeholder="输入任务，回车添加..." autocomplete="off">
                        <button class="btn btn-primary btn-sm" type="submit">添加</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="var el=document.getElementById('advFields');el.style.display=el.style.display==='none'?'block':'none'">高级</button>
                    </div>
                    <div id="advFields" style="display:none;margin-top:8px;padding:8px;background:#f8f9fa;border-radius:6px;font-size:13px">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="text-muted" style="white-space:nowrap">颜色</span>
                            <div class="d-flex gap-1" id="colorPicker">
                                <?php foreach(['red'=>'#dc2626','orange'=>'#ea580c','yellow'=>'#ca8a04','green'=>'#16a34a','blue'=>'#2563eb'] as $cn=>$cv): ?>
                                <span class="color-opt" data-color="<?= $cn ?>" style="width:18px;height:18px;border-radius:50%;background:<?= $cv ?>;cursor:pointer;border:2px solid transparent;display:inline-block" onclick="pickColor(this)"></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="text-muted" style="white-space:nowrap">重复</span>
                            <select id="recSel" class="form-select form-select-sm" style="width:auto" onchange="document.getElementById('taskRecurrence').value=this.value">
                                <option value="none">不重复</option><option value="daily">每日</option><option value="weekly">每周</option><option value="monthly">每月</option><option value="yearly">每年</option>
                            </select>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted" style="white-space:nowrap">提醒</span>
                            <input type="datetime-local" id="remAt" class="form-control form-control-sm" style="width:auto" onchange="document.getElementById('taskReminderAt').value=this.value">
                            <select id="remAdv" class="form-select form-select-sm" style="width:auto" onchange="document.getElementById('taskReminderAdvance').value=this.value">
                                <option value="0">准时</option><option value="5">5分钟前</option><option value="15">15分钟前</option><option value="30">30分钟前</option><option value="60">1小时前</option><option value="1440">1天前</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($stats) && $stats['total'] > 0): ?>
        <?php $pct = $stats['total'] > 0 ? round($stats['done'] / $stats['total'] * 100) : 0; ?>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="small text-muted">今日</span>
            <div class="progress flex-grow-1" style="height:5px">
                <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="small text-muted"><?= $stats['done'] ?>/<?= $stats['total'] ?></span>
        </div>
        <?php endif; ?>

        <!-- 任务列表 -->
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                <strong><?= htmlspecialchars($date) ?></strong>
                <span class="badge bg-secondary"><?= count($tasks) ?> 项</span>
            </div>
            <ul class="list-group list-group-flush" id="taskList">
                <?php if (empty($tasks)): ?>
                <li class="list-group-item text-center text-muted py-4" style="font-size:14px">暂无任务</li>
                <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                <?php $taskColor = $task['color'] ?? 'blue'; $colorMap = ['red'=>'#dc2626','orange'=>'#ea580c','yellow'=>'#ca8a04','green'=>'#16a34a','blue'=>'#2563eb']; $borderColor = $colorMap[$taskColor] ?? '#2563eb'; ?>
                <li class="list-group-item d-flex align-items-center gap-2 py-2 <?= !empty($task['completed']) ? 'text-muted opacity-75' : '' ?>" data-id="<?= (int)$task['id'] ?>" style="border-left:3px solid <?= $borderColor ?>">
                    <input type="checkbox" class="form-check-input task-toggle" <?= !empty($task['completed']) ? 'checked' : '' ?> style="width:16px;height:16px;cursor:pointer">
                    <span class="flex-grow-1 <?= !empty($task['completed']) ? 'text-decoration-line-through' : '' ?>" style="font-size:14px"><?= htmlspecialchars($task['title']) ?></span>
                    <?php if (($task['recurrence'] ?? 'none') !== 'none'): ?>
                    <span class="badge bg-info" style="font-size:10px"><?= ['daily'=>'每日','weekly'=>'每周','monthly'=>'每月','yearly'=>'每年'][$task['recurrence']] ?? '' ?></span>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-link text-danger p-0 task-delete" style="font-size:15px">×</button>
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- ===== 右侧：日历 ===== -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold"><?= $year ?>年<?= $month ?>月</h5>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <a href="/public/index.php?route=easytodo-tasks&date=<?= date('Y-m-d', strtotime("$year-$month-01 -1 month")) ?>&year=<?= $month == 1 ? $year-1 : $year ?>&month=<?= $month == 1 ? 12 : $month-1 ?>" class="btn btn-sm btn-outline-secondary">‹</a>
                    <a href="/public/index.php?route=easytodo-tasks&date=<?= date('Y-m-d', strtotime("$year-$month-01 +1 month")) ?>&year=<?= $month == 12 ? $year+1 : $year ?>&month=<?= $month == 12 ? 1 : $month+1 ?>" class="btn btn-sm btn-outline-secondary">›</a>
                    <a href="/public/index.php?route=easytodo-tasks&date=<?= date('Y-m-d') ?>&year=<?= (int)date('Y') ?>&month=<?= (int)date('n') ?>" class="btn btn-sm btn-primary ms-1">今天</a>
                </div>
            </div>

            <div class="card-body p-3">
                <?php
                $firstDay = mktime(0, 0, 0, $month, 1, $year);
                $daysInMonth = date('t', $firstDay);
                $startWeekday = date('N', $firstDay);
                $today = date('Y-m-d');

                $statMap = [];
                if (!empty($monthStats)) {
                    foreach ($monthStats as $row) {
                        $d = $row['task_date'];
                        if (!isset($statMap[$d])) $statMap[$d] = ['done'=>0,'total'=>0];
                        $statMap[$d]['total'] += (int)$row['cnt'];
                        if ((int)$row['completed'] === 1) $statMap[$d]['done'] += (int)$row['cnt'];
                    }
                }

                $taskMap = $tasksByDate;
                $holidayMap = is_array($holidays ?? null) ? $holidays : [];
                $lunarMap = is_array($lunarInfo ?? null) ? $lunarInfo : [];
                ?>

                <!-- 星期头 -->
                <div class="d-flex mb-2">
                    <?php foreach (['一','二','三','四','五','六','日'] as $i => $wd): ?>
                    <div class="text-center <?= $i >= 5 ? 'text-danger' : 'text-muted' ?>" style="width:calc(100%/7);font-size:14px;font-weight:600"><?= $wd ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- 日期网格 -->
                <div class="d-flex flex-wrap" style="gap:4px">
                    <?php for ($i = 1; $i < $startWeekday; $i++): ?>
                    <div style="width:calc(100%/7 - 4px);height:70px"></div>
                    <?php endfor; ?>

                    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <?php
                    $fullDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $isToday = $fullDate === $today;
                    $isSelected = $fullDate === $selectedDate;
                    $weekday = (int)date('N', strtotime($fullDate));
                    $isWeekend = $weekday >= 6;
                    $stat = $statMap[$fullDate] ?? null;
                    $hasTasks = $stat && $stat['total'] > 0;
                    $allDone = $hasTasks && $stat['done'] == $stat['total'];
                    $lunar = $lunarMap[$fullDate] ?? '';
                    $holiday = $holidayMap[$fullDate] ?? null;
                    ?>
                    <div onclick="showDayTasks('<?= $fullDate ?>')" data-date="<?= $fullDate ?>"
                         style="width:calc(100%/7 - 4px);height:70px;background:<?= $isToday ? '#e7f5ff' : 'white' ?>;border-radius:8px;padding:5px 7px;cursor:pointer;border:<?= $isToday ? '2px solid #0d6efd' : '1px solid #eee' ?>;position:relative;transition:box-shadow 0.15s"
                         onmouseover="this.style.boxShadow='0 3px 10px rgba(0,0,0,0.13)'"
                         onmouseout="this.style.boxShadow='none'">
                        <div style="font-size:16px;font-weight:700;color:<?= $isToday ? '#0d6efd' : ($isWeekend ? '#dc3545' : '#333') ?>"><?= $day ?></div>
                        <div class="lunar-text" style="font-size:12px;color:<?= $isWeekend || $holiday ? '#dc3545' : '#888' ?>;margin-top:1px;line-height:1.3"></div>
                        <?php if ($holiday): ?>
                        <div style="font-size:12px;color:#dc3545;font-weight:600;margin-top:1px;line-height:1.3"><?= htmlspecialchars($holiday) ?></div>
                        <?php endif; ?>
                        <?php if ($stat && $stat['total'] > 0): ?>
                        <div style="position:absolute;bottom:5px;left:7px;font-size:11px;color:#888"><?= $stat['done'] ?>/<?= $stat['total'] ?></div>
                        <?php endif; ?>
                        <?php if ($hasTasks): ?>
                        <span style="width:7px;height:7px;border-radius:50%;background:<?= $allDone ? '#198754' : '#ffc107' ?>;position:absolute;top:5px;right:5px;flex-shrink:0"></span>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>

                    <?php $remain = 7 - (($startWeekday - 1 + $daysInMonth) % 7); if ($remain < 7): ?>
                    <?php for ($i = 0; $i < $remain; $i++): ?>
                    <div style="width:calc(100%/7 - 4px);height:70px"></div>
                    <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 日期弹窗 -->
<div class="modal fade" id="dayTaskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="dayTaskTitle"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dayTaskBody">
                <div class="text-center text-muted py-4">加载中...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-sm" onclick="quickAddToDay()">+ 添加任务</button>
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script>
window.allTasksMap = <?= json_encode($taskMap) ?>;

var dayModal;
document.addEventListener('DOMContentLoaded', function() {
    dayModal = new bootstrap.Modal(document.getElementById('dayTaskModal'));
});

var currentDayDate = null;

function showDayTasks(date) {
    currentDayDate = date;
    var parts = date.split('-');
    var y = parseInt(parts[0]), m = parseInt(parts[1]), d = parseInt(parts[2]);
    var lunarStr = getLunar(y, m, d);
    document.getElementById('dayTaskTitle').textContent = lunarStr ? (date + ' ' + lunarStr) : date;

    var tasks = window.allTasksMap[date] || [];
    var html = '';
    if (tasks.length === 0) {
        html = '<div class="text-center text-muted py-4"><div style="font-size:36px;margin-bottom:8px">📋</div><div>暂无任务</div></div>';
    } else {
        html += '<ul class="list-group list-group-flush mb-3">';
        tasks.forEach(function(t) {
            var done = t.completed ? 'checked' : '';
            var cls = done ? 'text-decoration-line-through text-muted' : '';
            html += '<li class="list-group-item d-flex align-items-center gap-2 py-2 ' + (done ? 'opacity-75' : '') + '">';
            html += '<input type="checkbox" class="form-check-input" style="width:16px;height:16px" ' + done + ' data-id="' + t.id + '">';
            html += '<span class="flex-grow-1 ' + cls + '" style="font-size:14px">' + escapeHtml(t.title) + '</span>';
            html += '<button class="btn btn-sm btn-link text-danger p-0 del-task" style="font-size:16px" data-id="' + t.id + '">×</button>';
            html += '</li>';
        });
        html += '</ul>';
    }
    document.getElementById('dayTaskBody').innerHTML = html;
    dayModal.show();

    document.querySelectorAll('#dayTaskBody input[type=checkbox]').forEach(function(cb) {
        cb.onchange = function() {
            var id = this.dataset.id;
            var fd = new FormData();
            fd.append('action', 'toggle');
            fd.append('id', id);
            fetch('/public/index.php?route=easytodo-api-tasks', {method:'POST', body: fd})
                .then(r => r.json())
                .then(function() { showDayTasks(currentDayDate); location.reload(); });
        };
    });
    document.querySelectorAll('.del-task').forEach(function(btn) {
        btn.onclick = function() {
            if (!confirm('删除？')) return;
            var fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', this.dataset.id);
            fetch('/public/index.php?route=easytodo-api-tasks', {method:'POST', body: fd})
                .then(r => r.json())
                .then(function() { showDayTasks(currentDayDate); location.reload(); });
        };
    });
}

function quickAddToDay() {
    var title = prompt('输入任务：');
    if (!title) return;
    var fd = new FormData();
    fd.append('action', 'create');
    fd.append('task_date', currentDayDate);
    fd.append('title', title);
    fetch('/public/index.php?route=easytodo-api-tasks', {method:'POST', body: fd})
        .then(r => r.json())
        .then(function(d) { if (d.ok) { showDayTasks(currentDayDate); location.reload(); } });
}

function escapeHtml(t) {
    var d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
}

function pickColor(el) {
    document.querySelectorAll('.color-opt').forEach(function(c){c.style.borderColor='transparent'});
    el.style.borderColor='#333';
    document.getElementById('taskColor').value = el.getAttribute('data-color');
}

// 主页添加
document.getElementById('taskForm').onsubmit = function(e) {
    e.preventDefault();
    var t = document.getElementById('taskTitle').value.trim();
    if (!t) return;
    var fd = new FormData(this);
    fetch('/public/index.php?route=easytodo-api-tasks', {method:'POST', body: fd})
        .then(r => r.json())
        .then(function(d) { if (d.ok) location.reload(); });
};

document.querySelectorAll('.task-toggle').forEach(function(cb) {
    cb.onchange = function() {
        var li = this.closest('li');
        var id = li.dataset.id;
        var fd = new FormData();
        fd.append('action', 'toggle');
        fd.append('id', id);
        fetch('/public/index.php?route=easytodo-api-tasks', {method:'POST', body: fd})
            .then(r => r.json())
            .then(function(d) {
                if (d.ok) {
                    var span = li.querySelector('span:not(.badge)');
                    if (d.completed) { span.classList.add('text-decoration-line-through','text-muted'); li.classList.add('text-muted','opacity-75'); }
                    else { span.classList.remove('text-decoration-line-through','text-muted'); li.classList.remove('text-muted','opacity-75'); }
                }
            });
    };
});

document.querySelectorAll('.task-delete').forEach(function(btn) {
    btn.onclick = function() {
        var li = this.closest('li');
        if (!confirm('删除？')) return;
        var fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', li.dataset.id);
        fetch('/public/index.php?route=easytodo-api-tasks', {method:'POST', body: fd})
            .then(r => r.json())
            .then(function(d) { if (d.ok) li.remove(); });
    };
});

// 客户端渲染农历
(function() {
    if (typeof getLunar === 'undefined') return;
    document.querySelectorAll('[data-date]').forEach(function(cell) {
        var date = cell.getAttribute('data-date');
        var parts = date.split('-');
        var y = parseInt(parts[0]), m = parseInt(parts[1]), d = parseInt(parts[2]);
        var lunarText = cell.querySelector('.lunar-text');
        if (!lunarText) return;
        lunarText.textContent = getLunar(y, m, d);
    });
})();
</script>