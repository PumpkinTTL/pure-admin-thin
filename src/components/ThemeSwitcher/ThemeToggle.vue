<template>
  <el-dropdown trigger="click" placement="bottom-end" @command="handleCommand" @visible-change="handleVisibleChange">
    <el-button :class="['theme-toggle', { 'is-active': dropdownVisible }]" :type="buttonType" :size="size"
      :circle="circle">
      <i :class="triggerIcon"></i>
    </el-button>

    <template #dropdown>
      <el-dropdown-menu class="theme-dropdown-menu">
        <!-- 当前主题 -->
        <div v-if="currentTheme" class="current-theme-section">
          <div class="section-label">当前主题</div>
          <div class="current-theme-item">
            <div class="theme-preview" :style="getThemePreviewStyle(currentTheme)">
              <div class="preview-colors">
                <span v-for="(color, key) in Object.values(currentTheme.config_data.colors).slice(0, 4)" :key="key"
                  class="color-dot" :style="{ backgroundColor: color }"></span>
              </div>
            </div>
            <div class="theme-info">
              <div class="theme-name">{{ currentTheme.theme_name }}</div>
              <div class="theme-key">{{ currentTheme.theme_key }}</div>
            </div>
          </div>
        </div>

        <el-divider style="margin: 8px 0;" />

        <!-- 主题列表 -->
        <div v-loading="loading" class="theme-list-section">
          <div class="section-label">切换主题</div>
          <el-dropdown-item v-for="theme in availableThemes" :key="theme.id" :command="{ action: 'switch', theme }"
            :class="{ 'is-current': isCurrentTheme(theme.id) }" :disabled="isCurrentTheme(theme.id)">
            <div class="theme-item">
              <div class="theme-preview" :style="getThemePreviewStyle(theme)">
                <div class="preview-colors">
                  <span v-for="(color, key) in Object.values(theme.config_data.colors).slice(0, 4)" :key="key"
                    class="color-dot" :style="{ backgroundColor: color }"></span>
                </div>
                <div v-if="isCurrentTheme(theme.id)" class="current-badge">
                  <i class="fa fa-check"></i>
                </div>
              </div>
              <div class="theme-info">
                <div class="theme-name">{{ theme.theme_name }}</div>
                <div class="theme-description">{{ theme.description || '暂无描述' }}</div>
              </div>
            </div>
          </el-dropdown-item>
        </div>

        <el-divider style="margin: 8px 0;" />

        <!-- 操作按钮 -->
        <div class="action-section">
          <el-dropdown-item :command="{ action: 'reset' }">
            <i class="fa fa-undo"></i>
            重置默认主题
          </el-dropdown-item>
          <el-dropdown-item :command="{ action: 'refresh' }">
            <i class="fa fa-refresh"></i>
            刷新主题列表
          </el-dropdown-item>
          <el-dropdown-item :command="{ action: 'manage' }" divided>
            <i class="fa fa-cog"></i>
            主题管理
          </el-dropdown-item>
        </div>
      </el-dropdown-menu>
    </template>
  </el-dropdown>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
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

// 使用路由和主题store
const router = useRouter()
const themeStore = useThemeStore()
const { currentTheme, themeList, loading } = storeToRefs(themeStore)

// 响应式数据
const dropdownVisible = ref(false)

// 计算属性
const availableThemes = computed(() => {
  return themeList.value.filter(theme => theme.is_active === 1)
})

// 方法
const handleVisibleChange = (visible: boolean) => {
  dropdownVisible.value = visible
  if (visible && availableThemes.value.length === 0) {
    loadThemes()
  }
}

const handleCommand = async (command: { action: string; theme?: ThemeConfig }) => {
  const { action, theme } = command

  try {
    switch (action) {
      case 'switch':
        if (theme) {
          await themeStore.switchTheme(theme)
        }
        break

      case 'reset':
        themeStore.resetTheme()
        break

      case 'refresh':
        await loadThemes()
        message('主题列表已刷新', { type: 'success' })
        break

      case 'manage':
        router.push('/system/theme')
        break

      default:
        console.warn('未知的命令:', action)
    }
  } catch (error) {
    message('操作失败', { type: 'error' })
    console.error('主题操作失败:', error)
  }
}

const loadThemes = async () => {
  try {
    await themeStore.fetchThemeList({ is_active: 1 })
  } catch (error) {
    console.error('获取主题列表失败:', error)
  }
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
</script>

<style lang="scss" scoped>
.theme-toggle {
  transition: all 0.3s ease;

  &.is-active {
    transform: rotate(180deg);
  }

  &:hover {
    transform: scale(1.05);
  }
}

:deep(.theme-dropdown-menu) {
  min-width: 280px;
  max-width: 320px;
  padding: 8px 0;

  .current-theme-section,
  .theme-list-section,
  .action-section {
    padding: 0 12px;
  }

  .section-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--el-text-color-secondary);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .current-theme-item {
    display: flex;
    align-items: center;
    padding: 8px;
    background: var(--el-color-primary-light-9);
    border-radius: 6px;
    border: 1px solid var(--el-color-primary-light-7);
    margin-bottom: 8px;

    .theme-preview {
      margin-right: 12px;
    }

    .theme-info {
      flex: 1;
      min-width: 0;

      .theme-name {
        font-weight: 500;
        color: var(--el-text-color-primary);
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .theme-key {
        font-size: 11px;
        color: var(--el-text-color-secondary);
        font-family: 'Courier New', monospace;
      }
    }
  }

  .el-dropdown-menu__item {
    padding: 0;

    &.is-current {
      background: var(--el-color-primary-light-9);
      color: var(--el-color-primary);
    }

    .theme-item {
      display: flex;
      align-items: center;
      padding: 8px 12px;
      width: 100%;

      .theme-preview {
        margin-right: 12px;
      }

      .theme-info {
        flex: 1;
        min-width: 0;

        .theme-name {
          font-weight: 500;
          color: var(--el-text-color-primary);
          margin-bottom: 2px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .theme-description {
          font-size: 11px;
          color: var(--el-text-color-secondary);
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
      }
    }

    i {
      margin-right: 8px;
      width: 14px;
      text-align: center;
    }
  }
}

.theme-preview {
  width: 32px;
  height: 20px;
  border-radius: 4px;
  border: 1px solid var(--el-border-color-light);
  overflow: hidden;
  position: relative;
  flex-shrink: 0;

  .preview-colors {
    display: flex;
    height: 100%;

    .color-dot {
      flex: 1;
      height: 100%;
    }
  }

  .current-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    background: var(--el-color-success);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid white;

    i {
      font-size: 6px;
      color: white;
      margin: 0;
    }
  }
}
</style>
