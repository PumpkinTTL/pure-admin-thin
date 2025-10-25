<template>
  <el-dialog
    v-model="dialogVisible"
    title="邮件详情"
    width="70%"
    :before-close="handleClose"
    class="email-detail-dialog"
  >
    <div v-loading="loading" class="detail-container">
      <!-- 基本信息 -->
      <div class="info-section">
        <div class="section-title">
          <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-1" />
          <span>基本信息</span>
        </div>
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="邮件标题">
            {{ emailDetail.title }}
          </el-descriptions-item>

          <el-descriptions-item label="发送者">
            {{ emailDetail.sender_name }}
          </el-descriptions-item>

          <el-descriptions-item label="接收方式">
            <el-tag
              :type="getReceiverTypeTag(emailDetail.receiver_type)"
              size="small"
            >
              {{
                emailDetail.receiver_type_text ||
                RECEIVER_TYPE_TEXT[emailDetail.receiver_type || 0] ||
                "未知"
              }}
            </el-tag>
          </el-descriptions-item>

          <el-descriptions-item label="发送状态">
            <el-tag :type="getStatusTag(emailDetail.status)" size="small">
              {{
                emailDetail.status_text ||
                RECORD_STATUS_TEXT[emailDetail.status || 0] ||
                "未知"
              }}
            </el-tag>
          </el-descriptions-item>

          <el-descriptions-item label="发送时间">
            {{ emailDetail.send_time || "-" }}
          </el-descriptions-item>

          <el-descriptions-item label="创建时间">
            {{ emailDetail.create_time }}
          </el-descriptions-item>

          <el-descriptions-item label="邮件内容" :span="2">
            <div class="email-content" v-html="emailDetail.content" />
          </el-descriptions-item>
        </el-descriptions>
      </div>

      <!-- 统计信息 -->
      <el-row :gutter="10" class="stats-row">
        <el-col :xs="24" :sm="8">
          <div class="stat-item">
            <div class="stat-icon" style="background: #409eff">
              <font-awesome-icon :icon="['fas', 'envelope']" />
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ emailDetail.total_count || 0 }}</div>
              <div class="stat-label">总发送数</div>
            </div>
          </div>
        </el-col>

        <el-col :xs="24" :sm="8">
          <div class="stat-item">
            <div class="stat-icon" style="background: #67c23a">
              <font-awesome-icon :icon="['fas', 'check-circle']" />
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ emailDetail.success_count || 0 }}</div>
              <div class="stat-label">成功数</div>
            </div>
          </div>
        </el-col>

        <el-col :xs="24" :sm="8">
          <div class="stat-item">
            <div class="stat-icon" style="background: #f56c6c">
              <font-awesome-icon :icon="['fas', 'times-circle']" />
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ emailDetail.failed_count || 0 }}</div>
              <div class="stat-label">失败数</div>
            </div>
          </div>
        </el-col>
      </el-row>

      <!-- 接收者列表 -->
      <div class="receivers-section">
        <div class="section-header">
          <div class="section-title">
            <font-awesome-icon :icon="['fas', 'users']" class="mr-1" />
            <span>接收者列表</span>
          </div>
          <el-button
            v-if="emailDetail.failed_count > 0"
            type="warning"
            size="small"
            @click="handleResendFailed"
          >
            <font-awesome-icon :icon="['fas', 'redo']" class="mr-1" />
            重发失败邮件
          </el-button>
        </div>

        <el-table
          v-loading="receiversLoading"
          :data="receivers"
          stripe
          border
          max-height="400"
          size="small"
        >
          <el-table-column
            prop="email"
            label="邮箱地址"
            min-width="200"
            show-overflow-tooltip
          />

          <el-table-column
            prop="receiver_type_text"
            label="类型"
            width="120"
            align="center"
          >
            <template #default="{ row }">
              <el-tag :type="row.user_id ? 'success' : 'info'" size="small">
                {{ row.user_id ? "系统用户" : "外部邮箱" }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column
            prop="status_text"
            label="发送状态"
            width="120"
            align="center"
          >
            <template #default="{ row }">
              <el-tag :type="getReceiverStatusTag(row.status)" size="small">
                {{
                  row.status_text || RECEIVER_STATUS_TEXT[row.status] || "未知"
                }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column
            prop="error_msg"
            label="错误信息"
            min-width="200"
            show-overflow-tooltip
          >
            <template #default="{ row }">
              <span v-if="row.error_msg" style="color: #f56c6c">
                {{ row.error_msg }}
              </span>
              <span v-else style="color: #67c23a">-</span>
            </template>
          </el-table-column>

          <el-table-column
            prop="send_time"
            label="发送时间"
            width="180"
            align="center"
          />
        </el-table>

        <!-- 分页 -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="receiverPagination.page"
            v-model:page-size="receiverPagination.page_size"
            :page-sizes="[10, 20, 50, 100]"
            :total="receiverPagination.total"
            layout="total, sizes, prev, pager, next"
            size="small"
            @size-change="handleReceiverSizeChange"
            @current-change="handleReceiverPageChange"
          />
        </div>
      </div>
    </div>

    <template #footer>
      <el-button @click="handleClose">关闭</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from "vue";
import { ElMessageBox } from "element-plus";
import { message } from "@/utils/message";
import {
  getEmailRecordDetail,
  getEmailReceivers,
  resendEmail,
  type EmailRecordData,
  type EmailReceiverData
} from "@/api/emailRecord";

// 接收方式映射
const RECEIVER_TYPE_TEXT: Record<number, string> = {
  1: "全部用户",
  2: "指定多个用户",
  3: "单个用户",
  4: "指定邮箱"
};

// 邮件记录发送状态映射
const RECORD_STATUS_TEXT: Record<number, string> = {
  0: "待发送",
  1: "发送中",
  2: "发送完成",
  3: "部分失败",
  4: "全部失败"
};

// 接收者发送状态映射
const RECEIVER_STATUS_TEXT: Record<number, string> = {
  0: "待发送",
  1: "发送成功",
  2: "发送失败"
};

// Props
const props = defineProps<{
  visible: boolean;
  recordId: number;
}>();

// Emits
const emit = defineEmits<{
  "update:visible": [value: boolean];
  refresh: [];
}>();

// 对话框显示状态
const dialogVisible = ref(false);

// 加载状态
const loading = ref(false);
const receiversLoading = ref(false);

// 邮件详情
const emailDetail = ref<Partial<EmailRecordData>>({});

// 接收者列表
const receivers = ref<EmailReceiverData[]>([]);

// 接收者分页
const receiverPagination = reactive({
  page: 1,
  page_size: 10,
  total: 0
});

// 监听visible变化
watch(
  () => props.visible,
  val => {
    dialogVisible.value = val;
    if (val && props.recordId) {
      loadDetail();
      loadReceivers();
    }
  }
);

// 监听dialogVisible变化
watch(dialogVisible, val => {
  emit("update:visible", val);
});

// 加载详情
const loadDetail = async () => {
  loading.value = true;
  try {
    const res = (await getEmailRecordDetail(props.recordId)) as any;

    if (res.code === 200 && res.data) {
      emailDetail.value = res.data;
    }
  } catch (error) {
    console.error("加载邮件详情失败:", error);
    message("加载详情失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

// 加载接收者列表
const loadReceivers = async () => {
  receiversLoading.value = true;
  try {
    const res = (await getEmailReceivers({
      record_id: props.recordId,
      page: receiverPagination.page,
      page_size: receiverPagination.page_size
    })) as any;

    if (res.code === 200) {
      receivers.value = res.data.data || [];
      receiverPagination.total = res.data.total || 0;
    }
  } catch (error) {
    console.error("加载接收者列表失败:", error);
    message("加载接收者列表失败", { type: "error" });
  } finally {
    receiversLoading.value = false;
  }
};

// 接收者分页变化
const handleReceiverSizeChange = (size: number) => {
  receiverPagination.page_size = size;
  loadReceivers();
};

const handleReceiverPageChange = (page: number) => {
  receiverPagination.page = page;
  loadReceivers();
};

// 重发失败邮件
const handleResendFailed = async () => {
  try {
    await ElMessageBox.confirm(
      `确定要重新发送失败的邮件吗？将重新发送 ${emailDetail.value.failed_count} 封失败的邮件。`,
      "重新发送确认",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const res = (await resendEmail({ record_id: props.recordId })) as any;

    if (res.code === 200) {
      message("重新发送成功", { type: "success" });
      loadDetail();
      loadReceivers();
      emit("refresh");
    } else {
      message(res.message || "重新发送失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("重新发送失败:", error);
      message("重新发送失败", { type: "error" });
    }
  }
};

// 获取接收方式标签类型
const getReceiverTypeTag = (
  type: number
): "success" | "warning" | "info" | "danger" | "primary" => {
  const tags: Record<
    number,
    "success" | "warning" | "info" | "danger" | "primary"
  > = {
    1: "primary",
    2: "success",
    3: "warning",
    4: "info"
  };
  return tags[type] || "info";
};

// 获取邮件记录状态标签类型
const getStatusTag = (
  status: number
): "success" | "warning" | "info" | "danger" | "primary" => {
  const tags: Record<
    number,
    "success" | "warning" | "info" | "danger" | "primary"
  > = {
    0: "info",
    1: "warning",
    2: "success",
    3: "warning",
    4: "danger"
  };
  return tags[status] || "info";
};

// 获取接收者状态标签类型
const getReceiverStatusTag = (
  status: number
): "success" | "warning" | "info" | "danger" | "primary" => {
  const tags: Record<
    number,
    "success" | "warning" | "info" | "danger" | "primary"
  > = {
    0: "info", // 待发送
    1: "success", // 发送成功
    2: "danger" // 发送失败
  };
  return tags[status] || "info";
};

// 关闭对话框
const handleClose = () => {
  dialogVisible.value = false;
};

// 暴露方法给父组件
defineExpose({
  open
});
</script>

<style lang="scss" scoped>
.email-detail-dialog {
  @media (width <= 768px) {
    .detail-container {
      .stats-row {
        .stat-item {
          padding: 8px;
          margin-bottom: 8px;

          .stat-icon {
            width: 32px;
            height: 32px;
            font-size: 14px;
          }

          .stat-info {
            .stat-value {
              font-size: 16px;
            }

            .stat-label {
              font-size: 11px;
            }
          }
        }
      }

      .pagination-container :deep(.el-pagination) {
        justify-content: center;
      }
    }
  }

  .detail-container {
    .info-section,
    .receivers-section {
      margin-bottom: 12px;
    }

    .section-title {
      padding-bottom: 8px;
      margin-bottom: 10px;
      font-size: 14px;
      font-weight: 600;
      color: #303133;
      border-bottom: 1px solid #ebeef5;
    }

    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 8px;
      margin-bottom: 10px;
      border-bottom: 1px solid #ebeef5;

      .section-title {
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: none;
      }
    }

    .email-content {
      max-height: 180px;
      padding: 8px;
      overflow-y: auto;
      font-size: 13px;
      line-height: 1.6;
      background-color: #f5f7fa;
      border-radius: 4px;
    }

    .stats-row {
      margin-bottom: 12px;

      .stat-item {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 10px;
        background: white;
        border: 1px solid #ebeef5;
        border-radius: 6px;

        .stat-icon {
          display: flex;
          flex-shrink: 0;
          align-items: center;
          justify-content: center;
          width: 36px;
          height: 36px;
          font-size: 16px;
          color: white;
          border-radius: 6px;
        }

        .stat-info {
          flex: 1;

          .stat-value {
            margin-bottom: 2px;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.2;
            color: #303133;
          }

          .stat-label {
            font-size: 12px;
            color: #909399;
          }
        }
      }
    }

    .pagination-container {
      display: flex;
      justify-content: flex-end;
      margin-top: 10px;
    }
  }

  :deep(.el-dialog__body) {
    padding: 16px;
  }

  :deep(.el-descriptions__label) {
    font-size: 13px;
    font-weight: 500;
  }

  :deep(.el-descriptions__content) {
    font-size: 13px;
  }

  :deep(.el-table__header) th {
    height: 36px;
    padding: 6px 0;
    background-color: #fafafa;
  }

  :deep(.el-table__body) td {
    padding: 4px 0;
  }
}
</style>
