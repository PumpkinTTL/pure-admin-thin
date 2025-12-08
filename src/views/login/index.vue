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

// --- 逻辑保持不变 ---
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
          .catch(err => {
            message("跳转失败", { type: "error" });
          });
      })
      .catch(err => {
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
  <div
    class="min-h-screen w-full flex items-center justify-center bg-[#eaeff5] dark:bg-[#0f172a] transition-colors duration-500 relative overflow-hidden"
  >
    <!-- 背景装饰：极淡的背景图形 -->
    <div
      class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none"
    >
      <div
        class="absolute -top-[300px] -left-[150px] w-[600px] h-[600px] bg-indigo-100 dark:bg-indigo-900/20 rounded-full blur-[100px] opacity-60"
      />
      <div
        class="absolute bottom-[0] right-[0] w-[500px] h-[500px] bg-blue-50 dark:bg-blue-900/10 rounded-full blur-[80px] opacity-60"
      />
    </div>

    <!-- 主题切换 -->
    <div class="absolute top-6 right-8 z-50">
      <el-switch
        v-model="dataTheme"
        inline-prompt
        active-text="☀️"
        inactive-text="🌙"
        style="

--el-switch-on-color: #3b82f6; --el-switch-off-color: #64748b"
        @change="dataThemeChange"
      />
    </div>

    <!-- 登录主卡片 -->
    <div class="login-wrapper animate__animated animate__fadeInUp">
      <div
        class="relative z-10 w-full max-w-[900px] h-auto min-h-[520px] bg-white dark:bg-slate-800 rounded-[6px] shadow-2xl flex overflow-hidden"
      >
        <!-- 左侧：装饰区域 (40%宽度) -->
        <div
          class="hidden md:flex w-[40%] relative overflow-hidden flex-col justify-between p-8 text-white"
        >
          <!-- 抽象几何背景 (无纯色背景) -->
          <div class="absolute inset-0 z-0">
            <!-- 极淡的蓝色渐变 -->
            <div
              class="absolute inset-0 bg-gradient-to-br from-blue-50/30 to-indigo-50/20"
            />
            <!-- 装饰线条 - 淡化处理 -->
            <div
              class="absolute top-0 right-0 w-[300px] h-[300px] border-[40px] border-blue-200/20 rounded-full transform translate-x-1/2 -translate-y-1/2"
            />
            <div
              class="absolute bottom-0 left-0 w-[200px] h-[200px] border-[20px] border-indigo-200/15 rounded-full transform -translate-x-1/3 translate-y-1/3"
            />
            <!-- 极淡的悬浮块 -->
            <div
              class="absolute top-1/3 right-[-20px] w-20 h-20 bg-blue-100/30 backdrop-blur-sm rounded-xl transform rotate-12"
            />
            <div
              class="absolute bottom-1/4 left-[10%] w-14 h-14 bg-indigo-100/30 backdrop-blur-sm rounded-lg transform -rotate-12"
            />
          </div>

          <!-- 左侧内容 -->
          <div class="relative z-10">
            <div class="flex items-center gap-2 mb-6">
              <div
                class="w-8 h-8 rounded-md bg-blue-500 flex items-center justify-center text-white font-bold shadow-md"
              >
                <i class="fas fa-cube text-sm" />
              </div>
              <span class="text-sm font-semibold tracking-wide text-slate-700">
                System Admin
              </span>
            </div>
          </div>

          <div
            class="relative z-10 mb-4 animate__animated animate__fadeInLeft"
            :style="{ animationDelay: '0.1s' }"
          >
            <h2 class="text-2xl font-bold leading-tight mb-3 text-slate-800">
              Building the
              <br />
              <span class="text-blue-600">Future</span>
              of Data.
            </h2>
            <p class="text-slate-500 text-xs leading-relaxed">
              体验全新的管理系统，高效、安全、智能。我们致力于为您提供最极致的操作体验。
            </p>
          </div>

          <!-- 统计数据区域 -->
          <div
            class="relative z-10 mb-4 animate__animated animate__fadeInLeft"
            :style="{ animationDelay: '0.2s' }"
          >
            <div class="flex gap-4 text-slate-600">
              <div class="flex items-center gap-1">
                <i class="fas fa-shield-alt text-blue-500 text-xs" />
                <span class="text-xs font-medium">99.9% 系统稳定性</span>
              </div>
              <div class="flex items-center gap-1">
                <i class="fas fa-users text-blue-500 text-xs" />
                <span class="text-xs font-medium">1000+ 企业用户</span>
              </div>
              <div class="flex items-center gap-1">
                <i class="fas fa-clock text-blue-500 text-xs" />
                <span class="text-xs font-medium">24/7 在线服务</span>
              </div>
            </div>
          </div>

          <div
            class="relative z-10 text-xs text-slate-500 font-medium animate__animated animate__fadeInLeft"
            :style="{ animationDelay: '0.3s' }"
          >
            Version 3.0.1 PRO
          </div>
        </div>

        <!-- 右侧：登录表单 (60%宽度) -->
        <div
          class="w-full md:w-[60%] p-10 lg:p-14 flex flex-col justify-center bg-white dark:bg-slate-800 transition-colors duration-300"
        >
          <div class="brand-header animate__animated animate__fadeInLeft mb-10">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
              {{ title }}
            </h1>
            <div class="h-1 w-10 bg-blue-500 rounded-full mb-4" />
            <p class="text-slate-500 dark:text-slate-400 text-sm">
              请输入您的账户信息进行登录
            </p>
          </div>

          <el-form
            ref="ruleFormRef"
            :model="ruleForm"
            :rules="loginRules"
            class="elegant-form"
            size="large"
          >
            <div
              class="animate__animated animate__fadeInLeft mb-6"
              :style="{ animationDelay: '0.1s' }"
            >
              <el-form-item prop="account">
                <label
                  class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 ml-1"
                >
                  Account
                </label>
                <el-input
                  v-model="ruleForm.account"
                  placeholder="Username"
                  class="custom-input"
                >
                  <template #prefix>
                    <i class="fas fa-user text-slate-400" />
                  </template>
                </el-input>
              </el-form-item>
            </div>

            <div
              class="animate__animated animate__fadeInLeft mb-2"
              :style="{ animationDelay: '0.2s' }"
            >
              <el-form-item prop="password">
                <label
                  class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 ml-1"
                >
                  Password
                </label>
                <el-input
                  v-model="ruleForm.password"
                  type="password"
                  placeholder="Password"
                  show-password
                  class="custom-input"
                >
                  <template #prefix>
                    <i class="fas fa-lock text-slate-400" />
                  </template>
                </el-input>
              </el-form-item>
            </div>

            <div
              class="animate__animated animate__fadeInLeft mb-8 mt-2"
              :style="{ animationDelay: '0.3s' }"
            >
              <div class="flex items-center justify-between">
                <el-checkbox
                  v-model="rememberMe"
                  label="记住我"
                  class="custom-checkbox"
                />
                <a
                  class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 cursor-pointer transition-colors"
                >
                  忘记密码?
                </a>
              </div>
            </div>

            <div
              class="animate__animated animate__fadeInLeft"
              :style="{ animationDelay: '0.4s' }"
            >
              <el-button
                class="w-full !h-[46px] !rounded-md !text-base !font-semibold !bg-blue-500 hover:!bg-blue-600 !text-white !border-0 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-0.5"
                :loading="loading"
                @click="onLogin(ruleFormRef)"
              >
                {{ loading ? "登录中..." : "立即登录" }}
                <i v-if="!loading" class="ml-2 fas fa-arrow-right" />
              </el-button>
            </div>
          </el-form>

          <div
            class="animate__animated animate__fadeInLeft mt-auto pt-10 text-center"
            :style="{ animationDelay: '0.5s' }"
          >
            <p class="text-sm text-slate-400">
              没有账号？
              <span
                class="text-blue-600 dark:text-blue-400 font-semibold cursor-pointer hover:underline"
              >
                联系管理员注册
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <div
      class="absolute bottom-4 w-full text-center text-xs text-slate-400/60 font-light"
    >
      &copy; {{ new Date().getFullYear() }} {{ title }}. All rights reserved.
    </div>
  </div>
</template>

<style lang="scss" scoped>
/* 
  优雅表单样式重构 (Elegant Form Style)
*/

:deep(.elegant-form) {
  // 移除默认间距
  .el-form-item {
    margin-bottom: 0;
  }

  // 1. 输入框容器
  .el-input__wrapper {
    height: 48px;

    /* 标准圆角 */
    padding: 1px 15px;
    background-color: #f8fafc;

    /* 移除默认边框 */
    border: 1px solid #e2e8f0;

    /* slate-200 - 极细的边框 */
    border-radius: 8px;

    /* slate-50 - 极淡的灰背景 */
    box-shadow: none !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    // 图标
    .el-input__prefix-inner {
      /* slate-400 */
      font-size: 1.1rem;
      color: #94a3b8;
      transition: color 0.3s;
    }

    // 输入文字
    .el-input__inner {
      /* slate-700 */
      font-weight: 500;
      color: #334155;

      &::placeholder {
        color: #cbd5e1;

        /* slate-300 */
      }
    }

    // 悬停
    &:hover {
      background-color: #fff;
      border-color: #cbd5e1;
    }

    // 聚焦 (Focus) - 关键的微交互
    &.is-focus {
      background-color: #fff;
      border-color: #6366f1;

      /* indigo-500 */
      // 模拟 Ring 效果：扩散的蓝色光晕
      box-shadow: 0 0 0 4px rgb(99 102 241 / 10%) !important;

      .el-input__prefix-inner {
        color: #6366f1;
      }
    }
  }

  // 错误状态
  .is-error .el-input__wrapper {
    background-color: #fef2f2;
    border-color: #ef4444;
    box-shadow: none !important;

    &.is-focus {
      box-shadow: 0 0 0 4px rgb(239 68 68 / 10%) !important;
    }
  }
}

// 2. 复选框样式
:deep(.custom-checkbox) {
  .el-checkbox__label {
    color: #64748b;
  }

  .el-checkbox__inner {
    border-color: #cbd5e1;
    border-radius: 4px;
  }

  &.is-checked .el-checkbox__inner {
    background-color: #0f172a;

    /* 选中时变黑，显高级 */
    border-color: #0f172a;
  }
}

/* 
  暗黑模式适配
*/
.dark {
  :deep(.elegant-form) {
    .el-input__wrapper {
      background-color: #1e293b;

      /* slate-800 */
      border-color: #334155;

      /* slate-700 */

      .el-input__inner {
        color: #f1f5f9;
      }

      &:hover {
        border-color: #475569;
      }

      &.is-focus {
        background-color: #1e293b;
        border-color: #818cf8;

        /* indigo-400 */
        box-shadow: 0 0 0 4px rgb(129 140 248 / 15%) !important;

        .el-input__prefix-inner {
          color: #818cf8;
        }
      }
    }
  }

  :deep(.custom-checkbox) {
    &.is-checked .el-checkbox__inner {
      background-color: #6366f1;
      border-color: #6366f1;
    }
  }
}
</style>
