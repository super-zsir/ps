<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankWhiteList;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Service\Helper;

class ButtonTagService
{
    public function getList(array $params, int $page, int $pageSize): array
    {
        $conditions = $this->getConditions($params);
        $data = BbcRankButtonTag::getListAndTotal($conditions, '*', 'id desc', $page, $pageSize);
        $config = BbcTemplateConfig::findOne($params['act_id']);
        $activityService = new ActivityService();
        foreach ($data['data'] as &$val) {
            $val['tag_list_type'] = strval($val['tag_list_type']);
            $val['top1_show'] = strval($val['top1_show']);
            $val['dateline'] = $val['dateline'] > 0 ? date('Y-m-d H:i', $val['dateline']) : ' - ';
            $tmpA = CmsUser::findFirst(intval($val['admin_id']));
            $val['admin'] = $tmpA ? $tmpA->user_name : ' - ';
            $val['rank_object'] = "{$val['rank_object']}";
            $val['time_offset'] = $activityService->setTimeOffsetNew($config['time_offset']);
        }

        return $data;
    }

    public function add(array $params): array
    {
        [$res, $msg] = (new ActivityService())->checkActivityStatus($params['act_id']);
        if (!$res) {
            return [false, $msg];
        }
        if ($msg['type'] == BbcTemplateConfig::TYPE_RANK) {
            list($cpRes, $cpMsg) = $this->cpVisionValid($params, $msg['vision_type']);
            if (!$cpRes) {
                return [false, $cpMsg];
            }
        }
        $limit = $msg['vision_type'] == BbcTemplateConfig::VISION_TYPE_FAMILY ? 7 : 3;
        $tags = BbcRankButtonTag::getListByWhere([
            ['act_id', '=', $params['act_id']],
            ['rank_object', 'NOT IN', [BbcRankButtonTag::RANK_OBJECT_GIFT, BbcRankButtonTag::RANK_OBJECT_WEEK_STAR]]
        ]);

        if ($tags && count($tags) >= $limit) {
            return [false, "活动{$params['act_id']}已经存在{$limit}个button"];
        }
        $tagType = array_column($tags, 'button_tag_type');
        if (in_array($params['button_tag_type'], $tagType)) {
            return [false, "活动已经存在按钮顺序为{$params['button_tag_type']}的buttontag"];
        }
        $tmpRows = array(
            'act_id'          => (int)$params['act_id'],
            'button_tag_type' => $params['button_tag_type'],
            'button_content'  => $params['button_content'],
            'rank_object'     => (int)$params['rank_object'],
            'admin_id'        => Helper::getSystemUid(),
            'cycles'          => $msg['cycles'],
            'dateline'        => time(),
            'tag_list_type'   => (int)($params['tag_list_type'] ?? 0),
            'top1_show'       => (int)($params['top1_show'] ?? 0),
        );

        return BbcRankButtonTag::add($tmpRows);
    }

    public function edit(array $params)
    {
        $activityService = new ActivityService();
        [$res, $msg] = $activityService->checkActivityStatus($params['act_id']);
        if (!$res) {
            return [false, $msg];
        }
        if ($msg['type'] == BbcTemplateConfig::TYPE_RANK) {
            list($cpRes, $cpMsg) = $this->cpVisionValid($params, $msg['vision_type']);
            if (!$cpRes) {
                return [false, $cpMsg];
            }
        }
        $info = BbcRankButtonTag::findOne($params['id']);
        if (!$info) {
            return [false, '当前tag不存在'];
        }
        $tags = BbcRankButtonTag::getListByWhere([
            ['id', '<>', $params['id']],
            ['act_id', '=', $params['act_id']],
            ['button_tag_type', '=', $params['button_tag_type']],
            ['rank_object', 'NOT IN', [BbcRankButtonTag::RANK_OBJECT_GIFT, BbcRankButtonTag::RANK_OBJECT_WEEK_STAR]]
        ]);
        if ($tags) {
            return [false, "活动已经存在按钮顺序为{$params['button_tag_type']}的buttontag"];
        }

        $cycles = ButtonListService::isDaysAndCycleList($info['tag_list_type']) ? $info['cycles'] : $msg['cycles'];
        $update = [
            'button_tag_type' => $params['button_tag_type'],
            'button_content'  => $params['button_content'],
            'rank_object'     => $params['rank_object'],
            'cycles'          => $cycles,
            'tag_list_type'   => (int)($params['tag_list_type'] ?? 0),
            'top1_show'       => (int)($params['top1_show'] ?? 0),
        ];

        $activityService->updateActivityInfo($params['act_id']);

        [$res, $msg] = BbcRankButtonTag::edit($params['id'], $update);
        if (!$res) {
            return [false, '编辑失败' . $msg];
        }

        $this->updateButtonList($params['id'], $update);
        // 修改tag_list_type为周期榜是需求额外更新下buttonlist下的cycle相关的数据
        if ($update['tag_list_type'] != $info['tag_list_type'] && $update['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE) {
            $this->updateButtonListCycle($params['id']);
        }
        return [true, ''];
    }

    public function updateButtonList($id, $data): void
    {
        switch ($data['rank_object']) {
            case BbcRankButtonTag::RANK_OBJECT_CP:
                $this->handleCpRankObject($id);
                break;
            default:
                break;
        }
    }

    /**
     * 日｜总榜更新为周期榜需要更新下面字段
     * cycle_days = 1
     * cycle_limit = (end_time - start_time / 86400) + 1
     * @param $buttonTagId
     * @return void
     */
    public function updateButtonListCycle($buttonTagId): void
    {
        $buttonList = BbcRankButtonList::findOneByWhere([
            ['button_tag_id', '=', $buttonTagId]
        ]);

        $update = [
            'cycle_days'  => 1,
            'cycle_limit' => min(ceil(($buttonList['end_time'] - $buttonList['start_time']) / 86400) + 1, 30),
        ];

        BbcRankButtonList::edit($buttonList['id'], $update);
        BbcRankButtonTag::edit($buttonTagId, ['cycles' => $update['cycle_limit']]);
    }

    private function handleCpRankObject($id): void
    {
        $buttonList = BbcRankButtonList::getListByWhere([
            ['button_tag_id', '=', $id]
        ]);

        if ($buttonList) {
            $update = [];
            // 处理奖池字段更新
            foreach ($buttonList as $list) {
                $update[$list['id']] = [
                    'has_prize_pool'        => 0,
                    'prize_pool_proportion' => 0,
                ];
            }

            BbcRankButtonList::updateBatch($update);
            (new ButtonListService())->deletePrizePoolReward(['has_prize_pool' => 0], array_keys($update));
        }
    }

    public function getConditions(array $params): array
    {
        return [
            ['act_id', '=', $params['act_id']],
            ['rank_object', 'NOT IN', [BbcRankButtonTag::RANK_OBJECT_GIFT, BbcRankButtonTag::RANK_OBJECT_WEEK_STAR]]
        ];
    }

    private function cpVisionValid(array $params, int $visionType): array
    {
        if (!isset($params['tag_list_type'])) {
            return [false, 'tag类型为必填项'];
        }
        if ($visionType == BbcTemplateConfig::VISION_TYPE_CP) {
            if (!isset($params['top1_show'])) {
                return [false, 'TOP1是否展示在头图为必填项'];
            }
        }
        return [true, ''];
    }

    public function info(int $id): array
    {
        $data = BbcRankButtonTag::findOne($id);
        if ($data) {
            $data['rank_object'] = strval($data['rank_object']);
            $data['top1_show'] = strval($data['top1_show']);
            $data['tag_list_type'] = strval($data['tag_list_type']);
        }

        return $data;
    }

    /**
     * 清除白名单
     * @param int $buttonTagId button_tag_id
     * @param int $type 白名单类型
     * @return array
     */
    public function clearWhiteByButtonTagId(int $buttonTagId, int $type): array
    {
        return BbcRankWhiteList::deleteByWhere([
            ['button_tag_id', '=', $buttonTagId],
            ['type', '=', $type]
        ]);
    }
}