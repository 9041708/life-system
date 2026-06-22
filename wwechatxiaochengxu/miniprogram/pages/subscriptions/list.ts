// miniprogram/pages/subscriptions/list.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

function formatAmount(raw: any): string {
  const n = Number(raw || 0);
  return n.toFixed(2);
}

Page({
  data: {
    loading: false,
    keyword: "",
    activeTab: "subscription" as "subscription" | "lifetime",
    list: [] as any[],
    filteredList: [] as any[],
  },

  async onLoad() {
    try {
      await initShare(this, {
        title: "三石记账 · 订阅记录",
        path: "/pages/subscriptions/list",
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
    await this.loadSubscriptions();
  },

  async loadSubscriptions() {
    if (this.data.loading) return;
    this.setData({ loading: true });

    try {
      const res: any = await request({
        route: "subscriptions/list",
        method: "GET",
        data: this.data.keyword ? { q: this.data.keyword } : {},
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "加载失败", icon: "none" });
        return;
      }

      const list: any[] = res.subscriptions || [];
      const today = new Date();

      list.forEach((s) => {
        s._priceDisplay = formatAmount(s.price);

        // 剩余天数字段 + 提示标签
        const d = typeof s.days_left === "number" ? s.days_left : null;
        if (d === null || s.type === "lifetime") {
          s._daysLabel = s.type === "lifetime" ? "买断 / 永久" : "--";
          s._expireLevel = "";
        } else if (d < 0) {
          s._daysLabel = "已超期 " + Math.abs(d) + " 天";
          s._expireLevel = "danger";
        } else if (d === 0) {
          s._daysLabel = "今天到期";
          s._expireLevel = "danger";
        } else if (d <= 7) {
          s._daysLabel = "还剩 " + d + " 天";
          s._expireLevel = "warn";
        } else {
          s._daysLabel = "还剩 " + d + " 天";
          s._expireLevel = "";
        }
      });

      this.setData({
        list,
      });
      this.applyFilter();
    } catch (e) {
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  applyFilter() {
    const type = this.data.activeTab;
    const list = this.data.list || [];
    const filtered = list.filter((s: any) => s.type === type);
    this.setData({ filteredList: filtered });
  },

  onPullDownRefresh() {
    this.reload();
  },

  onKeywordInput(e: any) {
    this.setData({ keyword: e.detail.value || "" });
  },

  onSearch() {
    this.reload();
  },

  onTabSubscription() {
    if (this.data.activeTab === "subscription") return;
    this.setData({ activeTab: "subscription" });
    this.applyFilter();
  },

  onTabLifetime() {
    if (this.data.activeTab === "lifetime") return;
    this.setData({ activeTab: "lifetime" });
    this.applyFilter();
  },

  async onAddSubscription() {
    const typeRes: any = await wx.showActionSheet({
      itemList: ["订阅", "买断/永久"],
    } as any);
    const type = typeRes.tapIndex === 1 ? "lifetime" : "subscription";

    const platformRes: any = await wx.showModal({
      title: "平台名称",
      editable: true,
      placeholderText: "例如：腾讯视频 / Photoshop",
      content: "",
    } as any);
    if (!platformRes.confirm) return;
    const platform = String(platformRes.content || "").trim();
    if (!platform) {
      wx.showToast({ title: "平台名称不能为空", icon: "none" });
      return;
    }

    const priceRes: any = await wx.showModal({
      title: "价格",
      editable: true,
      placeholderText: "请输入金额，例如 19.9",
      content: "",
    } as any);
    if (!priceRes.confirm) return;
    const priceStr = String(priceRes.content || "").trim();
    const price = Number(priceStr || 0);
    if (!price || price <= 0) {
      wx.showToast({ title: "价格需大于 0", icon: "none" });
      return;
    }

    let expire_date = "";
    let period = "";
    let auto_renew = false;

    if (type === "subscription") {
      const expireRes: any = await wx.showModal({
        title: "到期日 (YYYY-MM-DD)",
        editable: true,
        placeholderText: "例如：2026-01-31",
        content: "",
      } as any);
      if (!expireRes.confirm) return;
      expire_date = String(expireRes.content || "").trim();
      if (!expire_date) {
        wx.showToast({ title: "到期日不能为空", icon: "none" });
        return;
      }

      const periodRes: any = await wx.showModal({
        title: "订阅周期（可留空）",
        editable: true,
        placeholderText: "例如：按月 / 按年",
        content: "",
      } as any);
      if (periodRes.confirm) {
        period = String(periodRes.content || "").trim();
      }

      const renewRes: any = await wx.showActionSheet({
        itemList: ["手动续费", "自动续费"],
      } as any);
      auto_renew = renewRes.tapIndex === 1;
    }

    const remarkRes: any = await wx.showModal({
      title: "备注（可留空）",
      editable: true,
      placeholderText: "例如：拼车 / 学生优惠",
      content: "",
    } as any);
    const remark = remarkRes.confirm ? String(remarkRes.content || "").trim() : "";

    try {
      wx.showLoading({ title: "保存中…", mask: true });
      const res: any = await request({
        route: "subscriptions/save",
        method: "POST",
        data: {
          platform,
          type,
          price,
          expire_date: type === "subscription" ? expire_date : undefined,
          period: type === "subscription" ? period : undefined,
          auto_renew: type === "subscription" ? auto_renew : false,
          remark,
        },
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

  async onSubscriptionLongPress(e: any) {
    const sub = e.currentTarget.dataset.sub;
    if (!sub) return;

    const that = this;
    wx.showActionSheet({
      itemList: ["编辑", "续费/改到期日", "关闭订阅"],
      success(res) {
        if (res.tapIndex === 0) {
          that.editSubscription(sub);
        } else if (res.tapIndex === 1) {
          that.renewSubscription(sub);
        } else if (res.tapIndex === 2) {
          that.deleteSubscription(sub);
        }
      },
    });
  },

  async editSubscription(sub: any) {
    const platformRes: any = await wx.showModal({
      title: "平台名称",
      editable: true,
      content: String(sub.platform || ""),
    } as any);
    if (!platformRes.confirm) return;
    const platform = String(platformRes.content || "").trim();
    if (!platform) {
      wx.showToast({ title: "平台名称不能为空", icon: "none" });
      return;
    }

    const priceRes: any = await wx.showModal({
      title: "价格",
      editable: true,
      content: String(sub.price || ""),
    } as any);
    if (!priceRes.confirm) return;
    const priceStr = String(priceRes.content || "").trim();
    const price = Number(priceStr || 0);
    if (!price || price <= 0) {
      wx.showToast({ title: "价格需大于 0", icon: "none" });
      return;
    }

    let expire_date = sub.expire_date || "";
    let period = sub.period || "";
    let auto_renew = !!sub.auto_renew;

    if (sub.type === "subscription") {
      const expireRes: any = await wx.showModal({
        title: "到期日 (YYYY-MM-DD)",
        editable: true,
        content: String(sub.expire_date || ""),
      } as any);
      if (!expireRes.confirm) return;
      expire_date = String(expireRes.content || "").trim();
      if (!expire_date) {
        wx.showToast({ title: "到期日不能为空", icon: "none" });
        return;
      }

      const periodRes: any = await wx.showModal({
        title: "订阅周期（可留空）",
        editable: true,
        content: String(sub.period || ""),
      } as any);
      if (periodRes.confirm) {
        period = String(periodRes.content || "").trim();
      }

      const renewRes: any = await wx.showActionSheet({
        itemList: ["手动续费", "自动续费"],
      } as any);
      auto_renew = renewRes.tapIndex === 1;
    }

    const remarkRes: any = await wx.showModal({
      title: "备注（可留空）",
      editable: true,
      content: String(sub.remark || ""),
    } as any);
    const remark = remarkRes.confirm ? String(remarkRes.content || "").trim() : "";

    try {
      wx.showLoading({ title: "保存中…", mask: true });
      const res: any = await request({
        route: "subscriptions/save",
        method: "POST",
        data: {
          id: sub.id,
          platform,
          type: sub.type,
          price,
          expire_date: sub.type === "subscription" ? expire_date : undefined,
          period: sub.type === "subscription" ? period : undefined,
          auto_renew: sub.type === "subscription" ? auto_renew : false,
          remark,
        },
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

  async renewSubscription(sub: any) {
    if (sub.type !== "subscription") {
      wx.showToast({ title: "买断无需续费", icon: "none" });
      return;
    }

    const priceRes: any = await wx.showModal({
      title: "本次续费金额",
      editable: true,
      placeholderText: "例如：19.9",
      content: String(sub.price || ""),
    } as any);
    if (!priceRes.confirm) return;
    const priceStr = String(priceRes.content || "").trim();
    const price = Number(priceStr || 0);
    if (!price || price <= 0) {
      wx.showToast({ title: "价格需大于 0", icon: "none" });
      return;
    }

    const expireRes: any = await wx.showModal({
      title: "新的到期日 (YYYY-MM-DD)",
      editable: true,
      content: String(sub.expire_date || ""),
    } as any);
    if (!expireRes.confirm) return;
    const expire_date = String(expireRes.content || "").trim();
    if (!expire_date) {
      wx.showToast({ title: "到期日不能为空", icon: "none" });
      return;
    }

    let auto_renew = !!sub.auto_renew;
    let period = sub.period || "";

    const periodRes: any = await wx.showModal({
      title: "订阅周期（可留空）",
      editable: true,
      content: String(sub.period || ""),
    } as any);
    if (periodRes.confirm) {
      period = String(periodRes.content || "").trim();
    }

    const renewRes: any = await wx.showActionSheet({
      itemList: ["手动续费", "自动续费"],
    } as any);
    auto_renew = renewRes.tapIndex === 1;

    try {
      wx.showLoading({ title: "保存中…", mask: true });
      const res: any = await request({
        route: "subscriptions/renew",
        method: "POST",
        data: {
          id: sub.id,
          type: sub.type,
          price,
          expire_date,
          auto_renew,
          period,
        },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "操作失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "续费已保存", icon: "success" });
      this.reload();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: "请求失败", icon: "none" });
    }
  },

  async deleteSubscription(sub: any) {
    const confirmRes = await wx.showModal({
      title: "关闭订阅",
      content: "确定要关闭/删除该订阅记录吗？",
    } as any);
    if (!confirmRes.confirm) return;

    try {
      wx.showLoading({ title: "处理中…", mask: true });
      const res: any = await request({
        route: "subscriptions/delete",
        method: "POST",
        data: { id: sub.id },
      });
      wx.hideLoading();
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "操作失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "已关闭", icon: "success" });
      this.reload();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: "请求失败", icon: "none" });
    }
  },
});
