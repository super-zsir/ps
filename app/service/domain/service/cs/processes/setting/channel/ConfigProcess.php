<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\Channel;

use Imee\Models\Xs\XsBigarea;

class ConfigProcess
{
    public function handle()
    {
        $format = [];

		foreach (XsBigarea::$_bigAreaMap as $key => $val) {
			$tmp['label'] = $val;
			$tmp['value'] = $key;
			$format['language'][] = $tmp;
		}

        return $format;
    }
}
