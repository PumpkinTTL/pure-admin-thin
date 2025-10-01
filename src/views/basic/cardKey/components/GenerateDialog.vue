<!--
  卡密生成对话框组件
  
  功能：
  - 选择卡密类型（预设+历史+自定义）
  - 设置生成数量
  - 设置有效时长（预设选项+自定义）
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
    width="600px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="100px"
      class="generate-form"
    >
      <!-- 卡密类型 -->
      <el-form-item label="卡密类型" prop="type">
        <el-select
          v-model="form.type"
          placeholder="选择或输入类型"
          filterable
          allow-create
          style="width: 100%"
        >
          <el-option-group label="常用类型">
            <el-option
              v-for="type in presetTypes"
              :key="type"
              :label="type"
              :value="type"
            />
          </el-option-group>
          <el-option-group label="历史类型" v-if="historyTypes.length > 0">
            <el-option
              v-for="type in historyTypes"
              :key="type"
              :label="type"
              :value="type"
            />
          </el-option-group>
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

      <!-- 有效时长 -->
      <el-form-item label="有效时长" prop="valid_minutes">
        <el-radio-group v-model="validType" @change="handleValidTypeChange">
          <el-radio label="0">永久有效</el-radio>
          <el-radio label="60">1小时</el-radio>
          <el-radio label="1440">1天</el-radio>
          <el-radio label="10080">7天</el-radio>
          <el-radio label="43200">30天</el-radio>
          <el-radio label="custom">自定义</el-radio>
        </el-radio-group>
        <el-input-number
          v-if="validType === 'custom'"
          v-model="form.valid_minutes"
          :min="1"
          :max="5256000"
          :step="1"
          placeholder="输入分钟数"
          style="width: 100%; margin-top: 10px"
        >
          <template #append>分钟</template>
        </el-input-number>
        <div class="form-tip" v-if="validType === 'custom'">
          提示：100年 = 52,560,000分钟
        </div>
      </el-form-item>

      <!-- 价格（可选） -->
      <el-form-item label="价格" prop="price">
        <el-input-number
          v-model="form.price"
          :min="0"
          :max="999999.99"
          :precision="2"
          :step="1"
          placeholder="选填，适用于商品兑换码"
          style="width: 100%"
        >
          <template #prefix>¥</template>
        </el-input-number>
        <div class="form-tip">可选字段，不填表示无价格</div>
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
        <el-alert
          :title="previewText"
          type="info"
          :closable="false"
          show-icon
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="loading" @click="handleSubmit">
        <IconifyIconOnline icon="ep:check" v-if="!loading" />
        {{ loading ? "生成中..." : "确定生成" }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import {
  batchGenerateCardKey,
  getCardKeyTypes,
  CardKeyTypeOptions,
  formatValidMinutes,
  type GenerateParams
} from "@/api/cardKey";
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
const validType = ref("0"); // 有效时长类型
const presetTypes = ref(CardKeyTypeOptions); // 预设类型
const historyTypes = ref<string[]>([]); // 历史类型

// 表单数据
const form = reactive<GenerateParams>({
  type: "",
  count: 100,
  price: undefined,
  valid_minutes: 0,
  remark: ""
});

// 表单验证规则
const rules = reactive<FormRules>({
  type: [
    { required: true, message: "请选择或输入卡密类型", trigger: "blur" }
  ],
  count: [
    { required: true, message: "请输入生成数量", trigger: "blur" },
    { type: "number", min: 1, max: 10000, message: "数量必须在1-10000之间", trigger: "blur" }
  ],
  valid_minutes: [
    { required: true, message: "请选择有效时长", trigger: "change" }
  ]
});

// 生成预览文本
const previewText = computed(() => {
  const type = form.type || "未选择";
  const count = form.count || 0;
  const validText = formatValidMinutes(form.valid_minutes);
  const priceText = form.price ? `，单价¥${form.price}` : "";
  
  return `将生成 ${count} 个【${type}】卡密，有效期：${validText}${priceText}`;
});

/**
 * 有效时长类型变化
 */
const handleValidTypeChange = (value: string) => {
  if (value === "custom") {
    form.valid_minutes = 60; // 默认1小时
  } else {
    form.valid_minutes = parseInt(value);
  }
};

/**
 * 获取历史类型列表
 */
const fetchHistoryTypes = async () => {
  try {
    const response = await getCardKeyTypes();
    if (response.code === 200) {
      // 过滤掉预设类型
      historyTypes.value = (response.data || []).filter(
        (type: string) => !presetTypes.value.includes(type)
      );
    }
  } catch (error) {
    console.error("获取历史类型失败", error);
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
  validType.value = "0";
  form.type = "";
  form.count = 100;
  form.price = undefined;
  form.valid_minutes = 0;
  form.remark = "";
  dialogVisible.value = false;
};

// 监听对话框打开
watch(
  () => props.visible,
  (val) => {
    if (val) {
      fetchHistoryTypes();
    }
  }
);

// 组件挂载
onMounted(() => {
  fetchHistoryTypes();
});
</script>

<style scoped lang="scss">
.generate-form {
  .form-tip {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    margin-top: 5px;
  }

  :deep(.el-radio-group) {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  :deep(.el-input-number) {
    width: 100%;
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

