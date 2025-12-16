<?php


namespace Imee\Controller\Operate\Coupon;


use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Coupon\GameCouponIssuedAddValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsstCouponIssued;
use Imee\Service\Operate\Coupon\GameCouponIssuedService;

class GamecouponissuedController extends BaseController
{
    use ImportTrait;

    /**
     * @var GameCouponIssuedService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GameCouponIssuedService();
    }

    /**
     * @page gamecouponissued
     * @name 运营管理-游戏优惠券下发
     */
    public function mainAction()
    {
    }

    /**
     * @page gamecouponissued
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page gamecouponissued
     * @point 下发
     * @logRecord(content = '创建', action = '0', model = 'gamecouponissued', model_id = 'id')
     */
    public function addAction()
    {
        GameCouponIssuedAddValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page gamecouponissued
     * @point 批量下发
     * @logRecord(content = '创建', action = '0', model = 'gamecouponissued', model_id = 'id')
     */
    public function addBatchAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values(XsstCouponIssued::uploadFields()), [], 'couponIssued');
            exit;
        }
        [$success, $msg, $data] = $this->uploadCsv(array_keys(XsstCouponIssued::uploadFields()));
        if (!$success) {
            return $this->outputError('-1', $msg);
        }

        list($flg, $rec) = $this->service->addBatch($this->params, $data['data']);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page gamecouponissued
     * @point 扣减
     * @logRecord(content = '扣减', action = '1', model = 'gamecouponissued', model_id = 'id')
     */
    public function subAction()
    {
        list($flg, $rec) = $this->service->sub($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page gamecouponissued
     * @point 审核
     * @logRecord(content = '审核', action = '1', model = 'gamecouponissued', model_id = 'id')
     */
    public function auditAction()
    {
        list($flg, $rec) = $this->service->audit($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page gamecouponissued
     * @point 批量审核
     * @logRecord(content = '批量审核', action = '1', model = 'gamecouponissued', model_id = 'id')
     */
    public function auditBatchAction()
    {
        list($flg, $rec) = $this->service->auditBatch($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page gamecouponissued
     * @point 配置
     */
    public function configAction()
    {
        return $this->outputSuccess($this->service->config());
    }

}