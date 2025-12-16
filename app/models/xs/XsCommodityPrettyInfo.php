<?php

namespace Imee\Models\Xs;

class XsCommodityPrettyInfo extends BaseModel
{
    const ON_SALE_STATUS_ON = 1;
    const ON_SALE_STATUS_OFF = 2;

    public static $displayOnSaleStatus = [
        self::ON_SALE_STATUS_ON => '上架中',
        self::ON_SALE_STATUS_OFF => '下架中',
    ];
}
