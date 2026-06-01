import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

Page({
  data: {
    account: "",
    password: "",
    loading: false,
    passwordHidden: true,
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: "三石记账 · 账号绑定",
        path: "/pages/bind/bind",
      });
    } catch (e) {}
  },

  onAccountInput(e: any) {
    this.setData({ account: e.detail.value });
  },

  onPasswordInput(e: any) {
    this.setData({ password: e.detail.value });
  },

  onTogglePassword() {
    this.setData({ passwordHidden: !this.data.passwordHidden });
  },

  async onBind() {
    if (this.data.loading) return;

    const { account, password } = this.data;
    if (!account || !password) {
      wx.showToast({ title: "请输入账号和密码", icon: "none" });
      return;
    }

    this.setData({ loading: true });

    try {
      // 这里重新获取一次新的 code
      const loginRes = await wx.login();
      if (!loginRes.code) {
        wx.showToast({ title: "微信登录失败", icon: "none" });
        return;
      }

      const res: any = await request({
        route: "wechat/bind-by-password",
        data: {
          code: loginRes.code,
          account,
          password,
        },
      });

      if (!res.success) {
        wx.showToast({ title: res.error || "绑定失败", icon: "none" });
        return;
      }

      wx.setStorageSync("token", res.token);
      wx.setStorageSync("user", res.user);

      wx.showToast({ title: "绑定成功", icon: "success" });
      setTimeout(() => {
        wx.reLaunch({ url: "/pages/home/home" });
      }, 500);
    } catch (e) {
      console.error(e);
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      this.setData({ loading: false });
    }
  },

  onGoRegister() {
    wx.navigateTo({ url: "/pages/register/register" });
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});