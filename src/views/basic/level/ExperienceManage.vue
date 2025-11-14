<template>
  <div class="experience-manage-container">
    <!-- 搜索区域 -->
    <div class="search-area">
      <el-input-number
        v-model="searchUserId"
        :min="1"
        placeholder="搜索用户ID"
        controls-position="right"
        clearable
        style="width: 180px"
      />
      <el-button type="primary" @click="handleSearch">搜索</el-button>
      <el-button @click="handleResetSearch">重置</el-button>
    </div>

    <!-- 操作区域 -->
    <div class="operation-area">
      <div class="operation-content">
        <div class="operation-left">
          <el-select
            v-model="selectedLevelType"
            placeholder="等级类型"
            style="width: 140px"
          >
            <el-option label="用户等级" value="user" />
            <el-option label="写作等级" value="writer" />
            <el-option label="读者等级" value="reader" />
            <el-option label="互动等级" value="interaction" />
          </el-select>
          <el-input-number
            v-model="userId"
            :min="1"
            placeholder="用户ID"
            controls-position="right"
          />
          <el-input-number
            v-model="experienceAmount"
            :min="-1000"
            :max="1000"
            placeholder="经验值（负数为扣除）"
            controls-position="right"
            :value-on-clear="undefined"
          />
          <el-select v-model="sourceType" placeholder="来源">
            <el-option label="手动" value="manual" />
            <el-option label="系统" value="system" />
            <el-option label="活动" value="activity" />
            <el-option label="签到" value="sign" />
            <el-option label="分享" value="share" />
          </el-select>
          <el-input
            v-model="description"
            placeholder="请输入描述"
            clearable
            class="description-input"
          />
        </div>
        <el-button
          type="primary"
          :icon="CirclePlus"
          :disabled="!userId || !experienceAmount || experienceAmount === 0"
          @click="handleAddExperience"
        >
          {{
            experienceAmount && experienceAmount > 0 ? "添加经验" : "扣除经验"
          }}
        </el-button>
      </div>
    </div>

    <!-- 表格 -->
    <el-table
      v-loading="loading"
      :data="logs"
      :header-cell-style="{
        background: '#fafafa',
        color: '#606266',
        fontWeight: 'normal'
      }"
      style="width: 100%"
    >
      <el-table-column label="用户" min-width="140">
        <template #default="{ row }">
          <div class="user-info">
            <el-avatar :size="28" :src="row.avatar">
              {{ row.nickname?.charAt(0) || row.username?.charAt(0) || "U" }}
            </el-avatar>
            <div class="user-text">
              <div class="nickname">{{ row.nickname || row.username }}</div>
              <div class="user-id">ID: {{ row.target_id }}</div>
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="经验" min-width="90" align="center">
        <template #default="{ row }">
          <el-tag :type="row.experience_amount > 0 ? 'success' : 'danger'">
            {{ row.experience_amount > 0 ? "+" : ""
            }}{{ row.experience_amount }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="来源" min-width="90" align="center">
        <template #default="{ row }">
          <el-tag :type="getSourceTypeColor(row.source_type)">
            {{ getSourceTypeLabel(row.source_type) }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column
        show-overflow-tooltip
        label="描述"
        prop="description"
        min-width="180"
      />

      <el-table-column label="等级变化" min-width="150" align="center">
        <template #default="{ row }">
          <div class="level-change">
            <el-tag type="info" effect="plain">{{ row.level_before }}</el-tag>
            <el-icon :size="14"><Right /></el-icon>
            <el-tag
              :type="
                row.level_after > row.level_before
                  ? 'success'
                  : row.level_after < row.level_before
                    ? 'danger'
                    : 'info'
              "
              effect="plain"
            >
              {{ row.level_after }}
            </el-tag>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="状态" min-width="80" align="center">
        <template #default="{ row }">
          <el-tag v-if="row.is_level_up" type="success">升级</el-tag>
          <el-tag v-else-if="row.is_level_down" type="danger">降级</el-tag>
          <span v-else>-</span>
        </template>
      </el-table-column>

      <el-table-column
        show-overflow-tooltip
        label="创建时间"
        prop="create_time"
        min-width="160"
        align="center"
      />
    </el-table>

    <!-- 分页 -->
    <div class="pagination-container">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[5, 10, 20, 50]"
        :background="true"
        layout="total, sizes, prev, pager, next, jumper"
        :total="total"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { CirclePlus, Right, Promotion } from "@element-plus/icons-vue";

interface ExperienceLog {
  id: number;
  target_id: number;
  username: string;
  nickname: string;
  avatar: string;
  experience_amount: number;
  source_type: string;
  description: string;
  level_before: number;
  level_after: number;
  is_level_up: number;
  is_level_down: number;
  create_time: string;
}

interface Props {
  logs?: ExperienceLog[];
  loading?: boolean;
  total?: number;
}

const props = withDefaults(defineProps<Props>(), {
  logs: () => [],
  loading: false,
  total: 0
});

const emit = defineEmits(["add-experience", "page-change", "search"]);

const selectedLevelType = ref<string>("user");
const userId = ref<number | null>(null);
const experienceAmount = ref<number | undefined>(undefined);
const sourceType = ref<string>("manual");
const description = ref<string>("");
const currentPage = ref(1);
const pageSize = ref(5);
const searchUserId = ref<number | null>(null);

const total = computed(() => props.total);

function handleAddExperience() {
  if (
    !userId.value ||
    !experienceAmount.value ||
    experienceAmount.value === 0
  ) {
    ElMessage.warning("请输入用户ID并输入经验值");
    return;
  }

  const actionText = experienceAmount.value > 0 ? "添加" : "扣除";
  const expText = Math.abs(experienceAmount.value);

  ElMessageBox.confirm(
    `确认给用户 ${userId.value} ${actionText} ${expText} 经验值？`,
    "确认操作",
    {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }
  )
    .then(() => {
      const data = {
        level_type: selectedLevelType.value,
        user_id: userId.value,
        experience_amount: experienceAmount.value,
        source_type: sourceType.value,
        description: description.value || `管理员手动${actionText}经验`
      };

      emit("add-experience", data);
      handleResetForm();
    })
    .catch(() => {
      // 取消操作
    });
}

function handleSearch() {
  emit("search", searchUserId.value);
}

function handleResetSearch() {
  searchUserId.value = null;
  emit("search", null);
}

function handleResetForm() {
  userId.value = null;
  experienceAmount.value = undefined;
  selectedLevelType.value = "user";
  sourceType.value = "manual";
  description.value = "";
}

function handleSizeChange(size: number) {
  pageSize.value = size;
  emit("page-change", { page: currentPage.value, pageSize: size });
}

function handleCurrentChange(page: number) {
  currentPage.value = page;
  emit("page-change", { page, pageSize: pageSize.value });
}

function getSourceTypeLabel(type: string): string {
  const map: Record<string, string> = {
    manual: "手动",
    system: "系统",
    activity: "活动",
    sign: "签到",
    share: "分享",
    article_publish: "发文",
    article_read: "阅读",
    comment_publish: "评论",
    comment_like: "点赞"
  };
  return map[type] || type;
}

function getSourceTypeColor(type: string): string {
  const colorMap: Record<string, string> = {
    manual: "",
    system: "success",
    activity: "warning",
    sign: "primary",
    share: "primary",
    article_publish: "success",
    article_read: "info",
    comment_publish: "primary",
    comment_like: "danger"
  };
  return colorMap[type] || "";
}
</script>

<style lang="scss" scoped>
.experience-manage-container {
  .search-area {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 16px;
    margin-bottom: 16px;
    background: #f5f7fa;
    border-radius: 4px;
  }

  .operation-area {
    padding: 16px;
    margin-bottom: 16px;
    background: #fff;
  }

  .operation-content {
    display: flex;
    gap: 16px;
    align-items: center;
    justify-content: space-between;
  }

  .operation-left {
    display: flex;
    flex: 1;
    gap: 12px;
    align-items: center;

    .el-input-number {
      width: 150px;
    }

    .el-select {
      width: 130px;
    }

    .description-input {
      flex: 1;
      min-width: 220px;
    }
  }

  .user-info {
    display: flex;
    gap: 8px;
    align-items: center;

    .user-text {
      flex: 1;
      line-height: 1.4;
      text-align: left;

      .nickname {
        font-size: 13px;
        font-weight: 500;
        color: #303133;
      }

      .user-id {
        font-size: 11px;
        color: #909399;
      }
    }
  }

  .level-change {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
  }

  .pagination-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
  }

  .el-icon {
    font-size: 12px;
    color: #909399;
  }
}
</style>
