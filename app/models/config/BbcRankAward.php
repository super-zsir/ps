<?php

namespace Imee\Models\Config;

use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xs\XsPropCard;

class BbcRankAward extends BaseModel
{
    protected static $primaryKey = 'id';

    const SCORE_MAX = 2147483647;
    const SCORE_MAX_NEW = 4294967295;

    const RANK_AWARD_TYPE_RANK = 0;         // 按排名返奖
    const RANK_AWARD_TYPE_SCORE = 1;        // 按积分区间返奖
    const RANK_AWARD_TYPE_RANK_SCORE = 2;   // 按排名加积分门槛返奖
    const RANK_AWARD_TYPE_EXTEND_RANK = 3;  // 按成员排名返奖
    const RANK_AWARD_TYPE_TOTAL_WINS = 4;   // 按累胜次数返奖

    public static $rankAwardType = [
        self::RANK_AWARD_TYPE_RANK        => '按排名返奖',
        self::RANK_AWARD_TYPE_SCORE       => '按积分区间返奖',
        self::RANK_AWARD_TYPE_RANK_SCORE  => '按照排名加积分门槛返奖',
        self::RANK_AWARD_TYPE_EXTEND_RANK => '按成员排名返奖',
    ];

    const AWARD_OBJECT_TYPE_MAIN = 0;     // 公会长、家族长、房主
    const AWARD_OBJECT_TYPE_EXTEND = 1;   // 贡献成员
    const AWARD_OBJECT_TYPE_CUSTOMIZATION_ACCEPT = 2;   // 定制礼物送礼人
    const AWARD_OBJECT_TYPE_CUSTOMIZATION_PAY = 3;   // 定制礼物收礼人

    public static $awardObjectTypeCustomizationMap = [
        self::AWARD_OBJECT_TYPE_MAIN                 => '定制礼物所有者',
        self::AWARD_OBJECT_TYPE_CUSTOMIZATION_ACCEPT => '定制礼物送礼人',
        self::AWARD_OBJECT_TYPE_CUSTOMIZATION_PAY    => '定制礼物收礼人',
    ];

    const AWARD_TYPE_DIAMOND = 1;                   // 钻石
    const AWARD_TYPE_COMMODITY = 3;                 // 物品
    const AWARD_TYPE_MEDAL = 4;                     // 勋章
    const AWARD_TYPE_VIP = 5;                       // VIP
    const AWARD_TYPE_ROOM_BACKGROUND = 6;           // 房间背景
    const AWARD_TYPE_PACK = 7;                      // 礼包
    const AWARD_TYPE_ROOM_BG_CARD = 8;              // 自定义房间背景卡
    const AWARD_TYPE_PRETTY_ID_CARD = 9;            // 自定义靓号卡
    const AWARD_TYPE_ROOM_TOP_CARD = 10;            // 房间置顶卡
    const AWARD_TYPE_ROOM_SKIN = 11;                // 房间皮肤
    const AWARD_TYPE_EMOTICONS = 12;                // 表情包
    const AWARD_TYPE_CERTIFICATION_ICON = 13;       // 认证图标
    const AWARD_TYPE_PRIZE_POOL = 14;               // 奖池
    const AWARD_TYPE_GAME_COUPON = 15;              // 游戏优惠券
    const AWARD_TYPE_UNBLOCK_CARD = 16;             // 解封卡
    const AWARD_TYPE_CUSTOMIZATION = 17;            // 自定义奖励
    const AWARD_TYPE_NAME_ID_LIGHTING = 18;         // 炫彩资源
    const AWARD_TYPE_ITEM_CARD = 19;                // 物品卡（mini卡装扮）
    const AWARD_TYPE_PROP_CARD = 20;                // pk道具卡
    const AWARD_TYPE_OPEN_SCREEN_CARD = 21;         // 开屏卡
    const AWARD_TYPE_HOMEPAGE_CARD = 22;            // 个人主页装扮卡
    const AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD = 23; // 定制表情卡


    public static $awardTypeAllMap = [
        self::AWARD_TYPE_DIAMOND                    => '钻石',
        self::AWARD_TYPE_COMMODITY                  => '物品',
        self::AWARD_TYPE_VIP                        => 'VIP',
        self::AWARD_TYPE_MEDAL                      => '勋章',
        self::AWARD_TYPE_CERTIFICATION_ICON         => '认证图标',
        self::AWARD_TYPE_PRETTY_ID_CARD             => '自定义靓号卡',
        self::AWARD_TYPE_ROOM_SKIN                  => '房间皮肤',
        self::AWARD_TYPE_ROOM_BACKGROUND            => '房间背景',
        self::AWARD_TYPE_ROOM_BG_CARD               => '自定义房间背景卡',
        self::AWARD_TYPE_ROOM_TOP_CARD              => '房间置顶卡',
        self::AWARD_TYPE_PACK                       => '礼包',
        self::AWARD_TYPE_PRIZE_POOL                 => '奖池',
        self::AWARD_TYPE_GAME_COUPON                => '游戏优惠券',
        self::AWARD_TYPE_EMOTICONS                  => '表情包',
        self::AWARD_TYPE_UNBLOCK_CARD               => '解封卡',
        self::AWARD_TYPE_CUSTOMIZATION              => '自定义奖励',
        self::AWARD_TYPE_NAME_ID_LIGHTING           => '炫彩资源',
        self::AWARD_TYPE_ITEM_CARD                  => 'mini卡装扮',
        self::AWARD_TYPE_PROP_CARD                  => 'pk道具卡',
        self::AWARD_TYPE_OPEN_SCREEN_CARD           => '开屏卡',
        self::AWARD_TYPE_HOMEPAGE_CARD              => '个人主页装扮卡',
        self::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD   => '定制表情卡',
    ];

    public static $awardTypeMap = [
        self::AWARD_TYPE_DIAMOND                    => '钻石',
        self::AWARD_TYPE_COMMODITY                  => '物品',
        self::AWARD_TYPE_VIP                        => 'VIP',
        self::AWARD_TYPE_MEDAL                      => '勋章',
        self::AWARD_TYPE_CERTIFICATION_ICON         => '认证图标',
        self::AWARD_TYPE_PRETTY_ID_CARD             => '自定义靓号卡',
        self::AWARD_TYPE_ROOM_SKIN                  => '房间皮肤',
        self::AWARD_TYPE_ROOM_BACKGROUND            => '房间背景',
        self::AWARD_TYPE_ROOM_BG_CARD               => '自定义房间背景卡',
        self::AWARD_TYPE_ROOM_TOP_CARD              => '房间置顶卡',
        self::AWARD_TYPE_PACK                       => '礼包',
        self::AWARD_TYPE_GAME_COUPON                => '游戏优惠券',
        self::AWARD_TYPE_NAME_ID_LIGHTING           => '炫彩资源',
        self::AWARD_TYPE_ITEM_CARD                  => 'mini卡装扮',
        self::AWARD_TYPE_PROP_CARD                  => 'pk道具卡',
        self::AWARD_TYPE_OPEN_SCREEN_CARD           => '开屏卡',
        self::AWARD_TYPE_HOMEPAGE_CARD              => '个人主页装扮卡',
        self::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD   => '定制表情卡',
    ];

    public static $exchangeAwardTypeMap = [
        self::AWARD_TYPE_COMMODITY                  => '物品',
        self::AWARD_TYPE_VIP                        => 'VIP',
        self::AWARD_TYPE_MEDAL                      => '勋章',
        self::AWARD_TYPE_CERTIFICATION_ICON         => '认证图标',
        self::AWARD_TYPE_PRETTY_ID_CARD             => '自定义靓号卡',
        self::AWARD_TYPE_ROOM_SKIN                  => '房间皮肤',
        self::AWARD_TYPE_EMOTICONS                  => '表情包',
        self::AWARD_TYPE_ROOM_BACKGROUND            => '房间背景',
        self::AWARD_TYPE_ROOM_BG_CARD               => '自定义房间背景卡',
        self::AWARD_TYPE_ROOM_TOP_CARD              => '房间置顶卡',
        self::AWARD_TYPE_UNBLOCK_CARD               => '解封卡',
        self::AWARD_TYPE_CUSTOMIZATION              => '自定义奖励',
        self::AWARD_TYPE_NAME_ID_LIGHTING           => '炫彩资源',
        self::AWARD_TYPE_ITEM_CARD                  => 'mini卡装扮',
        self::AWARD_TYPE_PROP_CARD                  => 'pk道具卡',
        self::AWARD_TYPE_OPEN_SCREEN_CARD           => '开屏卡',
        self::AWARD_TYPE_HOMEPAGE_CARD              => '个人主页装扮卡',
        self::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD   => '定制表情卡',
    ];

    public static $awardModelMap = [
        self::AWARD_TYPE_COMMODITY          => XsCommodityAdmin::class,
        self::AWARD_TYPE_MEDAL              => XsMedalResource::class,
        self::AWARD_TYPE_CERTIFICATION_ICON => XsCertificationSign::class,
        self::AWARD_TYPE_PRETTY_ID_CARD     => XsCustomizePrettyStyle::class,
        self::AWARD_TYPE_ROOM_SKIN          => XsRoomSkin::class,
        self::AWARD_TYPE_ROOM_BACKGROUND    => XsChatroomBackgroundMall::class,
        self::AWARD_TYPE_ROOM_TOP_CARD      => XsRoomTopCard::class,
        self::AWARD_TYPE_NAME_ID_LIGHTING   => XsNameIdLightingGroup::class,
        self::AWARD_TYPE_ITEM_CARD          => XsItemCard::class,
        self::AWARD_TYPE_PROP_CARD          => XsPropCard::class,
        self::AWARD_TYPE_HOMEPAGE_CARD      => XsItemCard::class,
    ];

    const CAN_TRANSFER_YES = 1;
    const CAN_TRANSFER_NO = 0;

    public static $canTransferBgcMap = [
        self::CAN_TRANSFER_YES => '是',
        self::CAN_TRANSFER_NO  => '否',
    ];

    public static $canTransferPrettuMap = [
        self::CAN_TRANSFER_YES => '可赠送',
        self::CAN_TRANSFER_NO  => '不可赠送',
    ];

    const GIVE_TYPE_AUTO_EFFECT = 1;
    const GIVE_TYPE_MANUAL_EFFECT_TRANSFERABLE = 2;
    const GIVE_TYPE_MANUAL_EFFECT_NON_TRANSFERABLE = 3;

    public static $giveTypeMap = [
        self::GIVE_TYPE_AUTO_EFFECT                    => '直接生效',
        self::GIVE_TYPE_MANUAL_EFFECT_TRANSFERABLE     => '用户手动生效可转赠',
        self::GIVE_TYPE_MANUAL_EFFECT_NON_TRANSFERABLE => '用户手动生效不可转赠',
    ];

    const STOCK_TYPE_NO_LIMIT = 0; // 不限
    const STOCK_TYPE_DAYS_LIMIT = 1; // 每日
    const STOCK_TYPE_TOTAL_LIMIT = 2; // 总共

    public static $stockTypeMap = [
        self::STOCK_TYPE_NO_LIMIT    => '不限',
        self::STOCK_TYPE_DAYS_LIMIT  => '每日',
        self::STOCK_TYPE_TOTAL_LIMIT => '总共',
    ];

    public static $roomBgCardTypeMap = [
        0 => '静态',
        1 => '动态',
    ];

    public static $openScreenCardTypeMap = [
        1 => '静态',
        2 => '动态',
    ];

    public static function getInfoByActId($actId, $fields = ['id'], $column = 'id', $searchKey = 'id'): array
    {
        if (!in_array('id', $fields)) {
            array_unshift($fields, 'id');
        }

        $ids = self::getListByWhere([
            ['act_id', '=', $actId]
        ], implode(',', $fields));

        return $ids ? array_column($ids, $column) : [];
    }

    public static function getRankDiamondProportionSum(int $buttonListId): int
    {
        // 查询所有符合条件的数据
        $rankList = self::getListByWhere([
            ['button_list_id', '=', $buttonListId],
            ['award_type', '=', self::AWARD_TYPE_PRIZE_POOL],
            ['rank_award_type', 'IN', [self::RANK_AWARD_TYPE_RANK, self::RANK_AWARD_TYPE_RANK_SCORE]]
        ]);

        $sum = 0;
        $rankScoreMaxArr = [];

        foreach ($rankList as $rank) {
            $extendMax = max($rank['extend_rank_max'], $rank['extend_rank_min']);
            $extendMin = min($rank['extend_rank_max'], $rank['extend_rank_min']);
            $num = $extendMax - $extendMin + 1;

            if ($rank['rank_award_type'] == self::RANK_AWARD_TYPE_RANK) {
                $sum += $rank['diamond_proportion'] * $num;
            } elseif ($rank['rank_award_type'] == self::RANK_AWARD_TYPE_RANK_SCORE) {
                // 只保留相同 rank 中 diamond_proportion 最大的
                $rankKey = $rank['rank'];
                if (!isset($rankScoreMaxArr[$rankKey]) || $rank['diamond_proportion'] > $rankScoreMaxArr[$rankKey]['diamond_proportion']) {
                    $rankScoreMaxArr[$rankKey] = [
                        'extend_rank_max'    => $extendMax,
                        'extend_rank_min'    => $extendMin,
                        'diamond_proportion' => $rank['diamond_proportion']
                    ];
                }
            }
        }

        // 计算 rank_award_type 为 RANK_SCORE 的部分
        foreach ($rankScoreMaxArr as $item) {
            $num = $item['extend_rank_max'] - $item['extend_rank_min'] + 1;
            $sum += $item['diamond_proportion'] * $num;
        }

        return $sum;
    }

    public static function addRows($tmpRows)
    {
        $rec = BbcRankAward::useMaster();
        foreach ($tmpRows as $k => $v) {
            $rec->{$k} = $v;
        }
        $rec->save();
        return true;
    }

    /**
     * 判断是否需要成员名次上下限
     * @param int $awardObjectType
     * @return bool
     */
    public static function isExtendRank(int $awardObjectType): bool
    {
        return in_array($awardObjectType, [BbcRankAward::AWARD_OBJECT_TYPE_EXTEND, BbcRankAward::AWARD_OBJECT_TYPE_CUSTOMIZATION_ACCEPT, BbcRankAward::AWARD_OBJECT_TYPE_CUSTOMIZATION_PAY]);
    }

    /**
     * 获取相同名次、发放对象、发放条件下的奖励数据
     * @param $id
     * @param $buttonListId
     * @param $rank
     * @param $awardObjectType
     * @param $rankAwardType
     * @return array
     */
    public static function getListByRankAndObjectAndType($id, $buttonListId, $rank, $awardObjectType, $rankAwardType): array
    {
        return self::getListByWhere([
            ['id', '<>', $id],
            ['button_list_id', '=', $buttonListId],
            ['rank', '=', $rank],
            ['award_object_type', '=', $awardObjectType],
            ['rank_award_type', '=', $rankAwardType],
        ]);
    }
}