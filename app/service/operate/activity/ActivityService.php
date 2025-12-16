<?php

namespace Imee\Service\Operate\Activity;

use Dcat\EasyExcel\Excel;
use Imee\Comp\Common\Fixed\ImportTrait;
use Imee\Comp\Common\Log\LoggerProxy;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Config\BaseModel;
use Imee\Models\Config\BbcActWheelLotteryReward;
use Imee\Models\Config\BbcOnepkObject;
use Imee\Models\Config\BbcRankAward;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankScoreConfig;
use Imee\Models\Config\BbcRankScoreConfigNew;
use Imee\Models\Config\BbcRankWhiteList;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsActRankAwardUserExtend;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsEmoticonsMeta;
use Imee\Models\Xs\XsFamily;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsGiftBag;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xs\XsTopUpActivityReward;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserIntimateRelation;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstActiveKingdeeRecord;
use Imee\Models\Xsst\XsstOnepkObjectLog;
use Imee\Models\Xsst\XsstWheelLotteryAwardAuditRecord;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use Phalcon\Di;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Comp\Common\Sdk\SdkSlack;

class ActivityService
{
    use ImportTrait;

    const ALL_CUSTOMIZED_GIFT = 'all_customized_gift';
    const PROD_URL = 'https://page.partystar.chat';
    const DEV_URL = 'https://dev.partystar.cloud/frontend';

    public static $sexMap = [
        0 => '未知',
        1 => '男',
        2 => '女',
    ];

    const ONE_PK = 1;
    const TASK = 2;
    const WHEEL_LOTTERY = 3;

    const TYPE_TIME_ADD = 1;
    const TYPE_TIME_SUBTRACT = 2;

    /**
     * 获取关联类型
     * @param int $relateType
     * @return int|string
     */
    public function getRelateType(int $relateType)
    {
        if (empty($relateType)) {
            return '';
        }
        return $relateType == BbcTemplateConfig::ACT_TEMPLATE_TYPE_WHEEL ? BbcTemplateConfig::RELATE_TYPE_WHEEL_LOTTERY : BbcTemplateConfig::RELATE_TYPE_TASK;

    }

    public function getTimeOffsetNew(int $timeOffset): int
    {
        if ($timeOffset) {
            return (8 - ($timeOffset / 10)) * 3600;
        }

        return 0;
    }

    public function setTimeOffsetNew(int $timeOffset, bool $isConvert = true)
    {
        $timeOffset = $timeOffset == 0 ? 80 : $timeOffset;

        if ($isConvert) {
            $timeOffset /= 10;
        }
        return $timeOffset;
    }

    private function formatActivity(array &$activity): void
    {
        $dataPeriod = intval($activity['data_period']) * 86400;
        $activity['start_time'] = $activity['start_time'] - $this->getTimeOffsetNew($activity['time_offset']);
        $activity['end_time'] = $activity['end_time'] - $this->getTimeOffsetNew($activity['time_offset']) - $dataPeriod;
        $timeOffset = $this->setTimeOffsetNew($activity['time_offset']);
        $activity['time_offset'] = 'UTC: ' . ($timeOffset > 0 ? "+{$timeOffset}" : $timeOffset);
        $activity['type'] = BbcTemplateConfig::$typeMap[$activity['type']] ?? '';
        $activity['vision_type_text'] = BbcTemplateConfig::$visionTypeMap[$activity['vision_type']] ?? '';
        $activity['start_end'] = Helper::now($activity['start_time']) . '~' . Helper::now($activity['end_time']);
        $activity['big_area'] = XsBigarea::formatBigAreaName($activity['bigarea_id']);
    }

    private function formatTaskAwardContext(array $award): string
    {
        $str = '';
        $extend = @json_decode($award['award_extend_info'], true);
        switch ($award['award_type']) {
            case BbcRankAward::AWARD_TYPE_DIAMOND:
                $str = $award['num'] ? '钻石，份数：【' . $award['num'] . '】' : '钻石，返钻比例%：【' . $award['diamond_proportion'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_PACK:
                $bag = XsGiftBag::findOne($award['cid']);
                $str = '礼包，ID：【' . $award['cid'] . '_' . ($bag['name'] ?? '') . '】，份数：【' . $award['num'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_COMMODITY:
                $commodity = XsCommodityAdmin::findOneByWhere([['ocid', '=', $award['cid']]]);
                $str = '物品，ID：【' . $award['cid'] . '_' . ($commodity['name'] ?? '') . '】，份数：【' . $award['num'] . '】，资格使用天数【' . $award['exp_days'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_MEDAL:
                $medal = XsMedalResource::findOne($award['cid']);
                $name = json_decode($medal['description_zh_tw'] ?? '', true)['name'] ?? '';
                $str = '勋章，ID：【' . $award['cid'] . '_' . $name . '】，天数：【' . $award['num'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND:
                $bgc = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $award['cid']]]);
                $str = '房间背景，ID：【' . $award['cid'] . '_' . ($bgc['name'] ?? '') . '】，天数：【' . $award['num'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_PRIZE_POOL:
                $str = '奖池，瓜分比例：【' . $award['diamond_proportion'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_VIP:
                $str = 'VIP，VIP等级：【VIP' . $award['cid'] . '】，VIP天数：【' . $award['num'] . '】，发放数量：【' . ($extend['send_num'] ?? 0) . '】';
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_BG_CARD:
                $str = '自定义房间背景卡，份数：【' . $award['num'] . '】，天数：【' . $award['exp_days'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD:
                $pretty = XsCustomizePrettyStyle::findOne($award['cid']);
                $str = '自定义靓号卡，ID：【' . $award['cid'] . '_' . ($pretty['name'] ?? '') . '】，天数：【' . $award['num'] . '】，资格有效天数：【' . $award['exp_days'] . '】，发放数量：【' . ($extend['send_num'] ?? 0) . '】';
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD:
                $card = XsRoomTopCard::findOne($award['cid']);
                $name = json_decode($card['name_json'] ?? '', true)['cn'] ?? '';
                $str = '房间置顶卡，ID：【' . $award['cid'] . '_' . $name . '】，份数：【' . $award['num'] . '】，资格有效天数：【' . $award['exp_days'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_ROOM_SKIN:
                $skin = XsRoomSkin::findOne($award['cid']);
                $str = '房间皮肤，ID：【' . $award['cid'] . '_' . ($skin['name'] ?? '') . '】，天数：【' . $award['num'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON:
                $certification = XsCertificationSign::findOne($award['cid']);
                $str = '认证图标，ID：【' . $award['cid'] . '_' . ($certification['name'] ?? '') . '】，天数：【' . $award['num'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_GAME_COUPON:
                $str .= '游戏优惠券，ID：【' . $award['cid'] . '】，数量：【' . $award['num'] . '】有效期：【' . ($award['exp_days'] <= 7 ? '本周失效' : '下周失效') . '】';
                break;
            case BbcRankAward::AWARD_TYPE_CUSTOMIZATION:
                $str .= '自定义奖励，预览图：【' . Helper::getHeadUrl($extend['icon']) . '】，自定义描述：【' . $extend['content'] . '】有效期：【' . $award['exp_days'] . '】，数量：【' . $award['num'] . '】';
                break;
            case BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING:
                $nameIdLighting = XsNameIdLightingGroup::findOne($award['cid']);
                $name = XsNameIdLightingGroup::formatName(json_decode($nameIdLighting['name'] ?? [], true));
                $str = '炫彩资源，ID：【' . $award['cid'] . '_' . $name . '】，天数：【' . $award['num'] . '】，资格有效天数：【' . $award['exp_days'] . '】，发放数量：【' . ($extend['send_num'] ?? 0) . '】';
                break;
            case BbcRankAward::AWARD_TYPE_ITEM_CARD:
                $itemCard = XsItemCard::findOne($award['cid']);
                $name = json_decode($itemCard['name_json'] ?? '', true)['zh_cn'] ?? '';
                $str = 'mini卡装扮，ID：【' . $award['cid'] . '_' . $name . '】，天数：【' . $award['num'] . '】，资格有效天数：【' . $award['exp_days'] . '】，发放数量：【' . ($extend['send_num'] ?? 0) . '】';
                break;
            case BbcRankAward::AWARD_TYPE_PROP_CARD:
                $propCard = XsPropCard::findOne($award['cid']);
                $propCardConfig = XsPropCardConfig::findOne($propCard['prop_card_config_id']);
                $name = @json_decode($propCardConfig['name_json'] ?? '', true)['cn'] ?? '';
                $str = 'pk道具卡，ID：【' . $award['id'] . '_' . $name . '】， 数量：【' . $award['num'] . '】，有效小时【' . ($award['exp_days'] ?? 0) . '】';
                break;
            case BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD:
                $str = '开屏卡，数量：【' . $award['num'] . '】，是否可赠送【' . (($award['can_transfer'] ?? 0) ? '是' : '否') . '】，有效小时：【' . ($extend['days'] ?? 0) . '】，过期时间【' . ($award['exp_days'] ? Helper::now($award['exp_days']) : '') . '】';
                break;
            case BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD:
                $itemCard = XsItemCard::findOne($award['cid']);
                $name = json_decode($itemCard['name_json'] ?? '', true)['zh_cn'] ?? '';
                $str = '个人主页卡装扮，ID：【' . $award['cid'] . '_' . $name . '】，天数：【' . $award['num'] . '】，资格有效天数：【' . $award['exp_days'] . '】，发放数量：【' . ($extend['send_num'] ?? 0) . '】';
                break;
            case BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD:
                $str = '定制表情卡，数量：【' . $award['num'] . '】，生效天数：【' . ($extend['days'] ?? 0) . '】，过期时间：【' . ($award['exp_days'] ? Helper::now($award['exp_days']) : '') . '】，是否赠送：【' . ($award['can_transfer'] ? '是' : '否') . '】';
                break;    
        }

        return $str;
    }

    private function taskConfigExport($activity)
    {
        $buttonTag = BbcRankButtonTag::findOneByWhere([['act_id', '=', $activity['id']],]);
        $buttonList = BbcRankButtonList::findOneByWhere([['act_id', '=', $activity['id']],]);
        $awardList = BbcRankAward::getListByWhere([
            ['act_id', '=', $activity['id']]
        ], '*', 'rank asc');
        $scoreList = $this->formatScoreConfig($activity['id'], $buttonList['id']);
        $this->formatActivity($activity);
        $data = [
            ['活动标题:', $activity['title']],
            ['活动大区:', $activity['big_area']],
            ['活动语言:', $activity['language']],
            ['统计时区:', $activity['time_offset']],
            ['活动时间:', $activity['start_end']],
            ['活动视觉:', $activity['vision_type_text']],
            ['活动对象:', BbcRankButtonTag::$rankObjectMap[$buttonTag['rank_object']] ?? ''],
            ['任务对象:', BbcRankButtonTag::$subRankObject[$buttonTag['sub_rank_object']] ?? ''],
            ['引言', $buttonList['button_desc'] ?? ''],
        ];
        if ($scoreList) {
            foreach ($scoreList as $sKey => $score) {
                if ($score['source_type'] == BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY) {
                    [$actId, $buttonListId] = explode('_', $score['scope']);
                    $list = BbcRankButtonList::findOneByWhere([['act_id', '=', $actId], ['id', '=', $buttonListId]], 'level');
                    $scopeName = sprintf('%d-%d-%s玩法', $actId, $buttonListId, BbcRankButtonList::$wheelLotteryLevelMap[$list['level']] ?? '');
                } else {
                    $scopeName = array_map(function ($item) {
                        return BbcRankScoreConfigNew::$scoreScopeMap[$item];
                    }, $score['scope']);
                }
                
                $sourceTypeName = BbcRankScoreConfigNew::$sourceTypeMap[$score['source_type']] ?? '';
                $isOnlyCrossRoomPk = $score['is_only_cross_room_pk'] ?? 0;
                $isOnlyCrossRoomPkText = $isOnlyCrossRoomPk ? '是' : '否';
                
                $sourceInfo = "积分来源：【{$sourceTypeName}】\n";
                $sourceInfo .= '统计范围：【' . (is_array($scopeName) ? implode("，", $scopeName) : $scopeName) . '】';
                
                if ($buttonTag['rank_object'] == BbcRankButtonTag::RANK_OBJECT_ROOM && 
                    $score['source_type'] == BbcRankScoreConfigNew::SOURCE_TYPE_GIFT && 
                    count($score['scope']) == 1 && 
                    in_array($score['scope'][0], [BbcRankScoreConfigNew::SCORE_SCOPE_CHAT, BbcRankScoreConfigNew::SCORE_SCOPE_LIVE])) {
                    $sourceInfo .= "\n是否只统计跨房pk：【{$isOnlyCrossRoomPkText}】";
                }
                
                $sourceConfig = [];
                foreach ($score['source_config'] as $config) {
                    $configText = '积分统计方式：【' . BbcRankScoreConfigNew::$scoreTypeMap[$config['type']] . '】';
                    $giftId = str_replace("\n", ',', $config['gift_id']);
                    if ($giftId) {
                        $configText .= "\n礼物ID：【" . $giftId . '】';
                    }
                    $unitText = BbcRankScoreConfigNew::$scoreUnitMap[$config['type']] ?? '分值';
                    $configText .= "\n{$unitText}：【" . $config['score'] . '】分';
                    
                    if (in_array($config['type'], [BbcRankScoreConfigNew::SCORE_TYPE_PK_WIN, BbcRankScoreConfigNew::SCORE_TYPE_PK_END])) {
                        $pkValidType = $config['pk_valid_type'] ?? null;
                        if ($pkValidType !== null) {
                            $isVideoRoom = count($score['scope']) == 1 && in_array(BbcRankScoreConfigNew::SCORE_SCOPE_LIVE, $score['scope']);
                            $pkValidTypeText = BbcRankScoreConfigNew::$pkValidTypeMap[$pkValidType] ?? '';
                            if ($isVideoRoom) {
                                $pkValidTypeText = str_replace('收礼达标', '值达标', $pkValidTypeText);
                            }
                            $configText .= "\n有效场次要求：【" . $pkValidTypeText . '】';
                            $pkTime = $config['pk_time'] ?? 0;
                            $pkGift = $config['pk_gift'] ?? 0;
                            
                            if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                                $configText .= "\n单场pk时长≥【{$pkTime}】分钟";
                            }
                            if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT])) {
                                $pkGiftLabel = $isVideoRoom ? '单场pk本方队伍值≥' : '单场pk房间内收礼≥';
                                $configText .= "\n且{$pkGiftLabel}【{$pkGift}】钻石";
                            } elseif ($pkValidType == BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT) {
                                $pkGiftLabel = $isVideoRoom ? '单场pk本方队伍值≥' : '单场pk房间内收礼≥';
                                $configText .= "\n或{$pkGiftLabel}【{$pkGift}】" . ($isVideoRoom ? '' : '钻石');
                            }
                        }
                    }
                    
                    $sourceConfig[] = $configText;
                }
                
                $data[] = [
                    $sKey == 0 ? '积分统计来源' : '',
                    $sourceInfo,
                    implode("\n", $sourceConfig)
                ];
            }
        }
        if ($awardList) {
            $rankList = array_column($awardList, null, 'rank');
            foreach ($rankList as $rank => $award) {
                $awardArr = [];
                foreach ($awardList as $item) {
                    if ($item['rank'] == $rank) {
                        $awardArr[] = $this->formatTaskAwardContext($item);
                    }
                }
                $data[] = [
                    $rank == 1 ? '任务档位及奖励配置' : '',
                    'Lv.' . $rank . '目标：' . $award['score_min'],
                    implode("\n", $awardArr)
                ];
            }
        }

        return $data;
    }

    private function taskMultiConfigExport($activity): array
    {
        $this->formatActivity($activity);
        $rankObject = BbcRankButtonTag::findOneByWhere([
                ['act_id', '=', $activity['id']]
            ])['rank_object'] ?? 0;
        $data = [
            ['活动标题:', $activity['title']],
            ['活动大区:', $activity['big_area']],
            ['活动语言:', $activity['language']],
            ['统计时区:', $activity['time_offset']],
            ['活动时间:', $activity['start_end']],
            ['任务对象:', BbcRankButtonTag::$rankObjectMap[$rankObject] ?? ''],
        ];

        $tagList = BbcRankButtonTag::getListByWhere([
            ['act_id', '=', $activity['id']]
        ], 'id, button_tag_type, button_content, tag_list_type', 'id asc');

        if ($tagList) {
            $tabNum = count($tagList);
            foreach ($tagList as $tag) {
                $data[] = [
                    sprintf('任务%s的配置如下',
                        $this->getTabNum($tabNum, $tag['button_tag_type'])
                    )
                ];
                $data[] = [
                    '任务周期',
                    $tag['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_DAY ? '日循环' : '单次'
                ];
                $data[] = ['任务名称', $tag['button_content']];
                $list = BbcRankButtonList::getListByWhere([
                    ['act_id', '=', $activity['id']],
                    ['button_tag_id', '=', $tag['id']]
                ], 'id', 'id asc');
                foreach ($list as $key => $item) {
                    $scoreList = $this->formatScoreConfig($activity['id'], $item['id']);
                    $scoreMsg = '';
                    $scopeName = [];
                    $firstScore = 0;
                    if ($scoreList) {
                        foreach ($scoreList as $score) {
                            if ($score['source_type'] == BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY) {
                                [$actId, $buttonListId] = explode('_', $score['scope']);
                                $wheelList = BbcRankButtonList::findOneByWhere([['act_id', '=', $actId], ['id', '=', $buttonListId]], 'level');
                                $scopeName[] = sprintf('%d-%d-%s玩法', $actId, $buttonListId, BbcRankButtonList::$wheelLotteryLevelMap[$wheelList['level']] ?? '');
                            } else {
                                $scopeName = array_merge($scopeName, array_map(function ($item) {
                                    return BbcRankScoreConfigNew::$scoreScopeMap[$item];
                                }, $score['scope']));
                            }
                            
                            $isOnlyCrossRoomPk = $score['is_only_cross_room_pk'] ?? 0;
                            $isOnlyCrossRoomPkText = $isOnlyCrossRoomPk ? '是' : '否';
                            
                            foreach ($score['source_config'] as $config) {
                                if ($firstScore == 0) {
                                    $firstScore = $config['score'];
                                }
                                $configText = BbcRankScoreConfigNew::$scoreTypeMap[$config['type']] ?? '';
                                $giftId = str_replace("\n", ',', $config['gift_id']);
                                if ($giftId) {
                                    $configText .= '，' . $giftId;
                                }
                                $configText .= '，分值【' . $config['score'] . '】';
                                
                                if ($rankObject == BbcRankButtonTag::RANK_OBJECT_ROOM && 
                                    $score['source_type'] == BbcRankScoreConfigNew::SOURCE_TYPE_GIFT && 
                                    count($score['scope']) == 1 && 
                                    in_array($score['scope'][0], [BbcRankScoreConfigNew::SCORE_SCOPE_CHAT, BbcRankScoreConfigNew::SCORE_SCOPE_LIVE])) {
                                    $configText .= '，是否只统计跨房pk【' . $isOnlyCrossRoomPkText . '】';
                                }
                                
                                if (in_array($config['type'], [BbcRankScoreConfigNew::SCORE_TYPE_PK_WIN, BbcRankScoreConfigNew::SCORE_TYPE_PK_END])) {
                                    $pkValidType = $config['pk_valid_type'] ?? null;
                                    if ($pkValidType !== null) {
                                        $isVideoRoom = count($score['scope']) == 1 && in_array(BbcRankScoreConfigNew::SCORE_SCOPE_LIVE, $score['scope']);
                                        $pkValidTypeText = BbcRankScoreConfigNew::$pkValidTypeMap[$pkValidType] ?? '';
                                        if ($isVideoRoom) {
                                            $pkValidTypeText = str_replace('收礼达标', '值达标', $pkValidTypeText);
                                        }
                                        $configText .= '，有效场次要求【' . $pkValidTypeText . '】';
                                        $pkTime = $config['pk_time'] ?? 0;
                                        $pkGift = $config['pk_gift'] ?? 0;
                                        
                                        if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                                            $configText .= '，单场pk时长≥【' . $pkTime . '】';
                                        }
                                        if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT])) {
                                            $pkGiftLabel = $isVideoRoom ? '单场pk本方队伍值≥' : '单场pk房间内收礼≥';
                                            $configText .= '，且' . $pkGiftLabel . '【' . $pkGift . '】';
                                        } elseif ($pkValidType == BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT) {
                                            $pkGiftLabel = $isVideoRoom ? '单场pk本方队伍值≥' : '单场pk房间内收礼≥';
                                            $configText .= '，或' . $pkGiftLabel . '【' . $pkGift . '】' . ($isVideoRoom ? '' : '钻石');
                                        }
                                    }
                                }
                                
                                if ($scoreMsg) {
                                    $scoreMsg .= "\n";
                                }
                                $scoreMsg .= $configText;
                            }
                        }
                    }
                    $data[] = [
                        '任务' . ($key + 1),
                        implode("，", array_unique($scopeName)),
                        $scoreMsg
                    ];
                    $awardList = BbcRankAward::getListByWhere([
                        ['act_id', '=', $activity['id']],
                        ['button_list_id', '=', $item['id']]
                    ], '*', 'rank asc');
                    $awardConfig = [];
                    if ($awardList) {
                        foreach ($awardList as $award) {
                            $awardConfig[] = $this->formatTaskAwardContext($award);
                        }
                    }
                    $data[] = [
                        '',
                        '目标分值' . $firstScore,
                        implode("\n", $awardConfig)
                    ];
                }
                $data[] = [];
            }
        }
        return $data;
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

    public function wheelLotteryConfigExport(array $activity, int $type = XsstActiveKingdeeRecord::TYPE_WHEEL_LOTTERY): array
    {
        $activity = BbcTemplateConfig::findOne($activity['id']);
        $buttonList = BbcRankButtonList::getListByWhere([['act_id', '=', $activity['id']]], 'id, level, button_content, score_min', 'level asc');
        $listIds = array_column($buttonList, 'id');
        $listMap = array_column($buttonList, null, 'id');
        $awardList = BbcActWheelLotteryReward::getListByWhere([
            ['act_id', '=', $activity['id']],
            ['list_id', 'IN', $listIds]
        ]);
        $awardList = array_column($awardList, null, 'list_id');
        $lotteryConsumes = array_column($awardList, 'lottery_consume', 'list_id');
        $scoreList = $this->formatScoreConfig($activity['id'], $buttonList[0]['id']);
        $this->formatActivity($activity);
        $data = [
            ['活动标题:', $activity['title']],
            ['活动大区:', $activity['big_area']],
            ['活动语言:', $activity['language']],
            ['统计时区:', $activity['time_offset']],
            ['活动时间:', $activity['start_end']],
        ];
        if ($scoreList) {
            foreach ($scoreList as $sKey => $score) {
                $scopeName = array_map(function ($item) {
                    return BbcRankScoreConfigNew::$scoreScopeMap[$item];
                }, $score['scope']);
                $sourceConfig = [];
                foreach ($score['source_config'] as $config) {
                    $sourceConfig[] = BbcRankScoreConfigNew::$scoreTypeMap[$config['type']] . '，' . str_replace("\n", ',', $config['gift_id']) . '，分值【' . $config['score'] . '】';
                }
                $data[] = [
                    $sKey == 0 ? '积分来源' : '',
                    implode("，", $scopeName),
                    implode("\n", $sourceConfig)
                ];
            }
        }
        if ($awardList) {
            if ($type == XsstActiveKingdeeRecord::TYPE_WHEEL_LOTTERY_STOCK) {
                foreach ($listMap as $listId => $list) {
                    $level = $list['level'];
                    $recordList = XsstWheelLotteryAwardAuditRecord::getListByWhere([
                        ['status', '=', XsstWheelLotteryAwardAuditRecord::STATUS_WAIT],
                        ['act_id', '=', $activity['id']],
                        ['list_id', '=', $listId]
                    ]);
                    $data[] = [
                        $level == 1 ? '补充库存:' : '',
                        BbcRankButtonList::$wheelLotteryLevelMap[$level] . '抽奖',
                        ''
                    ];
                    $data[] = [
                        '',
                        '单次抽奖消耗积分',
                        $lotteryConsumes[$listId] ?? ''
                    ];
                    foreach ($recordList as $record) {
                        $data[] = [
                            '',
                            '奖品' . $record['award_number'],
                            sprintf('%s，份数【%d】，原抽出上限：%d，补充库存：%d',
                                BbcActWheelLotteryReward::$rewardTypeMap[$record['award_type']] ?? '',
                                $record['number'],
                                $record['before_number'],
                                $record['number'])
                        ];
                    }
                }
            } else {
                foreach ($listMap as $listId => $list) {
                    $level = $list['level'];
                    $awardConfig = $awardList[$listId] ?? [];
                    if (empty($awardConfig)) {
                        continue;
                    }

                    $data[] = [
                        $level == 1 ? '奖品及预期配置:' : '',
                        BbcRankButtonList::$wheelLotteryLevelMap[$level] . '抽奖',
                        '',
                    ];
                    $data[] = [
                        '',
                        'tab标题',
                        $list['button_content'],
                    ];
                    $data[] = [
                        '',
                        '单次消耗积分',
                        $awardConfig['lottery_consume'],
                    ];
                    $data[] = [
                        '',
                        '是否设置解决门槛',
                        $list['score_min'] > 0 ? '是' : '否',
                    ];
                    $data[] = [
                        '',
                        '抽奖资格解锁门槛',
                        $list['score_min'],
                    ];
                    $awardJson = @json_decode($awardConfig['award_list'], true);
                    $totalWeight = array_sum(array_column($awardJson, 'weight'));
                    foreach ($awardJson as $award) {
                        $data[] = [
                            '',
                            '奖品' . $award['number'],
                            '权重：' . $award['weight'] . '，中奖预期：' . round($award['weight'] / $totalWeight * 100, 2) . '%，抽出上限：' . ($award['stock'] > 1 ? $award['stock'] : '无上限'),
                            $this->formatWheelLotteryAwardContext($award),
                        ];
                    }
                }
            }
        }

        return $data;
    }

    private function formatWheelLotteryAwardContext(array $award): string
    {
        $str = '';
        switch ($award['type']) {
            case BbcActWheelLotteryReward::REWARD_TYPE_ACTIVITY_DIAMOND:
                $str = '钻石，份数：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_GIFT_BAG:
                $bag = XsGiftBag::findOne($award['id']);
                $str = '礼包，ID：【' . $award['id'] . '_' . ($bag['name'] ?? '') . '】，份数：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_COMMODITY:
                $commodity = XsCommodityAdmin::findOneByWhere([['ocid', '=', $award['id']]]);
                $str = '物品，ID：【' . $award['id'] . '_' . ($commodity['name'] ?? '') . '】，份数：【' . $award['num'] . '】，资格使用天数【' . ($award['exp_days'] ?? '') . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_ROOM_SKIN:
                $skin = XsRoomSkin::findOne($award['id']);
                $str = '房间皮肤，ID：【' . $award['id'] . '_' . ($skin['name'] ?? '') . '】，天数：【' . $award['days'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_MEDAL:
                $medal = XsMedalResource::findOne($award['id']);
                $name = json_decode($medal['description_zh_tw'] ?? '', true)['name'] ?? '';
                $str = '勋章，ID：【' . $award['id'] . '_' . $name . '】，天数：【' . $award['days'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_VIP:
                $str = 'VIP【' . $award['id'] . '】，天数：【' . $award['days'] . '】，发放数量：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BG_CARD:
                $str = '自定义房间背景卡，天数：【' . $award['days'] . '】，份数：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_PRETTY_ID_CARD:
                $pretty = XsCustomizePrettyStyle::findOne($award['id']);
                $str = '自选靓号卡，ID：【' . $award['id'] . '_' . ($pretty['name'] ?? '') . '】，天数：【' . $award['days'] . '】，资格使用天数【' . $award['exp_days'] . '】，发放数量：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_EMOTICONS:
                $emoticons = XsEmoticonsMeta::findOne($award['id']);
                $name = json_decode($emoticons['detail'], true)[0]['name']['cn'] ?? '';
                $str = '表情包，ID：【' . $award['id'] . '_' . $name . '】，天数：【' . $award['days'] . '】，份数【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_ROOM_BACKGROUND:
                $bgc = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $award['id']]]);
                $str = '房间背景，ID：【' . $award['id'] . '_' . ($bgc['name'] ?? '') . '】，份数：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_ROOM_TOP_CARD:
                $card = XsRoomTopCard::findOne($award['id']);
                $name = json_decode($card['name_json'], true)['cn'] ?? '';
                $str = '房间置顶卡，ID：【' . $award['id'] . '_' . $name . '】天数：【' . $award['days'] . '】，份数【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_CERTIFICATION_ICON:
                $certification = XsCertificationSign::findOne($award['id']);
                $str = '认证图标，ID：【' . $award['id'] . '_' . ($certification['name'] ?? '') . '】，天数：【' . $award['days'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON:
                $str .= '游戏优惠券，ID：【' . $award['id'] . '】，数量：【' . $award['num'] . '】有效期：【' . ($award['exp_days'] <= 7 ? '本周失效' : '下周失效') . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_START:
                $str = '谢谢惠顾';
                break;
            case BbcActWheelLotteryReward::REWARD_NAME_ID_LIGHTING:
                $nameIdLighting = XsNameIdLightingGroup::findOne($award['id']);
                $name = XsNameIdLightingGroup::formatName(json_decode($nameIdLighting['name'] ?? [], true));
                $str = '炫彩资源，ID：【' . $award['id'] . '_' . $name . '】，天数：【' . $award['days'] . '】，资格使用天数【' . $award['exp_days'] . '】，发放数量：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_MINI_CARD_DRESS:
                $itemCard = XsItemCard::findOne($award['id']);
                $name = json_decode($itemCard['name_json'] ?? '', true)['zh_cn'] ?? '';
                $str = 'mini卡装扮，ID：【' . $award['id'] . '_' . $name . '】，天数：【' . $award['days'] . '】，资格使用天数【' . $award['exp_days'] . '】，发放数量：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_PROP_CARD:
                $propCard = XsPropCard::findOne($award['id']);
                $propCardConfig = XsPropCardConfig::findOne($propCard['prop_card_config_id'] ?? 0);
                $name = @json_decode($propCardConfig['name_json'] ?? '', true)['cn'] ?? '';
                $str = 'pk道具卡，ID：【' . $award['id'] . '_' . $name . '】， 数量：【' . $award['num'] . '】，有效小时【' . $award['exp_days'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_OPEN_SCREEN_CARD:
                $str = '开屏卡，数量：【' . $award['num'] . '】，是否可赠送【' . (($award['extend_info']['extend_type'] ?? 0) ? '是' : '否') . '】，有效小时：【' . $award['days'] . '】，过期时间【' . ($award['exp_days'] ? Helper::now($award['exp_days']) : '') . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_HOMEPAGE_CARD:
                $itemCard = XsItemCard::findOne($award['id']);
                $name = json_decode($itemCard['name_json'] ?? '', true)['zh_cn'] ?? '';
                $str = '个人主页装扮卡装扮，ID：【' . $award['id'] . '_' . $name . '】，天数：【' . $award['days'] . '】，资格使用天数【' . $award['exp_days'] . '】，发放数量：【' . $award['num'] . '】';
                break;
            case BbcActWheelLotteryReward::REWARD_TYPE_CUSTOMIZED_EMOTICON_CARD:
                $str = '定制表情卡，数量：【' . $award['num'] . '】，有效天数：【' . $award['days'] . '】，过期时间【' . ($award['exp_days'] ? Helper::now($award['exp_days']) : '') . '】，是否可赠送：【' . (($award['extend_info']['extend_type'] ?? 0) ? '是' : '否') . '】';
                break;    
        }

        return $str;
    }

    public function getActiveCsv(int $actId): array
    {
        try {
            $file = PUBLIC_DIR . DS . '系统自动导出的配置明细.xlsx';
            $sheetType = [
                BbcTemplateConfig::TYPE_RANK          => '榜单玩法',
                BbcTemplateConfig::TYPE_TASK          => '任务玩法',
                BbcTemplateConfig::TYPE_MULTI_TASK    => '任务玩法',
                BbcTemplateConfig::TYPE_WHEEL_LOTTERY => '幸运玩法',
            ];
            $sheets = [];

            while (true) {
                if (empty($actId)) {
                    break;
                }

                $activity = BbcTemplateConfig::findOne($actId);
                $title = $sheetType[$activity['type']];
                switch ($activity['type']) {
                    case BbcTemplateConfig::TYPE_RANK:
                        $data = $this->rankConfigExport($activity);
                        break;
                    case BbcTemplateConfig::TYPE_TASK:
                        $data = $this->taskConfigExport($activity);
                        break;
                    case BbcTemplateConfig:: TYPE_MULTI_TASK:
                        $data = $this->taskMultiConfigExport($activity);
                        break;
                    case BbcTemplateConfig::TYPE_WHEEL_LOTTERY:
                        $data = $this->wheelLotteryConfigExport($activity);
                        break;
                }

                $data && $sheets[] = Excel::createSheet($data, $title)->headings(['活动模版', $title, '', '']);

                if (empty($activity['relate_id'])) {
                    break;
                }
                $actId = $activity['relate_id'];
            }

            Excel::export($sheets)->store($file);
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }

        return [true, $file];
    }

    private function rankConfigExport($activity)
    {
        $this->formatActivity($activity);
        $data = [
            ['活动名称', $activity['title']],
            ['活动大区', $activity['big_area']],
            ['活动周期', $activity['start_end']],
            ['活动时区', $activity['time_offset']],
            ['活动语言', $activity['language']],
            ['活动模版', $activity['type']],
            ['活动视觉', $activity['vision_type_text']],
            []
        ];
        if ($activity['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            $this->weekGiftRewardExport($data, $activity);
        } else if ($activity['vision_type'] == BbcTemplateConfig::VISION_TYPE_CUSTOMIZED) {
            $this->customizedGiftRewardExport($data, $activity);
        } else {
            $this->otherRewardExport($data, $activity);
        }
        return $data;
    }

    private function customizedGiftRewardExport(array &$data, array $activity): void
    {
        $data[] = ['活动礼物ID', $activity['gift_act_ids'] == 'all_customized_gift' ? '实时拉取所有定制礼物' : $activity['gift_act_ids']];
        $data[] = [];

        $buttonList = BbcRankButtonList::getListByWhere([['act_id', '=', $activity['id']]]);
        $tag = BbcRankButtonTag::findOne($buttonList[0]['button_tag_id']);
        foreach ($buttonList as $list) {
            $data[] = ['榜单名称', $list['button_content']];
            $data[] = ['榜单类型', BbcRankButtonList::$rankTag[$list['rank_tag']] ?? ''];
            $data[] = ['房间类型', BbcRankButtonList::$roomSupportMap[$list['room_support']] ?? ''];
            $data[] = ['榜单奖励配置', $this->listRewardConfig($list, $tag, $activity)];
            $data[] = [];
        }
    }

    private function otherRewardExport(array &$data, array $activity): void
    {
        $buttonList = BbcRankButtonList::getListByWhere([['act_id', '=', $activity['id']]]);
        $tagList = BbcRankButtonTag::getBatchCommon(Helper::arrayFilter($buttonList, 'button_tag_id'), ['id', 'button_content', 'rank_object']);
        foreach ($buttonList as $list) {
            $data[] = ['榜单名称', $tagList[$list['button_tag_id']]['button_content']];
            $data[] = ['榜单对象', BbcRankButtonTag::$rankObjectMap[$tagList[$list['button_tag_id']]['rank_object']] ?? ''];
            $data[] = ['房间类型', BbcRankButtonList::$roomSupportMap[$list['room_support']] ?? ''];
            $data[] = ['榜单引言', $list['button_desc']];
            $data[] = ['积分类型', $this->listScoreConfig($list)];
            $data[] = ['榜单奖励配置', $this->listRewardConfig($list, $tagList[$list['button_tag_id']], $activity)];
            $data[] = [];
        }
    }

    private function weekGiftRewardExport(array &$data, array $activity): void
    {
        $masterTag = BbcRankButtonTag::findOneByWhere([
            ['act_id', '=', $activity['id']],
            ['rank_object', '=', BbcRankButtonTag::RANK_OBJECT_WEEK_STAR]
        ]);
        $data[] = ['单次循环周星礼物数量', $activity['cycles']];
        $data[] = ['活动礼物', str_replace(',', "\n", $masterTag['cycle_gift_id'])];
        $buttonList = BbcRankButtonList::getListByWhere([
            ['act_id', '=', $activity['id']],
            ['rank_tag', 'IN', [BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT_SUB_ACCEPT, BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT_SUB_PAY]]
        ], 'id, button_tag_id, rank_tag');
        $tagList = BbcRankButtonTag::getBatchCommon(Helper::arrayFilter($buttonList, 'button_tag_id'), ['id', 'rank_object', 'button_content']);
        foreach ($buttonList as $list) {
            $data[] = ['榜单名称', $tagList[$list['button_tag_id']]['button_content']];
            $data[] = ['榜单类型', BbcRankButtonList::$rankTag[$list['rank_tag']] ?? ''];
            $data[] = ['榜单奖励配置', $this->listRewardConfig($list, $tagList[$list['button_tag_id']], $activity)];
            $data[] = [];
        }
    }

    private function listScoreConfig(array $list): string
    {
        $configList = [];
        $scoreList = BbcRankScoreConfig::getListByWhere([
            ['button_list_id', '=', $list['id']]
        ]);

        $str = '积分类型：【%s】，分值：【%d】';
        foreach ($scoreList as $score) {
            $scoreType = BbcRankScoreConfig::$types[$score['type']];
            if (in_array($score['type'], [BbcRankScoreConfig::TYPE_PAY_GIFT_ID, BbcRankScoreConfig::TYPE_PAY_GIFT_NUM, BbcRankScoreConfig::TYPE_ACCEPT_GIFT_ID, BbcRankScoreConfig::TYPE_ACCEPT_GIFT_NUM])) {
                $scoreType .= '，' . $score['gift_id'];
            }
            $configList[] = sprintf($str, $scoreType, $score);
        }

        return implode("\n", $configList);
    }

    private function listRewardConfig(array $list, array $tag, array $activity): string
    {
        $configList = [];
        $rewardList = BbcRankAward::getListByWhere([
            ['button_list_id', '=', $list['id']]
        ]);
        $str = '发放对象：【%s】，发放条件：【%s】，%s，奖励类型：【%s】，%s';
        foreach ($rewardList as $reward) {
            $sendObject = $this->getSendObject($tag['rank_object'], $reward['award_object_type'] ?? 0, $activity['vision_type']);
            $rankAwardType = BbcRankAward::$rankAwardType[$reward['rank_award_type']] ?? '';
            $sendWhere = $this->getSendWhere($reward);
            $awardType = BbcRankAward::$awardTypeAllMap[$reward['award_type']] ?? '';
            $awardConfig = $this->formatTaskAwardContext($reward);
            $configList[] = sprintf($str, $sendObject, $rankAwardType, $sendWhere, $awardType, $awardConfig);
        }

        return implode("\n", $configList);
    }

    private function getSendObject(int $rankObject, int $objectType, int $visionType): string
    {
        if ($visionType == BbcTemplateConfig::VISION_TYPE_CUSTOMIZED) {
            return BbcRankAward::$awardObjectTypeCustomizationMap[$objectType] ?? '';
        }
        // 当Button对象为用户，CP时，发放对象为：用户
        if (in_array($rankObject, [BbcRankButtonTag::RANK_OBJECT_PERSONAL, BbcRankButtonTag::RANK_OBJECT_CP])) {
            return '用户';
        }

        // 当Button对象为公会成员时，发放对象为：公会成员
        if ($rankObject == BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS) {
            return '公会成员';
        }

        $objectMap = [];
        switch ($rankObject) {
            case BbcRankButtonTag::RANK_OBJECT_BROKER:
                $objectMap = ['公会长', '公会成员'];
                break;
            case BbcRankButtonTag::RANK_OBJECT_ROOM:
                $objectMap = ['房主', '房间成员'];
                break;
            case BbcRankButtonTag::RANK_OBJECT_FAMILY:
                $objectMap = ['家族长', '家族成员'];
                break;
            case BbcRankButtonTag::RANK_OBJECT_ANCHOR:
                $objectMap = ['主播', '贡献用户'];
                break;
        }

        return $objectMap[$objectType] ?? '';
    }

    private function getSendWhere(array $reward): string
    {
        $string = '';
        switch ($reward['rank_award_type']) {
            case BbcRankAward::RANK_AWARD_TYPE_RANK:
                $string .= '名次：' . $reward['rank'];
                break;
            case BbcRankAward::RANK_AWARD_TYPE_SCORE:
                $string .= '门槛：' . $reward['score_min'] . '~' . $reward['score_max'];
                break;
            case BbcRankAward::RANK_AWARD_TYPE_RANK_SCORE:
                $string .= '名次：' . $reward['rank'] . '，门槛：' . $reward['score_min'] . '~' . $reward['score_max'];
                break;
        }

        return $string;
    }

    public function getWheelLotteryStockCsv(int $actId): array
    {
        try {
            $file = PUBLIC_DIR . DS . '系统自动导出的配置明细.xlsx';
            $activity = BbcTemplateConfig::findOne($actId);
            $data = $this->wheelLotteryConfigExport($activity, XsstActiveKingdeeRecord::TYPE_WHEEL_LOTTERY_STOCK);
            $headings = ['活动模版', '幸运玩法', '', ''];
            $sheet1 = Excel::createSheet($data, '幸运玩法（补库存）')->headings($headings);
            Excel::export([$sheet1])->store($file);
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }

        return [true, $file];
    }

    private function formatRechargeChannels($rechargeChannels)
    {
        if (empty($rechargeChannels)) {
            return '';
        }
        $channelArr = [];
        foreach (explode(',', $rechargeChannels) as $channel) {
            $channelArr[] = XsTopUpActivity::$channelMap[$channel];
        }

        return $channelArr;
    }

    private function formatRechargeAwardContext(array $award)
    {
        $str = '';
        switch ($award['type']) {
            case XsTopUpActivityReward::AWARD_TYPE_DIAMOND:
                $str = '钻石，钻石面额：【' . $award['num'] . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_COMMODITY:
                $str = '物品，ID：【' . $award['id'] . '_' . $award['name'] . '】，份数：【' . $award['num'] . '】，资格使用天数【' . ($award['exp_days'] ?? '') . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_MEDAL:
                $str = '勋章，ID：【' . $award['id'] . '_' . $award['name'] . '】，天数：【' . $award['exp_days'] . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND:
                $str = '房间背景，ID：【' . $award['id'] . '_' . $award['name'] . '】，天数：【' . $award['exp_days'] . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_VIP:
                $str = 'VIP，VIP等级：【VIP' . $award['id'] . '】，VIP天数：【' . $award['num'] . '】，发放数量：【' . ($award['award_expand']['number'] ?? 0) . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_EXP:
                break;
            case XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID:
                $pretty = XsCustomizePrettyStyle::findOne($award['id']);
                $str = '自选靓号，ID：【' . $award['id'] . '_' . ($pretty['name'] ?? '') . '】，天数：【' . $award['num'] . '】，资格有效使用：【' . $award['exp_days'] . '】，发放数量：【' . ($award['award_expand']['number'] ?? 0) . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ACTIVITY_CUSTOMIZE:
                $str = '自定义奖励，定制礼物名额*10个balabalabal';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD:
                $str = '自定义房间背景卡，份数：【' . $award['num'] . '】，天数：【' . $award['exp_days'] . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_SKIN:
                $skin = XsRoomSkin::findOne($award['id']);
                $str = '房间皮肤，ID：【' . $award['id'] . '_' . ($skin['name'] ?? '') . '】，天数：【' . $award['exp_days'] . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_CERTIFICATION:
                $certification = XsCertificationSign::findOne($award['cid']);
                $str = '认证图标，ID：【' . $award['id'] . '_' . ($certification['name'] ?? '') . '】，天数：【' . $award['exp_days'] . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_TOP_CARD:
                $card = XsRoomTopCard::findOne($award['id']);
                $name = json_decode($card['name_json'], true)['cn'] ?? '';
                $str = '房间置顶卡，ID：【' . $award['id'] . '_' . $name . '】天数：【' . $award['exp_days'] . '】，份数【' . $award['num'] . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON:
                $str .= '游戏优惠券，ID：【' . $award['id'] . '】，数量：【' . $award['num'] . '】有效期：【' . ($award['exp_days'] <= 7 ? '本周失效' : '下周失效') . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING:
                $nameIdLighting = XsNameIdLightingGroup::findOne($award['id']);
                $name = XsNameIdLightingGroup::formatName(json_decode($nameIdLighting['name'] ?? [], true));
                $str = '炫彩资源，ID：【' . $award['id'] . '_' . $name . '】，天数：【' . $award['num'] . '】，资格有效使用：【' . $award['exp_days'] . '】，发放数量：【' . ($award['award_expand']['number'] ?? 0) . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD:
                $itemCard = XsItemCard::findOne($award['id']);
                $name = json_decode($itemCard['name_json'] ?? '', true)['zh_cn'] ?? '';
                $str = 'mini卡装扮，ID：【' . $award['id'] . '_' . $name . '】，天数：【' . $award['num'] . '】，资格有效使用：【' . $award['exp_days'] . '】，发放数量：【' . ($award['award_expand']['number'] ?? 0) . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_PROP_CARD:
                $propCard = XsPropCard::findOne($award['id']);
                $propCardConfig = XsPropCardConfig::findOne($propCard['prop_card_config_id']);
                $name = @json_decode($propCardConfig['name_json'] ?? '', true)['cn'] ?? '';
                $str = 'pk道具卡，ID：【' . $award['id'] . '_' . $name . '】， 数量：【' . $award['num'] . '】，有效小时【' . ($award['exp_days'] ?? 0) . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_OPEN_SCREEN_CARD:
                $str = '开屏卡，数量：【' . $award['num'] . '】，是否可赠送【' . (($award['award_expand']['act_extend_type'] ?? 0) ? '是' : '否') . '】，有效小时：【' . ($award['days'] ?? 0) . '】，过期时间【' . ($award['exp_days'] ? Helper::now($award['exp_days']) : '') . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD:
                $itemCard = XsItemCard::findOne($award['id']);
                $name = json_decode($itemCard['name_json'] ?? '', true)['zh_cn'] ?? '';
                $str = '个人主页装扮卡装扮，ID：【' . $award['id'] . '_' . $name . '】，天数：【' . $award['num'] . '】，资格有效使用：【' . $award['exp_days'] . '】，发放数量：【' . ($award['award_expand']['number'] ?? 0) . '】';
                break;
            case XsTopUpActivityReward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD:
                $str = '定制表情卡，数量：【' . $award['num'] . '】，有效天数：【' . $award['days'] . '】，过期时间【' . ($award['exp_days'] ? Helper::now($award['exp_days']) : '') . '】，是否可赠送：【' . (($award['award_expand']['act_extend_type'] ?? 0) ? '是' : '否') . '】';
                break;


        }

        return $str;
    }

    public function rechargeConfigExport($id): array
    {
        $active = XsTopUpActivity::findOne($id);
        $awardList = XsTopUpActivityReward::getListByWhere([
            ['top_up_activity_id', '=', $id]
        ]);
        $startTime = $active['start_time'] - $this->getTimeOffsetNew($active['time_offset']);
        $endTime = $active['end_time'] - $this->getTimeOffsetNew($active['time_offset']);
        $timeOffset = $this->setTimeOffsetNew($active['time_offset']);

        $data = [
            ['活动模版', '充值活动模版'],
            ['活动大区:', XsBigarea::AREA_MAP[$active['bigarea_id']] ?? ''],
            ['活动标题:', $active['title']],
            ['活动语言:', $active['language']],
            ['活动时区:', 'UTC: ' . ($timeOffset > 0 ? "+{$timeOffset}" : $timeOffset)],
            ['发奖方式:', XsTopUpActivity::$awardTypeMap[$active['award_type']] ?? ''],
            ['活动循环周期:', XsTopUpActivity::$cycleTypeMap[$active['cycle_type']] ?? ''],
            ['活动时间:', Helper::now($startTime) . '～' . Helper::now($endTime)],
            ['充值渠道:', implode("，", $this->formatRechargeChannels($active['recharge_channels']))],
            ['引言', $active['introduction']],
        ];
        if ($awardList) {
            foreach ($awardList as $key => $award) {
                $awardArr = [];
                $awardConfig = json_decode($award['award_list'], true);
                foreach ($awardConfig as $v) {
                    $awardArr[] = $this->formatRechargeAwardContext($v);
                }
                $data[] = [
                    $key == 0 ? '奖励配置' : '',
                    '钻石门槛：' . $award['level'],
                    implode("\n", $awardArr)
                ];
            }
        }

        return $data;
    }

    // todo 导出使用Excel组件
    public function getRechargeActiveCsv(int $actId): array
    {
        try {
            $file = PUBLIC_DIR . DS . '系统自动导出的配置明细.xlsx';
            $data = $this->rechargeConfigExport($actId);
            $headings = ['活动模版', '充值活动', ''];
            $sheet1 = Excel::createSheet($data, '充值活动')->headings($headings);
            Excel::export([$sheet1])->store($file);
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }

        return [true, $file];
    }

    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $fields = $this->getFields();
        $list = BbcTemplateConfig::getList($conditions, implode(',', $fields), $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $list['data'] = $this->onAfterList($list['data']);

        return $list;
    }

    public function copy(int $id, bool $isPk = false)
    {
        $template = BbcTemplateConfig::findOne($id);
        $now = time();
        $aminId = Helper::getSystemUid();
        if (empty($template)) {
            return [false, '当前活动配置不存在'];
        }
        $template['status'] = BbcTemplateConfig::STATUS_NOT_RELEASE;
        $template['publisher_id'] = 0;
        $template['admin_id'] = $aminId;
        $template['dateline'] = $now;
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            unset($template['id']);
            list($tleRes, $tleId) = BbcTemplateConfig::add($template);
            if (!$tleRes) {
                return [false, '活动配置复制失败，失败原因：' . $tleId];
            }
            if (in_array($template['type'], [BbcTemplateConfig::TYPE_ONE_PK, BbcTemplateConfig::TYPE_WHEEL_LOTTERY, BbcTemplateConfig::TYPE_TASK, BbcTemplateConfig::TYPE_MULTI_TASK])) {
                $service = ActivityOnePkPlayService::class;
                switch ($template['type']) {
                    case BbcTemplateConfig::TYPE_WHEEL_LOTTERY:
                        $service = ActivityLuckGamePlayService::class;
                        break;
                    case BbcTemplateConfig::TYPE_TASK:
                        $service = ActivityTaskGamePlayService::class;
                        break;
                    case BbcTemplateConfig::TYPE_MULTI_TASK:
                        $service = ActivityTaskGamePlayMultiwireService::class;
                        break;
                }
                // 更新活动链接
                $prefix = ENV == 'prod' ? self::PROD_URL : self::DEV_URL;
                $update = [
                    'page_url' => $this->getPageUrl($tleId, $template['vision_type'], ''),
                ];
                $template['type'] == BbcTemplateConfig::TYPE_ONE_PK && $update['desc_path'] = sprintf($service::DESC_PATH, $prefix, $tleId, $template['language']);
                list($res, $msg) = BbcTemplateConfig::edit($tleId, $update);
                if (!$res) {
                    return [false, '活动配置链接修改失败，失败原因：' . $msg];
                }
            }
            $buttonTags = BbcRankButtonTag::getListByWhere([['act_id', '=', $id]]);
            // 1v1活动pk时tag默认给一条数据用于复制
            if ($isPk) {
                $buttonTags[0] = ['id' => 0];
            }
            $tagId = 0;
            foreach ($buttonTags as &$tag) {
                $tag['act_id'] = $tleId;
                $tag['admin_id'] = $aminId;
                $tag['dateline'] = $now;
                $buttonTagId = $tag['id'];
                unset($tag['id']);
                if (!$isPk) {
                    list($tagRes, $tagId) = BbcRankButtonTag::add($tag);
                    if (!$tagRes) {
                        return [false, 'buttonTag配置复制失败，失败原因：' . $tagId];
                    }
                }
                $buttonLists = BbcRankButtonList::getListByWhere([
                    ['act_id', '=', $id],
                    ['button_tag_id', '=', $buttonTagId]
                ]);
                foreach ($buttonLists as &$list) {
                    $list['act_id'] = $tleId;
                    $list['button_tag_id'] = $tagId;
                    $list['admin_id'] = $aminId;
                    $list['dateline'] = $now;
                    // 任务玩法默认cp性别为全部
                    if ($template['type'] == BbcTemplateConfig::TYPE_TASK) {
                        $list['cp_gender'] = BbcRankButtonList::CP_GENDER_ALL;
                    }
                    $buttonListId = $list['id'];
                    unset($list['id']);
                    list($listRes, $listId) = BbcRankButtonList::add($list);
                    if (!$listRes) {
                        return [false, 'buttonList配置复制失败，失败原因：' . $listId];
                    }
                    $awardList = BbcRankAward::getListByWhere([
                        ['act_id', '=', $id],
                        ['button_list_id', '=', $buttonListId],
                    ]);
                    if ($awardList) {
                        foreach ($awardList as &$award) {
                            unset($award['id']);
                            $award['admin_id'] = $aminId;
                            $award['dateline'] = $now;
                            $award['act_id'] = $tleId;
                            $award['button_list_id'] = $listId;
                        }
                        list($awardRes, $awardMsg, $_) = BbcRankAward::addBatch($awardList);
                        if (!$awardRes) {
                            return [false, 'buttonList奖励复制失败，失败原因：' . $awardMsg];
                        }
                    }
                    $scoreList = BbcRankScoreConfig::getListByWhere([
                        ['act_id', '=', $id],
                        ['button_list_id', '=', $buttonListId],
                    ]);
                    if ($scoreList) {
                        foreach ($scoreList as &$score) {
                            unset($score['id']);
                            $score['admin_id'] = $aminId;
                            $score['dateline'] = $now;
                            $score['act_id'] = $tleId;
                            $score['button_list_id'] = $listId;
                        }
                        list($scoreRes, $scoreMsg, $_) = BbcRankScoreConfig::addBatch($scoreList);
                        if (!$scoreRes) {
                            return [false, 'buttonList积分复制失败，失败原因：' . $scoreMsg];
                        }
                    }
                    $newScoreList = BbcRankScoreConfigNew::getListByWhere([
                        ['act_id', '=', $id],
                        ['list_id', '=', $buttonListId],
                    ], '*', 'id asc');
                    if ($newScoreList) {
                        foreach ($newScoreList as &$newScore) {
                            unset($newScore['id']);
                            $newScore['admin_id'] = $aminId;
                            $newScore['dateline'] = $now;
                            $newScore['act_id'] = $tleId;
                            $newScore['list_id'] = $listId;
                        }
                        list($scoreRes, $scoreMsg, $_) = BbcRankScoreConfigNew::addBatch($newScoreList);
                        if (!$scoreRes) {
                            return [false, 'buttonList积分复制失败，失败原因：' . $scoreMsg];
                        }
                    }
                    $newReward = BbcActWheelLotteryReward::findOneByWhere([
                        ['act_id', '=', $id],
                        ['list_id', '=', $buttonListId],
                    ]);
                    if ($newReward) {
                        unset($newReward['id']);
                        $newReward['act_id'] = $tleId;
                        $newReward['list_id'] = $listId;
                        $newReward['dateline'] = $now;
                        list($rewardRes, $rewardMsg) = BbcActWheelLotteryReward::add($newReward);
                        if (!$rewardRes) {
                            return [false, 'buttonList奖励复制失败，失败原因：' . $rewardMsg];
                        }
                    }
                    $pkObjList = BbcOnepkObject::getListByWhere([
                        ['act_id', '=', $id],
                        ['button_list_id', '=', $buttonListId],
                    ]);
                    if ($pkObjList) {
                        foreach ($pkObjList as &$obj) {
                            unset($obj['id']);
                            $obj['act_id'] = $tleId;
                            $obj['button_list_id'] = $listId;
                        }
                        list($scoreRes, $scoreMsg, $_) = BbcOnepkObject::addBatch($pkObjList);
                        if (!$scoreRes) {
                            return [false, 'buttonListPk对象复制失败，失败原因：' . $scoreMsg];
                        }
                    }
                }
            }
            $conn->commit();
            return [true, $tleId];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage() ?? '复制失败'];
        }
    }

    /**
     * 删除活动
     * @param int $id
     * @return array
     * @throws ApiException
     */
    public function delete(int $id): array
    {
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            $res = BbcTemplateConfig::deleteById($id);
            if (!$res) {
                return [false, '活动配置删除失败'];
            }
            $conditions = [['act_id', '=', $id]];
            // 同步删除其他活动配置
            BbcRankButtonTag::deleteByWhere($conditions);
            BbcRankButtonList::deleteByWhere($conditions);
            BbcRankScoreConfig::deleteByWhere($conditions);
            BbcOnepkObject::deleteByWhere($conditions);
            BbcRankAward::deleteByWhere($conditions);
            BbcActWheelLotteryReward::deleteByWhere($conditions);
            BbcRankScoreConfigNew::deleteByWhere($conditions);
            $conn->commit();
            return [true, ''];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage() ?? '删除失败'];
        }
    }

    /**
     * 发布活动
     * @param array $params
     * @param bool $isDiamond
     * @return array
     * @throws ApiException
     */
    public function publish(array $params, bool $isDiamond = false): array
    {
        $id = $params['id'] ?? 0;
        $info = BbcTemplateConfig::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '发布失败, 当前活动不存在');
        }

        if (in_array($info['type'], [BbcTemplateConfig::TYPE_TASK, BbcTemplateConfig::TYPE_MULTI_TASK]) && $info['has_be_related'] == BbcTemplateConfig::HAS_RELATE_YES) {
            throw new ApiException(ApiException::MSG_ERROR, '发布失败, 关联活动不能发布');
        }

        $now = time();
        $data = [
            'desc_path'    => $params['desc_path'] ?? '',
            'publisher_id' => $params['admin_id'], // 记录发布人id
            'updated_at'   => $now,
        ];

        // 存在钻石奖励时需要云之家审核
        // dev 直接跳过审批流程
        if ($isDiamond) {
            // 新增发布中状态
            $data['status'] = BbcTemplateConfig::STATUS_PUBLISH_HAVE;
            // 记录发布人
            list($res, $msg) = BbcTemplateConfig::edit($id, $data);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, $msg || '发布人更新失败');
            }

            self::updateRelateActivityStatus($info['relate_id'], BbcTemplateConfig::STATUS_PUBLISH_HAVE);

            $type = XsstActiveKingdeeRecord::$typeMap[$info['type']];
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
            // 延时推动，用来检测OA是否发起成功
            NsqClient::publish(NsqConstant::TOPIC_KING_ACTIVITY, [
                'cmd'  => 'check_status',
                'data' => ['id' => $id, 'type' => $type],
            ], 300);

            return [];
        }

        $data['status'] = BbcTemplateConfig::STATUS_RELEASE;

        list($res, $msg) = BbcTemplateConfig::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg || '发布失败');
        }

        // 关联玩法同时修改状态
        self::updateRelateActivityStatus($info['relate_id'], BbcTemplateConfig::STATUS_RELEASE);

        if ($info['type'] == BbcTemplateConfig::TYPE_ONE_PK) {
            XsstOnepkObjectLog::addOnepkRecord($id, XsstOnepkObjectLog::TYPE_PUB);
        }

        return ['id' => $id, 'after_json' => ['status' => $data['status']], 'before_json' => ['status' => $info['status']]];
    }

    public function getStatusMap($type)
    {
        $statusMap = BbcTemplateConfig::$statusMap;
        $map = [];
        if ($type == self::ONE_PK) {
            unset($statusMap[BbcTemplateConfig::STATUS_PUBLISH_HAVE]);
            unset($statusMap[BbcTemplateConfig::STATUS_PUBLISH_ERROR]);
        }
        foreach ($statusMap as $key => $value) {
            $map[] = [
                'label' => $value,
                'value' => $key + 1
            ];
        }

        return $map;
    }

    public function getAuditStatusMap($type)
    {
        $map = BbcTemplateConfig::$auditStatusMap;
        if ($type != self::WHEEL_LOTTERY) {
            unset($map[BbcTemplateConfig::STATUS_REPLENISH_STOCK]);
        }
        return StatusService::formatMap($map);
    }

    /**
     * 根据时间判断status
     * @param int $status
     * @param int $startTime
     * @param int $endTime
     * @return string
     */
    protected function getStatus(int $status, int $startTime, int $endTime): string
    {
        if (in_array($status, [BbcTemplateConfig::STATUS_NOT_RELEASE, BbcTemplateConfig::STATUS_PUBLISH_HAVE, BbcTemplateConfig::STATUS_PUBLISH_ERROR])) {
            return $status;
        }
        $value = -1;
        $time = time();
        if ($startTime >= $time) {
            // 开始时间大于当前时间为待开始状态
            $value = BbcTemplateConfig::STATUS_WAIT_START;
        } else if ($startTime <= $time && $endTime >= $time) {
            // 开始时间小于当前时间并且结束时间大于当前时间状态为进行中
            $value = BbcTemplateConfig::STATUS_HAVE;
        } else if ($endTime < $time) {
            // 结束时间小于当前时间状态为已结束
            $value = BbcTemplateConfig::STATUS_END;
        }
        return strval($value);
    }

    /**
     * 获取审核状态
     * @param int $status
     * @return string
     */
    protected function getAuditStatus(int $status): string
    {
        return strval(in_array($status, array_keys(BbcTemplateConfig::$auditStatusMap)) ? $status : -1);
    }

    /**
     * 格式化数据为label,value格式
     * @param $data
     * @return array
     */
    protected function formatMap(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $map = [];
        foreach ($data as $item) {
            $map[] = [
                'label' => $item['id'] . '-' . $item['name'],
                'value' => (string)$item['id']
            ];
        }
        return $map;
    }

    protected function setStatusText(string $status): string
    {
        switch ($status) {
            case BbcTemplateConfig::STATUS_END:
                $color = 'grey';
                break;
            case BbcTemplateConfig::STATUS_HAVE:
                $color = 'green';
                break;
            default:
                $color = 'red';
                break;
        }
        $status = BbcTemplateConfig::$statusMap[$status];
        return "<font color='$color'>$status</font>";
    }

    protected function setAuditStatusText(string $status): string
    {
        $color = 'red';
        if ($status == BbcTemplateConfig::STATUS_RELEASE) {
            $color = 'green';
        }
        $status = BbcTemplateConfig::$auditStatusMap[$status] ?? '';
        return "<font color='$color'>$status</font>";
    }

    protected function getConditions(array $params): array
    {
        $title = $params['title'] ?? '';
        $type = $params['type'] ?? '';
        $hasBeRelated = $params['has_be_related'] ?? -1;
        $relateType = intval($params['relate_type'] ?? 0);
        if (!empty($type)) {
            $conditions = ['type = :type:'];
            $bind = ['type' => $type];
        } else {
            $conditions = ['type IN ({type:array})'];
            $bind = ['type' => $params['types']];
        }

        if ($hasBeRelated > -1) {
            $conditions[] = 'has_be_related = :has_be_related:';
            $bind['has_be_related'] = $hasBeRelated;
        }

        if ($relateType) {
            $conditions[] = 'relate_type = :relate_type:';
            $bind['relate_type'] = $relateType;
        }

        if (!empty($title)) {
            $conditions[] = "title like :title:";
            $bind['title'] = "%{$title}%";
        }

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = "FIND_IN_SET(:bigarea_id:, REPLACE(bigarea_id,'|',','))";
            $bind['bigarea_id'] = $params['bigarea_id'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $status = intval($params['status']) - 1;
            $time = time();
            // 状态为未发布
            if (in_array($status, [
                BbcTemplateConfig::STATUS_NOT_RELEASE, BbcTemplateConfig::STATUS_PUBLISH_HAVE, BbcTemplateConfig::STATUS_PUBLISH_ERROR,
            ])) {
                $conditions[] = 'status = :status:';
                $bind['status'] = $status;
            } else if ($status == BbcTemplateConfig::STATUS_WAIT_START) {
                $conditions[] = 'start_time > :start_time: and status NOT IN ({status:array})';
                $bind['start_time'] = $time;
                $bind['status'] = [
                    BbcTemplateConfig::STATUS_NOT_RELEASE,
                    BbcTemplateConfig::STATUS_PUBLISH_HAVE,
                    BbcTemplateConfig::STATUS_PUBLISH_ERROR,
                ];
            } else if ($status == BbcTemplateConfig::STATUS_HAVE) {
                $conditions[] = 'start_time < :start_time: AND end_time - data_period * 86400 >= :end_time: and status NOT IN ({status:array})';
                $bind['start_time'] = $time;
                $bind['end_time'] = $time;
                $bind['status'] = [
                    BbcTemplateConfig::STATUS_NOT_RELEASE,
                    BbcTemplateConfig::STATUS_PUBLISH_HAVE,
                    BbcTemplateConfig::STATUS_PUBLISH_ERROR,
                ];
            } else if ($status == BbcTemplateConfig::STATUS_END) {
                $conditions[] = 'end_time - data_period * 86400 < :end_time: and status NOT IN ({status:array})';
                $bind['end_time'] = $time;
                $bind['status'] = [
                    BbcTemplateConfig::STATUS_NOT_RELEASE,
                    BbcTemplateConfig::STATUS_PUBLISH_HAVE,
                    BbcTemplateConfig::STATUS_PUBLISH_ERROR,
                ];
            }
        }

        if (!empty($params['audit_status'])) {
            $conditions[] = 'status = :audit_status:';
            $bind['audit_status'] = $params['audit_status'];
        }

        if (isset($params['create_id']) && !empty($params['create_id'])) {
            $conditions[] = 'admin_id = :admin_id:';
            $bind['admin_id'] = $params['create_id'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = 'id = :id:';
            $bind['id'] = $params['id'];
        }

        return compact('conditions', 'bind');
    }

    public function check(array $params): array
    {
        $id = $params['id'] ?? 0;
        $info = BbcTemplateConfig::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }
        if ($this->setIsPublish($info['status'])) {
            throw new ApiException(ApiException::MSG_ERROR, '活动发布前状态必须为未发布|发布失败（请重试）｜已打回（需修改）');
        }
        $admin = Helper::getAdminName($info['admin_id']);

        return [
            'is_confirm'   => 1,
            'confirm_text' => "你确定发布【{$admin}】创建的活动【{$info['title']}】吗？"
        ];
    }

    public function pubAuthCheck(array $params): void
    {
//        if (ENV != 'dev') {
//            $user = CmsUser::findOne($params['admin_id']);
//            $userEmail = array_get($user, 'user_email', '');
//            $info = XsstTemplateAuditUser::checkUserName($userEmail);
//            if (!$info) {
//                throw new ApiException(ApiException::MSG_ERROR, '发布失败，请先向后台管理员申请“活动模板关联云之家”的权限。');
//            }
//        }
    }

    protected function verifyScoreSource(&$params): void
    {
        $scoreSourceData = [];
        $rankObject = $params['rank_object'] ?? 0;
        // 检验抽奖积分来源结构
        foreach ($params['score_source'] as $scoreSource) {
            $scope = $scoreSource['scope'] ?? [];
            $isOnlyCrossRoomPk = intval($scoreSource['is_only_cross_room_pk'] ?? -1);
            $sourceType = $scoreSource['source_type'];
            $sourceConfigList = $scoreSource['source_config'];
            $countSourceConfig = count($sourceConfigList);

            // 积分来源为幸运玩法模版，scope 为字符串 活动id_listid
            if ($sourceType == BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY && is_string($scope)) {
                [$actId, $listId] = explode('_', $scope);
                $scope = [];
            }

            if ($rankObject != BbcRankButtonTag::RANK_OBJECT_BROKER
                && in_array($params['type'], [BbcTemplateConfig::TYPE_TASK, BbcTemplateConfig::TYPE_EXCHANGE])
                && count($scope) == 1 && in_array($scope[0], [BbcRankScoreConfigNew::SCORE_SCOPE_CHAT, BbcRankScoreConfigNew::SCORE_SCOPE_LIVE])) {
                if ($isOnlyCrossRoomPk < 0) {
                    throw new ApiException(ApiException::MSG_ERROR, '积分来源为语音房或视频房时，是否只统计同房间PK不能为空');
                }
            } else {
                $isOnlyCrossRoomPk = 0;
            }

            if (in_array($sourceType, [BbcRankScoreConfigNew::SOURCE_TYPE_TOP_UP, BbcRankScoreConfigNew::SOURCE_TYPE_GAMES]) && $countSourceConfig != 1) {
                throw new ApiException(ApiException::MSG_ERROR, '积分来源为充值或游戏是，积分统计方式只能有一种');
            }
            // cp 不存在游戏积分类型，暂时不用校验
//            if ($params['rank_object'] != BbcRankButtonTag::RANK_OBJECT_PERSONAL && in_array(BbcRankScoreConfigNew::SCORE_SCOPE_GAME_FISHING, $scoreSource['scope'])) {
//                throw new ApiException(ApiException::MSG_ERROR, '统计范围包含Fishing时，任务对象必须是个人');
//            }
            foreach ($sourceConfigList as $sourceConfig) {
                $giftIds = $sourceConfig['gift_id'] ?? '';
                $type = $sourceConfig['type'] ?? 0;
                $score = $sourceConfig['score'] ?? 0;
                $pkValidType = $sourceConfig['pk_valid_type'] ?? -1;
                $pkTime = $sourceConfig['pk_time'] ?? 0;
                $pkGift = $sourceConfig['pk_gift'] ?? 0;
                
                if (empty($type) || empty($score)) {
                    throw new ApiException(ApiException::MSG_ERROR, '积分统计方式和分值必填');
                }

                // 验证score 必须为正整数
                if (!preg_match('/^[1-9]\d*$/', $score)) {
                    throw new ApiException(ApiException::MSG_ERROR, '分值必须为正整数');
                }

                // 当积分统计方式为 pk胜利场次或pk完成场次时，验证有效场次相关字段
                if (in_array($type, [BbcRankScoreConfigNew::SCORE_TYPE_PK_WIN, BbcRankScoreConfigNew::SCORE_TYPE_PK_END])) {
                    $pkValidType = intval($pkValidType);
                    if ($pkValidType < 0) {
                        throw new ApiException(ApiException::MSG_ERROR, 'pk相关积分统计方式下，有效场次要求必填');
                    }
                    
                    if (!in_array($pkValidType, array_keys(BbcRankScoreConfigNew::$pkValidTypeMap))) {
                        throw new ApiException(ApiException::MSG_ERROR, '有效场次要求选择错误');
                    }
                    
                    // 0-时长达标 2-时长且收礼达标 3-时长或收礼达标 需要验证时长
                    if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                        // 正则校验正整数且大于0
                        if (!preg_match('/^[1-9]\d*$/', $pkTime)) {
                            throw new ApiException(ApiException::MSG_ERROR, '当前有效场次要求下，单场pk时长必填且必须为正整数');
                        }
                    }
                    
                    // 1-收礼达标 2-时长且收礼达标 3-时长或收礼达标 需要验证收礼
                    if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                        if (!preg_match('/^[1-9]\d*$/', $pkGift)) {
                            throw new ApiException(ApiException::MSG_ERROR, '当前有效场次要求下，单场pk房间内收礼必填且必须为正整数');
                        }
                    }
                } else {
                    // 非pk相关积分统计方式，清空这些字段
                    $pkValidType = 0;
                    $pkTime = 0;
                    $pkGift = 0;
                }

                // 组装 pk_valid_extend JSON 字段
                $pkValidExtend = '';
                if (in_array($type, [BbcRankScoreConfigNew::SCORE_TYPE_PK_WIN, BbcRankScoreConfigNew::SCORE_TYPE_PK_END])) {
                    $pkValidExtendData = [
                        'pk_valid_type' => $pkValidType,
                    ];
                    if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                        $pkValidExtendData['pk_time'] = intval($pkTime);
                    }
                    if (in_array($pkValidType, [BbcRankScoreConfigNew::PK_VALID_TYPE_PK_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_AND_GIFT, BbcRankScoreConfigNew::PK_VALID_TYPE_PK_TIME_OR_GIFT])) {
                        $pkValidExtendData['pk_gift'] = intval($pkGift);
                    }
                    $pkValidExtend = json_encode($pkValidExtendData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                
                // 初始化下score表数据
                $tmp = [
                    'type'                  => $type,
                    'score'                 => $score,
                    'gift_id'               => 0,
                    'scope'                 => $scope ? implode(',', $scope) : '',
                    'is_only_cross_room_pk' => $isOnlyCrossRoomPk,
                    'extend_id'             => $actId ?? 0,
                    'extend_sub_id'         => $listId ?? 0,
                    'pk_valid_extend'       => $pkValidExtend,
                ];

                // 积分统计方式为 送出指定礼物钻石数｜送出指定礼物个数｜收到指定礼物钻石数｜收到指定礼物个数 礼物id必填
                if (in_array($type, BbcRankScoreConfigNew::$giftScoreTypeMap)) {
                    if (empty($giftIds)) {
                        throw new ApiException(ApiException::MSG_ERROR, '当前积分统计方式下礼物id必填');
                    }
                    $giftIdArr = explode("\n", $giftIds);
                    if (count($giftIdArr) > 40) {
                        throw new ApiException(ApiException::MSG_ERROR, '礼物id最多40个');
                    }
                    $diff = XsGift::existsGiftIds($giftIdArr);
                    if ($diff) {
                        throw new ApiException(ApiException::MSG_ERROR, '礼物id不存在：' . implode(',', $diff));
                    }
                    // 可以填写礼物id时，每个礼物id为一条记录
                    foreach ($giftIdArr as $gid) {
                        $tmp['gift_id'] = $gid;
                        $scoreSourceData[] = $tmp;
                    }
                } else {
                    $scoreSourceData[] = $tmp;
                }
            }
        }

        $params['score_source'] = $scoreSourceData;
    }

    protected function validScoreGift($giftIds): void
    {
        $giftIdArr = explode("\n", $giftIds);
        if (count($giftIdArr) > 20) {
            throw new ApiException(ApiException::MSG_ERROR, '礼物id最多20个');
        }
        $diff = XsGift::existsGiftIds($giftIdArr);
        if ($diff) {
            throw new ApiException(ApiException::MSG_ERROR, '礼物id不存在：' . implode(',', $diff));
        }
    }

    protected function formatScoreConfig(int $actId, int $buttonListId): array
    {
        $scoreList = BbcRankScoreConfigNew::getListByWhere([
            ['act_id', '=', $actId],
            ['list_id', '=', $buttonListId]
        ], '*', 'id asc');

        $result = [];
        foreach ($scoreList as &$item) {
            $found = false;
            $scopeType = $this->getScoreType($item['scope'], $item['type']);
            // 幸运玩法模版需要特殊处理一下
            if ($scopeType == BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY) {
                $item['scope'] = $item['extend_id'] . '_' . $item['extend_sub_id'];
            }
            $isOnlyCrossRoomPk = (string)$item['is_only_cross_room_pk'];
            
            // 解析 pk_valid_extend JSON 字段
            $pkValidExtend = @json_decode($item['pk_valid_extend'] ?? '', true);
            $item['pk_valid_type'] = isset($pkValidExtend['pk_valid_type']) ? (string)$pkValidExtend['pk_valid_type'] : null;
            $item['pk_time'] = $pkValidExtend['pk_time'] ?? null;
            $item['pk_gift'] = $pkValidExtend['pk_gift'] ?? null;

            // 检查是否已存在相同的source_type和scope组合
            foreach ($result as $rowIndex => $row) {
                if ($row['source_type'] == $scopeType && $item['scope'] == $row['scope'] && $isOnlyCrossRoomPk == $row['is_only_cross_room_pk'] && isset($row['pk_valid_type']) && $item['pk_valid_type'] == $row['pk_valid_type']) {
                    // 将type和score添加到对应的配置项中
                    $configFound = false;
                    foreach ($result[$rowIndex]['source_config'] as $configIndex => $config) {
                        if ($config['type'] == $item['type'] && $config['score'] == $item['score'] && $isOnlyCrossRoomPk == $config['is_only_cross_room_pk'] && isset($config['pk_valid_type']) && $item['pk_valid_type'] == $config['pk_valid_type']) {
                            $configFound = true;
                            // 将gift_id添加到该配置对应的数组中
                            if (!in_array($item['gift_id'], $result[$rowIndex]['source_config'][$configIndex]['gift_id'])) {
                                $result[$rowIndex]['source_config'][$configIndex]['gift_id'][] = $item['gift_id'];
                            }
                            break;
                        }
                    }
                    // 如果该类型配置未找到，则创建新的配置项
                    if (!$configFound) {
                        $result[$rowIndex]['source_config'][] = [
                            'type'                  => $item['type'],
                            'score'                 => $item['score'],
                            'gift_id'               => [$item['gift_id']],
                            'is_only_cross_room_pk' => $isOnlyCrossRoomPk,
                            'pk_valid_type'         => $item['pk_valid_type'],
                            'pk_time'               => $item['pk_time'],
                            'pk_gift'               => $item['pk_gift'],
                        ];
                    }
                    $found = true;
                    break;
                }
            }
            // 如果不存在相同的组合，则创建新的数据行
            if (!$found) {
                $result[] = [
                    'source_type'           => $scopeType,
                    'scope'                 => $item['scope'],
                    'is_only_cross_room_pk' => $isOnlyCrossRoomPk,
                    'source_config'         => [
                        [
                            'type'                  => $item['type'],
                            'score'                 => $item['score'],
                            'gift_id'               => [$item['gift_id']],
                            'is_only_cross_room_pk' => $isOnlyCrossRoomPk,
                            'pk_valid_type'         => $item['pk_valid_type'],
                            'pk_time'               => $item['pk_time'],
                            'pk_gift'               => $item['pk_gift'],
                        ]
                    ]
                ];
            }
        }

        // 处理礼物id和scope格式
        foreach ($result as $itemIndex => $item) {
            if ($item['source_type'] != BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY) {
                $result[$itemIndex]['scope'] = array_map(function ($scopeItem) {
                    return (int)$scopeItem;
                }, explode(',', $item['scope']));
            }

            foreach ($result[$itemIndex]['source_config'] as $configIndex => $val) {
                if (!empty($val['gift_id']) && count(array_filter($val['gift_id']))) {
                    $result[$itemIndex]['source_config'][$configIndex]['gift_id'] = implode("\n", $val['gift_id']);
                } else {
                    $result[$itemIndex]['source_config'][$configIndex]['gift_id'] = '';
                }
            }
        }

        return $result;
    }

    protected function getScoreType(string $scope, int $type): int
    {
        if (in_array($type, [BbcRankScoreConfigNew::SCORE_TYPE_SIGN_IN, BbcRankScoreConfigNew::SCORE_TYPE_ROOM_STAY_TIME, BbcRankScoreConfigNew::SCORE_TYPE_ROOM_COMMENT_NUM])) {
            return BbcRankScoreConfigNew::SOURCE_TYPE_ACTIVE;
        }

        if ($type == BbcRankScoreConfigNew::SCORE_TYPE_WHEEL_NUM) {
            return BbcRankScoreConfigNew::SOURCE_TYPE_WHEEL_LOTTERY;
        }
        // 取首个统计范围值即可
        $scoreArr = explode(',', $scope);
        $scope = $scoreArr[0];
        return BbcRankScoreConfigNew::$scopeAndSourceTypeMap[$scope] ?? 0;
    }

    protected function handleScoreConfig($config, $baseData): array
    {
        $listId = $baseData['button_list_id'] ?? $baseData['list_ids'][1];
        unset($baseData['list_ids'], $baseData['button_list_id']);
        $maxId = BbcRankScoreConfigNew::getMaxId();
        $exists = BbcRankScoreConfigNew::findOneByWhere([['act_id', '=', $baseData['act_id']]]);
        if ($exists) {
            [$scoreRes, $scoreMsg, $_] = BbcRankScoreConfigNew::deleteByWhere([['act_id', '=', $baseData['act_id']]]);
            if (!$scoreRes) {
                return [false, '积分统计方式数据删除失败，失败原因：' . $scoreMsg];
            }
        }
        foreach ($config as &$scoreItem) {
            $scoreItem = array_merge($scoreItem, $baseData);
            $scoreItem['list_id'] = $listId;

            // 数据顺序受影响，直接后台生成id
            $maxId += 1;
            $scoreItem['id'] = $maxId;
        }
        LoggerProxy::instance()->info("scoreData:" . json_encode($config));
        [$scoreRes, $scoreMsg, $_] = BbcRankScoreConfigNew::addBatch($config);

        if (!$scoreRes) {
            return [false, '积分统计方式数据添加失败，失败原因：' . $scoreMsg];
        }

        return [true, ''];
    }

    protected function setTimeOffset($timeOffset, $type = self::TYPE_TIME_ADD)
    {
        if ($type == self::TYPE_TIME_SUBTRACT) {
            return $timeOffset / 10;
        }
        return $timeOffset * 10;
    }

    protected function setActivityTime($time, $timeOffset, $dataPeriod = 0, $type = self::TYPE_TIME_ADD)
    {
        if ($type == self::TYPE_TIME_SUBTRACT) {
            return $time - (8 - $timeOffset) * 3600 - $dataPeriod * 86400;
        }
        return strtotime($time) + (8 - $timeOffset) * 3600 + $dataPeriod * 86400;
    }

    protected function formatTimeOffset($timeOffset): string
    {
        return 'UTC :' . ($timeOffset >= 0 ? '+' : '') . $timeOffset;
    }

    protected function getPageUrl(int $id, int $type, string $pageUrl): string
    {
        if ($pageUrl) {
            return $pageUrl;
        }

        return (new static())->setPageUrl($type, $id);
    }

    protected function isDiamondAward(int $actId): int
    {
        $isDiamond = 0;
        $award = BbcRankAward::findOneByWhere([
            ['act_id', '=', $actId],
            ['award_type', 'IN', [BbcRankAward::AWARD_TYPE_DIAMOND, BbcRankAward::AWARD_TYPE_GAME_COUPON]]
        ]);

        if ($award) {
            $isDiamond = 1;
        }

        return $isDiamond;
    }

    protected function getCycleTime(int $tagListType, int $cycle, int $startTime): string
    {
        if ($tagListType == BbcRankButtonTag::TAG_LIST_TYPE_ONE) {
            return '/';
        }

        return date('Y-m-d', $startTime + ($cycle - 1) * 86400);
    }

    public function getHasRelateMap(): array
    {
        return StatusService::formatMap(BbcTemplateConfig::$hasRelateMap);
    }

    /**
     * 验证活动发布状态
     * @param int $status
     * @return bool
     */
    public static function validActivityPublishStatus(int $status): bool
    {
        return in_array($status, [BbcTemplateConfig::STATUS_PUBLISH_HAVE, BbcTemplateConfig::STATUS_PUBLISH_ERROR]);
    }

    /**
     * 设置活动发布状态
     * @param int $status
     * @return int
     */
    public function setIsPublish(int $status): int
    {
        return in_array($status, [BbcTemplateConfig::STATUS_NOT_RELEASE, BbcTemplateConfig::STATUS_DISMISS, BbcTemplateConfig::STATUS_PUBLISH_ERROR]) ? 0 : $status;
    }

    /**
     * 获取有效小时数map
     * @return array
     */
    public static function getEffectiveHoursMap(): array
    {
        return [
            ['label' => '6小时', 'value' => 6],
            ['label' => '12小时', 'value' => 12],
            ['label' => '24小时', 'value' => 24],
            ['label' => '36小时', 'value' => 36],
        ];
    }

    /**
     * 获取生效天数map
     * @return array
     */
    public static function getEffectiveDaysMap(): array
    {
        return [
            ['label' => '1天', 'value' => 1],
            ['label' => '3天', 'value' => 3],
            ['label' => '7天', 'value' => 7],
            ['label' => '15天', 'value' => 15],
            ['label' => '30天', 'value' => 30],
        ];
    }

    /**
     * 获取关联活动ID和名称
     * @param int $relateType
     * @return array
     */
    public function getRelateIdMap(int $relateType): array
    {
        $type = BbcTemplateConfig::$actTemplateTypeAndTypeMap[$relateType] ?? '';
        if (empty($type)) {
            return [];
        }

        $activityList = BbcTemplateConfig::getListByWhere([
            ['type', '=', $type],
            ['has_be_related', '=', BbcTemplateConfig::HAS_RELATE_YES]
        ], 'id,title', 'id desc');

        if (empty($activityList)) {
            return [];
        }

        $map = [];
        foreach ($activityList as $item) {
            $map[] = [
                'label' => $item['id'] . '-' . $item['title'],
                'value' => $item['id']
            ];
        }
        return $map;
    }

    /**
     * 获取幸运玩法下相关等级玩法选项
     * @return array
     */
    public function getWheelLotteryScoreScopeMap(): array
    {
        // 获取所有幸运礼物玩法活动模版
        $templateList = BbcTemplateConfig::getListByWhere([
            ['type', '=', BbcTemplateConfig::TYPE_WHEEL_LOTTERY]
        ], 'id, title');

        $tidArr = array_column($templateList, 'id');
        $templateMap = array_column($templateList, 'title', 'id');
        $buttonList = BbcRankButtonList::getListByWhere([
            ['act_id', 'IN', $tidArr]
        ], 'id, level, act_id', 'act_id desc, level asc');

        $map = [];

        foreach ($buttonList as $item) {
            $label = sprintf('%d-%s-%s玩法', $item['act_id'], $templateMap[$item['act_id']] ?? '', BbcRankButtonList::$wheelLotteryLevelMap[$item['level']] ?? '');
            $map[] = [
                'label' => $label,
                'value' => $item['act_id'] . '_' . $item['id']
            ];
        }

        return $map;
    }

    /**
     * 更新关联活动状态
     * @param int $relateId
     * @param int $status
     * @return void
     */
    public static function updateRelateActivityStatus(int $relateId, int $status)
    {
        if (empty($relateId)) {
            return;
        }
        $tids = [$relateId];
        $relateActive = BbcTemplateConfig::useMaster()::findOne($relateId);
        if ($relateActive && $relateActive['relate_id']) {
            $tids[] = $relateActive['relate_id'];
        }
        BbcTemplateConfig::updateByWhere([
            ['id', 'IN', $tids]
        ], [
            'status' => $status,
        ]);
    }

    // 验证活动状态是否可修改非h5信息
    public function validActiveStatus(int $status): bool
    {
        if (in_array($status, [BbcTemplateConfig::STATUS_NOT_RELEASE])) {
            return false;
        }
        return true;
    }

    // 验证发布按钮是否允许展示，未发布 | 审核中 | 已打回(需修改) | 发布失败(请重试)
    public function validAuditStatus(int $status): bool
    {
        return in_array($status, [BbcTemplateConfig::STATUS_NOT_RELEASE, BbcTemplateConfig::STATUS_DISMISS, BbcTemplateConfig::STATUS_PUBLISH_ERROR]);
    }

    public function getMinStartTimeAndEndTime($id, $startTime, $endTime): array
    {
        $list = BbcRankButtonList::useMaster()::findFirst([
            "conditions" => "act_id = :act_id: and start_time <> 0",
            "bind"       => [
                'act_id' => $id,
            ],
            'columns'    => 'min(start_time) as start_time, max(end_time) as end_time'
        ])->toArray();

        if ($list['start_time'] < $startTime || $startTime == 0) {
            $startTime = $list['start_time'];
        }
        $endTime = max($list['end_time'], $endTime);

        return [$startTime, $endTime];
    }

    /**
     * 更新活动数据
     *
     * @param int $id
     * @param array $data
     * @return void
     * @throws \Exception
     */
    public function updateActivityInfo(int $id, array $data = []): void
    {
        $activity = BbcTemplateConfig::findOne($id);
        if (!$activity) {
            throw new \Exception('活动不存在');
        }

        if ($this->validActiveStatus($activity['status'])) {
            return;
        }

        if ($data) {
            // 获取最小开始时间和最大结束时间
            [$startTime, $endTime] = $this->getMinStartTimeAndEndTime($id, $data['start_time'], $data['end_time']);
            $data['start_time'] = $startTime;
            // 活动结束时间要加上页面保留数据时间 data_period（单位：天）
            $data['end_time'] = $endTime + $activity['data_period'] * 86400;
        }

        $update = [
            'updated_at' => time()
        ];
        [$res, $msg] = BbcTemplateConfig::edit($id, array_merge($update, $data));
        if (!$res) {
            throw new \Exception('更新活动信息失败，失败原因：' . $msg);
        }
    }

    public function getAddScoreButtonList(int $id): array
    {
        // 获取活动下的所有buttonlist，按tag_id和buttonlist_id排序
        $buttonLists = BbcRankButtonList::getListByWhere([
            ['act_id', '=', $id]
        ], 'id,button_tag_id,level,start_time,end_time,cycle_days,cycle_limit', 'button_tag_id asc, id asc');
        if (empty($buttonLists)) {
            return [];
        }

        // 获取活动的时区信息
        $config = BbcTemplateConfig::findOne($id);
        $timeOffset = 0;
        if ($config) {
            $timeOffset = $this->getTimeOffsetNew($config['time_offset']);
        }

        // 获取所有button_tag信息
        $tagIds = array_column($buttonLists, 'button_tag_id');
        $buttonTags = BbcRankButtonTag::getBatchCommon($tagIds, ['id', 'rank_object', 'tag_list_type']);

        $result = [];
        foreach ($buttonLists as $buttonList) {
            $tag = $buttonTags[$buttonList['button_tag_id']] ?? [];
            if (empty($tag)) {
                continue;
            }

            // 根据Tag类型生成前端可直接使用的数据
            $items = self::generateFrontendButtonListData($tag, $buttonList, $timeOffset);
            $result = array_merge($result, $items);
        }

        return $result;
    }

    /**
     * 根据Tag类型生成前端可直接使用的数据
     * @param array $tag Button Tag信息
     * @param array $buttonList ButtonList信息
     * @param int $timeOffset 时区偏移量（秒）
     */
    private static function generateFrontendButtonListData(array $tag, array $buttonList, int $timeOffset = 0): array
    {
        // 获取面向对象名称
        $rankObjectName = BbcRankButtonTag::$rankObjectMap[$tag['rank_object']] ?? '未知';

        // 获取Tag类型名称
        $tagTypeName = BbcRankButtonTag::$tagListType[$tag['tag_list_type']] ?? '未知';

        // 获取当前时间戳，并去除时区偏移量，得到活动时区的当前时间
        $currentTimestamp = time() - $timeOffset;

        $result = [];

        switch ($tag['tag_list_type']) {
            case BbcRankButtonTag::TAG_LIST_TYPE_TOTAL:
            case BbcRankButtonTag::TAG_LIST_TYPE_UPGRADE:
                // 总榜和晋级榜：只生成一条数据
                $label = '按钮顺序' . $buttonList['level'];
                $displayText = $rankObjectName . '-' . $tagTypeName . '-' . $label;
                $value = $buttonList['id'] . '_0';

                $result[$value] = 'TagId:' .$tag['id'] . '-' . $displayText;
                break;
            case BbcRankButtonTag::TAG_LIST_TYPE_DAY:
                // 日榜：根据开始和结束时间，每天生成一条数据，但不包含未来日期
                if ($buttonList['start_time'] > 0 && $buttonList['end_time'] > 0) {
                    // 去除时区偏移量，得到本地时间
                    $startTime = $buttonList['start_time'] - $timeOffset;
                    $endTime = $buttonList['end_time'] - $timeOffset;
                    $currentTime = $startTime;
                    $cycle = 1;
                    while ($currentTime < $endTime) {
                        // 只显示当前时间之前的日期（包含今天）
                        if ($currentTime <= $currentTimestamp) {
                            $dateStr = date('Y-m-d', $currentTime);
                            $label = '(' . $dateStr . ')';
                            $displayText = $rankObjectName . '-' . $tagTypeName . '-' . $label;
                            $value = $buttonList['id'] . '_' . $cycle;

                            $result[$value] = 'TagId:' .$tag['id'] . '-' .$displayText;
                        }

                        $currentTime += 86400; // 增加一天
                        $cycle++;
                    }
                }
                break;
            case BbcRankButtonTag::TAG_LIST_TYPE_CYCLE:
                // 周期榜：根据周期天数和周期次数生成多条数据，但不包含未来周期
                if ($buttonList['start_time'] > 0 && $buttonList['cycle_days'] > 0 && $buttonList['cycle_limit'] > 0) {
                    // 去除时区偏移量，得到本地时间
                    $startTime = $buttonList['start_time'] - $timeOffset;
                    $cycleDays = $buttonList['cycle_days'];
                    $cycleLimit = $buttonList['cycle_limit'];
                    for ($cycle = 1; $cycle <= $cycleLimit; $cycle++) {
                        // 计算每个周期的开始和结束时间
                        $cycleStartTime = $startTime + ($cycle - 1) * $cycleDays * 86400;
                        $cycleEndTime = $cycleStartTime + ($cycleDays - 1) * 86400; // 修正：减1天，确保周期正好是cycleDays天
                        // 只显示周期开始时间不在未来的周期
                        if ($cycleStartTime <= $currentTimestamp) {
                            $startDate = date('Y-m-d', $cycleStartTime);
                            $endDate = date('Y-m-d', $cycleEndTime);
                            $label = '(' . $startDate . '-' . $endDate . ')';
                            $displayText = $rankObjectName . '-' . $tagTypeName . '-' . $label;
                            $value = $buttonList['id'] . '_' . $cycle;
                            $result[$value] = 'TagId:' .$tag['id'] . '-' .$displayText;
                        }
                    }
                }
                break;

            default:
                // 默认情况：生成一条数据
                $label = '按钮顺序' . $buttonList['level'];
                $displayText = $rankObjectName . '-' . $tagTypeName . '-' . $label;
                $value = $buttonList['id'] . '_0';
                $result[$value] = 'TagId:' .$tag['id'] . '-' .$displayText;
                break;
        }

        return $result;
    }

    /**
     * 上传CSV分值数据
     * @param array $params
     * @return array [success, message, data]
     */
    public function uploadScore(array $params): array
    {
        set_time_limit(60);
        try {
            // 获取参数
            $listIdStr = $params['buttonlist_id'] ?? '';

            if (empty($listIdStr)) {
                return [false, '请选择榜单', []];
            }

            list($listId, $_) = explode('_', $listIdStr);
            if (empty($listId)) {
                return [false, '榜单不存在', []];
            }

            // 获取榜单信息
            $buttonList = BbcRankButtonList::findOne($listId);
            if (empty($buttonList)) {
                return [false, '榜单不存在', []];
            }

            // 获取button tag信息
            $buttonTag = BbcRankButtonTag::findOne($buttonList['button_tag_id']);
            if (empty($buttonTag)) {
                return [false, 'Button Tag不存在', []];
            }

            // 根据button面向对象确定表头规范
            [$requiredColumnName, $requiredColumns] = self::getRequiredColumnsByRankObject($buttonTag['rank_object']);
            if (empty($requiredColumns)) {
                return [false, '不支持的button面向对象', []];
            }

            $instance = new self();
            list($uploadResult, $uploadMsg, $csvData) = $instance->uploadCsv($requiredColumns, 50000);

            if (!$uploadResult) {
                return [false, $uploadMsg, []];
            }

            // 验证表头
            if (empty($csvData['data'])) {
                return [false, 'CSV文件内容为空', []];
            }

            $csvRows = $csvData['data'];

            // 验证数据内容
            list($validResult, $validMsg, $data) = self::validateScoreData($csvRows, $requiredColumns, $buttonTag['rank_object']);
            if (!$validResult) {
                return [false, $validMsg, []];
            }

            // 返回包含表头信息的数据
            $result = [
                'csv_data'     => $data,
                'columns_name' => $requiredColumnName,
                'columns'      => $requiredColumns,
                'rank_object'  => $buttonTag['rank_object'],
                'url'          => $csvData['name'],
            ];

            return [true, '', $result];
        } catch (\Exception $e) {
            return [false, '上传失败：' . $e->getMessage(), []];
        }
    }

    /**
     * 根据button面向对象获取所需的表头列
     * @param int $rankObject
     * @return array
     */
    private static function getRequiredColumnsByRankObject(int $rankObject): array
    {
        $columnMap = [
            BbcRankButtonTag::RANK_OBJECT_BROKER         => [['公会id', '公会成员uid', '分值'], ['bid1', 'uid1', 'score']],
            BbcRankButtonTag::RANK_OBJECT_PERSONAL       => [['uid', '分值'], ['uid1', 'score']],
            BbcRankButtonTag::RANK_OBJECT_ROOM           => [['房主uid', '贡献者uid', '分值'], ['uid1', 'uid2', 'score']],
            BbcRankButtonTag::RANK_OBJECT_FAMILY         => [['家族id', '家族成员uid', '分值'], ['fid1', 'uid1', 'score']],
            BbcRankButtonTag::RANK_OBJECT_CP             => [['uid1', 'uid2', '分值'], ['uid1', 'uid2', 'score']],
            BbcRankButtonTag::RANK_OBJECT_ANCHOR         => [['主播uid', '贡献者uid', '分值'], ['uid1', 'uid2', 'score']],
            BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS => [['uid', '分值'], ['uid1', 'score']],
        ];

        return $columnMap[$rankObject] ?? [[], []];
    }

    /**
     * 验证分值数据
     * @param array $csvRows
     * @param array $columns
     * @param int $rankObject
     * @return array [success, message]
     */
    private static function validateScoreData(array $csvRows, array $columns, int $rankObject): array
    {
        $uids = $fids = $bids = [];

        foreach ($csvRows as $rowIndex => &$row) {
            $rowNum = $rowIndex + 1;

            if (count($columns) != count($row)) {
                return [false, "第{$rowNum}行，表头与数据列数不一致", []];
            }

            // 验证分值必须为整数
            $score = $row['score'] ?? '';
            if (!is_numeric($score) || intval($score) != $score) {
                return [false, "第{$rowNum}行，分值必须为整数", []];
            }

            !empty($row['uid1']) && $uids[] = $row['uid1'];
            !empty($row['uid2']) && $uids[] = $row['uid2'];
            !empty($row['fid1']) && $fids[] = $row['fid1'];
            !empty($row['bid1']) && $bids[] = $row['bid1'];

            // CP榜需要 比较uid1和uid2 小的放在uid1 大的放在uid2
            if ($rankObject == BbcRankButtonTag::RANK_OBJECT_CP) {
                if ($row['uid1'] > $row['uid2']) {
                    $temp = $row['uid1'];
                    $row['uid1'] = $row['uid2'];
                    $row['uid2'] = $temp;
                }
            }
        }

        // 验证cp 关系
        if ($rankObject == BbcRankButtonTag::RANK_OBJECT_CP) {
            $uids1 = array_column($csvRows, 'uid1');
            $uids2 = array_column($csvRows, 'uid2');

            $cpObjIds = XsUserIntimateRelation::getListByUids($uids1, $uids2);

            foreach ($csvRows as $rowIndex => &$row) {
                $rowNum = $rowIndex + 1;
                $uid1 = $row['uid1'];
                $uid2 = $row['uid2'];
                $cpObjId = $cpObjIds[$uid1 . '_' . $uid2] ?? 0;
                if (empty($cpObjId)) {
                    return [false, "第{$rowNum}行，uid1:{$uid1}和uid2:{$uid2}的CP关系不存在", []];
                }
                $row['cpid'] = $cpObjId;
            }
        }


        if ($uids) {
            $uids = Helper::handleIds($uids);
            $invalidUids = XsUserProfile::checkUid($uids);
            if ($invalidUids) {
                return [false, '保存失败，uid：' . implode('、', $invalidUids) . '不存在。', []];
            }
        }
        if ($fids) {
            $fids = Helper::handleIds($fids);
            $invalidFamilyIds = XsFamily::checkFid($fids);
            if ($invalidFamilyIds) {
                return [false, '保存失败，家族id：' . implode('、', $invalidFamilyIds) . '不存在。', []];
            }
        }

        if ($bids) {
            $bids = Helper::handleIds($bids);
            $invalidBids = XsBroker::checkBid($bids);
            if ($invalidBids) {
                return [false, '保存失败，公会id：' . implode('、', $invalidBids) . '不存在。', []];
            }
        }


        return [true, '', $csvRows];
    }

    /**
     * 保存分值数据
     * @param array $data
     * @return void
     */
    public function addScore(array $data): void
    {   
        $actScoreDetail = $data['act_score_detail'] ?? [];
        if (empty($actScoreDetail)) {
            return;
        }

        $batchSize = 1000;
        $batches = array_chunk($actScoreDetail, $batchSize);
        $psService = new PsService();
        $url = SLACK_ACTIVITY_WEBHOOK;
        /** @var SdkSlack $obj */
        $obj = factory_single_obj(SdkSlack::class);
        $admin = Helper::getSystemUserName();
        $errorCount = $successCount = 0;

        $info = sprintf('活动ID: %d, 榜单ID: %d, 轮次：%d', $data['act_id'] ?? 0, $data['list_id'] ?? 0, $data['cycle'] ?? 0);
        
        foreach ($batches as $batch) {
            $batchData = $data;
            $batchData['act_score_detail'] = $batch;
            list($res, $msg) = $psService->opActScore($batchData);
            if (!$res) {
                $errorCount += count($batch);
                $errorData = [$batch[0], '...', end($batch)];
                $errorContent = <<<STR
> 异常告警🚨!!!
> 榜单信息: {info}
> 操作人: {admin}
> 异常时间: {dateline}
> 异常信息: {error_message}
> 异常条数：{count}
> 异常数据：{data}
STR;
                $errorContent = str_replace(
                    ['{info}', '{admin}', '{dateline}', '{error_message}', '{count}', '{data}'],
                    [$info, $admin, date('Y-m-d H:i:s'), $msg, count($batch), json_encode($errorData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)],
                    $errorContent
                );
                $obj->sendMsg($url, 'markdown', $errorContent);
            } else {
                $successCount += count($batch);
            }
            usleep(1000 * 100);
        }
        

        // 发送成功通知
        $successContent = <<<STR
> 处理通知✅!!!
> 榜单信息: {info}
> 处理人: {admin}
> 处理时间: {dateline}
> 处理总条数：{totalCount}
> 成功条数：{successCount}
> 失败条数：{errorCount}
STR;
        $successContent = str_replace(
            ['{info}', '{admin}', '{dateline}', '{totalCount}', '{successCount}', '{errorCount}'],
            [$info, $admin, date('Y-m-d H:i:s'), count($actScoreDetail), $successCount, $errorCount],
            $successContent

        );  
        $obj->sendMsg($url, 'markdown', $successContent);
    }

    /**
     * 验证添加分值数据
     * @param array $params
     * @return array
     */
    public function validationAddScore(array $params): array
    {
        $listIdStr = $params['buttonlist_id'] ?? '';
        $csvData = $params['csvData'] ?? '';
        $actId = $params['act_id'] ?? 0;
        $rankObject = $params['rank_object'] ?? 0;

        if (empty($actId) || empty($listIdStr) || empty($csvData) || empty($rankObject)) {
            return [false, '参数不完整', []];
        }

        list($listId, $level) = explode('_', $listIdStr);

        $csvRows = @json_decode($csvData, true);
        if (empty($csvRows)) {
            return [false, 'CSV数据格式错误', []];
        }

        $data = [
            'act_id'           => (int)$actId,
            'list_id'          => (int)$listId,
            'cycle'            => (int)$level,
            'act_score_detail' => self::getActScoreDetail($csvRows, $rankObject),
        ];

        return [true, '', $data];
    }

    /**
     * 获取补全积分接口数据
     * @param array $csvRows
     * @param int $rankObject
     * @return array
     */
    private static function getActScoreDetail(array $csvRows, int $rankObject): array
    {
        $actScoreDetails = [];

        foreach ($csvRows as $row) {
            $scoreDetail = [
                'uid'       => 0,
                'score'     => intval($row['score'] ?? 0),
                'extend_id' => 0,
                'cp_object' => []
            ];

            switch ($rankObject) {
                case BbcRankButtonTag::RANK_OBJECT_BROKER: // 公会榜
                    $scoreDetail['uid'] = intval($row['uid1'] ?? 0); // 公会成员uid
                    $scoreDetail['extend_id'] = intval($row['bid1'] ?? 0); // 公会id
                    break;
                case BbcRankButtonTag::RANK_OBJECT_PERSONAL: // 用户榜
                    $scoreDetail['uid'] = intval($row['uid1'] ?? 0);
                    $scoreDetail['extend_id'] = 0;
                    break;
                case BbcRankButtonTag::RANK_OBJECT_ROOM: // 房间榜
                    $scoreDetail['uid'] = intval($row['uid2'] ?? 0); // 贡献者uid
                    $scoreDetail['extend_id'] = intval($row['uid1'] ?? 0); // 房主uid
                    break;
                case BbcRankButtonTag::RANK_OBJECT_FAMILY: // 家族榜
                    $scoreDetail['uid'] = intval($row['uid1'] ?? 0); // 家族成员uid
                    $scoreDetail['extend_id'] = intval($row['fid1'] ?? 0); // 家族id
                    break;
                case BbcRankButtonTag::RANK_OBJECT_CP: // CP榜
                    $uid1 = intval($row['uid1'] ?? 0);
                    $uid2 = intval($row['uid2'] ?? 0);
                    $cpid = intval($row['cpid'] ?? 0);
                    $scoreDetail['extend_id'] = $cpid;
                    $scoreDetail['cp_object'] = [
                        'uid1' => $uid1,
                        'uid2' => $uid2
                    ];
                    break;
                case BbcRankButtonTag::RANK_OBJECT_ANCHOR: // 主播&贡献用户
                    $scoreDetail['uid'] = intval($row['uid2'] ?? 0); // 贡献者uid
                    $scoreDetail['extend_id'] = intval($row['uid1'] ?? 0); // 主播uid
                    break;
                case BbcRankButtonTag::RANK_OBJECT_BROKER_MEMBERS: // 公会成员
                    $scoreDetail['uid'] = intval($row['uid1'] ?? 0);
                    $scoreDetail['extend_id'] = 0;
                    break;
            }
            $actScoreDetails[] = $scoreDetail;
        }

        return $actScoreDetails;
    }

    /**
     * 活动模版配置-列表
     * @param array $filter
     * @param array $query
     * @return array
     */
    public function getActivityList(array $filter, array $query): array
    {
        $conditions = ['type NOT IN ({type:array})'];
        $bind = [
            'type' => [
                BbcTemplateConfig::TYPE_ONE_PK,
                BbcTemplateConfig::TYPE_TASK,
                BbcTemplateConfig::TYPE_WHEEL_LOTTERY,
                BbcTemplateConfig::TYPE_MULTI_TASK,
                BbcTemplateConfig::TYPE_EXCHANGE
            ]
        ];
        if (!empty($filter['onlineMode'])) {
            $conditions[] = 'onlineMode = :onlineMode:';
            $bind['onlineMode'] = $filter['onlineMode'];
        }
        if (!empty($filter['bigarea'])) {
            $conditions[] = "FIND_IN_SET(:bigarea_id:, REPLACE(bigarea_id,'|',','))";
            $bind['bigarea_id'] = $filter['bigarea'];
        }
        if (!empty($filter['admin_id'])) {
            $conditions[] = 'admin_id = :admin_id:';
            $bind['admin_id'] = $filter['admin_id'];
        }
        if (!empty($filter['id'])) {
            $conditions[] = 'id = :id:';
            $bind['id'] = $filter['id'];
        }
        if (!empty($filter['start'])) {
            $conditions[] = 'start_time >= :start_time:';
            $bind['start_time'] = strtotime($filter['start']);
        }
        if (!empty($filter['end'])) {
            $conditions[] = 'end_time - data_period * 86400 < :end_time:';
            $bind['end_time'] = strtotime($filter['end']) + 86400;
        }
        $time = time();

        if (!empty($filter['status'])) {
            $status = intval($filter['status']) - 1;
            $filterStatus = [BbcTemplateConfig::STATUS_NOT_RELEASE,  BbcTemplateConfig::STATUS_PUBLISH_HAVE, BbcTemplateConfig::STATUS_PUBLISH_ERROR];
            // 状态为未发布
            if (in_array($status, $filterStatus)) {
                $conditions[] = 'status = :status:';
                $bind['status'] = $status;
            } else if ($status == BbcTemplateConfig::STATUS_WAIT_START) {
                $conditions[] = 'start_time > :start_time: AND status NOT IN ({status:array})';
                $bind['start_time'] = $time;
                $bind['status'] = $filterStatus;
            } else if ($status == BbcTemplateConfig::STATUS_HAVE) {
                $conditions[] = 'start_time < :start_time: AND end_time - data_period * 86400 >= :end_time: AND status NOT IN ({status:array})';
                $bind['start_time'] = $time;
                $bind['end_time'] = $time;
                $bind['status'] = $filterStatus;
            } else if ($status == BbcTemplateConfig::STATUS_END) {
                $conditions[] = 'end_time - (data_period * 86400) < :end_time: AND status NOT IN ({status:array})';
                $bind['end_time'] = $time;
                $bind['status'] = $filterStatus;
            }
        }

        if (!empty($filter['audit_status'])) {
            $conditions[] = 'status = :audit_status:';
            $bind['audit_status'] = $filter['audit_status'];
        }

        $res = BbcTemplateConfig::getList(['conditions' => $conditions, 'bind' => $bind], '*', $query['page'] ?? 1, $query['pageSize'] ?? 15);
        $adminIds = array_unique(array_column($res['data'], 'admin_id'));
        $publisherIds = array_unique(array_column($res['data'], 'publisher_id'));
        $cmsUserArr = CmsUser::getAdminUserBatch(array_merge($adminIds, $publisherIds));
        $imagesKey = [
            'one_level_long_select',
            'one_level_long_no_select',
            'one_level_short_select',
            'one_level_short_no_select',
            'two_level_select',
            'two_level_no_select',
            'user_list_background',
            'top1_user_head',
            'top2_user_head',
            'top3_user_head',
            'broker_list_background',
            'top1_president_head',
            'top2_president_head',
            'top3_president_head',
            'pop_top_ornament',
            'rule_button_img',
            'rule_img',
            'banner_homepage_img',
            'desc_path',
            'banner_bottom_img',
            'money_img',
            'share_button_img',
            'new_banner_homepage_img',
            'new_money_img',
            'multigroup_user_list_top3',
            'multigroup_banner_homepage_img',
            'multigroup_gift_icon_bgc',
            'multigroup_gift_list_one_select',
            'multigroup_gift_list_one_no_select',
            'multigroup_gift_list_two_select',
            'multigroup_gift_list_two_no_select',
            'multigroup_back_btn',
            'multigroup_gift_carousel_about_btn',
            'multigroup_gift_carousel_select',
            'multigroup_gift_carousel_no_select',
            'multigroup_user_list_select',
            'multigroup_user_list_no_select',
            'weekstar_banner_homepage_img',
            'weekstar_gift_bgc_select',
            'weekstar_gift_bgc_no_select',
            'weekstar_btn_select',
            'weekstar_btn_no_select',
            'weekstar_top1_user_head',
            'weekstar_top2_user_head',
            'weekstar_top3_user_head',
            'weekstar_top3_bgc',
            'family_pop_top_ornament',
            'broker_pop_top_ornament',
            'top1_family_head',
            'top2_family_head',
            'top3_family_head',
            'family_pop_top_ornament',
            'family_integral_hand',
            'top1_cp_bgc',
            'top2_cp_bgc',
            'top3_cp_bgc',
            'top4_cp_head',
            'cp_icon',
            'cp_pop_top_ornament',
            'relate_icon',
            'room_list_bgc',
            'top1_host_head',
            'top2_host_head',
            'top3_host_head',
            'room_pop_top_ornament',
            'anchor_bgc',
            'anchor_head_one',
            'anchor_head_two',
            'anchor_head_three',
            'anchor_pop_top_ornament',
            'celebrity_header_img',
            'celebrity_homepage_head_shot_frame',
            'celebrity_head_shot_frame_top1',
            'celebrity_head_shot_frame_top2',
            'celebrity_head_shot_frame_top3',
        ];
        $versionId = BbcTemplateConfig::VERSION_ID;
        foreach ($res['data'] as &$val) {
            $val['award_show'] = $this->isAwardShow($val['id'], $val['time_offset'], $val['vision_type']);
            $dataPeriod = $val['data_period'] * 86400;
            $startTime = $val['start_time'] - $this->getTimeOffsetNew($val['time_offset']);
            $endTime = $val['end_time'] - $this->getTimeOffsetNew($val['time_offset']) - $dataPeriod;
            $val['time_offset'] = $this->setTimeOffsetNew($val['time_offset']);
            // 默认展示7
            if ($val['data_period'] == 0) {
                $val['data_period'] = 7;
            }
            $val['audit_status'] = $this->getAuditStatus($val['status']);
            $val['status'] = $this->getStatus($val['status'], $val['start_time'], $val['end_time'] - $dataPeriod);
            $val['bigarea_id'] = $this->formatBigArea($val['bigarea_id']);
            // $val['audit_status'] = $this->getAuditStatus($val['status']);
            $val['cycles_num'] = $val['cycles'];
            if ($val['vision_type'] != BbcTemplateConfig::VISION_TYPE_THREE) {
                $val['cycles'] = 0;
                $val['cycle_type'] = 0;
            } else {
                if ($val['cycle_type'] == 2) {
                    $val['cycles'] = '多次-' . $val['cycles'];
                } else {
                    $val['cycles'] = '单次';
                }
            }
            $val['dateline'] = $val['dateline'] > 0 ? Helper::now($val['dateline']) : ' - ';
            $val['start_time'] = Helper::now($startTime);
            $val['end_time'] = Helper::now($endTime);
            $val['admin'] = $cmsUserArr[$val['admin_id']]['user_name'] ?? '-';
            $val['publisher'] = $cmsUserArr[$val['publisher_id']]['user_name'] ?? '-';
            if ($val['vision_content_json']) {
                $visionJson = json_decode($val['vision_content_json'], true);
                $val = array_merge($val, $visionJson);
            }
            $val['relate_type'] = $this->getRelateType($val['relate_id']);
            foreach ($imagesKey as $field) {
                if (isset($val[$field])) {
                    $val[$field] = Helper::getHeadUrl($val[$field]);
                }
            }
            $this->onShiftFields($val);
            $val['version_id'] = $versionId;
        }
        return $res;
    }

    /**
     * 发钻名单入口是否展示
     * 奖励中存在钻石且所有日榜结束后入口才展示
     * @param int $id
     * @param int $timeOffset
     * @param int $visionType
     * @return int
     */
    private function isAwardShow(int $id, int $timeOffset, int $visionType): int
    {
        $show = BbcRankButtonList::IS_AWARD_NO;
        $list = BbcRankButtonList::getListByWhere([
            ['act_id', '=', $id],
            ['is_award', '=', BbcRankButtonList::IS_AWARD_YES]
        ]);
        if (empty($list)) {
            return $show;
        }

        $timeOffset = $this->getTimeOffsetNew($timeOffset);
        $buttonListIdArr = Helper::arrayFilter($list, 'id');
        // 判断是否包含钻石奖励
        $rankAward = BbcRankAward::findOneByWhere([
            ['button_list_id', 'IN', $buttonListIdArr],
            ['award_type', '=', BbcRankAward::AWARD_TYPE_DIAMOND]
        ]);
        if ($rankAward) {
            $tag = BbcRankButtonTag::getBatchCommon(Helper::arrayFilter($list, 'button_tag_id'), ['id', 'tag_list_type']);
            $minTime = 0;
            // 日榜 || 周星礼物 需要特殊处理
            foreach ($list as $k => $item) {
                $awardTime = $item['award_time'];
                //  日榜取榜单第一期结束时间
                if ($tag[$item['button_tag_id']]['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_DAY) {
                    $awardTime = $item['start_time'] - $timeOffset + 86399;
                } else if ($tag[$item['button_tag_id']]['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE) {
                    $awardTime = ($item['start_time'] - $timeOffset) + $item['cycle_days'] * 86400 - 1;
                }
                // 周星榜单取第一期活动结束时间
                if ($visionType == BbcTemplateConfig::VISION_TYPE_THREE) {
                    // dev调整周星发奖周期为1天
                    $cycle = ENV == 'dev' ? 1 : 7;
                    $awardTime = ($item['start_time'] - $timeOffset) + $cycle * 86400;
                }
                if ($k == 0) {
                    $minTime = $awardTime;
                } else {
                    $minTime > $awardTime && $minTime = $awardTime;
                }
            }
            if ($minTime < time()) {
                $show = BbcRankButtonList::IS_AWARD_YES;
            }
        }

        // 判断是否包含奖池奖励
        if (!$show) {
            $rankAward = BbcRankAward::findOneByWhere([
                ['button_list_id', 'IN', $buttonListIdArr],
                ['award_type', '=', BbcRankAward::AWARD_TYPE_PRIZE_POOL]
            ]);
            $rankAward && $show = BbcRankButtonList::IS_AWARD_YES;
        }

        return $show;
    }

    public function formatBigArea($bigArea)
    {
        $bigAreaMap = XsBigarea::getAllNewBigArea();
        if (empty($bigArea)) {
            return '';
        }
        $area = explode('|', $bigArea);
        $areaStr = '';
        foreach ($area as $v) {
            $areaStr .= $bigAreaMap[$v] . ',';
        }
        return rtrim($areaStr, ',');
    }

    private function onShiftFields(&$val)
    {
        if ($val['vision_type'] == 2) {
            $val['banner_homepage_img'] = $val['new_banner_homepage_img'];
        } else if ($val['vision_type'] == 3) {
            $val['banner_homepage_img'] = $val['weekstar_banner_homepage_img'];
            $val['top1_user_head'] = $val['weekstar_top1_user_head'];
            $val['top2_user_head'] = $val['weekstar_top2_user_head'];
            $val['top3_user_head'] = $val['weekstar_top3_user_head'];
        } else if ($val['vision_type'] == 1) {
            $val['font_color'] = $val['new_font_color'];
            $val['money_color'] = $val['new_money_color'];
            $val['banner_homepage_img'] = $val['new_banner_homepage_img'];
            $val['background_color'] = $val['new_background_color'];
            $val['money_img'] = $val['new_money_img']??'';
        } else if ($val['vision_type'] == 4) {
            $val['background_color'] = $val['multigroup_background_color'];
            $val['module_background_color'] = $val['multigroup_module_background_color'];
            $val['module_border_color'] = $val['multigroup_module_border_color'];
            $val['font_color'] = $val['multigroup_font_color'];
            $val['head_border_color'] = $val['multigroup_head_border_color'];
            $val['money_color'] = $val['multigroup_money_color'];
            $val['banner_homepage_img'] = $val['multigroup_banner_homepage_img'];
        }
    }

    private function validActivityModify($data, $config = [])
    {
        $bool = $this->validActiveStatus($data['status']);
        if ($bool) {
            $data['relate_id'] = $config['relate_id'];
            $data['has_relate'] = $config['has_relate'];
            $data['vision_type'] = $config['vision_type'];
        }
        $visionArray = json_decode($data['vision_content_json'], JSON_UNESCAPED_UNICODE);
        if (empty($visionArray)) {
            return [false, '动态配置区域配置错误'];
        }

        if (!$bool && (!isset($data['vision_type']) || !in_array($data['vision_type'], BbcTemplateConfig::$visionType))) {
            return [false, '必须选择活动视觉'];
        }

        // 获取需要验证的字段信息
        [$baseMsg, $_, $baseFields] = $this->getPublicFields();
        if ($data['vision_type'] == BbcTemplateConfig::VISION_TYPE_ONE) {
            [$msg, $configFields, $configAllFields] = $this->getOneVisionFields();
        } elseif ($data['vision_type'] == BbcTemplateConfig::VISION_TYPE_FAMILY) {
            [$msg, $configFields, $configAllFields] = $this->getFamilyVisionFields();
        } elseif ($data['vision_type'] == BbcTemplateConfig::VISION_TYPE_CUSTOMIZED) {
            [$msg, $configFields, $configAllFields] = $this->getCustomizedVisionFields();
        } elseif ($data['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
            [$msg, $configFields, $configAllFields] = $this->getWeekStarVisionFields();
        } elseif ($data['vision_type'] == BbcTemplateConfig::VISION_TYPE_FOUR) {
            [$msg, $configFields, $configAllFields] = $this->getMultipGroupVisionFields();
        } elseif ($data['vision_type'] == BbcTemplateConfig::VISION_TYPE_CP) {
            [$msg, $configFields, $configAllFields] = $this->getCpVisionFields();
        }
        $params = array_merge($data, $visionArray);
        $message = array_merge($baseMsg, $msg);
        $fields = array_merge($configFields, $baseFields);
        $allFields = array_merge($configAllFields, $baseFields);

        $insert = [
            'admin_id' => Helper::getSystemUid(),
            'status'   => $bool ? $data['status'] : 0,
            'dateline' => time()
        ];

        $id = $params['id'] ?? 0;
        $visionJson = [];
        $noAllowField = $this->noAllowUpdateField();
        $noVisionField = ['gift_act_ids', 'gift_act_type', 'award_content_json', 'auto_read_config'];
        foreach ($allFields as $field) {
            // 待生成和待发布(已生成)状态下不允许修改非h5页面信息
            if ($bool && in_array($field, $noAllowField)) {
                continue;
            }
            if ($field == 'award_content_json' &&
                (($id <= BbcTemplateConfig::VERSION_ID) || ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_FAMILY && $params['auto_read_config'] == 1))) {
                continue;
            }
            if ($field == 'test_white_list' && $params['online_mode'] == 2) {
                continue;
            }
            if ($field == 'gift_act_ids' && $params['gift_act_type'] == 1) {
                continue;
            }
            if ($field == 'auto_read_config' && $params['vision_type'] != BbcTemplateConfig::VISION_TYPE_FAMILY) {
                continue;
            }
            // 关联玩法为否是不需要验证任务id及任务icon
            if ($params['has_relate'] == BbcTemplateConfig::HAS_RELATE_NO) {
                if (in_array($field, ['relate_id', 'relate_icon', 'relate_type'])) {
                    continue;
                }
            }
            if (in_array($field, $fields)) {
                if (!isset($params[$field]) || $params[$field] === '') {
                    return [false, $message[$field] . '为必填项'];
                }
            }
            if ($field == 'data_period') {
                if ($params[$field] > 30) {
                    return [false, $message[$field] . '天数不得超过30天'];
                }
            }

            if ($field == 'cycles' && empty($params['cycles'])) {
                return [false, '循环次数最小为1'];
            }

            if ($field == 'bigarea_id') {
                $params[$field] = implode('|', $params[$field]);
            }
            if ($field == 'gift_act_ids') {
                [$res, $giftId] = $this->checkGiftActIds($params['gift_act_ids']);
                if (!$res) {
                    return [false, '礼物ID：' . $giftId . '不存在'];
                }
                $params['gift_act_ids'] = $giftId;
            }

            if (
                $data['vision_type'] != BbcTemplateConfig::VISION_TYPE_ONE
                && in_array($field, $configAllFields)
                && !in_array($field, $noVisionField)
            ) {
                $visionJson[$field] = $params[$field];
            } else {
                $insert[$field] = $params[$field];
            }
        }

        $insert['vision_content_json'] = json_encode($visionJson, JSON_UNESCAPED_UNICODE);

        // 非基础视觉2 || cp视觉 相关任务玩法字段给默认值
        if ($data['has_relate'] == BbcTemplateConfig::HAS_RELATE_NO) {
            $insert['relate_id'] = 0;
            $insert['relate_icon'] = '';
        }

        // 保留天数不存在时默认为7天
        if (empty($insert['data_period'])) {
            $insert['data_period'] = 7;
        }

        if (isset($insert['cycle_type']) && $insert['cycle_type'] == BbcTemplateConfig::CYCLE_TYPE_ONE) {
            $insert['cycles'] = 1;
        }

        if (isset($insert['gift_act_type']) && $insert['gift_act_type'] == '1') {
            $insert['gift_act_ids'] = self::ALL_CUSTOMIZED_GIFT;
        }

        // 视觉数据配置
        $this->setVisionContentJson($insert, $data['vision_type']);

        // 验证关联玩法
        list($relationRes, $type) = $this->verifyRelation($insert, $data['id'] ?? 0);
        if (!$relationRes) {
            return [$relationRes, $type];
        }

        // 非基础视觉2 || 基础视觉2 手动配置奖励时 award_content_json 为空
        if (in_array($data['vision_type'], [BbcTemplateConfig::VISION_TYPE_THREE, BbcTemplateConfig::VISION_TYPE_CP]) || ($data['auto_read_config'] ?? 0) == 1) {
            $insert['award_content_json'] = '';
        }

        return [true, $insert];
    }

    /**
     * 验证关联活动
     * @param $data
     * @param $id
     * @return array
     */
    public function verifyRelation(&$data, $id): array
    {
        if (empty($data['relate_id'])) {
            return [true, 0];
        }

        $relateActivity = BbcTemplateConfig::findOne($data['relate_id']);
        if (empty($relateActivity) || !in_array($relateActivity['type'], [BbcTemplateConfig::TYPE_TASK, BbcTemplateConfig::TYPE_MULTI_TASK, BbcTemplateConfig::TYPE_WHEEL_LOTTERY])) {
            return [false, '你填写的"关联id"不存在或不属于任务或幸运玩法'];
        }
        if ($relateActivity['has_be_related'] == BbcTemplateConfig::HAS_RELATE_NO) {
            return [false, '您关联的玩法id，当前不是可被关联状态，请先将对应的玩法的“是否被其他模板关联”结果改为“是”。'];
        }
        // 验证幸运玩法是否关联了其他玩法 且 是否被其他模版关联是否为是
        if ($relateActivity['type'] == BbcTemplateConfig::TYPE_WHEEL_LOTTERY && $relateActivity['relate_id']) {
            $wheelotteryRelateTask = BbcTemplateConfig::findOne($relateActivity['relate_id']);
            if (empty($wheelotteryRelateTask) || $wheelotteryRelateTask['has_be_related'] == BbcTemplateConfig::HAS_RELATE_NO) {
                return [false, '您关联的玩法id，还关联了其他玩法，其他玩法当前不是可被关联状态，请先将对应的玩法的“是否被其他模板关联”结果改为“是”。'];
            }
        }

        $relationTemplate = BbcTemplateConfig::findOneByWhere([
            ['id', '<>', $id],
            ['relate_id', '=', $data['relate_id']],
        ]);
        if ($relationTemplate) {
            return [false, '每个玩法id只能被一个榜单活动绑定，而你绑定的玩法id已被其他活动使用（活动id：' . $relationTemplate['id'] . '）'];
        }

        // 当发布有关联玩法（包括幸运玩法和任务玩法时），校验关联玩法活动周期必须在榜单活动时间周期范围内，
        // 否则报错：“活动周期必须在榜单活动时间周期范围内，请修改活动时间”，不允许发布

        // 当前活动信息
        $currentActivity = BbcTemplateConfig::findOne($id);

        if ($currentActivity && !empty($currentActivity['start_time']) && !empty($currentActivity['end_time'])) {
            $currentActivityTimeOffset = $this->getTimeOffsetNew($currentActivity['time_offset']);
            $currentActivityStartTime = $currentActivity['start_time'] - $currentActivityTimeOffset;
            $currentActivityEndTime = $currentActivity['end_time'] - $currentActivityTimeOffset - ($currentActivity['data_period'] * 86400);

            // 关联活动信息
            $relateActivityTimeOffset = $this->getTimeOffsetNew($relateActivity['time_offset']);
            $relateActivityStartTime = $relateActivity['start_time'] - $relateActivityTimeOffset;
            $relateActivityEndTime = $relateActivity['end_time'] - $relateActivityTimeOffset - ($relateActivity['data_period'] * 86400);

            if ($relateActivityStartTime < $currentActivityStartTime || $relateActivityEndTime > $currentActivityEndTime) {
                return [false, '活动周期必须在榜单活动时间周期范围内，请修改活动时间'];
            }
        }

        $data['relate_type'] = BbcTemplateConfig::$typeAndRelateTypeMap[$relateActivity['type']] ?? 0;

        return [true, $data['relate_type']];
    }

    /**
     * 视觉相关字段处理
     *
     * @param array $data
     * @param int $visionType
     * @return void
     */
    private function setVisionContentJson(array &$data, int $visionType): void
    {
        if ($visionType == BbcTemplateConfig::VISION_TYPE_ONE) {
            $data['vision_content_json'] = '';
        } else {
            [$fields, $_, $_] = $this->getOneVisionFields();
            foreach ($fields as $field => $name) {
                $data[$field] = '';
            }
        }
    }

    /**
     * 不允许修改字段
     * @return string[]
     */
    private function noAllowUpdateField()
    {
        return [
            'online_mode',
            'bigarea_id',
            'type',
            'time_offset',
            'test_white_list',
            'gift_act_ids',
            'gift_act_type',
            'relate_type',
        ];
    }

    /**
     * 家族视觉字段
     * @return array
     */
    private function getFamilyVisionFields()
    {
        $message = [
            'new_background_color'      => '背景色',
            'module_background_color'   => '模块背景色',
            'title_background_color'    => '标题背景色',
            'table_background_color'    => '表格背景色',
            'module_border_color'       => '模块边框色',
            'head_border_color'         => '头像边框色',
            'btn_select_text_color'     => '按钮选中字色',
            'btn_no_select_text_color'  => '按钮未选中字色',
            'new_font_color'            => '主字色',
            'new_money_color'           => '货币字色',
            'highlight_color'           => '高亮色',
            'new_banner_homepage_img'   => '榜单头图',
            'one_level_long_select'     => '一级按钮（长）选中',
            'one_level_long_no_select'  => '一级按钮（长）未选中',
            'one_level_short_select'    => '一级按钮（短）选中',
            'one_level_short_no_select' => '一级按钮（短）未选中',
            'two_level_select'          => '二级按钮选中',
            'two_level_no_select'       => '二级按钮未选中',
            'award_content_json'        => '活动奖励页面配置',
            'auto_read_config'          => '奖励页面配置读取方式',
        ];

        $allowEmpty = [
            'new_money_img'           => '货币图标',
            'user_list_background'    => 'TOP3用户背景',
            'top1_user_head'          => 'TOP1用户头像框',
            'top2_user_head'          => 'TOP2用户头像框',
            'top3_user_head'          => 'TOP3用户头像框',
            'broker_list_background'  => 'TOP1公会背景',
            'top1_president_head'     => 'TOP1公会长头像框',
            'top2_president_head'     => 'TOP2公会长头像框',
            'top3_president_head'     => 'TOP3公会长头像框',
            'broker_pop_top_ornament' => '公会弹窗上半部',
            'top1_family_head'        => 'TOP1家族头像框',
            'top2_family_head'        => 'TOP2家族头像框',
            'top3_family_head'        => 'TOP3家族头像框',
            'family_pop_top_ornament' => '家族弹窗上半部',
            'family_integral_hand'    => '家族积分底牌',
            'top1_cp_bgc'             => 'TOP1 CP背景图',
            'top2_cp_bgc'             => 'TOP2 CP背景图',
            'top3_cp_bgc'             => 'TOP3 CP背景图',
            'top4_cp_head'            => 'TOP4-N 头像框',
            'cp_icon'                 => 'CP连线icon',
            'cp_pop_top_ornament'     => 'CP弹窗上半部',
            'room_pop_top_ornament'   => '房间弹窗上半部',
            'room_list_bgc'           => 'TOP1房间背景',
            'top1_host_head'          => 'TOP1 房主背景框',
            'top2_host_head'          => 'TOP2 房主背景框',
            'top3_host_head'          => 'TOP3 房主背景框',
            "anchor_bgc"              => "TOP1主播背景",
            "anchor_head_one"         => "TOP1主播头像框",
            "anchor_head_two"         => "TOP2主播头像框",
            "anchor_head_three"       => "TOP3主播头像框",
            "anchor_pop_top_ornament" => "主播弹窗上半部",
        ];

        $allMessage = array_merge($allowEmpty, $message);

        return [$allMessage, array_keys($message), array_keys($allMessage)];
    }

    /**
     * 定制礼物视觉字段
     * @return array
     */
    private function getCustomizedVisionFields()
    {
        $message = [
            'gift_act_type'           => '活动礼物ID',
            'gift_act_ids'            => '礼物ID',
            'new_banner_homepage_img' => '榜单头图',
            'award_content_json'      => '活动奖励页面配置',
        ];

        $keys = array_keys($message);
        return [$message, $keys, $keys];
    }

    /**
     * 周星视觉字段
     * @return array
     */
    private function getWeekStarVisionFields()
    {
        $message = [
            'weekstar_background_color'         => '背景色',
            'weekstar_module_background_color'  => '模块背景色',
            'weekstar_module_border_color'      => '模块边框色',
            'weekstar_font_color'               => '主字色',
            'weekstar_head_border_color'        => '头像边框色',
            'weekstar_money_color'              => '货币字色',
            'weekstar_btn_select_text_color'    => '按钮选中字色',
            'weekstar_btn_no_select_text_color' => '按钮未选择字色',
            'weekstar_list_speed_bgc_color'     => '榜单个人进度背景色',
            'weekstar_banner_homepage_img'      => '榜单头图',
            'weekstar_gift_bgc_select'          => '礼物选中背景图',
            'weekstar_gift_bgc_no_select'       => '礼物未选中背景图',
            'weekstar_btn_select'               => '按钮选中图',
            'weekstar_btn_no_select'            => '按钮未选中图',
        ];

        $allowEmpty = [
            'weekstar_top1_user_head'            => 'TOP1用户头像框',
            'weekstar_top2_user_head'            => 'TOP2用户头像框',
            'weekstar_top3_user_head'            => 'TOP3用户头像框',
            'weekstar_top3_bgc'                  => 'TOP3的背景图',
            'celebrity_header_img'               => '名人堂头图',
            'celebrity_homepage_head_shot_frame' => '主页名人头像框',
            'celebrity_head_shot_frame_top1'     => '名人头像框TOP1',
            'celebrity_head_shot_frame_top2'     => '名人头像框TOP2',
            'celebrity_head_shot_frame_top3'     => '名人头像框TOP3',
        ];

        $allMessage = array_merge($allowEmpty, $message);

        return [$allMessage, array_keys($message), array_keys($allMessage)];
    }

    /**
     * 多组礼物视觉字段
     * @return array
     */
    private function getMultipGroupVisionFields()
    {
        $message = [
            'multigroup_background_color'         => '背景色',
            'multigroup_module_background_color'  => '模块背景色',
            'multigroup_module_border_color'      => '模块边框色',
            'multigroup_font_color'               => '主字色',
            'multigroup_head_border_color'        => '头像边框色',
            'multigroup_money_color'              => '货币字色',
            'multigroup_btn_select_text_color'    => '按钮选中字色',
            'multigroup_btn_no_select_text_color' => '按钮未选择字色',
            'multigroup_banner_homepage_img'      => '榜单头图',
            'multigroup_gift_icon_bgc'            => '礼物图标的背景图',
            'multigroup_gift_list_one_select'     => '礼物榜一级按钮选中',
            'multigroup_gift_list_one_no_select'  => '礼物榜一级按钮未选中',
            'multigroup_gift_list_two_select'     => '礼物榜二级按钮选中',
            'multigroup_gift_list_two_no_select'  => '礼物榜二级按钮未选中',
            'multigroup_back_btn'                 => '返回上一页按钮',
            'multigroup_gift_carousel_about_btn'  => '礼物轮播左右按钮',
            'multigroup_gift_carousel_select'     => '礼物轮播选中状态',
            'multigroup_gift_carousel_no_select'  => '礼物轮播未选中状态',
            'multigroup_user_list_select'         => '用户榜单按钮选中',
            'multigroup_user_list_no_select'      => '用户榜单按钮未选中',
            'multigroup_user_list_top3'           => '用户榜前三名背景+头像框切图',
            'award_content_json'                  => '活动奖励页面配置'
        ];

        $keys = array_keys($message);
        return [$message, $keys, $keys];
    }

    /**
     * cp视觉字段
     * @return array
     */
    private function getCpVisionFields()
    {
        $message = [
            'banner_homepage_img'      => '榜单头图',
            'long_btn_select'          => '长按钮选中',
            'long_btn_no_select'       => '长按钮未选中',
            'short_btn_select'         => '短按钮选中',
            'short_btn_no_select'      => '短按钮未选中',
            'top1_cp_bgc'              => 'TOP1 CP背景图',
            'top2_cp_bgc'              => 'TOP2 CP背景图',
            'top3_cp_bgc'              => 'TOP3 CP背景图',
            'top4_cp_head'             => 'TOP4-N 头像框',
            'cp_icon'                  => 'CP连线icon',
            'cp_pop_top_ornament'      => 'CP弹窗上半部',
            'module_stroke_color'      => '模块描边色',
            'module_color'             => '模块颜色',
            'background_color_one'     => '背景色1',
            'background_color_two'     => '背景色2',
            'rule_bubble_pop_color'    => '规则/气泡/弹窗色',
            'main_content_color'       => '主文案字色',
            'title_img'                => '标题切图',
            'btn_select_text_color'    => '按钮选中字色',
            'btn_no_select_text_color' => '按钮未选中字色',
            'money_color'              => '货币字色'
        ];

        $allowEmpty = [
            'top_position_bgc' => '头图展示位背景',
        ];

        $allMessage = array_merge($allowEmpty, $message);

        return [$allMessage, array_keys($message), array_keys($allMessage)];
    }


    /**
     * 基础视觉1字段
     * @return array
     */
    private function getOneVisionFields(): array
    {
        $message = [
            'banner_homepage_img'          => '榜单头图',
            'banner_bottom_img'            => '榜单底图',
            'background_color'             => '背景色',
            'money_img'                    => '货币图标',
            'activity_time_color'          => '活动时间字色',
            'money_color'                  => '货币字色',
            'font_color'                   => '主字色',
            'countdown_bottom_color'       => '倒计时框底色',
            'frame_color'                  => '主框底色',
            'countdown_side_color'         => '倒计时描边色',
            'side_color'                   => '主描边色',
            'countdown_font_color'         => '倒计时字色',
            'rule_button_img'              => '规则按钮图片',
            'rule_img'                     => '规则banner',
            'rule_button_content'          => '规则页按钮文字',
            'rule_button_content_color'    => '规则页按钮文字颜色',
            'rule_title_bottom_color'      => '规则页标题底色',
            'rule_title_frame_color'       => '规则页标题边框色',
            'rule_bottom_color'            => '规则页表格底色',
            'rule_frame_color'             => '规则页表格边框色',
            'button_tag_img_color'         => 'tag按钮图色',
            'button_tag_select_img_color'  => 'tag按钮选中态图色',
            'button_tag_color'             => 'tag按钮文字色',
            'button_tag_select_color'      => 'tag按钮文字选中色',
            'button_list_img_color'        => '榜单类型按钮图色',
            'button_list_select_img_color' => '榜单类型按钮选中态图色',
            'button_list_color'            => '榜单类型按钮文字色',
            'button_list_select_color'     => '榜单类型按钮文字选中色',
            'share_button_img'             => '分享按钮图',
            'frame_bottom_color'           => '自己排行框底色',
        ];

        $keys = array_keys($message);

        return [$message, $keys, $keys];
    }

    /**
     * 公共字段
     * @return array
     */
    private function getPublicFields()
    {
        $message = [
            'title'             => '活动名称',
            'online_mode'       => '活动上线模式',
            'language'          => '语言',
            'bigarea_id'        => '支持大区',
            'type'              => '活动类型',
            'vision_type'       => '活动视觉',
            'rule_content_json' => '规则页面配置',
            'has_relate'        => '是否关联玩法',
            'relate_id'         => '关联ID',
            'relate_icon'       => '关联入口icon',
            'relate_type'       => '关联玩法',
            'time_offset'       => '活动时区',
        ];

        $allowEmpty = [
            'test_white_list' => '活动测试白名单',
            'data_period'     => '活动结束后页面数据保留天数'
        ];

        $allMessage = array_merge($allowEmpty, $message);

        return [$allMessage, array_keys($message), array_keys($allMessage)];
    }

    public function checkGiftActIds($giftActIds)
    {
        $ids = explode("\n", $giftActIds);
        $ids = Helper::handleIds($ids);
        $diff = [];
        if ($ids) {
            foreach (array_chunk($ids, 1) as $arr) {
                $list = XsGift::getListByIds($arr);
                if (empty($list)) {
                    $diff = array_merge($diff, $arr);
                } else {
                    $diffItem = array_diff($arr, $list);
                    $diffItem && $diff = array_merge($diff, $diffItem);
                }
            }
        }
        return [!$diff, implode(',', $diff ?: $ids)];
    }

    /**
     * 获取活动版本id
     * @param int $type
     * @return array
     */
    public function getActiveVersion(int $type): array
    {
        switch ($type) {
            case 2:
                $versionId = BbcTemplateConfig::VERSION_ID_TWO;
                break;
            default:
                $versionId = 0;
        }

        return ['version_id' => $versionId];
    }

    /**
     * @desc 活动配置添加
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function activeCreate(array $data)
    {
        $data['status'] = 0;
        [$res, $data] = $this->validActivityModify($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $data);
        }
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            [$result, $pk] = BbcTemplateConfig::add($data);
            if (!$result) {
                throw new \Exception($pk);
            }
            if ($data['vision_type'] == BbcTemplateConfig::VISION_TYPE_CUSTOMIZED) {
                self::defaultButtonTag($pk);
            }
            $this->updatePageUrl($pk, $data['status']);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ApiException(ApiException::MSG_ERROR, '操作失败，请稍后处理' . $e->getMessage());
        }
    }

    public function activeModify($data): bool
    {
        $id = intval($data['id'] ?? 0);
        $act = BbcTemplateConfig::findOne($id);
        if (empty($act)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动模版不存在');
        }
        $data['status'] = $act['status'];
        [$res, $data] = $this->validActivityModify($data, $act);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $data);
        }
        $oldTimeOffset = $act['time_offset'];
        $oldDataPeriod = $act['data_period'];
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            $data['updated_at'] = time();
            unset($data['admin_id'], $data['dateline']);
            BbcTemplateConfig::edit($id, $data);
            if (!$this->validActiveStatus($data['status'])) {
                if ($data['vision_type'] == BbcTemplateConfig::VISION_TYPE_CUSTOMIZED) {
                    $this->defaultButtonTag($id);
                } elseif ($oldTimeOffset != $data['time_offset']) {
                    $this->updateDayTime($data['time_offset'], $oldTimeOffset, $id);
                }
            }
            if ($oldDataPeriod != $data['data_period']) {
                $this->updateDataPeriod($oldDataPeriod, $data['data_period'], $id);
            }
            $this->updatePageUrl($id, $data['status']);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ApiException(ApiException::MSG_ERROR, '操作失败，请稍后处理' . $e->getMessage());
        }
    }

    public static function defaultButtonTag($actId)
    {
        $tag = BbcRankButtonTag::findOneByWhere([['act_id', '=', $actId], ['button_tag_type', '=', 'left']]);
        if (empty($tag)) {
            BbcRankButtonTag::add([
                'act_id'              => $actId,
                'button_tag_type'     => 'left',
                'button_content'      => '',
                'rank_object'         => '2',
                'rank_object_content' => '',
                'admin_id'            => Helper::getSystemUid(),
                'dateline'            => time(),
            ]);
        }
    }

    /**
     * 更新日榜榜单时间
     * 修改时区时需要同步更新日榜的榜单时间
     * @param $timeOffset
     * @param $oldTimeOffset
     * @param $id
     * @return void
     */
    private function updateDayTime($timeOffset, $oldTimeOffset, $id)
    {
        $list = BbcRankButtonList::getListByWhere([
            ['act_id', '=', $id],
        ]);
        $buttonTagList = BbcRankButtonTag::getBatchCommon(Helper::arrayFilter($list, 'button_tag_id'), ['id', 'tag_list_type', 'cycles']);
        $update = [];
        $oldTimeOffset = $this->getTimeOffsetNew($oldTimeOffset);
        $timeOffset = $this->getTimeOffsetNew($timeOffset);
        foreach ($list as $item) {
            $startTime = $item['start_time'] > 0 ? $item['start_time'] - $oldTimeOffset : 0;
            $endTime = $item['end_time'] > 0 ? $item['end_time'] - $oldTimeOffset : 0;
            $awardTime = $item['award_time'] > 0 ? $item['award_time'] - $oldTimeOffset : 0;
            $tmp = [
                'start_time' => $startTime > 0 ? $startTime + $timeOffset : 0,
                'end_time'   => $endTime > 0 ? $endTime + $timeOffset : 0,
            ];
            if (
                $item['rank_tag'] == BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT ||
                ButtonListService::isDaysAndCycleList($buttonTagList[$item['button_tag_id']]['tag_list_type'])
            ) {
                $tmp['award_time'] = $startTime;
            } else {
                $tmp['award_time'] = $awardTime ? $tmp['end_time'] + 60 : 0;
            }
            // 日｜周期榜更新cycles
            $cycles = $this->getStartEndDay($tmp['start_time'], $tmp['end_time']);
            if ($buttonTagList[$item['button_tag_id']]['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_DAY && $cycles != $buttonTagList[$item['button_tag_id']]['cycles']) {
                BbcRankButtonTag::edit($item['button_tag_id'], ['cycles' => $cycles]);
            }
            $update[$item['id']] = $tmp;
        }
        $startTimeArr = Helper::arrayFilter($update, 'start_time');
        $endTimeArr = Helper::arrayFilter($update, 'end_time');
        $minStartTime = min($startTimeArr);
        $maxStartTime = max($endTimeArr);
        // 更新榜单时间
        BbcRankButtonList::updateBatch($update);
        // 更新活动时间
        $this->updateTemplateTime($minStartTime, $maxStartTime, $id);
    }

    public function updateTemplateTime($startTime, $endTime, $id)
    {
        $activity = BbcTemplateConfig::findOne($id);
        return BbcTemplateConfig::edit($id, [
            'start_time' => $startTime,
            'end_time'   => $endTime > 0 ? $endTime + $activity['data_period'] * 86400 : 0,
            'updated_at' => time()
        ]);
    }

    public function getStartEndDay(int $startTime, int $endTime): int
    {
        return ceil(($endTime - $startTime) / 86400);
    }

    public function updateDataPeriod($oldDataPeriod, $dataPeriod, $id)
    {
        $activity = BbcTemplateConfig::findOne($id);
        $endTime = $activity['end_time'] - $oldDataPeriod * 86400;
        BbcTemplateConfig::edit($id, [
            'end_time' => $endTime + $dataPeriod * 86400
        ]);
    }

    public function updatePageUrl($activeId, $status): array
    {
        if ($this->validActiveStatus($status)) {
            return [true, ''];
        }
        $config = BbcTemplateConfig::findOne($activeId, true);
        if (array_get($config, 'vision_type') == 2) {
            $v = 'rank-gift-template';
        } else if (array_get($config, 'vision_type') == 0) {
            $v = 'rank-template';
        } else {
            $v = 'rank-template-v2';
        }
        if (ENV == 'dev') {
            $pageUrl = self::DEV_URL . "/{$v}/?aid={$activeId}&clientScreenMode=1";
        } else {
            $pageUrl = self::PROD_URL . "/{$v}/?aid={$activeId}&clientScreenMode=1";
        }
        if (array_get($config, 'vision_type') == 2) {
            $pageUrl .= '&lan=' . array_get($config, 'language', '');
        }
        return BbcTemplateConfig::edit($activeId, ['page_url' => $pageUrl]);
    }

    public function getInfoPageUrl($id): string
    {
        $config = BbcTemplateConfig::findOne($id, true);
        $visionType = $config['vision_type'] ?? '';

        if ($visionType == 2) {
            $v = 'rank-gift-template';
        } else if ($visionType == 0) {
            $v = 'rank-template';
        } else {
            $v = 'rank-template-v2';
        }
        if (ENV == 'dev') {
            $pageUrl = self::DEV_URL . "/{$v}/?aid={$id}&clientScreenMode=1";
        } else {
            $pageUrl = self::PROD_URL . "/{$v}/?aid={$id}&clientScreenMode=1";
        }
        if ($visionType == 2) {
            $pageUrl .= '&lan=' . $config['language'];
        }

        return $pageUrl;
    }

    public function formatVisionJson($json)
    {
        $config = json_decode($json, true);

        if (empty($config)) {
            return [];
        }

        $data = [];

        foreach ($config as $field => $value) {
            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * 发布前检验
     * @param int $id
     * @return array
     */
    public function publishCheck(int $id): array
    {
        $template = BbcTemplateConfig::findOne($id);
        if (empty($template)) {
            return [false, '发布活动不存在', []];
        }
        if (!$this->validAuditStatus($template['status'])) {
            return [false, '发布前审核状态必须为待发布(已生成)｜发布失败(请重试)', []];
        }
        if ($template['online_mode'] == 1) {
            return [false, '预上线不需要发布', []];
        }
        list($res, $type) = $this->verifyRelation($template, $id);
        if (!$res) {
            return [$res, $type, []];
        }
        $buttonTagList = BbcRankButtonTag::getListByWhere([
            ['act_id', '=', $id],
        ]);
        if (empty($buttonTagList)) {
            return [false, '缺少button_tag,不能发布', []];
        }

        if ($template['vision_type'] == BbcTemplateConfig::VISION_TYPE_FOUR
            && (count(BbcRankButtonList::getListByRankType($template['id'], BbcRankButtonList::RANK_TAG_SUB_PAY)) != 1)) {
            return [false, '当活动视觉=“多组礼物榜单活动视觉“时，要求整个活动的配置积分规则中“积分送礼榜”必须出现、且只出现一次。'];
        }

        // 检查礼物视觉下是否缺少礼物tag
        $rankObjects = array_column($buttonTagList, 'rank_object');
        if ($template['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE && !in_array(BbcRankButtonTag::RANK_OBJECT_WEEK_STAR, $rankObjects)) {
            return [false, '缺少礼物tag,不能发布', []];
        }
        if ($template['vision_type'] == BbcTemplateConfig::VISION_TYPE_FOUR && !in_array(BbcRankButtonTag::RANK_OBJECT_GIFT, $rankObjects)) {
            return [false, '缺少礼物tag,不能发布', []];
        }
        foreach ($buttonTagList as $buttonTag) {
            $buttonList = BbcRankButtonList::getListByWhere([
                ['button_tag_id', '=', $buttonTag['id']],
            ]);
            if (empty($buttonList)) {
                return [false, "button_tag :{$buttonTag['button_tag_type']}缺少button_list,不能发布", []];
            }
            if ($buttonTag['rank_object'] == 3) {
                $userList = BbcRankWhiteList::findOneByWhere([
                    ['button_tag_id', '=', $buttonTag['id']],
                ]);
                if (empty($userList)) {
                    return [false, "button_tag :{$buttonTag['button_tag_type']}缺少白名单用户,不能发布", []];
                }
            }
            foreach ($buttonList as $list) {
                if ($list['is_award'] && $list['rank_tag'] != BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT) {
                    $randAward = BbcRankAward::findOneByWhere([
                        ['button_list_id', '=', $list['id']],
                    ]);
                    if (empty($randAward)) {
                        return [false, "button_list 第:{$list['level']}轮,缺少奖励配置,不能发布", []];
                    }
                }
                if (in_array($list['rank_tag'], [4, 13, 14, 15])) {
                    $score = BbcRankScoreConfig::findOneByWhere([
                        ['button_list_id', '=', $list['id']],
                    ]);
                    if (empty($score)) {
                        return [false, "button_list 第:{$list['level']}轮,缺少积分配置,不能发布", []];
                    }
                }
                // 验证是否存在奖池
                if ($list['is_award'] == BbcRankButtonList::IS_AWARD_YES && $list['has_prize_pool'] == BbcRankButtonList::HAS_PRIZE_POOL_YES) {
                    $existsAwardPool = BbcRankAward::findOneByWhere([
                        ['award_type', '=', BbcRankAward::AWARD_TYPE_PRIZE_POOL],
                        ['button_list_id', '=', $list['id']],
                    ]);
                    if (empty($existsAwardPool)) {
                        return [false, '当榜单设置有奖池时，对应榜单中必须配置有"奖池"类型的奖励。'];
                    }
                    // 获取每个所有名次下的瓜分比例
                    $sum = BbcRankAward::getRankDiamondProportionSum($list['id']);
                    if ($sum > 100) {
                        return [false, '单个榜单下，所有名次的瓜分比例加起来不得超过100%。'];
                    }
                }
            }
        }
        $isDiamond = $this->isDiamondReward($id, $template['relate_id'], $type);
        $admin = Helper::getAdminName($template['admin_id']);

        return [
            true,
            '',
            [
                'is_diamond' => $isDiamond ? 1 : 0,
                'title'      => "你确定发布【{$admin}】创建的活动【{$template['title']}】吗？"
            ]
        ];
    }

    public function isDiamondReward(int $id, int $relateId, int $type): bool
    {
        // 判断榜单是否允许发放奖励且存在钻石类型奖励
        $buttonList = BbcRankButtonList::getListByWhere([
            ['act_id', '=', $id],
            ['is_award', '=', BbcRankButtonList::IS_AWARD_YES]
        ], 'id');

        $awardList = BbcRankAward::findOneByWhere([
            ['button_list_id', 'IN', Helper::arrayFilter($buttonList, 'id')],
            ['award_type', 'in', [BbcRankAward::AWARD_TYPE_DIAMOND, BbcRankAward::AWARD_TYPE_PRIZE_POOL, BbcRankAward::AWARD_TYPE_GAME_COUPON]],
        ]);

        if ($awardList) {
            return true;
        }

        if (empty($relateId)) {
            return false;
        }

        if ($type == BbcTemplateConfig::ACT_TEMPLATE_TYPE_WHEEL) {
            $relateAwardList = BbcActWheelLotteryReward::findOneByWhere([
                ['act_id', '=', $relateId],
            ]);
            $relateAwardList = @json_decode($relateAwardList['award_list'], true);
            $relateAwardType = Helper::arrayFilter($relateAwardList, 'type');
            foreach ($relateAwardType as $v) {
                if (in_array($v, [BbcActWheelLotteryReward::REWARD_TYPE_ACTIVITY_DIAMOND, BbcActWheelLotteryReward::REWARD_TYPE_GAME_COUPON])) {
                    return true;
                }
            }
            // 查看幸运玩法是否关联了其他任务玩法, 任务玩法奖励中是否存在钻石｜优惠券奖励
            $wheelotteryRelateTask = BbcTemplateConfig::findOne($relateId);
            if ($wheelotteryRelateTask && $wheelotteryRelateTask['has_relate'] == BbcTemplateConfig::HAS_RELATE_YES && $wheelotteryRelateTask['relate_id']) {
                return $this->checkTaskDiamondReward($wheelotteryRelateTask['relate_id']);
            }
        } else if (in_array($type, [BbcTemplateConfig::ACT_TEMPLATE_TYPE_TASK, BbcTemplateConfig::ACT_TEMPLATE_TYPE_MUTLI_TASK])) {
            return $this->checkTaskDiamondReward($relateId);
        }

        return false;
    }

    /**
     * 检查任务玩法是否存在钻石｜优惠券奖励
     * @param int $relateId
     * @return bool
     */
    public function checkTaskDiamondReward(int $relateId): bool
    {
        $awardList = BbcRankAward::findOneByWhere([
            ['act_id', '=', $relateId],
            ['award_type', 'in', [BbcRankAward::AWARD_TYPE_DIAMOND, BbcRankAward::AWARD_TYPE_GAME_COUPON]],
        ]);

        return (bool)$awardList;
    }

    public function publishOld(int $id, string $descPath): array
    {
        $data = [
            'desc_path'    => $descPath,
            'publisher_id' => Helper::getSystemUid(),
        ];
        $template = BbcTemplateConfig::findOne($id);
        if (empty($template)) {
            return [false, '发布失败, 当前活动不存在'];
        }
        if ($this->isDiamondReward($id, $template['relate_id'], $template['relate_type'])) {
            // 新增发布中状态
            $data['status'] = BbcTemplateConfig::STATUS_PUBLISH_HAVE;
            [$res, $msg] = BbcTemplateConfig::edit($id, $data);
            if (!$res) {
                return [$res, $msg];
            }
            self::updateRelateActivityStatus($template['relate_id'], BbcTemplateConfig::STATUS_PUBLISH_HAVE);
            $record = XsstActiveKingdeeRecord::findOneByWhere([
                ['business_id', '=', $id],
                ['type', '=', XsstActiveKingdeeRecord::TYPE_RANK],
                ['is_handle', '=', XsstActiveKingdeeRecord::WAIT_STATUS]
            ]);
            if (empty($record)) {
                XsstActiveKingdeeRecord::add([
                    'business_id' => $id,
                    'type'        => XsstActiveKingdeeRecord::TYPE_RANK,
                    'create_time' => time(),
                ]);
            }
            NsqClient::publish(NsqConstant::TOPIC_KING_ACTIVITY, [
                'cmd'  => 'submit_activity',
                'data' => ['id' => $id, 'type' => XsstActiveKingdeeRecord::TYPE_RANK],
            ]);

            // 延时推动，用来检测OA是否发起成功
            NsqClient::publish(NsqConstant::TOPIC_KING_ACTIVITY, [
                'cmd'  => 'check_status',
                'data' => ['id' => $id, 'type' => XsstActiveKingdeeRecord::TYPE_RANK],
            ], 300);

            return [true, ['relate_id' => $template['relate_id']]];
        }

        //不是钻石，直接更新状态
        $data['status'] = BbcTemplateConfig::STATUS_RELEASE;
        self::updateRelateActivityStatus($template['relate_id'], BbcTemplateConfig::STATUS_RELEASE);
        [$res, $msg] = BbcTemplateConfig::edit($id, $data);

        if (!$res) {
            return [$res, $msg];
        }

        return [$res, ['relate_id' => $template['relate_id']]];
    }

    public function checkActivityStatus($id): array
    {
        $act = BbcTemplateConfig::findOne($id);

        if (empty($act)) {
            return [false, '活动模版不存在'];
        }

        return [true, $act];
    }

    public function isGift($id, $type = 0)
    {
        //是否付费礼物
        $coinGift = XsGift::findFirst($id);
        if (!$coinGift) {
            return 1;
        }
        if ($type == 1) {
            if ($coinGift->gift_type == 'coin' || $coinGift->price <= 0) {
                return 2;
            }
        }
        return 0;
    }

    public function getButtonList($rec)
    {
        $conditions = [
            ['act_id', '=', $rec->id]
        ];

        if ($rec->vision_type == BbcTemplateConfig::VISION_TYPE_FOUR) {
            $conditions[] = ['rank_tag', '=', BbcRankButtonList::RANK_TAG_GIFT_GROUP];
        } else if ($rec->vision_type == BbcTemplateConfig::VISION_TYPE_THREE) {
            $conditions[] = ['rank_tag', '=', BbcRankButtonList::RANK_TAG_WEEK_STAR_GIFT];
        }
        return BbcRankButtonList::getListByWhere($conditions, 'id,button_tag_id,start_time,end_time,is_upgrade,level,rank_tag, cycle_days, room_support', 'button_tag_id asc,level asc');
    }

    public function getFamilyListExport($data, $extra = [])
    {
        if (empty($data)) {
            return [];
        }

        $listData = [];
        // 获取家族信息
        $fids = array_column($data, 'object_id');
        $family = XsFamily::getFamilyBatchChounk($fids, ['fid, uid, name']);
        $fidx = 1;

        $extendDatas = XsActRankAwardUserExtend::getByListAndExtend(
            Helper::arrayFilter($data, 'list_id'),
            Helper::arrayFilter($data, 'object_id'),
            XsActRankAwardUserExtend::EXTEND_TYPE_BR,
            array_column($data, 'cycle')
        );

        $uIds = array_column($family, 'uid');
        if ($extendDatas) {
            foreach ($extendDatas as $item) {
                $uIds = array_merge($uIds, array_column($item, 'object_id'));
            }
        }

        $userProfile = XsUserProfile::getUserProfileBatch($uIds, ['uid', 'name', 'sex']);
        $userBigArea = XsUserBigarea::getUserBigareasChunk($uIds);
        $allBigareas = XsBigarea::getAllNewBigArea();

        $existsKeys = [];
        foreach ($data as $item) {
            $uniqueKey = $item['list_id'] . '_' . $item['cycle'];
            //$extendData = XsActRankAwardUserExtend::getListByListIdAndExtendId($item['list_id'], $item['object_id'], 0);
            $extendData = $extendDatas[$item['list_id'] . '_' . $item['object_id'] . '_' . $item['cycle']] ?? [];
            if (empty($extendData)) {
                continue;
            }
            // 不同周期下，需要重置下key
            if (!in_array($uniqueKey, $existsKeys)) {
                $fidx = 1;
                $existsKeys[] = $uniqueKey;
            }
            // 获取家族信息
            $fname = $family[$item['object_id']]['name'] ?? '';
            $fuid = $family[$item['object_id']]['uid'] ?? 0;
            $funame = $userProfile[$fuid]['name'] ?? '';
            // 获取用户信息
            /*$uids = array_column($extendData, 'object_id');
            $uids[] = $fuid;
            // 获取用户大区
            $userBigArea = \XsUserBigarea::getUserBigareasChunk($uids);
            $userProfile = \XsUserProfile::getUserProfileBatch($uids, ['uid, name, sex']);*/
            $dayRound = '/';
            if (ButtonListService::isDaysAndCycleList($extra['tag_list_type'])) {
                $dayRound = date('Y-m-d', strtotime($extra['list_start_time']) + 86400 * ($item['cycle'] - 1) * $extra['cycle_days']);
                if ($extra['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE) {
                    $dayRound .= ' 至 ' . date('Y-m-d', strtotime($dayRound) + 86400 * ($extra['cycle_days'] - 1));
                }
            }
            $listData[] = [
                $extra['act_id'],
                $extra['act_title'],
                $extra['act_start_time'] . '-' . $extra['act_end_time'],
                $extra['act_type'],
                $extra['tag_list_type_name'],
                $extra['button_tag_type'],
                $item['list_id'],
                $dayRound,
                $extra['list_start_time'] . '-' . $extra['list_end_time'],
                $extra['level'],
                $allBigareas[$userBigArea[$fuid] ?? 0] ?? '',
                $item['object_id'],
                $fname,
                $fuid,
                $funame,
                '',
                '',
                '',
                $fidx,
                $item['score'],
            ];
            foreach ($extendData as $uidx => $v) {
                $rank = $fidx . '--' . ($uidx + 1);
                $uname = $userProfile[$v['object_id']]['name'] ?? '';
                $usex = $userProfile[$v['object_id']]['sex'] ?? 0;
                $listData[] = [
                    $extra['act_id'],
                    $extra['act_title'],
                    $extra['act_start_time'] . '-' . $extra['act_end_time'],
                    $extra['act_type'],
                    $extra['tag_list_type_name'],
                    $extra['button_tag_type'],
                    $v['list_id'],
                    $dayRound,
                    $extra['list_start_time'] . '-' . $extra['list_end_time'],
                    $extra['level'],
                    $allBigareas[$userBigArea[$v['object_id']] ?? 0] ?? '',
                    $v['extend_id'],
                    $fname,
                    $fuid,
                    $funame,
                    $v['object_id'],
                    $uname,
                    self::$sexMap[$usex],
                    $rank,
                    $v['score'],
                ];
            }
            $fidx++;
        }
        return $listData;
    }

    public function getCpListExport($data, $extra): array
    {
        if (empty($data)) {
            return [];
        }
        $listData = [];
        $objId = Helper::arrayFilter($data, 'object_id');
        $objArr = XsUserIntimateRelation::getObjBatchChounk($objId);
        $uids = [];
        foreach ($objArr as $val) {
            if (!in_array($val['objid_1'], $uids)) {
                $uids[] = $val['objid_1'];
            }
            if (!in_array($val['objid_2'], $uids)) {
                $uids[] = $val['objid_2'];
            }
        }
        $cycles = [];
        $userProfile = XsUserProfile::getUserProfileBatchChunk($uids, ['uid, name, sex']);
        $brokerUsers = XsBrokerUser::getBrokerUserBatchChounk($uids);
        $bids = Helper::arrayFilter($brokerUsers, 'bid');
        $brokers = XsBroker::getBrokerBatchChounk($bids);
        $index = 0;
        foreach ($data as $item) {
            // 不同日榜轮次时重置下排名
            if (!in_array($item['cycle'], $cycles)) {
                $index = 0;
                $cycles[] = $item['cycle'];
            }
            $dayRound = '/';
            if (ButtonListService::isDaysAndCycleList($extra['tag_list_type'])) {
                $dayRound = date('Y-m-d', strtotime($extra['list_start_time']) + 86400 * ($item['cycle'] - 1) * $extra['cycle_days']);
                if ($extra['tag_list_type'] == BbcRankButtonTag::TAG_LIST_TYPE_CYCLE) {
                    $dayRound .= ' 至 ' . date('Y-m-d', strtotime($dayRound) + 86400 * ($extra['cycle_days'] - 1));
                }
            }
            $uid1 = $objArr[$item['object_id']]['objid_1'] ?? '';
            $uid2 = $objArr[$item['object_id']]['objid_2'] ?? '';
            $uid1Sex = $userProfile[$uid1]['sex'] ?? 0;
            $uid2Sex = $userProfile[$uid2]['sex'] ?? 0;
            $uid1Bid = $brokerUsers[$uid1]['bid'] ?? '';
            $uid2Bid = $brokerUsers[$uid2]['bid'] ?? '';
            $listData[] = [
                $extra['act_id'],
                $extra['act_title'],
                $extra['act_start_time'] . '-' . $extra['act_end_time'],
                $extra['act_type'],
                $extra['tag_list_type_name'],
                $extra['button_tag_type'],
                $item['list_id'],
                $extra['list_type'],
                $dayRound,
                $extra['list_start_time'] . '-' . $extra['list_end_time'],
                $extra['level'],
                $uid1,
                self::$sexMap[$uid1Sex],
                $uid1Bid,
                $brokers[$uid1Bid]['bname'] ?? '',
                $uid2,
                self::$sexMap[$uid2Sex],
                $uid2Bid,
                $brokers[$uid2Bid]['bname'] ?? '',
                $index + 1,
                $item['score']
            ];
            $index++;
            unset($item);
        }

        return $listData;
    }
}