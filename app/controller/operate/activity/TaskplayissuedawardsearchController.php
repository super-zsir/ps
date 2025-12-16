<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\TaskPlayIssuedAwardListValidation;
use Imee\Export\Operate\Activity\TaskPlayIssuedAwardExport;
use Imee\Service\Operate\Activity\ActivityTaskGamePlayService;

class TaskplayissuedawardsearchController extends BaseController
{
    /**
     * @var ActivityTaskGamePlayService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityTaskGamePlayService();
    }

    /**
     * @page taskplayissuedawardsearch
     * @name 任务玩法发奖记录
     */
    public function mainAction()
    {
    }

    /**
     * @page taskplayissuedawardsearch
     * @point 列表
     */
    public function listAction()
    {
        TaskPlayIssuedAwardListValidation::make()->validators($this->params);
        $list = $this->service->getAwardList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page taskplayissuedawardsearch
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'taskplayissuedawardsearch';
        ExportService::addTask($this->uid, 'taskplayissuedawardsearch.xlsx', [TaskPlayIssuedAwardExport::class, 'export'], $this->params, '任务玩法发奖记录导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}