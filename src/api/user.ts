import { http } from "@/utils/http";

// 定义API响应数据结构
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 定义用户列表响应数据结构
export interface UserListResponse {
  list: Array<UserInfo>;
  pagination: {
    total: number;
    current: number;
    page_size: number;
    pages: number;
  };
}

// 定义用户信息结构
export interface UserInfo {
  id: number;
  username: string;
  email: string;
  create_time: string;
  update_time: string;
  last_login: string;
  status: boolean;
  avatar: string;
  phone: string | null;
  ip_address: string | null;
  gender: number;
  nickname: string;
  signature: string;
  roles: Array<RoleInfo>;
  premium: PremiumInfo | null;
  delete_time?: string;
}

// 定义角色信息结构
export interface RoleInfo {
  id: number;
  name: string;
  description: string;
  iden: string;
  create_time: string | null;
  update_time: string | null;
  status: number | null;
  show_weight: number;
}

// 定义会员信息结构
export interface PremiumInfo {
  id: number;
  user_id: number;
  create_time: string;
  expiration_time: string;
  remark: string;
}

export type UserResult = {
  success: boolean;
  data: {
    /** 头像 */
    avatar: string;
    /** 用户名 */
    username: string;
    /** 昵称 */
    nickname: string;
    /** 当前登录用户的角色 */
    roles: Array<string>;
    /** 按钮级别权限 */
    permissions: Array<string>;
    /** 访问令牌 */
    token: string;
    /** token的过期时间 */
    expires: Date;
  };
};



/** 刷新token */
export const refreshTokenApi = (data?: object) => {
  return http.request("post", "/api/v1/auth/refresh", { data });
};

/** 用户登录 */
export const getLogin = async (data?: object) => {
  // loginR(data);
  const res = await http.request("post", "/api/v1/user/login", { data }) as any;
  if (res.code != 200) return { code: res.code, msg: res.msg, data: null };
  // 解构token信息
  const { token, expireTime } = res as any;
  const { avatar, username, nickname, roles } = res.data as any;
  const expires = new Date(expireTime * 1000); // 后端返回秒级时间戳，转换为毫秒
  // 解构角色信息
  const rolesArray = roles.map(item => item.iden);
  // 解构权限信息
  const permissions = roles.map(item =>
    item.permissions.map(pitem => pitem.name)
  );
  // 将权限信息扁平化
  const permissionsArr = [].concat.apply([], permissions);
  // 组装用户登录信息
  const userInfo = {
    avatar,
    username,
    nickname,
    roles: rolesArray,
    permissions: permissionsArr,
    token,
    expires
  };
  const jsonData = {
    data: userInfo,
    code: res.code,
    msg: res.msg
  };
  console.log("jsonData", jsonData);

  console.log("res", res);

  return jsonData;
};



// 后端登录
export const loginR = (data?: object) => {
  return http.request("post", "/api/v1/user/login", { data });
};

// 获取用户列表
export const getUserList = (data?: object) => {
  // 返回具体类型
  return http.request<ApiResponse<UserListResponse>>("get", "/api/v1/user/selectUserListWithRoles", { params: data });
};
// 修改用户信息
export const updateUserInfoR = (data?: object) => {
  return http.request<ApiResponse<any>>("put", "/api/v1/user/update", { data });
};

/** 删除用户 */
export const deleteUser = (data?: object) => {
  return http.request<ApiResponse<any>>("delete", "/api/v1/user/delete", { data });
};

/** 恢复已删除的用户 */
export const restoreUser = (data?: object) => {
  return http.request<ApiResponse<any>>("put", "/api/v1/user/restore", { data });
};

/** 创建用户 */
export const createUser = (data?: object) => {
  return http.request<ApiResponse<any>>("post", "/api/v1/user/add", { data });
};

