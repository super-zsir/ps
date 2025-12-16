<?php

namespace Imee\Models\Xs;

class XsUserPayPassword extends BaseModel
{
    const STATE_NO = '1';
    const STATE_YES = '2';

    public static $stateMessageMap = [
        self::STATE_NO => '未设置',
        self::STATE_YES => '已设置'
    ];
}