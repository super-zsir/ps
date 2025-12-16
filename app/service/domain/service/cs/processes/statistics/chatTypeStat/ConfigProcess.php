<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\ChatTypeStat;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\XssChatTypeStat;
use Imee\Models\Xss\XssAutoService;

class ConfigProcess
{
    public function handle()
    {
        $format['service'][] = [
            'label' => '汇总',
            'value' => 0,
        ];
        foreach (XssAutoService::$manualChatServiceConfig as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['service'][] = $tmp;
        }

        foreach (XssChatTypeStat::$activeType as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['active_type'][] = $tmp;
        }

		foreach (XsBigarea::$_bigAreaMap as $k => $v) {
			$tmp['label'] = $v;
			$tmp['value'] = $k;
			$format['big_area'][] = $tmp;
		}

        return $format;
    }
}
