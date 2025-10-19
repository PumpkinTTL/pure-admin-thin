<template>
  <div>
    <el-dialog
      v-model="visible"
      :title="fileData?.original_name || '文件预览'"
      width="70%"
      align-center
      @close="handleClose"
    >
    <div class="preview-dialog">
      <img
        v-if="isImageFile"
        :src="fileData?.http_url"
        class="preview-dialog__image"
        alt="图片预览"
      />
      <video
        v-else-if="isVideoFile"
        :src="fileData?.http_url"
        controls
        class="preview-dialog__video"
      ></video>
      <audio
        v-else-if="isAudioFile"
        :src="fileData?.http_url"
        controls
        class="preview-dialog__audio"
      ></audio>
      <div v-else class="preview-dialog__empty">该文件类型暂不支持预览</div>
    </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import type { FileInfo } from "@/api/fileManage";
import { useFileUtils } from "../composables/useFileUtils";

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
const { isImage, isVideo, isAudio } = useFileUtils();

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

// 关闭事件
const handleClose = () => {
  fileData.value = null;
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
  }

  &__video {
    width: 100%;
    max-height: 70vh;
  }

  &__audio {
    width: 100%;
  }

  &__empty {
    color: #909399;
    font-size: 14px;
    text-align: center;
    padding: 30px 0;
  }
}
</style>
