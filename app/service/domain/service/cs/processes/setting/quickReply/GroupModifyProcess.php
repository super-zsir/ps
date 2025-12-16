<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Config\BbcCustomerServiceQuickReplyGroup;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\GroupModifyContext;
use Imee\Service\Helper;

/**
 * 修改
 */
class GroupModifyProcess
{
    private $context;

    public function __construct(GroupModifyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
		$model = BbcCustomerServiceQuickReplyGroup::findFirst([
			'conditions' => 'id = :id: and deleted = :deleted:',
			'bind' => [
				'id' => $this->context->id,
				'deleted' => BbcCustomerServiceQuickReplyGroup::DELETED_NO,
			],
		]);
		if (empty($model)) {
			CommonException::throwException(CommonException::RECORD_NOT_FOUND);
		}

		$rec = BbcCustomerServiceQuickReplyGroup::query()
			->where('group_name = :group_name: and deleted = :deleted:',
				['group_name' => $this->context->groupName, 'deleted' => BbcCustomerServiceQuickReplyGroup::DELETED_NO])
			->execute()
			->toArray();
		if (!empty($rec)) {
			CommonException::throwException(CommonException::DUPLICATE_RECORD);
		}

		$model->update_time = time();
		$model->group_name = $this->context->groupName;
		$model->op_uid = Helper::getSystemUid();
		if (!$model->save()) {
			CommonException::throwException(CommonException::MODIFY_FAILED);
		}
		return true;
    }
}
