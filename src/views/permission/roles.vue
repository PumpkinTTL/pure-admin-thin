<template>
  <div class="roles-container">
    <el-dialog
      v-model="showAddOrEditModal"
      :title="currentRole.id ? '编辑角色' : '新增角色'"
      width="500"
      :before-close="handleClose"
    >
      <el-form
        ref="roleFormRef"
        :model="currentRole"
        :rules="roleRules"
        label-width="100px"
      >
        <el-form-item label="角色名称" prop="name">
          <el-input v-model="currentRole.name" placeholder="请输入角色名称" />
        </el-form-item>
        <el-form-item label="角色标识" prop="iden">
          <el-input
            v-model="currentRole.iden"
            placeholder="请输入角色标识，如admin"
          />
        </el-form-item>
        <el-form-item label="显示顺序" prop="show_weight">
          <el-input-number
            v-model="currentRole.show_weight"
            :min="0"
            :max="999"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch
            v-model="currentRole.status"
            :active-value="1"
            :inactive-value="0"
          />
        </el-form-item>
        <el-form-item label="角色描述" prop="description">
          <el-input
            v-model="currentRole.description"
            type="textarea"
            placeholder="请输入角色描述"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="showAddOrEditModal = false">取消</el-button>
          <el-button type="primary" :loading="submitting" @click="submitRole">
            确认
          </el-button>
        </div>
      </template>
    </el-dialog>

    <el-dialog
      v-model="showPermissionModal"
      title="分配权限"
      width="850"
      :before-close="handleClose"
      class="permission-dialog"
      center
      align-center
    >
      <el-form v-if="currentRole.id" label-width="100px">
        <div class="role-info-box">
          <div class="role-info-header">
            <div class="header-left">
              <el-icon>
                <User />
              </el-icon>
              <span>角色信息</span>
            </div>
            <div v-if="currentRole.status !== undefined" class="header-right">
              <el-tag
                size="small"
                :type="currentRole.status === 1 ? 'success' : 'info'"
              >
                {{ currentRole.status === 1 ? "活跃" : "禁用" }}
              </el-tag>
            </div>
          </div>
          <div class="role-info-content">
            <div class="info-item">
              <span class="label">角色名称：</span>
              <el-tag
                size="default"
                :type="currentRole.iden === 'admin' ? 'danger' : 'primary'"
                class="role-tag"
              >
                <el-icon>
                  <User />
                </el-icon>
                <span>{{ currentRole.name }}</span>
              </el-tag>
            </div>
            <div class="info-item">
              <span class="label">角色标识：</span>
              <el-tag
                size="default"
                effect="light"
                :type="currentRole.iden === 'admin' ? 'danger' : 'success'"
                class="role-tag"
              >
                <el-icon>
                  <Key />
                </el-icon>
                <span>{{ currentRole.iden }}</span>
              </el-tag>
            </div>
            <div v-if="currentRole.description" class="info-item">
              <span class="label">角色描述：</span>
              <span class="description">{{ currentRole.description }}</span>
            </div>
          </div>
        </div>

        <div v-loading="permissionsLoading" class="permission-box">
          <div class="permission-header">
            <div class="left">
              <el-icon>
                <Setting />
              </el-icon>
              <span>权限分配</span>
              <span class="subtitle">
                - 当前拥有权限：{{ totalSelectedPermissions }} 项
              </span>
            </div>
            <div class="right">
              <div class="search-box">
                <el-input
                  v-model="searchQuery"
                  placeholder="搜索权限（支持权限标识、说明搜索）..."
                  :prefix-icon="Search"
                  clearable
                  size="default"
                />
              </div>
            </div>
          </div>

          <div class="permission-content">
            <div class="tabs-wrapper">
              <el-tabs
                v-model="activePermissionTab"
                type="border-card"
                class="permission-tabs"
                @tab-click="handleTabClick"
              >
                <el-tab-pane
                  v-for="(permissions, category) in permissionsData"
                  :key="category"
                  :label="categoryLabels[category] || category.toUpperCase()"
                  :name="category"
                >
                  <template #label>
                    <div class="tab-label">
                      <el-icon>
                        <component :is="getCategoryIcon(category)" />
                      </el-icon>
                      <span>
                        {{ categoryLabels[category] || category.toUpperCase() }}
                      </span>
                      <el-badge
                        v-if="getSelectedCountByCategory(category) > 0"
                        :value="getSelectedCountByCategory(category)"
                        class="tab-badge"
                        type="info"
                      />
                    </div>
                  </template>
                  <div class="permission-list">
                    <div class="category-actions">
                      <div class="category-info">
                        {{ categoryLabels[category] || category.toUpperCase() }}
                        分组共 {{ permissions.length }} 个权限项
                      </div>
                      <div class="actions">
                        <el-button
                          type="primary"
                          size="small"
                          link
                          @click.stop="selectCategoryAll(category)"
                        >
                          全选当前组
                        </el-button>
                        <el-button
                          type="warning"
                          size="small"
                          link
                          @click.stop="unselectCategoryAll(category)"
                        >
                          取消当前组
                        </el-button>
                      </div>
                    </div>
                    <div class="tree-container">
                      <el-tree
                        :ref="
                          el => {
                            if (el) setTreeRef(category, el);
                          }
                        "
                        :data="permissions"
                        :props="{
                          label: 'name',
                          children: 'children'
                        }"
                        show-checkbox
                        node-key="id"
                        default-expand-all
                        :default-checked-keys="
                          selectedPermissionsByCategory[category] || []
                        "
                        highlight-current
                        check-strictly
                        class="permission-tree"
                        :filter-node-method="filterNode"
                      >
                        <template #default="{ data }">
                          <div
                            class="tree-node"
                            :class="{
                              'node-selected': isNodeSelected(category, data.id)
                            }"
                          >
                            <div class="node-content">
                              <div class="node-main">
                                <el-tag
                                  v-if="data.iden"
                                  size="small"
                                  type="info"
                                  class="node-iden"
                                >
                                  {{ data.iden }}
                                </el-tag>
                                <span class="node-label">{{ data.name }}</span>
                              </div>
                              <div class="node-meta">
                                <span v-if="data.id" class="node-id">
                                  ID: {{ data.id }}
                                </span>
                              </div>
                            </div>
                          </div>
                        </template>
                      </el-tree>
                    </div>
                  </div>
                </el-tab-pane>
              </el-tabs>
            </div>
          </div>
        </div>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <div class="footer-info">
            <el-icon>
              <InfoFilled />
            </el-icon>
            <span>已选择 {{ totalSelectedPermissions }} 项权限</span>
          </div>
          <div class="footer-buttons">
            <el-button :icon="Close" plain @click="showPermissionModal = false">
              取消
            </el-button>
            <el-button
              type="primary"
              :loading="submitting"
              :icon="Check"
              @click="submitPermissions"
            >
              保存权限配置
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <el-card class="main-card" shadow="never">
      <template #header>
        <el-row :gutter="16">
          <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="2">
            <span class="header-title">角色管理</span>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-input
              v-model="searchForm.name"
              placeholder="角色名称"
              clearable
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-input
              v-model="searchForm.iden"
              placeholder="角色标识"
              clearable
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-select
              v-model="searchForm.query_deleted"
              placeholder="删除状态"
              style="width: 100%"
            >
              <el-option label="全部" value="" />
              <el-option label="未删除" value="not_deleted" />
              <el-option label="已删除" value="only_deleted" />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
            <el-select
              v-model="searchForm.status"
              placeholder="角色状态"
              style="width: 100%"
            >
              <el-option label="全部" value="" />
              <el-option label="启用" :value="1" />
              <el-option label="禁用" :value="0" />
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
            新增角色
          </el-button>
          <el-button
            type="danger"
            :icon="Delete"
            :disabled="selectedRoles.length === 0"
            size="small"
            @click="handleBatchDelete"
          >
            批量删除
          </el-button>
        </div>
        <div class="right-tools">
          <el-button :icon="Printer" circle title="打印" size="small" />
          <el-button :icon="Upload" circle title="导出" size="small" />
        </div>
      </div>

      <el-divider content-position="left">角色列表</el-divider>

      <el-table
        v-loading="tableLoading"
        border
        :data="rolesList"
        style="width: 100%"
        :header-cell-style="headerCellStyle"
        size="small"
        class="roles-table"
        @selection-change="handleSelectionChange"
        @sort-change="handleSortChange"
      >
        <el-table-column type="selection" width="50" align="center" />
        <el-table-column
          show-overflow-tooltip
          prop="id"
          label="ID"
          sortable="custom"
          min-width="80"
          :sort-orders="['ascending', 'descending', null]"
        >
          <template #default="{ row }">
            <el-tag
              size="small"
              type="info"
              effect="plain"
              round
              class="id-tag"
            >
              {{ row.id }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          label="角色名称"
          prop="name"
          sortable="custom"
          min-width="150"
        >
          <template #default="{ row }">
            <div class="cell-with-icon">
              <el-icon>
                <User />
              </el-icon>
              <span>{{ row.name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          label="角色标识"
          prop="iden"
          min-width="120"
        >
          <template #default="{ row }">
            <el-tag
              :type="row.iden === 'admin' ? 'danger' : 'success'"
              size="small"
              effect="plain"
            >
              {{ row.iden }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          label="显示顺序"
          prop="show_weight"
          sortable="custom"
          min-width="100"
        >
          <template #default="{ row }">
            <el-tag size="small" type="warning" effect="plain">
              {{ row.show_weight }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          label="状态"
          prop="status"
          sortable="custom"
          min-width="90"
        >
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              inline-prompt
              active-text="启"
              inactive-text="禁"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          label="创建时间"
          prop="create_time"
          sortable="custom"
          min-width="160"
        />
        <el-table-column
          show-overflow-tooltip
          label="描述"
          prop="description"
          min-width="200"
        />
        <el-table-column
          fixed="right"
          align="center"
          header-align="center"
          label="操作"
          width="200"
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
                  <span>编辑</span>
                </el-button>
                <el-button
                  type="success"
                  link
                  size="small"
                  @click="handleAssignPermission(row)"
                >
                  <el-icon>
                    <SetUp />
                  </el-icon>
                  <span>权限</span>
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
                  <span>删除</span>
                </el-button>
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
                  <span>恢复</span>
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
                  <span>删除</span>
                </el-button>
              </template>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pageConfig.currentPage"
        v-model:page-size="pageConfig.pageSize"
        style=" justify-content: flex-end;margin-top: 20px"
        :page-sizes="[5, 10, 20, 30]"
        :background="true"
        layout="total, sizes, prev, pager, next, jumper"
        :total="pageConfig.total"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </el-card>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "roles"
});

import { ElMessageBox, ElNotification } from "element-plus";
import { ref, reactive, onMounted, computed, h, watch } from "vue";
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
  Setting,
  Check,
  Close,
  InfoFilled,
  SetUp,
  Key,
  Link,
  Document,
  Menu as MenuIcon,
  Operation,
  Promotion,
  Goods,
  Share
} from "@element-plus/icons-vue";
import { message } from "@/utils/message";
import {
  getRoleList,
  getRoleDetail,
  createRole,
  updateRole,
  deleteRole,
  batchDeleteRoles,
  restoreRole,
  getAllPermissions,
  getPermissionTree,
  assignRolePermissions,
  type RoleInfo,
  type RoleListParams,
  type PermissionInfo
} from "@/api/role";
import { generateSerialNumbers } from "@/utils/dataUtil";

// 响应式数据
const searchForm = reactive<Partial<RoleListParams>>({
  name: "",
  iden: "",
  query_deleted: "" as any,
  status: undefined,
  order_field: undefined,
  order_type: undefined
});

const buttonSize = ref<"default" | "small" | "large">("small");
const showAddOrEditModal = ref(false);
const showPermissionModal = ref(false);
const currentRole = ref<any>({});
const selectedRoles = ref<Array<any>>([]);
const tableLoading = ref(false);
const submitting = ref(false);
const roleFormRef = ref<any>(null);
const permissionTreeRef = ref<any>(null);

// 权限相关
const activePermissionTab = ref("user");
const permissionsData = ref<Record<string, any[]>>({});
const permissionsLoading = ref(false);
const selectedPermissions = ref<number[]>([]);
const searchQuery = ref("");

// 表格样式
const headerCellStyle = {
  backgroundColor: "#f5f7fa",
  color: "#606266",
  fontWeight: "600",
  textAlign: "center" as const,
  height: "40px",
  padding: "6px 0"
};

// 使用Map存储每个分类的树引用
const treeRefs = ref<Map<string, any>>(new Map());

// 存储每个分类的选择状态
const selectedPermissionsByCategory = ref<Record<string, number[]>>({});

// 计算总共选择的权限数量
const totalSelectedPermissions = computed(() => {
  return Object.values(selectedPermissionsByCategory.value).reduce(
    (total, arr) => total + arr.length,
    0
  );
});

// 获取指定分类的已选择权限数量
const getSelectedCountByCategory = (category: string) => {
  return selectedPermissionsByCategory.value[category]?.length || 0;
};

// 设置树引用
const setTreeRef = (category: string, el: any) => {
  treeRefs.value.set(category, el);
};

// 获取当前激活标签页的树组件
const getCurrentTreeRef = () => {
  const category = activePermissionTab.value;
  return treeRefs.value.get(category);
};

// 监听搜索输入，过滤树节点
watch(searchQuery, val => {
  const currentTree = getCurrentTreeRef();
  if (currentTree) {
    currentTree.filter(val);
  }
});

// 过滤树节点的方法
const filterNode = (value: string, data: any) => {
  if (!value) return true;
  return data.name.toLowerCase().includes(value.toLowerCase());
};

// 处理标签切换，保存当前标签的选中状态
const handleTabClick = () => {
  // 保存上一个标签的选中状态
  saveCurrentTabSelections();
};

// 保存当前标签页的选中状态
const saveCurrentTabSelections = () => {
  const lastCategory = activePermissionTab.value;
  const treeInstance = treeRefs.value.get(lastCategory);

  if (treeInstance) {
    const checkedKeys = treeInstance.getCheckedKeys();
    selectedPermissionsByCategory.value[lastCategory] = [...checkedKeys];
  }
};

// 分类中文标签
const categoryLabels: Record<string, string> = {
  user: "用户权限",
  open: "开放接口",
  product: "商品管理",
  article: "文章管理",
  system: "系统管理",
  menu: "菜单权限"
};

const pageConfig = ref({
  currentPage: 1,
  pageSize: 5, // 默认每页显示5条数据
  total: 0
});

const rolesList = ref<RoleInfo[]>([]);

// 表单校验规则
const roleRules = {
  name: [
    { required: true, message: "请输入角色名称", trigger: "blur" },
    { min: 2, max: 20, message: "长度应为 2 到 20 个字符", trigger: "blur" }
  ],
  iden: [
    { required: true, message: "请输入角色标识", trigger: "blur" },
    {
      pattern: /^[a-zA-Z0-9_]+$/,
      message: "角色标识只能包含字母、数字和下划线",
      trigger: "blur"
    }
  ]
};

// 方法定义
// 获取角色列表
const fetchRoleList = async () => {
  tableLoading.value = true;
  try {
    const params = {
      ...searchForm,
      page: pageConfig.value.currentPage,
      page_size: pageConfig.value.pageSize
    };

    const res = await getRoleList(params);
    if (res.code === 200) {
      rolesList.value = res.data.list || [];
      pageConfig.value.total =
        res.data.pagination?.total || rolesList.value.length;
    } else {
      message(res.msg || "获取角色列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取角色列表错误:", error);
    message("获取角色列表失败，请稍后重试", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 加载权限树数据
const loadPermissionsTree = async () => {
  permissionsLoading.value = true;
  try {
    // 从API获取权限树
    const res = await getPermissionTree();
    if (res.code === 200) {
      permissionsData.value = res.data || {};

      // 动态添加新的权限分类标签
      Object.keys(permissionsData.value).forEach(category => {
        if (!categoryLabels[category]) {
          categoryLabels[category] =
            category.charAt(0).toUpperCase() + category.slice(1);
        }
      });

      // 如果没有选中的标签或标签不在返回的数据中，设置默认标签
      if (
        !activePermissionTab.value ||
        !permissionsData.value[activePermissionTab.value]
      ) {
        const firstCategory = Object.keys(permissionsData.value)[0];
        if (firstCategory) {
          activePermissionTab.value = firstCategory;
        }
      }
    } else {
      message(res.msg || "获取权限树失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取权限树错误:", error);
    message("获取权限树失败，请稍后重试", { type: "error" });
  } finally {
    permissionsLoading.value = false;
  }
};

// 获取角色详情及权限
const fetchRoleDetail = async (id: number) => {
  permissionsLoading.value = true;
  try {
    const res = await getRoleDetail(id);
    if (res.code === 200) {
      const role = res.data;

      // 清空所有分类的选中状态
      selectedPermissionsByCategory.value = {};

      // 处理已选权限
      if (role.permissions && role.permissions.length > 0) {
        selectedPermissions.value = role.permissions.map(item => item.id);

        // 初始化所有分类的选中状态
        Object.keys(permissionsData.value).forEach(category => {
          selectedPermissionsByCategory.value[category] = [];
        });

        // 分配权限到各个分类
        role.permissions.forEach(permission => {
          // 找到权限所属的分类
          for (const [category, permissions] of Object.entries(
            permissionsData.value
          )) {
            const findPermissionInCategory = items => {
              for (const item of items) {
                if (item.id === permission.id) {
                  return true;
                }
                if (item.children && item.children.length > 0) {
                  if (findPermissionInCategory(item.children)) {
                    return true;
                  }
                }
              }
              return false;
            };

            if (findPermissionInCategory(permissions)) {
              if (!selectedPermissionsByCategory.value[category]) {
                selectedPermissionsByCategory.value[category] = [];
              }
              selectedPermissionsByCategory.value[category].push(permission.id);
            }
          }
        });
      } else {
        selectedPermissions.value = [];
        // 初始化所有分类的选中状态为空数组
        Object.keys(permissionsData.value).forEach(category => {
          selectedPermissionsByCategory.value[category] = [];
        });
      }
    } else {
      message(res.msg || "获取角色详情失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取角色详情错误:", error);
    message("获取角色详情失败，请稍后重试", { type: "error" });
  } finally {
    permissionsLoading.value = false;
  }
};

// 搜索
const handleSearch = () => {
  pageConfig.value.currentPage = 1;
  fetchRoleList();
};

// 重置搜索表单
const resetSearchForm = () => {
  searchForm.name = "";
  searchForm.iden = "";
  searchForm.query_deleted = "" as any;
  searchForm.status = undefined;
  searchForm.order_field = undefined;
  searchForm.order_type = undefined;
  pageConfig.value.currentPage = 1;
  fetchRoleList();
};

// 处理表格排序
const handleSortChange = ({ prop, order }: { prop: string; order: string }) => {
  if (order === "ascending") {
    searchForm.order_field = prop as any;
    searchForm.order_type = "asc";
  } else if (order === "descending") {
    searchForm.order_field = prop as any;
    searchForm.order_type = "desc";
  } else {
    searchForm.order_field = undefined;
    searchForm.order_type = undefined;
  }
  fetchRoleList();
};

// 添加角色
const handleAdd = () => {
  currentRole.value = {
    name: "",
    iden: "",
    description: "",
    status: 1,
    show_weight: 0
  };
  showAddOrEditModal.value = true;
};

// 编辑角色
const handleEdit = (row: RoleInfo) => {
  currentRole.value = { ...row };
  showAddOrEditModal.value = true;
};

// 分配权限
const handleAssignPermission = async (row: RoleInfo) => {
  // 清空之前的缓存数据
  clearPermissionCache();

  currentRole.value = { ...row };
  showPermissionModal.value = true;

  // 加载权限数据
  await loadPermissionsTree();

  // 获取角色的权限
  await fetchRoleDetail(row.id);
};

// 提交权限分配
const submitPermissions = async () => {
  if (!currentRole.value.id) return;

  // 先保存当前标签的选择状态
  saveCurrentTabSelections();

  submitting.value = true;
  try {
    // 收集所有标签页的选中权限
    const allCheckedPermissions: number[] = [];
    Object.values(selectedPermissionsByCategory.value).forEach(permissions => {
      if (permissions && permissions.length) {
        permissions.forEach(perm => {
          if (!allCheckedPermissions.includes(perm)) {
            allCheckedPermissions.push(perm);
          }
        });
      }
    });

    // 创建权限关联数据
    const currentTime = new Date()
      .toISOString()
      .replace("T", " ")
      .substring(0, 19);
    const rolePermissions = allCheckedPermissions.map(permissionId => {
      return {
        id: Number(generateSerialNumbers(1, 5)),
        role_id: currentRole.value.id,
        permission_id: permissionId,
        create_time: currentTime,
        updated_time: currentTime
      };
    });

    // 打印创建的关联数据
    console.log("创建的角色-权限关联数据:", rolePermissions);
    console.log("选中的权限ID:", allCheckedPermissions);

    // 调用API保存权限
    const res = await assignRolePermissions(
      currentRole.value.id,
      allCheckedPermissions
    );
    if (res.code === 200) {
      message("权限分配成功", { type: "success" });
      // 清空缓存并关闭对话框
      clearPermissionCache();
      showPermissionModal.value = false;
      // 刷新角色列表
      await fetchRoleList();
    } else {
      message(res.msg || "权限分配失败", { type: "error" });
    }
  } catch (error) {
    console.error("权限分配错误:", error);
    message("权限分配失败，请稍后重试", { type: "error" });
  } finally {
    submitting.value = false;
  }
};

// 提交角色
const submitRole = async () => {
  if (!roleFormRef.value) return;

  await roleFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    submitting.value = true;
    try {
      let res;

      if (currentRole.value.id) {
        // 更新角色
        res = await updateRole(currentRole.value);
      } else {
        // 创建角色
        res = await createRole(currentRole.value);
      }

      if (res.code === 200) {
        message(currentRole.value.id ? "更新角色成功" : "创建角色成功", {
          type: "success"
        });
        showAddOrEditModal.value = false;
        fetchRoleList();
      } else {
        message(
          res.msg || (currentRole.value.id ? "更新角色失败" : "创建角色失败"),
          { type: "error" }
        );
      }
    } catch (error) {
      console.error(
        currentRole.value.id ? "更新角色错误:" : "创建角色错误:",
        error
      );
      message(
        currentRole.value.id
          ? "更新角色失败，请稍后重试"
          : "创建角色失败，请稍后重试",
        { type: "error" }
      );
    } finally {
      submitting.value = false;
    }
  });
};

// 删除角色
const handleDelete = (row: RoleInfo) => {
  // 创建响应式变量用于存储复选框状态
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h("div", null, [
      h("p", null, `确定要删除角色 "${row.name}" 吗？`),
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
    title: "删除确认",
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: "确定删除",
    cancelButtonText: "取消",
    type: "warning",
    beforeClose: (action, instance, done) => {
      if (action === "confirm") {
        instance.confirmButtonLoading = true;
        deleteRole(row.id, isRealDelete.value)
          .then(res => {
            if (res.code === 200) {
              message(
                isRealDelete.value ? "永久删除角色成功" : "删除角色成功",
                { type: "success" }
              );
              fetchRoleList();
            } else {
              message(res.msg || "删除角色失败", { type: "error" });
            }
          })
          .catch(() => {
            message("删除角色失败，请稍后重试", { type: "error" });
          })
          .finally(() => {
            instance.confirmButtonLoading = false;
            done();
          });
      } else {
        done();
      }
    }
  });
};

// 对话框关闭处理
const handleClose = (done: () => void) => {
  ElMessageBox.confirm("确定要关闭吗? 未保存的数据将丢失", "提示", {
    confirmButtonText: "确定",
    cancelButtonText: "取消"
  })
    .then(() => {
      // 清空权限选择缓存
      clearPermissionCache();
      done();
    })
    .catch(() => {});
};

// 清空权限缓存
const clearPermissionCache = () => {
  selectedPermissionsByCategory.value = {};
  selectedPermissions.value = [];
  searchQuery.value = "";
  activePermissionTab.value = "user";
};

// 状态切换处理
const handleStatusChange = async (row: RoleInfo) => {
  try {
    const res = await updateRole({
      id: row.id,
      status: row.status
    });

    if (res.code === 200) {
      const action = row.status === 1 ? "启用" : "禁用";
      message(`已${action}角色 ${row.name}`, { type: "success" });
    } else {
      // 恢复原状态
      row.status = row.status === 1 ? 0 : 1;
      message(res.msg || "更新角色状态失败", { type: "error" });
    }
  } catch (error) {
    // 恢复原状态
    row.status = row.status === 1 ? 0 : 1;
    console.error("更新角色状态错误:", error);
    message("更新角色状态失败，请稍后重试", { type: "error" });
  }
};

// 恢复角色
const handleRestore = async (row: RoleInfo) => {
  try {
    const res = await restoreRole(row.id);
    if (res.code === 200) {
      message("恢复角色成功", { type: "success" });
      fetchRoleList();
    } else {
      message(res.msg || "恢复角色失败", { type: "error" });
    }
  } catch (error) {
    console.error("恢复角色错误:", error);
    message("恢复角色失败，请稍后重试", { type: "error" });
  }
};

// 处理分页大小变化
const handleSizeChange = (val: number) => {
  pageConfig.value.pageSize = val;
  fetchRoleList();
};

// 处理页码变化
const handleCurrentChange = (val: number) => {
  pageConfig.value.currentPage = val;
  fetchRoleList();
};

// 表格选择变化
const handleSelectionChange = (selection: Array<any>) => {
  selectedRoles.value = selection;
};

// 批量删除处理
const handleBatchDelete = () => {
  if (selectedRoles.value.length === 0) {
    message("请至少选择一个角色", { type: "warning" });
    return;
  }

  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h("div", null, [
      h("p", null, `确定要删除选中的 ${selectedRoles.value.length} 个角色吗？`),
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
        const ids = selectedRoles.value.map(item => item.id);
        batchDeleteRoles(ids, isRealDelete.value)
          .then(res => {
            if (res.code === 200) {
              message(
                isRealDelete.value ? "永久删除角色成功" : "批量删除角色成功",
                { type: "success" }
              );
              fetchRoleList();
              selectedRoles.value = [];
            } else {
              message(res.msg || "批量删除角色失败", { type: "error" });
            }
          })
          .catch(() => {
            message("批量删除角色失败，请稍后重试", { type: "error" });
          })
          .finally(() => {
            instance.confirmButtonLoading = false;
            done();
          });
      } else {
        done();
      }
    }
  });
};

// 根据分类返回对应的图标
const getCategoryIcon = (category: string) => {
  const iconMap: Record<string, any> = {
    user: User,
    menu: MenuIcon,
    system: Setting,
    open: Share,
    product: Goods,
    article: Document,
    default: Operation
  };
  return iconMap[category] || iconMap.default;
};

// 根据权限类型返回对应的图标
const getPermissionIcon = (type: string) => {
  const iconMap: Record<string, any> = {
    menu: MenuIcon,
    api: Link,
    button: Promotion,
    user: User,
    default: Operation
  };
  return iconMap[type] || iconMap.default;
};

// 权限操作函数
const checkAllPermissions = () => {
  try {
    // 获取当前激活标签页的树组件
    const treeInstance = getCurrentTreeRef();
    if (!treeInstance) return;

    // 获取当前标签页的权限数据
    const currentTabPermissions =
      permissionsData.value[activePermissionTab.value] || [];

    // 收集所有节点的ID
    const allNodeIds: number[] = [];
    const collectIds = (nodes: any[]) => {
      nodes.forEach(node => {
        allNodeIds.push(node.id);
        if (node.children && node.children.length) {
          collectIds(node.children);
        }
      });
    };
    collectIds(currentTabPermissions);

    // 全选所有节点
    treeInstance.setCheckedKeys(allNodeIds);

    // 保存选中状态
    saveCurrentTabSelections();
  } catch (error) {
    console.error("全选权限错误:", error);
  }
};

const uncheckAllPermissions = () => {
  try {
    // 获取当前激活标签页的树组件
    const treeInstance = getCurrentTreeRef();
    if (!treeInstance) return;

    // 清除所有选择
    treeInstance.setCheckedKeys([]);

    // 保存选中状态
    saveCurrentTabSelections();
  } catch (error) {
    console.error("取消全选权限错误:", error);
  }
};

// 新增的权限分配相关状态
const activeCollapse = ref<string[]>(["user"]);
const showOnlySelected = ref(false);

// 获取所有权限总数
const getTotalPermissionsCount = () => {
  let total = 0;
  for (const category in permissionsData.value) {
    const countPermissions = items => {
      let count = items.length;
      for (const item of items) {
        if (item.children && item.children.length) {
          count += countPermissions(item.children) - 1; // 减去父节点避免重复计数
        }
      }
      return count;
    };
    total += countPermissions(permissionsData.value[category]);
  }
  return total;
};

// 获取权限覆盖百分比
const getPermissionPercentage = () => {
  const total = getTotalPermissionsCount();
  if (total === 0) return 0;
  return Math.round((totalSelectedPermissions.value / total) * 100);
};

// 获取进度条颜色
const getProgressColor = () => {
  const percentage = getPermissionPercentage();
  if (percentage < 30) return "#F56C6C";
  if (percentage < 70) return "#E6A23C";
  return "#67C23A";
};

// 判断节点是否被选中
const isNodeSelected = (category: string, id: number) => {
  return selectedPermissionsByCategory.value[category]?.includes(id);
};

// 过滤权限树，根据是否仅显示已选项
const filterPermissions = permissions => {
  if (!showOnlySelected.value) return permissions;

  // 过滤只显示已选择的权限
  const filterSelectedPermissions = (items, category) => {
    return items.filter(item => {
      // 检查当前节点是否选中
      const isSelected = selectedPermissionsByCategory.value[
        category
      ]?.includes(item.id);

      // 如果有子节点，递归过滤
      let filteredChildren = [];
      if (item.children && item.children.length) {
        filteredChildren = filterSelectedPermissions(item.children, category);
      }

      // 如果当前节点选中或子节点有选中的，则显示
      const shouldShow = isSelected || filteredChildren.length > 0;

      // 如果需要保留，并且有过滤后的子节点，则更新子节点
      if (shouldShow && filteredChildren.length > 0) {
        return { ...item, children: filteredChildren };
      }

      return shouldShow ? item : null;
    });
  };

  return filterSelectedPermissions(permissions, activeCollapse.value[0]);
};

// 全选某个分类
const selectCategoryAll = category => {
  const treeInstance = treeRefs.value.get(category);
  if (!treeInstance) return;

  // 收集所有节点的ID
  const allNodeIds: number[] = [];
  const collectIds = (nodes: any[]) => {
    nodes.forEach(node => {
      allNodeIds.push(node.id);
      if (node.children && node.children.length) {
        collectIds(node.children);
      }
    });
  };
  collectIds(permissionsData.value[category]);

  // 全选所有节点
  treeInstance.setCheckedKeys(allNodeIds);

  // 保存选中状态
  selectedPermissionsByCategory.value[category] = [...allNodeIds];

  // 阻止事件冒泡，避免折叠面板收起
  event?.stopPropagation();
};

// 取消选择某个分类
const unselectCategoryAll = category => {
  const treeInstance = treeRefs.value.get(category);
  if (!treeInstance) return;

  // 清除所有选择
  treeInstance.setCheckedKeys([]);

  // 保存选中状态
  selectedPermissionsByCategory.value[category] = [];

  // 阻止事件冒泡，避免折叠面板收起
  event?.stopPropagation();
};

onMounted(() => {
  fetchRoleList();
});
</script>

<style lang="scss" scoped>
.roles-container {
  padding: 20px;

  @media (width >= 1920px) {
    .el-col {
      margin-bottom: 0;
    }
  }

  @media (width <= 767px) {
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

      span {
        font-size: 12px;
      }

      &.el-button--primary {
        --el-button-text-color: var(--el-color-primary);
        --el-button-hover-text-color: var(--el-color-primary-light-3);
      }

      &.el-button--success {
        --el-button-text-color: var(--el-color-success);
        --el-button-hover-text-color: var(--el-color-success-light-3);
      }

      &.el-button--danger {
        --el-button-text-color: var(--el-color-danger);
        --el-button-hover-text-color: var(--el-color-danger-light-3);
      }

      &.el-button--warning {
        --el-button-text-color: var(--el-color-warning);
        --el-button-hover-text-color: var(--el-color-warning-light-3);
      }
    }
  }

  .roles-table {
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

    :deep(.el-tag) {
      &.el-tag--danger {
        border: 1px solid rgb(245 108 108 / 20%);
      }

      &.el-tag--success {
        border: 1px solid rgb(103 194 58 / 20%);
      }
    }
  }

  .el-col {
    margin-bottom: 12px;
  }
}

.permission-dialog {
  :deep(.el-dialog__header) {
    padding: 16px 20px;
    color: #fff;
    background: linear-gradient(135deg, #1989fa, #5ab1ef);

    .el-dialog__title {
      font-size: 18px;
      font-weight: bold;
      color: #fff;
    }

    .el-dialog__close {
      color: #fff;

      &:hover {
        color: rgb(255 255 255 / 80%);
        background: rgb(255 255 255 / 20%);
      }
    }
  }

  :deep(.el-dialog__body) {
    max-height: 75vh;
    padding: 20px;
    overflow: auto;
  }

  :deep(.el-dialog__footer) {
    padding: 12px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #f0f0f0;
  }

  .role-info-box {
    margin-bottom: 16px;
    overflow: hidden;
    border: 1px solid #ebeef5;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgb(0 0 0 / 5%);

    .role-info-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      background-color: #f5f7fa;

      .header-left {
        display: flex;
        align-items: center;

        .el-icon {
          margin-right: 6px;
          font-size: 16px;
          color: #409eff;
        }

        span {
          font-size: 14px;
          font-weight: 500;
        }
      }
    }

    .role-info-content {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      padding: 12px 16px;
      background-color: #fff;

      .info-item {
        display: flex;
        align-items: center;

        .label {
          margin-right: 10px;
          font-size: 13px;
          color: #606266;
        }

        .description {
          max-width: 500px;
          overflow: hidden;
          font-size: 13px;
          color: #909399;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
      }
    }

    // 修复tag内icon对齐问题
    :deep(.el-tag) {
      display: inline-flex !important;
      align-items: center !important;

      .el-tag__content {
        display: inline-flex;
        gap: 4px;
        align-items: center;
      }
    }
  }

  .permission-box {
    overflow: hidden;
    border: 1px solid #ebeef5;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgb(0 0 0 / 5%);

    .permission-header {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      background-color: #f5f7fa;

      .left {
        display: flex;
        align-items: center;

        .el-icon {
          margin-right: 6px;
          font-size: 16px;
          color: #409eff;
        }

        span {
          font-size: 14px;
          font-weight: 500;
        }

        .subtitle {
          padding: 2px 8px;
          margin-left: 8px;
          font-size: 12px;
          font-weight: normal;
          color: #606266;
          background-color: #f1f7fe;
          border-radius: 12px;
        }
      }

      .right {
        display: flex;
        gap: 8px;
        align-items: center;

        .search-box {
          width: 380px;

          :deep(.el-input__inner) {
            font-size: 14px;
          }
        }
      }
    }

    .permission-content {
      background-color: #fff;
    }
  }

  .tabs-wrapper {
    overflow: hidden;
    background-color: #fff;
    border-radius: 4px;
  }

  .permission-tabs {
    :deep(.el-tabs__header) {
      margin: 0;
      background-color: #f8f9fa;

      .el-tabs__nav-wrap::after {
        display: none;
      }

      .el-tabs__nav {
        border: none;
      }

      .el-tabs__item {
        height: 36px;
        padding: 0 16px;
        margin-right: 4px;
        font-size: 13px;
        line-height: 36px;
        border: none;
        border-radius: 4px 4px 0 0;
        transition: all 0.3s;

        &.is-active {
          font-weight: 500;
          color: #409eff;
          background: #fff;
          box-shadow: 0 -2px 0 #409eff inset;
        }

        &:not(.is-active):hover {
          color: #409eff;
          background: rgb(64 158 255 / 8%);
        }
      }
    }

    :deep(.el-tabs__content) {
      padding: 0;
    }
  }

  .tab-label {
    display: inline-flex;
    gap: 5px;
    align-items: center;

    .el-icon {
      font-size: 15px;
    }

    .tab-badge {
      margin-left: 4px;

      :deep(.el-badge__content) {
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        line-height: 16px;
        transform: scale(0.8);
      }
    }
  }

  .permission-list {
    padding: 16px;

    .category-actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;

      .category-info {
        padding: 6px 10px;
        font-size: 13px;
        color: #606266;
        background-color: #f5f7fa;
        border-radius: 4px;
      }

      .actions {
        display: flex;
        gap: 8px;

        .el-button {
          padding: 4px 12px;
          font-size: 12px;
        }
      }
    }

    .tree-container {
      max-height: 450px;
      overflow: auto;
    }
  }

  .permission-tree {
    padding: 8px;

    :deep(.el-tree-node__content) {
      height: 36px;
      margin: 2px 0;
      border-radius: 4px;
      transition: all 0.2s;

      &:hover {
        background-color: #f5f7fa;
      }
    }

    :deep(.is-checked) .tree-node .node-label {
      font-weight: 500;
      color: #409eff;
    }

    .tree-node {
      display: flex;
      align-items: center;
      width: 100%;
      padding: 4px 0;

      &.node-selected {
        .node-label {
          font-weight: 500;
          color: #409eff;
        }
      }

      .node-content {
        display: flex;
        flex: 1;
        gap: 12px;
        align-items: center;
        justify-content: space-between;

        .node-main {
          display: flex;
          flex: 1;
          gap: 10px;
          align-items: center;

          .node-iden {
            min-width: 140px;
            font-family: "Courier New", monospace;
            font-size: 12px;
            color: #409eff;
            text-align: center;
            background-color: #ecf5ff;
            border-color: #d9ecff;
          }

          .node-label {
            font-size: 13px;
            line-height: 1.5;
            color: #606266;
          }
        }

        .node-meta {
          display: flex;
          flex-shrink: 0;
          gap: 8px;
          align-items: center;

          .node-id {
            padding: 2px 8px;
            font-size: 11px;
            color: #909399;
            white-space: nowrap;
            background-color: #f5f7fa;
            border-radius: 10px;
          }
        }
      }
    }
  }

  .dialog-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;

    .footer-info {
      display: flex;
      gap: 4px;
      align-items: center;
      font-size: 13px;
      color: #606266;

      .el-icon {
        color: #409eff;
      }
    }

    .footer-buttons {
      display: flex;
      gap: 10px;

      .el-button {
        min-width: 80px;

        .el-icon {
          margin-right: 4px;
        }
      }
    }
  }
}

@media screen and (width <= 768px) {
  .permission-dialog {
    .permission-box {
      .permission-header {
        flex-direction: column;
        align-items: flex-start;

        .left {
          margin-bottom: 8px;
        }

        .right {
          width: 100%;

          .search-box {
            width: 100%;
            padding-right: 8px;
            margin-right: 0;
          }
        }
      }
    }

    .category-actions {
      flex-direction: column;
      align-items: flex-start;

      .category-info {
        width: 100%;
        margin-bottom: 8px;
      }
    }
  }
}
</style>
