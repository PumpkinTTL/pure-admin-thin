<template>
  <div class="visitors-chart" v-motion :initial="{ opacity: 0, y: 50 }"
    :enter="{ opacity: 1, y: 0, transition: { delay: 200 } }">
    <el-card shadow="hover" class="animate__animated animate__fadeIn">
      <template #header>
        <div class="card-header">
          <span>访问趋势</span>
          <div class="header-right">
            <el-radio-group v-model="timeRange" size="small">
              <el-radio-button value="week">本周</el-radio-button>
              <el-radio-button value="month">本月</el-radio-button>
              <el-radio-button value="year">全年</el-radio-button>
            </el-radio-group>
          </div>
        </div>
      </template>
      <div ref="chartRef" class="chart-container"></div>
    </el-card>
  </div>
</template>

<script lang="ts">
import { ref, onMounted, watch, defineComponent, onBeforeUnmount } from "vue";
import * as echarts from "echarts";

export default defineComponent({
  name: "VisitorsChart",
  setup() {
    const chartRef = ref<HTMLElement | null>(null);
    let chart: echarts.ECharts | null = null;
    const timeRange = ref("week");

    const weekData = {
      dates: ["周一", "周二", "周三", "周四", "周五", "周六", "周日"],
      values: [820, 932, 901, 934, 1290, 1330, 1320],
      uniqueVisitors: [320, 332, 301, 334, 390, 330, 320]
    };

    const monthData = {
      dates: Array.from({ length: 30 }, (_, i) => `${i + 1}日`),
      values: Array.from({ length: 30 }, () => Math.floor(Math.random() * 1000) + 500),
      uniqueVisitors: Array.from({ length: 30 }, () => Math.floor(Math.random() * 500) + 200)
    };

    const yearData = {
      dates: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],
      values: [2200, 1800, 2100, 2300, 2500, 2700, 2900, 3100, 3300, 3500, 3700, 3900],
      uniqueVisitors: [1200, 1000, 1100, 1300, 1500, 1700, 1900, 2100, 2300, 2500, 2700, 2900]
    };

    // Handle the resize event
    const handleResize = () => {
      chart?.resize();
    };

    const initChart = () => {
      if (!chartRef.value) return;

      chart = echarts.init(chartRef.value);
      updateChart();

      // Add passive event listener
      window.addEventListener("resize", handleResize, { passive: true });
    };

    const updateChart = () => {
      if (!chart) return;

      let data;
      switch (timeRange.value) {
        case "week":
          data = weekData;
          break;
        case "month":
          data = monthData;
          break;
        case "year":
          data = yearData;
          break;
        default:
          data = weekData;
      }

      const option = {
        tooltip: {
          trigger: "axis",
          axisPointer: {
            type: "shadow"
          }
        },
        legend: {
          data: ["总访问量", "独立访客"]
        },
        grid: {
          left: "3%",
          right: "4%",
          bottom: "3%",
          containLabel: true
        },
        xAxis: {
          type: "category",
          data: data.dates
        },
        yAxis: {
          type: "value"
        },
        series: [
          {
            name: "总访问量",
            type: "line",
            smooth: true,
            data: data.values,
            itemStyle: {
              color: "#409EFF"
            },
            areaStyle: {
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                {
                  offset: 0,
                  color: "rgba(64, 158, 255, 0.7)"
                },
                {
                  offset: 1,
                  color: "rgba(64, 158, 255, 0.1)"
                }
              ])
            }
          },
          {
            name: "独立访客",
            type: "line",
            smooth: true,
            data: data.uniqueVisitors,
            itemStyle: {
              color: "#67C23A"
            },
            areaStyle: {
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                {
                  offset: 0,
                  color: "rgba(103, 194, 58, 0.7)"
                },
                {
                  offset: 1,
                  color: "rgba(103, 194, 58, 0.1)"
                }
              ])
            }
          }
        ]
      };

      chart.setOption(option);
    };

    // Clean up event listeners
    onBeforeUnmount(() => {
      window.removeEventListener("resize", handleResize);
      chart?.dispose();
    });

    watch(timeRange, () => {
      updateChart();
    });

    onMounted(() => {
      initChart();
    });

    return {
      chartRef,
      timeRange
    };
  }
});
</script>

<style scoped lang="scss">
.visitors-chart {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .chart-container {
    height: 350px;
    width: 100%;
  }
}
</style>