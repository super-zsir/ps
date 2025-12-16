<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Models\Config\BbcCustomerServiceQuickReplyGroup;

class ConfigProcess
{
    public function handle()
    {
        $format = [];

        $group = BbcCustomerServiceQuickReplyGroup::query()
			->where('deleted = :deleted:', ['deleted' => BbcCustomerServiceQuickReplyGroup::DELETED_NO])
			->execute()
			->toArray();
        if ($group) {
			foreach ($group as $val) {
				$tmp['label'] = $val['group_name'];
				$tmp['value'] = $val['id'];
				$format['group_id'][] = $tmp;
			}
		}

        return $format;
    }
}
