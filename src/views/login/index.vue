<script setup lang="ts">
import Motion from "./utils/motion";
import { useRouter } from "vue-router";
import { message } from "@/utils/message";
import { loginRules } from "./utils/rule";
import { useNav } from "@/layout/hooks/useNav";
import type { FormInstance } from "element-plus";
import { useLayout } from "@/layout/hooks/useLayout";
import { initRouter, getTopMenu } from "@/router/utils";

import { ref, reactive, onMounted, onBeforeUnmount } from "vue";
import { useDataThemeChange } from "@/layout/hooks/useDataThemeChange";

import dayIcon from "@/assets/svg/day.svg?component";
import darkIcon from "@/assets/svg/dark.svg?component";
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
  <div class="login-layout">
    <!-- 背景装饰 -->
    <div class="bg-decoration">
      <div class="bg-shapes">
        <div class="shape shape-1" />
        <div class="shape shape-2" />
        <div class="shape shape-3" />
      </div>
    </div>

    <!-- 主题切换 -->
    <div class="theme-switch">
      <el-switch
        v-model="dataTheme"
        inline-prompt
        :active-icon="dayIcon"
        :inactive-icon="darkIcon"
        @change="dataThemeChange"
      />
    </div>

    <!-- 主容器 -->
    <div class="login-container">
      <Motion :delay="100">
        <div class="login-wrapper">
          <!-- 左侧装饰区 -->
          <div class="decorative-side">
            <div class="side-content">
              <div class="brand-header">
                <div class="brand-icon">
                  <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <defs>
                      <linearGradient
                        id="iconGradient"
                        x1="0%"
                        y1="0%"
                        x2="100%"
                        y2="100%"
                      >
                        <stop
                          offset="0%"
                          style="stop-color: #3b82f6; stop-opacity: 1"
                        />
                        <stop
                          offset="100%"
                          style="stop-color: #2563eb; stop-opacity: 1"
                        />
                      </linearGradient>
                    </defs>
                    <rect
                      x="4"
                      y="4"
                      width="40"
                      height="40"
                      rx="10"
                      fill="url(#iconGradient)"
                      opacity="0.12"
                    />
                    <path
                      d="M24 10L34 13V27L24 32L14 27V13L24 10Z"
                      stroke="url(#iconGradient)"
                      stroke-width="2"
                      fill="none"
                      stroke-linejoin="round"
                    />
                    <circle cx="24" cy="19" r="3.5" fill="url(#iconGradient)" />
                    <path
                      d="M17 29L24 26L31 29"
                      stroke="url(#iconGradient)"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                    <circle
                      cx="24"
                      cy="24"
                      r="1.5"
                      fill="url(#iconGradient)"
                      opacity="0.6"
                    />
                  </svg>
                </div>
                <div class="brand-text">
                  <h1 class="brand-title">{{ title }}</h1>
                  <p class="brand-subtitle">智能化管理平台</p>
                </div>
              </div>

              <div class="stats-section">
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="fas fa-chart-line" />
                  </div>
                  <div class="stat-number">99.9%</div>
                  <div class="stat-label">系统稳定性</div>
                </div>
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="fas fa-users" />
                  </div>
                  <div class="stat-number">1000+</div>
                  <div class="stat-label">企业用户</div>
                </div>
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="fas fa-headset" />
                  </div>
                  <div class="stat-number">24/7</div>
                  <div class="stat-label">在线服务</div>
                </div>
              </div>

              <div class="feature-showcase">
                <div class="feature-card">
                  <div class="card-icon">
                    <i class="fas fa-bolt" />
                  </div>
                  <div class="card-content">
                    <h3>高效协同</h3>
                    <p>实时协作，无缝沟通</p>
                  </div>
                </div>
                <div class="feature-card">
                  <div class="card-icon">
                    <i class="fas fa-shield-alt" />
                  </div>
                  <div class="card-content">
                    <h3>安全可靠</h3>
                    <p>企业级安全防护</p>
                  </div>
                </div>
                <div class="feature-card">
                  <div class="card-icon">
                    <i class="fas fa-chart-line" />
                  </div>
                  <div class="card-content">
                    <h3>数据洞察</h3>
                    <p>智能分析，精准决策</p>
                  </div>
                </div>
              </div>

              <div class="tech-stack">
                <h3 class="tech-title">技术栈</h3>
                <div class="tech-list">
                  <div class="tech-item">
                    <i class="fab fa-vuejs" />
                    <span>Vue 3</span>
                  </div>
                  <div class="tech-item">
                    <i class="fas fa-code" />
                    <span>TypeScript</span>
                  </div>
                  <div class="tech-item">
                    <i class="fas fa-layer-group" />
                    <span>Element Plus</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 右侧登录区 -->
          <div class="login-side">
            <div class="login-panel">
              <!-- 登录头部 -->
              <div class="panel-header">
                <div class="header-badge">
                  <i class="fas fa-shield-alt" />
                  <span>安全登录</span>
                </div>
                <h2 class="login-title">欢迎回来</h2>
                <p class="login-subtitle">请使用您的账户凭据登录</p>
              </div>

              <!-- 登录表单 -->
              <el-form
                ref="ruleFormRef"
                :model="ruleForm"
                :rules="loginRules"
                class="login-form"
              >
                <Motion :delay="200">
                  <el-form-item prop="account" class="form-item">
                    <label class="input-label">
                      <i class="fas fa-user" />
                      用户名或邮箱
                    </label>
                    <el-input
                      v-model="ruleForm.account"
                      placeholder="请输入用户名或邮箱地址"
                      class="form-input"
                    >
                      <template #prefix>
                        <i class="fas fa-at" />
                      </template>
                    </el-input>
                  </el-form-item>
                </Motion>

                <Motion :delay="300">
                  <el-form-item prop="password" class="form-item">
                    <label class="input-label">
                      <i class="fas fa-lock" />
                      密码
                    </label>
                    <el-input
                      v-model="ruleForm.password"
                      type="password"
                      placeholder="请输入您的密码"
                      show-password
                      class="form-input"
                    >
                      <template #prefix>
                        <i class="fas fa-key" />
                      </template>
                    </el-input>
                  </el-form-item>
                </Motion>

                <Motion :delay="400">
                  <div class="form-actions">
                    <el-checkbox v-model="rememberMe" class="checkbox">
                      记住我
                    </el-checkbox>
                    <a class="link">忘记密码？</a>
                  </div>
                </Motion>

                <Motion :delay="500">
                  <el-button
                    class="submit-btn"
                    :loading="loading"
                    @click="onLogin(ruleFormRef)"
                  >
                    <template #default>
                      <i v-if="!loading" class="fas fa-sign-in-alt" />
                      <i v-else class="fas fa-circle-notch fa-spin" />
                      <span>{{ loading ? "登录中..." : "立即登录" }}</span>
                    </template>
                  </el-button>
                </Motion>
              </el-form>

              <!-- 登录底部 -->
              <div class="panel-footer">
                <div class="footer-divider">
                  <span>安全提示</span>
                </div>
                <div class="security-tips">
                  <div class="tip-item">
                    <i class="fas fa-user-shield" />
                    <span>256位SSL加密传输</span>
                  </div>
                  <div class="tip-item">
                    <i class="fas fa-clock" />
                    <span>登录状态实时监控</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Motion>
    </div>
  </div>
</template>

<style lang="scss" scoped>


@keyframes float {
  0%,
  100% {
    transform: translateY(0) rotate(0deg);
  }

  50% {
    transform: translateY(-40px) rotate(180deg);
  }
}

@keyframes wrapper-enter {
  from {
    opacity: 0;
    transform: translateY(60px) scale(0.9);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* 响应式 */
@media (width <= 968px) {
  .login-wrapper {
    grid-template-columns: 1fr;
    max-width: 420px;
    margin: 0 auto;
  }

  .decorative-side {
    padding: 24px 20px;

    .side-content {
      .brand-header {
        flex-direction: column;
        gap: 10px;
        margin-bottom: 14px;
        text-align: center;

        .brand-icon {
          width: 36px;
          height: 36px;
        }

        .brand-text {
          .brand-title {
            font-size: 16px;
          }

          .brand-subtitle {
            font-size: 10px;
          }
        }
      }

      .stats-section {
        padding: 8px;
        margin-bottom: 12px;

        .stat-item {
          gap: 3px;

          .stat-icon {
            width: 18px;
            height: 18px;

            i {
              font-size: 8px;
            }
          }

          .stat-number {
            font-size: 10px;
          }

          .stat-label {
            font-size: 7px;
          }
        }
      }

      .feature-showcase {
        gap: 8px;

        .feature-card {
          padding: 10px 12px;

          .card-icon {
            width: 30px;
            height: 30px;

            i {
              font-size: 13px;
            }
          }

          .card-content {
            h3 {
              font-size: 11px;
            }

            p {
              font-size: 8px;
            }
          }
        }
      }

      .tech-stack {
        padding-top: 12px;

        .tech-title {
          margin-bottom: 8px;
          font-size: 9px;
        }

        .tech-list {
          gap: 5px;

          .tech-item {
            padding: 5px 8px;

            i {
              font-size: 10px;
            }

            span {
              font-size: 8px;
            }
          }
        }
      }
    }
  }

  .login-side {
    padding: 28px 24px;
  }

  .security-tips {
    flex-direction: column;
    gap: 10px;
    align-items: center;
  }
}

.login-layout {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

/* 背景装饰 */
.bg-decoration {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;

  .bg-shapes {
    position: absolute;
    width: 100%;
    height: 100%;

    .shape {
      position: absolute;
      filter: blur(80px);
      border-radius: 50%;
      opacity: 0.3;

      &.shape-1 {
        top: -200px;
        right: -200px;
        width: 500px;
        height: 500px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        animation: float 20s infinite ease-in-out;
      }

      &.shape-2 {
        bottom: -150px;
        left: -150px;
        width: 400px;
        height: 400px;
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        animation: float 15s infinite ease-in-out reverse;
      }

      &.shape-3 {
        top: 50%;
        left: 50%;
        width: 350px;
        height: 350px;
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        transform: translate(-50%, -50%);
        animation: float 18s infinite ease-in-out;
      }
    }
  }
}

/* 主题切换 */
.theme-switch {
  position: absolute;
  top: 30px;
  right: 30px;
  z-index: 100;
}

/* 主容器 */
.login-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 900px;
}

/* 登录包装器 */
.login-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  overflow: hidden;
  background: white;
  border-radius: 6px;
  box-shadow: 0 20px 60px rgb(59 130 246 / 15%);
  animation: wrapper-enter 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* 左侧装饰区 */
.decorative-side {
  position: relative;
  display: flex;
  align-items: stretch;
  padding: 32px 28px;
  overflow: hidden;
  color: #1e293b;

  .side-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: 100%;

    .brand-header {
      display: flex;
      gap: 14px;
      align-items: center;
      margin-bottom: 16px;

      .brand-icon {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
      }

      .brand-text {
        .brand-title {
          margin: 0 0 4px;
          font-size: 18px;
          font-weight: 700;
          color: #1e293b;
          letter-spacing: -0.5px;
        }

        .brand-subtitle {
          margin: 0;
          font-size: 11px;
          font-weight: 400;
          color: #64748b;
        }
      }
    }

    .stats-section {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 10px;
      margin-bottom: 16px;
      background: rgb(255 255 255 / 60%);
      border: 1px solid rgb(59 130 246 / 10%);
      border-radius: 8px;

      .stat-item {
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: 4px;
        align-items: center;
        text-align: center;

        .stat-icon {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 22px;
          height: 22px;
          margin-bottom: 2px;
          background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
          border-radius: 5px;

          i {
            font-size: 10px;
            color: white;
          }
        }

        .stat-number {
          margin-bottom: 2px;
          font-size: 12px;
          font-weight: 600;
          color: #3b82f6;
        }

        .stat-label {
          font-size: 8px;
          font-weight: 500;
          color: #64748b;
        }
      }
    }

    .feature-showcase {
      display: flex;
      flex: 1;
      flex-direction: column;
      gap: 10px;

      .feature-card {
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 12px 14px;
        background: rgb(255 255 255 / 75%);
        border: 1px solid rgb(59 130 246 / 12%);
        border-radius: 10px;

        .card-icon {
          display: flex;
          flex-shrink: 0;
          align-items: center;
          justify-content: center;
          width: 36px;
          height: 36px;
          background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
          border-radius: 9px;
          box-shadow: 0 2px 8px rgb(59 130 246 / 20%);

          i {
            font-size: 15px;
            color: white;
          }
        }

        .card-content {
          flex: 1;

          h3 {
            margin: 0 0 4px;
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 0.1px;
          }

          p {
            margin: 0;
            font-size: 9px;
            line-height: 1.5;
            color: #64748b;
          }
        }
      }
    }

    .tech-stack {
      padding-top: 16px;
      margin-top: auto;

      .tech-title {
        margin: 0 0 10px;
        font-size: 10px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .tech-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;

        .tech-item {
          display: flex;
          gap: 5px;
          align-items: center;
          padding: 6px 10px;
          white-space: nowrap;
          background: rgb(255 255 255 / 50%);
          border: 1px solid rgb(59 130 246 / 8%);
          border-radius: 5px;
          transition: all 0.3s;

          &:hover {
            background: rgb(255 255 255 / 80%);
            border-color: rgb(59 130 246 / 20%);
            transform: translateX(2px);
          }

          i {
            width: 12px;
            font-size: 11px;
            color: #3b82f6;
            text-align: center;
          }

          span {
            font-size: 9px;
            font-weight: 500;
            color: #475569;
          }
        }
      }
    }
  }
}

/* 右侧登录区 */
.login-side {
  padding: 32px 28px;
  background: white;
}

/* 登录面板 */
.login-panel {
  max-width: 340px;
  margin: 0 auto;
}

.panel-header {
  margin-bottom: 24px;

  .header-badge {
    display: inline-flex;
    gap: 5px;
    align-items: center;
    padding: 5px 10px;
    margin-bottom: 12px;
    font-size: 10px;
    font-weight: 600;
    color: #3b82f6;
    background: rgb(59 130 246 / 8%);
    border-radius: 12px;

    i {
      font-size: 11px;
    }
  }

  .login-title {
    margin: 0 0 5px;
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.5px;
  }

  .login-subtitle {
    margin: 0;
    font-size: 12px;
    color: #64748b;
  }
}

/* 表单 */
.login-form {
  .form-item {
    margin-bottom: 16px;
  }

  .input-label {
    display: block;
    margin-bottom: 5px;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    letter-spacing: 0.3px;

    i {
      margin-right: 5px;
      font-size: 10px;
      color: #3b82f6;
    }
  }

  .form-input {
    :deep(.el-input__wrapper) {
      height: 36px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      box-shadow: none;
      transition: all 0.3s;

      &:hover {
        background: white;
        border-color: #cbd5e0;
      }

      &.is-focus {
        background: white;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgb(59 130 246 / 10%);

        .el-input__prefix-inner {
          color: #3b82f6;
        }
      }

      .el-input__inner {
        font-size: 12px;
        font-weight: 400;
        color: #1e293b;

        &::placeholder {
          color: #94a3b8;
        }
      }

      .el-input__prefix-inner {
        margin-right: 6px;
        font-size: 12px;
        color: #94a3b8;
        transition: color 0.3s;
      }
    }

    &.is-error :deep(.el-input__wrapper) {
      border-color: #ef4444;

      &.is-focus {
        box-shadow: 0 0 0 2px rgb(239 68 68 / 10%);
      }
    }
  }

  .form-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 16px 0 20px;
  }

  .checkbox {
    :deep(.el-checkbox__inner) {
      width: 14px;
      height: 14px;
      border: 2px solid #cbd5e0;
      border-radius: 3px;

      &::after {
        border-color: white;
      }
    }

    &.is-checked :deep(.el-checkbox__inner) {
      background-color: #3b82f6;
      border-color: #3b82f6;
    }

    :deep(.el-checkbox__label) {
      font-size: 12px;
      font-weight: 500;
      color: #64748b;
    }
  }

  .link {
    font-size: 12px;
    font-weight: 600;
    color: #3b82f6;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.2s;

    &:hover {
      color: #2563eb;
    }
  }

  .submit-btn {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 38px;
    font-size: 13px;
    font-weight: 600;
    color: white;
    letter-spacing: 0.3px;
    cursor: pointer;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgb(59 130 246 / 25%);
    transition: all 0.3s;

    &:hover {
      box-shadow: 0 3px 12px rgb(59 130 246 / 35%);
      transform: translateY(-1px);
    }

    &:active {
      transform: translateY(0);
    }

    i {
      font-size: 12px;
    }
  }
}

/* 面板底部 */
.panel-footer {
  padding-top: 16px;
  margin-top: 24px;
  border-top: 1px solid #f1f5f9;

  .footer-divider {
    position: relative;
    margin-bottom: 12px;
    text-align: center;

    &::before {
      position: absolute;
      top: 50%;
      right: 0;
      left: 0;
      height: 1px;
      content: "";
      background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
    }

    span {
      position: relative;
      padding: 0 10px;
      font-size: 10px;
      color: #94a3b8;
      background: white;
    }
  }

  .security-tips {
    display: flex;
    gap: 12px;
    justify-content: space-around;
  }

  .tip-item {
    display: flex;
    gap: 5px;
    align-items: center;
    font-size: 10px;
    font-weight: 500;
    color: #64748b;

    i {
      font-size: 11px;
      color: #10b981;
    }
  }
}

/* 暗黑模式 */
.dark {
  .login-layout {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  }

  .bg-decoration .shape {
    opacity: 0.2;
  }

  .login-wrapper {
    background: #1e293b;
    box-shadow: 0 25px 80px rgb(0 0 0 / 50%);
  }

  .decorative-side {
    color: #e0e0e0;

    .side-content {
      .brand-header {
        .brand-title {
          color: #f1f5f9;
        }

        .brand-subtitle {
          color: #94a3b8;
        }
      }

      .stats-section {
        background: rgb(30 41 59 / 60%);
        border-color: rgb(59 130 246 / 20%);

        .stat-item {
          .stat-icon {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
          }

          .stat-number {
            color: #60a5fa;
          }

          .stat-label {
            color: #94a3b8;
          }
        }
      }

      .feature-showcase {
        .feature-card {
          background: rgb(30 41 59 / 60%);
          border-color: rgb(59 130 246 / 20%);

          .card-content {
            h3 {
              color: #f1f5f9;
            }

            p {
              color: #94a3b8;
            }
          }
        }
      }

      .tech-stack {
        .tech-title {
          color: #94a3b8;
        }

        .tech-list {
          .tech-item {
            background: rgb(30 41 59 / 50%);
            border-color: rgb(59 130 246 / 15%);

            i {
              color: #60a5fa;
            }

            span {
              color: #cbd5e0;
            }
          }
        }
      }
    }
  }

  .login-side {
    background: #1e293b;
  }

  .panel-header {
    .header-badge {
      color: #60a5fa;
      background: rgb(59 130 246 / 15%);
    }

    .login-title {
      color: #f1f5f9;
    }

    .login-subtitle {
      color: #94a3b8;
    }
  }

  .input-label {
    color: #cbd5e0;
  }

  .form-input :deep(.el-input__wrapper) {
    background: #334155;
    border-color: #475569;

    .el-input__inner {
      color: #f1f5f9;

      &::placeholder {
        color: #64748b;
      }
    }

    &:hover {
      background: #3b4758;
      border-color: #5b6b7d;
    }

    &.is-focus {
      background: #3b4758;
      border-color: #60a5fa;
      box-shadow: 0 0 0 4px rgb(96 165 250 / 15%);

      .el-input__prefix-inner {
        color: #60a5fa;
      }
    }
  }

  .checkbox :deep(.el-checkbox__label) {
    color: #cbd5e0;
  }

  .panel-footer {
    border-top-color: #334155;

    .footer-divider span {
      color: #64748b;
      background: #1e293b;
    }

    .tip-item {
      color: #94a3b8;
    }
  }
}

/* 主布局 */
</style>
