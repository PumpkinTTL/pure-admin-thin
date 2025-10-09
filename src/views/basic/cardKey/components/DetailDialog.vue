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
    class="detail-dialog"
  >
    <div v-loading="loading" class="detail-container">
      <!-- 头部信息卡片 -->
      <div class="card-header-info">
        <div class="card-title-row">
          <span class="card-title">基本信息</span>
          <el-tag :type="getStatusType(detail.status)" size="small" effect="plain">
            {{ getStatusText(detail.status) }}
          </el-tag>
        </div>

        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="卡密ID">
            {{ detail.id }}
          </el-descriptions-item>
          <el-descriptions-item label="卡密类型">
            <el-tag type="primary" size="small">{{ detail.cardType?.type_name || detail.type || '-' }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="卡密码" :span="2">
            <div class="code-display">
              <el-text class="code-text" size="large" tag="b">
                {{ detail.card_key }}
              </el-text>
              <el-button
                link
                type="primary"
                @click="handleCopyCode"
                class="copy-btn"
              >
                <IconifyIconOnline icon="ep:document-copy" />
                <span>复制</span>
              </el-button>
            </div>
          </el-descriptions-item>
          <el-descriptions-item label="价格">
            {{ detail.price ? `¥${detail.price}` : "无" }}
          </el-descriptions-item>
          <el-descriptions-item label="会员时长">
            {{ formatValidMinutes(detail.membership_duration || detail.valid_minutes) }}
          </el-descriptions-item>
          <el-descriptions-item label="可用期限" :span="2">
            <span v-if="!detail.expire_time" style="color: #67c23a;">永久可用</span>
            <span v-else :style="{ color: isAvailableExpired(detail.expire_time) ? '#f56c6c' : '#606266' }">
              {{ detail.expire_time }}
              <el-tag v-if="isAvailableExpired(detail.expire_time)" type="danger" size="small" style="margin-left: 10px">
                已过期
              </el-tag>
            </span>
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
      </div>

      <!-- 使用记录 -->
      <div class="logs-section" v-if="logs.length > 0">
        <div class="card-title-row">
          <span class="card-title">使用记录</span>
          <el-badge :value="logs.length" type="primary" />
        </div>

        <div class="logs-list">
          <div 
            v-for="log in logs" 
            :key="log.id" 
            class="log-item"
          >
            <div class="log-time">{{ log.create_time }}</div>
            <div class="log-content">
              <div class="log-header">
                <el-tag :type="getLogType(log.action)" size="small" effect="plain">
                  {{ log.action }}
                </el-tag>
                <span class="log-user">
                  {{ log.username || log.nickname || `用户ID: ${log.user_id}` }}
                </span>
              </div>
              <div class="log-remark" v-if="log.remark">
                {{ log.remark }}
              </div>
              <div class="log-meta">
                <span>IP: {{ log.ip || "未知" }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 空状态 -->
      <el-empty
        v-if="!loading && logs.length === 0"
        description="暂无使用记录"
        :image-size="100"
      />
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose" size="default">关闭</el-button>
        <el-button
          type="danger"
          :disabled="detail.status !== 0"
          @click="handleDisable"
          size="default"
        >
          <IconifyIconOnline icon="ep:close" />
          <span>禁用卡密</span>
        </el-button>
      </div>
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
    await copy(detail.value.card_key);
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
 * 判断可用期限是否过期
 */
const isAvailableExpired = (availableTime: string): boolean => {
  if (!availableTime) return false;
  return new Date(availableTime).getTime() < Date.now();
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
.detail-dialog {
  :deep(.el-dialog__header) {
    padding: 20px 24px;
    border-bottom: none;
    background: #ffffff;
  }

  :deep(.el-dialog__body) {
    padding: 0 24px 24px;
    max-height: 75vh;
    overflow-y: auto;
    background: #f8fafc;
  }

  :deep(.el-dialog__footer) {
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    background: #ffffff;
  }
}

.detail-container {
  // 卡片头部
  .card-header-info,
  .logs-section {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;

    &:last-child {
      margin-bottom: 0;
    }
  }

  // 统一的标题样式
  .card-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1f5f9;

    .card-title {
      font-size: 15px;
      font-weight: 600;
      color: #0f172a;
      position: relative;
      padding-left: 12px;
      
      &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 16px;
        background: linear-gradient(to bottom, #3b82f6, #60a5fa);
        border-radius: 2px;
      }
    }
  }

  // descriptions 样式
  :deep(.el-descriptions) {
    .el-descriptions__label {
      font-size: 13px;
      color: #64748b;
      font-weight: 500;
      padding: 10px 16px;
      background: #f8fafc;
      border-right: 2px solid #e5e7eb;
    }

    .el-descriptions__content {
      font-size: 13px;
      color: #0f172a;
      padding: 10px 16px;
      font-weight: 500;
    }

    .el-descriptions__cell {
      border-color: #f1f5f9;
    }
    
    .el-descriptions__body {
      border-radius: 8px;
      overflow: hidden;
    }
  }

  .code-display {
    display: flex;
    align-items: center;
    gap: 8px;

    .code-text {
      font-family: "Consolas", "Monaco", "Courier New", monospace;
      letter-spacing: 0.5px;
      color: #3b82f6;
      font-weight: 600;
      font-size: 14px;
    }
  }

  .copy-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
  }

  // 使用记录列表
  .logs-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .log-item {
    display: flex;
    gap: 16px;
    padding: 14px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 3px solid #e5e7eb;
    transition: all 0.2s ease;
    
    &:hover {
      background: #f1f5f9;
      border-left-color: #3b82f6;
      box-shadow: 0 2px 6px rgba(59, 130, 246, 0.08);
    }
    
    .log-time {
      flex-shrink: 0;
      width: 140px;
      font-size: 12px;
      color: #64748b;
      font-weight: 500;
      line-height: 24px;
    }
    
    .log-content {
      flex: 1;
      
      .log-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;

        .log-user {
          font-weight: 600;
          color: #0f172a;
          font-size: 13px;
        }
      }

      .log-remark {
        margin-bottom: 6px;
        padding: 8px 12px;
        background: #ffffff;
        border-radius: 6px;
        font-size: 12px;
        color: #475569;
        line-height: 1.5;
      }

      .log-meta {
        font-size: 11px;
        color: #94a3b8;
        
        span {
          &::before {
            content: '•';
            margin-right: 6px;
            color: #cbd5e1;
          }
        }
      }
    }
  }
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;

  .el-button {
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
}

// 响应式适配
@media (max-width: 768px) {
  .detail-container {
    :deep(.el-descriptions) {
      .el-descriptions__label {
        width: 80px;
        font-size: 12px;
        padding: 6px 10px;
      }
      
      .el-descriptions__content {
        font-size: 12px;
        padding: 6px 10px;
      }
    }
    
    .info-section,
    .logs-section {
      padding: 10px;
    }
  }
}

// 暗黑模式适配
html.dark {
  .detail-dialog {
    :deep(.el-dialog__header) {
      background: linear-gradient(to bottom, #1f2937, #111827);
      border-bottom-color: #374151;
    }
    
    :deep(.el-dialog__footer) {
      background: #1f2937;
      border-top-color: #374151;
    }
  }
  
  .detail-container {
    .info-section,
    .logs-section {
      background: #1f2937;
      border-color: #374151;
    }

    .section-header {
      border-bottom-color: #374151;

      .section-title {
        color: #f9fafb;
        
        &::before {
          background: linear-gradient(to bottom, #60a5fa, #3b82f6);
        }
      }
    }
    
    :deep(.el-descriptions) {
      .el-descriptions__label {
        background: #111827;
        color: #9ca3af;
      }
      
      .el-descriptions__content {
        color: #f9fafb;
      }
      
      .el-descriptions__cell {
        border-color: #374151;
      }
    }
    
    .code-display {
      .code-text {
        color: #60a5fa;
      }
    }
    
    :deep(.el-timeline) {
      .el-timeline-item__tail {
        border-left-color: #4b5563;
      }
      
      .el-card {
        background: #111827;
        border-color: #374151;
        
        &:hover {
          border-color: #60a5fa;
          box-shadow: 0 2px 8px rgba(96, 165, 250, 0.2);
        }
      }
    }
    
    .log-item {
      .log-header {
        .log-user {
          color: #f9fafb;
        }
      }
      
      .log-content {
        background: #111827;
        border-left-color: #4b5563;
        color: #e5e7eb;
      }
      
      .log-meta {
        color: #6b7280;
        
        &::before {
          color: #4b5563;
        }
      }
    }
  }
}
</style>

