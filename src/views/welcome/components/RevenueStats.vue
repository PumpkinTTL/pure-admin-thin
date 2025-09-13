<template>
  <div class="revenue-stats" v-motion :initial="{ opacity: 0, y: 20 }" :enter="{ opacity: 1, y: 0 }">
    <!-- 标题栏 -->
    <div class="header-section">
      <h2>网站收益数据</h2>
      <div class="filter-section">
        <span>时间范围:</span>
        <el-radio-group v-model="timeRange" size="small">
          <el-radio-button label="day">今日</el-radio-button>
          <el-radio-button label="week">本周</el-radio-button>
          <el-radio-button label="month">本月</el-radio-button>
        </el-radio-group>
      </div>
    </div>

    <!-- 收益卡片 -->
    <div class="stat-cards">
      <div class="stat-card primary">
        <div class="card-icon">
          <font-awesome-icon :icon="['fas', 'coins']" />
        </div>
        <div class="card-content">
          <div class="card-value">¥32,648</div>
          <div class="card-title">总收入</div>
          <div class="card-trend up">
            <font-awesome-icon :icon="['fas', 'arrow-up']" />
            <span>12.5%</span>
          </div>
        </div>
      </div>

      <div class="stat-card info">
        <div class="card-icon">
          <font-awesome-icon :icon="['fas', 'user-friends']" />
        </div>
        <div class="card-content">
          <div class="card-value">1,842</div>
          <div class="card-title">订阅用户</div>
          <div class="card-trend up">
            <font-awesome-icon :icon="['fas', 'arrow-up']" />
            <span>8.2%</span>
          </div>
        </div>
      </div>

      <div class="stat-card success">
        <div class="card-icon">
          <font-awesome-icon :icon="['fas', 'money-bill-wave']" />
        </div>
        <div class="card-content">
          <div class="card-value">¥15,245</div>
          <div class="card-title">广告收益</div>
          <div class="card-trend down">
            <font-awesome-icon :icon="['fas', 'arrow-down']" />
            <span>3.4%</span>
          </div>
        </div>
      </div>

      <div class="stat-card warning">
        <div class="card-icon">
          <font-awesome-icon :icon="['fas', 'chart-line']" />
        </div>
        <div class="card-content">
          <div class="card-value">8.24%</div>
          <div class="card-title">转化率</div>
          <div class="card-trend up">
            <font-awesome-icon :icon="['fas', 'arrow-up']" />
            <span>5.7%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 收益明细和分布图表 -->
    <div class="chart-container">
      <div class="chart-section">
        <div class="chart-header">
          <h3>收入趋势</h3>
          <el-select v-model="chartPeriod" size="small" placeholder="选择时间段">
            <el-option label="月度收入" value="monthly" />
            <el-option label="年度收入" value="yearly" />
            <el-option label="平均客户价值" value="average" />
          </el-select>
        </div>
        <div class="chart-wrapper" ref="trendChartRef"></div>
      </div>

      <div class="chart-section">
        <div class="chart-header">
          <h3>收入来源</h3>
          <span class="period-label">{{ timeRange === 'day' ? '今日' : timeRange === 'week' ? '本周' : '本月' }}</span>
        </div>
        <div class="chart-wrapper" ref="pieChartRef"></div>
      </div>
    </div>

    <!-- 收益明细表格 -->
    <div class="table-section">
      <div class="table-header">
        <h3>收益明细</h3>
        <el-button size="small" type="primary">导出数据</el-button>
      </div>
      <el-table :data="revenueDetails" style="width: 100%" size="small" border>
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="type" label="类型" width="120" />
        <el-table-column prop="source" label="来源" min-width="150" />
        <el-table-column prop="amount" label="金额" width="120" align="right" />
        <el-table-column prop="status" label="状态" width="120">
          <template #default="scope">
            <el-tag :type="scope.row.status === '已结算' ? 'success' : scope.row.status === '处理中' ? 'warning' : 'info'"
              size="small">
              {{ scope.row.status }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onBeforeUnmount } from 'vue';
import * as echarts from 'echarts';

export default defineComponent({
  name: "RevenueStats",
  setup() {
    const timeRange = ref('week');
    const chartPeriod = ref('monthly');

    // 图表引用
    const trendChartRef = ref<HTMLElement | null>(null);
    const pieChartRef = ref<HTMLElement | null>(null);

    // 图表实例
    let trendChart: echarts.ECharts | null = null;
    let pieChart: echarts.ECharts | null = null;

    // 收益明细数据
    const revenueDetails = ref([
      { date: '2023-10-15', type: '会员订阅', source: '月度会员', amount: '¥2,980.00', status: '已结算' },
      { date: '2023-10-15', type: '广告收入', source: '谷歌广告联盟', amount: '¥1,254.30', status: '已结算' },
      { date: '2023-10-14', type: '会员订阅', source: '年度会员', amount: '¥4,580.00', status: '已结算' },
      { date: '2023-10-14', type: '课程售卖', source: 'Vue3高级教程', amount: '¥1,680.00', status: '处理中' },
      { date: '2023-10-13', type: '广告收入', source: '直接投放', amount: '¥3,200.00', status: '已结算' }
    ]);

    // 渲染趋势图表
    const renderTrendChart = () => {
      if (!trendChartRef.value) return;

      trendChart = echarts.init(trendChartRef.value);

      // 周数据
      const weekData = {
        xAxis: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
        series: [5200, 4800, 6100, 7500, 8200, 9500, 10200]
      };

      // 月数据
      const monthData = {
        xAxis: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
        series: [18000, 19200, 21500, 22000, 24500, 26800, 28000, 29500, 31000, 32500, 34000, 35800]
      };

      // 使用何种数据
      const data = timeRange.value === 'month' ? monthData : weekData;

      const option = {
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'shadow'
          }
        },
        grid: {
          left: '3%',
          right: '4%',
          bottom: '3%',
          top: '8%',
          containLabel: true
        },
        xAxis: {
          type: 'category',
          data: data.xAxis,
          axisLine: {
            lineStyle: {
              color: '#ddd'
            }
          },
          axisLabel: {
            color: '#666',
            fontSize: 12
          },
          axisTick: { show: false }
        },
        yAxis: {
          type: 'value',
          splitLine: {
            lineStyle: {
              color: '#f5f5f5'
            }
          },
          axisLabel: {
            color: '#666',
            fontSize: 12,
            formatter: function (value) {
              return value >= 10000 ? (value / 10000) + '万' : value;
            }
          }
        },
        series: [
          {
            name: '收入',
            type: 'bar',
            barWidth: '60%',
            data: data.series,
            itemStyle: {
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: '#FFD700' },
                { offset: 1, color: '#FFA500' }
              ])
            }
          }
        ]
      };

      trendChart.setOption(option);
    };

    // 渲染饼图表
    const renderPieChart = () => {
      if (!pieChartRef.value) return;

      pieChart = echarts.init(pieChartRef.value);

      const option = {
        tooltip: {
          trigger: 'item',
          formatter: '{b}: {c}元 ({d}%)'
        },
        legend: {
          orient: 'vertical',
          right: 10,
          top: 'center',
          itemWidth: 10,
          itemHeight: 10,
          textStyle: {
            fontSize: 12
          }
        },
        series: [
          {
            name: '收入来源',
            type: 'pie',
            radius: ['50%', '70%'],
            center: ['40%', '50%'],
            avoidLabelOverlap: false,
            itemStyle: {
              borderRadius: 6,
              borderColor: '#fff',
              borderWidth: 2
            },
            label: {
              show: false
            },
            emphasis: {
              label: {
                show: true,
                fontSize: 14,
                fontWeight: 'bold'
              }
            },
            labelLine: {
              show: false
            },
            data: [
              { value: 16598, name: '会员订阅', itemStyle: { color: '#FFD700' } },
              { value: 12473, name: '广告收入', itemStyle: { color: '#59A14F' } },
              { value: 3127, name: '课程售卖', itemStyle: { color: '#F28E2C' } },
              { value: 450, name: '打赏收入', itemStyle: { color: '#E15759' } }
            ]
          }
        ]
      };

      pieChart.setOption(option);
    };

    // 刷新所有图表
    const refreshCharts = () => {
      renderTrendChart();
      renderPieChart();
    };

    // 监听窗口大小变化
    const handleResize = () => {
      trendChart?.resize();
      pieChart?.resize();
    };

    onMounted(() => {
      refreshCharts();
      window.addEventListener('resize', handleResize, { passive: true });
    });

    onBeforeUnmount(() => {
      window.removeEventListener('resize', handleResize);
      trendChart?.dispose();
      pieChart?.dispose();
    });

    return {
      timeRange,
      chartPeriod,
      revenueDetails,
      trendChartRef,
      pieChartRef,
    };
  }
});
</script>

<style scoped lang="scss">
.revenue-stats {
  background-color: var(--el-bg-color);
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  width: 100% !important;
  min-height: 800px;
  display: block;
  overflow: auto;
  padding: 20px;
  box-sizing: border-box;

  @media (max-width: 768px) {
    padding: 15px;
  }

  .header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
    width: 100%;

    h2 {
      font-size: 18px;
      font-weight: 600;
      margin: 0;
      color: var(--el-text-color-primary);
    }

    .filter-section {
      display: flex;
      align-items: center;
      gap: 10px;

      span {
        color: var(--el-text-color-secondary);
        font-size: 14px;
      }
    }

    @media (max-width: 576px) {
      flex-direction: column;
      align-items: flex-start;
    }
  }

  .stat-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
    width: 100%;

    @media (max-width: 1200px) {
      grid-template-columns: repeat(2, 1fr);
    }

    @media (max-width: 576px) {
      grid-template-columns: 1fr;
    }

    .stat-card {
      background-color: var(--el-bg-color);
      border-radius: 8px;
      padding: 16px;
      position: relative;
      display: flex;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s, box-shadow 0.3s;
      width: 100%;

      &:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      }

      .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;

        .svg-inline--fa {
          font-size: 20px;
          color: white;
        }
      }

      .card-content {
        flex: 1;

        .card-value {
          font-size: 22px;
          font-weight: 600;
          color: var(--el-text-color-primary);
          margin-bottom: 4px;
        }

        .card-title {
          font-size: 14px;
          color: var(--el-text-color-secondary);
          margin-bottom: 4px;
        }

        .card-trend {
          font-size: 12px;
          display: flex;
          align-items: center;
          gap: 4px;

          &.up {
            color: #67C23A;
          }

          &.down {
            color: #F56C6C;
          }
        }
      }

      &.primary {
        border-left: 4px solid #FFD700;

        .card-icon {
          background: linear-gradient(135deg, #FFD700, #FFA500);
        }
      }

      &.info {
        border-left: 4px solid #409EFF;

        .card-icon {
          background: linear-gradient(135deg, #409EFF, #1890FF);
        }
      }

      &.success {
        border-left: 4px solid #67C23A;

        .card-icon {
          background: linear-gradient(135deg, #67C23A, #4CAF50);
        }
      }

      &.warning {
        border-left: 4px solid #E6A23C;

        .card-icon {
          background: linear-gradient(135deg, #E6A23C, #F39C12);
        }
      }
    }
  }

  .chart-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
    width: 100%;

    @media (max-width: 991px) {
      grid-template-columns: 1fr;
    }

    .chart-section {
      background-color: var(--el-bg-color);
      border-radius: 8px;
      padding: 16px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      width: 100%;

      .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;

        h3 {
          font-size: 16px;
          font-weight: 500;
          margin: 0;
          color: var(--el-text-color-primary);
        }

        .period-label {
          color: var(--el-text-color-secondary);
          font-size: 14px;
        }
      }

      .chart-wrapper {
        height: 300px;

        @media (max-width: 576px) {
          height: 250px;
        }
      }
    }
  }

  .table-section {
    width: 100%;
    overflow-x: auto;

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;

      h3 {
        font-size: 16px;
        font-weight: 500;
        margin: 0;
        color: var(--el-text-color-primary);
      }
    }
  }
}
</style>