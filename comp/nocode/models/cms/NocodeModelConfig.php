<?php

namespace Imee\Comp\Nocode\Models\Cms;

class NocodeModelConfig extends BaseModel
{
    public static $createTime = 'create_time';
    public static $updateTime = 'update_time';

    /**
     * 根据资源名称获取db and table
     * @param string $resource
     * @return array
     */
    public static function getInfoByName(string $name): array
    {
        return self::findOneByWhere([
            ['system_id', '=', SYSTEM_ID],
            ['name', '=', $name],
        ]);
    }

    /**
     * 根据资源名称获取资源列表（带分页）
     * @param string $resource
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getListByNameAndTotal(string $name, int $page = 0, int $limit = 0): array
    {
        $conditions = ['system_id', '=', SYSTEM_ID];

        $name && $conditions[] = ['name', '=', $name];

        return self::getListAndTotal($conditions, '*', 'id desc', $page, $limit);
    }
}