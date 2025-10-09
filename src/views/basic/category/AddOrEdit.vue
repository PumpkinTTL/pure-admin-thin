<template>
  <div class="form-add-or-edit-container" v-loading="showLoading">
    <el-form ref="formRef" :rules="rules" label-width="100px" :model="form">
      <!-- 响应式栅格布局 -->
      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="类别名称" prop="name">
            <el-input v-model="form.name" placeholder="请输入类别名称" clearable />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12">
          <el-form-item label="标识" prop="slug">
            <el-input v-model="form.slug" placeholder="请输入标识" clearable />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="分类层级" prop="parent_id">
            <el-select v-model="form.parent_id" placeholder="请选择分类层级" @change="handleCategoryTypeChange">
              <el-option label="大类别" :value="0" />
              <el-option label="标签" :value="1" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12">
          <el-form-item label="类型" prop="type">
            <el-input v-model="form.type" placeholder="请输入类型" clearable />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="排序" prop="sort_order">
            <el-input-number v-model="form.sort_order" :min="0" :max="999" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12">
          <el-form-item label="状态">
            <el-switch v-model="form.status" inline-prompt active-text="启用" inactive-text="禁用" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="SEO标题" prop="meta_title">
            <el-input v-model="form.meta_title" placeholder="请输入SEO标题" maxlength="100" show-word-limit />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="SEO关键词" prop="meta_keywords">
            <el-input v-model="form.meta_keywords" type="textarea" :rows="2" placeholder="请输入SEO关键词" maxlength="255"
              show-word-limit />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="SEO描述" prop="meta_description">
            <el-input v-model="form.meta_description" type="textarea" :rows="3" placeholder="请输入SEO描述" maxlength="255"
              show-word-limit />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="描述" prop="description">
            <el-input v-model="form.description" type="textarea" :rows="2" placeholder="请输入描述" />
          </el-form-item>
        </el-col>
      </el-row>





      <el-form-item class="form-actions">
        <el-button type="primary" @click="submitForm(formRef)" :loading="submitting">
          提交
        </el-button>
        <el-button @click="resetForm(formRef)">重置</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<style lang="scss">
.form-add-or-edit-container {

  // 移动端适配
  @media screen and (max-width: 768px) {
    .el-form-item {
      margin-bottom: 16px;
    }

    .el-form-item__label {
      width: 100% !important;
      text-align: left;
      margin-bottom: 8px;
    }

    .form-actions {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
  }
}
</style>

<script setup lang="ts">
import { reactive, ref, defineEmits, defineProps, onMounted } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { message } from '@/utils/message'
import { objectIsEqual } from '@/utils/dataUtil'
import { updateCategoryR, createCategoryR } from '@/api/category'
import { useUserStoreHook } from '@/store/modules/user'
interface CategoryForm {
  name: string
  slug: string
  type: string
  description: string
  sort_order: number
  icon: string
  status: boolean
  parent_id: number
  meta_title: string
  meta_keywords: string
  meta_description: string,
  user_id: number,
  id?: any // 可选，编辑时才有
}

const showLoading = ref(false)
const emits = defineEmits(['submit-success'])
const props = defineProps({
  formData: {
    type: Object,
    default: () => ({
      name: '',
      sort: 0,
      status: true,
      remark: ''
    })
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

// 获取用户store
const userStore = useUserStoreHook()

const formRef = ref<FormInstance>()
const submitting = ref(false)
const form = reactive<CategoryForm>({
  name: '',
  slug: '',
  type: '',
  description: '',
  sort_order: 0,
  icon: '',
  status: true,
  parent_id: 0,
  meta_title: '',
  meta_keywords: '',
  meta_description: '',
  user_id: userStore.id || 0, // 从store获取当前登录用户ID
  id: null
})
// 不再需要加载大类别列表，因为只有两个固定选项

// 分类层级变化处理
const handleCategoryTypeChange = (value: number) => {
  console.log('分类层级变化:', value);
  if (value === 0) {
    console.log('选择了大类别，parent_id设为0');
    form.parent_id = 0;
  } else {
    console.log('选择了标签，parent_id设为任意值（这里设为1）');
    form.parent_id = 1; // 标签的parent_id可以是任意值，这里简单设为1
  }
};

onMounted(() => {
  if (props.formData) {
    Object.assign(form, props.formData)
  }
})
const rules = reactive<FormRules<CategoryForm>>({
  name: [
    { required: true, message: '类别名称不能为空', trigger: 'blur' },
    { min: 2, max: 20, message: '长度应为2-20个字符', trigger: 'blur' }
  ],
  sort_order: [
    { type: 'number', message: '排序必须为数字', trigger: 'blur' }
  ]
})
// 数据提交
const submitForm = async (formEl: FormInstance | undefined) => {
  if (!formEl) return
  try {
    submitting.value = true
    await formEl.validate()

    if (props.formData.id) {
      // 编辑逻辑
      if (objectIsEqual(form, props.formData)) return message('未修改信息', { type: 'warning' })
      const res: any = await updateCategoryR(form)
      if (res.code != 200) return message(res.msg, { type: 'error' })
      message(res.msg, { type: 'success' })
    } else {
      // 新增逻辑 - ID由后端自动生成，不需要前端设置
      // 确保使用最新的用户ID
      form.user_id = userStore.id || 0
      
      // 复制表单数据，移除id字段（由后端生成）
      const { id, ...formData } = form
      console.log('提交新增分类:', formData);
      
      const res: any = await createCategoryR(formData)
      if (res.code != 200) return message(res.msg, { type: 'error' })
      message(res.msg, { type: 'success' })
    }

    emits('submit-success')
  } catch (error) {
    console.error(error)
  } finally {
    submitting.value = false
  }
}

const resetForm = (formEl: FormInstance | undefined) => {
  if (!formEl) return
  formEl.resetFields()
}
</script>