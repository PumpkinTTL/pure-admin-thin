/**
 * 卡密类型管理API接口
 *
 * 封装所有卡密类型相关的HTTP请求
 *
 * @author AI Assistant
 * @date 2025-10-04
 */

import { http } from "@/utils/http";

/**
 * 卡密类型数据类型定义
 */
export interface CardType {
  id: number;
  type_name: string;
  type_code: string;
  /**
   * 使用类型
   * @example 'membership' - 兑换会员
   * @example 'donation' - 捐赠
   * @example 'register' - 注册邀请
   * @example 'product' - 商品兑换
   * @example 'points' - 积分兑换
   * @example 'other' - 其他
   */
  use_type?: string;
  description?: string;
  icon?: string;
  /**
   * 会员时长(分钟)
   * @example null - 不需要会员时长字段
   * @example 0 - 永久会员
   * @example 43200 - 30天会员
   */
  membership_duration?: number | null;
  /**
   * 价格
   * @example null - 不需要价格字段
   * @example 99.00 - 价格99元
   */
  price?: number | null;
  /**
   * 可兑换天数
   * @example null - 永久可兑换
   * @example 90 - 生成后90天内可兑换
   */
  available_days?: number | null;
  sort_order: number;
  status: number;
  create_time: string;
  update_time?: string;
}

/**
 * 类型列表查询参数类型
 */
export interface CardTypeListParams {
  page?: number;
  limit?: number;
  status?: number | string;
  type_name?: string;
}

/**
 * 创建/更新类型参数类型
 */
export interface CardTypeFormData {
  type_name: string;
  type_code: string;
  use_type?: string;
  description?: string;
  icon?: string;
  membership_duration?: number | null;
  price?: number | null;
  available_days?: number | null;
  sort_order?: number;
  status?: number;
}

/**
 * 获取类型列表（分页+筛选）
 *
 * @param params 查询参数
 * @returns Promise
 */
export const getCardTypeList = (params: CardTypeListParams) => {
  return http.request<any>("get", "/api/v1/cardtype/list", { params });
};

/**
 * 获取所有启用的类型（用于下拉选择）
 *
 * @returns Promise
 */
export const getEnabledCardTypes = () => {
  return http.request<any>("get", "/api/v1/cardtype/enabled");
};

/**
 * 获取类型详情
 *
 * @param id 类型ID
 * @returns Promise
 */
export const getCardTypeDetail = (id: number) => {
  return http.request<any>("get", `/api/v1/cardtype/detail/${id}`);
};

/**
 * 创建类型
 *
 * @param data 类型数据
 * @returns Promise
 */
export const createCardType = (data: CardTypeFormData) => {
  return http.request<any>("post", "/api/v1/cardtype/create", { data });
};

/**
 * 更新类型
 *
 * @param id 类型ID
 * @param data 类型数据
 * @returns Promise
 */
export const updateCardType = (id: number, data: CardTypeFormData) => {
  return http.request<any>("put", `/api/v1/cardtype/update/${id}`, { data });
};

/**
 * 删除类型
 *
 * @param id 类型ID
 * @returns Promise
 */
export const deleteCardType = (id: number) => {
  return http.request<any>("delete", `/api/v1/cardtype/delete/${id}`);
};

/**
 * 批量删除类型
 *
 * @param ids 类型ID数组
 * @returns Promise
 */
export const batchDeleteCardType = (ids: number[]) => {
  return http.request<any>("post", "/api/v1/cardtype/batchDelete", {
    data: { ids }
  });
};
