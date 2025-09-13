<template>
  <div class="data-overview-container">
    <el-card class="overview-card">
      <template #header>
        <div class="card-header">
          <span>{{ title }}</span>
          <el-dropdown>
            <span class="el-dropdown-link">
              {{ currentPeriod }}
              <el-icon class="el-icon--right">
                <arrow-down />
              </el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-for="period in periods" :key="period" @click="currentPeriod = period">
                  {{ period }}
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </template>
      <div class="overview-content">
        <div v-for="(item, index) in overviewData" :key="index" class="overview-item">
          <div class="item-icon" :style="{ backgroundColor: item.bgColor }">
            <el-icon>
              <component :is="item.icon" />
            </el-icon>
          </div>
          <div class="item-info">
            <div class="item-title">{{ item.title }}</div>
            <div class="item-value">{{ item.value }}</div>
            <div class="item-trend">
              <span :class="item.trend > 0 ? 'up' : 'down'">
                {{ item.trend > 0 ? "+" : "" }}{{ item.trend }}%
                <el-icon>
                  <component :is="item.trend > 0 ? 'CaretTop' : 'CaretBottom'" />
                </el-icon>
              </span>
              <span class="trend-period">较{{ currentPeriod === "本月" ? "上月" : "昨日" }}</span>
            </div>
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import {
  ArrowDown,
  CaretTop,
  CaretBottom,
  TrendCharts,
  ShoppingCart,
  User,
  Money
} from "@element-plus/icons-vue";

defineOptions({
  name: "DataOverview"
});

const props = defineProps({
  title: {
    type: String,
    default: "数据概览"
  }
});

const periods = ["今日", "本周", "本月", "本季度", "本年"];
const currentPeriod = ref("本月");

const overviewData = computed(() => [
  {
    title: "总销售额",
    value: "¥ 126,560",
    trend: 12.5,
    icon: "Money",
    bgColor: "#409EFF"
  },
  {
    title: "访问量",
    value: "88,546",
    trend: 8.2,
    icon: "TrendCharts",
    bgColor: "#67C23A"
  },
  {
    title: "订单量",
    value: "6,325",
    trend: -2.5,
    icon: "ShoppingCart",
    bgColor: "#E6A23C"
  },
  {
    title: "新增用户",
    value: "1,258",
    trend: 5.8,
    icon: "User",
    bgColor: "#F56C6C"
  }
]);
</script>

<style lang="scss" scoped>
.data-overview-container {
  width: 100%;

  .overview-card {
    width: 100%;
    height: 100%;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .overview-content {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
  }

  .overview-item {
    flex: 1;
    min-width: 200px;
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 4px;
    transition: all 0.3s;

    &:hover {
      background-color: var(--el-fill-color-light);
    }
  }

  .item-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 12px;

    .el-icon {
      font-size: 24px;
      color: #fff;
    }
  }

  .item-info {
    flex: 1;
  }

  .item-title {
    font-size: 14px;
    color: var(--el-text-color-secondary);
    margin-bottom: 4px;
  }

  .item-value {
    font-size: 20px;
    font-weight: bold;
    color: var(--el-text-color-primary);
    margin-bottom: 4px;
  }

  .item-trend {
    font-size: 12px;
    display: flex;
    align-items: center;

    .up {
      color: var(--el-color-success);
    }

    .down {
      color: var(--el-color-danger);
    }

    .trend-period {
      color: var(--el-text-color-secondary);
      margin-left: 4px;
    }
  }
}

@media screen and (max-width: 768px) {
  .overview-content {
    flex-direction: column;
  }

  .overview-item {
    min-width: 100%;
  }

  .item-value {
    font-size: 18px;
  }
}
</style>
