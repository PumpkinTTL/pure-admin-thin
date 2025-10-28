<?php

namespace app\api\services;

use app\api\model\notice;
use app\api\model\noticeTarget;
use app\api\model\users;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;

class noticeService
{
    /**
     * 获取公告列表
     * @param array $params 查询参数
     * @return \think\Paginator
     */
    public static function getNoticeList(array $params = [])
    {
        // 默认分页参数
        $pageSize = $params['page_size'] ?? 10;
        $page = $params['page'] ?? 1;

        // 构建基础查询，加载关联数据
        $query = notice::with([
            'publisher',
            'targetUsers' => function ($query) {
                $query->field(['id', 'notice_id', 'target_type', 'target_id']);
            },
            'targetRoles' => function ($query) {
                $query->field(['id', 'notice_id', 'target_type', 'target_id']);
            }
        ]);

        // 应用查询条件
        $query = self::buildQueryConditions($query, $params);

        // 应用权限过滤（参考文章模块）
        $query = self::applyPermissionFilter($query, $params);

        // 默认排序
        if (!isset($params['sort_field']) || !isset($params['sort_order'])) {
            // 优先显示置顶，然后按优先级和发布时间排序
            $query->order('is_top', 'desc')
                ->order('priority', 'desc')
                ->order('publish_time', 'desc');
        }
        // 查询软删除数据
        $query->withTrashed();
        // 分页查询
        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
            'query' => Request::get()
        ]);
    }

    /**
     * 构建查询条件
     * @param \think\db\Query $query 查询构建器
     * @param array $params 查询参数
     * @return \think\db\Query
     */
    protected static function buildQueryConditions($query, array $params)
    {
        // 标题模糊查询
        if (!empty($params['title'])) {
            $query->whereLike('title', '%' . $params['title'] . '%');
        }

        // 可见性查询
        if (isset($params['visibility']) && $params['visibility'] !== '') {
            $query->where('visibility', $params['visibility']);
        }

        // 公告分类查询
        if (isset($params['category_type']) && $params['category_type'] !== '') {
            $query->where('category_type', intval($params['category_type']));
        }

        // 状态查询
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', intval($params['status']));
        }

        // 优先级查询
        if (isset($params['priority']) && $params['priority'] !== '') {
            $query->where('priority', intval($params['priority']));
        }

        // 是否置顶查询
        if (isset($params['is_top']) && $params['is_top'] !== '') {
            $query->where('is_top', boolval($params['is_top']));
        }

        // 发布人查询
        if (!empty($params['publisher_id'])) {
            $query->where('publisher_id', intval($params['publisher_id']));
        }

        // 发布时间范围查询
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetweenTime('publish_time', $params['start_time'], $params['end_time']);
        } else if (!empty($params['start_time'])) {
            $query->whereTime('publish_time', '>=', $params['start_time']);
        } else if (!empty($params['end_time'])) {
            $query->whereTime('publish_time', '<=', $params['end_time']);
        }



        // 自定义排序
        if (!empty($params['sort_field']) && !empty($params['sort_order'])) {
            $allowedFields = ['publish_time', 'priority', 'create_time', 'update_time'];
            $field = in_array($params['sort_field'], $allowedFields) ? $params['sort_field'] : 'publish_time';
            $order = strtolower($params['sort_order']) === 'asc' ? 'asc' : 'desc';
            $query->order($field, $order);
        }

        return $query;
    }

    /**
     * 根据ID获取公告详情
     * @param int $id 公告ID
     * @return array
     */
    public static function getNoticeById(int $id): array
    {
        try {
            $notice = notice::with(['publisher'])->find($id);

            if (!$notice) {
                throw new \think\Exception('公告不存在');
            }

            return [
                'success' => true,
                'data' => $notice,
                'message' => '查询成功'
            ];
        } catch (\Throwable $e) {
            Log::error('公告查询失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '公告查询失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 创建公告
     * @param array $data 公告数据
     * @return array
     */
    public static function createNotice(array $data): array
    {
        try {
            Db::startTrans();

            // 提取目标用户和角色数据
            $targetUserIds = $data['target_user_ids'] ?? [];
            $targetRoleIds = $data['target_role_ids'] ?? [];
            unset($data['target_user_ids'], $data['target_role_ids']);

            // 兼容旧版本的 target_uids 字段（保留但不再使用）
            if (isset($data['target_uids']) && is_array($data['target_uids'])) {
                $data['target_uid'] = implode(',', $data['target_uids']);
                unset($data['target_uids']);
            }

            // 创建公告
            $notice = notice::create($data);

            // 插入目标用户到中间表（target_type=1）
            if (!empty($targetUserIds) && is_array($targetUserIds)) {
                foreach ($targetUserIds as $userId) {
                    noticeTarget::create([
                        'notice_id' => $notice->notice_id,
                        'target_type' => 1,  // 1=用户
                        'target_id' => $userId,
                        'read_status' => 0
                    ]);
                }
            }

            // 插入目标角色到中间表（target_type=2）
            if (!empty($targetRoleIds) && is_array($targetRoleIds)) {
                foreach ($targetRoleIds as $roleId) {
                    noticeTarget::create([
                        'notice_id' => $notice->notice_id,
                        'target_type' => 2,  // 2=角色
                        'target_id' => $roleId,
                        'read_status' => 0
                    ]);
                }
            }

            Db::commit();

            return [
                'success' => true,
                'data' => ['notice_id' => $notice->notice_id],
                'message' => '公告创建成功'
            ];
        } catch (\Throwable $e) {
            Db::rollback();

            Log::error('公告创建失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '公告创建失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 更新公告
     * @param int $id 公告ID
     * @param array $data 更新数据
     * @return array
     */
    public static function updateNotice(int $id, array $data): array
    {
        try {
            Db::startTrans();

            // 检查公告是否存在
            $notice = notice::find($id);
            if (!$notice) {
                throw new \think\Exception('公告不存在');
            }

            // 提取目标用户和角色数据
            $targetUserIds = $data['target_user_ids'] ?? null;
            $targetRoleIds = $data['target_role_ids'] ?? null;
            unset($data['target_user_ids'], $data['target_role_ids']);

            // 兼容旧版本的 target_uids 字段（保留但不再使用）
            if (isset($data['target_uids']) && is_array($data['target_uids'])) {
                $data['target_uid'] = implode(',', $data['target_uids']);
                unset($data['target_uids']);
            }

            // 更新公告
            $notice->save($data);

            // 如果提供了目标用户或角色数据，更新中间表
            if ($targetUserIds !== null || $targetRoleIds !== null) {
                // 删除旧的关联数据
                noticeTarget::where('notice_id', $id)->delete();

                // 插入新的目标用户（target_type=1）
                if (!empty($targetUserIds) && is_array($targetUserIds)) {
                    foreach ($targetUserIds as $userId) {
                        noticeTarget::create([
                            'notice_id' => $id,
                            'target_type' => 1,  // 1=用户
                            'target_id' => $userId,
                            'read_status' => 0
                        ]);
                    }
                }

                // 插入新的目标角色（target_type=2）
                if (!empty($targetRoleIds) && is_array($targetRoleIds)) {
                    foreach ($targetRoleIds as $roleId) {
                        noticeTarget::create([
                            'notice_id' => $id,
                            'target_type' => 2,  // 2=角色
                            'target_id' => $roleId,
                            'read_status' => 0
                        ]);
                    }
                }
            }

            Db::commit();

            return [
                'success' => true,
                'data' => ['notice_id' => $id],
                'message' => '公告更新成功'
            ];
        } catch (\Throwable $e) {
            Db::rollback();

            Log::error('公告更新失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '公告更新失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 删除公告
     * @param int $id 公告ID
     * @param bool $realDelete 是否真实删除
     * @return array
     */
    public static function deleteNotice(int $id, bool $realDelete = false): array
    {
        try {
            Db::startTrans();

            // 检查公告是否存在
            $notice = notice::find($id);
            if (!$notice) {
                throw new \think\Exception('公告不存在');
            }

            // 根据参数决定是软删除还是真实删除
            if ($realDelete) {
                // 真实删除：删除中间表数据
                noticeTarget::where('notice_id', $id)->delete();

                // 真实删除公告
                $notice->force()->delete();
                Log::info('公告已永久删除', ['id' => $id]);
                $message = '公告已永久删除';
            } else {
                // 软删除公告（中间表数据保留）
                $notice->delete();
                Log::info('公告已软删除', ['id' => $id]);
                $message = '公告已移到回收站';
            }

            Db::commit();

            return [
                'success' => true,
                'message' => $message
            ];
        } catch (\Throwable $e) {
            Db::rollback();

            Log::error('公告删除失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '公告删除失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 获取已删除的公告列表
     * @param array $params 查询参数
     * @return \think\Paginator
     */
    public static function getTrashedNotices(array $params = [])
    {
        // 默认分页参数
        $pageSize = $params['page_size'] ?? 10;
        $page = $params['page'] ?? 1;

        // 构建基础查询 - 只查询已软删除的公告
        $query = notice::onlyTrashed()->with(['publisher']);

        // 应用查询条件
        $query = self::buildQueryConditions($query, $params);

        // 分页查询
        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
            'query' => Request::get()
        ]);
    }

    /**
     * 恢复被软删除的公告
     * @param int $id 公告ID
     * @return array
     */
    public static function restoreNotice(int $id): array
    {
        try {
            // 查找被软删除的公告
            $notice = notice::onlyTrashed()->find($id);

            if (!$notice) {
                throw new \think\Exception('找不到已删除的公告或公告不存在');
            }

            // 恢复公告
            $notice->restore();

            // 记录日志
            Log::info('公告恢复成功', ['id' => $id]);

            return [
                'success' => true,
                'message' => '公告恢复成功'
            ];
        } catch (\Throwable $e) {
            // 记录错误日志
            Log::error('公告恢复失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '公告恢复失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 更新公告状态
     * @param int $id 公告ID
     * @param int $status 新状态
     * @return array
     */
    public static function updateNoticeStatus(int $id, int $status): array
    {
        try {
            // 检查状态值是否有效
            if (!in_array($status, [0, 1, 2])) {
                throw new \think\Exception('无效的状态值');
            }

            // 检查公告是否存在
            $notice = notice::find($id);
            if (!$notice) {
                throw new \think\Exception('公告不存在');
            }

            // 更新状态
            $notice->status = $status;
            $notice->save();

            $statusText = ['草稿', '已发布', '已撤回'][$status];

            return [
                'success' => true,
                'message' => "公告状态已更新为{$statusText}"
            ];
        } catch (\Throwable $e) {
            Log::error('公告状态更新失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '公告状态更新失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 切换公告置顶状态
     * @param int $id 公告ID
     * @return array
     */
    public static function toggleNoticeTop(int $id): array
    {
        try {
            // 检查公告是否存在
            $notice = notice::find($id);
            if (!$notice) {
                throw new \think\Exception('公告不存在');
            }

            // 切换置顶状态
            $notice->is_top = !$notice->is_top;
            $notice->save();

            $status = $notice->is_top ? '置顶' : '取消置顶';

            return [
                'success' => true,
                'message' => "公告已{$status}"
            ];
        } catch (\Throwable $e) {
            Log::error('公告置顶状态更新失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '公告置顶状态更新失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 获取用户可见的公告列表
     * @param int $userId 用户ID
     * @param array $params 额外查询参数
     * @return \think\Paginator
     */
    public static function getUserNotices(int $userId, array $params = [])
    {
        $params['user_id'] = $userId;
        return self::getNoticeList($params);
    }

    /**
     * 应用权限过滤（参考文章模块的权限设计）
     * @param \think\db\Query $query 查询构建器
     * @param array $params 查询参数（包含 current_user_id、current_user_roles 和 is_admin）
     * @return \think\db\Query
     */
    private static function applyPermissionFilter($query, array $params)
    {
        $currentUserId = $params['current_user_id'] ?? 0;
        $currentUserRoles = $params['current_user_roles'] ?? [];
        $isAdmin = $params['is_admin'] ?? false; // 是否为管理端请求

        error_log("[noticeService] applyPermissionFilter - userId: {$currentUserId}, roles: " . json_encode($currentUserRoles) . ", isAdmin: " . ($isAdmin ? 'true' : 'false'));

        // 如果是管理端请求，不应用权限过滤（管理员可以看到所有公告）
        if ($isAdmin) {
            error_log("[noticeService] 管理端请求 - 跳过权限过滤");
            return $query;
        }

        // 客户端请求：严格按照 visibility 权限过滤
        $query->where(function ($query) use ($currentUserId, $currentUserRoles) {
            // 1. 公开公告（所有人可见，包括未登录用户）
            $query->whereOr('visibility', 'public');

            // 2. 如果已登录
            if ($currentUserId > 0) {
                // 2.1 登录可见的公告
                $query->whereOr('visibility', 'login_required');

                // 2.2 指定用户可见（检查中间表）
                $query->whereOr(function ($subQuery) use ($currentUserId) {
                    $subQuery->where('visibility', 'specific_users')
                        ->whereRaw("EXISTS (
                            SELECT 1 FROM bl_notice_target
                            WHERE bl_notice_target.notice_id = bl_notice.notice_id
                              AND bl_notice_target.target_type = 1
                              AND bl_notice_target.target_id = ?
                        )", [$currentUserId]);
                });
            }

            // 3. 如果有角色信息
            if (is_array($currentUserRoles) && count($currentUserRoles) > 0) {
                $rolesStr = implode(',', array_map('intval', $currentUserRoles));
                error_log("[noticeService] 角色过滤 - rolesStr: {$rolesStr}");

                // 3.1 指定角色可见（检查中间表）
                $query->whereOr(function ($subQuery) use ($rolesStr) {
                    $subQuery->where('visibility', 'specific_roles')
                        ->whereRaw("EXISTS (
                            SELECT 1 FROM bl_notice_target
                            WHERE bl_notice_target.notice_id = bl_notice.notice_id
                              AND bl_notice_target.target_type = 2
                              AND bl_notice_target.target_id IN ({$rolesStr})
                        )");
                });
            }
        });

        return $query;
    }
}
