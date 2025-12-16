<?php

namespace Imee\Models\Xs;

class XsActTaskAwardList extends BaseModel
{
    const AWARD_TASK_TYPE_DEFAULT = 0;
    const AWARD_TASK_TYPE_MULTI = 2;
    const AWARD_TASK_TYPE_EXCHANGE = 3;

    public static $awardTaskTypeMap = [
        self::AWARD_TASK_TYPE_DEFAULT  => '单线任务',
        self::AWARD_TASK_TYPE_MULTI    => '多线独立任务',
        self::AWARD_TASK_TYPE_EXCHANGE => '积分兑换',
    ];

    /**
     * 获取用户各个rank下积分兑换奖励数量
     * @param int $actId
     * @param array $uidArr
     * @return array
     */
    public static function getUserRewardInfoList(int $actId, array $uidArr): array
    {
        $list = self::getListByWhere([
            ['act_id', '=', $actId],
            ['award_task_type', '=', self::AWARD_TASK_TYPE_EXCHANGE],
            ['object_id', 'IN', $uidArr]
        ], 'object_id, top, count(id) AS total_num', '', 0, 0, 'object_id, top');

        if (empty($list)) {
            return [];
        }

        $data = [];

        foreach ($list as $item) {
            $data[$item['object_id']][$item['top']] = $item['total_num'];
        }

        return $data;
    }
}