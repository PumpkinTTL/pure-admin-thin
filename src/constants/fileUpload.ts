/**
 * 文件上传配置常量
 */

/**
 * 允许上传的文件类型
 */
export const ALLOWED_FILE_TYPES = {
  image: ["jpg", "jpeg", "png", "gif", "bmp", "webp", "svg"],
  video: ["mp4", "avi", "mov", "wmv", "flv", "mkv", "webm"],
  document: [
    "pdf",
    "doc",
    "docx",
    "xls",
    "xlsx",
    "ppt",
    "pptx",
    "txt",
    "csv",
    "md"
  ],
  audio: ["mp3", "wav", "ogg", "aac", "flac", "m4a"],
  archive: ["zip", "rar", "7z", "tar", "gz"]
} as const;

/**
 * 禁止上传的危险文件扩展名（可执行脚本和系统文件）
 */
export const FORBIDDEN_EXTENSIONS = [
  // 可执行文件
  "exe",
  "bat",
  "cmd",
  "com",
  "pif",
  "scr",
  "msi",
  "app",
  "deb",
  "rpm",
  // PHP 脚本
  "php",
  "php3",
  "php4",
  "php5",
  "phtml",
  "phar",
  // JSP 脚本
  "jsp",
  "jspx",
  "jsw",
  "jsv",
  "jspf",
  // ASP 脚本
  "asp",
  "aspx",
  "asa",
  "asax",
  "ascx",
  "ashx",
  "asmx",
  "cer",
  // Python 脚本
  "py",
  "pyc",
  "pyo",
  "pyw",
  "pyz",
  // Ruby 脚本
  "rb",
  "rbw",
  // Perl 脚本
  "pl",
  "pm",
  "cgi",
  // Shell 脚本
  "sh",
  "bash",
  "zsh",
  "fish",
  // PowerShell 脚本
  "ps1",
  "psm1",
  "psd1",
  "ps1xml",
  "pssc",
  "psrc",
  "cdxml",
  // VBScript
  "vbs",
  "vbe",
  "vba",
  "vbscript",
  // JavaScript (服务器端)
  "js",
  "jse",
  "ws",
  "wsf",
  "wsc",
  "wsh",
  // Java
  "jar",
  "war",
  "ear",
  "java",
  "class",
  // 系统文件
  "dll",
  "sys",
  "drv",
  "ocx",
  // 配置文件
  "htaccess",
  "htpasswd",
  "ini",
  "config",
  "conf"
] as const;

/**
 * 文件大小限制（字节）
 */
export const FILE_SIZE_LIMITS = {
  max: 8 * 1024 * 1024, // 8MB
  maxMB: 8
} as const;

/**
 * 最大上传文件数量
 */
export const MAX_UPLOAD_COUNT = 8;

/**
 * 获取所有允许的文件扩展名
 */
export function getAllowedExtensions(): string[] {
  return Object.values(ALLOWED_FILE_TYPES).flat();
}

/**
 * 检查文件扩展名是否被禁止
 */
export function isForbiddenExtension(extension: string): boolean {
  const ext = extension.toLowerCase().replace(/^\./, "");
  return FORBIDDEN_EXTENSIONS.includes(ext as any);
}

/**
 * 检查文件扩展名是否允许
 */
export function isAllowedExtension(extension: string): boolean {
  const ext = extension.toLowerCase().replace(/^\./, "");

  // 首先检查是否在禁止列表中
  if (isForbiddenExtension(ext)) {
    return false;
  }

  // 然后检查是否在允许列表中
  const allowedExtensions = getAllowedExtensions();
  return allowedExtensions.includes(ext);
}

/**
 * 从文件名获取扩展名
 */
export function getFileExtension(filename: string): string {
  const parts = filename.split(".");
  return parts.length > 1 ? parts[parts.length - 1].toLowerCase() : "";
}

/**
 * 验证文件
 */
export function validateFile(file: File): {
  valid: boolean;
  message: string;
} {
  // 检查文件大小
  if (file.size > FILE_SIZE_LIMITS.max) {
    return {
      valid: false,
      message: `文件 ${file.name} 超过 ${FILE_SIZE_LIMITS.maxMB}MB 限制`
    };
  }

  // 获取文件扩展名
  const extension = getFileExtension(file.name);

  if (!extension) {
    return {
      valid: false,
      message: `文件 ${file.name} 没有扩展名`
    };
  }

  // 检查是否是危险文件类型
  if (isForbiddenExtension(extension)) {
    return {
      valid: false,
      message: `禁止上传 .${extension} 类型的文件（安全限制）`
    };
  }

  // 检查是否是允许的文件类型
  if (!isAllowedExtension(extension)) {
    return {
      valid: false,
      message: `不支持上传 .${extension} 类型的文件`
    };
  }

  return {
    valid: true,
    message: "验证通过"
  };
}

/**
 * 批量验证文件
 */
export function validateFiles(files: File[]): {
  validFiles: File[];
  invalidFiles: Array<{ file: File; message: string }>;
} {
  const validFiles: File[] = [];
  const invalidFiles: Array<{ file: File; message: string }> = [];

  files.forEach(file => {
    const result = validateFile(file);
    if (result.valid) {
      validFiles.push(file);
    } else {
      invalidFiles.push({ file, message: result.message });
    }
  });

  return { validFiles, invalidFiles };
}

/**
 * 格式化文件大小
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return "0 B";

  const k = 1024;
  const sizes = ["B", "KB", "MB", "GB", "TB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}
