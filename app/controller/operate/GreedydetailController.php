<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Play\Greedy\GreedyDetailExport;
use Imee\Service\Operate\Play\Greedy\DetailedService;

class GreedydetailController extends BaseController
{
    /** @var DetailedService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new DetailedService();
    }

    /**
     * @page greedydetail
     * @name 玩法管理-GreedyStar玩法配置-GreedyStar 明细查询
     */
    public function mainAction()
    {
    }

    /**
     * @page greedydetail
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page greedydetail
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'greedydetail';
        ExportService::addTask($this->uid, 'greedydetail.xlsx', [GreedyDetailExport::class, 'export'], $this->params, 'GreedyStar明细查询导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}