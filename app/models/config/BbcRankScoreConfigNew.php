<?php

namespace Imee\Models\Config;

class BbcRankScoreConfigNew extends BaseModel
{
    protected static $primaryKey = 'id';

    const SOURCE_TYPE_GIFT = 1;
    const SOURCE_TYPE_TOP_UP = 2;
    const SOURCE_TYPE_GAMES = 3;
    const SOURCE_TYPE_ACTIVE = 4;
    const SOURCE_TYPE_WHEEL_LOTTERY = 5;

    public static $sourceTypeMap = [
        self::SOURCE_TYPE_GIFT          => '收送礼',
        self::SOURCE_TYPE_TOP_UP        => '充值',
        self::SOURCE_TYPE_GAMES         => '游戏',
        self::SOURCE_TYPE_ACTIVE        => '活跃',
        self::SOURCE_TYPE_WHEEL_LOTTERY => '幸运玩法模版',
    ];

    const ROOM_SCOPE_CHAT = 1;
    const ROOM_SCOPE_LIVE = 2;

    public static $roomScopeMap = [
        self::ROOM_SCOPE_CHAT => '语音房',
        self::ROOM_SCOPE_LIVE => '视频房',
    ];

    public static $rankObjectAndsourceTypeMap = [
        BbcTemplateConfig::TYPE_TASK . BbcRankButtonTag::RANK_OBJECT_PERSONAL => [
            self::SOURCE_TYPE_GIFT, self::SOURCE_TYPE_TOP_UP, self::SOURCE_TYPE_GAMES, self::SOURCE_TYPE_WHEEL_LOTTERY,
        ],
        BbcTemplateConfig::TYPE_TASK . BbcRankButtonTag::RANK_OBJECT_CP => [
            self::SOURCE_TYPE_GIFT,
        ],
        BbcTemplateConfig::TYPE_TASK . BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS => [
            self::SOURCE_TYPE_GIFT, self::SOURCE_TYPE_TOP_UP, self::SOURCE_TYPE_GAMES, self::SOURCE_TYPE_WHEEL_LOTTERY,
        ],
        BbcTemplateConfig::TYPE_TASK . BbcRankButtonTag::RANK_OBJECT_BROKER => [
            self::SOURCE_TYPE_GIFT
        ],
        BbcTemplateConfig::TYPE_TASK . BbcRankButtonTag::RANK_OBJECT_ROOM => [
            self::SOURCE_TYPE_GIFT
        ],
        BbcTemplateConfig::TYPE_EXCHANGE . BbcRankButtonTag::RANK_OBJECT_PERSONAL => [
            self::SOURCE_TYPE_GIFT, self::SOURCE_TYPE_TOP_UP, self::SOURCE_TYPE_GAMES,
        ],
        BbcTemplateConfig::TYPE_EXCHANGE . BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS => [
            self::SOURCE_TYPE_GIFT, self::SOURCE_TYPE_TOP_UP, self::SOURCE_TYPE_GAMES,
        ],
    ];

    const SCORE_TYPE_UNKNOWN = 0; //未知类型
    const SCORE_TYPE_PAY_GIFT = 1; //送付费礼物
    const SCORE_TYPE_PAY_GIFT_ID = 2; //送指定付费礼物
    const SCORE_TYPE_PAY_GIFT_NUM = 3; //送指定礼物数量
    const SCORE_TYPE_ACCEPT_GIFT = 4; //收付费礼物
    const SCORE_TYPE_ACCEPT_GIFT_ID = 5; //收指定付费礼物
    const SCORE_TYPE_ACCEPT_GIFT_NUM = 6; //收指定礼物数量
    const SCORE_TYPE_TOP_UP_DIAMOND = 7; // 充值钻石
    const SCORE_TYPE_LUCKY_GIFT_WIN = 8; // 幸运礼物赢取钻石
    const SCORE_TYPE_GAME_WIN = 9; // 游戏赢取钻石
    const SCORE_TYPE_PK_WIN = 10; // pk胜场次数
    const SCORE_TYPE_PK_END = 11; // pk完成次数
    const SCORE_TYPE_PK_ACCEPT_GIFT = 12; // pk时收礼流水
    const SCORE_TYPE_PK_PAY_GIFT = 13; // pk时送礼流水
    const SCORE_TYPE_SIGN_IN = 14; // 签到
    const SCORE_TYPE_ROOM_STAY_TIME = 15; // 房间停留时长（单位：分钟）
    const SCORE_TYPE_ROOM_COMMENT_NUM = 16; // 房间评论条数
    const SCORE_TYPE_WHEEL_NUM = 17; // 抽奖次数
    const SCORE_TYPE_GREEDY_BET = 18; // 游戏下注钻石

    public static $scoreTypeMap = [
        self::SCORE_TYPE_PAY_GIFT         => '送礼钻石数',
        self::SCORE_TYPE_PAY_GIFT_ID      => '送指定礼物钻石数',
        self::SCORE_TYPE_PAY_GIFT_NUM     => '送指定礼物数量',
        self::SCORE_TYPE_ACCEPT_GIFT      => '收礼钻石数',
        self::SCORE_TYPE_ACCEPT_GIFT_ID   => '收指定礼物钻石数',
        self::SCORE_TYPE_ACCEPT_GIFT_NUM  => '收指定礼物数量',
        self::SCORE_TYPE_TOP_UP_DIAMOND   => '充值钻石',
        self::SCORE_TYPE_LUCKY_GIFT_WIN   => '幸运礼物赢取钻石',
        self::SCORE_TYPE_GAME_WIN         => '游戏赢取钻石',
        self::SCORE_TYPE_PK_WIN           => 'pk胜场次数',
        self::SCORE_TYPE_PK_END           => 'pk完成次数',
        self::SCORE_TYPE_PK_ACCEPT_GIFT   => 'pk时收礼流水',
        self::SCORE_TYPE_PK_PAY_GIFT      => 'pk时送礼流水',
        self::SCORE_TYPE_SIGN_IN          => '签到',
        self::SCORE_TYPE_ROOM_STAY_TIME   => '房间停留时长（单位：分钟）',
        self::SCORE_TYPE_ROOM_COMMENT_NUM => '房间评论条数',
        self::SCORE_TYPE_WHEEL_NUM        => '抽奖次数',
        self::SCORE_TYPE_GREEDY_BET       => '游戏下注钻石',
    ];
    
    public static $scoreUnitMap = [
        self::SCORE_TYPE_ACCEPT_GIFT     => '每1钻',
        self::SCORE_TYPE_ACCEPT_GIFT_ID  => '每1钻',
        self::SCORE_TYPE_ACCEPT_GIFT_NUM => '每1个',
        self::SCORE_TYPE_PAY_GIFT        => '每1钻',
        self::SCORE_TYPE_PAY_GIFT_ID     => '每1钻',
        self::SCORE_TYPE_PAY_GIFT_NUM    => '每1个',
        self::SCORE_TYPE_TOP_UP_DIAMOND  => '每充1钻',
        self::SCORE_TYPE_LUCKY_GIFT_WIN  => '每赢1钻',
        self::SCORE_TYPE_GAME_WIN        => '每赢1钻',
        self::SCORE_TYPE_PK_WIN          => '每1场',
        self::SCORE_TYPE_PK_END          => '每1场',
        self::SCORE_TYPE_WHEEL_NUM       => '每1次',
        self::SCORE_TYPE_GREEDY_BET      => '每1钻',
        self::SCORE_TYPE_PK_PAY_GIFT     => '每1钻',
    ];

    public static $scoreScopeAndScoreTypeMap = [
        self::SCORE_SCOPE_CHAT                          => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
            self::SCORE_TYPE_PK_WIN, self::SCORE_TYPE_PK_END,
        ],
        self::SCORE_SCOPE_LIVE                          => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
            self::SCORE_TYPE_PK_WIN, self::SCORE_TYPE_PK_END,
        ],
        self::SCORE_SCOPE_CHAT . self::SCORE_SCOPE_LIVE                                       => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
            self::SCORE_TYPE_LUCKY_GIFT_WIN,
        ],
        self::SCORE_SCOPE_LIVE . self::SCORE_SCOPE_CHAT                                       => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
            self::SCORE_TYPE_LUCKY_GIFT_WIN,
        ],
        self::SCORE_SCOPE_PRIVATE_SEND_GIFT                                                   => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_PRIVATE_SEND_GIFT . self::SCORE_SCOPE_CHAT                          => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_CHAT . self::SCORE_SCOPE_PRIVATE_SEND_GIFT                          => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_PRIVATE_SEND_GIFT . self::SCORE_SCOPE_LIVE                          => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_LIVE . self::SCORE_SCOPE_PRIVATE_SEND_GIFT                          => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_PRIVATE_SEND_GIFT . self::SCORE_SCOPE_CHAT . self::SCORE_SCOPE_LIVE => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_PRIVATE_SEND_GIFT . self::SCORE_SCOPE_LIVE . self::SCORE_SCOPE_CHAT => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_CHAT . self::SCORE_SCOPE_LIVE . self::SCORE_SCOPE_PRIVATE_SEND_GIFT => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_CHAT . self::SCORE_SCOPE_PRIVATE_SEND_GIFT . self::SCORE_SCOPE_LIVE => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_LIVE . self::SCORE_SCOPE_CHAT . self::SCORE_SCOPE_PRIVATE_SEND_GIFT => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_LIVE . self::SCORE_SCOPE_PRIVATE_SEND_GIFT . self::SCORE_SCOPE_CHAT => [
            self::SCORE_TYPE_ACCEPT_GIFT, self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
            self::SCORE_TYPE_PAY_GIFT, self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        ],
        self::SCORE_SCOPE_TOP_UP_APPLE                                                        => [
            self::SCORE_TYPE_TOP_UP_DIAMOND
        ],
        self::SCORE_SCOPE_GAME_GREEDY                                                         => [
            self::SCORE_TYPE_GAME_WIN, self::SCORE_TYPE_GREEDY_BET,
        ],
        self::SOURCE_TYPE_ACTIVE                                                              => [
            self::SCORE_TYPE_SIGN_IN, self::SCORE_TYPE_ROOM_STAY_TIME, self::SCORE_TYPE_ROOM_COMMENT_NUM,
        ],
        self::SOURCE_TYPE_WHEEL_LOTTERY                                                       => [
            self::SCORE_TYPE_WHEEL_NUM,
        ],
    ];

    const SCORE_SCOPE_UNKNOWN = 0; //未知类型
    const SCORE_SCOPE_CHAT = 1; // 语音房
    const SCORE_SCOPE_LIVE = 2; // 视频房
    const SCORE_SCOPE_TOP_UP_APPLE = 3; //苹果充值
    const SCORE_SCOPE_TOP_UP_GOOGLE = 4; //google充值
    const SCORE_SCOPE_TOP_UP_AGENT_MONEY = 5; //通过币商充值
    const SCORE_SCOPE_TOP_UP_THIRD_PARTY = 6; //第三方充值
    const SCORE_SCOPE_TOP_UP_BANK_NOTE = 7; //现金兑换钻石
    const SCORE_SCOPE_TOP_UP_CHARM = 8; //魅力值兑换钻石
    const SCORE_SCOPE_GAME_GREEDY = 9;
    const SCORE_SCOPE_GAME_SLOT = 10;
    const SCORE_SCOPE_GAME_SICBO = 11;
    const SCORE_SCOPE_GAME_LUCKY_FRUIT = 12;
    const SCORE_SCOPE_GAME_HORSE_RACE = 13;
    const SCORE_SCOPE_GAME_TAROT = 14;
    const SCORE_SCOPE_GAME_TEEN_PATTI = 15;
    const SCORE_SCOPE_GAME_ROCKET = 16;
    const SCORE_SCOPE_GAME_DRAGON_TIGER = 17;
    const SCORE_SCOPE_GAME_GREEDY_BOX = 18;
    const SCORE_SCOPE_GAME_GREEDY_SLOT = 19;
    const SCORE_SCOPE_HUAWEI_IAP = 20;
    const SCORE_SCOPE_SALARY_PREPAY = 21;
    const SCORE_SCOPE_GAME_FISHING = 22;
    const SCORE_SCOPE_PRIVATE_SEND_GIFT = 23;
    const SCORE_SCOPE_GREEDY_BRUTAL_GIFT = 26;



    public static $scoreScopeMap = [
        self::SCORE_SCOPE_CHAT               => '语音房',
        self::SCORE_SCOPE_LIVE               => '视频房',
        self::SCORE_SCOPE_TOP_UP_APPLE       => '苹果充值',
        self::SCORE_SCOPE_TOP_UP_GOOGLE      => 'google充值',
        self::SCORE_SCOPE_TOP_UP_AGENT_MONEY => '通过币商充值',
        self::SCORE_SCOPE_TOP_UP_THIRD_PARTY => '第三方充值',
        self::SCORE_SCOPE_TOP_UP_BANK_NOTE   => '现金兑换钻石',
        self::SCORE_SCOPE_TOP_UP_CHARM       => '魅力值兑换钻石',
        self::SCORE_SCOPE_GAME_GREEDY        => 'greedy',
        self::SCORE_SCOPE_GAME_SLOT          => 'slot',
        self::SCORE_SCOPE_GAME_SICBO         => 'sicbo',
        self::SCORE_SCOPE_GAME_LUCKY_FRUIT   => 'lucky fruit',
        self::SCORE_SCOPE_GAME_HORSE_RACE    => 'horse race',
        self::SCORE_SCOPE_GAME_TAROT         => 'tarot',
        self::SCORE_SCOPE_GAME_TEEN_PATTI    => 'Teen Patti',
        self::SCORE_SCOPE_GAME_ROCKET        => 'Rocket',
        self::SCORE_SCOPE_GAME_DRAGON_TIGER  => 'Dragon Tiger',
        self::SCORE_SCOPE_GAME_GREEDY_BOX    => 'Greedy Box',
        self::SCORE_SCOPE_GAME_GREEDY_SLOT   => 'Greedy Slot',
        self::SCORE_SCOPE_GAME_FISHING       => 'Fishing',
        self::SCORE_SCOPE_PRIVATE_SEND_GIFT  => '私聊打赏',
        self::SCORE_SCOPE_HUAWEI_IAP         => '华为充值',
        self::SCORE_SCOPE_SALARY_PREPAY      => '公会提成薪资预支',
        self::SCORE_SCOPE_GREEDY_BRUTAL_GIFT => 'Greedy Brutal',
    ];

    public static $sourceTypeAndScoreScopeMap = [
        self::SOURCE_TYPE_GIFT   => [
            self::SCORE_SCOPE_CHAT, self::SCORE_SCOPE_LIVE, self::SCORE_SCOPE_PRIVATE_SEND_GIFT,
        ],
        self::SOURCE_TYPE_TOP_UP => [
            self::SCORE_SCOPE_TOP_UP_APPLE, self::SCORE_SCOPE_TOP_UP_GOOGLE, self::SCORE_SCOPE_TOP_UP_AGENT_MONEY,
            self::SCORE_SCOPE_TOP_UP_THIRD_PARTY, self::SCORE_SCOPE_TOP_UP_BANK_NOTE, self::SCORE_SCOPE_TOP_UP_CHARM,
            self::SCORE_SCOPE_HUAWEI_IAP, self::SCORE_SCOPE_SALARY_PREPAY,
        ],
        self::SOURCE_TYPE_GAMES  => [
            self::SCORE_SCOPE_GAME_GREEDY, self::SCORE_SCOPE_GAME_SLOT, self::SCORE_SCOPE_GAME_SICBO,
            self::SCORE_SCOPE_GAME_LUCKY_FRUIT, self::SCORE_SCOPE_GAME_HORSE_RACE, self::SCORE_SCOPE_GAME_TAROT,
            self::SCORE_SCOPE_GAME_TEEN_PATTI, self::SCORE_SCOPE_GAME_ROCKET, self::SCORE_SCOPE_GAME_DRAGON_TIGER,
            self::SCORE_SCOPE_GAME_GREEDY_BOX, self::SCORE_SCOPE_GAME_GREEDY_SLOT, self::SCORE_SCOPE_GAME_FISHING,
            // self::SCORE_SCOPE_GREEDY_BRUTAL_GIFT,
        ],
    ];

    // 积分来源与统计范围映射关系
    public static $scopeAndSourceTypeMap = [
        self::SCORE_SCOPE_CHAT               => self::SOURCE_TYPE_GIFT,
        self::SCORE_SCOPE_LIVE               => self::SOURCE_TYPE_GIFT,
        self::SCORE_SCOPE_PRIVATE_SEND_GIFT  => self::SOURCE_TYPE_GIFT,
        self::SCORE_SCOPE_TOP_UP_APPLE       => self::SOURCE_TYPE_TOP_UP,
        self::SCORE_SCOPE_TOP_UP_GOOGLE      => self::SOURCE_TYPE_TOP_UP,
        self::SCORE_SCOPE_TOP_UP_AGENT_MONEY => self::SOURCE_TYPE_TOP_UP,
        self::SCORE_SCOPE_TOP_UP_THIRD_PARTY => self::SOURCE_TYPE_TOP_UP,
        self::SCORE_SCOPE_TOP_UP_BANK_NOTE   => self::SOURCE_TYPE_TOP_UP,
        self::SCORE_SCOPE_TOP_UP_CHARM       => self::SOURCE_TYPE_TOP_UP,
        self::SCORE_SCOPE_GAME_GREEDY        => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_SLOT          => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_SICBO         => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_LUCKY_FRUIT   => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_HORSE_RACE    => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_TAROT         => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_TEEN_PATTI    => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_ROCKET        => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_DRAGON_TIGER  => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_GREEDY_BOX    => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_GREEDY_SLOT   => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_GAME_FISHING       => self::SOURCE_TYPE_GAMES,
        self::SCORE_SCOPE_HUAWEI_IAP         => self::SOURCE_TYPE_TOP_UP,
        self::SCORE_SCOPE_SALARY_PREPAY      => self::SOURCE_TYPE_TOP_UP,
        self::SCORE_SCOPE_GREEDY_BRUTAL_GIFT => self::SOURCE_TYPE_GAMES,
    ];

    public static $giftScoreTypeMap = [
        self::SCORE_TYPE_PAY_GIFT_ID, self::SCORE_TYPE_PAY_GIFT_NUM,
        self::SCORE_TYPE_ACCEPT_GIFT_ID, self::SCORE_TYPE_ACCEPT_GIFT_NUM,
    ];

    const PK_VALID_TYPE_PK_TIME = 0;
    const PK_VALID_TYPE_PK_GIFT = 1;
    const PK_VALID_TYPE_PK_TIME_AND_GIFT = 2;
    const PK_VALID_TYPE_PK_TIME_OR_GIFT = 3;

    public static $pkValidTypeMap = [
        self::PK_VALID_TYPE_PK_TIME          => 'pk时长达标',
        self::PK_VALID_TYPE_PK_GIFT          => 'pk收礼达标',
        self::PK_VALID_TYPE_PK_TIME_AND_GIFT => 'pk时长且收礼达标',
        self::PK_VALID_TYPE_PK_TIME_OR_GIFT  => 'pk时长或收礼达标',
    ];

    /**
     * 获取对应枚举映射关系
     * @param array $relateMap
     * @param array $nameMap
     * @return array
     */
    public static function getOptions(array $relateMap, array $nameMap): array
    {
        $map = [];
        foreach ($relateMap as $key => $value) {
            foreach ($value as $v) {
                $map[$key][] = [
                    'label' => $nameMap[$v],
                    'value' => $v
                ];
            }
        }

        return $map;
    }

    /**
     * 获取最大数据id
     * @return int
     */
    public static function getMaxId(): int
    {
        $data = self::findFirst([
            'columns' => 'MAX(id) as max_id',
        ])->toArray();

        return $data ? $data['max_id'] : 1;
    }
}