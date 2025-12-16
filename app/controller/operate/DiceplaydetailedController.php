<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Dice\DicePlayDetailedExport;
use Imee\Service\Operate\Play\Dice\DetailedService;

class DiceplaydetailedController extends BaseController
{
    /** @var DetailedService $service */
    private $service;

    protected function onConstruct()
    {
        $this->allowSort = ['id'];
        parent::onConstruct();
        $this->service = new DetailedService();
    }

    /**
     * @page diceplaydetailed
     * @name Dice玩法配置-明细数据查询
     */
    public function mainAction()
    {
    }

    /**
     * @page diceplaydetailed
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page diceplaydetailed
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'diceplaydetailed';
        ExportService::addTask($this->uid, 'diceplaydetailed.xlsx', [DicePlayDetailedExport::class, 'export'], $this->params, 'Dice明细数据查询导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}