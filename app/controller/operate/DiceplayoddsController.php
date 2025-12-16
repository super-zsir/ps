<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Dice\OddsService;

class DiceplayoddsController extends BaseController
{
    /** @var OddsService $service */
    private $service;

    protected function onConstruct()
    {
        $this->allowSort = ['id'];
        parent::onConstruct();
        $this->service = new OddsService();
    }

    /**
     * @page diceplayodds
     * @name Dice玩法配置-Dice预期配置
     */
    public function mainAction()
    {
    }

    /**
     * @page diceplayodds
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess($res);
    }

    /**
     * @page  diceplayodds
     * @point 修改
     * @logRecord(content = "修改Dice预期配置", action = "1", model = "diceplayodds", model_id = "id")
     */
    public function modifyAction()
    {
        if (!isset($this->params['hit_rate']) || !is_numeric($this->params['hit_rate'])) {
            return $this->outputError('-1', '权重必须填写且为数字');
        }
        [$result, $data] = $this->service->modify($this->params['id'], $this->params['hit_rate']);
        if (!$result) {
            return $this->outputError('-1', $data);
        }
        return $this->outputSuccess($data);
    }

}