<?php


namespace Imee\Service\Domain\Service\Audit;


use Imee\Service\Domain\Service\Audit\Context\Dirtysum\DirtySumListContext;
use Imee\Service\Domain\Service\Audit\Processes\Dirtysum\DirtySumListProcess;

/**
 * 敏感词触发数据
 * Class DirtySumService
 * @package Imee\Service\Domain\Service\Audit
 */
class DirtySumService
{


    public function list($params = [])
    {
        $context = new DirtySumListContext($params);
        $process = new DirtySumListProcess($context);
        return $process->handle();
    }

}