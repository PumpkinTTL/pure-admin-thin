# 🔮 知识棱镜 - 智慧知识管理平台

<div align="center">

**📚 不只是博客系统，更是你的知识宇宙**

*AI智能助手 · 资源整合管理 · 知识图谱构建 · 个人品牌打造*

[![Vue](https://img.shields.io/badge/Vue-3.4+-4FC08D.svg?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.0+-3178C6.svg?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.0-FF6B6B.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.thinkphp.cn/)

---

***"让知识像光一样，通过棱镜折射出无限可能"***

📝 **智能写作** | 🔍 **资源整合** | 🧠 **知识图谱** | 💎 **会员体系** | 🤖 **AI助手**

</div>

## 🌟 为什么选择知识棱镜？

> 💡 **这不是普通的博客系统，这是你的知识管理大脑**

### 📚 **知识管理核心**
```
📝 智能博客编辑器     🔍 互联网资源整合
📊 知识分类管理       🏷️ 智能标签系统  
🔗 资源链接收藏       📈 知识增长统计
```

### 🎯 **创作体验革命**
```
🤖 AI智能写作助手     📱 完美响应式设计
🌙 优雅暗黑模式       ⚡ 毫秒级编辑响应
🎨 Markdown所见即得   📊 数据可视化图表
```

### 💎 **会员价值体系**
```
👑 VIP会员分级       💳 多渠道支付集成
🎁 专属内容权限       📈 创作收益统计
🔔 实时消息通知       💸 知识变现支持
```

### 🛡️ **企业级安全**
```
🔒 RBAC权限控制       🛡️ AES-256加密
🔑 内容版权保护       🚫 防爬虫机制
📋 操作日志审计       🔄 数据备份恢复
```

## 🏗️ 技术架构

<div align="center">

**🎨 现代化知识管理架构，专为内容创作者设计**

```
┌─────────────────────────────────────────────┐
│  📝  内容编辑层 (Vue 3 + Markdown编辑器)     │
├─────────────────────────────────────────────┤
│  🌐  API服务层 (ThinkPHP 8.0 + RESTful)    │
├─────────────────────────────────────────────┤
│  🧠  业务逻辑层 (博客+资源+AI+会员+支付)       │  
├─────────────────────────────────────────────┤
│  💾  数据存储层 (MySQL 8.0 + Redis + 文件)  │
└─────────────────────────────────────────────┘
```

</div>

### 🎨 **前端技术栈** - *追求极致的用户体验*
```typescript
// 🔥 核心框架
Vue 3.4+              // Composition API + 响应式系统
TypeScript 5.0+       // 类型安全 + 开发体验
Vite 5.x              // 极速构建 + HMR热重载

// 🎨 UI & 样式
Element Plus 2.4+     // 企业级组件库
SCSS + TailwindCSS    // 现代化样式解决方案
Animate.css           // CSS3动画库
Vue Motion            // Vue动画组件

// 📊 数据 & 状态
Pinia                 // 轻量级状态管理
Vue Router 4.x        // 前端路由系统
Axios                 // HTTP请求库
ECharts               // 专业数据可视化

// 🎯 增强功能
FontAwesome           // 图标字体库
IconifyIcons          // 海量图标集合
Socket.IO Client      // 实时双向通讯
```

### ⚙️ **后端技术栈** - *稳定可靠的服务架构*
```php
// 🚀 核心框架
ThinkPHP 8.0          // 现代PHP企业框架
PHP 8.0+              // 最新语言特性支持

// 💾 数据存储
MySQL 8.0             // 高性能关系型数据库
Redis 6.0+            // 内存数据库缓存

// 🛡️ 安全认证
JWT Token             // 无状态认证
AES-256 + RSA         // 数据加密算法
自定义Token管理        // 智能续签机制

// 🔧 扩展功能
PHPMailer             // 邮件发送服务
OpenAI API            // 人工智能集成
Socket.IO Server      // WebSocket实时服务
Composer              // PHP依赖管理
```

## ⚡ 快速部署

> **🎯 3分钟搭建你的知识管理平台**

### 📋 环境准备

<table>
<tr>
<td width="50%">

#### 🖥️ **前端环境**
```bash
Node.js >= 18.0.0    # 🟢 推荐LTS版本
pnpm >= 8.0.0        # 🚀 高性能包管理
```

</td>
<td width="50%">

#### ⚙️ **后端环境**  
```bash
PHP >= 8.0.0         # 💎 现代PHP支持
MySQL >= 8.0.0       # 🗄️ 企业级数据库
Redis >= 6.0.0       # ⚡ 内存缓存加速
Composer              # 📦 PHP依赖管理
```

</td>
</tr>
</table>

### 🚀 一键启动

#### **第一步：获取源码**
```bash
git clone https://github.com/your-username/pure-admin-thin.git
cd pure-admin-thin
```

#### **第二步：前端启动**
```bash
# 🔥 安装依赖（推荐pnpm，速度更快）
pnpm install

# ⚡ 启动开发服务器（支持热重载）
pnpm dev

# 🏗️ 构建生产版本
pnpm build
```

#### **第三步：后端配置**
```bash
# 📂 进入后端目录
cd src/admin/m-service-server

# 📦 安装PHP依赖
composer install

# ⚙️ 配置环境变量
cp .env.example .env
# 编辑.env文件，配置数据库和Redis连接

# 💾 初始化数据库
# 1️⃣ 导入建表语句: sql_.txt
# 2️⃣ 导入支付方式: payment_methods.sql  
# 3️⃣ 更新权限结构: update_permissions.sql

# 🚀 启动后端服务
php think run
```

#### **第四步：开始创作**
```bash
🌐 知识棱镜: http://localhost:5173
🔧 后端API: http://localhost:8000  
👤 默认账号: admin / admin123
📚 开启你的知识管理之旅！
```

#### 4. 配置说明

**前端配置** (`vite.config.ts`)
```typescript
server: {
  proxy: {
    '/api': {
      target: 'http://localhost:8000', // 后端服务地址
      changeOrigin: true
    }
  }
}
```

**后端配置** (`.env`)
```env
# 数据库配置
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password

# Redis配置
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## 📁 项目结构

```
pure-admin-thin/
├── src/                          # 前端源码
│   ├── api/                      # API接口定义
│   ├── assets/                   # 静态资源
│   ├── components/               # 公共组件
│   │   ├── dashboard/           # 仪表盘组件
│   │   ├── entertainment/       # 娱乐功能组件
│   │   └── vips/               # VIP会员组件
│   ├── hooks/                   # Vue3 Hooks
│   ├── layout/                  # 布局组件
│   ├── router/                  # 路由配置
│   ├── store/                   # Pinia状态管理
│   ├── style/                   # 全局样式
│   ├── utils/                   # 工具函数
│   │   ├── auth.ts             # 认证工具
│   │   ├── tokenManager.ts     # Token管理器
│   │   └── http/               # HTTP请求封装
│   ├── views/                   # 页面组件
│   │   ├── basic/              # 基础功能页面
│   │   ├── login/              # 登录页面
│   │   ├── payment/            # 支付相关页面
│   │   ├── permission/         # 权限管理页面
│   │   ├── system/             # 系统管理页面
│   │   └── welcome/            # 欢迎页面
│   └── admin/                   # 后端项目
│       └── m-service-server/    # ThinkPHP后端
│           ├── app/api/         # API应用
│           │   ├── controller/  # 控制器
│           │   ├── middleware/  # 中间件
│           │   ├── model/      # 数据模型
│           │   └── services/   # 业务服务
│           ├── config/         # 配置文件
│           ├── extend/utils/   # 扩展工具类
│           └── public/         # 入口文件
├── mock/                        # Mock数据
├── docs/                        # 项目文档
└── public/                      # 公共静态文件
```

## 🔐 认证机制

### 单Token + 智能续签
项目采用单Token认证机制，配合智能续签实现无感刷新：

- **Token有效期**: 2小时
- **自动续签**: Token剩余10分钟时自动续签
- **并发保护**: 防止多个请求同时触发续签
- **重试机制**: 续签失败时自动重试（最多2次）
- **多标签页支持**: 通过Cookie共享实现多标签页同步

### 核心文件
- `src/utils/auth.ts` - Token管理核心工具
- `src/utils/tokenManager.ts` - Token自动续签管理器
- `src/utils/http/index.ts` - HTTP拦截器，集成Token验证

## 👥 权限管理

### RBAC权限模型
- **用户 (Users)** - 系统用户
- **角色 (Roles)** - 用户角色分组
- **权限 (Permissions)** - 具体操作权限
- **菜单 (Menus)** - 页面访问控制

### 权限控制
```typescript
// 页面权限控制
import { hasPerms } from "@/utils/auth";

// 检查按钮权限
if (hasPerms("user:create")) {
  // 显示创建用户按钮
}

// 检查多个权限
if (hasPerms(["user:update", "user:delete"])) {
  // 显示编辑操作
}
```

## 💳 支付系统

### 支持的支付方式
- **传统支付**: 支付宝、微信支付、银联、PayPal
- **加密货币**: Bitcoin、Ethereum、USDT (TRC20/ERC20)
- **数字钱包**: 支持各种数字钱包支付

### 支付功能
- 支付方式管理
- 支付订单跟踪
- 支付状态实时更新
- 多货币支持

## 📊 数据可视化

### 仪表盘功能
- **数据概览卡片** - 关键指标展示
- **销售图表** - 趋势分析
- **排行榜** - 数据排名
- **实时数据** - 动态更新

### 图表类型
- 折线图、柱状图、饼图
- 数据地图、仪表盘
- 自定义图表配置

## 🔄 实时通信

### Socket.IO集成
- 实时消息推送
- 在线用户状态
- 系统通知
- 聊天功能

## 🎨 UI/UX特性

### 响应式设计
- **栅格布局** - 基于Element Plus的24栅格系统
- **媒体查询** - 完美适配各种屏幕尺寸
- **移动端优化** - 触摸友好的交互体验

### 主题支持
- **暗黑模式** - 护眼的深色主题
- **主题切换** - 一键切换明暗主题
- **色彩定制** - 支持主题色自定义

### 动画效果
- **页面过渡** - 流畅的路由切换动画
- **组件动画** - Animate.css + Vue Motion
- **交互反馈** - 丰富的用户操作反馈

## 📋 API接口

### 核心接口

#### 认证接口
```http
POST /api/v1/user/login          # 用户登录
POST /api/v1/auth/refresh        # Token续签
POST /api/v1/user/logout         # 用户登出
```

#### 用户管理
```http
GET  /api/v1/user/selectUserListWithRoles  # 获取用户列表
POST /api/v1/user/add                       # 添加用户
PUT  /api/v1/user/update                    # 更新用户
DELETE /api/v1/user/delete                  # 删除用户
```

#### 权限管理
```http
GET  /api/v1/permissions/tree     # 获取权限树
POST /api/v1/permissions/add      # 添加权限
PUT  /api/v1/permissions/update   # 更新权限
```

## 🧪 开发指南

### 代码规范
- **TypeScript**: 使用setup语法糖
- **组件结构**: 模板 → 脚本 → 样式
- **样式**: SCSS + 栅格布局
- **命名**: 驼峰命名 (前端) + 下划线 (后端API)

### 开发工具
- **ESLint** - 代码质量检查
- **Prettier** - 代码格式化
- **TypeScript** - 类型检查
- **Vite** - 快速热重载

### 调试工具
- **Vue DevTools** - Vue组件调试
- **Network** - API请求监控
- **Console** - 日志输出
- **Redux DevTools** - 状态管理调试

## 📚 文档说明

### 项目文档
- `docs/单Token认证机制说明.md` - 认证机制详细说明
- API接口文档 - 完整的接口说明
- 数据库设计文档 - 表结构和关系说明

### 在线文档
- [Vue 3 官方文档](https://cn.vuejs.org/)
- [Element Plus 组件库](https://element-plus.org/zh-CN/)
- [ThinkPHP 8.0 文档](https://doc.thinkphp.cn/)

## 🤝 贡献指南

### 开发流程
1. Fork 项目仓库
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

### 代码提交规范
```
feat: 新功能
fix: 修复Bug
docs: 文档更新
style: 代码格式调整
refactor: 代码重构
test: 测试相关
chore: 构建/工具相关
```

## 📄 许可证

[MIT License](LICENSE) © 2024-present

## 🙏 致谢

- [Vue.js](https://vuejs.org/) - 渐进式JavaScript框架
- [Element Plus](https://element-plus.org/) - Vue 3 组件库
- [ThinkPHP](https://www.thinkphp.cn/) - 简洁的PHP框架
- [Pure Admin](https://github.com/pure-admin/vue-pure-admin) - 原始模板项目

---

**如果这个项目对你有帮助，请给个 ⭐️ 支持一下！**