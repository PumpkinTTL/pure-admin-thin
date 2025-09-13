import { http } from "@/utils/http";

export type CategoryResult = {
  success: boolean;
  data: {
    /** 分类ID */
    id: number;
    /** 分类名称 */
    name: string;
    /** 分类标识 */
    slug: string;
    /** 分类描述 */
    description: string;
    /** 分类类型 */
    type: string;
    /** 父分类ID */
    parent_id: number;
    /** 用户ID */
    user_id: number;
    /** 排序 */
    sort_order: number;
    /** 图标 */
    icon: string;
    /** 封面图 */
    cover_image: string | null;
    /** 是否显示 */
    is_show: number;
    /** SEO标题 */
    meta_title: string;
    /** SEO关键词 */
    meta_keywords: string;
    /** SEO描述 */
    meta_description: string;
    /** 创建时间 */
    create_time: string;
    /** 更新时间 */
    update_time: string;
    /** 作者信息 */
    author: {
      id: number;
      nickname: string;
      username: string;
      avatar: string;
    };
  };
};

export type CategoryListResult = {
  success: boolean;
  data: Array<{
    /** 分类ID */
    id: number;
    /** 分类名称 */
    name: string;
    /** 分类标识 */
    slug: string;
    /** 分类描述 */
    description: string;
    /** 分类类型 */
    type: string;
    /** 父分类ID */
    parent_id: number;
    /** 用户ID */
    user_id: number;
    /** 排序 */
    sort_order: number;
    /** 图标 */
    icon: string;
    /** 封面图 */
    cover_image: string | null;
    /** 是否显示 */
    is_show: number;
    /** SEO标题 */
    meta_title: string;
    /** SEO关键词 */
    meta_keywords: string;
    /** SEO描述 */
    meta_description: string;
    /** 创建时间 */
    create_time: string;
    /** 更新时间 */
    update_time: string;
    /** 作者信息 */
    author: {
      id: number;
      nickname: string;
      username: string;
      avatar: string;
    };
  }>;
};

/** 获取分类列表 */
export const getCategoryList = (params?: object) => {
  return http.request("get", "/api/v1/category/selectCategoryAll", {
    params
  });
};

/** 添加分类 */
export const addCategory = (data?: object) => {
  return http.request<CategoryResult>("post", "/api/v1/category/add", { data });
};

/** 更新分类 */
export const updateCategoryR = (data?: object) => {
  return http.request("put", "/api/v1/category/update", {
    data
  });
};

/** 删除分类 */
export const deleteCategory = (data?: object) => {
  return http.request<CategoryResult>("delete", "/api/v1/category/delete", {
    data
  });
};

export const createCategoryR = (data: any = {}) => {
  return http.request("post", "/api/v1/category/add", { data });
};

/** 恢复分类 */
export const restoreCategory = (data?: object) => {
  return http.request("post", "/api/v1/category/restore", { data });
};
