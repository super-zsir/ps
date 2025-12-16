<?php

namespace Imee\Comp\Common\Export\Service;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;

class CmsUserService
{
    public static function getUserList($where = [], $limit = 20, $page = 1): array
    {
        $page <= 1 && $page = 1;
        $page < 1 && $page = 1;
        $conditions = [];
        $conditions[] = ['system_id', '=', CMS_USER_SYSTEM_ID];
        $conditions[] = ['user_status', '=', CmsUser::USER_STATUS_VALID];
        if (!empty($where['user_id'])) {
            $conditions[] = ['user_id', '=', $where['user_id']];
        }
        return CmsUser::getListAndTotal($conditions, '*', 'user_id asc', $page, $limit);
    }

    public static function isSuper($uid): bool
    {
        $info = CmsUser::findFirst([
            'user_id = :user_id:',
            'bind' => [
                'user_id' => (int)$uid,
            ]
        ]);
        if (!empty($info)) {
            return $info->super == 1;
        }

        return false;
    }
}