<template>
  <div class="mysql-event-container">
    <div class="main-content">
      <!-- 头部区域 -->
      <div class="header-section">
        <h2 class="page-title">数据库事件调度器</h2>
        <div class="action-group">
          <div class="scheduler-status">
            <span class="status-label">调度器状态:</span>
            <el-switch v-model="schedulerEnabled" :loading="toggleLoading" @change="handleToggleScheduler"
              active-text="已启用" inactive-text="已禁用" :disabled="!hasEventPrivilege" />
          </div>
          <el-tooltip content="查看系统状态" placement="top">
            <el-button type="info" size="small" circle @click="handleOpenSystemStatus">
              <el-icon>
                <InfoFilled />
              </el-icon>
            </el-button>
          </el-tooltip>
        </div>
      </div>

      <!-- 专业操作提示 -->
      <div class="warning-panel">
        <div class="warning-content">
          <el-icon class="warning-icon">
            <Warning />
          </el-icon>
          <span class="warning-text">数据库事件调度器是MySQL的高级功能，如果您对SQL不了解，请勿随意操作。</span>
        </div>
      </div>

      <!-- 工具栏区域 -->
      <div class="tools-section">
        <div class="left-tools">
          <el-input v-model="searchKeyword" placeholder="搜索事件名称" clearable @clear="handleSearch"
            @keyup.enter="handleSearch" class="search-input">
            <template #prefix>
              <el-icon>
                <Search />
              </el-icon>
            </template>
            <template #append>
              <el-button :icon="Search" @click="handleSearch">搜索</el-button>
            </template>
          </el-input>

          <el-select v-model="selectedSchema" placeholder="选择数据库" clearable @change="handleSchemaChange"
            class="schema-select">
            <el-option label="默认数据库" value="" />
            <el-option label="mysql" value="mysql" />
            <el-option label="information_schema" value="information_schema" />
            <el-option label="performance_schema" value="performance_schema" />
          </el-select>
        </div>

        <div class="right-tools">
          <el-button type="primary" @click="handleCreateEvent" :disabled="!hasEventPrivilege">
            <el-icon>
              <Plus />
            </el-icon>创建事件
          </el-button>
          <el-button :icon="Refresh" circle @click="() => fetchEventList()" />
        </div>
      </div>

      <!-- 事件列表区域 -->
      <div class="events-table">
        <div v-if="!systemStatus?.events_supported" class="alert-message">
          <el-alert title="事件功能不可用" type="error" :closable="false" show-icon size="small">
            <template #default>
              当前数据库不支持事件功能
            </template>
          </el-alert>
        </div>

        <div v-else-if="!hasEventPrivilege" class="alert-message">
          <el-alert title="权限不足" type="warning" :closable="false" show-icon size="small">
            <template #default>
              您当前没有管理MySQL事件的权限
            </template>
          </el-alert>
        </div>

        <div class="table-container">
          <el-table v-loading="tableLoading" :data="filteredEvents" style="width: 100%" border
            @row-click="handleRowClick">
            <el-table-column label="事件名称" prop="Name" min-width="160" show-overflow-tooltip />
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <div class="status-switch">
                  <el-switch v-model="row.statusEnabled" :loading="row.statusLoading"
                    @change="(val) => handleToggleEventStatus(row, val)" :disabled="!hasEventPrivilege"
                    active-color="#13ce66" inactive-color="#ff4949" inline-prompt :active-text="'开'"
                    :inactive-text="'关'" />
                </div>
              </template>
            </el-table-column>
            <el-table-column label="调度规则" min-width="180" show-overflow-tooltip>
              <template #default="{ row }">
                <span v-if="row['Interval value'] && row['Interval field']">
                  每 {{ row['Interval value'] }} {{ translateIntervalField(row['Interval field']) }}
                </span>
                <span v-else-if="row['Execute at']">
                  {{ formatDateTime(row['Execute at']) }}
                </span>
                <span v-else>{{ row.schedule || '-' }}</span>
              </template>
            </el-table-column>
            <el-table-column label="开始时间" prop="Starts" width="180" show-overflow-tooltip />
            <el-table-column label="结束时间" prop="Ends" width="180" show-overflow-tooltip />
            <el-table-column label="上次执行时间" prop="last_executed" width="180" show-overflow-tooltip />
            <el-table-column label="备注" prop="Comment" min-width="150" show-overflow-tooltip />
            <el-table-column label="操作" width="150" fixed="right">
              <template #default="{ row }">
                <div class="table-actions">
                  <el-button type="primary" link size="small" @click.stop="handleEditEvent(row)"
                    :disabled="!hasEventPrivilege">
                    编辑
                  </el-button>
                  <el-button type="success" link size="small" @click.stop="handleViewLogs(row)">
                    日志
                  </el-button>
                  <el-button type="danger" link size="small" @click.stop="handleDeleteEvent(row)"
                    :disabled="!hasEventPrivilege">
                    删除
                  </el-button>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- 分页 -->
        <div class="pagination-container">
          <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper" :total="totalCount" @size-change="handleSizeChange"
            @current-change="handleCurrentChange" />
        </div>
      </div>
    </div>

    <!-- 事件表单对话框 -->
    <el-dialog v-model="showEventDialog" :title="isEdit ? '编辑事件' : '创建事件'" width="800px" destroy-on-close>
      <el-form ref="eventFormRef" :model="eventForm" :rules="eventFormRules" label-width="100px" class="event-form">
        <el-form-item label="事件名称" prop="event_name">
          <el-input v-model="eventForm.event_name" :disabled="isEdit" placeholder="请输入事件名称" />
        </el-form-item>

        <el-form-item label="数据库" prop="schema">
          <el-select v-model="eventForm.schema" placeholder="选择数据库">
            <el-option label="默认数据库" value="" />
            <el-option label="mysql" value="mysql" />
            <el-option label="information_schema" value="information_schema" />
            <el-option label="performance_schema" value="performance_schema" />
          </el-select>
        </el-form-item>

        <el-form-item label="调度表达式" prop="schedule">
          <div class="schedule-selector">
            <el-select v-model="scheduleType" placeholder="选择调度类型" @change="handleScheduleTypeChange"
              class="schedule-type">
              <el-option label="间隔执行" value="interval" />
              <el-option label="指定时间" value="at" />
            </el-select>

            <template v-if="scheduleType === 'interval'">
              <div class="interval-selector">
                <span class="interval-text">每</span>
                <el-input-number v-model="intervalValue" :min="1" :max="999" controls-position="right"
                  class="interval-value" />
                <el-select v-model="intervalUnit" placeholder="选择单位" class="interval-unit">
                  <el-option label="秒" value="SECOND" />
                  <el-option label="分钟" value="MINUTE" />
                  <el-option label="小时" value="HOUR" />
                  <el-option label="天" value="DAY" />
                  <el-option label="周" value="WEEK" />
                  <el-option label="月" value="MONTH" />
                  <el-option label="季度" value="QUARTER" />
                  <el-option label="年" value="YEAR" />
                </el-select>
              </div>
            </template>

            <template v-else-if="scheduleType === 'at'">
              <el-date-picker v-model="atTimestamp" type="datetime" placeholder="选择执行时间点" format="YYYY-MM-DD HH:mm:ss"
                value-format="YYYY-MM-DD HH:mm:ss" class="at-timestamp" />
            </template>
          </div>
          <div class="form-help">
            <p v-if="scheduleType === 'interval'">生成的表达式: EVERY {{ intervalValue }} {{ intervalUnit }}</p>
            <p v-else-if="scheduleType === 'at'">生成的表达式: AT TIMESTAMP '{{ atTimestamp || '选择时间' }}'</p>
          </div>
          <input type="hidden" v-model="eventForm.schedule" />
        </el-form-item>

        <el-form-item label="开始时间" prop="start_time">
          <el-date-picker v-model="eventForm.start_time" type="datetime" placeholder="选择开始时间"
            format="YYYY-MM-DD HH:mm:ss" value-format="YYYY-MM-DD HH:mm:ss" />
        </el-form-item>

        <el-form-item label="结束时间" prop="end_time">
          <el-date-picker v-model="eventForm.end_time" type="datetime" placeholder="选择结束时间" format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss" />
        </el-form-item>

        <el-form-item label="SQL语句" prop="sql_statement" class="sql-form-item">
          <div class="simple-sql-editor">
            <el-input v-model="eventForm.sql_statement" type="textarea" rows="10" placeholder="请输入要执行的SQL语句"
              @input="updateSqlPreview" />
            <div class="sql-preview" v-if="showSqlPreview">
              <div class="preview-header">
                <span>SQL语法高亮预览</span>
                <el-button type="text" @click="showSqlPreview = false">关闭</el-button>
              </div>
              <div class="preview-content" v-html="highlightedSql"></div>
            </div>
            <div class="editor-actions">
              <el-button type="primary" size="small" @click="formatSqlToMarkdown">
                <el-icon>
                  <Edit />
                </el-icon> 格式化SQL
              </el-button>
              <el-button type="info" size="small" @click="toggleSqlPreview">
                <el-icon>
                  <View />
                </el-icon> {{ showSqlPreview ? '隐藏高亮' : '显示高亮' }}
              </el-button>
            </div>
          </div>
        </el-form-item>

        <el-form-item label="注释" prop="comment">
          <el-input v-model="eventForm.comment" placeholder="请输入注释" />
        </el-form-item>

        <el-form-item label="状态" prop="enable">
          <el-switch v-model="eventForm.enable" active-text="启用" inactive-text="禁用" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="showEventDialog = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitEvent" :loading="submitLoading">
          确认
        </el-button>
      </template>
    </el-dialog>

    <!-- 日志查看对话框 -->
    <el-dialog v-model="showLogsDialog" :title="`事件执行日志 - ${currentEventName}`" width="900px" destroy-on-close>
      <div v-loading="detailLoading" class="logs-wrapper">
        <div class="logs-header">
          <div class="logs-title">
            <el-icon>
              <Document />
            </el-icon>
            <span>执行历史记录</span>
          </div>
          <div class="logs-actions">
            <el-tooltip content="清空日志" placement="top">
              <el-button type="danger" size="small" plain @click="handleClearLogs" :disabled="!hasEventPrivilege">
                <el-icon>
                  <Delete />
                </el-icon>
                清空日志
              </el-button>
            </el-tooltip>
            <el-tooltip content="刷新" placement="top">
              <el-button type="primary" size="small" plain circle @click="handleRefreshLogs">
                <el-icon>
                  <Refresh />
                </el-icon>
              </el-button>
            </el-tooltip>
          </div>
        </div>

        <el-table v-if="currentEventLogs.length > 0" :data="currentEventLogs" border style="width: 100%"
          class="logs-table">
          <el-table-column label="ID" prop="id" width="80" />
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small" effect="light">
                {{ row.status === 1 ? '成功' : '失败' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="执行时间(ms)" prop="execution_time" width="120">
            <template #default="{ row }">
              <span :class="{
                'text-success': row.execution_time < 100,
                'text-warning': row.execution_time >= 100 && row.execution_time < 1000,
                'text-danger': row.execution_time >= 1000
              }">{{ row.execution_time }}</span>
            </template>
          </el-table-column>
          <el-table-column label="影响行数" prop="affected_rows" width="100" />
          <el-table-column label="创建时间" prop="create_time" width="180" />
          <el-table-column label="错误信息" prop="error_message" min-width="250" show-overflow-tooltip>
            <template #default="{ row }">
              <span v-if="row.error_message" class="error-message">{{ row.error_message }}</span>
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="80" fixed="right">
            <template #default="{ row }">
              <el-button type="primary" link size="small" @click="handleViewLogDetail(row)">
                详情
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <el-empty v-else description="暂无执行日志" />

        <!-- 添加分页信息显示 -->
        <div v-if="currentEventLogs.length > 0" class="pagination-info">
          <div class="info-text">
            总共 <strong>{{ logsPageConfig.total }}</strong> 条记录，
            本地已加载 <strong>{{ allLoadedLogs.length }}</strong> 条
          </div>
        </div>

        <!-- 修改分页组件 -->
        <div v-if="currentEventLogs.length > 0" class="pagination-container">
          <el-pagination v-model:current-page="logsPageConfig.currentPage" v-model:page-size="logsPageConfig.pageSize"
            :page-sizes="logsPageConfig.options" :total="logsPageConfig.total" layout="sizes, prev, pager, next, jumper"
            @size-change="handleLogsSizeChange" @current-change="handleLogsCurrentChange" />
        </div>

        <div v-if="currentEventLogs.length > 0" class="logs-summary">
          <div class="summary-item">
            <span class="label">总执行次数:</span>
            <span class="value">{{ logsPageConfig.total }}</span>
          </div>
          <div class="summary-item">
            <span class="label">成功次数:</span>
            <span class="value success">{{ getSuccessCount() }}</span>
          </div>
          <div class="summary-item">
            <span class="label">失败次数:</span>
            <span class="value danger">{{ getErrorCount() }}</span>
          </div>
          <div class="summary-item">
            <span class="label">平均执行时间:</span>
            <span class="value">{{ getAvgExecutionTime() }}ms</span>
          </div>
        </div>
      </div>
    </el-dialog>

    <!-- 日志详情对话框 -->
    <el-dialog v-model="showLogDetailDialog" title="日志详情" width="700px" append-to-body destroy-on-close>
      <div class="log-detail-container">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="执行ID">{{ currentLogDetail.id }}</el-descriptions-item>
          <el-descriptions-item label="事件名称">{{ currentLogDetail.event_name || currentLogDetail.message
          }}</el-descriptions-item>
          <el-descriptions-item label="执行状态">
            <el-tag :type="currentLogDetail.status === 1 ? 'success' : 'danger'" size="small">
              {{ currentLogDetail.status === 1 ? '成功' : '失败' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="执行时间">{{ currentLogDetail.execution_time }}ms</el-descriptions-item>
          <el-descriptions-item label="影响行数">{{ currentLogDetail.affected_rows }}</el-descriptions-item>
          <el-descriptions-item label="创建时间">{{ currentLogDetail.create_time }}</el-descriptions-item>
          <el-descriptions-item label="服务器ID">{{ currentLogDetail.server_id || '-' }}</el-descriptions-item>
          <el-descriptions-item label="错误信息">
            <span v-if="currentLogDetail.error_message" class="error-message">{{ currentLogDetail.error_message
            }}</span>
            <span v-else>-</span>
          </el-descriptions-item>
          <el-descriptions-item v-if="currentLogDetail.execution_info" label="执行详情">
            <div class="execution-info">{{ currentLogDetail.execution_info }}</div>
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>

    <!-- 系统状态对话框 -->
    <el-dialog v-model="showSystemStatusDialog" title="MySQL事件系统状态" width="600px" destroy-on-close>
      <div v-loading="systemStatusLoading" class="system-status-wrapper">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="数据库版本">
            <el-tag size="small">{{ systemStatus?.db_version || '-' }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="调度器状态">
            <el-tag :type="systemStatus?.is_enabled ? 'success' : 'danger'" size="small">
              {{ systemStatus?.scheduler_status || '-' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="事件功能支持">
            <el-tag :type="systemStatus?.events_supported ? 'success' : 'danger'" size="small">
              {{ systemStatus?.events_supported ? '支持' : '不支持' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="事件功能启用">
            <el-tag :type="systemStatus?.events_enabled ? 'success' : 'danger'" size="small">
              {{ systemStatus?.events_enabled ? '已启用' : '已禁用' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="用户权限">
            <el-tag :type="systemStatus?.has_privilege ? 'success' : 'danger'" size="small">
              {{ systemStatus?.has_privilege ? '有权限' : '无权限' }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <div v-if="userPrivileges && userPrivileges.privileges.length > 0" class="privileges-section">
          <h4>用户权限列表</h4>
          <div class="privileges-list">
            <el-tag v-for="privilege in userPrivileges.privileges" :key="privilege" size="small" class="privilege-tag">
              {{ privilege }}
            </el-tag>
          </div>
        </div>

        <div v-if="!systemStatus?.has_privilege" class="warning-section">
          <el-alert title="权限不足" type="warning" show-icon :closable="false" size="small">
            <template #default>
              您当前没有管理MySQL事件的权限
            </template>
          </el-alert>
        </div>
      </div>

      <template #footer>
        <el-button @click="showSystemStatusDialog = false">关闭</el-button>
        <el-button type="primary" @click="fetchSystemStatus">刷新</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "MysqlEvent"
});

import { ref, reactive, onMounted, computed, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Search, Refresh, Plus, InfoFilled, Warning, Document, Delete, Edit, View } from "@element-plus/icons-vue";
import { message } from "@/utils/message";
import {
  getMysqlEventList,
  getMysqlEventDetail,
  createMysqlEvent,
  alterMysqlEvent,
  dropMysqlEvent,
  toggleMysqlEventScheduler,
  getMysqlEventSchedulerStatus,
  checkMysqlEventPrivileges,
  getMysqlEventSystemStatus,
  getMysqlEventLogList,
  clearMysqlEventLogs,
  getMysqlEventLogStatistics,
  toggleMysqlEvent,
  type MysqlEventInfo,
  type MysqlEventLogInfo,
  type MysqlEventParams,
  type MysqlEventPrivileges,
  type MysqlEventSystemStatus,
  type MysqlEventLogStatistics
} from "@/api/mysqlEvent";

// 状态变量
const tableLoading = ref(false);
const submitLoading = ref(false);
const toggleLoading = ref(false);
const systemStatusLoading = ref(false);
const detailLoading = ref(false);
const searchKeyword = ref("");
const selectedSchema = ref("");
const schedulerEnabled = ref(false);
const currentPage = ref(1);
const pageSize = ref(10);
const totalCount = ref(0);
const showEventDialog = ref(false);
const showLogsDialog = ref(false);
const showLogDetailDialog = ref(false);
const isEdit = ref(false);
const eventsList = ref<MysqlEventInfo[]>([]);
const currentEventLogs = ref<MysqlEventLogInfo[]>([]);
const allLoadedLogs = ref<MysqlEventLogInfo[]>([]);
const currentEventName = ref("");
const currentLogDetail = ref<MysqlEventLogInfo>({
  id: 0,
  status: 1,
  execution_time: 0,
  affected_rows: 0,
  create_time: ""
});
const eventFormRef = ref();

// 权限和系统状态
const systemStatus = ref<MysqlEventSystemStatus | null>(null);
const userPrivileges = ref<MysqlEventPrivileges | null>(null);
const hasEventPrivilege = ref(false);
const showSystemStatusDialog = ref(false);

// 事件表单
const eventForm = reactive<MysqlEventParams>({
  event_name: "",
  schedule: "",
  sql_statement: "",
  enable: true,
  comment: "",
  start_time: "",
  end_time: "",
  schema: ""
});

// 表单验证规则
const eventFormRules = {
  event_name: [
    { required: true, message: "请输入事件名称", trigger: "blur" },
    { min: 3, max: 100, message: "长度在 3 到 100 个字符之间", trigger: "blur" }
  ],
  schedule: [
    { required: true, message: "请输入调度表达式", trigger: "blur" }
  ],
  sql_statement: [
    { required: true, message: "请输入SQL语句", trigger: "blur" }
  ]
};

// 调度表达式相关变量
const scheduleType = ref<'interval' | 'at'>('interval');
const intervalValue = ref(1);
const intervalUnit = ref('DAY');
const atTimestamp = ref('');

// 日志分页配置
const logsPageConfig = ref({
  currentPage: 1,
  pageSize: 20, // 每页显示20条
  total: 0, // 总日志数
  serverTotal: 0, // 服务器总数量
  serverPage: 1, // 服务器当前页
  serverPageSize: 1000, // 从服务器一次获取1000条
  options: [10, 20, 50, 100]
});

// 分页过滤后的数据
const filteredEvents = computed(() => {
  let result = [...eventsList.value];

  // 关键词过滤
  if (searchKeyword.value) {
    const keyword = searchKeyword.value.toLowerCase();
    result = result.filter(event =>
      event.event_name.toLowerCase().includes(keyword) ||
      (event.event_comment && event.event_comment.toLowerCase().includes(keyword))
    );
  }

  totalCount.value = result.length;

  // 分页
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;

  return result.slice(start, end);
});

// 获取事件列表
const fetchEventList = async () => {
  tableLoading.value = true;
  try {
    const res = await getMysqlEventList(selectedSchema.value || undefined);
    if (res.code === 200) {
      eventsList.value = res.data || [];

      // 处理事件状态，添加UI所需的属性
      eventsList.value.forEach(event => {
        // 添加状态开关所需的属性
        event.statusEnabled = event.Status === 'ENABLED';
        event.statusLoading = false;
      });

      totalCount.value = eventsList.value.length;
    } else {
      message(res.msg || "获取事件列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取事件列表错误:", error);
    message("获取事件列表失败，请稍后重试", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 获取调度器状态
const fetchSchedulerStatus = async () => {
  toggleLoading.value = true;
  try {
    // 首先尝试从系统状态获取
    if (systemStatus.value) {
      schedulerEnabled.value = systemStatus.value.is_enabled;
      toggleLoading.value = false;
      return;
    }

    // 如果没有系统状态，则单独获取调度器状态
    const res = await getMysqlEventSchedulerStatus();
    if (res.code === 200) {
      schedulerEnabled.value = res.data.enabled;
    } else {
      message(res.msg || "获取调度器状态失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取调度器状态错误:", error);
    message("获取调度器状态失败，请稍后重试", { type: "error" });
  } finally {
    toggleLoading.value = false;
  }
};

// 切换调度器状态
const handleToggleScheduler = async (value: boolean) => {
  toggleLoading.value = true;
  try {
    const res = await toggleMysqlEventScheduler(value);
    if (res.code === 200) {
      message(`已${value ? '启用' : '禁用'}事件调度器`, { type: "success" });
    } else {
      schedulerEnabled.value = !value; // 恢复原状态
      message(res.msg || `${value ? '启用' : '禁用'}事件调度器失败`, { type: "error" });
    }
  } catch (error) {
    console.error("切换调度器状态错误:", error);
    schedulerEnabled.value = !value; // 恢复原状态
    message(`${value ? '启用' : '禁用'}事件调度器失败，请稍后重试`, { type: "error" });
  } finally {
    toggleLoading.value = false;
  }
};

// 搜索事件
const handleSearch = () => {
  currentPage.value = 1;
};

// 数据库切换
const handleSchemaChange = () => {
  fetchEventList();
};

// 创建事件
const handleCreateEvent = () => {
  // 专业操作确认
  ElMessageBox.confirm(
    '创建事件调度器是一项需要专业知识的操作。确认您了解所有风险并继续吗？',
    '专业操作确认',
    {
      confirmButtonText: '我了解风险，继续操作',
      cancelButtonText: '取消',
      type: 'warning',
      customClass: 'professional-confirm-dialog'
    }
  ).then(() => {
    isEdit.value = false;

    // 重置表单
    eventForm.event_name = "";
    eventForm.schedule = "EVERY 1 DAY";
    eventForm.sql_statement = "";
    eventForm.enable = true;
    eventForm.comment = "";
    eventForm.start_time = "";
    eventForm.end_time = "";
    eventForm.schema = selectedSchema.value;

    // 重置调度表达式相关变量
    scheduleType.value = 'interval';
    intervalValue.value = 1;
    intervalUnit.value = 'DAY';
    atTimestamp.value = '';

    showEventDialog.value = true;
  }).catch(() => {
    // 用户取消操作
  });
};

// 编辑事件
const handleEditEvent = async (row: MysqlEventInfo) => {
  isEdit.value = true;

  // 获取事件详情
  try {
    // 使用Name和Db字段
    const eventName = row.Name || row.event_name || '';
    const schema = row.Db || row.event_schema || selectedSchema.value || undefined;

    const res = await getMysqlEventDetail(eventName, schema);
    if (res.code === 200) {
      const eventDetail = res.data;

      // 填充表单
      eventForm.event_name = eventDetail.Name || eventDetail.event_name || '';
      eventForm.schedule = generateScheduleFromEvent(eventDetail);
      parseScheduleExpression(eventForm.schedule); // 解析调度表达式
      eventForm.sql_statement = eventDetail.SQL || eventDetail.sql_statement || '';
      eventForm.enable = (eventDetail.Status === 'ENABLED') || (eventDetail.status === 'ENABLED');
      eventForm.comment = eventDetail.Comment || eventDetail.event_comment || '';
      eventForm.start_time = eventDetail.Starts || eventDetail.starts || '';
      eventForm.end_time = eventDetail.Ends || eventDetail.ends || '';
      eventForm.schema = schema;

      showEventDialog.value = true;
    } else {
      message(res.msg || "获取事件详情失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取事件详情错误:", error);
    message("获取事件详情失败，请稍后重试", { type: "error" });
  }
};

// 根据事件信息生成调度表达式
const generateScheduleFromEvent = (event: MysqlEventInfo): string => {
  // 已有调度表达式，直接返回
  if (event.schedule) return event.schedule;

  // 间隔执行
  if (event['Interval value'] && event['Interval field']) {
    return `EVERY ${event['Interval value']} ${event['Interval field']}`;
  }

  // 指定时间执行
  if (event['Execute at']) {
    return `AT TIMESTAMP '${event['Execute at']}'`;
  }

  // 兼容旧格式
  if (event.interval_value && event.interval_field) {
    return `EVERY ${event.interval_value} ${event.interval_field}`;
  }

  if (event.execute_at) {
    return `AT TIMESTAMP '${event.execute_at}'`;
  }

  // 默认返回每天执行
  return 'EVERY 1 DAY';
};

// 提交事件表单
const handleSubmitEvent = async () => {
  if (!eventFormRef.value) return;

  await eventFormRef.value.validate(async (valid: boolean) => {
    if (valid) {
      submitLoading.value = true;
      try {
        const params = { ...eventForm };

        const res = isEdit.value
          ? await alterMysqlEvent(params)
          : await createMysqlEvent(params);

        if (res.code === 200) {
          message(`${isEdit.value ? '修改' : '创建'}事件成功`, { type: "success" });
          showEventDialog.value = false;
          fetchEventList();
        } else {
          message(res.msg || `${isEdit.value ? '修改' : '创建'}事件失败`, { type: "error" });
        }
      } catch (error) {
        console.error(`${isEdit.value ? '修改' : '创建'}事件错误:`, error);
        message(`${isEdit.value ? '修改' : '创建'}事件失败，请稍后重试`, { type: "error" });
      } finally {
        submitLoading.value = false;
      }
    }
  });
};

// 删除事件
const handleDeleteEvent = (row: MysqlEventInfo) => {
  const eventName = row.Name || row.event_name || '';
  const schema = row.Db || row.event_schema || selectedSchema.value || undefined;

  ElMessageBox.confirm(
    `<strong>危险操作警告</strong><br>
     <p>您正在尝试删除事件 "${eventName}"。</p>
     <p>此操作将永久删除该事件调度器，且不可恢复！</p>
     <p>如果您不确定此操作的影响，请立即取消。</p>`,
    "危险操作确认",
    {
      confirmButtonText: "我了解风险，确认删除",
      cancelButtonText: "取消",
      type: "error",
      dangerouslyUseHTMLString: true,
      customClass: 'delete-confirm-dialog'
    }
  ).then(async () => {
    try {
      const res = await dropMysqlEvent(eventName, schema);
      if (res.code === 200) {
        message("删除事件成功", { type: "success" });
        fetchEventList();
      } else {
        message(res.msg || "删除事件失败", { type: "error" });
      }
    } catch (error) {
      console.error("删除事件错误:", error);
      message("删除事件失败，请稍后重试", { type: "error" });
    }
  }).catch(() => { });
};

// 查看事件日志
const handleViewLogs = async (row: MysqlEventInfo) => {
  try {
    const eventName = row.Name || row.event_name || '';
    currentEventName.value = eventName;

    // 重置所有日志数据和分页配置
    allLoadedLogs.value = [];
    currentEventLogs.value = [];
    logsPageConfig.value.currentPage = 1;
    logsPageConfig.value.serverPage = 1;

    showLogsDialog.value = true;

    // 显示加载状态
    detailLoading.value = true;

    // 加载第一页日志数据
    await fetchEventLogs();
  } catch (error) {
    console.error("获取事件日志错误:", error);
    message("获取事件日志失败，请稍后重试", { type: "error" });
  } finally {
    detailLoading.value = false;
  }
};

// 获取事件日志数据
const fetchEventLogs = async (append = false) => {
  if (!currentEventName.value) return;

  detailLoading.value = true;
  try {
    const res = await getMysqlEventLogList({
      event_name: currentEventName.value,
      page: logsPageConfig.value.serverPage,
      limit: logsPageConfig.value.serverPageSize
    });

    if (res.code === 200) {
      // 处理返回数据结构兼容性
      let responseData;

      // 检查返回的数据结构是否包含list和total字段
      if (res.data && 'list' in res.data && 'total' in res.data) {
        // 新的API返回结构: { list: [...], total: number }
        responseData = {
          list: res.data.list || [],
          total: res.data.total || 0
        };
      } else {
        // 旧的API返回结构，直接使用res.data作为列表
        responseData = {
          list: res.data || [],
          total: Array.isArray(res.data) ? res.data.length : 0
        };
      }

      logsPageConfig.value.serverTotal = responseData.total;
      logsPageConfig.value.total = responseData.total;

      console.log("日志数据结构:", responseData);

      // 添加或替换日志数据
      if (append) {
        allLoadedLogs.value = [...allLoadedLogs.value, ...(responseData.list || [])];
      } else {
        allLoadedLogs.value = responseData.list || [];
      }

      // 更新当前页显示的数据
      updateCurrentPageLogs();

      if (allLoadedLogs.value.length === 0) {
        message("暂无执行日志", { type: "info" });
      }
    } else {
      message(res.msg || "获取事件日志失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取事件日志错误:", error);
    message("获取事件日志失败，请稍后重试", { type: "error" });
  } finally {
    detailLoading.value = false;
  }
};

// 更新当前页显示的日志
const updateCurrentPageLogs = () => {
  const start = (logsPageConfig.value.currentPage - 1) * logsPageConfig.value.pageSize;
  const end = start + logsPageConfig.value.pageSize;
  currentEventLogs.value = allLoadedLogs.value.slice(start, end);
};

// 检查是否需要加载更多服务器数据
const checkAndLoadMoreLogData = async () => {
  const currentDisplayedCount = allLoadedLogs.value.length;
  const totalNeededForCurrentView = logsPageConfig.value.currentPage * logsPageConfig.value.pageSize;

  // 如果当前页需要显示的数据超过了已加载的数据量，且服务器还有更多数据
  if (totalNeededForCurrentView > currentDisplayedCount && currentDisplayedCount < logsPageConfig.value.serverTotal) {
    // 增加服务器页码
    logsPageConfig.value.serverPage += 1;
    await fetchEventLogs(true); // 追加模式加载数据
  } else {
    // 只更新当前页面显示
    updateCurrentPageLogs();
  }
};

// 刷新日志列表
const handleRefreshLogs = async () => {
  if (!currentEventName.value) return;

  // 重置分页配置
  logsPageConfig.value.currentPage = 1;
  logsPageConfig.value.serverPage = 1;

  // 重新获取第一页数据
  await fetchEventLogs();
  message("日志已刷新", { type: "success" });
};

// 日志分页大小变化
const handleLogsSizeChange = (val: number) => {
  logsPageConfig.value.pageSize = val;
  checkAndLoadMoreLogData();
};

// 日志当前页变化
const handleLogsCurrentChange = (val: number) => {
  logsPageConfig.value.currentPage = val;
  checkAndLoadMoreLogData();
};

// 获取成功日志数量
const getSuccessCount = () => {
  return currentEventLogs.value.filter(log => log.status === 1).length;
};

// 获取失败日志数量
const getErrorCount = () => {
  return currentEventLogs.value.filter(log => log.status === 0).length;
};

// 计算平均执行时间
const getAvgExecutionTime = () => {
  if (currentEventLogs.value.length === 0) return 0;
  const total = currentEventLogs.value.reduce((sum, log) => sum + log.execution_time, 0);
  return Math.round(total / currentEventLogs.value.length);
};

// 行点击查看详情
const handleRowClick = (row: MysqlEventInfo) => {
  const eventName = row.Name || row.event_name || '';

  if (hasEventPrivilege) {
    handleEditEvent(row);
  } else {
    // 如果没有权限，只能查看详情不能编辑
    handleViewLogs(row);
  }
};

// 分页大小变化
const handleSizeChange = (val: number) => {
  pageSize.value = val;
  currentPage.value = 1;
};

// 当前页变化
const handleCurrentChange = (val: number) => {
  currentPage.value = val;
};

// 获取系统状态
const fetchSystemStatus = async () => {
  systemStatusLoading.value = true;
  try {
    const res = await getMysqlEventSystemStatus();
    if (res.code === 200) {
      systemStatus.value = res.data;
      // 系统状态中也包含了权限信息，可以直接使用
      hasEventPrivilege.value = res.data.has_privilege;

      // 如果调度器状态与我们当前显示的不一致，则更新
      if (schedulerEnabled.value !== res.data.is_enabled) {
        schedulerEnabled.value = res.data.is_enabled;
      }
    } else {
      message(res.msg || "获取系统状态失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取系统状态错误:", error);
    message("获取系统状态失败，请稍后重试", { type: "error" });
  } finally {
    systemStatusLoading.value = false;
  }
};

// 检查用户权限
const checkUserPrivileges = async () => {
  try {
    console.log("开始检查用户权限...");
    const res = await checkMysqlEventPrivileges();
    console.log("权限检查响应:", res);
    if (res.code === 200) {
      userPrivileges.value = res.data;
      hasEventPrivilege.value = res.data.has_privilege;

      // 如果没有事件权限，显示提示
      if (!res.data.has_privilege) {
        ElMessage.warning("您没有管理MySQL事件的权限，部分功能可能不可用");
      }
    } else {
      message(res.msg || "检查权限失败", { type: "error" });
    }
  } catch (error) {
    console.error("检查权限错误:", error);
    message("检查权限失败，请稍后重试", { type: "error" });
  }
};

// 打开系统状态对话框
const handleOpenSystemStatus = () => {
  fetchSystemStatus();
  showSystemStatusDialog.value = true;
};

// 处理调度类型切换
const handleScheduleTypeChange = () => {
  updateScheduleExpression();
};

// 更新调度表达式
const updateScheduleExpression = () => {
  if (scheduleType.value === 'interval') {
    eventForm.schedule = `EVERY ${intervalValue.value} ${intervalUnit.value}`;
  } else if (scheduleType.value === 'at' && atTimestamp.value) {
    eventForm.schedule = `AT TIMESTAMP '${atTimestamp.value}'`;
  }
};

// 监听调度表达式相关变量变化
watch([intervalValue, intervalUnit, atTimestamp], () => {
  updateScheduleExpression();
});

// 解析已有的调度表达式
const parseScheduleExpression = (expression: string) => {
  if (!expression) return;

  const intervalMatch = expression.match(/EVERY\s+(\d+)\s+(\w+)/i);
  const atMatch = expression.match(/AT\s+TIMESTAMP\s+'([^']+)'/i);

  if (intervalMatch) {
    scheduleType.value = 'interval';
    intervalValue.value = parseInt(intervalMatch[1]);
    intervalUnit.value = intervalMatch[2].toUpperCase();
  } else if (atMatch) {
    scheduleType.value = 'at';
    atTimestamp.value = atMatch[1];
  }
};

// 处理事件状态切换
const handleToggleEventStatus = async (row: MysqlEventInfo, enabled: boolean) => {
  // 设置当前行的加载状态
  row.statusLoading = true;

  try {
    // 获取事件名称和数据库
    const eventName = row.Name || row.event_name || '';
    const schema = row.Db || row.event_schema || selectedSchema.value || undefined;

    // 使用新的toggleMysqlEvent API切换事件状态
    const res = await toggleMysqlEvent(eventName, enabled, schema);

    if (res.code === 200) {
      // 更新本地状态
      row.Status = enabled ? 'ENABLED' : 'DISABLED';
      row.statusEnabled = enabled;

      message(`已${enabled ? '启用' : '禁用'}事件 "${eventName}"`, { type: "success" });
    } else {
      // 恢复原状态
      row.statusEnabled = !enabled;
      message(res.msg || `${enabled ? '启用' : '禁用'}事件失败`, { type: "error" });
    }
  } catch (error) {
    console.error("切换事件状态错误:", error);
    // 恢复原状态
    row.statusEnabled = !enabled;
    message(`${enabled ? '启用' : '禁用'}事件失败，请稍后重试`, { type: "error" });
  } finally {
    row.statusLoading = false;
  }
};

// 翻译时间间隔字段
const translateIntervalField = (field: string) => {
  const fieldMap: Record<string, string> = {
    'SECOND': '秒',
    'MINUTE': '分钟',
    'HOUR': '小时',
    'DAY': '天',
    'WEEK': '周',
    'MONTH': '月',
    'QUARTER': '季度',
    'YEAR': '年'
  };
  return fieldMap[field] || field;
};

// 格式化日期时间
const formatDateTime = (dateTime: string | null) => {
  if (!dateTime) return '-';
  return dateTime;
};

// 查看日志详情
const handleViewLogDetail = (log: MysqlEventLogInfo) => {
  currentLogDetail.value = log;
  showLogDetailDialog.value = true;
};

// SQL编辑器相关变量
const showSqlPreview = ref(false);
const highlightedSql = ref('');

// SQL关键字列表
const SQL_KEYWORDS = [
  'SELECT', 'FROM', 'WHERE', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'ALTER',
  'TABLE', 'INTO', 'VALUES', 'SET', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'FULL',
  'ON', 'GROUP', 'BY', 'HAVING', 'ORDER', 'LIMIT', 'OFFSET', 'AS', 'AND', 'OR', 'NOT',
  'IN', 'BETWEEN', 'LIKE', 'IS', 'NULL', 'TRUE', 'FALSE', 'ASC', 'DESC', 'DISTINCT',
  'CASE', 'WHEN', 'THEN', 'ELSE', 'END', 'EXISTS', 'ALL', 'ANY', 'UNION', 'INTERSECT',
  'EXCEPT', 'WITH', 'TRUNCATE', 'PROCEDURE', 'FUNCTION', 'TRIGGER', 'VIEW', 'INDEX',
  'CONSTRAINT', 'PRIMARY', 'FOREIGN', 'KEY', 'REFERENCES', 'DEFAULT', 'AUTO_INCREMENT'
];

// SQL函数列表
const SQL_FUNCTIONS = [
  'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'CONCAT', 'SUBSTRING', 'TRIM', 'LENGTH', 'UPPER',
  'LOWER', 'DATE', 'NOW', 'CURRENT_TIMESTAMP', 'COALESCE', 'IFNULL', 'CAST', 'CONVERT'
];

// SQL语法高亮函数
const highlightSql = () => {
  if (!eventForm.sql_statement) {
    highlightedSql.value = '';
    return;
  }

  let html = eventForm.sql_statement
    // 转义HTML特殊字符
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    // 处理字符串
    .replace(/'([^']*)'/g, '<span class="sql-string">\'$1\'</span>')
    .replace(/"([^"]*)"/g, '<span class="sql-string">"$1"</span>')
    // 处理数字
    .replace(/\b(\d+)\b/g, '<span class="sql-number">$1</span>')
    // 处理注释
    .replace(/--(.*)$/gm, '<span class="sql-comment">--$1</span>');

  // 处理关键字
  SQL_KEYWORDS.forEach(keyword => {
    const regex = new RegExp(`\\b${keyword}\\b`, 'gi');
    html = html.replace(regex, match => `<span class="sql-keyword">${match}</span>`);
  });

  // 处理函数
  SQL_FUNCTIONS.forEach(func => {
    const regex = new RegExp(`\\b${func}\\b`, 'gi');
    html = html.replace(regex, match => `<span class="sql-function">${match}</span>`);
  });

  // 添加换行
  html = html.replace(/\n/g, '<br>');

  highlightedSql.value = html;
};

// 将SQL格式化为Markdown格式
const formatSqlToMarkdown = () => {
  if (!eventForm.sql_statement) return;

  try {
    // 格式化SQL为Markdown风格
    let formattedSql = eventForm.sql_statement
      // 标准化空白字符
      .replace(/\s+/g, ' ')
      // 处理括号
      .replace(/\s*\(\s*/g, ' (')
      .replace(/\s*\)\s*/g, ') ')
      // 处理逗号
      .replace(/\s*,\s*/g, ', ')
      // 处理分号
      .replace(/\s*;\s*/g, ';\n\n')
      // 主要关键字换行并大写
      .replace(/\b(select|from|where|group by|having|order by|limit|insert into|values|update|set|delete from)\b/gi,
        match => `\n${match.toUpperCase()}`)
      // 子句缩进
      .replace(/\n(FROM|WHERE|GROUP BY|HAVING|ORDER BY|LIMIT)/gi, '\n  $1')
      // JOIN子句缩进和换行
      .replace(/\b(inner|left|right|full|cross)?\s*join\b/gi, match => `\n  ${match.toUpperCase()}`)
      // 处理AND/OR - 大写并缩进
      .replace(/\b(and|or)\b/gi, match => `\n    ${match.toUpperCase()}`)
      // 处理CASE语句 - 大写并缩进
      .replace(/\b(case|when|then|else|end)\b/gi, match => `\n      ${match.toUpperCase()}`)
      // 处理INSERT语句
      .replace(/\b(INSERT INTO.*?)\s*\(/i, '$1 (')
      .replace(/\bVALUES\s*\(/gi, 'VALUES (')
      // 处理多个值的情况
      .replace(/\),\s*\(/g, '),\n  (')
      // 处理UPDATE语句
      .replace(/\bUPDATE\s+(.*?)\s+SET\b/gi, 'UPDATE $1\n  SET')
      // 其他SQL关键字大写
      .replace(/\b(in|between|like|is|not|null|true|false|asc|desc|distinct|all|any|exists|using)\b/gi,
        match => match.toUpperCase())
      // 函数名大写
      .replace(/\b(count|sum|avg|min|max|concat|substring|trim|length|upper|lower|date|now|current_timestamp|coalesce|ifnull|cast|convert)\b/gi,
        match => match.toUpperCase())
      // 清理多余的空行
      .replace(/\n\s*\n/g, '\n')
      .trim();

    // 应用格式化后的SQL
    eventForm.sql_statement = formattedSql;

    // 更新语法高亮并显示预览
    highlightSql();
    showSqlPreview.value = true;

    message("SQL格式化成功", { type: "success" });
  } catch (error) {
    console.error("SQL格式化错误:", error);
    message("SQL格式化失败", { type: "error" });
  }
};

// 更新SQL预览
const updateSqlPreview = () => {
  highlightSql();
};

// 切换SQL预览
const toggleSqlPreview = () => {
  showSqlPreview.value = !showSqlPreview.value;
};

onMounted(() => {
  console.log("组件挂载，开始初始化...");
  // 先检查权限
  checkUserPrivileges().then(() => {
    console.log("权限检查完成，继续获取其他数据...");
    // 获取系统状态
    fetchSystemStatus();
    // 获取事件列表
    fetchEventList();
    // 获取调度器状态
    fetchSchedulerStatus();
  }).catch(error => {
    console.error("初始化过程中出错:", error);
    message("初始化失败，请检查网络或API配置", { type: "error" });
  });
});
</script>

<style lang="scss" scoped>
.mysql-event-container {
  padding: 16px;
  color: #333;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

  .main-content {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  // 头部区域
  .header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;

    .page-title {
      font-size: 16px;
      font-weight: 600;
      color: #262626;
      margin: 0;
    }

    .action-group {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .scheduler-status {
      display: flex;
      align-items: center;

      .status-label {
        margin-right: 8px;
        font-size: 13px;
        color: #606266;
      }
    }
  }

  // 警告提示
  .warning-panel {
    border-bottom: 1px solid #f0f0f0;
    background-color: #fffbe6;
    padding: 8px 16px;

    .warning-content {
      display: flex;
      align-items: center;

      .warning-icon {
        color: #e6a23c;
        font-size: 16px;
        margin-right: 8px;
        flex-shrink: 0;
      }

      .warning-text {
        color: #7d5b1e;
        font-size: 12px;
        line-height: 1.5;
      }
    }
  }

  // 工具栏区域
  .tools-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    border-bottom: 1px solid #f0f0f0;
    background-color: #fafafa;

    .left-tools {
      display: flex;
      gap: 10px;
      align-items: center;

      .search-input {
        width: 250px;
      }

      .schema-select {
        width: 160px;
      }
    }

    .right-tools {
      display: flex;
      gap: 8px;
      align-items: center;
    }
  }

  // 事件表格区域
  .events-table {
    display: flex;
    flex-direction: column;
    padding: 0;

    .alert-message {
      margin: 12px 16px;
    }

    .table-container {
      padding: 0 16px;
      margin-top: 12px;
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
        height: 36px;
      }

      .el-table__row td {
        padding: 4px 6px;
        height: 36px;
      }

      &::before {
        display: none;
      }
    }

    .table-actions {
      display: flex;
      justify-content: center;
      flex-wrap: nowrap;
      gap: 4px;
    }

    .pagination-container {
      display: flex;
      justify-content: flex-end;
      padding: 12px 16px;
      border-top: 1px solid #f0f0f0;
    }
  }

  // 表单样式
  .event-form {
    .form-help {
      font-size: 12px;
      color: #8c8c8c;
      margin-top: 4px;
      line-height: 1.3;
    }

    .schedule-selector {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
      margin-bottom: 8px;

      .schedule-type {
        width: 120px;
      }

      .interval-selector {
        display: flex;
        align-items: center;
        gap: 8px;

        .interval-text {
          font-size: 13px;
          color: #606266;
        }

        .interval-value {
          width: 100px;
        }

        .interval-unit {
          width: 120px;
        }
      }

      .at-timestamp {
        width: 100%;
        max-width: 220px;
      }
    }
  }

  .status-switch {
    .el-switch {
      --el-switch-on-color: #13ce66;
      --el-switch-off-color: #ff4949;

      .el-switch__core {
        .el-switch__inner {
          .is-text {
            color: white;
            font-weight: bold;
            font-size: 11px;
          }
        }
      }

      &.is-disabled {
        opacity: 0.7;
      }
    }
  }

  // 弹窗样式
  :deep(.el-dialog) {
    .el-dialog__header {
      padding: 12px 16px;
      margin: 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .el-dialog__body {
      padding: 16px;
    }

    .el-dialog__footer {
      padding: 12px 16px;
      border-top: 1px solid #f0f0f0;
    }
  }

  // 移动端适配
  @media (max-width: 768px) {
    padding: 0;

    .header-section {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;

      .action-group {
        width: 100%;
        justify-content: space-between;
      }
    }

    .tools-section {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;

      .left-tools {
        width: 100%;
        flex-wrap: wrap;

        .search-input,
        .schema-select {
          width: 100%;
        }
      }

      .right-tools {
        width: 100%;
        justify-content: flex-end;
      }
    }
  }
}

// 确认对话框样式
:deep(.professional-confirm-dialog) {
  .el-message-box__header {
    background-color: #faad14;
    padding: 10px 15px;

    .el-message-box__title {
      color: #fff;
      font-weight: bold;
    }
  }

  .el-message-box__content {
    padding: 16px;
    font-size: 14px;
    line-height: 1.5;
  }

  .el-message-box__btns {
    padding: 10px 15px;
  }
}

:deep(.delete-confirm-dialog) {
  .el-message-box__header {
    background-color: #f56c6c;
    padding: 10px 15px;

    .el-message-box__title {
      color: #fff;
      font-weight: bold;
    }
  }

  .el-message-box__content {
    padding: 16px;
    font-size: 14px;
    line-height: 1.5;

    strong {
      font-size: 16px;
      color: #f56c6c;
      display: block;
      margin-bottom: 10px;
    }

    p {
      margin: 5px 0;
    }
  }
}

/* 添加新的分页信息样式 */
.logs-wrapper {
  /* 已有样式保持不变 */

  .pagination-info {
    padding: 6px 8px;

    .info-text {
      font-size: 12px;
      color: #8c8c8c;

      strong {
        color: #262626;
        font-weight: 600;
      }
    }
  }

  .pagination-container {
    display: flex;
    justify-content: flex-end;
    padding: 8px;
    border-top: 1px solid #f0f0f0;
    margin-top: 8px;

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

.sql-form-item {
  margin-bottom: 20px;

  .simple-sql-editor {
    width: 100%;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    overflow: hidden;

    .el-textarea {
      width: 100%;

      :deep(.el-textarea__inner) {
        border: none;
        border-radius: 0;
        border-bottom: 1px solid #ebeef5;
        padding: 12px;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.5;
        resize: none;
      }
    }

    .sql-preview {
      border-bottom: 1px solid #ebeef5;

      .preview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background-color: #f5f7fa;
        border-bottom: 1px solid #ebeef5;
        font-size: 13px;
      }

      .preview-content {
        padding: 12px;
        max-height: 300px;
        overflow: auto;
        background-color: #282c34;
        color: #abb2bf;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.5;

        .sql-keyword {
          color: #c678dd;
          font-weight: bold;
        }

        .sql-function {
          color: #61afef;
        }

        .sql-string {
          color: #98c379;
        }

        .sql-number {
          color: #d19a66;
        }

        .sql-comment {
          color: #5c6370;
          font-style: italic;
        }
      }
    }

    .editor-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      padding: 8px 12px;
      background-color: #f5f7fa;
    }
  }
}
</style>