<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Config\BbcCustomerServiceQuickReplyGroup;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\GroupDelContext;
use Imee\Service\Helper;

/**
 * 快捷回复删除
 */
class GroupDelProcess
{
    private $context;

    public function __construct(GroupDelContext $context)
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
        $model->update_time = time();
        $model->deleted = BbcCustomerServiceQuickReplyGroup::DELETED_YES;
		$model->op_uid = Helper::getSystemUid();
        if (!$model->save()) {
			CommonException::throwException(CommonException::DELETE_FAILED);
		}
        return true;
    }
}
