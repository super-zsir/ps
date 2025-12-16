<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Config\BbcCustomerServiceQuickReplyGroup;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\GroupCreateContext;
use Imee\Service\Helper;

/**
 * 快捷回复分组创建
 */
class GroupCreateProcess
{
    private $context;

    public function __construct(GroupCreateContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
		$rec = BbcCustomerServiceQuickReplyGroup::query()
			->where('group_name = :group_name: and deleted = :deleted:',
				['group_name' => $this->context->groupName, 'deleted' => BbcCustomerServiceQuickReplyGroup::DELETED_NO])
			->execute()
			->toArray();
		if (!empty($rec)) {
			CommonException::throwException(CommonException::DUPLICATE_RECORD);
		}

		$model = new BbcCustomerServiceQuickReplyGroup();
		$model->group_name = $this->context->groupName;
		$model->dateline = time();
		$model->update_time = time();
		$model->op_uid = Helper::getSystemUid();
		if (!$model->save()) {
			CommonException::throwException(CommonException::CREATE_FAILED);
		}
		return true;
    }
}
