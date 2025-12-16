<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\PayActivityGiftBagLogListValidation;
use Imee\Export\Operate\Payactivity\PayActivityGiftBagLogExport;
use Imee\Service\Operate\Payactivity\PayActivityGiftBagLogService;

class PayactivitygiftbaglogController extends BaseController
{
    /**
     * @var PayActivityGiftBagLogService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PayActivityGiftBagLogService();
    }

    /**
     * @page payactivitygiftbaglog
     * @name 累充发奖记录
     */
    public function mainAction()
    {
    }

    /**
     * @page payactivitygiftbaglog
     * @point 列表
     */
    public function listAction()
    {
        PayActivityGiftBagLogListValidation::make()->validators($this->params);
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page payactivitygiftbaglog
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'payactivitygiftbaglog';
        ExportService::addTask($this->uid, 'payactivitygiftbaglog.xlsx', [PayActivityGiftBagLogExport::class, 'export'], $this->params, '充值活动发奖记录导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}