<?php

namespace Imee\Service\Operate\Payactivity;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Config\BbcActWheelLotteryReward;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsChatroomMaterial;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xs\XsTopUpActivityReward;
use Imee\Models\Xs\XsTopUpActivityUser;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserVip;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstActiveKingdeeRecord;
use Imee\Models\Xsst\XsstTemplateAuditUser;
use Imee\Service\Helper;
use Imee\Service\Operate\Activity\ActivityService;
use Imee\Service\StatusService;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;

class PayActivityPeriodManageService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsTopUpActivity::getList($conditions, '*',  $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        $adminList = CmsUser::getUserNameList(array_merge(Helper::arrayFilter($list['data'], 'admin_id'), Helper::arrayFilter($list['data'], 'publisher_id')));
        $now = time();
        $link = ENV == 'dev' ? XsTopUpActivity::DEV_LINK : XsTopUpActivity::LINK;
        $bigAreaList = XsBigarea::getAllNewBigArea();
        foreach ($list['data'] as &$item) {
            $item['is_diamond'] = $this->isDiamondOrGameCouponAward($item['id']);
            $item['bigarea_id'] = $bigAreaList[$item['bigarea_id']] ?? '';
            $item['time_offset'] = $item['offset'] = $item['time_offset'] / 10 ?: 8;
            $item['time_offset'] = 'UTC :' . ($item['time_offset'] >= 0 ? '+' : '') . $item['time_offset'];
            $item['link'] = sprintf($link, $item['id']);
            $item['link'] = [
                'title'        => $item['link'],
                'value'        => $item['link'],
                'type'         => 'url',
                'url'          => $item['link'],
                'resourceType' => 'static'
            ];
            $admin = $adminList[$item['admin_id']] ?? '';
            $publisher = $adminList[$item['publisher_id']] ?? '';
            $item['tips'] = "你确定发布【{$admin}】创建的活动【{$item['title']}】吗？";
            $item['admin_name'] = $item['admin_id'] . '-' . $admin;
            $item['publisher'] = $item['publisher_id'] > 0 ? $item['publisher_id'] . '-' . $publisher : '';
            $item['is_pub'] = $this->setIsPublish($item['status']);
            $startTime = $this->setTimeOffset($item['offset'], $item['start_time'], 2);
            $endTime = $this->setTimeOffset($item['offset'], $item['end_time'], 2);
            $item['audit_status'] = $this->formatAuditStatus($item['status']);
            $item['audit_status_text'] = $this->formatAuditStatusText($item['audit_status']);
            $item['status'] = $this->formatStatus($item['status'], $item['start_time'], $item['end_time'], $now);
            $item['status_text'] = $this->formatStatusColor($item['status']);
            $item['start_end'] = Helper::now($startTime) . ' - ' . Helper::now($endTime);
            $item['cycle_type'] = XsTopUpActivity::$cycleTypeMap[$item['cycle_type']];
            $item['recharge_channels'] = $this->formatRechargeChannels($item['recharge_channels']);
        }

        return $list;
    }

    private function formatRechargeChannels($rechargeChannels)
    {
        if (empty($rechargeChannels)) {
            return '';
        }
        $str = '';
        foreach (explode(',', $rechargeChannels) as $channel) {
            $channelName = XsTopUpActivity::$channelMap[$channel] ?? '';
            $channelName && $str .= $channelName . ',';
        }

        return $str;
    }

    public function create(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $id) = XsTopUpActivity::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }
        $rewardList = $data['reward_list'];

        foreach ($rewardList as &$reward) {
            $reward['top_up_activity_id'] = $id;
        }
        
        list($rewardRes, $msg, $_) = XsTopUpActivityReward::addBatch($rewardList);
        if (!$rewardRes) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $templateInfo = XsTopUpActivity::findOne($params['id']);
        if (empty($templateInfo)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }
        $params['status'] = $templateInfo['status'];
        $data = $this->formatData($params);
        list($res, $msg) = XsTopUpActivity::edit($params['id'], $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        if (isset($data['reward_list']) && !empty($data['reward_list'])) {
            XsTopUpActivityReward::deleteByWhere([
                ['top_up_activity_id', '=', $params['id']],
            ]);
            list($rewardRes, $msg, $_) = XsTopUpActivityReward::addBatch($data['reward_list']);
            if (!$rewardRes) {
                throw new ApiException(ApiException::MSG_ERROR, $msg);
            }
        }

        return ['id' => $params['id'], 'after_json'=> $data];
    }

    public function info(int $id): array
    {
        $info = XsTopUpActivity::findOne($id);

        if (empty($info)) {
            return [];
        }

        $info['time_offset'] = $info['time_offset'] / 10;
        $info['comment'] = json_decode($info['comment'], true);
        $visionContentJson = json_decode($info['vision_content_json'], true);
        if ($visionContentJson) {
            foreach ($visionContentJson as $key => $value) {
                if (in_array($key, $this->getImageField())) {
                    $visionContentJson[$key . '_all'] = Helper::getHeadUrl($value);
                }
            }
        }
        $info['audit_status'] = $this->formatAuditStatus($info['status']);
        $info['start_time'] = $this->setTimeOffset($info['time_offset'], $info['start_time'], 2);
        $info['end_time'] = $this->setTimeOffset($info['time_offset'], $info['end_time'], 2);
        $info['status'] = $this->formatStatus($info['status'], $info['start_time'], $info['end_time'], time());
        if ($info['cycle_type'] == XsTopUpActivity::CYCLE_TYPE_ONE) {
            $info['start_time'] = Helper::now($info['start_time']);
            $info['end_time'] = Helper::now($info['end_time']);
        } else {
            $info['start_time'] = date('Y-m-d', $info['start_time']);
            $info['end_time'] = date('Y-m-d', $info['end_time']);
        }
        $info['recharge_channels'] = explode(',', $info['recharge_channels']);
        $info['recharge_channels'] = array_map('strval', $info['recharge_channels']);
        $info['cycle_type'] = strval($info['cycle_type']);
        $info['award_type'] = strval($info['award_type']);
        $info['bigarea_id'] = strval($info['bigarea_id']);
        $info['vision_content_json'] = $visionContentJson;
        $info['admin'] = Helper::getAdminName($info['admin_id']);
        // 保留字段默认为7
        $info['data_period'] = $info['data_period'] ?: 7;
        // 添加配置奖励
        $rewardList = XsTopUpActivityReward::getListByActivityId($info['id']);
        $info['level_award_list'] = $this->getRewardList($rewardList);
        return $info;
    }

    public function getRewardList(array $rewardList): array
    {
        if (empty($rewardList)) {
            return $rewardList;
        }

        foreach ($rewardList as $key => &$reward) {
            $reward['reward_level'] = $reward['reward_level'] ?: $key + 1;
            $reward['award_list']= $this->formatAwardList($reward['award_list']);
        }

        return $rewardList;
    }

    public function formatAwardList(string $awardList): array
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
                $tmp['has_days'] = (int)($award['exp_days'] ?? '');
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
                $tmp['give_type'] = strval($award['award_expand']['act_extend_type'] ?? 0);
                $tmp['num'] = intval($award['award_expand']['number'] ?? 0);
            } else if (in_array($award['type'], [
                XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID, XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING, XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD,
                XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD,
            ])) {
                $tmp['exp_days'] = $award['num'];
                $tmp['has_days'] = $award['exp_days'];
                $tmp['id'] = (string) $award['id'];
                $tmp['give_type'] = strval($award['award_expand']['act_extend_type'] ?? 0);
                $tmp['num'] = intval($award['award_expand']['number'] ?? 0);
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD) {
                $tmp['num'] = $award['num'];
                $tmp['exp_days'] = $award['exp_days'];
                $tmp['give_type'] = strval($award['award_expand']['act_extend_type'] ?? 0);
                $extendInfo = $award['award_expand']['room_bg_card_extend'] ?? [];
                $extendInfo && $tmp['card_type'] = strval($extendInfo['card_type'] ?? '');
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_CERTIFICATION) {
                $tmp['id'] = (string) $award['id'];
                $tmp['exp_days'] = $award['exp_days'];
                $tmp['content'] = $award['award_expand']['certification_content'] ?? '';
            }  else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_TOP_CARD) {
                $tmp['id'] = (string) $award['id'];
                $tmp['has_days'] = $award['exp_days'];
                $tmp['num'] = $award['num'];
            }   else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_SKIN) {
                $tmp['id'] = (string) $award['id'];
                $tmp['exp_days'] = $award['exp_days'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON) {
                $tmp['id'] = (string) $award['id'];
                $tmp['num'] = $award['num'];
                $tmp['exp_days'] = $award['exp_days'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_PROP_CARD) {
                $tmp['id'] = (string) $award['id'];
                $tmp['effective_hours'] = $award['exp_days'] ?? 0;
                $tmp['num'] = $award['num'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_OPEN_SCREEN_CARD) {
                $tmp['num'] = $award['num'];
                $tmp['give_type'] = strval($award['award_expand']['act_extend_type'] ?? 0);
                $tmp['effective_hours'] = $award['days'] ?? 0;
                $tmp['expire_time'] = $award['exp_days'] ? Helper::now($award['exp_days']) : 0;
                $extendInfo = $award['award_expand']['open_screen_card_extend'] ?? [];
                $extendInfo && $tmp['card_type'] = strval($extendInfo['card_type'] ?? '');
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD) {
                $tmp['num'] = $award['num'];
                $tmp['give_type'] = strval($award['award_expand']['act_extend_type'] ?? 0);
                $tmp['effective_days'] = $award['days'] ?? 0;
                $tmp['expire_time'] = $award['exp_days'] ? Helper::now($award['exp_days']) : 0;
            }
            $data[] = $tmp;
        }

        return $data;
    }

    private function getImageField()
    {
        return [
            'countdown_bgc_vc',
            'award_show_bgc_vc',
            'head_img_vc',
            'individual_bgc_vc',
            'recharge_button_vc',
            'rule_button_vc',
            'show_base_vc'
        ];
    }

    private function formatStatusColor($status): string
    {
        switch ($status) {
            case XsTopUpActivity::END_START:
                $color = 'grey';
                break;
            case XsTopUpActivity::HAVE_STATUS:
                $color = 'greedy';
                break;
            default:
                $color = 'red';
                break;
        }
        $status = XsTopUpActivity::$statusMap[$status];
        return "<font color='$color'>$status</font>";
    }

    protected function formatAuditStatusText(string $status): string
    {
        $color = 'red';
        if ($status == XsTopUpActivity::RELEASE_STATUS) {
            $color = 'green';
        }
        $status = XsTopUpActivity::$auditStatusMap[$status] ?? '';
        return "<font color='$color'>$status</font>";
    }

    private function isDiamondOrGameCouponAward(int $id): int
    {
        $isDiamond = 0;
        $awardList = XsTopUpActivityReward::getListByWhere([
            ['top_up_activity_id', '=', $id],
            ['status', '=', XsTopUpActivityReward::HAVE_STATUS]
        ]);

        if (empty($awardList)) {
            return $isDiamond;
        }

        foreach ($awardList as $award) {
            $awardConfig = json_decode($award['award_list'], true);
            $awardTypeArr = Helper::arrayFilter($awardConfig, 'type');
            //钻石和游戏优惠券  都需要发起OA
            if (in_array(XsTopUpActivityReward::AWARD_TYPE_DIAMOND, $awardTypeArr) ||
                in_array(XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON, $awardTypeArr)) {
                return 1;
            }
        }

        return $isDiamond;
    }

    public function copy(int $id): array
    {
        $info = XsTopUpActivity::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '复制失败, 当前活动不存在');
        }

        $now = time();
        $data = [
            'bigarea_id'          => $info['bigarea_id'],
            'start_time'          => $info['start_time'],
            'end_time'            => $info['end_time'],
            'introduction'        => $info['introduction'],
            'title'               => $info['title'],
            'comment'             => $info['comment'],
            'language'            => $info['language'],
            'modify_time'         => $now,
            'cycles'              => $info['cycles'],
            'cycle_type'          => $info['cycle_type'],
            'recharge_channels'   => $info['recharge_channels'],
            'time_offset'         => $info['time_offset'],
            'award_type'          => $info['award_type'],
            'vision_content_json' => $info['vision_content_json'],
            'admin_id'            => Helper::getSystemUid(),
            'publisher_id'        => 0,
        ];
        list($res, $aid) = XsTopUpActivity::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '活动复制失败，失败原因：' . $aid);
        }
        $adminInfo = Helper::getSystemUserInfo();
        // 添加配置奖励
        $rewardList = XsTopUpActivityReward::getListByActivityId($info['id']);
        if ($rewardList) {
            foreach ($rewardList as &$award) {
                unset($award['id']);
                $award['top_up_activity_id'] = $aid;
                $award['modify_time'] = $now;
                list($rec, $awardId) = XsTopUpActivityReward::add($award);
                if ($rec) {
                    OperateLog::addOperateLog([
                        'model'        => 'payactivityaward',
                        'model_id'     => $awardId,
                        'action'       => BmsOperateLog::ACTION_ADD,
                        'content'      => '活动周期复制',
                        'after_json'   => $award,
                        'operate_id'   => $adminInfo['user_id'],
                        'operate_name' => $adminInfo['user_name'],
                    ]);
                }
            }
        }

        return ['id' => $aid, 'after_json' => $data];
    }

    public function publish(array $params, bool $isDiamond = false): array
    {
        $id = $params['id'] ?? 0;
        $info = XsTopUpActivity::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '发布失败, 当前活动不存在');
        }
        if ($this->setIsPublish($info['status'])) {
            throw new ApiException(ApiException::MSG_ERROR, '发布前状态需为待发布|发布失败（请重试）｜已打回（需修改）');
        }

        $now = time();

        $data = [
            'desc_path'    => $params['desc_path'] ?? '',
            'publisher_id' => $params['admin_id'],
            'modify_time'  => $now
        ];

        // 存在钻石奖励时需要云之家审核
        // dev 直接跳过审批流程
        if ($isDiamond) {
            $data['status'] = XsTopUpActivity::STATUS_PUBLISH_HAVE;
            // 记录发布人
            list($res, $msg) = XsTopUpActivity::edit($id, $data);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, '发布人记录失败，失败原因：' . $msg);
            }
            $record = XsstActiveKingdeeRecord::findOneByWhere([
                ['business_id', '=', $id],
                ['is_handle', '=', XsstActiveKingdeeRecord::WAIT_STATUS],
                ['type', '=', XsstActiveKingdeeRecord::TYPE_RECHARGE]
            ]);
            if (empty($record)) {
                XsstActiveKingdeeRecord::add([
                    'business_id' => $id,
                    'type'        => XsstActiveKingdeeRecord::TYPE_RECHARGE,
                    'create_time' => $now,
                ]);
            }
            NsqClient::publish(NsqConstant::TOPIC_KING_ACTIVITY, [
                'cmd'  => 'submit_activity',
                'data' => ['id' => $id, 'type' => XsstActiveKingdeeRecord::TYPE_RECHARGE],
            ]);
            // 延时推动，用来检测OA是否发起成功
            NsqClient::publish(NsqConstant::TOPIC_KING_ACTIVITY, [
                'cmd'  => 'check_status',
                'data' => ['id' => $id, 'type' => XsstActiveKingdeeRecord::TYPE_RECHARGE],
            ], 300);

            return [];
        }

        $data['status'] = XsTopUpActivity::RELEASE_STATUS;

        list($res, $msg) = XsTopUpActivity::edit($id, $data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '发布失败，失败原因：' . $msg);
        }

        return ['before_json' => ['status' => XsTopUpActivity::WAIT_RELEASE_STATUS], 'after_json' => ['status' => $data['status']]];
    }

    public function delete(int $id): array
    {
        $info = XsTopUpActivity::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '删除失败, 当前活动不存在');
        }

        $now = time();
        list($res, $msg) = XsTopUpActivity::edit($id, [
            'status'      => XsTopUpActivity::DELETE_STATUS,
            'modify_time' => $now
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '删除失败, 失败原因：' . $msg);
        }

        // 关闭活动奖励配置
        XsTopUpActivityReward::updateByWhere([
            ['top_up_activity_id', '=', $id],
            ['status', '=', XsTopUpActivityReward::HAVE_STATUS],
        ], [
            'status'      => XsTopUpActivityReward::DELETE_STATUS,
            'modify_time' => $now
        ]);

        return ['after_json' => ['status' => XsTopUpActivityReward::DELETE_STATUS], 'before_json' => ['status' => $info['status']]];
    }

    private function formatData(array $params): array
    {
        $params['now'] = time();
        $this->verify($params);

        // 进行中和已结束状态只允许修改说明配置及引言
        $data = [
            'introduction' => $params['introduction'] ?? '',
            'modify_time'  => $params['now'],
            'data_period'  => $params['data_period'],
            'title'        => $params['title'],
            'end_time'     => $params['end_time'],
            'language'     => $params['language'],
        ];

        if (!isset($params['id'])) {
            $data['admin_id'] = $params['admin_id'];
        }

        isset($params['vision_content_json']) && $data['vision_content_json'] = json_encode($params['vision_content_json']);
        isset($params['comment']) && $data['comment'] = json_encode($params['comment']);
        // 待发布状态字段可全部编辑
        if (!$this->setIsPublish($params['status'])) {
            if ($params['status'] != XsTopUpActivity::DISMISS_STATUS) {
                $data['bigarea_id'] = $params['bigarea_id'];
                $data['start_time'] = $params['start_time'];
                $data['cycles'] = $params['cycles'] ?? 0;
                $data['cycle_type'] = $params['cycle_type'];
                $data['recharge_channels'] = implode(',', $params['recharge_channels']);
                $data['time_offset'] = $params['time_offset'] * 10;
                $data['award_type'] = $params['award_type'];
            }
            $data['reward_list'] = $params['reward_list'];
        }
        return $data;
    }

    private function verify(array &$params)
    {
        if ($params['cycle_type'] == XsTopUpActivity::CYCLE_TYPE_DAY_LOOP) {
            $params['start_time'] = substr($params['start_time'], 0, 10);
            $params['end_time'] = substr($params['end_time'], 0, 10);
            $params['cycles'] = (strtotime($params['end_time']) - strtotime($params['start_time'])) / 86400;
            $params['end_time'] = $params['end_time'] . ' 23:59:59';
        } else if ($params['cycle_type'] == XsTopUpActivity::CYCLE_TYPE_WEEK_LOOP) {
            $params['start_time'] = substr($params['start_time'], 0, 10);
            $params['end_time'] = strtotime($params['start_time']) + 7 * 86400 * $params['cycles'] - 1;
            $params['end_time'] = Helper::now($params['end_time']);
        }
        $params['start_time'] = $this->setTimeOffset($params['time_offset'], strtotime($params['start_time']));
        $params['end_time'] = $this->setTimeOffset($params['time_offset'], strtotime($params['end_time']));

        if ($params['start_time'] >= $params['end_time']) {
            throw new ApiException(ApiException::MSG_ERROR, '活动结束时间不得早于开始时间');
        }

        // 待发布、待开始状态需校验活动结束时间不能晚于当前时间
        if (in_array($params['status'], [XsTopUpActivity::WAIT_RELEASE_STATUS])) {
            if ($params['end_time'] < $params['now']) {
                throw new ApiException(ApiException::MSG_ERROR, '活动结束时间不得早于当前时间');
            }
        }

        $params['reward_list'] = $this->setAwardData($params);
    }

    public function setAwardData(array $params): array
    {
        $baseData = [
            'bigarea_id'         => (int) $params['bigarea_id'],
            'modify_time'        => time(),
            'status'             => XsTopUpActivityReward::HAVE_STATUS
        ];

        // 编辑直接记录下活动id
        !empty($params['id']) && $baseData['top_up_activity_id'] = $params['id'];
        $data = [];
        foreach ($params['level_award_list'] as $key => $list) {
            $data[] = array_merge($baseData, [
                'reward_level' => (int)$key + 1,
                'level'        => (int)$list['level'],
                'award_list'   => json_encode($this->setAwardList($list['award_list'], $list['level']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ]);
        }

        return $data;
    }

    private function setAwardList(array $awardList, int $level): array
    {
        $data = [];
        foreach ($awardList as $key => $award) {
            $index = $key + 1;
            $this->verifyAwardList($award, $index, $level);
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
                if (empty($commodity)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['id'] = (int)$award['id'];
                $tmp['name'] = $commodity['name'];
                $tmp['num'] = (int)$award['num'];
                $tmp['commodity_type'] = $commodity['type'];
                $tmp['icon'] = $commodity['image'];
                $tmp['exp_days'] = (int)($award['has_days'] ?? 0);
                $panelImage = @json_decode($commodity['extra'], true)['panel_image'] ?? '';
                $tmp['award_expand'] = ['panel_image' => $panelImage];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_MEDAL) {
                $medal = XsMedalResource::findOne($award['id']);
                if (empty($medal)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['id'] = (int)$award['id'];
                $tmp['exp_days'] = (int)$award['exp_days'];
                $tmp['name'] = @json_decode($medal['description_zh_tw'], true)['name'] ?? '';
                $tmp['icon'] = $medal['image_3'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND) {
                $background = XsChatroomBackgroundMall::findOneByWhere([
                    ['bg_id', '=', $award['id']],
                ]);
                if (empty($background)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $material = XsChatroomMaterial::findOneByWhere([
                    ['mid', '=', $background['mid']],
                    ['source', '=', 0]
                ]);
                if (empty($material)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
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
                $giveType = intval($award['give_type'] ?? 0);
                $num = intval($award['num'] ?? 0);

                // 直接生效时要重置num的值
                if ($giveType == BbcActWheelLotteryReward::GIVE_TYPE_AUTO_EFFECT) {
                    $num = 1;
                }
                $tmp['award_expand'] = ['act_extend_type' => $giveType, 'number' => $num];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID) {
                $pretty = XsCustomizePrettyStyle::findOne($award['id']);
                if (empty($pretty)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['num'] = (int) $award['exp_days'];
                $tmp['id'] = (int) $award['id'];
                $tmp['exp_days'] = (int) $award['has_days'];
                $tmp['award_expand'] = ['act_extend_type' => intval($award['give_type'] ?? 0), 'number' => intval($award['num'] ?? 0)];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD) {
                $tmp['num'] = (int) $award['num'];
                $tmp['exp_days'] = (int) $award['exp_days'];
                $tmp['award_expand'] = ['act_extend_type' => intval($award['give_type'] ?? 0)];
                $cardType = intval($award['card_type'] ?? -1);
                $cardType > -1 && $tmp['award_expand']['room_bg_card_extend'] = ['card_type' => $cardType];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_CERTIFICATION) {
                $certification = XsCertificationSign::findOne($award['id']);
                if (empty($certification)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['id'] = (int) $award['id'];
                $tmp['name'] = $certification['name'];
                $tmp['icon'] = $certification['icon'];
                $tmp['exp_days'] = (int) $award['exp_days'];
                $tmp['award_expand'] = ['certification_content' => $award['content'] ?? ''];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_SKIN) {
                $skin = XsRoomSkin::getInfo($award['id']);
                if (empty($skin)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['name'] = $skin['name'];
                $tmp['icon'] = $skin['img'];
                $tmp['id'] = (int) $award['id'];
                $tmp['exp_days'] = (int) $award['exp_days'];
            }  else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_ROOM_TOP_CARD) {
                $card = XsRoomTopCard::findOneByWhere([
                    ['is_delete', '=', XsRoomTopCard::DELETE_NO],
                    ['id','=', $award['id']]
                ]);
                if (empty($card)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['id'] = (int) $award['id'];
                $tmp['name'] = json_decode($card['name_json'], true)['cn'] ?? '';
                $tmp['icon'] = $card['icon'];
                $tmp['num'] = (int) $award['num'];
                $tmp['exp_days'] = (int) $award['has_days'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON) {

                if (empty($award['id'])) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的优惠券ID错误");
                }
                if (empty($award['num'])) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励数量错误");
                }
                if (empty($award['exp_days'])) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的有效期错误");
                }

                $tmp['id'] = (int)$award['id'];
                $tmp['num'] = (int)$award['num'];
                $tmp['exp_days'] = (int)$award['exp_days'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON) {
                $certification = XsCoupon::findOne($award['id']);
                if (empty($certification)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['id'] = (int)$award['id'];
                $tmp['exp_days'] = (int)$award['exp_days'];
                $tmp['num'] = (int)$award['num'];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING) {
                $nameIdLighting = XsNameIdLightingGroup::findOne($award['id']);
                if (empty($nameIdLighting)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['num'] = (int) $award['exp_days'];
                $tmp['id'] = (int) $award['id'];
                $tmp['exp_days'] = (int) $award['has_days'];
                $tmp['award_expand'] = ['act_extend_type' => intval($award['give_type'] ?? 0), 'number' => intval($award['num'] ?? 0)];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD) {
                $miniCard = XsItemCard::findOne($award['id']);
                if (empty($miniCard)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['num'] = (int) $award['exp_days'];
                $tmp['id'] = (int) $award['id'];
                $tmp['exp_days'] = (int) $award['has_days'];
                $tmp['award_expand'] = ['act_extend_type' => intval($award['give_type'] ?? 0), 'number' => intval($award['num'] ?? 0)];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_PROP_CARD) {
                $propCard = XsPropCard::findOne($award['id']);
                $propCardConfig = XsPropCardConfig::findOne( $propCard['prop_card_config_id'] ?? 0);
                $tmp['id'] = (int) $award['id'];
                $tmp['num'] = (int) $award['num'];
                $tmp['exp_days'] = (int) $award['effective_hours'];
                $tmp['award_expand'] = ['act_extend_type' => $propCardConfig['type'] ?? 0];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_OPEN_SCREEN_CARD) {
                $tmp['num'] = (int) $award['num'];
                $tmp['exp_days'] = $award['expire_time'] ? strtotime($award['expire_time']) : 0;
                $tmp['days'] = (int) $award['effective_hours'];
                $tmp['award_expand'] = ['act_extend_type' => intval($award['give_type'] ?? 0)];
                $cardType = intval($award['card_type'] ?? 0);
                $cardType && $tmp['award_expand']['open_screen_card_extend'] = ['card_type' => $cardType];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD) {
                $miniCard = XsItemCard::findOne($award['id']);
                if (empty($miniCard)) {
                    throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$index}id错误");
                }
                $tmp['num'] = (int) $award['exp_days'];
                $tmp['id'] = (int) $award['id'];
                $tmp['exp_days'] = (int) $award['has_days'];
                $tmp['award_expand'] = ['act_extend_type' => intval($award['give_type'] ?? 0), 'number' => intval($award['num'] ?? 0)];
            } else if ($award['type'] == XsTopUpActivityReward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD) {
                $tmp['num'] = (int) $award['num'];
                $tmp['exp_days'] = $award['expire_time'] ? strtotime($award['expire_time']) : 0;
                $tmp['days'] = (int) $award['effective_days'];
                $tmp['award_expand'] = ['act_extend_type' => intval($award['give_type'] ?? 0)];
            }

            $data[] = $tmp;
        }

        return $data;
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
        $giveType = intval($award['give_type'] ?? 0);
        $content = trim($award['content'] ?? '');
        $icon = $award['icon'] ?? '';
        $awardDesc = $award['award_desc'] ?? '';
        $effectiveHours = intval($award['effective_hours'] ?? 0);
        $expireTime = trim($award['expire_time'] ?? '');
        $effectiveDays = intval($award['effective_days'] ?? 0);
        if (!in_array($type, array_keys(XsTopUpActivityReward::$awardTypeMap))) {
            throw new ApiException(ApiException::MSG_ERROR, "门槛{$level}下的奖励{$key}类型错误");
        }
        switch ($type) {
            case XsTopUpActivityReward::AWARD_TYPE_DIAMOND:
                $this->verifyValue($level, $key, $num, '钻石面额', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_COMMODITY:
                $this->verifyValue($level, $key, $num, '份数', 1);
                $this->verifyValue($level, $key, $id, 'ID');
                $this->verifyValue($level, $key, $hasDays, '资格有效天数', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_MEDAL:
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_BACKGROUND:
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_SKIN:
                $this->verifyValue($level, $key, $id, 'ID');
                $this->verifyValue($level, $key, $expDays, '天数', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_VIP:
                $this->verifyValue($level, $key, $vipLevel, 'VIP等级');
                $this->verifyValue($level, $key, $vipDays, 'VIP天数');
                $giveType != BbcActWheelLotteryReward::GIVE_TYPE_AUTO_EFFECT && $this->verifyValue($level, $key, $num, '发放数量', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_EXP:
                break;
            case XsTopUpActivityReward::AWARD_TYPE_PRETTY_ID:
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_NAME_ID_LIGHTING:
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ITEM_CARD:
            case XsTopUpActivityReward::AWARD_TYPE_HOMEPAGE_CARD:
                $this->verifyValue($level, $key, $id, 'ID');
                $this->verifyValue($level, $key, $expDays, '天数', 1);
                $this->verifyValue($level, $key, $hasDays, '资格有效天数', 1, 365);
                $this->verifyValue($level, $key, $num, '发放数量', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_ACTIVITY_CUSTOMIZE:
                $this->verifyValue($level, $key, $icon, '图片');
                $this->verifyValue($level, $key, $awardDesc, '自定义描述');
                break;
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_BG_CARD:
                $this->verifyValue($level, $key, $expDays, '天数', 1);
                $this->verifyValue($level, $key, $num, '份数', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_CERTIFICATION:
                $this->verifyValue($level, $key, $expDays, '天数', 1);
                $this->verifyValue($level, $key, $content, '文案');
                $this->verifyValue($level, $key, $id, 'ID');
                break;
            case XsTopUpActivityReward::AWARD_TYPE_ROOM_TOP_CARD:
                $this->verifyValue($level, $key, $num, '份数', 1);
                $this->verifyValue($level, $key, $hasDays, '资格有效天数', 1);
                $this->verifyValue($level, $key, $id, 'ID');
                break;
            case XsTopUpActivityReward::AWARD_TYPE_TOP_UP_GAME_COUPON:
                $this->verifyValue($level, $key, $num, '份数', 1);
                $this->verifyValue($level, $key, $expDays, '天数', 1);
                $this->verifyValue($level, $key, $id, 'ID');
                break;
            case XsTopUpActivityReward::AWARD_TYPE_PROP_CARD:
                $this->verifyValue($level, $key, $id, 'ID');
                $this->verifyValue($level, $key, $num, '份数', 1);
                $this->verifyValue($level, $key, $effectiveHours, '有效小时', 1);
                break;
            case XsTopUpActivityReward::AWARD_TYPE_OPEN_SCREEN_CARD:
                $this->verifyValue($level, $key, $num, '份数', 1);
                $this->verifyValue($level, $key, $effectiveHours, '有效小时', 1);
                $this->verifyValue($level, $key, $expireTime, '过期时间');
                break;
            case XsTopUpActivityReward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD:
                $this->verifyValue($level, $key, $num, '份数', 1);
                $this->verifyValue($level, $key, $effectiveDays, '生效天数', 1);
                $this->verifyValue($level, $key, $expireTime, '过期时间');
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

    private function setTimeOffset($timeOffset, $time, $type = 1)
    {
        if ($type == 2) {
            return $time - (8 - $timeOffset) * 3600;
        }

        return $time + (8 - $timeOffset) * 3600;
    }

    public function getOptions()
    {
        $service = new StatusService();
        $language = $service->getLanguageNameMap(null, 'label,value');
        $bigArea = $service->getFamilyBigArea(null, 'label,value');
        $cycleType = StatusService::formatMap(XsTopUpActivity::$cycleTypeMap, 'label,value');
        $channel = StatusService::formatMap(XsTopUpActivity::$channelMap, 'label,value');
        $awardType = StatusService::formatMap(XsTopUpActivity::$awardTypeMap, 'label,value');
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
        $content = XsCertificationSign::getContentMap();
        $effectiveHours = ActivityService::getEffectiveHoursMap();
        $effectiveDays = ActivityService::getEffectiveDaysMap();
        return compact('language', 'cycleType', 'channel', 'timeOffset', 'awardType', 'bigArea', 'content', 'effectiveHours', 'effectiveDays');
    }

    public function formatStatus(int $status, int $startTime, int $endTime, int $now): string
    {
        if (in_array($status, [
            XsTopUpActivity::WAIT_RELEASE_STATUS, XsTopUpActivity::STATUS_PUBLISH_HAVE, XsTopUpActivity::STATUS_PUBLISH_ERROR]
        )) {
            return $status;
        }
        $value = -1;
        $time = time();
        if ($startTime >= $now) {
            // 开始时间大于当前时间为待开始状态
            $value = XsTopUpActivity::STATUS_WAIT_START;
        } else if ($startTime <= $time && $endTime >= $time) {
            // 开始时间小于当前时间并且结束时间大于当前时间状态为进行中
            $value = XsTopUpActivity::HAVE_STATUS;
        } else if ($endTime < $time) {
            // 结束时间小于当前时间状态为已结束
            $value = XsTopUpActivity::END_START;
        }

        return strval($value);
    }

    /**
     * 获取审核状态
     * @param int $status
     * @return string
     */
    protected function formatAuditStatus(int $status): string
    {
        return strval(in_array($status, array_keys(XsTopUpActivity::$auditStatusMap)) ? $status : -1);
    }

    public function getStatusMap(): array
    {
        $map = [];
        foreach (XsTopUpActivity::$statusMap as $s => $c) {
            $map[] = ['label' => $c, 'value' => $s + 1];
        }
        return $map;
    }

    public function getAuditStatusMap(): array
    {
        return StatusService::formatMap(XsTopUpActivity::$auditStatusMap);
    }

    private function getConditions(array $params): array
    {
        $conditions = ['status <> :del_status: and type = :type:'];
        $bind = ['del_status' => XsTopUpActivity::DELETE_STATUS, 'type' => XsTopUpActivity::TYPE_TOP_UP];
        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = "bigarea_id = :bigarea_id:";
            $bind['bigarea_id'] = $params['bigarea_id'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $status = intval($params['status']) - 1;
            $time = time();
            // 状态为未发布
            if (in_array($status, [
                XsTopUpActivity::WAIT_RELEASE_STATUS, XsTopUpActivity::STATUS_PUBLISH_HAVE, XsTopUpActivity::STATUS_PUBLISH_ERROR
            ])) {
                $conditions[] = 'status = :status:';
                $bind['status'] = $status;
            } else if ($status == XsTopUpActivity::STATUS_WAIT_START) {
                $conditions[] = 'start_time > :start_time: AND status NOT IN ({status:array})';
                $bind['start_time'] = $time;
                $bind['status'] = [
                    XsTopUpActivity::WAIT_RELEASE_STATUS,
                    XsTopUpActivity::STATUS_PUBLISH_HAVE,
                    XsTopUpActivity::STATUS_PUBLISH_ERROR,
                ];
            } else if ($status == XsTopUpActivity::HAVE_STATUS) {
                $conditions[] = 'start_time < :start_time: AND end_time >= :end_time: AND status NOT IN ({status:array})';
                $bind['start_time'] = $time;
                $bind['end_time'] = $time;
                $bind['status'] = [
                    XsTopUpActivity::WAIT_RELEASE_STATUS,
                    XsTopUpActivity::STATUS_PUBLISH_HAVE,
                    XsTopUpActivity::STATUS_PUBLISH_ERROR,
                ];
            } else if ($status == XsTopUpActivity::END_START) {
                $conditions[] = 'end_time < :end_time: AND status NOT IN ({status:array})';
                $bind['end_time'] = $time;
                $bind['status'] = [
                    XsTopUpActivity::WAIT_RELEASE_STATUS,
                    XsTopUpActivity::STATUS_PUBLISH_HAVE,
                    XsTopUpActivity::STATUS_PUBLISH_ERROR,
                ];
            }
        }

        if (!empty($params['audit_status'])) {
            $conditions[] = 'status = :audit_status:';
            $bind['audit_status'] = $params['audit_status'];
        }

        if (isset($params['create_name']) && !empty($params['create_name'])) {
            $conditions[] = 'admin_id = :admin_id:';
            $bind['admin_id'] = $params['create_name'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = 'id = :id:';
            $bind['id'] = $params['id'];
        }


        return compact('conditions', 'bind');
    }

    public function checkExport(array $params): array
    {
        if (empty($params['id'])) {
            throw new ApiException(ApiException::MSG_ERROR, '活动ID不能为空');
        }
        $activity = XsTopUpActivity::findOne($params['id']);
        if (empty($activity)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }

        $timeOffset = intval($activity['time_offset']) / 10;

        return [
            'act_id'      => $activity['id'],
            'act_name'    => $activity['title'],
            'cycle_type'  => $activity['cycle_type'],
            'start_time'  => $this->setTimeOffset($timeOffset, $activity['start_time'], 2),
            'end_time'    => $this->setTimeOffset($timeOffset, $activity['end_time'], 2),
        ];
    }

    public function getTopUpDataList(array $params): array
    {
        $record = XsTopUpActivityUser::getListAndTotal([
            ['top_up_activity_id', '=', $params['act_id']],
        ], 'uid, level, cycle', 'level desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($record['data'])) {
            return [];
        }
        $uids = Helper::arrayFilter($record['data'], 'uid');
        $userList = XsUserProfile::getUserProfileBatch($uids);
        $brokerUserList = XsBrokerUser::getBrokerUserBatch($uids);
        $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($brokerUserList, 'bid'));
        $userVipList = XsUserVip::getMaxLevelList($uids);
        $data = [];
        foreach ($record['data'] as $value) {
            $bid = $brokerUserList[$value['uid']]['bid'] ?? '';
            $bname = $brokerList[$bid]['bname'] ?? '';
            $data[] = [
                'act_id'     => $params['act_id'],
                'act_name'   => $params['act_name'],
                'time'       => Helper::now($params['start_time']) . '至' . Helper::now($params['end_time']),
                'cycle_time' => $this->getCycleTime($params['cycle_type'], $params['start_time'], $value['cycle']),
                'uid'        => $value['uid'],
                'user_name'  => $userList[$value['uid']]['name'] ?? '',
                'bid'        => $bid,
                'bname'      => $bname,
                'vip'        => $userVipList[$value['uid']] ?? '',
                'score'      => $value['level'],
            ];
        }

        return $data;
    }

    public function getCycleTime(int $cycleType, int $startTime, int $cycle): string
    {
        if ($cycleType == XsTopUpActivity::CYCLE_TYPE_ONE) {
            return '';
        }

        $cycleStartTime = $cycleEndTime = '';

        if ($cycleType == XsTopUpActivity::CYCLE_TYPE_DAY_LOOP) {
            $cycleStartTime = $startTime + ($cycle - 1) * 86400;
            $cycleEndTime = $cycleStartTime + 86399;
        } else if ($cycleType == XsTopUpActivity::CYCLE_TYPE_WEEK_LOOP) {
            $cycleStartTime = $startTime + ($cycle -1) * 7 * 86400;
            $cycleEndTime = $cycleStartTime + 7 * 86400 - 1;
        }

        return Helper::now($cycleStartTime) . '至'. Helper::now($cycleEndTime);
    }

    public function check(array $params): array
    {
        $id = $params['id'] ?? 0;
        $info = XsTopUpActivity::findOne($id);
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

    /**
     * 验证活动发布状态
     * @param int $status
     * @return bool
     */
    public static function validActivityPublishStatus(int $status): bool
    {
        return in_array($status, [XsTopUpActivity::STATUS_PUBLISH_HAVE, XsTopUpActivity::STATUS_PUBLISH_ERROR]);
    }

    /**
     * 设置活动发布状态
     * @param int $status
     * @return int
     */
    public function setIsPublish(int $status): int
    {
        return in_array($status, [XsTopUpActivity::WAIT_RELEASE_STATUS, XsTopUpActivity::DISMISS_STATUS, XsTopUpActivity::STATUS_PUBLISH_ERROR]) ? 0 : $status;
    }
}