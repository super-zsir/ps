<?php

/**
 * 游戏热更新
 * BbcGameRenewal
 */

namespace Imee\Models\Config;

class BbcGameRenewal extends BaseModel
{
    protected static $primaryKey = 'id';

    protected $allowEmptyStringArr = [
        'remark', 'mop_uid', 'op_uid', 'status'
    ];

    const STATUS_VALID = 1;
    const STATUS_INVALID = 0;//未生效
}
