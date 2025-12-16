<?php

namespace Imee\Controller\Operate\Commodity;

use Imee\Controller\BaseController;
use Imee\Exception\ApiException;
use Imee\Service\Commodity\CommodityRecommendService;

class CommodityrecommendController extends BaseController
{

    /**
     * @var CommodityRecommendService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CommodityRecommendService();
    }

    /**
     * @page commodityrecommend
     * @name 运营系统-物品管理- 推荐管理
     */
    public function mainAction()
    {
    }

    /**
     * @page commodityrecommend
     * @point list
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page  commodityrecommend
     * @point create
     * @logRecord(content = "创建", action = "0", model = "XsCommodityRecommend", model_id = "id")
     * @throws ApiException
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  commodityrecommend
     * @point modify
     * @logRecord(content = "修改", action = "1", model = "XsCommodityRecommend", model_id = "id")
     * @throws ApiException
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  commodityrecommend
     * @point delete
     * @logRecord(content = "删除", action = "2", model = "XsCommodityRecommend", model_id = "id")
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}