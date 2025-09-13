<template>
  <el-form ref="formRef" :model="form" :rules="rules" label-width="100px" size="small" class="payment-channel-form">
    <el-row :gutter="16">
      <el-col :span="12">
        <el-form-item label="渠道名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入渠道名称" clearable />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="渠道代码" prop="code">
          <el-input v-model="form.code" placeholder="请输入渠道代码" clearable />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="16">
      <el-col :span="12">
        <el-form-item label="支付类型" prop="type">
          <el-select v-model="form.type" placeholder="请选择类型" style="width: 100%">
            <el-option label="传统支付" :value="1" />
            <el-option label="加密货币" :value="2" />
            <el-option label="数字钱包" :value="3" />
          </el-select>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="图标" prop="icon">
          <el-input v-model="form.icon" placeholder="图标类名" clearable>
            <template #prepend>
              <FontIcon v-if="form.icon" :icon="form.icon" />
              <FontIcon v-else icon="fas fa-credit-card" />
            </template>
          </el-input>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="16">
      <el-col :span="12">
        <el-form-item label="货币代码" prop="currency_code">
          <el-input v-model="form.currency_code" placeholder="如：CNY、USD" clearable />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="货币符号" prop="currency_symbol">
          <el-input v-model="form.currency_symbol" placeholder="如：¥、$" clearable />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="16">
      <el-col :span="12">
        <el-form-item label="加密货币" prop="is_crypto">
          <el-radio-group v-model="form.is_crypto" size="small">
            <el-radio :value="0">否</el-radio>
            <el-radio :value="1">是</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="排序权重" prop="sort_order">
          <el-input-number v-model="form.sort_order" :min="0" :max="9999" style="width: 100%" />
        </el-form-item>
      </el-col>
    </el-row>

    <!-- 加密货币相关字段 -->
    <template v-if="form.is_crypto === 1">
      <el-row :gutter="16">
        <el-col :span="12">
          <el-form-item label="区块链网络" prop="network">
            <el-input v-model="form.network" placeholder="如：ETH、TRX" clearable />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="合约地址" prop="contract_address">
            <el-input v-model="form.contract_address" placeholder="智能合约地址" clearable />
          </el-form-item>
        </el-col>
      </el-row>
    </template>

    <el-row :gutter="16">
      <el-col :span="12">
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status" size="small">
            <el-radio :value="0">禁用</el-radio>
            <el-radio :value="1">启用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="设为默认" prop="is_default">
          <el-radio-group v-model="form.is_default" size="small">
            <el-radio :value="0">否</el-radio>
            <el-radio :value="1">是</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-col>
    </el-row>

    <el-form-item>
      <el-button type="primary" @click="handleSubmit" :loading="loading" size="small">
        {{ isEdit ? '更新' : '添加' }}
      </el-button>
      <el-button @click="handleReset" size="small">重置</el-button>
    </el-form-item>
  </el-form>
</template>

<script lang="ts">
import { defineComponent, ref, reactive, computed, watch } from "vue";
import { ElMessage, type FormInstance, type FormRules } from "element-plus";
import { addPaymentMethod, updatePaymentMethod, type PaymentMethodForm, type PaymentMethod } from "@/api/paymentMethod";
import { FontIcon } from "@/components/ReIcon";

export default defineComponent({
  name: "AddOrEdit",
  components: {
    FontIcon
  },
  props: {
    formData: {
      type: Object as () => PaymentMethod | null,
      default: null
    }
  },
  emits: ["submit-success"],
  setup(props, { emit }) {
    // 响应式数据
    const formRef = ref<FormInstance>();
    const loading = ref(false);

    // 判断是否为编辑模式
    const isEdit = computed(() => props.formData && props.formData.id);

    // 表单数据
    const form = reactive<PaymentMethodForm>({
      name: "",
      code: "",
      type: 1,
      icon: "",
      currency_code: "",
      currency_symbol: "",
      is_crypto: 0,
      network: "",
      contract_address: "",
      status: 1,
      sort_order: 100,
      is_default: 0
    });

    // 表单验证规则
    const rules: FormRules = {
      name: [
        { required: true, message: "请输入支付渠道名称", trigger: "blur" },
        { min: 2, max: 50, message: "长度在 2 到 50 个字符", trigger: "blur" }
      ],
      code: [
        { required: true, message: "请输入支付渠道代码", trigger: "blur" },
        { min: 2, max: 30, message: "长度在 2 到 30 个字符", trigger: "blur" },
        { pattern: /^[a-zA-Z0-9_-]+$/, message: "只能包含字母、数字、下划线和横线", trigger: "blur" }
      ],
      type: [
        { required: true, message: "请选择支付类型", trigger: "change" }
      ]
    };

    // 监听 formData 变化，初始化表单
    watch(() => props.formData, (newData) => {
      if (newData) {
        Object.assign(form, {
          name: newData.name,
          code: newData.code,
          type: newData.type,
          icon: newData.icon,
          currency_code: newData.currency_code,
          currency_symbol: newData.currency_symbol,
          is_crypto: newData.is_crypto,
          network: newData.network,
          contract_address: newData.contract_address,
          status: newData.status,
          sort_order: newData.sort_order,
          is_default: newData.is_default
        });
      }
    }, { immediate: true });

    // 提交表单
    const handleSubmit = async () => {
      if (!formRef.value) return;

      try {
        await formRef.value.validate();
        loading.value = true;

        if (isEdit.value) {
          await updatePaymentMethod(props.formData!.id, form);
          ElMessage.success("更新支付渠道成功");
        } else {
          await addPaymentMethod(form);
          ElMessage.success("添加支付渠道成功");
        }

        emit("submit-success");
      } catch (error) {
        console.error("操作失败:", error);
      } finally {
        loading.value = false;
      }
    };

    // 重置表单
    const handleReset = () => {
      if (!formRef.value) return;
      formRef.value.resetFields();
    };

    return {
      formRef,
      loading,
      isEdit,
      form,
      rules,
      handleSubmit,
      handleReset
    };
  }
});
</script>

<style scoped lang="scss">
.payment-channel-form {
  padding: 16px;

  .el-form-item {
    margin-bottom: 16px;

    :deep(.el-form-item__label) {
      font-size: 12px;
      font-weight: 600;
    }

    :deep(.el-input__inner) {
      font-size: 12px;
    }

    :deep(.el-select .el-input__inner) {
      font-size: 12px;
    }
  }

  .el-row {
    margin-bottom: 0;
  }

  .el-button {
    font-size: 12px;
  }
}
</style>
