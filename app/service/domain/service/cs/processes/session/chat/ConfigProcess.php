<?php


namespace Imee\Service\Domain\Service\Cs\Processes\Session\Chat;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\XssAutoQuestion;
use Imee\Models\Xss\XssAutoService;

class ConfigProcess
{
    public function handle()
    {
        $format = [];

        foreach (XssAutoService::$serviceArray as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['service'][] = $tmp;
        }

        foreach (XssAutoService::$reasonArray as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['reason'][] = $tmp;
        }

        foreach (XssAutoService::$voteArray as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['vote'][] = $tmp;
        }

        foreach (XssAutoQuestion::$QUESTION_TYPE as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['chat_type'][] = $tmp;
        }

        foreach (XsBigarea::$_bigAreaMap as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['language'][] = $tmp;
        }

        return $format;
    }
}
