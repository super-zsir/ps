<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsBrokerAdmin extends BaseModel
{
    protected static $primaryKey = 'id';

    /**
     * 根据公会id获取管理员列表
     * @param array $bidArr
     * @return array
     */
    public static function getListByBidArr(array $bidArr): array
    {
        $list = self::getListByWhere([
            ['bid', 'in', $bidArr]
        ], 'uid');

        return $list ? Helper::arrayFilter($list, 'uid') : [];
    }
}
