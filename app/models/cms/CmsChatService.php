<?php

namespace Imee\Models\Cms;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;

class CmsChatService extends BaseModel
{
    /**
     * 获取可用的客服
     */
    public static function getEnableList(): array
    {
        $user = self::query()
            ->columns('user_id')
            ->execute()
            ->toArray();

        if (empty($user)) {
            return [];
        }

        $uids = array_column($user, 'user_id');

        return CmsUser::query()
            ->columns('user_id,user_name')
            ->where('user_status = 1')
            ->inWhere('user_id', $uids)
            ->execute()
            ->toArray();
    }
}
