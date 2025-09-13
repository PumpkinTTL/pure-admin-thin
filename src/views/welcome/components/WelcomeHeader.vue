<template>
  <div class="welcome-header" v-motion :initial="{ opacity: 0, y: 20 }" :enter="{ opacity: 1, y: 0 }">
    <div class="dashboard-container">
      <div class="welcome-section">
        <div class="greeting-area">
          <div class="greeting-header">
            <h1>{{ greeting }}，<span class="admin-name">{{ adminName }}</span></h1>
            <div class="admin-badge">管理员</div>
          </div>
          <p class="motivation-quote">{{ motivationalQuote }}</p>
          <div class="time-info">
            <span class="date">{{ currentDate }}</span>
            <span class="divider"></span>
            <span class="time">{{ currentTime }}</span>
          </div>
        </div>
        <div class="weather">
          <span class="temperature">{{ temperature }}°</span>
          <span class="city">{{ city }}</span>
          <font-awesome-icon icon="sun" class="weather-icon" />
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-box" v-for="(stat, index) in stats" :key="index" :class="stat.class">
          <div class="stat-content">
            <div class="stat-value">{{ stat.value }}</div>
            <div class="stat-label">{{ stat.label }}</div>
          </div>
          <div class="icon-container">
            <font-awesome-icon :icon="stat.icon" />
          </div>
        </div>
      </div>

      <div class="main-content">
        <div class="chart-section">
          <div class="chart-header">
            <h3>访问趋势</h3>
            <div class="period-selector">
              <button class="period-button active">今日</button>
              <button class="period-button">本周</button>
              <button class="period-button">本月</button>
            </div>
          </div>
          <div class="chart-container" ref="chartRef"></div>
        </div>

        <div class="tasks-section">
          <div class="tasks-header">
            <h3>待办事项</h3>
            <button class="add-task-btn">
              <font-awesome-icon icon="plus" />
            </button>
          </div>
          <div class="tasks-list">
            <div class="task-item" v-for="(task, index) in tasks" :key="index">
              <div class="task-checkbox" :class="{ 'checked': task.completed }" @click="toggleTaskStatus(index)">
                <font-awesome-icon v-if="task.completed" icon="check" class="check-icon" />
              </div>
              <div class="task-content" :class="{ 'completed': task.completed }">{{ task.content }}</div>
              <div class="task-date">{{ task.date }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="action-grid">
        <button v-for="(action, index) in actions" :key="index" class="action-button" :class="action.type">
          <font-awesome-icon :icon="action.icon" class="action-icon" />
          <span>{{ action.label }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, onBeforeUnmount, reactive } from 'vue';
import * as echarts from 'echarts';

export default defineComponent({
  name: "WelcomeHeader",
  setup() {
    // 管理员姓名
    const adminName = ref('李明');

    // 每日激励语
    const motivationalQuotes = [
      "今天又是充满可能性的一天！",
      "把每一个挑战都当作成长的机会。",
      "高效的管理来自于良好的规划。",
      "你的努力正在创造不凡的价值。",
      "专注于重要的事，而不是紧急的事。"
    ];
    const motivationalQuote = ref(motivationalQuotes[Math.floor(Math.random() * motivationalQuotes.length)]);

    const currentDate = computed(() => {
      const date = new Date();
      return date.toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      });
    });

    const currentTime = ref('');
    let timer: ReturnType<typeof setInterval> | undefined;

    const updateTime = () => {
      const now = new Date();
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      currentTime.value = `${hours}:${minutes}`;
    };

    const greeting = computed(() => {
      const hour = new Date().getHours();
      if (hour < 6) return "凌晨好";
      if (hour < 9) return "早上好";
      if (hour < 12) return "上午好";
      if (hour < 14) return "中午好";
      if (hour < 17) return "下午好";
      if (hour < 19) return "傍晚好";
      return "晚上好";
    });

    // 统计数据
    const stats = reactive([
      {
        value: "18",
        label: "待审文章",
        icon: "file-alt",
        class: "primary"
      },
      {
        value: "1,438",
        label: "今日访问",
        icon: "chart-line",
        class: "success"
      },
      {
        value: "28",
        label: "新增评论",
        icon: "comment",
        class: "warning"
      },
      {
        value: "6",
        label: "待办任务",
        icon: "tasks",
        class: "info"
      }
    ]);

    // 任务数据
    const tasks = reactive([
      {
        content: "完成博客首页设计稿",
        date: "今天 10:00",
        completed: true
      },
      {
        content: "内容审核：科技频道文章",
        date: "今天 13:30",
        completed: false
      },
      {
        content: "回复用户反馈邮件",
        date: "今天 15:00",
        completed: false
      },
      {
        content: "博客系统性能优化",
        date: "明天 10:00",
        completed: false
      }
    ]);

    // 操作按钮
    const actions = reactive([
      {
        label: "写文章",
        icon: "edit",
        type: "primary"
      },
      {
        label: "数据分析",
        icon: "chart-bar",
        type: "success"
      },
      {
        label: "评论管理",
        icon: "comments",
        type: "warning"
      },
      {
        label: "用户管理",
        icon: "users",
        type: "danger"
      },
      {
        label: "系统设置",
        icon: "cog",
        type: "info"
      }
    ]);

    // 天气数据
    const temperature = ref('24');
    const city = ref('北京');

    // 任务状态切换
    const toggleTaskStatus = (index: number) => {
      tasks[index].completed = !tasks[index].completed;
    };

    // 图表相关
    const chartRef = ref<HTMLElement | null>(null);
    let chart: echarts.ECharts | null = null;

    const initChart = () => {
      if (!chartRef.value) return;

      chart = echarts.init(chartRef.value);

      const option = {
        grid: {
          top: '5%',
          right: '3%',
          bottom: '5%',
          left: '3%',
          containLabel: true
        },
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'none'
          },
          backgroundColor: 'rgba(255, 255, 255, 0.8)',
          borderColor: '#eee',
          borderWidth: 1,
          textStyle: {
            color: '#333'
          }
        },
        xAxis: {
          type: 'category',
          data: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '现在'],
          axisTick: {
            show: false
          },
          axisLine: {
            show: false
          },
          axisLabel: {
            color: '#999',
            fontSize: 12
          }
        },
        yAxis: {
          type: 'value',
          splitLine: {
            lineStyle: {
              color: '#f5f5f5'
            }
          },
          axisLabel: {
            color: '#999',
            fontSize: 12
          },
          axisTick: {
            show: false
          },
          axisLine: {
            show: false
          }
        },
        series: [
          {
            type: 'line',
            data: [120, 132, 301, 534, 890, 530, 410],
            smooth: true,
            symbol: 'circle',
            symbolSize: 8,
            itemStyle: {
              color: '#1890ff'
            },
            lineStyle: {
              width: 3,
              color: '#1890ff'
            },
            areaStyle: {
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(24, 144, 255, 0.2)' },
                { offset: 1, color: 'rgba(24, 144, 255, 0)' }
              ])
            }
          }
        ]
      };

      chart.setOption(option);

      window.addEventListener('resize', handleResize, { passive: true });
    };

    const handleResize = () => {
      chart?.resize();
    };

    onMounted(() => {
      updateTime();
      timer = setInterval(updateTime, 60000); // 每分钟更新一次
      initChart();
    });

    onBeforeUnmount(() => {
      if (timer) {
        clearInterval(timer);
      }
      window.removeEventListener('resize', handleResize);
      chart?.dispose();
    });

    return {
      adminName,
      motivationalQuote,
      currentDate,
      currentTime,
      greeting,
      temperature,
      city,
      stats,
      tasks,
      actions,
      chartRef,
      toggleTaskStatus
    };
  }
});
</script>

<style scoped lang="scss">
.welcome-header {
  color: #333;

  .dashboard-container {
    background: #fff;
    border-radius: 8px;
    padding: 24px;
    display: grid;
    grid-template-columns: 1fr;
    grid-template-rows: auto;
    gap: 24px;
    max-width: 100%;
  }

  .welcome-section {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .greeting-area {
      .greeting-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;

        h1 {
          font-size: 24px;
          font-weight: 600;
          margin: 0;
          color: #111;

          .admin-name {
            color: #1890ff;
            position: relative;

            &::after {
              content: '';
              position: absolute;
              bottom: -2px;
              left: 0;
              width: 100%;
              height: 2px;
              background: #1890ff;
              transform: scaleX(0);
              transition: transform 0.3s;
              transform-origin: right;
            }

            &:hover::after {
              transform: scaleX(1);
              transform-origin: left;
            }
          }
        }

        .admin-badge {
          background: linear-gradient(to right, #40a9ff, #1890ff);
          color: white;
          padding: 2px 8px;
          border-radius: 12px;
          font-size: 12px;
          font-weight: 500;
        }
      }

      .motivation-quote {
        color: #666;
        font-size: 14px;
        margin-top: 4px;
        margin-bottom: 8px;
        font-weight: normal;
        /* 确保不是斜体 */
      }

      .time-info {
        margin-top: 6px;
        color: #666;
        font-size: 14px;
        display: flex;
        align-items: center;

        .divider {
          display: inline-block;
          width: 4px;
          height: 4px;
          border-radius: 50%;
          background-color: #ccc;
          margin: 0 8px;
        }
      }
    }

    .weather {
      background-color: #f9f9f9;
      border-radius: 6px;
      padding: 10px 16px;
      display: flex;
      align-items: center;
      gap: 8px;

      .temperature {
        font-size: 20px;
        font-weight: 600;
      }

      .city {
        color: #666;
        font-size: 14px;
      }

      .weather-icon {
        font-size: 20px;
        color: #f8cb00;
        margin-left: 6px;
      }
    }
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;

    .stat-box {
      border-radius: 8px;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      overflow: hidden;
      color: #fff;

      &.primary {
        background-color: #1890ff;
      }

      &.success {
        background-color: #52c41a;
      }

      &.warning {
        background-color: #faad14;
      }

      &.info {
        background-color: #722ed1;
      }

      .stat-content {
        position: relative;
        z-index: 1;

        .stat-value {
          font-size: 28px;
          font-weight: 600;
          line-height: 1;
          margin-bottom: 6px;
        }

        .stat-label {
          font-size: 14px;
          opacity: 0.9;
        }
      }

      .icon-container {
        font-size: 24px;
        opacity: 0.8;
        position: relative;
        z-index: 1;
      }
    }
  }

  .main-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;

    .chart-section {
      background-color: #fff;
      border: 1px solid #f0f0f0;
      border-radius: 8px;
      padding: 16px;

      .chart-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;

        h3 {
          font-size: 16px;
          font-weight: 600;
          margin: 0;
        }

        .period-selector {
          display: flex;
          gap: 8px;

          .period-button {
            background: none;
            border: none;
            padding: 4px 12px;
            font-size: 12px;
            color: #666;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;

            &:hover {
              background-color: #f5f5f5;
            }

            &.active {
              background-color: #e6f7ff;
              color: #1890ff;
            }
          }
        }
      }

      .chart-container {
        height: 240px;
      }
    }

    .tasks-section {
      background-color: #fff;
      border: 1px solid #f0f0f0;
      border-radius: 8px;
      padding: 16px;

      .tasks-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;

        h3 {
          font-size: 16px;
          font-weight: 600;
          margin: 0;
        }

        .add-task-btn {
          width: 24px;
          height: 24px;
          border-radius: 4px;
          background-color: #f5f5f5;
          border: none;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #666;
          cursor: pointer;
          transition: all 0.2s;

          &:hover {
            background-color: #1890ff;
            color: #fff;
          }
        }
      }

      .tasks-list {
        .task-item {
          display: flex;
          align-items: center;
          padding: 12px 0;
          border-bottom: 1px solid #f5f5f5;

          &:last-child {
            border-bottom: none;
          }

          .task-checkbox {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;

            &.checked {
              background-color: #1890ff;
              border-color: #1890ff;
            }

            .check-icon {
              color: #fff;
              font-size: 10px;
            }
          }

          .task-content {
            flex: 1;
            font-size: 14px;

            &.completed {
              text-decoration: line-through;
              color: #999;
            }
          }

          .task-date {
            color: #999;
            font-size: 12px;
            margin-left: 12px;
          }
        }
      }
    }
  }

  .action-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;

    .action-button {
      background-color: #fff;
      border: 1px solid #f0f0f0;
      border-radius: 8px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s;
      cursor: pointer;

      &:hover {
        transform: translateY(-2px);
      }

      &.primary .action-icon {
        color: #1890ff;
      }

      &.success .action-icon {
        color: #52c41a;
      }

      &.warning .action-icon {
        color: #faad14;
      }

      &.danger .action-icon {
        color: #f5222d;
      }

      &.info .action-icon {
        color: #722ed1;
      }

      .action-icon {
        font-size: 20px;
      }

      span {
        font-size: 14px;
      }

    }

  }

  /* 响应式设计 */
  @media (max-width: 1200px) {
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .main-content {
      grid-template-columns: 1fr;
    }

    .action-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 768px) {
    .welcome-section {
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;

      .weather {
        width: 100%;
      }
    }

    .stats-grid {
      grid-template-columns: 1fr;
    }

    .action-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
}
</style>
}
