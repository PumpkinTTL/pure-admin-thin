import { io, Socket } from 'socket.io-client';

// 定义事件类型
type MessageData = {
  id: string;
  text: string;
  sender: {
    id: string;
    username: string;
  };
  timestamp: Date;
};

type UserData = {
  id: string;
  username: string;
  room?: string;
};

// Socket.IO客户端服务
class SocketIOService {
  private socket: Socket | null = null;
  private serverUrl: string;
  private connected: boolean = false;
  private listeners: Map<string, ((...args: any[]) => void)[]> = new Map();

  constructor(url: string = 'http://localhost:3000') {
    this.serverUrl = url;
  }

  // 连接到服务器
  connect(options: { autoReconnect?: boolean } = {}): Promise<boolean> {
    return new Promise((resolve, reject) => {
      try {
        // 如果已连接，先断开连接
        if (this.socket && this.connected) {
          this.disconnect();
        }

        // 创建新的Socket实例
        this.socket = io(this.serverUrl, {
          autoConnect: true,
          reconnection: options.autoReconnect !== false,
          transports: ['websocket', 'polling']
        });

        // 连接成功事件
        this.socket.on('connect', () => {
          console.log('Connected to Socket.IO server');
          this.connected = true;
          this.notifyListeners('connect');
          resolve(true);
        });

        // 连接错误事件
        this.socket.on('connect_error', (error) => {
          console.error('Socket.IO connection error:', error);
          this.notifyListeners('connect_error', error);
          reject(error);
        });

        // 断开连接事件
        this.socket.on('disconnect', (reason) => {
          console.log(`Disconnected from Socket.IO server: ${reason}`);
          this.connected = false;
          this.notifyListeners('disconnect', reason);
        });

        // 欢迎消息
        this.socket.on('welcome', (data) => {
          console.log('Received welcome message:', data);
          this.notifyListeners('welcome', data);
        });

        // 收到消息事件
        this.socket.on('message', (data: MessageData) => {
          console.log('Received message:', data);
          this.notifyListeners('message', data);
        });

        // 用户加入事件
        this.socket.on('userJoined', (data) => {
          console.log('User joined:', data);
          this.notifyListeners('userJoined', data);
        });

        // 用户列表事件
        this.socket.on('userList', (users: UserData[]) => {
          console.log('User list:', users);
          this.notifyListeners('userList', users);
        });

        // 用户名更改事件
        this.socket.on('usernameChanged', (data) => {
          console.log('Username changed:', data);
          this.notifyListeners('usernameChanged', data);
        });

        // 用户离开事件
        this.socket.on('userLeft', (data) => {
          console.log('User left:', data);
          this.notifyListeners('userLeft', data);
        });

        // 用户断开连接事件
        this.socket.on('userDisconnected', (data) => {
          console.log('User disconnected:', data);
          this.notifyListeners('userDisconnected', data);
        });

        // 加入房间事件
        this.socket.on('roomJoined', (data) => {
          console.log('Room joined:', data);
          this.notifyListeners('roomJoined', data);
        });

        // 离开房间事件
        this.socket.on('roomLeft', (data) => {
          console.log('Room left:', data);
          this.notifyListeners('roomLeft', data);
        });

        // 消息发送成功事件
        this.socket.on('messageSent', (data) => {
          console.log('Message sent:', data);
          this.notifyListeners('messageSent', data);
        });

        // 错误事件
        this.socket.on('error', (data) => {
          console.error('Socket error:', data);
          this.notifyListeners('error', data);
        });

      } catch (error) {
        console.error('Failed to initialize Socket.IO client:', error);
        reject(error);
      }
    });
  }

  // 断开连接
  disconnect(): void {
    if (this.socket) {
      this.socket.disconnect();
      this.connected = false;
    }
  }

  // 发送消息
  sendMessage(text: string, room?: string): void {
    if (!this.connected || !this.socket) {
      console.error('Not connected to Socket.IO server');
      return;
    }

    this.socket.emit('message', { text, room });
  }

  // 设置用户名
  setUsername(username: string): void {
    if (!this.connected || !this.socket) {
      console.error('Not connected to Socket.IO server');
      return;
    }

    this.socket.emit('setUsername', username);
  }

  // 获取用户列表
  getUsers(): void {
    if (!this.connected || !this.socket) {
      console.error('Not connected to Socket.IO server');
      return;
    }

    this.socket.emit('getUsers');
  }

  // 加入房间
  joinRoom(roomName: string): void {
    if (!this.connected || !this.socket) {
      console.error('Not connected to Socket.IO server');
      return;
    }

    this.socket.emit('joinRoom', roomName);
  }

  // 离开房间
  leaveRoom(roomName: string): void {
    if (!this.connected || !this.socket) {
      console.error('Not connected to Socket.IO server');
      return;
    }

    this.socket.emit('leaveRoom', roomName);
  }

  // 发送ping请求
  ping(): Promise<any> {
    return new Promise((resolve, reject) => {
      if (!this.connected || !this.socket) {
        reject(new Error('Not connected to Socket.IO server'));
        return;
      }

      this.socket.emit('ping', (response: any) => {
        resolve(response);
      });
    });
  }

  // 检查连接状态
  isConnected(): boolean {
    return this.connected;
  }

  // 获取Socket实例
  getSocket(): Socket | null {
    return this.socket;
  }

  // 添加事件监听器
  on(event: string, callback: (...args: any[]) => void): void {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, []);
    }
    this.listeners.get(event)?.push(callback);
  }

  // 移除事件监听器
  off(event: string, callback?: (...args: any[]) => void): void {
    if (!callback) {
      // 如果没有提供回调，则移除该事件的所有监听器
      this.listeners.delete(event);
    } else {
      // 移除特定回调
      const callbacks = this.listeners.get(event);
      if (callbacks) {
        const index = callbacks.indexOf(callback);
        if (index !== -1) {
          callbacks.splice(index, 1);
        }
        if (callbacks.length === 0) {
          this.listeners.delete(event);
        }
      }
    }
  }

  // 通知所有监听器
  private notifyListeners(event: string, ...args: any[]): void {
    const callbacks = this.listeners.get(event);
    if (callbacks) {
      callbacks.forEach(callback => {
        try {
          callback(...args);
        } catch (error) {
          console.error(`Error in ${event} listener:`, error);
        }
      });
    }
  }
}

// 创建单例实例
const socketService = new SocketIOService();

export default socketService; 