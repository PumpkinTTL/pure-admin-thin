<template>
  <div class="favorites-container">
    <el-card>
      <template #header>
        <el-row :gutter="10">
          <el-col :xs="24" :sm="24" :md="4" :lg="3" :xl="2">
            <span class="header-title">收藏管理</span>
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-input v-model="searchForm.user_id" placeholder="用户ID" clearable size="default" />
          </el-col>
          <el-col :xs="24" :sm="12" :md="4" :lg="3" :xl="3">
            <el-select v-model="searchForm.target_type" placeholder="类型" clearable style="width: 100%" size="default">
              <el-option label="全部" value="" />
              <el-option label="文章" value="article" />
              <el-option label="商品" value="product" />
              <el-option label="评论" value="comment" />
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
          <el-col :xs="24" :sm="24" :md="10" :lg="8" :xl="8">
            <div class="test-buttons">
              <el-button type="success" size="small" @click="showTestFavoriteDialog">
                测试收藏/取消
              </el-button>
              <el-button type="danger" size="small" @click="batchDelete" :disabled="selectedRows.length === 0">
                批量删除({{selectedRows.length}})
              </el-button>
            </div>
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
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />
        <el-table-column label="ID" prop="id" width="60" />
        
        <el-table-column label="用户" min-width="160">
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

        <el-table-column label="类型" width="80">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.target_type)" size="small">
              {{ getTypeName(row.target_type) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="目标ID" prop="target_id" width="80" />

        <el-table-column label="收藏时间" min-width="150">
          <template #default="{ row }">
            {{ formatDateTime(row.create_time) }}
          </template>
        </el-table-column>

        <el-table-column label="更新时间" min-width="150">
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
            <el-button
              link
              type="danger"
              size="small"
              @click="handleDelete(row)"
            >
              删除
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

    <!-- 测试收藏对话框 -->
    <el-dialog v-model="testFavoriteDialog.visible" title="测试收藏/取消" width="400px">
      <el-form :model="testFavoriteDialog.form" label-width="80px">
        <el-form-item label="类型">
          <el-select v-model="testFavoriteDialog.form.target_type" style="width: 100%">
            <el-option label="文章" value="article" />
            <el-option label="商品" value="product" />
            <el-option label="评论" value="comment" />
          </el-select>
        </el-form-item>
        <el-form-item label="目标ID">
          <el-input-number v-model="testFavoriteDialog.form.target_id" :min="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="用户ID">
          <el-input-number v-model="testFavoriteDialog.form.user_id" :min="1" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="testFavoriteDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="confirmTestFavorite">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { message } from "@/utils/message";
import { Search, RefreshLeft } from "@element-plus/icons-vue";
import { getFavoritesList, toggleFavorite, type FavoriteRecord } from "@/api/favorites";

defineOptions({
  name: "Favorites"
});

// 默认头像
const defaultAvatar = "https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png";

// 搜索表单
const searchForm = reactive({
  user_id: "",
  target_type: ""
});

// 表格数据
const tableData = ref<FavoriteRecord[]>([]);
const loading = ref(false);
const selectedRows = ref<FavoriteRecord[]>([]);

// 测试收藏对话框
const testFavoriteDialog = reactive({
  visible: false,
  form: {
    target_type: 'article',
    target_id: 11355,
    user_id: 1001
  }
});

// 分页配置
const pagination = reactive({
  currentPage: 1,
  pageSize: 10,
  total: 0
});

// 获取类型名称
const getTypeName = (type: string): string => {
  const typeMap: Record<string, string> = {
    article: "文章",
    product: "商品",
    comment: "评论"
  };
  return typeMap[type] || type;
};

// 获取类型标签类型
const getTypeTagType = (type: string): "success" | "warning" | "primary" | "info" | "danger" => {
  const typeMap: Record<string, "success" | "warning" | "primary" | "info" | "danger"> = {
    article: "success",
    product: "warning",
    comment: "primary"
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
    
    const response = await getFavoritesList(params);
    
    if (response.code === 200 && response.data) {
      tableData.value = response.data.list || [];
      pagination.total = response.data.total || 0;
    } else {
      message(response.msg || "加载失败", { type: "error" });
    }
  } catch (error: any) {
    console.error("加载收藏列表失败:", error);
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
const handleViewTarget = (row: FavoriteRecord) => {
  message(`查看 ${getTypeName(row.target_type)} ID: ${row.target_id}`, { type: "info" });
};

// 选择变化
const handleSelectionChange = (selection: FavoriteRecord[]) => {
  selectedRows.value = selection;
};

// 单个删除
const handleDelete = async (row: FavoriteRecord) => {
  try {
    const response = await toggleFavorite({
      target_type: row.target_type,
      target_id: row.target_id,
      user_id: row.user_id
    });
    if (response.code === 200) {
      message('删除成功', { type: "success" });
      loadList();
    } else {
      message(response.msg || '删除失败', { type: "error" });
    }
  } catch (error: any) {
    message('删除失败', { type: "error" });
  }
};

// 批量删除
const batchDelete = async () => {
  if (selectedRows.value.length === 0) {
    message('请选择要删除的数据', { type: "warning" });
    return;
  }
  
  try {
    let successCount = 0;
    for (const row of selectedRows.value) {
      const response = await toggleFavorite({
        target_type: row.target_type,
        target_id: row.target_id,
        user_id: row.user_id
      });
      if (response.code === 200) {
        successCount++;
      }
    }
    
    message(`成功删除 ${successCount} 条记录`, { type: "success" });
    selectedRows.value = [];
    loadList();
  } catch (error: any) {
    message('批量删除失败', { type: "error" });
  }
};

// 显示测试收藏对话框
const showTestFavoriteDialog = () => {
  testFavoriteDialog.visible = true;
};

// 确认测试收藏
const confirmTestFavorite = async () => {
  try {
    const response = await toggleFavorite({
      target_type: testFavoriteDialog.form.target_type,
      target_id: testFavoriteDialog.form.target_id,
      user_id: testFavoriteDialog.form.user_id
    });
    if (response.code === 200) {
      message(response.msg, { type: "success" });
      testFavoriteDialog.visible = false;
      loadList(); // 刷新列表
    } else {
      message(response.msg || "操作失败", { type: "error" });
    }
  } catch (error: any) {
    message("测试收藏失败", { type: "error" });
  }
};

// 页面加载
onMounted(() => {
  loadList();
});
</script>

<style lang="scss" scoped>
.favorites-container {
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
  
  .test-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
    
    .el-button {
      margin-left: 0;
    }
  }
}
</style>
