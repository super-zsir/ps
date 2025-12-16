<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\FirstChatRecord;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xss\XssFirstChatRecord;

class ConfigProcess
{
    public function handle()
    {
		$format = [];

		$bigArea = XsBigarea::getAllNewBigArea();
		if ($bigArea) {
			foreach ($bigArea as $k => $v) {
				$tmp['label'] = $v;
				$tmp['value'] = $k;
				$format['from_big_area'][] = $tmp;
			}
		}

		foreach (XsUserProfile::$sex_arr as $k => $v) {
			$tmp['label'] = $v;
			$tmp['value'] = $k;
			$format['from_sex'][] = $tmp;
			$format['to_sex'][] = $tmp;
		}

		foreach (XssFirstChatRecord::$isReply as $k => $v) {
			$tmp['label'] = $v;
			$tmp['value'] = $k;
			$format['is_reply'][] = $tmp;
		}

		return $format;
    }
}
