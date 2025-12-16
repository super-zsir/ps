<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Fishing\FishingValueAddValidation;
use Imee\Controller\Validation\Operate\Play\Fishing\FishingValueEditValidation;
use Imee\Service\Operate\Play\Fishing\FishingValueService;

class FishingvalueController extends BaseController
{
    /** @var FishingValueService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FishingValueService();
    }

    /**
     * @page fishingvalue
     * @name Fishing Value
     */
    public function mainAction()
    {
    }

    /**
     * @page fishingvalue
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page fishingvalue
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'fishingvalue', model_id = 'id')
     */
    public function createAction()
    {
        FishingValueAddValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->create($this->params));
    }

    /**
     * @page fishingvalue
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'fishingvalue', model_id = 'id')
     */
    public function modifyAction()
    {
        FishingValueEditValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->modify($this->params));
    }
}