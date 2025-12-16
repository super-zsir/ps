<?php

namespace Imee\Models\Xs;

use Imee\Models\Xs\BaseModel;

class XsBanRoomLog extends BaseModel
{
    protected $primaryKey = 'id';

    const DELETED_FORBIDDEN = 1;
    const DELETED_REMOVE_FORBIDDEN = 2;

    public static $deletedMap = [
        self::DELETED_FORBIDDEN        => '封禁',
        self::DELETED_REMOVE_FORBIDDEN => '解禁',
    ];

    const DURATION_10_MINUTES = 600;
    const DURATION_30_MINUTES = 1800;
    const DURATION_60_MINUTES = 3600;
    const DURATION_120_MINUTES = 7200;
    const DURATION_240_MINUTES = 14400;
    const DURATION_480_MINUTES = 28800;
    const DURATION_720_MINUTES = 43200;
    const DURATION_1440_MINUTES = 86400;
    const DURATION_43200_MINUTES = 2592000;

    public static $durationMap = [
        self::DURATION_10_MINUTES    => '10分钟',
        self::DURATION_30_MINUTES    => '30分钟',
        self::DURATION_60_MINUTES    => '60分钟',
        self::DURATION_120_MINUTES   => '120分钟',
        self::DURATION_240_MINUTES   => '240分钟',
        self::DURATION_480_MINUTES   => '480分钟',
        self::DURATION_720_MINUTES   => '720分钟',
        self::DURATION_1440_MINUTES  => '1440分钟',
        self::DURATION_43200_MINUTES => '43200分钟',
    ];

    const REASON_DISORDER = 1;
    const REASON_POLITICS = 2;
    const REASON_ILLEGAL = 3;
    const REASON_HATEFUL = 4;
    const REASON_PORN = 5;
    const REASON_ADVERT = 6;


    public static $reasonMap = [
        self::REASON_DISORDER => '扰乱平台秩序',
        self::REASON_POLITICS => '时事政治',
        self::REASON_ILLEGAL  => '违法信息',
        self::REASON_HATEFUL  => '低俗恶心',
        self::REASON_PORN     => '淫秽色情',
        self::REASON_ADVERT   => '诈骗广告',
    ];

    public static $actionMap = [
        self::DELETED_FORBIDDEN        => 'forbidApi',
        self::DELETED_REMOVE_FORBIDDEN => 'unforbidApi',
    ];
}