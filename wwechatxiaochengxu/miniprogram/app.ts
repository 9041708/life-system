// app.ts
import { request } from "./utils/request";

App<IAppOption>({
  globalData: {
    hasUpdate: false,
    updateReady: false,
    authModalShown: false,
    theme: 'light' as 'light' | 'dark',
  },
  onLaunch() {
    // 展示本地存储能力
    const logs = wx.getStorageSync('logs') || []
    logs.unshift(Date.now())
    wx.setStorageSync('logs', logs)

    // 自动检测并应用新版本
    try {
      const updateManager = wx.getUpdateManager()
      updateManager.onCheckForUpdate(res => {
        // 有新版本时，后续会触发 onUpdateReady
        if (res.hasUpdate) {
          try { getApp().globalData.hasUpdate = true; } catch (e) {}
          wx.showToast({ title: '检测到新版本', icon: 'none' })
        }
      })
      updateManager.onUpdateReady(() => {
        try { getApp().globalData.updateReady = true; } catch (e) {}
        wx.showModal({
          title: '更新就绪',
          content: '有新版本可用，是否立即重启应用以更新？',
          confirmText: '重启更新',
          success: (r) => {
            if (r.confirm) {
              updateManager.applyUpdate()
            } else {
              wx.showToast({ title: '稍后可在设置检查更新', icon: 'none' })
            }
          }
        })
      })
      updateManager.onUpdateFailed(() => {
        wx.showModal({
          title: '更新失败',
          content: '请关闭后重新进入，或在设置页手动检查更新。',
          showCancel: false
        })
      })
    } catch (e) {
      // 低版本基础库不支持 UpdateManager
      console.warn('UpdateManager unavailable', e)
    }

    // 全局开启分享（朋友 + 朋友圈），并附带 shareTicket
    try {
      wx.showShareMenu({ withShareTicket: true, menus: ['shareAppMessage', 'shareTimeline'] })
    } catch (e) {}

    // 读取本地主题设置（默认 light）
    try {
      const storedTheme = wx.getStorageSync('theme');
      const theme: 'light' | 'dark' = storedTheme === 'dark' ? 'dark' : 'light';
      // @ts-ignore
      this.globalData.theme = theme;
    } catch (e) {}

    // 尝试从本地恢复登录状态，并静默校验 token 是否仍然有效
    try {
      const token = wx.getStorageSync('token') || '';
      const user = wx.getStorageSync('user') || null;
      try {
        const app = getApp<IAppOption>();
        // 恢复到全局，便于后续页面读取
        // @ts-ignore
        app.globalData.token = token;
        // @ts-ignore
        app.globalData.user = user;
      } catch (e) {}

      if (token) {
        // 轻量调用用户信息接口做一次静默校验
        request({ route: 'auth/profile', method: 'GET' })
          .then((res: any) => {
            if (res && res.success && res.user) {
              wx.setStorageSync('user', res.user);
              try {
                const app = getApp<IAppOption>();
                // @ts-ignore
                app.globalData.user = res.user;
              } catch (e) {}
            }
          })
          .catch(() => {
            // 网络异常时保持现有本地登录状态，由各页面自行处理
          });
      }
    } catch (e) {}
  },
})