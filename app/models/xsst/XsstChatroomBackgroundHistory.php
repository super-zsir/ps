<?php

namespace Imee\Models\Xsst;

class XsstChatroomBackgroundHistory extends BaseModel
{
    protected $primaryKey = 'id';

    /**
     * 获取最近的一条操作日志
     * @param $sidArray
     * @return array
     */
    public static function getFirstLogList(array $sidArray): array
    {
        if (!$sidArray) {
            return [];
        }

        $condition = [];
        $condition[] = ['sid', 'IN', $sidArray];
        $data = self::getListByWhere($condition, 'sid,update_uname,dateline', 'id desc');

        $res = [];
        foreach ($data as $val) {
            if (!empty($res[$val['sid']])) {
                continue;
            }
            $res[$val['sid']] = $val;
        }

        return $res;
    }
}