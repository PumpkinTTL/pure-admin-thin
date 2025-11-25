import { http } from "@/utils/http";

// 邮件模板数据类型
export interface EmailTemplate {
  id?: number;
  code: string;
  name: string;
  subject: string;
  body?: string; // 兼容前端字段
  content?: string; // 兼容后端字段
  variables?: string | Record<string, string>; // 支持字符串或对象格式
  description?: string;
  is_active?: number;
  create_time?: string;
  update_time?: string;
}

// 查询参数类型
export interface EmailTemplateParams {
  page?: number;
  page_size?: number;
  code?: string;
  name?: string;
  is_active?: number;
}

/**
 * 获取邮件模板列表
 */
export const getEmailTemplateList = (params?: EmailTemplateParams) => {
  return http.request("get", "/api/v1/emailTemplate/list", { params });
};

/**
 * 获取单个邮件模板详情
 */
export const getEmailTemplate = (id: number) => {
  return http.request("get", "/api/v1/emailTemplate/detail", {
    params: { id }
  });
};

/**
 * 创建邮件模板
 */
export const createEmailTemplate = (data: EmailTemplate) => {
  return http.request("post", "/api/v1/emailTemplate/create", { data });
};

/**
 * 更新邮件模板
 */
export const updateEmailTemplate = (id: number, data: EmailTemplate) => {
  return http.request("post", "/api/v1/emailTemplate/update", {
    data: { ...data, id }
  });
};

/**
 * 删除邮件模板
 * @param id 模板ID
 * @param realDel 是否物理删除，true=物理删除，false=软删除(默认)
 */
export const deleteEmailTemplate = (id: number, realDel: boolean = false) => {
  return http.request("post", "/api/v1/emailTemplate/delete", {
    data: { id, real_del: realDel ? 1 : 0 }
  });
};

/**
 * 恢复邮件模板
 */
export const restoreEmailTemplate = (id: number) => {
  return http.request("post", "/api/v1/emailTemplate/restore", {
    data: { id }
  });
};

/**
 * 切换模板启用状态
 */
export const toggleEmailTemplateStatus = (id: number, isActive: number) => {
  return http.request("post", "/api/v1/emailTemplate/toggleStatus", {
    data: { id, is_active: isActive }
  });
};

/**
 * 发送测试邮件
 */
export const sendTestEmail = (id: number, email: string) => {
  return http.request("post", "/api/v1/emailTemplate/sendTest", {
    data: { id, email }
  });
};

/**
 * 根据模板代码获取模板
 */
export const getEmailTemplateByCode = (code: string) => {
  return http.request("get", "/api/v1/emailTemplate/detail", {
    params: { code }
  });
};
