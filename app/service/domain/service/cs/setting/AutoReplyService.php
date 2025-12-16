<?php
namespace Imee\Service\Domain\Service\Cs\Setting;

use Imee\Service\Domain\Context\Cs\Setting\AutoReply\ListContext;
use Imee\Service\Domain\Context\Cs\Setting\AutoReply\ModifyContext;
use Imee\Service\Domain\Context\Cs\Setting\AutoReply\RemoveContext;
use Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply\ConfigProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply\ListProcess;
use Imee\Service\Domain\Context\Cs\Setting\AutoReply\CreateContext;
use Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply\CreateProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply\ModifyProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply\RemoveProcess;

class AutoReplyService
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

    public function modify($params)
    {
		$context = new ModifyContext($params);
        $process = new ModifyProcess($context);
        return $process->handle();
    }

    public function remove($params)
    {
		$context = new RemoveContext($params);
        $process = new RemoveProcess($context);
        return $process->handle();
    }

    public function getConfig()
    {
        $process = new ConfigProcess();
        return $process->handle();
    }
}
