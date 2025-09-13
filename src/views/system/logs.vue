<template>
  <div class="logs-container">
    <div class="main-content">
      <!-- 标题和Tab选择器 -->
      <div class="header-section">
        <h2 class="page-title">日志管理</h2>
        <div class="tab-selector">
          <el-radio-group v-model="activeTab" size="small" @change="handleTabChange">
            <el-radio-button label="api">API日志</el-radio-button>
            <el-radio-button label="system">系统日志</el-radio-button>
          </el-radio-group>
        </div>
      </div>

      <!-- API日志管理 -->
      <div v-if="activeTab === 'api'">
        <!-- 统计和筛选区域 -->
        <div class="unified-panel">
          <!-- 统计卡片 -->
          <div class="stats-row" v-loading="statsLoading">
            <div class="stat-card">
              <div class="stat-icon total">
                <el-icon>
                  <DataLine />
                </el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-label">总请求量</div>
                <div class="stat-value">{{ stats.total_count || 0 }}</div>
              </div>
              <el-select v-model="statsTimeRange" size="small" @change="fetchApiLogStats" class="time-select">
                <el-option label="今日" value="today" />
                <el-option label="昨日" value="yesterday" />
                <el-option label="本周" value="week" />
                <el-option label="本月" value="month" />
              </el-select>
            </div>
            <div class="stat-card">
              <div class="stat-icon error">
                <el-icon>
                  <CircleClose />
                </el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-label">错误请求</div>
                <div class="stat-value">{{ stats.error_count || 0 }}</div>
                <div class="stat-detail">
                  错误率：{{ stats.total_count ? ((stats.error_count / stats.total_count) * 100).toFixed(2) : '0.00' }}%
                </div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon time">
                <el-icon>
                  <Timer />
                </el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-label">平均响应时间</div>
                <div class="stat-value">{{ stats.avg_execution_time ? stats.avg_execution_time.toFixed(2) : '0.00'
                }}<span class="unit">ms</span></div>
                <div class="stat-detail">
                  最慢：{{ stats.max_execution_time || 0 }}ms
                </div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon rate">
                <el-icon>
                  <Stopwatch />
                </el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-label">实时请求速率</div>
                <div class="stat-value">{{ stats.request_per_minute || 0 }}<span class="unit">次/分钟</span></div>
                <div class="stat-actions">
                  <el-button type="primary" size="small" plain circle @click="() => fetchApiLogStats()"
                    :icon="Refresh" />
                </div>
              </div>
            </div>
          </div>

          <!-- 搜索筛选 -->
          <div class="filter-section">
            <div class="section-header">
              <span class="section-title">筛选条件</span>
              <div class="section-actions">
                <el-button size="small" :icon="Search" type="primary" @click="handleSearch">搜索</el-button>
                <el-button size="small" :icon="RefreshLeft" @click="resetSearchForm">重置</el-button>
              </div>
            </div>

            <el-form :model="searchForm" class="search-form" @submit.prevent="handleSearch">
              <div class="form-grid">
                <div class="form-item">
                  <div class="form-label">API路径</div>
                  <el-input v-model="searchForm.url_path" placeholder="搜索API路径" clearable>
                    <template #prefix>
                      <el-icon>
                        <Link />
                      </el-icon>
                    </template>
                  </el-input>
                </div>
                <div class="form-item">
                  <div class="form-label">HTTP方法</div>
                  <el-select v-model="searchForm.http_method" placeholder="选择方法" clearable>
                    <el-option label="GET" value="GET" />
                    <el-option label="POST" value="POST" />
                    <el-option label="PUT" value="PUT" />
                    <el-option label="DELETE" value="DELETE" />
                  </el-select>
                </div>
                <div class="form-item">
                  <div class="form-label">状态码</div>
                  <el-input v-model="searchForm.status_code" placeholder="状态码" clearable>
                    <template #prefix>
                      <el-icon>
                        <InfoFilled />
                      </el-icon>
                    </template>
                  </el-input>
                </div>
                <div class="form-item">
                  <div class="form-label">IP地址</div>
                  <el-input v-model="searchForm.ip" placeholder="IP地址" clearable>
                    <template #prefix>
                      <el-icon>
                        <Monitor />
                      </el-icon>
                    </template>
                  </el-input>
                </div>
                <div class="form-item double">
                  <div class="form-label">时间范围</div>
                  <el-date-picker v-model="dateRange" type="datetimerange" range-separator="至" start-placeholder="开始时间"
                    end-placeholder="结束时间" format="YYYY-MM-DD HH:mm:ss" value-format="YYYY-MM-DD HH:mm:ss" />
                </div>
              </div>
            </el-form>
          </div>
        </div>

        <!-- 日志表格区域 -->
        <div class="logs-table">
          <div class="table-header">
            <div class="left-section">
              <span class="title">日志列表</span>
              <div class="log-type-selector">
                <el-radio-group v-model="logType" size="small" @change="handleLogTypeChange">
                  <el-radio-button label="all">所有日志</el-radio-button>
                  <el-radio-button label="error">错误日志</el-radio-button>
                  <el-radio-button label="slow">慢日志</el-radio-button>
                </el-radio-group>
                <el-input v-if="logType === 'slow'" v-model="slowThreshold" placeholder="响应时间阈值(ms)"
                  class="threshold-input">
                  <template #append>
                    <el-button @click="handleThresholdChange">确定</el-button>
                  </template>
                </el-input>
              </div>
            </div>
            <div class="action-buttons">
              <el-tooltip content="清理过期日志" placement="top">
                <el-button type="danger" :icon="Delete" size="small" plain @click="handleCleanLogs">清理日志</el-button>
              </el-tooltip>
              <el-tooltip content="导出日志" placement="top">
                <el-button :icon="Download" size="small" plain>导出</el-button>
              </el-tooltip>
              <el-tooltip content="刷新" placement="top">
                <el-button :icon="Refresh" circle @click="() => fetchApiLogs()" size="small" />
              </el-tooltip>
            </div>
          </div>

          <div class="table-container">
            <el-table v-loading="tableLoading" :data="logsList" @sort-change="handleSortChange" class="data-table">
              <el-table-column label="ID" prop="id" width="70" sortable="custom">
                <template #default="{ row }">
                  <div class="id-badge">{{ row.id }}</div>
                </template>
              </el-table-column>
              <el-table-column label="方法" prop="http_method" width="80">
                <template #default="{ row }">
                  <div class="method-badge" :class="row.http_method.toLowerCase()">
                    {{ row.http_method }}
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="API路径" prop="url_path" min-width="200" show-overflow-tooltip>
                <template #default="{ row }">
                  <div class="url-path">
                    <el-icon>
                      <Link />
                    </el-icon>
                    <span>{{ row.url_path }}</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="状态" prop="status_code" width="90">
                <template #default="{ row }">
                  <div class="status-badge" :class="getStatusClass(row.status_code)">
                    {{ row.status_code }}
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="IP地址" prop="ip" width="130" show-overflow-tooltip />
              <el-table-column label="执行时间" prop="execution_time" width="110" sortable="custom">
                <template #default="{ row }">
                  <div class="execution-time" :class="{
                    'success': row.execution_time < 500,
                    'warning': row.execution_time >= 500 && row.execution_time < 1000,
                    'danger': row.execution_time >= 1000
                  }">
                    {{ row.execution_time }} ms
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="请求时间" prop="create_time" width="170" sortable="custom" />
              <el-table-column label="操作" width="80" fixed="right">
                <template #default="{ row }">
                  <div class="table-actions">
                    <el-button type="primary" link size="small" @click="handleViewDetail(row)" :icon="View">
                      详情
                    </el-button>
                  </div>
                </template>
              </el-table-column>
            </el-table>
          </div>

          <!-- 分页信息 -->
          <div class="pagination-container">
            <div class="pagination-info">
              <div class="info-text">总共 <strong>{{ pageConfig.total }}</strong> 条记录，本地已加载 <strong>{{ allLogsList.length
                  }}</strong> 条</div>
            </div>
            <el-pagination v-model:current-page="pageConfig.currentPage" v-model:page-size="pageConfig.pageSize"
              :page-sizes="pageConfig.options" :total="pageConfig.total" layout="sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange" @current-change="handleCurrentChange" />
          </div>
        </div>

        <!-- 日志详情弹窗 -->
        <el-dialog v-model="showDetailDialog" title="API日志详情" width="800px" destroy-on-close
          :before-close="() => showDetailDialog = false" class="log-detail-dialog">
          <div v-loading="detailLoading" class="log-detail-container">
            <div class="detail-header">
              <div class="header-left">
                <div class="detail-id">日志 #{{ currentLog.id }}</div>
                <div class="detail-time">{{ currentLog.create_time }}</div>
              </div>
              <div class="header-right">
                <div class="method-badge" :class="currentLog.http_method?.toLowerCase()">
                  {{ currentLog.http_method }}
                </div>
                <div class="status-badge" :class="getStatusClass(currentLog.status_code)">
                  {{ currentLog.status_code }}
                </div>
              </div>
            </div>

            <div class="detail-section">
              <div class="section-row">
                <div class="detail-item">
                  <div class="item-label">请求路径</div>
                  <div class="item-value url-path">
                    <el-icon>
                      <Link />
                    </el-icon>
                    <span>{{ currentLog.url_path }}</span>
                  </div>
                </div>
              </div>
              <div class="section-row">
                <div class="detail-item">
                  <div class="item-label">IP地址</div>
                  <div class="item-value">{{ currentLog.ip }}</div>
                </div>
                <div class="detail-item">
                  <div class="item-label">用户ID</div>
                  <div class="item-value">{{ currentLog.user_id || '未登录用户' }}</div>
                </div>
                <div class="detail-item">
                  <div class="item-label">执行时间</div>
                  <div class="item-value execution-time" :class="{
                    'success': currentLog.execution_time < 500,
                    'warning': currentLog.execution_time >= 500 && currentLog.execution_time < 1000,
                    'danger': currentLog.execution_time >= 1000
                  }">
                    {{ currentLog.execution_time }} ms
                  </div>
                </div>
              </div>
              <div class="section-row">
                <div class="detail-item full-width">
                  <div class="item-label">用户代理</div>
                  <div class="item-value">{{ currentLog.user_agent }}</div>
                </div>
              </div>
            </div>

            <div class="request-params">
              <div class="params-header">请求参数</div>
              <div class="params-content">
                <div v-if="currentLog.request_params" class="json-content">
                  <el-button type="primary" size="small" :icon="CopyDocument"
                    @click="copyToClipboard(currentLog.request_params)" class="copy-btn">
                    复制
                  </el-button>
                  <pre>{{ formatJson(currentLog.request_params) }}</pre>
                </div>
                <el-empty v-else description="无请求参数" />
              </div>
            </div>
          </div>
        </el-dialog>

        <!-- 清理日志弹窗 -->
        <el-dialog v-model="showCleanDialog" title="清理过期日志" width="400px" destroy-on-close
          :before-close="() => showCleanDialog = false" class="clean-dialog">
          <div class="clean-dialog-content">
            <el-alert title="警告：此操作将永久删除指定天数前的日志，不可恢复！" type="warning" :closable="false" show-icon
              style="margin-bottom: 20px" />
            <el-form label-position="top">
              <el-form-item label="保留天数">
                <el-input-number v-model="cleanDays" :min="1" :max="365" :step="1" controls-position="right"
                  style="width: 100%" />
                <div class="form-help">将删除 {{ cleanDays }} 天前的所有日志</div>
              </el-form-item>
            </el-form>
          </div>
          <template #footer>
            <el-button @click="showCleanDialog = false">取消</el-button>
            <el-button type="danger" @click="confirmCleanLogs" :loading="cleanLoading">确认清理</el-button>
          </template>
        </el-dialog>
      </div>

      <!-- 系统日志管理 (待实现) -->
      <div v-else-if="activeTab === 'system'" class="empty-state">
        <el-empty description="系统日志功能正在开发中..." />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "logs"
});

import { ref, reactive, onMounted, computed, watch } from "vue";
import {
  Search,
  RefreshLeft,
  Delete,
  Download,
  Link,
  View,
  Refresh,
  InfoFilled,
  Timer,
  Stopwatch,
  Monitor,
  DataLine,
  CircleClose,
  CopyDocument,
  ArrowUp,
  ArrowDown
} from "@element-plus/icons-vue";
import { ElMessageBox, ElMessage } from "element-plus";
import { message } from "@/utils/message";
import {
  getApiLogList,
  getApiLogDetail,
  getApiLogStats,
  cleanApiLogs,
  getApiErrorLogs,
  getApiSlowLogs,
  type ApiLogInfo,
  type ApiLogListParams,
  type ApiLogStats
} from "@/api/logs";

// 基础状态
const activeTab = ref("api");
const tableLoading = ref(false);
const statsLoading = ref(false);
const detailLoading = ref(false);
const cleanLoading = ref(false);
const showDetailDialog = ref(false);
const showCleanDialog = ref(false);
const logType = ref("all");
const slowThreshold = ref<number | string>(1000);
const statsTimeRange = ref<"today" | "yesterday" | "week" | "month">("today");

// 日志数据
const allLogsList = ref<ApiLogInfo[]>([]); // 保存所有从服务器获取的日志数据
const logsList = ref<ApiLogInfo[]>([]); // 当前展示的日志数据（分页后）
const currentLog = ref<Partial<ApiLogInfo>>({});
const stats = ref<Partial<ApiLogStats>>({});
const cleanDays = ref(30);

// 搜索表单
const searchForm = reactive<Partial<ApiLogListParams>>({
  url_path: "",
  http_method: "",
  status_code: undefined,
  ip: "",
  order_field: undefined,
  order_direction: undefined
});

// 日期范围
const dateRange = ref<[string, string] | null>(null);

// 分页配置
const pageConfig = ref({
  currentPage: 1,
  pageSize: 5, // 默认每页显示5条
  total: 0,
  serverTotal: 0, // 服务器总数据量
  serverPage: 1, // 服务器当前页
  serverPageSize: 100, // 从服务器一次获取100条
  options: [5, 10, 20, 30]
});

// 表格样式
const headerCellStyle = {
  backgroundColor: "#f5f7fa",
  color: "#606266",
  fontWeight: "600",
  textAlign: "center" as const,
  height: "40px",
  padding: "6px 0"
};

// 监听日期范围变化，更新搜索表单
watch(dateRange, (value) => {
  if (value) {
    searchForm.start_time = value[0];
    searchForm.end_time = value[1];
  } else {
    searchForm.start_time = undefined;
    searchForm.end_time = undefined;
  }
});

// 计算当前页显示的数据
const updateCurrentPageData = () => {
  const start = (pageConfig.value.currentPage - 1) * pageConfig.value.pageSize;
  const end = start + pageConfig.value.pageSize;
  logsList.value = allLogsList.value.slice(start, end);
};

// 需要获取下一页服务器数据
const needNextServerPage = () => {
  const currentDisplayedCount = allLogsList.value.length;
  const totalNeededForCurrentView = pageConfig.value.currentPage * pageConfig.value.pageSize;

  // 如果当前页需要显示的数据超过了已加载的数据量，且服务器还有更多数据
  return totalNeededForCurrentView > currentDisplayedCount &&
    currentDisplayedCount < pageConfig.value.serverTotal;
};

// 检查是否需要加载更多服务器数据
const checkAndLoadMoreData = async () => {
  if (needNextServerPage()) {
    // 增加服务器页码
    pageConfig.value.serverPage += 1;
    await fetchApiLogs(true); // 追加模式加载数据
  } else {
    // 只更新当前页面显示
    updateCurrentPageData();
  }
};

// HTTP方法对应的标签类型
const getMethodTagType = (method: string) => {
  const methodMap: Record<string, string> = {
    GET: "success",
    POST: "primary",
    PUT: "warning",
    DELETE: "danger",
    PATCH: "info"
  };
  return methodMap[method] || "info";
};

// 状态码对应的样式类型
const getStatusClass = (statusCode: number) => {
  if (statusCode >= 200 && statusCode < 300) {
    return "success";
  } else if (statusCode >= 300 && statusCode < 400) {
    return "info";
  } else if (statusCode >= 400 && statusCode < 500) {
    return "warning";
  } else if (statusCode >= 500) {
    return "danger";
  }
  return "default";
};

// 格式化JSON字符串
const formatJson = (jsonString: string) => {
  try {
    const obj = JSON.parse(jsonString);
    return JSON.stringify(obj, null, 2);
  } catch (e) {
    return jsonString;
  }
};

// 复制到剪贴板
const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
    .then(() => {
      ElMessage.success("已复制到剪贴板");
    })
    .catch(() => {
      ElMessage.error("复制失败");
    });
};

// 切换Tab
const handleTabChange = (tab: string) => {
  if (tab === "api") {
    // 重置数据和分页
    resetData();
    fetchApiLogs();
    fetchApiLogStats();
  }
};

// 重置所有数据
const resetData = () => {
  allLogsList.value = [];
  logsList.value = [];
  pageConfig.value.currentPage = 1;
  pageConfig.value.serverPage = 1;
  pageConfig.value.total = 0;
  pageConfig.value.serverTotal = 0;
};

// 搜索
const handleSearch = () => {
  resetData();
  fetchApiLogs();
};

// 重置搜索表单
const resetSearchForm = () => {
  Object.keys(searchForm).forEach(key => {
    searchForm[key] = "";
  });
  dateRange.value = null;
  resetData();
  fetchApiLogs();
};

// 处理日志类型切换
const handleLogTypeChange = (type: string) => {
  resetData();
  fetchApiLogs();
};

// 处理阈值变化
const handleThresholdChange = () => {
  resetData();
  fetchApiLogs();
};

// 处理排序变化
const handleSortChange = ({ prop, order }: { prop: string; order: string }) => {
  if (order === "ascending") {
    searchForm.order_field = prop;
    searchForm.order_direction = "asc";
  } else if (order === "descending") {
    searchForm.order_field = prop;
    searchForm.order_direction = "desc";
  } else {
    searchForm.order_field = undefined;
    searchForm.order_direction = undefined;
  }
  resetData();
  fetchApiLogs();
};

// 查看日志详情
const handleViewDetail = async (row: ApiLogInfo) => {
  showDetailDialog.value = true;
  detailLoading.value = true;
  try {
    const res = await getApiLogDetail(row.id);
    if (res.code === 200) {
      currentLog.value = res.data;
    } else {
      message(res.msg || "获取日志详情失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取日志详情错误:", error);
    message("获取日志详情失败，请稍后重试", { type: "error" });
  } finally {
    detailLoading.value = false;
  }
};

// 清理日志
const handleCleanLogs = () => {
  showCleanDialog.value = true;
};

// 确认清理日志
const confirmCleanLogs = async () => {
  cleanLoading.value = true;
  try {
    const res = await cleanApiLogs(cleanDays.value);
    if (res.code === 200) {
      message("清理日志成功", { type: "success" });
      showCleanDialog.value = false;
      resetData();
      fetchApiLogs();
    } else {
      message(res.msg || "清理日志失败", { type: "error" });
    }
  } catch (error) {
    console.error("清理日志错误:", error);
    message("清理日志失败，请稍后重试", { type: "error" });
  } finally {
    cleanLoading.value = false;
  }
};

// 获取API日志列表
const fetchApiLogs = async (append = false) => {
  tableLoading.value = true;
  try {
    const params: ApiLogListParams = {
      ...searchForm,
      page: pageConfig.value.serverPage,
      page_size: pageConfig.value.serverPageSize
    };

    let res;
    if (logType.value === "error") {
      res = await getApiErrorLogs(params);
    } else if (logType.value === "slow") {
      res = await getApiSlowLogs(Number(slowThreshold.value) || 1000, params);
    } else {
      res = await getApiLogList(params);
    }

    if (res.code === 200) {
      const newLogs = res.data.list || [];

      if (append) {
        // 追加新数据
        allLogsList.value = [...allLogsList.value, ...newLogs];
      } else {
        // 重置数据
        allLogsList.value = newLogs;
      }

      // 更新服务器总数量
      pageConfig.value.serverTotal = res.data.pagination?.total || allLogsList.value.length;

      // 更新本地总数量
      pageConfig.value.total = allLogsList.value.length;

      // 如果还有更多服务器数据，调整本地总数为服务器总数
      if (pageConfig.value.serverTotal > allLogsList.value.length) {
        pageConfig.value.total = pageConfig.value.serverTotal;
      }

      // 更新当前页显示的数据
      updateCurrentPageData();
    } else {
      message(res.msg || "获取日志列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取日志列表错误:", error);
    message("获取日志列表失败，请稍后重试", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 获取API日志统计信息
const fetchApiLogStats = async () => {
  statsLoading.value = true;
  try {
    const res = await getApiLogStats(statsTimeRange.value);
    if (res.code === 200) {
      stats.value = res.data;
    } else {
      message(res.msg || "获取统计信息失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取统计信息错误:", error);
    message("获取统计信息失败，请稍后重试", { type: "error" });
  } finally {
    statsLoading.value = false;
  }
};

// 分页大小变化
const handleSizeChange = (val: number) => {
  pageConfig.value.pageSize = val;
  checkAndLoadMoreData();
};

// 页码变化
const handleCurrentChange = (val: number) => {
  pageConfig.value.currentPage = val;
  checkAndLoadMoreData();
};

onMounted(() => {
  fetchApiLogs();
  fetchApiLogStats();
});
</script>

<style lang="scss" scoped>
.logs-container {
  color: #333;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

  .main-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #fff;
    border-radius: 4px;
    padding: 8px 10px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);

    .page-title {
      font-size: 16px;
      font-weight: 600;
      color: #262626;
      margin: 0;
    }

    .tab-selector {
      // 移除可能冲突的样式
    }
  }

  .unified-panel {
    background-color: #fff;
    border-radius: 4px;
    padding: 10px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  // 统计卡片
  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;

    .stat-card {
      position: relative;
      background-color: #fafafa;
      border-radius: 4px;
      padding: 8px;
      display: flex;
      align-items: center;
      border: 1px solid #eee;

      .stat-icon {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 36px;
        height: 36px;
        border-radius: 4px;
        margin-right: 10px;
        flex-shrink: 0;

        .el-icon {
          font-size: 18px;
        }

        &.total {
          background-color: rgba(24, 144, 255, 0.1);
          color: #1890ff;
        }

        &.error {
          background-color: rgba(245, 34, 45, 0.1);
          color: #f5222d;
        }

        &.time {
          background-color: rgba(250, 173, 20, 0.1);
          color: #faad14;
        }

        &.rate {
          background-color: rgba(82, 196, 26, 0.1);
          color: #52c41a;
        }
      }

      .stat-info {
        flex-grow: 1;
      }

      .stat-label {
        font-size: 12px;
        color: #8c8c8c;
        margin-bottom: 2px;
      }

      .stat-value {
        font-size: 18px;
        font-weight: 600;
        color: #262626;
        margin: 0 0 2px;
        line-height: 1.1;

        .unit {
          font-size: 12px;
          font-weight: 400;
          color: #8c8c8c;
          margin-left: 2px;
        }
      }

      .stat-detail {
        font-size: 12px;
        color: #8c8c8c;
      }

      .stat-actions {
        margin-top: 4px;
      }

      .time-select {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 80px;
      }
    }
  }

  // 搜索区域
  .filter-section {
    border-top: 1px solid #eee;
    padding-top: 8px;

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;

      .section-title {
        font-size: 13px;
        font-weight: 600;
        color: #262626;
      }

      .section-actions {
        display: flex;
        gap: 4px;

        :deep(.el-button) {
          padding: 5px 10px;
        }
      }
    }

    .search-form {
      .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 6px;

        .form-item {
          &.double {
            grid-column: span 2;
          }

          .form-label {
            font-size: 11px;
            color: #595959;
            margin-bottom: 2px;
          }

          // 让表单控件更紧凑
          :deep(.el-input__wrapper),
          :deep(.el-select),
          :deep(.el-date-editor) {
            --el-component-size: 26px;
          }

          :deep(.el-input__inner) {
            height: 26px;
            line-height: 26px;
            font-size: 12px;
          }
        }
      }
    }
  }

  // 表格区域
  .logs-table {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    margin-top: 10px;

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 6px 8px;
      border-bottom: 1px solid #f0f0f0;

      .left-section {
        display: flex;
        align-items: center;
        gap: 10px;

        .title {
          font-size: 13px;
          font-weight: 600;
          color: #262626;
        }

        .log-type-selector {
          display: flex;
          align-items: center;
          gap: 6px;

          .threshold-input {
            width: 130px;
            height: 26px;

            :deep(.el-input__wrapper) {
              --el-component-size: 26px;
            }

            :deep(.el-input__inner) {
              height: 26px;
              line-height: 26px;
              font-size: 12px;
            }

            :deep(.el-input-group__append) {
              padding: 0 8px;
            }
          }
        }
      }

      .action-buttons {
        display: flex;
        align-items: center;
        gap: 4px;

        :deep(.el-button) {
          padding: 5px 8px;
          font-size: 12px;

          &.is-circle {
            padding: 5px;
          }
        }
      }
    }

    .table-container {
      padding: 0;

      .data-table {
        width: 100%;
      }

      :deep(.el-table) {
        --el-table-header-bg-color: #fafafa;
        --el-table-border-color: #f0f0f0;
        --el-table-row-hover-bg-color: #f5f7fa;
        --el-table-header-text-color: #262626;
        --el-table-text-color: #333;
        font-size: 12px;

        .el-table__header th {
          font-weight: 600;
          padding: 4px 6px;
          height: 28px;
        }

        .el-table__row td {
          padding: 3px 5px;
          height: 28px;
        }

        &::before {
          display: none;
        }
      }

      .id-badge {
        display: inline-block;
        background-color: #f5f5f5;
        color: #595959;
        font-size: 11px;
        font-weight: 500;
        padding: 1px 5px;
        border-radius: 3px;
      }

      .method-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 1px 5px;
        border-radius: 2px;
        text-align: center;

        &.get {
          background-color: rgba(24, 144, 255, 0.1);
          color: #1890ff;
        }

        &.post {
          background-color: rgba(82, 196, 26, 0.1);
          color: #52c41a;
        }

        &.put {
          background-color: rgba(250, 173, 20, 0.1);
          color: #faad14;
        }

        &.delete {
          background-color: rgba(245, 34, 45, 0.1);
          color: #f5222d;
        }
      }

      .url-path {
        display: flex;
        align-items: center;
        gap: 3px;

        .el-icon {
          font-size: 12px;
          color: #1890ff;
        }
      }

      .status-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 1px 5px;
        border-radius: 2px;
        text-align: center;

        &.success {
          background-color: rgba(82, 196, 26, 0.1);
          color: #52c41a;
        }

        &.info {
          background-color: rgba(24, 144, 255, 0.1);
          color: #1890ff;
        }

        &.warning {
          background-color: rgba(250, 173, 20, 0.1);
          color: #faad14;
        }

        &.danger {
          background-color: rgba(245, 34, 45, 0.1);
          color: #f5222d;
        }

        &.default {
          background-color: rgba(140, 140, 140, 0.1);
          color: #8c8c8c;
        }
      }

      .execution-time {
        font-weight: 500;
        font-size: 11px;

        &.success {
          color: #52c41a;
        }

        &.warning {
          color: #faad14;
        }

        &.danger {
          color: #f5222d;
        }
      }

      .table-actions {
        display: flex;
        justify-content: center;

        :deep(.el-button) {
          font-size: 12px;
          height: 22px;
          line-height: 22px;
        }
      }
    }

    // 分页
    .pagination-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 6px 8px;
      border-top: 1px solid #f0f0f0;

      .pagination-info {
        .info-text {
          font-size: 11px;
          color: #8c8c8c;

          strong {
            color: #262626;
            font-weight: 600;
          }
        }
      }

      :deep(.el-pagination) {

        button,
        .el-pager li {
          min-width: 24px;
          height: 24px;
          line-height: 24px;
          font-size: 12px;
        }

        .el-pagination__sizes {
          margin-right: 6px;
        }

        .el-pagination__jump {
          margin-left: 6px;
          font-size: 12px;
        }
      }
    }
  }

  // 日志详情弹窗
  .log-detail-dialog {
    :deep(.el-dialog__header) {
      border-bottom: 1px solid #f0f0f0;
      padding: 10px 12px;
      margin: 0;
    }

    :deep(.el-dialog__body) {
      padding: 0;
    }

    .log-detail-container {
      .detail-header {
        display: flex;
        justify-content: space-between;
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;

        .header-left {
          .detail-id {
            font-size: 16px;
            font-weight: 600;
            color: #262626;
            margin-bottom: 4px;
          }

          .detail-time {
            font-size: 12px;
            color: #8c8c8c;
          }
        }

        .header-right {
          display: flex;
          gap: 6px;
        }
      }

      .detail-section {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;

        .section-row {
          display: flex;
          margin-bottom: 10px;
          gap: 16px;

          &:last-child {
            margin-bottom: 0;
          }

          .detail-item {
            flex: 1;

            &.full-width {
              flex: 0 0 100%;
            }

            .item-label {
              font-size: 12px;
              color: #8c8c8c;
              margin-bottom: 3px;
            }

            .item-value {
              font-size: 13px;
              color: #262626;
            }
          }
        }
      }

      .request-params {
        padding: 12px;

        .params-header {
          font-size: 14px;
          font-weight: 600;
          color: #262626;
          margin-bottom: 10px;
        }

        .params-content {
          .json-content {
            position: relative;
            background-color: #fafafa;
            border-radius: 4px;
            padding: 10px;
            max-height: 240px;
            overflow: auto;
            font-family: 'Fira Code', Consolas, monospace;

            pre {
              margin: 0;
              font-size: 12px;
              line-height: 1.4;
              color: #333;
              white-space: pre-wrap;
              word-break: break-all;
            }

            .copy-btn {
              position: absolute;
              top: 6px;
              right: 6px;
            }
          }
        }
      }
    }
  }

  // 清理日志弹窗
  .clean-dialog {
    .form-help {
      font-size: 12px;
      color: #8c8c8c;
      margin-top: 4px;
    }
  }

  // 空状态
  .empty-state {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    padding: 30px 0;
  }

  // 响应式布局
  @media (max-width: 1200px) {
    .stats-row {
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }
  }

  @media (max-width: 991px) {
    .table-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;

      .left-section {
        width: 100%;
        justify-content: space-between;

        .log-type-selector {
          flex-wrap: wrap;
        }
      }

      .action-buttons {
        width: 100%;
        justify-content: flex-end;
      }
    }

    .pagination-container {
      flex-direction: column;
      gap: 8px;
      align-items: flex-start;

      .el-pagination {
        width: 100%;
      }
    }
  }

  @media (max-width: 768px) {
    .stats-row {
      grid-template-columns: 1fr;
      gap: 8px;
    }

    .header-section {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }

    .form-grid {
      grid-template-columns: 1fr !important;

      .form-item {
        &.double {
          grid-column: span 1 !important;
        }

        :deep(.el-date-editor.el-input__wrapper),
        :deep(.el-date-editor.el-range-editor) {
          width: 100% !important;
          max-width: 100% !important;
        }
      }
    }

    .logs-table {
      margin-top: 10px;
    }

    .header-section {
      .tab-selector {
        margin-top: 6px;
        width: 100%;

        :deep(.el-radio-group) {
          display: flex;
          width: 100%;

          .el-radio-button {
            flex: 1;

            &+.el-radio-button {
              margin-left: 8px !important;
            }

            .el-radio-button__inner {
              width: 100%;
              text-align: center;
            }
          }
        }
      }
    }

    .logs-table {
      margin-top: 10px;

      .table-header {
        .left-section {
          flex-direction: column;
          align-items: flex-start;
          gap: 8px;

          .log-type-selector {
            width: 100%;

            :deep(.el-radio-group) {
              display: flex;
              width: 100%;

              .el-radio-button {
                flex: 1;

                .el-radio-button__inner {
                  width: 100%;
                  text-align: center;
                }
              }
            }

            .threshold-input {
              width: 100%;
              margin-top: 6px;
            }
          }
        }
      }
    }
  }

  // 全局按钮样式覆盖 - 优化使其更加明显
  :deep(.el-radio-button) {
    margin-right: 0;

    .el-radio-button__inner {
      border: 1px solid var(--el-border-color) !important;
      border-radius: 3px !important;
      margin: 0 !important;
    }

    &+.el-radio-button {
      margin-left: 8px !important;
    }

    // 选中状态样式
    &.is-active {
      .el-radio-button__inner {
        color: var(--el-color-white) !important;
        background-color: var(--el-color-primary) !important;
        border-color: var(--el-color-primary) !important;
        box-shadow: none !important;


      }

      // 顶部导航按钮
      .header-section {
        .tab-selector {
          :deep(.el-radio-button) {
            &+.el-radio-button {
              margin-left: 10px !important;
            }
          }
        }
      }

      // 表格顶部按钮
      .logs-table {
        .table-header {
          .left-section {
            .log-type-selector {
              :deep(.el-radio-button) {
                &+.el-radio-button {
                  margin-left: 6px !important;
                }

                .el-radio-button__inner {
                  padding: 5px 8px;
                  font-size: 12px;
                }
              }
            }
          }
        }
      }
    }
  }
}
</style>