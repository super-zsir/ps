<?php

namespace Imee\Models\Xsst;

class XsstWheelLotteryAwardAuditRecord extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_DEFAULT = 0;
    const STATUS_WAIT = 1;
    const STATUS_ERROR = 2;
    const STATUS_SUCCESS = 3;

    public static $statusMap = [
        self::STATUS_DEFAULT => '不需要审核',
        self::STATUS_WAIT    => '待审核',
        self::STATUS_ERROR   => '审核失败',
        self::STATUS_SUCCESS => '审核成功',
    ];
}