<?php

namespace Imee\Models\Xs;

class XsActSendDiamondCheck extends BaseModel
{
    protected static $primaryKey = 'id';

    const SUCCESS_STATUS = 0;
    const ERROR_STATUS = 1;

    const ACT_TYPE_LIST = 0;
    const ACT_TYPE_TASK = 1;
    const ACT_TYPE_RECHARGE = 2;
    const ACT_TYPE_LUCKY_PLAY = 3;

    public static $statusMap = [
        self::SUCCESS_STATUS => '成功',
        self::ERROR_STATUS   => '失败',
    ];

    public static $actTypeMap = [
        self::ACT_TYPE_LIST       => '榜单活动',
        self::ACT_TYPE_TASK       => '任务玩法',
        self::ACT_TYPE_RECHARGE   => '充值活动',
        self::ACT_TYPE_LUCKY_PLAY => '幸运玩法',
    ];
}