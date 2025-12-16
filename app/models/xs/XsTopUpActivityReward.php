<?php

namespace Imee\Models\Xs;

class XsTopUpActivityReward extends BaseModel
{
    protected static $primaryKey = 'id';

    const AWARD_TYPE_DIAMOND = 1;
    const AWARD_TYPE_COMMODITY = 2;
    const AWARD_TYPE_MEDAL = 3;
    const AWARD_TYPE_ROOM_BACKGROUND = 4;
    const AWARD_TYPE_VIP = 5;
    const AWARD_TYPE_EXP = 6;
    const AWARD_TYPE_PRETTY_ID = 7;
    const AWARD_TYPE_TOP_UP_ACTIVITY_CUSTOMIZE = 8;
    const AWARD_TYPE_ROOM_BG_CARD = 9;
    const AWARD_TYPE_ROOM_SKIN = 10;
    const AWARD_TYPE_EMOTICONS = 11;
    const AWARD_TYPE_CERTIFICATION = 12;
    const AWARD_TYPE_ROOM_TOP_CARD = 13;
    const AWARD_TYPE_TOP_UP_GAME_COUPON = 14;
    const AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING = 15; // 炫彩资源
    const AWARD_TYPE_TOP_UP_ITEM_CARD = 16; // 物品卡（mini卡装扮）
    const AWARD_TYPE_PROP_CARD = 17; // pk道具卡
    const AWARD_TYPE_OPEN_SCREEN_CARD = 18; // 开屏卡
    const AWARD_TYPE_HOMEPAGE_CARD = 19; // 个人主页装扮卡
    const AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD = 20; // 定制表情卡

    const DELETE_STATUS = 0;
    const HAVE_STATUS = 1;

    const DEV_DIAMOND_IMG = '/static/link/23102016234756.png';
    const DIAMOND_IMG = '/static/link/23102314392950.png';

    const REWARD_LEVEL_ONE = 1;
    const REWARD_LEVEL_TWO = 2;

    public static $rewardLevelMap = [
        self::REWARD_LEVEL_ONE => '档位1',
        self::REWARD_LEVEL_TWO => '档位2',
    ];

    public static $awardTypeMap = [
        self::AWARD_TYPE_DIAMOND                   => '钻石',
        self::AWARD_TYPE_COMMODITY                 => '物品',
        self::AWARD_TYPE_VIP                       => 'VIP',
        self::AWARD_TYPE_MEDAL                     => '勋章',
        self::AWARD_TYPE_CERTIFICATION             => '认证图标',
        self::AWARD_TYPE_PRETTY_ID                 => '自选靓号卡',
        self::AWARD_TYPE_ROOM_SKIN                 => '房间皮肤',
        self::AWARD_TYPE_ROOM_BACKGROUND           => '房间背景',
        self::AWARD_TYPE_ROOM_BG_CARD              => '自定义房间背景卡',
        self::AWARD_TYPE_ROOM_TOP_CARD             => '房间置顶卡',
        self::AWARD_TYPE_TOP_UP_ACTIVITY_CUSTOMIZE => '自定义奖励',
        self::AWARD_TYPE_TOP_UP_GAME_COUPON        => '游戏优惠券',
        self::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING   => '炫彩资源',
        self::AWARD_TYPE_TOP_UP_ITEM_CARD          => 'mini卡装扮',
        self::AWARD_TYPE_PROP_CARD                 => 'pk道具卡',
        self::AWARD_TYPE_OPEN_SCREEN_CARD          => '开屏卡',
        self::AWARD_TYPE_HOMEPAGE_CARD             => '个人主页装扮卡',
        self::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD  => '定制表情卡',
    ];

    /**
     * 根据活动ID获取奖励列表
     * @param int $aid
     * @return array
     */
    public static function getListByActivityId(int $aid): array
    {
        return self::getListByWhere([
            ['top_up_activity_id', '=', $aid],
            ['status', '=', self::HAVE_STATUS]
        ], '*', 'reward_level asc');
    }
}