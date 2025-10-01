<template>
  <div class="card-key-container">
    <!-- 顶部统计栏 -->
    <div class="stats-bar animate__animated animate__fadeInDown">
      <div class="stat-item">
        <span class="stat-label">总数</span>
        <span class="stat-value">{{ pagination.total }}</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-label">未使用</span>
        <span class="stat-value stat-unused">{{ statsData.unused }}</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-label">已使用</span>
        <span class="stat-value stat-used">{{ statsData.used }}</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-label">已禁用</span>
        <span class="stat-value stat-disabled">{{ statsData.disabled }}</span>
      </div>
    </div>

    <!-- 搜索和操作栏合并 -->
    <el-card class="search-toolbar-card animate__animated animate__fadeInUp" shadow="never">
      <el-row :gutter="12" align="middle">
        <!-- 类型筛选 -->
        <el-col :xs="24" :sm="8" :md="5" :lg="4">
          <el-select v-model="searchForm.type" placeholder="类型" clearable filterable size="default" @change="handleSearch">
            <el-option v-for="type in typeOptions" :key="type" :label="type" :value="type" />
          </el-select>
        </el-col>

        <!-- 状态筛选 -->
        <el-col :xs="24" :sm="8" :md="5" :lg="4">
          <el-select v-model="searchForm.status" placeholder="状态" clearable size="default" @change="handleSearch">
            <el-option label="未使用" :value="0" />
            <el-option label="已使用" :value="1" />
            <el-option label="已禁用" :value="2" />
          </el-select>
        </el-col>

        <!-- 关键词搜索 -->
        <el-col :xs="24" :sm="8" :md="6" :lg="5">
          <el-input v-model="searchForm.code" placeholder="搜索卡密" clearable size="default" @keyup.enter="handleSearch">
            <template #prefix>
              <IconifyIconOnline icon="ep:search" />
            </template>
          </el-input>
        </el-col>

        <!-- 搜索按钮 -->
        <el-col :xs="12" :sm="6" :md="4" :lg="3">
          <el-button type="primary" size="default" @click="handleSearch">
            <IconifyIconOnline icon="ep:search" />
            搜索
          </el-button>
        </el-col>

        <el-col :xs="12" :sm="6" :md="4" :lg="2">
          <el-button size="default" @click="handleReset">
            <IconifyIconOnline icon="ep:refresh" />
            重置
          </el-button>
        </el-col>

        <!-- 右侧操作按钮 -->
        <el-col :xs="24" :sm="24" :md="24" :lg="6" class="action-buttons">
          <el-button type="primary" size="default" @click="handleGenerate">
            <IconifyIconOnline icon="ep:plus" />
            生成
          </el-button>
          <el-button type="danger" size="default" :disabled="selectedIds.length === 0" @click="handleBatchDelete">
            <IconifyIconOnline icon="ep:delete" />
            删除
          </el-button>
          <el-button type="success" size="default" @click="handleExport">
            <IconifyIconOnline icon="ep:download" />
            导出
          </el-button>
        </el-col>
      </el-row>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card animate__animated animate__fadeIn" shadow="never">
      <el-table :data="tableData" v-loading="loading" size="small" style="width: 100%"
        @selection-change="handleSelectionChange" stripe border>
        <el-table-column type="selection" width="40" align="center" />
        <el-table-column prop="id" label="ID" width="50" align="center" />

        <!-- 卡密码列 -->
        <el-table-column prop="code" label="卡密码" min-width="140" align="center">
          <template #default="{ row }">
            <span style="font-family: 'Courier New', monospace;">
              {{ row.code }}
            </span>
          </template>
        </el-table-column>

        <!-- 类型列 -->
        <el-table-column prop="type" label="类型" width="100" align="center" />

        <!-- 状态列 -->
        <el-table-column prop="status" label="状态" width="70" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>

        <!-- 价格列 -->
        <el-table-column prop="price" label="价格" width="70" align="center">
          <template #default="{ row }">
            <span v-if="row.price">¥{{ row.price }}</span>
            <span v-else style="color: #909399;">-</span>
          </template>
        </el-table-column>

        <!-- 有效时长列 -->
        <el-table-column prop="valid_minutes" label="有效期" width="80" align="center">
          <template #default="{ row }">
            <span v-if="row.valid_minutes === 0" style="color: #67c23a;">永久</span>
            <span v-else>{{ formatValidMinutes(row.valid_minutes) }}</span>
          </template>
        </el-table-column>

        <!-- 创建时间列 -->
        <el-table-column prop="create_time" label="创建时间" width="140" align="center" />

        <!-- 使用时间列 -->
        <el-table-column prop="use_time" label="使用时间" width="140" align="center">
          <template #default="{ row }">
            <span v-if="row.use_time">{{ row.use_time }}</span>
            <span v-else style="color: #c0c4cc;">-</span>
          </template>
        </el-table-column>

        <!-- 操作列 -->
        <el-table-column label="操作" fixed="right" width="120" align="center">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleDetail(row)">
              详情
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)" :disabled="row.status === 1">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.currentPage"
          v-model:page-size="pagination.pageSize"
          :page-sizes="pagination.pageSizes"
          :background="true"
          layout="total, sizes, prev, pager, next, jumper"
          :total="pagination.total"
          size="small"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 生成对话框 -->
    <GenerateDialog v-model:visible="generateDialogVisible" @success="handleSearch" />

    <!-- 详情对话框 -->
    <DetailDialog v-model:visible="detailDialogVisible" :card-key-id="currentCardKeyId" />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessageBox } from "element-plus";
import { message } from "@/utils/message";
import {
  getCardKeyList,
  deleteCardKey,
  batchDeleteCardKey,
  exportCardKeys,
  getCardKeyTypes,
  CardKeyStatus,
  CardKeyStatusMap,
  CardKeyStatusTypeMap,
  formatValidMinutes,
  type CardKey,
  type CardKeyListParams
} from "@/api/cardKey";
import GenerateDialog from "./cardKey/components/GenerateDialog.vue";
import DetailDialog from "./cardKey/components/DetailDialog.vue";
import { IconifyIconOnline } from "@/components/ReIcon";

// 定义响应式数据
const loading = ref(false);
const tableData = ref<CardKey[]>([]);
const selectedIds = ref<number[]>([]);
const typeOptions = ref<string[]>([]);
const generateDialogVisible = ref(false);
const detailDialogVisible = ref(false);
const currentCardKeyId = ref<number>(0);

// 统计数据
const statsData = reactive({
  unused: 0,
  used: 0,
  disabled: 0
});

// 搜索表单
const searchForm = reactive<CardKeyListParams>({
  type: "",
  status: "",
  code: "",
  page: 1,
  limit: 5
});

// 分页配置
const pagination = reactive({
  total: 0,
  pageSize: 5,
  currentPage: 1,
  pageSizes: [5, 10, 20, 30, 50]
});

/**
 * 获取卡密列表
 */
const fetchList = async () => {
  loading.value = true;
  try {
    const params = {
      ...searchForm,
      page: pagination.currentPage,
      limit: pagination.pageSize
    };
    const response = await getCardKeyList(params);

    // axios拦截器已经解包了一层，response直接就是后端返回的数据
    if (response.code === 200) {
      tableData.value = response.data.list || [];
      pagination.total = response.data.total || 0;

      // 计算统计数据
      statsData.unused = tableData.value.filter(item => item.status === 0).length;
      statsData.used = tableData.value.filter(item => item.status === 1).length;
      statsData.disabled = tableData.value.filter(item => item.status === 2).length;
    } else {
      message(response.message || "获取列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取列表错误:", error);
    message("获取列表失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

/**
 * 获取类型列表
 */
const fetchTypes = async () => {
  try {
    const response = await getCardKeyTypes();
    if (response.code === 200) {
      typeOptions.value = response.data || [];
    }
  } catch (error) {
    console.error("获取类型列表失败", error);
  }
};

/**
 * 搜索
 */
const handleSearch = () => {
  pagination.currentPage = 1;
  fetchList();
};

/**
 * 重置
 */
const handleReset = () => {
  searchForm.type = "";
  searchForm.status = "";
  searchForm.code = "";
  handleSearch();
};

/**
 * 选择变化
 */
const handleSelectionChange = (selection: CardKey[]) => {
  selectedIds.value = selection.map(item => item.id);
};

/**
 * 分页大小变化
 */
const handleSizeChange = (size: number) => {
  pagination.pageSize = size;
  fetchList();
};

/**
 * 当前页变化
 */
const handleCurrentChange = (page: number) => {
  pagination.currentPage = page;
  fetchList();
};

/**
 * 生成卡密
 */
const handleGenerate = () => {
  generateDialogVisible.value = true;
};

/**
 * 查看详情
 */
const handleDetail = (row: CardKey) => {
  currentCardKeyId.value = row.id;
  detailDialogVisible.value = true;
};

/**
 * 删除卡密
 */
const handleDelete = async (row: CardKey) => {
  if (row.status === CardKeyStatus.USED) {
    message("已使用的卡密不允许删除", { type: "warning" });
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除卡密 ${row.code} 吗？`,
      "删除确认",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const response = await deleteCardKey(row.id);
    if (response.code === 200) {
      message("删除成功", { type: "success" });
      fetchList();
    } else {
      message(response.message || "删除失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      message("删除失败", { type: "error" });
    }
  }
};

/**
 * 批量删除
 */
const handleBatchDelete = async () => {
  if (selectedIds.value.length === 0) {
    message("请选择要删除的卡密", { type: "warning" });
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedIds.value.length} 个卡密吗？`,
      "批量删除确认",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const response = await batchDeleteCardKey(selectedIds.value);
    if (response.code === 200) {
      message("批量删除成功", { type: "success" });
      selectedIds.value = [];
      fetchList();
    } else {
      message(response.message || "批量删除失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      message("批量删除失败", { type: "error" });
    }
  }
};

/**
 * 导出数据
 */
const handleExport = async () => {
  try {
    const params = { ...searchForm };
    const { data } = await exportCardKeys(params);

    // 创建下载链接
    const blob = new Blob([data], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `cardkeys_${new Date().getTime()}.csv`;
    link.click();
    window.URL.revokeObjectURL(url);

    message("导出成功", { type: "success" });
  } catch (error) {
    message("导出失败", { type: "error" });
  }
};

/**
 * 统计数据
 */
const handleStatistics = () => {
  // TODO: 打开统计对话框
  message("统计功能开发中", { type: "info" });
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

// 组件挂载时获取数据
onMounted(() => {
  fetchList();
  fetchTypes();
});
</script>

<style scoped lang="scss">
.card-key-container {
  padding: 12px;

  // 统计栏 - 扁平化设计
  .stats-bar {
    display: flex;
    align-items: center;
    justify-content: space-around;
    background: #fff;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    padding: 12px 16px;
    margin-bottom: 12px;

    .stat-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;

      .stat-label {
        font-size: 11px;
        color: #909399;
      }

      .stat-value {
        font-size: 18px;
        font-weight: 500;
        color: #303133;

        &.stat-unused {
          color: #67c23a;
        }

        &.stat-used {
          color: #409eff;
        }

        &.stat-disabled {
          color: #f56c6c;
        }
      }
    }

    .stat-divider {
      width: 1px;
      height: 28px;
      background: #dcdfe6;
    }
  }

  // 搜索和工具栏卡片
  .search-toolbar-card {
    margin-bottom: 12px;

    :deep(.el-card__body) {
      padding: 12px;
    }

    .el-select,
    .el-input {
      width: 100%;
    }

    .action-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 8px;

      @media (max-width: 1200px) {
        justify-content: flex-start;
        margin-top: 8px;
      }
    }
  }

  // 表格卡片
  .table-card {
    margin-bottom: 12px;

    :deep(.el-card__body) {
      padding: 12px;
    }

    .pagination-wrapper {
      display: flex;
      justify-content: flex-end;
      margin-top: 12px;
    }
  }

  // 响应式适配
  @media (max-width: 768px) {
    padding: 10px;

    .stats-bar {
      flex-wrap: wrap;
      gap: 12px;
      padding: 12px;

      .stat-item {
        flex: 1;
        min-width: 80px;

        .stat-value {
          font-size: 18px;
        }

        .stat-label {
          font-size: 11px;
        }
      }

      .stat-divider {
        display: none;
      }
    }

    .search-toolbar-card {
      .el-button {
        width: 100%;
        margin-bottom: 8px;
      }
    }

    .pagination-wrapper {
      justify-content: center;

      :deep(.el-pagination) {
        flex-wrap: wrap;
      }
    }
  }

  // 暗黑模式适配
  html.dark & {
    .stats-bar {
      background: var(--el-bg-color);
      border-color: var(--el-border-color);

      .stat-value {
        color: var(--el-text-color-primary);
      }
    }

    .search-toolbar-card,
    .table-card {
      background: var(--el-bg-color);
      border-color: var(--el-border-color);
    }
  }
}
</style>
