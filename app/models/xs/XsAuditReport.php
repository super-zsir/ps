<?php

namespace Imee\Models\Xs;

class XsAuditReport extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATUS_WAIT = 0;
    const STATUS_PASS = 1;
    const STATUS_REJECT = 2;

    public static $statusMap = [
        self::STATUS_WAIT   => '等待审核',
        self::STATUS_PASS   => '审核通过',
        self::STATUS_REJECT => '审核拒绝',
    ];


    const REASON_TYPE_UNKNOWN = 0;
    const REASON_TYPE_CHICK = 1;
    const REASON_TYPE_POLICE = 2;
    const REASON_TYPE_RIGHT = 3;
    const REASON_TYPE_DIR = 4;
    const REASON_TYPE_PORN = 5;
    const REASON_TYPE_AD = 6;

    public static $reasonTypeMap = [
        self::REASON_TYPE_UNKNOWN => '未知类型',
        self::REASON_TYPE_CHICK   => '诈骗',
        self::REASON_TYPE_POLICE  => '政治',
        self::REASON_TYPE_RIGHT   => '侵权',
        self::REASON_TYPE_DIR     => '侮辱诋毁',
        self::REASON_TYPE_PORN    => '色情',
        self::REASON_TYPE_AD      => '广告',
    ];

    const REPORT_TYPE_UNKNOWN = 0;
    const REPORT_TYPE_VIDEO = 1;
    const REPORT_TYPE_VOICE = 2;
    const REPORT_TYPE_CHAT = 3;

    public static $reportTypeMap = [
        self::REPORT_TYPE_UNKNOWN => '未知类型',
        self::REPORT_TYPE_VIDEO   => '视频房',
        self::REPORT_TYPE_VOICE   => '语音房',
        self::REPORT_TYPE_CHAT    => '私聊',
    ];

    const BAN_TYPE_UNLOCK_USER = 0;
    const BAN_TYPE_DEFAULT = 1;
    const BAN_TYPE_NORMAL = 2;
    const BAN_TYPE_BANNED = 3;

    public static $banTypeMap = [
        self::BAN_TYPE_UNLOCK_USER => '正常',
        self::BAN_TYPE_DEFAULT     => '不可被搜索',
        self::BAN_TYPE_NORMAL      => '不可被搜索且不可进入聊天室',
        self::BAN_TYPE_BANNED      => '不可被搜索且禁止登录',
    ];

    const DURATION_UNKNOWN = 0;
    const DURATION_2_HOUR = 2;
    const DURATION_4_HOUR = 4;
    const DURATION_8_HOUR = 7;
    const DURATION_12_HOUR = 12;
    const DURATION_1_DAY = 24;
    const DURATION_3_DAY = 24 * 3;
    const DURATION_1_WEEK = 24 * 7;
    const DURATION_1_MONTH = 24 * 30;
    const DURATION_PERMANENT = 24 * 30 * 12 * 10;

    public static $durationMap = [
        self::DURATION_UNKNOWN   => '正常',
        self::DURATION_2_HOUR    => '2小时',
        self::DURATION_4_HOUR    => '4小时',
        self::DURATION_8_HOUR    => '8小时',
        self::DURATION_12_HOUR   => '12小时',
        self::DURATION_1_DAY     => '一天',
        self::DURATION_3_DAY     => '三天',
        self::DURATION_1_WEEK    => '一周',
        self::DURATION_1_MONTH   => '一个月',
        self::DURATION_PERMANENT => '永久',
    ];

    const IS_BAN_DEVICE_YES = 1;
    const IS_BAN_DEVICE_NO = 0;

    public static $isBanDeviceMap = [
        self::IS_BAN_DEVICE_NO  => '否',
        self::IS_BAN_DEVICE_YES => '是',
    ];

    const SYNC_TYPE_DEVICE = 0;
    const SYNC_TYPE_ALL_USER = 1;

    public static $syncTypeMap = [
        self::SYNC_TYPE_DEVICE   => '不同步安全手机号下的所有账号',
        self::SYNC_TYPE_ALL_USER => '同步安全手机号下的所有账号',
    ];

    const MESSAGE_TYPE_UNKNOWN = 0;
    const MESSAGE_TYPE_TEXT = 1;  // 文本
    const MESSAGE_TYPE_FILE = 2;  // 语音
}