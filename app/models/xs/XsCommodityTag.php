<?php
/**
 *物品标签
 */

namespace Imee\Models\Xs;

class XsCommodityTag extends BaseModel
{
    protected static $primaryKey = 'id';
    protected $allowEmptyStringArr = [
        'icon', 'name', 'updated_at', 'created_at'
    ];
}
