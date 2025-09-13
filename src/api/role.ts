import { http } from "@/utils/http";

// 定义API响应数据结构
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 定义角色数据结构
export interface RoleInfo {
  id: number;
  name: string;
  iden: string;
  description: string;
  create_time?: string;
  update_time?: string;
  delete_time?: string;
  status?: number;
  show_weight?: number;
  permissions?: Array<any>;
}

// 定义权限数据结构
export interface PermissionInfo {
  id: number;
  name: string;
  type: string;
  path?: string;
  parent_id?: number;
  sort?: number;
  icon?: string;
  component?: string;
  children?: PermissionInfo[];
}

// 定义角色列表请求参数
export interface RoleListParams {
  page?: number;
  page_size?: number;
  query_deleted?: 'only_deleted' | 'not_deleted' | 'all';
  id?: number;
  name?: string;
  iden?: string;
  description?: string;
  status?: number;
  order_field?: 'id' | 'name' | 'create_time' | 'update_time' | 'show_weight' | 'status';
  order_type?: 'asc' | 'desc';
}

// 获取所有的角色列表
export const getRoleList = (params?: RoleListParams) => {
  return http.request<ApiResponse<{
    list: Array<RoleInfo>;
    pagination: {
      total: number;
      current: number;
      page_size: number;
      pages: number;
    }
  }>>("get", "/api/v1/roles/selectRolesAll", { params });
};

// 获取角色详情
export const getRoleDetail = (id: number) => {
  return http.request<ApiResponse<RoleInfo>>("get", "/api/v1/roles/detail", { params: { id } });
};

// 创建角色
export const createRole = (data: {
  name: string;
  iden: string;
  description?: string;
  status?: number;
  show_weight?: number;
  permission_ids?: Array<number>;
}) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/roles/add", { data });
};

// 更新角色
export const updateRole = (data: {
  id: number;
  name?: string;
  iden?: string;
  description?: string;
  status?: number;
  show_weight?: number;
  permission_ids?: Array<number>;
}) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/roles/update", { data });
};

// 删除角色
export const deleteRole = (id: number, real?: boolean) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/roles/delete", {
    data: { id, real: real ? "true" : undefined }
  });
};

// 批量删除角色
export const batchDeleteRoles = (ids: number[], real?: boolean) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/roles/batchDelete", {
    data: { ids, real: real ? "true" : undefined }
  });
};

// 恢复已删除的角色
export const restoreRole = (id: number) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/roles/restore", { data: { id } });
};

// 获取用户的角色和权限
export const getUserRoles = (uid: number) => {
  return http.request<ApiResponse<any>>("get", "/api/v1/roles/selectRoleByUid", { params: { uid } });
};

// 获取所有权限列表
export const getAllPermissions = () => {
  return http.request<ApiResponse<PermissionInfo[]>>("get", "/api/v1/permissions/list");
};

// 分配角色权限
export const assignRolePermissions = (role_id: number, permission_ids: number[]) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/roles/assignPermissions", {
    data: { role_id, permission_ids }
  });
};

// 获取权限树结构
export const getPermissionTree = () => {
  return http.request<any>("get", "/api/v1/permissions/tree");
};
