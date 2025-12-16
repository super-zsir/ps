<?php

namespace Imee\Models\Xs;

class XsIntimateRelationPayConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const INTIMATE_RELATION_TYPE_CP = 1;
    const INTIMATE_RELATION_TYPE_BEST_FRIEND = 2;

    public static $intimateRelationTypeMap = [
        self::INTIMATE_RELATION_TYPE_CP          => 'cp席位',
        self::INTIMATE_RELATION_TYPE_BEST_FRIEND => '挚友席位'
    ];
}