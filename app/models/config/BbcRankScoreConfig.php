<?php

namespace Imee\Models\Config;

class BbcRankScoreConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const RECHARGE_TYPE_ALL = 0; //全选
    const RECHARGE_TYPE_APPLE = 1;
    const RECHARGE_TYPE_GOOGLE = 2;
    const RECHARGE_TYPE_AGENT_MONEY = 3; //通过币商充值
    const RECHARGE_TYPE_THIRD_PARTY = 4; //第三方
    const RECHARGE_TYPE_BANK_NOTE = 5; //现金兑换钻石
    const RECHARGE_TYPE_CHARM = 6; //魅力值兑换钻石
    const RECHARGE_TYPE_HUAWEI_IAP = 7; // 华为充值
    const RECHARGE_TYPE_SALARY_PREPAY = 8; //公会提成薪资预支

    public static $rechargeChannelsMap = [
        self::RECHARGE_TYPE_APPLE         => 'apple',
        self::RECHARGE_TYPE_GOOGLE        => 'google',
        self::RECHARGE_TYPE_AGENT_MONEY   => '通过币商充值',
        self::RECHARGE_TYPE_THIRD_PARTY   => '第三方',
        self::RECHARGE_TYPE_BANK_NOTE     => '现金兑换钻石',
        self::RECHARGE_TYPE_CHARM         => '魅力值兑换钻石',
        self::RECHARGE_TYPE_HUAWEI_IAP    => '华为充值',
        self::RECHARGE_TYPE_SALARY_PREPAY => '公会提成薪资预支',
    ];

    const TYPE_PAY_GIFT = 1;  //送付费礼物
    const TYPE_PAY_GIFT_ID = 2;  //送指定付费礼物
    const TYPE_PAY_GIFT_NUM = 3;  //送指定礼物数量
    const TYPE_ACCEPT_GIFT = 4;  //收付费礼物
    const TYPE_ACCEPT_GIFT_ID = 5;  //收指定付费礼物
    const TYPE_ACCEPT_GIFT_NUM = 6;  //收指定礼物数量
    const TYPE_INTERACT = 7;  //普通麦位麦时
    const TYPE_PK_WIN = 8;  //pk胜场
    const TYPE_PK_FAIL = 9;  //pk败场
    const TYPE_PK_END = 10; //pk完成次数
    const TYPE_PK_ACCEPT_GIFT = 11; //pk内流水
    const TYPE_PAY_GIFT_DOUBLE_HIT = 12; //送付费礼物连击（超过连击数按照连击数计算）
    const TYPE_PAY_GIFT_ID_DOUBLE_HIT = 13; //送指定付费礼物连击
    const TYPE_ACCEPT_GIFT_DOUBLE_HIT = 14; //收付费礼物连击
    const TYPE_ACCEPT_GIFT_ID_DOUBLE_HIT = 15; //收指定付费礼物连击
    const TYPE_PAY_GIFT_RECEPTION = 16; //送付费礼物给接待位
    const TYPE_PAY_GIFT_ID_RECEPTION = 17; //送指定付费礼物给接待位
    const TYPE_ACCEPT_GIFT_RECEPTION = 18; //接待位收付费礼物
    const TYPE_ACCEPT_GIFT_ID_RECEPTION = 19; //接待位收指定付费礼物
    const TYPE_LUCKY_GIFT_WIN = 20; // 幸运礼物赢取金额
    const TYPE_SLOT_WIN = 21; // slot赢取金额
    const TYPE_GREEDY_WIN = 22; // greedy赢取金额
    const TYPE_PK_PAY_GIFT = 23; // pk中送礼
    const TYPE_TOP_UP_DIAMOND = 24; // 充值钻石
    const TYPE_SICBO_WIN = 25; // sicbo赢取金额
    const TYPE_LUCKY_FRUIT_WIN = 26; // 水果机赢取金额
    const TYPE_HORSE_RACE_WIN = 27; // 赛马赢取金额
    const TYPE_TAROT_WIN = 28; // TAROT赢取金额
    const TYPE_TEEN_PATTI_WIN = 29; // Tenn Patti 赢取金额
    const TYPE_ROCKET_WIN = 30; // Rocket 赢取金额
    const TYPE_DRAGON_TIGER_WIN = 31; // Dragon Tiger 赢取金额
    const TYPE_GREEDY_BOX_WIN = 32; // Greedy Box 赢取金额
    const TYPE_GREEDY_SLOT_WIN = 33; // Greedy Slot 赢取金额
    const TYPE_FISHING_WIN = 34; // Fishing 赢取金额

    const TYPE_GREEDY_BET = 35; // Greedy 下注钻石数
    const TYPE_GREEDY_BRUTAL_BET = 36; // Greedy Brutal 下注钻石数

    public static $types = [
        self::TYPE_PAY_GIFT                  => '送付费礼物',
        self::TYPE_PAY_GIFT_ID               => '送指定付费礼物',
        self::TYPE_PAY_GIFT_NUM              => '送指定礼物数量',
        self::TYPE_ACCEPT_GIFT               => '收付费礼物',
        self::TYPE_ACCEPT_GIFT_ID            => '收指定付费礼物',
        self::TYPE_ACCEPT_GIFT_NUM           => '收指定礼物数量',
        self::TYPE_INTERACT                  => '普通麦位麦时',
        self::TYPE_PK_WIN                    => 'pk胜场',
        self::TYPE_PK_FAIL                   => 'pk败场',
        self::TYPE_PK_END                    => 'pk完成次数',
        self::TYPE_PK_ACCEPT_GIFT            => 'pk内流水',
        self::TYPE_PAY_GIFT_DOUBLE_HIT       => '送付费礼物连击',
        self::TYPE_PAY_GIFT_ID_DOUBLE_HIT    => '送指定付费礼物连击',
        self::TYPE_ACCEPT_GIFT_DOUBLE_HIT    => '收付费礼物连击',
        self::TYPE_ACCEPT_GIFT_ID_DOUBLE_HIT => '收指定付费礼物连击',
        self::TYPE_PAY_GIFT_RECEPTION        => '送付费礼物给接待位',
        self::TYPE_PAY_GIFT_ID_RECEPTION     => '送指定付费礼物给接待位',
        self::TYPE_ACCEPT_GIFT_RECEPTION     => '接待位收付费礼物',
        self::TYPE_ACCEPT_GIFT_ID_RECEPTION  => '接待位收指定付费礼物',
        self::TYPE_LUCKY_GIFT_WIN            => '幸运礼物赢取金额',
        self::TYPE_SLOT_WIN                  => 'slot赢取金额',
        self::TYPE_GREEDY_WIN                => 'greedy赢取金额',
        self::TYPE_PK_PAY_GIFT               => 'pk中送礼',
        self::TYPE_TOP_UP_DIAMOND            => '充值钻石',
        self::TYPE_SICBO_WIN                 => 'SICBO赢取金额',
        self::TYPE_LUCKY_FRUIT_WIN           => 'Lucky Fruit赢取金额',
        self::TYPE_HORSE_RACE_WIN            => 'Horse Race赢取金额',
        self::TYPE_TAROT_WIN                 => 'TAROT赢取金额',
        self::TYPE_TEEN_PATTI_WIN            => 'Teen Patti 赢取金额',
        self::TYPE_ROCKET_WIN                => 'Rocket 赢取金额',
        self::TYPE_DRAGON_TIGER_WIN          => 'Dragon Tiger 赢取金额',
        self::TYPE_GREEDY_BOX_WIN            => 'Greedy Box 赢取金额',
        self::TYPE_GREEDY_SLOT_WIN           => 'Greedy Slot 赢取金额',
        self::TYPE_FISHING_WIN               => 'Fishing 赢取金额',
        self::TYPE_GREEDY_BET                => 'Greedy 下注钻石数',
        self::TYPE_GREEDY_BRUTAL_BET         => 'Greedy Brutal 下注钻石数',
    ];

    const CHANNEL_APPLE = 1;
    const CHANNEL_GOOGLE = 2;
    const COIN_MERCHANT_CHANNEL = 3;
    const THIRD_PARTY_CHANNEL = 4;
    const CASH_CHANNEL = 5;
    const CHARM_VALUE_CHANNEL = 6;
    const CHARM_VALUE_HUAWEI_IAP = 7;
    const CHARM_VALUE_SALARY_PREPAY = 8;

    public static $channelMap = [
        self::CHANNEL_APPLE             => 'apple',
        self::CHANNEL_GOOGLE            => 'google',
        self::COIN_MERCHANT_CHANNEL     => '通过币商充值',
        self::THIRD_PARTY_CHANNEL       => '第三方',
        self::CASH_CHANNEL              => '现金兑换钻石',
        self::CHARM_VALUE_CHANNEL       => '魅力值兑换钻石',
        self::CHARM_VALUE_HUAWEI_IAP    => '华为充值',
        self::CHARM_VALUE_SALARY_PREPAY => '公会提成薪资预支',
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

    public static function addRows($tmpRows)
    {
        $rec = BbcRankScoreConfig::useMaster();
        foreach ($tmpRows as $k => $v) {
            $rec->{$k} = $v;
        }
        $rec->save();
        return true;
    }
}