(function () {
  var config = window.__SSJ_MOBILE__ || {};
  var storageKeyToken = "ssj_mobile_token";
  var storageKeyUser = "ssj_mobile_user";
  var storageKeyManageTab = "ssj_mobile_manage_tab";

  var state = {
    token: "",
    user: null,
    installPromptEvent: null,
    standalone: false,
    ledgers: [],
    activeLedgerId: 0,
    activeLedgerName: "个人账本",
    accountGroups: [],
    accounts: [],
    categories: [],
    items: [],
    overview: null,
    goals: [],
    budget: null,
    budgetYear: new Date().getFullYear(),
    budgetMonth: new Date().getMonth() + 1,
    transactions: [],
    transactionSummary: { income: 0, expense: 0 },
    editingTransactionId: 0,
    manageTab: "accounts",
    manageFilters: {
      accounts: "all",
      categories: "all",
      items: "all",
      assets: "all",
      subscriptions: "all",
      icons: "all"
    },
    assets: { active: [], transferred: [], summary: { total_value: 0, total_daily_cost: 0, asset_count: 0 } },
    subscriptions: [],
    icons: [],
    reportSummary: null,
    reportCategoryType: "expense",
    reportCategoryStats: [],
    stats: null,
    feedbacks: [],
    changelog: { appVersion: "", pcVersion: "", entries: [] },
    iconUploadContext: null,
    feedbackImages: [],
    feedbackImagePreviewUrls: [],
    transactionAttachments: [],
    changelogExpandedIndex: 0,
    editorSheet: { entity: "", id: 0, mode: "create" },
    confirmSheet: null,
    profileFieldSheet: { field: "", passwordFlow: false },
  };

  var els = {};

  document.addEventListener("DOMContentLoaded", init);

  function init() {
    cacheElements();
    bindEvents();
    setupShellCapabilities();
    restoreSession();
    renderFeedbackImageSelection();
    renderTransactionAttachments();
    if (els.formTransTime && !els.formTransTime.value) {
      els.formTransTime.value = nowDateTimeLocal();
    }
    ensureHash();
    onHashChange();

    if (state.token) {
      bootstrapApp();
    }
  }

  function cacheElements() {
    var now = new Date();
    Object.assign(els, {
      loginView: document.getElementById("loginView"),
      registerView: document.getElementById("registerView"),
      appShell: document.getElementById("appShell"),
      tabbar: document.getElementById("tabbar"),
      toast: document.getElementById("toast"),
      loadingMask: document.getElementById("loadingMask"),
      loginForm: document.getElementById("loginForm"),
      loginAccount: document.getElementById("loginAccount"),
      loginPassword: document.getElementById("loginPassword"),
      openRegisterBtn: document.getElementById("openRegisterBtn"),
      backToLoginBtn: document.getElementById("backToLoginBtn"),
      registerForm: document.getElementById("registerForm"),
      registerUsername: document.getElementById("registerUsername"),
      registerNickname: document.getElementById("registerNickname"),
      registerEmail: document.getElementById("registerEmail"),
      registerPassword: document.getElementById("registerPassword"),
      registerPasswordConfirm: document.getElementById("registerPasswordConfirm"),
      views: Array.prototype.slice.call(document.querySelectorAll(".view-section")),
      tabButtons: Array.prototype.slice.call(document.querySelectorAll(".tabbar-btn")),
      navButtons: Array.prototype.slice.call(document.querySelectorAll("[data-nav]")),
      headerLedgerName: document.getElementById("headerLedgerName"),
      headerUserBadge: document.getElementById("headerUserBadge"),
      homeHero: document.getElementById("homeHero"),
      homeMetrics: document.getElementById("homeMetrics"),
      recentTransactions: document.getElementById("recentTransactions"),
      homeAssetSheet: document.getElementById("homeAssetSheet"),
      closeHomeAssetSheetBackdrop: document.getElementById("closeHomeAssetSheetBackdrop"),
      closeHomeAssetSheetBtn: document.getElementById("closeHomeAssetSheetBtn"),
      homeAssetSheetTitle: document.getElementById("homeAssetSheetTitle"),
      homeAssetSheetMeta: document.getElementById("homeAssetSheetMeta"),
      homeAssetSheetSummary: document.getElementById("homeAssetSheetSummary"),
      homeAssetSheetList: document.getElementById("homeAssetSheetList"),
      profileFieldSheet: document.getElementById("profileFieldSheet"),
      closeProfileFieldSheetBackdrop: document.getElementById("closeProfileFieldSheetBackdrop"),
      closeProfileFieldSheetBtn: document.getElementById("closeProfileFieldSheetBtn"),
      profileFieldSheetTitle: document.getElementById("profileFieldSheetTitle"),
      profileFieldSheetMeta: document.getElementById("profileFieldSheetMeta"),
      profileFieldSheetForm: document.getElementById("profileFieldSheetForm"),
      profileFieldPrimaryWrap: document.getElementById("profileFieldPrimaryWrap"),
      profileFieldPrimaryLabel: document.getElementById("profileFieldPrimaryLabel"),
      profileFieldPrimaryInput: document.getElementById("profileFieldPrimaryInput"),
      profileFieldSecondaryWrap: document.getElementById("profileFieldSecondaryWrap"),
      profileFieldSecondaryLabel: document.getElementById("profileFieldSecondaryLabel"),
      profileFieldSecondaryInput: document.getElementById("profileFieldSecondaryInput"),
      submitProfileFieldSheetBtn: document.getElementById("submitProfileFieldSheetBtn"),
      plansMonthLabel: document.getElementById("plansMonthLabel"),
      plansSummary: document.getElementById("plansSummary"),
      goalsList: document.getElementById("goalsList"),
      budgetList: document.getElementById("budgetList"),
      prevBudgetMonthBtn: document.getElementById("prevBudgetMonthBtn"),
      nextBudgetMonthBtn: document.getElementById("nextBudgetMonthBtn"),
      addGoalBtn: document.getElementById("addGoalBtn"),
      addBudgetBtn: document.getElementById("addBudgetBtn"),
      openReportsFromPlansBtn: document.getElementById("openReportsFromPlansBtn"),
      transactionFilterForm: document.getElementById("transactionFilterForm"),
      filterType: document.getElementById("filterType"),
      filterAccount: document.getElementById("filterAccount"),
      filterCategory: document.getElementById("filterCategory"),
      filterDateFrom: document.getElementById("filterDateFrom"),
      filterDateTo: document.getElementById("filterDateTo"),
      filterKeyword: document.getElementById("filterKeyword"),
      refreshTransactionsBtn: document.getElementById("refreshTransactionsBtn"),
      resetTransactionFilterBtn: document.getElementById("resetTransactionFilterBtn"),
      transactionSummary: document.getElementById("transactionSummary"),
      transactionList: document.getElementById("transactionList"),
      manageTabs: document.getElementById("manageTabs"),
      manageControls: document.getElementById("manageControls"),
      manageSummary: document.getElementById("manageSummary"),
      manageContent: document.getElementById("manageContent"),
      addManageItemBtn: document.getElementById("addManageItemBtn"),
      iconUploadInput: document.getElementById("iconUploadInput"),
      transactionForm: document.getElementById("transactionForm"),
      transactionFormState: document.getElementById("transactionFormState"),
      formType: document.getElementById("formType"),
      formCategory: document.getElementById("formCategory"),
      formItem: document.getElementById("formItem"),
      formFromAccount: document.getElementById("formFromAccount"),
      formToAccount: document.getElementById("formToAccount"),
      formAmount: document.getElementById("formAmount"),
      formTransTime: document.getElementById("formTransTime"),
      formRemark: document.getElementById("formRemark"),
      pickTransactionAttachmentsBtn: document.getElementById("pickTransactionAttachmentsBtn"),
      clearTransactionAttachmentsBtn: document.getElementById("clearTransactionAttachmentsBtn"),
      transactionAttachmentsInput: document.getElementById("transactionAttachmentsInput"),
      transactionAttachmentList: document.getElementById("transactionAttachmentList"),
      fromAccountField: document.getElementById("fromAccountField"),
      toAccountField: document.getElementById("toAccountField"),
      resetTransactionFormBtn: document.getElementById("resetTransactionFormBtn"),
      reportForm: document.getElementById("reportForm"),
      reportMode: document.getElementById("reportMode"),
      reportYear: document.getElementById("reportYear"),
      reportMonth: document.getElementById("reportMonth"),
      reportDateFrom: document.getElementById("reportDateFrom"),
      reportDateTo: document.getElementById("reportDateTo"),
      refreshReportBtn: document.getElementById("refreshReportBtn"),
      reportSummary: document.getElementById("reportSummary"),
      reportPeriodTitle: document.getElementById("reportPeriodTitle"),
      reportTrend: document.getElementById("reportTrend"),
      reportCategoryType: document.getElementById("reportCategoryType"),
      reportCategoryStats: document.getElementById("reportCategoryStats"),
      settingsAvatar: document.getElementById("settingsAvatar"),
      settingsNickname: document.getElementById("settingsNickname"),
      settingsMeta: document.getElementById("settingsMeta"),
      settingsStats: document.getElementById("settingsStats"),
      settingsUsernameValue: document.getElementById("settingsUsernameValue"),
      settingsNicknameValue: document.getElementById("settingsNicknameValue"),
      settingsEmailValue: document.getElementById("settingsEmailValue"),
      editUsernameBtn: document.getElementById("editUsernameBtn"),
      editNicknameBtn: document.getElementById("editNicknameBtn"),
      editEmailBtn: document.getElementById("editEmailBtn"),
      openPasswordSheetBtn: document.getElementById("openPasswordSheetBtn"),
      installAppBtn: document.getElementById("installAppBtn"),
      settingsInstallBtn: document.getElementById("settingsInstallBtn"),
      openFeedbackBtn: document.getElementById("openFeedbackBtn"),
      openChangelogBtn: document.getElementById("openChangelogBtn"),
      backFromFeedbackBtn: document.getElementById("backFromFeedbackBtn"),
      backFromChangelogBtn: document.getElementById("backFromChangelogBtn"),
      feedbackForm: document.getElementById("feedbackForm"),
      feedbackCategory: document.getElementById("feedbackCategory"),
      feedbackContent: document.getElementById("feedbackContent"),
      pickFeedbackImagesBtn: document.getElementById("pickFeedbackImagesBtn"),
      clearFeedbackImagesBtn: document.getElementById("clearFeedbackImagesBtn"),
      feedbackImagesInput: document.getElementById("feedbackImagesInput"),
      feedbackImageList: document.getElementById("feedbackImageList"),
      refreshFeedbackBtn: document.getElementById("refreshFeedbackBtn"),
      feedbackList: document.getElementById("feedbackList"),
      changelogVersionMeta: document.getElementById("changelogVersionMeta"),
      changelogFilter: document.getElementById("changelogFilter"),
      changelogList: document.getElementById("changelogList"),
      ledgerSelect: document.getElementById("ledgerSelect"),
      budgetReminderToggle: document.getElementById("budgetReminderToggle"),
      transferToggle: document.getElementById("transferToggle"),
      negativeBalanceToggle: document.getElementById("negativeBalanceToggle"),
      avatarInput: document.getElementById("avatarInput"),
      logoutBtn: document.getElementById("logoutBtn"),
      mediaViewer: document.getElementById("mediaViewer"),
      closeMediaViewerBtn: document.getElementById("closeMediaViewerBtn"),
      mediaViewerImage: document.getElementById("mediaViewerImage"),
      editorSheet: document.getElementById("editorSheet"),
      closeEditorSheetBackdrop: document.getElementById("closeEditorSheetBackdrop"),
      closeEditorSheetBtn: document.getElementById("closeEditorSheetBtn"),
      editorSheetTitle: document.getElementById("editorSheetTitle"),
      editorSheetMeta: document.getElementById("editorSheetMeta"),
      editorSheetForm: document.getElementById("editorSheetForm"),
      editorGroupField: document.getElementById("editorGroupField"),
      editorGroupId: document.getElementById("editorGroupId"),
      editorGoalAccountField: document.getElementById("editorGoalAccountField"),
      editorGoalAccountId: document.getElementById("editorGoalAccountId"),
      editorCategoryTypeField: document.getElementById("editorCategoryTypeField"),
      editorCategoryType: document.getElementById("editorCategoryType"),
      editorGoalStatusField: document.getElementById("editorGoalStatusField"),
      editorGoalStatus: document.getElementById("editorGoalStatus"),
      editorBudgetTypeField: document.getElementById("editorBudgetTypeField"),
      editorBudgetType: document.getElementById("editorBudgetType"),
      editorSubscriptionTypeField: document.getElementById("editorSubscriptionTypeField"),
      editorSubscriptionType: document.getElementById("editorSubscriptionType"),
      editorItemCategoryField: document.getElementById("editorItemCategoryField"),
      editorItemCategoryId: document.getElementById("editorItemCategoryId"),
      editorBudgetCategoryField: document.getElementById("editorBudgetCategoryField"),
      editorBudgetCategoryId: document.getElementById("editorBudgetCategoryId"),
      editorBudgetItemField: document.getElementById("editorBudgetItemField"),
      editorBudgetItemId: document.getElementById("editorBudgetItemId"),
      editorNameField: document.getElementById("editorNameField"),
      editorNameLabel: document.getElementById("editorNameLabel"),
      editorNameInput: document.getElementById("editorNameInput"),
      editorAccountNoField: document.getElementById("editorAccountNoField"),
      editorAccountNoInput: document.getElementById("editorAccountNoInput"),
      editorInitialBalanceField: document.getElementById("editorInitialBalanceField"),
      editorInitialBalanceInput: document.getElementById("editorInitialBalanceInput"),
      editorSortOrderField: document.getElementById("editorSortOrderField"),
      editorSortOrderInput: document.getElementById("editorSortOrderInput"),
      editorTargetAmountField: document.getElementById("editorTargetAmountField"),
      editorTargetAmountInput: document.getElementById("editorTargetAmountInput"),
      editorSavedAmountField: document.getElementById("editorSavedAmountField"),
      editorSavedAmountInput: document.getElementById("editorSavedAmountInput"),
      editorBudgetAmountField: document.getElementById("editorBudgetAmountField"),
      editorBudgetAmountInput: document.getElementById("editorBudgetAmountInput"),
      editorAcquiredDateField: document.getElementById("editorAcquiredDateField"),
      editorAcquiredDateInput: document.getElementById("editorAcquiredDateInput"),
      editorDeadlineField: document.getElementById("editorDeadlineField"),
      editorDeadlineInput: document.getElementById("editorDeadlineInput"),
      editorTransferDateField: document.getElementById("editorTransferDateField"),
      editorTransferDateInput: document.getElementById("editorTransferDateInput"),
      editorExpireDateField: document.getElementById("editorExpireDateField"),
      editorExpireDateInput: document.getElementById("editorExpireDateInput"),
      editorValueAmountField: document.getElementById("editorValueAmountField"),
      editorValueAmountInput: document.getElementById("editorValueAmountInput"),
      editorTransferPriceField: document.getElementById("editorTransferPriceField"),
      editorTransferPriceInput: document.getElementById("editorTransferPriceInput"),
      editorSubscriptionPriceField: document.getElementById("editorSubscriptionPriceField"),
      editorSubscriptionPriceInput: document.getElementById("editorSubscriptionPriceInput"),
      editorSubscriptionAutoRenewField: document.getElementById("editorSubscriptionAutoRenewField"),
      editorSubscriptionAutoRenewInput: document.getElementById("editorSubscriptionAutoRenewInput"),
      editorPeriodField: document.getElementById("editorPeriodField"),
      editorPeriodInput: document.getElementById("editorPeriodInput"),
      editorRemarkField: document.getElementById("editorRemarkField"),
      editorRemarkInput: document.getElementById("editorRemarkInput"),
      submitEditorSheetBtn: document.getElementById("submitEditorSheetBtn"),
      confirmSheet: document.getElementById("confirmSheet"),
      closeConfirmSheetBackdrop: document.getElementById("closeConfirmSheetBackdrop"),
      closeConfirmSheetBtn: document.getElementById("closeConfirmSheetBtn"),
      cancelConfirmSheetBtn: document.getElementById("cancelConfirmSheetBtn"),
      submitConfirmSheetBtn: document.getElementById("submitConfirmSheetBtn"),
      confirmSheetTitle: document.getElementById("confirmSheetTitle"),
      confirmSheetMeta: document.getElementById("confirmSheetMeta"),
      confirmSheetMessage: document.getElementById("confirmSheetMessage"),
    });

    if (els.reportYear) {
      els.reportYear.value = String(now.getFullYear());
    }
    if (els.reportMonth) {
      els.reportMonth.value = String(now.getMonth() + 1);
    }
  }

  function bindEvents() {
    window.addEventListener("hashchange", onHashChange);

    els.loginForm.addEventListener("submit", onLoginSubmit);
    els.registerForm.addEventListener("submit", onRegisterSubmit);
    els.openRegisterBtn.addEventListener("click", function () {
      navigate("register");
    });
    els.backToLoginBtn.addEventListener("click", function () {
      navigate("login");
    });

    els.tabButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        navigate(button.getAttribute("data-route") || "home");
      });
    });

    els.navButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        navigate(button.getAttribute("data-nav") || "home");
      });
    });

    els.homeHero.addEventListener("click", onHomeAssetClick);

    els.prevBudgetMonthBtn.addEventListener("click", function () {
      shiftBudgetMonth(-1);
    });
    els.nextBudgetMonthBtn.addEventListener("click", function () {
      shiftBudgetMonth(1);
    });
    els.addGoalBtn.addEventListener("click", function () {
      openGoalEditor(null);
    });
    els.addBudgetBtn.addEventListener("click", function () {
      openBudgetEditor(null);
    });
    els.openReportsFromPlansBtn.addEventListener("click", function () {
      navigate("reports");
    });
    els.goalsList.addEventListener("click", onGoalListClick);
    els.budgetList.addEventListener("click", onBudgetListClick);

    els.refreshTransactionsBtn.addEventListener("click", function () {
      loadTransactions();
    });
    els.resetTransactionFilterBtn.addEventListener("click", resetTransactionFilters);
    els.transactionFilterForm.addEventListener("submit", function (event) {
      event.preventDefault();
      loadTransactions();
    });
    els.transactionList.addEventListener("click", onTransactionListClick);

    els.manageTabs.addEventListener("click", onManageTabClick);
    els.addManageItemBtn.addEventListener("click", onAddManageItem);
    els.manageControls.addEventListener("click", onManageControlsClick);
    els.manageContent.addEventListener("click", onManageContentClick);
    els.iconUploadInput.addEventListener("change", onIconUploadChange);

    els.formType.addEventListener("change", refreshTransactionFormOptions);
    els.formCategory.addEventListener("change", refreshTransactionFormOptions);
    els.transactionForm.addEventListener("submit", onTransactionSubmit);
    els.resetTransactionFormBtn.addEventListener("click", function () {
      resetTransactionForm(false);
    });
    els.pickTransactionAttachmentsBtn.addEventListener("click", function () {
      els.transactionAttachmentsInput.click();
    });
    els.clearTransactionAttachmentsBtn.addEventListener("click", clearTransactionAttachments);
    els.transactionAttachmentsInput.addEventListener("change", onTransactionAttachmentsChange);
    els.transactionAttachmentList.addEventListener("click", onTransactionAttachmentListClick);

    els.reportForm.addEventListener("submit", function (event) {
      event.preventDefault();
      loadReport();
    });
    els.refreshReportBtn.addEventListener("click", function () {
      loadReport();
    });
    els.reportCategoryType.addEventListener("change", function () {
      state.reportCategoryType = els.reportCategoryType.value;
      loadReport();
    });

    els.editUsernameBtn.addEventListener("click", function () {
      openProfileFieldSheet("username");
    });
    els.editNicknameBtn.addEventListener("click", function () {
      openProfileFieldSheet("nickname");
    });
    els.editEmailBtn.addEventListener("click", function () {
      openProfileFieldSheet("email");
    });
    els.openPasswordSheetBtn.addEventListener("click", function () {
      openProfileFieldSheet("password");
    });
    els.installAppBtn.addEventListener("click", onInstallApp);
    els.settingsInstallBtn.addEventListener("click", onInstallApp);
    els.openFeedbackBtn.addEventListener("click", function () {
      navigate("feedback");
    });
    els.openChangelogBtn.addEventListener("click", function () {
      navigate("changelog");
    });
    els.backFromFeedbackBtn.addEventListener("click", function () {
      navigate("settings");
    });
    els.backFromChangelogBtn.addEventListener("click", function () {
      navigate("settings");
    });
    els.feedbackForm.addEventListener("submit", onFeedbackSubmit);
    els.pickFeedbackImagesBtn.addEventListener("click", function () {
      els.feedbackImagesInput.click();
    });
    els.clearFeedbackImagesBtn.addEventListener("click", clearFeedbackImages);
    els.feedbackImagesInput.addEventListener("change", onFeedbackImagesChange);
    els.refreshFeedbackBtn.addEventListener("click", function () {
      loadFeedbackData();
    });
    els.feedbackList.addEventListener("click", onFeedbackListClick);
    els.changelogList.addEventListener("click", onChangelogListClick);
    els.changelogFilter.addEventListener("change", function () {
      state.changelogExpandedIndex = 0;
      renderChangelog();
    });
    els.ledgerSelect.addEventListener("change", onLedgerChange);
    els.budgetReminderToggle.addEventListener("change", function () {
      updateToggle("settings/update-budget-reminder", els.budgetReminderToggle.checked, "budget_reminder_enabled");
    });
    els.transferToggle.addEventListener("change", function () {
      updateToggle("settings/update-transfer-feature", els.transferToggle.checked, "enable_transfer");
    });
    els.negativeBalanceToggle.addEventListener("change", function () {
      updateToggle("settings/update-allow-negative-balance", els.negativeBalanceToggle.checked, "allow_negative_balance");
    });
    els.avatarInput.addEventListener("change", onAvatarChange);
    els.logoutBtn.addEventListener("click", onLogout);
    els.closeMediaViewerBtn.addEventListener("click", closeMediaViewer);
    els.mediaViewer.addEventListener("click", function (event) {
      if (event.target === els.mediaViewer) {
        closeMediaViewer();
      }
    });
    els.closeEditorSheetBackdrop.addEventListener("click", closeEditorSheet);
    els.closeEditorSheetBtn.addEventListener("click", closeEditorSheet);
    els.editorSheetForm.addEventListener("submit", onEditorSheetSubmit);
    els.closeConfirmSheetBackdrop.addEventListener("click", closeConfirmSheet);
    els.closeConfirmSheetBtn.addEventListener("click", closeConfirmSheet);
    els.cancelConfirmSheetBtn.addEventListener("click", closeConfirmSheet);
    els.submitConfirmSheetBtn.addEventListener("click", onConfirmSheetSubmit);
    els.closeHomeAssetSheetBackdrop.addEventListener("click", closeHomeAssetSheet);
    els.closeHomeAssetSheetBtn.addEventListener("click", closeHomeAssetSheet);
    els.closeProfileFieldSheetBackdrop.addEventListener("click", closeProfileFieldSheet);
    els.closeProfileFieldSheetBtn.addEventListener("click", closeProfileFieldSheet);
    els.profileFieldSheetForm.addEventListener("submit", onProfileFieldSheetSubmit);
    els.editorBudgetType.addEventListener("change", syncBudgetEditorOptions);
    els.editorBudgetCategoryId.addEventListener("change", syncBudgetEditorItemOptions);
    els.editorSubscriptionType.addEventListener("change", syncSubscriptionEditorFields);
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && els.mediaViewer && !els.mediaViewer.hidden) {
        closeMediaViewer();
      } else if (event.key === "Escape" && els.editorSheet && !els.editorSheet.hidden) {
        closeEditorSheet();
      } else if (event.key === "Escape" && els.confirmSheet && !els.confirmSheet.hidden) {
        closeConfirmSheet();
      } else if (event.key === "Escape" && els.homeAssetSheet && !els.homeAssetSheet.hidden) {
        closeHomeAssetSheet();
      } else if (event.key === "Escape" && els.profileFieldSheet && !els.profileFieldSheet.hidden) {
        closeProfileFieldSheet();
      }
    });
  }

  function setupShellCapabilities() {
    state.standalone = isStandaloneMode();
    document.body.classList.toggle("standalone-mode", state.standalone);
    updateInstallButtons();

    if ("serviceWorker" in navigator && config.swUrl) {
      navigator.serviceWorker.register(config.swUrl).catch(function () {
        return null;
      });
    }

    window.addEventListener("beforeinstallprompt", function (event) {
      event.preventDefault();
      state.installPromptEvent = event;
      updateInstallButtons();
    });

    window.addEventListener("appinstalled", function () {
      state.installPromptEvent = null;
      state.standalone = true;
      document.body.classList.add("standalone-mode");
      updateInstallButtons();
      toast("已添加到桌面");
    });

    window.addEventListener("offline", function () {
      toast("当前处于离线状态");
    });

    window.addEventListener("online", function () {
      toast("网络已恢复");
    });
  }

  function isStandaloneMode() {
    return window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
  }

  function updateInstallButtons() {
    var visible = !state.standalone;
    if (els.installAppBtn) {
      els.installAppBtn.hidden = !visible;
    }
    if (els.settingsInstallBtn) {
      els.settingsInstallBtn.hidden = !visible;
    }
  }

  async function onInstallApp() {
    if (state.standalone) {
      return;
    }

    if (state.installPromptEvent) {
      state.installPromptEvent.prompt();
      try {
        await state.installPromptEvent.userChoice;
      } catch (error) {
        return;
      }
      return;
    }

    if (/iphone|ipad|ipod/i.test(window.navigator.userAgent || "")) {
      toast("请使用浏览器分享菜单，选择添加到主屏幕");
      return;
    }

    toast("当前浏览器未直接提供安装弹窗，可通过浏览器菜单添加到桌面");
  }

  function restoreSession() {
    state.token = window.localStorage.getItem(storageKeyToken) || "";
    state.manageTab = window.localStorage.getItem(storageKeyManageTab) || "accounts";
    try {
      state.user = JSON.parse(window.localStorage.getItem(storageKeyUser) || "null");
    } catch (error) {
      state.user = null;
    }
  }

  function persistSession() {
    if (state.token) {
      window.localStorage.setItem(storageKeyToken, state.token);
    } else {
      window.localStorage.removeItem(storageKeyToken);
    }

    if (state.user) {
      window.localStorage.setItem(storageKeyUser, JSON.stringify(state.user));
    } else {
      window.localStorage.removeItem(storageKeyUser);
    }
  }

  function clearSession() {
    releaseFeedbackPreviewUrls();
    state.token = "";
    state.user = null;
    state.ledgers = [];
    state.feedbackImages = [];
    state.manageTab = window.localStorage.getItem(storageKeyManageTab) || "accounts";
    closeAllOverlays();
    persistSession();
  }

  function ensureHash() {
    if (!window.location.hash) {
      navigate(state.token ? "home" : "login", true);
    }
  }

  function currentRoute() {
    var raw = window.location.hash.replace(/^#\/?/, "").trim();
    return raw || (state.token ? "home" : "login");
  }

  function navigate(route, replace) {
    var target = "#/" + route;
    if (replace) {
      window.location.replace(target);
    } else {
      window.location.hash = target;
    }
  }

  function onHashChange() {
    var route = currentRoute();
    var allowed = ["login", "register", "home", "plans", "transactions", "manage", "add", "reports", "settings", "feedback", "changelog"];
    if (allowed.indexOf(route) === -1) {
      route = state.token ? "home" : "login";
    }

    if (!state.token && route !== "login" && route !== "register") {
      navigate("login", true);
      return;
    }
    if (state.token && (route === "login" || route === "register")) {
      navigate("home", true);
      return;
    }

    if (!state.token) {
      closeAllOverlays();
      renderAuthRoute(route === "register" ? "register" : "login");
      return;
    }

    showApp();
    var tabRoute = mapRouteToTab(route);

    els.views.forEach(function (view) {
      view.hidden = view.getAttribute("data-route") !== route;
    });
    els.tabButtons.forEach(function (button) {
      var active = button.getAttribute("data-route") === tabRoute;
      button.classList.toggle("active", active);
    });
  }

  function mapRouteToTab(route) {
    if (route === "feedback" || route === "changelog") {
      return "settings";
    }
    return route;
  }

  function renderAuthRoute(route) {
    closeAllOverlays();
    els.loginView.hidden = route !== "login";
    els.registerView.hidden = route !== "register";
    els.appShell.hidden = true;
    els.tabbar.hidden = true;
  }

  function showLogin() {
    renderAuthRoute("login");
  }

  function showApp() {
    els.loginView.hidden = true;
    els.registerView.hidden = true;
    els.appShell.hidden = false;
    els.tabbar.hidden = false;
  }

  async function onLoginSubmit(event) {
    event.preventDefault();
    var account = els.loginAccount.value.trim();
    var password = els.loginPassword.value;
    if (!account || !password) {
      toast("请输入账号和密码");
      return;
    }

    setLoading(true);
    try {
      var res = await api("auth/login-password", {
        method: "POST",
        requiresAuth: false,
        data: { account: account, password: password }
      });
      state.token = res.token || "";
      state.user = res.user || null;
      persistSession();
      els.loginPassword.value = "";
      toast("登录成功");
      showApp();
      navigate("home", true);
      await bootstrapApp();
    } catch (error) {
      setLoading(false);
      toast(error.message || "登录失败");
    }
  }

  async function onRegisterSubmit(event) {
    event.preventDefault();
    var username = els.registerUsername.value.trim();
    var nickname = els.registerNickname.value.trim();
    var email = els.registerEmail.value.trim();
    var password = els.registerPassword.value;
    var passwordConfirm = els.registerPasswordConfirm.value;

    if (!username || !nickname || !email || !password || !passwordConfirm) {
      toast("请完整填写注册信息");
      return;
    }

    setLoading(true);
    try {
      var res = await api("auth/register", {
        method: "POST",
        requiresAuth: false,
        data: {
          username: username,
          nickname: nickname,
          email: email,
          password: password,
          password_confirm: passwordConfirm
        }
      });
      state.token = res.token || "";
      state.user = res.user || null;
      persistSession();
      els.registerForm.reset();
      toast("注册成功");
      showApp();
      navigate("home", true);
      await bootstrapApp();
    } catch (error) {
      setLoading(false);
      toast(error.message || "注册失败");
    }
  }

  async function bootstrapApp() {
    setLoading(true);
    try {
      var responses = await Promise.all([
        api("auth/profile", { method: "GET" }),
        api("ledgers/list", { method: "GET" }),
      ]);
      state.user = responses[0].user || state.user;
      state.ledgers = responses[1].ledgers || [];
      state.activeLedgerId = Number(responses[1].active_ledger_id || 0);
      state.activeLedgerName = responses[1].active_ledger && responses[1].active_ledger.name
        ? String(responses[1].active_ledger.name)
        : "个人账本";
      persistSession();
      showApp();
      renderHeader();
      await reloadAllData();
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      setLoading(false);
    }
  }

  async function reloadAllData() {
    await Promise.all([
      loadMetaData(),
      loadHome(),
      loadPlans(),
      loadTransactions(),
      loadManageData(),
      loadReport(),
      loadSettingsData(),
      loadFeedbackData(),
      loadChangelogData(),
    ]);
  }

  async function api(route, options) {
    var opts = options || {};
    var method = (opts.method || "POST").toUpperCase();
    var requiresAuth = opts.requiresAuth !== false;
    var url = new URL(config.apiBase || "/public/api.php", window.location.origin);
    url.searchParams.set("route", route);

    var fetchOptions = {
      method: method,
      headers: {},
      credentials: "same-origin"
    };

    if (requiresAuth && state.token) {
      fetchOptions.headers.Authorization = "Bearer " + state.token;
    }

    if (method === "GET") {
      var data = opts.data || {};
      Object.keys(data).forEach(function (key) {
        var value = data[key];
        if (value !== undefined && value !== null && value !== "") {
          url.searchParams.set(key, String(value));
        }
      });
    } else {
      fetchOptions.headers["Content-Type"] = "application/json";
      fetchOptions.body = JSON.stringify(opts.data || {});
    }

    var response = await fetch(url.toString(), fetchOptions);
    var payload;
    try {
      payload = await response.json();
    } catch (error) {
      payload = { success: false, error: "服务器响应格式异常" };
    }

    if (!response.ok || !payload.success) {
      var err = new Error(payload.error || "请求失败");
      err.status = response.status;
      err.auth = response.status === 401;
      throw err;
    }

    return payload;
  }

  async function uploadFile(route, fieldName, file) {
    var url = new URL(config.apiBase || "/public/api.php", window.location.origin);
    url.searchParams.set("route", route);
    var form = new FormData();
    form.append(fieldName, file);
    var response = await fetch(url.toString(), {
      method: "POST",
      body: form,
      headers: state.token ? { Authorization: "Bearer " + state.token } : {},
      credentials: "same-origin"
    });
    var payload = await response.json();
    if (!response.ok || !payload.success) {
      var err = new Error(payload.error || "上传失败");
      err.status = response.status;
      err.auth = response.status === 401;
      throw err;
    }
    return payload;
  }

  async function uploadForm(route, form) {
    var url = new URL(config.apiBase || "/public/api.php", window.location.origin);
    url.searchParams.set("route", route);
    var response = await fetch(url.toString(), {
      method: "POST",
      body: form,
      headers: state.token ? { Authorization: "Bearer " + state.token } : {},
      credentials: "same-origin"
    });
    var payload = await response.json();
    if (!response.ok || !payload.success) {
      var err = new Error(payload.error || "上传失败");
      err.status = response.status;
      err.auth = response.status === 401;
      throw err;
    }
    return payload;
  }

  function handleAuthFailure(error) {
    if (error && error.auth) {
      clearSession();
      showLogin();
      navigate("login", true);
      toast("登录状态已过期，请重新登录");
      return;
    }
    toast(error && error.message ? error.message : "操作失败");
  }

  function setLoading(visible) {
    els.loadingMask.hidden = !visible;
  }

  function toast(message) {
    if (!message) {
      return;
    }
    els.toast.textContent = message;
    els.toast.hidden = false;
    window.clearTimeout(toast._timer);
    toast._timer = window.setTimeout(function () {
      els.toast.hidden = true;
    }, 2400);
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function formatAmount(value) {
    return Number(value || 0).toFixed(2);
  }

  function formatSignedAmount(value) {
    var amount = Number(value || 0);
    var prefix = amount > 0 ? "+" : "";
    return prefix + formatAmount(amount);
  }

  function formatDebtAmount(value) {
    var amount = Math.abs(Number(value || 0));
    return "-" + formatAmount(amount);
  }

  function formatDate(value) {
    return value ? String(value).slice(0, 10) : "";
  }

  function formatDateTime(value) {
    return value ? String(value).replace("T", " ").slice(0, 16) : "";
  }

  function toDateTimeLocal(value) {
    if (!value) {
      return nowDateTimeLocal();
    }
    return String(value)
      .replace(/\//g, '-')
      .replace(' ', 'T')
      .slice(0, 16);
  }

  function nowDateTimeLocal() {
    var now = new Date();
    return [
      now.getFullYear(),
      pad(now.getMonth() + 1),
      pad(now.getDate())
    ].join("-") + "T" + [pad(now.getHours()), pad(now.getMinutes())].join(":");
  }

  function pad(value) {
    return String(value).padStart(2, "0");
  }

  function statusBadge(label, type) {
    var klass = "status-badge" + (type ? " " + type : "");
    return '<span class="' + klass + '">' + escapeHtml(label) + '</span>';
  }

  function renderHeader() {
    els.headerLedgerName.textContent = state.activeLedgerName || "个人账本";
    els.headerUserBadge.textContent = state.user ? (state.user.nickname || state.user.username || "已登录") : "";
  }

  async function loadMetaData() {
    try {
      var results = await Promise.all([
        api("accounts/groups", { method: "GET" }),
        api("accounts/list", { method: "GET" }),
        api("categories/list", { method: "GET" }),
        api("items/list", { method: "GET" })
      ]);
      state.accountGroups = results[0].groups || [];
      state.accounts = results[1].accounts || [];
      state.categories = results[2].categories || [];
      state.items = results[3].items || [];
      populateTransactionFilters();
      populateLedgerOptions();
      refreshTransactionFormOptions();
      renderManage();
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  async function loadHome() {
    try {
      state.overview = await api("home/overview", { method: "GET" });
      renderHome();
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  function renderHome() {
    if (!state.overview) {
      return;
    }
    var overview = state.overview;
    var assets = overview.assets || {};
    var today = overview.today || {};
    var month = overview.month || {};
    var goals = overview.goals || {};

    var planBudgetTotal = state.budget ? Number(state.budget.totalBudgetExpense || 0) : 0;
    var planBudgetUsed = state.budget ? Number(state.budget.totalUsedExpense || 0) : 0;
    var budgetTotal = Math.max(Number(month.budget_total || 0), planBudgetTotal);
    var budgetUsed = Math.max(Number(month.budget_used || 0), planBudgetUsed);
    var budgetRemain = Math.max(0, budgetTotal - budgetUsed);
    var hasBudget = budgetTotal > 0;
    var budgetPercent = hasBudget ? Math.max(0, Math.min(100, Math.round((budgetUsed / budgetTotal) * 100))) : 0;
    var goalPercent = Math.max(0, Math.min(100, Number(goals.overall_percent || 0)));
    var monthLabel = String(month.year || new Date().getFullYear()) + "年" + String(month.month || (new Date().getMonth() + 1)) + "月";
    var budgetTone = hasBudget && budgetUsed > budgetTotal ? " danger" : "";
    var budgetNote = hasBudget
      ? (budgetUsed > budgetTotal ? "本月预算已超支" : "本月预算剩余 " + formatAmount(budgetRemain))
      : "本月还没有设置预算";
    var budgetHeadValue = hasBudget ? (String(budgetPercent) + "%") : "未设置";

    els.homeHero.innerHTML = ''
      + '<div class="hero-top compact-hero"><div><div class="hero-title">资产总览</div><div class="hero-caption">点击卡片可查看对应账户明细</div></div>'
      + '<div>' + statusBadge(state.activeLedgerName || "个人账本", "safe") + '</div></div>'
      + '<button type="button" class="asset-total-card" data-home-asset="total_assets">'
      + '<span>总资产</span><strong>' + escapeHtml(formatAmount(assets.total_assets)) + '</strong>'
      + '</button>'
      + '<div class="asset-grid">'
      + '<button type="button" class="asset-cell" data-home-asset="net_assets"><span>净资产</span><strong>' + escapeHtml(formatAmount(assets.net_assets)) + '</strong></button>'
      + '<button type="button" class="asset-cell" data-home-asset="total_debt"><span>负债</span><strong>' + escapeHtml(formatAmount(assets.total_debt)) + '</strong></button>'
      + '<button type="button" class="asset-cell" data-home-asset="financial"><span>资金账户</span><strong>' + escapeHtml(formatAmount(assets.financial)) + '</strong></button>'
      + '<button type="button" class="asset-cell" data-home-asset="saving"><span>储蓄账户</span><strong>' + escapeHtml(formatAmount(assets.saving)) + '</strong></button>'
      + '<button type="button" class="asset-cell" data-home-asset="receivable"><span>应收款</span><strong>' + escapeHtml(formatAmount(assets.receivable)) + '</strong></button>'
      + '<button type="button" class="asset-cell" data-home-asset="other"><span>其他</span><strong>' + escapeHtml(formatAmount(assets.other)) + '</strong></button>'
      + '</div>';

    els.homeMetrics.innerHTML = ''
      + '<div class="card-shell home-block">'
      + '<div class="section-head compact"><div><div class="section-title">今日收支</div><div class="section-meta">当天记录概况</div></div></div>'
      + '<div class="summary-grid compact-home-grid">'
      + '<div class="summary-tile"><span>收入</span><strong class="positive">' + escapeHtml(formatAmount(today.income)) + '</strong></div>'
      + '<div class="summary-tile"><span>支出</span><strong>' + escapeHtml(formatAmount(today.expense)) + '</strong></div>'
      + '<div class="summary-tile"><span>结余</span><strong class="' + (Number(today.net || 0) >= 0 ? 'positive' : 'negative') + '">' + escapeHtml(formatSignedAmount(today.net)) + '</strong></div>'
      + '</div>'
      + '</div>'
      + '<div class="card-shell home-block">'
      + '<div class="section-head compact"><div><div class="section-title">本月收支与预算</div><div class="section-meta">' + escapeHtml(monthLabel) + '</div></div></div>'
      + '<div class="summary-grid compact-home-grid">'
      + '<div class="summary-tile"><span>收入</span><strong class="positive">' + escapeHtml(formatAmount(month.income)) + '</strong></div>'
      + '<div class="summary-tile"><span>支出</span><strong>' + escapeHtml(formatAmount(month.expense)) + '</strong></div>'
      + '<div class="summary-tile"><span>结余</span><strong class="' + (Number(month.net || 0) >= 0 ? 'positive' : 'negative') + '">' + escapeHtml(formatSignedAmount(month.net)) + '</strong></div>'
      + '</div>'
      + '<div class="progress-card' + budgetTone + '">'
      + '<div class="progress-head"><span>预算进度</span><strong>' + escapeHtml(budgetHeadValue) + '</strong></div>'
      + '<div class="bar-track"><div class="bar-fill' + budgetTone + '" style="width:' + budgetPercent + '%"></div></div>'
      + '<div class="progress-meta"><span>预算 ' + escapeHtml(formatAmount(budgetTotal)) + '</span><span>已用 ' + escapeHtml(formatAmount(budgetUsed)) + '</span></div>'
      + '<div class="progress-meta"><span>剩余 ' + escapeHtml(formatAmount(budgetRemain)) + '</span><span>' + (hasBudget ? ('已用 ' + escapeHtml(String(budgetPercent)) + '%') : '请先设置预算') + '</span></div>'
      + '<div class="progress-note">' + escapeHtml(budgetNote) + '</div>'
      + '</div>'
      + '</div>'
      + '<div class="card-shell home-block">'
      + '<div class="section-head compact"><div><div class="section-title">目标进度</div><div class="section-meta">当前进行中的目标</div></div></div>'
      + '<div class="summary-grid goal-summary-grid">'
      + '<div class="summary-tile"><span>目标总额</span><strong>' + escapeHtml(formatAmount(goals.total_target)) + '</strong></div>'
      + '<div class="summary-tile"><span>已存入</span><strong class="positive">' + escapeHtml(formatAmount(goals.total_saved)) + '</strong></div>'
      + '</div>'
      + '<div class="progress-card">'
      + '<div class="progress-head"><span>整体完成度</span><strong>' + escapeHtml(String(goalPercent)) + '%</strong></div>'
      + '<div class="bar-track"><div class="bar-fill income" style="width:' + goalPercent + '%"></div></div>'
      + '<div class="progress-meta"><span>进行中目标</span><span>' + escapeHtml(String(goals.active_count || 0)) + ' 个</span></div>'
      + '</div>'
      + '</div>';

    var recent = overview.recent_transactions || [];
    els.recentTransactions.innerHTML = recent.length
      ? recent.map(function (tx) { return renderTransactionItem(tx, false); }).join("")
      : '<div class="empty-state">最近还没有流水</div>';
  }

  async function loadPlans() {
    try {
      var results = await Promise.all([
        api("goals/list", { method: "GET" }),
        api("budget/month", { method: "GET", data: { year: state.budgetYear, month: state.budgetMonth } })
      ]);
      state.goals = results[0].goals || [];
      state.budget = results[1];
      renderPlans();
      if (state.overview) {
        renderHome();
      }
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  function renderPlans() {
    var year = state.budget ? state.budget.year : state.budgetYear;
    var month = state.budget ? state.budget.month : state.budgetMonth;
    els.plansMonthLabel.textContent = year + "-" + pad(month);

    var totalBudget = state.budget ? Number(state.budget.totalBudgetExpense || 0) : 0;
    var usedBudget = state.budget ? Number(state.budget.totalUsedExpense || 0) : 0;
    var remainBudget = Math.max(0, totalBudget - usedBudget);
    var cards = [
      { label: "预算总额", value: formatAmount(totalBudget) },
      { label: "已用预算", value: formatAmount(usedBudget) },
      { label: "预算剩余", value: formatAmount(remainBudget) },
      { label: "进行中目标", value: String(state.goals.filter(function (goal) { return goal.status !== "archived"; }).length) },
    ];
    els.plansSummary.innerHTML = cards.map(function (card) {
      return '<div class="metric-card"><div class="metric-label">' + escapeHtml(card.label) + '</div><div class="metric-value">' + escapeHtml(card.value) + '</div></div>';
    }).join("");

    els.goalsList.innerHTML = state.goals.length ? state.goals.map(function (goal) {
      return ''
        + '<div class="list-item">'
        + '<div class="list-head"><div><div class="list-title">' + escapeHtml(goal.title) + '</div><div class="list-sub">目标 ' + escapeHtml(formatAmount(goal.target_amount)) + ' · 已存 ' + escapeHtml(formatAmount(goal.saved_amount)) + '</div></div><div>' + statusBadge(goal.status === 'done' ? '已完成' : (goal.status === 'archived' ? '已归档' : '进行中'), goal.status === 'active' ? 'safe' : '') + '</div></div>'
        + '<div class="list-sub">截止：' + escapeHtml(goal.deadline || '未设置') + ' · 进度 ' + escapeHtml(String(goal.percent || 0)) + '%</div>'
        + '<div class="bar-track"><div class="bar-fill income" style="width:' + Math.max(4, Number(goal.barPercent || 0)) + '%"></div></div>'
        + '<div class="list-actions"><button type="button" class="mini-btn" data-plan-entity="goal" data-action="edit" data-id="' + escapeHtml(goal.id) + '">编辑</button><button type="button" class="mini-btn danger" data-plan-entity="goal" data-action="delete" data-id="' + escapeHtml(goal.id) + '">删除</button></div>'
        + '</div>';
    }).join("") : '<div class="empty-state">还没有目标，先创建一个吧</div>';

    var budgets = state.budget && state.budget.budgets ? state.budget.budgets : [];
    els.budgetList.innerHTML = budgets.length ? budgets.map(function (budget) {
      var title = [budget.category_name || "全部分类", budget.item_name || "全部项目"].filter(Boolean).join(" / ");
      return ''
        + '<div class="list-item">'
        + '<div class="list-head"><div><div class="list-title">' + escapeHtml(title) + '</div><div class="list-sub">' + escapeHtml(budget.type === 'income' ? '收入预算' : '支出预算') + '</div></div><div class="list-amount">' + escapeHtml(formatAmount(budget.amount)) + '</div></div>'
        + '<div class="badge-row"><span class="badge">已用 ' + escapeHtml(formatAmount(budget.used_amount)) + '</span><span class="badge">剩余 ' + escapeHtml(formatAmount(budget.remain_amount)) + '</span></div>'
        + '<div class="list-actions"><button type="button" class="mini-btn" data-plan-entity="budget" data-action="edit" data-id="' + escapeHtml(budget.id) + '">编辑</button><button type="button" class="mini-btn danger" data-plan-entity="budget" data-action="delete" data-id="' + escapeHtml(budget.id) + '">删除</button></div>'
        + '</div>';
    }).join("") : '<div class="empty-state">这个月还没有预算</div>';
  }

  function shiftBudgetMonth(offset) {
    var date = new Date(state.budgetYear, state.budgetMonth - 1 + offset, 1);
    state.budgetYear = date.getFullYear();
    state.budgetMonth = date.getMonth() + 1;
    loadPlans();
  }

  function onGoalListClick(event) {
    var button = event.target.closest("button[data-plan-entity]");
    if (!button) {
      return;
    }
    var entity = button.getAttribute("data-plan-entity");
    var action = button.getAttribute("data-action");
    var id = Number(button.getAttribute("data-id") || 0);
    if (!id) {
      return;
    }

    if (entity === "goal") {
      var goal = state.goals.find(function (item) { return Number(item.id) === id; });
      if (!goal) {
        return;
      }
      if (action === "edit") {
        openGoalEditor(goal);
      } else if (action === "delete") {
        deleteGoal(id);
      }
    }
  }

  function onBudgetListClick(event) {
    var button = event.target.closest("button[data-plan-entity]");
    if (!button) {
      return;
    }
    var entity = button.getAttribute("data-plan-entity");
    var action = button.getAttribute("data-action");
    var id = Number(button.getAttribute("data-id") || 0);
    if (!id || entity !== "budget" || !state.budget || !state.budget.budgets) {
      return;
    }
    var budget = state.budget.budgets.find(function (item) { return Number(item.id) === id; });
    if (!budget) {
      return;
    }
    if (action === "edit") {
      openBudgetEditor(budget);
    } else if (action === "delete") {
      deleteBudget(id);
    }
  }

  async function openGoalEditor(goal) {
    var current = goal || {};
    openEditorSheet("goal", {
      mode: current.id ? "edit" : "create",
      id: current.id || 0,
      title: current.id ? "编辑目标" : "新增目标",
      meta: "设定金额、进度和截止日期",
      nameLabel: "目标名称",
      name: current.title || "",
      goalAccountId: current.account_id || "",
      targetAmount: current.target_amount || "",
      savedAmount: current.saved_amount || "0",
      deadline: current.deadline || "",
      goalStatus: current.status || "active",
    });
  }

  async function deleteGoal(id) {
    openConfirmSheet({
      title: "删除目标",
      meta: "删除后无法恢复",
      message: "确认删除这个目标吗？",
      confirmText: "确认删除",
      action: async function () {
        await api("goals/delete", { method: "POST", data: { id: id } });
        toast("目标已删除");
        await Promise.all([loadPlans(), loadHome()]);
      }
    });
  }

  async function openBudgetEditor(budget) {
    var current = budget || {};
    openEditorSheet("budget", {
      mode: current.id ? "edit" : "create",
      id: current.id || 0,
      title: current.id ? "编辑预算" : "新增预算",
      meta: state.budgetYear + "-" + pad(state.budgetMonth) + " 的预算设置",
      budgetType: current.type || "expense",
      budgetCategoryId: current.category_id || "",
      budgetItemId: current.item_id || "",
      budgetAmount: current.amount || "",
    });
  }

  async function deleteBudget(id) {
    openConfirmSheet({
      title: "删除预算",
      meta: "删除后本月预算将移除",
      message: "确认删除这条预算吗？",
      confirmText: "确认删除",
      action: async function () {
        await api("budget/delete", { method: "POST", data: { id: id } });
        toast("预算已删除");
        await Promise.all([loadPlans(), loadHome()]);
      }
    });
  }

  function populateTransactionFilters() {
    fillSelect(els.filterAccount, [{ id: "", name: "全部账户" }].concat(state.accounts));
    fillSelect(els.filterCategory, [{ id: "", name: "全部分类" }].concat(state.categories));
  }

  function fillSelect(select, items) {
    select.innerHTML = items.map(function (item) {
      return '<option value="' + escapeHtml(item.id == null ? "" : item.id) + '">' + escapeHtml(item.name || "") + '</option>';
    }).join("");
  }

  async function loadTransactions() {
    try {
      var type = els.filterType.value;
      var res = await api("transactions/list", {
        method: "GET",
        data: {
          page: 1,
          page_size: 50,
          type: type === "all" ? "" : type,
          account_id: els.filterAccount.value,
          category_id: els.filterCategory.value,
          date_from: els.filterDateFrom.value,
          date_to: els.filterDateTo.value,
          remark: els.filterKeyword.value.trim(),
        }
      });
      state.transactions = res.transactions || [];
      state.transactionSummary = res.summary || { income: 0, expense: 0 };
      renderTransactions();
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  function renderTransactions() {
    els.transactionSummary.innerHTML = [
      { label: "收入合计", value: state.transactionSummary.income },
      { label: "支出合计", value: state.transactionSummary.expense }
    ].map(function (card) {
      return '<div class="metric-card"><div class="metric-label">' + escapeHtml(card.label) + '</div><div class="metric-value">' + escapeHtml(formatAmount(card.value)) + '</div></div>';
    }).join("");

    els.transactionList.innerHTML = state.transactions.length
      ? state.transactions.map(function (tx) { return renderTransactionItem(tx, true); }).join("")
      : '<div class="empty-state">没有找到符合筛选条件的流水</div>';
  }

  function renderTransactionItem(tx, withActions) {
    var itemClass = withActions ? "list-item" : "list-item compact-item recent-item";
    var accountName = tx.type === "income"
      ? (tx.to_account_name || "未设置账户")
      : (tx.type === "transfer"
        ? ((tx.from_account_name || "未设置") + " -> " + (tx.to_account_name || "未设置"))
        : (tx.from_account_name || "未设置账户"));
    var remark = tx.remark ? '<div class="list-sub">备注：' + escapeHtml(tx.remark) + '</div>' : "";
    var actions = withActions
      ? '<div class="list-actions"><button type="button" class="mini-btn" data-tx-action="edit" data-id="' + escapeHtml(tx.id) + '">编辑</button><button type="button" class="mini-btn danger" data-tx-action="delete" data-id="' + escapeHtml(tx.id) + '">删除</button></div>'
      : "";
    return ''
      + '<div class="' + itemClass + '">'
      + '<div class="list-head"><div><div class="list-title">' + escapeHtml(tx.category_name || "未分类") + '</div><div class="list-sub">' + escapeHtml(accountName) + ' · ' + escapeHtml(formatDateTime(tx.trans_time)) + '</div></div><div class="list-amount">' + escapeHtml(formatAmount(tx.amount)) + '</div></div>'
      + remark
      + (tx.attachment_url ? ('<button type="button" class="transaction-attachment-preview" data-preview-image="' + escapeHtml(tx.attachment_url) + '"><img class="feedback-thumb" src="' + escapeHtml(tx.attachment_url) + '" alt="附件"></button>') : '')
      + actions
      + '</div>';
  }

  function resetTransactionFilters() {
    els.filterType.value = "all";
    els.filterAccount.value = "";
    els.filterCategory.value = "";
    els.filterDateFrom.value = "";
    els.filterDateTo.value = "";
    els.filterKeyword.value = "";
    loadTransactions();
  }

  function onTransactionListClick(event) {
    var button = event.target.closest("button[data-tx-action]");
    if (!button) {
      return;
    }
    var action = button.getAttribute("data-tx-action");
    var id = Number(button.getAttribute("data-id") || 0);
    if (!id) {
      return;
    }
    var tx = state.transactions.find(function (item) { return Number(item.id) === id; });
    if (!tx) {
      return;
    }

    if (action === "edit") {
      state.editingTransactionId = id;
      state.transactionAttachments = (tx.attachments || []).map(function (path, index) {
        return {
          path: path,
          url: (tx.attachment_urls && tx.attachment_urls[index]) || tx.attachment_url || ""
        };
      }).filter(function (item) {
        return !!item.path;
      });
      els.transactionFormState.textContent = "编辑记录 #" + id;
      els.formType.value = tx.type || "expense";
      refreshTransactionFormOptions();
      els.formCategory.value = tx.category_id ? String(tx.category_id) : "";
      refreshTransactionFormOptions();
      els.formItem.value = tx.item_id ? String(tx.item_id) : "";
      els.formFromAccount.value = tx.from_account_id ? String(tx.from_account_id) : "";
      els.formToAccount.value = tx.to_account_id ? String(tx.to_account_id) : "";
      els.formAmount.value = String(tx.amount || "");
      els.formTransTime.value = toDateTimeLocal(tx.trans_time);
      els.formRemark.value = tx.remark || "";
      renderTransactionAttachments();
      navigate("add");
    } else if (action === "delete") {
      deleteTransaction(id);
    }
  }

  async function deleteTransaction(id) {
    openConfirmSheet({
      title: "删除流水",
      meta: "删除后数据不可恢复",
      message: "确认删除这笔流水吗？",
      confirmText: "确认删除",
      action: async function () {
        await api("transactions/delete", { method: "POST", data: { ids: [id] } });
        toast("流水已删除");
        await Promise.all([loadTransactions(), loadHome(), loadPlans(), loadReport(), loadSettingsData()]);
      }
    });
  }

  function refreshTransactionFormOptions() {
    var type = els.formType.value;
    var categories = state.categories.filter(function (category) {
      return category.type === type;
    });
    els.formCategory.innerHTML = ['<option value="">请选择分类</option>'].concat(categories.map(function (category) {
      return '<option value="' + escapeHtml(category.id) + '">' + escapeHtml(category.name) + '</option>';
    })).join("");

    var categoryId = Number(els.formCategory.value || 0);
    var items = state.items.filter(function (item) {
      return !categoryId || Number(item.category_id) === categoryId;
    });
    els.formItem.innerHTML = ['<option value="">不选择项目</option>'].concat(items.map(function (item) {
      return '<option value="' + escapeHtml(item.id) + '">' + escapeHtml(item.name) + '</option>';
    })).join("");

    var accountOptions = ['<option value="">请选择账户</option>'].concat(state.accounts.map(function (account) {
      return '<option value="' + escapeHtml(account.id) + '">' + escapeHtml(account.name) + '</option>';
    })).join("");
    els.formFromAccount.innerHTML = accountOptions;
    els.formToAccount.innerHTML = accountOptions;

    els.fromAccountField.hidden = type === "income";
    els.toAccountField.hidden = type === "expense";
  }

  function resetTransactionForm(showToast) {
    state.editingTransactionId = 0;
    state.transactionAttachments = [];
    els.transactionForm.reset();
    els.formType.value = "expense";
    els.formTransTime.value = nowDateTimeLocal();
    refreshTransactionFormOptions();
    renderTransactionAttachments();
    els.transactionFormState.textContent = "新建记录";
    if (showToast) {
      toast("表单已重置");
    }
  }

  async function onTransactionSubmit(event) {
    event.preventDefault();
    var payload = {
      id: state.editingTransactionId || undefined,
      type: els.formType.value,
      category_id: Number(els.formCategory.value || 0),
      item_id: els.formItem.value ? Number(els.formItem.value) : null,
      from_account_id: els.formFromAccount.value ? Number(els.formFromAccount.value) : null,
      to_account_id: els.formToAccount.value ? Number(els.formToAccount.value) : null,
      amount: Number(els.formAmount.value || 0),
      trans_time: els.formTransTime.value || nowDateTimeLocal(),
      remark: els.formRemark.value.trim(),
      attachment_paths: state.transactionAttachments.map(function (item) { return item.path; })
    };

    if (!payload.category_id || payload.amount <= 0) {
      toast("请填写分类和金额");
      return;
    }

    setLoading(true);
    try {
      await api(state.editingTransactionId ? "transactions/update" : "transactions/create", {
        method: "POST",
        data: payload
      });
      toast(state.editingTransactionId ? "流水已更新" : "流水已保存");
      resetTransactionForm(false);
      await Promise.all([loadTransactions(), loadHome(), loadPlans(), loadReport(), loadSettingsData()]);
      navigate("transactions");
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      setLoading(false);
    }
  }

  function renderTransactionAttachments() {
    if (!els.transactionAttachmentList) {
      return;
    }
    els.transactionAttachmentList.innerHTML = state.transactionAttachments.length
      ? state.transactionAttachments.map(function (item, index) {
        return ''
          + '<div class="transaction-attachment-chip">'
          + '<button type="button" class="feedback-image" data-preview-image="' + escapeHtml(item.url) + '"><img class="feedback-thumb" src="' + escapeHtml(item.url) + '" alt="附件图片"></button>'
          + '<button type="button" class="mini-btn danger transaction-attachment-remove-btn" data-attachment-remove="' + index + '">删除</button>'
          + '</div>';
      }).join("")
      : '<div class="section-meta">暂未上传附件</div>';
  }

  async function onTransactionAttachmentsChange(event) {
    var files = Array.prototype.slice.call((event.target && event.target.files) || []);
    event.target.value = "";
    if (!files.length) {
      return;
    }

    var allowed = Math.max(0, 5 - state.transactionAttachments.length);
    if (!allowed) {
      toast("最多上传 5 张图片");
      return;
    }

    setLoading(true);
    try {
      for (var i = 0; i < files.length && i < allowed; i += 1) {
        var result = await uploadFile("transactions/upload-attachment", "file", files[i]);
        state.transactionAttachments.push({ path: result.path, url: result.url });
      }
      renderTransactionAttachments();
      toast("附件已上传");
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      setLoading(false);
    }
  }

  function clearTransactionAttachments() {
    state.transactionAttachments = [];
    renderTransactionAttachments();
    toast("附件已清空");
  }

  function onTransactionAttachmentListClick(event) {
    var removeButton = event.target.closest("button[data-attachment-remove]");
    if (removeButton) {
      var index = Number(removeButton.getAttribute("data-attachment-remove") || -1);
      if (index >= 0) {
        state.transactionAttachments.splice(index, 1);
        renderTransactionAttachments();
      }
      return;
    }

    var previewTrigger = event.target.closest("[data-preview-image]");
    if (previewTrigger && previewTrigger.getAttribute("data-preview-image")) {
      openMediaViewer(previewTrigger.getAttribute("data-preview-image") || "");
    }
  }

  async function loadManageData() {
    try {
      var results = await Promise.all([
        api("assets/list", { method: "GET" }),
        api("subscriptions/list", { method: "GET" }),
        api("icon-library/list", { method: "GET" })
      ]);
      state.assets = {
        active: (results[0].assets && results[0].assets.active) || [],
        transferred: (results[0].assets && results[0].assets.transferred) || [],
        summary: results[0].summary || { total_value: 0, total_daily_cost: 0, asset_count: 0 }
      };
      state.subscriptions = results[1].subscriptions || [];
      state.icons = results[2].icons || [];
      renderManage();
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  function onManageTabClick(event) {
    var button = event.target.closest("button[data-manage-tab]");
    if (!button) {
      return;
    }
    state.manageTab = button.getAttribute("data-manage-tab") || "accounts";
    window.localStorage.setItem(storageKeyManageTab, state.manageTab);
    Array.prototype.slice.call(els.manageTabs.querySelectorAll(".segment-btn")).forEach(function (item) {
      item.classList.toggle("active", item === button);
    });
    renderManage();
  }

  function onManageControlsClick(event) {
    var button = event.target.closest("button[data-manage-filter]");
    if (!button) {
      return;
    }
    var value = button.getAttribute("data-manage-filter") || "all";
    state.manageFilters[state.manageTab] = value;
    renderManage();
  }

  function getManageFilterValue() {
    return state.manageFilters[state.manageTab] || "all";
  }

  function renderManageFilterBadge(label, value, active, count) {
    var klass = "badge badge-filter" + (active ? " active" : "");
    var text = count == null ? label : (label + " " + count);
    return '<button type="button" class="' + klass + '" data-manage-filter="' + escapeHtml(value) + '">' + escapeHtml(text) + '</button>';
  }

  function getInitialBadgeText(value, fallback) {
    var text = String(value || fallback || "?").trim();
    return text ? text.slice(0, 1).toUpperCase() : "?";
  }

  function renderManageThumb(url, fallbackText, variant) {
    var klass = 'manage-thumb' + (variant ? (' ' + variant) : '');
    if (url) {
      return '<span class="' + klass + '"><img class="manage-thumb-image" src="' + escapeHtml(url) + '" alt="' + escapeHtml(fallbackText || '图标') + '"></span>';
    }
    return '<span class="' + klass + ' manage-thumb-fallback">' + escapeHtml(getInitialBadgeText(fallbackText, '?')) + '</span>';
  }

  function getFilteredManageData() {
    var filterValue = getManageFilterValue();
    if (state.manageTab === "accounts") {
      return state.accounts.filter(function (account) {
        return filterValue === "all" || String(account.group_id || 0) === filterValue;
      });
    }
    if (state.manageTab === "categories") {
      return state.categories.filter(function (category) {
        return filterValue === "all" || category.type === filterValue;
      });
    }
    if (state.manageTab === "items") {
      return state.items.filter(function (item) {
        return filterValue === "all" || String(item.category_id || 0) === filterValue;
      });
    }
    if (state.manageTab === "assets") {
      var allAssets = state.assets.active.concat(state.assets.transferred);
      return allAssets.filter(function (asset) {
        return filterValue === "all" || asset.status === filterValue;
      });
    }
    if (state.manageTab === "subscriptions") {
      return state.subscriptions.filter(function (subscription) {
        if (filterValue === "all") {
          return true;
        }
        if (filterValue === "auto_renew") {
          return !!subscription.auto_renew;
        }
        return subscription.type === filterValue;
      });
    }
    return state.icons.slice();
  }

  function renderManage() {
    if (!els.manageContent) {
      return;
    }
    Array.prototype.slice.call(els.manageTabs.querySelectorAll(".segment-btn")).forEach(function (item) {
      item.classList.toggle("active", item.getAttribute("data-manage-tab") === state.manageTab);
    });
    renderManageControls();
    renderManageSummary();
    renderManageContent();
  }

  function renderManageControls() {
    var html = '';
    var filterValue = getManageFilterValue();
    if (state.manageTab === "accounts") {
      html = '<div class="control-card"><div class="badge-row">'
        + renderManageFilterBadge('全部', 'all', filterValue === 'all', state.accounts.length)
        + state.accountGroups.map(function (group) {
        var count = state.accounts.filter(function (account) { return Number(account.group_id || 0) === Number(group.id); }).length;
        return renderManageFilterBadge(group.name, String(group.id), filterValue === String(group.id), count);
      }).join("") + '</div></div>';
    } else if (state.manageTab === "categories") {
      html = '<div class="control-card"><div class="badge-row">'
        + renderManageFilterBadge('全部', 'all', filterValue === 'all', state.categories.length)
        + renderManageFilterBadge('支出', 'expense', filterValue === 'expense', state.categories.filter(function (c) { return c.type === 'expense'; }).length)
        + renderManageFilterBadge('收入', 'income', filterValue === 'income', state.categories.filter(function (c) { return c.type === 'income'; }).length)
        + renderManageFilterBadge('转账', 'transfer', filterValue === 'transfer', state.categories.filter(function (c) { return c.type === 'transfer'; }).length)
        + '</div></div>';
    } else if (state.manageTab === "items") {
      html = '<div class="control-card"><div class="badge-row">'
        + renderManageFilterBadge('全部', 'all', filterValue === 'all', state.items.length)
        + state.categories.map(function (category) {
        var count = state.items.filter(function (item) { return Number(item.category_id || 0) === Number(category.id); }).length;
        return count ? renderManageFilterBadge(category.name, String(category.id), filterValue === String(category.id), count) : '';
      }).join('')
        + '</div></div>';
    } else if (state.manageTab === "assets") {
      html = '<div class="control-card"><div class="badge-row">'
        + renderManageFilterBadge('全部', 'all', filterValue === 'all', state.assets.active.length + state.assets.transferred.length)
        + renderManageFilterBadge('在用资产', 'active', filterValue === 'active', state.assets.active.length)
        + renderManageFilterBadge('已转手', 'transferred', filterValue === 'transferred', state.assets.transferred.length)
        + '</div></div>';
    } else if (state.manageTab === "subscriptions") {
      html = '<div class="control-card"><div class="badge-row">'
        + renderManageFilterBadge('全部', 'all', filterValue === 'all', state.subscriptions.length)
        + renderManageFilterBadge('订阅', 'subscription', filterValue === 'subscription', state.subscriptions.filter(function (sub) { return sub.type === 'subscription'; }).length)
        + renderManageFilterBadge('买断', 'lifetime', filterValue === 'lifetime', state.subscriptions.filter(function (sub) { return sub.type === 'lifetime'; }).length)
        + renderManageFilterBadge('自动续费', 'auto_renew', filterValue === 'auto_renew', state.subscriptions.filter(function (sub) { return sub.auto_renew; }).length)
        + '</div></div>';
    } else if (state.manageTab === "icons") {
      html = '<div class="control-card"><div class="badge-row"><span class="badge">自定义图标 ' + state.icons.length + '</span><span class="badge">支持上传与替换</span></div></div>';
    }
    els.manageControls.innerHTML = html;
  }

  function renderManageSummary() {
    var cards = [];
    var filtered = getFilteredManageData();
    if (state.manageTab === "accounts") {
      cards = [
        { label: "账户数量", value: String(filtered.length) },
        { label: "总余额", value: formatAmount(filtered.reduce(function (sum, account) { return sum + Number(account.current_balance || 0); }, 0)) }
      ];
    } else if (state.manageTab === "categories") {
      cards = [
        { label: "分类数量", value: String(filtered.length) },
        { label: "当前类型", value: getManageFilterValue() === 'all' ? '全部' : (getManageFilterValue() === 'expense' ? '支出' : (getManageFilterValue() === 'income' ? '收入' : '转账')) }
      ];
    } else if (state.manageTab === "items") {
      cards = [
        { label: "项目数量", value: String(filtered.length) },
        { label: "关联分类", value: String(new Set(filtered.map(function (item) { return item.category_id; })).size) }
      ];
    } else if (state.manageTab === "assets") {
      cards = [
        { label: "资产数量", value: String(filtered.length) },
        { label: "资产总值", value: formatAmount(filtered.reduce(function (sum, asset) { return sum + Number(asset.value_amount || 0); }, 0)) }
      ];
    } else if (state.manageTab === "subscriptions") {
      cards = [
        { label: "订阅数量", value: String(filtered.length) },
        { label: "自动续费", value: String(filtered.filter(function (sub) { return sub.auto_renew; }).length) }
      ];
    } else if (state.manageTab === "icons") {
      cards = [
        { label: "图标数量", value: String(state.icons.length) },
        { label: "可复用素材", value: String(state.icons.length) }
      ];
    }
    els.manageSummary.innerHTML = cards.map(function (card) {
      return '<div class="metric-card"><div class="metric-label">' + escapeHtml(card.label) + '</div><div class="metric-value">' + escapeHtml(card.value) + '</div></div>';
    }).join("");
  }

  function renderManageContent() {
    var html = "";
    var filtered = getFilteredManageData();
    if (state.manageTab === "accounts") {
      html = filtered.length ? filtered.map(function (account) {
        return ''
          + '<div class="list-item">'
          + '<div class="list-head"><div><div class="list-title">' + escapeHtml(account.name) + '</div><div class="list-sub">' + escapeHtml(account.group_name || '') + (account.account_no ? (' · ' + escapeHtml(account.account_no)) : '') + '</div></div><div class="list-amount">' + escapeHtml(formatAmount(account.current_balance)) + '</div></div>'
          + '<div class="list-actions"><button type="button" class="mini-btn" data-entity="account" data-action="edit" data-id="' + escapeHtml(account.id) + '">编辑</button><button type="button" class="mini-btn danger" data-entity="account" data-action="delete" data-id="' + escapeHtml(account.id) + '">删除</button></div>'
          + '</div>';
      }).join("") : '<div class="empty-state">当前筛选下没有账户</div>';
    } else if (state.manageTab === "categories") {
      html = filtered.length ? filtered.map(function (category) {
        return ''
          + '<div class="list-item">'
          + '<div class="list-head"><div><div class="list-title">' + escapeHtml(category.name) + '</div><div class="list-sub">' + escapeHtml(category.type) + ' · 排序 ' + escapeHtml(String(category.sort_order || 0)) + '</div></div><div>' + statusBadge(category.type === 'expense' ? '支出' : (category.type === 'income' ? '收入' : '转账'), category.type === 'income' ? 'safe' : '') + '</div></div>'
          + '<div class="list-actions"><button type="button" class="mini-btn" data-entity="category" data-action="edit" data-id="' + escapeHtml(category.id) + '">编辑</button><button type="button" class="mini-btn danger" data-entity="category" data-action="delete" data-id="' + escapeHtml(category.id) + '">删除</button></div>'
          + '</div>';
      }).join("") : '<div class="empty-state">当前筛选下没有分类</div>';
    } else if (state.manageTab === "items") {
      html = filtered.length ? filtered.map(function (item) {
        return ''
          + '<div class="list-item">'
          + '<div class="list-head"><div><div class="list-title">' + escapeHtml(item.name) + '</div><div class="list-sub">' + escapeHtml(item.category_label || item.category_name || '') + ' · 排序 ' + escapeHtml(String(item.sort_order || 0)) + '</div></div></div>'
          + '<div class="list-actions"><button type="button" class="mini-btn" data-entity="item" data-action="edit" data-id="' + escapeHtml(item.id) + '">编辑</button><button type="button" class="mini-btn danger" data-entity="item" data-action="delete" data-id="' + escapeHtml(item.id) + '">删除</button></div>'
          + '</div>';
      }).join("") : '<div class="empty-state">当前筛选下没有项目</div>';
    } else if (state.manageTab === "assets") {
      html = filtered.length ? filtered.map(function (asset) {
        var badge = asset.status === 'active' ? statusBadge('在用', 'safe') : statusBadge('已转手');
        var extra = asset.status === 'active'
          ? '日均成本 ' + formatAmount(asset.daily_cost)
          : '转手价 ' + formatAmount(asset.transfer_price || 0);
        var transferAction = asset.status === 'active'
          ? '<button type="button" class="mini-btn" data-entity="asset" data-action="transfer" data-id="' + escapeHtml(asset.id) + '">标记转手</button>'
          : '';
        var thumb = renderManageThumb(asset.icon_url || '', asset.name || (asset.status === 'active' ? '资' : '转'), asset.status === 'active' ? 'asset' : 'asset transferred');
        return ''
          + '<div class="list-item manage-row-card">'
          + '<div class="list-head manage-row-head"><div class="manage-row-main">' + thumb + '<div class="manage-row-copy"><div class="list-title">' + escapeHtml(asset.name) + '</div><div class="list-sub">到手 ' + escapeHtml(formatDate(asset.acquired_date)) + ' · ' + escapeHtml(extra) + '</div></div></div><div>' + badge + '</div></div>'
          + '<div class="list-sub">价值 ' + escapeHtml(formatAmount(asset.value_amount)) + (asset.remark ? (' · ' + escapeHtml(asset.remark)) : '') + '</div>'
          + '<div class="list-actions">' + transferAction + '<button type="button" class="mini-btn" data-entity="asset" data-action="edit" data-id="' + escapeHtml(asset.id) + '">编辑</button><button type="button" class="mini-btn danger" data-entity="asset" data-action="delete" data-id="' + escapeHtml(asset.id) + '">删除</button></div>'
          + '</div>';
      }).join("") : '<div class="empty-state">当前筛选下没有资产记录</div>';
    } else if (state.manageTab === "subscriptions") {
      html = filtered.length ? filtered.map(function (subscription) {
        var daysLeft = subscription.days_left == null ? '长期' : (subscription.days_left + ' 天');
        var thumb = renderManageThumb(subscription.icon_url || '', subscription.platform || (subscription.type === 'lifetime' ? '断' : '订'), subscription.type === 'lifetime' ? 'subscription lifetime' : 'subscription');
        return ''
          + '<div class="list-item manage-row-card">'
          + '<div class="list-head manage-row-head"><div class="manage-row-main">' + thumb + '<div class="manage-row-copy"><div class="list-title">' + escapeHtml(subscription.platform) + '</div><div class="list-sub">' + escapeHtml(subscription.type === 'lifetime' ? '买断' : '订阅') + ' · 价格 ' + escapeHtml(formatAmount(subscription.price)) + '</div></div></div><div>' + statusBadge(daysLeft, subscription.days_left !== null && subscription.days_left <= 7 ? 'danger' : 'safe') + '</div></div>'
          + '<div class="list-sub">到期 ' + escapeHtml(subscription.expire_date || '长期') + (subscription.period ? (' · 周期 ' + escapeHtml(subscription.period)) : '') + '</div>'
          + '<div class="list-actions"><button type="button" class="mini-btn" data-entity="subscription" data-action="renew" data-id="' + escapeHtml(subscription.id) + '">续费</button><button type="button" class="mini-btn" data-entity="subscription" data-action="edit" data-id="' + escapeHtml(subscription.id) + '">编辑</button><button type="button" class="mini-btn danger" data-entity="subscription" data-action="delete" data-id="' + escapeHtml(subscription.id) + '">关闭</button></div>'
          + '</div>';
      }).join("") : '<div class="empty-state">当前筛选下没有订阅记录</div>';
    } else if (state.manageTab === "icons") {
      html = state.icons.length ? '<div class="icon-list">' + state.icons.map(function (icon) {
        var preview = icon.file_url
          ? '<img class="icon-thumb" src="' + escapeHtml(icon.file_url) + '" alt="' + escapeHtml(icon.name) + '">'
          : '<div class="icon-thumb placeholder">图</div>';
        return ''
          + '<div class="icon-card icon-card-row">'
          + '<button type="button" class="icon-card-preview preview-btn icon-card-preview-inline" data-preview-image="' + escapeHtml(icon.file_url || "") + '">' + preview + '</button>'
          + '<div class="icon-card-copy"><div class="icon-card-title">' + escapeHtml(icon.name) + '</div><div class="list-sub">ID ' + escapeHtml(String(icon.id)) + '</div></div>'
          + '<div class="icon-card-actions"><button type="button" class="mini-btn" data-entity="icon" data-action="replace" data-id="' + escapeHtml(icon.id) + '">换图</button><button type="button" class="mini-btn" data-entity="icon" data-action="edit" data-id="' + escapeHtml(icon.id) + '">改名</button><button type="button" class="mini-btn danger" data-entity="icon" data-action="delete" data-id="' + escapeHtml(icon.id) + '">删除</button></div>'
          + '</div>';
      }).join("") + '</div>' : '<div class="empty-state">还没有上传图标</div>';
    }
    els.manageContent.innerHTML = html;
  }

  function onAddManageItem() {
    if (state.manageTab === "accounts") {
      openAccountEditor(null);
    } else if (state.manageTab === "categories") {
      openCategoryEditor(null);
    } else if (state.manageTab === "items") {
      openItemEditor(null);
    } else if (state.manageTab === "assets") {
      openAssetEditor(null);
    } else if (state.manageTab === "subscriptions") {
      openSubscriptionEditor(null);
    } else if (state.manageTab === "icons") {
      requestIconUpload(null);
    }
  }

  function onManageContentClick(event) {
    var previewTrigger = event.target.closest("[data-preview-image]");
    if (previewTrigger && previewTrigger.getAttribute("data-preview-image")) {
      openMediaViewer(previewTrigger.getAttribute("data-preview-image") || "");
      return;
    }

    var button = event.target.closest("button[data-entity]");
    if (!button) {
      return;
    }
    var entity = button.getAttribute("data-entity");
    var action = button.getAttribute("data-action");
    var id = Number(button.getAttribute("data-id") || 0);

    if (entity === "account") {
      var account = state.accounts.find(function (item) { return Number(item.id) === id; });
      if (!account) return;
      if (action === "edit") openAccountEditor(account);
      if (action === "delete") deleteEntity("accounts/delete", id, "账户");
    } else if (entity === "category") {
      var category = state.categories.find(function (item) { return Number(item.id) === id; });
      if (!category) return;
      if (action === "edit") openCategoryEditor(category);
      if (action === "delete") deleteEntity("categories/delete", id, "分类");
    } else if (entity === "item") {
      var item = state.items.find(function (entry) { return Number(entry.id) === id; });
      if (!item) return;
      if (action === "edit") openItemEditor(item);
      if (action === "delete") deleteEntity("items/delete", id, "项目");
    } else if (entity === "asset") {
      var assets = state.assets.active.concat(state.assets.transferred);
      var asset = assets.find(function (entry) { return Number(entry.id) === id; });
      if (!asset) return;
      if (action === "edit") openAssetEditor(asset);
      if (action === "transfer") openAssetTransfer(asset);
      if (action === "delete") deleteEntity("assets/delete", id, "资产");
    } else if (entity === "subscription") {
      var subscription = state.subscriptions.find(function (entry) { return Number(entry.id) === id; });
      if (!subscription) return;
      if (action === "edit") openSubscriptionEditor(subscription);
      if (action === "renew") openSubscriptionRenew(subscription);
      if (action === "delete") deleteEntity("subscriptions/delete", id, "订阅");
    } else if (entity === "icon") {
      var icon = state.icons.find(function (entry) { return Number(entry.id) === id; });
      if (!icon) return;
      if (action === "edit") openIconRename(icon);
      if (action === "replace") requestIconUpload(icon);
      if (action === "delete") deleteEntity("icon-library/delete", id, "图标");
    }
  }

  function requestIconUpload(icon) {
    var name = icon && icon.name ? icon.name : "自定义图标";
    state.iconUploadContext = {
      id: icon ? Number(icon.id) : 0,
      name: String(name).trim() || "自定义图标"
    };
    els.iconUploadInput.click();
  }

  async function onIconUploadChange(event) {
    var file = event.target.files && event.target.files[0];
    var context = state.iconUploadContext;
    event.target.value = "";
    if (!file || !context) {
      state.iconUploadContext = null;
      return;
    }

    var form = new FormData();
    form.append("file", file);
    form.append("name", context.name || "自定义图标");
    if (context.id) {
      form.append("id", String(context.id));
    }

    setLoading(true);
    try {
      await uploadForm(context.id ? "icon-library/update-file" : "icon-library/upload", form);
      toast(context.id ? "图标已更新" : "图标已上传");
      await loadManageData();
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      state.iconUploadContext = null;
      setLoading(false);
    }
  }

  async function openIconRename(icon) {
    openEditorSheet("icon", {
      mode: "edit",
      id: icon.id,
      title: "修改图标名称",
      meta: "仅修改名称，不替换图片",
      nameLabel: "图标名称",
      name: icon.name || "",
    });
  }

  async function deleteEntity(route, id, label) {
    openConfirmSheet({
      title: "删除" + label,
      meta: "请确认是否继续",
      message: "确认删除这个" + label + "吗？",
      confirmText: "确认删除",
      action: async function () {
        await api(route, { method: "POST", data: { id: id } });
        toast(label + "已删除");
        await reloadAfterManageChange();
      }
    });
  }

  function openConfirmSheet(options) {
    state.confirmSheet = options || null;
    els.confirmSheetTitle.textContent = (options && options.title) || "确认操作";
    els.confirmSheetMeta.textContent = (options && options.meta) || "";
    els.confirmSheetMessage.textContent = (options && options.message) || "";
    els.submitConfirmSheetBtn.textContent = (options && options.confirmText) || "确认";
    els.confirmSheet.hidden = false;
    updateOverlayBodyState();
  }

  function closeConfirmSheet() {
    state.confirmSheet = null;
    els.confirmSheet.hidden = true;
    updateOverlayBodyState();
  }

  async function onConfirmSheetSubmit() {
    if (!state.confirmSheet || typeof state.confirmSheet.action !== "function") {
      closeConfirmSheet();
      return;
    }
    setLoading(true);
    try {
      await state.confirmSheet.action();
      closeConfirmSheet();
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      setLoading(false);
    }
  }

  async function openAccountEditor(account) {
    var current = account || {};
    openEditorSheet("account", {
      mode: current.id ? "edit" : "create",
      id: current.id || 0,
      title: current.id ? "编辑账户" : "新增账户",
      meta: current.id ? "修改账户名称和分组" : "创建一个新的记账账户",
      nameLabel: "账户名称",
      name: current.name || "",
      groupId: current.group_id || (state.accountGroups[0] && state.accountGroups[0].id) || "",
      accountNo: current.account_no || "",
      initialBalance: current.initial_balance || "0",
    });
  }

  async function openCategoryEditor(category) {
    var current = category || {};
    openEditorSheet("category", {
      mode: current.id ? "edit" : "create",
      id: current.id || 0,
      title: current.id ? "编辑分类" : "新增分类",
      meta: "支出、收入、转账分类都在这里维护",
      nameLabel: "分类名称",
      name: current.name || "",
      categoryType: current.type || "expense",
      sortOrder: current.sort_order || "0",
    });
  }

  async function openItemEditor(item) {
    var current = item || {};
    openEditorSheet("item", {
      mode: current.id ? "edit" : "create",
      id: current.id || 0,
      title: current.id ? "编辑项目" : "新增项目",
      meta: "项目会挂在某个分类下面",
      nameLabel: "项目名称",
      name: current.name || "",
      itemCategoryId: current.category_id || (state.categories[0] && state.categories[0].id) || "",
      sortOrder: current.sort_order || "0",
    });
  }

  function openEditorSheet(entity, options) {
    var opts = options || {};
    state.editorSheet = {
      entity: entity,
      id: Number(opts.id || 0),
      mode: opts.mode || "create"
    };

    els.editorSheetTitle.textContent = opts.title || "编辑";
    els.editorSheetMeta.textContent = opts.meta || "";
    els.editorNameLabel.textContent = opts.nameLabel || "名称";
    els.editorNameInput.value = opts.name || "";
    els.editorAccountNoInput.value = opts.accountNo || "";
    els.editorInitialBalanceInput.value = opts.initialBalance || "0";
    els.editorSortOrderInput.value = String(opts.sortOrder != null ? opts.sortOrder : 0);
    els.editorCategoryType.value = opts.categoryType || "expense";
    els.editorGoalStatus.value = opts.goalStatus || "active";
    els.editorBudgetType.value = opts.budgetType || "expense";
    els.editorSubscriptionType.value = opts.subscriptionType || "subscription";
    els.editorTargetAmountInput.value = opts.targetAmount || "";
    els.editorSavedAmountInput.value = opts.savedAmount || "0";
    els.editorBudgetAmountInput.value = opts.budgetAmount || "";
    els.editorAcquiredDateInput.value = opts.acquiredDate || "";
    els.editorDeadlineInput.value = opts.deadline || "";
    els.editorTransferDateInput.value = opts.transferDate || "";
    els.editorExpireDateInput.value = opts.expireDate || "";
    els.editorValueAmountInput.value = opts.valueAmount || "";
    els.editorTransferPriceInput.value = opts.transferPrice || "";
    els.editorSubscriptionPriceInput.value = opts.subscriptionPrice || "";
    els.editorSubscriptionAutoRenewInput.checked = !!opts.subscriptionAutoRenew;
    els.editorPeriodInput.value = opts.period || "";
    els.editorRemarkInput.value = opts.remark || "";

    els.editorGroupId.innerHTML = state.accountGroups.map(function (group) {
      return '<option value="' + escapeHtml(group.id) + '">' + escapeHtml(group.name) + '</option>';
    }).join("");
    if (opts.groupId != null && opts.groupId !== "") {
      els.editorGroupId.value = String(opts.groupId);
    }

    els.editorGoalAccountId.innerHTML = ['<option value="0">不关联账户</option>'].concat(state.accounts.map(function (account) {
      return '<option value="' + escapeHtml(account.id) + '">' + escapeHtml(account.name) + '</option>';
    })).join("");
    if (opts.goalAccountId != null && opts.goalAccountId !== "") {
      els.editorGoalAccountId.value = String(opts.goalAccountId);
    }

    els.editorItemCategoryId.innerHTML = state.categories.map(function (category) {
      return '<option value="' + escapeHtml(category.id) + '">' + escapeHtml(category.name) + '（' + escapeHtml(category.type) + '）</option>';
    }).join("");
    if (opts.itemCategoryId != null && opts.itemCategoryId !== "") {
      els.editorItemCategoryId.value = String(opts.itemCategoryId);
    }

    syncBudgetEditorOptions();
    if (opts.budgetCategoryId != null && opts.budgetCategoryId !== "") {
      els.editorBudgetCategoryId.value = String(opts.budgetCategoryId);
    }
    syncBudgetEditorItemOptions();
    if (opts.budgetItemId != null && opts.budgetItemId !== "") {
      els.editorBudgetItemId.value = String(opts.budgetItemId);
    }
    syncSubscriptionEditorFields();

    toggleEditorSheetFields(entity, state.editorSheet.mode === "create");
    els.submitEditorSheetBtn.textContent = state.editorSheet.mode === "edit" ? "保存修改" : "确认新增";
    els.editorSheet.hidden = false;
    updateOverlayBodyState();
    window.setTimeout(function () {
      els.editorNameInput.focus();
    }, 10);
  }

  function toggleEditorSheetFields(entity, isCreate) {
    els.editorNameField.hidden = entity === "asset-transfer";
    els.editorGroupField.hidden = entity !== "account";
    els.editorGoalAccountField.hidden = entity !== "goal";
    els.editorAccountNoField.hidden = entity !== "account";
    els.editorInitialBalanceField.hidden = !(entity === "account" && isCreate);
    els.editorCategoryTypeField.hidden = entity !== "category";
    els.editorGoalStatusField.hidden = entity !== "goal";
    els.editorBudgetTypeField.hidden = entity !== "budget";
    els.editorSubscriptionTypeField.hidden = !(entity === "subscription" || entity === "subscription-renew");
    els.editorSortOrderField.hidden = !(entity === "category" || entity === "item");
    els.editorItemCategoryField.hidden = entity !== "item";
    els.editorBudgetCategoryField.hidden = entity !== "budget";
    els.editorBudgetItemField.hidden = entity !== "budget";
    els.editorTargetAmountField.hidden = entity !== "goal";
    els.editorSavedAmountField.hidden = entity !== "goal";
    els.editorBudgetAmountField.hidden = entity !== "budget";
    els.editorAcquiredDateField.hidden = entity !== "asset";
    els.editorDeadlineField.hidden = entity !== "goal";
    els.editorTransferDateField.hidden = entity !== "asset-transfer";
    els.editorExpireDateField.hidden = !(entity === "subscription" || entity === "subscription-renew");
    els.editorValueAmountField.hidden = entity !== "asset";
    els.editorTransferPriceField.hidden = entity !== "asset-transfer";
    els.editorSubscriptionPriceField.hidden = !(entity === "subscription" || entity === "subscription-renew");
    els.editorSubscriptionAutoRenewField.hidden = !(entity === "subscription" || entity === "subscription-renew");
    els.editorPeriodField.hidden = !(entity === "subscription" || entity === "subscription-renew");
    els.editorRemarkField.hidden = !(entity === "asset" || entity === "subscription");
  }

  function syncBudgetEditorOptions() {
    var budgetType = els.editorBudgetType.value || "expense";
    var categories = [{ id: "", name: "全部分类" }].concat(state.categories.filter(function (category) {
      return category.type === budgetType;
    }));
    els.editorBudgetCategoryId.innerHTML = categories.map(function (category) {
      return '<option value="' + escapeHtml(category.id == null ? "" : category.id) + '">' + escapeHtml(category.name || "") + '</option>';
    }).join("");
    syncBudgetEditorItemOptions();
  }

  function syncBudgetEditorItemOptions() {
    var categoryId = Number(els.editorBudgetCategoryId.value || 0);
    var items = [{ id: "", name: "全部项目" }].concat(state.items.filter(function (item) {
      return !categoryId || Number(item.category_id) === categoryId;
    }));
    els.editorBudgetItemId.innerHTML = items.map(function (item) {
      return '<option value="' + escapeHtml(item.id == null ? "" : item.id) + '">' + escapeHtml(item.name || "") + '</option>';
    }).join("");
  }

  function syncSubscriptionEditorFields() {
    var isSubscription = (els.editorSubscriptionType.value || "subscription") === "subscription";
    els.editorExpireDateField.hidden = !(state.editorSheet.entity === "subscription" || state.editorSheet.entity === "subscription-renew") || !isSubscription;
    els.editorPeriodField.hidden = !(state.editorSheet.entity === "subscription" || state.editorSheet.entity === "subscription-renew") || !isSubscription;
  }

  function closeEditorSheet() {
    state.editorSheet = { entity: "", id: 0, mode: "create" };
    els.editorSheetForm.reset();
    els.editorSheet.hidden = true;
    updateOverlayBodyState();
  }

  async function onEditorSheetSubmit(event) {
    event.preventDefault();
    var entity = state.editorSheet.entity;
    if (!entity) {
      return;
    }

    var name = els.editorNameInput.value.trim();
    if (entity !== "asset-transfer" && !name) {
      toast("名称不能为空");
      return;
    }

    setLoading(true);
    try {
      if (entity === "account") {
        var groupId = Number(els.editorGroupId.value || 0);
        if (!groupId) {
          toast("请选择账户分组");
          setLoading(false);
          return;
        }
        var accountPayload = {
          id: state.editorSheet.id || 0,
          group_id: groupId,
          name: name,
          account_no: els.editorAccountNoInput.value.trim()
        };
        if (state.editorSheet.mode === "create") {
          accountPayload.initial_balance = Number(els.editorInitialBalanceInput.value || 0);
        }
        await api(state.editorSheet.mode === "edit" ? "accounts/update" : "accounts/create", { method: "POST", data: accountPayload });
        toast(state.editorSheet.mode === "edit" ? "账户已更新" : "账户已创建");
        closeEditorSheet();
        await reloadAfterManageChange();
      } else if (entity === "category") {
        await api(state.editorSheet.mode === "edit" ? "categories/update" : "categories/create", {
          method: "POST",
          data: {
            id: state.editorSheet.id || 0,
            type: els.editorCategoryType.value || "expense",
            name: name,
            sort_order: Number(els.editorSortOrderInput.value || 0)
          }
        });
        toast(state.editorSheet.mode === "edit" ? "分类已更新" : "分类已创建");
        closeEditorSheet();
        await reloadAfterManageChange();
      } else if (entity === "item") {
        var categoryId = Number(els.editorItemCategoryId.value || 0);
        if (!categoryId) {
          toast("请选择所属分类");
          setLoading(false);
          return;
        }
        await api(state.editorSheet.mode === "edit" ? "items/update" : "items/create", {
          method: "POST",
          data: {
            id: state.editorSheet.id || 0,
            category_id: categoryId,
            name: name,
            sort_order: Number(els.editorSortOrderInput.value || 0)
          }
        });
        toast(state.editorSheet.mode === "edit" ? "项目已更新" : "项目已创建");
        closeEditorSheet();
        await reloadAfterManageChange();
      } else if (entity === "icon") {
        await api("icon-library/update", { method: "POST", data: { id: state.editorSheet.id, name: name } });
        if (state.iconUploadContext && Number(state.iconUploadContext.id || 0) === Number(state.editorSheet.id || 0)) {
          state.iconUploadContext.name = name;
        }
        toast("图标名称已更新");
        closeEditorSheet();
        await loadManageData();
      } else if (entity === "goal") {
        await api("goals/save", {
          method: "POST",
          data: {
            id: state.editorSheet.id || 0,
            title: name,
            account_id: Number(els.editorGoalAccountId.value || 0),
            target_amount: Number(els.editorTargetAmountInput.value || 0),
            saved_amount: Number(els.editorSavedAmountInput.value || 0),
            deadline: els.editorDeadlineInput.value || "",
            status: els.editorGoalStatus.value || "active"
          }
        });
        toast(state.editorSheet.mode === "edit" ? "目标已更新" : "目标已创建");
        closeEditorSheet();
        await Promise.all([loadPlans(), loadHome()]);
      } else if (entity === "budget") {
        var budgetAmount = Number(els.editorBudgetAmountInput.value || 0);
        if (state.editorSheet.mode === "edit") {
          await api("budget/update-amount", { method: "POST", data: { id: state.editorSheet.id, amount: budgetAmount } });
        } else {
          await api("budget/upsert", {
            method: "POST",
            data: {
              year: state.budgetYear,
              month: state.budgetMonth,
              type: els.editorBudgetType.value || "expense",
              category_id: els.editorBudgetCategoryId.value ? Number(els.editorBudgetCategoryId.value) : null,
              item_id: els.editorBudgetItemId.value ? Number(els.editorBudgetItemId.value) : null,
              amount: budgetAmount
            }
          });
        }
        toast(state.editorSheet.mode === "edit" ? "预算已更新" : "预算已创建");
        closeEditorSheet();
        await Promise.all([loadPlans(), loadHome()]);
      } else if (entity === "asset") {
        await api("assets/save", {
          method: "POST",
          data: {
            id: state.editorSheet.id || 0,
            name: name,
            acquired_date: els.editorAcquiredDateInput.value || "",
            value_amount: Number(els.editorValueAmountInput.value || 0),
            remark: els.editorRemarkInput.value.trim()
          }
        });
        toast(state.editorSheet.mode === "edit" ? "资产已更新" : "资产已创建");
        closeEditorSheet();
        await reloadAfterManageChange();
      } else if (entity === "asset-transfer") {
        await api("assets/transfer", {
          method: "POST",
          data: {
            id: state.editorSheet.id,
            transfer_date: els.editorTransferDateInput.value || "",
            transfer_price: Number(els.editorTransferPriceInput.value || 0)
          }
        });
        toast("已标记为转手资产");
        closeEditorSheet();
        await reloadAfterManageChange();
      } else if (entity === "subscription") {
        var subType = els.editorSubscriptionType.value || "subscription";
        await api("subscriptions/save", {
          method: "POST",
          data: {
            id: state.editorSheet.id || 0,
            platform: name,
            type: subType,
            price: Number(els.editorSubscriptionPriceInput.value || 0),
            expire_date: subType === "subscription" ? (els.editorExpireDateInput.value || "") : "",
            auto_renew: els.editorSubscriptionAutoRenewInput.checked ? 1 : 0,
            period: subType === "subscription" ? els.editorPeriodInput.value.trim() : "",
            remark: els.editorRemarkInput.value.trim()
          }
        });
        toast(state.editorSheet.mode === "edit" ? "订阅已更新" : "订阅已创建");
        closeEditorSheet();
        await reloadAfterManageChange();
      } else if (entity === "subscription-renew") {
        var renewType = els.editorSubscriptionType.value || "subscription";
        await api("subscriptions/renew", {
          method: "POST",
          data: {
            id: state.editorSheet.id,
            type: renewType,
            price: Number(els.editorSubscriptionPriceInput.value || 0),
            expire_date: renewType === "subscription" ? (els.editorExpireDateInput.value || "") : "",
            auto_renew: els.editorSubscriptionAutoRenewInput.checked ? 1 : 0,
            period: renewType === "subscription" ? els.editorPeriodInput.value.trim() : ""
          }
        });
        toast("订阅已续费");
        closeEditorSheet();
        await reloadAfterManageChange();
      }
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      setLoading(false);
    }
  }

  async function openAssetEditor(asset) {
    var current = asset || {};
    openEditorSheet("asset", {
      mode: current.id ? "edit" : "create",
      id: current.id || 0,
      title: current.id ? "编辑资产" : "新增资产",
      meta: "记录购入时间、价值和备注",
      nameLabel: "资产名称",
      name: current.name || "",
      acquiredDate: formatDate(current.acquired_date) || formatDate(new Date().toISOString()),
      valueAmount: current.value_amount || "",
      remark: current.remark || "",
    });
  }

  async function openAssetTransfer(asset) {
    openEditorSheet("asset-transfer", {
      mode: "edit",
      id: asset.id,
      title: "标记转手",
      meta: asset.name || "设置转手日期和价格",
      transferDate: formatDate(new Date().toISOString()),
      transferPrice: asset.transfer_price || asset.value_amount || "0",
    });
  }

  async function openSubscriptionEditor(subscription) {
    var current = subscription || {};
    openEditorSheet("subscription", {
      mode: current.id ? "edit" : "create",
      id: current.id || 0,
      title: current.id ? "编辑订阅" : "新增订阅",
      meta: "平台、价格、到期与续费设置",
      nameLabel: "平台名称",
      name: current.platform || "",
      subscriptionType: current.type || "subscription",
      subscriptionPrice: current.price || "",
      expireDate: formatDate(current.expire_date) || "",
      subscriptionAutoRenew: !!current.auto_renew,
      period: current.period || "",
      remark: current.remark || "",
    });
  }

  async function openSubscriptionRenew(subscription) {
    openEditorSheet("subscription-renew", {
      mode: "edit",
      id: subscription.id,
      title: "订阅续费",
      meta: subscription.platform || "更新续费金额与到期日",
      nameLabel: "平台名称",
      name: subscription.platform || "",
      subscriptionType: subscription.type || "subscription",
      subscriptionPrice: subscription.price || "",
      expireDate: formatDate(subscription.expire_date) || "",
      subscriptionAutoRenew: !!subscription.auto_renew,
      period: subscription.period || "",
      remark: subscription.remark || "",
    });
  }

  async function reloadAfterManageChange() {
    await Promise.all([loadMetaData(), loadManageData(), loadPlans(), loadTransactions(), loadHome(), loadReport(), loadSettingsData()]);
  }

  async function loadReport() {
    try {
      var mode = els.reportMode.value;
      var summary = await api("reports/summary", {
        method: "GET",
        data: {
          mode: mode,
          year: Number(els.reportYear.value || 0),
          month: Number(els.reportMonth.value || 0),
          date_from: mode === "custom" ? els.reportDateFrom.value : "",
          date_to: mode === "custom" ? els.reportDateTo.value : ""
        }
      });
      state.reportSummary = summary;
      state.reportCategoryStats = await buildReportCategoryStats(summary.dateFrom, summary.dateTo);
      renderReport();
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  async function buildReportCategoryStats(dateFrom, dateTo) {
    if (!dateFrom || !dateTo) {
      return [];
    }
    var res = await api("transactions/list", {
      method: "GET",
      data: {
        date_from: dateFrom,
        date_to: dateTo,
        type: state.reportCategoryType,
        page: 1,
        page_size: 100
      }
    });
    var map = {};
    var total = 0;
    (res.transactions || []).forEach(function (tx) {
      var key = tx.category_name || "未分类";
      var amount = Number(tx.amount || 0);
      map[key] = (map[key] || 0) + amount;
      total += amount;
    });
    return Object.keys(map).map(function (name) {
      return {
        name: name,
        amount: map[name],
        percent: total > 0 ? map[name] / total * 100 : 0
      };
    }).sort(function (left, right) {
      return right.amount - left.amount;
    });
  }

  function renderReport() {
    if (!state.reportSummary) {
      return;
    }
    var summary = state.reportSummary;
    var totalIncome = Number(summary.totalIncome || 0);
    var totalExpense = Number(summary.totalExpense || 0);
    var totalNet = totalIncome - totalExpense;
    var cards = [
      { label: "收入", value: formatAmount(totalIncome) },
      { label: "支出", value: formatAmount(totalExpense) },
      { label: "结余", value: formatAmount(totalNet) },
      { label: "总笔数", value: String(summary.totalCount || 0) }
    ];
    els.reportSummary.innerHTML = cards.map(function (card) {
      return '<div class="metric-card"><div class="metric-label">' + escapeHtml(card.label) + '</div><div class="metric-value">' + escapeHtml(card.value) + '</div></div>';
    }).join("");
    els.reportPeriodTitle.textContent = [summary.dateFrom || "", summary.dateTo || ""].filter(Boolean).join(" 至 ");

    var labels = summary.labels || [];
    var incomeData = summary.incomeData || [];
    var expenseData = summary.expenseData || [];
    var maxValue = Math.max(1, Math.max.apply(null, incomeData.concat(expenseData, [0])));
    els.reportTrend.innerHTML = labels.length ? labels.map(function (label, index) {
      var income = Number(incomeData[index] || 0);
      var expense = Number(expenseData[index] || 0);
      return ''
        + '<div class="bar-card">'
        + '<div class="list-head"><div class="list-title">' + escapeHtml(label) + '</div><div class="list-sub">收入 ' + escapeHtml(formatAmount(income)) + ' / 支出 ' + escapeHtml(formatAmount(expense)) + '</div></div>'
        + '<div class="bar-track"><div class="bar-fill income" style="width:' + Math.max(4, income / maxValue * 100) + '%"></div></div>'
        + '<div class="bar-track"><div class="bar-fill" style="width:' + Math.max(4, expense / maxValue * 100) + '%"></div></div>'
        + '</div>';
    }).join("") : '<div class="empty-state">当前区间暂无趋势数据</div>';

    els.reportCategoryStats.innerHTML = state.reportCategoryStats.length ? state.reportCategoryStats.map(function (item) {
      return ''
        + '<div class="list-item">'
        + '<div class="list-head"><div class="list-title">' + escapeHtml(item.name) + '</div><div class="list-amount">' + escapeHtml(formatAmount(item.amount)) + '</div></div>'
        + '<div class="list-sub">占比 ' + escapeHtml(item.percent.toFixed(1)) + '%</div>'
        + '<div class="bar-track"><div class="bar-fill" style="width:' + Math.max(4, item.percent) + '%"></div></div>'
        + '</div>';
    }).join("") : '<div class="empty-state">当前区间暂无分类统计</div>';
  }

  async function loadSettingsData() {
    try {
      var res = await api("user/stats", { method: "GET" });
      state.stats = res.stats || null;
      renderSettings();
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  async function loadFeedbackData() {
    try {
      var res = await api("feedback/list", { method: "GET", data: { limit: 50 } });
      state.feedbacks = res.feedbacks || [];
      renderFeedbacks();
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  function renderFeedbacks() {
    var categoryMap = {
      suggest: "建议",
      bug: "问题",
      other: "其他"
    };
    els.feedbackList.innerHTML = state.feedbacks.length ? state.feedbacks.map(function (item) {
      var reply = item.admin_reply
        ? '<div class="feedback-reply">回复：' + escapeHtml(item.admin_reply) + '</div>'
        : '';
      var images = item.images && item.images.length
        ? '<div class="feedback-gallery">' + item.images.map(function (image) {
          return '<button type="button" class="feedback-image" data-preview-image="' + escapeHtml(image.url) + '"><img class="feedback-thumb" src="' + escapeHtml(image.url) + '" alt="反馈图片"></button>';
        }).join("") + '</div>'
        : '';
      return ''
        + '<div class="list-item">'
        + '<div class="list-head"><div><div class="list-title">' + escapeHtml(categoryMap[item.category] || item.category || "反馈") + '</div><div class="list-sub">' + escapeHtml(item.user_nickname || "匿名用户") + ' · ' + escapeHtml(formatDateTime(item.created_at)) + '</div></div><div>' + statusBadge(item.status || "open", item.status === "replied" || item.admin_reply ? "safe" : "") + '</div></div>'
        + '<div class="list-sub">' + escapeHtml(item.content || "") + '</div>'
        + reply
        + images
        + '</div>';
    }).join("") : '<div class="empty-state">暂时还没有反馈记录</div>';
  }

  function onFeedbackListClick(event) {
    var trigger = event.target.closest("[data-preview-image]");
    if (!trigger) {
      return;
    }
    openMediaViewer(trigger.getAttribute("data-preview-image") || "");
  }

  async function onFeedbackSubmit(event) {
    event.preventDefault();
    var content = els.feedbackContent.value.trim();
    if (!content) {
      toast("请填写反馈内容");
      return;
    }

    setLoading(true);
    try {
      if (state.feedbackImages.length) {
        var form = new FormData();
        form.append("category", els.feedbackCategory.value || "suggest");
        form.append("content", content);
        state.feedbackImages.forEach(function (file) {
          form.append("images[]", file, file.name || "feedback-image");
        });
        await uploadForm("feedback/create", form);
      } else {
        await api("feedback/create", {
          method: "POST",
          data: {
            category: els.feedbackCategory.value || "suggest",
            content: content
          }
        });
      }
      els.feedbackContent.value = "";
      els.feedbackCategory.value = "suggest";
      clearFeedbackImages();
      toast("反馈已提交");
      await loadFeedbackData();
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      setLoading(false);
    }
  }

  function onFeedbackImagesChange(event) {
    var files = Array.prototype.slice.call(event.target.files || []).filter(function (file) {
      return file && /^image\//i.test(file.type || "");
    });
    if (!files.length) {
      event.target.value = "";
      return;
    }

    var nextImages = state.feedbackImages.concat(files);
    if (nextImages.length > 6) {
      toast("反馈截图最多上传 6 张");
    }
    releaseFeedbackPreviewUrls();
    state.feedbackImages = nextImages.slice(0, 6);
    event.target.value = "";
    renderFeedbackImageSelection();
  }

  function clearFeedbackImages() {
    releaseFeedbackPreviewUrls();
    state.feedbackImages = [];
    if (els.feedbackImagesInput) {
      els.feedbackImagesInput.value = "";
    }
    renderFeedbackImageSelection();
  }

  function renderFeedbackImageSelection() {
    if (!els.feedbackImageList) {
      return;
    }
    releaseFeedbackPreviewUrls();
    els.clearFeedbackImagesBtn.hidden = state.feedbackImages.length === 0;
    els.feedbackImageList.innerHTML = state.feedbackImages.length ? state.feedbackImages.map(function (file, index) {
      var sizeText = file.size ? (Math.max(1, Math.round(file.size / 1024)) + " KB") : "图片";
      var previewUrl = URL.createObjectURL(file);
      state.feedbackImagePreviewUrls.push(previewUrl);
      return ''
        + '<div class="list-item compact-item">'
        + '<div class="feedback-selected-row"><img class="feedback-selected-thumb" src="' + escapeHtml(previewUrl) + '" alt="已选图片"><div class="feedback-selected-meta"><div class="list-title">' + escapeHtml(file.name || ("图片 " + (index + 1))) + '</div><div class="list-sub">' + escapeHtml(sizeText) + '</div></div><button type="button" class="mini-btn danger" data-feedback-image-index="' + escapeHtml(index) + '">移除</button></div>'
        + '</div>';
    }).join("") : '<div class="empty-state">可选上传截图，最多 6 张</div>';

    Array.prototype.slice.call(els.feedbackImageList.querySelectorAll("button[data-feedback-image-index]")).forEach(function (button) {
      button.addEventListener("click", function () {
        var index = Number(button.getAttribute("data-feedback-image-index") || -1);
        if (index < 0) {
          return;
        }
        state.feedbackImages.splice(index, 1);
        renderFeedbackImageSelection();
      });
    });
  }

  function releaseFeedbackPreviewUrls() {
    state.feedbackImagePreviewUrls.forEach(function (url) {
      try {
        URL.revokeObjectURL(url);
      } catch (error) {
        return null;
      }
    });
    state.feedbackImagePreviewUrls = [];
  }

  async function loadChangelogData() {
    try {
      var res = await api("changelog/list", { method: "GET" });
      state.changelog = {
        appVersion: res.app_version || "",
        pcVersion: res.pc_version || "",
        entries: res.entries || []
      };
      state.changelogExpandedIndex = 0;
      renderChangelog();
    } catch (error) {
      handleAuthFailure(error);
    }
  }

  function renderChangelog() {
    var changelog = state.changelog || { entries: [] };
    populateChangelogFilter();
    var selectedVersion = els.changelogFilter.value || "all";
    var filteredEntries = (changelog.entries || []).filter(function (entry) {
      return selectedVersion === "all" || (entry.version || entry.title || "") === selectedVersion;
    });
    els.changelogVersionMeta.textContent = [
      changelog.appVersion ? ("手机版 " + changelog.appVersion) : "",
      changelog.pcVersion ? ("桌面版 " + changelog.pcVersion) : ""
    ].filter(Boolean).join(" · ");
    els.changelogList.innerHTML = filteredEntries.length ? filteredEntries.map(function (entry, index) {
      var expanded = index === state.changelogExpandedIndex;
      return ''
        + '<div class="list-item changelog-card' + (expanded ? ' expanded' : '') + '">'
        + '<button type="button" class="changelog-toggle" data-changelog-index="' + escapeHtml(index) + '"><span class="list-title">' + escapeHtml(entry.title || entry.version || "版本更新") + '</span><span class="changelog-arrow">' + (expanded ? '收起' : '展开') + '</span></button>'
        + '<div class="changelog-items"' + (expanded ? '' : ' hidden') + '>' + (entry.items || []).map(function (item) {
          return '<div class="list-sub">• ' + escapeHtml(item) + '</div>';
        }).join("") + '</div>'
        + '</div>';
    }).join("") : '<div class="empty-state">暂时没有可展示的更新日志</div>';
  }

  function populateChangelogFilter() {
    if (!els.changelogFilter) {
      return;
    }
    var currentValue = els.changelogFilter.value || "all";
    var options = ["<option value=\"all\">全部版本</option>"];
    (state.changelog.entries || []).forEach(function (entry) {
      var value = entry.version || entry.title || "";
      if (!value) {
        return;
      }
      options.push('<option value="' + escapeHtml(value) + '">' + escapeHtml(value) + '</option>');
    });
    els.changelogFilter.innerHTML = options.join("");
    if (Array.prototype.some.call(els.changelogFilter.options, function (option) { return option.value === currentValue; })) {
      els.changelogFilter.value = currentValue;
    }
  }

  function onChangelogListClick(event) {
    var button = event.target.closest("button[data-changelog-index]");
    if (!button) {
      return;
    }
    var index = Number(button.getAttribute("data-changelog-index") || -1);
    if (index < 0) {
      return;
    }
    state.changelogExpandedIndex = state.changelogExpandedIndex === index ? -1 : index;
    renderChangelog();
  }

  function openMediaViewer(url) {
    if (!url || !els.mediaViewerImage || !els.mediaViewer) {
      return;
    }
    els.mediaViewerImage.src = url;
    els.mediaViewer.hidden = false;
    updateOverlayBodyState();
  }

  function closeMediaViewer() {
    if (!els.mediaViewer || !els.mediaViewerImage) {
      return;
    }
    els.mediaViewer.hidden = true;
    els.mediaViewerImage.removeAttribute("src");
    updateOverlayBodyState();
  }

  function closeAllOverlays() {
    state.confirmSheet = null;
    state.editorSheet = { entity: "", id: 0, mode: "create" };
    state.profileFieldSheet = { field: "", passwordFlow: false };
    if (els.editorSheetForm) {
      els.editorSheetForm.reset();
    }
    if (els.confirmSheet) {
      els.confirmSheet.hidden = true;
    }
    if (els.homeAssetSheet) {
      els.homeAssetSheet.hidden = true;
    }
    if (els.profileFieldSheet) {
      els.profileFieldSheet.hidden = true;
    }
    if (els.editorSheet) {
      els.editorSheet.hidden = true;
    }
    if (els.mediaViewer && els.mediaViewerImage) {
      els.mediaViewer.hidden = true;
      els.mediaViewerImage.removeAttribute("src");
    }
    updateOverlayBodyState();
  }

  function updateOverlayBodyState() {
    var hasEditor = !!(els.editorSheet && !els.editorSheet.hidden);
    var hasConfirm = !!(els.confirmSheet && !els.confirmSheet.hidden);
    var hasHomeAsset = !!(els.homeAssetSheet && !els.homeAssetSheet.hidden);
    var hasProfileField = !!(els.profileFieldSheet && !els.profileFieldSheet.hidden);
    var hasMediaViewer = !!(els.mediaViewer && !els.mediaViewer.hidden);
    document.body.classList.toggle("editor-sheet-open", hasEditor || hasConfirm || hasHomeAsset || hasProfileField);
    document.body.classList.toggle("media-viewer-open", hasMediaViewer);
  }

  function onHomeAssetClick(event) {
    var button = event.target.closest("[data-home-asset]");
    if (!button) {
      return;
    }
    openHomeAssetSheet(button.getAttribute("data-home-asset") || "");
  }

  function openHomeAssetSheet(assetKey) {
    var detail = buildHomeAssetDetail(assetKey);
    if (!detail || !els.homeAssetSheet) {
      return;
    }
    els.homeAssetSheetTitle.textContent = detail.title;
    els.homeAssetSheetMeta.textContent = detail.meta;
    els.homeAssetSheetSummary.innerHTML = '<div class="summary-tile compact-summary"><span>合计</span><strong>' + escapeHtml(formatAmount(detail.total)) + '</strong></div>';
    els.homeAssetSheetList.innerHTML = detail.accounts.length
      ? detail.accounts.map(function (account) {
        var amountClass = detail.isDebt ? 'list-amount danger-text' : 'list-amount';
        var itemClass = detail.isDebt ? 'list-item compact-item asset-sheet-item danger' : 'list-item compact-item asset-sheet-item';
        var amountText = detail.isDebt ? formatDebtAmount(account.current_balance) : formatAmount(account.current_balance);
        return '<div class="' + itemClass + '"><div class="list-head"><div><div class="list-title">' + escapeHtml(account.name) + '</div><div class="list-sub">' + escapeHtml(account.group_name || "未分组") + (account.account_no ? (' · ' + escapeHtml(account.account_no)) : '') + '</div></div><div class="' + amountClass + '">' + escapeHtml(amountText) + '</div></div></div>';
      }).join("")
      : '<div class="empty-state">当前没有对应账户</div>';
    els.homeAssetSheet.hidden = false;
    updateOverlayBodyState();
  }

  function closeHomeAssetSheet() {
    if (!els.homeAssetSheet) {
      return;
    }
    els.homeAssetSheet.hidden = true;
    els.homeAssetSheetTitle.textContent = "资产明细";
    els.homeAssetSheetMeta.textContent = "";
    els.homeAssetSheetSummary.innerHTML = "";
    els.homeAssetSheetList.innerHTML = "";
    updateOverlayBodyState();
  }

  function buildHomeAssetDetail(assetKey) {
    var accounts = state.accounts || [];
    var map = {
      total_assets: { title: "总资产明细", meta: "资金、储蓄、应收款和其他账户", codes: ["financial", "saving", "receivable", "other"] },
      net_assets: { title: "净资产明细", meta: "全部账户余额合计", codes: ["financial", "saving", "receivable", "debt", "other"] },
      total_debt: { title: "负债明细", meta: "当前负债账户余额", codes: ["debt"] },
      financial: { title: "资金账户", meta: "现金、银行卡等流动资金", codes: ["financial"] },
      saving: { title: "储蓄账户", meta: "定存或长期储蓄账户", codes: ["saving"] },
      receivable: { title: "应收款", meta: "待收回的款项", codes: ["receivable"] },
      other: { title: "其他资产", meta: "未归类到上面类型的账户", codes: ["other"] }
    };
    var config = map[assetKey];
    if (!config) {
      return null;
    }
    var filtered = accounts.filter(function (account) {
      return config.codes.indexOf(String(account.group_code || "")) >= 0;
    });
    return {
      title: config.title,
      meta: config.meta,
      isDebt: assetKey === "total_debt",
      total: filtered.reduce(function (sum, account) { return sum + Number(account.current_balance || 0); }, 0),
      accounts: filtered
    };
  }

  function renderSettings() {
    if (!state.user) {
      return;
    }
    var user = state.user;
    var displayName = user.nickname || user.username || "用户";
    els.settingsNickname.textContent = displayName;
    els.settingsMeta.textContent = [user.username || "", user.email || "未设置邮箱"].filter(Boolean).join(" · ");
    els.settingsUsernameValue.textContent = user.username || "未设置";
    els.settingsNicknameValue.textContent = user.nickname || "未设置";
    els.settingsEmailValue.textContent = user.email || "未设置";
    els.settingsAvatar.textContent = displayName.slice(0, 1).toUpperCase();
    els.settingsAvatar.style.backgroundImage = user.avatar_url ? 'url("' + String(user.avatar_url) + '")' : "";
    els.settingsAvatar.style.color = user.avatar_url ? "transparent" : "var(--brand-deep)";
    els.editUsernameBtn.textContent = user.username ? "修改" : "设置";
    els.editNicknameBtn.textContent = user.nickname ? "修改" : "设置";
    els.editEmailBtn.textContent = user.email ? "修改" : "设置";
    els.budgetReminderToggle.checked = user.budget_reminder_enabled !== false;
    els.transferToggle.checked = !!user.enable_transfer;
    els.negativeBalanceToggle.checked = !!user.allow_negative_balance;
    renderHeader();
    populateLedgerOptions();

    var stats = state.stats || { register_days: 0, book_days: 0, streak_days: 0, transaction_count: 0 };
    els.settingsStats.innerHTML = [
      { label: "注册天数", value: String(stats.register_days || 0) },
      { label: "记账天数", value: String(stats.book_days || 0) },
      { label: "连续记账", value: String(stats.streak_days || 0) },
      { label: "总笔数", value: String(stats.transaction_count || 0) }
    ].map(function (card) {
      return '<div class="metric-card settings-stat-card"><div class="metric-label">' + escapeHtml(card.label) + '</div><div class="metric-value">' + escapeHtml(card.value) + '</div></div>';
    }).join("");
  }

  function populateLedgerOptions() {
    var ledgers = state.ledgers && state.ledgers.length ? state.ledgers : [{ id: 0, name: "个人账本" }];
    els.ledgerSelect.innerHTML = ledgers.map(function (ledger) {
      var selected = Number(ledger.id) === Number(state.activeLedgerId) ? ' selected' : '';
      return '<option value="' + escapeHtml(ledger.id) + '"' + selected + '>' + escapeHtml(ledger.name) + '</option>';
    }).join("");
  }

  async function onSaveUsername(username) {
    if (!username) {
      toast("用户名不能为空");
      return false;
    }
    var ok = await updateProfileRoute("settings/update-username", { username: username }, "用户名已更新");
    if (ok) {
      await loadSettingsData();
    }
    return ok;
  }

  async function onSaveNickname(nickname) {
    if (!nickname) {
      toast("昵称不能为空");
      return false;
    }
    var ok = await updateProfileRoute("settings/update-nickname-from-wechat", { nickname: nickname }, "昵称已更新");
    if (ok) {
      await loadSettingsData();
    }
    return ok;
  }

  async function onSaveEmail(email) {
    if (!email) {
      toast("邮箱不能为空");
      return false;
    }
    setLoading(true);
    try {
      var res = await api("settings/change-email", { method: "POST", data: { email: email } });
      state.user.email = res.email || email;
      persistSession();
      renderSettings();
      toast("邮箱已更新");
      await loadSettingsData();
      return true;
    } catch (error) {
      handleAuthFailure(error);
      return false;
    } finally {
      setLoading(false);
    }
  }

  async function onSavePassword(password, confirm) {
    if (!password || !confirm) {
      toast("请输入并确认新密码");
      return false;
    }
    setLoading(true);
    try {
      await api("settings/set-password", { method: "POST", data: { password: password, confirm: confirm } });
      closeProfileFieldSheet();
      try {
        await api("auth/logout", { method: "POST", data: {} });
      } catch (error) {
        // ignore and clear local state anyway
      }
      clearSession();
      showLogin();
      navigate("login", true);
      toast("密码已更新，已自动退出，请重新登录");
      return true;
    } catch (error) {
      handleAuthFailure(error);
      return false;
    } finally {
      setLoading(false);
    }
  }

  function openProfileFieldSheet(field) {
    state.profileFieldSheet = { field: field, passwordFlow: field === "password" };
    els.profileFieldSecondaryWrap.hidden = field !== "password";
    els.profileFieldPrimaryInput.value = "";
    els.profileFieldSecondaryInput.value = "";

    if (field === "username") {
      els.profileFieldSheetTitle.textContent = "修改用户名";
      els.profileFieldSheetMeta.textContent = "保存后会刷新当前资料信息";
      els.profileFieldPrimaryLabel.textContent = "用户名";
      els.profileFieldPrimaryInput.type = "text";
      els.profileFieldPrimaryInput.placeholder = "请输入新的用户名";
      els.profileFieldPrimaryInput.value = (state.user && state.user.username) || "";
      els.submitProfileFieldSheetBtn.textContent = "保存用户名";
    } else if (field === "nickname") {
      els.profileFieldSheetTitle.textContent = "修改昵称";
      els.profileFieldSheetMeta.textContent = "保存后会刷新当前资料信息";
      els.profileFieldPrimaryLabel.textContent = "昵称";
      els.profileFieldPrimaryInput.type = "text";
      els.profileFieldPrimaryInput.placeholder = "请输入新的昵称";
      els.profileFieldPrimaryInput.value = (state.user && state.user.nickname) || "";
      els.submitProfileFieldSheetBtn.textContent = "保存昵称";
    } else if (field === "email") {
      els.profileFieldSheetTitle.textContent = "修改邮箱";
      els.profileFieldSheetMeta.textContent = "保存后会刷新当前资料信息";
      els.profileFieldPrimaryLabel.textContent = "邮箱";
      els.profileFieldPrimaryInput.type = "email";
      els.profileFieldPrimaryInput.placeholder = "请输入新的邮箱";
      els.profileFieldPrimaryInput.value = (state.user && state.user.email) || "";
      els.submitProfileFieldSheetBtn.textContent = "保存邮箱";
    } else if (field === "password") {
      els.profileFieldSheetTitle.textContent = "修改密码";
      els.profileFieldSheetMeta.textContent = "修改成功后会自动退出，需要重新登录";
      els.profileFieldPrimaryLabel.textContent = "新密码";
      els.profileFieldPrimaryInput.type = "password";
      els.profileFieldPrimaryInput.placeholder = "至少 6 位";
      els.profileFieldSecondaryLabel.textContent = "确认密码";
      els.profileFieldSecondaryInput.type = "password";
      els.profileFieldSecondaryInput.placeholder = "再次输入新密码";
      els.submitProfileFieldSheetBtn.textContent = "更新密码";
    }

    els.profileFieldSheet.hidden = false;
    updateOverlayBodyState();
    window.setTimeout(function () {
      els.profileFieldPrimaryInput.focus();
    }, 10);
  }

  function closeProfileFieldSheet() {
    state.profileFieldSheet = { field: "", passwordFlow: false };
    els.profileFieldSheetForm.reset();
    els.profileFieldSheet.hidden = true;
    updateOverlayBodyState();
  }

  async function onProfileFieldSheetSubmit(event) {
    event.preventDefault();
    var field = state.profileFieldSheet.field;
    var primary = els.profileFieldPrimaryInput.value.trim();
    var secondary = els.profileFieldSecondaryInput.value;
    if (!field) {
      return;
    }
    if (field === "username") {
      if (await onSaveUsername(primary)) {
        closeProfileFieldSheet();
      }
    } else if (field === "nickname") {
      if (await onSaveNickname(primary)) {
        closeProfileFieldSheet();
      }
    } else if (field === "email") {
      if (await onSaveEmail(primary)) {
        closeProfileFieldSheet();
      }
    } else if (field === "password") {
      await onSavePassword(primary, secondary);
    }
  }

  async function updateProfileRoute(route, data, successText) {
    setLoading(true);
    try {
      var res = await api(route, { method: "POST", data: data });
      if (res.user) {
        state.user = res.user;
        persistSession();
        renderSettings();
      }
      toast(successText);
      return true;
    } catch (error) {
      handleAuthFailure(error);
      return false;
    } finally {
      setLoading(false);
    }
  }

  async function onLedgerChange() {
    var ledgerId = Number(els.ledgerSelect.value || 0);
    if (ledgerId === Number(state.activeLedgerId)) {
      return;
    }
    setLoading(true);
    try {
      var res = await api("ledgers/set-active", { method: "POST", data: { ledger_id: ledgerId } });
      state.activeLedgerId = Number(res.active_ledger_id || ledgerId);
      state.activeLedgerName = res.active_ledger && res.active_ledger.name ? res.active_ledger.name : state.activeLedgerName;
      renderHeader();
      toast("账本已切换");
      await reloadAllData();
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      setLoading(false);
    }
  }

  async function updateToggle(route, enabled, field) {
    setLoading(true);
    try {
      var res = await api(route, { method: "POST", data: { enabled: enabled ? 1 : 0 } });
      if (Object.prototype.hasOwnProperty.call(res, field)) {
        state.user[field] = res[field];
      } else {
        state.user[field] = enabled;
      }
      persistSession();
      renderSettings();
      refreshTransactionFormOptions();
      toast("设置已更新");
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      setLoading(false);
    }
  }

  async function onAvatarChange(event) {
    var file = event.target.files && event.target.files[0];
    if (!file) {
      return;
    }
    setLoading(true);
    try {
      var res = await uploadFile("settings/upload-avatar", "avatar", file);
      if (res.user) {
        state.user = res.user;
        persistSession();
        renderSettings();
      }
      toast("头像已更新");
    } catch (error) {
      handleAuthFailure(error);
    } finally {
      event.target.value = "";
      setLoading(false);
    }
  }

  async function onLogout() {
    setLoading(true);
    try {
      await api("auth/logout", { method: "POST", data: {} });
    } catch (error) {
      // ignore and clear local state anyway
    }
    clearSession();
    showLogin();
    navigate("login", true);
    setLoading(false);
    toast("已退出登录");
  }
})();