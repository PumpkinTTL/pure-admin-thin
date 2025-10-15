<template>
  <el-form ref="formRef" :model="form" label-width="100px" :rules="rules" v-loading="loading">
    <el-row :gutter="20">
      <el-col :span="24">
        <el-form-item label="文章标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入文章标题" clearable />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="24">
        <el-form-item label="文章副标题">
          <el-input v-model="form.subtitle" placeholder="请输入文章副标题" clearable />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="文章分类" prop="category_id">
          <el-select v-model="form.category_id" placeholder="请选择分类" clearable :loading="categoryLoading">
            <el-option v-for="item in categoryOptions" :key="item.id" :label="item.name" :value="item.id" />
          </el-select>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="文章别名" prop="slug">
          <el-input v-model="form.slug" placeholder="URL友好格式，如：my-first-article" clearable />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="文章标签" prop="tags">
          <el-select v-model="selectedTags" multiple filterable placeholder="请选择标签" style="width: 100%"
            :loading="tagsLoading">
            <el-option v-for="item in tagOptions" :key="item.id" :label="item.name" :value="item.id" />
          </el-select>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="文章状态" prop="status">
          <el-select v-model="form.status" placeholder="请选择状态">
            <el-option 
              v-for="option in ARTICLE_STATUS_OPTIONS" 
              :key="option.value" 
              :label="option.label" 
              :value="option.value" 
            />
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="24">
        <el-form-item label="文章属性">
          <div class="article-options">
            <el-checkbox v-model="form.is_top" label="置顶" />
            <el-checkbox v-model="form.is_recommend" label="推荐" />
            <el-checkbox v-model="form.is_original" label="原创" />
          </div>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20" v-if="!form.is_original">
      <el-col :span="24">
        <el-form-item label="原文链接">
          <el-input v-model="form.source_url" placeholder="请输入转载文章的原文链接" clearable />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="24">
        <el-form-item label="SEO关键词">
          <el-input v-model="form.keywords" placeholder="用逗号分隔多个关键词，如：Vue,前端,技术" clearable />
        </el-form-item>
      </el-col>
    </el-row>

    <!-- 文章权限控制 -->
    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="文章可见性" prop="visibility">
          <el-select 
            v-model="form.visibility" 
            placeholder="选择可见性"
            @change="handleVisibilityChange"
            style="width: 100%"
          >
            <el-option
              v-for="option in ARTICLE_VISIBILITY_OPTIONS"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            >
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <span>{{ option.label }}</span>
                <el-tag :type="getVisibilityTagType(option.value)" size="small">{{ option.tip }}</el-tag>
              </div>
            </el-option>
          </el-select>
        </el-form-item>
      </el-col>
      <el-col :span="12" v-if="form.visibility && form.visibility !== 'public'">
        <el-form-item label="权限说明">
          <el-alert 
            :title="getVisibilityDescription(form.visibility)" 
            :type="getVisibilityTagType(form.visibility)"
            :closable="false"
            show-icon
          />
        </el-form-item>
      </el-col>
    </el-row>

    <!-- 指定用户选择器 -->
    <el-row v-if="form.visibility === 'specific_users'" :gutter="20">
      <el-col :span="24">
        <el-form-item label="授权用户" prop="access_users">
          <el-select
            v-model="form.access_users"
            multiple
            filterable
            :loading="userLoading"
            placeholder="选择可访问的用户，可多选"
            style="width: 100%"
            clearable
          >
            <el-option
              v-for="user in userList"
              :key="user.id"
              :label="`${user.username} (ID:${user.id})`"
              :value="user.id"
            />
          </el-select>
          <div style="margin-top: 8px; font-size: 12px; color: #909399;">
            <el-icon><InfoFilled /></el-icon>
            已选择 {{ form.access_users?.length || 0 }} 个用户
          </div>
        </el-form-item>
      </el-col>
    </el-row>

    <!-- 指定角色选择器 -->
    <el-row v-if="form.visibility === 'specific_roles'" :gutter="20">
      <el-col :span="24">
        <el-form-item label="授权角色" prop="access_roles">
          <el-checkbox-group v-model="form.access_roles" :disabled="roleLoading">
            <el-checkbox
              v-for="role in roleList"
              :key="role.id"
              :label="role.id"
              border
              style="margin-right: 10px; margin-bottom: 10px;"
            >
              {{ role.name }}
            </el-checkbox>
          </el-checkbox-group>
          <div v-if="roleLoading" style="margin-top: 8px; font-size: 12px; color: #909399;">
            加载角色列表中...
          </div>
          <div v-else style="margin-top: 8px; font-size: 12px; color: #909399;">
            <el-icon><InfoFilled /></el-icon>
            已选择 {{ form.access_roles?.length || 0 }} 个角色
          </div>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row>
      <el-col :span="24">
        <el-form-item label="文章封面">
          <div class="elegant-cover-uploader">
            <!-- 封面预览区 -->
            <el-upload class="cover-preview" :auto-upload="false" :show-file-list="false" :on-change="handleCoverChange"
              :before-upload="beforeCoverUpload" :accept="COVER_UPLOAD_CONFIG.acceptFormats.join(',')">
              <div class="preview-container">
                <el-image v-if="coverUrl || form.cover_image" :src="coverUrl || form.cover_image" fit="cover"
                  class="cover-img" @error="handleCoverError" />
                <div v-else class="placeholder">
                  <el-icon>
                    <Picture />
                  </el-icon>
                  <span>点击上传封面</span>
                </div>
                <div class="hover-layer">
                  <el-icon>
                    <Camera />
                  </el-icon>
                </div>
              </div>
            </el-upload>

            <!-- 右侧操作区 -->
            <div class="controls">
              <!-- 格式提示信息 -->
              <div class="tip">
                支持jpg、png、gif、webp等格式，≤{{ COVER_UPLOAD_CONFIG.maxSize }}M<br>
                推荐尺寸：{{ COVER_UPLOAD_CONFIG.recommendedSize }}
              </div>

              <!-- 操作区域 -->
              <div class="actions">
                <!-- 文件名信息 -->
                <div v-if="coverFile" class="filename">
                  {{ coverFile.name.length > 12 ?
                    coverFile.name.substring(0, 10) + '...' +
                    coverFile.name.substring(coverFile.name.lastIndexOf('.')) :
                    coverFile.name }}
                </div>

                <!-- 删除按钮 -->
                <el-button v-if="coverUrl || form.cover_image" type="danger" size="small" circle
                  @click.stop="clearCoverFile" class="del-btn">
                  <el-icon>
                    <Delete />
                  </el-icon>
                </el-button>
              </div>
            </div>
          </div>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row>
      <el-col :span="24">
        <el-form-item label="文章内容" prop="content">
          <!-- 快捷插入按钮 -->
          <div class="quick-insert-buttons" style="margin-bottom: 8px;">
            <el-button size="small" @click="insertVideo">
              <el-icon><VideoPlay /></el-icon>
              插入视频
            </el-button>
            <el-button size="small" @click="insertAudio">
              <el-icon><Mic /></el-icon>
              插入音频
            </el-button>
            <el-tag type="info" size="small" style="margin-left: 8px;">支持B站、YouTube、直链等</el-tag>
          </div>
          
          <MdEditor 
            ref="editorRef"
            v-model="form.content" 
            :height="EDITOR_CONFIG.height" 
            :language="EDITOR_CONFIG.language" 
            :theme="EDITOR_CONFIG.theme"
            :preview-theme="EDITOR_CONFIG.previewTheme" 
            :code-theme="EDITOR_CONFIG.codeTheme" 
            :toolbars="EDITOR_TOOLBARS"
            :toolbars-exclude="[]"
            :footers="EDITOR_FOOTERS" 
            :scroll-auto="EDITOR_CONFIG.scrollAuto" 
            :auto-focus="EDITOR_CONFIG.autoFocus" 
            :auto-detect-code="EDITOR_CONFIG.autoDetectCode" 
            :tab-width="EDITOR_CONFIG.tabWidth"
            :show-code-row-number="EDITOR_CONFIG.showCodeRowNumber" 
            :preview-only="EDITOR_CONFIG.previewOnly" 
            :html-preview="EDITOR_CONFIG.htmlPreview" 
            :no-mermaid="EDITOR_CONFIG.noMermaid"
            :no-katex="EDITOR_CONFIG.noKatex" 
            :no-highlight="EDITOR_CONFIG.noHighlight" 
            :no-link-ref="EDITOR_CONFIG.noLinkRef" 
            :no-img-zoom-in="EDITOR_CONFIG.noImgZoomIn" 
            :sanitize="sanitizeConfig"
            :max-length="EDITOR_CONFIG.maxLength" 
            :auto-save="EDITOR_CONFIG.autoSave" 
            :placeholder="EDITOR_CONFIG.placeholder" 
            @change="handleContentChange"
            @onUploadImg="handleImageUpload" 
            @onSave="handleEditorSave" 
            @onFocus="handleEditorFocus"
            @onBlur="handleEditorBlur" 
            @onGetCatalog="handleGetCatalog"
          >
            <template #defToolbars>
              <button class="md-editor-toolbar-item custom-toolbar-btn" @click="insertVideo" title="插入视频">
                <el-icon :size="16"><VideoPlay /></el-icon>
              </button>
              <button class="md-editor-toolbar-item custom-toolbar-btn" @click="insertAudio" title="插入音频">
                <el-icon :size="16"><Mic /></el-icon>
              </button>
            </template>
          </MdEditor>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="24">
        <el-form-item label="文章摘要" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="根据文章内容自动生成，也可手动编辑"
            :maxlength="SUMMARY_CONFIG.manual" show-word-limit />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="24">
        <el-form-item label="AI智能摘要">
          <div class="ai-summary-container">
            <el-input v-model="form.ai_summary" type="textarea" :rows="2" :placeholder="`AI生成的摘要将显示在这里，不超过${SUMMARY_CONFIG.ai}个字符`"
              :maxlength="SUMMARY_CONFIG.ai" show-word-limit :disabled="true" />
            <div class="action-buttons">
              <el-button type="primary" @click="generateAiSummary" :loading="aiLoading" class="ai-button">
                <div class="ai-button-content">
                  <el-icon>
                    <ChatRound />
                  </el-icon>
                  <span>AI智能摘要</span>
                </div>
              </el-button>
            </div>
          </div>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="文章字数">
          <el-input v-model="form.word_count" placeholder="文章字数" :disabled="true" />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="阅读时长">
          <el-input v-model="form.read_time" placeholder="阅读时长(分钟)" :disabled="true" />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row>
      <el-col :span="24" style="text-align: right">
        <el-button @click="$emit('cancel')">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">提交</el-button>
        <el-button v-if="!props.formData?.id" type="info" @click="fillTestData">填充测试数据</el-button>
      </el-col>
    </el-row>
  </el-form>
</template>

<script lang="ts" setup>
import { ref, reactive, computed, watch, onMounted, defineEmits, defineProps, h, defineComponent } from 'vue'
import { MdEditor } from "md-editor-v3";
import "md-editor-v3/lib/style.css";
import { http } from '@/utils/http';
import { addArticle, updateArticle } from '@/api/article'
import { getCategoryList } from '@/api/category'
import { getUserList } from '@/api/user'
import { getRoleList } from '@/api/role'
import { message } from "@/utils/message";
import { ElMessageBox } from 'element-plus'
import { uploadImage } from '@/api/upload'
import {
  ChatRound, Picture, Delete, Camera, Star, ArrowRight, Plus,
  Minus, Files, InfoFilled, Paperclip, Loading, VideoPlay, Mic
} from '@element-plus/icons-vue'
import { request, wenxinRequest } from '@/api/openai'
import { ElMessage } from 'element-plus'
import { generateSerialNumbers } from '@/utils/dataUtil'
import { useUserStoreHook } from '@/store/modules/user'
// 导入工具函数和常量
import { countWords, calculateReadTime, generateSummary } from '@/utils/text'
import {
  ARTICLE_STATUS_OPTIONS,
  ARTICLE_VISIBILITY_OPTIONS,
  ARTICLE_VISIBILITY_MAP,
  EDITOR_CONFIG,
  EDITOR_TOOLBARS,
  EDITOR_FOOTERS,
  COVER_UPLOAD_CONFIG,
  MIN_CONTENT_LENGTH,
  SUMMARY_CONFIG
} from '@/constants/article'
const props = defineProps({
  formData: {
    type: Object,
    default: () => ({})
  },
  categoriesList: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['submit-success', 'cancel'])

const formRef = ref()
const editorRef = ref()
const loading = ref(false)
const submitting = ref(false)
const aiLoading = ref(false)

// 分类和标签相关
const selectedTags = ref([])
const categoriesListInternal = ref([]) // 内部分类数据存储
const categoryLoading = ref(false) // 分类加载状态
const tagsLoading = ref(false) // 标签加载状态

// 权限控制相关
const userList = ref([]) // 用户列表
const roleList = ref([]) // 角色列表
const userLoading = ref(false) // 用户加载状态
const roleLoading = ref(false) // 角色加载状态

// 计算属性：优先使用props传入的分类数据，否则使用内部请求的数据
const categoriesList = computed(() => {
  return props.categoriesList && props.categoriesList.length > 0 
    ? props.categoriesList 
    : categoriesListInternal.value
})

// 计算属性：文章分类选项（只显示大类别，parent_id === 0）
const categoryOptions = computed(() => {
  const list = categoriesList.value || []
  return list.filter(item => item.parent_id === 0)
})

// 计算属性：文章标签选项（只显示标签，parent_id !== 0）
const tagOptions = computed(() => {
  const list = categoriesList.value || []
  return list.filter(item => item.parent_id !== 0)
})

// 获取分类列表（包含可作为标签的分类）
// 只在props没有提供分类数据时才请求
const fetchCategories = async () => {
  // 如果props已经传入分类数据，则不需要请求
  if (props.categoriesList && props.categoriesList.length > 0) {
    console.log('使用父组件传入的分类数据，跳过请求')
    // 如果是编辑模式，设置已选标签
    if (props.formData?.tags?.length) {
      selectedTags.value = props.formData.tags.map(tag => tag.pivot.category_id)
    }
    return
  }

  try {
    categoryLoading.value = true
    tagsLoading.value = true
    const res: any = await getCategoryList({ page_size: 200 }) // 获取更多数据
    if (res && res.code === 200 && res.data) {
      // 新的API返回格式：res.data.list
      categoriesListInternal.value = res.data.list || res.data

      // 如果是编辑模式，设置已选标签
      if (props.formData?.tags?.length) {
        selectedTags.value = props.formData.tags.map(tag => tag.pivot.category_id)
      }
    }
  } catch (error) {
    console.error('获取分类/标签列表失败:', error)
    // 如果获取失败，提供一些默认数据以便测试
    if (categoriesListInternal.value.length === 0) {
      categoriesListInternal.value = [
        { id: 1, name: '技术', parent_id: 0 },
        { id: 2, name: '生活', parent_id: 0 },
        { id: 3, name: 'Vue', parent_id: 1 }
      ]
    }
    message('获取分类/标签列表失败，已使用默认数据', { type: 'warning' })
  } finally {
    categoryLoading.value = false
    tagsLoading.value = false
  }
}

// 表单数据 - 使用一个默认空对象，之后在onMounted中用Object.assign填充
const form = reactive({
  id: null,
  title: '',
  subtitle: '',
  content: '',
  ai_summary: '',
  html_content: '',
  cover_image: '',
  author_id: 1, // 默认为当前用户ID
  category_id: '',
  status: 0, // 默认为草稿
  is_top: false,
  is_recommend: false,
  is_original: true, // 默认为原创
  source_url: '',
  slug: '',
  keywords: '',
  description: '',
  word_count: 0,
  read_time: 0,
  // 权限相关字段
  visibility: 'public', // 默认公开
  access_users: [], // 授权用户ID数组
  access_roles: [] // 授权角色ID数组
})

// 表单验证规则
const rules = reactive({
  title: [{ required: true, message: '请输入文章标题', trigger: 'blur' }],
  category_id: [{ required: true, message: '请选择文章分类', trigger: 'change' }],
  content: [{ required: true, message: '请输入文章内容', trigger: 'blur' }],
  slug: [{ required: true, message: '请输入文章别名', trigger: 'blur' }]
})

// 可见性变化处理方法
const handleVisibilityChange = (value: string) => {
  // 当可见性不是指定用户或角色时，清空相关数据
  if (value !== 'specific_users') {
    form.access_users = []
  }
  if (value !== 'specific_roles') {
    form.access_roles = []
  }
  
  // 加载用户或角色列表
  if (value === 'specific_users' && userList.value.length === 0) {
    fetchUsers()
  }
  if (value === 'specific_roles' && roleList.value.length === 0) {
    fetchRoles()
  }
}

// 查询用户列表
const fetchUsers = async () => {
  try {
    userLoading.value = true
    const res: any = await getUserList({ page_size: 200 })
    if (res && res.code === 200 && res.data) {
      userList.value = res.data.list || []
    }
  } catch (error) {
    console.error('获取用户列表失败:', error)
    message('获取用户列表失败', { type: 'error' })
  } finally {
    userLoading.value = false
  }
}

// 查询角色列表
const fetchRoles = async () => {
  try {
    roleLoading.value = true
    const res: any = await getRoleList({ page_size: 200 })
    if (res && res.code === 200 && res.data) {
      roleList.value = res.data.list || []
    }
  } catch (error) {
    console.error('获取角色列表失败:', error)
    message('获取角色列表失败', { type: 'error' })
  } finally {
    roleLoading.value = false
  }
}

// 获取可见性标签颜色
const getVisibilityTagType = (visibility: string) => {
  const typeMap = {
    'public': '',
    'private': 'info',
    'password': 'warning',
    'login_required': 'info',
    'specific_users': 'success',
    'specific_roles': 'primary'
  }
  return typeMap[visibility] || ''
}

// 获取可见性说明文字
const getVisibilityDescription = (visibility: string) => {
  const descMap = {
    'public': '任何人都可以查看此文章',
    'private': '只有作者本人可以查看此文章',
    'password': '需要输入密码才能查看此文章',
    'login_required': '只有登录用户可以查看此文章',
    'specific_users': '只有指定的用户可以查看此文章',
    'specific_roles': '只有指定角色的用户可以查看此文章'
  }
  return descMap[visibility] || ''
}

// 封面图片处理相关变量和方法
const coverFile = ref(null)
const coverUrl = ref('')

// 处理封面图片上传
const handleCoverChange = (file) => {
  coverFile.value = file.raw
  if (coverFile.value) {
    coverUrl.value = URL.createObjectURL(coverFile.value)
  }
}

// 验证封面图片
const beforeCoverUpload = (file) => {
  const isValidFormat = COVER_UPLOAD_CONFIG.acceptExtensions.test(file.name)
  const isLt5M = file.size / 1024 / 1024 < COVER_UPLOAD_CONFIG.maxSize

  if (!isValidFormat) {
    message('上传封面图片格式不正确！', { type: 'error' })
    return false
  }
  if (!isLt5M) {
    message(`上传封面图片大小不能超过 ${COVER_UPLOAD_CONFIG.maxSize}MB！`, { type: 'error' })
    return false
  }
  return true
}

// 清除封面图片
const clearCoverFile = () => {
  coverFile.value = null
  coverUrl.value = ''
  form.cover_image = ''
}

// 封面图片加载错误处理
const handleCoverError = () => {
  message('封面图片加载失败', { type: 'warning' })
}

/**
 * md-editor-v3 的 sanitize 配置
 * 直接返回 HTML，绕过过滤，允许 iframe、video、audio 等标签
 */
const sanitizeConfig = (html: string) => {
  return html;
}

/**
 * 在光标位置插入内容
 */
const insertAtCursor = (text: string) => {
  const textarea = document.querySelector('.md-editor-input-wrapper textarea') as HTMLTextAreaElement;
  if (!textarea) {
    // 如果找不到textarea，就插入到文末
    form.content = form.content + '\n\n' + text + '\n\n';
    return;
  }
  
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const content = form.content;
  
  // 在光标位置插入
  const before = content.substring(0, start);
  const after = content.substring(end);
  form.content = before + '\n\n' + text + '\n\n' + after;
  
  // 设置光标位置到插入内容之后
  setTimeout(() => {
    const newPosition = start + text.length + 4; // +4 因为有两个\n\n
    textarea.focus();
    textarea.setSelectionRange(newPosition, newPosition);
  }, 100);
}

/**
 * 插入视频的函数
 */
const insertVideo = () => {
  ElMessageBox.prompt('请输入视频URL（支持B站、YouTube等平台的嵌入链接，或直接视频文件URL）', '插入视频', {
    confirmButtonText: '插入',
    cancelButtonText: '取消',
    inputPlaceholder: '例：https://www.example.com/video.mp4',
    inputValidator: (value) => {
      if (!value || !value.trim()) {
        return '请输入视频URL';
      }
      return true;
    }
  }).then(({ value }) => {
    const url = value.trim();
    let videoCode = '';
    
    // 判断是 B站视频
    if (url.includes('bilibili.com')) {
      // 提取 BV 号或嵌入代码
      if (url.includes('player.bilibili.com')) {
        videoCode = `<iframe src="${url}" width="100%" height="500" frameborder="0" allowfullscreen></iframe>`;
      } else {
        const bvMatch = url.match(/BV[a-zA-Z0-9]+/);
        if (bvMatch) {
          videoCode = `<iframe src="//player.bilibili.com/player.html?bvid=${bvMatch[0]}" width="100%" height="500" frameborder="0" allowfullscreen></iframe>`;
        }
      }
    }
    // 判断是 YouTube 视频
    else if (url.includes('youtube.com') || url.includes('youtu.be')) {
      const videoIdMatch = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/);
      if (videoIdMatch && videoIdMatch[1]) {
        videoCode = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${videoIdMatch[1]}" frameborder="0" allowfullscreen></iframe>`;
      }
    }
    // 否则使用 HTML5 video 标签
    else {
      videoCode = `<video controls width="100%">\n  <source src="${url}" type="video/mp4">\n  您的浏览器不支持 video 标签。\n</video>`;
    }
    
    if (videoCode) {
      // 插入到光标位置
      insertAtCursor(videoCode);
      message('视频插入成功', { type: 'success' });
    } else {
      message('视频URL格式不正确', { type: 'error' });
    }
  }).catch(() => {
    // 用户取消
  });
}

/**
 * 插入音频的函数
 */
const insertAudio = () => {
  ElMessageBox.prompt('请输入音频URL', '插入音频', {
    confirmButtonText: '插入',
    cancelButtonText: '取消',
    inputPlaceholder: '例：https://www.example.com/audio.mp3',
    inputValidator: (value) => {
      if (!value || !value.trim()) {
        return '请输入音频URL';
      }
      return true;
    }
  }).then(({ value }) => {
    const url = value.trim();
    const audioCode = `<audio controls>\n  <source src="${url}" type="audio/mpeg">\n  您的浏览器不支持 audio 标签。\n</audio>`;
    
    // 插入到光标位置
    insertAtCursor(audioCode);
    message('音频插入成功', { type: 'success' });
  }).catch(() => {
    // 用户取消
  });
}



// 处理编辑器图片上传
const handleImageUpload = async (files: File[], callback: (urls: string[]) => void) => {
  try {
    // 获取当前登录用户的ID
    const currentUserId = useUserStoreHook().id;

    // 生成备注信息
    const articleTitle = form.title || '新文章';
    const remark = `文章"${articleTitle}"的引用图片`;

    const uploadPromises = files.map(async (file) => {
      try {
        const result = await uploadImage(file, {
          userId: currentUserId || form.author_id || 1,
          remark: remark
        })
        // 从API响应中获取第一个文件的URL
        return result.data && result.data.length > 0 ? result.data[0].url : null
      } catch (error: any) {
        console.error('图片上传失败:', error)
        const errorMsg = error.response?.data?.msg || error.message || '上传失败';
        message(`图片 "${file.name}" 上传失败: ${errorMsg}`, { type: 'error' })
        return null
      }
    })

    const results = await Promise.all(uploadPromises)
    const successUrls = results.filter(url => url !== null)

    if (successUrls.length > 0) {
      callback(successUrls)
      message(`成功上传 ${successUrls.length} 张图片`, { type: 'success' })
    } else if (files.length > 0) {
      message('所有图片上传失败，请检查图片格式和大小', { type: 'error' })
    }
  } catch (error: any) {
    console.error('批量图片上传失败:', error)
    const errorMsg = error.response?.data?.msg || error.message || '图片上传失败';
    message(errorMsg, { type: 'error' })
  }
}

// 编辑器保存事件
const handleEditorSave = (value: string, html: string) => {
  console.log('编辑器保存:', { value, html })
  // 可以在这里实现自动保存功能
  message('内容已自动保存', { type: 'success' })
}

// 编辑器获得焦点事件
const handleEditorFocus = (event: FocusEvent) => {
  console.log('编辑器获得焦点:', event)
}

// 编辑器失去焦点事件
const handleEditorBlur = (event: FocusEvent) => {
  console.log('编辑器失去焦点:', event)
}

// 获取目录结构事件
const handleGetCatalog = (list: any[]) => {
  console.log('文章目录结构:', list)
  // 可以在这里处理目录结构，比如显示在侧边栏
}

// 内容变化时计算字数和阅读时间，并自动生成摘要
const handleContentChange = (content: string) => {
  // 使用工具函数计算字数和阅读时间
  form.word_count = countWords(content);
  form.read_time = calculateReadTime(content);

  // 文章内容超过最小长度且用户未手动编辑摘要时，自动生成摘要
  if (content.length > MIN_CONTENT_LENGTH.summary && !userEditedSummary.value) {
    autoGenerateSummary(content);
  }
}

// 跟踪用户是否手动编辑过摘要
const userEditedSummary = ref(false);

// 监听用户对摘要的编辑
watch(() => form.description, () => {
  userEditedSummary.value = true;
}, { immediate: false });

// 自动生成摘要（简单版，不调用API）
const autoGenerateSummary = (content: string) => {
  // 使用工具函数生成摘要
  form.description = generateSummary(content, SUMMARY_CONFIG.auto);
}

// 生成AI摘要
const generateAiSummary = async () => {
  if (!form.content) {
    message('请先输入文章内容再生成摘要', { type: 'warning' });
    return;
  }

  if (form.content.length < MIN_CONTENT_LENGTH.aiSummary) {
    message(`文章内容过短，请输入至少${MIN_CONTENT_LENGTH.aiSummary}个字符后再生成摘要`, { type: 'warning' });
    return;
  }

  try {
    aiLoading.value = true;

    // 准备提示词
    const prompt = `请基于以下文章内容，生成一个高质量的摘要，要求：
1. 摘要字数控制在${SUMMARY_CONFIG.ai}字以内
2. 准确提炼文章核心观点
3. 保留文章主要信息点
4. 语言简洁、流畅、有吸引力

文章内容如下：
${form.content}`;

    // 调用DeepSeek API生成摘要
    const summary = await request(prompt);

    if (!summary || summary.trim() === '') {
      throw new Error('AI返回的摘要为空');
    }

    // 将AI摘要设置到表单
    form.ai_summary = summary;

    // 同时更新普通摘要
    form.description = summary;

    // 重置用户编辑标志
    userEditedSummary.value = false;

    message('AI智能摘要生成成功', { type: 'success' });
  } catch (error: any) {
    console.error('生成AI摘要失败:', error);
    const errorMsg = error.response?.data?.msg || error.message || '生成AI摘要失败';
    message(`${errorMsg}，请稍后重试`, { type: 'error' });
  } finally {
    aiLoading.value = false;
  }
}

// 表单提交
const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    submitting.value = true;

    // 表单验证
    await formRef.value.validate();

    // 如果选择了新封面，需要先上传
    if (coverFile.value) {
      try {
        // 获取当前登录用户的ID
        const coverUploadUserId = useUserStoreHook().id;

        // 生成封面图片的备注信息
        const articleTitle = form.title || '新文章';
        const coverRemark = `文章"${articleTitle}"的封面图片`;

        // 创建FormData
        const formData = new FormData()
        formData.append('file', coverFile.value)
        // 添加用户ID参数
        formData.append('userId', String(coverUploadUserId || form.author_id || 1))
        // 添加备注参数
        formData.append('remark', coverRemark)

        // 调用上传API
        const res = await http.request<{
          code: number;
          msg: string;
          data: Array<{
            original_name: string;
            save_path: string;
            file_type: string;
            size: number;
            url: string;
          }>;
        }>("post", "/api/v1/upload/uploadFile", {
          data: formData,
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        // 检查上传结果
        if (res.code !== 200 || !res.data || !res.data.length) {
          throw new Error(res.msg || '封面上传失败')
        }

        // 更新封面图片URL
        form.cover_image = res.data[0].url
      } catch (error) {
        console.error('封面上传失败:', error)
        message('封面上传失败: ' + (error.message || '请重试'), { type: 'error' })
        return
      }
    }

    // 获取当前登录用户的ID
    const currentUserId = useUserStoreHook().id;

    // 处理布尔值转换为0/1
    const submitData = {
      ...form,
      author_id: currentUserId || form.author_id || 1, // 确保使用当前登录用户的ID
      is_top: form.is_top ? 1 : 0,
      is_recommend: form.is_recommend ? 1 : 0,
      is_original: form.is_original ? 1 : 0
    };

    // 生成或更新文章链接
    const id = form.id || generateSerialNumbers(1, 5);
    // 生成文章URL
    submitData.source_url = `https://pumpkin.top/article/detail/${id}`;

    // 处理标签数据 - 标签与分类共用同一个数据源
    const articleTagRelations = selectedTags.value.map(categoryId => ({
      article_id: id, // 使用上面生成的ID
      category_id: categoryId,
      id: generateSerialNumbers(1, 5)
    }));

    // 提交标签关联信息
    const finalSubmitData = {
      ...submitData,
      tags: articleTagRelations
    };

    // 判断是新增还是编辑
    const isEdit = !!props.formData.id;
    let res: any;

    if (isEdit) {
      // 编辑模式：使用updateArticle，确保包含ID
      finalSubmitData.id = props.formData.id;
      res = await updateArticle(finalSubmitData);
      console.log('更新文章数据:', finalSubmitData);
    } else {
      // 新增模式：使用addArticle，生成新ID
      finalSubmitData.id = id;
      res = await addArticle(finalSubmitData);
      console.log('新增文章数据:', finalSubmitData);
    }

    if (res.code !== 200) {
      message(res.msg, { type: 'error' });
      return;
    }

    message('操作成功', { type: 'success' });
    emit('submit-success');

  } catch (error: any) {
    console.error('表单提交失败:', error);
    // 更详细的错误信息
    let errorMsg = '表单验证失败';
    
    if (error.response?.data?.msg) {
      errorMsg = error.response.data.msg;
    } else if (error.message) {
      errorMsg = error.message;
    } else if (typeof error === 'string') {
      errorMsg = error;
    }
    
    message(errorMsg, { type: 'error' });
  } finally {
    submitting.value = false;
  }
}

// 添加onMounted生命周期钩子，初始化表单数据
onMounted(() => {
  // 获取分类和标签列表
  fetchCategories();

  // 设置当前登录用户的ID作为作者ID（新增文章时）
  const currentUserId = useUserStoreHook().id;
  if (currentUserId && (!props.formData || !props.formData.id)) {
    form.author_id = currentUserId;
  }

  // 初始化表单数据（使用Object.assign简化代码）
  if (props.formData && Object.keys(props.formData).length > 0) {
    // 处理布尔值字段
    const formDataProcessed = {
      ...props.formData,
      is_top: props.formData.is_top === 1,
      is_recommend: props.formData.is_recommend === 1,
      is_original: props.formData.is_original === 1
    };

    // 一次性赋值
    Object.assign(form, formDataProcessed);

    // 如果有封面图片，初始化预览
    if (form.cover_image) {
      coverUrl.value = form.cover_image;
    }

    // 如果已有摘要，标记为用户已编辑
    if (form.description) {
      userEditedSummary.value = true;
    }

    // 初始化权限相关数据
    if (form.visibility === 'specific_users' && userList.value.length === 0) {
      fetchUsers()
    }
    if (form.visibility === 'specific_roles' && roleList.value.length === 0) {
      fetchRoles()
    }
  }

  // 如果有内容但没有摘要，自动生成摘要
  if (form.content && !form.description) {
    autoGenerateSummary(form.content);
  }
})

// 添加测试数据填充方法
const fillTestData = () => {
  if (props.formData && props.formData.id) return; // 编辑模式不可用

  // 生成随机数，确保每次生成的测试数据都不一样
  const random = Math.floor(Math.random() * 1000);
  const currentDate = new Date();
  const formattedDate = currentDate.toISOString().split('T')[0];

  // 填充测试数据到表单
  form.title = `测试文章标题 ${random}`;
  form.subtitle = `测试副标题 ${random}`;
  form.slug = `test-article-${random}`;
  form.content = `# 测试文章 ${random}

这是一篇用于测试的文章，包含了基本的Markdown格式。

## 二级标题

这里是一些正文内容，可以包含**粗体**、*斜体*和~~删除线~~等格式。

### 三级标题

- 列表项1
- 列表项2
- 列表项3

#### 四级标题

1. 有序列表项1
2. 有序列表项2
3. 有序列表项3

##### 代码示例

\`\`\`javascript
function sayHello() {
  console.log("Hello, World!");
}
\`\`\`

###### 图片和链接

这里可以插入[链接文本](https://example.com)

> 这是一段引用文本，用于测试引用格式。

这是最后一段，用于总结文章内容。
`;

  // 设置分类（如果有分类数据）
  if (categoriesList.value.length > 0) {
    const categoryIndex = Math.floor(Math.random() * categoriesList.value.length);
    form.category_id = categoriesList.value[categoryIndex].id;
  }

  // 设置标签（如果有分类数据）
  if (categoriesList.value.length > 0) {
    // 随机选择1-3个标签
    const tagCount = Math.min(3, categoriesList.value.length);
    const randomTags = [];

    while (randomTags.length < tagCount) {
      const tagIndex = Math.floor(Math.random() * categoriesList.value.length);
      const tagId = categoriesList.value[tagIndex].id;

      // 确保不重复添加同一个标签
      if (!randomTags.includes(tagId)) {
        randomTags.push(tagId);
      }
    }

    selectedTags.value = randomTags;
  }

  // 设置关键词和描述
  form.keywords = `测试,文章,示例,${random}`;
  form.description = `这是一篇测试文章，用于展示文章管理功能。它包含了各种Markdown格式和基本结构，编号为${random}。`;

  // 设置状态和属性
  form.status = Math.floor(Math.random() * 2); // 随机选择草稿或已发布
  form.is_top = Math.random() > 0.7; // 30%概率设为置顶
  form.is_recommend = Math.random() > 0.5; // 50%概率设为推荐
  form.is_original = true; // 默认为原创

  // 计算字数和阅读时间
  handleContentChange(form.content);

  message('测试数据已填充', { type: 'success' });
}
</script>

<style scoped>
/* 自定义工具栏按钮样式，与原生工具栏保持一致 */
.custom-toolbar-btn {
  box-shadow: none !important;
  border: none !important;
  background: transparent !important;
  margin: 0;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 3px;
  transition: all 0.2s ease;
  width: 28px;
  height: 28px;
}

.custom-toolbar-btn:hover {
  background-color: var(--md-bk-hover, #e8e8e8) !important;
  box-shadow: none !important;
}

.custom-toolbar-btn:active {
  background-color: var(--md-bk-active, #d8d8d8) !important;
  transform: scale(0.95);
}

.custom-toolbar-btn .el-icon {
  font-size: 16px;
  color: var(--md-color, #333);
}

/* 深色主题适配 */
.md-editor-dark .custom-toolbar-btn:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
}

.md-editor-dark .custom-toolbar-btn:active {
  background-color: rgba(255, 255, 255, 0.15) !important;
}

.md-editor-dark .custom-toolbar-btn .el-icon {
  color: var(--md-color, #fff);
}

.el-form {
  padding: 20px;
}

.ai-summary-container {
  display: flex;
  flex-direction: column;
}

.action-buttons {
  margin-top: 8px;
  display: flex;
  justify-content: flex-start;
}

.article-options {
  display: flex;
  gap: 20px;
}

/* 封面上传组件样式 */
.elegant-cover-uploader {
  display: flex;
  align-items: flex-start;
  gap: 15px;

  /* 封面预览区 */
  .cover-preview {
    flex-shrink: 0;
    width: 200px;
    height: 112px;

    .preview-container {
      position: relative;
      width: 200px;
      height: 112px;
      border-radius: 6px;
      overflow: hidden;
      border: 1px solid var(--el-border-color);

      /* 封面图片 */
      .cover-img {
        width: 100%;
        height: 100%;
        display: block;
      }

      /* 无封面时的占位符 */
      .placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        font-size: 14px;
        background-color: var(--el-fill-color-light);
        color: var(--el-text-color-secondary);

        .el-icon {
          font-size: 32px;
          margin-bottom: 8px;
        }
      }

      /* 悬停效果层 */
      .hover-layer {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;

        .el-icon {
          color: #fff;
          font-size: 32px;
        }
      }

      &:hover {
        .hover-layer {
          opacity: 1;
        }
      }
    }

    /* 隐藏el-upload自带的样式 */
    .el-upload {
      display: block;
      border: none;
    }
  }

  /* 右侧控制区 */
  .controls {
    flex: 1;
    height: 112px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;

    /* 提示信息 */
    .tip {
      font-size: 12px;
      color: var(--el-text-color-secondary);
      margin-top: 4px;
      line-height: 1.5;
    }

    /* 操作区域 */
    .actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 4px;

      /* 文件名 */
      .filename {
        font-size: 12px;
        background-color: var(--el-fill-color-light);
        padding: 3px 8px;
        border-radius: 4px;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      /* 删除按钮 */
      .del-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        font-size: 12px;

        .el-icon {
          font-size: 14px;
        }
      }
    }
  }
}

.ai-button {
  background: linear-gradient(90deg, #1a56ff, #4c8dff);
  border: none;
  padding: 8px 16px;
  transition: all 0.3s ease;
}

.ai-button:hover {
  background: linear-gradient(90deg, #0040e0, #3677ff);
  box-shadow: 0 4px 12px rgba(26, 86, 255, 0.3);
  transform: translateY(-1px);
}

.ai-button-content {
  display: flex;
  align-items: center;
  gap: 6px;
}

.ai-button-content .el-icon {
  font-size: 16px;
}
</style>