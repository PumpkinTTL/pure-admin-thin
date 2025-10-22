<template>
  <div class="file-table-wrapper">
    <el-table
      ref="tableRef"
      :key="tableKey"
      :data="fileList"
      style="width: 100%"
      v-loading="loading"
      row-key="file_id"
      highlight-current-row
      :header-cell-style="tableHeaderStyle"
      :cell-style="tableCellStyle"
      @selection-change="handleSelectionChange"
    >
      <el-table-column type="selection" width="40" align="center" />
      <el-table-column label="文件信息" min-width="180">
        <template #default="{ row }">
          <div class="file-item">
            <div
              class="file-item__icon"
              :class="getFileTypeClass(row.file_type)"
            >
              <el-image
                v-if="isImage(row.file_extension)"
                :src="row.http_url"
                :preview-src-list="[row.http_url]"
                :preview-teleported="true"
                fit="cover"
                class="file-item__preview"
              >
                <template #error>
                  <div class="image-slot">
                    <i class="fa fa-image" />
                  </div>
                </template>
              </el-image>
              <i v-else :class="getFontAwesomeIcon(row.file_type)" />
            </div>
            <div class="file-item__details">
              <div class="file-item__name" :title="row.original_name">
                {{ row.original_name }}
              </div>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="存储名称" min-width="240" show-overflow-tooltip>
        <template #default="{ row }">
          <div class="store-name">
            <i class="fa fa-save" />
            <span>{{ row.store_name }}</span>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="文件大小" min-width="80" align="center">
        <template #default="{ row }">
          <span class="file-size">{{ formatFileSize(row.file_size) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="文件类型" min-width="100" align="center">
        <template #default="{ row }">
          <div class="file-type-item">
            <i :class="getFontAwesomeIcon(row.file_type)" />
            <span class="file-type-name">
              {{ getFileTypeName(row.file_type) }}
            </span>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="扩展名" min-width="80" align="center">
        <template #default="{ row }">
          <span class="file-ext-tag">
            {{ row.file_extension.toUpperCase() }}
          </span>
        </template>
      </el-table-column>
      <el-table-column label="存储位置" min-width="110" align="center">
        <template #default="{ row }">
          <div class="storage-type" :class="getStorageClass(row.storage_type)">
            <i :class="getStorageIcon(row.storage_type)" />
            <span>{{ getStorageTypeName(row.storage_type) }}</span>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="文件哈希" min-width="180" show-overflow-tooltip>
        <template #default="{ row }">
          <el-tooltip :content="row.file_hash" placement="top">
            <div class="hash-info">
              <span class="hash-algorithm">
                {{ getHashAlgorithmName(row.hash_algorithm) }}
              </span>
              <span class="hash-value">
                {{ row.file_hash.substring(0, 12) }}...
              </span>
            </div>
          </el-tooltip>
        </template>
      </el-table-column>
      <el-table-column label="备注" min-width="150" show-overflow-tooltip>
        <template #default="{ row }">
          <span class="remark-text">{{ row.remark || "-" }}</span>
        </template>
      </el-table-column>
      <el-table-column label="上传时间" min-width="150" align="center">
        <template #default="{ row }">
          <span class="create-time">{{ formatDate(row.create_time) }}</span>
        </template>
      </el-table-column>
      <el-table-column
        label="操作"
        min-width="200"
        fixed="right"
        align="center"
      >
        <template #default="{ row }">
          <div class="file-actions">
            <!-- 预览按钮 -->
            <el-button
              v-if="canPreview(row)"
              type="primary"
              size="small"
              text
              class="action-btn-text"
              @click="emit('preview', row)"
            >
              <i class="fa fa-eye mr-1" />
              预览
            </el-button>

            <!-- 下载按钮 -->
            <el-button
              type="primary"
              size="small"
              text
              class="action-btn-text"
              @click="emit('download', row)"
            >
              <i class="fa fa-download mr-1" />
              下载
            </el-button>

            <!-- 编辑按钮 -->
            <el-button
              type="primary"
              size="small"
              text
              class="action-btn-text"
              @click="emit('detail', row)"
            >
              <i class="fa fa-edit mr-1" />
              编辑
            </el-button>

            <!-- 活跃文件的删除按钮 -->
            <el-button
              v-if="status === 'active'"
              type="danger"
              size="small"
              text
              class="action-btn-text"
              @click="emit('delete', row)"
            >
              <i class="fa fa-trash-alt mr-1" />
              删除
            </el-button>

            <!-- 已删除文件的恢复按钮 -->
            <el-button
              v-if="status === 'deleted'"
              type="warning"
              size="small"
              text
              class="action-btn-text"
              @click="emit('restore', row)"
            >
              <i class="fa fa-undo-alt mr-1" />
              恢复
            </el-button>

            <!-- 已删除文件的彻底删除按钮 -->
            <el-button
              v-if="status === 'deleted'"
              type="danger"
              size="small"
              text
              class="action-btn-text"
              @click="emit('forceDelete', row)"
            >
              <i class="fa fa-times-circle mr-1" />
              彻底删除
            </el-button>
          </div>
        </template>
      </el-table-column>
    </el-table>

    <!-- 空状态 -->
    <div v-if="fileList.length === 0 && !loading" class="empty-state">
      <el-empty description="暂无文件数据" />
    </div>

    <!-- 分页 -->
    <div class="pagination">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="currentPageSize"
        :page-sizes="[5, 10, 20, 50]"
        background
        layout="total, sizes, prev, pager, next, jumper"
        :total="total"
        small
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import type { FileInfo } from "@/api/fileManage";
import { useFileUtils } from "../composables/useFileUtils";

// Props
interface Props {
  fileList: FileInfo[];
  total: number;
  page: number;
  pageSize: number;
  loading?: boolean;
  status: "active" | "deleted";
  tableKey?: number;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  tableKey: 0
});

// Emits
const emit = defineEmits<{
  "update:page": [value: number];
  "update:pageSize": [value: number];
  selectionChange: [selection: FileInfo[]];
  preview: [file: FileInfo];
  download: [file: FileInfo];
  detail: [file: FileInfo];
  delete: [file: FileInfo];
  restore: [file: FileInfo];
  forceDelete: [file: FileInfo];
}>();

// Hooks
const {
  formatFileSize,
  getFileTypeName,
  getFontAwesomeIcon,
  getFileTypeClass,
  getStorageIcon,
  getStorageClass,
  getStorageTypeName,
  getHashAlgorithmName,
  formatDate,
  isImage,
  canPreview,
  getFileThumbnail
} = useFileUtils();

// Refs
const tableRef = ref();

// 计算属性
const currentPage = computed({
  get: () => props.page,
  set: (val: number) => emit("update:page", val)
});

const currentPageSize = computed({
  get: () => props.pageSize,
  set: (val: number) => emit("update:pageSize", val)
});

// 选择变化
const handleSelectionChange = (selection: FileInfo[]) => {
  emit("selectionChange", selection);
};

// 分页大小变化
const handleSizeChange = (val: number) => {
  emit("update:pageSize", val);
};

// 页码变化
const handleCurrentChange = (val: number) => {
  emit("update:page", val);
};

// 清除表格选择
const clearSelection = () => {
  if (tableRef.value) {
    tableRef.value.clearSelection();
  }
};

// 表格样式
const tableHeaderStyle = {
  background: "linear-gradient(180deg, #fafbfc 0%, #f4f6f9 100%)",
  color: "#303133",
  fontWeight: "600",
  fontSize: "13px",
  padding: "10px 0",
  borderBottom: "2px solid #e4e7ed"
};

const tableCellStyle = {
  padding: "10px 8px",
  fontSize: "13px",
  borderBottom: "1px solid #f0f2f5"
};

// 导出方法供父组件调用
defineExpose({
  clearSelection
});
</script>

<style lang="scss" scoped>
.file-table-wrapper {
  width: 100%;
  overflow: visible;

  .el-table {
    margin-top: 15px;
    margin-bottom: 15px;
  }

  // 文件操作按钮
  .file-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: center;

    .el-button {
      margin-left: 0;
    }
  }

  .file-item {
    display: flex;
    align-items: center;

    &__icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      margin-right: 8px;
      font-size: 16px;
      border-radius: 4px;

      &.image-file {
        color: #1890ff;
        background-color: transparent;
      }

      &.video-file {
        color: #e6a23c;
        background-color: transparent;
      }

      &.audio-file {
        color: #f56c6c;
        background-color: transparent;
      }

      &.document-file {
        color: #409eff;
        background-color: transparent;
      }

      &.archive-file {
        color: #52c41a;
        background-color: transparent;
      }

      &.other-file {
        color: #909399;
        background-color: transparent;
      }
    }

    &__preview {
      width: 32px;
      height: 32px;
      cursor: pointer;
      border-radius: 4px;

      :deep(.el-image__inner) {
        width: 32px;
        height: 32px;
        object-fit: cover;
      }
    }

    &__details {
      flex: 1;
      min-width: 0;
    }

    &__name {
      margin-bottom: 0;
      overflow: hidden;
      font-size: 13px;
      font-weight: 500;
      color: #303133;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  // 存储名称
  .store-name {
    display: flex;
    gap: 6px;
    align-items: center;
    font-size: 12px;
    color: #606266;

    i {
      flex-shrink: 0;
      font-size: 14px;
      color: #409eff;
    }

    span {
      overflow: hidden;
      font-family: "Courier New", monospace;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  // 文件大小
  .file-size {
    font-size: 12px;
    font-weight: 500;
    color: #606266;
    white-space: nowrap;
  }

  // 文件类型项目
  .file-type-item {
    display: flex;
    gap: 4px;
    align-items: center;
    justify-content: center;

    i {
      font-size: 14px;

      &.fa-image {
        color: #1890ff;
      }

      &.fa-video {
        color: #e6a23c;
      }

      &.fa-headphones {
        color: #f56c6c;
      }

      &.fa-file-alt {
        color: #409eff;
      }

      &.fa-file-archive {
        color: #52c41a;
      }

      &.fa-file {
        color: #909399;
      }
    }

    .file-type-name {
      font-size: 12px;
    }
  }

  // 文件扩展名标签
  .file-ext-tag {
    display: inline-block;
    height: 20px;
    padding: 0 6px;
    font-size: 12px;
    line-height: 18px;
    color: #606266;
    background-color: transparent;
    border: 1px solid #e4e7ed;
    border-radius: 3px;
  }

  // 存储类型
  .storage-type {
    display: flex;
    gap: 4px;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    white-space: nowrap;

    i {
      flex-shrink: 0;
      font-size: 13px;
    }

    span {
      white-space: nowrap;
    }

    &.storage-local {
      color: #409eff;
    }

    &.storage-aliyun {
      color: #67c23a;
    }

    &.storage-tencent {
      color: #e6a23c;
    }

    &.storage-qiniu {
      color: #f56c6c;
    }
  }

  // 哈希信息
  .hash-info {
    display: flex;
    gap: 4px;
    align-items: center;
    padding: 0;
    font-size: 12px;
    cursor: pointer;
    background-color: transparent;
    border-radius: 0;

    .hash-algorithm {
      padding: 0;
      font-weight: 500;
      color: #606266;
      background-color: transparent;
      border-radius: 0;
    }

    .hash-value {
      font-family: "Courier New", monospace;
      color: #909399;
    }
  }

  // 创建时间
  .create-time {
    font-size: 12px;
    color: #606266;
  }

  // 备注
  .remark-text {
    font-size: 12px;
    color: #606266;
  }

  :deep(.el-table) {
    overflow: hidden;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgb(0 0 0 / 6%);

    .el-table__header th {
      font-weight: 600;
      color: #606266;
      background-color: transparent;
    }

    .el-table__row {
      &:hover {
        background-color: rgb(245 249 255 / 60%) !important;
      }

      &.current-row {
        background-color: rgb(236 245 255 / 60%) !important;
      }
    }

    // 文件扩展名列样式
    .file-ext-tag {
      font-weight: 500;
    }

    // 哈希值列样式
    .hash-value {
      font-family: "Courier New", monospace;
    }
  }

  .empty-state {
    padding: 20px 0;
  }

  .pagination {
    display: flex;
    justify-content: flex-end;
    padding: 12px 0;
    margin-top: 18px;
    border-top: 1px solid #e4e7ed;
  }
}

.action-btn-text {
  padding: 5px 10px;
  margin: 0 2px;
  font-weight: 500;
  border-radius: 4px;
}
</style>
