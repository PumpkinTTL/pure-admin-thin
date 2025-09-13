<template>
  <div class="order-container">
    <el-drawer v-model="showOrderForm" :title="currentOrderId ? '编辑订单' : '新增订单'" :size="isMobile ? '100%' : '550px'"
      direction="rtl" destroy-on-close @closed="handleDialogClosed">
      <OrderForm v-if="showOrderForm" :order-id="currentOrderId" @submit-success="handleSubmitSuccess" />
    </el-drawer>

    <el-drawer v-model="showOrderDetail" title="订单详情" :size="isMobile ? '100%' : '650px'" direction="rtl"
      destroy-on-close>
      <OrderDetail v-if="showOrderDetail" :order-id="currentOrderId" />
    </el-drawer>

    <el-card class="box-card">
      <template #header>
        <el-row :gutter="16">
          <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="2">
            <span class="header-title">订单管理</span>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="3">
            <el-input v-model="searchForm.order_no" placeholder="订单号" clearable />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="3">
            <el-input v-model="searchForm.user_id" placeholder="用户ID" clearable />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="3">
            <el-select v-model="searchForm.order_status" placeholder="订单状态" clearable style="width: 100%">
              <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-select v-model="searchForm.payment_method" placeholder="支付方式" clearable style="width: 100%">
              <el-option v-for="item in paymentMethodOptions" :key="item.value" :label="item.label"
                :value="item.value" />
            </el-select>
          </el-col>
          <el-col :xs="12" :sm="6" :md="4" :lg="3" :xl="2">
            <el-button type="primary" :icon="Search" style="width: 100%" @click="handleSearch" size="default">
              搜索
            </el-button>
          </el-col>
          <el-col :xs="12" :sm="6" :md="4" :lg="3" :xl="2">
            <el-button type="primary" :icon="RefreshLeft" style="width: 100%" @click="resetSearchForm" size="default">
              重置
            </el-button>
          </el-col>
        </el-row>
      </template>

      <div class="table-header">
        <el-button size="small" :icon="Search" circle />
        <el-button size="small" :icon="Printer" circle />
        <el-button size="small" :icon="Upload" circle />
        <el-button size="small" type="primary" :icon="Plus" @click="handleAddOrder">
          新增
        </el-button>
        <el-button size="small" type="info" plain @click="loadMockData">
          加载模拟数据
        </el-button>
      </div>

      <el-divider content-position="left" class="compact-divider">数据列表</el-divider>

      <!-- 订单列表 -->
      <el-table border :data="orderData" style="width: 100%" size="small"
        :header-cell-style="{ background: '#f5f7fa', color: '#606266' }" v-loading="tableLoading" :fit="true"
        @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="45" align="center" />
        <el-table-column label="ID" prop="id" min-width="60" align="center" />
        <el-table-column label="订单号" prop="order_no" min-width="120" show-overflow-tooltip />
        <el-table-column label="用户" min-width="80">
          <template #default="{ row }">
            {{ row.user_name || `ID: ${row.user_id}` }}
          </template>
        </el-table-column>
        <el-table-column label="金额" min-width="90">
          <template #header>
            <div style="text-align: center;">金额</div>
          </template>
          <template #default="{ row }">
            <div class="amount-container">
              <font-awesome-icon :icon="['fas', 'coins']" class="coin-icon" />
              <span class="amount">{{ formatAmount(row.order_amount) }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" min-width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.order_status)" size="small" effect="light">
              {{ getStatusText(row.order_status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="支付方式" min-width="110" align="center">
          <template #default="{ row }">
            <div class="payment-tag" :class="row.payment_method">
              <font-awesome-icon :icon="getPaymentIcon(row.payment_method)" />
              {{ getPaymentText(row.payment_method) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="商品名称" prop="product" min-width="150" show-overflow-tooltip />
        <el-table-column label="备注" prop="remark" min-width="120" show-overflow-tooltip />
        <el-table-column label="创建时间" prop="create_time" min-width="150" show-overflow-tooltip />
        <el-table-column label="操作" min-width="180" align="center">
          <template #default="scope">
            <div class="action-buttons">
              <el-button type="primary" text bg size="small" @click="handleViewOrder(scope.row)">
                <font-awesome-icon :icon="['fas', 'eye']" />
                查看
              </el-button>
              <el-button type="primary" text bg size="small" @click="handleEditOrder(scope.row)"
                :disabled="!!scope.row.delete_time">
                <font-awesome-icon :icon="['fas', 'edit']" />
                编辑
              </el-button>
              <el-button :type="scope.row.delete_time ? 'success' : 'danger'" text bg size="small"
                @click="scope.row.delete_time ? handleRestore(scope.row) : handleDelete(scope.row)">
                <font-awesome-icon :icon="scope.row.delete_time ? ['fas', 'undo'] : ['fas', 'trash']" />
                {{ scope.row.delete_time ? '恢复' : '删除' }}
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination v-model:current-page="pageConfig.currentPage" v-model:page-size="pageConfig.pageSize"
        :page-sizes="pageConfig.pageSizes" :disabled="pageConfig.disabled" :background="pageConfig.background"
        layout="total, sizes, prev, pager, next, jumper" :total="pageConfig.total" @size-change="handleSizeChange"
        @current-change="handleCurrentChange" class="pagination" />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, defineAsyncComponent, watch } from "vue";
import {
  Search,
  RefreshLeft,
  Printer,
  Upload,
  Plus,
  View,
  Edit,
  Delete,
  Refresh
} from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import { message } from "@/utils/message";
import {
  getOrderList,
  deleteOrder,
  restoreOrder,
  batchDeleteOrders,
  OrderItem,
  getOrderStatusOptions,
  getPaymentMethodOptions,
  generateMockOrders
} from "@/api/order";
import { useWindowSize } from "@/hooks/useWindowSize";

// 异步加载子组件
const OrderForm = defineAsyncComponent(() => import("./order/OrderForm.vue"));
const OrderDetail = defineAsyncComponent(() => import("./order/OrderDetail.vue"));

// 响应式设计
const { width } = useWindowSize();
const isMobile = computed(() => width.value < 768);

// 搜索表单
const searchForm = reactive({
  order_no: "",
  user_id: "",
  order_status: "",
  payment_method: ""
});

// 表格数据
const orderData = ref<OrderItem[]>([]);
const allOrderData = ref<OrderItem[]>([]); // 存储所有数据
const tableLoading = ref(false);
const selectedOrders = ref<OrderItem[]>([]);

// 分页配置
const pageConfig = ref({
  currentPage: 1,
  pageSize: 5,
  total: 0,
  disabled: false,
  background: true,
  pageSizes: [5, 10, 20, 30]
});

// 状态选项
const statusOptions = getOrderStatusOptions();

// 支付方式选项
const paymentMethodOptions = computed(() => {
  return getPaymentMethodOptions();
});

// 新增/编辑订单相关
const showOrderForm = ref(false);
const currentOrderId = ref<number | null>(null);

// 订单详情相关
const showOrderDetail = ref(false);

// 当前页数据的计算属性
const paginatedOrderData = computed(() => {
  const start = (pageConfig.value.currentPage - 1) * pageConfig.value.pageSize;
  const end = start + pageConfig.value.pageSize;
  return allOrderData.value.slice(start, end);
});

// 格式化日期时间
const formatDateTime = (dateTime: string) => {
  if (!dateTime) return "-";
  return dateTime;
};

// 格式化金额
const formatAmount = (amount: number) => {
  return Number(amount).toFixed(2);
};

// 获取状态对应的文本
const getStatusText = (status: string): string => {
  const statusMap: Record<string, string> = {
    "unpaid": "未支付",
    "paid": "已支付",
    "completed": "已完成",
    "cancelled": "已取消"
  };
  return statusMap[status] || status;
};

// 获取状态对应的标签类型
const getStatusTagType = (status: string): 'success' | 'warning' | 'info' | 'primary' | 'danger' => {
  const typeMap: Record<string, 'success' | 'warning' | 'info' | 'primary' | 'danger'> = {
    "unpaid": "warning",
    "paid": "primary",
    "completed": "success",
    "cancelled": "danger"
  };
  return typeMap[status] || "info";
};

// 获取支付方式对应的文本
const getPaymentText = (method: string): string => {
  const methodMap: Record<string, string> = {
    "wechat": "微信支付",
    "alipay": "支付宝",
    "usdt": "USDT"
  };
  return methodMap[method] || method;
};

// 获取支付方式对应的图标
const getPaymentIcon = (method: string): any => {
  const iconMap: Record<string, any> = {
    "wechat": ['fab', 'weixin'],
    "alipay": ['fab', 'alipay'],
    "usdt": ['fas', 'coins']
  };
  return iconMap[method] || ['fas', 'credit-card'];
};

// 获取订单列表
const fetchOrderList = async () => {
  try {
    tableLoading.value = true;

    // 构建请求参数
    const params = {
      page: pageConfig.value.currentPage,
      page_size: pageConfig.value.pageSize,
      order_no: searchForm.order_no || undefined,
      user_id: searchForm.user_id || undefined,
      order_status: searchForm.order_status || undefined,
      payment_method: searchForm.payment_method || undefined
    };

    // 调用API获取数据
    const res = await getOrderList(params);

    if (res.code === 200) {
      allOrderData.value = res.data.data || [];
      orderData.value = paginatedOrderData.value; // 使用分页后的数据
      pageConfig.value.total = res.data.total || 0;
    } else {
      message(res.message || "获取订单列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取订单列表出错:", error);
    message("获取订单列表失败", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 搜索
const handleSearch = () => {
  pageConfig.value.currentPage = 1;
  fetchOrderList();
};

// 重置搜索表单
const resetSearchForm = () => {
  searchForm.order_no = "";
  searchForm.user_id = "";
  searchForm.order_status = "";
  searchForm.payment_method = "";

  pageConfig.value.currentPage = 1;
  fetchOrderList();
};

// 加载模拟数据
const loadMockData = () => {
  tableLoading.value = true;

  // 使用生成的模拟数据，移除货到付款和银行卡类型的数据
  const mockData = generateMockOrders(30).filter(item =>
    item.payment_method !== 'cash_on_delivery' && item.payment_method !== 'bank_card'
  );

  // 更新全部数据
  allOrderData.value = mockData;
  pageConfig.value.total = mockData.length;

  // 更新当前页数据
  updateCurrentPageData();

  tableLoading.value = false;
  message("模拟数据加载成功", { type: "success" });
};

// 更新当前页数据
const updateCurrentPageData = () => {
  const start = (pageConfig.value.currentPage - 1) * pageConfig.value.pageSize;
  const end = start + pageConfig.value.pageSize;
  orderData.value = allOrderData.value.slice(start, end);
};

// 表格选中行变化
const handleSelectionChange = (selection: OrderItem[]) => {
  selectedOrders.value = selection;
};

// 批量删除
const handleBatchDelete = () => {
  if (selectedOrders.value.length === 0) return;

  ElMessageBox.confirm(
    `确认删除选中的 ${selectedOrders.value.length} 个订单吗？`,
    "提示",
    {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }
  )
    .then(async () => {
      try {
        const idList = selectedOrders.value.map(item => item.id);
        const res = await batchDeleteOrders(idList);

        if (res.code === 200) {
          message("批量删除成功", { type: "success" });
          fetchOrderList();
        } else {
          message(res.message || "批量删除失败", { type: "error" });
        }
      } catch (error) {
        console.error("批量删除出错:", error);
        message("批量删除失败", { type: "error" });
      }
    })
    .catch(() => {
      // 用户取消操作
    });
};

// 单个删除
const handleDelete = (row: OrderItem) => {
  ElMessageBox.confirm(`确认删除订单 "${row.order_no}" 吗？`, "提示", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "warning"
  })
    .then(async () => {
      try {
        const res = await deleteOrder(row.id);

        if (res.code === 200) {
          message("删除成功", { type: "success" });
          fetchOrderList();
        } else {
          message(res.message || "删除失败", { type: "error" });
        }
      } catch (error) {
        console.error("删除出错:", error);
        message("删除失败", { type: "error" });
      }
    })
    .catch(() => {
      // 用户取消操作
    });
};

// 恢复订单
const handleRestore = async (row: OrderItem) => {
  try {
    const res = await restoreOrder(row.id);

    if (res.code === 200) {
      message("恢复成功", { type: "success" });
      fetchOrderList();
    } else {
      message(res.message || "恢复失败", { type: "error" });
    }
  } catch (error) {
    console.error("恢复出错:", error);
    message("恢复失败", { type: "error" });
  }
};

// 添加订单
const handleAddOrder = () => {
  currentOrderId.value = null;
  showOrderForm.value = true;
};

// 编辑订单
const handleEditOrder = (row: OrderItem) => {
  currentOrderId.value = row.id;
  showOrderForm.value = true;
};

// 查看订单
const handleViewOrder = (row: OrderItem) => {
  currentOrderId.value = row.id;
  showOrderDetail.value = true;
};

// 提交成功回调
const handleSubmitSuccess = () => {
  fetchOrderList();
  showOrderForm.value = false;
};

// 弹窗关闭
const handleDialogClosed = () => {
  currentOrderId.value = null;
};

// 分页相关方法
const handleSizeChange = (val: number) => {
  pageConfig.value.pageSize = val;
  updateCurrentPageData();
};

const handleCurrentChange = (val: number) => {
  pageConfig.value.currentPage = val;
  updateCurrentPageData();
};

onMounted(() => {
  // 首次加载数据
  loadMockData();
});
</script>

<style lang="scss" scoped>
.order-container {
  .header-title {
    font-size: 16px;
    font-weight: 500;
    color: #303133;
  }

  .table-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
  }

  // 紧凑分割线
  .compact-divider {
    margin: 16px 0;
  }

  .action-buttons {
    display: flex;
    flex-wrap: nowrap;
    justify-content: center;
    gap: 8px;
  }

  // 金额样式
  .amount-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background-color: #f9f9f9;
    border-radius: 4px;
    padding: 4px 10px;

    .coin-icon {
      color: #f0b90b;
      font-size: 16px;
    }

    .amount {
      font-weight: normal;
      font-size: 14px;
      color: #f0b90b;
    }
  }

  // 支付方式标签样式
  .payment-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;

    &.wechat {
      background-color: #07c160;
      color: white;
    }

    &.alipay {
      background-color: #1677ff;
      color: white;
    }

    &.usdt {
      background-color: #26a17b;
      color: white;
    }

    svg {
      width: 14px;
      height: 14px;
    }
  }

  // 自定义卡片样式
  :deep(.box-card) {
    margin-bottom: 16px;

    .el-card__header {
      padding: 12px 16px;
    }

    .el-card__body {
      padding: 15px;
    }
  }

  // 自定义表格样式
  :deep(.el-table) {
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 16px;

    .el-table__header th {
      font-size: 13px;
      height: 40px;
      padding: 4px 0;
    }

    .el-table__row td {
      padding: 8px;
    }

    .el-tag {
      border-radius: 4px;
    }

    // 确保表格自适应
    table {
      width: 100% !important;
    }

    // 防止内容超出
    .cell {
      white-space: normal;
      word-break: break-word;
    }
  }

  // 自定义抽屉样式
  :deep(.el-drawer) {
    .el-drawer__header {
      margin-bottom: 0;
      padding: 16px 20px;
      border-bottom: 1px solid #f0f0f0;
    }

    .el-drawer__body {
      padding: 0;
      overflow-y: auto;
    }
  }

  // 自定义分页样式
  :deep(.el-pagination) {
    justify-content: center;
    margin-top: 20px;
    padding: 0;
  }

  // 按钮样式
  :deep(.el-button.is-circle) {
    width: 28px;
    height: 28px;
    padding: 0;
  }

  :deep(.el-button--primary) {
    background-color: var(--el-color-primary);
  }

  // 操作按钮样式
  :deep(.el-button--text) {
    padding: 4px 8px;
    margin: 0 2px;

    svg {
      margin-right: 4px;
      width: 14px;
      height: 14px;
    }

    &.is-disabled {
      opacity: 0.6;
    }

    &.el-button--primary {
      color: var(--el-color-primary);

      &:hover {
        color: var(--el-color-primary-light-3);
        background-color: var(--el-color-primary-light-9);
      }
    }

    &.el-button--danger {
      color: var(--el-color-danger);

      &:hover {
        color: var(--el-color-danger-light-3);
        background-color: var(--el-color-danger-light-9);
      }
    }

    &.el-button--success {
      color: var(--el-color-success);

      &:hover {
        color: var(--el-color-success-light-3);
        background-color: var(--el-color-success-light-9);
      }
    }
  }

  // 分页居中
  .pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
  }
}
</style>