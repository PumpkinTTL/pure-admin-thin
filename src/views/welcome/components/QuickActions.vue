<template>
  <div class="quick-actions" v-motion :initial="{ opacity: 0, y: 50 }"
    :enter="{ opacity: 1, y: 0, transition: { delay: 600 } }">
    <el-card shadow="hover" class="animate__animated animate__fadeIn">
      <template #header>
        <div class="card-header">
          <span>快捷操作</span>
        </div>
      </template>
      <div class="actions-grid">
        <div v-for="(action, index) in quickActions" :key="index" class="action-item">
          <el-button :type="action.type" circle size="large">
            <font-awesome-icon :icon="action.icon" />
          </el-button>
          <span class="action-name">{{ action.name }}</span>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script lang="ts">
import { ref, defineComponent } from "vue";

interface QuickAction {
  name: string;
  icon: string;
  type: 'primary' | 'success' | 'warning' | 'danger' | 'info';
}

export default defineComponent({
  name: "QuickActions",
  setup() {
    const quickActions = ref<QuickAction[]>([
      {
        name: "写文章",
        icon: "edit",
        type: "primary"
      },
      {
        name: "上传图片",
        icon: "image",
        type: "success"
      },
      {
        name: "评论管理",
        icon: "comment",
        type: "warning"
      },
      {
        name: "用户管理",
        icon: "user",
        type: "danger"
      },
      {
        name: "系统设置",
        icon: "cog",
        type: "info"
      },
      {
        name: "数据统计",
        icon: "chart-bar",
        type: "primary"
      }
    ]);

    return {
      quickActions
    };
  }
});
</script>

<style scoped lang="scss">
.quick-actions {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;

    .action-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      .action-name {
        margin-top: 8px;
        font-size: 12px;
        color: #606266;
      }
    }
  }

  @media (max-width: 768px) {
    .actions-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
}
</style>