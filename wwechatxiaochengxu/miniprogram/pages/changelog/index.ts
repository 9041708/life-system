// pages/changelog/index.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

Page({
  data: {
    loading: false,
    appVersion: "",
    entries: [] as Array<{ version: string; title: string; items: string[] }>,
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: "三石记账 · 更新日志",
        path: "/pages/changelog/index",
      });
    } catch (e) {}

    this.loadChangelog();
  },

  async loadChangelog() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: "changelog/list",   // ✅ 用 route，而不是 url
        method: "GET",
        data: {},
      });

      if (!res || !res.success) {
        wx.showToast({
          title: (res && res.error) || "加载失败",
          icon: "none",
        });
        return;
      }

      this.setData({
        appVersion: res.app_version || "",
        entries: res.entries || [],
      });
    } catch (e) {
      console.error(e);
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      this.setData({ loading: false });
    }
  },
  
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});