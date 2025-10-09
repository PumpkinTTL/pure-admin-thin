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
      <el-tabs v-model="activeTab" class="card-key-tabs" @tab-change="handleTabChange">
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

      <!-- 测试操作区域 -->
      <el-card class="test-area" shadow="never">
        <template #header>
          <div class="test-area-header">
            <span class="test-area-title">
              <IconifyIconOnline icon="ep:experiment" />
              测试操作区域
            </span>
            <span class="selected-count">已选择 {{ selectedIds.length }} 张卡密</span>
          </div>
        </template>
        
        <div class="test-buttons">
          <el-button 
            type="primary" 
            size="small" 
            @click="handleTestUse"
            :disabled="selectedIds.length !== 1"
          >
            <IconifyIconOnline icon="ep:check" />
            模拟使用
          </el-button>
          
          <el-button 
            type="warning" 
            size="small" 
            @click="handleTestVerify"
            :disabled="selectedIds.length !== 1"
          >
            <IconifyIconOnline icon="ep:search" />
            验证卡密
          </el-button>
          
          <el-button 
            type="danger" 
            size="small" 
            @click="handleTestDisable"
            :disabled="selectedIds.length === 0"
          >
            <IconifyIconOnline icon="ep:close" />
            禁用卡密
          </el-button>
          
          <el-button 
            type="success" 
            size="small" 
            @click="handleBatchTest"
            :disabled="selectedIds.length === 0"
          >
            <IconifyIconOnline icon="ep:magic-stick" />
            批量测试
          </el-button>
        </div>
      </el-card>

      <!-- 数据表格 -->
      <!-- 数据表格 -->
      <el-table 
        :data="tableData" 
        v-loading="loading" 
        class="modern-table"
        @selection-change="handleSelectionChange" 
        :header-cell-style="{ background: '#f8fafc', color: '#475569', fontWeight: '500' }">
        <el-table-column type="selection" width="45" align="center" fixed />
        <el-table-column prop="id" label="ID" width="60" align="center" />

        <!-- 卡密码列 -->
        <el-table-column prop="card_key" label="卡密码" min-width="180" align="center">
          <template #default="{ row }">
            <div class="code-cell">
              <el-tag effect="light" class="code-tag">
                {{ row.card_key }}
              </el-tag>
              <el-tooltip content="复制" placement="top">
                <el-icon class="copy-icon" @click="handleCopyCode(row.card_key)">
                  <IconifyIconOnline icon="ep:document-copy" />
                </el-icon>
              </el-tooltip>
            </div>
          </template>
        </el-table-column>

        <!-- 类型列 -->
        <el-table-column prop="type" label="类型" min-width="110" align="center">
          <template #default="{ row }">
            <span class="type-text">{{ row.cardType?.type_name || row.type || '-' }}</span>
          </template>
        </el-table-column>

        <!-- 状态列 -->
        <el-table-column prop="status" label="状态" width="90" align="center">
          <template #default="{ row }">
            <span :class="['status-badge', 'status-' + row.status]">
              {{ getStatusText(row.status) }}
            </span>
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
        <el-table-column prop="membership_duration" label="赠送时长" min-width="120" align="center">
          <template #default="{ row }">
            <span v-if="row.cardType?.membership_duration === null || row.cardType?.membership_duration === undefined" class="empty-text">-</span>
            <span v-else-if="row.cardType?.membership_duration === 0" class="duration-badge">
              永久
            </span>
            <span v-else class="duration-text">{{ formatMembershipDuration(row.cardType.membership_duration) }}</span>
          </template>
        </el-table-column>

        <!-- 兑换期限列 -->
        <el-table-column prop="expire_time" label="兑换期限" min-width="160" align="center">
          <template #default="{ row }">
            <span v-if="!row.expire_time || row.expire_time === '0000-00-00 00:00:00'" class="permanent-badge">
              永久可用
            </span>
            <span v-else :class="isAvailableExpired(row.expire_time) ? 'expired-text' : 'time-text'">
              {{ formatDateTime(row.expire_time) }}
            </span>
          </template>
        </el-table-column>

        <!-- 创建时间列 -->
        <el-table-column prop="create_time" label="创建时间" min-width="155" align="center">
          <template #default="{ row }">
            <span class="time-text">{{ row.create_time }}</span>
          </template>
        </el-table-column>

        <!-- 使用时间列 -->
        <el-table-column prop="use_time" label="使用时间" min-width="155" align="center">
          <template #default="{ row }">
            <span v-if="row.use_time" class="time-text">{{ row.use_time }}</span>
            <span v-else class="empty-text">-</span>
          </template>
        </el-table-column>

        <!-- 操作列 -->
        <el-table-column label="操作" fixed="right" min-width="150" align="center">
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
          <TypeManage ref="typeManageRef" />
        </el-tab-pane>
        
        <!-- 使用日志Tab -->
        <el-tab-pane label="使用日志" name="usageLogs">
          <div class="logs-container">
            <!-- 日志表格 -->
            <el-table 
              :data="logsData" 
              v-loading="logsLoading" 
              class="modern-table logs-table"
              :header-cell-style="{ background: '#f8fafc', color: '#475569', fontWeight: '500' }">
              <el-table-column prop="id" label="ID" width="70" align="center" />
              <el-table-column prop="card_key" label="卡密码" min-width="180" align="center">
                <template #default="{ row }">
                  <el-tag effect="plain" class="code-tag" size="small">
                    {{ row.cardKey?.card_key || '-' }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="action" label="操作" width="90" align="center">
                <template #default="{ row }">
                  <el-tag :type="getLogActionType(row.action)" size="small" effect="plain">
                    {{ row.action }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="user" label="用户" min-width="140" align="center">
                <template #default="{ row }">
                  <div class="user-info">
                    <IconifyIconOnline icon="ep:user" class="user-icon" />
                    <span>{{ row.username || row.nickname || `ID: ${row.user_id}` }}</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="ip" label="IP地址" width="140" align="center">
                <template #default="{ row }">
                  <div class="ip-info">
                    <IconifyIconOnline icon="ep:location" class="ip-icon" />
                    <span>{{ row.ip || '-' }}</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="remark" label="备注" min-width="180" align="center" show-overflow-tooltip>
                <template #default="{ row }">
                  <span class="remark-text">{{ row.remark || '-' }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="create_time" label="时间" width="165" align="center">
                <template #default="{ row }">
                  <div class="time-info">
                    <IconifyIconOnline icon="ep:clock" class="time-icon" />
                    <span>{{ row.create_time }}</span>
                  </div>
                </template>
              </el-table-column>
            </el-table>

            <!-- 分页 -->
            <div class="pagination-wrapper">
              <el-pagination
                v-model:current-page="logsPagination.currentPage"
                v-model:page-size="logsPagination.pageSize"
                :page-sizes="logsPagination.pageSizes"
                :background="true"
                layout="total, sizes, prev, pager, next, jumper"
                :total="logsPagination.total"
                size="small"
                @size-change="handleLogsSizeChange"
                @current-change="handleLogsCurrentChange"
              />
            </div>
          </div>
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
import { ref, reactive, onMounted, watch } from "vue";
import { ElMessageBox } from "element-plus";
import { message } from "@/utils/message";
import {
  getCardKeyList,
  deleteCardKey,
  batchDeleteCardKey,
  exportCardKeys,
  getCardKeyTypes,
  verifyCardKey,
  useCardKey,
  disableCardKey,
  batchDisableCardKey,
  getAllCardKeyLogs,
  CardKeyStatus,
  CardKeyStatusMap,
  CardKeyStatusTypeMap,
  formatMembershipDuration,
  type CardKey,
  type CardKeyListParams,
  type UseCardKeyParams,
  type DisableCardKeyParams
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
const typeManageRef = ref<any>(null);

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

// 日志数据
const logsData = ref<any[]>([]);
const logsLoading = ref(false);
const logsPagination = reactive({
  total: 0,
  pageSize: 5,
  currentPage: 1,
  pageSizes: [5, 10, 20, 50, 100]
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
      `确定要删除卡密 ${row.card_key} 吗？`,
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

/**
 * Tab切换处理
 */
const handleTabChange = (tabName: string) => {
  if (tabName === 'cardKeys') {
    // 切换到卡密列表时刷新数据
    fetchList();
    fetchTypes();
  } else if (tabName === 'cardTypes') {
    // 切换到类型管理时刷新数据
    if (typeManageRef.value && typeof typeManageRef.value.fetchList === 'function') {
      typeManageRef.value.fetchList();
    }
  } else if (tabName === 'usageLogs') {
    // 切换到使用日志时刷新数据
    fetchLogs();
  }
};

/**
 * 获取选中的卡密对象
 */
const getSelectedCardKey = (): CardKey | null => {
  if (selectedIds.value.length !== 1) return null;
  return tableData.value.find(item => item.id === selectedIds.value[0]) || null;
};

/**
 * 测试：模拟使用卡密
 */
const handleTestUse = async () => {
  const cardKey = getSelectedCardKey();
  console.log('=== 模拟使用：选中的卡密对象 ===', cardKey);
  console.log('=== card_key字段 ===', cardKey?.card_key);
  
  if (!cardKey) {
    message("请选择一张卡密", { type: "warning" });
    return;
  }

  if (!cardKey.card_key) {
    message("卡密码不能为空，请检查数据", { type: "error" });
    return;
  }

  if (cardKey.status !== CardKeyStatus.UNUSED) {
    message("只能使用未使用状态的卡密", { type: "warning" });
    return;
  }

  try {
    await ElMessageBox.prompt("请输入测试用户ID（默认为1）", "模拟使用卡密", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      inputValue: "1",
      inputPattern: /^\d+$/,
      inputErrorMessage: "请输入有效的用户ID"
    }).then(async ({ value }) => {
      const params: UseCardKeyParams = {
        card_key: cardKey.card_key,
        user_id: parseInt(value),
        remark: "测试使用"
      };

      const response = await useCardKey(params);
      if (response.code === 200) {
        message("使用成功！", { type: "success" });
        fetchList();
      } else {
        message(response.message || "使用失败", { type: "error" });
      }
    });
  } catch (error) {
    if (error !== "cancel") {
      message("操作失败", { type: "error" });
    }
  }
};

/**
 * 测试：验证卡密
 */
const handleTestVerify = async () => {
  const cardKey = getSelectedCardKey();
  console.log('=== 验证卡密：选中的卡密对象 ===', cardKey);
  console.log('=== card_key字段 ===', cardKey?.card_key);
  
  if (!cardKey) {
    message("请选择一张卡密", { type: "warning" });
    return;
  }

  if (!cardKey.card_key) {
    message("卡密码不能为空，请检查数据", { type: "error" });
    return;
  }

  try {
    const response = await verifyCardKey(cardKey.card_key);
    if (response.code === 200) {
      const data = response.data;
      ElMessageBox.alert(
        `
          <div style="line-height: 2;">
            <p><strong>卡密码：</strong>${data.card_key}</p>
            <p><strong>类型：</strong>${data.cardType?.type_name || '-'}</p>
            <p><strong>状态：</strong>${CardKeyStatusMap[data.status]}</p>
            <p><strong>价格：</strong>￥${data.cardType?.price || 0}</p>
            <p><strong>会员时长：</strong>${formatMembershipDuration(data.cardType?.membership_duration || 0)}</p>
            <p><strong>兑换期限：</strong>${data.expire_time || '永久可用'}</p>
          </div>
        `,
        "卡密验证成功",
        {
          dangerouslyUseHTMLString: true,
          confirmButtonText: "关闭"
        }
      );
    } else {
      message(response.message || "验证失败", { type: "error" });
    }
  } catch (error) {
    message("验证失败", { type: "error" });
  }
};

/**
 * 测试：禁用卡密
 */
const handleTestDisable = async () => {
  if (selectedIds.value.length === 0) {
    message("请选择要禁用的卡密", { type: "warning" });
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要禁用选中的 ${selectedIds.value.length} 张卡密吗？<br/><span style="color: #909399; font-size: 12px;">注：已使用的卡密无法禁用</span>`,
      "禁用确认",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning",
        dangerouslyUseHTMLString: true
      }
    );

    // 调用批量禁用API
    const params: DisableCardKeyParams = {
      user_id: 1,
      reason: "测试禁用"
    };
    const response = await batchDisableCardKey(selectedIds.value, params);
    
    if (response.code === 200) {
      const data = response.data;
      // 构建详细消息
      let detailMessage = '';
      if (data.success_count > 0) {
        detailMessage += `成功禁用 <strong style="color: #67c23a;">${data.success_count}</strong> 张卡密`;
      }
      if (data.used_count > 0) {
        detailMessage += (detailMessage ? '<br/>' : '') + `<strong style="color: #f56c6c;">${data.used_count}</strong> 张卡密已使用无法禁用`;
      }
      if (data.disabled_count > 0) {
        detailMessage += (detailMessage ? '<br/>' : '') + `<strong style="color: #909399;">${data.disabled_count}</strong> 张卡密已被禁用`;
      }
      
      // 显示结果对话框
      await ElMessageBox.alert(
        `<div style="line-height: 2;">${detailMessage}</div>`,
        "禁用完成",
        {
          confirmButtonText: "关闭",
          dangerouslyUseHTMLString: true,
          type: data.success_count > 0 ? "success" : "warning"
        }
      );
    } else {
      message(response.message || "禁用失败", { type: "error" });
    }

    fetchList();
  } catch (error) {
    if (error !== "cancel") {
      message("操作失败", { type: "error" });
    }
  }
};

/**
 * 测试：批量测试
 */
const handleBatchTest = async () => {
  if (selectedIds.value.length === 0) {
    message("请选择要测试的卡密", { type: "warning" });
    return;
  }

  try {
    await ElMessageBox.confirm(
      `将模拟使用选中的 ${selectedIds.value.length} 张卡密（仅处理未使用状态），是否继续？`,
      "批量测试",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    let successCount = 0;
    let skipCount = 0;
    let failCount = 0;

    for (const id of selectedIds.value) {
      const cardKey = tableData.value.find(item => item.id === id);
      if (!cardKey) continue;

      if (cardKey.status !== CardKeyStatus.UNUSED) {
        skipCount++;
        continue;
      }

      try {
        const params: UseCardKeyParams = {
          card_key: cardKey.card_key,
          user_id: 1,
          remark: "批量测试"
        };
        const response = await useCardKey(params);
        if (response.code === 200) {
          successCount++;
        } else {
          failCount++;
        }
      } catch (error) {
        failCount++;
      }
    }

    message(
      `测试完成！成功 ${successCount} 张，跳过 ${skipCount} 张，失败 ${failCount} 张`,
      { type: failCount === 0 ? "success" : "warning" }
    );

    fetchList();
  } catch (error) {
    if (error !== "cancel") {
      message("操作失败", { type: "error" });
    }
  }
};

/**
 * 获取使用日志列表
 */
const fetchLogs = async () => {
  logsLoading.value = true;
  try {
    const params = {
      page: logsPagination.currentPage,
      limit: logsPagination.pageSize
    };
    const response = await getAllCardKeyLogs(params);

    if (response.code === 200) {
      logsData.value = response.data.list || [];
      logsPagination.total = response.data.total || 0;
    } else {
      message(response.message || "获取日志失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取日志错误:", error);
    message("获取日志失败", { type: "error" });
  } finally {
    logsLoading.value = false;
  }
};

/**
 * 日志分页大小变化
 */
const handleLogsSizeChange = (size: number) => {
  logsPagination.pageSize = size;
  fetchLogs();
};

/**
 * 日志当前页变化
 */
const handleLogsCurrentChange = (page: number) => {
  logsPagination.currentPage = page;
  fetchLogs();
};

/**
 * 获取日志操作类型
 */
const getLogActionType = (action: string): string => {
  const typeMap: Record<string, string> = {
    "使用": "success",
    "use": "success",
    "验证": "info",
    "verify": "info",
    "禁用": "danger",
    "disable": "danger",
    "启用": "success",
    "enable": "success"
  };
  return typeMap[action] || "info";
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

  // 现代化表格样式
  .modern-table {
    margin-top: 16px;
    border-radius: 8px;
    overflow: hidden;

    :deep(.el-table__inner-wrapper) {
      &::before {
        display: none;
      }
    }

    :deep(.el-table__body) {
      tr {
        transition: background-color 0.2s ease;
        
        &:hover {
          background-color: #f8fafc !important;
        }
      }

      td {
        border-bottom: 1px solid #f1f5f9;
        padding: 12px 0;
      }
    }

    :deep(.el-table__header) {
      th {
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 0;
        font-size: 13px;
      }
    }
  }

  // 表格样式增强
  .code-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    .code-tag {
      font-family: "Consolas", "Monaco", monospace;
      font-size: 12px;
      font-weight: 500;
      letter-spacing: 0.5px;
      padding: 4px 10px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      color: #475569;
      border-radius: 4px;
    }

    .copy-icon {
      cursor: pointer;
      color: #94a3b8;
      font-size: 16px;
      transition: all 0.2s ease;

      &:hover {
        color: #3b82f6;
        transform: scale(1.1);
      }
    }
  }

  // 状态标记
  .status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    
    &.status-0 {
      background: #e0f2fe;
      color: #0284c7;
    }
    
    &.status-1 {
      background: #f0fdf4;
      color: #16a34a;
    }
    
    &.status-2 {
      background: #fef2f2;
      color: #dc2626;
    }
  }

  // 类型文本
  .type-text {
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
  }

  // 时长标记
  .duration-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    background: #f0fdf4;
    color: #16a34a;
  }

  .duration-text {
    color: #475569;
    font-size: 13px;
  }

  // 永久标记
  .permanent-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    background: #f0fdf4;
    color: #16a34a;
  }

  // 价格文本
  .price-text {
    color: #ef4444;
    font-weight: 600;
    font-size: 13px;
  }

  // 过期文本
  .expired-text {
    color: #ef4444;
    font-size: 13px;
  }

  // 时间文本
  .time-text {
    color: #64748b;
    font-size: 13px;
  }

  // 空文本
  .empty-text {
    color: #94a3b8;
    font-size: 13px;
  }
  
  // 使用日志表格样式
  .logs-table {
    .user-info,
    .ip-info,
    .time-info {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      font-size: 13px;
      color: #475569;
      
      .user-icon,
      .ip-icon,
      .time-icon {
        font-size: 14px;
        color: #94a3b8;
      }
    }
    
    .remark-text {
      font-size: 13px;
      color: #64748b;
      line-height: 1.4;
    }
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

  // 测试操作区域
  .test-area {
    margin-top: 16px;
    margin-bottom: 16px;
    border-radius: 8px;
    border: 2px dashed #e2e8f0;
    background: #fafbfc;

    :deep(.el-card__header) {
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      border-bottom: 1px solid #e2e8f0;
      padding: 12px 20px;
    }

    :deep(.el-card__body) {
      padding: 16px 20px;
    }

    .test-area-header {
      display: flex;
      align-items: center;
      justify-content: space-between;

      .test-area-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;

        .iconify {
          font-size: 18px;
          color: #3b82f6;
        }
      }

      .selected-count {
        font-size: 12px;
        color: #64748b;
        background: white;
        padding: 4px 12px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
      }
    }

    .test-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
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
