// miniprogram/pages/settings/index.ts
import { request } from '../../utils/request';
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

Page({
  data: {
    loading: false,
    user: null as any,
    displayUserId: '',
    budgetReminderEnabled: true,
    transferEnabled: false,
    allowNegativeBalance: false,
    reimbursementEnabled: false,
    ledgerMode: false,
    ledgers: [] as any[],
    ledgerLabels: [] as string[],
    activeLedgerIndex: 0,
    activeLedgerDisplay: '个人账本',
    creatingLedger: false,
    joiningLedger: false,
    saving: false,
    syncingNickname: false,
    uploadingAvatar: false,
    nickInput: '',
    emailIsPlaceholder: false,
    stats: {
      registerDays: 0,
      bookDays: 0,
      streakDays: 0,
      transactionCount: 0,
    },
    theme: 'light' as 'light' | 'dark',
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    const token = wx.getStorageSync('token') || '';
    const user = wx.getStorageSync('user') || null;
    if (user) {
      const email = user.email || '';
      this.setData({
        user,
        displayUserId: this.formatUserId(user.id),
        budgetReminderEnabled: user.budget_reminder_enabled !== false,
        transferEnabled: !!user.enable_transfer,
        allowNegativeBalance: !!user.allow_negative_balance,
        nickInput: user.nickname || user.username || '',
        emailIsPlaceholder: !!email && typeof email === 'string' && email.endsWith('@miniapp.local'),
      });
    }
    if (token) {
      this.loadProfile();
      this.loadLedgers();
      this.loadStats();
      this.loadReimbursementConfig();
    }

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
      // @ts-ignore
      app.globalData.theme = theme;
    } catch (e) {}

    try {
      await initShare(this, {
        title: '三石记账 · 设置中心',
        path: '/pages/settings/index',
      });
    } catch (e) {}
  },

  async onShow() {
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
  },

  async loadReimbursementConfig() {
    try {
      const res: any = await request({
        route: 'reimbursement/config',
        method: 'GET',
      });
      if (res && res.success && res.config) {
        this.setData({ reimbursementEnabled: !!res.config.enabled });
      }
    } catch (e) {}
  },

  async onChangeUsername() {
    const user = this.data.user;
    if (!user) return;
    const res = await wx.showModal({
      title: '修改用户名',
      editable: true,
      placeholderText: '请输入新的用户名',
      content: user.username || '',
    } as any);
    if (!res.confirm) return;
    const value = (res as any).content ? String((res as any).content).trim() : '';
    if (!value) {
      wx.showToast({ title: '用户名不能为空', icon: 'none' });
      return;
    }
    try {
      const resp: any = await request({
        route: 'settings/update-username',
        method: 'POST',
        data: { username: value },
      });
      if (!resp || !resp.success) {
        wx.showToast({ title: (resp && resp.error) || '修改失败', icon: 'none' });
        return;
      }
      this.setData({ user: resp.user });
      wx.setStorageSync('user', resp.user);
      wx.showToast({ title: '已更新', icon: 'success' });
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    }
  },

  async onSetEmail() {
    const user = this.data.user;
    if (!user) return;
    const res = await wx.showModal({
      title: '填写邮箱',
      editable: true,
      placeholderText: '请输入邮箱地址',
      content: (user.email && !(this.data.emailIsPlaceholder)) ? user.email : '',
    } as any);
    if (!res.confirm) return;
    const email = (res as any).content ? String((res as any).content).trim() : '';
    if (!email) {
      wx.showToast({ title: '请输入邮箱', icon: 'none' });
      return;
    }
    try {
      const resp: any = await request({ route: 'settings/change-email', method: 'POST', data: { email } });
      if (!resp || !resp.success) {
        wx.showToast({ title: (resp && resp.error) || '绑定失败', icon: 'none' });
        return;
      }
      const u = { ...(this.data.user || {}), email: resp.email };
      const emailIsPlaceholder = !!(u.email && typeof u.email === 'string' && u.email.endsWith('@miniapp.local'));
      this.setData({ user: u, emailIsPlaceholder });
      wx.setStorageSync('user', u);
      wx.showToast({ title: '邮箱已更新', icon: 'success' });
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    }
  },

  async onRequestChangeEmail() {
    await this.onSetEmail();
  },

  async onSetPassword() {
    const first = await wx.showModal({
      title: '设置密码',
      editable: true,
      placeholderText: '请输入新密码（至少6位）',
      content: '',
    } as any);
    if (!first.confirm) return;
    const p1 = (first as any).content ? String((first as any).content) : '';
    const second = await wx.showModal({
      title: '再次确认',
      editable: true,
      placeholderText: '请再次输入新密码',
      content: '',
    } as any);
    if (!second.confirm) return;
    const p2 = (second as any).content ? String((second as any).content) : '';
    try {
      const resp: any = await request({ route: 'settings/set-password', method: 'POST', data: { password: p1, confirm: p2 } });
      if (!resp || !resp.success) {
        wx.showToast({ title: (resp && resp.error) || '设置失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '密码已设置', icon: 'success' });
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    }
  },

  async onSyncWechatNickname() {
    if (this.data.syncingNickname) return;
    const user = this.data.user;
    if (!user) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }

    let nickname = '';
    let avatarUrl = '';
    try {
      const profile: any = await wx.getUserProfile({ desc: '用于展示的头像和昵称' });
      if (profile && profile.userInfo) {
        if (profile.userInfo.nickName) nickname = profile.userInfo.nickName;
        if (profile.userInfo.avatarUrl) avatarUrl = profile.userInfo.avatarUrl;
      }
    } catch (e) {
      wx.showToast({ title: '已取消授权', icon: 'none' });
      return;
    }

    if (!nickname) {
      wx.showToast({ title: '获取昵称失败', icon: 'none' });
      return;
    }

    this.setData({ syncingNickname: true });
    wx.showLoading({ title: '正在同步…', mask: true });
    try {
      const resp: any = await request({
        route: 'settings/update-nickname-from-wechat',
        method: 'POST',
        data: { nickname, avatar_url: avatarUrl },
      });
      if (!resp || !resp.success || !resp.user) {
        wx.showToast({ title: (resp && resp.error) || '同步失败', icon: 'none' });
        return;
      }
      this.setData({ user: resp.user });
      wx.setStorageSync('user', resp.user);
      wx.showToast({ title: '已同步微信昵称', icon: 'success' });
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ syncingNickname: false });
      wx.hideLoading();
    }
  },

  async onChooseAvatar(e: any) {
    const user = this.data.user;
    if (!user) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    if (this.data.uploadingAvatar) return;

    const avatarUrl = (e && e.detail && e.detail.avatarUrl) || '';
    if (!avatarUrl) return;

    try {
      this.setData({ uploadingAvatar: true });
      wx.showLoading({ title: '正在上传头像…', mask: true });
      const timeoutId = setTimeout(() => {
        if (this.data.uploadingAvatar) {
          wx.hideLoading();
          wx.showToast({ title: '上传时间较长，请稍后查看', icon: 'none' });
        }
      }, 15000);

      const token = wx.getStorageSync('token') || '';
      const uploadUrl = 'https://9041708.cn:555/public/api.php?route=settings/upload-avatar';
      await new Promise((resolve, reject) => {
        wx.uploadFile({
          url: uploadUrl,
          filePath: avatarUrl,
          name: 'avatar',
          header: token ? { Authorization: 'Bearer ' + token } : {},
          timeout: 15000,
          success: (res) => {
            try {
              const data = JSON.parse(res.data || '{}');
              if (!data || !data.success || !data.user) {
                wx.showToast({ title: (data && data.error) || '上传失败', icon: 'none' });
                reject(new Error('upload failed'));
                return;
              }
              this.setData({ user: data.user });
              wx.setStorageSync('user', data.user);
              wx.showToast({ title: '头像已更新', icon: 'success' });
              resolve(null);
            } catch (err) {
              wx.showToast({ title: '上传失败', icon: 'none' });
              reject(err);
            }
          },
          fail: (err) => {
            wx.showToast({ title: '网络错误', icon: 'none' });
            reject(err);
          },
          complete: () => {
            clearTimeout(timeoutId);
            wx.hideLoading();
          },
        });
      });
    } catch (err) {
      console.error('onChooseAvatar error', err);
    } finally {
      this.setData({ uploadingAvatar: false });
    }
  },

  async onEditNickname() {
    const user = this.data.user;
    if (!user) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }

    const res = await wx.showModal({
      title: '修改昵称',
      editable: true,
      placeholderText: '请输入新的昵称',
      content: user.nickname || '',
    } as any);
    if (!res.confirm) return;
    const value = (res as any).content ? String((res as any).content).trim() : '';
    if (!value) {
      wx.showToast({ title: '昵称不能为空', icon: 'none' });
      return;
    }

    try {
      const resp: any = await request({
        route: 'settings/update-nickname-from-wechat',
        method: 'POST',
        data: { nickname: value },
      });
      if (!resp || !resp.success || !resp.user) {
        wx.showToast({ title: (resp && resp.error) || '保存失败', icon: 'none' });
        return;
      }
      this.setData({ user: resp.user });
      wx.setStorageSync('user', resp.user);
      wx.showToast({ title: '昵称已更新', icon: 'success' });
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    }
  },

  async onNicknameBlur(e: any) {
    const user = this.data.user;
    if (!user) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }

    const value = ((e && e.detail && e.detail.value) || '').trim();
    this.setData({ nickInput: value });
    if (!value) {
      wx.showToast({ title: '昵称不能为空', icon: 'none' });
      return;
    }
    if (value === user.nickname || (!user.nickname && value === user.username)) {
      return;
    }

    try {
      const resp: any = await request({
        route: 'settings/update-nickname-from-wechat',
        method: 'POST',
        data: { nickname: value },
      });
      if (!resp || !resp.success || !resp.user) {
        wx.showToast({ title: (resp && resp.error) || '保存失败', icon: 'none' });
        return;
      }
      this.setData({ user: resp.user, nickInput: resp.user.nickname || resp.user.username || '' });
      wx.setStorageSync('user', resp.user);
      wx.showToast({ title: '昵称已更新', icon: 'success' });
    } catch (err) {
      console.error('onNicknameBlur error', err);
      wx.showToast({ title: '网络错误', icon: 'none' });
    }
  },

  async onChangeAvatar() {
    const user = this.data.user;
    if (!user) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    if (this.data.uploadingAvatar) return;

    try {
      const choose = await wx.chooseMedia({ count: 1, mediaType: ['image'] });
      if (!choose || !choose.tempFiles || !choose.tempFiles.length) return;
      const filePath = choose.tempFiles[0].tempFilePath;

      this.setData({ uploadingAvatar: true });
      wx.showLoading({ title: '正在上传头像…', mask: true });
      const timeoutId = setTimeout(() => {
        if (this.data.uploadingAvatar) {
          wx.hideLoading();
          wx.showToast({ title: '上传时间较长，请稍后查看', icon: 'none' });
        }
      }, 15000);

      const token = wx.getStorageSync('token') || '';
      const uploadUrl = 'https://9041708.cn:555/public/api.php?route=settings/upload-avatar';
      await new Promise((resolve, reject) => {
        wx.uploadFile({
          url: uploadUrl,
          filePath,
          name: 'avatar',
          header: token ? { Authorization: 'Bearer ' + token } : {},
          timeout: 15000,
          success: (res) => {
            try {
              const data = JSON.parse(res.data || '{}');
              if (!data || !data.success || !data.user) {
                wx.showToast({ title: (data && data.error) || '上传失败', icon: 'none' });
                reject(new Error('upload failed'));
                return;
              }
              this.setData({ user: data.user });
              wx.setStorageSync('user', data.user);
              wx.showToast({ title: '头像已更新', icon: 'success' });
              resolve(null);
            } catch (e) {
              wx.showToast({ title: '上传失败', icon: 'none' });
              reject(e);
            }
          },
          fail: (err) => {
            wx.showToast({ title: '网络错误', icon: 'none' });
            reject(err);
          },
          complete: () => {
            clearTimeout(timeoutId);
            wx.hideLoading();
          },
        });
      });
    } catch (e) {
      console.error('onChangeAvatar error', e);
    } finally {
      this.setData({ uploadingAvatar: false });
    }
  },

  async loadProfile() {
    const token = wx.getStorageSync('token') || '';
    if (!token) return;
    this.setData({ loading: true });
    try {
      const res: any = await request({
        route: 'auth/profile',
        method: 'GET',
      });
      if (res && res.success && res.user) {
        const user = res.user;
        user.budget_reminder_enabled = user.budget_reminder_enabled !== false;
        const email = user.email || '';
        this.setData({
          user,
          displayUserId: this.formatUserId(user.id),
          budgetReminderEnabled: !!user.budget_reminder_enabled,
          transferEnabled: !!user.enable_transfer,
          allowNegativeBalance: !!user.allow_negative_balance,
          emailIsPlaceholder: !!email && typeof email === 'string' && email.endsWith('@miniapp.local'),
        });
        wx.setStorageSync('user', user);
      }
    } catch (e) {
      console.error('loadProfile error', e);
    } finally {
      this.setData({ loading: false });
    }
  },

  async loadStats() {
    const token = wx.getStorageSync('token') || '';
    if (!token) return;
    try {
      const res: any = await request({
        route: 'user/stats',
        method: 'GET',
      });
      if (!res || !res.success || !res.stats) return;
      const s = res.stats;
      this.setData({
        stats: {
          registerDays: Number(s.register_days || 0),
          bookDays: Number(s.book_days || 0),
          streakDays: Number(s.streak_days || 0),
          transactionCount: Number(s.transaction_count || 0),
        },
      });
    } catch (e) {}
  },

  formatUserId(id: any) {
    const num = Number(id || 0);
    if (!num) return '';
    const base = 100000;
    return String(base + num);
  },

  async loadLedgers() {
    const token = wx.getStorageSync('token') || '';
    if (!token) return;

    try {
      const res: any = await request({ route: 'ledgers/list', method: 'GET' });
      if (!res || !res.success) return;

      const ledgers = (res.ledgers || []) as any[];
      const activeLedgerId = Number(res.active_ledger_id || (res.active_ledger && res.active_ledger.id) || 0);

      const labels = ledgers.map((l: any) => {
        const type = String(l.type || 'personal');
        const prefix = type === 'shared' ? '共享账本' : '个人账本';
        const name = l.name ? String(l.name) : '';
        const role = l.member_role ? String(l.member_role) : '';
        return role ? `${prefix}：${name}（${role}）` : `${prefix}：${name}`;
      });

      let activeIndex = 0;
      if (activeLedgerId) {
        const idx = ledgers.findIndex((l: any) => Number(l.id) === activeLedgerId);
        if (idx >= 0) activeIndex = idx;
      }

      const activeDisplay = labels[activeIndex] || '个人账本';

      this.setData({
        ledgerMode: !!res.ledger_mode,
        ledgers,
        ledgerLabels: labels,
        activeLedgerIndex: activeIndex,
        activeLedgerDisplay: activeDisplay,
      });

      if (res.active_ledger) {
        wx.setStorageSync('active_ledger', res.active_ledger);
      }
    } catch (e) {}
  },

  async onCreateSharedLedger() {
    const token = wx.getStorageSync('token') || '';
    if (!token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    if (this.data.creatingLedger) return;

    const res = await wx.showModal({
      title: '新建共享账本',
      editable: true,
      placeholderText: '例如：家庭账本 / 项目A 账本',
      content: '',
    } as any);
    if (!res.confirm) return;
    const name = (res as any).content ? String((res as any).content).trim() : '';

    this.setData({ creatingLedger: true });
    wx.showLoading({ title: '创建中…', mask: true });
    try {
      const resp: any = await request({
        route: 'ledgers/create-shared',
        method: 'POST',
        data: { name },
      });
      if (!resp || !resp.success) {
        wx.showToast({ title: (resp && resp.error) || '创建失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已创建并切换', icon: 'success' });
      await this.loadLedgers();
      setTimeout(() => { wx.reLaunch({ url: '/pages/home/home' }); }, 300);
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ creatingLedger: false });
      wx.hideLoading();
    }
  },

  async onJoinLedgerByCode() {
    const token = wx.getStorageSync('token') || '';
    if (!token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    if (this.data.joiningLedger) return;

    const res = await wx.showModal({
      title: '加入共享账本',
      editable: true,
      placeholderText: '请输入邀请码',
      content: '',
    } as any);
    if (!res.confirm) return;
    const code = (res as any).content ? String((res as any).content).trim() : '';
    if (!code) {
      wx.showToast({ title: '邀请码不能为空', icon: 'none' });
      return;
    }

    this.setData({ joiningLedger: true });
    wx.showLoading({ title: '加入中…', mask: true });
    try {
      const resp: any = await request({
        route: 'ledgers/join-by-code',
        method: 'POST',
        data: { invite_code: code },
      });
      if (!resp || !resp.success) {
        wx.showToast({ title: (resp && resp.error) || '加入失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已加入并切换', icon: 'success' });
      await this.loadLedgers();
      setTimeout(() => { wx.reLaunch({ url: '/pages/home/home' }); }, 300);
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ joiningLedger: false });
      wx.hideLoading();
    }
  },

  async onLedgerPickerChange(e: any) {
    const token = wx.getStorageSync('token') || '';
    if (!token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }

    const idx = Number((e && e.detail && e.detail.value) || 0);
    const ledgers = this.data.ledgers || [];
    const ledger = ledgers[idx];
    if (!ledger || !ledger.id) return;

    wx.showLoading({ title: '切换中…', mask: true });
    try {
      const res: any = await request({
        route: 'ledgers/set-active',
        method: 'POST',
        data: { ledger_id: Number(ledger.id) },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '切换失败', icon: 'none' });
        return;
      }

      this.setData({
        activeLedgerIndex: idx,
        activeLedgerDisplay: this.data.ledgerLabels[idx] || this.data.activeLedgerDisplay,
      });

      if (res.active_ledger) {
        wx.setStorageSync('active_ledger', res.active_ledger);
      }

      wx.showToast({ title: '已切换', icon: 'success' });
      setTimeout(() => { wx.reLaunch({ url: '/pages/home/home' }); }, 300);
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      wx.hideLoading();
    }
  },

  onBudgetReminderChange(e: WechatMiniprogram.SwitchChange) {
    const enabled = !!e.detail.value;
    this.setData({ budgetReminderEnabled: enabled });
    this.saveBudgetReminder(enabled);
  },

  async saveBudgetReminder(enabled: boolean) {
    if (this.data.saving) return;
    this.setData({ saving: true });
    try {
      const res: any = await request({
        route: 'settings/update-budget-reminder',
        method: 'POST',
        data: { enabled },
      });
      if (res && res.success) {
        const user = this.data.user || {};
        user.budget_reminder_enabled = res.budget_reminder_enabled !== false;
        this.setData({ user });
        wx.setStorageSync('user', user);
        wx.showToast({ title: '预算提醒已更新', icon: 'success' });
      } else {
        wx.showToast({ title: (res && res.error) || '保存失败', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ saving: false });
    }
  },

  onTransferChange(e: WechatMiniprogram.SwitchChange) {
    const enabled = !!e.detail.value;
    this.setData({ transferEnabled: enabled });
    this.saveTransferEnabled(enabled);
  },

  async saveTransferEnabled(enabled: boolean) {
    if (this.data.saving) return;
    this.setData({ saving: true });
    try {
      const res: any = await request({
        route: 'settings/update-transfer-feature',
        method: 'POST',
        data: { enabled },
      });
      if (res && res.success) {
        const user = this.data.user || {};
        (user as any).enable_transfer = !!res.enable_transfer;
        this.setData({ user });
        wx.setStorageSync('user', user);
        wx.showToast({ title: '转账功能已更新', icon: 'success' });
      } else {
        wx.showToast({ title: (res && res.error) || '保存失败', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ saving: false });
    }
  },

  onAllowNegativeBalanceChange(e: WechatMiniprogram.SwitchChange) {
    const enabled = !!e.detail.value;
    this.setData({ allowNegativeBalance: enabled });
    this.saveAllowNegativeBalance(enabled);
  },

  async saveAllowNegativeBalance(enabled: boolean) {
    if (this.data.saving) return;
    this.setData({ saving: true });
    try {
      const res: any = await request({
        route: 'settings/update-allow-negative-balance',
        method: 'POST',
        data: { enabled },
      });
      if (res && res.success) {
        const user = this.data.user || {};
        (user as any).allow_negative_balance = !!res.allow_negative_balance;
        this.setData({ user });
        wx.setStorageSync('user', user);
        wx.showToast({ title: '账户负数开关已更新', icon: 'success' });
      } else {
        wx.showToast({ title: (res && res.error) || '保存失败', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ saving: false });
    }
  },

  onReimbursementChange(e: WechatMiniprogram.SwitchChange) {
    const enabled = !!e.detail.value;
    this.setData({ reimbursementEnabled: enabled });
    this.saveReimbursementConfig(enabled);
  },

  async saveReimbursementConfig(enabled: boolean) {
    if (this.data.saving) return;
    this.setData({ saving: true });
    try {
      const res: any = await request({
        route: 'reimbursement/config',
        method: 'POST',
        data: { enabled },
      });
      if (res && res.success) {
        wx.showToast({ title: '报销管理已' + (enabled ? '开启' : '关闭'), icon: 'success' });
      } else {
        wx.showToast({ title: (res && res.error) || '保存失败', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '网络错误', icon: 'none' });
    } finally {
      this.setData({ saving: false });
    }
  },

  onViewChangelog() {
    wx.navigateTo({ url: "/pages/changelog/index" });
  },

  onBindPcAccount() {
    const token = wx.getStorageSync('token') || '';
    if (!token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    wx.navigateTo({ url: '/pages/bind/bind' });
  },

  onViewGoals() {
    const token = wx.getStorageSync('token') || '';
    if (!token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    wx.navigateTo({ url: '/pages/goals/list' });
  },

  onViewFeedback() {
    wx.navigateTo({ url: '/pages/feedback/index' });
  },

  onCheckUpdate() {
    try {
      const updateManager = wx.getUpdateManager();
      wx.showLoading({ title: '正在检查更新…', mask: true });
      updateManager.onCheckForUpdate((res) => {
        wx.hideLoading();
        if (res.hasUpdate) {
          wx.showToast({ title: '检测到新版本', icon: 'none' });
        } else {
          wx.showToast({ title: '当前已是最新版本', icon: 'none' });
        }
      });
      updateManager.onUpdateReady(() => {
        wx.hideLoading();
        wx.showModal({
          title: '更新就绪',
          content: '有新版本可用，是否重启应用以更新？',
          confirmText: '重启更新',
          success: (r) => {
            if (r.confirm) updateManager.applyUpdate();
          },
        });
      });
      updateManager.onUpdateFailed(() => {
        wx.hideLoading();
        wx.showModal({
          title: '更新失败',
          content: '请关闭后重新进入或稍后再试。',
          showCancel: false,
        });
      });
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: '当前基础库不支持更新检测', icon: 'none' });
    }
  },

  onViewIcons() {
    wx.navigateTo({ url: '/pages/icons/list' });
  },

  onOpenPrivacyContract() {
    try {
      if (wx.openPrivacyContract) {
        wx.openPrivacyContract({});
      } else {
        wx.showToast({ title: '当前基础库不支持查看隐私协议', icon: 'none' });
      }
    } catch (e) {
      wx.showToast({ title: '打开失败，请稍后重试', icon: 'none' });
    }
  },

  onThemeSwitchChange(e: WechatMiniprogram.SwitchChange) {
    const isDark = !!e.detail.value;
    const theme: 'light' | 'dark' = isDark ? 'dark' : 'light';
    this.applyTheme(theme);
  },

  applyTheme(theme: 'light' | 'dark') {
    this.setData({ theme });
    try {
      const app = getApp<IAppOption>();
      // @ts-ignore
      app.globalData.theme = theme;
    } catch (e) {}
    try {
      wx.setStorageSync('theme', theme);
    } catch (e) {}
  },

  onViewQrLogin() {
    wx.navigateTo({ url: '/pages/login/qr-confirm' });
  },

  async onScanJoinLedger() {
    const token = wx.getStorageSync('token') || '';
    if (!token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    try {
      const res = await wx.scanCode({ onlyFromCamera: true });
      const text = String((res as any).result || '');
      let inviteCode = '';
      try {
        const obj = JSON.parse(text);
        if (obj && obj.type === 'ledger_invite' && obj.code) {
          inviteCode = String(obj.code);
        }
      } catch (e) {}
      if (!inviteCode) {
        wx.showToast({ title: '二维码不正确', icon: 'none' });
        return;
      }

      wx.showLoading({ title: '加入中…', mask: true });
      const resp: any = await request({
        route: 'ledgers/join-by-code',
        method: 'POST',
        data: { invite_code: inviteCode },
      });
      if (!resp || !resp.success) {
        wx.showToast({ title: (resp && resp.error) || '加入失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已加入并切换', icon: 'success' });
      await this.loadLedgers();
      setTimeout(() => { wx.reLaunch({ url: '/pages/home/home' }); }, 300);
    } catch (e: any) {
      const msg = (e && e.message) ? String(e.message) : '操作失败，请重试';
      wx.showToast({ title: msg, icon: 'none' });
    } finally {
      wx.hideLoading();
    }
  },

  onLogoutTap() {
    wx.showModal({
      title: "确认退出登录？",
      content: "退出后需要重新登录才能继续使用。",
      confirmColor: "#e64340",
      success: (res) => {
        if (!res.confirm) return;
        try {
          wx.removeStorageSync("user");
          wx.removeStorageSync("token");
        } catch (e) {
          console.error("clear storage error", e);
        }
        wx.reLaunch({ url: "/pages/index/index" });
      },
    });
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});