import { http } from "@/utils/http";

// API响应通用格式
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 等级类型
export type LevelType = "user" | "writer" | "reader" | "interaction";

// 等级信息
export interface Level {
  id: number;
  type: LevelType;
  name: string;
  level: number;
  min_experience: number;
  max_experience: number;
  description: string;
  rewards?: any;
  status: number;
  create_time: string;
  update_time: string;
}

// 等级列表响应
export interface LevelListResponse {
  list: Level[];
  pagination: {
    total: number;
    current: number;
    page_size: number;
    pages: number;
  };
}

// 等级类型映射
export const LEVEL_TYPE_MAP = {
  user: "用户等级",
  writer: "写作等级",
  reader: "读者等级",
  interaction: "互动等级"
};

/**
 * 获取所有等级类型
 */
export const getLevelTypes = () => {
  return http.request<ApiResponse<Record<string, string>>>(
    "get",
    "/api/v1/level/types"
  );
};

/**
 * 获取等级列表
 * @param type 等级类型（必需）
 */
export const getLevelList = (data: { type: LevelType; [key: string]: any }) => {
  return http.request<ApiResponse<LevelListResponse>>(
    "get",
    "/api/v1/level/list",
    { params: data }
  );
};

/**
 * 新增等级
 */
export const createLevel = (data: Partial<Level>) => {
  return http.request<ApiResponse>("post", "/api/v1/level/add", { data });
};

/**
 * 编辑等级
 */
export const updateLevel = (data: Partial<Level> & { id: number }) => {
  return http.request<ApiResponse>("put", "/api/v1/level/update", { data });
};

/**
 * 删除等级
 */
export const deleteLevel = (id: number) => {
  return http.request<ApiResponse>("delete", "/api/v1/level/delete", {
    data: { id }
  });
};

/**
 * 设置等级状态
 */
export const setLevelStatus = (id: number, status: number) => {
  return http.request<ApiResponse>("put", "/api/v1/level/setStatus", {
    data: { id, status }
  });
};

/**
 * 获取等级详情
 */
export const getLevelDetail = (id: number) => {
  return http.request<ApiResponse>("get", "/api/v1/level/detail", {
    params: { id }
  });
};

/**
 * 根据经验值获取等级
 */
export const getLevelByExperience = (type: LevelType, experience: number) => {
  return http.request<ApiResponse<Level>>(
    "get",
    "/api/v1/level/getLevelByExperience",
    { params: { type, experience } }
  );
};

/**
 * 批量删除等级
 */
export const batchDeleteLevels = (ids: number[]) => {
  return http.request<ApiResponse>("post", "/api/v1/level/batchDelete", {
    data: { ids }
  });
};

/**
 * 批量更新等级状态
 */
export const batchUpdateStatus = (ids: number[], status: number) => {
  return http.request<ApiResponse>("post", "/api/v1/level/batchStatus", {
    data: { ids, status }
  });
};
