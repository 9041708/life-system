// miniprogram/pages/reimbursement/list.ts
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
    activeTab: 'situation' as 'situation' | 'statistics',
    enabled: true,

    // 报销列表（合并未报销+已报销）
    items: [] as any[],
    overview: {
      pendingAmount: '0.00',
      reimbursedAmount: '0.00',
      totalCount: 0,
    },

    // 统计
    monthly: [] as any[],
    category: [] as any[],
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: '三石记账 · 报销管理',
        path: '/pages/reimbursement/list',
      });
    } catch (e) {}

    this.syncThemeFromGlobal();
    await this.checkEnabled();
  },

  async onShow() {
    this.syncThemeFromGlobal();
    if (this.data.enabled) {
      await this.reload();
    }
  },

  onPullDownRefresh() {
    if (this.data.enabled) {
      this.reload();
    } else {
      wx.stopPullDownRefresh();
    }
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

  async checkEnabled() {
    try {
      const res: any = await request({
        route: 'reimbursement/config',
        method: 'GET',
      });
      const enabled = res && res.success && res.config && res.config.enabled !== false;
      this.setData({ enabled });
      if (!enabled) {
        wx.showModal({
          title: '功能未开启',
          content: '报销管理功能未开启，请先到"我的"页面开启。',
          showCancel: false,
          success: () => {
            wx.navigateBack();
          },
        });
        return;
      }
      await this.reload();
    } catch (e) {
      this.setData({ enabled: false });
      wx.showModal({
        title: '功能未开启',
        content: '报销管理功能未开启，请先到"我的"页面开启。',
        showCancel: false,
        success: () => {
          wx.navigateBack();
        },
      });
    }
  },

  async reload() {
    if (this.data.activeTab === 'situation') {
      await this.loadSituation();
    } else {
      await this.loadStatistics();
    }
  },

  // ========== Tab 切换 ==========
  onTabSituation() {
    if (this.data.activeTab === 'situation') return;
    this.setData({ activeTab: 'situation' });
    this.loadSituation();
  },

  onTabStatistics() {
    if (this.data.activeTab === 'statistics') return;
    this.setData({ activeTab: 'statistics' });
    this.loadStatistics();
  },

  // ========== 报销列表 ==========
  async loadSituation() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      // 加载全部报销记录（后端合并返回 pending + completed）
      const res: any = await request({
        route: 'reimbursement/list',
        method: 'GET',
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载失败', icon: 'none' });
        return;
      }

      const items: any[] = res.items || [];
      items.forEach((item) => {
        item.amountDisplay = formatAmount(item.amount);
        item.status_text = item.status === 'pending' ? '待报销' :
          item.status === 'approved' ? '已批准' :
            item.status === 'rejected' ? '已拒绝' :
              item.status === 'reimbursed' ? '已报销' :
                item.status === 'completed' ? '已完成' : '未知';
      });

      const overview = res.overview || {};
      this.setData({
        items,
        overview: {
          pendingAmount: formatAmount(overview.pending_amount || 0),
          reimbursedAmount: formatAmount(overview.reimbursed_amount || 0),
          totalCount: Number(overview.total_count || 0),
        },
      });
    } catch (e) {
      wx.showToast({ title: '网络异常', icon: 'none' });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  async loadList() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: 'reimbursement/pending',
        method: 'GET',
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载失败', icon: 'none' });
        return;
      }

      const items: any[] = res.items || [];
      items.forEach((item) => {
        item.amountDisplay = formatAmount(item.amount);
        item.status_text = item.status === 'pending' ? '待报销' :
          item.status === 'approved' ? '已批准' :
            item.status === 'rejected' ? '已拒绝' :
              item.status === 'reimbursed' ? '已报销' : '未知';
      });

      const overview = res.overview || {};
      this.setData({
        items,
        overview: {
          pendingAmount: formatAmount(overview.pending_amount || 0),
          reimbursedAmount: formatAmount(overview.reimbursed_amount || 0),
          totalCount: Number(overview.total_count || 0),
        },
      });
    } catch (e) {
      wx.showToast({ title: '网络异常', icon: 'none' });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  // ========== 统计 ==========
  async loadStatistics() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: 'reimbursement/overview',
        method: 'GET',
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '加载失败', icon: 'none' });
        return;
      }

      const overview = res.overview || {};
      const monthly: any[] = (res.monthly || []).map((m: any) => ({
        ...m,
        amountDisplay: formatAmount(m.amount),
      }));

      this.setData({
        overview: {
          pendingAmount: formatAmount(overview.pending_amount || 0),
          reimbursedAmount: formatAmount(overview.reimbursed_amount || 0),
          totalCount: Number(overview.total_count || 0),
        },
        monthly,
        category: res.category || [],
      });
    } catch (e) {
      wx.showToast({ title: '网络异常', icon: 'none' });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  // ========== 新增报销 ==========
  async onAddReimbursement() {
    const titleRes: any = await wx.showModal({
      title: '报销标题',
      editable: true,
      placeholderText: '例如：出差交通费',
      content: '',
    } as any);
    if (!titleRes.confirm) return;
    const title = String(titleRes.content || '').trim();
    if (!title) {
      wx.showToast({ title: '标题不能为空', icon: 'none' });
      return;
    }

    const amountRes: any = await wx.showModal({
      title: '报销金额',
      editable: true,
      placeholderText: '请输入金额，例如 299',
      content: '',
    } as any);
    if (!amountRes.confirm) return;
    const amountStr = String(amountRes.content || '').trim();
    const amount = Number(amountStr || 0);
    if (!amount || amount <= 0) {
      wx.showToast({ title: '金额需大于 0', icon: 'none' });
      return;
    }

    const descRes: any = await wx.showModal({
      title: '描述（可留空）',
      editable: true,
      placeholderText: '例如：2026年5月出差',
      content: '',
    } as any);
    const description = descRes.confirm ? String(descRes.content || '').trim() : '';

    try {
      wx.showLoading({ title: '保存中…', mask: true });
      const res: any = await request({
        route: 'reimbursement/save',
        method: 'POST',
        data: { title, amount, description },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '保存失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已保存', icon: 'success' });
      this.loadList();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: '请求失败', icon: 'none' });
    }
  },

  // ========== 长按操作 ==========
  async onItemLongPress(e: any) {
    const item = e.currentTarget.dataset.item;
    if (!item) return;

    const that = this;
    wx.showActionSheet({
      itemList: ['标记已报销', '删除'],
      success: async (res) => {
        if (res.tapIndex === 0) {
          await that.markReimbursed(item);
        } else if (res.tapIndex === 1) {
          await that.deleteReimbursement(item);
        }
      },
    });
  },

  async markReimbursed(item: any) {
    try {
      wx.showLoading({ title: '处理中…', mask: true });
      const res: any = await request({
        route: 'reimbursement/mark-reimbursed',
        method: 'POST',
        data: { id: item.id },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '操作失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已标记报销', icon: 'success' });
      this.loadList();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: '请求失败', icon: 'none' });
    }
  },

  async deleteReimbursement(item: any) {
    const confirmRes = await wx.showModal({
      title: '删除报销记录',
      content: '确定要删除该报销记录吗？',
    } as any);
    if (!confirmRes.confirm) return;

    try {
      wx.showLoading({ title: '删除中…', mask: true });
      const res: any = await request({
        route: 'reimbursement/delete',
        method: 'POST',
        data: { id: item.id },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || '删除失败', icon: 'none' });
        return;
      }
      wx.showToast({ title: '已删除', icon: 'success' });
      this.loadList();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: '请求失败', icon: 'none' });
    }
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});