<?php

namespace Imee\Models\Xs;

class XsIntimateRelationEnforceLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const RELATION_TYPE_UNRELATED = 0;
    const RELATION_TYPE_CP = 1;
    const RELATION_TYPE_BEST_FRIEND = 2;

    public static $relationTypeMap = [
        self::RELATION_TYPE_CP => 'cp',
        self::RELATION_TYPE_BEST_FRIEND => '挚友',
    ];
}