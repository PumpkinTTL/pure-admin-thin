<template>
  <div class="theme-switcher">
    <!-- 触发按钮 -->
    <el-tooltip :content="tooltipContent" placement="bottom">
      <el-button :class="['theme-trigger', { 'is-active': visible }]" :type="buttonType" :size="size" :circle="circle"
        @click="togglePanel">
        <i :class="triggerIcon"></i>
      </el-button>
    </el-tooltip>

    <!-- 主题选择面板 -->
    <Teleport to="body">
      <Transition name="theme-panel">
        <div v-if="visible" class="theme-panel-overlay" @click="closePanel">
          <div class="theme-panel" @click.stop>
            <div class="theme-panel-header">
              <h3 class="panel-title">
                <i class="fa fa-palette"></i>
                选择主题
              </h3>
              <el-button type="info" size="small" text @click="closePanel">
                <i class="fa fa-times"></i>
              </el-button>
            </div>

            <div class="theme-panel-content">
              <!-- 当前主题信息 -->
              <div v-if="currentTheme" class="current-theme-info">
                <div class="current-theme-label">当前主题</div>
                <div class="current-theme-card">
                  <div class="theme-preview" :style="getThemePreviewStyle(currentTheme)">
                    <div class="preview-colors">
                      <span v-for="(color, key) in currentTheme.config_data.colors" :key="key" class="color-dot"
                        :style="{ backgroundColor: color }"></span>
                    </div>
                  </div>
                  <div class="theme-info">
                    <div class="theme-name">{{ currentTheme.theme_name }}</div>
                    <div class="theme-description">{{ currentTheme.description || '暂无描述' }}</div>
                  </div>
                </div>
              </div>

              <!-- 主题列表 -->
              <div class="theme-list-section">
                <div class="section-title">可用主题</div>
                <div v-loading="loading" class="theme-list">
                  <div v-for="theme in availableThemes" :key="theme.id" :class="[
                    'theme-item',
                    { 'is-current': isCurrentTheme(theme.id) }
                  ]" @click="handleThemeSelect(theme)">
                    <div class="theme-preview" :style="getThemePreviewStyle(theme)">
                      <div class="preview-colors">
                        <span v-for="(color, key) in theme.config_data.colors" :key="key" class="color-dot"
                          :style="{ backgroundColor: color }"></span>
                      </div>
                      <div v-if="isCurrentTheme(theme.id)" class="current-badge">
                        <i class="fa fa-check"></i>
                      </div>
                    </div>
                    <div class="theme-info">
                      <div class="theme-name">{{ theme.theme_name }}</div>
                      <div class="theme-description">{{ theme.description || '暂无描述' }}</div>
                    </div>
                    <div class="theme-actions">
                      <el-button v-if="!isCurrentTheme(theme.id)" type="primary" size="small"
                        @click.stop="handleThemeSelect(theme)">
                        应用
                      </el-button>
                      <el-button type="info" size="small" @click.stop="handleThemePreview(theme)">
                        预览
                      </el-button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 快速操作 -->
              <div class="quick-actions">
                <el-button type="warning" size="small" @click="handleResetTheme">
                  <i class="fa fa-undo"></i>
                  重置默认
                </el-button>
                <el-button type="info" size="small" @click="handleRefreshThemes">
                  <i class="fa fa-refresh"></i>
                  刷新列表
                </el-button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useThemeStore } from '@/store/modules/theme'
import { storeToRefs } from 'pinia'
import { message } from '@/utils/message'
import type { ThemeConfig } from '@/api/theme'

// Props
interface Props {
  size?: 'large' | 'default' | 'small'
  buttonType?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'text' | 'default'
  circle?: boolean
  triggerIcon?: string
  autoLoad?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'default',
  buttonType: 'default',
  circle: true,
  triggerIcon: 'fa fa-palette',
  autoLoad: true
})

// 使用主题store
const themeStore = useThemeStore()
const { currentTheme, themeList, loading } = storeToRefs(themeStore)

// 响应式数据
const visible = ref(false)

// 计算属性
const tooltipContent = computed(() => {
  return visible.value ? '关闭主题选择器' : '打开主题选择器'
})

const availableThemes = computed(() => {
  return themeList.value.filter(theme => theme.is_active === 1)
})

// 方法
const togglePanel = () => {
  visible.value = !visible.value
  if (visible.value && availableThemes.value.length === 0) {
    loadThemes()
  }
}

const closePanel = () => {
  visible.value = false
}

const loadThemes = async () => {
  try {
    await themeStore.fetchThemeList({ is_active: 1 })
  } catch (error) {
    message('获取主题列表失败', { type: 'error' })
  }
}

const handleThemeSelect = async (theme: ThemeConfig) => {
  try {
    await themeStore.switchTheme(theme)
    closePanel()
  } catch (error) {
    message('切换主题失败', { type: 'error' })
  }
}

const handleThemePreview = (theme: ThemeConfig) => {
  themeStore.previewTheme(theme)
  message(`正在预览主题：${theme.theme_name}`, { type: 'info' })
}

const handleResetTheme = () => {
  themeStore.resetTheme()
  closePanel()
}

const handleRefreshThemes = () => {
  loadThemes()
}

const isCurrentTheme = (themeId: number) => {
  return themeStore.isCurrentTheme(themeId)
}

const getThemePreviewStyle = (theme: ThemeConfig) => {
  const { colors } = theme.config_data
  return {
    '--preview-primary': colors.primary,
    '--preview-success': colors.success,
    '--preview-warning': colors.warning,
    '--preview-danger': colors.danger
  }
}

// 生命周期
onMounted(() => {
  if (props.autoLoad) {
    loadThemes()
  }
})

// 监听点击外部关闭
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as Element
  if (!target.closest('.theme-panel') && !target.closest('.theme-trigger')) {
    closePanel()
  }
}

watch(visible, (newVisible) => {
  if (newVisible) {
    document.addEventListener('click', handleClickOutside)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})
</script>

<style lang="scss" scoped>
.theme-switcher {
  position: relative;

  .theme-trigger {
    transition: all 0.3s ease;

    &.is-active {
      transform: rotate(180deg);
    }

    &:hover {
      transform: scale(1.1);
    }
  }
}

.theme-panel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.theme-panel {
  background: var(--el-bg-color);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  width: 90vw;
  max-width: 600px;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;

  .theme-panel-header {
    padding: 20px;
    border-bottom: 1px solid var(--el-border-color-light);
    display: flex;
    align-items: center;
    justify-content: space-between;

    .panel-title {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
      color: var(--el-text-color-primary);

      i {
        margin-right: 8px;
        color: var(--el-color-primary);
      }
    }
  }

  .theme-panel-content {
    flex: 1;
    padding: 20px;
    overflow-y: auto;

    .current-theme-info {
      margin-bottom: 24px;

      .current-theme-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
        margin-bottom: 12px;
      }

      .current-theme-card {
        display: flex;
        align-items: center;
        padding: 16px;
        background: var(--el-fill-color-light);
        border-radius: 8px;
        border: 2px solid var(--el-color-primary);

        .theme-preview {
          margin-right: 16px;
        }

        .theme-info {
          flex: 1;

          .theme-name {
            font-weight: 600;
            color: var(--el-text-color-primary);
            margin-bottom: 4px;
          }

          .theme-description {
            font-size: 12px;
            color: var(--el-text-color-secondary);
          }
        }
      }
    }

    .theme-list-section {
      margin-bottom: 24px;

      .section-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
        margin-bottom: 12px;
      }

      .theme-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;

        .theme-item {
          display: flex;
          align-items: center;
          padding: 12px;
          border: 1px solid var(--el-border-color-light);
          border-radius: 8px;
          cursor: pointer;
          transition: all 0.3s ease;

          &:hover {
            border-color: var(--el-color-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          }

          &.is-current {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
          }

          .theme-preview {
            margin-right: 12px;
            position: relative;
          }

          .theme-info {
            flex: 1;
            min-width: 0;

            .theme-name {
              font-weight: 500;
              color: var(--el-text-color-primary);
              margin-bottom: 4px;
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
            }

            .theme-description {
              font-size: 12px;
              color: var(--el-text-color-secondary);
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
            }
          }

          .theme-actions {
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
          }

          &:hover .theme-actions {
            opacity: 1;
          }
        }
      }
    }

    .quick-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      padding-top: 16px;
      border-top: 1px solid var(--el-border-color-light);
    }
  }
}

.theme-preview {
  width: 48px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid var(--el-border-color-light);
  overflow: hidden;
  position: relative;

  .preview-colors {
    display: flex;
    height: 100%;

    .color-dot {
      flex: 1;
      height: 100%;

      &:first-child {
        background: var(--preview-primary, #409EFF) !important;
      }

      &:nth-child(2) {
        background: var(--preview-success, #67C23A) !important;
      }

      &:nth-child(3) {
        background: var(--preview-warning, #E6A23C) !important;
      }

      &:nth-child(4) {
        background: var(--preview-danger, #F56C6C) !important;
      }
    }
  }

  .current-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 16px;
    height: 16px;
    background: var(--el-color-success);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;

    i {
      font-size: 8px;
      color: white;
    }
  }
}

// 动画
.theme-panel-enter-active,
.theme-panel-leave-active {
  transition: all 0.3s ease;
}

.theme-panel-enter-from,
.theme-panel-leave-to {
  opacity: 0;

  .theme-panel {
    transform: scale(0.8);
  }
}

// 响应式
@media (max-width: 768px) {
  .theme-panel {
    width: 95vw;
    max-height: 90vh;

    .theme-panel-content {
      padding: 16px;

      .theme-list {
        grid-template-columns: 1fr;
      }
    }
  }
}
</style>
