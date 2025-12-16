<?php

namespace Imee\Service\Operate\Activity\Firstcharge;

use Imee\Exception\ApiException;
use Imee\Models\Config\BbcActWheelLotteryReward;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xs\XsTopUpActivityReward;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Operate\Lighting\NameIdLightingLogService;
use Imee\Service\Operate\Minicard\MiniCardSendService;
use Imee\Service\Operate\Payactivity\PayActivityPeriodManageService;
use Imee\Service\StatusService;
use Imee\Models\Xs\XsPropCard;
use Imee\Service\Operate\Activity\ActivityService;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardSendService;
use Imee\Service\Operate\User\OpenScreenCardService;

class FirstChargeActivityService
{

    const DOMAIN = 'https://page.waka.fit';
    const DOMAIN_DEV = 'https://dev.partystar.cloud/frontend';

    const LINK = '%s/first-recharge-template/?aid=%d&clientScreenMode=1&lan=%s';

    public function getList(): array
    {
        $bigAreaList = XsBigarea::getAllNewBigArea();
        $logs = BmsOperateLog::getFirstLogList('firstchargeactivityconfig', array_keys($bigAreaList));
        // 获取活动列表及奖励相关数据
        $activityList = XsTopUpActivity::getListByWhere([
            ['type', '=', XsTopUpActivity::TYPE_FIRST_RECHARGE]
        ]);
        $activityList = array_column($activityList, null, 'bigarea_id');
        $rewardList = XsTopUpActivityReward::getListByWhere([
            ['top_up_activity_id', 'IN', Helper::arrayFilter($activityList, 'id')]
        ], '*', 'top_up_activity_id asc, reward_level asc');

        $rewardData = [];
        foreach ($rewardList as $rewardItem) {
            $rewardData[$rewardItem['bigarea_id']][] = $rewardItem;
        }
        $data = [];

        $domain = ENV == 'dev' ? self::DOMAIN_DEV : self::DOMAIN;
        foreach ($bigAreaList as $bigAreaId => $bigAreaName) {
            $reward = $rewardData[$bigAreaId] ?? [0 => [], 1 => []];
            $activity = $activityList[$bigAreaId] ?? [];


            $tmp = [
                'id'                => $bigAreaId,
                'bigarea_id'        => (string)$bigAreaId,
                'recharge_channels' => $activity['recharge_channels'] ?? '',
                'level1'            => $reward[0]['level'] ?? '',
                'level2'            => $reward[1]['level'] ?? '',
                'admin_name'        => $logs[$bigAreaId]['operate_name'] ?? '-',
                'dateline'          => isset($logs[$bigAreaId]['created_time']) ? Helper::now($logs[$bigAreaId]['created_time']) : '',
            ];

            if ($activity) {
                $link = sprintf(self::LINK, $domain, $activity['id'], $activity['language']);
                $tmp['link'] = [
                    'title'        => $link,
                    'value'        => $link,
                    'type'         => 'url',
                    'url'          => $link,
                    'resourceType' => 'static'
                ];
            }

            $tmp['level1'] && $tmp['level1'] = '<' . $tmp['level1'];
            $tmp['level2'] && $tmp['level2'] = '>=' . $tmp['level2'];
            $tmp['recharge_channels'] && $tmp['recharge_channels'] = explode(',', $tmp['recharge_channels']);
            $data[] = $tmp;
        }

        return ['data' => $data, 'total' => count($data)];
    }

    public function modify(array $params): array
    {
        $update = [
            'bigarea_id'          => $params['id'],
            'language'            => $params['language'],
            'recharge_channels'   => implode(',', $params['recharge_channels']),
            'comment'             => $params['comment'] ?? '',
            'type'                => XsTopUpActivity::TYPE_FIRST_RECHARGE,
            'vision_content_json' => json_encode($params['vision_content_json'])
        ];

        $levelArr = array_column($params['level_award_list'], 'level');
        if (count(array_unique($levelArr)) > 1) {
            throw new ApiException(ApiException::MSG_ERROR, '档位1、档位2的两个数必须相等');
        }

        $activity = XsTopUpActivity::findOneByWhere([
            ['type', '=', XsTopUpActivity::TYPE_FIRST_RECHARGE],
            ['bigarea_id', '=', $params['id']]
        ]);

        if ($activity) {
            list($res, $msg) = XsTopUpActivity::edit($activity['id'], $update);
            $activityId = $activity['id'];
        } else {
            list($res, $msg) = XsTopUpActivity::add($update);
            $activityId = $msg;
        }
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '首充活动编辑失败，原因：' . $msg);
        }

        $params['id'] = $activityId;

        $rewardList = (new PayActivityPeriodManageService())->setAwardData($params);

        if ($rewardList) {
            XsTopUpActivityReward::deleteByWhere([
                ['top_up_activity_id', '=', $activityId],
            ]);
            list($rewardRes, $msg, $_) = XsTopUpActivityReward::addBatch($rewardList);
            if (!$rewardRes) {
                throw new ApiException(ApiException::MSG_ERROR, $msg);
            }
        }

        return ['id' => $update['bigarea_id'], 'after_json' => $params];
    }

    public function info(int $id): array
    {
        $data = [
            'id'                  => $id,
            'bigarea_id'          => (string)$id,
            'language'            => '',
            'recharge_channels'   => [],
            'vision_content_json' => [],
            'level_award_list'    => [['reward_level' => 1], ['reward_level' => 2]],
            'comment'             => '',
        ];

        $activity = XsTopUpActivity::findOneByWhere([
            ['type', '=', XsTopUpActivity::TYPE_FIRST_RECHARGE],
            ['bigarea_id', '=', $id]
        ]);
        if ($activity) {
            $activity['language'] && $data['language'] = $activity['language'];
            $activity['recharge_channels'] && $data['recharge_channels'] = explode(',', $activity['recharge_channels']);
            $activity['comment'] && $data['comment'] = $activity['comment'];
            if ($activity['vision_content_json']) {
                $visionContentJson = json_decode($activity['vision_content_json'], true);
                if ($visionContentJson) {
                    foreach ($visionContentJson as $key => $value) {
                        if (in_array($key, $this->getImageField())) {
                            $visionContentJson[$key . '_all'] = Helper::getHeadUrl($value);
                        }
                    }
                }
                $data['vision_content_json'] = $visionContentJson;
            }
            $rewardList = XsTopUpActivityReward::getListByWhere([
                ['top_up_activity_id', '=', $activity['id']],
                ['bigarea_id', '=', $id],
            ]);
            if ($rewardList) {
                $data['level_award_list'] = (new PayActivityPeriodManageService())->getRewardList($rewardList);
            }
        }

        return $data;
    }

    public function getImageField(): array
    {
        return [
            'head_img_vc', 'title_img_vc', 'module_bgx_vc', 'recharge_button_vc'
        ];
    }

    public function getOptions(): array
    {
        $service = new StatusService();
        $language = $service->getLanguageNameMap(null, 'label,value');
        $bigArea = $service->getFamilyBigArea(null, 'label,value');
        $channel = $service->getReChargeChannelMap(null, 'label,value');

        $awardTypeMap = XsTopUpActivityReward::$awardTypeMap;
        unset($awardTypeMap[XsTopUpActivityReward::AWARD_TYPE_DIAMOND],
            $awardTypeMap[XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ACTIVITY_CUSTOMIZE],
            $awardTypeMap[XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON]
        );
        $awardType = $service::formatMap($awardTypeMap, 'label,value');

        $commodity = $service->getCommodityMap(null, 'label,value');
        $medal = $service->getMedalMap(null, 'label,value');
        $vip = $service->getVipMap(null, 'label,value');
        $vipDays = $service::formatMap(XsUserProfile::$vipDaysMap, 'label,value');
        $background = $service->getActivityBackgroundMap(null, 'label,value');
        $pretty = $service->getPrettyCardMap(null, 'label,value');
        $certification = $service->getCertificationMap(null, 'label,value');
        $roomTopCard = $service->getRoomTopCardMap(null, 'label,value');
        $roomSkin = $service->getRoomSkinMap(null, 'label,value');
        $xsCoupon = StatusService::formatMap(XsCoupon::getCouponMap(), 'label,value');
        $nameIdLighting = NameIdLightingLogService::getGroupIdMap(null, 'label,value');
        $miniCard = (new MiniCardSendService())->getCardMap();
        $homepageCard = (new MiniCardSendService())->getCardMap(XsItemCard::TYPE_HOMEPAGE);
        $propCard = StatusService::formatMap(XsPropCard::getPkPropCardOptions());
        $effectiveHours = ActivityService::getEffectiveHoursMap();
        $effectiveDays = ActivityService::getEffectiveDaysMap();
        $giveType = [
            XsTopUpActivityReward::AWARD_TYPE_VIP                      => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeVipMap, 'label,value'),
            XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD             => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING  => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD         => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD            => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID                => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypePrettuMap, 'label,value'),
            XsTopUpActivityReward::AWARD_TYPE_OPEN_SCREEN_CARD         => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
            XsTopUpActivityReward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD => StatusService::formatMap(BbcActWheelLotteryReward::$giveTypeBgcCardMap, 'label,value'),
        ];
        $cardType = [
            XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD => (new CustomBgcCardSendService())->getCardTypeMap(),
            XsTopUpActivityReward::AWARD_TYPE_OPEN_SCREEN_CARD => (new OpenScreenCardService())->getTypeMap(),
        ];
        $content = XsCertificationSign::getContentMap();

        return [
            'awardType'                                               => $awardType,
            'bigArea'                                                 => $bigArea,
            'language'                                                => $language,
            'channel'                                                 => $channel,
            'vipDays'                                                 => $vipDays,
            'giveType'                                                => $giveType,
            'content'                                                 => $content,
            'effectiveHours'                                          => $effectiveHours,
            'effectiveDays'                                           => $effectiveDays,
            'cardType'                                                => $cardType,
            XsTopUpActivityReward::AWARD_TYPE_COMMODITY               => $commodity,
            XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON      => $xsCoupon,
            XsTopUpActivityReward::AWARD_TYPE_MEDAL                   => $medal,
            XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND         => $background,
            XsTopUpActivityReward::AWARD_TYPE_VIP                     => $vip,
            XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID               => $pretty,
            XsTopUpActivityReward::AWARD_TYPE_CERTIFICATION           => $certification,
            XsTopUpActivityReward::AWARD_TYPE_ROOM_TOP_CARD           => $roomTopCard,
            XsTopUpActivityReward::AWARD_TYPE_ROOM_SKIN               => $roomSkin,
            XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING => $nameIdLighting,
            XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD        => $miniCard,
            XsTopUpActivityReward::AWARD_TYPE_PROP_CARD               => $propCard,
            XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD           => $homepageCard,
        ];
    }
}