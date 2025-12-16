<?php
namespace Imee\Service\Domain\Service\Cs\Setting;

use Imee\Service\Domain\Context\Cs\Setting\Channel\ModifyContext;
use Imee\Service\Domain\Service\Cs\Processes\Setting\Channel\ConfigProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\Channel\ListProcess;
use Imee\Service\Domain\Context\Cs\Setting\Channel\CreateContext;
use Imee\Service\Domain\Service\Cs\Processes\Setting\Channel\CreateProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\Channel\ModifyProcess;

class ChannelService
{
    public function getList(): array
    {
        $process = new ListProcess();
        return $process->handle();
    }

    public function create($params)
    {
		$context = new CreateContext($params);
        $process = new CreateProcess($context);
        return $process->handle();
    }

	public function modify($params)
	{
		$context = new ModifyContext($params);
		$process = new ModifyProcess($context);
		return $process->handle();
	}

	public function config()
	{
		$process = new ConfigProcess();
		return $process->handle();
	}
}
