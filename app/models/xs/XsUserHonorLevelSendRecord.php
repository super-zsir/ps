<?php

namespace Imee\Models\Xs;
class XsUserHonorLevelSendRecord extends BaseModel
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

    public static $statusMap = [
        self::STATUS_NORMAL => '生效中',
        self::STATUS_DELETE => '已失效',
    ];

    public static function uploadFields(): array
    {
        return [
            'uid'        => 'uid',
            'send_level' => '下发等级',
            'remark'     => '备注',
        ];
    }

}