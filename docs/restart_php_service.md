# 重启PHP服务以清除代码缓存

## 问题
CLI测试正常，但Web访问仍然报错 `Unknown column 'code'`，说明PHP-FPM进程缓存了旧代码。

## 解决方法

### 方法1: 使用PHPStudy（推荐）
1. 打开PHPStudy控制面板
2. 找到 "软件管理" 或 "服务管理"
3. **停止 PHP**（不是Nginx）
4. **启动 PHP**
5. 刷新浏览器测试

### 方法2: 命令行重启
打开PHPStudy的安装目录，找到PHP-CGI或PHP-FPM进程，手动结束：

```powershell
# 查找PHP进程
Get-Process | Where-Object {$_.ProcessName -like "*php*"}

# 强制结束所有PHP进程
Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Stop-Process -Force

# 然后在PHPStudy中重新启动PHP
```

### 方法3: 重启整个PHPStudy
最简单暴力的方法：
1. 关闭PHPStudy
2. 重新打开PHPStudy
3. 启动所有服务

## 验证
重启后，访问：
```
POST http://localhost:5173/api/v1/cardkey/batch
Body: {"count": 2, "type_id": 1}
```

应该返回成功，不再报 `code` 字段错误。

## 为什么会这样？
- PHP-FPM使用常驻进程处理请求
- 修改代码后，旧进程仍在内存中运行旧代码
- CLI直接执行新代码，所以测试正常
- 必须重启PHP-FPM进程才能加载新代码

