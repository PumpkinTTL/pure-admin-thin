// 导出Socket.IO服务器
export * from './server';

// 导出Socket.IO客户端服务
export { default as socketService } from './client';

// Socket.IO配置
export const socketConfig = {
  serverUrl: process.env.NODE_ENV === 'production'
    ? 'https://your-production-domain.com'  // 生产环境URL
    : 'http://localhost:3000',              // 开发环境URL
  reconnection: true,
  autoConnect: false,
};

// 快速使用示例:
/*
import { socketService } from '@/socketio';

// 连接到服务器
socketService.connect().then(() => {
  console.log('Connected to Socket.IO server');
  
  // 获取用户列表
  socketService.getUsers();
  
  // 发送消息
  socketService.sendMessage('Hello, world!');
  
  // 加入房间
  socketService.joinRoom('general');
}).catch(error => {
  console.error('Failed to connect:', error);
});

// 监听消息
socketService.on('message', (data) => {
  console.log('Received message:', data);
});
*/ 