<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\Channel;

use Imee\Models\Cms\CmsChatService;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 客服通道列表
 */
class ListProcess
{
	use UserInfoTrait;

    public function handle(): array
    {
		$res = CmsChatService::find();
		$uids = array();
		foreach ($res as $rec) {
			$uids[] = intval($rec->user_id);
		}
		$data = $res->toArray();
		$uids = array_values(array_unique($uids));
		if (empty($uids)) {
			return [];
		}

		$users = $this->getStaffBaseInfos($uids);
		foreach ($data as $key => $v) {
			$data[$key]['name'] = isset($users[$v['user_id']]) && $users[$v['user_id']]['user_name'] ? $users[$v['user_id']]['user_name'] : '-';
			//前端展示语言
			$languages = json_decode($v['language'], true);
			if (empty($languages)) {
				continue;
			}
			$lang_str = '';
			foreach ($languages as $app_id => $langs) {
				if (empty($langs) || $app_id != APP_ID) continue;
				$lang_str .= implode(', ', $langs);
			}
			$data[$key]['language'] = $languages[APP_ID] ?? [] ;
			$data[$key]['language_name'] = $lang_str;
		}

        return $data;
    }
}
