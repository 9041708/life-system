// miniprogram/pages/debt/list.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

function formatAmount(raw: any): string {
  const n = Number(raw || 0);
  return n.toFixed(2);
}

Page({
  data: {
    loading: false,
    theme: 'light' as 'light' | 'dark',
    activeTab: 'current' as 'current' | 'summary' | 'config',

    // 当月应还
    payments: [] as any[],
    totalAmount: '0.00',
    paidAmount: '0.00',
    remainingAmount: '0.00',

    // 汇总统计
    summary: [] as any[],
    totalPeriods: 0,
    paidPeriods: 0,
    progressPercent: 0,

    // 负债配置
    configs: [] as any[],
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: '三石记账 · 负债管理',
        path: '/pages/debt/list',
      });
    } catch (e) {}

    this.syncThemeFromGlobal();
    this.loadCurrentMonth();
  },

  onShow() {
    this.syncThemeFromGlobal();
    this.reload();
  },

  onPullDownRefresh() {
    this.reload();
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

  async reload() {
    if (this.data.activeTab === 'current') {
      await this.loadCurrentMonth();
    } else if (this.data.activeTab === 'summary') {
      await this.loadSummary();
    } else {
      await this.loadConfigs();
    }
  },

  // ========== Tab 切换 ==========
  onTabCurrent() {
    if (this.data.activeTab === 'current') return;
    this.setData({ activeTab: 'current' });
    this.loadCurrentMonth();
  },

  onTabSummary() {
    if (this.data.activeTab === 'summary') return;
    this.setData({ activeTab: 'summary' });
    this.loadSummary();
  },

  onTabConfig() {
    if (this.data.activeTab === 'config') return;
    this.setData({ activeTab: 'config' });
    this.loadConfigs();
  },

  // ========== 当月应还 ==========
  async loadCurrentMonth() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: 'debt/current-month',
        method: 'GET',
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载失败', icon: 'none' });
        return;
      }

      const payments: any[] = res.payments || [];
      let totalAmount = 0;
      let paidAmount = 0;

      payments.forEach((p) => {
        const amount = Number(p.total_amount || 0);
        const paid = Number(p.paid_amount || 0);
        p.amountDisplay = formatAmount(amount);
        p.paidAmountDisplay = formatAmount(paid);
        p.remainingAmountDisplay = formatAmount(amount - paid);
        p.status_text = p.status === 'paid' ? '已还' : '未还';
        totalAmount += amount;
        if (p.status === 'paid') {
          paidAmount += paid;
        }
      });

      this.setData({
        payments,
        totalAmount: formatAmount(res.total_amount || totalAmount),
        paidAmount: formatAmount(res.paid_amount || paidAmount),
        remainingAmount: formatAmount(res.remaining_amount || (totalAmount - paidAmount)),
      });
    } catch (e) {
      wx.showToast({ title: '网络异常', icon: 'none' });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  async onMarkPaid(e: any) {
    const payment = e.currentTarget.dataset.payment;
    if (!payment) return;

    const paidRes: any = await wx.showModal({
      title: '标记还款',
      editable: true,
      placeholderText: '输入实际还款金额',
      content: String(payment.total_amount || ''),
    } as any);
    if (!paidRes.confirm) return;
    const paidAmount = Number(String(paidRes.content || '').trim());
    if (!paidAmount || paidAmount <= 0) {
      wx.showToast({ title: '还款金额需大于 0', icon: 'none' });
      return;
    }

    try {
      wx.showLoading({ title: '处理中…', mask: true });
      const res: any = await request({
        route: 'debt/mark-paid',
        method: 'POST',
        data: { payment_id: payment.id, paid_amount: paidAmount },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '操作失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已标记还款', icon: 'success' });
      this.loadCurrentMonth();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: '请求失败', icon: 'none' });
    }
  },

  async onUndoPaid(e: any) {
    const payment = e.currentTarget.dataset.payment;
    if (!payment) return;

    const confirmRes = await wx.showModal({
      title: '回退还款',
      content: '确定要回退该期还款记录吗？',
    } as any);
    if (!confirmRes.confirm) return;

    try {
      wx.showLoading({ title: '处理中…', mask: true });
      const res: any = await request({
        route: 'debt/undo-paid',
        method: 'POST',
        data: { payment_id: payment.id },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '操作失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已回退', icon: 'success' });
      this.loadCurrentMonth();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: '请求失败', icon: 'none' });
    }
  },

  // ========== 汇总统计 ==========
  async loadSummary() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: 'debt/summary',
        method: 'GET',
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载失败', icon: 'none' });
        return;
      }

      const summary: any[] = res.summary || [];
      summary.forEach((item) => {
        item.totalPrincipalDisplay = formatAmount(item.total_principal);
        item.totalInterestDisplay = formatAmount(item.total_interest);
        item.totalAmountDisplay = formatAmount(
          Number(item.total_principal) + Number(item.total_interest)
        );
        item.totalPaidDisplay = formatAmount(item.total_paid);
        item.remainingAmountDisplay = formatAmount(item.remaining_amount);
        item.progressPercent = item.installment_count > 0
          ? Math.round((item.paid_periods / item.installment_count) * 100)
          : 0;
      });

      this.setData({
        summary,
        totalPeriods: res.grand_total_periods || 0,
        paidPeriods: res.grand_paid_periods || 0,
        progressPercent: res.grand_progress_percent || 0,
      });
    } catch (e) {
      wx.showToast({ title: '网络异常', icon: 'none' });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  // ========== 负债配置 ==========
  async loadConfigs() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: 'debt/config',
        method: 'GET',
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载失败', icon: 'none' });
        return;
      }

      const configs: any[] = res.configs || [];
      configs.forEach((item) => {
        item.totalPrincipalDisplay = formatAmount(item.total_principal);
        item.totalInterestDisplay = formatAmount(item.total_interest);
        item.remainingAmountDisplay = formatAmount(item.remaining_amount);
      });

      this.setData({ configs });
    } catch (e) {
      wx.showToast({ title: '网络异常', icon: 'none' });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});