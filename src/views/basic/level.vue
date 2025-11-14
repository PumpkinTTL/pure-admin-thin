<template>
  <div class="level-system-container">
    <el-card class="box-card" shadow="never">
      <!-- 页面标题 -->
      <div class="page-header">
        <div class="header-left">
          <el-icon class="header-icon" :size="18"><TrophyBase /></el-icon>
          <span class="header-title">等级系统</span>
          <el-divider direction="vertical" />
          <el-button-group>
            <el-button
              :type="activeTab === 'level' ? 'primary' : ''"
              @click="activeTab = 'level'"
            >
              等级管理
            </el-button>
            <el-button
              :type="activeTab === 'experience' ? 'primary' : ''"
              @click="activeTab = 'experience'"
            >
              经验管理
            </el-button>
          </el-button-group>
        </div>
        <div class="header-right">
          <!-- 搜索区域 -->
          <template v-if="activeTab === 'level'">
            <el-input
              v-model="searchName"
              placeholder="等级名称"
              clearable
              style="width: 150px"
            />
            <el-select
              v-model="searchStatus"
              placeholder="状态"
              clearable
              style="width: 120px"
            >
              <el-option label="启用" :value="1" />
              <el-option label="禁用" :value="0" />
            </el-select>
            <el-button type="primary" @click="handleLevelSearch">
              搜索
            </el-button>
            <el-button @click="handleLevelReset">重置</el-button>
            <el-button type="success" @click="handleOpenAddDialog">
              新增
            </el-button>
          </template>
          <el-select
            v-model="currentLevelType"
            class="type-selector"
            placeholder="请选择类型"
          >
            <el-option label="用户等级" value="user" />
            <el-option label="写作等级" value="writer" />
            <el-option label="读者等级" value="reader" />
            <el-option label="互动等级" value="interaction" />
          </el-select>
        </div>
      </div>

      <el-divider class="section-divider" />

      <LevelManage
        v-if="activeTab === 'level'"
        ref="levelManageRef"
        :levels="levelList"
        :total="levelTotal"
        :loading="levelLoading"
        :level-type="currentLevelType"
        :search-name="searchName"
        :search-status="searchStatus"
        @add="handleAddLevel"
        @update="handleUpdateLevel"
        @delete="handleDeleteLevel"
        @status-change="handleLevelStatusChange"
        @page-change="handleLevelPageChange"
        @batch-delete="handleBatchDelete"
        @batch-status="handleBatchStatus"
      />

      <ExperienceManage
        v-if="activeTab === 'experience'"
        :logs="experienceLogs"
        :total="logsTotal"
        :loading="logsLoading"
        :level-type="currentLevelType"
        @add-experience="handleAddExperience"
        @page-change="handleLogsPageChange"
        @search="handleLogsSearch"
      />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { message } from "@/utils/message";
import { TrophyBase } from "@element-plus/icons-vue";
import LevelManage from "./level/LevelManage.vue";
import ExperienceManage from "./level/ExperienceManage.vue";
import {
  getLevelList,
  createLevel,
  updateLevel,
  deleteLevel,
  setLevelStatus,
  batchDeleteLevels,
  batchUpdateStatus,
  LEVEL_TYPE_MAP,
  type LevelType,
  type Level
} from "@/api/level";
import { addExperience, getExperienceLogs } from "@/api/experience";

defineOptions({
  name: "LevelSystem"
});

const activeTab = ref("level");
const currentLevelType = ref<LevelType>("user");
const levelManageRef = ref();
const levelList = ref<Level[]>([]);
const levelLoading = ref(false);
const levelTotal = ref(0);
const levelPage = ref(1);
const levelPageSize = ref(5);
const experienceLogs = ref([]);
const logsTotal = ref(0);
const logsLoading = ref(false);
const logsPage = ref(1);
const logsPageSize = ref(5);
const logsSearchUserId = ref<number | null>(null);
const searchName = ref("");
const searchStatus = ref<number | undefined>(undefined);

// 页面加载时初始化数据
onMounted(() => {
  initData();
});

// 监听 Tab 切换，重新加载数据
watch(activeTab, newTab => {
  initData();
});

// 监听等级类型切换，重新加载数据
watch(currentLevelType, () => {
  initData();
});

// 初始化数据（根据当前 Tab 加载对应数据）
async function initData() {
  if (activeTab.value === "level") {
    await loadLevelList();
  } else if (activeTab.value === "experience") {
    await loadExperienceLogs();
  }
}

// 加载经验日志
async function loadExperienceLogs() {
  logsLoading.value = true;
  try {
    const params: any = {
      target_type: currentLevelType.value,
      page: logsPage.value,
      page_size: logsPageSize.value
    };

    // 如果有搜索用户ID，添加到参数中
    if (logsSearchUserId.value) {
      params.target_id = logsSearchUserId.value;
    }

    const res = await getExperienceLogs(params);
    if (res.code === 200) {
      experienceLogs.value = res.data.list || [];
      logsTotal.value = res.data.pagination?.total || 0;
    } else {
      message(res.msg || "加载经验日志失败", { type: "error" });
    }
  } catch (error) {
    console.error("加载经验日志失败:", error);
    message("加载经验日志失败", { type: "error" });
  } finally {
    logsLoading.value = false;
  }
}

// 搜索经验日志
function handleLogsSearch(userId: number | null) {
  logsSearchUserId.value = userId;
  logsPage.value = 1;
  loadExperienceLogs();
}

// 处理分页变化
function handleLogsPageChange({
  page,
  pageSize
}: {
  page: number;
  pageSize: number;
}) {
  logsPage.value = page;
  logsPageSize.value = pageSize;
  loadExperienceLogs();
}

// 处理等级分页变化
function handleLevelPageChange({
  page,
  pageSize
}: {
  page: number;
  pageSize: number;
}) {
  levelPage.value = page;
  levelPageSize.value = pageSize;
  loadLevelList();
}

// 加载等级列表
async function loadLevelList() {
  levelLoading.value = true;
  try {
    const res = await getLevelList({
      type: currentLevelType.value,
      page: levelPage.value,
      page_size: levelPageSize.value
    });
    if (res.code === 200) {
      levelList.value = res.data.list || [];
      levelTotal.value = res.data.pagination?.total || 0;
    } else {
      message(res.msg || "加载等级列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("加载等级列表失败:", error);
    message("加载等级列表失败", { type: "error" });
  } finally {
    levelLoading.value = false;
  }
}

// 搜索功能
function handleLevelSearch() {
  levelPage.value = 1;
  loadLevelList();
}

function handleLevelReset() {
  searchName.value = "";
  searchStatus.value = undefined;
  levelPage.value = 1;
  loadLevelList();
}

// ========== 等级管理相关函数 ==========
// 打开新增对话框
function handleOpenAddDialog() {
  levelManageRef.value?.handleAddLevel();
}

async function handleAddLevel(levelData?: any) {
  // 如果没有传入levelData，说明是从按钮点击触发，由子组件处理
  if (!levelData) {
    return;
  }

  try {
    const res = await createLevel({
      ...levelData,
      type: currentLevelType.value
    });
    if (res.code === 200) {
      message("添加等级成功", { type: "success" });
      await loadLevelList();
    } else {
      message(res.msg || "添加等级失败", { type: "error" });
    }
  } catch (error) {
    console.error("添加等级失败:", error);
    message("添加等级失败", { type: "error" });
  }
}

async function handleUpdateLevel(levelData: any) {
  try {
    const res = await updateLevel(levelData);
    if (res.code === 200) {
      message("更新等级成功", { type: "success" });
      await loadLevelList();
    } else {
      message(res.msg || "更新等级失败", { type: "error" });
    }
  } catch (error) {
    console.error("更新等级失败:", error);
    message("更新等级失败", { type: "error" });
  }
}

async function handleDeleteLevel(levelId: number) {
  try {
    const res = await deleteLevel(levelId);
    if (res.code === 200) {
      message("删除等级成功", { type: "success" });
      await loadLevelList();
    } else {
      message(res.msg || "删除等级失败", { type: "error" });
    }
  } catch (error) {
    console.error("删除等级失败:", error);
    message("删除等级失败", { type: "error" });
  }
}

async function handleLevelStatusChange(level: any) {
  try {
    const res = await setLevelStatus(level.id, level.status);
    if (res.code === 200) {
      message("更新等级状态成功", { type: "success" });
      await loadLevelList();
    } else {
      message(res.msg || "更新等级状态失败", { type: "error" });
    }
  } catch (error) {
    console.error("更新等级状态失败:", error);
    message("更新等级状态失败", { type: "error" });
  }
}

async function handleBatchDelete(ids: number[]) {
  try {
    const res = await batchDeleteLevels(ids);
    if (res.code === 200) {
      message(res.msg || "批量删除成功", { type: "success" });
      await loadLevelList();
    } else {
      message(res.msg || "批量删除失败", { type: "error" });
    }
  } catch (error) {
    console.error("批量删除失败:", error);
    message("批量删除失败", { type: "error" });
  }
}

async function handleBatchStatus({
  ids,
  status
}: {
  ids: number[];
  status: number;
}) {
  try {
    const res = await batchUpdateStatus(ids, status);
    if (res.code === 200) {
      message(res.msg || "批量更新状态成功", { type: "success" });
      await loadLevelList();
    } else {
      message(res.msg || "批量更新状态失败", { type: "error" });
    }
  } catch (error) {
    console.error("批量更新状态失败:", error);
    message("批量更新状态失败", { type: "error" });
  }
}

// ========== 经验管理相关函数 ==========
async function handleAddExperience(experienceData: any) {
  try {
    const actionText = experienceData.experience_amount > 0 ? "添加" : "扣除";
    const expAmount = Math.abs(experienceData.experience_amount);
    const res = await addExperience({
      target_type: experienceData.level_type || currentLevelType.value,
      target_id: experienceData.user_id,
      experience_amount: experienceData.experience_amount,
      source_type: experienceData.source_type,
      source_id: experienceData.source_id,
      description: experienceData.description
    });
    if (res.code === 200) {
      // 先刷新数据
      await loadExperienceLogs();

      // 然后显示消息
      const symbol = experienceData.experience_amount > 0 ? "+" : "-";
      const userId = experienceData.user_id;
      message(
        `用户 ${userId} ${actionText}经验成功，共${symbol}${expAmount}经验值`,
        {
          type: "success",
          duration: 3000,
          grouping: false
        }
      );

      // 延迟显示等级变化消息
      if (res.data.is_level_up) {
        setTimeout(() => {
          message(
            `🎉 用户 ${userId} 恭喜升级！从 ${res.data.level_before} 级升到 ${res.data.level_after} 级`,
            { type: "success", duration: 4000, grouping: false }
          );
        }, 500);
      } else if (res.data.is_level_down) {
        setTimeout(() => {
          message(
            `⚠️ 用户 ${userId} 等级下降！从 ${res.data.level_before} 级降到 ${res.data.level_after} 级`,
            { type: "warning", duration: 4000, grouping: false }
          );
        }, 500);
      }
    } else {
      message(res.msg || `${actionText}经验失败`, { type: "error" });
    }
  } catch (error) {
    console.error("操作经验失败:", error);
    message("操作经验失败", { type: "error" });
  }
}
</script>

<style lang="scss" scoped>
.level-system-container {
  .box-card {
    border: none;

    :deep(.el-card__body) {
      padding: 20px;
    }
  }

  .page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;

    .header-left {
      display: flex;
      gap: 12px;
      align-items: center;

      .header-icon {
        color: #409eff;
      }

      .header-title {
        font-size: 16px;
        font-weight: 600;
        color: #303133;
      }

      .el-divider {
        margin: 0 8px;
      }
    }

    .header-right {
      display: flex;
      flex: 1;
      gap: 8px;
      align-items: center;
      justify-content: flex-end;

      .type-selector {
        width: 140px;
      }
    }
  }

  .section-divider {
    margin: 16px 0;
  }
}
</style>
