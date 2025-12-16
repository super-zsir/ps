<?php

namespace Imee\Models\Xs;

class XsChatroomSetredpackage extends BaseModel
{
    protected $primaryKey = 'id';

    const DELETED_NORMAL = 0;
    const DELETED_DELETE = 1;

    public static $deletedMap = [
        self::DELETED_NORMAL => '正常',
        self::DELETED_DELETE => '删除',
    ];
}