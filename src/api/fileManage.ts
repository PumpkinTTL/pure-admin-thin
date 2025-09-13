import { http } from "@/utils/http";
import { baseUrlApi } from "@/api/utils";

// ==================== 类型定义 ====================

// 文件信息类型
export type FileInfo = {
  file_id: number;
  original_name: string;
  store_name: string;
  file_path: string;
  file_size: number;
  file_size_format: string;
  file_type: string;
  file_extension: string;
  file_hash?: string;
  hash_algorithm?: string;
  storage_type: number;
  storage_type_text: string;
  bucket_name?: string;
  http_url: string;
  device_fingerprint?: string;
  remark?: string;
  create_time: string;
  update_time?: string;
  user?: {
    id: number;
    username: string;
    avatar?: string;
  };
  is_duplicate?: boolean;
};

// 上传响应类型
export type UploadResponse = {
  file_id: number;
  original_name: string;
  save_path: string;
  file_type: string;
  mime_type: string;
  size: number;
  url: string;
  is_duplicate: boolean;
};

// 文件统计信息类型 - 根据新API文档
export type FileStats = {
  total_count: number;
  active_count: number;
  deleted_count: number;
  total_size: number;
  total_size_format: string;
  storage_type_stats: Array<{
    type: number;
    type_name: string;
    count: number;
  }>;
  file_type_stats: Array<{
    file_extension: string;
    count: number;
  }>;
};

// 批量删除URL响应类型
export type BatchDeleteUrlResponse = {
  total: number;
  success_count: number;
  failed_count: number;
  details: Array<{
    url: string;
    status: string;
    message: string;
  }>;
};

// 批量删除ID响应类型
export type BatchDeleteIdResponse = {
  fail_ids: number[];
};

// 文件列表查询参数 - 根据新API文档
export type FileListParams = {
  page?: number;
  page_size?: number;
  status?: 'active' | 'deleted';
  original_name?: string;
  file_type?: string;
  file_extension?: string;
  user_id?: number;
  storage_type?: number;
  min_size?: number;
  max_size?: number;
  start_date?: string;
  end_date?: string;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
};

// ==================== 上传相关接口 ====================

// 1. 文件上传
export const uploadFile = (params: FormData) => {
  return http.request<{
    code: number;
    msg: string;
    data: UploadResponse[];
  }>("post", `${baseUrlApi}/upload/uploadFile`, {
    data: params,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });
};

// 2. 批量删除文件（通过URL）
export const deleteFilesByUrls = (urls: string[]) => {
  return http.request<{
    code: number;
    msg: string;
    data: BatchDeleteUrlResponse;
  }>("post", `${baseUrlApi}/upload/deleteFiles`, {
    data: { urls }
  });
};

// ==================== 文件管理接口 ====================

// 3. 获取文件列表
export const getFileList = (params: FileListParams) => {
  return http.request<{
    code: number;
    data: {
      total: number;
      per_page: number;
      current_page: number;
      data: FileInfo[];
    };
  }>("get", `${baseUrlApi}/file/list`, { params });
};

// 4. 获取文件详情
export const getFileDetail = (fileId: number) => {
  return http.request<{
    code: number;
    message: string;
    data: FileInfo;
  }>("get", `${baseUrlApi}/file/detail`, {
    params: { file_id: fileId }
  });
};

// 5. 软删除文件
export const deleteFile = (fileId: number) => {
  return http.request<{
    code: number;
    message: string;
  }>("post", `${baseUrlApi}/file/delete`, {
    data: { file_id: fileId }
  });
};

// 6. 恢复已删除文件
export const restoreFile = (fileId: number) => {
  return http.request<{
    code: number;
    message: string;
  }>("post", `${baseUrlApi}/file/restore`, {
    data: { file_id: fileId }
  });
};

// 7. 永久删除文件
export const forceDeleteFile = (fileId: number) => {
  return http.request<{
    code: number;
    message: string;
  }>("post", `${baseUrlApi}/file/forceDelete`, {
    data: { file_id: fileId }
  });
};

// 8. 批量删除文件（通过ID）
export const batchDeleteFiles = (fileIds: number[], isForce = false) => {
  const requestData = {
    file_ids: fileIds,
    is_force: isForce
  };

  console.log('API批量删除请求参数:', requestData);
  return http.request<{
    code: number;
    message: string;
    data: BatchDeleteIdResponse;
  }>("post", `${baseUrlApi}/file/batchDelete`, {
    data: requestData
  });
};

// 9. 获取文件统计信息
export const getFileStats = () => {
  return http.request<{
    code: number;
    message: string;
    data: FileStats;
  }>("get", `${baseUrlApi}/file/stats`);
};