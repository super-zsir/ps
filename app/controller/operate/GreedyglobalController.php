<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Play\Greedy\GreedyGlobalExport;
use Imee\Service\Operate\Play\Greedy\GlobalService;

class GreedyglobalController extends BaseController
{
    /** @var GlobalService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GlobalService();
    }

    /**
     * @page greedyglobal
     * @name 玩法管理-GreedyStar玩法配置-GreedyStar 全局查询
     */
    public function mainAction()
    {
    }

    /**
     * @page greedyglobal
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page greedyglobal
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'greedyglobal';
        ExportService::addTask($this->uid, 'greedyglobal.xlsx', [GreedyGlobalExport::class, 'export'], $this->params, 'GreedyStar全局查询导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}