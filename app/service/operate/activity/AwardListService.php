<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Config\BbcRankAward;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankScoreConfig;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xsst\XsstActiveKingdeeRecord;
use Imee\Models\Xsst\XsstAwardKingdeeRecord;
use Imee\Models\Xsst\XsstTemplateAwardListOperate;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class AwardListService
{
    private $psServer;

    /** @var AwardListService $service */
    private static $service;

    public function __construct()
    {
        $this->psServer = new PsService();
    }

    public static function getInstance()
    {
        if (is_null(self::$service)) {
            self::$service = new AwardListService();
        }
        return self::$service;
    }

    /**
     * 将数据格式化输出
     * @param $data
     * @param $resource
     * @param int $objectIdSearch
     * @return array
     */
    public function formatData($data, $resource, int $objectIdSearch = 0): array
    {
        [$templateConfig, $rankButtonList, $rankScoreConfig, $rankButtonTag] = $resource;
        $visionType = array_get($templateConfig, 'vision_type');
        $tagListType = array_get($rankButtonTag, 'tag_list_type');

        $list = [];
        foreach ($data as $k => $rec) {
            $listId = array_get($rec, 'list_id', 0);
            $awardId = array_get($rec, 'award_id', 0);

            $objectId = array_get($rec, 'object_id', 0);
            $listIdStr = ($visionType == BbcTemplateConfig::VISION_TYPE_THREE) ? array_get($rec, 'extend_id', '-') . ' - ' . array_get($rec, 'extend_type', '-') : $listId;

            if ($objectIdSearch && $objectId != $objectIdSearch) {
                continue;
            }

            $list[$k] = $rec;
            $rankAward = BbcRankAward::findOne($awardId);
            // 日榜情况下榜单序号为list_id-轮次
            // 轮次 = list_id的开始时间 + cycle * 86400
            if ($tagListType == BbcRankButtonTag::TAG_LIST_TYPE_DAY) {
                $cycleTime = $rankButtonList['start_time'] + (($rec['cycle'] - 1) * 86400);
                $cycle = date('Y-m-d', $cycleTime);
                $listIdStr = $listId . '-' . $cycle;
            }
            $list[$k]['init_num'] = $rec['num'];//最初的金额
            $list[$k]['cycle_str'] = $this->getCycleStr((int)$rec['cycle'], $templateConfig);
            $list[$k]['list_id_str'] = $listIdStr;

            $list[$k]['rank_tag'] = isset($rankButtonList['rank_tag']) ? array_get(BbcRankButtonList::$rankTag, $rankButtonList['rank_tag'], '') : '';
            $list[$k]['score_config_type'] = isset($rankScoreConfig['type']) ? array_get(BbcRankScoreConfig::$types, $rankScoreConfig['type'], '') : '';
            $list[$k]['award_id_enum'] = isset($rankAward['rank_award_type']) ? array_get(BbcRankAward::$rankAwardType, $rankAward['rank_award_type'], $rankAward['rank_award_type']) : '';

            $list[$k]['award_id_rank'] = array_get($rankAward, 'rank', '\\') ?: '\\';
            // 当发奖对象是成员时特殊处理
            // 发奖方式字段为：按成员排名返奖
            // 榜单排名：团队排名—成员排名
            // 所获奖励对应名次：团队排名—成员排名
            if ($rankAward['award_object_type'] == BbcRankAward::AWARD_OBJECT_TYPE_EXTEND) {
                $list[$k]['rank'] = $rec['rank'] . '-' . $rec['extend_rank'];
                $list[$k]['award_id_rank'] = ($rankAward['rank'] ?: "\\") . '-' . $rec['extend_rank'];
                $list[$k]['award_id_enum'] = BbcRankAward::RANK_AWARD_TYPE_EXTEND_RANK;
            }
            $list[$k]['award_id_score'] = (array_get($rankAward, 'score_min', '') ?: '\\') . ' ~ ' . (array_get($rankAward, 'score_max', '') ?: '\\');
            $list[$k]['award_id_type'] = isset($rankAward['award_type']) ? (in_array($rankAward['award_type'], [1, 14]) ? '钻石' : $rankAward['award_type']) : '';
            $list[$k]['button_list_name'] = $rankButtonList['button_content'];
            $list[$k]['diamond_proportion'] = $rankAward['diamond_proportion'];
        }

        $list = array_values($list);

        // 取出数据后根据榜单id（list_id）, 轮次（cycle）, 排名（rank）进行排序展示
        $listIds = array_column($list, 'list_id');
        $cycles = array_column($list, 'cycle');
        $ranks = array_column($list, 'rank');
        array_multisort($listIds, SORT_ASC, $cycles, SORT_ASC, $ranks, SORT_ASC, $list);

        return $list;
    }

    private function getCycleStr($cycle, $templateConfig): string
    {
        $startTime = array_get($templateConfig, 'start_time', 0);
        $endTime = array_get($templateConfig, 'end_time', 0);
        $endTime > 0 && $endTime -= 86400 * 7;

        return date('Y-m-d H:i', $startTime) . ' - ' . date('Y-m-d H:i', $endTime);
    }

    /**
     * @param $params
     * @return array
     *
     * 区分周星榜  bbc_template_config表中的vision_type为3时是周星榜
     * 字段显示：
     * 统计周期(仅周星榜) 通过cycle来计算 第一轮cycle是1
     * 榜单序号：普通榜显示list_id 周星榜显示 礼物id(对应extend_id)-收送礼榜(对应extend_type)
     * 榜单类型：通过list_id在bbc_rank_button_list表中的rank_tag取，与后台设置时的对应关系一致
     * 积分统计方式：通过list_id在bbc_rank_score_config表中的type取，与后台设置时的对应关系一致
     * 榜单排名：rank字段
     * 发奖方式：通过award_id在bbc_rank_award表中的rank_award_type取，与后台设置时的对应关系一致
     * 所获奖励对应的名次：通过award_id在bbc_rank_award表中的rank取
     * 门槛：通过award_id在bbc_rank_award表中的score_min取 (如果rank_award_type是1表示 按积分区间返奖(按门槛) score_max score_min 分别为上下限)
     * 用户id：object_id字段
     * 榜单积分数：score字段
     * 奖励金额：num字段
     * 金额单位：通过award_id在bbc_rank_award表中的award_type取，（1对应钻石目前只有这一种）
     */
    public function getListAndTotal($params): array
    {
        $actId = array_get($params, 'act_id', 0);
        $cycle = array_get($params, 'cycle', 0);
        $objectIdSearch = array_get($params, 'object_id', 0);
        $listIdStrSearch = array_get($params, 'list_id_str', 0);

        if (empty($actId) || empty($cycle) || empty($listIdStrSearch)) {
            return [];
        }

        $templateConfig = BbcTemplateConfig::findOne($actId);
        $rankButtonList = BbcRankButtonList::findOne($listIdStrSearch);
        $rankScoreConfig = BbcRankScoreConfig::findOneByWhere([['button_list_id', '=', $listIdStrSearch], ['act_id', '=', $actId]]);
        $rankButtonTag = BbcRankButtonTag::findOne($rankButtonList['button_tag_id']);

        // 非周星榜总榜 cycle默认为0
        if ($this->isResetCycleFilter($templateConfig['vision_type'], $rankButtonTag['tag_list_type'])) {
            $cycle = 0;
        }
        // 榜单相关资源汇总
        $resource = [$templateConfig, $rankButtonList, $rankScoreConfig, $rankButtonTag];

        //不支持别的其他查找参数，说是数据量小，可以自己筛选
        $search = ['act_id' => $actId, 'cycle' => $cycle, 'list_id' => $listIdStrSearch];

        list($flg, $data) = $this->psServer->getDiamondList($search);
        $record = XsstAwardKingdeeRecord::findOneByWhere([['act_id', '=', $actId], ['list_id', 'in', [0, $listIdStrSearch]], ['cycle', '=', $cycle], ['is_delete', '=', XsstAwardKingdeeRecord::DELETE_NO]]);
        $enableEit = 1;
        if ($record && in_array(array_get($record, 'status'), [XsstAwardKingdeeRecord::STATUS_INIT, XsstAwardKingdeeRecord::STATUS_PASS, XsstAwardKingdeeRecord::STATUS_SUBMIT])) {
            $enableEit = 0;
        }

        if ($flg) {
            $list = $this->formatData($data, $resource, $objectIdSearch);
            foreach ($list as &$v) {
                $v['enable_eit'] = $enableEit;
            }
            return [true, ['data' => $list, 'total' => count($list)]];
        }
        return [false, $data];
    }

    /**
     * 是否重置周期筛选
     *
     * @param int $visionType
     * @param int $tagListType
     * @return bool
     */
    public function isResetCycleFilter(int $visionType, int $tagListType): bool
    {
        return $visionType != BbcTemplateConfig::VISION_TYPE_THREE && in_array($tagListType, [BbcRankButtonTag::TAG_LIST_TYPE_TOTAL, BbcRankButtonTag::TAG_LIST_TYPE_UPGRADE]);
    }

    public function getInfo($params): array
    {
        $page = (int)array_get($params, 'page', 1);
        $limit = (int)array_get($params, 'limit', 15);

        $filter = [];
        $awardId = intval(array_get($params, 'award_id', 0));
        $awardId && $filter[] = ['award_id', '=', $awardId];

        $data = XsstTemplateAwardListOperate::getListAndTotal($filter, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $rec['content'] = sprintf("奖励金额：%d -> %d \t 修改原因：%s", $rec['origin_num'], $rec['num'], $rec['reason']);
            $rec['operator'] = Helper::getAdminName($rec['operate_id']);
            $rec['dateline'] = date('Y-m-d H:i:s', $rec['dateline']);
        }
        return $data;
    }

    public function modify($params): array
    {
        $id = intval(array_get($params, 'id', 0));
        $actId = intval(array_get($params, 'act_id', 0));
        $listId = intval(array_get($params, 'list_id', 0));
        $originNum = intval(array_get($params, 'origin_num', 0));
        $awardId = intval(array_get($params, 'award_id', 0));
        $initNum = intval(array_get($params, 'init_num', 0));

        $cycle = intval(array_get($params, 'cycle', 0));

        $numModify = trim(array_get($params, 'num_modify', ''));
        $reason = trim(array_get($params, 'reason', ''));

        $record = XsstAwardKingdeeRecord::findOneByWhere([['act_id', '=', $actId], ['list_id', 'in', [0, $listId]], ['cycle', '=', $cycle], ['is_delete', '=', XsstAwardKingdeeRecord::DELETE_NO]]);
        if ($record && in_array(array_get($record, 'status'), [XsstAwardKingdeeRecord::STATUS_PASS, XsstAwardKingdeeRecord::STATUS_SUBMIT])) {
            return [false, '当前名单已提交，不可修改金额'];
        }

        if (!preg_match("/^[0-9]+$/", $numModify)) {
            return [false, '奖励金额应该为正整数'];
        }
        if (empty($reason)) {
            return [false, '修改原因必填'];
        }
        if ($numModify > $initNum) {
            return [false, '修改数值不得大于当前初始奖励金额' . $initNum];
        }

        $data = ['modify_id' => $id, 'num' => $numModify, 'award_id' => $awardId];

        list($flg, $rec) = $this->psServer->modifyDiamondList($data);
        if ($flg) {
            XsstTemplateAwardListOperate::add([
                'act_id'     => $actId,
                'award_id'   => $id,
                'init_num'   => $initNum,
                'origin_num' => $originNum,
                'num'        => $numModify,
                'reason'     => $reason,
                'operate_id' => array_get($params, 'admin_id', 0),
                'dateline'   => time(),
            ]);
        }

        return [$flg, $rec];
    }

    public function getSubmitStatus($params): array
    {
        $actId = array_get($params, 'act_id', 0);
        $cycle = array_get($params, 'cycle', 0);
        $listId = array_get($params, 'list_id_str', 0);

        $templateConfig = BbcTemplateConfig::findOne($actId);
        $rankButtonList = BbcRankButtonList::findOne($listId);
        $rankButtonTag = BbcRankButtonTag::findOne($rankButtonList['button_tag_id']);

        // 非周星榜总榜时cycles默认为0
        if ($this->isResetCycleFilter($templateConfig['vision_type'], $rankButtonTag['tag_list_type'])) {
            $cycle = 0;
        }

        $record = XsstAwardKingdeeRecord::findOneByWhere([['act_id', '=', $actId], ['list_id', 'in', [0, $listId]], ['cycle', '=', $cycle], ['is_delete', '=', XsstAwardKingdeeRecord::DELETE_NO]]);
        return [!empty($record), array_get($record, 'status')];
    }

    public function pubList($params): array
    {
        $actId = array_get($params, 'act_id', 0);
        $cycle = array_get($params, 'cycle');
        $listId = array_get($params, 'list_id_str');

        if (!is_numeric($actId)) {
            return [false, '请输入活动id'];
        }
        if (!is_numeric($cycle)) {
            return [false, '请输入统计周期'];
        }
        if (!is_numeric($listId)) {
            return [false, '请输入榜单id'];
        }

        $templateConfig = BbcTemplateConfig::findOne($actId);
        if (empty($templateConfig) || $templateConfig['status'] < 4) {
            return [false, '活动id错误'];
        }

        $rankAward = BbcRankAward::findOneByWhere([['act_id', '=', $actId], ['award_type', '=', BbcRankAward::AWARD_TYPE_DIAMOND]]);
        if (array_get($rankAward, 'award_type') != BbcRankAward::AWARD_TYPE_DIAMOND) {
            return [false, '只有奖励方式为钻石时才允许提交云之家'];
        }

        $search = ['act_id' => $actId, 'cycle' => $cycle, 'list_id' => $listId];
        list($flg, $data) = $this->psServer->getDiamondList($search);
        if (!$flg) {
            return [false, $data];
        }
        if (empty($data)) {
            return [false, '当前 统计周期 没有奖励数据，不允许提交云之家'];
        }

        $record = XsstAwardKingdeeRecord::findOneByWhere([['act_id', '=', $actId], ['list_id', 'in', [0, $listId]], ['cycle', '=', $cycle], ['is_delete', '=', XsstAwardKingdeeRecord::DELETE_NO]]);
        $status = array_get($record, 'status');
        $recordId = (int)array_get($record, 'id', 0);
        if ($status == XsstAwardKingdeeRecord::STATUS_FAIL) {
            //失败的，删除
            XsstAwardKingdeeRecord::updateByWhere([['id', '=', $recordId]], [
                'is_delete' => XsstAwardKingdeeRecord::DELETE_YES,
            ]);
        }
        if (in_array($status, [XsstAwardKingdeeRecord::STATUS_SUBMIT, XsstAwardKingdeeRecord::STATUS_PASS])) {
            return [false, '当前 统计周期 奖励名单已提交云之家，请勿重复提交'];
        }

        if ($status == XsstAwardKingdeeRecord::STATUS_FAIL || empty($record)) {
            list($flg, $recordId) = XsstAwardKingdeeRecord::add([
                'act_id'   => $actId,
                'cycle'    => $cycle,
                'list_id'  => $listId,
                'admin'    => Helper::getSystemUid(),
                'dateline' => time()
            ]);
            if (!$flg) {
                return [false, '数据库异常，请稍后尝试'];
            }
        }

        NsqClient::publish(NsqConstant::TOPIC_KING_ACTIVITY, [
            'cmd'  => 'submit_activity_money',
            'data' => ['act_id' => $actId, 'cycle' => $cycle, 'list_id' => $listId, 'id' => $recordId],
        ]);

        return [true, ''];
    }

    public function send(array $params): array
    {
        $actId = intval($params['act_id'] ?? 0);
        $cycle = intval($params['cycle'] ?? -1);
        $listId = intval($params['list_id_str'] ?? 0);

        if (empty($actId) || $cycle === -1 || empty($listId)) {
            return [false, '活动ID和轮次、榜单id必传'];
        }

        $config = BbcTemplateConfig::findOne($actId);
        $buttonList = BbcRankButtonList::findOne($listId);
        $buttonTag = BbcRankButtonTag::findOne($buttonList['button_tag_id']);

        // 非周星榜总榜时cycles默认为0
        if ($this->isResetCycleFilter($config['vision_type'], $buttonTag['tag_list_type'])) {
            $cycle = 0;
        }

        $data = [
            'act_id'  => $actId,
            'cycle'   => $cycle,
            'list_id' => $listId,
        ];

        $record = XsstAwardKingdeeRecord::findOneByWhere([
            ['act_id', '=', $data['act_id']],
            ['cycle', '=', $data['cycle']],
            ['list_id', 'in', [0, $data['list_id']]]
        ]);

        if (!$record) {
            list($flg, $recordId) = XsstAwardKingdeeRecord::add([
                'act_id'   => $actId,
                'cycle'    => $cycle,
                'list_id'  => $listId,
                'admin'    => Helper::getSystemUid(),
                'dateline' => time()
            ]);
            if (!$flg) {
                return [false, '数据库异常，请稍后尝试'];
            }
        } else {
            if ($record['status'] == XsstAwardKingdeeRecord::STATUS_PASS) {
                return [false, '当前名单人员已发放成功'];
            }
            $recordId = $record['id'];
        }

        list($rpcRes, $rpcMsg) = $this->psServer->actSendDiamondAward($data);

        if (!$rpcRes) {
            XsstActiveKingdeeRecord::deleteById($recordId);
            return [false, $rpcMsg];
        }

        list($rec, $msg) = XsstAwardKingdeeRecord::edit($recordId, [
            'status' => XsstAwardKingdeeRecord::STATUS_PASS
        ]);

        if (!$rec) {
            return [false, '奖励已下发成功，状态修改失败。失败原因：' . $msg];
        }

        return [true, ''];
    }

    public function getButtonList(array $params): array
    {
        $activity = BbcTemplateConfig::findOne($params['act_id']);

        $conditions = [
            ['act_id', '=', $params['act_id']],
            ['is_award', '=', BbcRankButtonList::IS_AWARD_YES]
        ];

        $masterButtonList = [];

        if ($activity['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            $masterButtonList = BbcRankButtonList::findOneByWhere(array_merge($conditions, [['rank_tag', '=', BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT]]));
            $conditions[] = ['rank_tag', 'IN', [BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT_SUB_ACCEPT, BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT_SUB_SEND]];
        }
        $buttonList = BbcRankButtonList::getListByWhere($conditions);

        $activityService = new ActivityService();
        $timeOffset = $activityService->setTimeOffsetNew($activity['time_offset'], false);
        if ($buttonList) {
            $buttonTag = BbcRankButtonTag::getBatchCommon(Helper::arrayFilter($buttonList, 'button_tag_id'), ['cycles', 'tag_list_type']);
            foreach ($buttonList as &$list) {
                $list['start_time'] = $list['start_time'] - $activityService->getTimeOffsetNew($timeOffset);
                $list['end_time'] = $list['end_time'] - $activityService->getTimeOffsetNew($timeOffset);
                if ($activity['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
                    $list['start_time'] = $masterButtonList['start_time'] - $activityService->getTimeOffsetNew($timeOffset);
                    $list['end_time'] = $masterButtonList['end_time'];
                }
                $list['vision_type'] = $activity['vision_type'];
                $list['tag_list_type'] = $buttonTag[$list['button_tag_id']]['tag_list_type'];
                $list['cycles'] = $list['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE ? $list['cycle_limit'] : $buttonTag[$list['button_tag_id']]['cycles'];
            }
        }

        return $buttonList ? array_column($buttonList, null, 'id') : [];
    }
}