<template>
  <div class="permission-container">
    <el-dialog
      v-model="showAddOrEditModal"
      :title="currentPermission.id ? '编辑权限' : '新增权限'"
      width="550"
      :before-close="handleClose"
    >
      <el-form
        ref="permissionFormRef"
        :model="currentPermission"
        :rules="permissionRules"
        label-width="100px"
      >
        <el-form-item label="模块名称" prop="name">
          <el-input
            v-model="currentPermission.name"
            placeholder="请输入模块名称，如：user、article"
          />
        </el-form-item>
        <el-form-item label="权限标识" prop="iden">
          <el-input
            v-model="currentPermission.iden"
            placeholder="请输入权限标识，如：user:view 或 user:view:all"
          />
        </el-form-item>
        <el-form-item label="权限描述" prop="description">
          <el-input
            v-model="currentPermission.description"
            type="textarea"
            placeholder="请输入权限描述"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="showAddOrEditModal = false">取消</el-button>
          <el-button
            type="primary"
            :loading="submitting"
            @click="submitPermission"
          >
            确认
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 分配API对话框 -->
    <el-dialog
      v-model="showAssignApiModal"
      :before-close="handleClose"
      destroy-on-close
    >
      <el-form label-width="100px" class="assign-api-form">
        <div class="permission-info-card">
          <div class="permission-info-item">
            <span class="info-label">权限ID</span>
            <el-tag type="info" effect="plain" size="small" class="id-tag">
              {{ currentPermission.id }}
            </el-tag>
          </div>
          <div class="permission-info-item">
            <span class="info-label">权限名称</span>
            <span class="permission-name-text">
              {{ currentPermission.name }}
            </span>
          </div>
          <div class="permission-info-item">
            <span class="info-label">权限标识</span>
            <el-tag
              :type="getTagTypeByPermissionType(currentPermission.iden)"
              size="small"
            >
              {{ currentPermission.iden }}
            </el-tag>
          </div>
        </div>
        <el-divider content-position="left">
          <span class="api-divider-title">API接口分配</span>
        </el-divider>
        <div
          v-loading="apiLoading"
          class="api-container"
          element-loading-text="加载API接口中..."
        >
          <div class="api-filter">
            <el-input
              v-model="apiSearchKeyword"
              placeholder="搜索接口"
              clearable
              @input="filterApis"
            >
              <template #prefix>
                <el-icon>
                  <Search />
                </el-icon>
              </template>
            </el-input>
            <el-select
              v-model="apiSearchMethod"
              placeholder="请求方法"
              clearable
              @change="filterApis"
            >
              <el-option label="全部" value="" />
              <el-option label="GET" value="GET" />
              <el-option label="POST" value="POST" />
              <el-option label="PUT" value="PUT" />
              <el-option label="DELETE" value="DELETE" />
            </el-select>
            <el-select
              v-model="apiSearchStatus"
              placeholder="状态"
              clearable
              @change="filterApis"
            >
              <el-option label="全部" value="" />
              <el-option label="开放" :value="1" />
              <el-option label="维护" :value="0" />
              <el-option label="关闭" :value="3" />
            </el-select>
          </div>
          <div class="api-selection">
            <div class="api-list-header">
              <div class="api-checkbox">
                <el-checkbox
                  v-model="selectAllApis"
                  :indeterminate="isIndeterminate"
                  @change="handleSelectAllApis"
                />
              </div>
              <div class="api-id">ID</div>
              <div class="api-method">方法</div>
              <div class="api-path">路径</div>
              <div class="api-status">状态</div>
            </div>
            <div class="api-list">
              <template v-if="filteredApis.length > 0">
                <div v-for="api in filteredApis" :key="api.id" class="api-item">
                  <div class="api-checkbox">
                    <el-checkbox
                      v-model="api.selected"
                      @change="handleApiCheckChange"
                    />
                  </div>
                  <div class="api-id">{{ api.id }}</div>
                  <div class="api-method">
                    <el-tag :class="getMethodClass(api.method)" size="small">
                      {{ api.method.toLowerCase() }}
                    </el-tag>
                  </div>
                  <div class="api-path">
                    <el-tooltip
                      :content="api.full_path"
                      placement="top"
                      :show-after="500"
                    >
                      <div class="api-path-text">{{ api.full_path }}</div>
                    </el-tooltip>
                  </div>
                  <div class="api-status">
                    <div class="status-indicator">
                      <span
                        class="status-dot"
                        :class="getApiStatusClass(api.status)"
                      />
                      <span class="status-text">
                        {{ getApiStatusText(api.status) }}
                      </span>
                    </div>
                  </div>
                </div>
              </template>
              <el-empty v-else description="暂无数据" />
            </div>
          </div>
          <div v-if="filteredApis.length > 0" class="api-info">
            <span>
              已选择
              <strong>{{ apiList.filter(api => api.selected).length }}</strong>
              个API
            </span>
            <span>
              共
              <strong>{{ filteredApis.length }}</strong>
              个API
            </span>
          </div>
        </div>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button plain @click="showAssignApiModal = false">取 消</el-button>
          <el-button
            type="primary"
            :loading="apiSubmitting"
            @click="submitApiAssignment"
          >
            <el-icon>
              <Check />
            </el-icon>
            确认分配
          </el-button>
        </div>
      </template>
    </el-dialog>

    <el-card class="main-card" shadow="never">
      <template #header>
        <el-row :gutter="16">
          <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="2">
            <span class="header-title">权限管理</span>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-input
              v-model="searchForm.name"
              placeholder="权限名称"
              clearable
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-input
              v-model="searchForm.iden"
              placeholder="权限标识"
              clearable
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-input
              v-model="searchForm.description"
              placeholder="权限描述"
              clearable
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-select
              v-model="searchForm.delete_status"
              placeholder="删除状态"
              style="width: 100%"
            >
              <el-option label="全部" value="" />
              <el-option label="仅未删除" value="not_deleted" />
              <el-option label="包含已删除" value="with_deleted" />
              <el-option label="仅已删除" value="only_deleted" />
            </el-select>
          </el-col>
          <el-col :xs="12" :sm="6" :md="4" :lg="3" :xl="2">
            <el-button
              type="primary"
              :icon="Search"
              style="width: 100%"
              @click="handleSearch"
            >
              搜索
            </el-button>
          </el-col>
          <el-col :xs="12" :sm="6" :md="4" :lg="3" :xl="2">
            <el-button
              type="default"
              :icon="RefreshLeft"
              style="width: 100%"
              @click="resetSearchForm"
            >
              重置
            </el-button>
          </el-col>
        </el-row>
      </template>

      <div class="table-toolbar">
        <div class="left-tools">
          <el-button
            type="primary"
            :icon="CirclePlus"
            size="small"
            @click="handleAdd"
          >
            新增权限
          </el-button>
          <el-button
            type="danger"
            :icon="Delete"
            :disabled="selectedPermissions.length === 0"
            size="small"
            @click="handleBatchDelete"
          >
            批量删除
          </el-button>
          <el-button :icon="Sort" size="small" @click="toggleTableType">
            {{ toggleButtonText }}
          </el-button>
        </div>
        <div class="right-tools">
          <el-button :icon="Printer" circle title="打印" size="small" />
          <el-button :icon="Upload" circle title="导出" size="small" />
        </div>
      </div>

      <div v-if="tableType === 'flat'">
        <el-table
          v-loading="tableLoading"
          border
          :data="permissionsList"
          style="width: 100%; margin-bottom: 20px"
          :header-cell-style="headerCellStyle"
          size="small"
          class="permissions-table"
          @selection-change="handleSelectionChange"
          @sort-change="handleSortChange"
        >
          <el-table-column type="selection" width="45" align="center" />
          <el-table-column prop="id" label="ID" width="100">
            <template #default="{ row }">
              <el-tag
                size="small"
                :type="
                  row.id?.toString().startsWith('category-')
                    ? 'primary'
                    : 'info'
                "
                effect="plain"
              >
                {{
                  row.id?.toString().startsWith("category-") ? "分类" : row.id
                }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column
            show-overflow-tooltip
            label="权限名称"
            prop="name"
            sortable="custom"
          >
            <template #default="{ row }">
              <div class="cell-with-icon">
                <el-icon>
                  <component :is="getPermissionTypeIcon(row.iden)" />
                </el-icon>
                <span>{{ row.name }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column
            show-overflow-tooltip
            label="权限标识"
            prop="iden"
            width="180"
          >
            <template #default="{ row }">
              <el-tag
                :type="getTagTypeByPermissionType(row.iden)"
                size="small"
                effect="plain"
              >
                {{ row.iden }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column
            show-overflow-tooltip
            label="描述"
            prop="description"
          />
          <el-table-column
            show-overflow-tooltip
            label="创建时间"
            prop="create_time"
            sortable="custom"
            width="180"
          />
          <el-table-column
            show-overflow-tooltip
            label="更新时间"
            prop="update_time"
            sortable="custom"
            width="180"
          />
          <el-table-column
            fixed="right"
            align="center"
            header-align="center"
            label="操作"
          >
            <template #default="{ row }">
              <div class="action-buttons">
                <template v-if="!row.delete_time">
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
                  <el-button
                    type="danger"
                    link
                    size="small"
                    @click="handleDelete(row)"
                  >
                    <el-icon>
                      <Delete />
                    </el-icon>
                    删除
                  </el-button>
                  <!-- 分配API功能已废弃，改用 check_mode + required_permission 方式 -->
                  <!-- <el-button
                    type="success"
                    link
                    size="small"
                    @click="handleAssignApi(row)"
                  >
                    <el-icon>
                      <Link />
                    </el-icon>
                    分配API
                  </el-button> -->
                </template>
                <template v-else>
                  <el-button
                    type="warning"
                    link
                    size="small"
                    @click="handleRestore(row)"
                  >
                    <el-icon>
                      <RefreshRight />
                    </el-icon>
                    恢复
                  </el-button>
                  <el-button
                    type="danger"
                    link
                    size="small"
                    @click="handleDelete(row, true)"
                  >
                    <el-icon>
                      <Delete />
                    </el-icon>
                    彻底删除
                  </el-button>
                </template>
              </div>
            </template>
          </el-table-column>
        </el-table>

        <el-pagination
          v-model:current-page="pageConfig.currentPage"
          v-model:page-size="pageConfig.pageSize"
          style="justify-content: flex-end; margin-top: 12px"
          :page-sizes="[5, 10, 20, 30]"
          :background="true"
          layout="total, sizes, prev, pager, next, jumper"
          :total="pageConfig.total"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>

      <div v-else class="tree-container">
        <div class="tree-header">
          <span class="header-title">权限分类列表</span>
          <div class="header-buttons">
            <el-button
              type="primary"
              :icon="CirclePlus"
              size="small"
              @click="handleAdd"
            >
              新增权限
            </el-button>
            <el-button :icon="Sort" size="small" @click="toggleTableType">
              {{ toggleButtonText }}
            </el-button>
          </div>
        </div>

        <div v-show="!tableLoading" class="simple-tree">
          <div
            v-for="category in permissionsTree"
            :key="category.id"
            class="tree-category"
          >
            <div class="category-header" @click="toggleCategory(category.id)">
              <el-icon
                v-if="expandedCategories.includes(category.id)"
                class="category-toggle-icon"
              >
                <ArrowDown />
              </el-icon>
              <el-icon v-else class="category-toggle-icon">
                <ArrowRight />
              </el-icon>
              <el-icon class="category-icon">
                <component :is="getPermissionTypeIcon(category.iden)" />
              </el-icon>
              <span class="category-name">{{ category.name }}</span>
              <span class="category-count">
                ({{ category.children?.length || 0 }})
              </span>
            </div>

            <div
              v-if="
                category.children &&
                category.children.length > 0 &&
                expandedCategories.includes(category.id)
              "
              class="permissions-list"
            >
              <div
                v-for="permission in category.children"
                :key="permission.id"
                class="permission-item"
              >
                <div class="permission-main">
                  <el-icon class="permission-icon">
                    <component :is="getPermissionTypeIcon(permission.iden)" />
                  </el-icon>
                  <el-tag
                    size="small"
                    :type="getTagTypeByPermissionType(permission.iden)"
                    class="permission-tag"
                  >
                    {{ permission.iden }}
                  </el-tag>
                  <span class="permission-name">{{ permission.name }}</span>
                </div>

                <div class="permission-actions">
                  <template v-if="!permission.delete_time">
                    <el-button
                      type="primary"
                      link
                      size="small"
                      @click.stop="handleEdit(permission)"
                    >
                      <el-icon>
                        <Edit />
                      </el-icon>
                    </el-button>
                    <el-button
                      type="danger"
                      link
                      size="small"
                      @click.stop="handleDelete(permission)"
                    >
                      <el-icon>
                        <Delete />
                      </el-icon>
                    </el-button>
                    <el-button
                      type="success"
                      link
                      size="small"
                      @click.stop="handleAssignApi(permission)"
                    >
                      <el-icon>
                        <Link />
                      </el-icon>
                    </el-button>
                  </template>
                  <template v-else>
                    <el-button
                      type="warning"
                      link
                      size="small"
                      @click.stop="handleRestore(permission)"
                    >
                      <el-icon>
                        <RefreshRight />
                      </el-icon>
                    </el-button>
                    <el-button
                      type="danger"
                      link
                      size="small"
                      @click.stop="handleDelete(permission, true)"
                    >
                      <el-icon>
                        <Delete />
                      </el-icon>
                    </el-button>
                  </template>
                </div>
              </div>
            </div>
            <div
              v-if="!category.children || category.children.length === 0"
              class="no-permissions"
            >
              <span class="empty-text">暂无权限</span>
            </div>
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "permissions"
});

import { ElMessageBox, ElNotification, ElLoading } from "element-plus";
import { ref, reactive, onMounted, computed, h } from "vue";
import {
  Search,
  RefreshLeft,
  RefreshRight,
  CirclePlus,
  Printer,
  Upload,
  Edit,
  Delete,
  User,
  Lock,
  Operation,
  Menu as MenuIcon,
  Document,
  Setting,
  Link,
  Sort,
  Bell,
  Check,
  ArrowDown,
  ArrowRight
} from "@element-plus/icons-vue";
import { message } from "@/utils/message";
import {
  getPermissionList,
  getPermissionTree,
  getPermissionFullTree,
  addPermission,
  updatePermission,
  deletePermission,
  restorePermission,
  getParentPermissions,
  getChildrenPermissions,
  getPermissionCategories,
  getPermissionApis,
  assignPermissionApis,
  type PermissionInfo,
  type PermissionListParams
} from "@/api/permission";
import { getApiList } from "@/api/api";

// 响应式数据
const searchForm = reactive<Partial<PermissionListParams>>({
  name: "",
  iden: "",
  description: "",
  delete_status: undefined
});

const buttonSize = ref<"default" | "small" | "large">("small");
const showAddOrEditModal = ref(false);
const currentPermission = ref<any>({
  name: "",
  iden: "",
  description: "",
  parent_id: null
});
const selectedPermissions = ref<Array<any>>([]);
const tableLoading = ref(false);
const submitting = ref(false);
const permissionFormRef = ref<any>(null);
const tableType = ref<"flat" | "tree">("flat");
const permissionsTree = ref<PermissionInfo[]>([]);
const expandedCategories = ref<string[]>([]);

// 表格样式
const headerCellStyle = {
  backgroundColor: "#f5f7fa",
  color: "#606266",
  fontWeight: "600",
  textAlign: "center" as const,
  height: "40px",
  padding: "6px 0"
};

// 页面配置
const pageConfig = ref({
  currentPage: 1,
  pageSize: 5,
  total: 0
});

// 权限列表
const permissionsList = ref<PermissionInfo[]>([]);

// 修改实现以保存所有数据，只做本地分页
const allPermissions = ref<PermissionInfo[]>([]);

// 表单校验规则
const permissionRules = {
  name: [
    { required: true, message: "请输入模块名称", trigger: "blur" },
    { min: 2, max: 50, message: "长度应为 2 到 50 个字符", trigger: "blur" }
  ],
  iden: [
    { required: true, message: "请输入权限标识", trigger: "blur" },
    {
      pattern:
        /^(\*|[a-zA-Z][a-zA-Z0-9_]*)(?::(\*|[a-zA-Z][a-zA-Z0-9_]*))?(?::(\*|[a-zA-Z][a-zA-Z0-9_]*))?$/,
      message:
        "格式错误，支持格式：module:action、module:action:scope 或使用 * 通配符（如：*、*:*、user:*）",
      trigger: "blur"
    }
  ]
};

// 权限类型图标映射（基于iden值）
const permissionTypeIconMap = {
  user: User,
  product: Document,
  article: Document,
  notice: Bell,
  open: Link,
  default: Setting
};

// 权限类型标签样式映射
const permissionTypeTagMap = {
  user: "primary",
  product: "success",
  article: "warning",
  notice: "info",
  open: "danger",
  default: "info"
};

// 获取权限类型图标
const getPermissionTypeIcon = (iden: string) => {
  if (!iden) return permissionTypeIconMap.default;
  return permissionTypeIconMap[iden] || permissionTypeIconMap.default;
};

// 获取权限类型对应的标签样式
const getTagTypeByPermissionType = (iden: string) => {
  if (!iden) return permissionTypeTagMap.default;
  return permissionTypeTagMap[iden] || permissionTypeTagMap.default;
};

// 切换表格类型按钮文本
const toggleButtonText = computed(() => {
  return tableType.value === "flat" ? "树形结构" : "扁平结构";
});

// 切换表格类型
const toggleTableType = async () => {
  // 切换前先重置数据
  if (tableType.value === "flat") {
    // 从扁平切换到树形
    tableType.value = "tree";
    // 先清空树数据，显示加载状态
    permissionsTree.value = [];
    await fetchPermissionTree();
  } else {
    // 从树形切换到扁平
    tableType.value = "flat";
    // 重新获取扁平列表
    await fetchPermissionList();
  }
};

// 获取权限树结构
const fetchPermissionTree = async () => {
  tableLoading.value = true;
  try {
    // 使用正确的tree接口
    const res = await getPermissionTree();
    if (res.code === 200 && res.data) {
      // 将对象格式转换为树形数组
      const treeData = [];

      // 处理树形数据
      Object.entries(res.data).forEach(([key, permissions]) => {
        // 确保权限是数组
        const permissionList = Array.isArray(permissions) ? permissions : [];

        // 创建分类节点
        const categoryNode = {
          id: `category-${key}`,
          name: formatCategoryName(key),
          iden: key,
          description: `${formatCategoryName(key)}相关权限`,
          children: permissionList
        };

        treeData.push(categoryNode);
      });

      // 排序处理，确保用户管理在最前面
      treeData.sort((a, b) => {
        if (a.iden === "user") return -1;
        if (b.iden === "user") return 1;
        return a.name.localeCompare(b.name);
      });

      permissionsTree.value = treeData;
    } else {
      message(res.msg || "获取权限树失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取权限树错误:", error);
    message("获取权限树失败，请稍后重试", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 格式化分类名称
const formatCategoryName = (category: string) => {
  const categoryMap = {
    user: "用户管理",
    product: "商品管理",
    article: "文章管理",
    notice: "公告管理",
    open: "开放接口"
  };
  return categoryMap[category] || category;
};

// 切换分类展开/折叠
const toggleCategory = (categoryId: string) => {
  const index = expandedCategories.value.indexOf(categoryId);
  if (index > -1) {
    expandedCategories.value.splice(index, 1);
  } else {
    expandedCategories.value.push(categoryId);
  }
};

// 获取权限列表
const fetchPermissionList = async () => {
  tableLoading.value = true;
  try {
    const params = {
      ...searchForm,
      page: 1, // 服务器分页始终请求第一页
      limit: 100 // 服务器每页100条
    };

    const res = await getPermissionList(params);
    if (res.code === 200) {
      // 保存所有数据 - 修正访问正确的数据结构
      allPermissions.value = res.data.list || [];
      // 使用API返回的分页信息
      pageConfig.value.total = res.data.pagination?.total || 0;

      // 执行本地分页
      updateLocalPagination();
    } else {
      message(res.msg || "获取权限列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取权限列表错误:", error);
    message("获取权限列表失败，请稍后重试", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 本地分页方法
const updateLocalPagination = () => {
  const start = (pageConfig.value.currentPage - 1) * pageConfig.value.pageSize;
  const end = start + pageConfig.value.pageSize;
  permissionsList.value = allPermissions.value.slice(start, end);
};

// 搜索
const handleSearch = () => {
  pageConfig.value.currentPage = 1;
  fetchPermissionList();
};

// 重置搜索表单
const resetSearchForm = () => {
  searchForm.name = "";
  searchForm.iden = "";
  searchForm.description = "";
  searchForm.delete_status = undefined;
  pageConfig.value.currentPage = 1;
  fetchPermissionList();
};

// 处理表格排序
const handleSortChange = ({ prop, order }: { prop: string; order: string }) => {
  if (!prop || !order) return;

  // 在所有数据上执行排序
  if (order === "ascending") {
    allPermissions.value.sort((a, b) => {
      if (a[prop] < b[prop]) return -1;
      if (a[prop] > b[prop]) return 1;
      return 0;
    });
  } else if (order === "descending") {
    allPermissions.value.sort((a, b) => {
      if (a[prop] > b[prop]) return -1;
      if (a[prop] < b[prop]) return 1;
      return 0;
    });
  }

  // 更新本地分页数据
  updateLocalPagination();
};

// 添加权限
const handleAdd = () => {
  currentPermission.value = {
    name: "",
    iden: "",
    description: ""
  };
  showAddOrEditModal.value = true;
};

// 编辑权限
const handleEdit = async (row: PermissionInfo) => {
  // 加载权限树
  if (permissionsTree.value.length === 0) {
    await fetchPermissionTree();
  }

  currentPermission.value = { ...row };
  showAddOrEditModal.value = true;
};

// 提交权限
const submitPermission = async () => {
  if (!permissionFormRef.value) return;

  await permissionFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    submitting.value = true;
    try {
      let res;

      if (currentPermission.value.id) {
        // 更新权限
        res = await updatePermission(currentPermission.value);
      } else {
        // 创建权限
        res = await addPermission(currentPermission.value);
      }

      if (res.code === 200) {
        message(currentPermission.value.id ? "更新权限成功" : "创建权限成功", {
          type: "success"
        });
        showAddOrEditModal.value = false;

        // 刷新权限列表
        fetchPermissionList();

        // 如果是树形结构，更新树
        if (tableType.value === "tree") {
          fetchPermissionTree();
        }
      } else {
        message(
          res.msg ||
            (currentPermission.value.id ? "更新权限失败" : "创建权限失败"),
          { type: "error" }
        );
      }
    } catch (error) {
      console.error(
        currentPermission.value.id ? "更新权限错误:" : "创建权限错误:",
        error
      );
      message(
        currentPermission.value.id
          ? "更新权限失败，请稍后重试"
          : "创建权限失败，请稍后重试",
        { type: "error" }
      );
    } finally {
      submitting.value = false;
    }
  });
};

// 删除权限
const handleDelete = (row: PermissionInfo, isRealDelete: boolean = false) => {
  const confirmTitle = isRealDelete ? "彻底删除确认" : "删除确认";
  const confirmMsg = isRealDelete
    ? `确定要彻底删除权限 "${row.name}" 吗？此操作无法恢复！`
    : `确定要删除权限 "${row.name}" 吗？`;

  ElMessageBox.confirm(confirmMsg, confirmTitle, {
    confirmButtonText: "确定删除",
    cancelButtonText: "取消",
    type: "warning"
  })
    .then(async () => {
      try {
        const res = await deletePermission(row.id, isRealDelete);
        if (res.code === 200) {
          message(isRealDelete ? "彻底删除权限成功" : "删除权限成功", {
            type: "success"
          });
          fetchPermissionList();

          // 如果是树形结构，更新树
          if (tableType.value === "tree") {
            fetchPermissionTree();
          }
        } else {
          message(res.msg || "删除权限失败", { type: "error" });
        }
      } catch (error) {
        console.error("删除权限错误:", error);
        message("删除权限失败，请稍后重试", { type: "error" });
      }
    })
    .catch(() => {});
};

// 恢复权限
const handleRestore = async (row: PermissionInfo) => {
  try {
    const res = await restorePermission(row.id);
    if (res.code === 200) {
      message("恢复权限成功", { type: "success" });
      fetchPermissionList();

      // 如果是树形结构，更新树
      if (tableType.value === "tree") {
        fetchPermissionTree();
      }
    } else {
      message(res.msg || "恢复权限失败", { type: "error" });
    }
  } catch (error) {
    console.error("恢复权限错误:", error);
    message("恢复权限失败，请稍后重试", { type: "error" });
  }
};

// 批量删除权限 - 由于API不支持批量删除，需要循环单个删除
const handleBatchDelete = () => {
  if (selectedPermissions.value.length === 0) {
    message("请至少选择一个权限", { type: "warning" });
    return;
  }

  // 创建响应式变量用于存储复选框状态
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h("div", null, [
      h(
        "p",
        null,
        `确定要删除选中的 ${selectedPermissions.value.length} 个权限吗？`
      ),
      h(
        "div",
        { style: "margin-top: 16px; display: flex; align-items: center;" },
        [
          h("input", {
            type: "checkbox",
            style:
              "width: 16px; height: 16px; margin-right: 8px; cursor: pointer;",
            checked: isRealDelete.value,
            onInput: event => {
              isRealDelete.value = (event.target as HTMLInputElement).checked;
            }
          }),
          h("span", null, "永久删除（否则为软删除，可在回收站恢复）")
        ]
      )
    ]);
  };

  ElMessageBox({
    title: "批量删除确认",
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: "确定删除",
    cancelButtonText: "取消",
    type: "warning",
    beforeClose: (action, instance, done) => {
      if (action === "confirm") {
        instance.confirmButtonLoading = true;
        // 执行批量删除
        batchDeletePermissions(isRealDelete.value)
          .then(() => {
            done();
          })
          .finally(() => {
            instance.confirmButtonLoading = false;
          });
      } else {
        done();
      }
    }
  });
};

// 执行批量删除
const batchDeletePermissions = async (isRealDelete: boolean) => {
  let success = 0;
  let failed = 0;

  // 显示加载提示
  const loading = ElLoading.service({
    lock: true,
    text: "批量删除中...",
    background: "rgba(0, 0, 0, 0.7)"
  });

  // 逐个删除权限
  for (const permission of selectedPermissions.value) {
    try {
      const res = await deletePermission(permission.id, isRealDelete);
      if (res.code === 200) {
        success++;
      } else {
        failed++;
      }
    } catch (error) {
      failed++;
    }
  }

  // 关闭加载提示
  loading.close();

  // 显示结果
  if (success > 0) {
    message(`成功删除 ${success} 个权限`, { type: "success" });
    fetchPermissionList();
    selectedPermissions.value = [];

    // 如果是树形结构，更新树
    if (tableType.value === "tree") {
      fetchPermissionTree();
    }
  }

  if (failed > 0) {
    message(`${failed} 个权限删除失败`, { type: "error" });
  }
};

// 对话框关闭处理
const handleClose = (done: () => void) => {
  ElMessageBox.confirm("确定要关闭吗? 未保存的数据将丢失", "提示", {
    confirmButtonText: "确定",
    cancelButtonText: "取消"
  })
    .then(() => {
      done();
    })
    .catch(() => {});
};

// 状态切换处理 - 因为API不支持status字段，需要修改
const handleStatusChange = async (row: PermissionInfo) => {
  message("当前API不支持修改状态操作", { type: "warning" });
};

// 处理分页大小变化
const handleSizeChange = (val: number) => {
  pageConfig.value.pageSize = val;
  pageConfig.value.currentPage = 1; // 重置到第一页
  updateLocalPagination();
};

// 处理页码变化
const handleCurrentChange = (val: number) => {
  pageConfig.value.currentPage = val;
  updateLocalPagination();
};

// 表格选择变化
const handleSelectionChange = (selection: Array<any>) => {
  selectedPermissions.value = selection;
};

// API相关数据
const showAssignApiModal = ref(false);
const apiLoading = ref(false);
const apiSubmitting = ref(false);
const apiList = ref<any[]>([]);
const selectedApiIds = ref<number[]>([]);
const apiSearchKeyword = ref("");
const apiSearchMethod = ref("");
const apiSearchStatus = ref("");
const selectAllApis = ref(false);
const isIndeterminate = ref(false);

// 获取API列表
const fetchApiList = async () => {
  apiLoading.value = true;
  // 清空已有数据
  apiList.value = [];

  try {
    const res: any = await getApiList({
      page: 1,
      page_size: 1000, // 获取足够多的API
      keyword: ""
    });

    if (res?.code === 200 && res?.data) {
      // 为每个API添加selected属性
      apiList.value = (res.data.list || []).map(api => ({
        ...api,
        selected: false
      }));

      // 如果当前权限已经分配了API，标记为已选中
      if (currentPermission.value.id) {
        await loadPermissionApis(currentPermission.value.id);
      }
    } else {
      message(res?.msg || "获取API列表失败", { type: "error" });
    }
  } catch (err) {
    console.error("获取API列表失败:", err);
    message("获取API列表失败，请稍后重试", { type: "error" });
  } finally {
    setTimeout(() => {
      apiLoading.value = false;
    }, 300); // 添加短暂延迟，确保加载动画流畅
  }
};

// 加载权限已分配的API
const loadPermissionApis = async (permissionId: number) => {
  try {
    const res: any = await getPermissionApis(permissionId);

    if (res?.code === 200 && res?.data) {
      // 使用更新后的返回数据结构
      const permissionApiData = res.data;
      const assignedApis = permissionApiData.apis || [];

      console.log("获取到的权限API数据:", permissionApiData);

      // 更新当前权限信息，确保ID显示正确
      currentPermission.value = {
        ...currentPermission.value,
        id: permissionApiData.permission_id,
        name: permissionApiData.permission_name
      };

      // 标记已分配的API为选中状态
      apiList.value.forEach(api => {
        // 检查API是否在已分配的列表中
        api.selected = assignedApis.some(
          assignedApi => assignedApi.id === api.id
        );
      });

      // 更新全选状态
      updateSelectAllStatus();

      // 显示API数量信息
      if (assignedApis.length > 0) {
        message(`该权限已分配 ${assignedApis.length} 个API接口`, {
          type: "success"
        });
      }
    }
  } catch (err) {
    console.error("获取权限API失败:", err);
    message("获取权限API失败，请稍后重试", { type: "error" });
  }
};

// 过滤API列表
const filteredApis = computed(() => {
  return apiList.value.filter(api => {
    const matchKeyword =
      !apiSearchKeyword.value ||
      api.full_path
        .toLowerCase()
        .includes(apiSearchKeyword.value.toLowerCase()) ||
      api.model.toLowerCase().includes(apiSearchKeyword.value.toLowerCase());

    const matchMethod =
      !apiSearchMethod.value ||
      api.method.toUpperCase() === apiSearchMethod.value.toUpperCase();

    const matchStatus =
      apiSearchStatus.value === "" || api.status === apiSearchStatus.value;

    return matchKeyword && matchMethod && matchStatus;
  });
});

// 过滤API
const filterApis = () => {
  updateSelectAllStatus();
};

// 更新全选状态
const updateSelectAllStatus = () => {
  const selectedCount = filteredApis.value.filter(api => api.selected).length;
  selectAllApis.value =
    selectedCount === filteredApis.value.length &&
    filteredApis.value.length > 0;
  isIndeterminate.value =
    selectedCount > 0 && selectedCount < filteredApis.value.length;
};

// 全选/取消全选
const handleSelectAllApis = (val: boolean) => {
  filteredApis.value.forEach(api => {
    api.selected = val;
  });
  isIndeterminate.value = false;
};

// 单个API选择状态变化
const handleApiCheckChange = () => {
  updateSelectAllStatus();
};

// 提交API分配
const submitApiAssignment = async () => {
  const selectedApis = apiList.value.filter(api => api.selected);
  const selectedApiIds = selectedApis.map(api => api.id);

  apiSubmitting.value = true;
  try {
    // 提交分配API的请求
    const res: any = await assignPermissionApis(
      currentPermission.value.id,
      selectedApiIds
    );

    if (res?.code === 200) {
      message(
        `成功为权限 ${currentPermission.value.name} 分配了 ${selectedApis.length} 个API接口`,
        { type: "success" }
      );
      showAssignApiModal.value = false;
    } else {
      message(res?.msg || "API分配失败", { type: "error" });
    }
  } catch (err) {
    console.error("API分配失败:", err);
    message("API分配失败，请稍后重试", { type: "error" });
  } finally {
    apiSubmitting.value = false;
  }
};

// 分配API
const handleAssignApi = async (row: PermissionInfo) => {
  // 确保row.id存在且正确传递
  if (!row.id) {
    message("权限ID不存在，无法分配API", { type: "error" });
    return;
  }

  // 保存完整的权限对象
  currentPermission.value = { ...row };
  showAssignApiModal.value = true;

  // 获取API列表，然后加载已分配的API
  await fetchApiList();
};

// API状态类
const getApiStatusClass = (status: number) => {
  switch (status) {
    case 0:
      return "warning";
    case 1:
      return "success";
    case 3:
      return "info";
    default:
      return "";
  }
};

// API状态文本
const getApiStatusText = (status: number) => {
  switch (status) {
    case 0:
      return "维护";
    case 1:
      return "开放";
    case 3:
      return "关闭";
    default:
      return "未知";
  }
};

// 获取方法对应的样式类
const getMethodClass = (method: string) => {
  if (!method) return "";

  const methodLower = method.toLowerCase();
  switch (methodLower) {
    case "get":
      return "get";
    case "post":
      return "post";
    case "put":
      return "put";
    case "delete":
      return "delete";
    case "patch":
      return "patch";
    case "options":
      return "options";
    case "head":
      return "head";
    case "connect":
      return "connect";
    case "trace":
      return "trace";
    case "any":
      return "any";
    default:
      return "other";
  }
};

onMounted(async () => {
  try {
    await fetchPermissionList();
  } catch (error) {
    console.error("初始化加载权限列表失败:", error);
    message("初始化加载权限列表失败，请刷新页面重试", { type: "error" });
  }
});
</script>

<style lang="scss" scoped>
.permission-container {
  padding: 20px;

  @media (width >=1920px) {
    .el-col {
      margin-bottom: 0;
    }
  }

  @media (width <=767px) {
    .el-col {
      margin-bottom: 8px;
    }
  }

  .header-title {
    font-size: 16px;
    font-weight: bold;
    line-height: 32px;
  }

  .main-card {
    background-color: #fff;
    border-radius: 4px;
  }

  // 对话框样式优化
  :deep(.el-dialog) {
    overflow: hidden;
    box-shadow: 0 4px 16px rgb(0 0 0 / 10%);
  }

  .table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;

    .left-tools,
    .right-tools {
      display: flex;
      gap: 8px;
    }
  }

  .action-buttons {
    display: flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;

    .el-button {
      height: 28px;
      padding: 2px 6px;
      margin: 0 3px;

      .el-icon {
        margin-right: 2px;
        font-size: 14px;
      }
    }
  }

  .permissions-table {
    .id-tag {
      padding: 0 6px;
      font-size: 11px;
    }

    .cell-with-icon {
      display: flex;
      gap: 6px;
      align-items: center;
      justify-content: flex-start;

      .el-icon {
        font-size: 14px;
        color: #409eff;
      }
    }
  }

  .el-col {
    margin-bottom: 12px;
  }

  .text-muted {
    font-style: italic;
    color: #999;
  }

  .tree-container {
    padding: 20px;
    margin-top: 10px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgb(0 0 0 / 5%);

    .tree-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 12px;
      margin-bottom: 20px;
      border-bottom: 1px solid #f3f4f6;

      .header-title {
        font-size: 15px;
        font-weight: 600;
        color: #111827;
      }

      .header-buttons {
        display: flex;
        gap: 8px;
      }
    }

    .simple-tree {
      .tree-category {
        margin-bottom: 12px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e4e7ed;
        border-radius: 4px;

        .category-header {
          display: flex;
          align-items: center;
          padding: 12px 16px;
          cursor: pointer;
          user-select: none;
          background: #fafafa;
          border-bottom: 1px solid #e8e8e8;
          transition: background-color 0.2s;

          &:hover {
            background: #f0f0f0;
          }

          .category-toggle-icon {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            margin-right: 8px;
            color: #666;
            transition: transform 0.2s;
          }

          .category-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            margin-right: 8px;
            color: #409eff;
          }

          .category-name {
            flex: 1;
            font-size: 14px;
            font-weight: 600;
            color: #303133;
          }

          .category-count {
            padding: 2px 8px;
            font-size: 12px;
            font-weight: normal;
            color: #909399;
            background: #f4f4f5;
            border-radius: 10px;
          }
        }

        .permissions-list {
          padding: 0;
          background: #fff;

          .permission-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.15s ease;

            &:last-child {
              border-bottom: none;
            }

            &:hover {
              background: #f9fafb;
            }

            .permission-main {
              display: flex;
              flex: 1;
              gap: 10px;
              align-items: center;
              min-width: 0;

              .permission-icon {
                display: flex;
                flex-shrink: 0;
                align-items: center;
                justify-content: center;
                width: 16px;
                height: 16px;
                color: #6b7280;
              }

              .permission-tag {
                flex-shrink: 0;
                padding: 3px 8px;
                font-family:
                  "SF Mono", Monaco, Inconsolata, "Roboto Mono", monospace;
                font-size: 11px;
                font-weight: 500;
                white-space: nowrap;
                border-radius: 3px;
              }

              .permission-name {
                overflow: hidden;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                text-overflow: ellipsis;
                white-space: nowrap;
              }

              .permission-desc {
                margin-left: 6px;
                font-size: 12px;
                color: #9ca3af;
              }
            }

            .permission-actions {
              display: flex;
              flex-shrink: 0;
              gap: 4px;
              opacity: 0;
              transition: opacity 0.2s ease;

              .el-button {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 24px;
                height: 24px;
                padding: 0;
                border-radius: 4px;

                .el-icon {
                  margin: 0;
                  font-size: 14px;
                }

                &:hover {
                  transform: scale(1.1);
                }
              }
            }

            &:hover .permission-actions {
              opacity: 1;
            }
          }
        }
      }

      .no-permissions {
        padding: 24px;
        text-align: center;
        background: #fafafa;
        border-top: 1px solid #f0f0f0;

        .empty-text {
          font-size: 13px;
          font-style: italic;
          color: #9ca3af;
        }
      }
    }
  }

  // API分配相关样式
  .assign-api-form {
    width: 100%;

    .permission-info-card {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      padding: 16px;
      margin-bottom: 20px;
      background-color: #f8f9fb;
      border-radius: 8px;
      box-shadow: 0 1px 4px rgb(0 0 0 / 5%);

      .permission-info-item {
        display: flex;
        align-items: center;
        min-width: 200px;

        .info-label {
          width: 70px;
          margin-right: 8px;
          font-size: 14px;
          color: #909399;
        }

        .permission-name-text {
          font-weight: 500;
          color: #303133;
        }

        .id-tag {
          font-family: SFMono-Regular, Consolas, monospace;
        }
      }
    }

    .permission-name-text {
      font-weight: 500;
      color: #303133;
    }

    .api-divider-title {
      font-size: 14px;
      font-weight: bold;
      color: #409eff;
    }

    .el-form-item {
      margin-bottom: 16px;
    }
  }

  .api-container {
    position: relative;
    width: 100%;
    margin-top: 16px;
    overflow: hidden;
    background-color: #fff;
    border: 1px solid #ebeef5;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgb(0 0 0 / 4%);

    :deep(.el-loading-mask) {
      background-color: rgb(255 255 255 / 90%);

      .el-loading-spinner {
        .circular {
          width: 32px;
          height: 32px;
        }

        .el-loading-text {
          margin-top: 8px;
          font-size: 14px;
          color: #409eff;
        }
      }
    }
  }

  .api-filter {
    display: flex;
    gap: 8px;
    padding: 16px;
    background-color: #f8f9fb;
    border-bottom: 1px solid #ebeef5;

    .el-input {
      flex: 1;
      min-width: 180px;

      :deep(.el-input__wrapper) {
        border-radius: 4px;
        box-shadow: 0 0 0 1px #dcdfe6 inset;

        &:hover {
          box-shadow: 0 0 0 1px #c0c4cc inset;
        }

        &.is-focus {
          box-shadow: 0 0 0 1px #409eff inset;
        }
      }
    }

    .el-select {
      width: 120px;

      :deep(.el-input__wrapper) {
        border-radius: 4px;
      }
    }
  }

  .api-selection {
    position: relative;
    height: 400px;
    overflow-y: auto;
    background-color: #fff;

    &::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }

    &::-webkit-scrollbar-thumb {
      background-color: #c0c4cc;
      border-radius: 3px;
    }

    &::-webkit-scrollbar-track {
      background-color: #f8f9fb;
    }
  }

  .api-list-header {
    position: sticky;
    top: 0;
    z-index: 1;
    display: flex;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #606266;
    background-color: #f8f9fb;
    border-bottom: 1px solid #ebeef5;

    .api-checkbox {
      display: flex;
      align-items: center;
      width: 40px;
    }

    .api-id {
      width: 80px;
      text-align: center;
    }

    .api-method {
      width: 80px;
      text-align: center;
    }

    .api-path {
      flex: 1;
      padding-left: 8px;
    }

    .api-status {
      width: 80px;
      text-align: center;
    }
  }

  .api-list {
    padding: 0;
  }

  .api-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #ebeef5;
    transition: all 0.2s;

    &:last-child {
      border-bottom: none;
    }

    &:hover {
      background-color: #f0f7ff;
    }

    &:nth-child(odd) {
      background-color: #fafafa;

      &:hover {
        background-color: #f0f7ff;
      }
    }

    .api-checkbox {
      width: 40px;
    }

    .api-id {
      width: 80px;
      font-family: SFMono-Regular, Consolas, monospace;
      font-size: 12px;
      color: #909399;
      text-align: center;
    }

    .api-method {
      width: 80px;
      text-align: center;

      .el-tag {
        min-width: 48px;
        height: 22px;
        padding: 0 6px;
        font-size: 11px;
        font-weight: 500;
        line-height: 20px;
        text-align: center;
        text-transform: uppercase;
        border-radius: 4px;

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
    }

    .api-path {
      flex: 1;
      padding-left: 8px;
      overflow: hidden;

      .api-path-text {
        overflow: hidden;
        font-family: SFMono-Regular, Consolas, monospace;
        font-size: 12px;
        color: #303133;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }

    .api-status {
      width: 80px;
      text-align: center;

      .status-indicator {
        display: flex;
        gap: 4px;
        align-items: center;
        justify-content: center;
      }

      .status-dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;

        &.success {
          background-color: #52c41a;
          box-shadow: 0 0 3px rgb(82 196 26 / 50%);
        }

        &.warning {
          background-color: #faad14;
          box-shadow: 0 0 3px rgb(250 173 20 / 50%);
        }

        &.info {
          background-color: #999;
          box-shadow: 0 0 3px rgb(153 153 153 / 50%);
        }
      }

      .status-text {
        font-size: 12px;
        color: #606266;
      }
    }
  }

  .api-info {
    display: flex;
    justify-content: space-between;
    padding: 12px 16px;
    font-size: 13px;
    color: #606266;
    background-color: #f8f9fb;
    border-top: 1px solid #ebeef5;

    strong {
      font-weight: 600;
      color: #409eff;
    }
  }
}

.el-table .el-tag.el-tag--primary {
  font-weight: bold;
  color: #409eff;
  background-color: #ecf5ff;
}

.el-table {
  overflow: hidden;
  border-radius: 4px;

  .cell-with-icon {
    display: flex;
    gap: 8px;
    align-items: center;

    .el-icon {
      color: #409eff;
    }
  }

  // 分类行特殊样式
  .el-table__row {
    &.el-table__row--level-0 {
      font-weight: bold;
      background-color: #f5f7fa;

      .cell-with-icon .el-icon {
        font-size: 16px;
      }

      td {
        background-color: #f5f7fa;
      }
    }
  }

  // 悬停效果
  &.el-table--enable-row-hover .el-table__body tr:hover > td {
    background-color: #f0f7ff;
  }

  // 表头样式
  th.el-table__cell {
    font-weight: bold;
    color: #606266;
    background-color: #f5f7fa !important;
  }
}
</style>
