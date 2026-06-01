// pages/bind/bind.js
const { request } = require("../../utils/request");

Page({
  data: {
    account: "",
    password: "",
    loading: false
  },

  onAccountInput(e) {
    this.setData({ account: e.detail.value });
  },

  onPasswordInput(e) {
    this.setData({ password: e.detail.value });
  },

  async onBind() {
    if (!this.data.account || !this.data.password) {
      wx.showToast({ title: "请填写账号和密码", icon: "none" });
      return;
    }
    const loginRes = await wx.login();
    if (!loginRes.code) {
      wx.showToast({ title: "微信登录失败", icon: "none" });
      return;
    }
    this.setData({ loading: true });
    try {
      const res = await request({
        route: "wechat/bind-by-password",
        method: "POST",
        data: {
          code: loginRes.code,
          account: this.data.account,
          password: this.data.password
        }
      });
      if (!res.success) {
        wx.showToast({ title: res.error || "绑定失败", icon: "none" });
        return;
      }
      wx.setStorageSync("token", res.token);
      wx.setStorageSync("user", res.user);
      try { wx.removeStorageSync("last_login_code"); } catch (e) {}
      wx.reLaunch({ url: "/pages/home/home" });
    } catch (e) {
      wx.showToast({ title: "网络错误", icon: "none" });
    } finally {
      this.setData({ loading: false });
    }
  }
});