import express from 'express';
import { createServer } from 'http';
import { Server, Socket } from 'socket.io';
import path from 'path';

// 创建Express应用
const app = express();
const httpServer = createServer(app);

// 创建Socket.IO服务器实例
const io = new Server(httpServer, {
  cors: {
    origin: '*', // 在生产环境中应更改为特定域名
    methods: ['GET', 'POST'],
    credentials: true
  }
});

// 设置端口
const PORT = process.env.PORT || 3000;

// 连接的客户端
interface ConnectedClient {
  id: string;
  username: string;
  room?: string;
}

// 存储连接的客户端
const connectedClients: Map<string, ConnectedClient> = new Map();

// 监听连接事件
io.on('connection', (socket: Socket) => {
  console.log(`New client connected: ${socket.id}`);

  // 添加新连接的客户端
  connectedClients.set(socket.id, {
    id: socket.id,
    username: `User-${Math.floor(Math.random() * 1000)}`
  });

  // 发送欢迎消息
  socket.emit('welcome', {
    message: `Welcome to the server! Your ID is ${socket.id}`,
    id: socket.id,
    timestamp: new Date()
  });

  // 广播新用户连接
  socket.broadcast.emit('userJoined', {
    userId: socket.id,
    username: connectedClients.get(socket.id)?.username,
    timestamp: new Date(),
    message: `${connectedClients.get(socket.id)?.username} has joined the server`
  });

  // 获取当前在线用户列表
  socket.on('getUsers', () => {
    const users = Array.from(connectedClients.values());
    socket.emit('userList', users);
  });

  // 处理用户设置用户名
  socket.on('setUsername', (username: string) => {
    const client = connectedClients.get(socket.id);
    if (client) {
      const oldUsername = client.username;
      client.username = username;
      connectedClients.set(socket.id, client);

      // 通知所有用户
      io.emit('usernameChanged', {
        userId: socket.id,
        oldUsername,
        newUsername: username,
        timestamp: new Date()
      });
    }
  });

  // 处理客户端发送的消息
  socket.on('message', (data: { text: string; room?: string }) => {
    const client = connectedClients.get(socket.id);
    if (!client) return;

    const messageData = {
      id: Date.now().toString(),
      text: data.text,
      sender: {
        id: socket.id,
        username: client.username
      },
      timestamp: new Date()
    };

    // 如果指定了房间，则发送到该房间
    if (data.room) {
      socket.to(data.room).emit('message', messageData);
    } else {
      // 否则广播给所有用户
      socket.broadcast.emit('message', messageData);
    }

    // 返回确认消息
    socket.emit('messageSent', { success: true, messageId: messageData.id });
  });

  // 处理加入房间请求
  socket.on('joinRoom', (roomName: string) => {
    // 先离开当前房间
    const client = connectedClients.get(socket.id);
    if (client && client.room) {
      socket.leave(client.room);
      socket.to(client.room).emit('userLeft', {
        userId: socket.id,
        username: client.username,
        room: client.room,
        timestamp: new Date()
      });
    }

    // 加入新房间
    socket.join(roomName);
    if (client) {
      client.room = roomName;
      connectedClients.set(socket.id, client);
    }

    // 通知房间其他成员
    socket.to(roomName).emit('userJoined', {
      userId: socket.id,
      username: client?.username,
      room: roomName,
      timestamp: new Date()
    });

    // 通知自己
    socket.emit('roomJoined', {
      room: roomName,
      timestamp: new Date()
    });
  });

  // 处理离开房间请求
  socket.on('leaveRoom', (roomName: string) => {
    socket.leave(roomName);
    const client = connectedClients.get(socket.id);
    if (client) {
      client.room = undefined;
      connectedClients.set(socket.id, client);
    }

    // 通知房间其他成员
    socket.to(roomName).emit('userLeft', {
      userId: socket.id,
      username: client?.username,
      room: roomName,
      timestamp: new Date()
    });

    // 通知自己
    socket.emit('roomLeft', {
      room: roomName,
      timestamp: new Date()
    });
  });

  // 处理断开连接事件
  socket.on('disconnect', () => {
    const client = connectedClients.get(socket.id);
    console.log(`Client disconnected: ${socket.id}`);

    // 如果在房间中，通知房间其他成员
    if (client && client.room) {
      socket.to(client.room).emit('userLeft', {
        userId: socket.id,
        username: client.username,
        room: client.room,
        timestamp: new Date()
      });
    }

    // 广播用户离开
    socket.broadcast.emit('userDisconnected', {
      userId: socket.id,
      username: client?.username,
      timestamp: new Date()
    });

    // 从连接列表中移除
    connectedClients.delete(socket.id);
  });

  // 处理ping请求
  socket.on('ping', (callback) => {
    if (typeof callback === 'function') {
      callback({
        status: 'ok',
        time: new Date(),
        message: 'pong'
      });
    }
  });

  // 处理错误
  socket.on('error', (error) => {
    console.error(`Socket error for client ${socket.id}:`, error);
    socket.emit('error', {
      message: 'An error occurred',
      timestamp: new Date()
    });
  });
});

// 静态文件服务
app.use(express.static(path.join(__dirname, '../../dist')));

// 启动服务器
httpServer.listen(PORT, () => {
  console.log(`Socket.IO server running on port ${PORT}`);
});

// 优雅关闭
process.on('SIGINT', () => {
  console.log('Shutting down Socket.IO server...');
  io.close();
  httpServer.close();
  process.exit(0);
});

export { io, httpServer };
