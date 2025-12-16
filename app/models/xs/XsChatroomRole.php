<?php

namespace Imee\Models\Xs;

class XsChatroomRole extends BaseModel
{
    protected static $primaryKey = 'id';

    const ROLE_CREATOR = 'createor';
    const ROLE_ADMIN = 'admin';

    const ROLE_ADMIN_ONE = 'admin_1';
    const ROLE_ADMIN_TWO = 'admin_2';

    const WEIGHT_HIGH = 1;
    const WEIGHT_ORDINARY = 0;

    const HIGH_ADMIN_NUM = 1;

    const ROLE_ORDINARY = 1;
    const ROLE_HIGH = 2;
    const ROLE_CANCEL = 11;

    public static $weightMsgMap = [
        self::WEIGHT_HIGH  => '接待',
        self::WEIGHT_ORDINARY => '普通'
    ];

    /**
     * 根据rid和uid获取角色信息
     * @param int $rid
     * @param int $uid
     * @return array
     */
    public static function getInfoByRidAndUid(int $rid, int $uid): array
    {
        return self::findOneByWhere([
            ['uid', '=', $uid],
            ['rid', '=', $rid]
        ]);
    }

    /**
     * 获取房间高级管理员数量
     * @param int $rid
     * @return int
     */
    public static function getHighAdminCountByRid(int $rid): int
    {
        return self::getCount([
            ['rid', '=', $rid],
            ['weight', '=', self::WEIGHT_HIGH],
            ['role', '=', self::ROLE_ADMIN]
        ]);
    }
}