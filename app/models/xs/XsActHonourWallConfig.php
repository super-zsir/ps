<?php

namespace Imee\Models\Xs;

class XsActHonourWallConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const IS_SHOW_YES = 1;
    const IS_SHOW_NO = 0;

    public static $isShowMap = [
        self::IS_SHOW_YES => '展示',
        self::IS_SHOW_NO  => '不展示',
    ];

    const PAGE_URL_DEV = 'https://dev.partystar.cloud/frontend/honorWall-template/?aid=%d&clientScreenMode=1';
    const PAGE_URL = 'https://page.partystar.chat/honorWall-template/?aid=%d&clientScreenMode=1';
}