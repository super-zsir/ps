<?php

namespace Imee\Models\Xsst;

class XsstCommoditySendExtend extends BaseModel
{
    protected static $primaryKey = 'sid';

    public static $source = '官方发放';

    /**
     * 根据发奖id获取扩展数据
     * @param $sidArr
     * @return array
     */
    public static function getListBySid($sidArr): array
    {
        $list = self::getListByWhere([['sid', 'IN', $sidArr]], 'sid, source');
        return $list ? array_column($list, null, 'sid') : [];
    }
}