<?php

namespace Imee\Models\Xsst;

class XsstSessionForbiddenLog extends BaseModel
{
    public static function saveRows($rows)
    {
        $rec = new self();
        foreach ($rows as $k => $v) {
            $rec->{$k} = $v;
        }
        $rec->create_time = time();
        $rec->save();
        return true;
    }
}
