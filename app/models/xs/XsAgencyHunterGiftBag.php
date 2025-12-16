<?php

namespace Imee\Models\Xs;

class XsAgencyHunterGiftBag extends BaseModel
{
    const DELETED_YES = 1;
    const DELETED_NO = 0;

    const NO_AUDIT_STATUS = 0;
    const HAVE_AUDIT_STATUS = 1;
    const SUCCESS_AUDIT_STATUS = 2;
    const ERROR_AUDIT_STATUS = 3;

    const NO_EXISTS_COUPON = 0; // 不存在优惠券
    const EXISTS_COUPON = 1;    // 存在优惠券

    public static $displayStatus = [
        0 => '生效中',
        1 => '已删除',
        2 => '已过期',
        3 => '-'
    ];

    public static $auditStatus = [
        self::NO_AUDIT_STATUS      => '- (无需审核)',
        self::HAVE_AUDIT_STATUS    => '待审核',
        self::SUCCESS_AUDIT_STATUS => '审核成功',
        self::ERROR_AUDIT_STATUS   => '审核失败',
    ];

    const SEND_USER_TYPE__NONE = 0;
    const SEND_USER_TYPE__BATCH = 1;        // 批量发放
    const SEND_USER_TYPE__CONDITION = 2;    // 条件发放
    const SEND_USER_TYPE__BROKER_OWNER = 3; // 公会长
    const SEND_USER_TYPE__BROKER_ADMIN = 4; // 公会管理员

    public static $sendUserTypeMap = [
        self::SEND_USER_TYPE__BROKER_OWNER => '公会长',
        self::SEND_USER_TYPE__BROKER_ADMIN => '公会管理员',
    ];

    public static function displayStatus($info, $isCoupon)
    {
        if ($info['deleted'] == 1) {
            return 1;
        }

        // 礼包中存在优惠券审核状态为待审核/审核失败时状态为'-'
        if ($isCoupon && in_array($info['status'], [self::HAVE_AUDIT_STATUS, self::ERROR_AUDIT_STATUS])) {
            return 3;
        }

        if ($info['expire_time'] < time()) {
            return 2;
        }

        return 0;
    }
}
