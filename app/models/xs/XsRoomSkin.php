<?php


namespace Imee\Models\Xs;


class XsRoomSkin  extends BaseModel
{
    // 下发状态
    const NO_SUPPORT_SEND_STATUS = 0;
    const SUPPORT_SEND_STATUS    = 1;

    const TYPE_ROOM  = 1; // 房间麦波
    const TYPE_SKIN  = 2; // 麦位皮肤
    const TYPE_TITLE = 3; // 房间标题边框

    public static $typeMap = [
        self::TYPE_ROOM  => '房间麦波',
        self::TYPE_SKIN  => '麦位皮肤',
        self::TYPE_TITLE => '房间标题边框',
    ];

    protected static $primaryKey = 'id';

    public static function getListIdAndName()
    {
        $list = self::getListByWhere([
            ['status', '=', self::SUPPORT_SEND_STATUS]
        ], 'id, name', 'id desc');

        $map = [];
        foreach ($list as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }
        return $map;
    }

    public static function getInfo(int $id): array
    {
        return self::findOneByWhere([
            ['id', '=', $id],
            ['status', '=', self::SUPPORT_SEND_STATUS]
        ]);
    }
}