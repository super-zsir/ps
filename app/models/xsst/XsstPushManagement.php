<?php

namespace Imee\Models\Xsst;

class XsstPushManagement extends BaseModel
{
    public static $primaryKey = 'id';

    protected $isReadWriteSeparation = true;

    const WAIT_STATUS = 0;   // 待发送
    const SEND_STATUS = 1;   // 已发送
    const REJECT_STATUS = 2; // 已拒绝
    const PLAN_STATUS = 3;   // 已计划
    const PLAN_RECALL = 4;   // 已撤回
    const STATUS_CREATE_USER = 5;   // 生成中
    const STATUS_CREATE_USER_ERROR = 6;   // 生成失败

    public static $statusAllMap = [
        self::WAIT_STATUS              => '待发送',
        self::SEND_STATUS              => '已发送',
        self::REJECT_STATUS            => '已拒绝',
        self::PLAN_STATUS              => '定时发送',
        self::PLAN_RECALL              => '已撤回',
        self::STATUS_CREATE_USER       => '生成名单中',
        self::STATUS_CREATE_USER_ERROR => '生成名单失败'
    ];

    public static $statusMap = [
        self::WAIT_STATUS              => '待发送',
        self::SEND_STATUS              => '已发送',
        self::REJECT_STATUS            => '已拒绝',
        self::PLAN_STATUS              => '定时发送',
    ];

    const NOT_ALL_STAFF = 0; // 指定用户
    const ALL_STAFF = 1;     // 全员

    const TEXT_TYPE = 'text';
    const PICTURE_TYPE = 'picture';
    const LINK_TYPE = 'link';

    public static $msgTypeMap = [
        self::TEXT_TYPE    => '文本',
        self::PICTURE_TYPE => '图文',
        self::LINK_TYPE    => '链接'
    ];

    public static $pushRange = [
        '0' => '指定用户',
//        '1' => '全员',
//		'2' => '建联预流失任务',
//        '3' => '超R用户联建任务'
        '4' => '指定条件'
    ];

    const ROLE_ANCHOR = 1;
    const ROLE_BROKER_MASTER = 2;
    const ROLE_BROKER_ADMIN = 3;
    const ROLE_HUNTER = 4;
    const ROLE_ALL = 5;
    const ROLE_OPERATION_MANAGER = 6;//运营负责人旗下主播
    const ROLE_COIN_USER = 7;    //币商


    public static $roleMap = [
        self::ROLE_ANCHOR            => '主播',
        self::ROLE_BROKER_MASTER     => '公会长',
        self::ROLE_BROKER_ADMIN      => '公会管理员',
        self::ROLE_HUNTER            => '挖猎bd',
        self::ROLE_ALL               => '所有人（含普通用户）',
    ];

    public static $roleAllMap = [
        self::ROLE_ANCHOR            => '主播',
        self::ROLE_BROKER_MASTER     => '公会长',
        self::ROLE_BROKER_ADMIN      => '公会管理员',
        self::ROLE_HUNTER            => '挖猎bd',
        self::ROLE_ALL               => '所有人（含普通用户）',
        self::ROLE_OPERATION_MANAGER => '运营负责人旗下主播',
        self::ROLE_COIN_USER       => '币商'
    ];

    public static $onlineTimeMap = [
        3  => '3天',
        7  => '7天',
        15 => '15天',
        30 => '30天',
        90 => '90天',
    ];

    public static $fromIdMap = [
        10000000 => '系统通知',
        10000050 => '活动通知',
    ];

    public static function uploadFields(): array
    {
        return [
            'uid' => 'uid',
        ];
    }
}