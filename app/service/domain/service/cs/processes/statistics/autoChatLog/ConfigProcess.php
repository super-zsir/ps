<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChatLog;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\XsstAutoQuestionLog;

class ConfigProcess
{
    public function handle()
    {
        foreach (XsstAutoQuestionLog::$displayType as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['type'][] = $tmp;
        }

        foreach (XsstAutoQuestionLog::$displayIsService as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['is_service'][] = $tmp;
        }

		foreach (XsBigarea::$_bigAreaMap as $k => $v) {
			$tmp['label'] = $v;
			$tmp['value'] = $k;
			$format['language'][] = $tmp;
		}

        return $format;
    }
}
