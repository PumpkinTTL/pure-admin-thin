/**
 * 捐赠记录管理API接口
 *
 * 封装所有捐赠记录相关的HTTP请求
 *
 * @author AI Assistant
 * @date 2025-01-28
 */

import { http } from "@/utils/http";

/**
 * 用户信息类型定义（关联查询）
 */
export interface DonationUser {
  id: number;
  nickname?: string;
  avatar?: string;
}

/**
 * 捐赠记录数据类型定义
 */
export interface Donation {
  id: number;
  donation_no: string;
  user_id?: number;
  donor_name?: string;
  donor_contact?: string;
  amount?: number;
  channel: "wechat" | "alipay" | "crypto" | "cardkey";
  status: number;

  // 微信/支付宝字段
  order_no?: string;
  transaction_id?: string;
  payment_time?: string;

  // 加密货币字段
  crypto_type?: string;
  crypto_network?: string;
  transaction_hash?: string;
  from_address?: string;
  to_address?: string;
  confirmation_count?: number;

  // 卡密兑换字段
  card_key_id?: number;
  card_key_code?: string;
  card_key_value?: number;

  // 通用字段
  remark?: string;
  admin_remark?: string;
  is_anonymous: number;
  is_public: number;
  email?: string;
  iden?: string;
  create_time: string;
  update_time?: string;
  confirm_time?: string;
  delete_time?: string;

  // 关联数据（嵌套对象）
  user?: DonationUser;

  // 格式化字段
  channel_text?: string;
  status_text?: string;
  crypto_type_text?: string;
  crypto_network_text?: string;
  amount_formatted?: string;
  card_key_value_formatted?: string;
}

/**
 * 捐赠列表查询参数类型
 */
export interface DonationListParams {
  page?: number;
  page_size?: number;
  donation_no?: string;
  user_id?: number;
  donor_name?: string;
  channel?: string;
  status?: number | string;
  is_anonymous?: number | string;
  min_amount?: number;
  max_amount?: number;
  start_time?: string;
  end_time?: string;
  query_deleted?: "not_deleted" | "only_deleted" | "with_deleted";
  order_field?: string;
  order_type?: "asc" | "desc";
}

/**
 * 统计数据类型
 */
export interface DonationStatistics {
  total_count: number;
  status_stats: {
    pending: number;
    confirmed: number;
    completed: number;
    cancelled: number;
  };
  channel_stats: {
    wechat: number;
    alipay: number;
    crypto: number;
    cardkey: number;
  };
  amount_stats: {
    total_amount: number;
    cardkey_total_value: number;
    today_count: number;
    today_amount: number;
    month_count: number;
    month_amount: number;
  };
}

/**
 * 获取捐赠记录列表
 *
 * @param params 查询参数
 * @returns Promise
 */
export const getDonationList = (params: DonationListParams) => {
  return http.request<any>("get", "/api/v1/donation/list", { params });
};

/**
 * 获取捐赠记录详情
 *
 * @param id 捐赠ID
 * @returns Promise
 */
export const getDonationDetail = (id: number) => {
  return http.request<any>("get", "/api/v1/donation/detail", {
    params: { id }
  });
};

/**
 * 添加捐赠记录
 *
 * @param data 捐赠数据
 * @returns Promise
 */
export const addDonation = (data: Partial<Donation>) => {
  return http.request<any>("post", "/api/v1/donation/add", { data });
};

/**
 * 更新捐赠记录
 *
 * @param id 捐赠ID
 * @param data 更新数据
 * @returns Promise
 */
export const updateDonation = (id: number, data: Partial<Donation>) => {
  return http.request<any>("post", "/api/v1/donation/update", {
    data: { id, ...data }
  });
};

/**
 * 删除捐赠记录（软删除）
 *
 * @param id 捐赠ID
 * @returns Promise
 */
export const deleteDonation = (id: number) => {
  return http.request<any>("post", "/api/v1/donation/delete", { data: { id } });
};

/**
 * 批量删除捐赠记录
 *
 * @param ids 捐赠ID数组
 * @returns Promise
 */
export const batchDeleteDonation = (ids: number[]) => {
  return http.request<any>("post", "/api/v1/donation/batchDelete", {
    data: { ids }
  });
};

/**
 * 恢复捐赠记录
 *
 * @param id 捐赠ID
 * @returns Promise
 */
export const restoreDonation = (id: number) => {
  return http.request<any>("post", "/api/v1/donation/restore", {
    data: { id }
  });
};

/**
 * 获取已删除的捐赠记录列表
 *
 * @param params 查询参数
 * @returns Promise
 */
export const getDeletedDonationList = (params: DonationListParams) => {
  return http.request<any>("get", "/api/v1/donation/getDeletedList", {
    params
  });
};

/**
 * 更新捐赠状态
 *
 * @param id 捐赠ID
 * @param status 状态
 * @returns Promise
 */
export const updateDonationStatus = (id: number, status: number) => {
  return http.request<any>("post", "/api/v1/donation/updateStatus", {
    data: { id, status }
  });
};

/**
 * 获取统计数据
 *
 * @returns Promise
 */
export const getDonationStatistics = () => {
  return http.request<any>("get", "/api/v1/donation/statistics");
};

/**
 * 获取渠道选项
 *
 * @returns Promise
 */
export const getChannelOptions = () => {
  return http.request<any>("get", "/api/v1/donation/channelOptions");
};

/**
 * 获取状态选项
 *
 * @returns Promise
 */
export const getStatusOptions = () => {
  return http.request<any>("get", "/api/v1/donation/statusOptions");
};

/**
 * 捐赠状态枚举
 */
export enum DonationStatus {
  PENDING = 0, // 待确认
  CONFIRMED = 1, // 已确认
  COMPLETED = 2, // 已完成
  CANCELLED = 3 // 已取消
}

/**
 * 捐赠渠道枚举
 */
export enum DonationChannel {
  WECHAT = "wechat",
  ALIPAY = "alipay",
  CRYPTO = "crypto",
  CARDKEY = "cardkey"
}

/**
 * 捐赠状态标签类型映射（Element Plus）
 */
export const DonationStatusTypeMap = {
  [DonationStatus.PENDING]: "warning",
  [DonationStatus.CONFIRMED]: "primary",
  [DonationStatus.COMPLETED]: "success",
  [DonationStatus.CANCELLED]: "info"
};

/**
 * 加密货币类型选项
 */
export const CryptoTypeOptions = [
  { label: "USDT (泰达币)", value: "USDT" },
  { label: "BTC (比特币)", value: "BTC" },
  { label: "ETH (以太坊)", value: "ETH" },
  { label: "TRX (波场)", value: "TRX" }
];

/**
 * 区块链网络选项
 */
export const CryptoNetworkOptions = [
  { label: "TRC20 (波场网络)", value: "TRC20" },
  { label: "ERC20 (以太坊网络)", value: "ERC20" },
  { label: "BTC (比特币网络)", value: "BTC" },
  { label: "BSC (币安智能链)", value: "BSC" }
];
