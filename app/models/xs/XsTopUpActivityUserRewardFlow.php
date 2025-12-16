<?php

namespace Imee\Models\Xs;

class XsTopUpActivityUserRewardFlow extends BaseModel
{
    protected static $primaryKey = 'id';

    const IS_BROKER_YES = 1;
    const IS_BROKER_NO = 0;

    public static $isBroker = [
        self::IS_BROKER_NO => '否',
        self::IS_BROKER_YES => '是'
    ];
}