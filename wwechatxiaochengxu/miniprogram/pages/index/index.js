// pages/index/index.js
const { request } = require("../../utils/request");

Page({
  data: {
    loading: false,
  },

  onWeChatLogin() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    wx.login({
      success: async (res) => {
        if (!res.code) {
          wx.showToast({ title: "微信登录失败", icon: "none" });
          this.setData({ loading: false });
          return;
        }

        // 尝试获取用户昵称（需用户同意），获取失败则不携带
        let nickname = "";
        try {
          const profile = await wx.getUserProfile({ desc: "用于完善资料中的昵称" });
          if (profile && profile.userInfo && profile.userInfo.nickName) {
            nickname = profile.userInfo.nickName;
          }
        } catch (e) {
          // 用户拒绝授权或不支持，忽略昵称
        }

        try {
          const data = await request({
            route: "wechat/auto-login",
            method: "POST",
            data: { code: res.code, nickname },
          });

          console.log("wechat/auto-login response =>", data);

          if (!data || !data.success) {
            const msg =
              (data && (data.error || data.message)) || "登录失败";
            wx.showToast({ title: msg, icon: "none" });
            return;
          }

          wx.setStorageSync("token", data.token);
          wx.setStorageSync("user", data.user);
          wx.reLaunch({ url: "/pages/home/home" });
        } catch (e) {
          console.error(e);
          wx.showToast({ title: "网络错误", icon: "none" });
        } finally {
          this.setData({ loading: false });
        }
      },
      fail: () => {
        this.setData({ loading: false });
        wx.showToast({ title: "微信登录失败", icon: "none" });
      },
    });
  },
});