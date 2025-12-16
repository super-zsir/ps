<?php

namespace Imee\Models\Xsst;

class XsstFollowSum extends BaseModel
{
    public static function saveRows($data)
    {
        $rec = self::findFirst(array(
            "date=:date: and uid=:uid:",
            "bind" => array("date" => $data["date"], "uid" => $data["uid"])
        ));

        if ($rec) {
            foreach ($data as $k => $v) {
                $rec->{$k} = $v;
            }
            $d = $rec->save();
            if ($d) return true;
        } else {
            $rec = new XsstFollowSum();
            foreach ($data as $k => $v) {
                $rec->{$k} = $v;
            }
            $d = $rec->save();
            if ($d) return true;
        }
        return false;
    }
}
