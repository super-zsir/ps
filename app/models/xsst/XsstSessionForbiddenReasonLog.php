<?php

namespace Imee\Models\Xsst;

class XsstSessionForbiddenReasonLog extends BaseModel
{
    public static function saveRows($rows)
    {
        $rec = new self();
        foreach ($rows as $k => $v) {
            $rec->{$k} = $v;
        }
        $rec->dateline = time();
        $rec->save();
        return true;
    }
}
