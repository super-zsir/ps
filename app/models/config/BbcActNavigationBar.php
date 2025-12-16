<?php

namespace Imee\Models\Config;

use Imee\Models\Xs\XsActHonourWallConfig;
use Imee\Models\Xs\XsPkPropCardFirstGiftConfig;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xsst\XsstActNavigationBarSearch;

class BbcActNavigationBar extends BaseModel
{
    protected static $primaryKey = 'id';

    const START_TIME_MIN = 1000000000;
    const MYSQL_UINT_MAX = 4294967295;

    const ACT_TEMPLATE_TYPE_UNKNOWN = 0;
    const ACT_TEMPLATE_TYPE_RANK = 1;
    const ACT_TEMPLATE_TYPE_TASK = 2;
    const ACT_TEMPLATE_TYPE_MUTLI_TASK = 3;
    const ACT_TEMPLATE_TYPE_WHEEL = 4;
    const ACT_TEMPLATE_TYPE_TOP_UP = 5;
    const ACT_TEMPLATE_TYPE_FIRST_RECHARGE = 6;
    const ACT_TEMPLATE_TYPE_HONOUR_WALL = 7;
    const ACT_TEMPLATE_TYPE_EXCHANGE = 8;

    public static $actTypeMaps = [
        self::ACT_TEMPLATE_TYPE_RANK           => '榜单模板',
        self::ACT_TEMPLATE_TYPE_TASK           => '单线任务',
        self::ACT_TEMPLATE_TYPE_MUTLI_TASK     => '多线独立任务模板',
        self::ACT_TEMPLATE_TYPE_WHEEL          => '幸运玩法模板',
        self::ACT_TEMPLATE_TYPE_TOP_UP         => '累充模板',
        self::ACT_TEMPLATE_TYPE_FIRST_RECHARGE => '首充模板',
        self::ACT_TEMPLATE_TYPE_HONOUR_WALL    => '荣誉墙模板',
        self::ACT_TEMPLATE_TYPE_EXCHANGE       => '积分兑换模板',
    ];


    const STATE_WAIT = 1;
    const STATE_RUNNING = 2;
    const STATE_END = 3;

    public static $stateMaps = [
        self::STATE_WAIT    => '待开始',
        self::STATE_RUNNING => '进行中',
        self::STATE_END     => '已结束',
    ];

    public static function updateSearchTime($barId, $isMaster = false)
    {
        $model = BbcActNavigationBar::findOne($barId, $isMaster);
        if (!empty($model)) {
            $mainRace = @json_decode($model['main_race_json'], true);
            $attachRace = @json_decode($model['attach_race_json'], true);
            $mainRace = array_merge($mainRace, $attachRace);

            list($startTime, $endTime) = [self::MYSQL_UINT_MAX, 0];
            foreach ($mainRace as $item) {
                switch ($item['act_type']) {
                    case self::ACT_TEMPLATE_TYPE_RANK:
                    case self::ACT_TEMPLATE_TYPE_TASK:
                    case self::ACT_TEMPLATE_TYPE_MUTLI_TASK:
                    case self::ACT_TEMPLATE_TYPE_WHEEL:
                    case self::ACT_TEMPLATE_TYPE_EXCHANGE:
                        $data = BbcTemplateConfig::findOne($item['act_id']);
                        if (empty($data)) {
                            // 如果找不到对应的模板配置，跳过此项目
                            break;
                        }
                        if ($data['time_offset']) {
                            $offset = (8 - (intval($data['time_offset']) / 10)) * 3600;
                        } else {
                            $offset = 0;
                        }
                        $startTime = min($startTime, $data['start_time'] - $offset);
                        $endTime = max($endTime, $data['end_time'] - $offset - intval($data['data_period'] * 86400));
                        break;
                    case self::ACT_TEMPLATE_TYPE_TOP_UP://累充模板
                        $data = XsTopUpActivity::findOne($item['act_id']);
                        if (empty($data)) {
                            // 如果找不到对应的累充活动配置，跳过此项目
                            break;
                        }
                        $offset = intval($data['time_offset'] / 10 ?: 8);
                        $startTime = min($startTime, $data['start_time'] - ((8 - $offset) * 3600));
                        $endTime = max($endTime, $data['end_time'] - ((8 - $offset) * 3600));
                        break;
                    case self::ACT_TEMPLATE_TYPE_FIRST_RECHARGE://首充模板
//                        XsPkPropCardFirstGiftConfig::findOne($item['act_id']);
                        $startTime = self::START_TIME_MIN;
                        $endTime = self::MYSQL_UINT_MAX;
                        break;
                    case self::ACT_TEMPLATE_TYPE_HONOUR_WALL://荣誉墙
//                        XsActHonourWallConfig::findOne($item['act_id']);
                        $startTime = self::START_TIME_MIN;
                        $endTime = self::MYSQL_UINT_MAX;
                        break;
                    default:
                        break;
                }
            }

            $search = XsstActNavigationBarSearch::findOneByWhere([['bar_id', '=', $barId]], '*', 'id desc', $isMaster);
            if (empty($search)) {
                XsstActNavigationBarSearch::add([
                    'bar_id'     => $barId,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ]);
            } else {
                if ($search['start_time'] != $startTime || $search['end_time'] != $endTime) {
                    XsstActNavigationBarSearch::edit($search['id'], [
                        'start_time' => $startTime,
                        'end_time'   => $endTime,
                    ]);
                }
            }
        }
    }

    public static function updateAllSearchTime($limit = 300)
    {
        $lists = XsstActNavigationBarSearch::getListByWhere([], 'bar_id', 'bar_id desc', $limit);
        foreach ($lists as $list) {
            self::updateSearchTime($list['bar_id']);
        }
    }

}