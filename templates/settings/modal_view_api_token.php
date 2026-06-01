<!-- 查看 API Token 弹窗（邮箱验证码身份验证） -->
<div class="modal fade" id="modalViewApiToken" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title small">查看 API Token（身份验证）</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tvTokenId" value="">

                <!-- 邮箱验证码 -->
                <div class="mb-2">
                    <label class="form-label small">绑定邮箱</label>
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES) ?>" disabled>
                </div>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" id="tvEmailCode" class="form-control" placeholder="邮箱验证码">
                    <button type="button" class="btn btn-outline-secondary" id="tvSendEmailCodeBtn">发送验证码</button>
                </div>
                <div id="tvEmailError" class="alert alert-danger py-1 small d-none"></div>

                <!-- 验证通过后展示 Token -->
                <div id="tvTokenResult" class="d-none mt-2">
                    <div class="alert alert-success py-1 small mb-2">身份验证通过，请复制保存 Token：</div>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" id="tvTokenInput" class="form-control" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="tvCopyTokenBtn">复制</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="tvVerify()">验证并查看</button>
            </div>
        </div>
    </div>
</div>

<script>
/* 查看 Token 弹窗 JS */
var tvSendCooldown = 0;

function openViewTokenModal(tokenId) {
    document.getElementById('tvTokenId').value = tokenId;
    document.getElementById('tvTokenResult').classList.add('d-none');
    document.getElementById('tvEmailError').classList.add('d-none');
    document.getElementById('tvEmailCode').value = '';
    var modal = new bootstrap.Modal(document.getElementById('modalViewApiToken'));
    modal.show();
}

function tvVerify() {
    var tokenId = document.getElementById('tvTokenId').value;
    var code = document.getElementById('tvEmailCode').value;
    if (!code) { showTvError('tvEmailError', '请输入邮箱验证码'); return; }

    var body = new URLSearchParams();
    body.append('action', 'verify_token_view');
    body.append('token_id', tokenId);
    body.append('verify_type', 'email');
    body.append('verify_value', code);

    fetch(window.location.pathname + window.location.search, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
        body: body
    })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                document.getElementById('tvTokenInput').value = data.token;
                document.getElementById('tvTokenResult').classList.remove('d-none');
            } else {
                showTvError('tvEmailError', data.error || '验证失败');
            }
        })
        .catch(function() { showTvError('tvEmailError', '请求失败，请重试'); });
}

function tvSendEmailCode() {
    var btn = document.getElementById('tvSendEmailCodeBtn');
    if (btn.disabled) return;
    btn.disabled = true;
    tvSendCooldown = 60;
    var cooldownTimer = setInterval(function() {
        tvSendCooldown--;
        if (tvSendCooldown <= 0) {
            clearInterval(cooldownTimer);
            btn.disabled = false;
            btn.textContent = '发送验证码';
        } else {
            btn.textContent = tvSendCooldown + 's';
        }
    }, 1000);

    var body = new URLSearchParams();
    body.append('action', 'send_token_view_code');
    fetch(window.location.pathname + window.location.search, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
        body: body
    })
    .then(function(r) {
        if (!r.ok) { return r.text().then(function(t) { throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 200)); }); }
        return r.json();
    })
    .then(function(data) {
        if (data.ok) {
            showTvError('tvEmailError', '');
            document.getElementById('tvEmailError').classList.add('d-none');
        } else {
            showTvError('tvEmailError', data.error || '发送失败');
        }
    })
    .catch(function(e) { showTvError('tvEmailError', '发送失败：' + e.message); });
}

function tvCopyToken(btn) {
    var input = document.getElementById('tvTokenInput');
    input.select();
    document.execCommand('copy');
    var prev = btn.textContent;
    btn.textContent = '已复制';
    setTimeout(function() { btn.textContent = prev; }, 2000);
}

function showTvError(id, msg) {
    var el = document.getElementById(id);
    if (el) { el.textContent = msg; el.classList.remove('d-none'); }
}

// 绑定按钮事件
document.addEventListener('DOMContentLoaded', function() {
    var sendBtn = document.getElementById('tvSendEmailCodeBtn');
    if (sendBtn) {
        sendBtn.addEventListener('click', tvSendEmailCode);
    }
    var copyBtn = document.getElementById('tvCopyTokenBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() { tvCopyToken(this); });
    }
});
</script>
