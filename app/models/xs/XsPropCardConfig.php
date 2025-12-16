<?php

namespace Imee\Models\Xs;

class XsPropCardConfig extends BaseModel
{
    public static $primaryKey = 'id';

    const TYPE_RELIEVE_CARD = 1;            // 分手卡｜解除卡
    const TYPE_CAN_SEND_VIP_CARD = 2;       // vip卡可赠送
    const TYPE_VIP_CARD = 3;                // vip卡不可转赠
    const TYPE_RELIEVE_FORBIDDEN_CARD = 4;  // 解封卡
    const TYPE_PK_PROP_CARD_ADD = 5;//加成卡 5
    const TYPE_PK_PROP_CARD_MAG = 6;//磁力卡 6
    const TYPE_PK_PROP_CARD_INTIMATE_RELATION_ICON = 7;// 关系增值道具 7
    const TYPE_PK_PROP_CARD_RELATION_AVATAR_FRAME = 8; // 关系头像框 8

    public static $typeMaps = [
        self::TYPE_RELIEVE_CARD                        => '分手卡',
        self::TYPE_RELIEVE_FORBIDDEN_CARD              => '解封卡',
        self::TYPE_PK_PROP_CARD_ADD                    => '加成卡',
        self::TYPE_PK_PROP_CARD_MAG                    => '磁力卡',
        self::TYPE_PK_PROP_CARD_INTIMATE_RELATION_ICON => '关系增值道具',
        self::TYPE_PK_PROP_CARD_RELATION_AVATAR_FRAME  => '关系头像框',
    ];

    public static $typeAllMaps = [
        self::TYPE_RELIEVE_CARD                        => '分手卡',
        self::TYPE_CAN_SEND_VIP_CARD                   => 'vip卡可赠送',
        self::TYPE_VIP_CARD                            => 'vip卡不可转赠',
        self::TYPE_RELIEVE_FORBIDDEN_CARD              => '解封卡',
        self::TYPE_PK_PROP_CARD_ADD                    => '加成卡',
        self::TYPE_PK_PROP_CARD_MAG                    => '磁力卡',
        self::TYPE_PK_PROP_CARD_INTIMATE_RELATION_ICON => '关系增值道具',
        self::TYPE_PK_PROP_CARD_RELATION_AVATAR_FRAME  => '关系头像框',
    ];

    public static $typeBackPackMaps = [
        self::TYPE_CAN_SEND_VIP_CARD => 'vip卡可赠送',
        self::TYPE_VIP_CARD          => 'vip卡不可转赠',
    ];

    //pk道具卡
    public static $typePkPropCardMaps = [
        self::TYPE_PK_PROP_CARD_ADD => '加成卡',
        self::TYPE_PK_PROP_CARD_MAG => '磁力卡',
    ];

    const RELATION_TYPE_CP = 1;           // cp
    const RELATION_TYPE_BEST_FRIEND = 2;  // 挚友

    // 关系类型
    public static $relationTypeMaps = [
        self::RELATION_TYPE_CP          => 'cp',
        self::RELATION_TYPE_BEST_FRIEND => '挚友',
    ];

    // 可购买和可用的关系等级
    public static $buyUseLevelMaps = [
        1 => 'LV1',
        2 => 'LV2',
        3 => 'LV3',
        4 => 'LV4',
        5 => 'LV5',
        6 => 'LV6',
        7 => 'LV7',
    ];

    public static function getPropCardConfigMaps(): array
    {
        $map = [];
        $lists = XsPropCardConfig::getListByWhere([], 'id, name_json', 'id desc');
        foreach ($lists as $list) {
            $_id = array_get($list, 'id', 0);
            $_name = @json_decode(array_get($list, 'name_json', ''), true);
            $map[$_id] = $_id . ' - ' . array_get($_name, 'cn', '');
        }
        return $map;
    }
}