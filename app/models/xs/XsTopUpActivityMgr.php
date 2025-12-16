<?php

namespace Imee\Models\Xs;

class XsTopUpActivityMgr extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_OFF = 0;
    const STATUS_ON = 1;

    public static $statusMap = [
        self::STATUS_OFF => '关闭',
        self::STATUS_ON  => '开启',
    ];
}