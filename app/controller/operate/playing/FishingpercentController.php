<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Fishing\FishingPercentValidation;
use Imee\Service\Operate\Play\Fishing\FishingPercentService;

class FishingpercentController extends BaseController
{
    /**
     * @var FishingPercentService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FishingPercentService();
    }
    
    /**
     * @page fishingpercent
     * @name Fishing Percent
     */
    public function mainAction()
    {
    }
    
    /**
     * @page fishingpercent
     * @point 列表
     */
    public function listAction()
    {
        return $this->outputSuccess($this->service->getList());
    }

    /**
     * @page fishingpercent
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'fishingpercent', model_id = 'fishid')
     */
    public function modifyAction()
    {
        FishingPercentValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}