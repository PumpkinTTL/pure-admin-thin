import CryptoJS from 'crypto-js';

/**
 * HMAC参数签名工具函数集
 * 用于API请求安全认证
 */

/**
 * 按照键名对对象进行字典序排序
 * @param {Record<string, any>} params 需要排序的参数对象
 * @returns {Record<string, any>} 排序后的参数对象
 */
export function sortObjectByKeys(params: Record<string, any>): Record<string, any> {
  // 获取所有键并排序
  const keys = Object.keys(params).sort();

  // 创建新的排序后对象
  const sortedParams: Record<string, any> = {};
  keys.forEach(key => {
    sortedParams[key] = params[key];
  });

  return sortedParams;
}

/**
 * 将对象转换为URL查询字符串格式
 * @param {Record<string, any>} params 参数对象
 * @returns {string} 格式化后的查询字符串 (不包含问号前缀)
 */
export function objectToQueryString(params: Record<string, any>): string {
  return Object.entries(params)
    .map(([key, value]) => {
      // 处理数组或对象值，转为JSON字符串
      if (typeof value === 'object' && value !== null) {
        value = JSON.stringify(value);
      }
      // 对键和值进行编码
      return `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`;
    })
    .join('&');
}

/**
 * 生成时间戳和随机字符串(nonce)
 * @returns {{timestamp: string, nonce: string}} 时间戳和随机字符串
 */
export function generateTimestampAndNonce(): { timestamp: string, nonce: string } {
  // 当前时间戳(秒)
  const timestamp = Math.floor(Date.now() / 1000).toString();

  // 生成16位随机字符串
  const randomChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let nonce = '';
  for (let i = 0; i < 16; i++) {
    nonce += randomChars.charAt(Math.floor(Math.random() * randomChars.length));
  }

  return { timestamp, nonce };
}

/**
 * 使用HMAC-SHA256算法生成签名
 * @param {Record<string, any>} params 参数对象
 * @param {string} secretKey 密钥
 * @param {string} [method='GET'] HTTP请求方法
 * @param {string} [path='/api'] API路径
 * @returns {string} 生成的签名
 */
export function generateHmacSignature(
  params: Record<string, any>,
  secretKey: string,
  method: string = 'GET',
  path: string = '/api'
): string {
  // 1. 添加时间戳和随机字符串
  const { timestamp, nonce } = generateTimestampAndNonce();
  const signParams = {
    ...params,
    timestamp,
    nonce
  };

  // 2. 按键名排序参数
  const sortedParams = sortObjectByKeys(signParams);

  // 3. 转换为查询字符串
  const queryString = objectToQueryString(sortedParams);

  // 4. 构建签名原文
  // 格式: HTTP方法 + 路径 + 参数字符串
  const signatureBaseString = `${method.toUpperCase()}&${path}&${queryString}`;

  // 5. 使用HMAC-SHA256算法生成签名
  const signature = CryptoJS.HmacSHA256(signatureBaseString, secretKey).toString(CryptoJS.enc.Hex);

  // 6. 返回签名和用于请求的完整参数
  return signature;
}

/**
 * 创建包含签名的完整请求参数
 * @param {Record<string, any>} params 原始参数
 * @param {string} secretKey 密钥
 * @param {string} [method='GET'] HTTP请求方法
 * @param {string} [path='/api'] API路径
 * @returns {Record<string, any>} 包含签名的完整参数
 */
export function createSignedRequest(
  params: Record<string, any>,
  secretKey: string,
  method: string = 'GET',
  path: string = '/api'
): Record<string, any> {
  // 1. 添加时间戳和随机字符串
  const { timestamp, nonce } = generateTimestampAndNonce();
  const requestParams = {
    ...params,
    timestamp,
    nonce
  };

  // 2. 生成签名
  const signature = generateHmacSignature(requestParams, secretKey, method, path);

  // 3. 将签名添加到请求参数
  return {
    ...requestParams,
    signature
  };
}

/**
 * 使用示例:
 * 
 * // 1. 导入工具函数
 * import { createSignedRequest } from '@/api/util';
 * 
 * // 2. 创建签名请求
 * const params = { userId: 123, action: 'getData' };
 * const secretKey = 'your_secret_key';
 * const signedParams = createSignedRequest(params, secretKey, 'POST', '/api/user/info');
 * 
 * // 3. 使用签名参数发起请求
 * axios.post('/api/user/info', signedParams);
 */

/**
 * 验证签名是否有效 (服务端使用)
 * @param {Record<string, any>} params 包含签名的参数
 * @param {string} secretKey 密钥
 * @param {string} [method='GET'] HTTP请求方法
 * @param {string} [path='/api'] API路径
 * @returns {boolean} 签名是否有效
 */
export function verifyHmacSignature(
  params: Record<string, any>,
  secretKey: string,
  method: string = 'GET',
  path: string = '/api'
): boolean {
  // 1. 从参数中提取签名
  const { signature, ...otherParams } = params;

  if (!signature) {
    return false;
  }

  // 2. 使用相同算法生成签名
  const calculatedSignature = generateHmacSignature(otherParams, secretKey, method, path);

  // 3. 比较签名是否一致
  return signature === calculatedSignature;
}
