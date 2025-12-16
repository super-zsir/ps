<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Export\Operate\Activity\ActivityTaskGamePlayMultiwireExport;
use Imee\Service\Operate\Activity\ActivityTaskGamePlayMultiwireService;

class ActivitymultitaskdataController extends BaseController
{
    /**
     * @var ActivityTaskGamePlayMultiwireService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityTaskGamePlayMultiwireService();
    }

    /**
     * @page activitymultitaskdata
     * @name 数据明细
     */
    public function mainAction()
    {
    }

    /**
     * @page activitymultitaskdata
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getExportList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page activitymultitaskdata
     * @point 导出
     */
    public function exportAction()
    {
        return $this->syncExportWork('activityTaskGamePlayMultiwireExport', ActivityTaskGamePlayMultiwireExport::class, $this->params);
    }
}