// miniprogram/pages/budget/edit.ts
const budgetEditReq = require('../../utils/request');
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

Page({
  data: {
    mode: 'edit' as 'edit' | 'create',
    id: 0,
    year: 0,
    month: 0,

    // 通用字段
    type: 'expense', // expense / income
    categoryId: 0,
    categoryName: '',
    itemId: 0,
    itemName: '',
    amountInput: '',

    // 新增时用到
    categories: [] as any[],
    categoryIndex: 0,
    items: [] as any[],
    itemIndex: 0,
  },

  async onLoad(options: any) {
    const mode = (options.mode || 'edit') as 'edit' | 'create';
    const year = Number(options.year || 0);
    const month = Number(options.month || 0);

    if (mode === 'edit') {
      const id = Number(options.id || 0);
      const type = options.type || 'expense';
      const categoryId = Number(options.category_id || 0);
      const itemId = Number(options.item_id || 0);
      const amount = options.amount || '';

      this.setData({
        mode,
        id,
        year,
        month,
        type,
        categoryId,
        itemId,
        categoryName: decodeURIComponent(options.category_name || ''),
        itemName: decodeURIComponent(options.item_name || ''),
        amountInput: String(amount),
      });
    } else {
      this.setData({
        mode,
        year,
        month,
        type: 'expense',
        amountInput: '',
      });
      await this.loadCategories();
    }

    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: '三石记账 · 预算编辑',
        path: '/pages/budget/edit',
      });
    } catch (e) {}
  },

  // -------- 公共输入 --------
  onAmountInput(e: any) {
    this.setData({ amountInput: e.detail.value });
  },

  // -------- 编辑模式提交（只改金额） --------
  async submitEdit() {
    const id = this.data.id;
    const amountStr = this.data.amountInput.trim();
    if (!id) {
      wx.showToast({ title: '参数错误', icon: 'none' });
      return;
    }
    if (!amountStr) {
      wx.showToast({ title: '请输入预算金额', icon: 'none' });
      return;
    }
    const v = Number(amountStr);
    if (isNaN(v) || v <= 0) {
      wx.showToast({ title: '金额必须大于0', icon: 'none' });
      return;
    }

    try {
      const res: any = await budgetEditReq.request({
        route: 'budget/update-amount',
        method: 'POST',
        data: {
          id: id,
          amount: v,
        },
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
    }
  },

  // -------- 新增模式：加载分类/项目 --------
  async loadCategories() {
    try {
      const res: any = await budgetEditReq.request({
        route: 'categories/list',
        method: 'GET',
        data: { type: this.data.type },
      });
      const list: any[] = (res && res.success && res.categories) ? res.categories : [];
      const cats = [{ id: 0, name: '全部分类' }].concat(list);
      this.setData({
        categories: cats,
        categoryIndex: 0,
        items: [{ id: 0, name: '全部项目' }],
        itemIndex: 0,
        categoryId: 0,
        itemId: 0,
      });
    } catch (e) {
      this.setData({
        categories: [{ id: 0, name: '全部分类' }],
        categoryIndex: 0,
        items: [{ id: 0, name: '全部项目' }],
        itemIndex: 0,
        categoryId: 0,
        itemId: 0,
      });
    }
  },

  async loadItems() {
    const cats: any[] = this.data.categories;
    const idx = this.data.categoryIndex;
    if (!cats || !cats[idx] || !cats[idx].id) {
      this.setData({
        items: [{ id: 0, name: '全部项目' }],
        itemIndex: 0,
        itemId: 0,
      });
      return;
    }
    const cid = cats[idx].id;
    try {
      const res: any = await budgetEditReq.request({
        route: 'items/list',
        method: 'GET',
        data: { category_id: cid },
      });
      const list: any[] = (res && res.success && res.items) ? res.items : [];
      const items = [{ id: 0, name: '全部项目' }].concat(list);
      this.setData({
        items,
        itemIndex: 0,
        itemId: 0,
      });
    } catch (e) {
      this.setData({
        items: [{ id: 0, name: '全部项目' }],
        itemIndex: 0,
        itemId: 0,
      });
    }
  },

  // -------- 新增模式：交互 --------
  onTypeTap(e: any) {
    const t = e.currentTarget.dataset.type;
    if (!t || t === this.data.type) return;
    this.setData({
      type: t,
      categoryIndex: 0,
      itemIndex: 0,
      categoryId: 0,
      itemId: 0,
    });
    this.loadCategories();
  },

  onCategoryChange(e: any) {
    const idx = Number(e.detail.value);
    const cats: any[] = this.data.categories;
    const cid = (cats && cats[idx]) ? cats[idx].id : 0;
    this.setData({
      categoryIndex: idx,
      categoryId: cid,
      itemIndex: 0,
      itemId: 0,
    });
    this.loadItems();
  },

  onItemChange(e: any) {
    const idx = Number(e.detail.value);
    const items: any[] = this.data.items;
    const iid = (items && items[idx]) ? items[idx].id : 0;
    this.setData({
      itemIndex: idx,
      itemId: iid,
    });
  },

  async submitCreate() {
    const amountStr = this.data.amountInput.trim();
    if (!amountStr) {
      wx.showToast({ title: '请输入预算金额', icon: 'none' });
      return;
    }
    const v = Number(amountStr);
    if (isNaN(v) || v <= 0) {
      wx.showToast({ title: '金额必须大于0', icon: 'none' });
      return;
    }

    const type = this.data.type;
    const cid = this.data.categoryId || null;
    const iid = this.data.itemId || null;

    try {
      const res: any = await budgetEditReq.request({
        route: 'budget/upsert',
        method: 'POST',
        data: {
          year: this.data.year,
          month: this.data.month,
          type,
          category_id: cid,
          item_id: iid,
          amount: v,
        },
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
    }
  },

  // -------- 通用提交入口 --------
  onSubmit() {
    if (this.data.mode === 'edit') {
      this.submitEdit();
    } else {
      this.submitCreate();
    }
  },
  
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});