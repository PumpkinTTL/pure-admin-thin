<?php

namespace app\api\controller\v1;

use app\BaseController;
use app\api\services\favoritesService;
use think\facade\Validate;
use think\validate\ValidateRule;

class favorites extends BaseController
{
    /**
     * 获取收藏列表（管理后台）
     * GET /api/v1/favorites/list
     */
    public function list()
    {
        $params = $this->request->param();
        
        $result = favoritesService::getList($params);
        
        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => $result
        ]);
    }
    
    /**
     * 收藏/取消收藏（客户端接口）
     * POST /api/v1/favorites/toggle
     * 收藏记录存在就删除，不存在就创建
     */
    public function toggle()
    {
        $params = $this->request->param();
        
        // 参数验证
        $validate = Validate::rule([
            'target_type' => ValidateRule::isRequire(null, 'target_type必须传递')->in(['article', 'product', 'comment'], 'target_type必须是article、product或comment'),
            'target_id' => ValidateRule::isRequire(null, 'target_id必须传递'),
            'user_id' => ValidateRule::isRequire(null, '用户ID必须传递')
        ]);
        
        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }
        
        $result = favoritesService::toggleFavorite(
            $params['user_id'],
            $params['target_type'],
            $params['target_id']
        );
        
        return json($result);
    }
}
