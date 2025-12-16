<?php

namespace Imee\Controller\Operate\Commodity;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Export\CommodityGroupExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsCommodityGroup;
use Imee\Service\Commodity\CommodityGroupService;

class CommoditygroupController extends BaseController
{
    use ImportTrait;

    /**
     * @var CommodityGroupService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CommodityGroupService();
    }

    /**
     * @page commoditygroup
     * @name 运营系统-物品管理-spu管理
     */
    public function mainAction()
    {
    }

    /**
     * @page commoditygroup
     * @point list
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page  commoditygroup
     * @point create
     * @logRecord(content = "创建", action = "0", model = "XsCommodityGroup", model_id = "id")
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  commoditygroup
     * @point modify
     * @logRecord(content = "修改", action = "1", model = "XsCommodityGroup", model_id = "id")
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  commoditygroup
     * @point delete
     * @logRecord(content = "删除", action = "2", model = "XsCommodityGroup", model_id = "id")
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  commoditygroup
     * @point import
     * @logRecord(content = "导入", action = "0", model = "XsCommodityGroup", model_id = "id")
     */
    public function importAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values(XsCommodityGroup::$nameBigarea), [], 'commodityGroup');
            exit;
        }

        [$success, $msg, $data] = $this->uploadCsv(array_keys(XsCommodityGroup::$nameBigarea));
        if (!$success) {
            return $this->outputError('-1', $msg);
        }

        list($flg, $rec) = $this->service->import($data['data']);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  commoditygroup
     * @point export
     */
    public function exportAction()
    {
        $count = $this->service->getCount($this->params);
        if ($count > 100000) {
            return $this->outputError('-1', 'Exceeding the maximum limit of 100000');
        }
        return $this->syncExportWork('commoditygroup', CommodityGroupExport::class, $this->params);
    }
}