<?php

namespace Imee\Service\Domain\Service\Cs\Statistics;

use Imee\Service\Domain\Context\Cs\Statistics\AutoChatLog\ListContext;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChatLog\ListProcess;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChatLog\ConfigProcess;
use Imee\Service\Domain\Context\Cs\Statistics\AutoChatLog\AutoReplyListContext;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChatLog\AutoReplyListProcess;

/**
 * 自动应答统计服务
 */
class AutoChatLogService
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

    public function getAutoReplyStat($params)
    {
		$context = new AutoReplyListContext($params);
        $process = new AutoReplyListProcess($context);
        return $process->handle();
    }
}
