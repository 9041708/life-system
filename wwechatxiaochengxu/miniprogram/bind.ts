// bind.ts
import { request } from './utils/request';

Page({
  data: {
    binding: false,
    message: ''
  },

  async handleScanBind() {
    if (this.data.binding) return;
    this.setData({ binding: true, message: '' });
    try {
      const scanRes = await wx.scanCode({ onlyFromCamera: true });
      const text = String((scanRes as any).result || '');
      let token = '';
      try {
        const obj = JSON.parse(text);
        if (obj && obj.type === 'bind' && obj.token) token = String(obj.token);
      } catch (_) {
        const m = text.match(/^BIND\|([0-9a-fA-F]{32})$/);
        if (m) token = m[1];
      }
      if (!token) throw new Error('二维码不正确');

      const loginRes = await wx.login();
      const code = loginRes.code || '';
      if (!code) throw new Error('获取微信登录凭证失败');

      const resp: any = await request({ route: 'wechat/bind-by-token', data: { code, token } });
      if (!resp || !resp.success) {
        const errMsg = (resp && resp.error) ? String(resp.error) : '绑定失败';
        throw new Error(errMsg);
      }

      // 保存令牌并跳转首页
      try { wx.setStorageSync('token', resp.token); } catch (_) {}
      wx.showToast({ title: '绑定成功', icon: 'success' });
      setTimeout(() => {
        wx.reLaunch({ url: '/pages/home/home' });
      }, 500);
    } catch (e: any) {
      const msg = (e && e.message) ? String(e.message) : '操作失败，请重试';
      this.setData({ message: msg });
      wx.showToast({ title: msg, icon: 'none' });
    } finally {
      this.setData({ binding: false });
    }
  }
})