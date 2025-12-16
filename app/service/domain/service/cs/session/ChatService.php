<?php

namespace Imee\Service\Domain\Service\Cs\Session;

use Imee\Service\Domain\Context\Cs\Session\Chat\ListContext;
use Imee\Service\Domain\Service\Cs\Processes\Session\Chat\ConfigProcess;
use Imee\Service\Domain\Service\Cs\Processes\Session\Chat\ListProcess;

class ChatService
{
    public function getList(ListContext $context)
    {
        $process = new ListProcess($context);
        return $process->handle();
    }

    public function getConfig()
    {
        $process = new ConfigProcess();
        return $process->handle();
    }
}
