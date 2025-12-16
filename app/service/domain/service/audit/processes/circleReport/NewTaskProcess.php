<?php

namespace Imee\Service\Domain\Service\Audit\Processes\CircleReport;

use Imee\Helper\Constant\AuditConstant;
use Imee\Models\Bms\XsstKefuTaskCirclereport;
use Imee\Models\Bms\XsstKefuTaskText;
use Imee\Service\Domain\Service\Csms\Context\Staff\NewTaskContext;

/**
 * 朋友圈举报 原表没有app，所以无法只有用原表当任务池
 * Class NewTaskProcess
 * @package Imee\Service\Domain\Service\Audit\Processes\CircleReportService
 */
class NewTaskProcess
{
    protected $context;

    public function __construct(NewTaskContext $context)
    {
        $this->context = $context;
    }
    
    public function handle()
    {
        $conditions = [];
        $bind = [];

        if ($this->context->power) {
            $conditions[] = AuditConstant::NEW_TASK_FIELD . " in ({choice:array})";
            $bind['choice'] = $this->context->power;
        }

        if (isset($this->context->where['app_ids']) && !empty($this->context->where['app_ids'])) {
            $conditions[] = "app_id in ({app_ids:array})";
            $bind['app_ids'] = $this->context->where['app_ids'];
        }

        if ($this->context->oldIds) {
            $conditions[] = 'id not in ({id:array})';
            $bind['id'] = $this->context->oldIds;
        }

        $task = XsstKefuTaskCirclereport::find([
            'conditions' => implode(' and ', $conditions),
            'bind' => $bind,
            'order' => 'create_time asc',
            'limit' => $this->context->num
        ])->toArray();

        $data = array_column($task, 'id');
        return $data;
    }
}
