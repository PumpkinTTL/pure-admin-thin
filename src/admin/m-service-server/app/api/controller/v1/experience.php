<?php

namespace app\api\controller\v1;

use app\api\services\ExperienceService;
use think\facade\Validate;
use think\validate\ValidateRule;
use think\response\Json;
use app\BaseController;

class experience extends BaseController
{
    /**
     * 获取经验日志
     */
    public function logs(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'target_type' => ValidateRule::isRequire(null, '目标类型必须传递'),
        ]);
        
        if (!$validate->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $targetType = $params['target_type'];
        $targetId = isset($params['target_id']) ? intval($params['target_id']) : 0;
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['page_size']) ? intval($params['page_size']) : 20;
        
        $result = ExperienceService::getExperienceLogs($targetType, $targetId, $page, $pageSize);
        return json($result);
    }

    /**
     * 添加经验（管理员手动）
     */
    public function add(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'target_type' => ValidateRule::isRequire(null, '目标类型必须传递'),
            'target_id' => ValidateRule::isRequire(null, '目标ID必须传递'),
            'experience_amount' => ValidateRule::isRequire(null, '经验值必须传递'),
            'source_type' => ValidateRule::isRequire(null, '经验来源类型必须传递'),
            'description' => ValidateRule::max(500, '描述长度不能超过500'),
        ]);
        
        if (!$validate->batch()->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $targetType = $params['target_type'];
        $targetId = $params['target_id'];
        $amount = intval($params['experience_amount']);
        $sourceType = $params['source_type'];
        $sourceId = $params['source_id'] ?? null;
        $description = $params['description'] ?? '';
        
        $result = ExperienceService::addExperience($targetType, $targetId, $amount, $sourceType, $sourceId, $description);
        return json($result);
    }

    /**
     * 获取等级信息
     */
    public function levelInfo(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'target_type' => ValidateRule::isRequire(null, '目标类型必须传递'),
            'target_id' => ValidateRule::isRequire(null, '目标ID必须传递'),
        ]);
        
        if (!$validate->batch()->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $targetType = $params['target_type'];
        $targetId = $params['target_id'];
        $result = ExperienceService::getLevelInfo($targetType, $targetId);
        return json($result);
    }

    /**
     * 获取用户所有类型的等级信息
     */
    public function userAllLevels(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'user_id' => ValidateRule::isRequire(null, '用户ID必须传递'),
        ]);
        
        if (!$validate->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $userId = $params['user_id'];
        $result = ExperienceService::getUserAllLevels($userId);
        return json($result);
    }

    /**
     * 获取经验来源配置
     */
    public function sources(): Json
    {
        $params = request()->param();
        $targetType = $params['target_type'] ?? null;
        
        $result = ExperienceService::getExperienceSources($targetType);
        return json($result);
    }

    /**
     * 撤销经验
     */
    public function revoke(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'log_id' => ValidateRule::isRequire(null, '日志ID必须传递'),
        ]);
        
        if (!$validate->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $logId = $params['log_id'];
        $result = ExperienceService::revokeExperience($logId);
        return json($result);
    }

    /**
     * 获取经验来源统计
     */
    public function sourceStats(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'target_type' => ValidateRule::isRequire(null, '目标类型必须传递'),
            'target_id' => ValidateRule::isRequire(null, '目标ID必须传递'),
        ]);
        
        if (!$validate->batch()->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $targetType = $params['target_type'];
        $targetId = $params['target_id'];
        $result = ExperienceService::getExperienceSourceStats($targetType, $targetId);
        return json($result);
    }

    /**
     * 获取等级记录列表（联查用户信息）
     */
    public function records(): Json
    {
        $params = request()->param();
        
        // 参数验证
        $validate = Validate::rule([
            'target_type' => ValidateRule::isRequire(null, '目标类型必须传递'),
        ]);
        
        if (!$validate->check($params)) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }
        
        $result = ExperienceService::getLevelRecords($params);
        return json($result);
    }
}
