<template>
  <div class="form-add-or-edit-container" v-loading="showLoading">
    <el-form ref="formRef" :rules="rules" label-width="100px" :model="form">
      <!-- 重新设计的头像上传组件 -->
      <el-form-item label="用户头像">
        <div class="elegant-avatar-uploader">
          <!-- 头像预览区 -->
          <el-upload class="avatar-preview" :auto-upload="false" :show-file-list="false" :on-change="handleAvatarChange"
            accept="image/jpeg,image/png,image/gif,image/bmp,image/webp" :before-upload="beforeAvatarUpload">
            <div class="preview-container">
              <el-image v-if="avatarUrl || form.avatar" :src="avatarUrl || form.avatar" fit="cover" class="avatar" />
              <div v-else class="placeholder">
                {{ form.username ? form.username.charAt(0).toUpperCase() : 'U' }}
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
              支持jpg、png、gif等格式，≤8M
            </div>

            <!-- 操作区域 -->
            <div class="actions">
              <!-- 文件名信息 -->
              <div v-if="avatarFile" class="filename">
                {{ avatarFile.name.length > 10 ?
                  avatarFile.name.substring(0, 8) + '...' +
                  avatarFile.name.substring(avatarFile.name.lastIndexOf('.')) :
                  avatarFile.name }}
              </div>

              <!-- 删除按钮 -->
              <el-button v-if="avatarUrl || form.avatar" type="danger" size="small" circle @click.stop="clearAvatarFile"
                class="del-btn">
                <el-icon>
                  <Delete />
                </el-icon>
              </el-button>
            </div>
          </div>
        </div>
      </el-form-item>

      <!-- 响应式栅格布局 -->
      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="用户名" prop="username">
            <el-input v-model="form.username" placeholder="请输入用户名（3-16位字符）" clearable />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12">
          <el-form-item label="手机号" prop="phone">
            <el-input v-model="form.phone" placeholder="请输入11位手机号码" clearable />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="邮箱" prop="email">
            <el-input v-model="form.email" placeholder="请输入有效邮箱地址" clearable />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12">
          <el-form-item label="用户性别" prop="gender">
            <el-select v-model="form.gender" placeholder="请选择性别" clearable>
              <el-option label="男" :value="1" />
              <el-option label="女" :value="0" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="角色组" prop="roles">
            <el-select v-model="form.roles" multiple placeholder="请选择用户角色" clearable>
              <el-option v-for="item in rolessData" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12">
          <el-form-item label="昵称" prop="nickname">
            <el-input v-model="form.nickname" placeholder="请输入用户昵称" clearable />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="用户状态">
            <el-switch v-model="form.status" inline-prompt active-text="启用" inactive-text="禁用" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12">
          <el-form-item label="Premium会员">
            <el-switch v-model="isPremium" inline-prompt active-text="是" inactive-text="否" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="会员类型">
            <el-select v-model="selectedPremiumType" placeholder="请选择会员类型" :disabled="!isPremium" style="width: 100%"
              @change="handlePremiumTypeChange">
              <el-option v-for="item in premiumOptions" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12">
          <el-form-item label="过期时间">
            <el-date-picker v-model="premiumExpireTime" type="datetime" placeholder="选择过期时间"
              format="YYYY-MM-DD HH:mm:ss" value-format="YYYY-MM-DD HH:mm:ss"
              :disabled="!isPremium || selectedPremiumType === 'forever'" style="width: 100%" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :xs="24" :sm="12">
          <el-form-item label="自定义备注">
            <el-input v-model="customPremiumRemark" placeholder="可选填写自定义备注" :disabled="!isPremium" clearable />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item label="个性签名" prop="signature">
            <el-input v-model="form.signature" type="textarea" :rows="3" placeholder="请输入个性签名（最多100字）" show-word-limit
              maxlength="100" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item class="form-actions">
        <el-button type="primary" @click="submitForm(formRef)" :loading="submitting">
          提交
        </el-button>
        <el-button @click="resetForm(formRef)">重置</el-button>

        <!-- 添加测试数据按钮 -->
        <el-button type="info" @click="fillTestData" v-if="!props.formData.id">填充测试数据</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<style lang="scss">
.form-add-or-edit-container {

  // 精致头像上传组件样式
  .elegant-avatar-uploader {
    display: flex;
    align-items: center;
    width: 280px;
    height: 64px;

    // 头像预览区
    .avatar-preview {
      flex-shrink: 0;
      width: 54px;
      height: 54px;

      .preview-container {
        position: relative;
        width: 54px;
        height: 54px;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid var(--el-border-color);

        // 头像图片
        .avatar {
          width: 100%;
          height: 100%;
          display: block;
        }

        // 无头像时的占位符
        .placeholder {
          width: 100%;
          height: 100%;
          display: flex;
          justify-content: center;
          align-items: center;
          font-size: 22px;
          background-color: var(--el-fill-color-light);
          color: var(--el-text-color-secondary);
        }

        // 悬停效果层
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
            font-size: 20px;
          }
        }

        &:hover {
          .hover-layer {
            opacity: 1;
          }
        }
      }

      // 隐藏el-upload自带的样式
      .el-upload {
        display: block;
        border: none;
      }
    }

    // 右侧控制区
    .controls {
      flex: 1;
      height: 54px;
      margin-left: 12px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;

      // 提示信息
      .tip {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        margin-top: 4px;
      }

      // 操作区域
      .actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 4px;

        // 文件名
        .filename {
          font-size: 12px;
          background-color: var(--el-fill-color-light);
          padding: 2px 8px;
          border-radius: 4px;
          max-width: 120px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        // 删除按钮
        .del-btn {
          width: 24px;
          height: 24px;
          padding: 0;
          font-size: 12px;

          .el-icon {
            font-size: 12px;
          }
        }
      }
    }
  }

  // 其他现有样式保持不变
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
import { reactive, ref, onMounted, onUnmounted, defineEmits, defineProps, watch, computed, defineExpose } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { getRoleList } from '@/api/role'
import { updateUserInfoR, createUser } from '@/api/user'
import { message } from '@/utils/message'
import { generateSerialNumbers } from '@/utils/dataUtil'
import { http } from "@/utils/http"
import { Camera, Delete } from '@element-plus/icons-vue'

// 会员类型选项
const premiumOptions = [
  { label: '1天体验', value: '1day', days: 1 },
  { label: '3天体验', value: '3days', days: 3 },
  { label: '一周会员', value: 'week', days: 7 },
  { label: '半月会员', value: 'halfmonth', days: 15 },
  { label: '月度会员', value: 'month', days: 30 },
  { label: '年度会员', value: 'year', days: 365 },
  { label: '永久会员', value: 'forever', days: -1 } // -1 表示永久
]

interface UserForm {
  username: string
  phone: string
  email: string
  gender: number
  roles: Number[]
  nickname: string
  status: boolean
  signature: string,
  id: any,
  avatar: string,
}

const showLoading = ref(false)
const emits = defineEmits(['submit-success'])
const props = defineProps({
  formData: {
    type: Object,
    default: () => ({

    })
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const formRef = ref<FormInstance>()
const submitting = ref(false)
const rolessData = ref([])
const isPremium = ref(false)
const selectedPremiumType = ref('month') // 默认选择月度会员
const customPremiumRemark = ref('')
const premiumExpireTime = ref('')

const form = reactive<UserForm>({
  username: '',
  phone: '',
  email: '',
  gender: null,
  roles: [],
  nickname: '',
  status: true,
  signature: '',
  id: '',
  avatar: '',
})

// 计算会员备注
const premiumRemark = computed(() => {
  const typeLabel = premiumOptions.find(item => item.value === selectedPremiumType.value)?.label || '会员'
  return customPremiumRemark.value ? `${typeLabel} - ${customPremiumRemark.value}` : typeLabel
})

const avatarFile = ref(null)
const avatarUrl = ref('')
const defaultAvatar = ref(null)

const rules = reactive<FormRules<UserForm>>({
  username: [
    { required: true, message: '用户名不能为空', trigger: 'blur' },
    { min: 3, max: 16, message: '长度应为3-16个字符', trigger: 'blur' }
  ],
  phone: [
    { message: '手机号不能为空', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '请输入有效的手机号码', trigger: 'blur' }
  ],
  email: [
    { message: '邮箱不能为空', trigger: 'blur' },
    { type: 'email', message: '请输入有效的邮箱地址', trigger: 'blur' }
  ],
  gender: [
    { required: true, message: '请选择性别', trigger: 'change' }
  ],
  roles: [
    { required: true, message: '请选择用户角色', trigger: 'change' }
  ],
  nickname: [
    { required: true, message: '昵称不能为空', trigger: 'blur' },
    { min: 2, max: 16, message: '昵称长度2-16个字符', trigger: 'blur' }
  ],
  signature: [
    { max: 100, message: '最多输入100个字符', trigger: 'blur' }
  ]
})

const handleAvatarChange = (file) => {
  avatarFile.value = file.raw
  if (avatarFile.value) {
    avatarUrl.value = URL.createObjectURL(avatarFile.value)
  }
}

const beforeAvatarUpload = (file) => {
  const isValidFormat = /\.(jpe?g|png|gif|bmp|webp)$/i.test(file.name)
  const isLt8M = file.size / 1024 / 1024 < 8

  if (!isValidFormat) {
    message('上传头像图片格式不正确！', { type: 'error' })
    return false
  }
  if (!isLt8M) {
    message('上传头像图片大小不能超过 8MB！', { type: 'error' })
    return false
  }
  return true
}

const clearAvatarFile = () => {
  avatarFile.value = null
  avatarUrl.value = ''
  form.avatar = ''
}

const handleAvatarError = () => {
  message('头像加载失败', { type: 'error' })
}

const handlePremiumTypeChange = (value) => {
  if (value === 'forever') {
    // 永久会员，设置一个超长的过期时间
    const farFuture = new Date()
    farFuture.setFullYear(farFuture.getFullYear() + 100) // 100年后
    premiumExpireTime.value = farFuture.toISOString().split('T')[0] + ' 23:59:59'
  } else {
    // 根据选择计算过期时间
    const option = premiumOptions.find(item => item.value === value)
    if (option) {
      const expireDate = new Date()
      expireDate.setDate(expireDate.getDate() + option.days)
      premiumExpireTime.value = expireDate.toISOString().split('T')[0] + ' 23:59:59'
    }
  }
}

watch(isPremium, (newValue) => {
  if (newValue) {
    // 默认选择月度会员
    selectedPremiumType.value = 'month'
    handlePremiumTypeChange('month')
  } else {
    premiumExpireTime.value = ''
    customPremiumRemark.value = ''
  }
})

const submitForm = async (formEl: FormInstance | undefined) => {
  if (!formEl) return
  try {
    submitting.value = true
    await formEl.validate()

    // 判断是新增还是编辑模式
    const isEditMode = !!props.formData.id

    // 如果选择了新头像，需要先上传
    if (avatarFile.value) {
      try {
        const uploadRes = await uploadAvatar()
        if (uploadRes && uploadRes.data && uploadRes.data.length > 0) {
          // 获取上传后的URL并更新到表单
          form.avatar = uploadRes.data[0].url
          console.log('头像上传成功，URL:', form.avatar)
        }
      } catch (error) {
        message('头像上传失败: ' + (error.message || '请重试'), { type: 'error' })
        return
      }
    }

    // 创建premium对象，保留原有ID等信息，确保包含所有必要字段
    let premiumData = null
    if (isPremium.value) {
      const currentDate = new Date().toISOString().split('T')[0] + ' ' +
        new Date().toTimeString().split(' ')[0];

      if (isEditMode && props.formData.premium) {
        // 编辑模式且原本有premium，保留原有ID等字段
        premiumData = {
          ...props.formData.premium,
          expiration_time: premiumExpireTime.value,
          remark: premiumRemark.value,
          update_time: currentDate
        }
      } else {
        // 新增模式或原本没有premium
        premiumData = {
          user_id: isEditMode ? props.formData.id : form.id, // 确保包含user_id
          expiration_time: premiumExpireTime.value,
          remark: premiumRemark.value,
          create_time: currentDate
        }
      }
    }

    // 表单数据提交
    let res: any

    if (isEditMode) {
      // 编辑模式 - 先设置ID
      const submitData = {
        ...form,
        id: props.formData.id,
        premium: premiumData
      }

      console.log('更新用户数据:', submitData)
      res = await updateUserInfoR(submitData)
    } else {
      // 新增模式 - 先生成ID
      form.id = generateSerialNumbers(1, 5)

      const submitData = {
        ...form,
        premium: isPremium.value ? {
          ...premiumData,
          user_id: form.id // 确保新用户的premium也有user_id
        } : null
      }

      console.log('创建用户数据:', submitData)
      res = await createUser(submitData)
    }

    console.log('API响应结果:', res)

    if (res.code !== 200) {
      message(res.msg || '操作失败', { type: 'error' })
      return
    }

    message(`用户${isEditMode ? '更新' : '创建'}成功`, { type: 'success' })
    emits('submit-success')

  } catch (error) {
    console.error('表单提交失败:', error)
    message('操作失败: ' + (error.message || '请检查表单内容'), { type: 'error' })
  } finally {
    submitting.value = false
  }
}

const resetForm = (formEl: FormInstance | undefined) => {
  if (!formEl) return
  formEl.resetFields()
}

onMounted(async () => {
  initData()
})

const initData = async () => {
  try {
    showLoading.value = true

    // 获取角色列表（新增和编辑都需要）
    const rolessRes: any = await getRoleList()
    if (rolessRes.code !== 200) {
      message('角色列表获取失败', { type: 'error' })
      return
    }
    // 适配新的数据结构，现在角色数据在list字段中
    rolessData.value = rolessRes.data.list || []

    // 仅在编辑模式下填充表单数据
    if (props.formData.id) {
      console.log('编辑模式，接收到的表单数据:', props.formData)

      // 表单数据赋值
      Object.keys(form).forEach(key => {
        if (key === 'roles' && props.formData.roles) {
          // 确保roles是一个包含id的数组
          form[key] = props.formData.roles.map(item => item.id)
          console.log('设置角色:', key, form[key])
        } else if (props.formData[key] !== undefined) {
          form[key] = props.formData[key]
          console.log('设置字段:', key, props.formData[key])
        }
      })

      // 处理premium对象数据
      isPremium.value = !!props.formData.premium
      if (props.formData.premium) {
        premiumExpireTime.value = props.formData.premium.expiration_time
        // 根据备注尝试确定会员类型
        const remark = props.formData.premium.remark || ''

        // 提取自定义备注
        if (remark.includes(' - ')) {
          customPremiumRemark.value = remark.split(' - ')[1]
        }

        // 尝试匹配类型
        const matchedType = premiumOptions.find(option =>
          remark.startsWith(option.label)
        )
        selectedPremiumType.value = matchedType ? matchedType.value : 'month'

        console.log('设置会员信息:', {
          isPremium: isPremium.value,
          expireTime: premiumExpireTime.value,
          type: selectedPremiumType.value,
          remark: customPremiumRemark.value
        })
      } else {
        premiumExpireTime.value = ''
        customPremiumRemark.value = ''
        selectedPremiumType.value = 'month'
      }
    }
  } catch (error) {
    console.error('初始化数据失败:', error)
    message('初始化数据失败', { type: 'error' })
  } finally {
    showLoading.value = false
  }
}

/**
 * 上传头像文件到服务器
 * @returns 上传成功返回服务器响应，失败抛出异常
 */
const uploadAvatar = async () => {
  if (!avatarFile.value) return null

  // 创建FormData
  const formData = new FormData()
  formData.append('file', avatarFile.value) // 使用与后端一致的参数名

  // 添加备注信息
  const username = form.username || '新用户';
  const remark = `用户"${username}"的头像`;
  formData.append('remark', remark);

  try {
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
      throw new Error(res.msg || '图片上传失败')
    }

    // 返回第一个文件的信息
    return res
  } catch (error) {
    console.error('头像上传失败:', error)
    throw error
  }
}

/**
 * 填充测试数据
 * 仅在新增模式下可用
 */
const fillTestData = () => {
  if (props.formData.id) return; // 编辑模式不可用

  // 生成随机数，确保每次生成的测试数据都不一样
  const random = Math.floor(Math.random() * 1000)

  // 填充测试数据到表单
  form.username = `test_user_${random}`
  form.phone = `1380013${random.toString().padStart(4, '0')}`
  form.email = `test${random}@example.com`
  form.gender = Math.random() > 0.5 ? 1 : 0 // 随机选择男/女

  // 如果有角色数据，随机选择1-2个角色
  if (rolessData.value.length > 0) {
    const roleCount = rolessData.value.length
    // 随机选择1个或2个角色
    const roleIndex1 = Math.floor(Math.random() * roleCount)
    let roleIndex2 = Math.floor(Math.random() * roleCount)
    // 确保两个角色不重复
    while (roleCount > 1 && roleIndex2 === roleIndex1) {
      roleIndex2 = Math.floor(Math.random() * roleCount)
    }

    form.roles = roleCount > 1
      ? [rolessData.value[roleIndex1].id, rolessData.value[roleIndex2].id]
      : [rolessData.value[roleIndex1].id]
  }

  form.nickname = `测试用户${random}`
  form.status = true // 默认启用
  form.signature = `这是测试账号${random}的个性签名，用于演示和测试目的。`

  // 随机设置Premium会员状态及过期时间
  isPremium.value = Math.random() > 0.7 // 30%的概率是Premium会员
  if (isPremium.value) {
    // 随机选择一个会员类型
    const typeIndex = Math.floor(Math.random() * premiumOptions.length)
    selectedPremiumType.value = premiumOptions[typeIndex].value
    handlePremiumTypeChange(selectedPremiumType.value)

    // 随机添加自定义备注
    if (Math.random() > 0.5) {
      customPremiumRemark.value = `测试账号${random}的会员身份`
    }
  } else {
    premiumExpireTime.value = ''
    customPremiumRemark.value = ''
  }

  message('测试数据已填充', { type: 'success' })
}

// 组件销毁时清理资源
onUnmounted(() => {
  if (avatarUrl.value && avatarUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(avatarUrl.value)
  }
})

// 导出组件
defineExpose({
  // 可以导出需要暴露给父组件的方法或属性
});
</script>

<style lang="scss"></style>

<!-- 添加默认导出，解决导入问题 -->
<script lang="ts">
export default {
  name: "UserAddOrEdit"
}
</script>