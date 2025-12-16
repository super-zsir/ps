<?php

namespace Imee\Models\Xs;

class XsUserVip extends \Imee\Models\Xs\BaseModel
{
    protected static $primaryKey = 'id';


    // todo 暂定vip等级
    public static $levelMap = [1, 2, 3, 4, 5, 6, 7, 8];

    /**
     * 获取用户VIP等级
     * @param int $uid
     * @return array
     */
    public static function getAllUserVipByUid(int $uid): array
    {
        $res = self::getListByWhere([
            ['level', 'IN', self::$levelMap],
            ['uid', '=', $uid]
        ], 'level, uid, vip_expire_time, rebate_expire_time');
        if ($res) {
            $res = array_column($res, null, 'level');
        }
        return $res;
    }

    public static function getMaxLevelList(array $uid): array
    {
        if (empty($uid)) {
            return [];
        }
        $res = self::getListByWhere([
            ['uid', 'IN', $uid],
            ['vip_expire_time', '>=', time()]
        ], 'uid, max(level) as level', 'uid desc', count($uid), 0,'uid');
        if ($res) {
            $res = array_column($res, 'level', 'uid');
        }
        return $res;
    }
}