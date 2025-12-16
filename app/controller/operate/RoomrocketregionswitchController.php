<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Roomrocket\RoomRocketRegionSwitchService;

class RoomrocketregionswitchController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomRocketRegionSwitchService();
    }

    /**
     * @page roomrocketregionswitch
     * @name 玩法管理-爆火箭玩法配置-爆火箭玩法大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page roomrocketregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page roomrocketregionswitch
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'roomrocketregionswitch', model_id = 'id')
     */
    public function modifyAction()
    {
        [$result, $data] = $this->service->modify($this->params['bigarea_id'], $this->params['switch']);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
}