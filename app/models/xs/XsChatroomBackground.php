<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsChatroomBackground extends BaseModel
{
    protected static $primaryKey = 'id';

    const DELETED_NORMAL = 0;
    const DELETED_DELETE = 1;

    public static $deletedMap = [
        self::DELETED_NORMAL => '正常',
        self::DELETED_DELETE => '删除',
    ];

    /**
     * 根据type获取背景
     * @param string $type
     * @return array
     */
    public static function getInfoByType(string $type): array
    {
        return self::findOneByWhere([
            ['type', '=', $type],
            ['app_id', '=', APP_ID]
        ]);
    }

    /**
     * 获取背景图类型
     * @return array
     */
    public static function getBackgroundTypeMap(): array
    {
        $data = self::getListByWhere([
            ['app_id', '=', APP_ID],
            ['deleted', '=', self::DELETED_NORMAL],
            ['language', 'IN', Helper::getSystemUserLanguage()]
        ], 'type');

        return $data ? array_column($data, 'type', 'type') : [];
    }
}