<?php

namespace Imee\Models\Xs;

class XsDelayForbiddenTask extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_NOT_EXECUTED = 0;
    const STATUS_EXECUTED = 1;
    const STATUS_INVALID = 2;
    public static $statusMap = [
        self::STATUS_NOT_EXECUTED => '未执行',
        self::STATUS_EXECUTED => '已执行',
        self::STATUS_INVALID => '已失效',
    ];

}