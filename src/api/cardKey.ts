/**
 * 卡密管理API接口
 * 
 * 封装所有卡密相关的HTTP请求
 * 
 * @author AI Assistant
 * @date 2025-10-01
 */

import { http } from "@/utils/http";

/**
 * 卡密数据类型定义
 */
export interface CardKey {
  id: number;
  code: string;
  type: string;
  status: number;
  price?: number;
  valid_minutes: number;
  create_time: string;
  use_time?: string;
  user_id?: number;
  remark?: string;
  status_text?: string;
  valid_text?: string;
  is_expired?: boolean;
  expire_time?: string;
  remaining_time?: number;
  username?: string;
  nickname?: string;
}

/**
 * 生成卡密参数类型
 */
export interface GenerateParams {
  type: string;
  count?: number;
  price?: number;
  valid_minutes: number;
  remark?: string;
  salt?: string;
}

/**
 * 卡密列表查询参数类型
 */
export interface CardKeyListParams {
  page?: number;
  limit?: number;
  type?: string;
  status?: number | string;
  code?: string;
  create_time?: string[];
  start_time?: string;
  end_time?: string;
}

/**
 * 使用卡密参数类型
 */
export interface UseCardKeyParams {
  code: string;
  user_id: number;
  remark?: string;
}

/**
 * 禁用卡密参数类型
 */
export interface DisableCardKeyParams {
  user_id: number;
  reason?: string;
}

/**
 * 卡密使用记录类型
 */
export interface CardKeyLog {
  id: number;
  card_key_id: number;
  user_id: number;
  action: string;
  expire_time?: string;
  ip?: string;
  user_agent?: string;
  create_time: string;
  remark?: string;
  username?: string;
  nickname?: string;
}

/**
 * 统计数据类型
 */
export interface CardKeyStatistics {
  overview: {
    total: number;
    unused: number;
    used: number;
    disabled: number;
    expired: number;
    usage_rate: number;
  };
  by_type: Array<{
    type: string;
    count: number;
    unused_count: number;
    used_count: number;
    disabled_count: number;
  }>;
  today: {
    created: number;
    used: number;
  };
  week: {
    created: number;
    used: number;
  };
  month: {
    created: number;
    used: number;
  };
}

/**
 * 生成单个卡密
 *
 * @param data 生成参数
 * @returns Promise
 */
export const generateCardKey = (data: GenerateParams) => {
  return http.request<any>("post", "/api/v1/cardkey/generate", { data });
};

/**
 * 批量生成卡密
 *
 * @param data 生成参数（包含count字段）
 * @returns Promise
 */
export const batchGenerateCardKey = (data: GenerateParams) => {
  return http.request<any>("post", "/api/v1/cardkey/batch", { data });
};

/**
 * 获取卡密列表（分页+筛选）
 *
 * @param params 查询参数
 * @returns Promise
 */
export const getCardKeyList = (params: CardKeyListParams) => {
  return http.request<any>("get", "/api/v1/cardkey/list", { params });
};

/**
 * 获取卡密详情
 *
 * @param id 卡密ID
 * @returns Promise
 */
export const getCardKeyDetail = (id: number) => {
  return http.request<any>("get", `/api/v1/cardkey/detail/${id}`);
};

/**
 * 删除卡密
 *
 * @param id 卡密ID
 * @returns Promise
 */
export const deleteCardKey = (id: number) => {
  return http.request<any>("delete", `/api/v1/cardkey/delete/${id}`);
};

/**
 * 批量删除卡密
 *
 * @param ids 卡密ID数组
 * @returns Promise
 */
export const batchDeleteCardKey = (ids: number[]) => {
  return http.request<any>("post", "/api/v1/cardkey/batch-delete", { data: { ids } });
};

/**
 * 验证卡密
 *
 * @param code 卡密码
 * @returns Promise
 */
export const verifyCardKey = (code: string) => {
  return http.request<any>("post", "/api/v1/cardkey/verify", { data: { code } });
};

/**
 * 使用卡密
 *
 * @param data 使用参数
 * @returns Promise
 */
export const useCardKey = (data: UseCardKeyParams) => {
  return http.request<any>("post", "/api/v1/cardkey/use", { data });
};

/**
 * 禁用卡密
 *
 * @param id 卡密ID
 * @param data 禁用参数
 * @returns Promise
 */
export const disableCardKey = (id: number, data: DisableCardKeyParams) => {
  return http.request<any>("post", `/api/v1/cardkey/disable/${id}`, { data });
};

/**
 * 导出卡密
 *
 * @param params 筛选参数
 * @returns Promise
 */
export const exportCardKeys = (params: CardKeyListParams) => {
  return http.request<any>("get", "/api/v1/cardkey/export", {
    params,
    responseType: "blob" // 返回二进制数据
  });
};

/**
 * 获取统计数据
 *
 * @returns Promise
 */
export const getCardKeyStatistics = () => {
  return http.request<any>("get", "/api/v1/cardkey/statistics");
};

/**
 * 获取类型列表
 *
 * @returns Promise
 */
export const getCardKeyTypes = () => {
  return http.request<any>("get", "/api/v1/cardkey/types");
};

/**
 * 获取使用记录
 *
 * @param id 卡密ID
 * @param params 查询参数
 * @returns Promise
 */
export const getCardKeyLogs = (id: number, params?: { page?: number; limit?: number }) => {
  return http.request<any>("get", `/api/v1/cardkey/logs/${id}`, { params });
};

/**
 * 卡密状态枚举
 */
export enum CardKeyStatus {
  UNUSED = 0,    // 未使用
  USED = 1,      // 已使用
  DISABLED = 2   // 已禁用
}

/**
 * 卡密状态文本映射
 */
export const CardKeyStatusMap = {
  [CardKeyStatus.UNUSED]: "未使用",
  [CardKeyStatus.USED]: "已使用",
  [CardKeyStatus.DISABLED]: "已禁用"
};

/**
 * 卡密状态标签类型映射（Element Plus）
 */
export const CardKeyStatusTypeMap = {
  [CardKeyStatus.UNUSED]: "success",
  [CardKeyStatus.USED]: "info",
  [CardKeyStatus.DISABLED]: "danger"
};

/**
 * 格式化有效时长文本
 * 
 * @param minutes 分钟数
 * @returns 格式化后的文本
 */
export const formatValidMinutes = (minutes: number): string => {
  if (minutes === 0) {
    return "永久有效";
  } else if (minutes < 60) {
    return `${minutes}分钟`;
  } else if (minutes < 1440) {
    return `${Math.floor(minutes / 60)}小时`;
  } else {
    return `${Math.floor(minutes / 1440)}天`;
  }
};

/**
 * 预设有效时长选项
 */
export const ValidMinutesOptions = [
  { label: "永久有效", value: 0 },
  { label: "1小时", value: 60 },
  { label: "1天", value: 1440 },
  { label: "7天", value: 10080 },
  { label: "30天", value: 43200 },
  { label: "90天", value: 129600 },
  { label: "自定义", value: -1 }
];

/**
 * 预设卡密类型选项
 */
export const CardKeyTypeOptions = [
  "注册邀请码",
  "商品兑换码",
  "VIP兑换码",
  "积分兑换码",
  "优惠券码",
  "体验卡"
];

