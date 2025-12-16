<?php


namespace Imee\Models\Xs;


class XsUserRoomSkin  extends BaseModel
{
    const EXPIRE_STATUS = 1;
    const NO_EXPIRE_STATUS = 2;

    protected static $primaryKey = 'id';

    /**
     * 检测下发用户皮肤是否过期
     * @param $id
     * @return array
     */
    public static function checkIsExpire($id)
    {
         return self::findOneByWhere([
            ['skin_id', '=', $id],
            ['expire_time', '>', time()]
        ]);
    }
}