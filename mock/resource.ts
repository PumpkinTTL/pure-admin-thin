import { defineFakeRoute } from "vite-plugin-fake-server/client";
import { faker } from '@faker-js/faker';

// 模拟用户数据
const mockUsers = [
  {
    id: 1,
    username: "admin",
    avatar: "https://avatars.githubusercontent.com/u/44761321"
  },
  {
    id: 2,
    username: "developer",
    avatar: "https://avatars.githubusercontent.com/u/52823142"
  },
  {
    id: 3,
    username: "tester",
    avatar: "https://avatars.githubusercontent.com/u/3031563"
  }
];

// 模拟资源分类数据
const mockCategories = [
  { id: 1, name: "设计资源" },
  { id: 2, name: "开发工具" },
  { id: 3, name: "教程文档" },
  { id: 4, name: "办公模板" },
  { id: 5, name: "软件应用" }
];

// 模拟标签数据
const mockTags = [
  { id: 1, name: "免费" },
  { id: 2, name: "付费" },
  { id: 3, name: "高级" },
  { id: 4, name: "推荐" },
  { id: 5, name: "热门" },
  { id: 6, name: "新品" },
  { id: 7, name: "限时" }
];

// 模拟下载方式
const downloadMethodNames = ["百度网盘", "天翼云盘", "阿里云盘", "OneDrive", "Google Drive", "Dropbox", "直接下载", "Mega"];

// 生成模拟资源数据
const generateMockResources = (count = 30) => {
  const resources = [];

  for (let i = 1; i <= count; i++) {
    // 随机用户
    const randomUser = mockUsers[Math.floor(Math.random() * mockUsers.length)];

    // 随机分类
    const randomCategory = mockCategories[Math.floor(Math.random() * mockCategories.length)];

    // 随机标签 (1-3个)
    const tagCount = Math.floor(Math.random() * 3) + 1;
    const resourceTags = [];
    const usedTagIds = new Set();

    for (let j = 0; j < tagCount; j++) {
      let randomTagIndex;
      do {
        randomTagIndex = Math.floor(Math.random() * mockTags.length);
      } while (usedTagIds.has(randomTagIndex));

      usedTagIds.add(randomTagIndex);
      resourceTags.push(mockTags[randomTagIndex]);
    }

    // 随机下载方式 (1-3个)
    const downloadCount = Math.floor(Math.random() * 3) + 1;
    const downloadMethods = [];
    const usedMethodNames = new Set();

    for (let j = 0; j < downloadCount; j++) {
      let randomMethodName;
      do {
        randomMethodName = downloadMethodNames[Math.floor(Math.random() * downloadMethodNames.length)];
      } while (usedMethodNames.has(randomMethodName));

      usedMethodNames.add(randomMethodName);

      downloadMethods.push({
        id: i * 10 + j,
        resource_id: i,
        method_name: randomMethodName,
        download_link: `https://example.com/download/${faker.string.alphanumeric(8)}`,
        extraction_code: Math.random() > 0.5 ? faker.string.alphanumeric(4) : "",
        create_time: faker.date.past().toISOString(),
        update_time: faker.date.recent().toISOString(),
        status: Math.random() > 0.1 ? 1 : 0, // 90% 概率可用
        sort_order: j + 1,
        delete_time: null
      });
    }

    // 资源基本信息
    const resource = {
      id: i,
      resource_name: `${faker.commerce.productName()} ${faker.string.alphanumeric(4)}`,
      category_id: randomCategory.id,
      category: randomCategory.name,
      platform: ["Windows", "MacOS", "Linux", "Android", "iOS", "跨平台"][Math.floor(Math.random() * 6)],
      user_id: randomUser.id,
      user: randomUser,
      release_time: faker.date.past().toISOString(),
      download_count: Math.floor(Math.random() * 10000),
      view_count: Math.floor(Math.random() * 50000),
      favorite_count: Math.floor(Math.random() * 1000),
      resource_size: parseFloat((Math.random() * 1000).toFixed(2)),
      version: `v${Math.floor(Math.random() * 10)}.${Math.floor(Math.random() * 10)}.${Math.floor(Math.random() * 10)}`,
      file_format: ["ZIP", "RAR", "EXE", "DMG", "PDF", "PSD"][Math.floor(Math.random() * 6)],
      update_time: faker.date.recent().toISOString(),
      cover_url: `https://picsum.photos/id/${(i % 100) + 1}/300/200`,
      description: faker.lorem.paragraphs(2),
      file_hash: faker.string.alphanumeric(40),
      language: ["中文", "英文", "中英双语", "多语言"][Math.floor(Math.random() * 4)],
      is_premium: Math.random() > 0.7 ? 1 : 0,
      delete_time: Math.random() > 0.9 ? faker.date.recent().toISOString() : null, // 10% 概率已删除
      tags: resourceTags,
      downloadMethods: downloadMethods
    };

    resources.push(resource);
  }

  return resources;
};

// 生成资源数据
const mockResources = generateMockResources();

export default defineFakeRoute([
  // 获取资源列表
  {
    url: "/api/v1/resources",
    method: "get",
    response: ({ query }) => {
      const { page = 1, size = 10, resource_name, category_id, platform, status, is_premium } = query;

      // 过滤数据
      let filteredResources = [...mockResources];

      if (resource_name) {
        filteredResources = filteredResources.filter(item =>
          item.resource_name.toLowerCase().includes(resource_name.toLowerCase())
        );
      }

      if (category_id) {
        filteredResources = filteredResources.filter(item =>
          item.category_id === parseInt(category_id)
        );
      }

      if (platform) {
        filteredResources = filteredResources.filter(item =>
          item.platform === platform
        );
      }

      if (status === "deleted") {
        filteredResources = filteredResources.filter(item => item.delete_time !== null);
      } else if (status === "normal") {
        filteredResources = filteredResources.filter(item => item.delete_time === null);
      }

      if (is_premium !== undefined) {
        filteredResources = filteredResources.filter(item =>
          item.is_premium === parseInt(is_premium)
        );
      }

      // 分页
      const pageNum = parseInt(page);
      const pageSize = parseInt(size);
      const startIndex = (pageNum - 1) * pageSize;
      const endIndex = startIndex + pageSize;
      const paginatedResources = filteredResources.slice(startIndex, endIndex);

      return {
        success: true,
        data: {
          list: paginatedResources,
          total: filteredResources.length,
          pageSize: pageSize,
          currentPage: pageNum
        }
      };
    }
  },

  // 获取资源详情
  {
    url: "/api/v1/resources/:id",
    method: "get",
    response: ({ params }) => {
      const id = parseInt(params.id);
      const resource = mockResources.find(item => item.id === id);

      if (!resource) {
        return {
          success: false,
          message: "资源不存在"
        };
      }

      return {
        success: true,
        data: resource
      };
    }
  },

  // 添加资源
  {
    url: "/api/v1/resources/add",
    method: "post",
    response: ({ body }) => {
      const newId = mockResources.length + 1;
      const newResource = {
        id: newId,
        ...body,
        create_time: new Date().toISOString(),
        update_time: new Date().toISOString(),
        download_count: 0,
        view_count: 0,
        favorite_count: 0,
        delete_time: null
      };

      // 添加用户信息
      const user = mockUsers.find(user => user.id === body.user_id);
      if (user) {
        newResource.user = user;
      }

      // 添加分类名称
      const category = mockCategories.find(cat => cat.id === body.category_id);
      if (category) {
        newResource.category = category.name;
      }

      // 处理标签
      if (body.tags && body.tags.length > 0) {
        newResource.tags = body.tags.map(tagId => {
          const tag = mockTags.find(t => t.id === tagId);
          return tag || { id: tagId, name: `标签${tagId}` };
        });
      }

      mockResources.push(newResource);

      return {
        success: true,
        message: "添加成功",
        data: newResource
      };
    }
  },

  // 更新资源
  {
    url: "/api/v1/resources/update",
    method: "post",
    response: ({ body }) => {
      const index = mockResources.findIndex(item => item.id === body.id);

      if (index === -1) {
        return {
          success: false,
          message: "资源不存在"
        };
      }

      const updatedResource = {
        ...mockResources[index],
        ...body,
        update_time: new Date().toISOString()
      };

      // 更新分类名称
      if (body.category_id) {
        const category = mockCategories.find(cat => cat.id === body.category_id);
        if (category) {
          updatedResource.category = category.name;
        }
      }

      // 处理标签
      if (body.tags && body.tags.length > 0) {
        updatedResource.tags = body.tags.map(tagId => {
          const tag = mockTags.find(t => t.id === tagId);
          return tag || { id: tagId, name: `标签${tagId}` };
        });
      }

      mockResources[index] = updatedResource;

      return {
        success: true,
        message: "更新成功",
        data: updatedResource
      };
    }
  },

  // 删除资源（软删除）
  {
    url: "/api/v1/resources/delete",
    method: "post",
    response: ({ body }) => {
      const index = mockResources.findIndex(item => item.id === body.id);

      if (index === -1) {
        return {
          success: false,
          message: "资源不存在"
        };
      }

      mockResources[index].delete_time = new Date().toISOString();

      return {
        success: true,
        message: "删除成功",
        data: null
      };
    }
  },

  // 恢复资源
  {
    url: "/api/v1/resources/restore",
    method: "post",
    response: ({ body }) => {
      const index = mockResources.findIndex(item => item.id === body.id);

      if (index === -1) {
        return {
          success: false,
          message: "资源不存在"
        };
      }

      mockResources[index].delete_time = null;

      return {
        success: true,
        message: "恢复成功",
        data: null
      };
    }
  },

  // 获取分类列表
  {
    url: "/api/v1/categories",
    method: "get",
    response: () => {
      return {
        success: true,
        data: mockCategories
      };
    }
  },

  // 获取标签列表
  {
    url: "/api/v1/tags",
    method: "get",
    response: () => {
      return {
        success: true,
        data: mockTags
      };
    }
  },

  // 添加下载方式
  {
    url: "/api/v1/download-methods/add",
    method: "post",
    response: ({ body }) => {
      const newId = Math.max(...mockResources.flatMap(r => r.downloadMethods?.map(dm => dm.id) || [0])) + 1;

      const newMethod = {
        id: newId,
        ...body,
        create_time: new Date().toISOString(),
        update_time: new Date().toISOString(),
        delete_time: null
      };

      // 找到对应资源并添加下载方式
      const resourceIndex = mockResources.findIndex(r => r.id === body.resource_id);
      if (resourceIndex !== -1) {
        if (!mockResources[resourceIndex].downloadMethods) {
          mockResources[resourceIndex].downloadMethods = [];
        }
        mockResources[resourceIndex].downloadMethods.push(newMethod);
      }

      return {
        success: true,
        message: "添加成功",
        data: newMethod
      };
    }
  },

  // 更新下载方式
  {
    url: "/api/v1/download-methods/update",
    method: "post",
    response: ({ body }) => {
      let updated = false;
      let updatedMethod = null;

      // 遍历所有资源的下载方式
      for (const resource of mockResources) {
        if (!resource.downloadMethods) continue;

        const methodIndex = resource.downloadMethods.findIndex(dm => dm.id === body.id);
        if (methodIndex !== -1) {
          updatedMethod = {
            ...resource.downloadMethods[methodIndex],
            ...body,
            update_time: new Date().toISOString()
          };

          resource.downloadMethods[methodIndex] = updatedMethod;
          updated = true;
          break;
        }
      }

      if (!updated) {
        return {
          success: false,
          message: "下载方式不存在"
        };
      }

      return {
        success: true,
        message: "更新成功",
        data: updatedMethod
      };
    }
  },

  // 删除下载方式
  {
    url: "/api/v1/download-methods/delete",
    method: "post",
    response: ({ body }) => {
      let deleted = false;

      // 遍历所有资源的下载方式
      for (const resource of mockResources) {
        if (!resource.downloadMethods) continue;

        const methodIndex = resource.downloadMethods.findIndex(dm => dm.id === body.id);
        if (methodIndex !== -1) {
          resource.downloadMethods.splice(methodIndex, 1);
          deleted = true;
          break;
        }
      }

      if (!deleted) {
        return {
          success: false,
          message: "下载方式不存在"
        };
      }

      return {
        success: true,
        message: "删除成功",
        data: null
      };
    }
  }
]); 