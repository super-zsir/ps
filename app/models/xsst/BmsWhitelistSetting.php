<?php

namespace Imee\Models\Xsst;

class BmsWhitelistSetting extends BaseModel
{
    public static $primaryKey = 'id';

    public static $whiteListType = [
        'room' => '房间类白名单',
        'uid' => '用户类白名单',
        'rpool' => '分区房间白名单',
        'device' => '设备类白名单'
    ];

    public static $table = [
        'room' => 'config.bbc_room_whitelist, type=',
        'uid' => 'config.xsst_uid_white_list, type=',
        'rpool' => 'config.xsst_room_pool, room_list_type=',
        'device' => 'xsst.xsst_device_white_list, type=',
    ];

    /**
     * @desc 根据type获取白名单列表
     * @param string $type
     * @param int $uid
     * @return array
     */
    public static function getWhitelistByType($type, $uid)
    {
        if (!in_array($type, array_keys(self::$whiteListType))) {
            return [];
        }
        $conditions = [
            ['type', '=', $type],
            ['deleted', '=', 0]
        ];
        if ($uid > 0) {
            $conditions[] = ['uid', 'FIND_IN_SET', $uid];
        }
        $data = self::getListByWhere($conditions, 'name, value');
        if (!empty($data)) {
            $data = array_column($data, 'name', 'value');
        }
        return $data;
    }

    public static function getWhiteListValueByType($type)
    {
        if (!in_array($type, array_keys(self::$whiteListType))) {
            return [];
        }
        $conditions = [
            ['type', '=', $type],
            ['deleted', '=', 0]
        ];
        $data = self::getListByWhere($conditions, 'name, value');
        if (!empty($data)) {
            $data = array_column($data, 'name', 'value');
        }
        return $data;
    }
}