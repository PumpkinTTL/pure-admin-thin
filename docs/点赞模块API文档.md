# 点赞模块API文档

## 接口列表

### 1. 点赞/取消点赞
**POST** `/api/v1/likes/toggle`

**参数：**
```json
{
  "user_id": 1001,
  "target_type": "article",  // article | comment
  "target_id": 11355
}
```

**响应：**
```json
{
  "code": 200,
  "msg": "点赞成功", // 或 "取消点赞成功"
  "data": {
    "is_liked": true // true=点赞，false=取消
  }
}
```

### 2. 获取点赞列表
**GET** `/api/v1/likes/list`

**参数：**
```
user_id=1001       # 可选，按用户筛选
target_type=article # 可选，按类型筛选
page=1             # 可选，页码
limit=10           # 可选，每页数量
```

**响应：**
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "list": [...],
    "total": 100,
    "page": 1,
    "limit": 10
  }
}
```

## 数据结构

### 点赞记录
```json
{
  "id": 1,
  "user_id": 1001,
  "target_type": "article",
  "target_id": 11355,
  "create_time": "2025-10-18 21:56:21",
  "update_time": "2025-10-18 21:56:21",
  "user": {
    "id": 1001,
    "username": "张三",
    "avatar": "头像URL"
  }
}
```

## 使用示例

### 前端调用
```typescript
import { toggleLike, getLikesList } from "@/api/likes";

// 点赞/取消
const response = await toggleLike({
  user_id: 1001,
  target_type: "article", 
  target_id: 11355
});

// 获取列表
const list = await getLikesList({
  user_id: 1001,
  page: 1,
  limit: 10
});
```

### 业务逻辑
- **智能切换**：记录存在则删除（取消），不存在则创建（点赞）
- **物理删除**：所有删除操作都是物理删除，不保留记录
- **支持类型**：article（文章）、comment（评论）