<?php

namespace Imee\Models\Xsst;

class XsstChatroomBackground extends BaseModel
{
    protected $primaryKey = 'id';

    const ICON_PATH_PREFIX = 'static/background/';

    const ICON_PATH = self::ICON_PATH_PREFIX . 'room_background_%s.jpg';
    const ICON2_PATH = self::ICON_PATH_PREFIX . 'theme_preview_%s.png';

    /**
     * 根据类型获取房间背景图片
     * @param string $type
     * @return array
     */
    public static function getInfoByType(string $type): array
    {
        return self::findOneByWhere([
            ['app_id', '=', APP_ID],
            ['type', '=', $type]
        ]);
    }


    /**
     * 获取背景类型枚举
     * @return array
     */
    public static function getOptions(): array
    {
        $list = self::getListByWhere([['app_id', '=', APP_ID]], 'type', 'id desc');

        return $list ? array_column($list, 'type', 'type') : [];
    }
}