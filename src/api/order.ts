import { http } from "@/utils/http";
import dayjs from "dayjs";

export interface OrderItem {
  id: number;
  order_no: string;
  user_id: number;
  resource_id?: number;
  order_amount: number;
  order_status: 'unpaid' | 'paying' | 'confirming' | 'paid' | 'completed' | 'cancelled';
  payment_method: string;
  product: string;
  remark?: string;
  create_time: string;
  update_time: string;
  delete_time: string | null;
  // 额外的显示字段，可能通过关联查询获取
  user_name?: string;
  resource_name?: string;
  // 加密货币支付相关字段
  transaction_hash?: string;
  confirmation_count?: number;
  crypto_address?: string;
}

// 获取订单列表
export const getOrderList = (params?: any) => {
  return http.request<{
    code: number;
    data: {
      data: OrderItem[];
      total: number;
    };
    message: string;
  }>("get", "/api/orders", { params });
};

// 获取订单详情
export const getOrderDetail = (id: number) => {
  return http.request<{
    code: number;
    data: OrderItem;
    message: string;
  }>("get", `/api/orders/${id}`);
};

// 创建订单
export const createOrder = (data: Omit<OrderItem, "id" | "create_time" | "update_time" | "delete_time">) => {
  return http.request<{
    code: number;
    data: OrderItem;
    message: string;
  }>("post", "/api/orders", { data });
};

// 更新订单
export const updateOrder = (id: number, data: Partial<OrderItem>) => {
  return http.request<{
    code: number;
    data: OrderItem;
    message: string;
  }>("put", `/api/orders/${id}`, { data });
};

// 删除订单
export const deleteOrder = (id: number) => {
  return http.request<{
    code: number;
    data: null;
    message: string;
  }>("delete", `/api/orders/${id}`);
};

// 恢复订单
export const restoreOrder = (id: number) => {
  return http.request<{
    code: number;
    data: OrderItem;
    message: string;
  }>("put", `/api/orders/${id}/restore`);
};

// 批量删除订单
export const batchDeleteOrders = (ids: number[]) => {
  return http.request<{
    code: number;
    data: null;
    message: string;
  }>("delete", "/api/orders/batch", { data: { ids } });
};

// 获取订单状态选项
export const getOrderStatusOptions = () => {
  return [
    { label: "未支付", value: "unpaid" },
    { label: "支付中", value: "paying" },
    { label: "确认中", value: "confirming" },
    { label: "已支付", value: "paid" },
    { label: "已完成", value: "completed" },
    { label: "已取消", value: "cancelled" }
  ];
};

// 获取支付方式选项
export const getPaymentMethodOptions = () => {
  return [
    { label: "微信支付", value: "wechat" },
    { label: "支付宝", value: "alipay" },
    { label: "USDT", value: "usdt" }
  ];
};

// 生成模拟订单数据
export const generateMockOrders = (count: number = 30): OrderItem[] => {
  const orders: OrderItem[] = [];
  const statusOptions = ['unpaid', 'paid', 'completed', 'cancelled'];
  const paymentMethods = ['wechat', 'alipay', 'usdt'];
  const userNames = ['张三', '李四', '王五', '赵六', '钱七', '孙八', '周九', '吴十'];
  const productNames = [
    'Vue高级开发课程', 'React性能优化专题', 'Flutter移动应用开发',
    'Python数据分析课程', 'Java企业级开发实战', 'Node.js全栈开发',
    'Docker容器部署教程', '微服务架构设计方案'
  ];
  const remarks = [
    '急需使用', '请尽快处理', '课程推荐购买',
    '准备技术升级', '企业批量采购', '', '', ''
  ];

  for (let i = 0; i < count; i++) {
    // 创建随机日期，在过去3个月内
    const randomDays = Math.floor(Math.random() * 90);
    const createDate = dayjs().subtract(randomDays, 'day');
    const updateDate = dayjs(createDate).add(Math.floor(Math.random() * 3), 'day');

    // 随机生成删除时间（约10%的数据会被软删除）
    const isDeleted = Math.random() < 0.1;
    const deleteDate = isDeleted ? dayjs(updateDate).add(Math.floor(Math.random() * 5), 'day').format('YYYY-MM-DD HH:mm:ss') : null;

    // 随机选择状态和支付方式
    const status = statusOptions[Math.floor(Math.random() * statusOptions.length)] as 'unpaid' | 'paid' | 'completed' | 'cancelled';
    const paymentMethod = paymentMethods[Math.floor(Math.random() * paymentMethods.length)];

    // 生成随机金额（10-2000之间）
    const amount = Math.round((Math.random() * 1990 + 10) * 100) / 100;

    orders.push({
      id: i + 1,
      order_no: `ORD${dayjs().format('YYYYMMDD')}${String(i + 1001).padStart(4, '0')}`,
      user_id: Math.floor(Math.random() * 100) + 1,
      user_name: userNames[Math.floor(Math.random() * userNames.length)],
      order_amount: amount,
      order_status: status,
      payment_method: paymentMethod,
      product: productNames[Math.floor(Math.random() * productNames.length)],
      remark: Math.random() > 0.3 ? remarks[Math.floor(Math.random() * remarks.length)] : undefined,
      create_time: createDate.format('YYYY-MM-DD HH:mm:ss'),
      update_time: updateDate.format('YYYY-MM-DD HH:mm:ss'),
      delete_time: deleteDate
    });
  }

  return orders;
}; 