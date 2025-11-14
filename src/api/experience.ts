import { http } from "@/utils/http";
import type { LevelType } from "./level";

// API响应通用格式
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 等级信息
export interface LevelInfo {
  target_type: LevelType;
  target_id: number;
  current_level: number;
  level_name: string;
  level_icon: string;
  level_color: string;
  total_experience: number;
  experience_in_level: number;
  level_up_count: number;
  last_level_up_time: string | null;
  next_level: number | null;
  next_level_name: string;
  next_level_experience: number | null;
  experience_progress: number;
}

// 经验日志
export interface ExperienceLog {
  id: number;
  target_type: LevelType;
  target_id: number;
  experience_amount: number;
  source_type: string;
  source_id?: number;
  description?: string;
  level_before: number;
  level_after: number;
  is_level_up: number;
  status: number;
  create_time: string;
}

// 经验日志列表响应
export interface ExperienceLogResponse {
  list: ExperienceLog[];
  pagination: {
    total: number;
    current: number;
    page_size: number;
    pages: number;
  };
}

// 经验来源配置
export interface ExperienceSource {
  type: string;
  name: string;
  experience: number;
  description: string;
  support_types: LevelType[];
}

// 经验来源统计
export interface ExperienceSourceStat {
  source_type: string;
  count: number;
  total_exp: number;
}

/**
 * 获取经验日志
 */
export const getExperienceLogs = (data: {
  target_type: LevelType;
  target_id: number;
  page?: number;
  page_size?: number;
}) => {
  return http.request<ApiResponse<ExperienceLogResponse>>(
    "get",
    "/api/v1/experience/logs",
    { params: data }
  );
};

/**
 * 添加经验（管理员手动）
 */
export const addExperience = (data: {
  target_type: LevelType;
  target_id: number;
  experience_amount: number;
  source_type: string;
  source_id?: number;
  description?: string;
}) => {
  return http.request<ApiResponse>("post", "/api/v1/experience/add", { data });
};

/**
 * 获取等级信息
 */
export const getLevelInfo = (targetType: LevelType, targetId: number) => {
  return http.request<ApiResponse<LevelInfo>>(
    "get",
    "/api/v1/experience/levelInfo",
    { params: { target_type: targetType, target_id: targetId } }
  );
};

/**
 * 获取用户所有类型的等级信息
 */
export const getUserAllLevels = (userId: number) => {
  return http.request<ApiResponse<Record<LevelType, LevelInfo>>>(
    "get",
    "/api/v1/experience/userAllLevels",
    { params: { user_id: userId } }
  );
};

/**
 * 获取经验来源配置
 */
export const getExperienceSources = (targetType?: LevelType) => {
  return http.request<ApiResponse<ExperienceSource[]>>(
    "get",
    "/api/v1/experience/sources",
    { params: targetType ? { target_type: targetType } : undefined }
  );
};

/**
 * 撤销经验
 */
export const revokeExperience = (logId: number) => {
  return http.request<ApiResponse>("put", "/api/v1/experience/revoke", {
    data: { log_id: logId }
  });
};

/**
 * 获取经验来源统计
 */
export const getExperienceSourceStats = (
  targetType: LevelType,
  targetId: number
) => {
  return http.request<ApiResponse<ExperienceSourceStat[]>>(
    "get",
    "/api/v1/experience/sourceStats",
    { params: { target_type: targetType, target_id: targetId } }
  );
};

/**
 * 获取等级记录列表（联查用户信息）
 */
export const getLevelRecords = (data: {
  target_type: LevelType;
  page?: number;
  page_size?: number;
}) => {
  return http.request<ApiResponse<any>>("get", "/api/v1/experience/records", {
    params: data
  });
};
