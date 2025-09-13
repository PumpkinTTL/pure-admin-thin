import Axios, {
  type AxiosInstance,
  type AxiosRequestConfig,
  type CustomParamsSerializer
} from "axios";
import type {
  PureHttpError,
  RequestMethods,
  PureHttpResponse,
  PureHttpRequestConfig
} from "./types.d";
import { stringify } from "qs";
import NProgress from "../progress";
import { getToken, formatToken, removeToken } from "@/utils/auth";
import { message, closeAllMessage } from "@/utils/message";
import { tokenManager } from "@/utils/tokenManager";
import router from "@/router";

// 相关配置请参考：www.axios-js.com/zh-cn/docs/#axios-request-config-1
const defaultConfig: AxiosRequestConfig = {
  // 请求超时时间
  timeout: 5000,
  headers: {
    Accept: "application/json, text/plain, */*",
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest"
  },
  // 数组格式参数序列化（https://github.com/axios/axios/issues/5142）
  paramsSerializer: {
    serialize: stringify as unknown as CustomParamsSerializer
  }
};

class PureHttp {
  constructor() {
    this.httpInterceptorsRequest();
    this.httpInterceptorsResponse();
  }



  /** 初始化配置对象 */
  private static initConfig: PureHttpRequestConfig = {};

  /** 保存当前`Axios`实例对象 */
  private static axiosInstance: AxiosInstance = Axios.create(defaultConfig);



  /** 请求拦截 */
  private httpInterceptorsRequest(): void {
    PureHttp.axiosInstance.interceptors.request.use(
      async (config: PureHttpRequestConfig): Promise<any> => {
        // 开启进度条动画
        NProgress.start();
        // 显示加载提示
        if (!config.silent) {
          // message("请稍等...", { type: "warning" });
        }

        // 调试：在控制台输出请求信息，帮助调试载荷问题
        if (process.env.NODE_ENV === 'development') {
          console.group(`🚀 HTTP Request: ${config.method?.toUpperCase()} ${config.url}`);
          console.log('📋 Headers:', config.headers);
          console.log('📦 Data:', config.data);
          console.log('🔍 Params:', config.params);
          console.groupEnd();
        }

        // 添加浏览器指纹到请求头
        const fingerprint = localStorage.getItem("browser_fingerprint");
        if (fingerprint) {
          config.headers["X-Fingerprint"] = fingerprint;
        }

        // 执行回调
        if (typeof config.beforeRequestCallback === "function") {
          config.beforeRequestCallback(config);
          return config;
        }
        if (PureHttp.initConfig.beforeRequestCallback) {
          PureHttp.initConfig.beforeRequestCallback(config);
          return config;
        }

        // 请求白名单，不需要token的接口
        const whiteList = ["/login", "/refresh"];
        if (whiteList.some(url => config.url.endsWith(url))) {
          return config;
        }

        // 使用token管理器检查并续签token
        const isTokenValid = await tokenManager.checkAndRefreshToken();
        if (!isTokenValid) {
          // token无效或续签失败，tokenManager已处理跳转
          return Promise.reject("Token invalid or refresh failed");
        }

        // 获取最新的token（可能已经续签过）
        const data = getToken();
        if (!data?.token) {
          removeToken();
          router.push("/login");
          return Promise.reject("No token after refresh");
        }

        // 添加token到请求头 - 使用标准Bearer格式
        config.headers["Authorization"] = formatToken(data.token);

        return config;
      },
      error => {
        // 请求错误时清除提示
        closeAllMessage();
        message("网络请求不存在", { type: "error" });
        return Promise.reject(error);
      }
    );
  }

  /** 响应拦截 */
  private httpInterceptorsResponse(): void {
    const instance = PureHttp.axiosInstance;
    instance.interceptors.response.use(
      (response: PureHttpResponse) => {
        // 关闭进度条
        NProgress.done();
        closeAllMessage();
        const $config = response.config;

        // 执行回调
        if (typeof $config.beforeResponseCallback === "function") {
          $config.beforeResponseCallback(response);
          return response.data;
        }
        if (PureHttp.initConfig.beforeResponseCallback) {
          PureHttp.initConfig.beforeResponseCallback(response);
          return response.data;
        }
        return response.data;
      },
      (error: PureHttpError) => {
        // 关闭进度条
        NProgress.done();
        closeAllMessage();
        const $error = error;
        $error.isCancelRequest = Axios.isCancel($error);

        // 处理错误
        if ($error.code === "ECONNABORTED" && $error.message.includes("timeout")) {
          message("请求超时", { type: "error" });
        } else if ($error.response) {
          const status = $error.response.status;
          switch (status) {
            case 401:
              message("登录已过期", { type: "error" });
              removeToken();
              router.push("/login");
              break;
            case 403:
              message("权限不足", { type: "error" });
              break;
            case 404:
              message("资源不存在", { type: "error" });
              break;
            case 500:
              message("服务器错误", { type: "error" });
              break;
            default:
              message(`请求失败 (${status})`, { type: "error" });
          }
        } else {
          message("网络异常", { type: "error" });
        }

        return Promise.reject($error);
      }
    );
  }

  /** 通用请求工具函数 */
  public request<T>(
    method: RequestMethods,
    url: string,
    param?: AxiosRequestConfig,
    axiosConfig?: PureHttpRequestConfig
  ): Promise<T> {
    const config = {
      method,
      url,
      ...param,
      ...axiosConfig
    } as PureHttpRequestConfig;

    // 单独处理自定义请求/响应回调
    return new Promise((resolve, reject) => {
      PureHttp.axiosInstance
        .request(config)
        .then((response: undefined) => {
          resolve(response);
        })
        .catch(error => {
          reject(error);
        });
    });
  }

  /** 单独抽离的`post`工具函数 */
  public post<T, P>(
    url: string,
    params?: AxiosRequestConfig<P>,
    config?: PureHttpRequestConfig
  ): Promise<T> {
    return this.request<T>("post", url, params, config);
  }

  /** 单独抽离的`get`工具函数 */
  public get<T, P>(
    url: string,
    params?: AxiosRequestConfig<P>,
    config?: PureHttpRequestConfig
  ): Promise<T> {
    return this.request<T>("get", url, params, config);
  }
}

export const http = new PureHttp();
