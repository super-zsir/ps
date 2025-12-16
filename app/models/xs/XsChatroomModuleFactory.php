<?php

namespace Imee\Models\Xs;

class XsChatroomModuleFactory extends BaseModel
{

    /**
     * 获取模版枚举
     * @param array $conditions
     * @return array
     */
    public static function getOptions(array $conditions = []): array
    {
        $factoryList = self::getListByWhere($conditions, 'factory_name, factory_type');
        return $factoryList ? array_column($factoryList, 'factory_name', 'factory_type') : [];
    }
}