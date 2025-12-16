<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Cp\IntimateRelationEnforceLogService;

class IntimaterelationenforcelogController extends BaseController
{
    /** @var IntimateRelationEnforceLogService $service */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new IntimateRelationEnforceLogService();
    }

    /**
     * @page intimaterelationenforcelog
     * @name 解除卡使用记录
     */
    public function mainAction()
    {
    }

    /**
     * @page intimaterelationenforcelog
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }
}