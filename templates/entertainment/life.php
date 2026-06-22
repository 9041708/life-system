<?php
// templates/entertainment/life.php
// 我的人生 - 游戏主界面
?>
<div class="container-fluid px-3 py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <!-- 标题 -->
            <div class="text-center mb-4">
                <h2 class="fw-bold">🎲 我的人生</h2>
                <p class="text-muted mb-0">每一次选择，都是不同的人生</p>
            </div>

            <!-- 进行中的游戏 -->
            <?php if ($activeRecord): ?>
            <div class="alert alert-info d-flex align-items-center justify-content-between" role="alert">
                <div>
                    <strong>⏳ 进行中的人生</strong><br>
                    <small>原生家庭：<?= htmlspecialchars($activeRecord['family_background']) ?>，开始于 <?= date('Y-m-d H:i', strtotime($activeRecord['start_time'])) ?></small>
                </div>
                <button class="btn btn-primary btn-sm" onclick="continueLife(<?= $activeRecord['id'] ?>)">继续游戏 →</button>
            </div>
            <?php endif; ?>

            <!-- 开始新游戏 -->
            <div class="card shadow-sm mb-4" id="startCard">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">🌱 开始新的人生</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">选择性别</label>
                        <div class="d-flex gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="genderSelect" id="genderMale" value="male" checked>
                                <label class="form-check-label" for="genderMale">👨 男生</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="genderSelect" id="genderFemale" value="female">
                                <label class="form-check-label" for="genderFemale">👩 女生</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">选择原生家庭</label>
                        <select class="form-select" id="familySelect">
                            <option value="随机生成">🎲 随机生成</option>
                            <option value="贫困农村">🌾 贫困农村</option>
                            <option value="普通工薪">🏢 普通工薪</option>
                            <option value="富裕中产">💼 富裕中产</option>
                            <option value="知识分子">📚 知识分子</option>
                            <option value="官宦世家">🎩 官宦世家</option>
                            <option value="富豪家庭">💰 富豪家庭</option>
                            <option value="艺术世家">🎨 艺术世家</option>
                            <option value="单亲家庭">👤 单亲家庭</option>
                            <option value="重组家庭">🔄 重组家庭</option>
                        </select>
                        <div class="form-text" id="familyDesc">完全随机生成初始属性</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" onclick="startLife()">
                            🎲 开始新人生
                        </button>
                        <button class="btn btn-outline-secondary" onclick="showHistory()">
                            📜 历史回顾
                        </button>
                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <button class="btn btn-outline-warning" onclick="location.href='?route=life-admin'">
                            ⚙️ 管理
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 游戏界面（初始隐藏） -->
            <div id="gameArea" style="display:none;">
                <!-- 属性面板 -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                            <div class="text-center">
                                <div class="text-muted small">年龄</div>
                                <div class="fw-bold text-primary" id="ageDisplay">0</div>
                            </div>
                            <div class="text-center">
                                <div class="text-muted small">智商</div>
                                <div class="fw-bold" id="iqDisplay">50</div>
                            </div>
                            <div class="text-center">
                                <div class="text-muted small">情商</div>
                                <div class="fw-bold" id="eqDisplay">50</div>
                            </div>
                            <div class="text-center">
                                <div class="text-muted small">体质</div>
                                <div class="fw-bold" id="healthDisplay">50</div>
                            </div>
                            <div class="text-center">
                                <div class="text-muted small">财富</div>
                                <div class="fw-bold" id="wealthDisplay">50</div>
                            </div>
                            <div class="text-center">
                                <div class="text-muted small">颜值</div>
                                <div class="fw-bold" id="looksDisplay">50</div>
                            </div>
                            <div class="text-center">
                                <div class="text-muted small">运气</div>
                                <div class="fw-bold" id="luckDisplay">50</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 事件卡片 -->
                <div class="card shadow-sm mb-3" id="eventCard">
                    <div class="card-body">
                        <h5 class="card-title" id="eventTitle">事件标题</h5>
                        <p class="card-text text-muted" id="eventDesc">事件描述</p>
                    </div>
                </div>

                <!-- 选项按钮 -->
                <div id="choicesArea" class="d-flex flex-column gap-2 mb-3">
                    <!-- 动态生成选项 -->
                </div>

                <!-- 操作按钮 -->
                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-outline-primary btn-sm" id="autoPlayBtn" onclick="fastForward()">
                        ⏩ 快进10年
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="skipYear()">
                        ⏭ 跳过今年
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="endLife()">
                        ⏹ 结束人生
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="showAllAchievements()">
                        🏆 成就
                    </button>
                </div>
            </div>

            <!-- 历史记录（初始隐藏） -->
            <div id="historyArea" style="display:none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">📜 历史人生</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="hideHistory()">返回</button>
                </div>
                <div id="historyList">
                    <?php if (empty($history)): ?>
                        <div class="text-center text-muted py-5">暂无历史记录</div>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                        <div class="card shadow-sm mb-2">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($h['family_background']) ?></strong>
                                        <small class="text-muted"> · 活到 <?= $h['final_age'] ?> 岁</small>
                                        <?php if (!empty($h['gender'])): ?>
                                            <small class="text-muted"> · <?= $h['gender'] === 'female' ? '👩' : '👨' ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted"><?= date('Y-m-d', strtotime($h['end_time'])) ?></small><br>
                                        <span class="badge bg-light text-dark border">智商<?= $h['final_iq'] ?> · 财富<?= $h['final_wealth'] ?></span>
                                    </div>
                                </div>
                                <?php
                                $achSql = "SELECT a.name FROM life_user_achievements ua JOIN life_achievements a ON ua.achievement_id = a.id WHERE ua.record_id = ?";
                                $achStmt = $pdo->prepare($achSql);
                                $achStmt->execute([$h['id']]);
                                $achs = $achStmt->fetchAll(PDO::FETCH_ASSOC);
                                if ($achs): ?>
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    <?php foreach ($achs as $a): ?>
                                    <span class="badge bg-warning text-dark" style="font-size:0.75rem"><?= htmlspecialchars($a['name']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 成就展示（初始隐藏） -->
            <div id="achievementsArea" style="display:none;">
                <!-- 游戏结束后显示 -->
            </div>

        </div>
    </div>
</div>

<script>
let currentRecordId = 0;
let currentAge = 0;
let currentEventId = 0;  // 修复：正确定义 currentEventId
let autoPlayInterval = null;
const familyDescs = {
    '随机生成': '完全随机生成初始属性',
    '贫困农村': '初始财富-20，体质+10，智商-10，颜值-5',
    '普通工薪': '平衡开局，无特殊加成',
    '富裕中产': '初始财富+20，智商+5，情商+5，颜值+5',
    '知识分子': '智商+15，情商-5，初始财富-10',
    '官宦世家': '情商+10，财富+15，智商+5，颜值+5',
    '富豪家庭': '财富+30，智商+5，情商+5，颜值+10，运气+5',
    '艺术世家': '颜值+15，运气+5，智商+5，情商+5',
    '单亲家庭': '智商-5，情商-5，财富-10，运气-5',
    '重组家庭': '智商-3，情商-3，财富-5',
};

document.getElementById('familySelect').addEventListener('change', function() {
    document.getElementById('familyDesc').textContent = familyDescs[this.value] || '';
});

function startLife() {
    const family = document.getElementById('familySelect').value;
    const gender = document.querySelector('input[name="genderSelect"]:checked').value;
    const formData = new FormData();
    formData.append('action', 'start');
    formData.append('family', family);
    formData.append('gender', gender);

    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) {
            alert(data.error || '启动失败');
            return;
        }
        currentRecordId = data.record_id;
        currentAge = data.age;
        currentEventId = data.event ? data.event.id : 0;  // 修复：正确设置 currentEventId
        updateAttrs(data.attrs);
        showEvent(data.event);
        document.getElementById('startCard').style.display = 'none';
        document.getElementById('gameArea').style.display = 'block';
    })
    .catch(err => alert('网络错误：' + err));
}

function continueLife(recordId) {
    currentRecordId = recordId;
    const formData = new FormData();
    formData.append('action', 'get_status');
    formData.append('record_id', recordId);

    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) {
            alert(data.error || '恢复失败');
            return;
        }
        currentAge = data.age;
        currentEventId = data.event ? data.event.id : 0;  // 修复：正确设置 currentEventId
        updateAttrs(data.attrs);
        showEvent(data.event);
        document.getElementById('startCard').style.display = 'none';
        document.getElementById('gameArea').style.display = 'block';
    })
    .catch(err => alert('网络错误：' + err));
}

function showEvent(event) {
    if (!event || event.id === 0) {
        document.getElementById('eventTitle').textContent = event ? event.title : '平凡的一年';
        document.getElementById('eventDesc').textContent = event ? event.description : '这一年，你平平淡淡地过着日子。';
        currentEventId = 0;
    } else {
        document.getElementById('eventTitle').textContent = event.title;
        document.getElementById('eventDesc').textContent = event.description;
        currentEventId = event.id;  // 修复：正确设置 currentEventId
    }

    const area = document.getElementById('choicesArea');
    area.innerHTML = '';

    if (event && event.choices) {
        const choices = JSON.parse(event.choices || '[]');
        choices.forEach((c, idx) => {
            const btn = document.createElement('button');
            btn.className = 'btn btn-outline-primary text-start p-3';
            const dieHint = c.die ? '<div class="text-danger small">⚠️ 此选择将结束人生</div>' : '';
            btn.innerHTML = `<div class="fw-bold">${c.text}</div>${dieHint}`;
            // 隐藏效果数值，增加悬念（只显示方向：↑↓ 或不显示）
            if (c.effects) {
                const effects = [];
                for (const [k, v] of Object.entries(c.effects)) {
                    const name = {'iq':'智商','eq':'情商','health':'体质','wealth':'财富','looks':'颜值','luck':'运气'}[k] || k;
                    effects.push(`${name}${v > 0 ? ' ↑' : v < 0 ? ' ↓' : ''}`);
                }
                if (effects.length) {
                    btn.innerHTML += `<div class="small text-muted">${effects.join('，')}</div>`;
                }
            }
            btn.onclick = () => choose(idx);
            area.appendChild(btn);
        });
    } else {
        // 通用事件，只有一个"继续"选项
        const btn = document.createElement('button');
        btn.className = 'btn btn-primary';
        btn.textContent = '继续 →';
        btn.onclick = () => choose(0);
        area.appendChild(btn);
    }
}

function choose(choiceIdx) {
    const formData = new FormData();
    formData.append('action', 'choose');
    formData.append('record_id', currentRecordId);
    formData.append('event_id', currentEventId);  // 修复：使用 currentEventId
    formData.append('choice_idx', choiceIdx);

    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) {
            alert(data.error || '操作失败');
            return;
        }
        currentAge = data.age;
        currentEventId = data.event ? data.event.id : 0;  // 修复：正确更新 currentEventId
        updateAttrs(data.attrs);

        if (data.finished) {
            showFinish(data);
        } else {
            showEvent(data.event);
        }
    })
    .catch(err => alert('网络错误：' + err));
}

function updateAttrs(attrs) {
    document.getElementById('ageDisplay').textContent = currentAge;
    document.getElementById('iqDisplay').textContent = attrs.iq;
    document.getElementById('eqDisplay').textContent = attrs.eq;
    document.getElementById('healthDisplay').textContent = attrs.health;
    document.getElementById('wealthDisplay').textContent = attrs.wealth;
    document.getElementById('looksDisplay').textContent = attrs.looks;
    document.getElementById('luckDisplay').textContent = attrs.luck;
}

function fastForward() {
    const btn = document.getElementById('autoPlayBtn');
    if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
        autoPlayInterval = null;
        btn.innerHTML = '⏩ 快进10年';
        btn.className = 'btn btn-outline-primary btn-sm';
        return;
    }
    btn.innerHTML = '⏸ 停止快进';
    btn.className = 'btn btn-warning btn-sm';
    let yearsLeft = 10;
    autoPlayInterval = setInterval(() => {
        if (!currentRecordId || yearsLeft <= 0) {
            clearInterval(autoPlayInterval);
            autoPlayInterval = null;
            btn.innerHTML = '⏩ 快进10年';
            btn.className = 'btn btn-outline-primary btn-sm';
            return;
        }
        yearsLeft--;
        choose(0);
    }, 600);
}

function skipYear() {
    const formData = new FormData();
    formData.append('action', 'choose');
    formData.append('record_id', currentRecordId);
    formData.append('event_id', currentEventId);
    formData.append('choice_idx', 0);
    fetch('?route=life-api', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.ok && !data.finished) {
            currentAge = data.age;
            currentEventId = data.event ? data.event.id : 0;
            updateAttrs(data.attrs);
            showEvent(data.event);
        } else if (data.finished) {
            showFinish(data);
        }
    });
}

function endLife() {
    if (!confirm('确定要结束当前人生吗？')) return;
    const formData = new FormData();
    formData.append('action', 'end');
    formData.append('record_id', currentRecordId);
    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            showFinish(data);
        }
    });
}

function showFinish(data) {
    document.getElementById('gameArea').style.display = 'none';
    const area = document.getElementById('achievementsArea');
    area.style.display = 'block';
    
    // 修复：正确映射属性名
    const attrNames = {
        'iq': '智商',
        'eq': '情商', 
        'health': '体质',
        'wealth': '财富',
        'looks': '颜值',
        'luck': '运气'
    };
    
    area.innerHTML = `
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <h3 class="mb-3">🎬 人生落幕</h3>
                <p class="text-muted">你活到了 <strong>${data.age}</strong> 岁</p>
                <div class="row g-2 my-4">
                    ${Object.entries(data.attrs).map(([k,v]) => `
                        <div class="col-6 col-md-4">
                            <div class="border rounded p-2">
                                <div class="text-muted small">${attrNames[k] || k}</div>
                                <div class="fw-bold">${v}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                ${data.achievements && data.achievements.length ? `
                    <h5 class="mt-4 mb-3">🏆 解锁成就</h5>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        ${data.achievements.map(a => `
                            <span class="badge bg-warning text-dark p-2">${a.name}</span>
                        `).join('')}
                    </div>
                ` : ''}
                <button class="btn btn-primary mt-4" onclick="location.reload()">再来一次</button>
            </div>
        </div>
    `;
}

function showHistory() {
    document.getElementById('startCard').style.display = 'none';
    document.getElementById('historyArea').style.display = 'block';
}

function hideHistory() {
    document.getElementById('historyArea').style.display = 'none';
    document.getElementById('startCard').style.display = 'block';
}

function showAllAchievements() {
    // 加载累计成就
    fetch('?route=life-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_all_achievements'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) { alert(data.error); return; }
        const total = data.achievements.length;
        const unlocked = data.unlocked_list;
        let html = `<div class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">🏆 成就殿堂（已解锁 ${unlocked.length}/${total}）</h5>
                        <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                    </div>
                    <div class="modal-body" style="max-height:60vh;overflow-y:auto">
                        <div class="row g-2">`;
        data.achievements.forEach(a => {
            const isUnlocked = unlocked.includes(a.id);
            html += `<div class="col-6 col-md-4">
                <div class="card ${isUnlocked ? 'border-warning bg-warning-subtle' : 'border-secondary opacity-50'} p-2 h-100">
                    <div class="fw-bold">${isUnlocked ? a.name : '🔒 ???'}</div>
                    <div class="small text-muted">${a.description}</div>
                    ${a.unlock_count ? `<div class="small mt-1">累计解锁 ${a.unlock_count} 次</div>` : ''}
                </div>
            </div>`;
        });
        html += `</div></div></div></div></div>`;
        document.body.insertAdjacentHTML('beforeend', html);
    });
}
</script>
