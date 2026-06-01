import { request } from "../../utils/request";

Component({
  data: {
    loading: false,
    theme: 'light' as 'light' | 'dark',
    showPrivacy: false,
    privacyContractName: '',
    localPrivacyAgreed: false,
  },

  lifetimes: {
    attached() {
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

      // 本地记录的隐私同意状态（用于保证首次必弹）
      let localPrivacyAgreed = false;
      try {
        const flag = wx.getStorageSync('local_privacy_agreed');
        localPrivacyAgreed = !!flag;
      } catch (e) {}
      this.setData({ localPrivacyAgreed });

      // 查询微信侧隐私授权状态，并决定是否弹出隐私协议
      try {
        if (wx.getPrivacySetting) {
          wx.getPrivacySetting({
            success: (res: any) => {
              const needAuth = !!(res && res.needAuthorization);
              const name = (res && res.privacyContractName) || '《隐私保护指引》';
              if (needAuth || !localPrivacyAgreed) {
                this.setData({
                  showPrivacy: true,
                  privacyContractName: name,
                });
              } else {
                this.setData({ privacyContractName: name });
              }
            },
          });
        } else {
          // 旧基础库：若本地未同意，则仍然弹出自定义隐私提示
          if (!localPrivacyAgreed) {
            this.setData({ showPrivacy: true });
          }
        }
      } catch (e) {
        // 若调用失败，则仅依赖本地标记控制
        if (!localPrivacyAgreed) {
          this.setData({ showPrivacy: true });
        }
      }
    },
  },

  methods: {
    async onWeChatLogin() {
      if (this.data.loading) return;
      // 如仍存在待同意的隐私协议（微信侧或本地），引导用户先在隐私弹窗中同意
      if (this.data.showPrivacy || !this.data.localPrivacyAgreed) {
        this.setData({ showPrivacy: true });
        wx.showToast({ title: '请先阅读并同意隐私协议', icon: 'none' });
        return;
      }

      this.setData({ loading: true });

      try {
        const loginRes = await wx.login();
        if (!loginRes.code) {
          wx.showToast({ title: "微信登录失败", icon: "none" });
          return;
        }
        // 尝试获取用户昵称（需用户同意），获取失败则不携带
        let nickname = "";
        let avatarUrl = "";
        try {
          const profile: any = await wx.getUserProfile({ desc: "用于完善资料中的昵称和头像" });
          if (profile && profile.userInfo && profile.userInfo.nickName) {
            nickname = profile.userInfo.nickName;
          }
          if (profile && profile.userInfo && profile.userInfo.avatarUrl) {
            avatarUrl = profile.userInfo.avatarUrl;
          }
        } catch (e) {
          // 用户拒绝授权或不支持，忽略昵称
        }

        const res: any = await request({
          route: "wechat/auto-login",
          data: { code: loginRes.code, nickname, avatar_url: avatarUrl },
        });

        if (!res || !res.success) {
          wx.showToast({ title: (res && res.error) || "登录失败", icon: "none" });
          return;
        }

        wx.setStorageSync("token", res.token);
        wx.setStorageSync("user", res.user);

        // 若为小程序自动注册用户且邮箱为空或占位邮箱，优先引导填写邮箱
        try {
          const user = res.user || {};
          const email: string = (user && user.email) || "";
          const isPlaceholder = !!email && typeof email === "string" && email.endsWith("@miniapp.local");
          if (!email || isPlaceholder) {
            const modalRes: any = await wx.showModal({
              title: "完善邮箱",
              content:
                "为了接收重要公告和维护通知，建议先在“设置-邮箱”中填写一个可用邮箱。是否现在前往设置？",
              confirmText: "去填写",
              cancelText: "稍后再说",
            } as any);
            if (modalRes && modalRes.confirm) {
              wx.reLaunch({ url: "/pages/settings/index" });
              return;
            }
          }
        } catch (e) {
          // 引导失败不影响正常跳转
        }

        // 支持从其它页面引导登录后回跳
        let redirectUrl = "";
        try {
          const pages: any = getCurrentPages();
          const current = pages && pages.length ? pages[pages.length - 1] : null;
          const opt = (current && current.options) || {};
          if (opt && opt.redirect) {
            redirectUrl = decodeURIComponent(String(opt.redirect));
          }
        } catch (e) {}

        if (redirectUrl && redirectUrl.startsWith("/pages/")) {
          wx.reLaunch({ url: redirectUrl });
        } else {
          wx.reLaunch({ url: "/pages/home/home" });
        }
      } catch (e) {
        console.error(e);
        wx.showToast({ title: "网络异常", icon: "none" });
      } finally {
        this.setData({ loading: false });
      }
    },

    handleOpenPrivacyContract() {
      try {
        if (wx.openPrivacyContract) {
          wx.openPrivacyContract({});
        }
      } catch (e) {}
    },

    handleAgreePrivacyAuthorization() {
      // 用户通过官方 agreePrivacyAuthorization 按钮同意隐私协议
      this.setData({ showPrivacy: false, localPrivacyAgreed: true });
      try {
        wx.setStorageSync('local_privacy_agreed', 1);
      } catch (e) {}
    },

    handleDisagreePrivacy() {
      // 用户明确选择不同意，保持遮罩或给出提示
      wx.showToast({ title: '未同意隐私协议，无法继续使用登录功能', icon: 'none' });
    },
  },
});