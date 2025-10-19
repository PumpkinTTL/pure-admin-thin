import { defineStore } from "pinia";
import { type setType, store, getConfig } from "../utils";

export const useSettingStore = defineStore({
  id: "pure-setting",
  state: (): setType => ({
    title: getConfig().Title,
    fixedHeader: getConfig().FixedHeader,
    hiddenSideBar: getConfig().HiddenSideBar,
    iconSelectorVisible: false,
    iconSelectorValue: "",
    iconSelectorCallback: undefined
  }),
  getters: {
    getTitle(state) {
      return state.title;
    },
    getFixedHeader(state) {
      return state.fixedHeader;
    },
    getHiddenSideBar(state) {
      return state.hiddenSideBar;
    },
    getIconSelectorVisible(state) {
      return state.iconSelectorVisible;
    },
    getIconSelectorValue(state) {
      return state.iconSelectorValue;
    }
  },
  actions: {
    CHANGE_SETTING({ key, value }) {
      if (Reflect.has(this, key)) {
        this[key] = value;
      }
    },
    changeSetting(data) {
      this.CHANGE_SETTING(data);
    },
    // 打开图标选择器
    openIconSelector(options?: { currentValue?: string; callback?: (value: string) => void }) {
      this.iconSelectorValue = options?.currentValue || "";
      this.iconSelectorCallback = options?.callback;
      this.iconSelectorVisible = true;
    },
    // 设置图标选择器可见性
    setIconSelectorVisible(visible: boolean) {
      this.iconSelectorVisible = visible;
      if (!visible) {
        // 关闭时清空回调
        this.iconSelectorCallback = undefined;
      }
    },
    // 设置图标选择器的值
    setIconSelectorValue(value: string) {
      this.iconSelectorValue = value;
      // 如果有回调函数，执行它
      if (this.iconSelectorCallback) {
        this.iconSelectorCallback(value);
      }
    }
  }
});

export function useSettingStoreHook() {
  return useSettingStore(store);
}
