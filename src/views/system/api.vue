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
              <el-select
                v-model="searchParams.module"
                placeholder="全部模块"
                clearable
                filterable
                size="small"
                style="width: 120px"
              >
                <el-option
                  v-for="mod in moduleList"
                  :key="mod"
                  :label="mod"
                  :value="mod"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="版本">
              <el-select
                v-model="searchParams.version"
                placeholder="全部版本"
                clearable
                size="small"
                style="width: 100px"
              >
                <el-option
                  v-for="ver in versionList"
                  :key="ver"
                  :label="ver"
                  :value="ver"
                />
              </el-select>
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
            class="refresh-btn"
            @click="fetchApiList"
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
        v-loading="tableLoading"
        :data="apiList"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" align="center" fixed />
        <el-table-column prop="id" label="ID" align="center">
          <template #default="{ row }">
            <span class="id-text">#{{ row.id }}</span>
          </template>
        </el-table-column>
        <el-table-column label="版本" align="center" width="80">
          <template #default="{ row }">
            <el-tag :type="getVersionType(row.version)" size="small">
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
        <el-table-column label="接口路径" min-width="250" show-overflow-tooltip>
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
        <el-table-column label="指定权限" min-width="150" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.required_permission" class="permission-text">
              {{ row.required_permission }}
            </span>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="描述" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.description" class="desc-text">
              {{ row.description }}
            </span>
            <span v-else class="text-muted">暂无描述</span>
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

        <div
          v-if="editForm.check_mode === 'auto'"
          style="margin: 0 0 18px 70px"
        >
          <el-alert type="info" :closable="false" style="font-size: 12px">
            自动映射为：
            <code class="permission-code">
              {{ editForm.module || "module" }}:{{
                getAutoPermissionAction(editForm.method)
              }}
            </code>
          </el-alert>
        </div>

        <el-form-item v-if="editForm.check_mode === 'manual'" label="指定权限">
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
        <el-button size="small" @click="editDialogVisible = false">
          取消
        </el-button>
        <el-button
          type="primary"
          :loading="editLoading"
          size="small"
          @click="confirmEdit"
        >
          保存
        </el-button>
      </template>
    </el-dialog>
  </div>
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
  version: "",
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
const moduleList = ref<string[]>([]);
const versionList = ref<string[]>([]);
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

const getVersionType = (
  version: string
): "success" | "primary" | "warning" | "danger" | "info" => {
  const map: Record<
    string,
    "success" | "primary" | "warning" | "danger" | "info"
  > = {
    v1: "primary",
    v2: "success",
    v3: "warning",
    v4: "danger",
    v5: "info"
  };
  return map[version] || "primary";
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
      // 更新模块列表
      if (res.data.module_list) {
        moduleList.value = res.data.module_list;
      }
      // 更新版本列表
      if (res.data.version_list) {
        versionList.value = res.data.version_list;
      }
    } else {
      message(res?.msg || "获取列表失败", { type: "error" });
    }
  } catch (error: any) {
    message(error?.message || "获取列表失败", { type: "error" });
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
  searchParams.version = "";
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
    } else {
      message(res?.msg || "获取权限列表失败", { type: "error" });
    }
  } catch (error: any) {
    message(error?.message || "获取权限列表失败", { type: "error" });
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
    } else {
      // 处理后端返回的错误信息
      message(res?.msg || "更新失败", { type: "error" });
    }
  } catch (error: any) {
    // 处理网络错误或其他异常
    message(error?.message || "更新失败", { type: "error" });
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
    } else {
      message(res?.msg || "同步失败", { type: "error" });
    }
  } catch (error: any) {
    message(error?.message || "同步失败", { type: "error" });
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
      gap: 20px;
      align-items: flex-start;
      justify-content: space-between;
      padding: 16px;
      margin-bottom: 0;
      background: #fafbfc;
      border-radius: 4px;

      .search-area {
        flex: 1;

        .search-form {
          margin-bottom: 0;

          :deep(.el-form-item) {
            margin-right: 12px;
            margin-bottom: 0;

            &:last-child {
              margin-right: 0;
            }

            .el-form-item__label {
              font-size: 13px;
              font-weight: 500;
              color: #606266;
            }
          }
        }
      }

      .action-area {
        display: flex;
        flex-shrink: 0;
        gap: 8px;
        align-items: center;

        .refresh-btn {
          &:hover {
            color: #409eff;
            background-color: #ecf5ff;
            border-color: #c6e2ff;
          }
        }
      }
    }

    :deep(.el-table) {
      font-size: 13px;

      .el-table__header th {
        font-size: 13px;
        font-weight: 600;
        color: #606266;
        background: #fafafa;
      }

      .el-table__body tr:hover > td {
        background: #f5f7fa !important;
      }
    }

    .pagination-wrap {
      display: flex;
      justify-content: flex-end;
      padding: 12px 0 0;
      margin-top: 16px;
      border-top: 1px solid #ebeef5;
    }
  }

  // ID 文本
  .id-text {
    font-family: Consolas, monospace;
    font-size: 12px;
    color: #909399;
  }

  // 路径单元格 - 唯一保留图标的地方
  .path-cell {
    display: flex;
    gap: 6px;
    align-items: center;

    .path-icon {
      flex-shrink: 0;
      font-size: 14px;
      color: #409eff;
    }

    .path-text {
      font-family: Consolas, Monaco, "Courier New", monospace;
      font-size: 12px;
      color: #303133;
    }
  }

  // 权限文本
  .permission-text {
    font-family: Consolas, Monaco, monospace;
    font-size: 12px;
    color: #606266;
  }

  // 时间文本
  .time-text {
    font-size: 12px;
    color: #909399;
  }

  // 描述文本
  .desc-text {
    font-size: 12px;
    line-height: 1.5;
    color: #606266;
  }

  .text-muted {
    font-size: 12px;
    color: #c0c4cc;
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
        font-weight: 500;
        color: #606266;
      }
    }
  }

  // 信息展示
  .info-display {
    display: flex;
    align-items: center;
    padding: 6px 10px;
    font-family: Consolas, Monaco, monospace;
    font-size: 12px;
    color: #606266;
    background: #f5f7fa;
    border-radius: 4px;

    .info-icon {
      margin-right: 6px;
      font-size: 13px;
      color: #409eff;
    }
  }

  // 权限代码
  .permission-code {
    padding: 2px 6px;
    margin-left: 4px;
    font-family: Consolas, monospace;
    font-size: 12px;
    font-weight: 500;
    color: #409eff;
    background: #ecf5ff;
    border-radius: 3px;
  }

  // 权限下拉选项
  :deep(.el-select-dropdown__item) {
    height: auto;
    padding: 10px 12px;

    .permission-option {
      .permission-option-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 4px;

        .permission-name {
          font-size: 13px;
          font-weight: 500;
          color: #303133;
        }

        .permission-iden {
          padding: 2px 8px;
          font-family: Consolas, monospace;
          font-size: 12px;
          color: #909399;
          background: #f5f7fa;
          border-radius: 3px;
        }
      }

      .permission-desc {
        margin-top: 4px;
        font-size: 12px;
        line-height: 1.5;
        color: #909399;
      }
    }
  }
}
</style>
