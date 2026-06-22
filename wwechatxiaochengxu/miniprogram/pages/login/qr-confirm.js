const { request } = require('../../utils/request');

Page({
  data: {
    tokenInput: '',
    submitting: false,
  },

  onTokenInput(e) {
    this.setData({ tokenInput: e.detail.value });
  },

  onScan() {
    wx.scanCode({
      onlyFromCamera: true,
      scanType: ['qrCode'],
      success: (res) => {
        try {
          const raw = (res.result || '').trim();
          const payload = JSON.parse(raw);
          if (!payload || payload.type !== 'qr-login' || !payload.token) {
            wx.showToast({ title: '二维码格式不正确', icon: 'none' });
            return;
          }
          this.confirmToken(payload.token);
        } catch (e) {
          wx.showToast({ title: '二维码解析失败', icon: 'none' });
        }
      },
    });
  },

  onConfirmManual() {
    const token = (this.data.tokenInput || '').trim();
    if (!token) {
      wx.showToast({ title: '请输入token', icon: 'none' });
      return;
    }
    this.confirmToken(token);
  },

  async confirmToken(token) {
    if (this.data.submitting) return;
    this.setData({ submitting: true });
    try {
      const res = await request({
        route: 'qr-login/confirm',
        method: 'POST',
        data: { token },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '确认失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已确认，请回到PC', icon: 'success' });
      setTimeout(() => wx.navigateBack(), 500);
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ submitting: false });
    }
  },
});
