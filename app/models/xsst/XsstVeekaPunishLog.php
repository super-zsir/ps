<?php

namespace Imee\Models\Xsst;

class XsstVeekaPunishLog extends BaseModel
{
    const ACCOUNT_TYPE_CHARGE = 1;
    const ACCOUNT_TYPE_PLAY = 2;
    const ACCOUNT_TYPE_SEND = 3;
    const ACCOUNT_TYPE_PERSON = 4;
    const ACCOUNT_TYPE_FAMILY_DIAMOND = 5;
    const ACCOUNT_TYPE_FAMILY_CHARM = 6;

    const ACCOUNT_TYPE_MAP = [
        self::ACCOUNT_TYPE_CHARGE         => '充值金豆',
        self::ACCOUNT_TYPE_PLAY           => '玩法返钻',
        self::ACCOUNT_TYPE_SEND           => '发放金豆',
        self::ACCOUNT_TYPE_PERSON         => '个人魅力值',
        self::ACCOUNT_TYPE_FAMILY_DIAMOND => '家族钻',
        self::ACCOUNT_TYPE_FAMILY_CHARM   => '家族魅力值',
    ];

    const STATE_MAP = [
        1 => '待审核',
        2 => '审核通过',
        3 => '不通过',
        4 => '罚款退回',
    ];

    const OP_TYPE_MAP = [
        1 => '罚款',
        2 => '加钱',
        3 => '冻结',
    ];

    public static $importFields = [
        '用户id',
        '金豆/魅力值',
        '备注 (上传前删除表头)',
    ];
}
