<?php

namespace Imee\Comp\Common\Log\Models\Xsst;

class XsstNoticeConfig extends BaseModel
{
    public static $primaryKey = 'id';

    const STATUS_VALID = 0;
    const STATUS_INVALID = 1;

    /**
     * 获取通知群使用情况
     * @param array $gids
     * @return array
     */
    public static function getGroupUseCount(array $gids): array
    {
        $list = self::getListByWhere([
            ['gid', 'IN', $gids]
        ], 'count(*) as count, gid', 'gid desc', 0, 0, 'gid');

        return $list ? array_column($list, 'count', 'gid') : [];
    }

    /**
     * 获取所有通知权限及对应id
     * @return array
     */
    public static function getActionMap(): array
    {
        $generatorList = self::getGeneratorListByWhere([[
            ['status', '=', self::STATUS_VALID]
        ]], 'id, action');

        if (empty($generatorList)) {
            return [];
        }

        $actionMap = [];
        foreach ($generatorList as $generator) {
            foreach ($generator as $item) {
                $actionArray = explode(',', $item['action']);
                foreach ($actionArray as $action) {
                    $actionMap[$action][] = $item['id'];
                }
            }
        }

        return $actionMap;
    }
}