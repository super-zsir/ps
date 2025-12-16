<?php

namespace Imee\Models\Xs;

class XsPropCardUseLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const CARD_TYPE_DEFAULT = 0;
    const CARD_TYPE_RELEASE = 1;
    const CARD_TYPE_CAN_SEND_VIP_CARD = 2;
    const CARD_TYPE_VIP_CARD = 3;
    const CARD_TYPE_RELIEVE_FORBIDDEN_CARD = 4;

    const TYPE_PK_PROP_CARD_ADD = 5;//加成卡 5
    const TYPE_PK_PROP_CARD_MAG = 6;//磁力卡 6

    public static $cardTypeMap = [
        self::CARD_TYPE_DEFAULT                => '未知',
        self::CARD_TYPE_RELEASE                => '解除卡',
        self::CARD_TYPE_CAN_SEND_VIP_CARD      => 'vip卡可赠送',
        self::CARD_TYPE_VIP_CARD               => 'vip卡不可转赠',
        self::CARD_TYPE_RELIEVE_FORBIDDEN_CARD => '解封卡',
        self::TYPE_PK_PROP_CARD_ADD            => '加成卡',
        self::TYPE_PK_PROP_CARD_MAG            => '磁力卡',
    ];

}