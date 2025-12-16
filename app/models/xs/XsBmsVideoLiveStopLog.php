<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsBmsVideoLiveStopLog extends BaseModel
{
    public static $primaryKey = 'id';
    const TYPE_VIDEO_LIVE = 1;
    const TYPE_ROOM = 2;

    /**
     * 添加视频直播中断日志
     * @param array $params
     * @param int $type
     * @return array
     */
    public static function addStopLog(array $params, int $type = 1): array
    {
        $now = time();
        $adminId = Helper::getSystemUid();
        $data = [
            'sid'         => intval($params['id']),
            'uid'         => intval($params['uid'] ?? $adminId),
            'rid'         => intval($params['rid']),
            'reason'      => intval($params['reason']),
            'remark'      => $params['remarks'] ?? '',
            'type'        => $type,
            'create_time' => $now,
            'update_time' => $now,
            'admin_uid'   => $adminId,
            'admin_name'  => Helper::getAdminName($adminId)
        ];
        return self::add($data);
    }
}