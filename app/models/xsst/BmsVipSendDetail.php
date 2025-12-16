<?php

namespace Imee\Models\Xsst;

class BmsVipSendDetail extends BaseModel
{
    public static $displayVipLevel = [
        1 => 'VIP1',
        2 => 'VIP2',
        3 => 'VIP3',
        4 => 'VIP4',
        5 => 'VIP5',
        6 => 'VIP6',
        7 => 'VIP7',
        8 => 'VIP8',
    ];

    public static $allowDays = [
        30,
        7,
    ];

    public static $giveTypeMaps = [
        0 => '直接生效',
        1 => '用户手动可转赠',
        2 => '用户手动不可转赠',
    ];
}
