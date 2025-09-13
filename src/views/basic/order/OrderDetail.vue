<template>
  <div class="order-detail" v-loading="loading">
    <el-empty v-if="!orderData && !loading" description="未找到订单数据" />

    <template v-else>
      <!-- 顶部卡片区域 -->
      <div class="order-header">
        <div class="order-status" :class="orderData?.order_status">
          <div class="status-content">
            <font-awesome-icon :icon="getStatusIcon(orderData?.order_status)" class="status-icon" />
            <div class="status-info">
              <div class="status-text">{{ getStatusText(orderData?.order_status) }}</div>
              <div class="status-desc">{{ getStatusDescription(orderData?.order_status) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="main-content">
        <div class="order-info-section">
          <!-- 订单基本信息 -->
          <el-card class="info-card" shadow="hover">
            <template #header>
              <div class="card-header">
                <font-awesome-icon :icon="['fas', 'file-invoice-dollar']" />
                <span>订单信息</span>
                <div class="order-actions">
                  <el-button link type="primary" class="copy-btn" @click="handlePrint">
                    <font-awesome-icon :icon="['fas', 'print']" />
                    <span class="copy-text">打印</span>
                  </el-button>
                </div>
              </div>
            </template>

            <div class="order-grid">
              <div class="order-item">
                <div class="item-label">订单号</div>
                <div class="item-value">
                  <div class="copy-value">
                    <span class="monospace">{{ orderData?.order_no }}</span>
                    <el-button link type="primary" class="copy-btn" title="复制"
                      @click="copyToClipboard(orderData?.order_no)">
                      <font-awesome-icon :icon="['fas', 'copy']" />
                      <span class="copy-text">复制</span>
                    </el-button>
                  </div>
                </div>
              </div>

              <div class="order-item">
                <div class="item-label">创建时间</div>
                <div class="item-value">{{ formatDateTime(orderData?.create_time) }}</div>
              </div>

              <div class="order-item">
                <div class="item-label">用户信息</div>
                <div class="item-value">
                  <el-tag size="small" effect="plain" class="user-tag">
                    <font-awesome-icon :icon="['fas', 'user']" />
                    {{ orderData?.user_name || '用户' + orderData?.user_id }}
                  </el-tag>
                </div>
              </div>

              <div class="order-item">
                <div class="item-label">订单状态</div>
                <div class="item-value">
                  <el-tag size="small" :type="getStatusTagType(orderData?.order_status)">
                    {{ getStatusText(orderData?.order_status) }}
                  </el-tag>
                </div>
              </div>

              <div class="order-item">
                <div class="item-label">支付方式</div>
                <div class="item-value">
                  <div class="payment-tag" :class="orderData?.payment_method">
                    <font-awesome-icon :icon="getPaymentIcon(orderData?.payment_method)" />
                    {{ getPaymentText(orderData?.payment_method) }}
                  </div>
                </div>
              </div>

              <div class="order-item">
                <div class="item-label">订单金额</div>
                <div class="item-value">
                  <div class="amount-container">
                    <font-awesome-icon :icon="getPaymentIcon(orderData?.payment_method)" class="coin-icon" />
                    <span class="amount">{{ formatAmount(orderData?.order_amount) }}</span>
                    <span class="currency">{{ getCurrencySymbol(orderData?.payment_method) }}</span>
                  </div>
                </div>
              </div>

              <div class="order-item">
                <div class="item-label">商品信息</div>
                <div class="item-value">{{ orderData?.product || '-' }}</div>
              </div>

              <div class="order-item">
                <div class="item-label">更新时间</div>
                <div class="item-value">{{ formatDateTime(orderData?.update_time) }}</div>
              </div>

              <div class="order-item full-width" v-if="orderData?.remark">
                <div class="item-label">备注</div>
                <div class="item-value">{{ orderData?.remark }}</div>
              </div>

              <div class="order-item full-width" v-if="orderData?.payment_method === 'usdt'">
                <div class="item-label">收款地址</div>
                <div class="item-value">
                  <div class="copy-value">
                    <span class="monospace crypto-address">TXEeBrN3qXnLr3QJa6F87UtUWHs6xQBfdg</span>
                    <el-button link type="primary" class="copy-btn" title="复制"
                      @click="copyToClipboard('TXEeBrN3qXnLr3QJa6F87UtUWHs6xQBfdg')">
                      <font-awesome-icon :icon="['fas', 'copy']" />
                      <span class="copy-text">复制</span>
                    </el-button>
                  </div>
                </div>
              </div>

              <div class="order-item full-width" v-if="orderData?.payment_method === 'usdt'">
                <div class="item-label">交易哈希</div>
                <div class="item-value">
                  <div class="copy-value">
                    <span class="monospace crypto-address">{{ orderData?.transaction_hash ||
                      '6e46a38e8013e2229878336e75f3395a3e83d55db201c5dfb8c7af74b6d499ca' }}</span>
                    <el-button link type="primary" class="copy-btn" title="复制"
                      @click="copyToClipboard(orderData?.transaction_hash || '6e46a38e8013e2229878336e75f3395a3e83d55db201c5dfb8c7af74b6d499ca')">
                      <font-awesome-icon :icon="['fas', 'copy']" />
                      <span class="copy-text">复制</span>
                    </el-button>
                  </div>
                </div>
              </div>
            </div>
          </el-card>

          <!-- 交易流程时间线 -->
          <el-card class="info-card timeline-card" shadow="hover">
            <template #header>
              <div class="card-header">
                <font-awesome-icon :icon="['fas', 'clock-rotate-left']" />
                <span>操作日志</span>
              </div>
            </template>

            <div class="timeline-container">
              <el-timeline>
                <el-timeline-item v-for="(step, index) in getOrderTimeline()" :key="index" :type="step.type"
                  :color="step.color" :size="step.size" :icon="step.icon" :hollow="step.hollow">
                  <div class="timeline-content">
                    <div class="timeline-title">
                      <span class="title">{{ step.title }}</span>
                      <span class="time">{{ step.time }}</span>
                    </div>
                    <div class="timeline-desc" v-if="step.desc">{{ step.desc }}</div>
                    <div class="timeline-extra" v-if="step.extra">{{ step.extra }}</div>
                  </div>
                </el-timeline-item>
              </el-timeline>
            </div>
          </el-card>
        </div>
      </div>

      <!-- 底部操作栏 -->
      <div class="action-footer" v-if="orderData">
        <div class="action-buttons">
          <el-button type="warning" v-if="orderData?.order_status === 'unpaid'" @click="handlePay">
            <font-awesome-icon :icon="['fas', 'credit-card']" />
            标记支付
          </el-button>
          <el-button type="success" v-if="orderData?.order_status === 'paid' ||
            (orderData?.payment_method === 'usdt' && orderData?.order_status === 'confirming')"
            @click="handleComplete">
            <font-awesome-icon :icon="['fas', 'check']" />
            确认完成
          </el-button>
          <el-button type="danger"
            v-if="orderData?.order_status !== 'cancelled' && orderData?.order_status !== 'completed'"
            @click="handleCancel">
            <font-awesome-icon :icon="['fas', 'times']" />
            取消订单
          </el-button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: "OrderDetail"
});

import { ref, onMounted, computed } from "vue";
import { useWindowSize } from "@/hooks/useWindowSize";
import { OrderItem, updateOrder, generateMockOrders } from "@/api/order";
import { ElMessage } from "element-plus";

// 响应式设计
const { width } = useWindowSize();
const isMobile = computed(() => width.value < 768);

// 定义props
const props = defineProps({
  orderId: {
    type: Number,
    required: true
  }
});

// 扩展OrderItem类型，增加USDT交易相关字段
interface ExtendedOrderItem extends OrderItem {
  transaction_hash?: string;
  confirmation_count?: number;
  usdt_address?: string;
}

// 订单数据
const orderData = ref<ExtendedOrderItem | null>(null);
const loading = ref(false);
// 订单状态时间线
const orderTimeline = ref<{
  status: string;
  time: string;
  desc?: string;
  confirmation_count?: number;
  hash?: string;
}[]>([]);

// 格式化金额
const formatAmount = (amount?: number) => {
  return amount ? amount.toFixed(2) : "0.00";
};

// 获取货币符号
const getCurrencySymbol = (paymentMethod?: string) => {
  if (paymentMethod === 'usdt') return 'USDT';
  return '¥';
};

// 格式化日期函数
const formatDate = (date: Date): string => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');

  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

// 生成随机时间
const generateRandomTime = (baseTime: Date, offsetHours = 0): string => {
  const date = new Date(baseTime);
  date.setHours(date.getHours() + offsetHours);
  date.setMinutes(date.getMinutes() + Math.floor(Math.random() * 60));
  return formatDate(date);
};

// 获取订单状态对应的步骤
const getOrderStatusStep = (status?: string): number => {
  if (!status) return 0;

  const stepMap: Record<string, number> = {
    "unpaid": 1, // 创建订单后，等待支付
    "paying": 2, // 正在支付
    "confirming": 3, // 区块确认中
    "paid": 4,   // 已支付
    "completed": 5,  // 已完成
    "cancelled": 0   // 取消订单回到初始状态
  };

  return stepMap[status] || 0;
};

// 获取状态对应的图标
const getStatusIcon = (status?: string): any => {
  if (!status) return ['fas', 'question-circle'];

  const iconMap: Record<string, any> = {
    "unpaid": ['fas', 'clock'],
    "paying": ['fas', 'spinner'],
    "confirming": ['fas', 'spinner'],
    "paid": ['fas', 'check-circle'],
    "completed": ['fas', 'flag-checkered'],
    "cancelled": ['fas', 'ban']
  };
  return iconMap[status] || ['fas', 'question-circle'];
};

// 获取状态对应的文本
const getStatusText = (status?: string): string => {
  if (!status) return "-";

  const statusMap: Record<string, string> = {
    "unpaid": "未支付",
    "paying": "支付中",
    "confirming": "确认中",
    "paid": "已支付",
    "completed": "已完成",
    "cancelled": "已取消"
  };
  return statusMap[status] || status;
};

// 获取状态对应的描述
const getStatusDescription = (status?: string): string => {
  if (!status) return "";

  const descMap: Record<string, string> = {
    "unpaid": "等待客户付款，可提醒或取消",
    "paying": "客户正在支付流程中",
    "confirming": "USDT交易确认中，等待区块确认",
    "paid": "已收到付款，等待处理",
    "completed": "订单已完成处理",
    "cancelled": "订单已取消，无需操作"
  };
  return descMap[status] || "";
};

// 获取状态对应的标签类型
const getStatusTagType = (status?: string): 'success' | 'warning' | 'info' | 'primary' | 'danger' => {
  if (!status) return "info";

  const typeMap: Record<string, 'success' | 'warning' | 'info' | 'primary' | 'danger'> = {
    "unpaid": "warning",
    "paying": "info",
    "confirming": "info",
    "paid": "primary",
    "completed": "success",
    "cancelled": "danger"
  };
  return typeMap[status] || "info";
};

// 获取支付方式对应的文本
const getPaymentText = (method?: string): string => {
  if (!method) return "-";

  const methodMap: Record<string, string> = {
    "wechat": "微信支付",
    "alipay": "支付宝",
    "usdt": "USDT支付"
  };
  return methodMap[method] || method;
};

// 获取支付方式对应的图标
const getPaymentIcon = (method?: string): any => {
  if (!method) return ['fas', 'credit-card'];

  const iconMap: Record<string, any> = {
    "wechat": ['fab', 'weixin'],
    "alipay": ['fab', 'alipay'],
    "usdt": ['fab', 'btc']
  };
  return iconMap[method] || ['fas', 'credit-card'];
};

// 获取支付按钮文本
const getPayButtonText = () => {
  if (!orderData.value) return '标记支付';

  if (orderData.value.payment_method === 'usdt') {
    return '标记USDT支付';
  } else if (orderData.value.payment_method === 'wechat') {
    return '标记微信支付';
  } else if (orderData.value.payment_method === 'alipay') {
    return '标记支付宝支付';
  }

  return '标记支付';
};

// 构建订单状态时间线
const buildOrderTimeline = (order: ExtendedOrderItem) => {
  const timeline: {
    status: string;
    time: string;
    desc?: string;
    confirmation_count?: number;
    hash?: string;
  }[] = [];

  // 所有订单都有创建时间
  const createTime = order.create_time;
  timeline.push({
    status: 'created',
    time: createTime,
    desc: `系统创建订单 #${order.order_no}`
  });

  // 根据支付方式和订单状态构建完整时间线
  const createDate = new Date(createTime);

  // USDT支付有特殊流程
  if (order.payment_method === 'usdt') {
    // 如果是已支付或更高级状态
    if (['paying', 'confirming', 'paid', 'completed'].includes(order.order_status)) {
      // 加入用户转账步骤
      const payingTime = generateRandomTime(createDate, 0.2);
      timeline.push({
        status: 'paying',
        time: payingTime,
        desc: '客户已发起USDT转账操作'
      });

      // 如果已确认或更高级状态
      if (['confirming', 'paid', 'completed'].includes(order.order_status)) {
        // 用户手动确认
        const userConfirmTime = generateRandomTime(new Date(payingTime), 0.1);
        timeline.push({
          status: 'user_confirmed',
          time: userConfirmTime,
          desc: '客户手动确认已完成转账'
        });

        // 系统确认区块链状态
        const confirmingTime = generateRandomTime(new Date(userConfirmTime), 0.1);
        const confirmations = Math.floor(Math.random() * 15) + 5; // 5-20之间的确认数
        order.confirmation_count = confirmations;
        timeline.push({
          status: 'confirming',
          time: confirmingTime,
          desc: '系统自动检查区块链交易状态',
          confirmation_count: confirmations
        });
      }

      // 如果已支付或已完成
      if (['paid', 'completed'].includes(order.order_status)) {
        const lastTime = timeline[timeline.length - 1].time;
        const confirmedTime = generateRandomTime(new Date(lastTime), 0.2);
        // 生成一个波场网络格式的交易哈希
        const hash = generateTronHash();
        order.transaction_hash = hash;

        timeline.push({
          status: 'confirmed',
          time: confirmedTime,
          desc: '区块链交易确认成功',
          hash
        });
      }

      // 如果已完成
      if (order.order_status === 'completed') {
        const lastTime = timeline[timeline.length - 1].time;
        const completeTime = generateRandomTime(new Date(lastTime), 0.3);
        timeline.push({
          status: 'completed',
          time: completeTime,
          desc: '管理员标记订单已完成'
        });
      }
    }
  } else {
    // 其他支付方式的流程
    if (['paid', 'completed'].includes(order.order_status)) {
      // 已支付状态，添加支付时间
      const payTime = generateRandomTime(createDate, 0.5);
      timeline.push({
        status: 'paid',
        time: payTime,
        desc: `收到${getPaymentText(order.payment_method)}付款`
      });

      if (order.order_status === 'completed') {
        // 已完成状态，添加完成时间
        const completeTime = generateRandomTime(new Date(payTime), 1);
        timeline.push({
          status: 'completed',
          time: completeTime,
          desc: '管理员标记订单已完成'
        });
      }
    }
  }

  // 添加取消状态
  if (order.order_status === 'cancelled') {
    const cancelTime = generateRandomTime(createDate, Math.random() > 0.5 ? 0.5 : 0.1);
    timeline.push({
      status: 'cancelled',
      time: cancelTime,
      desc: '管理员取消订单'
    });
  }

  // 反转时间线，使最新的状态显示在上面
  return timeline.reverse();
};

// 计算并格式化订单时间线，用于UI显示
const getOrderTimeline = () => {
  if (!orderData.value || !orderTimeline.value.length) return [];

  const result = [];

  // 遍历时间线记录生成UI需要的格式
  for (const item of orderTimeline.value) {
    let title = '';
    let desc = item.desc || '';
    let color = '';
    let type = 'primary';
    let size = 'normal';
    let hollow = false;
    let icon = null;
    let extra = '';
    let actions = [];

    switch (item.status) {
      case 'created':
        title = '订单创建';
        color = '#409EFF';
        type = 'primary';
        icon = ['fas', 'file-invoice'];
        break;

      case 'paying':
        title = '客户转账中';
        color = '#E6A23C';
        type = 'warning';
        icon = ['fas', 'exchange-alt'];
        break;

      case 'user_confirmed':
        title = '客户确认转账';
        color = '#67C23A';
        type = 'success';
        icon = ['fas', 'user-check'];
        break;

      case 'confirming':
        title = '区块确认中';
        color = '#909399';
        type = 'info';
        hollow = true;
        icon = ['fas', 'link'];
        if (item.confirmation_count !== undefined) {
          extra = `区块确认数: ${item.confirmation_count}/19`;
        }
        break;

      case 'confirmed':
        title = '交易已确认';
        color = '#67C23A';
        type = 'success';
        icon = ['fas', 'check-double'];
        if (item.hash) {
          extra = `交易哈希: ${item.hash.substring(0, 12)}...`;
        }
        break;

      case 'paid':
        title = '支付完成';
        color = '#67C23A';
        type = 'success';
        icon = ['fas', 'check-circle'];
        break;

      case 'completed':
        title = '订单完成';
        color = '#67C23A';
        type = 'success';
        icon = ['fas', 'flag-checkered'];
        break;

      case 'cancelled':
        title = '订单取消';
        color = '#F56C6C';
        type = 'danger';
        icon = ['fas', 'times-circle'];
        break;
    }

    result.push({
      title,
      desc,
      time: item.time,
      color,
      type,
      size,
      hollow,
      icon,
      extra,
      actions
    });
  }

  return result;
};

// 使用模拟数据获取订单详情
const fetchOrderDetail = () => {
  loading.value = true;

  try {
    // 生成30条模拟数据
    const mockOrders = generateMockOrders(30).filter(item =>
      item.payment_method !== 'cash_on_delivery' && item.payment_method !== 'bank_card'
    );

    // 查找对应ID的订单
    const order = mockOrders.find(item => item.id === props.orderId);

    if (order) {
      // 为了保证数据时间合理性，使用当前数据的create_time作为基准
      orderData.value = order as ExtendedOrderItem;

      // 如果是USDT支付，设置为已完成状态并生成交易哈希
      if (orderData.value.payment_method === 'usdt' && orderData.value.order_status !== 'completed') {
        orderData.value.order_status = 'completed';
        orderData.value.transaction_hash = `Tx0x${Math.random().toString(36).substring(2, 15)}${Math.random().toString(36).substring(2, 15)}`;
      }

      // 构建订单状态时间线
      orderTimeline.value = buildOrderTimeline(orderData.value);

      // 更新订单的更新时间
      if (orderTimeline.value.length > 1) {
        const lastTimelineItem = orderTimeline.value[orderTimeline.value.length - 1];
        orderData.value.update_time = lastTimelineItem.time;
      }
    } else {
      ElMessage.error("未找到订单数据");
    }
  } catch (error) {
    console.error("获取订单详情出错:", error);
    ElMessage.error("获取订单详情失败");
  } finally {
    loading.value = false;
  }
};

// 格式化日期时间
const formatDateTime = (dateTime?: string): string => {
  if (!dateTime) return '-';
  return dateTime;
};

// 复制到剪贴板
const copyToClipboard = (text?: string) => {
  if (!text) return;

  try {
    navigator.clipboard.writeText(text).then(() => {
      ElMessage({
        message: '已复制到剪贴板',
        type: 'success',
        duration: 2000
      });
    });
  } catch (err) {
    console.error('复制失败:', err);
    ElMessage.error('复制失败');
  }
};

// 支付订单
const handlePay = async () => {
  if (!orderData.value) return;

  try {
    if (orderData.value.payment_method === 'usdt') {
      // USDT支付流程
      orderData.value.order_status = "paying";

      // 更新时间线
      const payingTime = generateRandomTime(new Date(), 0);
      orderTimeline.value.unshift({
        status: 'paying',
        time: payingTime,
        desc: '管理员标记客户已发起USDT转账'
      });

      // 模拟等待用户手动确认
      ElMessage.success("已标记为USDT支付中状态");

      // 自动设置为确认中状态（模拟用户已完成操作）
      setTimeout(() => {
        if (orderData.value && orderData.value.order_status === "paying") {
          // 用户确认时间
          const userConfirmTime = generateRandomTime(new Date(), 0);
          orderTimeline.value.unshift({
            status: 'user_confirmed',
            time: userConfirmTime,
            desc: '系统记录客户手动确认转账完成'
          });

          // 设置为确认状态
          orderData.value.order_status = "confirming";

          // 系统确认时间
          const confirmingTime = generateRandomTime(new Date(userConfirmTime), 0.1);
          const confirmations = Math.floor(Math.random() * 15) + 5; // 修改为5-20之间的确认数
          orderTimeline.value.unshift({
            status: 'confirming',
            time: confirmingTime,
            desc: '系统自动检查区块链交易状态',
            confirmation_count: confirmations
          });

          // 更新updateTime
          orderData.value.update_time = confirmingTime;
        }
      }, 2000);
    } else {
      // 普通支付直接设置为已支付
      orderData.value.order_status = "paid";

      // 更新时间线
      const payTime = generateRandomTime(new Date(), 0);
      orderTimeline.value.unshift({
        status: 'paid',
        time: payTime,
        desc: `管理员标记已收到${getPaymentText(orderData.value.payment_method)}`
      });

      // 更新updateTime
      orderData.value.update_time = payTime;

      ElMessage.success("订单已标记为已支付");
    }
  } catch (error) {
    console.error("订单支付出错:", error);
    ElMessage.error("订单支付失败");
  }
};

// 完成订单
const handleComplete = async () => {
  if (!orderData.value) return;

  try {
    // 处理USDT订单的特殊状态
    if (orderData.value.payment_method === 'usdt' && orderData.value.order_status === 'confirming') {
      // 先添加区块确认成功的记录
      const confirmedTime = generateRandomTime(new Date(), 0);
      const hash = `Tx0x${Math.random().toString(36).substring(2, 15)}${Math.random().toString(36).substring(2, 15)}`;
      orderData.value.transaction_hash = hash;

      orderTimeline.value.unshift({
        status: 'confirmed',
        time: confirmedTime,
        desc: '区块链交易确认成功',
        hash
      });

      // 更新为已支付状态
      orderData.value.order_status = "paid";
      orderData.value.update_time = confirmedTime;

      ElMessage.success("交易已确认");

      // 然后再设置为已完成
      setTimeout(() => {
        if (orderData.value && orderData.value.order_status === "paid") {
          // 更新状态
          orderData.value.order_status = "completed";

          // 更新时间线
          const completeTime = generateRandomTime(new Date(), 0);
          orderTimeline.value.unshift({
            status: 'completed',
            time: completeTime,
            desc: '管理员标记订单已完成'
          });

          // 更新updateTime
          orderData.value.update_time = completeTime;

          ElMessage.success("订单已完成");
        }
      }, 1000);
    } else {
      // 普通订单直接设置为已完成
      orderData.value.order_status = "completed";

      // 更新时间线
      const completeTime = generateRandomTime(new Date(), 0);
      orderTimeline.value.unshift({
        status: 'completed',
        time: completeTime,
        desc: '管理员标记订单已完成'
      });

      // 更新updateTime
      orderData.value.update_time = completeTime;

      ElMessage.success("订单已完成");
    }
  } catch (error) {
    console.error("操作出错:", error);
    ElMessage.error("操作失败");
  }
};

// 取消订单
const handleCancel = async () => {
  if (!orderData.value) return;

  try {
    // 更新状态
    orderData.value.order_status = "cancelled";

    // 更新时间线
    const cancelTime = generateRandomTime(new Date(), 0);
    orderTimeline.value.unshift({
      status: 'cancelled',
      time: cancelTime,
      desc: '管理员取消订单'
    });

    // 更新updateTime
    orderData.value.update_time = cancelTime;

    ElMessage.success("订单已取消");
  } catch (error) {
    console.error("取消出错:", error);
    ElMessage.error("取消失败");
  }
};

// 打印订单
const handlePrint = () => {
  // 转换为适合打印的格式，实际中可能会打开一个新页面
  ElMessage.success("正在准备打印订单...");
  window.print();
};

// 添加生成波场网络交易哈希的函数
const generateTronHash = (): string => {
  // 生成64位16进制字符
  const chars = '0123456789abcdef';
  let hash = '';
  for (let i = 0; i < 64; i++) {
    hash += chars[Math.floor(Math.random() * chars.length)];
  }
  return hash;
};

// 组件挂载时获取订单详情
onMounted(() => {
  fetchOrderDetail();
});
</script>

<style lang="scss" scoped>
.order-detail {
  padding: 15px;
  background-color: var(--el-bg-color-page, #f5f7fa);
  min-height: 100%;
  display: flex;
  flex-direction: column;
  gap: 15px;

  .order-header {
    margin-bottom: 0;
  }

  .order-status {
    display: flex;
    flex-direction: column;
    border-radius: 6px;
    background: var(--el-bg-color, #fff);
    box-shadow: var(--el-box-shadow-light);
    overflow: hidden;

    &.unpaid {
      border-left: 3px solid #e6a23c;
    }

    &.paying,
    &.confirming {
      border-left: 3px solid #409eff;
    }

    &.paid {
      border-left: 3px solid #67c23a;
    }

    &.completed {
      border-left: 3px solid #67c23a;
    }

    &.cancelled {
      border-left: 3px solid #f56c6c;
    }

    .status-content {
      display: flex;
      align-items: center;
      padding: 16px;

      .status-icon {
        font-size: 22px;
        margin-right: 16px;
        padding: 10px;
        border-radius: 50%;
        background: var(--el-fill-color-lighter);

        .unpaid & {
          color: #e6a23c;
        }

        .paying &,
        .confirming & {
          color: #409eff;
          animation: spin 2s linear infinite;
        }

        .paid & {
          color: #409eff;
        }

        .completed & {
          color: #67c23a;
        }

        .cancelled & {
          color: #f56c6c;
        }
      }
    }

    .status-info {
      flex: 1;

      .status-text {
        font-size: 16px;
        font-weight: 500;
        margin-bottom: 6px;
        color: var(--el-text-color-primary);
      }

      .status-desc {
        font-size: 13px;
        color: var(--el-text-color-secondary);
        margin-bottom: 0;
      }
    }
  }

  .main-content {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 15px;
  }

  .order-info-section {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  .info-card {
    border: none;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: var(--el-box-shadow-light);
    height: fit-content;
    margin-bottom: 0;

    .card-header {
      display: flex;
      align-items: center;
      font-size: 15px;
      font-weight: 500;
      color: var(--el-text-color-primary);
      padding: 12px 16px;

      svg {
        margin-right: 8px;
        color: var(--el-color-primary);
        font-size: 15px;
      }

      .order-actions {
        margin-left: auto;

        .copy-btn {
          display: inline-flex;
          align-items: center;
          font-size: 12px;
          padding: 4px 8px;
          height: auto;
          border-radius: 3px;
          background-color: rgba(64, 158, 255, 0.1);
          color: var(--el-color-primary);

          svg {
            margin-right: 4px;
            font-size: 12px;
          }

          .copy-text {
            margin-right: 0;
          }
        }
      }
    }
  }

  .order-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 16px;

    .order-item {
      &.full-width {
        grid-column: 1 / -1;
      }

      .item-label {
        font-size: 13px;
        color: var(--el-text-color-secondary);
        margin-bottom: 6px;
      }

      .item-value {
        font-size: 14px;
        color: var(--el-text-color-primary);
        word-break: break-all;
      }
    }
  }

  .copy-value {
    display: flex;
    align-items: center;

    span {
      margin-right: 6px;

      &.monospace {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
        font-size: 12px;
      }

      &.crypto-address {
        background-color: var(--el-fill-color-light);
        padding: 3px 6px;
        border-radius: 3px;
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }

    .copy-btn {
      display: inline-flex;
      align-items: center;
      font-size: 12px;
      padding: 4px 8px;
      height: auto;
      border-radius: 3px;
      background-color: rgba(64, 158, 255, 0.1);

      svg {
        margin-right: 4px;
        font-size: 12px;
      }

      .copy-text {
        margin-right: 0;
      }
    }
  }

  .amount-container {
    display: flex;
    align-items: center;
    gap: 5px;

    .coin-icon {
      color: var(--el-color-warning);
      font-size: 15px;
    }

    .amount {
      font-weight: 600;
      color: var(--el-color-danger);
      font-size: 15px;
    }

    .currency {
      color: var(--el-text-color-secondary);
      font-size: 13px;
    }
  }

  .user-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    height: 24px;
    line-height: 24px;
    padding: 0 8px;

    svg {
      font-size: 11px;
    }
  }

  .payment-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;

    &.wechat {
      background-color: rgba(7, 193, 96, 0.9);
      color: white;
    }

    &.alipay {
      background-color: rgba(22, 119, 255, 0.9);
      color: white;
    }

    &.usdt {
      background-color: rgba(38, 161, 123, 0.9);
      color: white;
    }

    svg {
      font-size: 12px;
    }
  }

  .timeline-container {
    padding: 8px 16px 16px;

    :deep(.el-timeline) {
      padding-left: 8px;

      .el-timeline-item__wrapper {
        padding-left: 20px;
      }

      .el-timeline-item__tail {
        left: 4px;
        border-left: 1px solid var(--el-border-color-lighter);
      }

      .el-timeline-item__node {
        left: 0;
        width: 8px;
        height: 8px;
      }

      .el-timeline-item__icon {
        font-size: 12px;
      }
    }

    .timeline-content {
      padding: 0 0 18px;

      .timeline-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;

        .title {
          font-size: 14px;
          font-weight: 500;
          color: var(--el-text-color-primary);
        }

        .time {
          font-size: 12px;
          color: var(--el-text-color-secondary);
        }
      }

      .timeline-desc {
        font-size: 13px;
        color: var(--el-text-color-secondary);
        margin-bottom: 4px;
      }

      .timeline-extra {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        background-color: var(--el-fill-color-light);
        padding: 3px 6px;
        margin-top: 6px;
        border-radius: 3px;
        font-family: monospace;
      }
    }
  }

  .action-footer {
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: var(--el-bg-color, #fff);
    padding: 12px 16px;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
    z-index: 10;
    margin: 0 -15px -15px;

    .action-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 12px;

      .el-button {
        min-width: 100px;
        padding: 8px 16px;
        height: auto;

        svg {
          margin-right: 4px;
        }
      }
    }
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }

  @media (max-width: 768px) {
    padding: 12px;

    .order-grid {
      grid-template-columns: 1fr;
      gap: 12px;
      padding: 12px;
    }

    .action-footer {
      margin: 0 -12px -12px;
      padding: 10px 12px;

      .action-buttons {
        gap: 8px;

        .el-button {
          min-width: auto;
          padding: 6px 12px;
        }
      }
    }
  }

  .hover-card {
    position: relative;
  }
}
</style>