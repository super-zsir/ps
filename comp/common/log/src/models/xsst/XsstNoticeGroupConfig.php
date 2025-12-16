<?php

namespace Imee\Comp\Common\Log\Models\Xsst;

class XsstNoticeGroupConfig extends BaseModel
{
    public static $primaryKey = 'id';

    const STATUS_VALID = 0;
    const STATUS_INVALID = 1;

    /**
     * 获取通知群名称
     * @param array $ids
     * @return array
     */
    public static function getNameByIds(array $ids): array
    {
        $list = self::getListByWhere([
            ['status', '=', self::STATUS_VALID]
        ], 'id, name');

        return $list ? array_column($list, 'name', 'id') : [];
    }
}