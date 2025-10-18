import { http } from "@/utils/http";

/**
 * 点赞相关接口
 */

// 点赞记录接口
export interface LikeRecord {
  id: number;
  user_id: number;
  target_type: string;
  target_id: number;
  create_time: number | string;
  update_time?: number | string;
  user?: {
    id: number;
    username: string;
    avatar?: string;
  };
}

// 点赞列表响应
export interface LikeListResponse {
  code: number;
  msg: string;
  data: {
    list: LikeRecord[];
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
 * 点赞/取消点赞
 * POST /api/v1/likes/toggle
 */
export const toggleLike = (data: {
  target_type: string;
  target_id: number;
  user_id: number;
}) => {
  return http.request<ApiResponse>("post", "/api/v1/likes/toggle", { data });
};

/**
 * 检查是否点赞
 * GET /api/v1/likes/check
 */
export const checkLike = (params: {
  target_type: string;
  target_id: number;
  user_id: number;
}) => {
  return http.request<ApiResponse>("get", "/api/v1/likes/check", { params });
};

/**
 * 批量检查点赞状态
 * POST /api/v1/likes/batchCheck
 */
export const batchCheckLikes = (data: {
  target_type: string;
  target_ids: number[];
  user_id: number;
}) => {
  return http.request<ApiResponse>("post", "/api/v1/likes/batchCheck", { data });
};

/**
 * 获取点赞列表
 * GET /api/v1/likes/list
 */
export const getLikesList = (params: {
  user_id?: number;
  target_type?: string;
  page?: number;
  limit?: number;
}) => {
  return http.request<LikeListResponse>("get", "/api/v1/likes/list", { params });
};
