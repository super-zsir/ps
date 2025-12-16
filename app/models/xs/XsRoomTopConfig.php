<?php

namespace Imee\Models\Xs;

class XsRoomTopConfig extends BaseModel
{
    protected $primaryKey = 'id';

    const PROPERTY_ROOM_TOP = 1;
    const PROPERTY_LIVE_VIDEO_TOP = 2;

    public static $propertyMap = [
        self::PROPERTY_ROOM_TOP       => 'vip',
        self::PROPERTY_LIVE_VIDEO_TOP => 'liveroom',
    ];

    const TYPE_TOP = 1;
    const TYPE_REMOVE = 2;

    const STATUS_CANCEL = 0;
    const STATUS_EFFECT = 1;
    const STATUS_FAIL = 2;
    const STATUS_NOT_START = 3;

    public static $statusMap = [
        self::STATUS_CANCEL    => '已取消',
        self::STATUS_EFFECT    => '已生效',
        self::STATUS_FAIL      => '已失效',
        self::STATUS_NOT_START => '未开始',
    ];

    const DELETED_WAIT_ADD = -1;
    const DELETED_NORMAL = 0;
    const DELETED_SHUT_DOWN = 1;
    const DELETED_FORBIDDEN = 2;

    public static $deletedMap = [
        self::DELETED_WAIT_ADD  => '待添加',
        self::DELETED_NORMAL    => '正常',
        self::DELETED_SHUT_DOWN => '关闭',
        self::DELETED_FORBIDDEN => '封禁',
    ];


}