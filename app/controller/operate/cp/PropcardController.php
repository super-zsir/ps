<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Cp\PropCardAddValidation;
use Imee\Controller\Validation\Operate\Cp\PropCardEditValidation;
use Imee\Models\Xs\XsPropCard;
use Imee\Service\Operate\Cp\PropCardService;

class PropcardController extends BaseController
{
    /** @var PropCardService $service */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PropCardService();
    }

    /**
     * @page propcard
     * @name 道具上架
     */
    public function mainAction()
    {
    }

    /**
     * @page propcard
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        }
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page propcard
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'propcard', model_id = 'id')
     */
    public function createAction()
    {
        PropCardAddValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page propcard
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'propcard', model_id = 'id')
     */
    public function modifyAction()
    {
        PropCardEditValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page propcard
     * @point 下架
     * @logRecord(content = '下架', action = '1', model = 'propcard', model_id = 'id')
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params, XsPropCard::DELETED_YES);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page propcard
     * @point 获取道具类型
     */
    public function getTypeAction()
    {
        return $this->outputSuccess($this->service->getType($this->params));
    }

    /**
     * @page propcard
     * @point 上架
     * @logRecord(content = '上架', action = '1', model = 'propcard', model_id = 'id')
     */
    public function onShelfAction()
    {
        list($flg, $rec) = $this->service->delete($this->params, XsPropCard::DELETED_NO);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}