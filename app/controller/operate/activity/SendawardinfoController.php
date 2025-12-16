<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Activity\SendAwardInfoExport;
use Imee\Service\Operate\Activity\ActivityAccountManageService;

class SendawardinfoController extends BaseController
{
    /**
     * @var ActivityAccountManageService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityAccountManageService();
    }

    /**
     * @page sendawardinfo
     * @name 查看发奖账单明细
     */
    public function mainAction()
    {

    }

    /**
     * @page sendawardinfo
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getSendList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page sendawardinfo
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'sendawardinfo';
        ExportService::addTask($this->uid, 'sendawardinfo.xlsx', [SendAwardInfoExport::class, 'export'], $this->params, '查看发奖账单明细导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}