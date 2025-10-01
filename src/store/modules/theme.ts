import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { storageLocal } from "@pureadmin/utils";
import { getCurrentTheme, getThemeList, applyThemeConfig, getDefaultThemeConfig, type ThemeConfig } from "@/api/theme";
import { message } from "@/utils/message";

export const useThemeStore = defineStore("theme", () => {
  // 状态
  const currentTheme = ref<ThemeConfig | null>(null);
  const themeList = ref<ThemeConfig[]>([]);
  const loading = ref(false);
  const initialized = ref(false);

  // 计算属性
  const currentThemeConfig = computed(() => {
    return currentTheme.value?.config_data || getDefaultThemeConfig();
  });

  const isCurrentTheme = computed(() => (themeId: number) => {
    return currentTheme.value?.id === themeId;
  });

  const activeThemes = computed(() => {
    return themeList.value.filter(theme => theme.is_active === 1);
  });

  // 初始化主题
  const initTheme = async () => {
    if (initialized.value) return;

    try {
      loading.value = true;

      // 从本地存储获取缓存的主题配置
      const cachedTheme = storageLocal().getItem<ThemeConfig>("current-theme");
      if (cachedTheme) {
        currentTheme.value = cachedTheme;
        applyThemeConfig(cachedTheme);
      }

      // 从服务器获取最新的主题配置
      await fetchCurrentTheme();
      initialized.value = true;
    } catch (error) {
      console.error("初始化主题失败:", error);
      // 如果服务器获取失败，使用默认主题
      if (!currentTheme.value) {
        const defaultTheme: ThemeConfig = {
          id: 0,
          theme_key: "default",
          theme_name: "默认主题",
          description: "系统默认主题",
          config_data: getDefaultThemeConfig(),
          is_system: 1,
          is_current: 1,
          is_active: 1,
          sort_order: 0,
          create_time: "",
          update_time: ""
        };
        currentTheme.value = defaultTheme;
        applyThemeConfig(defaultTheme);
      }
    } finally {
      loading.value = false;
    }
  };

  // 获取当前主题
  const fetchCurrentTheme = async () => {
    try {
      const response = await getCurrentTheme();
      if (response.code === 200 && response.data) {
        currentTheme.value = response.data;

        // 缓存到本地存储
        storageLocal().setItem("current-theme", response.data);

        // 应用主题配置
        applyThemeConfig(response.data);

        return response.data;
      } else {
        throw new Error(response.msg || "获取当前主题失败");
      }
    } catch (error) {
      console.error("获取当前主题失败:", error);
      throw error;
    }
  };

  // 获取主题列表
  const fetchThemeList = async (params?: any) => {
    try {
      loading.value = true;
      const response = await getThemeList(params);
      if (response.code === 200) {
        themeList.value = response.data.list;
        return response.data;
      } else {
        throw new Error(response.msg || "获取主题列表失败");
      }
    } catch (error) {
      console.error("获取主题列表失败:", error);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // 切换主题
  const switchTheme = async (theme: ThemeConfig) => {
    const previousTheme = currentTheme.value;
    try {
      // 立即应用主题（乐观更新）
      currentTheme.value = theme;
      applyThemeConfig(theme);

      // 缓存到本地存储
      storageLocal().setItem("current-theme", theme);

      message(`已切换到主题：${theme.theme_name}`, { type: "success" });

      return true;
    } catch (error) {
      // 如果出错，恢复之前的主题
      if (previousTheme) {
        currentTheme.value = previousTheme;
        applyThemeConfig(previousTheme);
        storageLocal().setItem("current-theme", previousTheme);
      }

      console.error("切换主题失败:", error);
      message("切换主题失败", { type: "error" });
      throw error;
    }
  };

  // 预览主题（不实际切换）
  const previewTheme = (theme: ThemeConfig) => {
    try {
      // 创建预览样式
      const previewElement = document.createElement("div");
      previewElement.id = "theme-preview";
      previewElement.style.position = "fixed";
      previewElement.style.top = "0";
      previewElement.style.left = "0";
      previewElement.style.width = "100%";
      previewElement.style.height = "100%";
      previewElement.style.pointerEvents = "none";
      previewElement.style.zIndex = "9999";

      // 应用预览样式
      const { colors, layout, typography, effects } = theme.config_data;
      previewElement.style.cssText += `
        --preview-primary: ${colors.primary};
        --preview-success: ${colors.success};
        --preview-warning: ${colors.warning};
        --preview-danger: ${colors.danger};
        --preview-info: ${colors.info};
      `;

      document.body.appendChild(previewElement);

      // 3秒后移除预览
      setTimeout(() => {
        document.body.removeChild(previewElement);
      }, 3000);

      return true;
    } catch (error) {
      console.error("预览主题失败:", error);
      return false;
    }
  };

  // 重置主题
  const resetTheme = () => {
    try {
      const defaultTheme: ThemeConfig = {
        id: 0,
        theme_key: "default",
        theme_name: "默认主题",
        description: "系统默认主题",
        config_data: getDefaultThemeConfig(),
        is_system: 1,
        is_current: 1,
        is_active: 1,
        sort_order: 0,
        create_time: "",
        update_time: ""
      };

      currentTheme.value = defaultTheme;
      applyThemeConfig(defaultTheme);
      storageLocal().setItem("current-theme", defaultTheme);

      message("已重置为默认主题", { type: "success" });
      return true;
    } catch (error) {
      console.error("重置主题失败:", error);
      message("重置主题失败", { type: "error" });
      return false;
    }
  };

  // 清除主题缓存
  const clearThemeCache = () => {
    storageLocal().removeItem("current-theme");
    initialized.value = false;
  };

  // 获取主题颜色值
  const getThemeColor = (colorKey: keyof typeof currentThemeConfig.value.colors) => {
    return currentThemeConfig.value.colors[colorKey];
  };

  // 获取主题布局值
  const getThemeLayout = (layoutKey: keyof typeof currentThemeConfig.value.layout) => {
    return currentThemeConfig.value.layout[layoutKey];
  };

  // 获取主题字体值
  const getThemeTypography = (typographyKey: keyof typeof currentThemeConfig.value.typography) => {
    return currentThemeConfig.value.typography[typographyKey];
  };

  // 获取主题特效值
  const getThemeEffects = (effectKey: keyof typeof currentThemeConfig.value.effects) => {
    return currentThemeConfig.value.effects[effectKey];
  };

  // 检查是否为暗色主题
  const isDarkTheme = computed(() => {
    if (!currentTheme.value) return false;

    // 简单的暗色主题检测逻辑
    const primaryColor = currentThemeConfig.value.colors.primary;
    const rgb = hexToRgb(primaryColor);
    if (!rgb) return false;

    // 计算亮度
    const brightness = (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1000;
    return brightness < 128;
  });

  // 辅助函数：十六进制颜色转RGB
  const hexToRgb = (hex: string) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : null;
  };

  // 监听系统主题变化
  const watchSystemTheme = () => {
    if (typeof window !== "undefined" && window.matchMedia) {
      const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");

      const handleChange = (e: MediaQueryListEvent) => {
        // 这里可以根据系统主题自动切换
        console.log("系统主题变化:", e.matches ? "dark" : "light");
      };

      mediaQuery.addEventListener("change", handleChange);

      // 返回清理函数
      return () => {
        mediaQuery.removeEventListener("change", handleChange);
      };
    }
  };

  return {
    // 状态
    currentTheme,
    themeList,
    loading,
    initialized,

    // 计算属性
    currentThemeConfig,
    isCurrentTheme,
    activeThemes,
    isDarkTheme,

    // 方法
    initTheme,
    fetchCurrentTheme,
    fetchThemeList,
    switchTheme,
    previewTheme,
    resetTheme,
    clearThemeCache,
    getThemeColor,
    getThemeLayout,
    getThemeTypography,
    getThemeEffects,
    watchSystemTheme
  };
});
