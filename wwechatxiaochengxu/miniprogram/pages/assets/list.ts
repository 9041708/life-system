// miniprogram/pages/assets/list.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

function formatAmount(raw: any): string {
  const n = Number(raw || 0);
  return n.toFixed(2);
}

Page({
  data: {
    loading: false,
    activeTab: "active" as "active" | "transferred",
    assetsActive: [] as any[],
    assetsTransferred: [] as any[],
    summary: {
      assetCount: 0,
      totalValue: "0.00",
      totalDailyCost: "0.00",
    },
  },

  async onLoad() {
    try {
      await initShare(this, {
        title: "三石记账 · 资产管理",
        path: "/pages/assets/list",
      });
    } catch (e) {}

    this.reload();
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },

  async reload() {
    await this.loadAssets();
  },

  async loadAssets() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: "assets/list",
        method: "GET",
        data: {},
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "加载失败", icon: "none" });
        return;
      }

      const active: any[] = (res.assets && res.assets.active) || [];
      const transferred: any[] = (res.assets && res.assets.transferred) || [];

      active.forEach((a) => {
        a._valueDisplay = formatAmount(a.value_amount);
        a._dailyDisplay = formatAmount(a.daily_cost);
      });
      transferred.forEach((a) => {
        a._valueDisplay = formatAmount(a.value_amount);
        a._dailyDisplay = formatAmount(a.daily_cost);
        a._transferPriceDisplay = a.transfer_price != null ? formatAmount(a.transfer_price) : "-";
      });

      const sum = res.summary || {};
      const assetCount = Number(sum.asset_count || 0);
      const totalValue = formatAmount(sum.total_value);
      const totalDailyCost = formatAmount(sum.total_daily_cost);

      this.setData({
        assetsActive: active,
        assetsTransferred: transferred,
        summary: {
          assetCount,
          totalValue,
          totalDailyCost,
        },
      });
    } catch (e) {
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  onPullDownRefresh() {
    this.reload();
  },

  onTabActive() {
    if (this.data.activeTab === "active") return;
    this.setData({ activeTab: "active" });
  },

  onTabTransferred() {
    if (this.data.activeTab === "transferred") return;
    this.setData({ activeTab: "transferred" });
  },

  async onAddAsset() {
    const nameRes: any = await wx.showModal({
      title: "资产名称",
      editable: true,
      placeholderText: "例如：iPhone / 显示器",
      content: "",
    } as any);
    if (!nameRes.confirm) return;
    const name = String(nameRes.content || "").trim();
    if (!name) {
      wx.showToast({ title: "名称不能为空", icon: "none" });
      return;
    }

    const dateRes: any = await wx.showModal({
      title: "到手日期 (YYYY-MM-DD)",
      editable: true,
      placeholderText: "例如：2026-01-01",
      content: "",
    } as any);
    if (!dateRes.confirm) return;
    const acquired_date = String(dateRes.content || "").trim();
    if (!acquired_date) {
      wx.showToast({ title: "到手日期不能为空", icon: "none" });
      return;
    }

    const priceRes: any = await wx.showModal({
      title: "到手价格",
      editable: true,
      placeholderText: "请输入金额，例如 1999",
      content: "",
    } as any);
    if (!priceRes.confirm) return;
    const priceStr = String(priceRes.content || "").trim();
    const value_amount = Number(priceStr || 0);
    if (!value_amount || value_amount <= 0) {
      wx.showToast({ title: "金额需大于 0", icon: "none" });
      return;
    }

    const remarkRes: any = await wx.showModal({
      title: "备注（可留空）",
      editable: true,
      placeholderText: "例如：二手购入 / 公司报销",
      content: "",
    } as any);
    if (!remarkRes.confirm) return;
    const remark = String(remarkRes.content || "").trim();

    try {
      wx.showLoading({ title: "保存中…", mask: true });
      const res: any = await request({
        route: "assets/save",
        method: "POST",
        data: { name, acquired_date, value_amount, remark },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "保存失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "已保存", icon: "success" });
      this.reload();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: "请求失败", icon: "none" });
    }
  },

  async onAssetLongPress(e: any) {
    const asset = e.currentTarget.dataset.asset;
    if (!asset) return;

    const that = this;
    wx.showActionSheet({
      itemList: ["编辑资产", "标记为已转手", "删除资产"],
      success(res) {
        if (res.tapIndex === 0) {
          that.editAsset(asset);
        } else if (res.tapIndex === 1) {
          that.transferAsset(asset);
        } else if (res.tapIndex === 2) {
          that.deleteAsset(asset);
        }
      },
    });
  },

  async editAsset(asset: any) {
    const nameRes: any = await wx.showModal({
      title: "编辑资产名称",
      editable: true,
      placeholderText: "资产名称",
      content: String(asset.name || ""),
    } as any);
    if (!nameRes.confirm) return;
    const name = String(nameRes.content || "").trim();
    if (!name) {
      wx.showToast({ title: "名称不能为空", icon: "none" });
      return;
    }

    const dateRes: any = await wx.showModal({
      title: "到手日期 (YYYY-MM-DD)",
      editable: true,
      placeholderText: "例如：2026-01-01",
      content: String(asset.acquired_date || ""),
    } as any);
    if (!dateRes.confirm) return;
    const acquired_date = String(dateRes.content || "").trim();
    if (!acquired_date) {
      wx.showToast({ title: "到手日期不能为空", icon: "none" });
      return;
    }

    const priceRes: any = await wx.showModal({
      title: "到手价格",
      editable: true,
      placeholderText: "请输入金额",
      content: String(asset.value_amount || ""),
    } as any);
    if (!priceRes.confirm) return;
    const priceStr = String(priceRes.content || "").trim();
    const value_amount = Number(priceStr || 0);
    if (!value_amount || value_amount <= 0) {
      wx.showToast({ title: "金额需大于 0", icon: "none" });
      return;
    }

    const remarkRes: any = await wx.showModal({
      title: "备注（可留空）",
      editable: true,
      placeholderText: "备注",
      content: String(asset.remark || ""),
    } as any);
    if (!remarkRes.confirm) return;
    const remark = String(remarkRes.content || "").trim();

    try {
      wx.showLoading({ title: "保存中…", mask: true });
      const res: any = await request({
        route: "assets/save",
        method: "POST",
        data: { id: asset.id, name, acquired_date, value_amount, remark },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "保存失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "已更新", icon: "success" });
      this.reload();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: "请求失败", icon: "none" });
    }
  },

  async transferAsset(asset: any) {
    const dateRes: any = await wx.showModal({
      title: "转手日期 (YYYY-MM-DD)",
      editable: true,
      placeholderText: "例如：2026-02-01",
      content: String(asset.transfer_date || ""),
    } as any);
    if (!dateRes.confirm) return;
    const transfer_date = String(dateRes.content || "").trim();
    if (!transfer_date) {
      wx.showToast({ title: "转手日期不能为空", icon: "none" });
      return;
    }

    const priceRes: any = await wx.showModal({
      title: "转手价格",
      editable: true,
      placeholderText: "请输入金额，可为 0",
      content: asset.transfer_price != null ? String(asset.transfer_price) : "",
    } as any);
    if (!priceRes.confirm) return;
    const priceStr = String(priceRes.content || "").trim();
    const transfer_price = priceStr ? Number(priceStr) : 0;
    if (transfer_price < 0) {
      wx.showToast({ title: "金额不能为负", icon: "none" });
      return;
    }

    try {
      wx.showLoading({ title: "保存中…", mask: true });
      const res: any = await request({
        route: "assets/transfer",
        method: "POST",
        data: { id: asset.id, transfer_date, transfer_price },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "操作失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "已标记为已转手", icon: "success" });
      this.reload();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: "请求失败", icon: "none" });
    }
  },

  async deleteAsset(asset: any) {
    const confirmRes = await wx.showModal({
      title: "删除资产",
      content: "确定要删除该资产吗？此操作不可恢复。",
    } as any);
    if (!confirmRes.confirm) return;

    try {
      wx.showLoading({ title: "删除中…", mask: true });
      const res: any = await request({
        route: "assets/delete",
        method: "POST",
        data: { id: asset.id },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "删除失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "已删除", icon: "success" });
      this.reload();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: "请求失败", icon: "none" });
    }
  },
});
