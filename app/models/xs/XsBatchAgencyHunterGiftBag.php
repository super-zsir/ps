<?php

namespace Imee\Models\Xs;

class XsBatchAgencyHunterGiftBag extends BaseModel
{
    const STATUS_AUDIT_NO = 0;
    const STATUS_AUDIT_WAIT = 1;
    const STATUS_AUDIT_PASS = 2;
    const STATUS_AUDIT_FAIL = 3;

    public static $statusMap = [
        self::STATUS_AUDIT_NO   => '无须审核',
        self::STATUS_AUDIT_WAIT => '审核中',
        self::STATUS_AUDIT_PASS => '审核通过',
        self::STATUS_AUDIT_FAIL => '审核拒绝'
    ];

    const TYPE_BATCH_SEND = 1;
    const TYPE_CONDITIONS_SEND = 2;
    const TYPE_BROKER_OWNER = 3;
    const TYPE_BROKER_ADMIN = 4;

    public static $typeMap = [
        self::TYPE_BATCH_SEND      => '批量发放',
        self::TYPE_CONDITIONS_SEND => '条件发放',
        self::TYPE_BROKER_OWNER    => '按人群发放-公会长',
        self::TYPE_BROKER_ADMIN    => '按人群发放-公会管理员'
    ];

    public static $taskStatusMap = [
        '0'   => '无需处理',
        '1' => '待处理',
        '2' => '处理成功',
        '3' => '处理失败'
    ];

}