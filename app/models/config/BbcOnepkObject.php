<?php

namespace Imee\Models\Config;

class BbcOnepkObject extends BaseModel
{
    protected static $primaryKey = 'id';

    // 对战是否开始
    const STATE_WAIT = 1; // 待开始
    const STATE_HAVE = 2; // 进行中

    // 对战是否生效
    const STATUS_EFFECTIVE = 1; // 生效
    const STATUS_INVALID = 0; // 未生效

    public static function check($objId, $startTime, $endTime, $id = 0)
    {
        $info = self::findFirst([
            'columns' => 'id',
            'conditions' => '(onepk_objid_1 = :obj_id1: OR onepk_objid_2 = :obj_id2:) AND start_time < :start_time: AND end_time > :end_time: AND id <> :id:',
            'bind' => [
                'id'         => $id,
                'obj_id1'    => $objId,
                'obj_id2'    => $objId,
                'start_time' => $endTime,
                'end_time'   => $startTime,
            ],
        ]);
        return $info ? $info->toArray() : [];
    }

    public static function getInfoByActId($actId, $column = null, $searchKey = 'id'): array
    {
        $ids = self::getListByWhere([
            ['act_id', '=', $actId]
        ], '*');

        return $ids ? array_column($ids, $column, $searchKey) : [];
    }
}