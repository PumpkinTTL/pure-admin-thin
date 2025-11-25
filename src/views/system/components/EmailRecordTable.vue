<template>
  <div class="email-record-container">
    <!-- 搜索栏 -->
    <el-form :model="searchForm" inline class="search-form">
      <el-form-item label="邮件标题">
        <el-input
          v-model="searchForm.title"
          placeholder="请输入标题"
          clearable
          style="width: 180px"
          @clear="handleSearch"
        />
      </el-form-item>

      <el-form-item label="发送状态">
        <el-select
          v-model="searchForm.status"
          placeholder="请选择状态"
          clearable
          style="width: 140px"
          @change="handleSearch"
        >
          <el-option label="待发送" :value="0" />
          <el-option label="发送中" :value="1" />
          <el-option label="发送完成" :value="2" />
          <el-option label="部分失败" :value="3" />
          <el-option label="全部失败" :value="4" />
        </el-select>
      </el-form-item>

      <el-form-item label="接收方式">
        <el-select
          v-model="searchForm.receiver_type"
          placeholder="请选择方式"
          clearable
          style="width: 140px"
          @change="handleSearch"
        >
          <el-option label="全部用户" :value="1" />
          <el-option label="指定多个用户" :value="2" />
          <el-option label="单个用户" :value="3" />
          <el-option label="指定邮箱" :value="4" />
        </el-select>
      </el-form-item>

      <el-form-item label="删除状态">
        <el-select
          v-model="searchForm.include_deleted"
          placeholder="请选择"
          style="width: 140px"
          @change="handleSearch"
        >
          <el-option label="正常数据" :value="0" />
          <el-option label="已删除" :value="1" />
          <el-option label="全部数据" :value="2" />
        </el-select>
      </el-form-item>

      <el-form-item label="时间范围">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          style="width: 240px"
          @change="handleSearch"
        />
      </el-form-item>

      <el-form-item class="search-buttons">
        <el-button type="primary" @click="handleSearch">
          <font-awesome-icon :icon="['fas', 'search']" class="mr-1" />
          搜索
        </el-button>
        <el-button @click="handleReset">
          <font-awesome-icon :icon="['fas', 'redo']" class="mr-1" />
          重置
        </el-button>
      </el-form-item>
    </el-form>

    <!-- 统计卡片 -->
    <div class="stats-container">
      <div class="stat-item">
        <div class="stat-icon stat-icon-blue">
          <font-awesome-icon :icon="['fas', 'envelope']" />
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ statistics.total_records || 0 }}</div>
          <div class="stat-label">总记录数</div>
        </div>
      </div>

      <div class="stat-item">
        <div class="stat-icon stat-icon-purple">
          <font-awesome-icon :icon="['fas', 'paper-plane']" />
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ statistics.total_emails || 0 }}</div>
          <div class="stat-label">总邮件数</div>
        </div>
      </div>

      <div class="stat-item">
        <div class="stat-icon stat-icon-green">
          <font-awesome-icon :icon="['fas', 'check-circle']" />
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ statistics.success_emails || 0 }}</div>
          <div class="stat-label">成功发送</div>
        </div>
      </div>

      <div class="stat-item">
        <div class="stat-icon stat-icon-orange">
          <font-awesome-icon :icon="['fas', 'chart-line']" />
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ statistics.success_rate || 0 }}%</div>
          <div class="stat-label">成功率</div>
        </div>
      </div>
    </div>

    <!-- 操作栏 -->
    <div class="action-toolbar">
      <div class="left-actions">
        <el-button
          type="danger"
          size="default"
          :disabled="selectedIds.length === 0"
          @click="handleBatchDelete"
        >
          <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" />
          批量删除
        </el-button>
      </div>
      <div class="right-actions">
        <el-button size="default" @click="loadData">
          <font-awesome-icon :icon="['fas', 'sync-alt']" class="mr-1" />
          刷新
        </el-button>
      </div>
    </div>

    <!-- 表格 -->
    <el-table
      v-loading="loading"
      :data="tableData"
      stripe
      border
      class="email-table"
      @selection-change="handleSelectionChange"
    >
      <el-table-column type="selection" width="55" align="center" />
      <el-table-column prop="id" label="ID" width="80" align="center" />

      <el-table-column
        prop="title"
        label="邮件标题"
        min-width="200"
        show-overflow-tooltip
      >
        <template v-slot="scope">
          <div class="title-cell">
            <font-awesome-icon
              :icon="['fas', 'envelope']"
              class="mr-1"
              style="color: #409eff"
            />
            <span>{{ scope.row.title }}</span>
          </div>
        </template>
      </el-table-column>

      <el-table-column
        prop="sender_name"
        label="发送者"
        width="120"
        align="center"
      />

      <el-table-column
        prop="receiver_type_text"
        label="接收方式"
        width="140"
        align="center"
      >
        <template v-slot="scope">
          <el-tag
            :type="getReceiverTypeTag(scope.row.receiver_type)"
            size="small"
          >
            {{
              scope.row.receiver_type_text ||
              RECEIVER_TYPE_TEXT[scope.row.receiver_type] ||
              "未知"
            }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="发送统计" width="200" align="center">
        <template v-slot="scope">
          <div class="stats-cell">
            <el-tag type="info" size="small">
              总: {{ scope.row.total_count }}
            </el-tag>
            <el-tag type="success" size="small">
              成功: {{ scope.row.success_count }}
            </el-tag>
            <el-tag type="danger" size="small">
              失败: {{ scope.row.failed_count }}
            </el-tag>
          </div>
        </template>
      </el-table-column>

      <el-table-column
        prop="status_text"
        label="发送状态"
        width="120"
        align="center"
      >
        <template v-slot="scope">
          <el-tag :type="getStatusTag(scope.row.status)" size="small">
            {{
              scope.row.status_text || STATUS_TEXT[scope.row.status] || "未知"
            }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column
        prop="send_time"
        label="发送时间"
        width="180"
        align="center"
      />

      <el-table-column label="操作" width="240" align="center" fixed="right">
        <template v-slot="scope">
          <el-button
            type="primary"
            size="small"
            link
            @click="handleViewDetail(scope.row)"
          >
            <font-awesome-icon :icon="['fas', 'eye']" class="mr-1" />
            查看详情
          </el-button>

          <el-button
            v-if="scope.row.failed_count > 0 && !scope.row.delete_time"
            type="warning"
            size="small"
            link
            @click="handleResend(scope.row)"
          >
            <font-awesome-icon :icon="['fas', 'redo']" class="mr-1" />
            重新发送
          </el-button>

          <el-button
            v-if="!scope.row.delete_time"
            type="danger"
            size="small"
            link
            @click="handleDelete(scope.row)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" />
            删除
          </el-button>

          <el-button
            v-if="scope.row.delete_time"
            type="success"
            size="small"
            link
            @click="handleRestore(scope.row)"
          >
            <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />
            恢复
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 分页 -->
    <div class="pagination-container">
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.page_size"
        :page-sizes="[5, 10, 20, 30]"
        :total="pagination.total"
        layout="total, sizes, prev, pager, next, jumper"
        size="small"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- 邮件详情弹窗 -->
    <EmailRecordDetail
      v-model:visible="detailVisible"
      :record-id="currentRecordId"
      @refresh="loadData"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessageBox } from "element-plus";
import { message } from "@/utils/message";
import {
  getEmailRecordList,
  deleteEmailRecord,
  batchDeleteEmailRecord,
  restoreEmailRecord,
  resendEmail,
  getEmailStatistics,
  type EmailRecordData,
  type EmailStatistics
} from "@/api/emailRecord";
import EmailRecordDetail from "./EmailRecordDetail.vue";

// 接收方式映射
const RECEIVER_TYPE_TEXT: Record<number, string> = {
  1: "全部用户",
  2: "指定多个用户",
  3: "单个用户",
  4: "指定邮箱"
};

// 发送状态映射
const STATUS_TEXT: Record<number, string> = {
  0: "待发送",
  1: "发送中",
  2: "发送完成",
  3: "部分失败",
  4: "全部失败"
};

// 搜索表单
const searchForm = reactive({
  title: "",
  status: undefined as number | undefined,
  receiver_type: undefined as number | undefined,
  include_deleted: 0 // 0=不包含软删除(默认), 1=仅软删除, 2=包含所有
});

// 日期范围
const dateRange = ref<[Date, Date] | null>(null);

// 表格数据
const loading = ref(false);
const tableData = ref<EmailRecordData[]>([]);
const selectedIds = ref<number[]>([]);

// 分页
const pagination = reactive({
  page: 1,
  page_size: 5,
  total: 0
});

// 统计数据
const statistics = ref<EmailStatistics>({
  total_records: 0,
  total_emails: 0,
  success_emails: 0,
  failed_emails: 0,
  success_rate: 0
});

// 详情弹窗
const detailVisible = ref(false);
const currentRecordId = ref<number>(0);

// 加载数据
const loadData = async () => {
  loading.value = true;
  try {
    const params: any = {
      page: pagination.page,
      page_size: pagination.page_size,
      ...searchForm
    };

    // 添加时间范围
    if (dateRange.value && dateRange.value.length === 2) {
      params.start_time = dateRange.value[0].toISOString().split("T")[0];
      params.end_time = dateRange.value[1].toISOString().split("T")[0];
    }

    const res = (await getEmailRecordList(params)) as any;

    if (res.code === 200) {
      tableData.value = res.data.data || [];
      pagination.total = res.data.total || 0;
    }
  } catch (error) {
    console.error("加载邮件记录失败:", error);
    message("加载数据失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

// 加载统计数据
const loadStatistics = async () => {
  try {
    const params: any = {};

    if (dateRange.value && dateRange.value.length === 2) {
      params.start_time = dateRange.value[0].toISOString().split("T")[0];
      params.end_time = dateRange.value[1].toISOString().split("T")[0];
    }

    const res = (await getEmailStatistics(params)) as any;

    if (res.code === 200 && res.data) {
      statistics.value = res.data;
    }
  } catch (error) {
    console.error("加载统计数据失败:", error);
  }
};

// 搜索
const handleSearch = () => {
  pagination.page = 1;
  loadData();
  loadStatistics();
};

// 重置
const handleReset = () => {
  searchForm.title = "";
  searchForm.status = undefined;
  searchForm.receiver_type = undefined;
  searchForm.include_deleted = 0;
  dateRange.value = null;
  handleSearch();
};

// 选择变化
const handleSelectionChange = (selection: EmailRecordData[]) => {
  selectedIds.value = selection.map(item => item.id);
};

// 分页变化
const handleSizeChange = (size: number) => {
  pagination.page_size = size;
  loadData();
};

const handlePageChange = (page: number) => {
  pagination.page = page;
  loadData();
};

// 查看详情
const handleViewDetail = (row: EmailRecordData) => {
  currentRecordId.value = row.id;
  detailVisible.value = true;
};

// 重新发送
const handleResend = async (row: EmailRecordData) => {
  try {
    await ElMessageBox.confirm(
      `确定要重新发送失败的邮件吗？将重新发送 ${row.failed_count} 封失败的邮件。`,
      "重新发送确认",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const res = (await resendEmail({ record_id: row.id })) as any;

    if (res.code === 200) {
      message("重新发送成功", { type: "success" });
      loadData();
      loadStatistics();
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

// 删除
const handleDelete = async (row: EmailRecordData) => {
  try {
    await ElMessageBox.confirm("确定要删除这条邮件记录吗？", "删除确认", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    });

    const res = (await deleteEmailRecord(row.id)) as any;

    if (res.code === 200) {
      message("删除成功", { type: "success" });
      loadData();
      loadStatistics();
    } else {
      message(res.message || "删除失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("删除失败:", error);
      message("删除失败", { type: "error" });
    }
  }
};

// 恢复
const handleRestore = async (row: EmailRecordData) => {
  try {
    await ElMessageBox.confirm("确定要恢复这条邮件记录吗？", "恢复确认", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "success"
    });

    const res = (await restoreEmailRecord(row.id)) as any;

    if (res.code === 200) {
      message("恢复成功", { type: "success" });
      loadData();
      loadStatistics();
    } else {
      message(res.message || "恢复失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("恢复失败:", error);
      message("恢复失败", { type: "error" });
    }
  }
};

// 批量删除
const handleBatchDelete = async () => {
  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedIds.value.length} 条记录吗？`,
      "批量删除确认",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const res = (await batchDeleteEmailRecord(selectedIds.value)) as any;

    if (res.code === 200) {
      message("批量删除成功", { type: "success" });
      selectedIds.value = [];
      loadData();
      loadStatistics();
    } else {
      message(res.message || "批量删除失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("批量删除失败:", error);
      message("批量删除失败", { type: "error" });
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

// 获取状态标签类型
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

// 初始化
onMounted(() => {
  loadData();
  loadStatistics();
});
</script>

<style lang="scss" scoped>
.email-record-container {
  padding: 0;

  .search-form {
    margin-bottom: 12px;

    :deep(.el-form-item) {
      margin-right: 12px;
      margin-bottom: 12px;
    }

    :deep(.el-form-item__label) {
      font-size: 13px;
      font-weight: 500;
      color: #606266;
    }

    .search-buttons {
      margin-top: 0;
    }
  }

  .stats-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 12px;
  }

  .stat-item {
    display: flex;
    flex: 1;
    gap: 10px;
    align-items: center;
    min-width: 180px;
    padding: 10px 12px;
    background: white;
    border: 1px solid #ebeef5;
    border-radius: 6px;
    box-shadow: 0 1px 2px rgb(0 0 0 / 4%);

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

    .stat-icon-blue {
      background: #409eff;
    }

    .stat-icon-purple {
      background: #7c3aed;
    }

    .stat-icon-green {
      background: #67c23a;
    }

    .stat-icon-orange {
      background: #f59e0b;
    }

    .stat-info {
      flex: 1;

      .stat-value {
        margin-bottom: 2px;
        font-size: 20px;
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

  .action-toolbar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;

    .left-actions,
    .right-actions {
      display: flex;
      gap: 8px;
    }
  }

  .email-table {
    margin-bottom: 12px;

    .title-cell {
      display: flex;
      align-items: center;
    }

    .stats-cell {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      justify-content: center;
    }
  }

  .pagination-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
  }
}

@media (width <= 768px) {
  .email-record-container {
    .search-area {
      padding: 8px 8px 0;
    }

    .stats-container {
      flex-direction: column;
      gap: 8px;
    }

    .stat-item {
      min-width: auto;
      padding: 10px;

      .stat-icon {
        width: 32px;
        height: 32px;
        font-size: 14px;
      }

      .stat-info {
        .stat-value {
          font-size: 18px;
        }

        .stat-label {
          font-size: 11px;
        }
      }
    }

    .email-table :deep(.el-table__header),
    .email-table :deep(.el-table__body) {
      font-size: 12px;
    }

    .pagination-container :deep(.el-pagination) {
      justify-content: center;
    }
  }
}
</style>
