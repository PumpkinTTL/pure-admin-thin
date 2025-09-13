// 根据角色动态生成路由
import { defineFakeRoute } from "vite-plugin-fake-server/client";

export default defineFakeRoute([
  {
    url: "/login",
    method: "post",
    response: ({ body }) => {
      if (body.username === "admin") {
        return {
          code: 200,
          msg: "登录成功",
          token: "eyJhbGciOiJIUzUxMiJ9.admin",
          expireTime: Math.floor(Date.now() / 1000) + 2 * 60 * 60, // 2小时后过期
          data: {
            avatar: "https://avatars.githubusercontent.com/u/44761321",
            username: "admin",
            nickname: "小铭",
            // 一个用户可能有多个角色
            roles: ["admin"],
            // 按钮级别权限
            permissions: ["*:*:*"]
          }
        };
      } else {
        return {
          code: 200,
          msg: "登录成功",
          token: "eyJhbGciOiJIUzUxMiJ9.common",
          expireTime: Math.floor(Date.now() / 1000) + 2 * 60 * 60, // 2小时后过期
          data: {
            avatar: "https://avatars.githubusercontent.com/u/52823142",
            username: "common",
            nickname: "小林",
            roles: ["common"],
            permissions: ["permission:btn:add", "permission:btn:edit"]
          }
        };
      }
    }
  }
]);
