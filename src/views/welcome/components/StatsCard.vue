<template>
  <div class="stats-card" v-motion :initial="{ opacity: 0, scale: 0.9 }"
    :enter="{ opacity: 1, scale: 1, transition: { delay: 100 } }">
    <el-card shadow="hover" class="animate__animated animate__fadeIn">
      <div class="card-content">
        <div class="icon-container" :style="{ backgroundColor: color + '15' }">
          <font-awesome-icon :icon="icon" :style="{ color: color }" />
        </div>
        <div class="stats-info">
          <div class="title">{{ title }}</div>
          <div class="value">{{ value }}</div>
          <div class="trend" :class="{ 'up': isIncrease, 'down': !isIncrease }">
            <font-awesome-icon :icon="isIncrease ? 'arrow-up' : 'arrow-down'" />
            {{ increase }}
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({
  name: "StatsCard",
  props: {
    icon: {
      type: String,
      required: true
    },
    color: {
      type: String,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    value: {
      type: String,
      required: true
    },
    increase: {
      type: String,
      required: true
    },
    isIncrease: {
      type: Boolean,
      default: true
    }
  }
});
</script>

<style scoped lang="scss">
.stats-card {
  margin-bottom: 10px;

  .el-card {
    height: 100%;
  }

  .card-content {
    display: flex;
    align-items: center;
    padding: 10px;

    .icon-container {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;

      i {
        font-size: 24px;
      }
    }

    .stats-info {
      flex: 1;

      .title {
        font-size: 14px;
        color: #909399;
        margin-bottom: 5px;
      }

      .value {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
      }

      .trend {
        font-size: 12px;
        display: flex;
        align-items: center;

        i {
          margin-right: 5px;
        }

        &.up {
          color: #67C23A;
        }

        &.down {
          color: #F56C6C;
        }
      }
    }
  }
}
</style>