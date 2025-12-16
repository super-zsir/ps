<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Dragontiger\OddsService;

class DragontigeroddsController extends BaseController
{
    /** @var OddsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OddsService();
    }
    
    /**
	 * @page dragontigerodds
	 * @name Dragon Tiger Percent
	 */
    public function mainAction()
    {
    }

    /**
     * @page dragontigerodds
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page  dragontigerodds
     * @point 修改
     * @logRecord(content = "修改Dragon Tiger预期配置", action = "1", model = "dragontigerodds", model_id = "id")
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}