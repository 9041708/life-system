// utils/request.ts
const BASE_URL = "https://your-domain.com/public/api.php";

interface RequestOptions {
  route: string;
  method?: "GET" | "POST";
  data?: any;
}

export function request(options: RequestOptions): Promise<any> {
  const { route, method = "POST", data = {} } = options;
  const url = `${BASE_URL}?route=${encodeURIComponent(route)}`;

  const token = wx.getStorageSync("token");

  return new Promise((resolve, reject) => {
    wx.request({
      url,
      method,
      data,
      header: {
        "content-type": "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      success(res) {
        const { statusCode, data } = res as WechatMiniprogram.RequestSuccessCallbackResult;

        // 统一处理登录过期/被禁用等需要重新登录的情况
        if (statusCode === 401 || statusCode === 403) {
          try {
            wx.removeStorageSync("token");
            wx.removeStorageSync("user");

            let alreadyShown = false;
            try {
              const app = getApp<IAppOption>();
              // @ts-ignore
              alreadyShown = !!app.globalData.authModalShown;
              // @ts-ignore
              app.globalData.authModalShown = true;
            } catch (e) {}

            if (!alreadyShown) {
              wx.showModal({
                title: "登录已过期",
                content: "请重新登录后继续使用。",
                showCancel: false,
                success: () => {
                  try {
                    const app = getApp<IAppOption>();
                    // @ts-ignore
                    app.globalData.authModalShown = false;
                  } catch (e) {}
                  wx.reLaunch({ url: "/pages/index/index" });
                },
              } as any);
            }
          } catch (e) {}

          // 仍然将后端返回的数据交给调用方，便于按需处理提示文本
          resolve(data);
          return;
        }

        resolve(data);
      },
      fail(err) {
        reject(err);
      },
    });
  });
}