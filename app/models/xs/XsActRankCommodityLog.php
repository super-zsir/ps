<?php

namespace Imee\Models\Xs;

class XsActRankCommodityLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const DIAMOND_AWARD = 1;
    const BEAN_AWARD = 2;
    const COMMODITY_AWARD = 3;

    public static $awardTypeMap = [
        self::DIAMOND_AWARD   => '钻石',
        self::BEAN_AWARD      => '金豆',
        self::COMMODITY_AWARD => '物品',
    ];

    const PROP_CARD_TYPE_PROP_CARD_UNKNOW                  = 0;
	const PROP_CARD_TYPE_PROP_CARD_RELIEVE_CARD            = 1; // 解除卡
	const PROP_CARD_TYPE_PROP_CARD_CAN_SEND_VIP_CARD       = 2; // vip卡可赠送
	const PROP_CARD_TYPE_PROP_CARD_VIP_CARD                = 3; // vip卡不可转赠
	const PROP_CARD_TYPE_PROP_CARD_RELIEVE_FORBIDDEN_CARD  = 4; // 解封卡
	const PROP_CARD_TYPE_PROP_CARD_PK_BONUS                = 5; // PK加成卡
	const PROP_CARD_TYPE_PROP_CARD_PK_MAGNETIC             = 6; // PK磁力卡
	const PROP_CARD_TYPE_PROP_CARD_INTIMATE_RELATION_ICON  = 7; // 亲密关系增值道具-亲密关系ICON
	const PROP_CARD_TYPE_PROP_CARD_RELATION_HEAD_FRAME     = 8; // 关系头像框

    public static $propCardTypeMap = [
        self::PROP_CARD_TYPE_PROP_CARD_UNKNOW                  => '未知',
        self::PROP_CARD_TYPE_PROP_CARD_RELIEVE_CARD            => '解除卡',
        self::PROP_CARD_TYPE_PROP_CARD_CAN_SEND_VIP_CARD       => 'vip卡可赠送',
        self::PROP_CARD_TYPE_PROP_CARD_VIP_CARD                => 'vip卡不可转赠',
        self::PROP_CARD_TYPE_PROP_CARD_RELIEVE_FORBIDDEN_CARD  => '解封卡',
        self::PROP_CARD_TYPE_PROP_CARD_PK_BONUS                => 'PK加成卡',
        self::PROP_CARD_TYPE_PROP_CARD_PK_MAGNETIC             => 'PK磁力卡',
        self::PROP_CARD_TYPE_PROP_CARD_INTIMATE_RELATION_ICON  => '亲密关系增值道具-亲密关系ICON',
        self::PROP_CARD_TYPE_PROP_CARD_RELATION_HEAD_FRAME     => '关系头像框',
    ];
}