<?php

namespace Imee\Models\Xs;

class XsWebPageResource extends BaseModel
{
    const STATUS_YES = 1;
    const STATUS_NO = 0;

    public static $statusMap = [
        self::STATUS_NO => '否',
        self::STATUS_YES   => '是',
    ];
}