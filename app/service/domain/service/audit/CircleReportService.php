<?php


namespace Imee\Service\Domain\Service\Audit;

use Imee\Service\Domain\Service\Audit\Context\CircleReport\HistoryContext;
use Imee\Service\Domain\Service\Audit\Context\CircleReport\MultPassContext;
use Imee\Service\Domain\Service\Audit\Context\CircleReport\TaskListContext;
use Imee\Service\Domain\Service\Audit\Processes\CircleReport\ConfigProcess;
use Imee\Service\Domain\Service\Audit\Processes\CircleReport\HistoryProcess;
use Imee\Service\Domain\Service\Audit\Processes\CircleReport\MultPassProcess;
use Imee\Service\Domain\Service\Audit\Processes\CircleReport\RhistoryProcess;
use Imee\Service\Domain\Service\Audit\Processes\CircleReport\TaskListProcess;

/**
 * 朋友圈举报
 * Class CircleReportService
 * @package Imee\Service\Domain\Service\Audit\Workbench
 */
class CircleReportService
{


    public function getTaskList(array $where = [], $task_ids = [])
    {
        if ($task_ids) {
            $where['rpids'] = $task_ids;
        }
        $context = new TaskListContext($where);
        $process = new TaskListProcess($context);
        return $process->handle();
    }


    public function getCheckedList($wheres = [])
    {
        return $this->getTaskList($wheres);
    }


    public function multpass($params = [])
    {
        $context = new MultPassContext($params);
        $process = new MultPassProcess($context);
        return $process->handle();
    }

    public function history($params = [])
    {
        $context = new HistoryContext($params);
        $process = new HistoryProcess($context);
        return $process->handle();
    }


    public function rhistory($params = [])
    {
        $context = new HistoryContext($params);
        $process = new RhistoryProcess($context);
        return $process->handle();
    }



    public function getConfig()
    {
        $process = new ConfigProcess();
        return $process->handle();
    }


}
