import { http } from "@/utils/http";

// ==================== 类型定义 ====================

/**
 * 邮件记录数据
 */
export interface EmailRecordData {
  id: number;
  notice_id?: number;
  notice_title?: string;
  sender_id: number;
  sender_name?: string;
  sender_email?: string;
  title: string;
  content: string;
  receiver_type: number;
  receiver_type_text?: string;
  total_count: number;
  success_count: number;
  failed_count: number;
  status: number;
  status_text?: string;
  success_rate?: number;
  send_time?: string;
  create_time: string;
  update_time?: string;
  delete_time?: string;
}

/**
 * 邮件接收者数据
 */
export interface EmailReceiverData {
  id: number;
  record_id: number;
  user_id?: number;
  username?: string;
  email: string;
  receiver_type_text?: string;
  status: number;
  status_text?: string;
  error_msg?: string;
  send_time?: string;
  create_time: string;
}

/**
 * 邮件发送参数
 */
export interface EmailSendParams {
  notice_id?: number;
  sender_id: number;
  title: string;
  content: string;
  receiver_type: number; // 1-全部用户, 2-指定多个用户, 3-单个用户, 4-指定邮箱
  receiver_ids?: number[]; // receiver_type为2或3时必填
  receiver_emails?: string[]; // receiver_type为4时必填
}

/**
 * 邮件记录查询参数
 */
export interface EmailRecordParams {
  page?: number;
  page_size?: number;
  title?: string;
  sender_id?: number;
  status?: number;
  receiver_type?: number;
  notice_id?: number;
  start_time?: string;
  end_time?: string;
  include_deleted?: number; // 0=不包含软删除(默认), 1=仅软删除, 2=包含所有
}

/**
 * 邮件接收者查询参数
 */
export interface EmailReceiverParams {
  record_id: number;
  page?: number;
  page_size?: number;
  status?: number;
  email?: string;
}

/**
 * 邮件统计数据
 */
export interface EmailStatistics {
  total_records: number;
  total_emails: number;
  success_emails: number;
  failed_emails: number;
  success_rate: number;
}

/**
 * 重发邮件参数
 */
export interface ResendEmailParams {
  record_id: number;
  receiver_ids?: number[];
}

// ==================== 常量定义 ====================

/**
 * 接收方式
 */
export const RECEIVER_TYPES = {
  ALL_USERS: 1, // 全部用户
  MULTIPLE_USERS: 2, // 指定多个用户
  SINGLE_USER: 3, // 单个用户
  EMAILS: 4 // 指定邮箱
};

/**
 * 接收方式文本映射
 */
export const RECEIVER_TYPE_TEXT = {
  1: "全部用户",
  2: "指定多个用户",
  3: "单个用户",
  4: "指定邮箱"
};

/**
 * 发送状态
 */
export const SEND_STATUS = {
  PENDING: 0, // 待发送
  SENDING: 1, // 发送中
  COMPLETED: 2, // 发送完成
  PARTIAL_FAILED: 3, // 部分失败
  ALL_FAILED: 4 // 全部失败
};

/**
 * 发送状态文本映射
 */
export const SEND_STATUS_TEXT = {
  0: "待发送",
  1: "发送中",
  2: "发送完成",
  3: "部分失败",
  4: "全部失败"
};

/**
 * 发送状态标签类型
 */
export const SEND_STATUS_TAG_TYPE = {
  0: "info",
  1: "warning",
  2: "success",
  3: "warning",
  4: "danger"
};

/**
 * 接收者发送状态
 */
export const RECEIVER_STATUS = {
  PENDING: 0, // 待发送
  SUCCESS: 1, // 发送成功
  FAILED: 2 // 发送失败
};

/**
 * 接收者发送状态文本映射
 */
export const RECEIVER_STATUS_TEXT = {
  0: "待发送",
  1: "发送成功",
  2: "发送失败"
};

/**
 * 接收者发送状态标签类型
 */
export const RECEIVER_STATUS_TAG_TYPE = {
  0: "info",
  1: "success",
  2: "danger"
};

// ==================== API 接口 ====================

/**
 * 获取邮件记录列表
 */
export const getEmailRecordList = (params: EmailRecordParams) => {
  return http.request("get", "/api/v1/emailRecord/list", { params });
};

/**
 * 获取邮件记录详情
 */
export const getEmailRecordDetail = (id: number) => {
  return http.request("get", "/api/v1/emailRecord/detail", {
    params: { id }
  });
};

/**
 * 获取接收者列表
 */
export const getEmailReceivers = (params: EmailReceiverParams) => {
  return http.request("get", "/api/v1/emailRecord/receivers", { params });
};

/**
 * 发送邮件
 */
export const sendEmail = (data: EmailSendParams) => {
  return http.request("post", "/api/v1/emailRecord/send", { data });
};

/**
 * 重新发送失败的邮件
 */
export const resendEmail = (data: ResendEmailParams) => {
  return http.request("post", "/api/v1/emailRecord/resend", { data });
};

/**
 * 删除邮件记录
 */
export const deleteEmailRecord = (id: number) => {
  return http.request("delete", "/api/v1/emailRecord/delete", {
    params: { id }
  });
};

/**
 * 批量删除邮件记录
 */
export const batchDeleteEmailRecord = (ids: number[]) => {
  return http.request("post", "/api/v1/emailRecord/batchDelete", {
    data: { ids }
  });
};

/**
 * 恢复邮件记录
 */
export const restoreEmailRecord = (id: number) => {
  return http.request("post", "/api/v1/emailRecord/restore", {
    data: { id }
  });
};

/**
 * 获取统计数据
 */
export const getEmailStatistics = (params?: {
  start_time?: string;
  end_time?: string;
}) => {
  return http.request("get", "/api/v1/emailRecord/statistics", { params });
};
