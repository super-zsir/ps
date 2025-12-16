<?php


namespace Imee\Models\Xs;


class XsUserCouponLog extends BaseModel
{
    public static $primaryKey = 'id';

    const COUPON_TYPE = 1;//游戏优惠券

    const ACTION_ADD = 1;
    const ACTION_SUB = 2;

    const OP_ISSUED = 1;
    const OP_SUB = 2;
    const OP_USED = 3;
    const OP_OUT_TIME = 4;
    const OP_EXCHANGE = 5;
    const OP_GIFT_BAG_ISSUED = 6;
    const OP_GIFT_BAG_OUT_TIME = 7;
    const OP_GIFT_BAG_DELETE = 8;
    const COUPON_OP_OP_TOP_UP_REACH_GRANT = 9;//不再使用 统一用活动发放报
    const COUPON_OP_OP_ACT_GRANT = 10;

    public static $op = [
        self::OP_ISSUED            => '下发',
        self::OP_SUB               => '扣除',
        self::OP_USED              => '使用',
        self::OP_OUT_TIME          => '过期',
        self::OP_EXCHANGE          => '兑换',
        self::OP_GIFT_BAG_ISSUED   => '新人礼包下发',
        self::OP_GIFT_BAG_OUT_TIME => '新人礼包过期',
        self::OP_GIFT_BAG_DELETE   => '新人礼包删除',
        self::COUPON_OP_OP_ACT_GRANT   => '活动发放',
    ];

}