# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 📋 Project Overview

**知识棱镜 - 智慧知识管理平台** is a comprehensive knowledge management system built with Vue 3, TypeScript, and ThinkPHP. It's a modern, full-stack application featuring AI-powered content generation, resource integration, knowledge graph construction, and community interaction.

### 🔑 Key Technologies

- **Frontend**: Vue 3.4+ (Composition API), TypeScript 5.0+, Vite 5.x
- **UI Framework**: Element Plus 3.4+, TailwindCSS, SCSS
- **State Management**: Pinia, Vue Router 4.x
- **Backend**: ThinkPHP 8.0
- **Database**: MySQL
- **Real-time**: Socket.IO
- **AI Integration**: OpenAI API
- **Charts**: ECharts
- **Package Manager**: pnpm (>=9)

## 🚀 Common Development Commands

### Development Server

```bash
# Start development server (port configured via VITE_PORT env, default 3000)
pnpm dev
# or
npm run dev

# Alternative command
pnpm serve
```

### Building & Deployment

```bash
# Build for production
pnpm build

# Build for staging environment
pnpm build:staging

# Build with bundle analysis report
pnpm report

# Preview production build locally
pnpm preview

# Build then preview
pnpm preview:build
```

### Code Quality & Linting

```bash
# Type checking (TypeScript + Vue)
pnpm typecheck

# Code formatting (Prettier)
pnpm lint:prettier

# Style linting (Stylelint)
pnpm lint:stylelint

# Run all linting
pnpm lint

# SVG optimization
pnpm svgo
```

### Cache & Dependencies

```bash
# Clean all caches and reinstall dependencies
pnpm clean:cache

# Only allow pnpm (enforced in preinstall script)
```

### Real-time Features

```bash
# Start Socket.IO server (for real-time features)
pnpm socket:server
```

## 📁 Project Architecture

### Frontend Structure (`src/`)

```
src/
├── admin/                  # Backend API files (ThinkPHP)
├── api/                    # Frontend API utilities
├── assets/                 # Static assets (images, fonts, etc.)
├── components/             # Reusable Vue components
├── config/                 # App configuration files
├── constants/              # App constants
├── directives/             # Custom Vue directives
├── hooks/                  # Vue composition hooks
├── layout/                 # App layout components
│   ├── components/         # Layout-specific components
│   ├── hooks/              # Layout-related hooks
│   ├── theme/              # Theme configuration
│   ├── frame.vue           # Main layout frame
│   └── index.vue           # Root layout component
├── plugins/                # Vue plugins (FontAwesome, etc.)
├── router/                 # Vue Router configuration
│   ├── modules/            # Route modules
│   ├── index.ts            # Main router config
│   └── utils.ts            # Router utilities
├── store/                  # Pinia stores
│   ├── modules/            # Store modules
│   ├── index.ts            # Store setup
│   ├── types.ts            # Store type definitions
│   └── utils.ts            # Store utilities
├── style/                  # Global styles
│   ├── index.scss          # Main stylesheet (imports others)
│   ├── dark.scss           # Dark mode styles
│   ├── element-plus.scss   # Element Plus customizations
│   ├── sidebar.scss        # Sidebar styles
│   └── transition.scss     # Transition animations
├── views/                  # Page components
├── App.vue                 # Root Vue component
└── main.ts                 # Application entry point
```

### Backend Structure (`src/admin/`)

```
src/admin/m-service-server/
├── app/
│   ├── api/                # API controllers
│   │   ├── controller/     # Request controllers
│   │   ├── middleware/     # Custom middleware
│   │   └── ...            # Other API files
├── config/                 # Backend configuration
├── database/               # Database migrations/seeds
├── route/                  # Route definitions
├── vendor/                 # Composer dependencies
└── view/                   # Backend views
```

## 🔧 Important Configuration Files

### Build & Development

- **`vite.config.ts`** - Vite configuration with plugins, proxy settings, and build options
- **`.env`** - Environment variables (local development)
- **`.env.development`** - Development environment variables
- **`.env.production`** - Production environment variables
- **`.env.staging`** - Staging environment variables

### Code Quality

- **`.lintstagedrc`** - lint-staged configuration for pre-commit hooks
- **`package.json`** - Dependencies, scripts, and project metadata

### Style & Formatting

- **`stylelint.config*`** - Stylelint configuration
- **`.prettierrc`** - Prettier configuration (if exists)

## 🏗️ Key Architectural Patterns

### 1. **Component Architecture**

- **Composition API**: All Vue components use `<script setup>` with Composition API
- **TypeScript**: Strict type checking throughout the application
- **SCSS Modules**: Modular SCSS styling with global imports in `src/style/index.scss`

### 2. **State Management**

- **Pinia Stores**: Centralized state management
- **Modules**: Stores are organized in `src/store/modules/`
- **Types**: Type definitions in `src/store/types.ts`

### 3. **Routing**

- **Vue Router 4**: Configured in `src/router/index.ts`
- **Route Modules**: Organized in `src/router/modules/`
- **Dynamic Routes**: Lazy-loaded route components

### 4. **Theme System**

- **CSS Variables**: Theme switching via CSS custom properties
- **Dark Mode**: Built-in dark/light theme toggle
- **Element Plus Integration**: Custom theme overrides in `src/style/element-plus.scss`

### 5. **API Layer**

- **Frontend**: Axios-based API client in `src/api/`
- **Backend**: ThinkPHP REST API
- **Proxy**: Vite proxy configured for `/api` routes to backend

## 🎨 Styling & UI

### Global Styles

- **Main Entry**: `src/style/index.scss` imports all style modules
- **Dark Mode**: `src/style/dark.scss` handles theme switching
- **Animations**: `animate.css` for transitions, custom animations in SCSS

### UI Framework

- **Element Plus**: Primary component library
- **TailwindCSS**: Utility-first CSS framework
- **Custom Components**: Located in `src/components/`

### Font Icons

- **FontAwesome**: Configured via `src/plugins/fontawesome.ts`
- **Usage**: `<i class="fas fa-icon-name" />`

## 🔐 Authentication & Permissions

The application implements a sophisticated permission system with:

- **Frontend Guards**: Route guards in Vue Router
- **Backend Middleware**: ThinkPHP middleware for API protection
- **Role-based Access**: Multi-level permission checking
- **JWT Authentication**: Token-based authentication

See documentation files for detailed implementation:

- `PERMISSION_IMPLEMENTATION_SUMMARY.md`
- `BACKEND_PERMISSION_FIX.md`
- `FRONTEND_PERMISSION_CHANGES.md`

## 🧪 Development Guidelines

### Adding New Features

1. Create route in appropriate module under `src/router/modules/`
2. Add view component in `src/views/`
3. Update Pinia store if state management needed
4. Create API endpoints in backend (`src/admin/`)
5. Update permissions if required

### Working with Styles

1. Use SCSS for component-specific styles
2. Add global styles to `src/style/index.scss`
3. Follow the existing naming conventions
4. Use TailwindCSS utilities for rapid development

### API Development

1. Frontend API utilities in `src/api/`
2. Backend controllers in `src/admin/m-service-server/app/api/controller/`
3. Use proper TypeScript types for all API responses

### State Management

1. Create Pinia stores in `src/store/modules/`
2. Define types in `src/store/types.ts`
3. Use Composition API in store setup

## 🐛 Common Issues & Solutions

### Sass Deprecation Warnings

- **Issue**: `@import` rules deprecated in Dart Sass 3.0.0
- **Solution**: Keep using `@import` for now, theme system depends on it
- **Note**: Do NOT convert to `@use` without testing theme switching

### Development Server Issues

- **Port conflicts**: Check `VITE_PORT` in `.env` files
- **Proxy issues**: Verify backend URL in `vite.config.ts` proxy config
- **Memory issues**: Use `NODE_OPTIONS=--max-old-space-size=4096` for dev

### Build Issues

- **Type errors**: Run `pnpm typecheck` before building
- **Style linting**: Fix Stylelint issues with `pnpm lint:stylelint`
- **Bundle size**: Use `pnpm report` to analyze bundle

## 📚 Additional Resources

### Documentation Files

- **Project Overview**: `README.md`
- **Permission System**: `PERMISSION_*.md` files
- **Backend Docs**: Various implementation guides in root directory
- **Email Module**: `邮件记录模块-文件规划.md`

### External Resources

- [Vue 3 Documentation](https://vuejs.org/)
- [Element Plus](https://element-plus.org/)
- [Vite](https://vitejs.dev/)
- [Pinia](https://pinia.vuejs.org/)
- [Pure Admin](https://github.com/pure-admin/pure-admin-thin)

## 🔍 Key Files to Understand

1. **`src/main.ts`** - Application entry point, plugin initialization
2. **`src/App.vue`** - Root component, layout structure
3. **`src/layout/index.vue`** - Main application layout
4. **`src/router/index.ts`** - Router configuration
5. **`src/store/index.ts`** - Pinia store setup
6. **`src/style/index.scss`** - Global styles entry point
7. **`vite.config.ts`** - Build configuration
8. **`src/plugins/fontawesome.ts`** - Icon library setup

## ⚡ Performance Tips

1. **Lazy Loading**: Routes are lazy-loaded by default
2. **Bundle Analysis**: Use `pnpm report` to check bundle size
3. **SVG Optimization**: Run `pnpm svgo` to optimize SVGs
4. **Cache Management**: Clear caches with `pnpm clean:cache` when needed
5. **Dev Server**: Use pre-warming in `vite.config.ts` for faster startup

---

**Note**: This project uses pnpm as the package manager. Always use `pnpm` commands, not `npm` or `yarn`.
