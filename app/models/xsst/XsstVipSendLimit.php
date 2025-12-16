<?php

namespace Imee\Models\Xsst;

class XsstVipSendLimit extends BaseModel
{
    public static $createTime = 'create_time';
    public static $updateTime = 'update_time';

    const PERIOD_MONTH = 1;
    const PERIOD_MONTH_HALF = 2;

    public static function getByVips(array $vips): array
    {
        $data = [];
        $limits = self::getListByWhere([['vip', 'in', $vips]], 'vip,bigarea_id,period,num');

        foreach ($limits as $item) {
            $k = $item['vip'] . '_' . $item['bigarea_id'];
            $data[$k] = $item;
        }

        return $data;
    }
}