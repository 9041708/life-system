// miniprogram/pages/accounts/create.ts
const accountCreateReq = require('../../utils/request');
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

Page({
  data: {
    groups: [] as any[],
    groupIndex: 0,

    name: '',
    accountNo: '',
    initialBalance: '',
    submitting: false,
    loading: false,
    iconLibrary: [] as any[],
    selectedIconId: 0,
  },

  async onLoad() {
    await this.loadGroups();
    await this.loadIconLibrary();

    // 初始化分享配置：新增账户
    try {
      await initShare(this, {
        title: '三石记账 · 新增账户',
        path: '/pages/accounts/create',
      });
    } catch (e) {}
  },

  // 从现有账户中抽出账户大类
  async loadGroups() {
    this.setData({ loading: true });
    try {
      const res: any = await accountCreateReq.request({
        route: 'accounts/list',
        method: 'GET',
        data: {},
      });
      const groups: any[] = [{ id: 0, name: '请选择账户大类' }];
      if (res && res.success) {
        const list: any[] = res.accounts || [];
        const map: any = {};
        for (let i = 0; i < list.length; i++) {
          const a = list[i];
          const gid = a.group_id;
          const gname = a.group_name || '';
          if (gid && !map[gid]) {
            map[gid] = true;
            groups.push({
              id: gid,
              name: gname || ('大类 ' + gid),
            });
          }
        }
      }
      this.setData({
        groups,
        groupIndex: 0,
      });
    } catch (e) {
      this.setData({
        groups: [{ id: 0, name: '请选择账户大类' }],
        groupIndex: 0,
      });
    } finally {
      this.setData({ loading: false });
    }
  },
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },

  async loadIconLibrary() {
    try {
      const res: any = await accountCreateReq.request({
        route: 'icon-library/list',
        method: 'GET',
        data: {},
      });
      if (res && res.success) {
        this.setData({ iconLibrary: res.icons || [] });
      }
    } catch (e) {
      console.error('loadIconLibrary error', e);
    }
  },

  onGroupChange(e: any) {
    const index = Number(e.detail.value);
    this.setData({ groupIndex: index });
  },

  onNameInput(e: any) {
    this.setData({ name: e.detail.value });
  },

  onAccountNoInput(e: any) {
    this.setData({ accountNo: e.detail.value });
  },

  onInitialBalanceInput(e: any) {
    this.setData({ initialBalance: e.detail.value });
  },

  onIconSelect(e: any) {
    const id = Number(e.currentTarget.dataset.id || 0);
    this.setData({ selectedIconId: id || 0 });
  },

  onClearIcon() {
    this.setData({ selectedIconId: 0 });
  },

  async onSubmit() {
    if (this.data.submitting) return;

    const groups: any[] = this.data.groups;
    const groupIndex = this.data.groupIndex;
    const name = (this.data.name || '').trim();
    const accountNo = (this.data.accountNo || '').trim();
    const initialStr = (this.data.initialBalance || '').trim();

    if (!groups || !groups[groupIndex] || !groups[groupIndex].id) {
      wx.showToast({ title: '请选择账户大类', icon: 'none' });
      return;
    }
    if (!name) {
      wx.showToast({ title: '请输入账户名称', icon: 'none' });
      return;
    }

    let initial = 0;
    if (initialStr) {
      const v = Number(initialStr);
      if (isNaN(v)) {
        wx.showToast({ title: '初始余额必须是数字', icon: 'none' });
        return;
      }
      initial = v;
    }

    const groupId = groups[groupIndex].id;

    const payload: any = {
      group_id: groupId,
      name,
      account_no: accountNo,
      initial_balance: initial,
    };

    if (this.data.selectedIconId) {
      payload.icon_library_id = this.data.selectedIconId;
    }

    this.setData({ submitting: true });
    try {
      const res: any = await accountCreateReq.request({
        route: 'accounts/create',
        method: 'POST',
        data: payload,
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '保存失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '新增成功', icon: 'success' });
      setTimeout(() => {
        wx.navigateBack({ delta: 1 });
      }, 600);
    } catch (e) {
      wx.showToast({ title: '请求失败', icon: 'none' });
    } finally {
      this.setData({ submitting: false });
    }
  },
});