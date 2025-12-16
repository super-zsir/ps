<?php

namespace Imee\Models\Xsst;

class XsstRewardTemplate extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_INVALID = 1;
    const STATUS_EFFECTIVE = 2;

    public static $statusMap = [
        self::STATUS_INVALID   => '失效',
        self::STATUS_EFFECTIVE => '生效',
    ];

    const LIMIT_OBJECT_UNLIMITED = 1;
    const LIMIT_OBJECT_BROKER_MASTER = 2;
    const LIMIT_OBJECT_ANCHOR = 3;
    const LIMIT_OBJECT_NON_BROKER_MEMBER = 4;

    public static $limitObjectMap = [
        self::LIMIT_OBJECT_UNLIMITED         => '不限制',
        self::LIMIT_OBJECT_BROKER_MASTER     => '公会长',
        self::LIMIT_OBJECT_ANCHOR            => '主播',
        self::LIMIT_OBJECT_NON_BROKER_MEMBER => '非公会成员',
    ];

    const REWARD_TYPE_COMMODITY = 1;
    const REWARD_TYPE_VIP = 2;
    const REWARD_TYPE_OPTIONAL_PRETTY = 3;
    const REWARD_TYPE_MEDAL = 4;
    const REWARD_TYPE_CERTIFICATION_SIGN = 5;
    const REWARD_TYPE_ROOM_BACKGROUND = 6;
    const REWARD_TYPE_ROOM_CUSTOM_BACKGROUND_CARD = 7;
    const REWARD_TYPE_ROOM_TOP_CARD = 8;
    const REWARD_TYPE_ROOM_SKIN = 9;
    const REWARD_TYPE_GAME_COUPON = 10;
    const REWARD_TYPE_EMOTICONS = 11;

    public static $rewardTypeMap = [
        self::REWARD_TYPE_COMMODITY                   => '物品',
        self::REWARD_TYPE_VIP                         => 'VIP',
        self::REWARD_TYPE_OPTIONAL_PRETTY             => '自选靓号',
        self::REWARD_TYPE_MEDAL                       => '勋章',
        self::REWARD_TYPE_CERTIFICATION_SIGN          => '认证标识',
        self::REWARD_TYPE_ROOM_BACKGROUND             => '房间背景',
        self::REWARD_TYPE_ROOM_CUSTOM_BACKGROUND_CARD => '房间自定义背景卡',
        self::REWARD_TYPE_ROOM_TOP_CARD               => '房间置顶卡',
        self::REWARD_TYPE_ROOM_SKIN                   => '房间皮肤',
        self::REWARD_TYPE_GAME_COUPON                 => '游戏优惠券',
        self::REWARD_TYPE_EMOTICONS                   => '房间表情',
    ];

    public static $initRewardItemDataMap = [
        self::REWARD_TYPE_COMMODITY                   => ['id'],
        self::REWARD_TYPE_VIP                         => ['vip_level', 'vip_days', 'give_type'],
        self::REWARD_TYPE_OPTIONAL_PRETTY             => ['id', 'valid_days', 'use_valid_days', 'give_type'],
        self::REWARD_TYPE_MEDAL                       => ['id', 'valid_days'],
        self::REWARD_TYPE_CERTIFICATION_SIGN          => ['id', 'valid_days', 'content'],
        self::REWARD_TYPE_ROOM_BACKGROUND             => ['id', 'valid_days'],
        self::REWARD_TYPE_ROOM_CUSTOM_BACKGROUND_CARD => ['valid_days', 'give_type'],
        self::REWARD_TYPE_ROOM_TOP_CARD               => ['id', 'valid_days'],
        self::REWARD_TYPE_ROOM_SKIN                   => ['id', 'valid_days'],
        self::REWARD_TYPE_GAME_COUPON                 => ['id', 'big_area', 'expire'],
        self::REWARD_TYPE_EMOTICONS                   => ['id', 'valid_days'],
    ];

    public static $validRewardActionMap = [
        self::REWARD_TYPE_COMMODITY          => 'validCommodityReward',
        self::REWARD_TYPE_VIP                => 'validVipReward',
        self::REWARD_TYPE_OPTIONAL_PRETTY    => 'validPrettyReward',
        self::REWARD_TYPE_MEDAL              => 'validMedalReward',
        self::REWARD_TYPE_CERTIFICATION_SIGN => 'validCertificationSignReward',
        self::REWARD_TYPE_ROOM_BACKGROUND    => 'validRoomBackgroundReward',
        self::REWARD_TYPE_ROOM_TOP_CARD      => 'validRoomTopCardReward',
        self::REWARD_TYPE_ROOM_SKIN          => 'validRoomSkinReward',
        self::REWARD_TYPE_GAME_COUPON        => 'validGameCouponReward',
        self::REWARD_TYPE_EMOTICONS          => 'validEmoticonsReward',
    ];

    public static $rewardItemFieldMap = [
        'vip_level' => 'id',
        'vip_days'  => 'valid_days',
    ];

    /**
     * 获取全部数据map
     * @return array
     */
    public static function getListMap(): array
    {
        $list = self::getListByWhere([
            ['status', '=', self::STATUS_EFFECTIVE]
        ], 'id, name', 'id desc');
        $map = [];
        foreach ($list as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }

        return $map;
    }
}