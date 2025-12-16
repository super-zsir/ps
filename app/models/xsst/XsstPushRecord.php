<?php

namespace Imee\Models\Xsst;

use Imee\Service\Helper;

class XsstPushRecord extends BaseModel
{
    public static $primaryKey = 'id';

    const NOT_SENT_STATUS = 0;  // 未发送
    const SENT_STATUS = 1;      // 已发送
    const SENT_FAIL = 2;      // 已拒绝
    const SENT_PLAIN = 3;      // 定时发送
    const SENT_RECALL = 4;      // 已撤回

    /**
     * 根据task_id和发送状态获取数据
     * @param int $taskId
     * @param int $status
     * @return array
     */
    public static function getListByTaskIdAndStatus(int $taskId, int $status): array
    {
        return self::getListByWhere([
            ['task_id', '=', $taskId],
            ['status', '=', $status]
        ]);
    }

    /**
     * 获取最大id
     * @return int
     */
    public static function getMaxId(): int
    {
        $result = Helper::fetchOne("SELECT MAX(id) as max_id FROM xsst_push_record", null, self::SCHEMA);
        return $result ? $result['max_id'] : 0;
    }
}