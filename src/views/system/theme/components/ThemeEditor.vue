<template>
  <div>
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑主题' : '创建主题'" width="750px" :close-on-click-modal="false"
      @close="handleClose" class="theme-editor-compact">
      <div class="editor-container">
        <el-form ref="formRef" :model="formData" :rules="formRules" label-position="top" size="small"
          class="compact-form">
          <!-- 基本信息 - 横向紧凑 -->
          <el-row :gutter="10">
            <el-col :span="8">
              <el-form-item label="主题键名" prop="theme_key">
                <el-input v-model="formData.theme_key" placeholder="my-theme" :disabled="isEdit" size="small">
                  <template #prefix><i class="fa fa-key"></i></template>
                </el-input>
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="主题名称" prop="theme_name">
                <el-input v-model="formData.theme_name" placeholder="我的主题" size="small">
                  <template #prefix><i class="fa fa-tag"></i></template>
                </el-input>
              </el-form-item>
            </el-col>
            <el-col :span="4">
              <el-form-item label="排序">
                <el-input-number v-model="formData.sort_order" :min="0" :max="9999" size="small" style="width: 100%"
                  controls-position="right" />
              </el-form-item>
            </el-col>
            <el-col :span="4">
              <el-form-item label="状态">
                <el-switch v-model="formData.is_active" :active-value="1" :inactive-value="0" size="small" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="描述" style="margin-bottom: 12px;">
            <el-input v-model="formData.description" type="textarea" :rows="2" placeholder="主题描述..." size="small" />
          </el-form-item>

          <!-- 颜色配置区 -->
          <div class="color-section">
            <div class="section-header">
              <span class="title"><i class="fa fa-palette"></i> 主题配色</span>
              <div class="actions">
                <el-button type="primary" size="small" @click="handleShowPresets">
                  <i class="fa fa-swatchbook"></i> 预设(170)
                </el-button>
                <el-button type="success" size="small" @click="handleRandomPreset">
                  <i class="fa fa-random"></i> 随机
                </el-button>
              </div>
            </div>

            <el-row :gutter="10" class="color-row">
              <el-col :span="12" v-for="(label, key) in colorLabels" :key="key">
                <div class="color-item">
                  <div class="color-display" :style="{ background: formData.config_data.colors[key] }">
                    <span class="label">{{ label }}</span>
                  </div>
                  <div class="color-input">
                    <el-color-picker v-model="formData.config_data.colors[key]" size="small" />
                    <el-input v-model="formData.config_data.colors[key]" size="small" />
                  </div>
                </div>
              </el-col>
            </el-row>
          </div>

          <!-- 实时预览 -->
          <div class="preview-section">
            <div class="preview-header">
              <span><i class="fa fa-eye"></i> 实时预览</span>
            </div>
            <div class="preview-box" :style="previewStyle">
              <div class="p-header">
                <span>应用预览</span>
                <div class="dots">
                  <span class="d"></span><span class="d"></span><span class="d"></span>
                </div>
              </div>
              <div class="p-body">
                <div class="p-sidebar">
                  <div class="item active"><i class="fa fa-home"></i> 首页</div>
                  <div class="item"><i class="fa fa-chart-bar"></i> 数据</div>
                  <div class="item"><i class="fa fa-cog"></i> 设置</div>
                </div>
                <div class="p-main">
                  <div class="card">
                    <h4>示例卡片</h4>
                    <p>这是示例文本，展示主题配色效果。</p>
                    <div class="tags">
                      <span class="t primary">主要</span>
                      <span class="t success">成功</span>
                      <span class="t warning">警告</span>
                      <span class="t danger">危险</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-form>
      </div>

      <template #footer>
        <div class="footer">
          <el-button @click="handleClose" size="small">取消</el-button>
          <el-button type="primary" :loading="submitLoading" @click="handleSubmit" size="small">
            <i class="fa fa-check"></i> {{ isEdit ? '保存' : '创建' }}
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 预设选择器 -->
    <PresetSelector v-model:visible="presetSelectorVisible" @select="handlePresetSelect" />
  </div>
</template>

<script lang="ts">
export default {
  name: 'ThemeEditorCompact'
}
</script>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { type FormInstance, type FormRules } from 'element-plus'
import { createTheme, updateTheme, getDefaultThemeConfig, type ThemeConfig, type ThemeCreateParams } from '@/api/theme'
import { message } from '@/utils/message'
import { getRandomPreset, type ThemePreset } from '../presets'
import PresetSelector from './PresetSelector.vue'

interface Props {
  visible?: boolean
  themeData?: ThemeConfig | null
  isEdit?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  themeData: null,
  isEdit: false
})

const emit = defineEmits<{
  'update:visible': [value: boolean]
  success: []
}>()

const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const formRef = ref<FormInstance>()
const submitLoading = ref(false)
const presetSelectorVisible = ref(false)

const colorLabels = {
  primary: '主色',
  secondary: '辅助',
  success: '成功',
  warning: '警告',
  danger: '危险',
  info: '信息'
}

const formData = reactive<ThemeCreateParams>({
  theme_key: '',
  theme_name: '',
  description: '',
  preview_image: '',
  config_data: getDefaultThemeConfig(),
  is_system: 0,
  is_active: 1,
  sort_order: 0
})

const formRules: FormRules = {
  theme_key: [
    { required: true, message: '请输入主题键名', trigger: 'blur' },
    { pattern: /^[a-zA-Z0-9_]+$/, message: '只能包含字母、数字和下划线', trigger: 'blur' }
  ],
  theme_name: [
    { required: true, message: '请输入主题名称', trigger: 'blur' }
  ]
}

const previewStyle = computed(() => {
  const colors = formData.config_data.colors
  return {
    '--p-primary': colors.primary,
    '--p-success': colors.success,
    '--p-warning': colors.warning,
    '--p-danger': colors.danger
  }
})

watch(() => props.themeData, (newData) => {
  if (newData && props.isEdit) {
    Object.assign(formData, {
      theme_key: newData.theme_key,
      theme_name: newData.theme_name,
      description: newData.description || '',
      preview_image: newData.preview_image || '',
      config_data: { ...newData.config_data },
      is_system: newData.is_system,
      is_active: newData.is_active,
      sort_order: newData.sort_order
    })
  } else if (!props.isEdit) {
    Object.assign(formData, {
      theme_key: '',
      theme_name: '',
      description: '',
      preview_image: '',
      config_data: getDefaultThemeConfig(),
      is_system: 0,
      is_active: 1,
      sort_order: 0
    })
  }
}, { immediate: true })

const handlePreview = () => {
  message('预览已刷新', { type: 'success' })
}

const handleSubmit = async () => {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
    submitLoading.value = true

    let response: any
    if (props.isEdit && props.themeData) {
      response = await updateTheme(props.themeData.id, formData)
    } else {
      response = await createTheme(formData)
    }

    if (response.code === 200) {
      message(props.isEdit ? '更新成功' : '创建成功', { type: 'success' })
      emit('success')
      handleClose()
    } else {
      message(response.msg, { type: 'error' })
    }
  } catch (error) {
    console.error('提交失败:', error)
  } finally {
    submitLoading.value = false
  }
}

const handleShowPresets = () => {
  presetSelectorVisible.value = true
}

const handlePresetSelect = (preset: ThemePreset) => {
  applyPreset(preset)
  message(`已应用预设：${preset.name}`, { type: 'success' })
}

const handleRandomPreset = () => {
  const randomPreset = getRandomPreset()
  applyPreset(randomPreset)
  message(`已应用随机预设：${randomPreset.name}`, { type: 'success' })
}

const applyPreset = (preset: ThemePreset) => {
  formData.config_data.colors = { ...preset.colors }
}

const handleClose = () => {
  formRef.value?.resetFields()
  dialogVisible.value = false
}
</script>

<style lang="scss" scoped>
:deep(.theme-editor-compact) {
  .el-dialog__header {
    padding: 12px 16px;
    border-bottom: 1px solid var(--el-border-color-lighter);

    .el-dialog__title {
      font-size: 14px;
      font-weight: 600;
    }
  }

  .el-dialog__body {
    padding: 0;
  }
}

.editor-container {
  padding: 16px;

  .compact-form {
    :deep(.el-form-item) {
      margin-bottom: 12px;
    }

    :deep(.el-form-item__label) {
      font-size: 11px;
      padding-bottom: 4px;
      margin-bottom: 0;
    }

    :deep(.el-input__inner) {
      font-size: 12px;
    }

    :deep(.el-textarea__inner) {
      font-size: 12px;
    }

    // 基本信息区
    .info-section {
      padding-bottom: 12px;
      border-bottom: 1px solid var(--el-border-color-lighter);
      margin-bottom: 12px;
    }

    // 颜色配置区
    .color-section {
      .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        padding: 8px 10px;
        background: var(--el-fill-color-extra-light);
        border-radius: 4px;

        .title {
          font-size: 13px;
          font-weight: 600;
          color: var(--el-text-color-primary);

          i {
            margin-right: 4px;
            color: var(--el-color-primary);
          }
        }

        .actions {
          display: flex;
          gap: 6px;

          i {
            margin-right: 3px;
            font-size: 11px;
          }
        }
      }

      .color-row {
        margin-bottom: 12px;

        .color-item {
          border: 1px solid var(--el-border-color-lighter);
          border-radius: 4px;
          overflow: hidden;
          transition: all 0.2s ease;

          &:hover {
            border-color: var(--el-color-primary-light-7);
          }

          .color-display {
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;

            &::before {
              content: '';
              position: absolute;
              inset: 0;
              background: linear-gradient(135deg, transparent, rgba(0, 0, 0, 0.1));
            }

            .label {
              position: relative;
              z-index: 1;
              font-size: 11px;
              font-weight: 600;
              color: white;
              text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            }
          }

          .color-input {
            padding: 6px;
            background: var(--el-bg-color);
            display: flex;
            align-items: center;
            gap: 6px;

            :deep(.el-input__inner) {
              font-family: 'Courier New', monospace;
              font-size: 10px;
            }
          }
        }
      }
    }

    // 预览区
    .preview-section {
      border-top: 1px solid var(--el-border-color-lighter);
      padding-top: 12px;

      .preview-header {
        padding: 6px 10px;
        background: var(--el-fill-color-light);
        border-radius: 4px 4px 0 0;
        font-size: 12px;
        font-weight: 600;

        i {
          margin-right: 4px;
          color: var(--el-color-primary);
        }
      }

      .preview-box {
        border: 1px solid var(--el-border-color-light);
        border-radius: 0 0 4px 4px;
        overflow: hidden;

        .p-header {
          height: 30px;
          background: var(--p-primary);
          color: white;
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 0 10px;
          font-size: 11px;

          .dots {
            display: flex;
            gap: 3px;

            .d {
              width: 5px;
              height: 5px;
              border-radius: 50%;
              background: rgba(255, 255, 255, 0.6);
            }
          }
        }

        .p-body {
          display: flex;
          height: 140px;
          background: var(--el-bg-color-page);

          .p-sidebar {
            width: 70px;
            background: var(--el-fill-color-light);
            padding: 8px 6px;

            .item {
              padding: 6px;
              margin-bottom: 3px;
              border-radius: 3px;
              font-size: 10px;
              cursor: pointer;
              transition: all 0.2s;
              display: flex;
              align-items: center;
              gap: 4px;

              &.active {
                background: var(--p-primary);
                color: white;
              }

              i {
                font-size: 9px;
              }
            }
          }

          .p-main {
            flex: 1;
            padding: 10px;

            .card {
              padding: 10px;
              background: var(--el-bg-color);
              border-radius: 4px;
              border: 1px solid var(--el-border-color-lighter);

              h4 {
                margin: 0 0 6px 0;
                font-size: 12px;
                color: var(--p-primary);
              }

              p {
                margin: 0 0 8px 0;
                font-size: 10px;
                line-height: 1.5;
                color: var(--el-text-color-regular);
              }

              .tags {
                display: flex;
                gap: 4px;

                .t {
                  padding: 3px 8px;
                  border-radius: 3px;
                  font-size: 9px;
                  color: white;

                  &.primary {
                    background: var(--p-primary);
                  }

                  &.success {
                    background: var(--p-success);
                  }

                  &.warning {
                    background: var(--p-warning);
                  }

                  &.danger {
                    background: var(--p-danger);
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}

.footer {
  padding: 10px 16px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  border-top: 1px solid var(--el-border-color-lighter);

  i {
    margin-right: 3px;
  }
}

@media (max-width: 768px) {
  .editor-container .compact-form .color-section .color-row {
    .el-col {
      margin-bottom: 10px;
    }
  }
}
</style>