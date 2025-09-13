import { http } from "@/utils/http";

// 定义API响应数据结构
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 定义API接口数据结构
export interface ApiInfo {
  id: number;
  path: string;
  method: string;
  controller: string;
  action: string;
  description?: string;
  status: number;
  create_time: string;
  update_time: string;
  status_text?: string;
}

// 定义API接口列表请求参数
export interface ApiListParams {
  page?: number;
  page_size?: number;
  keyword?: string;
  status?: number;
}

// 获取接口列表
export const getApiList = (params?: ApiListParams) => {
  return http.request<ApiResponse<{
    list: Array<ApiInfo>;
    total: number;
    page: number;
    page_size: number;
  }>>("get", "/api/v1/api/list", { params });
};

// 获取接口详情
export const getApiDetail = (id: number) => {
  return http.request<ApiResponse<ApiInfo>>("get", "/api/v1/api/detail", { params: { id } });
};

// 更新接口信息
export const updateApi = (data: { id: number; description?: string; status?: number }) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/api/update", { data });
};

// 重置接口数据
export const resetApiData = (clear_existing: boolean = false) => {
  return http.request<ApiResponse<{
    imported_count: number;
    clear_existing: boolean;
  }>>("post", "/api/v1/api/reset", { data: { clear_existing } });
};

// 更新接口状态
export const updateApiStatus = (id: number, status: number) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/api/status", { data: { id, status } });
};

// 批量更新接口状态
export const batchUpdateApiStatus = (ids: number[], status: number) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/api/batchStatus", { data: { ids, status } });
}; 