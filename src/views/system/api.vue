<template>
  <div class="api-container">
    <div class="api-header">
      <div class="title">API接口管理</div>
      <el-button type="primary" size="small" @click="handleReset(false)">
        <el-icon>
          <RefreshRight />
        </el-icon>同步
      </el-button>
    </div>

    <div class="filter-bar">
      <div class="left">
        <el-input v-model="searchParams.keyword" placeholder="搜索路径/控制器/方法" clearable size="small" class="search-input"
          @keyup.enter="handleSearch" @clear="handleSearch">
          <template #prefix><el-icon>
              <Search />
            </el-icon></template>
        </el-input>
        <el-select v-model="searchParams.status" placeholder="状态" clearable size="small" @change="handleSearch">
          <el-option label="全部" value="" />
          <el-option label="开放" :value="1" />
          <el-option label="维护" :value="0" />
          <el-option label="关闭" :value="3" />
        </el-select>
        <el-button type="primary" size="small" @click="handleSearch">
          <el-icon>
            <Search />
          </el-icon>
        </el-button>
        <el-button size="small" @click="fetchApiList">
          <el-icon>
            <Refresh />
          </el-icon>
        </el-button>
      </div>
      <div class="right">
        <el-button type="success" size="small" @click="handleBatchEnable" :disabled="!selectedApis.length">
          <el-icon>
            <Check />
          </el-icon>启用
        </el-button>
        <el-button type="info" size="small" @click="handleBatchDisable" :disabled="!selectedApis.length">
          <el-icon>
            <Close />
          </el-icon>禁用
        </el-button>
      </div>
    </div>

    <div class="table-wrap">
      <el-table :data="apiList" @selection-change="handleSelectionChange" v-loading="tableLoading" border size="small"
        :row-class-name="tableRowClassName" style="width: 100%">
        <el-table-column type="selection" align="center" />
        <el-table-column label="ID" prop="id" align="center" />
        <el-table-column label="方法" align="center">
          <template #default="{ row }">
            <el-tag :class="getMethodClass(row.method)" size="small" effect="plain">{{ row.method.toLowerCase()
            }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="应用" align="center" prop="app">
          <template #default="{ row }">
            <span>{{ getAppName(row.full_path) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="版本" align="center" prop="version" />
        <el-table-column label="路径" prop="full_path" min-width="120">
          <template #default="{ row }">
            <div class="controller-cell">
              <el-icon>
                <Link />
              </el-icon>
              <span>{{ row.full_path }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="控制器" prop="model" min-width="100">
          <template #default="{ row }">
            <div class="controller-cell">
              <el-icon>
                <Grid />
              </el-icon>
              <span>{{ row.model }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="方法" min-width="80">
          <template #default="{ row }">
            <div class="controller-cell">
              <el-icon>
                <Operation />
              </el-icon>
              <span>{{ getActionFromPath(row.path) }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" align="center">
          <template #default="{ row }">
            <div class="status-indicator">
              <span class="status-dot" :class="getStatusClass(row.status)"></span>
              <span class="status-text" :class="getStatusClass(row.status)">{{ getStatusText(row.status) }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="操作" align="center">
          <template #default="{ row }">
            <div class="actions">
              <el-button type="primary" link size="small" @click="handleEdit(row)">
                <el-icon>
                  <Edit />
                </el-icon>
              </el-button>
              <el-dropdown @command="(cmd) => setApiStatus(row, cmd)" trigger="click">
                <el-button type="info" link size="small">
                  <el-icon>
                    <ArrowDown />
                  </el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item :command="1" :disabled="row.status === 1">开放</el-dropdown-item>
                    <el-dropdown-item :command="0" :disabled="row.status === 0">维护</el-dropdown-item>
                    <el-dropdown-item :command="3" :disabled="row.status === 3">关闭</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <div class="pagination">
      <el-pagination v-model:current-page="searchParams.page" v-model:page-size="searchParams.page_size"
        :page-sizes="[5, 10, 20, 50, 100]" background layout="total, sizes, prev, pager, next" :total="totalCount"
        @size-change="handleSizeChange" @current-change="handleCurrentChange" small />
    </div>

    <el-dialog v-model="editDialogVisible" title="编辑接口信息" width="450px" align-center destroy-on-close>
      <el-form :model="editForm" label-width="70px" size="small">
        <el-form-item label="请求方式">
          <el-tag :class="getMethodClass(editForm.method)" size="small">
            {{ editForm.method.toLowerCase() }}
          </el-tag>
        </el-form-item>
        <el-form-item label="应用">
          <span>{{ getAppName(editForm.full_path) }}</span>
        </el-form-item>
        <el-form-item label="版本">
          <span>{{ editForm.version }}</span>
        </el-form-item>
        <el-form-item label="接口路径">
          <div class="api-path-display">{{ editForm.full_path }}</div>
        </el-form-item>
        <el-form-item label="控制器">
          <div class="info-display">{{ editForm.model }}</div>
        </el-form-item>
        <el-form-item label="方法">
          <div class="info-display">{{ getActionFromPath(editForm.path) }}</div>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="editForm.description" type="textarea" :rows="3" placeholder="请输入接口描述" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="editForm.status">
            <el-radio :label="1">
              <div class="radio-option">
                <span class="status-dot success"></span>
                <span>开放</span>
              </div>
            </el-radio>
            <el-radio :label="0">
              <div class="radio-option">
                <span class="status-dot warning"></span>
                <span>维护</span>
              </div>
            </el-radio>
            <el-radio :label="3">
              <div class="radio-option">
                <span class="status-dot info"></span>
                <span>关闭</span>
              </div>
            </el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button size="small" @click="editDialogVisible = false">取消</el-button>
          <el-button type="primary" size="small" @click="confirmEdit" :loading="editLoading">确定</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "SystemApi"
});

import { ref, reactive, onMounted } from 'vue';
import { ElMessageBox } from 'element-plus';
import {
  Refresh, RefreshRight, Search, Check, Close, Edit,
  Grid, Operation, Link, InfoFilled, Connection, ArrowDown
} from '@element-plus/icons-vue';
import {
  getApiList, getApiDetail, updateApi, resetApiData, updateApiStatus, batchUpdateApiStatus
} from '@/api/api';
import { message } from '@/utils/message';

// 定义扩展的ApiInfo类型
interface ExtendedApiInfo {
  id: number;
  path: string;
  full_path: string;
  method: string;
  model: string;
  version: string;
  create_time: string;
  update_time: string;
  description: string;
  status: number;
}

// 状态变量
const tableLoading = ref(false);
const editDialogVisible = ref(false);
const editLoading = ref(false);
const selectedApis = ref<number[]>([]);
const apiList = ref<ExtendedApiInfo[]>([]);
const totalCount = ref(0);
const editForm = reactive<ExtendedApiInfo>({
  id: 0,
  path: '',
  full_path: '',
  method: '',
  model: '',
  version: '',
  create_time: '',
  update_time: '',
  description: '',
  status: 1
});

// 消息提示函数
const success = (msg: string) => message(msg, { type: 'success' });
const error = (msg: string) => message(msg, { type: 'error' });
const warning = (msg: string) => message(msg, { type: 'warning' });

// 搜索参数
const searchParams = reactive({
  page: 1,
  page_size: 5,
  keyword: '',
  status: undefined as number | string | undefined,
});

// 获取方法对应的样式类
const getMethodClass = (method: string) => {
  if (!method) return '';

  const methodLower = method.toLowerCase();
  switch (methodLower) {
    case 'get': return 'get';
    case 'post': return 'post';
    case 'put': return 'put';
    case 'delete': return 'delete';
    case 'patch': return 'patch';
    case 'options': return 'options';
    case 'head': return 'head';
    case 'connect': return 'connect';
    case 'trace': return 'trace';
    case 'any': return 'any';
    default: return 'other';
  }
};

// 获取状态文本
const getStatusText = (status: number) => {
  switch (status) {
    case 0: return '维护';
    case 1: return '开放';
    case 3: return '关闭';
    default: return '未知';
  }
};

// 获取状态样式类
const getStatusClass = (status: number) => {
  switch (status) {
    case 0: return 'warning';
    case 1: return 'success';
    case 3: return 'info';
    default: return '';
  }
};

// 从路径中提取应用名称
const getAppName = (fullPath: string) => {
  if (!fullPath) return '';

  // 匹配 /api/xxx/ 格式
  const match = fullPath.match(/\/([^\/]+)\/[^\/]+\//);
  if (match && match[1]) {
    return match[1];
  }

  return '';
};

// 从路径中提取方法名
const getActionFromPath = (path: string) => {
  if (!path) return '';

  // 移除开头的斜杠
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;

  // 如果路径中包含斜杠，取最后一部分
  if (cleanPath.includes('/')) {
    const parts = cleanPath.split('/');
    return parts[parts.length - 1];
  }

  // 如果路径中包含冒号（参数），取冒号前的部分
  if (cleanPath.includes(':')) {
    return cleanPath.split(':')[0];
  }

  return cleanPath;
};

// 表格行样式
const tableRowClassName = ({ row }: { row: ExtendedApiInfo }) => {
  if (row.status === 3) return 'closed-row';
  if (row.status === 0) return 'maintenance-row';
  return '';
};

// 获取API接口列表
const fetchApiList = async () => {
  tableLoading.value = true;
  try {
    // 创建一个新的参数对象，确保类型正确
    const params: any = {
      page: searchParams.page,
      page_size: searchParams.page_size,
      keyword: searchParams.keyword
    };

    // 只有当status不是undefined时才添加到请求参数
    if (searchParams.status !== undefined) {
      getApis
      params.status = Number(searchParams.status);
    }

    const res: any = await getApiList(params);
    if (res?.code === 200 && res?.data) {
      apiList.value = res.data.list || [];
      totalCount.value = res.data.total || 0;
    } else {
      error(res?.msg || '获取接口列表失败');
    }
  } catch (err) {
    console.error('获取接口列表失败:', err);
    error('获取接口列表失败，请稍后重试');
  } finally {
    tableLoading.value = false;
  }
};

// 搜索
const handleSearch = () => {
  searchParams.page = 1;
  // 处理状态参数，确保空字符串转为undefined
  if (searchParams.status === '') {
    searchParams.status = undefined;
  }
  fetchApiList();
};

// 分页大小变化
const handleSizeChange = (val: number) => {
  searchParams.page_size = val;
  fetchApiList();
};

// 页码变化
const handleCurrentChange = (val: number) => {
  searchParams.page = val;
  fetchApiList();
};

// 选择变化
const handleSelectionChange = (selection: ExtendedApiInfo[]) => {
  selectedApis.value = selection.map(item => item.id);
};

// 编辑接口
const handleEdit = (row: ExtendedApiInfo) => {
  editForm.id = row.id;
  editForm.path = row.path;
  editForm.full_path = row.full_path;
  editForm.method = row.method;
  editForm.model = row.model;
  editForm.version = row.version;
  editForm.description = row.description || '';
  editForm.status = row.status;
  editDialogVisible.value = true;
};

// 确认编辑
const confirmEdit = async () => {
  editLoading.value = true;
  try {
    const res: any = await updateApi({
      id: editForm.id,
      description: editForm.description,
      status: editForm.status
    });
    if (res?.code === 200) {
      success('更新成功');
      editDialogVisible.value = false;
      fetchApiList();
    } else {
      error(res?.msg || '更新失败');
    }
  } catch (err) {
    console.error('更新接口信息失败:', err);
    error('更新接口信息失败，请稍后重试');
  } finally {
    editLoading.value = false;
  }
};

// 设置接口状态
const setApiStatus = async (row: ExtendedApiInfo, status: number) => {
  try {
    const res: any = await updateApiStatus(row.id, status);
    if (res?.code === 200) {
      const statusText = getStatusText(status);
      success(`接口状态已更新为: ${statusText}`);
      row.status = status;
    } else {
      error(res?.msg || '状态修改失败');
    }
  } catch (err) {
    console.error('修改接口状态失败:', err);
    error('修改接口状态失败，请稍后重试');
  }
};

// 批量启用
const handleBatchEnable = () => {
  if (selectedApis.value.length === 0) {
    warning('请选择要启用的接口');
    return;
  }

  ElMessageBox.confirm(`确定要启用选中的 ${selectedApis.value.length} 个接口吗？`, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res: any = await batchUpdateApiStatus(selectedApis.value, 1);
      if (res?.code === 200) {
        success('批量启用成功');
        fetchApiList();
      } else {
        error(res?.msg || '批量启用失败');
      }
    } catch (err) {
      console.error('批量启用接口失败:', err);
      error('批量启用接口失败，请稍后重试');
    }
  }).catch(() => {
    // 用户取消操作
  });
};

// 批量禁用
const handleBatchDisable = () => {
  if (selectedApis.value.length === 0) {
    warning('请选择要禁用的接口');
    return;
  }

  ElMessageBox.confirm(`确定要禁用选中的 ${selectedApis.value.length} 个接口吗？`, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res: any = await batchUpdateApiStatus(selectedApis.value, 0);
      if (res?.code === 200) {
        success('批量禁用成功');
        fetchApiList();
      } else {
        error(res?.msg || '批量禁用失败');
      }
    } catch (err) {
      console.error('批量禁用接口失败:', err);
      error('批量禁用接口失败，请稍后重试');
    }
  }).catch(() => {
    // 用户取消操作
  });
};

// 同步接口
const handleReset = async (clearExisting: boolean) => {
  try {
    tableLoading.value = true;
    const res: any = await resetApiData(clearExisting);
    if (res?.code === 200) {
      success(`成功${clearExisting ? '重置' : '同步'}接口数据，共导入 ${res.data.imported_count} 个接口`);
      fetchApiList();
    } else {
      error(res?.msg || `${clearExisting ? '重置' : '同步'}接口数据失败`);
    }
  } catch (err) {
    console.error(`${clearExisting ? '重置' : '同步'}接口数据失败:`, err);
    error(`${clearExisting ? '重置' : '同步'}接口数据失败，请稍后重试`);
  } finally {
    tableLoading.value = false;
  }
};

// 组件挂载时执行
onMounted(() => {
  fetchApiList();
});
</script>

<style lang="scss" scoped>
.api-container {
  display: flex;
  flex-direction: column;
  background-color: #fff;
  font-size: 12px;

  .api-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 16px;
    border-bottom: 1px solid #e8e8e8;

    .title {
      font-size: 14px;
      font-weight: 500;
      color: #333;
    }
  }

  .filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 16px;
    background-color: #fafafa;

    .left {
      display: flex;
      gap: 8px;

      .search-input {}
    }

    .right {
      display: flex;
      gap: 8px;
    }
  }

  .table-wrap {
    flex: 1;
    overflow: auto;
    padding: 0 16px;

    .el-table {
      :deep(.el-table__header) th {
        background-color: #fafafa;
        color: #606266;
        padding: 8px 0;
        font-weight: 500;
      }

      :deep(.el-table__row) {
        td {
          padding: 6px 0;
        }
      }

      .closed-row td {
        color: #999;
        background-color: #fafafa;
      }

      .maintenance-row td {
        background-color: #fffef8;
      }
    }
  }

  .el-tag {
    font-size: 11px;
    height: 20px;
    line-height: 18px;
    padding: 0 4px;

    &.get {
      color: #389e0d;
      background-color: #f6ffed;
      border-color: #b7eb8f;
    }

    &.post {
      color: #1890ff;
      background-color: #e6f7ff;
      border-color: #91d5ff;
    }

    &.put {
      color: #fa8c16;
      background-color: #fff7e6;
      border-color: #ffd591;
    }

    &.delete {
      color: #f5222d;
      background-color: #fff1f0;
      border-color: #ffa39e;
    }

    &.any {
      color: #722ed1;
      background-color: #f9f0ff;
      border-color: #d3adf7;
    }

    &.patch,
    &.options,
    &.head,
    &.connect,
    &.trace,
    &.other {
      color: #722ed1;
      background-color: #f9f0ff;
      border-color: #d3adf7;
    }
  }

  .path-cell,
  .controller-cell {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 11px;
  }

  .controller-cell {
    display: flex;
    align-items: center;
    gap: 4px;

    .el-icon {
      color: #909399;
      font-size: 12px;
    }
  }

  .status-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
  }

  .status-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;

    &.success {
      background-color: #52c41a;
      box-shadow: 0 0 3px rgba(82, 196, 26, 0.5);
    }

    &.warning {
      background-color: #faad14;
      box-shadow: 0 0 3px rgba(250, 173, 20, 0.5);
    }

    &.info {
      background-color: #999;
      box-shadow: 0 0 3px rgba(153, 153, 153, 0.5);
    }
  }

  .status-text {
    display: inline-block;
    padding: 0 6px;
    height: 20px;
    line-height: 20px;
    border-radius: 2px;
    font-size: 11px;

    &.success {
      color: #52c41a;
      background: #f6ffed;
    }

    &.warning {
      color: #faad14;
      background: #fffbe6;
    }

    &.info {
      color: #999;
      background: #f5f5f5;
    }
  }

  .actions {
    display: flex;
    justify-content: center;
    gap: 2px;
  }

  .pagination {
    padding: 12px 16px;
    display: flex;
    justify-content: flex-end;
    border-top: 1px solid #e8e8e8;
  }

  .api-path-display,
  .info-display {
    padding: 6px 10px;
    background-color: #fafafa;
    border-radius: 2px;
    border: 1px solid #e8e8e8;
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 12px;
    color: #333;
    word-break: break-all;
  }

  .radio-option {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  :deep(.el-form-item) {
    margin-bottom: 14px;
  }

  :deep(.el-dialog__body) {
    padding: 15px 20px;
  }

  :deep(.el-dialog__header) {
    padding: 12px 20px;
    margin-right: 0;
    background-color: #fafafa;
    border-bottom: 1px solid #e8e8e8;

    .el-dialog__title {
      font-size: 14px;
      font-weight: 500;
    }
  }

  :deep(.el-dialog__footer) {
    padding: 10px 20px;
    border-top: 1px solid #e8e8e8;

  }

  @media (max-width: 768px) {
    .api-container {
      .filter-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;

        .left,
        .right {
          width: 100%;
        }

        .right {
          justify-content: flex-end;
        }

        .search-input {
          flex: 1;
          width: auto;
        }
      }
    }
  }
}
</style>
