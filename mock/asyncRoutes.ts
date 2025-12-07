// 模拟后端动态生成路由
import { defineFakeRoute } from "vite-plugin-fake-server/client";

/**
 * roles：页面级别权限，这里模拟二种 "superAdmin"、"common"
 * superAdmin：管理员角色
 * common：普通角色
 */

// 最简代码，也就是这些字段必须有
const testRouter = {
  path: "/basic",
  rank: 1,

  meta: {
    title: "基础管理",
    icon: "ep:menu"
  },
  children: [
    {
      path: "/basic/user",
      name: "user",
      meta: {
        title: "用户管理",
        icon: "ep:user"
      }
    },
    {
      path: "/basic/article",
      name: "article",
      meta: {
        icon: "ep:document",
        title: "文章管理"
      }
    },
    {
      path: "/basic/category",
      name: "category",
      meta: {
        icon: "ep:folder",
        title: "类别管理"
      }
    },
    {
      path: "/basic/resource",
      name: "resource",
      meta: {
        icon: "ep:files",
        title: "资源管理"
      }
    },
    {
      path: "/basic/order",
      name: "order",
      meta: {
        icon: "ep:shopping-cart",
        title: "订单管理"
      }
    },
    {
      path: "/basic/comment",
      name: "comment",
      component: "/src/views/basic/comments.vue",
      meta: {
        icon: "ep:chat-dot-round",
        title: "评论管理"
      }
    },
    {
      path: "/basic/product",
      name: "product",
      meta: {
        icon: "ep:goods",
        title: "商品管理"
      }
    },
    {
      path: "/basic/cardKey",
      name: "CardKey",
      component: "/src/views/basic/cardKey.vue",
      meta: {
        icon: "ep:ticket",
        title: "卡密管理",
        auths: ["cardkey:view"]
      }
    },
    {
      path: "/basic/donation",
      name: "Donation",
      component: "/src/views/basic/donation.vue",
      meta: {
        icon: "ep:coin",
        title: "捐赠记录",
        auths: ["donation:view"]
      }
    }
  ]
};

// 系统管理
const systemRouter = {
  path: "/system",
  meta: {
    title: "系统管理",
    icon: "ep:setting",
    rank: 10
  },
  children: [
    {
      path: "/system/files",
      name: "SystemFiles",
      component: "/src/views/system/files/index.vue",
      meta: {
        title: "文件管理",
        roles: ["superAdmin"],
        icon: "ep:folder-opened"
      }
    },
    {
      path: "/system/notice",
      name: "SystemNotice",
      meta: {
        title: "公告管理",
        roles: ["superAdmin"],
        icon: "ep:bell"
      }
    },
    {
      path: "/system/log",
      name: "SystemLog",
      meta: {
        title: "日志管理",
        roles: ["superAdmin"],
        icon: "ep:list"
      }
    },
    {
      path: "/system/mysql-event",
      name: "MysqlEvent",
      meta: {
        title: "数据库事件调度器",
        roles: ["superAdmin"],
        icon: "ep:clock"
      }
    },

    {
      path: "/system/backup",
      name: "SystemBackup",
      meta: {
        title: "备份恢复",
        roles: ["superAdmin"],
        icon: "ep:download"
      }
    },
    {
      path: "/system/settings",
      name: "SystemSettings",
      meta: {
        title: "系统设置",
        roles: ["superAdmin"],
        icon: "ep:setting"
      }
    },
    {
      path: "/system/theme",
      name: "SystemTheme",
      component: "/src/views/system/theme/index.vue",
      meta: {
        title: "主题配置",
        roles: ["superAdmin"],
        icon: "ep:brush"
      }
    }
  ]
};

// 数据管理
const dataRouter = {
  path: "/data",
  meta: {
    title: "数据管理",
    icon: "ep:data-line",
    rank: 8
  },
  children: [
    {
      path: "/data/dashboard",
      name: "Dashboard",
      meta: {
        title: "数据概览",
        icon: "ep:pie-chart"
      }
    },
    {
      path: "/data/analysis",
      name: "Analysis",
      meta: {
        title: "数据分析",
        icon: "ep:data-analysis"
      }
    },
    {
      path: "/data/report",
      name: "Report",
      meta: {
        title: "统计报表",
        icon: "ep:histogram"
      }
    },
    {
      path: "/data/likes",
      name: "DataLikes",
      component: "/src/views/basic/likes.vue",
      meta: {
        title: "点赞管理",
        icon: "ep:star"
      }
    },
    {
      path: "/data/favorites",
      name: "DataFavorites",
      component: "/src/views/basic/favorites.vue",
      meta: {
        title: "收藏管理",
        icon: "ep:collection"
      }
    }
  ]
};

// 支付管理
const paymentRouter = {
  path: "/payment",
  meta: {
    title: "支付管理",
    icon: "ep:money",
    rank: 9
  },
  children: [
    {
      path: "/payment/channel",
      name: "PaymentChannel",
      meta: {
        title: "支付渠道管理",
        icon: "ep:credit-card"
      }
    },
    {
      path: "/payment/record",
      name: "PaymentRecord",
      meta: {
        title: "支付记录",
        icon: "ep:tickets"
      }
    },
    {
      path: "/payment/refund",
      name: "PaymentRefund",
      meta: {
        title: "退款管理",
        icon: "ep:turn-off"
      }
    }
  ]
};

// 等级系统
const levelRouter = {
  path: "/level",
  rank: 2,
  meta: {
    title: "等级系统",
    icon: "ep:trophy"
  },
  children: [
    {
      path: "/level/manage",
      name: "LevelSystem",
      component: "/src/views/basic/level.vue",
      meta: {
        icon: "ep:trophy",
        title: "等级管理"
      }
    }
  ]
};

const permissionRouter = {
  path: "/permission",
  meta: {
    title: "权限管理",
    icon: "ep:lock",
    rank: 12
  },
  children: [
    {
      path: "/permission/roles",
      name: "PermissionRoles",
      component: "permission/roles",
      meta: {
        title: "角色管理",
        roles: ["superAdmin", "common"],
        icon: "ep:user-filled"
      }
    },
    {
      path: "/permission/list",
      name: "PermissionList",
      component: "permission/list",
      meta: {
        title: "权限列表",
        roles: ["superAdmin"],
        icon: "ep:list"
      }
    },
    {
      path: "/permission/api",
      name: "PermissionApi",
      component: "/src/views/system/api.vue",
      meta: {
        title: "接口管理",
        roles: ["superAdmin"],
        icon: "ep:connection"
      }
    }
  ]
};

export default defineFakeRoute([
  {
    url: "/get-async-routes",
    method: "get",
    response: () => {
      return {
        success: true,
        data: [
          dataRouter,
          permissionRouter,
          testRouter,
          levelRouter,
          systemRouter,
          paymentRouter
        ]
      };
    }
  }
]);
