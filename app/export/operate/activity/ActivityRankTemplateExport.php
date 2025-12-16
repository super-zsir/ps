<?php

namespace Imee\Export\Operate\Activity;

use Imee\Export\BaseExport;
use Imee\Models\Config\BbcActGiftGroup;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsActRankAwardUser;
use Imee\Models\Xs\XsActRankAwardUserExtend;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Operate\Activity\ActivityService;
use Imee\Service\Operate\Activity\ButtonListService;

class ActivityRankTemplateExport extends BaseExport
{
    public function getTitle()
    {
        return [];
    }

    public function export($filePathName, $filterParams)
    {
        //$this->setFileHeader($filePathName);

        $id = $filterParams['id'];
        if ($id < 1) return;
        $rec = BbcTemplateConfig::findFirst($id);
        if (!$rec) return;
        $activityService = new ActivityService();
        $button_list = $activityService->getButtonList($rec);
        if (!$button_list) {
            return;
        }

        $rank_tag_arr = ['1' => '魅力值榜单', '2' => '贡献榜', '3' => '房间流水榜', '4' => '积分榜', '5' => '礼物榜', '6' => '礼物榜'];
        $rank_type_arr = ['love' => '情人节模版', 'rank' => '榜单模版', 'gift_rank' => '定制礼物榜单模版'];
        $sexArr = ['未知', '男', '女'];
        $extendTypeArr = ['1' => '送礼榜', '2' => '收礼榜'];

        $buttonTagIds = array_column($button_list, 'button_tag_id');
        $button_other = BbcRankButtonTag::getBatchCommon($buttonTagIds, ['rank_object', 'button_tag_type', 'tag_list_type']);
        $dataPeriod = $rec->data_period > 0 ? $rec->data_period : 7;
        $rec->end_time = $rec->end_time - $dataPeriod * 86400;
        $rec->start_time = $rec->start_time - $activityService->getTimeOffsetNew($rec->time_offset);
        $rec->end_time = $rec->end_time - $activityService->getTimeOffsetNew($rec->time_offset);
        $activityStartTime = Helper::now($rec->start_time);
        $activityEndTime = Helper::now($rec->end_time);

        foreach ($button_list as &$item) {
            $roomSupport = BbcRankButtonList::$roomSupportMap[$item['room_support']] ?? '';
            $rank_tag = $item['rank_tag'];
            $rank_name = $rank_tag_arr[$rank_tag];
            $rank_obj = $button_other[$item['button_tag_id']]['rank_object'] ?? 0;
            $button_tag = $button_other[$item['button_tag_id']]['button_tag_type'] ?? '';
            $tagListType = $button_other[$item['button_tag_id']]['tag_list_type'] ?? 0;
            $item['start_time'] = $item['start_time'] - $activityService->getTimeOffsetNew($rec->time_offset);
            $item['end_time'] = $item['end_time'] - $activityService->getTimeOffsetNew($rec->time_offset);
            $listStartTime = Helper::now($item['start_time']);
            $listEndTime = Helper::now($item['end_time']);
            $level = $item['level'];
            $cycleDays = $tagListType == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE ? $item['cycle_days'] : 1;
            $extra = [];
            if ($rank_tag == 6) {
                $header = "活动ID,活动名称,活动时间,活动模版, 榜单类型,榜单时间,大区,礼物组名称,礼物组包含的id,榜单排名,分值";
                $childHeader = "活动ID,活动名称,活动时间,活动模版,榜单类型,榜单时间,大区,用户Id,用户性别,公会ID,公会名称,榜单排名,分值";
            } elseif ($rank_tag == 9) {
                $header = "活动ID,活动名称,活动时间,活动模版, 榜单类型,榜单时间,大区,用户id,用户性别,公会ID,公会名称,榜单排名,分值";
            } elseif ($rank_tag == 5) {
                $header = "活动ID,活动名称,活动时间,活动模版,榜单类型,所处周期,榜单时间,大区,礼物Id,榜单排名,分值";
                $childHeader = "活动ID,活动名称,活动时间,活动模版,榜单类型,所处周期,榜单时间,大区,用户Id,用户性别,公会ID,公会名称,榜单排名,分值";
            } elseif ($rank_obj == BbcRankButtonTag::FAMILY_WHITE_LIST_TYPE) {
                $header = '活动ID,活动名称,活动时间,活动模版,tag类型,button_tag,button_list,所处周期,榜单时间,晋级轮次|按钮顺序,大区,家族Id,家族名称,家族长id,家族长名称,用户id,用户名称,用户性别,榜单排名,分值';
                $extra = [
                    'button_tag_type'    => $button_tag,
                    'level'              => $level,
                    'act_type'           => $rank_type_arr[$rec->type] ?? '',
                    'act_start_time'     => $activityStartTime,
                    'act_end_time'       => $activityEndTime,
                    'list_start_time'    => $listStartTime,
                    'list_end_time'      => $listEndTime,
                    'act_id'             => $rec->id,
                    'act_title'          => $rec->title,
                    'button_tag_id'      => $item['button_tag_id'],
                    'tag_list_type'      => $tagListType,
                    'tag_list_type_name' => BbcRankButtonTag::$tagListType[$tagListType],
                    'cycle_days'         => $cycleDays,
                ];
            } elseif ($rank_obj == BbcRankButtonTag::CP_RANK_OBJECT) {
                $header = "活动ID,活动名称,活动时间,活动模版,tag类型,button_tag,button_list,榜单类型,所处周期,榜单时间,晋级轮次|按钮顺序,用户1id,用户1性别,用户1公会ID,用户1公会名称,用户2id,用户2性别,用户2公会ID,用户2公会名称,榜单排名,分值";
                $extra = [
                    'button_tag_type'    => $button_tag,
                    'act_type'           => $rank_type_arr[$rec->type] ?? '',
                    'list_type'          => $rank_name,
                    'act_start_time'     => $activityStartTime,
                    'act_end_time'       => $activityEndTime,
                    'list_start_time'    => $listStartTime,
                    'list_end_time'      => $listEndTime,
                    'act_id'             => $rec->id,
                    'act_title'          => $rec->title,
                    'level'              => $level,
                    'tag_list_type'      => $tagListType,
                    'tag_list_type_name' => BbcRankButtonTag::$tagListType[$tagListType],
                    'cycle_days'         => $cycleDays,
                ];
            } elseif ($rank_tag == BbcRankButtonList::RANK_TAG_ROOM_SCORE) {
                $header = '活动ID,活动名称,活动时间,活动模版,tag类型,button_tag,button_list,榜单类型,所处周期,榜单时间,晋级轮次|按钮顺序,大区,房主ID,公会ID,公会名称,贡献用户ID,榜单排名,流水值';
            } elseif ($rank_obj == BbcRankButtonTag::ARCHER_RANK_OBJECT) {
                $header = '活动ID,活动名称,活动时间,活动模版,tag类型,button_tag,button_list,榜单类型,所处周期,榜单时间,晋级轮次|按钮顺序,大区,主播ID,公会ID,公会名称,贡献用户ID,榜单排名,分值';
            } elseif ($rank_tag != 3) {
                $header = "活动ID,活动名称,活动时间,活动模版,tag类型,button_tag,button_list,榜单类型,所处周期,榜单时间,晋级轮次|按钮顺序,大区,用户Id,用户性别,公会ID,公会名称,榜单排名,分值";
            } else {
                $header = "活动ID,活动名称,活动时间,活动模版,tag类型,button_tag,button_list,榜单类型,所处周期,榜单时间,晋级轮次|按钮顺序,大区,房间id,榜单排名,流水值";
            }

            if ($rank_tag != 9) {
                $minMax = Helper::fetchOne("SELECT MIN(id) AS minId, MAX(id) AS maxId FROM xs_act_rank_award_user WHERE list_id={$item['id']} AND act_id={$id}", [], XsActRankAwardUser::SCHEMA_READ);
                $minId = $minMax['minId'] ?? 0;
                $maxId = $minMax['maxId'] ?? 0;
                if ($maxId == 0) {
                    continue;
                }
            } else {
                $minMax = Helper::fetchOne("SELECT MIN(id) AS minId, MAX(id) AS maxId FROM xs_act_rank_award_user_extend WHERE list_id={$item['id']} AND act_id={$id}", [], XsActRankAwardUserExtend::SCHEMA_READ);
                $minId = $minMax['minId'] ?? 0;
                $maxId = $minMax['maxId'] ?? 0;
                if ($maxId == 0) {
                    continue;
                }
            }

            $page = 1;
            $limit = 2000;

            while (true) {

                $offset = ($page - 1) * $limit;

                if ($rank_tag != 9) {
                    $result = XsActRankAwardUser::getListByWhere([
                        ['id', '>=', $minId],
                        ['id', '<=', $maxId],
                        ['list_id', '=', $item['id']],
                        ['act_id', '=', $id]
                    ], 'object_id, act_id, list_id, id, score, cycle', 'cycle asc, score desc, updated_at asc', $limit, $offset);

                } else {
                    $result = XsActRankAwardUserExtend::getListByWhere([
                        ['id', '>=', $minId],
                        ['id', '<=', $maxId],
                        ['act_id', '=', $id],
                        ['list_id', '=', $item['id']]
                    ], '*', 'cycle asc, extend_id asc, extend_type asc, score desc, updated_at asc', $limit, $offset);
                }

                if (empty($result)) {
                    break;
                }

                //start result
                $newArr = [];

                if ($page == 1) {
                    file_put_contents($filePathName, pack('H*', 'EFBBBF'), FILE_APPEND);
                    file_put_contents($filePathName, $header . "\n", FILE_APPEND);
                }

                if ($rank_tag == BbcRankButtonList::RANK_TAG_GIFT_GROUP) {
                    // 获取礼物组信息
                    $gids = array_column($result, 'object_id');
                    $gids = Helper::handleIds($gids);
                    $giftGroups = BbcActGiftGroup::getListByWhere([
                        ['id', 'IN', $gids],
                    ], 'id, name, gift_act_ids');
                    $giftGroups = array_column($giftGroups, null, 'id');
                } elseif ($rank_obj == BbcRankButtonTag::FAMILY_WHITE_LIST_TYPE) {
                    $newArr = $activityService->getFamilyListExport($result, $extra);
                } elseif ($rank_obj == BbcRankButtonTag::CP_RANK_OBJECT) {
                    $newArr = $activityService->getCpListExport($result, $extra);
                } elseif ($rank_tag != 3 && $rank_tag != 5) {
                    if ($rank_obj == 1 || $rank_tag == 7) {
                        $bids = array_column($result, 'object_id');
                        $bids = Helper::handleIds($bids);
                        $brokerInfo = XsBroker::getBrokerBatch($bids, ['bid', 'bname', 'creater']);
                        $uids = array_column($brokerInfo, 'creater');
                    } else {
                        $uids = array_column($result, 'object_id');
                        $uids = Helper::handleIds($uids);
                        $brokerUsers = XsBrokerUser::getBrokerUserBatchChounk($uids);
                        $brokerUsers = array_column($brokerUsers, 'bid', 'uid');
                        $bids = array_values($brokerUsers);
                        $bids = Helper::handleIds($bids);
                        $brokers = XsBroker::getBrokerBatchChounk($bids, ['bid', 'bname']);
                        $brokers = array_column($brokers, null, 'bid');
                    }
                    $uids = Helper::handleIds($uids);
                    $userInfo = XsUserProfile::getUserProfileBatchChunk($uids, ['uid', 'sex']);
                    $userBigAreas = XsUserBigarea::getUserBigareasChunk($uids);
                } else {
                    $rids = array_column($result, 'object_id');
                    $rids = Helper::handleIds($rids);
                    $userBigAreas = XsUserBigarea::getUserBigareasChunk($rids);
                }
                // cp和家族榜数据单独处理
                if (!in_array($rank_obj, [BbcRankButtonTag::FAMILY_WHITE_LIST_TYPE, BbcRankButtonTag::CP_RANK_OBJECT])) {
                    $k = $offset;
                    $cycles = [];
                    foreach ($result as $v) {
                        $dayRound = '/';
                        if (ButtonListService::isDaysAndCycleList($tagListType)) {
                            // 不同日榜轮次时重置下排名
                            if (!in_array($v['cycle'], $cycles)) {
                                $k = $offset;
                                $cycles[] = $v['cycle'];
                            }
                            $dayRound = date('Y-m-d', strtotime($listStartTime) + 86400 * ($v['cycle'] - 1) * $cycleDays);
                            if ($tagListType == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE) {
                                $dayRound .= ' 至 ' . date('Y-m-d', strtotime($dayRound) + 86400 * ($cycleDays - 1));
                            }
                        }
                        if ($rank_tag == BbcRankButtonList::RANK_TAG_GIFT_GROUP) {
                            $newArr[] = [
                                $rec->id,
                                $rec->title,
                                date('Y-m-d H:i', $rec->start_time) . '-' . date('Y-m-d H:i', $rec->end_time),
                                '多组礼物榜单模版',
                                $rank_name,
                                $listStartTime . '-' . $listEndTime,
                                'area'            => Helper::getLanguageName($rec->language),
                                'gift_group_name' => $giftGroups[$v['object_id']]['name'] ?? '',
                                $giftGroups[$v['object_id']]['gift_act_ids'] ?? '',
                                'key'             => $k + 1,
                                'score'           => $v['score'],
                                'list_id'         => $item['id'],
                                'gift_id'         => $v['object_id'],
                                'cycle'           => $v['cycle']
                            ];
                        } elseif ($rank_tag == BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT) {
                            $sex = isset($userInfo[$v['object_id']]) ? $sexArr[$userInfo[$v['object_id']]['sex']] : '-';
                            $bid = $brokerUsers[$v['object_id']] ?? 0;
                            $broker_name = $brokers[$bid]['bname'] ?? '';
                            // 轮次开始时间
                            $cycleStartTime = $rec->start_time + 7 * 86400 * ($v['cycle'] - 1);
                            $newArr[] = [
                                $rec->id,
                                $rec->title,
                                date('Y-m-d H:i', $rec->start_time) . '-' . date('Y-m-d H:i', $rec->end_time),
                                '周星礼物榜单模板',
                                '礼物id' . $v['extend_id'] . '-' . XsActRankAwardUserExtend::$extendType[$v['extend_type']],
                                date('Y-m-d H:i:s', $cycleStartTime) . '-' . date('Y-m-d H:i:s', strtotime("+7 day", $cycleStartTime)),
                                'area'        => XsBigarea::AREA_MAP[$userBigAreas[$v['object_id']] ?? 0] ?? '-',
                                $v['object_id'],
                                $sex,
                                $bid,
                                $broker_name,
                                $k + 1,
                                $v['score'],
                                'extend_id'   => $v['extend_id'],
                                'extend_type' => $v['extend_type'],
                                'cycle'       => $v['cycle'],
                            ];
                        } elseif ($rank_tag == BbcRankButtonList::RANK_TAG_ROOM_SCORE || $rank_obj == BbcRankButtonTag::ARCHER_RANK_OBJECT) {
                            $bid = $brokerUsers[$v['object_id']] ?? 0;
                            $brokerName = $brokers[$bid]['bname'] ?? '';
                            $newArr[] = [
                                $rec->id,
                                $rec->title,
                                date('Y-m-d H:i', $rec->start_time) . '-' . date('Y-m-d H:i', $rec->end_time),
                                $rank_type_arr[$rec->type],
                                BbcRankButtonTag::$tagListType[$tagListType],
                                $button_tag,
                                $item['id'],
                                $rank_name,
                                $dayRound,
                                $listStartTime . '-' . $listEndTime,
                                $level,
                                'area'  => XsBigarea::AREA_MAP[$userBigAreas[$v['object_id']] ?? 0] ?? '-',
                                $v['object_id'],
                                $bid,
                                $brokerName,
                                '',
                                'key'   => $k + 1,
                                'score' => $v['score'],
                                'cycle' => $v['cycle']
                            ];
                        } elseif ($rank_tag != 3 && $rank_tag != 5) {
                            if ($rank_obj == 1 || $rank_obj == 7) {
                                $uid = $brokerInfo[$v['object_id']]['creater'] ?? 0;
                                $sexKey = intval($userInfo[$v['uid']]['sex'] ?? 0);
                                $sex = $sexArr[$sexKey];
                                $broker_name = $brokerInfo[$v['object_id']]['bname'] ?? '-';
                                $bid = $v['object_id'];
                            } else {
                                $uid = $v['object_id'];
                                $sex = isset($userInfo[$v['object_id']]) ? $sexArr[$userInfo[$v['object_id']]['sex']] : '-';
                                $bid = $brokerUsers[$v['object_id']] ?? 0;
                                $broker_name = $brokers[$bid]['bname'] ?? '';
                            }
                            $newArr[] = [
                                $rec->id,
                                $rec->title,
                                date('Y-m-d H:i', $rec->start_time) . '-' . date('Y-m-d H:i', $rec->end_time),
                                $rank_type_arr[$rec->type],
                                BbcRankButtonTag::$tagListType[$tagListType],
                                $button_tag,
                                $item['id'],
                                $rank_name,
                                $dayRound,
                                $listStartTime . '-' . $listEndTime,
                                $level,
                                'area'    => XsBigarea::AREA_MAP[$userBigAreas[$uid] ?? 0] ?? '-',
                                $uid,
                                $sex,
                                'bid'     => $bid,
                                $broker_name,
                                'key'     => $k + 1,
                                'score'   => $v['score'],
                                'list_id' => $item['id'],
                                'cycle'   => $v['cycle']
                            ];
                        } elseif ($rank_tag == 5) {
                            $newArr[] = [
                                $rec->id,
                                $rec->title,
                                date('Y-m-d H:i', $rec->start_time) . '-' . date('Y-m-d H:i', $rec->end_time),
                                $rank_type_arr[$rec->type],
                                $rank_name,
                                'cycle_day' => $dayRound,
                                $listStartTime . '-' . $listEndTime,
                                'area'      => Helper::getLanguageName($rec->language),
                                'gift_id'   => $v['object_id'],
                                'key'       => $k + 1,
                                'score'     => $v['score'],
                                'list_id'   => $item['id'],
                                'cycle'     => $v['cycle'],
                            ];
                        } else {
                            $newArr[] = [
                                $rec->id,
                                $rec->title,
                                date('Y-m-d H:i', $rec->start_time) . '-' . date('Y-m-d H:i', $rec->end_time),
                                $rank_type_arr[$rec->type],
                                BbcRankButtonTag::$tagListType[$tagListType],
                                $button_tag,
                                $item['id'],
                                $rank_name,
                                $dayRound,
                                $listStartTime . '-' . $listEndTime,
                                $level,
                                'area'  => XsBigarea::AREA_MAP[$userBigAreas[$v['object_id']] ?? 0] ?? '-',
                                $v['object_id'],
                                'key'   => $k + 1,
                                'score' => $v['score'],
                            ];
                        }
                        $k++;
                    }
                }

                $tmp = array_map(function ($item) use ($rank_tag) {
                    unset($item['list_id']);
                    unset($item['cycle']);
                    if ($rank_tag == BbcRankButtonList::RANK_TAG_GIFT_GROUP) {
                        unset($item['gift_id']);
                    } elseif ($rank_tag != BbcRankButtonList::RANK_TAG_CUSTOMIZED_GIFT) {
                        unset($item['cycle_day']);
                    }
                    return $item;
                }, $newArr);
                if ($rank_tag == BbcRankButtonList::RANK_TAG_CUSTOMIZED_GIFT || $rank_tag == BbcRankButtonList::RANK_TAG_GIFT_GROUP) {
                    $tmpStr = $this->formatCsvTextBatch($tmp);
                    file_put_contents($filePathName, $tmpStr, FILE_APPEND);
                    // 定制礼物只存在收送榜单
                    $extendTypeMap = $rank_tag == BbcRankButtonList::RANK_TAG_CUSTOMIZED_GIFT ? [1, 2] : array_keys(XsActRankAwardUserExtend::$extendType);
                    foreach ($newArr as $val2) {
                        foreach ($extendTypeMap as $type) {
                            $extend = XsActRankAwardUserExtend::getListByListIdAndExtendId($val2['list_id'], $val2['gift_id'], $type, $val2['cycle']);
                            if (empty($extend)) {
                                continue;
                            }
                            $extendTypeName = XsActRankAwardUserExtend::$extendType[$type] ?? '';
                            if ($rank_tag == BbcRankButtonList::RANK_TAG_CUSTOMIZED_GIFT) {
                                $extendTypeName = str_replace('+', '', $roomSupport) . ($extendTypeArr[$type] ?? '');
                            }
                            $uids2 = array_column($extend, 'object_id');
                            $uids2 = Helper::handleIds($uids2);
                            $userProfiles2 = XsUserProfile::getUserProfileBatchChunk($uids2, ['uid', 'sex']);
                            $brokerUsers2 = XsBrokerUser::getBrokerUserBatchChounk($uids2);
                            $brokerUsers2 = array_column($brokerUsers2, 'bid', 'uid');
                            $bids2 = array_values($brokerUsers2);
                            $bids2 = Helper::handleIds($bids2);
                            $brokers2 = XsBroker::getBrokerBatchChounk($bids2);
                            $brokers2 = array_column($brokers2, null, 'bid');
                            $listInfo2 = [];
                            $i2 = 1;
                            file_put_contents($filePathName, $childHeader . "\n", FILE_APPEND);
                            foreach ($extend as $item2) {
                                $newItems2 = [
                                    $val2[0],
                                    $val2[1],
                                    $val2[2],
                                    $val2[3],
                                    ($val2['gift_group_name'] ?? '') . $val2['gift_id'] . '-' . $extendTypeName,
                                    $val2['cycle_day'],
                                    $val2[5],
                                    $val2['area'],
                                    $item2['object_id'],
                                    $sexArr[$userProfiles2[$item2['object_id']]['sex'] ?? 0],
                                    $brokers2[$brokerUsers2[$item2['object_id']] ?? 0]['bid'] ?? 0,
                                    $brokers2[$brokerUsers2[$item2['object_id']] ?? 0]['bname'] ?? '',
                                    $i2,
                                    $item2['score']
                                ];
                                // 多组礼物榜单不需要轮次字段
                                if ($rank_tag == BbcRankButtonList::RANK_TAG_GIFT_GROUP) {
                                    unset($newItems2[5]);
                                }
                                $listInfo2[] = $newItems2;
                                $i2++;
                            }
                            $tmpStr2 = $this->formatCsvTextBatch($listInfo2, false);
                            file_put_contents($filePathName, $tmpStr2, FILE_APPEND);
                        }
                    }
                } elseif ($rank_tag == BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT) {
                    $initCycle = $tips = $newArr[0]['cycle'];
                    $initType = $newArr[0]['extend_type'];
                    $initGiftId = $newArr[0]['extend_id'];
                    $newData = [];
                    $rank = 0;
                    // 按照在同一期下，某个礼物的收、送礼榜，上榜用户之间排名格式话数据
                    foreach ($newArr as $key => &$data) {
                        if ($data['cycle'] != $initCycle || $data['extend_type'] != $initType || $data['extend_id'] != $initGiftId) {
                            $tips = $key;
                            $rank = 0;
                        }
                        $rank++;
                        $data[10] = $rank;
                        $initCycle = $data['cycle'];
                        $initType = $data['extend_type'];
                        $initGiftId = $data['extend_id'];
                        unset($data['cycle'], $data['extend_type'], $data['extend_id']);
                        $newData[$tips][] = $data;
                    }
                    foreach ($newData as $val) {
                        $tmpStr = $this->formatCsvTextBatch($val);
                        file_put_contents($filePathName, $tmpStr, FILE_APPEND);
                    }
                } elseif (in_array($rank_obj, [BbcRankButtonTag::FAMILY_WHITE_LIST_TYPE, BbcRankButtonTag::CP_RANK_OBJECT])) {
                    $tmpStr = $this->formatCsvTextBatch($tmp);
                    file_put_contents($filePathName, $tmpStr, FILE_APPEND);
                } else {
                    if ($rank_tag != 3 && ($rank_obj == 1 || $rank_obj == 7)) {
                        $arr = [];
                        foreach ($newArr as &$val) {
                            $listId = $val['list_id'] ?? 0;
                            $cycle = $val['cycle'];
                            unset($val['list_id']);
                            unset($val['cycle']);
                            $arr[] = $val;
                            $this->getBrokerGSInfo($listId, $cycle, $val['bid'], $val['key'], $val['area'], $arr);
                        }
                        $tmp = $arr;
                        unset($arr);
                    } elseif ($rank_tag == BbcRankButtonList::RANK_TAG_ROOM_SCORE || $rank_obj == BbcRankButtonTag::ARCHER_RANK_OBJECT) {
                        $arr = [];
                        foreach ($newArr as &$val) {
                            $cycle = $val['cycle'];
                            unset($val['cycle']);
                            $arr[] = $val;
                            $this->getRoomScoreInfo($val, $cycle, $arr);
                        }
                        $tmp = $arr;
                        unset($arr);
                    }
                    $tmpStr = $this->formatCsvTextBatch($tmp);
                    file_put_contents($filePathName, $tmpStr, FILE_APPEND);
                }

                //end result
                if (count($result) < $limit) {
                    break;
                }

                $page++;
            }
        }

        if (!file_exists($filePathName)) {
            file_put_contents($filePathName, pack('H*', 'EFBBBF'), FILE_APPEND);
            file_put_contents($filePathName, '无数据' . "\n", FILE_APPEND);
        }
    }

    private function getBrokerGSInfo($listId, $cycle, $bid, $key, $area, &$d): array
    {
        $sexArr = ['未知', '男', '女'];
        $extend = XsActRankAwardUserExtend::getListByListIdAndExtendId($listId, $bid, XsActRankAwardUserExtend::EXTEND_TYPE_BR, $cycle);
        if (empty($extend)) {
            return [];
        };
        $uids = array_column($extend, 'object_id');

        $userInfo = XsUserProfile::getUserProfileBatch($uids, ['uid', 'sex']);
        foreach ($extend as $k => $value) {
            $sex = intval($userInfo[$value['object_id']]['sex'] ?? 0);
            $d[] = [
                '', '', '', '', '', '', '', '', '', '', '',
                $area, $value['object_id'], $sexArr[$sex],
                '', '', $key . '--' . ($k + 1), $value['score'], ''
            ];
        }

        return $d;
    }

    private function getRoomScoreInfo($list, $cycle, &$data)
    {
        $k = $list['key'];
        $baseData = array_slice($list, 0, 11);
        $extend = XsActRankAwardUserExtend::getListByListIdAndExtendId($list[6], $list[11], XsActRankAwardUserExtend::EXTEND_TYPE_BR, $cycle);
        if (empty($extend)) {
            return [];
        };

        foreach ($extend as $i => $item) {
            $row = $baseData;
            $row[] = XsBigarea::AREA_MAP[$userBigAreas[$item['object_id']] ?? 0] ?? '';
            $row[] = $list[11];
            $row[] = '';
            $row[] = '';
            $row[] = $item['object_id'];
            $row[] = $k . '--' . ($i + 1);
            $row[] = $item['score'];

            $data[] = $row;
        }

        return $data;
    }
}