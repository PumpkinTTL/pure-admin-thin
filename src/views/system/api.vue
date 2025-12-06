<template>
  <div class="app-container">
    <el-card shadow="never" class="main-card">
      <!-- 顶部操作区 -->
      <div class="header-section">
        <!-- 搜索区 -->
        <div class="search-area">
          <el-form :inline="true" :model="searchParams" class="search-form">
            <el-form-item label="关键词">
              <el-input
                v-model="searchParams.keyword"
                placeholder="搜索路径或控制器"
                clearable
                size="small"
                style="width: 200px"
                @keyup.enter="handleSearch"
              >
                <template #prefix>
                  <el-icon>
                    <Search />
                  </el-icon>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="模块">
              <el-input
                v-model="searchParams.module"
                placeholder="模块名称"
                clearable
                size="small"
                style="width: 140px"
                @keyup.enter="handleSearch"
              />
            </el-form-item>
            <el-form-item label="状态">
              <el-select
                v-model="searchParams.status"
                placeholder="全部"
                clearable
                size="small"
                style="width: 110px"
              >
                <el-option label="开放" :value="1" />
                <el-option label="维护" :value="0" />
                <el-option label="关闭" :value="3" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" size="small" @click="handleSearch">
                <el-icon>
                  <Search />
                </el-icon>
                查询
              </el-button>
              <el-button size="small" @click="resetSearch">
                <el-icon>
                  <RefreshLeft />
                </el-icon>
                重置
              </el-button>
            </el-form-item>
          </el-form>
        </div>

        <!-- 操作区 -->
        <div class="action-area">
          <el-button
            type="primary"
            size="small"
            plain
            @click="handleReset(false)"
          >
            <el-icon>
              <RefreshRight />
            </el-icon>
            同步接口
          </el-button>
          <el-button
            type="success"
            size="small"
            plain
            :disabled="!selectedApis.length"
          >
            <el-icon>
              <Check />
            </el-icon>
            批量启用
          </el-button>
          <el-button
            type="danger"
            size="small"
            plain
            :disabled="!selectedApis.length"
          >
            <el-icon>
              <Close />
            </el-icon>
            批量禁用
          </el-button>
          <el-divider
            direction="vertical"
            style="height: 24px; margin: 0 8px"
          />
          <el-button
            size="small"
            plain
            @click="fetchApiList"
            class="refresh-btn"
          >
            <el-icon>
              <Refresh />
            </el-icon>
            刷新
          </el-button>
        </div>
      </div>

      <el-divider style="margin: 16px 0" />

      <!-- 表格 -->
      <el-table
        :data="apiList"
        v-loading="tableLoading"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" align="center" fixed />
        <el-table-column prop="id" label="ID" align="center">
          <template #default="{ row }">
            <span class="id-text">#{{ row.id }}</span>
          </template>
        </el-table-column>
        <el-table-column label="版本" align="center">
          <template #default="{ row }">
            <el-tag size="small" effect="plain">
              {{ row.version || "v1" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="方法" align="center">
          <template #default="{ row }">
            <el-tag :type="getMethodType(row.method)" size="small">
              {{ row.method }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="接口路径" show-overflow-tooltip>
          <template #default="{ row }">
            <div class="path-cell">
              <el-icon class="path-icon">
                <Link />
              </el-icon>
              <span class="path-text">{{ row.full_path }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="模块" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.module" type="info" size="small" effect="plain">
              {{ row.module }}
            </el-tag>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="模型" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.model" size="small" effect="plain">
              {{ row.model }}
            </el-tag>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="权限模式" align="center">
          <template #default="{ row }">
            <el-tag
              v-if="row.check_mode === 'auto'"
              type="success"
              size="small"
              effect="plain"
            >
              自动
            </el-tag>
            <el-tag
              v-else-if="row.check_mode === 'manual'"
              type="warning"
              size="small"
              effect="plain"
            >
              手动
            </el-tag>
            <el-tag v-else type="info" size="small" effect="plain">公开</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="指定权限" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.required_permission" class="permission-text">
              {{ row.required_permission }}
            </span>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.status === 1" type="success" size="small">
              开放
            </el-tag>
            <el-tag v-else-if="row.status === 0" type="warning" size="small">
              维护
            </el-tag>
            <el-tag v-else type="info" size="small">关闭</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="更新时间" align="center">
          <template #default="{ row }">
            <span class="time-text">{{ row.update_time || "-" }}</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" align="center" fixed="right">
          <template #default="{ row }">
            <el-button
              type="primary"
              link
              size="small"
              @click="handleEdit(row)"
            >
              <el-icon>
                <Edit />
              </el-icon>
              编辑
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="searchParams.page"
          v-model:page-size="searchParams.page_size"
          :total="totalCount"
          :page-sizes="[5, 10, 20, 50, 100]"
          background
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>
  </div>

  <!-- 编辑对话框 -->
  <el-dialog
    v-model="editDialogVisible"
    title="编辑接口"
    width="500px"
    :close-on-click-modal="false"
  >
    <el-form :model="editForm" label-width="70px" size="small">
      <el-form-item label="路径">
        <div class="info-display">
          <el-icon class="info-icon">
            <Link />
          </el-icon>
          <span>{{ editForm.full_path }}</span>
        </div>
      </el-form-item>

      <el-form-item label="方法">
        <el-tag :type="getMethodType(editForm.method)" size="small">
          {{ editForm.method }}
        </el-tag>
      </el-form-item>

      <el-form-item label="模块">
        <el-input
          v-model="editForm.module"
          placeholder="如：user、role"
          clearable
        >
          <template #prefix>
            <el-icon>
              <Box />
            </el-icon>
          </template>
        </el-input>
      </el-form-item>

      <el-form-item label="权限模式">
        <el-select v-model="editForm.check_mode" style="width: 100%">
          <el-option label="自动检查（RESTful）" value="auto" />
          <el-option label="手动指定（推荐）" value="manual" />
          <el-option label="不检查（公开）" value="none" />
        </el-select>
      </el-form-item>

      <div v-if="editForm.check_mode === 'auto'" style="margin: 0 0 18px 70px">
        <el-alert type="info" :closable="false" style="font-size: 12px">
          自动映射为：
          <code class="permission-code">
            {{ editForm.module || "module" }}:{{
              getAutoPermissionAction(editForm.method)
            }}
          </code>
        </el-alert>
      </div>

      <el-form-item label="指定权限" v-if="editForm.check_mode === 'manual'">
        <el-select
          v-model="editForm.required_permission"
          placeholder="请选择权限"
          filterable
          clearable
          style="width: 100%"
          :loading="permissionLoading"
        >
          <template #prefix>
            <el-icon>
              <Key />
            </el-icon>
          </template>
          <el-option
            v-for="perm in permissionList"
            :key="perm.id"
            :label="`${perm.name} (${perm.iden})`"
            :value="perm.iden"
          >
            <div class="permission-option">
              <div class="permission-option-header">
                <span class="permission-name">{{ perm.name }}</span>
                <span class="permission-iden">{{ perm.iden }}</span>
              </div>
              <div v-if="perm.description" class="permission-desc">
                {{ perm.description }}
              </div>
            </div>
          </el-option>
        </el-select>
      </el-form-item>

      <el-form-item label="状态">
        <el-radio-group v-model="editForm.status" size="small">
          <el-radio :label="1">开放</el-radio>
          <el-radio :label="0">维护</el-radio>
          <el-radio :label="3">关闭</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item label="描述">
        <el-input
          v-model="editForm.description"
          type="textarea"
          :rows="3"
          placeholder="请输入接口描述"
          maxlength="200"
          show-word-limit
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="editDialogVisible = false" size="small">
        取消
      </el-button>
      <el-button
        type="primary"
        @click="confirmEdit"
        :loading="editLoading"
        size="small"
      >
        保存
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import {
  Search,
  RefreshLeft,
  RefreshRight,
  Check,
  Close,
  Refresh,
  Edit,
  Link,
  Box,
  Key
} from "@element-plus/icons-vue";
import { getApiList, updateApi, resetApiData } from "@/api/api";
import { getPermissionList, type PermissionInfo } from "@/api/permission";
import { message } from "@/utils/message";

const selectedApis = ref<number[]>([]);

const handleSelectionChange = (selection: any[]) => {
  selectedApis.value = selection.map(item => item.id);
};

interface ApiInfo {
  id: number;
  version?: string;
  method: string;
  module?: string;
  model?: string;
  path?: string;
  full_path: string;
  check_mode?: "auto" | "manual" | "none";
  required_permission?: string;
  description?: string;
  status: number;
  create_time?: string;
  update_time?: string;
}

const searchParams = reactive({
  keyword: "",
  module: "",
  status: undefined as number | undefined,
  page: 1,
  page_size: 5
});

const apiList = ref<ApiInfo[]>([]);
const totalCount = ref(0);
const tableLoading = ref(false);
const editDialogVisible = ref(false);
const editLoading = ref(false);
const permissionList = ref<PermissionInfo[]>([]);
const permissionLoading = ref(false);
const editForm = reactive<ApiInfo>({
  id: 0,
  full_path: "",
  method: "",
  module: "",
  check_mode: "auto",
  required_permission: "",
  description: "",
  status: 1
});

const getMethodType = (
  method: string
): "success" | "info" | "warning" | "primary" | "danger" => {
  const map: Record<
    string,
    "success" | "info" | "warning" | "primary" | "danger"
  > = {
    GET: "success",
    POST: "primary",
    PUT: "warning",
    DELETE: "danger",
    PATCH: "warning",
    ANY: "info"
  };
  return map[method] || "info";
};

const getAutoPermissionAction = (method: string): string => {
  const map: Record<string, string> = {
    GET: "view",
    POST: "add",
    PUT: "edit",
    PATCH: "edit",
    DELETE: "delete",
    ANY: "unknown"
  };
  return map[method] || "unknown";
};

const fetchApiList = async () => {
  tableLoading.value = true;
  try {
    const res: any = await getApiList(searchParams);
    if (res?.code === 200) {
      apiList.value = res.data.list || [];
      totalCount.value = res.data.total || 0;
    }
  } catch (error) {
    message("获取列表失败", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

const handleSearch = () => {
  searchParams.page = 1;
  fetchApiList();
};

const resetSearch = () => {
  searchParams.keyword = "";
  searchParams.module = "";
  searchParams.status = undefined;
  searchParams.page = 1;
  fetchApiList();
};

const fetchPermissionList = async () => {
  permissionLoading.value = true;
  try {
    const res: any = await getPermissionList({ page: 1, limit: 1000 });
    if (res?.code === 200) {
      permissionList.value = res.data.list || [];
    }
  } catch (error) {
    console.error("获取权限列表失败", error);
  } finally {
    permissionLoading.value = false;
  }
};

const handleEdit = (row: ApiInfo) => {
  Object.assign(editForm, row);
  editDialogVisible.value = true;
  // 打开对话框时加载权限列表
  if (permissionList.value.length === 0) {
    fetchPermissionList();
  }
};

const confirmEdit = async () => {
  editLoading.value = true;
  try {
    const res: any = await updateApi({
      id: editForm.id,
      module: editForm.module,
      check_mode: editForm.check_mode,
      required_permission: editForm.required_permission,
      description: editForm.description,
      status: editForm.status
    });
    if (res?.code === 200) {
      message("更新成功", { type: "success" });
      editDialogVisible.value = false;
      fetchApiList();
    }
  } catch (error) {
    message("更新失败", { type: "error" });
  } finally {
    editLoading.value = false;
  }
};

const handleReset = async (clearExisting: boolean) => {
  tableLoading.value = true;
  try {
    const res: any = await resetApiData(clearExisting);
    if (res?.code === 200) {
      message(`成功同步 ${res.data.imported_count} 个接口`, {
        type: "success"
      });
      fetchApiList();
    }
  } catch (error) {
    message("同步失败", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

const handleSizeChange = () => {
  searchParams.page = 1;
  fetchApiList();
};

const handleCurrentChange = () => {
  fetchApiList();
};

onMounted(() => {
  fetchApiList();
});
</script>

<style lang="scss" scoped>
.app-container {
  padding: 20px;

  .main-card {
    // 顶部操作区
    .header-section {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 20px;
      padding: 16px;
      background: #fafbfc;
      border-radius: 4px;
      margin-bottom: 0;

      .search-area {
        flex: 1;

        .search-form {
          margin-bottom: 0;

          :deep(.el-form-item) {
            margin-bottom: 0;
            margin-right: 12px;

            &:last-child {
              margin-right: 0;
            }

            .el-form-item__label {
              font-weight: 500;
              color: #606266;
              font-size: 13px;
            }
          }
        }
      }

      .action-area {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;

        .refresh-btn {
          &:hover {
            color: #409eff;
            border-color: #c6e2ff;
            background-color: #ecf5ff;
          }
        }
      }
    }

    :deep(.el-table) {
      font-size: 13px;

      .el-table__header th {
        background: #fafafa;
        color: #606266;
        font-weight: 600;
        font-size: 13px;
      }

      .el-table__body tr:hover > td {
        background: #f5f7fa !important;
      }
    }

    .pagination-wrap {
      margin-top: 16px;
      display: flex;
      justify-content: flex-end;
      padding: 12px 0 0;
      border-top: 1px solid #ebeef5;
    }
  }

  // ID 文本
  .id-text {
    font-family: "Consolas", monospace;
    font-size: 12px;
    color: #909399;
  }

  // 路径单元格 - 唯一保留图标的地方
  .path-cell {
    display: flex;
    align-items: center;
    gap: 6px;

    .path-icon {
      color: #409eff;
      font-size: 14px;
      flex-shrink: 0;
    }

    .path-text {
      font-family: "Consolas", "Monaco", "Courier New", monospace;
      font-size: 12px;
      color: #303133;
    }
  }

  // 权限文本
  .permission-text {
    font-family: "Consolas", "Monaco", monospace;
    font-size: 12px;
    color: #606266;
  }

  // 时间文本
  .time-text {
    font-size: 12px;
    color: #909399;
  }

  .text-muted {
    color: #c0c4cc;
    font-size: 12px;
  }

  // 对话框样式
  :deep(.el-dialog) {
    .el-dialog__body {
      padding: 20px 24px;
    }

    .el-form-item {
      margin-bottom: 18px;

      .el-form-item__label {
        font-size: 13px;
        color: #606266;
        font-weight: 500;
      }
    }
  }

  // 信息展示
  .info-display {
    display: flex;
    align-items: center;
    padding: 6px 10px;
    background: #f5f7fa;
    border-radius: 4px;
    font-family: "Consolas", "Monaco", monospace;
    font-size: 12px;
    color: #606266;

    .info-icon {
      color: #409eff;
      margin-right: 6px;
      font-size: 13px;
    }
  }

  // 权限代码
  .permission-code {
    background: #ecf5ff;
    color: #409eff;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: "Consolas", monospace;
    font-size: 12px;
    font-weight: 500;
    margin-left: 4px;
  }

  // 权限下拉选项
  :deep(.el-select-dropdown__item) {
    height: auto;
    padding: 10px 12px;

    .permission-option {
      .permission-option-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;

        .permission-name {
          font-size: 13px;
          color: #303133;
          font-weight: 500;
        }

        .permission-iden {
          font-size: 12px;
          color: #909399;
          font-family: "Consolas", monospace;
          background: #f5f7fa;
          padding: 2px 8px;
          border-radius: 3px;
        }
      }

      .permission-desc {
        font-size: 12px;
        color: #909399;
        line-height: 1.5;
        margin-top: 4px;
      }
    }
  }
}
</style>
