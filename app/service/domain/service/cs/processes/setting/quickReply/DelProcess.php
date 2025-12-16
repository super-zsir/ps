<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Config\BbcCustomerServiceQuickReply;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\DelContext;

/**
 * 快捷回复删除
 */
class DelProcess
{
    private $context;

    public function __construct(DelContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $model = BbcCustomerServiceQuickReply::findFirst([
            'conditions' => 'id = :id: and deleted = :deleted:',
            'bind' => [
                'id' => $this->context->id,
                'deleted' => BbcCustomerServiceQuickReply::DELETED_NO,
            ],
        ]);
        if (empty($model)) {
			CommonException::throwException(CommonException::RECORD_NOT_FOUND);
        }
        $model->update_time = time();
        $model->deleted = BbcCustomerServiceQuickReply::DELETED_YES;
        if (!$model->save()) {
			CommonException::throwException(CommonException::DELETE_FAILED);
		}
        return true;
    }
}
