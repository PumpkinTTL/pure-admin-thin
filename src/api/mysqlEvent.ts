import { http } from "@/utils/http";

/**
 * MySQL事件调度器接口
 */

// API响应数据结构
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 事件日志信息接口
export interface MysqlEventLogInfo {
  id: number;
  message?: string;
  event_name?: string;
  status: 0 | 1; // 0失败，1成功
  execution_time: number;
  error_message?: string;
  affected_rows: number;
  execution_info?: string;
  create_time: string;
  server_id?: string;
}

// 事件日志统计信息接口
export interface MysqlEventLogStatistics {
  total_count: number;
  success_count: number;
  error_count: number;
  avg_execution_time: number;
  max_execution_time: number;
  min_execution_time: number;
  last_24h_count: number;
}

// 事件日志查询参数
export interface MysqlEventLogParams {
  event_name?: string;
  status?: 0 | 1;
  page?: number;
  limit?: number;
}

// 事件信息接口
export interface MysqlEventInfo {
  // 原始字段
  Db?: string;
  Name?: string;
  Definer?: string;
  "Time zone"?: string;
  Type?: string;
  "Execute at"?: string | null;
  "Interval value"?: string;
  "Interval field"?: string;
  Starts?: string;
  Ends?: string;
  Status?: string;
  Originator?: number;
  "character_set_client"?: string;
  "collation_connection"?: string;
  "Database Collation"?: string;
  Comment?: string;
  SQL?: string;

  // 兼容字段
  event_name?: string;
  event_schema?: string;
  definer?: string;
  event_body?: string;
  event_type?: string;
  execute_at?: string;
  interval_value?: string;
  interval_field?: string;
  sql_mode?: string;
  starts?: string;
  ends?: string;
  status?: string;
  on_completion?: string;
  created?: string;
  last_altered?: string;
  last_executed?: string;
  event_comment?: string;
  originator?: number;
  time_zone?: string;
  sql_statement?: string;
  schedule?: string;
  logs?: MysqlEventLogInfo[];

  // UI状态属性
  statusEnabled?: boolean;
  statusLoading?: boolean;
}

// 创建/修改事件参数
export interface MysqlEventParams {
  event_name: string;
  schedule?: string;
  sql_statement?: string;
  enable?: boolean;
  comment?: string;
  start_time?: string;
  end_time?: string;
  schema?: string;
}

// 添加新的接口类型
export interface MysqlEventSystemStatus {
  scheduler_status: string; // 'ON' 或 'OFF'
  is_enabled: boolean;
  has_privilege: boolean;
  events_supported: boolean;
  events_enabled: boolean;
  db_version: string;
}

export interface MysqlEventPrivileges {
  has_privilege: boolean;
  privileges: string[];
}

/**
 * 获取事件列表
 * @param schema 数据库名称，可选
 */
export const getMysqlEventList = (schema?: string) => {
  return http.request<ApiResponse<MysqlEventInfo[]>>("get", "/api/v1/mysqlEvent/list", {
    params: schema ? { schema } : {}
  });
};

/**
 * 获取事件详情
 * @param event_name 事件名称
 * @param schema 数据库名称，可选
 */
export const getMysqlEventDetail = (event_name: string, schema?: string) => {
  return http.request<ApiResponse<MysqlEventInfo>>("get", "/api/v1/mysqlEvent/detail", {
    params: {
      event_name,
      ...schema ? { schema } : {}
    }
  });
};

/**
 * 创建事件
 * @param params 事件参数
 */
export const createMysqlEvent = (params: MysqlEventParams) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/mysqlEvent/create", { data: params });
};

/**
 * 修改事件
 * @param params 事件参数
 */
export const alterMysqlEvent = (params: MysqlEventParams) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/mysqlEvent/alter", { data: params });
};

/**
 * 删除事件
 * @param event_name 事件名称
 * @param schema 数据库名称，可选
 */
export const dropMysqlEvent = (event_name: string, schema?: string) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/mysqlEvent/drop", {
    data: {
      event_name,
      ...schema ? { schema } : {}
    }
  });
};

/**
 * 启用/禁用事件调度器
 * @param enable 是否启用
 */
export const toggleMysqlEventScheduler = (enable: boolean = true) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/mysqlEvent/toggleScheduler", {
    data: { enable }
  });
};

/**
 * 获取事件调度器状态
 */
export const getMysqlEventSchedulerStatus = () => {
  return http.request<ApiResponse<{ enabled: boolean }>>("get", "/api/v1/mysqlEvent/schedulerStatus");
};

/**
 * 检查用户事件权限
 */
export const checkMysqlEventPrivileges = () => {
  return http.request<ApiResponse<MysqlEventPrivileges>>("get", "/api/v1/mysqlEvent/checkPrivileges");
};

/**
 * 获取事件系统状态
 */
export const getMysqlEventSystemStatus = () => {
  return http.request<ApiResponse<MysqlEventSystemStatus>>("get", "/api/v1/mysqlEvent/systemStatus");
};

/**
 * 获取事件日志列表
 * @param params 查询参数
 */
export const getMysqlEventLogList = (params?: MysqlEventLogParams) => {
  return http.request<ApiResponse<{
    list: MysqlEventLogInfo[];
    pagination: {
      total: number;
      current: number;
      page_size: number;
      pages: number;
    }
  }>>("get", "/api/v1/mysqlEventLog/list", { params });
};

/**
 * 清空事件日志
 * @param event_name 事件名称（可选）
 */
export const clearMysqlEventLogs = (event_name?: string) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/mysqlEventLog/clear", {
    data: event_name ? { event_name } : {}
  });
};

/**
 * 获取事件日志统计信息
 */
export const getMysqlEventLogStatistics = () => {
  return http.request<ApiResponse<MysqlEventLogStatistics>>("get", "/api/v1/mysqlEventLog/statistics");
};

/**
 * 启用/禁用事件
 * @param event_name 事件名称
 * @param enable 是否启用
 * @param schema 数据库名称（可选）
 */
export const toggleMysqlEvent = (event_name: string, enable: boolean = true, schema?: string) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/mysqlEvent/toggleEvent", {
    data: {
      event_name,
      enable,
      ...schema ? { schema } : {}
    }
  });
}; 