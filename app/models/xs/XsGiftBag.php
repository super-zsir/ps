<?php

namespace Imee\Models\Xs;

class XsGiftBag extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_VALID = 0;
    const STATUS_UNVALID = 1;

    public static $displayStatus = [
        self::STATUS_VALID => '生效中',
        self::STATUS_UNVALID => '已禁用',
    ];

    const REWARD_CAR = 1;
    const REWARD_BUBBLE = 2;
    const REWARD_MIC_APERTURE = 3;
    const REWARD_AVATAR_FARME = 4;
    const REWARD_ENTER_EFFECT = 5;
    const REWARD_GIFT = 6;
    const REWARD_MEDAL = 7;
    const REWARD_BACKGROUND = 8;
    const REWARD_EXP = 9;
    const REWARD_PRETTY_UID = 10;
    const REWARD_GAME_COUPON = 11;
    const REWARD_VIP = 12;

    public static $commodityList = [
        self::REWARD_CAR,
        self::REWARD_BUBBLE,
        self::REWARD_MIC_APERTURE,
        self::REWARD_AVATAR_FARME,
        self::REWARD_ENTER_EFFECT,
        self::REWARD_GIFT,
    ];

    public static $medalList = [
        self::REWARD_MEDAL,
    ];

    public static $couponList = [
        self::REWARD_GAME_COUPON,
    ];

    public static $backgroundList = [
        self::REWARD_BACKGROUND,
    ];

    public static $displayRewardType = [
        self::REWARD_CAR => '入场座驾',
        self::REWARD_BUBBLE => '聊天气泡',
        self::REWARD_MIC_APERTURE => '麦位动效/麦位光圈',
        self::REWARD_AVATAR_FARME => '头像框',
        self::REWARD_ENTER_EFFECT => '入场Banner/进场特效',
        self::REWARD_GIFT => '免费礼物/背包礼物',
        self::REWARD_MEDAL => '勋章',
        self::REWARD_BACKGROUND => '房间背景',
        self::REWARD_EXP => '升级财富level30',
        self::REWARD_PRETTY_UID => '靓号设置权限',
        self::REWARD_GAME_COUPON => '游戏优惠券',
        self::REWARD_VIP => 'vip',
    ];

    public static $commodityTypeMap = [
        self::REWARD_CAR          => 'mounts',
        self::REWARD_BUBBLE       => 'bubble',
        self::REWARD_MIC_APERTURE => 'ring',
        self::REWARD_AVATAR_FARME => 'header',
        self::REWARD_ENTER_EFFECT => 'effect',
        self::REWARD_GIFT         => 'gift',
    ];

    public static $allooCommodityMap = [
        self::REWARD_CAR => ['mounts'],
        self::REWARD_BUBBLE => ['bubble'],
        self::REWARD_MIC_APERTURE => ['ring'],

        self::REWARD_AVATAR_FARME => ['header'],
        self::REWARD_ENTER_EFFECT => ['effect'],
        self::REWARD_GIFT => ['gift'],
    ];

    public static function getAllValidList()
    {
        return self::find([
            'conditions' => 'status = :status:',
            'bind' => [
                'status' => self::STATUS_VALID
            ],
        ])->toArray();
    }

    /**
     * Get Options
     *
     * @return array
     */
    public static function getOptions(): array
    {
        $list = self::getListByWhere([
            ['status', '=', self::STATUS_VALID]
        ], 'id, name', 'id desc');

        if (empty($list)) {
            return $list;
        }

        $map = [];
        foreach ($list as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }

        return $map;
    }
}
