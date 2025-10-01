import { http } from "@/utils/http";

// 定义API响应数据结构
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

// 定义主题配置数据结构
export interface ThemeConfig {
  id: number;
  theme_key: string;
  theme_name: string;
  description?: string;
  preview_image?: string;
  config_data: {
    colors: {
      primary: string;
      secondary: string;
      success: string;
      warning: string;
      danger: string;
      info: string;
    };
    layout: {
      sidebar_width: number;
      header_height: number;
      border_radius: number;
      content_padding: number;
    };
    typography: {
      font_family: string;
      font_size_base: number;
      line_height: number;
    };
    effects: {
      shadow_level: number;
      animation_duration: string;
      transition_timing: string;
    };
  };
  is_system: number;
  is_current: number;
  is_active: number;
  sort_order: number;
  create_time: string;
  update_time: string;
  status_text?: string;
  current_text?: string;
  type_text?: string;
}

// 定义主题列表请求参数
export interface ThemeListParams {
  page?: number;
  page_size?: number;
  keyword?: string;
  is_active?: number;
}

// 定义创建/更新主题参数
export interface ThemeCreateParams {
  theme_key: string;
  theme_name: string;
  description?: string;
  preview_image?: string;
  config_data: ThemeConfig['config_data'];
  is_system?: number;
  is_active?: number;
  sort_order?: number;
}

// ========== 客户端API ==========

/**
 * 获取当前主题配置（客户端调用）
 */
export const getCurrentTheme = () => {
  return http.request<ApiResponse<ThemeConfig>>("get", "/api/v1/theme/current");
};

/**
 * 获取主题列表（客户端调用）
 */
export const getThemeList = (params?: ThemeListParams) => {
  return http.request<ApiResponse<{
    list: Array<ThemeConfig>;
    total: number;
    page: number;
    page_size: number;
  }>>("get", "/api/v1/theme/list", { params });
};

/**
 * 根据主题键获取主题详情（客户端调用）
 */
export const getThemeByKey = (themeKey: string) => {
  return http.request<ApiResponse<ThemeConfig>>("get", `/api/v1/theme/detail/${themeKey}`);
};

// ========== 管理端API ==========

/**
 * 创建主题配置（管理端）
 */
export const createTheme = (data: ThemeCreateParams) => {
  return http.request<ApiResponse<ThemeConfig>>("post", "/api/v1/theme/create", { data });
};

/**
 * 更新主题配置（管理端）
 */
export const updateTheme = (id: number, data: Partial<ThemeCreateParams>) => {
  return http.request<ApiResponse<ThemeConfig>>("put", `/api/v1/theme/update/${id}`, { data });
};

/**
 * 删除主题配置（管理端）
 */
export const deleteTheme = (id: number) => {
  return http.request<ApiResponse<null>>("delete", `/api/v1/theme/delete/${id}`);
};

/**
 * 设置当前主题（管理端）
 */
export const setCurrentTheme = (themeId: number) => {
  return http.request<ApiResponse<null>>("post", "/api/v1/theme/set-current", {
    data: { theme_id: themeId }
  });
};

/**
 * 切换主题状态（管理端）
 */
export const toggleThemeStatus = (id: number) => {
  return http.request<ApiResponse<null>>("post", `/api/v1/theme/toggle-status/${id}`);
};

// ========== 工具函数 ==========

/**
 * 验证主题配置数据格式
 */
export const validateThemeConfig = (configData: any): boolean => {
  try {
    // 检查必需的配置项
    const requiredKeys = ['colors', 'layout', 'typography', 'effects'];
    for (const key of requiredKeys) {
      if (!configData[key]) {
        return false;
      }
    }

    // 检查颜色配置
    const requiredColors = ['primary', 'secondary', 'success', 'warning', 'danger', 'info'];
    for (const color of requiredColors) {
      if (!configData.colors[color]) {
        return false;
      }
    }

    // 检查布局配置
    const requiredLayout = ['sidebar_width', 'header_height', 'border_radius', 'content_padding'];
    for (const layout of requiredLayout) {
      if (configData.layout[layout] === undefined) {
        return false;
      }
    }

    // 检查字体配置
    const requiredTypography = ['font_family', 'font_size_base', 'line_height'];
    for (const typography of requiredTypography) {
      if (configData.typography[typography] === undefined) {
        return false;
      }
    }

    // 检查特效配置
    const requiredEffects = ['shadow_level', 'animation_duration', 'transition_timing'];
    for (const effect of requiredEffects) {
      if (configData.effects[effect] === undefined) {
        return false;
      }
    }

    return true;
  } catch (error) {
    return false;
  }
};

/**
 * 生成默认主题配置
 */
export const getDefaultThemeConfig = () => {
  return {
    colors: {
      primary: "#409EFF",
      secondary: "#909399",
      success: "#67C23A",
      warning: "#E6A23C",
      danger: "#F56C6C",
      info: "#909399"
    },
    layout: {
      sidebar_width: 240,
      header_height: 60,
      border_radius: 4,
      content_padding: 20
    },
    typography: {
      font_family: "PingFang SC, Microsoft YaHei",
      font_size_base: 14,
      line_height: 1.5
    },
    effects: {
      shadow_level: 2,
      animation_duration: "0.3s",
      transition_timing: "ease-in-out"
    }
  };
};

/**
 * 应用主题配置到页面
 */
export const applyThemeConfig = (themeConfig: ThemeConfig) => {
  try {
    const { colors, layout, typography, effects } = themeConfig.config_data;

    // 应用颜色配置
    const root = document.documentElement;
    root.style.setProperty('--el-color-primary', colors.primary);
    root.style.setProperty('--el-color-success', colors.success);
    root.style.setProperty('--el-color-warning', colors.warning);
    root.style.setProperty('--el-color-danger', colors.danger);
    root.style.setProperty('--el-color-info', colors.info);

    // 应用布局配置
    root.style.setProperty('--sidebar-width', `${layout.sidebar_width}px`);
    root.style.setProperty('--header-height', `${layout.header_height}px`);
    root.style.setProperty('--border-radius', `${layout.border_radius}px`);
    root.style.setProperty('--content-padding', `${layout.content_padding}px`);

    // 应用字体配置
    root.style.setProperty('--font-family', typography.font_family);
    root.style.setProperty('--font-size-base', `${typography.font_size_base}px`);
    root.style.setProperty('--line-height', typography.line_height.toString());

    // 应用特效配置
    root.style.setProperty('--animation-duration', effects.animation_duration);
    root.style.setProperty('--transition-timing', effects.transition_timing);

    return true;
  } catch (error) {
    console.error('应用主题配置失败:', error);
    return false;
  }
};

/**
 * 预览主题配置（不实际应用）
 */
export const previewThemeConfig = (themeConfig: ThemeConfig, previewElement?: HTMLElement) => {
  try {
    const element = previewElement || document.body;
    const { colors, layout, typography, effects } = themeConfig.config_data;

    // 创建预览样式
    const previewStyle = `
      --preview-color-primary: ${colors.primary};
      --preview-color-success: ${colors.success};
      --preview-color-warning: ${colors.warning};
      --preview-color-danger: ${colors.danger};
      --preview-color-info: ${colors.info};
      --preview-sidebar-width: ${layout.sidebar_width}px;
      --preview-header-height: ${layout.header_height}px;
      --preview-border-radius: ${layout.border_radius}px;
      --preview-content-padding: ${layout.content_padding}px;
      --preview-font-family: ${typography.font_family};
      --preview-font-size-base: ${typography.font_size_base}px;
      --preview-line-height: ${typography.line_height};
      --preview-animation-duration: ${effects.animation_duration};
      --preview-transition-timing: ${effects.transition_timing};
    `;

    element.style.cssText += previewStyle;
    return true;
  } catch (error) {
    console.error('预览主题配置失败:', error);
    return false;
  }
};
