<?php

namespace Imee\Models\Xsst;

class XsstChatroomDefaultCover extends BaseModel
{
    protected static $primaryKey = 'id';

    /**
     * 获取房间封面枚举
     * @return array
     */
    public static function getOptions(): array
    {
        $list = self::getListByWhere([], 'icon, name', 'id desc');
        $map = [];

        foreach ($list as $item) {
            $map[$item['icon']] = $item['name'];
        }

        return $map;
    }
}