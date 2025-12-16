<?php

namespace Imee\Models\Config;

class BbcRankWhiteList extends BaseModel
{
    const TYPE_USER = 1;
    const TYPE_ROOM = 2;   // 房主白名单
    const TYPE_BROKER = 5;
    const TYPE_FAMILY = 6;
    const TYPE_ANCHOR = 7;
    const TYPE_CONTRIBUTE_USER = 8;

    public static $primaryKey = 'id';

    public static function has($uid, $bid, $tagId)
    {
        return self::findOneByWhere([
            ['uid', '=', $uid],
            ['extend_id', '=', $bid],
            ['button_tag_id', '=', $tagId],
        ]);
    }
}