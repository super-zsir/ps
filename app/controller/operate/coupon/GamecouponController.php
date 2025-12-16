<?php

namespace Imee\Controller\Operate\Coupon;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Coupon\GameCouponService;

class GamecouponController extends BaseController
{
    /**
     * @var GameCouponService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GameCouponService();
    }

    /**
     * @page gamecoupon
     * @name 运营管理-游戏优惠券配置
     */
    public function mainAction()
    {
    }

    /**
     * @page gamecoupon
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page gamecoupon
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'gamecoupon', model_id = 'id')
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page gamecoupon
     * @point 修改
     * @logRecord(content = '创建', action = '1', model = 'gamecoupon', model_id = 'id')
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

}