/// <reference path="./types/index.d.ts" />

interface IAppOption {
  globalData: {
    userInfo?: WechatMiniprogram.UserInfo;
    /** 小程序是否检测到有新版本 */
    hasUpdate?: boolean;
    /** 新版本已下载就绪 */
    updateReady?: boolean;
    /** 是否已经弹出过“登录过期”提示，避免重复弹窗 */
    authModalShown?: boolean;
  };
  userInfoReadyCallback?: WechatMiniprogram.GetUserInfoSuccessCallback;
}