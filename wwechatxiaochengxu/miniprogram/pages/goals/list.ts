// miniprogram/pages/goals/list.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

Page({
  data: {
    goals: [] as any[],
    loading: false,
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: "三石记账 · 小目标",
        path: "/pages/goals/list",
      });
    } catch (e) {}

    this.reload();
  },

  onShow() {
    this.reload();
  },

  onPullDownRefresh() {
    this.reload();
  },

  async reload() {
    this.setData({ goals: [] });
    await this.loadGoals();
  },

  async loadGoals() {
    const token = wx.getStorageSync("token") || "";
    if (!token) {
      this.setData({ goals: [], loading: false });
      wx.stopPullDownRefresh();
      return;
    }

    this.setData({ loading: true });
    try {
      const res: any = await request({
        route: "goals/list",
        method: "GET",
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "加载失败", icon: "none" });
        return;
      }

      const list: any[] = res.goals || [];
      const mapped = list.map((g) => {
        const target = Number(g.target_amount || 0);
        const saved = Number(g.saved_amount || 0);
        const percent = Number(g.percent || 0);
        const barPercent = Number(g.barPercent || 0);
        return {
          ...g,
          targetAmountDisplay: target.toFixed(2),
          savedAmountDisplay: saved.toFixed(2),
          percent,
          barPercent,
        };
      });

      this.setData({ goals: mapped });
    } catch (e) {
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },

  async onAddGoal() {
    const token = wx.getStorageSync("token") || "";
    if (!token) {
      wx.showToast({ title: "请先登录", icon: "none" });
      return;
    }
    await this.showGoalEditor();
  },

  // 底部子菜单：目标管理 / 预算管理
  onNavGoals() {
    // 当前即为目标管理页，无需跳转
  },

  onNavBudget() {
    wx.navigateTo({ url: '/pages/budget/list' });
  },

  async onEditGoal(e: any) {
    const id = Number((e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id) || 0);
    const goal = this.data.goals.find((g: any) => Number(g.id) === id);
    await this.showGoalEditor(goal || null);
  },

  async onActionsTap(e: any) {
    const id = Number((e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id) || 0);
    const goal = this.data.goals.find((g: any) => Number(g.id) === id);
    if (!goal) return;

    const that = this;
    wx.showActionSheet({
      itemList: ["编辑目标", "标记为已完成", "删除目标"],
      success: async (res) => {
        if (res.tapIndex === 0) {
          await that.showGoalEditor(goal);
        } else if (res.tapIndex === 1) {
          await that.updateGoalStatus(goal, "done");
        } else if (res.tapIndex === 2) {
          await that.deleteGoal(goal);
        }
      },
    });
  },

  async showGoalEditor(goal?: any | null) {
    const isEdit = !!(goal && goal.id);

    // 标题
    const titleRes: any = await wx.showModal({
      title: isEdit ? "编辑目标" : "新建目标",
      editable: true,
      placeholderText: "例如：旅行基金 / 新电脑",
      content: goal ? String(goal.title || "") : "",
    } as any);
    if (!titleRes.confirm) return;
    const title = (titleRes.content ? String(titleRes.content) : "").trim();
    if (!title) {
      wx.showToast({ title: "目标名称不能为空", icon: "none" });
      return;
    }

    // 目标金额
    const targetRes: any = await wx.showModal({
      title: "目标金额",
      editable: true,
      placeholderText: "请输入目标金额，例如 5000",
      content: goal ? String(goal.target_amount || "") : "",
    } as any);
    if (!targetRes.confirm) return;
    const targetStr = (targetRes.content ? String(targetRes.content) : "").trim();
    const targetAmount = Number(targetStr || 0);
    if (!targetAmount || targetAmount <= 0) {
      wx.showToast({ title: "目标金额需大于 0", icon: "none" });
      return;
    }

    // 当前进度
    const savedRes: any = await wx.showModal({
      title: "当前已完成金额",
      editable: true,
      placeholderText: "例如：已存入的金额，可留空为 0",
      content: goal ? String(goal.saved_amount || "") : "",
    } as any);
    if (!savedRes.confirm) return;
    const savedStr = (savedRes.content ? String(savedRes.content) : "").trim();
    let savedAmount = savedStr ? Number(savedStr) : 0;
    if (savedAmount < 0) savedAmount = 0;
    if (savedAmount > targetAmount) savedAmount = targetAmount;

    // 目标账户（更准确跟踪存款，优先要求选择专用账户）
    let accountId = goal && goal.account_id ? Number(goal.account_id) : 0;
    try {
      const accRes: any = await request({
        route: "accounts/list",
        method: "GET",
      });
      if (!accRes || !accRes.success) {
        wx.showToast({ title: (accRes && accRes.error) || "加载账户失败", icon: "none" });
        return;
      }

      const accounts: any[] = accRes.accounts || [];
      if (!accounts || accounts.length === 0) {
        const modalRes: any = await wx.showModal({
          title: "尚未创建账户",
          content:
            "为保证目标进度的准确性，建议先创建一个专用的“目标存款账户”，初始余额默认为 0。如已有存款，可在“记一笔收入”中录入到该账户。是否现在去创建账户？",
          confirmText: "去创建",
          cancelText: "稍后再说",
        } as any);
        if (modalRes.confirm) {
          wx.navigateTo({ url: "/pages/accounts/create" });
        }
        return;
      }

      const itemList = accounts.map((a) => {
        const labelParts: string[] = [];
        if (a.group_name) {
          labelParts.push("[" + String(a.group_name) + "]");
        }
        if (a.name) {
          labelParts.push(String(a.name));
        }
        const base = labelParts.join(" ");
        if (goal && goal.account_id && Number(goal.account_id) === Number(a.id)) {
          return base ? base + "（当前）" : "当前账户";
        }
        return base || "未命名账户";
      });

      const sheetRes: any = await wx.showActionSheet({
        itemList,
      });
      const idx = typeof sheetRes.tapIndex === "number" ? sheetRes.tapIndex : -1;
      if (idx < 0 || idx >= accounts.length) {
        wx.showToast({ title: "已取消选择账户", icon: "none" });
        return;
      }
      accountId = Number(accounts[idx].id || 0);
      if (!accountId) {
        wx.showToast({ title: "请选择有效账户", icon: "none" });
        return;
      }
    } catch (e) {
      wx.showToast({ title: "加载账户失败", icon: "none" });
      return;
    }

    // 截止日期（可选）
    let deadline = goal && goal.deadline ? String(goal.deadline) : "";
    try {
      const dateRes: any = await wx.showActionSheet({
        itemList: ["修改截止日期", "清除截止日期", "保持不变"],
      });
      if (dateRes.tapIndex === 0) {
        const pickerRes: any = await wx.showModal({
          title: "截止日期 (YYYY-MM-DD)",
          editable: true,
          placeholderText: "例如：2026-12-31，可留空",
          content: deadline,
        } as any);
        if (!pickerRes.confirm) return;
        deadline = (pickerRes.content ? String(pickerRes.content) : "").trim();
      } else if (dateRes.tapIndex === 1) {
        deadline = "";
      }
    } catch (e) {
      // 用户取消操作，视为不修改
    }

    try {
      wx.showLoading({ title: "保存中…", mask: true });
      const res: any = await request({
        route: "goals/save",
        method: "POST",
        data: {
          id: goal && goal.id ? Number(goal.id) : 0,
          title,
          account_id: accountId,
          target_amount: targetAmount,
          saved_amount: savedAmount,
          deadline,
          status: goal && goal.status ? goal.status : "active",
        },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "保存失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "已保存", icon: "success" });
      await this.loadGoals();
    } catch (e) {
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      wx.hideLoading();
    }
  },

  async updateGoalStatus(goal: any, status: "active" | "done" | "archived") {
    try {
      wx.showLoading({ title: "处理中…", mask: true });
      const res: any = await request({
        route: "goals/save",
        method: "POST",
        data: {
          id: Number(goal.id),
          title: goal.title,
          target_amount: goal.target_amount,
          saved_amount: goal.saved_amount,
          deadline: goal.deadline,
          status,
        },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "操作失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "已更新", icon: "success" });
      await this.loadGoals();
    } catch (e) {
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      wx.hideLoading();
    }
  },

  async deleteGoal(goal: any) {
    const confirmRes: any = await wx.showModal({
      title: "删除目标",
      content: "删除后无法恢复，是否继续？",
      confirmText: "删除",
      confirmColor: "#e11d48",
    } as any);
    if (!confirmRes.confirm) return;

    try {
      wx.showLoading({ title: "删除中…", mask: true });
      const res: any = await request({
        route: "goals/delete",
        method: "POST",
        data: { id: Number(goal.id) },
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "删除失败", icon: "none" });
        return;
      }
      wx.showToast({ title: "已删除", icon: "success" });
      await this.loadGoals();
    } catch (e) {
      wx.showToast({ title: "网络异常", icon: "none" });
    } finally {
      wx.hideLoading();
    }
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});
