<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Cp\PkPropCardUseRecordExport;
use Imee\Service\Operate\Cp\PkPropCardUseRecordService;

class PkpropuserecordController extends BaseController
{
    /**
     * @var PkPropCardUseRecordService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PkPropCardUseRecordService();
    }

    /**
     * @page pkpropuserecord
     * @name PK道具卡的使用记录
     */
    public function mainAction()
    {
    }

    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }


    /**
     * @page  pkpropuserecord
     * @point 导出
     */
    public function exportAction()
    {
        ExportService::addTask($this->uid, 'pk_prop_use_record_export.csv', [PkPropCardUseRecordExport::class, 'export'], $this->params, 'PK道具卡的使用记录');
        ExportService::showHtml();
    }
}