<template>
  <div class="ranking-list-container">
    <el-card class="ranking-card">
      <template #header>
        <div class="card-header">
          <span>{{ title }}</span>
          <el-tag size="small" type="success">{{ period }}</el-tag>
        </div>
      </template>
      <div class="ranking-content">
        <div v-for="(item, index) in rankData" :key="index" class="ranking-item">
          <div class="ranking-item-header">
            <div class="ranking-index" :class="{ 'top-three': index < 3 }">
              {{ index + 1 }}
            </div>
            <div class="ranking-name">{{ item.name }}</div>
            <div class="ranking-value">{{ item.value }}</div>
          </div>
          <el-progress :percentage="getPercentage(item.value)" :stroke-width="10" :color="getProgressColor(index)" />
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

defineOptions({
  name: "RankingList"
});

interface RankItem {
  name: string;
  value: number;
}

const props = defineProps({
  title: {
    type: String,
    default: "销售排行榜"
  },
  period: {
    type: String,
    default: "本月"
  },
  data: {
    type: Array<RankItem>,
    default: () => [
      { name: "产品A", value: 2530 },
      { name: "产品B", value: 2300 },
      { name: "产品C", value: 1980 },
      { name: "产品D", value: 1850 },
      { name: "产品E", value: 1690 },
      { name: "产品F", value: 1450 },
      { name: "产品G", value: 1200 },
      { name: "产品H", value: 980 },
      { name: "产品I", value: 860 },
      { name: "产品J", value: 740 }
    ]
  },
  maxItems: {
    type: Number,
    default: 10
  }
});

const rankData = computed(() => {
  return props.data
    .slice()
    .sort((a, b) => b.value - a.value)
    .slice(0, props.maxItems);
});

const maxValue = computed(() => {
  if (rankData.value.length === 0) return 0;
  return Math.max(...rankData.value.map(item => item.value));
});

const getPercentage = (value: number) => {
  if (maxValue.value === 0) return 0;
  return Math.round((value / maxValue.value) * 100);
};

const getProgressColor = (index: number) => {
  const colors = [
    "#f56c6c", // 第一名 - 红色
    "#e6a23c", // 第二名 - 橙色
    "#5cb87a", // 第三名 - 绿色
    "#409eff" // 其他 - 蓝色
  ];

  return index < 3 ? colors[index] : colors[3];
};
</script>

<style lang="scss" scoped>
.ranking-list-container {
  width: 100%;

  .ranking-card {
    width: 100%;
    height: 100%;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .ranking-content {
    padding: 0 10px;
  }

  .ranking-item {
    margin-bottom: 15px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .ranking-item-header {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
  }

  .ranking-index {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #909399;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 12px;
    margin-right: 10px;

    &.top-three {
      font-weight: bold;
    }
  }

  .ranking-name {
    flex: 1;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .ranking-value {
    font-size: 14px;
    font-weight: bold;
    margin-left: 10px;
    color: var(--el-color-primary);
  }
}

@media screen and (max-width: 768px) {
  .ranking-item-header {
    flex-wrap: wrap;
  }

  .ranking-name {
    font-size: 12px;
  }

  .ranking-value {
    font-size: 12px;
  }
}
</style>
