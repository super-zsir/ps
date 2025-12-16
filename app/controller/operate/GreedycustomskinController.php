<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Greedy\GreedyCustomSkinAddValidation;
use Imee\Controller\Validation\Operate\Play\Greedy\GreedyCustomSkinDeleteValidation;
use Imee\Controller\Validation\Operate\Play\Greedy\GreedyCustomSkinEditValidation;
use Imee\Service\Operate\Play\Greedy\GreedyCustomSkinService;

class GreedycustomskinController extends BaseController
{
    /**
     * @var GreedyCustomSkinService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GreedyCustomSkinService();
    }
    
    /**
     * @page greedycustomskin
     * @name GreedyStar 定制皮肤管理标签
     */
    public function mainAction()
    {
    }
    
    /**
     * @page greedycustomskin
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page greedycustomskin
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'greedycustomskin', model_id = 'skin_id')
     */
    public function createAction()
    {
        GreedyCustomSkinAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page greedycustomskin
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'greedycustomskin', model_id = 'skin_id')
     */
    public function modifyAction()
    {
        GreedyCustomSkinEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page greedycustomskin
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'greedycustomskin', model_id = 'skin_id')
     */
    public function deleteAction()
    {
        GreedyCustomSkinDeleteValidation::make()->validators($this->params);
        $data = $this->service->delete($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page greedycustomskin
     * @point 过期
     * @logRecord(content = '过期', action = '1', model = 'greedycustomskin', model_id = 'skin_id')
     */
    public function expireAction()
    {
        GreedyCustomSkinDeleteValidation::make()->validators($this->params);
        $data = $this->service->expire($this->params);
        return $this->outputSuccess($data);
    }
}