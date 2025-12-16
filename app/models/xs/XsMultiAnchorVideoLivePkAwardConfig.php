<?php

namespace Imee\Models\Xs;

class XsMultiAnchorVideoLivePkAwardConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const COMMODITY_TYPE_EFFECT = 'effect';
    const COMMODITY_TYPE_BUBBLE = 'bubble';
    const COMMODITY_TYPE_HEADER = 'header';
    const COMMODITY_TYPE_MOUNTS = 'mounts';
    const COMMODITY_TYPE_RING = 'ring';
    const COMMODITY_TYPE_DECORATE = 'decorate';
    const TYPE_COMMODITY = 1;

    const TYPE_ROOM_BACKGROUND = 3;

    const COMMODITY_TYPE_PK = 20;
    const TYPE_EMOTICON = 19;

    public static $typeMap = [
        self::COMMODITY_TYPE_EFFECT   => '入场横幅',
        self::COMMODITY_TYPE_BUBBLE   => '聊天气泡',
        self::TYPE_ROOM_BACKGROUND    => '房间背景',
        self::COMMODITY_TYPE_HEADER   => '头像框',
        self::COMMODITY_TYPE_MOUNTS   => '座驾',
        self::COMMODITY_TYPE_RING     => '麦上光圈',
        self::COMMODITY_TYPE_PK       => 'PK道具卡',
        self::COMMODITY_TYPE_DECORATE => '主页装扮',
        self::TYPE_EMOTICON           => '表情包',
    ];
    public static $commodityTypeArr = ['effect', 'bubble', 'header', 'mounts', 'ring', 'decorate'];

    # config 案例: [{"id":4312,"type":1,"weight":10,"num":1}]
}