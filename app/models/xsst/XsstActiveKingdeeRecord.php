<?php

namespace Imee\Models\Xsst;

class XsstActiveKingdeeRecord extends BaseModel
{
    public static $primaryKey = 'id';

    const WAIT_STATUS = 1;
    const END_STATUS = 2;

    const TYPE_RANK = 1;
    const TYPE_RECHARGE = 2;
    const TYPE_TASK = 3;
    const TYPE_WHEEL_LOTTERY = 4;
    const TYPE_WHEEL_LOTTERY_STOCK = 5;
    const TYPE_MULTI_TASK = 6;

    public static $typeMap = [
        'task'          => self::TYPE_TASK,
        'gift_task'     => self::TYPE_TASK,
        'onepk'         => self::TYPE_RANK,
        'wheel_lottery' => self::TYPE_WHEEL_LOTTERY,
        'multi_task'    => self::TYPE_MULTI_TASK,
    ];

}