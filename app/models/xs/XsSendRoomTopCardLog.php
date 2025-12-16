<?php

namespace Imee\Models\Xs;

/**
 * 用户置顶卡发放记录
 */
class XsSendRoomTopCardLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const SOURCE_GRANT = 1;
    const SOURCE_GIVE = 2;

    public static $sourceMap = [
        self::SOURCE_GRANT => '发放',
        self::SOURCE_GIVE => '赠送',
    ];
}