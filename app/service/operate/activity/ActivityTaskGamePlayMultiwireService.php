<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Config\BaseModel;
use Imee\Models\Config\BbcRankAward;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankScoreConfigNew;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsActRankAwardUser;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\StatusService;
use Phalcon\Di;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;

class ActivityTaskGamePlayMultiwireService extends ActivityService
{
    const PAGE_URL = '%s/task-template/?aid=%d&clientScreenMode=1#/MultipleTask';

    protected function onAfterList($list)
    {
        $adminList = CmsUser::getUserNameList(array_merge(Helper::arrayFilter($list, 'admin_id'), Helper::arrayFilter($list, 'publisher_id')));
        foreach ($list as &$item) {
            $item['is_pub'] = $this->setIsPublish($item['status']);
            $item['is_diamond'] = $this->isDiamondAward($item['id']);
            $item['time_offset'] = $this->setTimeOffset($item['time_offset'], self::TYPE_TIME_SUBTRACT);
            $starTime = $this->setActivityTime($item['start_time'], $item['time_offset'], 0, self::TYPE_TIME_SUBTRACT);
            $endTime = $this->setActivityTime($item['end_time'], $item['time_offset'], $item['data_period'], self::TYPE_TIME_SUBTRACT);
            $item['time_offset'] = $this->formatTimeOffset($item['time_offset']);
            $item['dateline'] = Helper::now($item['dateline']);
            $item['activity_time'] = Helper::now($starTime) . '-<br />' . Helper::now($endTime);
            $item['audit_status'] = $this->getAuditStatus($item['status']);
            $item['audit_status_text'] = $this->setAuditStatusText($item['audit_status']);
            $item['status'] = $this->getStatus($item['status'], $item['start_time'], $item['end_time'] - $item['data_period'] * 86400);
            $item['status_text'] = $this->setStatusText($item['status']);
            $item['bigarea_id'] = XsBigarea::formatBigAreaName($item['bigarea_id']);
            $admin = $adminList[$item['admin_id']] ?? '';
            $publisher = $adminList[$item['publisher_id']] ?? '';
            $item['tips'] = "你确定发布【{$admin}】创建的活动【{$item['title']}】吗？";
            $item['admin_id'] = $item['admin_id'] . '-' . $admin;
            $item['publisher'] = $item['publisher_id'] > 0 ? $item['publisher_id'] . '-' . $publisher : '';
            $item['page_url'] = $this->getPageUrl($item['id'], $item['vision_type'], $item['page_url']);
            $item['page_url'] = [
                'title'        => $item['page_url'],
                'value'        => $item['page_url'],
                'type'         => 'url',
                'url'          => $item['page_url'],
                'resourceType' => 'static'
            ];
        }

        return $list;
    }

    public function info(int $id): array
    {
        $template = BbcTemplateConfig::findOne($id);
        if (!$template) {
            return [];
        }
        $timeOffset = $this->setTimeOffset($template['time_offset'], self::TYPE_TIME_SUBTRACT);
        $visionContentJson = @json_decode($template['vision_content_json'], true);
        if ($visionContentJson) {
            foreach ($visionContentJson as $key => $value) {
                if (str_contains($key, 'img_vc')) {
                    $visionContentJson[$key . '_all'] = Helper::getHeadUrl($value);
                }
            }
        }
        return [
            'id'                  => $template['id'],
            'start_time'          => Helper::now($this->setActivityTime($template['start_time'], $timeOffset, 0, self::TYPE_TIME_SUBTRACT)),
            'end_time'            => Helper::now($this->setActivityTime($template['end_time'], $timeOffset, $template['data_period'], self::TYPE_TIME_SUBTRACT)),
            'language'            => $template['language'],
            'title'               => $template['title'],
            'data_period'         => $template['data_period'],
            'time_offset'         => $timeOffset,
            'bigarea_id'          => explode('|', $template['bigarea_id']),
            'type'                => $template['type'],
            'audit_status'        => $this->getAuditStatus($template['status']),
            'status'              => $this->getStatus($template['status'], $template['start_time'], $template['end_time'] - $template['data_period']),
            'has_be_related'      => (string)$template['has_be_related'],
            'vision_content_json' => $visionContentJson,
            'award_content_json'  => json_decode($template['award_content_json'], true),
            'admin'               => Helper::getAdminName($template['admin_id'])
        ];
    }

    public function create(array $params): array
    {
        $data = $this->verify($params);
        list($res, $id) = BbcTemplateConfig::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }

        // 更新活动链接
        list($editRes, $msg) = BbcTemplateConfig::edit($id, [
            'page_url' => $this->setPageUrl(BbcTemplateConfig::TYPE_MULTI_TASK, $id),
        ]);

        if (!$editRes) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $data = $this->verify($params, true);
        list($res, $msg) = BbcTemplateConfig::edit($params['id'], $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $this->updateListTime($params['id'], $data);
        return ['id' => $params['id'], 'after_json' => $data];
    }

    private function updateListTime($id, $data): void
    {
        $endTime = $this->setActivityTime($data['end_time'], 8, $data['data_period'], self::TYPE_TIME_SUBTRACT);

        $update = [
            'start_time' => $data['start_time'],
            'end_time'   => $endTime
        ];
        list($res, $msg) = BbcRankButtonList::updateByWhere([['act_id', '=', $id]], $update);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function getTask(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id不能为空');
        }

        $template = BbcTemplateConfig::findOne($id);
        if (empty($template)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }

        $auditStatus = $this->getAuditStatus($template['status']);
        $status = $this->getStatus($template['status'], $template['start_time'], $template['end_time'] - $template['data_period']);

        $tagList = BbcRankButtonTag::getListByWhere([
            ['act_id', '=', $id]
        ], 'id, button_tag_type, button_content, tag_list_type, rank_object, cycle_days', 'id asc');

        $buttonListOne = BbcRankButtonList::findOneByWhere([
            ['act_id', '=', $id]
        ]);

        $data = [
            'status'                    => $status,
            'audit_status'              => $auditStatus,
            'tab_num'                   => count($tagList),
            'rank_object'               => !empty($tagList) ? ($tagList[0]['rank_object'] ?? '') : '',
            'divide_track'              => (string)($buttonListOne['divide_track'] ?? ''),
            'divide_type'               => "1",
            'broker_distance_start_day' => $buttonListOne['broker_distance_start_day'] ?? '',
        ];

        if (empty($tagList)) {
            return $data;
        }

        $tabList = [];
        foreach ($tagList as $tag) {
            $taskList = [];
            $list = BbcRankButtonList::getListByWhere([
                ['act_id', '=', $id],
                ['button_tag_id', '=', $tag['id']]
            ], 'id, is_only_cross_room_pk');
            if ($list) {
                foreach ($list as $key => $item) {
                    $scoreList = BbcRankScoreConfigNew::getListByWhere([
                        ['act_id', '=', $id],
                        ['list_id', '=', $item['id']]
                    ], '*', 'id asc');
                    $sourceType = $this->getScoreType($scoreList[0]['scope'], $scoreList[0]['type']);
                    $scope = $scoreList[0]['scope'] ?? '';
                    if ($sourceType == BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY) {
                        $scope = $scoreList[0]['extend_id'] . '_' . $scoreList[0]['extend_sub_id'];
                    } else {
                        $scope = array_map('intval', explode(',', $scope));
                    }
                    $roomScope = $scoreList[0]['room_scope'] ?? '';
                    $roomScope = $roomScope !== '' ? explode(',', $roomScope) : [];
                    $roomScope = array_map('strval', $roomScope);
                    $awardList = BbcRankAward::getListByWhere([
                        ['act_id', '=', $id],
                        ['button_list_id', '=', $item['id']]
                    ]);
                    if ($scoreList) {
                        $giftId = Helper::arrayFilter($scoreList, 'gift_id');
                        $pkValidExtend = @json_decode($scoreList[0]['pk_valid_extend'] ?? '', true);
                        $taskList[$key] = [
                            'list_id'               => $item['id'],
                            'source_type'           => (string) $sourceType,
                            'type'                  => $scoreList[0]['type'],
                            'comment'               => $scoreList[0]['comment'],
                            'score_min'             => $awardList[0]['score_min'],
                            'scope'                 => $scope,
                            'gift_id'               => $giftId ? implode("\n", $giftId) : '',
                            'is_only_cross_room_pk' => (string) $scoreList[0]['is_only_cross_room_pk'],
                            'room_scope'            => $roomScope,
                            'pk_valid_type'         => isset($pkValidExtend['pk_valid_type']) ? (string)$pkValidExtend['pk_valid_type'] : '',
                            'pk_time'               => $pkValidExtend['pk_time'] ?? '',
                            'pk_gift'               => $pkValidExtend['pk_gift'] ?? '',
                        ];
                    }
                    $rewardList = [];
                    if ($awardList) {
                        foreach ($awardList as $award) {
                            $rewardList[] = $this->getAwardData($award);
                        }
                        $taskList[$key]['reward_list'] = $rewardList;
                    }
                }
            }
            $tabList[] = [
                'tag_id'         => $tag['id'],
                'tag_list_type'  => (string)$tag['tag_list_type'],
                'button_content' => $tag['button_content'],
                'cycle_days'     => $tag['cycle_days'],
                'task_list'      => $taskList
            ];
        }
        $data['tab_list'] = $tabList;

        return $data;
    }

    private function getAwardData($award)
    {
        $extend = @json_decode($award['award_extend_info'], true);

        $data = [
            'award_id'  => $award['id'],
            'type'      => (string)$award['award_type'],
            'cid'       => (string)$award['cid'],
            'exp_days'  => $award['exp_days'],
            'days'      => $award['exp_days'],
            'vip_level' => (string)$award['cid'],
            'vip_days'  => (string)$award['num'],
            'give_type' => strval($extend['extend_type'] ?? $award['can_transfer'] ?? 0),
            'content'   => $extend['content'] ?? '',
            'num'       => $award['num'],
            'effective_hours' => $extend['days'] ?? $award['exp_days'] ?? 0,
        ];
        switch ($award['award_type']) {
            case BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON:
            case BbcRankAward::AWARD_TYPE_MEDAL:
            case BbcRankAward::AWARD_TYPE_ROOM_SKIN:
            case BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND:
                $data['days'] = $award['num'];
                break;
            case BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD:
            case BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING:
            case BbcRankAward::AWARD_TYPE_ITEM_CARD:
            case BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD:
                $data['days'] = $award['num'];
                $data['num'] = $extend['send_num'] ?? '';
                break;
            case BbcRankAward::AWARD_TYPE_VIP:
                $data['num'] = $extend['send_num'] ?? '';
            case BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD:
                $extendInfo = $extend['open_screen_card_extend'] ?? [];
                $data['card_type'] = strval($extendInfo['card_type'] ?? '');
                $data['expire_time'] = $award['exp_days'] ? Helper::now($award['exp_days']) : '';
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_BG_CARD:
                $extendInfo = $extend['room_bg_card_extend'] ?? [];
                $data['card_type'] = strval($extendInfo['card_type'] ?? '');
                break;
            case BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD:
                $data['card_type'] = strval($extendInfo['card_type'] ?? '');
                $data['expire_time'] = $award['exp_days'] ? Helper::now($award['exp_days']) : '';
                $data['effective_days'] = $extend['days'] ?? '';
                break;    
        }
        return $data;
    }

    public function setTask(array $params): array
    {
        $id = $params['id'];
        [$startTime, $endTime] = $this->getTemplateTime($id);
        
        $this->validTask($params, $startTime, $endTime);

        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            $now = time();
            $adminId = $params['admin_id'];
            $rankObject = intval($params['rank_object'] ?? 0);
            $divideTrack = intval($params['divide_track'] ?? -1);
            $divideType = intval($params['divide_type'] ?? 0);
            $brokerDistanceStartDay = intval($params['broker_distance_start_day'] ?? 0);
            $divideObject = 0;

            if ($rankObject != BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS) {
                $divideTrack = $divideType = $brokerDistanceStartDay = 0;
            } else {
                if ($divideTrack == BbcRankButtonList::DIVIDE_TRACK_NO) {
                    $divideType = $brokerDistanceStartDay = 0;
                }
                $divideObject = BbcRankButtonList::DIVIDE_OBJECT_BROKER_USER;
            }

            $tabList = $params['tab_list'];
            $countTabList = count($tabList);
            
            // 在添加新数据之前，先获取旧的ID列表
            [$oldTagIdArr, $oldListIdArr, $oldAwardIdArr] = $this->getRankId($id);
            
            $newTagIdArr = $newListIdArr = $newAwardIdArr = [];
            foreach ($tabList as $tabKey => $tab) {
                list($tag, $tagId) = BbcRankButtonTag::addOrEdit($tab['tag_id'] ?? 0, [
                    'act_id'          => $id,
                    'tag_list_type'   => $tab['tag_list_type'],
                    'button_content'  => $tab['button_content'],
                    'cycle_days'      => $tab['cycle_days'] ?? 0,
                    'button_tag_type' => $this->setButtonTagType($tabKey, $countTabList),
                    'admin_id'        => $adminId,
                    'dateline'        => $now,
                    'rank_object'     => $rankObject,
                ]);
                if (!$tag) {
                    throw new \Exception(sprintf('ButtonTag配置失败, 原因：%s', $tagId));
                }
                $newTagIdArr[] = $tagId;
                foreach ($tab['task_list'] as $task) {
                    list($list, $listId) = BbcRankButtonList::addOrEdit($task['list_id'] ?? 0, [
                        'act_id'                    => $id,
                        'button_tag_id'             => $tagId,
                        'button_content'            => $tab['button_content'],
                        'upgrade_extend_num'        => 0,
                        'start_time'                => $startTime,
                        'end_time'                  => $endTime,
                        'cycle_days'                => $tab['cycle_days'] ?? 0,
                        'admin_id'                  => $adminId,
                        'divide_track'              => $divideTrack,
                        'divide_type'               => $divideType,
                        'divide_object'             => $divideObject,
                        'broker_distance_start_day' => $brokerDistanceStartDay,
                        'dateline'                  => $now,
                    ]);
                    if (!$list) {
                        throw new \Exception(sprintf('ButtonList配置失败, 原因：%s', $listId));
                    }
                    $newListIdArr[] = $listId;
                    $baseData = [
                        'act_id'         => $id,
                        'button_list_id' => $listId,
                        'admin_id'       => $adminId,
                        'dateline'       => $now,
                    ];
                    foreach ($task['reward_list'] as $rewardItem) {
                        $data = $this->setRewardData($rewardItem);
                        list($reward, $rid) = BbcRankAward::addOrEdit(
                            $rewardItem['award_id'] ?? 0,
                            array_merge(
                                $baseData,
                                $data,
                                ['score_min' => $task['score_min']]
                            )
                        );
                        if (!$reward) {
                            throw new \Exception(sprintf('Reward配置失败, 原因：%s', $rid));
                        }
                        $newAwardIdArr[] = $rid;
                    }
                    
                    // 为每个task单独处理Score配置的删除和添加
                    $currentTaskScoreConfig = [];
                    $this->setScoreConfigData($task, $currentTaskScoreConfig, $baseData, $rankObject);
                    
                    if ($currentTaskScoreConfig) {
                        // 删除该list_id下的所有Score配置
                        list($scoreDel, $scoreDelMsg, $_) = BbcRankScoreConfigNew::deleteByWhere([
                            ['act_id', '=', $baseData['act_id']],
                            ['list_id', '=', $baseData['button_list_id']],
                        ]);
                        if (!$scoreDel) {
                            throw new \Exception(sprintf('删除Score配置失败, 原因：%s', $scoreDelMsg));
                        }
                        
                        // 添加新的Score配置
                        list($score, $scoreMsg, $_) = BbcRankScoreConfigNew::addBatch($currentTaskScoreConfig);
                        if (!$score) {
                            throw new \Exception(sprintf('Score配置失败, 原因：%s', $scoreMsg));
                        }
                    }
                }
            }
            
            // 在循环结束后删除不需要的数据
            $this->deleteRank($oldTagIdArr, $oldListIdArr, $oldAwardIdArr, $newTagIdArr, $newListIdArr, $newAwardIdArr);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ApiException(ApiException::MSG_ERROR, $e->getMessage());
        }

        return ['id' => $id, 'after_json' => $params];
    }

    private function deleteRank($oldTagIdArr, $oldListIdArr, $oldAwardIdArr, $newTagIdArr, $newListIdArr, $newAwardIdArr)
    {
        // 计算需要删除的ID：旧ID中不在新ID列表中的
        $diffTagIdArr = array_values(array_diff($oldTagIdArr, $newTagIdArr));
        $diffListIdArr = array_values(array_diff($oldListIdArr, $newListIdArr));
        $diffAwardIdArr = array_values(array_diff($oldAwardIdArr, $newAwardIdArr));

        // 执行删除操作
        $diffTagIdArr && BbcRankButtonTag::deleteBatch($diffTagIdArr);
        $diffListIdArr && BbcRankButtonList::deleteBatch($diffListIdArr);
        $diffAwardIdArr && BbcRankAward::deleteBatch($diffAwardIdArr);
    }

    private function getRankId($id): array
    {
        $conditions = [
            ['act_id', '=', $id]
        ];

        $list = BbcRankButtonList::useMaster()::getListByWhere($conditions, 'id');
        $award = BbcRankAward::useMaster()::getListByWhere($conditions, 'id');
        $tag = BbcRankButtonTag::useMaster()::getListByWhere($conditions, 'id');

        return [
            Helper::arrayFilter($tag, 'id'),
            Helper::arrayFilter($list, 'id'),
            Helper::arrayFilter($award, 'id'),
        ];
    }

    private function setScoreConfigData($task, &$scoreConfig, $baseData, $rankObject)
    {
        $giftId = trim($task['gift_id'] ?? '');
        $comment = $task['comment'] ?? '';
        $scope = $task['scope'] ?? '';
        $roomScope = $task['room_scope'] ?? '';
        $pkValidType = intval($task['pk_valid_type'] ?? 0);
        $pkTime = intval($task['pk_time'] ?? 0);
        $pkGift = intval($task['pk_gift'] ?? 0);
        
        if ($task['type'] != BbcRankScoreConfigNew::SCORE_TYPE_ROOM_COMMENT_NUM) {
            $comment = '';
        }

        if (in_array($task['type'], [BbcRankScoreConfigNew::SCORE_TYPE_ROOM_STAY_TIME, BbcRankScoreConfigNew::SCORE_TYPE_ROOM_COMMENT_NUM])) {
            $scope = '1,2';
        }

        if ($task['source_type'] == BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY) {
            [$actId, $listId] = explode('_', $scope);
            $scope = '';
        } else {
            $actId = 0;
            $listId = 0;
        }

        if ($rankObject != BbcRankButtonTag::RANK_OBJECT_ROOM) {
            $roomScope = '';
        }

        $pkValidExtend = '';
        if (in_array($task['type'], [BbcRankScoreConfigNew::SCORE_TYPE_PK_WIN, BbcRankScoreConfigNew::SCORE_TYPE_PK_END])) {
            $pkValidExtendData = [
                'pk_valid_type' => $pkValidType,
            ];
            if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                $pkValidExtendData['pk_time'] = $pkTime;
            }
            if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                $pkValidExtendData['pk_gift'] = $pkGift;
            }
            $pkValidExtend = json_encode($pkValidExtendData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $baseScoreConfig = [
            'type'                  => $task['type'],
            'comment'               => $comment,
            'scope'                 => $scope,
            'score'                 => 1,
            'gift_id'               => 0,
            'act_id'                => $baseData['act_id'],
            'list_id'               => $baseData['button_list_id'],
            'admin_id'              => $baseData['admin_id'],
            'dateline'              => $baseData['dateline'],
            'is_only_cross_room_pk' => $task['is_only_cross_room_pk'] ?? 0,
            'extend_id'             => $actId ?? 0,
            'extend_sub_id'         => $listId ?? 0,
            'room_scope'            => $rankObject != BbcRankButtonTag::RANK_OBJECT_ROOM ? '' : $roomScope,
            'pk_valid_extend'       => $pkValidExtend,
        ];

        if (in_array($task['type'], BbcRankScoreConfigNew::$giftScoreTypeMap) && $giftId) {
            $giftId = explode("\n", $giftId);
            foreach ($giftId as $gift) {
                $baseScoreConfig['gift_id'] = $gift;
                $scoreConfig[] = $baseScoreConfig;
            }
        } else {
            $scoreConfig[] = $baseScoreConfig;
        }

    }

    public function formatRewardData($reward): array
    {
        $cid = intval($reward['cid'] ?? 0);
        $awardType = intval($reward['type'] ?? 0);
        $num = intval($reward['num'] ?? 0);
        $days = intval($reward['days'] ?? 0);
        $expDays = intval($reward['exp_days'] ?? 0);
        $giveType = intval($reward['give_type'] ?? -1);
        $content = trim($reward['content'] ?? '');
        $vipLevel = intval($reward['vip_level'] ?? 0);
        $vipDays = intval($reward['vip_days'] ?? 0);
        $expireTime = trim($reward['expire_time'] ?? '');
        $effectiveHours = intval($reward['effective_hours'] ?? 0);
        $cardType = intval($reward['card_type'] ?? -1);
        $effectiveDays = intval($reward['effective_days'] ?? 0);

        // vip 奖励 直接下发 数量默认为0
        if ($awardType == BbcRankAward::AWARD_TYPE_VIP && $giveType == BbcRankAward::GIVE_TYPE_AUTO_EFFECT) {
            $num = 1;
        }

        return [
            'cid'        => $cid,
            'award_type' => $awardType,
            'num'        => $num,
            'days'       => $days,
            'exp_days'   => $expDays,
            'give_type'  => $giveType,
            'content'    => $content,
            'vip_level'  => $vipLevel,
            'vip_days'   => $vipDays,
            'expire_time' => $expireTime,
            'effective_hours' => $effectiveHours,
            'card_type' => $cardType,
            'effective_days' => $effectiveDays,
        ];
    }

    private function setRewardData($reward): array
    {
        $reward = $this->formatRewardData($reward);

        $data = [
            'rank'              => 1,
            'award_type'        => $reward['award_type'],
            'cid'               => 0,
            'exp_days'          => 0,
            'can_transfer'      => 0,
            'award_extend_info' => '',
            'num'               => 0,
        ];

        switch ($data['award_type']) {
            case BbcRankAward::AWARD_TYPE_DIAMOND:
                $data['num'] = $reward['num'];
                break;
            case BbcRankAward::AWARD_TYPE_COMMODITY:
                $data['num'] = $reward['num'];
                $data['cid'] = $reward['cid'];
                $data['exp_days'] = $reward['exp_days'];
                break;
            case BbcRankAward::AWARD_TYPE_PACK:
                $data['num'] = $reward['num'];
                $data['cid'] = $reward['cid'];
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND:
            case BbcRankAward::AWARD_TYPE_ROOM_SKIN:
            case BbcRankAward::AWARD_TYPE_MEDAL:
                $data['num'] = $reward['days'];
                $data['cid'] = $reward['cid'];
                break;
            case BbcRankAward::AWARD_TYPE_VIP:
                $data['num'] = $reward['vip_days'];
                $data['cid'] = $reward['vip_level'];
                $data['award_extend_info'] = json_encode(['extend_type' => $reward['give_type'], 'send_num' => $reward['num']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_BG_CARD:
                $data['num'] = $reward['num'];
                $data['exp_days'] = $reward['days'];
                $data['can_transfer'] = $reward['give_type'];
                $reward['card_type'] > -1 && $data['award_extend_info'] = json_encode(['room_bg_card_extend' => ['card_type' => $reward['card_type']]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD:
            case BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING:
            case BbcRankAward::AWARD_TYPE_ITEM_CARD:
            case BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD:
                $data['num'] = $reward['days'];
                $data['cid'] = $reward['cid'];
                $data['exp_days'] = $reward['exp_days'];
                $data['can_transfer'] = $reward['give_type'];
                $data['award_extend_info'] = json_encode(['send_num' => $reward['num']]);
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD:
            case BbcRankAward::AWARD_TYPE_GAME_COUPON:
                $data['num'] = $reward['num'];
                $data['cid'] = $reward['cid'];
                $data['exp_days'] = $reward['exp_days'];
                break;
            case BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON:
                $data['num'] = $reward['days'];
                $data['cid'] = $reward['cid'];
                $data['award_extend_info'] = json_encode(['content' => $reward['content']],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case BbcRankAward::AWARD_TYPE_PROP_CARD:
                $propCard = XsPropCard::findOne($reward['cid']);
                $propCardConfig = XsPropCardConfig::findOne( $propCard['prop_card_config_id'] ?? 0);
                $data['num'] = $reward['num'];
                $data['cid'] = $reward['cid'];
                $data['exp_days'] = $reward['effective_hours'];
                $data['award_extend_info'] = json_encode(['extend_type' => $propCardConfig['type'] ?? 0],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD:
                $data['num'] = $reward['num'];
                $data['can_transfer'] = $reward['give_type'];
                $data['exp_days'] = $reward['expire_time'] ? strtotime($reward['expire_time']) : 0;
                $data['award_extend_info'] = ['days' => $reward['effective_hours']];
                $reward['card_type'] && $data['award_extend_info']['open_screen_card_extend'] = ['card_type' => $reward['card_type']];
                $data['award_extend_info'] = json_encode($data['award_extend_info'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD:
                $data['num'] = $reward['num'];
                $data['exp_days'] = $reward['expire_time'] ? strtotime($reward['expire_time']) : 0;
                $data['can_transfer'] = $reward['give_type'];
                $data['award_extend_info'] = json_encode(['days' => $reward['effective_days']]);
                break;
        }

        return $data;
    }

    private function setButtonTagType($tabKey, $count): string
    {
        if ($tabKey == 0) {
            return 'left';
        }

        if ($tabKey == 1) {
            return $count == 2 ? 'right' : 'middle';
        }

        return 'right';
    }

    public function checkExport(array $params): array
    {
        if (empty($params['id'])) {
            throw new ApiException(ApiException::MSG_ERROR, '活动ID不能为空');
        }
        $template = BbcTemplateConfig::findOne($params['id']);
        if (empty($template)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }

        $tagList = BbcRankButtonTag::getListByWhere([
            ['act_id', '=', $params['id']]
        ], 'id, button_tag_type, tag_list_type', 'id asc');
        $tagList = array_column($tagList, null, 'id');

        $list = BbcRankButtonList::getListByWhere([
            ['act_id', '=', $params['id']],
        ], 'id', 'id asc');
        $list = array_column($list, 'id');
        $timeOffset = $this->setTimeOffset($template['time_offset'], self::TYPE_TIME_SUBTRACT);
        return [
            'act_id'     => $template['id'],
            'act_name'   => $template['title'],
            'start_time' => $this->setActivityTime($template['start_time'], $timeOffset, 0, self::TYPE_TIME_SUBTRACT),
            'end_time'   => $this->setActivityTime($template['end_time'], $timeOffset, $template['data_period'], self::TYPE_TIME_SUBTRACT),
            'tag_list'   => $tagList,
            'list'       => $list,
            'tag_num'    => count($tagList)
        ];
    }

    public function getExportList(array $params): array
    {
        $params = array_merge($params, $this->checkExport($params));
        $conditions = [['act_id', '=', $params['act_id']]];
        $tab = $this->getTagId($params);
        $cycle = intval($params['cycle'] ?? 0);
        $tab && $conditions[] = ['tag_id', '=', $tab];
        $cycle && $conditions[] = ['cycle', '=', $cycle];
        $record = XsActRankAwardUser::getListAndTotal($conditions, 'tag_id, list_id, object_id, score, cycle', 'tag_id asc, cycle desc, list_id asc, score desc', $params['page'], $params['limit']);
        if (empty($record['data'])) {
            return $record;
        }
        $data = [];
        $userList = XsUserProfile::getUserProfileBatch(Helper::arrayFilter($record['data'], 'object_id'), ['uid', 'name', 'sex']);
        $userBrokerList = XsBrokerUser::getBrokerUserBatch(Helper::arrayFilter($record['data'], 'object_id'));
        $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($userBrokerList, 'bid'));
        foreach ($record['data'] as $value) {
            $tag = $params['tag_list'][$value['tag_id']] ?? [];
            $userBroker = $userBrokerList[$value['object_id']] ?? [];
            $bid = $userBroker['bid'] ?? '';
            $broker = $brokerList[$bid] ?? [];
            $bname = $broker['bname'] ?? '';
            $data[] = [
                'act_id'        => $params['act_id'],
                'act_name'      => $params['act_name'],
                'time'          => Helper::now($params['start_time']) . '至' . Helper::now($params['end_time']),
                'tab_num'       => $this->getTabNum($params['tag_num'], $tag['button_tag_type'] ?? ''),
                'tag_list_type' => BbcRankButtonTag::$tagListTypeMultiMap[$tag['tag_list_type'] ?? ''] ?? '',
                'task_id'       => $this->getTaskId($value['list_id'], $params['list']),
                'cycle_time'    => $this->getCycleTime($tag['tag_list_type'] ?? 0, $value['cycle'], $params['start_time']),
                'uid'           => $value['object_id'],
                'user_name'     => $userList[$value['object_id']]['name'] ?? '',
                'sex'           => XsUserProfile::$sex_arr[$userList[$value['object_id']]['sex'] ?? ''] ?? '',
                'bid'           => $bid,
                'bname'         => $bname,
                'score'         => $value['score']
            ];
        }

        return ['data' => $data, 'total' => $record['total']];
    }

    private function getTagId(array $params): int
    {
        $tab = intval($params['tab'] ?? 0);
        // tab只会存在3个
        if ($tab < 1 || $tab > 3) {
            return 0;
        }
        $tagIdMap = array_column($params['tag_list'], 'id', 'button_tag_type');
        if ($tab == 1) {
            return $tagIdMap['left'] ?? 0;
        }
        if ($tab == 2 && $params['tag_num'] == 3) {
            return $tagIdMap['middle'] ?? 0;
        }

        return $tagIdMap['right'] ?? 0;

    }

    private function getTabNum($tagNum, $tagType): string
    {
        $num = '';

        switch ($tagType) {
            case 'left':
                $num = 1;
                break;
            case 'middle':
                $num = 2;
                break;
            case 'right':
                $tagNum == 2 ? $num = 2 : $num = 3;
                break;
        }

        return 'Tab' . $num;
    }

    private function getTaskId($listId, $list): string
    {
        if (!in_array($listId, $list)) {
            return '';
        }
        $index = array_search($listId, $list);
        return '任务' . ($index + 1);
    }

    private function validTask(array $params, int $startTime, int $endTime): void
    {
        $id = ($params['id'] ?? 0);
        $tabNum = intval($params['tab_num'] ?? 0);
        $tabList = array_filter($params['tab_list'] ?? []);
        $rankObject = intval($params['rank_object'] ?? 0);
        $divideTrack = intval($params['divide_track'] ?? -1);
        $divideType = intval($params['divide_type'] ?? 0);
        $brokerDistanceStartDay = intval($params['broker_distance_start_day'] ?? 0);

        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }
        
        // 计算活动总天数
        $activityDays = $this->calculateActivityDays($startTime, $endTime);
        
        if ($tabNum < 1 || $tabNum > 3) {
            throw new ApiException(ApiException::MSG_ERROR, '任务tab数量配置错误');
        }

        if (count($tabList) != $tabNum) {
            throw new ApiException(ApiException::MSG_ERROR, '任务tab数量和奖励配置数量不一致');
        }

        if (empty($rankObject)) {
            throw new ApiException(ApiException::MSG_ERROR, '任务对象必填');
        }
        if ($rankObject == BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS) {
            if ($divideTrack < 0) {
                throw new ApiException(ApiException::MSG_ERROR, '是否限制任务人群必填');
            }
            if ($divideTrack == BbcRankButtonList::DIVIDE_TRACK_YES) {
                if (empty($divideType)) {
                    throw new ApiException(ApiException::MSG_ERROR, '依据必填');
                }
                if ($brokerDistanceStartDay < 1 || $brokerDistanceStartDay > 90) {
                    throw new ApiException(ApiException::MSG_ERROR, '具体要求天数区间为1-90');
                }
            }
        }

        foreach ($tabList as $tabKey => $tab) {
            $tabIndex = $tabKey + 1;
            $tagListType = intval($tab['tag_list_type'] ?? -1);
            $cycleDays = intval($tab['cycle_days'] ?? 0);
            $buttonContent = trim($tab['button_content'] ?? '');
            $taskList = $tab['task_list'] ?? [];
            if ($tagListType < 0 || empty($buttonContent) || empty($taskList) || count($taskList) > 50) {
                throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，配置错误请检查', $tabIndex));
            }

            if ($tagListType == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE) {
                if ($cycleDays < 1 || $cycleDays > 90) {        
                    throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务周期天数区间为1-90', $tabIndex));
                }
                
                // 校验周期天数不能大于活动总天数
                if ($activityDays > 0 && $cycleDays > $activityDays) {
                    throw new ApiException(ApiException::MSG_ERROR, sprintf('第%d个Tab的周期天数不能大于活动总天数%d天', $tabIndex, $activityDays));
                }
            }

            foreach ($taskList as $taskKey => $task) {
                $taskIndex = $taskKey + 1;
                $sourceType = intval($task['source_type'] ?? 0);
                $scope = trim($task['scope'] ?? '');
                $comment = trim($task['comment'] ?? '');
                $type = intval($task['type'] ?? 0);
                $giftId = trim($task['gift_id'] ?? '');
                $score = intval($task['score_min'] ?? 0);
                $rewardList = $task['reward_list'] ?? [];
                $isOnlyCrossRoomPk = intval($task['is_only_cross_room_pk'] ?? -1);
                $pkValidType = intval($task['pk_valid_type'] ?? -1);
                $pkTime = intval($task['pk_time'] ?? 0);
                $pkGift = intval($task['pk_gift'] ?? 0);

                if ($rankObject == BbcRankButtonTag::RANK_OBJECT_ROOM && $sourceType == BbcRankScoreConfigNew::SOURCE_TYPE_GAMES && !isset($task['room_scope'])) {
                    throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，房型为必填', $tabIndex, $taskIndex));
                }

                $scopeArr = explode(',', $scope);
                if ($rankObject == BbcRankButtonTag::RANK_OBJECT_ROOM && $sourceType == BbcRankScoreConfigNew::SOURCE_TYPE_GIFT) {
                    if (count($scopeArr) == 1 && in_array($scopeArr[0], [BbcRankScoreConfigNew::SCORE_SCOPE_CHAT, BbcRankScoreConfigNew::SCORE_SCOPE_LIVE])) {
                        if ($isOnlyCrossRoomPk < 0) {
                            throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，是否只统计跨房PK为必填', $tabIndex, $taskIndex));
                        }
                    }
                }

                if (empty($sourceType) || empty($type) || $score < 1 || empty($rewardList) ||
                    (in_array($type, BbcRankScoreConfigNew::$giftScoreTypeMap) && empty($giftId)) ||
                    ($sourceType != BbcRankScoreConfigNew::SOURCE_TYPE_ACTIVE && empty($scope))) {
                    throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，配置错误请检查', $tabIndex, $taskIndex));
                }

                if (!in_array($type, $this->getScopeKey($scope, $type))) {
                    throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，积分统计方式错误请检查', $tabIndex, $taskIndex));
                }

                if ($type == BbcRankScoreConfigNew::SCORE_TYPE_ROOM_COMMENT_NUM && Helper::hasEmoji($comment)) {
                    throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，评论内容不可填写emoji', $tabIndex, $taskIndex));
                }

                if (in_array($type, [BbcRankScoreConfigNew::SCORE_TYPE_PK_WIN, BbcRankScoreConfigNew::SCORE_TYPE_PK_END])) {
                    if ($pkValidType < 0) {
                        throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，有效场次要求必填', $tabIndex, $taskIndex));
                    }
                    
                    if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                        if (!preg_match('/^[1-9]\d*$/', $pkTime)) {
                            throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，单场pk时长必填且必须为正整数', $tabIndex, $taskIndex));
                        }
                    }
                    
                    if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                        if (!preg_match('/^[1-9]\d*$/', $pkGift)) {
                            throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，单场pk房间内收礼必填且必须为正整数', $tabIndex, $taskIndex));
                        }
                    }
                }

                try {
                    $giftId && $this->validScoreGift($giftId);
                } catch (ApiException $e) {
                    throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d, %s', $tabIndex, $taskIndex, $e->getMsg()));
                }

                foreach ($rewardList as $rewardKey => $reward) {
                    $rewardIndex = $rewardKey + 1;
                    try {
                        $this->validReward($reward);
                    } catch (ApiException $e) {
                        throw new ApiException(ApiException::MSG_ERROR, sprintf('任务tab%d下，任务%d，奖励%d，%s', $tabIndex, $taskIndex, $rewardIndex, $e->getMsg()));
                    }
                }
            }
        }
    }

    private function getScopeKey($scope, $type): array
    {
        $scoreType = $this->getScoreType($scope, $type);
        switch ($scoreType) {
            case BbcRankScoreConfigNew::SOURCE_TYPE_GIFT:
                $scopeKey = str_replace(',', '', $scope);
                break;
            case BbcRankScoreConfigNew::SOURCE_TYPE_TOP_UP:
                $scopeKey = BbcRankScoreConfigNew::SCORE_SCOPE_TOP_UP_APPLE;
                break;
            case BbcRankScoreConfigNew::SOURCE_TYPE_GAMES:
                $scopeKey = BbcRankScoreConfigNew::SCORE_SCOPE_GAME_GREEDY;
                break;
            case BbcRankScoreConfigNew::SOURCE_TYPE_ACTIVE:
                $scopeKey = BbcRankScoreConfigNew::SOURCE_TYPE_ACTIVE;
                break;
            case BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY:
                $scopeKey = BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY;
                break;
            default:
                $scopeKey = 0;
        }

        return BbcRankScoreConfigNew::$scoreScopeAndScoreTypeMap[$scopeKey] ?? [];
    }

    private function validReward(array $reward)
    {
        $reward = $this->formatRewardData($reward);

        if (empty($reward['award_type'])) {
            throw new ApiException(ApiException::MSG_ERROR, '类型错误');
        }

        // vip 是否赠送非直接生效需要验证数量必填
        if ($reward['award_type'] == BbcRankAward::AWARD_TYPE_VIP && $reward['give_type'] != BbcRankAward::GIVE_TYPE_AUTO_EFFECT && $reward['num'] < 1) {
            throw new ApiException(ApiException::MSG_ERROR, '数量必填');
        }

        if ($reward['num'] < 1 && in_array($reward['award_type'], [
                BbcRankAward::AWARD_TYPE_DIAMOND, BbcRankAward::AWARD_TYPE_COMMODITY, BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD, BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD,
                BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD, BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD,
                BbcRankAward::AWARD_TYPE_PROP_CARD, BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD,
            ])) {
            throw new ApiException(ApiException::MSG_ERROR, '数量必填');
        }

        if (in_array($reward['award_type'], [
            BbcRankAward::AWARD_TYPE_COMMODITY, BbcRankAward::AWARD_TYPE_MEDAL, BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON,
            BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD, BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND, BbcRankAward::AWARD_TYPE_ROOM_SKIN,
            BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD, BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD,
            BbcRankAward::AWARD_TYPE_PROP_CARD, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD
        ])) {
            if (empty($reward['cid'])) {
                throw new ApiException(ApiException::MSG_ERROR, 'ID必填');
            }
            $model = BbcRankAward::$awardModelMap[$reward['award_type']] ?? '';
            if (empty((new $model)->getInfo($reward['cid']))) {
                throw new ApiException(ApiException::MSG_ERROR, 'ID错误');
            }
        }

        if ($reward['award_type'] == BbcRankAward::AWARD_TYPE_VIP && (empty($reward['vip_level']) || empty($reward['vip_days']))) {
            throw new ApiException(ApiException::MSG_ERROR, '等级、天数必填');
        }

        if ($reward['days'] < 1 && in_array($reward['award_type'], [
                BbcRankAward::AWARD_TYPE_MEDAL, BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON, BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD,
                BbcRankAward::AWARD_TYPE_ROOM_SKIN, BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND, BbcRankAward::AWARD_TYPE_ROOM_BG_CARD,
                BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD
            ])) {
            throw new ApiException(ApiException::MSG_ERROR, '天数必填');
        }

        if ($reward['exp_days'] < 1 && in_array($reward['award_type'], [
                BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD, BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD,
                BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD,
                BbcRankAward::AWARD_TYPE_COMMODITY, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD
            ])) {
            throw new ApiException(ApiException::MSG_ERROR, '资格使用有效天数必填');
        }

        if ($reward['award_type'] == BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON && empty($reward['content'])) {
            throw new ApiException(ApiException::MSG_ERROR, '文案必填');
        }

        if ($reward['give_type'] < 0 && in_array($reward['award_type'], [
                BbcRankAward::AWARD_TYPE_VIP, BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD, BbcRankAward::AWARD_TYPE_ROOM_BG_CARD,
                BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD, BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD,
                BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD, BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD,
            ])) {
            throw new ApiException(ApiException::MSG_ERROR, '是否可赠送必填');
        }

        if (in_array($reward['award_type'], [BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD, BbcRankAward::AWARD_TYPE_PROP_CARD]) && $reward['effective_hours'] < 1) {
            throw new ApiException(ApiException::MSG_ERROR, '有效小时必填');
        }

        if (in_array($reward['award_type'], [BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD]) && $reward['effective_days'] < 1) {
            throw new ApiException(ApiException::MSG_ERROR, '生效天数必填');
        }

        if (in_array($reward['award_type'], [BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD, BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD]) && empty($reward['expire_time'])) {
            throw new ApiException(ApiException::MSG_ERROR, '过期时间必填');
        }
    }


    private function verify(array $params, bool $isModify = false): array
    {
        $data = [
            'title'               => $params['title'],
            'bigarea_id'          => implode('|', $params['bigarea_id']),
            'language'            => $params['language'],
            'type'                => BbcTemplateConfig::TYPE_MULTI_TASK,
            'time_offset'         => $this->setTimeOffset($params['time_offset']),
            'start_time'          => $this->setActivityTime($params['start_time'], $params['time_offset']),
            'end_time'            => $this->setActivityTime($params['end_time'], $params['time_offset'], $params['data_period']),
            'has_be_related'      => $params['has_be_related'],
            'vision_content_json' => json_encode($params['vision_content_json']),
            'award_content_json'  => isset($params['award_content_json']) ? json_encode($params['award_content_json']) : '',
            'data_period'         => $params['data_period'],
            'admin_id'            => $params['admin_id'],
            'dateline'            => time()
        ];

        if ($isModify) {
            $template = BbcTemplateConfig::findOne($params['id']);
            if (empty($template)) {
                throw new ApiException(ApiException::MSG_ERROR, 'template error');
            }
            $data['page_url'] = $this->setPageUrl(BbcTemplateConfig::TYPE_MULTI_TASK, $params['id']);
        }

        return $data;
    }

    public function getOptions(): array
    {
        $service = new StatusService();
        $language = $service->getLanguageNameMap(null, 'label,value');
        $bigAreaId = $service->getFamilyBigArea(null, 'label,value');
        $hasRelate = $this->getHasRelateMap();
        $timeOffset = [
            ['label' => '+3', 'value' => +3],
            ['label' => '+5', 'value' => +5],
            ['label' => '+5.5', 'value' => +5.5],
            ['label' => '+6', 'value' => +6],
            ['label' => '+7', 'value' => +7],
            ['label' => '+8', 'value' => +8],
            ['label' => '+9', 'value' => +9],
            ['label' => '-7', 'value' => -7],
            ['label' => '-8', 'value' => -8],
        ];


        return compact(
            'language',
            'bigAreaId',
            'timeOffset',
            'hasRelate'
        );
    }

    /**
     * 获取活动开始时间和结束时间
     * @param int $id
     * @param string $type
     * @return array
     * @throws ApiException
     */
    private function getTemplateTime(int $id, string $type = 'set'): array
    {
        $template = BbcTemplateConfig::findOne($id);
        if (empty($template)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }
        if ($type == 'set') {
            return [$template['start_time'], $template['end_time'] - $template['data_period'] * 86400];
        }
        $timOffset = $this->setTimeOffset($template['time_offset'], self::TYPE_TIME_SUBTRACT);
        $startTime = $this->setActivityTime($template['start_time'], $timOffset, 0, self::TYPE_TIME_SUBTRACT);
        $endTime = $this->setActivityTime($template['end_time'], $timOffset, $template['data_period'], self::TYPE_TIME_SUBTRACT);   
        
        return [$startTime, $endTime];
    }

    public function getRewardOptions(int $id = 0): array
    {   
        [$startTime, $endTime] = $this->getTemplateTime($id, 'get');
        $tagListType = StatusService::formatMap(BbcRankButtonTag::$tagListTypeMultiMap, 'label,value');
        $awardType = BbcRankAward::$awardTypeMap;
        unset($awardType[BbcRankAward::AWARD_TYPE_PACK]);
        $awardType = StatusService::formatMap($awardType, 'label,value');
        $scoreTypeMap = BbcRankScoreConfigNew::$sourceTypeMap;
        $scoreTypeMap[BbcRankScoreConfigNew::SOURCE_TYPE_ACTIVE] = '活跃';
        $source = StatusService::formatMap($scoreTypeMap, 'label,value');
        $scoreScope = BbcRankScoreConfigNew::getOptions(BbcRankScoreConfigNew::$sourceTypeAndScoreScopeMap, BbcRankScoreConfigNew::$scoreScopeMap);
        $scoreScope[BbcRankScoreConfigNew::SOURCE_TYPE_GAMES][] = [
            'label' => BbcRankScoreConfigNew::$scoreScopeMap[BbcRankScoreConfigNew::SCORE_SCOPE_GREEDY_BRUTAL_GIFT],
            'value' => BbcRankScoreConfigNew::SCORE_SCOPE_GREEDY_BRUTAL_GIFT,
        ];
        // 手动新增积分来源是幸运玩法模版是统计范围的数据源
        $scoreScope[BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY] = $this->getWheelLotteryScoreScopeMap();
        $scoreType = BbcRankScoreConfigNew::getOptions(BbcRankScoreConfigNew::$scoreScopeAndScoreTypeMap, BbcRankScoreConfigNew::$scoreTypeMap);
        $vipDays = StatusService::formatMap(XsUserProfile::$vipDaysMap, 'label,value');
        $content = XsCertificationSign::getContentMap();
        $awardOptions = (new ActivityTaskGamePlayService())->getAwardOptions();
        $rankObject = [
            ['label' => '用户', 'value' => BbcRankButtonTag::RANK_OBJECT_PERSONAL],
            ['label' => '公会成员', 'value' => BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS],
            ['label' => '房间', 'value' => BbcRankButtonTag::RANK_OBJECT_ROOM],
        ];

        $subRankObject = [
            BbcRankButtonTag::RANK_OBJECT_ROOM => [
                ['label' => '房主', 'value' => BbcRankButtonTag::SUB_RANK_OBJECT_ROOM_MASTER],
            ],
        ];

        $vipLevel = $awardOptions[BbcRankAward::AWARD_TYPE_VIP];
        $giveType = $awardOptions['giveType'];
        $cardType = $awardOptions['cardType'];
        unset($awardOptions[BbcRankAward::AWARD_TYPE_VIP], $awardOptions['giveType']);
        $isOnlyCrossRoomPk = StatusService::formatMap(BbcRankButtonList::$isOnlyCorssRoomPkMap);
        $roomScope = StatusService::formatMap(BbcRankScoreConfigNew::$roomScopeMap, 'label,value');
        $effectiveHours = self::getEffectiveHoursMap();
        $pkValidType = StatusService::formatMap(BbcRankScoreConfigNew::$pkValidTypeMap);

        $effectiveDays = self::getEffectiveDaysMap();
        return compact(
            'tagListType',
            'vipDays',
            'content',
            'awardType',
            'source',
            'scoreScope',
            'vipLevel',
            'giveType',
            'cardType',
            'awardOptions',
            'scoreType',
            'rankObject',
            'isOnlyCrossRoomPk',
            'roomScope',
            'startTime',
            'endTime',
            'effectiveHours',
            'subRankObject',
            'pkValidType',
            'effectiveDays'
        );
    }

    protected function getFields(): array
    {
        return [
            'id', 'title', 'start_time', 'end_time', 'time_offset',
            'bigarea_id', 'admin_id', 'dateline', 'language', 'status',
            'data_period', 'publisher_id', 'has_relate', 'page_url',
            'vision_type', 'has_be_related'
        ];
    }

    public function setPageUrl($type, $id): string
    {
        // 更新活动链接
        $prefix = ENV == 'prod' ? self::PROD_URL : self::DEV_URL;

        return sprintf(self::PAGE_URL, $prefix, $id);
    }


    public function formatParams(array &$params): void
    {
        $params['vision_content_json'] = @json_decode($params['vision_content_json'], true);
    }

    /**
     * 计算活动总天数
     * @param int $startTime
     * @param int $endTime
     * @return int
     */
    private function calculateActivityDays(int $startTime, int $endTime): int
    {
        if (empty($startTime) || empty($endTime)) {
            return 0;
        }
        
        // 计算时间差（秒）
        $diffSeconds = $endTime - $startTime;
        // 转换为天数，向上取整，+1包含结束日期
        $diffDays = ceil($diffSeconds / (24 * 60 * 60)) + 1;
        
        return max(0, $diffDays);
    }
}