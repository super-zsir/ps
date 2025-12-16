<?php

namespace Imee\Service\Domain\Service\Audit\Risk;

use Imee\Service\Domain\Context\Audit\RiskIp\CreateContext;
use Imee\Service\Domain\Context\Audit\RiskIp\ListContext;
use Imee\Service\Domain\Context\Audit\RiskIp\RemoveContext;
use Imee\Service\Domain\Service\Audit\Processes\RiskIp\CreateProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskIp\ListProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskIp\RemoveProcess;

class RiskIpService
{
    public function getList($params)
	{
		$context = new ListContext($params);
		$process = new ListProcess($context);
		return $process->handle();
	}

	public function create($params)
	{
		$context = new CreateContext($params);
		$process = new CreateProcess($context);
		return $process->handle();
	}

	public function remove($params)
	{
		$context = new RemoveContext($params);
		$process = new RemoveProcess($context);
		return $process->handle();
	}
}
