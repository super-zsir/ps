<?php

namespace Imee\Models\Xs;

class XsItemCardLog extends BaseModel
{
    public const SCHEMA_READ = 'xsserverslave';

    public static $sourceMap = [
        1 => '后台下发',
        2 => '用户赠送',
        3 => '活动下发',
        4 => 'VIP下发',
    ];

    public static $uploadFields = [
        'uid'         => 'uid',
        'resource_id' => '资源ID',
        'days'        => '装扮卡片天数',
        'period_days' => '资格使用有效天数',
        'num'         => '发放数量',
        'can_give'    => '是否可转赠(0 不可转赠 1 可转赠)',
        'remark'      => '备注',
    ];
}