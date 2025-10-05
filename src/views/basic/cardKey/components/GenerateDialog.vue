<!--
  卡密生成对话框组件
  
  功能：
  - 选择卡密类型（预设+历史+自定义）
  - 设置生成数量
  - 设置会员时长（预设选项+自定义）
  - 设置兑换期限（卡密可用截止时间）
  - 可选价格输入
  - 备注信息
  - 表单验证
  
  @author AI Assistant
  @date 2025-10-01
-->
<template>
  <el-dialog
    v-model="dialogVisible"
    title="生成卡密"
    width="580px"
    :close-on-click-modal="false"
    @close="handleClose"
    class="generate-dialog"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="85px"
      class="generate-form"
    >
      <!-- 卡密类型 -->
      <el-form-item label="卡密类型" prop="type_id">
        <el-select
          v-model="form.type_id"
          placeholder="选择类型"
          filterable
          @change="handleTypeChange"
          class="type-select"
        >
          <el-option
            v-for="type in cardTypes"
            :key="type.id"
            :label="type.type_name"
            :value="type.id"
          >
            <div class="type-option-content">
              <div class="type-name">{{ type.type_name }}</div>
              <div class="type-description" v-if="type.description">{{ type.description }}</div>
            </div>
          </el-option>
        </el-select>
      </el-form-item>

      <!-- 生成数量 -->
      <el-form-item label="生成数量" prop="count">
        <el-input-number
          v-model="form.count"
          :min="1"
          :max="10000"
          :step="1"
          style="width: 100%"
        />
        <div class="form-tip">单次最多生成10000个卡密</div>
      </el-form-item>

      <!-- 类型配置信息（选择类型后显示） -->
      <el-form-item v-if="selectedType">
        <div class="type-info-card">
          <div class="type-info-row" v-if="selectedType.price !== null">
            <span class="info-label">价格</span>
            <span class="info-value price-value">￥{{ selectedType.price }}</span>
          </div>
          <div class="type-info-row" v-if="selectedType.membership_duration !== null">
            <span class="info-label">会员时长</span>
            <span class="info-value duration-value">{{ formatDuration(selectedType.membership_duration) }}</span>
          </div>
          <div class="type-info-row" v-if="selectedType.available_days !== null">
            <span class="info-label">有效期</span>
            <span class="info-value days-value">{{ selectedType.available_days }}天</span>
          </div>
          <div class="type-info-empty" v-if="!selectedType.price && selectedType.membership_duration === null && !selectedType.available_days">
            <IconifyIconOnline icon="ep:info-filled" />
            <span>此类型无需配置额外字段</span>
          </div>
        </div>
      </el-form-item>

      <!-- 兑换期限（可选，支持覆盖） -->
      <el-form-item label="兑换期限" prop="expire_time">
        <div class="form-item-content">
          <div class="form-tip" v-if="selectedType?.available_days">
            该类型默认在<strong>{{ selectedType.available_days }}天</strong>内可兑换，可以单独设置覆盖
          </div>
          <div class="form-tip" v-else>
            该类型默认<strong>永久可兑换</strong>，可以单独设置覆盖
          </div>
          <div class="button-group">
            <div 
              v-for="option in availableOptions" 
              :key="option.value"
              :class="['option-btn', { active: availableType === option.value }]"
              @click="selectAvailable(option.value)"
            >
              {{ option.label }}
            </div>
          </div>
        </div>
        <el-date-picker
          v-if="availableType === 'custom'"
          v-model="form.expire_time"
          type="datetime"
          placeholder="选择失效时间"
          format="YYYY-MM-DD HH:mm:ss"
          value-format="YYYY-MM-DD HH:mm:ss"
          class="custom-input"
        />
      </el-form-item>

      <!-- 备注 -->
      <el-form-item label="备注" prop="remark">
        <el-input
          v-model="form.remark"
          type="textarea"
          :rows="3"
          placeholder="输入备注信息（可选）"
          maxlength="255"
          show-word-limit
        />
      </el-form-item>

      <!-- 生成预览 -->
      <el-form-item label="生成预览">
        <div class="preview-card">
          <IconifyIconOnline icon="ep:tickets" class="preview-icon" />
          <div class="preview-text">{{ previewText }}</div>
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose" size="default">取消</el-button>
        <el-button type="primary" :loading="loading" @click="handleSubmit" size="default">
          {{ loading ? "生成中..." : "确定生成" }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import {
  batchGenerateCardKey,
  type GenerateParams
} from "@/api/cardKey";
import {
  getEnabledCardTypes,
  type CardType
} from "@/api/cardType";
import { message } from "@/utils/message";

defineOptions({
  name: "GenerateDialog"
});

// Props
interface Props {
  visible: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  visible: false
});

// Emits
const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
  (e: "success"): void;
}>();

// 响应式数据
const dialogVisible = computed({
  get: () => props.visible,
  set: (val) => emit("update:visible", val)
});

const formRef = ref<FormInstance>();
const loading = ref(false);
const availableType = ref("default"); // 兑换期限类型
const cardTypes = ref<CardType[]>([]); // 卡密类型列表
const selectedType = ref<CardType | null>(null); // 当前选中的类型

// 兑换期限选项
const availableOptions = [
  { label: '使用默认', value: 'default' },
  { label: '7天内', value: '7' },
  { label: '30天内', value: '30' },
  { label: '90天内', value: '90' },
  { label: '永久可用', value: 'forever' },
  { label: '自定义', value: 'custom' }
];

// 表单数据
const form = reactive<any>({
  type_id: null,
  count: 5,
  expire_time: undefined,
  remark: ""
});

// 表单验证规则
const rules = reactive<FormRules>({
  type_id: [
    { required: true, message: "请选择卡密类型", trigger: "change" }
  ],
  count: [
    { required: true, message: "请输入生成数量", trigger: "blur" },
    { type: "number", min: 1, max: 10000, message: "数量必须在1-10000之间", trigger: "blur" }
  ]
});

// 计算属性：是否显示会员时长
const showMembershipDuration = computed(() => {
  return selectedType.value && selectedType.value.membership_duration !== null;
});

// 计算属性：是否显示价格
const showPrice = computed(() => {
  return selectedType.value && selectedType.value.price !== null;
});

// 生成预览文本
const previewText = computed(() => {
  if (!selectedType.value) {
    return "请先选择卡密类型";
  }
  
  const type = selectedType.value.type_name || "未选择";
  const count = form.count || 0;
  
  let text = `将生成 ${count} 个【${type}】卡密`;
  
  if (selectedType.value.membership_duration !== null) {
    const durationText = formatDuration(selectedType.value.membership_duration);
    text += `，赠送${durationText}会员`;
  }
  
  if (selectedType.value.price !== null) {
    text += `，单价￥${selectedType.value.price}`;
  }
  
  if (form.expire_time) {
    text += `，必须在${form.expire_time}前兑换`;
  } else if (selectedType.value.available_days) {
    text += `，${selectedType.value.available_days}天内可兑换`;
  } else {
    text += `，永久可用`;
  }
  
  return text;
});

/**
 * 类型变化处理
 */
const handleTypeChange = (typeId: number) => {
  selectedType.value = cardTypes.value.find(t => t.id === typeId) || null;
  // 重置兑换期限
  availableType.value = "default";
  form.expire_time = undefined;
};

/**
 * 格式化时长
 */
const formatDuration = (minutes: number): string => {
  if (minutes === 0) {
    return "永久";
  } else if (minutes < 60) {
    return `${minutes}分钟`;
  } else if (minutes < 1440) {
    return `${Math.floor(minutes / 60)}小时`;
  } else {
    return `${Math.floor(minutes / 1440)}天`;
  }
};

/**
 * 选择兑换期限
 */
const selectAvailable = (value: string) => {
  availableType.value = value;
  handleAvailableTypeChange(value);
};

/**
 * 兑换期限类型变化
 */
const handleAvailableTypeChange = (value: string) => {
  if (value === "default") {
    form.expire_time = undefined;
  } else if (value === "forever") {
    form.expire_time = null; // 显式设置null表示永久
  } else if (value === "custom") {
    // 自定义，由用户选择日期
    form.expire_time = undefined;
  } else {
    // 计算截止时间
    const days = parseInt(value);
    const deadline = new Date();
    deadline.setDate(deadline.getDate() + days);
    form.expire_time = deadline.toISOString().slice(0, 19).replace('T', ' ');
  }
};

/**
 * 获取类型列表
 */
const fetchCardTypes = async () => {
  try {
    const response = await getEnabledCardTypes();
    if (response.code === 200) {
      cardTypes.value = response.data || [];
    }
  } catch (error) {
    console.error("获取类型列表失败", error);
  }
};

/**
 * 提交表单
 */
const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid) => {
    if (!valid) return;

    loading.value = true;
    try {
      const response = await batchGenerateCardKey(form);

      if (response.code === 200) {
        message(`成功生成${response.data.total}个卡密`, { type: "success" });
        emit("success");
        handleClose();
      } else {
        message(response.message || "生成失败", { type: "error" });
      }
    } catch (error) {
      message("生成失败", { type: "error" });
    } finally {
      loading.value = false;
    }
  });
};

/**
 * 关闭对话框
 */
const handleClose = () => {
  formRef.value?.resetFields();
  availableType.value = "default";
  selectedType.value = null;
  form.type_id = null;
  form.count = 5;
  form.expire_time = undefined;
  form.remark = "";
  dialogVisible.value = false;
};

// 监听对话框打开
watch(
  () => props.visible,
  (val) => {
    if (val) {
      fetchCardTypes();
    }
  }
);

// 组件挂载
onMounted(() => {
  fetchCardTypes();
});
</script>

<style scoped lang="scss">
.generate-dialog {
  :deep(.el-dialog__header) {
    padding: 16px 20px 12px;
    border-bottom: 1px solid #e4e7ed;
  }

  :deep(.el-dialog__body) {
    padding: 16px 20px;
    max-height: 65vh;
    overflow-y: auto;
  }

  :deep(.el-dialog__footer) {
    padding: 12px 20px 16px;
    border-top: 1px solid #e4e7ed;
  }
}

.generate-form {
  .form-item-content {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 100%;
  }

  .form-tip {
    font-size: 12px;
    color: #909399;
    line-height: 1.4;
  }

  .button-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;

    .option-btn {
      padding: 3px 10px;
      border: 1px solid #dcdfe6;
      border-radius: 3px;
      background: #fff;
      color: #606266;
      font-size: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      user-select: none;
      line-height: 1.5;

      &:hover {
        border-color: #409eff;
        color: #409eff;
        background: #ecf5ff;
      }

      &.active {
        background: #409eff;
        border-color: #409eff;
        color: #fff;
      }
    }
  }

  :deep(.el-input-number) {
    width: 100%;
  }

  :deep(.el-form-item__label) {
    font-weight: 500;
    color: #606266;
  }

  :deep(.el-form-item) {
    margin-bottom: 16px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .custom-input {
    width: 100%;
    margin-top: 8px;
  }

  // 类型选择下拉框样式
  .type-select {
    :deep(.el-select-dropdown__item) {
      height: auto;
      padding: 8px 12px;
    }
  }

  .type-option-content {
    display: flex;
    flex-direction: column;
    gap: 4px;

    .type-name {
      font-size: 14px;
      color: #303133;
      font-weight: 500;
    }

    .type-description {
      font-size: 12px;
      color: #909399;
      line-height: 1.4;
    }
  }

  // 类型配置信息卡片 - 精致紧凑
  .type-info-card {
    width: 100%;
    padding: 10px 12px;
    background: linear-gradient(135deg, #fafbfc 0%, #f5f7fa 100%);
    border: 1px solid #e4e7ed;
    border-radius: 6px;
    display: inline-flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;

    .type-info-row {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      background: white;
      border-radius: 4px;
      border: 1px solid #e8eaed;
      gap: 6px;
      transition: all 0.2s;

      &:hover {
        border-color: #d0d3d9;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      }

      .info-label {
        font-size: 12px;
        color: #909399;
        display: flex;
        align-items: center;
        gap: 3px;
        
        &:before {
          content: '•';
          color: #409eff;
          font-size: 14px;
        }
      }

      .info-value {
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.3px;
        
        &.price-value {
          color: #ff6b6b;
          font-size: 14px;
        }
        
        &.duration-value {
          color: #4a90e2;
        }
        
        &.days-value {
          color: #8b7be8;
        }
      }
    }

    .type-info-empty {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: #909399;
      justify-content: center;
      padding: 4px 0;
      width: 100%;
    }
  }

  // 生成预览卡片
  .preview-card {
    width: 100%;
    padding: 14px 16px;
    background: linear-gradient(135deg, #fff9f0 0%, #fff4e6 100%);
    border: 1px solid #ffe7ba;
    border-radius: 8px;
    display: flex;
    align-items: flex-start;
    gap: 12px;

    .preview-icon {
      color: #fa8c16;
      font-size: 20px;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .preview-text {
      flex: 1;
      font-size: 13px;
      color: #595959;
      line-height: 1.7;
    }
  }
}

// 暗黑模式适配
html.dark {
  .generate-form {
    .form-tip {
      color: var(--el-text-color-secondary);
    }
  }
}
</style>

