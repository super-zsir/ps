<?php

namespace Imee\Controller\Super;

use Imee\Controller\BaseController;
use Imee\Service\Super\SuperOperationlogService;

class SuperoperationlogController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }
    
    /**
     * @page superoperationlog
     * @name 超管操作日志
     */
    public function mainAction()
    {
    }
    
    /**
     * @page superoperationlog
     * @point 列表
     */
    public function listAction()
    {
        $data = SuperOperationlogService::getInstance()->list($this->params);
        return $this->outputSuccess($data["data"], ["total" => $data["total"]]);
    }

    /**
     * @page superoperationlog
     * @point 详情
     */
    public function logAction()
    {
        $data = SuperOperationlogService::getInstance()->log($this->params);
        return $this->outputSuccess($data["data"], ["total" => $data["total"]]);
    }
    
}