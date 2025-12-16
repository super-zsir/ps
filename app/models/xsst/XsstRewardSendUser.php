<?php

namespace Imee\Models\Xsst;

use Imee\Service\Helper;

class XsstRewardSendUser extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_WAIT = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;


    public static $statusMap = [
        self::STATUS_WAIT    => '待处理',
        self::STATUS_SUCCESS => '发放成功',
        self::STATUS_FAIL    => '发放失败',
    ];

    /**
     * 统计模版下uid的发放次数
     * @param int $tid
     * @return array
     */
    public static function getUidSendNumByTidList(int $tid, array $uidArr, int $now): array
    {
        $list = self::getListByWhere([
            ['tid', '=', $tid],
            ['uid', 'IN', $uidArr],
            ['dateline', '>=', $now - 30 * 86400],
            ['dateline', '<', $now]
        ], 'COUNT(*) AS num, uid', 'uid desc', 0, 0, 'uid');

        return $list ? array_column($list, 'num', 'uid') : [];
    }

    /**
     * 统计任务下用户的发放次数
     * @param array $taskId
     * @return array
     */
    public static function getSendNumByTaskIdList(array $taskId): array
    {
        $list = self::getListByWhere([
            ['task_id', 'IN', $taskId],
        ], 'COUNT(*) AS num, task_id', 'task_id desc', 0, 0, 'task_id');

        return $list ? array_column($list, 'num', 'task_id') : [];
    }

    /**
     * 获取task下的待发送和发送失败的uid列表
     * @param int $taskId
     * @return array
     */
    public static function getListByTaskId(int $taskId): array
    {
        $list = self::getListByWhere([
            ['task_id', '=', $taskId],
            ['status', 'IN', [self::STATUS_FAIL, self::STATUS_WAIT]]
        ], 'uid');

        return $list ? array_column($list, 'uid') : [];
    }

    /**
     * 获取用户奖励下发状态
     * @param int $taskId
     * @param int $uid
     * @return array
     */
    public static function findRewardSendUser(int $taskId, int $uid): array
    {
        $info = self::findOneByWhere([
            ['task_id', '=', $taskId],
            ['status', 'IN', [self::STATUS_FAIL, self::STATUS_WAIT]],
            ['uid', '=', $uid]
        ]);

        return $info ? self::formatJson($info['reward_send_status']) : [];
    }

    /**
     * 获取任务下用户下发状态
     * @param int $taskId
     * @param int $status
     * @return array
     */
    public static function findTaskSendStatus(int $taskId, int $status): array
    {
        return self::useMaster()::findOneByWhere([
            ['task_id', '=', $taskId],
            ['status', '=', $status],
        ]);
    }

    /**
     * 格式化json
     * @param $json
     * @return array
     */
    public static function formatJson($json): array
    {
        if (empty($json)) {
            return [];
        }

        $json = str_replace('&quot;', '"', $json);
        return @json_decode($json, true);
    }
}