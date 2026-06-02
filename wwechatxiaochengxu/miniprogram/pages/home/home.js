const { request } = require("../../utils/request");

Page({
  data: {
    user: {},
    summary: {
      totalIncome: 0,
      totalExpense: 0
    }
  },

  onLoad() {
    const user = wx.getStorageSync("user") || {};
    this.setData({ user });
    this.loadSummary();
  },

  async loadSummary() {
    const res = await request({
      route: "reports/summary",
      data: { mode: "month" }
    });
    if (res.success) {
      this.setData({
        summary: {
          totalIncome: res.totalIncome,
          totalExpense: res.totalExpense
        }
      });
    }
  }
});