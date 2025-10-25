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

// 公告类型常量
export const NOTICE_TYPES = {
  ALL: 1, // 全体公告
  PARTIAL: 2, // 部分用户公告
  PERSONAL: 3 // 个人通知
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

// 类型定义
export interface NoticeParams {
  page?: number;
  page_size?: number;
  title?: string;
  notice_type?: number;
  category_type?: number;
  status?: number;
  priority?: number;
  is_top?: boolean;
  publisher_id?: number | string;
  start_time?: string;
  end_time?: string;
  sort_field?: string;
  sort_order?: "asc" | "desc";
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
  notice_type: number;
  category_type: number;
  publisher_id: number;
  target_uids?: (number | string)[];
  publish_time?: string;
  expire_time?: string;
  status?: number;
  priority?: number;
  is_top?: boolean;
}

export interface NoticeUpdateData {
  title?: string;
  content?: string;
  notice_type?: number;
  category_type?: number;
  target_uids?: (number | string)[];
  publish_time?: string;
  expire_time?: string;
  status?: number;
  priority?: number;
  is_top?: boolean;
}
