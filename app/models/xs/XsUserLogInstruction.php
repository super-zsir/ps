<?php

namespace Imee\Models\Xs;

class XsUserLogInstruction extends BaseModel
{
    public static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';
    const LOG_TYPE_ONE = 1;
    const LOG_TYPE_TWO = 2;
    const LOG_TYPE_THREE = 3;
    const LOG_TYPE_FOUR = 4;
    const LOG_TYPE_FIVE = 5;


    public static $logTypeMap = [
        self::LOG_TYPE_ONE  => '所有的日志',
        self::LOG_TYPE_TWO  => '伴伴日志',
        //        self::LOG_TYPE_THREE => '声网日志',
        self::LOG_TYPE_FOUR => '即构日志',
        self::LOG_TYPE_FIVE => '原生日志',
    ];

    const STATUS_WAITING = 0;
    const STATUS_DONE = 1;
    const STATUS_FAIL = 2;

    public static $statusMap = [
        self::STATUS_WAITING => '等待中',
        self::STATUS_DONE    => '已上传',
        self::STATUS_FAIL    => '失败',
    ];

}