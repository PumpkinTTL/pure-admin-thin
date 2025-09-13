<?php

namespace app\api\middleware;

use Closure;
use think\facade\Db;
use think\Request;
use think\Response;
use utils\RedisUtil;
use utils\SecretUtil;

class Sign
{
    /**
     * 处理请求并进行签名验证。
     *
     * @param Request $request 请求对象
     * @param Closure $next 下一个中间件
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 获取请求参数
        $appid = $request->header('appid');
        $sign = $request->param('sign');
        $timestamp = $request->header('timestamp');
        $nonce = $request->header('nonce');
//
        // 验证参数完整性
        if (empty($appid) || empty($sign) || empty($timestamp) || empty($nonce)) {
            return $this->errorResponse('参数不完整', 5001);
        }

        // 获取应用信息
        $currentApp = Db::name('apps')->find($appid);
        if (!$currentApp) {
            return $this->errorResponse('appid错误', 5002);
        }

        // 验证请求时间有效性
//        if ($this->isRequestTimeout($timestamp)) {
//            return $this->errorResponse('请求超时', 5003);
//        }

        // 验证nonce是否重复
//        if ($this->isNonceRepeated($request->ip(), $nonce)) {
//            return $this->errorResponse('重复请求', 5004);
//        }
//        放行
        // 设置nonce有效时间
//        $this->setNonceExpiration($request->ip(), $nonce);

        // 验证签名
        if (!$this->verifySign(request()->param(), $sign, $currentApp)) {
            return $this->errorResponse('签名错误,请查阅文档后重试', 5005);
        }


        // 继续执行后续中间件
        return $next($request);
    }

    /**
     * 组装参数并进行排序。
     *
     * @param Request $request 请求对象
     * @param array $appInfo 应用信息
     * @return bool 排序后的参数
     */
    protected function verifySign($params, $sign, array $appInfo): bool
    {
        unset($params['version'], $params['sign'], $params['appid']); // 移除不需要参与签名的参数
        $params['timestamp'] = request()->header('timestamp');
        $params['nonce'] = request()->header('nonce');
        $params['secret'] = $appInfo['secret'];       //增加secret
        ksort($params); // 按键排序
        $paramsStr = http_build_query($params); // 构建查询字符串
        $paramsHash = hash('sha256', $paramsStr); //计算参数摘要
        $parseSignHash = SecretUtil::aesDecryptECB($sign);  //解密客户端摘要
//        var_dump($paramsStr);  //参数排序
//        var_dump($sign);  //客户端的签名
//        var_dump($paramsHash); //计算排序后的参数摘要
//        var_dump($parseSignHash); //解密的参数摘要
        return $paramsHash == $parseSignHash;
    }


    /**
     * 检查请求是否超时。超过三分钟为超时
     *
     * @param int $timestamp 请求的时间戳
     * @return bool 是否超时
     */
    protected function isRequestTimeout(int $timestamp): bool
    {
        $diff = abs(time() - $timestamp);
        return $diff >= 180;
    }

    /**
     * 检查nonce是否重复。
     *
     * @param string $ip 用户IP
     * @param string $nonce 随机字符串
     * @return bool 是否重复
     */
    protected function isNonceRepeated(string $ip, string $nonce): bool
    {
        $ipStr = implode('', explode('.', $ip));
        return RedisUtil::getString($ipStr . '_' . $nonce) !== null;
    }

    /**
     * 设置nonce的过期时间。
     *
     * @param string $ip 用户IP
     * @param string $nonce 随机字符串
     */
    protected function setNonceExpiration(string $ip, string $nonce, $timeOut = 60): void
    {
        $ipStr = implode('', explode('.', $ip));
        RedisUtil::setString($ipStr . '_' . $nonce, $nonce, $timeOut);
    }

    /**
     * 创建错误响应。
     *
     * @param string $msg 错误信息
     * @param int $code 错误码
     * @param array $data 附加数据
     * @return Response
     */
    protected function errorResponse(string $msg, int $code, array $data = []): Response
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }
}