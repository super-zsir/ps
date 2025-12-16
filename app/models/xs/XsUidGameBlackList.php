<?php

namespace Imee\Models\Xs;

class XsUidGameBlackList extends BaseModel
{
    protected static $primaryKey = 'id';

    const GREEDY_BLACKLIST = 1;
    const SIC_BO_BLACKLIST = 2;
    const SLOT_BLACKLIST = 3;
    const LUCKY_BLACKLIST = 4;
    const DRAGON_BLACKLIST = 5;
    const HORSE_RACE_BLACKLIST = 6;
    const LUCKY_FRUIT = 7;
    const ROCKET_CRASH = 8;
    const TAROT = 9;
    const TEEN_PATTI = 10;
    const GREEDY_SLOT = 11;
    const GREEDY_BOX = 12;
    const FISHING = 13;
    const SWEET_CANDY = 14;
    const GREEDY_BRUTAL = 15;

    const SOURCE_ADMIN = 1;
    const SOURCE_USER = 2;
    const SOURCE_BROKER = 3;


    public static $blacklistNameMap = [
        self::GREEDY_BLACKLIST     => 'greedy',
        self::SLOT_BLACKLIST       => 'slot',
        self::SIC_BO_BLACKLIST     => 'sic_bo',
        self::DRAGON_BLACKLIST     => 'dragon',
        self::HORSE_RACE_BLACKLIST => 'horse_race',
//        self::LUCKY_BLACKLIST      => 'lucky',
        self::LUCKY_FRUIT          => 'lucky_fruit',
        self::ROCKET_CRASH         => 'crash',
        self::TAROT                => 'tarot',
        self::TEEN_PATTI           => 'teenpatti',
        self::GREEDY_SLOT          => 'greedy_slot',
        self::GREEDY_BOX           => 'greedy_box',
        self::FISHING              => 'fishing',
        self::SWEET_CANDY          => 'sweet_candy',
        self::GREEDY_BRUTAL        => 'greedy_brutal',
    ];

    const HAVE_STATUS = 1;      // 生效中
    const CANCEL_STATUS = 2;    // 已取消
    const DELETE_STATUS = 3;    // 已删除
    const AUDIT_STATUS = 4;    // 待审核
    const LOSE_STATUS = 40;      // 已失效
    const WAIT_STATUS = 50;      // 未生效

    public static $statusMap = [
        self::WAIT_STATUS   => '未生效',
        self::HAVE_STATUS   => '生效中',
        self::LOSE_STATUS   => '已失效',
        self::CANCEL_STATUS => '已取消',
        self::AUDIT_STATUS  => '待审核',
        self::DELETE_STATUS => '已删除',
    ];

    const FOREVER_TIME_TYPE = 1;
    const EFFECT_TIME_TYPE = 2;
    const CANCEL_TIME_TYPE = 3;

    public static $displayType = [
        1 => '隐藏',
        2 => '展示',
    ];

    const AUDIT_AGREE = 1;
    const AUDIT_REFUSE = 2;

    public static $auditStatusMap = [
        self::AUDIT_AGREE  => '通过',
        self::AUDIT_REFUSE => '拒绝',
    ];

    public static $sourceMap = [
        self::SOURCE_ADMIN  => '运营后台',
        self::SOURCE_USER   => '主播',
        self::SOURCE_BROKER => '公会',
    ];

    public static $logType = [
        1 => '修改',
        2 => '取消',
        3 => '隐藏审批通过',
        4 => '隐藏审批拒绝',
        5 => '展示审批通过',
        6 => '展示审批拒绝',
    ];

}