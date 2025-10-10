<template>
  <div class="articles-container">
    <el-dialog v-model="showAddOrEditModal" title="添加/修改文章" :before-close="handleClose" width="85%" top="5vh"
      fullscreen>
      <AddOrEdit v-if="showAddOrEditModal" :formData="currentArticle" @submit-success="handleSubmitSuccess"
        @cancel="handleClose" />
    </el-dialog>
    <el-card class="article-card" shadow="false">
      <template #header>
        <div class="header-wrapper">
          <span class="header-title">文章管理</span>
          <div class="search-wrapper">
            <el-row :gutter="16" class="search-row">
              <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="5" class="search-item">
                <el-input v-model="searchForm.title" placeholder="搜索标题" clearable :prefix-icon="Search"
                  :size="buttonSize" />
              </el-col>

              <el-col :xs="12" :sm="12" :md="8" :lg="4" :xl="4" class="search-item">
                <el-select v-model="searchForm.category_id" placeholder="分类" style="width: 100%" :size="buttonSize"
                  clearable :loading="categoryLoading">
                  <el-option v-for="item in categoriesList" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-col>

              <el-col :xs="12" :sm="12" :md="8" :lg="4" :xl="3" class="search-item">
                <el-select v-model="searchForm.status" placeholder="状态" style="width: 100%" :size="buttonSize"
                  clearable>
                  <el-option v-for="(label, value) in statusOptions" :key="value" :label="label"
                    :value="Number(value)" />
                </el-select>
              </el-col>

              <el-col :xs="12" :sm="8" :md="6" :lg="3" :xl="3" class="search-item">
                <el-select v-model="searchForm.is_top" placeholder="置顶" style="width: 100%" :size="buttonSize"
                  clearable>
                  <el-option label="是" :value="1" />
                  <el-option label="否" :value="0" />
                </el-select>
              </el-col>

              <el-col :xs="12" :sm="12" :md="8" :lg="5" :xl="4" class="search-item">
                <el-select v-model="searchForm.author_id" filterable remote reserve-keyword placeholder="搜索用户"
                  :remote-method="remoteSearchUsers" :loading="userSelectLoading" style="width: 100%" :size="buttonSize"
                  clearable @focus="handleUserSelectFocus">
                  <el-option v-for="item in userOptions" :key="item.id" :label="item.username" :value="item.id">
                    <div class="user-option">
                      <span class="user-name">{{ item.username }}</span>
                      <span class="user-id">(ID:{{ item.id }})</span>
                    </div>
                  </el-option>
                </el-select>
              </el-col>

              <el-col :xs="12" :sm="8" :md="6" :lg="3" :xl="3" class="search-item">
                <el-select v-model="searchForm.delete_status" placeholder="删除状态" style="width: 100%" :size="buttonSize"
                  clearable>
                  <el-option label="未删除" value="" />
                  <el-option label="包含已删除" value="with_deleted" />
                  <el-option label="仅已删除" value="only_deleted" />
                </el-select>
              </el-col>

              <el-col :xs="12" :sm="8" :md="6" :lg="4" :xl="3" class="search-item">
                <el-select v-model="sortEnabled" placeholder="排序方式" style="width: 100%" :size="buttonSize">
                  <el-option label="默认排序" :value="true" />
                  <el-option label="不排序" :value="false" />
                </el-select>
              </el-col>

              <el-col :xs="12" :sm="8" :md="6" :lg="4" :xl="3" class="search-item search-buttons">
                <div class="button-group">
                  <el-button type="primary" :size="buttonSize" @click="handleSearch">
                    <el-icon>
                      <Search />
                    </el-icon>搜索
                  </el-button>
                  <el-button :size="buttonSize" @click="resetSearch">
                    <el-icon>
                      <RefreshRight />
                    </el-icon>重置
                  </el-button>
                </div>
              </el-col>
            </el-row>
          </div>
        </div>
      </template>

      <div class="table-toolbar">
        <div class="left-tools">
          <template v-if="selectedRows.length > 0">
            <el-button type="danger" size="default" @click="handleBatchDelete">
              <el-icon>
                <Delete />
              </el-icon>
              <span>批量删除</span>
              <span class="selected-count">({{ selectedRows.length }})</span>
            </el-button>
            <el-divider direction="vertical" />
          </template>
          <el-button type="primary" size="default" @click="handleAdd">
            <el-icon>
              <Plus />
            </el-icon>
            <span>新增文章</span>
          </el-button>
          <el-button size="default">
            <el-icon>
              <Download />
            </el-icon>
            <span>导出数据</span>
          </el-button>
        </div>
        <div class="right-tools">
          <el-space :size="4">
            <el-tooltip content="回收站" placement="top">
              <el-button size="small" @click="handleShowRecycleBin" type="danger">
                <el-icon>
                  <Delete />
                </el-icon>
                回收站
              </el-button>
            </el-tooltip>
            <el-tooltip content="刷新数据" placement="top">
              <el-button size="small" @click="fetchArticleList" type="info">
                <el-icon>
                  <Refresh />
                </el-icon>
                刷新
              </el-button>
            </el-tooltip>
            <el-tooltip content="列设置" placement="top">
              <el-button size="small" type="default">
                <el-icon>
                  <Setting />
                </el-icon>
                设置
              </el-button>
            </el-tooltip>
          </el-space>
        </div>
      </div>

    <el-table :data="tableData" style="width: 100%" v-loading="tableLoading" :fit="true"
        class="article-table modern-flat-table" size="default" @selection-change="handleSelectionChange"
        :show-header="true" element-loading-text="正在加载文章数据..." element-loading-background="rgba(255, 255, 255, 0.8)"
        :empty-text="getEmptyText()" :key="pageConfig.current_page">
        <el-table-column type="selection" width="50" align="center" />

        <!-- 文章内容 -->
        <el-table-column label="文章" min-width="320">
          <template #default="{ row }">
            <div class="article-item-simple">
              <div class="article-cover">
                <img :src="row.cover_image || defaultCoverImage" :alt="row.title"
                  @error="$event.target.src = defaultCoverImage" />
              </div>
              <div class="article-content">
                <div class="article-header">
                  <h4 class="article-title" @click="handleView(row)">{{ row.title }}</h4>
                  <div class="article-badges">
                    <el-tag v-if="row.is_top === 1" type="danger" size="small" effect="dark">置顶</el-tag>
                    <el-tag v-if="row.is_recommend === 1" type="warning" size="small" effect="dark">推荐</el-tag>
                    <el-tag v-if="row.is_original === 1" type="success" size="small" effect="dark">原创</el-tag>
                  </div>
                </div>
                <div class="article-summary">{{ getSummaryText(row) }}</div>
                <div class="article-tags" v-if="row.tags && row.tags.length > 0">
                  <el-tag v-for="tag in row.tags" :key="tag.name" size="small" type="primary" class="tag-item">
                    {{ tag.name }}
                  </el-tag>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>

        <!-- 详细信息 -->
        <el-table-column label="详细信息" min-width="220">
          <template #default="{ row }">
            <div class="meta-info-column">
              <!-- 作者信息 -->
              <div class="meta-row">
                <div class="author-content">
                  <div class="author-avatar">
                    <img :src="row.author?.avatar || '/default-avatar.png'" :alt="row.author?.username" />
                  </div>
                  <div class="author-name">{{ row.author?.username || getAuthorName(row.author_id) }}</div>
                </div>
              </div>

              <!-- 分类和状态 -->
              <div class="meta-row">
                <div class="category-status-content">
                  <el-tag size="small">
                    {{ row.category?.name || getCategoryName(row.category_id) }}
                  </el-tag>
                  <el-dropdown trigger="click" @command="(command) => handleStatusChange(row, command)">
                    <el-tag
                      :type="row.status === 0 ? 'info' : row.status === 1 ? 'success' : row.status === 2 ? 'warning' : 'danger'"
                      class="status-tag clickable" size="small">
                      {{ row.status === 0 ? '草稿' : row.status === 1 ? '已发布' : row.status === 2 ? '待审核' : '已下架' }}
                      <el-icon class="el-icon--right"><arrow-down /></el-icon>
                    </el-tag>
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item :command="0" :disabled="row.status === 0">草稿</el-dropdown-item>
                        <el-dropdown-item :command="1" :disabled="row.status === 1">已发布</el-dropdown-item>
                        <el-dropdown-item :command="2" :disabled="row.status === 2">待审核</el-dropdown-item>
                        <el-dropdown-item :command="3" :disabled="row.status === 3">已下架</el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </div>
              </div>

              <!-- 统计信息 -->
              <div class="meta-row">
                <div class="stats-content">
                  <span class="stat-item">{{ row.word_count || 0 }}字</span>
                  <span class="stat-item">{{ row.read_time || 0 }}分钟</span>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>

        <!-- 互动数据 -->
        <el-table-column label="互动" min-width="100" align="center">
          <template #default="{ row }">
            <div class="interaction-stats">
              <div class="stat-item">
                <i class="fas fa-thumbs-up"></i>
                <span>{{ row.likes_count || 0 }}</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-heart"></i>
                <span>{{ row.favorites_count || 0 }}</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-comment"></i>
                <span>{{ row.comments_count || 0 }}</span>
              </div>
            </div>
          </template>
        </el-table-column>

        <!-- 时间 -->
        <el-table-column label="时间" min-width="130">
          <template #default="{ row }">
            <div class="time-info">
              <div class="time-item">
                <i class="fas fa-clock"></i>
                <span>{{ formatTime(row.create_time) }}</span>
              </div>
              <div v-if="row.update_time !== row.create_time" class="time-item update">
                <i class="fas fa-edit"></i>
                <span>{{ formatTime(row.update_time) }}</span>
              </div>
            </div>
          </template>
        </el-table-column>

        <!-- 操作列 -->
        <el-table-column fixed="right" label="操作" width="190" align="center">
          <template #default="{ row }">
            <!-- 针对已删除文章显示恢复和彻底删除按钮 -->
            <template v-if="row.delete_time">
              <div class="action-buttons">
                <el-button type="warning" link size="small" @click="handleRestore(row)">
                  <el-icon>
                    <RefreshRight />
                  </el-icon>恢复
                </el-button>
                <el-button type="danger" link size="small" @click="handleDelete(row, true)">
                  <el-icon>
                    <Delete />
                  </el-icon>彻底删除
                </el-button>
              </div>
            </template>

            <!-- 针对正常文章显示编辑和删除按钮 -->
            <template v-else>
              <div class="action-buttons">
                <el-button type="primary" link size="small" @click="handleEdit(row)">
                  <el-icon>
                    <Edit />
                  </el-icon>编辑
                </el-button>
                <el-button type="danger" link size="small" @click="handleDelete(row)">
                  <el-icon>
                    <Delete />
                  </el-icon>删除
                </el-button>
              </div>
            </template>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination v-model:current-page="pageConfig.current_page" v-model:page-size="pageConfig.page_size"
          :page-sizes="[10, 20, 30, 50, 100]" :background="true" layout="total, sizes, prev, pager, next, jumper"
          :total="pageConfig.total" @size-change="handleSizeChange" @current-change="handleCurrentChange"
          size="small" />
      </div>
    </el-card>

    <!-- 新增/编辑文章全屏弹窗 -->
    <el-dialog v-model="showAddOrEditModal" :title="currentArticle.id ? '编辑文章' : '新增文章'" fullscreen
      :close-on-click-modal="false" :close-on-press-escape="false" @close="handleClose"
      class="article-fullscreen-dialog">
      <AddOrEdit v-if="showAddOrEditModal" :form-data="currentArticle" :categories-list="categoriesList" @submit-success="handleSubmitSuccess"
        @cancel="handleClose" @close="handleClose" />
    </el-dialog>

    <!-- 回收站弹窗 -->
    <el-dialog v-model="showRecycleBinModal" title="回收站" width="80%" :close-on-click-modal="false"
      class="recycle-bin-dialog">
      <div class="recycle-bin-content">
        <div class="recycle-bin-header" style="margin-bottom: 16px; display: flex; gap: 8px;">
          <el-button type="primary" size="small" @click="handleBatchRestore"
            :disabled="selectedRecycleBinRows.length === 0">
            <el-icon>
              <RefreshRight />
            </el-icon>
            批量恢复
          </el-button>
          <el-button type="danger" size="small" @click="handleBatchPermanentDelete"
            :disabled="selectedRecycleBinRows.length === 0">
            <el-icon>
              <Delete />
            </el-icon>
            永久删除
          </el-button>
        </div>

        <el-table :data="recycleBinData" v-loading="recycleBinLoading"
          @selection-change="handleRecycleBinSelectionChange" style="width: 100%" border stripe>
          <el-table-column type="selection" width="55" />
          <el-table-column prop="id" label="ID" width="80" align="center" />
          <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
          <el-table-column prop="author_id" label="作者" width="100" align="center">
            <template #default="{ row }">
              {{ getAuthorName(row.author_id) }}
            </template>
          </el-table-column>
          <el-table-column prop="category_id" label="分类" width="100" align="center">
            <template #default="{ row }">
              {{ getCategoryName(row.category_id) }}
            </template>
          </el-table-column>
          <el-table-column prop="delete_time" label="删除时间" width="180" align="center">
            <template #default="{ row }">
              {{ formatTime(row.delete_time) }}
            </template>
          </el-table-column>
          <el-table-column label="操作" width="200" fixed="right" align="center">
            <template #default="{ row }">
              <el-button type="primary" size="small" @click="handleRestore(row)">
                <el-icon>
                  <RefreshRight />
                </el-icon>
                恢复
              </el-button>
              <el-button type="danger" size="small" @click="handlePermanentDelete(row)">
                <el-icon>
                  <Delete />
                </el-icon>
                永久删除
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue'
// @ts-ignore 
// 忽略AddOrEdit的导入错误
import AddOrEdit from './article/AddOrEdit.vue'
import { ElMessageBox, ElLoading } from 'element-plus'
import { getArticleList, deleteArticle, restoreArticle, updateArticle, getDeletedArticles } from '@/api/article'
import { getCategoryList } from '@/api/category'
import { getUserList } from '@/api/user'
import { debounce } from 'lodash-es'
import { message } from "@/utils/message";
import { extractAndDeleteImages, batchExtractAndDeleteImages, deleteFiles } from '@/api/upload';
import {
  ArrowDown,
  Plus,
  Delete,
  Download,
  Refresh,
  Setting,
  Search,
  RefreshRight,
  Edit,
  View
} from '@element-plus/icons-vue'

// 定义buttonSize以避免TypeScript错误
const buttonSize = 'default'
const showAddOrEditModal = ref(false)
const currentArticle = ref<any>({})

const tableLoading = ref(false)
const selectedRows = ref([])

// 回收站相关变量
const showRecycleBinModal = ref(false)
const recycleBinData = ref([])
const recycleBinLoading = ref(false)
const selectedRecycleBinRows = ref([])

// 分类列表
const categoriesList = ref([])
const categoryLoading = ref(false) // 分类加载状态

// 状态选项
const statusOptions = {
  0: '草稿',
  1: '已发布',
  2: '待审核',
  3: '已下架'
}

// 搜索表单
const searchForm = reactive({
  id: '',
  title: '',
  category_id: null,
  status: null,
  is_top: null,
  author_id: null,
  delete_status: ''
})

// 排序配置
const sortEnabled = ref(true); // 默认启用排序

// 分页配置
const pageConfig = ref({
  current_page: 1,
  page_size: 10,    // 每页显示10条
  disabled: false,
  background: true,
  layout: "total, sizes, prev, pager, next, jumper",
  total: 0
})

// 添加默认封面图片变量
const defaultCoverImage = 'https://p3-juejin.byteimg.com/tos-cn-i-k3u1fbpfcp/8a6ff8e9cebe410089bfe045b882fc0a~tplv-k3u1fbpfcp-jj:300:250:0:0:q75.avis'

// 根据分类ID获取分类名称
const getCategoryName = (categoryId) => {
  const categories = {
    1: '技术',
    2: '生活'
  }
  return categories[categoryId] || '未分类'
}

// 根据作者ID获取作者名称
const getAuthorName = (authorId) => {
  const authors = {
    1: '管理员',
    2: '编辑',
    3: '用户'
  }
  return authors[authorId] || `用户${authorId}`
}

// 根据状态获取显示文本
const getStatusText = (status) => {
  const statusMap = {
    0: '草稿',
    1: '已发布',
    2: '待审核',
    3: '已下架'
  }
  return statusMap[status] || '未知'
}

// 根据状态获取标签类型
const getStatusTagType = (status) => {
  const typeMap = {
    0: 'info',
    1: 'success',
    2: 'warning',
    3: 'danger'
  }
  return typeMap[status] || 'info'
}

// 根据分类ID获取标签类型
const getCategoryTagType = (categoryId) => {
  const typeMap = {
    1: 'primary',
    2: 'success',
    3: 'warning',
    4: 'danger',
    5: 'info'
  }
  return typeMap[categoryId] || 'primary'
}

// 格式化时间显示
const formatTime = (timeString) => {
  if (!timeString) return '-'

  // 如果已经是格式化好的日期字符串，直接返回
  if (typeof timeString === 'string' && /^\d{4}[-/]\d{1,2}[-/]\d{1,2}/.test(timeString)) {
    return timeString
  }

  // 否则尝试转换为日期对象并格式化
  try {
    const date = new Date(timeString)
    return date.toLocaleDateString()
  } catch (e) {
    return timeString
  }
}

// 表格数据直接使用服务器返回的数据
const tableData = ref([])

// 搜索文章 - 主动搜索时向服务器发送请求
const handleSearch = () => {
  console.log('执行搜索，搜索条件:', searchForm);
  // 重置到第一页
  pageConfig.value.current_page = 1;
  // 获取数据
  fetchArticleList();
}

// 表格选择变化处理
const handleSelectionChange = (selection: any[]) => {
  selectedRows.value = selection
}

// 回收站选择变化处理
const handleRecycleBinSelectionChange = (selection: any[]) => {
  selectedRecycleBinRows.value = selection
}

/**
 * 获取分类列表
 * 用于搜索表单中的分类筛选
 */
const fetchCategoryList = async () => {
  try {
    categoryLoading.value = true
    const res: any = await getCategoryList({ page_size: 200 }) // 获取更多数据
    if (res.code === 200 && res.data) {
      // 新的API返回格式：res.data.list
      categoriesList.value = res.data.list || res.data
      return res.data.list || res.data
    } else {
      console.error('获取分类列表失败:', res.message)
      message('获取分类列表失败，将使用默认分类数据', { type: 'warning' })
      return []
    }
  } catch (err) {
    console.error('获取分类列表出错:', err)
    message('获取分类列表出错，将使用默认分类数据', { type: 'warning' })
    return []
  } finally {
    categoryLoading.value = false
  }
}

// 重置搜索
const resetSearch = () => {
  // 重置所有搜索条件
  Object.keys(searchForm).forEach(key => {
    searchForm[key] = null;
  });

  // 清空特定字段
  searchForm.title = '';
  searchForm.delete_status = '';
  searchForm.author_id = null;

  // 重置排序为默认
  sortEnabled.value = true;

  // 重置到第一页
  pageConfig.value.current_page = 1;

  // 重新加载数据
  fetchArticleList();
};

// 添加文章
const handleAdd = () => {
  currentArticle.value = {}
  showAddOrEditModal.value = true
}

// 查看文章
const handleView = (row) => {
  message(`查看文章：${row.title}`, { type: 'info' })
  // 实际应用中，这里会打开文章详情页或弹出详情对话框
}

// 编辑文章
const handleEdit = (row) => {
  currentArticle.value = { ...row }
  showAddOrEditModal.value = true
}

// 下拉菜单命令处理
const handleCommand = (command, row) => {
  switch (command) {
    case 'toggleTop':
      toggleTopStatus(row)
      break
    case 'toggleRecommend':
      toggleRecommendStatus(row)
      break
    case 'delete':
      handleDelete(row)
      break
  }
}

// 删除文章
const handleDelete = (row, isRealDelete = false) => {
  const actionText = isRealDelete ? '彻底删除' : '删除';
  const warningText = isRealDelete ? '此操作将永久删除该文章，无法恢复!' : '确定要删除该文章吗?';

  ElMessageBox.confirm(warningText, `${actionText}确认`, {
    confirmButtonText: '确认',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res: any = await deleteArticle(row.id, isRealDelete);
      if (res.code === 200) {
        message(`${actionText}成功`, { type: 'success' });
        // 重新加载数据
        await fetchArticleList();
      } else {
        message(res.msg || `${actionText}失败`, { type: 'error' });
      }
    } catch (error: any) {
      console.error(`${actionText}文章错误:`, error);
      const errorMsg = error.response?.data?.msg || error.message || `${actionText}失败`;
      message(`${errorMsg}，请稍后重试`, { type: 'error' });
    }
  }).catch(() => { });
};



// 批量删除文章
const handleBatchDelete = () => {
  if (selectedRows.value.length === 0) {
    message('请至少选择一条记录', { type: 'warning' });
    return;
  }

  // 创建响应式变量用于存储复选框状态
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h('div', null, [
      h('p', null, `确定要删除选中的 ${selectedRows.value.length} 条记录吗？`),
      h('div', { style: 'margin-top: 16px; display: flex; align-items: center;' }, [
        h('input', {
          type: 'checkbox',
          style: 'width: 16px; height: 16px; margin-right: 8px; cursor: pointer;',
          checked: isRealDelete.value,
          onInput: (event) => {
            isRealDelete.value = (event.target as HTMLInputElement).checked;
          }
        }),
        h('span', null, '永久删除（否则为软删除，可在回收站恢复）')
      ])
    ]);
  };

  ElMessageBox({
    title: '批量删除确认',
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: '确定删除',
    cancelButtonText: '取消',
    type: 'warning',
    beforeClose: (action, instance, done) => {
      if (action === 'confirm') {
        instance.confirmButtonLoading = true;
        // 执行批量删除
        batchDeleteArticles(isRealDelete.value)
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
}

// 执行批量删除
const batchDeleteArticles = async (isRealDelete) => {
  let success = 0;
  let failed = 0;

  // 显示加载提示
  const loading = ElLoading.service({
    lock: true,
    text: '批量删除中...',
    background: 'rgba(0, 0, 0, 0.7)'
  });

  // 逐个删除文章
  for (const article of selectedRows.value) {
    try {
      const res: any = await deleteArticle(article.id, isRealDelete);
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
    message(`成功删除 ${success} 条记录`, { type: 'success' });
    // 重新加载数据
    await fetchArticleList();
    selectedRows.value = [];
  }

  if (failed > 0) {
    message(`${failed} 条记录删除失败`, { type: 'error' });
  }
};

// 切换置顶状态
const toggleTopStatus = async (row) => {
  const newStatus = row.is_top === 1 ? 0 : 1;

  try {
    const res: any = await updateArticle({
      id: row.id,
      is_top: newStatus
    });

    if (res.code === 200) {
      message(`文章"${row.title}"${newStatus === 1 ? '已设为置顶' : '已取消置顶'}`, { type: 'success' });
      // 重新加载数据
      await fetchArticleList();
    } else {
      const errorMsg = res.msg || '操作失败';
      message(errorMsg, { type: 'error' });
      console.error('API错误:', res);
    }
  } catch (error: any) {
    console.error('更新文章置顶状态错误:', error);
    const errorMsg = error.response?.data?.msg || error.message || '操作失败，请稍后重试';
    message(errorMsg, { type: 'error' });
  }
};

// 切换推荐状态
const toggleRecommendStatus = async (row) => {
  const newStatus = row.is_recommend === 1 ? 0 : 1;

  try {
    const res: any = await updateArticle({
      id: row.id,
      is_recommend: newStatus
    });

    if (res.code === 200) {
      message(`文章"${row.title}"${newStatus === 1 ? '已设为推荐' : '已取消推荐'}`, { type: 'success' });
      // 重新加载数据
      await fetchArticleList();
    } else {
      const errorMsg = res.msg || '操作失败';
      message(errorMsg, { type: 'error' });
      console.error('API错误:', res);
    }
  } catch (error: any) {
    console.error('更新文章推荐状态错误:', error);
    const errorMsg = error.response?.data?.msg || error.message || '操作失败，请稍后重试';
    message(errorMsg, { type: 'error' });
  }
};

// 关闭对话框
const handleClose = () => {
  showAddOrEditModal.value = false
}

// 提交成功处理
const handleSubmitSuccess = () => {
  showAddOrEditModal.value = false
  // 刷新数据
  fetchArticleList()
}

// 显示回收站
const handleShowRecycleBin = () => {
  showRecycleBinModal.value = true
  fetchRecycleBinData()
}

// 获取回收站数据
const fetchRecycleBinData = async () => {
  recycleBinLoading.value = true
  try {
    const res: any = await getDeletedArticles({
      page: 1,
      page_size: 200
    })

    if (res.code === 200) {
      recycleBinData.value = res.data?.list || []
    } else {
      message(res.msg || '获取回收站数据失败', { type: 'error' })
    }
  } catch (error: any) {
    console.error('获取回收站数据错误:', error)
    const errorMsg = error.response?.data?.msg || error.message || '获取回收站数据失败';
    message(`${errorMsg}，请稍后重试`, { type: 'error' })
  } finally {
    recycleBinLoading.value = false
  }
}

// 恢复文章
const handleRestore = async (row: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要恢复文章"${row.title}"吗？`,
      '恢复确认',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    )

    const res: any = await restoreArticle(row.id)
    if (res.code === 200) {
      message('文章恢复成功', { type: 'success' })
      fetchRecycleBinData()
      fetchArticleList() // 刷新主表格
    } else {
      message(res.msg || '恢复失败', { type: 'error' })
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('恢复文章错误:', error)
      const errorMsg = error.response?.data?.msg || error.message || '恢复失败';
      message(`${errorMsg}，请稍后重试`, { type: 'error' })
    }
  }
}

// 永久删除文章
const handlePermanentDelete = async (row: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要永久删除文章"${row.title}"吗？此操作不可恢复！`,
      '永久删除确认',
      {
        confirmButtonText: '确定删除',
        cancelButtonText: '取消',
        type: 'error',
      }
    )

    // 删除文章
    const res: any = await deleteArticle(row.id, true) // true表示物理删除
    if (res.code === 200) {
      message('文章已永久删除', { type: 'success' })

      // 提取并删除文章中的图片
      try {
        // 收集所有需要删除的图片URL
        const allImageUrls = [];

        // 1. 提取内容中的图片
        const contentResult = await extractAndDeleteImages(row.content || '');

        // 2. 添加封面图片（如果存在）
        if (row.cover_image && row.cover_image.trim() !== '') {
          allImageUrls.push(row.cover_image);
        }

        // 3. 如果有封面图片，单独删除封面图片
        if (allImageUrls.length > 0) {
          const coverDeleteResult = await deleteFiles(allImageUrls);

          // 合并删除结果
          const totalSuccessCount = contentResult.data.success_count + coverDeleteResult.data.success_count;
          const totalFailedCount = contentResult.data.failed_count + coverDeleteResult.data.failed_count;

          if (totalSuccessCount > 0) {
            message(`已删除 ${totalSuccessCount} 张相关图片（包含封面图片）`, { type: 'info' });
          }
          if (totalFailedCount > 0) {
            console.warn('部分图片删除失败:', [...contentResult.data.failed_urls, ...coverDeleteResult.data.failed_urls]);
          }
        } else {
          // 只有内容图片
          if (contentResult.data.success_count > 0) {
            message(`已删除 ${contentResult.data.success_count} 张相关图片`, { type: 'info' });
          }
          if (contentResult.data.failed_count > 0) {
            console.warn('部分图片删除失败:', contentResult.data.failed_urls);
          }
        }
      } catch (error) {
        console.error('删除图片失败:', error);
      }

      fetchRecycleBinData()
    } else {
      message(res.msg || '删除失败', { type: 'error' })
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('永久删除文章错误:', error)
      const errorMsg = error.response?.data?.msg || error.message || '删除失败';
      message(`${errorMsg}，请稍后重试`, { type: 'error' })
    }
  }
}

// 批量恢复
const handleBatchRestore = async () => {
  if (selectedRecycleBinRows.value.length === 0) {
    message('请选择要恢复的文章', { type: 'warning' })
    return
  }

  try {
    await ElMessageBox.confirm(
      `确定要恢复选中的 ${selectedRecycleBinRows.value.length} 篇文章吗？`,
      '批量恢复确认',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    )

    const promises = selectedRecycleBinRows.value.map((row: any) => restoreArticle(row.id))
    await Promise.all(promises)

    message('批量恢复成功', { type: 'success' })
    fetchRecycleBinData()
    fetchArticleList() // 刷新主表格
    selectedRecycleBinRows.value = []
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('批量恢复错误:', error)
      const errorMsg = error.response?.data?.msg || error.message || '批量恢复失败';
      message(`${errorMsg}，请稍后重试`, { type: 'error' })
    }
  }
}

// 批量永久删除
const handleBatchPermanentDelete = async () => {
  if (selectedRecycleBinRows.value.length === 0) {
    message('请选择要删除的文章', { type: 'warning' })
    return
  }

  try {
    await ElMessageBox.confirm(
      `确定要永久删除选中的 ${selectedRecycleBinRows.value.length} 篇文章吗？此操作不可恢复！`,
      '批量永久删除确认',
      {
        confirmButtonText: '确定删除',
        cancelButtonText: '取消',
        type: 'error',
      }
    )

    // 批量删除文章
    const promises = selectedRecycleBinRows.value.map((row: any) => deleteArticle(row.id, true))
    await Promise.all(promises)

    message('批量删除成功', { type: 'success' })

    // 批量提取并删除所有文章中的图片
    try {
      // 1. 提取所有文章内容中的图片
      const contents = selectedRecycleBinRows.value.map((row: any) => row.content || '');
      const contentDeleteResult = await batchExtractAndDeleteImages(contents);

      // 2. 收集所有文章的封面图片
      const coverImages = selectedRecycleBinRows.value
        .map((row: any) => row.cover_image)
        .filter((coverImage: string) => coverImage && coverImage.trim() !== '');

      let coverDeleteResult = { data: { success_count: 0, failed_count: 0, failed_urls: [] } };

      // 3. 如果有封面图片，批量删除封面图片
      if (coverImages.length > 0) {
        coverDeleteResult = await deleteFiles(coverImages);
      }

      // 4. 合并删除结果并显示消息
      const totalSuccessCount = contentDeleteResult.data.success_count + coverDeleteResult.data.success_count;
      const totalFailedCount = contentDeleteResult.data.failed_count + coverDeleteResult.data.failed_count;

      if (totalSuccessCount > 0) {
        message(`已删除 ${totalSuccessCount} 张相关图片（包含封面图片）`, { type: 'info' });
      }
      if (totalFailedCount > 0) {
        console.warn('部分图片删除失败:', [...contentDeleteResult.data.failed_urls, ...coverDeleteResult.data.failed_urls]);
      }
    } catch (error) {
      console.error('批量删除图片失败:', error);
    }

    fetchRecycleBinData()
    selectedRecycleBinRows.value = []
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('批量永久删除错误:', error)
      const errorMsg = error.response?.data?.msg || error.message || '批量删除失败';
      message(`${errorMsg}，请稍后重试`, { type: 'error' })
    }
  }
}

// 分页大小变化
const handleSizeChange = (val: number) => {
  console.log('分页大小变化:', val)
  // v-model 已经自动更新了 pageConfig.value.page_size
  // 重置到第一页
  pageConfig.value.current_page = 1
  // 重新获取数据
  fetchArticleList()
}

// 页码变化
const handleCurrentChange = (val: number) => {
  console.log('页码变化:', val)
  // v-model 已经自动更新了 pageConfig.value.current_page
  // 重新获取数据
  fetchArticleList()
}

// 获取摘要文本，优先使用AI摘要，其次是手动摘要，最后是内容截取
const getSummaryText = (row) => {
  if (row.ai_summary) {
    return row.ai_summary;
  } else if (row.description) {
    return row.description;
  } else if (row.content) {
    // 移除Markdown标记的内容，截取前80个字符
    const plainText = row.content.replace(/(?:!\[(.*?)\]\((.*?)\))|(?:\[(.*?)\]\((.*?)\))|(?:\*\*(.*?)\*\*)|(?:\*(.*?)\*)|(?:__(.*?)__)|(?:_(.*?)_)|(?:~~(.*?)~~)|(?:`(.*?)`)|(?:```([\s\S]*?)```)|(?:#+ )|(?:- )|(?:\d+\. )|(?:\|)|(?:-{3,})|(?:>{1,})/g, '$1$3$5$6$7$8$9$10$11');
    const trimmed = plainText.replace(/\s+/g, ' ').trim();
    return trimmed.length > 80 ? trimmed.substring(0, 80) + '...' : trimmed;
  }
  return '暂无摘要';
}

// 获取内容预览
const getContentPreview = (content, maxLength = 50) => {
  if (!content) return '暂无内容';

  // 移除Markdown标记
  const plainText = content.replace(/(?:!\[(.*?)\]\((.*?)\))|(?:\[(.*?)\]\((.*?)\))|(?:\*\*(.*?)\*\*)|(?:\*(.*?)\*)|(?:__(.*?)__)|(?:_(.*?)_)|(?:~~(.*?)~~)|(?:`(.*?)`)|(?:```([\s\S]*?)```)|(?:#+ )|(?:- )|(?:\d+\. )|(?:\|)|(?:-{3,})|(?:>{1,})/g, '$1$3$5$6$7$8$9$10$11');
  const trimmed = plainText.replace(/\s+/g, ' ').trim();

  return trimmed.length > maxLength ? trimmed.substring(0, maxLength) + '...' : trimmed;
}

// 获取空状态文本
const getEmptyText = () => {
  if (tableLoading.value) return '';

  // 检查是否有搜索条件
  const hasSearchConditions = searchForm.title ||
    searchForm.category_id ||
    searchForm.status !== null ||
    searchForm.is_top !== null ||
    searchForm.author_id ||
    (searchForm.delete_status && searchForm.delete_status !== '');

  if (hasSearchConditions) {
    return '没有找到符合条件的文章，请尝试调整搜索条件';
  }

  return '暂无文章数据，点击"新增文章"开始创建您的第一篇文章';
}

// 用户选择相关变量
const userSelectLoading = ref(false);
const userOptions = ref<any[]>([]);
const userDataLoaded = ref(false);

// 远程搜索用户
const remoteSearchUsers = debounce(async (query) => {
  // 如果有查询内容，才进行搜索
  if (query && query.trim() !== '') {
    console.log('开始搜索用户:', query);
    userSelectLoading.value = true;
    try {
      // 根据查询内容决定搜索条件
      const searchParams: Record<string, any> = {};

      // 判断是否为数字ID查询
      if (/^\d+$/.test(query)) {
        searchParams.id = query;
        console.log('按ID搜索用户:', query);
      } else {
        searchParams.username = query;
        console.log('按用户名搜索用户:', query);
      }

      const res: any = await getUserList({
        ...searchParams,
        page_size: 10 // 限制显示10条数据
      });

      if (res.code === 200 && res.data && res.data.list) {
        userOptions.value = res.data.list;
        userDataLoaded.value = true;
        console.log('搜索到用户数量:', res.data.list.length);
      } else {
        console.error('获取用户列表失败:', res.msg);
        userOptions.value = [];
      }
    } catch (error) {
      console.error('搜索用户时出错:', error);
      userOptions.value = [];
    } finally {
      userSelectLoading.value = false;
      console.log('用户搜索loading结束');
    }
  } else {
    // 如果查询为空，清空选项
    userOptions.value = [];
  }
}, 300);

// 当下拉框获得焦点但没有数据时加载默认数据
const handleUserSelectFocus = () => {
  // 如果用户选项为空并且未在加载状态，才加载数据
  if (userOptions.value.length === 0 && !userSelectLoading.value) {
    console.log('用户下拉框获得焦点，开始加载默认用户数据');
    // 延迟200ms后再加载，减轻服务器压力
    setTimeout(() => {
      loadDefaultUserOptions();
    }, 200);
  }
};

// 加载默认用户列表，使用懒加载方式
const loadDefaultUserOptions = async () => {
  // 如果已经有数据或正在加载中，不重复加载
  if (userOptions.value.length > 0 || userSelectLoading.value || userDataLoaded.value) {
    console.log('跳过加载默认用户数据，原因：', {
      hasData: userOptions.value.length > 0,
      isLoading: userSelectLoading.value,
      dataLoaded: userDataLoaded.value
    });
    return;
  }

  console.log('开始加载默认用户数据');
  userSelectLoading.value = true;
  try {
    const res: any = await getUserList({
      page_size: 10, // 限制显示10条数据
      sort: true // 使用默认排序
    });

    if (res.code === 200 && res.data && res.data.list) {
      userOptions.value = res.data.list;
      userDataLoaded.value = true;
      console.log('加载默认用户数据成功，数量:', res.data.list.length);
    } else {
      console.error('获取用户列表失败:', res.msg);
      userOptions.value = [];
    }
  } catch (error) {
    console.error('加载用户列表出错:', error);
    userOptions.value = [];
  } finally {
    userSelectLoading.value = false;
    console.log('默认用户数据loading结束');
  }
};

// 获取文章列表
const fetchArticleList = async () => {
  tableLoading.value = true;

  try {
    // 构建请求参数
    const params: Record<string, any> = {
      page: pageConfig.value.current_page,
      page_size: pageConfig.value.page_size
    };

    // 添加搜索条件（过滤空值）
    Object.keys(searchForm).forEach(key => {
      if (
        searchForm[key] !== null &&
        searchForm[key] !== undefined &&
        searchForm[key] !== ''
      ) {
        params[key] = searchForm[key];
      }
    });

    // 添加排序参数
    params.sort = sortEnabled.value;

    console.log('请求参数:', params);

    const res: any = await getArticleList(params);
    
    if (res.code === 200) {
      if (res.data && typeof res.data === 'object') {
        // 更新表格数据
        tableData.value = res.data.list || [];

        // 更新总数
        if (res.data.pagination) {
          pageConfig.value.total = res.data.pagination.total || 0;
        }
      } else {
        tableData.value = [];
        pageConfig.value.total = 0;
      }
    } else {
      const errorMsg = res.msg || '获取文章列表失败';
      message(errorMsg, { type: 'error' });
      console.error('API错误:', res);
      tableData.value = [];
      pageConfig.value.total = 0;
    }
  } catch (error: any) {
    console.error('获取文章列表错误:', error);
    const errorMsg = error.response?.data?.msg || error.message || '获取文章列表失败，请稍后重试';
    message(errorMsg, { type: 'error' });
    tableData.value = [];
    pageConfig.value.total = 0;
  } finally {
    tableLoading.value = false;
    selectedRows.value = [];
  }
};

// 切换文章状态
const handleStatusChange = (row: any, status: number) => {
  if (row.status === status) return;

  const statusTexts = {
    0: '草稿',
    1: '已发布',
    2: '待审核',
    3: '已下架'
  };

  ElMessageBox.confirm(
    `确定要将文章"${row.title}"的状态更改为"${statusTexts[status]}"吗？`,
    '更改状态',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  ).then(async () => {
    try {
      const res: any = await updateArticle({
        id: row.id,
        status,
        // 如果状态变更为已发布且原来没有发布时间，则设置当前时间为发布时间
        publish_time: status === 1 && !row.publish_time ? new Date().toISOString().slice(0, 19).replace('T', ' ') : undefined
      });

      if (res.code === 200) {
        message(`文章"${row.title}"状态已更改为"${statusTexts[status]}"`, { type: 'success' });
        // 直接更新本地数据，立即显示
        row.status = status;
        if (status === 1 && !row.publish_time) {
          row.publish_time = new Date().toISOString().slice(0, 19).replace('T', ' ');
        }
      } else {
        message(res.msg || '操作失败', { type: 'error' });
      }
    } catch (error: any) {
      console.error('更新文章状态错误:', error);
      const errorMsg = error.response?.data?.msg || error.message || '操作失败';
      message(`${errorMsg}，请稍后重试`, { type: 'error' });
    }
  }).catch(() => {
    message('已取消操作', { type: 'info' });
  });
};

onMounted(() => {
  // 先获取分类列表，然后初始化文章数据
  fetchCategoryList().then(() => {
    fetchArticleList()
  }).catch(() => {
    // 即使获取分类失败，也继续加载文章数据
    fetchArticleList()
  });
});
</script>

<style lang="scss" scoped>
// 简洁文章布局样式
.article-item-simple {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 8px 0;

  .article-cover {
    width: 60px;
    height: 45px;
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
    border: 1px solid #e5e7eb;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.2s ease;
    }

    &:hover img {
      transform: scale(1.05);
    }
  }

  .article-content {
    flex: 1;
    min-width: 0;

    .article-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 6px;
      gap: 8px;

      .article-title {
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        cursor: pointer;
        line-height: 1.4;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;

        &:hover {
          color: #3b82f6;
        }
      }

      .article-badges {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
      }
    }

    .article-summary {
      font-size: 12px;
      color: #6b7280;
      line-height: 1.4;
      margin-bottom: 6px;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
    }

    .article-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;

      .tag-item {
        font-size: 11px;
        height: 18px;
        line-height: 16px;
        padding: 0 6px;
      }
    }
  }
}

// 紧凑的元信息列样式
.meta-info-column {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 8px 0;

  .meta-row {
    display: flex;
    align-items: center;
    padding: 2px 0;
    min-height: 24px;

    .author-content {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;

      .author-avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 1px solid #e5e7eb;

        img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }
      }

      .author-name {
        font-size: 12px;
        font-weight: 500;
        color: #374151;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
      }
    }

    .category-status-content {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;

      .status-tag {
        cursor: pointer;
        transition: all 0.2s ease;

        &.clickable:hover {
          opacity: 0.8;
        }
      }
    }
  }
}



.stats-content {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;

  .stat-item {
    font-size: 11px;
    color: #6b7280;
    padding: 2px 6px;
    background: #f8fafc;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    white-space: nowrap;
  }
}

// 原有的作者卡片样式已移除，现在集成在文章信息中

.status-tag {
  cursor: pointer;

  i {
    font-size: 12px;
    margin-left: 4px;
  }
}

.interaction-stats {
  display: flex;
  flex-direction: column;
  gap: 4px;

  .stat-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    font-size: 12px;
    color: #606266;

    i {
      font-size: 12px;

      &.fa-thumbs-up {
        color: #409eff;
      }

      &.fa-heart {
        color: #f56c6c;
      }

      &.fa-comment {
        color: #e6a23c;
      }
    }

    span {
      font-weight: 500;
      min-width: 16px;
      text-align: center;
    }
  }
}

.time-info {
  .time-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #909399;
    margin-bottom: 4px;

    i {
      font-size: 12px;
      color: #c0c4cc;
      width: 12px;
    }

    span {
      font-weight: 400;
    }

    &.update {
      color: #e6a23c;

      i {
        color: #e6a23c;
      }
    }
  }
}

// 使用Element UI默认表格样式，只优化单元格内容

.articles-container {
  .article-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    transition: all 0.2s ease;

  }

  .header-wrapper {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .header-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    letter-spacing: -0.025em;

    &::before {
      content: '';
      width: 4px;
      height: 18px;
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      margin-right: 12px;
      border-radius: 2px;
    }
  }

  .search-wrapper {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
  }

  .search-row {
    margin-bottom: -12px;
  }

  .search-item {
    margin-bottom: 12px;
  }

  .search-buttons .button-group {
    display: flex;
    gap: 8px;
  }

  @media (max-width: 768px) {
    .header-title {
      font-size: 16px;
    }

    .search-wrapper {
      padding: 16px;
    }

    .search-buttons .button-group {
      flex-direction: column;
      gap: 8px;
    }

    .search-buttons .button-group .el-button {
      width: 100%;
    }

    .table-toolbar {
      flex-direction: column;
      gap: 12px;
      align-items: stretch;

      .left-tools,
      .right-tools {
        justify-content: center;
      }
    }

    .article-footer {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;

      .article-interactions {
        order: -1;
      }
    }


  }

  @media (min-width: 992px) {
    .search-buttons .button-group {
      justify-content: flex-end;
    }
  }

  .table-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;

    .left-tools {
      display: flex;
      align-items: center;
      gap: 8px;

      .el-button {
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s ease;

        &:hover {
          transform: translateY(-1px);
        }
      }
    }

    .right-tools {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    // 删除自定义按钮样式，使用Element UI Plus默认样式
  }

  .article-table {
    border-radius: 4px;
    margin-bottom: 12px;

    :deep(.el-table__header th) {
      background-color: var(--el-fill-color-light);
      color: var(--el-text-color-regular);
      font-weight: 600;
      padding: 6px 0;
    }

    :deep(.el-table__row td) {
      padding: 6px 0;
    }
  }

  .article-content-cell {
    display: flex;
    flex-direction: column;
    gap: 10px;

    .article-main-content {
      display: flex;
      gap: 12px;

      .cover-image {
        width: 120px;
        height: 80px;
        flex-shrink: 0;
        border-radius: 6px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;

        &:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .el-image {
          width: 100%;
          height: 100%;
        }

        .image-error {
          height: 100%;
          display: flex;
          align-items: center;
          justify-content: center;
          background-color: #f5f7fa;
          color: #909399;
          font-size: 24px;
        }

        .status-corner-badge {
          position: absolute;
          top: 0;
          right: 0;
          padding: 2px 6px;
          font-size: 10px;
          font-weight: bold;
          color: white;
          border-bottom-left-radius: 6px;
          z-index: 1;

          &.status-0 {
            background-color: #909399;
          }

          &.status-1 {
            background-color: #67c23a;
          }

          &.status-2 {
            background-color: #e6a23c;
          }

          &.status-3 {
            background-color: #f56c6c;
          }
        }
      }

      .content-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;

        .title-line {
          display: flex;
          align-items: center;
          margin-bottom: 6px;
          gap: 8px;

          .article-flags {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
          }

          .article-title-text {
            font-size: 14px;
            font-weight: bold;
            color: var(--el-text-color-primary);
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
          }
        }

        .article-subtitle {
          font-size: 12px;
          color: var(--el-text-color-secondary);
          margin-bottom: 6px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .article-summary {
          display: flex;
          font-size: 12px;
          color: var(--el-text-color-secondary);
          overflow: hidden;

          .summary-label {
            flex-shrink: 0;
            font-weight: bold;
            margin-right: 4px;
          }

          .summary-text {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            word-break: break-word;
          }
        }
      }
    }

    .article-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 4px;

      .meta-item {
        display: flex;
        align-items: center;
        font-size: 12px;
        color: var(--el-text-color-secondary);

        .el-icon {
          margin-right: 4px;
          font-size: 14px;
        }

        &.tags-item {
          max-width: 100%;
          overflow: hidden;

          .tags-list {
            display: flex;
            gap: 4px;
            overflow: hidden;

            .article-tag {
              flex-shrink: 0;
              max-width: 80px;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }
          }
        }
      }

      .category-tag {
        border-radius: 4px;
      }

      .status-tag {
        border-radius: 12px;
        padding: 0 8px;
        transition: all 0.2s ease;

        &.interactive-tag {
          cursor: pointer;
          display: flex;
          align-items: center;

          &:hover {
            filter: brightness(1.05);
          }

          .el-icon--right {
            margin-left: 2px;
            font-size: 10px;
            transition: transform 0.2s;
          }

          &:hover .el-icon--right {
            transform: rotate(180deg);
          }
        }
      }
    }
  }

  .status-option {
    display: flex;
    align-items: center;
    gap: 8px;

    .status-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;

      &.status-0 {
        background-color: #909399;
      }

      &.status-1 {
        background-color: #67c23a;
      }

      &.status-2 {
        background-color: #e6a23c;
      }

      &.status-3 {
        background-color: #f56c6c;
      }
    }
  }

  .meta-info-cell {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 4px;

    .meta-item {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 4px 8px;
      border-radius: 4px;
      background: var(--el-fill-color-light);
      transition: all 0.2s ease;

      &:hover {
        background: var(--el-fill-color);
      }

      .keyword-tag,
      .slug-tag {
        padding: 0 4px;
        height: 16px;
        line-height: 14px;
        font-size: 10px;
        border-radius: 2px;
        flex-shrink: 0;
      }

      .meta-content {
        font-size: 11px;
        color: var(--el-text-color-regular);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
      }

      &.keywords-item {
        border-left: 2px solid var(--el-color-info);
      }

      &.slug-item {
        border-left: 2px solid var(--el-color-warning);
      }
    }
  }

  .time-info-cell {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 4px;

    .time-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      padding: 3px 6px;
      border-radius: 3px;
      transition: all 0.2s ease;

      &:hover {
        background: var(--el-fill-color-light);
      }

      .time-badge {
        font-size: 10px;
        color: #fff;
        padding: 1px 4px;
        border-radius: 2px;
        flex-shrink: 0;
        width: 28px;
        text-align: center;
      }

      .time-value {
        color: var(--el-text-color-regular);
        font-size: 11px;
      }

      &.publish-time .time-badge {
        background-color: #67c23a;
      }

      &.update-time .time-badge {
        background-color: #409eff;
      }

      &.create-time .time-badge {
        background-color: #909399;
      }
    }
  }

  .info-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;

    .info-item {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;

      .el-icon {
        font-size: 12px;
      }
    }
  }

  .publish-cell {
    display: flex;
    flex-direction: column;
    gap: 3px;

    .time-item {
      display: flex;
      align-items: center;
      font-size: 11px;

      .time-label {
        color: var(--el-text-color-secondary);
        width: 32px;
      }

      .time-value {
        color: var(--el-text-color-regular);
      }
    }
  }

  .action-buttons {
    display: flex;
    justify-content: center;
    gap: 4px;
  }

  .el-dropdown-menu {
    :deep(.el-dropdown-menu__item) {
      display: flex;
      align-items: center;
      gap: 5px;
      padding: 6px 10px;
      line-height: 1.4;
      font-size: 12px;

      .el-icon {
        font-size: 12px;
      }
    }
  }

  .pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
  }

  .content-preview-cell {
    .preview-text {
      font-size: 12px;
      color: var(--el-text-color-secondary);
      line-height: 1.4;
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 3;
      overflow: hidden;
      cursor: pointer;

      &:hover {
        color: var(--el-color-primary);
      }
    }
  }

  .interaction-data-cell {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 4px;

    .interaction-item {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      font-size: 11px;
      background: var(--el-fill-color-light);
      border-radius: 16px;
      padding: 3px 10px;
      transition: all 0.2s ease;

      .el-icon {
        font-size: 14px;
      }

      .interaction-label {
        font-size: 11px;
        color: var(--el-text-color-secondary);
      }

      .interaction-count {
        font-weight: 600;
        font-size: 12px;
      }

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
      }

      &.like-item {
        color: #ff9a00;
        background: rgba(255, 154, 0, 0.1);

        .interaction-count {
          color: #ff9a00;
        }
      }

      &.favorite-item {
        color: #409eff;
        background: rgba(64, 158, 255, 0.1);

        .interaction-count {
          color: #409eff;
        }
      }

      &.comment-item {
        color: #67c23a;
        background: rgba(103, 194, 58, 0.1);

        .interaction-count {
          color: #67c23a;
        }
      }
    }
  }

  .stats-data-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 4px;

    .stats-item {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      font-size: 11px;
      background: var(--el-fill-color-light);
      border-radius: 16px;
      padding: 3px 10px;
      width: 100%;
      transition: all 0.2s ease;

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
      }

      .stats-icon {
        font-size: 14px;
      }

      .stats-value {
        font-weight: 600;
        font-size: 12px;
      }

      .stats-unit {
        font-size: 10px;
        color: var(--el-text-color-secondary);
      }

      &.word-count {
        color: #8a56e2;
        background: rgba(138, 86, 226, 0.1);
      }

      &.read-time {
        color: #f56c6c;
        background: rgba(245, 108, 108, 0.1);
      }

      &.date-item {
        color: #909399;
        background: rgba(144, 147, 153, 0.1);

        .date-value {
          font-size: 11px;
          font-weight: normal;
        }
      }
    }
  }
}

@media screen and (max-width: 768px) {
  .articles-container {
    // 使用Element UI Plus默认的移动端样式

    .article-content-cell {
      padding: 12px 8px;

      .article-main-content {
        .cover-image {
          width: 45px;
          height: 45px;
        }
      }
    }

    .interaction-data-cell {
      .interaction-item {
        padding: 2px 6px;

        .interaction-label {
          display: none;
        }
      }
    }

    .stats-data-cell {
      .stats-item {
        padding: 2px 6px;

        .stats-unit {
          display: none;
        }
      }
    }

    .meta-info-cell {
      .meta-item {
        padding: 2px 6px;

        .meta-content {
          max-width: 100px;
        }
      }
    }

    .time-info-cell {
      .time-item {
        padding: 2px 4px;

        .time-badge {
          width: 24px;
          font-size: 9px;
        }
      }
    }
  }
}

:deep(.content-preview-popover) {
  .preview-popover-content {
    .preview-title {
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 8px;
      color: var(--el-color-primary);
    }

    .preview-content {
      font-size: 12px;
      color: var(--el-text-color-regular);
      line-height: 1.6;
      margin-bottom: 10px;
    }

    .preview-footer {
      text-align: right;
    }
  }
}

.user-option {
  display: flex;
  align-items: center;
  padding: 2px 0;
}

.user-name {
  font-size: 13px;
  color: var(--el-text-color-primary);
  margin-right: 4px;
}

.user-id {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>