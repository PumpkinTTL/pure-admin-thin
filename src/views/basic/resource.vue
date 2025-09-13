<template>
  <div class="resource-container">
    <el-card class="box-card" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="header-title">
            <font-awesome-icon :icon="['fas', 'cubes']" class="mr-2" />资源管理
          </h3>
        </div>
      </template>

      <!-- 搜索区域 -->
      <el-form :model="searchForm" label-width="70px" class="search-form">
        <div class="search-area">
          <div class="search-items">
            <el-row :gutter="12">
              <el-col :xs="24" :sm="12" :md="8" :lg="5" :xl="4">
                <el-form-item label="资源名称">
                  <el-input v-model="searchForm.resource_name" placeholder="请输入资源名称" clearable :prefix-icon="Search" />
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="5" :xl="4">
                <el-form-item label="资源分类">
                  <el-select v-model="searchForm.category_id" placeholder="选择分类" clearable style="width: 100%">
                    <el-option v-for="item in categoryOptions" :key="item.id" :label="item.name" :value="item.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="5" :xl="4">
                <el-form-item label="适用平台">
                  <el-select v-model="searchForm.platform" placeholder="选择平台" clearable style="width: 100%">
                    <el-option v-for="item in platformOptions" :key="item" :label="item" :value="item" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="5" :xl="4">
                <el-form-item label="资源状态">
                  <el-select v-model="searchForm.status" placeholder="选择状态" clearable style="width: 100%">
                    <el-option label="正常" value="active" />
                    <el-option label="已删除" value="deleted" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="5" :xl="4">
                <el-form-item label="资源类型">
                  <el-select v-model="searchForm.is_premium" placeholder="选择类型" clearable style="width: 100%">
                    <el-option label="普通资源" :value="0" />
                    <el-option label="高级资源" :value="1" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="5" :xl="4" class="search-buttons-col">
                <div class="search-buttons">
                  <el-button type="primary" @click="handleSearch" :size="buttonSize">
                    <font-awesome-icon :icon="['fas', 'search']" class="mr-1" />
                    搜索
                  </el-button>
                  <el-button @click="resetSearchForm" :size="buttonSize">
                    <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />
                    重置
                  </el-button>
                </div>
              </el-col>
            </el-row>
          </div>
        </div>
      </el-form>

      <!-- 操作按钮区域 -->
      <div class="action-toolbar">
        <div class="left-actions">
          <el-button :size="buttonSize" type="primary" @click="handleAddResource" class="btn-with-icon">
            <font-awesome-icon :icon="['fas', 'plus']" class="mr-1" />
            新增资源
          </el-button>
          <el-button :size="buttonSize" type="danger" :disabled="selectedResources.length === 0"
            @click="handleBatchDelete" class="btn-with-icon">
            <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" />
            批量删除 ({{ selectedResources.length }})
          </el-button>
          <el-button :size="buttonSize" @click="showRecycleBin" class="btn-with-icon">
            <font-awesome-icon :icon="['fas', 'recycle']" class="mr-1" />
            回收站
          </el-button>
        </div>
        <div class="right-actions">
          <el-button :size="buttonSize" plain @click="fetchResourceList" class="btn-with-icon">
            <font-awesome-icon :icon="['fas', 'sync']" class="mr-1" />
            刷新
          </el-button>
          <el-dropdown trigger="click">
            <el-button :size="buttonSize" plain class="btn-with-icon">
              <font-awesome-icon :icon="['fas', 'ellipsis-h']" class="mr-1" />
              更多操作
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item>
                  <font-awesome-icon :icon="['fas', 'print']" class="mr-1" />打印
                </el-dropdown-item>
                <el-dropdown-item>
                  <font-awesome-icon :icon="['fas', 'file-export']" class="mr-1" />导出
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>

      <!-- 资源列表 -->
      <div class="table-container">
        <el-table v-loading="tableLoading" border :data="resourceData" size="small"
          @selection-change="handleSelectionChange"
          :header-cell-style="{ backgroundColor: '#f5f7fa', color: '#606266', fontWeight: 'normal' }"
          :cell-style="{ padding: '4px 0' }" stripe>
          <el-table-column type="selection" align="center" width="45" />
          <el-table-column fixed="left" label="ID" prop="id" align="center" width="55" />

          <!-- 资源名称和封面 -->
          <el-table-column label="资源" prop="resource_name" fixed="left" min-width="160">
            <template #default="{ row }">
              <div class="resource-name-cell">
                <el-image :src="row.cover_url" :preview-src-list="[row.cover_url]" fit="cover"
                  class="resource-thumbnail">
                  <template #error>
                    <div class="image-placeholder">
                      <font-awesome-icon :icon="['fas', 'image']" />
                    </div>
                  </template>
                </el-image>
                <div class="resource-info">
                  <span class="resource-title">{{ row.resource_name }}</span>
                  <div class="resource-desc" v-if="row.description">{{ row.description }}</div>
                </div>
              </div>
            </template>
          </el-table-column>

          <!-- Premium标志 -->
          <el-table-column label="Premium" align="center" width="90">
            <template #default="{ row }">
              <div class="premium-badge" v-if="row.is_premium">
                <font-awesome-icon :icon="['fas', 'crown']" class="crown-icon" />
                <span>Premium</span>
              </div>
              <div v-else class="standard-badge">
                <font-awesome-icon :icon="['fas', 'bookmark']" class="standard-icon" />
                <span>标准版</span>
              </div>
            </template>
          </el-table-column>

          <!-- 资源分类 -->
          <el-table-column label="分类" align="center" width="100">
            <template #default="{ row }">
              <div class="category-wrapper">
                <el-tag size="small" :type="getCategoryTagType(row.category.name)" effect="dark">
                  <font-awesome-icon :icon="getCategoryIcon(row.category.name)" class="mr-1" />
                  {{ row.category.name }}
                </el-tag>
              </div>
            </template>
          </el-table-column>

          <!-- 作者信息 -->
          <el-table-column label="上传者" align="center" width="100">
            <template #default="{ row }">
              <div class="author-info" v-if="row.author">
                <el-avatar :size="22" :src="row.author.avatar" class="author-avatar">
                  <font-awesome-icon :icon="['fas', 'user']" />
                </el-avatar>
                <span class="author-name">{{ row.author.username }}</span>
              </div>
              <span v-else class="text-gray-400">未知</span>
            </template>
          </el-table-column>

          <!-- 资源标签 -->
          <el-table-column label="标签">
            <template #default="{ row }">
              <div class="tags-container">
                <div v-for="tag in row.tags" :key="tag.pivot?.id || tag.name" class="tag-item">
                  <font-awesome-icon :icon="['fas', 'tag']" class="tag-icon" />
                  {{ tag.name }}
                </div>
                <span v-if="!row.tags || row.tags.length === 0" class="text-gray-400">暂无标签</span>
              </div>
            </template>
          </el-table-column>

          <!-- 平台和版本 -->
          <el-table-column label="平台/版本" width="100">
            <template #default="{ row }">
              <div class="platform-container">
                <div class="platform-item">
                  <font-awesome-icon :icon="getPlatformIcon(row.platform)" />
                  <span class="ml-1">{{ row.platform }}</span>
                </div>
                <div class="version-item">
                  <font-awesome-icon :icon="['fas', 'code-branch']" />
                  <span class="ml-1">{{ row.version }}</span>
                </div>
              </div>
            </template>
          </el-table-column>

          <!-- 资源大小 -->
          <el-table-column label="大小" align="center">
            <template #default="{ row }">
              <div class="size-info">
                <font-awesome-icon :icon="['fas', 'hdd']" />
                <span class="ml-1">{{ formatFileSize(row.resource_size) }}</span>
              </div>
            </template>
          </el-table-column>

          <!-- 云盘下载 -->
          <el-table-column label="下载/链接" width="120">
            <template #default="{ row }">
              <div class="download-methods">
                <!-- Web网站类型资源显示链接 -->
                <template v-if="row.platform === 'Web网站' && row.web_link">
                  <div class="download-tag cloud-web">
                    <font-awesome-icon :icon="['fas', 'globe']" class="mr-1" />
                    <a :href="row.web_link" target="_blank" class="web-link">访问网站</a>
                  </div>
                </template>
                <!-- 其他类型资源显示下载方式 -->
                <template v-else-if="row.downloadLinks && row.downloadLinks.length">
                  <div v-for="method in row.downloadLinks.filter(m =>
                    ['蓝奏网盘', '百度网盘', '夸克网盘'].includes(m.method_name)
                  )" :key="method.id" :class="['download-tag', getCloudClass(method.method_name)]">
                    <font-awesome-icon :icon="['fas', getDownloadMethodIcon(method.method_name)]" class="mr-1" />
                    {{ method.method_name }}
                  </div>
                </template>
                <span v-else class="text-gray-400">暂无</span>
              </div>
            </template>
          </el-table-column>

          <!-- 状态 -->
          <el-table-column label="状态" align="center" width="80">
            <template #default="{ row }">
              <div class="status-tag" :class="row.delete_time ? 'status-deleted' : 'status-active'">
                <font-awesome-icon :icon="row.delete_time ? ['fas', 'ban'] : ['fas', 'check-circle']" class="mr-1" />
                {{ row.delete_time ? '已删除' : '正常' }}
              </div>
            </template>
          </el-table-column>

          <!-- 统计信息 -->
          <el-table-column label="统计数据" align="center">
            <template #default="{ row }">
              <div class="stats-card">
                <div class="stat-item view">
                  <font-awesome-icon :icon="['fas', 'eye']" />
                  <span>{{ row.view_count.toLocaleString() }}</span>
                </div>
                <div class="stat-item download">
                  <font-awesome-icon :icon="['fas', 'cloud-arrow-down']" />
                  <span>{{ row.download_count.toLocaleString() }}</span>
                </div>
                <div class="stat-item favorite">
                  <font-awesome-icon :icon="['fas', 'heart']" />
                  <span>{{ (row.favorite_count || 0).toLocaleString() }}</span>
                </div>
              </div>
            </template>
          </el-table-column>

          <!-- 更新时间 -->
          <el-table-column label="更新时间">
            <template #default="{ row }">
              <div class="time-info">
                <font-awesome-icon :icon="['fas', 'clock']" class="mr-1" />
                {{ formatDateTime(row.update_time) }}
              </div>
            </template>
          </el-table-column>

          <!-- 操作按钮 -->
          <el-table-column fixed="right" label="操作" align="center" width="120">
            <template #default="scope">
              <div class="action-buttons">
                <el-button size="small" type="primary" text @click="handleEditResource(scope.row)">
                  <font-awesome-icon :icon="['fas', 'edit']" class="mr-1" />编辑
                </el-button>
                <el-divider direction="vertical" />
                <el-button size="small" type="primary" text
                  @click="scope.row.delete_time ? handleRestore(scope.row) : handleDelete(scope.row)">
                  <font-awesome-icon :icon="scope.row.delete_time ? ['fas', 'undo'] : ['fas', 'trash']" class="mr-1" />
                  {{ scope.row.delete_time ? '恢复' : '删除' }}
                </el-button>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- 分页 -->
      <el-pagination v-model:current-page="pageConfig.currentPage" v-model:page-size="pageConfig.pageSize"
        style="margin-top: 12px; justify-content: flex-end" :page-sizes="[5, 10, 20, 30]" :background="true"
        layout="total, sizes, prev, pager, next, jumper" :total="pageConfig.total" @size-change="handleSizeChange"
        @current-change="handleCurrentChange" size="small" />
    </el-card>

    <!-- 新增/编辑资源对话框 -->
    <ResourceForm v-model:visible="showResourceForm" :resource-id="currentResourceId" v-model:loading="formLoading"
      @refresh="fetchResourceList" />

    <!-- 资源详情对话框 -->
    <ResourceDetail v-if="showResourceDetail" v-model:visible="showResourceDetail" :resource-id="currentResourceId" />

    <!-- 回收站对话框 -->
    <el-dialog v-model="showRecycleBinModal" title="资源回收站" width="80%" :close-on-click-modal="true">
      <div class="recycle-bin-header">
        <el-button type="primary" :disabled="selectedRecycleBinRows.length === 0" @click="handleBatchRestore">
          <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />
          批量恢复 ({{ selectedRecycleBinRows.length }})
        </el-button>
        <el-button type="danger" :disabled="selectedRecycleBinRows.length === 0" @click="handleBatchPermanentDelete">
          <font-awesome-icon :icon="['fas', 'trash-alt']" class="mr-1" />
          批量彻底删除 ({{ selectedRecycleBinRows.length }})
        </el-button>
        <el-button @click="handleRefreshRecycleBin">
          <font-awesome-icon :icon="['fas', 'sync']" class="mr-1" />
          刷新
        </el-button>
      </div>

      <el-table :data="recycleBinData" v-loading="recycleBinLoading" @selection-change="handleRecycleBinSelectionChange"
        style="width: 100%; margin-top: 16px;">
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="resource_name" label="资源名称" />
        <el-table-column prop="category_name" label="分类" width="120" />
        <el-table-column prop="platform" label="平台" width="100" />
        <el-table-column prop="delete_time" label="删除时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.delete_time) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleRestore(row)">
              <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />
              恢复
            </el-button>
            <el-button type="danger" size="small" @click="handlePermanentDelete(row)">
              <font-awesome-icon :icon="['fas', 'trash-alt']" class="mr-1" />
              彻底删除
            </el-button>
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
import { ref, computed, reactive, onMounted, defineAsyncComponent, defineOptions, h } from "vue";
import {
  Search,
  RefreshLeft,
  RefreshRight,
  CirclePlus,
  Printer,
  Upload,
  Edit,
  Delete,
  View,
  Picture
} from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import dayjs from "dayjs";
import { message } from "@/utils/message";
import {
  getResourceList,
  deleteResource,
  restoreResource,
  batchDeleteResource,
  batchRestoreResource,
  getCategoryList,
  ResourceItem,
  getResourceDetail
} from "@/api/resource";

// 异步导入组件
const ResourceForm = defineAsyncComponent(() => import("@/views/basic/resource/ResourceForm.vue"));
const ResourceDetail = defineAsyncComponent(() => import("@/views/basic/resource/ResourceDetail.vue"));

defineOptions({
  name: "resource"
});

// 按钮尺寸 - 响应式
const buttonSize = computed<'small' | 'default' | 'large'>(() => {
  // 只在移动端使用small尺寸按钮
  return window.innerWidth <= 768 ? 'small' : 'default';
});

// 搜索表单
const searchForm = reactive({
  resource_name: "",
  category_id: "",
  platform: "",
  status: "",
  is_premium: undefined as undefined | number
});

// 表格数据
const resourceData = ref<ResourceItem[]>([]);
const tableLoading = ref(false);
const selectedResources = ref<ResourceItem[]>([]);

// 分页配置
const pageConfig = ref({
  currentPage: 1,
  pageSize: 5,
  total: 0
});

// 分类选项
const categoryOptions = ref<{ id: number; name: string }[]>([]);

// 平台选项
const platformOptions = ref(["Windows", "MacOS", "Linux", "Android", "iOS", "Web网站"]);

// 新增/编辑资源相关
const showResourceForm = ref(false);
const currentResourceId = ref<number | null>(null);
const formLoading = ref(false);

// 资源详情相关
const showResourceDetail = ref(false);

// 回收站相关
const showRecycleBinModal = ref(false);
const recycleBinData = ref<ResourceItem[]>([]);
const recycleBinLoading = ref(false);
const selectedRecycleBinRows = ref<ResourceItem[]>([]);

// 回收站分页配置
const recycleBinPageConfig = ref({
  currentPage: 1,
  pageSize: 10,
  total: 0
});

// 格式化日期时间
const formatDateTime = (dateTime: string) => {
  if (!dateTime) return "-";
  return dayjs(dateTime).format("YYYY-MM-DD HH:mm");
};

// 格式化文件大小
const formatFileSize = (size: number | string | null | undefined) => {
  if (size === null || size === undefined || size === '') return "-";

  // 确保转换为数字
  const sizeNum = Number(size);

  // 检查是否为有效数字
  if (isNaN(sizeNum)) return "-";

  if (sizeNum < 1) return sizeNum.toFixed(2) + " KB";
  if (sizeNum < 1000) return sizeNum.toFixed(2) + " MB";
  return (sizeNum / 1000).toFixed(2) + " GB";
};

// 获取平台对应的图标
const getPlatformIcon = (platform: string): string[] => {
  const iconMap: Record<string, string[]> = {
    "Windows": ['fab', 'windows'],
    "MacOS": ['fab', 'apple'],
    "Linux": ['fab', 'linux'],
    "Android": ['fab', 'android'],
    "iOS": ['fab', 'apple'],
    "Web网站": ['fas', 'globe']
  };
  return iconMap[platform] || ['fas', 'desktop'];
};

// 获取云盘对应的标签类型
const getCloudTagType = (methodName: string): 'success' | 'warning' | 'info' | 'primary' | 'danger' => {
  const typeMap: Record<string, 'success' | 'warning' | 'info' | 'primary' | 'danger'> = {
    "蓝奏网盘": "primary",
    "夸克网盘": "warning",
    "百度网盘": "success"
  };

  return typeMap[methodName] || "info";
};

// 获取下载方式对应的图标
const getDownloadMethodIcon = (methodName: string): string => {
  const iconMap: Record<string, string> = {
    "蓝奏网盘": "cloud",
    "夸克网盘": "hdd",
    "百度网盘": "database"
  };

  return iconMap[methodName] || "link";
};

// 获取下载方式对应的云盘类名
const getCloudClass = (methodName: string): string => {
  const classMap: Record<string, string> = {
    "蓝奏网盘": "cloud-lanzouyun",
    "夸克网盘": "cloud-quark",
    "百度网盘": "cloud-baidu"
  };

  return classMap[methodName] || "cloud-default";
};

// 获取分类对应的图标
const getCategoryIcon = (categoryName: string): string[] => {
  const iconMap: Record<string, string[]> = {
    "默认": ['fas', 'circle'],
    "实用网站": ['fas', 'globe'],
    "生活": ['fas', 'coffee'],
    "科技": ['fas', 'microchip'],
    "软件": ['fas', 'laptop-code'],
    "资源": ['fas', 'box-archive'],
    "数码": ['fas', 'mobile-screen'],
    // 其他类别使用文件夹图标
  };
  return iconMap[categoryName] || ['fas', 'folder'];
};

// 获取分类对应的标签类型
const getCategoryTagType = (categoryName: string): 'success' | 'warning' | 'info' | 'primary' | 'danger' => {
  const typeMap: Record<string, 'success' | 'warning' | 'info' | 'primary' | 'danger'> = {
    "默认": "info",
    "实用网站": "primary",
    "生活": "success",
    "科技": "danger",
    "软件": "primary",
    "资源": "warning",
    "数码": "success"
  };
  return typeMap[categoryName] || "info";
};

// 获取资源列表
const fetchResourceList = async () => {
  try {
    tableLoading.value = true;

    // 构建请求参数
    const params = {
      page: pageConfig.value.currentPage,
      page_size: pageConfig.value.pageSize,
      resource_name: searchForm.resource_name || undefined,
      category_id: searchForm.category_id || undefined,
      platform: searchForm.platform || undefined,
      status: searchForm.status || undefined,
      is_premium: searchForm.is_premium
    };

    // 调用API获取数据
    const res: any = await getResourceList(params);

    if (res.code === 200) {
      // 修复数据解析：正常数据中 res.data.list 直接是数组
      resourceData.value = res.data.list || [];
      // 使用pagination中的分页信息
      pageConfig.value.total = res.data.pagination?.total || 0;
      console.log("正常资源数据:", res.data);
      console.log("解析后资源数据:", resourceData.value);
    } else {
      message(res.message || "获取资源列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取资源列表出错:", error);
    message("获取资源列表失败", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 获取分类选项
const fetchCategories = async () => {
  try {
    const res: any = await getCategoryList(); // 资源模块使用的是不同的API接口
    console.log('分类API返回的原始数据:', res);

    if (res.code === 200) {
      let categoryData: any[] = [];

      // 适配不同的数据格式
      if (Array.isArray(res.data)) {
        // 如果res.data直接是数组
        categoryData = res.data;
      } else if (res.data && res.data.list && Array.isArray(res.data.list)) {
        // 如果res.data.list是数组
        categoryData = res.data.list;
      } else if (res.data && typeof res.data === 'object') {
        // 如果res.data是对象，尝试获取其中的数组
        const values = Object.values(res.data);
        const arrayValue = values.find(val => Array.isArray(val));
        categoryData = arrayValue ? arrayValue as any[] : [];
      }

      // 只选择parent_id为0的项作为大类别
      categoryOptions.value = categoryData.filter(
        (item: any) => item && (item.parent_id === 0 || item.parent_id === null)
      );

      console.log('处理后的分类数据:', categoryOptions.value);
    } else {
      console.error("获取分类选项失败:", res.message || res.msg || '未知错误');
    }
  } catch (error) {
    console.error("获取分类选项出错:", error);
  }
};

// 搜索
const handleSearch = () => {
  pageConfig.value.currentPage = 1;
  fetchResourceList();
};

// 重置搜索表单
const resetSearchForm = () => {
  searchForm.resource_name = "";
  searchForm.category_id = "";
  searchForm.platform = "";
  searchForm.status = "";
  searchForm.is_premium = undefined;

  pageConfig.value.currentPage = 1;
  fetchResourceList();
};

// 表格选中行变化
const handleSelectionChange = (selection: ResourceItem[]) => {
  selectedResources.value = selection;
};

// 单个删除
const handleDelete = (row: ResourceItem) => {
  // 创建响应式变量用于存储复选框状态
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h('div', null, [
      h('p', null, `确认删除资源 "${row.resource_name}" 吗？`),
      h('div', { style: 'margin-top: 16px; display: flex; align-items: center;' }, [
        h('input', {
          type: 'checkbox',
          style: 'width: 16px; height: 16px; margin-right: 8px; cursor: pointer;',
          checked: isRealDelete.value,
          onInput: (event) => {
            isRealDelete.value = (event.target as HTMLInputElement).checked;
          }
        }),
        h('span', null, '永久删除（此操作将无法恢复）')
      ])
    ]);
  };

  ElMessageBox({
    title: '删除确认',
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning',
    beforeClose: (action, instance, done) => {
      if (action === 'confirm') {
        instance.confirmButtonLoading = true;

        // 根据复选框状态决定是否真实删除
        deleteResource(row.id, isRealDelete.value ? { real: true } : undefined)
          .then(res => {
            if (res.code === 200) {
              message(isRealDelete.value ? "永久删除成功" : "软删除成功", { type: "success" });
              fetchResourceList();
            } else {
              message(res.message || "删除失败", { type: "error" });
            }
          })
          .catch(error => {
            console.error("删除出错:", error);
            message("删除失败", { type: "error" });
          })
          .finally(() => {
            instance.confirmButtonLoading = false;
            done();
          });
      } else {
        done();
      }
    }
  }).catch(() => {
    // 用户取消操作
  });
};

// 批量删除
const handleBatchDelete = () => {
  if (selectedResources.value.length === 0) return;

  // 创建响应式变量用于存储复选框状态
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h('div', null, [
      h('p', null, `确认删除选中的 ${selectedResources.value.length} 个资源吗？`),
      h('div', { style: 'margin-top: 16px; display: flex; align-items: center;' }, [
        h('input', {
          type: 'checkbox',
          style: 'width: 16px; height: 16px; margin-right: 8px; cursor: pointer;',
          checked: isRealDelete.value,
          onInput: (event) => {
            isRealDelete.value = (event.target as HTMLInputElement).checked;
          }
        }),
        h('span', null, '永久删除（此操作将无法恢复）')
      ])
    ]);
  };

  ElMessageBox({
    title: '批量删除确认',
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning',
    beforeClose: (action, instance, done) => {
      if (action === 'confirm') {
        instance.confirmButtonLoading = true;

        try {
          const idList = selectedResources.value.map(item => item.id);

          // 根据复选框状态决定是否真实删除
          batchDeleteResource(idList, isRealDelete.value ? { real: true } : undefined)
            .then(res => {
              if (res.code === 200) {
                message(isRealDelete.value ? "永久批量删除成功" : "批量软删除成功", { type: "success" });
                fetchResourceList();
              } else {
                message(res.message || "批量删除失败", { type: "error" });
              }
            })
            .catch(error => {
              console.error("批量删除出错:", error);
              message("批量删除失败", { type: "error" });
            })
            .finally(() => {
              instance.confirmButtonLoading = false;
              done();
            });
        } catch (error) {
          console.error("批量删除出错:", error);
          message("批量删除失败", { type: "error" });
          instance.confirmButtonLoading = false;
          done();
        }
      } else {
        done();
      }
    }
  }).catch(() => {
    // 用户取消操作
  });
};

// 原有的恢复资源函数已被新的回收站恢复函数替代

// 添加资源
const handleAddResource = () => {
  currentResourceId.value = null;
  formLoading.value = true;
  showResourceForm.value = true;
};

// 编辑资源
const handleEditResource = (row: ResourceItem) => {
  currentResourceId.value = row.id;
  formLoading.value = true;
  showResourceForm.value = true;
};

// 查看资源
const handleViewResource = (row: ResourceItem) => {
  currentResourceId.value = row.id;
  showResourceDetail.value = true;
};

// 分页相关方法
const handleSizeChange = (val: number) => {
  pageConfig.value.pageSize = val;
  fetchResourceList();
};

const handleCurrentChange = (val: number) => {
  pageConfig.value.currentPage = val;
  fetchResourceList();
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
    const res: any = await getResourceList({
      page_num: page,
      page_size: recycleBinPageConfig.value.pageSize,
      include_deleted: true // 查询软删除的资源
    });

    if (res.code === 200) {
      // 智能数据解析：处理两种不同的数据结构
      let dataArray = [];
      let totalCount = 0;

      if (Array.isArray(res.data.list)) {
        // 情况1：res.data.list 直接是数组（正常数据结构）
        dataArray = res.data.list;
        totalCount = res.data.pagination?.total || 0;
        console.log('回收站数据结构：直接数组');
      } else if (res.data.list && res.data.list.data) {
        // 情况2：res.data.list 是对象，真正的数据在 res.data.list.data 中
        dataArray = res.data.list.data;
        totalCount = res.data.list.total || 0;
        console.log('回收站数据结构：嵌套对象');
      }

      recycleBinData.value = dataArray;
      recycleBinPageConfig.value.total = totalCount;
      recycleBinPageConfig.value.currentPage = page;
      console.log('回收站原始数据:', res.data);
      console.log('回收站解析后数据:', recycleBinData.value);
    }
  } catch (error) {
    console.error('获取回收站数据失败:', error);
    message('获取回收站数据失败', { type: 'error' });
  } finally {
    recycleBinLoading.value = false;
  }
};

// 回收站表格选择变化
const handleRecycleBinSelectionChange = (selection: ResourceItem[]) => {
  selectedRecycleBinRows.value = selection;
};

// 刷新回收站
const handleRefreshRecycleBin = () => {
  fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
};

// 回收站分页处理
const handleRecycleBinPageChange = (page: number) => {
  fetchRecycleBinData(page);
};

const handleRecycleBinSizeChange = (size: number) => {
  recycleBinPageConfig.value.pageSize = size;
  fetchRecycleBinData(1);
};

// 恢复单个资源
const handleRestore = async (row: ResourceItem) => {
  try {
    ElMessageBox.confirm(`确认恢复资源 "${row.resource_name}"?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }).then(async () => {
      const res: any = await restoreResource(row.id);

      if (res.code === 200) {
        message(`资源 "${row.resource_name}" 已恢复`, { type: 'success' });

        // 刷新回收站数据
        fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
        // 刷新主表格数据
        fetchResourceList();
      } else {
        message(res.msg || '恢复失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('恢复资源失败:', error);
    message('恢复资源失败', { type: 'error' });
  }
};

// 彻底删除单个资源
const handlePermanentDelete = async (row: ResourceItem) => {
  try {
    ElMessageBox.confirm(`确认彻底删除资源 "${row.resource_name}"? 此操作不可恢复!`, "警告", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "error"
    }).then(async () => {
      const res: any = await deleteResource(row.id, { real: true });

      if (res.code === 200) {
        message(`资源 "${row.resource_name}" 已彻底删除`, { type: 'success' });

        // 刷新回收站数据
        fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
      } else {
        message(res.msg || '彻底删除失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('彻底删除资源失败:', error);
    message('彻底删除资源失败', { type: 'error' });
  }
};

// 批量恢复
const handleBatchRestore = async () => {
  if (selectedRecycleBinRows.value.length === 0) return;

  try {
    ElMessageBox.confirm(`确认恢复选中的 ${selectedRecycleBinRows.value.length} 个资源?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }).then(async () => {
      const ids = selectedRecycleBinRows.value.map(row => row.id);
      const res: any = await batchRestoreResource(ids);

      if (res.code === 200) {
        message(`成功恢复 ${selectedRecycleBinRows.value.length} 个资源`, { type: 'success' });

        // 清空选择
        selectedRecycleBinRows.value = [];
        // 刷新回收站数据
        fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
        // 刷新主表格数据
        fetchResourceList();
      } else {
        message(res.msg || '批量恢复失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('批量恢复资源失败:', error);
    message('批量恢复失败', { type: 'error' });
  }
};

// 批量彻底删除
const handleBatchPermanentDelete = async () => {
  if (selectedRecycleBinRows.value.length === 0) return;

  try {
    ElMessageBox.confirm(`确认彻底删除选中的 ${selectedRecycleBinRows.value.length} 个资源? 此操作不可恢复!`, "警告", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "error"
    }).then(async () => {
      const ids = selectedRecycleBinRows.value.map(row => row.id);
      const res: any = await batchDeleteResource(ids, { real: true });

      if (res.code === 200) {
        message(`成功彻底删除 ${selectedRecycleBinRows.value.length} 个资源`, { type: 'success' });

        // 清空选择
        selectedRecycleBinRows.value = [];
        // 刷新回收站数据
        fetchRecycleBinData(recycleBinPageConfig.value.currentPage);
      } else {
        message(res.msg || '批量彻底删除失败', { type: 'error' });
      }
    });
  } catch (error) {
    console.error('批量彻底删除资源失败:', error);
    message('批量彻底删除失败', { type: 'error' });
  }
};

onMounted(() => {
  fetchResourceList();
  fetchCategories();
});
</script>

<style lang="scss" scoped>
.resource-container {
  @media (max-width: 768px) {
    padding: 6px;
  }

  .box-card {
    transition: all 0.3s ease;
    border-radius: 4px;
    overflow: hidden;

    @media (max-width: 768px) {
      margin: 0;
      border-radius: 0;
      box-shadow: none;
    }
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .header-title {
      font-size: 15px;
      font-weight: 500;
      margin: 0;
      display: flex;
      align-items: center;
      color: #303133;
    }
  }

  .table-container {
    margin-top: 12px;
    border-radius: 3px;
    overflow: auto;
    border: 1px solid #ebeef5;

    @media (max-width: 768px) {
      margin: 0;
      border-radius: 0;
    }


  }

  .search-form {
    margin-bottom: 16px;
    padding: 14px 16px;
    border-radius: 4px;
    background-color: #f9f9f9;
    border: 1px solid #ebeef5;
    transition: all 0.3s ease;

    .search-area {
      display: flex;
      flex-wrap: wrap;

      .search-items {
        flex: 1;
        width: 100%;
      }
    }

    @media (max-width: 768px) {
      padding: 12px 10px;
      margin-bottom: 12px;
    }

    :deep(.el-form-item) {
      margin-bottom: 10px;

      .el-form-item__label {
        padding-right: 6px;
        line-height: 32px;
        font-size: 13px;
      }

      .el-form-item__content {
        line-height: 32px;
      }

      .el-input__wrapper,
      .el-select .el-input__wrapper {
        box-shadow: 0 0 0 1px #dcdfe6 inset;
        padding-left: 8px;
        padding-right: 8px;
        border-radius: 3px;

        &:hover {
          box-shadow: 0 0 0 1px #c0c4cc inset;
        }

        &.is-focus {
          box-shadow: 0 0 0 1px #409eff inset;
        }
      }

      @media (max-width: 768px) {
        margin-bottom: 8px;
      }
    }

    .search-buttons-col {
      display: flex;
      align-items: flex-end;
    }

    .search-buttons {
      display: flex;
      align-items: flex-end;
      gap: 8px;
      margin-bottom: 10px;
      height: 100%;

      @media (max-width: 991px) {
        width: 100%;
        justify-content: flex-end;
      }

      @media (max-width: 768px) {
        justify-content: center;
        margin-top: 4px;
      }

      .el-button {
        min-width: 80px;
      }
    }
  }

  .action-toolbar {
    display: flex;
    justify-content: space-between;
    margin: 14px 0;
    align-items: center;
    padding: 8px 0;
    border-top: 1px solid #ebeef5;
    border-bottom: 1px solid #ebeef5;

    @media (max-width: 768px) {
      flex-direction: column;
      gap: 8px;
      margin: 10px 0;

      .left-actions,
      .right-actions {
        width: 100%;
        justify-content: center;
      }
    }

    .left-actions,
    .right-actions {
      display: flex;
      gap: 8px;
    }

    .btn-with-icon {
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      padding: 0 12px;
      border-radius: 3px;
      font-size: 13px;

      &:hover {
        opacity: 0.9;
      }
    }
  }

  .resource-name-cell {
    display: flex;
    align-items: center;
    text-align: left;

    .resource-thumbnail {
      width: 32px;
      height: 32px;
      margin-right: 8px;
      border-radius: 3px;
      flex-shrink: 0;
      object-fit: cover;
      overflow: hidden;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
      transition: all 0.2s ease;
    }

    .image-placeholder {
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f5f7fa;
      border-radius: 3px;
      color: #c0c4cc;
      font-size: 12px;
    }

    .resource-info {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;

      .resource-title {
        font-size: 12px;
        font-weight: normal;
        color: #303133;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
      }

      .resource-desc {
        font-size: 10px;
        color: #909399;
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
      }
    }
  }

  .premium-badge {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #FF6CAB, #7366FF);
    color: white;
    font-size: 10px;
    padding: 0 5px;
    border-radius: 3px;
    height: 18px;
    box-shadow: 0 1px 3px rgba(115, 102, 255, 0.2);

    .crown-icon {
      color: #FFD700;
      margin-right: 3px;
      font-size: 9px;
    }
  }

  .standard-badge {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #409EFF, #64B5F6);
    color: white;
    font-size: 10px;
    padding: 0 5px;
    border-radius: 3px;
    height: 18px;
    box-shadow: 0 1px 3px rgba(64, 158, 255, 0.2);

    .standard-icon {
      color: white;
      margin-right: 3px;
      font-size: 9px;
    }
  }

  .download-methods {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .download-tag {
    display: inline-flex;
    align-items: center;
    margin: 0 !important;
    padding: 0 5px;
    height: 18px;
    line-height: 18px;
    font-size: 10px;
    transition: all 0.2s ease;
    border-radius: 3px;

    &:hover {
      transform: translateY(-1px);
    }

    &.cloud-lanzouyun {
      background-color: rgba(64, 158, 255, 0.15);
      color: #409EFF;
    }

    &.cloud-quark {
      background-color: rgba(230, 162, 60, 0.15);
      color: #e6a23c;
    }

    &.cloud-baidu {
      background-color: rgba(103, 194, 58, 0.15);
      color: #67c23a;
    }

    &.cloud-web {
      background-color: rgba(64, 158, 255, 0.15);
      color: #409EFF;
    }
  }

  .web-link {
    color: inherit;
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }

  /* Compact stats area */
  .stats-card {
    display: flex;
    justify-content: space-around;
    gap: 3px;

    .stat-item {
      display: flex;
      align-items: center;
      gap: 3px;
      padding: 1px 5px;
      border-radius: 3px;
      font-size: 10px;
      white-space: nowrap;

      &.view {
        color: #409EFF;
        background-color: rgba(64, 158, 255, 0.15);
      }

      &.download {
        color: #67C23A;
        background-color: rgba(103, 194, 58, 0.15);
      }

      &.favorite {
        color: #F56C6C;
        background-color: rgba(245, 108, 108, 0.15);
      }
    }
  }

  .action-buttons {
    display: flex;
    justify-content: center;
    align-items: center;

    .el-button {
      height: 20px;
      padding: 0 4px;
      margin: 0;
      font-size: 11px;
      color: #409EFF;

      &:hover {
        color: #79bbff;
      }
    }

    .el-divider--vertical {
      height: 12px;
      margin: 0 3px;
    }
  }

  .platform-container {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;

    .platform-item,
    .version-item {
      display: flex;
      align-items: center;
      color: #606266;
      font-size: 11px;
    }
  }

  .size-info {
    font-size: 11px;
    color: #606266;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 3px;
  }

  .time-info {
    font-size: 11px;
    color: #606266;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .category-wrapper {
    display: flex;
    justify-content: center;

    .el-tag {
      padding: 2px 6px;
      height: 20px;
      line-height: 1.2;
      font-size: 10px;
      border: none;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
      transition: all 0.2s ease;

      &:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
      }
    }
  }

  .tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
  }

  .tag-item {
    display: inline-flex;
    align-items: center;
    background-color: rgba(64, 158, 255, 0.1);
    color: #409EFF;
    font-size: 10px;
    padding: 0 5px;
    border-radius: 3px;
    height: 18px;
    transition: all 0.2s ease;
    border: 1px solid rgba(64, 158, 255, 0.2);

    &:hover {
      background-color: rgba(64, 158, 255, 0.2);
      transform: translateY(-1px);
    }

    .tag-icon {
      margin-right: 3px;
      font-size: 8px;
      color: #409EFF;
    }
  }

  .status-tag {
    display: inline-flex;
    align-items: center;
    font-size: 10px;
    padding: 0 5px;
    border-radius: 3px;
    height: 18px;

    &.status-active {
      background-color: rgba(103, 194, 58, 0.15);
      color: #67C23A;
    }

    &.status-deleted {
      background-color: rgba(245, 108, 108, 0.15);
      color: #F56C6C;
    }
  }

  .author-info {
    display: flex;
    align-items: center;
    gap: 4px;

    .author-avatar {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      object-fit: cover;
    }

    .author-name {
      font-size: 11px;
      color: #606266;
    }
  }

  .text-gray-400 {
    color: #909399;
    font-size: 11px;
  }
}

// 暗黑模式支持
:deep(.dark) {
  .premium-badge {
    background: linear-gradient(135deg, #b94d7c, #5b52cc);
  }

  .resource-title {
    color: var(--el-color-primary-light-3);
  }

  .search-form {
    background-color: var(--el-bg-color-overlay);
  }

  .stats-card {
    .stat-item {
      &.view {
        background-color: rgba(64, 158, 255, 0.15);
      }

      &.download {
        background-color: rgba(103, 194, 58, 0.15);
      }

      &.favorite {
        background-color: rgba(245, 108, 108, 0.15);
      }
    }
  }

  .box-card:hover {
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
  }

  .recycle-bin-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
  }
}
</style>