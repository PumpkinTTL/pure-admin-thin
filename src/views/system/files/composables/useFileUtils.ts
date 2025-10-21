import { formatFileSize, storageTypeTextMap } from "@/api/utils";
import type { FileInfo } from "@/api/fileManage";

/**
 * 文件工具函数composable
 */
export function useFileUtils() {
  // 文件类型映射
  const fileTypeMap = {
    image: {
      name: "图片",
      icon: "fa-image",
      class: "image-file"
    },
    video: {
      name: "视频",
      icon: "fa-video",
      class: "video-file"
    },
    audio: {
      name: "音频",
      icon: "fa-headphones",
      class: "audio-file"
    },
    document: {
      name: "文档",
      icon: "fa-file-alt",
      class: "document-file"
    },
    archive: {
      name: "压缩包",
      icon: "fa-file-archive",
      class: "archive-file"
    },
    other: {
      name: "其他",
      icon: "fa-file",
      class: "other-file"
    }
  };

  // 存储类型映射
  const storageTypeMap = {
    0: {
      icon: "fa-server",
      class: "storage-local"
    },
    1: {
      icon: "fa-cloud",
      class: "storage-aliyun"
    },
    2: {
      icon: "fa-cloud-upload-alt",
      class: "storage-tencent"
    },
    3: {
      icon: "fa-cloud-download-alt",
      class: "storage-qiniu"
    }
  };

  // 哈希算法映射
  const hashAlgorithmMap = {
    MD5: "MD5",
    SHA1: "SHA1",
    SHA256: "SHA256",
    CRC32: "CRC32"
  };

  /**
   * 获取基本文件类型
   */
  const getBaseFileType = (fileType: string): string => {
    if (!fileType) return "other";

    if (fileType.includes("image/")) {
      return "image";
    } else if (fileType.includes("video/")) {
      return "video";
    } else if (fileType.includes("audio/")) {
      return "audio";
    } else if (
      fileType.includes("application/pdf") ||
      fileType.includes("text/") ||
      fileType.includes("application/msword") ||
      fileType.includes("application/vnd.openxmlformats-officedocument") ||
      fileType.includes("application/vnd.ms-")
    ) {
      return "document";
    } else if (
      fileType.includes("application/zip") ||
      fileType.includes("application/x-rar") ||
      fileType.includes("application/x-tar") ||
      fileType.includes("application/x-7z")
    ) {
      return "archive";
    }

    return "other";
  };

  /**
   * 获取文件类型名称
   */
  const getFileTypeName = (fileType: string): string => {
    const type = getBaseFileType(fileType);
    return fileTypeMap[type]?.name || "未知类型";
  };

  /**
   * 获取文件类型图标类名 (FontAwesome)
   */
  const getFontAwesomeIcon = (fileType: string): string => {
    const type = getBaseFileType(fileType);
    return `fa ${fileTypeMap[type]?.icon || "fa-file"}`;
  };

  /**
   * 获取文件类型CSS类
   */
  const getFileTypeClass = (fileType: string): string => {
    const type = getBaseFileType(fileType);
    return fileTypeMap[type]?.class || "other-file";
  };

  /**
   * 获取存储类型图标类名 (FontAwesome)
   */
  const getStorageIcon = (type: number): string => {
    return `fa ${storageTypeMap[type]?.icon || "fa-server"}`;
  };

  /**
   * 获取存储类型CSS类
   */
  const getStorageClass = (type: number): string => {
    return storageTypeMap[type]?.class || "storage-local";
  };

  /**
   * 获取存储类型名称
   */
  const getStorageTypeName = (type: number) => {
    return storageTypeTextMap[type] || "未知";
  };

  /**
   * 获取哈希算法名称
   */
  const getHashAlgorithmName = (algorithm: string): string => {
    return hashAlgorithmMap[algorithm] || algorithm || "HASH";
  };

  /**
   * 格式化日期
   */
  const formatDate = (dateStr: string): string => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    return date
      .toLocaleString("zh-CN", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
      })
      .replace(/\//g, "-");
  };

  /**
   * 判断是否为图片
   */
  const isImage = (extension: string) => {
    const imageExtensions = ["jpg", "jpeg", "png", "gif", "bmp", "webp"];
    return imageExtensions.includes(extension.toLowerCase());
  };

  /**
   * 判断是否为视频
   */
  const isVideo = (extension: string) => {
    const videoExtensions = ["mp4", "webm", "ogg", "mov", "avi"];
    return videoExtensions.includes(extension.toLowerCase());
  };

  /**
   * 判断是否为音频
   */
  const isAudio = (extension: string) => {
    const audioExtensions = ["mp3", "wav", "ogg", "flac"];
    return audioExtensions.includes(extension.toLowerCase());
  };

  /**
   * 判断是否为文本文件
   */
  const isText = (extension: string) => {
    if (!extension) return false;
    const textExtensions = [
      "txt", "log", "md", "json", "xml", "html", "htm", "css", "js", "ts",
      "vue", "jsx", "tsx", "php", "java", "py", "c", "cpp", "h", "go",
      "sql", "yaml", "yml", "ini", "conf", "sh", "bat", "csv"
    ];
    return textExtensions.includes(extension.toLowerCase());
  };

  /**
   * 检查是否可预览
   */
  const canPreview = (file: FileInfo) => {
    return (
      isImage(file.file_extension) ||
      isVideo(file.file_extension) ||
      isAudio(file.file_extension) ||
      isText(file.file_extension)
    );
  };

  /**
   * 获取文件缩略图
   */
  const getFileThumbnail = (file: FileInfo) => {
    // 如果有缩略图URL，返回缩略图
    if (file.http_url && isImage(file.file_extension)) {
      return file.http_url;
    }
    // 默认返回文件类型图标
    return "";
  };

  return {
    formatFileSize,
    getFileTypeName,
    getFontAwesomeIcon,
    getFileTypeClass,
    getStorageIcon,
    getStorageClass,
    getStorageTypeName,
    getHashAlgorithmName,
    formatDate,
    isImage,
    isVideo,
    isAudio,
    isText,
    canPreview,
    getFileThumbnail
  };
}
