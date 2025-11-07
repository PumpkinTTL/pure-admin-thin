<template>
  <div class="donation-form">
    <el-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      label-position="top"
      label-width="auto"
      class="form"
      size="small"
    >
      <el-row :gutter="20">
        <el-col :span="24">
          <el-form-item label="捐赠渠道" prop="channel">
            <el-select
              v-model="formData.channel"
              placeholder="请选择捐赠渠道"
              style="width: 100%"
              @change="handleChannelChange"
            >
              <el-option
                v-for="(label, value) in channelOptions"
                :key="value"
                :label="label"
                :value="value"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="关联用户" prop="user_id">
            <el-input
              v-model.number="formData.user_id"
              placeholder="请输入用户ID（留空为匿名捐赠）"
              type="number"
              clearable
            />
            <div class="form-tip">
              💡 填写用户ID后，捐赠者信息将从用户表查询
            </div>
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="是否匿名" prop="is_anonymous">
            <el-switch
              v-model="formData.is_anonymous"
              :active-value="1"
              :inactive-value="0"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <!-- 匿名捐赠时的可选字段 -->
      <el-row v-if="!formData.user_id" :gutter="20">
        <el-col :span="12">
          <el-form-item label="捐赠者姓名（可选）" prop="donor_name">
            <el-input
              v-model="formData.donor_name"
              placeholder="匿名捐赠可不填"
            />
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="联系邮箱（可选）" prop="email">
            <el-input v-model="formData.email" placeholder="用于后续联系" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row v-if="!formData.user_id" :gutter="20">
        <el-col :span="12">
          <el-form-item label="唯一标识（可选）" prop="iden">
            <el-input v-model="formData.iden" placeholder="用于统计识别" />
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="联系方式（可选）" prop="donor_contact">
            <el-input
              v-model="formData.donor_contact"
              placeholder="邮箱或手机号"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <!-- 微信/支付宝字段 -->
      <template
        v-if="formData.channel === 'wechat' || formData.channel === 'alipay'"
      >
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="订单号" prop="order_no">
              <el-input
                v-model="formData.order_no"
                placeholder="请输入订单号"
              />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="交易流水号" prop="transaction_id">
              <el-input
                v-model="formData.transaction_id"
                placeholder="请输入交易流水号"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="捐赠金额" prop="amount">
              <el-input-number
                v-model="formData.amount"
                :precision="2"
                :step="10"
                :min="0"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="支付时间" prop="payment_time">
              <el-date-picker
                v-model="formData.payment_time"
                type="datetime"
                placeholder="选择支付时间"
                style="width: 100%"
                format="YYYY-MM-DD HH:mm:ss"
                value-format="YYYY-MM-DD HH:mm:ss"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </template>

      <!-- 加密货币字段 -->
      <template v-if="formData.channel === 'crypto'">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="加密货币类型" prop="crypto_type">
              <el-select
                v-model="formData.crypto_type"
                placeholder="请选择"
                style="width: 100%"
              >
                <el-option
                  v-for="item in cryptoTypeOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="区块链网络" prop="crypto_network">
              <el-select
                v-model="formData.crypto_network"
                placeholder="请选择"
                style="width: 100%"
              >
                <el-option
                  v-for="item in cryptoNetworkOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="24">
            <el-form-item label="交易哈希" prop="transaction_hash">
              <el-input
                v-model="formData.transaction_hash"
                placeholder="请输入交易哈希值"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="发送地址" prop="from_address">
              <el-input
                v-model="formData.from_address"
                placeholder="请输入发送地址"
              />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="接收地址" prop="to_address">
              <el-input
                v-model="formData.to_address"
                placeholder="请输入接收地址"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="捐赠金额" prop="amount">
              <el-input-number
                v-model="formData.amount"
                :precision="2"
                :step="10"
                :min="0"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="确认数" prop="confirmation_count">
              <el-input-number
                v-model="formData.confirmation_count"
                :min="0"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </template>

      <!-- 卡密兑换字段 -->
      <template v-if="formData.channel === 'cardkey'">
        <el-row :gutter="20">
          <el-col :span="24">
            <el-form-item label="卡密码" prop="card_key_code">
              <el-input
                v-model="formData.card_key_code"
                placeholder="请输入卡密码"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="卡密等值金额" prop="card_key_value">
              <el-input-number
                v-model="formData.card_key_value"
                :precision="2"
                :step="10"
                :min="0"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </template>

      <!-- 通用字段 -->
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="状态" prop="status">
            <el-select
              v-model="formData.status"
              placeholder="请选择状态"
              style="width: 100%"
            >
              <el-option
                v-for="(label, value) in statusOptions"
                :key="value"
                :label="label"
                :value="parseInt(value)"
              />
            </el-select>
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="是否匿名" prop="is_anonymous">
            <el-switch
              v-model="formData.is_anonymous"
              :active-value="1"
              :inactive-value="0"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="是否公开展示" prop="is_public">
            <el-switch
              v-model="formData.is_public"
              :active-value="1"
              :inactive-value="0"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="备注" prop="remark">
        <el-input
          v-model="formData.remark"
          placeholder="请输入备注"
          type="textarea"
          rows="3"
        />
      </el-form-item>

      <el-form-item label="管理员备注" prop="admin_remark">
        <el-input
          v-model="formData.admin_remark"
          placeholder="请输入管理员备注"
          type="textarea"
          rows="2"
        />
      </el-form-item>

      <el-form-item class="form-buttons">
        <el-button type="primary" :loading="submitting" @click="submitForm">
          提交
        </el-button>
        <el-button @click="resetForm">重置</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "DonationForm"
});

import { ref, reactive, onMounted } from "vue";
import { FormInstance, FormRules } from "element-plus";
import { message } from "@/utils/message";
import {
  getDonationDetail,
  addDonation,
  updateDonation,
  getChannelOptions,
  getStatusOptions,
  Donation,
  CryptoTypeOptions,
  CryptoNetworkOptions
} from "@/api/donation";

// 定义props
const props = defineProps({
  donationId: {
    type: Number,
    default: null
  }
});

// 定义事件
const emit = defineEmits(["submit-success"]);

// 表单ref
const formRef = ref<FormInstance>();

// 提交状态
const submitting = ref(false);

// 渠道选项
const channelOptions = ref<Record<string, string>>({});

// 状态选项
const statusOptions = ref<Record<string, string>>({});

// 加密货币类型选项
const cryptoTypeOptions = CryptoTypeOptions;

// 区块链网络选项
const cryptoNetworkOptions = CryptoNetworkOptions;

// 表单数据
const formData = reactive<Partial<Donation>>({
  channel: "wechat",
  donor_name: "",
  donor_contact: "",
  amount: 0,
  status: 0,
  is_anonymous: 0,
  is_public: 1,
  order_no: "",
  transaction_id: "",
  payment_time: "",
  crypto_type: "",
  crypto_network: "",
  transaction_hash: "",
  from_address: "",
  to_address: "",
  confirmation_count: 0,
  card_key_code: "",
  card_key_value: 0,
  remark: "",
  admin_remark: ""
});

// 表单验证规则
const rules = reactive<FormRules>({
  channel: [{ required: true, message: "请选择捐赠渠道", trigger: "change" }],
  amount: [{ required: false, message: "请输入捐赠金额", trigger: "blur" }],
  status: [{ required: true, message: "请选择状态", trigger: "change" }]
});

// 渠道改变
const handleChannelChange = () => {
  // 清空其他渠道的字段
  formData.order_no = "";
  formData.transaction_id = "";
  formData.payment_time = "";
  formData.crypto_type = "";
  formData.crypto_network = "";
  formData.transaction_hash = "";
  formData.from_address = "";
  formData.to_address = "";
  formData.confirmation_count = 0;
  formData.card_key_code = "";
  formData.card_key_value = 0;
};

// 加载选项数据
const loadOptions = async () => {
  try {
    const [channelRes, statusRes] = await Promise.all([
      getChannelOptions(),
      getStatusOptions()
    ]);

    if (channelRes.code === 200) {
      channelOptions.value = channelRes.data;
    }

    if (statusRes.code === 200) {
      statusOptions.value = statusRes.data;
    }
  } catch (error) {
    console.error("加载选项失败:", error);
  }
};

// 加载捐赠记录详情
const loadDonationDetail = async () => {
  if (!props.donationId) return;

  try {
    const res = await getDonationDetail(props.donationId);

    if (res.code === 200) {
      Object.assign(formData, res.data);
    } else {
      message(res.message || "加载失败", { type: "error" });
    }
  } catch (error) {
    console.error("加载捐赠记录详情失败:", error);
    message("加载捐赠记录详情失败", { type: "error" });
  }
};

// 提交表单
const submitForm = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async valid => {
    if (valid) {
      submitting.value = true;

      try {
        const res = props.donationId
          ? await updateDonation(props.donationId, formData)
          : await addDonation(formData);

        if (res.code === 200) {
          message(props.donationId ? "更新成功" : "添加成功", {
            type: "success"
          });
          emit("submit-success");
        } else {
          message(res.message || "操作失败", { type: "error" });
        }
      } catch (error) {
        console.error("提交失败:", error);
        message("提交失败", { type: "error" });
      } finally {
        submitting.value = false;
      }
    }
  });
};

// 重置表单
const resetForm = () => {
  formRef.value?.resetFields();
};

// 初始化
onMounted(() => {
  loadOptions();
  if (props.donationId) {
    loadDonationDetail();
  }
});
</script>

<style scoped lang="scss">
.donation-form {
  padding: 0;

  .form {
    :deep(.el-form-item__label) {
      margin-bottom: 6px;
      font-size: 13px;
      font-weight: 500;
      color: var(--el-text-color-regular);
    }

    :deep(.el-input__inner) {
      font-size: 13px;
    }

    :deep(.el-select) {
      font-size: 13px;
    }

    :deep(.el-input-number) {
      font-size: 13px;
    }

    .form-buttons {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      padding-top: 16px;
      margin-top: 20px;
      text-align: right;
      border-top: 1px solid var(--el-border-color-lighter);

      .el-button {
        min-width: 90px;
      }
    }

    .form-tip {
      margin-top: 4px;
      font-size: 12px;
      color: #909399;
    }
  }
}

@media (width <= 768px) {
  .donation-form {
    .form {
      .form-buttons {
        justify-content: center;

        .el-button {
          width: 45%;
        }
      }
    }
  }
}
</style>
