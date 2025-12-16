<?php

namespace Imee\Service\Domain\Service\Cs\Statistics;

use Imee\Service\Domain\Context\Cs\Statistics\ManualChatService\ListContext;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\ManualChatService\ListProcess;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\ManualChatService\ConfigProcess;

/**
 * 客服系统统计服务
 */
class ManualChatServiceService
{
    public function getManualChatServiceList($params)
    {
        $context = new ListContext($params);
        $process = new ListProcess($context);
        return $process->handle();
    }

    public function getManualChatServiceConfig()
    {
        $process = new ConfigProcess();
        return $process->handle();
    }
}
