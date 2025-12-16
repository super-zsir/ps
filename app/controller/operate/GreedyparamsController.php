<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Greedy\ParamsService;

class GreedyparamsController extends BaseController
{
    /** @var ParamsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ParamsService();
    }

    /**
     * @page greedyparams
     * @name 玩法管理-GreedyStar玩法配置-GreedyStar 参数配置
     */
    public function mainAction()
    {
    }

    /**
     * @page greedyparams
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res);
    }

    /**
     * @page greedyparams
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'greedyparams', model_id = 'id')
     */
    public function modifyAction()
    {
        $id = array_get($this->params,'id');
        $number = array_get($this->params,'number');
        $greedyEngineId = array_get($this->params,'greedy_engine_id');
        if (!is_numeric($id) || !is_numeric($number) || $id < 0 || $number < 0) {
            return $this->outputError(-1, 'ID/数值预期必须填写且不能小于0');
        }
        [$result, $data] = $this->service->modify($id, $number, $greedyEngineId);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
}