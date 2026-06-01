<?php
/**
 * 安全监控页面模板
 * 包含：统计卡片、趋势图、活跃IP、黑白名单、策略配置、日志表格
 */
?>
<style>
/* ========== 主色调 ========== */
:root {
    --gh-bg:        #f6f8fa;
    --gh-bg2:       #ffffff;
    --gh-bg3:       #f6f8fa;
    --gh-border:    #d0d7de;
    --gh-text:      #1f2328;
    --gh-text2:     #656d76;
    --gh-accent:    #0969da;
    --gh-green:     #1a7f37;
    --gh-red:       #cf222e;
    --gh-orange:    #9a6700;
    --gh-purple:    #8250df;
    --gh-cyan:      #0550ae;
    --gh-radius:    6px;
    --gh-shadow:    0 1px 3px rgba(0,0,0,.08);
}

body { background: var(--gh-bg); color: var(--gh-text); }

/* ========== 统计卡片 ========== */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.stat-card {
    background: var(--gh-bg2);
    border: 1px solid var(--gh-border);
    border-radius: var(--gh-radius);
    padding: 20px 22px;
    display: flex; align-items: center; gap: 16px;
    transition: border-color .2s, box-shadow .2s;
    cursor: default;
}
.stat-card:hover {
    border-color: var(--gh-accent);
    box-shadow: var(--gh-shadow);
}
.stat-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.stat-icon.blue   { background: rgba(88,166,255,.12); color: var(--gh-accent); }
.stat-icon.green  { background: rgba(63,185,80,.12);  color: var(--gh-green);  }
.stat-icon.red    { background: rgba(248,81,73,.12);  color: var(--gh-red);    }
.stat-icon.orange { background: rgba(210,153,34,.12); color: var(--gh-orange);}
.stat-icon.purple { background: rgba(188,140,255,.12);color: var(--gh-purple); }
.stat-icon.cyan  { background: rgba(57,210,192,.12); color: var(--gh-cyan);  }
.stat-info h3 {
    font-size: 24px; font-weight: 700; margin: 0 0 2px 0;
    font-variant-numeric: tabular-nums;
}
.stat-info p { font-size: 12px; color: var(--gh-text2); margin: 0; }

/* ========== 面板通用 ========== */
.panel {
    background: var(--gh-bg2);
    border: 1px solid var(--gh-border);
    border-radius: var(--gh-radius);
    margin-bottom: 24px;
    overflow: hidden;
}
.panel-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--gh-border);
    background: var(--gh-bg2);
}
.panel-header h2 {
    font-size: 14px; font-weight: 600; margin: 0;
    display: flex; align-items: center; gap: 8px;
}
.panel-body { padding: 20px; }

/* ========== 趋势图 ========== */
.chart-container { position: relative; height: 280px; }

/* ========== 表格 ========== */
.data-table {
    width: 100%; border-collapse: collapse;
}
.data-table th {
    text-align: left; padding: 10px 14px;
    font-size: 11px; font-weight: 600;
    color: var(--gh-text2); text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 1px solid var(--gh-border);
    background: var(--gh-bg2);
}
.data-table td {
    padding: 10px 14px;
    font-size: 13px;
    border-bottom: 1px solid var(--gh-border);
    vertical-align: middle;
}
.data-table tr:hover td { background: rgba(88,166,255,.04); }
.data-table .mono { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 12px; }

/* ========== 徽章 ========== */
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: 12px;
    font-size: 11px; font-weight: 600;
}
.badge-success { background: rgba(63,185,80,.15); color: var(--gh-green); }
.badge-danger  { background: rgba(248,81,73,.15); color: var(--gh-red);    }
.badge-warning { background: rgba(210,153,34,.15); color: var(--gh-orange); }
.badge-info    { background: rgba(88,166,255,.15); color: var(--gh-accent); }
.badge-muted   { background: rgba(139,148,158,.12); color: var(--gh-text2); }

/* ========== 按钮 ========== */
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 6px;
    font-size: 12px; font-weight: 500;
    border: 1px solid var(--gh-border);
    background: var(--gh-bg2); color: var(--gh-text);
    cursor: pointer; transition: .15s;
    text-decoration: none;
}
.btn:hover { border-color: var(--gh-accent); color: var(--gh-accent); }
.btn-sm { padding: 4px 10px; font-size: 11px; }
.btn-primary {
    background: var(--gh-accent); border-color: var(--gh-accent); color: #fff;
}
.btn-primary:hover { opacity: .88; color: #fff; }
.btn-danger { color: var(--gh-red); border-color: rgba(248,81,73,.3); }
.btn-danger:hover { background: rgba(248,81,73,.1); }
.btn-success { color: var(--gh-green); border-color: rgba(63,185,80,.3); }
.btn-success:hover { background: rgba(63,185,80,.1); }

/* ========== 标签切换 ========== */
.tab-bar {
    display: flex; gap: 2px;
    border-bottom: 1px solid var(--gh-border);
    padding: 0 20px;
}
.tab-btn {
    padding: 10px 16px;
    font-size: 13px; font-weight: 500;
    color: var(--gh-text2);
    background: none; border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer; transition: .15s;
}
.tab-btn:hover { color: var(--gh-text); }
.tab-btn.active {
    color: var(--gh-accent);
    border-bottom-color: var(--gh-accent);
}
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ========== 策略配置 ========== */
.policy-section { margin-bottom: 24px; }
.policy-section h3 {
    font-size: 13px; font-weight: 600; margin: 0 0 14px 0;
    color: var(--gh-text);
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gh-border);
}
.form-row {
    display: flex; align-items: flex-start; gap: 16px;
    margin-bottom: 16px; flex-wrap: wrap;
}
.form-group { display: flex; flex-direction: column; gap: 5px; min-width: 200px; }
.form-group label {
    font-size: 12px; font-weight: 500; color: var(--gh-text2);
}
.form-group select,
.form-group input[type="text"],
.form-group input[type="number"] {
    background: var(--gh-bg);
    border: 1px solid var(--gh-border);
    border-radius: 6px;
    padding: 7px 11px;
    font-size: 13px; color: var(--gh-text);
    outline: none; transition: border-color .15s;
}
.form-group select:focus,
.form-group input:focus {
    border-color: var(--gh-accent);
}
.form-hint { font-size: 11px; color: var(--gh-text2); margin-top: 2px; }
.checkbox-label {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: var(--gh-text);
    cursor: pointer;
}
.checkbox-label input[type="checkbox"] { accent-color: var(--gh-accent); }

/* ========== IP 列表 ========== */
.ip-list-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid var(--gh-border);
    transition: background .15s;
}
.ip-list-item:hover { background: rgba(88,166,255,.04); }
.ip-info { display: flex; flex-direction: column; gap: 2px; }
.ip-addr { font-family: 'SF Mono', monospace; font-size: 13px; color: var(--gh-text); }
.ip-meta { font-size: 11px; color: var(--gh-text2); }
.ip-actions { display: flex; gap: 6px; }

/* ========== 响应式 ========== */
/* ========== 地区多选 ========== */
.region-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 5px;
    margin: 6px 0 8px;
}
.region-checkbox {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border: 1px solid var(--gh-border);
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
.region-checkbox:hover { border-color: var(--gh-accent); }
.region-checkbox.active {
    border-color: var(--gh-accent);
    background: rgba(9,105,218,.06);
}
.region-checkbox input[type="checkbox"] { accent-color: var(--gh-accent); margin: 0; }
.region-name { font-size: 12px; color: var(--gh-text); white-space: nowrap; }
.region-code { font-size: 10px; color: var(--gh-text2); margin-left: auto; }
.btn-sm {
    padding: 3px 10px;
    font-size: 12px;
    border: 1px solid var(--gh-border);
    border-radius: 4px;
    background: var(--gh-bg2);
    color: var(--gh-text);
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
.btn-sm:hover { border-color: var(--gh-accent); background: var(--gh-bg3); }

@media (max-width: 768px) {
    .stat-grid { grid-template-columns: 1fr 1fr; }
    .form-row  { flex-direction: column; }
}
</style>

<!-- ==================== 顶部统计卡片 ==================== -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div class="stat-info">
            <h3><?= number_format($todayTotal) ?></h3>
            <p>24H 登录总量</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">🌐</div>
        <div class="stat-info">
            <h3><?= number_format($uniqueIps) ?></h3>
            <p>独立 IP 数</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">❌</div>
        <div class="stat-info">
            <h3><?= number_format($todayFail) ?></h3>
            <p>失败尝试</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
            <h3><?= number_format($todaySuccess) ?></h3>
            <p>成功登录</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">🔒</div>
        <div class="stat-info">
            <h3><?= number_format($lockedCount) ?></h3>
            <p>锁定账户</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📝</div>
        <div class="stat-info">
            <h3><?= number_format($totalLogs) ?></h3>
            <p>总记录数</p>
        </div>
    </div>
</div>

<!-- ==================== 趋势图 + 活跃IP ==================== -->
<div style="display:grid; grid-template-columns:1.6fr 1fr; gap:20px; margin-bottom:24px;" class="responsive-grid">
    <!-- 近7天访问趋势 -->
    <div class="panel">
        <div class="panel-header">
            <h2>📈 近 7 天访问趋势</h2>
        </div>
        <div class="panel-body">
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 右侧：活跃IP + 高风险IP -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <!-- 活跃IP -->
        <div class="panel" style="flex:1;">
            <div class="panel-header">
                <h2>🔥 活跃 IP（24H）</h2>
                <span class="badge badge-info"><?= count($activeIps) ?></span>
            </div>
            <div class="panel-body" style="padding:0; max-height:260px; overflow-y:auto;">
                <?php if (empty($activeIps)): ?>
                    <div style="padding:20px; text-align:center; color:var(--gh-text2); font-size:13px;">暂无数据</div>
                <?php else: ?>
                    <?php foreach (array_slice($activeIps, 0, 10) as $ip): ?>
                        <div class="ip-list-item">
                            <div class="ip-info">
                                <span class="ip-addr"><?= htmlspecialchars($ip['ip_address']) ?></span>
                                <span class="ip-meta">请求 <?= $ip['request_count'] ?> 次 · 失败 <?= $ip['fail_count'] ?></span>
                            </div>
                            <div class="ip-actions">
                                <?php if ($ip['fail_count'] >= 3): ?>
                                    <span class="badge badge-danger">风险</span>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="addBlacklist('<?= htmlspecialchars($ip['ip_address']) ?>')">拉黑</button>
                                <button class="btn btn-sm btn-success" onclick="addWhitelist('<?= htmlspecialchars($ip['ip_address']) ?>')">白名单</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 高风险IP -->
        <div class="panel" style="flex:1;">
            <div class="panel-header">
                <h2>⚠️ 高风险 IP</h2>
                <span class="badge badge-danger"><?= count($riskyIps) ?></span>
            </div>
            <div class="panel-body" style="padding:0; max-height:200px; overflow-y:auto;">
                <?php if (empty($riskyIps)): ?>
                    <div style="padding:20px; text-align:center; color:var(--gh-text2); font-size:13px;">暂无高风险 IP</div>
                <?php else: ?>
                    <?php foreach ($riskyIps as $ip): ?>
                        <div class="ip-list-item">
                            <div class="ip-info">
                                <span class="ip-addr"><?= htmlspecialchars($ip['ip_address']) ?></span>
                                <span class="ip-meta">失败 <?= $ip['fail_count'] ?> 次</span>
                            </div>
                            <button class="btn btn-sm btn-danger" onclick="addBlacklist('<?= htmlspecialchars($ip['ip_address']) ?>')">拉黑</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ==================== Tab 面板：黑白名单 + 策略配置 + 日志 ==================== -->
<div class="panel">
    <div class="tab-bar">
        <button class="tab-btn active" data-tab="blacklist">🚫 黑名单 <span class="badge badge-danger"><?= $blacklistCount ?></span></button>
        <button class="tab-btn" data-tab="whitelist">✅ 白名单 <span class="badge badge-success"><?= $whitelistCount ?></span></button>
        <button class="tab-btn" data-tab="policy">🛡️ 策略配置</button>
        <button class="tab-btn" data-tab="logs">📋 访问日志</button>
    </div>

    <!-- ========== 黑名单 Tab ========== -->
    <div class="tab-content active" id="tab-blacklist">
        <div class="panel-body">
            <!-- 添加黑名单表单 -->
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; align-items:flex-end;">
                <div class="form-group" style="min-width:180px;">
                    <label>IP 地址</label>
                    <input type="text" id="bl-ip" placeholder="例：192.168.1.1" style="width:180px;">
                </div>
                <div class="form-group" style="min-width:200px;">
                    <label>原因</label>
                    <input type="text" id="bl-reason" placeholder="例：暴力破解" style="width:200px;">
                </div>
                <div class="form-group" style="min-width:140px;">
                    <label>拉黑时长</label>
                    <select id="bl-duration">
                        <option value="0">永久</option>
                        <option value="10">10 分钟</option>
                        <option value="60">1 小时</option>
                        <option value="1440">1 天</option>
                        <option value="10080">7 天</option>
                    </select>
                </div>
                <button class="btn btn-danger" onclick="submitAddBlacklist()" style="height:fit-content;">加入黑名单</button>
            </div>

            <!-- 批量操作 -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" id="bl-check-all" onchange="toggleCheckAll('bl')">
                    <label for="bl-check-all" style="font-size:12px; color:var(--gh-text2); cursor:pointer;">全选</label>
                </div>
                <button class="btn btn-sm btn-danger" onclick="batchRemoveBlacklist()">批量移除</button>
            </div>

            <!-- 黑名单列表 -->
            <div style="max-height:400px; overflow-y:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" onchange="toggleCheckAll('bl')"></th>
                            <th>IP 地址</th>
                            <th>原因</th>
                            <th>过期时间</th>
                            <th>添加时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blacklist)): ?>
                            <tr><td colspan="6" style="text-align:center; color:var(--gh-text2); padding:24px;">暂无黑名单记录</td></tr>
                        <?php else: ?>
                            <?php foreach ($blacklist as $item): ?>
                                <?php
                                    $expired = !empty($item['expires_at']) && $item['expires_at'] < time();
                                    $expiresText = $item['expires_at'] > 0
                                        ? ($expired ? '<span class="badge badge-muted">已过期</span>' : date('Y-m-d H:i', $item['expires_at']))
                                        : '永久';
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="bl-check" value="<?= htmlspecialchars($item['ip_address']) ?>"></td>
                                    <td class="mono"><?= htmlspecialchars($item['ip_address']) ?></td>
                                    <td style="font-size:12px; color:var(--gh-text2);"><?= htmlspecialchars($item['reason'] ?: '-') ?></td>
                                    <td style="font-size:12px;"><?= $expiresText ?></td>
                                    <td style="font-size:12px; color:var(--gh-text2);"><?= date('Y-m-d H:i', $item['created_at']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="removeBlacklist('<?= htmlspecialchars($item['ip_address']) ?>')">移除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========== 白名单 Tab ========== -->
    <div class="tab-content" id="tab-whitelist">
        <div class="panel-body">
            <!-- 添加白名单表单 -->
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; align-items:flex-end;">
                <div class="form-group" style="min-width:180px;">
                    <label>IP 地址</label>
                    <input type="text" id="wl-ip" placeholder="例：192.168.1.100" style="width:180px;">
                </div>
                <div class="form-group" style="min-width:200px;">
                    <label>备注</label>
                    <input type="text" id="wl-remark" placeholder="例：公司办公网" style="width:200px;">
                </div>
                <button class="btn btn-success" onclick="submitAddWhitelist()" style="height:fit-content;">加入白名单</button>
            </div>

            <!-- 批量操作 -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" id="wl-check-all" onchange="toggleCheckAll('wl')">
                    <label for="wl-check-all" style="font-size:12px; color:var(--gh-text2); cursor:pointer;">全选</label>
                </div>
                <button class="btn btn-sm btn-danger" onclick="batchRemoveWhitelist()">批量移除</button>
            </div>

            <!-- 白名单列表 -->
            <div style="max-height:400px; overflow-y:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" onchange="toggleCheckAll('wl')"></th>
                            <th>IP 地址</th>
                            <th>备注</th>
                            <th>添加时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($whitelist)): ?>
                            <tr><td colspan="5" style="text-align:center; color:var(--gh-text2); padding:24px;">暂无白名单记录</td></tr>
                        <?php else: ?>
                            <?php foreach ($whitelist as $item): ?>
                                <tr>
                                    <td><input type="checkbox" class="wl-check" value="<?= htmlspecialchars($item['ip_address']) ?>"></td>
                                    <td class="mono"><?= htmlspecialchars($item['ip_address']) ?></td>
                                    <td style="font-size:12px; color:var(--gh-text2);"><?= htmlspecialchars($item['remark'] ?: '-') ?></td>
                                    <td style="font-size:12px; color:var(--gh-text2);"><?= date('Y-m-d H:i', $item['created_at']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="removeWhitelist('<?= htmlspecialchars($item['ip_address']) ?>')">移除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========== 策略配置 Tab ========== -->
    <div class="tab-content" id="tab-policy">
        <div class="panel-body">
            <!-- 登录安全策略 -->
            <div class="policy-section" style="margin-bottom:24px;">
                <h3>🔒 登录安全策略</h3>
                <form id="loginPolicyForm">
                    <div class="form-row">
                        <div class="form-group" style="flex:1; min-width:180px;">
                            <label>连续失败锁定阈值</label>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <input type="number" name="login_lock_threshold" min="1" max="100" value="<?= htmlspecialchars($policies['login_lock_threshold'] ?? '5') ?>" style="width:80px;">
                                <span style="font-size:12px; color:var(--gh-text2);">次</span>
                            </div>
                            <div class="form-hint">连续输错密码达到此次数后锁定账户</div>
                        </div>
                        <div class="form-group" style="flex:1; min-width:180px;">
                            <label>账户锁定时长</label>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <input type="number" name="login_lock_duration" min="1" max="1440" value="<?= htmlspecialchars($policies['login_lock_duration'] ?? '3') ?>" style="width:80px;">
                                <span style="font-size:12px; color:var(--gh-text2);">分钟</span>
                            </div>
                            <div class="form-hint">锁定期间该账户无法登录</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex:1; min-width:180px;">
                            <label>IP 封禁阈值</label>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <input type="number" name="ip_ban_threshold" min="1" max="1000" value="<?= htmlspecialchars($policies['ip_ban_threshold'] ?? '10') ?>" style="width:80px;">
                                <span style="font-size:12px; color:var(--gh-text2);">次</span>
                            </div>
                            <div class="form-hint">同一 IP 失败达到此次数后封禁</div>
                        </div>
                        <div class="form-group" style="flex:1; min-width:180px;">
                            <label>IP 封禁时长</label>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <input type="number" name="ip_ban_duration" min="1" max="43200" value="<?= htmlspecialchars($policies['ip_ban_duration'] ?? '60') ?>" style="width:80px;">
                                <span style="font-size:12px; color:var(--gh-text2);">分钟</span>
                            </div>
                            <div class="form-hint">封禁期间该 IP 无法访问登录接口</div>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <button type="submit" class="btn btn-primary" id="loginPolicyBtn">💾 保存登录策略</button>
                        <span id="loginPolicyStatus" style="font-size:12px;"></span>
                    </div>
                </form>
            </div>

            <form id="geo-policy-form">
                <!-- 地区访问策略 -->
                <div class="policy-section">
                    <h3>🌍 地区访问策略</h3>
                    <div class="form-row">
                        <div class="form-group" style="flex:1; min-width:220px;">
                            <label>策略模式</label>
                            <select name="geo_mode" id="geo-mode" onchange="onGeoModeChange()">
                                <option value="off" <?= ($policies['geo_mode'] ?? 'off') === 'off' ? 'selected' : '' ?>>关闭（不限制地区）</option>
                                <option value="monitor" <?= ($policies['geo_mode'] ?? '') === 'monitor' ? 'selected' : '' ?>>监控模式（仅记录，不拦截）</option>
                                <option value="auto_block" <?= ($policies['geo_mode'] ?? '') === 'auto_block' ? 'selected' : '' ?>>自动拦截</option>
                            </select>
                            <div class="form-hint">关闭：不检查地区；监控：记录但不拦截；自动拦截：来自非允许地区的 IP 自动加入黑名单</div>
                        </div>
                    </div>
                    <div class="form-row" id="geo-allowed-row">
                        <div class="form-group" style="flex:1; min-width:300px;">
                            <label>允许访问的地区</label>
                            <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                                <button type="button" class="btn btn-sm" onclick="selectAllRegions()">全选</button>
                                <button type="button" class="btn btn-sm" onclick="clearAllRegions()">清空</button>
                                <span id="region-selected-count" style="font-size:12px; color:var(--gh-text2);"></span>
                            </div>
                            <div class="region-checkbox-grid">
                                <?php
                                $selectedRegions = explode(',', $policies['geo_allowed_regions'] ?? 'CN,HK,MO,TW');
                                $selectedRegions = array_map('trim', $selectedRegions);
                                foreach ($all_regions as $code => $name):
                                    $checked = in_array($code, $selectedRegions) ? 'checked' : '';
                                    $isActive = $checked ? ' active' : '';
                                ?>
                                    <label class="region-checkbox<?= $isActive ?>">
                                        <input type="checkbox" name="geo_region_cb[]" value="<?= $code ?>" <?= $checked ?>
                                               onchange="this.parentElement.classList.toggle('active',this.checked);updateGeoHidden()">
                                        <span class="region-name"><?= htmlspecialchars($name) ?></span>
                                        <span class="region-code"><?= $code ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="geo_allowed_regions" id="geo-allowed-regions-hidden"
                                   value="<?= htmlspecialchars($policies['geo_allowed_regions'] ?? 'CN,HK,MO,TW') ?>">
                            <div class="form-hint">勾选允许访问的地区（中文名为参考，存储使用 ISO 代码）。来自未勾选地区的访问将被拦截。</div>
                        </div>
                    </div>
                    <div class="form-row" id="geo-duration-row">
                        <div class="form-group" style="min-width:180px;">
                            <label>自动拉黑时长</label>
                            <select name="auto_block_duration" id="auto-block-duration" onchange="onBlockDurationChange()">
                                <option value="temporary" <?= ($policies['auto_block_duration'] ?? 'permanent') === 'temporary' ? 'selected' : '' ?>>临时拉黑</option>
                                <option value="permanent" <?= ($policies['auto_block_duration'] ?? 'permanent') === 'permanent' ? 'selected' : '' ?>>永久拉黑</option>
                            </select>
                        </div>
                        <div class="form-group" style="min-width:140px; display:<?= ($policies['auto_block_duration'] ?? 'permanent') === 'temporary' ? 'flex' : 'none' ?>" id="auto-block-minutes-group">
                            <label>拉黑时长（分钟）</label>
                            <input type="number" name="auto_block_minutes" id="auto-block-minutes"
                                   value="<?= htmlspecialchars($policies['auto_block_minutes'] ?? '1440') ?>"
                                   min="1" style="width:120px;">
                        </div>
                    </div>
                </div>

                <!-- 保存按钮 -->
                <div style="display:flex; gap:10px; align-items:center;">
                    <button type="button" class="btn btn-primary" onclick="saveGeoPolicy()">💾 保存策略配置</button>
                    <span id="policy-save-msg" style="font-size:12px;"></span>
                </div>
            </form>

            <!-- 当前策略状态 -->
            <div style="margin-top:28px; padding:16px; background:var(--gh-bg); border-radius:var(--gh-radius); border:1px solid var(--gh-border);">
                <div style="font-size:12px; font-weight:600; color:var(--gh-text2); margin-bottom:10px; text-transform:uppercase; letter-spacing:.5px;">当前策略状态</div>
                <div style="display:flex; gap:24px; flex-wrap:wrap;">
                    <div>
                        <span style="font-size:11px; color:var(--gh-text2);">地区策略：</span>
                        <span id="policy-status-mode" class="badge <?= ($policies['geo_mode'] ?? 'off') === 'auto_block' ? 'badge-danger' : (($policies['geo_mode'] ?? 'off') === 'monitor' ? 'badge-warning' : 'badge-muted') ?>">
                            <?= ['off'=>'关闭', 'monitor'=>'监控中', 'auto_block'=>'自动拦截'][$policies['geo_mode'] ?? 'off'] ?>
                        </span>
                    </div>
                    <div>
                        <span style="font-size:11px; color:var(--gh-text2);">允许地区：</span>
                        <span style="font-size:12px; color:var(--gh-text);">
                            <?= htmlspecialchars($geo_allowed_regions_display ?? '中国、香港、澳门、台湾') ?>
                        </span>
                    </div>
                    <div>
                        <span style="font-size:11px; color:var(--gh-text2);">拉黑策略：</span>
                        <span class="badge badge-info">
                            <?= ($policies['auto_block_duration'] ?? 'permanent') === 'temporary' ? '临时（'.$policies['auto_block_minutes'].'分钟）' : '永久' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== 访问日志 Tab ========== -->
    <div class="tab-content" id="tab-logs">
        <div class="panel-body">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" id="log-check-all" onchange="toggleCheckAll('log')">
                    <label for="log-check-all" style="font-size:12px; color:var(--gh-text2); cursor:pointer;">全选</label>
                    <button class="btn btn-sm btn-danger" onclick="clearLogs()">清空日志</button>
                </div>
                <div style="font-size:12px; color:var(--gh-text2);">
                    共 <?= number_format($totalLogs) ?> 条记录
                </div>
            </div>
            <div style="max-height:500px; overflow-y:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" onchange="toggleCheckAll('log')"></th>
                            <th>时间</th>
                            <th>账户</th>
                            <th>IP 地址</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLogs)): ?>
                            <tr><td colspan="5" style="text-align:center; color:var(--gh-text2); padding:24px;">暂无登录记录</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td><input type="checkbox" class="log-check" value="<?= $log['id'] ?>"></td>
                                    <td style="font-size:12px; color:var(--gh-text2); white-space:nowrap;">
                                        <?= date('Y-m-d H:i:s', $log['attempt_time']) ?>
                                    </td>
                                    <td style="font-size:13px;"><?= htmlspecialchars($log['account']) ?></td>
                                    <td class="mono" style="font-size:12px;"><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td>
                                        <?php if ($log['success']): ?>
                                            <span class="badge badge-success">成功</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">失败</span>
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

<!-- ==================== Chart.js ==================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/* ========== Tab 切换 ========== */
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

/* ========== 全选 ========== */
function toggleCheckAll(type) {
    const selector = '.' + type + '-check';
    const checked = document.querySelector('#' + type + '-check-all')?.checked ?? false;
    document.querySelectorAll(selector).forEach(cb => cb.checked = checked);
}

/* ========== 趋势图 ========== */
(function(){
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;
    const trendData = <?= json_encode($trendData, JSON_UNESCAPED_SLASHES) ?>;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.date),
            datasets: [
                {
                    label: '总访问',
                    data: trendData.map(d => d.total),
                    borderColor: '#0969da',
                    backgroundColor: 'rgba(9,105,218,.12)',
                    fill: true, tension: .35, pointRadius: 3, pointHoverRadius: 6,
                },
                {
                    label: '独立IP',
                    data: trendData.map(d => d.unique_ips),
                    borderColor: '#1b7c83',
                    backgroundColor: 'rgba(27,124,131,.10)',
                    fill: true, tension: .35, pointRadius: 3, pointHoverRadius: 6,
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#656d76', font: { size: 11 } } },
                tooltip: { backgroundColor: '#ffffff', titleColor: '#1f2328', bodyColor: '#1f2328', borderColor: '#d0d7de', borderWidth: 1 }
            },
            scales: {
                x: { ticks: { color: '#656d76', font: { size: 11 } }, grid: { color: 'rgba(208,215,222,.5)' } },
                y: { ticks: { color: '#656d76', font: { size: 11 }, precision: 0 }, grid: { color: 'rgba(208,215,222,.5)' }, beginAtZero: true }
            }
        }
    });
})();

/* ========== 黑名单操作 ========== */
function addBlacklist(ip) {
    document.getElementById('bl-ip').value = ip || '';
    document.getElementById('tab-blacklist').classList.add('active');
    document.querySelector('[data-tab="blacklist"]').click();
}

function submitAddBlacklist() {
    const ip       = document.getElementById('bl-ip').value.trim();
    const reason   = document.getElementById('bl-reason').value.trim();
    const duration = parseInt(document.getElementById('bl-duration').value) || 0;

    if (!ip) { alert('请输入 IP 地址'); return; }

    const fd = new FormData();
    fd.append('ip', ip);
    fd.append('reason', reason);
    fd.append('duration_minutes', duration);

    fetch('index.php?route=security/addBlacklist', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                location.reload();
            } else {
                alert('操作失败：' + (d.error || '未知错误'));
            }
        });
}

function removeBlacklist(ip) {
    if (!confirm('确定从黑名单移除 ' + ip + '？')) return;
    const fd = new FormData();
    fd.append('ip', ip);
    fetch('index.php?route=security/removeBlacklist', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert('失败：' + (d.error||'')); });
}

function batchRemoveBlacklist() {
    const ips = Array.from(document.querySelectorAll('.bl-check:checked')).map(cb => cb.value);
    if (!ips.length) { alert('请选择要移除的 IP'); return; }
    if (!confirm('确定批量移除 ' + ips.length + ' 个 IP？')) return;
    const fd = new FormData();
    ips.forEach(ip => fd.append('ips[]', ip));
    fetch('index.php?route=security/batchRemoveBlacklist', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert('失败：' + (d.error||'')); });
}

/* ========== 白名单操作 ========== */
function addWhitelist(ip) {
    document.getElementById('wl-ip').value = ip || '';
    document.getElementById('tab-whitelist').classList.add('active');
    document.querySelector('[data-tab="whitelist"]').click();
}

function submitAddWhitelist() {
    const ip     = document.getElementById('wl-ip').value.trim();
    const remark = document.getElementById('wl-remark').value.trim();

    if (!ip) { alert('请输入 IP 地址'); return; }

    const fd = new FormData();
    fd.append('ip', ip);
    fd.append('remark', remark);

    fetch('index.php?route=security/addWhitelist', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                location.reload();
            } else {
                alert('操作失败：' + (d.error || '未知错误'));
            }
        });
}

function removeWhitelist(ip) {
    if (!confirm('确定从白名单移除 ' + ip + '？')) return;
    const fd = new FormData();
    fd.append('ip', ip);
    fetch('index.php?route=security/removeWhitelist', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert('失败：' + (d.error||'')); });
}

function batchRemoveWhitelist() {
    const ips = Array.from(document.querySelectorAll('.wl-check:checked')).map(cb => cb.value);
    if (!ips.length) { alert('请选择要移除的 IP'); return; }
    if (!confirm('确定批量移除 ' + ips.length + ' 个 IP？')) return;
    const fd = new FormData();
    ips.forEach(ip => fd.append('ips[]', ip));
    fetch('index.php?route=security/batchRemoveWhitelist', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert('失败：' + (d.error||'')); });
}

/* ========== 策略配置 ========== */
function onGeoModeChange() {
    const mode = document.getElementById('geo-mode').value;
    const showGeo = mode === 'auto_block' || mode === 'monitor';
    document.getElementById('geo-allowed-row').style.display = showGeo ? 'flex' : 'none';
    document.getElementById('geo-duration-row').style.display = mode === 'auto_block' ? 'flex' : 'none';
}

function onBlockDurationChange() {
    const dur = document.getElementById('auto-block-duration').value;
    document.getElementById('auto-block-minutes-group').style.display = dur === 'temporary' ? 'flex' : 'none';
}

function saveGeoPolicy() {
    const form = document.getElementById('geo-policy-form');
    const fd = new FormData(form);
    const msgEl = document.getElementById('policy-save-msg');
    msgEl.textContent = '保存中...';
    msgEl.style.color = 'var(--gh-orange)';

    fetch('index.php?route=security-save-geo-policy', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                msgEl.textContent = '✅ 保存成功！';
                msgEl.style.color = 'var(--gh-green)';
                setTimeout(() => location.reload(), 600);
            } else {
                msgEl.textContent = '❌ 保存失败：' + (d.error || '未知错误');
                msgEl.style.color = 'var(--gh-red)';
            }
        })
        .catch(e => {
            msgEl.textContent = '❌ 请求失败';
            msgEl.style.color = 'var(--gh-red)';
        });
}

/* ========== 日志清理 ========== */
function clearLogs() {
    if (!confirm('确定清空所有登录日志？此操作不可恢复！')) return;
    const fd = new FormData();
    fd.append('before', 0);
    fetch('index.php?route=security-clear-logs', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); });
}

/* 初始化隐藏/显示策略选项 */
onGeoModeChange();

// ========== 地区多选 ==========
function updateGeoHidden() {
    const checked = [];
    document.querySelectorAll('input[name="geo_region_cb[]"]').forEach(cb => { if (cb.checked) checked.push(cb.value); });
    document.getElementById('geo-allowed-regions-hidden').value = checked.join(',');
    const total = document.querySelectorAll('input[name="geo_region_cb[]"]').length;
    const el = document.getElementById('region-selected-count');
    if (el) el.textContent = '已选 ' + checked.length + ' / ' + total + ' 个地区';
}
function selectAllRegions() {
    document.querySelectorAll('input[name="geo_region_cb[]"]').forEach(cb => { cb.checked = true; cb.parentElement.classList.add('active'); });
    updateGeoHidden();
}
function clearAllRegions() {
    document.querySelectorAll('input[name="geo_region_cb[]"]').forEach(cb => { cb.checked = false; cb.parentElement.classList.remove('active'); });
    updateGeoHidden();
}
document.addEventListener('DOMContentLoaded', updateGeoHidden);

// ========== 登录安全策略保存 ==========
document.getElementById('loginPolicyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('loginPolicyBtn');
    var statusEl = document.getElementById('loginPolicyStatus');
    btn.disabled = true;
    btn.textContent = '保存中...';
    statusEl.textContent = '';

    fetch('index.php?route=security-save-login-policy', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: new FormData(form)
    }).then(r => r.json()).then(data => {
        btn.disabled = false;
        btn.textContent = '💾 保存登录策略';
        if (data.success) {
            statusEl.textContent = '✅ 已保存';
            statusEl.style.color = 'var(--gh-green)';
            setTimeout(function() { statusEl.textContent = ''; }, 3000);
        } else {
            statusEl.textContent = '❌ ' + (data.error || '保存失败');
            statusEl.style.color = 'var(--gh-red)';
        }
    }).catch(function() {
        btn.disabled = false;
        btn.textContent = '💾 保存登录策略';
        statusEl.textContent = '❌ 网络错误';
        statusEl.style.color = 'var(--gh-red)';
    });
});

</script>

<!-- ==================== 响应式 ==================== -->
<style>
@media (max-width: 900px) {
    .responsive-grid { grid-template-columns: 1fr !important; }
}
</style>
