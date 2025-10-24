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
    word: {
      name: "Word",
      icon: "fa-file-word",
      class: "word-file"
    },
    excel: {
      name: "Excel",
      icon: "fa-file-excel",
      class: "excel-file"
    },
    pdf: {
      name: "PDF",
      icon: "fa-file-pdf",
      class: "pdf-file"
    },
    ppt: {
      name: "PPT",
      icon: "fa-file-powerpoint",
      class: "ppt-file"
    },
    text: {
      name: "文本",
      icon: "fa-file-alt",
      class: "text-file"
    },
    code: {
      name: "代码",
      icon: "fa-file-code",
      class: "code-file"
    },
    archive: {
      name: "压缩包",
      icon: "fa-file-archive",
      class: "archive-file"
    },
    other: {
      name: "文件",
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

  /**
   * 根据文件扩展名获取文件类型
   */
  const getFileTypeByExtension = (extension: string): string => {
    if (!extension) return "other";
    const ext = extension.toLowerCase();

    // 图片
    if (
      ["jpg", "jpeg", "png", "gif", "bmp", "webp", "svg", "ico"].includes(ext)
    ) {
      return "image";
    }
    // 视频
    if (
      ["mp4", "webm", "ogg", "mov", "avi", "mkv", "flv", "wmv"].includes(ext)
    ) {
      return "video";
    }
    // 音频
    if (["mp3", "wav", "ogg", "flac", "aac", "m4a", "wma"].includes(ext)) {
      return "audio";
    }
    // Word
    if (["doc", "docx"].includes(ext)) {
      return "word";
    }
    // Excel
    if (["xls", "xlsx", "csv"].includes(ext)) {
      return "excel";
    }
    // PDF
    if (ext === "pdf") {
      return "pdf";
    }
    // PPT
    if (["ppt", "pptx"].includes(ext)) {
      return "ppt";
    }
    // 代码文件
    if (
      [
        "js",
        "ts",
        "jsx",
        "tsx",
        "vue",
        "html",
        "css",
        "scss",
        "less",
        "php",
        "java",
        "py",
        "c",
        "cpp",
        "h",
        "go",
        "rs",
        "rb",
        "sql"
      ].includes(ext)
    ) {
      return "code";
    }
    // 文本文件
    if (
      [
        "txt",
        "log",
        "md",
        "json",
        "xml",
        "yaml",
        "yml",
        "ini",
        "conf"
      ].includes(ext)
    ) {
      return "text";
    }
    // 压缩包
    if (["zip", "rar", "7z", "tar", "gz", "bz2"].includes(ext)) {
      return "archive";
    }

    return "other";
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
   * 获取文件类型(内部公用函数)
   */
  const getFileType = (extension: string, fileType?: string): string => {
    let type = extension ? getFileTypeByExtension(extension) : "other";
    if (type === "other" && fileType) {
      type = getBaseFileType(fileType);
    }
    return type;
  };

  /**
   * 获取文件类型名称
   */
  const getFileTypeName = (extension: string, fileType?: string): string => {
    const type = getFileType(extension, fileType);
    return fileTypeMap[type]?.name || "未知类型";
  };

  /**
   * 获取文件类型图标类名 (FontAwesome)
   */
  const getFontAwesomeIcon = (extension: string, fileType?: string): string => {
    const type = getFileType(extension, fileType);
    return `fa ${fileTypeMap[type]?.icon || "fa-file"}`;
  };

  /**
   * 获取文件类型 CSS类
   */
  const getFileTypeClass = (extension: string, fileType?: string): string => {
    const type = getFileType(extension, fileType);
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
    return algorithm || "HASH";
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
   * 判断文件类型
   */
  const isImage = (extension: string) =>
    getFileTypeByExtension(extension) === "image";
  const isVideo = (extension: string) =>
    getFileTypeByExtension(extension) === "video";
  const isAudio = (extension: string) =>
    getFileTypeByExtension(extension) === "audio";
  const isWord = (extension: string) =>
    getFileTypeByExtension(extension) === "word";
  const isExcel = (extension: string) =>
    getFileTypeByExtension(extension) === "excel";
  const isPdf = (extension: string) =>
    getFileTypeByExtension(extension) === "pdf";
  const isText = (extension: string) => {
    const type = getFileTypeByExtension(extension);
    return type === "text" || type === "code";
  };

  /**
   * 检查是否可预览
   */
  const canPreview = (file: FileInfo) => {
    return (
      isImage(file.file_extension) ||
      isVideo(file.file_extension) ||
      isAudio(file.file_extension) ||
      isText(file.file_extension) ||
      isWord(file.file_extension) ||
      isExcel(file.file_extension) ||
      isPdf(file.file_extension)
    );
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
    isWord,
    isExcel,
    isPdf,
    canPreview
  };
}
