<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Vipsend\LimitCreateValidation;
use Imee\Service\Operate\Vip\VipSendLimitService;

class VipsendlimitController extends BaseController
{
    /** @var VipSendLimitService */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new VipSendLimitService();
    }
    
    /**
     * @page vipsendlimit
     * @name vip发放限制
     */
    public function mainAction()
    {
    }
    
    /**
     * @page vipsendlimit
     * @point 列表
     */
    public function listAction()
    {
        $result = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($result['data'] ?? [], array('total' => $result['total'] ?? 0));
    }
    
    /**
     * @page vipsendlimit
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'vipsendlimit', model_id = 'id')
     */
    public function createAction()
    {
        LimitCreateValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page vipsendlimit
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'vipsendlimit', model_id = 'id')
     */
    public function modifyAction()
    {
        LimitCreateValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page vipsendlimit
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'vipsendlimit', model_id = 'id')
     */
    public function deleteAction()
    {
        $data = $this->service->delete($this->params);
        return $this->outputSuccess($data);
    }
}