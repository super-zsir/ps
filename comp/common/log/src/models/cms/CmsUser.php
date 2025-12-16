<?php

namespace Imee\Comp\Common\Log\Models\Cms;

class CmsUser extends BaseModel
{
    public static $primaryKey = 'user_id';

    /**
     * 获取用户名list
     * @param $adminIds
     * @return array
     */
    public static function getUserNameList($adminIds): array
    {
        if (!$adminIds) {
            return [];
        }
        $adminIds = array_filter($adminIds);
        $adminIds = array_unique($adminIds);
        $adminIds = array_values($adminIds);
        $list = self::getListByWhere([['user_id', 'in', $adminIds]], 'user_id,user_name');
        return array_column($list, 'user_name', 'user_id');
    }
}