<?php

namespace Imee\Models\Xs;

class XsLuckyFruitsLimitConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const CONFIG_TYPE_SINGLE = 1;
    const CONFIG_TYPE_BULK = 2;

    public static $configTypeMap = [
        self::CONFIG_TYPE_SINGLE => '单轮保底配置',
        self::CONFIG_TYPE_BULK => '大额中奖限制配置',
    ];
}