<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Models\Config\BbcCustomerServiceQuickReplyGroup;

/**
 * 快捷回复分组列表
 */
class GroupListProcess
{
	protected $masterClass;
	protected $query;

    public function __construct()
    {
        $this->masterClass = BbcCustomerServiceQuickReplyGroup::class;
        $this->query = BbcCustomerServiceQuickReplyGroup::query();
    }

    public function handle()
	{
		$res = $this->query->where('deleted = :deleted:', ['deleted' => BbcCustomerServiceQuickReplyGroup::DELETED_NO])
			->execute()
			->toArray();

		return [
			'total' => count($res),
			'data' => $res
		];
	}
}
