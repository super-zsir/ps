<?php

namespace Imee\Models\Xsst;

class BmsVipSend extends BaseModel
{
    const STATE_UNVALID = 0;
    const STATE_SUC = 1;
    const STATE_FAIL = 2;
    public static $displayState = [
        self::STATE_SUC => '成功',
        self::STATE_FAIL => '失败',
    ];
}
