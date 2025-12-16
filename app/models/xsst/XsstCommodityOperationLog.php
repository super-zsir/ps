<?php
/**
 * 物品修改日志
 */

namespace Imee\Models\Xsst;

class XsstCommodityOperationLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const TYPE_ADD = 1;
    const TYPE_UPDATE = 2;
    const TYPE_REVIEW_PASS = 3;
    const TYPE_REVIEW_FAIL = 4;
    const TYPE_REVIEW_WAIT = 5;

    public static $typeList = [
        self::TYPE_ADD         => '新增',
        self::TYPE_UPDATE      => '修改',
        self::TYPE_REVIEW_PASS => '审核通过',
        self::TYPE_REVIEW_FAIL => '审核拒绝',
        self::TYPE_REVIEW_WAIT => '待审核'
    ];
}