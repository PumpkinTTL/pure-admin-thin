import { http } from "@/utils/http";

// 上传结果接口定义
export interface UploadResult {
  code: number;
  msg: string;
  data: Array<{
    file_id: number;
    original_name: string;
    save_path: string;
    file_type: string;
    mime_type: string;
    size: number;
    url: string;
    is_duplicate: boolean;
  }>;
}

// 删除文件接口定义
export interface DeleteFilesResult {
  code: number;
  msg: string;
  data: {
    success_count: number;
    failed_count: number;
    failed_urls: string[];
  };
}

// 上传选项接口
export interface UploadOptions {
  userId?: number;
  storageType?: number; // 0-本地 1-阿里云OSS 2-七牛云 3-腾讯云COS
  bucketName?: string;
  deviceFingerprint?: string;
  remark?: string; // 文件备注信息
}

/**
 * 单文件上传
 * @param file 要上传的文件
 * @param options 上传选项
 * @returns 上传结果
 */
export const uploadSingleFile = (file: File, options: UploadOptions = {}) => {
  const formData = new FormData();
  formData.append('file', file);

  // 添加可选参数
  if (options.userId) formData.append('user_id', options.userId.toString());
  if (options.storageType !== undefined) formData.append('storage_type', options.storageType.toString());
  if (options.bucketName) formData.append('bucket_name', options.bucketName);
  if (options.deviceFingerprint) formData.append('device_fingerprint', options.deviceFingerprint);
  if (options.remark) formData.append('remark', options.remark);

  return http.request<UploadResult>("post", "/api/v1/upload/uploadFile", {
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });
};

/**
 * 多文件上传
 * @param files 要上传的文件数组
 * @param options 上传选项
 * @returns 上传结果
 */
export const uploadMultipleFiles = (files: File[], options: UploadOptions = {}) => {
  const formData = new FormData();

  // 添加多个文件
  files.forEach(file => {
    formData.append('files[]', file);
  });

  // 添加可选参数
  if (options.userId) formData.append('user_id', options.userId.toString());
  if (options.storageType !== undefined) formData.append('storage_type', options.storageType.toString());
  if (options.bucketName) formData.append('bucket_name', options.bucketName);
  if (options.deviceFingerprint) formData.append('device_fingerprint', options.deviceFingerprint);
  if (options.remark) formData.append('remark', options.remark);

  return http.request<UploadResult>("post", "/api/v1/upload/uploadFile", {
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });
};

/**
 * 图片上传（专门用于富文本编辑器）
 * @param file 图片文件
 * @param options 上传选项
 * @returns 上传结果
 */
export const uploadImage = (file: File, options: UploadOptions = {}) => {
  return uploadSingleFile(file, options);
};

/**
 * 兼容旧版本的上传接口
 * @param files 要上传的文件
 * @param fileName 上传字段名称，默认为'file'
 * @returns 上传结果
 */
export const uploadFile = (files: File | FormData, fileName: string = 'file') => {
  // 如果传入的已经是FormData，直接使用
  if (files instanceof FormData) {
    return http.request<UploadResult>("post", "/api/v1/upload/uploadFile", {
      data: files,
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
  }

  // 否则创建新的FormData并添加文件
  const formData = new FormData();
  formData.append(fileName, files);

  return http.request<UploadResult>("post", "/api/v1/upload/uploadFile", {
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });
};

/**
 * 删除文件
 * @param urls 要删除的文件URL数组
 * @returns 删除结果
 */
export const deleteFiles = (urls: string[]) => {
  return http.request<DeleteFilesResult>("post", "/api/v1/upload/deleteFiles", {
    data: { urls }
  });
};

/**
 * 从富文本内容中提取图片链接
 * @param content 富文本内容（HTML或Markdown）
 * @returns 图片链接数组
 */
export const extractImageUrls = (content: string): string[] => {
  if (!content || typeof content !== 'string') {
    return [];
  }

  const imageUrls = new Set<string>(); // 使用Set去重

  // 匹配HTML img标签中的src
  const htmlImgRegex = /<img[^>]+src\s*=\s*["']([^"']+)["'][^>]*>/gi;
  let htmlMatch: RegExpExecArray | null;
  while ((htmlMatch = htmlImgRegex.exec(content)) !== null) {
    imageUrls.add(htmlMatch[1]);
  }

  // 匹配Markdown图片语法 ![alt](url)
  const markdownImgRegex = /!\[([^\]]*)\]\(([^)]+)\)/g;
  let markdownMatch: RegExpExecArray | null;
  while ((markdownMatch = markdownImgRegex.exec(content)) !== null) {
    imageUrls.add(markdownMatch[2]);
  }

  // 匹配直接的图片URL（以图片扩展名结尾的URL）
  const directUrlRegex = /https?:\/\/[^\s<>"]+\.(?:jpg|jpeg|png|gif|bmp|webp)(?:\?[^\s<>"]*)?/gi;
  let urlMatch: RegExpExecArray | null;
  while ((urlMatch = directUrlRegex.exec(content)) !== null) {
    imageUrls.add(urlMatch[0]);
  }

  return Array.from(imageUrls);
};

/**
 * 从文章内容中提取图片并删除
 * @param content 文章内容
 * @returns 删除结果
 */
export const extractAndDeleteImages = async (content: string) => {
  const imageUrls = extractImageUrls(content);

  if (imageUrls.length === 0) {
    return {
      code: 200,
      msg: '没有找到图片',
      data: {
        success_count: 0,
        failed_count: 0,
        failed_urls: []
      }
    };
  }

  return await deleteFiles(imageUrls);
};

/**
 * 批量从文章内容中提取图片并删除
 * @param contents 文章内容数组
 * @returns 删除结果
 */
export const batchExtractAndDeleteImages = async (contents: string[]) => {
  const allImageUrls = new Set<string>();

  contents.forEach(content => {
    const imageUrls = extractImageUrls(content);
    imageUrls.forEach(url => allImageUrls.add(url));
  });

  const uniqueUrls = Array.from(allImageUrls);

  if (uniqueUrls.length === 0) {
    return {
      code: 200,
      msg: '没有找到图片',
      data: {
        success_count: 0,
        failed_count: 0,
        failed_urls: []
      }
    };
  }

  return await deleteFiles(uniqueUrls);
};