<?php


namespace Imee\Controller\Operate\Gift;


use Imee\Controller\BaseController;
use Imee\Service\Operate\Gift\TagService;
use Imee\Controller\Validation\Operate\Gift\{TagCreateValidation, TagModifyValidation};

class GifttagController extends BaseController
{
    /**
     * @var TagService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new TagService();
    }

    /**
     * @page gifttag
     * @name 运营系统-礼物管理-标签管理
     */
    public function mainAction()
    {
    }

    /**
     * @page gifttag
     * @point list
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page  gifttag
     * @point create
     * @logRecord(content = "创建", action = "0", model = "XsCommodityTag", model_id = "id")
     */
    public function createAction()
    {
        TagCreateValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  gifttag
     * @point modify
     * @logRecord(content = "修改", action = "1", model = "XsCommodityTag", model_id = "id")
     */
    public function modifyAction()
    {
        TagModifyValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  gifttag
     * @point delete
     * @logRecord(content = "删除", action = "2", model = "XsCommodityTag", model_id = "id")
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}