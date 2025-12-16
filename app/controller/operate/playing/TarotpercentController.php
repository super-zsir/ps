<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Tarot\OddsService;

class TarotpercentController extends BaseController
{
    /**
     * @var OddsService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OddsService();
    }
    
    /**
     * @page tarotpercent
     * @name Tarot Percent
     */
    public function mainAction()
    {
    }
    
    /**
     * @page tarotpercent
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page tarotpercent
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'tarotpercent', model_id = 'index')
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}