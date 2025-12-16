<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Config\BaseModel;
use Imee\Models\Config\BbcActWheelLotteryReward;
use Imee\Models\Config\BbcRankAward;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankScoreConfigNew;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsActivityScoreWallet;
use Imee\Models\Xs\XsActRankAwardUser;
use Imee\Models\Xs\XsActTaskAwardList;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsChatroomMaterial;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsEmoticons;
use Imee\Models\Xs\XsGiftBag;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xs\XsUserIntimateRelation;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Operate\Lighting\NameIdLightingLogService;
use Imee\Service\Operate\Minicard\MiniCardSendService;
use Imee\Service\Operate\Relieveforbiddencard\RelieveForbiddenCardSendService;
use Imee\Service\StatusService;
use Phalcon\Di;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardSendService;
use Imee\Service\Operate\Minicard\MiniCardService;
use Imee\Service\Operate\User\OpenScreenCardService;

class ActivityTaskGamePlayService extends ActivityService
{
    const PAGE_URL = '%s/%s-template/?aid=%d&clientScreenMode=1%s';

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
            $update = [
                'page_url' => $this->setPageUrl($data['templateConfig']['vision_type'], $actId),
            ];
            BbcTemplateConfig::edit($actId, $update);
            $baseData['act_id'] = $actId;
            [$tagRes, $tagId] = BbcRankButtonTag::add(array_merge($baseData, $data['buttonTag']));
            if (!$tagRes) {
                return [false, 'buttonTag数据添加失败，失败原因：' . $tagId];
            }
            [$listRes, $listId] = BbcRankButtonList::add(array_merge($baseData, ['button_tag_id' => $tagId], $data['buttonList']));
            if (!$listRes) {
                return [false, 'buttonList数据添加失败，失败原因：' . $listId];
            }
            $baseData['button_list_id'] = $listId;
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
        $this->verify($params);
        $data = $this->formatData($params);
        $baseData = [
            'dateline' => time(),
            'admin_id' => Helper::getSystemUid()
        ];
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            $data['templateConfig']['page_url'] = $this->setPageUrl($data['templateConfig']['vision_type'], $params['id']);
            [$actRes, $actMsg] = BbcTemplateConfig::edit($params['id'], $data['templateConfig']);
            if (!$actRes) {
                return [false, '活动模版数据修改失败，失败原因：' . $actMsg];
            }
            $baseData['act_id'] = $params['id'];
            [$tagRes, $tagMsg] = BbcRankButtonTag::edit($params['button_tag_id'], $data['buttonTag']);
            if (!$tagRes) {
                return [false, 'buttonTag数据修改失败，失败原因：' . $tagMsg];
            }
            [$listRes, $listMsg] = BbcRankButtonList::edit($params['button_list_id'], $data['buttonList']);
            if (!$listRes) {
                return [false, 'buttonList数据修改失败，失败原因：' . $listMsg];
            }
            $baseData['button_list_id'] = $params['button_list_id'];
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

    public function setPageUrl($type, $id): string
    {
        // 更新活动链接
        $prefix = ENV == 'prod' ? self::PROD_URL : self::DEV_URL;
        $pageUrl = '';
        switch ($type) {
            case BbcTemplateConfig::VISION_TYPE_PROGRESS:
            case BbcTemplateConfig::VISION_TYPE_EXCHANGE:
                $pageUrl = sprintf(self::PAGE_URL, $prefix, BbcTemplateConfig::$pageUrlTypeMap[$type], $id, '');
                break;
            case BbcTemplateConfig::VISION_TYPE_MAP_FORWARD:
                $pageUrl = sprintf(self::PAGE_URL, $prefix, BbcTemplateConfig::$pageUrlTypeMap[$type], $id, '#/home');
                break;
        }

        return $pageUrl;
    }

    public function info(int $id): array
    {
        $res = BbcTemplateConfig::findOne($id);
        if (!$res) {
            return [];
        }
        $tag = BbcRankButtonTag::findOneByWhere([['act_id', '=', $id]]);
        $list = BbcRankButtonList::findOneByWhere([['act_id', '=', $id]]);

        $timeOffset = (8 - intval($res['time_offset']) / 10) * 3600;
        $dataPeriod = intval($res['data_period']) * 86400;
        $startTime = $res['start_time'] - $timeOffset;
        $endTime = $res['end_time'] - $timeOffset - $dataPeriod;
        $visionContentJson = @json_decode($res['vision_content_json'], true);
        if ($visionContentJson) {
            foreach ($visionContentJson as $key => $value) {
                if (in_array($key, [
                    'title_bottom_box_vc', 'info_bottom_box_vc', "score_1icon_vc", "score_task_bottom_img_vc",
                    "count_down_bottom_box_img2_vc", "level_bottom_box_img2_vc", "level_module_decorate_img2_vc",
                    "gift_plinth_img2_vc", "title_broker_img2_vc", "button_img2_vc", "task_bottom_box_img2_vc",
                    "score_2icon_vc", 'header_img2_vc', 'reward_rule_border_img3_vc', 'gift_show_module_decoration_img3_vc',
                    'map_module_decoration_img3_vc', 'user_info_module_decoration_img3_vc', 'task_bottom_box_img3_vc',
                    'road_img3_vc', 'header_img3_vc', 'score_3icon_vc', 'button_img3_vc', 'map_bgc_top_half_decoration_img3_vc',
                    'map_bgc_bottom_half_decoration_img3_vc', 'header_img4_vc', 'score_4icon_vc', 'select_btn_long_img4_vc',
                    'select_btn_short_img4_vc', 'user_info_border_img4_vc', 'get_btn_img4_vc', 'diamond_broder_img4_vc',
                    'reward_broder_img4_vc', 'exchange_btn_img4_vc', 'title_bottom_box3_vc'
                ])) {
                    $visionContentJson[$key . '_all'] = Helper::getHeadUrl($value);
                }
            }
        }
        $lvIcon = $visionContentJson['lv_icon'] ?? [];
        $subRankObject = $tag['sub_rank_object'] ?? 0;
        if (!in_array($tag['rank_object'], [BbcRankButtonTag::RANK_OBJECT_BROKER, BbcRankButtonTag::RANK_OBJECT_ROOM])) {
            $subRankObject = 0;
        }
        $data = [
            'id'                        => $res['id'],
            'start_time'                => Helper::now($startTime),
            'end_time'                  => Helper::now($endTime),
            'language'                  => $res['language'],
            'title'                     => $res['title'],
            'data_period'               => $res['data_period'],
            'time_offset'               => $res['time_offset'] / 10,
            'bigarea_id'                => explode('|', $res['bigarea_id']),
            'type'                      => $res['type'],
            'button_list_id'            => $list['id'] ?? 0,
            'button_tag_id'             => $tag['id'] ?? 0,
            'rank_object'               => $tag['rank_object'] ?? 0,
            'sub_rank_object'           => $subRankObject,
            'tag_list_type'             => strval($tag['tag_list_type'] ?? 0),
            'room_support'              => $list['room_support'] ?? 0,
            'divide_track'              => strval($list['divide_track'] ?? 0),
            'divide_type'               => strval($list['divide_type'] ?? 0),
            'broker_distance_start_day' => $list['broker_distance_start_day'] ?: '',
            'score_source'              => $this->formatScoreConfig($res['id'], $list['id'] ?? 0),
            'rank_award'                => $this->formatRankAward($res['id'], $list['id'] ?? 0, $lvIcon, $res['type']),
            'audit_status'              => $this->getAuditStatus($res['status']),
            'status'                    => $this->getStatus($res['status'], $res['start_time'], $res['end_time'] - $dataPeriod),
            'vision_type'               => $res['vision_type'],
            'button_desc'               => $list['button_desc'] ?? '',
            'has_be_related'            => (string)$res['has_be_related'],
            'cp_gender'                 => $list['cp_gender'] ?? BbcRankButtonList::CP_GENDER_ALL,
            'vision_content_json'       => $visionContentJson,
            'award_content_json'        => json_decode($res['award_content_json'], true),
            'admin'                     => Helper::getAdminName($res['admin_id'])
        ];

        return $data;
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

    private function formatRankAward(int $actId, int $buttonListId, array $lvIcon, string $type): array
    {
        if ($lvIcon) {
            $lvIcon = array_column($lvIcon, null, 'lv');
        }
        $awardList = BbcRankAward::getListByWhere([
            ['act_id', '=', $actId],
            ['button_list_id', '=', $buttonListId]
        ], '*', 'rank asc, id asc');
        $awardConfig = [];
        if ($awardList) {
            // 兼容下前端回显格式
            foreach ($awardList as $award) {
                $extend = @json_decode($award['award_extend_info'], true);
                $tmp = [
                    'id'             => (string)$award['id'],
                    'score_min'      => (string)$award['score_min'],
                    'award_type'     => (string)$award['award_type'],
                    'cid'            => (string)$award['cid'],
                    'exp_days'       => $award['exp_days'],
                    'days'           => $award['exp_days'],
                    'vip_level'      => (string)$award['cid'],
                    'give_type'      => strval($extend['extend_type'] ?? $award['can_transfer'] ?? 0),
                    'content'        => $extend['content'] ?? '',
                    'icon'           => $extend['icon'] ?? '',
                    'num'            => $award['num'],
                    'stock_type'     => $this->getStockType($award['stock_type'], $award['lv_award_stock']),
                    'lv_award_stock' => $award['lv_award_stock'] ?? 0,
                    'effective_hours'=> $extend['days'] ?? $award['exp_days'] ?? 0,
                ];
                if ($tmp['award_type'] == BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD) {
                    $tmp['expire_time'] = $award['exp_days'] ? Helper::now($award['exp_days']) : '';
                    $extendInfo = $extend['open_screen_card_extend'] ?? [];
                    $tmp['card_type'] = strval($extendInfo['card_type'] ?? '');
                }
                if ($tmp['award_type'] == BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD) {
                    $tmp['expire_time'] = $award['exp_days'] ? Helper::now($award['exp_days']) : '';
                    $tmp['effective_days'] = $extend['days'] ?? '';
                    $tmp['card_type'] = strval($extendInfo['card_type'] ?? '');
                }
                if ($tmp['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_BG_CARD) {
                    $extendInfo = $extend['room_bg_card_extend'] ?? [];
                    $tmp['card_type'] = strval($extendInfo['card_type'] ?? '');
                }
                if (in_array($tmp['award_type'], [
                    BbcRankAward::AWARD_TYPE_VIP, BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD,
                    BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD,
                    BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD
                ])) {
                    $tmp['num'] = $extend['send_num'] ?? '';
                }
                $tmp['icon'] && $tmp['icon_all'] = Helper::getHeadUrl($tmp['icon']);
                switch ($tmp['award_type']) {
                    case BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD:
                    case BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON:
                    case BbcRankAward::AWARD_TYPE_MEDAL:
                    case BbcRankAward::AWARD_TYPE_ROOM_SKIN:
                    case BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND:
                    case BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD:
                    case BbcRankAward::AWARD_TYPE_VIP:
                    case BbcRankAward::AWARD_TYPE_EMOTICONS:
                    case BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING:
                    case BbcRankAward::AWARD_TYPE_ITEM_CARD:
                    case BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD:    
                        $tmp['days'] = $award['num'];
                        break;
                }
                if ($type == BbcTemplateConfig::TYPE_TASK) {
                    if (!isset($awardConfig[$award['rank']])) {
                        $litIcon = $lvIcon[$award['rank']]['lit_icon'] ?? '';
                        $unLitIcon = $lvIcon[$award['rank']]['unlit_icon'] ?? '';
                        $awardConfig[$award['rank']] = [
                            'rank'           => $award['rank'],
                            'score_min'      => $award['score_min'],
                            'lit_icon'       => $litIcon,
                            'lit_icon_all'   => Helper::getHeadUrl($litIcon),
                            'unlit_icon'     => $unLitIcon,
                            'unlit_icon_all' => Helper::getHeadUrl($unLitIcon),
                            'rank_award_arr' => [$tmp],
                        ];
                    } else {
                        $awardConfig[$award['rank']]['rank_award_arr'][] = $tmp;
                    }
                } else if ($type == BbcTemplateConfig::TYPE_EXCHANGE) {
                    $awardConfig[] = $tmp;
                }
            }
            $awardConfig = array_values($awardConfig);
        }

        return $awardConfig;
    }

    private function getStockType(int $stockType, int $lvAwardStock): string
    {
        if ($stockType == BbcRankAward::STOCK_TYPE_DAYS_LIMIT) {
            return (string)$stockType;
        }

        return (string)$lvAwardStock ? BbcRankAward::STOCK_TYPE_TOTAL_LIMIT : BbcRankAward::STOCK_TYPE_NO_LIMIT;
    }

    private function handleRankAward($config, $baseData): array
    {
        if ($config['addAward']) {
            foreach ($config['addAward'] as &$awardItem) {
                $awardItem = array_merge($awardItem, $baseData);
            }
            [$awardRes, $awardMsg, $_] = BbcRankAward::addBatch($config['addAward']);
            if (!$awardRes) {
                return [false, '奖励配置数据添加失败，失败原因：' . $this->handleSpecialAwardMsg($awardMsg)];
            }
        }
        if ($config['editAward']) {
            [$awardRes, $awardMsg, $_] = BbcRankAward::updateBatch($config['editAward']);
            if (!$awardRes) {
                return [false, '奖励配置数据修改失败，失败原因：' . $this->handleSpecialAwardMsg($awardMsg)];
            }
        }
        if ($config['deleteAward']) {
            [$awardRes, $awardMsg, $_] = BbcRankAward::deleteByWhere([['id', 'IN', $config['deleteAward']]]);
            if (!$awardRes) {
                return [false, '奖励配置数据删除失败，失败原因：' . $this->handleSpecialAwardMsg($awardMsg)];
            }
        }
        return [true, ''];
    }

    /**
     * 特殊处理下自定义描述字段错误信息（award_extend_info）不支持emoji表情
     * @param string $awardMsg
     * @return string
     */
    private function handleSpecialAwardMsg(string $awardMsg): string
    {
        if (str_contains($awardMsg, 'General error: 1366 Incorrect string value:') && str_contains($awardMsg, "for column 'award_extend_info' at row")) {
            return '数据类型不支持';
        }

        return $awardMsg;
    }

    private function getTemplateData(array &$params): array
    {
        $dataPeriod = intval($params['data_period']);
        $timeOffset = $params['time_offset'];
        if ($params['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_DAY) {
            $params['start_time'] = date('Y-m-d', strtotime($params['start_time']));
            $params['end_time'] = date('Y-m-d', strtotime($params['end_time'])) . ' 23:59:59';
        }
        $startTime = strtotime($params['start_time']) + (8 - $timeOffset) * 3600;
        $endTime = strtotime($params['end_time']) + (8 - $timeOffset) * 3600;
        $visionType = $params['type'] == BbcTemplateConfig::TYPE_EXCHANGE ? BbcTemplateConfig::VISION_TYPE_EXCHANGE : $params['vision_type'];
        // 活动时间根据时区字段进行转化 （8-time_offset）* 3600, 结束时间要在加上数据保留时间（data_period）
        $templateConfig = [
            'vision_content_json' => $this->setVisionContentJson($params),
            'vision_type'         => $visionType,
            'start_time'          => $startTime,
            'end_time'            => $endTime + ($dataPeriod * 86400),
            'language'            => $params['language'],
            'title'               => $params['title'],
            'time_offset'         => $timeOffset * 10, // 10倍保存
            'bigarea_id'          => implode('|', array_map('intval', $params['bigarea_id'])),
            'type'                => $params['type'],
            'data_period'         => $dataPeriod,
            'has_be_related'      => intval($params['has_be_related'] ?? 0),
            'award_content_json'  => isset($params['award_content_json']) ? json_encode($params['award_content_json']) : ''
        ];

        $params['start_time'] = $startTime;
        $params['end_time'] = $endTime;
        return $templateConfig;
    }

    private function setVisionContentJson($params)
    {
        $visionContentJson = $params['vision_content_json'];
        $params['lv_icon'] && $visionContentJson['lv_icon'] = array_values($params['lv_icon']);
        return $visionContentJson ? json_encode($visionContentJson) : '';
    }

    private function getButtonTagData(array $params): array
    {
        $subRankObject = $params['sub_rank_object'] ?? 0;
        if (!in_array($params['rank_object'], [BbcRankButtonTag::RANK_OBJECT_BROKER, BbcRankButtonTag::RANK_OBJECT_ROOM])) {
            $subRankObject = 0;
        }

        return [
            'rank_object'     => $params['rank_object'],
            'sub_rank_object' => $subRankObject,
            'tag_list_type'   => $params['tag_list_type']
        ];
    }

    private function getButtonListData(array $params): array
    {
        // 设置对象默认值
        $params['rank_object'] != BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS && $params['divide_track'] = BbcRankButtonList::DIVIDE_TRACK_NO;
        $params['divide_track'] == BbcRankButtonList::DIVIDE_TRACK_YES && $params['divide_object'] = BbcRankButtonList::DIVIDE_TRACK_YES;

        if ($params['divide_track'] == BbcRankButtonList::DIVIDE_TRACK_NO) {
            $params['divide_type'] = 0;
            $params['broker_distance_start_day'] = 0;
            $params['divide_object'] = 0;
        }


        return [
            'button_desc'               => $params['button_desc'] ?? '',
            'upgrade_extend_num'        => 0,  // 给个默认值
            'start_time'                => $params['start_time'],
            'end_time'                  => $params['end_time'],
            'cp_gender'                 => BbcRankButtonList::CP_GENDER_ALL,
            'divide_type'               => $params['divide_type'] ?? 0,
            'divide_track'              => $params['divide_track'] ?? 0,
            'broker_distance_start_day' => $params['broker_distance_start_day'] ?? 0,
            'divide_object'             => $params['divide_object'] ?? 0,
        ];
    }

    private function getRankScoreConfigData(array $params): array
    {
        return $params['score_source'];
    }

    private function getRankAwardData(array $params): array
    {
        $addAward = $editAward = $awardIds = [];
        if (isset($params['id']) && !empty($params['id'])) {
            $awardIds = BbcRankAward::getInfoByActId($params['id']);
        }
        foreach ($params['rank_award'] as &$config) {
            $num = intval($config['num'] ?? 0);
            $vipLevel = intval($config['vip_level'] ?? 0);
            $days = intval($config['days'] ?? 0);
            $expDays = intval($config['exp_days'] ?? 0);
            $content = trim($config['content'] ?? 0);
            $giveType = intval($config['give_type'] ?? 0);
            $cid = intval($config['cid'] ?? 0);
            $lvAwardStock = intval($config['lv_award_stock'] ?? 0);
            $stockType = intval($config['stock_type'] ?? 0);
            $icon = trim($config['icon'] ?? '');
            $effectiveHours = intval($config['effective_hours'] ?? 0);
            $expireTime = trim($config['expire_time'] ?? '');
            $cardType = intval($config['card_type'] ?? -1);
            $effectiveDays = intval($config['effective_days'] ?? 0);

            $tmp = [
                'rank'              => (int)$config['rank'],
                'award_type'        => (int)$config['award_type'],
                'score_min'         => (int)$config['score_min'],
                'cid'               => 0,
                'exp_days'          => 0,
                'can_transfer'      => 0,
                'award_extend_info' => '',
                'num'               => 0,
                'lv_award_stock'    => $stockType != BbcRankAward::STOCK_TYPE_NO_LIMIT ? $lvAwardStock : 0,
                'stock_type'        => $stockType == BbcRankAward::STOCK_TYPE_TOTAL_LIMIT ? 0 : $stockType,
            ];
            switch ($config['award_type']) {
                case BbcRankAward::AWARD_TYPE_DIAMOND:
                    $tmp['num'] = $num;
                    break;
                case BbcRankAward::AWARD_TYPE_COMMODITY:
                    $tmp['num'] = $num;
                    $tmp['cid'] = $cid;
                    $tmp['exp_days'] = $expDays;
                    break;
                case BbcRankAward::AWARD_TYPE_PACK:
                    $tmp['num'] = $num;
                    $tmp['cid'] = $cid;
                    break;
                case BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND:
                case BbcRankAward::AWARD_TYPE_ROOM_SKIN:
                case BbcRankAward::AWARD_TYPE_MEDAL:
                case BbcRankAward::AWARD_TYPE_EMOTICONS:
                    $tmp['num'] = $days;
                    $tmp['cid'] = $cid;
                    break;
                case BbcRankAward::AWARD_TYPE_VIP:
                    $tmp['num'] = $days;
                    $tmp['cid'] = $vipLevel;
                    $tmp['award_extend_info'] = json_encode(['extend_type' => $giveType, 'send_num' => $giveType == BbcRankAward::GIVE_TYPE_AUTO_EFFECT ? 1 : $num], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    break;
                case BbcRankAward::AWARD_TYPE_ROOM_BG_CARD:
                    $tmp['num'] = $num;
                    $tmp['exp_days'] = $days;
                    $tmp['can_transfer'] = $giveType;
                    $cardType > -1 && $tmp['award_extend_info'] = json_encode(['room_bg_card_extend' => ['card_type' => $cardType]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    break;
                case BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD:
                case BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING:
                case BbcRankAward::AWARD_TYPE_ITEM_CARD:
                case BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD:
                    $tmp['num'] = $days;
                    $tmp['cid'] = $cid;
                    $tmp['exp_days'] = $expDays;
                    $tmp['can_transfer'] = $giveType;
                    $tmp['award_extend_info'] = json_encode(['send_num' => $num], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    break;
                case BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD:
                case BbcRankAward::AWARD_TYPE_GAME_COUPON:
                case BbcRankAward::AWARD_TYPE_UNBLOCK_CARD:
                    $tmp['num'] = $num;
                    $tmp['cid'] = $cid;
                    $tmp['exp_days'] = $expDays;
                    break;
                case BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON:
                    $tmp['num'] = $days;
                    $tmp['cid'] = $cid;
                    $tmp['award_extend_info'] = json_encode(['content' => $content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                case BbcRankAward::AWARD_TYPE_CUSTOMIZATION:
                    $tmp['num'] = $num;
                    $tmp['exp_days'] = $expDays;
                    $tmp['award_extend_info'] = json_encode(['content' => $content, 'icon' => $icon], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                case BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD:
                    $tmp['num'] = $num;
                    $tmp['can_transfer'] = $giveType;
                    $tmp['exp_days'] = strtotime($expireTime);
                    $tmp['award_extend_info'] = ['days' => $effectiveHours];
                    $cardType && $tmp['award_extend_info']['open_screen_card_extend'] = ['card_type' => $cardType];
                    $tmp['award_extend_info'] = json_encode($tmp['award_extend_info'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                case BbcRankAward::AWARD_TYPE_PROP_CARD:
                    $propCard = XsPropCard::findOne($cid);
                    $propCardConfig = XsPropCardConfig::findOne( $propCard['prop_card_config_id'] ?? 0);
                    $tmp['num'] = $num;
                    $tmp['cid'] = $cid;
                    $tmp['exp_days'] = $effectiveHours;
                    $tmp['award_extend_info'] = json_encode(['extend_type' => $propCardConfig['type'] ?? 0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                case BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD:
                    $tmp['num'] = $num;
                    $tmp['can_transfer'] = $giveType;
                    $tmp['exp_days'] = strtotime($expireTime);
                    $tmp['award_extend_info'] = json_encode(['days' => $effectiveDays]);
                    break;    
            }
            if (isset($config['id']) && !empty($config['id'])) {
                $editAward[$config['id']] = $tmp;
            } else {
                $addAward[] = $tmp;
            }
        }
        $deleteAward = array_diff($awardIds, array_keys($editAward));
        $deleteAward = array_map('intval', array_values($deleteAward));

        return compact('addAward', 'editAward', 'deleteAward');
    }

    private function verify(array &$params): void
    {
        $type = $params['type'] ?? '';
        switch ($type) {
            case BbcTemplateConfig::TYPE_TASK:
                $this->verifyReward($params);
                break;
            case BbcTemplateConfig::TYPE_EXCHANGE:
                $this->verifyReward($params, false);
                break;
        }
        
        $this->verifyScoreSource($params);
    }

    private function verifyReward(array &$params, bool $isTask = true): void
    {
        $scoreMin = 0;
        $lvIcon = $msg = [];
        $awardList = $params['rank_award'];

        foreach ($awardList as $key => $award) {
            $rank = $key + 1;
            // 任务奖励校验排序
            if ($isTask && $award['score_min'] < $scoreMin) {
                throw new ApiException(ApiException::MSG_ERROR, '目标等级请按住从小到大顺序填写');
            }

            $scoreMin = $award['score_min'] ?? $scoreMin;

            if ($isTask) {
                $lvIcon[$rank] = [
                    'lv'         => $rank,
                    'lit_icon'   => $award['lit_icon'] ?? '',
                    'unlit_icon' => $award['unlit_icon'] ?? ''
                ];
            }

            $items = $isTask ? ($award['rank_award_arr'] ?? []) : [$award];

            if (empty($items)) {
                throw new ApiException(ApiException::MSG_ERROR, $isTask ? '奖励配置不能为空' : '商品奖励不能为空');
            }

            foreach ($items as $item) {
                $awardType = intval($item['award_type'] ?? 0);
                $expDays = intval($item['exp_days'] ?? 0);
                $content = trim($item['content'] ?? '');
                $giveType = intval($item['give_type'] ?? -1);
                $days = intval($item['days'] ?? 0);
                $vipLevel = intval($item['vip_level'] ?? 0);
                $num = intval($item['num'] ?? 0);
                $cid = intval($item['cid'] ?? 0);
                $stockType = intval($item['stock_type'] ?? 0);
                $lvAwardStock = intval($item['lv_award_stock'] ?? 0);
                $icon = trim($item['icon'] ?? '');
                $effectiveHours = intval($item['effective_hours'] ?? 0);
                $effectiveDays = intval($item['effective_days'] ?? 0);
                $expireTime = trim($item['expire_time'] ?? '');


                $label = $isTask ? "Lv.$rank" : "商品.$rank";

                if (empty($awardType)) {
                    throw new ApiException(ApiException::MSG_ERROR, '奖励类型不能为空');
                }

                if (!$isTask) {
                    if ($scoreMin < 1) {
                        throw new ApiException(ApiException::MSG_ERROR, "{$label}，兑换需消耗积分必须大于等于1");
                    }
                    if ($stockType != BbcRankAward::STOCK_TYPE_NO_LIMIT && $lvAwardStock < 1) {
                        throw new ApiException(ApiException::MSG_ERROR, "{$label}，当前库存限制类型，库存数量必须大于等于1");
                    }
                }

                if ($awardType == BbcRankAward::AWARD_TYPE_VIP && $giveType != BbcRankAward::GIVE_TYPE_AUTO_EFFECT && $num < 1) {
                    $msg[] = "{$label}，数量必须大于等于1";
                }

                if (in_array($awardType, [BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD, BbcRankAward::AWARD_TYPE_PROP_CARD]) && $effectiveHours < 1) {
                    $msg[] = "{$label}，有效小时数必须大于等于1";
                }

                if (in_array($awardType, [
                    BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD, BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD
                ]) && empty($expireTime)) {
                    $msg[] = "{$label}，过期时间必须填写";
                }

                if ($awardType == BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD && $effectiveDays < 1) {
                    $msg[] = "{$label}，生效天数必须大于等于1";
                }

                if (in_array($awardType, [
                        BbcRankAward::AWARD_TYPE_DIAMOND, BbcRankAward::AWARD_TYPE_COMMODITY, BbcRankAward::AWARD_TYPE_PACK,
                        BbcRankAward::AWARD_TYPE_ROOM_BG_CARD, BbcRankAward::AWARD_TYPE_GAME_COUPON, BbcRankAward::AWARD_TYPE_UNBLOCK_CARD,
                        BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD, BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD,
                        BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD, BbcRankAward::AWARD_TYPE_PROP_CARD, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD,
                        BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD
                    ]) && $num < 1) {
                    $msg[] = "{$label}，数量必须大于等于1";
                }

                if (in_array($awardType, [
                        BbcRankAward::AWARD_TYPE_MEDAL, BbcRankAward::AWARD_TYPE_VIP, BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND,
                        BbcRankAward::AWARD_TYPE_ROOM_BG_CARD, BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD, BbcRankAward::AWARD_TYPE_ROOM_SKIN,
                        BbcRankAward::AWARD_TYPE_EMOTICONS, BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON,  BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING,
                        BbcRankAward::AWARD_TYPE_ITEM_CARD, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD
                    ]) && $days < 1) {
                    $msg[] = "{$label}，天数必须大于等于1";
                }

                if ($awardType == BbcRankAward::AWARD_TYPE_CUSTOMIZATION && empty($icon)) {
                    $msg[] = "{$label}，预览图必须填写";
                }

                if (in_array($awardType, [
                        BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD, BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD, BbcRankAward::AWARD_TYPE_GAME_COUPON,
                        BbcRankAward::AWARD_TYPE_UNBLOCK_CARD, BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD,
                        BbcRankAward::AWARD_TYPE_COMMODITY, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD
                    ]) && $expDays < 1) {
                    $msg[] = "{$label}，资格有效天数必须大于等于1";
                }

                if (in_array($awardType, [BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON, BbcRankAward::AWARD_TYPE_CUSTOMIZATION]) && empty($content)) {
                    $msg[] = "{$label}，文案/自定义描述必须填写";
                }

                if ($awardType == BbcRankAward::AWARD_TYPE_VIP && empty($vipLevel)) {
                    $msg[] = "{$label}，vip等级必须大于等于1";
                }

                if (in_array($awardType, [
                        BbcRankAward::AWARD_TYPE_VIP, BbcRankAward::AWARD_TYPE_ROOM_BG_CARD, BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD,
                        BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD, BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD,
                        BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD, BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD
                    ]) && $giveType < 0) {
                    $msg[] = "{$label}，是否可赠送必须大于等于1";
                }

                $errorMsg = $this->verifyCid($awardType, $cid, $params);

                if ($errorMsg) {
                    $msg[] = "{$label}，{$errorMsg}";
                }
            }
        }

        if (!empty($msg)) {
            throw new ApiException(ApiException::MSG_ERROR, implode('；', $msg));
        }

        $this->processRankAward($params, $isTask);
        $params['lv_icon'] = $lvIcon;
    }

    private function processRankAward(array &$params, bool $isTask): void
    {
        if ($isTask) {
            $rewardList = [];
            foreach ($params['rank_award'] as $k => $award) {
                foreach ($award['rank_award_arr'] as &$item) {
                    $item['score_min'] = $award['score_min'];
                    $newAward = $award;
                    unset($newAward['rank_award_arr']);
                    $rewardList[] = array_merge($newAward, $item, ['rank' => $k + 1]);
                }
            }
            $params['rank_award'] = $rewardList;
        } else {
            foreach ($params['rank_award'] as $k => &$award) {
                $award['rank'] = $k + 1;
            }
        }
    }

    private function verifyCid(int $awardType, int $cid, array $params): string
    {
        $errorMsg = '';
        switch ($awardType) {
            case BbcRankAward::AWARD_TYPE_COMMODITY:
                $cond = [['ocid', '=', $cid], ['state', '=', 1], ['app_id', '=', APP_ID]];
                if (empty(XsCommodityAdmin::findOneByWhere($cond))) {
                    $errorMsg = "物品ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_MEDAL:
                $cond = [['type', '=', XsMedalResource::HONOR_MEDAL], ['id', '=', $cid]];
                if (empty(XsMedalResource::findOneByWhere($cond))) {
                    $errorMsg = "勋章ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND:
                $bgc = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $cid]]);
                $material = $bgc ? XsChatroomMaterial::findOneByWhere([['mid', '=', $bgc['mid']], ['source', '=', 0]]) : null;
                if (empty($bgc) || empty($material)) {
                    $errorMsg = "房间背景ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_PACK:
                $cond = [['status', '=', XsGiftBag::STATUS_VALID], ['id', '=', $cid]];
                if (empty(XsGiftBag::findOneByWhere($cond))) {
                    $errorMsg = "礼包ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD:
                if (empty(XsCustomizePrettyStyle::findOne($cid))) {
                    $errorMsg = "自定义靓号卡ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD:
                $cond = [['id', '=', $cid], ['is_delete', '=', XsRoomTopCard::DELETE_NO]];
                if (empty(XsRoomTopCard::findOneByWhere($cond))) {
                    $errorMsg = "房间置顶卡ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_SKIN:
                $cond = [['id', '=', $cid], ['status', '=', XsRoomSkin::SUPPORT_SEND_STATUS]];
                if (empty(XsRoomSkin::findOneByWhere($cond))) {
                    $errorMsg = "房间皮肤ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON:
                if (empty(XsCertificationSign::findOne($cid))) {
                    $errorMsg = "认证图标ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_GAME_COUPON:
                if (empty(XsCoupon::findOne($cid))) {
                    $errorMsg = "游戏优惠券ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_EMOTICONS:
                $emoticons = XsEmoticons::findOneByWhere([
                    ['id', '=', $cid],
                    ['status', '=', XsEmoticons::LISTED_STATUS],
                    ['identity', '=', XsEmoticons::EMOTICONS_IDENTITY_ACTIVE]
                ]);
                if (empty($emoticons)) {
                    $errorMsg = "表情包ID 【'$cid'】不存在，请检查";
                } else {
                    if (count($params['bigarea_id']) > 1 || $params['bigarea_id'][0] != $emoticons['bigarea_id']) {
                        $errorMsg = "表情包ID 【'$cid'】不是活动大区的表情包，不可使用";
                    }
                }
                break;
            case BbcRankAward::AWARD_TYPE_UNBLOCK_CARD:
                $card = XsPropCard::findOneByWhere([['id', '=', $cid], ['deleted', '=', XsPropCard::DELETED_NO]]);
                $configId = $card['prop_card_config_id'] ?? 0;
                $config = XsPropCardConfig::findOne($configId);
                if (empty($card) || empty($config) || $config['type'] != XsPropCardConfig::TYPE_RELIEVE_FORBIDDEN_CARD) {
                    $errorMsg = "解封卡ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING:
                if (empty(XsNameIdLightingGroup::findOne($cid))) {
                    $errorMsg = "炫彩资源ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_ITEM_CARD:
            case BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD: 
                if (empty(XsItemCard::findOne($cid))) {
                    $errorMsg = "mini/个人主页装扮卡装扮ID【'$cid'】不存在，请检查";
                }
                break;
            case BbcRankAward::AWARD_TYPE_PROP_CARD:
                if (empty(XsPropCard::findOne($cid))) {
                    $errorMsg = "pk道具卡ID【'$cid'】不存在，请检查";
                }
                break;

        }

        return $errorMsg;
    }


    public function getOptions()
    {
        $service = new StatusService();
        $language = $service->getLanguageNameMap(null, 'label,value');

        $bigAreaId = $service->getFamilyBigArea(null, 'label,value');

        $awardType = [
            BbcTemplateConfig::TYPE_TASK     => StatusService::formatMap(BbcRankAward::$awardTypeMap),
            BbcTemplateConfig::TYPE_EXCHANGE => StatusService::formatMap(BbcRankAward::$exchangeAwardTypeMap),
        ];

        $visionType = [
            ['label' => '进度条', 'value' => BbcTemplateConfig::VISION_TYPE_PROGRESS],
            ['label' => '地图前进', 'value' => BbcTemplateConfig::VISION_TYPE_MAP_FORWARD],
        ];

        $rankObject = [
            BbcTemplateConfig::TYPE_TASK     => [
                ['label' => '用户', 'value' => BbcRankButtonTag::RANK_OBJECT_PERSONAL],
                ['label' => 'CP', 'value' => BbcRankButtonTag::RANK_OBJECT_CP],
                ['label' => '公会成员', 'value' => BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS],
                ['label' => '公会', 'value' => BbcRankButtonTag::RANK_OBJECT_BROKER],
                ['label' => '房间', 'value' => BbcRankButtonTag::RANK_OBJECT_ROOM],
            ],
            BbcTemplateConfig::TYPE_EXCHANGE => [
                ['label' => '用户', 'value' => BbcRankButtonTag::RANK_OBJECT_PERSONAL],
                ['label' => '公会成员', 'value' => BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS],
            ],
        ];

        $subRankObject = [
            BbcRankButtonTag::RANK_OBJECT_BROKER => [
                ['label' => '公会长', 'value' => BbcRankButtonTag::SUB_RANK_OBJECT_BROKER_MASTER],
            ],
            BbcRankButtonTag::RANK_OBJECT_ROOM => [
                ['label' => '房主', 'value' => BbcRankButtonTag::SUB_RANK_OBJECT_ROOM_MASTER],
            ],
        ];

        $sourceType = BbcRankScoreConfigNew::getOptions(BbcRankScoreConfigNew::$rankObjectAndsourceTypeMap, BbcRankScoreConfigNew::$sourceTypeMap);
        $scoreType = StatusService::formatMap(BbcRankScoreConfigNew::$scoreTypeMap, 'label,value');
        $scoreScope = BbcRankScoreConfigNew::getOptions(BbcRankScoreConfigNew::$sourceTypeAndScoreScopeMap, BbcRankScoreConfigNew::$scoreScopeMap);

        // 手动新增积分来源是幸运玩法模版是统计范围的数据源
        $scoreScope[BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY] = $this->getWheelLotteryScoreScopeMap();
        $stockType = StatusService::formatMap(BbcRankAward::$stockTypeMap);

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

        $tagListType = [
            BbcTemplateConfig::TYPE_EXCHANGE => [
                ['label' => '单次', 'value' => BbcRankButtonTag::TAG_LIST_TYPE_ONE]
            ],
            BbcTemplateConfig::TYPE_TASK     => StatusService::formatMap(BbcRankButtonTag::$tagListTypeMap),
        ];
        $hasRelate = [
            BbcTemplateConfig::TYPE_EXCHANGE => [
                ['label' => '否', 'value' => (string)BbcTemplateConfig::HAS_RELATE_NO],
            ],
            BbcTemplateConfig::TYPE_TASK     => $this->getHasRelateMap(),
        ];
        $vipDays = StatusService::formatMap(XsUserProfile::$vipDaysMap, 'label,value');
        $content = XsCertificationSign::getContentMap();
        $type = $this->getTypeMap();
        $isOnlyCrossRoomPk = StatusService::formatMap(BbcRankButtonList::$isOnlyCorssRoomPkMap);
        $effectiveHours = self::getEffectiveHoursMap();
        $pkValidType = StatusService::formatMap(BbcRankScoreConfigNew::$pkValidTypeMap);
        $scoreUnit = BbcRankScoreConfigNew::$scoreUnitMap;
        $effectiveDays = self::getEffectiveDaysMap();

        return compact(
            'type',
            'language',
            'bigAreaId',
            'timeOffset',
            'awardType',
            'rankObject',
            'visionType',
            'hasRelate',
            'sourceType',
            'scoreType',
            'scoreScope',
            'tagListType',
            'vipDays',
            'content',
            'stockType',
            'isOnlyCrossRoomPk',
            'effectiveHours',
            'subRankObject',
            'pkValidType',
            'scoreUnit',
            'effectiveDays'
        );
    }

    public function getAwardOptions(): array
    {
        $service = new StatusService();
        $giftBag = $service->getGiftBagMap(null, 'label,value');
        $commodityList = XsCommodityAdmin::getCommodityListByTypes($this->getCommodityTypes());
        $commoditys = [];
        foreach ($commodityList as $commodity) {
            $commoditys[] = [
                'label' => $commodity['ocid'] . '_' . $commodity['name'],
                'value' => (string)$commodity['ocid'],
            ];
        }
        $emoticons = XsEmoticons::getOptions();
        $medal = $service->getMedalMap(null, 'label,value');
        $background = $service->getActivityBackgroundMap(null, 'label,value');
        $roomTopCard = $service->getRoomTopCardMap(null, 'label,value');
        $vip = $service->getVipMap(null, 'label,value');
        $prettyCard = $service->getPrettyCardMap(null, 'label,value');
        $certification = $service->getCertificationMap(null, 'label,value');
        $roomSkin = $service->getRoomSkinMap(null, 'label,value');
        $xsCoupon = StatusService::formatMap(XsCoupon::getCouponMap(), 'label,value');
        $unblockCard = (new RelieveForbiddenCardSendService())->getRelieveForbiddenCardMap();
        $nameIdLighting = NameIdLightingLogService::getGroupIdMap(null, 'label,value');
        $miniCard = (new MiniCardSendService())->getCardMap();
        $homepageCard = (new MiniCardSendService())->getCardMap(XsItemCard::TYPE_HOMEPAGE);
        $propCard = StatusService::formatMap(XsPropCard::getPkPropCardOptions());

        $giveType = [
            BbcRankAward::AWARD_TYPE_VIP                      => StatusService::formatMap(BbcRankAward::$giveTypeMap, 'label,value'),
            BbcRankAward::AWARD_TYPE_ROOM_BG_CARD             => StatusService::formatMap(BbcRankAward::$canTransferBgcMap, 'label,value'),
            BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING         => StatusService::formatMap(BbcRankAward::$canTransferBgcMap, 'label,value'),
            BbcRankAward::AWARD_TYPE_ITEM_CARD                => StatusService::formatMap(BbcRankAward::$canTransferBgcMap, 'label,value'),
            BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD            => StatusService::formatMap(BbcRankAward::$canTransferBgcMap, 'label,value'),
            BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD           => StatusService::formatMap(BbcRankAward::$canTransferPrettuMap, 'label,value'),
            BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD         => StatusService::formatMap(BbcRankAward::$canTransferBgcMap, 'label,value'),
            BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD => StatusService::formatMap(BbcRankAward::$canTransferBgcMap, 'label,value'),
        ];

        $cardType = [
            BbcRankAward::AWARD_TYPE_ROOM_BG_CARD => (new CustomBgcCardSendService())->getCardTypeMap(),
            BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD => (new OpenScreenCardService())->getTypeMap(),
        ];

        return [
            'giveType'                                  => $giveType,
            'cardType'                                  => $cardType,
            BbcRankAward::AWARD_TYPE_PACK               => $giftBag,
            BbcRankAward::AWARD_TYPE_COMMODITY          => $commoditys,
            BbcRankAward::AWARD_TYPE_GAME_COUPON        => $xsCoupon,
            BbcRankAward::AWARD_TYPE_MEDAL              => $medal,
            BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND    => $background,
            BbcRankAward::AWARD_TYPE_ROOM_SKIN          => $roomSkin,
            BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD      => $roomTopCard,
            BbcRankAward::AWARD_TYPE_VIP                => $vip,
            BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON => $certification,
            BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD     => $prettyCard,
            BbcRankAward::AWARD_TYPE_EMOTICONS          => $emoticons,
            BbcRankAward::AWARD_TYPE_UNBLOCK_CARD       => $unblockCard,
            BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING   => $nameIdLighting,
            BbcRankAward::AWARD_TYPE_ITEM_CARD          => $miniCard,
            BbcRankAward::AWARD_TYPE_PROP_CARD          => $propCard,
            BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD      => $homepageCard,
        ];
    }

    protected function onAfterList($list)
    {
        $buttonTags = BbcRankButtonTag::getListByWhere([['act_id', 'IN', array_column($list, 'id')]], 'act_id, rank_object, tag_list_type, sub_rank_object');
        $buttonTags = array_column($buttonTags, null, 'act_id');
        $adminList = CmsUser::getUserNameList(array_merge(Helper::arrayFilter($list, 'admin_id'), Helper::arrayFilter($list, 'publisher_id')));
        $relateList = BbcTemplateConfig::getListByWhere([
            ['relate_id', 'IN', Helper::arrayFilter($list, 'id')],
        ], 'id, relate_id');
        $relateList = array_column($relateList, 'id', 'relate_id');
        foreach ($list as &$item) {
            $visionContentJson = @json_decode($item['vision_content_json'], true);
            $item['header_img'] = '';
            if (is_array($visionContentJson)) {
                foreach ($visionContentJson as $key => $val) {
                    if (str_contains($key, 'header_img')) {
                        $item['header_img'] = Helper::getHeadUrl($val);
                    }
                }
            }
            $item['be_relate_id'] = $relateList[$item['id']] ?? '';
            $buttonTag = $buttonTags[$item['id']] ?? [];
            $rankObject = $buttonTag['rank_object'] ?? 0;
            $item['is_diamond'] = $this->isDiamondAward($item['id']);
            $item['time_offset'] = $item['offset'] = $item['time_offset'] / 10 ?: 8;
            $item['time_offset'] = 'UTC :' . ($item['time_offset'] >= 0 ? '+' : '') . $item['time_offset'];
            $item['is_pub'] = $this->setIsPublish($item['status']);
            $dataPeriod = intval($item['data_period']) * 86400;
            $timeOffset = (8 - $item['offset']) * 3600;
            $starTime = intval($item['start_time']) - $timeOffset;
            $endTime = intval($item['end_time']) - $timeOffset - $dataPeriod;
            $item['bigarea_id'] = XsBigarea::formatBigAreaName($item['bigarea_id']);
            $admin = $adminList[$item['admin_id']] ?? '';
            $publisher = $adminList[$item['publisher_id']] ?? '';
            $item['tips'] = "你确定发布【{$admin}】创建的活动【{$item['title']}】吗？";
            $item['admin_id'] = $item['admin_id'] . '-' . $admin;
            $item['publisher'] = $item['publisher_id'] > 0 ? $item['publisher_id'] . '-' . $publisher : '';
            $item['dateline'] = Helper::now($item['dateline']);
            $item['activity_time'] = Helper::now($starTime) . '-<br />' . Helper::now($endTime);
            $item['audit_status'] = $this->getAuditStatus($item['status']);
            $item['audit_status_text'] = $this->setAuditStatusText($item['audit_status']);
            $item['status'] = $this->getStatus($item['status'], $item['start_time'], $item['end_time'] - $dataPeriod);
            $item['status_text'] = $this->setStatusText($item['status']);
            $item['rank_object'] = BbcRankButtonTag::$rankObjectMap[$rankObject] ?? '';
            if (in_array($rankObject, [BbcRankButtonTag::RANK_OBJECT_BROKER, BbcRankButtonTag::RANK_OBJECT_ROOM])) {
                $item['sub_rank_object'] = BbcRankButtonTag::$subRankObject[$buttonTag['sub_rank_object'] ?? 0] ?? '';
            }
            $item['tag_list_type'] = BbcRankButtonTag::$tagListTypeMap[$buttonTag['tag_list_type'] ?? 0] ?? '';
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

    protected function getFields(): array
    {
        return [
            'id', 'title', 'start_time', 'end_time', 'time_offset',
            'bigarea_id', 'admin_id', 'dateline', 'language', 'status',
            'data_period', 'publisher_id', 'has_relate', 'page_url',
            'vision_type', 'vision_content_json', 'type', 'relate_id',
            'has_be_related'
        ];
    }

    public function getAwardList(array $params): array
    {
        $list = XsActTaskAwardList::getListAndTotal($this->getAwardConditions($params), '*', 'dateline desc,id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($list['data'], 'user_bid'), ['bid', 'bname'], 'bname');

        foreach ($list['data'] as &$item) {
            $top = $item['top'] ?? 0;
            $scoreMin = $item['score_min'] ?? 0;
            $item['top'] = $item['score_min'] = $item['top2'] = $item['score_min2'] = '/';
            if ($item['award_task_type'] == XsActTaskAwardList::AWARD_TASK_TYPE_EXCHANGE) {
                $item['top2'] = $top;
                $item['score_min2'] = $scoreMin;
            } else {
                $item['top'] = $top;
                $item['score_min'] = $scoreMin;
            }
            if ($item['award_task_type'] != XsActTaskAwardList::AWARD_TASK_TYPE_MULTI) {
                $item['button_tag_type'] = '/';
            }
            $item['award_task_type'] = XsActTaskAwardList::$awardTaskTypeMap[$item['award_task_type']] ?? '';
            $item['act_bigarea_id'] = XsBigarea::formatBigAreaName($item['act_bigarea_id'], ',');
            $item['bigarea_id'] = XsBigarea::formatBigAreaName($item['bigarea_id'], ',');
            $item['bname'] = $brokerList[$item['user_bid']] ?? '';
            if ($item['award_type'] == BbcRankAward::AWARD_TYPE_PROP_CARD) {
                $item['award_type'] = BbcActWheelLotteryReward::$awardExtendTypeMap[$item['award_extend_type']] ?? '';
            } else {
                $item['award_type'] = BbcActWheelLotteryReward::$rewardTypeAllMap[$item['award_type']] ?? '';
            }
            $item['dateline'] = Helper::now($item['dateline']);
        }
        return $list;
    }

    public function getAwardConditions(array $params): array
    {
        $conditions = [];
        if (isset($params['object_id']) && !empty($params['object_id'])) {
            $conditions[] = ['object_id', '=', $params['object_id']];
        }
        if (isset($params['act_bigarea_id']) && !empty($params['act_bigarea_id'])) {
            $conditions[] = ['act_bigarea_id', 'FIND_IN_SET', $params['act_bigarea_id']];
        }
        if (isset($params['act_id']) && !empty($params['act_id'])) {
            $conditions[] = ['act_id', '=', $params['act_id']];
        }
        if (isset($params['top']) && !empty($params['act_id'])) {
            $conditions[] = ['top', '=', $params['top']];
        }
        if (isset($params['cid']) && !empty($params['cid'])) {
            $conditions[] = ['cid', '=', $params['cid']];
        }
        if (isset($params['button_tag_type']) && !empty($params['button_tag_type'])) {
            if (empty($params['act_id'])) {
                throw new ApiException(ApiException::MSG_ERROR, '任务tab存在时必须筛选活动ID');
            }
            $conditions[] = ['button_tag_type', '=', $params['button_tag_type']];
            $conditions[] = ['award_task_type', '=', XsActTaskAwardList::AWARD_TASK_TYPE_MULTI];
        }
        return $conditions;
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
        $tag = BbcRankButtonTag::findOneByWhere([
            ['act_id', '=', $template['id']]
        ]);
        if (empty($tag) || !in_array($tag['rank_object'], [
            BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS, BbcRankButtonTag::RANK_OBJECT_CP, 
            BbcRankButtonTag::RANK_OBJECT_PERSONAL, BbcRankButtonTag::RANK_OBJECT_BROKER,
            BbcRankButtonTag::RANK_OBJECT_ROOM
        ])) {
            throw new ApiException(ApiException::MSG_ERROR, '活动类型不正确');
        }

        $awardList = BbcRankAward::getListByWhere([
            ['act_id', '=', $template['id']]
        ], 'rank, score_min');
        $awardMap = [];
        foreach ($awardList as $item) {
            $awardMap[$item['rank']] = $item['score_min'];
        }

        $timeOffset = (8 - intval($template['time_offset']) / 10) * 3600;
        $dataPeriod = intval($template['data_period']) * 86400;

        return [
            'act_id'        => $template['id'],
            'act_name'      => $template['title'],
            'type'          => $template['type'],
            'start_time'    => $template['start_time'] - $timeOffset,
            'end_time'      => $template['end_time'] - $timeOffset - $dataPeriod,
            'tag_id'        => $tag['id'],
            'rank_object'   => $tag['rank_object'],
            'tag_list_type' => $tag['tag_list_type'],
            'award_list'    => $awardMap
        ];
    }

    public function getTaskAndExchangeDataList(array $params): array
    {
        $type = $params['type'] ?? '';
        switch ($type) {
            case BbcTemplateConfig::TYPE_EXCHANGE:
                return $this->getExchangeDataList($params);
            case BbcTemplateConfig::TYPE_TASK:
                return $this->getTaskDataList($params);
            default:
                return [];
        }
    }

    private function getExchangeDataList(array $params): array
    {
        $record = XsActivityScoreWallet::getListAndTotal([
            ['act_id', '=', $params['act_id']],
            ['score_type', '=', XsActivityScoreWallet::SCORE_TYPE_EXCHANGE]
        ], 'uid, total_count, availble_count', 'total_count desc', $params['page'], $params['limit']);
        $data = [];
        $uidArr = Helper::arrayFilter($record['data'], 'uid');
        $userList = XsUserProfile::getUserProfileBatch($uidArr);
        $userBrokerList = XsBrokerUser::getBrokerUserBatch($uidArr);
        $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($userBrokerList, 'bid'));
        $userRewardInfoList = XsActTaskAwardList::getUserRewardInfoList($params['act_id'], $uidArr);
        $rewardInfoMsg = "商品{rank}：所需积分{score}，兑换次数{num}；";
        foreach ($record['data'] as $value) {
            $userBroker = $userBrokerList[$value['uid']] ?? [];
            $bid = $userBroker['bid'] ?? '';
            $broker = $brokerList[$bid] ?? [];
            $bname = $broker['bname'] ?? '';
            $data[] = [
                'act_id'        => $params['act_id'],
                'act_name'      => $params['act_name'],
                'time'          => Helper::now($params['start_time']) . '至' . Helper::now($params['end_time']),
                'uid'           => $value['uid'],
                'user_name'     => $userList[$value['uid']]['name'] ?? '',
                'bname'         => $bname,
                'bid'           => $bid,
                'score'         => $value['total_count'],
                'surplus_score' => $value['availble_count'],
                'use_score'     => $value['total_count'] - $value['availble_count'],
                'reward_info'   => $this->getExchangeRewardInfo($params['award_list'], $userRewardInfoList[$value['uid']] ?? [], $rewardInfoMsg),
            ];
        }

        return $data;
    }

    // 处理奖励导出信息
    private function getExchangeRewardInfo(array $awardList, array $userRewardInfoList, $rewardInfoMsg): string
    {
        $rewardList = [];
        foreach ($awardList as $rank => $score) {
            $rewardList[] = str_replace(
                ['{rank}', '{score}', '{num}'],
                [$rank, $score, $userRewardInfoList[$rank] ?? 0],
                $rewardInfoMsg
            );
        }
        return implode("\n", $rewardList);
    }

    private function getTaskDataList(array $params): array
    {
        $record = XsActRankAwardUser::getListAndTotal([
            ['act_id', '=', $params['act_id']],
            ['tag_id', '=', $params['tag_id']]
        ], 'object_id, score, cycle', 'score desc', $params['page'], $params['limit']);
        if (empty($record['data'])) {
            return [];
        }
        $data = [];
        if (in_array($params['rank_object'], [BbcRankButtonTag::RANK_OBJECT_PERSONAL, BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS, BbcRankButtonTag::RANK_OBJECT_ROOM])) {
            $userList = XsUserProfile::getUserProfileBatch(Helper::arrayFilter($record['data'], 'object_id'), ['uid', 'name', 'sex']);
            $userBrokerList = XsBrokerUser::getBrokerUserBatch(Helper::arrayFilter($record['data'], 'object_id'));
            $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($userBrokerList, 'bid'));
            foreach ($record['data'] as $value) {
                $userBroker = $userBrokerList[$value['object_id']] ?? [];
                $bid = $userBroker['bid'] ?? '';
                $broker = $brokerList[$bid] ?? [];
                $bname = $broker['bname'] ?? '';
                $data[] = [
                    'act_id'        => $params['act_id'],
                    'act_name'      => $params['act_name'],
                    'time'          => Helper::now($params['start_time']) . '至' . Helper::now($params['end_time']),
                    'tag_list_type' => BbcRankButtonTag::$tagListTypeMap[$params['tag_list_type']] ?? '',
                    'cycle_time'    => $this->getCycleTime($params['tag_list_type'], $value['cycle'], $params['start_time']),
                    'uid'           => $value['object_id'],
                    'user_name'     => $userList[$value['object_id']]['name'] ?? '',
                    'sex'           => XsUserProfile::$sex_arr[$userList[$value['object_id']]['sex'] ?? ''] ?? '',
                    'bid'           => $bid,
                    'bname'         => $bname,
                    'score'         => $value['score']
                ];
            }
        } else if ($params['rank_object'] == BbcRankButtonTag::RANK_OBJECT_BROKER) {
            // object_id 为公会id
            $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($record['data'], 'object_id'), ['bid', 'bname', 'creater']);
            $brokerCreaterUids = Helper::arrayFilter($brokerList, 'creater');
            $userList = XsUserProfile::getUserProfileBatch($brokerCreaterUids);
            foreach ($record['data'] as $value) {
                $broker = $brokerList[$value['object_id']] ?? [];
                $brokerUid = $broker['creater'] ?? '';
                $data[] = [
                    'act_id'    => $params['act_id'],
                    'act_name'  => $params['act_name'],
                    'time'      => Helper::now($params['start_time']) . '至' . Helper::now($params['end_time']),
                    'bid'       => $value['object_id'],
                    'bname'     => $broker['bname'] ?? '',
                    'uid'       => $brokerUid,
                    'user_name' => $userList[$brokerUid]['name'] ?? '',
                    'score'     => $value['score']
                ];
            }
        } else if ($params['rank_object'] == BbcRankButtonTag::RANK_OBJECT_CP) {
            $objectList = XsUserIntimateRelation::getBatchCommon(Helper::arrayFilter($record['data'], 'object_id'), ['id', 'objid_1', 'objid_2']);
            $obj1 = Helper::arrayFilter($objectList, 'objid_1');
            $obj2 = Helper::arrayFilter($objectList, 'objid_2');
            $uids = Helper::formatIds(array_merge($obj1, $obj2));
            $userList = XsUserProfile::getUserProfileBatch($uids, ['uid', 'name', 'sex']);
            $userBrokerList = XsBrokerUser::getBrokerUserBatch($uids);
            $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($userBrokerList, 'bid'));
            foreach ($record['data'] as $value) {
                $user = $objectList[$value['object_id']] ?? [];
                $uid1 = $user['objid_1'] ?? 0;
                $uid2 = $user['objid_2'] ?? 0;
                $user1Broker = $userBrokerList[$uid1] ?? [];
                $bid1 = $user1Broker['bid'] ?? '';
                $broker1 = $brokerList[$bid1] ?? [];
                $bname1 = $broker1['bname'] ?? '';
                $user2Broker = $userBrokerList[$uid2] ?? [];
                $bid2 = $user2Broker['bid'] ?? '';
                $broker2 = $brokerList[$bid2] ?? [];
                $bname2 = $broker2['bname'] ?? '';
                $data[] = [
                    'act_id'        => $params['act_id'],
                    'act_name'      => $params['act_name'],
                    'time'          => Helper::now($params['start_time']) . '至' . Helper::now($params['end_time']),
                    'tag_list_type' => BbcRankButtonTag::$tagListTypeMap[$params['tag_list_type']] ?? '',
                    'cycle_time'    => $this->getCycleTime($params['tag_list_type'], $value['cycle'], $params['start_time']),
                    'uid1'          => $uid1,
                    'user1_name'    => $userList[$uid1]['name'] ?? '',
                    'sex1'          => XsUserProfile::$sex_arr[$userList[$uid1]['sex'] ?? ''] ?? '',
                    'bid1'          => $bid1,
                    'bname1'        => $bname1,
                    'uid2'          => $uid2,
                    'user2_name'    => $userList[$uid2]['name'] ?? '',
                    'sex2'          => XsUserProfile::$sex_arr[$userList[$uid2]['sex'] ?? ''] ?? '',
                    'bid2'          => $bid2,
                    'bname2'        => $bname2,
                    'score'         => $value['score']
                ];
            }
        }

        return $data;
    }


    public function formatParams(array &$params)
    {
        $params['score_source'] = @json_decode($params['score_source'], true);
        $params['rank_award'] = @json_decode($params['rank_award'], true);
        $params['vision_content_json'] = @json_decode($params['vision_content_json'], true);
    }

    public function getTypeMap(): array
    {
        return [
            ['label' => '单线任务', 'value' => BbcTemplateConfig::TYPE_TASK],
            ['label' => '积分兑换', 'value' => BbcTemplateConfig::TYPE_EXCHANGE],
        ];
    }

    // 单线任务&积分兑换支持物品类型
    public function getCommodityTypes(): array
    {
        return [
            'gift', 'coupon', 'header', 'bubble', 'effect',
            'ring', 'decorate', 'mounts'
        ];
    }
}