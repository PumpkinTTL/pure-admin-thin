<!--
  卡密详情对话框组件
  
  功能：
  - 显示卡密完整信息
  - 显示使用记录时间线
  - 支持复制卡密码
  - 支持禁用操作
  
  @author AI Assistant
  @date 2025-10-01
-->
<template>
  <el-dialog
    v-model="dialogVisible"
    title="卡密详情"
    width="700px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <div v-loading="loading" class="detail-container">
      <!-- 基本信息 -->
      <el-card class="info-card animate__animated animate__fadeIn" shadow="never">
        <template #header>
          <div class="card-header">
            <span>基本信息</span>
            <el-tag :type="getStatusType(detail.status)">
              {{ getStatusText(detail.status) }}
            </el-tag>
          </div>
        </template>

        <el-descriptions :column="2" border>
          <el-descriptions-item label="卡密ID">
            {{ detail.id }}
          </el-descriptions-item>
          <el-descriptions-item label="卡密类型">
            <el-tag type="primary">{{ detail.type }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="卡密码" :span="2">
            <div class="code-display">
              <el-text class="code-text" size="large" tag="b">
                {{ detail.code }}
              </el-text>
              <el-button
                link
                type="primary"
                @click="handleCopyCode"
                style="margin-left: 10px"
              >
                <IconifyIconOnline icon="ep:document-copy" />
                复制
              </el-button>
            </div>
          </el-descriptions-item>
          <el-descriptions-item label="价格">
            {{ detail.price ? `¥${detail.price}` : "无" }}
          </el-descriptions-item>
          <el-descriptions-item label="有效时长">
            {{ formatValidMinutes(detail.valid_minutes) }}
          </el-descriptions-item>
          <el-descriptions-item label="创建时间" :span="2">
            {{ detail.create_time }}
          </el-descriptions-item>
          <el-descriptions-item label="使用时间" :span="2">
            {{ detail.use_time || "未使用" }}
          </el-descriptions-item>
          <el-descriptions-item label="使用者" :span="2" v-if="detail.user_id">
            {{ detail.username || detail.nickname || `用户ID: ${detail.user_id}` }}
          </el-descriptions-item>
          <el-descriptions-item label="过期时间" :span="2" v-if="detail.expire_time">
            <el-text :type="detail.is_expired ? 'danger' : 'success'">
              {{ detail.expire_time }}
              <el-tag v-if="detail.is_expired" type="danger" size="small" style="margin-left: 10px">
                已过期
              </el-tag>
            </el-text>
          </el-descriptions-item>
          <el-descriptions-item label="备注" :span="2" v-if="detail.remark">
            {{ detail.remark }}
          </el-descriptions-item>
        </el-descriptions>
      </el-card>

      <!-- 使用记录 -->
      <el-card class="logs-card animate__animated animate__fadeIn" shadow="never" v-if="logs.length > 0">
        <template #header>
          <div class="card-header">
            <span>使用记录</span>
            <el-badge :value="logs.length" type="primary" />
          </div>
        </template>

        <el-timeline>
          <el-timeline-item
            v-for="log in logs"
            :key="log.id"
            :timestamp="log.create_time"
            placement="top"
            :type="getLogType(log.action)"
          >
            <el-card shadow="hover">
              <div class="log-item">
                <div class="log-header">
                  <el-tag :type="getLogType(log.action)" size="small">
                    {{ log.action }}
                  </el-tag>
                  <span class="log-user">
                    {{ log.username || log.nickname || `用户ID: ${log.user_id}` }}
                  </span>
                </div>
                <div class="log-content" v-if="log.remark">
                  <el-text type="info">{{ log.remark }}</el-text>
                </div>
                <div class="log-meta">
                  <el-text size="small" type="info">
                    IP: {{ log.ip || "未知" }}
                  </el-text>
                </div>
              </div>
            </el-card>
          </el-timeline-item>
        </el-timeline>
      </el-card>

      <!-- 空状态 -->
      <el-empty
        v-if="!loading && logs.length === 0"
        description="暂无使用记录"
        :image-size="100"
      />
    </div>

    <template #footer>
      <el-button @click="handleClose">关闭</el-button>
      <el-button
        type="danger"
        :disabled="detail.status === 2"
        @click="handleDisable"
      >
        <IconifyIconOnline icon="ep:close" />
        禁用卡密
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from "vue";
import { ElMessageBox } from "element-plus";
import { useClipboard } from "@vueuse/core";
import {
  getCardKeyDetail,
  getCardKeyLogs,
  disableCardKey,
  CardKeyStatusMap,
  CardKeyStatusTypeMap,
  formatValidMinutes,
  type CardKey,
  type CardKeyLog
} from "@/api/cardKey";
import { message } from "@/utils/message";
import { IconifyIconOnline } from "@/components/ReIcon";

defineOptions({
  name: "DetailDialog"
});

// Props
interface Props {
  visible: boolean;
  cardKeyId: number;
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  cardKeyId: 0
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

const loading = ref(false);
const detail = ref<CardKey>({} as CardKey);
const logs = ref<CardKeyLog[]>([]);

// 剪贴板
const { copy, isSupported } = useClipboard();

/**
 * 获取详情
 */
const fetchDetail = async () => {
  if (!props.cardKeyId) return;

  loading.value = true;
  try {
    const response = await getCardKeyDetail(props.cardKeyId);
    if (response.code === 200) {
      detail.value = response.data;
    } else {
      message(response.message || "获取详情失败", { type: "error" });
    }
  } catch (error) {
    message("获取详情失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

/**
 * 获取使用记录
 */
const fetchLogs = async () => {
  if (!props.cardKeyId) return;

  try {
    const response = await getCardKeyLogs(props.cardKeyId, { page: 1, limit: 100 });
    if (response.code === 200) {
      logs.value = response.data.list || [];
    }
  } catch (error) {
    console.error("获取使用记录失败", error);
  }
};

/**
 * 复制卡密码
 */
const handleCopyCode = async () => {
  if (!isSupported) {
    message("浏览器不支持复制功能", { type: "warning" });
    return;
  }

  try {
    await copy(detail.value.code);
    message("复制成功", { type: "success" });
  } catch (error) {
    message("复制失败", { type: "error" });
  }
};

/**
 * 禁用卡密
 */
const handleDisable = async () => {
  try {
    const { value: reason } = await ElMessageBox.prompt(
      "请输入禁用原因",
      "禁用卡密",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        inputPattern: /.+/,
        inputErrorMessage: "禁用原因不能为空"
      }
    );

    const response = await disableCardKey(props.cardKeyId, {
      user_id: 1, // TODO: 从用户信息获取
      reason
    });

    if (response.code === 200) {
      message("禁用成功", { type: "success" });
      emit("success");
      handleClose();
    } else {
      message(response.message || "禁用失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      message("禁用失败", { type: "error" });
    }
  }
};

/**
 * 获取状态文本
 */
const getStatusText = (status: number): string => {
  return CardKeyStatusMap[status] || "未知";
};

/**
 * 获取状态标签类型
 */
const getStatusType = (status: number): string => {
  return CardKeyStatusTypeMap[status] || "info";
};

/**
 * 获取日志类型
 */
const getLogType = (action: string): string => {
  const typeMap: Record<string, string> = {
    "使用": "success",
    "验证": "info",
    "禁用": "danger",
    "启用": "success"
  };
  return typeMap[action] || "info";
};

/**
 * 关闭对话框
 */
const handleClose = () => {
  detail.value = {} as CardKey;
  logs.value = [];
  dialogVisible.value = false;
};

// 监听对话框打开
watch(
  () => props.visible,
  (val) => {
    if (val && props.cardKeyId) {
      fetchDetail();
      fetchLogs();
    }
  }
);
</script>

<style scoped lang="scss">
.detail-container {
  .info-card,
  .logs-card {
    margin-bottom: 20px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .code-display {
    display: flex;
    align-items: center;

    .code-text {
      font-family: "Courier New", monospace;
      letter-spacing: 2px;
      color: var(--el-color-primary);
    }
  }

  .log-item {
    .log-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;

      .log-user {
        font-weight: 500;
      }
    }

    .log-content {
      margin-bottom: 8px;
    }

    .log-meta {
      font-size: 12px;
    }
  }
}

// 响应式适配
@media (max-width: 768px) {
  .detail-container {
    :deep(.el-descriptions) {
      .el-descriptions__label {
        width: 80px;
      }
    }
  }
}

// 暗黑模式适配
html.dark {
  .detail-container {
    .info-card,
    .logs-card {
      background: var(--el-bg-color);
      border-color: var(--el-border-color);
    }
  }
}
</style>

