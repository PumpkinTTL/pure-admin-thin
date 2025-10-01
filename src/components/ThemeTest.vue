<template>
  <div class="theme-test">
    <h3>主题系统测试</h3>
    
    <div class="test-section">
      <h4>当前主题信息</h4>
      <div v-if="currentTheme">
        <p><strong>主题名称:</strong> {{ currentTheme.theme_name }}</p>
        <p><strong>主题键名:</strong> {{ currentTheme.theme_key }}</p>
        <p><strong>主色调:</strong> 
          <span 
            class="color-preview" 
            :style="{ backgroundColor: currentTheme.config_data.colors.primary }"
          ></span>
          {{ currentTheme.config_data.colors.primary }}
        </p>
      </div>
      <div v-else>
        <p>暂无主题信息</p>
      </div>
    </div>

    <div class="test-section">
      <h4>主题操作测试</h4>
      <el-button @click="initTheme">初始化主题</el-button>
      <el-button @click="resetTheme">重置主题</el-button>
      <el-button @click="loadThemes">加载主题列表</el-button>
    </div>

    <div class="test-section">
      <h4>主题列表 ({{ themeList.length }} 个)</h4>
      <div v-if="themeList.length > 0" class="theme-list">
        <div 
          v-for="theme in themeList" 
          :key="theme.id"
          class="theme-item"
          @click="switchToTheme(theme)"
        >
          <div class="theme-preview">
            <span 
              v-for="(color, key) in Object.values(theme.config_data.colors).slice(0, 4)"
              :key="key"
              class="color-dot"
              :style="{ backgroundColor: color }"
            ></span>
          </div>
          <div class="theme-info">
            <div class="theme-name">{{ theme.theme_name }}</div>
            <div class="theme-key">{{ theme.theme_key }}</div>
          </div>
          <div v-if="isCurrentTheme(theme.id)" class="current-badge">当前</div>
        </div>
      </div>
      <div v-else>
        <p>暂无主题数据</p>
      </div>
    </div>

    <div class="test-section">
      <h4>CSS变量测试</h4>
      <div class="css-variables-test">
        <div class="test-box primary">主色调测试</div>
        <div class="test-box success">成功色测试</div>
        <div class="test-box warning">警告色测试</div>
        <div class="test-box danger">危险色测试</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useThemeStore } from '@/store/modules/theme'
import { storeToRefs } from 'pinia'
import { message } from '@/utils/message'
import type { ThemeConfig } from '@/api/theme'

const themeStore = useThemeStore()
const { currentTheme, themeList, loading } = storeToRefs(themeStore)

const initTheme = async () => {
  try {
    await themeStore.initTheme()
    message('主题初始化成功', { type: 'success' })
  } catch (error) {
    message('主题初始化失败', { type: 'error' })
    console.error(error)
  }
}

const resetTheme = () => {
  themeStore.resetTheme()
  message('主题已重置', { type: 'success' })
}

const loadThemes = async () => {
  try {
    await themeStore.fetchThemeList()
    message(`加载了 ${themeList.value.length} 个主题`, { type: 'success' })
  } catch (error) {
    message('加载主题列表失败', { type: 'error' })
    console.error(error)
  }
}

const switchToTheme = async (theme: ThemeConfig) => {
  try {
    await themeStore.switchTheme(theme)
    message(`已切换到主题: ${theme.theme_name}`, { type: 'success' })
  } catch (error) {
    message('切换主题失败', { type: 'error' })
    console.error(error)
  }
}

const isCurrentTheme = (themeId: number) => {
  return themeStore.isCurrentTheme(themeId)
}

onMounted(() => {
  initTheme()
  loadThemes()
})
</script>

<style lang="scss" scoped>
.theme-test {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
  
  .test-section {
    margin-bottom: 30px;
    padding: 20px;
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    
    h4 {
      margin-top: 0;
      color: var(--el-color-primary);
    }
  }
  
  .color-preview {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid var(--el-border-color);
    margin-right: 8px;
    vertical-align: middle;
  }
  
  .theme-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
    
    .theme-item {
      display: flex;
      align-items: center;
      padding: 12px;
      border: 1px solid var(--el-border-color-light);
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      
      &:hover {
        border-color: var(--el-color-primary);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      }
      
      .theme-preview {
        display: flex;
        margin-right: 12px;
        
        .color-dot {
          width: 12px;
          height: 12px;
          border-radius: 2px;
          margin-right: 2px;
        }
      }
      
      .theme-info {
        flex: 1;
        
        .theme-name {
          font-weight: 500;
          margin-bottom: 4px;
        }
        
        .theme-key {
          font-size: 12px;
          color: var(--el-text-color-secondary);
          font-family: monospace;
        }
      }
      
      .current-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--el-color-success);
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
      }
    }
  }
  
  .css-variables-test {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    
    .test-box {
      padding: 16px;
      text-align: center;
      border-radius: 8px;
      color: white;
      font-weight: 500;
      
      &.primary {
        background-color: var(--theme-color-primary, var(--el-color-primary));
      }
      
      &.success {
        background-color: var(--theme-color-success, var(--el-color-success));
      }
      
      &.warning {
        background-color: var(--theme-color-warning, var(--el-color-warning));
      }
      
      &.danger {
        background-color: var(--theme-color-danger, var(--el-color-danger));
      }
    }
  }
}
</style>
