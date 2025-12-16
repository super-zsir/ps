<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsUserItemCard extends BaseModel
{
    public const SCHEMA_READ = 'xsserverslave';

    public static $statusMap = [
        1 => '未生效',
        2 => '已生效',
        3 => '已失效',
    ];

    public static function getByIds(array $ids): array
    {
        $data = self::findByIds($ids, 'id,can_give,days,expire_time,period_end');
        foreach ($data as &$item) {
            $item['expire_time'] = $item['expire_time'] > 0 ? Helper::now($item['expire_time']) : '';
            $item['period_end'] = $item['period_end'] > 0 ? Helper::now($item['period_end']) : '';
        }

        return array_column($data, null, 'id');
    }
}