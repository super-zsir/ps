<?php

namespace Imee\Models\Xs;

class XsTopUpActivity extends BaseModel
{
    protected static $primaryKey = 'id';

    const DEV_LINK = "https://dev.partystar.cloud/frontend/ps-resource/?aid=%d&clientScreenMode=1#/recharge-bonus";
    const LINK = "https://page.partystar.chat/ps-resource/?aid=%d&clientScreenMode=1#/recharge-bonus";

    const WAIT_RELEASE_STATUS = 0;
    const RELEASE_STATUS = 1;
    const AUDIT_STATUS = 2;
    const DISMISS_STATUS = 3;
    const HAVE_STATUS = 4;
    const END_START = 5;
    const DELETE_STATUS = 6;
    const STATUS_WAIT_START = 7;
    const STATUS_PUBLISH_HAVE = 8; // 发布中
    const STATUS_PUBLISH_ERROR = 9; // 发布失败（请重试）

    const CYCLE_TYPE_ONE = 0;
    const CYCLE_TYPE_DAY_LOOP = 1;
    const CYCLE_TYPE_WEEK_LOOP = 2;

    const CHANNEL_APPLE = 1;
    const CHANNEL_GOOGLE = 2;
    const COIN_MERCHANT_CHANNEL = 3;
    const THIRD_PARTY_CHANNEL = 4;
    const CASH_CHANNEL = 5;
    const CHARM_VALUE_CHANNEL = 6;
    const CHARM_VALUE_HUAWEI_IAP = 7;
    const CHARM_VALUE_SALARY_PREPAY = 8;

    const AWARD_TYPE_REACH = 0;
    const AWARD_TYPE_ACT_END = 1;

    public static $channelMap = [
        self::CHANNEL_APPLE             => 'apple',
        self::CHANNEL_GOOGLE            => 'google',
        self::COIN_MERCHANT_CHANNEL     => '通过币商充值',
        self::THIRD_PARTY_CHANNEL       => '第三方',
        self::CASH_CHANNEL              => '现金兑换钻石',
        self::CHARM_VALUE_CHANNEL       => '魅力值兑换钻石',
        self::CHARM_VALUE_HUAWEI_IAP    => '华为充值',
        self::CHARM_VALUE_SALARY_PREPAY => '公会提成薪资预支',
    ];

    public static $cycleTypeMap = [
        self::CYCLE_TYPE_ONE       => '一次性活动（不循环）',
        self::CYCLE_TYPE_DAY_LOOP  => '日循环活动',
        self::CYCLE_TYPE_WEEK_LOOP => '周循环活动'
    ];

    public static $statusMap = [
        self::WAIT_RELEASE_STATUS  => '未发布',
        self::STATUS_WAIT_START    => '待开始',
        self::HAVE_STATUS          => '进行中',
        self::END_START            => '已结束',
        self::STATUS_PUBLISH_HAVE  => '发布中',
        self::STATUS_PUBLISH_ERROR => '发布失败（请重试）',
    ];

    public static $auditStatusMap = [
        self::AUDIT_STATUS         => '审核中',
        self::DISMISS_STATUS       => '已打回（需修改）',
        self::RELEASE_STATUS       => '审核已通过',
    ];

    public static $awardTypeMap = [
        self::AWARD_TYPE_REACH   => '达到要求时立即下发（多档位可重复获得）',
        self::AWARD_TYPE_ACT_END => '活动结束时下发最终奖励（仅可获得最终档位）'
    ];

    const TYPE_TOP_UP = 0;  // 累充活动
    const TYPE_FIRST_RECHARGE = 1; // 首充活动

    public static function getList(array $conditions, $fields = '*', $page = 1, $pageSize = 15): array
    {
        $list = self::find([
            'conditions' => implode(' AND ', $conditions['conditions']),
            'bind'       => $conditions['bind'],
            'columns'    => $fields,
            'order'      => 'id desc',
            'limit'      => $pageSize,
            'offset'     => ($page - 1) * $pageSize
        ]);
        if (empty($list)) {
            return ['data' => [], 'total' => 0];
        }

        $total = self::count([
            'conditions' => implode(' AND ', $conditions['conditions']),
            'bind'       => $conditions['bind']
        ]);

        return ['data' => $list->toArray(), 'total' => $total];
    }
}