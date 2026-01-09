<template>
  <el-dialog
    v-model="dialogVisible"
    :title="formTitle"
    width="900px"
    :before-close="handleClose"
    destroy-on-close
    class="resource-form-dialog"
    top="5vh"
  >
    <div class="form-toolbar">
      <div class="form-actions">
        <el-button
          type="primary"
          size="default"
          class="fill-data-btn"
          @click="fillTestData"
        >
          <font-awesome-icon :icon="['fas', 'magic']" class="mr-1" />
          一键填充数据
        </el-button>
        <el-button
          type="success"
          size="default"
          :loading="submitting"
          class="submit-btn"
          @click="submitForm"
        >
          <font-awesome-icon
            :icon="['fas', isEdit ? 'save' : 'plus-circle']"
            class="mr-1"
          />
          {{ isEdit ? "更新资源" : "创建资源" }}
        </el-button>
      </div>
    </div>

    <el-scrollbar height="calc(85vh - 180px)" class="form-scrollbar">
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="90px"
        label-position="right"
        size="default"
        class="animated-form"
      >
        <!-- 文件拖拽区域 -->
        <div class="file-selector-wrapper">
          <input
            ref="fileInput"
            type="file"
            class="file-input-hidden"
            @change="handleFileInputChange"
          />

          <div
            class="file-drop-zone"
            :class="{ active: isDragging, 'has-file': fileInfo }"
            @dragover.prevent="fileDragOver"
            @dragleave.prevent="fileDragLeave"
            @drop.prevent="handleFileDrop"
          >
            <div v-if="!fileInfo" class="drop-overlay">
              <div class="drop-icon-container">
                <font-awesome-icon
                  :icon="['fas', 'cloud-upload-alt']"
                  class="drop-icon"
                />
                <div class="drop-icon-pulse" />
              </div>
              <h3 class="drop-title">释放文件以自动填充信息</h3>
            </div>

            <div v-if="!fileInfo" class="file-select-content">
              <div class="select-icon-container">
                <font-awesome-icon
                  :icon="['fas', 'file-import']"
                  class="select-icon"
                />
                <div class="select-icon-ring" />
              </div>

              <div class="select-text">
                <h3>导入文件自动填写信息</h3>
                <p>将自动提取文件名、大小、格式和哈希值</p>
              </div>

              <div class="select-actions">
                <button
                  class="select-btn primary-btn"
                  @click.prevent="triggerFileSelect"
                >
                  <font-awesome-icon :icon="['fas', 'folder-open']" />
                  <span>浏览文件</span>
                </button>
                <div class="drop-info">或将文件拖放到此处</div>
              </div>
            </div>

            <div v-else class="file-info-display">
              <!-- 文件信息左侧 -->
              <div class="file-info-main">
                <div class="file-icon" :class="getFileIconClass()">
                  <font-awesome-icon :icon="['fas', getFileTypeIcon()]" />
                  <span class="file-ext">
                    {{ fileInfo?.format.toLowerCase() }}
                  </span>
                </div>

                <div class="file-details">
                  <div class="file-name-row">
                    <div :title="fileInfo?.name" class="file-name">
                      {{ truncateFileName(fileInfo?.name, 35) }}
                    </div>
                  </div>

                  <div class="file-meta-grid">
                    <div class="meta-item">
                      <label>
                        <font-awesome-icon :icon="['fas', 'weight']" />
                        大小
                      </label>
                      <div>{{ formatFileSize(fileInfo?.size) }}</div>
                    </div>

                    <div class="meta-item">
                      <label>
                        <font-awesome-icon :icon="['fas', 'file-alt']" />
                        格式
                      </label>
                      <div>
                        <span class="format-badge">{{ fileInfo?.format }}</span>
                      </div>
                    </div>

                    <div class="meta-item">
                      <label>
                        <font-awesome-icon :icon="['fas', 'calendar-alt']" />
                        修改日期
                      </label>
                      <div>
                        {{
                          fileInfo
                            ? dayjs(fileInfo.lastModified).format(
                                "YYYY-MM-DD HH:mm"
                              )
                            : ""
                        }}
                      </div>
                    </div>
                  </div>

                  <div class="file-hash">
                    <div class="hash-header">
                      <label>
                        <font-awesome-icon :icon="['fas', 'fingerprint']" />
                        MD5哈希
                      </label>
                      <div v-if="fileHashLoading" class="hash-loading">
                        <div class="spinner" />
                        计算中...
                      </div>
                    </div>

                    <div
                      v-if="!fileHashLoading"
                      class="hash-value"
                      :class="{ copied: hasCopied }"
                      @click="copyToClipboard(form.file_hash)"
                    >
                      {{ form.file_hash || "无" }}
                      <font-awesome-icon
                        :icon="['fas', hasCopied ? 'check' : 'copy']"
                        class="hash-icon"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- 文件信息右侧操作栏 -->
              <div class="file-actions-container">
                <button
                  class="file-action-btn change-btn"
                  title="更换文件"
                  @click="triggerFileSelect"
                >
                  <font-awesome-icon :icon="['fas', 'sync']" />
                  <span>更换</span>
                </button>

                <button
                  class="file-action-btn remove-btn"
                  title="清除文件信息"
                  @click="clearFileInfo"
                >
                  <font-awesome-icon :icon="['fas', 'trash-alt']" />
                  <span>清除</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <el-tabs v-model="activeTab" type="border-card" class="custom-tabs">
          <!-- 基本信息 -->
          <el-tab-pane
            v-loading="categoryLoading"
            label="基本信息"
            name="basic"
          >
            <div class="form-section">
              <!-- 资源名称 -->
              <el-row :gutter="20">
                <el-col :span="24">
                  <el-form-item label="资源名称" prop="resource_name">
                    <el-input
                      v-model="form.resource_name"
                      placeholder="请输入资源名称"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- 分类和标签行 -->
              <el-row :gutter="20">
                <el-col :xs="24" :sm="12">
                  <el-form-item label="分类" prop="category_id">
                    <el-select
                      v-model="form.category_id"
                      placeholder="请选择分类"
                      style="width: 100%"
                    >
                      <template #prefix>
                        <font-awesome-icon
                          :icon="['fas', 'folder']"
                          class="select-prefix-icon"
                        />
                      </template>
                      <el-option
                        v-for="item in categoryOptions"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="12">
                  <el-form-item label="标签" prop="tags">
                    <el-select
                      v-model="form.tags"
                      multiple
                      collapse-tags
                      collapse-tags-tooltip
                      :max-collapse-tags="2"
                      placeholder="请选择标签"
                      style="width: 100%"
                      :max="4"
                      @change="handleTagsChange"
                    >
                      <template #prefix>
                        <font-awesome-icon
                          :icon="['fas', 'tags']"
                          class="select-prefix-icon"
                        />
                      </template>
                      <el-option
                        v-for="item in tagOptions"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                      />
                    </el-select>
                    <div class="form-tip">最多可选4个标签</div>
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- 平台和语言行 -->
              <el-row :gutter="20">
                <el-col :xs="24" :sm="12">
                  <el-form-item label="平台" prop="platform">
                    <el-select
                      v-model="form.platform"
                      placeholder="请选择平台"
                      style="width: 100%"
                    >
                      <template #prefix>
                        <font-awesome-icon
                          :icon="['fas', 'desktop']"
                          class="select-prefix-icon"
                        />
                      </template>
                      <el-option
                        v-for="item in platformOptions"
                        :key="item"
                        :label="item"
                        :value="item"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="12">
                  <el-form-item label="语言" prop="language">
                    <el-select
                      v-model="form.language"
                      placeholder="请选择语言"
                      style="width: 100%"
                    >
                      <template #prefix>
                        <font-awesome-icon
                          :icon="['fas', 'language']"
                          class="select-prefix-icon"
                        />
                      </template>
                      <el-option
                        v-for="item in languageOptions"
                        :key="item"
                        :label="item"
                        :value="item"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- Web网站链接 (仅当平台为Web网站时显示) -->
              <el-row v-if="isWebResource" :gutter="20">
                <el-col :span="24">
                  <el-form-item label="网站链接" prop="web_link">
                    <el-input
                      v-model="form.web_link"
                      placeholder="请输入网站链接 (例如: https://example.com)"
                    >
                      <template #prefix>
                        <font-awesome-icon
                          :icon="['fas', 'globe']"
                          class="input-prefix-icon"
                        />
                      </template>
                    </el-input>
                    <div class="form-tip">
                      对于Web网站类型资源，请提供完整的网站链接
                    </div>
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- 大小、时间和版本行 -->
              <el-row :gutter="20">
                <el-col :xs="24" :sm="8">
                  <el-form-item label="大小(MB)" prop="resource_size">
                    <el-input-number
                      v-model="form.resource_size"
                      :min="0"
                      :precision="2"
                      :step="0.1"
                      style="width: 100%"
                    />
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="8">
                  <el-form-item label="发布时间" prop="publish_time">
                    <el-date-picker
                      v-model="form.publish_time"
                      type="datetime"
                      placeholder="选择发布时间"
                      style="width: 100%"
                    />
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="8">
                  <el-form-item label="版本" prop="version">
                    <el-input
                      v-model="form.version"
                      placeholder="例如：v1.0.0"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- 文件格式和哈希行 -->
              <el-row :gutter="20">
                <el-col :xs="24" :sm="12">
                  <el-form-item label="文件格式" prop="file_format">
                    <el-input
                      v-model="form.file_format"
                      placeholder="例如：ZIP, RAR, EXE"
                    />
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="12">
                  <el-form-item label="文件哈希" prop="file_hash">
                    <el-input
                      v-model="form.file_hash"
                      placeholder="文件哈希值（MD5/SHA1等）"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- 封面和高级资源行 -->
              <el-row :gutter="20">
                <el-col :xs="24" :sm="12">
                  <el-form-item label="资源封面" prop="cover_url">
                    <el-upload
                      class="cover-uploader"
                      :show-file-list="false"
                      action="#"
                      :auto-upload="false"
                      :on-change="handleCoverChange"
                      :disabled="uploadLoading"
                    >
                      <div v-if="coverUrl" class="cover-preview">
                        <img :src="coverUrl" class="cover-image" />
                        <div class="cover-actions">
                          <el-button
                            type="danger"
                            circle
                            size="small"
                            @click.stop="removeCover"
                          >
                            <font-awesome-icon :icon="['fas', 'trash']" />
                          </el-button>
                        </div>
                        <div v-if="uploadLoading" class="cover-loading">
                          <el-icon class="is-loading">
                            <svg
                              viewBox="0 0 1024 1024"
                              xmlns="http://www.w3.org/2000/svg"
                              data-v-78e17ca8=""
                            >
                              <path
                                fill="currentColor"
                                d="M512 64a32 32 0 0 1 32 32v192a32 32 0 0 1-64 0V96a32 32 0 0 1 32-32zm0 640a32 32 0 0 1 32 32v192a32 32 0 1 1-64 0V736a32 32 0 0 1 32-32zm448-192a32 32 0 0 1-32 32H736a32 32 0 1 1 0-64h192a32 32 0 0 1 32 32zm-640 0a32 32 0 0 1-32 32H96a32 32 0 0 1 0-64h192a32 32 0 0 1 32 32zM195.2 195.2a32 32 0 0 1 45.248 0L376.32 331.008a32 32 0 0 1-45.248 45.248L195.2 240.448a32 32 0 0 1 0-45.248zm452.544 452.544a32 32 0 0 1 45.248 0L828.8 783.552a32 32 0 0 1-45.248 45.248L647.744 692.992a32 32 0 0 1 0-45.248zM828.8 195.264a32 32 0 0 1 0 45.184L692.992 376.32a32 32 0 0 1-45.248-45.248l135.808-135.808a32 32 0 0 1 45.248 0zm-452.544 452.48a32 32 0 0 1 0 45.248L240.448 828.8a32 32 0 0 1-45.248-45.248l135.808-135.808a32 32 0 0 1 45.248 0z"
                              />
                            </svg>
                          </el-icon>
                          <span>上传中...</span>
                        </div>
                      </div>
                      <div v-else class="cover-uploader-empty">
                        <font-awesome-icon
                          :icon="['fas', 'image']"
                          class="upload-icon"
                        />
                        <span>点击上传封面</span>
                      </div>
                    </el-upload>
                    <div class="upload-tip">
                      建议上传 300x200 像素的图片作为资源封面
                    </div>
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="12">
                  <el-form-item label="高级资源">
                    <el-switch
                      v-model="form.is_premium"
                      active-text="是"
                      inactive-text="否"
                    />
                    <div v-if="form.is_premium" class="premium-info">
                      <el-alert
                        title="高级资源提示"
                        type="warning"
                        description="高级资源仅对高级会员开放，请确保资源质量符合高级标准。"
                        :closable="false"
                        show-icon
                      />
                    </div>
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- 资源描述行 -->
              <el-row :gutter="20">
                <el-col :span="24">
                  <el-form-item label="资源描述" prop="description">
                    <el-input
                      v-model="form.description"
                      type="textarea"
                      :rows="4"
                      placeholder="请输入资源描述"
                    />
                  </el-form-item>
                </el-col>
              </el-row>
            </div>
          </el-tab-pane>

          <!-- 下载方式 (仅当平台不是Web网站时显示) -->
          <el-tab-pane v-if="!isWebResource" label="下载方式" name="download">
            <div class="download-section">
              <div class="download-method-header">
                <h3 class="section-title">
                  <font-awesome-icon :icon="['fas', 'download']" class="mr-1" />
                  下载方式设置
                </h3>
                <div class="download-actions">
                  <el-button
                    type="primary"
                    size="small"
                    class="mr-2"
                    @click="resetDownloadMethods"
                  >
                    <font-awesome-icon :icon="['fas', 'sync']" class="mr-1" />
                    重置
                  </el-button>
                  <el-button
                    type="success"
                    size="small"
                    @click="addDownloadMethod"
                  >
                    <font-awesome-icon :icon="['fas', 'plus']" class="mr-1" />
                    添加
                  </el-button>
                </div>
              </div>

              <el-empty
                v-if="downloadMethods.length === 0"
                description="暂无下载方式"
                class="download-empty"
              >
                <el-button type="primary" @click="initDefaultDownloadMethods">
                  添加默认下载方式
                </el-button>
              </el-empty>

              <div v-else class="download-methods-wrapper">
                <el-row :gutter="16">
                  <el-col
                    v-for="(method, index) in downloadMethods"
                    :key="index"
                    :xs="24"
                    :sm="24"
                    :md="8"
                    class="method-col"
                  >
                    <el-card
                      :class="['download-card', `cloud-${method.method_name}`]"
                      shadow="hover"
                    >
                      <template #header>
                        <div class="card-header">
                          <div class="method-tag-container">
                            <font-awesome-icon
                              :icon="[
                                'fas',
                                getDownloadMethodIcon(method.method_name)
                              ]"
                              class="method-icon"
                            />
                            {{ method.method_name }}
                          </div>
                          <div class="card-actions">
                            <el-button
                              type="danger"
                              circle
                              size="small"
                              @click="removeDownloadMethod(index)"
                            >
                              <font-awesome-icon :icon="['fas', 'trash']" />
                            </el-button>
                          </div>
                        </div>
                      </template>

                      <div class="card-content">
                        <el-form label-position="top" class="compact-form">
                          <el-form-item label="下载方式">
                            <el-select
                              v-model="method.method_name"
                              placeholder="请选择下载方式"
                              style="width: 100%"
                            >
                              <el-option
                                v-for="item in downloadMethodOptions"
                                :key="item"
                                :label="item"
                                :value="item"
                              />
                            </el-select>
                          </el-form-item>

                          <el-form-item label="下载链接">
                            <el-input
                              v-model="method.download_link"
                              placeholder="请输入下载链接"
                            >
                              <template #prepend>
                                <font-awesome-icon :icon="['fas', 'link']" />
                              </template>
                            </el-input>
                          </el-form-item>

                          <el-form-item label="提取码">
                            <el-input
                              v-model="method.extraction_code"
                              placeholder="请输入提取码（如有）"
                            >
                              <template #prepend>
                                <font-awesome-icon :icon="['fas', 'key']" />
                              </template>
                            </el-input>
                          </el-form-item>

                          <el-row :gutter="16">
                            <el-col :span="12">
                              <el-form-item label="排序">
                                <el-input-number
                                  v-model="method.sort_order"
                                  :min="1"
                                  :max="99"
                                  :controls="false"
                                  style="width: 100%"
                                />
                              </el-form-item>
                            </el-col>
                            <el-col :span="12">
                              <el-form-item label="状态">
                                <el-switch
                                  v-model="method.status"
                                  active-text="启用"
                                  inactive-text="禁用"
                                  style="width: 100%"
                                />
                              </el-form-item>
                            </el-col>
                          </el-row>
                        </el-form>
                      </div>
                    </el-card>
                  </el-col>
                </el-row>
              </div>
            </div>
          </el-tab-pane>
        </el-tabs>
      </el-form>
    </el-scrollbar>
  </el-dialog>
</template>

<script setup lang="ts">
import {
  ref,
  computed,
  reactive,
  watch,
  onMounted,
  nextTick,
  defineComponent
} from "vue";
import { ElMessageBox } from "element-plus";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { message } from "@/utils/message";
import { generateSerialNumbers } from "@/utils/dataUtil";
import dayjs from "dayjs";
import { uploadFile } from "@/api/upload";
import SparkMD5 from "spark-md5";
import {
  getCategoryList,
  addResource,
  updateResource,
  getResourceDetail
} from "@/api/resource";

// 定义下载方式接口
interface DownloadMethod {
  id?: number | null;
  resource_id?: number | null;
  method_name: string;
  download_link: string;
  extraction_code: string;
  status: boolean;
  sort_order: number;
}

// 定义提交到后端的下载方式接口
interface DownloadMethodSubmit {
  id?: number;
  resource_id: number;
  method_name: string;
  download_link: string;
  extraction_code: string;
  status: number;
  sort_order: number;
}

// 文件信息接口
interface FileInfoData {
  name: string;
  size: number;
  format: string;
  sizeInMb: number;
  type: string;
  lastModified: Date;
}

defineComponent({
  name: "ResourceForm"
});

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  resourceId: {
    type: Number,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(["update:visible", "refresh", "update:loading"]);

// 表单引用
const formRef = ref();
const submitting = ref(false);
const categoryLoading = ref(false);

// 对话框可见状态
const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

// 当前激活的标签页
const activeTab = ref("basic");

// 是否为编辑模式
const isEdit = computed(() => !!props.resourceId);

// 表单标题
const formTitle = computed(() => (isEdit.value ? "编辑资源" : "新增资源"));

// 封面预览
const coverUrl = ref("");

// 封面文件
const coverFile = ref<File | null>(null);
const uploadLoading = ref(false);

// 表单数据
const form = reactive({
  id: null,
  resource_name: "",
  category_id: undefined,
  platform: "",
  user_id: 1, // 默认为当前用户ID
  download_count: 0, // 初始下载次数
  view_count: 0, // 初始浏览次数
  favorite_count: 0, // 初始收藏次数
  resource_size: 0,
  version: "",
  file_format: "",
  update_time: new Date(), // 更新时间
  publish_time: new Date(), // 发布时间
  cover_url: "",
  description: "",
  file_hash: "",
  language: "",
  is_premium: false,
  tags: [],
  web_link: "" // 新增web_link字段用于Web网站类型资源
});

// 表单验证规则
const rules = {
  resource_name: [
    { required: true, message: "请输入资源名称", trigger: "blur" }
  ],
  category_id: [
    { required: true, message: "请选择资源分类", trigger: "change" }
  ],
  platform: [{ required: true, message: "请选择适用平台", trigger: "change" }],
  resource_size: [
    { required: true, message: "请输入资源大小", trigger: "blur" }
  ],
  web_link: [
    {
      required: false,
      message: "请输入有效的网站链接",
      trigger: "blur"
    },
    {
      validator: (rule, value, callback) => {
        if (form.platform === "Web网站" && !value) {
          callback(new Error("Web网站资源必须提供网站链接"));
        } else if (value && !/^https?:\/\/.+/.test(value)) {
          callback(
            new Error("请输入有效的网站链接，必须以http://或https://开头")
          );
        } else {
          callback();
        }
      },
      trigger: "blur"
    }
  ]
};

// 下载方式列表
const downloadMethods = ref<Array<DownloadMethod>>([]);

// 选项数据
const categoryOptions = ref<Array<any>>([]);
const tagOptions = ref<Array<any>>([]);
const platformOptions = ref([
  "Windows",
  "MacOS",
  "Linux",
  "Android",
  "iOS",
  "Web网站"
]);
const languageOptions = ref(["中文", "英文", "中英双语", "多语言"]);
const downloadMethodOptions = ref(["蓝奏网盘", "夸克网盘", "百度网盘"]);

// 获取下载方式对应的标签类型
const getDownloadMethodTagType = (
  methodName: string
): "success" | "warning" | "info" | "primary" | "danger" => {
  const typeMap: Record<
    string,
    "success" | "warning" | "info" | "primary" | "danger"
  > = {
    蓝奏网盘: "primary",
    夸克网盘: "warning",
    百度网盘: "success"
  };

  return typeMap[methodName] || "info";
};

// 获取下载方式对应的图标
const getDownloadMethodIcon = (methodName: string): string => {
  const iconMap: Record<string, string> = {
    蓝奏网盘: "cloud",
    夸克网盘: "hdd",
    百度网盘: "database"
  };

  return iconMap[methodName] || "link";
};

// 是否为Web网站资源
const isWebResource = computed(() => form.platform === "Web网站");

// 在平台变更时处理
watch(
  () => form.platform,
  newPlatform => {
    if (newPlatform === "Web网站") {
      // 如果选择了Web网站，清空下载方式
      downloadMethods.value = [];
      // 切换到基本信息标签页
      activeTab.value = "basic";
    } else {
      // 如果切换回其他平台且没有下载方式，初始化默认下载方式
      if (downloadMethods.value.length === 0) {
        initDefaultDownloadMethods();
      }
    }
  }
);

// 初始化数据
const initData = async () => {
  try {
    categoryLoading.value = true;

    // 获取分类和标签数据
    const categoryRes: any = await getCategoryList();
    console.log("资源表单获取到的分类原始数据:", categoryRes);

    if (categoryRes.code === 200) {
      let allCategories: any[] = [];

      // 适配不同的数据格式
      if (Array.isArray(categoryRes.data)) {
        // 如果res.data直接是数组
        allCategories = categoryRes.data;
      } else if (
        categoryRes.data &&
        categoryRes.data.list &&
        Array.isArray(categoryRes.data.list)
      ) {
        // 如果res.data.list是数组
        allCategories = categoryRes.data.list;
      } else if (categoryRes.data && typeof categoryRes.data === "object") {
        // 如果res.data是对象，尝试获取其中的数组
        const values = Object.values(categoryRes.data);
        const arrayValue = values.find(val => Array.isArray(val));
        allCategories = arrayValue ? (arrayValue as any[]) : [];
      }

      // 分类是parent_id=0的项
      categoryOptions.value = allCategories.filter(
        item => item && item.parent_id === 0
      );

      // 标签是parent_id>0的项
      tagOptions.value = allCategories.filter(
        item => item && item.parent_id > 0
      );

      console.log("处理后的分类数据:", categoryOptions.value);
      console.log("处理后的标签数据:", tagOptions.value);
    }

    // 编辑模式下获取资源详情
    if (props.resourceId) {
      const resDetail = await getResourceDetail(props.resourceId);

      if (resDetail.code === 200) {
        const resourceData = resDetail.data;

        // 填充基本信息
        Object.keys(form).forEach(key => {
          if (key in resourceData) {
            if (key === "is_premium") {
              // 将数值转换为布尔值
              form[key] = Boolean(resourceData[key]);
            } else if (key === "resource_size") {
              // 将字符串转换为数字
              form[key] = resourceData[key] ? Number(resourceData[key]) : 0;
            } else if (key === "publish_time" || key === "update_time") {
              // 日期字段处理
              form[key] = resourceData[key]
                ? new Date(resourceData[key])
                : new Date();
            } else {
              form[key] = resourceData[key];
            }
          }
        });

        // 设置标签（需要处理不同的数据结构）
        if (resourceData.tags && resourceData.tags.length > 0) {
          // 尝试从标签对象中提取id，可能在pivot中或直接在标签对象上
          form.tags = resourceData.tags
            .map(tag => {
              // 检查tag是否有id属性
              if ("id" in tag) {
                return tag.id;
              }
              // 如果没有id但有pivot，则从pivot中获取category_id
              else if (tag.pivot && tag.pivot.category_id) {
                return tag.pivot.category_id;
              }
              return null;
            })
            .filter(id => id !== null); // 过滤掉null值
        } else {
          form.tags = [];
        }

        // 设置封面URL
        if (resourceData.cover_url) {
          coverUrl.value = resourceData.cover_url;
          form.cover_url = resourceData.cover_url;
        }

        // 根据平台类型决定是否显示下载方式
        if (form.platform === "Web网站") {
          // Web网站资源不显示下载方式
          downloadMethods.value = [];
        } else {
          // 填充下载方式
          if (
            resourceData.downloadLinks &&
            resourceData.downloadLinks.length > 0
          ) {
            // 转换并使用downloadLinks数据
            downloadMethods.value = resourceData.downloadLinks.map(
              (link: any, index) => {
                // 确保所有必需的字段都存在
                const downloadMethod: DownloadMethod = {
                  id: link.id,
                  resource_id: link.resource_id,
                  method_name: link.method_name,
                  download_link: link.download_link,
                  extraction_code: link.extraction_code || "",
                  status:
                    typeof link.status !== "undefined"
                      ? Boolean(link.status)
                      : true,
                  sort_order:
                    typeof link.sort_order !== "undefined"
                      ? link.sort_order
                      : index + 1
                };
                return downloadMethod;
              }
            );
          } else {
            // 如果没有下载方式，初始化默认下载方式
            initDefaultDownloadMethods();
          }
        }
      }
    }
  } catch (error) {
    console.error("初始化数据出错:", error);
    message("获取数据失败", { type: "error" });
  } finally {
    categoryLoading.value = false;
    // 通知父组件加载完成
    emit("update:loading", false);
  }
};

// 处理表单提交
const submitForm = async () => {
  if (!formRef.value) return;

  // 表单验证
  await formRef.value.validate(async (valid, fields) => {
    if (!valid) {
      console.log("表单验证失败", fields);
      return;
    }

    // 检查是否有封面
    if (!coverUrl.value && !form.cover_url) {
      message("请上传资源封面", { type: "warning" });
      activeTab.value = "basic"; // 切换到基本信息选项卡
      return;
    }

    // Web网站类型资源需要检查web_link字段
    if (form.platform === "Web网站" && !form.web_link) {
      message("请输入网站链接", { type: "warning" });
      activeTab.value = "basic";
      return;
    }

    try {
      submitting.value = true;

      // 设置当前时间作为更新时间
      form.update_time = new Date();

      // 如果是新增，生成一个临时ID和设置发布时间
      if (!isEdit.value) {
        // 生成5位的资源ID（这只是前端临时ID，后端会替换）
        form.id = Number(generateSerialNumbers(1, 5));
        form.publish_time = new Date();
      }

      // 上传封面图片到服务器（如果有新封面）
      let coverUploadUrl = coverUrl.value;
      if (coverFile.value) {
        try {
          coverUploadUrl = await uploadCover();
        } catch (error) {
          message("封面上传失败，请重试", { type: "error" });
          return;
        }
      }

      // 构建主表数据
      const resourceData = {
        ...form,
        // 不需要转换布尔值，后端会处理
        cover_url: coverUploadUrl, // 使用上传后的URL
        // 使用dayjs格式化日期时间
        update_time: dayjs(form.update_time).format("YYYY-MM-DD HH:mm:ss"),
        publish_time: dayjs(form.publish_time).format("YYYY-MM-DD HH:mm:ss")
      };

      // 构建下载方式数据，保留id字段，将布尔值status转换为数值
      const downloadData = downloadMethods.value.map(method => {
        // 创建新对象，保持数据结构一致
        const downloadMethod: DownloadMethodSubmit = {
          method_name: method.method_name,
          download_link: method.download_link,
          extraction_code: method.extraction_code,
          status: method.status ? 1 : 0, // 将布尔值转换为数值
          sort_order: method.sort_order,
          resource_id: form.id
        };

        // 如果是新增资源，或者这个下载方式没有id（新增的下载方式）
        if (!isEdit.value || !method.id) {
          // 为新增的下载方式生成临时ID
          downloadMethod.id = Number(generateSerialNumbers(1, 5));
        } else {
          // 编辑模式，保留原有ID
          downloadMethod.id = method.id;
        }

        return downloadMethod;
      });

      // 构建标签数据，处理pivot关系
      const tagsData = form.tags.map(tagId => {
        // 为每个标签关系创建一个ID
        return {
          id: Number(generateSerialNumbers(1, 5)), // 生成临时ID
          resource_id: form.id,
          category_id: tagId,
          create_time: dayjs().format("YYYY-MM-DD HH:mm:ss")
        };
      });

      // 组装完整的提交数据
      const submitData = {
        resource: resourceData, // 主表数据
        downloadMethods: downloadData, // 下载方式表数据
        resourceTags: tagsData // 多标签表数据
      };

      // 打印提交数据，便于检查
      console.log("提交数据:", JSON.stringify(submitData, null, 2));

      // 根据是否为编辑模式调用不同的API
      const res = isEdit.value
        ? await updateResource(submitData)
        : await addResource(submitData);

      if (res.code === 200) {
        message(isEdit.value ? "更新成功" : "创建成功", { type: "success" });

        // 关闭表单
        dialogVisible.value = false;

        // 通知父组件刷新数据
        emit("refresh");
      } else {
        message(res.message || (isEdit.value ? "更新失败" : "创建失败"), {
          type: "error"
        });
      }
    } catch (error) {
      console.error("表单提交出错:", error);
      message(isEdit.value ? "更新失败" : "创建失败", { type: "error" });
    } finally {
      submitting.value = false;
    }
  });
};

// 处理对话框关闭
const handleClose = () => {
  ElMessageBox.confirm("确认关闭？未保存的数据将丢失", "提示", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "warning"
  })
    .then(() => {
      dialogVisible.value = false;
    })
    .catch(() => {});
};

// 处理封面上传
const handleCoverChange = (file: any) => {
  const isImage = file.raw.type.startsWith("image/");
  const isLt2M = file.raw.size / 1024 / 1024 < 8;

  if (!isImage) {
    message("封面文件必须是图片格式!", { type: "error" });
    return false;
  }

  if (!isLt2M) {
    message("封面图片大小不能超过 8MB!", { type: "error" });
    return false;
  }

  // 保存文件对象以便后续上传
  coverFile.value = file.raw;

  // 本地预览
  const reader = new FileReader();
  reader.onload = (e: ProgressEvent<FileReader>) => {
    if (e.target?.result) {
      coverUrl.value = e.target.result as string;
    }
  };
  reader.readAsDataURL(file.raw);
};

// 移除封面
const removeCover = e => {
  e.stopPropagation();
  coverUrl.value = "";
  form.cover_url = "";
  coverFile.value = null;
};

// 上传封面到服务器
const uploadCover = async (): Promise<string> => {
  if (!coverFile.value || !coverUrl.value.startsWith("data:")) {
    // 如果没有新文件或者封面URL已经是网络路径，直接返回当前URL
    return coverUrl.value;
  }

  try {
    uploadLoading.value = true;
    const formData = new FormData();
    formData.append("file", coverFile.value);

    // 添加备注信息
    const resourceTitle = form.resource_name || "新资源";
    const remark = `资源"${resourceTitle}"的封面图片`;
    formData.append("remark", remark);

    console.log("开始上传封面", coverFile.value);

    const res = await uploadFile(formData);
    console.log("封面上传响应:", res);

    if (res.code === 200 && res.data && res.data.length > 0) {
      // 返回上传成功的URL
      message("封面上传成功", { type: "success" });
      return res.data[0].url;
    } else {
      throw new Error(res.msg || "封面上传失败");
    }
  } catch (error) {
    console.error("封面上传错误:", error);
    throw error;
  } finally {
    uploadLoading.value = false;
  }
};

// 添加下载方式
const addDownloadMethod = () => {
  // 获取最大排序值
  const maxSortOrder =
    downloadMethods.value.length > 0
      ? Math.max(...downloadMethods.value.map(item => item.sort_order || 0))
      : 0;

  // 获取已有下载方式的第一个作为模板
  const template =
    downloadMethods.value.length > 0 ? { ...downloadMethods.value[0] } : null;

  // 创建与已有下载方式结构一致的新对象
  const newMethod = template
    ? {
        ...template,
        id: null, // 清空ID
        method_name: downloadMethodOptions.value[0],
        download_link: "",
        extraction_code: "",
        status: true,
        sort_order: maxSortOrder + 1,
        resource_id: form.id
      }
    : {
        method_name: downloadMethodOptions.value[0],
        download_link: "",
        extraction_code: "",
        status: true,
        sort_order: maxSortOrder + 1,
        resource_id: form.id
      };

  downloadMethods.value.push(newMethod);
};

// 移除下载方式
const removeDownloadMethod = index => {
  downloadMethods.value.splice(index, 1);
};

// 填充测试数据
const fillTestData = () => {
  if (isEdit.value) {
    message("编辑模式下不允许填充测试数据", { type: "warning" });
    return;
  }

  // 生成一个临时ID
  form.id = Number(generateSerialNumbers(1, 5));

  // 基本信息
  form.resource_name = "高级开发工具包 Pro V3";
  form.category_id =
    categoryOptions.value.length > 0 ? categoryOptions.value[0].id : undefined;
  form.platform = "Windows";
  form.resource_size = 156.75;
  form.version = "v3.2.1";
  form.file_format = "ZIP";
  form.publish_time = new Date();
  form.update_time = new Date();
  form.download_count = Math.floor(Math.random() * 1000); // 随机下载次数
  form.view_count = Math.floor(Math.random() * 5000); // 随机浏览次数
  form.favorite_count = Math.floor(Math.random() * 300); // 随机收藏次数
  form.file_hash = "5f4dcc3b5aa765d61d8327deb882cf99";
  form.language = "中文";
  form.is_premium = true;
  form.description =
    "这是一款功能强大的开发工具包，包含了多种实用工具和插件，适合专业开发人员使用。\n\n主要功能：\n- 代码自动补全\n- 多语言支持\n- 智能调试\n- 性能分析\n- 版本控制集成";

  // 选择标签，随机选择2-3个
  if (tagOptions.value.length > 0) {
    const tagCount = Math.min(
      Math.floor(Math.random() * 2) + 2,
      tagOptions.value.length
    );
    const shuffled = [...tagOptions.value].sort(() => 0.5 - Math.random());
    form.tags = shuffled.slice(0, tagCount).map(tag => tag.id);
  }

  // 设置封面URL - 仅用于测试数据展示
  coverUrl.value = "https://via.placeholder.com/800x450";
  // 不设置form.cover_url，让它在提交时从服务器获取

  // 设置下载方式
  downloadMethods.value = [
    {
      method_name: "百度网盘",
      download_link: "https://pan.baidu.com/s/abc123defg",
      extraction_code: "a1b2",
      status: true,
      sort_order: 1,
      resource_id: form.id
    },
    {
      method_name: "蓝奏网盘",
      download_link: "https://lanzou.com/s/dev-toolkit-pro-v3",
      extraction_code: "devp",
      status: true,
      sort_order: 2,
      resource_id: form.id
    },
    {
      method_name: "夸克网盘",
      download_link: "https://pan.quark.cn/s/123456789",
      extraction_code: "quark",
      status: true,
      sort_order: 3,
      resource_id: form.id
    }
  ];

  // 打印生成的测试数据
  const testData = {
    resource: {
      ...form
    },
    downloadMethods: downloadMethods.value,
    resourceTags: form.tags.map(tagId => ({
      category_id: tagId,
      resource_id: form.id
    }))
  };

  console.log("测试数据:", JSON.stringify(testData, null, 2));
  message("测试数据已填充", { type: "success" });
};

// 初始化默认的三种下载方式
const initDefaultDownloadMethods = () => {
  downloadMethods.value = [
    {
      id: null,
      resource_id: form.id,
      method_name: "蓝奏网盘",
      download_link: "",
      extraction_code: "",
      status: true,
      sort_order: 1
    },
    {
      id: null,
      resource_id: form.id,
      method_name: "百度网盘",
      download_link: "",
      extraction_code: "",
      status: true,
      sort_order: 2
    },
    {
      id: null,
      resource_id: form.id,
      method_name: "夸克网盘",
      download_link: "",
      extraction_code: "",
      status: true,
      sort_order: 3
    }
  ];
};

// 重置下载方式为默认的三种
const resetDownloadMethods = () => {
  ElMessageBox.confirm("确认重置下载方式？已有数据将被覆盖", "提示", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "warning"
  })
    .then(() => {
      initDefaultDownloadMethods();
      message("已重置为默认下载方式", { type: "success" });
    })
    .catch(() => {});
};

// 处理标签变更，限制最多选择4个
const handleTagsChange = (value: number[]) => {
  if (value.length > 4) {
    form.tags = value.slice(0, 4);
    message("最多只能选择4个标签", { type: "warning" });
  }
};

// 文件拖放相关状态
const isDragging = ref(false);
const fileInfo = ref<FileInfoData | null>(null);
const fileHashLoading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const hasCopied = ref(false);

// 处理文件拖拽
const fileDragOver = (e: DragEvent) => {
  isDragging.value = true;
};

const fileDragLeave = (e: DragEvent) => {
  isDragging.value = false;
};

const handleFileDrop = async (e: DragEvent) => {
  isDragging.value = false;
  if (
    !e.dataTransfer ||
    !e.dataTransfer.files ||
    e.dataTransfer.files.length === 0
  )
    return;

  const file = e.dataTransfer.files[0];
  processFileInfo(file);
};

// 截断文件名
const truncateFileName = (
  name: string | undefined,
  maxLength: number
): string => {
  if (!name) return "";
  if (name.length <= maxLength) return name;

  // 保留文件扩展名
  const lastDotIndex = name.lastIndexOf(".");
  if (lastDotIndex !== -1) {
    const ext = name.substring(lastDotIndex);
    const nameWithoutExt = name.substring(0, lastDotIndex);

    if (nameWithoutExt.length <= maxLength - ext.length - 3) return name;

    return (
      nameWithoutExt.substring(0, maxLength - ext.length - 3) + "..." + ext
    );
  }

  // 没有扩展名
  return name.substring(0, maxLength - 3) + "...";
};

// 触发文件选择器
const triggerFileSelect = () => {
  console.log("Triggering file select", fileInput.value);
  if (fileInput.value) {
    // 清空之前的值，确保onChange事件总是触发
    fileInput.value.value = "";
    fileInput.value.click();
  } else {
    console.error("File input element not found");
  }
};

// 处理文件输入变化
const handleFileInputChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    processFileInfo(file);
  }
};

// 清除文件信息
const clearFileInfo = () => {
  fileInfo.value = null;
  if (fileInput.value) {
    fileInput.value.value = "";
  }
  // 同时重置相关表单字段
  resetFormFields();
};

// 添加重置表单字段的函数
const resetFormFields = () => {
  // 重置与文件相关的字段
  form.resource_name = "";
  form.resource_size = 0;
  form.file_format = "";
  form.file_hash = "";

  // 清除封面（如果是从文件中提取的）
  if (coverUrl.value && coverUrl.value.startsWith("data:")) {
    coverUrl.value = "";
    coverFile.value = null;
  }
};

// 获取文件图标
const getFileTypeIcon = (): string => {
  if (!fileInfo.value) return "file";

  const format = fileInfo.value.format.toLowerCase();
  const type = fileInfo.value.type;

  if (type.startsWith("image/")) return "file-image";
  if (type.startsWith("video/")) return "file-video";
  if (type.startsWith("audio/")) return "file-audio";

  switch (format) {
    case "pdf":
      return "file-pdf";
    case "doc":
    case "docx":
      return "file-word";
    case "xls":
    case "xlsx":
      return "file-excel";
    case "ppt":
    case "pptx":
      return "file-powerpoint";
    case "zip":
    case "rar":
    case "7z":
      return "file-archive";
    case "txt":
      return "file-alt";
    case "html":
    case "css":
    case "js":
    case "ts":
    case "json":
    case "xml":
      return "file-code";
    default:
      return "file";
  }
};

// 获取文件图标类名
const getFileIconClass = (): string => {
  if (!fileInfo.value) return "";

  const format = fileInfo.value.format.toLowerCase();
  const type = fileInfo.value.type;

  if (type.startsWith("image/")) return "icon-image";
  if (type.startsWith("video/")) return "icon-video";
  if (type.startsWith("audio/")) return "icon-audio";

  switch (format) {
    case "pdf":
      return "icon-pdf";
    case "doc":
    case "docx":
      return "icon-word";
    case "xls":
    case "xlsx":
      return "icon-excel";
    case "ppt":
    case "pptx":
      return "icon-powerpoint";
    case "zip":
    case "rar":
    case "7z":
      return "icon-archive";
    case "txt":
      return "icon-text";
    case "html":
    case "css":
    case "js":
    case "ts":
    case "json":
    case "xml":
      return "icon-code";
    default:
      return "icon-default";
  }
};

// 计算文件哈希（MD5）
const calculateFileHash = (file: File): Promise<string> => {
  return new Promise((resolve, reject) => {
    const blobSlice =
      File.prototype.slice ||
      (File.prototype as any).mozSlice ||
      (File.prototype as any).webkitSlice;
    const chunkSize = 2097152; // 每次读取 2MB
    const chunks = Math.ceil(file.size / chunkSize);
    const spark = new SparkMD5.ArrayBuffer();
    const fileReader = new FileReader();
    let currentChunk = 0;

    fileReader.onload = function (e) {
      if (e.target?.result) {
        spark.append(e.target.result as ArrayBuffer);
        currentChunk++;

        if (currentChunk < chunks) {
          loadNext();
        } else {
          const hash = spark.end();
          resolve(hash);
        }
      }
    };

    fileReader.onerror = function () {
      reject("文件读取错误");
    };

    function loadNext() {
      const start = currentChunk * chunkSize;
      const end =
        start + chunkSize >= file.size ? file.size : start + chunkSize;
      fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
    }

    loadNext();
  });
};

// 自动填充表单
const autoFillFormWithFileInfo = () => {
  if (!fileInfo.value) return;

  // 填充文件名为资源名称（如果为空）
  if (!form.resource_name) {
    // 去除扩展名
    const nameWithoutExt = fileInfo.value.name.includes(".")
      ? fileInfo.value.name.substring(0, fileInfo.value.name.lastIndexOf("."))
      : fileInfo.value.name;
    form.resource_name = nameWithoutExt;
  }

  // 填充文件大小（确保是数字类型）
  form.resource_size = Number(fileInfo.value.sizeInMb) || 0;

  // 填充文件格式
  if (!form.file_format) {
    form.file_format = fileInfo.value.format;
  }

  // 填充发布时间为文件最后修改时间（如果为空）
  if (!form.publish_time) {
    form.publish_time = fileInfo.value.lastModified;
  }
};

// 如果是图片文件，设置为封面预览
const handleImageFileAsPreview = (file: File) => {
  // 检查文件是否是图片
  if (!file.type.startsWith("image/")) return;

  const isImage = file.type.startsWith("image/");
  const isLt8M = file.size / 1024 / 1024 < 8;

  if (!isImage) {
    message("封面文件必须是图片格式!", { type: "error" });
    return;
  }

  if (!isLt8M) {
    message("封面图片大小不能超过 8MB!", { type: "error" });
    return;
  }

  // 保存文件对象以便后续上传
  coverFile.value = file;

  // 本地预览
  const reader = new FileReader();
  reader.onload = (e: ProgressEvent<FileReader>) => {
    if (e.target?.result) {
      coverUrl.value = e.target.result as string;
    }
  };
  reader.readAsDataURL(file);
};

// 格式化文件大小显示
const formatFileSize = (size: number): string => {
  if (size < 1024) {
    return size + " B";
  } else if (size < 1024 * 1024) {
    return (size / 1024).toFixed(2) + " KB";
  } else if (size < 1024 * 1024 * 1024) {
    return (size / (1024 * 1024)).toFixed(2) + " MB";
  } else {
    return (size / (1024 * 1024 * 1024)).toFixed(2) + " GB";
  }
};

// 复制到剪贴板
const copyToClipboard = (text: string) => {
  if (!text) return;

  navigator.clipboard
    .writeText(text)
    .then(() => {
      hasCopied.value = true;
      setTimeout(() => {
        hasCopied.value = false;
      }, 2000);
    })
    .catch(err => {
      console.error("复制失败:", err);
    });
};

// 处理文件信息
const processFileInfo = async (file: File) => {
  if (!file) return;

  try {
    // 重置相关表单字段
    resetFormFields();

    // 获取文件名和扩展名
    const fileName = file.name;
    const lastDotIndex = fileName.lastIndexOf(".");
    const fileFormat =
      lastDotIndex !== -1 ? fileName.slice(lastDotIndex + 1).toUpperCase() : "";

    // 计算文件大小(MB)
    const fileSizeMB = parseFloat((file.size / (1024 * 1024)).toFixed(2));

    // 创建文件信息对象
    fileInfo.value = {
      name: fileName,
      size: file.size,
      format: fileFormat,
      sizeInMb: fileSizeMB,
      type: file.type,
      lastModified: new Date(file.lastModified)
    };

    // 自动填充表单信息
    autoFillFormWithFileInfo();

    // 如果是图片，自动设为封面
    if (file.type.startsWith("image/")) {
      handleImageFileAsPreview(file);
    }

    // 计算文件哈希
    fileHashLoading.value = true;
    form.file_hash = ""; // 清空之前的哈希值
    try {
      const fileHash = await calculateFileHash(file);
      form.file_hash = fileHash;
    } catch (error) {
      console.error("计算文件哈希失败:", error);
    } finally {
      fileHashLoading.value = false;
    }
  } catch (error) {
    console.error("处理文件信息出错:", error);
  }
};

// 监听资源ID变化重新加载数据
watch(
  () => props.resourceId,
  () => {
    if (props.visible) {
      initData();
    }
  }
);

// 监听对话框可见性
watch(
  () => props.visible,
  val => {
    if (val) {
      // 重置表单
      nextTick(() => {
        if (formRef.value) {
          formRef.value.resetFields();
        }

        // 重置数据
        if (!isEdit.value) {
          Object.keys(form).forEach(key => {
            if (
              key !== "publish_time" &&
              key !== "user_id" &&
              key !== "is_premium"
            ) {
              form[key] =
                typeof form[key] === "boolean"
                  ? false
                  : Array.isArray(form[key])
                    ? []
                    : "";
            }
          });
          form.is_premium = false;
          form.publish_time = new Date();
          coverUrl.value = "";
          // 初始化默认的三个下载方式
          initDefaultDownloadMethods();
        }

        // 加载数据
        initData();
      });

      // 重置文件信息
      fileInfo.value = null;
    }
  }
);

onMounted(() => {
  if (props.visible) {
    initData();
    // 如果是新建，初始化默认下载方式
    if (!isEdit.value) {
      initDefaultDownloadMethods();
    }
  }
});
</script>

<style lang="scss" scoped>
.resource-form-dialog {
  :deep(.el-dialog__header) {
    padding: 12px 16px;
    margin-bottom: 0;
    background-color: #f5f7fa;
    border-bottom: 1px solid #ebeef5;

    .el-dialog__title {
      font-size: 16px;
      font-weight: 500;
      color: #303133;
    }
  }

  :deep(.el-dialog__body) {
    position: relative;
    padding: 16px;
  }

  :deep(.el-dialog) {
    overflow: hidden;
    border-radius: 6px;
    box-shadow: 0 2px 12px rgb(0 0 0 / 10%);

    @media (width <= 768px) {
      width: 95% !important;
      margin: 0 auto;
    }
  }

  .form-toolbar {
    display: flex;
    justify-content: flex-end;
    padding: 0 0 12px;
    margin-bottom: 12px;
    border-bottom: 1px dashed #ebeef5;

    .form-actions {
      display: flex;
      gap: 8px;

      .el-button {
        padding: 6px 12px;
        font-size: 13px;
      }
    }
  }

  .form-scrollbar {
    :deep(.el-scrollbar__wrap) {
      overflow-x: hidden;
    }
  }

  .animated-form {
    @keyframes fade-in {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    animation: fade-in 0.2s ease;
  }

  .custom-tabs {
    margin-top: 6px;
    overflow: hidden;
    border: 1px solid #ebeef5;
    border-radius: 4px;

    :deep(.el-tabs__header) {
      margin-bottom: 0;
      background-color: #f5f7fa;
      border-bottom: 1px solid #ebeef5;

      .el-tabs__item {
        height: 36px;
        padding: 0 14px;
        font-size: 13px;
        line-height: 36px;
        color: #606266;

        &.is-active {
          font-weight: 500;
          color: var(--el-color-primary, #409eff);
        }

        &:hover:not(.is-active) {
          color: var(--el-color-primary-light-3, #79bbff);
        }
      }
    }

    :deep(.el-tabs__content) {
      padding: 14px;
      background: #fff;
    }
  }

  .form-section {
    padding: 0;

    .el-row {
      margin-bottom: 6px;
    }
  }

  :deep(.el-form) {
    .el-form-item {
      margin-bottom: 12px;

      .el-form-item__label {
        padding-right: 10px;
        padding-bottom: 0;
        font-size: 13px;
        font-weight: 500;
        line-height: 30px;
        color: #606266;
      }

      .el-form-item__content {
        .el-input__wrapper,
        .el-select .el-input__wrapper,
        .el-input-number__wrapper {
          height: 32px;
          padding: 0 11px;
          box-shadow: 0 0 0 1px #dcdfe6 inset;

          &:hover {
            box-shadow: 0 0 0 1px #c0c4cc inset;
          }

          &.is-focus {
            box-shadow: 0 0 0 1px #409eff inset;
          }
        }

        .el-input__inner {
          height: 32px;
          font-size: 13px;
        }

        .el-textarea__inner {
          font-size: 13px;
        }
      }
    }

    .form-tip {
      margin-top: 4px;
      font-size: 12px;
      line-height: 1.2;
      color: #909399;
    }
  }

  .select-prefix-icon {
    display: inline-flex;
    margin-right: 6px;
    font-size: 14px;
    color: #909399;
  }

  .download-section {
    padding: 0;

    .download-empty {
      padding: 30px 0;
    }
  }

  .download-method-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 10px;
    margin-bottom: 14px;
    border-bottom: 1px dashed #ebeef5;

    .section-title {
      margin: 0;
      font-size: 14px;
      font-weight: 500;
      color: #303133;
    }

    .download-actions {
      display: flex;
      gap: 6px;
    }

    .add-method-btn {
      display: flex;
      align-items: center;
      padding: 5px 10px;
    }
  }

  .download-methods-wrapper {
    .method-col {
      margin-bottom: 12px;
    }

    .download-card {
      height: 100%;
      overflow: hidden;
      border-top: 3px solid #e6e6e6;
      border-radius: 4px;
      box-shadow: 0 1px 3px rgb(0 0 0 / 5%);
      transition: all 0.2s ease;

      &:hover {
        box-shadow: 0 2px 8px rgb(0 0 0 / 8%);
        transform: translateY(-1px);
      }

      &.cloud-蓝奏网盘 {
        border-top-color: var(--el-color-primary, #409eff);

        .method-icon {
          color: var(--el-color-primary, #409eff);
        }
      }

      &.cloud-夸克网盘 {
        border-top-color: var(--el-color-warning, #e6a23c);

        .method-icon {
          color: var(--el-color-warning, #e6a23c);
        }
      }

      &.cloud-百度网盘 {
        border-top-color: var(--el-color-success, #67c23a);

        .method-icon {
          color: var(--el-color-success, #67c23a);
        }
      }

      :deep(.el-card__header) {
        padding: 10px 12px;
        background: #f8f9fa;
        border-bottom: 1px solid #f0f0f0;
      }

      :deep(.el-card__body) {
        padding: 12px;
        background: rgb(255 255 255 / 80%);
      }

      .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        .method-tag-container {
          display: flex;
          gap: 6px;
          align-items: center;
          padding: 2px 8px;
          font-size: 13px;
          font-weight: 500;
          background-color: rgb(0 0 0 / 3%);
          border-radius: 3px;
        }

        .method-icon {
          opacity: 0.8;
        }

        .card-actions {
          .el-button {
            transition: all 0.2s;

            &:hover {
              transform: scale(1.1);
            }
          }
        }
      }

      .card-content {
        .compact-form {
          .el-form-item {
            margin-bottom: 12px;

            &:last-child {
              margin-bottom: 0;
            }
          }

          :deep(.el-form-item__label) {
            padding-bottom: 4px;
            font-size: 12px;
            line-height: 1.3;
          }

          :deep(.el-input-group__prepend) {
            padding: 0 8px;
          }
        }
      }
    }
  }

  // 文件选择器包装
  .file-selector-wrapper {
    position: relative;
    margin-bottom: 16px;

    .file-input-hidden {
      position: absolute;
      z-index: -1;
      width: 0;
      height: 0;
      opacity: 0;
    }
  }

  // 文件拖拽区新样式
  .file-drop-zone {
    position: relative;
    display: flex;
    overflow: hidden;
    background-color: #f8fafc;
    border: 1px solid rgb(60 110 220 / 20%);
    border-radius: 10px;
    box-shadow: 0 2px 8px rgb(0 0 0 / 4%);
    transition: all 0.25s ease;

    &:hover {
      border-color: rgb(60 110 220 / 40%);
      box-shadow: 0 4px 12px rgb(0 0 0 / 6%);
    }

    &.active {
      background-color: rgb(60 110 220 / 5%);
      border-color: rgb(60 110 220 / 80%);
      box-shadow: 0 4px 15px rgb(60 110 220 / 15%);
      transform: scale(1.003);

      .drop-overlay {
        visibility: visible;
        opacity: 1;
      }
    }

    &.has-file {
      background-color: #f8fafd;
    }

    .drop-overlay {
      position: absolute;
      top: 0;
      left: 0;
      z-index: 10;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      visibility: hidden;
      background-color: rgb(60 110 220 / 5%);
      backdrop-filter: blur(2px);
      opacity: 0;
      transition: all 0.3s ease;

      .drop-icon-container {
        @keyframes pulse {
          0% {
            opacity: 0.6;
            transform: translate(-50%, -50%) scale(0.8);
          }

          70% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(1.2);
          }

          100% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(1.3);
          }
        }

        @keyframes bounce {
          0% {
            transform: translateY(-3px);
          }

          100% {
            transform: translateY(3px);
          }
        }

        position: relative;
        margin-bottom: 10px;

        .drop-icon {
          font-size: 32px;
          color: #3c6edc;
          animation: bounce 0.8s ease infinite alternate;
        }

        .drop-icon-pulse {
          position: absolute;
          top: 50%;
          left: 50%;
          width: 70px;
          height: 70px;
          background-color: rgb(60 110 220 / 12%);
          border-radius: 50%;
          transform: translate(-50%, -50%);
          animation: pulse 1.5s ease infinite;
        }
      }

      .drop-title {
        margin: 0;
        font-size: 16px;
        font-weight: 500;
        color: #3c6edc;
      }
    }

    // 文件选择内容区域
    .file-select-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100%;
      padding: 32px 20px;

      .select-icon-container {
        position: relative;
        margin-bottom: 16px;

        .select-icon {
          font-size: 38px;
          color: #3c6edc;
          opacity: 0.9;
        }

        .select-icon-ring {
          @keyframes spin {
            0% {
              transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
              transform: translate(-50%, -50%) rotate(360deg);
            }
          }

          position: absolute;
          top: 50%;
          left: 50%;
          width: 70px;
          height: 70px;
          border: 2px dashed rgb(60 110 220 / 30%);
          border-radius: 50%;
          transform: translate(-50%, -50%);
          animation: spin 15s linear infinite;
        }
      }

      .select-text {
        margin-bottom: 20px;
        text-align: center;

        h3 {
          margin: 0 0 6px;
          font-size: 16px;
          font-weight: 600;
          color: #2c3e50;
        }

        p {
          max-width: 280px;
          margin: 0;
          font-size: 14px;
          color: #64748b;
        }
      }

      .select-actions {
        display: flex;
        flex-direction: column;
        align-items: center;

        .select-btn {
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 10px 18px;
          font-size: 14px;
          font-weight: 500;
          cursor: pointer;
          border: none;
          border-radius: 6px;
          outline: none;
          transition: all 0.2s;

          &.primary-btn {
            color: white;
            background-color: #3c6edc;
            box-shadow: 0 2px 5px rgb(60 110 220 / 30%);

            svg {
              margin-right: 8px;
            }

            &:hover {
              background-color: #2a5cc9;
              box-shadow: 0 4px 8px rgb(60 110 220 / 40%);
              transform: translateY(-1px);
            }

            &:active {
              box-shadow: 0 1px 3px rgb(60 110 220 / 40%);
              transform: translateY(0);
            }
          }
        }

        .drop-info {
          margin-top: 12px;
          font-size: 13px;
          color: #64748b;
        }
      }
    }

    // 文件信息展示
    .file-info-display {
      display: flex;
      width: 100%;
      min-height: 150px;

      .file-info-main {
        display: flex;
        flex: 1;
        padding: 16px;
        background: linear-gradient(
          135deg,
          rgb(60 110 220 / 3%) 0%,
          rgb(60 110 220 / 8%) 100%
        );
        border-right: 1px solid rgb(60 110 220 / 10%);

        .file-icon {
          position: relative;
          display: flex;
          flex-shrink: 0;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          width: 60px;
          height: 75px;
          margin-right: 16px;
          background-color: white;
          border-radius: 5px;
          box-shadow: 0 3px 6px rgb(0 0 0 / 7%);

          svg {
            margin-bottom: 8px;
            font-size: 26px;
          }

          .file-ext {
            position: absolute;
            bottom: 8px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
          }

          // 文件图标颜色
          &.icon-pdf {
            color: #ff5733;
          }

          &.icon-word {
            color: #295396;
          }

          &.icon-excel {
            color: #1f7244;
          }

          &.icon-powerpoint {
            color: #d24625;
          }

          &.icon-image {
            color: #26a69a;
          }

          &.icon-video {
            color: #e53935;
          }

          &.icon-audio {
            color: #9c27b0;
          }

          &.icon-archive {
            color: #8d6e63;
          }

          &.icon-code {
            color: #42a5f5;
          }

          &.icon-text {
            color: #607d8b;
          }

          &.icon-default {
            color: #78909c;
          }
        }

        .file-details {
          display: flex;
          flex: 1;
          flex-direction: column;
          overflow: hidden;

          .file-name-row {
            margin-bottom: 10px;

            .file-name {
              overflow: hidden;
              font-size: 15px;
              font-weight: 600;
              color: #2c3e50;
              text-overflow: ellipsis;
              white-space: nowrap;
            }
          }

          .file-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;

            .meta-item {
              label {
                display: flex;
                align-items: center;
                margin-bottom: 3px;
                font-size: 12px;
                color: #64748b;

                svg {
                  width: 12px;
                  margin-right: 4px;
                }
              }

              div {
                font-size: 13px;
                color: #334155;

                .format-badge {
                  display: inline-block;
                  padding: 1px 8px;
                  font-size: 11px;
                  font-weight: 600;
                  color: #3c6edc;
                  background-color: rgb(60 110 220 / 10%);
                  border-radius: 4px;
                }
              }
            }
          }

          .file-hash {
            margin-top: auto;

            .hash-header {
              display: flex;
              align-items: center;
              justify-content: space-between;
              margin-bottom: 4px;

              label {
                display: flex;
                align-items: center;
                font-size: 12px;
                color: #64748b;

                svg {
                  width: 12px;
                  margin-right: 4px;
                }
              }

              .hash-loading {
                display: flex;
                align-items: center;
                font-size: 12px;
                color: #64748b;

                .spinner {
                  @keyframes spin {
                    to {
                      transform: rotate(360deg);
                    }
                  }

                  width: 12px;
                  height: 12px;
                  margin-right: 6px;
                  border: 2px solid rgb(60 110 220 / 20%);
                  border-top-color: #3c6edc;
                  border-radius: 50%;
                  animation: spin 1s linear infinite;
                }
              }
            }

            .hash-value {
              position: relative;
              padding: 5px 30px 5px 8px;
              overflow: hidden;
              font-family:
                SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
              font-size: 11px;
              color: #334155;
              text-overflow: ellipsis;
              white-space: nowrap;
              cursor: pointer;
              background-color: rgb(0 0 0 / 2%);
              border: 1px solid rgb(0 0 0 / 6%);
              border-radius: 4px;
              transition: all 0.2s;

              .hash-icon {
                position: absolute;
                top: 50%;
                right: 8px;
                font-size: 10px;
                color: #64748b;
                opacity: 0.7;
                transition: all 0.2s;
                transform: translateY(-50%);
              }

              &:hover {
                background-color: rgb(0 0 0 / 4%);

                .hash-icon {
                  opacity: 1;
                }
              }

              &.copied {
                color: #16a34a;
                background-color: rgb(22 163 74 / 8%);
                border-color: rgb(22 163 74 / 20%);

                .hash-icon {
                  color: #16a34a;
                  opacity: 1;
                }
              }
            }
          }
        }
      }

      .file-actions-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
        justify-content: center;
        width: 80px;
        padding: 0 10px;
        background-color: rgb(60 110 220 / 2%);

        .file-action-btn {
          display: flex;
          flex-direction: column;
          align-items: center;
          width: 100%;
          padding: 10px 0;
          color: #64748b;
          cursor: pointer;
          background: none;
          border: none;
          border-radius: 5px;
          outline: none;
          transition: all 0.2s;

          svg {
            margin-bottom: 4px;
            font-size: 16px;
          }

          span {
            font-size: 12px;
            font-weight: 500;
          }

          &:hover {
            color: #334155;
            background-color: rgb(0 0 0 / 4%);
          }

          &.change-btn:hover {
            color: #3c6edc;
            background-color: rgb(60 110 220 / 10%);
          }

          &.remove-btn:hover {
            color: #e11d48;
            background-color: rgb(225 29 72 / 10%);
          }
        }
      }
    }
  }

  .cover-uploader {
    :deep(.el-upload) {
      position: relative;
      width: 300px;
      height: 180px;
      overflow: hidden;
      cursor: pointer;
      border: 1px dashed #d9d9d9;
      border-radius: 6px;
      transition: border-color 0.3s;

      &:hover {
        border-color: #409eff;
      }
    }

    .cover-uploader-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      color: #909399;
      background-color: #f7f8fa;

      .upload-icon {
        margin-bottom: 12px;
        font-size: 36px;
        color: #c0c4cc;
      }

      span {
        font-size: 14px;
      }
    }

    .cover-preview {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      background-color: #f0f0f0;

      .cover-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }

      .cover-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 2px;
        background: rgb(0 0 0 / 50%);
        border-radius: 50%;
        opacity: 0;
        transition: opacity 0.2s;
      }

      .cover-loading {
        position: absolute;
        top: 0;
        left: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: #fff;
        background-color: rgb(0 0 0 / 50%);

        .el-icon {
          font-size: 24px;
        }
      }

      &:hover .cover-actions {
        opacity: 1;
      }
    }
  }

  .upload-tip {
    margin-top: 6px;
    font-size: 12px;
    color: #909399;
  }

  .premium-info {
    margin-top: 8px;

    :deep(.el-alert) {
      padding: 8px 10px;
      border-radius: 4px;

      .el-alert__content {
        padding: 0 8px 0 0;
      }

      .el-alert__description {
        margin: 4px 0 0;
        font-size: 12px;
      }
    }
  }
}

// 暗黑模式支持
:deep(.dark) {
  .resource-form-dialog {
    .custom-tabs {
      :deep(.el-tabs__header) {
        background: var(--el-bg-color-overlay);
      }

      :deep(.el-tabs__content) {
        background: var(--el-bg-color);
      }
    }

    .download-card {
      :deep(.el-card__header) {
        background-color: var(--el-bg-color-overlay);
      }

      :deep(.el-card__body) {
        background-color: var(--el-bg-color);
      }

      .card-header {
        .method-tag-container {
          background-color: rgb(255 255 255 / 5%);
        }
      }
    }

    .select-prefix-icon {
      color: var(--el-text-color-secondary);
    }

    .file-drop-zone {
      background-color: var(--el-bg-color-overlay);
      border-color: rgb(66 153 225 / 20%);

      &:hover {
        border-color: rgb(66 153 225 / 40%);
      }

      &.active {
        background-color: rgb(66 153 225 / 8%);
        border-color: rgb(66 153 225);
      }

      &.has-file {
        background-color: rgb(66 153 225 / 5%);
        border-color: rgb(66 153 225 / 30%);
      }

      .drop-instructions {
        .instructions-icon {
          color: var(--el-text-color-secondary);
        }

        h3 {
          color: var(--el-text-color-primary);
        }

        p {
          color: var(--el-text-color-secondary);
        }

        .drop-divider {
          &::before {
            background-color: var(--el-border-color-darker);
          }

          span {
            color: var(--el-text-color-secondary);
            background-color: var(--el-bg-color-overlay);
          }
        }
      }

      .file-info-display {
        .file-preview {
          .file-icon {
            background-color: var(--el-bg-color);
            border-color: var(--el-border-color-darker);
            box-shadow: 0 2px 8px rgb(0 0 0 / 15%);
          }
        }

        .file-details {
          .file-header {
            .file-name {
              color: var(--el-text-color-primary);
            }

            .file-actions {
              .action-btn {
                color: var(--el-text-color-secondary);
                background-color: rgb(255 255 255 / 10%);

                &:hover {
                  color: var(--el-text-color-primary);
                  background-color: rgb(255 255 255 / 15%);
                }

                &.remove-btn:hover {
                  color: #f87171;
                  background-color: rgb(220 38 38 / 20%);
                }
              }
            }
          }

          .file-meta {
            .meta-item {
              .meta-label {
                color: var(--el-text-color-secondary);
              }

              .meta-value {
                color: var(--el-text-color-primary);

                .format-badge {
                  color: #63b3ed;
                  background-color: rgb(49 130 206 / 15%);
                }

                .hash-loading {
                  color: var(--el-text-color-secondary);
                }

                .hash-result {
                  background-color: var(--el-bg-color);

                  &:hover {
                    background-color: var(--el-color-primary-light-9);
                  }
                }
              }
            }

            .data-status {
              color: #4ade80;
              background-color: rgb(22 163 74 / 10%);
              border-color: rgb(22 163 74 / 20%);
            }
          }
        }
      }
    }

    .cover-uploader {
      .cover-uploader-empty {
        background-color: var(--el-bg-color-overlay);
      }
    }
  }
}
</style>
