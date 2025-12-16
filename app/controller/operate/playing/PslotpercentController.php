<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Pslot\PercentService;

class PslotpercentController extends BaseController
{
    /**
     * @var PercentService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PercentService();
    }
    
    /**
     * @page pslotpercent
     * @name Greedyslot Percent
     */
    public function mainAction()
    {
    }
    
    /**
     * @page pslotpercent
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getPercentList($this->params);
        return $this->outputSuccess($data, ['dev' => ENV == 'dev']);
    }
    
    /**
     * @page pslotpercent
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'pslotpercent', model_id = 'list_tab_key')
     */
    public function modifyAction()
    {
        $data = $this->service->modifyPercent($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page pslotpercent
     * @point 模拟
     */
    public function testAction()
    {
        $data = $this->service->modifyPercent($this->params, true);
        return $this->outputSuccess($data);
    }
}