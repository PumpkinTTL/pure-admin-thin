<template>
  <div>
    <el-dialog
      v-model="visible"
      :title="fileData?.original_name || '文件预览'"
      :width="isTextFile ? '80%' : '70%'"
      align-center
      destroy-on-close
      @close="handleClose"
    >
      <div
        v-loading="loading"
        class="preview-dialog"
        element-loading-text="加载中..."
        element-loading-spinner="fa fa-spinner fa-spin"
        element-loading-svg-view-box="-10, -10, 50, 50"
      >
        <!-- 图片预览 -->
        <el-image
          v-if="isImageFile"
          :src="fileData?.http_url"
          :preview-src-list="[fileData?.http_url]"
          :preview-teleported="true"
          fit="contain"
          class="preview-dialog__image"
        >
          <template #error>
            <div class="image-error">
              <i class="fa fa-image" />
              <p>图片加载失败</p>
            </div>
          </template>
        </el-image>
        <!-- 视频预览 -->
        <video
          v-else-if="isVideoFile"
          ref="videoRef"
          :src="fileData?.http_url"
          controls
          class="preview-dialog__video"
        />
        <!-- 音频预览 -->
        <audio
          v-else-if="isAudioFile"
          ref="audioRef"
          :src="fileData?.http_url"
          controls
          class="preview-dialog__audio"
        />
        <!-- 文本预览 -->
        <div v-else-if="isTextFile" class="preview-dialog__text">
          <div v-if="textContent" class="text-content-wrapper">
            <div class="text-info">
              <span class="text-info-item">
                <i class="fa fa-file-alt" />
                扩展名: {{ fileData?.file_extension?.toUpperCase() }}
              </span>
              <span class="text-info-item">
                <i class="fa fa-font" />
                编码: {{ textEncoding }}
              </span>
              <span class="text-info-item">
                <i class="fa fa-code" />
                {{ textLines }} 行
              </span>
            </div>
            <pre
              class="text-content"
            ><code :class="getLanguageClass()">{{ textContent }}</code></pre>
          </div>
          <div v-else-if="textError" class="preview-error">
            <i class="fa fa-exclamation-triangle" />
            <p>{{ textError }}</p>
          </div>
        </div>
        <!-- Word预览 -->
        <div v-else-if="isWordFile" class="preview-dialog__office">
          <div class="office-header">
            <i class="fa fa-file-word" />
            <span class="office-title">{{ fileData?.original_name }}</span>
            <span class="office-type">Word文档</span>
          </div>
          <div
            v-loading="officeLoading"
            class="office-content"
            element-loading-text="正在加载文档..."
            element-loading-spinner="fa fa-spinner fa-spin"
          >
            <vue-office-docx
              :src="getProxyUrl(fileData?.file_id)"
              @rendered="handleOfficeRendered"
              @error="handleOfficeError"
            />
          </div>
        </div>
        <!-- Excel预览 -->
        <div v-else-if="isExcelFile" class="preview-dialog__office">
          <div class="office-header">
            <i class="fa fa-file-excel" />
            <span class="office-title">{{ fileData?.original_name }}</span>
            <span class="office-type">Excel表格</span>
          </div>
          <div
            v-loading="officeLoading"
            class="office-content"
            element-loading-text="正在加载表格..."
            element-loading-spinner="fa fa-spinner fa-spin"
          >
            <vue-office-excel
              :src="getProxyUrl(fileData?.file_id)"
              @rendered="handleOfficeRendered"
              @error="handleOfficeError"
            />
          </div>
        </div>
        <!-- PDF预览 -->
        <div v-else-if="isPdfFile" class="preview-dialog__office">
          <div class="office-header">
            <i class="fa fa-file-pdf" />
            <span class="office-title">{{ fileData?.original_name }}</span>
            <span class="office-type">PDF文档</span>
          </div>
          <div
            v-loading="officeLoading"
            class="office-content"
            element-loading-text="正在加载PDF..."
            element-loading-spinner="fa fa-spinner fa-spin"
          >
            <vue-office-pdf
              :src="getProxyUrl(fileData?.file_id)"
              @rendered="handleOfficeRendered"
              @error="handleOfficeError"
            />
          </div>
        </div>
        <!-- 不支持预览 -->
        <div v-else class="preview-dialog__empty">
          <i class="fa fa-eye-slash" />
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
import VueOfficeDocx from "@vue-office/docx";
import VueOfficeExcel from "@vue-office/excel";
import VueOfficePdf from "@vue-office/pdf";
import "@vue-office/docx/lib/index.css";
import "@vue-office/excel/lib/index.css";
import { baseUrlApi } from "@/api/utils";

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
const { isImage, isVideo, isAudio, isText, isWord, isExcel, isPdf } =
  useFileUtils();

// 状态
const fileData = ref<FileInfo | null>(null);
const loading = ref(false);
const officeLoading = ref(false);
const textContent = ref("");
const textEncoding = ref("");
const textError = ref("");

// 媒体元素引用
const videoRef = ref<HTMLVideoElement | null>(null);
const audioRef = ref<HTMLAudioElement | null>(null);

// 计算属性
const visible = computed({
  get: () => props.modelValue,
  set: (val: boolean) => emit("update:modelValue", val)
});

const isImageFile = computed(
  () => fileData.value && isImage(fileData.value.file_extension)
);
const isVideoFile = computed(
  () => fileData.value && isVideo(fileData.value.file_extension)
);
const isAudioFile = computed(
  () => fileData.value && isAudio(fileData.value.file_extension)
);
const isTextFile = computed(
  () => fileData.value && isText(fileData.value.file_extension)
);
const isWordFile = computed(
  () => fileData.value && isWord(fileData.value.file_extension)
);
const isExcelFile = computed(
  () => fileData.value && isExcel(fileData.value.file_extension)
);
const isPdfFile = computed(
  () => fileData.value && isPdf(fileData.value.file_extension)
);

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
  newVal => {
    if (newVal && props.file) {
      // 对话框打开时，重新设置 fileData
      fileData.value = props.file;

      // 如果是Office文件，设置加载状态
      if (
        props.file.file_extension &&
        (isWord(props.file.file_extension) ||
          isExcel(props.file.file_extension) ||
          isPdf(props.file.file_extension))
      ) {
        officeLoading.value = true;
      }

      // 如果是文本文件，总是重新加载内容（不依赖缓存）
      if (props.file.file_extension && isText(props.file.file_extension)) {
        loadTextContent(props.file);
      }
    }
  }
);

// 获取代理URL
const getProxyUrl = (fileId: number | undefined) => {
  if (!fileId) return "";
  // 组件销毁后重建，不需要时间戳
  return `${baseUrlApi}/file/proxy?file_id=${fileId}`;
};

// Office文件渲染成功
const handleOfficeRendered = () => {
  officeLoading.value = false;
  loading.value = false;
  // 组件销毁后重建，每次都是新实例，只会触发一次
  message("文档加载成功", { type: "success" });
};

// Office文件加载错误
const handleOfficeError = (error: any) => {
  console.error("Office文件预览失败:", error);
  message("文件预览失败，请下载后查看", { type: "error" });
  officeLoading.value = false;
  loading.value = false;
};

// 关闭事件
const handleClose = () => {
  // 停止音频/视频播放
  if (videoRef.value) {
    videoRef.value.pause();
    videoRef.value.currentTime = 0;
  }
  if (audioRef.value) {
    audioRef.value.pause();
    audioRef.value.currentTime = 0;
  }

  // 清空文本内容
  textContent.value = "";
  textError.value = "";
  textEncoding.value = "";
};
</script>

<script lang="ts">
export default {
  components: {
    VueOfficeDocx,
    VueOfficeExcel,
    VueOfficePdf
  }
};
</script>

<style lang="scss" scoped>
.preview-dialog {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  background-color: transparent;
  border-radius: 0;

  &__image {
    max-width: 100%;
    max-height: 70vh;
    border-radius: 8px;
    box-shadow: 0 2px 12px 0 rgb(0 0 0 / 10%);

    :deep(.el-image__inner) {
      max-width: 100%;
      max-height: 70vh;
      object-fit: contain;
    }
  }

  &__video {
    width: 100%;
    max-height: 70vh;
    border-radius: 8px;
    box-shadow: 0 2px 12px 0 rgb(0 0 0 / 10%);
  }

  &__audio {
    width: 100%;
    border-radius: 8px;
  }

  &__text {
    width: 100%;
  }

  &__office {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 70vh;
    overflow: hidden;
    background: var(--el-bg-color);
    border-radius: 8px;
    box-shadow: 0 2px 12px 0 rgb(0 0 0 / 5%);
  }

  &__empty {
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: center;
    padding: 60px 20px;
    font-size: 14px;
    color: var(--el-text-color-secondary);
    text-align: center;
    background: var(--el-fill-color-light);
    border: 1px dashed var(--el-border-color);
    border-radius: 8px;

    // 移动端适配
    @media (width <=768px) {
      padding: 40px 16px;

      i {
        font-size: 42px;
      }

      p {
        font-size: 13px;
      }
    }

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

// Office头部
.office-header {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-lighter);

  i {
    font-size: 18px;
    color: var(--el-color-primary);
  }

  .office-title {
    flex: 1;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  .office-type {
    padding: 2px 6px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
    background: var(--el-fill-color);
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 4px;
  }
}

// Office内容区
.office-content {
  flex: 1;
  padding: 12px 16px;
  overflow: auto;
  background: var(--el-bg-color);
}

// 图片加载错误样式
.image-error {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 200px;
  color: var(--el-text-color-secondary);
  background: var(--el-fill-color-light);
  border-radius: 8px;

  i {
    font-size: 48px;
    color: var(--el-text-color-placeholder);
  }

  p {
    margin: 0;
    font-size: 14px;
  }
}

// 文本内容区域
.text-content-wrapper {
  width: 100%;
  margin-top: 10px;
  overflow: hidden;
  background: var(--el-bg-color);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  box-shadow: 0 2px 12px 0 rgb(0 0 0 / 5%);
}

// 文本信息栏
.text-info {
  display: flex;
  gap: 24px;
  align-items: center;
  padding: 14px 20px;
  font-size: 13px;
  color: var(--el-text-color-regular);
  background: var(--el-fill-color-light);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--el-border-color-lighter);

  &-item {
    display: flex;
    gap: 8px;
    align-items: center;
    padding: 6px 12px;
    background: var(--el-bg-color);
    border: 1px solid var(--el-border-color-light);
    border-radius: 6px;
    transition: all 0.2s;

    &:hover {
      background: var(--el-fill-color);
      border-color: var(--el-color-primary-light-7);
    }

    i {
      font-size: 14px;
      color: var(--el-color-primary);
    }
  }
}

// 文本内容区
.text-content {
  max-height: calc(70vh - 120px);
  padding: 20px;
  margin: 0;
  overflow: auto;
  font-family: Consolas, Monaco, "Courier New", Courier, monospace;
  font-size: 13px;
  line-height: 1.8;
  color: var(--el-text-color-primary);
  tab-size: 4;
  white-space: pre;
  background: var(--el-fill-color-light);

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
  gap: 16px;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--el-color-danger);
  background: var(--el-color-danger-light-9);
  border: 1px solid var(--el-color-danger-light-7);
  border-radius: 8px;

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
    color: #d4d4d4;
    background: #1e1e1e;

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
    box-shadow: 0 2px 16px 0 rgb(0 0 0 / 30%);
  }

  .preview-dialog__image,
  .preview-dialog__video {
    box-shadow: 0 2px 16px 0 rgb(0 0 0 / 40%);
  }
}
</style>
