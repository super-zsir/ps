<?php


namespace Imee\Service\Domain\Service\Csms;


use Imee\Service\Domain\Service\Csms\Context\Kanban\AutomationListContext;
use Imee\Service\Domain\Service\Csms\Process\Kanban\AutomationListProcess;

/**
 * 内容安全管理-看板
 * Class CsmsKanbanService
 * @package Imee\Service\Domain\Service\Csms
 */
class CsmsKanbanService
{

    /**
     * 自动化看板数据
     * @param array $params
     */
    public function automation($params = [])
    {
        $context = new AutomationListContext($params);
        $process = new AutomationListProcess($context);
        return $process->handle();
    }



}