<?php

namespace Imee\Models\Xs;

class XsRoomSpecialEffectsConfig extends BaseModel
{
    const STICKER_TYPE = 1;

    public static function getIdAndNameMap(int $type): array
    {
        $list = self::getListByWhere([
            ['type', '=', $type]
        ], 'id, name', 'id desc');

        $map = [];
        foreach ($list as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }

        return $map;
    }
}