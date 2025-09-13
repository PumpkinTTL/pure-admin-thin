// API基础URL
export const baseUrlApi = "/api/v1";

// 格式化文件大小
export const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return "0 B";
  
  const k = 1024;
  const sizes = ["B", "KB", "MB", "GB", "TB", "PB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

// 获取文件类型图标
export const getFileTypeIcon = (fileType: string, extension: string): string => {
  // 根据文件类型或扩展名返回对应的图标
  const fileTypeMap = {
    "image": "el-icon-picture",
    "video": "el-icon-video-camera",
    "audio": "el-icon-headset",
    "document": "el-icon-document",
    "pdf": "el-icon-document-checked",
    "spreadsheet": "el-icon-s-grid",
    "presentation": "el-icon-s-opportunity",
    "archive": "el-icon-folder-opened",
    "code": "el-icon-s-opportunity",
    "other": "el-icon-document"
  };
  
  // 根据扩展名进一步细分文件类型
  const extensionMap = {
    // 图片
    "jpg": "el-icon-picture",
    "jpeg": "el-icon-picture",
    "png": "el-icon-picture",
    "gif": "el-icon-picture",
    "svg": "el-icon-picture",
    
    // 文档
    "doc": "el-icon-document",
    "docx": "el-icon-document",
    "pdf": "el-icon-document-checked",
    "txt": "el-icon-document",
    
    // 表格
    "xls": "el-icon-s-grid",
    "xlsx": "el-icon-s-grid",
    "csv": "el-icon-s-grid",
    
    // 演示文稿
    "ppt": "el-icon-s-opportunity",
    "pptx": "el-icon-s-opportunity",
    
    // 压缩文件
    "zip": "el-icon-folder-opened",
    "rar": "el-icon-folder-opened",
    "7z": "el-icon-folder-opened",
    "tar": "el-icon-folder-opened",
    "gz": "el-icon-folder-opened",
    
    // 代码
    "html": "el-icon-s-opportunity",
    "css": "el-icon-s-opportunity",
    "js": "el-icon-s-opportunity",
    "php": "el-icon-s-opportunity",
    "java": "el-icon-s-opportunity",
    "py": "el-icon-s-opportunity",
    
    // 视频
    "mp4": "el-icon-video-camera",
    "avi": "el-icon-video-camera",
    "mov": "el-icon-video-camera",
    "wmv": "el-icon-video-camera",
    
    // 音频
    "mp3": "el-icon-headset",
    "wav": "el-icon-headset",
    "ogg": "el-icon-headset"
  };
  
  return extensionMap[extension.toLowerCase()] || fileTypeMap[fileType.toLowerCase()] || fileTypeMap["other"];
};

// 存储类型文本映射
export const storageTypeTextMap = {
  0: "本地存储",
  1: "阿里云OSS",
  2: "七牛云",
  3: "腾讯云COS"
};

// 根据文件类型生成颜色
export const getFileTypeColor = (fileType: string): string => {
  const colorMap = {
    "image": "#67C23A",
    "video": "#E6A23C",
    "audio": "#F56C6C",
    "document": "#409EFF",
    "pdf": "#F56C6C",
    "spreadsheet": "#67C23A",
    "presentation": "#E6A23C",
    "archive": "#909399",
    "code": "#409EFF",
    "other": "#909399"
  };
  
  return colorMap[fileType.toLowerCase()] || colorMap["other"];
}; 