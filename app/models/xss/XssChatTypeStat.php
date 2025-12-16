<?php

namespace Imee\Models\Xss;

class XssChatTypeStat extends BaseModel
{
    const USER_DRIVING = 1;//用户主动
    const SERVICE_DRIVING = 2;//客服主动
    public static $activeType = [
        self::USER_DRIVING => '用户主动',
        self::SERVICE_DRIVING => '客服主动',
    ];
}
