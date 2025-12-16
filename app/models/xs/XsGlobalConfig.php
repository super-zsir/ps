<?php

namespace Imee\Models\Xs;

class XsGlobalConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const GAME_CENTER_ID_UNKNOWN = 0;
    const GAME_CENTER_ID_GREEDY = 1;
    const GAME_CENTER_ID_SIC_BO = 2;
    const GAME_CENTER_ID_SLOT = 3;
    const GAME_CENTER_ID_LUCKY_WHEEL = 4;
    const GAME_CENTER_ID_END = 5;
    const GAME_CENTER_ID_HORSE_RACE = 6;
    const GAME_CENTER_ID_LUCKY_FRUIT = 7;
    const GAME_CENTER_ID_ROCKET_CRASH = 8;
    const GAME_CENTER_ID_TAROT = 9;
    const GAME_CENTER_ID_TEEN_PATTI = 10;
    const GAME_CENTER_ID_NEW_SLOT = 11;
    const GAME_CENTER_ID_GREEDY_BOX = 12;
    const GAME_CENTER_ID_FISHING = 13;
    const GAME_CENTER_ID_SWEET_CANDY = 14;
    const GAME_CENTER_ID_GREEDY_BRUTAL = 15;


    const GREEDY_PLAY = 1;
    const DICE_PLAY = 2;
    const DRAGON_TIGER_PLAY = 8;
    const CRASH_PLAY = 13;
    const HORSE_RACE_PLAY_START = 201;
    const LUCKY_FRUIT_TYPE = 301;
    const GLOBAL_CONFIG_ROCKET_CRASH = 302;
    const GLOBAL_CONFIG_TAROT = 303;

    public static $gameCenterIdMap = [
        self::GAME_CENTER_ID_UNKNOWN      => 'UNKNOWN',
        self::GAME_CENTER_ID_GREEDY       => 'GREEDY',
        self::GAME_CENTER_ID_SIC_BO       => 'SIC_BO',
        self::GAME_CENTER_ID_SLOT         => 'SLOT',
        self::GAME_CENTER_ID_LUCKY_WHEEL  => 'LUCKY_WHEEL',
        self::GAME_CENTER_ID_END          => 'Dragon_Tiger',
        self::GAME_CENTER_ID_HORSE_RACE   => 'HORSE_RACE',
        self::GAME_CENTER_ID_LUCKY_FRUIT  => 'LUCKY_FRUIT',
        self::GAME_CENTER_ID_ROCKET_CRASH => 'CRASH',
        self::GAME_CENTER_ID_TAROT        => 'TAROT',
        self::GAME_CENTER_ID_TEEN_PATTI   => 'TEEN_PATTI',
        self::GAME_CENTER_ID_NEW_SLOT     => 'GREEDY_SLOT',
        self::GAME_CENTER_ID_GREEDY_BOX   => 'GREEDY_BOX',
        self::GAME_CENTER_ID_FISHING      => 'FISHING',
        self::GAME_CENTER_ID_SWEET_CANDY  => 'SWEET_CANDY',
    ];

    public static $probabilityMap = [
        'sic_bo_init_status'           => '初始化状态',
        'profit_line'                  => '利润分割线',
        'profit_money'                 => '利润分割金额',
        'prize_pool_refill_line'       => '奖池补充线',
        'prize_pool_lower_limit_today' => '每日奖池底限',
        'reward_upper_limit_rate'      => '反奖上线',
        'gold_finger_rate'             => '作弊率'
    ];

    public static $probabilityIds = [
        'sic_bo_init_status'           => 1,
        'profit_line'                  => 2,
        'profit_money'                 => 3,
        'prize_pool_refill_line'       => 4,
        'prize_pool_lower_limit_today' => 5,
        'reward_upper_limit_rate'      => 6,
        'gold_finger_rate'             => 7
    ];

    public static $luckyFruitParams = [
        'profit_line',
        'profit_money',
        'prize_pool_refill_line',
        'prize_pool_lower_limit_today',
        'global_loss_line',
        'reward_upper_limit_rate',
        'system_commission_rate',
    ];

    public static $greedyBoxParamsIds = [
        'consecutive_big_loss_rate'  => 1,
        'consecutive_big_loss_count' => 2,
        'ratio_0'                    => 3,
        'ratio_10'                   => 4,
        'reward_ratio'               => 5,
        'available_reward_ratio'     => 6,
    ];

    public static $greedyBoxParams = [
        'consecutive_big_loss_rate'  => ' 超量亏损比例',
        'consecutive_big_loss_count' => ' 下发所需轮次',
        'ratio_0'                    => '0档比例',
        'ratio_10'                   => '10档比例',
        'reward_ratio'               => ' 单轮返奖比例',
        'available_reward_ratio'     => ' 可用奖励比例',
    ];

    // crash玩法配置参数
    public static $rocketCrashParamsIds = [
        'beginning_crash_percent' => 1,
        'hours'                   => 2,
        'return_rate'             => 3,
        'emoji_switch'            => 4,
        'jp_reward'               => 5,
        'ahead_off'               => 6,
    ];

    // crash玩法配置参数
    public static $rocketCrashParams = [
        'beginning_crash_percent' => 'N%',
        'hours'                   => 'X',
        'return_rate'             => 'M%',
        'emoji_switch'            => 'emote',
        'jp_reward'               => 'JP reward',
        'ahead_off'               => 'Ahead off',
    ];

    /**
     * 食物对应文字
     * @var string[]
     */
    public static $greedyFoodCnName = [
        0   => 'START',
        1   => '萝卜',
        2   => '玉米',
        3   => '番茄',
        4   => '菜花',
        5   => '大虾',
        6   => '鸡腿',
        7   => '肉',
        8   => '鱼',
        100 => '沙拉',
        101 => '披萨'
    ];

    /**
     * 食物对应id
     * @var string[]
     */
    public static $greedyFoodId = [
        0   => 'start',
        1   => 'carrot',
        2   => 'corn',
        3   => 'tomatoes',
        4   => 'cauliflower',
        5   => 'shrimp',
        6   => 'drumstick',
        7   => 'meat',
        8   => 'fish',
        100 => 'salad',
        101 => 'pizza',
    ];

    public static $horseRaceMap = [
        1 => '1号马',
        2 => '2号马',
        3 => '3号马',
        4 => '4号马',
        5 => '5号马',
        6 => '6号马',
        7 => '7号马',
        8 => '8号马',
    ];

    /**
     * 参数对应文字
     * @var string[]
     */
    public static $params = [
        'greedy_init_status'            => '是否初始化',
        'horse_race_init_status'        => '是否初始化',
        'profit_line'                   => '利润分割线',
        'profit_money'                  => '利润分割金额',
        'prize_pool_refill_line'        => '奖池补充线',
        'prize_pool_lower_limit_today'  => '每日奖池底限',
        'reward_upper_limit_rate'       => '反奖上限',
        'in_group_gold_finger_rate'     => '组内作弊率',
        'change_group_gold_finger_rate' => '换组作弊率',
        'salad'                         => 'salad_大奖线',
        'pizza'                         => 'pizza_大奖线',
        'system_commission_rate'        => '系统抽水百分比',
        'global_loss_line'              => '全局亏损线',
        'after_percent'                 => 'after',
        'pizza_cd_interval'             => '披萨冷却间隔',
        'pizza_random_interval'         => '披萨随机区间',
        'salad_cd_interval'             => '沙拉冷却间隔',
        'salad_random_interval'         => '沙拉随机区间',
        'hours'                         => 'X',
        'return_rate'                   => 'M%',
        'limit_loss_money'              => 'loss',
    ];

    // greedystar 参数配置
    public static $greedystarParams = [
        'greedy_init_status', 'profit_line', 'profit_money', 'prize_pool_refill_line', 'prize_pool_lower_limit_today',
        'reward_upper_limit_rate', 'in_group_gold_finger_rate', 'change_group_gold_finger_rate', 'salad', 'pizza',
        'pizza_cd_interval', 'pizza_random_interval', 'salad_cd_interval', 'salad_random_interval'
    ];

    // 赛马参数配置
    public static $horseRaceParams = ['hours', 'return_rate', 'limit_loss_money', 'after_percent'];

    // params 默认值
    public static $paramsDefaultValue = [
        'pizza_cd_interval'     => 1440,
        'pizza_random_interval' => 240,
        'salad_cd_interval'     => 1440,
        'salad_random_interval' => 240
    ];

    /**
     * 参数对应ID
     * @var int[]
     */
    public static $paramsIds = [
        'greedy_init_status'            => 1,
        'horse_race_init_status'        => 1,
        'profit_line'                   => 2,
        'profit_money'                  => 3,
        'prize_pool_refill_line'        => 4,
        'prize_pool_lower_limit_today'  => 5,
        'reward_upper_limit_rate'       => 6,
        'in_group_gold_finger_rate'     => 7,
        'change_group_gold_finger_rate' => 8,
        'system_commission_rate'        => 9,
        'global_loss_line'              => 10,
        'after_percent'                 => 11,
        'pizza_cd_interval'             => 12,
        'pizza_random_interval'         => 13,
        'salad_cd_interval'             => 14,
        'salad_random_interval'         => 15,
        'hours'                         => 16,
        'return_rate'                   => 17,
        'limit_loss_money'              => 18,
        'salad'                         => 100,
        'pizza'                         => 101,
    ];

    public static $sicBoConfig = [
        1 => 'small',
        2 => 'big',
        3 => 'triple'
    ];

    public static $dragonTigerConfig = [
        1 => '龙',
        2 => '虎',
        3 => '和'
    ];

    public static function getParamsConfigByType(int $type): array
    {
        $config = self::findByType($type);
        if (!$config) {
            return [];
        }
        $data = [];
        [$map, $ids, $keys] = self::setMapData($type);
        foreach ($keys as $key) {
            $data[] = [
                'id'      => $ids[$key],
                'name'    => $key,
                'cn_name' => $map[$key],
                'weight'  => $config[$key] ?? 0,
            ];
        }
        return $data;
    }

    public static function getDiceConfigOdds(): array
    {
        $config = self::findByType(self::DICE_PLAY);
        if (!$config) {
            return [];
        }
        $config = $config['sic_bo_config'];
        $params = [];
        foreach ($config as $k => $v) {
            $params[] = [
                'id'       => $v['SIC_BO_ID'],
                'name'     => self::$sicBoConfig[$v['SIC_BO_ID']],
                'hit_rate' => $v['hit_rate']
            ];
        }
        return $params;
    }

    public static function getGreedyWeightConfigParams(int $greedyEngineId): array
    {
        $config = self::findByType(self::getGreedyType($greedyEngineId));
        if (!$config || empty($config['big_reward']) && empty($config['food_config'])) {
            return [];
        }
        $weight = [];

        foreach ($config['big_reward'] as $val) {
            $weight[] = [
                'id'               => $val['id'],
                'name'             => $val['name'] . ' ' . self::$greedyFoodCnName[$val['id']],
                'type'             => 'big',
                'hit_rate'         => $val['hit_rate'],
                'greedy_engine_id' => $greedyEngineId,
            ];
        }
        foreach ($config['food_config'] as $val) {
            $weight[] = [
                'id'               => $val['id'],
                'name'             => $val['name'] . ' ' . self::$greedyFoodCnName[$val['id']],
                'type'             => 'food',
                'hit_rate'         => $val['hit_rate'],
                'greedy_engine_id' => $greedyEngineId,
            ];
        }

        return $weight;
    }

    public static function getGreedyConfigParams(int $greedyEngineId): array
    {
        $config = self::findByType(self::getGreedyType($greedyEngineId));

        if (!$config) {
            return [];
        }
        $bigReward = $config['big_reward'] ?? [];
        $bigReward = array_column($bigReward, 'reward_line', 'name');
        $config = array_merge($config, $bigReward);

        $params = [];

        foreach (self::$greedystarParams as $key) {
            $params[] = [
                'id'               => self::$paramsIds[$key],
                'name'             => $key,
                'c_name'           => self::$params[$key],
                'number'           => $config[$key] ?? self::$paramsDefaultValue[$key] ?? 0,
                'greedy_engine_id' => $greedyEngineId,
            ];
        }

        return $params;
    }

    public static function getGreedyBoxConfigParams(int $greedyEngineId): array
    {
        $config = self::findByType(self::getGreedyType($greedyEngineId))['greedy_box_config'] ?? [];

        $params = [];

        foreach (self::$greedyBoxParams as $key => $item) {
            $params[] = [
                'id'               => self::$greedyBoxParamsIds[$key],
                'name'             => $key,
                'c_name'           => $item,
                'number'           => $config[$key] ?? 0,
                'greedy_engine_id' => $greedyEngineId,
            ];
        }

        return $params;
    }

    public static function setMapData(int $type): array
    {
        if ($type == self::DICE_PLAY) {
            $map = self::$probabilityMap;
            $keys = array_keys($map);
            $ids = self::$probabilityIds;
        } else if ($type == self::DRAGON_TIGER_PLAY) {
            $map = self::$probabilityMap;
            unset($map['sic_bo_init_status']);
            $map['dragon_tiger_init_status'] = '初始化状态';
            $keys = array_keys($map);
            $ids = self::$probabilityIds;
            unset($ids['sic_bo_init_status']);
            $ids['dragon_tiger_init_status'] = 1;
        } else if ($type == self::LUCKY_FRUIT_TYPE) {
            $map = $ids = [];
            foreach (self::$luckyFruitParams as $param) {
                $map[$param] = self::$params[$param];
                $ids[$param] = self::$paramsIds[$param];
            }
            $keys = self::$luckyFruitParams;
        } else if ($type == self::GLOBAL_CONFIG_ROCKET_CRASH) {
            $map = self::$rocketCrashParams;
            $keys = array_keys($map);
            $ids = self::$rocketCrashParamsIds;
        } else {
            $map = $ids = $keys = [];
        }
        return [$map, $ids, $keys];
    }

    public static function findByType(int $type): array
    {
        $config = self::findOneByWhere([
            ['type', '=', $type]
        ]);
        if (empty($config) || empty($config['comment'])) {
            return [];
        }
        return json_decode($config['comment'], true);
    }

    public static function editConfig(int $type, string $keys, int $weight): array
    {
        $config = self::findByType($type);
        if (!$config) {
            return [false, '数据不存在'];
        }
        if (!isset($config[$keys])) {
            return [false, '当前配置不存在'];
        }
        $config[$keys] = $weight;

        return [true, $config];
    }

    /**
     * 修改Greedy权重配置
     * @param int $id
     * @param int $hitRate
     * @return array
     */
    public static function setGreedyWeightConfig(int $id, int $hitRate, int $greedyEngineId): array
    {
        $config = self::findByType(self::getGreedyType($greedyEngineId));
        if ($id < 100) {
            foreach ($config['food_config'] as &$value) {
                if ($value['id'] == $id) {
                    $value['hit_rate'] = $hitRate;
                }
            }
        } else {
            foreach ($config['big_reward'] as &$value) {
                if ($value['id'] == $id) {
                    $value['hit_rate'] = $hitRate;
                }
            }
        }
        $config['lastUpdateTime'] = time();
        $config['engine_id'] = $greedyEngineId;
        return $config;
    }

    /**
     * 修改Greedy参数配置
     * @param int $id
     * @param int $number
     * @return array
     */
    public static function modifyGreedyParamsConfig(int $id, int $number, int $greedyEngineId): array
    {
        $config = self::findByType(self::getGreedyType($greedyEngineId));
        self::setParamsValue($id, $config);
        if (in_array($id, [100, 101])) {
            foreach ($config['big_reward'] as &$val) {
                if ($val['id'] == $id) {
                    $val['reward_line'] = $number;
                }
            }
        } else {
            $key = array_search($id, self::$paramsIds);
            $config[$key] = $number;
        }
        $config['lastUpdateTime'] = time();
        $config['engine_id'] = $greedyEngineId;
        return $config;
    }

    /**
     * 设置默认值
     * @param int $id
     * @param array $config
     * @return void
     */
    public static function setParamsValue(int $id, array &$config)
    {
        foreach (self::$paramsDefaultValue as $key => $defaultValue) {
            if (!isset($config[$key])) {
                $config[$key] = $defaultValue;
            }
        }
    }

    /**
     * 修改GreedyBox参数配置
     * @param int $id
     * @param int $number
     * @return array
     */
    public static function modifyGreedyBoxParamsConfig(int $id, int $number, int $greedyEngineId): array
    {
        $config = self::findByType(self::getGreedyType($greedyEngineId));
        $config['greedy_box_config'] = $config['greedy_box_config'] ?? [];
        foreach (self::$greedyBoxParamsIds as $key => $val) {
            if ($val == $id) {
                $config['greedy_box_config'][$key] = $number;
            } else {
                $config['greedy_box_config'][$key] = $config['greedy_box_config'][$key] ?? 0;
            }
        }
        $config['lastUpdateTime'] = time();
        $config['engine_id'] = $greedyEngineId;
        return $config;
    }

    public static function getDragonTigerConfigOdds(): array
    {
        $config = self::findByType(self::DRAGON_TIGER_PLAY);
        if (!$config) {
            return [];
        }
        $config = $config['dragon_tiger_config'];
        $params = [];
        foreach ($config as $v) {
            $params[] = [
                'id'       => $v['DRAGON_TIGER_ID'],
                'name'     => self::$dragonTigerConfig[$v['DRAGON_TIGER_ID']],
                'hit_rate' => $v['hit_rate']
            ];
        }
        return $params;
    }

    public static function getHorseRaceConfigParams(): array
    {
        $config = self::findByType(self::HORSE_RACE_PLAY_START);
        if (!$config) {
            return [];
        }
        $params = [];
        foreach (self::$horseRaceParams as $value) {
            $params[] = [
                'id'      => self::$paramsIds[$value],
                'name'    => $value,
                'cn_name' => self::$params[$value],
                'weight'  => $config[$value] ?? 0,
            ];
        }
        return $params;
    }

    /**
     * 新增的GReedy类型从 100+ 开始
     * @param $greedyEngineId
     * @return int
     */
    public static function getGreedyType($greedyEngineId)
    {
        return self::GREEDY_PLAY + ($greedyEngineId ? $greedyEngineId + 99 : 0);//默认 100 + type
    }

    public static function getHorseRaceWeightConfigParams(int $horseRaceEngineId): array
    {
        $config = self::findByType(self::getHorseRaceType($horseRaceEngineId));
        if (!$config || empty($config['horse_config'])) {
            return [];
        }
        $weight = [];
        foreach ($config['horse_config'] as $val) {
            $weight[] = [
                'id'       => $val['id'],
                'name'     => self::$horseRaceMap[$val['id']],
                'hit_rate' => $val['hit_rate'],
                'group'    => $val['group']
            ];
        }
        return $weight;
    }

    public static function modifyHorseRaceParamsConfig($name, $number): array
    {
        $config = self::findByType(self::HORSE_RACE_PLAY_START);
        $config[$name] = $name == 'after_percent' ? (float)$number : (int)$number;
        $config['lastUpdateTime'] = time();
        return $config;
    }

    /**
     * 修改Greedy权重配置
     * @param int $id
     * @param int $hitRate
     * @return array
     */
    public static function setHorseRaceWeightConfig(array $horseConfig, int $horseRaceEngineId): array
    {
        $config = self::findByType(self::getHorseRaceType($horseRaceEngineId));
        $horseConfig = array_map(function ($item) {
            return array_map('intval', $item);
        }, $horseConfig);
        $config['horse_config'] = $horseConfig;
        $config['lastUpdateTime'] = time();
        return $config;
    }

    /**
     * 新增的赛马类型从 201+ 开始
     * @param $horseRaceEngineId
     * @return int
     */
    public static function getHorseRaceType(int $horseRaceEngineId)
    {
        return self::HORSE_RACE_PLAY_START + $horseRaceEngineId; //默认 201 + type
    }

    /**
     * 获取日志记录ID
     * 每个分区$greedyEngineId 里，日志model_id 加100万，用以区分
     * @param $ids
     * @param $greedyEngineId
     * @return array|float|int|string
     */
    public static function getLogId($ids, $greedyEngineId)
    {
        if (is_array($ids)) {
            foreach ($ids as &$_id) {
                $_id = $_id + $greedyEngineId * 1000000;
            }
        }
        if (is_numeric($ids)) {
            return $ids + $greedyEngineId * 1000000;
        }
        return $ids;
    }
}