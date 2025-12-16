<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Models\Config\BbcCustomerServiceQuickReply;
use Imee\Models\Config\BbcCustomerServiceQuickReplyGroup;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 快捷回复列表
 */
class TreeListProcess
{
	use UserInfoTrait;

    public function __construct() {}

    public function handle()
	{
		$groupName = $this->getGroupName();
		if (empty($groupName)) return [];

		$query = BbcCustomerServiceQuickReply::query()
			->where('deleted = ' . BbcCustomerServiceQuickReply::DELETED_NO)
			->execute()
			->toArray();
		if (empty($query)) return [];

		$index = 10000;
		foreach ($query as $val) {
			if (!isset($groupName[$val['group_id']])) continue;

			$groupName[$val['group_id']]['children'][] = [
				'key' => $index,
				'title' => $val['content'],
				'parent_id' => $val['group_id'],
				'is_leaf' => 1,
			];

			$index++;
		}

		return array_values($groupName);
	}

	private function getGroupName()
	{
		$groupName = [];
		$groupList = BbcCustomerServiceQuickReplyGroup::getList(BbcCustomerServiceQuickReplyGroup::DELETED_NO);
		if ($groupList) {
			foreach ($groupList as $group) {
				$groupName[$group['id']] = [
					'key' => $group['id'],
					'title' => $group['group_name'],
					'parent_id' => 0,
					'is_leaf' => 0,
				];
			}
		}
		return $groupName;
	}
}
