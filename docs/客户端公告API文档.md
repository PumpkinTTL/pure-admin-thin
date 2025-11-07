# 客户端公告 API 文档

> **Base URL**: `/api/v1/notice`  
> **接口类型**: 只读查询（GET）  
> **权限**: 后端自动过滤，前端无需处理

---

## 认证方式

请求头携带 Token：

```http
Authorization: Bearer {your_access_token}
```

未登录用户只能查看公开公告。

---

## 1. 获取公告列表

**接口**: `GET /api/v1/notice/list`

**请求参数**:

| 参数          | 类型   | 必填 | 说明                                                         |
| ------------- | ------ | ---- | ------------------------------------------------------------ |
| page          | number | 否   | 页码，默认 1                                                 |
| page_size     | number | 否   | 每页条数，默认 10                                            |
| title         | string | 否   | 标题搜索                                                     |
| category_type | number | 否   | 分类：1=系统更新，2=账号安全，3=活动通知，4=政策公告，5=其他 |
| priority      | number | 否   | 优先级：0=普通，1=重要，2=紧急                               |

> 客户端建议固定传 `status=1` 只查询已发布的公告

**响应示例**:

```json
{
  "code": 200,
  "data": {
    "total": 25,
    "data": [
      {
        "notice_id": 1,
        "title": "【重要】系统升级通知",
        "content": "为提升系统性能...",
        "category_type": 1,
        "category_type_text": "系统更新",
        "priority": 2,
        "priority_text": "紧急",
        "is_top": true,
        "visibility": "public",
        "publish_time": "2025-11-07 10:00:00",
        "expire_time": "2025-11-30 23:59:59",
        "publisher": {
          "id": 1,
          "username": "管理员",
          "avatar": "https://example.com/avatar.png"
        }
      }
    ]
  }
}
```

**关键字段**:

- `notice_id`: 公告ID
- `title`: 标题
- `content`: 内容
- `priority`: 0=普通，1=重要，2=紧急
- `is_top`: 是否置顶
- `publish_time`: 发布时间

---

## 2. 获取公告详情

**接口**: `GET /api/v1/notice/detail/{notice_id}`

**路径参数**:

- `notice_id`: 公告ID

**响应**: 与列表接口的单条数据格式相同

**错误码**:

- `403`: 无权查看
- `404`: 公告不存在

---

## 前端调用示例

```typescript
// src/api/clientNotice.ts
import { http } from "@/utils/http";

// 获取公告列表
export const getClientNoticeList = (params?: {
  page?: number;
  page_size?: number;
  title?: string;
  category_type?: number;
  priority?: number;
}) => {
  return http.request("get", "/api/v1/notice/list", {
    params: { ...params, status: 1 }
  });
};

// 获取公告详情
export const getClientNoticeDetail = (noticeId: number) => {
  return http.request("get", `/api/v1/notice/detail/${noticeId}`);
};
```

**调用示例**:

```typescript
import { getClientNoticeList, getClientNoticeDetail } from "@/api/clientNotice";

// 加载列表
const loadNotices = async () => {
  const res = await getClientNoticeList({
    page: 1,
    page_size: 10,
    status: 1
  });

  if (res.code === 200) {
    noticeList.value = res.data.data;
    total.value = res.data.total;
  }
};

// 查看详情
const viewDetail = async (noticeId: number) => {
  const res = await getClientNoticeDetail(noticeId);
  if (res.code === 200) {
    // 显示详情
  } else if (res.code === 403) {
    ElMessage.warning("您无权查看该公告");
  }
};
```

---

## 注意事项

1. **权限**: 后端自动过滤，前端只需处理 403 错误提示
2. **状态**: 客户端固定传 `status=1` 查询已发布公告
3. **缓存**: 列表数据建议缓存 5 分钟
4. **置顶**: `is_top=true` 的公告建议优先展示
