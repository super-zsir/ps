<?php

namespace Imee\Service\Operate\Payactivity;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;
use Imee\Models\Config\BbcActWheelLotteryReward;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xs\XsTopUpActivityReward;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Operate\Lighting\NameIdLightingLogService;
use Imee\Service\Operate\Minicard\MiniCardSendService;
use Imee\Service\StatusService;
use Imee\Models\Xs\XsPropCard;
use Imee\Service\Operate\User\OpenScreenCardService;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardSendService;
use Imee\Models\Xs\XsItemCard;

class PayActivityAwardService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsTopUpActivityReward::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        $ids = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('payactivityaward', $ids);
        $createLogs = BmsOperateLog::getFirstLogListByAction('payactivityaward', $ids, BmsOperateLog::ACTION_ADD);
        foreach ($list['data'] as &$item) {
            $item['model_id'] = $item['id'];
            $item['gift_bag_content'] = $this->formatAwardContext($item['award_list']);
            $item['created_time'] = isset($createLogs[$item['id']]['created_time']) ? Helper::now($createLogs[$item['id']]['created_time']) : '';
            $item['created_name'] = $createLogs[$item['id']]['operate_name'] ?? '';
            $item['updated_time'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
            $item['updated_name'] = $logs[$item['id']]['operate_name'] ?? '';
        }
        return $list;
    }

    public function create(array $params): void
    {
        $this->verify($params);
        $data = $this->setData($params);

        $admin = Helper::getAdminName($params['admin_uid']);
        foreach ($data as $item) {
            list($res, $msg) = XsTopUpActivityReward::add($item);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, '添加失败，失败原因：' . $msg);
            }
            OperateLog::addOperateLog([
                'model_id'     => $msg,
                'model'        => 'payactivityaward',
                'action'       => BmsOperateLog::ACTION_ADD,
                'content'      => '创建',
                'after_json'   => $item,
                'operate_id'   => $params['admin_uid'],
                'operate_name' => $admin,
            ]);
        }
    }

    public function modify(array $params): array
    {
        $this->verify($params);
        $data = $this->setData($params);
        $info = XsTopUpActivityReward::findOne($params['id']);
        list($res, $msg) = XsTopUpActivityReward::edit($params['id'], $data[0]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '修改失败，失败原因：' . $msg);
        }
        return ['id' => $params['id'], 'before_json' => $info, 'after_json' => $data];
    }

    public function delete(int $id): array
    {
        $info = XsTopUpActivityReward::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR,'当前奖励不存在');
        }
        if ($info['status'] != XsTopUpActivityReward::HAVE_STATUS) {
            throw new ApiException(ApiException::MSG_ERROR,'删除前状态必须为开启状态');
        }
        $this->verify(['top_up_activity_id' => $info['top_up_activity_id']]);

        if (!XsTopUpActivityReward::edit($id, ['status' => XsTopUpActivityReward::DELETE_STATUS])) {
            throw new ApiException(ApiException::MSG_ERROR, '删除失败');
        }

        return ['id' => $id, 'after_json' => ['status' => XsTopUpActivityReward::DELETE_STATUS], 'before_json' => ['status' => XsTopUpActivityReward::HAVE_STATUS]];
    }

    public function info(int $id): array
    {
        $info = XsTopUpActivityReward::findOne($id);
        if (empty($info)) {
            return [];
        }
        $info['bigarea_id'] = (string) $info['bigarea_id'];
        $info['level_award_list'] = [
            [
                'level' => $info['level'],
                'award_list' => $this->formatAwardList($info['award_list'])
            ]
        ];

        return $info;
    }

    public function getOptions()
    {
        $service = new StatusService();
        $awardType = StatusService::formatMap(XsTopUpActivityReward::$awardTypeMap, 'label,value');
        $language = $service->getLanguageNameMap(null, 'label,value');
        $area = $service->getFamilyBigArea(null, 'label,value');
        $commodity = $service->getCommodityMap(null, 'label,value');
        $medal = $service->getMedalMap(null, 'label,value');
        $reward = StatusService::formatMap(XsTopUpActivity::$awardTypeMap, 'label,value');
        $vip = $service->getVipMap(null, 'label,value');
        $vipDays = StatusService::formatMap(XsUserProfile::$vipDaysMap, 'label,value');
        $background = $service->getActivityBackgroundMap(null, 'label,value');
        $pretty = $service->getPrettyCardMap(null, 'label,value');
        $certification = $service->getCertificationMap(null, 'label,value');
        $roomTopCard = $service->getRoomTopCardMap(null,'label,value');
        $roomSkin = $service->getRoomSkinMap(null, 'label,value');
        $couponLists = StatusService::formatMap(XsCoupon::getCouponMap(), 'label,value');
        $nameIdLighting = NameIdLightingLogService::getGroupIdMap(null, 'label,value');
        $miniCard = (new MiniCardSendService())->getCardMap();
        $homepageCard = (new MiniCardSendService())->getCardMap(XsItemCard::TYPE_HOMEPAGE);
        $propCard = StatusService::formatMap(XsPropCard::getPkPropCardOptions());


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

        return [
            'awardType'                                               => $awardType,
            'area'                                                    => $area,
            'language'                                                => $language,
            'reward'                                                  => $reward,
            'vipDays'                                                 => $vipDays,
            'giveType'                                                => $giveType,
            'cardType'                                                => $cardType,
            XsTopUpActivityReward::AWARD_TYPE_COMMODITY               => $commodity,
            XsTopUpActivityReward::AWARD_TYPE_MEDAL                   => $medal,
            XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND         => $background,
            XsTopUpActivityReward::AWARD_TYPE_VIP                     => $vip,
            XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID               => $pretty,
            XsTopUpActivityReward::AWARD_TYPE_CERTIFICATION           => $certification,
            XsTopUpActivityReward::AWARD_TYPE_ROOM_TOP_CARD           => $roomTopCard,
            XsTopUpActivityReward::AWARD_TYPE_ROOM_SKIN               => $roomSkin,
            XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON      => $couponLists,
            XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING => $nameIdLighting,
            XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD        => $miniCard,
            XsTopUpActivityReward::AWARD_TYPE_PROP_CARD               => $propCard,
            XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD           => $homepageCard,
        ];
    }

    private function setData(array $params): array
    {
        $baseData = [
            'bigarea_id'         => (int) $params['bigarea_id'],
            'top_up_activity_id' => (int) $params['top_up_activity_id'],
            'modify_time'        => time(),
            'status'             => XsTopUpActivityReward::HAVE_STATUS
        ];

        $data = [];
        foreach ($params['level_award_list'] as $list) {
            $data[] = array_merge($baseData, [
                'level' => (int) $list['level'],
                'award_list' => json_encode($this->setAwardList($list['award_list'], $list['level']))
            ]);
        }

        return $data;
    }

    private function formatAwardList(string $awardList): array
    {
        $awardList = @json_decode($awardList, true);
        if (empty($awardList)) {
            return [];
        }
        $data = [];
        foreach ($awardList as $award) {
            $tmp = ['type' => (string) $award['type']];
            if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_DIAMOND) {
                $tmp['num'] = $award['num'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_COMMODITY) {
                $tmp['id'] = (string) $award['id'];
                $tmp['num'] = (int)$award['num'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_MEDAL) {
                $tmp['id'] = (string)$award['id'];
                $tmp['exp_days'] = (int)$award['exp_days'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND) {
                $tmp['id'] = (string) $award['id'];
                $tmp['exp_days'] = (int)$award['exp_days'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ACTIVITY_CUSTOMIZE) {
                $tmp['icon'] = $award['icon'];
                $tmp['icon_all'] = Helper::getHeadUrl($award['icon']);
                $tmp['award_desc'] = $award['award_expand']['award_desc'] ?? '';
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_VIP) {
                $tmp['vip_days'] = (string) $award['num'];
                $tmp['vip_level'] = (string) $award['id'];
            } else if (in_array($award['type'], [
                XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID, XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING, XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD,
                XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD
            ])) {
                $tmp['exp_days'] = $award['num'];
                $tmp['has_days'] = $award['exp_days'];
                $tmp['id'] = (string) $award['id'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD) {
                $tmp['num'] = $award['num'];
                $tmp['exp_days'] = $award['exp_days'];
            }
            $data[] = $tmp;
        }

        return $data;
    }

    private function setAwardList(array $awardList, int $level): array
    {
        $data = [];
        foreach ($awardList as $key => $award) {
            $this->verifyAwardList($award, $key + 1, $level);
            $tmp = ['type' => (int) $award['type']];
            if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_DIAMOND) {
                $tmp['icon'] = ENV == 'dev' ? XsTopUpActivityReward::DEV_DIAMOND_IMG : XsTopUpActivityReward::DIAMOND_IMG;
                $tmp['num'] = (int) $award['num'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_COMMODITY) {
                $commodity = XsCommodityAdmin::findOneByWhere([
                    ['ocid', '=', $award['id']],
                    ['state', '=', 1],
                    ['app_id', '=', APP_ID]
                ]);
                $tmp['id'] = (int)$award['id'];
                $tmp['name'] = $commodity['name'];
                $tmp['num'] = (int)$award['num'];
                $tmp['commodity_type'] = $commodity['type'];
                $tmp['icon'] = $commodity['image'];
                $panelImage = @json_decode($commodity['extra'], true)['panel_image'] ?? '';
                $tmp['award_expand'] = ['panel_image' => $panelImage];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_MEDAL) {
                $medal = XsMedalResource::findOne($award['id']);
                $tmp['id'] = (int)$award['id'];
                $tmp['exp_days'] = (int)$award['exp_days'];
                $tmp['name'] = @json_decode($medal['description_zh_tw'], true)['name'] ?? '';
                $tmp['icon'] = $medal['image_3'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND) {
                $background = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $award['id']]]);
                $tmp['id'] = (int)$award['id'];
                $tmp['name'] = $background['name'];
                $tmp['exp_days'] = (int)$award['exp_days'];
                $tmp['icon'] = $background['cover'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ACTIVITY_CUSTOMIZE) {
                $tmp['icon'] = $award['icon'] ?? '';
                $tmp['award_expand'] = ['award_desc' => $award['award_desc']];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_VIP) {
                $tmp['num'] = (int) $award['vip_days'];
                $tmp['id'] = (int) $award['vip_level'];
            } else if (in_array($award['type'], [
                XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID, XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING, XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD,
                XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD
            ])) {
                $tmp['num'] = (int) $award['exp_days'];
                $tmp['id'] = (int) $award['id'];
                $tmp['exp_days'] = (int) $award['has_days'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD) {
                $tmp['num'] = (int) $award['num'];
                $tmp['exp_days'] = (int) $award['exp_days'];
            }
            $data[] = $tmp;
        }

        return $data;
    }

    private function formatAwardContext(string $awardList)
    {
        $awardList = @json_decode($awardList, true);
        if (empty($awardList)) {
            return [];
        }
        $awardContext = '';
        foreach ($awardList as $award) {
            $id = $award['id'] ?? '';
            $icon = $award['icon'] ?? '';
            $img = Helper::getHeadUrl($icon);
            $awardContext .= XsTopUpActivityReward::$awardTypeMap[$award['type']];
            // 物品、勋章、房间背景需要拼接下ID
            if (in_array($award['type'],
                [
                    XsTopUpActivityReward::AWARD_TYPE_COMMODITY,
                    XsTopUpActivityReward::AWARD_TYPE_MEDAL,
                    XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND,
                    XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID,
                    XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING,
                    XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD,
                    XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD,
                ])) {
                $awardContext .= " ID【" . $id . "】";
            }
            // VIP、自选靓号、自定义背景卡不需要需要拼接图标
            if (!in_array($award['type'],
                [
                    XsTopUpActivityReward::AWARD_TYPE_VIP,
                    XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID,
                    XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD,
                    XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING,
                    XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD,
                    XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD,
                ])) {
                $awardContext .= " <img src='{$img}' width='30' height='30' />";
            }

            $awardContext .= "<br />";
        }

        return $awardContext;
    }

    private function verify(array $params)
    {
        $actInfo = XsTopUpActivity::findOne($params['top_up_activity_id']);

        if (empty($actInfo)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前活动不存在');
        }
        $status = (new PayActivityPeriodManageService())->formatStatus($actInfo['status'], $actInfo['start_time'], $actInfo['end_time'], time());
        if (!in_array($status, [XsTopUpActivity::WAIT_RELEASE_STATUS, XsTopUpActivity::DISMISS_STATUS])) {
            throw new ApiException(ApiException::MSG_ERROR, '当前活动已开始，不可在进行添加/编辑操作');
        }
    }

    private function verifyAwardList(array $award, int $key, int $level): void
    {
        $type = intval($award['type'] ?? 0);
        $num = intval($award['num'] ?? 0);
        $expDays = intval($award['exp_days'] ?? 0);
        $hasDays = intval($award['has_days'] ?? 0);
        $id = intval($award['id'] ?? 0);
        $vipLevel = intval($award['vip_level'] ?? 0);
        $vipDays = intval($award['vip_days'] ?? 0);
        $icon = $award['icon'] ?? '';
        $awardDesc = $award['award_desc'] ?? '';
        if (!in_array($type, array_keys(XsTopUpActivityReward::$awardTypeMap))) {
            throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$key}类型错误");
        }
        switch ($type) {
            case XsTopUpActivityReward::AWARD_TYPE_DIAMOND:
                $this->verifyValue($level, $key, $num, '份数', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_COMMODITY:
                $this->verifyValue($level, $key, $num, '份数', 1);
                $this->verifyValue($level, $key, $id, 'ID');
                break;
            case XsTopUpActivityReward::AWARD_TYPE_MEDAL:
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND:
                $this->verifyValue($level, $key, $id, 'ID');
                $this->verifyValue($level, $key, $expDays, '天数', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_VIP:
                $this->verifyValue($level, $key, $vipLevel, 'VIP等级');
                $this->verifyValue($level, $key, $vipDays, 'VIP天数');
                break;
            case XsTopUpActivityReward::AWARD_TYPE_EXP:
                break;
            case XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID:
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING:
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD:
            case XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD:
                $this->verifyValue($level, $key, $id, 'ID');
                $this->verifyValue($level, $key, $expDays, '天数', 1);
                $this->verifyValue($level, $key, $hasDays, '有效天数', 1, 365);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ACTIVITY_CUSTOMIZE:
                $this->verifyValue($level, $key, $icon, '图片');
                $this->verifyValue($level, $key, $awardDesc, '自定义描述');
                break;
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD:
                $this->verifyValue($level, $key, $expDays, '天数', 1);
                $this->verifyValue($level, $key, $num, '份数', 1);
                break;
        }
    }

    private function verifyValue($level, $key, $value, $msg, $min = 0, $max = 0)
    {
        if (empty($value)) {
            throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下奖励{$key}的{$msg}为必填项");
        }

        // 数字时校验
        if (is_numeric($value)) {
            if ($min && $value < $min) {
                throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下奖励{$key}的{$msg}最小值为$min");
            }

            if ($max && $value > $max) {
                throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下奖励{$key}的{$msg}最大值为$max");
            }
        }
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            ['status', '=', XsTopUpActivityReward::HAVE_STATUS]
        ];

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }

        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['top_up_activity_id', '=', $params['id']];
        }

        return $conditions;
    }
}