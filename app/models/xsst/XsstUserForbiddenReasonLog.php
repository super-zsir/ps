<?php

namespace Imee\Models\Xsst;

class XsstUserForbiddenReasonLog extends BaseModel
{
    public static function saveRows($data)
    {
        try {
            $rec = new self();
            foreach ($data as $k => $v) {
                $rec->{$k} = $v;
            }
            $rec->dateline  = time();
            $d = $rec->save();
            if ($d) {
                return true;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return false;
    }
}
