<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Fishing\FishingFromService;

class FishingfrominfoController extends BaseController
{
    /** @var FishingFromService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FishingFromService();
    }
    
    /**
     * @page fishingfrominfo
     * @name Fishing From Info
     */
    public function mainAction()
    {
    }
    
    /**
     * @page fishingfrominfo
     * @point 列表
     */
    public function listAction()
    {
        return $this->outputSuccess($this->service->getInfoList());
    }
}