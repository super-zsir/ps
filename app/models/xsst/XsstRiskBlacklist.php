<?php


namespace Imee\Models\Xsst;


class XsstRiskBlacklist extends BaseModel
{
    const STATUS_INVALID = 0;
    const STATUS_VALID = 1;
    public static $statusMapping = [
        self::STATUS_INVALID => '未生效',
        self::STATUS_VALID   => '生效',
    ];

    const TYPE_IP = 'ip';
    const TYPE_IP_SUBNET = 'ip_subnet';
    const TYPE_MAC = 'mac';
    const TYPE_CHANNEL = 'channel';
    const TYPE_SPECIAL_WORD = 'special_word';
    const TYPE_SIMULATOR = 'simulator';
    const TYPE_APP_VERSION = 'app_version';
    const TYPE_BACKUP_ONE = 'backup_1';
    const TYPE_BACKUP_TWO = 'backup_2';
    const TYPE_BACKUP_THREE = 'backup_3';
    const TYPE_BACKUP_FOUR = 'backup_4';
    const TYPE_BACKUP_FIVE = 'backup_5';
    public static $typeMapping = [
        self::TYPE_IP           => 'IP',
        self::TYPE_IP_SUBNET    => 'IP子网',
        self::TYPE_MAC          => 'MAC',
        self::TYPE_CHANNEL      => '渠道',
        self::TYPE_SPECIAL_WORD => '特殊词',
        self::TYPE_SIMULATOR    => '模拟器',
        self::TYPE_APP_VERSION  => 'app版本',
        self::TYPE_BACKUP_ONE   => '备选1',
        self::TYPE_BACKUP_TWO   => '备选2',
        self::TYPE_BACKUP_THREE => '备选3',
        self::TYPE_BACKUP_FOUR  => '备选4',
        self::TYPE_BACKUP_FIVE  => '备选5',
    ];

    const HANDLE_METHOD_FOREVER = 'forever';
    const HANDLE_METHOD_SMS = 'sms';
    public static $handleMethodMapping = [
        self::HANDLE_METHOD_FOREVER => '永久封禁',
        self::HANDLE_METHOD_SMS     => '短信认证',
    ];

}