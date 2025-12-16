<?php

namespace Imee\Models\Xs;

class XsCoupon extends BaseModel
{
    protected static $primaryKey = 'id';

    public static function getCouponMap(): array
    {
        $map = [];
        $couponLists = XsCoupon::getListByWhere([], 'id, amount', 'id asc');
        foreach ($couponLists as $item){

            $map[$item['id']] = sprintf("ID:%d(%d💎)", $item['id'], $item['amount']);
        }
        return $map;
    }
}