<template>
  <el-dialog v-model="dialogVisible" title="系统预设主题 - 170套精选配色" width="900px" class="preset-dialog">
    <div class="preset-selector">
      <!-- 分类标签 -->
      <el-tabs v-model="activeCategory" class="category-tabs">
        <el-tab-pane v-for="(presets, category) in categorizedPresets" :key="category"
          :label="`${category} (${presets.length})`" :name="category">
          <div class="preset-grid">
            <div v-for="(preset, index) in presets" :key="index" class="preset-card" @click="handleSelect(preset)">
              <div class="card-colors">
                <div class="main-color" :style="{ background: preset.colors.primary }">
                  <span class="color-code">{{ preset.colors.primary }}</span>
                </div>
                <div class="sub-colors">
                  <span class="color-dot" :style="{ background: preset.colors.success }"></span>
                  <span class="color-dot" :style="{ background: preset.colors.warning }"></span>
                  <span class="color-dot" :style="{ background: preset.colors.danger }"></span>
                  <span class="color-dot" :style="{ background: preset.colors.info }"></span>
                </div>
              </div>
              <div class="card-info">
                <div class="preset-name">{{ preset.name }}</div>
                <div class="preset-desc">{{ preset.description }}</div>
              </div>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </el-dialog>
</template>

<script lang="ts">
export default {
  name: 'PresetSelector'
}
</script>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { getPresetsByCategory, type ThemePreset } from '../presets'

interface Props {
  visible?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  visible: false
})

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'select': [preset: ThemePreset]
}>()

const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const activeCategory = ref('经典系列')
const categorizedPresets = getPresetsByCategory()

const handleSelect = (preset: ThemePreset) => {
  emit('select', preset)
  dialogVisible.value = false
}
</script>

<style lang="scss" scoped>
:deep(.preset-dialog) {
  .el-dialog__body {
    padding: 0;
  }
}

.preset-selector {
  .category-tabs {
    :deep(.el-tabs__header) {
      margin: 0;
      padding: 0 20px;
      background: var(--el-fill-color-extra-light);
    }

    :deep(.el-tabs__item) {
      font-size: 12px;
      padding: 12px 16px;
    }

    :deep(.el-tabs__content) {
      padding: 16px 20px;
    }

    .preset-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      max-height: 500px;
      overflow-y: auto;
      padding-right: 4px;

      &::-webkit-scrollbar {
        width: 6px;
      }

      &::-webkit-scrollbar-thumb {
        background: var(--el-color-primary-light-7);
        border-radius: 3px;
      }

      .preset-card {
        border: 1px solid var(--el-border-color-lighter);
        border-radius: 4px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s ease;

        &:hover {
          border-color: var(--el-color-primary);
          transform: translateY(-2px);
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .card-colors {
          .main-color {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;

            &::before {
              content: '';
              position: absolute;
              top: 0;
              left: 0;
              right: 0;
              bottom: 0;
              background: linear-gradient(135deg, transparent 0%, rgba(0, 0, 0, 0.1) 100%);
            }

            .color-code {
              position: relative;
              z-index: 1;
              font-size: 9px;
              font-weight: 700;
              color: white;
              font-family: 'Courier New', monospace;
              text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
              letter-spacing: 0.5px;
            }
          }

          .sub-colors {
            display: flex;
            gap: 1px;
            background: var(--el-border-color-lighter);

            .color-dot {
              flex: 1;
              height: 12px;
              transition: all 0.2s ease;

              &:hover {
                flex: 1.5;
              }
            }
          }
        }

        .card-info {
          padding: 8px;
          background: var(--el-bg-color);

          .preset-name {
            font-size: 11px;
            font-weight: 600;
            color: var(--el-text-color-primary);
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }

          .preset-desc {
            font-size: 10px;
            color: var(--el-text-color-secondary);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
        }
      }
    }
  }
}

@media (max-width: 1024px) {
  .preset-selector .category-tabs .preset-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .preset-selector .category-tabs .preset-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .preset-selector .category-tabs .preset-grid {
    grid-template-columns: 1fr;
  }
}
</style>