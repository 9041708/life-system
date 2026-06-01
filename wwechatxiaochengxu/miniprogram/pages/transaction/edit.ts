// miniprogram/pages/transaction/edit.ts
const editReq = require('../../utils/request');
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

function pad2(n: number): string {
  return n < 10 ? '0' + n : '' + n;
}

function formatDateTimeStr(str: string): { date: string; time: string } {
  if (!str || str.length < 16) {
    const d = new Date();
    return {
      date: d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()),
      time: pad2(d.getHours()) + ':' + pad2(d.getMinutes()),
    };
  }
  return {
    date: str.substr(0, 10),
    time: str.substr(11, 5),
  };
}

Page({
  data: {
    id: 0,
    type: 'expense', // expense / income / transfer

    // 是否启用「转账」类型（由后端用户开关控制，用于控制类型切换按钮展示）
    transferEnabled: false,

    categories: [] as any[],
    categoryIndex: -1,

    items: [] as any[],
    itemIndex: -1,

    accounts: [] as any[],
    accountIndex: -1,

    // 转账类型下的转出 / 转入账户索引
    fromAccountIndex: -1,
    toAccountIndex: -1,

    currentAccountRole: '' as 'from' | 'to' | '',

    amount: '',
    date: '',
    time: '',
    remark: '',

    fromAccountId: 0,
    toAccountId: 0,

    // 图片附件（最多 5 张）
    attachments: [] as Attachment[],
    uploadingAttachments: false,

    submitting: false,
    loading: false,

    // 账户选择弹层
    showAccountSheet: false,

    // 分类 / 项目选择弹层
    showCategorySheet: false,
    showItemSheet: false,
  },

  async onLoad(options: any) {
    if (!options.data) {
      wx.showToast({ title: '参数错误', icon: 'none' });
      wx.navigateBack({ delta: 1 });
      return;
    }
    let tx: any = null;
    try {
      tx = JSON.parse(decodeURIComponent(options.data));
    } catch (e) {
      wx.showToast({ title: '数据解析失败', icon: 'none' });
      wx.navigateBack({ delta: 1 });
      return;
    }

    const dt = formatDateTimeStr(tx.trans_time || '');

    let attachments: Attachment[] = [];
    if (tx && Array.isArray(tx.attachments) && Array.isArray(tx.attachment_urls)) {
      const paths: string[] = tx.attachments || [];
      const urls: string[] = tx.attachment_urls || [];
      for (let i = 0; i < paths.length; i++) {
        const p = paths[i];
        const u = urls[i] || '';
        if (p) {
          attachments.push({ path: p, url: u });
        }
      }
    }
    if (!attachments.length && (tx.attachment_path || tx.attachment_url)) {
      attachments = [
        {
          path: tx.attachment_path || '',
          url: tx.attachment_url || '',
        },
      ].filter((a) => !!a.path || !!a.url);
    }

    this.setData({
      id: tx.id,
      type: tx.type,
      amount: String(tx.amount),
      remark: tx.remark || '',
      date: dt.date,
      time: dt.time,
      fromAccountId: tx.from_account_id || 0,
      toAccountId: tx.to_account_id || 0,
      transferEnabled: !!(wx.getStorageSync('user') && (wx.getStorageSync('user') as any).enable_transfer),
      attachments: attachments.slice(0, 5),
    });

    await this.loadAccounts(tx);
    await this.loadCategoriesAndItems(tx);

    // 初始化分享配置：编辑记账
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: '三石记账 · 编辑记账',
        path: '/pages/transaction/edit',
      });
    } catch (e) {}
  },

  async loadAccounts(tx: any) {
    this.setData({ loading: true });
    try {
      const res: any = await editReq.request({
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

      let index = -1;
      let fromIndex = -1;
      let toIndex = -1;
      const fromId = tx.from_account_id || 0;
      const toId = tx.to_account_id || 0;
      for (let i = 0; i < accounts.length; i++) {
        const id = accounts[i].id;
        if (id === fromId) {
          fromIndex = i;
        }
        if (id === toId) {
          toIndex = i;
        }
      }
      if (this.data.type === 'expense') {
        index = fromIndex;
      } else if (this.data.type === 'income') {
        index = toIndex;
      }
      this.setData({
        accounts,
        accountIndex: index,
        fromAccountIndex: fromIndex,
        toAccountIndex: toIndex,
      });
    } catch (e) {
      wx.showToast({ title: '请求账户失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  async loadCategoriesAndItems(tx: any) {
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
      const res: any = await editReq.request({
        route: 'categories/list',
        method: 'GET',
        data: { type },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载分类失败', icon: 'none' });
        return;
      }
      const categories = res.categories || [];
      let categoryIndex = -1;
      for (let i = 0; i < categories.length; i++) {
        if (categories[i].id === tx.category_id) {
          categoryIndex = i;
          break;
        }
      }
      this.setData({
        categories,
        categoryIndex,
      });

      if (categoryIndex >= 0) {
        await this.loadItemsInner(tx.category_id, tx.item_id || 0);
      }
    } catch (e) {
      wx.showToast({ title: '请求分类失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  async loadItemsInner(categoryId: number, currentItemId: number) {
    this.setData({ loading: true, items: [], itemIndex: -1 });
    try {
      const res: any = await editReq.request({
        route: 'items/list',
        method: 'GET',
        data: { category_id: categoryId },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载项目失败', icon: 'none' });
        return;
      }
      const items = res.items || [];
      let itemIndex = -1;
      for (let i = 0; i < items.length; i++) {
        if (items[i].id === currentItemId) {
          itemIndex = i;
          break;
        }
      }
      this.setData({
        items,
        itemIndex,
      });
    } catch (e) {
      wx.showToast({ title: '请求项目失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  onTypeTap(e: any) {
    const t = e.currentTarget.dataset.type;
    if (!t || t === this.data.type) return;
    this.setData({
      type: t,
      categories: [],
      categoryIndex: -1,
      items: [],
      itemIndex: -1,
      fromAccountId: 0,
      toAccountId: 0,
      accountIndex: -1,
      showCategorySheet: false,
      showItemSheet: false,
    });
    this.loadCategoriesAndItems({
      category_id: 0,
      item_id: 0,
    });
  },
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
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
    const cats: any[] = this.data.categories;
    if (!cats || !cats[index]) return;

    this.setData({
      categoryIndex: index,
      itemIndex: -1,
      items: [],
      showCategorySheet: false,
    });
    await this.loadItemsInner(cats[index].id, 0);
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
  onOpenAccountSheet() {
    if (!this.data.accounts || this.data.accounts.length === 0) {
      wx.showToast({ title: '暂无账户', icon: 'none' });
      return;
    }
    this.setData({ showAccountSheet: true, currentAccountRole: this.data.type === 'transfer' ? 'from' : 'single' as any });
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
        this.setData({ toAccountIndex: index, showAccountSheet: false });
      } else {
        this.setData({ fromAccountIndex: index, showAccountSheet: false });
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

  // 选择并上传附件（追加，最多 5 张）
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

  async onSubmit() {
    if (this.data.submitting) return;

    const {
      id,
      type,
      categories,
      categoryIndex,
      items,
      itemIndex,
      accounts,
      accountIndex,
      amount,
      date,
      time,
      remark,
    } = this.data;

    if (!id) {
      wx.showToast({ title: '参数错误', icon: 'none' });
      return;
    }

    const amt = Number(amount);
    if (!amount || amt <= 0) {
      wx.showToast({ title: '请输入金额', icon: 'none' });
      return;
    }
    if (!categories || categoryIndex < 0 || !categories[categoryIndex]) {
      wx.showToast({ title: '请选择分类', icon: 'none' });
      return;
    }
    const categoryId = categories[categoryIndex].id;

    let fromAccountId: number | null = null;
    let toAccountId: number | null = null;

    if (type === 'transfer') {
      const fromIndex = this.data.fromAccountIndex;
      const toIndex = this.data.toAccountIndex;
      if (!accounts || fromIndex < 0 || !accounts[fromIndex]) {
        wx.showToast({ title: '请选择转出账户', icon: 'none' });
        return;
      }
      if (!accounts || toIndex < 0 || !accounts[toIndex]) {
        wx.showToast({ title: '请选择转入账户', icon: 'none' });
        return;
      }
      fromAccountId = accounts[fromIndex].id;
      toAccountId = accounts[toIndex].id;
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
        toAccountId = null;
      } else {
        toAccountId = accountId;
        fromAccountId = null;
      }
    }
    const itemId =
      items && itemIndex >= 0 && items[itemIndex] ? items[itemIndex].id : null;

    const d = date || new Date().toISOString().slice(0, 10);
    const t = time || '00:00';
    const transTime = d + ' ' + t + ':00';

    const payload: any = {
      id,
      type,
      category_id: categoryId,
      amount: amt,
      trans_time: transTime,
      remark: remark || '',
      attachment_paths: (this.data.attachments || []).map((a) => a.path).filter((p) => !!p),
    };
    if (itemId) {
      payload.item_id = itemId;
    }
    if (fromAccountId !== null) {
      payload.from_account_id = fromAccountId;
    }
    if (toAccountId !== null) {
      payload.to_account_id = toAccountId;
    }

    this.setData({ submitting: true });
    try {
      const res: any = await editReq.request({
        route: 'transactions/update',
        method: 'POST',
        data: payload,
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '保存失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '保存成功', icon: 'success' });
      setTimeout(() => {
        wx.navigateBack({ delta: 1 });
      }, 600);
    } catch (e) {
      wx.showToast({ title: '请求失败', icon: 'none' });
    } finally {
      this.setData({ submitting: false });
    }
  },

  // 阻止弹层内部点击冒泡关闭
  noop() {},
});