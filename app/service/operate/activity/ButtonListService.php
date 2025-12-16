<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Helper\Traits\BitmapTrait;
use Imee\Models\Config\BbcRankAward;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Service\Helper;

class ButtonListService
{
    use BitmapTrait;

    public function getList(array $params, int $page, int $pageSize): array
    {
        $conditions = $this->getConditions($params);
        $data = BbcRankButtonList::getListAndTotal($conditions, '*', 'level asc, id desc', $page, $pageSize);
        if (empty($data['data'])) {
            return $data;
        }
        $format = 'Y-m-d H:i:s';
        // 日榜时间需要特殊处理一下，只保留日期
        $tagList = BbcRankButtonTag::getBatchCommon(array_column($data['data'], 'button_tag_id'), ['id', 'tag_list_type', 'rank_object']);
        // 获取日榜时区，取第一条数据的act_id即可
        $config = BbcTemplateConfig::findOne($data['data'][0]['act_id'] ?? 0);
        $timeOffset = (new ActivityService)->getTimeOffsetNew($config['time_offset']);
        foreach ($data['data'] as &$val) {
            $tagListType = $tagList[$val['button_tag_id']]['tag_list_type'];
            $val['time_offset'] = (new ActivityService)->setTimeOffsetNew($config['time_offset']);
            if ($tagList[$val['button_tag_id']]['rank_object'] != BbcRankButtonTag::RANK_OBJECT_CP) {
                $val['cp_gender'] = '-';
            }
            $val['award_time'] = $val["award_time"] > 0 ? Helper::now($val['award_time'] - $timeOffset) : '';
            if (self::isDaysAndCycleList($tagListType)) {
                $format = 'Y-m-d';
                $val['award_time'] = $val['is_award'] ? '0' : '/';
            }
            // 去除time_offset时间
            $val['start_time'] = $val['start_time'] - $timeOffset;
            $val['end_time'] = $val['end_time'] - $timeOffset;
            // 列表数据使用
            $val['start_date_time'] = $val['start_time'] > 0 ? Helper::now($val['start_time']) : '';
            $val['end_date_time'] = $val['end_time'] > 0 ? Helper::now($val['end_time']) : '';
            $val['dateline'] = $val['dateline'] > 0 ? Helper::now($val['dateline']) : ' - ';
            // 复制回显使用
            $val['start_time'] = $val["start_time"] > 0 ? date($format, $val['start_time']) : '';
            $val['end_time'] = $val["end_time"] > 0 ? date($format, $val['end_time']) : '';
            $tmpA = CmsUser::findFirst(intval($val['admin_id']));
            $val['admin'] = $tmpA ? $tmpA->user_name : ' - ';
            $val['rank_tag'] = strval($val['rank_tag']);
            $val['is_award'] = strval($val['is_award']);
            $val['is_upgrade'] = strval($val['is_upgrade']);
            $val['upgrade_type'] = $val['is_upgrade'] == BbcRankButtonList::IS_UPGRADE_NO ? '' : strval($val['upgrade_type']);
            $val['is_open'] = strval($val['is_open']);
            $val['cp_gender'] = strval($val['cp_gender']);
            $button_tag_info = BbcRankButtonTag::findFirst(intval($val['button_tag_id']));
            $val['button_tag_type'] = $button_tag_info ? $button_tag_info->button_tag_type : '-';
            $val['button_desc'] = htmlspecialchars($val['button_desc']);
            $val['level'] = strval($val['level']);
            $val['divide_track'] = strval($val['divide_track']);
            $val['divide_track'] == 0 && $val['divide_type'] = '';
            $val['divide_type'] = strval($val['divide_type']);
            $val['divide_object'] = strval($val['divide_object']);
            $val['room_support'] = self::getRoomSupportValue($val['room_support']);

            $val['divide_days'] = $val['days'] ?: $val['broker_distance_start_day'];
            $scoreType = '';
            if ($val['score_max'] == 4294967295) {
                $scoreType = BbcRankButtonList::SCORE_TYPE_EGT;
            } elseif ($val['score_min'] == 0 && $val['score_max']) {
                $scoreType = BbcRankButtonList::SCORE_TYPE_ELT;
            } elseif ($val['score_min'] && $val['score_max']) {
                $scoreType = BbcRankButtonList::SCORE_TYPE_RANGE;
            }
            $val['hide_info'] = array_map('strval', self::getSetBits($val['hide_score']));
            $val['hide_score'] = $val['hide_score'] ? 1 : 0;
            $val['score_type'] = strval($scoreType);
            $val['is_only_cross_room_pk'] = strval($val['is_only_cross_room_pk']);
            $val['is_total_wins'] = strval($val['is_total_wins']);
            $totalWinsExtend = json_decode($val['total_wins_extend'], true);
            if ($totalWinsExtend) {
                $val['total_wins_type'] = ($totalWinsExtend['total_wins_type'] ?? -1) < 0 ? '' : strval($totalWinsExtend['total_wins_type']);
                $val['total_wins_score_value'] = $totalWinsExtend['score'] ?? '';
                $val['total_wins_rank_value'] = $totalWinsExtend['rank'] ?? '';
            }
        }

        return $data;
    }

    public function add(array $params): array
    {
        list($vRes, $vMsg) = $this->valid($params);
        if (!$vRes) {
            return [false, $vMsg];
        }
        $tmpRows = $this->formatData($params);
        [$res, $msg] = BbcRankButtonList::add($tmpRows);
        if (!$res) {
            return [false, $msg];
        }
        $tmpRows['vision_type'] = $params['vision_type'];
        self::updateMasterList($tmpRows);
        $this->updateCpTagCycles($tmpRows);
        $activeData = [
            'start_time' => $tmpRows['start_time'],
            'end_time'   => $tmpRows['end_time'],
        ];
        usleep(100 * 1000);
        $activityService = new ActivityService();
        $activityService->updateActivityInfo($tmpRows['act_id'], $activeData);
        return [true, ''];
    }

    public function edit(array $params): array
    {
        list($vRes, $vMsg) = $this->valid($params);
        if (!$vRes) {
            return [false, $vMsg];
        }
        $tmpRows = $this->formatData($params);
        [$res, $msg] = BbcRankButtonList::edit($params['id'], $tmpRows);
        if (!$res) {
            return [false, $msg];
        }
        // 活动状态为非待生成和待发布(已生成)状态下直接返回
        $activityService = new ActivityService();
        if ($activityService->validActiveStatus($params['status'])) {
            return [true, ''];
        }
        $tmpRows['vision_type'] = $params['vision_type'];
        self::updateMasterList($tmpRows);
        $this->updateCpTagCycles($tmpRows);
        $this->deletePrizePoolReward($tmpRows, [$params['id']]);
        usleep(100 * 1000);
        $activeData = [
            'start_time' => $tmpRows['start_time'],
            'end_time'   => $tmpRows['end_time'],
        ];
        $activityService->updateActivityInfo($tmpRows['act_id'], $activeData);
        return [true, ''];
    }

    public function info(int $id): array
    {
        $res = BbcRankButtonList::findOne($id);
        $tag = BbcRankButtonTag::findOne($res['button_tag_id']);
        $format = 'Y-m-d H:i:s';
        $config = BbcTemplateConfig::findOne($res['act_id']);

        $activityService = new ActivityService();
        // 去除time_offset时间
        $res['start_time'] = $res['start_time'] - $activityService->getTimeOffsetNew($config['time_offset']);
        $res['end_time'] = $res['end_time'] - $activityService->getTimeOffsetNew($config['time_offset']);
        $res['award_time'] = $res['award_time'] ? $res['award_time'] - $activityService->getTimeOffsetNew($config['time_offset']) : 0;
        $res['time_offset'] = $activityService->setTimeOffsetNew($config['time_offset'], false);

        if (self::isDaysAndCycleList($tag['tag_list_type'])) {
            $format = 'Y-m-d';
            $res['award_time'] = 0;
        }

        if ($res) {
            $res['start_time'] = $res["start_time"] > 0 ? date($format, $res["start_time"]) : '';
            $res['end_time'] = $res["end_time"] > 0 ? date($format, $res["end_time"]) : '';
            $res['award_time'] = $res['award_time'] ? date('Y-m-d H:i:s', $res['award_time']) : '0';
            $res['is_award'] = strval($res["is_award"]);
            $res['is_upgrade'] = strval($res["is_upgrade"]);
            $res['rank_tag'] = strval($res["rank_tag"]);
            $res['level'] = strval($res["level"]);
            $res['is_open'] = strval($res["is_open"]);
            $res['is_only_cross_room_pk'] = strval($res["is_only_cross_room_pk"]);
            //$res['can_transfer'] = strval($res["can_transfer"]);
            $res['divide_type'] = strval($res["divide_type"]);
            $res['upgrade_type'] = $res['is_upgrade'] == BbcRankButtonList::IS_UPGRADE_NO ? '' : strval($res['upgrade_type']);
            $res['divide_track'] = strval($res['divide_track']);
            $res['divide_object'] = strval($res['divide_object']);
            $res['divide_days'] = $res['days'] ?: $res['broker_distance_start_day'];
            $scoreType = '';
            if ($res['score_max'] == 4294967295) {
                $scoreType = BbcRankButtonList::SCORE_TYPE_EGT;
            } else if ($res['score_min'] == 0 && $res['score_max']) {
                $scoreType = BbcRankButtonList::SCORE_TYPE_ELT;
            } else if ($res['score_min'] && $res['score_max']) {
                $scoreType = BbcRankButtonList::SCORE_TYPE_RANGE;
            }
            $res['hide_info'] = array_map('strval', self::getSetBits($res['hide_score']));
            $res['hide_score'] = $res['hide_score'] ? 1 : 0;
            $res['score_type'] = strval($scoreType);
            $res['room_support'] = self::getRoomSupportValue($res['room_support']);
            $res['is_total_wins'] = strval($res['is_total_wins']);
            $totalWinsExtend = json_decode($res['total_wins_extend'], true);
            if ($totalWinsExtend) {
                $res['total_wins_type'] = ($totalWinsExtend['total_wins_type'] ?? -1) < 0 ? '' : strval($totalWinsExtend['total_wins_type']);
                $res['total_wins_score_value'] = $totalWinsExtend['score'] ?? '';
                $res['total_wins_rank_value'] = $totalWinsExtend['rank'] ?? '';
            }
        }
        return $res;
    }

    /**
     * 转换房间类型的值
     * @param int $roomSupport
     * @return array
     */
    private static function getRoomSupportValue(int $roomSupport): array
    {
        $roomSupportMap = [
            BbcRankButtonList::ROOM_SUPPORT_VOICE_AND_VIDEO              => [BbcRankButtonList::ROOM_SUPPORT_VOICE, BbcRankButtonList::ROOM_SUPPORT_VIDEO],
            BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT_AND_VOICE       => [BbcRankButtonList::ROOM_SUPPORT_VOICE, BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT],
            BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT_AND_VIDEO       => [BbcRankButtonList::ROOM_SUPPORT_VIDEO, BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT],
            BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT_VOICE_AND_VIDEO => [BbcRankButtonList::ROOM_SUPPORT_VOICE, BbcRankButtonList::ROOM_SUPPORT_VIDEO, BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT],
        ];

        $roomSupportArr = $roomSupportMap[$roomSupport] ?? [$roomSupport];

        return array_map('strval', $roomSupportArr);
    }

    /**
     * 设置room_support值
     * @param $roomSupport
     * @return int
     */
    private static function setRoomSupportValue($roomSupport): int
    {
        if (!is_array($roomSupport)) {
            return BbcRankButtonList::ROOM_SUPPORT_VOICE;
        }

        // 根据房间支持类型的数量进行处理
        $count = count($roomSupport);

        // 单个类型直接取第一个即可
        if ($count == 1) {
            return $roomSupport[0];
        }
        if ($count == 3) {
            return BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT_VOICE_AND_VIDEO;
        }

        if ($count == 2) {
            sort($roomSupport); // 排序确保顺序不会影响结果
            $roomSupportStr = implode(',', $roomSupport);
            return BbcRankButtonList::$roomSupportMapping[$roomSupportStr] ?? reset($roomSupport);
        }

        return BbcRankButtonList::ROOM_SUPPORT_VOICE;
    }

    /**
     * 设置奖池改为0时需要同步删除奖池奖励
     *
     * @param array $data
     * @param array $idArr
     * @return void
     */
    public function deletePrizePoolReward(array $data, array $idArr): void
    {
        if ($data['has_prize_pool'] == BbcRankButtonList::HAS_PRIZE_POOL_NO) {
            BbcRankAward::deleteByWhere([
                ['button_list_id', 'IN', $idArr],
                ['award_type', '=', BbcRankAward::AWARD_TYPE_PRIZE_POOL]
            ]);
        }
    }

    public static function updateMasterList(array $params): void
    {
        if (in_array($params['vision_type'], [BbcTemplateConfig::VISION_TYPE_THREE, BbcTemplateConfig::VISION_TYPE_FOUR])) {
            $update = [
                'room_support' => self::getMasterRoomSupport($params['act_id'], $params['vision_type'])
            ];
            $rankTag = BbcRankButtonList::RANK_TAG_GIFT_GROUP;
            if ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
                $rankTag = BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT;
                $update['is_award'] = BbcRankButtonList::getWeekStarListIsAward($params['act_id']);
            }
            BbcRankButtonList::updateByWhere([
                ['act_id', '=', $params['act_id']],
                ['rank_tag', '=', $rankTag]
            ], $update);
        }
    }

    /**
     * 获取主榜的room_support
     * 子榜如果都是语音房，主榜就是语音房；就是子榜如果都是视频房，主榜就是视频房；子榜有视频和语音，主榜就是视频和语音
     * 新增私聊
     * @param int $actId
     * @param int $visionType
     * @return int
     */
    private static function getMasterRoomSupport(int $actId, int $visionType): int
    {
        $rankTags = [BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT_SUB_ACCEPT, BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT_SUB_SEND];
        if ($visionType == BbcTemplateConfig::VISION_TYPE_FOUR) {
            $rankTags = [BbcRankButtonList::RANK_TAG_SUB_ACCEPT, BbcRankButtonList::RANK_TAG_SUB_PAY];
        }
        $list = BbcRankButtonList::getListByWhere([
            ['act_id', '=', $actId],
            ['rank_tag', 'IN', $rankTags]
        ], 'id, room_support');

        $roomSupports = [];
        foreach ($list as $item) {
            $roomSupport = self::getRoomSupportValue($item['room_support']);
            $roomSupports = array_merge($roomSupports, $roomSupport);
        }
        $roomSupports = array_values(array_unique($roomSupports));
        return self::setRoomSupportValue($roomSupports);
    }

    public function getStartEndDay(int $startTime, int $endTime): int
    {
        return ceil(($endTime - $startTime) / 86400);
    }

    /**
     * 日|周榜同步tag的cycles字段
     * @param array $data
     * @return void
     */
    private function updateCpTagCycles(array $data): void
    {
        $tag = BbcRankButtonTag::findOneByWhere([
            ['id', '=', $data['button_tag_id']],
            ['tag_list_type', 'IN', [BbcRankButtonTag::TAG_LIST_TYPE_DAY, BbcRankButtonTag::TAG_LIST_TYPE_CYCLE]]
        ]);

        if ($tag) {
            $cycles = $this->getStartEndDay($data['start_time'], $data['end_time']);
            if ($tag['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE) {
                $cycles = $data['cycle_limit'];
            }
            BbcRankButtonTag::edit($data['button_tag_id'], [
                'cycles' => $cycles
            ]);
        }
    }

    private function formatData(array $params): array
    {
        // 活动状态为待生成和待发布(已生成)状态下只需更改引言及文案
        $activityService = new ActivityService();
        if ($activityService->validActiveStatus($params['status'])) {
            $list = BbcRankButtonList::findOne($params['id']);
            $update = [
                'button_content' => $params['button_content'] ?? '',
                'button_desc'    => $params['button_desc'] ?? '',
                'hide_score'     => $params['hide_score'] ?? 0,
                'rank_list_num'  => $params['rank_list_num'] ?? 0,
            ];

            return array_merge($list, $update);
        }

        // 日榜下需要特殊处理一下榜单结束时间
        // 日｜周期榜奖励时间和开始时间相同
        // 周星榜单不需要记录发奖时间
        if (self::isDaysAndCycleList($params['tag_list_type'])) {
            $params['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_DAY && $params['end_time'] += 86399;
            $params['is_award'] && $params['award_time'] = $params['start_time'];
        } else if ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            $params['award_time'] = 0;
        } else {
            $params['is_award'] && $params['award_time'] = $params['end_time'] + 60;
        }

        return [
            'button_tag_id'             => $params['button_tag_id'],
            'act_id'                    => $params['act_id'],
            'start_time'                => intval($params['start_time'] ?? 0),
            'end_time'                  => intval($params['end_time'] ?? 0),
            'award_time'                => intval($params['award_time'] ?? 0),
            'upgrade_type'              => intval($params['upgrade_type']),
            'upgrade_num'               => empty($params['upgrade_num']) ? 0 : intval($params['upgrade_num']),
            'upgrade_score'             => empty($params['upgrade_score']) ? 0 : intval($params['upgrade_score']),
            'upgrade_extend_num'        => empty($params['upgrade_extend_num']) ? 0 : intval($params['upgrade_extend_num']),
            'is_award'                  => empty($params['is_award']) ? 0 : $params['is_award'],
            'is_upgrade'                => $params['is_upgrade'],
            'rank_list_num'             => empty($params['rank_list_num']) ? 0 : $params['rank_list_num'],
            'is_open'                   => empty($params['is_open']) ? 1 : $params['is_open'],
            'room_support'              => empty($params['room_support']) ? 0 : $params['room_support'],
            'level'                     => $params['level'],
            'p_level'                   => $params['p_level'],
            'rank_tag'                  => $params['rank_tag'],
            'button_content'            => $params['button_content'] ?? '',
            'button_desc'               => $params['button_desc'] ?? '',
            'cp_gender'                 => 1,
            'cycles'                    => $params['cycles'] ?? 0,
            'admin_id'                  => Helper::getSystemUid(),
            'dateline'                  => time(),
            'divide_track'              => intval($params['divide_track'] ?? 0),
            'divide_object'             => intval($params['divide_object'] ?? 0),
            'score_min'                 => intval($params['score_min'] ?? 0),
            'score_max'                 => intval($params['score_max'] ?? 0),
            'days'                      => intval($params['days'] ?? 0),
            'broker_distance_start_day' => intval($params['broker_distance_start_day'] ?? 0),
            'divide_type'               => intval($params['divide_type'] ?? 0),
            'hide_score'                => $params['hide_score'],
            'has_prize_pool'            => intval($params['has_prize_pool'] ?? 0),
            'prize_pool_proportion'     => $params['prize_pool_proportion'] ?? 0,
            'cycle_days'                => $params['cycle_days'] ?? 0,
            'cycle_limit'               => $params['cycle_limit'] ?? 0,
            'is_only_cross_room_pk'     => intval($params['is_only_cross_room_pk'] ?? 0),
            'is_total_wins'             => $params['is_total_wins'] ?? 0,
            'total_wins_extend'         => $params['total_wins_extend'] ?? '',
        ];
    }

    /**
     * 是否日榜、周期榜
     * @param int $tagListType
     * @return bool
     */
    public static function isDaysAndCycleList(int $tagListType): bool
    {
        return in_array($tagListType, [BbcRankButtonTag::TAG_LIST_TYPE_DAY, BbcRankButtonTag::TAG_LIST_TYPE_CYCLE]);
    }

    private function checkLevel($tagId, $level, $startTime, $endTime, $id)
    {
        $level = intval($level);
        $list = BbcRankButtonList::getListByWhere([
            ['id', '<>', $id],
            ['button_tag_id', '=', $tagId]
        ]);
        foreach ($list as $item) {
            if ($item['level'] == $level + 1 && $endTime >= $item['start_time']) {
                return [false, "轮次时间不能重叠且不能大于下一轮次时间"];
            }
            if ($item['level'] == $level - 1 && $startTime <= $item['end_time']) {
                return [false, "轮次时间不能重叠且不能小于上一轮次时间"];
            }
        }

        return [true, ''];
    }

    /**
     * 获取承接轮次
     * 是否晋级赛为是时，承接轮次为选中轮次 - 1
     *
     * @param int $isUpgrade
     * @param int $level
     * @return string
     */
    public static function setPLevel(int $isUpgrade, int $level): string
    {
        if (empty($isUpgrade) || $level == 1) {
            return '';
        }

        return strval($level - 1);
    }

    private function valid(array &$params): array
    {
        $rankButtonInfo = BbcRankButtonTag::findOne($params['button_tag_id']);
        if (!$rankButtonInfo || !$rankButtonInfo['act_id']) {
            return [false, '活动button_tag信息错误'];
        }
        $config = BbcTemplateConfig::findOne($rankButtonInfo['act_id']);
        if (empty($config)) {
            return [false, '当前活动不存在'];
        }
        $params['status'] = $config['status'];

        // 验证是否隐藏积分相关信息
        if (!isset($params['act_id'])) {
            $params['act_id'] = $rankButtonInfo['act_id'];
        }
        list($hideScoreRes, $msg) = $this->validateHideScore($params);
        if (!$hideScoreRes) {
            return [false, $msg];
        }

        $activityService = new ActivityService();

        // 活动状态为待生成和待发布(已生成)状态下不需要往下验证
        if ($activityService->validActiveStatus($config['status'])) {
            return [true, ''];
        }

        // cp对象性别给默认值为全部
        $params['cp_gender'] = 1;
        $params['tag_list_type'] = (int)$rankButtonInfo['tag_list_type'];
        // 表里存放的是10倍时区
        $params['time_offset'] = (int)$config['time_offset'];
        $params['act_id'] = $config['id'];
        $params['vision_type'] = $config['vision_type'];
        $params['type'] = $config['type'];
        $params['rank_object'] = $rankButtonInfo['rank_object'];
        $params['level'] = (int)($params['level'] ?? 1);
        $params['is_upgrade'] = (int)($params['is_upgrade'] ?? 0);
        $params['p_level'] = self::setPLevel($params['is_upgrade'], $params['level']);
        $limitList = $this->setLimitList($config['vision_type'], $rankButtonInfo['tag_list_type'], $params['is_upgrade']);
        list($roomSupportRes, $msg) = $this->validateRoomSupport($params);
        if (!$roomSupportRes) {
            return [false, $msg];
        }
        list($isCycleRes, $msg) = $this->validateCycleList($params);
        if (!$isCycleRes) {
            return [false, $msg];
        }
        // 基础校验
        if (!in_array($config['vision_type'], [BbcTemplateConfig::VISION_TYPE_THREE, BbcTemplateConfig::VISION_TYPE_FOUR])) {
            if (empty($params['start_time']) || empty($params['end_time'])) {
                return [false, '榜单开始和结束时间不能为空'];
            }
            if (strtotime($params['start_time']) > strtotime($params['end_time'])) {
                return [false, '榜单开始时间必须小于结束时间'];
            }
            if ($rankButtonInfo['rank_object'] != 5 && $params['rank_tag'] == 3) {
                return [false, '房间流水榜,活动对象必须是商业厅频道'];
            }
            if (!isset($params['room_support'])) {
                return [false, '房间类型必选'];
            }
        }

        // 限制条数检测及是否存在该轮次；
        $buttonListLevel = BbcRankButtonList::getListByWhere([
            ['button_tag_id', '=', $params['button_tag_id']],
            ['id', '<>', $params['id'] ?? 0]
        ]);
        $isUpgrade = $params['is_upgrade'] ?? 0;
        if ($buttonListLevel) {
            if (count($buttonListLevel) >= $limitList) {
                return [false, "此处只能创建{$limitList}条记录"];
            }
            foreach ($buttonListLevel as $item) {
                // 用户维度下总榜需验证同个tag下cp性别类型、房间类型必须相同
                if ($config['type'] == BbcTemplateConfig::TYPE_RANK && $rankButtonInfo['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_TOTAL) {
                    $cpGender = $params['cp_gender'] ?? 1;
                    $roomSupport = $params['room_support'] ?? 0;
                    if ($cpGender != $item['cp_gender']) {
                        return [false, '同一个button性别配置必须相同'];
                    }
                    if ($roomSupport != $item['room_support']) {
                        return [false, '同一个button房间类型必须相同'];
                    }
                    if ($isUpgrade != $item['is_upgrade']) {
                        return [false, '晋级榜和非晋级榜不支持同时配置'];
                    }
                }

                if ($params['level'] == $item['level']) {
                    return [false, "已存在按钮顺序为{$params['level']}的buttonlist"];
                }
            }
        }

        if (isset($params['start_time']) && !empty($params['start_time'])) {
            $params['start_time'] = strtotime($params['start_time']) + $activityService->getTimeOffsetNew($params['time_offset']);
        }
        if (isset($params['end_time']) && !empty($params['end_time'])) {
            $params['end_time'] = strtotime($params['end_time']) + $activityService->getTimeOffsetNew($params['time_offset']);
        }

        if ($isUpgrade) {
            [$res, $msg] = $this->checkLevel($params['button_tag_id'], $params['level'], $params['start_time'] ?? 0, $params['end_time'] ?? 0, $params['id'] ?? 0);
            if (!$res) {
                return [false, $msg];
            }
        }

        //验证  晋级赛字段
        list($isUpdateFlg, $msg) = $this->validateIsUpdate($params);
        if (!$isUpdateFlg) {
            return [false, $msg];
        }

        // 验证赛道相关字段
        list($isDivideRes, $msg) = $this->validateDivideTrack($params);
        if (!$isDivideRes) {
            return [false, $msg];
        }

        // 验证奖池相关信息
        list($isPrizePoolRes, $msg) = $this->validateHasPrizePool($params);
        if (!$isPrizePoolRes) {
            return [false, $msg];
        }

        list ($isRoomPkRes, $msg) = $this->validateIsOnlyCrossRoomPk($params);
        if (!$isRoomPkRes) {
            return [false, $msg];
        }

        // 验证是否开启累胜玩法
        list($totalWinsRes, $msg) = $this->validateTotalWins($params);
        if (!$totalWinsRes) {
            return [false, $msg];
        }
        return [true, ''];
    }

    /**
     * 处理是否只统计跨房PK
     * @param array $params
     * @return array
     */
    private function validateIsOnlyCrossRoomPk(array &$params): array
    {
        $visionType = intval($params['vision_type'] ?? 0);
        $IsOnlyCrossRoomPk = intval($params['is_only_cross_room_pk'] ?? -1);
        $roomSupport = intval($params['room_support'] ?? BbcRankButtonList::ROOM_SUPPORT_VOICE);

        if (in_array($visionType, [BbcTemplateConfig::VISION_TYPE_FAMILY, BbcTemplateConfig::VISION_TYPE_CP]) && in_array($roomSupport, [BbcRankButtonList::ROOM_SUPPORT_VOICE, BbcRankButtonList::ROOM_SUPPORT_VIDEO])) {
            if ($IsOnlyCrossRoomPk < 0) {
                return [false, '基础视觉2和CP视觉下语音房和视频房必选是否只统计跨房PK'];
            }
        } else {
            $params['is_only_cross_room_pk'] = 0;
        }

        return [true, ''];
    }

    /**
     * 验证是否开启累充玩法
     * @param array $params
     * @return array
     */
    private function validateTotalWins(array &$params): array
    {
        // 非周星礼物榜直接默认值返回
        if ($params['vision_type'] != BbcTemplateConfig::VISION_TYPE_THREE) {
            $params['is_total_wins'] = 0;
            $params['total_wins_extend'] = '';

            return [true, ''];
        }
        $isTotalWins = intval($params['is_total_wins'] ?? -1);
        if ($isTotalWins < 0) {
            return [false, '周星礼物榜下是否开启累胜玩法必填'];
        }
        // 不开启累胜玩法 直接返回默认
        if ($isTotalWins == 0) {
            $params['is_total_wins'] = 0;
            $params['total_wins_extend'] = '';
            return [true, ''];
        }

        $params['is_total_wins'] = 1;

        $totalWinsType = intval($params['total_wins_type'] ?? -1);
        if ($totalWinsType < 0) {
            return [false, '开启累胜玩法时胜利条件必填'];
        }

        $totalWinsScore = $params['total_wins_score_value'] ?? '';
        $totalWinsRank = $params['total_wins_rank_value'] ?? '';

        $params['total_wins_extend'] = [
            'total_wins_type' => $totalWinsType,
        ];
        switch ($totalWinsType) {
            case '0':
                if (!preg_match('/^\d+$/', $totalWinsRank) || $totalWinsRank <= 0) {
                    return [false, '胜利条件按名次时名次要求必填且为正整数'];
                }
                $params['total_wins_extend']['rank'] = (int)$totalWinsRank;
                break;
            case '1':
                if (!preg_match('/^\d+$/', $totalWinsScore) || $totalWinsScore <= 0) {
                    return [false, '胜利条件按名次时积分达到必填且为正整数'];
                }
                $params['total_wins_extend']['score'] = (int)$totalWinsScore;
                break;
            case '2':
                if (!preg_match('/^\d+$/', $totalWinsRank) || $totalWinsRank <= 0 || !preg_match('/^\d+$/', $totalWinsScore) || $totalWinsScore <= 0) {
                    return [false, '胜利条件按名次+积分达标时名次要求和积分达到必填且为正整数'];
                }
                $params['total_wins_extend']['score'] = (int)$totalWinsScore;
                $params['total_wins_extend']['rank'] = (int)$totalWinsRank;
                break;
        }

        $params['total_wins_extend'] = json_encode($params['total_wins_extend']);
        return [true, ''];
    }

    /**
     * 处理下房间类型字段
     *  几种组合方式对应不同的房间类型值（单个就不赘述了）
     *  0，1 语音房 + 视频房
     *  0，4 语音房 + 私聊
     *  1，4 视频房 + 私聊
     *  0，1，4 语音房 + 视频房 + 私聊
     * @param array $params
     * @return array
     */
    private function validateRoomSupport(array &$params): array
    {
        $roomSupport = $params['room_support'] ?? BbcRankButtonList::ROOM_SUPPORT_VOICE;
        if (is_array($roomSupport) && in_array(BbcRankButtonList::ROOM_SUPPORT_NO_MATTER, $roomSupport) && count($roomSupport) > 1) {
            return [false, '房间类型选择与房型无关时不允许选择其他房型'];
        }

        $params['room_support'] = self::setRoomSupportValue($roomSupport);
        return [true, ''];
    }

    /**
     * 验证是否隐藏榜单信息/积分
     *
     * @param array $params
     * @return array
     */
    private function validateHideScore(array &$params): array
    {
        $hideScore = $params['hide_score'] ?? -1;
        if ($hideScore < 0) {
            return [false, '是否选择隐藏榜单积分/信息必填'];
        }
        if ($hideScore == 0) {
            return [true, ''];
        }
        if ($params['act_id'] > BbcTemplateConfig::VERSION_ID_TWO) {
            if ($hideScore && empty($params['hide_info'])) {
                return [false, '是否选择隐藏信息必填'];
            }
        }
        $params['hide_info'] = Helper::handleIds($params['hide_info'] ?? []);
        $params['hide_score'] = self::merge($params['hide_info'] ?: [$hideScore]);

        return [true, ''];
    }

    /**
     * 验证周期榜单信息
     * @param array $params
     * @return array
     */
    private function validateCycleList(array &$params): array
    {
        if ($params['tag_list_type'] != BbcRankButtonTag::TAG_LIST_TYPE_CYCLE) {
            $params['cycle_limit'] = $params['cycle_days'] = 0;
            return [true, ''];
        }
        $cycleDays = intval($params['cycle_days'] ?? 0);
        $cycleLimit = intval($params['cycle_limit'] ?? 0);
        $startTime = trim($params['start_time'] ?? '');

        if (empty($startTime)) {
            return [false, '周期开始时间必填'];
        }

        if ($cycleDays < 1 || $cycleDays > 90) {
            return [false, '周期天数需填写正整数，最大限制90'];
        }

        if ($cycleLimit < 1 || $cycleLimit > 30) {
            return [false, '周期循环次数需填写正整数，最大限制30'];
        }

        $params['end_time'] = strtotime($params['start_time']) + $cycleDays * $cycleLimit * 86400;
        $params['end_time'] = Helper::now($params['end_time'] - 1);
        return [true, ''];
    }

    /**
     * 验证奖池相关信息
     *
     * @param array $params
     * @return array
     */
    private function validateHasPrizePool(array &$params): array
    {
        $isAward = intval($params['is_award'] ?? 0);
        $hasPrizePool = intval($params['has_prize_pool'] ?? 0);
        $prizePoolProportion = $params['prize_pool_proportion'] ?? 0;

        if ($isAward == BbcRankButtonList::IS_AWARD_NO || $hasPrizePool == BbcRankButtonList::HAS_PRIZE_POOL_NO) {
            $params['has_prize_pool'] = $params['prize_pool_proportion'] = 0;
            return [true, ''];
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $prizePoolProportion) || $prizePoolProportion < 0.01 || $prizePoolProportion > 100) {
            return [false, '奖池比例填写大于等于0.01且小于等于100的数'];
        }

        $params['prize_pool_proportion'] = round($prizePoolProportion, 2);
        return [true, ''];
    }

    private function validateDivideTrack(&$params)
    {
        $level = intval($params['level'] ?? 0);
        $isUpgrade = intval($params['is_upgrade'] ?? 0);
        $divideTrack = intval($params['divide_track'] ?? 0);
        $divideObject = intval($params['divide_object'] ?? 0);
        $visionType = $params['vision_type'];
        $rankObject = $params['rank_object'];
        $divideType = intval($params['divide_type'] ?? 0);
        $divideDays = intval($params['divide_days']);
        $scoreType = intval($params['score_type']);
        $scoreMin = intval($params['score_min'] ?? 0);
        $scoreMax = intval($params['score_max'] ?? 0);

        // 非 公会｜用户｜主播&贡献用户 || 非基础视觉2 || 晋级赛且轮次不是1 || || 不限制上榜人群 直接返回默认值
        if (
            !in_array($rankObject, [
                BbcRankButtonTag::RANK_OBJECT_BROKER,
                BbcRankButtonTag::RANK_OBJECT_PERSONAL,
                BbcRankButtonTag::RANK_OBJECT_ANCHOR,
                BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS
            ])
            || $visionType != BbcTemplateConfig::VISION_TYPE_FAMILY
            || ($isUpgrade == BbcRankButtonList::IS_AWARD_YES && $level != 1)
            || $divideTrack == 0
        ) {
            $params['divide_track'] = 0;
            $params['score_min'] = 0;
            $params['score_max'] = 0;
            $params['days'] = 0;
            $params['divide_type'] = 0;
            $params['broker_distance_start_day'] = 0;
            $params['divide_object'] = 0;

            return [true, ''];
        }

        // 依据为按入会时间 || 对应为公会主播时 天数只允许填写1-90的正整数
        if (
            ($divideType == BbcRankButtonList::DIVIDE_TYPE_JOIN_BROKER || $divideType == BbcRankButtonList::DIVIDE_TYPE_LAST_JOIN_BROKER)
            && ($divideDays < 1 || $divideDays > 90)
        ) {
            return [false, '天数只允许填写1-90的正整数'];
        }

        // 榜单对象为公会用户  对象默认为 公会的主播
        if ($rankObject == BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS) {
            $divideObject = BbcRankButtonList::DIVIDE_OBJECT_BROKER_USER;
        }

        if ($divideObject == BbcRankButtonList::DIVIDE_OBJECT_BROKER_USER || in_array($divideType, [BbcRankButtonList::DIVIDE_TYPE_LAST_JOIN_BROKER, BbcRankButtonList::DIVIDE_TYPE_JOIN_BROKER])) {
            $scoreMin = $scoreMax = $scoreType = 0;
        }

        if (in_array($divideType, [BbcRankButtonList::DIVIDE_TYPE_RECEIVE_GIFT, BbcRankButtonList::DIVIDE_TYPE_WEALTH_LEVEL])) {
            $maxDays = $divideObject ? 90 : 30;
            if ($divideType == BbcRankButtonList::DIVIDE_TYPE_RECEIVE_GIFT && ($divideDays < 1 || $divideDays > $maxDays)) {
                return [false, sprintf('天数只允许填写1-%d的正整数', $maxDays)];
            }

            if ($divideObject != BbcRankButtonList::DIVIDE_OBJECT_BROKER_USER && empty($scoreType)) {
                return [false, '收礼数范围必须填写'];
            }

            switch ($scoreType) {
                case BbcRankButtonList::SCORE_TYPE_ELT:
                    if ($divideType == BbcRankButtonList::DIVIDE_TYPE_WEALTH_LEVEL && ($scoreMax < 0 || $scoreMax > 9999)) {
                        return [false, '财富等级填写范围0-9999'];
                    }
                    $scoreMin = 0;
                    break;
                case BbcRankButtonList::SCORE_TYPE_EGT:
                    if ($divideType == BbcRankButtonList::DIVIDE_TYPE_WEALTH_LEVEL && ($scoreMin < 0 || $scoreMin > 9999)) {
                        return [false, '财富等级填写范围0-9999'];
                    }
                    $scoreMax = 4294967295;
                    break;
                case BbcRankButtonList::SCORE_TYPE_RANGE:
                    if ($divideType == BbcRankButtonList::DIVIDE_TYPE_WEALTH_LEVEL && ($scoreMin < 0 || $scoreMin > 9999 || $scoreMax < 0 || $scoreMax > 9999)) {
                        return [false, '财富等级填写范围0-9999'];
                    }
                    break;
            }

            if ($scoreType == BbcRankButtonList::SCORE_TYPE_RANGE && $scoreMin >= $scoreMax) {
                return [false, '收礼范围选择区间时需保证下限不得高于上限'];
            }
        }

        $params['score_min'] = $scoreMin;
        $params['score_max'] = $scoreMax;

        switch ($divideType) {
            case BbcRankButtonList::DIVIDE_TYPE_RECEIVE_GIFT:
                $params['days'] = $divideDays;
                $params['broker_distance_start_day'] = 0;
                break;
            case BbcRankButtonList::DIVIDE_TYPE_JOIN_BROKER:
            case BbcRankButtonList::DIVIDE_TYPE_LAST_JOIN_BROKER:
                $params['broker_distance_start_day'] = $divideDays;
                $params['days'] = 0;
                break;
            case BbcRankButtonList::DIVIDE_TYPE_WEALTH_LEVEL:
                $params['broker_distance_start_day'] = 0;
                $params['days'] = 0;
                break;
        }

        if ($divideObject == BbcRankButtonList::DIVIDE_OBJECT_BROKER_USER && $rankObject != BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS) {
            $params['broker_distance_start_day'] = $divideDays;
            $params['divide_type'] = BbcRankButtonList::DIVIDE_TYPE_LAST_JOIN_BROKER;
            $params['days'] = 0;
        }

        return [true, ''];
    }

    /**
     * list限制多少条
     * 周星、多组视觉list限制1条
     * 定制视觉list限制2条
     * 基础1、2、cp视觉 总榜 3条，日榜|周期榜： 1条
     * 视觉2、非日榜、非晋级赛 7条
     * @param int $visionType
     * @param int $tagListType
     * @param int $isUpgrade
     * @return int
     */
    private function setLimitList(int $visionType, int $tagListType, int $isUpgrade): int
    {
        $limit = 3;
        if (in_array($visionType, [BbcTemplateConfig::VISION_TYPE_CUSTOMIZED])) {
            $limit = 2;
        } else if ($this->isDaysAndCycleList($tagListType) || in_array($visionType, [BbcTemplateConfig::VISION_TYPE_THREE, BbcTemplateConfig::VISION_TYPE_FOUR])) {
            $limit = 1;
        } else if ($visionType == BbcTemplateConfig::VISION_TYPE_FAMILY && $isUpgrade == BbcRankButtonList::IS_UPGRADE_NO) {
            $limit = 7;
        }
        return $limit;
    }

    public function getConditions(array $params): array
    {
        return [
            ['button_tag_id', '=', $params['button_tag_id']]
        ];
    }

    public function validateIsUpdate(&$params): array
    {
        if ($params['is_upgrade'] == BbcRankButtonList::IS_UPGRADE_YES) {
            if (!is_numeric($params['upgrade_type'])) {
                return [false, "请选择晋级方式"];
            }
            switch (intval($params['upgrade_type'])) {
                case BbcRankButtonList::UPGRADE_TYPE_ONE:
                    if (empty($params['upgrade_num'])) {
                        return [false, "晋级方式为【名次】，请输入名次"];
                    }
                    $params['upgrade_score'] = 0;
                    break;
                case BbcRankButtonList::UPGRADE_TYPE_TWO:
                    if (empty($params['upgrade_score'])) {
                        return [false, "晋级方式为【积分门槛】，请输入积分门槛"];
                    }
                    $params['upgrade_num'] = 0;
                    break;
                case BbcRankButtonList::UPGRADE_TYPE_THREE:
                    if (empty($params['upgrade_score']) || empty($params['upgrade_num'])) {
                        return [false, "晋级方式为【名次+积分门槛】，请输入名次与积分门槛"];
                    }
                    break;
                case BbcRankButtonList::UPGRADE_TYPE_FOUR:
                    if (empty($params['upgrade_score']) && empty($params['upgrade_num'])) {
                        return [false, "晋级方式为【名次或积分门槛】，请输入名次或积分门槛"];
                    }
                    break;
            }
        } else {
            $params['upgrade_type'] = 0;
            $params['upgrade_num'] = 0; //名次
            $params['upgrade_score'] = 0; //积分门槛
            $params['upgrade_extend_num'] = 0; //工会晋级人数
        }
        return [true, ''];
    }
}
