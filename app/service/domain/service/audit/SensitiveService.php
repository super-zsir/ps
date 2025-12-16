<?php

namespace Imee\Service\Domain\Service\Audit;

use Imee\Service\Domain\Context\Audit\Sensitive\AddContext;
use Imee\Service\Domain\Context\Audit\Sensitive\RemoveContext;
use Imee\Service\Domain\Context\Audit\Sensitive\ListContext;
use Imee\Service\Domain\Context\Audit\Sensitive\ModifyContext;
use Imee\Service\Domain\Service\Audit\Processes\Sensitive\ListProcess;
use Imee\Service\Domain\Service\Audit\Processes\Sensitive\ConfigProcess;
use Imee\Service\Domain\Service\Audit\Processes\Sensitive\RemoveProcess;
use Imee\Service\Domain\Service\Audit\Processes\Sensitive\AddOrModifyProcess;

class SensitiveService
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

    public function remove($params)
    {
        $context = new RemoveContext($params);
        $process = new RemoveProcess($context);
        return $process->handle();
    }

    public function add($params)
    {
        $context = new AddContext($params);
        return $this->modify($context->toArray());
    }

    public function modify($params)
    {
        $context = new ModifyContext($params);
        $process = new AddOrModifyProcess($context);
        return $process->handle();
    }
}
