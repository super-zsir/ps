<?php

namespace Imee\Models\Xs;

class XsLuckyWheelConfig extends BaseModel
{
    const PARAM_PREFIX = 'config_';
    const TICKETS_PRICE_FIELD  = 'tickets_price_gear';
    const JOIN_NUMS_FIELD = 'join_nums_gear';

    public static $type = [
        'tickets_price_gear' => '入场费配置',
        'join_nums_gear'     => '参与上线人数配置',
        'gain'               => '分成比例配置',
    ];

    public static $default = [
        'join_nums_gear' => [],
        'tickets_price_gear' => [],
        'homeowner_gain' => 0,
        'platform_gain' => 0,
        'winner_gain' => 0,
    ];
}