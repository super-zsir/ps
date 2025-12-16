<?php

namespace Imee\Models\Xs;

class XsPkPropCardFirstGiftConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_NORMAL = 1;
    const STATUS_DELETED = 0;


    const COMMODITY_TYPE_EMPTY = 0;
    const COMMODITY_TYPE_PK = 20;

    public static $typeMap = [
        self::COMMODITY_TYPE_EMPTY => '空奖励',
        self::COMMODITY_TYPE_PK    => 'PK道具卡',
    ];

}