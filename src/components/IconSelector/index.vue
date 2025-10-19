<template>
  <el-dialog
    v-model="dialogVisible"
    title="选择图标"
    width="800px"
    :before-close="handleClose"
    append-to-body
  >
    <div class="icon-selector">
      <!-- 搜索框 -->
      <div class="search-bar">
        <el-input
          v-model="searchText"
          placeholder="搜索图标..."
          clearable
          size="default"
        >
          <template #prefix>
            <FontAwesomeIconWrapper icon="fas fa-search" />
          </template>
        </el-input>
        <div class="icon-type-tabs">
          <el-radio-group v-model="activeType" size="small">
            <el-radio-button value="all">全部</el-radio-button>
            <el-radio-button value="fas">Solid</el-radio-button>
            <el-radio-button value="far">Regular</el-radio-button>
            <el-radio-button value="fab">Brands</el-radio-button>
          </el-radio-group>
        </div>
      </div>

      <!-- 当前选中 -->
      <div v-if="selectedIcon" class="current-selection">
        <span class="label">当前选中:</span>
        <div class="selected-icon-preview">
          <FontAwesomeIconWrapper :icon="selectedIcon" class="preview-icon" />
          <span class="icon-name">{{ selectedIcon }}</span>
        </div>
      </div>

      <!-- 图标列表 -->
      <div class="icon-list-wrapper">
        <el-scrollbar height="400px">
          <div v-if="filteredIcons.length > 0" class="icon-grid">
            <div
              v-for="icon in filteredIcons"
              :key="icon.iconName"
              :class="['icon-item', { active: selectedIcon === icon.iconName }]"
              @click="handleSelectIcon(icon.iconName)"
            >
              <FontAwesomeIconWrapper :icon="icon.iconName" class="icon" />
              <span class="icon-label">{{ icon.displayName }}</span>
            </div>
          </div>
          <el-empty v-else description="没有找到匹配的图标" />
        </el-scrollbar>
      </div>

      <!-- 底部信息 -->
      <div class="footer-info">
        <span class="count-info">共 {{ filteredIcons.length }} 个图标</span>
      </div>
    </div>

    <template #footer>
      <el-button @click="handleClose" size="default">取消</el-button>
      <el-button type="primary" @click="handleConfirm" size="default">
        确定
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import FontAwesomeIconWrapper from "./FontAwesomeIconWrapper.vue";
import { useSettingStoreHook } from "@/store/modules/settings";

const settingStore = useSettingStoreHook();

// 从 fontawesome.ts 中实际引入的所有图标
// 这个列表需要与 src/plugins/fontawesome.ts 保持同步
const iconList = [
  // Solid icons (fas)
  { iconName: "fas fa-home", displayName: "home", type: "fas" },
  { iconName: "fas fa-user", displayName: "user", type: "fas" },
  { iconName: "fas fa-chart-bar", displayName: "chart-bar", type: "fas" },
  { iconName: "fas fa-cog", displayName: "cog", type: "fas" },
  { iconName: "fas fa-bell", displayName: "bell", type: "fas" },
  { iconName: "fas fa-envelope", displayName: "envelope", type: "fas" },
  { iconName: "fas fa-calendar", displayName: "calendar", type: "fas" },
  { iconName: "fas fa-server", displayName: "server", type: "fas" },
  { iconName: "fas fa-database", displayName: "database", type: "fas" },
  { iconName: "fas fa-exclamation-triangle", displayName: "exclamation-triangle", type: "fas" },
  { iconName: "fas fa-tachometer-alt", displayName: "tachometer-alt", type: "fas" },
  { iconName: "fas fa-clipboard", displayName: "clipboard", type: "fas" },
  { iconName: "fas fa-comment", displayName: "comment", type: "fas" },
  { iconName: "fas fa-eye", displayName: "eye", type: "fas" },
  { iconName: "fas fa-user-plus", displayName: "user-plus", type: "fas" },
  { iconName: "fas fa-chart-line", displayName: "chart-line", type: "fas" },
  { iconName: "fas fa-check-circle", displayName: "check-circle", type: "fas" },
  { iconName: "fas fa-info-circle", displayName: "info-circle", type: "fas" },
  { iconName: "fas fa-exclamation-circle", displayName: "exclamation-circle", type: "fas" },
  { iconName: "fas fa-list", displayName: "list", type: "fas" },
  { iconName: "fas fa-cloud-upload-alt", displayName: "cloud-upload-alt", type: "fas" },
  { iconName: "fas fa-file-alt", displayName: "file-alt", type: "fas" },
  { iconName: "fas fa-shield-alt", displayName: "shield-alt", type: "fas" },
  { iconName: "fas fa-clock", displayName: "clock", type: "fas" },
  { iconName: "fas fa-lightbulb", displayName: "lightbulb", type: "fas" },
  { iconName: "fas fa-wrench", displayName: "wrench", type: "fas" },
  { iconName: "fas fa-terminal", displayName: "terminal", type: "fas" },
  { iconName: "fas fa-folder", displayName: "folder", type: "fas" },
  { iconName: "fas fa-sync", displayName: "sync", type: "fas" },
  { iconName: "fas fa-copy", displayName: "copy", type: "fas" },
  { iconName: "fas fa-plus-circle", displayName: "plus-circle", type: "fas" },
  { iconName: "fas fa-arrow-up", displayName: "arrow-up", type: "fas" },
  { iconName: "fas fa-arrow-down", displayName: "arrow-down", type: "fas" },
  { iconName: "fas fa-edit", displayName: "edit", type: "fas" },
  { iconName: "fas fa-image", displayName: "image", type: "fas" },
  { iconName: "fas fa-sun", displayName: "sun", type: "fas" },
  { iconName: "fas fa-upload", displayName: "upload", type: "fas" },
  { iconName: "fas fa-calendar-alt", displayName: "calendar-alt", type: "fas" },
  { iconName: "fas fa-plus", displayName: "plus", type: "fas" },
  { iconName: "fas fa-check", displayName: "check", type: "fas" },
  { iconName: "fas fa-tasks", displayName: "tasks", type: "fas" },
  { iconName: "fas fa-comments", displayName: "comments", type: "fas" },
  { iconName: "fas fa-users", displayName: "users", type: "fas" },
  { iconName: "fas fa-microchip", displayName: "microchip", type: "fas" },
  { iconName: "fas fa-memory", displayName: "memory", type: "fas" },
  { iconName: "fas fa-hdd", displayName: "hdd", type: "fas" },
  { iconName: "fas fa-network-wired", displayName: "network-wired", type: "fas" },
  { iconName: "fas fa-angle-down", displayName: "angle-down", type: "fas" },
  { iconName: "fas fa-heart", displayName: "heart", type: "fas" },
  { iconName: "fas fa-reply", displayName: "reply", type: "fas" },
  { iconName: "fas fa-bookmark", displayName: "bookmark", type: "fas" },
  { iconName: "fas fa-ad", displayName: "ad", type: "fas" },
  { iconName: "fas fa-book", displayName: "book", type: "fas" },
  { iconName: "fas fa-gift", displayName: "gift", type: "fas" },
  { iconName: "fas fa-coins", displayName: "coins", type: "fas" },
  { iconName: "fas fa-money-bill-wave", displayName: "money-bill-wave", type: "fas" },
  { iconName: "fas fa-chevron-down", displayName: "chevron-down", type: "fas" },
  { iconName: "fas fa-donate", displayName: "donate", type: "fas" },
  { iconName: "fas fa-hand-holding-usd", displayName: "hand-holding-usd", type: "fas" },
  { iconName: "fas fa-user-friends", displayName: "user-friends", type: "fas" },
  { iconName: "fas fa-crown", displayName: "crown", type: "fas" },
  { iconName: "fas fa-cloud-arrow-down", displayName: "cloud-arrow-down", type: "fas" },
  { iconName: "fas fa-cubes", displayName: "cubes", type: "fas" },
  { iconName: "fas fa-search", displayName: "search", type: "fas" },
  { iconName: "fas fa-undo", displayName: "undo", type: "fas" },
  { iconName: "fas fa-trash", displayName: "trash", type: "fas" },
  { iconName: "fas fa-ellipsis-h", displayName: "ellipsis-h", type: "fas" },
  { iconName: "fas fa-print", displayName: "print", type: "fas" },
  { iconName: "fas fa-file-export", displayName: "file-export", type: "fas" },
  { iconName: "fas fa-globe", displayName: "globe", type: "fas" },
  { iconName: "fas fa-code-branch", displayName: "code-branch", type: "fas" },
  { iconName: "fas fa-cloud", displayName: "cloud", type: "fas" },
  { iconName: "fas fa-desktop", displayName: "desktop", type: "fas" },
  { iconName: "fas fa-link", displayName: "link", type: "fas" },
  { iconName: "fas fa-tag", displayName: "tag", type: "fas" },
  { iconName: "fas fa-laptop-code", displayName: "laptop-code", type: "fas" },
  { iconName: "fas fa-ban", displayName: "ban", type: "fas" },
  { iconName: "fas fa-circle", displayName: "circle", type: "fas" },
  { iconName: "fas fa-coffee", displayName: "coffee", type: "fas" },
  { iconName: "fas fa-box-archive", displayName: "box-archive", type: "fas" },
  { iconName: "fas fa-mobile-screen", displayName: "mobile-screen", type: "fas" },
  { iconName: "fas fa-comment-dots", displayName: "comment-dots", type: "fas" },
  { iconName: "fas fa-credit-card", displayName: "credit-card", type: "fas" },
  { iconName: "fas fa-times", displayName: "times", type: "fas" },
  { iconName: "fas fa-bullhorn", displayName: "bullhorn", type: "fas" },
  { iconName: "fas fa-thumbs-up", displayName: "thumbs-up", type: "fas" },
  { iconName: "fas fa-thumbs-down", displayName: "thumbs-down", type: "fas" },
  { iconName: "fas fa-paper-plane", displayName: "paper-plane", type: "fas" },
  { iconName: "fas fa-paperclip", displayName: "paperclip", type: "fas" },
  { iconName: "fas fa-hourglass", displayName: "hourglass", type: "fas" },
  { iconName: "fas fa-hourglass-half", displayName: "hourglass-half", type: "fas" },
  { iconName: "fas fa-thumbtack", displayName: "thumbtack", type: "fas" },
  { iconName: "fas fa-user-slash", displayName: "user-slash", type: "fas" },
  { iconName: "fas fa-magic", displayName: "magic", type: "fas" },
  { iconName: "fas fa-star", displayName: "star", type: "fas" },
  { iconName: "fas fa-rocket", displayName: "rocket", type: "fas" },
  { iconName: "fas fa-palette", displayName: "palette", type: "fas" },
  { iconName: "fas fa-save", displayName: "save", type: "fas" },
  { iconName: "fas fa-refresh", displayName: "refresh", type: "fas" },
  { iconName: "fas fa-font", displayName: "font", type: "fas" },
  { iconName: "fas fa-wand-magic-sparkles", displayName: "wand-magic-sparkles", type: "fas" },
  { iconName: "fas fa-th-large", displayName: "th-large", type: "fas" },
  { iconName: "fas fa-pause", displayName: "pause", type: "fas" },
  { iconName: "fas fa-play", displayName: "play", type: "fas" },
  { iconName: "fas fa-ellipsis-v", displayName: "ellipsis-v", type: "fas" },
  { iconName: "fas fa-inbox", displayName: "inbox", type: "fas" },
  { iconName: "fas fa-swatchbook", displayName: "swatchbook", type: "fas" },
  { iconName: "fas fa-random", displayName: "random", type: "fas" },
  { iconName: "fas fa-arrow-right", displayName: "arrow-right", type: "fas" },
  { iconName: "fas fa-key", displayName: "key", type: "fas" },
  
  // Regular icons (far)
  { iconName: "far fa-check-circle", displayName: "check-circle", type: "far" },
  { iconName: "far fa-bell", displayName: "bell", type: "far" },
  
  // Brand icons (fab)
  { iconName: "fab fa-github", displayName: "github", type: "fab" },
  { iconName: "fab fa-vuejs", displayName: "vuejs", type: "fab" },
  { iconName: "fab fa-windows", displayName: "windows", type: "fab" },
  { iconName: "fab fa-apple", displayName: "apple", type: "fab" },
  { iconName: "fab fa-linux", displayName: "linux", type: "fab" },
  { iconName: "fab fa-android", displayName: "android", type: "fab" },
  { iconName: "fab fa-weixin", displayName: "weixin", type: "fab" },
  { iconName: "fab fa-alipay", displayName: "alipay", type: "fab" },
  { iconName: "fab fa-btc", displayName: "btc", type: "fab" },
  { iconName: "fab fa-ethereum", displayName: "ethereum", type: "fab" },
  { iconName: "fab fa-bitcoin", displayName: "bitcoin", type: "fab" }
];

const dialogVisible = computed({
  get: () => settingStore.iconSelectorVisible,
  set: (val) => {
    settingStore.setIconSelectorVisible(val);
  }
});

const searchText = ref("");
const activeType = ref("all");
const selectedIcon = ref("");

// 监听 store 中的初始值
watch(
  () => settingStore.iconSelectorValue,
  (val) => {
    if (val) {
      selectedIcon.value = val;
    }
  },
  { immediate: true }
);

// 过滤图标
const filteredIcons = computed(() => {
  let icons = iconList;

  // 按类型过滤
  if (activeType.value !== "all") {
    icons = icons.filter((icon) => icon.type === activeType.value);
  }

  // 按搜索文本过滤
  if (searchText.value) {
    const search = searchText.value.toLowerCase();
    icons = icons.filter((icon) =>
      icon.displayName.toLowerCase().includes(search)
    );
  }

  return icons;
});

// 选择图标
const handleSelectIcon = (iconName: string) => {
  selectedIcon.value = iconName;
};

// 确认选择
const handleConfirm = () => {
  settingStore.setIconSelectorValue(selectedIcon.value);
  handleClose();
};

// 关闭对话框
const handleClose = () => {
  settingStore.setIconSelectorVisible(false);
  // 重置状态
  searchText.value = "";
  activeType.value = "all";
  selectedIcon.value = "";
};
</script>

<style scoped lang="scss">
.icon-selector {
  .search-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    align-items: center;

    .el-input {
      flex: 1;
    }

    .icon-type-tabs {
      flex-shrink: 0;
    }
  }

  .current-selection {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    margin-bottom: 16px;
    background: var(--el-fill-color-light);
    border-radius: 4px;

    .label {
      font-size: 14px;
      font-weight: 600;
      color: var(--el-text-color-regular);
    }

    .selected-icon-preview {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      background: var(--el-bg-color);
      border-radius: 4px;

      .preview-icon {
        font-size: 18px;
        color: var(--el-color-primary);
      }

      .icon-name {
        font-size: 13px;
        font-family: monospace;
        color: var(--el-text-color-primary);
      }
    }
  }

  .icon-list-wrapper {
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 4px;
    margin-bottom: 12px;
  }

  .icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 8px;
    padding: 12px;

    .icon-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 16px 8px;
      border: 1px solid var(--el-border-color-lighter);
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s;

      &:hover {
        border-color: var(--el-color-primary);
        background: var(--el-fill-color-light);
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      }

      &.active {
        border-color: var(--el-color-primary);
        background: var(--el-color-primary-light-9);
        box-shadow: 0 0 0 2px var(--el-color-primary-light-5);
      }

      .icon {
        font-size: 24px;
        color: var(--el-text-color-primary);
        margin-bottom: 8px;
      }

      .icon-label {
        font-size: 11px;
        color: var(--el-text-color-secondary);
        text-align: center;
        word-break: break-all;
        line-height: 1.2;
      }
    }
  }

  .footer-info {
    display: flex;
    justify-content: flex-end;
    padding: 8px 0;

    .count-info {
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }
  }
}
</style>
