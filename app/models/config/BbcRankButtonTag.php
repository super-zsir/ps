<?php

namespace Imee\Models\Config;

class BbcRankButtonTag extends BaseModel
{
    protected static $primaryKey = 'id';

    const RANK_OBJECT_DEFAULT = 0; // 默认值
    const RANK_OBJECT_PERSONAL = 2; // 个人|用户
    const RANK_OBJECT_BROKER = 1;
    const RANK_OBJECT_ROOM = 5;
    const RANK_OBJECT_GIFT = 8;
    const RANK_OBJECT_WEEK_STAR = 9;
    const RANK_OBJECT_FAMILY = 10;
    const RANK_OBJECT_CP = 11;
    const RANK_OBJECT_ANCHOR = 12;
    const RANK_OBJECT_BROKER_MEMBERS = 13;

    public static $rankObject = [
        self::RANK_OBJECT_DEFAULT        => '',
        self::RANK_OBJECT_PERSONAL       => '用户',
        self::RANK_OBJECT_CP             => 'CP',
        self::RANK_OBJECT_BROKER_MEMBERS => '公会成员',
        self::RANK_OBJECT_BROKER         => '公会',
    ];

    public static $rankObjectMap = [
        self::RANK_OBJECT_BROKER         => '公会',
        self::RANK_OBJECT_PERSONAL       => '用户',
        self::RANK_OBJECT_ROOM           => '房间',
        self::RANK_OBJECT_FAMILY         => '家族',
        self::RANK_OBJECT_CP             => 'CP',
        self::RANK_OBJECT_ANCHOR         => '主播&贡献用户',
        self::RANK_OBJECT_BROKER_MEMBERS => '公会成员',
    ];

    const TAG_LIST_TYPE_ONE = 0;
    const TAG_LIST_TYPE_TOTAL = 0; // 总榜
    const TAG_LIST_TYPE_UPGRADE = 1; // 晋级榜
    const TAG_LIST_TYPE_DAY = 2;
    const TAG_LIST_TYPE_CYCLE = 3;

    public static $tagListTypeMap = [
        self::TAG_LIST_TYPE_ONE => '单次',
        self::TAG_LIST_TYPE_DAY => '日循环',
    ];

    public static $tagListType = [
        self::TAG_LIST_TYPE_TOTAL   => '总榜',
        self::TAG_LIST_TYPE_UPGRADE => '晋级榜',
        self::TAG_LIST_TYPE_DAY     => '日榜',
        self::TAG_LIST_TYPE_CYCLE   => '周期榜',
    ];

    public static $tagListTypeMultiMap = [
        self::TAG_LIST_TYPE_ONE => '单次',
        self::TAG_LIST_TYPE_DAY => '日循环',
        self::TAG_LIST_TYPE_CYCLE => '按固定天数进行周期循环',
    ];

    const SUB_RANK_OBJECT_BROKER_MASTER = 1;
    const SUB_RANK_OBJECT_ROOM_MASTER = 2;

    public static $subRankObject = [
        self::SUB_RANK_OBJECT_BROKER_MASTER => '公会长',
        self::SUB_RANK_OBJECT_ROOM_MASTER   => '房主',
    ];
    
    const BROKER_WHITE_LIST_TYPE = 1;
    const ROOM_WHITE_LIST_TYPE = 5;
    const GUILD_WHITE_LIST_TYPE = 7;
    const GIFT_RANK_OBJECT = 8;
    const GIFT_RANK_OBJECT_WEEK_STAR = 9;
    const FAMILY_WHITE_LIST_TYPE = 10;
    const CP_RANK_OBJECT = 11;
    const ARCHER_RANK_OBJECT = 12;
}