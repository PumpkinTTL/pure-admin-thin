/**
 * 邮件模板处理工具
 */

/**
 * 模板变量接口
 */
export interface TemplateVariables {
  // 用户相关变量
  userName?: string;
  nickname?: string;
  email?: string;
  user_id?: number | string;

  // 时间相关变量
  date?: string;
  datetime?: string;
  time?: string;
  year?: string;

  // 系统相关变量
  site_name?: string;
  companyName?: string;
  company_name?: string;

  // 节日祝福模板变量
  festivalName?: string;
  greeting?: string;
  signature?: string;

  // 密码重置模板变量
  code?: string;
  link?: string;
  reset_url?: string;

  // 其他自定义变量
  [key: string]: any;
}

/**
 * 默认模板变量
 */
export const DEFAULT_TEMPLATE_VARIABLES: TemplateVariables = {
  // 系统信息
  site_name: "苍穹云网络",
  companyName: "苍穹云网络",
  company_name: "苍穹云网络",

  // 当前时间
  date: new Date().toISOString().split("T")[0], // YYYY-MM-DD
  datetime: new Date()
    .toLocaleString("zh-CN", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: false
    })
    .replace(/\//g, "-"), // YYYY-MM-DD HH:mm:ss
  time: new Date().toLocaleTimeString("zh-CN", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false
  }), // HH:mm:ss
  year: new Date().getFullYear().toString(),

  // 默认节日祝福变量
  festivalName: "春节",
  greeting: "新年快乐，阖家幸福！",
  signature: "苍穹云网络 团队",

  // 默认密码重置变量
  code: "123456",
  link: "https://example.com/verify",
  reset_url: "https://example.com/reset-password"
};

/**
 * 从 HTML 内容中提取所有变量占位符
 * @param content HTML 内容
 * @returns 提取的变量名数组
 */
export function extractTemplateVariables(content: string): string[] {
  if (!content) return [];

  // 匹配 {变量名} 格式
  const regex = /\{([a-zA-Z0-9_]+)\}/g;
  const matches = content.matchAll(regex);
  const variables = new Set<string>();

  for (const match of matches) {
    variables.add(match[1]);
  }

  return Array.from(variables);
}

/**
 * 渲染模板内容，替换变量占位符
 * @param content 原始模板内容
 * @param variables 变量对象
 * @param defaultVariables 默认变量（当 variables 中的值不存在时使用）
 * @returns 渲染后的内容
 */
export function renderTemplate(
  content: string,
  variables: TemplateVariables = {},
  defaultVariables: TemplateVariables = DEFAULT_TEMPLATE_VARIABLES
): string {
  if (!content) return "";

  // 合并变量，传入的 variables 优先级更高
  const allVariables = { ...defaultVariables, ...variables };

  let renderedContent = content;

  // 替换所有变量占位符
  Object.keys(allVariables).forEach(key => {
    const placeholder = `{${key}}`;
    const value = allVariables[key];

    if (value !== undefined && value !== null) {
      renderedContent = renderedContent.replace(
        new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), "g"),
        String(value)
      );
    }
  });

  return renderedContent;
}

/**
 * 获取用户相关的模板变量
 * @param user 用户对象
 * @returns 用户变量对象
 */
export function getUserTemplateVariables(user: any): TemplateVariables {
  return {
    userName: user?.username || "用户名",
    nickname: user?.nickname || user?.username || "昵称",
    email: user?.email || "user@example.com",
    user_id: user?.id || ""
  };
}

/**
 * 获取节日祝福模板的完整变量
 * @param user 用户对象（可选）
 * @param festivalName 节日名称（可选）
 * @param greeting 祝福语（可选）
 * @returns 节日祝福模板变量
 */
export function getFestivalTemplateVariables(
  user?: any,
  festivalName?: string,
  greeting?: string
): TemplateVariables {
  const userVars = user ? getUserTemplateVariables(user) : {};

  return {
    ...userVars,
    festivalName: festivalName || "春节",
    greeting: greeting || "新年快乐，阖家幸福！",
    signature: "苍穹云网络 团队"
  };
}

/**
 * 获取密码重置模板的完整变量
 * @param user 用户对象
 * @param resetCode 重置码
 * @param resetUrl 重置链接
 * @returns 密码重置模板变量
 */
export function getPasswordResetTemplateVariables(
  user: any,
  resetCode?: string,
  resetUrl?: string
): TemplateVariables {
  const userVars = getUserTemplateVariables(user);

  return {
    ...userVars,
    code: resetCode || "123456",
    link: resetUrl || "https://example.com/verify",
    reset_url: resetUrl || "https://example.com/reset-password"
  };
}

/**
 * 检查模板内容中是否还有未替换的变量
 * @param content 渲染后的内容
 * @param originalContent 原始内容
 * @returns 检查结果
 */
export function checkUnreplacedVariables(
  content: string,
  originalContent: string
): {
  hasMissing: boolean;
  missingVars: Array<{ placeholder: string; variable: string }>;
  replacedVars: Array<{ placeholder: string; variable: string; value: string }>;
} {
  const originalVars = extractTemplateVariables(originalContent);
  const missingVars: Array<{ placeholder: string; variable: string }> = [];
  const replacedVars: Array<{
    placeholder: string;
    variable: string;
    value: string;
  }> = [];

  originalVars.forEach(variable => {
    const placeholder = `{${variable}}`;
    if (content.includes(placeholder)) {
      missingVars.push({ placeholder, variable });
    } else {
      // 尝试从内容中提取替换后的值（这个可能不准确，仅供参考）
      replacedVars.push({ placeholder, variable, value: "[已替换]" });
    }
  });

  return {
    hasMissing: missingVars.length > 0,
    missingVars,
    replacedVars
  };
}

/**
 * 根据模板类型获取预设变量
 * @param templateCode 模板代码
 * @param user 用户对象（可选）
 * @returns 预设变量对象
 */
export function getPresetVariablesByTemplateType(
  templateCode: string,
  user?: any
): TemplateVariables {
  const baseVars = user ? getUserTemplateVariables(user) : {};

  switch (templateCode) {
    case "festival_greeting":
    case "holiday_greeting":
      return getFestivalTemplateVariables(user);

    case "password_reset":
    case "reset_password":
      return getPasswordResetTemplateVariables(user || {});

    default:
      return baseVars;
  }
}
