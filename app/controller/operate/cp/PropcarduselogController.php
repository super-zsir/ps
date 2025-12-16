<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Cp\PropCardUseLogService;

class PropcarduselogController extends BaseController
{
    /** @var PropCardUseLogService $service */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PropCardUseLogService();
    }

    /**
     * @page propcarduselog
     * @name 解除卡使用记录
     */
    public function mainAction()
    {
    }

    /**
     * @page propcarduselog
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

}