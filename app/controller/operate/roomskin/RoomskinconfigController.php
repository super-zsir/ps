<?php

namespace Imee\Controller\Operate\Roomskin;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Roomskin\RoomSkinConfigValidation;
use Imee\Service\Operate\Roomskin\RoomSkinConfigService;

class RoomskinconfigController extends BaseController
{
    /**
     * @var RoomSkinConfigService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomSkinConfigService();
    }
    
    /**
	 * @page roomskinconfig
	 * @name 房间皮肤配置
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page roomskinconfig
	 * @point 列表
	 */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
	 * @page roomskinconfig
	 * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'roomskinconfig', model_id = 'id')
	 */
    public function createAction()
    {
        RoomSkinConfigValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['id' => $msg, 'after_json' => $this->params]);
    }
    
    /**
	 * @page roomskinconfig
	 * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'roomskinconfig', model_id = 'id')
	 */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        [$res, $msg] = $this->service->delete($id);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['before_json' => $this->params]);
    }
}