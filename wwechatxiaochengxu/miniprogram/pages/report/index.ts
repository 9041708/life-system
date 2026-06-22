// miniprogram/pages/report/index.ts
const reportReq = require('../../utils/request');
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

Page({
  data: {
    // 顶部子页：overview / category / item
    activeTab: 'overview',

    // 时间维度：year / quarter / month / custom
    mode: 'month',
    year: 0,
    month: 0,
    quarter: 0,

    // 当前统计区间
    dateFrom: '',
    dateTo: '',
    periodTitle: '',

    // 汇总
    totalIncome: 0,
    totalExpense: 0,
    totalNet: 0,
    totalIncomeLast: 0,
    totalExpenseLast: 0,
    totalNetLast: 0,
    totalIncomeCount: 0,
    totalExpenseCount: 0,
    totalCount: 0,

    // 是否对比去年
    compareLastYear: false,

    // 趋势图数据
    labels: [] as string[],
    incomeSeries: [] as number[],
    expenseSeries: [] as number[],
    incomeLastSeries: [] as number[],
    expenseLastSeries: [] as number[],
    chartMode: 'expense' as 'expense' | 'income' | 'net',
    chartBars: [] as any[],
    chartAverage: 0,

    // 分类 & 项目统计
    categoryType: 'expense' as 'expense' | 'income',
    categoryStats: [] as any[],
    itemType: 'expense' as 'expense' | 'income',
    itemStats: [] as any[],

    // 自定义时间
    showFilterPanel: false,
    customFrom: '',
    customTo: '',

    loading: false,
  },

  async onLoad() {
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth() + 1;
    const q = Math.ceil(m / 3);
    this.setData({
      year: y,
      month: m,
      quarter: q,
    });
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: '三石记账 · 报表分析',
        path: '/pages/report/index',
      });
    } catch (e) {}

    this.reload();
  },

  onPullDownRefresh() {
    this.reload();
  },

  async reload() {
    await this.loadSummary();
    await this.loadCategoryStats();
  },

  // 调用 reports/summary 拿到总收入/支出 + 时间范围
  async loadSummary() {
    this.setData({ loading: true });
    try {
      const data: any = {
        mode: this.data.mode,
        year: this.data.year,
        month: this.data.month,
        quarter: this.data.quarter,
        date_from: this.data.mode === 'custom' ? this.data.dateFrom : undefined,
        date_to: this.data.mode === 'custom' ? this.data.dateTo : undefined,
        compare_last_year: this.data.compareLastYear ? 1 : 0,
      };
      const res: any = await reportReq.request({
        route: 'reports/summary',
        method: 'GET',
        data,
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载失败', icon: 'none' });
        return;
      }

      const totalIncome = Number((res.totalIncome || 0).toFixed(2));
      const totalExpense = Number((res.totalExpense || 0).toFixed(2));
      const totalNet = Number((totalIncome - totalExpense).toFixed(2));

  const totalIncomeLast = Number((res.totalIncomeLast || 0).toFixed(2));
  const totalExpenseLast = Number((res.totalExpenseLast || 0).toFixed(2));
  const totalNetLast = Number((totalIncomeLast - totalExpenseLast).toFixed(2));

      const labels: string[] = (res.labels || []) as string[];
      const incomeSeries: number[] = (res.incomeData || []).map((n: any) => Number(n || 0));
      const expenseSeries: number[] = (res.expenseData || []).map((n: any) => Number(n || 0));

      const incomeLastSeries: number[] = (res.incomeLastData || []).map((n: any) => Number(n || 0));
      const expenseLastSeries: number[] = (res.expenseLastData || []).map((n: any) => Number(n || 0));

      const dateFrom = res.dateFrom || '';
      const dateTo = res.dateTo || '';
      const periodTitle = this.buildPeriodTitle(this.data.mode, this.data.year, this.data.month, this.data.quarter, dateFrom, dateTo);

      this.setData({
        totalIncome,
        totalExpense,
        totalNet,
        totalIncomeCount: Number(res.totalIncomeCount || 0),
        totalExpenseCount: Number(res.totalExpenseCount || 0),
        totalCount: Number(res.totalCount || 0),
        totalIncomeLast,
        totalExpenseLast,
        totalNetLast,
        dateFrom,
        dateTo,
        periodTitle,
        labels,
        incomeSeries,
        expenseSeries,
        incomeLastSeries,
        expenseLastSeries,
      });
      this.rebuildChart();
    } catch (e) {
      wx.showToast({ title: '请求失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  // 开关：对比去年
  async onToggleCompare(e: any) {
    const checked = !!(e.detail && e.detail.value);
    this.setData({ compareLastYear: checked });
    await this.reload();
  },

  // 用 transactions/list 在当前时间范围内统计“按分类汇总”
  async loadCategoryStats() {
    if (!this.data.dateFrom || !this.data.dateTo) {
      return;
    }
    this.setData({ loading: true });
    try {
      const res: any = await reportReq.request({
        route: 'transactions/list',
        method: 'GET',
        data: {
          date_from: this.data.dateFrom,
          date_to: this.data.dateTo,
          type: this.data.categoryType,
          page: 1,
          page_size: 500, // 简单版拉前 500 条足够日常使用
        },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载明细失败', icon: 'none' });
        return;
      }
      const list: any[] = res.transactions || [];
      const map: any = {};
      let total = 0;
      for (let i = 0; i < list.length; i++) {
        const t = list[i];
        const name = t.category_name || '未分类';
        const amt = Number(t.amount || 0);
        if (!map[name]) {
          map[name] = 0;
        }
        map[name] += amt;
        total += amt;
      }

      const stats: any[] = [];
      for (const name in map) {
        const sum = map[name];
        const percent = total > 0 ? (sum / total * 100) : 0;
        stats.push({
          name,
          amount: Number(sum.toFixed(2)),
          percent: Number(percent.toFixed(1)),
        });
      }
      // 按金额从大到小排
      stats.sort((a, b) => b.amount - a.amount);
      this.setData({
        categoryStats: stats,
      });
    } catch (e) {
      wx.showToast({ title: '请求失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  // 项目统计：同样基于 transactions/list，按项目名称汇总
  async loadItemStats() {
    if (!this.data.dateFrom || !this.data.dateTo) {
      return;
    }
    this.setData({ loading: true });
    try {
      const res: any = await reportReq.request({
        route: 'transactions/list',
        method: 'GET',
        data: {
          date_from: this.data.dateFrom,
          date_to: this.data.dateTo,
          type: this.data.itemType,
          page: 1,
          page_size: 500,
        },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载明细失败', icon: 'none' });
        return;
      }
      const list: any[] = res.transactions || [];
      const map: any = {};
      let total = 0;
      for (let i = 0; i < list.length; i++) {
        const t = list[i];
        const name = t.item_name || '未指定项目';
        const amt = Number(t.amount || 0);
        if (!map[name]) {
          map[name] = 0;
        }
        map[name] += amt;
        total += amt;
      }
      const stats: any[] = [];
      for (const name in map) {
        const sum = map[name];
        const percent = total > 0 ? (sum / total * 100) : 0;
        stats.push({
          name,
          amount: Number(sum.toFixed(2)),
          percent: Number(percent.toFixed(1)),
        });
      }
      stats.sort((a, b) => b.amount - a.amount);
      this.setData({ itemStats: stats });
    } catch (e) {
      wx.showToast({ title: '请求失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  // 顶部时间模式切换
  async onModeTap(e: any) {
    const m = e.currentTarget.dataset.mode;
    if (!m || m === this.data.mode) return;
    this.setData({ mode: m });
    await this.reload();
  },

  // 构建区间标题
  buildPeriodTitle(mode: string, year: number, month: number, quarter: number, from: string, to: string): string {
    if (mode === 'year') {
      return `${year}年`;
    }
    if (mode === 'quarter') {
      return `${year}年第${quarter}季度`;
    }
    if (mode === 'month') {
      const mm = month < 10 ? '0' + month : '' + month;
      return `${year}年${mm}月`;
    }
    if (mode === 'custom') {
      return '自定义';
    }
    return `${from} ~ ${to}`;
  },

  // 重新构建趋势图柱状数据
  rebuildChart() {
    const { labels, incomeSeries, expenseSeries, incomeLastSeries, expenseLastSeries, chartMode, compareLastYear } = this.data as any;
    if (!labels || labels.length === 0) {
      this.setData({ chartBars: [], chartAverage: 0 });
      return;
    }
    const maxBarHeight = 180; // 每根柱子的最大高度（rpx）
    const values: number[] = [];
    const allValues: number[] = [];
    const bars: any[] = [];
    for (let i = 0; i < labels.length; i++) {
      const inc = Number(incomeSeries[i] || 0);
      const exp = Number(expenseSeries[i] || 0);
      const incLast = Number(incomeLastSeries && incomeLastSeries[i] != null ? incomeLastSeries[i] : 0);
      const expLast = Number(expenseLastSeries && expenseLastSeries[i] != null ? expenseLastSeries[i] : 0);

      let vNow = 0;
      let vLast = 0;
      if (chartMode === 'income') {
        vNow = inc;
        vLast = incLast;
      } else if (chartMode === 'expense') {
        vNow = exp;
        vLast = expLast;
      } else {
        vNow = inc - exp;
        vLast = incLast - expLast;
      }

      values.push(vNow);
      allValues.push(vNow);
      if (compareLastYear) {
        allValues.push(vLast);
      }
    }
    const max = Math.max(...allValues.map((v) => Math.abs(v)), 0);
    const sum = values.reduce((a, b) => a + b, 0);
    const avg = values.length > 0 ? sum / values.length : 0;
    for (let i = 0; i < labels.length; i++) {
      const rawLabel = labels[i];
      const parts = rawLabel.split('-');
      const shortLabel = parts.length === 3 ? parts[2] + '日' : rawLabel;
      const inc = Number(incomeSeries[i] || 0);
      const exp = Number(expenseSeries[i] || 0);
      const incLast = Number(incomeLastSeries && incomeLastSeries[i] != null ? incomeLastSeries[i] : 0);
      const expLast = Number(expenseLastSeries && expenseLastSeries[i] != null ? expenseLastSeries[i] : 0);

      let vNow = 0;
      let vLast = 0;
      if (chartMode === 'income') {
        vNow = inc;
        vLast = incLast;
      } else if (chartMode === 'expense') {
        vNow = exp;
        vLast = expLast;
      } else {
        vNow = inc - exp;
        vLast = incLast - expLast;
      }

      let hNow = max > 0 ? Math.round((Math.abs(vNow) / max) * maxBarHeight) : 0;
      let hLast = max > 0 ? Math.round((Math.abs(vLast) / max) * maxBarHeight) : 0;
      if (hNow > 0 && hNow < 8) {
        hNow = 8; // 最小高度，避免肉眼看不到
      }
      if (hLast > 0 && hLast < 8) {
        hLast = 8;
      }
      bars.push({
        label: shortLabel,
        valueNow: Number(vNow.toFixed(2)),
        valueLast: Number(vLast.toFixed(2)),
        heightNow: hNow,
        heightLast: hLast,
      });
    }
    this.setData({
      chartBars: bars,
      chartAverage: Number(avg.toFixed(2)),
    });
  },

  // 图表模式切换：支出/收入/结余
  onChartModeTap(e: any) {
    const m = e.currentTarget.dataset.mode;
    if (!m || m === this.data.chartMode) return;
    this.setData({ chartMode: m });
    this.rebuildChart();
  },

  // 子页切换：总览 / 分类 / 项目
  async onTabChange(e: any) {
    const tab = e.currentTarget.dataset.tab;
    if (!tab || tab === this.data.activeTab) return;
    this.setData({ activeTab: tab });
    if (tab === 'category') {
      await this.loadCategoryStats();
    } else if (tab === 'item') {
      await this.loadItemStats();
    }
  },

  // 分类统计：支出/收入切换
  async onCategoryTypeChange(e: any) {
    const t = e.currentTarget.dataset.type;
    if (!t || t === this.data.categoryType) return;
    this.setData({ categoryType: t });
    await this.loadCategoryStats();
  },

  // 项目统计：支出/收入切换
  async onItemTypeChange(e: any) {
    const t = e.currentTarget.dataset.type;
    if (!t || t === this.data.itemType) return;
    this.setData({ itemType: t });
    await this.loadItemStats();
  },

  // 上一周期 / 下一周期
  async onPrevPeriod() {
    let { mode, year, month, quarter } = this.data as any;
    if (mode === 'year') {
      year -= 1;
    } else if (mode === 'quarter') {
      quarter -= 1;
      if (quarter <= 0) {
        quarter = 4;
        year -= 1;
      }
    } else if (mode === 'month') {
      month -= 1;
      if (month <= 0) {
        month = 12;
        year -= 1;
      }
    }
    this.setData({ year, month, quarter });
    await this.reload();
  },

  async onNextPeriod() {
    let { mode, year, month, quarter } = this.data as any;
    if (mode === 'year') {
      year += 1;
    } else if (mode === 'quarter') {
      quarter += 1;
      if (quarter > 4) {
        quarter = 1;
        year += 1;
      }
    } else if (mode === 'month') {
      month += 1;
      if (month > 12) {
        month = 1;
        year += 1;
      }
    }
    this.setData({ year, month, quarter });
    await this.reload();
  },

  // 打开自定义时间面板
  onOpenFilter() {
    this.setData({
      showFilterPanel: true,
      customFrom: this.data.dateFrom,
      customTo: this.data.dateTo,
    });
  },

  onCloseFilter() {
    this.setData({ showFilterPanel: false });
  },

  onPickCustomFrom(e: any) {
    this.setData({ customFrom: e.detail.value });
  },

  onPickCustomTo(e: any) {
    this.setData({ customTo: e.detail.value });
  },

  async onConfirmFilter() {
    const from = (this.data.customFrom || '').trim();
    const to = (this.data.customTo || '').trim();
    if (!from || !to) {
      wx.showToast({ title: '请选择开始和结束日期', icon: 'none' });
      return;
    }
    if (from > to) {
      wx.showToast({ title: '开始日期不能晚于结束日期', icon: 'none' });
      return;
    }
    this.setData({
      mode: 'custom',
      dateFrom: from,
      dateTo: to,
      showFilterPanel: false,
    });
    await this.reload();
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});