<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Probability\LevelAreaValidation;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Operate\Play\Probability\LevelAreaService;

class LuckyfruitlevelfirstchargeController extends BaseController
{
    /**
     * @var LevelAreaService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LevelAreaService();
    }

    /**
     * @page luckyfruitlevelfirstcharge
     * @name Lucky Fruit Level&First Charge
     */
    public function mainAction()
    {
    }

    /**
     * @page luckyfruitlevelfirstcharge
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'switch') {
            $data = $this->service->getFirstChargeSwitch(XsGlobalConfig::GAME_CENTER_ID_LUCKY_FRUIT);
            return $this->outputSuccess($data);
        }
        $res = $this->service->getList(XsGlobalConfig::GAME_CENTER_ID_LUCKY_FRUIT);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page luckyfruitlevelfirstcharge
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'luckyfruitlevel', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        LevelAreaValidation::make()->validators($this->params);
        $this->params['game_id'] = XsGlobalConfig::GAME_CENTER_ID_LUCKY_FRUIT;
        $this->service->edit($this->params);
        return $this->outputSuccess($this->params);
    }

    /**
     * @page luckyfruitlevelfirstcharge
     * @point 修改首冲开关
     * @logRecord(content = '修改首冲开关', action = '1', model = 'luckyfruitfirstcharge', model_id = 'game_center_id')
     */
    public function modifySwitchAction()
    {
        $this->params['game_center_id'] = XsGlobalConfig::GAME_CENTER_ID_LUCKY_FRUIT;
        $this->service->modifySwitch($this->params);
        return $this->outputSuccess($this->params);
    }
}