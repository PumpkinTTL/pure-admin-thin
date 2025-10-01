<template>
  <div class="theme-provider">
    <slot />

    <!-- 主题切换加载遮罩 -->
    <Transition name="theme-loading">
      <div v-if="loading" class="theme-loading-overlay">
        <div class="theme-loading-content">
          <div class="theme-loading-spinner">
            <i class="fa fa-palette fa-spin"></i>
          </div>
          <p class="theme-loading-text">正在应用主题...</p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'
import { useThemeStore } from '@/store/modules/theme'
import { storeToRefs } from 'pinia'

// 使用主题store
const themeStore = useThemeStore()
const { loading, currentTheme, initialized } = storeToRefs(themeStore)

// 监听主题变化
let cleanupSystemThemeWatcher: (() => void) | undefined

onMounted(async () => {
  try {
    // 初始化主题
    await themeStore.initTheme()

    // 监听系统主题变化
    cleanupSystemThemeWatcher = themeStore.watchSystemTheme()

    // 监听当前主题变化，应用CSS变量
    watch(
      () => currentTheme.value,
      (newTheme) => {
        if (newTheme) {
          applyThemeVariables(newTheme)
        }
      },
      { immediate: true }
    )

    // 监听页面可见性变化，重新同步主题
    document.addEventListener('visibilitychange', handleVisibilityChange)

  } catch (error) {
    console.error('主题初始化失败:', error)
  }
})

onUnmounted(() => {
  // 清理监听器
  if (cleanupSystemThemeWatcher) {
    cleanupSystemThemeWatcher()
  }
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})

// 应用主题CSS变量
const applyThemeVariables = (theme: any) => {
  if (!theme?.config_data) return

  const { colors, layout, typography, effects } = theme.config_data
  const root = document.documentElement

  // 应用颜色变量
  Object.entries(colors).forEach(([key, value]) => {
    root.style.setProperty(`--theme-color-${key}`, value as string)
  })

  // 应用布局变量
  Object.entries(layout).forEach(([key, value]) => {
    const unit = typeof value === 'number' ? 'px' : ''
    root.style.setProperty(`--theme-layout-${key.replace(/_/g, '-')}`, `${value}${unit}`)
  })

  // 应用字体变量
  Object.entries(typography).forEach(([key, value]) => {
    const unit = key === 'font_size_base' ? 'px' : ''
    root.style.setProperty(`--theme-typography-${key.replace(/_/g, '-')}`, `${value}${unit}`)
  })

  // 应用特效变量
  Object.entries(effects).forEach(([key, value]) => {
    root.style.setProperty(`--theme-effects-${key.replace(/_/g, '-')}`, value as string)
  })

  // 设置主题标识
  root.setAttribute('data-theme', theme.theme_key)
  root.setAttribute('data-theme-id', theme.id.toString())
}

// 处理页面可见性变化
const handleVisibilityChange = () => {
  if (!document.hidden && initialized.value) {
    // 页面重新可见时，重新获取当前主题（防止其他地方修改了主题）
    themeStore.fetchCurrentTheme().catch(error => {
      console.warn('重新获取主题失败:', error)
    })
  }
}
</script>

<style lang="scss" scoped>
.theme-provider {
  width: 100%;
  height: 100%;
}

.theme-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;

  .theme-loading-content {
    background: white;
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    min-width: 200px;

    .theme-loading-spinner {
      margin-bottom: 16px;

      i {
        font-size: 32px;
        color: var(--el-color-primary, #409EFF);
      }
    }

    .theme-loading-text {
      margin: 0;
      color: var(--el-text-color-primary, #303133);
      font-size: 14px;
    }
  }
}

// 主题切换动画
.theme-loading-enter-active,
.theme-loading-leave-active {
  transition: all 0.3s ease;
}

.theme-loading-enter-from,
.theme-loading-leave-to {
  opacity: 0;

  .theme-loading-content {
    transform: scale(0.8);
  }
}

// 暗色模式适配
@media (prefers-color-scheme: dark) {
  .theme-loading-overlay {
    .theme-loading-content {
      background: var(--el-bg-color, #1d1e1f);
      color: var(--el-text-color-primary, #e5eaf3);
    }
  }
}
</style>

<style lang="scss">
// 全局主题CSS变量定义
:root {
  // 默认颜色变量
  --theme-color-primary: #409EFF;
  --theme-color-secondary: #909399;
  --theme-color-success: #67C23A;
  --theme-color-warning: #E6A23C;
  --theme-color-danger: #F56C6C;
  --theme-color-info: #909399;

  // 默认布局变量
  --theme-layout-sidebar-width: 240px;
  --theme-layout-header-height: 60px;
  --theme-layout-border-radius: 4px;
  --theme-layout-content-padding: 20px;

  // 默认字体变量
  --theme-typography-font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
  --theme-typography-font-size-base: 14px;
  --theme-typography-line-height: 1.5;

  // 默认特效变量
  --theme-effects-shadow-level: 2;
  --theme-effects-animation-duration: 0.3s;
  --theme-effects-transition-timing: ease-in-out;
}

// 主题变量应用到Element Plus
:root {
  --el-color-primary: var(--theme-color-primary);
  --el-color-success: var(--theme-color-success);
  --el-color-warning: var(--theme-color-warning);
  --el-color-danger: var(--theme-color-danger);
  --el-color-info: var(--theme-color-info);

  --el-border-radius-base: var(--theme-layout-border-radius);
  --el-font-size-base: var(--theme-typography-font-size-base);
  --el-font-family: var(--theme-typography-font-family);
}

// 主题切换时的平滑过渡
* {
  transition:
    background-color var(--theme-effects-animation-duration) var(--theme-effects-transition-timing),
    border-color var(--theme-effects-animation-duration) var(--theme-effects-transition-timing),
    color var(--theme-effects-animation-duration) var(--theme-effects-transition-timing),
    box-shadow var(--theme-effects-animation-duration) var(--theme-effects-transition-timing);
}

// 禁用某些元素的过渡效果（避免影响性能）
img,
video,
iframe,
canvas,
svg {
  transition: none !important;
}

// 主题特定样式
[data-theme="dark"] {
  --el-bg-color: #1d1e1f;
  --el-bg-color-page: #0a0a0a;
  --el-text-color-primary: #e5eaf3;
  --el-text-color-regular: #cfd3dc;
  --el-border-color: #414243;
}

[data-theme="light"] {
  --el-bg-color: #ffffff;
  --el-bg-color-page: #f2f3f5;
  --el-text-color-primary: #303133;
  --el-text-color-regular: #606266;
  --el-border-color: #dcdfe6;
}

// 响应式主题变量
@media (max-width: 768px) {
  :root {
    --theme-layout-sidebar-width: 200px;
    --theme-layout-header-height: 50px;
    --theme-layout-content-padding: 16px;
    --theme-typography-font-size-base: 13px;
  }
}

@media (max-width: 480px) {
  :root {
    --theme-layout-sidebar-width: 180px;
    --theme-layout-header-height: 45px;
    --theme-layout-content-padding: 12px;
    --theme-typography-font-size-base: 12px;
  }
}
</style>
