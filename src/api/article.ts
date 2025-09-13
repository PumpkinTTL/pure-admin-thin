import { http } from "@/utils/http";

/**
 * 获取文章列表(管理后台)
 * @param params 查询参数
 */
export const getArticleList = (params?: any) => {
  return http.request("get", "/api/v1/article/selectArticleAll", { params });
};

/**
 * 获取文章列表(前台)
 * @param params 查询参数
 */
export const getFrontArticleList = (params?: any) => {
  return http.request("get", "/api/v1/article/getArticleList", { params });
};

/**
 * 根据ID获取文章详情
 * @param id 文章ID
 */
export const getArticleById = (id: number) => {
  return http.request("get", "/api/v1/article/selectArticleById", { params: { id } });
};

/**
 * 添加文章
 * @param data 文章数据
 */
export const addArticle = (data: any) => {
  return http.request("post", "/api/v1/article/add", { data });
};

/**
 * 更新文章
 * @param data 文章数据
 */
export const updateArticle = (data: any) => {
  return http.request("post", "/api/v1/article/update", { data });
};

/**
 * 删除文章
 * @param id 文章ID
 * @param real 是否物理删除，默认false(软删除)
 */
export const deleteArticle = (id: number, real: boolean = false) => {
  return http.request("post", "/api/v1/article/delete", { data: { id, real } });
};

/**
 * 恢复已删除的文章
 * @param id 文章ID
 */
export const restoreArticle = (id: number) => {
  return http.request("post", "/api/v1/article/restore", { data: { id } });
};

/**
 * 获取已删除的文章列表
 * @param params 查询参数
 */
export const getDeletedArticles = (params?: any) => {
  return http.request("get", "/api/v1/article/getDeletedArticles", { params });
};

/**
 * 生成文章摘要
 * @param prompt 文章内容或相关提示文本
 */
export const generateArticleSummary = (prompt: string) => {
  return http.request("post", "/api/v1/article/getSummary", { data: { prompt } });
};


