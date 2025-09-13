<template>
  <div class="datacaritem-container">
    <div class="datacaritem-header">
      <div class="datacaritem-header-left">
        {{ title }}
      </div>
      <div class="datacaritem-header-right">
        <el-tag :type="tagType">{{ tagName }}</el-tag>
      </div>

    </div>
    <!-- <el-divider direction="horizontal" /> -->
    <div class="datacaritem-center-content">
      <div class="statistic-card">
        <el-statistic :value="98500">
          <template #title>
            <div style="display: inline-flex; align-items: center">
              {{ statisticTitle }}
              <el-tooltip effect="dark" content="Number of users who logged into the product in one day"
                placement="top">
                <el-icon style="margin-left: 4px" :size="12">
                  <Warning />
                </el-icon>
              </el-tooltip>
            </div>
          </template>
        </el-statistic>
        <div class="statistic-footer">
          <div class="footer-item">
            <span>相比较昨日</span>
            <span class="green">
              24%
              <el-icon>
                <CaretTop />
              </el-icon>
            </span>
          </div>
        </div>
      </div>
      <div class="datacartiem-progress-container">
        <el-progress :percentage="50" />
        <!-- <el-progress :indeterminate="true" :text-inside="true" :stroke-width="18" :percentage="50" status="" /> -->
      </div>
    </div>
    <!-- 底部 -->
    <div class="datacaritem-footer-cintaoner">
      <span>总销售额</span>
      <span>￥12332,254</span>
      <!-- <el-statistic title="统计全站内的销售额" :value="268500" /> -->
    </div>
  </div>
</template>
<script setup lang='ts'>
import {
  ArrowRight,
  CaretBottom,
  CaretTop,
  Warning,
} from '@element-plus/icons-vue'
import { defineOptions } from 'vue';
const pros = defineProps({
  title: {
    type: String,
    default: '销售额'
  },
  tagName: {
    default: '天',
    type: String
  },
  tagType: {
    default: 'warning',
    type: String
  },
  statisticTitle: {
    type: String,
    default: '日活跃用户'
  }
})
defineOptions({
  name: 'datacaritem'
})
</script>
<style lang='scss' scoped>
.datacaritem-container {
  background-color: #fff;
  border-radius: 4px;

  .datacaritem-header {
    display: flex;
    justify-content: space-between;
    padding: 15px 10px;
    border-bottom: 1px solid #eee;

  }

  .datacaritem-center-content {
    padding: 15px 10px;
    border-bottom: 1px solid #eee;

  }

  .datacaritem-footer-cintaoner {
    display: flex;
    justify-content: space-between;
    padding: 15px 10px;

    span {
      font-size: 12px;
    }
  }
}

:global(h2#card-usage ~ .example .example-showcase) {
  background-color: var(--el-fill-color) !important;
}

.el-statistic {
  --el-statistic-content-font-size: 28px;
}

.statistic-card {
  height: 100%;
  // padding: 20px;
  border-radius: 4px;
  background-color: var(--el-bg-color-overlay);
}

.statistic-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  font-size: 12px;
  color: var(--el-text-color-regular);
  // margin-top: 16px;
}

.statistic-footer .footer-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.datacartiem-progress-container {
  padding: 10px 0;
}

.statistic-footer .footer-item span:last-child {
  display: inline-flex;
  align-items: center;
  margin-left: 4px;
}

.green {
  color: var(--el-color-success);
}

.red {
  color: var(--el-color-error);
}
</style>
