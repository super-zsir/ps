<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Config\BaseModel;
use Imee\Models\Config\BbcActWheelLotteryReward;
use Imee\Models\Config\BbcRankAward;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankScoreConfigNew;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsActivityScoreWallet;
use Imee\Models\Xs\XsActWheelLotteryAwardList;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsEmoticons;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstActiveKingdeeRecord;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xsst\XsstWheelLotteryAwardAuditRecord;
use Imee\Service\Helper;
use Imee\Service\Operate\Lighting\NameIdLightingLogService;
use Imee\Service\Operate\Minicard\MiniCardSendService;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use Phalcon\Di;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Service\Operate\User\OpenScreenCardService;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardSendService;
use Imee\Models\Xs\XsActRankCommodityLog;
use Imee\Models\Xs\XsItemCard;

class ActivityLuckGamePlayService extends ActivityService
{
    const PAGE_URL = '%s/turntable-template/?aid=%d&clientScreenMode=1%s';

    public function add(array $params): array
    {
        $this->verify($params);
        $data = $this->formatData($params);
        $baseData = [
            'dateline' => time(),
            'admin_id' => Helper::getSystemUid()
        ];
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            [$actRes, $actId] = BbcTemplateConfig::add(array_merge($data['templateConfig'], $baseData));
            if (!$actRes) {
                return [false, '活动模版数据添加失败，失败原因：' . $actId];
            }
            // 更新活动链接
            $update = [
                'page_url' => $this->setPageUrl($data['templateConfig']['vision_type'], $actId),
            ];
            BbcTemplateConfig::edit($actId, $update);
            $baseData['act_id'] = $actId;
            [$tagRes, $tagId] = BbcRankButtonTag::add(array_merge($baseData, $data['buttonTag']));
            if (!$tagRes) {
                return [false, 'buttonTag数据添加失败，失败原因：' . $tagId];
            }
            $baseData['button_tag_id'] = $tagId;
            [$listRes, $listIds] = $this->handleButtonList($data['buttonList'], $baseData);
            if (!$listRes) {
                return [false, 'buttonList数据添加失败，失败原因：' . $listIds];
            }
            unset($baseData['button_tag_id']);
            $baseData['list_ids'] = $listIds;
            [$scoreRes, $scoreMsg] = $this->handleScoreConfig($data['rankScoreConfig'], $baseData);
            if (!$scoreRes) {
                return [false, $scoreMsg];
            }
            [$awardRes, $awardMsg] = $this->handleRankAward($data['rankAward'], $baseData);
            if (!$awardRes) {
                return [false, $awardMsg];
            }
            $conn->commit();
            return [true, $actId];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage() ?? '添加失败'];
        }
    }

    public function edit(array $params): array
    {
        $this->verify($params, true);
        $data = $this->formatData($params);
        $baseData = [
            'dateline' => time(),
            'admin_id' => Helper::getSystemUid()
        ];
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            [$actRes, $actMsg] = BbcTemplateConfig::edit($params['id'], $data['templateConfig']);
            if (!$actRes) {
                return [false, '活动模版数据修改失败，失败原因：' . $actMsg];
            }
            $baseData['act_id'] = $params['id'];
            [$tagRes, $tagMsg] = BbcRankButtonTag::edit($params['button_tag_id'], $data['buttonTag']);
            if (!$tagRes) {
                return [false, 'buttonTag数据修改失败，失败原因：' . $tagMsg];
            }

            $baseData['button_tag_id'] = $params['button_tag_id'];
            [$listRes, $listIds] = $this->handleButtonList($data['buttonList'], $baseData);
            if (!$listRes) {
                return [false, 'buttonList数据修改失败，失败原因：' . $listIds];
            }
            unset($baseData['button_tag_id']);
            $baseData['list_ids'] = $listIds;

            [$scoreRes, $scoreMsg] = $this->handleScoreConfig($data['rankScoreConfig'], $baseData);
            if (!$scoreRes) {
                return [false, $scoreMsg];
            }
            [$awardRes, $awardMsg] = $this->handleRankAward($data['rankAward'], $baseData);
            if (!$awardRes) {
                return [false, $awardMsg];
            }
            $conn->commit();
            return [true, $params['id']];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage() ?? '添加失败'];
        }
    }

    private function formatData(array $params): array
    {
        $templateConfig = $this->getTemplateData($params);
        $buttonTag = $this->getButtonTagData($params);
        $buttonList = $this->getButtonListData($params);
        $rankScoreConfig = $this->getRankScoreConfigData($params);
        $rankAward = $this->getRankAwardData($params);

        return compact('templateConfig', 'buttonTag', 'buttonList', 'rankScoreConfig', 'rankAward');
    }

    private function getTemplateData(array &$params): array
    {
        $dataPeriod = intval($params['data_period']);
        $timeOffset = $params['time_offset'];
        $startTime = strtotime($params['start_time']) + (8 - $timeOffset) * 3600;
        $endTime = strtotime($params['end_time']) + (8 - $timeOffset) * 3600;
        // 活动时间根据时区字段进行转化 （8-time_offset）* 3600, 结束时间要在加上数据保留时间（data_period）
        $templateConfig = [
            'start_time'          => $startTime,
            'end_time'            => $endTime + ($dataPeriod * 86400),
            'language'            => $params['language'],
            'title'               => $params['title'],
            'time_offset'         => $timeOffset * 10, // 10倍保存
            'bigarea_id'          => implode('|', array_map('intval', $params['bigarea_id'])),
            'type'                => BbcTemplateConfig::TYPE_WHEEL_LOTTERY,
            'vision_type'         => $params['vision_type'],
            'data_period'         => $dataPeriod,
            'has_relate'          => $params['has_relate'],
            'has_be_related'      => $params['has_be_related'],
            'relate_type'         => $params['relate_type'],
            'relate_id'           => $params['relate_id'],
            'relate_icon'         => $params['relate_icon'],
            'vision_content_json' => json_encode($params['vision_content_json']),
            'rule_content_json'   => $params['rule_content_json'],
        ];

        if (isset($params['id']) && !empty($params['id'])) {
            // 更新活动链接
            $templateConfig['page_url'] = $this->setPageUrl($templateConfig['vision_type'], $params['id']);
        }


        $params['start_time'] = $startTime;
        $params['end_time'] = $endTime;
        return $templateConfig;
    }

    private function getButtonTagData(array $params): array
    {
        return [
            'rank_object' => BbcRankButtonTag::RANK_OBJECT_PERSONAL,
        ];
    }

    private function getButtonListData(array $params): array
    {
        $add = $edit = $existsIds = [];
        if (!empty($params['id'])) {
            $existsIds = BbcRankButtonList::getListByWhere([['act_id', '=', $params['id']]], 'id');
            $existsIds = array_column($existsIds, 'id');
        }

        foreach ($params['award_config'] as $awardConfig) {
            $isScore = $awardConfig['is_score'] ?? 0;
            $scoreMin = $isScore == BbcRankButtonList::IS_SCORE_YES ? ($awardConfig['score_min'] ?? 0) : 0;
            $tmp = [
                'button_content'     => $awardConfig['button_content'] ?? '',
                'level'              => $awardConfig['level'],
                'upgrade_extend_num' => 0,
                'start_time'         => $params['start_time'],
                'end_time'           => $params['end_time'],
                'score_min'          => $scoreMin,
            ];
            if (!empty($awardConfig['button_list_id'])) {
                $edit[$awardConfig['button_list_id']] = $tmp;
            } else {
                $add[] = $tmp;
            }
        }

        $delete = array_diff($existsIds, array_keys($edit));
        $delete = array_map('intval', array_values($delete));

        return compact('add', 'edit', 'delete');
    }

    private function getRankScoreConfigData(array $params): array
    {
        return $params['score_source'];
    }

    private function getRankAwardData(array $params): array
    {
        $add = $edit = $existsIds = [];
        if (!empty($params['id'])) {
            $existsIds = BbcActWheelLotteryReward::getListByWhere([['act_id', '=', $params['id']]], 'id');
            $existsIds = array_column($existsIds, 'id');
        }
        foreach ($params['award_config'] as $awardConfig) {
            $tmp = [
                'list_id'         => $awardConfig['button_list_id'] ?? 0,
                'award_list'      => json_encode($this->getAwardData($awardConfig['award_list']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'lottery_consume' => $awardConfig['lottery_consume'],
            ];
            if (!empty($awardConfig['award_id'])) {
                $edit[$awardConfig['award_id']] = $tmp;
            } else {
                $add[] = array_merge($tmp, ['level' => $awardConfig['level']]);
            }
        }
        $delete = array_diff($existsIds, array_keys($edit));
        $delete = array_map('intval', array_values($delete));

        return compact('add', 'edit', 'delete');
    }

    private function getAwardData(array $awardList): array
    {
        $data = [];

        $i = 1;
        foreach ($awardList as $award) {
            $type = (int)$award['type'];
            $weight = (int)$award['weight'];
            $stockType = (int)$award['stock_type'];
            $stock = intval($award['stock'] ?? 0);
            $num = intval($award['num'] ?? 1);
            $expDays = intval($award['exp_days'] ?? 0);
            $days = intval($award['days'] ?? 0);
            $id = intval($award['id'] ?? 0);
            $content = trim($award['content'] ?? '');
            $giveType = intval($award['give_type'] ?? 0);
            $effectiveHours = intval($award['effective_hours'] ?? 0);
            $expireTime = trim($award['expire_time'] ?? '');
            $cardType = intval($award['card_type'] ?? -1);
            $effectiveDays = intval($award['effective_days'] ?? 0);

            // vip 是否可赠送为直接生效 num默认为1
            if ($type == BbcActWheelLotteryReward::REWARD_TYPE_VIP && $giveType == BbcActWheelLotteryReward::GIVE_TYPE_AUTO_EFFECT) {
                $num = 1;
            }

            if ($stockType == BbcActWheelLotteryReward::STOCK_TYPE_NO_LIMIT) {
                $stock = 0;
            }
            // 特定类型下重置下num，不然前端会把修改前的值传过来保存
            if (in_array($type, [
                BbcActWheelLotteryReward::REWARD_TYPE_START,
                BbcActWheelLotteryReward::REWARD_TYPE_MEDAL,
                BbcActWheelLotteryReward::REWARD_TYPE_ROOM_SKIN,
                BbcActWheelLotteryReward::REWARD_TYPE_CERTIFICATION_ICON,
                BbcActWheelLotteryReward::REWARD_TYPE_EMOTICONS,
                BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BACKGROUND
            ])) {
                $num = 1;
            }

            $tmp = [];
            switch ($type) {
                case BbcActWheelLotteryReward::REWARD_TYPE_GIFT_BAG:
                    $tmp = ['id' => $id];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_MEDAL:
                case BbcActWheelLotteryReward::REWARD_TYPE_ROOM_SKIN:
                case BbcActWheelLotteryReward::REWARD_TYPE_EMOTICONS:
                case BbcActWheelLotteryReward::REWARD_TYPE_ROOM_TOP_CARD:
                case BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BACKGROUND:
                    $tmp = ['id' => $id, 'days' => $days];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_VIP:
                    $tmp = ['id' => $id, 'days' => $days, 'num' => $num, 'extend_info' => ['extend_type' => $giveType]];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BG_CARD:
                    $tmp = ['days' => $days, 'extend_info' => ['extend_type' => $giveType]];
                    $cardType > -1 && $tmp['extend_info']['room_bg_card_extend'] = ['card_type' => $cardType];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_PRETTY_ID_CARD:
                case BbcActWheelLotteryReward::REWARD_NAME_ID_LIGHTING:
                case BbcActWheelLotteryReward::REWARD_TYPE_MINI_CARD_DRESS:
                case BbcActWheelLotteryReward::REWARD_TYPE_HOMEPAGE_CARD:
                    $tmp = ['id' => $id, 'days' => $days, 'num' => $num, 'exp_days' => $expDays, 'extend_info' => ['extend_type' => $giveType]];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_CERTIFICATION_ICON:
                    $tmp = ['id' => $id, 'days' => $days, 'extend_info' => ['content' => $content]];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON:
                case BbcActWheelLotteryReward::REWARD_TYPE_COMMODITY:
                    $tmp = ['id' => $id, 'num' => $num, 'exp_days' => $expDays];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD:
                    $tmp = ['id' => $id, 'num' => $num, 'days' => $effectiveHours, 'exp_days' => strtotime($expireTime), 'extend_info' => ['extend_type' => $giveType]];
                    $cardType > 0 && $tmp['extend_info']['open_screen_card_extend'] = ['card_type' => $cardType];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD:
                    $propCard = XsPropCard::findOne($id);
                    $propCardConfig = XsPropCardConfig::findOne( $propCard['prop_card_config_id'] ?? 0);
                    $tmp = ['id' => $id, 'num' => $num, 'exp_days' => $effectiveHours, 'extend_info' => ['extend_type' => $propCardConfig['type'] ?? 0]];
                    break;
                case BbcActWheelLotteryReward::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD:
                    $tmp = ['id' => $id, 'num' => $num, 'days' => $effectiveDays, 'exp_days' => strtotime($expireTime), 'extend_info' => ['extend_type' => $giveType]];
                    break;
            }
            $data[] = array_merge($tmp, ['num' => $num, 'type' => $type, 'weight' => $weight, 'stock' => $stock, 'number' => $i]);
            $i++;
        }

        return $data;
    }

    public function info(int $id): array
    {
        $res = BbcTemplateConfig::findOne($id);
        if (!$res) {
            return [];
        }
        $tag = BbcRankButtonTag::findOneByWhere([['act_id', '=', $id]]);

        $timeOffset = (8 - intval($res['time_offset']) / 10) * 3600;
        $dataPeriod = intval($res['data_period']) * 86400;
        $startTime = $res['start_time'] - $timeOffset;
        $endTime = $res['end_time'] - $timeOffset - $dataPeriod;
        $visionContentJson = @json_decode($res['vision_content_json'], true);
        if ($visionContentJson) {
            foreach ($visionContentJson as $key => $value) {
                if (str_contains($key, '_img_vc')) {
                    $visionContentJson[$key . '_all'] = Helper::getHeadUrl($value);
                }
            }
        }
        $data = [
            'id'                  => $res['id'],
            'start_time'          => Helper::now($startTime),
            'end_time'            => Helper::now($endTime),
            'language'            => $res['language'],
            'title'               => $res['title'],
            'data_period'         => $res['data_period'],
            'time_offset'         => $res['time_offset'] / 10,
            'bigarea_id'          => explode('|', $res['bigarea_id']),
            'type'                => $res['type'],
            'vision_type'         => $res['vision_type'],
            'button_tag_id'       => $tag['id'] ?? 0,
            'audit_status'        => $this->getAuditStatus($res['status']),
            'status'              => $this->getStatus($res['status'], $res['start_time'], $res['end_time'] - $dataPeriod),
            'admin'               => Helper::getAdminName($res['admin_id']),
            'has_relate'          => (string)$res['has_relate'],
            'has_be_related'      => (string)$res['has_be_related'],
            'relate_type'         => (string)$res['relate_type'],
            'relate_id'           => $res['relate_id'] ?: '',
            'relate_icon'         => $res['relate_icon'],
            'relate_icon_all'     => Helper::getHeadUrl($res['relate_icon']),
            'vision_content_json' => $visionContentJson,
            'rule_content_json'   => $res['rule_content_json'],
        ];
        $list = BbcRankButtonList::getListByWhere([['act_id', '=', $id]], 'id, level, button_content, score_min', 'level asc');
        $listId = $list[0]['id'];
        if (BbcTemplateConfig::isWheelLotteryNewVersion($id)) {
            $list = array_column($list, null, 'id');
            $awardList = BbcActWheelLotteryReward::getListByWhere([['act_id', '=', $id]], '*', 'list_id asc');
            $awardConfig = [];
            foreach ($awardList as $item) {
                $scoreMin = $list[$item['list_id']]['score_min'] ?? 0;
                $awardConfig[] = [
                    'button_content'  => $list[$item['list_id']]['button_content'] ?? '',
                    'button_list_id'  => $item['list_id'],
                    'lottery_consume' => $item['lottery_consume'],
                    'award_id'        => $item['id'],
                    'score_min'       => $scoreMin,
                    'is_score'        => (string)($scoreMin ? BbcRankButtonList::IS_SCORE_YES : BbcRankButtonList::IS_SCORE_NO),
                    'award_list'      => $this->formatAwardList($item['award_list'] ?? ''),
                ];
            }
            $data['components_number'] = (string) count($awardConfig);
            $data['award_config'] = $awardConfig;
        } else {
            $award = BbcActWheelLotteryReward::findOneByWhere([['act_id', '=', $id]]);
            $data['button_list_id'] = $listId;
            $data['lottery_consume'] = $award['lottery_consume'];
            $data['award_list'] = $this->formatAwardList($award['award_list'] ?? '');
            $data['award_id'] = $award['id'] ?? 0;
        }
        $data['score_source'] = $this->formatScoreConfig($res['id'], $listId);
        return $data;
    }

    private function formatAwardList(string $awardList): array
    {
        if (empty($awardList)) {
            return [];
        }
        $awardList = json_decode($awardList, true);

        foreach ($awardList as &$item) {
            $item['id'] = (string)($item['id'] ?? 0);
            $item['type'] = (string)$item['type'];
            $item['stock_type'] = (string)($item['stock'] > 0 ? BbcActWheelLotteryReward::STOCK_TYPE_LIMIT : BbcActWheelLotteryReward::STOCK_TYPE_NO_LIMIT);
            $extend = $item['extend_info'] ?? [];
            if ($extend) {
                $item['give_type'] = (string)($extend['extend_type'] ?? 0);
                $item['content'] = $extend['content'] ?? '';
            }
            if ($item['type'] == BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD) {
                $item['expire_time'] = $item['exp_days'] ? Helper::now($item['exp_days']) : '';
                $extendInfo = $extend['open_screen_card_extend'] ?? [];
                $item['card_type'] = strval($extendInfo['card_type'] ?? '');
            }
            if ($item['type'] == BbcActWheelLotteryReward::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD) {
                $item['expire_time'] = $item['exp_days'] ? Helper::now($item['exp_days']) : '';
                $item['card_type'] = strval($extendInfo['card_type'] ?? '');
                $item['effective_days'] = $item['days'] ?? '';
            }
            if ($item['type'] == BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BG_CARD) {
                $extendInfo = $extend['room_bg_card_extend'] ?? [];
                $item['card_type'] = strval($extendInfo['card_type'] ?? '');
            }
            $item['effective_hours'] = $item['days'] ?? '';
            if ($item['type'] == BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD) {
                $item['effective_hours'] = $item['exp_days'] ?? '';
            }

        }

        return $awardList;
    }

    private function handleButtonList(array $config, array $baseData): array
    {
        $maxId = BbcRankButtonList::getMaxId();
        $levelIds = [];
        if ($config['add']) {
            foreach ($config['add'] as $key => &$item) {
                $item['id'] = $maxId + $key + 1;
                $levelIds[$item['level']] = $item['id'];
                $item = array_merge($item, $baseData);
            }
            [$res, $msg, $_] = BbcRankButtonList::addBatch($config['add']);
            if (!$res) {
                return [false, 'buttonList数据添加失败，失败原因：' . $msg];
            }
        }
        if ($config['edit']) {
            foreach ($config['edit'] as $k => $v) {
                $levelIds[$v['level']] = $k;
            }
            [$res, $msg, $_] = BbcRankButtonList::updateBatch($config['edit']);
            if (!$res) {
                return [false, 'buttonList数据修改失败，失败原因：' . $msg];
            }
        }
        if ($config['delete']) {
            [$res, $msg, $_] = BbcRankButtonList::deleteByWhere([['id', 'IN', $config['delete']]]);
            if (!$res) {
                return [false, 'buttonList数据删除失败，失败原因：' . $msg];
            }
        }
        return [true, $levelIds];
    }

    private function handleRankAward($config, $baseData): array
    {
        $listIds = $baseData['list_ids'] ?? [];
        unset($baseData['list_ids']);
        $now = time();
        if ($config['add']) {
            $addBatch = [];
            foreach ($config['add'] as $item) {
                $addBatch[] = [
                    'act_id'          => $baseData['act_id'],
                    'list_id'         => $listIds[$item['level']] ?? 0,
                    'award_list'      => $item['award_list'],
                    'lottery_consume' => $item['lottery_consume'],
                    'dateline'        => $now,
                ];
            }
            [$res, $msg, $_] = BbcActWheelLotteryReward::addBatch($addBatch);
            if (!$res) {
                return [false, '奖励配置数据添加失败，失败原因：' . $msg];
            }
        }
        if ($config['edit']) {
            [$res, $msg, $_] = BbcActWheelLotteryReward::updateBatch($config['edit']);
            if (!$res) {
                return [false, '奖励配置数据修改失败，失败原因：' . $msg];
            }
        }
        if ($config['delete']) {
            [$res, $msg, $_] = BbcActWheelLotteryReward::deleteByWhere([['id', 'IN', $config['delete']]]);
            if (!$res) {
                return [false, '奖励配置数据删除失败，失败原因：' . $msg];
            }
        }
        return [true, ''];
    }

    private function versifyOldVersion(array $awardList, array &$params, bool $isUpdate = false): void
    {
        $buttonListId = $awardList['button_list_id'] ?? 0;
        $rewardId = $awardList['award_id'] ?? 0;
        $this->verifyAwardList($awardList['award_list'] ?? []);
        // 兼容下新版本格式统一处理
        if (!isset($params['award_config'])) {
            $params['award_config'] = [
                [
                    'level'           => 1,
                    'button_list_id'  => $buttonListId,
                    'award_id'        => $rewardId,
                    'award_list'      => $awardList['award_list'],
                    'lottery_consume' => $awardList['lottery_consume'],
                    'button_content'  => '',
                    'is_score'        => 1,
                    'score_min'       => 0
                ]
            ];
        }
    }

    private function verifyNewVersion(array &$params, bool $isUpdate = false): void
    {
        $prevLotteryConsume = null;
        $prevScoreMin = null;
        $hasIsScore = false;
        if ($params['components_number'] != count($params['award_config'])) {
            throw new ApiException(ApiException::MSG_ERROR, '组件数量与奖励配置数量不一致');
        }
        foreach ($params['award_config'] as $key => &$awardConfig) {
            $awardConfig['level'] = $key + 1;
            $scoreMin = $awardConfig['score_min'] ?? 0;
            $this->versifyOldVersion($awardConfig, $params, $isUpdate);
            // 验证 lottery_consumes 是否从小到大依次填写
            if ($prevLotteryConsume !== null && $awardConfig['lottery_consume'] <= $prevLotteryConsume) {
                throw new ApiException(ApiException::MSG_ERROR, "单次消耗积分配置必须满足高级>中级>低级，请修改");
            }
            $prevLotteryConsume = $awardConfig['lottery_consume'];

            // 验证积分解锁门槛
            $awardConfig['is_score'] == BbcRankButtonList::IS_SCORE_YES && $hasIsScore = true;
            // 验证更高等级的解锁门槛
            if ($hasIsScore && $awardConfig['is_score'] == BbcRankButtonList::IS_SCORE_NO) {
                throw new ApiException(ApiException::MSG_ERROR, "有等级配置了解锁门槛时，其余更高等级必须配置门槛，请修改");
            }
            // 验证解锁门槛数值顺序
            if (!empty($scoreMin) && $scoreMin <= $prevScoreMin) {
                throw new ApiException(ApiException::MSG_ERROR, "资格解锁门槛数值配置必须满足高级>中级>低级，请修改");
            }
            $prevScoreMin = $scoreMin;
        }
    }

    private function verify(array &$params, bool $isUpdate = false): void
    {
        $id = $params['id'] ?? 0;
        $buttonTagId = $params['button_tag_id'] ?? 0;
        $relateId = $params['relate_id'] ?? 0;
        if ($isUpdate && (empty($id) || empty($buttonTagId))) {
            throw new ApiException(ApiException::MSG_ERROR, 'id信息错误');
        }
        $params['type'] = BbcTemplateConfig::TYPE_WHEEL_LOTTERY;
        if ($params['has_relate'] == BbcTemplateConfig::HAS_RELATE_NO) {
            $params['relate_type'] = 0;
            $params['relate_id'] = 0;
            $params['relate_icon'] = '';
        }
        $this->verifyRelateId($id, $relateId);
        $this->verifyScoreSource($params);
        if ($isUpdate && !BbcTemplateConfig::isWheelLotteryNewVersion($id)) {
            $this->versifyOldVersion($params, $params, $isUpdate);
        } else {
            $this->verifyVisionContent($params);
            $this->verifyNewVersion($params, $isUpdate);
        }
    }

    /**
     * 验证关联玩法id
     * @param int $id
     * @param int $relateId
     * @return void
     * @throws ApiException
     */
    private function verifyRelateId(int $id, int $relateId): void
    {
        if (empty($relateId)) {
            return;
        }
        $relationTemplate = BbcTemplateConfig::findOneByWhere([
            ['id', '<>', $id],
            ['relate_id', '=', $relateId],
        ]);

        if ($relationTemplate) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('每个玩法id只能被一个玩法绑定，而你绑定的玩法id已被其他玩法使用（玩法id：%d）', $relationTemplate['id']));
        }
    }

    private function verifyAwardList(array $awardList): void
    {
        $msg = [];
        foreach ($awardList as $key => $award) {
            $type = intval($award['type'] ?? 0);
            $stock = intval($award['stock'] ?? 0);
            $num = intval($award['num'] ?? 0);
            $days = intval($award['days'] ?? 0);
            $expDays = intval($award['exp_days'] ?? 0);
            $giveType = intval($award['give_type'] ?? 0);
            $effectiveHours = intval($award['effective_hours'] ?? 0);
            $expireTime = trim($award['expire_time'] ?? '');
            $effectiveDays = intval($award['effective_days'] ?? 0);
            // $cardType = intval($award['card_type'] ?? 0);
            if ($award['type'] == BbcActWheelLotteryReward::REWARD_TYPE_ACTIVITY_DIAMOND && empty($stock)) {
                throw new ApiException(ApiException::MSG_ERROR, '奖励为钻石时，抽出数量上限必须填写');
            }

            // vip 是否赠送非直接生效需要验证数量必填
            if ($type == BbcActWheelLotteryReward::REWARD_TYPE_VIP && $giveType != BbcActWheelLotteryReward::GIVE_TYPE_AUTO_EFFECT && $num < 1) {
                $msg[] = sprintf('奖励%d，%s必须为大于等于1的正整数', $key + 1, '数量');
            }

            if (in_array($type, [BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD, BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD]) && $effectiveHours < 1) {
                $msg[] = sprintf('奖励%d，%s必须为大于等于1的正整数', $key + 1, '有效小时数');
            }

            if (in_array($type, [BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD, BbcActWheelLotteryReward::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD]) && empty($expireTime)) {
                $msg[] = sprintf('奖励%d，%s必须填写', $key + 1, '过期时间');
            }

            if (in_array($type, [BbcActWheelLotteryReward::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD]) && empty($effectiveDays)) {
                $msg[] = sprintf('奖励%d，%s必须填写', $key + 1, '生效天数');
            }

            if (in_array($type, [
                    BbcActWheelLotteryReward::REWARD_TYPE_ACTIVITY_DIAMOND,
                    BbcActWheelLotteryReward::REWARD_TYPE_COMMODITY,
                    BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BG_CARD,
                    BbcActWheelLotteryReward::REWARD_TYPE_ROOM_TOP_CARD,
                    BbcActWheelLotteryReward::REWARD_TYPE_GIFT_BAG,
                    BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON,
                    BbcActWheelLotteryReward::REWARD_TYPE_PRETTY_ID_CARD,
                    BbcActWheelLotteryReward::REWARD_NAME_ID_LIGHTING,
                    BbcActWheelLotteryReward::REWARD_TYPE_MINI_CARD_DRESS,
                    BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD,
                    BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD,
                    BbcActWheelLotteryReward::REWARD_TYPE_HOMEPAGE_CARD,
                    BbcActWheelLotteryReward::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD,
                ]) && $num < 1) {
                $msg[] = sprintf('奖励%d，%s必须为大于等于1的正整数', $key + 1, '数量');
            }
            if (in_array($type, [
                    BbcActWheelLotteryReward::REWARD_TYPE_MEDAL,
                    BbcActWheelLotteryReward::REWARD_TYPE_VIP,
                    BbcActWheelLotteryReward::REWARD_TYPE_CERTIFICATION_ICON,
                    BbcActWheelLotteryReward::REWARD_TYPE_PRETTY_ID_CARD,
                    BbcActWheelLotteryReward::REWARD_TYPE_EMOTICONS,
                    BbcActWheelLotteryReward::REWARD_TYPE_ROOM_SKIN,
                    BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BACKGROUND,
                    BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BG_CARD,
                    BbcActWheelLotteryReward::REWARD_TYPE_ROOM_TOP_CARD,
                    BbcActWheelLotteryReward::REWARD_NAME_ID_LIGHTING,
                    BbcActWheelLotteryReward::REWARD_TYPE_MINI_CARD_DRESS,
                    BbcActWheelLotteryReward::REWARD_TYPE_HOMEPAGE_CARD,
                ]) && $days < 1) {
                $msg[] = sprintf('奖励%d，%s必须为大于等于1的正整数', $key + 1, '天数');
            }

            if (in_array($type, [
                    BbcActWheelLotteryReward::REWARD_TYPE_PRETTY_ID_CARD,
                    BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON,
                    BbcActWheelLotteryReward::REWARD_NAME_ID_LIGHTING,
                    BbcActWheelLotteryReward::REWARD_TYPE_MINI_CARD_DRESS,
                    BbcActWheelLotteryReward::REWARD_TYPE_COMMODITY,
                    BbcActWheelLotteryReward::REWARD_TYPE_HOMEPAGE_CARD,
                ]) && $expDays < 1) {
                $msg[] = sprintf('奖品%d，%s必须为大于等于1的正整数', $key + 1, '资格有效天数');
            }

            // if (in_array($type, [
            //         BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BG_CARD,
            //         BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD,
            //     ]) && $cardType < 0) {
            //     $msg[] = sprintf('奖励%d，%s必须填写', $key + 1, '类型');
            // }
        }

        if ($msg) {
            throw new ApiException(ApiException::MSG_ERROR, implode("；", $msg));
        }
    }

    // 验证视觉内容配置
    private function verifyVisionContent($params): void
    {
        $requiredFieldArr = $this->getVisionContentJsonRequestField($params['vision_type'], $params['components_number']);

        foreach ($requiredFieldArr as $field) {
            if (empty($params['vision_content_json'][$field])) {
                throw new ApiException(ApiException::MSG_ERROR, "活动视觉配置有字段未填，请检查");
            }
        }
    }

    // 根据视频类型及组件数量获取视觉内容配置中必填字段
    private function getVisionContentJsonRequestField(int $visionType, int $componentsNumber): array
    {
        $key = $visionType . $componentsNumber;
        $map = [
            81 => [
                'task_button_img_vc', 'bgc_img_vc', 'wheel_lottery_img_vc', 'button_img_vc', 'barrage_img_vc', 'pointer_img_vc', 'turntable_img_vc'
            ],
            82 => [
                'task_button_img_vc', 'long_button_select_img_vc', 'long_button_no_select_img_vc', 'bgc_img_vc',
                "low_wheel_lottery_img_vc", "low_button_img_vc", "low_barrage_img_vc", "low_pointer_img_vc", "low_turntable_img_vc",
                "middle_wheel_lottery_img_vc", "middle_button_img_vc", "middle_barrage_img_vc", "middle_pointer_img_vc", "middle_turntable_img_vc"
            ],
            83 => [
                "task_button_img_vc", "short_button_select_img_vc", "short_button_no_select_img_vc", "bgc_img_vc",
                "low_wheel_lottery_img_vc", "low_button_img_vc", "low_barrage_img_vc", "low_pointer_img_vc", "low_turntable_img_vc",
                "middle_wheel_lottery_img_vc", "middle_button_img_vc", "middle_barrage_img_vc", "middle_pointer_img_vc", "middle_turntable_img_vc",
                "high_wheel_lottery_img_vc", "high_button_img_vc", "high_barrage_img_vc", "high_pointer_img_vc", "high_turntable_img_vc"
            ],
            91 => [
                "task_button_img_vc", "bgc2_img_vc", "twisted_egg_img_vc", "twisted_egg_one2_img_vc", "twisted_egg_two2_img_vc", "rotation_btn2_img_vc", "barrage2_img_vc",
            ],
            92 => [
                "task_button_img_vc", "long_button_select_img_vc", "long_button_no_select_img_vc", "bgc2_img_vc",
                "low_twisted_egg_img_vc", "low_twisted_egg_one2_img_vc", "low_twisted_egg_two2_img_vc", "low_rotation_btn2_img_vc", "low_barrage2_img_vc", "middle_twisted_egg_img_vc",
                "middle_twisted_egg_one2_img_vc", "middle_twisted_egg_two2_img_vc", "middle_rotation_btn2_img_vc", "middle_barrage2_img_vc",
            ],
            93 => [
                "task_button_img_vc", "short_button_select2_img_vc", "short_button_no_select2_img_vc", "bgc2_img_vc",
                "low_twisted_egg_img_vc", "low_twisted_egg_one2_img_vc", "low_twisted_egg_two2_img_vc", "low_rotation_btn2_img_vc", "low_barrage2_img_vc",
                "middle_twisted_egg_img_vc", "middle_twisted_egg_one2_img_vc", "middle_twisted_egg_two2_img_vc", "middle_rotation_btn2_img_vc", "middle_barrage2_img_vc",
                "high_twisted_egg_img_vc", "high_twisted_egg_one2_img_vc", "high_twisted_egg_two2_img_vc", "high_rotation_btn2_img_vc", "high_barrage2_img_vc"
            ],
        ];

        return $map[$key] ?? [];
    }

    public function getOptions()
    {
        $service = new StatusService();

        $language = $service->getLanguageNameMap(null, 'label,value');

        $sourceType = [
            ['label' => '收送礼', 'value' => BbcRankScoreConfigNew::SOURCE_TYPE_GIFT],
            ['label' => '充值', 'value' => BbcRankScoreConfigNew::SOURCE_TYPE_TOP_UP],
            ['label' => '游戏', 'value' => BbcRankScoreConfigNew::SOURCE_TYPE_GAMES],
        ];

        $scoreType = [
            BbcRankScoreConfigNew::SOURCE_TYPE_GIFT   => [
                ['label' => '送出礼物钻石数', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_PAY_GIFT],
                ['label' => '送出指定礼物钻石数', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_PAY_GIFT_ID],
                ['label' => '送出指定礼物个数', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_PAY_GIFT_NUM],
                ['label' => '收到礼物钻石数', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_ACCEPT_GIFT],
                ['label' => '收到指定礼物钻石数', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_ACCEPT_GIFT_ID],
                ['label' => '收到指定礼物个数', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_ACCEPT_GIFT_NUM],
                ['label' => '幸运礼物赢取钻石', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_LUCKY_GIFT_WIN]
            ],
            BbcRankScoreConfigNew::SOURCE_TYPE_TOP_UP => [
                ['label' => '充值钻石数', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_TOP_UP_DIAMOND],
            ],
            BbcRankScoreConfigNew::SOURCE_TYPE_GAMES  => [
                ['label' => '赢取钻石数', 'value' => BbcRankScoreConfigNew::SCORE_TYPE_GAME_WIN],
            ],
        ];
        $scoreTypeNew = StatusService::formatMap(BbcRankScoreConfigNew::$scoreTypeMap, 'label,value');
        $bigAreaId = $service->getFamilyBigArea(null, 'label,value');

        $awardType = StatusService::formatMap(BbcActWheelLotteryReward::$rewardTypeMap, 'label,value');
        $scoreScope = BbcRankScoreConfigNew::getOptions(BbcRankScoreConfigNew::$sourceTypeAndScoreScopeMap, BbcRankScoreConfigNew::$scoreScopeMap);
        $vipDays = StatusService::formatMap(XsUserProfile::$vipDaysMap, 'label,value');

        $visionType = [
            ['label' => '转盘', 'value' => BbcTemplateConfig::VISION_TYPE_WHEEL_LOTTERY],
            ['label' => '扭蛋机', 'value' => BbcTemplateConfig::VISION_TYPE_EGG_TWISTING_MACHINE],
        ];

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
            ['label' => '-4', 'value' => -4],
        ];
        $isScore = StatusService::formatMap(BbcRankButtonList::$isScoreMap);
        $stockType = StatusService::formatMap(BbcActWheelLotteryReward::$stockTypeMap, 'label,value');
        $hasRelate = $this->getHasRelateMap();
        $giveType = [
            BbcActWheelLotteryReward::REWARD_TYPE_VIP                      => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeVipMap, 'label,value'),
            BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BG_CARD             => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            BbcActWheelLotteryReward::REWARD_NAME_ID_LIGHTING              => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            BbcActWheelLotteryReward::REWARD_TYPE_MINI_CARD_DRESS          => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            BbcActWheelLotteryReward::REWARD_TYPE_HOMEPAGE_CARD            => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            BbcActWheelLotteryReward::REWARD_TYPE_PRETTY_ID_CARD           => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypePrettuMap, 'label,value'),
            BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD         => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            BbcActWheelLotteryReward::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
        ];
        $cardType = [
            BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BG_CARD => (new CustomBgcCardSendService())->getCardTypeMap(),
            BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD => (new OpenScreenCardService())->getTypeMap(),  
        ];
        $content = XsCertificationSign::getContentMap();
        $effectiveHours = self::getEffectiveHoursMap();
        $effectiveDays = self::getEffectiveDaysMap();

        $relateType = $this->getRelateTaskTypeMap();

        $relateId = [
            // BbcTemplateConfig::ACT_TEMPLATE_TYPE_TASK => $this->getRelateIdMap(BbcTemplateConfig::ACT_TEMPLATE_TYPE_TASK),
            // BbcTemplateConfig::ACT_TEMPLATE_TYPE_MUTLI_TASK => $this->getRelateIdMap(BbcTemplateConfig::ACT_TEMPLATE_TYPE_MUTLI_TASK),
        ];

        $relateType = $this->getRelateTaskTypeMap();

        $relateId = [
            BbcTemplateConfig::ACT_TEMPLATE_TYPE_TASK => $this->getRelateIdMap(BbcTemplateConfig::ACT_TEMPLATE_TYPE_TASK),
            BbcTemplateConfig::ACT_TEMPLATE_TYPE_MUTLI_TASK => $this->getRelateIdMap(BbcTemplateConfig::ACT_TEMPLATE_TYPE_MUTLI_TASK),
        ];

        return compact(
            'language',
            'sourceType',
            'bigAreaId',
            'timeOffset',
            'awardType',
            'scoreType',
            'scoreScope',
            'stockType',
            'hasRelate',
            'giveType',
            'content',
            'visionType',
            'isScore',
            'scoreTypeNew',
            'relateType',
            'relateId',
            'effectiveHours',
            'vipDays',
            'cardType',
            'effectiveDays'
        );
    }

    public function getAwardTypeMap(): array
    {
        return StatusService::formatMap(BbcActWheelLotteryReward::$rewardTypeMap, 'label,value');
    }

    public function getAwardOptions(): array
    {
        $service = new StatusService();
        $giftBag = $service->getGiftBagMap(null, 'label,value');
        $commodity = $service->getCommodityMap(null, 'label,value');
        $medal = $service->getMedalMap(null, 'label,value');
        $background = $service->getActivityBackgroundMap(null, 'label,value');
        $roomTopCard = $service->getRoomTopCardMap(null, 'label,value');
        $vip = $service->getVipMap(null, 'label,value');
        $emoticons = XsEmoticons::getOptions();
        $prettyCard = $service->getPrettyCardMap(null, 'label,value');
        $certification = $service->getCertificationMap(null, 'label,value');
        $roomSkin = $service->getRoomSkinMap(null, 'label,value');
        $xsCoupon = StatusService::formatMap(XsCoupon::getCouponMap(), 'label,value');
        $nameIdLighting = NameIdLightingLogService::getGroupIdMap(null, 'label,value');
        $miniCard = (new MiniCardSendService())->getCardMap();
        $homepageCard = (new MiniCardSendService())->getCardMap(XsItemCard::TYPE_HOMEPAGE);
        $propCard = StatusService::formatMap(XsPropCard::getPkPropCardOptions());

        return [
            BbcActWheelLotteryReward::REWARD_TYPE_COMMODITY          => $commodity,
            BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON        => $xsCoupon,
            BbcActWheelLotteryReward::REWARD_TYPE_GIFT_BAG           => $giftBag,
            BbcActWheelLotteryReward::REWARD_TYPE_MEDAL              => $medal,
            BbcActWheelLotteryReward::REWARD_TYPE_ROOM_SKIN          => $roomSkin,
            BbcActWheelLotteryReward::REWARD_TYPE_VIP                => $vip,
            BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BACKGROUND    => $background,
            BbcActWheelLotteryReward::REWARD_TYPE_PRETTY_ID_CARD     => $prettyCard,
            BbcActWheelLotteryReward::REWARD_TYPE_EMOTICONS          => $emoticons,
            BbcActWheelLotteryReward::REWARD_TYPE_ROOM_TOP_CARD      => $roomTopCard,
            BbcActWheelLotteryReward::REWARD_TYPE_CERTIFICATION_ICON => $certification,
            BbcActWheelLotteryReward::REWARD_NAME_ID_LIGHTING        => $nameIdLighting,
            BbcActWheelLotteryReward::REWARD_TYPE_MINI_CARD_DRESS    => $miniCard,
            BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD          => $propCard,
            BbcActWheelLotteryReward::REWARD_TYPE_HOMEPAGE_CARD      => $homepageCard,
        ];
    }

    protected function onAfterList($list)
    {
        $actIdArr = array_column($list, 'id');
        $buttonTags = BbcRankButtonTag::getListByWhere([['act_id', 'IN', $actIdArr]], 'act_id, rank_object');
        $buttonTags = array_column($buttonTags, 'rank_object', 'act_id');
        $stockRecord = XsstWheelLotteryAwardAuditRecord::getListByWhere([
            ['act_id', 'IN', $actIdArr]
        ], 'act_id, count(*) AS count', 'act_id desc', 0, 0, 'act_id');
        $stockRecord = array_column($stockRecord, 'count', 'act_id');
        $relateList = BbcTemplateConfig::getListByWhere([['relate_id', 'IN', $actIdArr]], 'id, relate_id');
        $relateList = array_column($relateList, 'id', 'relate_id');
        foreach ($list as &$item) {
            $item['page_url'] = $this->getPageUrl($item['id'], $item['vision_type'], $item['page_url']);
            $item['page_url'] = [
                'title'        => $item['page_url'],
                'value'        => $item['page_url'],
                'type'         => 'url',
                'url'          => $item['page_url'],
                'resourceType' => 'static'
            ];
            $item['is_diamond'] = $this->isDiamondAward($item['id']);
            $item['is_pub'] = $this->setIsPublish($item['status']);
            $dataPeriod = intval($item['data_period']) * 86400;
            $timeOffset = (8 - intval($item['time_offset']) / 10) * 3600;
            $starTime = intval($item['start_time']) - $timeOffset;
            $endTime = intval($item['end_time']) - $timeOffset - $dataPeriod;
            $item['bigarea_id'] = XsBigarea::formatBigAreaName($item['bigarea_id']);
            $item['admin_id'] = $item['admin_id'] . '-' . Helper::getAdminName($item['admin_id']);
            $item['publisher'] = $item['publisher_id'] > 0 ? $item['publisher_id'] . '-' . Helper::getAdminName($item['publisher_id']) : '';
            $item['dateline'] = Helper::now($item['dateline']);
            $item['activity_time'] = Helper::now($starTime) . '-<br />' . Helper::now($endTime);
            $item['audit_status'] = $this->getAuditStatus($item['status']);
            $item['status'] = $this->getStatus($item['status'], $item['start_time'], $item['end_time'] - $dataPeriod);
            $item['is_stock'] = ($stockRecord[$item['id']] ?? 0) ? 1 : 0;
            $item['status_text'] = $this->setStatusText($item['status']);
            $item['audit_status_text'] = $this->setAuditStatusText($item['audit_status']);
            $item['rank_object'] = BbcRankButtonTag::$rankObjectMap[$buttonTags[$item['id']] ?? 0] ?? '';
            $item['is_new'] = BbcTemplateConfig::isWheelLotteryNewVersion($item['id']) ? 1 : 0;
            $item['be_relate_id'] = $relateList[$item['id']] ?? '';
        }

        return $list;
    }

    public function setPageUrl($type, $id): string
    {
        $prefix = ENV == 'dev' ? self::DEV_URL : self::PROD_URL;

        $pageUrl = '';
        switch ($type) {
            case BbcTemplateConfig::VISION_TYPE_WHEEL_LOTTERY:
                $pageUrl = sprintf(self::PAGE_URL, $prefix, $id, '');
                break;
            case BbcTemplateConfig::VISION_TYPE_EGG_TWISTING_MACHINE:
                $pageUrl = sprintf(self::PAGE_URL, $prefix, $id, '#/GashaponMachine');
                break;
        }

        return $pageUrl;
    }

    protected function isDiamondAward(int $actId): int
    {
        $isDiamond = 0;
        $awardList = BbcActWheelLotteryReward::getListByWhere([['act_id', '=', $actId]]);
        foreach ($awardList as $award) {
            if ($isDiamond) {
                return $isDiamond;
            }
            $awardArr = json_decode($award['award_list'], true);
            $awardType = array_column($awardArr, 'type');
            if (in_array(BbcActWheelLotteryReward::REWARD_TYPE_ACTIVITY_DIAMOND, $awardType) ||
                in_array(BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON, $awardType)) {
                //游戏优惠券也需要OA
                $isDiamond = 1;
            }
        }

        // 判断关联的其他玩法是否存在钻石｜游戏优惠券
        if ($isDiamond == 0) {
            $template = BbcTemplateConfig::findOne($actId);
            if ($template && $template['relate_id']) {
                $awardList = BbcRankAward::findOneByWhere([
                    ['act_id', '=', $template['relate_id']],
                    ['award_type', 'IN', [BbcRankAward::AWARD_TYPE_DIAMOND, BbcRankAward::AWARD_TYPE_GAME_COUPON]],
                ]);
                if ($awardList) {
                    $isDiamond = 1;
                }
            }
        }

        return $isDiamond;
    }

    protected function getFields(): array
    {
        return [
            'id', 'title', 'start_time', 'end_time', 'time_offset',
            'bigarea_id', 'admin_id', 'dateline', 'language', 'status',
            'data_period', 'publisher_id', 'has_relate', 'page_url',
            'vision_type', 'relate_type', 'has_be_related', 'relate_id'
        ];
    }

    public function getAwardList(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $listId = intval($params['list_id'] ?? 0);
        $query = [
            'act_id'  => $id,
            'list_id' => $listId
        ];
        list($res, $msg, $data) = (new PsService())->actWheelLotteryGetWeightInfo($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        foreach ($data as &$item) {
            $record = $this->getAuditStockStatus($id, $query['list_id'], $item['number']);
            $item['left_num'] = $item['stock_num'] ? $item['left_num'] : '不限';
            $item['is_show'] = $item['stock_num'] ? 1 : 0;
            $item['stock_num'] = $item['stock_num'] ?: '不限';
            $item['is_disabled'] = $this->getIsDisabled($record['status'] ?? 0);
            $item['replenish_num'] = $record['number'] ?? '';
            $item['probability'] = sprintf('%.2f', $item['probability'] / 100) . '%';
            $item['award_type_text'] = BbcActWheelLotteryReward::$rewardTypeMap[$item['award_type']];
        }

        return $data;
    }

    /**
     * 获取玩法等级tab
     *
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function getAwardTabList(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动id错误');
        }

        $list = BbcRankButtonList::getListByWhere([['act_id', '=', $id]], 'id, level', 'level asc');

        $map = [];
        foreach ($list as $item) {
            $level = BbcRankButtonList::$wheelLotteryLevelMap[$item['level']] ?? '';
            if ($level) {
                $map[] = [
                    'label' => $level . '玩法',
                    'value' => $item['id']
                ];
            }
        }

        return $map;
    }

    /**
     * 记录是否禁用
     * @param int $status
     * @return int
     */
    private function getIsDisabled(int $status): int
    {
        if ($status == 1) {
            return 1;
        }
        return 0;
    }

    /**
     * 获取奖励是否存在审核中状态
     *
     * @param $id
     * @param $listId
     * @param $number
     * @return array
     */
    private function getAuditStockStatus($id, $listId, $number): array
    {
        return XsstWheelLotteryAwardAuditRecord::findOneByWhere([
            ['act_id', '=', $id],
            ['list_id', '=', $listId],
            ['award_number', '=', $number],
            ['status', '=', XsstWheelLotteryAwardAuditRecord::STATUS_WAIT]
        ]);
    }

    public function getAwardHistory(array $params): array
    {
        $conditions = [];
        if (isset($params['act_id']) && !empty($params['act_id'])) {
            $conditions[] = ['act_id', '=', $params['act_id']];
        }
        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['act_bigarea_id', 'FIND_IN_SET', $params['bigarea_id']];
        }
        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['object_id', '=', $params['uid']];
        }
        if (isset($params['award_number']) && !empty($params['award_number'])) {
            if (!isset($params['act_id'])) {
                throw new ApiException(ApiException::MSG_ERROR, '奖品等级存在时必须筛选活动ID');
            }
            $conditions[] = ['award_number', '=', $params['award_number']];
        }
        if (isset($params['cid']) && !empty($params['cid'])) {
            $conditions[] = ['cid', '=', $params['cid']];
        }

        $list = XsActWheelLotteryAwardList::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        $userBigAreaList = XsUserBigarea::getUserBigAreaBatch(Helper::arrayFilter($list['data'], 'object_id'));
        $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($list['data'], 'user_bid'), ['bid', 'bname'], 'bname');
        $levelList = BbcRankButtonList::getListByWhere([
            ['id', 'IN', Helper::arrayFilter($list['data'], 'list_id')]
        ], 'id, level');
        $levelList = array_column($levelList, 'level', 'id');
        foreach ($list['data'] as &$item) {
            $userBigArea = $userBigAreaList[$item['object_id']] ?? [];
            $item['uid'] = $item['object_id'];
            $item['bigarea_id'] = XsBigarea::formatBigAreaName($item['act_bigarea_id'], ',');
            $item['user_bigarea_id'] = XsBigarea::formatBigAreaName($userBigArea['bigarea_id'] ?? 0, ',');
            $item['bname'] = $brokerList[$item['user_bid']] ?? '';
            if ($item['award_type'] == BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD) {
                $item['award_type'] = XsActRankCommodityLog::$propCardTypeMap[$item['extend_type'] ?? ''] ?? '';
            } else {
                $item['award_type'] = BbcActWheelLotteryReward::$rewardTypeAllMap[$item['award_type']] ?? '';
            }
            $item['dateline'] = Helper::now($item['dateline']);
            $item['level'] = $levelList[$item['list_id']] ?? '';
            $item['level'] = BbcRankButtonList::$wheelLotteryLevelMap[$item['level']] ?? '';
        }

        return $list;
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
        $list = BbcRankButtonList::getListByWhere([
            ['act_id', '=', $template['id']]
        ]);
        $levels = array_column($list, 'level', 'id');
        $timeOffset = (8 - intval($template['time_offset']) / 10) * 3600;
        $dataPeriod = intval($template['data_period']) * 86400;

        $awardConfig = BbcActWheelLotteryReward::getListByWhere([
            ['act_id', '=', $template['id']],
        ]);
        $lotteryConsumes = array_column($awardConfig, 'lottery_consume', 'list_id');

        return [
            'act_id'           => $template['id'],
            'act_name'         => $template['title'],
            'start_time'       => $template['start_time'] - $timeOffset,
            'end_time'         => $template['end_time'] - $timeOffset - $dataPeriod,
            'levels'           => $levels,
            'lottery_consumes' => $lotteryConsumes,
            'level_prefix'     => [1 => 'low_', 2 => 'middle_', 3 => 'high_'],
        ];
    }

    public function getLuckDataList(array $params): array
    {
        $listId = array_search(1, $params['levels']);
        $lotteryConsume = $params['lottery_consumes'][$listId] ?? 1;

        $conditions = [
            ['act_id', '=', $params['act_id']],
            ['list_id', '=', $listId],
        ];

        $list = XsActivityScoreWallet::getListAndTotal(array_merge($conditions, [['score_type', '=', XsActivityScoreWallet::SCORE_TYPE_WHEEL_LOTTERY]]), '*', 'id desc', $params['page'], $params['limit'])['data'];
        if (empty($list)) {
            return $list;
        }
        $userList = XsUserProfile::getUserProfileBatch(Helper::arrayFilter($list, 'uid'), ['uid', 'name', 'sex']);
        $exportList = [];
        $awardList = $this->getExportAwardList($conditions);
        foreach ($list as $item) {
            $user = $userList[$item['uid']] ?? [];
            $userName = $user['name'] ?? '';
            $sex = self::$sexMap[$user['sex'] ?? 0];
            $useScore = $item['total_count'] - $item['availble_count'];
            $awardNumber = $awardList[$item['uid']] ?? [];

            $tmp = [
                'uid'           => $item['uid'],
                'user_name'     => $userName,
                'sex'           => $sex,
                'total_score'   => $item['total_count'],
                'surplus_score' => $item['availble_count'],
                'use_score'     => $useScore,
                'use_number'    => intval($useScore / $lotteryConsume),
            ];
            for ($i = 1; $i <= 10; $i++) {
                $key = 'award' . $i . '_num';
                $tmp[$key] = $awardNumber[$key] ?? 0;
            }
            $exportList[] = $tmp;
        }
        return $exportList;
    }

    public function getLuckDataNewList(array $params): array
    {
        $exportList = [];
        foreach ($params['levels'] as $listId => $level) {
            $conditions = [
                ['act_id', '=', $params['act_id']],
                ['list_id', '=', $listId],
            ];

            // 获取当前玩法下的中奖次数和积分
            $useScoreList = $this->getExportUseScoreList($conditions, $params['page'], $params['limit']);
            if ($level == 1) {
                $totalScoreList = XsActivityScoreWallet::getListByWhere(array_merge($conditions, [['score_type', '=', XsActivityScoreWallet::SCORE_TYPE_WHEEL_LOTTERY]]), 'uid, total_count, availble_count');
                $totalScoreList = array_column($totalScoreList, null, 'uid');
                $userList = XsUserProfile::getUserProfileBatch(array_keys($useScoreList), ['uid', 'name', 'sex']);
            }
            foreach ($useScoreList as $uid => $item) {
                $prefix = $params['level_prefix'][$level];

                $tmp = [
                    $prefix . 'use_score'  => $item['use_score'],
                    $prefix . 'use_number' => $item['use_number'],
                ];

                if ($level == 1) {
                    $user = $userList[$uid] ?? [];
                    $userName = $user['name'] ?? '';
                    $sex = self::$sexMap[$user['sex'] ?? 0] ?? '';
                    $totalScore = $totalScoreList[$uid]['total_count'] ?? '';
                    $surplusScore = $totalScoreList[$uid]['availble_count'] ?? '';

                    $tmp['uid'] = $uid;
                    $tmp['user_name'] = $userName;
                    $tmp['sex'] = $sex;
                    $tmp['total_score'] = $totalScore;
                    $tmp['surplus_score'] = $surplusScore;
                }

                $exportList[$uid] = array_merge($exportList[$uid] ?? [], $tmp);
            }
        }
        return array_values($exportList);
    }

    /**
     * 获取每个用户的使用积分和使用次数
     * @param array $conditions
     * @param int $page
     * @param int $limit
     * @return array
     */
    private function getExportUseScoreList(array $conditions, int $page, int $limit): array
    {
        $list = XsActWheelLotteryAwardList::getListAndTotal($conditions, 'object_id, num, lottery_consume', 'id desc', $page, $limit)['data'];

        $data = [];
        // 计算每个用户的使用积分（lottery_consume * num）和使用次数（num）
        foreach ($list as $item) {
            $uniqueKey = $item['object_id'];
            if (isset($data[$uniqueKey])) {
                $data[$uniqueKey]['use_score'] += $item['lottery_consume'] * $item['num'];
                $data[$uniqueKey]['use_number'] += $item['num'];
            } else {
                $data[$uniqueKey] = [
                    'use_score'  => $item['lottery_consume'] * $item['num'],
                    'use_number' => $item['num'],
                ];
            }
        }

        return $data;
    }


    private function getExportAwardList(array $conditions): array
    {
        $awardList = XsActWheelLotteryAwardList::getListByWhere($conditions);
        $data = [];
        $awardNum = [];
        foreach ($awardList as $award) {
            $key = $award['object_id'] . '_' . $award['award_number'];
            if (isset($awardNum[$key])) {
                $awardNum[$key] += $award['num'];
            } else {
                $awardNum[$key] = $award['num'];
            }
        }
        foreach ($awardList as $award) {
            if (!isset($data[$award['object_id']])) {
                // 设置中奖数量默认值
                for ($i = 1; $i <= 10; $i++) {
                    $key = 'award' . $i . '_num';
                    $data[$award['object_id']][$key] = $awardNum[$award['object_id'] . '_' . $i] ?? 0;
                }
            }
        }
        return $data;
    }

    public function awardModify(array $params): array
    {
        $id = (int)$params['id'];
        $recordData = [];
        $activity = BbcTemplateConfig::findOne($id);
        if (empty($activity)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }

        $listIds = Helper::arrayFilter($params['award_list'], 'list_id');

        $updateAwardTypes = Helper::arrayFilter($params['award_list'], 'award_type');

        // 验证是否存在待审核的数据
        if (in_array(BbcActWheelLotteryReward::REWARD_TYPE_ACTIVITY_DIAMOND, $updateAwardTypes) || in_array(BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON, $updateAwardTypes)) {
            $stockRecord = XsstWheelLotteryAwardAuditRecord::findOneByWhere([
                ['act_id', '=', $id],
                ['list_id', 'IN', $listIds],
                ['status', '=', XsstWheelLotteryAwardAuditRecord::STATUS_WAIT]
            ]);

            if ($stockRecord) {
                throw new ApiException(ApiException::MSG_ERROR, '当前活动已存在待审核数据无法修改，请审核结束后在操作');
            }
        }

        $rewardList = BbcActWheelLotteryReward::getListByWhere([
            ['act_id', '=', $id],
            ['list_id', 'IN', $listIds]
        ]);

        if (empty($rewardList)) {
            throw new ApiException(ApiException::MSG_ERROR, '奖励不存在');
        }

        $rewardList = array_column($rewardList, null, 'list_id');
        $oldRewardList = $rewardList;
        $now = time();
        foreach ($params['award_list'] as $award) {
            $awardList = @json_decode($rewardList[$award['list_id']]['award_list'], true);
            $awardArr = array_column($awardList, null, 'number');
            $beforeNumber = $awardArr[$award['award_number']]['stock'] ?? 0;
            $status = XsstWheelLotteryAwardAuditRecord::STATUS_DEFAULT;
            if (in_array($award['award_type'], [BbcActWheelLotteryReward::REWARD_TYPE_ACTIVITY_DIAMOND, BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON])) {
                $status = XsstWheelLotteryAwardAuditRecord::STATUS_WAIT;
            } else {
                !empty($awardArr[$award['award_number']]) && $awardArr[$award['award_number']]['stock'] += $award['number'];
            }
            $recordData[] = [
                'act_id'        => $id,
                'list_id'       => $award['list_id'],
                'award_number'  => $award['award_number'],
                'before_number' => $beforeNumber,
                'number'        => $award['number'],
                'award_type'    => $award['award_type'],
                'status'        => $status,
                'desc_path'     => $params['desc_path'] ?? '',
                'admin_id'      => $params['admin_id'],
                'dateline'      => $now,
            ];

            $rewardList[$award['list_id']]['award_list'] = json_encode(array_values($awardArr));
        }
        $awardInfo = [];
        foreach ($listIds as $listId) {
            $awardInfo[] = [
                'list_id' => (int) $listId,
                'award_info' => $rewardList[$listId]['award_list']
            ];
        }
        $data = [
            'act_id'     => (int)$id,
            'award_info' => $awardInfo,
        ];
        list($res, $msg) = (new PsService())->actWheelLotterySetAward($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        [$recordRes, $recordMsg, $rows] = XsstWheelLotteryAwardAuditRecord::addBatch($recordData);
        if (!$recordRes) {
            throw new ApiException(ApiException::MSG_ERROR, 'record 写入失败，' . $recordMsg);
        }

        if (in_array(XsstWheelLotteryAwardAuditRecord::STATUS_WAIT, array_column($recordData, 'status'))) {
            $type = XsstActiveKingdeeRecord::TYPE_WHEEL_LOTTERY_STOCK;
            $record = XsstActiveKingdeeRecord::findOneByWhere([
                ['business_id', '=', $id],
                ['type', '=', $type],
                ['is_handle', '=', XsstActiveKingdeeRecord::WAIT_STATUS]
            ]);
            if (empty($record)) {
                XsstActiveKingdeeRecord::add([
                    'business_id' => $id,
                    'type'        => $type,
                    'create_time' => $now,
                ]);
            }

            NsqClient::publish(NsqConstant::TOPIC_KING_ACTIVITY, [
                'cmd'  => 'submit_activity',
                'data' => ['id' => $id, 'type' => $type],
            ]);
        }

        return ['id' => $id, 'after_json' => ['award_list' => $rewardList], 'before_json' => $oldRewardList];
    }

    public function getStockRecordList(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $listId = intval($params['list_id'] ?? 0);
        $conditions = [
            ['act_id', '=', $id],
            ['list_id', '=', $listId],
            ['status', 'IN', [XsstWheelLotteryAwardAuditRecord::STATUS_DEFAULT, XsstWheelLotteryAwardAuditRecord::STATUS_SUCCESS]]
        ];

        $list = XsstWheelLotteryAwardAuditRecord::getListAndTotal($conditions, '*', 'dateline desc,id desc', $params['page'], $params['limit']);

        if (empty($list['data'])) {
            return $list;
        }

        $adminList = CmsUser::getUserNameList(array_column($list['data'], 'admin_id'));
        foreach ($list['data'] as &$item) {
            $item['after_number'] = $item['before_number'] + $item['number'];
            $item['award_number'] = '奖品' . $item['award_number'];
            $item['award_type_text'] = BbcActWheelLotteryReward::$rewardTypeMap[$item['award_type']] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
            $item['admin_name'] = $adminList[$item['admin_id']] ?? '';
        }

        return $list;
    }

    /**
     * 获取关联模版类型映射
     * @return array
     */
    public function getRelateTaskTypeMap(): array
    {
        return StatusService::formatMap(BbcTemplateConfig::$actTemplateTypeMap);
    }
}