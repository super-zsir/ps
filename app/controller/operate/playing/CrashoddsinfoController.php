<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Crash\OddsService;

class CrashoddsinfoController extends BaseController
{
    /** @var OddsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OddsService();
    }
    
    /**
     * @page crashoddsinfo
     * @name Crash Odds Info
     */
    public function mainAction()
    {
    }
    
    /**
     * @page crashoddsinfo
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }
}