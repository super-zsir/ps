<?php


namespace Imee\Controller\Operate\Coupon;


use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Coupon\CouponAreaLog;
use Imee\Service\Operate\Coupon\CouponAreaManageService;

class CouponarealogController extends BaseController
{
    /**
     * @var CouponAreaManageService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CouponAreaManageService();
    }

    /**
     * @page couponarealog
     * @name 运营管理-大区账户管理-账户操作记录
     */
    public function mainAction()
    {
    }

    /**
     * @page couponarealog
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getCouponAreaLogListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page couponarealog
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'couponarealog';
        ExportService::addTask($this->uid, 'couponarealog.xlsx', [CouponAreaLog::class, 'export'], $this->params, '账户操作记录导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}