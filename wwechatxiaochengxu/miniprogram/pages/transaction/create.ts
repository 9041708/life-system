// miniprogram/pages/transaction/create.ts
const requestApi = require('../../utils/request');

function two(n: number): string {
  return n < 10 ? '0' + n : '' + n;
}

function formatDate(d: Date): string {
  const y = d.getFullYear();
  const m = d.getMonth() + 1;
  const day = d.getDate();
  return y + '-' + two(m) + '-' + two(day);
}

function formatTime(d: Date): string {
  const h = d.getHours();
  const m = d.getMinutes();
  return two(h) + ':' + two(m);
}

const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

type Attachment = {
  path: string;
  url: string;
};

function uploadAttachment(filePath: string, token: string): Promise<Attachment> {
  return new Promise((resolve, reject) => {
    const BASE_URL = 'https://9041708.cn:555/public/api.php';
    wx.uploadFile({
      url: BASE_URL + '?route=transactions/upload-attachment',
      filePath,
      name: 'file',
      header: {
        Authorization: 'Bearer ' + token,
      },
      formData: {},
      success: (res) => {
        try {
          const data = JSON.parse((res as any).data || '{}');
          if (!data || !data.success || !data.path || !data.url) {
            reject(new Error((data && data.error) || '上传失败'));
            return;
          }
          resolve({
            path: data.path || '',
            url: data.url || '',
          });
        } catch (e) {
          reject(new Error('上传返回异常'));
        }
      },
      fail: () => reject(new Error('上传失败')),
    });
  });
}

Page({
  data: {
    type: 'expense', // expense / income / transfer

    // 是否启用「转账」类型（由后端用户开关控制）
    transferEnabled: false,

    categories: [] as any[],
    categoryIndex: -1,

    items: [] as any[],
    itemIndex: -1,

    accounts: [] as any[],
    // 单账户类型时使用的账户索引（支出账户 / 收入账户）
    accountIndex: -1,

    // 转账类型下的转出 / 转入账户索引
    fromAccountIndex: -1,
    toAccountIndex: -1,

    // 当前正在选择的是哪个角色的账户（from/to）
    currentAccountRole: '' as 'from' | 'to' | '',

    amount: '',
    date: '',
    time: '',
    remark: '',

    submitting: false,
    loading: false,

    // 图片附件（最多 5 张）
    attachments: [] as Attachment[],
    uploadingAttachments: false,

    // 账户选择弹层
    showAccountSheet: false,

    // 分类 / 项目选择弹层
    showCategorySheet: false,
    showItemSheet: false,
    theme: 'light' as 'light' | 'dark',


  },

  async onLoad() {
    const user = wx.getStorageSync('user');
    if (!user) {
      wx.reLaunch({ url: '/pages/index/index' });
      return;
    }

    this.syncThemeFromGlobal();

    const now = new Date();
    this.setData({
      date: formatDate(now),
      time: formatTime(now),
      transferEnabled: !!(user && (user as any).enable_transfer),
    });

    await this.loadAccounts();
    await this.loadCategories();

    // 初始化分享配置：记一笔
    try {
      await initShare(this, {
        title: '三石记账 · 记一笔',
        path: '/pages/transaction/create',
      });
    } catch (e) {}
  },
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },

  onShow() {
    this.syncThemeFromGlobal();
  },

  syncThemeFromGlobal() {
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

  // ========== 顶部快捷入口 ==========
  onDebt() {
    wx.navigateTo({ url: '/pages/debt/list' });
  },

  onReimbursement() {
    wx.navigateTo({ url: '/pages/reimbursement/list' });
  },

  async loadAccounts() {
    this.setData({ loading: true });
    try {
      const res: any = await requestApi.request({
        route: 'accounts/list',
        method: 'GET',
        data: {},
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载账户失败', icon: 'none' });
        return;
      }
      const accounts: any[] = res.accounts || [];

      // 预先算好余额显示和正负，保留 icon_url
      for (let i = 0; i < accounts.length; i++) {
        const a = accounts[i];
        const bal = Number(a.current_balance || 0);
        const abs = Math.abs(bal);
        a._positive = bal >= 0;
        a._balanceDisplay = (bal >= 0 ? '' : '-') + abs.toFixed(2);
      }

      this.setData({
        accounts,
        accountIndex: accounts.length > 0 ? 0 : -1,
      });
    } catch (e) {
      wx.showToast({ title: '请求账户失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  async loadCategories() {
    const type = this.data.type;
    this.setData({
      loading: true,
      categories: [],
      categoryIndex: -1,
      items: [],
      itemIndex: -1,
      showCategorySheet: false,
      showItemSheet: false,
    });
    try {
      const res: any = await requestApi.request({
        route: 'categories/list',
        method: 'GET',
        data: { type },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载分类失败', icon: 'none' });
        return;
      }
      const categories = res.categories || [];
      this.setData({
        categories,
        categoryIndex: categories.length > 0 ? 0 : -1,
      });
      if (categories.length > 0) {
        await this.loadItems();
      }
    } catch (e) {
      wx.showToast({ title: '请求分类失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  async loadItems() {
    const categories: any[] = this.data.categories;
    const categoryIndex = this.data.categoryIndex;
    if (!categories || categoryIndex < 0 || !categories[categoryIndex]) {
      this.setData({ items: [], itemIndex: -1 });
      return;
    }
    const categoryId = categories[categoryIndex].id;
    this.setData({ loading: true, items: [], itemIndex: -1 });
    try {
      const res: any = await requestApi.request({
        route: 'items/list',
        method: 'GET',
        data: { category_id: categoryId },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载项目失败', icon: 'none' });
        return;
      }
      const items = res.items || [];
      this.setData({
        items,
        itemIndex: items.length > 0 ? 0 : -1,
      });
    } catch (e) {
      wx.showToast({ title: '请求项目失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  onTypeTap(e: any) {
    const t = e.currentTarget.dataset.type;
    if (t === this.data.type) return;
    this.setData({
      type: t,
      categories: [],
      categoryIndex: -1,
      items: [],
      itemIndex: -1,
      showCategorySheet: false,
      showItemSheet: false,
    });
    this.loadCategories();
  },

  // 分类弹层：打开 / 关闭 / 选择
  onOpenCategorySheet() {
    if (!this.data.categories || this.data.categories.length === 0) {
      wx.showToast({ title: '暂无分类', icon: 'none' });
      return;
    }
    this.setData({ showCategorySheet: true });
  },

  onCloseCategorySheet() {
    this.setData({
      showCategorySheet: false,
      showItemSheet: false,
    });
  },

  async onSelectCategory(e: any) {
    const index = Number(e.currentTarget.dataset.index);
    if (isNaN(index)) return;
    this.setData({
      categoryIndex: index,
      itemIndex: -1,
      items: [],
      showCategorySheet: false,
    });
    await this.loadItems();
  },

  // 项目弹层：打开 / 选择
  onOpenItemSheet() {
    if (this.data.categoryIndex < 0) {
      wx.showToast({ title: '请先选择分类', icon: 'none' });
      return;
    }
    if (!this.data.items || this.data.items.length === 0) {
      wx.showToast({ title: '该分类下暂无项目', icon: 'none' });
      return;
    }
    this.setData({ showItemSheet: true });
  },

  onSelectItem(e: any) {
    const index = Number(e.currentTarget.dataset.index);
    if (isNaN(index)) return;
    this.setData({
      itemIndex: index,
      showItemSheet: false,
    });
  },

  // 打开 / 关闭账户选择弹层
  onOpenAccountSheet(e: any) {
    let role: 'single' | 'from' | 'to' = 'single';
    if (e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.role) {
      const r = String(e.currentTarget.dataset.role);
      if (r === 'from' || r === 'to' || r === 'single') {
        role = r as any;
      }
    }

    // 转账类型要求明确区分转出 / 转入账户
    if (this.data.type === 'transfer') {
      if (role === 'single') {
        role = 'from';
      }
    }

    if (!this.data.accounts || this.data.accounts.length === 0) {
      wx.showToast({ title: '暂无账户', icon: 'none' });
      return;
    }
    this.setData({ showAccountSheet: true, currentAccountRole: role });
  },

  onCloseAccountSheet() {
    this.setData({ showAccountSheet: false });
  },

  onSelectAccount(e: any) {
    const index = Number(e.currentTarget.dataset.index);
    if (isNaN(index)) return;
    const accounts: any[] = this.data.accounts || [];
    if (!accounts || !accounts[index]) {
      this.setData({ showAccountSheet: false });
      return;
    }

    const role = this.data.currentAccountRole || 'single';
    if (this.data.type === 'transfer') {
      if (role === 'to') {
        this.setData({
          toAccountIndex: index,
          showAccountSheet: false,
        });
      } else {
        this.setData({
          fromAccountIndex: index,
          showAccountSheet: false,
        });
      }
    } else {
      this.setData({
        accountIndex: index,
        showAccountSheet: false,
      });
    }
  },

  onAmountInput(e: any) {
    this.setData({ amount: e.detail.value });
  },

  onRemarkInput(e: any) {
    this.setData({ remark: e.detail.value });
  },

  onDateChange(e: any) {
    this.setData({ date: e.detail.value });
  },

  onTimeChange(e: any) {
    this.setData({ time: e.detail.value });
  },

  // 选择 / 追加图片并上传（最多 5 张）
  async onPickImagePlaceholder() {
    const token = wx.getStorageSync('token') || '';
    if (!token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }

    const current: Attachment[] = this.data.attachments || [];
    const remaining = 5 - current.length;
    if (remaining <= 0) {
      wx.showToast({ title: '最多上传5张图片', icon: 'none' });
      return;
    }

    wx.chooseImage({
      count: remaining,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: async (chooseRes) => {
        const paths: string[] = (chooseRes && (chooseRes.tempFilePaths as any)) || [];
        if (!paths || paths.length === 0) return;

        this.setData({ uploadingAttachments: true });
        wx.showLoading({ title: '上传中...', mask: true });

        const added: Attachment[] = [];
        try {
          for (let i = 0; i < paths.length; i++) {
            const p = paths[i];
            if (!p) continue;
            const uploaded = await uploadAttachment(p, token);
            if (uploaded && uploaded.path) {
              added.push(uploaded);
            }
            const left = 5 - (current.length + added.length);
            wx.showLoading({ title: left > 0 ? '上传中...' : '处理中...', mask: true });
            if (current.length + added.length >= 5) {
              break;
            }
          }

          const next = (current.concat(added)).slice(0, 5);
          this.setData({ attachments: next });
          if (added.length > 0) {
            wx.showToast({ title: '图片已上传', icon: 'success' });
          }
        } catch (e: any) {
          wx.showToast({ title: (e && e.message) || '上传失败', icon: 'none' });
        } finally {
          wx.hideLoading();
          this.setData({ uploadingAttachments: false });
        }
      },
      fail: () => {
        // 用户取消不提示错误
      },
    });
  },

  // 预览附件
  onPreviewAttachment(e: any) {
    const source = e && e.currentTarget && e.currentTarget.dataset;
    const index = Number((source && source.index) || 0);
    const list: Attachment[] = this.data.attachments || [];
    const urls = list.map((a) => a.url).filter((u) => !!u);
    if (!urls || urls.length === 0) return;
    wx.previewImage({
      current: urls[index] || urls[0],
      urls,
    });
  },

  // 删除某一张附件
  onRemoveAttachment(e: any) {
    const source = e && e.currentTarget && e.currentTarget.dataset;
    const index = Number(source && source.index);
    const list: Attachment[] = this.data.attachments || [];
    if (!list || list.length === 0) return;
    if (isNaN(index) || index < 0 || index >= list.length) return;
    wx.showModal({
      title: '删除图片',
      content: '确定删除这张图片吗？',
      success: (res) => {
        if (!res.confirm) return;
        const next = list.slice(0, index).concat(list.slice(index + 1));
        this.setData({
          attachments: next,
        });
      },
    });
  },

  // 清空全部附件
  onClearAttachments() {
    const list: Attachment[] = this.data.attachments || [];
    if (!list || list.length === 0) return;
    wx.showModal({
      title: '清空图片',
      content: '确定清空全部图片吗？',
      success: (res) => {
        if (!res.confirm) return;
        this.setData({ attachments: [] });
      },
    });
  },

  noop() {},

  // 记账成功后，针对 tabBar 场景做表单重置
  resetFormAfterSubmit() {
    const now = new Date();
    this.setData({
      amount: '',
      remark: '',
      attachments: [],
      date: formatDate(now),
      time: formatTime(now),
    });
  },

  async onSubmit() {
    if (this.data.submitting) return;

    const type = this.data.type;
    const categories: any[] = this.data.categories;
    const categoryIndex = this.data.categoryIndex;
    const items: any[] = this.data.items;
    const itemIndex = this.data.itemIndex;
    const accounts: any[] = this.data.accounts;
    const accountIndex = this.data.accountIndex;
    const fromAccountIndex = this.data.fromAccountIndex;
    const toAccountIndex = this.data.toAccountIndex;
    const amountStr = this.data.amount;
    const date = this.data.date;
    const time = this.data.time;
    const remark = this.data.remark;

    const amount = Number(amountStr);
    if (!amountStr || amount <= 0) {
      wx.showToast({ title: '请输入金额', icon: 'none' });
      return;
    }
    if (!categories || categoryIndex < 0 || !categories[categoryIndex]) {
      wx.showToast({ title: '请选择分类', icon: 'none' });
      return;
    }
    let fromAccountId: number | null = null;
    let toAccountId: number | null = null;

    if (type === 'transfer') {
      if (!accounts || fromAccountIndex < 0 || !accounts[fromAccountIndex]) {
        wx.showToast({ title: '请选择转出账户', icon: 'none' });
        return;
      }
      if (!accounts || toAccountIndex < 0 || !accounts[toAccountIndex]) {
        wx.showToast({ title: '请选择转入账户', icon: 'none' });
        return;
      }
      fromAccountId = accounts[fromAccountIndex].id;
      toAccountId = accounts[toAccountIndex].id;
      if (fromAccountId && toAccountId && fromAccountId === toAccountId) {
        wx.showToast({ title: '转出账户和转入账户不能相同', icon: 'none' });
        return;
      }
    } else {
      if (!accounts || accountIndex < 0 || !accounts[accountIndex]) {
        wx.showToast({
          title: type === 'expense' ? '请选择支出账户' : '请选择收入账户',
          icon: 'none',
        });
        return;
      }
      const accountId = accounts[accountIndex].id;
      if (type === 'expense') {
        fromAccountId = accountId;
      } else {
        toAccountId = accountId;
      }
    }

    const categoryId = categories[categoryIndex].id;
    const itemId = items && itemIndex >= 0 && items[itemIndex] ? items[itemIndex].id : null;

    const now = new Date();
    const transTime =
      (date || formatDate(now)) + ' ' + (time || formatTime(now)) + ':00';

    const payload: any = {
      type,
      category_id: categoryId,
      amount: amount,
      trans_time: transTime,
      remark: remark || '',
      attachment_paths: (this.data.attachments || []).map((a) => a.path).filter((p) => !!p),
      source: 'miniapp',
    };
    if (itemId) {
      payload.item_id = itemId;
    }
    if (fromAccountId) {
      payload.from_account_id = fromAccountId;
    }
    if (toAccountId) {
      payload.to_account_id = toAccountId;
    }

    this.setData({ submitting: true });
    try {
      const res: any = await requestApi.request({
        route: 'transactions/create',
        method: 'POST',
        data: payload,
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '保存失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '记账成功', icon: 'success' });
      setTimeout(() => {
        const pages = getCurrentPages();
        // 如果是从其他页面 navigateTo 过来的，则返回上一页；
        // 如果当前是 tabBar 入口（无上一页），则重置表单，相当于新开一笔。
        if (pages && pages.length > 1) {
          wx.navigateBack({ delta: 1 });
        } else {
          this.resetFormAfterSubmit();
        }
      }, 600);
    } catch (e) {
      wx.showToast({ title: '请求失败', icon: 'none' });
    } finally {
      this.setData({ submitting: false });
    }
  },
});