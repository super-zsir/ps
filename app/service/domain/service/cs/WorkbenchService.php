<?php
namespace Imee\Service\Domain\Service\Cs;

use Imee\Controller\Validation\Cs\Workbench\SessionListValidation;
use Imee\Service\Domain\Context\Cs\Workbench\ActiveServiceContext;
use Imee\Service\Domain\Context\Cs\Workbench\SessionListContext;
use Imee\Service\Domain\Service\Cs\Processes\Workbench\ActiveServiceProcess;
use Imee\Service\Domain\Service\Cs\Processes\Workbench\ChatIndexProcess;
use Imee\Service\Domain\Service\Cs\Processes\Workbench\ChatInitProcess;
use Imee\Service\Domain\Service\Cs\Processes\Workbench\SessionListProcess;

class WorkbenchService
{
    public function chatInit()
    {
        $process = new ChatInitProcess();
        return $process->handle();
    }

    public function chatSessionList(SessionListContext $context)
    {
        $process = new SessionListProcess($context);
        return $process->handle();
    }

    public function chatIndex()
    {
        $process = new ChatIndexProcess();
        return $process->handle();
    }

    public function activeService(ActiveServiceContext $context)
    {
        $process = new ActiveServiceProcess($context);
        return $process->handle();
    }
}
