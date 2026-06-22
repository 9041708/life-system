<?php
// templates/entertainment/life_admin.php
// 我的人生 - 管理后台
?>
<div class="container-fluid px-3 py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">⚙️ 我的人生 - 管理后台</h2>
                <button class="btn btn-outline-secondary btn-sm" onclick="location.href='?route=life'">← 返回游戏</button>
            </div>

            <!-- 配置面板 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">⚙️ 配置管理</h5>
                </div>
                <div class="card-body">
                    <?php
                    $configLabels = [
                        'initial_iq_range'      => '初始智商范围（逗号分隔最小值,最大值）',
                        'initial_eq_range'      => '初始情商范围（逗号分隔最小值,最大值）',
                        'initial_health_range'  => '初始体质范围（逗号分隔最小值,最大值）',
                        'initial_wealth_range'  => '初始财富范围（逗号分隔最小值,最大值）',
                        'initial_looks_range'   => '初始颜值范围（逗号分隔最小值,最大值）',
                        'initial_luck_range'    => '初始运气范围（逗号分隔最小值,最大值）',
                        'max_age'               => '最大年龄（达到此年龄自动结束人生）',
                    ];
                    ?>
                    <form id="configForm" class="row g-3">
                        <?php foreach ($configs as $key => $value): ?>
                        <?php if (isset($configLabels[$key])): ?>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold"><?= $configLabels[$key] ?></label>
                            <input type="text" class="form-control form-control-sm" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <div class="col-12">
                            <button type="button" class="btn btn-primary btn-sm" onclick="saveConfig()">保存配置</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 事件管理 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📋 事件库管理</h5>
                    <button class="btn btn-primary btn-sm" onclick="showEventModal()">+ 新增事件</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>年龄范围</th>
                                    <th>标题</th>
                                    <th>性别</th>
                                    <th>启用</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $ev): ?>
                                <tr>
                                    <td><?= $ev['id'] ?></td>
                                    <td><?= $ev['age_min'] ?>-<?= $ev['age_max'] ?>岁</td>
                                    <td><?= htmlspecialchars($ev['title']) ?></td>
                                    <td><?= $ev['gender'] === 'all' ? '不限' : ($ev['gender'] === 'male' ? '男' : '女') ?></td>
                                    <td>
                                        <span class="badge <?= $ev['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $ev['is_active'] ? '是' : '否' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0" onclick="editEvent(<?= $ev['id'] ?>)">编辑</button>
                                        <button class="btn btn-sm btn-outline-danger py-0" onclick="deleteEvent(<?= $ev['id'] ?>)">删除</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 成就管理 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🏆 成就管理</h5>
                    <button class="btn btn-primary btn-sm" onclick="showAchievementModal()">+ 新增成就</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>成就名称</th>
                                    <th>描述</th>
                                    <th>排序</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($achievements as $ach): ?>
                                <tr>
                                    <td><?= $ach['id'] ?></td>
                                    <td><?= htmlspecialchars($ach['name']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($ach['description']) ?></td>
                                    <td><?= $ach['sort_order'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0" onclick="editAchievement(<?= $ach['id'] ?>)">编辑</button>
                                        <button class="btn btn-sm btn-outline-danger py-0" onclick="deleteAchievement(<?= $ach['id'] ?>)">删除</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- 事件编辑模态框 -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">新增事件</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="eventId" value="0">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small">年龄下限</label>
                            <input type="number" class="form-control form-control-sm" id="eventAgeMin" value="0" min="0" max="100">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">年龄上限</label>
                            <input type="number" class="form-control form-control-sm" id="eventAgeMax" value="100" min="0" max="100">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">适用性别</label>
                            <select class="form-select form-select-sm" id="eventGender">
                                <option value="all">不限</option>
                                <option value="male">男</option>
                                <option value="female">女</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">原生家庭过滤</label>
                            <select class="form-select form-select-sm" id="eventFamily">
                                <option value="">不限</option>
                                <option value="贫困农村">贫困农村</option>
                                <option value="普通工薪">普通工薪</option>
                                <option value="富裕中产">富裕中产</option>
                                <option value="知识分子">知识分子</option>
                                <option value="官宦世家">官宦世家</option>
                                <option value="富豪家庭">富豪家庭</option>
                                <option value="艺术世家">艺术世家</option>
                                <option value="单亲家庭">单亲家庭</option>
                                <option value="重组家庭">重组家庭</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">事件标题</label>
                            <input type="text" class="form-control" id="eventTitle" placeholder="事件标题">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">事件描述</label>
                            <textarea class="form-control" id="eventDesc" rows="3" placeholder="事件描述"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">选项（JSON格式）</label>
                            <textarea class="form-control font-monospace" id="eventChoices" rows="6" placeholder='[{"text":"选项1","effects":{"iq":5}},{"text":"选项2","effects":{"eq":3}}]'></textarea>
                            <div class="form-text">格式：[{"text":"选项文字","effects":{"属性":"变化值"}}]</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="eventActive" checked>
                                <label class="form-check-label small" for="eventActive">启用</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveEvent()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 成就编辑模态框 -->
<div class="modal fade" id="achievementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="achievementModalTitle">新增成就</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="achievementForm">
                    <input type="hidden" id="achievementId" value="0">
                    <div class="mb-3">
                        <label class="form-label small">成就名称（含emoji）</label>
                        <input type="text" class="form-control" id="achievementName" placeholder="💰 百万富翁">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">成就描述</label>
                        <input type="text" class="form-control" id="achievementDesc" placeholder="财富达到90+">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">解锁条件（JSON格式）</label>
                        <textarea class="form-control font-monospace" id="achievementCondition" rows="3" placeholder='{"wealth":{"min":90}}'></textarea>
                        <div class="form-text">格式：{"属性":{"min":最小值}} 或 {"属性":{"max":最大值}}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">排序</label>
                        <input type="number" class="form-control" id="achievementSort" value="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveAchievement()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
// 配置保存
function saveConfig() {
    const form = document.getElementById('configForm');
    const formData = new FormData(form);
    formData.append('action', 'admin_save_config');

    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('配置保存成功');
            location.reload();
        } else {
            alert(data.error || '保存失败');
        }
    })
    .catch(err => alert('网络错误：' + err));
}

// 事件管理
function showEventModal(id = 0) {
    document.getElementById('eventModalTitle').textContent = id > 0 ? '编辑事件' : '新增事件';
    document.getElementById('eventId').value = id;

    if (id > 0) {
        // 加载事件数据（需要后端提供API）
        alert('编辑功能需后端提供API，当前仅支持新增');
        return;
    } else {
        document.getElementById('eventForm').reset();
        document.getElementById('eventActive').checked = true;
    }

    new bootstrap.Modal(document.getElementById('eventModal')).show();
}

function saveEvent() {
    const id = document.getElementById('eventId').value;
    const choices = document.getElementById('eventChoices').value;

    // 验证JSON格式
    try {
        JSON.parse(choices);
    } catch (e) {
        alert('选项JSON格式错误：' + e.message);
        return;
    }

    const formData = new FormData();
    formData.append('action', 'admin_save_event');
    formData.append('id', id);
    formData.append('age_min', document.getElementById('eventAgeMin').value);
    formData.append('age_max', document.getElementById('eventAgeMax').value);
    formData.append('gender', document.getElementById('eventGender').value);
    formData.append('family_background', document.getElementById('eventFamily').value);
    formData.append('title', document.getElementById('eventTitle').value);
    formData.append('description', document.getElementById('eventDesc').value);
    formData.append('choices', choices);
    formData.append('is_active', document.getElementById('eventActive').checked ? 1 : 0);

    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('保存成功');
            location.reload();
        } else {
            alert(data.error || '保存失败');
        }
    })
    .catch(err => alert('网络错误：' + err));
}

function deleteEvent(id) {
    if (!confirm('确定要删除这个事件吗？')) return;

    const formData = new FormData();
    formData.append('action', 'admin_delete_event');
    formData.append('id', id);

    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('删除成功');
            location.reload();
        } else {
            alert(data.error || '删除失败');
        }
    })
    .catch(err => alert('网络错误：' + err));
}

// 成就管理
function showAchievementModal(id = 0) {
    document.getElementById('achievementModalTitle').textContent = id > 0 ? '编辑成就' : '新增成就';
    document.getElementById('achievementId').value = id;

    if (id > 0) {
        alert('编辑功能需后端提供API，当前仅支持新增');
        return;
    } else {
        document.getElementById('achievementForm').reset();
    }

    new bootstrap.Modal(document.getElementById('achievementModal')).show();
}

function saveAchievement() {
    const id = document.getElementById('achievementId').value;
    const condition = document.getElementById('achievementCondition').value;

    // 验证JSON格式
    try {
        JSON.parse(condition);
    } catch (e) {
        alert('条件JSON格式错误：' + e.message);
        return;
    }

    const formData = new FormData();
    formData.append('action', 'admin_save_achievement');
    formData.append('id', id);
    formData.append('name', document.getElementById('achievementName').value);
    formData.append('description', document.getElementById('achievementDesc').value);
    formData.append('condition_json', condition);
    formData.append('sort_order', document.getElementById('achievementSort').value);

    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('保存成功');
            location.reload();
        } else {
            alert(data.error || '保存失败');
        }
    })
    .catch(err => alert('网络错误：' + err));
}

function deleteAchievement(id) {
    if (!confirm('确定要删除这个成就吗？')) return;

    const formData = new FormData();
    formData.append('action', 'admin_delete_achievement');
    formData.append('id', id);

    fetch('?route=life-api', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('删除成功');
            location.reload();
        } else {
            alert(data.error || '删除失败');
        }
    })
    .catch(err => alert('网络错误：' + err));
}
</script>
