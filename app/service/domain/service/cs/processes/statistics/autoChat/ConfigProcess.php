<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChat;

use Imee\Models\Xs\XsBigarea;

class ConfigProcess
{
    public function handle()
    {
        $format['statistical_type'] = [
            [
                'label' => '问题类型统计',
                'value' => 1
            ],
            [
                'label' => '标准问题统计',
                'value' => 2
            ],
        ];

		foreach (XsBigarea::$_bigAreaMap as $k => $v) {
			$tmp['label'] = $v;
			$tmp['value'] = $k;
			$format['language'][] = $tmp;
		}

        return $format;
    }
}
