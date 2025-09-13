import { defineStore } from "pinia";
import { store } from "../utils";

interface GlobalState {
  // 系统主题相关配置
  theme: string;
  // 系统语言
  language: string;
  // 布局模式
  layout: string;
  // 是否显示页面切换动画
  showPageTransition: boolean;
  // 当前操作的id
  currentEditID: number;
}

export const useGlobalStore = defineStore({
  id: "pure-global",
  state: (): GlobalState => ({
    theme: "light",
    language: "zh-CN",
    layout: "vertical",
    showPageTransition: true,
    currentEditID: -1
  }),
  getters: {
    getTheme(): string {
      return this.theme;
    },
    getLanguage(): string {
      return this.language;
    }
  },
  actions: {
    // 切换主题
    setTheme(theme: string) {
      this.theme = theme;
    },
    // 切换语言
    setLanguage(language: string) {
      this.language = language;
    },
    // 修改布局模式
    setLayout(layout: string) {
      this.layout = layout;
    },
    setCurrentEditID(id: number) {
      this.currentEditID = id;
    }
  }
});

// 便于外部使用
export function useGlobalStoreHook() {
  return useGlobalStore(store);
}
