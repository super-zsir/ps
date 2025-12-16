<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Config\BbcCustomerServiceQuickReply;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\CreateContext;
use Imee\Service\Helper;

/**
 * 快捷回复创建
 */
class CreateProcess
{
    private $context;

    public function __construct(CreateContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $model = new BbcCustomerServiceQuickReply();
        $model->dateline = time();
        $model->update_time = time();
        $model->content = $this->context->content;
        $model->app_str = APP_ID;
        $model->group_id = $this->context->groupId;
        $model->deleted = BbcCustomerServiceQuickReply::DELETED_NO;
        $model->op_uid = Helper::getSystemUid();

        if (!$model->save()) {
        	CommonException::throwException(CommonException::CREATE_FAILED);
		}

        return true;
    }
}
