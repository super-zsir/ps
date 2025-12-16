<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Greedy\BoxRegionSwitchService;

class GreedyboxregionswitchController extends BaseController
{
    /** @var BoxRegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new BoxRegionSwitchService();
    }

    /**
     * @page greedyboxregionswitch
     * @name 玩法管理-GreedyStar玩法配置-Greedystar宝箱开关
     */
    public function mainAction()
    {
    }

    /**
     * @page greedyboxregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  greedyboxregionswitch
     * @point 修改
     * @logRecord(content = "修改GreedyBox大区开关", action = "1", model = "greedyboxregionswitch", model_id = "id")
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