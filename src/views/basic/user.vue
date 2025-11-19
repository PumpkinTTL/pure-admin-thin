<template>
  <div class="users-container">
    <el-dialog
      v-model="showAddOrEditMoadl"
      title="添加/修改用户"
      :before-close="handleClose"
      @closed="handleDialogClosed"
    >
      <!-- v-if 触发组件的销毁 -->
      <AddOrEdit
        v-if="showAddOrEditMoadl"
        :formData="currentUser"
        @submit-success="handleSubmitSuccess"
      />
    </el-dialog>
    <el-card>
      <template #header>
        <el-row :gutter="10">
          <el-col :xs="24" :sm="24" :md="4" :lg="3" :xl="2">
            <span class="header-title">用户管理</span>
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="2">
            <el-input
              v-model="searchForm.id"
              placeholder="ID"
              clearable
              size="default"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-input
              v-model="searchForm.username"
              placeholder="用户名"
              clearable
              size="default"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-input
              v-model="searchForm.phone"
              placeholder="手机号"
              clearable
              size="default"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-input
              v-model="searchForm.email"
              placeholder="邮箱"
              clearable
              size="default"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-select
              v-model="searchForm.status"
              placeholder="状态"
              style="width: 100%"
              size="default"
            >
              <el-option label="全部" value="" />
              <el-option label="正常" value="1" />
              <el-option label="禁用" value="0" />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-select
              v-model="searchForm.deleted"
              placeholder="在库状态"
              style="width: 100%"
              size="default"
            >
              <el-option label="全部" value="" />
              <el-option label="正常" value="0" />
              <el-option label="已软删除" value="1" />
            </el-select>
          </el-col>
          <el-col :xs="12" :sm="6" :md="3" :lg="2" :xl="2">
            <div class="search-buttons">
              <el-button type="primary" :icon="Search" @click="search">
                搜索
              </el-button>
              <el-button
                type="primary"
                :icon="RefreshLeft"
                @click="resetSearchForm"
              >
                重置
              </el-button>
            </div>
          </el-col>
        </el-row>
      </template>

      <div class="table-header flex">
        <el-button :size="buttonSize" :icon="Search" circle />
        <el-button :size="buttonSize" :icon="Printer" circle />
        <el-button :size="buttonSize" :icon="Upload" circle />
        <el-button
          :size="buttonSize"
          type="primary"
          :icon="CirclePlus"
          @click="handleAdd"
        >
          新增
        </el-button>
      </div>

      <el-divider content-position="left">
        数据列表
        <el-tag
          v-if="pageConfig.total > 0"
          size="small"
          type="info"
          class="ml-2"
        >
          共 {{ pageConfig.total }} 条记录
        </el-tag>
      </el-divider>

      <!-- 表格 -->
      <el-table
        v-loading="tableLoading"
        border
        :data="computedPagedData"
        :cell-style="{ textAlign: 'center' }"
        style="width: 100%"
        :header-cell-style="{ textAlign: 'center', backgroundColor: '#F5F7FA' }"
        :size="buttonSize"
        :fit="true"
      >
        <el-table-column
          show-overflow-tooltip
          width="100px"
          label="id"
          prop="id"
        />
        <el-table-column show-overflow-tooltip label="用户名" prop="username" />
        <el-table-column show-overflow-tooltip label="手机号" prop="phone" />
        <el-table-column show-overflow-tooltip label="邮箱" prop="email" />
        <el-table-column show-overflow-tooltip label="性别" prop="gender">
          <template #default="{ row }">
            <el-text v-if="row.gender === 0" type="info">女</el-text>
            <el-text v-if="row.gender === 1" type="info">男</el-text>
            <el-text v-if="row.gender === 2" type="info">未知</el-text>
          </template>
        </el-table-column>

        <el-table-column show-overflow-tooltip label="头像" prop="avatar">
          <template #default="{ row }">
            <el-image
              :preview-teleported="true"
              style="width: 40px; height: 40px"
              :src="row.avatar"
              :preview-src-list="[row.avatar]"
            >
              <!-- 当头像加载失败时显示默认头像 -->
              <template #error>
                <div class="image-error">
                  <el-avatar :size="40">
                    {{ row.name?.charAt(0) || row.username?.charAt(0) }}
                  </el-avatar>
                </div>
              </template>
            </el-image>
          </template>
        </el-table-column>
        <el-table-column show-overflow-tooltip label="在库状态" prop="status">
          <template #default="{ row }">
            <el-tag :type="row.delete_time ? 'danger' : 'primary'">
              {{ row.delete_time ? "已软删除" : "正常" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column show-overflow-tooltip label="启用状态" prop="status">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              inline-prompt
              active-text="启用"
              inactive-text="禁用"
              :disabled="!!row.delete_time"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          width="160"
          label="会员状态"
          prop="premium"
        >
          <template #default="{ row }">
            <div class="premium-status">
              <el-tag v-if="row.premium" class="premium-tag">
                <el-icon>
                  <Star />
                </el-icon>
                Premium
              </el-tag>
              <el-tag v-else type="info" effect="plain">普通用户</el-tag>
              <div v-if="row.premium" class="premium-expire">
                到期: {{ formatDate(row.premium.expiration_time) }}
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          width="100"
          label="等级"
          prop="level_records"
        >
          <template #default="{ row }">
            <el-popover
              placement="right"
              :width="300"
              trigger="click"
              :hide-after="0"
            >
              <template #reference>
                <el-button size="small" type="primary" link class="level-btn">
                  <el-icon :size="13"><TrendCharts /></el-icon>
                  <span>查看</span>
                </el-button>
              </template>
              <div class="level-details">
                <div
                  v-for="record in row.level_records"
                  :key="record.id"
                  class="level-item"
                >
                  <div class="level-header">
                    <el-tag
                      :type="getLevelTypeColor(record.target_type)"
                      size="small"
                    >
                      {{ getLevelTypeName(record.target_type) }}
                    </el-tag>
                    <span class="level-number">
                      Lv.{{ record.current_level }}
                    </span>
                  </div>
                  <div class="level-info">
                    <div class="info-row">
                      <span class="label">总经验</span>
                      <span class="value">{{ record.total_experience }}</span>
                    </div>
                    <div class="info-row">
                      <span class="label">当前经验</span>
                      <span class="value">
                        {{ record.experience_in_level }}
                      </span>
                    </div>
                    <div class="info-row">
                      <span class="label">升级次数</span>
                      <span class="value">{{ record.level_up_count }}</span>
                    </div>
                  </div>
                </div>
                <el-empty
                  v-if="!row.level_records || row.level_records.length === 0"
                  description="暂无等级数据"
                  :image-size="60"
                />
              </div>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          width="150"
          label="角色组"
          prop="role"
        >
          <template #default="{ row }">
            <el-popover placement="top-start" trigger="click" :hide-after="0">
              <template #reference>
                <div class="role-preview">
                  <el-tag
                    v-if="row.roles && row.roles.length > 0"
                    type="primary"
                    :class="{
                      'super-admin-tag': row.roles[0].name === '超级管理员',
                      'normal-admin-tag':
                        row.roles[0].name.includes('管理员') &&
                        row.roles[0].name !== '超级管理员',
                      'user-tag': !row.roles[0].name.includes('管理员')
                    }"
                    size="small"
                  >
                    <el-icon>
                      <Lock v-if="row.roles[0].name.includes('管理员')" />
                      <User v-else />
                    </el-icon>
                    {{
                      row.roles[0].name.includes("管理员")
                        ? row.roles[0].name
                        : "用户"
                    }}
                  </el-tag>
                  <el-link
                    v-if="row.roles && row.roles.length > 1"
                    type="primary"
                    class="more-roles"
                  >
                    共 {{ row.roles.length - 1 }}+
                  </el-link>
                </div>
              </template>
              <div class="role-list">
                <el-tag
                  v-for="(role, index) in row.roles"
                  :key="index"
                  :type="role.name.includes('管理员') ? 'primary' : 'info'"
                  :class="{
                    'super-admin-tag': role.name === '超级管理员',
                    'normal-admin-tag':
                      role.name.includes('管理员') &&
                      role.name !== '超级管理员',
                    'user-tag': !role.name.includes('管理员')
                  }"
                  size="small"
                  class="role-tag"
                >
                  <el-icon>
                    <Lock v-if="role.name.includes('管理员')" />
                    <User v-else />
                  </el-icon>
                  {{ role.name }}
                </el-tag>
              </div>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column
          show-overflow-tooltip
          label="注册时间"
          prop="create_time"
        />
        <el-table-column
          show-overflow-tooltip
          label="个性签名"
          prop="signature"
        />
        <el-table-column
          show-overflow-tooltip
          label="最后登录时间"
          prop="last_login"
        />
        <el-table-column
          fixed="right"
          align="center"
          header-align="center"
          label="操作"
          width="180"
        >
          <template #default="scope">
            <div class="action-buttons">
              <el-button
                size="small"
                class="action-button"
                :disabled="!!scope.row.delete_time"
                @click="handleEdit(scope.$index, scope.row)"
              >
                <el-icon>
                  <Edit />
                </el-icon>
                <span>编辑</span>
              </el-button>
              <el-button
                size="small"
                :type="scope.row.delete_time ? 'success' : 'danger'"
                class="action-button"
                @click="handleDelete(scope.$index, scope.row)"
              >
                <el-icon>
                  <template v-if="scope.row.delete_time">
                    <RefreshRight />
                  </template>
                  <template v-else>
                    <Delete />
                  </template>
                </el-icon>
                <span>{{ scope.row.delete_time ? "恢复" : "删除" }}</span>
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pageConfig.currentPage"
        v-model:page-size="pageConfig.pageSize"
        style="margin-top: 20px"
        :page-sizes="PAGE_SIZES"
        :disabled="pageConfig.disabled"
        :background="pageConfig.background"
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
  name: "user"
});
import {
  getUserList,
  updateUserInfoR,
  deleteUser,
  restoreUser,
  type ApiResponse,
  type UserListResponse
} from "@/api/user";
import { useGlobalStoreHook } from "@/store/modules/global";
import { generateSerialNumbers } from "@/utils/dataUtil";
import { message } from "@/utils/message";
// 恢复为默认导入
import AddOrEdit from "./user/AddOrEdit.vue";
import { ElMessageBox, ElNotification } from "element-plus";
import { h } from "vue";
import {
  Delete,
  Edit,
  Search,
  Share,
  RefreshLeft,
  CirclePlus,
  Printer,
  Upload,
  Lock,
  Star,
  User,
  RefreshRight,
  TrendCharts
} from "@element-plus/icons-vue";
import { ref, reactive, onMounted, computed } from "vue";

// 定义搜索参数接口（使用下划线格式）
interface SearchParams {
  id?: string; // 确保id为字符串类型
  username?: string;
  phone?: string;
  email?: string;
  status?: string | number;
  deleted?: string | number; // 添加软删除状态字段
  page_size?: number;
  current_page?: number;
}

// 获取store实例
const globalStore = useGlobalStoreHook();
// 响应式数据区
const searchForm = reactive<SearchParams>({
  id: "",
  username: "",
  phone: "",
  email: "",
  status: undefined,
  deleted: undefined
});
const buttonSize = ref("small" as const);
const showAddOrEditMoadl = ref(false);
// 分页配置
const pageConfig = ref({
  currentPage: 1,
  pageSize: 10, // 默认每页10条
  size: false,
  disabled: false,
  background: true, // 改为有背景色
  layout: "total, sizes, prev, pager, next, jumper",
  total: 0
});
const currentUser = ref({});
const tableData = ref([]);

// 常量定义
const PAGE_SIZES = [5, 10, 20, 30, 50];
const MAX_FETCH_SIZE = 100; // 每次从服务器获取的最大记录数

// 计算属性计算分页数据
const computedPagedData = computed(() => {
  const start = (pageConfig.value.currentPage - 1) * pageConfig.value.pageSize;
  const end = start + pageConfig.value.pageSize;
  // 截取数据
  return tableData.value.slice(start, end);
});

// 函数区
function search() {
  // 构建搜索参数，直接使用表单值无需验证
  const searchParams: SearchParams = {};

  // 添加各个搜索字段
  if (searchForm.id) {
    searchParams.id = searchForm.id;
  }

  if (searchForm.username) {
    searchParams.username = searchForm.username;
  }

  if (searchForm.phone) {
    searchParams.phone = searchForm.phone;
  }

  if (searchForm.email) {
    searchParams.email = searchForm.email;
  }

  if (searchForm.status !== undefined && searchForm.status !== "") {
    searchParams.status = searchForm.status;
  }

  if (searchForm.deleted !== undefined && searchForm.deleted !== "") {
    searchParams.deleted = searchForm.deleted;
  }

  // 添加每页请求的最大记录数
  searchParams.page_size = MAX_FETCH_SIZE;

  // 使用参数调用API并重置分页到第一页
  pageConfig.value.currentPage = 1;
  initData(searchParams);
}

// 添加重置搜索表单方法
function resetSearchForm() {
  searchForm.id = "";
  searchForm.username = "";
  searchForm.phone = "";
  searchForm.email = "";
  searchForm.status = undefined;
  searchForm.deleted = undefined;

  // 重置后自动搜索所有数据
  search();
}

function handleEdit(index, row) {
  globalStore.setCurrentEditID(row.id);
  showAddOrEditMoadl.value = true;

  // 克隆行数据以避免引用问题
  const rowClone = JSON.parse(JSON.stringify(row));

  // 使用克隆的数据更新currentUser
  currentUser.value = rowClone;

  console.log("编辑用户数据:", rowClone);
}
// 表格删除
async function handleDelete(index, row) {
  const isDeleted = !!row.delete_time; // 检查 delete_time 是否有值，有值表示是已软删除的用户
  const actionText = isDeleted ? "恢复" : "删除";

  if (isDeleted) {
    // 已软删除用户，直接询问是否恢复
    ElMessageBox.confirm(`确认${actionText}用户 "${row.username}"?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    })
      .then(async () => {
        try {
          tableLoading.value = true;
          const res = await restoreUser({ id: row.id });

          if (res.code !== 200) {
            ElNotification({
              title: "错误",
              message: res.msg || `${actionText}失败`,
              type: "error"
            });
            return;
          }

          message(`${actionText}成功`, { type: "success" });
          await initData(); // 操作成功后重新加载数据
        } catch (error) {
          console.error(`${actionText}用户失败:`, error);

          message(`${actionText}失败`, { type: "error" });
        } finally {
          tableLoading.value = false; // 隐藏加载状态
        }
      })
      .catch(() => {
        console.log(`用户取消${actionText}操作`);
      });
  } else {
    // 未软删除用户，显示带永久删除选项的确认框
    const isRealDelete = ref(false);

    // 使用h渲染函数创建自定义消息内容
    const confirmContent = () => {
      return h("div", null, [
        h("p", null, `确认删除用户 "${row.username}"?`),
        h(
          "div",
          { style: "margin-top: 12px; display: flex; align-items: center;" },
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
            h(
              "span",
              { style: "color: #f56c6c;" },
              "永久删除（此操作不可恢复）"
            )
          ]
        )
      ]);
    };

    ElMessageBox({
      title: "删除确认",
      message: confirmContent(),
      showCancelButton: true,
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
      beforeClose: (action, instance, done) => {
        if (action === "confirm") {
          instance.confirmButtonLoading = true;

          // 根据复选框状态决定是否执行物理删除
          deleteUser({
            id: row.id,
            real: isRealDelete.value // 传递real参数表示是否永久删除
          })
            .then(res => {
              if (res.code !== 200) {
                return message(res.msg || "删除失败", { type: "error" });
              }
              message(res.msg || "删除成功", { type: "success" });
              initData(); // 操作成功后重新加载数据
              done();
            })
            .catch(error => {
              console.error("删除用户失败:", error);
              ElNotification({
                title: "错误",
                message: "删除用户操作失败",
                type: "error"
              });
              done();
            })
            .finally(() => {
              instance.confirmButtonLoading = false;
            });
        } else {
          done();
        }
      }
    }).catch(() => {
      console.log(`用户取消删除操作`);
    });
  }
}
const handleClose = (done: () => void) => {
  ElMessageBox.confirm("退出将不会保存已输入的数据?", "系统提示", {
    confirmButtonText: "确定",
    cancelButtonText: "我再想想",
    type: "warning"
  })
    .then(() => {
      done();
    })
    .catch(() => {
      ElNotification({
        title: "上帝保佑",
        message: "好的伙计,你现在可以继续编辑了!",
        type: "success"
      });
    });
};
const handleStatusChange = async row => {
  try {
    const action = row.status ? "启用" : "禁用";
    await ElMessageBox.confirm(
      `确定要${action}${row.username}吗?`,
      "系统提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );
    tableLoading.value = true;
    // 这里添加更新用户状态的API调用
    const res = await updateUserInfoR({
      id: row.id,
      status: row.status,
      updateStatusOnly: true
    });
    if (res.code !== 200) {
      row.status = !row.status; // 如果失败则恢复原状态
      return message("状态更新失败", { type: "error" });
    }
    await initData(); // 刷新表格数据
    message(`用户已${action}`, { type: "success" });
  } catch (error) {
    row.status = !row.status; // 取消操作时恢复原状态
    console.log("用户取消状态变更");
  } finally {
    tableLoading.value = false;
  }
};
// 监听选择每页几条数据
const handleSizeChange = (val: number) => {
  pageConfig.value.pageSize = val;
  pageConfig.value.currentPage = 1;
  // 无需重新调用API，只需重新计算分页数据
  console.log(`每页显示 ${val} 条数据`);
};

// 监听选择跳转页
const handleCurrentChange = (val: number) => {
  pageConfig.value.currentPage = val;
  console.log(`当前页: ${val}`);
  // 无需重新调用API，只需重新计算分页数据
};

const tableLoading = ref(false); // 添加这行

async function initData(params: SearchParams = {}) {
  tableLoading.value = true;
  try {
    // 确保请求中包含最大获取条数和分页参数
    const queryParams = {
      ...params,
      page_size: MAX_FETCH_SIZE, // 从服务器一次请求100条数据
      current_page: 1 // 默认请求第一页
    };

    const res = await getUserList(queryParams);

    if (res.code === 200) {
      // 从新的数据结构中获取列表和分页信息
      const { list, pagination } = res.data;

      // 更新表格数据
      tableData.value = list;

      // 更新分页信息
      if (pagination) {
        pageConfig.value.total = pagination.total;

        // 如果后端返回了分页数据，但本地页码超出了范围，则重置为第一页
        if (
          pageConfig.value.currentPage > pagination.pages &&
          pagination.pages > 0
        ) {
          pageConfig.value.currentPage = 1;
        }
      } else {
        pageConfig.value.total = list.length;
      }
    } else {
      message(res.msg || "获取用户数据失败", { type: "error" });
      tableData.value = [];
      pageConfig.value.total = 0;
    }
  } catch (error: any) {
    console.error("获取用户数据失败:", error);
    message("网络错误，请稍后重试", { type: "error" });
    tableData.value = [];
    pageConfig.value.total = 0;
  } finally {
    tableLoading.value = false;
  }
}
const handleSubmitSuccess = () => {
  showAddOrEditMoadl.value = false; // 关闭弹窗
  // 可以在这里添加刷新表格数据等操作
  initData();
};

// 页面加载
onMounted(() => {
  initData();
});

function handleAdd() {
  showAddOrEditMoadl.value = true;
}

// 弹窗完全关闭后触发，重置 currentUser
function handleDialogClosed() {
  // 无论是新增还是编辑，只要弹窗关闭就重置 currentUser
  currentUser.value = {};
  console.log("对话框已关闭，currentUser已重置");
}

// 格式化日期函数
function formatDate(dateString) {
  if (!dateString) return "未设置";
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return "无效日期";
  return date.toLocaleDateString("zh-CN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  });
}

// 获取等级类型名称
function getLevelTypeName(type: string): string {
  const typeMap = {
    user: "用户等级",
    writer: "写作等级",
    reader: "读者等级",
    interaction: "互动等级"
  };
  return typeMap[type] || type;
}

// 获取等级类型颜色
function getLevelTypeColor(type: string): string {
  const colorMap = {
    user: "primary",
    writer: "success",
    reader: "warning",
    interaction: "danger"
  };
  return colorMap[type] || "info";
}
</script>
<style lang="scss" scoped>


@media screen and (width <= 768px) {
  .action-buttons {
    flex-direction: column;
    gap: 4px;
  }

  .action-button {
    justify-content: center;
    width: 100%;
  }
}

@media (width >= 1920px) {
  .el-col {
    margin-bottom: 0;
  }
}

@media (width >= 1200px) and (width <= 1919px) {
  .el-col {
    margin-bottom: 12px;
  }
}

@media (width >= 768px) and (width <= 1199px) {
  .el-col {
    margin-bottom: 10px;
  }
}

@media (width <= 767px) {
  .el-col {
    margin-bottom: 8px;
  }

  .el-input,
  .el-select {
    margin-bottom: 8px;
  }
}

.header-title {
  font-size: 16px;
  font-weight: bold;
  line-height: 32px;
  white-space: nowrap;
}

.action-buttons {
  display: flex;
  flex-wrap: nowrap;
  gap: 8px;
  justify-content: center;
}

.search-buttons {
  display: flex;
  gap: 8px;
  justify-content: flex-start;
}

// 等级按钮样式
.level-btn {
  height: 24px !important;
  padding: 2px 6px !important;
  font-size: 12px !important;

  span {
    font-size: 12px;
  }

  .el-icon {
    margin-right: 2px;
  }
}

// 等级信息弹窗样式
.level-details {
  padding: 6px;

  .level-item {
    padding: 6px 8px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    transition: all 0.15s;

    &:last-child {
      margin-bottom: 0;
    }

    &:hover {
      background: #e7f2ff;
      border-color: #b3d8ff;
    }

    .level-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 5px;

      :deep(.el-tag) {
        height: 20px;
        padding: 0 6px;
        font-size: 11px;
        line-height: 18px;
        border-radius: 3px;
      }

      .level-number {
        font-size: 12px;
        font-weight: 600;
        color: #409eff;
      }
    }

    .level-info {
      .info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 2px 0;
        font-size: 11px;
        line-height: 1.4;

        &:first-child {
          padding-top: 0;
        }

        &:last-child {
          padding-bottom: 0;
        }

        .label {
          font-size: 11px;
          font-weight: 400;
          color: #6c757d;

          &::after {
            margin: 0 2px;
            content: ":";
          }
        }

        .value {
          font-size: 11px;
          font-weight: 500;
          color: #495057;
        }
      }
    }
  }
}

.premium-status {
  @keyframes star-glow {
    from {
      filter: drop-shadow(0 0 1px rgb(255 215 0 / 30%));
    }

    to {
      filter: drop-shadow(0 0 3px rgb(255 215 0 / 80%));
    }
  }

  display: flex;
  flex-direction: column;
  gap: 5px;
  align-items: center;

  .premium-tag {
    position: relative;
    display: flex;
    gap: 4px;
    align-items: center;
    padding: 0 10px;
    overflow: hidden;
    font-weight: 500;
    color: white;
    background: linear-gradient(135deg, #ff6cab, #7366ff);
    border: 1px solid #e679c8;
    box-shadow: 0 1px 4px rgb(115 102 255 / 30%);
    transition: all 0.3s ease;

    &::before {
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      pointer-events: none;
      content: "";
      background: radial-gradient(
        circle,
        rgb(255 255 255 / 30%) 0%,
        rgb(255 255 255 / 0%) 70%
      );
      opacity: 0;
      transition:
        transform 0.5s,
        opacity 0.5s;
      transform: scale(0.5);
    }

    .el-icon {
      color: #ffd700;
      animation: star-glow 2s infinite alternate;
    }

    &:hover {
      background: linear-gradient(135deg, #ff5ba0, #6257ee);
      box-shadow: 0 2px 6px rgb(115 102 255 / 40%);
      transform: translateY(-1px);

      &::before {
        opacity: 1;
        transform: scale(1);
      }
    }
  }

  .el-tag {
    display: flex;
    gap: 4px;
    align-items: center;
  }

  .premium-expire {
    font-size: 12px;
    color: #909399;
  }
}

.action-button {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  padding: 8px 12px;
}

.el-col {
  margin-bottom: 16px;
}

.role-preview {
  display: flex;
  gap: 8px;
  align-items: center;

  .more-roles {
    font-size: 12px;
    cursor: pointer;
  }
}

.role-list {
  display: flex;
  flex-direction: column;
  gap: 8px;

  .role-tag {
    margin: 2px 0;
  }
}

/* 特殊标签样式已移至 @/style/tags/index.scss */
</style>
