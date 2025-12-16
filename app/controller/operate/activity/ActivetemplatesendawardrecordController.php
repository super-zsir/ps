<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Activity\ActiveTemplateSendAwardRecordExport;
use Imee\Service\Operate\Activity\ActiveSendAwardRecordService;

class ActivetemplatesendawardrecordController extends BaseController
{
    /**
     * @var ActiveSendAwardRecordService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActiveSendAwardRecordService();
    }

    /**
     * @page activetemplatesendawardrecord
     * @name 活动模版发奖记录
     */
    public function mainAction()
    {
    }

    /**
     * @page activetemplatesendawardrecord
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page activetemplatesendawardrecord
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'activetemplatesendawardrecord';
        ExportService::addTask($this->uid, 'activetemplatesendawardrecord.xlsx', [ActiveTemplateSendAwardRecordExport::class, 'export'], $this->params, '活动模版发奖记录导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}