<template>
  <el-dialog v-model="dialogVisible" width="900px" :close-on-click-modal="false" :show-close="false"
    class="qq-music-player" append-to-body>
    <div class="music-container">
      <!-- 顶部导航 -->
      <div class="top-bar">
        <div class="app-brand">
          <div class="logo"></div>
          <h2 class="app-name">南瓜音乐</h2>
        </div>
        <div class="nav-links">
          <div class="nav-item active">音乐馆</div>
          <div class="nav-item">我的音乐</div>
          <div class="nav-item">客户端</div>
          <div class="nav-item">开放平台</div>
          <div class="nav-item">VIP</div>
        </div>
        <div class="search-bar">
          <el-input placeholder="搜索音乐、MV、歌单、用户" prefix-icon="Search">
            <template #suffix>
              <el-button class="search-btn">Q</el-button>
            </template>
          </el-input>
        </div>
        <div class="user-controls">
          <el-button class="login-btn">登录</el-button>
          <el-button class="vip-btn">开通VIP<i class="el-icon-arrow-down"></i></el-button>
        </div>
      </div>

      <!-- 二级导航 -->
      <div class="secondary-nav">
        <div class="nav-item active">首页</div>
        <div class="nav-item">歌手</div>
        <div class="nav-item">新碟</div>
        <div class="nav-item">排行榜</div>
        <div class="nav-item">分类歌单</div>
        <div class="nav-item">电台</div>
        <div class="nav-item">MV</div>
        <div class="nav-item">数字专辑</div>
      </div>

      <!-- 主体内容区 -->
      <div class="main-content">
        <!-- 歌单推荐区域 -->
        <div class="section-header">
          <h2 class="section-title">歌单推荐</h2>
        </div>

        <div class="playlist-grid">
          <div v-for="(playlist, index) in playlists" :key="index" class="playlist-item">
            <div class="playlist-cover">
              <img :src="playlist.coverImg" alt="歌单封面" />
              <div class="play-count">
                <el-icon>
                  <VideoPlay />
                </el-icon>
                <span>{{ playlist.playCount }}</span>
              </div>
            </div>
            <div class="playlist-info">
              <h3 class="playlist-title">{{ playlist.title }}</h3>
              <p class="playlist-desc">播放量: {{ playlist.playCount }}</p>
            </div>
          </div>
        </div>

        <!-- 新歌首发区域 -->
        <div class="section-header">
          <h2 class="section-title">新歌首发</h2>
          <div class="section-tabs">
            <div class="tab active">最新</div>
            <div class="tab">内地</div>
            <div class="tab">港台</div>
            <div class="tab">欧美</div>
            <div class="tab">韩国</div>
            <div class="tab">日本</div>
          </div>
          <div class="more-btn">
            <el-button class="btn-text">播放全部</el-button>
          </div>
        </div>

        <div class="new-songs-grid">
          <div v-for="(song, index) in newSongs" :key="index" class="song-item">
            <div class="song-cover">
              <img :src="song.coverImg" alt="歌曲封面" />
              <div class="play-button">
                <el-icon>
                  <VideoPlay />
                </el-icon>
              </div>
              <span class="song-duration">{{ formatTime(song.duration) }}</span>
            </div>
            <div class="song-info">
              <h3 class="song-title">{{ song.title }}</h3>
              <p class="song-artist">{{ song.artist }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 底部播放控制条 -->
      <div class="player-bar">
        <div class="now-playing">
          <img :src="currentSong.albumArt || defaultAlbumArt" alt="当前播放" class="current-song-cover" />
          <div class="song-info">
            <div class="song-title">{{ currentSong.title || '未选择歌曲' }}</div>
            <div class="song-artist">{{ currentSong.artist || '未知歌手' }}</div>
          </div>
        </div>

        <div class="playback-controls">
          <div class="control-buttons">
            <el-button class="control-btn">
              <el-icon>
                <Back />
              </el-icon>
            </el-button>
            <el-button class="play-btn" :class="{ 'is-playing': isPlaying }" @click="togglePlay">
              <el-icon v-if="isPlaying">
                <VideoPause />
              </el-icon>
              <el-icon v-else>
                <VideoPlay />
              </el-icon>
            </el-button>
            <el-button class="control-btn">
              <el-icon>
                <Right />
              </el-icon>
            </el-button>
          </div>
          <div class="progress-bar">
            <span class="time current">{{ formatTime(currentTime) }}</span>
            <el-slider v-model="progress" :max="duration" @change="handleSeek" />
            <span class="time total">{{ formatTime(duration) }}</span>
          </div>
        </div>

        <div class="extra-controls">
          <el-button class="extra-btn">
            <el-icon>
              <Star />
            </el-icon>
          </el-button>
          <el-button class="extra-btn">
            <el-icon>
              <Download />
            </el-icon>
          </el-button>
          <el-button class="extra-btn">
            <el-icon>
              <More />
            </el-icon>
          </el-button>
        </div>
      </div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
  VideoPlay, VideoPause, Back, Right, DCaret, Share, Star,
  Delete, Close, Minus, Headset, Download, More, Search
} from '@element-plus/icons-vue';

// props和事件
const props = defineProps({
  visible: { type: Boolean, default: false }
});

const emit = defineEmits(['update:visible']);

// 弹窗显示状态
const dialogVisible = ref(props.visible);

watch(() => props.visible, (newVal) => {
  dialogVisible.value = newVal;
});

// 基础状态
const defaultAlbumArt = 'https://music-file.y.qq.com/songlist/u/7io5NecPoKoA/1106c/b7ed2c4ec51d451b3d7c57b54acb3290b7c6a90b_4d634.jpg?imageView2/4/w/300/h/300';
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(269);

// 计算属性
const progress = computed({
  get: () => currentTime.value,
  set: (val) => { currentTime.value = val; }
});

// 当前歌曲信息
const currentSong = ref({
  id: 1,
  title: '起风了',
  artist: '买辣椒也用券',
  albumArt: defaultAlbumArt,
  duration: 269
});

// 歌单数据
const playlists = ref([
  {
    id: 1,
    title: '心情随身听，一键红心的热歌集',
    coverImg: defaultAlbumArt,
    playCount: '28.2万'
  },
  {
    id: 2,
    title: '每周精选 | AI新创榜',
    coverImg: defaultAlbumArt,
    playCount: '1.77万'
  },
  {
    id: 3,
    title: '纪念万大同: 我们耳机里特别的人',
    coverImg: defaultAlbumArt,
    playCount: '798.5万'
  },
  {
    id: 4,
    title: '华语唱哈今夏，做个酷girl',
    coverImg: defaultAlbumArt,
    playCount: '16.4万'
  },
  {
    id: 5,
    title: 'DNA动了！全网都在听的热歌',
    coverImg: defaultAlbumArt,
    playCount: '28.7万'
  }
]);

// 新歌数据
const newSongs = ref([
  {
    id: 1,
    title: '曼花 《谁水什么》 影视剧主题曲',
    artist: '周深',
    coverImg: defaultAlbumArt,
    duration: 208
  },
  {
    id: 2,
    title: '写未来的日记',
    artist: '林特嘉',
    coverImg: defaultAlbumArt,
    duration: 238
  },
  {
    id: 3,
    title: 'For You (写给你)',
    artist: 'TOP营地少年组合',
    coverImg: defaultAlbumArt,
    duration: 210
  }
]);

// 方法
const closePlayer = () => {
  emit('update:visible', false);
};

const togglePlay = () => {
  isPlaying.value = !isPlaying.value;
};

const handleSeek = (value: number) => {
  currentTime.value = value;
};

const formatTime = (seconds: number): string => {
  if (isNaN(seconds) || seconds < 0) return '0:00';
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
};

// 模拟播放进度更新(仅用于演示)
if (import.meta.env.DEV) {
  setInterval(() => {
    if (isPlaying.value) {
      currentTime.value += 1;
      if (currentTime.value >= duration.value) {
        currentTime.value = 0;
      }
    }
  }, 1000);
}
</script>

<style lang="scss" scoped>
.qq-music-player {
  :deep(.el-dialog) {
    border-radius: 0;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
  }

  :deep(.el-dialog__header),
  :deep(.el-dialog__body) {
    padding: 0;
    margin: 0;
  }
}

.music-container {
  color: #333;
  font-family: "PingFang SC", "Microsoft YaHei", Arial, sans-serif;
  height: 100%;
  display: flex;
  flex-direction: column;
}

/* 顶部导航栏 */
.top-bar {
  display: flex;
  align-items: center;
  padding: 0 20px;
  height: 60px;
  background-color: #fff;
  border-bottom: 1px solid #e9e9e9;

  .app-brand {
    display: flex;
    align-items: center;
    margin-right: 20px;

    .logo {
      width: 36px;
      height: 36px;
      background-color: #FF7518;
      border-radius: 50%;
      margin-right: 10px;
      position: relative;

      &::before {
        content: '';
        position: absolute;
        width: 12px;
        height: 12px;
        background-color: white;
        border-radius: 50%;
        top: 12px;
        left: 12px;
      }
    }

    .app-name {
      font-size: 22px;
      font-weight: 600;
      color: #000;
    }
  }

  .nav-links {
    display: flex;
    margin-right: 10px;

    .nav-item {
      padding: 0 12px;
      font-size: 14px;
      line-height: 60px;
      cursor: pointer;
      position: relative;

      &.active {
        color: #FF7518;
      }

      &:hover {
        color: #FF7518;
      }
    }
  }

  .search-bar {
    flex: 1;
    max-width: 320px;
    margin: 0 15px;

    :deep(.el-input__wrapper) {
      background-color: #f5f5f5;
      box-shadow: none;
      border-radius: 4px;
    }

    :deep(.el-input__inner) {
      height: 36px;
    }

    .search-btn {
      background-color: #FF7518;
      color: white;
      border: none;
      border-radius: 0 4px 4px 0;
      height: 36px;
      padding: 0 12px;
    }
  }

  .user-controls {
    display: flex;
    align-items: center;
    gap: 10px;

    .login-btn {
      background: transparent;
      border: none;
      color: #333;
      font-size: 14px;
    }

    .vip-btn {
      background-color: #FF7518;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      padding: 8px 15px;
    }
  }
}

/* 二级导航 */
.secondary-nav {
  display: flex;
  height: 50px;
  background-color: #fbfbfb;
  border-bottom: 1px solid #e9e9e9;
  padding: 0 20px;

  .nav-item {
    padding: 0 20px;
    font-size: 14px;
    line-height: 50px;
    cursor: pointer;
    position: relative;

    &.active {
      color: #FF7518;

      &::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 20px;
        right: 20px;
        height: 2px;
        background-color: #FF7518;
      }
    }

    &:hover {
      color: #FF7518;
    }
  }
}

/* 主体内容 */
.main-content {
  flex: 1;
  padding: 20px;
  background-color: #fbfbfb;
  overflow-y: auto;
}

/* 分区标题 */
.section-header {
  display: flex;
  align-items: center;
  margin-bottom: 20px;

  .section-title {
    font-size: 22px;
    font-weight: 600;
    color: #000;
    margin: 0;
    padding-right: 20px;
  }

  .section-tabs {
    display: flex;
    margin-left: auto;

    .tab {
      padding: 0 15px;
      font-size: 14px;
      cursor: pointer;
      color: #666;

      &.active {
        color: #FF7518;
        font-weight: 500;
      }

      &:hover {
        color: #FF7518;
      }
    }
  }

  .more-btn {
    margin-left: auto;

    .btn-text {
      color: #666;
      background: transparent;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      font-size: 13px;

      &:hover {
        color: #FF7518;
        border-color: #FF7518;
      }
    }
  }
}

/* 歌单网格 */
.playlist-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 20px;
  margin-bottom: 40px;

  .playlist-item {
    cursor: pointer;
    transition: transform 0.2s;

    &:hover {
      transform: translateY(-5px);
    }

    .playlist-cover {
      position: relative;
      width: 100%;
      padding-bottom: 100%;
      overflow: hidden;
      border-radius: 8px;
      margin-bottom: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);

      img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
      }

      &:hover img {
        transform: scale(1.05);
      }

      .play-count {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 4px;
      }
    }

    .playlist-info {
      .playlist-title {
        font-size: 14px;
        margin: 0 0 5px;
        line-height: 1.4;
        height: 40px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
      }

      .playlist-desc {
        font-size: 12px;
        color: #999;
        margin: 0;
      }
    }
  }
}

/* 新歌网格 */
.new-songs-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;

  .song-item {
    display: flex;
    background-color: #fff;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);

    &:hover {
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
      transform: translateY(-3px);
    }

    .song-cover {
      width: 80px;
      height: 80px;
      position: relative;
      margin-right: 10px;
      flex-shrink: 0;
      overflow: hidden;
      border-radius: 4px;

      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
      }

      &:hover img {
        transform: scale(1.1);
      }

      .play-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 32px;
        height: 32px;
        background-color: rgba(255, 117, 24, 0.8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transition: opacity 0.3s;
      }

      .song-duration {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        font-size: 12px;
        padding: 1px 5px;
        border-radius: 2px;
      }

      &:hover .play-button {
        opacity: 1;
      }
    }

    .song-info {
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-width: 0;

      .song-title {
        font-size: 14px;
        margin: 0 0 5px;
        font-weight: normal;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: color 0.2s;
      }

      .song-artist {
        font-size: 12px;
        color: #999;
        margin: 0;
      }
    }

    &:hover .song-title {
      color: #FF7518;
    }
  }
}

/* 底部播放控制条 */
.player-bar {
  height: 70px;
  display: flex;
  align-items: center;
  background-color: #fafafa;
  border-top: 1px solid #e9e9e9;
  padding: 0 20px;

  .now-playing {
    display: flex;
    align-items: center;
    width: 200px;

    .current-song-cover {
      width: 50px;
      height: 50px;
      border-radius: 4px;
      margin-right: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);

      &:hover {
        transform: scale(1.05);
      }
    }

    .song-info {
      min-width: 0;

      .song-title {
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 4px;
        transition: color 0.2s;

        &:hover {
          color: #FF7518;
        }
      }

      .song-artist {
        font-size: 12px;
        color: #999;
      }
    }
  }

  .playback-controls {
    flex: 1;
    padding: 0 20px;

    .control-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-bottom: 5px;

      .control-btn {
        background: transparent;
        border: none;
        color: #666;
        padding: 8px;
        font-size: 18px;
        transition: all 0.2s;
        border-radius: 50%;

        &:hover {
          color: #FF7518;
          background-color: rgba(255, 117, 24, 0.1);
        }
      }

      .play-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #FF7518;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(255, 117, 24, 0.3);

        &:hover {
          background-color: darken(#FF7518, 5%);
          transform: scale(1.05);
          box-shadow: 0 3px 8px rgba(255, 117, 24, 0.4);
          color: #fff;
        }
      }
    }

    .progress-bar {
      display: flex;
      align-items: center;
      gap: 10px;

      .time {
        font-size: 12px;
        color: #999;
        width: 35px;

        &.current {
          text-align: right;
        }

        &.total {
          text-align: left;
        }
      }

      .el-slider {
        flex: 1;

        :deep(.el-slider__runway) {
          height: 2px;
          margin: 0;
          background-color: #e0e0e0;
        }

        :deep(.el-slider__bar) {
          height: 2px;
          background-color: #FF7518;
        }

        :deep(.el-slider__button) {
          width: 10px;
          height: 10px;
          border: 2px solid #FF7518;
          background-color: #fff;
          transition: transform 0.2s;

          &:hover {
            transform: scale(1.2);
          }
        }
      }
    }
  }

  .extra-controls {
    display: flex;
    gap: 10px;
    margin-left: auto;

    .extra-btn {
      background: transparent;
      border: none;
      color: #666;
      padding: 8px;
      border-radius: 50%;
      transition: all 0.2s;

      &:hover {
        color: #FF7518;
        background-color: rgba(255, 117, 24, 0.1);
      }
    }
  }
}
</style>
