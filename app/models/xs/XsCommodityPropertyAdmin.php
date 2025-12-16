<?php
/**
 * 折扣率审核
 */

namespace Imee\Models\Xs;

class XsCommodityPropertyAdmin extends BaseModel
{
    protected static $primaryKey = 'cid';

    protected $allowEmptyStringArr = [
        'grant_limit', 'grant_limit_range',
    ];

    public $title = 0;
}
