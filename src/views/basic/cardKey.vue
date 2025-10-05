<template>
  <div class="card-key-container">
    <!-- 顶部统计卡片 -->
    <div class="stats-container">
      <el-card class="stat-card" shadow="hover">
        <div class="stat-content">
          <div class="stat-icon stat-icon-total">
            <IconifyIconOnline icon="ep:data-line" />
          </div>
          <div class="stat-info">
            <div class="stat-label">总数</div>
            <div class="stat-value">{{ pagination.total }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover">
        <div class="stat-content">
          <div class="stat-icon stat-icon-unused">
            <IconifyIconOnline icon="ep:tickets" />
          </div>
          <div class="stat-info">
            <div class="stat-label">未使用</div>
            <div class="stat-value stat-value-unused">{{ statsData.unused }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover">
        <div class="stat-content">
          <div class="stat-icon stat-icon-used">
            <IconifyIconOnline icon="ep:circle-check" />
          </div>
          <div class="stat-info">
            <div class="stat-label">已使用</div>
            <div class="stat-value stat-value-used">{{ statsData.used }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover">
        <div class="stat-content">
          <div class="stat-icon stat-icon-disabled">
            <IconifyIconOnline icon="ep:circle-close" />
          </div>
          <div class="stat-info">
            <div class="stat-label">已禁用</div>
            <div class="stat-value stat-value-disabled">{{ statsData.disabled }}</div>
          </div>
        </div>
      </el-card>
    </div>

    <!-- 主内容卡片 -->
    <el-card class="main-card" shadow="never">
      <!-- Tab切换 -->
      <el-tabs v-model="activeTab" class="card-key-tabs">
        <!-- 卡密列表Tab -->
        <el-tab-pane label="卡密列表" name="cardKeys">
      <!-- 搜索和操作栏 -->
      <el-row :gutter="8" align="middle">
        <!-- 类型筛选 -->
        <el-col :xs="24" :sm="8" :md="5" :lg="4">
          <el-select v-model="searchForm.type_id" placeholder="类型" clearable filterable size="small" @change="handleSearch">
            <el-option v-for="type in typeOptions" :key="type.id" :label="type.type_name" :value="type.id" />
          </el-select>
        </el-col>

        <!-- 状态筛选 -->
        <el-col :xs="24" :sm="8" :md="5" :lg="4">
          <el-select v-model="searchForm.status" placeholder="状态" clearable size="small" @change="handleSearch">
            <el-option label="未使用" :value="0" />
            <el-option label="已使用" :value="1" />
            <el-option label="已禁用" :value="2" />
          </el-select>
        </el-col>

        <!-- 关键词搜索 -->
        <el-col :xs="24" :sm="8" :md="6" :lg="5">
          <el-input v-model="searchForm.card_key" placeholder="搜索卡密" clearable size="small" @keyup.enter="handleSearch">
            <template #prefix>
              <IconifyIconOnline icon="ep:search" />
            </template>
          </el-input>
        </el-col>

        <!-- 搜索按钮 -->
        <el-col :xs="12" :sm="6" :md="4" :lg="3">
          <el-button type="primary" size="small" @click="handleSearch">
            <IconifyIconOnline icon="ep:search" />
            搜索
          </el-button>
        </el-col>

        <el-col :xs="12" :sm="6" :md="4" :lg="2">
          <el-button size="small" @click="handleReset">
            <IconifyIconOnline icon="ep:refresh" />
            重置
          </el-button>
        </el-col>

        <!-- 右侧操作按钮 -->
        <el-col :xs="24" :sm="24" :md="24" :lg="6" class="action-buttons">
          <el-button type="primary" size="small" @click="handleGenerate">
            <IconifyIconOnline icon="ep:plus" />
            生成
          </el-button>
          <el-button type="danger" size="small" :disabled="selectedIds.length === 0" @click="handleBatchDelete">
            <IconifyIconOnline icon="ep:delete" />
            删除
          </el-button>
          <el-button type="success" size="small" @click="handleExport">
            <IconifyIconOnline icon="ep:download" />
            导出
          </el-button>
        </el-col>
      </el-row>

      <!-- 数据表格 -->
      <!-- 数据表格 -->
      <el-table 
        :data="tableData" 
        v-loading="loading" 
        style="width: 100%; margin-top: 16px"
        @selection-change="handleSelectionChange" 
        :header-cell-style="{ background: '#f5f7fa', color: '#606266' }"
        stripe>
        <el-table-column type="selection" width="45" align="center" fixed />
        <el-table-column prop="id" label="ID" width="60" align="center" />

        <!-- 卡密码列 -->
        <el-table-column prop="card_key" label="卡密码" min-width="180" align="center">
          <template #default="{ row }">
            <div class="code-cell">
              <el-tag type="" effect="light" class="code-tag">
                {{ row.card_key || row.code }}
              </el-tag>
              <el-tooltip content="复制" placement="top">
                <el-icon class="copy-icon" @click="handleCopyCode(row.card_key || row.code)">
                  <IconifyIconOnline icon="ep:document-copy" />
                </el-icon>
              </el-tooltip>
            </div>
          </template>
        </el-table-column>

        <!-- 类型列 -->
        <el-table-column prop="type" label="类型" width="110" align="center">
          <template #default="{ row }">
            <el-tag type="" size="small" effect="light">
              {{ row.cardType?.type_name || row.type || '-' }}
            </el-tag>
          </template>
        </el-table-column>

        <!-- 状态列 -->
        <el-table-column prop="status" label="状态" width="85" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small" effect="light">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>

        <!-- 价格列 -->
        <el-table-column prop="price" label="价格" width="85" align="center">
          <template #default="{ row }">
            <span v-if="row.cardType?.price !== null && row.cardType?.price !== undefined" class="price-text">¥{{ row.cardType.price }}</span>
            <span v-else class="empty-text">-</span>
          </template>
        </el-table-column>

        <!-- 会员时长列 -->
        <el-table-column prop="membership_duration" label="赠送时长" width="130" align="center">
          <template #default="{ row }">
            <span v-if="row.cardType?.membership_duration === null || row.cardType?.membership_duration === undefined">-</span>
            <el-tag v-else-if="row.cardType?.membership_duration === 0" type="success" size="small">
              <IconifyIconOnline icon="ep:trophy" />永久
            </el-tag>
            <span v-else>{{ formatMembershipDuration(row.cardType.membership_duration) }}</span>
          </template>
        </el-table-column>

        <!-- 兑换期限列 -->
        <el-table-column prop="expire_time" label="兑换期限" width="180" align="center">
          <template #default="{ row }">
            <el-tag v-if="!row.expire_time || row.expire_time === '0000-00-00 00:00:00'" type="success" size="small">
              <IconifyIconOnline icon="ep:timer" />永久可用
            </el-tag>
            <span v-else :class="isAvailableExpired(row.expire_time) ? 'expired-text' : ''">
              <el-icon v-if="isAvailableExpired(row.expire_time)">
                <IconifyIconOnline icon="ep:warning" />
              </el-icon>
              {{ formatDateTime(row.expire_time) }}
            </span>
          </template>
        </el-table-column>

        <!-- 创建时间列 -->
        <el-table-column prop="create_time" label="创建时间" width="155" align="center">
          <template #default="{ row }">
            <span class="time-text">{{ row.create_time }}</span>
          </template>
        </el-table-column>

        <!-- 使用时间列 -->
        <el-table-column prop="use_time" label="使用时间" width="155" align="center">
          <template #default="{ row }">
            <span v-if="row.use_time" class="time-text">{{ row.use_time }}</span>
            <span v-else class="empty-text">-</span>
          </template>
        </el-table-column>

        <!-- 操作列 -->
        <el-table-column label="操作" fixed="right" width="140" align="center">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleDetail(row)">
              <IconifyIconOnline icon="ep:view" />详情
            </el-button>
            <el-button 
              link 
              type="danger" 
              size="small" 
              @click="handleDelete(row)" 
              :disabled="row.status === 1 || row.status === 2"
            >
              <IconifyIconOnline icon="ep:delete" />删除
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
        </el-tab-pane>

        <!-- 类型管理Tab -->
        <el-tab-pane label="类型管理" name="cardTypes">
          <TypeManage />
        </el-tab-pane>
      </el-tabs>
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
  formatMembershipDuration,
  type CardKey,
  type CardKeyListParams
} from "@/api/cardKey";
import GenerateDialog from "./cardKey/components/GenerateDialog.vue";
import DetailDialog from "./cardKey/components/DetailDialog.vue";
import TypeManage from "./cardKey/components/TypeManage.vue";
import { IconifyIconOnline } from "@/components/ReIcon";

// 定义响应式数据
const activeTab = ref("cardKeys");
const loading = ref(false);
const tableData = ref<CardKey[]>([]);
const selectedIds = ref<number[]>([]);
const typeOptions = ref<any[]>([]);
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
  type_id: undefined,
  status: "",
  card_key: "",
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
  searchForm.type_id = undefined;
  searchForm.status = "";
  searchForm.card_key = "";
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
      `确定要删除卡密 ${row.card_key || row.code} 吗？`,
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

/**
 * 判断兑换期限是否过期
 */
const isAvailableExpired = (availableTime: string): boolean => {
  if (!availableTime) return false;
  return new Date(availableTime).getTime() < Date.now();
};

/**
 * 格式化日期时间
 */
const formatDateTime = (datetime: string): string => {
  if (!datetime) return "-";
  return datetime.replace(" ", " ");
};

/**
 * 复制卡密码
 */
const handleCopyCode = async (code: string) => {
  try {
    await navigator.clipboard.writeText(code);
    message("复制成功", { type: "success" });
  } catch (error) {
    // 降级处理：使用传统方法
    const textarea = document.createElement("textarea");
    textarea.value = code;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    document.body.removeChild(textarea);
    message("复制成功", { type: "success" });
  }
};

// 组件挂载时获取数据
onMounted(() => {
  fetchList();
  fetchTypes();
});
</script>

<style scoped lang="scss">
.card-key-container {
  padding: 16px;

  // 统计卡片容器
  .stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
    margin-bottom: 16px;

    .stat-card {
      border-radius: 10px;
      transition: all 0.3s ease;
      border: 1px solid #ebeef5;

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      }

      :deep(.el-card__body) {
        padding: 18px;
      }

      .stat-content {
        display: flex;
        align-items: center;
        gap: 14px;

        .stat-icon {
          width: 48px;
          height: 48px;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 10px;
          font-size: 24px;
          flex-shrink: 0;

          &.stat-icon-total {
            background: #ecf5ff;
            color: #409eff;
          }

          &.stat-icon-unused {
            background: #f0f9ff;
            color: #67c23a;
          }

          &.stat-icon-used {
            background: #e1f3fb;
            color: #409eff;
          }

          &.stat-icon-disabled {
            background: #fef0f0;
            color: #f56c6c;
          }
        }

        .stat-info {
          flex: 1;
          min-width: 0;

          .stat-label {
            font-size: 13px;
            color: #909399;
            margin-bottom: 6px;
          }

          .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #303133;
            line-height: 1;

            &.stat-value-unused {
              color: #67c23a;
            }

            &.stat-value-used {
              color: #409eff;
            }

            &.stat-value-disabled {
              color: #f56c6c;
            }
          }
        }
      }
    }
  }

  // 主容器
  .main-card {
    border-radius: 12px;

    :deep(.el-card__body) {
      padding: 20px;
    }
  }

  // 表格样式增强
  .code-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    .code-tag {
      font-family: "Courier New", monospace;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.5px;
      padding: 5px 12px;
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      border: 1px solid #fbbf24;
      color: #92400e;
    }

    .copy-icon {
      cursor: pointer;
      color: #909399;
      font-size: 16px;
      transition: all 0.3s ease;

      &:hover {
        color: #409eff;
        transform: scale(1.15);
      }
    }
  }

  .warning-icon {
    color: #f56c6c;
  }


  .price-text {
    color: #f56c6c;
  }

  .expired-text {
    color: #f56c6c;
  }

  // 输入框宽度
  .el-select,
  .el-input {
    width: 100%;
  }

  // 操作按钮区域
  .action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 8px;

    @media (max-width: 1200px) {
      justify-content: flex-start;
      margin-top: 8px;
    }
  }

  // 分页区域
  .pagination-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
  }

  // 响应式适配
  @media (max-width: 768px) {
    padding: 12px;

    .stats-container {
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;

      .stat-card {
        :deep(.el-card__body) {
          padding: 14px;
        }

        .stat-content {
          gap: 10px;

          .stat-icon {
            width: 42px;
            height: 42px;
            font-size: 20px;
          }

          .stat-info {
            .stat-value {
              font-size: 20px;
            }

            .stat-label {
              font-size: 12px;
            }
          }
        }
      }
    }

    .action-buttons {
      .el-button {
        flex: 1;
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
    .stats-container {
      .stat-card {
        background: var(--el-bg-color);
        border-color: var(--el-border-color);

        .stat-content {
          .stat-icon {
            &.stat-icon-total {
              background: rgba(64, 158, 255, 0.1);
            }

            &.stat-icon-unused {
              background: rgba(103, 194, 58, 0.1);
            }

            &.stat-icon-used {
              background: rgba(64, 158, 255, 0.1);
            }

            &.stat-icon-disabled {
              background: rgba(245, 108, 108, 0.1);
            }
          }

          .stat-info {
            .stat-label {
              color: var(--el-text-color-secondary);
            }

            .stat-value {
              color: var(--el-text-color-primary);

              &.stat-value-unused {
                color: #67c23a;
              }

              &.stat-value-used {
                color: #409eff;
              }

              &.stat-value-disabled {
                color: #f56c6c;
              }
            }
          }
        }
      }
    }

    .main-card {
      background: var(--el-bg-color);
      border-color: var(--el-border-color);
    }

    .code-text,
    .time-text,
    .duration-text {
      color: var(--el-text-color-primary);
    }
  }
}
</style>
