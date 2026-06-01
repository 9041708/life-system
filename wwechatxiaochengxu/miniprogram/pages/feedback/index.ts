// pages/feedback/index.ts
import { request } from '../../utils/request';
const { initShare, buildShareAppMessage, buildShareTimeline } = require('../../utils/share');

const app = getApp();

interface FeedbackImage {
  path: string;
  url: string;
}

interface FeedbackItem {
  id: number;
  category: string;
  content: string;
  status: string;
  created_at: string;
  user_nickname: string;
  admin_reply: string | null;
  images: FeedbackImage[];

  _categoryLabel?: string;
  _statusLabel?: string;
  _date?: string;
  _time?: string;
}

Page({
  data: {
    loading: false,

    // FAQ 列表 & 搜索
    feedbacks: [] as FeedbackItem[],
    allFeedbacks: [] as FeedbackItem[],
    searchKeyword: '',

    // “我要反馈”弹窗
    showFormDialog: false,
    categoryOptions: [
      { value: 'suggest', label: '建议反馈' },
      { value: 'bug', label: '错误反馈' },
      { value: 'other', label: '其它反馈' },
    ],
    categoryIndex: 0,
    content: '',
    submitting: false,
    images: [] as string[],      // 相对路径
    imageUrls: [] as string[],   // 完整 URL

    // 详情弹窗
    showDetailDialog: false,
    detailFeedback: null as FeedbackItem | null,
  },

  async onLoad() {
    try {
      wx.showShareMenu({ withShareTicket: false });
    } catch (e) {}

    try {
      await initShare(this, {
        title: '三石记账 · 问题反馈',
        path: '/pages/feedback/index',
      });
    } catch (e) {}

    this.loadFeedbacks();
  },

  async loadFeedbacks() {
    this.setData({ loading: true });
    try {
      const res = await request({
        route: 'feedback/list',
        method: 'GET',
      });
  
      // 这里原来是 res.list 或类似写法，改成 feedbacks
      const rows = res.feedbacks || [];
  
      const list: FeedbackItem[] = rows.map((row: any) => {
        const createdAt = row.created_at || '';
        let date = '';
        let time = '';
        if (createdAt.includes(' ')) {
          const parts = createdAt.split(' ');
          date = parts[0];
          time = parts[1];
        }
  
        const category = row.category || 'suggest';
        let categoryLabel = '建议反馈';
        if (category === 'bug') categoryLabel = '错误反馈';
        else if (category === 'other') categoryLabel = '其它反馈';
  
        const status = row.status || 'pending';
        let statusLabel = '处理中';
        if (status === 'resolved') statusLabel = '已解决';
        else if (status === 'closed') statusLabel = '已关闭';
  
        return {
          id: row.id,
          category,
          content: row.content || '',
          status,
          created_at: createdAt,
          user_nickname: row.user_nickname || '用户',
          admin_reply: row.admin_reply || null,
          images: row.images || [],
          _categoryLabel: categoryLabel,
          _statusLabel: statusLabel,
          _date: date,
          _time: time,
        };
      });
  
      this.setData({
        allFeedbacks: list,
        feedbacks: list,
      });
    } finally {
      this.setData({ loading: false });
    }
  },

  // 搜索输入
  onSearchInput(e: WechatMiniprogram.Input) {
    const keyword = (e.detail.value || '').toLowerCase().trim();
    this.setData({ searchKeyword: keyword });

    if (!keyword) {
      this.setData({
        feedbacks: this.data.allFeedbacks,
      });
      return;
    }

    const all = this.data.allFeedbacks || [];
    const filtered = all.filter((item) => {
      const text = (
        (item.content || '') +
        ' ' +
        (item.admin_reply || '') +
        ' ' +
        (item.user_nickname || '')
      ).toLowerCase();
      return text.indexOf(keyword) !== -1;
    });

    this.setData({ feedbacks: filtered });
  },

  // 打开“我要反馈”弹窗
  onOpenFormDialog() {
    this.setData({
      showFormDialog: true,
    });
  },

  // 关闭“我要反馈”弹窗
  onCloseFormDialog() {
    if (this.data.submitting) return;
    this.setData({
      showFormDialog: false,
      content: '',
      categoryIndex: 0,
      images: [],
      imageUrls: [],
    });
  },

  // 阻止弹窗内点击冒泡
  noop() {},

  // 反馈类型选择
  onCategoryChange(e: WechatMiniprogram.PickerChange) {
    const index = Number(e.detail.value || 0);
    this.setData({ categoryIndex: index });
  },

  // 描述输入
  onContentInput(e: WechatMiniprogram.TextareaInput) {
    this.setData({ content: e.detail.value || '' });
  },

  // 选择图片（最多 3 张）
  onPickImage() {
    const remain = 3 - this.data.images.length;
    if (remain <= 0) {
      wx.showToast({ title: '最多上传 3 张图片', icon: 'none' });
      return;
    }

    wx.chooseImage({
      count: remain,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const files = res.tempFilePaths || [];
        if (!files.length) return;
        files.forEach((filePath) => {
          this.uploadImage(filePath);
        });
      },
    });
  },

  // 上传单张图片到后端（复用 transactions/upload-attachment）
  uploadImage(filePath: string) {
    const token = wx.getStorageSync('token') || '';
    const baseUrl = app.globalData.apiBaseUrl; // 你项目里已有的 API 基址
    wx.uploadFile({
      url: `${baseUrl}/public/api.php?route=transactions/upload-attachment`,
      filePath,
      name: 'file',
      header: {
        Authorization: token ? `Bearer ${token}` : '',
      },
      success: (res) => {
        try {
          const data = JSON.parse(res.data);
          if (!data || !data.success) {
            wx.showToast({ title: data.error || '上传失败', icon: 'none' });
            return;
          }
          const path = data.path;
          const url = data.url;
          const images = this.data.images.concat(path);
          const imageUrls = this.data.imageUrls.concat(url);
          this.setData({ images, imageUrls });
        } catch (e) {
          console.error('uploadImage parse error', e);
          wx.showToast({ title: '上传失败', icon: 'none' });
        }
      },
      fail: (err) => {
        console.error('uploadImage fail', err);
        wx.showToast({ title: '上传失败', icon: 'none' });
      },
    });
  },

  // 预览弹窗里的图片
  onPreviewImage(e: WechatMiniprogram.TouchEvent) {
    const url = e.currentTarget.dataset.url as string;
    const urls = this.data.imageUrls || [];
    if (!url || !urls.length) return;
    wx.previewImage({
      current: url,
      urls,
    });
  },

  // 删除已选图片
  onRemoveImage(e: WechatMiniprogram.TouchEvent) {
    const index = Number(e.currentTarget.dataset.index || 0);
    const images = this.data.images.slice();
    const imageUrls = this.data.imageUrls.slice();
    images.splice(index, 1);
    imageUrls.splice(index, 1);
    this.setData({ images, imageUrls });
  },

  // 提交反馈
  async onSubmit() {
    if (this.data.submitting) return;

    const content = (this.data.content || '').trim();
    if (!content) {
      wx.showToast({ title: '请填写问题描述', icon: 'none' });
      return;
    }
    const category = this.data.categoryOptions[this.data.categoryIndex].value;

    this.setData({ submitting: true });
    try {
      await request({
        route: 'feedback/create',
        method: 'POST',
        data: {
          category,
          content,
          images: this.data.images,
        },
      });

      wx.showToast({ title: '已提交', icon: 'success' });
      this.setData({
        showFormDialog: false,
        content: '',
        categoryIndex: 0,
        images: [],
        imageUrls: [],
      });
      this.loadFeedbacks();
    } catch (e: any) {
      const msg =
        e && typeof e === 'object' && 'message' in e && e.message
          ? String((e as any).message)
          : '提交失败';
    
      wx.showToast({
        title: msg,
        icon: 'none',
      });
    }
  },

  // 点击列表项 -> 打开详情弹窗
  onTapFeedback(e: WechatMiniprogram.TouchEvent) {
    const index = Number(e.currentTarget.dataset.index || 0);
    const list = this.data.feedbacks || [];
    const fb = list[index];
    if (!fb) return;
    this.setData({
      detailFeedback: fb,
      showDetailDialog: true,
    });
  },

  // 关闭详情弹窗
  onCloseDetailDialog() {
    this.setData({
      showDetailDialog: false,
      detailFeedback: null,
    });
  },

  // 预览详情里的图片
  onPreviewDetailImage(e: WechatMiniprogram.TouchEvent) {
    const current = e.currentTarget.dataset.url as string;
    const fb = this.data.detailFeedback as any;
    if (!fb || !fb.images || !fb.images.length) return;
    const urls = fb.images.map((it: FeedbackImage) => it.url);
    wx.previewImage({
      current,
      urls,
    });
  },

  onShareAppMessage() {
    return buildShareAppMessage(this) as WechatMiniprogram.Page.IShareAppMessageOption;
  },

  onShareTimeline() {
    return buildShareTimeline(this) as any;
  },
});