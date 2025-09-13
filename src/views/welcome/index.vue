<template>
  <div class="dashboard">
    <!-- 页面顶部：欢迎信息和用户状态 -->
    <el-row :gutter="16">
      <el-col :span="24">
        <welcome-header />
      </el-col>
    </el-row>

    <!-- 网站收益数据区域 -->
    <el-row :gutter="16">
      <el-col :span="24">
        <revenue-stats />
      </el-col>
    </el-row>

    <!-- 博客统计和服务器监控 -->
    <el-row :gutter="16">
      <el-col :xs="24" :sm="24" :md="24" :lg="16" :xl="16">
        <blog-stats />
      </el-col>
      <el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
        <server-monitor />
      </el-col>
    </el-row>
  </div>
</template>
<script lang="ts">
import { defineComponent } from "vue";
import WelcomeHeader from "./components/WelcomeHeader.vue";
import ServerMonitor from "./components/ServerMonitor.vue";
import BlogStats from "./components/BlogStats.vue";
import RevenueStats from "./components/RevenueStats.vue";

export default defineComponent({
  name: "Dashboard",
  components: {
    WelcomeHeader,
    ServerMonitor,
    BlogStats,
    RevenueStats
  }
});
</script>

<style scoped lang="scss">
.dashboard {
  max-width: 100%;
  overflow-x: hidden;

  .el-row {
    margin-bottom: 16px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  @media (max-width: 768px) {
    padding: 10px;
  }
}
</style>
