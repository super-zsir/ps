<?php

namespace Imee\Models\Xs;

class XsInteractiveEmoticons extends BaseModel
{
    protected static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';

    const TYPE_ONE = 1;

    public static $typeMap = [
        self::TYPE_ONE => '游戏机',
    ];

    const STATE_NORMAL = 1;
    const STATE_DOWN = 2;

    public static $stateMap = [
        self::STATE_NORMAL => '上架',
        self::STATE_DOWN   => '下架',
    ];

    const SCENE_VOICE = 'voice';
    const SCENE_VIDEO = 'video';
    const SCENE_CHAT = 'chat';
    public static $sceneMap = [
        self::SCENE_VOICE => '语音房',
        self::SCENE_VIDEO => '视频房',
        self::SCENE_CHAT  => '私聊',
    ];
}