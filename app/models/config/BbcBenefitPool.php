<?php
/**
 * 奖池
 */

namespace Imee\Models\Config;

class BbcBenefitPool extends BaseModel
{
    protected static $primaryKey = 'id';
    const STATUS_ON = 1;
    const STATUS_OFF = 0;
}