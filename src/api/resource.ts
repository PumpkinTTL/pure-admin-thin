import { http } from "@/utils/http";

// 接口定义
export interface BaseResponse<T = any> {
  code: number;
  message?: string;
  data: T;
}

export interface ResourceTag {
  id: number;
  name: string;
}

export interface DownloadMethod {
  id: number;
  resource_id: number;
  method_name: string;
  download_link: string;
  extraction_code: string;
  create_time: string;
  update_time: string;
  status: number;
  sort_order: number;
  delete_time: string | null;
}

export interface ResourceItem {
  id: number;
  resource_name: string;
  category_id: number;
  platform: string;
  user_id: number;
  release_time: string;
  download_count: number;
  view_count: number;
  favorite_count: number;
  resource_size: number | string;
  version: string;
  file_format: string;
  update_time: string;
  publish_time?: string;
  cover_url: string;
  description: string;
  file_hash: string;
  language: string;
  is_premium: number | boolean;
  delete_time: string | null;
  author?: {
    id: number;
    username: string;
    avatar: string;
  };
  user?: {
    id: number;
    username: string;
    avatar: string;
  };
  tags?: Array<{
    id?: number;
    name: string;
    pivot?: {
      id: number;
      resource_id: number;
      category_id: number;
      create_time: string;
    }
  }>;
  downloadLinks?: Array<{
    id: number;
    resource_id: number;
    method_name: string;
    download_link: string;
    extraction_code: string;
  }>;
  downloadMethods?: Array<{
    id: number;
    resource_id: number;
    method_name: string;
    download_link: string;
    extraction_code: string;
    status: number;
    sort_order: number;
  }>;
  category?: string;
}

export interface PageResult<T = any> {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  data: T[];
  has_more: boolean;
}

// 资源列表接口
export const getResourceList = (params?: any) => {
  return http.request<BaseResponse<PageResult<ResourceItem>>>("get", "/api/v1/resource/selectResourceAll", { params });
};

// 获取资源详情
export const getResourceDetail = (id: number) => {
  return http.request<BaseResponse<ResourceItem>>("get", `/api/v1/resource/selectResourceById`, {
    params: { id }
  });
};

// 添加资源
export const addResource = (data: any) => {
  return http.request<BaseResponse<ResourceItem>>("post", "/api/v1/resource/add", { data });
};

// 更新资源
export const updateResource = (data: any) => {
  return http.request<BaseResponse<ResourceItem>>("post", "/api/v1/resource/update", { data });
};

// 删除资源（软删除）
export const deleteResource = (id: number, options?: { real?: boolean }) => {
  return http.request<BaseResponse<null>>("post", "/api/v1/resource/delete", {
    data: { id, ...options }
  });
};

// 批量删除资源
export const batchDeleteResource = (ids: number[], options?: { real?: boolean }) => {
  return http.request<BaseResponse<null>>("post", "/api/v1/resource/batchDelete", {
    data: { ids, ...options }
  });
};

// 恢复资源
export const restoreResource = (id: number) => {
  return http.request<BaseResponse<null>>("post", "/api/v1/resource/restore", {
    data: { id }
  });
};

// 批量恢复资源
export const batchRestoreResource = (ids: number[]) => {
  return http.request<BaseResponse<null>>("post", "/api/v1/resource/batchRestore", {
    data: { ids }
  });
};

// 获取分类列表
export const getCategoryList = () => {
  return http.request<BaseResponse<any[]>>("get", "/api/v1/category/selectCategoryAll");
};



