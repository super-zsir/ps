<?php
namespace Imee\Service\Domain\Service\Cs\Processes\Workbench;

use Imee\Models\Cms\CmsChatService;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserSettings;
use Imee\Models\Xss\XsChatSession;
use Imee\Service\Domain\Service\User\UserService;
use Imee\Service\Helper;

class ChatIndexProcess
{

    public function handle()
    {
        $uid = Helper::getSystemUid();

		$cmsChatService = CmsChatService::findByUserId($uid);
		$cmsServices = array();
		foreach ($cmsChatService as $rec) {
			$languages = json_decode($rec->language, true);
			if (empty($languages)) {
				continue;
			}
			$temp = [];
			foreach ($languages as $app_id => $langs) {
				if (empty($langs)) {
					continue;
				}
				$temp = array_merge($temp, $langs);
			}
			$cmsServices[$rec->service] = $temp;
		}
		$services = array_keys($cmsServices);
		unset($cmsChatService);

		//获取未读对话
		$xsChatSession = XsChatSession::query()
			->columns('service, uid')
			->andWhere('unread > :unread:', ['unread' => 0])
			->limit(1000)
			->execute()
			->toArray();

		if (empty($xsChatSession)) return [];

		$unread_services = array();
		$unread_uids = array();
		foreach ($xsChatSession as $v) {
			$unread_services[$v['service']][] = $v['uid'];
			$unread_uids[] = $v['uid'];
		}
		$unread_uids = array_values(array_unique($unread_uids));
		unset($xsChatSession);
		//获取未读用户语言
		$user_langs = [];
		$unread_uids = array_chunk($unread_uids, 100);

		$userService = new UserService();
		foreach ($unread_uids as $un_uids) {
			$bigAreaCodes = $userService->getUserBigAreaCode($un_uids);
			if (empty($bigAreaCodes)) continue;
			foreach ($bigAreaCodes as $uid => $areaCode) {
				$user_langs[$uid] = $areaCode;
			}
		}

		//按语言区分未读数量
		$map = array();
		foreach ($unread_services as $service => $uids) {
			$map[$service]['all'] = count($uids);
			if (empty($uids)) continue;
			foreach ($uids as $uid) {
				$language = !empty($user_langs[$uid]) ? $user_langs[$uid] : '-';
				if (!isset($map[$service][$language])) {
					$map[$service][$language] = 0;
				}
				$map[$service][$language] += 1;
			}
		}

		$res = XsUserProfile::find("uid > 10000000 and uid <= 10000050");
		$disallow = array(10000000, 10000014, 10000015);
		$data = array();
		foreach ($res as $rec) {
			if (in_array($rec->uid, $disallow)) continue;
			if (!empty($services) && !in_array($rec->uid, $services)) continue;

			$todo = 0;//该客服拥有的语言权限下，待办数量
			if (!empty($map[$rec->uid]) && !empty($cmsServices[$rec->uid])) {
				foreach ($map[$rec->uid] as $lang => $lang_num) {
					if (in_array($lang, $cmsServices[$rec->uid])) $todo += intval($lang_num);
				}
			}

			$data[] = array(
				'uid' => intval($rec->uid),
				'unread' => isset($map[$rec->uid]) && isset($map[$rec->uid]['all']) ? intval($map[$rec->uid]['all']) : 0,
				'name' => trim($rec->name),
				'icon' => trim($rec->icon),
				'todo' => $todo,
			);
		}

		return $data;
    }
}
