<script setup lang="ts">
import { useRouter } from "vue-router";
import { message } from "@/utils/message";
import { loginRules } from "./utils/rule";
import { useNav } from "@/layout/hooks/useNav";
import type { FormInstance } from "element-plus";
import { useLayout } from "@/layout/hooks/useLayout";
import { initRouter, getTopMenu } from "@/router/utils";

import { ref, reactive, onMounted, onBeforeUnmount } from "vue";
import { useDataThemeChange } from "@/layout/hooks/useDataThemeChange";

import { loginR } from "@/api/user";
import { setToken } from "@/utils/auth";

const pageLoaded = ref(false);
defineOptions({
  name: "Login"
});

const router = useRouter();
const loading = ref(false);
const ruleFormRef = ref<FormInstance>();

const { initStorage } = useLayout();
initStorage();

const { dataTheme, overallStyle, dataThemeChange } = useDataThemeChange();
dataThemeChange(overallStyle.value);
const { title } = useNav();

const ruleForm = reactive({
  account: "7709",
  action: "pwd",
  password: "admin3306"
});

const rememberMe = ref(false);

const onLogin = async (formEl: FormInstance | undefined) => {
  if (!formEl) return;
  const valid = await formEl.validate().catch(() => false);
  if (!valid) return;
  loading.value = true;
  try {
    const res: any = await loginR(ruleForm);
    if (res.code !== 200) {
      message(res.msg || "登录失败", { type: "error" });
      return;
    }
    const { avatar, username, nickname, id, roles } = res.data;
    const { token, expireTime } = res;
    const idens = roles.map(item => item.iden);
    const permissions = roles.map(item =>
      item.permissions.map(pitem => pitem.name)
    );
    const permissionsArr = [].concat.apply([], permissions);
    const userInfo = {
      id,
      avatar,
      username,
      nickname,
      token,
      roles: idens,
      permissions: permissionsArr,
      expires: new Date(expireTime * 1000)
    };

    setToken(userInfo);

    return initRouter()
      .then(() => {
        const topMenu = getTopMenu(true);
        router
          .push(topMenu.path)
          .then(() => {})
          .catch(() => {
            message("跳转失败", { type: "error" });
          });
      })
      .catch(() => {
        message("路由初始化失败", { type: "error" });
      });
  } catch (error) {
    message("登录失败，请稍后重试", { type: "error" });
  } finally {
    loading.value = false;
  }
};

function onkeypress({ code }: KeyboardEvent) {
  if (["Enter", "NumpadEnter"].includes(code)) {
    onLogin(ruleFormRef.value);
  }
}

onMounted(() => {
  window.document.addEventListener("keypress", onkeypress);
  setTimeout(() => {
    pageLoaded.value = true;
  }, 100);
});

onBeforeUnmount(() => {
  window.document.removeEventListener("keypress", onkeypress);
});
</script>

<template>
  <div class="login-page">
    <!-- 动态背景 -->
    <div class="bg-gradient" />
    <div class="bg-grid" />

    <!-- 主题切换 -->
    <div class="theme-toggle">
      <el-switch
        v-model="dataTheme"
        inline-prompt
        active-text="☀️"
        inactive-text="🌙"
        style="

--el-switch-on-color: #6366f1; --el-switch-off-color: #94a3b8"
        @change="dataThemeChange"
      />
    </div>

    <!-- 登录卡片 -->
    <div class="login-card" :class="{ 'login-card-enter': pageLoaded }">
      <!-- 卡片顶部Logo -->
      <div class="card-header">
        <div class="logo-wrapper">
          <div class="logo-icon">
            <i class="fas fa-cube" />
          </div>
          <div class="logo-text">
            <span class="brand-name">知识棱镜</span>
            <span class="brand-en">Knowledge Prism</span>
          </div>
        </div>
      </div>

      <!-- 欢迎语 -->
      <div class="welcome-section">
        <h1 class="welcome-title">欢迎回来</h1>
        <p class="welcome-subtitle">请登录您的账户以继续</p>
      </div>

      <!-- 登录表单 -->
      <el-form
        ref="ruleFormRef"
        :model="ruleForm"
        :rules="loginRules"
        class="login-form"
        size="large"
      >
        <el-form-item prop="account">
          <el-input
            v-model="ruleForm.account"
            placeholder="请输入账号"
            class="form-input"
          >
            <template #prefix>
              <i class="fas fa-user" />
            </template>
          </el-input>
        </el-form-item>

        <el-form-item prop="password">
          <el-input
            v-model="ruleForm.password"
            type="password"
            placeholder="请输入密码"
            show-password
            class="form-input"
          >
            <template #prefix>
              <i class="fas fa-lock" />
            </template>
          </el-input>
        </el-form-item>

        <div class="form-options">
          <el-checkbox v-model="rememberMe" class="remember-checkbox">
            记住我
          </el-checkbox>
          <a class="forgot-link">忘记密码？</a>
        </div>

        <el-button
          type="primary"
          class="submit-btn"
          :loading="loading"
          @click="onLogin(ruleFormRef)"
        >
          <span v-if="!loading">登 录</span>
          <span v-else>登录中...</span>
        </el-button>
      </el-form>

      <!-- 底部信息 -->
      <div class="card-footer">
        <p class="footer-text">
          登录即表示您同意我们的
          <a class="footer-link">服务条款</a>
          和
          <a class="footer-link">隐私政策</a>
        </p>
      </div>
    </div>

    <!-- 底部版权 -->
    <div class="copyright">
      &copy; {{ new Date().getFullYear() }} {{ title }}. All rights reserved.
    </div>
  </div>
</template>

<style lang="scss" scoped>


// 响应式
@media (width <= 480px) {
  .login-card {
    max-width: calc(100vw - 32px);
    padding: 36px 28px;
  }

  .welcome-title {
    font-size: 24px;
  }

  .logo-icon {
    width: 42px;
    height: 42px;

    i {
      font-size: 20px;
    }
  }

  .brand-name {
    font-size: 20px;
  }
}

.login-page {
  position: relative;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100vw;
  height: 100vh;
  padding: 20px;
  overflow: hidden;
  background: #f8fafc;
  transition: background 0.3s ease;
}

// 动态渐变背景
.bg-gradient {
  position: absolute;
  inset: 0;
  z-index: 0;
  background: linear-gradient(160deg, #f1f5f9 0%, #e2e8f0 100%);
}

// 网格纹理
.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgb(148 163 184 / 10%) 1px, transparent 1px),
    linear-gradient(90deg, rgb(148 163 184 / 10%) 1px, transparent 1px);
  background-size: 40px 40px;
  mask-image: linear-gradient(to bottom, black 60%, transparent 100%);
}

// 主题切换
.theme-toggle {
  position: absolute;
  top: 24px;
  right: 24px;
  z-index: 100;
}

// 登录卡片
.login-card {
  position: relative;
  z-index: 10;
  align-self: center;
  width: 100%;
  max-width: 420px;
  padding: 48px 40px;
  background: rgb(255 255 255 / 85%);
  backdrop-filter: blur(20px);
  backdrop-filter: blur(20px);
  border: 1px solid rgb(255 255 255 / 50%);
  border-radius: 24px;
  box-shadow:
    0 25px 50px -12px rgb(0 0 0 / 10%),
    0 0 0 1px rgb(255 255 255 / 30%) inset;
  opacity: 0;
  transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
  transform: translateY(30px) scale(0.95);

  &.login-card-enter {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

// 卡片头部Logo
.card-header {
  margin-bottom: 32px;
  text-align: center;
}

.logo-wrapper {
  display: inline-flex;
  gap: 12px;
  align-items: center;
}

.logo-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 14px;
  box-shadow: 0 8px 20px -8px rgb(99 102 241 / 50%);

  i {
    font-size: 24px;
    color: #fff;
  }
}

.logo-text {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.brand-name {
  font-size: 22px;
  font-weight: 700;
  line-height: 1.2;
  background: linear-gradient(135deg, #1e293b, #475569);
  background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.brand-en {
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 1px;
}

// 欢迎区域
.welcome-section {
  margin-bottom: 36px;
  text-align: center;
}

.welcome-title {
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: -0.5px;
}

.welcome-subtitle {
  margin: 0;
  font-size: 14px;
  color: #64748b;
}

// 表单样式
.login-form {
  :deep(.el-form-item) {
    margin-bottom: 20px;
  }
}

.form-input {
  width: 100%;

  :deep(.el-input__wrapper) {
    height: 52px;
    padding: 0 16px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: none;
    transition: all 0.25s ease;

    &:hover {
      background: #fff;
      border-color: #cbd5e1;
    }

    &.is-focus {
      background: #fff;
      border-color: #6366f1;
      box-shadow: 0 0 0 4px rgb(99 102 241 / 10%);
    }
  }

  :deep(.el-input__prefix) {
    display: flex;
    align-items: center;
    font-size: 15px;
    color: #94a3b8;
    transition: color 0.2s ease;
  }

  :deep(.el-input__inner) {
    height: 100%;
    padding-left: 12px;
    font-size: 15px;
    color: #334155;

    &::placeholder {
      color: #94a3b8;
    }
  }

  &:hover :deep(.el-input__prefix),
  &:focus-within :deep(.el-input__prefix) {
    color: #6366f1;
  }
}

.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 8px 0 28px;
}

.remember-checkbox {
  :deep(.el-checkbox__label) {
    font-size: 14px;
    color: #64748b;
  }

  :deep(.el-checkbox__inner) {
    border-color: #cbd5e1;
    border-radius: 6px;

    &::after {
      border-width: 2px;
    }
  }

  &.is-checked :deep(.el-checkbox__inner) {
    background-color: #6366f1;
    border-color: #6366f1;
  }
}

.forgot-link {
  font-size: 14px;
  color: #6366f1;
  text-decoration: none;
  transition: color 0.2s;

  &:hover {
    color: #4f46e5;
  }
}

.submit-btn {
  width: 100%;
  height: 52px;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 2px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border: none;
  border-radius: 12px;
  box-shadow: 0 10px 25px -10px rgb(99 102 241 / 50%);
  transition: all 0.3s ease;

  &:hover {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    box-shadow: 0 15px 30px -12px rgb(99 102 241 / 60%);
    transform: translateY(-2px);
  }

  &:active {
    transform: translateY(0);
  }
}

// 卡片底部
.card-footer {
  padding-top: 24px;
  margin-top: 32px;
  text-align: center;
  border-top: 1px solid #e2e8f0;
}

.footer-text {
  margin: 0;
  font-size: 12px;
  line-height: 1.6;
  color: #94a3b8;
}

.footer-link {
  color: #6366f1;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

// 版权
.copyright {
  position: absolute;
  bottom: 20px;
  z-index: 10;
  font-size: 12px;
  color: #94a3b8;
}

// 暗黑模式
.dark {
  .login-page {
    background: #0f172a;
  }

  .bg-gradient {
    background: linear-gradient(160deg, #1e293b 0%, #0f172a 100%);
  }

  .bg-grid {
    background-image:
      linear-gradient(rgb(51 65 85 / 30%) 1px, transparent 1px),
      linear-gradient(90deg, rgb(51 65 85 / 30%) 1px, transparent 1px);
  }

  .login-card {
    background: rgb(30 41 59 / 70%);
    border-color: rgb(255 255 255 / 10%);
    box-shadow:
      0 25px 50px -12px rgb(0 0 0 / 50%),
      0 0 0 1px rgb(255 255 255 / 5%) inset;

    .brand-name {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .welcome-title {
      color: #f1f5f9;
    }

    .welcome-subtitle {
      color: #94a3b8;
    }
  }

  .logo-icon {
    background: linear-gradient(135deg, #6366f1, #a78bfa);
    box-shadow: 0 8px 20px -8px rgb(99 102 241 / 40%);
  }

  .form-input {
    :deep(.el-input__wrapper) {
      background: #1e293b;
      border-color: #334155;

      &:hover {
        background: #334155;
      }

      &.is-focus {
        background: #1e293b;
        border-color: #818cf8;
        box-shadow: 0 0 0 4px rgb(129 140 248 / 15%);
      }
    }

    :deep(.el-input__inner) {
      color: #f1f5f9;

      &::placeholder {
        color: #64748b;
      }
    }
  }

  .remember-checkbox {
    :deep(.el-checkbox__label) {
      color: #94a3b8;
    }

    :deep(.el-checkbox__inner) {
      background: transparent;
      border-color: #475569;

      &::after {
        border-color: #fff;
      }
    }

    &.is-checked :deep(.el-checkbox__inner) {
      background-color: #6366f1;
      border-color: #6366f1;
    }
  }

  .submit-btn {
    background: linear-gradient(135deg, #6366f1, #a78bfa);
    box-shadow: 0 10px 25px -10px rgb(99 102 241 / 40%);

    &:hover {
      background: linear-gradient(135deg, #818cf8, #c4b5fd);
      box-shadow: 0 15px 30px -12px rgb(99 102 241 / 50%);
    }
  }

  .card-footer {
    border-color: #334155;
  }

  .footer-text {
    color: #64748b;
  }

  .copyright {
    color: #475569;
  }

  .gradient-orb {
    opacity: 0.3;
  }
}// 页面容器
</style>
