<?php

namespace Imee\Service\Operate\Activity;

use Imee\Models\Config\BaseModel;
use Imee\Models\Config\BbcActGiftGroup;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsGift;
use Phalcon\Di;
use Imee\Service\Helper;

class ButtonGiftTagService
{
    public function getList(array $params, int $page, int $pageSize, string $order): array
    {
        $object = BbcRankButtonTag::RANK_OBJECT_GIFT;

        $config = BbcTemplateConfig::findOne($params['act_id']);
        if ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            $object = BbcRankButtonTag::RANK_OBJECT_WEEK_STAR;
        } else {
            $config['cycle_type'] = $config['cycles'] = 0;
        }

        $activityService = new ActivityService();
        $timeOffset = $activityService->getTimeOffsetNew($config['time_offset']);
        $startTime = $config['start_time'] - $timeOffset;
        $endTime = $config['end_time'] - $timeOffset - ($config['data_period'] * 86400);

        $list = BbcRankButtonTag::getListAndTotal([
            ['act_id', '=', $params['act_id']],
            ['rank_object', '=', $object]
        ], '*', $order, $page, $pageSize);

        foreach ($list['data'] as &$item) {
            $startTime && $item['start_time'] = Helper::now($startTime);
            $endTime && $item['end_time'] = Helper::now($endTime);
            $item['cycle_type'] = $config['cycle_type'];
            $item['cycles'] = $config['cycles'];
            $item['dateline'] = $item['dateline'] > 0 ? Helper::now($item['dateline']) : '';
            $item['admin'] = Helper::getAdminName($item['admin_id']);
        }

        return $list;
    }

    public function add(array $params): array
    {
        [$res, $msg] = (new ActivityService())->checkActivityStatus($params['act_id']);
        if (!$res) {
            return [false, $msg];
        }
        $object = BbcRankButtonTag::RANK_OBJECT_GIFT;
        if ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            $object = BbcRankButtonTag::RANK_OBJECT_WEEK_STAR;
        }

        $tag = BbcRankButtonTag::findOneByWhere([
            ['act_id', '=', $params['act_id']],
            ['rank_object', '=', $object]
        ]);

        if ($tag) {
            return [false, '您已经创建了一条礼物Tag'];
        }

        [$gRes, $gMsg] = $this->filterGiftId($params['cycle_gift_id'] ?? '', $params['cycle_gift_id_num'] ?? 0);

        if (!$gRes) {
            return [false, $gMsg];
        }

        $tagData = [
            'act_id'            => $params['act_id'],
            'button_content'    => $params['button_content'] ?? '',
            'rank_object'       => $params['rank_object'],
            'dateline'          => time(),
            'admin_id'          => Helper::getSystemUid(),
            'cycle_gift_id_num' => $params['cycle_gift_id_num'] ?? 0,
            'cycle_gift_id'     => $gMsg ?? '',
            'cycles'            => $params['cycle_type'] == BbcTemplateConfig::CYCLE_TYPE_ONE ? 1 : ($params['cycles'] ?? 0),
        ];

        $params['time_offset'] = (new ActivityService())->getTimeOffsetNew($msg['time_offset']);
        $params['data_period'] = $msg['data_period'];

        if ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            return $this->weekStarAdd($params, $tagData);
        } else {
            return $this->multiGroupGiftAdd($params, $tagData);
        }
    }

    public function filterGiftId($giftStr, $num): array
    {
        if (empty($giftStr) || empty($num)) {
            return [true, ''];
        }

        $giftArr = explode("\n", $giftStr);
        $ids = [];
        $errorIds = [];
        foreach (array_chunk($giftArr, $num) as $item) {
            $gifts = Helper::handleIds($item);
            if (count($item) != count($gifts) && $num != 1) {
                return [false, '同期内的礼物id不可重复，请修改礼物id'];
            }
            $validIds = XsGift::getListByIds($gifts);

            $diffIds = array_diff($item, $validIds);
            if ($diffIds) {
                $errorIds = array_merge($errorIds, $diffIds);
            }
            if ($validIds) {
                $ids = array_merge($ids, $item);
            }
        }
        if ($errorIds) {
            return [false, '礼物id' . implode(',', $errorIds) . '不存在'];
        }
        return [true, $ids];
    }

    public function multiGroupGiftAdd($params, $tagData): array
    {
        $giftGroupData = [];
        $json = @json_decode($params['gift_group_json'], true) ?? [];
        foreach ($json as $value) {
            if (empty($value) || empty($value['name']) || empty($value['icon']) || empty($value['gift_act_ids'])) {
                return [false, '礼物组数据有误, 名称、图标、礼物ID均为必填项'];
            }
            $giftGroupData[] = [
                'act_id'       => $params['act_id'],
                'name'         => $value['name'],
                'icon'         => $value['icon'],
                'gift_act_ids' => (new ActivityService())->checkGiftActIds($value['gift_act_ids'])
            ];
        }
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            [$res, $id] = BbcRankButtonTag::add($tagData);
            if (!$res) {
                throw new \Exception('tag添加失败，失败原因：' . $id);
            }
            foreach ($giftGroupData as &$data) {
                $data['button_tag_id'] = $id;
            }
            [$res, $msg, $rows] = BbcActGiftGroup::addBatch($giftGroupData);
            if (!$res) {
                throw new \Exception('礼物组添加失败，失败原因：' . $msg);
            }
            $conn->commit();
            return [true, ''];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage()];
        }
    }

    /**
     * 添加周星礼物榜单（主榜）
     *
     * @param array $params
     * @param $tagData
     * @return array
     * @throws \Exception
     */
    public function weekStarAdd(array $params, $tagData): array
    {
        $effectiveGiftId = count($tagData['cycle_gift_id']);
        if ($tagData['cycle_gift_id_num'] > $effectiveGiftId || $effectiveGiftId % $tagData['cycle_gift_id_num']) {
            return [false, '礼物id数量需要多于或者等于单次活动的周星礼物数量，且为整数倍'];
        }
        $tagData['cycle_gift_id'] = implode(',', $tagData['cycle_gift_id']);
        $startTime = strtotime($params['start_time']) + $params['time_offset'];
        $endTime = $startTime + 7 * 86400 * $tagData['cycles'];
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            [$tagRes, $id] = BbcRankButtonTag::add($tagData);
            if (!$tagRes) {
                throw new \Exception('tag 添加失败，失败原因：' . $id);
            }
            $listData = [
                'act_id'        => $params['act_id'],
                'button_tag_id' => $id,
                'rank_tag'      => BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'is_award'      => BbcRankButtonList::getWeekStarListIsAward($params['act_id']),
                'award_time'    => $startTime,
                'room_support'  => BbcRankButtonList::ROOM_SUPPORT_VOICE_AND_VIDEO,
            ];
            [$listRes, $msg] = BbcRankButtonList::add(array_merge(BbcRankButtonList::getInitData(), $listData));
            if (!$listRes) {
                throw new \Exception('list 添加失败，失败原因：' . $msg);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage()];
        }

        // 等一下在更新活动时间
        usleep(100 * 1000);
        $activityData = [
            'cycles'     => $tagData['cycles'],
            'cycle_type' => $params['cycle_type'],
            'start_time' => $startTime,
            'end_time'   => $endTime,
        ];
        (new ActivityService())->updateActivityInfo($params['act_id'], $activityData);

        return [true, ''];
    }

    public function edit(array $params): array
    {
        $tag = BbcRankButtonTag::findOne($params['id']);
        if (!$tag) {
            return [false, 'tag不存在'];
        }
        [$res, $msg] = (new ActivityService())->checkActivityStatus($tag['act_id']);
        if (!$res) {
            return [false, $msg];
        }

        [$gRes, $gMsg] = $this->filterGiftId($params['cycle_gift_id'] ?? '', $params['cycle_gift_id_num'] ?? 0);

        if (!$gRes) {
            return [false, $gMsg];
        }

        $tagData = [
            'button_content'    => $params['button_content'] ?? '',
            'rank_object'       => $params['rank_object'],
            'dateline'          => time(),
            'admin_id'          => Helper::getSystemUid(),
            'cycle_gift_id_num' => $params['cycle_gift_id_num'] ?? 0,
            'cycle_gift_id'     => $gMsg,
            'cycles'            => $params['cycle_type'] == BbcTemplateConfig::CYCLE_TYPE_ONE ? 1 : ($params['cycles'] ?? 0),
        ];

        $params['act_id'] = $msg['id'];
        $params['time_offset'] = (new ActivityService())->getTimeOffsetNew($msg['time_offset']);
        $params['data_period'] = (new ActivityService())->getTimeOffsetNew($msg['time_offset']);

        if ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            return $this->weekStarEdit($params, $params['id'], $tagData);
        } else {
            return $this->multiGroupGiftEdit($params, $tagData, $tag);
        }
    }

    public function multiGroupGiftEdit(array $params, array $tagData, $tag): array
    {
        $giftGroups = BbcActGiftGroup::getListByWhere([
            ['button_tag_id', '=', $params['id']]
        ], 'id');
        $giftGroupsIds = array_column($giftGroups, 'id');
        $insetGroupData = $updateGroupData = [];
        $groups = @json_decode($params['gift_group_json'], true) ?? [];
        $count = 0;
        $newGroupIds = [];
        foreach ($groups as $group) {
            if (empty($group) || empty($group['name']) || empty($group['icon']) || empty($group['gift_act_ids'])) {
                return [false, '礼物组数据有误, 名称、图标、礼物ID均为必填项'];
            }
            $count++;
            $data = [
                'act_id'        => $tag['act_id'],
                'button_tag_id' => $tag['id'],
                'name'          => $group['name'],
                'icon'          => $group['icon'],
                'gift_act_ids'  => (new ActivityService())->checkGiftActIds($group['gift_act_ids'])
            ];
            if (in_array($group['id'], $giftGroupsIds)) {
                $newGroupIds[] = $group['id'];
                $updateGroupData[$group['id']] = $data;
            } else {
                $insetGroupData[] = $data;
            }
        }
        $delIds = array_values(array_diff($giftGroupsIds, $newGroupIds));
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            [$res, $id] = BbcRankButtonTag::edit($params['id'], $tagData);
            if (!$res) {
                throw new \Exception('tag修改失败，失败原因：' . $id);
            }
            if ($insetGroupData) {
                [$res, $msg, $rows] = BbcActGiftGroup::addBatch($insetGroupData);
                if (!$res) {
                    throw new \Exception('礼物组修改失败，失败原因：' . $msg);
                }

            }
            if ($updateGroupData) {
                [$res, $msg, $rows] = BbcActGiftGroup::updateBatch($updateGroupData);
                if (!$res) {
                    throw new \Exception('礼物组修改失败，失败原因：' . $msg);
                }
            }
            if ($delIds) {
                [$res, $msg, $rows] = BbcActGiftGroup::deleteByWhere([['id', 'in', $delIds]]);
                if (!$res) {
                    throw new \Exception('礼物组修改失败，失败原因：' . $msg);
                }
            }
            if ($insetGroupData || $delIds) {
                $this->updateButtonListRankLimitNum($params['id'], $count);
            }
            $conn->commit();
            return [true, ''];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage()];
        }
    }

    public function weekStarEdit(array $params, int $id, array $tagData)
    {
        $effectiveGiftId = count($tagData['cycle_gift_id']);
        if ($tagData['cycle_gift_id_num'] > $effectiveGiftId || ($effectiveGiftId % $tagData['cycle_gift_id_num'])) {
            return [false, '礼物id数量需要多于或者等于单次活动的周星礼物数量，且为整数倍'];
        }
        $tagData['cycle_gift_id'] = implode(',', $tagData['cycle_gift_id']);
        $startTime = strtotime($params['start_time']) + $params['time_offset'];
        $endTime = $startTime + 7 * 86400 * $tagData['cycles'];
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            $listData = [
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'is_award'     => BbcRankButtonList::getWeekStarListIsAward($params['act_id']),
                'award_time'   => $startTime,
                'room_support' => BbcRankButtonList::ROOM_SUPPORT_VOICE_AND_VIDEO,
            ];
            [$listRes, $msg, $_] = BbcRankButtonList::updateByWhere([
                ['act_id', '=', $params['act_id']],
                ['button_tag_id', '=', $id],
                ['rank_tag', '=', BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT]
            ], $listData);
            if (!$listRes) {
                throw new \Exception('list 编辑失败，失败原因：' . $msg);
            }
            [$tagRes, $msg] = BbcRankButtonTag::edit($id, $tagData);
            if (!$tagRes) {
                throw new \Exception('tag 添加失败，失败原因：' . $msg);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage()];
        }

        // 等一下在更新活动时间
        usleep(100 * 1000);
        $activityData = [
            'cycles'     => $tagData['cycles'],
            'cycle_type' => $params['cycle_type'],
            'start_time' => $startTime,
            'end_time'   => $endTime,
        ];
        (new ActivityService())->updateActivityInfo($params['act_id'], $activityData);

        return [true, ''];
    }

    public function updateButtonListRankLimitNum($tagId, $count): ButtonGiftTagService
    {
        $list = BbcRankButtonList::getListByWhere([['button_tag_id', '=', $tagId]], 'id');
        if ($list) {
            [$res, $msg, $rows] = BbcRankButtonList::updateByWhere([['button_tag_id', '=', $tagId]], ['rank_list_num' => $count]);
            if (!$res) {
                throw new \Exception('礼物组修改失败，失败原因：' . $msg);
            }
        }

        return $this;
    }

    public function delete(int $id)
    {
        $tag = BbcRankButtonTag::findOne($id);
        if (!$tag) {
            return [false, 'tag不存在'];
        }
        [$res, $msg] = (new ActivityService())->checkActivityStatus($tag['act_id']);
        if (!$res) {
            return [false, $msg];
        }
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            $delRes = BbcRankButtonTag::deleteById($id);
            if (!$delRes) {
                return [false, '删除失败'];
            }
            list($res, $msg, $_) = BbcRankButtonList::deleteByWhere([
                ['button_tag_id', '=', $id]
            ]);
            if (!$res) {
                throw new \Exception('删除礼物榜失败，失败原因：' . $msg);
            }

            [$res, $msg, $_] = BbcActGiftGroup::deleteByWhere([['button_tag_id', '=', $id]]);
            if (!$res) {
                throw new \Exception('删除礼物组失败，失败原因：' . $msg);
            }
            $conn->commit();
            return [true, ''];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage()];
        }
    }

    public function info(int $id)
    {
        $tag = BbcRankButtonTag::findOne($id);
        $tag['rank_object'] = (string)$tag['rank_object'];
        $activity = BbcTemplateConfig::findOne($tag['act_id']);
        $timeOffset = (new ActivityService())->getTimeOffsetNew($activity['time_offset']);
        if ($tag['rank_object'] == BbcRankButtonTag::RANK_OBJECT_WEEK_STAR) {
            $list = BbcRankButtonList::getWeekStarMasterList($tag['act_id'], $id);
            $tag['start_time'] = Helper::now($list['start_time'] - $timeOffset);
            $tag['cycle_gift_id'] = str_replace(',', "\n", $tag['cycle_gift_id']);
        } else if ($tag['rank_object'] == BbcRankButtonTag::RANK_OBJECT_GIFT) {
            $giftGroups = BbcActGiftGroup::getListByWhere([
                ['button_tag_id', '=', $id]
            ], 'id, name, icon, gift_act_ids');
            foreach ($giftGroups as &$group) {
                $group['preview_icon'] = Helper::getHeadUrl($group['icon']);
                $group['gift_act_ids'] = str_replace(',', "\n", $group['gift_act_ids']);
            }
            $tag['gift_group_json'] = empty($giftGroups) ? '' : $giftGroups;
            $list = BbcRankButtonList::getMultiGroupGiftList($tag['act_id'], $id);
            foreach ($list as $key => $item) {
                $index = $key + 1;
                if ($item['is_upgrade'] == BbcRankButtonList::IS_UPGRADE_NO) {
                    $item['upgrade_num'] = '';
                    $item['upgrade_type'] = '';
                    $item['upgrade_score'] = '';
                }
                foreach ($item as $field => $v) {
                    if (str_contains($field, '_time')) {
                        $v = Helper::now($v - $timeOffset);
                    }
                    $tag[$field . $index] = (string)$v;
                }
            }
        }

        return $tag;
    }
}