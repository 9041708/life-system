<div style="max-width:400px;margin:60px auto">
    <div class="card border-0 shadow-sm"><div class="card-body p-4 text-center">
        <h5>🔐 管理员验证</h5>
        <div class="text-muted small mb-3">请输入独立管理密码</div>
        <?php if (!empty($error)): ?><div class="alert alert-danger py-1 small"><?= $error ?></div><?php endif; ?>
        <form method="post"><input name="admin_pass" type="password" class="form-control mb-2" placeholder="管理密码"><button class="btn btn-primary w-100">验证</button></form>
    </div></div>
</div>
