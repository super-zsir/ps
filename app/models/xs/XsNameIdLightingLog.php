<?php

namespace Imee\Models\Xs;

class XsNameIdLightingLog extends BaseModel
{
    protected static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';

    const SOURCE_BACKEND = 1;
    const SOURCE_USER = 2;
    const SOURCE_ACTIVITY = 3;

    public static $sourceMap = [
        self::SOURCE_BACKEND  => '后台下发',
        self::SOURCE_USER     => '用户赠送',
        self::SOURCE_ACTIVITY => '活动下发',
    ];


    public static function uploadFields(): array
    {
        return [
            'uid'         => '用户UID',
            'group_id'    => '分组ID',
            'days'        => '装扮有效天数',
            'period_days' => '资格使用有效天数',
            'num'         => '发放数量',
            'can_give'    => '是否可转赠(0不可转赠，1可转赠)',
            'remark'      => '备注',
        ];
    }

}