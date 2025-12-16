<?php

namespace Imee\Models\Xs;

class XsChatroomMaterial extends BaseModel
{
    protected static $primaryKey = 'id';
    
    public static $isFree = [
        0 => 'NO',
        1 => 'YES'
    ];
}