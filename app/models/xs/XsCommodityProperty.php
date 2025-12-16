<?php
/**
 * 折扣率
 */

namespace Imee\Models\Xs;

class XsCommodityProperty extends BaseModel
{
    protected static $primaryKey = 'cid';

    protected $allowEmptyStringArr = [
        'grant_limit', 'grant_limit_range',
    ];

    public $title = 0;
}
