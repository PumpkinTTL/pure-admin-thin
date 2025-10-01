<?php

namespace app\api\services;

use app\api\model\notice;
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

        // 构建基础查询
        $query = notice::with(['publisher']);

        // 应用查询条件
        $query = self::buildQueryConditions($query, $params);

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

        // 公告类型查询
        if (isset($params['notice_type']) && $params['notice_type'] !== '') {
            $query->where('notice_type', intval($params['notice_type']));
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

        // 特定用户可见的公告查询
        if (!empty($params['user_id'])) {
            $userId = intval($params['user_id']);
            $query->where(function ($query) use ($userId) {
                // 全体公告
                $query->whereOr('notice_type', 1);
                // 部分用户公告，且包含该用户
                $query->whereOr(function ($query) use ($userId) {
                    $query->where('notice_type', 2)
                        ->whereLike('target_uid', '%,' . $userId . ',%')
                        ->whereOr('target_uid', $userId)
                        ->whereOr('target_uid', $userId . ',%')
                        ->whereOr('target_uid', '%,' . $userId);
                });
                // 个人通知，且指定给该用户
                $query->whereOr(function ($query) use ($userId) {
                    $query->where('notice_type', 3)
                        ->where('target_uid', $userId);
                });
            });

            // 只显示已发布且未过期的公告
            $query->where('status', 1)
                ->where(function ($query) {
                    $query->whereNull('expire_time')
                        ->whereOr('expire_time', '>', date('Y-m-d H:i:s'));
                });
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

            // 处理目标用户
            if (isset($data['target_uids']) && is_array($data['target_uids'])) {
                $data['target_uid'] = implode(',', $data['target_uids']);
                unset($data['target_uids']);
            }

            // 创建公告
            $notice = notice::create($data);

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

            // 处理目标用户
            if (isset($data['target_uids']) && is_array($data['target_uids'])) {
                $data['target_uid'] = implode(',', $data['target_uids']);
                unset($data['target_uids']);
            }

            // 更新公告
            $notice->save($data);

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
                // 真实删除公告
                $notice->force()->delete();
                Log::info('公告已永久删除', ['id' => $id]);
                $message = '公告已永久删除';
            } else {
                // 软删除公告
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
}