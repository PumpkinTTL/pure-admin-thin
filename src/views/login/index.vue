<script setup lang="ts">
import Motion from "./utils/motion";
import { useRouter } from "vue-router";
import { message } from "@/utils/message";
import { loginRules } from "./utils/rule";
import { useNav } from "@/layout/hooks/useNav";
import type { FormInstance } from "element-plus";
import { useLayout } from "@/layout/hooks/useLayout";
import { initRouter, getTopMenu } from "@/router/utils";

import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { ref, reactive, onMounted, onBeforeUnmount } from "vue";
import { useDataThemeChange } from "@/layout/hooks/useDataThemeChange";

import dayIcon from "@/assets/svg/day.svg?component";
import darkIcon from "@/assets/svg/dark.svg?component";
import Lock from "@iconify-icons/ri/lock-fill";
import User from "@iconify-icons/ri/user-3-fill";
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
        :active-icon="dayIcon"
        :inactive-icon="darkIcon"
        style="

--el-switch-on-color: #334155; --el-switch-off-color: #94a3b8"
        @change="dataThemeChange"
      />
    </div>

    <!-- 登录主卡片 -->
    <Motion :delay="100">
      <div
        class="relative z-10 w-full max-w-[900px] h-auto min-h-[580px] bg-white dark:bg-slate-800 rounded-[24px] shadow-2xl flex overflow-hidden"
      >
        <!-- 左侧：装饰区域 (40%宽度) -->
        <div
          class="hidden md:flex w-[40%] bg-slate-900 relative overflow-hidden flex-col justify-between p-10 text-white"
        >
          <!-- 抽象几何背景 (纯 CSS 绘制) -->
          <div class="absolute inset-0 z-0">
            <!-- 渐变底色 -->
            <div
              class="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-950"
            />
            <!-- 装饰线条 -->
            <div
              class="absolute top-0 right-0 w-[300px] h-[300px] border-[40px] border-slate-700/30 rounded-full transform translate-x-1/2 -translate-y-1/2"
            />
            <div
              class="absolute bottom-0 left-0 w-[200px] h-[200px] border-[20px] border-indigo-500/10 rounded-full transform -translate-x-1/3 translate-y-1/3"
            />
            <!-- 玻璃质感悬浮块 -->
            <div
              class="absolute top-1/3 right-[-20px] w-24 h-24 bg-indigo-500/20 backdrop-blur-md rounded-2xl transform rotate-12 border border-white/10"
            />
            <div
              class="absolute bottom-1/4 left-[10%] w-16 h-16 bg-blue-400/20 backdrop-blur-md rounded-xl transform -rotate-12 border border-white/10"
            />
          </div>

          <!-- 左侧内容 -->
          <div class="relative z-10">
            <div class="flex items-center gap-3 mb-8">
              <div
                class="w-10 h-10 rounded-lg bg-indigo-500 flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-500/40"
              >
                S
              </div>
              <span class="text-lg font-semibold tracking-wide">
                System Admin
              </span>
            </div>
          </div>

          <div class="relative z-10 mb-6">
            <h2 class="text-3xl font-bold leading-tight mb-4">
              Building the
              <br />
              <span class="text-indigo-400">Future</span>
              of Data.
            </h2>
            <p class="text-slate-400 text-sm leading-relaxed">
              体验全新的管理系统，高效、安全、智能。我们致力于为您提供最极致的操作体验。
            </p>
          </div>

          <div class="relative z-10 text-xs text-slate-500 font-medium">
            Version 3.0.1 PRO
          </div>
        </div>

        <!-- 右侧：登录表单 (60%宽度) -->
        <div
          class="w-full md:w-[60%] p-10 lg:p-14 flex flex-col justify-center bg-white dark:bg-slate-800 transition-colors duration-300"
        >
          <Motion :delay="200">
            <div class="mb-10">
              <h1
                class="text-2xl font-bold text-slate-900 dark:text-white mb-2"
              >
                {{ title }}
              </h1>
              <div class="h-1 w-10 bg-indigo-500 rounded-full mb-4" />
              <p class="text-slate-500 dark:text-slate-400 text-sm">
                请输入您的账户信息进行登录
              </p>
            </div>
          </Motion>

          <el-form
            ref="ruleFormRef"
            :model="ruleForm"
            :rules="loginRules"
            class="elegant-form"
            size="large"
          >
            <Motion :delay="300">
              <el-form-item prop="account" class="mb-6">
                <label
                  class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 ml-1"
                >
                  Account
                </label>
                <el-input
                  v-model="ruleForm.account"
                  placeholder="Username"
                  :prefix-icon="useRenderIcon(User)"
                  class="custom-input"
                />
              </el-form-item>
            </Motion>

            <Motion :delay="400">
              <el-form-item prop="password" class="mb-2">
                <label
                  class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 ml-1"
                >
                  Password
                </label>
                <el-input
                  v-model="ruleForm.password"
                  type="password"
                  placeholder="Password"
                  :prefix-icon="useRenderIcon(Lock)"
                  show-password
                  class="custom-input"
                />
              </el-form-item>
            </Motion>

            <Motion :delay="500">
              <div class="flex items-center justify-between mb-8 mt-2">
                <el-checkbox
                  v-model="rememberMe"
                  label="记住我"
                  class="custom-checkbox"
                />
                <a
                  class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 cursor-pointer transition-colors"
                >
                  忘记密码?
                </a>
              </div>
            </Motion>

            <Motion :delay="600">
              <el-button
                class="w-full !h-[50px] !rounded-lg !text-base !font-semibold !bg-slate-900 dark:!bg-indigo-600 hover:!bg-slate-800 dark:hover:!bg-indigo-500 !text-white !border-0 transition-all duration-300 shadow-xl shadow-slate-200 dark:shadow-none hover:shadow-2xl hover:-translate-y-0.5"
                :loading="loading"
                @click="onLogin(ruleFormRef)"
              >
                {{ loading ? "登录中..." : "立即登录" }}
                <i v-if="!loading" class="ml-2 fa fa-arrow-right" />
              </el-button>
            </Motion>
          </el-form>

          <Motion :delay="700">
            <div class="mt-auto pt-10 text-center">
              <p class="text-sm text-slate-400">
                没有账号？
                <span
                  class="text-indigo-600 dark:text-indigo-400 font-semibold cursor-pointer hover:underline"
                >
                  联系管理员注册
                </span>
              </p>
            </div>
          </Motion>
        </div>
      </div>
    </Motion>

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
