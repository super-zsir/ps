<?php

namespace Imee\Models\Xss;

class XssRoomLog extends BaseModel
{
    protected $primaryKey = 'id';

    const TYPE_NORMAL = 'normal';
    const TYPE_PACKAGE = 'package';
    const TYPE_SYSTEM = 'system';
    const TYPE_OTHER = 'other';

    const TYPE_NOTIFY = 'notify';

    public static $typeMap = [
        self::TYPE_NORMAL  => '普通',
        self::TYPE_PACKAGE => '红包',
        self::TYPE_SYSTEM  => '系统',
        self::TYPE_OTHER   => '其它',
    ];
}