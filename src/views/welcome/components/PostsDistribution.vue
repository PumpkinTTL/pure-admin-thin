<template>
  <div class="posts-distribution" v-motion :initial="{ opacity: 0, y: 50 }"
    :enter="{ opacity: 1, y: 0, transition: { delay: 300 } }">
    <el-card shadow="hover" class="animate__animated animate__fadeIn">
      <template #header>
        <div class="card-header">
          <span>文章分类统计</span>
        </div>
      </template>
      <div ref="chartRef" class="chart-container"></div>
    </el-card>
  </div>
</template>

<script lang="ts">
import { ref, onMounted, defineComponent, onBeforeUnmount } from "vue";
import * as echarts from "echarts";

export default defineComponent({
  name: "PostsDistribution",
  setup() {
    const chartRef = ref<HTMLElement | null>(null);
    let chart: echarts.ECharts | null = null;

    const categoryData = [
      { value: 40, name: '技术博客' },
      { value: 25, name: '生活随笔' },
      { value: 15, name: '项目分享' },
      { value: 10, name: '教程' },
      { value: 10, name: '其他' }
    ];

    // Handle the resize event
    const handleResize = () => {
      chart?.resize();
    };

    const initChart = () => {
      if (!chartRef.value) return;

      chart = echarts.init(chartRef.value);

      const option = {
        tooltip: {
          trigger: 'item',
          formatter: '{a} <br/>{b}: {c} ({d}%)'
        },
        legend: {
          orient: 'vertical',
          left: 10,
          data: categoryData.map(item => item.name)
        },
        series: [
          {
            name: '文章分类',
            type: 'pie',
            radius: ['50%', '70%'],
            avoidLabelOverlap: false,
            itemStyle: {
              borderRadius: 10,
              borderColor: '#fff',
              borderWidth: 2
            },
            label: {
              show: false,
              position: 'center'
            },
            emphasis: {
              label: {
                show: true,
                fontSize: '18',
                fontWeight: 'bold'
              }
            },
            labelLine: {
              show: false
            },
            data: categoryData
          }
        ],
        color: ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399']
      };

      chart.setOption(option);

      // Add passive event listener
      window.addEventListener("resize", handleResize, { passive: true });
    };

    // Clean up event listeners
    onBeforeUnmount(() => {
      window.removeEventListener("resize", handleResize);
      chart?.dispose();
    });

    onMounted(() => {
      initChart();
    });

    return {
      chartRef
    };
  }
});
</script>

<style scoped lang="scss">
.posts-distribution {
  .chart-container {
    height: 350px;
    width: 100%;
  }
}
</style>