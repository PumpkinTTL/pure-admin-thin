<template>
  <div class="likes-container">
    <el-card>
      <template #header>
        <el-row :gutter="10">
          <el-col :xs="24" :sm="24" :md="4" :lg="3" :xl="2">
            <span class="header-title">点赞管理</span>
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-input v-model="searchForm.user_id" placeholder="用户ID" clearable size="default" />
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-select v-model="searchForm.target_type" placeholder="类型" clearable style="width: 100%" size="default">
              <el-option label="全部" value="" />
              <el-option label="评论" value="comment" />
              <el-option label="文章" value="article" />
            </el-select>
          </el-col>
          <el-col :xs="12" :sm="6" :md="3" :lg="2" :xl="2">
            <el-button type="primary" :icon="Search" @click="handleSearch">
              搜索
            </el-button>
          </el-col>
          <el-col :xs="12" :sm="6" :md="3" :lg="2" :xl="2">
            <el-button :icon="RefreshLeft" @click="handleReset">
              重置
            </el-button>
          </el-col>
        </el-row>
      </template>

      <el-divider content-position="left">
        数据列表
        <el-tag size="small" type="info" class="ml-2" v-if="pagination.total > 0">
          共 {{ pagination.total }} 条记录
        </el-tag>
      </el-divider>

      <!-- 表格 -->
      <el-table
        border
        :data="tableData"
        :cell-style="{ textAlign: 'center' }"
        style="width: 100%"
        :header-cell-style="{ textAlign: 'center', backgroundColor: '#F5F7FA' }"
        size="small"
        v-loading="loading"
      >
        <el-table-column label="ID" prop="id" width="80" />
        
        <el-table-column label="用户" width="180">
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

        <el-table-column label="类型" prop="target_type" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.target_type)" size="small">
              {{ getTypeName(row.target_type) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="目标ID" prop="target_id" width="100" />

        <el-table-column label="点赞时间" prop="create_time" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.create_time) }}
          </template>
        </el-table-column>

        <el-table-column label="更新时间" prop="update_time" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.update_time) }}
          </template>
        </el-table-column>

        <el-table-column fixed="right" label="操作" width="120">
          <template #default="{ row }">
            <el-button
              link
              type="primary"
              size="small"
              @click="handleViewTarget(row)"
            >
              查看目标
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.currentPage"
        v-model:page-size="pagination.pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :background="true"
        layout="total, sizes, prev, pager, next, jumper"
        :total="pagination.total"
        style="margin-top: 20px"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { message } from "@/utils/message";
import { Search, RefreshLeft } from "@element-plus/icons-vue";
import { getLikesList, type LikeRecord } from "@/api/likes";

defineOptions({
  name: "Likes"
});

// 默认头像
const defaultAvatar = "https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png";

// 搜索表单
const searchForm = reactive({
  user_id: "",
  target_type: ""
});

// 表格数据
const tableData = ref<LikeRecord[]>([]);
const loading = ref(false);

// 分页配置
const pagination = reactive({
  currentPage: 1,
  pageSize: 10,
  total: 0
});

// 获取类型名称
const getTypeName = (type: string): string => {
  const typeMap: Record<string, string> = {
    comment: "评论",
    article: "文章"
  };
  return typeMap[type] || type;
};

// 获取类型标签类型
const getTypeTagType = (type: string): string => {
  const typeMap: Record<string, string> = {
    comment: "primary",
    article: "success"
  };
  return typeMap[type] || "info";
};

// 格式化时间
const formatDateTime = (timestamp: any): string => {
  if (!timestamp) return "-";
  
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
  
  return timestamp;
};

// 加载列表
const loadList = async () => {
  loading.value = true;
  try {
    const params: any = {
      page: pagination.currentPage,
      limit: pagination.pageSize
    };
    
    if (searchForm.user_id) {
      params.user_id = Number(searchForm.user_id);
    }
    
    if (searchForm.target_type) {
      params.target_type = searchForm.target_type;
    }
    
    const response = await getLikesList(params);
    
    if (response.code === 200 && response.data) {
      tableData.value = response.data.list || [];
      pagination.total = response.data.total || 0;
    } else {
      message(response.msg || "加载失败", { type: "error" });
    }
  } catch (error: any) {
    console.error("加载点赞列表失败:", error);
    message("加载失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

// 搜索
const handleSearch = () => {
  pagination.currentPage = 1;
  loadList();
};

// 重置
const handleReset = () => {
  searchForm.user_id = "";
  searchForm.target_type = "";
  handleSearch();
};

// 分页变化
const handleSizeChange = (size: number) => {
  pagination.pageSize = size;
  loadList();
};

const handleCurrentChange = (page: number) => {
  pagination.currentPage = page;
  loadList();
};

// 查看目标
const handleViewTarget = (row: LikeRecord) => {
  message(`查看 ${getTypeName(row.target_type)} ID: ${row.target_id}`, { type: "info" });
};

// 页面加载
onMounted(() => {
  loadList();
});
</script>

<style lang="scss" scoped>
.likes-container {
  padding: 16px;
  
  .header-title {
    font-weight: 600;
    font-size: 16px;
    line-height: 32px;
  }
  
  .user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 0;
    
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
}
</style>
