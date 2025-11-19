<template>
  <div class="notice-container">
    <el-card class="box-card" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="header-title">
            <font-awesome-icon :icon="['fas', 'bullhorn']" class="mr-2" />
            公告管理
          </h3>
        </div>
      </template>

      <!-- Tab切换 -->
      <el-tabs v-model="activeTab" class="notice-tabs">
        <el-tab-pane label="公告管理" name="notice">
          <template #label>
            <span>
              <font-awesome-icon :icon="['fas', 'bullhorn']" class="mr-1" />
              公告管理
            </span>
          </template>

          <!-- 搜索区域 -->
          <el-form :model="searchForm" label-width="80px" class="search-form">
            <div class="search-area">
              <div class="search-items">
                <el-row :gutter="16">
                  <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                    <el-form-item label="公告标题">
                      <el-input
                        v-model="searchForm.title"
                        placeholder="请输入标题"
                        clearable
                        :prefix-icon="Search"
                        size="default"
                      />
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                    <el-form-item label="公告类型">
                      <el-select
                        v-model="searchForm.category_type"
                        placeholder="选择类型"
                        clearable
                        style="width: 100%"
                        size="default"
                      >
                        <el-option
                          v-for="item in typeOptions"
                          :key="item.id"
                          :label="item.name"
                          :value="item.id"
                        >
                          <div class="option-with-icon">
                            <font-awesome-icon
                              :icon="['fas', item.icon]"
                              :style="{ color: item.color }"
                              class="mr-1"
                            />
                            <span>{{ item.name }}</span>
                          </div>
                        </el-option>
                      </el-select>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                    <el-form-item label="状态">
                      <el-select
                        v-model="searchForm.status"
                        placeholder="选择状态"
                        clearable
                        style="width: 100%"
                        size="default"
                      >
                        <el-option
                          v-for="item in statusOptions"
                          :key="item.id"
                          :label="item.name"
                          :value="item.id"
                        >
                          <div class="option-with-color">
                            <div
                              class="color-dot"
                              :style="{ backgroundColor: item.color }"
                            />
                            <span>{{ item.name }}</span>
                          </div>
                        </el-option>
                      </el-select>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                    <el-form-item label="优先级">
                      <el-select
                        v-model="searchForm.priority"
                        placeholder="选择优先级"
                        clearable
                        style="width: 100%"
                        size="default"
                      >
                        <el-option
                          v-for="item in priorityOptions"
                          :key="item.id"
                          :label="item.name"
                          :value="item.id"
                        >
                          <div class="option-with-color">
                            <div
                              class="color-dot"
                              :style="{ backgroundColor: item.color }"
                            />
                            <span>{{ item.name }}</span>
                          </div>
                        </el-option>
                      </el-select>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                    <el-form-item label="可见性">
                      <el-select
                        v-model="searchForm.visibility"
                        placeholder="选择可见性"
                        clearable
                        style="width: 100%"
                        size="default"
                      >
                        <el-option value="public" label="公开">
                          <div class="option-with-icon">
                            <font-awesome-icon
                              :icon="['fas', 'globe']"
                              class="mr-1"
                            />
                            <span>公开</span>
                          </div>
                        </el-option>
                        <el-option value="login_required" label="登录可见">
                          <div class="option-with-icon">
                            <font-awesome-icon
                              :icon="['fas', 'lock']"
                              class="mr-1"
                            />
                            <span>登录可见</span>
                          </div>
                        </el-option>
                        <el-option value="specific_users" label="指定用户">
                          <div class="option-with-icon">
                            <font-awesome-icon
                              :icon="['fas', 'user']"
                              class="mr-1"
                            />
                            <span>指定用户</span>
                          </div>
                        </el-option>
                        <el-option value="specific_roles" label="指定角色">
                          <div class="option-with-icon">
                            <font-awesome-icon
                              :icon="['fas', 'users']"
                              class="mr-1"
                            />
                            <span>指定角色</span>
                          </div>
                        </el-option>
                      </el-select>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="12" :sm="8" :md="5" :lg="4" :xl="3">
                    <el-form-item label="是否置顶">
                      <el-select
                        v-model="searchForm.is_top"
                        placeholder="是否置顶"
                        clearable
                        style="width: 100%"
                        size="default"
                      >
                        <el-option label="已置顶" :value="1" />
                        <el-option label="未置顶" :value="0" />
                      </el-select>
                    </el-form-item>
                  </el-col>
                  <el-col
                    :xs="12"
                    :sm="4"
                    :md="3"
                    :lg="2"
                    :xl="1"
                    class="search-btn-col"
                  >
                    <el-button
                      type="primary"
                      size="default"
                      class="search-btn"
                      @click="handleSearch"
                    >
                      <font-awesome-icon
                        :icon="['fas', 'search']"
                        class="mr-1"
                      />
                      搜索
                    </el-button>
                  </el-col>
                  <el-col
                    :xs="12"
                    :sm="4"
                    :md="3"
                    :lg="2"
                    :xl="1"
                    class="search-btn-col"
                  >
                    <el-button
                      type="default"
                      size="default"
                      class="search-btn"
                      @click="resetSearchForm"
                    >
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
              <el-button
                size="small"
                type="primary"
                class="btn-with-icon"
                @click="handleAddNotice"
              >
                <font-awesome-icon :icon="['fas', 'plus']" class="mr-1" />
                新增公告
              </el-button>
              <el-button
                size="small"
                type="success"
                class="btn-with-icon"
                @click="handleOpenEmailDialog"
              >
                <font-awesome-icon :icon="['fas', 'envelope']" class="mr-1" />
                发送邮件
              </el-button>
              <el-button
                size="small"
                type="danger"
                :disabled="selectedNotices.length === 0"
                class="btn-with-icon"
                @click="handleBatchDelete"
              >
                <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" />
                批量删除
              </el-button>
            </div>
            <div class="right-actions">
              <el-tooltip content="刷新数据" placement="top" :show-after="500">
                <el-button
                  :size="buttonSize"
                  type="info"
                  plain
                  class="btn-with-icon action-btn"
                  @click="fetchNoticeList"
                >
                  <font-awesome-icon :icon="['fas', 'sync']" />
                </el-button>
              </el-tooltip>
              <el-dropdown trigger="click">
                <el-button
                  :size="buttonSize"
                  type="primary"
                  plain
                  class="btn-with-icon action-btn"
                >
                  <font-awesome-icon :icon="['fas', 'ellipsis-v']" />
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item>
                      <font-awesome-icon
                        :icon="['fas', 'print']"
                        class="mr-1"
                      />
                      打印
                    </el-dropdown-item>
                    <el-dropdown-item>
                      <font-awesome-icon
                        :icon="['fas', 'file-export']"
                        class="mr-1"
                      />
                      导出
                    </el-dropdown-item>
                    <el-dropdown-item divided>
                      <font-awesome-icon :icon="['fas', 'cog']" class="mr-1" />
                      设置
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </div>

          <!-- 公告列表 -->
          <div class="table-container">
            <el-table
              v-loading="tableLoading"
              border
              :data="noticeData"
              size="small"
              :header-cell-style="{
                backgroundColor: '#f5f7fa',
                color: '#606266',
                fontWeight: 'normal'
              }"
              :cell-style="{ padding: '4px 0' }"
              stripe
              @selection-change="handleSelectionChange"
            >
              <el-table-column type="selection" align="center" width="35" />
              <el-table-column
                fixed="left"
                label="ID"
                prop="id"
                align="center"
                width="50"
              />

              <!-- 公告标题 -->
              <el-table-column
                label="公告标题"
                prop="title"
                min-width="150"
                fixed="left"
              >
                <template #default="{ row }">
                  <div
                    class="notice-title"
                    :class="{ 'is-pinned': row.is_pinned }"
                  >
                    <font-awesome-icon
                      v-if="row.is_pinned"
                      :icon="['fas', 'thumbtack']"
                      class="pin-icon"
                    />
                    <el-tooltip
                      :content="row.title"
                      placement="top"
                      :show-after="500"
                    >
                      <span class="notice-title-text">{{ row.title }}</span>
                    </el-tooltip>
                    <div v-if="row.attachment" class="meta-item">
                      <font-awesome-icon :icon="['fas', 'paperclip']" />
                    </div>
                  </div>
                </template>
              </el-table-column>

              <!-- 公告内容 -->
              <el-table-column label="公告内容" prop="content" min-width="200">
                <template #default="{ row }">
                  <div class="notice-content">
                    <el-tooltip
                      :content="row.content"
                      placement="top"
                      :show-after="500"
                    >
                      <span>
                        {{
                          row.content.substring(0, 60) +
                          (row.content.length > 60 ? "..." : "")
                        }}
                      </span>
                    </el-tooltip>
                  </div>
                </template>
              </el-table-column>

              <!-- 浏览次数 改为显示删除状态 -->
              <el-table-column label="状态" align="center" width="90">
                <template #default="{ row }">
                  <div
                    class="status-tag"
                    :class="
                      row.delete_time ? 'status-deleted' : 'status-active'
                    "
                  >
                    <font-awesome-icon
                      :icon="
                        row.delete_time
                          ? ['fas', 'ban']
                          : ['fas', 'check-circle']
                      "
                      class="mr-1"
                    />
                    {{ row.delete_time ? "已删除" : "正常" }}
                  </div>
                </template>
              </el-table-column>

              <!-- 公告类型 -->
              <el-table-column label="类型" align="center" width="90">
                <template #default="{ row }">
                  <div class="notice-type">
                    <el-tag
                      size="small"
                      :style="{
                        backgroundColor: row.type.color,
                        borderColor: row.type.color
                      }"
                      effect="dark"
                    >
                      <font-awesome-icon
                        :icon="['fas', row.type.icon]"
                        class="mr-1"
                      />
                      {{ row.type.name }}
                    </el-tag>
                  </div>
                </template>
              </el-table-column>

              <!-- 优先级 -->
              <el-table-column label="优先级" align="center" width="70">
                <template #default="{ row }">
                  <div
                    class="priority-badge"
                    :style="{
                      color: row.priority.color,
                      borderColor: row.priority.color
                    }"
                  >
                    {{ row.priority.name }}
                  </div>
                </template>
              </el-table-column>

              <!-- 可见性 -->
              <el-table-column label="可见性" align="center" width="110">
                <template #default="{ row }">
                  <el-tag
                    v-if="row.visibility"
                    size="small"
                    :type="getVisibilityTagType(row.visibility)"
                    effect="plain"
                  >
                    {{
                      NOTICE_VISIBILITY_MAP[row.visibility]?.label ||
                      row.visibility
                    }}
                  </el-tag>
                  <el-tag v-else size="small" type="info" effect="plain">
                    未设置
                  </el-tag>
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
                      <font-awesome-icon
                        :icon="['fas', 'clock']"
                        class="mr-1"
                      />
                      {{ formatDateTime(row.publish_time) }}
                    </div>
                    <div v-else class="text-gray-400">
                      <font-awesome-icon
                        :icon="['fas', 'hourglass-half']"
                        class="mr-1"
                      />
                      未发布
                    </div>
                  </div>
                </template>
              </el-table-column>

              <!-- 操作按钮 -->
              <el-table-column
                fixed="right"
                label="操作"
                align="center"
                width="220"
              >
                <template #default="{ row }">
                  <div class="operation-btns">
                    <el-button
                      type="primary"
                      :size="buttonSize"
                      text
                      @click="handleViewNotice(row)"
                    >
                      查看
                    </el-button>
                    <el-button
                      v-if="!row.delete_time"
                      type="primary"
                      :size="buttonSize"
                      text
                      @click="handleEditNotice(row)"
                    >
                      编辑
                    </el-button>
                    <el-button
                      v-if="row.delete_time"
                      type="primary"
                      :size="buttonSize"
                      text
                      @click="handleRestoreNotice(row)"
                    >
                      <font-awesome-icon :icon="['fas', 'undo']" class="mr-1" />
                      恢复
                    </el-button>

                    <el-dropdown
                      v-if="!row.delete_time"
                      trigger="click"
                      class="action-dropdown"
                    >
                      <el-button type="primary" :size="buttonSize" text>
                        更多
                        <font-awesome-icon
                          :icon="['fas', 'chevron-down']"
                          class="ml-1"
                        />
                      </el-button>
                      <template #dropdown>
                        <el-dropdown-menu>
                          <el-dropdown-item @click="handleSendEmail(row)">
                            <font-awesome-icon
                              :icon="['fas', 'envelope']"
                              class="mr-1"
                            />
                            发送邮件
                          </el-dropdown-item>
                          <el-dropdown-item
                            v-if="row.status.id === 0"
                            @click="handlePublishNotice(row)"
                          >
                            <font-awesome-icon
                              :icon="['fas', 'paper-plane']"
                              class="mr-1"
                            />
                            发布
                          </el-dropdown-item>
                          <el-dropdown-item
                            v-if="row.status.id === 1"
                            @click="handleRevokeNotice(row)"
                          >
                            <font-awesome-icon
                              :icon="['fas', 'undo']"
                              class="mr-1"
                            />
                            撤回
                          </el-dropdown-item>
                          <el-dropdown-item @click="handleTogglePin(row)">
                            <font-awesome-icon
                              :icon="[
                                'fas',
                                row.is_pinned ? 'thumbtack' : 'thumbtack'
                              ]"
                              class="mr-1"
                            />
                            {{ row.is_pinned ? "取消置顶" : "置顶" }}
                          </el-dropdown-item>
                          <el-dropdown-item
                            divided
                            @click="handleDeleteNotice(row)"
                          >
                            <font-awesome-icon
                              :icon="['fas', 'trash']"
                              class="mr-1"
                            />
                            删除
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
            <el-pagination
              v-model:current-page="currentPage"
              v-model:page-size="pageSize"
              :page-sizes="[5, 10, 20, 50]"
              :default-page-size="5"
              layout="total, sizes, prev, pager, next"
              :total="total"
              background
              small
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"
            />
          </div>
        </el-tab-pane>

        <!-- 邮件记录Tab -->
        <el-tab-pane label="邮件记录" name="email">
          <template #label>
            <span>
              <font-awesome-icon :icon="['fas', 'envelope']" class="mr-1" />
              邮件记录
            </span>
          </template>

          <EmailRecordTable />
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- 弹窗占位，稍后添加 -->
    <el-dialog
      v-model="noticeDialogVisible"
      :title="dialogTitle"
      width="65%"
      :close-on-click-modal="false"
      destroy-on-close
    >
      <el-form
        ref="noticeFormRef"
        :model="noticeForm"
        :rules="noticeRules"
        label-width="80px"
        label-position="right"
      >
        <el-form-item label="标题" prop="title">
          <el-input
            v-model="noticeForm.title"
            placeholder="请输入公告标题"
            maxlength="100"
            show-word-limit
          />
        </el-form-item>

        <el-form-item label="内容" prop="content">
          <el-input
            v-model="noticeForm.content"
            type="textarea"
            :rows="6"
            placeholder="请输入公告内容"
            maxlength="5000"
            show-word-limit
          />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="分类" prop="category_type">
              <el-select
                v-model="noticeForm.category_type"
                placeholder="请选择公告分类"
                style="width: 100%"
              >
                <el-option
                  v-for="item in typeOptions"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
                >
                  <div class="option-with-icon">
                    <font-awesome-icon
                      :icon="['fas', item.icon]"
                      :style="{ color: item.color }"
                      class="mr-1"
                    />
                    <span>{{ item.name }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="可见性" prop="visibility">
              <el-select
                v-model="noticeForm.visibility"
                placeholder="请选择可见性"
                style="width: 100%"
              >
                <el-option
                  v-for="item in NOTICE_VISIBILITY_OPTIONS"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                >
                  <div class="option-with-icon">
                    <span>{{ item.label }}</span>
                    <span
                      style="margin-left: 8px; font-size: 12px; color: #999"
                    >
                      {{ item.tip }}
                    </span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 指定用户选择（当 visibility = 'specific_users' 时显示） -->
        <el-row v-if="noticeForm.visibility === 'specific_users'" :gutter="20">
          <el-col :span="24">
            <el-form-item label="指定用户" prop="target_user_ids">
              <el-select
                v-model="noticeForm.target_user_ids"
                multiple
                filterable
                remote
                reserve-keyword
                placeholder="请输入用户名或ID搜索"
                :remote-method="remoteSearchUsers"
                :loading="userSelectLoading"
                style="width: 100%"
                popper-class="user-select-dropdown"
                @focus="handleFocus"
              >
                <el-option
                  v-for="user in userOptions"
                  :key="user.id"
                  :label="`${user.username} (ID: ${user.id})`"
                  :value="user.id"
                >
                  <div style="display: flex; align-items: center">
                    <el-avatar
                      :size="24"
                      :src="user.avatar"
                      style="margin-right: 8px"
                    />
                    <span>{{ user.username }}</span>
                    <span style="margin-left: 8px; color: #999">
                      (ID: {{ user.id }})
                    </span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 指定角色选择（当 visibility = 'specific_roles' 时显示） -->
        <el-row v-if="noticeForm.visibility === 'specific_roles'" :gutter="20">
          <el-col :span="24">
            <el-form-item label="指定角色" prop="target_role_ids">
              <el-select
                v-model="noticeForm.target_role_ids"
                multiple
                placeholder="请选择角色"
                style="width: 100%"
                popper-class="user-select-dropdown"
              >
                <el-option
                  v-for="role in roleOptions"
                  :key="role.id"
                  :label="role.name"
                  :value="role.id"
                >
                  <div style="display: flex; align-items: center">
                    <span>{{ role.name }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="优先级" prop="priority">
              <el-select
                v-model="noticeForm.priority"
                placeholder="请选择优先级"
                style="width: 100%"
              >
                <el-option
                  v-for="item in priorityOptions"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
                >
                  <div class="option-with-color">
                    <div
                      class="color-dot"
                      :style="{ backgroundColor: item.color }"
                    />
                    <span>{{ item.name }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="状态" prop="status">
              <el-select
                v-model="noticeForm.status"
                placeholder="请选择状态"
                style="width: 100%"
              >
                <el-option
                  v-for="item in statusOptions"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
                >
                  <div class="option-with-color">
                    <div
                      class="color-dot"
                      :style="{ backgroundColor: item.color }"
                    />
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
              <el-date-picker
                v-model="noticeForm.publish_time"
                type="datetime"
                placeholder="选择发布时间"
                format="YYYY-MM-DD HH:mm:ss"
                value-format="YYYY-MM-DD HH:mm:ss"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="过期时间">
              <el-date-picker
                v-model="noticeForm.expire_time"
                type="datetime"
                placeholder="选择过期时间"
                format="YYYY-MM-DD HH:mm:ss"
                value-format="YYYY-MM-DD HH:mm:ss"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item>
          <el-switch
            v-model="noticeForm.is_top"
            active-text="置顶"
            inactive-text="不置顶"
          />
        </el-form-item>

        <el-divider content-position="left">
          <span class="divider-title">
            <font-awesome-icon
              :icon="['fas', 'envelope']"
              class="divider-icon"
            />
            邮件通知设置
          </span>
        </el-divider>
        <div class="email-settings-container">
          <el-form-item>
            <el-switch
              v-model="emailSettings.enabled"
              active-text="开启邮件通知"
              inactive-text="不发送邮件"
            />
          </el-form-item>

          <template v-if="emailSettings.enabled">
            <el-form-item label="邮件标题">
              <el-input
                v-model="emailSettings.title"
                placeholder="邮件标题，留空则使用通知标题"
              />
            </el-form-item>

            <el-form-item label="邮件内容">
              <el-input
                v-model="emailSettings.content"
                type="textarea"
                :rows="5"
                placeholder="邮件内容，留空则使用通知内容"
                maxlength="2000"
                show-word-limit
              />
            </el-form-item>

            <el-form-item>
              <div class="ai-generator">
                <div class="ai-content">
                  <div class="ai-input-container">
                    <div class="ai-input-wrapper">
                      <el-input
                        v-model="aiPrompt"
                        placeholder="描述您需要的邮件内容，例如：'公司政策更新通知'"
                        :disabled="aiGenerating"
                        class="ai-prompt-input"
                      >
                        <template #prefix>
                          <font-awesome-icon
                            :icon="['fas', 'lightbulb']"
                            class="input-icon"
                          />
                        </template>
                      </el-input>
                    </div>

                    <el-button
                      :loading="aiGenerating"
                      class="ai-generate-btn"
                      @click="generateEmailContent"
                    >
                      <font-awesome-icon
                        :icon="['fas', 'magic']"
                        class="btn-icon"
                      />
                      AI生成
                    </el-button>
                  </div>

                  <div v-if="aiGenerating" class="ai-generating">
                    <div class="ai-generating-indicator">
                      <div class="typing-dots">
                        <span />
                        <span />
                        <span />
                      </div>
                      <div class="ai-status">智能创作中</div>
                    </div>
                    <el-progress
                      :percentage="aiProgress"
                      :indeterminate="true"
                      class="ai-progress"
                    />
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
          <el-button
            type="primary"
            :loading="submitting"
            @click="submitNoticeForm"
          >
            确认
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 邮件发送弹窗 -->
    <el-dialog
      v-model="emailDialogVisible"
      title="发送邮件通知"
      width="65%"
      :close-on-click-modal="false"
      destroy-on-close
    >
      <el-form
        ref="emailFormRef"
        :model="emailForm"
        :rules="emailRules"
        label-width="110px"
        label-position="left"
      >
        <el-alert
          v-if="currentNoticeForEmail"
          type="info"
          :closable="false"
          style="margin-bottom: 20px"
        >
          <template #title>
            <div style="display: flex; gap: 8px; align-items: center">
              <font-awesome-icon :icon="['fas', 'info-circle']" />
              <span>关联公告: {{ currentNoticeForEmail?.title }}</span>
            </div>
          </template>
        </el-alert>

        <el-form-item label="接收方式" prop="receiver_type">
          <el-radio-group
            v-model="emailForm.receiver_type"
            size="default"
            @change="handleReceiverTypeChange"
          >
            <el-radio :label="1" border>
              <font-awesome-icon
                :icon="['fas', 'users']"
                style="margin-right: 6px"
              />
              全部用户
            </el-radio>
            <el-radio :label="2" border>
              <font-awesome-icon
                :icon="['fas', 'user-friends']"
                style="margin-right: 6px"
              />
              选择用户
            </el-radio>
            <el-radio :label="3" border>
              <font-awesome-icon
                :icon="['fas', 'user']"
                style="margin-right: 6px"
              />
              单个用户
            </el-radio>
            <el-radio :label="4" border>
              <font-awesome-icon
                :icon="['fas', 'envelope']"
                style="margin-right: 6px"
              />
              指定邮箱
            </el-radio>
          </el-radio-group>
        </el-form-item>

        <!-- 指定多个用户 -->
        <el-form-item
          v-if="emailForm.receiver_type === 2"
          label="选择用户"
          prop="receiver_ids"
        >
          <el-select
            v-model="emailForm.receiver_ids"
            multiple
            filterable
            remote
            reserve-keyword
            placeholder="请输入ID或用户名搜索用户"
            :remote-method="remoteSearchUsers"
            :loading="userSelectLoading"
            style="width: 100%"
            clearable
            collapse-tags
            collapse-tags-tooltip
            :max-collapse-tags="3"
            teleported
            popper-class="user-select-dropdown"
            @focus="handleFocus"
          >
            <template #loading>
              <div style="padding: 10px; text-align: center">
                <font-awesome-icon
                  :icon="['fas', 'spinner']"
                  spin
                  style="margin-right: 8px"
                />
                加载中...
              </div>
            </template>
            <el-option
              v-for="item in userOptions"
              :key="item.id"
              :label="`${item.username} (${item.email})`"
              :value="item.id"
            >
              <div style="display: flex; gap: 10px; align-items: center">
                <el-avatar :size="28" :src="item.avatar">
                  {{ item.username?.charAt(0) }}
                </el-avatar>
                <span style="font-weight: 500">{{ item.username }}</span>
                <span style="font-size: 12px; color: #67c23a">
                  {{ item.email }}
                </span>
              </div>
            </el-option>
          </el-select>
          <div style="margin-top: 8px; font-size: 12px; color: #909399">
            <font-awesome-icon
              :icon="['fas', 'info-circle']"
              style="margin-right: 4px"
            />
            已选择 {{ emailForm.receiver_ids.length }} 个用户
          </div>
        </el-form-item>

        <!-- 单个用户 -->
        <el-form-item
          v-if="emailForm.receiver_type === 3"
          label="选择用户"
          prop="receiver_id"
        >
          <el-select
            v-model="emailForm.receiver_id"
            filterable
            remote
            reserve-keyword
            placeholder="请输入ID或用户名搜索用户"
            :remote-method="remoteSearchUsers"
            :loading="userSelectLoading"
            style="width: 100%"
            clearable
            teleported
            popper-class="user-select-dropdown"
            @focus="handleFocus"
          >
            <template #loading>
              <div style="padding: 10px; text-align: center">
                <font-awesome-icon
                  :icon="['fas', 'spinner']"
                  spin
                  style="margin-right: 8px"
                />
                加载中...
              </div>
            </template>
            <el-option
              v-for="item in userOptions"
              :key="item.id"
              :label="`${item.username} (${item.email})`"
              :value="item.id"
            >
              <div style="display: flex; gap: 10px; align-items: center">
                <el-avatar :size="28" :src="item.avatar">
                  {{ item.username?.charAt(0) }}
                </el-avatar>
                <span style="font-weight: 500">{{ item.username }}</span>
                <span style="font-size: 12px; color: #67c23a">
                  {{ item.email }}
                </span>
              </div>
            </el-option>
          </el-select>
        </el-form-item>

        <!-- 指定邮箱地址 -->
        <el-form-item
          v-if="emailForm.receiver_type === 4"
          label="邮箱地址"
          prop="receiver_emails"
        >
          <el-input
            v-model="emailForm.receiver_emails"
            type="textarea"
            :rows="3"
            placeholder="请输入邮箱地址，多个邮箱用逗号、分号或换行分隔&#10;例如：user1@example.com, user2@example.com"
            maxlength="2000"
            show-word-limit
          />
          <div style="margin-top: 8px; font-size: 12px; color: #909399">
            <font-awesome-icon
              :icon="['fas', 'info-circle']"
              style="margin-right: 4px"
            />
            支持多个邮箱地址，用逗号、分号或换行分隔
          </div>
        </el-form-item>

        <el-form-item label="邮件标题" prop="email_title">
          <el-input
            v-model="emailForm.email_title"
            placeholder="请输入邮件标题"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>

        <el-form-item label="邮件内容" prop="email_content">
          <el-input
            v-model="emailForm.email_content"
            type="textarea"
            :rows="8"
            placeholder="请输入邮件内容"
            maxlength="5000"
            show-word-limit
          />
        </el-form-item>

        <el-divider content-position="left">
          <span class="divider-title">
            <font-awesome-icon :icon="['fas', 'magic']" class="divider-icon" />
            AI 智能生成
          </span>
        </el-divider>

        <el-form-item>
          <div class="ai-generator">
            <div class="ai-content">
              <div class="ai-input-container">
                <div class="ai-input-wrapper">
                  <el-input
                    v-model="emailAiPrompt"
                    placeholder="描述您需要的邮件内容，例如：'系统升级通知邮件'"
                    :disabled="emailAiGenerating"
                    class="ai-prompt-input"
                  >
                    <template #prefix>
                      <font-awesome-icon
                        :icon="['fas', 'lightbulb']"
                        class="input-icon"
                      />
                    </template>
                  </el-input>
                </div>

                <el-button
                  :loading="emailAiGenerating"
                  class="ai-generate-btn"
                  @click="generateEmailContentForDialog"
                >
                  <font-awesome-icon
                    :icon="['fas', 'magic']"
                    class="btn-icon"
                  />
                  AI生成
                </el-button>
              </div>

              <div v-if="emailAiGenerating" class="ai-generating">
                <div class="ai-generating-indicator">
                  <div class="typing-dots">
                    <span />
                    <span />
                    <span />
                  </div>
                  <div class="ai-status">智能创作中</div>
                </div>
                <el-progress
                  :percentage="emailAiProgress"
                  :indeterminate="true"
                  class="ai-progress"
                />
              </div>
            </div>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="emailDialogVisible = false">取消</el-button>
          <el-button
            type="primary"
            :loading="emailSending"
            @click="submitEmailForm"
          >
            <font-awesome-icon :icon="['fas', 'paper-plane']" class="mr-1" />
            发送邮件
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch, h } from "vue";
import {
  Search,
  Delete,
  Edit,
  View,
  Plus,
  Refresh,
  Loading,
  MagicStick
} from "@element-plus/icons-vue";
import { ElMessageBox, ElMessage } from "element-plus";
import { message } from "@/utils/message";
import dayjs from "dayjs";
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
  sendNoticeEmail,
  CATEGORY_TYPES,
  NOTICE_STATUS,
  PRIORITY_LEVELS,
  NOTICE_VISIBILITY_OPTIONS,
  NOTICE_VISIBILITY_MAP,
  NoticeParams,
  NoticeData,
  NoticeCreateData
} from "@/api/notice";
import { getUserList } from "@/api/user";
import { getRoleList } from "@/api/role";
import { sendEmail } from "@/api/emailRecord";
import debounce from "lodash/debounce";
import EmailRecordTable from "./components/EmailRecordTable.vue";
import { useUserStoreHook } from "@/store/modules/user";

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
const userSearchKeyword = ref("");

// 邮件通知相关变量
const emailSettings = reactive({
  enabled: false,
  title: "",
  content: ""
});

// AI生成相关变量
const aiPrompt = ref("");
const aiGenerating = ref(false);
const aiProgress = ref(0);

// 邮件发送弹窗相关变量
const emailDialogVisible = ref(false);
const emailFormRef = ref<any>(null);
const emailSending = ref(false);
const currentNoticeForEmail = ref<any>(null);

// 邮件表单数据
const emailForm = reactive({
  receiver_type: 1, // 1-全部用户, 2-指定用户, 3-单个用户, 4-指定邮箱
  receiver_ids: [] as number[],
  receiver_id: undefined as number | undefined,
  receiver_emails: "", // 直接输入的邮箱地址
  email_title: "",
  email_content: ""
});

// 邮件表单验证规则
const emailRules = reactive({
  receiver_type: [
    { required: true, message: "请选择接收方式", trigger: "change" }
  ],
  receiver_ids: [
    {
      validator: (rule: any, value: any, callback: any) => {
        if (emailForm.receiver_type === 2 && (!value || value.length === 0)) {
          callback(new Error("请至少选择一个用户"));
        } else {
          callback();
        }
      },
      trigger: "change"
    }
  ],
  receiver_id: [
    {
      validator: (rule: any, value: any, callback: any) => {
        if (emailForm.receiver_type === 3 && !value) {
          callback(new Error("请选择一个用户"));
        } else {
          callback();
        }
      },
      trigger: "change"
    }
  ],
  receiver_emails: [
    {
      validator: (rule: any, value: any, callback: any) => {
        if (emailForm.receiver_type === 4) {
          if (!value || value.trim() === "") {
            callback(new Error("请输入邮箱地址"));
            return;
          }

          // 验证邮箱格式
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          const emails = value
            .split(/[,;\n]/)
            .map((e: string) => e.trim())
            .filter((e: string) => e);

          for (const email of emails) {
            if (!emailRegex.test(email)) {
              callback(new Error(`邮箱格式不正确: ${email}`));
              return;
            }
          }

          callback();
        } else {
          callback();
        }
      },
      trigger: "blur"
    }
  ],
  email_title: [
    { required: true, message: "请输入邮件标题", trigger: "blur" },
    { min: 1, max: 200, message: "标题长度应为1-200个字符", trigger: "blur" }
  ],
  email_content: [
    { required: true, message: "请输入邮件内容", trigger: "blur" },
    { min: 1, max: 5000, message: "内容长度应为1-5000个字符", trigger: "blur" }
  ]
});

// 邮件AI生成相关变量
const emailAiPrompt = ref("");
const emailAiGenerating = ref(false);
const emailAiProgress = ref(0);

// 远程搜索用户函数
const remoteSearchUsers = debounce(async query => {
  // 如果有查询内容，才进行搜索
  if (query && query.trim() !== "") {
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
        console.error("获取用户列表失败:", res.msg);
      }
    } catch (error) {
      console.error("搜索用户时出错:", error);
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
    message("请先输入AI提示词", { type: "warning" });
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
    if (aiPrompt.value.includes("系统更新")) {
      emailSettings.title = "系统功能升级通知";
      emailSettings.content = `尊敬的用户：

我们很高兴地通知您，我们的系统已完成重要升级。此次更新包含多项功能改进和性能优化，旨在提升您的使用体验。

主要更新内容：
1. 用户界面全面优化，操作更加便捷
2. 新增数据分析功能，助您更好地把握业务动态
3. 系统性能提升，响应速度提高约30%
4. 修复了若干已知问题，系统更加稳定可靠

更新已于${dayjs().format("YYYY年MM月DD日")}正式上线，您无需进行任何操作即可享受新功能。

如有任何问题或建议，欢迎随时与我们联系。

感谢您一直以来对我们的支持与信任！

此致
系统管理团队`;
    } else {
      emailSettings.title = `关于"${noticeForm.title || "公告"}"的通知`;
      emailSettings.content = `尊敬的用户：

我们需要向您通知以下重要信息：

${noticeForm.content || "（这里将显示公告内容）"}

如有任何疑问，请随时联系我们的客户服务团队。

此致
${mockUsers.find(u => u.id === noticeForm.publisher_id)?.username || "系统管理员"}`;
    }

    message("AI内容生成成功", { type: "success" });
  } catch (error) {
    console.error("AI生成内容时出错:", error);
    message("生成内容失败，请稍后重试", { type: "error" });
  } finally {
    aiGenerating.value = false;
    aiProgress.value = 100;
  }
};

// 重置邮件设置
const resetEmailSettings = () => {
  emailSettings.enabled = false;
  emailSettings.title = "";
  emailSettings.content = "";
  aiPrompt.value = "";
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
  { id: 1, name: "已发布", color: "#52c41a" }
];

// 角色选项（从API获取）
const roleOptions = ref<any[]>([]);
const roleLoading = ref(false);

// 按钮尺寸
const buttonSize = ref<"" | "default" | "small" | "large">("small");

// 所有公告数据和本地分页后的数据
const allNoticeData = ref<any[]>([]);
const noticeData = ref<any[]>([]);

// 分页设置
// Tab切换
const activeTab = ref("notice");

const currentPage = ref(1);
const pageSize = ref(5);
const total = ref(0);

// 搜索表单
const searchForm = reactive<NoticeParams>({
  title: "",
  category_type: undefined,
  status: undefined,
  priority: undefined,
  visibility: undefined,
  is_top: undefined
});

// 表格数据与加载状态
const tableLoading = ref(false);
const selectedNotices = ref<any[]>([]);

// 获取用户store
const userStore = useUserStoreHook();

// 对话框相关状态
const noticeDialogVisible = ref(false);
const dialogTitle = ref("新增公告");
const noticeFormRef = ref<any>(null);
const submitting = ref(false);

// 表单数据
const noticeForm = reactive<NoticeCreateData>({
  title: "",
  content: "",
  category_type: CATEGORY_TYPES.SYSTEM_UPDATE,
  publisher_id: userStore.id || 1, // 从store获取当前用户ID
  visibility: "public", // 默认公开
  target_user_ids: [], // 指定用户ID列表
  target_role_ids: [], // 指定角色ID列表
  status: NOTICE_STATUS.DRAFT,
  priority: PRIORITY_LEVELS.NORMAL,
  is_top: false,
  publish_time: dayjs().format("YYYY-MM-DD HH:mm:ss")
});

// 表单验证规则
const noticeRules = {
  title: [{ required: true, message: "请输入公告标题", trigger: "blur" }],
  content: [{ required: true, message: "请输入公告内容", trigger: "blur" }],
  category_type: [
    { required: true, message: "请选择公告分类", trigger: "change" }
  ],
  visibility: [{ required: true, message: "请选择可见性", trigger: "change" }],
  target_user_ids: [
    {
      required: false,
      message: "请选择接收用户",
      trigger: "change",
      validator: (rule: any, value: any, callback: any) => {
        if (
          noticeForm.visibility === "specific_users" &&
          (!value || value.length === 0)
        ) {
          callback(new Error("请选择至少一个接收用户"));
        } else {
          callback();
        }
      }
    }
  ],
  target_role_ids: [
    {
      required: false,
      message: "请选择角色",
      trigger: "change",
      validator: (rule: any, value: any, callback: any) => {
        if (
          noticeForm.visibility === "specific_roles" &&
          (!value || value.length === 0)
        ) {
          callback(new Error("请选择至少一个角色"));
        } else {
          callback();
        }
      }
    }
  ]
};

// 监听 visibility 变化，重置目标选择
watch(
  () => noticeForm.visibility,
  (newVal, oldVal) => {
    // 当 visibility 改变时，清空之前的目标选择
    if (newVal !== oldVal) {
      noticeForm.target_user_ids = [];
      noticeForm.target_role_ids = [];

      // 如果切换到需要选择用户或角色的模式，加载数据
      if (newVal === "specific_users") {
        loadDefaultUserOptions();
      } else if (newVal === "specific_roles") {
        loadRoleOptions();
      }
    }
  }
);

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
      console.error("获取用户列表失败:", res.msg);
    }
  } catch (error) {
    console.error("加载用户列表出错:", error);
  } finally {
    userSelectLoading.value = false;
  }
};

// 加载角色列表
const loadRoleOptions = async () => {
  if (roleOptions.value.length > 0) return; // 已有角色数据，不重新加载

  roleLoading.value = true;
  try {
    const res: any = await getRoleList({
      page_size: 200
    });

    if (res && res.code === 200 && res.data) {
      roleOptions.value = res.data.list || [];
    } else {
      console.error("获取角色列表失败:", res.msg);
      message("获取角色列表失败", { type: "error" });
    }
  } catch (error) {
    console.error("加载角色列表出错:", error);
    message("加载角色列表失败", { type: "error" });
  } finally {
    roleLoading.value = false;
  }
};

// 获取公告列表
const fetchNoticeList = async () => {
  tableLoading.value = true;
  try {
    const params: NoticeParams = {
      page: 1,
      page_size: 100,
      ...searchForm,
      sort_field: "publish_time",
      sort_order: "desc"
      // 不需要传 is_admin 参数，后端会从 Token 中的角色自动判断
    };

    // 过滤掉空值
    Object.keys(params).forEach(key => {
      if (
        params[key] === "" ||
        params[key] === null ||
        params[key] === undefined
      ) {
        delete params[key];
      }
    });

    const res = (await getNoticeList(params)) as ApiResponse;
    if (res.code === 200) {
      // 处理返回的数据
      const list = res.data.data || [];

      // 格式化数据
      allNoticeData.value = list.map(item => formatNoticeItem(item));
      total.value = res.data.total || list.length;

      // 本地分页
      updatePageData();
    } else {
      message(`获取公告列表失败: ${res.message}`, { type: "error" });
    }
  } catch (error) {
    console.error("获取公告列表出错:", error);
    message("获取公告列表失败，请稍后重试", { type: "error" });
  } finally {
    tableLoading.value = false;
  }
};

// 格式化公告数据
const formatNoticeItem = (item: any) => {
  // 获取类型、状态、优先级显示信息
  const typeInfo =
    typeOptions.find(t => t.id === item.category_type) || typeOptions[4]; // 默认其他
  const statusInfo =
    statusOptions.find(s => s.id === item.status) || statusOptions[0]; // 默认草稿
  const priorityInfo =
    priorityOptions.find(p => p.id === item.priority) || priorityOptions[0]; // 默认普通

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
    visibility: item.visibility, // 可见性
    is_pinned: item.is_top ? 1 : 0,
    delete_time: item.delete_time, // 添加软删除时间字段
    publish_time: item.publish_time,
    publisher: publisher,
    target_users: item.target_users || [], // 目标用户列表
    target_roles: item.target_roles || [], // 目标角色列表
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
  return dayjs(dateStr).format("YYYY-MM-DD HH:mm");
};

// 获取可见性标签类型
const getVisibilityTagType = (visibility: string) => {
  const typeMap: Record<string, any> = {
    public: "success",
    login_required: "info",
    specific_users: "warning",
    specific_roles: "primary"
  };
  return typeMap[visibility] || "info";
};

// 处理查看公告
const handleViewNotice = (row: any) => {
  // 暂时简单实现，使用弹窗
  ElMessageBox.alert(row.content, row.title, {
    dangerouslyUseHTMLString: true,
    confirmButtonText: "关闭"
  });
};

// 处理删除公告
const handleDeleteNotice = (row: any) => {
  // 创建响应式变量用于存储复选框状态
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h("div", null, [
      h("p", null, `确定要删除公告 "${row.title}" 吗？`),
      h(
        "div",
        { style: "margin-top: 16px; display: flex; align-items: center;" },
        [
          h("input", {
            type: "checkbox",
            style:
              "width: 16px; height: 16px; margin-right: 8px; cursor: pointer;",
            checked: isRealDelete.value,
            onInput: event => {
              isRealDelete.value = (event.target as HTMLInputElement).checked;
            }
          }),
          h("span", null, "永久删除（否则为软删除，可在回收站恢复）")
        ]
      )
    ]);
  };

  ElMessageBox({
    title: "删除确认",
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: "确定删除",
    cancelButtonText: "取消",
    type: "warning",
    beforeClose: (action, instance, done) => {
      if (action === "confirm") {
        instance.confirmButtonLoading = true;
        deleteNotice(row.id, isRealDelete.value)
          .then(res => {
            if ((res as ApiResponse).code === 200) {
              message(
                isRealDelete.value ? "永久删除公告成功" : "删除公告成功",
                { type: "success" }
              );
              fetchNoticeList();
            } else {
              message((res as ApiResponse).message || "删除公告失败", {
                type: "error"
              });
            }
          })
          .catch(() => {
            message("删除公告失败，请稍后重试", { type: "error" });
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
    message("请至少选择一条公告", { type: "warning" });
    return;
  }

  const noticeIds = selectedNotices.value.map(item => item.id);
  const isRealDelete = ref(false);

  // 使用render函数创建自定义内容
  const renderContent = () => {
    return h("div", null, [
      h("p", null, `确定要删除选中的 ${noticeIds.length} 条公告吗？`),
      h(
        "div",
        { style: "margin-top: 16px; display: flex; align-items: center;" },
        [
          h("input", {
            type: "checkbox",
            style:
              "width: 16px; height: 16px; margin-right: 8px; cursor: pointer;",
            checked: isRealDelete.value,
            onInput: event => {
              isRealDelete.value = (event.target as HTMLInputElement).checked;
            }
          }),
          h("span", null, "永久删除（否则为软删除，可在回收站恢复）")
        ]
      )
    ]);
  };

  ElMessageBox({
    title: "批量删除确认",
    message: renderContent(),
    showCancelButton: true,
    confirmButtonText: "确定删除",
    cancelButtonText: "取消",
    type: "warning",
    beforeClose: (action, instance, done) => {
      if (action === "confirm") {
        instance.confirmButtonLoading = true;
        batchDeleteNotice(noticeIds, isRealDelete.value)
          .then(res => {
            if ((res as ApiResponse).code === 200) {
              message(
                isRealDelete.value ? "永久删除公告成功" : "批量删除公告成功",
                { type: "success" }
              );
              fetchNoticeList();
              selectedNotices.value = [];
            } else {
              message((res as ApiResponse).message || "批量删除公告失败", {
                type: "error"
              });
            }
          })
          .catch(() => {
            message("批量删除公告失败，请稍后重试", { type: "error" });
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
    const res = (await publishNotice(row.id)) as ApiResponse;
    if (res.code === 200) {
      message("发布公告成功", { type: "success" });
      fetchNoticeList();
    } else {
      message(res.message || "发布公告失败", { type: "error" });
    }
  } catch (error) {
    console.error("发布公告出错:", error);
    message("发布公告失败，请稍后重试", { type: "error" });
  }
};

// 处理撤回公告
const handleRevokeNotice = async (row: any) => {
  try {
    const res = (await revokeNotice(row.id)) as ApiResponse;
    if (res.code === 200) {
      message("撤回公告成功", { type: "success" });
      fetchNoticeList();
    } else {
      message(res.message || "撤回公告失败", { type: "error" });
    }
  } catch (error) {
    console.error("撤回公告出错:", error);
    message("撤回公告失败，请稍后重试", { type: "error" });
  }
};

// 处理置顶/取消置顶
const handleTogglePin = async (row: any) => {
  try {
    const isPinned = !row.is_pinned;
    const res = (await setNoticeTopStatus(row.id, isPinned)) as ApiResponse;
    if (res.code === 200) {
      message(isPinned ? "置顶公告成功" : "取消置顶成功", { type: "success" });
      fetchNoticeList();
    } else {
      message(res.message || (isPinned ? "置顶公告失败" : "取消置顶失败"), {
        type: "error"
      });
    }
  } catch (error) {
    console.error("操作公告置顶状态出错:", error);
    message("操作失败，请稍后重试", { type: "error" });
  }
};

// 处理恢复公告
const handleRestoreNotice = async (row: any) => {
  try {
    const res = (await restoreNotice(row.id)) as ApiResponse;
    if (res.code === 200) {
      message("恢复公告成功", { type: "success" });
      fetchNoticeList();
    } else {
      message(res.message || "恢复公告失败", { type: "error" });
    }
  } catch (error) {
    console.error("恢复公告出错:", error);
    message("恢复公告失败，请稍后重试", { type: "error" });
  }
};

// 在script部分顶部添加当前编辑ID变量
const currentEditId = ref<number | null>(null);

// 处理新增公告
const handleAddNotice = () => {
  dialogTitle.value = "新增公告";
  resetNoticeForm();
  noticeDialogVisible.value = true;
  // 预加载用户数据
  loadDefaultUserOptions();
};

// 处理编辑公告
const handleEditNotice = (row: any) => {
  dialogTitle.value = "编辑公告";
  resetNoticeForm();

  // 加载公告数据到表单
  noticeForm.title = row.title;
  noticeForm.content = row.content;
  noticeForm.category_type = row.type_id;
  noticeForm.status = row.status_id;
  noticeForm.priority = row.priority_id;
  noticeForm.is_top = !!row.is_pinned;
  noticeForm.publish_time =
    row.publish_time || dayjs().format("YYYY-MM-DD HH:mm:ss");

  // 加载 visibility 数据
  noticeForm.visibility = row.visibility || "public";

  // 加载目标用户和角色数据
  if (row.target_users && Array.isArray(row.target_users)) {
    noticeForm.target_user_ids = row.target_users.map(
      (item: any) => item.target_id
    );
    // 如果有指定用户，加载用户列表
    if (noticeForm.target_user_ids.length > 0) {
      loadDefaultUserOptions();
    }
  }

  if (row.target_roles && Array.isArray(row.target_roles)) {
    noticeForm.target_role_ids = row.target_roles.map(
      (item: any) => item.target_id
    );
    // 如果有指定角色，加载角色列表
    if (noticeForm.target_role_ids.length > 0) {
      loadRoleOptions();
    }
  }

  // 保存当前编辑的ID
  currentEditId.value = row.id;

  // 打开对话框
  noticeDialogVisible.value = true;
};

// 重置表单
const resetNoticeForm = () => {
  noticeForm.title = "";
  noticeForm.content = "";
  noticeForm.category_type = CATEGORY_TYPES.SYSTEM_UPDATE;
  noticeForm.publisher_id = userStore.id || 1; // 重置时重新设置当前用户ID
  noticeForm.visibility = "public"; // 重置为公开
  noticeForm.target_user_ids = []; // 重置指定用户
  noticeForm.target_role_ids = []; // 重置指定角色
  noticeForm.status = NOTICE_STATUS.DRAFT;
  noticeForm.priority = PRIORITY_LEVELS.NORMAL;
  noticeForm.is_top = false;
  noticeForm.publish_time = dayjs().format("YYYY-MM-DD HH:mm:ss");
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

      // 确保 visibility 和目标数据正确发送
      // 根据 visibility 清理不需要的字段
      if (
        submitData.visibility === "public" ||
        submitData.visibility === "login_required"
      ) {
        // 公开或登录可见不需要目标数据
        submitData.target_user_ids = [];
        submitData.target_role_ids = [];
      } else if (submitData.visibility === "specific_users") {
        // 指定用户时清空角色数据
        submitData.target_role_ids = [];
      } else if (submitData.visibility === "specific_roles") {
        // 指定角色时清空用户数据
        submitData.target_user_ids = [];
      }

      let res;
      if (currentEditId.value) {
        // 编辑现有公告
        res = (await updateNotice(
          currentEditId.value,
          submitData
        )) as ApiResponse;
      } else {
        // 创建新公告
        res = (await createNotice(submitData)) as ApiResponse;
      }

      if (res.code === 200) {
        // 如果开启了邮件通知，单独处理邮件发送
        if (emailSettings.enabled) {
          // 这里应该调用发送邮件的API，与公告数据分开处理
          const emailData = {
            notice_id: res.data?.id || currentEditId.value,
            title: emailSettings.title || noticeForm.title,
            content: emailSettings.content || noticeForm.content,
            receivers: noticeForm.target_user_ids || []
          };

          console.log("准备发送邮件通知:", emailData);
          // 实际项目中这里应该调用发送邮件的API
          // await sendEmailNotification(emailData);
        }

        message(currentEditId.value ? "更新公告成功" : "创建公告成功", {
          type: "success"
        });
        noticeDialogVisible.value = false;
        resetEmailSettings();
        currentEditId.value = null;
        fetchNoticeList();
      } else {
        message(
          res.message ||
            (currentEditId.value ? "更新公告失败" : "创建公告失败"),
          { type: "error" }
        );
      }
    } catch (error) {
      console.error(
        currentEditId.value ? "更新公告出错" : "创建公告出错:",
        error
      );
      message(
        currentEditId.value
          ? "更新公告失败，请稍后重试"
          : "创建公告失败，请稍后重试",
        { type: "error" }
      );
    } finally {
      submitting.value = false;
    }
  });
};

// 打开邮件发送弹窗(独立功能,不依赖公告)
const handleOpenEmailDialog = () => {
  currentNoticeForEmail.value = null;

  // 重置表单
  emailForm.receiver_type = 1;
  emailForm.receiver_ids = [];
  emailForm.receiver_id = undefined;
  emailForm.receiver_emails = "";
  emailForm.email_title = "";
  emailForm.email_content = "";
  emailAiPrompt.value = "";

  emailDialogVisible.value = true;
};

// 处理发送邮件(从公告行触发)
const handleSendEmail = (row: any) => {
  currentNoticeForEmail.value = row;

  // 重置表单
  emailForm.receiver_type = 1;
  emailForm.receiver_ids = [];
  emailForm.receiver_id = undefined;
  emailForm.receiver_emails = "";
  emailForm.email_title = `【公告通知】${row.title}`;
  emailForm.email_content = row.content;
  emailAiPrompt.value = "";

  // 根据公告的可见性预设接收对象
  if (row.visibility === "public" || row.visibility === "login_required") {
    emailForm.receiver_type = 1; // 全部用户
  } else if (
    row.visibility === "specific_users" &&
    row.target_users &&
    row.target_users.length > 0
  ) {
    emailForm.receiver_type = 2; // 指定用户
    emailForm.receiver_ids = row.target_users.map(
      (item: any) => item.target_id
    );
  } else if (row.visibility === "specific_roles") {
    emailForm.receiver_type = 1; // 角色用户，暂时按全部处理
  }

  emailDialogVisible.value = true;
};

// 处理接收方式变化
const handleReceiverTypeChange = () => {
  // 清空所有选择
  emailForm.receiver_ids = [];
  emailForm.receiver_id = undefined;
  emailForm.receiver_emails = "";

  // 清除验证
  if (emailFormRef.value) {
    emailFormRef.value.clearValidate([
      "receiver_ids",
      "receiver_id",
      "receiver_emails"
    ]);
  }
};

// 为邮件弹窗生成AI内容
const generateEmailContentForDialog = async () => {
  if (!emailAiPrompt.value.trim()) {
    message("请先输入AI提示词", { type: "warning" });
    return;
  }

  emailAiGenerating.value = true;
  emailAiProgress.value = 0;

  try {
    // 模拟AI生成进度
    const interval = setInterval(() => {
      emailAiProgress.value += Math.floor(Math.random() * 15);
      if (emailAiProgress.value >= 100) {
        clearInterval(interval);
        emailAiProgress.value = 100;
      }
    }, 300);

    // 这里是模拟生成，实际项目中可以调用真实的AI接口
    await new Promise(resolve => setTimeout(resolve, 2000));

    // 根据不同提示词模拟不同的结果
    if (
      emailAiPrompt.value.includes("系统") ||
      emailAiPrompt.value.includes("升级")
    ) {
      emailForm.email_title = "【重要通知】系统功能升级通知";
      emailForm.email_content = `尊敬的用户：

您好！

我们很高兴地通知您，我们的系统已完成重要升级。此次更新包含多项功能改进和性能优化，旨在提升您的使用体验。

主要更新内容：
1. 用户界面全面优化，操作更加便捷
2. 新增数据分析功能，助您更好地把握业务动态
3. 系统性能提升，响应速度提高约30%
4. 修复了若干已知问题，系统更加稳定可靠

更新已于${dayjs().format("YYYY年MM月DD日")}正式上线，您无需进行任何操作即可享受新功能。

如有任何问题或建议，欢迎随时与我们联系。

感谢您一直以来对我们的支持与信任！

此致
系统管理团队`;
    } else {
      const noticeTitle = currentNoticeForEmail.value?.title || "重要通知";
      const noticeContent = currentNoticeForEmail.value?.content || "";

      emailForm.email_title = `【公告通知】${noticeTitle}`;
      emailForm.email_content = `尊敬的用户：

您好！

我们需要向您通知以下重要信息：

${noticeContent || "请在此填写通知内容..."}

如有任何疑问，请随时联系我们的客户服务团队。

此致
系统管理员`;
    }

    message("AI内容生成成功", { type: "success" });
  } catch (error) {
    console.error("AI生成内容时出错:", error);
    message("生成内容失败，请稍后重试", { type: "error" });
  } finally {
    emailAiGenerating.value = false;
    emailAiProgress.value = 100;
  }
};

// 提交邮件发送表单
const submitEmailForm = async () => {
  if (!emailFormRef.value) return;

  await emailFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    emailSending.value = true;
    try {
      // 从store获取当前登录用户ID
      const currentUserId = userStore.id || 1;

      // 准备邮件数据
      const emailData: any = {
        sender_id: currentUserId,
        title: emailForm.email_title,
        content: emailForm.email_content,
        receiver_type: emailForm.receiver_type
      };

      // 如果有关联公告,添加notice_id
      if (currentNoticeForEmail.value?.notice_id) {
        emailData.notice_id = currentNoticeForEmail.value.notice_id;
      }

      // 根据接收方式添加接收者信息
      if (emailForm.receiver_type === 2) {
        // 指定多个用户
        emailData.receiver_ids = emailForm.receiver_ids;
      } else if (emailForm.receiver_type === 3) {
        // 单个用户
        emailData.receiver_ids = [emailForm.receiver_id];
      } else if (emailForm.receiver_type === 4) {
        // 指定邮箱地址
        const emails = emailForm.receiver_emails
          .split(/[,;\n]/)
          .map((e: string) => e.trim())
          .filter((e: string) => e);
        emailData.receiver_emails = emails;
      }

      console.log("准备发送邮件:", emailData);

      // 调用邮件发送API
      const res = (await sendEmail(emailData)) as any;

      if (res.code === 200) {
        message("邮件发送成功", { type: "success" });
        emailDialogVisible.value = false;

        // 重置表单
        emailForm.receiver_type = 1;
        emailForm.receiver_ids = [];
        emailForm.receiver_id = undefined;
        emailForm.receiver_emails = "";
        emailForm.email_title = "";
        emailForm.email_content = "";
        emailAiPrompt.value = "";
      } else {
        message(res.message || "邮件发送失败", { type: "error" });
      }
    } catch (error) {
      console.error("发送邮件时出错:", error);
      message("发送邮件失败，请稍后重试", { type: "error" });
    } finally {
      emailSending.value = false;
    }
  });
};

onMounted(() => {
  fetchNoticeList();
});
</script>

<style lang="scss" scoped>
@keyframes typing-animation {
  0%,
  100% {
    opacity: 0.6;
    transform: translateY(0);
  }

  50% {
    opacity: 1;
    transform: translateY(-3px);
  }
}

// 移动端适配
@media (width <=768px) {
  :deep(.el-dialog) {
    width: 95% !important;
    margin: 0 auto;
  }

  :deep(.el-form-item__label) {
    width: 100% !important;
    margin-bottom: 8px;
    text-align: left;
  }

  :deep(.el-form-item__content) {
    margin-left: 0 !important;
  }

  :deep(.el-radio.is-bordered) {
    padding: 8px 12px;
    margin-right: 8px;
    margin-bottom: 8px;
    font-size: 13px;
  }

  .user-option {
    padding: 6px 2px;

    :deep(.el-avatar) {
      width: 24px !important;
      height: 24px !important;
      font-size: 12px;
    }

    .user-info {
      margin-left: 8px;

      .username {
        font-size: 13px;
      }

      .user-detail {
        flex-direction: column;
        gap: 2px;
        font-size: 11px;
      }
    }
  }
}

.notice-container {
  padding: 0;

  .card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    .header-title {
      display: flex;
      align-items: center;
      margin: 0;
      font-size: 16px;
      font-weight: 600;
    }
  }

  .search-form {
    margin-bottom: 16px;

    .search-area {
      padding: 16px 12px 8px;
      background-color: #f9fafc;
      border-radius: 4px;

      .search-items {
        .search-btn-col {
          display: flex;
          align-items: flex-end;
          margin-bottom: 18px;

          .search-btn {
            justify-content: center;
            width: 100%;
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
      padding: 6px 10px;
      font-size: 12px;
    }

    .action-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 36px;
      height: 36px;
      padding: 0;
      border-radius: 4px;

      &:hover {
        transition: transform 0.2s;
        transform: scale(1.05);
      }
    }
  }

  .table-container {
    margin-bottom: 16px;

    :deep(.el-table__header) th {
      height: 38px;
      padding: 6px 0;
    }

    :deep(.el-table__body) td {
      padding: 4px 0;
    }

    .notice-title {
      display: flex;
      gap: 4px;
      align-items: center;
      font-size: 14px;
      font-weight: 500;

      &.is-pinned {
        color: #f56c6c;

        .pin-icon {
          color: #f56c6c;
          transform: rotate(45deg);
        }
      }

      .notice-title-text {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .meta-item {
        font-size: 12px;
        color: #909399;
      }
    }

    .notice-content {
      overflow: hidden;
      font-size: 12px;
      line-height: 1.4;
      color: #606266;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .view-count {
      display: flex;
      gap: 3px;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      color: #909399;
    }

    .notice-type {
      .el-tag {
        height: 22px;
        padding: 0 6px;
        font-size: 11px;
        line-height: 22px;
        border-radius: 2px;
      }
    }

    .priority-badge {
      display: inline-block;
      padding: 1px 6px;
      font-size: 11px;
      font-weight: 500;
      border: 1px solid;
      border-radius: 2px;
    }

    .status-badge {
      display: inline-block;
      padding: 1px 6px;
      font-size: 11px;
      font-weight: 500;
      border: 1px solid;
      border-radius: 2px;
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
          margin-left: 4px;
          border-radius: 50%;

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
      gap: 4px;
      justify-content: center;

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
  gap: 4px;
  align-items: center;

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
  gap: 12px;
  justify-content: flex-end;
  padding-top: 10px;

  .el-button {
    margin-left: 10px;
  }
}

.status-tag {
  display: inline-flex;
  align-items: center;
  height: 18px;
  padding: 0 5px;
  font-size: 11px;
  border-radius: 3px;

  &.status-active {
    color: #67c23a;
    background-color: rgb(103 194 58 / 15%);
  }

  &.status-deleted {
    color: #f56c6c;
    background-color: rgb(245 108 108 / 15%);
  }
}

.user-option {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  transition: all 0.2s ease;

  :deep(.el-avatar) {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgb(102 126 234 / 30%);
  }

  .user-info {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    margin-left: 12px;

    .username {
      overflow: hidden;
      font-size: 14px;
      font-weight: 600;
      line-height: 1.4;
      color: #303133;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .user-detail {
      display: flex;
      gap: 8px;
      align-items: center;
      margin-top: 4px;
      font-size: 12px;
      line-height: 1.2;
      color: #909399;

      .user-id {
        font-weight: 500;
        color: #606266;
      }

      .user-email {
        flex: 1;
        overflow: hidden;
        font-weight: 500;
        color: #67c23a;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .user-role {
        color: #409eff;
      }
    }
  }
}

.ai-generator {
  padding: 18px;
  margin-top: 15px;
  background: linear-gradient(135deg, #fff7ed 0%, #fdf2f8 50%, #f5f3ff 100%);
  border: 1px solid #f5d0fe;
  border-radius: 10px;
  box-shadow: 0 6px 16px rgb(244 114 182 / 8%);

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
          box-shadow:
            0 0 0 1px #d8b4fe,
            0 4px 10px rgb(216 180 254 / 20%);
        }
      }

      :deep(.el-input__prefix) {
        margin-right: 8px;
        color: #f59e0b;
      }
    }

    .ai-generate-btn {
      height: 40px;
      padding: 0 20px;
      font-size: 14px;
      font-weight: 500;
      color: white;
      background: linear-gradient(45deg, #f59e0b 0%, #ec4899 50%, #8b5cf6 100%);
      border: none;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgb(139 92 246 / 30%);
      transition: all 0.3s ease;

      &:hover {
        background: linear-gradient(
          45deg,
          #f59e0b 0%,
          #ec4899 50%,
          #8b5cf6 100%
        );
        box-shadow: 0 6px 15px rgb(139 92 246 / 40%);
        transform: translateY(-2px);
      }

      &:active {
        box-shadow: 0 2px 8px rgb(139 92 246 / 30%);
        transform: translateY(0);
      }

      &:focus,
      &:hover,
      &:active {
        color: white;
        background-color: transparent;
        border: none;
        outline: none;
      }

      .btn-icon {
        margin-right: 5px;
        font-size: 15px;
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
          background: linear-gradient(
            45deg,
            #f59e0b 0%,
            #ec4899 50%,
            #8b5cf6 100%
          );
          border-radius: 50%;
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
        margin-left: 10px;
        font-size: 14px;
        font-weight: 500;
        color: transparent;
        background: linear-gradient(
          45deg,
          #f59e0b 0%,
          #ec4899 50%,
          #8b5cf6 100%
        );
        background-clip: text;
        -webkit-text-fill-color: transparent;
      }
    }

    .ai-progress {
      :deep(.el-progress-bar__inner) {
        background: linear-gradient(
          45deg,
          #f59e0b 0%,
          #ec4899 50%,
          #8b5cf6 100%
        );
      }
    }
  }
}

.email-settings-container {
  padding: 16px;
  margin-bottom: 10px;
  background-color: #f8f9fc;
  border: 1px solid #ebeef5;
  border-radius: 8px;
  box-shadow: 0 2px 12px 0 rgb(0 0 0 / 2%);

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
  display: flex;
  align-items: center;
  padding: 0 15px;
  background-color: #fff;
}

// 邮件弹窗样式
:deep(.el-dialog__header) {
  padding: 18px 24px;
  border-bottom: 1px solid #ebeef5;
}

:deep(.el-dialog__body) {
  max-height: 70vh;
  padding: 24px;
  overflow-y: auto;
}

:deep(.el-dialog__footer) {
  padding: 14px 24px;
  background-color: #fafafa;
  border-top: 1px solid #ebeef5;
}

// 接收方式单选按钮样式优化
:deep(.el-radio.is-bordered) {
  padding: 10px 16px;
  margin-right: 12px;
  margin-bottom: 8px;
  border-radius: 8px;
  transition: all 0.3s ease;

  &:hover {
    background-color: #ecf5ff;
    border-color: #409eff;
  }

  &.is-checked {
    background-color: #ecf5ff;
    border-color: #409eff;
    box-shadow: 0 2px 8px rgb(64 158 255 / 20%);
  }
}

// 用户选择器样式优化
:deep(.el-select-dropdown__item) {
  height: auto;
  padding: 0 12px;
  line-height: normal;

  &:hover {
    background-color: #f5f7fa;
  }

  &.selected {
    background-color: #ecf5ff;
  }
}

// 加载状态样式
:deep(.el-select__loading-text) {
  color: #909399;
  text-align: center;
}

// 标签折叠样式
:deep(.el-tag) {
  margin: 2px 4px 2px 0;
  border-radius: 4px;
}
</style>

<style lang="scss">
// 全局样式：修复用户选择下拉菜单被遮挡的问题
.user-select-dropdown {
  z-index: 9999 !important;
  min-width: 450px !important;
  max-width: 600px !important;

  .el-select-dropdown__item {
    height: auto !important;
    padding: 8px 12px !important;
    line-height: normal !important;
  }

  // 隐藏或修复popper箭头位置
  .el-popper__arrow {
    display: none !important;
  }
}
</style>
