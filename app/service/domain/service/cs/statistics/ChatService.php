<?php

namespace Imee\Service\Domain\Service\Cs\Statistics;

use Imee\Service\Domain\Context\Cs\Statistics\Chat\ListContext;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\Chat\ListProcess;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\Chat\ConfigProcess;

/**
 * 客服满意度统计
 */
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
