<?php

namespace Imee\Controller\Operate\Commodity;

use Imee\Controller\BaseController;
use Imee\Exception\ApiException;
use Imee\Service\Commodity\CommodityVerifyService;

class CommodityverifyController extends BaseController
{

    /**
     * @var CommodityVerifyService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CommodityVerifyService();
    }

    /**
     * @page commodityverify
     * @name 运营系统-物品管理-物品审核白名单管理
     */
    public function mainAction()
    {
    }

    /**
     * @page commodityverify
     * @point list
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page  commodityverify
     * @point create
     * @logRecord(content = "创建", action = "0", model = "BmsCommodityVerify", model_id = "id")
     * @throws ApiException
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  commodityverify
     * @point modify
     * @logRecord(content = "过期", action = "1", model = "BmsCommodityVerify", model_id = "id")
     * @throws ApiException
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}