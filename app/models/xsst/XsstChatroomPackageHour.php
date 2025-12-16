<?php

namespace Imee\Models\Xsst;

class XsstChatroomPackageHour extends BaseModel
{

    public static function insertRows($data)
    {
        try {
            $rec = self::findFirst(array(
                "dateline=:dateline: and uid=:uid: and rid=:rid:",
                "bind" => array("dateline" => $data["dateline"], 'uid' => $data['uid'], 'rid' => $data['rid'])
            ));

            if ($rec) {
                $rec->money += $data['money'];
                $d = $rec->save();
                if ($d) return true;
            } else {
                $rec = new self();
                foreach ($data as $k => $v) {
                    $rec->{$k} = $v;
                }
                $d = $rec->save();
                if ($d) return true;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return false;
    }

}
