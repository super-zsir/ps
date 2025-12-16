<?php

namespace Imee\Models\Xs;

class XsBmsRoomTop extends BaseModel
{
    protected $primaryKey = 'id';

    /**
     * 获取房间移除列表
     * @param array $tidArray
     * @return array
     */
    public static function getListByTid(array $tidArray): array
    {
        if (empty($tidArray)) {
            return [];
        }

        $list = self::getListByWhere([
            ['tid', 'IN', $tidArray]
        ]);

        return $list ? array_column($list, null, 'tid') : [];
    }

    /**
     * 根据tid获取数据
     * @param int $tid
     * @return array
     */
    public static function getInfoByTid(int $tid): array
    {
        return self::findOneByWhere([
            ['tid', '=', $tid]
        ]);
    }
}