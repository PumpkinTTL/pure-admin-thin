<template>
  <div class="level-manage-container">
    <!-- 批量操作栏 -->
    <div v-if="selectedItems.length > 0" class="batch-actions">
      <span class="selected-info">已选择 {{ selectedItems.length }} 项</span>
      <el-button type="danger" size="small" @click="handleBatchDelete">
        批量删除
      </el-button>
      <el-button type="warning" size="small" @click="handleBatchDisable">
        批量禁用
      </el-button>
      <el-button type="success" size="small" @click="handleBatchEnable">
        批量启用
      </el-button>
      <el-button size="small" @click="clearSelection">取消选择</el-button>
    </div>

    <el-table
      ref="tableRef"
      v-loading="loading"
      :data="filteredLevels"
      size="default"
      :header-cell-style="{
        background: '#fafafa',
        color: '#606266',
        fontWeight: 'normal'
      }"
      :row-style="{ height: '50px' }"
      style="width: 100%"
      @selection-change="handleSelectionChange"
    >
      <el-table-column type="selection" width="55" />

      <el-table-column prop="id" label="ID" width="80" align="center" />

      <el-table-column prop="type" label="类型" width="120" align="center">
        <template #default="{ row }">
          <el-tag :type="getTypeTagColor(row.type)" effect="plain" size="small">
            {{ getTypeName(row.type) }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="level" label="等级" width="90" align="center">
        <template #default="{ row }">
          <el-tag type="primary">
            {{ row.level }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="name" label="等级名称" width="200" />

      <el-table-column label="经验范围" width="200" align="center">
        <template #default="{ row }">
          <div class="exp-range">
            <el-tag type="info" effect="plain">{{ row.min_experience }}</el-tag>
            <el-icon :size="14"><Right /></el-icon>
            <el-tag type="info" effect="plain">{{ row.max_experience }}</el-tag>
          </div>
        </template>
      </el-table-column>

      <el-table-column
        prop="description"
        label="描述"
        show-overflow-tooltip
        min-width="150"
      />

      <el-table-column
        prop="create_time"
        label="创建时间"
        width="180"
        align="center"
      >
        <template #default="{ row }">
          <el-tag effect="plain" type="info" size="small">
            {{ formatDateTime(row.create_time) }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="status" label="状态" width="90" align="center">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'" effect="plain">
            {{ row.status === 1 ? "启用" : "禁用" }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column fixed="right" label="操作" width="200">
        <template #default="{ row }">
          <el-button link type="primary" @click="handleEditLevel(row)">
            <el-icon><Edit /></el-icon>
            编辑
          </el-button>
          <el-divider direction="vertical" />
          <el-switch
            v-model="row.status"
            :active-value="1"
            :inactive-value="0"
            @change="handleStatusChange(row)"
          />
          <el-divider direction="vertical" />
          <el-button link type="danger" @click="handleDeleteLevel(row)">
            <el-icon><Delete /></el-icon>
            删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-empty
      v-if="filteredLevels.length === 0"
      description="暂无等级数据"
      style="margin-top: 20px"
    />

    <div v-if="filteredLevels.length > 0" class="pagination-container">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[5, 10, 20, 50]"
        :total="total"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handlePageSizeChange"
        @current-change="handleCurrentPageChange"
      />
    </div>

    <!-- 添加/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="editingLevel?.id ? '编辑等级' : '添加等级'"
      width="600px"
      @closed="handleDialogClosed"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
      >
        <el-form-item label="等级类型" prop="type">
          <el-select
            v-model="formData.type"
            placeholder="选择等级类型"
            :disabled="!!editingLevel?.id"
          >
            <el-option label="用户等级" value="user" />
            <el-option label="写作等级" value="writer" />
            <el-option label="读者等级" value="reader" />
            <el-option label="互动等级" value="interaction" />
          </el-select>
          <div v-if="editingLevel?.id" class="form-tips">
            <span>编辑时不能修改类型</span>
          </div>
        </el-form-item>

        <el-form-item label="等级数字" prop="level">
          <el-input-number
            v-model="formData.level"
            :min="1"
            :max="100"
            controls-position="right"
          />
        </el-form-item>

        <el-form-item label="等级名称" prop="name">
          <el-input v-model="formData.name" placeholder="如：青铜 I" />
        </el-form-item>

        <el-form-item label="最小经验" prop="min_experience">
          <el-input-number
            v-model="formData.min_experience"
            :min="0"
            controls-position="right"
          />
        </el-form-item>

        <el-form-item label="最大经验" prop="max_experience">
          <el-input-number
            v-model="formData.max_experience"
            :min="0"
            controls-position="right"
          />
        </el-form-item>

        <el-form-item label="描述" prop="description">
          <el-input v-model="formData.description" type="textarea" rows="3" />
        </el-form-item>

        <el-form-item label="状态" prop="status">
          <el-switch
            v-model="formData.status"
            :active-value="1"
            :inactive-value="0"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          :loading="submitLoading"
          @click="handleSubmit"
        >
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Right, Edit, Delete } from "@element-plus/icons-vue";
import type { FormInstance } from "element-plus";

interface LevelInfo {
  id?: number;
  type?: string;
  level: number;
  name: string;
  min_experience?: number;
  max_experience?: number;
  description?: string;
  status?: number;
}

interface Props {
  levels?: LevelInfo[];
  total?: number;
  loading?: boolean;
  searchName?: string;
  searchStatus?: number;
}

const props = withDefaults(defineProps<Props>(), {
  levels: () => [],
  total: 0,
  loading: false,
  searchName: "",
  searchStatus: undefined
});

const emit = defineEmits([
  "add",
  "update",
  "delete",
  "status-change",
  "page-change",
  "batch-delete",
  "batch-status"
]);

const currentPage = ref(1);
const pageSize = ref(5);
const selectedItems = ref<LevelInfo[]>([]);
const tableRef = ref();

const formRef = ref<FormInstance>();
const dialogVisible = ref(false);
const submitLoading = ref(false);
const editingLevel = ref<LevelInfo | null>(null);

const formData = ref<Partial<LevelInfo>>({
  type: "user",
  level: 1,
  name: "",
  min_experience: 0,
  max_experience: 100,
  description: "",
  status: 1
});

const formRules = {
  type: [{ required: true, message: "请选择等级类型" }],
  level: [{ required: true, message: "请输入等级数字" }],
  name: [{ required: true, message: "请输入等级名称" }],
  min_experience: [{ required: true, message: "请输入最小经验值" }],
  max_experience: [{ required: true, message: "请输入最大经验值" }]
};

const filteredLevels = computed(() => {
  return (props.levels || []).filter(level => {
    let match = true;
    if (props.searchName) {
      match = match && (level.name?.includes(props.searchName) || false);
    }
    if (props.searchStatus !== undefined) {
      match = match && level.status === props.searchStatus;
    }
    return match;
  });
});

function handleAddLevel() {
  editingLevel.value = null;
  formData.value = {
    type: "user",
    level: 1,
    name: "",
    min_experience: 0,
    max_experience: 100,
    description: "",
    status: 1
  };
  dialogVisible.value = true;
}

function handleEditLevel(level: LevelInfo) {
  editingLevel.value = level;
  formData.value = { ...level };
  dialogVisible.value = true;
}

function handleDeleteLevel(level: LevelInfo) {
  ElMessageBox.confirm(`确定删除等级"${level.name}"吗?`, "确认", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "warning"
  })
    .then(() => {
      emit("delete", level.id);
    })
    .catch(() => {
      // 取消删除
    });
}

function handleStatusChange(level: LevelInfo) {
  emit("status-change", level);
}

function handleSubmit() {
  formRef.value?.validate(async valid => {
    if (valid) {
      submitLoading.value = true;
      try {
        if (editingLevel.value?.id) {
          // 编辑时只传递必要字段，过滤掉只读字段
          const updateData = {
            id: editingLevel.value.id,
            name: formData.value.name,
            min_experience: formData.value.min_experience,
            max_experience: formData.value.max_experience,
            description: formData.value.description,
            status: formData.value.status
          };
          emit("update", updateData);
        } else {
          emit("add", formData.value);
        }
        dialogVisible.value = false;
      } catch (error) {
        // 错误由父组件处理
      } finally {
        submitLoading.value = false;
      }
    }
  });
}

function handleDialogClosed() {
  formRef.value?.clearValidate();
  editingLevel.value = null;
}

function handlePageSizeChange(size: number) {
  pageSize.value = size;
  emit("page-change", { page: currentPage.value, pageSize: size });
}

function handleCurrentPageChange(page: number) {
  currentPage.value = page;
  emit("page-change", { page, pageSize: pageSize.value });
}

// 选中项变化
function handleSelectionChange(selection: LevelInfo[]) {
  selectedItems.value = selection;
}

// 清空选择
function clearSelection() {
  selectedItems.value = [];
  tableRef.value?.clearSelection();
}

// 批量删除
function handleBatchDelete() {
  if (selectedItems.value.length === 0) {
    ElMessage.warning("请选择要删除的等级");
    return;
  }

  ElMessageBox.confirm(
    `确定要删除选中的 ${selectedItems.value.length} 个等级吗？`,
    "批量删除",
    {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }
  )
    .then(() => {
      const ids = selectedItems.value
        .map(item => item.id)
        .filter(id => id !== undefined) as number[];
      emit("batch-delete", ids);
      clearSelection();
    })
    .catch(() => {
      // 取消删除
    });
}

// 批量禁用
function handleBatchDisable() {
  if (selectedItems.value.length === 0) {
    ElMessage.warning("请选择要禁用的等级");
    return;
  }

  const ids = selectedItems.value
    .map(item => item.id)
    .filter(id => id !== undefined) as number[];
  emit("batch-status", { ids, status: 0 });
  clearSelection();
}

// 批量启用
function handleBatchEnable() {
  if (selectedItems.value.length === 0) {
    ElMessage.warning("请选择要启用的等级");
    return;
  }

  const ids = selectedItems.value
    .map(item => item.id)
    .filter(id => id !== undefined) as number[];
  emit("batch-status", { ids, status: 1 });
  clearSelection();
}

// 格式化时间
function formatDateTime(dateTime: string) {
  if (!dateTime) return "-";
  const date = new Date(dateTime);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  const seconds = String(date.getSeconds()).padStart(2, "0");
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// 获取类型名称
function getTypeName(type: string) {
  const typeMap: Record<string, string> = {
    user: "用户等级",
    writer: "写作等级",
    reader: "读者等级",
    interaction: "互动等级"
  };
  return typeMap[type] || type;
}

// 获取类型标签颜色
function getTypeTagColor(type: string) {
  const colorMap: Record<string, string> = {
    user: "primary",
    writer: "success",
    reader: "warning",
    interaction: "danger"
  };
  return colorMap[type] || "info";
}

// 暴露方法给父组件调用
defineExpose({
  handleAddLevel
});
</script>

<style lang="scss" scoped>
.level-manage-container {
  .batch-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 12px 16px;
    margin-bottom: 16px;
    background: #f5f7fa;
    border-radius: 4px;

    .selected-info {
      font-size: 14px;
      font-weight: 500;
      color: #606266;
    }
  }

  :deep(.el-table) {
    font-size: 13px;
  }

  .exp-range {
    display: flex;
    gap: 8px;
    align-items: center;
    justify-content: center;
  }

  .pagination-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
  }

  .form-tips {
    margin-top: 4px;
    font-size: 12px;
    color: #909399;
  }
}
</style>
