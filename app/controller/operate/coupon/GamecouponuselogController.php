<?php


namespace Imee\Controller\Operate\Coupon;


use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Coupon\GameCouponUseLog;
use Imee\Service\Operate\Coupon\GameCouponService;

class GamecouponuselogController extends BaseController
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
     * @page gamecouponuselog
     * @name 运营管理-游戏优惠券配置-游戏优惠券账单明细
     */
    public function mainAction()
    {
    }

    /**
     * @page gamecouponuselog
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getUseLogListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page gamecouponuselog
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'gamecouponuselog';
        ExportService::addTask($this->uid, 'gamecouponuselog.xlsx', [GameCouponUseLog::class, 'export'], $this->params, '游戏优惠券账单明细导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }

}