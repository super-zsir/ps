<?php

namespace Imee\Models\Xs;

class XsSicBoUserLog extends BaseModel
{
    protected static $primaryKey = 'id';

    /**
     * 获取轮次数量
     * @param $condition
     * @return mixed
     */
    public static function getCountByRound($condition)
    {
        $res = self::findFirst([
            'columns' => 'count(distinct(round_id)) as count',
            'conditions' => $condition,
        ])->toArray();

        return $res['count'];
    }

    public static function getUidByRoundIds($roundIds, $bigArea)
    {
        $uids = [];

        $list = self::getListByWhere([
            ['round_id', 'in', $roundIds],
            ['bigarea_id', '=', $bigArea]
        ]);

        if ($list) {
            $uids = array_column($list, 'uid');
        }

        return $uids;
    }
}