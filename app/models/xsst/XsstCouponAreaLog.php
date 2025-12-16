<?php


namespace Imee\Models\Xsst;

class XsstCouponAreaLog extends BaseModel
{
    public static $primaryKey = 'id';

    const TYPE_ADD = 'add';
    const TYPE_SUB = 'sub';
    const TYPE_SEND = 'send';

    public static $types = [
        self::TYPE_ADD => '新增',
        self::TYPE_SUB => '扣减',
        self::TYPE_SEND => '发放'
    ];

}