<template>
  <div class="sales-chart-container">
    <el-card class="chart-card">
      <template #header>
        <div class="card-header">
          <span>{{ title }}</span>
          <el-radio-group v-model="chartType" size="small">
            <el-radio-button label="bar">柱状图</el-radio-button>
            <el-radio-button label="line">折线图</el-radio-button>
          </el-radio-group>
        </div>
      </template>
      <div ref="chartRef" class="chart-container" />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from "vue";
import echarts from "@/plugins/echarts";
import type { EChartsOption } from "echarts/types/dist/shared";
import { useWindowSize } from "@vueuse/core";

defineOptions({
  name: "SalesChart"
});

const props = defineProps({
  title: {
    type: String,
    default: "销售数据"
  },
  data: {
    type: Array,
    default: () => [
      { name: "产品A", value: 120 },
      { name: "产品B", value: 200 },
      { name: "产品C", value: 150 },
      { name: "产品D", value: 80 },
      { name: "产品E", value: 70 },
      { name: "产品F", value: 110 },
      { name: "产品G", value: 130 }
    ]
  }
});

const chartRef = ref<HTMLElement | null>(null);
const chartInstance = ref<echarts.ECharts | null>(null);
const chartType = ref("bar");
const { width } = useWindowSize();

const initChart = () => {
  if (!chartRef.value) return;

  chartInstance.value = echarts.init(chartRef.value);
  setChartOption();
};

const setChartOption = () => {
  if (!chartInstance.value) return;

  const names = props.data.map(item => item.name);
  const values = props.data.map(item => item.value);

  const option: EChartsOption = {
    tooltip: {
      trigger: "axis",
      axisPointer: {
        type: "shadow"
      }
    },
    grid: {
      left: "3%",
      right: "4%",
      bottom: "3%",
      containLabel: true
    },
    xAxis: {
      type: "category",
      data: names,
      axisLabel: {
        interval: 0,
        rotate: 30
      }
    },
    yAxis: {
      type: "value"
    },
    series: [
      {
        name: "销售额",
        type: chartType.value,
        data: values,
        itemStyle: {
          color: function (params) {
            const colorList = [
              "#5470c6",
              "#91cc75",
              "#fac858",
              "#ee6666",
              "#73c0de",
              "#3ba272",
              "#fc8452"
            ];
            return colorList[params.dataIndex % colorList.length];
          }
        }
      }
    ]
  };

  chartInstance.value.setOption(option);
};

const handleResize = () => {
  chartInstance.value?.resize();
};

watch(chartType, () => {
  setChartOption();
});

watch(
  () => width.value,
  () => {
    handleResize();
  }
);

onMounted(() => {
  initChart();
  window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
  chartInstance.value?.dispose();
});
</script>

<style lang="scss" scoped>
.sales-chart-container {
  width: 100%;

  .chart-card {
    width: 100%;
    height: 100%;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .chart-container {
    height: 300px;
    width: 100%;
  }
}

@media screen and (max-width: 768px) {
  .chart-container {
    height: 250px;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>
