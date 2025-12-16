<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\TotalAddValidation;
use Imee\Controller\Validation\Operate\Play\Tarot\TotalEditValidation;
use Imee\Models\Xs\XsUidGameBlackList;
use Imee\Service\Operate\Play\KvBaseService;

class PslottotalController extends BaseController
{
    /**
     * @var KvBaseService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KvBaseService();
        $this->params['game_id'] = XsUidGameBlackList::GREEDY_SLOT;
    }
    
    /**
     * @page pslottotal
     * @name Greedyslot Total
     */
    public function mainAction()
    {
    }
    
    /**
     * @page pslottotal
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getTotalList($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }
    
    /**
     * @page pslottotal
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'pslottotal', model_id = 'id')
     */
    public function createAction()
    {
        TotalAddValidation::make()->validators($this->params);
        $data = $this->service->setTotal($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page pslottotal
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'pslottotal', model_id = 'id')
     */
    public function modifyAction()
    {
        TotalEditValidation::make()->validators($this->params);
        $data = $this->service->setTotal($this->params);
        return $this->outputSuccess($data);
    }
}