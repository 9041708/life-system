const BASE_URL = "https://9041708.cn:555/public/api.php";

function request(options) {
  const { route, method = "POST", data = {} } = options;
  return new Promise((resolve, reject) => {
    const url = `${BASE_URL}?route=${encodeURIComponent(route)}`;
    console.log("request url =>", url);
    let token = "";
    try { token = wx.getStorageSync("token") || ""; } catch (_) {}
    wx.request({
      url,
      method,
      data,
      header: {
        "content-type": "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      success(res) {
        resolve(res.data);
      },
      fail(err) {
        reject(err);
      },
    });
  });
}

function getBaseUrl() {
  return BASE_URL;
}

module.exports = { request, getBaseUrl, BASE_URL };