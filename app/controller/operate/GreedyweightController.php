<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Greedy\WeightService;

class GreedyweightController extends BaseController
{
    /** @var WeightService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new WeightService();
    }

    /**
     * @page greedyweight
     * @name 玩法管理-GreedyStar玩法配置-GreedyStar 权重预期配置
     */
    public function mainAction()
    {
    }

    /**
     * @page greedyweight
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res);
    }

    /**
     * @page greedyweight
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'greedyweight', model_id = 'id')
     */
    public function modifyAction()
    {
        $id = array_get($this->params,'id');
        $hitRate = array_get($this->params,'hit_rate');
        $greedyEngineId = array_get($this->params,'greedy_engine_id');
        if (!is_numeric($id) || !is_numeric($hitRate) || $id < 0 || $hitRate < 0) {
            return $this->outputError(-1, 'ID/权重值必须填写且不能小于0');
        }
        [$result, $data] = $this->service->modify($id, $hitRate, $greedyEngineId);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

}