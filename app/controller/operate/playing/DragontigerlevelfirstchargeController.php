<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Probability\LevelAreaValidation;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Operate\Play\Probability\LevelAreaService;

class DragontigerlevelfirstchargeController extends BaseController
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
     * @page dragontigerlevelfirstcharge
     * @name Dragon Tiger Level&First Charge
     */
    public function mainAction()
    {
    }

    /**
     * @page dragontigerlevelfirstcharge
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'switch') {
            $data = $this->service->getFirstChargeSwitch(XsGlobalConfig::GAME_CENTER_ID_END);
            return $this->outputSuccess($data);
        }
        $res = $this->service->getList(XsGlobalConfig::GAME_CENTER_ID_END);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page dragontigerlevelfirstcharge
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'dragontigerlevel', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        LevelAreaValidation::make()->validators($this->params);
        $this->params['game_id'] = XsGlobalConfig::GAME_CENTER_ID_END;
        $this->service->edit($this->params);
        return $this->outputSuccess($this->params);
    }

    /**
     * @page dragontigerlevelfirstcharge
     * @point 修改首冲开关
     * @logRecord(content = '修改首冲开关', action = '1', model = 'dragontigerfirstcharge', model_id = 'game_center_id')
     */
    public function modifySwitchAction()
    {
        $this->params['game_center_id'] = XsGlobalConfig::GAME_CENTER_ID_END;
        $this->service->modifySwitch($this->params);
        return $this->outputSuccess($this->params);
    }
}