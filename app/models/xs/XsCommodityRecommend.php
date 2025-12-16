<?php
/**
 *物品推荐配置表
 */

namespace Imee\Models\Xs;

class XsCommodityRecommend extends BaseModel
{
    protected static $primaryKey = 'id';
    protected $allowEmptyStringArr = ['type'];

    public static $typeMap = [
        'new'  => '上新',
        'rcmd' => '推荐',
    ];
}
