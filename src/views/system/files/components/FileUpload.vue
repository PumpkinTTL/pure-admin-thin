<template>
  <div class="file-upload-wrapper">
    <!-- 上传方式选择下拉菜单 -->
    <el-dropdown @command="handleCommand">
      <el-button type="primary" size="small" class="toolbar-btn">
        <i class="fa fa-cloud-upload-alt mr-1"></i>
        上传文件
        <el-icon class="el-icon--right">
          <arrow-down />
        </el-icon>
      </el-button>
      <template #dropdown>
        <el-dropdown-menu>
          <el-dropdown-item command="local">
            <el-icon><Upload /></el-icon>
            <span style="margin-left: 8px">本地上传</span>
          </el-dropdown-item>
          <el-dropdown-item command="cloud">
            <el-icon><Cloudy /></el-icon>
            <span style="margin-left: 8px">云存储上传</span>
          </el-dropdown-item>
          <el-dropdown-item command="record" divided>
            <el-icon><DocumentAdd /></el-icon>
            <span style="margin-left: 8px">添加记录</span>
          </el-dropdown-item>
        </el-dropdown-menu>
      </template>
    </el-dropdown>

    <!-- 隐藏的文件选择器 -->
    <input
      ref="fileInputRef"
      type="file"
      multiple
      style="display: none"
      @change="handleFileSelect"
    />

    <!-- 隐藏的本地上传组件 -->
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
      :auto-upload="false"
      style="display: none"
    >
    </el-upload>

    <!-- 上传进度对话框 -->
    <el-dialog
      v-model="progressDialogVisible"
      width="500px"
      :close-on-click-modal="false"
      destroy-on-close
      class="modern-dialog"
    >
      <template #header>
        <div class="dialog-header">
          <i class="fa fa-spinner fa-spin"></i>
          <span>上传进度</span>
        </div>
      </template>
      <div class="upload-progress-container">
        <div v-for="(item, index) in uploadFiles" :key="index" class="upload-progress-item">
          <div class="progress-info">
            <i class="fa fa-file"></i>
            <span class="file-name">{{ item.name }}</span>
          </div>
          <el-progress :percentage="item.percentage" :status="item.status" />
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer-modern">
          <el-button @click="progressDialogVisible = false" size="small">关闭</el-button>
          <el-button type="primary" @click="continueUpload" size="small">继续上传</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 本地上传对话框 -->
    <el-dialog
      v-model="localDialogVisible"
      width="520px"
      :close-on-click-modal="false"
      destroy-on-close
      class="modern-dialog"
    >
      <template #header>
        <div class="dialog-header">
          <i class="fa fa-upload"></i>
          <span>本地上传</span>
        </div>
      </template>
      <el-form :model="localForm" label-position="top" class="modern-form">
        <div class="form-section">
          <div class="section-label">选择文件</div>
          <div class="local-upload-area" @click="triggerFileInput">
            <el-icon class="upload-icon-local"><upload-filled /></el-icon>
            <div class="upload-text-local">
              {{ selectedLocalFiles.length > 0 ? `已选择 ${selectedLocalFiles.length} 个文件` : '将文件拖到此处，或点击选择' }}
            </div>
            <div class="upload-hint-local">支持批量上传,单个文件大小不超过 8MB</div>
          </div>
        </div>
        <div v-if="selectedLocalFiles.length > 0" class="file-preview-box">
          <div
            v-for="(file, index) in selectedLocalFiles"
            :key="index"
            class="file-preview-item"
          >
            <i class="fa fa-file"></i>
            <span class="name">{{ file.name }}</span>
            <span class="size">{{ formatSize(file.size) }}</span>
            <i class="fa fa-times remove-btn" @click="removeFile(index)" title="移除"></i>
          </div>
        </div>
        <el-form-item label="备注">
          <el-input
            v-model="localForm.remark"
            type="textarea"
            :rows="3"
            placeholder="选填文件备注信息"
            maxlength="200"
            show-word-limit
            size="default"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer-modern">
          <el-button @click="handleCancelLocalUpload" size="default">取消</el-button>
          <el-button type="primary" @click="handleStartLocalUpload" :disabled="selectedLocalFiles.length === 0" size="default">
            <i class="fa fa-cloud-upload-alt" style="margin-right: 4px"></i>
            开始上传
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 云存储上传对话框 -->
    <el-dialog
      v-model="cloudDialogVisible"
      width="520px"
      :close-on-click-modal="false"
      destroy-on-close
      class="modern-dialog"
    >
      <template #header>
        <div class="dialog-header">
          <i class="fa fa-cloud"></i>
          <span>云存储上传</span>
        </div>
      </template>
      <el-form :model="cloudForm" label-position="top" class="modern-form cloud-form">
        <el-form-item label="存储平台">
          <el-select v-model="cloudForm.platform" placeholder="请选择云存储平台" size="default" style="width: 100%">
            <el-option label="阿里云 OSS" value="aliyun">
              <span style="float: left">阿里云 OSS</span>
              <span style="float: right; color: #8492a6; font-size: 12px">Aliyun</span>
            </el-option>
            <el-option label="腾讯云 COS" value="tencent">
              <span style="float: left">腾讯云 COS</span>
              <span style="float: right; color: #8492a6; font-size: 12px">Tencent</span>
            </el-option>
            <el-option label="七牛云" value="qiniu">
              <span style="float: left">七牛云</span>
              <span style="float: right; color: #8492a6; font-size: 12px">Qiniu</span>
            </el-option>
            <el-option label="AWS S3" value="aws">
              <span style="float: left">AWS S3</span>
              <span style="float: right; color: #8492a6; font-size: 12px">Amazon</span>
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <div class="cloud-upload-section">
        <div class="section-label">选择文件</div>
        <el-upload
          ref="cloudUploadRef"
          :auto-upload="false"
          :on-change="handleCloudFileChange"
          :limit="8"
          :multiple="true"
          drag
          class="modern-upload-drag"
        >
          <el-icon class="upload-icon"><upload-filled /></el-icon>
          <div class="upload-text">将文件拖到此处，或点击上传</div>
          <div class="upload-hint">支持批量上传，单个文件大小不超过 8MB</div>
        </el-upload>
      </div>
      <template #footer>
        <div class="dialog-footer-modern">
          <el-button @click="cloudDialogVisible = false" size="default">取消</el-button>
          <el-button type="primary" @click="handleCloudUpload" size="default">
            <i class="fa fa-cloud-upload-alt" style="margin-right: 4px"></i>
            开始上传
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 添加记录对话框 -->
    <el-dialog
      v-model="recordDialogVisible"
      width="540px"
      :close-on-click-modal="false"
      destroy-on-close
      class="modern-dialog"
    >
      <template #header>
        <div class="dialog-header">
          <i class="fa fa-plus-circle"></i>
          <span>添加文件记录</span>
        </div>
      </template>
      <el-form
        ref="recordFormRef"
        :model="recordForm"
        :rules="recordFormRules"
        label-position="top"
        class="modern-form record-form"
      >
        <div class="form-row">
          <el-form-item label="文件名称" prop="filename" class="form-item-flex">
            <el-input v-model="recordForm.filename" placeholder="例如: document.pdf" clearable size="default" />
          </el-form-item>
          <el-form-item label="MIME 类型" prop="mime_type" class="form-item-small">
            <el-input v-model="recordForm.mime_type" placeholder="image/png" clearable size="default" />
          </el-form-item>
        </div>
        <el-form-item label="访问 URL" prop="url">
          <el-input v-model="recordForm.url" placeholder="https://example.com/file.pdf" size="default" />
        </el-form-item>
        <div class="form-row">
          <el-form-item label="文件大小（字节）" prop="size" class="form-item-flex">
            <el-input-number v-model="recordForm.size" :min="0" :step="1024" controls-position="right" size="default" style="width: 100%" />
          </el-form-item>
          <el-form-item label="格式化显示" class="form-item-small">
            <el-input :value="recordForm.size > 0 ? formatSize(recordForm.size) : '-'" size="default" disabled />
          </el-form-item>
        </div>
        <el-form-item label="备注信息">
          <el-input v-model="recordForm.remark" placeholder="选填备注信息" type="textarea" :rows="3" maxlength="200" show-word-limit size="default" />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer-modern">
          <el-button @click="handleCancelRecord" size="default">取消</el-button>
          <el-button type="primary" @click="handleAddRecord" :loading="recordLoading" size="default">
            <i class="fa fa-save" style="margin-right: 4px"></i>
            保存记录
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from "vue";
import {
  Document,
  ArrowDown,
  Upload,
  Cloudy,
  DocumentAdd,
  UploadFilled
} from "@element-plus/icons-vue";
import type { UploadProps, FormInstance, FormRules } from "element-plus";
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
const fileInputRef = ref<HTMLInputElement>();
const cloudUploadRef = ref();
const recordFormRef = ref<FormInstance>();
const progressDialogVisible = ref(false);
const localDialogVisible = ref(false);
const cloudDialogVisible = ref(false);
const recordDialogVisible = ref(false);
const uploadFiles = ref<any[]>([]);
const selectedLocalFiles = ref<File[]>([]);
const recordLoading = ref(false);

// 本地上传表单
const localForm = reactive({
  remark: ""
});

// 云存储表单
const cloudForm = reactive({
  platform: "aliyun",
  files: [] as File[]
});

// 添加记录表单
const recordForm = reactive({
  filename: "",
  url: "",
  size: 0,
  mime_type: "",
  remark: ""
});

// 添加记录表单验证规则
const recordFormRules = reactive<FormRules>({
  filename: [{ required: true, message: "请输入文件名称", trigger: "blur" }],
  url: [{ required: true, message: "请输入文件URL", trigger: "blur" }],
  size: [
    { required: true, message: "请输入文件大小", trigger: "blur" },
    { type: "number", message: "文件大小必须为数字", trigger: "blur" }
  ],
  mime_type: [{ required: true, message: "请输入文件类型", trigger: "blur" }]
});

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

// 处理下拉菜单命令
const handleCommand = (command: string) => {
  switch (command) {
    case "local":
      // 直接打开本地上传对话框
      localDialogVisible.value = true;
      break;
    case "cloud":
      // 打开云存储上传对话框
      cloudDialogVisible.value = true;
      break;
    case "record":
      // 打开添加记录对话框
      recordDialogVisible.value = true;
      break;
  }
};

// 在对话框中触发文件选择
const triggerFileInput = () => {
  fileInputRef.value?.click();
};

// 处理文件选择
const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    const files = Array.from(input.files);
    const maxSize = 8 * 1024 * 1024; // 8MB
    
    // 过滤过大的文件
    const validFiles: File[] = [];
    const invalidFiles: string[] = [];
    
    files.forEach(file => {
      if (file.size > maxSize) {
        invalidFiles.push(file.name);
      } else {
        validFiles.push(file);
      }
    });
    
    if (invalidFiles.length > 0) {
      message(`以下文件超过8MB已被过滤：${invalidFiles.join(", ")}`, { type: "warning" });
    }
    
    // 追加有效文件到已选列表
    selectedLocalFiles.value = [...selectedLocalFiles.value, ...validFiles];
    // 清空 input 以便下次选择相同文件时也能触发 change 事件
    input.value = "";
  }
};

// 取消本地上传
const handleCancelLocalUpload = () => {
  localDialogVisible.value = false;
  selectedLocalFiles.value = [];
  localForm.remark = "";
};

// 开始本地上传
const handleStartLocalUpload = () => {
  if (selectedLocalFiles.value.length === 0) {
    message("请选择要上传的文件", { type: "warning" });
    return;
  }

  const maxSize = 8 * 1024 * 1024; // 8MB
  
  // 再次验证文件大小
  const validFiles = selectedLocalFiles.value.filter(file => file.size <= maxSize);
  const invalidFiles = selectedLocalFiles.value.filter(file => file.size > maxSize);
  
  if (invalidFiles.length > 0) {
    message(`有 ${invalidFiles.length} 个文件超过8MB，无法上传`, { type: "error" });
    return;
  }

  if (validFiles.length === 0) {
    message("没有可上传的文件", { type: "warning" });
    return;
  }

  // 更新上传数据的 remark
  uploadData.value.remark = localForm.remark || "系统文件管理上传的文件";

  // 清空上传列表
  uploadFiles.value = [];

  // 关闭对话框
  localDialogVisible.value = false;

  // 手动触发上传
  validFiles.forEach(file => {
    uploadRef.value?.handleStart(file);
  });

  // 开始上传
  uploadRef.value?.submit();

  // 显示进度对话框
  progressDialogVisible.value = true;

  // 清空选择
  selectedLocalFiles.value = [];
  localForm.remark = "";
};

// 移除选中的文件
const removeFile = (index: number) => {
  selectedLocalFiles.value.splice(index, 1);
};

// 格式化文件大小
const formatSize = (bytes: number) => {
  if (bytes === 0) return "0 B";
  const k = 1024;
  const sizes = ["B", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + " " + sizes[i];
};

// 上传前检查
const beforeUpload: UploadProps["beforeUpload"] = file => {
  // 检查文件大小，限制为8MB
  const maxSize = 8 * 1024 * 1024;
  if (file.size > maxSize) {
    message(`文件 ${file.name} 超过8MB，已跳过`, { type: "error" });
    return false;
  }

  // 添加到上传文件列表
  uploadFiles.value.push({
    name: file.name,
    size: file.size,
    percentage: 0,
    status: ""
  });

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
  progressDialogVisible.value = false;
  uploadFiles.value = [];
};

// 云存储文件选择
const handleCloudFileChange = (file: any, fileList: any[]) => {
  cloudForm.files = fileList.map(item => item.raw).filter(Boolean);
};

// 云存储上传
const handleCloudUpload = () => {
  if (!cloudForm.platform) {
    message("请选择云存储平台", { type: "warning" });
    return;
  }
  if (cloudForm.files.length === 0) {
    message("请选择要上传的文件", { type: "warning" });
    return;
  }

  // TODO: 云存储上传逻辑，等待后端接口实现
  message(
    `已选择 ${cloudForm.files.length} 个文件上传到 ${getPlatformName(cloudForm.platform)}，功能开发中...`,
    { type: "info" }
  );

  // 关闭对话框并清空表单
  cloudDialogVisible.value = false;
  cloudForm.files = [];
  cloudUploadRef.value?.clearFiles();
};

// 获取平台名称
const getPlatformName = (platform: string) => {
  const platformMap: Record<string, string> = {
    aliyun: "阿里云 OSS",
    tencent: "腾讯云 COS",
    qiniu: "七牛云",
    aws: "AWS S3"
  };
  return platformMap[platform] || platform;
};

// 取消添加记录
const handleCancelRecord = () => {
  recordDialogVisible.value = false;
  recordFormRef.value?.resetFields();
};

// 添加文件记录
const handleAddRecord = async () => {
  if (!recordFormRef.value) return;

  await recordFormRef.value.validate(async (valid, fields) => {
    if (valid) {
      recordLoading.value = true;
      try {
        // TODO: 调用后端接口添加记录
        // const res = await addFileRecord(recordForm);
        
        // 模拟成功
        await new Promise(resolve => setTimeout(resolve, 500));
        
        message(`文件记录 "${recordForm.filename}" 添加成功`, { type: "success" });

        // 关闭对话框并清空表单
        recordDialogVisible.value = false;
        recordFormRef.value?.resetFields();

        // 触发成功事件刷新列表
        emit("success");
      } catch (error) {
        message("添加记录失败，请稍后重试", { type: "error" });
      } finally {
        recordLoading.value = false;
      }
    } else {
      console.log("表单验证失败:", fields);
    }
  });
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
  border-radius: 4px;
  font-weight: 500;
}

// 上传进度容器
.upload-progress-container {
  max-height: 400px;
  overflow-y: auto;
}

// 上传进度项
.upload-progress-item {
  padding: 14px;
  margin-bottom: 12px;
  background: #f7f8fa;
  border-radius: 4px;
  border: 1px solid #e8eaed;

  &:last-child {
    margin-bottom: 0;
  }

  .progress-info {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 13px;

    i {
      color: #409eff;
      font-size: 15px;
    }

    .file-name {
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      color: #303133;
      font-weight: 500;
    }
  }
}

// 简洁对话框样式
.modern-dialog {
  :deep(.el-dialog) {
    border-radius: 6px;
    overflow: hidden;
  }

  :deep(.el-dialog__header) {
    padding: 16px 20px;
    border-bottom: 1px solid #e4e7ed;
    background: #fafbfc;
  }

  :deep(.el-dialog__body) {
    padding: 20px;
  }

  :deep(.el-dialog__footer) {
    padding: 14px 20px;
    border-top: 1px solid #e4e7ed;
    background: #fafbfc;
  }
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  font-weight: 600;
  color: #303133;

  i {
    font-size: 18px;
    color: #409eff;
  }
}

.dialog-footer-modern {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.modern-form {
  :deep(.el-form-item) {
    margin-bottom: 20px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  :deep(.el-form-item__label) {
    font-size: 14px;
    font-weight: 600;
    padding-bottom: 10px;
    color: #303133;
  }

  :deep(.el-input__inner),
  :deep(.el-textarea__inner) {
    font-size: 13px;
  }

  :deep(.el-input-number) {
    width: 100%;
  }

  :deep(.el-select) {
    width: 100%;
  }
}

// 记录表单特殊布局
.record-form {
  .form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;

    .form-item-flex {
      flex: 1;
      min-width: 0;
      margin-bottom: 0;
    }

    .form-item-small {
      width: 180px;
      flex-shrink: 0;
      margin-bottom: 0;
    }
  }
}

// 文件预览框
.file-preview-box {
  margin-top: -4px;
  margin-bottom: 16px;
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  background: #f7f8fa;
}

.file-preview-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 14px;
  font-size: 13px;
  border-bottom: 1px solid #e8eaed;
  background: white;

  &:last-child {
    border-bottom: none;
  }

  &:hover {
    background: #f0f3f7;

    .remove-btn {
      opacity: 1;
    }
  }

  > i.fa-file {
    color: #409eff;
    font-size: 15px;
  }

  .name {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #303133;
    font-weight: 500;
  }

  .size {
    color: #909399;
    font-size: 12px;
  }

  .remove-btn {
    color: #f56c6c;
    font-size: 14px;
    cursor: pointer;
    opacity: 0.4;

    &:hover {
      color: #f13838;
      opacity: 1;
    }
  }
}


// 云存储上传区域和表单区块
.cloud-upload-section,
.form-section {
  padding: 0 20px 20px;

  .section-label {
    font-size: 14px;
    font-weight: 600;
    color: #303133;
    margin-bottom: 10px;
  }
}

.cloud-form {
  padding-bottom: 0;

  :deep(.el-form-item:last-child) {
    margin-bottom: 20px;
  }
}

// 上传拖拽区
.modern-upload-drag {
  :deep(.el-upload) {
    width: 100%;
  }

  :deep(.el-upload-dragger) {
    padding: 40px 24px;
    border-radius: 6px;
    border: 2px dashed #d0d4d9;
    background: #fafbfc;

    &:hover {
      border-color: #409eff;
      background: #f0f7ff;
    }
  }

  .upload-icon {
    font-size: 48px;
    color: #409eff;
    margin-bottom: 16px;
  }

  .upload-text {
    font-size: 14px;
    color: #303133;
    font-weight: 500;
    margin-bottom: 8px;
  }

  .upload-hint {
    font-size: 12px;
    color: #909399;
    line-height: 1.6;
  }
}

// 本地上传区域
.local-upload-area {
  padding: 40px 24px;
  border-radius: 6px;
  border: 2px dashed #d0d4d9;
  background: #fafbfc;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;

  &:hover {
    border-color: #409eff;
    background: #f0f7ff;
  }

  .upload-icon-local {
    font-size: 48px;
    color: #409eff;
    margin-bottom: 16px;
  }

  .upload-text-local {
    font-size: 14px;
    color: #303133;
    font-weight: 500;
    margin-bottom: 8px;
  }

  .upload-hint-local {
    font-size: 12px;
    color: #909399;
    line-height: 1.6;
  }
}

// 旧样式兼容
.selected-files {
  min-height: 100px;
  padding: 12px;
  border: 1px dashed #dcdfe6;
  border-radius: 4px;
  background-color: #fafafa;

  .empty-text {
    text-align: center;
    color: #909399;
    padding: 30px 0;
    font-size: 13px;
  }

  .file-list {
    max-height: 200px;
    overflow-y: auto;
  }

  .file-item-small {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    margin-bottom: 6px;
    background-color: white;
    border-radius: 4px;
    font-size: 13px;

    i {
      color: #409eff;
      font-size: 14px;
    }

    .file-name {
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      color: #303133;
    }

    .file-size-small {
      color: #909399;
      font-size: 12px;
    }
  }
}
</style>
