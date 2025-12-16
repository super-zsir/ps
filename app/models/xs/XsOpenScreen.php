<?php

namespace Imee\Models\Xs;

class XsOpenScreen extends BaseModel
{
    const NO_EFFECT   = 1;   // 未生效
    const IN_EFFECT   = 2;   // 生效中
    const LOSE_EFFECT = 3;   // 已失效

    const VISIBLE_CROWD_ALL      = 1; // 所有用户
    const VISIBLE_CROWD_ANCHOR   = 2; // 仅主播可见
    const VISIBLE_CROWD_SPECIFIC = 3; // 特定用户

    const JUMP_TYPE_WEB_PAGE  = 1;  // 网页
    const JUMP_TYPE_HOME_PAGE = 2;  // 个人主页
    const JUMP_TYPE_ROOM      = 3;  // 房间

    protected static $primaryKey = 'id';

    public static function getInfoByWeight(int $weight, int $id): array
    {
        return self::findOneByWhere([
            ['weight', '=', $weight],
            ['id', '<>', $id],
            ['end_time', '>', time()]
        ]);
    }
}