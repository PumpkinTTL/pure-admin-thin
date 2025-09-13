import { http } from "@/utils/http";

// 定义API响应数据结构
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 定义支付方式列表响应数据结构
export interface PaymentMethodListResponse {
  list: Array<PaymentMethod>;
  pagination: {
    total: number;
    current: number;
    page_size: number;
    pages: number;
  };
}

// 定义支付方式信息结构
export interface PaymentMethod {
  id: number;
  name: string;
  code: string;
  type: number;
  type_text: string;
  icon: string;
  currency_code: string;
  currency_symbol: string;
  is_crypto: number;
  is_crypto_text: string;
  network: string;
  contract_address: string;
  status: number;
  status_text: string;
  sort_order: number;
  is_default: number;
  is_default_text: string;
  create_time: string;
  update_time: string;
}

// 定义搜索表单结构
export interface PaymentMethodSearchForm {
  page?: number;
  limit?: number;
  id?: string;
  name?: string;
  code?: string;
  type?: string;
  status?: string;
  is_crypto?: string;
  currency_code?: string;
  network?: string;
  is_default?: string;
}

// 定义添加/编辑表单结构
export interface PaymentMethodForm {
  name: string;
  code: string;
  type: number;
  icon?: string;
  currency_code?: string;
  currency_symbol?: string;
  is_crypto?: number;
  network?: string;
  contract_address?: string;
  status?: number;
  sort_order?: number;
  is_default?: number;
}

// 获取支付方式列表
export const getPaymentMethodList = (params: PaymentMethodSearchForm) => {
  return http.request<ApiResponse<PaymentMethodListResponse>>("get", "/api/v1/paymentMethod/selectPaymentMethodAll", { params });
};

// 获取支付方式详情
export const getPaymentMethodDetail = (id: number) => {
  return http.request<ApiResponse<PaymentMethod>>("get", "/api/v1/paymentMethod/selectPaymentMethodById", { params: { id } });
};

// 添加支付方式
export const addPaymentMethod = (data: PaymentMethodForm) => {
  return http.request<ApiResponse<PaymentMethod>>("post", "/api/v1/paymentMethod/add", { data });
};

// 更新支付方式
export const updatePaymentMethod = (id: number, data: Partial<PaymentMethodForm>) => {
  return http.request<ApiResponse<PaymentMethod>>("post", "/api/v1/paymentMethod/update", { data: { id, ...data } });
};

// 删除支付方式
export const deletePaymentMethod = (id: number) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/paymentMethod/delete", { data: { id } });
};

// 更新支付方式状态
export const updatePaymentMethodStatus = (id: number, status: number) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/paymentMethod/updateStatus", { data: { id, status } });
};

// 设置默认支付方式
export const setDefaultPaymentMethod = (id: number) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/paymentMethod/setDefault", { data: { id } });
};

// 获取启用的支付方式
export const getEnabledPaymentMethods = (params?: { type?: number; is_crypto?: number; currency_code?: string }) => {
  return http.request<ApiResponse<PaymentMethod[]>>("get", "/api/v1/paymentMethod/getEnabledList", { params });
};
