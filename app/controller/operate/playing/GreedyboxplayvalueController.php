<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Operate\Playing\Teen;
use Imee\Controller\Validation\Operate\Play\Tarot\ValueAddValidation;
use Imee\Controller\Validation\Operate\Play\Tarot\ValueEditValidation;
use Imee\Models\Xs\XsUidGameBlackList;
use Imee\Service\Operate\Play\KvBaseService;

class GreedyboxplayvalueController extends BaseController
{
    /**
     * @var KvBaseService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService();
        $this->params['game_id'] = XsUidGameBlackList::GREEDY_BOX;
    }
    
    /**
     * @page greedyboxplayvalue
     * @name Greedy Box Value
     */
    public function mainAction()
    {
    }
    
    /**
     * @page greedyboxplayvalue
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getValueList($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }
    
    /**
     * @page greedyboxplayvalue
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'greedyboxplayvalue', model_id = 'id')
     */
    public function createAction()
    {
        ValueAddValidation::make()->validators($this->params);
        $data = $this->service->setValue($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page greedyboxplayvalue
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'greedyboxplayvalue', model_id = 'id')
     */
    public function modifyAction()
    {
        ValueEditValidation::make()->validators($this->params);
        $data = $this->service->setValue($this->params);
        return $this->outputSuccess($data);
    }
}