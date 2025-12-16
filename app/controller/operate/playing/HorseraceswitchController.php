<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Horserace\RegionSwitchService;
use Imee\Controller\Validation\Operate\Play\Tarot\RegionSwitchValidation;

class HorseraceswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }

    /**
     * @page horseraceswitch
     * @name Horse Region
     */
    public function mainAction()
    {
    }

    /**
     * @page horseraceswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page horseraceswitch
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'horseraceswitch', model_id = 'big_area_id')
     */
    public function modifyAction()
    {
        RegionSwitchValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);        
    }
}