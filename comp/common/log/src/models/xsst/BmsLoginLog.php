<?php

namespace Imee\Comp\Common\Log\Models\Xsst;

class BmsLoginLog extends BaseModel
{
    public static $primaryKey = 'id';
    protected static $createTime = 'dateline';

    const LOGIN_TYPE = 1;
    const LOGOUT_TYPE = 2;

    public static $typeMap = [
        self::LOGIN_TYPE  => '登录',
        self::LOGOUT_TYPE => '退出'
    ];
}