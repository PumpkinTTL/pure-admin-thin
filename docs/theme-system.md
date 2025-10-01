# 主题配置系统使用文档

## 概述

主题配置系统是一个完整的主题管理解决方案，支持动态主题切换、主题配置管理、实时预览等功能。系统采用前后端分离架构，提供了完整的API接口和前端组件。

## 系统架构

### 后端架构
- **数据库表**: `bl_theme_configs` - 存储主题配置信息
- **模型层**: `ThemeConfig.php` - 数据模型和业务逻辑
- **服务层**: `ThemeService.php` - 业务服务和缓存管理
- **控制器**: `Theme.php` - API接口控制器
- **路由**: 主题相关的RESTful API路由

### 前端架构
- **API接口**: `src/api/theme.ts` - 主题API调用
- **状态管理**: `src/store/modules/theme.ts` - Pinia主题状态管理
- **组件系统**: 
  - `ThemeProvider` - 主题提供者组件
  - `ThemeSwitcher` - 完整主题切换器
  - `ThemeToggle` - 简化主题切换器
- **管理页面**: `src/views/system/theme/` - 主题管理界面

## 数据库设计

### bl_theme_configs 表结构

```sql
CREATE TABLE `bl_theme_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `theme_key` varchar(50) NOT NULL COMMENT '主题键名（唯一标识）',
  `theme_name` varchar(100) NOT NULL COMMENT '主题名称',
  `description` text COMMENT '主题描述',
  `preview_image` varchar(255) DEFAULT NULL COMMENT '预览图片URL',
  `config_data` json NOT NULL COMMENT '主题配置数据（JSON格式）',
  `is_system` tinyint(1) DEFAULT '0' COMMENT '是否为系统主题（1=是，0=否）',
  `is_current` tinyint(1) DEFAULT '0' COMMENT '是否为当前主题（1=是，0=否）',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用（1=启用，0=禁用）',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序权重',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间（软删除）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_theme_key` (`theme_key`),
  KEY `idx_is_current` (`is_current`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主题配置表';
```

### 主题配置数据结构

```json
{
  "colors": {
    "primary": "#409EFF",
    "secondary": "#909399",
    "success": "#67C23A",
    "warning": "#E6A23C",
    "danger": "#F56C6C",
    "info": "#909399"
  },
  "layout": {
    "sidebar_width": 240,
    "header_height": 60,
    "border_radius": 4,
    "content_padding": 20
  },
  "typography": {
    "font_family": "PingFang SC, Microsoft YaHei",
    "font_size_base": 14,
    "line_height": 1.5
  },
  "effects": {
    "shadow_level": 2,
    "animation_duration": "0.3s",
    "transition_timing": "ease-in-out"
  }
}
```

## API接口

### 客户端API

#### 获取当前主题
```
GET /api/v1/theme/current
```

#### 获取主题列表
```
GET /api/v1/theme/list?page=1&page_size=20&keyword=&is_active=1
```

#### 获取主题详情
```
GET /api/v1/theme/detail/{theme_key}
```

### 管理端API

#### 创建主题
```
POST /api/v1/theme/create
Content-Type: application/json

{
  "theme_key": "custom_theme",
  "theme_name": "自定义主题",
  "description": "这是一个自定义主题",
  "config_data": { ... },
  "is_active": 1,
  "sort_order": 10
}
```

#### 更新主题
```
PUT /api/v1/theme/update/{id}
Content-Type: application/json

{
  "theme_name": "更新的主题名称",
  "config_data": { ... }
}
```

#### 删除主题
```
DELETE /api/v1/theme/delete/{id}
```

#### 设置当前主题
```
POST /api/v1/theme/set-current
Content-Type: application/json

{
  "theme_id": 1
}
```

#### 切换主题状态
```
POST /api/v1/theme/toggle-status/{id}
```

## 前端使用

### 1. 在应用中集成主题系统

主题系统已经集成到 `App.vue` 中，通过 `ThemeProvider` 组件自动初始化和管理主题。

### 2. 使用主题状态管理

```typescript
import { useThemeStore } from '@/store/modules/theme'

const themeStore = useThemeStore()

// 获取当前主题
const currentTheme = themeStore.currentTheme

// 切换主题
await themeStore.switchTheme(theme)

// 重置主题
themeStore.resetTheme()

// 获取主题颜色
const primaryColor = themeStore.getThemeColor('primary')
```

### 3. 使用主题切换组件

#### 完整主题切换器
```vue
<template>
  <ThemeSwitcher
    size="default"
    button-type="primary"
    :circle="true"
    trigger-icon="fa fa-palette"
  />
</template>

<script setup>
import ThemeSwitcher from '@/components/ThemeSwitcher/index.vue'
</script>
```

#### 简化主题切换器
```vue
<template>
  <ThemeToggle 
    size="small"
    button-type="text"
    :circle="false"
  />
</template>

<script setup>
import ThemeToggle from '@/components/ThemeSwitcher/ThemeToggle.vue'
</script>
```

### 4. 使用主题CSS变量

在组件样式中使用主题变量：

```scss
.my-component {
  background-color: var(--theme-color-primary);
  border-radius: var(--theme-layout-border-radius);
  font-family: var(--theme-typography-font-family);
  font-size: var(--theme-typography-font-size-base);
  
  .header {
    height: var(--theme-layout-header-height);
    padding: var(--theme-layout-content-padding);
  }
  
  .sidebar {
    width: var(--theme-layout-sidebar-width);
  }
}
```

## 管理界面

### 访问主题管理
- 路由: `/system/theme`
- 菜单: 系统管理 > 主题配置

### 功能特性
- ✅ 主题列表展示和搜索
- ✅ 主题创建和编辑
- ✅ 实时预览功能
- ✅ 主题状态管理
- ✅ 主题排序和分类
- ✅ 响应式设计

## 开发指南

### 1. 创建新主题

```typescript
import { createTheme } from '@/api/theme'

const newTheme = {
  theme_key: 'my_theme',
  theme_name: '我的主题',
  description: '这是我创建的主题',
  config_data: {
    colors: {
      primary: '#FF6B6B',
      success: '#4ECDC4',
      // ... 其他颜色配置
    },
    // ... 其他配置
  }
}

await createTheme(newTheme)
```

### 2. 扩展主题配置

如需添加新的主题配置项，需要：

1. 更新 `getDefaultThemeConfig()` 函数
2. 修改 `ThemeEditor.vue` 组件
3. 更新 CSS 变量定义
4. 更新 TypeScript 类型定义

### 3. 自定义主题应用逻辑

```typescript
// 在 ThemeProvider 中扩展主题应用逻辑
const applyThemeVariables = (theme: ThemeConfig) => {
  const root = document.documentElement
  
  // 应用自定义配置
  if (theme.config_data.custom) {
    Object.entries(theme.config_data.custom).forEach(([key, value]) => {
      root.style.setProperty(`--theme-custom-${key}`, value)
    })
  }
}
```

## 注意事项

1. **数据库执行**: SQL语句需要手动执行，系统不会自动创建表结构
2. **缓存管理**: 主题配置使用了缓存机制，修改后会自动清除缓存
3. **权限控制**: 管理端API需要相应的权限验证
4. **响应式设计**: 主题系统支持响应式，会根据屏幕尺寸调整变量值
5. **浏览器兼容**: 使用了CSS自定义属性，需要现代浏览器支持

## 故障排除

### 常见问题

1. **主题不生效**: 检查 `ThemeProvider` 是否正确包裹应用
2. **API调用失败**: 确认后端路由和权限配置
3. **样式不更新**: 清除浏览器缓存或检查CSS变量使用
4. **数据库错误**: 确认表结构是否正确创建

### 调试方法

```javascript
// 在浏览器控制台查看当前主题状态
console.log('当前主题:', useThemeStore().currentTheme)

// 查看CSS变量值
console.log('主色调:', getComputedStyle(document.documentElement).getPropertyValue('--theme-color-primary'))
```

## 更新日志

- **v1.0.0**: 初始版本，包含完整的主题管理功能
- 支持动态主题切换
- 支持主题配置管理
- 支持实时预览
- 支持响应式设计
