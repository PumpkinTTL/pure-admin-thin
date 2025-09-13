<template>
  <el-dialog title="资源详情" v-model="dialogVisible" width="850px" destroy-on-close>
    <el-skeleton :loading="loading" animated>
      <template #template>
        <div class="skeleton-container">
          <div class="flex items-center">
            <el-skeleton-item variant="image" style="width: 240px; height: 160px" />
            <div class="ml-4 w-full">
              <el-skeleton-item variant="h1" style="width: 50%" />
              <div class="mt-3">
                <el-skeleton-item variant="text" style="width: 30%" />
              </div>
              <div class="mt-2">
                <el-skeleton-item variant="text" style="width: 60%" />
              </div>
            </div>
          </div>
          <div class="mt-6">
            <el-skeleton :rows="5" />
          </div>
        </div>
      </template>

      <template #default>
        <div class="resource-detail-container" v-if="resource">
          <!-- 资源头部信息 -->
          <div class="resource-header">
            <div class="resource-cover">
              <el-image :src="resource.cover_url" fit="cover" :preview-src-list="[resource.cover_url]">
                <template #error>
                  <div class="image-placeholder">
                    <el-icon>
                      <Picture />
                    </el-icon>
                  </div>
                </template>
              </el-image>
            </div>

            <div class="resource-info">
              <h2 class="resource-title">
                {{ resource.resource_name }}
                <el-tag v-if="resource.is_premium === 1" size="small" type="danger" effect="dark">Premium</el-tag>
              </h2>

              <div class="resource-meta-items">
                <div class="resource-meta-item">
                  <span class="meta-label">资源分类：</span>
                  <span class="meta-value">
                    <el-tag size="small">{{ resource.category || '未分类' }}</el-tag>
                  </span>
                </div>

                <div class="resource-meta-item">
                  <span class="meta-label">适用平台：</span>
                  <span class="meta-value">{{ resource.platform || '未指定' }}</span>
                </div>

                <div class="resource-meta-item">
                  <span class="meta-label">资源版本：</span>
                  <span class="meta-value">{{ resource.version || '未知' }}</span>
                </div>

                <div class="resource-meta-item">
                  <span class="meta-label">资源格式：</span>
                  <span class="meta-value">{{ resource.file_format || '未指定' }}</span>
                </div>

                <div class="resource-meta-item">
                  <span class="meta-label">文件大小：</span>
                  <span class="meta-value">{{ formatFileSize(resource.resource_size) }}</span>
                </div>

                <div class="resource-meta-item">
                  <span class="meta-label">支持语言：</span>
                  <span class="meta-value">{{ resource.language || '未指定' }}</span>
                </div>
              </div>

              <div class="resource-tags mt-2" v-if="resource.tags && resource.tags.length > 0">
                <span class="meta-label">标签：</span>
                <el-tag v-for="tag in resource.tags" :key="tag.id || tag.name" size="small" effect="light" class="ml-1">
                  {{ tag.name }}
                </el-tag>
              </div>

              <div class="resource-stats">
                <div class="stat-item">
                  <el-icon>
                    <View />
                  </el-icon>
                  <span>{{ resource.view_count || 0 }}</span>
                  <span class="stat-label">浏览</span>
                </div>
                <div class="stat-item">
                  <el-icon>
                    <Download />
                  </el-icon>
                  <span>{{ resource.download_count || 0 }}</span>
                  <span class="stat-label">下载</span>
                </div>
                <div class="stat-item">
                  <el-icon>
                    <Star />
                  </el-icon>
                  <span>{{ resource.favorite_count || 0 }}</span>
                  <span class="stat-label">收藏</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 上传者信息 -->
          <div class="uploader-info">
            <el-avatar :size="36" :src="resource.user?.avatar">
              <el-icon>
                <UserFilled />
              </el-icon>
            </el-avatar>
            <div class="uploader-details">
              <div class="uploader-name">{{ resource.user?.username || '未知用户' }}</div>
              <div class="upload-time">
                上传于 {{ formatDateTime(resource.release_time) }}
                <span v-if="resource.update_time">，更新于 {{ formatDateTime(resource.update_time) }}</span>
              </div>
            </div>
          </div>

          <!-- 资源描述 -->
          <el-divider content-position="left">资源描述</el-divider>
          <div class="resource-description">
            <p v-if="resource.description">{{ resource.description }}</p>
            <el-empty v-else description="暂无描述信息" />
          </div>

          <!-- 下载方式 -->
          <el-divider content-position="left">{{ resource.platform === 'Web网站' ? '网站链接' : '下载方式' }}</el-divider>
          <div class="download-methods">
            <!-- Web网站资源显示链接 -->
            <div v-if="resource.platform === 'Web网站'" class="web-link-container">
              <template v-if="resource.web_link">
                <div class="web-link-info">
                  <div class="link-title">
                    <font-awesome-icon :icon="['fas', 'globe']" class="mr-2" />
                    <span>网站链接</span>
                  </div>
                  <div class="link-content">
                    <el-link type="primary" :href="resource.web_link" target="_blank" :underline="false">
                      {{ resource.web_link }}
                    </el-link>
                    <el-button size="small" type="primary" plain class="copy-btn"
                      @click="copyToClipboard(resource.web_link)">
                      复制
                    </el-button>
                  </div>
                </div>
                <div class="web-link-tip">
                  <el-alert title="点击链接或复制按钮访问资源网站" type="info" :closable="false" show-icon />
                </div>
              </template>
              <el-empty v-else description="暂无网站链接" />
            </div>
            <!-- 普通资源显示下载方式 -->
            <template v-else>
              <el-empty v-if="!resource.downloadMethods || resource.downloadMethods.length === 0"
                description="暂无下载方式" />
              <el-table v-else :data="resource.downloadMethods" border stripe style="width: 100%">
                <el-table-column prop="method_name" label="下载方式" width="150">
                  <template #default="{ row }">
                    <el-tag :type="getDownloadMethodTagType(row.method_name)">{{ row.method_name }}</el-tag>
                  </template>
                </el-table-column>
                <el-table-column prop="download_link" label="下载链接" min-width="280">
                  <template #default="{ row }">
                    <div class="download-link">
                      <el-link type="primary" :href="row.download_link" target="_blank" :underline="false">
                        {{ row.download_link }}
                      </el-link>
                      <el-button size="small" type="primary" plain class="copy-btn"
                        @click="copyToClipboard(row.download_link)">
                        复制
                      </el-button>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column prop="extraction_code" label="提取码" width="120">
                  <template #default="{ row }">
                    <div v-if="row.extraction_code" class="extraction-code">
                      <span>{{ row.extraction_code }}</span>
                      <el-button size="small" plain class="copy-btn" @click="copyToClipboard(row.extraction_code)">
                        复制
                      </el-button>
                    </div>
                    <span v-else>-</span>
                  </template>
                </el-table-column>
                <el-table-column prop="status" label="状态" width="80" align="center">
                  <template #default="{ row }">
                    <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
                      {{ row.status === 1 ? '可用' : '失效' }}
                    </el-tag>
                  </template>
                </el-table-column>
              </el-table>
            </template>
          </div>

          <!-- 系统信息 -->
          <el-divider content-position="left">系统信息</el-divider>
          <div class="sys-info">
            <el-descriptions :column="2" border>
              <el-descriptions-item label="资源ID">{{ resource.id }}</el-descriptions-item>
              <el-descriptions-item label="上传用户ID">{{ resource.user_id }}</el-descriptions-item>
              <el-descriptions-item label="文件哈希">
                <el-tooltip :content="resource.file_hash || ''" placement="top">
                  <span class="truncate-text">{{ resource.file_hash || '-' }}</span>
                </el-tooltip>
              </el-descriptions-item>
              <el-descriptions-item label="资源状态">
                <el-tag :type="resource.delete_time ? 'danger' : 'success'" size="small">
                  {{ resource.delete_time ? '已删除' : '正常' }}
                </el-tag>
              </el-descriptions-item>
            </el-descriptions>
          </div>
        </div>
      </template>
    </el-skeleton>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="dialogVisible = false">关闭</el-button>
        <el-button type="primary" @click="handleDownload">下载资源</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, defineComponent } from "vue";
import { Picture, View, Download, Star, UserFilled } from "@element-plus/icons-vue";
import { getResourceDetail } from "@/api/resource";
import { message } from "@/utils/message";
import dayjs from "dayjs";
import type { ResourceItem } from "@/api/resource";

defineComponent({
  name: "ResourceDetail"
});

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  resourceId: {
    type: Number,
    default: null
  }
});

const emit = defineEmits(["update:visible"]);

// 对话框可见状态
const dialogVisible = computed({
  get: () => props.visible,
  set: (val) => emit("update:visible", val)
});

// 资源数据
const resource = ref<ResourceItem | null>(null);
const loading = ref(true);

// 获取资源详情
const fetchResourceDetail = async () => {
  if (!props.resourceId) {
    return;
  }

  try {
    loading.value = true;
    const res = await getResourceDetail(props.resourceId);
    if (res.code === 200) {
      // 确保资源数据正确
      const resourceData = res.data;

      // 如果有downloadLinks但没有downloadMethods，进行转换
      if (resourceData.downloadLinks && resourceData.downloadLinks.length > 0 &&
        (!resourceData.downloadMethods || resourceData.downloadMethods.length === 0)) {
        resourceData.downloadMethods = resourceData.downloadLinks.map(link => ({
          id: link.id,
          resource_id: link.resource_id,
          method_name: link.method_name,
          download_link: link.download_link,
          extraction_code: link.extraction_code || '',
          status: 1, // 默认为可用状态
          sort_order: 1
        }));
      }

      resource.value = resourceData;
    } else {
      message("获取资源详情失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取资源详情出错:", error);
    message("获取资源详情失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

// 格式化日期时间
const formatDateTime = (dateTime: string) => {
  if (!dateTime) return "-";
  return dayjs(dateTime).format("YYYY-MM-DD HH:mm");
};

// 格式化文件大小
const formatFileSize = (size: number | string) => {
  if (size === undefined || size === null) return "-";

  // 如果是字符串类型，尝试转换为数字
  const sizeNum = typeof size === 'string' ? parseFloat(size) : size;

  if (isNaN(sizeNum)) return "-";
  if (sizeNum < 1) return sizeNum.toFixed(2) + " KB";
  if (sizeNum < 1000) return sizeNum.toFixed(2) + " MB";
  return (sizeNum / 1000).toFixed(2) + " GB";
};

// 获取下载方式对应的标签类型
const getDownloadMethodTagType = (methodName: string): 'success' | 'warning' | 'info' | 'primary' | 'danger' => {
  const typeMap: Record<string, 'success' | 'warning' | 'info' | 'primary' | 'danger'> = {
    "百度网盘": "primary",
    "天翼云盘": "success",
    "阿里云盘": "warning",
    "OneDrive": "danger",
    "Google Drive": "info",
    "Dropbox": "primary",
    "直接下载": "success",
    "Mega": "warning"
  };

  return typeMap[methodName] || "info";
};

// 复制到剪贴板
const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text).then(() => {
    message("复制成功", { type: "success" });
  }).catch(() => {
    message("复制失败", { type: "error" });
  });
};

// 处理下载
const handleDownload = () => {
  if (!resource.value?.downloadMethods || resource.value.downloadMethods.length === 0) {
    message("该资源暂无下载方式", { type: "warning" });
    return;
  }

  const availableDownloads = resource.value.downloadMethods.filter(dm => dm.status === 1);
  if (availableDownloads.length === 0) {
    message("该资源所有下载方式均已失效", { type: "warning" });
    return;
  }

  // 在实际应用中，可以跳转到下载页或进行其他处理
  // 这里仅统计下载次数
  message("正在跳转到下载页...", { type: "success" });
};

// 监听对话框可见性和资源ID变化
watch(
  [() => props.visible, () => props.resourceId],
  ([visible, id]) => {
    if (visible && id) {
      fetchResourceDetail();
    }
  },
  { immediate: true }
);

onMounted(() => {
  if (props.visible && props.resourceId) {
    fetchResourceDetail();
  }
});
</script>

<style lang="scss" scoped>
.skeleton-container {
  padding: 20px;
}

.resource-detail-container {
  .resource-header {
    display: flex;
    margin-bottom: 24px;

    .resource-cover {
      width: 240px;
      height: 160px;
      border-radius: 4px;
      overflow: hidden;
      flex-shrink: 0;
      background-color: var(--el-fill-color-light);

      .el-image {
        width: 100%;
        height: 100%;
        object-fit: cover;

        .image-placeholder {
          width: 100%;
          height: 100%;
          display: flex;
          align-items: center;
          justify-content: center;
          background-color: var(--el-fill-color-light);
          color: var(--el-text-color-placeholder);
          font-size: 24px;
        }
      }
    }

    .resource-info {
      margin-left: 24px;
      flex: 1;

      .resource-title {
        font-size: 20px;
        margin: 0 0 16px 0;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .resource-meta-items {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        grid-gap: 12px;

        .resource-meta-item {
          font-size: 14px;
          display: flex;
          align-items: center;

          .meta-label {
            color: var(--el-text-color-secondary);
            margin-right: 4px;
            flex-shrink: 0;
          }

          .meta-value {
            color: var(--el-text-color-primary);
          }
        }
      }

      .resource-tags {
        display: flex;
        align-items: center;

        .meta-label {
          color: var(--el-text-color-secondary);
          margin-right: 4px;
          flex-shrink: 0;
        }
      }

      .resource-stats {
        margin-top: 18px;
        display: flex;
        gap: 24px;

        .stat-item {
          display: flex;
          align-items: center;
          gap: 4px;
          font-size: 14px;

          .el-icon {
            margin-right: 4px;
            color: var(--el-color-primary);
          }

          .stat-label {
            color: var(--el-text-color-secondary);
            margin-left: 2px;
          }
        }
      }
    }
  }

  .uploader-info {
    display: flex;
    align-items: center;
    padding: 12px;
    background-color: var(--el-fill-color-lighter);
    border-radius: 4px;
    margin-bottom: 24px;

    .uploader-details {
      margin-left: 12px;

      .uploader-name {
        font-weight: 500;
        font-size: 14px;
      }

      .upload-time {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        margin-top: 2px;
      }
    }
  }

  .resource-description {
    margin-bottom: 24px;
    line-height: 1.6;
    color: var(--el-text-color-regular);
    font-size: 14px;

    p {
      margin: 0;
      white-space: pre-wrap;
    }
  }

  .download-methods {
    margin-bottom: 24px;

    .download-link {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .extraction-code {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .copy-btn {
      padding: 2px 8px;
      height: 24px;
      line-height: 1;
      margin-left: 8px;
    }
  }

  .sys-info {
    margin-bottom: 16px;

    .truncate-text {
      display: block;
      max-width: 300px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }
}

.web-link-container {
  padding: 16px;
  border-radius: 4px;
  border: 1px solid #ebeef5;

  .web-link-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;

    .link-title {
      font-size: 15px;
      font-weight: 500;
      color: #303133;
      display: flex;
      align-items: center;
    }

    .link-content {
      display: flex;
      align-items: center;
      gap: 12px;

      .el-link {
        font-size: 14px;
        max-width: 80%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .copy-btn {
        margin-left: auto;
      }
    }
  }

  .web-link-tip {
    margin-top: 16px;
  }
}
</style>