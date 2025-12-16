<?php

namespace Imee\Models\Xs;

class XsOrder extends BaseModel
{
    public function getOrderPage($uids, $start, $end)
    {
        if (!$uids) {
            return [];
        }
        $uidarr = array_chunk($uids, 200);
        $rec = [];
        foreach ($uidarr as $uids) {
            $orderData = XsOrder::find(array(
                "to in ({uids:array}) and iscomplete>0 and ispay>0 and update_time>=:st: and update_time<:est:",
                "bind"    => array("uids" => $uids, "st" => $start, "est" => $end),
                'columns' => 'id,to,state,version,money,money_subsidy,price,num,cid,isappeal',
            ))->toArray();
            if ($orderData) {
                foreach ($orderData as $v) {
                    $rec[] = $v;
                }
            }
        }
        return $rec;
    }
}