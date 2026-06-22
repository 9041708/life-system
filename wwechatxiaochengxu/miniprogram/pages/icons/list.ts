// miniprogram/pages/icons/list.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require("../../utils/share");

Page({
  data: {
    loading: false,
    icons: [] as any[],
    showFormModal: false,
    editingId: 0,
    formName: "",
    tempFilePath: "",
    formUploading: false,
    formPreviewUrl: "",
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: "三石记账 · 图标库管理",
        path: "/pages/icons/list",
      });
    } catch (e) {}

    this.reload();
  },

  onPullDownRefresh() {
    this.reload().finally(() => {
      wx.stopPullDownRefresh();
    });
  },

  async reload() {
    if (this.data.loading) return;
    this.setData({ loading: true });
    try {
      const res: any = await request({
        route: "icon-library/list",
        method: "GET",
      });
      if (res && res.success) {
        this.setData({ icons: res.icons || [] });
      } else {
        wx.showToast({
          title: (res && res.error) || "加载失败",
          icon: "none",
        });
      }
    } catch (e) {
      console.error("load icons error", e);
      wx.showToast({ title: "请求失败", icon: "none" });
    } finally {
      this.setData({ loading: false });
    }
  },

  // 打开新增图标弹窗
  onAddIconTap() {
    this.setData({
      showFormModal: true,
      editingId: 0,
      formName: "",
      tempFilePath: "",
      formPreviewUrl: "",
      formUploading: false,
    });
  },

  // 打开编辑图标弹窗
  onEditTap(e: any) {
    const id = Number(e.currentTarget.dataset.id || 0);
    if (!id) return;
    const item = (this.data.icons || []).find((x: any) => x.id === id);
    if (!item) {
      wx.showToast({ title: "未找到该图标", icon: "none" });
      return;
    }
    this.setData({
      showFormModal: true,
      editingId: id,
      formName: item.name || "",
      tempFilePath: "",
      formPreviewUrl: item.file_url || "",
      formUploading: false,
    });
  },

  onFormNameInput(e: any) {
    this.setData({ formName: e.detail.value });
  },

  // 选择图标文件（仅新增时使用）
  onChooseImage() {
    if (this.data.editingId) {
      return;
    }
    const token = wx.getStorageSync("token") || "";
    if (!token) {
      wx.showToast({ title: "请先登录", icon: "none" });
      return;
    }

    wx.chooseImage({
      count: 1,
      sizeType: ["compressed"],
      sourceType: ["album", "camera"],
      success: (chooseRes) => {
        const filePath = chooseRes.tempFilePaths[0];
        if (!filePath) return;

        this.setData({ tempFilePath: filePath });
      },
    });
  },

  // 编辑模式下更换图标文件
  onChangeImage() {
    const editingId = this.data.editingId;
    if (!editingId) return;
    const token = wx.getStorageSync("token") || "";
    if (!token) {
      wx.showToast({ title: "请先登录", icon: "none" });
      return;
    }

    wx.chooseImage({
      count: 1,
      sizeType: ["compressed"],
      sourceType: ["album", "camera"],
      success: (chooseRes) => {
        const filePath = chooseRes.tempFilePaths[0];
        if (!filePath) return;

        this.setData({ formUploading: true });
        wx.showLoading({ title: "上传中...", mask: true });

        const BASE_URL = "https://your-domain.com/public/api.php";
        wx.uploadFile({
          url: BASE_URL + "?route=icon-library/update-file",
          filePath,
          name: "file",
          header: {
            Authorization: "Bearer " + token,
          },
          formData: {
            id: String(editingId),
            name: this.data.formName || "",
          },
          success: (res) => {
            try {
              const data = JSON.parse(res.data || "{}");
              if (!data || !data.success || !data.icon) {
                wx.showToast({
                  title: (data && data.error) || "上传失败",
                  icon: "none",
                });
                return;
              }
              const icon = data.icon;
              const list = (this.data.icons || []).map((x: any) =>
                x.id === editingId ? { ...x, name: icon.name, file_path: icon.file_path, file_url: icon.file_url } : x
              );
              this.setData({
                icons: list,
                formPreviewUrl: icon.file_url,
                formName: icon.name || this.data.formName,
              });
              wx.showToast({ title: "已更新", icon: "success" });
            } catch (e) {
              wx.showToast({ title: "上传返回异常", icon: "none" });
            }
          },
          fail: () => {
            wx.showToast({ title: "上传失败", icon: "none" });
          },
          complete: () => {
            wx.hideLoading();
            this.setData({ formUploading: false });
          },
        });
      },
    });
  },

  // 关闭弹窗
  onCloseModal() {
    this.setData({
      showFormModal: false,
      editingId: 0,
      formName: "",
      tempFilePath: "",
      formPreviewUrl: "",
      formUploading: false,
    });
  },

  // 提交新增 / 编辑
  onSubmitForm() {
    if (this.data.formUploading) return;
    const editingId = this.data.editingId;
    const name = this.data.formName || "";

    if (editingId) {
      // 编辑：仅修改名称
      this.setData({ formUploading: true });
      request({
        route: "icon-library/update",
        method: "POST",
        data: { id: editingId, name },
      })
        .then((res: any) => {
          if (!res || !res.success) {
            wx.showToast({
              title: (res && res.error) || "保存失败",
              icon: "none",
            });
            return;
          }
          const list = (this.data.icons || []).map((x: any) =>
            x.id === editingId ? { ...x, name } : x
          );
          this.setData({ icons: list });
          wx.showToast({ title: "已保存", icon: "success" });
          this.onCloseModal();
        })
        .catch((err: any) => {
          console.error("update icon error", err);
          wx.showToast({ title: "请求失败", icon: "none" });
        })
        .finally(() => {
          this.setData({ formUploading: false });
        });
      return;
    }

    // 新增：需要选择图标并上传
    if (!this.data.tempFilePath) {
      wx.showToast({ title: "请先选择图标", icon: "none" });
      return;
    }
    const token = wx.getStorageSync("token") || "";
    if (!token) {
      wx.showToast({ title: "请先登录", icon: "none" });
      return;
    }

    const filePath = this.data.tempFilePath;
    this.setData({ formUploading: true });
    wx.showLoading({ title: "上传中...", mask: true });

    const BASE_URL = "https://your-domain.com/public/api.php";
    wx.uploadFile({
      url: BASE_URL + "?route=icon-library/upload",
      filePath,
      name: "file",
      header: {
        Authorization: "Bearer " + token,
      },
      formData: {
        name,
      },
      success: (res) => {
        try {
          const data = JSON.parse(res.data || "{}");
          if (!data || !data.success || !data.icon) {
            wx.showToast({
              title: (data && data.error) || "上传失败",
              icon: "none",
            });
            return;
          }
          const list = this.data.icons || [];
          list.push(data.icon);
          this.setData({ icons: list });
          wx.showToast({ title: "已添加", icon: "success" });
          this.onCloseModal();
        } catch (e) {
          wx.showToast({ title: "上传返回异常", icon: "none" });
        }
      },
      fail: () => {
        wx.showToast({ title: "上传失败", icon: "none" });
      },
      complete: () => {
        wx.hideLoading();
        this.setData({ formUploading: false });
      },
    });
  },

  onDeleteTap(e: any) {
    const id = Number(e.currentTarget.dataset.id || 0);
    if (!id) return;
    const item = this.data.icons.find((x: any) => x.id === id);
    const name = (item && item.name) || "该图标";
    wx.showModal({
      title: "确认删除？",
      content: `只会删除图标库记录，不会删除实际图片文件。确定删除“${name}”？`,
      confirmColor: "#e64340",
      success: async (res) => {
        if (!res.confirm) return;
        try {
          const r: any = await request({
            route: "icon-library/delete",
            method: "POST",
            data: { id },
          });
          if (!r || !r.success) {
            wx.showToast({
              title: (r && r.error) || "删除失败",
              icon: "none",
            });
            return;
          }
          wx.showToast({ title: "已删除", icon: "success" });
          this.setData({ icons: (this.data.icons || []).filter((x: any) => x.id !== id) });
        } catch (err) {
          console.error("delete icon error", err);
          wx.showToast({ title: "请求失败", icon: "none" });
        }
      },
    });
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});
