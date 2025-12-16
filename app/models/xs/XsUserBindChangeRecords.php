<?php

namespace Imee\Models\Xs;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class XsUserBindChangeRecords extends BaseModel
{
    public static function addRecord($uid, $op, $type)
    {
        $record = self::useMaster();
        $record->uid = $uid;
        $record->op = $op;
        $record->type = $type;
        $record->dateline = time();
        $record->save();

        // 5秒后检查用户地区
//        NsqClient::publish(NsqConstant::TOPIC_USER_COUNTRY, array(
//            'cmd'  => 'user.mobile',
//            'data' => array(
//                'uid' => $uid,
//                'tp'  => 'mobile',
//            )
//        ), 5);
    }
}