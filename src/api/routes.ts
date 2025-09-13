import { http } from "@/utils/http";
import { message } from "@/utils/message";

type Result = {
  success: boolean;
  data: Array<any>;
};
// 请求数据
export const getAsyncRoutes = async () => {
  const token = localStorage.getItem("token");
  const refreshToken = localStorage.getItem("refreshToken");
  const accessToken = localStorage.getItem("accessToken");
  console.log("请求路由");
  const res = await http.request<Result>("get", "/get-async-routes");
  console.log(res);

  return res;
};
