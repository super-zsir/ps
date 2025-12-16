<?php

namespace Imee\Models\Xsst;

use Imee\Service\Helper;

class XsstChatroomAdminLog extends BaseModel
{
    public static $primaryKey = 'id';

    const AC_EDIT = 2;

    /**
     * 添加聊天室操作记录
     * @param int $rid
     * @param int $ac
     * @param array $data
     * @return array
     */
    public static function addRecord(int $rid, int $ac, array $data): array
    {
        $data = [
            'rid'       => $rid,
            'ac'        => $ac,
            'admin_uid' => Helper::getSystemUid(),
            'acdata'    => json_encode($data),
            'dateline'  => time()
        ];

        return self::add($data);
    }
}