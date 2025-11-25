<template>
  <div class="email-template-container">
    <!-- 操作栏 -->
    <div class="action-toolbar">
      <div class="left-actions">
        <el-button type="primary" @click="handleAdd">
          <font-awesome-icon :icon="['fas', 'plus']" class="mr-1" />
          新增模板
        </el-button>
      </div>
      <div class="right-actions">
        <el-select
          v-model="includeDeleted"
          placeholder="状态"
          style="width: 120px; margin-right: 8px"
          @change="fetchList"
        >
          <el-option label="正常" :value="0" />
          <el-option label="已删除" :value="1" />
          <el-option label="全部" :value="2" />
        </el-select>
        <el-input
          v-model="searchKeyword"
          placeholder="搜索模板名称或代码"
          clearable
          style="width: 250px; margin-right: 8px"
        >
          <template #prefix>
            <font-awesome-icon :icon="['fas', 'search']" />
          </template>
        </el-input>
        <el-button type="info" plain circle @click="fetchList">
          <font-awesome-icon :icon="['fas', 'sync']" />
        </el-button>
      </div>
    </div>

    <!-- 卡片式列表 -->
    <el-row v-loading="loading" :gutter="16" class="template-cards">
      <el-col
        v-for="item in filteredData"
        :key="item.id"
        :xs="24"
        :sm="12"
        :md="8"
        :lg="6"
        :xl="4"
      >
        <el-card class="template-card" shadow="hover">
          <template #header>
            <div class="card-header">
              <div class="template-info">
                <el-tag v-if="item.delete_time" type="danger" size="small">
                  已删除
                </el-tag>
                <el-tag
                  v-else
                  :type="item.is_active ? 'success' : 'info'"
                  size="small"
                >
                  {{ item.is_active ? "启用" : "禁用" }}
                </el-tag>
                <span class="template-name">{{ item.name }}</span>
              </div>
              <el-dropdown
                trigger="click"
                @command="handleCommand($event, item)"
              >
                <el-button type="primary" text circle size="small">
                  <font-awesome-icon :icon="['fas', 'ellipsis-v']" />
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item v-if="!item.delete_time" command="edit">
                      <font-awesome-icon :icon="['fas', 'edit']" class="mr-1" />
                      编辑
                    </el-dropdown-item>
                    <el-dropdown-item command="preview">
                      <font-awesome-icon :icon="['fas', 'eye']" class="mr-1" />
                      预览
                    </el-dropdown-item>
                    <el-dropdown-item v-if="!item.delete_time" command="test">
                      <font-awesome-icon
                        :icon="['fas', 'paper-plane']"
                        class="mr-1"
                      />
                      测试发送
                    </el-dropdown-item>
                    <el-dropdown-item v-if="!item.delete_time" command="toggle">
                      <font-awesome-icon
                        :icon="['fas', item.is_active ? 'ban' : 'check']"
                        class="mr-1"
                      />
                      {{ item.is_active ? "禁用" : "启用" }}
                    </el-dropdown-item>
                    <el-dropdown-item v-if="item.delete_time" command="restore">
                      <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />
                      恢复
                    </el-dropdown-item>
                    <el-dropdown-item
                      v-if="!item.delete_time"
                      command="delete"
                      divided
                    >
                      <font-awesome-icon
                        :icon="['fas', 'trash']"
                        class="mr-1"
                      />
                      删除
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </template>

          <div class="card-content">
            <div class="code-badge">
              <el-tag type="info" size="small" effect="plain">
                {{ item.code }}
              </el-tag>
            </div>

            <div class="subject-preview">
              <div class="label">邮件主题:</div>
              <div class="value">{{ item.subject }}</div>
            </div>

            <div v-if="item.variables" class="variables-section">
              <div class="label">可用变量:</div>
              <div class="variables-list">
                <el-tag
                  v-for="(variable, idx) in parseVariables(item.variables)"
                  :key="idx"
                  size="small"
                  class="variable-tag"
                >
                  {{ variable }}
                </el-tag>
              </div>
            </div>

            <div class="meta-info">
              <div class="meta-item">
                <font-awesome-icon :icon="['fas', 'clock']" class="mr-1" />
                {{ formatDate(item.create_time) }}
              </div>
            </div>
          </div>

          <template #footer>
            <div class="card-actions">
              <el-button
                v-if="!item.delete_time"
                type="primary"
                size="small"
                text
                @click="handleEdit(item)"
              >
                编辑
              </el-button>
              <el-button
                type="success"
                size="small"
                text
                @click="handlePreview(item)"
              >
                预览
              </el-button>
              <el-button
                v-if="!item.delete_time"
                type="warning"
                size="small"
                text
                @click="handleTestEmail(item)"
              >
                测试
              </el-button>
              <el-button
                v-if="item.delete_time"
                type="info"
                size="small"
                text
                @click="handleRestore(item)"
              >
                恢复
              </el-button>
            </div>
          </template>
        </el-card>
      </el-col>

      <!-- 空状态 -->
      <el-col v-if="!loading && !filteredData.length" :span="24">
        <el-empty description="暂无邮件模板" />
      </el-col>
    </el-row>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="85%"
      :close-on-click-modal="false"
      destroy-on-close
      class="template-dialog"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="90px"
      >
        <!-- 基本信息 -->
        <el-card shadow="never" class="form-section">
          <template #header>
            <div class="section-header">
              <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-2" />
              基本信息
            </div>
          </template>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="模板名称" prop="name">
                <el-input
                  v-model="formData.name"
                  placeholder="例如：密码重置邮件"
                  maxlength="100"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="模板代码" prop="code">
                <el-input
                  v-model="formData.code"
                  placeholder="例如：password_reset"
                  maxlength="50"
                  :disabled="!!formData.id"
                />
                <div
                  v-if="formData.id"
                  style="margin-top: 4px; font-size: 12px; color: #909399"
                >
                  <font-awesome-icon
                    :icon="['fas', 'info-circle']"
                    style="margin-right: 4px"
                  />
                  模板代码不可修改
                </div>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="24">
              <el-form-item label="邮件主题" prop="subject">
                <el-input
                  v-model="formData.subject"
                  placeholder="例如：您的密码已重置"
                  maxlength="200"
                />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="24">
              <el-form-item label="模板描述" prop="description">
                <el-input
                  v-model="formData.description"
                  type="textarea"
                  :rows="2"
                  placeholder="简要描述模板用途"
                  maxlength="500"
                />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="状态">
                <el-switch
                  v-model="formData.is_active"
                  :active-value="1"
                  :inactive-value="0"
                  active-text="启用"
                  inactive-text="禁用"
                />
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>

        <!-- 模板内容 -->
        <el-card shadow="never" class="form-section" style="margin-top: 20px">
          <template #header>
            <div class="section-header">
              <font-awesome-icon :icon="['fas', 'file-code']" class="mr-2" />
              模板内容
            </div>
          </template>

          <el-form-item label="邮件正文" prop="body">
            <el-input
              v-model="formData.body"
              type="textarea"
              :rows="12"
              placeholder="输入HTML格式的邮件正文，使用 {变量名} 格式标记变量"
              @input="handleBodyInput"
            />
          </el-form-item>

          <!-- 变量管理 -->
          <el-form-item label="模板变量">
            <div class="variables-section">
              <!-- 自动识别的变量列表 -->
              <div
                v-if="formData.variablesList.length > 0"
                class="detected-variables"
              >
                <span class="label">已识别变量：</span>
                <el-tag
                  v-for="(variable, index) in formData.variablesList"
                  :key="index"
                  size="small"
                  type="success"
                  closable
                  class="variable-tag"
                  @close="removeVariable(index)"
                >
                  {{ variable }}
                </el-tag>
              </div>

              <!-- 常用变量快速添加 -->
              <div class="common-variables">
                <span class="label">常用变量：</span>
                <el-button
                  v-for="cv in commonVariables"
                  :key="cv"
                  size="small"
                  text
                  @click="quickAddVariable(cv)"
                >
                  {{ cv }}
                </el-button>
              </div>
            </div>
          </el-form-item>
        </el-card>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">
          保存模板
        </el-button>
      </template>
    </el-dialog>

    <!-- 预览弹窗 -->
    <el-dialog
      v-model="previewDialogVisible"
      title="邮件模板预览"
      width="70%"
      top="5vh"
      destroy-on-close
      :lock-scroll="true"
      class="preview-dialog"
    >
      <div class="preview-dialog-content">
        <div class="preview-meta">
          <div class="meta-row">
            <span class="meta-label">模板名称：</span>
            <span class="meta-value">{{ previewData.name }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">模板代码：</span>
            <el-tag size="small" type="info">{{ previewData.code }}</el-tag>
          </div>
          <div class="meta-row">
            <span class="meta-label">邮件主题：</span>
            <span class="meta-value">{{ previewData.subject }}</span>
          </div>
        </div>
        <el-divider />
        <div class="preview-body-wrapper">
          <iframe
            ref="previewIframe"
            class="preview-iframe"
            sandbox="allow-same-origin"
            frameborder="0"
            @load="loadPreviewContent"
          />
        </div>
      </div>
    </el-dialog>

    <!-- 测试邮件弹窗 -->
    <el-dialog
      v-model="testDialogVisible"
      title="发送测试邮件"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form :model="testForm" label-width="100px">
        <el-form-item label="测试邮箱">
          <el-input
            v-model="testForm.email"
            placeholder="请输入接收测试邮件的邮箱地址"
            type="email"
          >
            <template #prefix>
              <font-awesome-icon :icon="['fas', 'envelope']" />
            </template>
          </el-input>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="testDialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          :loading="testSending"
          @click="handleSendTest"
        >
          <font-awesome-icon :icon="['fas', 'paper-plane']" class="mr-1" />
          发送测试
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from "vue";
import { ElMessageBox, ElCheckbox } from "element-plus";
import { message } from "@/utils/message";
import dayjs from "dayjs";
import {
  getEmailTemplateList,
  createEmailTemplate,
  updateEmailTemplate,
  deleteEmailTemplate,
  restoreEmailTemplate,
  toggleEmailTemplateStatus,
  sendTestEmail,
  type EmailTemplate
} from "@/api/emailTemplate";

// 表格数据
const loading = ref(false);
const tableData = ref<EmailTemplate[]>([]);
const searchKeyword = ref("");
const includeDeleted = ref(0); // 0=不包含软删除(默认), 1=仅软删除, 2=包含所有

// 常用变量
const commonVariables = [
  "{username}",
  "{email}",
  "{date}",
  "{year}",
  "{link}",
  "{code}"
];

// 弹窗
const dialogVisible = ref(false);
const dialogTitle = ref("新增邮件模板");
const submitting = ref(false);
const formRef = ref<any>(null);

// 变量管理
const showAddVariable = ref(false);
const newVariable = ref("");

// 表单数据
const formData = reactive<EmailTemplate & { variablesList: string[] }>({
  code: "",
  name: "",
  subject: "",
  body: "",
  variables: "",
  variablesList: [], // 用于UI展示的变量数组
  description: "",
  is_active: 1
});

// 表单验证规则
const formRules = {
  name: [{ required: true, message: "请输入模板名称", trigger: "blur" }],
  code: [
    { required: true, message: "请输入模板代码", trigger: "blur" },
    {
      pattern: /^[a-z0-9_]+$/,
      message: "只能包含小写字母、数字和下划线",
      trigger: "blur"
    }
  ],
  subject: [{ required: true, message: "请输入邮件主题", trigger: "blur" }],
  body: [{ required: true, message: "请输入邮件正文", trigger: "blur" }]
};

// 测试邮件
const testDialogVisible = ref(false);
const testSending = ref(false);
const testForm = reactive({
  id: 0,
  email: ""
});

// 预览弹窗
const previewDialogVisible = ref(false);
const previewIframe = ref<HTMLIFrameElement | null>(null);
const previewData = reactive({
  name: "",
  code: "",
  subject: "",
  body: ""
});

// 过滤数据
const filteredData = computed(() => {
  if (!searchKeyword.value) return tableData.value;

  const keyword = searchKeyword.value.toLowerCase();
  return tableData.value.filter(
    item =>
      item.name.toLowerCase().includes(keyword) ||
      item.code.toLowerCase().includes(keyword)
  );
});

// 获取列表
const fetchList = async () => {
  loading.value = true;
  try {
    const res = await getEmailTemplateList({
      page: 1,
      page_size: 100,
      include_deleted: includeDeleted.value // 传递软删除筛选参数
    });
    if (res.code === 200 && res.data) {
      tableData.value = res.data.list || [];
    }
  } catch (error) {
    console.error("获取邮件模板列表失败:", error);
    message("获取列表失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

// 解析变量字符串或对象
const parseVariables = (
  variablesStr: string | string[] | Record<string, string> | null | undefined
) => {
  if (!variablesStr) return [];

  // 如果已经是数组，直接返回
  if (Array.isArray(variablesStr)) {
    return variablesStr.filter(v => v);
  }

  // 如果是对象，提取键名并格式化为 {key} 格式
  if (typeof variablesStr === "object") {
    return Object.keys(variablesStr).map(key => `{${key}}`);
  }

  // 确保是字符串类型
  if (typeof variablesStr !== "string") {
    console.warn("variables 字段类型异常:", typeof variablesStr, variablesStr);
    return [];
  }

  return variablesStr
    .split(",")
    .map(v => v.trim())
    .filter(v => v);
};

// 新增
const handleAdd = () => {
  dialogTitle.value = "新增邮件模板";
  resetForm();
  dialogVisible.value = true;
};

// 编辑
const handleEdit = (row: EmailTemplate) => {
  dialogTitle.value = "编辑邮件模板";

  // 手动赋值以确保类型正确
  formData.id = row.id;
  formData.code = row.code;
  formData.name = row.name;
  formData.subject = row.subject;
  formData.body = row.body || row.content || "";
  formData.description = row.description || "";
  formData.is_active = Number(row.is_active); // 确保是数字类型

  // 解析变量到数组
  formData.variablesList = parseVariables(row.variables || "");
  dialogVisible.value = true;
};

// 重置表单
const resetForm = () => {
  formData.id = undefined;
  formData.code = "";
  formData.name = "";
  formData.subject = "";
  formData.body = "";
  formData.variables = "";
  formData.variablesList = [];
  formData.description = "";
  formData.is_active = 1;
  showAddVariable.value = false;
  newVariable.value = "";

  if (formRef.value) {
    formRef.value.resetFields();
  }
};

// 快速添加变量（点击常用变量时）
const quickAddVariable = (variable: string) => {
  // 插入到光标位置或末尾
  const textarea = document.querySelector(
    'textarea[placeholder*="HTML"]'
  ) as HTMLTextAreaElement;
  if (textarea) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = formData.body;

    formData.body = text.substring(0, start) + variable + text.substring(end);

    // 更新变量列表
    setTimeout(() => {
      handleBodyInput();
    }, 0);
  } else {
    // 如果无法获取textarea，直接添加到末尾
    formData.body += variable;
    handleBodyInput();
  }
};

// 移除变量
const removeVariable = (index: number) => {
  formData.variablesList.splice(index, 1);
};

// 自动识别HTML中的变量
const extractVariablesFromBody = (text: string) => {
  if (!text) return [];

  // 匹配 {变量名} 格式
  const regex = /\{([a-zA-Z0-9_]+)\}/g;
  const matches = text.matchAll(regex);
  const variables = new Set<string>();

  for (const match of matches) {
    variables.add(`{${match[1]}}`);
  }

  return Array.from(variables);
};

// 处理HTML输入，自动识别变量
const handleBodyInput = () => {
  const detectedVars = extractVariablesFromBody(formData.body);
  formData.variablesList = detectedVars;
};

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    submitting.value = true;
    try {
      // 将变量数组转为对象格式 {key: description}
      const variablesObj: Record<string, string> = {};
      formData.variablesList.forEach(v => {
        // 移除大括号，只保留变量名
        const varName = v.replace(/[{}]/g, "");
        variablesObj[varName] = varName; // 简单使用变量名作为描述
      });

      const submitData: any = {
        code: formData.code.trim(),
        name: formData.name.trim(),
        subject: formData.subject.trim(),
        content: formData.body || "", // 后端使用 content 字段
        variables: variablesObj, // 发送对象格式
        description: formData.description || "",
        is_active: Number(formData.is_active) // 确保是数字类型 0 或 1
      };

      console.log(
        "当前 is_active 状态:",
        formData.is_active,
        "类型:",
        typeof formData.is_active
      );
      console.log(
        "提交的 is_active:",
        submitData.is_active,
        "类型:",
        typeof submitData.is_active
      );

      // 调试输出
      console.log("提交数据:", submitData);

      const res = formData.id
        ? await updateEmailTemplate(formData.id, submitData)
        : await createEmailTemplate(submitData);

      if (res.code === 200) {
        message(formData.id ? "更新成功" : "创建成功", { type: "success" });
        dialogVisible.value = false;
        fetchList();
      } else {
        message(res.message || res.msg || "操作失败", { type: "error" });
      }
    } catch (error: any) {
      console.error("提交失败:", error);
      message(error.response?.data?.msg || error.message || "操作失败", {
        type: "error"
      });
    } finally {
      submitting.value = false;
    }
  });
};

// 加载预览内容到iframe
const loadPreviewContent = () => {
  if (!previewIframe.value) return;

  const iframe = previewIframe.value;
  const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document;

  if (iframeDoc) {
    iframeDoc.open();
    iframeDoc.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <style>
            body {
              margin: 0;
              padding: 16px;
              font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
              font-size: 14px;
              line-height: 1.6;
              color: #333;
              background-color: #fff;
              word-wrap: break-word;
              overflow-wrap: break-word;
            }
            img { max-width: 100%; height: auto; }
            table { max-width: 100%; border-collapse: collapse; }
            * { box-sizing: border-box; }
          </style>
        </head>
        <body>
          ${previewData.body}
        </body>
      </html>
    `);
    iframeDoc.close();

    // 自动调整iframe高度
    setTimeout(() => {
      if (iframeDoc.body) {
        iframe.style.height =
          Math.max(iframeDoc.body.scrollHeight + 40, 300) + "px";
      }
    }, 100);
  }
};

// 预览
const handlePreview = (row: EmailTemplate) => {
  Object.assign(previewData, {
    name: row.name,
    code: row.code,
    subject: row.subject,
    body: row.body || row.content || "" // 兼容 content 和 body 字段
  });
  previewDialogVisible.value = true;

  // 等待弹窗打开后加载内容
  setTimeout(() => {
    loadPreviewContent();
  }, 100);
};

// 测试邮件
const handleTestEmail = (row: EmailTemplate) => {
  testForm.id = row.id!;
  testForm.email = "";
  testDialogVisible.value = true;
};

// 发送测试
const handleSendTest = async () => {
  if (!testForm.email) {
    message("请输入测试邮箱", { type: "warning" });
    return;
  }

  testSending.value = true;
  try {
    const res = await sendTestEmail(testForm.id, testForm.email);
    if (res.code === 200) {
      message("测试邮件发送成功", { type: "success" });
      testDialogVisible.value = false;
    } else {
      message(res.message || "发送失败", { type: "error" });
    }
  } catch (error) {
    console.error("发送测试邮件失败:", error);
    message("发送失败", { type: "error" });
  } finally {
    testSending.value = false;
  }
};

// 下拉菜单命令处理
const handleCommand = async (command: string, row: EmailTemplate) => {
  switch (command) {
    case "edit":
      handleEdit(row);
      break;
    case "preview":
      handlePreview(row);
      break;
    case "test":
      handleTestEmail(row);
      break;
    case "toggle":
      await handleToggleStatus(row);
      break;
    case "restore":
      await handleRestore(row);
      break;
    case "delete":
      await handleDelete(row);
      break;
  }
};

// 切换状态
const handleToggleStatus = async (row: EmailTemplate) => {
  try {
    const newStatus = row.is_active === 1 ? 0 : 1;
    const res = await toggleEmailTemplateStatus(row.id!, newStatus);
    if (res.code === 200) {
      message("状态更新成功", { type: "success" });
      fetchList();
    } else {
      message(res.message || "状态更新失败", { type: "error" });
    }
  } catch (error) {
    message("状态更新失败", { type: "error" });
  }
};

// 恢复
const handleRestore = async (row: EmailTemplate) => {
  try {
    await ElMessageBox.confirm(`确定恢复模板"${row.name}"吗？`, "恢复确认", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "info"
    });

    const res = await restoreEmailTemplate(row.id!);
    if (res.code === 200) {
      message("恢复成功", { type: "success" });
      fetchList();
    } else {
      message(res.message || "恢复失败", { type: "error" });
    }
  } catch (error) {
    // 用户取消
  }
};

// 删除
const handleDelete = async (row: EmailTemplate) => {
  try {
    // 创建自定义确认框内容
    const isPhysicalDelete = ref(false);

    await ElMessageBox.confirm(
      () =>
        h("div", [
          h(
            "p",
            { style: { marginBottom: "16px" } },
            `确定删除模板"${row.name}"吗？`
          ),
          h(ElCheckbox, {
            modelValue: isPhysicalDelete.value,
            "onUpdate:modelValue": (val: boolean) => {
              isPhysicalDelete.value = val;
            },
            label: "永久删除（否则为软删除，可在已删除中恢复）"
          })
        ]),
      "删除确认",
      {
        confirmButtonText: "确定删除",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const res = await deleteEmailTemplate(row.id!, isPhysicalDelete.value);
    if (res.code === 200) {
      message(isPhysicalDelete.value ? "永久删除成功" : "删除成功", {
        type: "success"
      });
      fetchList();
    } else {
      message(res.message || "删除失败", { type: "error" });
    }
  } catch (error) {
    // 用户取消
  }
};

// 格式化日期
const formatDate = (dateStr: string) => {
  return dayjs(dateStr).format("YYYY-MM-DD HH:mm");
};

// 暴露方法供父组件调用
defineExpose({
  fetchList
});
</script>

<style lang="scss" scoped>
.email-template-container {
  .action-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;

    .right-actions {
      display: flex;
      align-items: center;
    }
  }

  .template-cards {
    min-height: 400px;

    .template-card {
      display: flex;
      flex-direction: column;
      height: 100%;
      margin-bottom: 16px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgb(0 0 0 / 4%);
      transition: all 0.2s ease;

      &:hover {
        box-shadow: 0 4px 16px rgb(0 0 0 / 8%);
      }

      :deep(.el-card__header) {
        padding: 14px 18px;
        background-color: #fafbfc;
      }

      :deep(.el-card__body) {
        display: flex;
        flex: 1;
        flex-direction: column;
        padding: 16px 18px;
      }

      :deep(.el-card__footer) {
        padding: 12px 18px;
        background-color: #f8f9fa;
      }

      .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        .template-info {
          display: flex;
          flex: 1;
          gap: 8px;
          align-items: center;
          overflow: hidden;

          .template-name {
            overflow: hidden;
            font-size: 14px;
            font-weight: 600;
            color: #303133;
            text-overflow: ellipsis;
            white-space: nowrap;
          }
        }
      }

      .card-content {
        display: flex;
        flex: 1;
        flex-direction: column;
        min-height: 0;

        .code-badge {
          margin-bottom: 12px;
        }

        .subject-preview {
          padding: 10px 12px;
          margin-bottom: 12px;
          background-color: #f5f7fa;
          border-radius: 6px;

          .label {
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 500;
            color: #909399;
          }

          .value {
            display: -webkit-box;
            overflow: hidden;
            font-size: 13px;
            line-height: 1.5;
            color: #303133;
            text-overflow: ellipsis;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
          }
        }

        .variables-section {
          padding: 10px 12px;
          margin-bottom: 12px;
          background-color: #f5f7fa;
          border-radius: 6px;

          .label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 500;
            color: #909399;
          }

          .variables-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            max-height: calc(22px * 3 + 6px * 2); // 3行紧凑布局
            overflow: hidden;

            :deep(.variable-tag) {
              box-sizing: border-box;
              display: inline-flex;
              flex-shrink: 0;
              align-items: center;
              height: 22px !important;
              padding: 0 8px !important;
              margin: 0 !important;
              font-family: Consolas, "Courier New", monospace !important;
              font-size: 12px !important;
              line-height: 20px !important;
            }
          }
        }

        .meta-info {
          padding-top: 12px;
          margin-top: auto;

          .meta-item {
            display: flex;
            align-items: center;
            padding: 6px 10px;
            font-size: 12px;
            color: #909399;
            background-color: #f5f7fa;
            border-radius: 4px;
          }
        }
      }

      .card-actions {
        display: flex;
        gap: 8px;
        justify-content: space-around;
      }
    }
  }

  .form-section {
    margin-bottom: 0;

    .section-header {
      font-size: 14px;
      font-weight: 600;
      color: #303133;
    }
  }

  .variables-section {
    width: 100%;

    .detected-variables,
    .common-variables {
      margin-bottom: 12px;

      .label {
        margin-right: 8px;
        font-size: 12px;
        color: #909399;
      }

      .variable-tag {
        margin-right: 6px;
        margin-bottom: 6px;
        font-family: "Courier New", monospace;
      }
    }
  }

  // 预览弹窗样式
  .preview-dialog {
    :deep(.el-dialog__body) {
      max-height: 70vh;
      padding: 20px;
      overflow-y: auto;
    }
  }

  .preview-dialog-content {
    .preview-meta {
      margin-bottom: 16px;

      .meta-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px;

        .meta-label {
          margin-right: 8px;
          font-weight: 600;
          color: #606266;
        }

        .meta-value {
          color: #303133;
        }
      }
    }

    .preview-body-wrapper {
      overflow: hidden;
      background-color: #fff;
      border: 1px solid #e4e7ed;
      border-radius: 4px;

      .preview-iframe {
        display: block;
        width: 100%;
        min-height: 300px;
        border: none;
      }
    }
  }
}
</style>
