<template>
  <div class="comments-container">
    <!-- 顶部统计卡片 -->
    <div class="stats-container">
      <el-card class="stat-card" shadow="hover">
        <div class="stat-content">
          <div class="stat-icon stat-icon-total">
            <IconifyIconOnline icon="ep:comment" />
          </div>
          <div class="stat-info">
            <div class="stat-label">总评论</div>
            <div class="stat-value">{{ pagination.total }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover">
        <div class="stat-content">
          <div class="stat-icon stat-icon-pending">
            <IconifyIconOnline icon="ep:warning" />
          </div>
          <div class="stat-info">
            <div class="stat-label">待审核</div>
            <div class="stat-value stat-value-pending">{{ statsData.pending }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover">
        <div class="stat-content">
          <div class="stat-icon stat-icon-approved">
            <IconifyIconOnline icon="ep:circle-check" />
          </div>
          <div class="stat-info">
            <div class="stat-label">已通过</div>
            <div class="stat-value stat-value-approved">{{ statsData.approved }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card" shadow="hover">
        <div class="stat-content">
          <div class="stat-icon stat-icon-rejected">
            <IconifyIconOnline icon="ep:circle-close" />
          </div>
          <div class="stat-info">
            <div class="stat-label">已拒绝</div>
            <div class="stat-value stat-value-rejected">{{ statsData.rejected }}</div>
          </div>
        </div>
      </el-card>
    </div>

    <!-- 主内容卡片 -->
    <el-card class="main-card" shadow="never">
      <!-- 搜索和操作栏 -->
      <el-row :gutter="8" align="middle">
        <!-- 文章筛选 -->
        <el-col :xs="24" :sm="8" :md="6" :lg="5">
          <el-input 
            v-model="searchForm.article_id" 
            placeholder="文章ID" 
            clearable 
            size="small"
            @keyup.enter="handleSearch"
          >
            <template #prefix>
              <IconifyIconOnline icon="ep:document" />
            </template>
          </el-input>
        </el-col>

        <!-- 状态筛选 -->
        <el-col :xs="24" :sm="8" :md="5" :lg="4">
          <el-select 
            v-model="searchForm.status" 
            placeholder="状态" 
            clearable 
            size="small" 
            @change="handleSearch"
          >
            <el-option label="待审核" :value="0" />
            <el-option label="已通过" :value="1" />
            <el-option label="已拒绝" :value="2" />
          </el-select>
        </el-col>

        <!-- 关键词搜索 -->
        <el-col :xs="24" :sm="8" :md="6" :lg="5">
          <el-input 
            v-model="searchForm.keyword" 
            placeholder="搜索内容/用户" 
            clearable 
            size="small" 
            @keyup.enter="handleSearch"
          >
            <template #prefix>
              <IconifyIconOnline icon="ep:search" />
            </template>
          </el-input>
        </el-col>

        <!-- 搜索按钮 -->
        <el-col :xs="12" :sm="6" :md="4" :lg="3">
          <el-button type="primary" size="small" @click="handleSearch">
            <IconifyIconOnline icon="ep:search" />
            搜索
          </el-button>
        </el-col>

        <el-col :xs="12" :sm="6" :md="4" :lg="2">
          <el-button size="small" @click="handleReset">
            <IconifyIconOnline icon="ep:refresh" />
            重置
          </el-button>
        </el-col>

        <!-- 右侧操作按钮 -->
        <el-col :xs="24" :sm="24" :md="24" :lg="5" class="action-buttons">
          <el-button 
            type="success" 
            size="small" 
            :disabled="selectedIds.length === 0" 
            @click="handleBatchApprove"
          >
            <IconifyIconOnline icon="ep:select" />
            批量通过
          </el-button>
          <el-button 
            type="danger" 
            size="small" 
            :disabled="selectedIds.length === 0" 
            @click="handleBatchDelete"
          >
            <IconifyIconOnline icon="ep:delete" />
            批量删除
          </el-button>
        </el-col>
      </el-row>

      <!-- 测试操作区域 -->
      <el-card class="test-area" shadow="never">
        <template #header>
          <div class="test-area-header">
            <span class="test-area-title">
              <IconifyIconOnline icon="ep:experiment" />
              后端测试操作区域
            </span>
            <span class="selected-count">已选择 {{ selectedIds.length }} 条评论</span>
          </div>
        </template>
        
        <div class="test-buttons">
          <el-button 
            type="primary" 
            size="small" 
            @click="handleTestGetComments"
          >
            <IconifyIconOnline icon="ep:reading" />
            获取评论树
          </el-button>
          
          <el-button 
            type="success" 
            size="small" 
            @click="handleTestGetChildren"
            :disabled="selectedIds.length !== 1"
          >
            <IconifyIconOnline icon="ep:fork" />
            获取子评论
          </el-button>
          
          <el-button 
            type="warning" 
            size="small" 
            @click="handleTestAddComment"
          >
            <IconifyIconOnline icon="ep:plus" />
            测试添加评论
          </el-button>
          
          <el-button 
            type="danger" 
            size="small" 
            @click="handleTestDeleteComment"
            :disabled="selectedIds.length !== 1"
          >
            <IconifyIconOnline icon="ep:delete" />
            测试删除评论
          </el-button>

          <el-button 
            type="info" 
            size="small" 
            @click="handleTestApprove"
            :disabled="selectedIds.length !== 1"
          >
            <IconifyIconOnline icon="ep:check" />
            测试审核评论
          </el-button>
        </div>
      </el-card>

      <!-- 数据表格 -->
      <el-table 
        :data="tableData" 
        v-loading="loading" 
        class="modern-table"
        row-key="id"
        :tree-props="{ children: 'replies', hasChildren: 'hasChildren' }"
        @selection-change="handleSelectionChange" 
        :header-cell-style="{ background: '#f8fafc', color: '#475569', fontWeight: '500' }"
      >
        <el-table-column type="selection" width="45" align="center" fixed />
        <el-table-column prop="id" label="ID" width="70" align="center" />

        <!-- 用户信息列 -->
        <el-table-column label="用户" min-width="150" align="center">
          <template #default="{ row }">
            <div class="user-cell">
              <el-avatar :size="32" :src="row.user?.avatar || defaultAvatar" />
              <div class="user-info">
                <div class="username">{{ row.user?.username || '未知用户' }}</div>
                <div class="user-id">ID: {{ row.user_id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>

        <!-- 评论内容列 -->
        <el-table-column prop="content" label="评论内容" min-width="280">
          <template #default="{ row }">
            <div class="content-text" :title="row.content">{{ row.content }}</div>
          </template>
        </el-table-column>

        <!-- 文章信息列 -->
        <el-table-column label="所属文章" min-width="120" align="center">
          <template #default="{ row }">
            <div class="article-info">
              <el-link type="primary" :underline="false" @click="handleViewArticle(row.article_id)">
                <IconifyIconOnline icon="ep:document" />
                文章 #{{ row.article_id }}
              </el-link>
            </div>
          </template>
        </el-table-column>

        <!-- 层级列 -->
        <el-table-column prop="level" label="层级" width="80" align="center">
          <template #default="{ row }">
            <el-tag v-if="!row.parent_id || row.parent_id === 0 || row.parent_id === -1" type="success" size="small">
              顶级
            </el-tag>
            <el-tag v-else type="info" size="small">
              L{{ row.level || 1 }}
            </el-tag>
          </template>
        </el-table-column>

        <!-- 状态列 -->
        <el-table-column prop="status" label="状态" width="90" align="center">
          <template #default="{ row }">
            <span :class="['status-badge', 'status-' + row.status]">
              {{ getStatusText(row.status) }}
            </span>
          </template>
        </el-table-column>

        <!-- 点赞数列 -->
        <el-table-column prop="likes" label="点赞" width="80" align="center">
          <template #default="{ row }">
            <div class="likes-cell">
              <IconifyIconOnline icon="ep:star-filled" class="like-icon" />
              <span>{{ row.likes || 0 }}</span>
            </div>
          </template>
        </el-table-column>

        <!-- 回复数列 -->
        <el-table-column prop="reply_count" label="回复数" width="150" align="center">
          <template #default="{ row }">
            <div class="reply-count-cell">
              <el-tag v-if="row.reply_count > 0" type="primary" size="small">
                {{ row.reply_count }}
              </el-tag>
              <span v-else class="empty-text">0</span>
              
              <!-- 懒加载按钮：只在第3层且有子评论时显示 -->
              <el-button 
                v-if="row.hasChildren && row.reply_count > 0"
                link
                type="primary" 
                size="small"
                @click="handleLoadMore(row)"
              >
                <IconifyIconOnline icon="ep:arrow-down" />
                加载更多
              </el-button>
            </div>
          </template>
        </el-table-column>

        <!-- 创建时间列 -->
        <el-table-column prop="create_time" label="评论时间" min-width="155" align="center">
          <template #default="{ row }">
            <span class="time-text">{{ formatDateTime(row.create_time) }}</span>
          </template>
        </el-table-column>

        <!-- 操作列 -->
        <el-table-column label="操作" fixed="right" width="240" align="center">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-button 
                v-if="row.status === 0" 
                link 
                type="success" 
                size="small" 
                @click="handleApprove(row)"
              >
                <IconifyIconOnline icon="ep:check" />
                通过
              </el-button>
              <el-button 
                v-if="row.status === 0" 
                link 
                type="warning" 
                size="small" 
                @click="handleReject(row)"
              >
                <IconifyIconOnline icon="ep:close" />
                拒绝
              </el-button>
              <el-button 
                link 
                type="primary" 
                size="small" 
                @click="handleDetail(row)"
              >
                <IconifyIconOnline icon="ep:view" />
                详情
              </el-button>
              <el-button 
                link 
                type="danger" 
                size="small" 
                @click="handleDelete(row)"
              >
                <IconifyIconOnline icon="ep:delete" />
                删除
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.currentPage"
          v-model:page-size="pagination.pageSize"
          :page-sizes="pagination.pageSizes"
          :background="true"
          layout="total, sizes, prev, pager, next, jumper"
          :total="pagination.total"
          size="small"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 评论详情对话框 -->
    <el-dialog 
      v-model="detailDialogVisible" 
      title="评论详情" 
      width="700px"
      :close-on-click-modal="false"
    >
      <div v-if="currentComment" class="comment-detail-wrapper">
        <!-- 主评论信息 -->
        <div class="main-comment-card">
          <div class="comment-user-header">
            <el-avatar :size="40" :src="currentComment.user?.avatar || defaultAvatar" />
            <div class="user-detail">
              <div class="username">{{ currentComment.user?.username || '未知用户' }}</div>
              <div class="meta-info">
                <span>ID: {{ currentComment.id }}</span>
                <span class="divider">|</span>
                <span>{{ formatDateTime(currentComment.create_time) }}</span>
              </div>
            </div>
            <el-tag :type="getStatusType(currentComment.status)" size="large">
              {{ getStatusText(currentComment.status) }}
            </el-tag>
          </div>
          
          <div class="comment-content-box">
            <div class="content-label">评论内容：</div>
            <div class="content-body">{{ currentComment.content }}</div>
          </div>
          
          <div class="comment-meta-grid">
            <div class="meta-item">
              <span class="label">文章：</span>
              <el-link type="primary" @click="handleViewArticle(currentComment.article_id)">
                #{{ currentComment.article_id }}
              </el-link>
            </div>
            <div class="meta-item">
              <span class="label">点赞：</span>
              <span class="value">{{ currentComment.likes || 0 }}</span>
            </div>
            <div class="meta-item">
              <span class="label">回复数：</span>
              <span class="value">{{ currentComment.reply_count || 0 }}</span>
            </div>
            <div class="meta-item" v-if="currentComment.parent_id > 0">
              <span class="label">父评论：</span>
              <el-link type="info" @click="handleViewParent(currentComment.parent_id)">
                #{{ currentComment.parent_id }}
              </el-link>
            </div>
          </div>
        </div>
        
        <!-- 回复列表 -->
        <div v-if="currentComment.replies && currentComment.replies.length > 0" class="replies-section">
          <div class="section-title">
            <IconifyIconOnline icon="ep:chat-dot-round" />
            回复列表 ({{ currentComment.replies.length }})
          </div>
          <div class="replies-list">
            <div v-for="reply in currentComment.replies" :key="reply.id" class="reply-item-card">
              <div class="reply-header">
                <el-avatar :size="32" :src="reply.user?.avatar || defaultAvatar" />
                <div class="reply-user-info">
                  <span class="reply-username">{{ reply.user?.username || '未知用户' }}</span>
                  <span class="reply-time">{{ formatDateTime(reply.create_time) }}</span>
                </div>
                <el-tag :type="getStatusType(reply.status)" size="small">
                  {{ getStatusText(reply.status) }}
                </el-tag>
              </div>
              <div class="reply-content">{{ reply.content }}</div>
            </div>
          </div>
        </div>
      </div>
      
      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
        <el-button 
          v-if="currentComment?.status === 0" 
          type="success" 
          @click="handleApprove(currentComment)"
        >
          <IconifyIconOnline icon="ep:check" />
          通过
        </el-button>
        <el-button 
          v-if="currentComment?.status === 0" 
          type="warning" 
          @click="handleReject(currentComment)"
        >
          <IconifyIconOnline icon="ep:close" />
          拒绝
        </el-button>
        <el-button type="danger" @click="handleDelete(currentComment)">
          <IconifyIconOnline icon="ep:delete" />
          删除
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { IconifyIconOnline } from "@/components/ReIcon";
import {
  getCommentsList,
  getCommentsTree,
  getCommentsChildren,
  deleteComment,
  batchDeleteComments,
  approveComment,
  rejectComment,
  batchApproveComments,
  getCommentsStats,
  type Comment,
  type CommentListParams
} from "@/api/comments";

defineOptions({
  name: "CommentsManage"
});

// 默认头像
const defaultAvatar = "https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png";

// 统计数据
const statsData = reactive({
  pending: 0,
  approved: 0,
  rejected: 0
});

// 搜索表单
const searchForm = reactive({
  article_id: "",
  status: null,
  keyword: ""
});

// 表格数据
const tableData = ref([]);
const loading = ref(false);
const selectedIds = ref<number[]>([]);

// 分页配置
const pagination = reactive({
  currentPage: 1,
  pageSize: 20,
  pageSizes: [10, 20, 50, 100],
  total: 0
});

// 详情对话框
const detailDialogVisible = ref(false);
const currentComment = ref<any>(null);

// 获取状态文本
const getStatusText = (status: number): string => {
  const statusMap: Record<number, string> = {
    0: "待审核",
    1: "已通过",
    2: "已拒绝"
  };
  return statusMap[status] || "未知";
};

// 获取状态类型
const getStatusType = (status: number): string => {
  const typeMap: Record<number, string> = {
    0: "warning",
    1: "success",
    2: "danger"
  };
  return typeMap[status] || "info";
};

// 格式化时间
const formatDateTime = (timestamp: any): string => {
  if (!timestamp) return "-";
  
  // 如果是时间戳（数字）
  if (typeof timestamp === "number") {
    const date = new Date(timestamp * 1000);
    return date.toLocaleString("zh-CN", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit"
    });
  }
  
  // 如果是日期字符串
  return timestamp;
};

// 加载统计数据
const loadStats = async () => {
  try {
    const response = await getCommentsStats();
    if (response.code === 200 && response.data) {
      statsData.pending = response.data.pending || 0;
      statsData.approved = response.data.approved || 0;
      statsData.rejected = response.data.rejected || 0;
    }
  } catch (error) {
    console.error("加载统计数据失败:", error);
  }
};

// 加载评论列表
const loadCommentsList = async () => {
  loading.value = true;
  try {
    // 如果指定了文章ID，使用评论树接口（显示嵌套结构）
    if (searchForm.article_id) {
      const articleId = Number(searchForm.article_id);
      const response = await getCommentsTree(articleId);
      
      if (response.code === 200) {
        tableData.value = response.data || [];
        pagination.total = response.data?.length || 0;
      } else {
        ElMessage.error(response.msg || "加载失败");
        tableData.value = [];
      }
    } else {
      // 没有指定文章ID，加载所有评论（分页）
      const params = {
        page: pagination.currentPage,
        limit: pagination.pageSize,
        status: searchForm.status,
        keyword: searchForm.keyword
      };
      
      const response = await getCommentsList(params);
      
      if (response.code === 200 && response.data) {
        tableData.value = response.data.list || [];
        pagination.total = response.data.total || 0;
      } else {
        ElMessage.error(response.msg || "加载失败");
        tableData.value = [];
      }
    }
  } catch (error: any) {
    console.error("加载评论列表失败:", error);
    ElMessage.error(error?.message || "加载评论列表失败");
    tableData.value = [];
  } finally {
    loading.value = false;
  }
};

// 搜索
const handleSearch = () => {
  pagination.currentPage = 1;
  loadCommentsList();
};

// 重置
const handleReset = () => {
  searchForm.article_id = "";
  searchForm.status = null;
  searchForm.keyword = "";
  handleSearch();
};

// 选择变化
const handleSelectionChange = (selection: any[]) => {
  selectedIds.value = selection.map(item => item.id);
};

// 分页变化
const handleSizeChange = (size: number) => {
  pagination.pageSize = size;
  loadCommentsList();
};

const handleCurrentChange = (page: number) => {
  pagination.currentPage = page;
  loadCommentsList();
};

// 通过评论
const handleApprove = async (row: any) => {
  try {
    await ElMessageBox.confirm("确定通过该评论吗？", "提示", {
      type: "warning"
    });
    
    const response = await approveComment(row.id);
    if (response.code === 200) {
      ElMessage.success("操作成功");
      loadCommentsList();
      loadStats();
      detailDialogVisible.value = false;
    } else {
      ElMessage.error(response.msg || "操作失败");
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("通过评论失败:", error);
    }
  }
};

// 拒绝评论
const handleReject = async (row: any) => {
  try {
    await ElMessageBox.confirm("确定拒绝该评论吗？", "提示", {
      type: "warning"
    });
    
    const response = await rejectComment(row.id);
    if (response.code === 200) {
      ElMessage.success("操作成功");
      loadCommentsList();
      loadStats();
      detailDialogVisible.value = false;
    } else {
      ElMessage.error(response.msg || "操作失败");
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("拒绝评论失败:", error);
    }
  }
};

// 删除评论
const handleDelete = async (row: any) => {
  try {
    await ElMessageBox.confirm("确定删除该评论吗？删除后不可恢复！", "警告", {
      type: "error"
    });
    
    const response = await deleteComment(row.id);
    if (response.code === 200) {
      ElMessage.success("删除成功");
      loadCommentsList();
      loadStats();
      detailDialogVisible.value = false;
    } else {
      ElMessage.error(response.msg || "删除失败");
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("删除评论失败:", error);
    }
  }
};

// 批量通过
const handleBatchApprove = async () => {
  try {
    await ElMessageBox.confirm(`确定通过选中的 ${selectedIds.value.length} 条评论吗？`, "提示", {
      type: "warning"
    });
    
    const response = await batchApproveComments(selectedIds.value);
    if (response.code === 200) {
      ElMessage.success("操作成功");
      selectedIds.value = [];
      loadCommentsList();
      loadStats();
    } else {
      ElMessage.error(response.msg || "操作失败");
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("批量通过失败:", error);
    }
  }
};

// 批量删除
const handleBatchDelete = async () => {
  try {
    await ElMessageBox.confirm(`确定删除选中的 ${selectedIds.value.length} 条评论吗？`, "警告", {
      type: "error"
    });
    
    const response = await batchDeleteComments(selectedIds.value);
    if (response.code === 200) {
      ElMessage.success("删除成功");
      selectedIds.value = [];
      loadCommentsList();
      loadStats();
    } else {
      ElMessage.error(response.msg || "删除失败");
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("批量删除失败:", error);
    }
  }
};

// 查看详情
const handleDetail = async (row: any) => {
  try {
    loading.value = true;
    // 获取完整的评论树（包括所有回复）
    const response = await getCommentsChildren(row.id);
    
    if (response.code === 200) {
      // 合并评论信息和回复列表
      currentComment.value = {
        ...row,
        replies: response.data || []
      };
      detailDialogVisible.value = true;
    } else {
      ElMessage.error(response.msg || "获取评论详情失败");
    }
  } catch (error: any) {
    console.error("获取评论详情失败:", error);
    ElMessage.error(error?.message || "获取评论详情失败");
  } finally {
    loading.value = false;
  }
};

// 查看文章
const handleViewArticle = (articleId: number) => {
  ElMessage.info(`跳转到文章 #${articleId}`);
  // TODO: 路由跳转到文章详情
};

// 查看父评论
const handleViewParent = (parentId: number) => {
  ElMessage.info(`查看父评论 #${parentId}`);
  // TODO: 高亮显示父评论
};

// 懒加载更多回复
const handleLoadMore = async (row: any) => {
  try {
    loading.value = true;
    const response = await getCommentsChildren(row.id);
    
    if (response.code === 200) {
      // 将懒加载的数据追加到当前评论的replies中
      if (!row.replies) {
        row.replies = [];
      }
      row.replies = response.data || [];
      row.hasChildren = false; // 隐藏加载按钮
      ElMessage.success(`加载了 ${response.data?.length || 0} 条回复`);
    } else {
      ElMessage.error(response.msg || "加载失败");
    }
  } catch (error: any) {
    console.error("加载更多回复失败:", error);
    ElMessage.error(error?.message || "加载失败");
  } finally {
    loading.value = false;
  }
};

// ===== 测试按钮操作 =====

// 测试获取评论树
const handleTestGetComments = async () => {
  if (!searchForm.article_id) {
    ElMessage.warning("请先输入文章ID");
    return;
  }
  
  try {
    const articleId = Number(searchForm.article_id);
    ElMessage.info(`测试：获取文章 #${articleId} 的评论树结构`);
    console.log(`测试API: GET /api/v1/comments/getComments/${articleId}`);
    
    const response = await getCommentsTree(articleId);
    console.log("响应数据:", response);
    
    if (response.code === 200) {
      ElMessage.success(`获取成功，共 ${response.data?.length || 0} 条顶级评论`);
    } else {
      ElMessage.error(response.msg || "获取失败");
    }
  } catch (error: any) {
    console.error("测试失败:", error);
    ElMessage.error(error?.message || "测试失败");
  }
};

// 测试获取子评论
const handleTestGetChildren = async () => {
  if (selectedIds.value.length !== 1) {
    ElMessage.warning("请选择一条评论");
    return;
  }
  
  try {
    const parentId = selectedIds.value[0];
    ElMessage.info(`测试：获取评论 #${parentId} 的子评论`);
    console.log(`测试API: GET /api/v1/comments/getCommentsChildren/${parentId}`);
    
    const response = await getCommentsChildren(parentId);
    console.log("响应数据:", response);
    
    if (response.code === 200) {
      ElMessage.success(`获取成功，共 ${response.data?.length || 0} 条子评论`);
    } else {
      ElMessage.error(response.msg || "获取失败");
    }
  } catch (error: any) {
    console.error("测试失败:", error);
    ElMessage.error(error?.message || "测试失败");
  }
};

// 测试添加评论
const handleTestAddComment = () => {
  ElMessage.info("测试：添加评论");
  console.log("测试API: POST /api/v1/comments/add");
  // TODO: 调用测试接口
};

// 测试删除评论
const handleTestDeleteComment = async () => {
  if (selectedIds.value.length !== 1) {
    ElMessage.warning("请选择一条评论");
    return;
  }
  
  try {
    const commentId = selectedIds.value[0];
    ElMessage.info(`测试：删除评论 #${commentId}`);
    console.log(`测试API: POST /api/v1/comments/delete`);
    
    const response = await deleteComment(commentId);
    console.log("响应数据:", response);
    
    if (response.code === 200) {
      ElMessage.success("删除成功");
      loadCommentsList();
    } else {
      ElMessage.error(response.msg || "删除失败");
    }
  } catch (error: any) {
    console.error("测试失败:", error);
    ElMessage.error(error?.message || "测试失败");
  }
};

// 测试审核评论
const handleTestApprove = async () => {
  if (selectedIds.value.length !== 1) {
    ElMessage.warning("请选择一条评论");
    return;
  }
  
  try {
    const commentId = selectedIds.value[0];
    ElMessage.info(`测试：审核评论 #${commentId}`);
    console.log(`测试API: POST /api/v1/comments/approve`);
    
    const response = await approveComment(commentId);
    console.log("响应数据:", response);
    
    if (response.code === 200) {
      ElMessage.success("审核通过");
      loadCommentsList();
    } else {
      ElMessage.error(response.msg || "审核失败");
    }
  } catch (error: any) {
    console.error("测试失败:", error);
    ElMessage.error(error?.message || "测试失败");
  }
};

// 页面加载
onMounted(() => {
  loadStats();
  loadCommentsList(); // 初始加载所有评论
});
</script>

<style lang="scss" scoped>
.comments-container {
  padding: 16px;
  background: var(--el-bg-color-page);
  min-height: calc(100vh - 100px);
}

// 统计卡片
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 20px;

  .stat-card {
    border-radius: 8px;
    transition: all 0.2s ease;
    background: var(--el-bg-color);
    border: 1px solid var(--el-border-color-light);
    overflow: hidden;

    &:hover {
      border-color: var(--el-color-primary-light-5);
      box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.06);
    }

    :deep(.el-card__body) {
      padding: 20px;
    }
  }

  .stat-content {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
    background: var(--el-fill-color);

    &.stat-icon-total {
      color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
    }

    &.stat-icon-pending {
      color: var(--el-color-warning);
      background: var(--el-color-warning-light-9);
    }

    &.stat-icon-approved {
      color: var(--el-color-success);
      background: var(--el-color-success-light-9);
    }

    &.stat-icon-rejected {
      color: var(--el-color-danger);
      background: var(--el-color-danger-light-9);
    }
  }

  .stat-info {
    flex: 1;

    .stat-label {
      font-size: 13px;
      color: var(--el-text-color-secondary);
      margin-bottom: 8px;
      font-weight: 400;
    }

    .stat-value {
      font-size: 26px;
      font-weight: 600;
      color: var(--el-text-color-primary);
      line-height: 1;
    }
  }
}

// 主卡片
.main-card {
  border-radius: 8px;
  background: var(--el-bg-color);
  border: 1px solid var(--el-border-color-light);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);

  :deep(.el-card__body) {
    padding: 24px;
  }

  .el-row {
    margin-bottom: 16px;
  }

  .action-buttons {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
  }
}

// 测试区域
.test-area {
  margin: 16px 0;
  background: var(--el-fill-color-blank);
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;

  :deep(.el-card__header) {
    background: var(--el-fill-color-lighter);
    border-bottom: 1px solid var(--el-border-color-light);
    padding: 12px 16px;
  }

  :deep(.el-card__body) {
    padding: 16px;
  }

  .test-area-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .test-area-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
      color: var(--el-text-color-primary);
      font-size: 14px;
    }

    .selected-count {
      font-size: 12px;
      color: var(--el-text-color-regular);
      background: var(--el-fill-color);
      padding: 4px 10px;
      border-radius: 4px;
      font-weight: 400;
    }
  }

  .test-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
}

// 表格样式
.modern-table {
  margin-top: 16px;
  border-radius: 8px;
  overflow: hidden;

  // 用户单元格
  .user-cell {
    display: flex;
    align-items: center;
    gap: 10px;

    .user-info {
      text-align: left;

      .username {
        font-weight: 500;
        color: var(--el-text-color-primary);
        font-size: 14px;
      }

      .user-id {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        margin-top: 2px;
      }
    }
  }

  // 内容单元格
  .content-text {
    color: var(--el-text-color-regular);
    line-height: 1.6;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    text-align: left;
  }

  // 文章信息
  .article-info {
    .el-link {
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
  }

  // 状态徽章
  .status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 400;

    &.status-0 {
      background: var(--el-color-warning-light-9);
      color: var(--el-color-warning);
    }

    &.status-1 {
      background: var(--el-color-success-light-9);
      color: var(--el-color-success);
    }

    &.status-2 {
      background: var(--el-color-danger-light-9);
      color: var(--el-color-danger);
    }
  }

  // 点赞单元格
  .likes-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;

    .like-icon {
      color: var(--el-color-warning);
      font-size: 14px;
    }
  }

  // 回复数单元格（包含懒加载按钮）
  .reply-count-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;

    .el-button {
      font-size: 12px;
      padding: 2px 4px;
      margin-top: 2px;
    }
  }

  .time-text {
    color: var(--el-text-color-secondary);
    font-size: 13px;
  }

  .empty-text {
    color: var(--el-text-color-placeholder);
  }

  // 操作按钮容器
  .action-buttons {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    white-space: nowrap;
    
    .el-button {
      margin: 0;
    }
  }
}

// 分页
.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--el-border-color-light);
}

// 详情对话框样式
.comment-detail-wrapper {
  .main-comment-card {
    background: var(--el-fill-color-blank);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
    border: 1px solid var(--el-border-color-light);

    .comment-user-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;

      .user-detail {
        flex: 1;

        .username {
          font-size: 15px;
          font-weight: 500;
          color: var(--el-text-color-primary);
          margin-bottom: 4px;
        }

        .meta-info {
          font-size: 13px;
          color: var(--el-text-color-secondary);

          .divider {
            margin: 0 8px;
          }
        }
      }
    }

    .comment-content-box {
      margin-bottom: 16px;

      .content-label {
        font-size: 13px;
        font-weight: 400;
        color: var(--el-text-color-secondary);
        margin-bottom: 8px;
      }

      .content-body {
        padding: 12px 16px;
        background: var(--el-fill-color-lighter);
        border-radius: 4px;
        line-height: 1.6;
        color: var(--el-text-color-primary);
        white-space: pre-wrap;
        word-break: break-word;
        border: 1px solid var(--el-border-color-lighter);
      }
    }

    .comment-meta-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;

      .meta-item {
        font-size: 14px;

        .label {
          color: var(--el-text-color-secondary);
          margin-right: 4px;
        }

        .value {
          color: var(--el-text-color-primary);
          font-weight: 500;
        }
      }
    }
  }

  .replies-section {
    .section-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      font-weight: 500;
      color: var(--el-text-color-primary);
      margin-bottom: 12px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--el-border-color-light);
    }

    .replies-list {
      max-height: 400px;
      overflow-y: auto;

      .reply-item-card {
        background: var(--el-fill-color-blank);
        border: 1px solid var(--el-border-color-light);
        border-radius: 6px;
        padding: 14px;
        margin-bottom: 8px;
        transition: all 0.2s ease;

        &:hover {
          background: var(--el-fill-color-lighter);
        }

        &:last-child {
          margin-bottom: 0;
        }

        .reply-header {
          display: flex;
          align-items: center;
          gap: 8px;
          margin-bottom: 8px;

          .reply-user-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;

            .reply-username {
              font-size: 14px;
              font-weight: 500;
              color: var(--el-text-color-primary);
            }

            .reply-time {
              font-size: 12px;
              color: var(--el-text-color-secondary);
            }
          }
        }

        .reply-content {
          font-size: 14px;
          line-height: 1.6;
          color: var(--el-text-color-regular);
          padding-left: 40px;
          word-break: break-word;
        }
      }
    }
  }
}

// 响应式
@media (max-width: 768px) {
  .stats-container {
    grid-template-columns: repeat(2, 1fr);
  }

  .action-buttons {
    justify-content: flex-start !important;
    margin-top: 8px;
  }
}
</style>

