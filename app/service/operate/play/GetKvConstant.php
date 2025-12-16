<?php

namespace Imee\Service\Operate\Play;

class GetKvConstant
{
    const KEY_TAROT_BIG_AREA_SWITCH = 'tarot_bigarea_switch';
    const KEY_TAROT_BIG_AREA_LIMIT_LEVEL = 'tarot_big_area_limit_level';
    const KEY_TAROT_PARAMETERS = 'tarot_parameters';
    const KEY_TEEN_PATTI_PARAMETERS = 'teen_patti_parameters';
    const KEY_TEEN_PATTI_BIG_AREA_LIMIT_LEVEL = 'teen_patti_big_area_limit_level';
    const KEY_TEEN_PATTI_BIG_AREA_SWITCH = 'teen_patti_bigarea_switch';
    const KEY_NEW_SLOT_BIG_AREA_LIMIT_LEVEL = 'new_slot_big_area_limit_level';
    const KEY_NEW_SLOT_BIG_AREA_SWITCH = 'new_slot_big_area_switch';
    const KEY_NEW_SLOT_PARAMETERS = 'new_slot_parameters';


    const INDEX_TAROT_BIG_AREA_LIST = 'tarot_big_area_list';
    const INDEX_TEEN_PATTI_BIG_AREA_LIST = 'teen_patti_big_area_list';
    const KEY_GREEDY_BOX_BIG_AREA_LIMIT_LEVEL = 'greedy_box_big_area_limit_level';
    const INDEX_BIG_AREA_LIST = 'big_area_list';
    const KEY_GREEDY_BOX_BIG_AREA_SWITCH = 'greedy_box_bigarea_switch';
    const KEY_GREEDY_BOX_PARAMETERS = 'greedy_box_parameters';

    const KEY_FISHING_BIG_AREA_LIMIT_LEVEL = 'fish_big_area_limit_level';
    const KEY_FISHING_BIG_AREA_SWITCH = 'fish_bigarea_switch';
    
    const KEY_DRAGON_TIGER_BIG_AREA_SWITCH = 'dragon_tiger_big_area_switch';
    const KEY_DRAGON_TIGER_PARAMETERS = 'dragon_tiger_parameters';
    const INDEX_DRAGON_TIGER_CONFIG = 'dragon_tiger_config';
    const KEY_HORSE_RACE_BIG_AREA_SWITCH = 'horse_race_big_area_switch';
    const KEY_HORSE_RACE_PARAMETERS = 'horse_race_parameters';
    const INDEX_HORSE_RACE_CONFIG = 'horse_config';
    const KEY_LUCKY_FRUITS_BIG_AREA_SWITCH = 'lucky_fruits_big_area_switch';
    const KEY_LUCKY_FRUIT_PARAMETERS = 'lucky_fruits_parameters';
    const KEY_ROCKET_CRASH_BIG_AREA_SWITCH = 'rocket_crash_big_area_switch';
    const KEY_ROCKET_CRASH_PARAMETERS = 'rocket_crash_parameters';

    const KEY_SWEET_CANDY_BIG_AREA_LIMIT_LEVEL = 'sweet_candy_big_area_limit_level';
    const KEY_SWEET_CANDY_BIG_AREA_SWITCH = 'sweet_candy_big_area_switch';

    const KEY_GREEDY_BRUTAL_BIG_AREA_LIMIT_LEVEL = 'greedy_brutal_big_area_limit_level';
    const KEY_GREEDY_BRUTAL_BIG_AREA_SWITCH = 'greedy_brutal_big_area_switch';

    const BUSINESS_TYPE_TAROT = 1;
    const BUSINESS_TYPE_TEEN_PATTI = 2;
    const BUSINESS_TYPE_NEW_SLOT = 3;
    const BUSINESS_TYPE_GREEDY_BOX = 4;
    const BUSINESS_TYPE_FISHING = 5;
    const BUSINESS_TYPE_SWEET_CANDY = 7;
    const BUSINESS_TYPE_HORSE_RACE = 8;
    const BUSINESS_TYPE_LUCKY_FRUIT = 9;
    const BUSINESS_TYPE_DRAGON_TIGER = 10;
    const BUSINESS_TYPE_ROCKET_CRASH = 12;
    const BUSINESS_TYPE_GREEDY_BRUTAL = 7;

    const TAROT_PARAMS_ID = [
        'hours'                        => 1,
        'return_rate'                  => 2,
        'lucky_duration'               => 3,
        'robot_switch'                 => 4,
        'after_percent'                => 5,
        'limit_loss_money'             => 6,
        'jp_duration'                  => 7,
        'first_three'                  => 8,
        'jp_add_ratio'                 => 9,
        'dragon_tiger_init_status'     => 1,
        'profit_line'                  => 2,
        'profit_money'                 => 3,
        'prize_pool_refill_line'       => 4,
        'prize_pool_lower_limit_today' => 5,
        'reward_upper_limit_rate'      => 6,
        'gold_finger_rate'             => 7,
        'beginning_crash_percent'      => 1,
        'emoji_switch'                 => 4,
        'jp_reward'                    => 5,
        'ahead_off'                    => 6,
        'get_jp_rate'                  => 10,
    ];

    const TAROT_PARAMS_NAME = [
        'hours'                        => 'X',
        'return_rate'                  => 'M%',
        'lucky_duration'               => 'Lucky CD',
        'robot_switch'                 => 'AI',
        'after_percent'                => 'after',
        'limit_loss_money'             => 'Loss',
        'jp_duration'                  => 'JP CD',
        'first_three'                  => 'First Three',
        'jp_add_ratio'                 => 'JP Add',
        'dragon_tiger_init_status'     => '初始化状态',
        'profit_line'                  => '利润分割线',
        'profit_money'                 => '利润分割金额',
        'prize_pool_refill_line'       => '奖池补充线',
        'prize_pool_lower_limit_today' => '每日奖池底限',
        'reward_upper_limit_rate'      => '反奖上线',
        'gold_finger_rate'             => '作弊率',
        'system_commission_rate'       => '系统抽水百分比',
        'global_loss_line'             => '全局亏损线',
        'beginning_crash_percent'      => 'N%',
        'emoji_switch'                 => 'emote',
        'jp_reward'                    => 'JP reward',
        'ahead_off'                    => 'Ahead off',
        'get_jp_rate'                  => 'JP 10000'
    ];

    const LUCKY_FRUIT_PARAMS_NAME = [
        'profit_line'                  => 'Jackpot limit',
        'profit_money'                 => 'Amount in excess of limit',
        'prize_pool_refill_line'       => 'Jackpot addition limit',
        'prize_pool_lower_limit_today' => 'Daily jackpot Lower Limit',
        'system_commission_rate'       => 'Percentage of system share',
        'global_loss_line'             => 'Compensatory limit',
        'reward_upper_limit_rate'      => 'Reward limit',
    ];

    const PARAMS_FIELDS = [
        self::BUSINESS_TYPE_TAROT        => ['hours', 'return_rate', 'lucky_duration', 'robot_switch', 'after_percent', 'limit_loss_money'],
        self::BUSINESS_TYPE_TEEN_PATTI   => ['hours', 'return_rate', 'jp_duration', 'robot_switch', 'after_percent', 'limit_loss_money'],
        self::BUSINESS_TYPE_NEW_SLOT     => ['hours', 'return_rate', 'jp_duration', 'first_three', 'jp_add_ratio', 'limit_loss_money', 'get_jp_rate'],
        self::BUSINESS_TYPE_GREEDY_BOX   => ['hours', 'return_rate', 'jp_duration', 'robot_switch', 'after_percent', 'limit_loss_money'],
        self::BUSINESS_TYPE_DRAGON_TIGER => ['dragon_tiger_init_status', 'profit_line', 'profit_money', 'prize_pool_refill_line', 'prize_pool_lower_limit_today', 'reward_upper_limit_rate', 'gold_finger_rate'],
        self::BUSINESS_TYPE_HORSE_RACE   => ['hours', 'return_rate', 'limit_loss_money', 'after_percent'],
        self::BUSINESS_TYPE_LUCKY_FRUIT  => ['profit_line', 'profit_money', 'prize_pool_refill_line', 'prize_pool_lower_limit_today', 'system_commission_rate', 'global_loss_line', 'reward_upper_limit_rate'],
        self::BUSINESS_TYPE_ROCKET_CRASH => ['beginning_crash_percent', 'hours', 'return_rate', 'emoji_switch', 'jp_reward', 'ahead_off']
    ];
}