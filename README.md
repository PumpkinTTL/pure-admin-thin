# 🚀 企业级管理系统 - Pure Admin

基于 Vue 3 + TypeScript + ThinkPHP 8.0 的现代化企业级管理系统

[![Vue](https://img.shields.io/badge/Vue-3.x-brightgreen.svg)](https://vuejs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-blue.svg)](https://www.typescriptlang.org/)
[![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.0-red.svg)](https://www.thinkphp.cn/)
[![Element Plus](https://img.shields.io/badge/Element%20Plus-2.x-409EFF.svg)](https://element-plus.org/)
[![License](https://img.shields.io/github/license/pure-admin/vue-pure-admin.svg)](LICENSE)

## ✨ 项目特色

### 🎯 核心功能
- 🔐 **完善的认证体系** - 单Token + 智能续签，无感刷新用户体验
- 👥 **用户权限管理** - 基于RBAC的角色权限控制，支持细粒度权限分配
- 📊 **数据可视化** - 集成ECharts图表，支持多种数据展示
- 📱 **响应式设计** - 完美适配PC端和移动端，支持暗黑模式
- 🔄 **实时通信** - 集成Socket.IO实现实时消息推送
- 💳 **支付系统** - 支持多种支付方式（支付宝、微信、加密货币等）

### 🛠 技术架构

#### 前端技术栈
- **框架**: Vue 3.x + TypeScript
- **构建工具**: Vite 5.x
- **UI组件**: Element Plus 2.x
- **状态管理**: Pinia
- **路由管理**: Vue Router 4.x
- **HTTP客户端**: Axios
- **样式处理**: SCSS + TailwindCSS
- **动画库**: Animate.css + Vue Motion
- **图标**: FontAwesome + IconifyIcons
- **图表**: ECharts

#### 后端技术栈
- **框架**: ThinkPHP 8.0 (PHP 8.0+)
- **数据库**: MySQL 8.0
- **缓存**: Redis
- **认证**: JWT + 自定义Token管理
- **加密**: AES-256 + RSA
- **邮件**: PHPMailer
- **AI集成**: OpenAI API

## 🚀 快速开始

### 环境要求

#### 前端环境
- Node.js >= 18.0.0
- pnpm >= 8.0.0

#### 后端环境  
- PHP >= 8.0.0
- MySQL >= 8.0.0
- Redis >= 6.0.0
- Composer

### 安装步骤

#### 1. 克隆项目
```bash
git clone https://github.com/your-username/pure-admin-thin.git
cd pure-admin-thin
```

#### 2. 前端安装
```bash
# 安装依赖
pnpm install

# 启动开发服务器
pnpm dev

# 构建生产版本
pnpm build
```

#### 3. 后端安装
```bash
cd src/admin/m-service-server

# 安装PHP依赖
composer install

# 配置数据库连接
cp .env.example .env
# 编辑 .env 文件，配置数据库和Redis连接信息

# 导入数据库
# 执行 sql_.txt 中的建表语句
# 导入 payment_methods.sql 支付方式数据
# 执行 update_permissions.sql 权限结构更新

# 启动后端服务
php think run
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