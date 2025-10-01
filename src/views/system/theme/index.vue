<template>
  <div class="theme-management-flat" v-motion-fade>
    <!-- 顶部栏 -->
    <div class="flat-header animate__animated animate__fadeInDown">
      <div class="header-left">
        <div class="page-icon">
          <i class="fa fa-palette"></i>
        </div>
        <div class="header-content">
          <h1 class="page-title">主题管理</h1>
          <p class="page-desc">共 <span class="count">{{ total }}</span> 个主题</p>
        </div>
      </div>
      <div class="header-actions">
        <el-input v-model="searchForm.keyword" placeholder="搜索主题..." clearable size="small" @keyup.enter="handleSearch"
          class="search-input">
          <template #prefix>
            <i class="fa fa-search"></i>
          </template>
        </el-input>
        <el-select v-model="searchForm.is_active" placeholder="状态" clearable size="small" class="status-select"
          @change="handleSearch">
          <el-option label="全部" :value="null" />
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        <el-button type="primary" size="small" @click="handleCreate">
          <i class="fa fa-plus"></i>
          创建
        </el-button>
        <el-button size="small" @click="handleReset">
          <i class="fa fa-refresh"></i>
        </el-button>
      </div>
    </div>

    <!-- 主题卡片网格 -->
    <div v-loading="loading" class="theme-grid-flat" element-loading-text="加载中...">
      <transition-group name="card" tag="div" class="grid-container">
        <div v-for="theme in themeList" :key="theme.id" class="theme-card-flat" v-motion-pop>
          <div :class="['card-wrapper', { 'is-current': theme.is_current, 'is-disabled': !theme.is_active }]">
            <!-- 当前主题角标 -->
            <div v-if="theme.is_current" class="current-badge">
              <i class="fa fa-star"></i>
            </div>

            <!-- 卡片头部 -->
            <div class="card-header">
              <div class="theme-info">
                <h3 class="theme-name">{{ theme.theme_name }}</h3>
                <div class="theme-meta">
                  <el-tag v-if="theme.is_system" size="small" effect="plain" class="system-tag">
                    <i class="fa fa-shield-alt"></i> 系统
                  </el-tag>
                  <span class="theme-key">{{ theme.theme_key }}</span>
                </div>
              </div>
            </div>

            <!-- 配色展示区 - 优化版 -->
            <div class="color-showcase">
              <!-- 主色展示 -->
              <div class="main-display" :style="{ background: getThemeColor(theme, 'primary') }">
                <div class="gradient-overlay"></div>
                <div class="display-content">
                  <span class="color-value">{{ getThemeColor(theme, 'primary') }}</span>
                  <div class="accent-dots">
                    <span
                      v-for="(color, idx) in [getThemeColor(theme, 'success'), getThemeColor(theme, 'warning'), getThemeColor(theme, 'danger')]"
                      :key="idx" class="dot" :style="{ background: color }"></span>
                  </div>
                </div>
              </div>

              <!-- 配色网格 -->
              <div class="color-palette">
                <div v-for="(color, index) in getThemePalette(theme)" :key="index" class="palette-item"
                  :style="{ background: color }" :title="['主色', '成功', '警告', '危险', '信息'][index]">
                  <span class="palette-label">{{ ['主', '成', '警', '危', '信'][index] }}</span>
                </div>
              </div>
            </div>

            <!-- 描述 -->
            <div class="card-body">
              <p class="theme-desc">{{ theme.description || '暂无描述' }}</p>
            </div>

            <!-- 操作按钮区 -->
            <div class="card-actions">
              <el-button v-if="!theme.is_current" type="primary" size="small" @click.stop="handleSetCurrent(theme)"
                class="action-btn">
                <i class="fa fa-check"></i> 应用
              </el-button>
              <el-button size="small" @click.stop="handleEdit(theme)" class="action-btn">
                <i class="fa fa-edit"></i> 编辑
              </el-button>
              <el-button v-if="!theme.is_current && !theme.is_system" type="danger" size="small"
                @click.stop="handleDelete(theme)" class="action-btn delete-btn">
                <i class="fa fa-trash"></i> 删除
              </el-button>
              <el-dropdown trigger="click" size="small" @click.stop>
                <el-button size="small" class="action-btn more-btn">
                  <i class="fa fa-ellipsis-h"></i>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item @click="handleToggleStatus(theme)">
                      <i :class="theme.is_active ? 'fa fa-pause' : 'fa fa-play'"></i>
                      {{ theme.is_active ? '禁用' : '启用' }}
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>

            <!-- 底部元信息 -->
            <div class="card-footer">
              <span class="meta-item">
                <i class="fa fa-clock"></i>
                {{ formatTime(theme.create_time) }}
              </span>
              <el-tag :type="theme.is_active ? 'success' : 'info'" size="small" effect="plain">
                {{ theme.is_active ? '启用' : '禁用' }}
              </el-tag>
            </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div v-if="!loading && themeList.length === 0" key="empty" class="empty-state">
          <i class="fa fa-inbox"></i>
          <p>暂无主题</p>
          <el-button type="primary" size="small" @click="handleCreate">
            <i class="fa fa-plus"></i> 创建主题
          </el-button>
        </div>
      </transition-group>
    </div>

    <!-- 分页 -->
    <div v-if="total > 0" class="pagination-flat">
      <el-pagination v-model:current-page="pagination.page" v-model:page-size="pagination.pageSize" :total="total"
        :page-sizes="[12, 24, 36, 48]" layout="total, sizes, prev, pager, next" @size-change="handleSizeChange"
        @current-change="handleCurrentChange" small background />
    </div>

    <!-- 主题编辑器 -->
    <ThemeEditor v-model:visible="editorVisible" :theme-data="currentTheme" :is-edit="isEdit"
      @success="handleEditorSuccess" />
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: 'ThemeManagementFlat'
})
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox } from 'element-plus'
import { getThemeList, setCurrentTheme, toggleThemeStatus, deleteTheme, type ThemeConfig, type ThemeListParams } from '@/api/theme'
import { message } from '@/utils/message'
import ThemeEditor from './components/ThemeEditor.vue'

const loading = ref(false)
const themeList = ref<ThemeConfig[]>([])
const total = ref(0)
const editorVisible = ref(false)
const isEdit = ref(false)
const currentTheme = ref<ThemeConfig | null>(null)

const searchForm = reactive<ThemeListParams>({
  keyword: '',
  is_active: null
})

const pagination = reactive({
  page: 1,
  pageSize: 12
})

const fetchThemeList = async () => {
  try {
    loading.value = true
    const params = {
      ...searchForm,
      page: pagination.page,
      page_size: pagination.pageSize
    }

    const response = await getThemeList(params)
    if (response.code === 200) {
      themeList.value = response.data.list
      total.value = response.data.total
    } else {
      message(response.msg, { type: 'error' })
    }
  } catch (error) {
    message('获取主题列表失败', { type: 'error' })
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchThemeList()
}

const handleReset = () => {
  searchForm.keyword = ''
  searchForm.is_active = null
  pagination.page = 1
  fetchThemeList()
}

const handleSizeChange = (size: number) => {
  pagination.pageSize = size
  pagination.page = 1
  fetchThemeList()
}

const handleCurrentChange = (page: number) => {
  pagination.page = page
  fetchThemeList()
}

const handleCreate = () => {
  currentTheme.value = null
  isEdit.value = false
  editorVisible.value = true
}

const handleEdit = (theme: ThemeConfig) => {
  currentTheme.value = theme
  isEdit.value = true
  editorVisible.value = true
}

const handleSetCurrent = async (theme: ThemeConfig) => {
  try {
    await ElMessageBox.confirm(
      `确定要应用"${theme.theme_name}"主题吗？`,
      '确认',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    const response = await setCurrentTheme(theme.id)
    if (response.code === 200) {
      message('应用成功', { type: 'success' })
      fetchThemeList()
    } else {
      message(response.msg, { type: 'error' })
    }
  } catch (error) {
    // 用户取消
  }
}

const handleToggleStatus = async (theme: ThemeConfig) => {
  try {
    const action = theme.is_active ? '禁用' : '启用'
    await ElMessageBox.confirm(
      `确定要${action}"${theme.theme_name}"吗？`,
      '确认',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    const response = await toggleThemeStatus(theme.id)
    if (response.code === 200) {
      message(`${action}成功`, { type: 'success' })
      fetchThemeList()
    } else {
      message(response.msg, { type: 'error' })
    }
  } catch (error) {
    // 用户取消
  }
}

const handleDelete = async (theme: ThemeConfig) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除"${theme.theme_name}"吗？此操作不可恢复！`,
      '危险操作',
      {
        confirmButtonText: '确认删除',
        cancelButtonText: '取消',
        type: 'error'
      }
    )

    const response = await deleteTheme(theme.id)
    if (response.code === 200) {
      message('删除成功', { type: 'success' })
      fetchThemeList()
    } else {
      message(response.msg, { type: 'error' })
    }
  } catch (error) {
    // 用户取消
  }
}

const getThemeColor = (theme: ThemeConfig, colorType: string) => {
  const defaultColors = {
    primary: '#409eff',
    success: '#67c23a',
    warning: '#e6a23c',
    danger: '#f56c6c',
    info: '#909399',
    secondary: '#909399'
  }

  if (theme.config_data && typeof theme.config_data === 'object') {
    const configData = theme.config_data as any
    if (configData.colors && configData.colors[colorType]) {
      return configData.colors[colorType]
    }
  }

  return defaultColors[colorType as keyof typeof defaultColors] || defaultColors.primary
}

const getThemePalette = (theme: ThemeConfig) => {
  return [
    getThemeColor(theme, 'primary'),
    getThemeColor(theme, 'success'),
    getThemeColor(theme, 'warning'),
    getThemeColor(theme, 'danger'),
    getThemeColor(theme, 'info')
  ]
}

const formatTime = (time: string) => {
  if (!time) return '-'
  const date = new Date(time)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (days === 0) return '今天'
  if (days === 1) return '昨天'
  if (days < 7) return `${days}天前`

  return date.toLocaleDateString('zh-CN', {
    month: '2-digit',
    day: '2-digit'
  })
}

const handleEditorSuccess = () => {
  fetchThemeList()
}

onMounted(() => {
  fetchThemeList()
})
</script>

<style lang="scss" scoped>
.theme-management-flat {
  padding: 12px;
  background: var(--el-bg-color-page);
  min-height: 100vh;

  // 顶部栏
  .flat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 10px 14px;
    background: var(--el-bg-color);
    border-radius: 4px;
    border: 1px solid var(--el-border-color-lighter);

    .header-left {
      display: flex;
      align-items: center;
      gap: 10px;

      .page-icon {
        width: 32px;
        height: 32px;
        background: var(--el-color-primary);
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;

        i {
          font-size: 14px;
          color: white;
        }
      }

      .header-content {
        .page-title {
          margin: 0;
          font-size: 14px;
          font-weight: 600;
          color: var(--el-text-color-primary);
          line-height: 1.2;
        }

        .page-desc {
          margin: 2px 0 0 0;
          font-size: 11px;
          color: var(--el-text-color-secondary);

          .count {
            font-weight: 700;
            color: var(--el-color-primary);
          }
        }
      }
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 8px;

      .search-input {
        width: 180px;
      }

      .status-select {
        width: 90px;
      }
    }
  }

  // 主题网格
  .theme-grid-flat {
    min-height: 300px;

    .grid-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 12px;

      .theme-card-flat {
        .card-wrapper {
          position: relative;
          background: var(--el-bg-color);
          border: 1px solid var(--el-border-color-lighter);
          border-radius: 4px;
          overflow: hidden;
          transition: all 0.2s ease;

          &:hover {
            border-color: var(--el-color-primary-light-5);
            transform: translateY(-2px);
          }

          &.is-current {
            border-color: var(--el-color-primary);
            border-width: 2px;
          }

          &.is-disabled {
            opacity: 0.6;
          }

          // 当前角标
          .current-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 22px;
            height: 22px;
            background: var(--el-color-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;

            i {
              font-size: 10px;
              color: white;
            }
          }

          // 头部
          .card-header {
            padding: 10px 12px;
            border-bottom: 1px solid var(--el-border-color-lighter);

            .theme-info {
              .theme-name {
                margin: 0 0 6px 0;
                font-size: 14px;
                font-weight: 600;
                color: var(--el-text-color-primary);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
              }

              .theme-meta {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;

                .system-tag {
                  i {
                    margin-right: 2px;
                    font-size: 9px;
                  }
                }

                .theme-key {
                  padding: 2px 6px;
                  background: var(--el-fill-color);
                  border-radius: 2px;
                  font-size: 10px;
                  font-family: 'Courier New', monospace;
                  color: var(--el-text-color-secondary);
                }
              }
            }
          }

          // 配色展示 - 优化版
          .color-showcase {
            padding: 12px;

            .main-display {
              position: relative;
              height: 60px;
              border-radius: 4px;
              overflow: hidden;
              margin-bottom: 8px;
              transition: all 0.2s ease;

              .gradient-overlay {
                position: absolute;
                inset: 0;
                background: linear-gradient(135deg, transparent 0%, rgba(0, 0, 0, 0.12) 100%);
                pointer-events: none;
              }

              .display-content {
                position: relative;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 14px;
                z-index: 1;

                .color-value {
                  font-size: 12px;
                  font-weight: 700;
                  color: white;
                  font-family: 'Courier New', monospace;
                  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
                  letter-spacing: 0.8px;
                }

                .accent-dots {
                  display: flex;
                  gap: 6px;

                  .dot {
                    width: 16px;
                    height: 16px;
                    border-radius: 2px;
                    border: 2px solid rgba(255, 255, 255, 0.4);
                    transition: all 0.2s ease;

                    &:hover {
                      transform: scale(1.2);
                      border-color: rgba(255, 255, 255, 0.9);
                    }
                  }
                }
              }

              &:hover {
                transform: translateY(-1px);
              }
            }

            .color-palette {
              display: flex;
              gap: 4px;

              .palette-item {
                position: relative;
                flex: 1;
                height: 30px;
                border-radius: 3px;
                cursor: pointer;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;

                &::before {
                  content: '';
                  position: absolute;
                  inset: 0;
                  background: linear-gradient(to bottom, rgba(255, 255, 255, 0.2), transparent);
                  opacity: 0;
                  transition: opacity 0.2s ease;
                }

                .palette-label {
                  position: relative;
                  z-index: 1;
                  font-size: 10px;
                  font-weight: 700;
                  color: white;
                  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
                  opacity: 0;
                  transition: opacity 0.2s ease;
                }

                &:hover {
                  transform: translateY(-3px);

                  &::before {
                    opacity: 1;
                  }

                  .palette-label {
                    opacity: 1;
                  }
                }
              }
            }
          }

          // 主体
          .card-body {
            padding: 0 12px 10px 12px;

            .theme-desc {
              margin: 0;
              font-size: 12px;
              line-height: 1.5;
              color: var(--el-text-color-regular);
              display: -webkit-box;
              -webkit-line-clamp: 2;
              line-clamp: 2;
              -webkit-box-orient: vertical;
              overflow: hidden;
              min-height: 36px;
            }
          }

          // 操作按钮区
          .card-actions {
            padding: 10px 12px;
            display: flex;
            gap: 6px;
            border-top: 1px solid var(--el-border-color-lighter);

            .action-btn {
              flex: 1;
              font-size: 11px;
              padding: 5px 8px;

              i {
                margin-right: 3px;
                font-size: 10px;
              }

              &.delete-btn {
                flex: 0 0 auto;

                &:hover {
                  background: #f56c6c;
                  border-color: #f56c6c;
                  color: white;
                }
              }

              &.more-btn {
                flex: 0 0 auto;
                min-width: 32px;
                padding: 5px;

                i {
                  margin: 0;
                }
              }
            }
          }

          // 底部元信息
          .card-footer {
            padding: 8px 12px;
            background: var(--el-fill-color-extra-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--el-border-color-lighter);

            .meta-item {
              display: flex;
              align-items: center;
              gap: 4px;
              font-size: 10px;
              color: var(--el-text-color-secondary);

              i {
                font-size: 9px;
              }
            }
          }
        }
      }

      // 空状态
      .empty-state {
        grid-column: 1 / -1;
        padding: 60px 20px;
        text-align: center;

        i {
          font-size: 48px;
          color: var(--el-text-color-disabled);
          opacity: 0.5;
          margin-bottom: 16px;
        }

        p {
          margin: 0 0 16px 0;
          font-size: 13px;
          color: var(--el-text-color-secondary);
        }
      }
    }
  }

  // 分页
  .pagination-flat {
    margin-top: 12px;
    display: flex;
    justify-content: center;
  }

  // 卡片动画
  .card-enter-active,
  .card-leave-active {
    transition: all 0.3s ease;
  }

  .card-enter-from {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
  }

  .card-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }
}

// 响应式
@media (max-width: 768px) {
  .theme-management-flat {
    padding: 8px;

    .flat-header {
      flex-direction: column;
      gap: 10px;
      padding: 12px;

      .header-left {
        width: 100%;
      }

      .header-actions {
        width: 100%;
        flex-wrap: wrap;

        .search-input {
          flex: 1;
          min-width: 120px;
        }

        .status-select {
          width: 80px;
        }
      }
    }

    .theme-grid-flat .grid-container {
      grid-template-columns: 1fr;
      gap: 10px;
    }
  }
}
</style>