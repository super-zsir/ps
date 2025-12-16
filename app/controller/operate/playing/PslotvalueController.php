<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Pslot\ValueService;

class PslotvalueController extends BaseController
{
    /**
     * @var ValueService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ValueService();
    }
    
    /**
     * @page pslotvalue
     * @name Greedyslot Value
     */
    public function mainAction()
    {
    }
    
    /**
     * @page pslotvalue
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getValueList($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }
    
    /**
     * @page pslotvalue
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'pslotvalue', model_id = 'id')
     */
    public function createAction()
    {
        $data = $this->service->setValue($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page pslotvalue
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'pslotvalue', model_id = 'id')
     */
    public function modifyAction()
    {
        $data = $this->service->setValue($this->params);
        return $this->outputSuccess($data);
    }
}