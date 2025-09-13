import { http } from "@/utils/http";

/**
 * 权限列表参数
 */
export interface PermissionListParams {
  id?: number;
  name?: string;
  description?: string;
  iden?: string;
  tree?: boolean;
  delete_status?: 'only_deleted' | 'with_deleted';
  page?: number;
  limit?: number;
}

/**
 * 权限信息
 */
export interface PermissionInfo {
  id: number;
  name: string;
  iden: string;
  description?: string;
  create_time?: string;
  update_time?: string;
  delete_time?: string;
  parent_id?: number;
  children?: PermissionInfo[];
}

/**
 * 获取权限列表
 */
export function getPermissionList(params: Partial<PermissionListParams>) {
  return http.request<any>("get", "/api/v1/permissions", { params });
}

/**
 * 获取权限树
 */
export function getPermissionTree() {
  return http.request<any>("get", "/api/v1/permissions/tree");
}

/**
 * 获取完整权限树
 */
export function getPermissionFullTree() {
  return http.request<any>("get", "/api/v1/permissions/fullTree");
}

/**
 * 添加权限
 */
export function addPermission(data: {
  name: string;
  iden: string;
  description?: string;
  real?: boolean;
}) {
  return http.request<any>("post", "/api/v1/permissions/add", { data });
}

/**
 * 更新权限
 */
export function updatePermission(data: {
  id: number;
  name?: string;
  iden?: string;
  description?: string;
  real?: boolean;
}) {
  return http.request<any>("put", "/api/v1/permissions/update", { data });
}

/**
 * 删除权限
 */
export function deletePermission(id: number, real: boolean = false) {
  return http.request<any>("delete", "/api/v1/permissions/delete", {
    data: { id, real }
  });
}

/**
 * 恢复已删除的权限
 */
export function restorePermission(id: number) {
  return http.request<any>("post", "/api/v1/permissions/restore", {
    data: { id }
  });
}

/**
 * 获取父级权限列表
 */
export function getParentPermissions() {
  return http.request<any>("get", "/api/v1/permissions/parents");
}

/**
 * 获取子级权限列表
 */
export function getChildrenPermissions(parent_id: number) {
  return http.request<any>("get", "/api/v1/permissions/children", { params: { parent_id } });
}

/**
 * 获取权限分类
 */
export function getPermissionCategories() {
  return http.request<any>("get", "/api/v1/permissions/categories");
}

/**
 * 获取权限分配的API接口
 */
export function getPermissionApis(permissionId: number) {
  return http.request<any>("get", `/api/v1/permissions/getApis`, {
    params: { permission_id: permissionId }
  });
}

/**
 * 分配API接口给权限
 */
export function assignPermissionApis(permissionId: number, apiIds: number[]) {
  return http.request<any>("post", `/api/v1/permissions/assignApis`, {
    data: {
      permission_id: permissionId,
      api_ids: apiIds
    }
  });
}

/**
 * 权限类型列表
 */
export const permissionTypes = [
  { label: "页面权限", value: "page" },
  { label: "按钮权限", value: "button" },
  { label: "接口权限", value: "api" },
  { label: "数据权限", value: "data" }
]; 