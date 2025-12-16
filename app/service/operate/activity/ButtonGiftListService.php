<?php

namespace Imee\Service\Operate\Activity;

use Imee\Models\Config\BbcActGiftGroup;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcTemplateConfig;
use Phalcon\Di;
use Imee\Service\Helper;

class ButtonGiftListService
{
    public function getList(array $params, int $page, int $pageSize, string $order): array
    {
        $list = BbcRankButtonList::getListAndTotal([
            ['button_tag_id', '=', $params['button_tag_id'] ?? 0],
        ], '*', $order, $page, $pageSize);
        $config = BbcTemplateConfig::findOne($list['data'][0]['act_id'] ?? 0);
        $activityService = new ActivityService();
        foreach ($list['data'] as &$item) {
            $item['time_offset'] = $activityService->setTimeOffsetNew($config['time_offset']);
            $item['start_time'] && $item['start_time'] -= $activityService->getTimeOffsetNew($config['time_offset']);
            $item['end_time'] && $item['end_time'] -= $activityService->getTimeOffsetNew($config['time_offset']);
            $item['start_time'] = $item['start_time'] > 0 ? Helper::now($item['start_time']) : '';
            $item['end_time'] = $item['end_time'] > 0 ? Helper::now($item['end_time']) : '';
            $item['dateline'] = $item['dateline'] > 0 ? Helper::now($item['dateline']) : '';
            $item['admin'] = Helper::getAdminName($item['admin_id']);
            $item['button_desc'] = htmlspecialchars($item['button_desc']);
            $item['room_support'] = (string)$item['room_support'];
            $item['is_upgrade'] = (string)$item['is_upgrade'];
            $item['level'] = (string)$item['level'];
        }

        return $list;
    }

    public function add(array $params)
    {
        list($res, $msg) = $this->verify($params);
        if (!$res) {
            return [$res, $msg];
        }

        $data = $this->formatData($params);
        [$res, $msg] = BbcRankButtonList::add($data);
        if (!$res) {
            return [false, $msg];
        }

        $activityService = new ActivityService();
        [$minStartTime, $maxEndTime] = $activityService->getMinStartTimeAndEndTime($data['act_id'], $data['start_time'], $data['end_time']);
        return $activityService->updateTemplateTime($minStartTime, $maxEndTime, $data['act_id']);
    }

    public function edit(array $params)
    {
        list($res, $msg) = $this->verify($params);
        if (!$res) {
            return [$res, $msg];
        }

        $data = $this->formatData($params);
        [$res, $msg] = BbcRankButtonList::edit($data['id'], $data);
        if (!$res) {
            return [false, $msg];
        }

        $activityService = new ActivityService();
        [$minStartTime, $maxEndTime] = $activityService->getMinStartTimeAndEndTime($data['act_id'], $data['start_time'], $data['end_time']);
        return $activityService->updateTemplateTime($minStartTime, $maxEndTime, $data['act_id']);
    }

    private function verify(array &$params): array
    {
        if (isset($params['id']) && !empty($params['id'])) {
            $list = BbcRankButtonList::findOne($params['id']);
            if (empty($list)) {
                return [false, '当前礼物榜单不存在'];
            }
            $params['button_tag_id'] = $list['button_tag_id'];
        }
        $tag = BbcRankButtonTag::findOne($params['button_tag_id']);
        if (!$tag) {
            return [false, 'tag不存在'];
        }

        $act = BbcTemplateConfig::findOne($tag['act_id']);
        if (empty($act)) {
            return [false, '活动模版不存在'];
        }
        $activityService = new ActivityService();
        if ($activityService->validActiveStatus($act['status'])) {
            return [false, '当前活动已上线，不可进行此操作'];
        }

        if ($act['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            $params['rank_list_num'] = 0;
            $params['button_content'] = '';
            $params['is_upgrade'] = 0;
            $params['level'] = 1;
            $params['p_level'] = '';
            $params['button_desc'] = '';
        }

        $params['act_id'] = $act['id'];
        $params['button_tag_id'] = $tag['id'];
        $startTime = strtotime($params['start_time'] ?? 0);
        $endTime = strtotime($params['end_time'] ?? 0);
        $startTime && $params['start_time'] = $startTime + $activityService->getTimeOffsetNew($act['time_offset']);
        $endTime && $params['end_time'] = $endTime + $activityService->getTimeOffsetNew($act['time_offset']);

        $params['award_time'] = 0;
        $params['rank_tag'] = BbcRankButtonList::RANK_TAG_GIFT_GROUP;
        if ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            $params['end_time'] = $params['start_time'] + 7 * 86400 * $act['cycles'];
            $params['rank_tag'] = BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT;
            $params['award_time'] = $params['start_time'];
        }
        if ($params['start_time'] > $params['end_time']) {
            return [false, '榜单开始时间不能大于结束时间'];
        }
        $info = BbcRankButtonList::getInfoByTagAndActAndLevel($tag['id'], $tag['act_id'], $params['level'], $params['id'] ?? 0);
        if ($info) {
            return [false, '每个轮次仅可创建一条记录'];
        }
        $level = (int) ($params['level'] ?? 1);
        $params['is_upgrade'] = (int) ($params['is_upgrade'] ?? 0);
        // 设置承接轮次
        // 1）“选择是否晋级赛”为否时，承接轮次为空
        // 2）“选择是否晋级赛”为是，且轮次=1时，承接轮次为空
        // 3）“选择是否晋级赛”为是，且轮次≠1时，承接轮次=轮次-1.（举例：是晋级赛，轮次为2，那么承接轮次赋值1）
        $params['p_level'] = $this->setPLevel($params['is_upgrade'], $level);

        if ($params['is_upgrade']) {
            [$res, $msg] = $this->checkLevel($params['button_tag_id'], $params['level'], $params['start_time'],$params['end_time'], $params['id'] ?? 0);
            if (!$res) {
                return [false, $msg];
            }
        }

        //验证  晋级赛字段
        list($isUpdateFlg,$msg) =  $this->validateIsUpdate($params) ;
        if(!$isUpdateFlg){
            return [$isUpdateFlg,$msg];
        }

        $params['rank_list_num'] = BbcActGiftGroup::getCount([
            ['button_tag_id', '=', $tag['id']]
        ]);

        return [true, ''];
    }

    private function formatData(array $params): array
    {
        $data = [
            'act_id'             => $params['act_id'],
            'button_tag_id'      => $params['button_tag_id'],
            'rank_list_num'      => $params['rank_list_num'],
            'button_content'     => $params['button_content'] ?? '',
            'is_upgrade'         => $params['is_upgrade'],
            'room_support'       => (int)($params['room_support'] ?? 0),
            'rank_tag'           => $params['rank_tag'],
            'level'              => $params['level'],
            'p_level'            => $params['p_level'],
            'button_desc'        => $params['button_desc'] ?? '',
            'start_time'         => $params['start_time'],
            'end_time'           => $params['end_time'],
            'award_time'         => $params['award_time'],
            'upgrade_type'       => intval($params['upgrade_type']),
            'upgrade_num'        => empty($params['upgrade_num']) ? 0 : intval($params['upgrade_num']),
            'upgrade_score'      => empty($params['upgrade_score']) ? 0 : intval($params['upgrade_score']),
            'upgrade_extend_num' => empty($params['upgrade_extend_num']) ? 0 : intval($params['upgrade_extend_num']),
            'is_award'           => $params['rank_tag'] == BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT ? $this->getIsAward($params['act_id']) : 0,
            'dateline'           => time(),
            'admin_id'           => Helper::getSystemUid()
        ];

        if (isset($params['id']) && !empty($params['id'])) {
            $data['id'] = $params['id'];
        }

        return $data;
    }

    public static function setPLevel($isUpgrade, $level)
    {
        if (empty($isUpgrade) || $level == 1) {
            return '';
        }

        return $level - 1;
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

    public function getIsAward($act_id)
    {
        $list = BbcRankButtonList::findOneByWhere([
            ['act_id', '=', $act_id],
            ['is_award', '=', 1],
            ['rank_tag', '<>', BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT]
        ]);

        return empty($list) ? 0 : 1;
    }

    public function validateIsUpdate(&$params): array
    {
        if ($params['is_upgrade'] == BbcRankButtonList::IS_UPGRADE_YES) {
            if (!is_numeric($params['upgrade_type'])) {
                return [false, "请选择晋级方式"];
            }
            switch (intval($params['upgrade_type'])) {
                case  BbcRankButtonList::UPGRADE_TYPE_ONE:
                    if (empty($params['upgrade_num'])) {
                        return [false, "晋级方式为【名次】，请输入名次"];
                    }
                    $params['upgrade_score'] = 0;
                    break;
                case  BbcRankButtonList::UPGRADE_TYPE_TWO:
                    if (empty($params['upgrade_score'])) {
                        return [false, "晋级方式为【积分门槛】，请输入积分门槛"];
                    }
                    $params['upgrade_num'] = 0;
                    break;
                case  BbcRankButtonList::UPGRADE_TYPE_THREE:
                    if (empty($params['upgrade_score']) || empty($params['upgrade_num'])) {
                        return [false, "晋级方式为【名次+积分门槛】，请输入名次与积分门槛"];
                    }
                    break;
                case  BbcRankButtonList::UPGRADE_TYPE_FOUR:
                    if (empty($params['upgrade_score']) && empty($params['upgrade_num'])) {
                        return [false, "晋级方式为【名次或积分门槛】，请输入名次或积分门槛"];
                    }
                    break;
            }
        } else {
            $params['upgrade_type'] = 0;
            $params['upgrade_num'] = 0;//名次
            $params['upgrade_score'] = 0;//积分门槛
            $params['upgrade_extend_num'] = 0;//工会晋级人数
        }
        return [true, ''];
    }

    public function delete(int $id)
    {
        return BbcRankButtonList::deleteById($id);
    }

    public function info(int $id)
    {
        $list = BbcRankButtonList::findOne($id);
        if ($list) {
            $act = BbcTemplateConfig::findOne($list['act_id']);
            $activityService = new ActivityService();
            // 去除time_offset时间
            $list['start_time'] = $list['start_time'] - $activityService->getTimeOffsetNew($act['time_offset']);
            $list['end_time'] = $list['end_time'] - $activityService->getTimeOffsetNew($act['time_offset']);
            $list['start_time'] = $list['start_time'] > 0 ? Helper::now($list['start_time']) : '';
            $list['end_time'] = $list['end_time'] > 0 ? Helper::now($list['end_time']) : '';
            $list['room_support'] = (string)$list['room_support'];
            $list['is_upgrade'] = (string)$list['is_upgrade'];
            $list['is_award'] = (string)$list['is_award'];
            $list['award_time'] = $list['award_time'] > 0 ? Helper::now($list['award_time']) : '';
            $list['level'] = (string)$list['level'];
        }

        return $list;
    }
}