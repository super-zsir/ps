<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Config\BbcCustomerServiceQuickReply;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\ModifyContext;

/**
 * 快捷回复修改
 */
class ModifyProcess
{
    private $context;

    public function __construct(ModifyContext $context)
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
        $model->content = $this->context->content;
        if (!$model->save()) {
			CommonException::throwException(CommonException::MODIFY_FAILED);
		}
        return true;
    }
}
