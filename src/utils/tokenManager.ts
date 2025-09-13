import { getToken, setToken, removeToken } from "@/utils/auth";
import { http } from "@/utils/http";
import router from "@/router";
import { message } from "@/utils/message";

/**
 * Token管理器 - 负责token的无感续签和生命周期管理
 * 
 * 主要功能：
 * 1. 检测token过期时间，提前续签
 * 2. 防止并发续签请求
 * 3. 续签失败时的降级处理
 * 4. 网络异常时的重试机制
 */
class TokenManager {
  /** 是否正在刷新token */
  private isRefreshing = false;

  /** 当前的刷新Promise，用于防止并发刷新 */
  private refreshPromise: Promise<boolean> | null = null;

  /** token续签的时间阈值（毫秒）- 剩余10分钟时开始续签 */
  private readonly REFRESH_THRESHOLD = 10 * 60 * 1000;

  /** token续签的最小阈值（毫秒）- 剩余5分钟时强制续签 */
  private readonly MIN_REFRESH_THRESHOLD = 5 * 60 * 1000;

  /** 续签失败重试次数 */
  private readonly MAX_RETRY_COUNT = 2;

  /** 续签失败重试间隔（毫秒） */
  private readonly RETRY_DELAY = 1000;

  /**
   * 检查token并在需要时进行续签
   * @returns Promise<boolean> 返回token是否有效（续签成功或无需续签）
   */
  async checkAndRefreshToken(): Promise<boolean> {
    try {
      const tokenData = getToken();
      // 没有token数据，直接返回false
      if (!tokenData || !tokenData.token || !tokenData.expires) {
        console.warn('[TokenManager] 没有有效的token数据');
        return false;
      }

      const now = Date.now();
      const expiresTime = typeof tokenData.expires === 'number'
        ? tokenData.expires
        : new Date(tokenData.expires).getTime();

      const timeLeft = expiresTime - now;

      // token已过期
      if (timeLeft <= 0) {
        console.warn('[TokenManager] Token已过期');
        this.handleTokenExpired();
        return false;
      }

      // token还有效，但需要续签
      if (timeLeft <= this.REFRESH_THRESHOLD) {
        console.info(`[TokenManager] Token将在${Math.floor(timeLeft / 1000 / 60)}分钟后过期，开始续签`);
        return await this.refreshToken();
      }

      // token有效，无需续签
      console.info(`[TokenManager] Token还有${Math.floor(timeLeft / 1000 / 60)}分钟有效期，无需续签`);
      return true;

    } catch (error) {
      console.error('[TokenManager] 检查token时发生错误:', error);
      return false;
    }
  }

  /**
   * 刷新token
   * @returns Promise<boolean> 续签是否成功
   */
  private async refreshToken(): Promise<boolean> {
    // 如果正在刷新，返回当前的刷新Promise
    if (this.isRefreshing && this.refreshPromise) {
      console.info('[TokenManager] 检测到并发续签请求，等待当前续签完成');
      return await this.refreshPromise;
    }

    // 开始新的刷新流程
    this.isRefreshing = true;
    this.refreshPromise = this.doRefreshWithRetry();

    try {
      const result = await this.refreshPromise;
      return result;
    } finally {
      // 清理状态
      this.isRefreshing = false;
      this.refreshPromise = null;
    }
  }

  /**
   * 执行token刷新（带重试机制）
   * @returns Promise<boolean> 续签是否成功
   */
  private async doRefreshWithRetry(): Promise<boolean> {
    let lastError: any = null;

    for (let attempt = 1; attempt <= this.MAX_RETRY_COUNT; attempt++) {
      try {
        console.info(`[TokenManager] 开始第${attempt}次续签尝试`);

        const success = await this.doRefresh();
        if (success) {
          console.info('[TokenManager] Token续签成功');
          return true;
        }

      } catch (error) {
        lastError = error;
        console.warn(`[TokenManager] 第${attempt}次续签失败:`, error);

        // 如果不是最后一次尝试，等待后重试
        if (attempt < this.MAX_RETRY_COUNT) {
          console.info(`[TokenManager] ${this.RETRY_DELAY}ms后进行第${attempt + 1}次重试`);
          await this.delay(this.RETRY_DELAY);
        }
      }
    }

    // 所有重试都失败了
    console.error('[TokenManager] Token续签失败，已达到最大重试次数', lastError);
    this.handleRefreshFailure(lastError);
    return false;
  }

  /**
   * 执行实际的token刷新请求
   * @returns Promise<boolean> 续签是否成功
   */
  private async doRefresh(): Promise<boolean> {
    try {
      const currentToken = getToken();
      if (!currentToken?.token) {
        throw new Error('当前没有有效的token');
      }

      // 调用续签接口
      const response = await http.request('post', '/api/v1/auth/refresh', {
        data: { token: currentToken.token }
      }) as any;

      // 检查响应
      if (response.code !== 200) {
        throw new Error(response.msg || '续签接口返回错误');
      }

      // 解构新的token数据
      const { token: newToken, expireTime } = response as any;
      if (!newToken || !expireTime) {
        throw new Error('续签响应数据格式错误');
      }

      // 构建新的token数据
      const newTokenData = {
        ...currentToken,
        token: newToken,
        expires: new Date(expireTime * 1000) // 后端返回秒级时间戳，转换为毫秒
      };

      // 保存新token
      setToken(newTokenData);

      console.info('[TokenManager] 新token已保存，过期时间:', new Date(expireTime * 1000));
      return true;

    } catch (error) {
      console.error('[TokenManager] 续签请求失败:', error);
      throw error;
    }
  }

  /**
   * 处理token过期
   */
  private handleTokenExpired(): void {
    console.warn('[TokenManager] 处理token过期');
    removeToken();
    message("登录已过期，请重新登录", { type: "warning" });
    router.push("/login");
  }

  /**
   * 处理续签失败
   * @param error 失败原因
   */
  private handleRefreshFailure(error: any): void {
    console.error('[TokenManager] 处理续签失败:', error);
    removeToken();

    // 根据错误类型显示不同的提示
    if (error?.response?.status === 401) {
      message("登录已失效，请重新登录", { type: "warning" });
    } else if (error?.code === 'ECONNABORTED' || error?.message?.includes('timeout')) {
      message("网络超时，请检查网络连接后重新登录", { type: "error" });
    } else {
      message("登录状态异常，请重新登录", { type: "error" });
    }

    router.push("/login");
  }

  /**
   * 延迟函数
   * @param ms 延迟毫秒数
   */
  private delay(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * 获取token剩余有效时间（毫秒）
   * @returns number | null 剩余时间，null表示无效token
   */
  getTokenTimeLeft(): number | null {
    try {
      const tokenData = getToken();
      if (!tokenData?.expires) return null;

      const now = Date.now();
      const expiresTime = typeof tokenData.expires === 'number'
        ? tokenData.expires
        : new Date(tokenData.expires).getTime();

      return Math.max(0, expiresTime - now);
    } catch {
      return null;
    }
  }

  /**
   * 检查token是否即将过期
   * @returns boolean 是否即将过期
   */
  isTokenExpiringSoon(): boolean {
    const timeLeft = this.getTokenTimeLeft();
    return timeLeft !== null && timeLeft <= this.REFRESH_THRESHOLD;
  }

  /**
   * 强制刷新token（用于手动触发）
   * @returns Promise<boolean> 续签是否成功
   */
  async forceRefreshToken(): Promise<boolean> {
    console.info('[TokenManager] 手动触发token续签');
    return await this.refreshToken();
  }
}

// 导出单例实例
export const tokenManager = new TokenManager();

// 导出类型（如果需要）
export type { TokenManager };
