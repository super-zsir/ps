<?php
namespace Imee\Service\Domain\Service\Audit\Processes\RiskIp;

use Imee\Models\Config\BbcRiskIpList;
use Imee\Service\Domain\Context\Audit\RiskIp\CreateContext;
use Imee\Service\Helper;

class CreateProcess
{
    private $context;
    public function __construct(CreateContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
    	$ip4 = $this->context->ip4 ?? '*';
    	$ip = $this->context->ip1 . '.' . $this->context->ip2 . '.' . $this->context->ip3 . '.' . $ip4;

		$rec = new BbcRiskIpList();
		$rec->ip = $ip;
		$rec->mark = $this->context->mark ?? '';
		$rec->op_id = Helper::getSystemUid();
		$rec->is_delete = 0;
		$rec->op_dateline = time();
		$rec->dateline = time();

		return $rec->save();
    }
}
