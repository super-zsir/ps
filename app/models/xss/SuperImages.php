<?php

namespace Imee\Models\Xss;

class SuperImages extends BaseModel
{
    // 公共协议
    const OPERATE_TYPE_UNKNOWN = 0;
    const OPERATE_TYPE_ROOM_BOTTOM = 1;
    const OPERATE_TYPE_CLOSE_ROOM = 2;
    const OPERATE_TYPE_FORBIDDEN_ROOM = 3;
    const OPERATE_TYPE_REMOVE_ROOM_FEED = 4;
    const OPERATE_TYPE_CHANGE_ROOM_ICON = 5;
    const OPERATE_TYPE_ACTIVE_CHECK = 6;

    // 操作内容枚举
    public static $operateType = [
        self::OPERATE_TYPE_UNKNOWN => '未知',
        self::OPERATE_TYPE_ROOM_BOTTOM => '置底房间',
        self::OPERATE_TYPE_CLOSE_ROOM => '关闭房间',
        self::OPERATE_TYPE_FORBIDDEN_ROOM => '封禁房间',
        self::OPERATE_TYPE_REMOVE_ROOM_FEED => '移除房间feed',
        self::OPERATE_TYPE_CHANGE_ROOM_ICON => '更换房间封面',
        self::OPERATE_TYPE_ACTIVE_CHECK => '活跃检查弹窗',
    ];


    const ROOM_TYPE_AUDIO = 0;
    const ROOM_TYPE_VIDEO = 1;
    // 房间类型
    public static $roomType = [
        self::ROOM_TYPE_AUDIO => '语音',
        self::ROOM_TYPE_VIDEO => '视频',
    ];

}