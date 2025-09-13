import FingerprintJS from '@fingerprintjs/fingerprintjs';

// 指纹存储的键名
const FINGERPRINT_KEY = 'browser_fingerprint';

/**
 * 生成浏览器指纹并保存到localStorage
 */
export async function generateFingerprint(): Promise<string> {
  // 检查localStorage中是否已存在指纹
  const storedFingerprint = localStorage.getItem(FINGERPRINT_KEY);
  if (storedFingerprint) {
    return storedFingerprint;
  }

  try {
    // 初始化FingerprintJS实例
    const fp = await FingerprintJS.load();

    // 获取访问者的标识信息
    const result = await fp.get();

    // 使用visitorId作为指纹
    const fingerprint = result.visitorId;

    // 存储到localStorage
    localStorage.setItem(FINGERPRINT_KEY, fingerprint);

    console.log('浏览器指纹已生成:', fingerprint);
    return fingerprint;
  } catch (error) {
    console.error('生成浏览器指纹失败:', error);

    // 生成随机备用指纹
    const fallbackFingerprint = 'fp-' + Math.random().toString(36).substring(2);
    localStorage.setItem(FINGERPRINT_KEY, fallbackFingerprint);

    return fallbackFingerprint;
  }
}

/**
 * 获取浏览器指纹
 * 如果本地存储中不存在，则生成新的指纹
 */
export function getFingerprint(): Promise<string> {
  const storedFingerprint = localStorage.getItem(FINGERPRINT_KEY);
  return storedFingerprint ? Promise.resolve(storedFingerprint) : generateFingerprint();
}

/**
 * 清除已存储的指纹
 */
export function clearFingerprint(): void {
  localStorage.removeItem(FINGERPRINT_KEY);
}

export default {
  generateFingerprint,
  getFingerprint,
  clearFingerprint
}; 