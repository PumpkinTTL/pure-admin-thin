<template>
  <div class="recent-posts" v-motion :initial="{ opacity: 0, y: 50 }"
    :enter="{ opacity: 1, y: 0, transition: { delay: 400 } }">
    <el-card shadow="hover" class="animate__animated animate__fadeIn">
      <template #header>
        <div class="card-header">
          <span>最近发布</span>
          <el-button type="primary" link>查看全部</el-button>
        </div>
      </template>
      <el-table :data="recentPosts" style="width: 100%" :show-header="true" size="large">
        <el-table-column prop="title" label="标题" min-width="200">
          <template #default="scope">
            <div class="post-title">
              <el-tag :type="scope.row.category.type" size="small" class="mr-5">{{ scope.row.category.name }}</el-tag>
              {{ scope.row.title }}
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="views" label="浏览量" width="100" align="center" />
        <el-table-column prop="comments" label="评论" width="80" align="center" />
        <el-table-column prop="date" label="发布日期" width="120" align="center" />
        <el-table-column label="操作" width="150" align="center">
          <template #default>
            <el-button type="primary" link size="small">编辑</el-button>
            <el-button type="danger" link size="small">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script lang="ts">
import { ref, defineComponent } from "vue";

interface Category {
  name: string;
  type: 'success' | 'warning' | 'danger' | 'info' | 'primary';
}

interface Post {
  id: number;
  title: string;
  views: number;
  comments: number;
  date: string;
  category: Category;
}

export default defineComponent({
  name: "RecentPosts",
  setup() {
    const recentPosts = ref<Post[]>([
      {
        id: 1,
        title: "Vue3 组合式 API 最佳实践",
        views: 1254,
        comments: 25,
        date: "2023-05-20",
        category: { name: "技术", type: "success" }
      },
      {
        id: 2,
        title: "如何优化前端性能",
        views: 987,
        comments: 18,
        date: "2023-05-18",
        category: { name: "技术", type: "success" }
      },
      {
        id: 3,
        title: "旅行日记：春日京都",
        views: 756,
        comments: 32,
        date: "2023-05-15",
        category: { name: "随笔", type: "warning" }
      },
      {
        id: 4,
        title: "从零开始学习 TypeScript",
        views: 1102,
        comments: 42,
        date: "2023-05-12",
        category: { name: "教程", type: "primary" }
      },
      {
        id: 5,
        title: "我的工作环境搭建",
        views: 632,
        comments: 15,
        date: "2023-05-10",
        category: { name: "分享", type: "info" }
      }
    ]);

    return {
      recentPosts
    };
  }
});
</script>

<style scoped lang="scss">
.recent-posts {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .post-title {
    display: flex;
    align-items: center;

    .el-tag {
      margin-right: 8px;
    }
  }

  .mr-5 {
    margin-right: 5px;
  }
}
</style>