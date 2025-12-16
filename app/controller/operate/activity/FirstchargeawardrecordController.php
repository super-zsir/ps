<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Export\Operate\Activity\Firstcharge\FirstChargeAwardRecordExport;
use Imee\Service\Operate\Activity\Firstcharge\FirstChargeAwardRecordService;

class FirstchargeawardrecordController extends BaseController
{
    /**
     * @var FirstChargeAwardRecordService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FirstChargeAwardRecordService();
    }

    /**
     * @page firstchargeawardrecord
     * @name 首充发奖记录
     */
    public function mainAction()
    {
    }

    /**
     * @page firstchargeawardrecord
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page firstchargeawardrecord
     * @point 导出
     */
    public function exportAction()
    {
        return $this->syncExportWork('firstChargeAwardRecordExport', FirstChargeAwardRecordExport::class, $this->params);
    }
}