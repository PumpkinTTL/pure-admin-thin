<template>
  <div class="file-upload-wrapper">
    <!-- 上传按钮 -->
    <el-upload
      ref="uploadRef"
      :action="uploadUrl"
      :headers="uploadHeaders"
      :data="uploadData"
      :on-success="handleSuccess"
      :on-error="handleError"
      :on-progress="handleProgress"
      :before-upload="beforeUpload"
      :limit="8"
      :multiple="true"
      :show-file-list="false"
    >
      <el-button type="primary" size="small" class="toolbar-btn">
        <i class="fa fa-cloud-upload-alt mr-1"></i>
        上传文件
      </el-button>
    </el-upload>

    <!-- 上传进度对话框 -->
    <el-dialog
      v-model="dialogVisible"
      title="文件上传进度"
      width="450px"
      align-center
      destroy-on-close
    >
      <div v-for="(item, index) in uploadFiles" :key="index" class="upload-item">
        <div class="upload-item__info">
          <el-icon>
            <Document />
          </el-icon>
          <span class="upload-item__name">{{ item.name }}</span>
        </div>
        <el-progress :percentage="item.percentage" :status="item.status" />
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="dialogVisible = false" size="small">关闭</el-button>
          <el-button type="primary" @click="continueUpload" size="small"
            >继续上传</el-button
          >
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { Document } from "@element-plus/icons-vue";
import type { UploadProps } from "element-plus";
import { baseUrlApi } from "@/api/utils";
import { getToken } from "@/utils/auth";
import { message } from "@/utils/message";
import { getFingerprint } from "@/utils/fingerprint";

// Props
interface Props {
  deviceFingerprint?: string;
}

const props = withDefaults(defineProps<Props>(), {
  deviceFingerprint: ""
});

// Emits
const emit = defineEmits<{
  success: [];
  error: [error: any];
}>();

// 状态
const uploadRef = ref();
const dialogVisible = ref(false);
const uploadFiles = ref<any[]>([]);

// 上传URL
const uploadUrl = `${baseUrlApi}/upload/uploadFile`;

// 上传请求头
const uploadHeaders = computed(() => {
  const token = getToken();
  return {
    Authorization: token?.token ? `Bearer ${token.token}` : ""
  };
});

// 上传数据
const uploadData = ref({
  remark: "系统文件管理上传的文件",
  device_fingerprint: props.deviceFingerprint || ""
});

// 上传前检查
const beforeUpload: UploadProps["beforeUpload"] = file => {
  // 检查文件大小，限制为8MB
  const isLt8M = file.size / 1024 / 1024 < 8;
  if (!isLt8M) {
    message("文件大小不能超过8MB!", { type: "error" });
    return false;
  }

  // 添加到上传文件列表
  uploadFiles.value.push({
    name: file.name,
    size: file.size,
    percentage: 0,
    status: ""
  });

  dialogVisible.value = true;
  return true;
};

// 上传进度
const handleProgress: UploadProps["onProgress"] = (event, file) => {
  const fileIndex = uploadFiles.value.findIndex(item => item.name === file.name);
  if (fileIndex !== -1) {
    uploadFiles.value[fileIndex].percentage = Math.round(event.percent);
  }
};

// 上传成功
const handleSuccess: UploadProps["onSuccess"] = (response: any, file) => {
  const fileIndex = uploadFiles.value.findIndex(item => item.name === file.name);
  if (fileIndex !== -1) {
    uploadFiles.value[fileIndex].status = "success";
  }

  if (response?.code === 200) {
    // 检查是否有重复文件
    const duplicateFiles =
      response.data?.filter((item: any) => item.is_duplicate) || [];
    const newFiles =
      response.data?.filter((item: any) => !item.is_duplicate) || [];

    if (duplicateFiles.length > 0) {
      message(`文件 ${file.name} 已存在，跳过上传`, { type: "warning" });
    }
    if (newFiles.length > 0) {
      message(`文件 ${file.name} 上传成功`, { type: "success" });
    }

    // 触发成功事件
    emit("success");
  } else {
    message(response?.msg || `文件 ${file.name} 上传失败`, { type: "error" });
  }
};

// 上传失败
const handleError: UploadProps["onError"] = (error, file) => {
  const fileIndex = uploadFiles.value.findIndex(item => item.name === file.name);
  if (fileIndex !== -1) {
    uploadFiles.value[fileIndex].status = "exception";
  }
  console.error("文件上传失败:", error);
  message(`文件 ${file.name} 上传失败`, { type: "error" });
  emit("error", error);
};

// 继续上传
const continueUpload = () => {
  dialogVisible.value = false;
  uploadFiles.value = [];
};

// 导出方法供父组件调用
defineExpose({
  clearFiles: () => {
    uploadFiles.value = [];
  }
});
</script>

<style lang="scss" scoped>
.file-upload-wrapper {
  display: inline-block;
}

.upload-item {
  margin-bottom: 14px;

  &__info {
    display: flex;
    align-items: center;
    margin-bottom: 8px;

    .el-icon {
      color: #409eff;
      margin-right: 8px;
    }

    &__name {
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      font-size: 13px;
    }
  }
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.toolbar-btn {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
}
</style>
