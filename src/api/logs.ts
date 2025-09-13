import { http } from "@/utils/http";

// 定义API响应数据结构
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 定义API日志数据结构
export interface ApiLogInfo {
  id: number;
  user_id?: number;
  api_key?: string;
  device_fingerprint?: string;
  http_method: string;
  url_path: string;
  request_params?: string;
  ip: string;
  user_agent?: string;
  status_code: number;
  error_code?: string;
  execution_time: number;
  create_time: string;
}

// 定义API日志列表请求参数
export interface ApiLogListParams {
  page?: number;
  page_size?: number;
  user_id?: number;
  url_path?: string;
  http_method?: string;
  status_code?: number;
  ip?: string;
  start_time?: string;
  end_time?: string;
  order_field?: string;
  order_direction?: 'asc' | 'desc';
}

// 定义API日志统计数据结构
export interface ApiLogStats {
  total_count: number;
  error_count: number;
  avg_execution_time: number;
  max_execution_time: number;
  request_per_minute: number;
  status_distribution: Record<string, number>;
  method_distribution: Record<string, number>;
}

// 获取API日志列表
export const getApiLogList = (params?: ApiLogListParams) => {
  return http.request<ApiResponse<{
    list: Array<ApiLogInfo>;
    pagination: {
      total: number;
      current: number;
      page_size: number;
      pages: number;
    }
  }>>("get", "/api/v1/apiLog/list", { params });
};

// 获取API日志详情
export const getApiLogDetail = (id: number) => {
  return http.request<ApiResponse<ApiLogInfo>>("get", "/api/v1/apiLog/detail", { params: { id } });
};

// 获取API日志统计信息
export const getApiLogStats = (time_range: 'today' | 'yesterday' | 'week' | 'month' = 'today') => {
  return http.request<ApiResponse<ApiLogStats>>("get", "/api/v1/apiLog/stats", { params: { time_range } });
};

// 清理过期日志
export const cleanApiLogs = (days: number = 30) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/apiLog/clean", { data: { days } });
};

// 获取特定用户的API日志
export const getUserApiLogs = (user_id: number, params?: Omit<ApiLogListParams, 'user_id'>) => {
  return http.request<ApiResponse<{
    list: Array<ApiLogInfo>;
    pagination: {
      total: number;
      current: number;
      page_size: number;
      pages: number;
    }
  }>>("get", "/api/v1/apiLog/user", { params: { user_id, ...params } });
};

// 获取错误日志
export const getApiErrorLogs = (params?: ApiLogListParams) => {
  return http.request<ApiResponse<{
    list: Array<ApiLogInfo>;
    pagination: {
      total: number;
      current: number;
      page_size: number;
      pages: number;
    }
  }>>("get", "/api/v1/apiLog/errors", { params });
};

// 获取慢日志
export const getApiSlowLogs = (threshold: number = 1000, params?: ApiLogListParams) => {
  return http.request<ApiResponse<{
    list: Array<ApiLogInfo>;
    pagination: {
      total: number;
      current: number;
      page_size: number;
      pages: number;
    }
  }>>("get", "/api/v1/apiLog/slow", { params: { threshold, ...params } });
}; 