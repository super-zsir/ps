<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Dice\RegionSwitchService;

class DiceplayregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        $this->allowSort = ['id'];
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }

    /**
     * @page diceplayregionswitch
     * @name Dice玩法配置-Dice大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page diceplayregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params, 'id asc', 1, 20);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  diceplayregionswitch
     * @point 修改
     * @logRecord(content = "修改Dice大区开关", action = "1", model = "diceplayregionswitch", model_id = "id")
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