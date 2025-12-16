<?php

namespace Imee\Service\Domain\Service\Cs\Statistics;

use Imee\Service\Domain\Context\Cs\Statistics\ChatTypeStat\ListContext;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\ChatTypeStat\ConfigProcess;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\ChatTypeStat\ListProcess;

class ChatTypeStatService
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