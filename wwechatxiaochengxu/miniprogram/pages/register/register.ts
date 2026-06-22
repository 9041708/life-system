import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

Page({
  data: {
    username: "",
    nickname: "",
    email: "",
    password: "",
    passwordConfirm: "",
    loading: false,
    passwordHidden: true,
    passwordConfirmHidden: true,
    avatarTempPath: "",
    avatarPreview: "",
    defaultAvatar: "",
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: "三石记账 · 注册帐号",
        path: "/pages/register/register",
      });
    } catch (e) {}
  },

  onChooseAvatar(e: any) {
    const avatarUrl = (e && e.detail && e.detail.avatarUrl) || "";
    if (!avatarUrl) return;
    this.setData({
      avatarTempPath: avatarUrl,
      avatarPreview: avatarUrl,
    });
  },

  onUsernameInput(e: any) {
    this.setData({ username: e.detail.value });
  },

  onNicknameInput(e: any) {
    this.setData({ nickname: e.detail.value });
  },

  onEmailInput(e: any) {
    this.setData({ email: e.detail.value });
  },

  onPasswordInput(e: any) {
    this.setData({ password: e.detail.value });
  },

  onPasswordConfirmInput(e: any) {
    this.setData({ passwordConfirm: e.detail.value });
  },

  onTogglePassword() {
    this.setData({ passwordHidden: !this.data.passwordHidden });
  },

  onTogglePasswordConfirm() {
    this.setData({ passwordConfirmHidden: !this.data.passwordConfirmHidden });
  },

  async onSubmit() {
    if (this.data.loading) return;

    const { username, nickname, email, password, passwordConfirm } = this.data;

    if (!username || !nickname || !email || !password || !passwordConfirm) {
      wx.showToast({ title: "请完整填写信息", icon: "none" });
      return;
    }

    if (password !== passwordConfirm) {
      wx.showToast({ title: "两次密码不一致", icon: "none" });
      return;
    }

    const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailReg.test(email)) {
      wx.showToast({ title: "邮箱格式不正确", icon: "none" });
      return;
    }

    this.setData({ loading: true });

    try {
      const loginRes = await wx.login();
      if (!loginRes.code) {
        wx.showToast({ title: "微信登录失败", icon: "none" });
        return;
      }

      // 注册时优先尝试获取微信头像和昵称，用于同步资料
      let finalNickname = nickname;
      let avatarUrl = "";
      // 这里仅保留旧逻辑，以后如需兼容 getUserProfile 可再调整

      const res: any = await request({
        route: "wechat/register-bind",
        data: {
          code: loginRes.code,
          username,
          nickname: finalNickname,
          email,
          password,
          // avatar_url 不再依赖微信默认头像，这里仅保留字段兼容，实际头像在注册成功后通过上传接口保存
          avatar_url: "",
        },
      });

      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "注册失败", icon: "none" });
        return;
      }

      wx.setStorageSync("token", res.token);
      wx.setStorageSync("user", res.user);

      // 如果注册时选择了头像，则在注册成功后上传该头像
      const avatarTempPath = this.data.avatarTempPath;
      if (avatarTempPath) {
        try {
          wx.showLoading({ title: "正在上传头像…", mask: true });
          const token = wx.getStorageSync("token") || "";
          const uploadUrl = "https://your-domain.com/public/api.php?route=settings/upload-avatar";
          console.log("upload avatar after register =>", uploadUrl);
          await new Promise((resolve, reject) => {
            wx.uploadFile({
              url: uploadUrl,
              filePath: avatarTempPath,
              name: "avatar",
              timeout: 15000,
              header: token ? { Authorization: "Bearer " + token } : {},
              success: (uploadRes) => {
                try {
                  const data = JSON.parse(uploadRes.data || "{}");
                  if (data && data.success && data.user) {
                    wx.setStorageSync("user", data.user);
                  }
                  resolve(null);
                } catch (err) {
                  reject(err);
                }
              },
              fail: (err) => {
                reject(err);
              },
              complete: () => {
                wx.hideLoading();
              },
            });
          });
        } catch (e) {
          console.error("upload avatar after register error", e);
        }
      }

      wx.showToast({ title: "注册成功", icon: "success" });
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

  onGoBind() {
    wx.navigateBack();
  },
  
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});