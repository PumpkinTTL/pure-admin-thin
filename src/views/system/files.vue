<template>
  <div class="files-container">
    <!-- 主内容区 -->
    <el-card shadow="never" class="content-card">
      <div class="toolbar">
        <div class="toolbar__actions">
          <el-upload ref="uploadRef" :action="uploadUrl" :headers="uploadHeaders" :data="uploadData"
            :on-success="handleUploadSuccess" :on-error="handleUploadError" :on-progress="handleUploadProgress"
            :before-upload="beforeUpload" :limit="8" :multiple="true" :show-file-list="false">
            <el-button type="primary" size="small" class="toolbar-btn">
              <i class="fa fa-cloud-upload-alt mr-1"></i>
              上传文件
            </el-button>
          </el-upload>

          <el-button type="danger" :disabled="!selectedFiles.length" size="small" class="toolbar-btn"
            @click="handleBatchDelete">
            <i class="fa fa-trash-alt mr-1"></i>
            批量删除
            <span v-if="selectedFiles.length">({{ selectedFiles.length }})</span>
          </el-button>

          <el-dropdown @command="handleStatusCommand" trigger="click">
            <el-button size="small" class="toolbar-btn status-btn">
              <i class="fa fa-filter mr-1"></i>
              {{ searchParams.status === "active" ? "活跃文件" : "已删除文件" }}
              <i class="fa fa-chevron-down ml-1"></i>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="active" :class="{ 'is-active': searchParams.status === 'active' }">
                  <i class="fa fa-file mr-2 text-blue-500"></i>
                  <span>活跃文件</span>
                  <i v-if="searchParams.status === 'active'" class="fa fa-check ml-auto text-blue-500"></i>
                </el-dropdown-item>
                <el-dropdown-item command="deleted" :class="{ 'is-active': searchParams.status === 'deleted' }">
                  <i class="fa fa-trash mr-2 text-red-500"></i>
                  <span>已删除文件</span>
                  <i v-if="searchParams.status === 'deleted'" class="fa fa-check ml-auto text-red-500"></i>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>

        <div class="toolbar__search">
          <el-input v-model="searchParams.original_name" placeholder="搜索文件名" clearable size="small"
            @keyup.enter="handleSearch" @clear="handleSearch">
            <template #prefix>
              <el-icon>
                <Search />
              </el-icon>
            </template>
          </el-input>

          <el-button type="primary" size="small" @click="handleSearch" class="search-btn">
            <i class="fa fa-search mr-1"></i>
            搜索
          </el-button>

          <el-button size="small" @click="showAdvancedSearch = !showAdvancedSearch" class="search-btn">
            <i class="fa fa-cog mr-1"></i>
            {{ showAdvancedSearch ? "收起" : "高级" }}
          </el-button>

          <el-button type="info" size="small" @click="fetchFileList" class="search-btn">
            <i class="fa fa-sync-alt mr-1"></i>
            刷新
          </el-button>
        </div>
      </div>

      <!-- 高级搜索 -->
      <el-collapse-transition>
        <div v-show="showAdvancedSearch" class="advanced-search">
          <el-form :model="searchParams" size="small" label-width="80px">
            <div class="form-row">
              <el-form-item label="文件类型">
                <el-select v-model="searchParams.file_type" placeholder="选择文件类型" clearable style="width: 100%">
                  <el-option label="图片" value="image" />
                  <el-option label="文档" value="document" />
                  <el-option label="视频" value="video" />
                  <el-option label="音频" value="audio" />
                  <el-option label="压缩包" value="archive" />
                  <el-option label="其他" value="other" />
                </el-select>
              </el-form-item>
              <el-form-item label="扩展名">
                <el-input v-model="searchParams.file_extension" placeholder="如: jpg, pdf" clearable
                  style="width: 100%" />
              </el-form-item>
              <el-form-item label="存储类型">
                <el-select v-model="searchParams.storage_type" placeholder="存储位置" clearable style="width: 100%">
                  <el-option v-for="(label, value) in storageTypeTextMap" :key="value" :label="label"
                    :value="Number(value)" />
                </el-select>
              </el-form-item>
            </div>
            <div class="form-row">
              <el-form-item label="上传时间">
                <el-date-picker v-model="dateRange" type="daterange" range-separator="至" start-placeholder="开始日期"
                  end-placeholder="结束日期" value-format="YYYY-MM-DD" style="width: 100%" />
              </el-form-item>
              <el-form-item label="文件大小">
                <div class="size-range">
                  <el-input-number v-model="searchParams.min_size" :min="0" :step="1024" placeholder="最小"
                    controls-position="right" />
                  <span class="separator">至</span>
                  <el-input-number v-model="searchParams.max_size" :min="0" :step="1024" placeholder="最大"
                    controls-position="right" />
                </div>
              </el-form-item>
            </div>
            <div class="form-actions">
              <el-button size="small" @click="resetSearch" class="form-btn">
                <i class="fa fa-undo mr-1"></i>
                重置
              </el-button>
              <el-button type="primary" size="small" @click="handleSearch" class="form-btn">
                <i class="fa fa-search mr-1"></i>
                搜索
              </el-button>
            </div>
          </el-form>
        </div>
      </el-collapse-transition>

      <!-- 文件表格 -->
      <div class="file-table" v-loading="tableLoading">
        <el-table ref="tableRef" :key="tableKey" :data="fileList" @selection-change="handleSelectionChange"
          style="width: 100%" border row-key="file_id" highlight-current-row :header-cell-style="tableHeaderStyle"
          :cell-style="tableCellStyle">
          <el-table-column type="selection" width="40" align="center" />
          <el-table-column label="文件信息" min-width="180">
            <template #default="{ row }">
              <div class="file-item">
                <div class="file-item__icon" :class="getFileTypeClass(row.file_type)">
                  <img v-if="isImage(row.file_extension)" :src="row.http_url || getFileThumbnail(row)"
                    class="file-item__preview" />
                  <i v-else :class="getFontAwesomeIcon(row.file_type)"></i>
                </div>
                <div class="file-item__details">
                  <div class="file-item__name" :title="row.original_name">
                    {{ row.original_name }}
                  </div>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="存储名称" min-width="180" show-overflow-tooltip>
            <template #default="{ row }">
              <div class="store-name">
                <i class="fa fa-save"></i>
                <span>{{ row.store_name }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="文件大小" min-width="80" align="center">
            <template #default="{ row }">
              <span class="file-size">{{ formatFileSize(row.file_size) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="文件类型" min-width="100" align="center">
            <template #default="{ row }">
              <div class="file-type-item">
                <i :class="getFontAwesomeIcon(row.file_type)"></i>
                <span class="file-type-name">{{
                  getFileTypeName(row.file_type)
                }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="扩展名" min-width="80" align="center">
            <template #default="{ row }">
              <span class="file-ext-tag">{{
                row.file_extension.toUpperCase()
              }}</span>
            </template>
          </el-table-column>
          <el-table-column label="存储位置" min-width="100" align="center">
            <template #default="{ row }">
              <div class="storage-type" :class="getStorageClass(row.storage_type)">
                <i :class="getStorageIcon(row.storage_type)"></i>
                <span>{{ getStorageTypeName(row.storage_type) }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="文件哈希" min-width="180" show-overflow-tooltip>
            <template #default="{ row }">
              <el-tooltip :content="row.file_hash" placement="top">
                <div class="hash-info">
                  <span class="hash-algorithm">{{
                    getHashAlgorithmName(row.hash_algorithm)
                  }}</span>
                  <span class="hash-value">{{ row.file_hash.substring(0, 12) }}...</span>
                </div>
              </el-tooltip>
            </template>
          </el-table-column>
          <el-table-column label="上传时间" min-width="150" align="center">
            <template #default="{ row }">
              <span class="create-time">{{ formatDate(row.create_time) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" min-width="200" fixed="right" align="center">
            <template #default="{ row }">
              <div class="file-actions">
                <!-- 预览按钮 -->
                <el-button v-if="canPreview(row)" type="primary" size="small" text @click="handlePreview(row)"
                  class="action-btn-text">
                  <i class="fa fa-eye mr-1"></i>
                  预览
                </el-button>

                <!-- 下载按钮 -->
                <el-button type="primary" size="small" text @click="handleDownload(row)" class="action-btn-text">
                  <i class="fa fa-download mr-1"></i>
                  下载
                </el-button>

                <!-- 详情按钮 -->
                <el-button type="primary" size="small" text @click="handleDetail(row)" class="action-btn-text">
                  <i class="fa fa-info-circle mr-1"></i>
                  详情
                </el-button>

                <!-- 活跃文件的删除按钮 -->
                <el-button v-if="currentStatus === 'active'" type="danger" size="small" text @click="handleDelete(row)"
                  class="action-btn-text">
                  <i class="fa fa-trash-alt mr-1"></i>
                  删除
                </el-button>

                <!-- 已删除文件的恢复按钮 -->
                <el-button v-if="currentStatus === 'deleted'" type="warning" size="small" text
                  @click="handleRestore(row)" class="action-btn-text">
                  <i class="fa fa-undo-alt mr-1"></i>
                  恢复
                </el-button>

                <!-- 已删除文件的彻底删除按钮 -->
                <el-button v-if="currentStatus === 'deleted'" type="danger" size="small" text
                  @click="handleForceDelete(row)" class="action-btn-text">
                  <i class="fa fa-times-circle mr-1"></i>
                  彻底删除
                </el-button>
              </div>
            </template>
          </el-table-column>
        </el-table>

        <div v-if="fileList.length === 0 && !tableLoading" class="empty-state">
          <el-empty description="暂无文件数据" />
        </div>

        <div class="pagination">
          <el-pagination v-model:current-page="searchParams.page" v-model:page-size="searchParams.page_size"
            :page-sizes="[10, 20, 50, 100]" background layout="total, sizes, prev, pager, next, jumper"
            :total="totalCount" small @size-change="handleSizeChange" @current-change="handleCurrentChange" />
        </div>
      </div>
    </el-card>

    <!-- 上传进度对话框 -->
    <el-dialog v-model="uploadDialogVisible" title="文件上传进度" width="450px" align-center destroy-on-close>
      <div v-for="(item, index) in uploadFiles" :key="index" class="upload-item">
        <div class="upload-item__info">
          <el-icon>
            <Document />
          </el-icon>
          <span class="upload-item__name">{{ item.name }}</span>
        </div>
        <el-progress :percentage="item.percentage" :status="item.status" />
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="uploadDialogVisible = false" size="small">关闭</el-button>
          <el-button type="primary" @click="continueUpload" size="small">继续上传</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 文件详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="文件详情" width="800px" align-center>
      <div class="file-detail-container">
        <div class="file-detail-header" v-if="canPreview(currentFile)">
          <div class="preview-wrapper">
            <img v-if="isImage(currentFile.file_extension)" :src="currentFile.http_url" class="preview-image" />
            <video v-else-if="isVideo(currentFile.file_extension)" :src="currentFile.http_url" controls
              class="preview-video"></video>
            <audio v-else-if="isAudio(currentFile.file_extension)" :src="currentFile.http_url" controls
              class="preview-audio"></audio>
          </div>
        </div>
        <div class="file-detail-info">
          <div class="detail-item-group">
            <div class="detail-item">
              <div class="detail-label">原始文件名</div>
              <div class="detail-value">{{ currentFile.original_name }}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">文件大小</div>
              <div class="detail-value">
                {{ formatFileSize(currentFile.file_size) }}
              </div>
            </div>
          </div>

          <div class="detail-item-group">
            <div class="detail-item">
              <div class="detail-label">文件类型</div>
              <div class="detail-value">
                <div class="file-type-item">
                  <i :class="getFontAwesomeIcon(currentFile.file_type)"></i>
                  <span class="file-type-name">{{
                    getFileTypeName(currentFile.file_type)
                  }}</span>
                </div>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">文件扩展名</div>
              <div class="detail-value">
                <span class="file-ext-tag">{{
                  currentFile.file_extension?.toUpperCase()
                }}</span>
              </div>
            </div>
          </div>

          <div class="detail-item-group">
            <div class="detail-item">
              <div class="detail-label">存储位置</div>
              <div class="detail-value">
                <div class="storage-type" :class="getStorageClass(currentFile.storage_type)">
                  <i :class="getStorageIcon(currentFile.storage_type)"></i>
                  <span>{{
                    getStorageTypeName(currentFile.storage_type)
                  }}</span>
                </div>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">文件ID</div>
              <div class="detail-value id-value">{{ currentFile.file_id }}</div>
            </div>
          </div>

          <div class="detail-item full-width">
            <div class="detail-label">存储文件名</div>
            <div class="detail-value path-value">
              {{ currentFile.store_name }}
            </div>
          </div>

          <div class="detail-item full-width">
            <div class="detail-label">存储路径</div>
            <div class="detail-value path-value">
              {{ currentFile.file_path }}
            </div>
          </div>

          <div class="detail-item full-width">
            <div class="detail-label">文件哈希</div>
            <div class="detail-value">
              <div class="hash-detail">
                <span class="hash-algorithm">{{
                  getHashAlgorithmName(currentFile.hash_algorithm)
                }}</span>
                <span class="hash-value">{{ currentFile.file_hash }}</span>
              </div>
            </div>
          </div>

          <div class="detail-item-group">
            <div class="detail-item">
              <div class="detail-label">创建时间</div>
              <div class="detail-value">
                {{ formatDate(currentFile.create_time) }}
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">更新时间</div>
              <div class="detail-value">
                {{ formatDate(currentFile.update_time) }}
              </div>
            </div>
          </div>

          <div class="detail-item full-width" v-if="currentFile.http_url">
            <div class="detail-label">访问地址</div>
            <div class="detail-value link-value">
              <el-link type="primary" :href="currentFile.http_url" target="_blank">{{ currentFile.http_url }}</el-link>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="detailDialogVisible = false" size="default" class="dialog-btn">
            <i class="fa fa-times mr-1"></i>
            关闭
          </el-button>
          <el-button type="primary" @click="handleDownload(currentFile)" size="default" class="dialog-btn">
            <i class="fa fa-download mr-1"></i>
            下载文件
          </el-button>
          <el-button v-if="canPreview(currentFile)" type="success" @click="handlePreview(currentFile)" size="default"
            class="dialog-btn">
            <i class="fa fa-eye mr-1"></i>
            预览
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 文件预览对话框 -->
    <el-dialog v-model="previewDialogVisible" :title="previewFile.original_name" width="70%" align-center>
      <div class="preview-dialog">
        <img v-if="isImage(previewFile.file_extension)" :src="previewFile.http_url" class="preview-dialog__image" />
        <video v-else-if="isVideo(previewFile.file_extension)" :src="previewFile.http_url" controls
          class="preview-dialog__video"></video>
        <audio v-else-if="isAudio(previewFile.file_extension)" :src="previewFile.http_url" controls
          class="preview-dialog__audio"></audio>
        <div v-else class="preview-dialog__empty">该文件类型暂不支持预览</div>
      </div>
    </el-dialog>

    <!-- 删除确认对话框 -->
    <el-dialog v-model="deleteConfirmVisible" title="删除确认" width="400px" :close-on-click-modal="false" append-to-body>
      <div v-if="currentDeleteFile">
        <p>确定要删除文件 "{{ currentDeleteFile.original_name }}" 吗？</p>
        <div style="margin-top: 15px">
          <el-checkbox v-model="isPermanentDelete">永久删除，数据将无法恢复</el-checkbox>
        </div>
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteConfirmVisible = false" size="small" class="dialog-btn">
            <i class="fa fa-times mr-1"></i>
            取消
          </el-button>
          <el-button type="danger" @click="confirmDelete" :loading="deleteLoading" size="small" class="dialog-btn">
            <i class="fa fa-trash-alt mr-1"></i>
            确定删除
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 批量删除确认对话框 -->
    <el-dialog v-model="batchDeleteDialogVisible" title="批量删除确认" width="500px" :close-on-click-modal="false"
      append-to-body>
      <div>
        <p>
          确定要删除选中的
          <strong>{{ selectedFiles.length }}</strong> 个文件吗？
        </p>

        <!-- 正常文件的删除选项 -->
        <div v-if="currentStatus === 'active'" style="margin-top: 20px">
          <div style="margin-bottom: 15px">
            <el-checkbox v-model="batchDeleteForce">
              <span style="color: #f56c6c; font-weight: 500">永久删除（删除数据库记录和物理文件，不可恢复）</span>
            </el-checkbox>
          </div>

          <el-alert v-if="!batchDeleteForce" title="软删除：仅标记删除，可通过恢复功能找回文件" type="info" :closable="false" show-icon
            style="margin-top: 10px" />
          <el-alert v-else title="永久删除：删除数据库记录和物理文件，完全不可恢复！" type="error" :closable="false" show-icon
            style="margin-top: 10px" />
        </div>

        <!-- 已删除文件的提示 -->
        <div v-else-if="currentStatus === 'deleted'" style="margin-top: 15px">
          <el-alert title="注意：已删除的文件将被永久删除，数据无法恢复！" type="error" :closable="false" show-icon />
        </div>
      </div>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="batchDeleteDialogVisible = false" size="small" class="dialog-btn">
            <i class="fa fa-times mr-1"></i>
            取消
          </el-button>
          <el-button type="danger" @click="confirmBatchDelete" size="small" class="dialog-btn">
            <i class="fa fa-trash-alt mr-1"></i>
            {{ getDeleteButtonText() }}
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "SystemFiles",
});

import { ref, reactive, computed, onMounted, watch, nextTick } from "vue";
import { ElMessageBox, type UploadProps } from "element-plus";
import { Search } from "@element-plus/icons-vue";
import {
  getFileList,
  deleteFile,
  batchDeleteFiles,
  restoreFile,
  forceDeleteFile,
  getFileDetail,
  type FileInfo,
} from "@/api/fileManage";
import { baseUrlApi, formatFileSize, storageTypeTextMap } from "@/api/utils";
import { getToken } from "@/utils/auth";
import { message } from "@/utils/message";
import { getFingerprint } from "@/utils/fingerprint";

// 新的FileInfo已经包含http_url，不需要扩展接口

// 状态变量
const tableLoading = ref(false);
const uploadDialogVisible = ref(false);
const detailDialogVisible = ref(false);
const previewDialogVisible = ref(false);
const showAdvancedSearch = ref(false);
const uploadRef = ref();
const tableRef = ref();
const selectedFiles = ref<number[]>([]);
const uploadFiles = ref<any[]>([]);
const fileList = ref<FileInfo[]>([]);
const totalCount = ref(0);
const tableKey = ref(0); // 用于强制重新渲染表格
const currentFile = ref<FileInfo>({} as FileInfo);
const previewFile = ref<FileInfo>({} as FileInfo);
const uploadUrl = `${baseUrlApi}/upload/uploadFile`;

// 当前文件状态 - 使用计算属性确保响应式更新
const currentStatus = computed(() => searchParams.status);

// 搜索参数
const dateRange = ref<[string, string] | null>(null);
const searchParams = reactive({
  page: 1,
  page_size: 10,
  original_name: "",
  file_type: "",
  file_extension: "",
  storage_type: undefined,
  min_size: undefined,
  max_size: undefined,
  start_date: "",
  end_date: "",
  status: "active" as "active" | "deleted", // 默认显示活跃文件
  sort_field: "create_time",
  sort_order: "desc" as "asc" | "desc",
});

// 上传请求头
const uploadHeaders = computed(() => {
  const token = getToken();
  return {
    Authorization: token?.token ? `Bearer ${token.token}` : "",
  };
});

// 上传数据
const uploadData = ref({
  remark: "系统文件管理上传的文件",
  device_fingerprint: "",
});

// 初始化设备指纹
const initDeviceFingerprint = async () => {
  try {
    const fingerprint = await getFingerprint();
    uploadData.value.device_fingerprint = fingerprint;
  } catch (error) {
    console.error("获取设备指纹失败:", error);
  }
};

// 监听日期范围变化
watch(dateRange, (val) => {
  if (val) {
    searchParams.start_date = val[0];
    searchParams.end_date = val[1];
  } else {
    searchParams.start_date = "";
    searchParams.end_date = "";
  }
});

// 监听状态变化，确保界面正确更新
watch(
  () => searchParams.status,
  (newStatus, oldStatus) => {
    if (newStatus !== oldStatus) {
      // 强制重新渲染表格
      tableKey.value++;
    }
  },
  { immediate: true }
);

// 文件类型映射
const fileTypeMap = {
  image: {
    name: "图片",
    icon: "fa-image",
    class: "image-file",
  },
  video: {
    name: "视频",
    icon: "fa-video",
    class: "video-file",
  },
  audio: {
    name: "音频",
    icon: "fa-headphones",
    class: "audio-file",
  },
  document: {
    name: "文档",
    icon: "fa-file-alt",
    class: "document-file",
  },
  archive: {
    name: "压缩包",
    icon: "fa-file-archive",
    class: "archive-file",
  },
  other: {
    name: "其他",
    icon: "fa-file",
    class: "other-file",
  },
};

// 存储类型映射
const storageTypeMap = {
  0: {
    icon: "fa-server",
    class: "storage-local",
  },
  1: {
    icon: "fa-cloud",
    class: "storage-aliyun",
  },
  2: {
    icon: "fa-cloud-upload-alt",
    class: "storage-tencent",
  },
  3: {
    icon: "fa-cloud-download-alt",
    class: "storage-qiniu",
  },
};

// 哈希算法映射
const hashAlgorithmMap = {
  MD5: "MD5",
  SHA1: "SHA1",
  SHA256: "SHA256",
  CRC32: "CRC32",
};

// 获取文件类型名称
const getFileTypeName = (fileType: string): string => {
  const type = getBaseFileType(fileType);
  return fileTypeMap[type]?.name || "未知类型";
};

// 获取文件类型图标类名 (FontAwesome)
const getFontAwesomeIcon = (fileType: string): string => {
  const type = getBaseFileType(fileType);
  return `fa ${fileTypeMap[type]?.icon || "fa-file"}`;
};

// 获取文件类型CSS类
const getFileTypeClass = (fileType: string): string => {
  const type = getBaseFileType(fileType);
  return fileTypeMap[type]?.class || "other-file";
};

// 获取基本文件类型
const getBaseFileType = (fileType: string): string => {
  if (!fileType) return "other";

  if (fileType.includes("image/")) {
    return "image";
  } else if (fileType.includes("video/")) {
    return "video";
  } else if (fileType.includes("audio/")) {
    return "audio";
  } else if (
    fileType.includes("application/pdf") ||
    fileType.includes("text/") ||
    fileType.includes("application/msword") ||
    fileType.includes("application/vnd.openxmlformats-officedocument") ||
    fileType.includes("application/vnd.ms-")
  ) {
    return "document";
  } else if (
    fileType.includes("application/zip") ||
    fileType.includes("application/x-rar") ||
    fileType.includes("application/x-tar") ||
    fileType.includes("application/x-7z")
  ) {
    return "archive";
  }

  return "other";
};

// 获取存储类型图标类名 (FontAwesome)
const getStorageIcon = (type: number): string => {
  return `fa ${storageTypeMap[type]?.icon || "fa-server"}`;
};

// 获取存储类型CSS类
const getStorageClass = (type: number): string => {
  return storageTypeMap[type]?.class || "storage-local";
};

// 获取哈希算法名称
const getHashAlgorithmName = (algorithm: string): string => {
  return hashAlgorithmMap[algorithm] || algorithm || "HASH";
};

// 格式化日期
const formatDate = (dateStr: string): string => {
  if (!dateStr) return "";
  const date = new Date(dateStr);
  return date
    .toLocaleString("zh-CN", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    })
    .replace(/\//g, "-");
};

// 获取文件列表
const fetchFileList = async () => {
  tableLoading.value = true;
  try {
    const res: any = await getFileList(searchParams);
    if (res?.code === 200 && res?.data) {
      fileList.value = res.data.data || [];
      totalCount.value = res.data.total || 0;

      // 强制重新渲染表格以确保操作按钮正确显示
      tableKey.value++;
    } else {
      message(res?.msg || "获取文件列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取文件列表失败:", error);
    message("获取文件列表失败，请稍后重试", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 搜索
const handleSearch = () => {
  searchParams.page = 1;
  clearTableSelection();
  fetchFileList();
};

// 重置搜索
const resetSearch = () => {
  searchParams.original_name = "";
  searchParams.file_type = "";
  searchParams.file_extension = "";
  searchParams.storage_type = undefined;
  searchParams.min_size = undefined;
  searchParams.max_size = undefined;
  searchParams.start_date = "";
  searchParams.end_date = "";
  dateRange.value = null;
  clearTableSelection();
  handleSearch();
};

// 状态过滤
const handleStatusCommand = async (command: string) => {
  // 清除所有状态
  clearTableSelection();

  // 重置分页到第一页
  searchParams.page = 1;

  // 更新状态
  searchParams.status = command as "active" | "deleted";

  // 强制重新渲染表格
  tableKey.value++;

  // 等待DOM更新完成后再刷新文件列表
  await nextTick();

  // 强制刷新文件列表
  fetchFileList();
};

// 分页大小变化
const handleSizeChange = (val: number) => {
  searchParams.page_size = val;
  fetchFileList();
};

// 页码变化
const handleCurrentChange = (val: number) => {
  searchParams.page = val;
  fetchFileList();
};

// 选择变化
const handleSelectionChange = (selection: FileInfo[]) => {
  selectedFiles.value = selection.map((item) => item.file_id);
};

// 清除表格选择
const clearTableSelection = () => {
  if (tableRef.value) {
    tableRef.value.clearSelection();
  }
  selectedFiles.value = [];
};

// 上传前检查
const beforeUpload: UploadProps["beforeUpload"] = (file) => {
  // 检查文件大小，限制为8MB
  const isLt8M = file.size / 1024 / 1024 < 8;
  if (!isLt8M) {
    message("文件大小不能超过8MB!", { type: "error" });
    return false;
  }

  // 添加到上传文件列表
  uploadFiles.value.push({
    name: file.name,
    size: file.size,
    percentage: 0,
    status: "normal",
  });

  uploadDialogVisible.value = true;
  return true;
};

// 上传进度
const handleUploadProgress: UploadProps["onProgress"] = (event, file) => {
  const fileIndex = uploadFiles.value.findIndex(
    (item) => item.name === file.name
  );
  if (fileIndex !== -1) {
    uploadFiles.value[fileIndex].percentage = Math.round(event.percent);
  }
};

// 上传成功
const handleUploadSuccess: UploadProps["onSuccess"] = (response: any, file) => {
  const fileIndex = uploadFiles.value.findIndex(
    (item) => item.name === file.name
  );
  if (fileIndex !== -1) {
    uploadFiles.value[fileIndex].status = "success";
  }

  if (response?.code === 200) {
    // 检查是否有重复文件
    const duplicateFiles =
      response.data?.filter((item: any) => item.is_duplicate) || [];
    const newFiles =
      response.data?.filter((item: any) => !item.is_duplicate) || [];

    if (duplicateFiles.length > 0) {
      message(`文件 ${file.name} 已存在，跳过上传`, { type: "warning" });
    }
    if (newFiles.length > 0) {
      message(`文件 ${file.name} 上传成功`, { type: "success" });
    }

    // 刷新文件列表
    clearTableSelection();
    fetchFileList();
  } else {
    message(response?.msg || `文件 ${file.name} 上传失败`, { type: "error" });
  }
};

// 上传失败
const handleUploadError: UploadProps["onError"] = (error, file) => {
  const fileIndex = uploadFiles.value.findIndex(
    (item) => item.name === file.name
  );
  if (fileIndex !== -1) {
    uploadFiles.value[fileIndex].status = "exception";
  }
  console.error("文件上传失败:", error);
  message(`文件 ${file.name} 上传失败`, { type: "error" });
};

// 继续上传
const continueUpload = () => {
  uploadDialogVisible.value = false;
  uploadFiles.value = [];
};

// 获取存储类型名称
const getStorageTypeName = (type: number) => {
  return storageTypeTextMap[type] || "未知";
};

// 判断文件类型
const isImage = (extension: string) => {
  const imageExtensions = ["jpg", "jpeg", "png", "gif", "bmp", "webp"];
  return imageExtensions.includes(extension.toLowerCase());
};

const isVideo = (extension: string) => {
  const videoExtensions = ["mp4", "webm", "ogg", "mov", "avi"];
  return videoExtensions.includes(extension.toLowerCase());
};

const isAudio = (extension: string) => {
  const audioExtensions = ["mp3", "wav", "ogg", "flac"];
  return audioExtensions.includes(extension.toLowerCase());
};

// 检查是否可预览
const canPreview = (file: FileInfo) => {
  return (
    isImage(file.file_extension) ||
    isVideo(file.file_extension) ||
    isAudio(file.file_extension)
  );
};

// 获取文件缩略图
const getFileThumbnail = (file: FileInfo) => {
  // 如果有缩略图URL，返回缩略图
  if (file.http_url && isImage(file.file_extension)) {
    return file.http_url;
  }
  // 默认返回文件类型图标
  return "";
};

// 预览文件
const handlePreview = (file: FileInfo) => {
  if (!canPreview(file)) {
    message("该文件类型不支持预览", { type: "warning" });
    return;
  }

  previewFile.value = file;
  previewDialogVisible.value = true;
};

// 下载文件
const handleDownload = (file: FileInfo) => {
  if (file.http_url) {
    const a = document.createElement("a");
    a.href = file.http_url;
    a.download = file.original_name;
    a.click();
  } else {
    message("文件无法下载，URL不存在", { type: "warning" });
  }
};

// 查看文件详情
const handleDetail = async (file: FileInfo) => {
  try {
    const res: any = await getFileDetail(file.file_id);
    if (res?.code === 200) {
      currentFile.value = res.data;
      detailDialogVisible.value = true;
    } else {
      message(res?.message || "获取文件详情失败", { type: "error" });
    }
  } catch (error) {
    console.error("获取文件详情失败:", error);
    message("获取文件详情失败，请稍后重试", { type: "error" });
  }
};

// 删除文件
const deleteConfirmVisible = ref(false);
const currentDeleteFile = ref<FileInfo | null>(null);
const isPermanentDelete = ref(false);
const deleteLoading = ref(false);

const handleDelete = (file: FileInfo) => {
  currentDeleteFile.value = file;
  isPermanentDelete.value = false;
  deleteConfirmVisible.value = true;
};

// 执行删除操作
const confirmDelete = async () => {
  if (!currentDeleteFile.value) return;

  deleteLoading.value = true;
  try {
    // 根据是否永久删除调用不同的API
    const res: any = isPermanentDelete.value
      ? await forceDeleteFile(currentDeleteFile.value.file_id)
      : await deleteFile(currentDeleteFile.value.file_id);

    if (res?.code === 200) {
      message(isPermanentDelete.value ? "文件永久删除成功" : "文件删除成功", {
        type: "success",
      });
      clearTableSelection();
      fetchFileList();
      deleteConfirmVisible.value = false;
    } else {
      message(res?.message || "删除失败", { type: "error" });
    }
  } catch (error) {
    console.error("删除文件失败:", error);
    message("操作失败，请稍后重试", { type: "error" });
  } finally {
    deleteLoading.value = false;
  }
};

// 恢复文件
const handleRestore = async (file: FileInfo) => {
  try {
    const res: any = await restoreFile(file.file_id);
    if (res?.code === 200) {
      message("文件恢复成功", { type: "success" });
      clearTableSelection();
      fetchFileList();
    } else {
      message(res?.message || "文件恢复失败", { type: "error" });
    }
  } catch (error) {
    console.error("恢复文件失败:", error);
    message("文件恢复失败，请稍后重试", { type: "error" });
  }
};

// 彻底删除文件
const handleForceDelete = (file: FileInfo) => {
  ElMessageBox.confirm(
    `确定要永久删除文件 "${file.original_name}" 吗？此操作无法撤销！`,
    "永久删除确认",
    {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "error",
    }
  )
    .then(async () => {
      try {
        const res: any = await forceDeleteFile(file.file_id);
        if (res?.code === 200) {
          message("文件永久删除成功", { type: "success" });
          clearTableSelection();
          fetchFileList();
        } else {
          message(res?.message || "文件永久删除失败", { type: "error" });
        }
      } catch (error) {
        console.error("永久删除文件失败:", error);
        message("文件永久删除失败，请稍后重试", { type: "error" });
      }
    })
    .catch(() => {
      // 用户取消操作
    });
};

// 批量删除状态
const batchDeleteDialogVisible = ref(false);
const batchDeleteForce = ref(false); // 是否永久删除（删除数据库记录和物理文件）

// 批量删除
const handleBatchDelete = () => {
  if (selectedFiles.value.length === 0) {
    message("请选择要删除的文件", { type: "warning" });
    return;
  }

  console.log("当前文件状态:", searchParams.status);
  // 显示批量删除确认对话框
  batchDeleteDialogVisible.value = true;

  // 根据当前状态初始化选项
  if (currentStatus.value === "deleted") {
    // 已删除文件只能永久删除
    batchDeleteForce.value = true;
  } else {
    // 正常文件默认软删除
    batchDeleteForce.value = false;
  }
};

// 确认批量删除
const confirmBatchDelete = async () => {
  try {
    // 根据新API文档的删除模式：
    // 1. 软删除：is_force=false
    // 2. 永久删除（删除数据库记录和物理文件）：is_force=true

    const isForce = batchDeleteForce.value;

    console.log("批量删除参数详情:", {
      当前文件状态: searchParams.status,
      用户勾选永久删除: batchDeleteForce.value,
      最终is_force值: isForce,
      选中文件ID: selectedFiles.value,
      删除模式: !isForce
        ? "软删除（可恢复）"
        : "永久删除（删除数据库记录和物理文件）",
    });

    const res: any = await batchDeleteFiles(selectedFiles.value, isForce);
    if (res?.code === 200) {
      const action = isForce ? "永久删除" : "删除";
      message(`批量${action}成功`, { type: "success" });
      clearTableSelection();
      fetchFileList();
      batchDeleteDialogVisible.value = false;
    } else {
      message(res?.message || "批量删除失败", { type: "error" });
    }
  } catch (error) {
    console.error("批量删除文件失败:", error);
    message("批量删除失败，请稍后重试", { type: "error" });
  }
};

// 获取删除按钮文字
const getDeleteButtonText = () => {
  if (currentStatus.value === "deleted") {
    return "永久删除";
  }

  return batchDeleteForce.value ? "永久删除" : "删除";
};

// 表格样式
const tableHeaderStyle = {
  background: "transparent",
  color: "#303133",
  fontWeight: "600",
  fontSize: "13px",
  padding: "8px 0",
  borderBottom: "1px solid #EBEEF5",
};

const tableCellStyle = () => {
  return {
    padding: "8px",
    fontSize: "13px",
    borderBottom: "1px solid #EBEEF5",
  };
};

// 组件挂载时执行
onMounted(() => {
  fetchFileList();
  initDeviceFingerprint();
});
</script>

<style lang="scss" scoped>
.files-container {
  padding: 8px;
  height: calc(100vh - 84px);

  // 内容卡片
  .content-card {
    margin-bottom: 10px;

    :deep(.el-card__body) {
      padding: 12px;
    }
  }

  // 工具栏
  .toolbar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;

    &__actions,
    &__search {
      display: flex;
      gap: 6px;
      align-items: center;
    }

    .action-btn {
      .el-icon {
        margin-right: 4px;
      }
    }
  }

  // 高级搜索
  .advanced-search {
    background-color: transparent;
    padding: 15px;
    border-radius: 0;
    margin-bottom: 15px;
    border-bottom: 1px solid #ebeef5;
    border-top: none;
    border-left: none;
    border-right: none;

    .el-form {
      width: 100%;

      .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 12px;

        .el-form-item {
          flex: 1;
          min-width: 220px;
          margin-bottom: 0;
        }
      }
    }

    .size-range {
      display: flex;
      align-items: center;
      width: 100%;

      .el-input-number {
        width: 45%;
      }

      .separator {
        margin: 0 8px;
        color: #909399;
      }
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-top: 5px;
    }
  }

  // 文件表格
  .file-table {
    .el-table {
      margin-top: 15px;
      margin-bottom: 15px;
    }

    // 文件操作按钮
    .file-actions {
      display: flex;
      justify-content: center;
      gap: 6px;

      .el-button {
        margin-left: 0;

        &.is-circle {
          width: 26px;
          height: 26px;

          i {
            font-size: 12px;
          }
        }
      }
    }

    .file-item {
      display: flex;
      align-items: center;

      &__icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 8px;
        border-radius: 4px;
        font-size: 16px;

        &.image-file {
          color: #1890ff;
          background-color: transparent;
        }

        &.video-file {
          color: #e6a23c;
          background-color: transparent;
        }

        &.audio-file {
          color: #f56c6c;
          background-color: transparent;
        }

        &.document-file {
          color: #409eff;
          background-color: transparent;
        }

        &.archive-file {
          color: #52c41a;
          background-color: transparent;
        }

        &.other-file {
          color: #909399;
          background-color: transparent;
        }
      }

      &__preview {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 4px;
      }

      &__details {
        flex: 1;
        min-width: 0;
      }

      &__name {
        font-size: 13px;
        font-weight: 500;
        color: #303133;
        margin-bottom: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }

    // 存储名称
    .store-name {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 12px;
      color: #606266;

      i {
        color: #409eff;
        font-size: 14px;
      }

      span {
        font-family: monospace;
      }
    }

    // 文件大小
    .file-size {
      font-size: 12px;
      color: #606266;
      font-weight: 500;
      white-space: nowrap;
    }

    // 文件类型项目
    .file-type-item {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;

      i {
        font-size: 14px;

        &.fa-image {
          color: #1890ff;
        }

        &.fa-video {
          color: #e6a23c;
        }

        &.fa-headphones {
          color: #f56c6c;
        }

        &.fa-file-alt {
          color: #409eff;
        }

        &.fa-file-archive {
          color: #52c41a;
        }

        &.fa-file {
          color: #909399;
        }
      }

      .file-type-name {
        font-size: 12px;
      }
    }

    // 文件扩展名标签
    .file-ext-tag {
      display: inline-block;
      padding: 0 6px;
      height: 20px;
      line-height: 18px;
      font-size: 12px;
      color: #606266;
      background-color: transparent;
      border: 1px solid #e4e7ed;
      border-radius: 3px;
    }

    // 存储类型
    .storage-type {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      font-size: 12px;

      i {
        font-size: 13px;
      }

      &.storage-local {
        color: #409eff;
      }

      &.storage-aliyun {
        color: #67c23a;
      }

      &.storage-tencent {
        color: #e6a23c;
      }

      &.storage-qiniu {
        color: #f56c6c;
      }
    }

    // 哈希信息
    .hash-info {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 12px;
      background-color: transparent;
      padding: 0;
      border-radius: 0;
      cursor: pointer;

      .hash-algorithm {
        color: #606266;
        font-weight: 500;
        background-color: transparent;
        padding: 0;
        border-radius: 0;
      }

      .hash-value {
        color: #909399;
        font-family: "Courier New", monospace;
      }
    }

    // 创建时间
    .create-time {
      font-size: 12px;
      color: #606266;
    }

    :deep(.el-table) {
      border-radius: 4px;
      overflow: hidden;

      .el-table__header th {
        background-color: transparent;
        color: #606266;
        font-weight: 600;
      }

      .el-table__row {
        transition: all 0.2s;

        &:hover {
          background-color: rgba(240, 247, 255, 0.5) !important;
        }

        &.current-row {
          background-color: rgba(236, 245, 255, 0.5) !important;
        }
      }

      // 文件扩展名列样式
      .file-ext-tag {
        font-weight: 500;
      }

      // 哈希值列样式
      .hash-value {
        font-family: "Courier New", monospace;
      }
    }

    .empty-state {
      padding: 20px 0;
    }

    .pagination {
      margin-top: 15px;
      padding-top: 10px;
      border-top: 1px solid #ebeef5;
      display: flex;
      justify-content: flex-end;
    }
  }

  // 上传项
  .upload-item {
    margin-bottom: 14px;

    &__info {
      display: flex;
      align-items: center;
      margin-bottom: 8px;

      .el-icon {
        color: #409eff;
        margin-right: 8px;
      }

      &__name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 13px;
      }
    }
  }

  // 文件详情对话框
  :deep(.el-dialog) {
    border-radius: 8px;
    overflow: hidden;

    .el-dialog__header {
      background-color: transparent;
      padding: 12px 16px;
      margin: 0;
      border-bottom: 1px solid #e4e7ed;

      .el-dialog__title {
        font-size: 16px;
        font-weight: 600;
        color: #303133;
      }
    }

    .el-dialog__body {
      padding: 16px;
    }

    .el-dialog__footer {
      padding: 12px 16px;
      border-top: 1px solid #e4e7ed;
    }
  }

  .file-detail-container {
    display: flex;
    flex-direction: column;
    gap: 20px;

    .file-detail-header {
      display: flex;
      justify-content: center;
      margin-bottom: 10px;

      .preview-wrapper {
        max-height: 200px;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;

        .preview-image {
          max-width: 100%;
          max-height: 200px;
          object-fit: contain;
        }

        .preview-video,
        .preview-audio {
          width: 100%;
          max-height: 200px;
        }
      }
    }

    .file-detail-info {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;

      .detail-item-group {
        display: flex;
        width: 100%;
        gap: 16px;

        .detail-item {
          flex: 1;
        }
      }

      .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;

        &.full-width {
          width: 100%;
        }

        .detail-label {
          font-size: 12px;
          color: #909399;
          font-weight: 500;
        }

        .detail-value {
          font-size: 14px;
          color: #303133;
          word-break: break-word;

          &.id-value {
            font-family: "Courier New", monospace;
            font-size: 13px;
          }

          &.path-value {
            font-family: "Courier New", monospace;
            font-size: 13px;
            padding: 4px 8px;
            border: 1px solid #e4e7ed;
            border-radius: 4px;
          }

          &.link-value {
            word-break: break-all;
          }
        }
      }
    }
  }

  // 哈希详情显示
  .hash-detail {
    display: flex;
    align-items: center;
    gap: 8px;

    .hash-algorithm {
      background-color: transparent;
      color: #409eff;
      font-size: 12px;
      font-weight: 500;
      padding: 0;
      border-radius: 0;
    }

    .hash-value {
      font-family: "Courier New", monospace;
      color: #606266;
      font-size: 12px;
      background-color: transparent;
      padding: 0;
      border-radius: 0;
      border-bottom: 1px dashed #dcdfe6;
      border-left: none;
      border-right: none;
      border-top: none;
    }
  }

  // 预览对话框
  .preview-dialog {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
    background-color: transparent;
    border-radius: 0;

    &__image {
      max-width: 100%;
      max-height: 70vh;
      object-fit: contain;
    }

    &__video {
      width: 100%;
      max-height: 70vh;
    }

    &__audio {
      width: 100%;
    }

    &__empty {
      color: #909399;
      font-size: 14px;
      text-align: center;
      padding: 30px 0;
    }
  }

  // 按钮样式优化
  .toolbar-btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
  }

  .search-btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-1px);
    }
  }

  .form-btn {
    border-radius: 6px;
    font-weight: 500;
    padding: 8px 16px;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-1px);
    }
  }

  .action-btn-text {
    border-radius: 4px;
    font-weight: 500;
    padding: 4px 8px;
    margin: 0 2px;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
  }

  .status-btn {
    position: relative;

    .fa-chevron-down {
      font-size: 10px;
      opacity: 0.7;
    }
  }

  // 操作栏样式
  .file-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-wrap: wrap;
  }

  // 下拉菜单样式
  .el-dropdown-menu__item {
    display: flex;
    align-items: center;
    padding: 8px 16px;

    &.is-active {
      background-color: var(--el-color-primary-light-9);
      color: var(--el-color-primary);
    }

    .fa {
      width: 16px;
      text-align: center;
    }

    .ml-auto {
      margin-left: auto;
    }
  }

  // 对话框按钮样式
  .dialog-btn {
    border-radius: 6px;
    font-weight: 500;
    padding: 8px 16px;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
  }

  // 对话框底部样式
  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
  }
}
</style>