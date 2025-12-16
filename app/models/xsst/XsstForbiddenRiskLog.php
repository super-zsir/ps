<?php

namespace Imee\Models\Xsst;

class XsstForbiddenRiskLog extends BaseModel
{
    public static function createLog(array $row)
    {
        try {
            $log = new self();
            foreach ($row as $k => $v) {
                $log->{$k} = $v;
            }
            $now = time();
            $log->create_time = $now;
            $log->update_time = $now;
            $log->create();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
