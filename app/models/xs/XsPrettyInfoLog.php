<?php

namespace Imee\Models\Xs;

class XsPrettyInfoLog extends BaseModel
{
    const REASON_ADMIN = 0;
    const REASON_BUY = 1;
    const REASON_CUSTOMIZE = 2;
    public static $displayReason = [
        self::REASON_ADMIN => '后台操作',
        self::REASON_BUY => '购买靓号',
        self::REASON_CUSTOMIZE => '自选靓号',
    ];
}
