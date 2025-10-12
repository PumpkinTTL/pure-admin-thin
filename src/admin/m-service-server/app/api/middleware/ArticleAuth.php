<?php

namespace app\api\middleware;

use utils\JWTUtil;

class ArticleAuth
{

  public function handle($request, \Closure $next)
  {
    // 获取请求头中的 Authorization
    $authHeader = request()->header('authorization');
    // 如果存在头部令牌的话
    if (!empty($authHeader)) {
      // 去掉 "Bearer " 前缀，获取纯 token
      $token = trim(str_replace(['Bearer ', 'bearer '], '', $authHeader));
      $parseToken =  JWTUtil::verifyToken($token);
    }
    return $next($request);
  }
}
