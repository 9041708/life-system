// miniprogram/pages/items/list.ts
import { request } from "../../utils/request";
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

Page({
  data: {
    categories: [] as any[],
    categoryOptions: [] as any[],
    categoryIndex: 0,
    items: [] as any[],
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
  },

  async onLoad() {
    await this.loadCategories();
    await this.loadIconLibrary();
    await this.loadItems();

    // 初始化分享配置：项目管理
    try {
      await initShare(this, {
        title: '三石记账 · 项目管理',
        path: '/pages/items/list',
      });
    } catch (e) {}
  },

  onPullDownRefresh() {
    this.loadItems().finally(() => {
      wx.stopPullDownRefresh();
    });
  },

  async loadCategories() {
    try {
      const res: any = await request({
        route: "categories/list",
        method: "GET",
        data: {},
      });
      const categories: any[] = res && res.success && res.categories ? res.categories : [];
      const options: any[] = [{ id: 0, name: "全部分类" }];
      for (let i = 0; i < categories.length; i++) {
        const c = categories[i];
        const t = String(c.type || "");
        let typeLabel = "支出";
        if (t === "income") {
          typeLabel = "收入";
        } else if (t === "transfer") {
          typeLabel = "转账";
        }
        options.push({
          id: c.id,
          name: `[${typeLabel}] ` + c.name,
        });
      }
      this.setData({
        categories,
        categoryOptions: options,
        categoryIndex: 0,
      });
    } catch (e) {
      console.error("loadCategories error", e);
      this.setData({
        categories: [],
        categoryOptions: [{ id: 0, name: "全部分类" }],
        categoryIndex: 0,
      });
    }
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

  getCurrentCategoryId(): number | null {
    const idx = this.data.categoryIndex;
    const opts: any[] = this.data.categoryOptions || [];
    if (!opts[idx]) return null;
    const id = Number(opts[idx].id || 0);
    return id > 0 ? id : null;
  },

  async loadItems() {
    if (this.data.loading) return;
    this.setData({ loading: true, items: [] });
    try {
      const categoryId = this.getCurrentCategoryId();
      const data: any = {};
      if (categoryId) {
        data.category_id = categoryId;
      }
      const res: any = await request({
        route: "items/list",
        method: "GET",
        data,
      });
      if (!res || !res.success) {
        wx.showToast({ title: (res && res.error) || "加载失败", icon: "none" });
        return;
      }
      const list: any[] = res.items || [];
      this.setData({ items: list });
    } catch (e) {
      console.error("loadItems error", e);
      wx.showToast({ title: "请求失败", icon: "none" });
    } finally {
      this.setData({ loading: false });
    }
  },

  onCategoryChange(e: any) {
    const index = Number(e.detail.value || 0);
    this.setData({
      categoryIndex: index,
      editingId: 0,
      nameInput: "",
      sortInput: "",
      selectedIconId: 0,
      iconCleared: false,
    });
    this.loadItems();
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
    const item = this.data.items.find((x: any) => x.id === id);
    if (!item) return;
    // 找到对应分类在下拉框中的位置
    const opts: any[] = this.data.categoryOptions || [];
    let index = 0;
    for (let i = 0; i < opts.length; i++) {
      if (Number(opts[i].id || 0) === item.category_id) {
        index = i;
        break;
      }
    }

    // 预选中与当前项目图标路径相同的图标库条目
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
      categoryIndex: index,
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

        const BASE_URL = "https://9041708.cn:555/public/api.php";
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
    const categoryId = this.getCurrentCategoryId();

    if (!categoryId) {
      wx.showToast({ title: "请选择分类", icon: "none" });
      return;
    }
    if (!name) {
      wx.showToast({ title: "请输入项目名称", icon: "none" });
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
    const route = isEdit ? "items/update" : "items/create";
    const payload: any = {
      category_id: categoryId,
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
        wx.showToast({ title: (res && res.error) || "保存失败", icon: "none" });
        return;
      }
      wx.showToast({ title: isEdit ? "更新成功" : "新增成功", icon: "success" });
      this.setData({
        editingId: 0,
        nameInput: "",
        sortInput: "",
        showFormModal: false,
      });
      this.loadItems();
    } catch (e) {
      console.error("save item error", e);
      wx.showToast({ title: "请求失败", icon: "none" });
    } finally {
      this.setData({ submitting: false });
    }
  },
  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },

  // 管理子菜单：账户列表 / 分类管理 / 项目管理
  onNavAccounts() {
    wx.switchTab({ url: '/pages/accounts/list' });
  },

  onNavCategories() {
    wx.navigateTo({ url: '/pages/categories/list' });
  },

  onNavItems() {
    // 当前即为项目管理页
  },

  onDeleteTap(e: any) {
    const id = Number(e.currentTarget.dataset.id || 0);
    if (!id) return;
    const item = this.data.items.find((x: any) => x.id === id);
    const name = (item && item.name) || "此项目";
    wx.showModal({
      title: "确认删除？",
      content: `删除后将无法恢复，且已有记账数据的项目不能删除。确定删除“${name}”？`,
      confirmColor: "#e64340",
      success: async (res) => {
        if (!res.confirm) return;
        try {
          const r: any = await request({
            route: "items/delete",
            method: "POST",
            data: { id },
          });
          if (!r || !r.success) {
            wx.showToast({ title: (r && r.error) || "删除失败", icon: "none" });
            return;
          }
          wx.showToast({ title: "删除成功", icon: "success" });
          this.loadItems();
        } catch (err) {
          console.error("delete item error", err);
          wx.showToast({ title: "请求失败", icon: "none" });
        }
      },
    });
  },
});
