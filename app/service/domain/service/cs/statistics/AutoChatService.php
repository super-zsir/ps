<?php

namespace Imee\Service\Domain\Service\Cs\Statistics;

use Imee\Service\Domain\Context\Cs\Statistics\AutoChat\DetailContext;
use Imee\Service\Domain\Context\Cs\Statistics\AutoChat\ListContext;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChat\DetailProcess;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChat\ListProcess;
use Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChat\ConfigProcess;

/**
 * 自动回复数据统计
 */
class AutoChatService
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

    public function detail($params)
    {
		$context = new DetailContext($params);
        $process = new DetailProcess($context);
        return $process->handle();
    }
}
