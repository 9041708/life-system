<?php
/** @var array $accounts */
/** @var array $logs */
?>
<style>
.forum-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: box-shadow 0.15s, background 0.3s, border-color 0.3s;
    background: rgba(255, 255, 255, 0.42);
    backdrop-filter: blur(16px) saturate(130%);
    -webkit-backdrop-filter: blur(16px) saturate(130%);
}
.forum-card:hover {
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12);
    background: rgba(255, 255, 255, 0.58);
}
.forum-card .forum-name { font-weight: 600; font-size: 15px; margin-bottom: 4px; }
.forum-card .forum-meta { font-size: 13px; color: #666; line-height: 1.8; }
.forum-card .forum-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
.forum-card .forum-actions .btn { font-size: 12px; }
/* 暗黑模式 */
body.theme-dark .forum-card {
    background: rgba(30, 41, 59, 0.45);
    border-color: rgba(148, 163, 184, 0.2);
}
body.theme-dark .forum-card:hover {
    background: rgba(30, 41, 59, 0.58);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
}
body.theme-dark .forum-card .forum-meta { color: #94a3b8; }
.badge-switch {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    margin-right: 4px;
}
.badge-switch.on { background: #d4edda; color: #155724; }
.badge-switch.off { background: #f8f9fa; color: #999; }
body.theme-dark .badge-switch.on { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
body.theme-dark .badge-switch.off { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
.log-item {
    padding: 8px 12px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    font-size: 13px;
}
.log-item:last-child { border-bottom: none; }
body.theme-dark .log-item { border-bottom-color: rgba(148, 163, 184, 0.1); }
.log-type {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 11px;
    margin-right: 6px;
}
.log-type.signin { background: #cce5ff; color: #004085; }
.log-type.reply { background: #d4edda; color: #155724; }
.log-type.notice { background: #fff3cd; color: #856404; }
.log-type.error { background: #f8d7da; color: #721c24; }
.log-type.login { background: #e2e3e5; color: #383d41; }
.thread-item {
    padding: 8px 12px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 6px;
    margin-bottom: 4px;
    transition: all 0.15s;
}
.thread-item:hover { background: rgba(15, 23, 42, 0.04); border-color: #007bff; }
.thread-item:has(.thread-check:checked) { background: rgba(0, 123, 255, 0.08); border-color: #007bff; }
.thread-check { cursor: pointer; }
body.theme-dark .thread-item { border-color: rgba(148, 163, 184, 0.2); }
body.theme-dark .thread-item:hover { background: rgba(148, 163, 184, 0.1); }
body.theme-dark .thread-item:has(.thread-check:checked) { background: rgba(59, 130, 246, 0.15); border-color: #3b82f6; }
#replyProgressText {
    font-size: 12px;
    line-height: 1.8;
    max-height: 150px;
    overflow-y: auto;
    background: rgba(15, 23, 42, 0.04);
    border-radius: 6px;
    padding: 8px;
}
body.theme-dark #replyProgressText { background: rgba(15, 23, 42, 0.35); }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">🌐 论坛助手</h5>
    <button class="btn btn-primary btn-sm" onclick="openAccountModal()">+ 添加论坛</button>
</div>

<?php if (empty($accounts)): ?>
<div class="text-center text-muted py-5">
    <div style="font-size:48px;margin-bottom:12px">🌐</div>
    <div>暂无配置的论坛账号</div>
    <div class="small text-muted mt-1">点击右上角"添加论坛"开始使用</div>
</div>
<?php else: ?>
<div class="row" id="accountList">
    <?php foreach ($accounts as $acc): ?>
    <div class="col-12 col-md-6 col-lg-4" data-id="<?= (int)$acc['id'] ?>">
        <div class="forum-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div style="min-width:0;flex:1">
                    <div class="forum-name"><?= htmlspecialchars($acc['forum_name']) ?></div>
                    <div class="forum-meta text-truncate">
                        <span>🔗 <a href="<?= htmlspecialchars($acc['forum_url']) ?>" target="_blank" rel="noopener" class="text-decoration-none"><?= htmlspecialchars($acc['forum_url']) ?></a></span>
                    </div>
                    <div class="forum-meta">
                        <span>👤 <?= htmlspecialchars($acc['username']) ?></span>
                    </div>
                </div>
                <div class="d-flex gap-1 ms-2 flex-shrink-0">
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?= (int)$acc['id'] ?>)" title="编辑">✏️</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAccount(<?= (int)$acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['forum_name'])) ?>')" title="删除">🗑</button>
                </div>
            </div>
            <div class="mb-3">
                <span class="badge-switch <?= $acc['enable_notice'] ? 'on' : 'off' ?>">
                    <?= $acc['enable_notice'] ? '✓' : '✗' ?> 通知
                </span>
                <span class="badge-switch <?= $acc['enable_signin'] ? 'on' : 'off' ?>">
                    <?= $acc['enable_signin'] ? '✓' : '✗' ?> 签到
                </span>
                <span class="badge-switch <?= $acc['enable_autoreply'] ? 'on' : 'off' ?>">
                    <?= $acc['enable_autoreply'] ? '✓' : '✗' ?> 回帖
                </span>
            </div>
            <div class="forum-actions">
                <button class="btn btn-sm btn-outline-success" onclick="doSignin(<?= (int)$acc['id'] ?>)" id="btnSignin<?= (int)$acc['id'] ?>">
                    📅 签到
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="doCheckNotice(<?= (int)$acc['id'] ?>)" id="btnNotice<?= (int)$acc['id'] ?>">
                    🔔 通知
                </button>
                <button class="btn btn-sm btn-outline-warning" onclick="openReplyModal(<?= (int)$acc['id'] ?>)" id="btnReply<?= (int)$acc['id'] ?>">
                    💬 回帖
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="testConnection(<?= (int)$acc['id'] ?>)" id="btnTest<?= (int)$acc['id'] ?>">
                    🔌 测试
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card glass-card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>📋 操作日志 <small class="text-muted" id="logRefreshHint">(每30秒自动刷新)</small></span>
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-primary" onclick="refreshLogs()">刷新</button>
            <button class="btn btn-sm btn-outline-danger" onclick="clearLogs()">清除全部</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="cleanOldLogs()">清理3天前</button>
        </div>
    </div>
    <div class="card-body p-0" style="max-height:600px;overflow-y:auto" id="logContainer">
        <?php if (empty($logs)): ?>
        <div class="text-center text-muted py-4" id="logEmpty">暂无操作日志</div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
            <div class="log-item">
                <span class="log-type <?= htmlspecialchars($log['action_type']) ?>"><?= $log['action_type'] === 'signin' ? '签到' : ($log['action_type'] === 'reply' ? '回帖' : ($log['action_type'] === 'notice' ? '通知' : ($log['action_type'] === 'login' ? '登录' : '错误'))) ?></span>
                <?php if (!empty($log['forum_name'])): ?>
                <span class="text-primary">[<?= htmlspecialchars($log['forum_name']) ?>]</span>
                <?php endif; ?>
                <span><?= htmlspecialchars($log['result']) ?></span>
                <?php if (!empty($log['target_info'])): ?>
                <span class="text-muted"> - <?= htmlspecialchars($log['target_info']) ?></span>
                <?php endif; ?>
                <small class="text-muted float-end"><?= htmlspecialchars(substr($log['created_at'], 5, 11)) ?></small>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalTitle">添加论坛</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="accountForm">
                    <input type="hidden" name="id" id="acc_id">
                    <input type="hidden" name="action" id="acc_action" value="create">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">论坛名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="forum_name" id="acc_forum_name" required placeholder="如: 我的论坛">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">论坛地址 <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" name="forum_url" id="acc_forum_url" required placeholder="https://example.com">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">用户名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" id="acc_username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">密码 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="acc_password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()" id="btnTogglePwd">显示</button>
                            </div>
                            <small class="text-muted" id="passwordHint"></small>
                        </div>
                    </div>
                    <hr>
                    <h6>自动化设置</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_notice" id="acc_enable_notice">
                                <label class="form-check-label" for="acc_enable_notice">接收通知</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">通知检查间隔(分钟)</label>
                            <input type="number" class="form-control" name="notice_interval" id="acc_notice_interval" value="15" min="5" max="1440">
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_mention_reply" id="acc_enable_mention_reply">
                                <label class="form-check-label" for="acc_enable_mention_reply">@提及自动回复</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">@回复模式</label>
                            <select class="form-select" name="mention_reply_mode" id="acc_mention_reply_mode">
                                <option value="ai">AI 智能回复</option>
                                <option value="random">随机语录</option>
                                <option value="custom">自定义内容</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_signin" id="acc_enable_signin">
                                <label class="form-check-label" for="acc_enable_signin">自动签到</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_autoreply" id="acc_enable_autoreply">
                                <label class="form-check-label" for="acc_enable_autoreply">自动回帖</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">签到时间</label>
                            <input type="time" class="form-control" name="signin_time" id="acc_signin_time" value="08:00">
                            <small class="text-muted">每天在此时间点自动签到</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">回帖起始时间</label>
                            <input type="time" class="form-control" name="reply_time" id="acc_reply_time" value="09:00">
                            <small class="text-muted">从此时开始自动回帖</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">签到页面链接</label>
                            <input type="text" class="form-control" name="signin_url" id="acc_signin_url" placeholder="如: https://www.niumabbs.com/plugin.php?id=nm_achievement_master&op=signin_page">
                            <small class="text-muted">填写论坛签到页面的完整URL，系统会自动解析签到表单并提交。留空则自动检测常见签到插件。</small>
                        </div>
                    </div>
                    <hr>
                    <h6>回帖设置</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">回帖模式</label>
                            <select class="form-select" name="reply_mode" id="acc_reply_mode" onchange="toggleReplyMode()">
                                <option value="random">随机鸡汤</option>
                                <option value="custom">自定义内容</option>
                                <option value="smart">智能回复(AI)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">手动回帖间隔(秒)</label>
                            <input type="number" class="form-control" name="reply_interval" id="acc_reply_interval" value="10" min="5" max="300">
                            <small class="text-muted">手动点击回帖时的等待时间</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">自动回帖间隔(分钟)</label>
                            <input type="number" class="form-control" name="auto_reply_interval" id="acc_auto_reply_interval" value="30" min="5" max="1440">
                            <small class="text-muted">自动回帖每隔多久执行一次</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">AI回帖标识</label>
                            <input type="text" class="form-control" name="ai_reply_flag" id="acc_ai_reply_flag" value="[AI回帖]">
                        </div>
                    </div>
                    <div class="mb-3" id="customReplyGroup" style="display:none">
                        <label class="form-label">自定义回帖内容 <small class="text-muted">(每条一行，随机选择)</small></label>
                        <textarea class="form-control" name="custom_reply" id="acc_custom_reply" rows="5" placeholder="第一条回帖内容&#10;第二条回帖内容&#10;第三条回帖内容"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveAccount()">保存</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">自助回帖</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">选择帖子 <small class="text-muted" id="threadSelectedCount"></small></label>
                    <div class="input-group mb-2">
                        <input type="number" class="form-control" id="manualTid" placeholder="输入帖子TID">
                        <button class="btn btn-outline-primary" onclick="loadThreads()">加载帖子列表</button>
                    </div>
                    <div class="mb-2 d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="selectAllThreads(true)">全选</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="selectAllThreads(false)">取消全选</button>
                        <button class="btn btn-sm btn-outline-info" onclick="selectUnrepliedOnly()">仅选未回复</button>
                    </div>
                    <div id="threadList" style="max-height:300px;overflow-y:auto"></div>
                </div>
                <div id="replyProgress" style="display:none">
                    <label class="form-label">回帖进度</label>
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="replyProgressBar" style="width:0%">0%</div>
                    </div>
                    <div id="replyProgressText" style="max-height:150px;overflow-y:auto;font-size:12px"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="btnDoReply" onclick="doBatchReply()" disabled>确认回帖</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="noticeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">论坛通知</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="noticeContent">
                <div class="text-center text-muted py-3">加载中...</div>
            </div>
        </div>
    </div>
</div>

<script>
var API = '/public/index.php?route=toolbox-forum-assistant-api';
var currentReplyAccountId = 0;
var currentReplyInterval = 10;
var selectedThreads = [];
var allThreads = [];

function togglePassword() {
    var pwdInput = document.getElementById('acc_password');
    var btn = document.getElementById('btnTogglePwd');
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        btn.textContent = '隐藏';
    } else {
        pwdInput.type = 'password';
        btn.textContent = '显示';
    }
}

function showToast(msg, type) {
    var bg, icon;
    if (type === 'error') { bg = '#dc3545'; icon = '✗'; }
    else if (type === 'success') { bg = '#198754'; icon = '✓'; }
    else { bg = '#333'; icon = ''; }
    
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;z-index:9999;pointer-events:none;';
    var box = document.createElement('div');
    box.style.cssText = 'background:' + bg + ';color:#fff;padding:16px 32px;border-radius:10px;font-size:15px;box-shadow:0 4px 20px rgba(0,0,0,0.3);pointer-events:auto;opacity:0;transform:scale(0.8);transition:all 0.2s ease;text-align:center;max-width:80vw;';
    box.innerHTML = (icon ? '<span style="font-size:20px;margin-right:8px">' + icon + '</span>' : '') + msg;
    overlay.appendChild(box);
    document.body.appendChild(overlay);
    
    requestAnimationFrame(function() {
        box.style.opacity = '1';
        box.style.transform = 'scale(1)';
    });
    
    setTimeout(function() {
        box.style.opacity = '0';
        box.style.transform = 'scale(0.8)';
        setTimeout(function() { overlay.remove(); }, 200);
    }, 2000);
}

function openAccountModal(id) {
    var modal = new bootstrap.Modal(document.getElementById('accountModal'));
    document.getElementById('accountForm').reset();
    document.getElementById('acc_id').value = '';
    document.getElementById('acc_action').value = 'create';
    document.getElementById('accountModalTitle').textContent = '添加论坛';
    document.getElementById('passwordHint').textContent = '';
    document.getElementById('acc_password').required = true;
    document.getElementById('acc_password').type = 'password';
    document.getElementById('btnTogglePwd').textContent = '显示';
    document.getElementById('customReplyGroup').style.display = 'none';

    if (id) {
        document.getElementById('acc_action').value = 'update';
        document.getElementById('accountModalTitle').textContent = '编辑论坛';
        document.getElementById('acc_password').required = false;
        document.getElementById('passwordHint').textContent = '留空则不修改密码';

        fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get&id=' + id
        })
        .then(function(r) { return r.text(); })
        .then(function(text) {
            var d;
            try {
                d = JSON.parse(text);
            } catch(e) {
                showToast('服务器返回异常，无法加载数据', 'error');
                return;
            }
            if (d.ok) {
                var a = d.account;
                document.getElementById('acc_id').value = a.id || '';
                document.getElementById('acc_forum_name').value = a.forum_name || '';
                document.getElementById('acc_forum_url').value = a.forum_url || '';
                document.getElementById('acc_username').value = a.username || '';
                document.getElementById('acc_enable_notice').checked = !!parseInt(a.enable_notice);
                document.getElementById('acc_notice_interval').value = a.notice_interval || 15;
                document.getElementById('acc_enable_mention_reply').checked = !!parseInt(a.enable_mention_reply);
                document.getElementById('acc_mention_reply_mode').value = a.mention_reply_mode || 'ai';
                document.getElementById('acc_enable_signin').checked = !!parseInt(a.enable_signin);
                document.getElementById('acc_enable_autoreply').checked = !!parseInt(a.enable_autoreply);
                document.getElementById('acc_reply_mode').value = a.reply_mode || 'random';
                document.getElementById('acc_custom_reply').value = a.custom_reply || '';
                document.getElementById('acc_ai_reply_flag').value = a.ai_reply_flag || '[AI回帖]';
                document.getElementById('acc_signin_time').value = (a.signin_time || '08:00:00').substring(0, 5);
                document.getElementById('acc_signin_url').value = a.signin_url || '';
                document.getElementById('acc_reply_time').value = (a.reply_time || '09:00:00').substring(0, 5);
                document.getElementById('acc_reply_interval').value = a.reply_interval || 10;
                document.getElementById('acc_auto_reply_interval').value = a.auto_reply_interval || 30;
                toggleReplyMode();
            } else {
                showToast(d.error || '加载账号信息失败', 'error');
            }
        })
        .catch(function(e) {
            showToast('网络请求失败: ' + e.message, 'error');
        });
    }
    modal.show();
}

function openEditModal(id) {
    openAccountModal(id);
}

function toggleReplyMode() {
    var mode = document.getElementById('acc_reply_mode').value;
    document.getElementById('customReplyGroup').style.display = mode === 'custom' ? 'block' : 'none';
}

function saveAccount() {
    var form = document.getElementById('accountForm');
    var formData = new FormData(form);
    formData.set('action', document.getElementById('acc_action').value);

    fetch(API, {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
            if (d.ok) {
                showToast(d.message || '保存成功');
                setTimeout(function() { location.reload(); }, 500);
            } else {
                showToast(d.error || '保存失败', 'error');
            }
        } catch(e) {
            showToast('服务器返回异常: ' + text.substring(0, 150), 'error');
        }
    })
    .catch(function(e) { showToast('请求失败: ' + e.message, 'error'); });
}

function deleteAccount(id, name) {
    if (!confirm('确定删除论坛 "' + name + '" 吗？相关日志也将被删除。')) return;
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete&id=' + id
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
            if (d.ok) {
                showToast('删除成功');
                setTimeout(function() { location.reload(); }, 500);
            } else {
                showToast(d.error || '删除失败', 'error');
            }
        } catch(e) {
            showToast('服务器返回异常: ' + text.substring(0, 150), 'error');
        }
    })
    .catch(function(e) { showToast('请求失败: ' + e.message, 'error'); });
}

function testConnection(id) {
    var btn = document.getElementById('btnTest' + id);
    btn.disabled = true;
    btn.textContent = '测试中...';
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=test&id=' + id
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        btn.disabled = false;
        btn.textContent = '🔌 测试连接';
        try {
            var d = JSON.parse(text);
            showToast(d.ok ? (d.message || '连接成功') : (d.error || '连接失败'), d.ok ? 'success' : 'error');
        } catch(e) {
            showToast('服务器返回异常: ' + text.substring(0, 150), 'error');
        }
    })
    .catch(function(e) {
        btn.disabled = false;
        btn.textContent = '🔌 测试连接';
        showToast('请求失败: ' + e.message, 'error');
    });
}

function doSignin(id) {
    var btn = document.getElementById('btnSignin' + id);
    btn.disabled = true;
    btn.textContent = '签到中...';
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=signin&id=' + id
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        btn.disabled = false;
        btn.textContent = '📅 签到';
        try {
            var d = JSON.parse(text);
            showToast(d.ok ? (d.message || '签到成功') : (d.error || '签到失败'), d.ok ? 'success' : 'error');
            if (d.ok) setTimeout(function() { location.reload(); }, 1000);
        } catch(e) {
            showToast('服务器返回异常: ' + text.substring(0, 150), 'error');
        }
    })
    .catch(function(e) {
        btn.disabled = false;
        btn.textContent = '📅 签到';
        showToast('请求失败: ' + e.message, 'error');
    });
}

function doCheckNotice(id) {
    var modal = new bootstrap.Modal(document.getElementById('noticeModal'));
    document.getElementById('noticeContent').innerHTML = '<div class="text-center text-muted py-3">加载中...</div>';
    modal.show();

    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=notice&id=' + id
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
        } catch(e) {
            document.getElementById('noticeContent').innerHTML = '<div class="text-danger text-center py-3">服务器返回异常: ' + text.substring(0, 100) + '</div>';
            return;
        }
        if (d.ok) {
            var html = '';
            if (d.unread > 0) {
                html += '<div class="text-center py-3">';
                html += '<div style="font-size:48px;margin-bottom:16px">🔔</div>';
                html += '<div style="font-size:20px;font-weight:600;margin-bottom:8px">有 ' + d.unread + ' 条未读通知</div>';
                html += '</div>';
            } else {
                html += '<div class="text-center py-3">';
                html += '<div style="font-size:48px;margin-bottom:16px">🔔</div>';
                html += '<div style="font-size:16px;color:#666">暂无新通知</div>';
                html += '</div>';
            }
            if (d.forum_url) {
                html += '<div class="text-center mt-2"><a href="' + d.forum_url + '/home.php?mod=space&do=notice" target="_blank" class="btn btn-primary">去论坛查看通知 →</a></div>';
            }
            document.getElementById('noticeContent').innerHTML = html;
            
            // 标记已查看
            lastNoticeCount[id] = d.unread;
        } else {
            var errHtml = '<div class="text-danger text-center py-3">' + (d.error || '获取失败') + '</div>';
            if (d.forum_url) {
                errHtml += '<div class="text-center mt-2"><a href="' + d.forum_url + '/home.php?mod=space&do=notice" target="_blank" class="btn btn-primary">去论坛查看通知 →</a></div>';
            }
            document.getElementById('noticeContent').innerHTML = errHtml;
        }
    })
    .catch(function(e) {
        document.getElementById('noticeContent').innerHTML = '<div class="text-danger text-center py-3">请求失败: ' + e.message + '</div>';
    });
}

function openReplyModal(id) {
    currentReplyAccountId = id;
    currentReplyInterval = 10;
    selectedThreads = [];
    document.getElementById('manualTid').value = '';
    document.getElementById('threadList').innerHTML = '';
    document.getElementById('replyProgress').style.display = 'none';
    document.getElementById('replyProgressText').innerHTML = '';
    document.getElementById('replyProgressBar').style.width = '0%';
    document.getElementById('replyProgressBar').textContent = '0%';
    document.getElementById('threadSelectedCount').textContent = '';
    document.getElementById('btnDoReply').disabled = true;

    // 获取账号的回帖间隔
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get&id=' + id
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
            if (d.ok && d.account) {
                currentReplyInterval = parseInt(d.account.reply_interval) || 10;
            }
        } catch(e) {}
    })
    .catch(function() {});

    var modal = new bootstrap.Modal(document.getElementById('replyModal'));
    modal.show();
}

function loadThreads() {
    var container = document.getElementById('threadList');
    container.innerHTML = '<div class="text-center text-muted py-2">加载中...</div>';
    selectedThreads = [];

    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_threads&id=' + currentReplyAccountId
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
        } catch(e) {
            container.innerHTML = '<div class="text-danger text-center py-2">服务器返回异常</div>';
            return;
        }
        if (d.ok && d.threads && d.threads.length > 0) {
            allThreads = d.threads;
            var html = '';
            if (d.total !== undefined) {
                html += '<div class="mb-2"><small class="text-muted">共 ' + d.total + ' 个帖子，已回复 ' + d.replied + ' 个，未回复 ' + d.unreplied + ' 个</small></div>';
            }
            d.threads.forEach(function(t) {
                html += '<div class="thread-item" data-tid="' + t.tid + '">';
                html += '<label class="d-flex align-items-center gap-2 m-0" style="cursor:pointer">';
                html += '<input type="checkbox" class="thread-check" value="' + t.tid + '" data-title="' + encodeURIComponent(t.title) + '" onchange="updateSelectedCount()">';
                html += '<span><strong>TID:' + t.tid + '</strong> ' + t.title + '</span>';
                html += '</label>';
                html += '</div>';
            });
            container.innerHTML = html;
            selectUnrepliedOnly();
        } else {
            container.innerHTML = '<div class="text-muted text-center py-2">' + (d.error || '未找到可回复的帖子（可能都已回复过）') + '</div>';
        }
    })
    .catch(function() {
        container.innerHTML = '<div class="text-danger text-center py-2">加载失败</div>';
    });
}

function selectAllThreads(checked) {
    document.querySelectorAll('.thread-check').forEach(function(cb) {
        cb.checked = checked;
    });
    updateSelectedCount();
}

function selectUnrepliedOnly() {
    document.querySelectorAll('.thread-check').forEach(function(cb) {
        cb.checked = true;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    var checked = document.querySelectorAll('.thread-check:checked');
    selectedThreads = [];
    checked.forEach(function(cb) {
        selectedThreads.push({
            tid: parseInt(cb.value),
            title: decodeURIComponent(cb.getAttribute('data-title') || '')
        });
    });
    var count = selectedThreads.length;
    document.getElementById('threadSelectedCount').textContent = count > 0 ? '(已选 ' + count + ' 个)' : '';
    document.getElementById('btnDoReply').disabled = count === 0 && !document.getElementById('manualTid').value;
}

function doBatchReply() {
    var manualTid = parseInt(document.getElementById('manualTid').value);
    var tids = selectedThreads.map(function(t) { return t.tid; });
    if (manualTid > 0 && tids.indexOf(manualTid) === -1) {
        tids.unshift(manualTid);
    }
    if (tids.length === 0) {
        showToast('请选择至少一个帖子', 'error');
        return;
    }

    var btn = document.getElementById('btnDoReply');
    btn.disabled = true;
    btn.textContent = '回帖中...';

    var progressBox = document.getElementById('replyProgress');
    var progressBar = document.getElementById('replyProgressBar');
    var progressText = document.getElementById('replyProgressText');
    progressBox.style.display = 'block';
    progressText.innerHTML = '';
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';

    var total = tids.length;
    var done = 0;
    var success = 0;
    var fail = 0;

    function doNext() {
        if (done >= total) {
            btn.textContent = '确认回帖';
            btn.disabled = false;
            progressBar.classList.remove('progress-bar-animated');
            progressText.innerHTML += '<div class="mt-2"><strong>完成！成功 ' + success + ' 个，失败 ' + fail + ' 个</strong></div>';
            refreshLogs();
            return;
        }

        var tid = tids[done];
        var pct = Math.round((done / total) * 100);
        progressBar.style.width = pct + '%';
        progressBar.textContent = pct + '%';

        progressText.innerHTML += '<div>正在回复 TID:' + tid + '...</div>';
        progressText.scrollTop = progressText.scrollHeight;

        fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=reply&id=' + currentReplyAccountId + '&tid=' + tid
        })
        .then(function(r) { 
            return r.text().then(function(text) {
                try {
                    return JSON.parse(text);
                } catch(e) {
                    throw new Error('服务器返回非JSON: ' + text.substring(0, 100));
                }
            });
        })
        .then(function(d) {
            done++;
            if (d.ok) {
                success++;
                var title = d.title || 'TID:' + tid;
                progressText.innerHTML += '<div class="text-success">✓ TID:' + tid + ' 回帖成功 - ' + title + '</div>';
            } else {
                fail++;
                progressText.innerHTML += '<div class="text-danger">✗ TID:' + tid + ' 失败: ' + (d.error || '未知错误') + '</div>';
            }
            var pct2 = Math.round((done / total) * 100);
            progressBar.style.width = pct2 + '%';
            progressBar.textContent = pct2 + '%';
            progressText.scrollTop = progressText.scrollHeight;
            
            // 如果还有下一条，显示倒计时
            if (done < total) {
                var countdownId = 'cd_' + Date.now();
                progressText.innerHTML += '<div id="' + countdownId + '" class="text-muted">⏳ 等待 ' + currentReplyInterval + ' 秒后回复下一条...</div>';
                progressText.scrollTop = progressText.scrollHeight;
                var remaining = currentReplyInterval;
                var cdTimer = setInterval(function() {
                    remaining--;
                    var el = document.getElementById(countdownId);
                    if (el && remaining > 0) {
                        el.textContent = '⏳ 等待 ' + remaining + ' 秒后回复下一条...';
                    } else {
                        clearInterval(cdTimer);
                        if (el) el.remove();
                    }
                }, 1000);
                setTimeout(doNext, currentReplyInterval * 1000);
            } else {
                doNext();
            }
        })
        .catch(function(e) {
            done++;
            fail++;
            progressText.innerHTML += '<div class="text-danger">✗ TID:' + tid + ' 请求异常: ' + e.message + '</div>';
            progressText.scrollTop = progressText.scrollHeight;
            
            // 如果还有下一条，显示倒计时
            if (done < total) {
                var countdownId = 'cd_' + Date.now();
                progressText.innerHTML += '<div id="' + countdownId + '" class="text-muted">⏳ 等待 ' + currentReplyInterval + ' 秒后回复下一条...</div>';
                progressText.scrollTop = progressText.scrollHeight;
                var remaining = currentReplyInterval;
                var cdTimer = setInterval(function() {
                    remaining--;
                    var el = document.getElementById(countdownId);
                    if (el && remaining > 0) {
                        el.textContent = '⏳ 等待 ' + remaining + ' 秒后回复下一条...';
                    } else {
                        clearInterval(cdTimer);
                        if (el) el.remove();
                    }
                }, 1000);
                setTimeout(doNext, currentReplyInterval * 1000);
            } else {
                doNext();
            }
        });
    }

    doNext();
}

function clearLogs() {
    if (!confirm('确定清除所有操作日志吗？')) return;
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=clear_logs'
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
            if (d.ok) {
                showToast(d.message || '清除成功');
                setTimeout(function() { location.reload(); }, 500);
            }
        } catch(e) {
            showToast('服务器返回异常: ' + text.substring(0, 150), 'error');
        }
    });
}

function cleanOldLogs() {
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=clean_old_logs'
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
            if (d.ok) {
                showToast(d.message || '清理成功');
                setTimeout(function() { location.reload(); }, 500);
            }
        } catch(e) {
            showToast('服务器返回异常: ' + text.substring(0, 150), 'error');
        }
    });
}

// 操作日志自动刷新
var logRefreshTimer = null;

function refreshLogs() {
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_logs'
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
        } catch(e) { return; }
        if (d.ok) {
            var container = document.getElementById('logContainer');
            if (!d.logs || d.logs.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4">暂无操作日志</div>';
                return;
            }
            var html = '';
            var typeMap = {signin:'签到', reply:'回帖', notice:'通知', login:'登录', error:'错误'};
            d.logs.forEach(function(log) {
                html += '<div class="log-item">';
                html += '<span class="log-type ' + log.action_type + '">' + (typeMap[log.action_type] || log.action_type) + '</span>';
                if (log.forum_name) html += '<span class="text-primary">[' + log.forum_name + ']</span> ';
                html += '<span>' + (log.result || '') + '</span>';
                if (log.target_info) html += ' <span class="text-muted">- ' + log.target_info + '</span>';
                if (log.forum_url && log.target_info && log.action_type === 'reply') {
                    var tid = extractTidFromLog(log);
                    if (tid) {
                        html += ' <a href="' + log.forum_url + '/forum.php?mod=viewthread&tid=' + tid + '" target="_blank" class="text-decoration-none" style="font-size:11px">查看 →</a>';
                    }
                }
                if (log.forum_url && log.action_type === 'notice' && log.result && log.result.indexOf('暂无新通知') === -1) {
                    html += ' <a href="' + log.forum_url + '/home.php?mod=space&do=notice" target="_blank" class="text-decoration-none" style="font-size:11px">查看通知 →</a>';
                }
                var timeStr = log.created_at ? log.created_at.substring(5, 16) : '';
                html += '<small class="text-muted float-end">' + timeStr + '</small>';
                html += '</div>';
            });
            container.innerHTML = html;
        }
    })
    .catch(function() {});
}

function extractTidFromLog(log) {
    if (log.target_info) {
        var m = log.target_info.match(/\[tid[:\s]*(\d+)\]/i);
        if (m) return m[1];
        m = log.target_info.match(/TID[:\s]*(\d+)/i);
        if (m) return m[1];
        m = log.target_info.match(/#(\d+)/);
        if (m) return m[1];
    }
    return '';
}

function initLogRefresh() {
    logRefreshTimer = setInterval(refreshLogs, 30000);
}

// 通知自动检查
var lastNoticeCount = {};
var noticeCheckTimer = null;

function initNoticeCheck() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

function checkAllNotices() {
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=check_notices'
    })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        try {
            var d = JSON.parse(text);
        } catch(e) { return; }
        if (d.ok && d.results) {
            d.results.forEach(function(item) {
                var prevCount = lastNoticeCount[item.id] || 0;
                if (item.unread > 0 && item.unread > prevCount && prevCount >= 0) {
                    sendBrowserNotify(item.forum_name, item.unread - prevCount);
                }
                lastNoticeCount[item.id] = item.unread;
            });
        }
    })
    .catch(function() {});
}

function sendBrowserNotify(forumName, count) {
    var title = '论坛新通知';
    var body = forumName + ' 有 ' + count + ' 条新通知';
    
    // 页面内提示
    showToast('🔔 ' + body, 'success');
    
    // 浏览器通知
    if ('Notification' in window && Notification.permission === 'granted') {
        try {
            new Notification(title, {
                body: body,
                icon: '/public/manifest.json',
                tag: 'forum-notice',
            });
        } catch(e) {}
    }
}

// 页面加载时启动
initNoticeCheck();
initLogRefresh();
</script>
