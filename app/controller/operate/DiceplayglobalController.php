<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Dice\DicePlayGlobalExport;
use Imee\Service\Operate\Play\Dice\GlobalService;

class DiceplayglobalController extends BaseController
{

    /** @var GlobalService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GlobalService();
    }

    /**
     * @page diceplayglobal
     * @name Dice玩法配置-全局数据查询
     */
    public function mainAction()
    {
    }

    /**
     * @page diceplayglobal
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
        $this->params['guid'] = 'diceplayglobal';
        ExportService::addTask($this->uid, 'diceplayglobal.xlsx', [DicePlayGlobalExport::class, 'export'], $this->params, 'Dice全局数据查询导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}