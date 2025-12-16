<?php
namespace Imee\Models\Xs;

class XsUserCustomizePretty extends BaseModel
{
    const STATUS_INIT = 0;
    const STATUS_USED = 1;
    const STATUS_GIVE = 2;

    const STATUS_EXPIRE = 12;//注，这块来身数据表是没有的

    public static $displayStatus = [
        self::STATUS_INIT => '待使用',
        self::STATUS_USED => '已使用',
        self::STATUS_GIVE => '已赠送',
        self::STATUS_EXPIRE => '已过期',
    ];

    public static $giveTypeMaps = [
        0 => '不可转赠',
        1 => '可转赠',
    ];
}
