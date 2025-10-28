import { http } from "@/utils/http";

/**
 * 公告系统接口
 */

// 获取公告列表
export const getNoticeList = (params?: any) => {
  return http.request("get", "/api/v1/notice/list", { params });
};

// 获取公告详情
export const getNoticeDetail = (noticeId: number | string) => {
  return http.request("get", `/api/v1/notice/detail/${noticeId}`);
};

// 创建公告
export const createNotice = (data: any) => {
  return http.request("post", "/api/v1/notice/create", { data });
};

// 更新公告
export const updateNotice = (noticeId: number | string, data: any) => {
  return http.request("post", `/api/v1/notice/update/${noticeId}`, { data });
};

// 删除公告
export const deleteNotice = (
  noticeId: number | string,
  isReal: boolean = false
) => {
  return http.request("get", `/api/v1/notice/delete/${noticeId}`, {
    params: { real: isReal }
  });
};

// 批量删除公告
export const batchDeleteNotice = (
  noticeIds: (number | string)[],
  isReal: boolean = false
) => {
  return http.request("post", "/api/v1/notice/batchDelete", {
    data: { ids: noticeIds, real: isReal }
  });
};

// 更新公告状态
export const updateNoticeStatus = (
  noticeId: number | string,
  status: number
) => {
  return http.request("get", `/api/v1/notice/status/${noticeId}`, {
    params: { status }
  });
};

// 发布公告
export const publishNotice = (noticeId: number | string) => {
  return http.request("get", `/api/v1/notice/status/${noticeId}`, {
    params: { status: 1 }
  });
};

// 撤回公告
export const revokeNotice = (noticeId: number | string) => {
  return http.request("get", `/api/v1/notice/status/${noticeId}`, {
    params: { status: 2 }
  });
};

// 切换公告置顶状态
export const toggleNoticeTop = (noticeId: number | string) => {
  return http.request("get", `/api/v1/notice/top/${noticeId}`);
};

// 设置公告置顶状态
export const setNoticeTopStatus = (
  noticeId: number | string,
  isTop: boolean
) => {
  return http.request("get", `/api/v1/notice/top/${noticeId}`, {
    params: { is_top: isTop }
  });
};

// 获取用户可见公告
export const getUserNotices = (userId: number | string, params?: any) => {
  return http.request("get", `/api/v1/notice/user/${userId}`, { params });
};

// 恢复已删除的公告
export const restoreNotice = (noticeId: number | string) => {
  return http.request("get", `/api/v1/notice/restore/${noticeId}`);
};

// 发送公告邮件通知
export const sendNoticeEmail = (data: any) => {
  return http.request("post", "/api/v1/notice/sendEmail", { data });
};

// 公告分类常量
export const CATEGORY_TYPES = {
  SYSTEM_UPDATE: 1, // 系统更新
  ACCOUNT_SECURITY: 2, // 账号安全
  ACTIVITY: 3, // 活动通知
  POLICY: 4, // 政策公告
  OTHER: 5 // 其他
};

// 公告状态常量
export const NOTICE_STATUS = {
  DRAFT: 0, // 草稿
  PUBLISHED: 1, // 已发布
  REVOKED: 2 // 已撤回
};

// 优先级常量
export const PRIORITY_LEVELS = {
  NORMAL: 0, // 普通
  IMPORTANT: 1, // 重要
  URGENT: 2 // 紧急
};

// 公告可见性常量（参考文章模块）
export const NOTICE_VISIBILITY_OPTIONS = [
  {
    label: "公开",
    value: "public",
    icon: "Globe",
    color: "#67c23a",
    tip: "所有人可见，包括未登录用户"
  },
  {
    label: "登录可见",
    value: "login_required",
    icon: "User",
    color: "#409eff",
    tip: "仅已登录用户可见"
  },
  {
    label: "指定用户",
    value: "specific_users",
    icon: "Users",
    color: "#e6a23c",
    tip: "只有指定的用户可以访问"
  },
  {
    label: "指定角色",
    value: "specific_roles",
    icon: "UserFilled",
    color: "#909399",
    tip: "只有指定角色组的成员可以访问"
  }
] as const;

// 可见性映射（用于显示）
export const NOTICE_VISIBILITY_MAP = {
  public: { label: "公开", color: "#67c23a", icon: "Globe" },
  login_required: { label: "登录可见", color: "#409eff", icon: "User" },
  specific_users: { label: "指定用户", color: "#e6a23c", icon: "Users" },
  specific_roles: { label: "指定角色", color: "#909399", icon: "UserFilled" }
};

// 类型定义
export interface NoticeParams {
  page?: number;
  page_size?: number;
  title?: string;
  visibility?: string;
  category_type?: number;
  status?: number;
  priority?: number;
  is_top?: boolean;
  publisher_id?: number | string;
  start_time?: string;
  end_time?: string;
  sort_field?: string;
  sort_order?: "asc" | "desc";
  is_admin?: number | boolean; // 是否为管理端请求（1=管理端，0或不传=客户端）
}

export interface NoticeData {
  notice_id: number;
  title: string;
  content: string;
  notice_type: number;
  category_type: number;
  target_uid?: string;
  publisher_id: number;
  publish_time: string;
  expire_time?: string;
  status: number;
  priority: number;
  is_top: boolean;
  create_time: string;
  update_time: string;
}

export interface NoticeCreateData {
  title: string;
  content: string;
  category_type: number;
  publisher_id: number;
  visibility: string; // 可见性：public, login_required, specific_users, specific_roles
  target_user_ids?: (number | string)[]; // 指定用户ID列表
  target_role_ids?: (number | string)[]; // 指定角色ID列表
  publish_time?: string;
  expire_time?: string;
  status?: number;
  priority?: number;
  is_top?: boolean;
}

export interface NoticeUpdateData {
  title?: string;
  content?: string;
  category_type?: number;
  visibility?: string; // 可见性
  target_user_ids?: (number | string)[]; // 指定用户ID列表
  target_role_ids?: (number | string)[]; // 指定角色ID列表
  publish_time?: string;
  expire_time?: string;
  status?: number;
  priority?: number;
  is_top?: boolean;
}
