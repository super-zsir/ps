<?php

namespace Imee\Models\Xs;

class XsRoomBottomConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_NORMAL = 1;
    const STATUS_CANCEL = 0;
    public static $statusMap = [
        self::STATUS_NORMAL => '生效中',
        self::STATUS_CANCEL => '失效',
    ];

    const PROPERTY_ROOM = 1;
    const PROPERTY_LIVE = 2;
    public static $propertyMap = [
        self::PROPERTY_ROOM => '聊天室',
        self::PROPERTY_LIVE => '直播',
    ];
}