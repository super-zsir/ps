<?php

namespace Imee\Models\Xsst;

class XsstRewardSendTask extends BaseModel
{
    protected static $primaryKey = 'id';

    const IS_GIFT_COUPON_YES = 1;
    const IS_GIFT_COUPON_NO = 0;

    public static $isGiftCouponMap = [
        self::IS_GIFT_COUPON_YES => '是',
        self::IS_GIFT_COUPON_NO => '否',
    ];

    const IS_NOTICE_YES = 1;
    const IS_NOTICE_NO = 0;

    public static $isNoticeMap = [
        self::IS_NOTICE_YES => '是',
        self::IS_NOTICE_NO => '否',
    ];

    const AUDIT_STATUS_WAIT = 0;
    const AUDIT_STATUS_PASS = 1;
    const AUDIT_STATUS_FAIL = 2;

    public static $auditStatusMap = [
        self::AUDIT_STATUS_WAIT => '待审核',
        self::AUDIT_STATUS_PASS => '审核通过',
        self::AUDIT_STATUS_FAIL => '审核不通过',
    ];

    const TASK_STATUS_WAIT = 0;
    const TASK_STATUS_SENDING = 1;
    const TASK_STATUS_SUCCESS = 2;
    const TASK_STATUS_FAIL = 3;
    const TASK_STATUS_PART_SUCCESS = 4;


    public static $taskStatusMap = [
        self::TASK_STATUS_WAIT         => '待处理',
        self::TASK_STATUS_SENDING      => '发放中',
        self::TASK_STATUS_SUCCESS      => '发放成功',
        self::TASK_STATUS_FAIL         => '发放失败',
        self::TASK_STATUS_PART_SUCCESS => '部分成功',
    ];
}