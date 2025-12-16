<?php

namespace Imee\Models\Xs;

use Phalcon\Di;

class XsUserMobile extends BaseModel
{
    public static $primaryKey = 'uid';

    protected $allowEmptyStringArr = ["password"];

    public static function findFirstValue($uid)
    {
        $rec = self::findFirst(
            array(
                "uid = :uid:",
                "bind" => array("uid" => $uid)
            )
        );

        return $rec ? $rec->toArray() : array();
    }
}
