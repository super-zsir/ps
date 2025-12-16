<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\VipsendExport;
use Imee\Service\Operate\VipsendService;
use Imee\Controller\Validation\Operate\Viprecord\DetailListValidation;
use Imee\Controller\Validation\Operate\Viprecord\DetailExportValidation;

class VipsenddetailController extends BaseController
{
    /**
     * @var VipsendService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new VipsendService();
    }

    /**
     * @page vipsenddetail
     * @name VIP发放任务明细
     */
    public function mainAction()
    {
    }

    /**
     * @page vipsenddetail
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        DetailListValidation::make()->validators($params);
        $result = $this->service->getDetailList($params);
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }

    /**
     * @page vipsenddetail
     * @point 导出
     */
    public function exportAction()
    {
        $params = $this->trimParams($this->params);
        DetailExportValidation::make()->validators($params);
        $this->params['guid'] = 'vipsenddetail';
        ExportService::addTask($this->uid, 'vipsenddetail.xlsx', [VipsendExport::class, 'export'], $this->params, 'VIP发放明细导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}
