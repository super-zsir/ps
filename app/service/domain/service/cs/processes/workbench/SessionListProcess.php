<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Workbench;

use Imee\Models\Xss\XsChatSession;
use Imee\Service\Domain\Context\Cs\Workbench\SessionListContext;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

class SessionListProcess
{
    use UserInfoTrait;

    private $context;

    public function __construct(SessionListContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $uid = intval($this->context->uid);
        $uids = trim($this->context->uids);

        if ($uid <= 0) {
            return [];
        }
        $uids = array_map('intval', json_decode($uids));
		if (empty($uids)) {
			return [];
		}


		$res = XsChatSession::query()
			->columns("uid, dateline, unread")
			->where("service = {$uid}")
			->inWhere('uid', $uids)
			->orderBy('unread desc, dateline desc')
			->limit(200)
			->execute()
			->toArray();

        $map = array();
        foreach ($res as $rec) {
            $map[$rec['uid']] = $rec;
        }

		$userMap = $this->getUserInfoModel(array_unique($uids))
			->vip()
			->bigarea()
			->title()
			->handle();

        $ordering = array();
        $unread = array();

        foreach ($userMap as &$val) {
            $rec = $map[$val['uid']];
            $val['dateline'] = $rec['dateline'];

//            //界面展示时-1
            if ($rec['unread'] > 0) {
                $rec['unread'] -= 1;
            }
            $val['unread'] = $rec['unread'];
            $val['app_name'] = Helper::getAppName($val['app_id']);

            $ordering[] = intval($rec['dateline']);
            $unread[] = intval($rec['unread']);

            $val['icon'] = Helper::getHeadUrl($val['icon']);
        }

        array_multisort($unread, SORT_DESC, SORT_NUMERIC, $ordering, SORT_DESC, SORT_NUMERIC, $userMap);
        return $userMap;
    }
}
