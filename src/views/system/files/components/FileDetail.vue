<template>
  <div>
    <el-dialog
      v-model="visible"
      title="文件详情"
      width="800px"
      align-center
      @close="handleClose"
    >
    <div class="file-detail-container" v-if="fileData">
      <!-- 文件预览区域 -->
      <div class="file-detail-header" v-if="canPreviewFile">
        <div class="preview-wrapper">
          <img
            v-if="isImageFile"
            :src="fileData.http_url"
            class="preview-image"
            alt="文件预览"
          />
          <video
            v-else-if="isVideoFile"
            :src="fileData.http_url"
            controls
            class="preview-video"
          ></video>
          <audio
            v-else-if="isAudioFile"
            :src="fileData.http_url"
            controls
            class="preview-audio"
          ></audio>
        </div>
      </div>

      <!-- 文件详细信息 -->
      <div class="file-detail-info">
        <div class="detail-item-group">
          <div class="detail-item">
            <div class="detail-label">原始文件名</div>
            <div class="detail-value">{{ fileData.original_name }}</div>
          </div>
          <div class="detail-item">
            <div class="detail-label">文件大小</div>
            <div class="detail-value">
              {{ formatFileSize(fileData.file_size) }}
            </div>
          </div>
        </div>

        <div class="detail-item-group">
          <div class="detail-item">
            <div class="detail-label">文件类型</div>
            <div class="detail-value">
              <div class="file-type-item">
                <i :class="getFontAwesomeIcon(fileData.file_type)"></i>
                <span class="file-type-name">{{
                  getFileTypeName(fileData.file_type)
                }}</span>
              </div>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-label">文件扩展名</div>
            <div class="detail-value">
              <span class="file-ext-tag">{{
                fileData.file_extension?.toUpperCase()
              }}</span>
            </div>
          </div>
        </div>

        <div class="detail-item-group">
          <div class="detail-item">
            <div class="detail-label">存储位置</div>
            <div class="detail-value">
              <div
                class="storage-type"
                :class="getStorageClass(fileData.storage_type)"
              >
                <i :class="getStorageIcon(fileData.storage_type)"></i>
                <span>{{ getStorageTypeName(fileData.storage_type) }}</span>
              </div>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-label">文件ID</div>
            <div class="detail-value id-value">{{ fileData.file_id }}</div>
          </div>
        </div>

        <div class="detail-item full-width">
          <div class="detail-label">存储文件名</div>
          <div class="detail-value path-value">
            {{ fileData.store_name }}
          </div>
        </div>

        <div class="detail-item full-width">
          <div class="detail-label">存储路径</div>
          <div class="detail-value path-value">
            {{ fileData.file_path }}
          </div>
        </div>

        <div class="detail-item full-width">
          <div class="detail-label">文件哈希</div>
          <div class="detail-value">
            <div class="hash-detail">
              <span class="hash-algorithm">{{
                getHashAlgorithmName(fileData.hash_algorithm)
              }}</span>
              <span class="hash-value">{{ fileData.file_hash }}</span>
            </div>
          </div>
        </div>

        <div class="detail-item-group">
          <div class="detail-item">
            <div class="detail-label">创建时间</div>
            <div class="detail-value">
              {{ formatDate(fileData.create_time) }}
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-label">更新时间</div>
            <div class="detail-value">
              {{ formatDate(fileData.update_time) }}
            </div>
          </div>
        </div>

        <div class="detail-item full-width" v-if="fileData.http_url">
          <div class="detail-label">访问地址</div>
          <div class="detail-value link-value">
            <el-link type="primary" :href="fileData.http_url" target="_blank">{{
              fileData.http_url
            }}</el-link>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false" size="default" class="dialog-btn">
          <i class="fa fa-times mr-1"></i>
          关闭
        </el-button>
        <el-button
          type="primary"
          @click="handleDownload"
          size="default"
          class="dialog-btn"
        >
          <i class="fa fa-download mr-1"></i>
          下载文件
        </el-button>
        <el-button
          v-if="canPreviewFile"
          type="success"
          @click="handlePreview"
          size="default"
          class="dialog-btn"
        >
          <i class="fa fa-eye mr-1"></i>
          预览
        </el-button>
      </div>
    </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import type { FileInfo } from "@/api/fileManage";
import { useFileUtils } from "../composables/useFileUtils";
import { message } from "@/utils/message";

// Props
interface Props {
  modelValue?: boolean;
  file?: FileInfo | null;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  file: null
});

// Emits
const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  preview: [file: FileInfo];
}>();

// Hooks
const {
  formatFileSize,
  getFileTypeName,
  getFontAwesomeIcon,
  getStorageIcon,
  getStorageClass,
  getStorageTypeName,
  getHashAlgorithmName,
  formatDate,
  isImage,
  isVideo,
  isAudio,
  canPreview
} = useFileUtils();

// 状态
const fileData = ref<FileInfo | null>(null);

// 计算属性
const visible = computed({
  get: () => props.modelValue,
  set: (val: boolean) => emit("update:modelValue", val)
});

const isImageFile = computed(() => {
  return fileData.value ? isImage(fileData.value.file_extension) : false;
});

const isVideoFile = computed(() => {
  return fileData.value ? isVideo(fileData.value.file_extension) : false;
});

const isAudioFile = computed(() => {
  return fileData.value ? isAudio(fileData.value.file_extension) : false;
});

const canPreviewFile = computed(() => {
  return fileData.value ? canPreview(fileData.value) : false;
});

// 监听文件变化
watch(
  () => props.file,
  newFile => {
    if (newFile) {
      fileData.value = newFile;
    }
  },
  { immediate: true }
);

// 下载文件
const handleDownload = () => {
  if (fileData.value?.http_url) {
    const a = document.createElement("a");
    a.href = fileData.value.http_url;
    a.download = fileData.value.original_name;
    a.click();
  } else {
    message("文件无法下载，URL不存在", { type: "warning" });
  }
};

// 预览文件
const handlePreview = () => {
  if (fileData.value) {
    emit("preview", fileData.value);
  }
};

// 关闭事件
const handleClose = () => {
  fileData.value = null;
};
</script>

<style lang="scss" scoped>
.file-detail-container {
  display: flex;
  flex-direction: column;
  gap: 20px;

  .file-detail-header {
    display: flex;
    justify-content: center;
    margin-bottom: 10px;

    .preview-wrapper {
      max-height: 200px;
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;

      .preview-image {
        max-width: 100%;
        max-height: 200px;
        object-fit: contain;
      }

      .preview-video,
      .preview-audio {
        width: 100%;
        max-height: 200px;
      }
    }
  }

  .file-detail-info {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;

    .detail-item-group {
      display: flex;
      width: 100%;
      gap: 16px;

      .detail-item {
        flex: 1;
      }
    }

    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 4px;

      &.full-width {
        width: 100%;
      }

      .detail-label {
        font-size: 12px;
        color: #909399;
        font-weight: 500;
      }

      .detail-value {
        font-size: 14px;
        color: #303133;
        word-break: break-word;

        &.id-value {
          font-family: "Courier New", monospace;
          font-size: 13px;
        }

        &.path-value {
          font-family: "Courier New", monospace;
          font-size: 13px;
          padding: 4px 8px;
          border: 1px solid #e4e7ed;
          border-radius: 4px;
        }

        &.link-value {
          word-break: break-all;
        }
      }
    }
  }
}

// 文件类型项目
.file-type-item {
  display: flex;
  align-items: center;
  gap: 4px;

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
  padding: 0 6px;
  height: 20px;
  line-height: 18px;
  font-size: 12px;
  color: #606266;
  background-color: transparent;
  border: 1px solid #e4e7ed;
  border-radius: 3px;
}

// 存储类型
.storage-type {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;

  i {
    font-size: 13px;
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

// 哈希详情显示
.hash-detail {
  display: flex;
  align-items: center;
  gap: 8px;

  .hash-algorithm {
    background-color: transparent;
    color: #409eff;
    font-size: 12px;
    font-weight: 500;
    padding: 0;
    border-radius: 0;
  }

  .hash-value {
    font-family: "Courier New", monospace;
    color: #606266;
    font-size: 12px;
    background-color: transparent;
    padding: 0;
    border-radius: 0;
    border-bottom: 1px dashed #dcdfe6;
    border-left: none;
    border-right: none;
    border-top: none;
  }
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.dialog-btn {
  border-radius: 6px;
  font-weight: 500;
  padding: 8px 16px;
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
}
</style>
