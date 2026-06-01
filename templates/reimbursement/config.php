<!-- 报销配置页面 - 简化版 -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h4 class="mb-0">⚙️ 报销功能设置</h4>
            <p class="text-muted small mb-0">控制记账时是否显示报销选项</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">功能开关</h6>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="enabled" id="enableReimbursement" 
                                       value="1" <?= ($config['enabled'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enableReimbursement">
                                    <strong>启用报销功能</strong>
                                </label>
                                <div class="form-text text-muted">
                                    启用后，在记账表单中会显示报销选项（支出可勾选"需要报销"，收入可勾选"已报销"）
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">保存设置</button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="small text-muted">
                        <h6 class="fw-bold mb-2">使用说明</h6>
                        <ul class="mb-0">
                            <li>启用后，在【新增记账】表单中：
                                <ul>
                                    <li>支出类型：会显示"需要报销"勾选项</li>
                                    <li>收入类型：会显示"已报销"勾选项</li>
                                </ul>
                            </li>
                            <li>勾选后，系统会自动创建报销记录，可在【报销情况】页面查看和管理</li>
                            <li>禁用后，已创建的报销记录仍然保留，只是记账时不再显示报销选项</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
