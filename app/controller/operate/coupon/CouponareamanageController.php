<?php


namespace Imee\Controller\Operate\Coupon;


use Imee\Controller\BaseController;
use Imee\Service\Operate\Coupon\CouponAreaManageService;

class CouponareamanageController extends BaseController
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
     * @page couponareamanage
     * @name 运营管理-大区账户管理
     */
    public function mainAction()
    {
    }

    /**
     * @page couponareamanage
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page couponareamanage
     * @point create
     * @logRecord(content = '创建', action = '0', model = 'couponareamanage', model_id = 'id')
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page couponareamanage
     * @point delete
     * @logRecord(content = '创建', action = '2', model = 'couponareamanage', model_id = 'id')
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page couponareamanage
     * @point 增加余额
     * @logRecord(content = '创建', action = '1', model = 'couponareamanage', model_id = 'id')
     */
    public function addAmountAction()
    {
        list($flg, $rec) = $this->service->addAmount($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page couponareamanage
     * @point 扣减余额
     * @logRecord(content = '创建', action = '1', model = 'couponareamanage', model_id = 'id')
     */
    public function subAmountAction()
    {
        list($flg, $rec) = $this->service->subAmount($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

}