// miniprogram/pages/accounts/list.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

Page({
  data: {
    accounts: [] as any[],
    loading: false,
    totalBalance: 0, // 全部账户余额合计
    theme: 'light' as 'light' | 'dark',
  },

  async onLoad() {
    this.syncThemeFromGlobal();
    this.reload();

    // 初始化分享配置：账户列表
    try {
      await initShare(this, {
        title: '三石记账 · 账户列表',
        path: '/pages/accounts/list',
      });
    } catch (e) {}
  },

  onShow() {
    // 从"新增账户"返回时刷新一下
    this.syncThemeFromGlobal();
    this.reload();
  },

  onPullDownRefresh() {
    this.reload();
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

  async reload() {
    this.setData({
      accounts: [],
      totalBalance: 0,
    });
    await this.loadAccounts();
  },

  async loadAccounts() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: "accounts/list",
        method: "GET",
      });

      if (!res || !res.success) {
        wx.showToast({
          title: (res && res.error) || "加载失败",
          icon: "none",
        });
        return;
      }

      // 后端可能返回 res.accounts 或 res.list，这里都兼容一下
      const list: any[] = res.accounts || res.list || [];

      let total = 0;
      for (let i = 0; i < list.length; i++) {
        const a = list[i];
        const bal = Number(a.current_balance || 0);
        const abs = Math.abs(bal);

        // 预先算好展示用的余额字符串和正负
        a._positive = bal >= 0;
        a._balanceDisplay = (bal >= 0 ? "" : "-") + abs.toFixed(2);

        total += bal;
      }

      this.setData({
        accounts: list,
        totalBalance: Number(total.toFixed(2)),
      });
    } catch (e) {
      console.error(e);
      wx.showToast({ title: "请求失败", icon: "none" });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  // 管理子菜单：账户列表 / 分类管理 / 项目管理
  onNavAccounts() {
    // 当前即为账户列表，无需跳转
  },

  onNavCategories() {
    wx.navigateTo({ url: '/pages/categories/list' });
  },

  onNavItems() {
    wx.navigateTo({ url: '/pages/items/list' });
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },

  onAddAccount() {
    wx.navigateTo({
      url: "/pages/accounts/create",
    });
  },
});