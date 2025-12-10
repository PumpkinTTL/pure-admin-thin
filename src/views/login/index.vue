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
    class="min-h-screen w-full flex items-center justify-center bg-[#eaeff5] dark:bg-[#0f172a] transition-colors duration-500 relative overflow-x-hidden"
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
    <div class="login-wrapper w-full px-4 md:px-0">
      <div
        class="relative z-10 w-full max-w-[900px] h-auto min-h-[520px] bg-white/90 backdrop-blur-sm dark:bg-slate-800/90 rounded-[6px] shadow-2xl flex overflow-hidden mx-auto my-4 md:my-0"
      >
        <!-- 左侧：装饰区域 (40%宽度) - 重新设计 -->
        <div
          class="hidden md:flex w-[40%] border-r border-slate-200/60 relative overflow-hidden"
        >
          <!-- 内容区域 -->
          <div class="flex flex-col h-full px-6 py-6 w-full relative z-10">
            <!-- 品牌区域 - 简洁设计 -->
            <div
              class="text-center mb-6 animate__animated animate__fadeInUp"
              style="
                animation-duration: 0.6s;
                animation-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);
              "
            >
              <div class="flex items-center justify-center mb-4">
                <div
                  class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-md shadow-blue-500/20 hover:shadow-blue-500/30 transition-shadow duration-300 animate__animated animate__pulse animate__infinite animate__slower"
                  style="animation-duration: 3s"
                >
                  <i class="fas fa-cube text-lg" />
                </div>
              </div>
              <h1
                class="text-lg font-bold mb-1 tracking-tight shine-text"
                style="animation-delay: 0.15s"
              >
                System Admin
              </h1>
              <p
                class="text-xs font-medium shine-text-subtitle"
                style="animation-delay: 0.3s"
              >
                Enterprise Platform
              </p>
              <div
                class="h-0.5 w-16 bg-gradient-to-r from-blue-500 to-purple-500 mx-auto mt-3 rounded-full animate__animated animate__fadeIn"
                style=" animation-duration: 0.5s;animation-delay: 0.5s"
              />
            </div>

            <!-- 简洁功能展示 -->
            <div class="flex-1">
              <div class="space-y-4">
                <!-- 核心优势 -->
                <div
                  class="bg-white/60 backdrop-blur-sm rounded-xl p-4 border border-white/50 shadow-sm animate__animated animate__fadeInUp"
                  style="
                    animation-duration: 0.5s;
                    animation-timing-function: cubic-bezier(
                      0.34,
                      1.56,
                      0.64,
                      1
                    );
                    animation-delay: 0.2s;
                  "
                >
                  <h3
                    class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2"
                  >
                    <i class="fas fa-star text-yellow-500 text-xs" />
                    核心优势
                  </h3>
                  <div class="space-y-3">
                    <div
                      class="flex items-center gap-2 animate__animated animate__fadeInUp"
                      style="
                        animation-duration: 0.4s;
                        animation-timing-function: ease-out;
                        animation-delay: 0.35s;
                      "
                    >
                      <div
                        class="w-7 h-7 rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center text-white flex-shrink-0"
                      >
                        <i class="fas fa-shield-alt text-xs" />
                      </div>
                      <div>
                        <h4 class="text-xs font-bold text-slate-700">
                          安全可靠
                        </h4>
                        <p class="text-[10px] text-slate-500">企业级防护</p>
                      </div>
                    </div>
                    <div
                      class="flex items-center gap-2 animate__animated animate__fadeInUp"
                      style="
                        animation-duration: 0.4s;
                        animation-timing-function: ease-out;
                        animation-delay: 0.45s;
                      "
                    >
                      <div
                        class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white flex-shrink-0"
                      >
                        <i class="fas fa-chart-line text-xs" />
                      </div>
                      <div>
                        <h4 class="text-xs font-bold text-slate-700">
                          数据驱动
                        </h4>
                        <p class="text-[10px] text-slate-500">智能分析</p>
                      </div>
                    </div>
                    <div
                      class="flex items-center gap-2 animate__animated animate__fadeInUp"
                      style="
                        animation-duration: 0.4s;
                        animation-timing-function: ease-out;
                        animation-delay: 0.55s;
                      "
                    >
                      <div
                        class="w-7 h-7 rounded-lg bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center text-white flex-shrink-0"
                      >
                        <i class="fas fa-cloud text-xs" />
                      </div>
                      <div>
                        <h4 class="text-xs font-bold text-slate-700">
                          云端协同
                        </h4>
                        <p class="text-[10px] text-slate-500">无缝协作</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 系统状态 -->
                <div
                  class="bg-white/60 backdrop-blur-sm rounded-xl p-4 border border-white/50 shadow-sm animate__animated animate__fadeInUp"
                  style="
                    animation-duration: 0.5s;
                    animation-timing-function: cubic-bezier(
                      0.34,
                      1.56,
                      0.64,
                      1
                    );
                    animation-delay: 0.65s;
                  "
                >
                  <h3
                    class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2"
                  >
                    <i class="fas fa-heartbeat text-red-500 text-xs" />
                    系统状态
                  </h3>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <div
                        class="w-2 h-2 bg-green-400 rounded-full animate-pulse shadow-sm shadow-green-400/30"
                      />
                      <span class="text-xs font-semibold text-slate-700">
                        运行正常
                      </span>
                    </div>
                    <div class="text-right">
                      <div class="text-[10px] text-slate-500">Version</div>
                      <div class="text-xs font-bold text-slate-700">v3.0.1</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 底部信息 -->
            <div
              class="pt-4 border-t border-slate-200/60 animate__animated animate__fadeIn"
              style=" animation-duration: 0.4s;animation-delay: 0.8s"
            >
              <div class="text-center">
                <p class="text-[10px] text-slate-500 mb-1">企业级管理平台</p>
                <p class="text-[10px] text-slate-400">© 2024</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 右侧：登录表单 (60%宽度) -->
        <div
          class="w-full md:w-[60%] p-3 sm:p-4 md:p-10 lg:p-14 flex flex-col justify-center transition-colors duration-300"
        >
          <!-- 移动端品牌信息 -->
          <div
            class="md:hidden flex items-center gap-2 mb-3 animate__animated animate__fadeInDown"
          >
            <div
              class="w-6 h-6 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-md"
            >
              <i class="fas fa-layer-group text-[10px]" />
            </div>
            <div>
              <h1
                class="text-xs font-bold shine-text"
                style="animation-delay: 0.1s"
              >
                System Admin
              </h1>
              <p
                class="text-[9px] shine-text-subtitle"
                style="animation-delay: 1s"
              >
                Professional
              </p>
            </div>
          </div>

          <div
            class="brand-header animate__animated animate__fadeInUp mb-8"
            style="
              animation-duration: 0.6s;
              animation-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);
            "
          >
            <h1
              class="text-2xl font-bold shine-text mb-2"
              style="animation-delay: 0.15s"
            >
              {{ title }}
            </h1>
            <div
              class="h-1 w-10 bg-blue-500 rounded-full mb-3 animate__animated animate__fadeIn"
              style=" animation-duration: 0.4s;animation-delay: 0.3s"
            />
            <div
              class="flex items-center gap-2 animate__animated animate__fadeIn"
              style=" animation-duration: 0.5s;animation-delay: 0.4s"
            >
              <i class="fas fa-user-shield text-blue-500 text-sm" />
              <p
                class="text-slate-600 dark:text-slate-300 text-sm font-medium shine-text-subtitle"
                style="animation-delay: 0.3s"
              >
                请输入账户信息登录
              </p>
            </div>
          </div>

          <el-form
            ref="ruleFormRef"
            :model="ruleForm"
            :rules="loginRules"
            class="elegant-form"
            size="large"
          >
            <div
              class="animate__animated animate__fadeInUp mb-4"
              :style="{
                animationDelay: '0.5s',
                animationDuration: '0.4s',
                animationTimingFunction: 'ease-out'
              }"
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
              class="animate__animated animate__fadeInUp mb-2"
              :style="{
                animationDelay: '0.6s',
                animationDuration: '0.4s',
                animationTimingFunction: 'ease-out'
              }"
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
              class="animate__animated animate__fadeIn mb-5 mt-1"
              :style="{ animationDelay: '0.7s', animationDuration: '0.3s' }"
            >
              <div class="flex items-center justify-between">
                <el-checkbox
                  v-model="rememberMe"
                  label="记住我"
                  class="custom-checkbox"
                />
                <a
                  class="text-[10px] md:text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 cursor-pointer transition-colors"
                >
                  忘记密码?
                </a>
              </div>
            </div>

            <div
              class="animate__animated animate__fadeInUp"
              :style="{
                animationDelay: '0.8s',
                animationDuration: '0.5s',
                animationTimingFunction: 'cubic-bezier(0.34, 1.56, 0.64, 1)'
              }"
            >
              <el-button
                class="w-full !h-[46px] !rounded-md !text-base !font-semibold !bg-blue-500 hover:!bg-blue-600 !text-white !border-0 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-0.5"
                :loading="loading"
                @click="onLogin(ruleFormRef)"
              >
                {{ loading ? "登录中..." : "登录" }}
                <i v-if="!loading" class="ml-2 fas fa-arrow-right" />
              </el-button>
            </div>
          </el-form>

          <div
            class="animate__animated animate__fadeIn mt-auto pt-3 md:pt-6 text-center"
            :style="{ animationDelay: '0.9s', animationDuration: '0.4s' }"
          >
            <p class="text-[10px] md:text-sm text-slate-400">
              没有账号？
              <span
                class="text-blue-600 dark:text-blue-400 font-semibold cursor-pointer hover:underline"
              >
                联系管理员
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <div
      class="absolute bottom-3 md:bottom-4 w-full text-center text-[9px] md:text-xs text-slate-400/60 font-light px-4"
    >
      &copy; {{ new Date().getFullYear() }} {{ title }}. All rights reserved.
    </div>
  </div>
</template>

<style lang="scss" scoped>


@keyframes shine {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: -200% 0;
  }
}

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

/* 暗黑模式适配
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

/* 镜面扫过文字效果 - 高级配色 */
.shine-text {
  position: relative;
  background: linear-gradient(
    120deg,
    #0f172a 0%,
    #1e293b 15%,
    #0ea5e9 30%,
    #6366f1 45%,
    #8b5cf6 60%,
    #a855f7 75%,
    #0f172a 100%
  );
  filter: drop-shadow(0 0 10px rgb(99 102 241 / 30%));
  background-position: 100% 0;
  background-clip: text;
  background-clip: text;
  background-size: 300% 100%;
  animation: shine 4s ease-in-out infinite;
  -webkit-text-fill-color: transparent;
}

.shine-text-subtitle {
  position: relative;
  background: linear-gradient(
    120deg,
    #374151 0%,
    #6b7280 20%,
    #d1d5db 40%,
    #f59e0b 55%,
    #fbbf24 70%,
    #f59e0b 85%,
    #374151 100%
  );
  filter: drop-shadow(0 0 8px rgb(245 158 11 / 20%));
  background-position: 100% 0;
  background-clip: text;
  background-clip: text;
  background-size: 300% 100%;
  animation: shine 5s ease-in-out infinite;
  animation-delay: 0.5s;
  -webkit-text-fill-color: transparent;
}

/* 暗黑模式下的镜面效果 - 更奢华的配色 */
.dark .shine-text {
  background: linear-gradient(
    120deg,
    #f8fafc 0%,
    #e2e8f0 15%,
    #60a5fa 30%,
    #818cf8 45%,
    #a78bfa 60%,
    #c084fc 75%,
    #f8fafc 100%
  );
  filter: drop-shadow(0 0 12px rgb(129 140 248 / 40%));
  background-position: 100% 0;
  background-clip: text;
  background-clip: text;
  background-size: 300% 100%;
  animation: shine 4s ease-in-out infinite;
  -webkit-text-fill-color: transparent;
}

.dark .shine-text-subtitle {
  background: linear-gradient(
    120deg,
    #f1f5f9 0%,
    #cbd5e1 20%,
    #fde047 40%,
    #facc15 55%,
    #eab308 70%,
    #ca8a04 85%,
    #f1f5f9 100%
  );
  filter: drop-shadow(0 0 10px rgb(250 204 21 / 30%));
  background-position: 100% 0;
  background-clip: text;
  background-clip: text;
  background-size: 300% 100%;
  animation: shine 5s ease-in-out infinite;
  animation-delay: 0.5s;
  -webkit-text-fill-color: transparent;
}

/*
  优雅表单样式重构 (Elegant Form Style)
*/
</style>
