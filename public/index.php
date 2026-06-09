<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', '/volume1/web/ssjizhang.cn_ceshi/runtime/php_error.log');


// Front controller
require __DIR__ . '/../src/bootstrap.php';

// 根据站点 URL 生成唯一 Session 名称，确保不同安装之间隔离
$sessionName = \App\Service\Config::get('app.session_name', null);
if (!$sessionName) {
    $siteUrl = \App\Service\Config::get('app.site_url', __DIR__);
    $sessionName = 'SANSESS_' . substr(md5($siteUrl), 0, 10);
}

// Always start session for all routes
if (session_status() === PHP_SESSION_NONE) {
    session_name($sessionName);
    session_start();
}

// Default route
$route = isset($_GET['route']) ? trim((string)$_GET['route'], '/') : 'dashboard';
// 把路径中的斜杠用连字符替换，便于直接映射到对应的控制器方法（如 security/addBlacklist -> security-addBlacklist）
$route = $route === '' ? 'dashboard' : str_replace('/', '-', $route);

// 页面标题映射
$pageTitles = [
    'dashboard' => '首页',
    'transaction-create' => '记账',
    'transactions' => '明细',
    'reports' => '统计报表',
    'categories' => '分类管理',
    'items' => '项目管理',
    'accounts' => '账户管理',
    'icons' => '图标库',
    'system-icons' => '系统图标库',
    'assets' => '资产管理',
    'subscriptions' => '订阅记录',
    'budget' => '预算管理',
    'goals' => '目标管理',
    'debt-current' => '当月应还',
    'debts' => '当月应还',
    'debt-summary' => '汇总统计',
    'debt-config' => '负债配置',
    'reimbursement' => '报销情况',
    'reimbursements' => '报销情况',
    'reimbursement-statistics' => '报销统计',
    'reimbursement-config' => '报销配置',
    'settings' => '系统设置',
    'feedback' => '问题反馈',
    'changelog' => '更新日志',
    'license-admin' => '系统日志',
    'security' => '安全监控',
    'backup' => '数据备份',
    'scheduler' => '定时任务',
    'naming' => '取名助手',
    'login' => '登录',
    'register' => '注册',
    'register-bind' => '绑定小程序',
    'qr-login' => '小程序登录',
    'forgot-password' => '忘记密码',
    'reset-password' => '重置密码',
    'landing' => '首页',
    // EasyTodo
    'easytodo-tasks' => '待办管理',
    'easytodo-countdowns' => '倒计时',
    'easytodo-pomodoro' => '番茄钟',
    'easytodo-memos' => '备忘录',
    'easytodo-statistics' => '统计看板',
    'easytodo-reports' => 'AI日报/周报',
    // Finance
    'finance' => '理财管理',
    // Books
    'books' => '在线阅览',
    'books-reader' => '在线阅读',
    'books-config' => '图书配置',
    // Resume
    'resume-preview' => '简历预览',
    'resume-builder' => '简历配置',
    // 工具箱
    'toolbox-password-vault' => '密码箱',
    'toolbox-cny' => '人民币大写转换器',
    'toolbox-shelf-life' => '保质期计算器',
    'toolbox-qrcode' => '二维码生成器',
    'toolbox-morse' => '摩斯电码编码解码',
    'toolbox-calendar' => '万年历',
    'toolbox-forum-assistant' => '论坛助手',
    // 导航
    'nav-my' => '我的导航',
    'nav-config' => '导航配置',
    'nav-detail' => '导航详情',
    // 正念
    'mindfulness-checkin' => '正念签到',
    'mindfulness-treasure' => '正念树洞',
    'mindfulness-config' => '正念配置',
];

if (isset($pageTitles[$route])) {
    $_SESSION['current_page_title'] = $pageTitles[$route];
} elseif (strpos($route, 'transaction-') === 0) {
    $_SESSION['current_page_title'] = '明细';
} elseif (strpos($route, 'debt-') === 0) {
    $_SESSION['current_page_title'] = '负债管理';
} elseif (strpos($route, 'reimbursement-') === 0) {
    $_SESSION['current_page_title'] = '报销管理';
} elseif (strpos($route, 'security-') === 0) {
    $_SESSION['current_page_title'] = '安全监控';
} elseif (strpos($route, 'backup-') === 0) {
    $_SESSION['current_page_title'] = '数据备份';
} else {
    $_SESSION['current_page_title'] = '首页';
}

// 已登录用户访问根路径时，自动跳转到仪表盘
if (!empty($_SESSION['user_id']) && ($route === 'landing' || $route === '')) {
    header('Location: ?route=dashboard');
    exit;
}

// 未登录用户，跳转到登录页
// 这些页面在未登录状态下也必须允许访问
if (empty($_SESSION['user_id']) && !in_array($route, [
    'login',
    'register',
    'register-bind',
    'forgot-password',
    'reset-password',
    'qr-login',
    'qr-login-complete',
    'landing',
    'deploy-auth',
    'license-request-submit',
    'license-message-submit',
    'license-query',
    'source-download',
    'wechat_bind_callback',
    'wechat_self_bind_callback',
])) {
    header('Location: ?route=login');
    exit;
}

// Route dispatch
$controller = null;
switch ($route) {
    // Auth
    case 'login':
        $controller = new \App\Controller\AuthController();
        $controller->login();
        break;
    case 'register':
        $controller = new \App\Controller\AuthController();
        $controller->register();
        break;
    case 'register-bind':
        $controller = new \App\Controller\AuthController();
        $controller->registerBind();
        break;
    case 'qr-login':
        $controller = new \App\Controller\AuthController();
        $controller->qrLogin();
        break;
    case 'qr-login-complete':
        $controller = new \App\Controller\AuthController();
        $controller->qrLoginComplete();
        break;
    case 'forgot-password':
        $controller = new \App\Controller\AuthController();
        $controller->forgotPassword();
        break;
    case 'reset-password':
        $controller = new \App\Controller\AuthController();
        $controller->resetPassword();
        break;
    case 'onboarding':
        $controller = new \App\Controller\AuthController();
        $controller->onboarding();
        break;
    case 'logout':
        session_destroy();
        header('Location: ?route=login');
        exit;
        break;

    // Dashboard
    case 'dashboard':
        $controller = new \App\Controller\DashboardController();
        $controller->index();
        break;

    // Transactions (menu uses 'transactions' and 'transaction-create')
    case 'transactions':
        $controller = new \App\Controller\TransactionController();
        $controller->index();
        break;
    case 'transaction-create':
        $controller = new \App\Controller\TransactionController();
        $controller->create();
        break;
    case 'transaction-edit':
        $controller = new \App\Controller\TransactionController();
        $controller->edit();
        break;
    case 'transaction-delete':
        $controller = new \App\Controller\TransactionController();
        $controller->delete();
        break;

    // Reports
    case 'reports':
        $controller = new \App\Controller\ReportController();
        $controller->index();
        break;

    // Categories
    case 'categories':
        $controller = new \App\Controller\CategoryController();
        $controller->index();
        break;

    // Items
    case 'items':
        $controller = new \App\Controller\ItemController();
        $controller->index();
        break;

    // Accounts
    case 'accounts':
        $controller = new \App\Controller\AccountController();
        $controller->index();
        break;

    // Icons (menu uses 'icons', controller is IconController)
    case 'icons':
    case 'icon_library':
        $controller = new \App\Controller\IconController();
        $controller->index();
        break;

    // System icons
    case 'system-icons':
        $controller = new \App\Controller\SystemIconController();
        $controller->index();
        break;

    // Assets
    case 'assets':
        $controller = new \App\Controller\AssetController();
        $controller->index();
        break;

    // Subscriptions
    case 'subscriptions':
        $controller = new \App\Controller\SubscriptionController();
        $controller->index();
        break;

    // Budget (menu uses 'budget', controller is BudgetController)
    case 'budget':
    case 'budgets':
        $controller = new \App\Controller\BudgetController();
        $controller->index();
        break;

    // Goals
    case 'goals':
        $controller = new \App\Controller\GoalController();
        $controller->index();
        break;

    // Debt (menu uses 'debt-current', 'debt-summary', 'debt-config')
    case 'debt-current':
    case 'debts':
        $controller = new \App\Controller\DebtController();
        $controller->currentMonth();
        break;
    case 'debt-summary':
        $controller = new \App\Controller\DebtController();
        $controller->summary();
        break;
    case 'debt-config':
        $controller = new \App\Controller\DebtController();
        $controller->configIndex();
        break;
    case 'debt-config-create':
        $controller = new \App\Controller\DebtController();
        $controller->configCreate();
        break;
    case 'debt-config-cancel':
        $controller = new \App\Controller\DebtController();
        $controller->configCancel();
        break;
    case 'debt-mark-paid':
        $controller = new \App\Controller\DebtController();
        $controller->markPaid();
        break;
    case 'debt-undo-paid':
        $controller = new \App\Controller\DebtController();
        $controller->undoPaid();
        break;

    // Reimbursement (menu uses 'reimbursement', 'reimbursement-statistics', 'reimbursement-config')
    case 'reimbursement':
    case 'reimbursements':
        $controller = new \App\Controller\ReimbursementController();
        $controller->pending();
        break;
    case 'reimbursement-statistics':
        $controller = new \App\Controller\ReimbursementController();
        $controller->statistics();
        break;
    case 'reimbursement-config':
        $controller = new \App\Controller\ReimbursementController();
        $controller->config();
        break;

    case 'reimbursement-pending':
        $controller = new \App\Controller\ReimbursementController();
        $controller->pending();
        break;

    // Feedback
    case 'feedback':
        $controller = new \App\Controller\FeedbackController();
        $controller->index();
        break;

    // Settings
    case 'settings':
        $controller = new \App\Controller\SettingsController();
        $controller->index();
        break;

    // Changelog
    case 'changelog':
        $controller = new \App\Controller\ChangelogController();
        $controller->index();
        break;

    // License admin (menu uses 'license-admin')
    case 'license-admin':
    case 'admin_licenses':
        $controller = new \App\Controller\LicenseAdminController();
        $controller->index();
        break;

    // Security
    case 'security':
        $controller = new \App\Controller\SecurityController();
        $controller->index();
        break;
    case 'security-unlock':
        $controller = new \App\Controller\SecurityController();
        $controller->unlock();
        break;
    case 'security-addBlacklist':
        $controller = new \App\Controller\SecurityController();
        $controller->addBlacklist();
        break;
    case 'security-removeBlacklist':
        $controller = new \App\Controller\SecurityController();
        $controller->removeBlacklist();
        break;
    case 'security-batchRemoveBlacklist':
        $controller = new \App\Controller\SecurityController();
        $controller->batchRemoveBlacklist();
        break;
    case 'security-addWhitelist':
        $controller = new \App\Controller\SecurityController();
        $controller->addWhitelist();
        break;
    case 'security-removeWhitelist':
        $controller = new \App\Controller\SecurityController();
        $controller->removeWhitelist();
        break;
    case 'security-batchRemoveWhitelist':
        $controller = new \App\Controller\SecurityController();
        $controller->batchRemoveWhitelist();
        break;
    case 'security-save-login-policy':
        $controller = new \App\Controller\SecurityController();
        $controller->saveLoginPolicy();
        break;
    case 'security-save-geo-policy':
        $controller = new \App\Controller\SecurityController();
        $controller->saveGeoPolicy();
        break;
    case 'security-clear-logs':
        $controller = new \App\Controller\SecurityController();
        $controller->clearLogs();
        break;
    case 'security-update-config':
        $controller = new \App\Controller\SecurityController();
        $controller->updateConfig();
        break;

    case 'finance':
        $controller = new \App\Controller\FinanceController();
        $controller->index();
        break;
    case 'finance-api':
        $controller = new \App\Controller\FinanceController();
        $controller->api();
        break;

    // Books
    case 'books':
        $controller = new \App\Controller\BookController();
        $controller->index();
        break;
    case 'books-config':
        $controller = new \App\Controller\BookController();
        $controller->config();
        break;
    case 'books-reader':
        $controller = new \App\Controller\BookController();
        $controller->reader();
        break;
    case 'books-api':
        $controller = new \App\Controller\BookController();
        $controller->api();
        break;
    case 'books-api-update':
        $controller = new \App\Controller\BookController();
        $controller->apiUpdate();
        break;
    case 'books-serve':
        $controller = new \App\Controller\BookController();
        $controller->serve();
        break;

    // Resume
    case 'resume-preview':
        $controller = new \App\Controller\ResumeController();
        $controller->preview();
        break;
    case 'resume-builder':
        $controller = new \App\Controller\ResumeController();
        $controller->builder();
        break;
    case 'resume-api':
        $controller = new \App\Controller\ResumeController();
        $controller->api();
        break;

    // Backup
    case 'backup':
        $controller = new \App\Controller\BackupController();
        $controller->index();
        break;
    case 'backup-perform':
        $controller = new \App\Controller\BackupController();
        $controller->perform();
        break;
    case 'backup-update-config':
        $controller = new \App\Controller\BackupController();
        $controller->updateConfig();
        break;
    case 'backup-restore':
        $controller = new \App\Controller\BackupController();
        $controller->restore();
        break;

    // Scheduler (定时任务管理)
    case 'scheduler':
        $controller = new \App\Controller\SchedulerController();
        $controller->index();
        break;
    case 'scheduler-api':
        $controller = new \App\Controller\SchedulerController();
        $controller->api();
        break;

    // Ledger
    case 'ledger':
        $controller = new \App\Controller\LedgerController();
        $controller->index();
        break;
    case 'ledger-switch':
        $controller = new \App\Controller\LedgerController();
        $controller->switch();
        break;

    // EasyTodo
    case 'easytodo-tasks':
    case 'easytodo-task-create':
        $controller = new \App\Controller\EasyTodoController();
        $controller->tasks();
        break;
    case 'easytodo-api-tasks':
        $controller = new \App\Controller\EasyTodoController();
        $controller->apiTasks();
        exit;
    case 'easytodo-countdowns':
        $controller = new \App\Controller\EasyTodoController();
        $controller->countdowns();
        break;
    case 'easytodo-api-countdowns':
        $controller = new \App\Controller\EasyTodoController();
        $controller->apiCountdowns();
        exit;
    case 'easytodo-pomodoro':
        $controller = new \App\Controller\EasyTodoController();
        $controller->pomodoro();
        break;
    case 'easytodo-api-pomodoro':
        $controller = new \App\Controller\EasyTodoController();
        $controller->apiPomodoro();
        exit;
    case 'easytodo-memos':
        $controller = new \App\Controller\EasyTodoController();
        $controller->memos();
        break;
    case 'easytodo-api-memos':
        $controller = new \App\Controller\EasyTodoController();
        $controller->apiMemos();
        exit;
    case 'easytodo-statistics':
        $controller = new \App\Controller\EasyTodoController();
        $controller->statistics();
        break;
    case 'easytodo-reports':
        $controller = new \App\Controller\EasyTodoController();
        $controller->reports();
        break;
    case 'easytodo-api-reports':
        $controller = new \App\Controller\EasyTodoController();
        $controller->apiReports();
        exit;

    // Theme toggle (POST)
    case 'theme-toggle':
        $controller = new \App\Controller\SettingsController();
        $controller->toggleTheme();
        break;

    // Announcement mark read (POST)
    case 'announcement-mark-read':
        $controller = new \App\Controller\DashboardController();
        $controller->markAnnouncementRead();
        break;

    case 'landing':
        $controller = new \App\Controller\LandingController();
        $controller->index();
        break;
    case 'deploy-auth':
        $controller = new \App\Controller\LandingController();
        $controller->deployAuth();
        break;
    case 'license-request-submit':
        $controller = new \App\Controller\LandingController();
        $controller->submitLicenseRequest();
        break;
    case 'license-message-submit':
        $controller = new \App\Controller\LandingController();
        $controller->submitLicenseMessage();
        break;
    case 'license-query':
        $controller = new \App\Controller\LandingController();
        $controller->queryLicense();
        break;
    case 'source-download':
        $controller = new \App\Controller\LandingController();
        $controller->downloadSource();
        break;

    // 工具箱
    case 'toolbox-password-vault':
        $controller = new \App\Controller\ToolboxController();
        $controller->passwordVault();
        break;
    case 'toolbox-password-vault-api':
        $controller = new \App\Controller\ToolboxController();
        $controller->passwordVaultApi();
        break;
    case 'toolbox-cny':
        $controller = new \App\Controller\ToolboxController();
        $controller->cnyConverter();
        break;
    case 'toolbox-shelf-life':
        $controller = new \App\Controller\ToolboxController();
        $controller->shelfLife();
        break;
    case 'toolbox-qrcode':
        $controller = new \App\Controller\ToolboxController();
        $controller->qrcode();
        break;
    case 'toolbox-morse':
        $controller = new \App\Controller\ToolboxController();
        $controller->morse();
        break;

    case 'toolbox-calendar':
        $controller = new \App\Controller\ToolboxController();
        $controller->calendar();
        break;

    case 'toolbox-forum-assistant':
        $controller = new \App\Controller\ToolboxController();
        $controller->forumAssistant();
        break;
    case 'toolbox-forum-assistant-api':
        $controller = new \App\Controller\ToolboxController();
        $controller->forumAssistantApi();
        break;

    case 'toolbox-today-do':
        $controller = new \App\Controller\ToolboxController();
        $controller->todayDo();
        break;
    case 'toolbox-today-do-api':
        $controller = new \App\Controller\ToolboxController();
        $controller->todayDoApi();
        break;

    // 取名助手
    case 'naming':
        $controller = new \App\Controller\NamingController();
        $controller->index();
        break;
    case 'naming-api':
        $controller = new \App\Controller\NamingController();
        $controller->api();
        break;

    // 导航
    case 'nav-my':
        $controller = new \App\Controller\NavController();
        $controller->my();
        break;
    case 'nav-detail':
        $controller = new \App\Controller\NavController();
        $controller->detail();
        break;
    case 'nav-config':
        $controller = new \App\Controller\NavController();
        $controller->config();
        break;
    case 'nav-api':
        $controller = new \App\Controller\NavController();
        $controller->api();
        break;

    // 正念
    case 'mindfulness-checkin':
        $controller = new \App\Controller\MindfulnessController();
        $controller->checkin();
        break;
    case 'mindfulness-treasure':
        $controller = new \App\Controller\MindfulnessController();
        $controller->treasure();
        break;
    case 'mindfulness-config':
        $controller = new \App\Controller\MindfulnessController();
        $controller->config();
        break;
    case 'mindfulness-api':
        $controller = new \App\Controller\MindfulnessController();
        $controller->api();
        break;

    default:
        // 未命中的 route：尝试通用动态分发（支持 base-action 或 base-action-sub 的格式）
        $normalized = $route;
        // 期望形如 base-action 或 base-action-more
        if (strpos($normalized, '-') !== false) {
            $parts = explode('-', $normalized, 2);
            $base = $parts[0];
            $actionPart = $parts[1];
            $controllerClass = '\\App\\Controller\\' . str_replace(' ', '', ucwords(str_replace('-', ' ', $base))) . 'Controller';
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                // 将 actionPart 的连字符风格转换为 camelCase
                $method = preg_replace_callback('/-([a-z])/', function($m){ return strtoupper($m[1]); }, $actionPart);
                if (method_exists($controller, $method)) {
                    $controller->{$method}();
                    exit;
                }
                // 如果 actionPart 原本已经是驼峰，直接尝试
                if (method_exists($controller, $actionPart)) {
                    $controller->{$actionPart}();
                    exit;
                }
            }
        }

        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
        echo '<p>Route: ' . htmlspecialchars($route) . '</p>';
        echo '<a href="?route=dashboard">Go to Dashboard</a>';
}



