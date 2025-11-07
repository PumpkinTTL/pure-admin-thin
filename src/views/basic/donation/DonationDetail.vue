<template>
  <div v-loading="loading" class="donation-detail">
    <el-empty v-if="!donationData && !loading" description="未找到捐赠记录" />

    <template v-else>
      <div class="main-content">
        <!-- 捐赠基本信息 -->
        <el-card class="info-card" shadow="never">
          <template #header>
            <div class="card-header">
              <font-awesome-icon :icon="['fas', 'hand-holding-heart']" />
              <span>捐赠信息</span>
              <el-tag
                :type="getStatusTagType(donationData?.status)"
                size="small"
                style="margin-left: auto"
              >
                {{ donationData?.status_text }}
              </el-tag>
            </div>
          </template>

          <div class="info-grid">
            <div class="info-item">
              <div class="item-label">捐赠单号</div>
              <div class="item-value">
                <div class="copy-value">
                  <span class="monospace">{{ donationData?.donation_no }}</span>
                  <el-button
                    link
                    type="primary"
                    size="small"
                    @click="copyToClipboard(donationData?.donation_no)"
                  >
                    <font-awesome-icon :icon="['fas', 'copy']" />
                  </el-button>
                </div>
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">捐赠渠道</div>
              <div class="item-value">
                <el-tag
                  class="channel-tag"
                  :class="donationData?.channel"
                  size="small"
                >
                  <font-awesome-icon
                    :icon="getChannelIcon(donationData?.channel)"
                  />
                  {{ donationData?.channel_text }}
                </el-tag>
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">捐赠者</div>
              <div class="item-value">
                <span
                  v-if="donationData?.is_anonymous === 1"
                  class="anonymous-tag"
                >
                  <font-awesome-icon :icon="['fas', 'user-secret']" />
                  匿名捐赠
                </span>
                <span v-else>{{ getDonorName() }}</span>
              </div>
            </div>

            <div v-if="donationData?.is_anonymous === 0" class="info-item">
              <div class="item-label">联系方式</div>
              <div class="item-value">
                {{ donationData?.donor_contact || "-" }}
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">捐赠金额</div>
              <div class="item-value">
                <div
                  v-if="donationData?.channel === 'cardkey'"
                  class="amount-display"
                >
                  <font-awesome-icon
                    :icon="['fas', 'ticket']"
                    class="amount-icon cardkey"
                  />
                  <span class="amount-text cardkey">
                    {{ formatAmount(donationData?.card_key_value) }}
                  </span>
                </div>
                <div
                  v-else-if="donationData?.channel === 'crypto'"
                  class="amount-display"
                >
                  <font-awesome-icon
                    :icon="['fab', 'bitcoin']"
                    class="amount-icon crypto"
                  />
                  <span class="amount-text crypto">
                    {{ formatAmount(donationData?.amount) }}
                    {{ donationData?.crypto_type || "USDT" }}
                  </span>
                </div>
                <div v-else class="amount-display">
                  <font-awesome-icon
                    :icon="['fas', 'yen-sign']"
                    class="amount-icon money"
                  />
                  <span class="amount-text money">
                    {{ formatAmount(donationData?.amount) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">是否公开</div>
              <div class="item-value">
                <font-awesome-icon
                  :icon="
                    donationData?.is_public === 1
                      ? ['fas', 'eye']
                      : ['fas', 'eye-slash']
                  "
                  :style="{
                    color:
                      donationData?.is_public === 1 ? '#67c23a' : '#909399',
                    fontSize: '16px'
                  }"
                />
                <span style="margin-left: 8px">
                  {{ donationData?.is_public === 1 ? "公开展示" : "不公开" }}
                </span>
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">创建时间</div>
              <div class="item-value">
                {{ formatDateTime(donationData?.create_time) }}
              </div>
            </div>

            <div v-if="donationData?.confirm_time" class="info-item">
              <div class="item-label">确认时间</div>
              <div class="item-value">
                {{ formatDateTime(donationData?.confirm_time) }}
              </div>
            </div>
          </div>
        </el-card>

        <!-- 渠道详细信息 -->
        <el-card v-if="showChannelDetails" class="info-card" shadow="never">
          <template #header>
            <div class="card-header">
              <font-awesome-icon :icon="['fas', 'info-circle']" />
              <span>渠道详情</span>
            </div>
          </template>

          <!-- 微信/支付宝 -->
          <div
            v-if="
              donationData?.channel === 'wechat' ||
              donationData?.channel === 'alipay'
            "
            class="info-grid"
          >
            <div class="info-item">
              <div class="item-label">订单号</div>
              <div class="item-value">
                <div class="copy-value">
                  <span class="monospace">
                    {{ donationData?.order_no || "-" }}
                  </span>
                  <el-button
                    v-if="donationData?.order_no"
                    link
                    type="primary"
                    class="copy-btn"
                    title="复制"
                    @click="copyToClipboard(donationData?.order_no)"
                  >
                    <font-awesome-icon :icon="['fas', 'copy']" />
                  </el-button>
                </div>
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">交易流水号</div>
              <div class="item-value">
                <div class="copy-value">
                  <span class="monospace">
                    {{ donationData?.transaction_id || "-" }}
                  </span>
                  <el-button
                    v-if="donationData?.transaction_id"
                    link
                    type="primary"
                    class="copy-btn"
                    title="复制"
                    @click="copyToClipboard(donationData?.transaction_id)"
                  >
                    <font-awesome-icon :icon="['fas', 'copy']" />
                  </el-button>
                </div>
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">支付时间</div>
              <div class="item-value">
                {{ formatDateTime(donationData?.payment_time) }}
              </div>
            </div>
          </div>

          <!-- 加密货币 -->
          <div v-if="donationData?.channel === 'crypto'" class="info-grid">
            <div class="info-item">
              <div class="item-label">加密货币类型</div>
              <div class="item-value">
                {{
                  donationData?.crypto_type_text ||
                  donationData?.crypto_type ||
                  "-"
                }}
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">区块链网络</div>
              <div class="item-value">
                {{
                  donationData?.crypto_network_text ||
                  donationData?.crypto_network ||
                  "-"
                }}
              </div>
            </div>

            <div class="info-item full-width">
              <div class="item-label">交易哈希</div>
              <div class="item-value">
                <div class="copy-value">
                  <span class="monospace hash-text">
                    {{ donationData?.transaction_hash || "-" }}
                  </span>
                  <el-button
                    v-if="donationData?.transaction_hash"
                    link
                    type="primary"
                    class="copy-btn"
                    title="复制"
                    @click="copyToClipboard(donationData?.transaction_hash)"
                  >
                    <font-awesome-icon :icon="['fas', 'copy']" />
                  </el-button>
                </div>
              </div>
            </div>

            <div class="info-item full-width">
              <div class="item-label">发送地址</div>
              <div class="item-value">
                <div class="copy-value">
                  <span class="monospace">
                    {{ donationData?.from_address || "-" }}
                  </span>
                  <el-button
                    v-if="donationData?.from_address"
                    link
                    type="primary"
                    class="copy-btn"
                    title="复制"
                    @click="copyToClipboard(donationData?.from_address)"
                  >
                    <font-awesome-icon :icon="['fas', 'copy']" />
                  </el-button>
                </div>
              </div>
            </div>

            <div class="info-item full-width">
              <div class="item-label">接收地址</div>
              <div class="item-value">
                <div class="copy-value">
                  <span class="monospace">
                    {{ donationData?.to_address || "-" }}
                  </span>
                  <el-button
                    v-if="donationData?.to_address"
                    link
                    type="primary"
                    class="copy-btn"
                    title="复制"
                    @click="copyToClipboard(donationData?.to_address)"
                  >
                    <font-awesome-icon :icon="['fas', 'copy']" />
                  </el-button>
                </div>
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">确认数</div>
              <div class="item-value">
                {{ donationData?.confirmation_count || 0 }}
              </div>
            </div>
          </div>

          <!-- 卡密兑换 -->
          <div v-if="donationData?.channel === 'cardkey'" class="info-grid">
            <div class="info-item">
              <div class="item-label">卡密码</div>
              <div class="item-value">
                <div class="copy-value">
                  <span class="monospace">
                    {{ donationData?.card_key_code || "-" }}
                  </span>
                  <el-button
                    v-if="donationData?.card_key_code"
                    link
                    type="primary"
                    class="copy-btn"
                    title="复制"
                    @click="copyToClipboard(donationData?.card_key_code)"
                  >
                    <font-awesome-icon :icon="['fas', 'copy']" />
                  </el-button>
                </div>
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">卡密ID</div>
              <div class="item-value">
                {{ donationData?.card_key_id || "-" }}
              </div>
            </div>

            <div class="info-item">
              <div class="item-label">卡密等值金额</div>
              <div class="item-value">
                <span class="amount">
                  ¥{{ formatAmount(donationData?.card_key_value) }}
                </span>
              </div>
            </div>
          </div>
        </el-card>

        <!-- 备注信息 -->
        <el-card
          v-if="donationData?.remark || donationData?.admin_remark"
          class="info-card"
          shadow="never"
        >
          <template #header>
            <div class="card-header">
              <font-awesome-icon :icon="['fas', 'comment-dots']" />
              <span>备注信息</span>
            </div>
          </template>

          <div class="info-grid">
            <div v-if="donationData?.remark" class="info-item full-width">
              <div class="item-label">备注</div>
              <div class="item-value remark-text">
                {{ donationData?.remark }}
              </div>
            </div>

            <div v-if="donationData?.admin_remark" class="info-item full-width">
              <div class="item-label">管理员备注</div>
              <div class="item-value remark-text">
                {{ donationData?.admin_remark }}
              </div>
            </div>
          </div>
        </el-card>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "DonationDetail"
});

import { ref, computed, onMounted } from "vue";
import { message } from "@/utils/message";
import {
  getDonationDetail,
  Donation,
  DonationStatusTypeMap
} from "@/api/donation";
import { useClipboard } from "@vueuse/core";

// 定义props
const props = defineProps({
  donationId: {
    type: Number,
    required: true
  }
});

// 加载状态
const loading = ref(false);

// 捐赠记录数据
const donationData = ref<Donation | null>(null);

// 是否显示渠道详情
const showChannelDetails = computed(() => {
  if (!donationData.value) return false;
  const channel = donationData.value.channel;
  return (
    channel === "wechat" ||
    channel === "alipay" ||
    channel === "crypto" ||
    channel === "cardkey"
  );
});

// 格式化日期时间
const formatDateTime = (dateTime: string | undefined) => {
  if (!dateTime) return "-";
  return dateTime;
};

// 格式化金额
const formatAmount = (amount: number | undefined) => {
  if (amount === undefined || amount === null) return "0.00";
  return Number(amount).toFixed(2);
};

// 获取捐赠者名称
const getDonorName = () => {
  if (!donationData.value) return "-";

  // 如果有user_id且关联了用户信息，显示用户昵称
  if (donationData.value.user_id && donationData.value.user) {
    return (
      donationData.value.user.nickname ||
      `用户ID: ${donationData.value.user_id}`
    );
  }

  // 如果没有user_id，显示donor_name（匿名捐赠的情况）
  return donationData.value.donor_name || "匿名用户";
};

// 获取状态图标
const getStatusIcon = (status: number | undefined) => {
  const iconMap: Record<number, any> = {
    0: ["fas", "clock"],
    1: ["fas", "check-circle"],
    2: ["fas", "check-double"],
    3: ["fas", "times-circle"]
  };
  return iconMap[status || 0] || ["fas", "question"];
};

// 获取状态描述
const getStatusDescription = (status: number | undefined) => {
  const descMap: Record<number, string> = {
    0: "等待管理员确认",
    1: "已确认收到捐赠",
    2: "捐赠流程已完成",
    3: "捐赠已取消"
  };
  return descMap[status || 0] || "";
};

// 获取状态标签类型
const getStatusTagType = (
  status: number | undefined
): "success" | "warning" | "info" | "primary" | "danger" => {
  return (DonationStatusTypeMap[status || 0] as any) || "info";
};

// 获取渠道图标
const getChannelIcon = (channel: string | undefined) => {
  const iconMap: Record<string, any> = {
    wechat: ["fab", "weixin"],
    alipay: ["fab", "alipay"],
    crypto: ["fab", "bitcoin"],
    cardkey: ["fas", "key"]
  };
  return iconMap[channel || ""] || ["fas", "question"];
};

// 复制到剪贴板
const { copy } = useClipboard();
const copyToClipboard = async (text: string | undefined) => {
  if (!text) return;

  try {
    await copy(text);
    message("复制成功", { type: "success" });
  } catch (error) {
    message("复制失败", { type: "error" });
  }
};

// 加载捐赠记录详情
const loadDonationDetail = async () => {
  loading.value = true;

  try {
    const res = await getDonationDetail(props.donationId);

    if (res.code === 200) {
      donationData.value = res.data;
    } else {
      message(res.message || "加载失败", { type: "error" });
    }
  } catch (error) {
    console.error("加载捐赠记录详情失败:", error);
    message("加载捐赠记录详情失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

// 初始化
onMounted(() => {
  loadDonationDetail();
});
</script>

<style scoped lang="scss">


@media (width <= 768px) {
  .donation-detail {
    .main-content {
      .info-card {
        .info-grid {
          grid-template-columns: 1fr;
        }
      }
    }
  }
}

.donation-detail {
  padding: 0;

  .donation-header {
    margin-bottom: 20px;

    .donation-status {
      padding: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 8px;

      &.status-0 {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      }

      &.status-1 {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      }

      &.status-2 {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
      }

      &.status-3 {
        background: linear-gradient(135deg, #a8a8a8 0%, #7f7f7f 100%);
      }

      .status-content {
        display: flex;
        gap: 16px;
        align-items: center;
        color: white;

        .status-icon {
          font-size: 48px;
        }

        .status-info {
          .status-text {
            margin-bottom: 4px;
            font-size: 24px;
            font-weight: 600;
          }

          .status-desc {
            font-size: 14px;
            opacity: 0.9;
          }
        }
      }
    }
  }

  .main-content {
    display: flex;
    flex-direction: column;
    gap: 16px;

    .info-card {
      border: 1px solid var(--el-border-color-lighter);
      border-radius: 8px;

      :deep(.el-card__header) {
        padding: 12px 16px;
        background: var(--el-fill-color-light);
        border-bottom: 1px solid var(--el-border-color-lighter);
      }

      :deep(.el-card__body) {
        padding: 16px;
      }

      .card-header {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);

        svg {
          font-size: 14px;
          color: var(--el-color-primary);
        }
      }

      .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;

        .info-item {
          display: flex;
          flex-direction: column;
          gap: 6px;

          &.full-width {
            grid-column: 1 / -1;
          }

          .item-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--el-text-color-secondary);
          }

          .item-value {
            font-size: 13px;
            color: var(--el-text-color-primary);
            word-break: break-all;

            .monospace {
              padding: 2px 6px;
              font-family: Consolas, Monaco, monospace;
              font-size: 12px;
              background: var(--el-fill-color-light);
              border-radius: 4px;
            }

            .hash-text {
              font-size: 11px;
            }

            .copy-value {
              display: flex;
              gap: 6px;
              align-items: center;
            }

            .remark-text {
              padding: 8px;
              line-height: 1.6;
              white-space: pre-wrap;
              background: var(--el-fill-color-light);
              border-radius: 4px;
            }
          }
        }
      }
    }
  }

  .anonymous-tag {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    color: var(--el-text-color-secondary);
  }

  .channel-tag {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: 500;
    border: none;
    border-radius: 4px;

    &.wechat {
      color: #2e7d32;
      background: #e8f5e9;
    }

    &.alipay {
      color: #1565c0;
      background: #e3f2fd;
    }

    &.crypto {
      color: #e65100;
      background: #fff3e0;
    }

    &.cardkey {
      color: #6a1b9a;
      background: #f3e5f5;
    }
  }

  .amount-display {
    display: inline-flex;
    gap: 6px;
    align-items: center;

    .amount-icon {
      font-size: 14px;

      &.money {
        color: #e74c3c;
      }

      &.crypto {
        color: #ff9800;
      }

      &.cardkey {
        color: #9c27b0;
      }
    }

    .amount-text {
      font-size: 14px;
      color: var(--el-text-color-primary);

      &.money {
        color: #e74c3c;
      }

      &.crypto {
        color: #ff9800;
      }

      &.cardkey {
        color: #9c27b0;
      }
    }
  }
}

// 暗黑模式适配
html.dark {
  .donation-detail {
    .channel-tag {
      &.wechat {
        color: #81c784;
        background: rgb(46 125 50 / 20%);
      }

      &.alipay {
        color: #64b5f6;
        background: rgb(21 101 192 / 20%);
      }

      &.crypto {
        color: #ffb74d;
        background: rgb(230 81 0 / 20%);
      }

      &.cardkey {
        color: #ba68c8;
        background: rgb(106 27 154 / 20%);
      }
    }
  }
}
</style>
