<template>
  <div class="notice-container">
    <el-card class="box-card" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="header-title">
            <font-awesome-icon :icon="['fas', 'bullhorn']" class="mr-2" />公告管理
          </h3>
        </div>
      </template>

      <!-- 搜索区域 -->
      <el-form :model="searchForm" label-width="80px" class="search-form">
        <div class="search-area">
          <div class="search-items">
            <el-row :gutter="16">
              <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                <el-form-item label="公告标题">
                  <el-input v-model="searchForm.title" placeholder="请输入标题" clearable :prefix-icon="Search"
                    size="default" />
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                <el-form-item label="公告类型">
                  <el-select v-model="searchForm.category_type" placeholder="选择类型" clearable style="width: 100%"
                    size="default">
                    <el-option v-for="item in typeOptions" :key="item.id" :label="item.name" :value="item.id">
                      <div class="option-with-icon">
                        <font-awesome-icon :icon="['fas', item.icon]" :style="{ color: item.color }" class="mr-1" />
                        <span>{{ item.name }}</span>
                      </div>
                    </el-option>
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                <el-form-item label="状态">
                  <el-select v-model="searchForm.status" placeholder="选择状态" clearable style="width: 100%"
                    size="default">
                    <el-option v-for="item in statusOptions" :key="item.id" :label="item.name" :value="item.id">
                      <div class="option-with-color">
                        <div class="color-dot" :style="{ backgroundColor: item.color }"></div>
                        <span>{{ item.name }}</span>
                      </div>
                    </el-option>
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                <el-form-item label="优先级">
                  <el-select v-model="searchForm.priority" placeholder="选择优先级" clearable style="width: 100%"
                    size="default">
                    <el-option v-for="item in priorityOptions" :key="item.id" :label="item.name" :value="item.id">
                      <div class="option-with-color">
                        <div class="color-dot" :style="{ backgroundColor: item.color }"></div>
                        <span>{{ item.name }}</span>
                      </div>
                    </el-option>
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                <el-form-item label="接收对象">
                  <el-select v-model="searchForm.notice_type" placeholder="选择对象" clearable style="width: 100%"
                    size="default">
                    <el-option :value="1" label="全部用户">
                      <div class="option-with-icon">
                        <font-awesome-icon :icon="['fas', 'users']" class="mr-1" />
                        <span>全部用户</span>
                      </div>
                    </el-option>
                    <el-option :value="2" label="特定用户">
                      <div class="option-with-icon">
                        <font-awesome-icon :icon="['fas', 'user']" class="mr-1" />
                        <span>特定用户</span>
                      </div>
                    </el-option>
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="12" :sm="8" :md="5" :lg="4" :xl="3">
                <el-form-item label="是否置顶">
                  <el-select v-model="searchForm.is_top" placeholder="是否置顶" clearable style="width: 100%"
                    size="default">
                    <el-option label="已置顶" :value="1" />
                    <el-option label="未置顶" :value="0" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="12" :sm="4" :md="3" :lg="2" :xl="1" class="search-btn-col">
                <el-button type="primary" @click="handleSearch" size="default" class="search-btn">
                  <font-awesome-icon :icon="['fas', 'search']" class="mr-1" />
                  搜索
                </el-button>
              </el-col>
              <el-col :xs="12" :sm="4" :md="3" :lg="2" :xl="1" class="search-btn-col">
                <el-button type="default" @click="resetSearchForm" size="default" class="search-btn">
                  <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />
                  重置
                </el-button>
              </el-col>
            </el-row>
          </div>
        </div>
      </el-form>

      <!-- 操作按钮区域 -->
      <div class="action-toolbar">
        <div class="left-actions">
          <el-button size="small" type="primary" @click="handleAddNotice" class="btn-with-icon">
            <font-awesome-icon :icon="['fas', 'plus']" class="mr-1" />
            新增公告
          </el-button>
          <el-button size="small" type="danger" :disabled="selectedNotices.length === 0" @click="handleBatchDelete"
            class="btn-with-icon">
            <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" />
            批量删除
          </el-button>
        </div>
        <div class="right-actions">
          <el-tooltip content="刷新数据" placement="top" :show-after="500">
            <el-button :size="buttonSize" type="info" plain @click="fetchNoticeList" class="btn-with-icon action-btn">
              <font-awesome-icon :icon="['fas', 'sync']" />
            </el-button>
          </el-tooltip>
          <el-dropdown trigger="click">
            <el-button :size="buttonSize" type="primary" plain class="btn-with-icon action-btn">
              <font-awesome-icon :icon="['fas', 'ellipsis-v']" />
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item>
                  <font-awesome-icon :icon="['fas', 'print']" class="mr-1" />打印
                </el-dropdown-item>
                <el-dropdown-item>
                  <font-awesome-icon :icon="['fas', 'file-export']" class="mr-1" />导出
                </el-dropdown-item>
                <el-dropdown-item divided>
                  <font-awesome-icon :icon="['fas', 'cog']" class="mr-1" />设置
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>

      <!-- 公告列表 -->
      <div class="table-container">
        <el-table v-loading="tableLoading" border :data="noticeData" size="small"
          @selection-change="handleSelectionChange"
          :header-cell-style="{ backgroundColor: '#f5f7fa', color: '#606266', fontWeight: 'normal' }"
          :cell-style="{ padding: '4px 0' }" stripe>
          <el-table-column type="selection" align="center" width="35" />
          <el-table-column fixed="left" label="ID" prop="id" align="center" width="50" />

          <!-- 公告标题 -->
          <el-table-column label="公告标题" prop="title" min-width="150" fixed="left">
            <template #default="{ row }">
              <div class="notice-title" :class="{ 'is-pinned': row.is_pinned }">
                <font-awesome-icon v-if="row.is_pinned" :icon="['fas', 'thumbtack']" class="pin-icon" />
                <el-tooltip :content="row.title" placement="top" :show-after="500">
                  <span class="notice-title-text">{{ row.title }}</span>
                </el-tooltip>
                <div class="meta-item" v-if="row.attachment">
                  <font-awesome-icon :icon="['fas', 'paperclip']" />
                </div>
              </div>
            </template>
          </el-table-column>

          <!-- 公告内容 -->
          <el-table-column label="公告内容" prop="content" min-width="200">
            <template #default="{ row }">
              <div class="notice-content">
                <el-tooltip :content="row.content" placement="top" :show-after="500">
                  <span>{{ row.content.substring(0, 60) + (row.content.length > 60 ? '...' : '') }}</span>
                </el-tooltip>
              </div>
            </template>
          </el-table-column>

          <!-- 浏览次数 改为显示删除状态 -->
          <el-table-column label="状态" align="center" width="90">
            <template #default="{ row }">
              <div class="status-tag" :class="row.delete_time ? 'status-deleted' : 'status-active'">
                <font-awesome-icon :icon="row.delete_time ? ['fas', 'ban'] : ['fas', 'check-circle']" class="mr-1" />
                {{ row.delete_time ? '已删除' : '正常' }}
              </div>
            </template>
          </el-table-column>

          <!-- 公告类型 -->
          <el-table-column label="类型" align="center" width="90">
            <template #default="{ row }">
              <div class="notice-type">
                <el-tag size="small" :style="{ backgroundColor: row.type.color, borderColor: row.type.color }"
                  effect="dark">
                  <font-awesome-icon :icon="['fas', row.type.icon]" class="mr-1" />
                  {{ row.type.name }}
                </el-tag>
              </div>
            </template>
          </el-table-column>

          <!-- 优先级 -->
          <el-table-column label="优先级" align="center" width="70">
            <template #default="{ row }">
              <div class="priority-badge" :style="{ color: row.priority.color, borderColor: row.priority.color }">
                {{ row.priority.name }}
              </div>
            </template>
          </el-table-column>

          <!-- 接收范围 -->
          <el-table-column label="接收对象" align="center" width="120">
            <template #default="{ row }">
              <div class="scope-info">
                <el-tag v-if="row.scope_type_id === 1" size="small" type="info" effect="plain">
                  <font-awesome-icon :icon="['fas', 'users']" class="mr-1" />全部用户
                </el-tag>

                <!-- 特定用户 - 单个用户时直接显示 -->
                <template v-else-if="row.scope_type_id === 2">
                  <!-- 只有一个接收者时直接显示用户名 -->
                  <el-tag v-if="row.receivers && row.receivers.length === 1" size="small" type="success" effect="plain">
                    <font-awesome-icon :icon="['fas', 'user']" class="mr-1" />
                    {{ row.receivers[0].username }}
                    <span class="status-dot" :class="row.receivers[0].read_status ? 'read' : 'unread'"></span>
                  </el-tag>

                  <!-- 无接收者时显示空标签 -->
                  <el-tag v-else-if="!row.receivers || row.receivers.length === 0" size="small" type="warning"
                    effect="plain">
                    <font-awesome-icon :icon="['fas', 'user-slash']" class="mr-1" />无接收者
                  </el-tag>

                  <!-- 多个接收者时使用弹出菜单 -->
                  <el-popover v-else placement="top" :width="350" trigger="hover">
                    <template #reference>
                      <el-tag size="small" type="warning" effect="plain" class="receiver-tag">
                        <font-awesome-icon :icon="['fas', 'users']" class="mr-1" />{{ row.receivers.length }}位用户
                      </el-tag>
                    </template>
                    <div class="receivers-list">
                      <div class="receivers-header">
                        <font-awesome-icon :icon="['fas', 'users']" class="mr-1" />
                        接收用户列表 ({{ row.receivers.length }}人)
                      </div>
                      <el-scrollbar max-height="200px" class="receivers-scrollbar">
                        <div class="receivers-table">
                          <div class="table-header">
                            <span class="col-name">用户名</span>
                            <span class="col-status">状态</span>
                          </div>
                          <div class="table-body">
                            <div v-for="receiver in row.receivers" :key="receiver.id" class="table-row">
                              <span class="col-name">{{ receiver.username }}</span>
                              <span class="col-status">
                                <el-tag :type="receiver.read_status ? 'success' : 'info'" size="small" effect="light">
                                  {{ receiver.read_status ? '已读' : '未读' }}
                                </el-tag>
                              </span>
                            </div>
                          </div>
                        </div>
                      </el-scrollbar>
                    </div>
                  </el-popover>
                </template>
              </div>
            </template>
          </el-table-column>

          <!-- 发布者 -->
          <el-table-column label="发布者" align="center" width="80">
            <template #default="{ row }">
              <div class="publisher-info">
                <el-tag size="small" effect="plain" type="info">
                  {{ row.publisher.username }}
                </el-tag>
              </div>
            </template>
          </el-table-column>

          <!-- 发布时间 -->
          <el-table-column label="发布时间" align="center" min-width="120">
            <template #default="{ row }">
              <div class="time-info">
                <div v-if="row.publish_time">
                  <font-awesome-icon :icon="['fas', 'clock']" class="mr-1" />
                  {{ formatDateTime(row.publish_time) }}
                </div>
                <div v-else class="text-gray-400">
                  <font-awesome-icon :icon="['fas', 'hourglass-half']" class="mr-1" />
                  未发布
                </div>
              </div>
            </template>
          </el-table-column>

          <!-- 操作按钮 -->
          <el-table-column fixed="right" label="操作" align="center" width="180">
            <template #default="{ row }">
              <div class="operation-btns">
                <el-button type="primary" :size="buttonSize" text @click="handleViewNotice(row)">
                  查看
                </el-button>
                <el-button type="primary" :size="buttonSize" text @click="handleEditNotice(row)"
                  v-if="!row.delete_time">
                  编辑
                </el-button>
                <el-button type="primary" :size="buttonSize" text @click="handleRestoreNotice(row)"
                  v-if="row.delete_time">
                  <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />恢复
                </el-button>

                <el-dropdown trigger="click" class="action-dropdown" v-if="!row.delete_time">
                  <el-button type="primary" :size="buttonSize" text>
                    更多<font-awesome-icon :icon="['fas', 'chevron-down']" class="ml-1" />
                  </el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item v-if="row.status.id === 0" @click="handlePublishNotice(row)">
                        <font-awesome-icon :icon="['fas', 'paper-plane']" class="mr-1" />发布
                      </el-dropdown-item>
                      <el-dropdown-item v-if="row.status.id === 1" @click="handleRevokeNotice(row)">
                        <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />撤回
                      </el-dropdown-item>
                      <el-dropdown-item @click="handleTogglePin(row)">
                        <font-awesome-icon :icon="['fas', row.is_pinned ? 'thumbtack' : 'thumbtack']" class="mr-1" />
                        {{ row.is_pinned ? '取消置顶' : '置顶' }}
                      </el-dropdown-item>
                      <el-dropdown-item divided @click="handleDeleteNotice(row)">
                        <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" />删除
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :page-sizes="[5, 10, 20, 50]"
          :default-page-size="5" layout="total, sizes, prev, pager, next" :total="total" @size-change="handleSizeChange"
          @current-change="handleCurrentChange" background small />
      </div>
    </el-card>

    <!-- 弹窗占位，稍后添加 -->
    <el-dialog v-model="noticeDialogVisible" :title="dialogTitle" width="65%" :close-on-click-modal="false"
      destroy-on-close>
      <el-form :model="noticeForm" ref="noticeFormRef" :rules="noticeRules" label-width="80px" label-position="right">
        <el-form-item label="标题" prop="title">
          <el-input v-model="noticeForm.title" placeholder="请输入公告标题" maxlength="100" show-word-limit />
        </el-form-item>

        <el-form-item label="内容" prop="content">
          <el-input v-model="noticeForm.content" type="textarea" :rows="6" placeholder="请输入公告内容" maxlength="5000"
            show-word-limit />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="公告类型" prop="notice_type">
              <el-select v-model="noticeForm.notice_type" placeholder="请选择公告类型" style="width: 100%">
                <el-option v-for="(value, key) in NOTICE_TYPES" :key="key" :label="getScopeTypeName(value)"
                  :value="value" />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="分类" prop="category_type">
              <el-select v-model="noticeForm.category_type" placeholder="请选择公告分类" style="width: 100%">
                <el-option v-for="item in typeOptions" :key="item.id" :label="item.name" :value="item.id">
                  <div class="option-with-icon">
                    <font-awesome-icon :icon="['fas', item.icon]" :style="{ color: item.color }" class="mr-1" />
                    <span>{{ item.name }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="优先级" prop="priority">
              <el-select v-model="noticeForm.priority" placeholder="请选择优先级" style="width: 100%">
                <el-option v-for="item in priorityOptions" :key="item.id" :label="item.name" :value="item.id">
                  <div class="option-with-color">
                    <div class="color-dot" :style="{ backgroundColor: item.color }"></div>
                    <span>{{ item.name }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="状态" prop="status">
              <el-select v-model="noticeForm.status" placeholder="请选择状态" style="width: 100%">
                <el-option v-for="item in statusOptions" :key="item.id" :label="item.name" :value="item.id">
                  <div class="option-with-color">
                    <div class="color-dot" :style="{ backgroundColor: item.color }"></div>
                    <span>{{ item.name }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="发布时间">
              <el-date-picker v-model="noticeForm.publish_time" type="datetime" placeholder="选择发布时间"
                format="YYYY-MM-DD HH:mm:ss" value-format="YYYY-MM-DD HH:mm:ss" style="width: 100%" />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="过期时间">
              <el-date-picker v-model="noticeForm.expire_time" type="datetime" placeholder="选择过期时间"
                format="YYYY-MM-DD HH:mm:ss" value-format="YYYY-MM-DD HH:mm:ss" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item>
          <el-switch v-model="noticeForm.is_top" active-text="置顶" inactive-text="不置顶" />
        </el-form-item>

        <el-form-item v-if="noticeForm.notice_type === NOTICE_TYPES.PARTIAL" label="接收用户" prop="target_uids">
          <el-select v-model="noticeForm.target_uids" multiple filterable remote reserve-keyword
            placeholder="请输入ID或用户名搜索" :remote-method="remoteSearchUsers" :loading="userSelectLoading"
            style="width: 100%" clearable @focus="handleFocus">
            <el-option v-for="item in userOptions" :key="item.id" :label="item.username" :value="item.id">
              <div class="user-option">
                <el-avatar :size="24" :src="item.avatar">{{ item.username?.charAt(0) }}</el-avatar>
                <div class="user-info">
                  <div class="username">{{ item.username }}</div>
                  <div class="user-detail">
                    <span class="user-id">ID: {{ item.id }}</span>
                    <span v-if="item.roles && item.roles.length > 0" class="user-role">{{ item.roles[0].name }}</span>
                  </div>
                </div>
              </div>
            </el-option>
          </el-select>
        </el-form-item>

        <el-form-item v-if="noticeForm.notice_type === NOTICE_TYPES.PERSONAL" label="接收用户" prop="target_uid">
          <el-select v-model="noticeForm.target_uid" filterable remote reserve-keyword placeholder="请输入ID或用户名搜索"
            :remote-method="remoteSearchUsers" :loading="userSelectLoading" style="width: 100%" clearable
            @focus="handleFocus">
            <el-option v-for="item in userOptions" :key="item.id" :label="item.username" :value="item.id">
              <div class="user-option">
                <el-avatar :size="24" :src="item.avatar">{{ item.username?.charAt(0) }}</el-avatar>
                <div class="user-info">
                  <div class="username">{{ item.username }}</div>
                  <div class="user-detail">
                    <span class="user-id">ID: {{ item.id }}</span>
                    <span v-if="item.roles && item.roles.length > 0" class="user-role">{{ item.roles[0].name }}</span>
                  </div>
                </div>
              </div>
            </el-option>
          </el-select>
        </el-form-item>

        <el-divider content-position="left">
          <span class="divider-title">
            <font-awesome-icon :icon="['fas', 'envelope']" class="divider-icon" />
            邮件通知设置
          </span>
        </el-divider>
        <div class="email-settings-container">
          <el-form-item>
            <el-switch v-model="emailSettings.enabled" active-text="开启邮件通知" inactive-text="不发送邮件" />
          </el-form-item>

          <template v-if="emailSettings.enabled">
            <el-form-item label="邮件标题">
              <el-input v-model="emailSettings.title" placeholder="邮件标题，留空则使用通知标题"></el-input>
            </el-form-item>

            <el-form-item label="邮件内容">
              <el-input v-model="emailSettings.content" type="textarea" :rows="5" placeholder="邮件内容，留空则使用通知内容"
                maxlength="2000" show-word-limit />
            </el-form-item>

            <el-form-item>
              <div class="ai-generator">
                <div class="ai-content">
                  <div class="ai-input-container">
                    <div class="ai-input-wrapper">
                      <el-input v-model="aiPrompt" placeholder="描述您需要的邮件内容，例如：'公司政策更新通知'" :disabled="aiGenerating"
                        class="ai-prompt-input">
                        <template #prefix>
                          <font-awesome-icon :icon="['fas', 'lightbulb']" class="input-icon" />
                        </template>
                      </el-input>
                    </div>

                    <el-button @click="generateEmailContent" :loading="aiGenerating" class="ai-generate-btn">
                      <font-awesome-icon :icon="['fas', 'magic']" class="btn-icon" />
                      AI生成
                    </el-button>
                  </div>

                  <div v-if="aiGenerating" class="ai-generating">
                    <div class="ai-generating-indicator">
                      <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                      </div>
                      <div class="ai-status">智能创作中</div>
                    </div>
                    <el-progress :percentage="aiProgress" :indeterminate="true" class="ai-progress" />
                  </div>
                </div>
              </div>
            </el-form-item>
          </template>
        </div>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="noticeDialogVisible = false">取消</el-button>
          <el-button type="primary" @click="submitNoticeForm" :loading="submitting">确认</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch, h } from 'vue';
import { Search, Delete, Edit, View, Plus, Refresh, Loading, MagicStick } from '@element-plus/icons-vue';
import { ElMessageBox, ElMessage } from 'element-plus';
import { message } from "@/utils/message";
import dayjs from 'dayjs';
import {
  getNoticeList,
  deleteNotice,
  batchDeleteNotice,
  publishNotice,
  revokeNotice,
  setNoticeTopStatus,
  createNotice,
  updateNotice,
  restoreNotice,
  NOTICE_TYPES,
  CATEGORY_TYPES,
  NOTICE_STATUS,
  PRIORITY_LEVELS,
  NoticeParams,
  NoticeData,
  NoticeCreateData
} from "@/api/notice";
import { getUserList } from "@/api/user";
import debounce from 'lodash/debounce';

// 定义API响应类型
interface ApiResponse<T = any> {
  code: number;
  message: string;
  data: T;
}

// 模拟用户数据 - 实际项目中应从API获取
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

// 用户选择相关变量
const userSelectLoading = ref(false);
const userOptions = ref<any[]>([]);
const userSearchKeyword = ref('');

// 邮件通知相关变量
const emailSettings = reactive({
  enabled: false,
  title: '',
  content: ''
});

// AI生成相关变量
const aiPrompt = ref('');
const aiGenerating = ref(false);
const aiProgress = ref(0);

// 远程搜索用户函数
const remoteSearchUsers = debounce(async (query) => {
  // 如果有查询内容，才进行搜索
  if (query && query.trim() !== '') {
    userSelectLoading.value = true;
    try {
      // 根据查询内容决定搜索条件
      const searchParams: any = {};

      // 判断是否为数字ID查询
      if (/^\d+$/.test(query)) {
        searchParams.id = query;
      } else {
        searchParams.username = query;
      }

      const res = await getUserList({
        ...searchParams,
        page_size: 10 // 限制显示10条数据
      });

      if (res.code === 200 && res.data && res.data.list) {
        userOptions.value = res.data.list;
      } else {
        console.error('获取用户列表失败:', res.msg);
      }
    } catch (error) {
      console.error('搜索用户时出错:', error);
    } finally {
      userSelectLoading.value = false;
    }
  }
}, 300);

// 当下拉框获得焦点但没有数据时加载默认数据
const handleFocus = () => {
  // 只有当userOptions为空且未加载过数据时才加载默认数据
  if (userOptions.value.length === 0 && !userDataLoaded.value) {
    loadDefaultUserOptions();
  }
};

// 添加标记是否已加载过用户数据
const userDataLoaded = ref(false);

// 生成邮件内容函数
const generateEmailContent = async () => {
  if (!aiPrompt.value.trim()) {
    message('请先输入AI提示词', { type: 'warning' });
    return;
  }

  aiGenerating.value = true;
  aiProgress.value = 0;

  try {
    // 模拟AI生成进度
    const interval = setInterval(() => {
      aiProgress.value += Math.floor(Math.random() * 15);
      if (aiProgress.value >= 100) {
        clearInterval(interval);
        aiProgress.value = 100;
      }
    }, 300);

    // 这里是模拟生成，实际项目中可以调用真实的AI接口
    await new Promise(resolve => setTimeout(resolve, 2000));

    // 根据不同提示词模拟不同的结果
    if (aiPrompt.value.includes('系统更新')) {
      emailSettings.title = '系统功能升级通知';
      emailSettings.content = `尊敬的用户：

我们很高兴地通知您，我们的系统已完成重要升级。此次更新包含多项功能改进和性能优化，旨在提升您的使用体验。

主要更新内容：
1. 用户界面全面优化，操作更加便捷
2. 新增数据分析功能，助您更好地把握业务动态
3. 系统性能提升，响应速度提高约30%
4. 修复了若干已知问题，系统更加稳定可靠

更新已于${dayjs().format('YYYY年MM月DD日')}正式上线，您无需进行任何操作即可享受新功能。

如有任何问题或建议，欢迎随时与我们联系。

感谢您一直以来对我们的支持与信任！

此致
系统管理团队`;
    } else {
      emailSettings.title = `关于"${noticeForm.title || '公告'}"的通知`;
      emailSettings.content = `尊敬的用户：

我们需要向您通知以下重要信息：

${noticeForm.content || '（这里将显示公告内容）'}

如有任何疑问，请随时联系我们的客户服务团队。

此致
${mockUsers.find(u => u.id === noticeForm.publisher_id)?.username || '系统管理员'}`;
    }

    message('AI内容生成成功', { type: 'success' });
  } catch (error) {
    console.error('AI生成内容时出错:', error);
    message('生成内容失败，请稍后重试', { type: 'error' });
  } finally {
    aiGenerating.value = false;
    aiProgress.value = 100;
  }
};

// 重置邮件设置
const resetEmailSettings = () => {
  emailSettings.enabled = false;
  emailSettings.title = '';
  emailSettings.content = '';
  aiPrompt.value = '';
};

// 公告类型选项
const typeOptions = [
  { id: 1, name: "系统更新", color: "#1890ff", icon: "sync" },
  { id: 2, name: "账号安全", color: "#f5222d", icon: "shield-alt" },
  { id: 3, name: "活动通知", color: "#722ed1", icon: "calendar-alt" },
  { id: 4, name: "政策公告", color: "#52c41a", icon: "bullhorn" },
  { id: 5, name: "其他", color: "#faad14", icon: "comment-dots" }
];

// 公告优先级选项
const priorityOptions = [
  { id: 0, name: "普通", color: "#52c41a" },
  { id: 1, name: "重要", color: "#1890ff" },
  { id: 2, name: "紧急", color: "#f5222d" }
];

// 公告状态选项
const statusOptions = [
  { id: 0, name: "草稿", color: "#d9d9d9" },
  { id: 1, name: "已发布", color: "#52c41a" },
  { id: 2, name: "已撤回", color: "#f5222d" }
];

// 公告接收范围选项
const scopeOptions = [
  { id: 1, name: "全体公告", icon: "users" },
  { id: 2, name: "部分用户公告", icon: "user-friends" },
  { id: 3, name: "个人通知", icon: "user" }
];

// 按钮尺寸
const buttonSize = ref<'' | 'default' | 'small' | 'large'>('small');

// 所有公告数据和本地分页后的数据
const allNoticeData = ref<any[]>([]);
const noticeData = ref<any[]>([]);

// 分页设置
const currentPage = ref(1);
const pageSize = ref(5);
const total = ref(0);

// 搜索表单
const searchForm = reactive<NoticeParams>({
  title: '',
  category_type: undefined,
  status: undefined,
  priority: undefined,
  notice_type: undefined,
  is_top: undefined
});

// 表格数据与加载状态
const tableLoading = ref(false);
const selectedNotices = ref<any[]>([]);

// 对话框相关状态
const noticeDialogVisible = ref(false);
const dialogTitle = ref('新增公告');
const noticeFormRef = ref<any>(null);
const submitting = ref(false);

// 表单数据
const noticeForm = reactive<NoticeCreateData & { target_uid?: number | string }>({
  title: '',
  content: '',
  notice_type: NOTICE_TYPES.ALL,
  category_type: CATEGORY_TYPES.SYSTEM_UPDATE,
  publisher_id: 1, // 默认当前用户ID，实际应该从登录用户信息中获取
  target_uids: [],
  target_uid: undefined,
  status: NOTICE_STATUS.DRAFT,
  priority: PRIORITY_LEVELS.NORMAL,
  is_top: false,
  publish_time: dayjs().format('YYYY-MM-DD HH:mm:ss')
});

// 表单验证规则
const noticeRules = {
  title: [{ required: true, message: '请输入公告标题', trigger: 'blur' }],
  content: [{ required: true, message: '请输入公告内容', trigger: 'blur' }],
  notice_type: [{ required: true, message: '请选择公告类型', trigger: 'change' }],
  category_type: [{ required: true, message: '请选择公告分类', trigger: 'change' }],
  target_uids: [{
    required: true,
    message: '请选择接收用户',
    trigger: 'change',
    validator: (rule: any, value: any, callback: any) => {
      if (noticeForm.notice_type === NOTICE_TYPES.PARTIAL && (!value || value.length === 0)) {
        callback(new Error('请选择接收用户'));
      } else {
        callback();
      }
    }
  }],
  target_uid: [{
    required: true,
    message: '请选择接收用户',
    trigger: 'change',
    validator: (rule: any, value: any, callback: any) => {
      if (noticeForm.notice_type === NOTICE_TYPES.PERSONAL && !value) {
        callback(new Error('请选择接收用户'));
      } else {
        callback();
      }
    }
  }]
};

// 监听公告类型变化，重置目标用户并加载用户数据
watch(() => noticeForm.notice_type, (newVal) => {
  if (newVal === NOTICE_TYPES.ALL) {
    noticeForm.target_uids = [];
    noticeForm.target_uid = undefined;
  } else if (newVal === NOTICE_TYPES.PARTIAL) {
    noticeForm.target_uid = undefined;
    // 如果是多用户通知，自动加载默认用户列表
    loadDefaultUserOptions();
  } else if (newVal === NOTICE_TYPES.PERSONAL) {
    noticeForm.target_uids = [];
    // 如果是个人通知，自动加载默认用户列表
    loadDefaultUserOptions();
  }
});

// 加载默认用户列表
const loadDefaultUserOptions = async () => {
  if (userOptions.value.length > 0 || userDataLoaded.value) return; // 已有用户数据，不重新加载

  userSelectLoading.value = true;
  try {
    const res = await getUserList({
      page_size: 10
    });

    if (res.code === 200 && res.data && res.data.list) {
      userOptions.value = res.data.list;
      userDataLoaded.value = true; // 标记已加载数据
    } else {
      console.error('获取用户列表失败:', res.msg);
    }
  } catch (error) {
    console.error('加载用户列表出错:', error);
  } finally {
    userSelectLoading.value = false;
  }
};

// 获取接收范围类型名称
const getScopeTypeName = (type: number) => {
  const option = scopeOptions.find(item => item.id === type);
  return option ? option.name : '未知';
};

// 获取公告列表
const fetchNoticeList = async () => {
  tableLoading.value = true;
  try {
    const params: NoticeParams = {
      page: 1,
      page_size: 100,
      ...searchForm,
      sort_field: 'publish_time',
      sort_order: 'desc'
    };

    // 过滤掉空值
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null || params[key] === undefined) {
        delete params[key];
      }
    });

    const res = await getNoticeList(params) as ApiResponse;
    if (res.code === 200) {
      // 处理返回的数据
      const list = res.data.data || [];

      // 格式化数据
      allNoticeData.value = list.map(item => formatNoticeItem(item));
      total.value = res.data.total || list.length;

      // 本地分页
      updatePageData();
    } else {
      message(`获取公告列表失败: ${res.message}`, { type: 'error' });
    }
  } catch (error) {
    console.error('获取公告列表出错:', error);
    message('获取公告列表失败，请稍后重试', { type: 'error' });
  } finally {
    tableLoading.value = false;
  }
};

// 格式化公告数据
const formatNoticeItem = (item: any) => {
  // 获取类型、状态、优先级显示信息
  const typeInfo = typeOptions.find(t => t.id === item.category_type) || typeOptions[4]; // 默认其他
  const statusInfo = statusOptions.find(s => s.id === item.status) || statusOptions[0]; // 默认草稿
  const priorityInfo = priorityOptions.find(p => p.id === item.priority) || priorityOptions[0]; // 默认普通

  // 获取发布者信息
  let publisher = item.publisher;
  if (!publisher) {
    publisher = mockUsers.find(u => u.id === item.publisher_id) || {
      id: item.publisher_id,
      username: `用户${item.publisher_id}`,
      avatar: "",
      role: "用户"
    };
  }

  // 处理接收用户
  let receivers = [];
  if (item.notice_type === NOTICE_TYPES.PARTIAL && item.target_uid) {
    const targetIds = item.target_uid.split(',').map(id => Number(id.trim()));
    if (targetIds[0] !== 0) { // 排除值为0的情况
      receivers = targetIds.map(id => {
        const user = mockUsers.find(u => u.id === id);
        return {
          id,
          username: user ? user.username : `用户${id}`,
          read_status: Math.random() > 0.5 // 模拟随机已读/未读状态
        };
      });
    }
  } else if (item.notice_type === NOTICE_TYPES.PERSONAL && item.target_uid) {
    const id = Number(item.target_uid);
    if (id !== 0) { // 排除值为0的情况
      const user = mockUsers.find(u => u.id === id);
      receivers = [{
        id,
        username: user ? user.username : `用户${id}`,
        read_status: Math.random() > 0.5 // 模拟随机已读/未读状态
      }];
    }
  }

  return {
    id: item.notice_id,
    title: item.title,
    content: item.content,
    type_id: item.category_type,
    type: typeInfo,
    status_id: item.status,
    status: statusInfo,
    priority_id: item.priority,
    priority: priorityInfo,
    scope_type_id: item.notice_type,
    is_pinned: item.is_top ? 1 : 0,
    delete_time: item.delete_time, // 添加软删除时间字段
    publish_time: item.publish_time,
    publisher: publisher,
    receivers: item.notice_type !== NOTICE_TYPES.ALL ? receivers : null,
    attachment: false // 暂不支持附件
  };
};

// 更新分页后的数据
const updatePageData = () => {
  const startIndex = (currentPage.value - 1) * pageSize.value;
  const endIndex = startIndex + pageSize.value;
  noticeData.value = allNoticeData.value.slice(startIndex, endIndex);
};

// 处理搜索
const handleSearch = () => {
  currentPage.value = 1;
  fetchNoticeList();
};

// 重置搜索表单
const resetSearchForm = () => {
  Object.keys(searchForm).forEach(key => {
    searchForm[key] = undefined;
  });
  currentPage.value = 1;
  fetchNoticeList();
};

// 处理分页大小变化
const handleSizeChange = (val: number) => {
  pageSize.value = val;
  updatePageData();
};

// 处理页码变化
const handleCurrentChange = (val: number) => {
  currentPage.value = val;
  updatePageData();
};

// 处理表格选择变化
const handleSelectionChange = (selection: any[]) => {
  selectedNotices.value = selection;
};

// 格式化日期时间
const formatDateTime = (dateStr: string) => {
  return dayjs(dateStr).format('YYYY-MM-DD HH:mm');
};

// 处理查看公告
const handleViewNotice = (row: any) => {
  // 暂时简单实现，使用弹窗
  ElMessageBox.alert(row.content, row.title, {
    dangerouslyUseHTMLString: true,
    confirmButtonText: '关闭'
  });
};

// 处理删除公告
const handleDeleteNotice = (row: any) => {
  // 创建响应式变量用于存储复选框状态
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h('div', null, [
      h('p', null, `确定要删除公告 "${row.title}" 吗？`),
      h('div', { style: 'margin-top: 16px; display: flex; align-items: center;' }, [
        h('input', {
          type: 'checkbox',
          style: 'width: 16px; height: 16px; margin-right: 8px; cursor: pointer;',
          checked: isRealDelete.value,
          onInput: (event) => {
            isRealDelete.value = (event.target as HTMLInputElement).checked;
          }
        }),
        h('span', null, '永久删除（否则为软删除，可在回收站恢复）')
      ])
    ]);
  };

  ElMessageBox({
    title: '删除确认',
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: '确定删除',
    cancelButtonText: '取消',
    type: 'warning',
    beforeClose: (action, instance, done) => {
      if (action === 'confirm') {
        instance.confirmButtonLoading = true;
        deleteNotice(row.id, isRealDelete.value)
          .then(res => {
            if ((res as ApiResponse).code === 200) {
              message(isRealDelete.value ? '永久删除公告成功' : '删除公告成功', { type: 'success' });
              fetchNoticeList();
            } else {
              message((res as ApiResponse).message || '删除公告失败', { type: 'error' });
            }
          })
          .catch(() => {
            message('删除公告失败，请稍后重试', { type: 'error' });
          })
          .finally(() => {
            instance.confirmButtonLoading = false;
            done();
          });
      } else {
        done();
      }
    }
  });
};

// 处理批量删除
const handleBatchDelete = () => {
  if (selectedNotices.value.length === 0) {
    message('请至少选择一条公告', { type: 'warning' });
    return;
  }

  const noticeIds = selectedNotices.value.map(item => item.id);
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h('div', null, [
      h('p', null, `确定要删除选中的 ${noticeIds.length} 条公告吗？`),
      h('div', { style: 'margin-top: 16px; display: flex; align-items: center;' }, [
        h('input', {
          type: 'checkbox',
          style: 'width: 16px; height: 16px; margin-right: 8px; cursor: pointer;',
          checked: isRealDelete.value,
          onInput: (event) => {
            isRealDelete.value = (event.target as HTMLInputElement).checked;
          }
        }),
        h('span', null, '永久删除（否则为软删除，可在回收站恢复）')
      ])
    ]);
  };

  ElMessageBox({
    title: '批量删除确认',
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: '确定删除',
    cancelButtonText: '取消',
    type: 'warning',
    beforeClose: (action, instance, done) => {
      if (action === 'confirm') {
        instance.confirmButtonLoading = true;
        batchDeleteNotice(noticeIds, isRealDelete.value)
          .then(res => {
            if ((res as ApiResponse).code === 200) {
              message(isRealDelete.value ? '永久删除公告成功' : '批量删除公告成功', { type: 'success' });
              fetchNoticeList();
              selectedNotices.value = [];
            } else {
              message((res as ApiResponse).message || '批量删除公告失败', { type: 'error' });
            }
          })
          .catch(() => {
            message('批量删除公告失败，请稍后重试', { type: 'error' });
          })
          .finally(() => {
            instance.confirmButtonLoading = false;
            done();
          });
      } else {
        done();
      }
    }
  });
};

// 处理发布公告
const handlePublishNotice = async (row: any) => {
  try {
    const res = await publishNotice(row.id) as ApiResponse;
    if (res.code === 200) {
      message('发布公告成功', { type: 'success' });
      fetchNoticeList();
    } else {
      message(res.message || '发布公告失败', { type: 'error' });
    }
  } catch (error) {
    console.error('发布公告出错:', error);
    message('发布公告失败，请稍后重试', { type: 'error' });
  }
};

// 处理撤回公告
const handleRevokeNotice = async (row: any) => {
  try {
    const res = await revokeNotice(row.id) as ApiResponse;
    if (res.code === 200) {
      message('撤回公告成功', { type: 'success' });
      fetchNoticeList();
    } else {
      message(res.message || '撤回公告失败', { type: 'error' });
    }
  } catch (error) {
    console.error('撤回公告出错:', error);
    message('撤回公告失败，请稍后重试', { type: 'error' });
  }
};

// 处理置顶/取消置顶
const handleTogglePin = async (row: any) => {
  try {
    const isPinned = !row.is_pinned;
    const res = await setNoticeTopStatus(row.id, isPinned) as ApiResponse;
    if (res.code === 200) {
      message(isPinned ? '置顶公告成功' : '取消置顶成功', { type: 'success' });
      fetchNoticeList();
    } else {
      message((res.message || (isPinned ? '置顶公告失败' : '取消置顶失败')), { type: 'error' });
    }
  } catch (error) {
    console.error('操作公告置顶状态出错:', error);
    message('操作失败，请稍后重试', { type: 'error' });
  }
};

// 处理恢复公告
const handleRestoreNotice = async (row: any) => {
  try {
    const res = await restoreNotice(row.id) as ApiResponse;
    if (res.code === 200) {
      message('恢复公告成功', { type: 'success' });
      fetchNoticeList();
    } else {
      message(res.message || '恢复公告失败', { type: 'error' });
    }
  } catch (error) {
    console.error('恢复公告出错:', error);
    message('恢复公告失败，请稍后重试', { type: 'error' });
  }
};

// 在script部分顶部添加当前编辑ID变量
const currentEditId = ref<number | null>(null);

// 处理新增公告
const handleAddNotice = () => {
  dialogTitle.value = '新增公告';
  resetNoticeForm();
  noticeDialogVisible.value = true;
  // 预加载用户数据
  loadDefaultUserOptions();
};

// 处理编辑公告
const handleEditNotice = (row: any) => {
  dialogTitle.value = '编辑公告';
  resetNoticeForm();

  // 加载公告数据到表单
  noticeForm.title = row.title;
  noticeForm.content = row.content;
  noticeForm.notice_type = row.scope_type_id;
  noticeForm.category_type = row.type_id;
  noticeForm.status = row.status_id;
  noticeForm.priority = row.priority_id;
  noticeForm.is_top = !!row.is_pinned;
  noticeForm.publish_time = row.publish_time || dayjs().format('YYYY-MM-DD HH:mm:ss');

  // 处理接收用户
  if (row.scope_type_id === NOTICE_TYPES.PARTIAL && row.receivers) {
    noticeForm.target_uids = row.receivers.map(user => user.id);
  } else if (row.scope_type_id === NOTICE_TYPES.PERSONAL && row.receivers && row.receivers.length === 1) {
    noticeForm.target_uid = row.receivers[0].id;
  }

  // 保存当前编辑的ID
  currentEditId.value = row.id;

  // 打开对话框
  noticeDialogVisible.value = true;
};

// 重置表单
const resetNoticeForm = () => {
  noticeForm.title = '';
  noticeForm.content = '';
  noticeForm.notice_type = NOTICE_TYPES.ALL;
  noticeForm.category_type = CATEGORY_TYPES.SYSTEM_UPDATE;
  noticeForm.target_uids = [];
  noticeForm.target_uid = undefined;
  noticeForm.status = NOTICE_STATUS.DRAFT;
  noticeForm.priority = PRIORITY_LEVELS.NORMAL;
  noticeForm.is_top = false;
  noticeForm.publish_time = dayjs().format('YYYY-MM-DD HH:mm:ss');
  noticeForm.expire_time = undefined;

  // 重置邮件设置
  resetEmailSettings();

  // 重置表单验证
  if (noticeFormRef.value) {
    noticeFormRef.value.resetFields();
  }
};

// 提交公告表单
const submitNoticeForm = async () => {
  if (!noticeFormRef.value) return;

  await noticeFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    submitting.value = true;
    try {
      // 处理目标用户
      let submitData = { ...noticeForm };

      // 个人通知处理
      if (noticeForm.notice_type === NOTICE_TYPES.PERSONAL && noticeForm.target_uid) {
        submitData.target_uids = [noticeForm.target_uid];
        delete submitData.target_uid;
      }

      // 全体公告不需要目标用户
      if (noticeForm.notice_type === NOTICE_TYPES.ALL) {
        delete submitData.target_uids;
        delete submitData.target_uid;
      }

      let res;
      if (currentEditId.value) {
        // 编辑现有公告
        res = await updateNotice(currentEditId.value, submitData) as ApiResponse;
      } else {
        // 创建新公告
        res = await createNotice(submitData) as ApiResponse;
      }

      if (res.code === 200) {
        // 如果开启了邮件通知，单独处理邮件发送
        if (emailSettings.enabled) {
          // 这里应该调用发送邮件的API，与公告数据分开处理
          const emailData = {
            notice_id: res.data?.id || currentEditId.value,
            title: emailSettings.title || noticeForm.title,
            content: emailSettings.content || noticeForm.content,
            receivers: noticeForm.notice_type === NOTICE_TYPES.PERSONAL ? [noticeForm.target_uid] :
              noticeForm.notice_type === NOTICE_TYPES.PARTIAL ? noticeForm.target_uids : []
          };

          console.log('准备发送邮件通知:', emailData);
          // 实际项目中这里应该调用发送邮件的API
          // await sendEmailNotification(emailData);
        }

        message(currentEditId.value ? '更新公告成功' : '创建公告成功', { type: 'success' });
        noticeDialogVisible.value = false;
        resetEmailSettings();
        currentEditId.value = null;
        fetchNoticeList();
      } else {
        message(res.message || (currentEditId.value ? '更新公告失败' : '创建公告失败'), { type: 'error' });
      }
    } catch (error) {
      console.error(currentEditId.value ? '更新公告出错' : '创建公告出错:', error);
      message(currentEditId.value ? '更新公告失败，请稍后重试' : '创建公告失败，请稍后重试', { type: 'error' });
    } finally {
      submitting.value = false;
    }
  });
};

onMounted(() => {
  fetchNoticeList();
});
</script>

<style lang="scss" scoped>
.notice-container {
  padding: 0;

  .card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    .header-title {
      font-size: 16px;
      font-weight: 600;
      margin: 0;
      display: flex;
      align-items: center;
    }
  }

  .search-form {
    margin-bottom: 16px;

    .search-area {
      background-color: #f9fafc;
      border-radius: 4px;
      padding: 16px 12px 8px;

      .search-items {
        .search-btn-col {
          display: flex;
          align-items: flex-end;
          margin-bottom: 18px;

          .search-btn {
            width: 100%;
            justify-content: center;
          }
        }
      }
    }
  }

  .action-toolbar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;

    .left-actions,
    .right-actions {
      display: flex;
      gap: 8px;
    }

    .btn-with-icon {
      display: flex;
      align-items: center;
      font-size: 12px;
      padding: 6px 10px;
    }

    .action-btn {
      min-width: 36px;
      height: 36px;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;

      &:hover {
        transform: scale(1.05);
        transition: transform 0.2s;
      }
    }
  }

  .table-container {
    margin-bottom: 16px;

    :deep(.el-table__header) th {
      padding: 6px 0;
      height: 38px;
    }

    :deep(.el-table__body) td {
      padding: 4px 0;
    }

    .notice-title {
      font-size: 14px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 4px;

      &.is-pinned {
        color: #f56c6c;

        .pin-icon {
          transform: rotate(45deg);
          color: #f56c6c;
        }
      }

      .notice-title-text {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .meta-item {
        color: #909399;
        font-size: 12px;
      }
    }

    .notice-content {
      color: #606266;
      font-size: 12px;
      line-height: 1.4;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .view-count {
      font-size: 12px;
      color: #909399;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 3px;
    }

    .notice-type {
      .el-tag {
        padding: 0 6px;
        height: 22px;
        line-height: 22px;
        font-size: 11px;
        border-radius: 2px;
      }
    }

    .priority-badge {
      display: inline-block;
      padding: 1px 6px;
      border-radius: 2px;
      border: 1px solid;
      font-size: 11px;
      font-weight: 500;
    }

    .status-badge {
      display: inline-block;
      padding: 1px 6px;
      border-radius: 2px;
      border: 1px solid;
      font-size: 11px;
      font-weight: 500;
    }

    .scope-info {
      .el-tag {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2px 8px;

        .status-dot {
          display: inline-block;
          width: 6px;
          height: 6px;
          border-radius: 50%;
          margin-left: 4px;

          &.read {
            background-color: #67c23a;
          }

          &.unread {
            background-color: #909399;
          }
        }

        &.receiver-tag {
          position: relative;
        }
      }
    }

    .receivers-list {
      .receivers-header {
        font-weight: bold;
        padding: 8px 0;
        margin-bottom: 8px;
        border-bottom: 1px solid #ebeef5;
        text-align: center;
        color: #606266;
      }

      .receivers-scrollbar {
        border: 1px solid #f0f0f0;
        border-radius: 4px;
        background-color: #fafafa;
      }

      .receivers-table {
        .table-header {
          display: flex;
          background-color: #f5f7fa;
          padding: 8px 12px;
          font-weight: 500;
          color: #606266;
          border-bottom: 1px solid #ebeef5;

          .col-name {
            flex: 1;
          }

          .col-status {
            width: 80px;
            text-align: center;
          }
        }

        .table-body {
          .table-row {
            display: flex;
            padding: 8px 12px;
            border-bottom: 1px solid #ebeef5;
            align-items: center;

            &:last-child {
              border-bottom: none;
            }

            .col-name {
              flex: 1;
              font-size: 13px;
            }

            .col-status {
              width: 80px;
              text-align: center;
            }
          }
        }
      }
    }

    .publisher-info {
      display: flex;
      justify-content: center;
    }

    .time-info {
      font-size: 12px;
      color: #606266;
    }

    .operation-btns {
      display: flex;
      justify-content: center;
      gap: 4px;

      :deep(.el-button) {
        padding: 4px 6px;
        font-size: 12px;
      }
    }
  }

  .pagination-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;

    :deep(.el-pagination) {
      padding: 0;
      font-size: 12px;

      .el-pagination__sizes {
        margin: 0 6px 0 0;
      }

      .el-pagination__total {
        margin-right: 6px;
      }

      .el-pagination button {
        min-width: 24px;
        height: 24px;
      }

      .el-pager li {
        min-width: 24px;
        height: 24px;
        line-height: 24px;
      }
    }
  }
}

.option-with-icon,
.option-with-color {
  display: flex;
  align-items: center;
  gap: 4px;

  .color-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
  }
}

.text-gray-400 {
  color: #909399;
}

.mr-1 {
  margin-right: 3px;
}

.mr-2 {
  margin-right: 6px;
}

.ml-1 {
  margin-left: 3px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 10px;

  .el-button {
    margin-left: 10px;
  }
}

.status-tag {
  display: inline-flex;
  align-items: center;
  font-size: 11px;
  padding: 0 5px;
  border-radius: 3px;
  height: 18px;

  &.status-active {
    background-color: rgba(103, 194, 58, 0.15);
    color: #67C23A;
  }

  &.status-deleted {
    background-color: rgba(245, 108, 108, 0.15);
    color: #F56C6C;
  }
}

.user-option {
  display: flex;
  align-items: center;
  padding: 6px 0;

  :deep(.el-avatar) {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    background-color: #409eff;
  }

  .user-info {
    margin-left: 10px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;

    .username {
      font-weight: 500;
      font-size: 13px;
      line-height: 1.2;
    }

    .user-detail {
      display: flex;
      font-size: 11px;
      color: #909399;
      margin-top: 2px;
      line-height: 1.2;

      .user-id {
        margin-right: 8px;
      }

      .user-role {
        color: #409eff;
      }
    }
  }
}

.ai-generator {
  margin-top: 15px;
  padding: 18px;
  border-radius: 10px;
  background: linear-gradient(135deg, #fff7ed 0%, #fdf2f8 50%, #f5f3ff 100%);
  border: 1px solid #f5d0fe;
  box-shadow: 0 6px 16px rgba(244, 114, 182, 0.08);

  .ai-content {
    margin-top: 0;
  }

  .ai-input-container {
    display: flex;
    gap: 12px;
    align-items: center;

    .ai-input-wrapper {
      flex: 1;

      :deep(.el-input__wrapper) {
        border-radius: 8px;
        box-shadow: 0 0 0 1px #e9d5ff;
        transition: all 0.3s ease;

        &:hover,
        &:focus-within {
          box-shadow: 0 0 0 1px #d8b4fe, 0 4px 10px rgba(216, 180, 254, 0.2);
        }
      }

      :deep(.el-input__prefix) {
        margin-right: 8px;
        color: #f59e0b;
      }
    }

    .ai-generate-btn {
      border-radius: 8px;
      background: linear-gradient(45deg, #f59e0b 0%, #ec4899 50%, #8b5cf6 100%);
      border: none;
      height: 40px;
      font-size: 14px;
      font-weight: 500;
      padding: 0 20px;
      box-shadow: 0 4px 10px rgba(139, 92, 246, 0.3);
      color: white;
      transition: all 0.3s ease;

      &:hover {
        background: linear-gradient(45deg, #f59e0b 0%, #ec4899 50%, #8b5cf6 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(139, 92, 246, 0.4);
      }

      &:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
      }

      &:focus,
      &:hover,
      &:active {
        border: none;
        outline: none;
        color: white;
        background-color: transparent;
      }

      .btn-icon {
        font-size: 15px;
        margin-right: 5px;
      }
    }
  }

  .ai-generating {
    margin-top: 14px;

    .ai-generating-indicator {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 8px;

      .typing-dots {
        display: flex;
        gap: 4px;

        span {
          width: 6px;
          height: 6px;
          border-radius: 50%;
          background: linear-gradient(45deg, #f59e0b 0%, #ec4899 50%, #8b5cf6 100%);
          animation: typing-animation 1s infinite;

          &:nth-child(2) {
            animation-delay: 0.2s;
          }

          &:nth-child(3) {
            animation-delay: 0.4s;
          }
        }
      }

      .ai-status {
        font-size: 14px;
        background: linear-gradient(45deg, #f59e0b 0%, #ec4899 50%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
        margin-left: 10px;
        font-weight: 500;
      }
    }

    .ai-progress {
      :deep(.el-progress-bar__inner) {
        background: linear-gradient(45deg, #f59e0b 0%, #ec4899 50%, #8b5cf6 100%);
      }
    }
  }
}

@keyframes typing-animation {

  0%,
  100% {
    transform: translateY(0);
    opacity: 0.6;
  }

  50% {
    transform: translateY(-3px);
    opacity: 1;
  }
}

.email-settings-container {
  background-color: #f8f9fc;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 10px;
  border: 1px solid #ebeef5;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.02);

  :deep(.el-switch) {
    --el-switch-on-color: #a18cd1;
  }

  :deep(.el-form-item__label) {
    font-weight: 500;
  }
}

.divider-title {
  display: flex;
  align-items: center;
  font-weight: 600;
  color: #606266;

  .divider-icon {
    margin-right: 8px;
    color: #a18cd1;
  }
}

:deep(.el-divider__text) {
  background-color: #fff;
  padding: 0 15px;
  display: flex;
  align-items: center;
}
</style>