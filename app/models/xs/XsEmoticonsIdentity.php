<?php

namespace Imee\Models\Xs;

class XsEmoticonsIdentity extends BaseModel
{
    protected static $primaryKey = 'id';

    public static function getListByEmoticonsId(int $emoticonsId): array
    {
        $list = self::getListByWhere([
            ['emoticons_id', '=', $emoticonsId]
        ], 'emoticons_id, target_id');

        return $list ? array_column($list, 'target_id', 'emoticons_id') : [];
    }
}