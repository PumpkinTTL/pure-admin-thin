# Socket.IO 集成文档

本项目集成了 Socket.IO 客户端和服务器端，提供实时通信功能。

## 目录结构

```
src/socketio/
├── server.ts       # Socket.IO 服务器端代码
├── client.ts       # Socket.IO 客户端代码
├── index.ts        # 导出和配置
└── readme.md       # 使用文档
```

## 安装依赖

项目使用以下依赖：

```bash
# 使用 pnpm
pnpm add socket.io socket.io-client express @types/socket.io @types/express --save-dev

# 使用 npm
npm install socket.io socket.io-client express @types/socket.io @types/express --save-dev
```

## 服务器端使用方法

### 启动服务器

```bash
# 在项目根目录下执行
ts-node src/socketio/server.ts
```

或者在 package.json 中添加启动脚本：

```json
{
  "scripts": {
    "socket-server": "ts-node src/socketio/server.ts"
  }
}
```

然后运行：

```bash
pnpm run socket-server
```

### 服务器端事件

服务器支持以下事件：

- `connection`: 客户端连接
- `disconnect`: 客户端断开连接
- `message`: 接收客户端消息
- `getUsers`: 获取用户列表
- `setUsername`: 设置用户名
- `joinRoom`: 加入房间
- `leaveRoom`: 离开房间
- `ping`: Ping服务器

## 客户端使用方法

### 基本使用

在组件中导入并使用：

```typescript
import { socketService } from '@/socketio';

// 在组件挂载时连接
onMounted(() => {
  socketService.connect()
    .then(() => {
      console.log('已连接到Socket.IO服务器');
    })
    .catch(error => {
      console.error('连接失败:', error);
    });
    
  // 监听消息
  socketService.on('message', handleMessage);
});

// 在组件销毁时断开连接
onUnmounted(() => {
  socketService.off('message', handleMessage);
  socketService.disconnect();
});

// 处理接收到的消息
const handleMessage = (data) => {
  console.log('收到消息:', data);
};

// 发送消息
const sendMessage = (text) => {
  socketService.sendMessage(text);
};
```

### 在Vue组件中使用的完整示例

```vue
<template>
  <div class="chat-container">
    <div v-if="isConnected" class="status connected">已连接</div>
    <div v-else class="status disconnected">未连接</div>
    
    <div class="message-list">
      <div v-for="msg in messages" :key="msg.id" class="message">
        <strong>{{ msg.sender.username }}:</strong> {{ msg.text }}
      </div>
    </div>
    
    <div class="input-area">
      <input v-model="messageText" @keyup.enter="sendMessage" placeholder="输入消息..." />
      <button @click="sendMessage">发送</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { socketService } from '@/socketio';

const isConnected = ref(false);
const messages = ref<any[]>([]);
const messageText = ref('');

// 处理接收到的消息
const handleMessage = (data: any) => {
  messages.value.push(data);
};

// 处理连接状态变化
const handleConnect = () => {
  isConnected.value = true;
};

const handleDisconnect = () => {
  isConnected.value = false;
};

// 发送消息
const sendMessage = () => {
  if (!messageText.value.trim()) return;
  
  socketService.sendMessage(messageText.value);
  messageText.value = '';
};

// 在组件挂载时连接
onMounted(() => {
  // 添加事件监听
  socketService.on('connect', handleConnect);
  socketService.on('disconnect', handleDisconnect);
  socketService.on('message', handleMessage);
  
  // 连接到服务器
  socketService.connect().catch(error => {
    console.error('连接失败:', error);
  });
});

// 在组件销毁时清理
onUnmounted(() => {
  // 移除事件监听
  socketService.off('connect', handleConnect);
  socketService.off('disconnect', handleDisconnect);
  socketService.off('message', handleMessage);
  
  // 断开连接
  socketService.disconnect();
});
</script>

<style scoped>
.chat-container {
  display: flex;
  flex-direction: column;
  height: 400px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.status {
  padding: 5px 10px;
  text-align: center;
}

.connected {
  background-color: #d4edda;
  color: #155724;
}

.disconnected {
  background-color: #f8d7da;
  color: #721c24;
}

.message-list {
  flex: 1;
  overflow-y: auto;
  padding: 10px;
}

.message {
  margin-bottom: 8px;
}

.input-area {
  display: flex;
  padding: 10px;
  border-top: 1px solid #ccc;
}

.input-area input {
  flex: 1;
  padding: 6px 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.input-area button {
  margin-left: 8px;
  padding: 6px 12px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style>
```

### 客户端API

客户端服务提供以下方法：

#### 连接管理
- `connect(options?)`: 连接到服务器
- `disconnect()`: 断开连接
- `isConnected()`: 检查连接状态
- `getSocket()`: 获取原始Socket实例

#### 消息和用户操作
- `sendMessage(text, room?)`: 发送消息
- `setUsername(username)`: 设置用户名
- `getUsers()`: 获取用户列表
- `joinRoom(roomName)`: 加入房间
- `leaveRoom(roomName)`: 离开房间
- `ping()`: ping服务器

#### 事件监听
- `on(event, callback)`: 添加事件监听器
- `off(event, callback?)`: 移除事件监听器

### 客户端事件

客户端可以监听以下事件：

- `connect`: 连接成功
- `connect_error`: 连接错误
- `disconnect`: 断开连接
- `welcome`: 欢迎消息
- `message`: 接收消息
- `userJoined`: 用户加入
- `userList`: 用户列表
- `usernameChanged`: 用户名更改
- `userLeft`: 用户离开
- `userDisconnected`: 用户断开连接
- `roomJoined`: 加入房间
- `roomLeft`: 离开房间
- `messageSent`: 消息发送成功
- `error`: 错误

## 配置

在 `src/socketio/index.ts` 中可以修改服务器URL等配置：

```typescript
export const socketConfig = {
  serverUrl: process.env.NODE_ENV === 'production' 
    ? 'https://your-production-domain.com'  // 修改为生产环境URL
    : 'http://localhost:3000',              // 开发环境URL
  reconnection: true,
  autoConnect: false,
};
```

## 注意事项

1. 服务器默认运行在 3000 端口，确保该端口未被占用
2. 生产环境部署时需要修改生产环境URL
3. 处理敏感数据时应进行适当的安全措施，如验证和身份认证 