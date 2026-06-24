<?php
/** @var string $appName */
use App\Service\Config;
use App\Model\SystemSetting;
use App\Model\Ledger;
use App\Service\LedgerContext;
use App\License\LicenseBootstrap;
use App\License\LicenseClient;

$appVersion = Config::get('app.version', 'v1.0.0');
$cssPath = __DIR__ . '/../assets/css/app.css';
$cssVersion = is_file($cssPath) ? (string)filemtime($cssPath) : $appVersion;

// 授权检查
LicenseClient::init();
$licStatus = LicenseBootstrap::check();
$licTrial = $licStatus['trial_days'];
$licActivated = $licStatus['activated'];
$licExpired = $licStatus['expired'];
$currentRoute = $_GET['route'] ?? '';

// 正念自动签到：开启后任意页面访问时，当天未签则自动签
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
    try {
        $autoCheckinUid = (int)$_SESSION['user_id'];
        $autoCfg = \App\Model\MindfulnessConfig::get($autoCheckinUid);
        if (!empty($autoCfg['auto_checkin'])) {
            $today = date('Y-m-d');
            if (!\App\Model\MindfulnessCheckin::isCheckedIn($autoCheckinUid, $today)) {
                \App\Model\MindfulnessCheckin::checkin($autoCheckinUid, $today, (float)$autoCfg['checkin_score']);
            }
        }
    } catch (\Throwable $e) {}
}
$systemSetting = SystemSetting::get();
$siteIconSvg = isset($systemSetting['site_icon_svg']) ? $systemSetting['site_icon_svg'] : null;
$miniappEnabled = (bool)Config::get('wechat.enable_miniapp', true);
$miniappsList = [];
if ($miniappEnabled) {
    static $miniappsCache = null;
    if ($miniappsCache !== null) {
        $miniappsList = $miniappsCache;
    } else {
        try {
            $pdoMa = \App\Service\Database::getConnection();
            $miniappsList = $pdoMa->query("SELECT * FROM miniapps ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { $miniappsList = []; }
        $miniappsCache = $miniappsList;
    }
}

$ledgerList = [];
$activeLedgerId = 0;
try {
    $uid = (int)(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
    if ($uid > 0) {
        $activeLedgerId = LedgerContext::requireActiveLedgerId($uid);
        if ($activeLedgerId > 0) {
            $ledgerList = Ledger::listForUser($uid);
        }
    }
} catch (\Throwable $e) {
    $ledgerList = [];
    $activeLedgerId = 0;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <title><?= htmlspecialchars(isset($systemSetting['site_name']) && $systemSetting['site_name'] !== '' ? $systemSetting['site_name'] : $appName) ?> - <?= htmlspecialchars($_SESSION['current_page_title'] ?? '首页') ?></title>
    <?php if (!empty($siteIconSvg)): ?>
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode($siteIconSvg) ?>">
    <?php endif; ?>
    <link rel="manifest" href="/public/manifest.json">
    <?php $bootstrapCssLocal = __DIR__ . '/../assets/vendor/bootstrap/bootstrap.min.css'; ?>
    <?php if (is_file($bootstrapCssLocal)): ?>
        <link href="/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <?php $choicesCssLocal = __DIR__ . '/../assets/vendor/choices/choices.min.css'; if (is_file($choicesCssLocal)): ?>
        <link rel="stylesheet" href="/assets/vendor/choices/choices.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <?php endif; ?>
	<link href="/assets/css/app.css?v=<?= htmlspecialchars($cssVersion) ?>" rel="stylesheet">
    <?php if (!empty($systemSetting['bg_image_path'])): ?>
    <style id="bg-image-style">
        body.theme-light,
        body.theme-dark {
            background: url("/uploads/<?= htmlspecialchars($systemSetting['bg_image_path']) ?>") center center / cover no-repeat fixed !important;
        }
        /* 白天模式背景图遮罩 */
        body.theme-light.has-bg-image::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.55);
            z-index: 0;
            pointer-events: none;
        }
        /* 暗黑模式背景图遮罩（更透明，保持氛围感） */
        body.theme-dark.has-bg-image::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.75);
            z-index: 0;
            pointer-events: none;
        }
        body.has-bg-image > *:not(.modal) {
            position: relative;
            z-index: 1;
        }
        /* 有背景图时侧边栏毛玻璃效果 */
        body.has-bg-image .sidebar {
            background: rgba(255, 255, 255, 0.45) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        /* 有背景图时顶部栏毛玻璃 */
        body.has-bg-image .navbar.bg-white {
            background: rgba(255, 255, 255, 0.4) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        body.theme-dark.has-bg-image .sidebar {
            background: rgba(30, 41, 59, 0.75) !important;
        }
    </style>
    <?php endif; ?>
</head>
<?php
$themeMode = isset($_SESSION['theme_mode']) ? $_SESSION['theme_mode'] : 'light';
$themeClass = $themeMode === 'dark' ? 'theme-dark' : 'theme-light';
$themeIcon = $themeMode === 'dark' ? '🌙' : '☀';
$themeTitle = $themeMode === 'dark' ? '切换为白天模式' : '切换为夜间模式';
?>
<body class="<?= $themeClass ?><?= !empty($systemSetting['bg_image_path']) ? ' has-bg-image' : '' ?>">
<div class="d-flex" style="min-height:100vh;">
    <!-- 左侧侧边栏菜单 -->
    <nav class="sidebar flex-shrink-0 d-flex flex-column">
        <div class="sidebar-header px-3 py-3 border-bottom d-flex align-items-center gap-2">
            <?php if (!empty($siteIconSvg)): ?>
                <span style="width:22px;height:22px;flex-shrink:0;display:inline-flex;"><?= $siteIconSvg ?></span>
            <?php else: ?>
                <span style="font-size:1.2rem;flex-shrink:0;">💰</span>
            <?php endif; ?>
            <div class="fw-bold"><?= htmlspecialchars(isset($systemSetting['site_name']) ? $systemSetting['site_name'] : 'SanS三石记账') ?></div>
        </div>
                <div class="sidebar-menu mt-2">
            <!-- 首页（独立） -->
            <a href="/public/index.php" class="list-group-item list-group-item-action border-0">
                <span class="menu-icon" style="font-size:1.15rem;line-height:1;">🏠</span>
                首页
            </a>

            <!-- 记账（悬浮子菜单） -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-bookkeeping">
                <div class="sidebar-section-header">
                    <span>📝 记账</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 规划 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-plan">
                <div class="sidebar-section-header">
                    <span>📊 规划</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 导航 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-nav">
                <div class="sidebar-section-header">
                    <span>🧭 导航</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- EasyTodo -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-easytodo">
                <div class="sidebar-section-header">
                    <span>✅ EasyTodo</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 图书 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-books">
                <div class="sidebar-section-header">
                    <span>📚 图书</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 负债 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-debt">
                <div class="sidebar-section-header">
                    <span>💳 负债</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 报销 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-reimbursement">
                <div class="sidebar-section-header">
                    <span>🧾 报销</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 考勤 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-attendance">
                <div class="sidebar-section-header">
                    <span>📋 考勤</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 简历 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-resume">
                <div class="sidebar-section-header">
                    <span>📄 简历</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

    <!-- 正念 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-mindfulness">
                <div class="sidebar-section-header">
                    <span>💊 正念</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

    <!-- 娱乐 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-entertainment">
                <div class="sidebar-section-header">
                    <span>🎮 娱乐</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 项目 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-project">
                <div class="sidebar-section-header">
                    <span>📂 项目</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 工具箱 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-toolbox">
                <div class="sidebar-section-header">
                    <span>🧰 工具箱</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>

            <!-- 系统 -->
            <div class="sidebar-section sidebar-flyout-trigger" id="flyout-system">
                <div class="sidebar-section-header">
                    <span>⚙️ 系统</span><span class="section-arrow" style="transform:rotate(90deg)">▸</span>
                </div>
            </div>
        </div>
        <div class="mt-auto px-3 py-3 border-top small text-muted">
            <div>版本 <?= htmlspecialchars($appVersion) ?></div>
            <div>&copy; 2025-<?= date('Y') ?> SanS 三石</div>
        </div>
    </nav>

    <!-- 移动端侧边栏遮罩层 -->
    <div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

    <!-- 记账悬浮子菜单（放在body层级，不受sidebar层叠上下文限制） -->
    <div class="sidebar-flyout" id="flyout-bookkeeping-menu">
        <a href="/public/index.php?route=transaction-create" class="flyout-item">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            记账
        </a>
        <a href="/public/index.php?route=transactions" class="flyout-item">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            明细
        </a>
        <a href="/public/index.php?route=reports" class="flyout-item">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
            统计报表
        </a>
        <div style="border-top:1px solid rgba(148,163,184,0.15);margin:4px 12px;"></div>
        <a href="/public/index.php?route=categories" class="flyout-item">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
            分类管理
        </a>
        <a href="/public/index.php?route=items" class="flyout-item">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
            项目管理
        </a>
        <a href="/public/index.php?route=accounts" class="flyout-item">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            账户管理
        </a>
        <a href="/public/index.php?route=icons" class="flyout-item">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            图标库
        </a>
    </div>

    <!-- 规划 -->
    <div class="sidebar-flyout" id="flyout-plan-menu">
        <a href="/public/index.php?route=assets" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>资产管理</a>
        <a href="/public/index.php?route=subscriptions" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>订阅记录</a>
        <a href="/public/index.php?route=budget" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>预算管理</a>
        <a href="/public/index.php?route=goals" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>目标管理</a>
        <a href="/public/index.php?route=finance" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>理财管理</a>
    </div>

    <!-- 导航 -->
    <div class="sidebar-flyout" id="flyout-nav-menu">
        <a href="/public/index.php?route=nav-my" class="flyout-item"><span class="menu-icon">🌐</span>我的导航</a>
        <a href="/public/index.php?route=nav-config" class="flyout-item"><span class="menu-icon">⚙️</span>导航配置</a>
    </div>

    <!-- EasyTodo -->
    <div class="sidebar-flyout" id="flyout-easytodo-menu">
        <a href="/public/index.php?route=easytodo-tasks" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>待办管理</a>
        <a href="/public/index.php?route=easytodo-countdowns" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>倒计时</a>
        <a href="/public/index.php?route=easytodo-pomodoro" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>番茄钟</a>
        <a href="/public/index.php?route=easytodo-memos" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>备忘录</a>
    </div>

    <!-- 图书 -->
    <div class="sidebar-flyout" id="flyout-books-menu">
        <a href="/public/index.php?route=books" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>在线阅览</a>
        <a href="/public/index.php?route=books-config" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>图书配置</a>
    </div>

    <!-- 负债 -->
    <div class="sidebar-flyout" id="flyout-debt-menu">
        <a href="/public/index.php?route=debt-current" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>当月应还</a>
        <a href="/public/index.php?route=debt-summary" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 118 2.83"/><path d="M22 12A10 10 0 0012 2v10z"/></svg>汇总统计</a>
        <a href="/public/index.php?route=debt-config" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 022 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>负债配置</a>
    </div>

    <!-- 报销 -->
    <div class="sidebar-flyout" id="flyout-reimbursement-menu">
        <a href="/public/index.php?route=reimbursement-list" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>报销列表</a>
        <a href="/public/index.php?route=reimbursement-config" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>报销配置</a>
    </div>

    <!-- 考勤 -->
    <div class="sidebar-flyout" id="flyout-attendance-menu">
        <a href="/public/index.php?route=attendance-schedule" class="flyout-item">📅 排班管理</a>
        <a href="/public/index.php?route=attendance-shift" class="flyout-item">📋 出勤管理</a>
        <a href="/public/index.php?route=attendance-salary" class="flyout-item">💰 薪资计算</a>
        <a href="/public/index.php?route=attendance-deduction" class="flyout-item">💸 扣款管理</a>
        <a href="/public/index.php?route=attendance-social" class="flyout-item">🏛️ 社保公积金</a>
        <a href="/public/index.php?route=attendance-performance" class="flyout-item">📊 绩效管理</a>
    </div>

    <!-- 娱乐 -->
    <div class="sidebar-flyout" id="flyout-entertainment-menu">
        <a href="/public/index.php?route=entertainment" class="flyout-item"><span class="menu-icon">📈</span>炒股</a>
        <a href="/public/index.php?route=life" class="flyout-item"><span class="menu-icon">🎲</span>我的人生</a>
    </div>

    <!-- 项目 -->
    <div class="sidebar-flyout" id="flyout-project-menu">
        <a href="/public/index.php?route=project-list" class="flyout-item"><span class="menu-icon">📋</span>项目列表</a>
    </div>

    <!-- 工具箱 -->
    <div class="sidebar-flyout" id="flyout-toolbox-menu">
        <a href="/public/index.php?route=toolbox-password-vault" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>密码箱</a>
        <a href="/public/index.php?route=toolbox-cny" class="flyout-item"><span class="menu-icon">💴</span>人民币大写转换</a>
        <a href="/public/index.php?route=toolbox-shelf-life" class="flyout-item"><span class="menu-icon">📆</span>保质期计算器</a>
        <a href="/public/index.php?route=toolbox-qrcode" class="flyout-item"><span class="menu-icon">📱</span>二维码生成器</a>
        <a href="/public/index.php?route=toolbox-morse" class="flyout-item"><span class="menu-icon">📡</span>摩斯电码编码解码</a>
        <a href="/public/index.php?route=toolbox-calendar" class="flyout-item"><span class="menu-icon">📅</span>万年历</a>
        <a href="/public/index.php?route=toolbox-forum-assistant" class="flyout-item"><span class="menu-icon">🌐</span>论坛助手</a>
        <a href="/public/index.php?route=toolbox-today-do" class="flyout-item"><span class="menu-icon">🎲</span>今天干嘛</a>
        <a href="/public/index.php?route=naming" class="flyout-item"><span class="menu-icon">✍️</span>取名助手</a>
    </div>

    <!-- 系统 -->
    <div class="sidebar-flyout" id="flyout-system-menu">
        <a href="/public/index.php?route=feedback" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>问题反馈</a>
        <a href="/public/index.php?route=settings" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06-.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 022 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>系统设置</a>
        <a href="/public/index.php?route=changelog" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>更新日志</a>
        <a href="/public/index.php?route=security" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>安全监控</a>
        <?php if ((isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user') === 'admin'): ?>
        <div style="border-top:1px solid rgba(148,163,184,0.15);margin:4px 12px;"></div>
        <a href="/public/index.php?route=system-icons" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="12" cy="12" r="3"/></svg>系统图标库</a>
        <a href="/public/index.php?route=backup" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>数据备份</a>
        <a href="/public/index.php?route=license-activate" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>授权激活</a>
        <a href="/public/index.php?route=license-admin-panel" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 14.66V20a2 2 0 01-2 2H4a2 2 0 01-2-2V14.66"/><path d="M8 12h8"/><rect x="8" y="2" width="8" height="8" rx="1"/></svg>授权管理</a>
        <a href="/public/index.php?route=license-admin" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>系统日志</a>
        <a href="/public/index.php?route=scheduler" class="flyout-item"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>定时任务</a>
        <?php endif; ?>
    </div>

    <!-- 正念 -->
    <div class="sidebar-flyout" id="flyout-mindfulness-menu">
        <a href="/public/index.php?route=mindfulness-checkin" class="flyout-item"><span class="menu-icon">💊</span>签到</a>
        <a href="/public/index.php?route=mindfulness-treasure" class="flyout-item"><span class="menu-icon">🕳️</span>树洞</a>
        <a href="/public/index.php?route=mindfulness-config" class="flyout-item"><span class="menu-icon">⚙️</span>配置</a>
    </div>

    <!-- 简历 -->
    <div class="sidebar-flyout" id="flyout-resume-menu">
        <a href="/public/index.php?route=resume-builder" class="flyout-item"><span class="menu-icon">📝</span>简历配置</a>
        <a href="/public/index.php?route=resume-preview" class="flyout-item"><span class="menu-icon">👁️</span>简历预览</a>
    </div>

    <!-- 右侧主内容区域 -->
    <div class="flex-grow-1 d-flex flex-column">
        <header class="navbar navbar-light bg-white shadow-sm px-3">
            <div class="container-fluid">
				<div class="d-flex align-items-center">
					<button class="btn btn-outline-secondary btn-sm d-md-none me-2" id="sidebarToggle" type="button">
						☰
					</button>
					<span class="navbar-brand mb-0 h6 mb-0"><?= htmlspecialchars(isset($_SESSION['current_page_title']) ? $_SESSION['current_page_title'] : 'SanS三石记账') ?></span>
				</div>
	    		<div class="d-flex align-items-center">
                        <?php if ($activeLedgerId > 0 && !empty($ledgerList)): ?>
                        <form method="post" action="/public/index.php?route=ledger-switch" class="me-2">
                            <select name="ledger_id" class="form-select form-select-sm" style="max-width:240px;" onchange="this.form.submit()">
                                <?php foreach ($ledgerList as $l):
                                    $type = (string)(isset($l['type']) ? $l['type'] : '');
                                    $prefix = $type === 'shared' ? '共享:' : '个人:';
                                    $role = (string)(isset($l['member_role']) ? $l['member_role'] : '');
                                    $label = $prefix . (string)(isset($l['name']) ? $l['name'] : '');
                                    if ($role !== '') {
                                        $label .= ' (' . $role . ')';
                                    }
                                ?>
                                <option value="<?= (int)(isset($l['id']) ? $l['id'] : 0) ?>" <?= ((int)(isset($l['id']) ? $l['id'] : 0) === (int)$activeLedgerId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php endif; ?>
                        <button
                            type="button"
                            id="themeToggleBtn"
                            class="btn btn-outline-secondary btn-sm me-2 btn-theme-toggle"
                            title="<?= htmlspecialchars($themeTitle) ?>"
                            aria-label="<?= htmlspecialchars($themeTitle) ?>"
                        >
                            <?= htmlspecialchars($themeIcon) ?>
                        </button>
                        <?php
                        $sessionNickname = isset($_SESSION['user_nickname']) ? $_SESSION['user_nickname'] : '';
                        $sessionAvatar = isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : null;
                        ?>
                        <div class="d-flex align-items-center me-3">
                            <?php if (!empty($sessionAvatar)): ?>
                                 <img src="<?= htmlspecialchars($sessionAvatar) ?>" alt="头像" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-size:0.75rem;">👤</div>
                            <?php endif; ?>
                            <span class="small text-muted text-nowrap"><?= htmlspecialchars($sessionNickname) ?></span>
                        </div>
                        <?php if ($miniappEnabled): ?>
                        <button
                            type="button"
                            id="miniAppBtn"
                            class="btn btn-outline-primary btn-sm me-2"
                            data-bs-toggle="modal"
                            data-bs-target="#miniAppModal"
                        >使用小程序</button>
                        <?php endif; ?>
                        <a class="btn btn-outline-secondary btn-sm" href="/public/index.php?route=logout">退出</a>
                    </div>
            </div>
        </header>
        <?php if (!$licActivated && $licTrial <= 0 && $currentRoute !== 'license-activate'): ?>
        <div style="background:#fef2f2;border-bottom:2px solid #fca5a5;padding:6px 16px;text-align:center;font-size:0.82rem;color:#dc2626">
            🔒 试用已到期 · 部分功能受限 · <a href="/public/index.php?route=license-activate" style="color:#dc2626;font-weight:700;text-decoration:underline">激活授权</a>
            <?php if ($licActivated): ?><?php else: ?><?php endif; ?>
        </div>
        <?php elseif (!$licActivated && $licTrial > 0): ?>
        <div style="background:#fffbeb;border-bottom:2px solid #fcd34d;padding:6px 16px;text-align:center;font-size:0.82rem;color:#b45309">
            ⏳ 试用期还剩 <?= $licTrial ?> 天 · <a href="/public/index.php?route=license-activate" style="color:#b45309;font-weight:700;text-decoration:underline">获取授权码</a>
        </div>
        <?php endif; ?>
        <main class="flex-grow-1 py-4 px-3 px-md-4">
            <div class="container-fluid">
                <?php include __DIR__ . '/' . $view . '.php'; ?>
            </div>
        </main>
    </div>
</div>

<!-- 全局凭证大图预览弹窗 -->
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0 text-center">
                <img src="" alt="凭证预览" id="attachmentPreviewImage" class="img-fluid attachment-modal-img">
            </div>
        </div>
    </div>
</div>

<?php if ($miniappEnabled): ?>
<!-- 小程序列表弹窗 -->
<div class="modal fade" id="miniAppModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title" style="font-size:0.95rem">📱 使用小程序</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <?php if (!empty($miniappsList)): ?>
                    <?php foreach ($miniappsList as $ma): ?>
                    <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded-3" style="background:rgba(255,255,255,0.3);border:1px solid rgba(255,255,255,0.4)">
                        <span style="font-size:0.9rem;font-weight:600"><?= htmlspecialchars($ma['name']) ?></span>
                        <?php if (!empty($ma['qrcode_path'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.78rem"
                                onclick="showMiniappQrcode('<?= htmlspecialchars($ma['name']) ?>', '/uploads/<?= htmlspecialchars($ma['qrcode_path']) ?>')">查看小程序码</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-2" style="font-size:0.85rem">暂未配置小程序</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 小程序码查看弹窗 -->
<div class="modal fade" id="miniappQrcodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title" style="font-size:0.95rem" id="miniappQrcodeTitle">小程序码</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-3">
                <img id="miniappQrcodeImg" src="" alt="小程序码" class="img-fluid" style="max-width:240px;border-radius:8px;">
            </div>
        </div>
    </div>
</div>
<script>
function showMiniappQrcode(name, src) {
    document.getElementById('miniappQrcodeTitle').textContent = name + ' 小程序码';
    document.getElementById('miniappQrcodeImg').src = src;
    var listModal = bootstrap.Modal.getInstance(document.getElementById('miniAppModal'));
    if (listModal) listModal.hide();
    setTimeout(function() {
        new bootstrap.Modal(document.getElementById('miniappQrcodeModal')).show();
    }, 300);
}
</script>
<?php endif; ?>
<?php if (!empty($latestAnnouncement) && is_array($latestAnnouncement)): ?>
<!-- PC 首页公告弹窗 -->
<div class="modal fade" id="pcAnnouncementModal" tabindex="-1" aria-hidden="true" data-announcement-id="<?= (int)(isset($latestAnnouncement['id']) ? $latestAnnouncement['id'] : 0) ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">系统公告</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-announcement-close="1"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-semibold mb-2"><?= htmlspecialchars(isset($latestAnnouncement['title']) ? $latestAnnouncement['title'] : '') ?></h6>
                <div class="small text-muted mb-2"><?= htmlspecialchars(isset($latestAnnouncement['scheduled_at']) ? $latestAnnouncement['scheduled_at'] : '') ?></div>
                <div class="announcement-content small" style="white-space:pre-wrap;">
                    <?= nl2br(htmlspecialchars(isset($latestAnnouncement['content']) ? $latestAnnouncement['content'] : '')) ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal" data-announcement-close="1">知道了</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php $bootstrapJsLocal = __DIR__ . '/../assets/vendor/bootstrap/bootstrap.bundle.min.js'; ?>
<?php if (is_file($bootstrapJsLocal)): ?>
<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<?php $choicesJsLocal = __DIR__ . '/../assets/vendor/choices/choices.min.js'; if (is_file($choicesJsLocal)): ?>
<script src="/assets/vendor/choices/choices.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<?php endif; ?>
<script>
// 简单用户名推荐：在当前用户名后追加 3 位随机数字
function suggestUsername() {
    var input = document.querySelector('#modalUsernameChange input[name="new_username"]');
    if (!input) return;
    var base = input.value.trim();
    if (!base) {
        base = 'user';
    }
    var suffix = Math.floor(100 + Math.random() * 900); // 100-999
    input.value = base.replace(/[^a-zA-Z0-9_]/g, '') + '_' + suffix;
}

// 凭证图片点击放大预览（取消悬停预览）
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('attachmentPreviewModal');
    var modalImg = document.getElementById('attachmentPreviewImage');

    function bindAttachmentPreview(root) {
        var triggers = (root || document).querySelectorAll('[data-attachment-preview]');
        triggers.forEach(function (el) {
            var url = el.getAttribute('data-attachment-preview');
            if (!url) return;
            el.addEventListener('click', function (e) {
                e.preventDefault();
                if (!modalEl || !modalImg || !window.bootstrap) return;
                modalImg.src = url;
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            });
        });
    }

    bindAttachmentPreview(document);

    // 移动端侧边栏收起/展开
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarOverlay = document.getElementById('sidebarOverlay');

    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    var sidebarLinks = document.querySelectorAll('.sidebar .list-group-item');
    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });

    // 悬浮子菜单（通用：所有 sidebar-flyout-trigger）
    var allFlyouts = [];
    document.querySelectorAll('.sidebar-flyout-trigger').forEach(function (trigger) {
        var id = trigger.id; // e.g. "flyout-bookkeeping"
        var flyout = document.getElementById(id + '-menu');
        if (!flyout) return;

        var hideTimer = null;
        var pair = { trigger: trigger, flyout: flyout };
        allFlyouts.push(pair);

        function positionFlyout() {
            var rect = trigger.getBoundingClientRect();
            flyout.style.top = rect.top + 'px';
            flyout.style.left = rect.right + 'px';
        }

        function showFlyout() {
            if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
            positionFlyout();
            flyout.style.display = 'block';
        }
        function scheduleHide() {
            hideTimer = setTimeout(function () { flyout.style.display = 'none'; }, 200);
        }

        trigger.addEventListener('mouseenter', showFlyout);
        trigger.addEventListener('mouseleave', scheduleHide);
        flyout.addEventListener('mouseenter', function () {
            if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
        });
        flyout.addEventListener('mouseleave', scheduleHide);

        // 侧边栏滚动时更新位置
        var sidebar = trigger.closest('.sidebar');
        if (sidebar) {
            sidebar.addEventListener('scroll', function () {
                if (flyout.style.display === 'block') positionFlyout();
            });
        }

        // 移动端点击
        trigger.querySelector('.sidebar-section-header').addEventListener('click', function (e) {
            if (window.innerWidth <= 768) {
                e.stopPropagation();
                var isOpen = flyout.style.display === 'block';
                allFlyouts.forEach(function (p) { p.flyout.style.display = 'none'; });
                if (!isOpen) {
                    positionFlyout();
                    flyout.style.display = 'block';
                }
            }
        });
    });

    // 点击页面其他地方关闭所有浮窗
    document.addEventListener('click', function (e) {
        allFlyouts.forEach(function (p) {
            if (!p.trigger.contains(e.target) && !p.flyout.contains(e.target)) {
                p.flyout.style.display = 'none';
            }
        });
    });

    // 日/夜模式一键切换
    var themeBtn = document.getElementById('themeToggleBtn');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            if (themeBtn.disabled) return;
            themeBtn.disabled = true;
            var current = document.body.classList.contains('theme-dark') ? 'dark' : 'light';
            var nextMode = current === 'dark' ? 'light' : 'dark';
            document.body.classList.remove('theme-light', 'theme-dark');
            document.body.classList.add(nextMode === 'dark' ? 'theme-dark' : 'theme-light');
            themeBtn.textContent = nextMode === 'dark' ? '☀' : '🌙';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/public/index.php?route=theme-toggle', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.timeout = 5000;
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    themeBtn.disabled = false;
                    if (xhr.status !== 200) {
                        document.body.classList.remove('theme-light', 'theme-dark');
                        document.body.classList.add(current === 'dark' ? 'theme-dark' : 'theme-light');
                        themeBtn.textContent = current === 'dark' ? '☀' : '🌙';
                    }
                }
            };
            xhr.ontimeout = function () {
                themeBtn.disabled = false;
                window.location.reload();
            };
            xhr.send('current=' + encodeURIComponent(current));
        });
    }

    // 兜底：如果 data-bs-* 未生效，手动触发
    var miniBtn = document.getElementById('miniAppBtn');
    if (miniBtn && window.bootstrap) {
        miniBtn.addEventListener('click', function (e) {
            // 若已由 data-bs-* 处理则不重复
            if (e.defaultPrevented) return;
            var modalEl = document.getElementById('miniAppModal');
            if (!modalEl) return;
            var m = bootstrap.Modal.getOrCreateInstance(modalEl);
            m.show();
        }, { once: false });
    }

    // PC 首页系统公告弹窗
    try {
        var annModal = document.getElementById('pcAnnouncementModal');
        if (annModal && window.bootstrap) {
            var annId = annModal.getAttribute('data-announcement-id');
            var hasId = annId && parseInt(annId, 10) > 0;
            var markReadOnce = false;

            function markAnnouncementRead() {
                if (!hasId || markReadOnce) return;
                markReadOnce = true;
                try {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '/public/index.php?route=announcement-mark-read', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                    xhr.send('id=' + encodeURIComponent(String(annId)));
                } catch (e) {}
            }

            var modal = bootstrap.Modal.getOrCreateInstance(annModal);
            modal.show();

            var closeBtns = annModal.querySelectorAll('[data-announcement-close]');
            closeBtns.forEach(function (btn) {
                btn.addEventListener('click', markAnnouncementRead);
            });
            annModal.addEventListener('hidden.bs.modal', markAnnouncementRead);
        }
    } catch (e) {}

    // 兜底：清理可能残留的 Bootstrap 模态遮罩/锁定状态（避免整页无法点击)
    window.setTimeout(function () {
        try {
            var anyShownModal = document.querySelector('.modal.show');
            if (!anyShownModal) {
                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function (b) {
                    if (b && b.parentNode) b.parentNode.removeChild(b);
                });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.body.style.removeProperty('overflow');
            }
        } catch (e) {}
    }, 800);

    // 注册 Service Worker，用于 PWA 支持
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/public/sw.js').catch(function (err) {
            console.warn('ServiceWorker 注册失败:', err);
        });
    }

    // 图标库选择下拉启用搜索（适用于上千图标）
    try {
        if (window.Choices) {
            document.querySelectorAll('select.icon-library-select').forEach(function (select) {
                try {
                    if (select._choicesInstance) return;
                    var instance = new Choices(select, {
                        searchEnabled: true,
                        shouldSort: false,
                        position: 'bottom',
                        itemSelectText: '',
                        allowHTML: false,
                    });
                    select._choicesInstance = instance;
                } catch (e) {}
            });
        }
    } catch (e) {}
});
        // 侧边栏分组折叠（默认全部展开）
        document.querySelectorAll('.sidebar-section-header').forEach(function(header) {
            var targetId = header.getAttribute('data-target');
            if (!targetId) return;
            var body = document.querySelector(targetId);
            if (!body) return;
            var arrow = header.querySelector('.section-arrow');

            body.classList.add('show');
            if (arrow) arrow.textContent = '▾';

            header.addEventListener('click', function() {
                if (body.classList.contains('show')) {
                    body.classList.remove('show');
                    if (arrow) arrow.textContent = '▸';
                } else {
                    body.classList.add('show');
                    if (arrow) arrow.textContent = '▾';
                }
            });
        });
</script>
</body>
</html>
