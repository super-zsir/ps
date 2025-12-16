<?php


namespace Imee\Models\Xss;


class XsChatSession extends BaseModel
{
	public static function addRow($uid, $service)
	{
		$dateline = time();
		$rec = self::findFirst("service={$service} and uid={$uid}");
		$changed = false;
		if (!$rec) {
			$rec = new self();
			$rec->service = $service;
			$rec->uid = $uid;
			$rec->unread = 0;
			$changed = true;
		}
		$rec->dateline = $dateline;
		$rec->save();
		return array(
			'unread' => intval($rec->unread),
			'changed' => $changed ? 1 : 0,
		);
	}
}