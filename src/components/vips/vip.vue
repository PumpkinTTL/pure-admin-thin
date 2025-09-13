<template>
  <div class="vip-badge" :class="[type, size]" :style="{
    '--scale': scale,
    '--primary-color': colors[colorScheme].primary,
    '--secondary-color': colors[colorScheme].secondary,
    '--text-color': colors[colorScheme].text
  }">
    <!-- 标准金色VIP徽章 -->
    <div v-if="type === 'classic'" class="vip-classic">
      <span class="vip-text">{{ text }}</span>
    </div>

    <!-- 菱形橙色VIP图标 -->
    <div v-else-if="type === 'diamond'" class="vip-diamond">
      <div class="vip-diamond-inner">
        <span v-if="showText" class="vip-text">{{ text }}</span>
      </div>
    </div>

    <!-- 六边形橙色VIP图标 -->
    <div v-else-if="type === 'hexagon'" class="vip-hexagon">
      <div class="vip-hexagon-inner">
        <span v-if="showText" class="vip-text">{{ text }}</span>
      </div>
    </div>

    <!-- 圆形橙色VIP图标 -->
    <div v-else-if="type === 'circle'" class="vip-circle">
      <div class="vip-circle-inner">
        <div class="vip-diamond-shape">
          <span v-if="showText" class="vip-text">{{ text }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
  // 徽章类型: classic(金色票), diamond(菱形), hexagon(六边形), circle(圆形)
  type: {
    type: String,
    default: 'classic',
    validator: (value: string) => ['classic', 'diamond', 'hexagon', 'circle'].includes(value)
  },
  // 尺寸: small, medium, large
  size: {
    type: String,
    default: 'medium',
    validator: (value: string) => ['small', 'medium', 'large'].includes(value)
  },
  // 自定义缩放比例
  scale: {
    type: Number,
    default: 1
  },
  // 颜色主题
  colorScheme: {
    type: String,
    default: 'orange',
    validator: (value: string) => ['orange', 'gold', 'blue', 'green', 'purple'].includes(value)
  },
  // 显示的文字
  text: {
    type: String,
    default: 'VIP'
  },
  // 是否显示文字
  showText: {
    type: Boolean,
    default: true
  }
});

// 预定义的颜色方案
const colors = {
  orange: {
    primary: '#FF7E1F',
    secondary: '#FFF6E9',
    text: '#FFFFFF'
  },
  gold: {
    primary: '#E6C029',
    secondary: '#FFF3D6',
    text: '#EE5F68'
  },
  blue: {
    primary: '#2B7FFF',
    secondary: '#E9F3FF',
    text: '#FFFFFF'
  },
  green: {
    primary: '#1FD37A',
    secondary: '#E4FFF0',
    text: '#FFFFFF'
  },
  purple: {
    primary: '#7E3FFC',
    secondary: '#F2E9FF',
    text: '#FFFFFF'
  }
};
</script>

<style lang="scss" scoped>
.vip-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  transform: scale(var(--scale, 1));
  transform-origin: center center;
}

// 尺寸变体
.small {
  font-size: 10px;
}

.medium {
  font-size: 14px;
}

.large {
  font-size: 18px;
}

// 经典金色VIP票样式
.vip-classic {
  background-color: var(--primary-color, #E6C029);
  border: 2px solid var(--primary-color, #E6C029);
  color: var(--text-color, #EE5F68);
  padding: 0.2em 0.6em;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: relative;

  &::before,
  &::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 0.5em;
    height: 0.5em;
    background-color: var(--secondary-color, #FFF3D6);
    border-radius: 50%;
    transform: translateY(-50%);
  }

  &::before {
    left: -0.25em;
  }

  &::after {
    right: -0.25em;
  }

  .vip-text {
    font-weight: bold;
    letter-spacing: 0.05em;
  }
}

// 菱形VIP图标样式
.vip-diamond {
  width: 2.5em;
  height: 2.5em;
  position: relative;

  .vip-diamond-inner {
    width: 100%;
    height: 100%;
    background-color: var(--primary-color, #FF7E1F);
    transform: rotate(45deg);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);

    .vip-text {
      transform: rotate(-45deg);
      color: var(--text-color, #FFFFFF);
      font-weight: bold;
      font-size: 0.9em;
    }
  }
}

// 六边形VIP图标样式
.vip-hexagon {
  width: 2.5em;
  height: 2.2em;
  position: relative;

  .vip-hexagon-inner {
    width: 100%;
    height: 100%;
    background-color: var(--primary-color, #FF7E1F);
    clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);

    .vip-text {
      color: var(--text-color, #FFFFFF);
      font-weight: bold;
      font-size: 0.9em;
    }
  }
}

// 圆形VIP图标样式
.vip-circle {
  width: 2.8em;
  height: 2.8em;
  position: relative;

  .vip-circle-inner {
    width: 100%;
    height: 100%;
    background-color: var(--secondary-color, #FFF6E9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);

    .vip-diamond-shape {
      width: 70%;
      height: 70%;
      background-color: var(--primary-color, #FF7E1F);
      clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
      display: flex;
      align-items: center;
      justify-content: center;

      .vip-text {
        color: var(--text-color, #FFFFFF);
        font-weight: bold;
        font-size: 0.8em;
      }
    }
  }
}
</style>
