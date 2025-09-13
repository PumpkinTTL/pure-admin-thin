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
          <el-select v-model="form.category_id" placeholder="请选择分类" clearable>
            <el-option v-for="item in categoriesList" :key="item.id" :label="item.name" :value="item.id" />
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
            <el-option v-for="item in categoriesList" :key="item.id" :label="item.name" :value="item.id" />
          </el-select>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="文章状态" prop="status">
          <el-select v-model="form.status" placeholder="请选择状态">
            <el-option label="草稿" :value="0" />
            <el-option label="已发布" :value="1" />
            <el-option label="待审核" :value="2" />
            <el-option label="已下架" :value="3" />
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

    <el-row>
      <el-col :span="24">
        <el-form-item label="文章封面">
          <div class="elegant-cover-uploader">
            <!-- 封面预览区 -->
            <el-upload class="cover-preview" :auto-upload="false" :show-file-list="false" :on-change="handleCoverChange"
              :before-upload="beforeCoverUpload" accept="image/jpeg,image/png,image/gif,image/webp">
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
                支持jpg、png、gif、webp等格式，≤5M<br>
                推荐尺寸：1200×675px (16:9)
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
          <MdEditor v-model="form.content" height="500px" :language="editorLanguage" :theme="editorTheme"
            :preview-theme="previewTheme" :code-theme="codeTheme" :toolbars="editorToolbars" :toolbars-exclude="[]"
            :footers="editorFooters" :scroll-auto="true" :auto-focus="false" :auto-detect-code="true" :tab-width="2"
            :show-code-row-number="true" :preview-only="false" :html-preview="true" :no-mermaid="false"
            :no-katex="false" :no-highlight="false" :no-link-ref="false" :no-img-zoom-in="false" :sanitize-html="true"
            :max-length="50000" :auto-save="true" :placeholder="editorPlaceholder" @change="handleContentChange"
            @onUploadImg="handleImageUpload" @onSave="handleEditorSave" @onFocus="handleEditorFocus"
            @onBlur="handleEditorBlur" @onGetCatalog="handleGetCatalog" />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row>
      <el-col :span="24">
        <el-form-item label="文章摘要" prop="description">
          <div class="summary-container">
            <el-input v-model="form.description" type="textarea" :rows="3" placeholder="根据文章内容自动生成，也可手动编辑"
              maxlength="255" show-word-limit />
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

    <el-row>
      <el-col :span="24">
        <el-form-item label="AI摘要">
          <el-input v-model="form.ai_summary" type="textarea" :rows="2" placeholder="AI生成的摘要将显示在这里，不超过200个字符"
            maxlength="200" show-word-limit :disabled="true" />
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
import { ref, reactive, computed, watch, onMounted, defineEmits, defineProps } from 'vue'
import { MdEditor } from "md-editor-v3";
import "md-editor-v3/lib/style.css";
import { http } from '@/utils/http';
import { addArticle, updateArticle } from '@/api/article'
import { getCategoryList } from '@/api/category'
import { message } from "@/utils/message";
import { ElMessageBox } from 'element-plus'
import { uploadImage } from '@/api/upload'
import {
  ChatRound, Picture, Delete, Camera, Star, ArrowRight, Plus,
  Minus, Files, InfoFilled, Paperclip, Loading
} from '@element-plus/icons-vue'
import { request, wenxinRequest } from '@/api/openai'
import { ElMessage } from 'element-plus'
import { generateSerialNumbers } from '@/utils/dataUtil'
import { useUserStoreHook } from '@/store/modules/user'
const props = defineProps({
  formData: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['submit-success', 'cancel'])

const formRef = ref()
const loading = ref(false)
const submitting = ref(false)
const aiLoading = ref(false)

// 标签相关
const selectedTags = ref([])
const categoriesList = ref([])
const tagsLoading = ref(false)

// 获取分类列表（包含可作为标签的分类）
const fetchCategories = async () => {
  try {
    tagsLoading.value = true
    const res: any = await getCategoryList({ page_size: 200 }) // 获取更多数据
    if (res && res.code === 200 && res.data) {
      // 新的API返回格式：res.data.list
      categoriesList.value = res.data.list || res.data

      // 如果是编辑模式，设置已选标签
      if (props.formData?.tags?.length) {
        selectedTags.value = props.formData.tags.map(tag => tag.pivot.category_id)
      }
    }
  } catch (error) {
    console.error('获取分类/标签列表失败:', error)
    // 如果获取失败，提供一些默认数据以便测试
    if (categoriesList.value.length === 0) {
      categoriesList.value = [
        { id: 1, name: '技术' },
        { id: 2, name: '生活' },
        { id: 3, name: '科技' }
      ]
    }
    message('获取分类/标签列表失败，已使用默认数据', { type: 'warning' })
  } finally {
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
})

// 表单验证规则
const rules = reactive({
  title: [{ required: true, message: '请输入文章标题', trigger: 'blur' }],
  category_id: [{ required: true, message: '请选择文章分类', trigger: 'change' }],
  content: [{ required: true, message: '请输入文章内容', trigger: 'blur' }],
  slug: [{ required: true, message: '请输入文章别名', trigger: 'blur' }]
})

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
  const isValidFormat = /\.(jpe?g|png|gif|webp)$/i.test(file.name)
  const isLt5M = file.size / 1024 / 1024 < 5

  if (!isValidFormat) {
    message('上传封面图片格式不正确！', { type: 'error' })
    return false
  }
  if (!isLt5M) {
    message('上传封面图片大小不能超过 5MB！', { type: 'error' })
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

// 编辑器完整配置
const editorLanguage = ref('zh-CN') // 编辑器语言
const editorTheme = ref('light') // 编辑器主题：light/dark
const previewTheme = ref('default') // 预览主题：default/github/vuepress/mk-cute/smart-blue/cyanosis
const codeTheme = ref('atom') // 代码主题：atom/a11y/github/gradient/kimbie/paraiso/qtcreator/stackoverflow

// 编辑器占位符
const editorPlaceholder = ref('请输入文章内容，支持Markdown语法...')

// 编辑器工具栏配置（完整版）
const editorToolbars = [
  // 文本格式化
  'bold', 'underline', 'italic', 'strikeThrough', '-',
  // 标题和引用
  'title', 'sub', 'sup', 'quote', '-',
  // 列表和任务
  'unorderedList', 'orderedList', 'task', '-',
  // 代码和链接
  'codeRow', 'code', 'link', '-',
  // 媒体插入
  'image', 'table', 'mermaid', 'katex', '-',
  // 操作按钮
  'revoke', 'next', 'save', '-',
  // 视图控制
  '=', 'pageFullscreen', 'fullscreen', 'preview', 'htmlPreview', 'catalog'
]

// 编辑器底部工具栏
const editorFooters = [
  'markdownTotal', '=', 'scrollSwitch'
]

// 编辑器快捷键配置
const editorShortcuts = {
  bold: 'Ctrl+B',
  italic: 'Ctrl+I',
  underline: 'Ctrl+U',
  strikeThrough: 'Ctrl+Shift+S',
  title: 'Ctrl+H',
  quote: 'Ctrl+Q',
  unorderedList: 'Ctrl+L',
  orderedList: 'Ctrl+Shift+L',
  code: 'Ctrl+K',
  codeRow: 'Ctrl+Shift+K',
  link: 'Ctrl+Shift+L',
  image: 'Ctrl+Shift+I',
  table: 'Ctrl+T',
  save: 'Ctrl+S',
  revoke: 'Ctrl+Z',
  next: 'Ctrl+Y',
  fullscreen: 'F11',
  preview: 'Ctrl+P'
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
      } catch (error) {
        console.error('图片上传失败:', error)
        message(`图片 "${file.name}" 上传失败: ${error.message}`, { type: 'error' })
        return null
      }
    })

    const results = await Promise.all(uploadPromises)
    const successUrls = results.filter(url => url !== null)

    if (successUrls.length > 0) {
      callback(successUrls)
      message(`成功上传 ${successUrls.length} 张图片`, { type: 'success' })
    }
  } catch (error) {
    console.error('批量图片上传失败:', error)
    message('图片上传失败: ' + error.message, { type: 'error' })
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
const handleContentChange = (content) => {
  // 移除Markdown标记后计算字数
  const plainText = content.replace(/(?:!\[(.*?)\]\((.*?)\))|(?:\[(.*?)\]\((.*?)\))|(?:\*\*(.*?)\*\*)|(?:\*(.*?)\*)|(?:__(.*?)__)|(?:_(.*?)_)|(?:~~(.*?)~~)|(?:`(.*?)`)|(?:```([\s\S]*?)```)|(?:#+ )|(?:- )|(?:\d+\. )|(?:\|)|(?:-{3,})|(?:>{1,})/g, '$1$3$5$6$7$8$9$10$11');

  const wordCount = plainText.replace(/\s+/g, '').length;
  form.word_count = wordCount;

  // 假设阅读速度为每分钟300字
  form.read_time = Math.max(1, Math.ceil(wordCount / 300));

  // 文章内容变化且内容超过50字时，自动生成摘要
  if (content.length > 50 && !userEditedSummary.value) {
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
const autoGenerateSummary = (content) => {
  // 简单提取前100个字符作为摘要
  const plainText = content.replace(/(?:!\[(.*?)\]\((.*?)\))|(?:\[(.*?)\]\((.*?)\))|(?:\*\*(.*?)\*\*)|(?:\*(.*?)\*)|(?:__(.*?)__)|(?:_(.*?)_)|(?:~~(.*?)~~)|(?:`(.*?)`)|(?:```([\s\S]*?)```)|(?:#+ )|(?:- )|(?:\d+\. )|(?:\|)|(?:-{3,})|(?:>{1,})/g, '$1$3$5$6$7$8$9$10$11').trim();

  if (plainText.length > 0) {
    // 提取第一段落或前100个字符
    const firstParagraph = plainText.split('\n\n')[0];
    form.description = firstParagraph.length > 100
      ? firstParagraph.substring(0, 100) + '...'
      : firstParagraph;
  }
}

// 生成AI摘要
const generateAiSummary = async () => {
  if (!form.content) {
    message('请先输入文章内容再生成摘要', { type: 'warning' });
    return;
  }

  try {
    aiLoading.value = true;

    // 准备提示词
    const prompt = `请基于以下文章内容，生成一个高质量的摘要，要求：
1. 摘要字数控制在200字以内
2. 准确提炼文章核心观点
3. 保留文章主要信息点
4. 语言简洁、流畅、有吸引力

文章内容如下：
${form.content}`;

    // 调用DeepSeek API生成摘要
    // const summary = await request(prompt);
    const summary = await request(prompt);

    // 将AI摘要设置到表单
    form.ai_summary = summary;

    // 同时更新普通摘要
    form.description = summary;

    // 重置用户编辑标志
    userEditedSummary.value = false;

    message('AI智能摘要生成成功', { type: 'success' });
  } catch (error) {
    console.error('生成AI摘要失败:', error);
    message('生成AI摘要失败，请稍后重试', { type: 'error' });
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

  } catch (error) {
    console.error('表单提交失败:', error);
    message('表单验证失败，请检查填写的内容', { type: 'error' });
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
.el-form {
  padding: 20px;
}

.summary-container {
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