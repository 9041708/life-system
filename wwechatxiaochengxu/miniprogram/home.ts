// home.ts
const { initShare, buildShareAppMessage, buildShareTimeline } = require('./utils/share');

Page({
  data: {
    hasUpdate: false,
    updateReady: false,
  },

  async onShow() {
    try {
      const app = getApp<IAppOption>();
      this.setData({
        hasUpdate: !!app.globalData.hasUpdate,
        updateReady: !!app.globalData.updateReady,
      });
    } catch (e) {}

    // 初始化分享配置（首页）
    try {
      await initShare(this, {
        title: '三石记账 · 轻松理清收支',
        path: '/pages/index/index',
      });
    } catch (e) {}
  },

  onApplyUpdate() {
    try {
      const updateManager = wx.getUpdateManager();
      updateManager.applyUpdate();
    } catch (e) {
      wx.showToast({ title: '请关闭后重新进入', icon: 'none' });
    }
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
})
