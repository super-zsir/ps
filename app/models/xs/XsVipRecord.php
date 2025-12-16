<?php

namespace Imee\Models\Xs;

class XsVipRecord extends BaseModel
{
    const RECORD_TYPE_DEDUCT = 1;
    const RECORD_TYPE_GIVE = 2;
    const RECORD_TYPE_BUY = 3;
    const RECORD_TYPE_VIP = 4;
    public static $displayRecordType = [
        self::RECORD_TYPE_DEDUCT => '扣除',
        self::RECORD_TYPE_GIVE => '发放',
        self::RECORD_TYPE_BUY => '购买',
        self::RECORD_TYPE_VIP=> '使用VIP卡',
    ];
}
