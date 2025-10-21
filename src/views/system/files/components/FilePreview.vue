<template>
  <div>
    <el-dialog
      v-model="visible"
      :title="fileData?.original_name || '文件预览'"
      :width="isTextFile ? '80%' : '70%'"
      align-center
      @close="handleClose"
    >
    <div class="preview-dialog" v-loading="loading" element-loading-text="加载中...">
      <!-- 图片预览 -->
      <img
        v-if="isImageFile"
        :src="fileData?.http_url"
        class="preview-dialog__image"
        alt="图片预览"
      />
      <!-- 视频预览 -->
      <video
        v-else-if="isVideoFile"
        :src="fileData?.http_url"
        controls
        class="preview-dialog__video"
      ></video>
      <!-- 音频预览 -->
      <audio
        v-else-if="isAudioFile"
        :src="fileData?.http_url"
        controls
        class="preview-dialog__audio"
      ></audio>
      <!-- 文本预览 -->
      <div v-else-if="isTextFile" class="preview-dialog__text">
        <div v-if="textContent" class="text-content-wrapper">
          <div class="text-info">
            <span class="text-info-item">
              <i class="fa fa-file-alt"></i>
              扩展名: {{ fileData?.file_extension?.toUpperCase() }}
            </span>
            <span class="text-info-item">
              <i class="fa fa-font"></i>
              编码: {{ textEncoding }}
            </span>
            <span class="text-info-item">
              <i class="fa fa-code"></i>
              {{ textLines }} 行
            </span>
          </div>
          <pre class="text-content"><code :class="getLanguageClass()">{{ textContent }}</code></pre>
        </div>
        <div v-else-if="textError" class="preview-error">
          <i class="fa fa-exclamation-triangle"></i>
          <p>{{ textError }}</p>
        </div>
      </div>
      <!-- 不支持预览 -->
      <div v-else class="preview-dialog__empty">
        <i class="fa fa-eye-slash"></i>
        <p>该文件类型暂不支持预览</p>
      </div>
    </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import type { FileInfo } from "@/api/fileManage";
import { getFileContent } from "@/api/fileManage";
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
}>();

// Hooks
const { isImage, isVideo, isAudio, isText } = useFileUtils();

// 状态
const fileData = ref<FileInfo | null>(null);
const loading = ref(false);
const textContent = ref("");
const textEncoding = ref("");
const textError = ref("");

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

const isTextFile = computed(() => {
  return fileData.value ? isText(fileData.value.file_extension) : false;
});

const textLines = computed(() => {
  if (!textContent.value) return 0;
  return textContent.value.split("\n").length;
});

// 获取语言类名（用于语法高亮）
const getLanguageClass = () => {
  if (!fileData.value) return "";
  const ext = fileData.value.file_extension.toLowerCase();
  const langMap: Record<string, string> = {
    js: "language-javascript",
    ts: "language-typescript",
    vue: "language-vue",
    jsx: "language-jsx",
    tsx: "language-tsx",
    php: "language-php",
    java: "language-java",
    py: "language-python",
    c: "language-c",
    cpp: "language-cpp",
    go: "language-go",
    sql: "language-sql",
    json: "language-json",
    xml: "language-xml",
    html: "language-html",
    css: "language-css",
    md: "language-markdown",
    yaml: "language-yaml",
    yml: "language-yaml",
    sh: "language-bash",
    bat: "language-batch"
  };
  return langMap[ext] || "";
};

// 加载文本文件内容
const loadTextContent = async (file: FileInfo) => {
  if (!isText(file.file_extension)) return;
  
  loading.value = true;
  textContent.value = "";
  textError.value = "";
  textEncoding.value = "";
  
  try {
    const res: any = await getFileContent(file.file_id);
    if (res?.code === 200 && res?.data) {
      textContent.value = res.data.content;
      textEncoding.value = res.data.encoding || "UTF-8";
    } else {
      textError.value = res?.message || "加载文件内容失败";
      message(textError.value, { type: "error" });
    }
  } catch (error) {
    console.error("加载文本文件失败:", error);
    textError.value = "加载文件内容失败，请稍后重试";
    message(textError.value, { type: "error" });
  } finally {
    loading.value = false;
  }
};

// 监听文件变化
watch(
  () => props.file,
  newFile => {
    if (newFile) {
      fileData.value = newFile;
      // 如果是文本文件，加载内容
      if (newFile.file_extension && isText(newFile.file_extension)) {
        loadTextContent(newFile);
      }
    }
  },
  { immediate: true }
);

// 监听对话框显示状态
watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal && props.file) {
      // 对话框打开时，重新设置 fileData
      fileData.value = props.file;
      // 如果是文本文件且内容为空，加载内容
      if (props.file.file_extension && isText(props.file.file_extension) && !textContent.value) {
        loadTextContent(props.file);
      }
    }
  }
);

// 关闭事件
const handleClose = () => {
  // 不再清空 fileData，保持数据
  textContent.value = "";
  textError.value = "";
  textEncoding.value = "";
};
</script>

<style lang="scss" scoped>
.preview-dialog {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
  background-color: transparent;
  border-radius: 0;

  &__image {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
  }

  &__video {
    width: 100%;
    max-height: 70vh;
    border-radius: 8px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
  }

  &__audio {
    width: 100%;
    border-radius: 8px;
  }

  &__text {
    width: 100%;
  }

  &__empty {
    color: var(--el-text-color-secondary);
    font-size: 14px;
    text-align: center;
    padding: 60px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    background: var(--el-fill-color-light);
    border-radius: 8px;
    border: 1px dashed var(--el-border-color);

    i {
      font-size: 56px;
      color: var(--el-text-color-placeholder);
      opacity: 0.5;
    }

    p {
      margin: 0;
      color: var(--el-text-color-regular);
    }
  }
}

// 文本内容区域
.text-content-wrapper {
  width: 100%;
  background: var(--el-bg-color);
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--el-border-color-lighter);
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.05);
}

// 文本信息栏
.text-info {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 14px 20px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-lighter);
  font-size: 13px;
  color: var(--el-text-color-regular);
  backdrop-filter: blur(10px);

  &-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: var(--el-bg-color);
    border-radius: 6px;
    transition: all 0.2s;
    border: 1px solid var(--el-border-color-light);

    &:hover {
      background: var(--el-fill-color);
      border-color: var(--el-color-primary-light-7);
    }

    i {
      color: var(--el-color-primary);
      font-size: 14px;
    }
  }
}

// 文本内容区
.text-content {
  margin: 0;
  padding: 20px;
  background: var(--el-fill-color-light);
  color: var(--el-text-color-primary);
  font-family: 'Consolas', 'Monaco', 'Courier New', 'Courier', monospace;
  font-size: 13px;
  line-height: 1.8;
  overflow: auto;
  max-height: calc(70vh - 120px);
  white-space: pre;
  tab-size: 4;
  
  code {
    font-family: inherit;
    color: inherit;
    background: transparent;
  }

  // 自定义滚动条
  &::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }

  &::-webkit-scrollbar-track {
    background: var(--el-fill-color);
    border-radius: 4px;
  }

  &::-webkit-scrollbar-thumb {
    background: var(--el-border-color);
    border-radius: 4px;

    &:hover {
      background: var(--el-border-color-dark);
    }
  }
}

// 错误提示
.preview-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 60px 20px;
  color: var(--el-color-danger);
  background: var(--el-color-danger-light-9);
  border-radius: 8px;
  border: 1px solid var(--el-color-danger-light-7);
  
  i {
    font-size: 56px;
    opacity: 0.6;
  }
  
  p {
    margin: 0;
    font-size: 14px;
    color: var(--el-text-color-regular);
  }
}

// 深色模式专用样式
html.dark {
  .text-content {
    background: #1e1e1e;
    color: #d4d4d4;
    
    // 深色模式下的滚动条
    &::-webkit-scrollbar-track {
      background: #2d2d2d;
    }

    &::-webkit-scrollbar-thumb {
      background: #454545;

      &:hover {
        background: #5a5a5a;
      }
    }
  }

  .text-content-wrapper {
    box-shadow: 0 2px 16px 0 rgba(0, 0, 0, 0.3);
  }

  .preview-dialog__image,
  .preview-dialog__video {
    box-shadow: 0 2px 16px 0 rgba(0, 0, 0, 0.4);
  }
}
</style>
