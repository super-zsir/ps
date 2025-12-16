<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\User;

use Imee\Models\Xs\XsUserPretty;

/**
 * 列表
 */
class ConfigProcess
{
    public function getStatus()
    {
        $format = [];

        foreach (XsUserPretty::$displayStatus as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getSource()
    {
        $format = [];

        foreach (XsUserPretty::$displayPrettySource as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }
}
