<?php

namespace Imee\Service\Domain\Service\Csms\Helper;

class CommonHelper
{
    /**
     * p90
     * @param $data
     * @return int|mixed|string|null
     */
    public static function calP90($data = array())
    {
        if (empty($data)) {
            return 0;
        }
        $n = count($data);
        if ($n == 1) {
            return array_pop($data);
        }
        sort($data);
        $b = ($n - 1) * 0.9;
        $i = intval($b);
        $j = $b - $i;
        return sprintf('%.2f', (1 - $j) * $data[$i] + $j * $data[$i + 1]);
    }
}