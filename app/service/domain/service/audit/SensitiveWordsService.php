<?php

namespace Imee\Service\Domain\Service\Audit;

use Imee\Service\Domain\Context\Audit\SensitiveWords\ListContext;
use Imee\Service\Domain\Service\Audit\Processes\SensitiveWords\ConfigProcess;
use Imee\Service\Domain\Service\Audit\Processes\SensitiveWords\ListProcess;

class SensitiveWordsService
{
    public function list($params)
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
}
