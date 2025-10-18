const Layout = () => import("@/layout/index.vue");

export default {
  path: "/basic",
  name: "Basic",
  component: Layout,
  redirect: "/basic/user",
  meta: {
    icon: "ep:management",
    title: "基础管理",
    rank: 2
  },
  children: [
    {
      path: "/basic/user",
      name: "User",
      component: () => import("@/views/basic/user.vue"),
      meta: {
        title: "用户管理"
      }
    },
    {
      path: "/basic/roles",
      name: "Roles",
      component: () => import("@/views/basic/roles.vue"),
      meta: {
        title: "角色管理"
      }
    },
    {
      path: "/basic/comments",
      name: "Comments",
      component: () => import("@/views/basic/comments.vue"),
      meta: {
        title: "评论管理"
      }
    },
    {
      path: "/basic/likes",
      name: "Likes",
      component: () => import("@/views/basic/likes.vue"),
      meta: {
        title: "点赞管理"
      }
    },
    {
      path: "/basic/article",
      name: "Article",
      component: () => import("@/views/basic/article.vue"),
      meta: {
        title: "文章管理"
      }
    },
    {
      path: "/basic/category",
      name: "Category",
      component: () => import("@/views/basic/category.vue"),
      meta: {
        title: "分类管理"
      }
    },
    {
      path: "/basic/resource",
      name: "Resource",
      component: () => import("@/views/basic/resource.vue"),
      meta: {
        title: "资源管理"
      }
    },
    {
      path: "/basic/order",
      name: "Order",
      component: () => import("@/views/basic/order.vue"),
      meta: {
        title: "订单管理"
      }
    },
    {
      path: "/basic/cardKey",
      name: "CardKey",
      component: () => import("@/views/basic/cardKey.vue"),
      meta: {
        title: "卡密管理"
      }
    }
  ]
} satisfies RouteConfigsTable;
