<template>
  <div class="blog-stats" v-motion :initial="{ opacity: 0, y: 20 }"
    :enter="{ opacity: 1, y: 0, transition: { delay: 200 } }">
    <div class="dashboard-container">
      <!-- 博客数据分析区域 -->
      <div class="stats-section">
        <div class="section-header">
          <h1>博客数据分析</h1>
          <div class="date-selector">
            <span class="label">时间范围:</span>
            <el-radio-group v-model="timeRange" size="small">
              <el-radio-button label="day">今日</el-radio-button>
              <el-radio-button label="week">本周</el-radio-button>
              <el-radio-button label="month">本月</el-radio-button>
            </el-radio-group>
          </div>
        </div>

        <div class="category-stats">
          <el-row :gutter="20">
            <!-- 分类占比图表 -->
            <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>文章分类占比</h3>
                  <span class="total-count">总计: {{ totalArticles }} 篇</span>
                </div>
                <div class="chart-container" ref="categoryChartRef"></div>
              </div>
            </el-col>

            <!-- 热门标签 -->
            <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>热门标签</h3>
                  <span class="view-all">查看全部</span>
                </div>
                <div class="tags-list">
                  <div class="tag-item" v-for="(tag, index) in topPopularTags" :key="tag.name">
                    <div class="tag-rank">{{ index + 1 }}</div>
                    <div class="tag-info">
                      <div class="tag-name">{{ tag.name }}</div>
                      <div class="tag-bar-container">
                        <div class="tag-bar" :style="{ width: `${tag.percentage}%`, backgroundColor: tag.color }"></div>
                      </div>
                    </div>
                    <div class="tag-count">{{ tag.count }}</div>
                  </div>
                </div>
              </div>
            </el-col>

            <!-- 发布趋势 -->
            <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
              <div class="chart-card trend-card">
                <div class="chart-header">
                  <h3>发布趋势</h3>
                  <div class="trend-type-selector">
                    <span :class="['trend-type', { active: trendType === 'articles' }]"
                      @click="changeTrendType('articles')">文章</span>
                    <span :class="['trend-type', { active: trendType === 'comments' }]"
                      @click="changeTrendType('comments')">评论</span>
                  </div>
                </div>
                <div class="trend-summary">
                  <div class="trend-value">
                    {{ trendType === 'articles' ? '152' : '384' }}
                    <span class="trend-unit">{{ trendType === 'articles' ? '篇' : '条' }}</span>
                  </div>
                  <div class="trend-change" :class="{ 'is-increase': trendChange > 0 }">
                    <font-awesome-icon :icon="trendChange > 0 ? ['fas', 'arrow-up'] : ['fas', 'arrow-down']" />
                    {{ Math.abs(trendChange) }}% 较上期
                  </div>
                </div>
                <div class="chart-container" ref="trendChartRef"></div>
              </div>
            </el-col>
          </el-row>
        </div>
      </div>

      <!-- 最新互动 -->
      <div class="interaction-section">
        <div class="section-header">
          <h2>最新互动</h2>
          <el-tabs v-model="activeTab" class="interaction-tabs" @tab-change="handleTabChange">
            <el-tab-pane label="最新评论" name="comments"></el-tab-pane>
            <el-tab-pane label="收到的赞" name="likes"></el-tab-pane>
          </el-tabs>
        </div>

        <!-- 评论列表 -->
        <div v-if="activeTab === 'comments'" class="comment-list">
          <div class="table-responsive">
            <el-table :data="recentComments.slice(0, 5)" style="width: 100%" size="small">
              <el-table-column width="60">
                <template #default="{ row }">
                  <el-avatar :size="40" :src="row.avatar">
                    <span v-if="!row.avatar">{{ row.author.substring(0, 1) }}</span>
                  </el-avatar>
                </template>
              </el-table-column>
              <el-table-column prop="author" label="评论者" width="120" />
              <el-table-column prop="content" label="评论内容" min-width="200" show-overflow-tooltip />
              <el-table-column prop="articleTitle" label="文章标题" min-width="150" show-overflow-tooltip />
              <el-table-column prop="time" label="时间" width="120" />
              <el-table-column width="100" align="right">
                <template #default>
                  <el-button size="small" type="text">回复</el-button>
                  <el-button size="small" type="text">查看</el-button>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>

        <!-- 点赞列表 -->
        <div v-else class="likes-list">
          <div class="table-responsive">
            <el-table :data="recentLikes.slice(0, 5)" style="width: 100%" size="small">
              <el-table-column width="60">
                <template #default="{ row }">
                  <el-avatar :size="40" :src="row.avatar">
                    <span v-if="!row.avatar">{{ row.username.substring(0, 1) }}</span>
                  </el-avatar>
                </template>
              </el-table-column>
              <el-table-column prop="username" label="用户" width="120" />
              <el-table-column prop="articleTitle" label="文章标题" min-width="200" show-overflow-tooltip />
              <el-table-column prop="time" label="时间" width="120" />
              <el-table-column width="100" align="right">
                <template #default>
                  <el-button size="small" type="text">查看</el-button>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, reactive } from 'vue';
import * as echarts from 'echarts';

export default defineComponent({
  name: "BlogStats",
  setup() {
    // 时间范围
    const timeRange = ref('week');

    // 总文章数
    const totalArticles = ref(386);

    // 分类图表引用
    const categoryChartRef = ref(null);

    // 趋势图表引用
    const trendChartRef = ref(null);

    // 趋势类型
    const trendType = ref('articles');
    const trendChange = ref(12.5);

    // 热门标签
    const topPopularTags = reactive([
      { name: '前端开发', count: 45, percentage: 90, color: '#3498db' },
      { name: 'Vue3', count: 38, percentage: 76, color: '#2ecc71' },
      { name: 'TypeScript', count: 32, percentage: 64, color: '#f39c12' },
      { name: '性能优化', count: 28, percentage: 56, color: '#9b59b6' },
      { name: '响应式设计', count: 23, percentage: 46, color: '#e74c3c' },
    ]);

    // 交互区域活动标签
    const activeTab = ref('comments');

    // 最近评论
    const recentComments = reactive([
      {
        author: '张小明',
        avatar: '',
        content: '这篇文章写得非常好，对我帮助很大！',
        articleTitle: '2023年前端开发趋势展望',
        time: '10分钟前'
      },
      {
        author: '李梅',
        avatar: '',
        content: '有一处代码示例似乎有误，第三行应该是useState而不是useStatus',
        articleTitle: 'React Hooks完全指南',
        time: '25分钟前'
      },
      {
        author: '王大锤',
        avatar: '',
        content: '期待更多相关内容，已经关注了',
        articleTitle: 'Vue3性能优化实战',
        time: '1小时前'
      },
      {
        author: '赵四',
        avatar: '',
        content: '文章中提到的工具我试了，确实比之前用的好用多了',
        articleTitle: '10个提高开发效率的VSCode插件',
        time: '2小时前'
      },
      {
        author: '刘能',
        avatar: '',
        content: '这个解决方案太棒了！解决了我困扰很久的问题',
        articleTitle: 'CSS Grid布局详解',
        time: '3小时前'
      }
    ]);

    // 最近点赞
    const recentLikes = reactive([
      {
        username: '张伟',
        avatar: '',
        articleTitle: '2023年前端开发趋势展望',
        time: '5分钟前'
      },
      {
        username: '王芳',
        avatar: '',
        articleTitle: 'Vue3性能优化实战',
        time: '15分钟前'
      },
      {
        username: '李娜',
        avatar: '',
        articleTitle: 'TypeScript高级类型应用',
        time: '30分钟前'
      },
      {
        username: '赵明',
        avatar: '',
        articleTitle: 'CSS Grid布局详解',
        time: '1小时前'
      },
      {
        username: '周杰',
        avatar: '',
        articleTitle: 'Web Components实战指南',
        time: '2小时前'
      }
    ]);

    // 处理标签页切换
    const handleTabChange = (tab) => {
      activeTab.value = tab;
    };

    // 切换趋势类型
    const changeTrendType = (type) => {
      trendType.value = type;
      // 简化模拟，实际应该根据类型重新渲染图表
      trendChange.value = type === 'articles' ? 12.5 : -5.2;
      renderTrendChart();
    };

    // 渲染分类图表
    const renderCategoryChart = () => {
      if (!categoryChartRef.value) return;

      const chart = echarts.init(categoryChartRef.value);
      const option = {
        tooltip: {
          trigger: 'item',
          formatter: '{b}: {c} ({d}%)'
        },
        color: ['#5470c6', '#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272'],
        series: [
          {
            name: '文章分类',
            type: 'pie',
            radius: ['40%', '70%'],
            avoidLabelOverlap: false,
            itemStyle: {
              borderRadius: 10,
              borderColor: '#fff',
              borderWidth: 2
            },
            label: {
              show: false
            },
            labelLine: {
              show: false
            },
            data: [
              { value: 128, name: '前端开发' },
              { value: 86, name: '后端技术' },
              { value: 72, name: '移动应用' },
              { value: 53, name: '数据库' },
              { value: 34, name: '服务器运维' },
              { value: 13, name: '其他' }
            ]
          }
        ]
      };

      chart.setOption(option);
    };

    // 渲染趋势图表
    const renderTrendChart = () => {
      if (!trendChartRef.value) return;

      const chart = echarts.init(trendChartRef.value);
      const option = {
        grid: {
          top: 20,
          right: 10,
          bottom: 20,
          left: 10,
          containLabel: true
        },
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'none'
          }
        },
        xAxis: {
          type: 'category',
          data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
          axisLine: {
            show: false
          },
          axisTick: {
            show: false
          },
          axisLabel: {
            color: '#999',
            fontSize: 12
          }
        },
        yAxis: {
          type: 'value',
          show: false
        },
        series: [
          {
            data: trendType.value === 'articles' ?
              [15, 12, 19, 23, 28, 35, 20] :
              [35, 42, 58, 62, 45, 67, 75],
            type: 'line',
            smooth: true,
            showSymbol: false,
            lineStyle: {
              width: 3,
              color: trendType.value === 'articles' ? '#409eff' : '#67c23a'
            },
            areaStyle: {
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                {
                  offset: 0,
                  color: trendType.value === 'articles' ? 'rgba(64, 158, 255, 0.3)' : 'rgba(103, 194, 58, 0.3)'
                },
                {
                  offset: 1,
                  color: 'rgba(255, 255, 255, 0)'
                }
              ])
            }
          }
        ]
      };

      chart.setOption(option);
    };

    onMounted(() => {
      renderCategoryChart();
      renderTrendChart();
    });

    return {
      timeRange,
      totalArticles,
      categoryChartRef,
      trendChartRef,
      trendType,
      trendChange,
      topPopularTags,
      activeTab,
      recentComments,
      recentLikes,
      handleTabChange,
      changeTrendType
    };
  }
});
</script>

<style scoped lang="scss">
.blog-stats {
  width: 100%;
  background-color: var(--el-bg-color);
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;

  .dashboard-container {
    padding: 20px;

    @media (max-width: 768px) {
      padding: 15px;
    }
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;

    @media (max-width: 768px) {
      flex-direction: column;
      align-items: flex-start;
    }

    h1,
    h2 {
      font-size: 18px;
      font-weight: 600;
      margin: 0;
      color: var(--el-text-color-primary);
    }

    .date-selector {
      display: flex;
      align-items: center;
      gap: 8px;

      .label {
        font-size: 14px;
        color: var(--el-text-color-secondary);
      }
    }
  }

  .chart-card {
    background-color: var(--el-bg-color);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.04);
    height: 350px;
    display: flex;
    flex-direction: column;

    @media (max-width: 768px) {
      height: 300px;
      padding: 12px;
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;

      h3 {
        font-size: 16px;
        font-weight: 500;
        margin: 0;
        color: var(--el-text-color-primary);
      }

      .total-count,
      .view-all {
        font-size: 13px;
        color: var(--el-text-color-secondary);
      }

      .view-all {
        cursor: pointer;
        color: var(--el-color-primary);
      }

      .trend-type-selector {
        display: flex;
        gap: 10px;

        .trend-type {
          cursor: pointer;
          font-size: 13px;
          color: var(--el-text-color-secondary);
          padding: 2px 8px;
          border-radius: 10px;

          &.active {
            color: var(--el-color-primary);
            background-color: var(--el-color-primary-light-9);
          }
        }
      }
    }

    .chart-container {
      flex: 1;
      width: 100%;
    }

    &.trend-card {
      .trend-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;

        .trend-value {
          font-size: 24px;
          font-weight: 600;
          color: var(--el-text-color-primary);

          .trend-unit {
            font-size: 14px;
            font-weight: normal;
            margin-left: 2px;
            color: var(--el-text-color-secondary);
          }
        }

        .trend-change {
          font-size: 14px;
          color: #f56c6c;
          display: flex;
          align-items: center;
          gap: 5px;

          &.is-increase {
            color: #67c23a;
          }
        }
      }
    }

    .tags-list {
      flex: 1;
      overflow-y: auto;
      padding-right: 5px;

      .tag-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid var(--el-border-color-lighter);

        &:last-child {
          border-bottom: none;
        }

        .tag-rank {
          width: 24px;
          height: 24px;
          border-radius: 50%;
          background-color: #f5f7fa;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 12px;
          font-weight: bold;
          margin-right: 10px;
          color: var(--el-text-color-secondary);
        }

        .tag-info {
          flex: 1;
          margin-right: 10px;

          .tag-name {
            font-size: 14px;
            margin-bottom: 4px;
            color: var(--el-text-color-primary);
          }

          .tag-bar-container {
            height: 6px;
            background-color: #f5f7fa;
            border-radius: 3px;
            overflow: hidden;

            .tag-bar {
              height: 100%;
              border-radius: 3px;
            }
          }
        }

        .tag-count {
          font-size: 14px;
          font-weight: 500;
          color: var(--el-text-color-secondary);
        }
      }
    }
  }

  .interaction-section {
    margin-top: 20px;
    background-color: var(--el-bg-color);
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.04);

    .interaction-tabs {
      margin-bottom: 15px;
    }

    .table-responsive {
      width: 100%;
      overflow-x: auto;
    }
  }
}
</style>