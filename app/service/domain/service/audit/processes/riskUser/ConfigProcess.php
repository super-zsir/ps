<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskUser;

use Imee\Helper\Constant\RiskConstant;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserReaudit;

class ConfigProcess
{
    public function handle()
    {
        $format = [];

        foreach (XsUserReaudit::$status_arr as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['status'][] = $tmp;
        }

		foreach (RiskConstant::RISK_USER_RULE_TYPES as $key => $val) {
			$tmp['label'] = $val;
			$tmp['value'] = $key;
			$format['type'][] = $tmp;
		}

		foreach (XsBigarea::$_bigAreaMap as $key => $val) {
			$tmp['label'] = $val;
			$tmp['value'] = $key;
			$format['language'][] = $tmp;
		}

        return $format;
    }
}
