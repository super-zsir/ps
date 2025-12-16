<?php

namespace Imee\Models\Xs;


class XsBigarea extends BaseModel
{
    const START_SWITCH = 1;
    // 个人主页展示原始ID开关
    const SHOW_ORIGIN_UID_SWITCH = 8;
    // 个人主页客态情况下点击粉丝、关注、好友、可跳转查看到被查看用户的对应列表开关
    const GUEST_RELATION_JUMP_SWITCH = 9;

    public static $displayInviteGiftSwitch = [
        0 => '关',
        1 => '开',
    ];

    public static $accountUidDevMap = [
        1  => 816290470,
        2  => 816290471,
        3  => 816290472,
        4  => 816290473,
        5  => 816290474,
        6  => 816290475,
        7  => 816290476,
        8  => 816290477,
        9  => 816290478,
        11 => 816290479,
        12 => 816290480,
        13 => 816290481,
        14 => 816290482,
        15 => 816290483,
    ];

    public static $accountUidMap = [
        1  => 820125688,
        2  => 820125701,
        3  => 820125719,
        4  => 820125724,
        5  => 820125730,
        6  => 820125734,
        7  => 820125743,
        8  => 820125760,
        9  => 820125786,
        10 => 820125797,
        11 => 820125808,
        12 => 820125810,
        13 => 820125817,
        14 => 820125837,
        15 => 820125844,
    ];

    const AREA_MAP = [
        1  => '英语大区',
        2  => '中文大区',
        3  => '阿语大区',
        4  => '韩语大区',
        5  => '印尼大区',
        6  => '泰语大区',
        7  => '越语大区',
        8  => '土耳其大区',
        9  => '马来大区',
        10 => '日语大区',
        11 => '孟加拉大区',
        12 => '印度大区',
        13 => '巴基斯坦大区',
        14 => '菲律宾大区',
        15 => '马来华语区',
    ];

    // 大区宝箱配置汇总对应id
    public static $boxConfigIds = [
        'switch'                     => 1,
        'consecutive_big_loss_rate'  => 2,
        'consecutive_big_loss_count' => 3,
        'ratio_0'                    => 4,
        'ratio_10'                   => 5,
        'reward_ratio'               => 6,
        'available_reward_ratio'     => 7,
        'greedy_box_diamond_2'       => 8,
        'greedy_box_diamond_3'       => 9,
        'greedy_box_diamond_4'       => 10,
        'greedy_box_diamond_5'       => 11,
        'flow_reward_ratio'          => 12,
        'bet_reward_ratio'           => 13,
        'flow_reward_limit'          => 14,
    ];

    // 白名单用户宝箱掉落配置对应ID
    public static $specailConfigIds = [
        'consecutive_big_loss_rate'  => 2,
        'consecutive_big_loss_count' => 3,
        'reward_ratio'               => 6,
    ];

    // 大区宝箱config配置
    public static $boxconfig = [
        'consecutive_big_loss_rate', 'consecutive_big_loss_count', 'ratio_0',
        'ratio_10', 'reward_ratio', 'available_reward_ratio', 'flow_reward_ratio',
        'bet_reward_ratio', 'flow_reward_limit'
    ];

    // 大区宝箱配置汇总
    public static $boxConfigAll = [
        'switch'                     => '大区标准开关',
        'consecutive_big_loss_rate'  => ' 超量亏损比例',
        'consecutive_big_loss_count' => ' 下发所需轮次',
        'ratio_0'                    => '0档比例',
        'ratio_10'                   => '10档比例',
        'reward_ratio'               => ' 单轮返奖比例',
        'available_reward_ratio'     => ' 利润奖励比例',
        'greedy_box_diamond_2'       => '奖励金额3',
        'greedy_box_diamond_3'       => '奖励金额4',
        'greedy_box_diamond_4'       => '奖励金额5',
        'greedy_box_diamond_5'       => '奖励金额6',
        'flow_reward_ratio'          => '流水奖励比例',
        'bet_reward_ratio'           => '投入奖励比例',
        'flow_reward_limit'          => '流水奖励限额',
    ];

    /**
     * @desc 新版大区
     * @var string[]
     */
    public static $_bigAreaMap = [
        'cn' => '中文大区',
        'id' => '印尼大区',
        'ar' => '阿语大区',
        'th' => '泰语大区',
        'vi' => '越南大区',
        'ko' => '韩语大区',
        'ms' => '马来大区',
        'en' => '英文大区',
        'tr' => '土耳其大区',
        'ja' => '日语大区',
//        'pt' => '葡语大区',
//        'es' => '西语大区',
        // PS 新增大区 - by dulijie 20221104
        'hi' => '印度大区',
        'bn' => '孟加拉大区',
        'ur' => '巴基斯坦大区',
        'tl' => '菲律宾大区',
        'mz' => '马来华语区'
    ];

    public static $langToBigArea = [
        'zh_cn' => 'cn',
        'zh_tw' => 'cn',
    ];

    const GREEDY_START_A = 0;
    const GREEDY_START_B = 1;
    public static $greedyEngine = [
        self::GREEDY_START_A => 'GreedyStar A',
        self::GREEDY_START_B => 'GreedyStar B',
    ];

    const HORSE_RACE_A = 0;
    const HORSE_RACE_B = 1;
    public static $horseRaceEngine = [
        self::HORSE_RACE_A => 'Horse Pool A',
        self::HORSE_RACE_B => 'Horse Pool B',
    ];

    // 房间类玩法开关
    const LAYA_LUDO_ROOM_SWITCH = 1;
    const LAYA_CARROM_ROOM_SWITCH = 2;
    const LAYA_BILLIARD_ROOM_SWITCH = 3;
    const LAYA_UNO_ROOM_SWITCH = 4;

    public static function getBigAreaList(): array
    {
        return self::$_bigAreaMap;
    }

    public static function getBigAreaCnName($area): string
    {
        return self::$_bigAreaMap[$area] ?? '-';
    }

    /**
     * 获取所有新大区
     */
    public static function getAllNewBigArea()
    {
        $arr = [];
        $res = self::find()->toArray();
        if ($res) {
            foreach ($res as $v) {
                $arr[$v['id']] = self::getBigAreaCnName($v['name']);
            }
        }
        return $arr;
    }

    /**
     * 获取大区英文code
     */
    public static function getAllBigAreaCode(): array
    {
        $res = self::find()->toArray();
        if (empty($res)) return [];

        return array_column($res, 'name', 'id');
    }

    public static function getAreaList(): array
    {
        $bigareas = XsBigarea::find()->toArray();
        $bigareas = array_map(function ($a) {
            $a['cn_name'] = self::getBigAreaCnName($a['name']);
            return $a;
        }, $bigareas);
        return $bigareas;
    }

    /**
     * 获取下拉筛选格式
     * @return array
     */
    public static function getAreaFilter(): array
    {
        $data = [];
        $bigareas = self::getAreaList();
        foreach ($bigareas as $v) {
            $data[] = array($v["id"], $v['cn_name'] . "({$v["id"]})");
        }
        return $data;
    }

    public static function langToBigAreaName($lang)
    {
        $area = self::$langToBigArea[$lang] ?? $lang;
        return self::getBigAreaCnName($area);
    }

    public static function getLanguageArr()
    {
        return array(
            'zh_cn' => '简体中文',
            'zh_tw' => '繁体中文',
            'en'    => '英语',
            'ar'    => '阿语',
            'ms'    => '马来语',
            'th'    => '泰语',
            'id'    => '印尼语',
            'vi'    => '越南语',
            'ko'    => '韩语',
            'ja'    => '日语',
            'pt'    => '葡语',
            'tr'    => '土耳其语',
            'es'    => '西语',
            'hi'    => '印地语',
            'bn'    => '孟加拉语',
            'ur'    => '乌尔都语',
            'tl'    => '他加禄语',
            'mz'    => '马来华语'
        );
    }

    public static function getLanguageNewArr()
    {
        return array(
            'en' => '英文',
            'cn' => '中文',
            'ar' => '阿拉伯语',
            'ko' => '韩语',
            'id' => '印尼语',
            'th' => '泰语',
            'vi' => '越南语',
            'tr' => '土耳其语',
            'ms' => '马来语',
            'bn' => '孟加拉语',
            'hi' => '印度语',
            'ur' => '乌尔都语',
            'tl' => '菲律宾语',
            'ja' => '日语',
        );
    }

    public static function getLanguageName($language)
    {
        $languageMap = self::getLanguageArr();
        return $languageMap[$language] ?? '-';
    }

    /**
     * 获取大区幸运玩法状态
     * @param $conditions
     * @param $page
     * @param $pageSize
     * @return array
     */
    public static function getBigareaLuckyPlaySwitchList($conditions, $page, $pageSize)
    {
        $list = self::getListAndTotal($conditions, '*', 'id asc', $page, $pageSize);
        foreach ($list['data'] as &$v) {
            $v['lucky_gift_switch'] = json_decode($v['lucky_gift_config'], true)['lucky_gift_switch'] ?? 0;
            $v['bigarea_name'] = self::getBigAreaCnName($v['name']);
        }
        return $list;
    }

    /**
     * 获取大区中文名
     * @return array
     */
    public static function getBigAreaCnNameById()
    {
        $data = self::getAreaList();

        return array_column($data, 'cn_name', 'id');
    }


    public static function getBigareaIdList($bigareaCodeArr = []): array
    {
        if ($bigareaCodeArr) {
            $res = self::getListByWhere([['name', 'in', $bigareaCodeArr]]);
        } else {
            $res = self::findAll();
        }

        return array_column($res, 'id', 'name');
    }


    /**
     * 指定分隔符格式话大区名称
     * @param $bigArea
     * @param string $replace
     * @param string $symbol
     * @return string
     */
    public static function formatBigAreaName($bigArea, $replace = '|', $symbol = ',')
    {
        $bigAreaMap = self::getAllNewBigArea();
        $bigAreaMap[99] = '全区';
        if (empty($bigArea)) {
            return '';
        }
        if (!is_array($bigArea)) {
            $bigArea = explode($replace, $bigArea);
        }
        $areaStr = '';
        foreach ($bigArea as $v) {
            if (isset($bigAreaMap[$v])) {
                $areaStr .= $bigAreaMap[$v] . $symbol;
            }
        }
        return rtrim($areaStr, $symbol);
    }

    public static function setGreedyBoxDefault(int $id)
    {
        $default = [
            'switch'             => 0,
            'config'             => [
                'consecutive_big_loss_rate'  => 8000,
                'consecutive_big_loss_count' => 4,
                'ratio_0'                    => 7000,
                'ratio_10'                   => 3000,
                'reward_ratio'               => 1000,
                'available_reward_ratio'     => 1000,
                'greedy_box_diamond'         => [0, 10, 500, 4000, 10000, 50000],
                'flow_reward_ratio'          => 0,
                'bet_reward_ratio'           => 0,
                'flow_reward_limit'          => 0,
            ],
            'special_box_config' => [
                'consecutive_big_loss_rate'  => 100,
                'consecutive_big_loss_count' => 3,
                'reward_ratio'               => 5000,
            ],
        ];

        $config = XsBigarea::findOne($id)['greedy_box'] ?? [];
        if (empty($config)) {
            return $default;
        }
        $config = json_decode($config, true);
        $config['config'] = array_merge($default['config'], $config['config']);
        $config['special_box_config'] = array_merge($default['special_box_config'], $config['special_box_config'] ?? []);
        return $config;
    }

    /**
     * 根据大区id获取大区名称
     * @param $id
     * @param string $symbol
     * @return string
     */
    public static function getAreaName($id, string $symbol = ','): string
    {
        $bidArr = $id;
        if (!is_array($id)) {
            $bidArr = [$id];
            if (strpos($id, $symbol) !== false) {
                $bidArr = explode($symbol, $id);
            }
        }

        $area = [];
        $bigAreaNameMap = self::getBigAreaCnNameById();
        foreach ($bidArr as $bid) {
            if (isset($bigAreaNameMap[$bid])) {
                $area[] = $bigAreaNameMap[$bid];
            }
        }

        return $area ? implode(',', $area) : '';
    }

    public static function findFirstBigAreaName(int $id): array
    {
        $info = self::findOne($id);
        if ($info) {
            $info['cn_name'] = self::$_bigAreaMap[$info['name']] ?? '';
        }

        return $info;
    }

    /**
     * 根据大区简称获取大区ID
     * @param array $bigAreaNameArr
     * @return array
     */
    public static function getBigAreaIdByName(array $bigAreaNameArr): array
    {
        $bigAreaMap = self::getAllBigAreaCode();

        return array_map(function ($item) use ($bigAreaMap) {
            return array_search($item, $bigAreaMap);
        }, $bigAreaNameArr);
    }
}
