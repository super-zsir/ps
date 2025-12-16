<?php


namespace Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply;

use Imee\Models\Xss\XssAutoQuestion;
use Imee\Service\Helper;

class ConfigProcess
{
    public function handle()
    {
        $format = [];
        $types = XssAutoQuestion::$QUESTION_TYPE;
        foreach ($types as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['type'][] = $tmp;
        }

        $guide_to_service = XssAutoQuestion::$guide_to_service;
        foreach ($guide_to_service as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['guide_to_service'][] = $tmp;
        }

        foreach (Helper::getLanguageArr() as $key => $val) {
			$tmp['label'] = $val;
			$tmp['value'] = $key;
			$format['language'][] = $tmp;
		}

        return $format;
    }
}
