<template>
  <i :class="iconClass"></i>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  icon: string;
}>();

// 将 "fas fa-home" 格式转换为实际的 class
const iconClass = computed(() => {
  if (!props.icon) return "";
  // 如果已经是正确的格式,直接返回
  if (props.icon.includes(" ")) {
    return props.icon;
  }
  return props.icon;
});
</script>

<style scoped>
/* 确保图标大小继承父元素 */
i {
  font-size: inherit;
  color: inherit;
}
</style>
