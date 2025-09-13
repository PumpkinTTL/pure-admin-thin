<template>
  <div class="recent-comments" v-motion :initial="{ opacity: 0, y: 50 }"
    :enter="{ opacity: 1, y: 0, transition: { delay: 500 } }">
    <el-card shadow="hover" class="animate__animated animate__fadeIn">
      <template #header>
        <div class="card-header">
          <span>最新评论</span>
          <el-button type="primary" link>查看全部</el-button>
        </div>
      </template>
      <div class="comments-list">
        <div v-for="(comment, index) in comments" :key="index" class="comment-item">
          <el-avatar :size="40" :src="comment.avatar"></el-avatar>
          <div class="comment-content">
            <div class="comment-header">
              <span class="username">{{ comment.username }}</span>
              <span class="time">{{ comment.time }}</span>
            </div>
            <div class="comment-text">{{ comment.content }}</div>
            <div class="comment-post">
              <font-awesome-icon icon="file-alt" class="mr-5" />
              <span>{{ comment.post }}</span>
            </div>
            <div class="comment-actions">
              <el-button type="primary" link size="small">回复</el-button>
              <el-button type="success" link size="small">批准</el-button>
              <el-button type="danger" link size="small">删除</el-button>
            </div>
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script lang="ts">
import { ref, defineComponent } from "vue";

interface Comment {
  id: number;
  username: string;
  avatar: string;
  content: string;
  time: string;
  post: string;
}

export default defineComponent({
  name: "RecentComments",
  setup() {
    const comments = ref<Comment[]>([
      {
        id: 1,
        username: "张三",
        avatar: "https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png",
        content: "这篇文章写得非常好，对我帮助很大！",
        time: "10分钟前",
        post: "Vue3 组合式 API 最佳实践"
      },
      {
        id: 2,
        username: "李四",
        avatar: "https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png",
        content: "我有一个问题，可以详细解释一下第三部分吗？",
        time: "30分钟前",
        post: "如何优化前端性能"
      },
      {
        id: 3,
        username: "王五",
        avatar: "https://cube.elemecdn.com/9/c2/f0ee8a3c7c9638a54940382568c9dpng.png",
        content: "京都真的很美，我也去过那里，推荐大家去看看！",
        time: "1小时前",
        post: "旅行日记：春日京都"
      }
    ]);

    return {
      comments
    };
  }
});
</script>

<style scoped lang="scss">
.recent-comments {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .comments-list {
    .comment-item {
      display: flex;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;

      &:last-child {
        border-bottom: none;
      }

      .el-avatar {
        margin-right: 12px;
      }

      .comment-content {
        flex: 1;

        .comment-header {
          display: flex;
          justify-content: space-between;
          margin-bottom: 5px;

          .username {
            font-weight: bold;
            color: #303133;
          }

          .time {
            color: #909399;
            font-size: 12px;
          }
        }

        .comment-text {
          color: #606266;
          margin-bottom: 5px;
          line-height: 1.5;
        }

        .comment-post {
          font-size: 12px;
          color: #909399;
          margin-bottom: 5px;

          i {
            margin-right: 5px;
          }
        }

        .comment-actions {
          display: flex;
          gap: 10px;
        }
      }
    }
  }
}
</style>