<template>
  <div class="order-form">
    <el-form ref="formRef" :model="formData" :rules="rules" label-position="top" label-width="auto" class="form"
      size="default">
      <el-row :gutter="20">
        <el-col :span="24">
          <el-form-item label="订单号" prop="order_no">
            <el-input v-model="formData.order_no" placeholder="请输入订单号" :disabled="!!orderId" />
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="用户ID" prop="user_id">
            <el-input v-model.number="formData.user_id" placeholder="请输入用户ID" />
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="用户名称" prop="user_name">
            <el-input v-model="formData.user_name" placeholder="请输入用户名称" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="订单金额" prop="order_amount">
            <el-input-number v-model="formData.order_amount" :precision="2" :step="10" :min="0" style="width: 100%" />
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="商品名称" prop="product">
            <el-input v-model="formData.product" placeholder="请输入商品名称" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="订单状态" prop="order_status">
            <el-select v-model="formData.order_status" placeholder="请选择订单状态" style="width: 100%">
              <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="支付方式" prop="payment_method">
            <el-select v-model="formData.payment_method" placeholder="请选择支付方式" style="width: 100%">
              <el-option v-for="item in paymentOptions" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="备注" prop="remark">
        <el-input v-model="formData.remark" placeholder="请输入备注" type="textarea" rows="3" />
      </el-form-item>

      <el-form-item class="form-buttons">
        <el-button type="primary" @click="submitForm">提交</el-button>
        <el-button @click="resetForm">重置</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "OrderForm"
});

import { ref, reactive, onMounted, computed } from "vue";
import { ElMessage, FormInstance, FormRules } from "element-plus";
import { useWindowSize } from "@/hooks/useWindowSize";
import {
  getOrderDetail,
  createOrder,
  updateOrder,
  OrderItem,
  getOrderStatusOptions,
  getPaymentMethodOptions
} from "@/api/order";

// 响应式设计
const { width } = useWindowSize();
const isMobile = computed(() => width.value < 768);

// 定义props
const props = defineProps({
  orderId: {
    type: Number,
    default: null
  }
});

// 定义事件
const emit = defineEmits(["submit-success"]);

// 表单ref
const formRef = ref<FormInstance>();

// 订单状态选项
const statusOptions = getOrderStatusOptions();

// 支付方式选项 - 已在API中移除银行卡选项
const paymentOptions = getPaymentMethodOptions();

// 表单数据
const formData = reactive<Partial<OrderItem>>({
  order_no: "",
  user_id: undefined,
  user_name: "",
  order_amount: 0,
  order_status: "unpaid",
  payment_method: "wechat",
  product: "",
  remark: ""
});

// 表单验证规则
const rules = reactive<FormRules>({
  order_no: [
    { required: true, message: "请输入订单号", trigger: "blur" },
    { min: 3, max: 50, message: "长度在 3 到 50 个字符", trigger: "blur" }
  ],
  user_id: [
    { required: true, message: "请输入用户ID", trigger: "blur" },
    { type: "number", message: "用户ID必须为数字", trigger: "blur" }
  ],
  order_amount: [
    { required: true, message: "请输入订单金额", trigger: "blur" }
  ],
  order_status: [
    { required: true, message: "请选择订单状态", trigger: "change" }
  ],
  payment_method: [
    { required: true, message: "请选择支付方式", trigger: "change" }
  ],
  product: [
    { required: true, message: "请输入商品名称", trigger: "blur" }
  ]
});

// 提交表单
const submitForm = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid, fields) => {
    if (valid) {
      try {
        let response;
        if (props.orderId) {
          // 更新订单
          response = await updateOrder(props.orderId, formData);
        } else {
          // 创建订单
          response = await createOrder(formData as any);
        }

        if (response.code === 200) {
          ElMessage.success(props.orderId ? "更新成功" : "创建成功");
          emit("submit-success");
        } else {
          ElMessage.error(response.message || (props.orderId ? "更新失败" : "创建失败"));
        }
      } catch (error) {
        console.error("提交表单出错:", error);
        ElMessage.error(props.orderId ? "更新失败" : "创建失败");
      }
    } else {
      console.log("验证失败", fields);
    }
  });
};

// 重置表单
const resetForm = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
};

// 获取订单详情
const fetchOrderDetail = async () => {
  if (!props.orderId) return;

  try {
    const response = await getOrderDetail(props.orderId);

    if (response.code === 200) {
      const orderData = response.data;

      // 更新表单数据 - 修复类型错误
      Object.assign(formData, orderData);
    } else {
      ElMessage.error(response.message || "获取订单详情失败");
    }
  } catch (error) {
    console.error("获取订单详情出错:", error);
    ElMessage.error("获取订单详情失败");
  }
};

// 组件挂载时获取订单详情
onMounted(() => {
  if (props.orderId) {
    fetchOrderDetail();
  } else {
    // 生成随机订单号
    formData.order_no = `ORD${new Date().getTime()}${Math.floor(Math.random() * 1000)}`;
  }
});
</script>

<style lang="scss" scoped>
.order-form {
  height: 100%;
  padding: 20px;
  display: flex;
  flex-direction: column;
  background-color: #f5f7fa;
  overflow-y: auto;

  .form {
    width: 100%;
    flex: 1;
    background-color: #fff;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.05);

    :deep(.el-form-item__label) {
      font-weight: 500;
      color: #303133;
      padding-bottom: 8px;
      line-height: 1;
    }

    :deep(.el-input__wrapper),
    :deep(.el-textarea__inner),
    :deep(.el-select) {
      box-shadow: 0 0 0 1px #dcdfe6 inset;
      border-radius: 4px;
      transition: all 0.2s;

      &:hover {
        box-shadow: 0 0 0 1px var(--el-color-primary) inset;
      }

      &.is-focus {
        box-shadow: 0 0 0 1px var(--el-color-primary) inset;
      }
    }

    :deep(.el-input-number) {
      width: 100%;
    }

    :deep(.el-input-number__wrapper) {
      box-shadow: 0 0 0 1px #dcdfe6 inset;
      transition: all 0.2s;

      &:hover {
        box-shadow: 0 0 0 1px var(--el-color-primary) inset;
      }
    }

    :deep(.el-select) {
      width: 100%;
    }

    :deep(.el-form-item) {
      margin-bottom: 22px;
    }

    .form-buttons {
      display: flex;
      justify-content: center;
      margin-top: 32px;
      margin-bottom: 0;

      .el-button {
        padding: 12px 24px;
        font-size: 14px;
        min-width: 100px;
        margin: 0 10px;
        border-radius: 4px;

        &.el-button--primary {
          background-color: var(--el-color-primary);
        }
      }
    }
  }
}
</style>