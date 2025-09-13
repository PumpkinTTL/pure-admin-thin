<script setup lang="ts">
import Motion from "./utils/motion";
import { useRouter } from "vue-router";
import { message } from "@/utils/message";
import { loginRules } from "./utils/rule";
import { useNav } from "@/layout/hooks/useNav";
import type { FormInstance } from "element-plus";
import { useLayout } from "@/layout/hooks/useLayout";
import { useUserStoreHook } from "@/store/modules/user";
import { initRouter, getTopMenu } from "@/router/utils";

import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { ref, reactive, toRaw, onMounted, onBeforeUnmount } from "vue";
import { useDataThemeChange } from "@/layout/hooks/useDataThemeChange";

import dayIcon from "@/assets/svg/day.svg?component";
import darkIcon from "@/assets/svg/dark.svg?component";
import Lock from "@iconify-icons/ri/lock-fill";
import User from "@iconify-icons/ri/user-3-fill";
import { loginR } from "@/api/user";
import { setToken } from "@/utils/auth";
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
  password: "admin123"
});

const rememberMe = ref(false);

const onLogin = async (formEl: FormInstance | undefined) => {
  // 表单验证
  if (!formEl) return;

  const valid = await formEl.validate().catch(() => false);
  if (!valid) return;

  loading.value = true;

  try {
    console.log("开始登录，请求参数:", ruleForm);
    const res: any = await loginR(ruleForm);

    if (res.code !== 200) {
      message(res.msg || "登录失败", { type: "error" });
      return;
    }


    // 从响应中解构数据
    const { avatar, username, nickname, id, roles } = res.data;
    const { token, expireTime } = res; // token格式

    // 处理角色和权限
    const idens = roles.map(item => item.iden);
    const permissions = roles.map(item =>
      item.permissions.map(pitem => pitem.name)
    );
    const permissionsArr = [].concat.apply([], permissions);

    console.log("处理后的角色:", idens);
    console.log("处理后的权限:", permissionsArr);

    // 构建用户信息对象
    const userInfo = {
      id,
      avatar,
      username,
      nickname,
      token, // 访问令牌
      roles: idens,
      permissions: permissionsArr,
      expires: new Date(expireTime * 1000) // 后端返回的时间戳（秒），转换为毫秒
    };

    console.log("构建的用户信息:", userInfo);

    // 关键：调用setToken保存用户信息到localStorage和Cookie

    setToken(userInfo);

    console.log("Token已保存，开始初始化路由...");

    // 存储用户信息后初始化路由并跳转
    return initRouter().then(() => {
      console.log("路由初始化完成，准备跳转...");
      const topMenu = getTopMenu(true);
      console.log("顶级菜单:", topMenu);

      router.push(topMenu.path).then(() => {
        console.log("跳转成功!");
      }).catch(err => {
        message("跳转失败", { type: "error" });
      });
    }).catch(err => {
      message("路由初始化失败", { type: "error" });
    });

  } catch (error) {
    message("登录失败，请稍后重试", { type: "error" });
  } finally {
    loading.value = false;
  }

};

/** 使用公共函数，避免`removeEventListener`失效 */
function onkeypress({ code }: KeyboardEvent) {
  if (["Enter", "NumpadEnter"].includes(code)) {
    onLogin(ruleFormRef.value);
  }
}

onMounted(() => {
  window.document.addEventListener("keypress", onkeypress);
});

onBeforeUnmount(() => {
  window.document.removeEventListener("keypress", onkeypress);
});
</script>

<template>
  <div class="select-none login-page">
    <!-- 几何装饰元素 -->
    <div class="geometric-square animate__animated animate__fadeIn animate__delay-1s"></div>
    <div class="geometric-hexagon animate__animated animate__fadeIn animate__delay-2s"></div>

    <!-- 主题切换器 -->
    <div class="theme-switcher">
      <el-switch v-model="dataTheme" inline-prompt :active-icon="dayIcon" :inactive-icon="darkIcon"
        @change="dataThemeChange" class="theme-switch" />
    </div>

    <!-- 主容器 -->
    <div class="main-container">
      <Motion>
        <div class="login-container">
          <!-- 左侧问候区域 -->
          <div class="welcome-section">
            <div class="welcome-content">
              <div class="brand-area">
                <div class="brand-logo">
                  <i class="fas fa-rocket"></i>
                </div>
                <h1 class="welcome-title">{{ title }}</h1>
                <p class="welcome-subtitle">现代化企业管理系统</p>
              </div>

              <div class="greeting-text">
                <h2 class="greeting-title">欢迎回来！</h2>
                <p class="greeting-desc">请登录您的账户继续使用我们的服务</p>
              </div>

              <div class="features-highlight">
                <div class="feature-item">
                  <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                  </div>
                  <span class="feature-text">安全可靠</span>
                </div>

                <div class="feature-item">
                  <div class="feature-icon">
                    <i class="fas fa-rocket"></i>
                  </div>
                  <span class="feature-text">高效便捷</span>
                </div>

                <div class="feature-item">
                  <div class="feature-icon">
                    <i class="fas fa-users"></i>
                  </div>
                  <span class="feature-text">团队协作</span>
                </div>
              </div>

              <div class="decorative-elements">
                <div class="deco-circle deco-1"></div>
                <div class="deco-circle deco-2"></div>
                <div class="deco-square deco-3"></div>
              </div>
            </div>
          </div>

          <!-- 右侧登录表单区域 -->
          <div class="form-section">
            <div class="form-content">
              <div class="form-header">
                <h3 class="form-title">
                  <div class="title-icon">
                    <i class="fas fa-user-circle"></i>
                  </div>
                  账户登录
                </h3>
                <p class="form-subtitle">输入您的登录凭证</p>
              </div>

              <el-form ref="ruleFormRef" :model="ruleForm" :rules="loginRules" class="login-form">
                <Motion :delay="200">
                  <el-form-item prop="account" class="form-group">
                    <label class="form-label">账户</label>
                    <el-input v-model="ruleForm.account" clearable placeholder="请输入邮箱或用户名"
                      :prefix-icon="useRenderIcon(User)" class="form-input" size="large" />
                  </el-form-item>
                </Motion>

                <Motion :delay="300">
                  <el-form-item prop="password" class="form-group">
                    <label class="form-label">密码</label>
                    <el-input v-model="ruleForm.password" show-password placeholder="请输入您的密码"
                      :prefix-icon="useRenderIcon(Lock)" class="form-input" size="large" />
                  </el-form-item>
                </Motion>

                <Motion :delay="400">
                  <div class="form-options">
                    <el-checkbox v-model="rememberMe" class="remember-checkbox">
                      记住我
                    </el-checkbox>
                    <el-link type="primary" class="forgot-link">
                      忘记密码？
                    </el-link>
                  </div>
                </Motion>

                <Motion :delay="500">
                  <el-button class="submit-btn" type="primary" :loading="loading" @click="onLogin(ruleFormRef)"
                    size="large" block>
                    {{ loading ? '登录中...' : '立即登录' }}
                  </el-button>
                </Motion>

                <Motion :delay="600">
                  <div class="register-area">
                    <span class="register-text">还没有账户？</span>
                    <el-link type="primary" class="register-link">立即注册</el-link>
                  </div>
                </Motion>
              </el-form>
            </div>
          </div>
        </div>
      </Motion>
    </div>
  </div>
</template>

<style scoped>
@import "./styles/login.scss";
</style>

<style lang="scss" scoped>
:deep(.el-input-group__append, .el-input-group__prepend) {
  padding: 0;
}
</style>
