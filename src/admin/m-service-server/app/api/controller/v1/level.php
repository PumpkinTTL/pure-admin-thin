<?php

namespace app\api\controller\v1;

use app\api\services\LevelService;
use think\facade\Validate;
use think\validate\ValidateRule;
use think\response\Json;
use app\BaseController;

class level extends BaseController
{
    /**
     * 获取等级列表
     */
    public function list(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'type' => ValidateRule::isRequire(null, '等级类型必须传递'),
            'page' => ValidateRule::integer(),
            'page_size' => ValidateRule::integer(),
        ]);
        
        if (!$validate->batch()->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $result = LevelService::getLevelList($params);
        return json($result);
    }

    /**
     * 新增等级
     */
    public function add(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'type' => ValidateRule::isRequire(null, '等级类型必须传递'),
            'name' => ValidateRule::isRequire(null, '等级名称必须传递')->max(50, '等级名称长度不能超过50'),
            'level' => ValidateRule::isRequire(null, '等级数值必须传递'),
            'min_experience' => ValidateRule::isRequire(null, '最小经验值必须传递'),
        ]);
        
        if (!$validate->batch()->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $result = LevelService::addLevel($params);
        return json($result);
    }

    /**
     * 编辑等级
     */
    public function update(): Json
    {
        $params = request()->param();
        unset($params['version']);
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '等级ID必须传递'),
        ]);
        
        if (!$validate->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $id = intval($params['id']);
        $result = LevelService::updateLevel($id, $params);
        return json($result);
    }

    /**
     * 删除等级
     */
    public function delete(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '等级ID必须传递'),
        ]);
        
        if (!$validate->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $id = intval($params['id']);
        $result = LevelService::deleteLevel($id);
        return json($result);
    }

    /**
     * 设置等级状态
     */
    public function setStatus(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '等级ID必须传递'),
            'status' => ValidateRule::isRequire(null, '状态必须传递'),
        ]);
        
        if (!$validate->batch()->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $result = LevelService::setLevelStatus(intval($params['id']), intval($params['status']));
        return json($result);
    }

    /**
     * 获取等级详情
     */
    public function detail(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '等级ID必须传递'),
        ]);
        
        if (!$validate->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $id = intval($params['id']);
        $result = LevelService::getLevelDetail($id);
        return json($result);
    }

    /**
     * 根据经验值获取等级
     */
    public function getLevelByExperience(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'type' => ValidateRule::isRequire(null, '等级类型必须传递'),
            'experience' => ValidateRule::isRequire(null, '经验值必须传递')->integer('经验值必须是整数'),
        ]);
        
        if (!$validate->batch()->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $type = $params['type'];
        $experience = intval($params['experience']);
        $level = LevelService::getLevelByExperience($type, $experience);
        
        if ($level) {
            return json(['code' => 200, 'msg' => '获取成功', 'data' => $level]);
        } else {
            return json(['code' => 0, 'msg' => '未找到对应等级']);
        }
    }

    /**
     * 获取所有等级类型
     */
    public function types(): Json
    {
        $result = LevelService::getAllTypes();
        return json($result);
    }
    
    /**
     * 批量删除等级
     */
    public function batchDelete(): Json
    {
        // 获取POST请求体中的数据
        $ids = request()->post('ids', []);
        
        // 参数验证
        if (empty($ids) || !is_array($ids)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => 'ids必须是数组'
            ]);
        }
        
        $ids = array_map('intval', $ids);
        $result = LevelService::batchDeleteLevels($ids);
        return json($result);
    }
    
    /**
     * 批量更新等级状态
     */
    public function batchStatus(): Json
    {
        // 获取POST请求体中的数据
        $ids = request()->post('ids', []);
        $status = request()->post('status');
        
        // 参数验证
        if (empty($ids) || !is_array($ids)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => 'ids必须是数组'
            ]);
        }
        
        if (!isset($status)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => 'status必须传递'
            ]);
        }
        
        $ids = array_map('intval', $ids);
        $status = intval($status);
        $result = LevelService::batchUpdateStatus($ids, $status);
        return json($result);
    }
}
