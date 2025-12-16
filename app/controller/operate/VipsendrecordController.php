<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\VipsendService;

class VipsendrecordController extends BaseController
{
    /**
     * @var VipsendService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new VipsendService();
    }
    
    /**
     * @page vipsendrecord
     * @name VIP赠送记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page vipsendrecord
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getRecordList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}