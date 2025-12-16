<?php

namespace Imee\Service\Domain\Service\Cs\Statistics;

use Imee\Service\Domain\Context\Cs\Statistics\FirstChatRecord\ListContext;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\FirstChatRecord\ConfigProcess;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\FirstChatRecord\ListProcess;

class FirstChatRecordService
{
	public function list($params)
	{
		$context = new ListContext($params);
		$process = new ListProcess($context);
		return $process->handle();
	}

	public function config()
	{
		$process = new ConfigProcess();
		return $process->handle();
	}
}