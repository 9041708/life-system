// miniprogram/pages/categories/list.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

Page({
  data: {
    typeTabs: [
      { key: "expense", name: "支出分类" },
      { key: "income", name: "收入分类" },
      { key: "transfer", name: "转账分类" },
    ],
    currentType: "expense",
    categories: [] as any[],
    loading: false,
    nameInput: "",
    sortInput: "",
    editingId: 0,
    submitting: false,
    iconLibrary: [] as any[],
    selectedIconId: 0,
    iconCleared: false,
    showFormModal: false,
    newIconName: "",
    uploadingIcon: false,
    theme: 'light' as 'light' | 'dark',
  },

  async onLoad() {
    this.syncThemeFromGlobal();
    this.loadIconLibrary();
    this.reload();

    // 初始化分享配置：分类管理
    try {
      await initShare(this, {
        title: '三石记账 · 分类管理',
        path: '/pages/categories/list',
      });
    } catch (e) {}
  },

  onPullDownRefresh() {
    this.syncThemeFromGlobal();
    this.reload();
  },

  async reload() {
    this.setData({ categories: [] });
    await this.loadCategories();
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

  async loadIconLibrary() {
    try {
      const res: any = await request({
        route: "icon-library/list",
        method: "GET",
      });
      if (res && res.success) {
        this.setData({ iconLibrary: res.icons || [] });
      }
    } catch (e) {
      console.error("loadIconLibrary error", e);
    }
  },

  async loadCategories() {
    if (this.data.loading) return;
    this.setData({ loading: true });
    try {
      const res: any = await request({
        route: "categories/list",
        method: "GET",
        data: {
          type: this.data.currentType,
        },
      });
      if (!res || !res.success) {
        wx.showToast({
          title: (res && res.error) || "加载失败",
          icon: "none",
        });
        return;
      }
      const list: any[] = res.categories || [];
      this.setData({ categories: list });
    } catch (e) {
      console.error("loadCategories error", e);
      wx.showToast({ title: "请求失败", icon: "none" });
    } finally {
      this.setData({ loading: false });
      wx.stopPullDownRefresh();
    }
  },
  onNavAccounts() {
    wx.switchTab({ url: '/pages/accounts/list' });
  },

  onNavCategories() {
    // 当前即为分类管理页
  },

  onNavItems() {
    wx.navigateTo({ url: '/pages/items/list' });
  },
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },

  onTabChange(e: any) {
    const key = e.currentTarget.dataset.key;
    if (!key || key === this.data.currentType) return;
    this.setData({
      currentType: key,
      categories: [],
      nameInput: "",
      sortInput: "",
      editingId: 0,
      selectedIconId: 0,
      iconCleared: false,
    });
    this.loadCategories();
  },

  onNameInput(e: any) {
    this.setData({ nameInput: e.detail.value });
  },

  onSortInput(e: any) {
    this.setData({ sortInput: e.detail.value });
  },

  onAddNew() {
    this.setData({
      editingId: 0,
      nameInput: "",
      sortInput: "",
      selectedIconId: 0,
      iconCleared: false,
      showFormModal: true,
    });
  },

  onEditTap(e: any) {
    const id = Number(e.currentTarget.dataset.id || 0);
    const item = this.data.categories.find((c: any) => c.id === id);
    if (!item) return;

    // 预选中与当前分类图标路径相同的图标库条目
    let selectedIconId = 0;
    if (item.icon_type === "file" && item.icon_value && this.data.iconLibrary) {
      const lib = (this.data.iconLibrary as any[]).find(
        (x) => x.file_path === item.icon_value
      );
      if (lib) {
        selectedIconId = lib.id;
      }
    }

    this.setData({
      editingId: id,
      nameInput: item.name || "",
      sortInput: String(item.sort_order || ""),
      selectedIconId,
      iconCleared: false,
      showFormModal: true,
    });
  },

  onCancelEdit() {
    this.setData({
      editingId: 0,
      nameInput: "",
      sortInput: "",
      selectedIconId: 0,
      iconCleared: false,
      showFormModal: false,
    });
  },

  onIconSelect(e: any) {
    const id = Number(e.currentTarget.dataset.id || 0);
    if (!id) {
      this.setData({ selectedIconId: 0, iconCleared: false });
      return;
    }
    this.setData({ selectedIconId: id, iconCleared: false });
  },

  onClearIcon() {
    this.setData({ selectedIconId: 0, iconCleared: true });
  },

  onCloseModal() {
    this.setData({ showFormModal: false });
  },

  // 上传新图标到图标库
  onUploadIcon() {
    if (this.data.uploadingIcon) return;
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

        this.setData({ uploadingIcon: true });
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
            name: this.data.newIconName || "",
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
              const icons = this.data.iconLibrary || [];
              icons.push(icon);
              this.setData({
                iconLibrary: icons,
                selectedIconId: icon.id,
                iconCleared: false,
              });
              wx.showToast({ title: "已添加到图标库", icon: "success" });
            } catch (e) {
              wx.showToast({ title: "上传返回异常", icon: "none" });
            }
          },
          fail: () => {
            wx.showToast({ title: "上传失败", icon: "none" });
          },
          complete: () => {
            wx.hideLoading();
            this.setData({ uploadingIcon: false });
          },
        });
      },
    });
  },

  async onSubmit() {
    if (this.data.submitting) return;
    const name = (this.data.nameInput || "").trim();
    const sortStr = (this.data.sortInput || "").trim();
    if (!name) {
      wx.showToast({ title: "请输入分类名称", icon: "none" });
      return;
    }
    let sortOrder = 0;
    if (sortStr) {
      const v = Number(sortStr);
      if (isNaN(v)) {
        wx.showToast({ title: "排序必须是数字", icon: "none" });
        return;
      }
      sortOrder = Math.floor(v);
    }

    const isEdit = !!this.data.editingId;
    const route = isEdit ? "categories/update" : "categories/create";
    const payload: any = {
      type: this.data.currentType,
      name,
      sort_order: sortOrder,
    };
    if (isEdit) {
      payload.id = this.data.editingId;
    }

    if (this.data.selectedIconId) {
      payload.icon_library_id = this.data.selectedIconId;
    } else if (isEdit && this.data.iconCleared) {
      payload.icon_clear = true;
    }

    this.setData({ submitting: true });
    try {
      const res: any = await request({
        route,
        method: "POST",
        data: payload,
      });
      if (!res || !res.success) {
        wx.showToast({
          title: (res && res.error) || "保存失败",
          icon: "none",
        });
        return;
      }
      wx.showToast({ title: isEdit ? "更新成功" : "新增成功", icon: "success" });
      this.setData({
        editingId: 0,
        nameInput: "",
        sortInput: "",
        showFormModal: false,
      });
      this.loadCategories();
    } catch (e) {
      console.error("save category error", e);
      wx.showToast({ title: "请求失败", icon: "none" });
    } finally {
      this.setData({ submitting: false });
    }
  },

  onDeleteTap(e: any) {
    const id = Number(e.currentTarget.dataset.id || 0);
    if (!id) return;
    const item = this.data.categories.find((c: any) => c.id === id);
    const name = (item && item.name) || "此分类";
    wx.showModal({
      title: "确认删除？",
      content: `删除后将无法恢复，且已有记账的分类不能删除。确定删除“${name}”？`,
      confirmColor: "#e64340",
      success: async (res) => {
        if (!res.confirm) return;
        try {
          const r: any = await request({
            route: "categories/delete",
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
          wx.showToast({ title: "删除成功", icon: "success" });
          this.loadCategories();
        } catch (err) {
          console.error("delete category error", err);
          wx.showToast({ title: "请求失败", icon: "none" });
        }
      },
    });
  },
});
