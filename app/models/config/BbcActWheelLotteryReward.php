<?php

namespace Imee\Models\Config;

class BbcActWheelLotteryReward extends BaseModel
{
    protected static $primaryKey = 'id';

    const REWARD_TYPE_START = 0; // 谢谢惠顾
    const REWARD_TYPE_COMMODITY = 1; // 物品
    const REWARD_TYPE_GAME_COUPON = 2; // 游戏优惠券
    const REWARD_TYPE_ROOM_BACKGROUND = 3; // 房间背景
    const REWARD_TYPE_ROOM_BG_CARD = 4; // 房间背景定制卡
    const REWARD_TYPE_MEDAL = 5; // 勋章
    const REWARD_TYPE_PRETTY_ID = 6; // 靓号
    const REWARD_TYPE_PRETTY_ID_CARD = 7; // 靓号自定义卡
    const REWARD_TYPE_VIP = 8; // VIP
    const REWARD_TYPE_WEALTH_LV = 9; // 财富等级
    const REWARD_TYPE_DIAMOND = 10; // 钻石
    const REWARD_TYPE_COIN = 11; // 游戏金币
    const REWARD_TYPE_CHIP = 12; // 游戏筹码
    const REWARD_TYPE_COMMODITY_SPU = 13; // xs_user_commodity_spu 表中的物品
    const REWARD_TYPE_GIFT_BAG = 14; // 礼包
    const REWARD_TYPE_ROOM_SKIN = 16;  // 房间皮肤
    const REWARD_TYPE_ACTIVITY_DIAMOND = 17;  // 活动发放钻石
    const REWARD_TYPE_ROOM_TOP_CARD = 18;  // 房间置顶卡
    const REWARD_TYPE_EMOTICONS = 19; // 表情包
    const REWARD_TYPE_PROP_CARD = 20;  // pk道具卡
    const REWARD_TYPE_SALARY = 21;  // 自动发薪
    const REWARD_TYPE_CERTIFICATION_ICON = 22;  // 认证图标
    const REWARD_NAME_ID_LIGHTING = 26;  // 炫彩资源
    const REWARD_TYPE_MINI_CARD_DRESS = 27;  // mini卡装扮
    const REWARD_TYPE_OPEN_SCREEN_CARD = 28;  // 开屏卡
    const REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD = 30;  // 定制表情卡
    const REWARD_TYPE_HOMEPAGE_CARD = 31;  // 个人主页装扮卡
    const REWARD_TYPE_END = 32;  // END用于判断枚举值的边界

    public static $rewardTypeMap = [
        self::REWARD_TYPE_ACTIVITY_DIAMOND         => '钻石',
        self::REWARD_TYPE_COMMODITY                => '物品',
        self::REWARD_TYPE_VIP                      => 'VIP',
        self::REWARD_TYPE_MEDAL                    => '勋章',
        self::REWARD_TYPE_CERTIFICATION_ICON       => '认证图标',
        self::REWARD_TYPE_PRETTY_ID_CARD           => '自选靓号卡',
        self::REWARD_TYPE_EMOTICONS                => '表情包',
        self::REWARD_TYPE_ROOM_SKIN                => '房间皮肤',
        self::REWARD_TYPE_ROOM_BACKGROUND          => '房间背景',
        self::REWARD_TYPE_ROOM_BG_CARD             => '自定义房间背景卡',
        self::REWARD_TYPE_ROOM_TOP_CARD            => '房间置顶卡',
        self::REWARD_TYPE_GIFT_BAG                 => '礼包',
        self::REWARD_TYPE_GAME_COUPON              => '游戏优惠券',
        self::REWARD_NAME_ID_LIGHTING              => '炫彩资源',
        self::REWARD_TYPE_MINI_CARD_DRESS          => 'mini卡装扮',
        self::REWARD_TYPE_START                    => '谢谢惠顾',
        self::REWARD_TYPE_OPEN_SCREEN_CARD         => '开屏卡',
        self::REWARD_TYPE_PROP_CARD                => 'pk道具卡',
        self::REWARD_TYPE_HOMEPAGE_CARD            => '个人主页装扮卡',
        self::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD => '定制表情卡',
    ];

    public static $rewardTypeAllMap = [
        self::REWARD_TYPE_START                    => '谢谢惠顾',
        self::REWARD_TYPE_COMMODITY                => '物品',
        self::REWARD_TYPE_GAME_COUPON              => '游戏优惠券',
        self::REWARD_TYPE_ROOM_BACKGROUND          => '房间背景',
        self::REWARD_TYPE_ROOM_BG_CARD             => '房间背景定制卡',
        self::REWARD_TYPE_MEDAL                    => '勋章',
        self::REWARD_TYPE_PRETTY_ID                => '靓号',
        self::REWARD_TYPE_PRETTY_ID_CARD           => '靓号自定义卡',
        self::REWARD_TYPE_VIP                      => 'VIP',
        self::REWARD_TYPE_WEALTH_LV                => '财富等级',
        self::REWARD_TYPE_DIAMOND                  => '钻石',
        self::REWARD_TYPE_COIN                     => '游戏金币',
        self::REWARD_TYPE_CHIP                     => '游戏筹码',
        self::REWARD_TYPE_COMMODITY_SPU            => 'xs_user_commodity_spu 表中的物品',
        self::REWARD_TYPE_GIFT_BAG                 => '礼包',
        self::REWARD_TYPE_ROOM_SKIN                => '房间皮肤',
        self::REWARD_TYPE_ACTIVITY_DIAMOND         => '活动发放钻石',
        self::REWARD_TYPE_ROOM_TOP_CARD            => '房间置顶卡',
        self::REWARD_TYPE_EMOTICONS                => '表情包',
        self::REWARD_TYPE_PROP_CARD                => 'pk道具卡',
        self::REWARD_TYPE_SALARY                   => '自动发薪',
        self::REWARD_TYPE_CERTIFICATION_ICON       => '认证图标',
        self::REWARD_NAME_ID_LIGHTING              => '炫彩资源',
        self::REWARD_TYPE_MINI_CARD_DRESS          => 'mini卡装扮',
        self::REWARD_TYPE_OPEN_SCREEN_CARD         => '开屏卡',
        self::REWARD_TYPE_HOMEPAGE_CARD            => '个人主页装扮卡',
        self::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD => '定制表情卡',
    ];
    
    const AWARD_EXTEND_TYPE_PROP_CARD_DEFAULT = 0;
    const AWARD_EXTEND_TYPE_PROP_CARD_RELIEVE_CARD = 1;
    const AWARD_EXTEND_TYPE_PROP_CARD_CAN_SEND_VIP_CARD = 2;
    const AWARD_EXTEND_TYPE_PROP_CARD_VIP_CARD = 3;
    const AWARD_EXTEND_TYPE_PROP_CARD_RELIEVE_FORBIDDEN_CARD = 4;
    const AWARD_EXTEND_TYPE_PROP_CARD_PK_BONUS = 5;
    const AWARD_EXTEND_TYPE_PROP_CARD_PK_MAGNETIC = 6;
    const AWARD_EXTEND_TYPE_PROP_CARD_INTIMATE_RELATION_ICON = 7;
    const AWARD_EXTEND_TYPE_PROP_CARD_RELATION_HEAD_FRAME = 8;

    public static $awardExtendTypeMap = [
        self::AWARD_EXTEND_TYPE_PROP_CARD_DEFAULT => 'vip卡',
        self::AWARD_EXTEND_TYPE_PROP_CARD_RELIEVE_CARD => '解除卡',
        self::AWARD_EXTEND_TYPE_PROP_CARD_CAN_SEND_VIP_CARD => 'vip卡可赠送',
        self::AWARD_EXTEND_TYPE_PROP_CARD_VIP_CARD => 'vip卡不可转赠',
        self::AWARD_EXTEND_TYPE_PROP_CARD_RELIEVE_FORBIDDEN_CARD => '解封卡',
        self::AWARD_EXTEND_TYPE_PROP_CARD_PK_BONUS => 'PK加成卡',
        self::AWARD_EXTEND_TYPE_PROP_CARD_PK_MAGNETIC => 'PK磁力卡',
        self::AWARD_EXTEND_TYPE_PROP_CARD_INTIMATE_RELATION_ICON => '亲密关系增值道具-亲密关系ICON',
        self::AWARD_EXTEND_TYPE_PROP_CARD_RELATION_HEAD_FRAME => '关系头像框',
    ];

    const STOCK_TYPE_NO_LIMIT = 1;  // 抽出数量无上限
    const STOCK_TYPE_LIMIT = 2; // 抽出数量有上限

    public static $stockTypeMap = [
        self::STOCK_TYPE_NO_LIMIT => '不限',
        self::STOCK_TYPE_LIMIT    => '有限',
    ];

    const GIVE_TYPE_NON_TRANSFERABLE = 0;
    const GIVE_TYPE_AUTO_EFFECT = 1;
    const GIVE_TYPE_MANUAL_EFFECT_TRANSFERABLE = 2;
    const GIVE_TYPE_MANUAL_EFFECT_NON_TRANSFERABLE = 3;
    const GIVE_TYPE_TRANSFERABLE = 4;

    public static $giveTypeVipMap = [
        self::GIVE_TYPE_AUTO_EFFECT                    => '直接生效',
        self::GIVE_TYPE_MANUAL_EFFECT_TRANSFERABLE     => '用户手动生效可转赠',
        self::GIVE_TYPE_MANUAL_EFFECT_NON_TRANSFERABLE => '用户手动生效不可转赠',
    ];


    public static $giveTypeBgcCardMap = [
        self::GIVE_TYPE_TRANSFERABLE     => '是',
        self::GIVE_TYPE_NON_TRANSFERABLE => '否',
    ];


    public static $giveTypePrettuMap = [
        self::GIVE_TYPE_NON_TRANSFERABLE => '不可赠送',
        self::GIVE_TYPE_TRANSFERABLE     => '可转赠',
    ];
}