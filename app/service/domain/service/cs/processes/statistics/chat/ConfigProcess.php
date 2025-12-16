<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\Chat;

use Imee\Models\Xs\XsBigarea;

class ConfigProcess
{
    public function handle()
    {
        $format = [];
         foreach (XsBigarea::$_bigAreaMap as $k => $v) {
             $tmp['label'] = $v;
             $tmp['value'] = $k;
             $format['big_area'][] = $tmp;
         }
        return $format;
    }
}
