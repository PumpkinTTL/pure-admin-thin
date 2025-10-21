<template>
  <div class="files-container">
    <!-- 主内容区 -->
    <el-card shadow="never" class="content-card">
      <!-- 工具栏 -->
      <div class="toolbar">
        <div class="toolbar__actions">
          <!-- 上传组件 -->
          <FileUpload
            ref="fileUploadRef"
            :device-fingerprint="deviceFingerprint"
            @success="handleUploadSuccess"
          />

          <el-button
            type="danger"
            :disabled="!selectedFiles.length"
            size="small"
            class="toolbar-btn"
            @click="handleBatchDelete"
          >
            <i class="fa fa-trash-alt mr-1"></i>
            批量删除
            <span v-if="selectedFiles.length">({{ selectedFiles.length }})</span>
          </el-button>

          <el-dropdown @command="handleStatusCommand" trigger="click">
            <el-button size="small" class="toolbar-btn status-btn">
              <i class="fa fa-filter mr-1"></i>
              {{ searchParams.status === "active" ? "活跃文件" : "已删除文件" }}
              <i class="fa fa-chevron-down ml-1"></i>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item
                  command="active"
                  :class="{ 'is-active': searchParams.status === 'active' }"
                >
                  <i class="fa fa-file mr-2 text-blue-500"></i>
                  <span>活跃文件</span>
                  <i
                    v-if="searchParams.status === 'active'"
                    class="fa fa-check ml-auto text-blue-500"
                  ></i>
                </el-dropdown-item>
                <el-dropdown-item
                  command="deleted"
                  :class="{ 'is-active': searchParams.status === 'deleted' }"
                >
                  <i class="fa fa-trash mr-2 text-red-500"></i>
                  <span>已删除文件</span>
                  <i
                    v-if="searchParams.status === 'deleted'"
                    class="fa fa-check ml-auto text-red-500"
                  ></i>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>

        <div class="toolbar__search">
          <el-input
            v-model="searchParams.original_name"
            placeholder="搜索文件名"
            clearable
            size="small"
            @keyup.enter="handleSearch"
            @clear="handleSearch"
          >
            <template #prefix>
              <el-icon>
                <Search />
              </el-icon>
            </template>
          </el-input>

          <el-button type="primary" size="small" @click="handleSearch" class="search-btn">
            <i class="fa fa-search mr-1"></i>
            搜索
          </el-button>

          <el-button
            size="small"
            @click="showAdvancedSearch = !showAdvancedSearch"
            class="search-btn"
          >
            <i class="fa fa-cog mr-1"></i>
            {{ showAdvancedSearch ? "收起" : "高级" }}
          </el-button>

          <el-button type="info" size="small" @click="fetchFileList" class="search-btn">
            <i class="fa fa-sync-alt mr-1"></i>
            刷新
          </el-button>
        </div>
      </div>

      <!-- 高级搜索 -->
      <el-collapse-transition>
        <div v-show="showAdvancedSearch" class="advanced-search">
          <el-form :model="searchParams" size="small" label-width="80px">
            <div class="form-row">
              <el-form-item label="文件类型">
                <el-select
                  v-model="searchParams.file_type"
                  placeholder="选择文件类型"
                  clearable
                  style="width: 100%"
                >
                  <el-option label="图片" value="image" />
                  <el-option label="文档" value="document" />
                  <el-option label="视频" value="video" />
                  <el-option label="音频" value="audio" />
                  <el-option label="压缩包" value="archive" />
                  <el-option label="其他" value="other" />
                </el-select>
              </el-form-item>
              <el-form-item label="扩展名">
                <el-input
                  v-model="searchParams.file_extension"
                  placeholder="如: jpg, pdf"
                  clearable
                  style="width: 100%"
                />
              </el-form-item>
              <el-form-item label="存储类型">
                <el-select
                  v-model="searchParams.storage_type"
                  placeholder="存储位置"
                  clearable
                  style="width: 100%"
                >
                  <el-option
                    v-for="(label, value) in storageTypeTextMap"
                    :key="value"
                    :label="label"
                    :value="Number(value)"
                  />
                </el-select>
              </el-form-item>
            </div>
            <div class="form-row">
              <el-form-item label="上传时间">
                <el-date-picker
                  v-model="dateRange"
                  type="daterange"
                  range-separator="至"
                  start-placeholder="开始日期"
                  end-placeholder="结束日期"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                />
              </el-form-item>
              <el-form-item label="文件大小">
                <div class="size-range">
                  <el-input-number
                    v-model="searchParams.min_size"
                    :min="0"
                    :step="1024"
                    placeholder="最小"
                    controls-position="right"
                  />
                  <span class="separator">至</span>
                  <el-input-number
                    v-model="searchParams.max_size"
                    :min="0"
                    :step="1024"
                    placeholder="最大"
                    controls-position="right"
                  />
                </div>
              </el-form-item>
            </div>
            <div class="form-actions">
              <el-button size="small" @click="resetSearch" class="form-btn">
                <i class="fa fa-undo mr-1"></i>
                重置
              </el-button>
              <el-button type="primary" size="small" @click="handleSearch" class="form-btn">
                <i class="fa fa-search mr-1"></i>
                搜索
              </el-button>
            </div>
          </el-form>
        </div>
      </el-collapse-transition>

      <!-- 文件表格组件 -->
      <FileTable
        ref="fileTableRef"
        :file-list="fileList"
        :total="totalCount"
        v-model:page="searchParams.page"
        v-model:page-size="searchParams.page_size"
        :loading="tableLoading"
        :status="searchParams.status"
        :table-key="tableKey"
        @selection-change="handleSelectionChange"
        @preview="handlePreview"
        @download="handleDownload"
        @detail="handleDetail"
        @delete="handleDelete"
        @restore="handleRestoreFile"
        @force-delete="handleForceDeleteFile"
      />
    </el-card>

    <!-- 文件编辑对话框 -->
    <FileDetail
      v-model="detailDialogVisible"
      :file="currentFile"
      @preview="handlePreview"
      @updated="handleFileUpdated"
    />

    <!-- 文件预览对话框 -->
    <FilePreview v-model="previewDialogVisible" :file="previewFile" />

    <!-- 删除确认对话框 -->
    <el-dialog
      v-model="deleteConfirmVisible"
      title="删除确认"
      width="400px"
      :close-on-click-modal="false"
      append-to-body
    >
      <div v-if="currentDeleteFile">
        <p>确定要删除文件 "{{ currentDeleteFile.original_name }}" 吗？</p>
        <div style="margin-top: 15px">
          <el-checkbox v-model="isPermanentDelete"
            >永久删除，数据将无法恢复</el-checkbox
          >
        </div>
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button
            @click="deleteConfirmVisible = false"
            size="small"
            class="dialog-btn"
          >
            <i class="fa fa-times mr-1"></i>
            取消
          </el-button>
          <el-button
            type="danger"
            @click="confirmDelete"
            :loading="deleteLoading"
            size="small"
            class="dialog-btn"
          >
            <i class="fa fa-trash-alt mr-1"></i>
            确定删除
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 批量删除确认对话框 -->
    <el-dialog
      v-model="batchDeleteDialogVisible"
      title="批量删除确认"
      width="500px"
      :close-on-click-modal="false"
      append-to-body
    >
      <div>
        <p>
          确定要删除选中的
          <strong>{{ selectedFiles.length }}</strong> 个文件吗？
        </p>

        <!-- 正常文件的删除选项 -->
        <div v-if="currentStatus === 'active'" style="margin-top: 20px">
          <div style="margin-bottom: 15px">
            <el-checkbox v-model="batchDeleteForce">
              <span style="color: #f56c6c; font-weight: 500"
                >永久删除（删除数据库记录和物理文件，不可恢复）</span
              >
            </el-checkbox>
          </div>

          <el-alert
            v-if="!batchDeleteForce"
            title="软删除：仅标记删除，可通过恢复功能找回文件"
            type="info"
            :closable="false"
            show-icon
            style="margin-top: 10px"
          />
          <el-alert
            v-else
            title="永久删除：删除数据库记录和物理文件，完全不可恢复！"
            type="error"
            :closable="false"
            show-icon
            style="margin-top: 10px"
          />
        </div>

        <!-- 已删除文件的提示 -->
        <div v-else-if="currentStatus === 'deleted'" style="margin-top: 15px">
          <el-alert
            title="注意：已删除的文件将被永久删除，数据无法恢复！"
            type="error"
            :closable="false"
            show-icon
          />
        </div>
      </div>

      <template #footer>
        <span class="dialog-footer">
          <el-button
            @click="batchDeleteDialogVisible = false"
            size="small"
            class="dialog-btn"
          >
            <i class="fa fa-times mr-1"></i>
            取消
          </el-button>
          <el-button type="danger" @click="confirmBatchDelete" size="small" class="dialog-btn">
            <i class="fa fa-trash-alt mr-1"></i>
            {{ getDeleteButtonText() }}
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "SystemFiles"
});

import { ref, reactive, computed, onMounted, watch, nextTick } from "vue";
import { ElMessageBox } from "element-plus";
import { Search } from "@element-plus/icons-vue";
import {
  getFileList,
  getFileDetail,
  type FileInfo
} from "@/api/fileManage";
import { storageTypeTextMap } from "@/api/utils";
import { message } from "@/utils/message";
import { getFingerprint } from "@/utils/fingerprint";

// 导入组件
import FileUpload from "./components/FileUpload.vue";
import FileTable from "./components/FileTable.vue";
import FileDetail from "./components/FileDetail.vue";
import FilePreview from "./components/FilePreview.vue";

// 导入hooks
import { useFileOperations } from "./composables/useFileOperations";

// Hooks
const {
  deleteLoading,
  handleSoftDelete,
  handleRestore,
  handleForceDelete,
  handleBatchDelete: batchDeleteFiles
} = useFileOperations();

// 状态变量
const tableLoading = ref(false);
const detailDialogVisible = ref(false);
const previewDialogVisible = ref(false);
const showAdvancedSearch = ref(false);
const deleteConfirmVisible = ref(false);
const batchDeleteDialogVisible = ref(false);

const fileUploadRef = ref();
const fileTableRef = ref();

const selectedFiles = ref<number[]>([]);
const fileList = ref<FileInfo[]>([]);
const totalCount = ref(0);
const tableKey = ref(0);

const currentFile = ref<FileInfo>({} as FileInfo);
const previewFile = ref<FileInfo>({} as FileInfo);
const currentDeleteFile = ref<FileInfo | null>(null);
const isPermanentDelete = ref(false);
const batchDeleteForce = ref(false);
const deviceFingerprint = ref("");

// 当前文件状态
const currentStatus = computed(() => searchParams.status);

// 搜索参数
const dateRange = ref<[string, string] | null>(null);
const searchParams = reactive({
  page: 1,
  page_size: 5,
  original_name: "",
  file_type: "",
  file_extension: "",
  storage_type: undefined,
  min_size: undefined,
  max_size: undefined,
  start_date: "",
  end_date: "",
  status: "active" as "active" | "deleted",
  sort_field: "create_time",
  sort_order: "desc" as "asc" | "desc"
});

// 初始化设备指纹
const initDeviceFingerprint = async () => {
  try {
    const fingerprint = await getFingerprint();
    deviceFingerprint.value = fingerprint;
  } catch (error) {
    console.error("获取设备指纹失败:", error);
  }
};

// 监听日期范围变化
watch(dateRange, val => {
  if (val) {
    searchParams.start_date = val[0];
    searchParams.end_date = val[1];
  } else {
    searchParams.start_date = "";
    searchParams.end_date = "";
  }
});

// 监听状态变化
watch(
  () => searchParams.status,
  (newStatus, oldStatus) => {
    if (newStatus !== oldStatus) {
      tableKey.value++;
    }
  },
  { immediate: true }
);

// 获取文件列表
const fetchFileList = async () => {
  tableLoading.value = true;
  try {
    const res: any = await getFileList(searchParams);
    if (res?.code === 200 && res?.data) {
      fileList.value = res.data.data || [];
      totalCount.value = res.data.total || 0;
      tableKey.value++;
    } else {
      message(res?.msg || "获取文件列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取文件列表失败:", error);
    message("获取文件列表失败,请稍后重试", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 监听分页变化,自动刷新
watch(() => [searchParams.page, searchParams.page_size], fetchFileList);

// 搜索
const handleSearch = () => {
  searchParams.page = 1;
  clearTableSelection();
  fetchFileList();
};

// 重置搜索
const resetSearch = () => {
  searchParams.original_name = "";
  searchParams.file_type = "";
  searchParams.file_extension = "";
  searchParams.storage_type = undefined;
  searchParams.min_size = undefined;
  searchParams.max_size = undefined;
  searchParams.start_date = "";
  searchParams.end_date = "";
  dateRange.value = null;
  clearTableSelection();
  handleSearch();
};

// 状态过滤
const handleStatusCommand = async (command: string) => {
  clearTableSelection();
  searchParams.page = 1;
  searchParams.status = command as "active" | "deleted";
  tableKey.value++;
  await nextTick();
  fetchFileList();
};

// 选择变化
const handleSelectionChange = (selection: FileInfo[]) => {
  selectedFiles.value = selection.map(item => item.file_id);
};

// 清除表格选择
const clearTableSelection = () => {
  if (fileTableRef.value) {
    fileTableRef.value.clearSelection();
  }
  selectedFiles.value = [];
};

// 上传成功
const handleUploadSuccess = () => {
  clearTableSelection();
  fetchFileList();
};

// 预览文件
const handlePreview = (file: FileInfo) => {
  previewFile.value = file;
  previewDialogVisible.value = true;
};

// 下载文件
const handleDownload = (file: FileInfo) => {
  if (file.http_url) {
    const a = document.createElement("a");
    a.href = file.http_url;
    a.download = file.original_name;
    a.click();
  } else {
    message("文件无法下载，URL不存在", { type: "warning" });
  }
};

// 查看文件详情/编辑
const handleDetail = async (file: FileInfo) => {
  try {
    const res: any = await getFileDetail(file.file_id);
    if (res?.code === 200) {
      currentFile.value = res.data;
      detailDialogVisible.value = true;
    } else {
      message(res?.message || "获取文件详情失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取文件详情失败:", error);
    message("获取文件详情失败，请稍后重试", { type: "error" });
  }
};

// 文件更新后刷新列表
const handleFileUpdated = () => {
  fetchFileList();
};

// 删除文件
const handleDelete = (file: FileInfo) => {
  currentDeleteFile.value = file;
  isPermanentDelete.value = false;
  deleteConfirmVisible.value = true;
};

// 确认删除
const confirmDelete = async () => {
  if (!currentDeleteFile.value) return;

  const success = isPermanentDelete.value
    ? await handleForceDelete(currentDeleteFile.value.file_id)
    : await handleSoftDelete(currentDeleteFile.value.file_id);

  if (success) {
    clearTableSelection();
    fetchFileList();
    deleteConfirmVisible.value = false;
  }
};

// 恢复文件
const handleRestoreFile = async (file: FileInfo) => {
  const success = await handleRestore(file.file_id);
  if (success) {
    clearTableSelection();
    fetchFileList();
  }
};

// 彻底删除文件
const handleForceDeleteFile = (file: FileInfo) => {
  ElMessageBox.confirm(
    `确定要永久删除文件 "${file.original_name}" 吗？此操作无法撤销！`,
    "永久删除确认",
    {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "error"
    }
  )
    .then(async () => {
      const success = await handleForceDelete(file.file_id);
      if (success) {
        clearTableSelection();
        fetchFileList();
      }
    })
    .catch(() => {
      // 用户取消操作
    });
};

// 批量删除
const handleBatchDelete = () => {
  if (selectedFiles.value.length === 0) {
    message("请选择要删除的文件", { type: "warning" });
    return;
  }

  batchDeleteDialogVisible.value = true;

  if (currentStatus.value === "deleted") {
    batchDeleteForce.value = true;
  } else {
    batchDeleteForce.value = false;
  }
};

// 确认批量删除
const confirmBatchDelete = async () => {
  const isForce = batchDeleteForce.value;
  const success = await batchDeleteFiles(selectedFiles.value, isForce);

  if (success) {
    clearTableSelection();
    fetchFileList();
    batchDeleteDialogVisible.value = false;
  }
};

// 获取删除按钮文字
const getDeleteButtonText = () => {
  if (currentStatus.value === "deleted") {
    return "永久删除";
  }
  return batchDeleteForce.value ? "永久删除" : "删除";
};

// 组件挂载时执行
onMounted(() => {
  fetchFileList();
  initDeviceFingerprint();
});
</script>

<style lang="scss" scoped>
.files-container {
  padding: 8px;
  min-height: calc(100vh - 84px);

  .content-card {
    margin-bottom: 10px;

    :deep(.el-card__body) {
      padding: 12px;
    }
  }

  // 工具栏
  .toolbar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;

    &__actions,
    &__search {
      display: flex;
      gap: 6px;
      align-items: center;
    }
  }

  // 高级搜索
  .advanced-search {
    background-color: transparent;
    padding: 15px;
    border-radius: 0;
    margin-bottom: 15px;
    border-bottom: 1px solid #ebeef5;
    border-top: none;
    border-left: none;
    border-right: none;

    .el-form {
      width: 100%;

      .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 12px;

        .el-form-item {
          flex: 1;
          min-width: 220px;
          margin-bottom: 0;
        }
      }
    }

    .size-range {
      display: flex;
      align-items: center;
      width: 100%;

      .el-input-number {
        width: 45%;
      }

      .separator {
        margin: 0 8px;
        color: #909399;
      }
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-top: 5px;
    }
  }

  // 按钮样式
  .toolbar-btn {
    border-radius: 4px;
    font-weight: 500;
  }

  .search-btn {
    border-radius: 4px;
    font-weight: 500;
  }

  .form-btn {
    border-radius: 4px;
    font-weight: 500;
    padding: 8px 16px;
  }

  .status-btn {
    position: relative;

    .fa-chevron-down {
      font-size: 10px;
      opacity: 0.7;
    }
  }

  .dialog-btn {
    border-radius: 4px;
    font-weight: 500;
    padding: 8px 16px;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
  }

  // 下拉菜单样式
  :deep(.el-dropdown-menu__item) {
    display: flex;
    align-items: center;
    padding: 8px 16px;

    &.is-active {
      background-color: var(--el-color-primary-light-9);
      color: var(--el-color-primary);
    }

    .fa {
      width: 16px;
      text-align: center;
    }

    .ml-auto {
      margin-left: auto;
    }
  }
}
</style>
