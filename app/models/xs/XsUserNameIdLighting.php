<?php

namespace Imee\Models\Xs;

class XsUserNameIdLighting extends BaseModel
{
    protected static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';

    const CAN_GIVE_YES = 1;
    const CAN_GIVE_NO = 0;
    public static $canGiveMap = [
        self::CAN_GIVE_YES => '可以赠送',
        self::CAN_GIVE_NO  => '不可赠送',
    ];

    const SCENE_NICKNAME = 1;
    const SCENE_UID = 2;
    const SCENE_ALL = 3;
    public static $sceneMap = [
        self::SCENE_NICKNAME => '昵称',
        self::SCENE_UID      => 'uid',
        self::SCENE_ALL      => '所有',
    ];

    const SOURCE_BACKEND = 1;
    const SOURCE_USER = 2;
    const SOURCE_ACTIVITY = 3;
    public static $sourceMap = [
        self::SOURCE_BACKEND  => '后台下发',
        self::SOURCE_USER     => '用户赠送',
        self::SOURCE_ACTIVITY => '活动下发',
    ];

    const STATUS_NORMAL = 1;
    const STATUS_EFFECTIVE = 2;
    const STATUS_INVALID = 3;
    public static $statusMap = [
        self::STATUS_NORMAL    => '未生效',
        self::STATUS_EFFECTIVE => '已生效',
        self::STATUS_INVALID   => '已失效',
    ];

    const DRESS_STATUS_NOT_DRESS = 1;
    const DRESS_STATUS_DRESSED = 2;
    public static $dressStatusMap = [
        self::DRESS_STATUS_NOT_DRESS => '未装扮',
        self::DRESS_STATUS_DRESSED   => '已装扮',
    ];

}