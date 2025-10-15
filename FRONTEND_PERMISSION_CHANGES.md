# 前端文章权限控制修改总结

## 修改文件

### 1. AddOrEdit.vue (文章编辑组件)

**文件路径**: `src/views/basic/article/AddOrEdit.vue`

#### 修改内容

##### A. 表单数据新增字段 (第489-492行)

```javascript
// 权限相关字段
visibility: 'public', // 默认公开
access_users: [], // 授权用户ID数组
access_roles: [] // 授权角色ID数组
```

##### B. 权限控制相关响应式变量 (第406-410行)

```javascript
// 权限控制相关
const userList = ref([]) // 用户列表
const roleList = ref([]) // 角色列表
const userLoading = ref(false) // 用户加载状态
const roleLoading = ref(false) // 角色加载状态
```

##### C. 权限控制相关方法 (第509-586行)

1. **可见性变化处理方法**
   ```javascript
   const handleVisibilityChange = (value: string) => {
     // 当可见性不是指定用户或角色时，清空相关数据
     if (value !== 'specified_users') {
       form.access_users = []
     }
     if (value !== 'specified_roles') {
       form.access_roles = []
     }
     
     // 加载用户或角色列表
     if (value === 'specified_users' && userList.value.length === 0) {
       fetchUsers()
     }
     if (value === 'specified_roles' && roleList.value.length === 0) {
       fetchRoles()
     }
   }
   ```

2. **查询用户列表**
   ```javascript
   const fetchUsers = async () => {
     try {
       userLoading.value = true
       const res: any = await http.request('get', '/api/v1/users', {
         params: { page_size: 200 }
       })
       if (res && res.code === 200 && res.data) {
         userList.value = res.data.list || res.data || []
       }
     } catch (error) {
       console.error('获取用户列表失败:', error)
       message('获取用户列表失败', { type: 'error' })
     } finally {
       userLoading.value = false
     }
   }
   ```

3. **查询角色列表**
   ```javascript
   const fetchRoles = async () => {
     try {
       roleLoading.value = true
       const res: any = await http.request('get', '/api/v1/roles', {
         params: { page_size: 200 }
       })
       if (res && res.code === 200 && res.data) {
         roleList.value = res.data.list || res.data || []
       }
     } catch (error) {
       console.error('获取角色列表失败:', error)
       message('获取角色列表失败', { type: 'error' })
     } finally {
       roleLoading.value = false
     }
   }
   ```

4. **获取可见性标签颜色**
   ```javascript
   const getVisibilityTagType = (visibility: string) => {
     const typeMap = {
       'public': '',
       'private': 'info',
       'password': 'warning',
       'specified_users': 'success',
       'specified_roles': 'primary'
     }
     return typeMap[visibility] || ''
   }
   ```

5. **获取可见性说明文字**
   ```javascript
   const getVisibilityDescription = (visibility: string) => {
     const descMap = {
       'public': '任何人都可以查看此文章',
       'private': '只有作者本人可以查看此文章',
       'password': '需要输入密码才能查看此文章',
       'specified_users': '只有指定的用户可以查看此文章',
       'specified_roles': '只有指定角色的用户可以查看此文章'
     }
     return descMap[visibility] || ''
   }
   ```

##### D. 模板UI更新 (第85-176行)

1. **可见性选择器**
   ```vue
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
   ```

2. **指定用户选择器**
   ```vue
   <el-row v-if="form.visibility === 'specified_users'" :gutter="20">
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
   ```

3. **指定角色选择器**
   ```vue
   <el-row v-if="form.visibility === 'specified_roles'" :gutter="20">
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
   ```

##### E. 初始化逻辑更新 (第1052-1058行)

在 `onMounted` 钩子中添加权限数据初始化：

```javascript
// 初始化权限相关数据
if (form.visibility === 'specified_users' && userList.value.length === 0) {
  fetchUsers()
}
if (form.visibility === 'specified_roles' && roleList.value.length === 0) {
  fetchRoles()
}
```

## 数据流说明

### 1. 创建文章流程

1. 用户选择文章可见性
2. 如果选择"指定用户"或"指定角色"，自动加载对应列表
3. 用户选择授权的用户或角色
4. 提交表单时，权限数据随表单一起提交到后端
5. 后端保存文章基本信息和权限关联信息

### 2. 编辑文章流程

1. 从后端加载文章数据（包含权限信息）
2. 初始化表单时，将权限字段赋值到 form 对象
3. 如果可见性是"指定用户"或"指定角色"，自动加载对应列表
4. 用户修改权限设置
5. 提交表单时，更新的权限数据随表单一起提交到后端
6. 后端更新文章基本信息和权限关联信息

### 3. 数据格式

**前端提交到后端的数据格式：**
```javascript
{
  id: 1,
  title: "文章标题",
  content: "文章内容",
  // ... 其他字段 ...
  visibility: "specified_users", // 可见性
  access_users: [1, 2, 3], // 授权用户ID数组
  access_roles: [1, 2] // 授权角色ID数组
}
```

**后端返回给前端的数据格式：**
```javascript
{
  code: 200,
  msg: "获取成功",
  data: {
    id: 1,
    title: "文章标题",
    content: "文章内容",
    // ... 其他字段 ...
    visibility: "specified_users",
    access_users: [ // 授权用户详细信息
      { id: 1, username: "user1", ... }
    ],
    access_roles: [ // 授权角色详细信息
      { id: 1, name: "editor", ... }
    ]
  }
}
```

## 可见性选项说明

| 值 | 标签 | 说明 | 标签颜色 |
|---|---|---|---|
| public | 公开 | 任何人都可以查看 | 默认 |
| private | 私有 | 只有作者本人可以查看 | info |
| password | 密码保护 | 需要输入密码才能查看 | warning |
| specified_users | 指定用户 | 只有指定的用户可以查看 | success |
| specified_roles | 指定角色 | 只有指定角色的用户可以查看 | primary |

## 用户交互流程

1. **选择可见性**
   - 用户在"文章可见性"下拉框中选择一个选项
   - 系统触发 `handleVisibilityChange` 方法

2. **指定用户场景**
   - 如果选择"指定用户"，系统自动加载用户列表
   - 显示用户选择器（支持多选和搜索）
   - 用户选择需要授权的用户
   - 显示已选择的用户数量

3. **指定角色场景**
   - 如果选择"指定角色"，系统自动加载角色列表
   - 显示角色复选框组
   - 用户选择需要授权的角色
   - 显示已选择的角色数量

4. **切换可见性**
   - 如果从"指定用户"切换到其他选项，自动清空 `access_users`
   - 如果从"指定角色"切换到其他选项，自动清空 `access_roles`
   - 确保数据的一致性

## 注意事项

1. **权限字段自动提交**
   - 权限相关字段通过表单的 `...form` 展开操作符自动包含在提交数据中
   - 不需要额外处理这些字段的提交逻辑

2. **懒加载策略**
   - 用户列表和角色列表只在需要时才加载
   - 避免不必要的API请求

3. **数据一致性**
   - 切换可见性时自动清空不相关的权限数据
   - 避免数据冗余和混乱

4. **用户体验**
   - 提供视觉反馈（标签颜色、说明文字）
   - 显示已选择数量
   - 支持搜索和过滤（用户列表）

5. **编辑模式支持**
   - 编辑文章时正确加载和显示已设置的权限
   - 保留用户之前的权限设置

## 相关常量

权限控制使用的常量定义在 `src/constants/article.ts` 中：

```typescript
// 文章可见性选项
export const ARTICLE_VISIBILITY_OPTIONS = [
  { value: 'public', label: '公开', tip: '所有人' },
  { value: 'private', label: '私有', tip: '仅自己' },
  { value: 'password', label: '密码保护', tip: '输入密码' },
  { value: 'specified_users', label: '指定用户', tip: '特定用户' },
  { value: 'specified_roles', label: '指定角色', tip: '特定角色' }
]

// 文章可见性映射
export const ARTICLE_VISIBILITY_MAP = {
  public: '公开',
  private: '私有',
  password: '密码保护',
  specified_users: '指定用户',
  specified_roles: '指定角色'
}
```

## 测试建议

1. **创建文章测试**
   - 创建公开文章
   - 创建私有文章
   - 创建指定用户可访问的文章
   - 创建指定角色可访问的文章

2. **编辑文章测试**
   - 编辑已有文章的权限设置
   - 从公开切换到私有
   - 从指定用户切换到指定角色
   - 修改授权用户列表
   - 修改授权角色列表

3. **边界测试**
   - 不选择任何用户或角色时的处理
   - 用户列表或角色列表加载失败时的处理
   - 网络错误时的处理

4. **UI测试**
   - 验证标签颜色显示正确
   - 验证说明文字显示正确
   - 验证已选择数量显示正确
   - 验证加载状态显示正确

