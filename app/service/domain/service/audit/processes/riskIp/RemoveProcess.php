<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskIp;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Config\BbcRiskIpList;
use Imee\Service\Domain\Context\Audit\RiskIp\RemoveContext;
use Imee\Service\Helper;

/**
 * 删除
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
        $rec = BbcRiskIpList::findFirst($this->context->id);
        if (!$rec) {
			CommonException::throwException(CommonException::RECORD_NOT_FOUND);
        }
		$rec->is_delete = 1;
		$rec->op_id = Helper::getSystemUid();
		$rec->op_dateline = time();
		if (!$rec->save()) {
			CommonException::throwException(CommonException::DELETE_FAILED);
		}
		return true;
    }
}
