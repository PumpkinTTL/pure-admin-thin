<template>
  <div class="comments-container">
    <el-card>
      <template #header>
        <el-row :gutter="10">
          <el-col :xs="24" :sm="24" :md="4" :lg="3" :xl="2">
            <span class="header-title">评论管理</span>
          </el-col>
          
          <!-- 文章ID筛选 -->
          <el-col :xs="24" :sm="8" :md="4" :lg="3" :xl="3">
            <el-input 
              v-model="searchForm.article_id" 
              placeholder="文章ID" 
              clearable 
              size="default"
              @keyup.enter="handleSearch"
            />
          </el-col>
          
          <!-- 状态筛选 -->
          <el-col :xs="24" :sm="8" :md="4" :lg="3" :xl="3">
            <el-select 
              v-model="searchForm.status" 
              placeholder="状态" 
              clearable 
              size="default"
              style="width: 100%"
              @change="handleSearch"
            >
              <el-option label="待审核" :value="0" />
              <el-option label="已通过" :value="1" />
              <el-option label="已拒绝" :value="2" />
            </el-select>
          </el-col>
          
          <!-- 关键词搜索 -->
          <el-col :xs="24" :sm="8" :md="5" :lg="4" :xl="4">
            <el-input 
              v-model="searchForm.keyword" 
              placeholder="搜索内容/用户" 
              clearable 
              size="default"
              @keyup.enter="handleSearch"
            />
          </el-col>
          
          <!-- 搜索按钮 -->
          <el-col :xs="12" :sm="6" :md="3" :lg="2" :xl="2">
            <el-button type="primary" :icon="Search" size="default" @click="handleSearch">
              搜索
            </el-button>
          </el-col>
          
          <el-col :xs="12" :sm="6" :md="3" :lg="2" :xl="2">
            <el-button :icon="RefreshLeft" size="default" @click="handleReset">
              重置
            </el-button>
          </el-col>
        </el-row>
      </template>

      <el-divider content-position="left" style="margin: 16px 0 12px 0;">
        <span style="font-size: 13px; color: var(--el-text-color-regular);">数据列表</span>
        <el-tag size="small" type="info" style="margin-left: 8px;" v-if="pagination.total > 0">
          共 {{ pagination.total }} 条
        </el-tag>
      </el-divider>

      <!-- 测试操作区域 -->
      <el-card class="test-area" shadow="never">
        <template #header>
          <div class="test-area-header">
            <div style="display: flex; align-items: center; gap: 12px;">
              <span class="test-area-title">
                <IconifyIconOnline icon="ep:experiment" />
                测试操作区域
              </span>
              <div class="stats-tags">
                <el-tag size="small" type="warning">待审核 {{ statsData.pending }}</el-tag>
                <el-tag size="small" type="success">已通过 {{ statsData.approved }}</el-tag>
                <el-tag size="small" type="danger">已拒绝 {{ statsData.rejected }}</el-tag>
              </div>
            </div>
            <span class="selected-count">已选择 {{ selectedIds.length }} 条评论</span>
          </div>
        </template>
        
        <div class="test-buttons">
          <el-button 
            type="primary" 
            size="small" 
            @click="showAddDialog(false)"
          >
            <IconifyIconOnline icon="ep:edit" />
            测试添加评论
          </el-button>
          
          <el-button 
            type="success" 
            size="small" 
            @click="showAddDialog(true)"
            :disabled="selectedIds.length !== 1"
          >
            <IconifyIconOnline icon="ep:chat-dot-round" />
            测试回复
          </el-button>
          
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
            @click="handleTestApprove"
            :disabled="selectedIds.length !== 1"
          >
            <IconifyIconOnline icon="ep:check" />
            测试审核
          </el-button>
          
          <el-button 
            type="danger" 
            size="small" 
            @click="handleTestDeleteComment"
            :disabled="selectedIds.length !== 1"
          >
            <IconifyIconOnline icon="ep:delete" />
            测试删除
          </el-button>
          
          <el-button 
            type="info" 
            size="small" 
            @click="handleBatchApprove"
            :disabled="selectedIds.length === 0"
          >
            <IconifyIconOnline icon="ep:select" />
            批量通过
          </el-button>
          
          <el-button 
            type="danger" 
            size="small" 
            @click="handleBatchDelete"
            :disabled="selectedIds.length === 0"
          >
            <IconifyIconOnline icon="ep:circle-close" />
            批量删除
          </el-button>
        </div>
      </el-card>

      <!-- 数据表格 -->
      <el-table 
        :data="tableData" 
        v-loading="loading"
        border
        size="small"
        row-key="id"
        :tree-props="{ children: 'replies', hasChildren: 'hasChildren' }"
        @selection-change="handleSelectionChange"
        :header-cell-style="{ textAlign: 'center', backgroundColor: '#F5F7FA' }"
        :cell-style="{ textAlign: 'center' }"
        style="width: 100%"
      >
        <el-table-column type="selection" width="45" fixed />
        <el-table-column prop="id" label="ID" width="60" />
        
        <!-- 用户信息 -->
        <el-table-column label="用户" width="140">
          <template #default="{ row }">
            <div class="user-cell">
              <el-avatar :size="28" :src="row.user?.avatar || defaultAvatar" />
              <div class="user-info">
                <div class="username">{{ row.user?.username || '未知用户' }}</div>
                <div class="user-id">ID: {{ row.user_id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        
        <!-- 评论内容 -->
        <el-table-column prop="content" label="评论内容" min-width="200" show-overflow-tooltip />
        
        <!-- 文章信息 -->
        <el-table-column label="文章" width="100">
          <template #default="{ row }">
            <el-link type="primary" :underline="false" size="small">
              #{{ row.article_id }}
            </el-link>
          </template>
        </el-table-column>
        
        <!-- 状态 -->
        <el-table-column prop="status" label="状态" width="85">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        
        <!-- 点赞数 -->
        <el-table-column prop="likes" label="点赞" width="70" align="center">
          <template #default="{ row }">
            <span class="likes-text">
              <IconifyIconOnline icon="ep:star-filled" class="like-icon" />
              {{ row.likes || 0 }}
            </span>
          </template>
        </el-table-column>
        
        <!-- 回复数 -->
        <el-table-column prop="reply_count" label="回复" width="70" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.reply_count > 0" type="primary" size="small">
              {{ row.reply_count }}
            </el-tag>
            <span v-else class="empty-text">0</span>
          </template>
        </el-table-column>
        
        <!-- 创建时间 -->
        <el-table-column prop="create_time" label="评论时间" width="155">
          <template #default="{ row }">
            <span class="time-text">{{ formatDateTime(row.create_time) }}</span>
          </template>
        </el-table-column>
        
        <!-- 操作 -->
        <el-table-column label="操作" fixed="right" width="200" align="center">
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
      <el-pagination
        v-model:current-page="pagination.currentPage"
        v-model:page-size="pagination.pageSize"
        :page-sizes="pagination.pageSizes"
        :background="true"
        layout="total, sizes, prev, pager, next, jumper"
        :total="pagination.total"
        size="small"
        style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--el-border-color-lighter);"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
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
              <el-link type="primary">
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
              <el-link type="info">
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

    <!-- 添加评论对话框 -->
    <el-dialog 
      v-model="addDialogVisible" 
      :title="addForm.parent_id > 0 ? '测试回复评论' : '测试添加评论'" 
      width="600px"
    >
      <el-form :model="addForm" label-width="80px">
        <el-form-item label="文章ID">
          <el-input v-model="addForm.article_id" :disabled="addForm.parent_id > 0" />
        </el-form-item>
        <el-form-item label="父评论ID" v-if="addForm.parent_id > 0">
          <el-input v-model="addForm.parent_id" disabled />
        </el-form-item>
        <el-form-item label="用户ID">
          <el-input-number v-model="addForm.user_id" :min="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="内容">
          <el-input v-model="addForm.content" type="textarea" :rows="5" maxlength="500" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="addDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitAdd">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessageBox } from "element-plus";
import { message } from "@/utils/message";
import { IconifyIconOnline } from "@/components/ReIcon";
import { Search, RefreshLeft, Select, Delete } from "@element-plus/icons-vue";
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
  addComment,
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
  pageSize: 5,
  pageSizes: [5, 10, 20, 50, 100],
  total: 0
});

// 详情对话框
const detailDialogVisible = ref(false);
const currentComment = ref<any>(null);

// 添加评论对话框
const addDialogVisible = ref(false);
const addForm = reactive({
  article_id: "",
  parent_id: 0,
  content: "",
  user_id: 1
});

// 获取状态文本
const getStatusText = (status: any): string => {
  // 将status转换为数字
  const statusNum = Number(status);
  const statusMap: Record<number, string> = {
    0: "待审核",
    1: "已通过",
    2: "已拒绝"
  };
  return statusMap[statusNum] || `未知(${status})`;
};

// 获取状态类型
const getStatusType = (status: any): string => {
  // 将status转换为数字
  const statusNum = Number(status);
  const typeMap: Record<number, string> = {
    0: "warning",
    1: "success",
    2: "danger"
  };
  return typeMap[statusNum] || "info";
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
        message(response.msg || "加载失败", { type: "error" });
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
        message(response.msg || "加载失败", { type: "error" });
        tableData.value = [];
      }
    }
  } catch (error: any) {
    console.error("加载评论列表失败:", error);
    message(error?.message || "加载评论列表失败", { type: "error" });
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
      message("操作成功", { type: "success" });
      loadCommentsList();
      loadStats();
      detailDialogVisible.value = false;
    } else {
      message(response.msg || "操作失败", { type: "error" });
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("通过评论失败:", error);
      message("操作失败", { type: "error" });
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
      message("操作成功", { type: "success" });
      loadCommentsList();
      loadStats();
      detailDialogVisible.value = false;
    } else {
      message(response.msg || "操作失败", { type: "error" });
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("拒绝评论失败:", error);
      message("操作失败", { type: "error" });
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
      message("删除成功", { type: "success" });
      loadCommentsList();
      loadStats();
      detailDialogVisible.value = false;
    } else {
      message(response.msg || "删除失败", { type: "error" });
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("删除评论失败:", error);
      message("删除失败", { type: "error" });
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
      message("操作成功", { type: "success" });
      selectedIds.value = [];
      loadCommentsList();
      loadStats();
    } else {
      message(response.msg || "操作失败", { type: "error" });
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("批量通过失败:", error);
      message("操作失败", { type: "error" });
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
      message("删除成功", { type: "success" });
      selectedIds.value = [];
      loadCommentsList();
      loadStats();
    } else {
      message(response.msg || "删除失败", { type: "error" });
    }
  } catch (error: any) {
    if (error !== "cancel") {
      console.error("批量删除失败:", error);
      message("删除失败", { type: "error" });
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
      message(response.msg || "获取评论详情失败", { type: "error" });
    }
  } catch (error: any) {
    console.error("获取评论详情失败:", error);
    message(error?.message || "获取评论详情失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

// ===== 测试按钮操作 =====

// 显示添加对话框
const showAddDialog = (isReply: boolean) => {
  if (isReply) {
    if (selectedIds.value.length !== 1) {
      message("请选择一条评论", { type: "warning" });
      return;
    }
    const selected = tableData.value.find(item => item.id === selectedIds.value[0]);
    if (!selected) return;
    addForm.article_id = selected.article_id.toString();
    addForm.parent_id = selected.id;
  } else {
    addForm.article_id = searchForm.article_id || "";
    addForm.parent_id = 0;
  }
  addForm.content = "";
  addDialogVisible.value = true;
};

// 提交添加
const submitAdd = async () => {
  if (!addForm.article_id) {
    message("请输入文章ID", { type: "warning" });
    return;
  }
  if (!addForm.content.trim()) {
    message("请输入内容", { type: "warning" });
    return;
  }
  try {
    const response = await addComment({
      article_id: Number(addForm.article_id),
      parent_id: addForm.parent_id,
      content: addForm.content,
      user_id: addForm.user_id
    });
    if (response.code === 200) {
      message("添加成功", { type: "success" });
      addDialogVisible.value = false;
      loadCommentsList();
      loadStats();
    } else {
      message(response.msg || "添加失败", { type: "error" });
    }
  } catch (error: any) {
    message(error?.message || "操作失败", { type: "error" });
  }
};

// 测试获取评论树
const handleTestGetComments = async () => {
  if (!searchForm.article_id) {
    message("请先输入文章ID", { type: "warning" });
    return;
  }
  
  try {
    const articleId = Number(searchForm.article_id);
    message(`测试：获取文章 #${articleId} 的评论树结构`, { type: "info" });
    console.log(`测试API: GET /api/v1/comments/getComments/${articleId}`);
    
    const response = await getCommentsTree(articleId);
    console.log("响应数据:", response);
    
    if (response.code === 200) {
      message(`获取成功，共 ${response.data?.length || 0} 条顶级评论`, { type: "success" });
    } else {
      message(response.msg || "获取失败", { type: "error" });
    }
  } catch (error: any) {
    console.error("测试失败:", error);
    message(error?.message || "测试失败", { type: "error" });
  }
};

// 测试获取子评论
const handleTestGetChildren = async () => {
  if (selectedIds.value.length !== 1) {
    message("请选择一条评论", { type: "warning" });
    return;
  }
  
  try {
    const parentId = selectedIds.value[0];
    message(`测试：获取评论 #${parentId} 的子评论`, { type: "info" });
    console.log(`测试API: GET /api/v1/comments/getCommentsChildren/${parentId}`);
    
    const response = await getCommentsChildren(parentId);
    console.log("响应数据:", response);
    
    if (response.code === 200) {
      message(`获取成功，共 ${response.data?.length || 0} 条子评论`, { type: "success" });
    } else {
      message(response.msg || "获取失败", { type: "error" });
    }
  } catch (error: any) {
    console.error("测试失败:", error);
    message(error?.message || "测试失败", { type: "error" });
  }
};

// 测试删除评论
const handleTestDeleteComment = async () => {
  if (selectedIds.value.length !== 1) {
    message("请选择一条评论", { type: "warning" });
    return;
  }
  
  try {
    const commentId = selectedIds.value[0];
    message(`测试：删除评论 #${commentId}`, { type: "info" });
    console.log(`测试API: POST /api/v1/comments/delete`);
    
    const response = await deleteComment(commentId);
    console.log("响应数据:", response);
    
    if (response.code === 200) {
      message("删除成功", { type: "success" });
      loadCommentsList();
      loadStats();
    } else {
      message(response.msg || "删除失败", { type: "error" });
    }
  } catch (error: any) {
    console.error("测试失败:", error);
    message(error?.message || "测试失败", { type: "error" });
  }
};

// 测试审核评论
const handleTestApprove = async () => {
  if (selectedIds.value.length !== 1) {
    message("请选择一条评论", { type: "warning" });
    return;
  }
  
  try {
    const commentId = selectedIds.value[0];
    message(`测试：审核评论 #${commentId}`, { type: "info" });
    console.log(`测试API: POST /api/v1/comments/approve`);
    
    const response = await approveComment(commentId);
    console.log("响应数据:", response);
    
    if (response.code === 200) {
      message("审核通过", { type: "success" });
      loadCommentsList();
      loadStats();
    } else {
      message(response.msg || "审核失败", { type: "error" });
    }
  } catch (error: any) {
    console.error("测试失败:", error);
    message(error?.message || "测试失败", { type: "error" });
  }
};

// 页面加载
onMounted(() => {
  loadStats();
  loadCommentsList();
});
</script>

<style lang="scss" scoped>
.comments-container {
  padding: 12px;
  
  .header-title {
    font-weight: 600;
    font-size: 15px;
    line-height: 32px;
  }
  
  
  // 测试区域
  .test-area {
    margin: 12px 0;
    background: var(--el-fill-color-blank);
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;

    :deep(.el-card__header) {
      background: var(--el-fill-color-lighter);
      border-bottom: 1px solid var(--el-border-color-light);
      padding: 8px 12px;
    }

    :deep(.el-card__body) {
      padding: 12px;
    }

    .test-area-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;

      .test-area-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        color: var(--el-text-color-primary);
        font-size: 14px;
      }
      
      .stats-tags {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: nowrap;
        
        :deep(.el-tag) {
          white-space: nowrap;
          .iconify {
            display: inline-block;
            vertical-align: middle;
          }
        }
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
  
  // 用户单元格
  .user-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    
    .user-info {
      text-align: left;
      
      .username {
        font-weight: 500;
        color: var(--el-text-color-primary);
        font-size: 13px;
      }
      
      .user-id {
        font-size: 11px;
        color: var(--el-text-color-secondary);
        margin-top: 1px;
      }
    }
  }
  
  // 点赞文本
  .likes-text {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 12px;
    
    .like-icon {
      color: var(--el-color-warning);
      font-size: 13px;
    }
  }
  
  .time-text {
    color: var(--el-text-color-secondary);
    font-size: 12px;
  }
  
  .empty-text {
    color: var(--el-text-color-placeholder);
    font-size: 12px;
  }
  
  // 操作按钮
  .action-buttons {
    display: inline-flex;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: center;
    
    :deep(.el-button) {
      padding: 4px 8px;
      font-size: 12px;
    }
  }
  
  // 表格样式优化
  :deep(.el-table) {
    font-size: 12px;
    
    .el-table__header th {
      font-size: 12px;
      padding: 8px 0;
    }
    
    .el-table__body td {
      padding: 6px 0;
    }
  }
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

</style>
