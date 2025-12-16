<?php

namespace Imee\Models\Xs;

class XsTarotOddsPercent extends BaseModel
{
    public static $typeMap = [
        0  => 0,
        1  => 1,
        -1 => -1,
        2  => 'lucky'
    ];
}