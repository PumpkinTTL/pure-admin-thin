<template>
  <div class="server-monitor" v-motion :initial="{ opacity: 0, y: 20 }"
    :enter="{ opacity: 1, y: 0, transition: { delay: 300 } }">
    <div class="monitor-container">
      <div class="section-header">
        <h2>服务器监控</h2>
        <el-tag size="small" :type="serverStatus === 'normal' ? 'success' : 'danger'">
          {{ serverStatus === 'normal' ? '运行正常' : '需要注意' }}
        </el-tag>
      </div>

      <!-- 服务器资源使用情况 -->
      <div class="resource-cards">
        <!-- CPU使用率 -->
        <div class="resource-card">
          <div class="resource-icon cpu">
            <font-awesome-icon :icon="['fas', 'microchip']" />
          </div>
          <div class="resource-info">
            <div class="resource-name">CPU</div>
            <el-progress :percentage="cpuUsage" :color="getCpuProgressColor()" :stroke-width="8" :show-text="false" />
            <div class="resource-value">{{ cpuUsage }}%</div>
          </div>
        </div>

        <!-- 内存使用率 -->
        <div class="resource-card">
          <div class="resource-icon memory">
            <font-awesome-icon :icon="['fas', 'memory']" />
          </div>
          <div class="resource-info">
            <div class="resource-name">内存</div>
            <el-progress :percentage="memoryUsage" :color="getMemoryProgressColor()" :stroke-width="8"
              :show-text="false" />
            <div class="resource-value">{{ memoryUsage }}% <span class="detail-text">{{ usedMemory }}/{{ totalMemory
                }}</span></div>
          </div>
        </div>

        <!-- 磁盘使用率 -->
        <div class="resource-card">
          <div class="resource-icon disk">
            <font-awesome-icon :icon="['fas', 'hdd']" />
          </div>
          <div class="resource-info">
            <div class="resource-name">存储</div>
            <el-progress :percentage="diskUsage" :color="getDiskProgressColor()" :stroke-width="8" :show-text="false" />
            <div class="resource-value">{{ diskUsage }}% <span class="detail-text">{{ usedDisk }}/{{ totalDisk }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 服务状态 -->
      <div class="services-section">
        <div class="section-title">服务状态</div>
        <div class="services-list">
          <div v-for="(service, index) in services" :key="index" class="service-item">
            <div class="service-info">
              <div class="service-name">{{ service.name }}</div>
              <el-tag size="small"
                :type="service.status === 'running' ? 'success' : service.status === 'warning' ? 'warning' : 'danger'">
                {{ service.status === 'running' ? '运行中' : service.status === 'warning' ? '警告' : '错误' }}
              </el-tag>
            </div>
            <div class="service-metrics">
              <div class="metric">
                <font-awesome-icon :icon="['fas', 'clock']" class="metric-icon" />
                <span>{{ service.uptime }}</span>
              </div>
              <div class="metric">
                <font-awesome-icon :icon="['fas', 'signal']" class="metric-icon" />
                <span>{{ service.load }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 系统日志摘要 -->
      <div class="system-logs">
        <div class="section-title">
          <span>系统日志</span>
          <el-button size="small" type="primary" text>查看全部</el-button>
        </div>
        <div class="logs-list">
          <div v-for="(log, index) in logs" :key="index" class="log-item"
            :class="{ error: log.type === 'error', warning: log.type === 'warning' }">
            <div class="log-time">{{ log.time }}</div>
            <div class="log-content">
              <div class="log-type">
                <font-awesome-icon :icon="getLogIcon(log.type)" :class="log.type" />
              </div>
              <div class="log-message">{{ log.message }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- 操作按钮 -->
      <div class="action-buttons">
        <el-button size="small" type="primary"><font-awesome-icon :icon="['fas', 'sync']" /> 刷新数据</el-button>
        <el-button size="small"><font-awesome-icon :icon="['fas', 'cog']" /> 管理服务器</el-button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, reactive } from 'vue';

export default defineComponent({
  name: "ServerMonitor",
  setup() {
    // 服务器状态
    const serverStatus = ref('normal'); // normal or warning

    // CPU使用率
    const cpuUsage = ref(68);

    // 内存使用情况
    const memoryUsage = ref(75);
    const totalMemory = ref('16GB');
    const usedMemory = ref('12GB');

    // 磁盘使用情况
    const diskUsage = ref(42);
    const totalDisk = ref('1TB');
    const usedDisk = ref('420GB');

    // 服务状态列表
    const services = reactive([
      {
        name: "Node.js应用服务",
        status: "running",
        uptime: "23天12小时",
        load: "正常"
      },
      {
        name: "MySQL数据库",
        status: "running",
        uptime: "23天12小时",
        load: "正常"
      },
      {
        name: "Nginx服务器",
        status: "warning",
        uptime: "15天8小时",
        load: "较高"
      },
      {
        name: "Redis缓存",
        status: "running",
        uptime: "23天11小时",
        load: "正常"
      }
    ]);

    // 系统日志
    const logs = reactive([
      {
        time: "10:25:43",
        type: "info",
        message: "系统定时备份完成"
      },
      {
        time: "09:42:17",
        type: "warning",
        message: "Nginx服务器负载过高，请检查"
      },
      {
        time: "08:15:30",
        type: "error",
        message: "数据库连接池达到上限"
      },
      {
        time: "昨天 23:50",
        type: "info",
        message: "系统更新完成"
      }
    ]);

    // 获取CPU进度条颜色
    const getCpuProgressColor = () => {
      if (cpuUsage.value < 50) return '#67C23A';
      if (cpuUsage.value < 80) return '#E6A23C';
      return '#F56C6C';
    };

    // 获取内存进度条颜色
    const getMemoryProgressColor = () => {
      if (memoryUsage.value < 60) return '#67C23A';
      if (memoryUsage.value < 85) return '#E6A23C';
      return '#F56C6C';
    };

    // 获取磁盘进度条颜色
    const getDiskProgressColor = () => {
      if (diskUsage.value < 70) return '#67C23A';
      if (diskUsage.value < 90) return '#E6A23C';
      return '#F56C6C';
    };

    // 获取日志图标
    const getLogIcon = (type) => {
      switch (type) {
        case 'error':
          return ['fas', 'exclamation-circle'];
        case 'warning':
          return ['fas', 'exclamation-triangle'];
        default:
          return ['fas', 'info-circle'];
      }
    };

    return {
      serverStatus,
      cpuUsage,
      memoryUsage,
      totalMemory,
      usedMemory,
      diskUsage,
      totalDisk,
      usedDisk,
      services,
      logs,
      getCpuProgressColor,
      getMemoryProgressColor,
      getDiskProgressColor,
      getLogIcon
    };
  }
});
</script>

<style scoped lang="scss">
.server-monitor {
  background-color: var(--el-bg-color);
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  height: 100%;
  width: 100%;
  overflow: hidden;

  .monitor-container {
    padding: 20px;
    height: 100%;
    display: flex;
    flex-direction: column;

    @media (max-width: 768px) {
      padding: 15px;
    }
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;

    h2 {
      font-size: 18px;
      font-weight: 600;
      margin: 0;
      color: var(--el-text-color-primary);
    }
  }

  .section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 500;
    color: var(--el-text-color-primary);
  }

  .resource-cards {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;

    .resource-card {
      display: flex;
      align-items: center;
      background-color: var(--el-bg-color-page);
      border-radius: 8px;
      padding: 12px;
      transition: all 0.3s;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);

      &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      }

      .resource-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;
        color: #fff;
        font-size: 18px;

        &.cpu {
          background: linear-gradient(135deg, #3498db, #2980b9);
        }

        &.memory {
          background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        &.disk {
          background: linear-gradient(135deg, #1abc9c, #16a085);
        }
      }

      .resource-info {
        flex: 1;

        .resource-name {
          font-size: 14px;
          color: var(--el-text-color-secondary);
          margin-bottom: 6px;
        }

        .resource-value {
          font-size: 13px;
          color: var(--el-text-color-primary);
          margin-top: 6px;

          .detail-text {
            font-size: 12px;
            color: var(--el-text-color-secondary);
            margin-left: 6px;
          }
        }

        :deep(.el-progress-bar__outer) {
          border-radius: 4px;
          background-color: var(--el-fill-color-lighter);
        }

        :deep(.el-progress-bar__inner) {
          border-radius: 4px;
        }
      }
    }
  }

  .services-section {
    margin-bottom: 20px;

    .services-list {
      background-color: var(--el-bg-color-page);
      border-radius: 8px;
      padding: 8px 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);

      .service-item {
        padding: 10px 0;
        border-bottom: 1px solid var(--el-border-color-lighter);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;

        &:last-child {
          border-bottom: none;
        }

        .service-info {
          display: flex;
          align-items: center;
          justify-content: space-between;
          width: 60%;
          min-width: 200px;

          .service-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--el-text-color-primary);
          }
        }

        .service-metrics {
          display: flex;
          gap: 16px;
          color: var(--el-text-color-secondary);
          font-size: 13px;

          .metric {
            display: flex;
            align-items: center;
            gap: 5px;

            .metric-icon {
              font-size: 12px;
            }
          }
        }

        @media (max-width: 768px) {
          flex-direction: column;
          align-items: flex-start;
          gap: 6px;

          .service-info {
            width: 100%;
          }
        }
      }
    }
  }

  .system-logs {
    flex: 1;
    margin-bottom: 20px;
    min-height: 160px;

    .logs-list {
      background-color: var(--el-bg-color-page);
      border-radius: 8px;
      padding: 8px 12px;
      max-height: 160px;
      overflow-y: auto;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);

      .log-item {
        padding: 8px 0;
        border-bottom: 1px solid var(--el-border-color-lighter);
        font-size: 13px;

        &:last-child {
          border-bottom: none;
        }

        .log-time {
          font-size: 12px;
          color: var(--el-text-color-secondary);
          margin-bottom: 4px;
        }

        .log-content {
          display: flex;
          align-items: center;
          gap: 8px;

          .log-type {
            .info {
              color: #409EFF;
            }

            .warning {
              color: #E6A23C;
            }

            .error {
              color: #F56C6C;
            }
          }

          .log-message {
            flex: 1;
            color: var(--el-text-color-primary);
          }
        }
      }
    }
  }

  .action-buttons {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: auto;
  }
}
</style>