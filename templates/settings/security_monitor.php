<?php
/**
 * 安全监控页面 /public/index.php?route=settings&tab=security_monitor
 * 展示系统级安全态势感知数据（仅管理员可见）
 */
?>
<style>
.sec-mon-card {
    background: #1a1a2e;
    border: 1px solid #2d2d44;
    border-radius: 12px;
    padding: 20px 24px;
    color: #e0e0e0;
    display: flex;
    align-items: center;
    gap: 16px;
    min-height: 100px;
}
.sec-mon-card .icon { font-size: 2rem; opacity: 0.85; }
.sec-mon-card .info { flex: 1; }
.sec-mon-card .num { font-size: 2rem; font-weight: 700; line-height: 1; color: #fff; }
.sec-mon-card .lbl { font-size: 0.8rem; color: #888; margin-top: 4px; }
.sec-mon-card .trend { font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; }
.sec-mon-card .trend.up { background: rgba(255,100,100,0.15); color: #ff7b7b; }
.sec-mon-card .trend.down { background: rgba(100,255,100,0.15); color: #7bff7b; }

.sec-event-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.sec-event-table th { background: #2a2a3e; padding: 8px 12px; text-align: left; color: #888; font-weight: 500; border-bottom: 1px solid #333; white-space: nowrap; }
.sec-event-table td { padding: 8px 12px; border-bottom: 1px solid #252535; color: #ccc; }
.sec-event-table tr:hover td { background: #1e1e30; }
.sec-sev-low { color: #4caf50; }
.sec-sev-medium { color: #ff9800; }
.sec-sev-high { color: #f44336; }
.sec-sev-critical { color: #e91e63; font-weight: 700; }
.sec-status-pending { color: #ff9800; }
.sec-status-blocked { color: #4caf50; }
.sec-status-cleared { color: #888; }

.sec-chart-row { display: flex; gap: 16px; flex-wrap: wrap; }
.sec-chart-col { flex: 1; min-width: 260px; }
.sec-pie-chart { width: 160px; height: 160px; border-radius: 50%; position: relative; margin: 0 auto 12px; background: conic-gradient(
    #e91e63 0deg 45deg,
    #9c27b0 45deg 90deg,
    #3f51b5 90deg 135deg,
    #009688 135deg 180deg,
    #4caf50 180deg 225deg,
    #ff9800 225deg 270deg,
    #f44336 270deg 315deg,
    #888 315deg 360deg
); display: flex; align-items: center; justify-content: center; }
.sec-pie-center { width: 80px; height: 80px; background: #1a1a2e; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #888; text-align: center; }
.sec-bar-chart { display: flex; align-items: flex-end; gap: 6px; height: 140px; padding: 0 4px; }
.sec-bar { flex: 1; background: linear-gradient(to top, #7b2d8b, #e91e63); border-radius: 3px 3px 0 0; min-height: 4px; position: relative; }
.sec-bar-label { font-size: 0.65rem; color: #666; text-align: center; margin-top: 4px; word-break: break-all; }

.sec-alert-banner { background: rgba(220, 53, 69, 0.12); border: 1px solid rgba(220, 53, 69, 0.4); border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.sec-alert-banner .icon { font-size: 1.4rem; }
.sec-alert-banner .txt { flex: 1; font-size: 0.82rem; color: #ff8a8a; }
.sec-alert-banner .btn-freeze { background: #dc3545; border: none; color: #fff; padding: 5px 14px; border-radius: 6px; font-size: 0.78rem; cursor: pointer; white-space: nowrap; }
.sec-alert-banner .btn-freeze:hover { background: #bb2d3b; }

.sec-section-title { font-size: 0.9rem; font-weight: 600; color: #ccc; margin: 20px 0 10px; padding-bottom: 6px; border-bottom: 1px solid #333; }
</style>

<?php if (!empty($secAlertBanner)): ?>
<div class="sec-alert-banner">
    <span class="icon">🚨</span>
    <div class="txt">
        <strong>异地登录告警</strong> — 检测到异常登录：
        用户 <strong><?= htmlspecialchars($secAlertBanner['username'] ?? '?') ?></strong>
        于 <?= htmlspecialchars($secAlertBanner['login_at'] ?? '') ?>
        在 <strong><?= htmlspecialchars($secAlertBanner['location'] ?? '?') ?></strong>（IP: <?= htmlspecialchars($secAlertBanner['ip'] ?? '?') ?>）登录
    </div>
    <button class="btn-freeze" onclick="freezeSession('<?= $secAlertBanner['session_id'] ?? '' ?>')">立即冻结</button>
</div>
<?php endif; ?>

<!-- 安全概览卡片 -->
<div class="sec-chart-row mb-3">
    <div class="sec-mon-card">
        <div class="icon">🔑</div>
        <div class="info">
            <div class="num"><?= $secTodayLogins ?? 0 ?></div>
            <div class="lbl">今日登录次数</div>
        </div>
        <?php if (($secLoginGrowth ?? 0) !== 0): ?>
        <span class="trend <?= ($secLoginGrowth ?? 0) > 0 ? 'up' : 'down' ?>">
            <?= ($secLoginGrowth ?? 0) > 0 ? '↑' : '↓' ?> <?= abs($secLoginGrowth ?? 0) ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="sec-mon-card">
        <div class="icon">🌍</div>
        <div class="info">
            <div class="num"><?= $secUniqueLocations ?? 0 ?></div>
            <div class="lbl">登录地区数</div>
        </div>
    </div>
    <div class="sec-mon-card">
        <div class="icon">📱</div>
        <div class="info">
            <div class="num"><?= $secUnknownDevices ?? 0 ?></div>
            <div class="lbl">未知设备</div>
        </div>
    </div>
    <div class="sec-mon-card">
        <div class="icon">💬</div>
        <div class="info">
            <div class="num"><?= $secActiveSessions ?? 0 ?></div>
            <div class="lbl">活跃会话</div>
        </div>
    </div>
</div>

<div class="sec-chart-row">
    <!-- 攻击来源分布 -->
    <div class="sec-chart-col">
        <div class="sec-section-title">🌐 攻击来源分布</div>
        <div class="sec-pie-chart">
            <div class="sec-pie-center">来源<br>分布</div>
        </div>
        <?php if (!empty($secLocationStats)): foreach ($secLocationStats as $s): ?>
        <div class="d-flex justify-content-between small py-1" style="color:#888; font-size:0.78rem;">
            <span><?= htmlspecialchars($s['location'] ?: '未知') ?></span>
            <span style="color:#ccc;"><?= $s['cnt'] ?> 次</span>
        </div>
        <?php endforeach; else: ?>
        <div class="small text-muted">暂无数据</div>
        <?php endif; ?>
    </div>

    <!-- 攻击类型分布 -->
    <div class="sec-chart-col">
        <div class="sec-section-title">⚔️ 攻击类型分布</div>
        <div class="sec-bar-chart" id="secBarChart">
            <?php
            $maxCnt = 0;
            if (!empty($secTypeStats)) {
                foreach ($secTypeStats as $s) { if (($s['cnt'] ?? 0) > $maxCnt) $maxCnt = $s['cnt']; }
                foreach ($secTypeStats as $s):
                    $h = $maxCnt > 0 ? max(4, round(($s['cnt'] / $maxCnt) * 120)) : 4;
            ?>
            <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:2px;">
                <div class="sec-bar" style="height:<?= $h ?>px;"></div>
                <div class="sec-bar-label"><?= htmlspecialchars($s['attack_type'] ?: '?') ?></div>
                <div style="font-size:0.65rem;color:#666;"><?= $s['cnt'] ?></div>
            </div>
            <?php
                endforeach;
            } else {
                echo '<div class="small text-muted">暂无数据</div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- 近期登录日志 -->
<div class="sec-section-title">📋 近期登录日志</div>
<?php if (!empty($secLoginHistory)): ?>
<table class="sec-event-table">
    <thead>
        <tr>
            <th>登录时间</th>
            <th>用户</th>
            <th>地区</th>
            <th>IP</th>
            <th>设备</th>
            <th>状态</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($secLoginHistory as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h['login_at'] ?? '') ?></td>
            <td><?= htmlspecialchars($h['username'] ?? ($h['user_id'] ?? '')) ?></td>
            <td><?= htmlspecialchars($h['location'] ?? '—') ?></td>
            <td style="font-family:monospace;font-size:0.78rem;"><?= htmlspecialchars($h['ip'] ?? '') ?></td>
            <td><?= htmlspecialchars($h['device_type'] ?? '未知') ?></td>
            <td>
                <?php if (!empty($h['is_anomalous'])): ?>
                <span class="sec-sev-high">⚠ 异常</span>
                <?php else: ?>
                <span class="sec-status-blocked">✓ 正常</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="text-muted small">暂无登录记录</div>
<?php endif; ?>

<!-- 近期攻击记录 -->
<div class="sec-section-title">🛡️ 近期攻击记录</div>
<?php if (!empty($secAttackHistory)): ?>
<table class="sec-event-table">
    <thead>
        <tr>
            <th>时间</th>
            <th>来源 IP</th>
            <th>目标</th>
            <th>类型</th>
            <th>严重程度</th>
            <th>状态</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($secAttackHistory as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['created_at'] ?? '') ?></td>
            <td style="font-family:monospace;font-size:0.78rem;"><?= htmlspecialchars($e['source_ip'] ?? '') ?></td>
            <td><?= htmlspecialchars($e['target'] ?? '—') ?></td>
            <td><?= htmlspecialchars($e['attack_type'] ?? '—') ?></td>
            <td>
                <?php
                $sev = $e['severity'] ?? 'low';
                $sevClass = 'sec-sev-' . $sev;
                $sevLabel = ['low'=>'低', 'medium'=>'中', 'high'=>'高', 'critical'=>'严重'];
                ?>
                <span class="<?= $sevClass ?>"><?= $sevLabel[$sev] ?? $sev ?></span>
            </td>
            <td>
                <?php
                $st = $e['status'] ?? 'pending';
                $stClass = 'sec-status-' . $st;
                $stLabel = ['pending'=>'待处理', 'blocked'=>'已拦截', 'cleared'=>'已清除'];
                ?>
                <span class="<?= $stClass ?>"><?= $stLabel[$st] ?? $st ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="text-muted small">暂无攻击记录</div>
<?php endif; ?>

<script>
function freezeSession(sessionId) {
    if (!sessionId) return;
    if (!confirm('确定要冻结该会话吗？')) return;
    fetch(window.location.pathname + window.location.search, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'action=security_freeze_session&session_id=' + encodeURIComponent(sessionId)
    }).then(r => r.json()).then(d => {
        if (d.ok) {
            alert('会话已冻结');
            location.reload();
        } else {
            alert('操作失败：' + (d.error || '未知错误'));
        }
    });
}
</script>