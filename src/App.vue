<template>
  <el-config-provider :locale="currentLocale">
    <router-view />
    <ReDialog />
  </el-config-provider>
</template>

<script lang="ts">
import { defineComponent, onMounted } from "vue";
import { ElConfigProvider } from "element-plus";
import { ReDialog } from "@/components/ReDialog";
import zhCn from "element-plus/es/locale/lang/zh-cn";
import { generateFingerprint, getFingerprint } from "@/utils/fingerprint";

export default defineComponent({
  name: "app",
  components: {
    [ElConfigProvider.name]: ElConfigProvider,
    ReDialog
  },
  setup() {
    onMounted(async () => {
      try {
        // 判断是否存在指纹
        const fingerprint = await getFingerprint();
        if (!fingerprint) {
          // 在应用挂载时生成浏览器指纹
          const fingerprint = await generateFingerprint();
          console.log('应用初始化完成，浏览器指纹:', fingerprint);
        } else {
          console.log('已存在浏览器指纹:', fingerprint);
        }


      } catch (error) {
        console.error('生成浏览器指纹时出错:', error);
      }
    });
  },
  computed: {
    currentLocale() {
      return zhCn;
    }
  }
});
</script>
