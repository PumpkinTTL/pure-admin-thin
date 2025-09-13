<template>
  <div class="socket-chat-container">
    <div class="chat-header">
      <div class="title">
        <h3>实时聊天</h3>
        <div v-if="isConnected" class="status connected">已连接</div>
        <div v-else class="status disconnected">未连接</div>
      </div>
      <div class="user-info">
        <el-input v-model="username" placeholder="设置用户名" size="small" :disabled="isConnected">
          <template #append>
            <el-button @click="setUsername" :disabled="!username.trim() || isConnected">
              确定
            </el-button>
          </template>
        </el-input>
      </div>
    </div>

    <div class="chat-rooms">
      <div class="rooms-header">
        <span>房间列表</span>
        <el-button size="small" @click="showCreateRoomDialog = true">创建房间</el-button>
      </div>
      <div class="room-list">
        <div v-for="room in rooms" :key="room" class="room-item" :class="{ active: currentRoom === room }"
          @click="joinRoom(room)">
          {{ room }}
        </div>
      </div>
    </div>

    <div class="chat-content">
      <div class="message-list" ref="messageListRef">
        <div v-if="messages.length === 0" class="no-messages">
          暂无消息，开始聊天吧！
        </div>
        <div v-for="msg in messages" :key="msg.id" class="message-item"
          :class="{ 'self-message': msg.sender.id === socketId }">
          <div class="message-header">
            <span class="username">{{ msg.sender.username }}</span>
            <span class="time">{{ formatTime(msg.timestamp) }}</span>
          </div>
          <div class="message-body">{{ msg.text }}</div>
        </div>
      </div>

      <div class="input-area">
        <el-input v-model="messageText" type="textarea" :rows="2" placeholder="输入消息..."
          @keyup.enter.exact.native="sendMessage" :disabled="!isConnected" />
        <div class="action-buttons">
          <el-button type="primary" @click="sendMessage" :disabled="!isConnected || !messageText.trim()">
            发送
          </el-button>
        </div>
      </div>
    </div>

    <div class="users-panel">
      <div class="panel-header">在线用户 ({{ users.length }})</div>
      <div class="user-list">
        <div v-for="user in users" :key="user.id" class="user-item" :class="{ 'current-user': user.id === socketId }">
          <span class="user-avatar">
            {{ user.username.charAt(0).toUpperCase() }}
          </span>
          <span class="user-name">{{ user.username }}</span>
          <span v-if="user.id === socketId" class="self-tag">我</span>
        </div>
      </div>
    </div>

    <el-dialog v-model="showCreateRoomDialog" title="创建/加入房间" width="30%">
      <el-input v-model="newRoomName" placeholder="输入房间名称" />
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showCreateRoomDialog = false">取消</el-button>
          <el-button type="primary" @click="createRoom" :disabled="!newRoomName.trim()">
            确定
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { socketService } from '@/socketio';
import { ElMessage } from 'element-plus';

// 响应式状态
const isConnected = ref(false);
const messages = ref<any[]>([]);
const messageText = ref('');
const username = ref('');
const socketId = ref('');
const users = ref<any[]>([]);
const showCreateRoomDialog = ref(false);
const newRoomName = ref('');
const currentRoom = ref('');
const rooms = ref(['general', 'tech', 'random']); // 默认房间
const messageListRef = ref<HTMLElement | null>(null);

// 处理连接成功
const handleConnect = () => {
  isConnected.value = true;
  ElMessage.success('已连接到聊天服务器');
  socketService.getUsers(); // 获取在线用户列表
};

// 处理连接错误
const handleConnectError = (error: any) => {
  console.error('连接错误:', error);
  isConnected.value = false;
  ElMessage.error('连接失败，请检查网络或稍后重试');
};

// 处理断开连接
const handleDisconnect = (reason: string) => {
  isConnected.value = false;
  ElMessage.warning(`已断开连接: ${reason}`);
};

// 处理欢迎消息
const handleWelcome = (data: any) => {
  socketId.value = data.id;
  ElMessage.info(`欢迎！您的ID是：${data.id}`);

  // 如果未设置用户名，自动分配临时用户名
  if (!username.value) {
    username.value = `User-${Math.floor(Math.random() * 1000)}`;
    socketService.setUsername(username.value);
  }

  // 默认加入general房间
  joinRoom('general');
};

// 处理收到的消息
const handleMessage = (data: any) => {
  messages.value.push(data);
  scrollToBottom();
};

// 处理用户列表
const handleUserList = (data: any[]) => {
  users.value = data;
};

// 处理用户加入
const handleUserJoined = (data: any) => {
  ElMessage.info(`${data.username} 已加入${data.room ? ' ' + data.room + ' 房间' : ''}`);
  socketService.getUsers(); // 刷新用户列表
};

// 处理用户离开
const handleUserLeft = (data: any) => {
  ElMessage.info(`${data.username} 已离开${data.room ? ' ' + data.room + ' 房间' : ''}`);
  socketService.getUsers(); // 刷新用户列表
};

// 处理用户断开连接
const handleUserDisconnected = (data: any) => {
  ElMessage.info(`${data.username} 已断开连接`);
  socketService.getUsers(); // 刷新用户列表
};

// 处理加入房间
const handleRoomJoined = (data: any) => {
  currentRoom.value = data.room;
  messages.value = []; // 清空消息列表
  ElMessage.success(`已加入 ${data.room} 房间`);
};

// 处理用户名变更
const handleUsernameChanged = (data: any) => {
  if (data.userId === socketId.value) {
    username.value = data.newUsername;
  }
  ElMessage.info(`用户 ${data.oldUsername} 已更名为 ${data.newUsername}`);
  socketService.getUsers(); // 刷新用户列表
};

// 设置用户名
const setUsername = () => {
  if (!username.value.trim()) return;
  socketService.setUsername(username.value);
};

// 发送消息
const sendMessage = () => {
  if (!messageText.value.trim() || !isConnected.value) return;

  socketService.sendMessage(messageText.value, currentRoom.value);

  // 添加自己的消息到列表中
  messages.value.push({
    id: Date.now().toString(),
    text: messageText.value,
    sender: {
      id: socketId.value,
      username: username.value
    },
    timestamp: new Date()
  });

  messageText.value = '';
  scrollToBottom();
};

// 加入房间
const joinRoom = (roomName: string) => {
  if (!isConnected.value) return;
  socketService.joinRoom(roomName);
};

// 创建/加入新房间
const createRoom = () => {
  if (!newRoomName.value.trim() || !isConnected.value) return;

  if (!rooms.value.includes(newRoomName.value)) {
    rooms.value.push(newRoomName.value);
  }

  joinRoom(newRoomName.value);
  showCreateRoomDialog.value = false;
  newRoomName.value = '';
};

// 消息窗口滚动到底部
const scrollToBottom = () => {
  nextTick(() => {
    if (messageListRef.value) {
      messageListRef.value.scrollTop = messageListRef.value.scrollHeight;
    }
  });
};

// 格式化时间
const formatTime = (timestamp: string | Date) => {
  const date = new Date(timestamp);
  return date.toLocaleTimeString();
};

// 页面挂载时
onMounted(() => {
  // 添加事件监听
  socketService.on('connect', handleConnect);
  socketService.on('connect_error', handleConnectError);
  socketService.on('disconnect', handleDisconnect);
  socketService.on('welcome', handleWelcome);
  socketService.on('message', handleMessage);
  socketService.on('userList', handleUserList);
  socketService.on('userJoined', handleUserJoined);
  socketService.on('userLeft', handleUserLeft);
  socketService.on('userDisconnected', handleUserDisconnected);
  socketService.on('roomJoined', handleRoomJoined);
  socketService.on('usernameChanged', handleUsernameChanged);

  // 连接到服务器
  socketService.connect().catch(error => {
    console.error('连接失败:', error);
  });
});

// 页面卸载时
onUnmounted(() => {
  // 移除事件监听
  socketService.off('connect', handleConnect);
  socketService.off('connect_error', handleConnectError);
  socketService.off('disconnect', handleDisconnect);
  socketService.off('welcome', handleWelcome);
  socketService.off('message', handleMessage);
  socketService.off('userList', handleUserList);
  socketService.off('userJoined', handleUserJoined);
  socketService.off('userLeft', handleUserLeft);
  socketService.off('userDisconnected', handleUserDisconnected);
  socketService.off('roomJoined', handleRoomJoined);
  socketService.off('usernameChanged', handleUsernameChanged);

  // 断开连接
  socketService.disconnect();
});

// 监听消息变化，自动滚动到底部
watch(messages, () => {
  scrollToBottom();
});
</script>

<style scoped>
.socket-chat-container {
  display: grid;
  grid-template-columns: 200px 1fr 200px;
  grid-template-rows: 60px 1fr;
  grid-template-areas:
    "header header header"
    "rooms content users";
  height: 600px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
  background-color: #f9f9f9;
}

.chat-header {
  grid-area: header;
  background-color: #4a5568;
  color: white;
  padding: 10px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e0e0e0;
}

.title {
  display: flex;
  align-items: center;
}

.title h3 {
  margin: 0;
  margin-right: 10px;
}

.status {
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
}

.connected {
  background-color: #48bb78;
}

.disconnected {
  background-color: #f56565;
}

.user-info {
  width: 250px;
}

.chat-rooms {
  grid-area: rooms;
  padding: 10px;
  border-right: 1px solid #e0e0e0;
  background-color: #edf2f7;
}

.rooms-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 10px;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 10px;
  font-weight: bold;
}

.room-list {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.room-item {
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.room-item:hover {
  background-color: #e2e8f0;
}

.room-item.active {
  background-color: #bee3f8;
  font-weight: bold;
}

.chat-content {
  grid-area: content;
  display: flex;
  flex-direction: column;
  background-color: white;
}

.message-list {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.no-messages {
  color: #a0aec0;
  text-align: center;
  margin-top: 40px;
}

.message-item {
  max-width: 80%;
  padding: 8px 12px;
  border-radius: 12px;
  background-color: #edf2f7;
  align-self: flex-start;
}

.self-message {
  background-color: #bee3f8;
  align-self: flex-end;
}

.message-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 5px;
  font-size: 12px;
}

.username {
  font-weight: bold;
  color: #4a5568;
}

.time {
  color: #718096;
}

.message-body {
  word-break: break-word;
}

.input-area {
  padding: 10px;
  border-top: 1px solid #e0e0e0;
  background-color: #f7fafc;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
}

.users-panel {
  grid-area: users;
  border-left: 1px solid #e0e0e0;
  background-color: #edf2f7;
}

.panel-header {
  padding: 10px;
  border-bottom: 1px solid #e0e0e0;
  font-weight: bold;
  background-color: #e2e8f0;
}

.user-list {
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  overflow-y: auto;
}

.user-item {
  display: flex;
  align-items: center;
  padding: 5px;
  border-radius: 4px;
}

.user-item:hover {
  background-color: #e2e8f0;
}

.current-user {
  background-color: #e2e8f0;
}

.user-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background-color: #4a5568;
  color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-right: 10px;
  font-weight: bold;
}

.self-tag {
  margin-left: auto;
  font-size: 12px;
  background-color: #4a5568;
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
}

@media (max-width: 768px) {
  .socket-chat-container {
    grid-template-columns: 1fr;
    grid-template-rows: 60px auto 1fr auto;
    grid-template-areas:
      "header"
      "rooms"
      "content"
      "users";
    height: 100vh;
  }

  .chat-rooms,
  .users-panel {
    max-height: 200px;
    overflow-y: auto;
  }
}
</style>