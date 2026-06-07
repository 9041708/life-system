<?php
/** @var array $logs */
/** @var array $logFilters */
/** @var int $logPage */
/** @var int $logTotalPages */
/** @var int $logTotal */
/** @var string $tab */

$tab = $tab ?? "logs";
?>
<div class="row mb-3">
    <div class="col">
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5 mb-3">系统操作日志</h2>
        <form class="row g-2 mb-3" method="get" action="/public/index.php">
            <input type="hidden" name="route" value="license-admin">
            <input type="hidden" name="tab" value="logs">
            <div class="col-md-2">
                <label for="user_id" class="form-label">用户ID</label>
                <input type="number" class="form-control" id="user_id" name="user_id" value="<?= htmlspecialchars($logFilters["user_id"] ?? "", ENT_QUOTES) ?>" placeholder="全部用户">
            </div>
            <div class="col-md-3">
                <label for="action" class="form-label">操作类型</label>
                <select class="form-select" id="action" name="action">
                    <option value="">全部操作</option>
                    <optgroup label="系统操作">
                        <option value="登录" <?= ($logFilters["action"] ?? "") === "登录" ? "selected" : "" ?>>登录</option>
                        <option value="用户注册" <?= ($logFilters["action"] ?? "") === "用户注册" ? "selected" : "" ?>>用户注册</option>
                        <option value="退出登录" <?= ($logFilters["action"] ?? "") === "退出登录" ? "selected" : "" ?>>退出登录</option>
                    </optgroup>
                    <optgroup label="账户管理">
                        <option value="创建账户" <?= ($logFilters["action"] ?? "") === "创建账户" ? "selected" : "" ?>>创建账户</option>
                        <option value="更新账户" <?= ($logFilters["action"] ?? "") === "更新账户" ? "selected" : "" ?>>更新账户</option>
                        <option value="删除账户" <?= ($logFilters["action"] ?? "") === "删除账户" ? "selected" : "" ?>>删除账户</option>
                    </optgroup>
                    <optgroup label="分类管理">
                        <option value="创建分类" <?= ($logFilters["action"] ?? "") === "创建分类" ? "selected" : "" ?>>创建分类</option>
                        <option value="更新分类" <?= ($logFilters["action"] ?? "") === "更新分类" ? "selected" : "" ?>>更新分类</option>
                        <option value="删除分类" <?= ($logFilters["action"] ?? "") === "删除分类" ? "selected" : "" ?>>删除分类</option>
                    </optgroup>
                    <optgroup label="交易操作">
                        <option value="创建交易" <?= ($logFilters["action"] ?? "") === "创建交易" ? "selected" : "" ?>>创建交易</option>
                        <option value="更新交易" <?= ($logFilters["action"] ?? "") === "更新交易" ? "selected" : "" ?>>更新交易</option>
                        <option value="删除交易" <?= ($logFilters["action"] ?? "") === "删除交易" ? "selected" : "" ?>>删除交易</option>
                    </optgroup>
                    <optgroup label="负债管理">
                        <option value="创建负债配置" <?= ($logFilters["action"] ?? "") === "创建负债配置" ? "selected" : "" ?>>创建负债配置</option>
                        <option value="更新负债配置" <?= ($logFilters["action"] ?? "") === "更新负债配置" ? "selected" : "" ?>>更新负债配置</option>
                        <option value="取消负债配置" <?= ($logFilters["action"] ?? "") === "取消负债配置" ? "selected" : "" ?>>取消负债配置</option>
                        <option value="标记还款" <?= ($logFilters["action"] ?? "") === "标记还款" ? "selected" : "" ?>>标记还款</option>
                        <option value="回退还款" <?= ($logFilters["action"] ?? "") === "回退还款" ? "selected" : "" ?>>回退还款</option>
                    </optgroup>
                    <optgroup label="报销管理">
                        <option value="创建报销记录" <?= ($logFilters["action"] ?? "") === "创建报销记录" ? "selected" : "" ?>>创建报销记录</option>
                        <option value="标记已报销" <?= ($logFilters["action"] ?? "") === "标记已报销" ? "selected" : "" ?>>标记已报销</option>
                        <option value="删除报销记录" <?= ($logFilters["action"] ?? "") === "删除报销记录" ? "selected" : "" ?>>删除报销记录</option>
                        <option value="更新报销配置" <?= ($logFilters["action"] ?? "") === "更新报销配置" ? "selected" : "" ?>>更新报销配置</option>
                    </optgroup>
                    <optgroup label="简历管理">
                        <option value="保存简历" <?= ($logFilters["action"] ?? "") === "保存简历" ? "selected" : "" ?>>保存简历</option>
                        <option value="新建简历" <?= ($logFilters["action"] ?? "") === "新建简历" ? "selected" : "" ?>>新建简历</option>
                        <option value="复制简历" <?= ($logFilters["action"] ?? "") === "复制简历" ? "selected" : "" ?>>复制简历</option>
                        <option value="删除简历" <?= ($logFilters["action"] ?? "") === "删除简历" ? "selected" : "" ?>>删除简历</option>
                    </optgroup>
                    <optgroup label="理财管理">
                        <option value="新增理财" <?= ($logFilters["action"] ?? "") === "新增理财" ? "selected" : "" ?>>新增理财</option>
                        <option value="取出理财" <?= ($logFilters["action"] ?? "") === "取出理财" ? "selected" : "" ?>>取出理财</option>
                        <option value="删除理财" <?= ($logFilters["action"] ?? "") === "删除理财" ? "selected" : "" ?>>删除理财</option>
                    </optgroup>
                    <optgroup label="图书管理">
                        <option value="上传图书" <?= ($logFilters["action"] ?? "") === "上传图书" ? "selected" : "" ?>>上传图书</option>
                        <option value="删除图书" <?= ($logFilters["action"] ?? "") === "删除图书" ? "selected" : "" ?>>删除图书</option>
                        <option value="推送图书-全系统" <?= ($logFilters["action"] ?? "") === "推送图书-全系统" ? "selected" : "" ?>>推送图书-全系统</option>
                        <option value="推送图书-指定用户" <?= ($logFilters["action"] ?? "") === "推送图书-指定用户" ? "selected" : "" ?>>推送图书-指定用户</option>
                        <option value="取消推送" <?= ($logFilters["action"] ?? "") === "取消推送" ? "selected" : "" ?>>取消推送</option>
                    </optgroup>
                    <optgroup label="论坛助手">
                        <option value="论坛签到" <?= ($logFilters["action"] ?? "") === "论坛签到" ? "selected" : "" ?>>论坛签到</option>
                        <option value="论坛回帖" <?= ($logFilters["action"] ?? "") === "论坛回帖" ? "selected" : "" ?>>论坛回帖</option>
                        <option value="论坛通知检查" <?= ($logFilters["action"] ?? "") === "论坛通知检查" ? "selected" : "" ?>>论坛通知检查</option>
                        <option value="@提及回复" <?= ($logFilters["action"] ?? "") === "@提及回复" ? "selected" : "" ?>>@提及回复</option>
                        <option value="论坛登录" <?= ($logFilters["action"] ?? "") === "论坛登录" ? "selected" : "" ?>>论坛登录</option>
                        <option value="论坛错误" <?= ($logFilters["action"] ?? "") === "论坛错误" ? "selected" : "" ?>>论坛错误</option>
                    </optgroup>
                    <option value="其他操作" <?= ($logFilters["action"] ?? "") === "其他操作" ? "selected" : "" ?>>其他操作</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">开始日期</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($logFilters["date_from"] ?? "", ENT_QUOTES) ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">结束日期</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($logFilters["date_to"] ?? "", ENT_QUOTES) ?>">
            </div>
            <div class="col-md-2">
                <label for="per_page" class="form-label">每页显示</label>
                <select class="form-select" id="per_page" name="per_page">
                    <option value="10" <?= ((int)($_GET['per_page'] ?? 50)) === 10 ? "selected" : "" ?>>10 条</option>
                    <option value="25" <?= ((int)($_GET['per_page'] ?? 50)) === 25 ? "selected" : "" ?>>25 条</option>
                    <option value="50" <?= ((int)($_GET['per_page'] ?? 50)) === 50 ? "selected" : "" ?>>50 条</option>
                    <option value="100" <?= ((int)($_GET['per_page'] ?? 50)) === 100 ? "selected" : "" ?>>100 条</option>
                    <option value="200" <?= ((int)($_GET['per_page'] ?? 50)) === 200 ? "selected" : "" ?>>200 条</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">搜索</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th style="width:140px">时间</th>
                        <th style="width:55px">来源</th>
                        <th style="width:120px">用户</th>
                        <th style="width:90px">IP地址</th>
                        <th>操作</th>
                        <th>详情</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">暂无日志记录</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log):
                            $isForum = ($log['source'] ?? 'system') === 'forum';
                            $sourceLabel = $isForum ? '论坛' : '系统';
                            $sourceBadge = $isForum ? 'bg-info' : 'bg-secondary';
                            $forumName = $log['forum_name'] ?? null;
                            $result = $log['result'] ?? null;
                        ?>
                            <tr>
                                <td class="text-nowrap small"><?= htmlspecialchars($log["created_at"], ENT_QUOTES) ?></td>
                                <td><span class="badge <?= $sourceBadge ?>"><?= $sourceLabel ?></span></td>
                                <td class="small">
                                    <?php if ($isForum): ?>
                                        <?php if ($forumName): ?>
                                            <span class="text-info">🌐 <?= htmlspecialchars($forumName, ENT_QUOTES) ?></span><br>
                                        <?php endif; ?>
                                        <span class="text-muted">UID: <?= (int)$log["user_id"] ?></span>
                                    <?php elseif ($log["user_id"]): ?>
                                        ID: <?= (int)$log["user_id"] ?> (<?= htmlspecialchars($log["username"] ?? "未知", ENT_QUOTES) ?>)
                                    <?php else: ?>
                                        游客
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($log["ip_address"] ?? "-", ENT_QUOTES) ?></td>
                                <td class="small"><?= htmlspecialchars($log["action"], ENT_QUOTES) ?></td>
                                <td class="small">
                                    <?= htmlspecialchars($log["details"] ?? "", ENT_QUOTES) ?>
                                    <?php if ($isForum && $result): ?>
                                        <br><small class="text-muted">结果: <?= htmlspecialchars($result, ENT_QUOTES) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($logTotalPages > 1): ?>
            <?php
            $paginationParams = $logFilters;
            if (isset($_GET['per_page'])) {
                $paginationParams['per_page'] = $_GET['per_page'];
            }
            ?>
            <nav aria-label="日志分页">
                <ul class="pagination justify-content-center">
                    <?php if ($logPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?route=license-admin&tab=logs&page=<?= $logPage - 1 ?>&<?= http_build_query($paginationParams) ?>">上一页</a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $logPage - 2);
                    $endPage = min($logTotalPages, $logPage + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <li class="page-item <?= $i === $logPage ? "active" : "" ?>">
                            <a class="page-link" href="?route=license-admin&tab=logs&page=<?= $i ?>&<?= http_build_query($paginationParams) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($logPage < $logTotalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?route=license-admin&tab=logs&page=<?= $logPage + 1 ?>&<?= http_build_query($paginationParams) ?>">下一页</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="mt-3 text-muted small">
            共 <?= $logTotal ?> 条记录，当前第 <?= $logPage ?> 页，共 <?= $logTotalPages ?> 页
        </div>
    </div>
</div>
