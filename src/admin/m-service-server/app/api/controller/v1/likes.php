<?php

namespace app\api\controller\v1;

use app\BaseController;
use app\api\services\likesService;
use think\facade\Validate;
use think\validate\ValidateRule;

class likes extends BaseController
{
    /**
     * 获取点赞列表（管理后台）
     * GET /api/v1/likes/list
     */
    public function list()
    {
        $params = $this->request->param();
        
        $result = likesService::getList($params);
        
        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => $result
        ]);
    }
    
    /**
     * 点赞/取消点赞（客户端接口）
     * POST /api/v1/likes/toggle
     * 点赞记录存在就删除，不存在就创建
     */
    public function toggle()
    {
        $params = $this->request->param();
        
        // 参数验证
        $validate = Validate::rule([
            'target_type' => ValidateRule::isRequire(null, 'target_type必须传递')->in(['comment', 'article'], 'target_type必须是comment或article'),
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
        
        $result = likesService::toggleLike(
            $params['user_id'],
            $params['target_type'],
            $params['target_id']
        );
        
        return json($result);
    }
    
}
