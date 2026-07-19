<?php
/** @var string $appName */
use App\Service\Config;
use App\Model\SystemSetting;
use App\Service\LedgerContext;

$appVersion = $systemSetting['site_version'] ?? (include __DIR__ . '/../../config/config.php')['app']['version'] ?? 'v1.0.0';
$cssPath = __DIR__ . '/../../assets/css/app.css';
$cssVersion = is_file($cssPath) ? (string)filemtime($cssPath) : $appVersion;

$systemSetting = SystemSetting::get();
$siteIconSvg = isset($systemSetting['site_icon_svg']) ? $systemSetting['site_icon_svg'] : null;

// 菜单组定义 — 与PC端侧边栏保持一致
$menuGroups = [
    ['id'=>'home', 'icon'=>'🏠', 'name'=>'首页', 'route'=>'dashboard', 'tabs'=>[]],
    ['id'=>'bookkeeping', 'icon'=>'📝', 'name'=>'记账', 'route'=>'transaction-create', 'tabs'=>[
        ['name'=>'记账', 'route'=>'transaction-create'],
        ['name'=>'明细', 'route'=>'transactions'],
        ['name'=>'统计报表', 'route'=>'reports'],
        ['name'=>'分类管理', 'route'=>'categories'],
        ['name'=>'项目管理', 'route'=>'items'],
        ['name'=>'账户管理', 'route'=>'accounts'],
        ['name'=>'图标库', 'route'=>'icons'],
    ]],
    ['id'=>'plan', 'icon'=>'📊', 'name'=>'规划', 'route'=>'assets', 'tabs'=>[
        ['name'=>'资产管理', 'route'=>'assets'],
        ['name'=>'订阅记录', 'route'=>'subscriptions'],
        ['name'=>'预算管理', 'route'=>'budget'],
        ['name'=>'目标管理', 'route'=>'goals'],
        ['name'=>'理财管理', 'route'=>'finance'],
    ]],
    ['id'=>'nav', 'icon'=>'🧭', 'name'=>'导航', 'route'=>'nav-my', 'tabs'=>[
        ['name'=>'我的导航', 'route'=>'nav-my'],
        ['name'=>'导航配置', 'route'=>'nav-config'],
    ]],
    ['id'=>'easytodo', 'icon'=>'✅', 'name'=>'EasyTODO', 'route'=>'easytodo-tasks', 'hide'=>true, 'tabs'=>[]],
    ['id'=>'books', 'icon'=>'📚', 'name'=>'图书', 'route'=>'books', 'tabs'=>[
        ['name'=>'在线阅览', 'route'=>'books'],
        ['name'=>'图书配置', 'route'=>'books-config'],
    ]],
    ['id'=>'debt', 'icon'=>'💳', 'name'=>'负债', 'route'=>'debt-current', 'tabs'=>[
        ['name'=>'当月应还', 'route'=>'debt-current'],
        ['name'=>'汇总统计', 'route'=>'debt-summary'],
        ['name'=>'负债配置', 'route'=>'debt-config'],
    ]],
    ['id'=>'reimbursement', 'icon'=>'🧾', 'name'=>'报销', 'route'=>'reimbursement-list', 'tabs'=>[
        ['name'=>'报销列表', 'route'=>'reimbursement-list'],
        ['name'=>'报销配置', 'route'=>'reimbursement-config'],
    ]],
    ['id'=>'attendance', 'icon'=>'📋', 'name'=>'考勤', 'route'=>'attendance-schedule', 'tabs'=>[
        ['name'=>'排班管理', 'route'=>'attendance-schedule'],
        ['name'=>'出勤管理', 'route'=>'attendance-shift'],
        ['name'=>'薪资计算', 'route'=>'attendance-salary'],
        ['name'=>'扣款管理', 'route'=>'attendance-deduction'],
        ['name'=>'社保公积金', 'route'=>'attendance-social'],
        ['name'=>'绩效管理', 'route'=>'attendance-performance'],
    ]],
    ['id'=>'mindfulness', 'icon'=>'🧘', 'name'=>'正念', 'route'=>'mindfulness-checkin', 'tabs'=>[
        ['name'=>'签到', 'route'=>'mindfulness-checkin'],
        ['name'=>'树洞', 'route'=>'mindfulness-treasure'],
        ['name'=>'配置', 'route'=>'mindfulness-config'],
    ]],
    ['id'=>'resume', 'icon'=>'📄', 'name'=>'简历', 'route'=>'resume-builder', 'tabs'=>[
        ['name'=>'简历配置', 'route'=>'resume-builder'],
        ['name'=>'简历预览', 'route'=>'resume-preview'],
    ]],
    ['id'=>'entertainment', 'icon'=>'🎮', 'name'=>'娱乐', 'route'=>'entertainment', 'tabs'=>[
        ['name'=>'炒股', 'route'=>'entertainment'],
        ['name'=>'我的企业', 'route'=>'enterprise'],
        ['name'=>'我的人生', 'route'=>'life'],
    ]],
    ['id'=>'project', 'icon'=>'📂', 'name'=>'项目', 'route'=>'project-list', 'tabs'=>[
        ['name'=>'项目列表', 'route'=>'project-list'],
    ]],
    ['id'=>'toolbox', 'icon'=>'🧰', 'name'=>'工具箱', 'route'=>'toolbox-password-vault', 'tabs'=>[
        ['name'=>'密码箱', 'route'=>'toolbox-password-vault'],
        ['name'=>'大写转换', 'route'=>'toolbox-cny'],
        ['name'=>'保质期', 'route'=>'toolbox-shelf-life'],
        ['name'=>'二维码', 'route'=>'toolbox-qrcode'],
        ['name'=>'摩斯电码', 'route'=>'toolbox-morse'],
        ['name'=>'万年历', 'route'=>'toolbox-calendar'],
        ['name'=>'论坛助手', 'route'=>'toolbox-forum-assistant'],
        ['name'=>'今天干嘛', 'route'=>'toolbox-today-do'],
        ['name'=>'取名助手', 'route'=>'naming'],
    ]],
    ['id'=>'system', 'icon'=>'⚙️', 'name'=>'系统', 'route'=>'settings', 'hide'=>true, 'tabs'=>[]],
];

// 确定当前激活的菜单组
$currentRoute = $_GET['route'] ?? 'dashboard';
$activeGroupId = 'home';
// 路由到菜单组的映射
$routeToGroup = [
    'dashboard'=>'home',
    'transaction-create'=>'bookkeeping', 'transactions'=>'bookkeeping', 'reports'=>'bookkeeping',
    'categories'=>'bookkeeping', 'items'=>'bookkeeping', 'accounts'=>'bookkeeping', 'icons'=>'bookkeeping',
    'system-icons'=>'system',
    'assets'=>'plan', 'subscriptions'=>'plan', 'budget'=>'plan', 'goals'=>'plan', 'finance'=>'plan',
    'nav-my'=>'nav', 'nav-config'=>'nav',
    'easytodo-tasks'=>'easytodo', 'easytodo-countdowns'=>'easytodo', 'easytodo-pomodoro'=>'easytodo', 'easytodo-memos'=>'easytodo',
    'books'=>'books', 'books-config'=>'books', 'books-reader'=>'books',
    'debt-current'=>'debt', 'debt-summary'=>'debt', 'debt-config'=>'debt', 'debts'=>'debt',
    'reimbursement-list'=>'reimbursement', 'reimbursement-config'=>'reimbursement', 'reimbursement'=>'reimbursement',
    'attendance-schedule'=>'attendance', 'attendance-shift'=>'attendance', 'attendance-salary'=>'attendance',
    'attendance-deduction'=>'attendance', 'attendance-social'=>'attendance', 'attendance-performance'=>'attendance',
    'mindfulness-checkin'=>'mindfulness', 'mindfulness-treasure'=>'mindfulness', 'mindfulness-config'=>'mindfulness',
    'resume-builder'=>'resume', 'resume-preview'=>'resume',
    'entertainment'=>'entertainment', 'life'=>'entertainment', 'entertainment-api'=>'entertainment',
    'enterprise'=>'entertainment', 'enterprise-api'=>'entertainment', 'enterprise-mall'=>'entertainment',
    'enterprise-products'=>'entertainment', 'enterprise-rd'=>'entertainment', 'enterprise-sales'=>'entertainment',
    'enterprise-guide'=>'entertainment',
    'project-list'=>'project', 'project-detail'=>'project',
    'toolbox-password-vault'=>'toolbox', 'toolbox-cny'=>'toolbox', 'toolbox-shelf-life'=>'toolbox',
    'toolbox-qrcode'=>'toolbox', 'toolbox-morse'=>'toolbox', 'toolbox-calendar'=>'toolbox',
    'toolbox-forum-assistant'=>'toolbox', 'toolbox-today-do'=>'toolbox', 'naming'=>'toolbox',
    'feedback'=>'system', 'settings'=>'system', 'changelog'=>'system', 'security'=>'system',
    'backup'=>'system', 'scheduler'=>'system', 'license-activate'=>'system', 'license-admin-panel'=>'system',
];
if (isset($routeToGroup[$currentRoute])) {
    $activeGroupId = $routeToGroup[$currentRoute];
}

// 找到当前菜单组
$activeGroup = $menuGroups[0];
foreach ($menuGroups as $g) {
    if ($g['id'] === $activeGroupId) { $activeGroup = $g; break; }
}

$themeMode = isset($_SESSION['theme_mode']) ? $_SESSION['theme_mode'] : 'light';
$themeClass = $themeMode === 'dark' ? 'theme-dark' : 'theme-light';
$pageTitle = $_SESSION['current_page_title'] ?? '首页';
$sessionNickname = $_SESSION['user_nickname'] ?? '';
$sessionAvatar = $_SESSION['user_avatar'] ?? null;
$isAdmin = ($_SESSION['user_role'] ?? 'user') === 'admin';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(isset($systemSetting['site_name']) && $systemSetting['site_name'] !== '' ? $systemSetting['site_name'] : $appName) ?></title>
    <?php if (!empty($siteIconSvg)): ?>
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode($siteIconSvg) ?>">
    <?php endif; ?>
    <?php $bootstrapCssLocal = __DIR__ . '/../../assets/vendor/bootstrap/bootstrap.min.css'; ?>
    <?php if (is_file($bootstrapCssLocal)): ?>
        <link href="/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <?php $choicesCssLocal = __DIR__ . '/../../assets/vendor/choices/choices.min.css'; if (is_file($choicesCssLocal)): ?>
        <link rel="stylesheet" href="/assets/vendor/choices/choices.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <?php endif; ?>
    <link href="/assets/css/app.css?v=<?= htmlspecialchars($cssVersion) ?>" rel="stylesheet">
    <style>
    :root { --safe-bottom: env(safe-area-inset-bottom, 0px); }
    .mobile-wrap { display:flex; flex-direction:column; min-height:100vh; min-height:100dvh; }
    .mobile-topbar {
        position:sticky; top:0; z-index:1030; background:var(--bg-navbar, #fff);
        display:flex; align-items:center; padding:8px 12px; gap:8px;
        border-bottom:1px solid rgba(148,163,184,0.15); min-height:48px;
    }
    .mobile-topbar .menu-btn { font-size:1.3rem; background:none; border:none; padding:4px 6px; color:var(--text-main, #333); }
    .mobile-topbar .group-title { flex:1; font-size:0.95rem; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .mobile-topbar .user-name { font-size:0.8rem; cursor:pointer; display:flex; align-items:center; gap:2px; white-space:nowrap; }
    .mobile-topbar .user-dropdown { position:absolute; top:100%; right:0; min-width:140px; background:var(--bg-card,#fff); border:1px solid rgba(0,0,0,0.1); border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.12); display:none; z-index:1050; padding:6px 0; }
    .mobile-topbar .user-dropdown.show { display:block; }
    .mobile-topbar .user-dropdown a { display:block; padding:8px 16px; font-size:0.85rem; color:var(--text-main,#333); text-decoration:none; }
    .mobile-topbar .user-dropdown a:active { background:rgba(102,126,234,0.1); }
    .mobile-drawer {
        position:fixed; top:0; left:0; bottom:0; width:260px; max-width:80vw;
        background:var(--bg-sidebar, #f8fafc); z-index:1040;
        transform:translateX(-100%); transition:transform 0.25s ease;
        display:flex; flex-direction:column; overflow-y:auto;
    }
    .mobile-drawer.open { transform:translateX(0); }
    .mobile-drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1035; display:none; }
    .mobile-drawer-overlay.show { display:block; }
    .mobile-drawer-header {
        padding:14px 16px; font-size:0.95rem; font-weight:700;
        border-bottom:1px solid rgba(148,163,184,0.15);
        display:flex; align-items:center; gap:8px;
    }
    .mobile-drawer-item {
        display:flex; align-items:center; gap:10px; padding:12px 16px;
        font-size:0.9rem; color:var(--text-main, #333); text-decoration:none;
        border-left:3px solid transparent; transition:all 0.15s;
    }
    .mobile-drawer-item.active { border-left-color:#667eea; background:rgba(102,126,234,0.08); font-weight:600; }
    .mobile-drawer-item:active { background:rgba(102,126,234,0.06); }
    .mobile-content { flex:1; overflow-y:auto; padding:12px; padding-bottom:calc(60px + var(--safe-bottom)); }
    .mobile-tabbar {
        position:fixed; bottom:0; left:0; right:0; z-index:1030;
        background:var(--bg-navbar, #fff); border-top:1px solid rgba(148,163,184,0.15);
        display:flex; overflow-x:auto; padding-bottom:var(--safe-bottom);
        -webkit-overflow-scrolling:touch;
    }
    .mobile-tabbar::-webkit-scrollbar { display:none; }
    .mobile-tabbar .tab-item {
        flex-shrink:0; padding:8px 14px; font-size:0.78rem; color:var(--text-muted, #888);
        text-decoration:none; text-align:center; border-bottom:2px solid transparent;
        white-space:nowrap; transition:all 0.15s;
    }
    .mobile-tabbar .tab-item.active { color:#667eea; border-bottom-color:#667eea; font-weight:600; }
    .mobile-drawer-version { margin-top:auto; padding:10px 16px; font-size:0.7rem; color:var(--text-muted,#999); border-top:1px solid rgba(148,163,184,0.1); }
    body.theme-dark .mobile-drawer { background:#0f172a; }
    body.theme-dark .mobile-topbar { background:#0f172a; border-color:rgba(148,163,184,0.1); }
    body.theme-dark .mobile-tabbar { background:#0f172a; border-color:rgba(148,163,184,0.1); }
    body.theme-dark .mobile-drawer-item { color:#e2e8f0; }
    body.theme-dark .mobile-drawer-item.active { background:rgba(102,126,234,0.15); }
    body.theme-dark .mobile-topbar .user-dropdown { background:#1e293b; border-color:rgba(148,163,184,0.2); }
    body.theme-dark .mobile-topbar .user-dropdown a { color:#e2e8f0; }
    body.theme-dark .mobile-topbar .user-dropdown a:active { background:rgba(102,126,234,0.15); }
    /* 全局弹窗样式 */
    .ent-trade-modal { position:fixed; inset:0; z-index:9999; display:none; }
    .ent-trade-modal.show { display:flex; }
    .ent-trade-modal .backdrop { position:absolute; inset:0; background:rgba(0,0,0,0.45); }
    .ent-trade-modal .sheet { position:absolute; bottom:0; left:0; right:0; background:var(--bg-card,#fff); border-radius:16px 16px 0 0; padding:16px; padding-bottom:calc(16px + var(--safe-bottom)); max-height:90vh; overflow-y:auto; }
    body.theme-dark .ent-trade-modal .sheet { background:#1a1a2e; }
    .ent-trade-modal .sheet h6 { margin-bottom:8px; font-size:0.9rem; }
    .ent-trade-modal .sheet .qty-row { display:flex; gap:6px; margin:8px 0; }
    .ent-trade-modal .sheet .qty-row input, .ent-trade-modal .sheet .qty-row textarea, .ent-trade-modal .sheet .qty-row select { flex:1; font-size:0.9rem; padding:8px; border-radius:10px; border:1px solid rgba(148,163,184,0.2); text-align:center; }
    .ent-trade-modal .sheet .btn-row { display:flex; gap:8px; margin-top:10px; }
    .ent-trade-modal .sheet .btn-row button { flex:1; padding:10px; border-radius:10px; font-size:0.9rem; font-weight:600; border:none; cursor:pointer; }
    .ent-trade-modal .sheet .info-line { font-size:0.68rem; color:var(--text-muted,#888); text-align:center; margin-top:4px; }
    .ent-trade-modal .sheet .qck-btns { display:flex; gap:4px; flex-wrap:wrap; }
    .ent-trade-modal .sheet .qck-btns button { flex:1; padding:6px 4px; border-radius:8px; border:1px solid rgba(148,163,184,0.15); background:transparent; font-size:0.7rem; cursor:pointer; }
    </style>
</head>
<body class="<?= $themeClass ?>">
<div class="mobile-wrap">
    <!-- 遮罩 -->
    <div class="mobile-drawer-overlay" id="drawerOverlay"></div>

    <!-- 抽屉菜单 -->
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="mobile-drawer-header">
            <?= htmlspecialchars(isset($systemSetting['site_name']) ? $systemSetting['site_name'] : 'SanS三石记账') ?>
        </div>
        <?php foreach ($menuGroups as $g): ?>
        <?php if (!empty($g['hide'])) continue; ?>
        <a href="/public/index.php?route=<?= $g['route'] ?>" class="mobile-drawer-item<?= $g['id'] === $activeGroupId ? ' active' : '' ?>" data-group="<?= $g['id'] ?>">
            <span style="font-size:1.1rem"><?= $g['icon'] ?></span> <?= $g['name'] ?>
        </a>
        <?php endforeach; ?>
        <div class="mobile-drawer-version">v<?= htmlspecialchars($appVersion) ?> © SanS三石记账</div>
    </div>

    <!-- 顶栏 -->
    <div class="mobile-topbar">
        <button class="menu-btn" id="menuBtn" aria-label="菜单">☰</button>
        <span class="group-title"><?= htmlspecialchars($activeGroup['name']) ?></span>
        <button class="btn btn-sm btn-outline-secondary me-1" id="themeBtn" style="font-size:0.9rem;padding:2px 8px;border-radius:8px;">
            <?= $themeMode === 'dark' ? '🌙' : '☀' ?>
        </button>
        <div style="position:relative;">
            <span class="user-name" id="userMenuBtn">
                <?php if ($sessionAvatar): ?><img src="<?= htmlspecialchars($sessionAvatar) ?>" style="width:26px;height:26px;border-radius:50%;margin-right:4px;"><?php endif; ?>
                <?= htmlspecialchars($sessionNickname ?: '用户') ?> ▾
            </span>
            <div class="user-dropdown" id="userDropdown">
                <a href="/public/index.php?route=settings">⚙️ 系统设置</a>
                <a href="/public/index.php?route=feedback">💬 问题反馈</a>
                <a href="/public/index.php?route=changelog">📋 更新日志</a>
                <a href="/public/index.php?route=logout">🚪 退出登录</a>
            </div>
        </div>
    </div>

    <!-- 内容区 -->
    <div class="mobile-content">
        <?php $mobileView = __DIR__ . '/' . $view . '.php'; ?>
        <?php if (file_exists($mobileView)): ?>
            <?php include $mobileView; ?>
        <?php else: ?>
            <?php include __DIR__ . '/../../templates/' . $view . '.php'; ?>
        <?php endif; ?>
    </div>

    <!-- 底部Tab栏 -->
    <?php $noTabRoutes = ['settings', 'feedback', 'changelog', 'security', 'backup', 'scheduler', 'license-activate', 'license-admin-panel', 'forgot-password', 'reset-password', 'resume-preview']; ?>
    <?php if (!empty($activeGroup['tabs']) && !in_array($currentRoute, $noTabRoutes)): ?>
    <div class="mobile-tabbar" id="mobileTabbar">
        <?php foreach ($activeGroup['tabs'] as $tab): ?>
        <a href="/public/index.php?route=<?= $tab['route'] ?>" class="tab-item<?= $tab['route'] === $currentRoute ? ' active' : '' ?>"><?= htmlspecialchars($tab['name']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php $bootstrapJsLocal = __DIR__ . '/../../assets/vendor/bootstrap/bootstrap.bundle.min.js'; ?>
<?php if (is_file($bootstrapJsLocal)): ?>
<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<script>
// 抽屉菜单
var drawer = document.getElementById('mobileDrawer');
var overlay = document.getElementById('drawerOverlay');
var menuBtn = document.getElementById('menuBtn');

function openDrawer() { drawer.classList.add('open'); overlay.classList.add('show'); }
function closeDrawer() { drawer.classList.remove('open'); overlay.classList.remove('show'); }
menuBtn.addEventListener('click', function(e) { e.stopPropagation(); openDrawer(); });
overlay.addEventListener('click', closeDrawer);

// 点击抽屉内菜单项后关闭
drawer.querySelectorAll('.mobile-drawer-item').forEach(function(item) {
    item.addEventListener('click', function() { setTimeout(closeDrawer, 100); });
});

// 用户下拉菜单
var userMenuBtn = document.getElementById('userMenuBtn');
var userDropdown = document.getElementById('userDropdown');
userMenuBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    userDropdown.classList.toggle('show');
});
document.addEventListener('click', function() {
    userDropdown.classList.remove('show');
});

// 日夜切换
var themeBtn = document.getElementById('themeBtn');
themeBtn.addEventListener('click', function() {
    var isDark = document.body.classList.contains('theme-dark');
    var nextMode = isDark ? 'light' : 'dark';
    document.body.classList.remove('theme-light', 'theme-dark');
    document.body.classList.add(nextMode === 'dark' ? 'theme-dark' : 'theme-light');
    themeBtn.textContent = nextMode === 'dark' ? '🌙' : '☀';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/public/index.php?route=theme-toggle', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('mode=' + nextMode);
});

// 底部Tab点击效果
document.querySelectorAll('.mobile-tabbar .tab-item').forEach(function(tab) {
    tab.addEventListener('click', function(e) {
        document.querySelectorAll('.mobile-tabbar .tab-item').forEach(function(t) { t.classList.remove('active'); });
        tab.classList.add('active');
    });
});
</script>
</body>
</html>
