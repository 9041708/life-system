<?php
require __DIR__ . '/../../src/bootstrap.php';

use App\Service\Config;
use App\Model\SystemSetting;

$systemSetting = SystemSetting::get();
$appName = Config::get('app.name', '三石记账');
$appVersion = Config::get('app.version', 'v1.0.0');
$siteIconSvg = $systemSetting['site_icon_svg'] ?? null;
$cssPath = __DIR__ . '/app.css';
$jsPath = __DIR__ . '/app.js';
$cssVersion = is_file($cssPath) ? (string)filemtime($cssPath) : $appVersion;
$jsVersion = is_file($jsPath) ? (string)filemtime($jsPath) : $appVersion;
?><!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#113a2f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="三石记账">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?> 手机版</title>
    <?php if (!empty($siteIconSvg)): ?>
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode($siteIconSvg) ?>">
    <?php endif; ?>
    <link rel="manifest" href="./manifest.webmanifest">
    <link rel="apple-touch-icon" href="./icon.svg">
    <link rel="stylesheet" href="./app.css?v=<?php echo htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<div class="mobile-shell">
    <header class="topbar">
        <div>
            <div class="brand-kicker">Mobile Web</div>
            <div class="brand-title"><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="topbar-actions">
            <button type="button" id="installAppBtn" class="desktop-link" hidden>添加到桌面</button>
            <a class="desktop-link" href="/public/index.php?view=desktop">桌面版</a>
        </div>
    </header>

    <main class="main-stage">
        <section id="loginView" class="login-view card-shell">
            <div class="hero-chip">手机网页版</div>
            <h1>直接在手机浏览器记账</h1>
            <p>针对手机触屏交互重新整理的网页端界面。</p>
            <form id="loginForm" class="stack-form">
                <label>
                    <span>账号</span>
                    <input id="loginAccount" name="account" type="text" placeholder="用户名或邮箱" autocomplete="username">
                </label>
                <label>
                    <span>密码</span>
                    <input id="loginPassword" name="password" type="password" placeholder="请输入密码" autocomplete="current-password">
                </label>
                <button type="submit" class="primary-btn">登录手机端</button>
            </form>
            <div class="inline-actions align-left section-gap-top">
                <button type="button" class="secondary-btn" id="openRegisterBtn">创建账号</button>
            </div>
            <div class="login-footnote">如需继续使用电脑端界面，可点击右上角“桌面版”。</div>
        </section>

        <section id="registerView" class="login-view card-shell" hidden>
            <div class="hero-chip">注册账号</div>
            <h1>创建手机端账号</h1>
            <p>注册成功后可直接进入手机网页版使用。</p>
            <form id="registerForm" class="stack-form">
                <label>
                    <span>用户名</span>
                    <input id="registerUsername" type="text" placeholder="请输入用户名" autocomplete="username">
                </label>
                <label>
                    <span>昵称</span>
                    <input id="registerNickname" type="text" placeholder="请输入昵称">
                </label>
                <label>
                    <span>邮箱</span>
                    <input id="registerEmail" type="email" placeholder="请输入邮箱" autocomplete="email">
                </label>
                <label>
                    <span>密码</span>
                    <input id="registerPassword" type="password" placeholder="至少 6 位" autocomplete="new-password">
                </label>
                <label>
                    <span>确认密码</span>
                    <input id="registerPasswordConfirm" type="password" placeholder="再次输入密码" autocomplete="new-password">
                </label>
                <button type="submit" class="primary-btn">注册并进入手机端</button>
            </form>
            <div class="inline-actions align-left section-gap-top">
                <button type="button" class="secondary-btn" id="backToLoginBtn">返回登录</button>
            </div>
        </section>

        <div id="appShell" class="app-shell" hidden>
            <section class="view-section" data-route="home">
                <div class="section-head compact">
                    <div>
                        <div class="section-title">总览</div>
                        <div id="headerLedgerName" class="section-meta">个人账本</div>
                    </div>
                    <div id="headerUserBadge" class="user-badge"></div>
                </div>

                <div id="homeHero" class="hero-card"></div>
                <div id="homeMetrics" class="home-panels"></div>

                <div class="card-shell">
                    <div class="section-head">
                        <div class="section-title">最近流水</div>
                        <button type="button" class="ghost-btn" data-nav="transactions">查看全部</button>
                    </div>
                    <div id="recentTransactions" class="list-stack"></div>
                </div>
            </section>

            <section class="view-section" data-route="plans" hidden>
                <div class="card-shell">
                    <div class="section-head">
                        <div>
                            <div class="section-title">目标预算</div>
                            <div id="plansMonthLabel" class="section-meta"></div>
                        </div>
                        <div class="inline-actions">
                            <button type="button" class="ghost-btn" id="prevBudgetMonthBtn">上月</button>
                            <button type="button" class="ghost-btn" id="nextBudgetMonthBtn">下月</button>
                        </div>
                    </div>
                    <div id="plansSummary" class="metric-grid compact-grid"></div>
                    <div class="inline-actions align-left section-gap-top">
                        <button type="button" class="secondary-btn" id="addGoalBtn">新建目标</button>
                        <button type="button" class="secondary-btn" id="addBudgetBtn">新增预算</button>
                        <button type="button" class="ghost-btn" id="openReportsFromPlansBtn">查看报表</button>
                    </div>
                </div>

                <div class="card-shell">
                    <div class="section-head">
                        <div class="section-title">我的目标</div>
                    </div>
                    <div id="goalsList" class="list-stack"></div>
                </div>

                <div class="card-shell">
                    <div class="section-head">
                        <div class="section-title">月度预算</div>
                    </div>
                    <div id="budgetList" class="list-stack"></div>
                </div>
            </section>

            <section class="view-section" data-route="transactions" hidden>
                <div class="card-shell">
                    <div class="section-head">
                        <div class="section-title">流水明细</div>
                        <button type="button" class="ghost-btn" id="refreshTransactionsBtn">刷新</button>
                    </div>
                    <form id="transactionFilterForm" class="filter-grid">
                        <label>
                            <span>类型</span>
                            <select id="filterType">
                                <option value="all">全部</option>
                                <option value="expense">支出</option>
                                <option value="income">收入</option>
                                <option value="transfer">转账</option>
                            </select>
                        </label>
                        <label>
                            <span>账户</span>
                            <select id="filterAccount"></select>
                        </label>
                        <label>
                            <span>分类</span>
                            <select id="filterCategory"></select>
                        </label>
                        <label>
                            <span>开始日期</span>
                            <input id="filterDateFrom" type="date">
                        </label>
                        <label>
                            <span>结束日期</span>
                            <input id="filterDateTo" type="date">
                        </label>
                        <label class="full-row">
                            <span>关键词</span>
                            <input id="filterKeyword" type="text" placeholder="备注关键词">
                        </label>
                        <div class="inline-actions full-row">
                            <button type="submit" class="primary-btn">应用筛选</button>
                            <button type="button" class="secondary-btn" id="resetTransactionFilterBtn">重置</button>
                        </div>
                    </form>
                </div>

                <div id="transactionSummary" class="metric-grid compact-grid"></div>
                <div id="transactionList" class="list-stack"></div>
            </section>

            <section class="view-section" data-route="manage" hidden>
                <div class="card-shell">
                    <div class="section-head">
                        <div>
                            <div class="section-title">管理中心</div>
                            <div class="section-meta">账户、分类、项目、资产、订阅</div>
                        </div>
                        <button type="button" class="secondary-btn" id="addManageItemBtn">新增</button>
                    </div>
                    <div id="manageTabs" class="segment-bar">
                        <button type="button" class="segment-btn active" data-manage-tab="accounts">账户</button>
                        <button type="button" class="segment-btn" data-manage-tab="categories">分类</button>
                        <button type="button" class="segment-btn" data-manage-tab="items">项目</button>
                        <button type="button" class="segment-btn" data-manage-tab="assets">资产</button>
                        <button type="button" class="segment-btn" data-manage-tab="subscriptions">订阅</button>
                        <button type="button" class="segment-btn" data-manage-tab="icons">图标</button>
                    </div>
                    <div id="manageControls" class="section-gap-top"></div>
                    <div id="manageSummary" class="metric-grid compact-grid section-gap-top"></div>
                </div>
                <input id="iconUploadInput" type="file" accept="image/*" hidden>
                <div id="manageContent" class="list-stack"></div>
            </section>

            <section class="view-section" data-route="add" hidden>
                <div class="card-shell">
                    <div class="section-head">
                        <div>
                            <div class="section-title">记一笔</div>
                            <div id="transactionFormState" class="section-meta">新建记录</div>
                        </div>
                        <button type="button" class="ghost-btn" id="resetTransactionFormBtn">清空</button>
                    </div>
                    <form id="transactionForm" class="stack-form">
                        <label>
                            <span>类型</span>
                            <select id="formType" name="type">
                                <option value="expense">支出</option>
                                <option value="income">收入</option>
                                <option value="transfer">转账</option>
                            </select>
                        </label>
                        <label>
                            <span>分类</span>
                            <select id="formCategory" name="category_id"></select>
                        </label>
                        <label>
                            <span>项目</span>
                            <select id="formItem" name="item_id"></select>
                        </label>
                        <label id="fromAccountField">
                            <span>支出账户</span>
                            <select id="formFromAccount" name="from_account_id"></select>
                        </label>
                        <label id="toAccountField">
                            <span>收入账户</span>
                            <select id="formToAccount" name="to_account_id"></select>
                        </label>
                        <label>
                            <span>金额</span>
                            <input id="formAmount" name="amount" type="number" step="0.01" min="0" placeholder="0.00">
                        </label>
                        <label>
                            <span>时间</span>
                            <input id="formTransTime" name="trans_time" type="datetime-local">
                        </label>
                        <label>
                            <span>备注</span>
                            <textarea id="formRemark" name="remark" rows="3" placeholder="可选"></textarea>
                        </label>
                        <div class="stack-form slim-gap">
                            <span>附件</span>
                            <div class="inline-actions align-left">
                                <button type="button" class="secondary-btn" id="pickTransactionAttachmentsBtn">上传图片</button>
                                <button type="button" class="ghost-btn" id="clearTransactionAttachmentsBtn">清空附件</button>
                            </div>
                            <input id="transactionAttachmentsInput" type="file" accept="image/*" multiple hidden>
                            <div id="transactionAttachmentList" class="feedback-gallery"></div>
                        </div>
                        <button type="submit" class="primary-btn">保存记录</button>
                    </form>
                </div>
            </section>

            <section class="view-section" data-route="reports" hidden>
                <div class="card-shell">
                    <div class="section-head">
                        <div class="section-title">报表分析</div>
                        <button type="button" class="ghost-btn" id="refreshReportBtn">刷新</button>
                    </div>
                    <form id="reportForm" class="filter-grid">
                        <label>
                            <span>周期</span>
                            <select id="reportMode">
                                <option value="month">本月</option>
                                <option value="year">全年</option>
                                <option value="custom">自定义</option>
                            </select>
                        </label>
                        <label>
                            <span>年份</span>
                            <input id="reportYear" type="number" min="2020" max="2100">
                        </label>
                        <label>
                            <span>月份</span>
                            <input id="reportMonth" type="number" min="1" max="12">
                        </label>
                        <label>
                            <span>开始</span>
                            <input id="reportDateFrom" type="date">
                        </label>
                        <label>
                            <span>结束</span>
                            <input id="reportDateTo" type="date">
                        </label>
                        <div class="inline-actions full-row">
                            <button type="submit" class="primary-btn">更新报表</button>
                        </div>
                    </form>
                </div>
                <div id="reportSummary" class="metric-grid"></div>
                <div class="card-shell">
                    <div class="section-head">
                        <div class="section-title">趋势</div>
                        <div id="reportPeriodTitle" class="section-meta"></div>
                    </div>
                    <div id="reportTrend" class="bars-panel"></div>
                </div>
                <div class="card-shell">
                    <div class="section-head">
                        <div class="section-title">分类统计</div>
                        <select id="reportCategoryType" class="mini-select">
                            <option value="expense">支出</option>
                            <option value="income">收入</option>
                        </select>
                    </div>
                    <div id="reportCategoryStats" class="list-stack"></div>
                </div>
            </section>

            <section class="view-section" data-route="settings" hidden>
                <div class="card-shell profile-shell">
                    <div class="section-head compact">
                        <div>
                            <div class="section-title">我的</div>
                            <div class="section-meta">手机端资料与偏好设置</div>
                        </div>
                        <label class="avatar-upload">
                            <input id="avatarInput" type="file" accept="image/*" hidden>
                            <span>更换头像</span>
                        </label>
                    </div>
                    <div class="profile-row">
                        <div id="settingsAvatar" class="avatar-circle"></div>
                        <div>
                            <div id="settingsNickname" class="profile-name"></div>
                            <div id="settingsMeta" class="section-meta"></div>
                        </div>
                    </div>
                    <div id="settingsStats" class="settings-stats-grid"></div>
                </div>

                <div class="card-shell">
                    <div class="section-title">资料修改</div>
                    <div class="profile-edit-grid section-gap-top">
                        <div class="profile-edit-card">
                            <span class="profile-edit-label">用户名</span>
                            <strong id="settingsUsernameValue" class="profile-edit-value"></strong>
                            <button type="button" class="secondary-btn profile-edit-btn" id="editUsernameBtn">修改</button>
                        </div>
                        <div class="profile-edit-card">
                            <span class="profile-edit-label">昵称</span>
                            <strong id="settingsNicknameValue" class="profile-edit-value"></strong>
                            <button type="button" class="secondary-btn profile-edit-btn" id="editNicknameBtn">修改</button>
                        </div>
                    </div>
                    <div class="profile-edit-card section-gap-top">
                        <span class="profile-edit-label">邮箱</span>
                        <strong id="settingsEmailValue" class="profile-edit-value"></strong>
                        <button type="button" class="secondary-btn profile-edit-btn" id="editEmailBtn">修改</button>
                    </div>
                    <div class="stack-form slim-gap section-gap-top">
                        <div class="profile-password-row">
                            <div>
                                <div class="profile-edit-label">登录密码</div>
                                <div class="section-meta">通过弹窗修改，修改后会自动退出重新登录</div>
                            </div>
                            <button type="button" class="secondary-btn" id="openPasswordSheetBtn">修改密码</button>
                        </div>
                    </div>
                </div>

                <div class="card-shell">
                    <div class="section-title">账本与开关</div>
                    <div class="stack-form slim-gap">
                        <label>
                            <span>当前账本</span>
                            <select id="ledgerSelect"></select>
                        </label>
                        <div class="toggle-grid">
                            <label class="toggle-card">
                                <span class="toggle-copy">
                                    <strong>预算提醒</strong>
                                    <small>接近预算时提醒</small>
                                </span>
                                <span class="toggle-switch">
                                    <input id="budgetReminderToggle" type="checkbox">
                                    <span class="toggle-slider"></span>
                                </span>
                            </label>
                            <label class="toggle-card">
                                <span class="toggle-copy">
                                    <strong>账户转账</strong>
                                    <small>启用转账记录</small>
                                </span>
                                <span class="toggle-switch">
                                    <input id="transferToggle" type="checkbox">
                                    <span class="toggle-slider"></span>
                                </span>
                            </label>
                            <label class="toggle-card">
                                <span class="toggle-copy">
                                    <strong>允许负余额</strong>
                                    <small>支出可低于余额</small>
                                </span>
                                <span class="toggle-switch">
                                    <input id="negativeBalanceToggle" type="checkbox">
                                    <span class="toggle-slider"></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card-shell">
                    <div class="section-title">服务与支持</div>
                    <div class="inline-actions align-left">
                        <button type="button" class="secondary-btn" id="openFeedbackBtn">意见反馈</button>
                        <button type="button" class="secondary-btn" id="openChangelogBtn">更新日志</button>
                    </div>
                </div>

                <div class="card-shell">
                    <div class="section-title">其他</div>
                    <div class="inline-actions align-left">
                        <button type="button" class="secondary-btn" id="settingsInstallBtn" hidden>添加到桌面</button>
                        <a class="secondary-btn link-btn" href="/public/index.php?view=desktop">进入桌面版</a>
                        <button type="button" class="danger-btn" id="logoutBtn">退出登录</button>
                    </div>
                </div>
            </section>

            <section class="view-section" data-route="feedback" hidden>
                <div class="card-shell">
                    <div class="section-head">
                        <div>
                            <div class="section-title">意见反馈</div>
                            <div class="section-meta">问题、建议、体验反馈都可以在这里提交</div>
                        </div>
                        <button type="button" class="ghost-btn" id="backFromFeedbackBtn">返回</button>
                    </div>
                    <form id="feedbackForm" class="stack-form">
                        <label>
                            <span>反馈类型</span>
                            <select id="feedbackCategory">
                                <option value="suggest">建议</option>
                                <option value="bug">问题</option>
                                <option value="other">其他</option>
                            </select>
                        </label>
                        <label>
                            <span>反馈内容</span>
                            <textarea id="feedbackContent" rows="4" placeholder="请尽量描述清楚你的问题或想法"></textarea>
                        </label>
                        <div class="stack-form">
                            <span>问题截图</span>
                            <div class="inline-actions align-left">
                                <button type="button" class="secondary-btn" id="pickFeedbackImagesBtn">选择图片</button>
                                <button type="button" class="ghost-btn" id="clearFeedbackImagesBtn" hidden>清空图片</button>
                            </div>
                            <input id="feedbackImagesInput" type="file" accept="image/*" multiple hidden>
                            <div id="feedbackImageList" class="list-stack compact-list"></div>
                        </div>
                        <button type="submit" class="primary-btn">提交反馈</button>
                    </form>
                </div>
                <div class="card-shell">
                    <div class="section-head">
                        <div class="section-title">近期反馈</div>
                        <button type="button" class="ghost-btn" id="refreshFeedbackBtn">刷新</button>
                    </div>
                    <div id="feedbackList" class="list-stack"></div>
                </div>
            </section>

            <section class="view-section" data-route="changelog" hidden>
                <div class="card-shell">
                    <div class="section-head">
                        <div>
                            <div class="section-title">更新日志</div>
                            <div id="changelogVersionMeta" class="section-meta"></div>
                        </div>
                        <button type="button" class="ghost-btn" id="backFromChangelogBtn">返回</button>
                    </div>
                    <div class="inline-actions align-left section-gap-top">
                        <select id="changelogFilter" class="mini-select">
                            <option value="all">全部版本</option>
                        </select>
                    </div>
                    <div id="changelogList" class="list-stack"></div>
                </div>
            </section>
        </div>
    </main>

    <nav id="tabbar" class="tabbar" hidden>
        <button type="button" class="tabbar-btn active" data-route="home">总览</button>
        <button type="button" class="tabbar-btn" data-route="plans">目标预算</button>
        <button type="button" class="tabbar-btn emphasize" data-route="add">记一笔</button>
        <button type="button" class="tabbar-btn" data-route="manage">管理</button>
        <button type="button" class="tabbar-btn" data-route="settings">我的</button>
    </nav>
</div>

<div id="homeAssetSheet" class="editor-sheet" hidden>
    <div class="editor-sheet-backdrop" id="closeHomeAssetSheetBackdrop"></div>
    <div class="editor-sheet-panel confirm-sheet-panel home-asset-sheet-panel">
        <div class="section-head compact">
            <div>
                <div id="homeAssetSheetTitle" class="section-title">资产明细</div>
                <div id="homeAssetSheetMeta" class="section-meta"></div>
            </div>
            <button type="button" class="ghost-btn" id="closeHomeAssetSheetBtn">关闭</button>
        </div>
        <div id="homeAssetSheetSummary" class="home-asset-sheet-summary"></div>
        <div id="homeAssetSheetList" class="list-stack compact-list"></div>
    </div>
</div>
<div id="profileFieldSheet" class="editor-sheet" hidden>
    <div class="editor-sheet-backdrop" id="closeProfileFieldSheetBackdrop"></div>
    <div class="editor-sheet-panel confirm-sheet-panel profile-field-sheet-panel">
        <div class="section-head compact profile-field-sheet-head">
            <div>
                <div id="profileFieldSheetTitle" class="section-title">修改资料</div>
                <div id="profileFieldSheetMeta" class="section-meta"></div>
            </div>
            <button type="button" class="ghost-btn" id="closeProfileFieldSheetBtn">关闭</button>
        </div>
        <form id="profileFieldSheetForm" class="stack-form slim-gap">
            <label id="profileFieldPrimaryWrap">
                <span id="profileFieldPrimaryLabel">内容</span>
                <input id="profileFieldPrimaryInput" type="text">
            </label>
            <label id="profileFieldSecondaryWrap" hidden>
                <span id="profileFieldSecondaryLabel">确认</span>
                <input id="profileFieldSecondaryInput" type="password">
            </label>
            <div class="inline-actions align-left section-gap-top">
                <button type="submit" class="primary-btn" id="submitProfileFieldSheetBtn">保存</button>
            </div>
        </form>
    </div>
</div>
<div id="toast" class="toast" hidden></div>
<div id="loadingMask" class="loading-mask" hidden>
    <div class="loading-card">正在同步数据…</div>
</div>
<div id="mediaViewer" class="media-viewer" hidden>
    <button type="button" class="media-viewer-close" id="closeMediaViewerBtn">关闭</button>
    <img id="mediaViewerImage" class="media-viewer-image" alt="图片预览">
</div>
<div id="editorSheet" class="editor-sheet" hidden>
    <div class="editor-sheet-backdrop" id="closeEditorSheetBackdrop"></div>
    <div class="editor-sheet-panel">
        <div class="section-head compact">
            <div>
                <div id="editorSheetTitle" class="section-title">编辑</div>
                <div id="editorSheetMeta" class="section-meta"></div>
            </div>
            <button type="button" class="ghost-btn" id="closeEditorSheetBtn">关闭</button>
        </div>
        <form id="editorSheetForm" class="stack-form">
            <label id="editorGroupField">
                <span>账户分组</span>
                <select id="editorGroupId"></select>
            </label>
            <label id="editorGoalAccountField">
                <span>关联账户</span>
                <select id="editorGoalAccountId"></select>
            </label>
            <label id="editorCategoryTypeField">
                <span>分类类型</span>
                <select id="editorCategoryType">
                    <option value="expense">支出</option>
                    <option value="income">收入</option>
                    <option value="transfer">转账</option>
                </select>
            </label>
            <label id="editorGoalStatusField">
                <span>目标状态</span>
                <select id="editorGoalStatus">
                    <option value="active">进行中</option>
                    <option value="done">已完成</option>
                    <option value="archived">已归档</option>
                </select>
            </label>
            <label id="editorBudgetTypeField">
                <span>预算类型</span>
                <select id="editorBudgetType">
                    <option value="expense">支出</option>
                    <option value="income">收入</option>
                </select>
            </label>
            <label id="editorSubscriptionTypeField">
                <span>记录类型</span>
                <select id="editorSubscriptionType">
                    <option value="subscription">订阅</option>
                    <option value="lifetime">买断</option>
                </select>
            </label>
            <label id="editorItemCategoryField">
                <span>所属分类</span>
                <select id="editorItemCategoryId"></select>
            </label>
            <label id="editorBudgetCategoryField">
                <span>预算分类</span>
                <select id="editorBudgetCategoryId"></select>
            </label>
            <label id="editorBudgetItemField">
                <span>预算项目</span>
                <select id="editorBudgetItemId"></select>
            </label>
            <label id="editorNameField">
                <span id="editorNameLabel">名称</span>
                <input id="editorNameInput" type="text" placeholder="请输入名称">
            </label>
            <label id="editorAccountNoField">
                <span>账号尾号或备注</span>
                <input id="editorAccountNoInput" type="text" placeholder="可留空">
            </label>
            <label id="editorInitialBalanceField">
                <span>初始余额</span>
                <input id="editorInitialBalanceInput" type="number" step="0.01" placeholder="0.00">
            </label>
            <label id="editorSortOrderField">
                <span>排序值</span>
                <input id="editorSortOrderInput" type="number" step="1" placeholder="0">
            </label>
            <label id="editorTargetAmountField">
                <span>目标金额</span>
                <input id="editorTargetAmountInput" type="number" step="0.01" min="0" placeholder="0.00">
            </label>
            <label id="editorSavedAmountField">
                <span>已存金额</span>
                <input id="editorSavedAmountInput" type="number" step="0.01" min="0" placeholder="0.00">
            </label>
            <label id="editorBudgetAmountField">
                <span>预算金额</span>
                <input id="editorBudgetAmountInput" type="number" step="0.01" min="0" placeholder="0.00">
            </label>
            <label id="editorAcquiredDateField">
                <span>到手日期</span>
                <input id="editorAcquiredDateInput" type="date">
            </label>
            <label id="editorDeadlineField">
                <span>截止日期</span>
                <input id="editorDeadlineInput" type="date">
            </label>
            <label id="editorTransferDateField">
                <span>转手日期</span>
                <input id="editorTransferDateInput" type="date">
            </label>
            <label id="editorExpireDateField">
                <span>到期日期</span>
                <input id="editorExpireDateInput" type="date">
            </label>
            <label id="editorValueAmountField">
                <span>资产价值</span>
                <input id="editorValueAmountInput" type="number" step="0.01" min="0" placeholder="0.00">
            </label>
            <label id="editorTransferPriceField">
                <span>转手价格</span>
                <input id="editorTransferPriceInput" type="number" step="0.01" min="0" placeholder="0.00">
            </label>
            <label id="editorSubscriptionPriceField">
                <span>价格</span>
                <input id="editorSubscriptionPriceInput" type="number" step="0.01" min="0" placeholder="0.00">
            </label>
            <label id="editorSubscriptionAutoRenewField" class="switch-row">
                <span>自动续费</span>
                <input id="editorSubscriptionAutoRenewInput" type="checkbox">
            </label>
            <label id="editorPeriodField">
                <span>周期</span>
                <input id="editorPeriodInput" type="text" placeholder="例如 month / year">
            </label>
            <label id="editorRemarkField">
                <span>备注</span>
                <textarea id="editorRemarkInput" rows="3" placeholder="可留空"></textarea>
            </label>
            <div class="inline-actions align-left">
                <button type="submit" class="primary-btn" id="submitEditorSheetBtn">保存</button>
            </div>
        </form>
    </div>
</div>
<div id="confirmSheet" class="editor-sheet" hidden>
    <div class="editor-sheet-backdrop" id="closeConfirmSheetBackdrop"></div>
    <div class="editor-sheet-panel confirm-sheet-panel">
        <div class="section-head compact">
            <div>
                <div id="confirmSheetTitle" class="section-title">确认操作</div>
                <div id="confirmSheetMeta" class="section-meta"></div>
            </div>
            <button type="button" class="ghost-btn" id="closeConfirmSheetBtn">关闭</button>
        </div>
        <div id="confirmSheetMessage" class="section-meta"></div>
        <div class="inline-actions align-left section-gap-top">
            <button type="button" class="secondary-btn" id="cancelConfirmSheetBtn">取消</button>
            <button type="button" class="danger-btn" id="submitConfirmSheetBtn">确认删除</button>
        </div>
    </div>
</div>

<script>
window.__SSJ_MOBILE__ = {
    appName: <?php echo json_encode($appName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    version: <?php echo json_encode($appVersion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    apiBase: "/public/api.php",
    desktopUrl: "/public/index.php?view=desktop",
    swUrl: "./sw.js",
    manifestUrl: "./manifest.webmanifest"
};
</script>
<script src="./app.js?v=<?php echo htmlspecialchars($jsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>