<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Greedy\RegionSwitchService;

class GreedyplayregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }

    /**
     * @page greedyplayregionswitch
     * @name 玩法管理-GreedyStar玩法配置-GreedyStar玩法大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page greedyplayregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  greedyplayregionswitch
     * @point 修改
     * @logRecord(content = "修改Greedy大区开关", action = "1", model = "greedyplayregionswitch", model_id = "id")
     */
    public function modifyAction()
    {
        [$result, $data] = $this->service->modify($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
}