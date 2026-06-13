<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">系统设置</h2>
    <div class="small text-muted">管理个人资料、安全设置以及系统参数和用户账号。</div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
		<a class="nav-link <?= $tab === 'profile' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=profile">个人信息</a>
    </li>
    <li class="nav-item">
		<a class="nav-link <?= $tab === 'security' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=security">安全设置</a>
    </li>
        <li class="nav-item">
		<a class="nav-link <?= $tab === 'ledgers' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=ledgers">账本管理</a>
        </li>
    <li class="nav-item">
		<a class="nav-link <?= $tab === 'ai_service' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=ai_service">AI服务</a>
    </li>
    <?php if ($isAdmin): ?>
        <li class="nav-item">
			<a class="nav-link <?= $tab === 'system' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=system">系统参数</a>
        </li>
        <li class="nav-item">
			<a class="nav-link <?= $tab === 'users' ? 'active' : '' ?>" href="/public/index.php?route=settings&tab=users">用户管理</a>
        </li>
    <?php endif; ?>
</ul>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php
$miniappEnabled = \App\Service\Config::get('wechat.enable_miniapp', true);
?>

<?php if ($tab === 'profile'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">个人信息</h3>
                <div class="small text-muted">用户ID：<?= isset($currentUser['id']) ? (int)$currentUser['id'] : '-' ?></div>
            </div>
            <form method="post" enctype="multipart/form-data" class="row g-3 mb-3 align-items-center">
                <input type="hidden" name="action" value="update_avatar">
                <div class="col-12 col-md-6 d-flex align-items-center gap-3">
                    <div>
                        <?php if (!empty($currentUser['avatar_path'])): ?>
                            <img src="/uploads/<?= htmlspecialchars($currentUser['avatar_path']) ?>" alt="头像" class="rounded-circle" style="width:64px;height:64px;object-fit:cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:64px;height:64px;font-size:1.25rem;">👤</div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label small mb-1">头像</label>
                        <input type="file" name="avatar" accept="image/*" class="form-control form-control-sm">
                        <div class="form-text small">支持常见图片格式，文件大小不超过 5MB。更换头像后将自动删除旧头像文件。</div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-outline-primary">上传头像</button>
                    </div>
                </div>
            </form>

            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="update_profile">
                <div class="col-12 col-md-6">
                    <label class="form-label small d-flex justify-content-between align-items-center">
                        <span>用户名（登录账号）</span>
                        <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#modalUsernameChange">修改用户名</button>
                    </label>
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" disabled>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">昵称（展示用）</label>
                    <input type="text" name="nickname" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['nickname'] ?? '') ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small d-flex justify-content-between align-items-center">
                        <span>邮箱</span>
                        <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#modalEmailChange">换绑邮箱</button>
                    </label>
                    <input type="email" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" disabled>
                    <div class="form-text small">
                        <?= !empty($currentUser['email_verified']) ? '当前邮箱已验证，可用于登录通知和重置密码。' : '当前邮箱尚未验证，部分功能可能受限，请尽快完成验证。' ?>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">注册时间</label>
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['created_at'] ?? '') ?>" disabled>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary">保存昵称</button>
                </div>
            </form>

            <?php if ($miniappEnabled): ?>
            <?php $miniappEnabled = \App\Service\Config::get('wechat.enable_miniapp', true); ?>

            <?php if ($miniappEnabled): ?>
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small text-muted">微信小程序绑定</div>
                    <?php if (!empty($hasWechatBinding)): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success">已绑定</span>
                            <form method="post" class="d-inline" onsubmit="return confirm('确认解绑当前微信？解绑后可用新微信在小程序中登录或重新扫码绑定。');">
                                <input type="hidden" name="action" value="unbind_wechat">
                                <button type="submit" class="btn btn-sm btn-outline-danger">解绑微信</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="self_generate_bind_qr">
                            <button type="submit" class="btn btn-sm btn-outline-success">生成绑定二维码</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!empty($isMiniappUser) && !empty($hasWechatBinding)): ?>
                    <div class="small text-muted">您是通过小程序注册的账号，默认已完成微信绑定，无需重复绑定。如需更换微信，可先解绑后再在小程序中登录/绑定。</div>
                <?php elseif (!empty($hasWechatBinding)): ?>
                    <div class="small text-muted">当前账号已绑定微信<?= !empty($wechatBinding['last_login_at']) ? '，最近微信登录：' . htmlspecialchars($wechatBinding['last_login_at']) : '' ?>。如需更换微信，可解绑后在小程序中重新绑定。</div>
                <?php else: ?>
                    <div class="small text-muted">用于将当前账号与小程序绑定，便于在手机端使用同一数据。生成后请在有效期内打开小程序扫码完成绑定。</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="mt-3 pt-3 border-top">
                <form method="post" class="row g-2 align-items-center">
                    <input type="hidden" name="action" value="update_theme">
                    <?php $themeMode = $currentUser['theme_mode'] ?? ($_SESSION['theme_mode'] ?? 'light'); ?>
                    <div class="col-12 col-md-auto">
                        <label class="form-label small mb-1 mb-md-0">主题模式</label>
                    </div>
                    <div class="col-8 col-md-4 col-lg-3">
                        <select name="theme_mode" class="form-select form-select-sm">
                            <option value="light" <?= $themeMode === 'light' ? 'selected' : '' ?>>白天模式</option>
                            <option value="dark" <?= $themeMode === 'dark' ? 'selected' : '' ?>>夜间模式</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">保存主题</button>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="form-text small mt-1 mt-lg-0">更改后将在下次页面加载时应用到整个系统。</div>
                    </div>
                </form>
            </div>

            <div class="mt-3 pt-3 border-top">
                <form method="post" class="row g-2 align-items-center">
                    <input type="hidden" name="action" value="update_budget_reminder">
                    <?php $budgetReminderEnabled = isset($currentUser['budget_reminder_enabled']) ? (int)$currentUser['budget_reminder_enabled'] : 1; ?>
                    <div class="col-12 col-md-auto">
                        <label class="form-label small mb-1 mb-md-0">预算提醒</label>
                    </div>
                    <div class="col-8 col-md-4 col-lg-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="budgetReminderEnabled" name="budget_reminder_enabled" value="1" <?= $budgetReminderEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="budgetReminderEnabled">开启接近上限 / 超支提醒</label>
                        </div>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">保存设置</button>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="form-text small mt-1 mt-lg-0">关闭后，小程序和 PC 端仅展示预算数据，不再高亮或提示“接近上限 / 已超支”。</div>
                    </div>
                </form>
            </div>

            <div class="mt-3 pt-3 border-top">
                <form method="post" class="row g-2 align-items-center">
                    <input type="hidden" name="action" value="update_transfer_feature">
                    <?php $transferEnabled = isset($currentUser['enable_transfer']) ? (int)$currentUser['enable_transfer'] : 0; ?>
                    <div class="col-12 col-md-auto">
                        <label class="form-label small mb-1 mb-md-0">账户间转账功能</label>
                    </div>
                    <div class="col-8 col-md-4 col-lg-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="enableTransfer" name="enable_transfer" value="1" <?= $transferEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="enableTransfer">开启账户之间转账记录</label>
                        </div>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">保存设置</button>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="form-text small mt-1 mt-lg-0">开启后，可在记账时选择“转账”类型，并为其维护独立的分类和项目；关闭后仅保留支出 / 收入两种类型。</div>
                    </div>
                </form>
            </div>

            <div class="mt-3 pt-3 border-top">
                <form method="post" class="row g-2 align-items-center">
                    <input type="hidden" name="action" value="update_allow_negative_balance">
                    <?php $allowNegativeBalance = isset($currentUser['allow_negative_balance']) ? (int)$currentUser['allow_negative_balance'] : 0; ?>
                    <div class="col-12 col-md-auto">
                        <label class="form-label small mb-1 mb-md-0">账户余额为负数</label>
                    </div>
                    <div class="col-8 col-md-4 col-lg-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="allowNegativeBalance" name="allow_negative_balance" value="1" <?= $allowNegativeBalance ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="allowNegativeBalance">允许账户余额为负数</label>
                        </div>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">保存设置</button>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="form-text small mt-1 mt-lg-0">开启后：账户余额不足时仍可记录支出流水，账户余额会变为负数；关闭后：账户余额不足时将无法保存该支出。</div>
                    </div>
                </form>
            </div>

            <div class="mt-3 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small fw-semibold">API Token 管理</div>
                    <a href="/API_DOCUMENTATION.md" download class="btn btn-sm btn-outline-info">📄 下载 API 文档</a>
                </div>
                <div class="small text-muted mb-2">用于第三方应用或 QClaw 调用 API 时的身份认证。Token 等同于登录凭证，请妥善保管。</div>

                <?php $apiTokenCount = count($apiTokens); ?>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateApiToken" <?= $apiTokenCount >= 3 ? 'disabled' : '' ?>>+ 创建 Token</button>
                <?php if ($apiTokenCount >= 3): ?>
                    <div class="text-danger small mt-2">最多只能创建 3 个 API Token，请先撤销旧 Token。</div>
                <?php endif; ?>
                <?php if (!empty($apiTokenError)): ?>
                    <div class="alert alert-danger py-2 small mt-2"><?= htmlspecialchars($apiTokenError) ?></div>
                <?php endif; ?>

                <?php if (!empty($newApiToken)): ?>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var token = '<?= htmlspecialchars($newApiToken) ?>';
                        var modal = document.getElementById('modalViewApiToken');
                        if (modal) {
                            document.getElementById('viewApiTokenValue').value = token;
                            document.getElementById('viewApiTokenDesc').textContent = '新创建的 Token — 请立即复制保存！';
                            new bootstrap.Modal(modal).show();
                        }
        });
    </script>
<?php endif; ?>

    <script>
    </script>

                <!-- Token 列表 -->
                <?php if (!empty($apiTokens)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:30px;"><input type="checkbox" class="form-check-input" id="checkAllApiTokens" onclick="toggleAllApiTokens(this)"></th>
                                    <th>用途</th>
                                    <th>Token (前8位)</th>
                                    <th>过期时间</th>
                                    <th>最后使用</th>
                                    <th class="text-center">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apiTokens as $t): ?>
                                    <?php
                                        $isExpired = !empty($t['expires_at']) && strtotime($t['expires_at']) < time();
                                        $expiresDisplay = $isExpired ? '<span class="text-danger">已过期</span>' : (!empty($t['expires_at']) ? htmlspecialchars(date('Y-m-d', strtotime($t['expires_at']))) : '永久');
                                    ?>
                                    <tr class="<?= $isExpired ? 'table-secondary' : '' ?>">
                                        <td><input type="checkbox" class="form-check-input api-token-checkbox" name="token_ids[]" value="<?= (int)$t['id'] ?>"></td>
                                        <td class="small"><?= htmlspecialchars($t['description'] ?? '') ?></td>
                                        <td><code class="small"><?= htmlspecialchars($t['token_prefix'] ?? '') ?>****</code></td>
                                        <td class="small"><?= $expiresDisplay ?></td>
                                        <td class="small text-muted"><?= !empty($t['last_used_at']) ? htmlspecialchars($t['last_used_at']) : '从未' ?></td>
                                        <td class="text-center">
                                            <?php if (!$isExpired): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 me-1" onclick="viewApiToken(<?= (int)$t['id'] ?>, '<?= htmlspecialchars($t['description'] ?? '', ENT_QUOTES) ?>')">查看</button>
                                            <?php endif; ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('确定撤销此 Token？');">
                                                <input type="hidden" name="action" value="revoke_api_token">
                                                <input type="hidden" name="token_id" value="<?= (int)$t['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0">撤销</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="batchRevokeApiTokens()">🗑️ 批量撤销选中</button>
                    </div>
                <?php else: ?>
                    <div class="text-muted small">暂无 API Token</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- 修改用户名弹窗 -->
    <div class="modal fade" id="modalUsernameChange" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">修改用户名</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($usernameModalError)): ?>
                        <div class="alert alert-danger py-2 small mb-2"><?= htmlspecialchars($usernameModalError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($usernameModalSuccess)): ?>
                        <div class="alert alert-success py-2 small mb-2"><?= htmlspecialchars($usernameModalSuccess) ?></div>
                    <?php endif; ?>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="change_username">
                        <div class="col-12">
                            <label class="form-label small">新用户名</label>
                            <input type="text" name="new_username" class="form-control form-control-sm" value="<?= htmlspecialchars($pendingUsername !== '' ? $pendingUsername : ($currentUser['username'] ?? '')) ?>" required>
                            <div class="form-text small">建议保持原有用户名；如需修改，请先验证新用户名是否可用。</div>
                        </div>
                        <div class="col-12 d-flex justify-content-end align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-outline-secondary" name="submit_type" value="check">验证</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="suggestUsername()">使用推荐</button>
                            <button type="submit" class="btn btn-sm btn-primary" name="submit_type" value="save">确认修改</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($openModal) && $openModal === 'username'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalUsernameChange');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>
    <?php if (!empty($openModal) && $openModal === 'api_token'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalCreateApiToken');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>

    <!-- 个人绑定二维码弹窗 -->
    <div class="modal fade" id="modalBindQr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">微信绑定二维码</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2"><?= htmlspecialchars($system['bind_qr_text'] ?? '') ?></div>
                    <?php if (!empty($selfBindQrPayload) && !empty($selfBindQrToken)): ?>
                        <div class="d-flex flex-column align-items-center">
                            <div id="selfBindQr" class="border rounded mb-2" style="width:180px;height:180px;"></div>
                            <div class="small text-muted">过期时间：<?= htmlspecialchars($selfBindQrExpiresAt ?? '') ?>，绑定码：<span class="badge bg-secondary"><?= htmlspecialchars($selfBindQrToken) ?></span></div>
                        </div>
                        <script src="/assets/js/qrcode.min.js"></script>
                        <script>
                        (function(){
                            try {
                                var payload = <?= json_encode($selfBindQrPayload ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                                var el = document.getElementById('selfBindQr');
                                if (window.QRCode && typeof window.QRCode === 'function' && el) {
                                    new QRCode(el, { text: payload, width: 180, height: 180 });
                                }
                            } catch (e) { console.error(e); }
                        })();
                        </script>
                    <?php else: ?>
                        <div class="text-muted small">请点击“生成绑定二维码”按钮创建二维码。</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($openModal) && $openModal === 'bindqr'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalBindQr');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>


    <!-- 换绑邮箱弹窗 -->
    <div class="modal fade" id="modalEmailChange" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑邮箱</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="change_email">
                        <div class="col-12">
                            <?php if (!empty($emailModalError)): ?>
                                <div class="alert alert-danger py-2 small mb-2"><?= htmlspecialchars($emailModalError) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($emailModalSuccess)): ?>
                                <div class="alert alert-success py-2 small mb-2"><?= htmlspecialchars($emailModalSuccess) ?></div>
                            <?php endif; ?>
                            <label class="form-label small">邮箱地址</label>
                            <input type="email" name="new_email" class="form-control form-control-sm" value="<?= htmlspecialchars($pendingEmail !== '' ? $pendingEmail : ($currentUser['email'] ?? '')) ?>" required>
                            <div class="form-text small">直接编辑并保存即可更新邮箱，用于接收公告和密码重置邮件。</div>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-primary">保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($openModal) && $openModal === 'email'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalEmailChange');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>
<?php elseif ($tab === 'security'): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 mb-3">修改登录密码</h3>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="change_password">
                <div class="col-12">
                    <label class="form-label small d-block mb-1">旧密码</label>
                    <input type="password" name="old_password" class="form-control form-control-sm" required>
                </div>
                <div class="col-12">
                    <label class="form-label small d-block mb-1">新密码</label>
                    <input type="password" name="new_password" class="form-control form-control-sm" required>
                </div>
                <div class="col-12">
                    <label class="form-label small d-block mb-1">确认新密码</label>
                    <input type="password" name="confirm_password" class="form-control form-control-sm" required>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">建议使用至少 8 位且包含大小写字母与数字的密码。</div>
					<a href="/public/index.php?route=forgot-password" class="btn btn-link btn-sm p-0">忘记密码？</a>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary">保存密码</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h3 class="h6 mb-2">账户重置 / 注销</h3>
            <div class="small text-muted mb-3">重置会清空您的个人数据并保留账号；注销会删除账号本身且无法恢复。若账号仍关联共享账本，将禁止执行以避免误删多人数据。</div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalResetConfirm1">重置账户数据</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteConfirm1">注销账号</button>
            </div>
        </div>
    </div>

    <!-- 重置：确认 1 -->
    <div class="modal fade" id="modalResetConfirm1" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认重置账户数据</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small py-2">将清空您的个人数据（流水、账户、分类、项目、预算、目标、资产、订阅、图标、反馈等）。此操作不可撤销。</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="resetAcknowledge">
                        <label class="form-check-label small" for="resetAcknowledge">我已了解该操作会清空个人数据且无法恢复</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-sm btn-warning" id="btnResetNext" disabled>继续</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 重置：确认 2 -->
    <div class="modal fade" id="modalResetConfirm2" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">最终确认（输入 RESET）</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2">请输入 <b>RESET</b> 以继续执行重置。</div>
                    <input type="text" class="form-control form-control-sm" id="resetConfirmText" placeholder="RESET" autocomplete="off">
                    <div class="alert alert-danger small py-2 mt-2 d-none" id="resetError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-sm btn-warning" id="btnResetStart">开始重置</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 注销：确认 1 -->
    <div class="modal fade" id="modalDeleteConfirm1" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认注销账号</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger small py-2">将删除账号本身及其个人数据，且无法恢复。请谨慎操作。</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="deleteAcknowledge">
                        <label class="form-check-label small" for="deleteAcknowledge">我已了解注销账号不可恢复</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btnDeleteNext" disabled>继续</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 注销：确认 2 -->
    <div class="modal fade" id="modalDeleteConfirm2" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">最终确认（输入 DELETE）</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2">请输入 <b>DELETE</b> 以继续执行注销。</div>
                    <input type="text" class="form-control form-control-sm" id="deleteConfirmText" placeholder="DELETE" autocomplete="off">
                    <div class="alert alert-danger small py-2 mt-2 d-none" id="deleteError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btnDeleteStart">开始注销</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 进度弹窗 -->
    <div class="modal fade" id="modalAccountProgress" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="progressTitle">处理中…</h5>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2" id="progressCurrentStep">准备开始</div>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" id="progressBar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-muted" id="progressPercent">0%</div>
                    <div class="mt-2">
                        <ul class="small mb-0" id="progressLog" style="max-height: 180px; overflow:auto;"></ul>
                    </div>
                    <div class="alert alert-danger small py-2 mt-2 d-none" id="progressError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-primary" id="btnProgressClose" data-bs-dismiss="modal" disabled>关闭</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            function showEl(el) { el.classList.remove('d-none'); }
            function hideEl(el) { el.classList.add('d-none'); }

            function setBtnDisabled(btn, disabled) {
                if (!btn) return;
                btn.disabled = !!disabled;
            }

            var resetAck = document.getElementById('resetAcknowledge');
            var btnResetNext = document.getElementById('btnResetNext');
            if (resetAck && btnResetNext) {
                resetAck.addEventListener('change', function () {
                    setBtnDisabled(btnResetNext, !resetAck.checked);
                });
            }

            var deleteAck = document.getElementById('deleteAcknowledge');
            var btnDeleteNext = document.getElementById('btnDeleteNext');
            if (deleteAck && btnDeleteNext) {
                deleteAck.addEventListener('change', function () {
                    setBtnDisabled(btnDeleteNext, !deleteAck.checked);
                });
            }

            function openModal(id) {
                var el = document.getElementById(id);
                if (!el || typeof bootstrap === 'undefined') return;
                bootstrap.Modal.getOrCreateInstance(el).show();
            }
            function closeModal(id) {
                var el = document.getElementById(id);
                if (!el || typeof bootstrap === 'undefined') return;
                bootstrap.Modal.getOrCreateInstance(el).hide();
            }

            if (btnResetNext) {
                btnResetNext.addEventListener('click', function () {
                    closeModal('modalResetConfirm1');
                    openModal('modalResetConfirm2');
                });
            }
            if (btnDeleteNext) {
                btnDeleteNext.addEventListener('click', function () {
                    closeModal('modalDeleteConfirm1');
                    openModal('modalDeleteConfirm2');
                });
            }

            var progressTitle = document.getElementById('progressTitle');
            var progressCurrentStep = document.getElementById('progressCurrentStep');
            var progressBar = document.getElementById('progressBar');
            var progressPercent = document.getElementById('progressPercent');
            var progressLog = document.getElementById('progressLog');
            var progressError = document.getElementById('progressError');
            var btnProgressClose = document.getElementById('btnProgressClose');

            function resetProgressUI() {
                if (progressLog) progressLog.innerHTML = '';
                if (progressBar) {
                    progressBar.style.width = '0%';
                    progressBar.setAttribute('aria-valuenow', '0');
                }
                if (progressPercent) progressPercent.textContent = '0%';
                if (progressCurrentStep) progressCurrentStep.textContent = '准备开始';
                if (progressError) { progressError.textContent = ''; hideEl(progressError); }
                if (btnProgressClose) setBtnDisabled(btnProgressClose, true);
            }

            function logLine(text) {
                if (!progressLog) return;
                var li = document.createElement('li');
                li.textContent = text;
                progressLog.appendChild(li);
                progressLog.scrollTop = progressLog.scrollHeight;
            }

            async function postAjax(params) {
                var body = new URLSearchParams(params);
                var resp = await fetch(window.location.pathname + window.location.search, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body
                });
                return await resp.json();
            }

            async function runSteps(type, totalSteps) {
                for (var step = 0; step < totalSteps; step++) {
                    var r = await postAjax({ action: type + '_step', step: String(step) });
                    if (!r || !r.ok) {
                        var err = (r && r.error) ? r.error : '执行失败，请稍后重试。';
                        if (progressError) { progressError.textContent = err; showEl(progressError); }
                        logLine('失败：' + err);
                        if (btnProgressClose) setBtnDisabled(btnProgressClose, false);
                        return;
                    }

                    var p = (typeof r.percent === 'number') ? r.percent : 0;
                    if (progressBar) {
                        progressBar.style.width = p + '%';
                        progressBar.setAttribute('aria-valuenow', String(p));
                    }
                    if (progressPercent) progressPercent.textContent = p + '%';
                    if (progressCurrentStep) progressCurrentStep.textContent = '正在执行：' + (r.stepName || '');
                    logLine('完成：' + (r.stepName || ''));

                    if (r.redirect) {
                        // 注销账号：最后一步会销毁会话，直接跳转
                        window.location.href = r.redirect;
                        return;
                    }
                }

                if (progressTitle) progressTitle.textContent = '已完成';
                if (progressCurrentStep) progressCurrentStep.textContent = '操作已完成';
                if (btnProgressClose) setBtnDisabled(btnProgressClose, false);
            }

            async function startMaintenance(type, confirmText, errorElId, titleText, confirmModalId) {
                var errorEl = document.getElementById(errorElId);
                if (errorEl) { errorEl.textContent = ''; hideEl(errorEl); }
                var initResp = await postAjax({ action: type + '_init', confirm: confirmText });
                if (!initResp || !initResp.ok) {
                    var err = (initResp && initResp.error) ? initResp.error : '发起失败，请稍后重试。';
                    if (errorEl) { errorEl.textContent = err; showEl(errorEl); }
                    return;
                }

                if (confirmModalId) {
                    closeModal(confirmModalId);
                }

                resetProgressUI();
                if (progressTitle) progressTitle.textContent = titleText;
                openModal('modalAccountProgress');

                logLine('任务已开始…');
                await runSteps(type, initResp.totalSteps || 0);
            }

            var btnResetStart = document.getElementById('btnResetStart');
            if (btnResetStart) {
                btnResetStart.addEventListener('click', function () {
                    var txt = (document.getElementById('resetConfirmText') || {}).value || '';
                    startMaintenance('self_reset', txt, 'resetError', '正在重置账户数据…', 'modalResetConfirm2');
                });
            }

            var btnDeleteStart = document.getElementById('btnDeleteStart');
            if (btnDeleteStart) {
                btnDeleteStart.addEventListener('click', function () {
                    var txt = (document.getElementById('deleteConfirmText') || {}).value || '';
                    startMaintenance('self_delete', txt, 'deleteError', '正在注销账号…', 'modalDeleteConfirm2');
                });
            }
        })();
    </script>
<?php elseif ($tab === 'ledgers'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h3 class="h6 mb-0">账本管理</h3>
                    <div class="small text-muted">个人账本仅自己可见；共享账本支持多人协作（成员/管理员）。</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($ledgerMode)): ?>
                        <span class="badge bg-primary">当前账本：<?= htmlspecialchars(($activeLedger['name'] ?? '个人账本')) ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary">按用户隔离（旧模式）</span>
                    <?php endif; ?>
                    <?php if (!empty($ledgerMode)): ?>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalLedgerCreate">新增账本</button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($ledgerMode)): ?>
                <div class="small text-muted">
                    当前系统仍按用户维度隔离数据，未启用多账本功能。如需启用，请联系管理员升级数据库后重新登录。
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th style="width:120px;">类型</th>
                            <th>名称</th>
                            <th style="width:80px;">账本ID</th>
                            <th style="width:170px;">创建时间</th>
                            <th style="width:120px;">身份</th>
                            <th style="width:260px;" class="text-center">操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($ledgers)): ?>
                            <tr><td colspan="5" class="text-center text-muted small">暂无可用账本</td></tr>
                        <?php else: ?>
                            <?php foreach ($ledgers as $l): ?>
                                <?php
                                $lid = (int)($l['id'] ?? 0);
                                $type = (string)($l['type'] ?? 'personal');
                                $name = (string)($l['name'] ?? '');
                                $createdAt = (string)($l['created_at'] ?? '');
                                $role = (string)($l['member_role'] ?? '');
                                $isActive = isset($activeLedgerId) && (int)$activeLedgerId === $lid;
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($type === 'shared'): ?>
                                            <span class="badge bg-info">共享账本</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">个人账本</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($name !== '' ? $name : ($type === 'shared' ? '共享账本' : '个人账本')) ?>
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success ms-1">当前</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= $lid > 0 ? $lid : '-' ?></span>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= htmlspecialchars($createdAt !== '' ? $createdAt : '-') ?></span>
                                    </td>
                                    <td>
                                        <?php if ($role === 'admin'): ?>
                                            <span class="badge bg-primary">管理员</span>
                                        <?php elseif ($role === 'member'): ?>
                                            <span class="badge bg-outline-secondary border">成员</span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap justify-content-center gap-1">
                                            <?php if (!$isActive): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="ledger_set_active">
                                                    <input type="hidden" name="ledger_id" value="<?= $lid ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">切换为当前</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($type === 'shared' && $role === 'admin'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="ledger_show_members">
                                                    <input type="hidden" name="ledger_id" value="<?= $lid ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">成员列表</button>
                                                </form>
                                                <form method="post" class="d-inline" onsubmit="return confirm('确定要刷新该账本的邀请码吗？刷新后旧邀请码将失效，需要重新分享。');">
                                                    <input type="hidden" name="action" value="ledger_regenerate_invite">
                                                    <input type="hidden" name="ledger_id" value="<?= $lid ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">刷新邀请码</button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="ledger_show_invite_qr">
                                                    <input type="hidden" name="ledger_id" value="<?= $lid ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success">邀请成员（二维码）</button>
                                                </form>
                                                <form method="post" class="d-inline" onsubmit="return confirm('删除共享账本将同时删除该账本下的所有流水、分类、项目、账户等数据，且无法恢复。建议先在“记账明细”页面使用右上角的“导出当前账本流水”按钮备份数据后再执行删除。确定继续删除吗？');">
                                                    <input type="hidden" name="action" value="ledger_delete_shared">
                                                    <input type="hidden" name="ledger_id" value="<?= $lid ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除账本</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 新增共享账本弹窗 -->
    <div class="modal fade" id="modalLedgerCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">新增共享账本</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="ledger_create_shared">
                        <div class="col-12">
                            <label class="form-label small">账本名称</label>
                            <input type="text" name="ledger_name" class="form-control form-control-sm" placeholder="例如：家庭账本 / 项目A 账本">
                            <div class="form-text small">创建后将自动成为该账本的管理员，可生成邀请二维码让成员加入。</div>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-primary">创建</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 共享账本邀请二维码弹窗 -->
    <div class="modal fade" id="modalLedgerInviteQr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">共享账本邀请二维码</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($ledgerInviteQrPayload) && !empty($ledgerInviteQrLedgerId)): ?>
                        <div class="small text-muted mb-2">
                            使用微信小程序打开“设置中心 &gt; 扫码加入账本”，扫描下方二维码即可加入账本：<?= htmlspecialchars($ledgerInviteQrLedgerName ?? '共享账本') ?>。
                        </div>
                        <div class="d-flex flex-column align-items-center">
                            <canvas id="ledgerInviteQr" class="border rounded mb-2" width="180" height="180"></canvas>
                            <div class="small text-muted">若成员无法扫码，也可将邀请码手动发送：<span class="badge bg-secondary"><?= htmlspecialchars($ledgerInviteCode ?? '') ?></span></div>
                        </div>
                        <script src="/assets/js/qrcode.min.js"></script>
                        <script>
                        (function(){
                            try {
                                var payload = <?= json_encode($ledgerInviteQrPayload ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                                var el = document.getElementById('ledgerInviteQr');
                                if (window.QRCode && typeof window.QRCode.toCanvas === 'function' && el && payload) {
                                    window.QRCode.toCanvas(el, payload, { width: 180, margin: 1 }, function (err) {
                                        if (err) { console.error(err); }
                                    });
                                }
                            } catch (e) { console.error(e); }
                        })();
                        </script>
                    <?php else: ?>
                        <div class="text-muted small">请先在账本列表中选择“邀请成员（二维码）”按钮生成二维码。</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 共享账本成员管理弹窗 -->
    <div class="modal fade" id="modalLedgerMembers" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">账本成员：<?= htmlspecialchars($ledgerMembersModalLedgerName ?? '共享账本') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($ledgerMembersModalError)): ?>
                        <div class="alert alert-danger py-2 small mb-2"><?= htmlspecialchars($ledgerMembersModalError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ledgerMembersModalSuccess)): ?>
                        <div class="alert alert-success py-2 small mb-2"><?= htmlspecialchars($ledgerMembersModalSuccess) ?></div>
                    <?php endif; ?>

                    <form method="post" class="row g-2 align-items-end mb-3">
                        <input type="hidden" name="action" value="ledger_add_member">
                        <input type="hidden" name="ledger_id" value="<?= (int)($ledgerMembersModalLedgerId ?? 0) ?>">
                        <div class="col-12 col-md-8">
                            <label class="form-label small mb-1">添加成员（邮箱或用户名）</label>
                            <input type="text" name="member_keyword" class="form-control form-control-sm" value="<?= htmlspecialchars($pendingLedgerMemberKeyword ?? '') ?>" placeholder="例如：user@example.com 或 username">
                            <div class="form-text small">添加后默认身份为“成员”。</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-sm btn-primary w-100">添加成员</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th style="width:80px;">用户ID</th>
                                <th>用户</th>
                                <th style="width:120px;">身份</th>
                                <th style="width:120px;" class="text-center">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($ledgerMembers)): ?>
                                <tr><td colspan="4" class="text-center text-muted small">暂无成员</td></tr>
                            <?php else: ?>
                                <?php foreach ($ledgerMembers as $m): ?>
                                    <?php
                                    $mid = (int)($m['user_id'] ?? 0);
                                    $mRole = (string)($m['role'] ?? 'member');
                                    $mUsername = (string)($m['username'] ?? '');
                                    $mNickname = (string)($m['nickname'] ?? '');
                                    $mEmail = (string)($m['email'] ?? '');
                                    $isOwner = !empty($ledgerMembersOwnerUserId) && (int)$ledgerMembersOwnerUserId === $mid;
                                    ?>
                                    <tr>
                                        <td><?= $mid ?></td>
                                        <td>
                                            <div class="small">
                                                <?= htmlspecialchars($mNickname !== '' ? $mNickname : ($mUsername !== '' ? $mUsername : '用户')) ?>
                                                <?php if ($mUsername !== ''): ?>
                                                    <span class="text-muted">@<?= htmlspecialchars($mUsername) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($mEmail !== ''): ?>
                                                <div class="small text-muted"><?= htmlspecialchars($mEmail) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isOwner): ?>
                                                <span class="badge bg-dark">创建者</span>
                                            <?php elseif ($mRole === 'admin'): ?>
                                                <span class="badge bg-primary">管理员</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">成员</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!$isOwner): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('确定要移除该成员吗？');">
                                                    <input type="hidden" name="action" value="ledger_remove_member">
                                                    <input type="hidden" name="ledger_id" value="<?= (int)($ledgerMembersModalLedgerId ?? 0) ?>">
                                                    <input type="hidden" name="member_user_id" value="<?= $mid ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">移除</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($openModal) && $openModal === 'ledger_invite_qr'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalLedgerInviteQr');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>
    <?php if (!empty($openModal) && $openModal === 'ledger_members'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('modalLedgerMembers');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>
<?php elseif ($tab === 'ai_service'): ?>
    <?php
    $aiQuotaData = $aiQuotaData ?? ['system_quota'=>10,'system_used'=>0,'purchased_quota'=>0,'purchased_used'=>0];
    $aiRemaining = max(0, (int)$aiQuotaData['system_quota'] - (int)$aiQuotaData['system_used']) + max(0, (int)$aiQuotaData['purchased_quota'] - (int)$aiQuotaData['purchased_used']);
    $pricingPlansData = $pricingPlansData ?? [];
    ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 mb-3">🤖 AI服务</h3>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 text-center" style="background:rgba(102,126,234,0.06)">
                        <div class="small text-muted mb-1">系统配额</div>
                        <div class="fs-4 fw-bold"><?= (int)$aiQuotaData['system_quota'] ?></div>
                        <div class="small text-muted">已用 <?= (int)$aiQuotaData['system_used'] ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 text-center" style="background:rgba(34,197,94,0.06)">
                        <div class="small text-muted mb-1">购买配额</div>
                        <div class="fs-4 fw-bold"><?= (int)$aiQuotaData['purchased_quota'] ?></div>
                        <div class="small text-muted">已用 <?= (int)$aiQuotaData['purchased_used'] ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 text-center" style="background:rgba(245,158,11,0.06)">
                        <div class="small text-muted mb-1">剩余总次数</div>
                        <div class="fs-4 fw-bold" style="color:<?= $aiRemaining > 0 ? '#22c55e' : '#ef4444' ?>"><?= $aiRemaining ?></div>
                        <div class="small text-muted"><?= $aiRemaining > 0 ? '可用' : '已用完' ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 text-center" style="background:rgba(148,163,184,0.08)">
                        <div class="small text-muted mb-1">论坛助手</div>
                        <div class="small fw-semibold"><?= $aiRemaining > 0 ? '可用' : '已用完' ?></div>
                        <div class="small text-muted">AI回帖/回复均消耗1次</div>
                    </div>
                </div>
            </div>

            <div class="small text-muted mb-3">AI次数用于树洞AI回复和论坛助手AI回帖。系统配置模式下每次消耗1次，自定义AI配置不消耗次数。</div>

            <?php if (!empty($pricingPlansData)): ?>
            <h6 class="fw-semibold mb-3">💰 购买套餐</h6>
            <div class="row g-3 mb-3">
                <?php foreach ($pricingPlansData as $plan): ?>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 text-center h-100" style="border-color:rgba(102,126,234,0.3)!important;transition:all 0.2s">
                        <div class="fw-semibold mb-1"><?= htmlspecialchars($plan['name']) ?></div>
                        <div class="text-muted small mb-2"><?= (int)$plan['quota'] ?> 次</div>
                        <div>
                            <span class="fs-4 fw-bold" style="color:#667eea">¥<?= number_format((float)$plan['price'], 2) ?></span>
                            <?php if ((float)$plan['original_price'] > (float)$plan['price']): ?>
                            <span class="text-muted small text-decoration-line-through ms-1">¥<?= number_format((float)$plan['original_price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php
                        $discount = 0;
                        if ((float)$plan['original_price'] > 0 && (float)$plan['price'] < (float)$plan['original_price']) {
                            $discount = round((float)$plan['price'] / (float)$plan['original_price'] * 10, 1);
                        }
                        ?>
                        <?php if ($discount > 0): ?>
                        <div class="mt-1"><span class="badge bg-danger" style="font-size:0.65rem"><?= $discount ?>折</span></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#contactAdminModal">联系管理员购买</button>
            </div>
            <?php else: ?>
            <div class="text-center text-muted py-3">暂无可购买的套餐</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="contactAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2 px-3">
                    <h6 class="modal-title">联系管理员</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <?php if (!empty($system['admin_qrcode_image'])): ?>
                    <div class="mb-3">
                        <img src="/uploads/<?= htmlspecialchars($system['admin_qrcode_image']) ?>" style="max-width:200px;border-radius:8px;" alt="收款码">
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($system['admin_contact'])): ?>
                    <div class="small" style="white-space:pre-wrap;line-height:1.8"><?= nl2br(htmlspecialchars($system['admin_contact'])) ?></div>
                    <?php else: ?>
                    <div class="text-muted small">管理员暂未配置联系方式，请通过其他渠道联系。</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    $logs = $aiUsageLogs ?? [];
    $aiLogPage = $aiLogPage ?? 1;
    $aiLogTotal = $aiLogTotal ?? 0;
    $aiLogTotalPages = max(1, (int)ceil($aiLogTotal / 20));
    ?>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h6 mb-0">📋 AI使用日志</h3>
                <span class="small text-muted">保留最近10天 · 共 <?= $aiLogTotal ?> 条</span>
            </div>
            <?php if (empty($logs)): ?>
            <div class="text-muted small text-center py-3">暂无使用记录</div>
            <?php else: ?>
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light" style="position:sticky;top:0"><tr><th>时间</th><th>来源</th><th>详情</th></tr></thead>
                    <tbody>
                    <?php
                    $sourceLabels = ['treasure' => '🕳️ 树洞', 'forum_reply' => '💬 论坛回帖', 'auto_reply' => '🤖 自动回帖', 'mention_reply' => '📢 @回复'];
                    foreach ($logs as $log):
                    ?>
                    <tr>
                        <td class="small text-muted" style="white-space:nowrap"><?= htmlspecialchars(substr($log['created_at'], 5, 11)) ?></td>
                        <td><span class="small"><?= $sourceLabels[$log['source']] ?? htmlspecialchars($log['source']) ?></span></td>
                        <td class="small text-muted"><?= htmlspecialchars($log['detail']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($aiLogTotalPages > 1): ?>
            <nav class="mt-2"><ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?= $aiLogPage <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?route=settings&tab=ai_service&ai_log_page=<?= $aiLogPage - 1 ?>">上一页</a></li>
                <?php for ($p = 1; $p <= $aiLogTotalPages; $p++): ?>
                <li class="page-item <?= $p === $aiLogPage ? 'active' : '' ?>"><a class="page-link" href="?route=settings&tab=ai_service&ai_log_page=<?= $p ?>"><?= $p ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $aiLogPage >= $aiLogTotalPages ? 'disabled' : '' ?>"><a class="page-link" href="?route=settings&tab=ai_service&ai_log_page=<?= $aiLogPage + 1 ?>">下一页</a></li>
            </ul></nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($tab === 'system' && $isAdmin): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 mb-3">系统参数</h3>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="action" value="update_system">
                <div class="col-12 col-md-6">
                    <label class="form-label small">站点名称</label>
                    <input type="text" name="site_name" class="form-control form-control-sm" value="<?= htmlspecialchars($system['site_name'] ?? '') ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">站点网址</label>
                    <input type="url" name="site_url" class="form-control form-control-sm" value="<?= htmlspecialchars($system['site_url'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
					<label class="form-label small">自动退出时间（小时）</label>
					<div class="input-group input-group-sm">
						<input type="number" name="session_timeout_hours" class="form-control" min="1" max="168" step="1" value="<?= htmlspecialchars((string)($system['session_timeout_hours'] ?? 24)) ?>">
						<button class="btn btn-outline-primary" type="submit">保存时间</button>
					</div>
					<div class="form-text small">从最后一次操作开始计时，超过设定时长将自动退出登录。建议设置为 24 小时，允许范围 1~168 小时。</div>
				</div>
                <div class="col-12">
                    <label class="form-label small">系统图标（SVG）</label>
                    <textarea id="site_icon_svg" name="site_icon_svg" class="form-control form-control-sm" rows="4" placeholder="在此粘贴完整的 &lt;svg&gt;...&lt;/svg&gt; 代码，用作浏览器标签页图标。"><?= htmlspecialchars($system['site_icon_svg'] ?? '') ?></textarea>
                    <div class="form-text small mb-2">
                        仅管理员可见。此 SVG 将作为全系统浏览器标签页的图标（favicon）使用，建议图形简洁、尺寸不宜过大。
                    </div>
                    <label class="form-label small mb-1">图标预览</label>
                    <div class="border rounded bg-white d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;overflow:hidden;">
                        <div id="site_icon_preview" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;"></div>
                    </div>
                    <div class="form-text small">预览仅基于当前输入内容，保存后全站标签页图标将更新为该 SVG。</div>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="allow_register" id="allow_register" <?= !empty($system['allow_register']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="allow_register">允许新用户注册</label>
                    </div>
                    <div class="form-text small">关闭注册后，仅管理员可通过数据库或其他方式创建新账号。</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">绑定二维码有效期（分钟）</label>
                    <input type="number" name="bind_qr_expires_minutes" class="form-control form-control-sm" min="1" max="1440" step="1" value="<?= htmlspecialchars((string)($system['bind_qr_expires_minutes'] ?? 10)) ?>">
                    <div class="form-text small">用于注册成功页和后台“生成绑定码”所用二维码的有效期，建议 10~30 分钟，范围 1~1440 分钟。</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">绑定二维码提示文案</label>
                    <textarea name="bind_qr_text" class="form-control form-control-sm" rows="3" placeholder="扫码绑定时展示的说明文字，可告诉用户如何在小程序中完成绑定。"><?= htmlspecialchars($system['bind_qr_text'] ?? '') ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">管理员联系方式（AI服务购买联系）</label>
                    <textarea name="admin_contact" class="form-control form-control-sm" rows="3" placeholder="如：微信：admin123&#10;QQ群：123456&#10;邮箱：admin@example.com"><?= htmlspecialchars($system['admin_contact'] ?? '') ?></textarea>
                    <div class="form-text small">用户在"AI服务"页面点击"联系管理员"时显示此内容，支持多行。</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">管理员收款码图片</label>
                    <input type="file" name="admin_qrcode_image" accept="image/*" class="form-control form-control-sm mb-1">
                    <?php if (!empty($system['admin_qrcode_image'])): ?>
                    <div class="mt-1"><img src="/uploads/<?= htmlspecialchars($system['admin_qrcode_image']) ?>" style="max-width:120px;border-radius:6px;"></div>
                    <?php endif; ?>
                    <div class="form-text small">可上传收款码图片，用户联系管理员弹窗中会展示。</div>
                </div>
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="small fw-bold mb-2">🖼️ PC 端背景图（毛玻璃效果）</h6>
                </div>
                <div class="col-12 col-md-8">
                    <input type="file" name="bg_image" accept="image/*" class="form-control form-control-sm mb-2">
                    <div class="form-text small mb-2">建议尺寸 1920×1080 以上，支持 JPG/PNG/WebP，文件不超过 5MB。上传后 PC 端将显示毛玻璃效果，白天模式自动加半透明遮罩保证文字可读。</div>
                </div>
                <?php if (!empty($bgImages)): ?>
                <div class="col-12">
                    <div class="small text-muted mb-2">已上传的背景图（点击图片可切换，删除按钮同步删除服务器文件）：</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($bgImages as $bg): ?>
                            <div class="position-relative border rounded p-1 <?= ($system['bg_image_path'] ?? '') === $bg['file_path'] ? 'border-primary' : '' ?>" style="background:rgba(255,255,255,0.5);">
                                <img src="/uploads/<?= htmlspecialchars($bg['file_path']) ?>" alt="背景图" style="width:100px;height:60px;object-fit:cover;border-radius:4px;cursor:pointer;" onclick="this.form.bg_image_select.value='<?= htmlspecialchars($bg['file_path']) ?>'; this.form.submit();">
                                <button type="submit" name="bg_image_delete" value="<?= htmlspecialchars($bg['file_path']) ?>" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 py-0 px-1" style="font-size:10px;line-height:1;" onclick="return confirm('确认删除此背景图？服务器文件将同步删除。');">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <input type="hidden" name="bg_image_select" value="">
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary">保存参数</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 mb-2">🔄 系统更新</h3>
            <div class="form-text small mb-3">点击下方按钮执行数据库迁移和数据表更新。新用户自动建表，老用户自动添加新字段和种子数据。不会影响已有数据。</div>
            <form method="post" onsubmit="return confirm('确认执行系统更新？这将检查并更新数据库结构。')">
                <input type="hidden" name="action" value="run_migration">
                <button type="submit" class="btn btn-sm btn-warning fw-bold">🚀 执行系统更新</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 mb-2">📱 小程序配置</h3>
            <div class="form-text small mb-3">配置后，顶部导航栏"使用小程序"按钮将显示以下小程序列表，用户可查看对应小程序码。</div>

            <?php if (!empty($miniapps)): ?>
            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle mb-0" style="font-size:0.85rem">
                    <thead><tr><th>名称</th><th>小程序码</th><th>排序</th><th>操作</th></tr></thead>
                    <tbody>
                    <?php foreach ($miniapps as $ma): ?>
                    <tr>
                        <td><?= htmlspecialchars($ma['name']) ?></td>
                        <td>
                            <?php if (!empty($ma['qrcode_path'])): ?>
                                <img src="/uploads/<?= htmlspecialchars($ma['qrcode_path']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$ma['sort_order'] ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.75rem"
                                onclick="openEditMiniapp(<?= (int)$ma['id'] ?>, this)">编辑</button>
                            <form method="post" class="d-inline" onsubmit="return confirm('确认删除？')">
                                <input type="hidden" name="action" value="miniapp_delete">
                                <input type="hidden" name="miniapp_id" value="<?= (int)$ma['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.75rem">删除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end border rounded p-3" style="background:rgba(255,255,255,0.2)">
                <div class="col-md-4">
                    <label class="form-label small">小程序名称</label>
                    <input type="text" name="miniapp_name" class="form-control form-control-sm" placeholder="如：记账助手" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">小程序码</label>
                    <input type="file" name="miniapp_qrcode" accept="image/*" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">排序</label>
                    <input type="number" name="miniapp_sort" class="form-control form-control-sm" value="0">
                </div>
                <div class="col-md-3">
                    <button type="submit" name="action" value="miniapp_add" class="btn btn-sm btn-primary">添加小程序</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">公告推送</h3>
                <button type="button" class="btn btn-sm btn-primary" id="btnAnnouncementCreate" data-bs-toggle="modal" data-bs-target="#announcementModal">新建公告</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th style="width:180px;">标题</th>
                        <th>内容预览</th>
                        <th style="width:180px;">推送时间</th>
                        <th style="width:120px;">查看用户数</th>
                        <th style="width:220px;" class="text-center">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr><td colspan="6" class="text-center text-muted small">暂无公告</td></tr>
                    <?php else: ?>
                        <?php foreach ($announcements as $a): ?>
                            <?php
                            $preview = trim(mb_substr(strip_tags((string)($a['content'] ?? '')), 0, 10, 'UTF-8'));
                            if ($preview === '') { $preview = '（无内容）'; }
                            ?>
                            <tr>
                                <td><?= (int)$a['id'] ?></td>
                                <td><?= htmlspecialchars($a['title'] ?? '') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($preview) ?><?= mb_strlen((string)($a['content'] ?? ''), 'UTF-8') > 10 ? '…' : '' ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($a['scheduled_at'] ?? '') ?></td>
                                <td><?= (int)($a['view_count'] ?? 0) ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editAnnouncement(
                                                <?= (int)$a['id'] ?>,
                                                <?= json_encode((string)($a['title'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                                                <?= json_encode((string)($a['content'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                                                <?= json_encode((string)($a['scheduled_at'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                                            )">编辑</button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要删除该公告及其阅读统计吗？');">
                                            <input type="hidden" name="action" value="announcement_delete">
                                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要以当前内容重新推送一条新公告吗？');">
                                            <input type="hidden" name="action" value="announcement_repush">
                                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">重新推送</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">邮件推送</h3>
                <button type="button" class="btn btn-sm btn-primary" id="btnEmailPushCreate" data-bs-toggle="modal" data-bs-target="#emailPushModal">新建推送</button>
            </div>
            <div class="form-text small mb-2">
                当前系统使用企业邮箱的 SMTP 或 PHP mail() 直接发送邮件，配置在 config/config.php 中。全量推送会向所有状态正常且已填写邮箱的用户发送，选择推送则仅向勾选的用户发送。
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th style="width:200px;">标题</th>
                        <th>内容预览</th>
                        <th style="width:160px;">计划时间</th>
                        <th style="width:160px;">最近发送时间</th>
                        <th style="width:100px;">状态</th>
                        <th style="width:200px;" class="text-center">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($emailPushes)): ?>
                        <tr><td colspan="7" class="text-center text-muted small">暂无邮件推送记录</td></tr>
                    <?php else: ?>
                        <?php foreach ($emailPushes as $p): ?>
                            <?php
                            $preview = trim(mb_substr(strip_tags((string)($p['content'] ?? '')), 0, 10, 'UTF-8'));
                            if ($preview === '') { $preview = '（无内容）'; }
                            ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td><?= htmlspecialchars($p['title'] ?? '') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($preview) ?><?= mb_strlen((string)($p['content'] ?? ''), 'UTF-8') > 10 ? '…' : '' ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($p['scheduled_at'] ?? '') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($p['sent_at'] ?? '') ?></td>
                                <td class="small"><?= htmlspecialchars($p['status'] ?? '') ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要重新发送该邮件推送吗？');">
                                            <input type="hidden" name="action" value="email_push_resend">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">重新发送</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要删除该邮件推送记录吗？不会影响已发送的邮件。');">
                                            <input type="hidden" name="action" value="email_push_delete">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- 公告推送弹窗 -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">公告推送</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-2 align-items-end" id="announcement-form">
                        <input type="hidden" name="action" id="announcement_action" value="announcement_create">
                        <input type="hidden" name="id" id="announcement_id" value="">
                        <div class="col-12 col-md-4">
                            <label class="form-label small">公告标题</label>
                            <input type="text" name="announcement_title" id="announcement_title" class="form-control form-control-sm" maxlength="255" required>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label small">公告内容</label>
                            <textarea name="announcement_content" id="announcement_content" class="form-control form-control-sm" rows="3" placeholder="请输入需要展示给所有用户的公告内容" required></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small">推送方式</label>
                            <div class="mb-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="announcement_send_type" id="announcement_send_now" value="now" checked>
                                    <label class="form-check-label small" for="announcement_send_now">立即推送</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="announcement_send_type" id="announcement_send_schedule" value="schedule">
                                    <label class="form-check-label small" for="announcement_send_schedule">按时间推送</label>
                                </div>
                            </div>
                            <input type="datetime-local" name="announcement_scheduled_at" id="announcement_scheduled_at" class="form-control form-control-sm" placeholder="默认为当前时间，可自定义">
                        </div>
                        <div class="col-12 col-md-6 d-flex justify-content-end align-items-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-primary" id="announcement_submit_btn">创建公告</button>
                        </div>
                        <div class="col-12 mt-1">
                            <div class="form-text small" id="announcement_form_hint">创建后，公告将在 PC 首页和小程序首页登录时以弹窗形式展示，用户关闭视为已查看。</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 邮件推送弹窗 -->
    <div class="modal fade" id="emailPushModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">新建邮件推送</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-2 align-items-end">
                        <input type="hidden" name="action" value="email_push_create">
                        <div class="col-12 col-md-4">
                            <label class="form-label small">邮件标题</label>
                            <input type="text" name="email_title" id="email_title" class="form-control form-control-sm" maxlength="255" required>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label small">邮件内容</label>
                            <textarea name="email_content" id="email_content" class="form-control form-control-sm" rows="3" placeholder="支持 HTML 内容，用于向用户发送维护通知等" required></textarea>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small">推送范围</label>
                            <div class="mb-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="email_scope" id="email_scope_all" value="all" checked>
                                    <label class="form-check-label small" for="email_scope_all">全量推送</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="email_scope" id="email_scope_selected" value="selected">
                                    <label class="form-check-label small" for="email_scope_selected">选择推送</label>
                                </div>
                            </div>
                            <select name="email_selected_users[]" id="email_selected_users" class="form-select form-select-sm" multiple size="6" disabled>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>"><?= (int)$u['id'] ?> - <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small">发送时间</label>
                            <div class="mb-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="email_send_type" id="email_send_now" value="now" checked>
                                    <label class="form-check-label small" for="email_send_now">立即发送</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="email_send_type" id="email_send_schedule" value="schedule">
                                    <label class="form-check-label small" for="email_send_schedule">定时发送</label>
                                </div>
                            </div>
                            <input type="datetime-local" name="email_scheduled_at" id="email_scheduled_at" class="form-control form-control-sm" placeholder="留空则使用当前时间">
                        </div>
                        <div class="col-12 col-md-4 d-flex justify-content-end align-items-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-sm btn-primary">创建推送</button>
                        </div>
                        <div class="col-12 mt-1">
                            <div class="form-text small">全量推送会向所有状态正常且已填写邮箱的用户发送，选择推送则仅向勾选的用户发送。</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function() {
            function toggleEmailUserSelect() {
                var scopeAll = document.getElementById('email_scope_all');
                var selectEl = document.getElementById('email_selected_users');
                if (!scopeAll || !selectEl) return;
                var useAll = scopeAll.checked;
                selectEl.disabled = useAll;
            }
            document.addEventListener('DOMContentLoaded', function () {
                var scopeRadios = document.querySelectorAll('input[name="email_scope"]');
                scopeRadios.forEach(function (r) { r.addEventListener('change', toggleEmailUserSelect); });
                toggleEmailUserSelect();

                var btnAnnouncement = document.getElementById('btnAnnouncementCreate');
                if (btnAnnouncement) {
                    btnAnnouncement.addEventListener('click', function () {
                        if (typeof resetAnnouncementForm === 'function') {
                            resetAnnouncementForm();
                        }
                    });
                }

                var btnEmailPush = document.getElementById('btnEmailPushCreate');
                if (btnEmailPush) {
                    btnEmailPush.addEventListener('click', function () {
                        if (typeof resetEmailPushForm === 'function') {
                            resetEmailPushForm();
                        }
                    });
                }
            });
        })();

        function resetAnnouncementForm() {
            try {
                var action = document.getElementById('announcement_action');
                var idInput = document.getElementById('announcement_id');
                var titleInput = document.getElementById('announcement_title');
                var contentInput = document.getElementById('announcement_content');
                var dtInput = document.getElementById('announcement_scheduled_at');
                var nowRadio = document.getElementById('announcement_send_now');
                var scheduleRadio = document.getElementById('announcement_send_schedule');
                if (action) action.value = 'announcement_create';
                if (idInput) idInput.value = '';
                if (titleInput) titleInput.value = '';
                if (contentInput) contentInput.value = '';
                if (dtInput) dtInput.value = '';
                if (nowRadio) nowRadio.checked = true;
                if (scheduleRadio) scheduleRadio.checked = false;
                var hint = document.getElementById('announcement_form_hint');
                if (hint) {
                    hint.textContent = '创建后，公告将在 PC 首页和小程序首页登录时以弹窗形式展示，用户关闭视为已查看。';
                }
                var btn = document.getElementById('announcement_submit_btn');
                if (btn) {
                    btn.textContent = '创建公告';
                }
            } catch (e) { console.error(e); }
        }

        function editAnnouncement(id, title, content, scheduledAt) {
            try {
                var form = document.getElementById('announcement-form');
                if (!form) return;
                document.getElementById('announcement_action').value = 'announcement_update';
                document.getElementById('announcement_id').value = id;
                document.getElementById('announcement_title').value = title || '';
                document.getElementById('announcement_content').value = content || '';
                // 将 YYYY-MM-DD HH:MM:SS 转为 datetime-local 可识别格式
                var dtInput = document.getElementById('announcement_scheduled_at');
                if (scheduledAt && dtInput) {
                    var replaced = scheduledAt.replace(' ', 'T').slice(0, 16);
                    dtInput.value = replaced;
                }
                var nowRadio = document.getElementById('announcement_send_now');
                var scheduleRadio = document.getElementById('announcement_send_schedule');
                if (scheduleRadio && dtInput && dtInput.value) {
                    scheduleRadio.checked = true;
                } else if (nowRadio) {
                    nowRadio.checked = true;
                }
                var hint = document.getElementById('announcement_form_hint');
                if (hint) {
                    hint.textContent = '当前为“编辑公告”模式，保存后将覆盖该公告的标题、内容和推送时间。点击浏览器刷新可退出编辑模式。';
                }
                var btn = document.getElementById('announcement_submit_btn');
                if (btn) {
                    btn.textContent = '保存公告修改';
                }

                var modalEl = document.getElementById('announcementModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            } catch (e) { console.error(e); }
        }

        function resetEmailPushForm() {
            try {
                var titleInput = document.getElementById('email_title');
                var contentInput = document.getElementById('email_content');
                var scopeAll = document.getElementById('email_scope_all');
                var scopeSelected = document.getElementById('email_scope_selected');
                var selectedUsers = document.getElementById('email_selected_users');
                var sendNow = document.getElementById('email_send_now');
                var sendSchedule = document.getElementById('email_send_schedule');
                var dtInput = document.getElementById('email_scheduled_at');
                if (titleInput) titleInput.value = '';
                if (contentInput) contentInput.value = '';
                if (scopeAll) scopeAll.checked = true;
                if (scopeSelected) scopeSelected.checked = false;
                if (selectedUsers) {
                    selectedUsers.disabled = true;
                    for (var i = 0; i < selectedUsers.options.length; i++) {
                        selectedUsers.options[i].selected = false;
                    }
                }
                if (sendNow) sendNow.checked = true;
                if (sendSchedule) sendSchedule.checked = false;
                if (dtInput) dtInput.value = '';
            } catch (e) { console.error(e); }
        }
    </script>
<?php elseif ($tab === 'users' && $isAdmin): ?>
    <?php
    $usersPage = $usersPage ?? [];
    $usersPageTotal = (int)($usersPageTotal ?? 0);
    $usersPageSize = (int)($usersPageSize ?? 10);
    $usersPageIndex = (int)($usersPageIndex ?? 1);
    $usersPageTotalPages = (int)($usersPageTotalPages ?? 1);
    if ($usersPageIndex < 1) { $usersPageIndex = 1; }
    if ($usersPageTotalPages < 1) { $usersPageTotalPages = 1; }
    if ($usersPageIndex > $usersPageTotalPages) { $usersPageIndex = $usersPageTotalPages; }
    $usersPageSizeOptions = [10, 30, 50, 100];
    if (!in_array($usersPageSize, $usersPageSizeOptions, true)) { $usersPageSize = 10; }
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h3 class="h6 mb-3">用户管理</h3>
            <form class="mb-3" onsubmit="return false;">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="user-search-input" class="col-form-label small text-muted">模糊搜索</label>
                    </div>
                    <div class="col-sm-4 col-md-4 col-lg-3">
                        <input type="search" id="user-search-input" class="form-control form-control-sm" placeholder="输入任意关键字，实时筛选列表">
                    </div>
                    <div class="col-sm-3 col-md-3 col-lg-2">
                        <select id="user-bind-filter" class="form-select form-select-sm">
                            <option value="">全部绑定状态</option>
                            <option value="bound">仅已绑定</option>
                            <option value="unbound">仅未绑定</option>
                        </select>
                    </div>
                    <div class="col-sm-3 col-md-3 col-lg-2">
                        <select id="user-page-size" class="form-select form-select-sm" title="每页显示条数" aria-label="每页显示条数">
                            <?php foreach ($usersPageSizeOptions as $opt): ?>
                                <option value="<?= (int)$opt ?>" <?= $usersPageSize === (int)$opt ? 'selected' : '' ?>>每页 <?= (int)$opt ?> 条</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto small text-muted">
                        支持按用户名、昵称、邮箱、注册来源、微信绑定、角色、状态等任意字段模糊匹配，并可按绑定状态快速筛选。
                    </div>
                </div>
            </form>
            <?php /* 移除后台在列表页生成绑定二维码的入口，绑定二维码改为用户个人信息页自行生成查看 */ ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 settings-users-table">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>头像</th>
                        <th>用户名</th>
                        <th>昵称</th>
                        <th>邮箱</th>
                        <th>注册来源</th>
                        <th>微信绑定</th>
                        <th>账本</th>
                        <th>角色</th>
                        <th>状态</th>
                        <th>邮箱验证</th>
                        <th>注册时间</th>
                        <th class="text-center">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($usersPage)): ?>
                        <tr><td colspan="13" class="text-center text-muted small">暂无用户</td></tr>
                    <?php else: ?>
                        <?php foreach ($usersPage as $u): ?>
                            <?php
                            $bindCount = (int)($u['wechat_bind_count'] ?? 0);
                            $uid = (int)($u['id'] ?? 0);
                            $ledgersForUser = $userLedgers[$uid] ?? [];
                            ?>
                            <tr data-wechat-bind="<?= $bindCount > 0 ? 'bound' : 'unbound' ?>">
                                <td><?= (int)$u['id'] ?></td>
                                <td>
                                    <?php if (!empty($u['avatar_path'])): ?>
                                        <img src="/uploads/<?= htmlspecialchars($u['avatar_path']) ?>" alt="头像" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted small">无</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['nickname']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php $src = $u['register_source'] ?? 'pc'; ?>
                                    <?php if ($src === 'miniapp'): ?>
                                        <span class="badge bg-info text-dark">小程序注册</span>
                                    <?php else: ?>
                                        <span class="text-muted small">PC/网页注册</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $lastLoginAt = $u['wechat_last_login_at'] ?? null;
                                    if ($bindCount > 0): ?>
                                        <span class="badge bg-success me-1">已绑定</span>
                                        <?php if (!empty($lastLoginAt)): ?>
                                            <span class="text-muted small">最近登录：<?= htmlspecialchars($lastLoginAt) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">有绑定记录</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">未绑定</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if (empty($ledgersForUser)): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <?php foreach ($ledgersForUser as $ledger): ?>
                                            <?php
                                            $lname = (string)($ledger['name'] ?? '未命名账本');
                                            $ltype = (string)($ledger['type'] ?? 'personal');
                                            $lrole = (string)($ledger['member_role'] ?? 'member');
                                            $typeLabel = $ltype === 'shared' ? '共享' : '个人';
                                            $roleLabel = $lrole === 'admin' ? '管理员' : '成员';
                                            ?>
                                            <div>
                                                <span class="badge bg-light text-muted border me-1"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?= htmlspecialchars($lname, ENT_QUOTES, 'UTF-8') ?>
                                                <span class="text-muted ms-1">(<?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= $u['role'] === 'admin' ? '管理员' : '普通用户' ?></td>
                                <td><?= (int)$u['status'] === 1 ? '正常' : '禁用' ?></td>
                                <td><?= !empty($u['email_verified']) ? '已验证' : '未验证' ?></td>
                                <td><?= htmlspecialchars($u['created_at']) ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="user_status">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="status" value="<?= (int)$u['status'] === 1 ? 0 : 1 ?>">
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-secondary"
                                                title="<?= (int)$u['status'] === 1 ? '禁用' : '启用' ?>"
                                                aria-label="<?= (int)$u['status'] === 1 ? '禁用' : '启用' ?>"
                                            ><?= (int)$u['status'] === 1 ? '⛔' : '✅' ?></button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="user_role">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-primary"
                                                title="<?= $u['role'] === 'admin' ? '设为普通用户' : '设为管理员' ?>"
                                                aria-label="<?= $u['role'] === 'admin' ? '设为普通用户' : '设为管理员' ?>"
                                            ><?= $u['role'] === 'admin' ? '👤' : '👑' ?></button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要为该用户重置密码并发送邮件通知吗？');">
                                            <input type="hidden" name="action" value="user_reset_password">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="重置密码" aria-label="重置密码">🔑</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('确定要强制删除该用户及其所有数据吗？此操作无法恢复。');">
                                            <input type="hidden" name="action" value="user_delete">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="强制删除" aria-label="强制删除">🗑</button>
                                        </form>
                                        <?php /* 绑定二维码按钮已迁移至用户个人信息页 */ ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('将为该用户注入一套默认分类/项目/账户，仅在其当前无任何相关数据时生效，确定继续吗？');">
                                            <input type="hidden" name="action" value="user_seed_defaults">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-info" title="注入默认数据" aria-label="注入默认数据">🌱</button>
                                        </form>
                                        <?php if ($bindCount > 0): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('确定要为该用户解除微信绑定吗？解绑后该用户需要重新在小程序登录或扫码绑定才能继续使用。');">
                                                <input type="hidden" name="action" value="user_unbind_wechat">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="解除微信绑定" aria-label="解除微信绑定">🔗</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3 gap-2">
                <div class="small text-muted">第 <?= (int)$usersPageIndex ?> / <?= (int)$usersPageTotalPages ?> 页，共 <?= (int)$usersPageTotal ?> 个用户</div>
                <nav aria-label="用户分页">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $prevPage = $usersPageIndex > 1 ? $usersPageIndex - 1 : 1;
                        $nextPage = $usersPageIndex < $usersPageTotalPages ? $usersPageIndex + 1 : $usersPageTotalPages;
                        $base = '/public/index.php?route=settings&tab=users&page_size=' . (int)$usersPageSize . '&page=';
                        ?>
                        <li class="page-item <?= $usersPageIndex <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($base . $prevPage) ?>" aria-label="上一页">上一页</a>
                        </li>
                        <?php
                        $totalPages = (int)$usersPageTotalPages;
                        $currentPage = (int)$usersPageIndex;
                        $maxShow = 7;
                        $start = 1;
                        $end = $totalPages;
                        if ($totalPages > $maxShow) {
                            $pad = (int)floor(($maxShow - 1) / 2);
                            $start = max(1, $currentPage - $pad);
                            $end = min($totalPages, $start + $maxShow - 1);
                            if ($end - $start + 1 < $maxShow) {
                                $start = max(1, $end - $maxShow + 1);
                            }
                        }
                        if ($start > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($base . '1') ?>">1</a></li>
                            <?php if ($start > 2): ?>
                            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php for ($p = $start; $p <= $end; $p++): ?>
                            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                <?php if ($p === $currentPage): ?>
                                    <span class="page-link"><?= $p ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="<?= htmlspecialchars($base . $p) ?>"><?= $p ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            <?php endif; ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($base . $totalPages) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>
                        <li class="page-item <?= $usersPageIndex >= $usersPageTotalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($base . $nextPage) ?>" aria-label="下一页">下一页</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="small text-muted mt-2">提示：无法禁用或删除当前登录账号，以避免误操作。</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">🤖 AI次数管理</h3>
                <button type="button" class="btn btn-sm btn-primary" onclick="openGrantAiModal()">发放次数</button>
            </div>
            <div class="form-text small mb-2">此处可为用户发放/修改AI使用次数。管理员同样受次数限制，需自行发放。</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>用户ID</th><th>用户名</th><th>系统配额</th><th>已用</th><th>购买配额</th><th>已用</th><th>剩余</th><th class="text-center">操作</th></tr></thead>
                    <tbody id="aiQuotaTableBody">
                    <tr><td colspan="8" class="text-center text-muted small py-3">加载中...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="aiQuotaPagination" class="mt-2"></div>
        </div>
    </div>

    <div class="modal fade" id="grantAiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h6 class="modal-title">发放AI次数</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="grantSelectedUid" value="">
                    <div class="mb-3">
                        <label class="form-label small">搜索用户</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="grantSearchInput" class="form-control" placeholder="输入用户名/昵称/邮箱/ID搜索" autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" onclick="searchUsersForGrant()">搜索</button>
                        </div>
                        <div id="grantSearchResults" class="mt-1" style="max-height:200px;overflow-y:auto"></div>
                    </div>
                    <div id="grantSelectedUser" class="mb-3" style="display:none">
                        <div class="alert alert-success py-2 small mb-2">
                            已选择：<strong id="grantSelectedName"></strong> <span class="text-muted" id="grantSelectedId"></span>
                            <button type="button" class="btn btn-sm btn-link py-0 ms-1" onclick="clearGrantSelection()">更换</button>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">调整配额（正数增加，负数减少）</label>
                            <input type="number" id="grantPurchasedQuota" class="form-control form-control-sm" value="10">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnGrantSubmit" onclick="submitGrant()" disabled>确认发放</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">💰 AI套餐定价</h3>
                <button type="button" class="btn btn-sm btn-primary" onclick="openPlanModal()">+ 添加套餐</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>名称</th><th>次数</th><th>原价</th><th>售价</th><th>排序</th><th>状态</th><th class="text-center">操作</th></tr></thead>
                    <tbody>
                    <?php if (empty($pricingPlansAdmin)): ?>
                        <tr><td colspan="7" class="text-center text-muted small">暂无套餐</td></tr>
                    <?php else: ?>
                        <?php foreach ($pricingPlansAdmin as $plan): ?>
                        <tr>
                            <td><?= htmlspecialchars($plan['name']) ?></td>
                            <td><?= (int)$plan['quota'] ?></td>
                            <td>¥<?= number_format((float)$plan['original_price'], 2) ?></td>
                            <td>¥<?= number_format((float)$plan['price'], 2) ?></td>
                            <td><?= (int)$plan['sort_order'] ?></td>
                            <td><?= (int)$plan['enabled'] ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-secondary">禁用</span>' ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-plan="<?= htmlspecialchars(json_encode($plan, JSON_UNESCAPED_UNICODE)) ?>" onclick="editPlan(JSON.parse(this.getAttribute('data-plan')))">编辑</button>
                                <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')">
                                    <input type="hidden" name="action" value="ai_plan_delete">
                                    <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">删除</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="planModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="ai_plan_save">
                    <input type="hidden" name="plan_id" id="planId" value="0">
                    <div class="modal-header"><h6 class="modal-title" id="planModalTitle">添加套餐</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><label class="form-label small">套餐名称</label><input type="text" name="plan_name" id="planName" class="form-control form-control-sm" required></div>
                        <div class="mb-2"><label class="form-label small">次数</label><input type="number" name="plan_quota" id="planQuota" class="form-control form-control-sm" required></div>
                        <div class="mb-2"><label class="form-label small">原价</label><input type="number" name="plan_original_price" id="planOriginalPrice" class="form-control form-control-sm" step="0.01" required></div>
                        <div class="mb-2"><label class="form-label small">售价</label><input type="number" name="plan_price" id="planPrice" class="form-control form-control-sm" step="0.01" required></div>
                        <div class="mb-2"><label class="form-label small">排序</label><input type="number" name="plan_sort" id="planSort" class="form-control form-control-sm" value="0"></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="plan_enabled" id="planEnabled" value="1" checked><label class="form-check-label small" for="planEnabled">启用</label></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-sm btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    var aiQuotaPage = 1;
    document.addEventListener('DOMContentLoaded', function() { loadAiQuotas(1); });

    function loadAiQuotas(page) {
        aiQuotaPage = page || 1;
        fetch('/public/index.php?route=settings&action=get_ai_quotas&page=' + aiQuotaPage, { headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.ok) { alert(d.error || '加载失败'); return; }
            var html = '';
            if (!d.list || d.list.length === 0) { html = '<tr><td colspan="8" class="text-center text-muted small">暂无数据</td></tr>'; }
            else {
                d.list.forEach(function(q) {
                    var rem = Math.max(0, q.system_quota - q.system_used) + Math.max(0, q.purchased_quota - q.purchased_used);
                    html += '<tr>';
                    html += '<td>' + q.user_id + '</td>';
                    html += '<td>' + (q.username || q.nickname || '-') + '</td>';
                    html += '<td>' + q.system_quota + '</td>';
                    html += '<td>' + q.system_used + '</td>';
                    html += '<td>' + q.purchased_quota + '</td>';
                    html += '<td>' + q.purchased_used + '</td>';
                    html += '<td><strong>' + rem + '</strong></td>';
                    html += '<td class="text-center"><button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="editQuota(' + q.user_id + ',' + q.system_quota + ',' + q.purchased_quota + ')">修改</button></td>';
                    html += '</tr>';
                });
            }
            document.getElementById('aiQuotaTableBody').innerHTML = html;
            renderAiQuotaPagination(d.page || 1, d.total_pages || 1, d.total || 0);
        });
    }

    function renderAiQuotaPagination(page, totalPages, total) {
        if (totalPages <= 1) { document.getElementById('aiQuotaPagination').innerHTML = '<div class="small text-muted">共 ' + total + ' 条</div>'; return; }
        var html = '<div class="d-flex justify-content-between align-items-center"><div class="small text-muted">共 ' + total + ' 条</div><nav><ul class="pagination pagination-sm mb-0">';
        html += '<li class="page-item ' + (page<=1?'disabled':'') + '"><a class="page-link" href="javascript:void(0)" onclick="loadAiQuotas('+(page-1)+')">上一页</a></li>';
        for (var p = 1; p <= totalPages; p++) {
            if (totalPages > 7 && p > 3 && p < totalPages-2 && Math.abs(p-page)>1) { if (p===4) html += '<li class="page-item disabled"><span class="page-link">…</span></li>'; continue; }
            html += '<li class="page-item '+(p===page?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="loadAiQuotas('+p+')">'+p+'</a></li>';
        }
        html += '<li class="page-item ' + (page>=totalPages?'disabled':'') + '"><a class="page-link" href="javascript:void(0)" onclick="loadAiQuotas('+(page+1)+')">下一页</a></li>';
        html += '</ul></nav></div>';
        document.getElementById('aiQuotaPagination').innerHTML = html;
    }

    function openGrantAiModal() {
        clearGrantSelection();
        document.getElementById('grantSearchInput').value = '';
        document.getElementById('grantSearchResults').innerHTML = '';
        document.getElementById('grantPurchasedQuota').value = '10';
        new bootstrap.Modal(document.getElementById('grantAiModal')).show();
        setTimeout(function(){ document.getElementById('grantSearchInput').focus(); }, 300);
    }

    function searchUsersForGrant() {
        var kw = document.getElementById('grantSearchInput').value.trim();
        if (!kw) { document.getElementById('grantSearchResults').innerHTML = '<div class="small text-muted">请输入搜索关键字</div>'; return; }
        fetch('/public/index.php?route=settings&action=search_users&q=' + encodeURIComponent(kw), { headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.ok || !d.users.length) { document.getElementById('grantSearchResults').innerHTML = '<div class="small text-muted">未找到匹配用户</div>'; return; }
            var html = '';
            d.users.forEach(function(u) {
                html += '<div class="d-flex align-items-center justify-content-between py-1 px-2 rounded" style="cursor:pointer;border:1px solid rgba(0,0,0,0.06);margin-bottom:3px;transition:background 0.1s" onmouseenter="this.style.background=\'rgba(102,126,234,0.08)\'" onmouseleave="this.style.background=\'\'" onclick="selectGrantUser('+u.id+',\''+h(u.username)+'\',\''+h(u.nickname||'')+'\',\''+h(u.email||'')+'\')">';
                html += '<div><strong>'+h(u.username)+'</strong> <span class="text-muted small">('+h(u.nickname||'')+' / ID:'+u.id+')</span></div>';
                html += '<span class="small text-muted">'+h(u.email||'')+'</span>';
                html += '</div>';
            });
            document.getElementById('grantSearchResults').innerHTML = html;
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        var gsi = document.getElementById('grantSearchInput');
        if (gsi) gsi.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); searchUsersForGrant(); } });
    });

    function selectGrantUser(uid, username, nickname, email) {
        document.getElementById('grantSelectedUid').value = uid;
        document.getElementById('grantSelectedName').textContent = nickname || username;
        document.getElementById('grantSelectedId').textContent = '(ID:' + uid + ' / ' + email + ')';
        document.getElementById('grantSelectedUser').style.display = '';
        document.getElementById('grantSearchResults').innerHTML = '';
        document.getElementById('grantSearchInput').value = '';
        document.getElementById('btnGrantSubmit').disabled = false;
    }
    function clearGrantSelection() {
        document.getElementById('grantSelectedUid').value = '';
        document.getElementById('grantSelectedUser').style.display = 'none';
        document.getElementById('btnGrantSubmit').disabled = true;
    }
    function submitGrant() {
        var uid = document.getElementById('grantSelectedUid').value;
        if (!uid) { alert('请先选择用户'); return; }
        var fd = new FormData();
        fd.append('action', 'ai_quota_grant');
        fd.append('target_user_id', uid);
        fd.append('purchased_quota', document.getElementById('grantPurchasedQuota').value);
        fetch('/public/index.php?route=settings', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.ok) { alert(d.message || '已发放'); bootstrap.Modal.getInstance(document.getElementById('grantAiModal')).hide(); loadAiQuotas(aiQuotaPage); }
            else alert(d.error || '失败');
        });
    }
    function h(s) { return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function editQuota(uid, sq, pq) {
        var nsq = prompt('系统配额（当前' + sq + '）', sq);
        if (nsq === null) return;
        var npq = prompt('购买配额（当前' + pq + '）', pq);
        if (npq === null) return;
        var fd = new FormData();
        fd.append('action', 'ai_quota_set');
        fd.append('target_user_id', uid);
        fd.append('system_quota', nsq);
        fd.append('purchased_quota', npq);
        fetch('/public/index.php?route=settings', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.ok) { alert('已更新'); loadAiQuotas(aiQuotaPage); } else alert(d.error || '失败'); });
    }
    function openPlanModal() {
        document.getElementById('planId').value = '0';
        document.getElementById('planName').value = '';
        document.getElementById('planQuota').value = '';
        document.getElementById('planOriginalPrice').value = '';
        document.getElementById('planPrice').value = '';
        document.getElementById('planSort').value = '0';
        document.getElementById('planEnabled').checked = true;
        document.getElementById('planModalTitle').textContent = '添加套餐';
        new bootstrap.Modal(document.getElementById('planModal')).show();
    }
    function editPlan(p) {
        document.getElementById('planId').value = p.id;
        document.getElementById('planName').value = p.name;
        document.getElementById('planQuota').value = p.quota;
        document.getElementById('planOriginalPrice').value = p.original_price;
        document.getElementById('planPrice').value = p.price;
        document.getElementById('planSort').value = p.sort_order;
        document.getElementById('planEnabled').checked = !!parseInt(p.enabled);
        document.getElementById('planModalTitle').textContent = '编辑套餐';
        new bootstrap.Modal(document.getElementById('planModal')).show();
    }
    </script>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="text-muted small">请选择上方标签进入对应设置页面。</div>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'users' && $isAdmin): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var input = document.getElementById('user-search-input');
            var bindSelect = document.getElementById('user-bind-filter');
            var pageSizeSelect = document.getElementById('user-page-size');
            var tableBody = document.querySelector('.settings-users-table tbody');
            if (!input || !tableBody) return;

            if (pageSizeSelect) {
                pageSizeSelect.addEventListener('change', function () {
                    var v = pageSizeSelect.value || '10';
                    window.location.href = '/public/index.php?route=settings&tab=users&page_size=' + encodeURIComponent(v) + '&page=1';
                });
            }

            function applyUserFilter() {
                var keyword = input.value.trim().toLowerCase();
                var bindStatus = bindSelect ? bindSelect.value : '';
                var rows = tableBody.querySelectorAll('tr');
                rows.forEach(function (row) {
                    // "暂无用户" 这种只有一格提示行特殊处理
                    if (row.children.length <= 1) {
                        row.style.display = (keyword || bindStatus) ? 'none' : '';
                        return;
                    }
                    var text = (row.textContent || '').toLowerCase();
                    var rowBind = row.getAttribute('data-wechat-bind') || '';

                    if (keyword && text.indexOf(keyword) === -1) {
                        row.style.display = 'none';
                        return;
                    }

                    if (bindStatus === 'bound' && rowBind !== 'bound') {
                        row.style.display = 'none';
                        return;
                    }
                    if (bindStatus === 'unbound' && rowBind !== 'unbound') {
                        row.style.display = 'none';
                        return;
                    }

                    row.style.display = '';
                });
            }

            input.addEventListener('input', applyUserFilter);
            if (bindSelect) {
                bindSelect.addEventListener('change', applyUserFilter);
            }
        });
    </script>
<?php endif; ?>

<?php if ($tab === 'system' && $isAdmin): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var textarea = document.getElementById('site_icon_svg');
            var preview = document.getElementById('site_icon_preview');
            if (!textarea || !preview) return;

            function updatePreview() {
                var svg = textarea.value.trim();
                if (svg) {
                    preview.innerHTML = svg;
                } else {
                    preview.innerHTML = '<span class="text-muted small">暂无图标</span>';
                }
            }

            textarea.addEventListener('input', updatePreview);
            updatePreview();
        });
    </script>
<?php endif; ?>
<!-- 创建 API Token 弹窗 -->
<div class="modal fade" id="modalCreateApiToken" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form id="formCreateApiToken" method="post">
                <input type="hidden" name="action" value="create_api_token">
                <div class="modal-header py-2">
                    <h6 class="modal-title small">创建 API Token</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small">用途描述</label>
                    <input type="text" name="token_description" class="form-control form-control-sm" placeholder="例如：QClaw、个人脚本" required autofocus>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">创建</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 查看 API Token 弹窗 -->
<div class="modal fade" id="modalViewApiToken" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title small">查看 API Token</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small text-muted">用途</label>
                    <div class="form-control form-control-sm bg-light" id="viewApiTokenDesc"></div>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted">Token</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm font-monospace user-select-all" id="viewApiTokenValue" readonly>
                        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('viewApiTokenValue').value);this.textContent='已复制';setTimeout(()=>this.textContent='复制',1500)">复制</button>
                    </div>
                </div>
                <div class="text-danger small">⚠️ 请妥善保管 Token，不要泄露给他人。</div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewApiToken(id, desc) {
    document.getElementById('viewApiTokenDesc').textContent = desc;
    document.getElementById('viewApiTokenValue').value = 'Loading...';
    var modal = new bootstrap.Modal(document.getElementById('modalViewApiToken'));
    modal.show();
    fetch('/public/index.php?route=settings&action=get_api_token&id=' + id, {
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            document.getElementById('viewApiTokenValue').value = d.token;
        } else {
            document.getElementById('viewApiTokenValue').value = d.error || '获取失败';
        }
    })
    .catch(function() {
        document.getElementById('viewApiTokenValue').value = '请求失败';
    });
}

function toggleAllApiTokens(el) {
    document.querySelectorAll('.api-token-checkbox').forEach(function(cb) {
        cb.checked = el.checked;
    });
}

function batchRevokeApiTokens() {
    var checked = document.querySelectorAll('.api-token-checkbox:checked');
    if (checked.length === 0) {
        alert('请先选择要撤销的 Token');
        return;
    }
    if (!confirm('确定撤销选中的 ' + checked.length + ' 个 Token？')) return;
    var ids = [];
    checked.forEach(function(cb) { ids.push(cb.value); });
    var fd = new FormData();
    fd.append('action', 'batch_revoke_api_token');
    fd.append('token_ids', ids.join(','));
    fetch('/public/index.php?route=settings', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            alert('已撤销 ' + (d.count || ids.length) + ' 个 Token');
            location.reload();
        } else {
            alert('撤销失败：' + (d.error || '未知错误'));
        }
    })
    .catch(function() { alert('请求失败'); });
}

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('formCreateApiToken');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var descInput = form.querySelector('input[name="token_description"]');
        var savedDesc = descInput ? descInput.value : '';
        var fd = new FormData(form);
        fd.append('action', 'create_api_token');
        fetch('/public/index.php?route=settings', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                var cm = bootstrap.Modal.getInstance(document.getElementById('modalCreateApiToken'));
                if (cm) cm.hide();
                document.getElementById('viewApiTokenValue').value = d.token;
                document.getElementById('viewApiTokenDesc').textContent = savedDesc || '新创建的 Token';
                var vm = new bootstrap.Modal(document.getElementById('modalViewApiToken'));
                vm.show();
                document.getElementById('modalViewApiToken').addEventListener('hidden.bs.modal', function handler() {
                    document.getElementById('modalViewApiToken').removeEventListener('hidden.bs.modal', handler);
                    location.reload();
                });
            } else {
                alert('创建失败：' + (d.error || '未知错误'));
            }
        })
        .catch(function() { alert('请求失败'); });
    });
});
</script>

<?php require __DIR__ . '/modal_view_api_token.php'; ?>

<script>
function openEditMiniapp(id, btn) {
    var row = btn.closest('tr');
    var cells = row.querySelectorAll('td');
    document.getElementById('editMiniappId').value = id;
    document.getElementById('editMiniappName').value = cells[0].textContent.trim();
    document.getElementById('editMiniappSort').value = cells[2].textContent.trim();
    var img = cells[1].querySelector('img');
    document.getElementById('editMiniappQrcodePreview').innerHTML = img
        ? '<img src="' + img.src + '" style="width:80px;height:80px;object-fit:cover;border-radius:6px;">'
        : '<span class="text-muted small">暂无</span>';
    new bootstrap.Modal(document.getElementById('miniappEditModal')).show();
}
</script>

<div class="modal fade" id="miniappEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="miniapp_update">
                <input type="hidden" id="editMiniappId" name="miniapp_id" value="">
                <div class="modal-header py-2 px-3">
                    <h5 class="modal-title" style="font-size:0.95rem">编辑小程序</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <div class="mb-3">
                        <label class="form-label small">小程序名称</label>
                        <input type="text" id="editMiniappName" name="miniapp_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">当前小程序码</label>
                        <div id="editMiniappQrcodePreview" class="mb-2"></div>
                        <label class="form-label small">更换小程序码（可选）</label>
                        <input type="file" name="miniapp_qrcode" accept="image/*" class="form-control form-control-sm">
                    </div>
                    <div class="mb-1">
                        <label class="form-label small">排序</label>
                        <input type="number" id="editMiniappSort" name="miniapp_sort" class="form-control form-control-sm" value="0">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>