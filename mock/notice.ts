import { defineFakeRoute } from "vite-plugin-fake-server/client";
import { faker } from '@faker-js/faker';

// 模拟用户数据
const mockUsers = [
  {
    id: 1,
    username: "admin",
    avatar: "https://avatars.githubusercontent.com/u/44761321",
    role: "管理员"
  },
  {
    id: 2,
    username: "developer",
    avatar: "https://avatars.githubusercontent.com/u/52823142",
    role: "开发者"
  },
  {
    id: 3,
    username: "tester",
    avatar: "https://avatars.githubusercontent.com/u/3031563",
    role: "测试人员"
  },
  {
    id: 4,
    username: "designer",
    avatar: "https://avatars.githubusercontent.com/u/5031563",
    role: "设计师"
  },
  {
    id: 5,
    username: "manager",
    avatar: "https://avatars.githubusercontent.com/u/6031563",
    role: "项目经理"
  }
];

// 公告类型
const noticeTypes = [
  { id: 1, name: "系统公告", color: "#1890ff", icon: "bullhorn" },
  { id: 2, name: "功能更新", color: "#52c41a", icon: "sync" },
  { id: 3, name: "维护通知", color: "#faad14", icon: "tools" },
  { id: 4, name: "安全提醒", color: "#f5222d", icon: "shield-alt" },
  { id: 5, name: "节日活动", color: "#722ed1", icon: "calendar-alt" }
];

// 公告优先级
const priorities = [
  { id: 1, name: "低", color: "#52c41a" },
  { id: 2, name: "中", color: "#1890ff" },
  { id: 3, name: "高", color: "#faad14" },
  { id: 4, name: "紧急", color: "#f5222d" }
];

// 公告状态
const statuses = [
  { id: 1, name: "草稿", color: "#d9d9d9" },
  { id: 2, name: "已发布", color: "#52c41a" },
  { id: 3, name: "已过期", color: "#8c8c8c" },
  { id: 4, name: "已撤回", color: "#f5222d" }
];

// 公告接收范围
const scopeTypes = [
  { id: 1, name: "全部用户", icon: "users" },
  { id: 2, name: "特定用户", icon: "user" },
  { id: 3, name: "特定部门", icon: "building" },
  { id: 4, name: "特定角色", icon: "user-tag" }
];

// 生成模拟公告数据
const generateMockNotices = (count = 30) => {
  const notices = [];

  for (let i = 1; i <= count; i++) {
    // 随机发布者
    const randomUser = mockUsers[Math.floor(Math.random() * mockUsers.length)];

    // 随机公告类型
    const randomType = noticeTypes[Math.floor(Math.random() * noticeTypes.length)];

    // 随机优先级
    const randomPriority = priorities[Math.floor(Math.random() * priorities.length)];

    // 随机状态
    const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];

    // 随机接收范围
    const randomScope = scopeTypes[Math.floor(Math.random() * scopeTypes.length)];

    // 创建和发布时间
    const createTime = faker.date.past({ years: 1 });
    const publishTime = randomStatus.id >= 2 ? faker.date.between({ from: createTime, to: new Date() }) : null;

    // 过期时间
    let expireTime = null;
    if (randomStatus.id === 2) {
      // 如果是已发布状态，设置未来的过期时间
      expireTime = faker.date.future({ years: 1, refDate: publishTime });
    } else if (randomStatus.id === 3) {
      // 如果是已过期状态，设置过去的过期时间
      expireTime = faker.date.between({ from: publishTime || createTime, to: new Date() });
    }

    // 接收者列表
    let receivers = [];
    if (randomScope.id === 1) {
      // 全部用户不需要特定接收者列表
      receivers = [];
    } else {
      // 随机选择1-3个接收者
      const receiverCount = Math.floor(Math.random() * 3) + 1;
      const usedReceivers = new Set();

      for (let j = 0; j < receiverCount; j++) {
        let randomReceiverId;
        do {
          randomReceiverId = mockUsers[Math.floor(Math.random() * mockUsers.length)].id;
        } while (usedReceivers.has(randomReceiverId));

        usedReceivers.add(randomReceiverId);
        receivers.push({
          id: randomReceiverId,
          username: mockUsers.find(user => user.id === randomReceiverId)?.username,
          read_status: Math.random() > 0.5 ? 1 : 0, // 1: 已读, 0: 未读
          read_time: Math.random() > 0.5 ? faker.date.between({ from: publishTime || createTime, to: new Date() }) : null
        });
      }
    }

    // 生成通知数据
    const notice = {
      id: i,
      title: `${randomType.name}: ${faker.lorem.sentence(Math.floor(Math.random() * 5) + 3)}`,
      content: faker.lorem.paragraphs(Math.floor(Math.random() * 3) + 1),
      type_id: randomType.id,
      type: randomType,
      publisher_id: randomUser.id,
      publisher: randomUser,
      priority_id: randomPriority.id,
      priority: randomPriority,
      status_id: randomStatus.id,
      status: randomStatus,
      create_time: createTime.toISOString(),
      publish_time: publishTime?.toISOString() || null,
      expire_time: expireTime?.toISOString() || null,
      update_time: faker.date.between({ from: createTime, to: new Date() }).toISOString(),
      scope_type_id: randomScope.id,
      scope_type: randomScope,
      receivers: receivers,
      view_count: Math.floor(Math.random() * 500),
      is_pinned: Math.random() > 0.8 ? 1 : 0, // 20%置顶概率
      delete_time: null,
      attachment: Math.random() > 0.7 ? {
        id: i * 100,
        name: `attachment_${faker.string.alphanumeric(8)}.${['pdf', 'docx', 'xlsx', 'zip'][Math.floor(Math.random() * 4)]}`,
        size: Math.floor(Math.random() * 10000) + 50,
        url: `https://example.com/attachments/${faker.string.alphanumeric(16)}`
      } : null
    };

    notices.push(notice);
  }

  return notices;
};

// 生成通知数据
const mockNotices = generateMockNotices();

export default defineFakeRoute([
  // 获取通知列表
  {
    url: "/api/v1/notices",
    method: "get",
    response: ({ query }) => {
      const { page = 1, size = 10, title, type_id, status_id, priority_id, scope_type_id, is_pinned } = query;

      // 过滤数据
      let filteredNotices = [...mockNotices];

      if (title) {
        filteredNotices = filteredNotices.filter(item =>
          item.title.toLowerCase().includes(title.toLowerCase())
        );
      }

      if (type_id) {
        filteredNotices = filteredNotices.filter(item =>
          item.type_id === parseInt(type_id)
        );
      }

      if (status_id) {
        filteredNotices = filteredNotices.filter(item =>
          item.status_id === parseInt(status_id)
        );
      }

      if (priority_id) {
        filteredNotices = filteredNotices.filter(item =>
          item.priority_id === parseInt(priority_id)
        );
      }

      if (scope_type_id) {
        filteredNotices = filteredNotices.filter(item =>
          item.scope_type_id === parseInt(scope_type_id)
        );
      }

      if (is_pinned !== undefined) {
        filteredNotices = filteredNotices.filter(item =>
          item.is_pinned === parseInt(is_pinned)
        );
      }

      // 分页
      const pageNum = parseInt(page);
      const pageSize = parseInt(size);
      const startIndex = (pageNum - 1) * pageSize;
      const endIndex = startIndex + pageSize;
      const paginatedNotices = filteredNotices.slice(startIndex, endIndex);

      return {
        success: true,
        data: {
          list: paginatedNotices,
          total: filteredNotices.length,
          pageSize: pageSize,
          currentPage: pageNum
        }
      };
    }
  },

  // 获取通知详情
  {
    url: "/api/v1/notices/:id",
    method: "get",
    response: ({ params }) => {
      const id = parseInt(params.id);
      const notice = mockNotices.find(item => item.id === id);

      if (!notice) {
        return {
          success: false,
          message: "公告不存在"
        };
      }

      return {
        success: true,
        data: notice
      };
    }
  },

  // 创建通知
  {
    url: "/api/v1/notices",
    method: "post",
    response: ({ body }) => {
      const newId = mockNotices.length > 0 ? Math.max(...mockNotices.map(item => item.id)) + 1 : 1;
      const newNotice = {
        id: newId,
        ...body,
        create_time: new Date().toISOString(),
        update_time: new Date().toISOString(),
        delete_time: null
      };

      mockNotices.push(newNotice);

      return {
        success: true,
        message: "创建公告成功",
        data: newNotice
      };
    }
  },

  // 更新通知
  {
    url: "/api/v1/notices/:id",
    method: "put",
    response: ({ params, body }) => {
      const id = parseInt(params.id);
      const index = mockNotices.findIndex(item => item.id === id);

      if (index === -1) {
        return {
          success: false,
          message: "公告不存在"
        };
      }

      mockNotices[index] = {
        ...mockNotices[index],
        ...body,
        update_time: new Date().toISOString()
      };

      return {
        success: true,
        message: "更新公告成功",
        data: mockNotices[index]
      };
    }
  },

  // 删除通知（软删除）
  {
    url: "/api/v1/notices/:id",
    method: "delete",
    response: ({ params }) => {
      const id = parseInt(params.id);
      const index = mockNotices.findIndex(item => item.id === id);

      if (index === -1) {
        return {
          success: false,
          message: "公告不存在"
        };
      }

      mockNotices[index].delete_time = new Date().toISOString();

      return {
        success: true,
        message: "删除公告成功"
      };
    }
  },

  // 批量删除通知
  {
    url: "/api/v1/notices/batch",
    method: "delete",
    response: ({ body }) => {
      const { ids } = body;

      if (!ids || !Array.isArray(ids) || ids.length === 0) {
        return {
          success: false,
          message: "参数错误"
        };
      }

      ids.forEach(id => {
        const index = mockNotices.findIndex(item => item.id === id);
        if (index !== -1) {
          mockNotices[index].delete_time = new Date().toISOString();
        }
      });

      return {
        success: true,
        message: `成功删除 ${ids.length} 条公告`
      };
    }
  },

  // 发布通知
  {
    url: "/api/v1/notices/:id/publish",
    method: "put",
    response: ({ params }) => {
      const id = parseInt(params.id);
      const index = mockNotices.findIndex(item => item.id === id);

      if (index === -1) {
        return {
          success: false,
          message: "公告不存在"
        };
      }

      mockNotices[index].status_id = 2; // 设置为已发布
      mockNotices[index].status = statuses.find(status => status.id === 2);
      mockNotices[index].publish_time = new Date().toISOString();

      // 设置默认过期时间为30天后
      const expireDate = new Date();
      expireDate.setDate(expireDate.getDate() + 30);
      mockNotices[index].expire_time = expireDate.toISOString();

      return {
        success: true,
        message: "发布公告成功",
        data: mockNotices[index]
      };
    }
  },

  // 撤回通知
  {
    url: "/api/v1/notices/:id/revoke",
    method: "put",
    response: ({ params }) => {
      const id = parseInt(params.id);
      const index = mockNotices.findIndex(item => item.id === id);

      if (index === -1) {
        return {
          success: false,
          message: "公告不存在"
        };
      }

      mockNotices[index].status_id = 4; // 设置为已撤回
      mockNotices[index].status = statuses.find(status => status.id === 4);

      return {
        success: true,
        message: "撤回公告成功",
        data: mockNotices[index]
      };
    }
  },

  // 置顶/取消置顶通知
  {
    url: "/api/v1/notices/:id/pin",
    method: "put",
    response: ({ params, body }) => {
      const id = parseInt(params.id);
      const { is_pinned } = body;
      const index = mockNotices.findIndex(item => item.id === id);

      if (index === -1) {
        return {
          success: false,
          message: "公告不存在"
        };
      }

      mockNotices[index].is_pinned = is_pinned ? 1 : 0;

      return {
        success: true,
        message: is_pinned ? "置顶公告成功" : "取消置顶成功",
        data: mockNotices[index]
      };
    }
  }
]); 