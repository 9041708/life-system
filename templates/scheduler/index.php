<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">定时任务</h2>
    <div class="small text-muted">系统内所有自动执行的定时任务一览。</div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-2">
        <span class="fw-semibold small">任务列表</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>任务名称</th>
                        <th>执行计划</th>
                        <th>状态</th>
                        <th>上次执行</th>
                        <th>配置来源</th>
                        <th>详情</th>
                        <th style="width:80px;" class="text-center">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">暂无定时任务</td></tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="fw-semibold small"><?= htmlspecialchars($task['description']) ?></td>
                            <td class="small"><?= htmlspecialchars($task['schedule']) ?></td>
                            <td>
                                <?php if (!empty($task['enabled'])): ?>
                                    <span class="badge bg-success">已启用</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">未启用</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?php if (!empty($task['last_run'])): ?>
                                    <?= htmlspecialchars($task['last_run']) ?>
                                    <?php if ($task['last_result'] === 'success'): ?>
                                        <span class="badge bg-success ms-1">成功</span>
                                    <?php elseif ($task['last_result'] === 'error'): ?>
                                        <span class="badge bg-danger ms-1">失败</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">从未执行</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($task['source']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($task['detail'] ?? '') ?></td>
                            <td class="text-center">
                                <?php if (!empty($task['runnable'])): ?>
                                    <button class="btn btn-outline-primary btn-sm" style="font-size:0.75rem;padding:2px 8px;" onclick="runTask('<?= htmlspecialchars($task['name']) ?>', this)">▶ 执行</button>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.75rem;">-</span>
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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">最近执行记录</span>
        <span class="badge bg-secondary"><?= count($history) ?> 条</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>任务</th>
                        <th>执行时间</th>
                        <th>耗时</th>
                        <th>结果</th>
                        <th>详情</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">暂无执行记录</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $record): ?>
                        <tr>
                            <td class="small fw-semibold"><?= htmlspecialchars($record['task_name'] ?? '-') ?></td>
                            <td class="small"><?= htmlspecialchars($record['formatted_start_time'] ?? '-') ?></td>
                            <td class="small"><?= ($record['duration'] ?? 0) . ' 秒' ?></td>
                            <td>
                                <?php if (!empty($record['success'])): ?>
                                    <span class="badge bg-success">成功</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">失败</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($record['result'] ?? $record['error'] ?? '') ?>">
                                <?= htmlspecialchars(mb_strimwidth($record['result'] ?? $record['error'] ?? '-', 0, 80, '...')) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function runTask(taskName, btn) {
    if (btn) btn.disabled = true;
    fetch('/public/index.php?route=scheduler-api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'action=run_task&task_name=' + encodeURIComponent(taskName)
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok !== undefined ? data.ok : data.success) {
            showToast('执行完成', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('执行失败: ' + (data.error || '未知错误'), 'danger');
        }
    })
    .catch(e => showToast('请求失败: ' + e.message, 'danger'))
    .finally(() => { if (btn) btn.disabled = false; });
}

function showToast(msg, type) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + type + ' position-fixed top-0 end-0 m-3 shadow-sm';
    toast.style.zIndex = 9999;
    toast.style.minWidth = '250px';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
