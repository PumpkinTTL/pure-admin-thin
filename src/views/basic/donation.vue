<template>
  <div class="donation-container">
    <el-drawer
      v-model="showDonationForm"
      :title="currentDonationId ? '编辑捐赠记录' : '新增捐赠记录'"
      :size="isMobile ? '100%' : '650px'"
      direction="rtl"
      destroy-on-close
      @closed="handleDialogClosed"
    >
      <DonationForm
        v-if="showDonationForm"
        :donation-id="currentDonationId"
        @submit-success="handleSubmitSuccess"
      />
    </el-drawer>

    <el-drawer
      v-model="showDonationDetail"
      title="捐赠记录详情"
      :size="isMobile ? '100%' : '700px'"
      direction="rtl"
      destroy-on-close
      class="donation-detail-drawer"
    >
      <DonationDetail
        v-if="showDonationDetail"
        :donation-id="currentDonationId"
      />
    </el-drawer>

    <el-card class="box-card" shadow="never">
      <!-- 页面标题 -->
      <div class="page-header">
        <div class="header-left">
          <font-awesome-icon :icon="['fas', 'gift']" class="header-icon" />
          <span class="header-title">捐赠记录</span>
          <el-divider direction="vertical" />
          <span class="header-count">共 {{ pageConfig.total }} 条</span>
        </div>
      </div>

      <!-- 搜索区域 -->
      <div class="search-section">
        <el-row :gutter="12">
          <el-col :xs="24" :sm="12" :md="6" :lg="5" :xl="5" class="search-col">
            <el-input
              v-model="searchForm.donation_no"
              placeholder="捐赠单号"
              clearable
              size="default"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="5" :xl="5" class="search-col">
            <el-input
              v-model="searchForm.donor_name"
              placeholder="捐赠者姓名"
              clearable
              size="default"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="5" :xl="5" class="search-col">
            <el-select
              v-model="searchForm.channel"
              placeholder="捐赠渠道"
              clearable
              style="width: 100%"
              size="default"
            >
              <el-option
                v-for="(label, value) in channelOptions"
                :key="value"
                :label="label"
                :value="value"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="4" :xl="4" class="search-col">
            <el-select
              v-model="searchForm.status"
              placeholder="状态"
              clearable
              style="width: 100%"
              size="default"
            >
              <el-option
                v-for="(label, value) in statusOptions"
                :key="value"
                :label="label"
                :value="value"
              />
            </el-select>
          </el-col>
          <el-col
            :xs="24"
            :sm="24"
            :md="24"
            :lg="5"
            :xl="5"
            class="search-col search-buttons"
          >
            <el-button
              type="primary"
              :icon="Search"
              size="default"
              @click="handleSearch"
            >
              搜索
            </el-button>
            <el-button
              :icon="RefreshLeft"
              size="default"
              @click="resetSearchForm"
            >
              重置
            </el-button>
          </el-col>
        </el-row>
      </div>

      <el-divider class="section-divider" />

      <div class="table-header">
        <el-button
          size="small"
          type="primary"
          :icon="Plus"
          @click="handleAddDonation"
        >
          新增
        </el-button>
        <el-button
          size="small"
          type="danger"
          plain
          :icon="Delete"
          :disabled="selectedDonations.length === 0"
          @click="handleBatchDelete"
        >
          批量删除
        </el-button>
        <el-button
          size="small"
          type="info"
          plain
          :icon="DataAnalysis"
          @click="showStatistics"
        >
          统计数据
        </el-button>
      </div>

      <el-divider content-position="left" class="compact-divider">
        数据列表
      </el-divider>

      <!-- 捐赠记录列表 -->
      <el-table
        v-loading="tableLoading"
        border
        :data="donationData"
        style="width: 100%"
        size="small"
        :header-cell-style="{ background: '#f5f7fa', color: '#606266' }"
        :fit="true"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="45" align="center" />
        <el-table-column
          label="ID"
          prop="id"
          min-width="70"
          align="center"
          show-overflow-tooltip
        />
        <el-table-column label="捐赠者" min-width="120" align="center">
          <template #default="{ row }">
            <span v-if="row.is_anonymous === 1" class="anonymous-tag">
              <font-awesome-icon :icon="['fas', 'user-secret']" />
              匿名
            </span>
            <span v-else style="white-space: nowrap">
              {{ getDonorName(row) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="渠道" width="100" align="center">
          <template #default="{ row }">
            <el-tag class="channel-tag" :class="row.channel" size="small">
              <font-awesome-icon :icon="getChannelIcon(row.channel)" />
              {{ row.channel_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="金额" min-width="120" align="center">
          <template #default="{ row }">
            <div v-if="row.channel === 'cardkey'" class="amount-wrapper">
              <font-awesome-icon
                :icon="['fas', 'ticket']"
                class="amount-icon cardkey"
              />
              <span class="amount-text cardkey">
                {{ formatAmount(row.card_key_value) }}
              </span>
            </div>
            <div v-else-if="row.channel === 'crypto'" class="amount-wrapper">
              <font-awesome-icon
                :icon="['fab', 'bitcoin']"
                class="amount-icon crypto"
              />
              <span class="amount-text crypto">
                {{ formatAmount(row.amount) }} {{ row.crypto_type || "USDT" }}
              </span>
            </div>
            <div v-else class="amount-wrapper">
              <font-awesome-icon
                :icon="['fas', 'yen-sign']"
                class="amount-icon money"
              />
              <span class="amount-text money">
                {{ formatAmount(row.amount) }}
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="渠道详情" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <div class="channel-detail">
              <span
                v-if="row.channel === 'wechat' || row.channel === 'alipay'"
                class="detail-content"
              >
                <font-awesome-icon
                  :icon="['fas', 'receipt']"
                  class="detail-icon"
                />
                <span class="detail-text">{{ row.order_no || "-" }}</span>
              </span>
              <span
                v-else-if="row.channel === 'crypto'"
                class="detail-content crypto"
              >
                <font-awesome-icon
                  :icon="['fas', 'link']"
                  class="detail-icon"
                />
                <span class="detail-text">
                  {{ row.crypto_type }}-{{ row.crypto_network }}
                </span>
              </span>
              <span
                v-else-if="row.channel === 'cardkey'"
                class="detail-content"
              >
                <font-awesome-icon
                  :icon="['fas', 'ticket']"
                  class="detail-icon"
                />
                <span class="detail-text">{{ row.card_key_code || "-" }}</span>
              </span>
              <span v-else class="detail-content">-</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" min-width="80" align="center">
          <template #default="{ row }">
            <el-tag
              :type="getStatusTagType(row.status)"
              size="small"
              effect="light"
            >
              {{ row.status_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="公开" min-width="60" align="center">
          <template #default="{ row }">
            <el-tooltip
              :content="row.is_public === 1 ? '公开展示' : '不公开'"
              placement="top"
            >
              <font-awesome-icon
                :icon="
                  row.is_public === 1 ? ['fas', 'eye'] : ['fas', 'eye-slash']
                "
                :class="row.is_public === 1 ? 'public-icon' : 'private-icon'"
                style="font-size: 16px"
              />
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column
          label="创建时间"
          prop="create_time"
          min-width="160"
          show-overflow-tooltip
        />
        <el-table-column
          label="操作"
          min-width="200"
          align="center"
          fixed="right"
        >
          <template #default="scope">
            <div class="action-buttons">
              <el-button
                type="primary"
                text
                bg
                size="small"
                @click="handleViewDonation(scope.row)"
              >
                <font-awesome-icon :icon="['fas', 'eye']" />
                查看
              </el-button>
              <el-button
                type="primary"
                text
                bg
                size="small"
                :disabled="!!scope.row.delete_time"
                @click="handleEditDonation(scope.row)"
              >
                <font-awesome-icon :icon="['fas', 'edit']" />
                编辑
              </el-button>
              <el-button
                :type="scope.row.delete_time ? 'success' : 'danger'"
                text
                bg
                size="small"
                @click="
                  scope.row.delete_time
                    ? handleRestore(scope.row)
                    : handleDelete(scope.row)
                "
              >
                <font-awesome-icon
                  :icon="
                    scope.row.delete_time ? ['fas', 'undo'] : ['fas', 'trash']
                  "
                />
                {{ scope.row.delete_time ? "恢复" : "删除" }}
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pageConfig.currentPage"
        v-model:page-size="pageConfig.pageSize"
        :page-sizes="pageConfig.pageSizes"
        :disabled="pageConfig.disabled"
        :background="pageConfig.background"
        layout="total, sizes, prev, pager, next, jumper"
        :total="pageConfig.total"
        class="pagination"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, defineAsyncComponent } from "vue";
import {
  Search,
  RefreshLeft,
  Plus,
  Delete,
  DataAnalysis
} from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import { message } from "@/utils/message";
import {
  getDonationList,
  deleteDonation,
  restoreDonation,
  batchDeleteDonation,
  getChannelOptions,
  getStatusOptions,
  getDonationStatistics,
  Donation,
  DonationStatusTypeMap
} from "@/api/donation";
import { useWindowSize } from "@/hooks/useWindowSize";

// 异步加载子组件
const DonationForm = defineAsyncComponent(
  () => import("./donation/DonationForm.vue")
);
const DonationDetail = defineAsyncComponent(
  () => import("./donation/DonationDetail.vue")
);

// 响应式设计
const { width } = useWindowSize();
const isMobile = computed(() => width.value < 768);

// 搜索表单
const searchForm = reactive({
  donation_no: "",
  donor_name: "",
  channel: "",
  status: ""
});

// 表格数据
const donationData = ref<Donation[]>([]);
const allDonationData = ref<Donation[]>([]); // 存储所有数据
const tableLoading = ref(false);
const selectedDonations = ref<Donation[]>([]);

// 分页配置
const pageConfig = ref({
  currentPage: 1,
  pageSize: 5,
  total: 0,
  disabled: false,
  background: true,
  pageSizes: [5, 10, 20, 30]
});

// 渠道选项
const channelOptions = ref<Record<string, string>>({});

// 状态选项
const statusOptions = ref<Record<string, string>>({});

// 新增/编辑捐赠记录相关
const showDonationForm = ref(false);
const currentDonationId = ref<number | null>(null);

// 捐赠记录详情相关
const showDonationDetail = ref(false);

// 当前页数据的计算属性
const paginatedDonationData = computed(() => {
  const start = (pageConfig.value.currentPage - 1) * pageConfig.value.pageSize;
  const end = start + pageConfig.value.pageSize;
  return allDonationData.value.slice(start, end);
});

// 格式化金额
const formatAmount = (amount: number | undefined) => {
  if (amount === undefined || amount === null) return "0.00";
  return Number(amount).toFixed(2);
};

// 获取状态对应的标签类型
const getStatusTagType = (
  status: number
): "success" | "warning" | "info" | "primary" | "danger" => {
  return (DonationStatusTypeMap[status] as any) || "info";
};

// 获取渠道图标
const getChannelIcon = (channel: string) => {
  const iconMap: Record<string, any> = {
    wechat: ["fab", "weixin"],
    alipay: ["fab", "alipay"],
    crypto: ["fab", "bitcoin"],
    cardkey: ["fas", "ticket"]
  };
  return iconMap[channel] || ["fas", "question"];
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

// 加载捐赠记录列表
const loadDonationList = async () => {
  tableLoading.value = true;
  try {
    const params = {
      page: 1,
      page_size: 100,
      ...searchForm,
      query_deleted: "with_deleted" as const
    };

    const res = await getDonationList(params);

    if (res.code === 200) {
      allDonationData.value = res.data.list || [];
      pageConfig.value.total = res.data.total || 0;
      updatePaginatedData();
    } else {
      message(res.message || "加载失败", { type: "error" });
    }
  } catch (error) {
    console.error("加载捐赠记录失败:", error);
    message("加载捐赠记录失败", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 更新分页数据
const updatePaginatedData = () => {
  donationData.value = paginatedDonationData.value;
};

// 搜索
const handleSearch = () => {
  pageConfig.value.currentPage = 1;
  loadDonationList();
};

// 重置搜索表单
const resetSearchForm = () => {
  searchForm.donation_no = "";
  searchForm.donor_name = "";
  searchForm.channel = "";
  searchForm.status = "";
  handleSearch();
};

// 获取捐赠者名称
const getDonorName = (row: Donation) => {
  // 如果有user_id且关联了用户信息，显示用户昵称
  if (row.user_id && row.user) {
    return row.user.nickname || `用户ID: ${row.user_id}`;
  }
  // 如果没有user_id，显示donor_name（匿名捐赠的情况）
  return row.donor_name || "匿名用户";
};

// 分页大小改变
const handleSizeChange = (val: number) => {
  pageConfig.value.pageSize = val;
  updatePaginatedData();
};

// 当前页改变
const handleCurrentChange = (val: number) => {
  pageConfig.value.currentPage = val;
  updatePaginatedData();
};

// 选择改变
const handleSelectionChange = (val: Donation[]) => {
  selectedDonations.value = val;
};

// 新增捐赠记录
const handleAddDonation = () => {
  currentDonationId.value = null;
  showDonationForm.value = true;
};

// 编辑捐赠记录
const handleEditDonation = (row: Donation) => {
  currentDonationId.value = row.id;
  showDonationForm.value = true;
};

// 查看捐赠记录
const handleViewDonation = (row: Donation) => {
  currentDonationId.value = row.id;
  showDonationDetail.value = true;
};

// 删除捐赠记录
const handleDelete = async (row: Donation) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除捐赠单号 ${row.donation_no} 吗？`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const res = await deleteDonation(row.id);

    if (res.code === 200) {
      message("删除成功", { type: "success" });
      loadDonationList();
    } else {
      message(res.message || "删除失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("删除失败:", error);
    }
  }
};

// 恢复捐赠记录
const handleRestore = async (row: Donation) => {
  try {
    const res = await restoreDonation(row.id);

    if (res.code === 200) {
      message("恢复成功", { type: "success" });
      loadDonationList();
    } else {
      message(res.message || "恢复失败", { type: "error" });
    }
  } catch (error) {
    console.error("恢复失败:", error);
    message("恢复失败", { type: "error" });
  }
};

// 批量删除
const handleBatchDelete = async () => {
  if (selectedDonations.value.length === 0) {
    message("请选择要删除的记录", { type: "warning" });
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedDonations.value.length} 条记录吗？`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const ids = selectedDonations.value.map(item => item.id);
    const res = await batchDeleteDonation(ids);

    if (res.code === 200) {
      message("批量删除成功", { type: "success" });
      loadDonationList();
    } else {
      message(res.message || "批量删除失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("批量删除失败:", error);
    }
  }
};

// 显示统计数据
const showStatistics = async () => {
  try {
    const res = await getDonationStatistics();

    if (res.code === 200) {
      const stats = res.data;
      const content = `
        <div style="text-align: left; line-height: 2;">
          <p><strong>总记录数：</strong>${stats.total_count}</p>
          <p><strong>今日捐赠：</strong>${stats.amount_stats.today_count} 笔，金额 ¥${stats.amount_stats.today_amount}</p>
          <p><strong>本月捐赠：</strong>${stats.amount_stats.month_count} 笔，金额 ¥${stats.amount_stats.month_amount}</p>
          <p><strong>总金额：</strong>¥${stats.amount_stats.total_amount}</p>
          <p><strong>卡密等值：</strong>¥${stats.amount_stats.cardkey_total_value}</p>
        </div>
      `;

      ElMessageBox.alert(content, "统计数据", {
        dangerouslyUseHTMLString: true,
        confirmButtonText: "确定"
      });
    }
  } catch (error) {
    console.error("获取统计数据失败:", error);
  }
};

// 对话框关闭
const handleDialogClosed = () => {
  currentDonationId.value = null;
};

// 提交成功
const handleSubmitSuccess = () => {
  showDonationForm.value = false;
  loadDonationList();
};

// 初始化
onMounted(() => {
  loadOptions();
  loadDonationList();
});
</script>

<style scoped lang="scss">
.donation-container {

  // 中等屏幕适配
  @media (width <= 1200px) {
    .search-section {
      .el-row {
        align-items: flex-start;
      }

      .search-col {
        margin-bottom: 10px;

        &:last-child {
          margin-bottom: 0;
        }
      }

      .search-buttons {
        flex-wrap: wrap;

        .el-button {
          flex: 1;
          min-width: 80px;
        }
      }
    }
  }

  // 响应式适配
  @media (width <= 768px) {
    .page-header {
      padding: 14px 16px;

      .header-left {
        flex-wrap: wrap;

        .header-icon {
          font-size: 16px;
        }

        .header-title {
          font-size: 15px;
        }

        .header-count {
          font-size: 12px;
        }
      }
    }

    .search-section {
      padding: 12px 16px;

      .search-col {
        margin-bottom: 10px;

        &:last-child {
          margin-bottom: 0;
        }
      }

      .search-buttons {
        flex-direction: column;
        gap: 8px;

        .el-button {
          width: 100%;
        }
      }
    }

    .table-header {
      flex-direction: column;
      padding: 12px 16px;

      .el-button {
        width: 100%;
      }
    }

    .compact-divider {
      margin: 12px 16px;
    }

    :deep(.el-table) {
      margin: 0 16px 16px;
    }

    .pagination {
      flex-wrap: wrap;
      justify-content: center;
      padding: 12px 16px;
    }
  }
  // 移除padding，因为main-content已经设置了间距

  .box-card {
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 4px;

    :deep(.el-card__body) {
      padding: 0;
    }
  }

  // 页面标题区域
  .page-header {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--el-border-color-lighter);

    .header-left {
      display: flex;
      gap: 10px;
      align-items: center;

      .header-icon {
        font-size: 18px;
        color: var(--el-color-primary);
      }

      .header-title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: var(--el-text-color-primary);
      }

      .el-divider {
        margin: 0 4px;
      }

      .header-count {
        font-size: 13px;
        color: var(--el-text-color-secondary);
      }
    }
  }

  // 搜索区域
  .search-section {
    padding: 14px 20px;
    background: var(--el-fill-color-blank);

    .el-row {
      align-items: flex-end;
    }

    .search-col {
      margin-bottom: 0;
    }

    .search-buttons {
      display: flex;
      gap: 8px;

      .el-button {
        flex-shrink: 0;
      }
    }
  }

  // 分隔线
  .section-divider {
    margin: 0;
  }

  .table-header {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 16px 24px;
  }

  .compact-divider {
    margin: 16px 24px;
  }

  // 表格容器
  :deep(.el-table) {
    margin: 0 24px 20px;
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
    white-space: nowrap;
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

  .amount-wrapper {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
    white-space: nowrap;

    .amount-icon {
      font-size: 13px;

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
      font-size: 13px;
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

  .channel-detail {
    display: flex;
    align-items: center;

    .detail-content {
      display: inline-flex;
      gap: 6px;
      align-items: center;
      padding: 4px 10px;
      font-size: 12px;
      color: var(--el-text-color-regular);
      background: var(--el-fill-color-light);
      border-radius: 4px;
      transition: all 0.3s;

      &:hover {
        background: var(--el-fill-color);
      }

      &.crypto {
        color: #e65100;
        background: #fff3e0;

        .detail-icon {
          color: #e65100;
        }
      }

      .detail-icon {
        flex-shrink: 0;
        font-size: 12px;
        color: var(--el-color-primary);
      }

      .detail-text {
        overflow: hidden;
        font-family: Consolas, Monaco, monospace;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }
  }

  .public-icon {
    color: #67c23a;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
      transform: scale(1.2);
    }
  }

  .private-icon {
    color: #909399;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
      transform: scale(1.2);
    }
  }

  .action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: center;

    .el-button {
      margin: 0;
    }
  }

  .pagination {
    display: flex;
    justify-content: flex-end;
    padding: 16px 24px;
    border-top: 1px solid var(--el-border-color-lighter);
  }

  // 暗黑模式适配
  html.dark & {
    .page-header {
      border-bottom-color: var(--el-border-color);
    }

    .search-section {
      background: var(--el-bg-color);
    }

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

// 详情抽屉样式 - 移除头部的margin-bottom
.donation-detail-drawer {
  :deep(.el-drawer__header) {
    margin-bottom: 0 !important;
  }
}
</style>
