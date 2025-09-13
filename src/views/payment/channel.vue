<template>
  <div class="payment-channel-container">
    <!-- 添加/编辑对话框 -->
    <el-dialog v-model="showAddOrEditModal" :title="currentPaymentMethod ? '编辑支付渠道' : '添加支付渠道'"
      :before-close="handleClose" @closed="handleDialogClosed" width="700px">
      <AddOrEdit v-if="showAddOrEditModal" :formData="currentPaymentMethod" @submit-success="handleSubmitSuccess" />
    </el-dialog>

    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <template #header>
        <div class="search-header">
          <span class="search-title">筛选条件</span>
        </div>
      </template>
      <el-form :model="searchForm" inline class="search-form">
        <el-form-item label="渠道名称">
          <el-input v-model="searchForm.name" placeholder="请输入渠道名称" clearable size="small" style="width: 140px" />
        </el-form-item>
        <el-form-item label="渠道代码">
          <el-input v-model="searchForm.code" placeholder="请输入渠道代码" clearable size="small" style="width: 140px" />
        </el-form-item>
        <el-form-item label="支付类型">
          <el-select v-model="searchForm.type" placeholder="请选择类型" size="small" style="width: 120px">
            <el-option label="全部" value="" />
            <el-option label="传统支付" value="1" />
            <el-option label="加密货币" value="2" />
            <el-option label="数字钱包" value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择状态" size="small" style="width: 100px">
            <el-option label="全部" value="" />
            <el-option label="启用" value="1" />
            <el-option label="禁用" value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="search" size="small">搜索</el-button>
          <el-button @click="resetSearchForm" size="small">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 主内容区域 -->
    <el-card class="table-card" shadow="never">
      <!-- 操作按钮 -->
      <div class="table-header">
        <div>
          <el-button type="primary" :icon="Plus" @click="handleAdd" size="small">
            新增
          </el-button>
          <el-button type="danger" :icon="Delete" @click="handleBatchDelete" :disabled="selectedIds.length === 0"
            size="small">
            删除
          </el-button>
        </div>
        <div>
          <el-button :icon="Refresh" @click="refreshData" size="small" circle />
        </div>
      </div>

      <!-- 数据表格 -->
      <el-table ref="tableRef" :data="tableData" v-loading="loading" row-key="id"
        @selection-change="handleSelectionChange" size="small" stripe class="compact-table" style="width: 100%">
        <el-table-column type="selection" width="40" align="center" />
        <el-table-column prop="id" label="ID" width="60" align="center" />
        <el-table-column label="图标" width="50" align="center">
          <template #default="{ row }">
            <FontIcon v-if="row.icon" :icon="row.icon" class="payment-icon" />
            <FontIcon v-else icon="fas fa-credit-card" class="payment-icon" />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="渠道名称" min-width="120" show-overflow-tooltip />
        <el-table-column label="代码" width="120" align="center">
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.code }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="区块网络" width="120" align="center">
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.network ? row.network : '-' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)" size="small">
              {{ getTypeText(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="货币" width="100" align="center">
          <template #default="{ row }">
            <div class="currency-info">
              <span>{{ row.currency_symbol }}</span>
              <small>{{ row.currency_code }}</small>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="钱包地址" align="center">
          <template #default="{ row }">
            <div v-if="row.is_crypto === 1 && row.contract_address" class="wallet-address">
              <div class="address-content">
                <el-tooltip :content="row.contract_address" placement="top">
                  <span class="address-text">
                    {{ row.contract_address.slice(0, 8) }}...{{ row.contract_address.slice(-6) }}
                  </span>
                </el-tooltip>
                <el-button size="small" type="primary" link @click="copyAddress(row.contract_address)" class="copy-btn">
                  <FontIcon icon="fas fa-copy" />
                </el-button>
              </div>
            </div>
            <el-tag v-else-if="row.is_crypto === 1" size="small" type="info">
              原生币
            </el-tag>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-switch v-model="row.status" :active-value="1" :inactive-value="0" @change="handleStatusChange(row)"
              size="small" />
          </template>
        </el-table-column>
        <el-table-column label="默认" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_default === 1 ? 'success' : 'info'" size="small">
              {{ row.is_default === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="80" align="center" />
        <el-table-column label="操作" width="200" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)" link>编辑</el-button>
            <el-button v-if="row.is_default === 0" type="warning" size="small" @click="handleSetDefault(row)" link>
              设默认
            </el-button>
            <el-button type="danger" size="small" @click="handleDelete(row)" link>删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination v-model:current-page="pagination.currentPage" v-model:page-size="pagination.pageSize"
          :page-sizes="[5, 10, 20, 50]" :total="pagination.total" layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange" @current-change="handleCurrentChange" size="small" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessageBox } from "element-plus";
import {
  Plus,
  Delete,
  Refresh
} from "@element-plus/icons-vue";
import {
  getPaymentMethodList,
  deletePaymentMethod,
  updatePaymentMethodStatus,
  setDefaultPaymentMethod,
  type PaymentMethod,
  type PaymentMethodSearchForm
} from "@/api/paymentMethod";
import { message } from "@/utils/message";
import { FontIcon } from "@/components/ReIcon";
import AddOrEdit from "./channel/AddOrEdit.vue";

// 扩展PaymentMethod接口以包含UI状态
interface PaymentMethodWithUI extends PaymentMethod {
  // 可以在这里添加其他UI状态
}

// 响应式数据
const tableRef = ref();
const loading = ref(false);
const showAddOrEditModal = ref(false);
const currentPaymentMethod = ref<PaymentMethod | null>(null);
const tableData = ref<PaymentMethodWithUI[]>([]);
const selectedIds = ref<number[]>([]);

// 搜索表单
const searchForm = reactive<PaymentMethodSearchForm>({
  id: "",
  name: "",
  code: "",
  type: "",
  status: "",
  is_crypto: "",
  currency_code: "",
  network: "",
  is_default: ""
});

// 分页信息
const pagination = reactive({
  total: 0,
  currentPage: 1,
  pageSize: 5,
  pageSizes: [5, 10, 20, 50],
  layout: "total, sizes, prev, pager, next, jumper"
});

// 获取支付类型标签类型
const getTypeTagType = (type: number) => {
  switch (type) {
    case 1: return "primary";
    case 2: return "warning";
    case 3: return "success";
    default: return "info";
  }
};

// 获取支付类型文本
const getTypeText = (type: number) => {
  switch (type) {
    case 1: return "传统";
    case 2: return "加密";
    case 3: return "钱包";
    default: return "未知";
  }
};

// 获取数据
async function loadServerData(pageNum = 1) {
  loading.value = true;

  try {
    const params = {
      ...searchForm,
      page: pageNum,
      limit: pagination.pageSize
    };

    const res: any = await getPaymentMethodList(params);

    if (res.code === 200) {
      const newData = res.data.list || [];

      // 处理数据格式
      newData.forEach((item: any) => {
        // 确保status是数字类型
        item.status = Number(item.status);
        item.is_crypto = Number(item.is_crypto);
        item.is_default = Number(item.is_default);
      });

      tableData.value = newData;
      pagination.currentPage = pageNum;

      // 更新分页信息 - 按照文档，分页信息在 res.data.pagination
      if (res.data.pagination) {
        pagination.total = res.data.pagination.total;
        // 如果当前页超出范围，重置为第一页
        if (pagination.currentPage > res.data.pagination.pages && res.data.pagination.pages > 0) {
          pagination.currentPage = 1;
        }
      } else {
        pagination.total = newData.length;
      }
    } else {
      message(res.msg || "获取数据失败", { type: "error" });
      tableData.value = [];
      pagination.total = 0;
    }
  } catch (error) {
    console.error('加载支付渠道数据失败:', error);
    message("网络错误，请稍后重试", { type: "error" });
    tableData.value = [];
    pagination.total = 0;
  } finally {
    loading.value = false;
  }
}

// 初始化数据
async function initData() {
  await loadServerData(1);
}

// 搜索
const search = () => {
  pagination.currentPage = 1;
  initData();
};

// 重置搜索表单
const resetSearchForm = () => {
  Object.assign(searchForm, {
    id: "",
    name: "",
    code: "",
    type: "",
    status: "",
    is_crypto: "",
    currency_code: "",
    network: "",
    is_default: ""
  });
  search();
};

// 刷新数据
const refreshData = () => {
  initData();
};

// 添加支付渠道
const handleAdd = () => {
  currentPaymentMethod.value = null;
  showAddOrEditModal.value = true;
};

// 编辑支付渠道
const handleEdit = (row: PaymentMethod) => {
  currentPaymentMethod.value = { ...row };
  showAddOrEditModal.value = true;
};

// 删除支付渠道
const handleDelete = async (row: PaymentMethod) => {
  ElMessageBox.confirm(`确定要删除支付渠道 "${row.name}" 吗？`, "删除确认", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "warning"
  }).then(async () => {
    try {
      loading.value = true;
      const res = await deletePaymentMethod(row.id);

      if (res.code !== 200) {
        message(res.msg || "删除失败", { type: "error" });
        return;
      }

      message("删除成功", { type: "success" });
      await initData(); // 操作成功后重新加载数据
    } catch (error) {
      console.error("删除支付渠道失败:", error);
      message("删除失败", { type: "error" });
    } finally {
      loading.value = false;
    }
  }).catch(() => {
    // 用户取消删除
  });
};

// 批量删除
const handleBatchDelete = async () => {
  if (selectedIds.value.length === 0) {
    message("请选择要删除的支付渠道", { type: "warning" });
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedIds.value.length} 个支付渠道吗？`,
      "批量删除确认",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    // 批量删除
    const promises = selectedIds.value.map(id => deletePaymentMethod(id));
    await Promise.all(promises);

    message("批量删除成功", { type: "success" });
    selectedIds.value = [];
    initData();
  } catch (error) {
    if (error !== "cancel") {
      console.error("批量删除失败:", error);
      message("批量删除失败", { type: "error" });
    }
  }
};

// 状态切换
const handleStatusChange = async (row: PaymentMethodWithUI) => {
  try {
    const action = row.status === 1 ? '启用' : '禁用';
    await ElMessageBox.confirm(`确定要${action}支付渠道"${row.name}"吗?`, '系统提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    });

    loading.value = true;
    const res = await updatePaymentMethodStatus(row.id, row.status);
    if (res.code !== 200) {
      row.status = row.status === 1 ? 0 : 1; // 如果失败则恢复原状态
      return message('状态更新失败', { type: 'error' });
    }

    await initData(); // 刷新表格数据
    message(`支付渠道已${action}`, { type: 'success' });
  } catch (error) {
    row.status = row.status === 1 ? 0 : 1; // 取消操作时恢复原状态
    console.log('用户取消状态变更');
  } finally {
    loading.value = false;
  }
};

// 设置默认支付渠道
const handleSetDefault = async (row: PaymentMethod) => {
  ElMessageBox.confirm(`确定要将 "${row.name}" 设置为默认支付渠道吗？`, "设置默认确认", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "info"
  }).then(async () => {
    try {
      loading.value = true;
      const res = await setDefaultPaymentMethod(row.id);

      if (res.code !== 200) {
        message(res.msg || "设置默认失败", { type: "error" });
        return;
      }

      message("设置默认支付渠道成功", { type: "success" });
      await initData(); // 操作成功后重新加载数据
    } catch (error) {
      console.error("设置默认失败:", error);
      message("设置默认失败", { type: "error" });
    } finally {
      loading.value = false;
    }
  }).catch(() => {
    // 用户取消操作
  });
};

// 表格选择变化
const handleSelectionChange = (selection: PaymentMethod[]) => {
  selectedIds.value = selection.map(item => item.id);
};

// 分页大小变化
const handleSizeChange = (size: number) => {
  pagination.pageSize = size;
  pagination.currentPage = 1;
  initData();
};

// 当前页变化
const handleCurrentChange = (page: number) => {
  pagination.currentPage = page;
  loadServerData(page);
};

// 对话框关闭前
const handleClose = (done: () => void) => {
  done();
};

// 对话框关闭后
const handleDialogClosed = () => {
  currentPaymentMethod.value = null;
};

// 提交成功
const handleSubmitSuccess = () => {
  showAddOrEditModal.value = false;
  initData();
};

// 复制地址到剪贴板
const copyAddress = async (address: string) => {
  try {
    await navigator.clipboard.writeText(address);
    message("地址已复制到剪贴板", { type: "success" });
  } catch (error) {
    // 降级方案
    const textArea = document.createElement('textarea');
    textArea.value = address;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    message("地址已复制到剪贴板", { type: "success" });
  }
};

// 组件挂载
onMounted(() => {
  initData();
});
</script>

<style scoped lang="scss">
.payment-channel-container {
  .search-card {
    margin-bottom: 16px;
    border: 1px solid var(--el-border-color-lighter);
    background: var(--el-bg-color-page);

    :deep(.el-card__header) {
      padding: 12px 16px;
      background: var(--el-fill-color-lighter);
      border-bottom: 1px solid var(--el-border-color-light);

      .search-header {
        display: flex;
        align-items: center;

        .search-title {
          font-size: 14px;
          font-weight: 600;
          color: var(--el-text-color-primary);
        }
      }
    }

    :deep(.el-card__body) {
      padding: 16px;
      background: var(--el-bg-color);
    }

    .search-form {
      margin-bottom: 0;

      .el-form-item {
        margin-bottom: 12px;
        margin-right: 16px;

        :deep(.el-form-item__label) {
          font-size: 12px;
          font-weight: 500;
          color: var(--el-text-color-regular);
        }
      }
    }
  }

  .table-card {
    border: 1px solid var(--el-border-color-lighter);

    :deep(.el-card__body) {
      padding: 12px;
      overflow-x: auto;
    }
  }

  .table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;

    .el-button+.el-button {
      margin-left: 8px;
    }
  }

  .compact-table {
    width: 100%;
    min-width: 800px;

    :deep(.el-table__header) {
      th {
        background-color: var(--el-fill-color-lighter);
        padding: 8px 0;
        font-size: 12px;
        font-weight: 600;
      }
    }

    :deep(.el-table__body) {
      td {
        padding: 6px 0;
        font-size: 12px;
      }
    }

    :deep(.el-table__cell) {
      padding: 8px 12px;
    }
  }

  .payment-icon {
    font-size: 14px;
    color: var(--el-color-primary);
  }

  .currency-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1.2;

    span {
      font-weight: 600;
      color: var(--el-color-primary);
      font-size: 14px;
    }

    small {
      font-size: 10px;
      color: var(--el-text-color-secondary);
    }
  }

  .wallet-address {
    display: flex;
    justify-content: center;

    .address-content {
      display: flex;
      align-items: center;
      gap: 6px;

      .address-text {
        font-family: 'Courier New', monospace;
        font-size: 11px;
        color: var(--el-text-color-secondary);
        background: var(--el-fill-color-light);
        padding: 2px 6px;
        border-radius: 4px;
        cursor: pointer;

        &:hover {
          background: var(--el-fill-color);
        }
      }

      .copy-btn {
        padding: 2px 4px;
        font-size: 12px;

        &:hover {
          color: var(--el-color-primary-light-3);
        }
      }
    }
  }

  .text-muted {
    color: var(--el-text-color-placeholder);
    font-size: 12px;
  }

  .pagination-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--el-border-color-lighter);
  }

  // 响应式设计
  @media (max-width: 768px) {
    .search-card {
      .el-form {
        .el-form-item {
          margin-right: 8px;
          margin-bottom: 8px;
        }
      }
    }

    .table-header {
      flex-direction: column;
      gap: 8px;
      align-items: stretch;
    }

    .table-card {
      :deep(.el-card__body) {
        padding: 8px;
      }
    }

    .compact-table {
      min-width: 600px;

      :deep(.el-table__cell) {
        padding: 6px 8px;
      }
    }

    .pagination-wrapper {
      justify-content: center;
    }
  }
}

// 暗黑模式适配
:deep(.dark) {

  .search-card,
  .table-card {
    border-color: var(--el-border-color);
  }

  .payment-icon {
    color: var(--el-color-primary);
  }
}
</style>
