<?php

namespace Imee\Service\Domain\Service\Audit\RiskUser;

use Imee\Service\Domain\Context\Audit\RiskUser\ListContext;
use Imee\Service\Domain\Context\Audit\RiskUser\ModifyContext;
use Imee\Service\Domain\Context\Audit\RiskUser\RiskDataCountContext;
use Imee\Service\Domain\Context\Audit\RiskUser\RiskStrategyStatisticsContext;
use Imee\Service\Domain\Service\Audit\Processes\RiskUser\ConfigProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskUser\ListProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskUser\ModifyProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskUser\RiskDataCountProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskUser\RiskStrategyStatisticsProcess;

class RiskUserService
{
    public function getList($params)
    {
		$context = new ListContext($params);
        $process = new ListProcess($context);
        return $process->handle();
    }

    public function getConfig()
    {
        $process = new ConfigProcess();
        return $process->handle();
    }

    public function modify($params)
    {
		$context = new ModifyContext($params);
        $process = new ModifyProcess($context);
        return $process->handle();
    }

    public function riskDataCount($params)
	{
		$context = new RiskDataCountContext($params);
		$process = new RiskDataCountProcess($context);
		return $process->handle();
	}

	public function riskStrategyStatistics($params)
	{
		$context = new RiskStrategyStatisticsContext($params);
		$process = new RiskStrategyStatisticsProcess($context);
		return $process->handle();
	}
}
