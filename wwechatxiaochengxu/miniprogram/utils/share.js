// 小程序统一分享工具（CommonJS 版本）
// 使用后端 share/sign 接口为路径生成签名，并在各页面复用

const { request } = require('./request');

function buildQuery(params) {
  if (!params) return '';
  const parts = [];
  Object.keys(params).forEach((k) => {
    const v = params[k];
    if (v === undefined || v === null || v === '') return;
    parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(v)));
  });
  return parts.join('&');
}

/**
 * 初始化当前页面的分享配置：
 * - 调用后端 share/sign 获取签名后的路径
 * - 将结果存入 page.data 中
 */
async function initShare(page, options) {
  const title = options.title || '三石记账 · 随时随地记一笔';
  let path = options.path || '/pages/index/index';
  const extraQuery = options.extraQuery || null;

  const q = buildQuery(extraQuery);
  if (q) {
    path = path + (path.indexOf('?') === -1 ? '?' : '&') + q;
  }

  let sharePath = path;
  try {
    const res = await request({
      route: 'share/sign',
      method: 'POST',
      data: { path },
    });
    if (res && res.success && res.signed_path) {
      sharePath = res.signed_path;
    }
  } catch (e) {
    console.error('initShare error', e);
  }

  try {
    page.setData({
      _shareTitle: title,
      _sharePath: sharePath,
    });
  } catch (e) {
    console.error('set share data error', e);
  }
}

function buildShareAppMessage(page) {
  const data = (page && page.data) || {};
  return {
    title: data._shareTitle || '三石记账 · 随时随地记一笔',
    path: data._sharePath || '/pages/index/index',
  };
}

function buildShareTimeline(page) {
  const data = (page && page.data) || {};
  const path = data._sharePath || '/pages/index/index';
  const queryIndex = path.indexOf('?');
  const query = queryIndex >= 0 ? path.slice(queryIndex + 1) : '';
  return {
    title: data._shareTitle || '三石记账 · 随时随地记一笔',
    query,
  };
}

module.exports = {
  initShare,
  buildShareAppMessage,
  buildShareTimeline,
};
