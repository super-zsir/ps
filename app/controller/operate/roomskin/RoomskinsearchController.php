<?php

namespace Imee\Controller\Operate\Roomskin;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Roomskin\RoomSkinSearchValidation;
use Imee\Service\Operate\Roomskin\RoomSkinSearchService;

class RoomskinsearchController extends BaseController
{
    /**
     * @var RoomSkinSearchService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomSkinSearchService();
    }
    
    /**
	 * @page roomskinsearch
	 * @name 房间皮肤查询
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page roomskinsearch
	 * @point 列表
	 */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
	 * @page roomskinsearch
	 * @point 回收
     * @logRecord(content = '回收天数', action = '1', model = 'roomskinsearch', model_id = 'uid')
	 */
    public function recoveryAction()
    {
        RoomSkinSearchValidation::make()->validators($this->params);
        $this->service->recovery($this->params);
        return $this->outputSuccess(['after_json' => $this->params]);
    }
}