<?php

namespace Imee\Models\Xs;

class XsActHonourWallTab extends BaseModel
{
    protected static $primaryKey = 'id';

    const ACT_HONOUR_WALL_SOURCE_RANK = 1;
    const ACT_HONOUR_WALL_SOURCE_USER = 2;
    const ACT_HONOUR_WALL_SOURCE_CONTRIBUTION = 3;
    const ACT_HONOUR_WALL_SOURCE_FAMILY = 4;
    const ACT_HONOUR_WALL_SOURCE_CP = 5;
    const ACT_HONOUR_WALL_SOURCE_GIFT = 6;

    const ACT_HONOUR_WALL_SOURCE_CUSTOM = 2; // 自定义输入

    public static $actHonourWallSourceMap = [
        self::ACT_HONOUR_WALL_SOURCE_RANK         => '榜单',
        self::ACT_HONOUR_WALL_SOURCE_USER         => '用户',
        self::ACT_HONOUR_WALL_SOURCE_CONTRIBUTION => '公会&成员/主播&贡献者/房主&贡献者',
        self::ACT_HONOUR_WALL_SOURCE_FAMILY       => '家族&成员',
        self::ACT_HONOUR_WALL_SOURCE_CP           => 'CP',
        self::ACT_HONOUR_WALL_SOURCE_GIFT         => '定制礼物',
    ];

    public static $sourceMap = [
        self::ACT_HONOUR_WALL_SOURCE_RANK   => '榜单',
        self::ACT_HONOUR_WALL_SOURCE_CUSTOM => '自定义输入',
    ];

    public static $objectTypeMap = [
        self::ACT_HONOUR_WALL_SOURCE_USER         => '用户',
        self::ACT_HONOUR_WALL_SOURCE_CONTRIBUTION => '公会&成员/主播&贡献者/房主&贡献者',
        self::ACT_HONOUR_WALL_SOURCE_FAMILY       => '家族&成员',
        self::ACT_HONOUR_WALL_SOURCE_CP           => 'CP',
        self::ACT_HONOUR_WALL_SOURCE_GIFT         => '定制礼物',
    ];


    /**
     * 数据来源字段需要转一下
     * @param int $source
     * @return int
     */
    public static function getSourceType(int $source): int
    {
        return $source == self::ACT_HONOUR_WALL_SOURCE_RANK ? self::ACT_HONOUR_WALL_SOURCE_RANK : self::ACT_HONOUR_WALL_SOURCE_CUSTOM;
    }

    /**
     * 获取对象类型
     * @param int $source
     * @return int
     */
    public static function getObjectType(int $source): int
    {
        return in_array($source, array_keys(self::$objectTypeMap)) ? $source : 0;
    }


}