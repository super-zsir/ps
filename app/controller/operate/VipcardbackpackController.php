<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Vip\VipCardBackPackService;

class VipcardbackpackController extends BaseController
{
    /**
     * @var VipCardBackPackService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new VipCardBackPackService();
    }
    
    /**
     * @page vipcardbackpack
     * @name VIP卡背包
     */
    public function mainAction()
    {
    }
    
    /**
     * @page vipcardbackpack
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page vipcardbackpack
     * @point 回收
     * @logRecord(content = '回收', action = '1', model = 'vipcardbackpack', model_id = 'id')
     */
    public function recycleAction()
    {
        $data = $this->service->recycle($this->params);
        return $this->outputSuccess($data);
    }
}