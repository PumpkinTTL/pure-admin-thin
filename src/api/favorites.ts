import { http } from "@/utils/http";

/**
 * 收藏相关接口
 */

// 收藏记录接口
export interface FavoriteRecord {
  id: number;
  user_id: number;
  target_type: string;
  target_id: number;
  create_time: number | string;
  update_time?: number | string;
  delete_time?: number | string;
  user?: {
    id: number;
    username: string;
    avatar?: string;
  };
}

// 收藏列表响应
export interface FavoriteListResponse {
  code: number;
  msg: string;
  data: {
    list: FavoriteRecord[];
    total: number;
    page: number;
    limit: number;
  };
}

// 通用响应
export interface ApiResponse {
  code: number;
  msg: string;
  data?: any;
}

/**
 * 收藏/取消收藏
 * POST /api/v1/favorites/toggle
 */
export const toggleFavorite = (data: {
  target_type: string;
  target_id: number;
  user_id: number;
}) => {
  return http.request<ApiResponse>("post", "/api/v1/favorites/toggle", { data });
};

/**
 * 获取收藏列表
 * GET /api/v1/favorites/list
 */
export const getFavoritesList = (params: {
  user_id?: number;
  target_type?: string;
  page?: number;
  limit?: number;
}) => {
  return http.request<FavoriteListResponse>("get", "/api/v1/favorites/list", { params });
};
