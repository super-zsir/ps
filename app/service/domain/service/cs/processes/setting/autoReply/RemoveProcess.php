<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Xss\XssAutoQuestion;
use Imee\Service\Domain\Context\Cs\Setting\AutoReply\RemoveContext;

/**
 * 工单系统删除
 */
class RemoveProcess
{
    private $context;

    public function __construct(RemoveContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $rec = XssAutoQuestion::findFirst($this->context->id);
        if (!$rec) {
			CommonException::throwException(CommonException::RECORD_NOT_FOUND);
        }
        if (!$rec->delete()) {
			CommonException::throwException(CommonException::DELETE_FAILED);
		}
        return true;
    }
}
