<?php

namespace Imee\Models\Xs;

class XsUserModify extends BaseModel
{
    protected static $primaryKey = 'id';

    public static function getValueByKey($uid, $type = '', $state = 1)
    {
        return self::findFirst(array(
            "uid=:uid: and type=:type: and state=:state:",
            "bind"  => array("uid" => $uid, "type" => $type, "state" => $state),
            "order" => "dateline desc"
        ));
    }

    public static function getValueByKeyUp($uid, $type = '', $state = 1)
    {
        return self::findFirst(array(
            "uid=:uid: and type=:type: and state=:state:",
            "bind"  => array("uid" => $uid, "type" => $type, "state" => $state),
            "order" => "update_time desc"
        ));
    }

    public static function updateUnUse($uid, $type = '')
    {
        $data = self::useMaster()->find(array(
            "uid=:uid: and type=:type: and state=:state:",
            "bind"  => array("uid" => $uid, "type" => $type, "state" => 0),
            "order" => "dateline desc"
        ));
        if (!$data) return true;
        foreach ($data as $v) {
            $v->state = 4;
            $v->save();
        }
    }

    public static function updateRows($uid, $type = '', $rows = array())
    {
        $rec = self::useMaster();
        $rec->uid = $uid;
        $rec->type = $type;
        foreach ($rows as $k => $v) {
            $rec->{$k} = $v;
        }
        $rec->dateline = time();
        $rec->save();
        return true;
    }
}