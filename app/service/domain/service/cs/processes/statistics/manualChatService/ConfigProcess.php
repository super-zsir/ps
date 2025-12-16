<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\ManualChatService;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\XssAutoService;

class ConfigProcess
{
    public function handle()
    {
        foreach (XssAutoService::$manualChatServiceConfig as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['service'][] = $tmp;
        }

        foreach (XsBigarea::$_bigAreaMap as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['language'][] = $tmp;
        }

        return $format;
    }
}
