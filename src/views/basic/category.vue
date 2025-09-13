<template>
  <div class="categories-container">
    <el-dialog v-model="showAddOrEditMoadl" title="添加/修改分类" :before-close="handleClose">
      <!-- v-if 触发组件的销毁 -->
      <AddOrEdit v-if="showAddOrEditMoadl" :formData="currentCategory" @submit-success="handleSubmitSuccess" />
    </el-dialog>
    <el-card>
      <template #header>
        <el-row :gutter="16">
          <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="2">
            <span class="header-title">分类管理</span>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="3">
            <el-input v-model="searchForm.id" placeholder="ID" clearable />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="3">
            <el-input v-model="searchForm.name" placeholder="分类名称" clearable />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="3">
            <el-input v-model="searchForm.type" placeholder="类型" clearable />
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="3">
            <el-select v-model="searchForm.parent_id" placeholder="分类层级" clearable>
              <el-option label="大类别" value="0" />
              <el-option label="标签" value=">0" />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="3">
            <el-select v-model="searchForm.status" placeholder="状态" clearable>
              <el-option label="启用" value="1" />
              <el-option label="禁用" value="0" />
            </el-select>
          </el-col>
          <el-col :xs="12" :sm="6" :md="4" :lg="3" :xl="2">
            <el-button type="primary" :icon="Search" style="width: 100%" @click="search">
              搜索
            </el-button>
          </el-col>
          <el-col :xs="12" :sm="6" :md="4" :lg="3" :xl="2">
            <el-button type="primary" :icon="RefreshLeft" style="width: 100%" @click="resetSearchForm">
              重置
            </el-button>
          </el-col>
        </el-row>
      </template>

      <div class="table-header">
        <div class="header-left">
          <el-button :size="buttonSize" type="primary" :icon="CirclePlus" @click="handleAdd">
            新增分类
          </el-button>
          <el-button :size="buttonSize" type="danger" :icon="Delete" :disabled="selectedRows.length === 0"
            @click="handleBatchDelete">
            批量删除 ({{ selectedRows.length }})
          </el-button>
          <el-button :size="buttonSize" :icon="Delete" @click="showRecycleBin">
            回收站
          </el-button>
        </div>
        <div class="header-right">
          <el-button :size="buttonSize" :icon="Refresh" @click="handleRefresh">
            刷新
          </el-button>
          <el-button :size="buttonSize" :icon="Download">
            导出
          </el-button>
        </div>
      </div>

      <el-divider content-position="left">数据列表</el-divider>

      <!-- 表格 -->
      <el-table border :data="computedPagedData" :cell-style="{ textAlign: 'center' }" style="width: 100%"
        :header-cell-style="{ textAlign: 'center', backgroundColor: '#F5F7FA' }" :size="buttonSize"
        v-loading="tableLoading" :fit="true" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="55" />
        <el-table-column show-overflow-tooltip width="100px" label="id" prop="id" />
        <el-table-column show-overflow-tooltip label="分类名称" prop="name">
          <template #default="{ row }">
            <el-tag>
              {{ row.name }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column show-overflow-tooltip label="分类层级" prop="category_type_text" width="100px">
          <template #default="{ row }">
            <el-tag :type="row.parent_id === 0 ? 'primary' : 'success'" size="small">
              {{ row.parent_id === 0 ? '大类别' : '标签' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column show-overflow-tooltip label="类型" prop="type" />
        <el-table-column show-overflow-tooltip label="描述" prop="description" />
        <el-table-column show-overflow-tooltip label="排序" prop="sort_order" width="80px" />
        <el-table-column show-overflow-tooltip label="状态" prop="status" width="100px">
          <template #default="{ row }">
            <el-switch v-model="row.status" inline-prompt active-text="启用" inactive-text="禁用"
              @change="handleStatusChange(row)" />
          </template>
        </el-table-column>
        <el-table-column show-overflow-tooltip label="创建时间" prop="create_time" />
        <el-table-column show-overflow-tooltip label="更新时间" prop="update_time" />
        <el-table-column show-overflow-tooltip label="创建者" prop="author.username" />
        <el-table-column fixed="right" align="center" header-align="center" label="操作" width="180">
          <template #default="scope">
            <div class="action-buttons">
              <el-button size="small" @click="handleEdit(scope.$index, scope.row)" class="action-button">
                <el-icon>
                  <Edit />
                </el-icon>
                <span>编辑</span>
              </el-button>
              <el-button size="small" @click="handleDelete(scope.$index, scope.row)" class="action-button">
                <el-icon>
                  <Delete />
                </el-icon>
                <span>删除</span>
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <!-- 分页 -->
      <el-pagination v-model:current-page="pageConfig.currentPage" v-model:page-size="pageConfig.pageSize"
        style="margin-top: 20px" :page-sizes="[5, 10, 20, 30]" :disabled="pageConfig.disabled"
        :background="pageConfig.background" layout="total, sizes, prev, pager, next, jumper" :total="pageConfig.total"
        @size-change="handleSizeChange" @current-change="handleCurrentChange" />
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog v-model="showAddOrEditMoadl" :title="currentCategory.id ? '编辑分类' : '新增分类'" width="60%"
      :close-on-click-modal="false">
      <AddOrEdit :formData="currentCategory" @submit-success="handleSubmitSuccess" />
    </el-dialog>

    <!-- 回收站对话框 -->
    <el-dialog v-model="showRecycleBinModal" title="分类回收站" width="80%" :close-on-click-modal="false">
      <div class="recycle-bin-header">
        <el-button type="primary" :disabled="selectedRecycleBinRows.length === 0" @click="handleBatchRestore">
          批量恢复
        </el-button>
        <el-button type="danger" :disabled="selectedRecycleBinRows.length === 0" @click="handleBatchPermanentDelete">
          批量彻底删除
        </el-button>
        <el-button @click="handleRefreshRecycleBin">刷新</el-button>
      </div>

      <el-table :data="recycleBinData" v-loading="recycleBinLoading" @selection-change="handleRecycleBinSelectionChange"
        style="width: 100%; margin-top: 16px;">
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="分类名称" />
        <el-table-column prop="type" label="类型" />
        <el-table-column label="分类层级" width="100">
          <template #default="{ row }">
            <el-tag :type="row.parent_id === 0 ? 'primary' : 'success'" size="small">
              {{ row.parent_id === 0 ? '大类别' : '标签' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="delete_time" label="删除时间" width="180" />
        <el-table-column label="操作" width="200">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleRestore(row)">恢复</el-button>
            <el-button type="danger" size="small" @click="handlePermanentDelete(row)">彻底删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 回收站分页 -->
      <el-pagination v-model:current-page="recycleBinPageConfig.currentPage"
        v-model:page-size="recycleBinPageConfig.pageSize" style="margin-top: 20px; text-align: center"
        :page-sizes="[5, 10, 20, 30]" layout="total, sizes, prev, pager, next, jumper"
        :total="recycleBinPageConfig.total" @size-change="handleRecycleBinSizeChange"
        @current-change="handleRecycleBinPageChange" />
    </el-dialog>
  </div>
</template>
<script setup lang="ts">
defineOptions({
  name: "category"
});
import { getCategoryList, deleteCategory, updateCategoryR, restoreCategory } from "@/api/category";
import { message } from "@/utils/message";
import { useGlobalStoreHook } from '@/store/modules/global'
import AddOrEdit from "./category/AddOrEdit.vue";
import { ElMessageBox } from "element-plus";
import {
  Delete,
  Edit,
  Search,
  RefreshLeft,
  CirclePlus,
  Refresh,
  Download
} from "@element-plus/icons-vue";
import { ref, reactive, onMounted, computed } from "vue";
// 获取store实例
const globalStore = useGlobalStoreHook()
// 响应式数据区
const searchForm = reactive({
  id: "",
  name: "",
  type: "",
  parent_id: "",
  description: "",
  status: ""
});
const buttonSize = ref("small");
const showAddOrEditMoadl = ref(false);
const currentCategory = ref({});
const tableLoading = ref(false);

// 主表格选择相关
const selectedRows = ref([]);

// 回收站相关
const showRecycleBinModal = ref(false);
const recycleBinData = ref([]);
const recycleBinLoading = ref(false);
const selectedRecycleBinRows = ref([]);

// 回收站分页配置
const recycleBinPageConfig = ref({
  currentPage: 1,
  pageSize: 10,
  total: 0
});

// 服务器分页配置
const serverPage = ref({
  current: 1,
  size: 200,
  total: 0
});

// 本地分页配置
const pageConfig = ref({
  currentPage: 1,
  pageSize: 5,
  size: false,
  disabled: false,
  background: false,
  layout: "total, sizes, prev, pager, next, jumper",
  total: 0
});

const tableData = ref([]); // 当前页的服务器数据
const allServerData = ref([]); // 所有服务器数据缓存
const filteredData = ref([]); // 搜索过滤后的数据

// 计算属性：本地分页数据
const computedPagedData = computed(() => {
  const start = (pageConfig.value.currentPage - 1) * pageConfig.value.pageSize;
  const end = start + pageConfig.value.pageSize;
  return filteredData.value.slice(start, end);
});

// 搜索过滤函数
const filterData = () => {
  // 显示loading
  tableLoading.value = true;

  let filtered = allServerData.value;

  // 根据搜索条件过滤
  if (searchForm.id) {
    filtered = filtered.filter(item => item.id && item.id.toString().includes(searchForm.id));
  }
  if (searchForm.name) {
    filtered = filtered.filter(item => item.name && item.name.toLowerCase().includes(searchForm.name.toLowerCase()));
  }
  if (searchForm.type) {
    filtered = filtered.filter(item => item.type && item.type.toLowerCase().includes(searchForm.type.toLowerCase()));
  }
  if (searchForm.parent_id) {
    if (searchForm.parent_id === "0") {
      // 大类别
      filtered = filtered.filter(item => item.parent_id === 0);
    } else if (searchForm.parent_id === ">0") {
      // 标签
      filtered = filtered.filter(item => item.parent_id > 0);
    } else {
      // 具体的parent_id
      filtered = filtered.filter(item => item.parent_id !== undefined && item.parent_id.toString() === searchForm.parent_id);
    }
  }
  if (searchForm.description) {
    filtered = filtered.filter(item => item.description && item.description.toLowerCase().includes(searchForm.description.toLowerCase()));
  }
  if (searchForm.status !== "") {
    const statusValue = searchForm.status === "1"; // "1"表示启用(true)，"0"表示禁用(false)
    filtered = filtered.filter(item => item.status !== undefined && item.status === statusValue);
  }

  filteredData.value = filtered;
  pageConfig.value.total = filtered.length;
  pageConfig.value.currentPage = 1; // 重置到第一页

  // 关闭loading
  setTimeout(() => {
    tableLoading.value = false;
  }, 300); // 稍微延迟一下，让用户看到loading效果
};

// 搜索函数
function search() {
  filterData();
}

// 重置搜索表单
function resetSearchForm() {
  searchForm.id = "";
  searchForm.name = "";
  searchForm.type = "";
  searchForm.parent_id = "";
  searchForm.description = "";
  searchForm.status = "";

  // 重置后重新过滤数据并刷新表格
  filterData();
  // 也可以选择重新加载数据
  // initData();
}

function handleEdit(index, row) {
  globalStore.setCurrentEditID(row.id);
  showAddOrEditMoadl.value = true;
  currentCategory.value = row;
  console.log(row);
}

function handleDelete(index, row) {
  ElMessageBox.confirm(`确认删除分类 "${row.name}"?`, "提示", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "warning"
  }).then(async () => {
    try {
      const res: any = await deleteCategory({ id: row.id });
      if (res.code !== 200) {
        message(res.msg || "删除失败", { type: "error" });
        return;
      }
      message("删除成功", { type: "success" });
      // 重新加载数据
      initData();
    } catch (error) {
      console.error("删除失败:", error);
      message("删除失败", { type: "error" });
    }
  });
}

const handleClose = (done: () => void) => {
  ElMessageBox.confirm("退出将不会保存已输入的数据?", "系统提示", {
    confirmButtonText: "确定",
    cancelButtonText: "我再想想",
    type: "warning"
  })
    .then(() => {
      currentCategory.value = {};
      done();
    })
    .catch(() => {
      message("好的伙计,你现在可以继续编辑了!", { type: "success" });
    });
};

// 状态切换处理
const handleStatusChange = async (row: any) => {
  const originalStatus = !row.status; // 保存原始状态用于回滚

  try {
    const newStatus = row.status; // true为显示，false为隐藏

    // 调用更新接口
    const res: any = await updateCategoryR({
      id: row.id,
      status: newStatus ? 1 : 0  // 转换为后端需要的格式
    });

    if (res.code === 200) {
      console.log(`分类 "${row.name}" 状态已更改为: ${newStatus ? '启用' : '禁用'}`);

      // 更新本地数据
      const index = allServerData.value.findIndex(item => item.id === row.id);
      if (index !== -1) {
        allServerData.value[index].status = newStatus;
      }

      // 重新过滤数据
      filterData();

      // 显示成功消息
      message(`分类状态已更新为${newStatus ? '启用' : '禁用'}`, { type: 'success' });
    } else {
      // 服务器返回错误，恢复原状态
      row.status = originalStatus;
      message(res.msg || '状态更新失败', { type: 'error' });
    }

  } catch (error) {
    // 如果失败，恢复原状态
    row.status = originalStatus;
    console.error('状态更新失败:', error);
    message('状态更新失败', { type: 'error' });
  }
};

// 新增分类
const handleAdd = () => {
  currentCategory.value = {};
  globalStore.setCurrentEditID(null);
  showAddOrEditMoadl.value = true;
};

// 刷新表格
const handleRefresh = async () => {
  // 清空搜索条件
  resetSearchForm();
  // 重新加载数据
  await initData();
};

// 显示回收站
const showRecycleBin = () => {
  showRecycleBinModal.value = true;
  fetchRecycleBinData();
};

// 获取回收站数据（支持分页）
const fetchRecycleBinData = async (page = 1) => {
  try {
    recycleBinLoading.value = true;
    const res: any = await getCategoryList({
      page_num: page,
      page_size: recycleBinPageConfig.value.pageSize,
      include_deleted: true // 只查询软删除数据
    });

    if (res.code === 200) {
      recycleBinData.value = res.data.list || [];
      recycleBinPageConfig.value.total = res.data.pagination?.total || 0;
      recycleBinPageConfig.value.currentPage = page;
      console.log('回收站数据:', recycleBinData.value);
      console.log('回收站分页信息:', res.data.pagination);
    }
  } catch (error) {
    console.error('获取回收站数据失败:', error);
    message('获取回收站数据失败', { type: 'error' });
  } finally {
    recycleBinLoading.value = false;
  }
};

// 回收站表格选择变化
const handleRecycleBinSelectionChange = (selection: any[]) => {
  selectedRecycleBinRows.value = selection;
};

// 刷新回收站
const handleRefreshRecycleBin = () => {
  fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
};

// 主表格选择变化处理
const handleSelectionChange = (selection: any[]) => {
  selectedRows.value = selection;
};

// 回收站分页处理
const handleRecycleBinPageChange = (page: number) => {
  fetchRecycleBinData(page);
};

const handleRecycleBinSizeChange = (size: number) => {
  recycleBinPageConfig.value.pageSize = size;
  fetchRecycleBinData(1);
};

// 主表格批量删除（软删除）
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) return;

  try {
    ElMessageBox.confirm(`确认删除选中的 ${selectedRows.value.length} 个分类?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }).then(async () => {
      const ids = selectedRows.value.map(row => row.id);
      const res: any = await deleteCategory({
        ids: ids,
        real: false // 软删除
      });

      if (res.code === 200) {
        const { success, failed, total } = res.data;
        if (failed === 0) {
          message(`成功删除 ${success} 个分类`, { type: 'success' });
        } else {
          message(`删除完成：成功 ${success} 个，失败 ${failed} 个`, { type: 'warning' });
        }

        // 清空选择
        selectedRows.value = [];
        // 刷新数据
        initData();
      } else {
        message(res.msg || '批量删除失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('批量删除失败:', error);
    message('批量删除失败', { type: 'error' });
  }
};

// 恢复单个分类
const handleRestore = async (row: any) => {
  try {
    ElMessageBox.confirm(`确认恢复分类 "${row.name}"?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }).then(async () => {
      // 调用恢复接口
      const res: any = await restoreCategory({ id: row.id });

      if (res.code === 200) {
        message(`分类 "${row.name}" 已恢复`, { type: 'success' });

        // 刷新回收站数据
        fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
        // 刷新主表格数据
        initData();
      } else {
        message(res.msg || '恢复失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('恢复分类失败:', error);
    message('恢复分类失败', { type: 'error' });
  }
};

// 彻底删除单个分类
const handlePermanentDelete = async (row: any) => {
  try {
    ElMessageBox.confirm(`确认彻底删除分类 "${row.name}"? 此操作不可恢复!`, "警告", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "error"
    }).then(async () => {
      // 调用彻底删除接口
      const res: any = await deleteCategory({
        id: row.id,
        real: true // 物理删除
      });

      if (res.code === 200) {
        message(`分类 "${row.name}" 已彻底删除`, { type: 'success' });

        // 刷新回收站数据
        fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
      } else {
        message(res.msg || '彻底删除失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('彻底删除分类失败:', error);
    message('彻底删除分类失败', { type: 'error' });
  }
};

// 批量恢复
const handleBatchRestore = async () => {
  if (selectedRecycleBinRows.value.length === 0) return;

  try {
    ElMessageBox.confirm(`确认恢复选中的 ${selectedRecycleBinRows.value.length} 个分类?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }).then(async () => {
      // 调用批量恢复接口
      const ids = selectedRecycleBinRows.value.map(row => row.id);
      const res: any = await restoreCategory({ ids: ids });

      if (res.code === 200) {
        const { success, failed } = res.data || { success: ids.length, failed: 0 };
        if (failed === 0) {
          message(`成功恢复 ${success} 个分类`, { type: 'success' });
        } else {
          message(`恢复完成：成功 ${success} 个，失败 ${failed} 个`, { type: 'warning' });
        }

        // 清空选择
        selectedRecycleBinRows.value = [];
        // 刷新回收站数据
        fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
        // 刷新主表格数据
        initData();
      } else {
        message(res.msg || '批量恢复失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('批量恢复分类失败:', error);
    message('批量恢复失败', { type: 'error' });
  }
};

// 批量彻底删除
const handleBatchPermanentDelete = async () => {
  if (selectedRecycleBinRows.value.length === 0) return;

  try {
    ElMessageBox.confirm(`确认彻底删除选中的 ${selectedRecycleBinRows.value.length} 个分类? 此操作不可恢复!`, "警告", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "error"
    }).then(async () => {
      const ids = selectedRecycleBinRows.value.map(row => row.id);
      const res: any = await deleteCategory({
        ids: ids,
        real: true // 物理删除
      });

      if (res.code === 200) {
        const { success, failed } = res.data;
        if (failed === 0) {
          message(`成功彻底删除 ${success} 个分类`, { type: 'success' });
        } else {
          message(`删除完成：成功 ${success} 个，失败 ${failed} 个`, { type: 'warning' });
        }

        // 清空选择
        selectedRecycleBinRows.value = [];
        // 刷新回收站数据
        fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
      } else {
        message(res.msg || '批量彻底删除失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('批量彻底删除分类失败:', error);
    message('批量彻底删除失败', { type: 'error' });
  }
};

const handleSizeChange = (val: number) => {
  pageConfig.value.pageSize = val;
  pageConfig.value.currentPage = 1;
};

const handleCurrentChange = (val: number) => {
  pageConfig.value.currentPage = val;

  // 检查是否需要加载更多服务器数据
  const totalDisplayed = filteredData.value.length;
  const currentEnd = val * pageConfig.value.pageSize;

  // 如果当前显示的数据不够，且服务器还有更多数据，则加载下一页
  if (currentEnd > totalDisplayed && serverPage.value.current * serverPage.value.size < serverPage.value.total) {
    loadMoreServerData();
  }
};

// 加载服务器数据
async function loadServerData(pageNum = 1, reset = false) {
  tableLoading.value = true;

  try {
    const params = {
      page_num: pageNum,
      page_size: serverPage.value.size
    };

    const res: any = await getCategoryList(params);

    if (res.code === 200) {
      const newData = res.data.list || [];

      // 处理数据格式
      newData.forEach(item => {
        // status字段：true为显示，false为隐藏
        item.status = Boolean(item.status);
        // 兼容性：如果有is_show字段，也处理一下
        if (item.is_show !== undefined) {
          item.status = Boolean(item.is_show);
        }
        // 添加分类类型文本
        item.category_type_text = item.parent_id === 0 ? '大类别' : '标签';
      });

      if (reset) {
        // 重置数据
        allServerData.value = newData;
        serverPage.value.current = 1;
      } else {
        // 追加数据
        allServerData.value.push(...newData);
        serverPage.value.current = pageNum;
      }

      // 更新服务器分页信息
      serverPage.value.total = res.data.pagination?.total || newData.length;

      // 重新过滤数据
      filterData();
    }
  } catch (error) {
    console.error('加载分类数据失败:', error);
  } finally {
    tableLoading.value = false;
  }
}

// 加载更多服务器数据
async function loadMoreServerData() {
  const nextPage = serverPage.value.current + 1;
  await loadServerData(nextPage, false);
}

// 初始化数据
async function initData() {
  await loadServerData(1, true);
}

const handleSubmitSuccess = () => {
  showAddOrEditMoadl.value = false
  initData();
}

// 页面加载
onMounted(() => {
  initData();
});
</script>
<style lang="scss" scoped>
.header-title {
  font-size: 16px;
  font-weight: bold;
  line-height: 32px;
  white-space: nowrap;
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: nowrap;
}

.action-button {
  padding: 8px 12px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

@media screen and (max-width: 768px) {
  .action-buttons {
    flex-direction: column;
    gap: 4px;
  }

  .action-button {
    width: 100%;
    justify-content: center;
  }
}

.el-col {
  margin-bottom: 16px;
}

@media (min-width: 1920px) {
  .el-col {
    margin-bottom: 0;
  }
}

@media (min-width: 1200px) and (max-width: 1919px) {
  .el-col {
    margin-bottom: 12px;
  }
}

@media (min-width: 768px) and (max-width: 1199px) {
  .el-col {
    margin-bottom: 10px;
  }
}

@media (max-width: 767px) {
  .el-col {
    margin-bottom: 8px;
  }

  .el-input,
  .el-select {
    margin-bottom: 8px;
  }
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.header-left,
.header-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.recycle-bin-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}
</style>