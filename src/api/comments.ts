import { http } from "@/utils/http";

/**
 * 评论相关接口
 */

// 用户信息接口
export interface CommentUser {
  id: number;
  username: string;
  avatar?: string;
}

// 评论接口
export interface Comment {
  id: number;
  user_id: number;
  user?: CommentUser;
  content: string;
  target_id: number; // 目标ID（文章、商品等）
  target_type: string; // 目标类型（article、product、user等）
  article_id?: number; // 兼容旧版本
  parent_id: number;
  status: number; // 0-待审核, 1-已通过, 2-已拒绝
  likes?: number;
  reply_count?: number;
  level?: number;
  create_time: number | string;
  update_time?: number | string;
  delete_time?: number | string | null;
  replies?: Comment[];
  hasChildren?: boolean;
}

// 评论列表响应
export interface CommentListResponse {
  code: number;
  msg: string;
  data: {
    list: Comment[];
    total: number;
    page: number;
    limit: number;
  };
}

// 评论树响应
export interface CommentTreeResponse {
  code: number;
  msg: string;
  data: Comment[];
}

// 评论详情响应
export interface CommentDetailResponse {
  code: number;
  msg: string;
  data: Comment;
}

// 通用响应
export interface ApiResponse {
  code: number;
  msg: string;
  data?: any;
}

// 评论列表查询参数
export interface CommentListParams {
  page?: number;
  limit?: number;
  target_id?: number | string; // 目标ID
  target_type?: string; // 目标类型
  article_id?: number | string; // 兼容旧版本
  status?: number | string;
  keyword?: string;
  user_id?: number;
  parent_id?: number;
}

// 添加评论参数
export interface AddCommentParams {
  target_id?: number; // 目标ID
  target_type?: string; // 目标类型
  article_id?: number; // 兼容旧版本
  parent_id?: number;
  content: string;
  user_id: number;
}

// 更新评论参数
export interface UpdateCommentParams {
  id: number;
  content?: string;
  status?: number;
}

/**
 * 获取评论列表（分页，管理端）
 * 后端路由: GET /api/v1/comments/list
 */
export const getCommentsList = (params: CommentListParams) => {
  return http.request<CommentListResponse>("get", "/api/v1/comments/list", {
    params
  });
};

/**
 * 获取评论列表（通过文章ID）
 * 后端路由: GET /api/v1/comments/getCommentsByArticleId
 */
export const getCommentsByArticleId = (articleId: number, params?: CommentListParams) => {
  return http.request<CommentTreeResponse>("get", "/api/v1/comments/getCommentsByArticleId", {
    params: { article_id: articleId, ...params }
  });
};

/**
 * 获取文章评论树（首次加载，包含嵌套结构）
 * @deprecated 使用 getTargetCommentsTree 替代
 */
export const getCommentsTree = (articleId: number) => {
  return http.request<CommentTreeResponse>(
    "get",
    `/api/v1/comments/getComments/${articleId}`
  );
};

/**
 * 获取目标对象的评论树（通用方法）
 */
export const getTargetCommentsTree = (targetId: number, targetType: string = 'article') => {
  return http.request<CommentTreeResponse>(
    "get",
    `/api/v1/comments/getTargetComments/${targetId}/${targetType}`
  );
};

/**
 * 获取子评论（懒加载）
 */
export const getCommentsChildren = (parentId: number) => {
  return http.request<CommentTreeResponse>(
    "get",
    `/api/v1/comments/getCommentsChildren/${parentId}`
  );
};

// ========== 增删改接口（已实现） ==========

/**
 * 添加评论
 * 后端路由: POST /api/v1/comments/add
 */
export const addComment = (data: AddCommentParams) => {
  return http.request<ApiResponse>("post", "/api/v1/comments/add", { data });
};

/**
 * 更新评论
 * 后端路由: POST /api/v1/comments/update
 */
export const updateComment = (data: UpdateCommentParams) => {
  return http.request<ApiResponse>("post", "/api/v1/comments/update", { data });
};

/**
 * 删除评论（软删除）
 * 后端路由: POST /api/v1/comments/delete
 */
export const deleteComment = (id: number) => {
  return http.request<ApiResponse>("post", "/api/v1/comments/delete", { data: { id } });
};

/**
 * 批量删除评论
 * 后端路由: POST /api/v1/comments/batchDelete
 */
export const batchDeleteComments = (ids: number[]) => {
  return http.request<ApiResponse>("post", "/api/v1/comments/batchDelete", { data: { ids } });
};

/**
 * 审核评论（通过）
 * 后端路由: POST /api/v1/comments/approve
 */
export const approveComment = (id: number) => {
  return http.request<ApiResponse>("post", "/api/v1/comments/approve", { data: { id } });
};

/**
 * 拒绝评论
 * 后端路由: POST /api/v1/comments/reject
 */
export const rejectComment = (id: number) => {
  return http.request<ApiResponse>("post", "/api/v1/comments/reject", { data: { id } });
};

/**
 * 批量审核评论
 * 后端路由: POST /api/v1/comments/batchApprove
 */
export const batchApproveComments = (ids: number[]) => {
  return http.request<ApiResponse>("post", "/api/v1/comments/batchApprove", { data: { ids } });
};

/**
 * 获取评论详情
 * 后端路由: GET /api/v1/comments/detail
 */
export const getCommentDetail = (id: number) => {
  return http.request<CommentDetailResponse>("get", "/api/v1/comments/detail", { params: { id } });
};

/**
 * 获取评论统计
 * 后端路由: GET /api/v1/comments/stats
 */
export const getCommentsStats = () => {
  return http.request<ApiResponse>("get", "/api/v1/comments/stats");
};

// 评论状态映射
export const CommentStatusMap: Record<number, string> = {
  0: "待审核",
  1: "已通过",
  2: "已拒绝"
};

// 评论状态类型映射（用于Element Plus标签类型）
export const CommentStatusTypeMap: Record<number, string> = {
  0: "warning",
  1: "success",
  2: "danger"
};

