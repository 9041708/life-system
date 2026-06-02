// miniprogram/pages/transaction/list.ts
const requestUtil = require('../../utils/request');
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

Page({
  data: {
    // 列表数据
    transactions: [] as any[],
    summary: {
      income: 0,
      expense: 0,
    },
    page: 1,
    pageSize: 20,
    hasMore: true,
    loading: false,

    // 类型筛选
    typeFilter: 'all', // all / expense / income

    // 是否展开高级筛选（分类/项目/账户/时间/搜索）
    filtersExpanded: false,

    // 分类 / 项目筛选
    categories: [] as any[],
    categoryIndex: 0,
    items: [] as any[],
    itemIndex: 0,

    // 账户筛选
    accounts: [] as any[],
    accountIndex: 0,

    // 时间段筛选
    dateFrom: '',
    dateTo: '',

    // 搜索：备注关键字 & 金额区间
    keyword: '',
    amountMin: '',
    amountMax: '',
  },

  onToggleFilters() {
    this.setData({
      filtersExpanded: !this.data.filtersExpanded,
    });
  },

  async onLoad() {
    await this.loadAccounts();
    await this.loadCategories();
    await this.reload();

    // 初始化分享配置：流水明细
    try {
      await initShare(this, {
        title: '三石记账 · 流水明细',
        path: '/pages/transaction/list',
      });
    } catch (e) {}
  },
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },

  async reload() {
    this.setData({
      page: 1,
      hasMore: true,
      transactions: [],
    });
    await this.loadMore();
  },

  async loadMore() {
    if (this.data.loading || !this.data.hasMore) {
      return;
    }
    this.setData({ loading: true });

    const page = this.data.page;
    const pageSize = this.data.pageSize;
    const query: any = {
      page: page,
      page_size: pageSize,
    };

    if (this.data.typeFilter === 'income' || this.data.typeFilter === 'expense') {
      query.type = this.data.typeFilter;
    }

    const categories: any[] = this.data.categories;
    const categoryIndex = this.data.categoryIndex;
    if (categories && categories[categoryIndex] && categories[categoryIndex].id) {
      query.category_id = categories[categoryIndex].id;
    }

    const items: any[] = this.data.items;
    const itemIndex = this.data.itemIndex;
    if (items && items[itemIndex] && items[itemIndex].id) {
      query.item_id = items[itemIndex].id;
    }

    const accounts: any[] = this.data.accounts;
    const accountIndex = this.data.accountIndex;
    if (accounts && accounts[accountIndex] && accounts[accountIndex].id) {
      query.account_id = accounts[accountIndex].id;
    }

    if (this.data.dateFrom) {
      query.date_from = this.data.dateFrom;
    }
    if (this.data.dateTo) {
      query.date_to = this.data.dateTo;
    }

    // 金额区间
    if (this.data.amountMin) {
      query.amount_min = this.data.amountMin;
    }
    if (this.data.amountMax) {
      query.amount_max = this.data.amountMax;
    }

    // 备注关键字
    if (this.data.keyword) {
      query.remark = this.data.keyword;
    }

    try {
      const res: any = await requestUtil.request({
        route: 'transactions/list',
        method: 'GET',
        data: query,
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载失败', icon: 'none' });
        return;
      }

      const records: any[] = res.transactions || [];
      for (let i = 0; i < records.length; i++) {
        const t = records[i];
        const ts: string = t.trans_time || '';
        let d = '';
        let tm = '';
        if (ts && ts.length >= 16) {
          d = ts.substr(0, 10);
          tm = ts.substr(11, 5);
        }
        t._date = d;
        t._time = tm;

        // 处理图标：分类 / 项目 / 账户
        t._categoryIconUrl = t.category_icon_url || '';
        t._itemIconUrl = t.item_icon_url || '';

        const fromIcon = t.from_account_icon_url || '';
        const toIcon = t.to_account_icon_url || '';
        if (t.type === 'income') {
          t._accountIconUrl = toIcon || fromIcon || '';
        } else {
          t._accountIconUrl = fromIcon || toIcon || '';
        }
      }

      const list = this.data.transactions.concat(records);
      const pagination = res.pagination || {};
      const total = pagination.total || list.length;
      const hasMore = page * pageSize < total;

      this.setData({
        transactions: list,
        summary: res.summary || { income: 0, expense: 0 },
        page: page + 1,
        hasMore: hasMore,
      });
    } catch (e) {
      wx.showToast({ title: '请求失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  onReachBottom() {
    this.loadMore();
  },

  onPullDownRefresh() {
    this.reload();
  },

  // 账户
  async loadAccounts() {
    try {
      const res: any = await requestUtil.request({
        route: 'accounts/list',
        method: 'GET',
        data: {},
      });
      if (!res || !res.success) {
        this.setData({
          accounts: [{ id: 0, name: '全部账户' }],
          accountIndex: 0,
        });
        return;
      }
      const list = res.accounts || [];
      const acc = [{ id: 0, name: '全部账户' }].concat(list);
      this.setData({
        accounts: acc,
        accountIndex: 0,
      });
    } catch (e) {
      this.setData({
        accounts: [{ id: 0, name: '全部账户' }],
        accountIndex: 0,
      });
    }
  },

  // 分类
  async loadCategories() {
    this.setData({
      categories: [],
      categoryIndex: 0,
      items: [],
      itemIndex: 0,
    });

    const data: any = {};
    if (this.data.typeFilter === 'income' || this.data.typeFilter === 'expense') {
      data.type = this.data.typeFilter;
    }

    try {
      const res: any = await requestUtil.request({
        route: 'categories/list',
        method: 'GET',
        data,
      });
      if (!res || !res.success) {
        this.setData({
          categories: [{ id: 0, name: '全部分类' }],
          categoryIndex: 0,
          items: [{ id: 0, name: '全部项目' }],
          itemIndex: 0,
        });
        return;
      }
      const list = res.categories || [];
      const cats = [{ id: 0, name: '全部分类' }].concat(list);
      this.setData({
        categories: cats,
        categoryIndex: 0,
        items: [{ id: 0, name: '全部项目' }],
        itemIndex: 0,
      });
    } catch (e) {
      this.setData({
        categories: [{ id: 0, name: '全部分类' }],
        categoryIndex: 0,
        items: [{ id: 0, name: '全部项目' }],
        itemIndex: 0,
      });
    }
  },

  async loadItems() {
    const categories: any[] = this.data.categories;
    const categoryIndex = this.data.categoryIndex;

    if (!categories || !categories[categoryIndex] || !categories[categoryIndex].id) {
      this.setData({
        items: [{ id: 0, name: '全部项目' }],
        itemIndex: 0,
      });
      return;
    }

    const categoryId = categories[categoryIndex].id;

    try {
      const res: any = await requestUtil.request({
        route: 'items/list',
        method: 'GET',
        data: { category_id: categoryId },
      });
      if (!res || !res.success) {
        this.setData({
          items: [{ id: 0, name: '全部项目' }],
          itemIndex: 0,
        });
        return;
      }
      const list = res.items || [];
      const items = [{ id: 0, name: '全部项目' }].concat(list);
      this.setData({
        items,
        itemIndex: 0,
      });
    } catch (e) {
      this.setData({
        items: [{ id: 0, name: '全部项目' }],
        itemIndex: 0,
      });
    }
  },

  async onTypeFilterTap(e: any) {
    const t = e.currentTarget.dataset.type;
    if (!t || t === this.data.typeFilter) return;

    this.setData({ typeFilter: t });
    await this.loadCategories();
    await this.reload();
  },

  async onCategoryChange(e: any) {
    const index = Number(e.detail.value);
    this.setData({ categoryIndex: index, itemIndex: 0 });
    await this.loadItems();
    await this.reload();
  },

  async onItemChange(e: any) {
    const index = Number(e.detail.value);
    this.setData({ itemIndex: index });
    await this.reload();
  },

  async onAccountChange(e: any) {
    const index = Number(e.detail.value);
    this.setData({ accountIndex: index });
    await this.reload();
  },

  async onDateFromChange(e: any) {
    this.setData({ dateFrom: e.detail.value });
    await this.reload();
  },

  async onDateToChange(e: any) {
    this.setData({ dateTo: e.detail.value });
    await this.reload();
  },

  // 搜索输入
  onKeywordInput(e: any) {
    this.setData({ keyword: e.detail.value });
  },
  onAmountMinInput(e: any) {
    this.setData({ amountMin: e.detail.value });
  },
  onAmountMaxInput(e: any) {
    this.setData({ amountMax: e.detail.value });
  },

  // 点击搜索按钮
  onSearchSubmit() {
    this.reload();
  },

  // 点按：去编辑页
  onItemTap(e: any) {
    const tx = e.currentTarget.dataset.tx;
    if (!tx) return;
    const encoded = encodeURIComponent(JSON.stringify(tx));
    wx.navigateTo({
      url: '/pages/transaction/edit?data=' + encoded,
    });
  },

  // 长按：删除
  onItemLongPress(e: any) {
    const tx = e.currentTarget.dataset.tx;
    if (!tx) return;
    wx.showModal({
      title: '提示',
      content: '确定要删除这条记录吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          const r: any = await requestUtil.request({
            route: 'transactions/delete',
            method: 'POST',
            data: {
              ids: [tx.id],
            },
          });
          if (!r || !r.success) {
            wx.showToast({ title: (r && r.error) || '删除失败', icon: 'none' });
            return;
          }
          wx.showToast({ title: '已删除', icon: 'success' });
          this.reload();
        } catch (err) {
          wx.showToast({ title: '请求失败', icon: 'none' });
        }
      },
    });
  },

  // 点击缩略图：预览大图
  onPreviewAttachment(e: any) {
    const url = e.currentTarget.dataset.url as string;
    const urls = (e.currentTarget.dataset.urls as string[]) || [];
    if (!url) return;

    wx.previewImage({
      current: url,
      urls: urls && urls.length > 0 ? urls : [url],
    });
  },
});