// pages/home/home.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

function formatAmount(raw: any): string {
  const n = Number(raw || 0);
  return n.toFixed(2);
}

Page({
  data: {
    user: null as any,
    isLoggedIn: false,
    loading: false,

    ledgerMode: false,
    currentLedgerName: '个人账本',
    currentLedgerRole: '',

    // 用户级预算提醒开关（由后端返回）
    budgetReminderEnabled: true,

    assets: {
      financial: "0.00",
      saving: "0.00",
      receivable: "0.00",
      debt: "0.00",
      other: "0.00",
      totalAssets: "0.00",
      totalDebt: "0.00",
      netAssets: "0.00",
    },

    today: {
      income: "0.00",
      expense: "0.00",
      net: "0.00",
    },

    month: {
      year: 0,
      month: 0,
      label: "",
      income: "0.00",
      expense: "0.00",
      net: "0.00",
      budgetTotal: "0.00",
      budgetUsed: "0.00",
      budgetRemain: "0.00",
      budgetTotalNumber: 0,
      budgetRatePercent: 0,
      budgetOver: false,
      budgetStatus: "none", // none | warn | over
    },

    // 三个月内需要续费的订阅
    upcomingRenewals: [] as any[],

    recentTransactions: [] as any[],
    theme: 'light' as 'light' | 'dark',
  },

  async onLoad() {
    this.syncThemeFromGlobal();
    const token = wx.getStorageSync('token') || '';
    const user = wx.getStorageSync("user") || null;
    const isLoggedIn = !!token;
    this.setData({ user, isLoggedIn });

    // 未登录时也展示"框架"，不拉真实数据
    if (!isLoggedIn) {
      const now = new Date();
      const y = now.getFullYear();
      const m = now.getMonth() + 1;
      const label = y + '-' + (m < 10 ? '0' + m : String(m));
      this.setData({
        budgetReminderEnabled: true,
        ledgerMode: false,
        currentLedgerName: '未登录',
        currentLedgerRole: '',
        assets: {
          financial: '--',
          saving: '--',
          receivable: '--',
          debt: '--',
          other: '--',
          totalAssets: '--',
          totalDebt: '--',
          netAssets: '--',
        },
        today: {
          income: '--',
          expense: '--',
          net: '--',
        },
        month: {
          ...this.data.month,
          year: y,
          month: m,
          label,
          income: '--',
          expense: '--',
          net: '--',
          budgetTotal: '--',
          budgetUsed: '--',
          budgetRemain: '--',
          budgetTotalNumber: 0,
          budgetRatePercent: 0,
          budgetOver: false,
          budgetStatus: 'none',
        },
        recentTransactions: [],
      });
    }

    // 首次登录且资料不完整时，引导前往设置中心完善头像和昵称
    try {
      const hintShown = wx.getStorageSync("profile_hint_shown");
      const needProfile =
        !user.avatar_url ||
        !user.nickname ||
        user.nickname === "微信用户";
      if (!hintShown && needProfile) {
        wx.showModal({
          title: "完善个人资料",
          content: "建议前往设置中心同步微信头像和昵称，体验更完整。",
          confirmText: "去设置",
          cancelText: "稍后",
          success: (res) => {
            wx.setStorageSync("profile_hint_shown", 1);
            if (res.confirm) {
              wx.navigateTo({ url: "/pages/settings/index" });
            }
          },
        });
      }
    } catch (e) {}

    // 开启分享菜单并初始化签名分享
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: "三石记账 · 概览",
        path: "/pages/home/home",
      });
    } catch (e) {}

    if (this.data.isLoggedIn) {
      await this.ensureUserLoaded();
      this.loadOverview();
      this.loadLedgerInfo();
    }
  },

  onShow() {
    const token = wx.getStorageSync('token') || '';
    const user = wx.getStorageSync('user') || null;
    const isLoggedIn = !!token;
    this.setData({ isLoggedIn, user });

    if (isLoggedIn) {
      this.ensureUserLoaded().then(() => {
        this.loadOverview();
        this.loadLedgerInfo();
      });
    }
    this.syncThemeFromGlobal();
  },

  onAddTransaction() {
    this.requireLoginAndNavigate('/pages/transaction/create');
  },

  onViewList() {
    this.requireLoginAndNavigate('/pages/transaction/list');
  },

  onViewAccounts() {
    this.requireLoginAndNavigate('/pages/accounts/list');
  },

  onViewBudget() {
    this.requireLoginAndNavigate('/pages/budget/list');
  },

  onViewGoals() {
    this.requireLoginAndNavigate('/pages/goals/list');
  },

  onViewReport() {
    this.requireLoginAndNavigate('/pages/report/index');
  },

  onViewSettings() {
    wx.navigateTo({ url: '/pages/settings/index' });
  },

  onViewCategories() {
    this.requireLoginAndNavigate('/pages/categories/list');
  },

  onViewItems() {
    this.requireLoginAndNavigate('/pages/items/list');
  },

  onSwitchLedger() {
    // 账本切换入口放在设置中心
    this.requireLoginAndNavigate('/pages/settings/index');
  },

  onViewSubscriptions() {
    this.requireLoginAndNavigate('/pages/subscriptions/list');
  },

  requireLoginAndNavigate(url: string) {
    const token = wx.getStorageSync('token') || '';
    if (token) {
      wx.navigateTo({ url });
      return;
    }
    const redirect = encodeURIComponent(url);
    wx.showModal({
      title: '需要登录',
      content: '该功能需要登录后使用。是否现在登录？',
      confirmText: '去登录',
      cancelText: '取消',
      success: (res) => {
        if (res.confirm) {
          wx.navigateTo({ url: `/pages/index/index?redirect=${redirect}` });
        }
      },
    });
  },

  async ensureUserLoaded() {
    if (this.data.user) return;
    try {
      const res: any = await request({ route: 'auth/profile' });
      if (res && res.success && res.user) {
        this.setData({ user: res.user });
        wx.setStorageSync('user', res.user);
      }
    } catch (e) {}
  },

  async loadLedgerInfo() {
    try {
      const res: any = await request({ route: 'ledgers/list', method: 'GET' });
      if (!res || !res.success) return;
      const active = res.active_ledger || null;
      this.setData({
        ledgerMode: !!res.ledger_mode,
        currentLedgerName: (active && active.name) ? String(active.name) : '个人账本',
        currentLedgerRole: (active && active.member_role) ? String(active.member_role) : '',
      });
      if (active && active.name) {
        wx.setStorageSync('active_ledger', active);
      }
    } catch (e) {}
  },

  syncThemeFromGlobal() {
    try {
      const app = getApp<IAppOption>();
      // @ts-ignore
      const globalTheme = app.globalData && app.globalData.theme;
      const storedTheme = wx.getStorageSync('theme');
      let theme: 'light' | 'dark' = 'light';
      if (storedTheme === 'dark' || storedTheme === 'light') {
        theme = storedTheme;
      } else if (globalTheme === 'dark' || globalTheme === 'light') {
        theme = globalTheme;
      }
      this.setData({ theme });
    } catch (e) {}
  },

  async loadOverview() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: "home/overview",
      });

      if (!res || !res.success) {
        wx.showToast({
          title: (res && res.error) || "加载失败",
          icon: "none",
        });
        return;
      }

      const assets = res.assets || {};
      const today = res.today || {};
      const month = res.month || {};
      const recent = res.recent_transactions || [];
      const announcement = res.announcement || null;

      // 用户级预算提醒开关（后端没有给时默认开启）
      const budgetReminderEnabled =
        res.budget_reminder_enabled !== false;

      const now = new Date();
      const monthYear = Number(month.year || now.getFullYear());
      const monthMonth = Number(month.month || now.getMonth() + 1);
      const monthLabel =
        monthYear +
        "-" +
        (monthMonth < 10 ? "0" + monthMonth : String(monthMonth));

      const budgetTotalNum = Number(month.budget_total || 0);
      const budgetUsedNum = Number(month.budget_used || 0);
      const budgetRemainNum = Math.max(
        0,
        Number(month.budget_remain || budgetTotalNum - budgetUsedNum)
      );
      const rate =
        budgetTotalNum > 0 ? (budgetUsedNum / budgetTotalNum) * 100 : 0;
      const ratePercent = Math.min(999, Math.round(rate));

      let budgetStatus: "none" | "warn" | "over" = "none";
      if (budgetReminderEnabled && budgetTotalNum > 0) {
        if (budgetUsedNum > budgetTotalNum) {
          budgetStatus = "over";
        } else if (ratePercent >= 80) {
          budgetStatus = "warn";
        }
      }

      const recentTransactions = (recent || []).map((t: any) => {
        return {
          id: t.id,
          type: t.type,
          amount: formatAmount(t.amount),
          category_name: t.category_name || "",
          item_name: t.item_name || "",
          from_account_name: t.from_account_name || "",
          to_account_name: t.to_account_name || "",
          trans_time: t.trans_time ? t.trans_time.slice(5, 16) : "",
        };
      });

      this.setData({
        budgetReminderEnabled,

        assets: {
          financial: formatAmount(assets.financial),
          saving: formatAmount(assets.saving),
          receivable: formatAmount(assets.receivable),
          debt: formatAmount(assets.debt),
          other: formatAmount(assets.other),
          totalAssets: formatAmount(assets.total_assets),
          totalDebt: formatAmount(assets.total_debt),
          netAssets: formatAmount(assets.net_assets),
        },
        today: {
          income: formatAmount(today.income),
          expense: formatAmount(today.expense),
          net: formatAmount(today.net),
        },
        month: {
          year: monthYear,
          month: monthMonth,
          label: monthLabel,
          income: formatAmount(month.income),
          expense: formatAmount(month.expense),
          net: formatAmount(month.net),
          budgetTotal: formatAmount(budgetTotalNum),
          budgetUsed: formatAmount(budgetUsedNum),
          budgetRemain: formatAmount(budgetRemainNum),
          budgetTotalNumber: budgetTotalNum,
          budgetRatePercent: ratePercent,
          budgetOver: !!month.budget_over,
          budgetStatus,
        },
        recentTransactions,
      });

      // 首页公告弹窗：有未读公告时进行一次提示，关闭后标记已读
      if (announcement && announcement.id) {
        try {
          const title: string = announcement.title || "系统公告";
          const content: string = (announcement.content || "").replace(/\r?\n/g, "\n");
          await new Promise<void>((resolve) => {
            wx.showModal({
              title,
              content,
              showCancel: false,
              confirmText: "知道了",
              success: () => resolve(),
              fail: () => resolve(),
            } as any);
          });
        } catch (e) {}

        // 弹窗关闭即视为已查看
        try {
          await request({
            route: "announcement/mark-read",
            method: "POST",
            data: { announcement_id: announcement.id },
          });
        } catch (e) {
          // 统计失败不影响正常使用
        }
      }
    } catch (e) {
      console.error(e);
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }

    // 同时加载三个月内需要续费的订阅
    this.loadUpcomingRenewals();
  },

  async loadUpcomingRenewals() {
    try {
      const res: any = await request({
        route: "subscriptions/list",
        method: "GET",
        data: {},
      });
      if (!res || !res.success) return;

      const list: any[] = res.subscriptions || [];
      // 过滤出三个月内（90天内）需要续费的订阅
      const upcoming = list.filter((s: any) => {
        if (s.type !== 'subscription') return false;
        const d = typeof s.days_left === 'number' ? s.days_left : null;
        return d !== null && d >= 0 && d <= 90;
      });

      // 按剩余天数升序（最紧急的排前面）
      upcoming.sort((a: any, b: any) => (a.days_left || 0) - (b.days_left || 0));

      const mapped = upcoming.slice(0, 10).map((s: any) => {
        const d = typeof s.days_left === 'number' ? s.days_left : 0;
        let daysLabel = '';
        let expireLevel = '';
        if (d === 0) {
          daysLabel = '今天到期';
          expireLevel = 'danger';
        } else if (d <= 7) {
          daysLabel = '还剩 ' + d + ' 天';
          expireLevel = 'warn';
        } else {
          daysLabel = '还剩 ' + d + ' 天';
          expireLevel = '';
        }
        return {
          id: s.id,
          platform: s.platform || '',
          priceDisplay: formatAmount(s.price),
          expire_date: s.expire_date || '',
          daysLabel,
          expireLevel,
          auto_renew: !!s.auto_renew,
        };
      });

      this.setData({ upcomingRenewals: mapped });
    } catch (e) {
      // 静默失败，不打扰主流程
    }
  },

  onPullDownRefresh() {
    if (!this.data.isLoggedIn) {
      wx.stopPullDownRefresh();
      return;
    }
    this.loadOverview();
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});