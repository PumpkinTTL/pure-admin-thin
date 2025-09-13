<template>
  <div class="vip-badge" :class="[`vip-${type}`, { 'no-text': !text }]" :style="badgeStyles">
    <span v-if="text" class="vip-text">{{ text }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: 'classic',
    validator: (value) => ['classic', 'diamond', 'hexagon', 'circle'].includes(value)
  },
  text: {
    type: String,
    default: 'VIP'
  },
  scale: {
    type: Number,
    default: 1
  },
  colorScheme: {
    type: String,
    default: 'orange',
    validator: (value) => [
      'orange', 'gold', 'blue', 'green', 'purple',
      'goldGradient', 'blueGradient', 'purpleGradient',
      'redGradient', 'darkGradient', 'premiumGradient'
    ].includes(value)
  }
});

const badgeStyles = computed(() => {
  const styles = {
    transform: `scale(${props.scale})`,
  };

  // 基础颜色方案
  const colorMap = {
    orange: { background: '#ff9800', color: '#fff' },
    gold: { background: '#ffc107', color: '#fff' },
    blue: { background: '#2196f3', color: '#fff' },
    green: { background: '#4caf50', color: '#fff' },
    purple: { background: '#9c27b0', color: '#fff' },

    // 渐变色方案
    goldGradient: {
      background: 'linear-gradient(135deg, #f6d365 0%, #fda085 100%)',
      color: '#fff'
    },
    blueGradient: {
      background: 'linear-gradient(135deg, #56ccf2 0%, #2f80ed 100%)',
      color: '#fff'
    },
    purpleGradient: {
      background: 'linear-gradient(135deg, #a166ab 0%, #e975a8 100%)',
      color: '#fff'
    },
    redGradient: {
      background: 'linear-gradient(135deg, #ff5f6d 0%, #ffc371 100%)',
      color: '#fff'
    },
    darkGradient: {
      background: 'linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%)',
      color: '#fff'
    },
    premiumGradient: {
      background: 'linear-gradient(135deg, #b9a55a 0%, #e4d5a3 50%, #b9a55a 100%)',
      color: '#fff',
      boxShadow: '0 2px 10px rgba(185, 165, 90, 0.5)'
    }
  };

  const selectedColor = colorMap[props.colorScheme] || colorMap.orange;

  // 应用颜色方案
  styles.background = selectedColor.background;
  styles.color = selectedColor.color;

  // 应用阴影效果（如果有）
  if (selectedColor.boxShadow) {
    styles.boxShadow = selectedColor.boxShadow;
  }

  return styles;
});
</script>

<style>
.vip-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 12px;
  height: 20px;
  padding: 0 6px;
  border-radius: 3px;
  user-select: none;
  transform-origin: left center;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.vip-classic {
  border-radius: 3px;
}

.vip-diamond {
  transform: rotate(45deg);
  height: 24px;
  width: 24px;
  padding: 0;
}

.vip-diamond .vip-text {
  transform: rotate(-45deg);
  white-space: nowrap;
}

.vip-hexagon {
  clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
  height: 26px;
  width: 30px;
  padding: 0;
}

.vip-circle {
  border-radius: 50%;
  height: 26px;
  width: 26px;
  padding: 0;
}

.vip-badge.no-text {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  padding: 0;
}
</style>