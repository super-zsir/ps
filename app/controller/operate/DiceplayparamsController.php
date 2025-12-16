<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Dice\ParamsService;

class DiceplayparamsController extends BaseController
{
    /** @var ParamsService $service */
    private $service;

    protected function onConstruct()
    {
        $this->allowSort = ['id'];
        parent::onConstruct();
        $this->service = new ParamsService();
    }

    /**
     * @page diceplayparams
     * @name Dice玩法配置-Dice参数配置
     */
    public function mainAction()
    {
    }

    /**
     * @page diceplayparams
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page  diceplayparams
     * @point 修改
     * @logRecord(content = "修改Dice参数配置", action = "1", model = "diceplayparams", model_id = "id")
     */
    public function modifyAction()
    {
        if (!isset($this->params['weight']) || !is_numeric($this->params['weight'])) {
            return $this->outputError('-1', '数值必须填写且为数字');
        }
        [$result, $data] = $this->service->modify($this->params['id'], $this->params['name'], $this->params['weight']);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

}