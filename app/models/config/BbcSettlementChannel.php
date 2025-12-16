<?php

namespace Imee\Models\Config;

class BbcSettlementChannel extends BaseModel
{
    protected static $primaryKey = 'id';

    /**
     * 获取频道枚举
     * @return array
     */
    public static function getOptions(): array
    {
        $channelList = self::findAll();

        return $channelList ? array_column($channelList, 'name', 'type') : [];
    }
}
