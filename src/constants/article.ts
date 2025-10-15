/**
 * 文章相关常量配置
 */

/**
 * 文章状态选项
 */
export const ARTICLE_STATUS_OPTIONS = [
  { label: '草稿', value: 0 },
  { label: '已发布', value: 1 },
  { label: '待审核', value: 2 },
  { label: '已下架', value: 3 }
] as const;

/**
 * 文章状态映射
 */
export const ARTICLE_STATUS_MAP = {
  0: '草稿',
  1: '已发布',
  2: '待审核',
  3: '已下架'
} as const;

/**
 * Markdown 编辑器配置
 */
export const EDITOR_CONFIG = {
  language: 'zh-CN',
  theme: 'light',
  previewTheme: 'default',
  codeTheme: 'atom',
  height: '500px',
  placeholder: '请输入文章内容，支持Markdown语法...',
  scrollAuto: true,
  autoFocus: false,
  autoDetectCode: true,
  tabWidth: 2,
  showCodeRowNumber: true,
  previewOnly: false,
  htmlPreview: true,
  noMermaid: false,
  noKatex: false,
  noHighlight: false,
  noLinkRef: false,
  noImgZoomIn: false,
  maxLength: 50000,
  autoSave: true
} as const;

/**
 * 编辑器工具栏配置
 */
export const EDITOR_TOOLBARS = [
  'bold', 'underline', 'italic', 'strikeThrough', '-',
  'title', 'sub', 'sup', 'quote', '-',
  'unorderedList', 'orderedList', 'task', '-',
  'codeRow', 'code', 'link', '-',
  'image', 'table', 'mermaid', 'katex', '-',
  0, 1, '-',
  'revoke', 'next', 'save', '-',
  '=', 'pageFullscreen', 'fullscreen', 'preview', 'htmlPreview', 'catalog'
] as const;

/**
 * 编辑器底部工具栏配置
 */
export const EDITOR_FOOTERS = ['markdownTotal', '=', 'scrollSwitch'] as const;

/**
 * 编辑器快捷键配置（已废弃，md-editor-v3 自带快捷键）
 */
export const EDITOR_SHORTCUTS = {
  bold: 'Ctrl+B',
  italic: 'Ctrl+I',
  underline: 'Ctrl+U',
  strikeThrough: 'Ctrl+Shift+S',
  title: 'Ctrl+H',
  quote: 'Ctrl+Q',
  unorderedList: 'Ctrl+L',
  orderedList: 'Ctrl+Shift+L',
  code: 'Ctrl+K',
  codeRow: 'Ctrl+Shift+K',
  link: 'Ctrl+Shift+L',
  image: 'Ctrl+Shift+I',
  table: 'Ctrl+T',
  save: 'Ctrl+S',
  revoke: 'Ctrl+Z',
  next: 'Ctrl+Y',
  fullscreen: 'F11',
  preview: 'Ctrl+P'
} as const;

/**
 * 封面图片上传配置
 */
export const COVER_UPLOAD_CONFIG = {
  maxSize: 5, // MB
  acceptFormats: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
  acceptExtensions: /\.(jpe?g|png|gif|webp)$/i,
  recommendedSize: '1200×675px (16:9)'
} as const;

/**
 * 阅读速度配置（字/分钟）
 */
export const READING_SPEED = 300;

/**
 * 摘要最大长度配置
 */
export const SUMMARY_CONFIG = {
  manual: 255, // 手动摘要最大长度
  ai: 200, // AI摘要最大长度
  auto: 100 // 自动摘要长度
} as const;

/**
 * 最小内容长度要求
 */
export const MIN_CONTENT_LENGTH = {
  summary: 50, // 生成摘要所需最小内容长度
  aiSummary: 50 // 生成AI摘要所需最小内容长度
} as const;

/**
 * 文章可见性选项
 */
export const ARTICLE_VISIBILITY_OPTIONS = [
  { label: '公开', value: 'public', icon: 'Globe', color: '#67c23a', tip: '所有人可见，包括未登录用户' },
  { label: '登录可见', value: 'login_required', icon: 'User', color: '#409eff', tip: '仅已登录用户可见' },
  { label: '指定用户', value: 'specific_users', icon: 'Users', color: '#e6a23c', tip: '只有指定的用户可以访问' },
  { label: '指定角色', value: 'specific_roles', icon: 'UserFilled', color: '#909399', tip: '只有指定角色组的成员可以访问' },
  { label: '私密', value: 'private', icon: 'Lock', color: '#f56c6c', tip: '只有作者本人可见' }
] as const;

/**
 * 文章可见性映射
 */
export const ARTICLE_VISIBILITY_MAP = {
  public: '公开',
  login_required: '登录可见',
  specific_users: '指定用户',
  specific_roles: '指定角色',
  private: '私密'
} as const;

